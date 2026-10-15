<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo json_encode(['status' => 'method_not_allowed']);
    exit;
}

$checks = ['database' => false, 'uploads' => false, 'sessions' => false, 'temporary' => false];
try {
    $checks['database'] = (int) db()->query('SELECT 1')->fetchColumn() === 1;
} catch (Throwable) {
    $checks['database'] = false;
}

$paths = [
    'uploads' => app_root_path() . '/storage/uploads',
    'sessions' => app_runtime_path('SERVIMETERS_SESSION_PATH', 'storage/sessions'),
    'temporary' => app_runtime_path('SERVIMETERS_TEMP_PATH', 'tmp'),
];
foreach ($paths as $name => $path) {
    $checks[$name] = is_dir($path) && is_writable($path);
}

$healthy = !in_array(false, $checks, true);
http_response_code($healthy ? 200 : 503);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
    echo json_encode(['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks], JSON_UNESCAPED_SLASHES);
}
