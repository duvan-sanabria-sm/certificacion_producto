<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$mode = $argv[1] ?? 'setup';
$pdo = db();

$cleanup = static function (PDO $connection): void {
    $requestIds = $connection->query(
        "SELECT id FROM solicitudes_servicio
         WHERE codigo_solicitud LIKE 'AUTH-TEST-%' OR creado_por LIKE '__test\\_%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($requestIds) {
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $connection->prepare("DELETE FROM revisiones_tecnicas WHERE solicitud_servicio_id IN ({$placeholders})")->execute($requestIds);
    }
    $quoteIds = $connection->query(
        "SELECT p.id FROM precotizaciones p
         INNER JOIN solicitudes_servicio s ON s.id = p.solicitud_servicio_id
         WHERE s.codigo_solicitud LIKE 'AUTH-TEST-%' OR s.creado_por LIKE '__test\\_%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($quoteIds) {
        $placeholders = implode(',', array_fill(0, count($quoteIds), '?'));
        $connection->prepare("DELETE FROM precotizacion_ensayos WHERE precotizacion_id IN ({$placeholders})")->execute($quoteIds);
        $connection->prepare("DELETE FROM precotizacion_servicios WHERE precotizacion_id IN ({$placeholders})")->execute($quoteIds);
        $connection->prepare("DELETE FROM precotizaciones WHERE id IN ({$placeholders})")->execute($quoteIds);
    }
    $connection->exec("DELETE FROM solicitudes_servicio WHERE codigo_solicitud LIKE 'AUTH-TEST-%' OR creado_por LIKE '__test\\_%'");
    $connection->exec("DELETE FROM usuarios WHERE usuario LIKE '__test\\_%'");
};

if ($mode === 'cleanup') {
    $cleanup($pdo);
    echo json_encode(['ok' => true, 'removed' => true], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

$cleanup($pdo);
$roles = ['administrador', 'comercial_1', 'comercial_2', 'coordinador_tecnico', 'jefe_tecnico'];
$password = 'Tmp-' . bin2hex(random_bytes(8)) . '-9a';
$commercialIds = array_map('intval', $pdo->query("SELECT id FROM comerciales WHERE activo = 1 ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_COLUMN));
$commercialId = $commercialIds[0] ?? 0;
$statement = $pdo->prepare(db_is_postgres($pdo)
    ? 'INSERT INTO usuarios
      (nombre_completo, usuario, correo, password_hash, rol, comercial_id, activo, cambiar_password)
     VALUES
      (:nombre, :usuario, NULL, :password_hash, :rol, :comercial_id, 1, 0)
     ON CONFLICT (usuario) DO UPDATE SET
      password_hash = EXCLUDED.password_hash, rol = EXCLUDED.rol, comercial_id = EXCLUDED.comercial_id,
      activo = 1, cambiar_password = 0, intentos_fallidos = 0, bloqueado_hasta = NULL'
    : 'INSERT INTO usuarios
      (nombre_completo, usuario, correo, password_hash, rol, comercial_id, activo, cambiar_password)
     VALUES
      (:nombre, :usuario, NULL, :password_hash, :rol, :comercial_id, 1, 0)
     ON DUPLICATE KEY UPDATE
      password_hash = VALUES(password_hash), rol = VALUES(rol), comercial_id = VALUES(comercial_id),
      activo = 1, cambiar_password = 0, intentos_fallidos = 0, bloqueado_hasta = NULL');

$users = [];
foreach ($roles as $role) {
    $username = '__test_' . $role;
    $statement->execute([
        'nombre' => 'Prueba ' . $role,
        'usuario' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'rol' => $role,
        'comercial_id' => $role === 'comercial_1' && $commercialId > 0 ? $commercialId : null,
    ]);
    $users[$role] = $username;
}

$requestStatement = $pdo->prepare(
    'INSERT INTO solicitudes_servicio (comercial_id, canal_entrada, codigo_solicitud, producto, creado_por)
     VALUES (:comercial_id, :canal, :codigo, :producto, :creado_por)'
);
$requestStatement->execute([
    'comercial_id' => $commercialId > 0 ? $commercialId : null,
    'canal' => 'interno',
    'codigo' => 'AUTH-TEST-OWN',
    'producto' => 'Producto de prueba propio',
    'creado_por' => '__test_comercial_1',
]);
$ownRequestId = db_last_insert_id($pdo);
$requestStatement->execute([
    'comercial_id' => $commercialIds[1] ?? null,
    'canal' => 'interno',
    'codigo' => 'AUTH-TEST-OTHER',
    'producto' => 'Producto de prueba ajeno',
    'creado_por' => '__test_comercial_2',
]);
$otherRequestId = db_last_insert_id($pdo);

echo json_encode([
    'ok' => true,
    'password' => $password,
    'users' => $users,
    'own_request' => 'AUTH-TEST-OWN',
    'other_request' => 'AUTH-TEST-OTHER',
    'own_request_id' => $ownRequestId,
    'other_request_id' => $otherRequestId,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
