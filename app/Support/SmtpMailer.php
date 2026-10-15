<?php
declare(strict_types=1);

function smtp_read_response($socket, array $expectedCodes): string
{
    $response = '';
    while (($line = fgets($socket, 1024)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('El servidor SMTP rechazo la operacion con codigo ' . $code . '.');
    }

    return $response;
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_read_response($socket, $expectedCodes);
}

function smtp_header_value(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function smtp_send_html(array $config, string $toEmail, string $toName, string $subject, string $html, string $replyTo): void
{
    $host = (string) ($config['host'] ?? '');
    $port = (int) ($config['port'] ?? 587);
    $timeout = max(5, (int) ($config['timeout'] ?? 15));
    $transport = (($config['encryption'] ?? '') === 'ssl' ? 'ssl' : 'tcp') . '://' . $host . ':' . $port;

    $socket = @stream_socket_client($transport, $errorNumber, $errorMessage, $timeout, STREAM_CLIENT_CONNECT);
    if (!is_resource($socket)) {
        throw new RuntimeException('No fue posible conectar con el servidor SMTP.');
    }

    try {
        stream_set_timeout($socket, $timeout);
        smtp_read_response($socket, [220]);
        smtp_command($socket, 'EHLO localhost', [250]);

        if (($config['encryption'] ?? '') === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No fue posible activar la conexion segura SMTP.');
            }
            smtp_command($socket, 'EHLO localhost', [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode((string) $config['username']), [334]);
        smtp_command($socket, base64_encode((string) $config['password']), [235]);
        smtp_command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . smtp_header_value((string) $config['from_name']) . ' <' . $config['from_email'] . '>',
            'To: ' . smtp_header_value($toName) . ' <' . $toEmail . '>',
            'Reply-To: ' . $replyTo,
            'Subject: ' . smtp_header_value($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html));
        $message = preg_replace('/(?m)^\./', '..', $message) ?? $message;
        fwrite($socket, $message . "\r\n.\r\n");
        smtp_read_response($socket, [250]);
        smtp_command($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

function smtp_is_configured(array $config): bool
{
    return !empty($config['enabled'])
        && trim((string) ($config['host'] ?? '')) !== ''
        && trim((string) ($config['username'] ?? '')) !== ''
        && trim((string) ($config['password'] ?? '')) !== ''
        && filter_var((string) ($config['from_email'] ?? ''), FILTER_VALIDATE_EMAIL);
}

function notify_service_request(PDO $pdo, array $request, array $commercial, array $products): string
{
    $config = require __DIR__ . '/../../config/mail.php';
    $subject = 'Nueva solicitud de certificacion ' . $request['codigo_solicitud'];
    $recipientEmail = (string) $commercial['correo'];
    $recipientName = (string) $commercial['nombre_completo'];
    $productItems = '';
    foreach ($products as $product) {
        $productItems .= '<li><strong>' . htmlspecialchars((string) $product['producto'], ENT_QUOTES, 'UTF-8') . '</strong>';
        if (!empty($product['referencia'])) {
            $productItems .= ' - Referencia ' . htmlspecialchars((string) $product['referencia'], ENT_QUOTES, 'UTF-8');
        }
        $productItems .= '</li>';
    }

    $html = '<h2>Nueva solicitud de certificacion de producto</h2>'
        . '<p>Se registro una solicitud web asignada a ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<table cellpadding="7" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#d9e2ee">'
        . '<tr><td><strong>Solicitud</strong></td><td>' . htmlspecialchars((string) $request['codigo_solicitud'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td><strong>Cliente</strong></td><td>' . htmlspecialchars((string) $request['cliente_nombre'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td><strong>Contacto</strong></td><td>' . htmlspecialchars((string) $request['contacto_nombre'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td><strong>Correo</strong></td><td>' . htmlspecialchars((string) $request['contacto_correo'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td><strong>Telefono</strong></td><td>' . htmlspecialchars((string) $request['contacto_telefono'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td><strong>Reglamento</strong></td><td>' . htmlspecialchars((string) $request['reglamento'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table><h3>Productos solicitados</h3><ul>' . $productItems . '</ul>'
        . '<p>La solicitud y sus soportes quedaron registrados en el sistema de producto.</p>';

    $configured = smtp_is_configured($config);
    $initialStatus = $configured ? 'pendiente' : 'pendiente_configuracion';
    $insert = $pdo->prepare(
        'INSERT INTO notificaciones_correo
          (solicitud_servicio_id, comercial_id, destinatario_nombre, destinatario_correo, asunto, estado)
         VALUES (:solicitud_id, :comercial_id, :nombre, :correo, :asunto, :estado)'
    );
    $insert->execute([
        'solicitud_id' => $request['id'],
        'comercial_id' => $commercial['id'],
        'nombre' => $recipientName,
        'correo' => $recipientEmail,
        'asunto' => $subject,
        'estado' => $initialStatus,
    ]);
    $notificationId = db_last_insert_id($pdo);

    if (!$configured) {
        return 'pendiente_configuracion';
    }

    try {
        smtp_send_html($config, $recipientEmail, $recipientName, $subject, $html, (string) $request['contacto_correo']);
        $update = $pdo->prepare(
            "UPDATE notificaciones_correo
             SET estado = 'enviado', intentos = intentos + 1, enviado_en = NOW(), detalle_error = NULL
             WHERE id = :id"
        );
        $update->execute(['id' => $notificationId]);
        return 'enviado';
    } catch (Throwable $exception) {
        $update = $pdo->prepare(
            "UPDATE notificaciones_correo
             SET estado = 'fallido', intentos = intentos + 1, detalle_error = :detalle
             WHERE id = :id"
        );
        $update->execute([
            'id' => $notificationId,
            'detalle' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
        return 'fallido';
    }
}
