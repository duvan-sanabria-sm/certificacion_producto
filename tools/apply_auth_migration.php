<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$pdo = db();
if (!db_is_postgres($pdo)) {
    $sql = file_get_contents(__DIR__ . '/../database/20260908_roles_usuarios.sql');
    if ($sql === false) {
        throw new RuntimeException('No fue posible leer la migración de usuarios.');
    }
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [])) as $statement) {
        $pdo->exec($statement);
    }
}

$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'")->fetchColumn();
if ($adminCount > 0) {
    echo 'Migración aplicada. Ya existe un administrador.' . PHP_EOL;
    exit;
}

$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@$%';
$password = '';
for ($i = 0; $i < 16; $i++) {
    $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}

$statement = $pdo->prepare(
    'INSERT INTO usuarios (nombre_completo, usuario, correo, password_hash, rol, activo, cambiar_password)
     VALUES (:nombre, :usuario, :correo, :password_hash, :rol, 1, 1)'
);
$statement->execute([
    'nombre' => 'Administrador del software',
    'usuario' => 'administrador',
    'correo' => 'analista.innovacion@servimeters.com',
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'rol' => 'administrador',
]);

echo 'Migración aplicada y administrador creado.' . PHP_EOL;
echo 'Usuario: administrador' . PHP_EOL;
echo 'Contraseña temporal: ' . $password . PHP_EOL;
echo 'El sistema solicitará cambiarla en el primer ingreso.' . PHP_EOL;
