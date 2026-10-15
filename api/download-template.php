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
$instructions = [
    ['Campo', 'Valor'],
    ['Alcance', $scope['name']],
    ['Codigo', $scope['code']],
    ['Reglamento o norma', $scope['regulation']],
    ['Uso', 'Diligencia registros desde la fila 2 de Matriz_normativa. No cambies los encabezados.'],
    ['Fases', 'Usa SI/NO para OT, vigilancia_1, vigilancia_2 y renovacion.'],
];

output_xlsx_download(
    'plantilla_matriz_' . strtolower($scope['code']) . '.xlsx',
    [
        'Matriz_normativa' => [
            'rows' => [$columns],
            'widths' => array_fill(1, count($columns), 22),
        ],
        'Instrucciones' => [
            'rows' => $instructions,
            'widths' => [1 => 28, 2 => 90],
        ],
    ]
);
