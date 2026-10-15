<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime.php';

function db_driver(?PDO $pdo = null): string
{
    if ($pdo instanceof PDO) {
        return (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    return strtolower((string) app_env('SERVIMETERS_DB_DRIVER', 'pgsql'));
}

function db_is_postgres(?PDO $pdo = null): bool
{
    return db_driver($pdo) === 'pgsql';
}

function db_last_insert_id(PDO $pdo): int
{
    if (db_is_postgres($pdo)) {
        return (int) $pdo->query('SELECT LASTVAL()')->fetchColumn();
    }
    return (int) $pdo->lastInsertId();
}

function db_like_operator(PDO $pdo): string
{
    return db_is_postgres($pdo) ? 'ILIKE' : 'LIKE';
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = db_driver();
    if (!in_array($driver, ['mysql', 'pgsql'], true)) {
        throw new RuntimeException('SERVIMETERS_DB_DRIVER debe ser mysql o pgsql.');
    }
    $host = app_env_required('SERVIMETERS_DB_HOST');
    $port = (string) app_env('SERVIMETERS_DB_PORT', $driver === 'pgsql' ? '5432' : '3306');
    $database = app_env_required('SERVIMETERS_DB_NAME');
    $user = app_env_required('SERVIMETERS_DB_USERNAME');
    $password = (string) app_env('SERVIMETERS_DB_PASSWORD', '');

    $dsn = $driver === 'pgsql'
        ? "pgsql:host={$host};port={$port};dbname={$database};sslmode=" . app_env('SERVIMETERS_DB_SSLMODE', 'prefer')
        : "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        if ($driver === 'pgsql') {
            $schema = (string) app_env('SERVIMETERS_DB_SCHEMA', 'servimeters_producto');
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $schema)) {
                throw new RuntimeException('SERVIMETERS_DB_SCHEMA no es un identificador válido.');
            }
            $pdo->exec('SET search_path TO "' . $schema . '", public');
            $pdo->exec('SET TIME ZONE ' . $pdo->quote((string) app_env('SERVIMETERS_TIMEZONE', 'America/Bogota')));
        }
        return $pdo;
    } catch (PDOException $exception) {
        throw new RuntimeException('Error de conexion a la base de datos.', 0, $exception);
    }
}
