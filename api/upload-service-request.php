<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';
require_once __DIR__ . '/../app/Support/ServiceRequestExtractor.php';
require_once __DIR__ . '/../app/Support/Auth.php';

$authUser = auth_require_permission('requests.create');

function redirect_request(string $status, string $message): void
{
    header('Location: ../index.php?request_status=' . urlencode($status) . '&request_message=' . urlencode($message));
    exit;
}

function request_value(string $key, array $extracted, string $default = ''): string
{
    $posted = trim((string) ($_POST[$key] ?? ''));
    if ($posted !== '') {
        return $posted;
    }

    return trim((string) ($extracted[$key] ?? $default));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_request('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $file = $_FILES['solicitud_servicio'] ?? null;
    if (!$file) {
        throw new RuntimeException('Adjunta el archivo de solicitud de servicio.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'docx', 'xlsx', 'xls', 'jpg', 'jpeg', 'png'], true)) {
        throw new RuntimeException('Formato no soportado para solicitud de servicio.');
    }

    $stored = store_uploaded_file($file, 'storage/uploads/solicitudes');
    $extracted = [];

    if ($stored['extension'] === 'xlsx') {
        try {
            $extracted = extract_service_request_from_xlsx($stored['absolute_path'])['fields'] ?? [];
        } catch (Throwable) {
            $extracted = [];
        }
    }

    $clienteNombre = request_value('cliente_nombre', $extracted);
    $tipoDocumento = trim((string) ($_POST['tipo_documento'] ?? 'NIT'));
    $numeroDocumento = request_value('numero_documento', $extracted);
    $direccion = request_value('direccion', $extracted);
    $ciudad = request_value('ciudad', $extracted);
    $contactoNombre = request_value('contacto_nombre', $extracted);
    $contactoCorreo = request_value('contacto_correo', $extracted);
    $contactoTelefono = request_value('contacto_telefono', $extracted);
    $contactoCargo = request_value('contacto_cargo', $extracted);
    $lineaProductoId = (int) ($_POST['linea_producto_id'] ?? 0);
    $codigoSolicitud = trim((string) ($_POST['codigo_solicitud'] ?? ''));
    $servicioSolicitado = request_value('servicio_solicitado', $extracted);
    $producto = request_value('producto', $extracted);
    $referenciaProducto = request_value('referencia_producto', $extracted);
    $cantidadMuestras = request_value('cantidad_muestras', $extracted);
    $reglamentoNorma = request_value('reglamento_norma', $extracted);
    $observaciones = request_value('observaciones', $extracted);

    $assignedCommercialId = (int) ($authUser['comercial_id'] ?? 0);
    if (($authUser['rol'] ?? '') === 'comercial_1' && $assignedCommercialId <= 0) {
        throw new RuntimeException('Tu usuario Comercial 1 debe estar asociado a un comercial antes de registrar solicitudes.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    if ($lineaProductoId <= 0 && !empty($extracted['linea_producto_codigo'])) {
        $lineStatement = $pdo->prepare('SELECT id FROM lineas_producto WHERE codigo = :codigo LIMIT 1');
        $lineStatement->execute(['codigo' => $extracted['linea_producto_codigo']]);
        $lineaProductoId = (int) ($lineStatement->fetchColumn() ?: 0);
    }

    $fileStatement = $pdo->prepare(
        'INSERT INTO archivos_cargados
          (tipo_archivo, nombre_original, nombre_guardado, ruta_relativa, extension, mime_type, tamano_bytes, hash_sha256, estado, mensaje_validacion)
         VALUES
          (:tipo_archivo, :nombre_original, :nombre_guardado, :ruta_relativa, :extension, :mime_type, :tamano_bytes, :hash_sha256, :estado, :mensaje_validacion)'
    );
    $fileStatement->execute([
        'tipo_archivo' => 'solicitud_servicio',
        'nombre_original' => $stored['original_name'],
        'nombre_guardado' => $stored['stored_name'],
        'ruta_relativa' => $stored['relative_path'],
        'extension' => $stored['extension'],
        'mime_type' => $stored['mime_type'],
        'tamano_bytes' => $stored['size'],
        'hash_sha256' => $stored['sha256'],
        'estado' => 'recibido',
        'mensaje_validacion' => 'Solicitud recibida para identificacion preliminar.',
    ]);
    $archivoId = db_last_insert_id($pdo);

    $clienteId = null;
    if ($clienteNombre !== '') {
        $clientSql = db_is_postgres($pdo)
            ? 'INSERT INTO clientes
                (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo, contacto_telefono, direccion, ciudad)
               VALUES
                (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo, :contacto_telefono, :direccion, :ciudad)
               ON CONFLICT (tipo_documento, numero_documento) DO UPDATE SET
                razon_social = EXCLUDED.razon_social,
                contacto_nombre = EXCLUDED.contacto_nombre,
                contacto_correo = EXCLUDED.contacto_correo,
                contacto_telefono = EXCLUDED.contacto_telefono,
                direccion = EXCLUDED.direccion,
                ciudad = EXCLUDED.ciudad
               RETURNING id'
            : 'INSERT INTO clientes
                (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo, contacto_telefono, direccion, ciudad)
               VALUES
                (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo, :contacto_telefono, :direccion, :ciudad)
               ON DUPLICATE KEY UPDATE
                razon_social = VALUES(razon_social),
                contacto_nombre = VALUES(contacto_nombre),
                contacto_correo = VALUES(contacto_correo),
                contacto_telefono = VALUES(contacto_telefono),
                direccion = VALUES(direccion),
                ciudad = VALUES(ciudad),
                id = LAST_INSERT_ID(id)';
        $clientStatement = $pdo->prepare($clientSql);
        $clientStatement->execute([
            'tipo_documento' => $tipoDocumento ?: null,
            'numero_documento' => $numeroDocumento ?: null,
            'razon_social' => $clienteNombre,
            'contacto_nombre' => $contactoNombre ?: null,
            'contacto_correo' => $contactoCorreo ?: null,
            'contacto_telefono' => $contactoTelefono ?: null,
            'direccion' => $direccion ?: null,
            'ciudad' => $ciudad ?: null,
        ]);
        $clienteId = db_is_postgres($pdo) ? (int) $clientStatement->fetchColumn() : db_last_insert_id($pdo);
    }

    $preliminaryData = [
        'cliente_nombre' => $clienteNombre,
        'tipo_documento' => $tipoDocumento,
        'numero_documento' => $numeroDocumento,
        'contacto_nombre' => $contactoNombre,
        'contacto_correo' => $contactoCorreo,
        'contacto_telefono' => $contactoTelefono,
        'contacto_cargo' => $contactoCargo,
        'direccion' => $direccion,
        'ciudad' => $ciudad,
        'producto' => $producto,
        'referencia_producto' => $referenciaProducto,
        'cantidad_muestras' => $cantidadMuestras,
        'reglamento_norma' => $reglamentoNorma,
        'observaciones' => $observaciones,
    ];

    $requestStatement = $pdo->prepare(
        'INSERT INTO solicitudes_servicio
          (archivo_cargado_id, cliente_id, linea_producto_id, comercial_id, canal_entrada,
           atendido_por_comercial, codigo_solicitud, servicio_solicitado, producto, referencia_producto,
           cantidad_muestras, reglamento_norma, datos_preliminares_json, observaciones, creado_por)
         VALUES
          (:archivo_cargado_id, :cliente_id, :linea_producto_id, :comercial_id, :canal_entrada,
           :atendido_por_comercial, :codigo_solicitud, :servicio_solicitado, :producto, :referencia_producto,
           :cantidad_muestras, :reglamento_norma, :datos_preliminares_json, :observaciones, :creado_por)'
    );
    $requestStatement->execute([
        'archivo_cargado_id' => $archivoId,
        'cliente_id' => $clienteId,
        'linea_producto_id' => $lineaProductoId > 0 ? $lineaProductoId : null,
        'comercial_id' => $assignedCommercialId > 0 ? $assignedCommercialId : null,
        'canal_entrada' => 'interno',
        'atendido_por_comercial' => $assignedCommercialId > 0 ? 1 : 0,
        'codigo_solicitud' => $codigoSolicitud ?: null,
        'servicio_solicitado' => $servicioSolicitado ?: null,
        'producto' => $producto ?: null,
        'referencia_producto' => $referenciaProducto ?: null,
        'cantidad_muestras' => $cantidadMuestras ?: null,
        'reglamento_norma' => $reglamentoNorma ?: null,
        'datos_preliminares_json' => json_encode($preliminaryData, JSON_UNESCAPED_UNICODE),
        'observaciones' => $observaciones ?: null,
        'creado_por' => $authUser['usuario'],
    ]);

    $requestId = db_last_insert_id($pdo);

    $pdo->commit();

    auth_log('solicitudes_servicio', (string) $requestId, 'crear_desde_archivo');

    redirect_request('ok', 'Solicitud cargada y registrada para identificacion preliminar.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_request('error', $exception->getMessage());
}
