<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/ProductCatalog.php';

if ($argc < 2) {
    fwrite(STDERR, "Uso: php tools/import_request_catalog.php archivo.xlsx\n");
    exit(1);
}

$path = (string) $argv[1];
$pdo = db();
$pdo->beginTransaction();

try {
    $result = import_request_catalog($pdo, $path, basename($path));
    $pdo->commit();
    echo 'Catalogo importado: ' . $result['total'] . " productos.\n";
    foreach ($result['counts'] as $code => $count) {
        echo sprintf("- %s: %d\n", $code, $count);
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
