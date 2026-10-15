<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/MatrixTemplate.php';
require_once __DIR__ . '/../app/Support/SimpleXlsxWriter.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('matrices.view');

$matrixId = (int) ($_GET['id'] ?? 0);
if ($matrixId <= 0) {
    http_response_code(400);
    exit('Matriz no valida.');
}

$pdo = db();
$matrixStatement = $pdo->prepare(
    'SELECT m.id, m.version_matriz, l.codigo, l.nombre
     FROM matrices m
     INNER JOIN lineas_producto l ON l.id = m.linea_producto_id
     WHERE m.id = :id'
);
$matrixStatement->execute(['id' => $matrixId]);
$matrix = $matrixStatement->fetch();

if (!$matrix) {
    http_response_code(404);
    exit('Matriz no encontrada.');
}

$rowsStatement = $pdo->prepare(
    'SELECT linea_producto_codigo, linea_producto_nombre, tipo_referencia_normativa, reglamento_norma,
            producto, clase, familia, familia_articulo, subfamilia, articulo,
            articulo_adicional_observacion, ensayo, literal_numeral, norma_tecnica,
            metodo_ensayo, aplica_ot, aplica_vigilancia_1, aplica_vigilancia_2,
            aplica_renovacion, esquema, codigo_nace, nace, observaciones,
            version_matriz, fecha_actualizacion
     FROM matriz_ensayos
     WHERE matriz_id = :id
     ORDER BY id'
);
$rowsStatement->execute(['id' => $matrixId]);
$records = $rowsStatement->fetchAll();

$columns = array_keys(matrix_columns());
$rows = [$columns];

foreach ($records as $record) {
    $rows[] = [
        $record['linea_producto_codigo'] ?? '',
        $record['linea_producto_nombre'] ?? '',
        $record['tipo_referencia_normativa'] ?? '',
        $record['reglamento_norma'] ?? '',
        $record['producto'] ?? '',
        $record['clase'] ?? '',
        $record['familia'] ?? '',
        $record['familia_articulo'] ?? '',
        $record['subfamilia'] ?? '',
        $record['articulo'] ?? '',
        $record['articulo_adicional_observacion'] ?? '',
        $record['ensayo'] ?? '',
        $record['literal_numeral'] ?? '',
        $record['norma_tecnica'] ?? '',
        $record['metodo_ensayo'] ?? '',
        !empty($record['aplica_ot']) ? 'SI' : 'NO',
        !empty($record['aplica_vigilancia_1']) ? 'SI' : 'NO',
        !empty($record['aplica_vigilancia_2']) ? 'SI' : 'NO',
        !empty($record['aplica_renovacion']) ? 'SI' : 'NO',
        $record['esquema'] ?? '',
        $record['codigo_nace'] ?? '',
        $record['nace'] ?? '',
        $record['observaciones'] ?? '',
        $record['version_matriz'] ?? '',
        $record['fecha_actualizacion'] ?? '',
    ];
}

$filename = 'matriz_' . strtolower((string) $matrix['codigo']) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $matrix['version_matriz']) . '.xlsx';

output_xlsx_download($filename, [
    'Matriz_normativa' => [
        'rows' => $rows,
        'widths' => array_fill(1, count($columns), 22),
    ],
]);
