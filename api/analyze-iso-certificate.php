<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';

$authUser = auth_require_permission('technical_review.edit');
header('Content-Type: application/json; charset=utf-8');

function iso_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function iso_run_python(array $command, int $timeoutSeconds): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        throw new RuntimeException('No fue posible iniciar el analizador de certificados.');
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = '';
    $stderr = '';
    $startedAt = microtime(true);
    do {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) - $startedAt > $timeoutSeconds) {
            proc_terminate($process);
            throw new RuntimeException('El análisis ISO excedió el tiempo permitido.');
        }
        usleep(100000);
    } while (true);
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $result = json_decode(trim($stdout), true);
    if (!is_array($result) || empty($result['ok'])) {
        $detail = trim($stderr) !== '' ? trim($stderr) : trim($stdout);
        throw new RuntimeException('Python no pudo analizar el certificado (código ' . $exitCode . '). ' . mb_substr($detail, 0, 800));
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    iso_response(['ok' => false, 'message' => 'Método no permitido.'], 405);
}

$newFilePath = null;
$newFilePersisted = false;
try {
    auth_assert_csrf();
    $requestId = (int) ($_POST['solicitud_id'] ?? 0);
    $attachmentId = (int) ($_POST['adjunto_id'] ?? 0);
    if ($requestId <= 0) {
        throw new RuntimeException('Selecciona una solicitud válida.');
    }
    $pdo = db();
    if (!auth_can_access_request($pdo, $requestId)) {
        throw new RuntimeException('No tienes acceso a la solicitud seleccionada.');
    }

    $requestStatement = $pdo->prepare(
        'SELECT s.id, s.codigo_solicitud, s.datos_preliminares_json
         FROM solicitudes_servicio s WHERE s.id = :id'
    );
    $requestStatement->execute(['id' => $requestId]);
    $request = $requestStatement->fetch();
    if (!$request) {
        throw new RuntimeException('La solicitud no existe.');
    }
    $productStatement = $pdo->prepare(
        'SELECT producto, fabricante FROM solicitud_productos WHERE solicitud_servicio_id = :id ORDER BY id'
    );
    $productStatement->execute(['id' => $requestId]);
    $productRows = $productStatement->fetchAll();
    $products = array_values(array_unique(array_filter(array_map(
        static fn(array $row): string => trim((string) ($row['producto'] ?? '')),
        $productRows
    ))));
    $manufacturers = array_values(array_unique(array_filter(array_map(
        static fn(array $row): string => trim((string) ($row['fabricante'] ?? '')),
        $productRows
    ))));

    $certificatePath = '';
    $storedAttachmentId = $attachmentId;
    $uploaded = $_FILES['certificado_iso'] ?? null;
    if (is_array($uploaded) && (int) ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ((int) ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No fue posible recibir el certificado ISO.');
        }
        if ((int) ($uploaded['size'] ?? 0) <= 0 || (int) ($uploaded['size'] ?? 0) > 20 * 1024 * 1024) {
            throw new RuntimeException('El certificado ISO está vacío o supera 20 MB.');
        }
        $extension = strtolower(pathinfo((string) ($uploaded['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new RuntimeException('El certificado debe estar en PDF, JPG, PNG o WEBP.');
        }
        $stored = store_uploaded_file($uploaded, 'storage/uploads/solicitudes');
        $newFilePath = (string) $stored['absolute_path'];
        $pdo->beginTransaction();
        $fileStatement = $pdo->prepare(
            'INSERT INTO archivos_cargados
              (tipo_archivo, nombre_original, nombre_guardado, ruta_relativa, extension, mime_type,
               tamano_bytes, hash_sha256, estado, mensaje_validacion, creado_por)
             VALUES
              (:tipo, :original, :guardado, :ruta, :extension, :mime, :tamano, :hash, :estado, :mensaje, :creado_por)'
        );
        $fileStatement->execute([
            'tipo' => 'anexo',
            'original' => $stored['original_name'],
            'guardado' => $stored['stored_name'],
            'ruta' => $stored['relative_path'],
            'extension' => $stored['extension'],
            'mime' => $stored['mime_type'],
            'tamano' => $stored['size'],
            'hash' => $stored['sha256'],
            'estado' => 'procesado',
            'mensaje' => 'Certificado ISO cargado durante la revisión técnica.',
            'creado_por' => $authUser['usuario'],
        ]);
        $fileId = db_last_insert_id($pdo);
        $pdo->prepare(
            'INSERT INTO solicitud_adjuntos (solicitud_servicio_id, archivo_cargado_id, categoria)
             VALUES (:solicitud, :archivo, :categoria)'
        )->execute(['solicitud' => $requestId, 'archivo' => $fileId, 'categoria' => 'certificado_iso_9001_revision']);
        $storedAttachmentId = (int) db_last_insert_id($pdo);
        $pdo->commit();
        $newFilePersisted = true;
        $certificatePath = $newFilePath;
    } elseif ($attachmentId > 0) {
        $attachmentStatement = $pdo->prepare(
            "SELECT sa.id, ac.ruta_relativa
             FROM solicitud_adjuntos sa
             INNER JOIN archivos_cargados ac ON ac.id = sa.archivo_cargado_id
             WHERE sa.id = :adjunto AND sa.solicitud_servicio_id = :solicitud
               AND sa.categoria IN ('certificado_iso_9001', 'certificado_iso_9001_revision')"
        );
        $attachmentStatement->execute(['adjunto' => $attachmentId, 'solicitud' => $requestId]);
        $attachment = $attachmentStatement->fetch();
        if (!$attachment) {
            throw new RuntimeException('El certificado seleccionado no pertenece a la solicitud.');
        }
        $certificatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $attachment['ruta_relativa']);
    }
    if ($certificatePath === '' || !is_file($certificatePath)) {
        throw new RuntimeException('Selecciona o adjunta un certificado ISO para analizar.');
    }

    $config = require __DIR__ . '/../config/analysis.php';
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'iso_certificate_analyzer.py';
    if (!is_file((string) $config['python_path']) || !is_file($script)) {
        throw new RuntimeException('El analizador Python de certificados no está disponible.');
    }
    $result = iso_run_python([
        (string) $config['python_path'], '-X', 'utf8', $script,
        '--certificate', $certificatePath,
        '--products-json', json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        '--manufacturers-json', json_encode($manufacturers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ], (int) $config['timeout_seconds']);
    $result['attachment_id'] = $storedAttachmentId;
    $result['request_code'] = $request['codigo_solicitud'];
    iso_response($result);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!$newFilePersisted && $newFilePath !== null && is_file($newFilePath)) {
        @unlink($newFilePath);
    }
    iso_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
