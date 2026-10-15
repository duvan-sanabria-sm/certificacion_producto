<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/ServiceRequestExtractor.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('requests.create');

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Metodo no permitido.');
    }
    auth_assert_csrf();

    $file = $_FILES['solicitud_servicio'] ?? null;
    if (!$file || (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
        throw new RuntimeException('Adjunta una solicitud de servicio valida.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($extension !== 'xlsx') {
        throw new RuntimeException('La extraccion automatica esta disponible para solicitudes .xlsx.');
    }

    $result = extract_service_request_from_xlsx((string) $file['tmp_name']);

    echo json_encode([
        'ok' => true,
        'message' => 'Solicitud leida correctamente.',
        'fields' => $result['fields'],
        'sources' => $result['sources'],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
