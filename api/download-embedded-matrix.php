<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/MatrixTemplate.php';
require_once __DIR__ . '/../app/Support/EmbeddedMatrix.php';
require_once __DIR__ . '/../app/Support/SimpleXlsxWriter.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('matrices.view');

$scopeCode = strtoupper(trim((string) ($_GET['scope'] ?? 'JUGUETES')));
$scope = matrix_scope_definitions()[$scopeCode] ?? null;

if (!$scope) {
    http_response_code(404);
    exit('Alcance no reconocido.');
}

$columns = array_keys(matrix_columns());
$dataRows = embedded_matrix_rows($scopeCode);
$rows = [$columns];

foreach ($dataRows as $row) {
    $rows[] = array_map(fn ($column) => (string) ($row[$column] ?? ''), $columns);
}

output_xlsx_download(
    'matriz_base_' . strtolower($scope['code']) . '.xlsx',
    [
        'Matriz_normativa' => [
            'rows' => $rows,
            'widths' => array_fill(1, count($columns), 22),
        ],
        'Resumen' => [
            'rows' => [
                ['Campo', 'Valor'],
                ['Alcance', $scope['name']],
                ['Fuente', $scope['source_file']],
                ['Estado', $scope['status']],
                ['Registros', (string) count($dataRows)],
            ],
            'widths' => [1 => 24, 2 => 80],
        ],
    ]
);
