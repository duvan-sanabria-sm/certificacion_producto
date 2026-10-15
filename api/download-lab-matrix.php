<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/LabMatrix.php';
require_once __DIR__ . '/../app/Support/SimpleXlsxWriter.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('laboratories.view');

$laboratorioId = (int) ($_GET['laboratorio_id'] ?? 0);
if ($laboratorioId <= 0) {
    http_response_code(400);
    exit('Laboratorio no valido.');
}

$pdo = db();
$statement = $pdo->prepare('SELECT codigo, nombre FROM laboratorios WHERE id = :id');
$statement->execute(['id' => $laboratorioId]);
$laboratorio = $statement->fetch();

if (!$laboratorio) {
    http_response_code(404);
    exit('Laboratorio no encontrado.');
}

$rowsStatement = $pdo->prepare(
    'SELECT codigo_sm, proveedor, ensayo, tecnica, sustancia_material_producto,
            intervalo_medicion, documento_normativo, sede, estado, tipo_pago,
            costo_ensayo, codigo_ensayo_lab, observaciones
     FROM laboratorio_ensayos
     WHERE laboratorio_id = :id
     ORDER BY ensayo'
);
$rowsStatement->execute(['id' => $laboratorioId]);

$rows = [array_values(lab_matrix_columns())];
foreach ($rowsStatement->fetchAll() as $row) {
    $rows[] = [
        $row['codigo_sm'] ?? '',
        $row['proveedor'] ?? '',
        $row['ensayo'] ?? '',
        $row['tecnica'] ?? '',
        $row['sustancia_material_producto'] ?? '',
        $row['intervalo_medicion'] ?? '',
        $row['documento_normativo'] ?? '',
        $row['sede'] ?? '',
        $row['estado'] ?? '',
        $row['tipo_pago'] ?? '',
        (string) ($row['costo_ensayo'] ?? '0'),
        $row['codigo_ensayo_lab'] ?? '',
        $row['observaciones'] ?? '',
    ];
}

$filename = 'matriz_laboratorio_' . strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $laboratorio['codigo'])) . '.xlsx';

output_xlsx_download($filename, [
    'TODOS LOS LABORATORIOS' => [
        'rows' => $rows,
        'widths' => array_fill(1, count($rows[0]), 24),
    ],
]);
