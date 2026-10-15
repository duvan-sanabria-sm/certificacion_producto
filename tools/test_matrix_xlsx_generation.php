<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/MatrixTemplate.php';
require_once __DIR__ . '/../app/Support/EmbeddedMatrix.php';
require_once __DIR__ . '/../app/Support/SimpleXlsxWriter.php';

$columns = array_keys(matrix_columns());
$dataRows = embedded_matrix_rows('JUGUETES');
$rows = [$columns];
foreach (array_slice($dataRows, 0, 5) as $row) {
    $rows[] = array_map(fn ($column) => (string) ($row[$column] ?? ''), $columns);
}

$binary = build_xlsx_binary([
    'Matriz_normativa' => [
        'rows' => $rows,
        'widths' => array_fill(1, count($columns), 22),
    ],
]);

$temp = tempnam(sys_get_temp_dir(), 'matrix_test_') . '.xlsx';
file_put_contents($temp, $binary);
$matrix = read_matrix_file($temp, 'xlsx');
$validation = validate_matrix_headers($matrix['headers']);
unlink($temp);

echo ($validation['ok'] ? 'OK' : 'ERROR') . PHP_EOL;
echo 'Filas leidas: ' . count($matrix['rows']) . PHP_EOL;
