<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('requests.view');

$attachmentId = (int) ($_GET['id'] ?? 0);
if ($attachmentId <= 0) {
    http_response_code(400);
    exit('Adjunto no válido.');
}

$pdo = db();
$scope = auth_request_scope_sql('s');
$statement = $pdo->prepare(
    "SELECT a.nombre_original, a.ruta_relativa, a.mime_type, a.tamano_bytes
     FROM solicitud_adjuntos sa
     INNER JOIN archivos_cargados a ON a.id = sa.archivo_cargado_id
     INNER JOIN solicitudes_servicio s ON s.id = sa.solicitud_servicio_id
     WHERE sa.id = :id AND {$scope['sql']}
     LIMIT 1"
);
$statement->execute(array_merge(['id' => $attachmentId], $scope['params']));
$attachment = $statement->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Adjunto no encontrado.');
}

$storageRoot = realpath(__DIR__ . '/../storage');
$filePath = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', (string) $attachment['ruta_relativa']), '/'));
if ($storageRoot === false || $filePath === false || !str_starts_with(strtolower($filePath), strtolower($storageRoot . DIRECTORY_SEPARATOR)) || !is_file($filePath)) {
    http_response_code(404);
    exit('El archivo ya no está disponible.');
}

$downloadName = preg_replace('/[\r\n"]+/', '_', (string) $attachment['nombre_original']) ?: 'adjunto';
header('Content-Type: ' . ((string) ($attachment['mime_type'] ?? '') ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
