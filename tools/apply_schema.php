<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$schemaPath = db_is_postgres($pdo)
    ? __DIR__ . '/../database/postgresql_schema.sql'
    : __DIR__ . '/../database/schema.sql';
$sql = file_get_contents($schemaPath);

if ($sql === false) {
    throw new RuntimeException('No fue posible leer ' . $schemaPath);
}

if (db_is_postgres($pdo)) {
    $pdo->exec($sql);
} else {
    $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: []));
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
    }
}

echo 'Esquema ' . basename($schemaPath) . ' aplicado correctamente.' . PHP_EOL;
