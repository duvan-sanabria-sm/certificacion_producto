<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('database.test');

try {
    $connection = db();
    $sql = db_is_postgres($connection)
        ? 'SELECT current_database() AS database_name, CURRENT_TIMESTAMP AS server_time'
        : 'SELECT DATABASE() AS database_name, CURRENT_TIMESTAMP AS server_time';
    $result = $connection->query($sql)->fetch();

    echo json_encode([
        'ok' => true,
        'message' => 'Conexion correcta.',
        'database' => $result['database_name'] ?? null,
        'server_time' => $result['server_time'] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'No fue posible conectar con la base de datos.',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
