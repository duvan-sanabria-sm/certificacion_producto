<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/MatrixTemplate.php';

$file = __DIR__ . '/../templates/plantilla_matriz_normativa.xlsx';
$matrix = read_matrix_file($file, 'xlsx');
$validation = validate_matrix_headers($matrix['headers']);

echo ($validation['ok'] ? 'OK' : 'ERROR') . PHP_EOL;
echo 'Filas de ejemplo: ' . count($matrix['rows']) . PHP_EOL;

if (!$validation['ok']) {
    echo 'Faltan columnas: ' . implode(', ', $validation['missing']) . PHP_EOL;
    exit(1);
}
