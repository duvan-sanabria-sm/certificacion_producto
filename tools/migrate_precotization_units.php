<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$columns = db_is_postgres($pdo)
    ? [
        'criterios_precotizacion' => "ALTER TABLE criterios_precotizacion ADD COLUMN unidad VARCHAR(20) NOT NULL DEFAULT 'horas' CHECK (unidad IN ('horas', 'cantidad'))",
        'precotizacion_servicios' => "ALTER TABLE precotizacion_servicios ADD COLUMN unidad VARCHAR(20) NOT NULL DEFAULT 'horas' CHECK (unidad IN ('horas', 'cantidad'))",
    ]
    : [
        'criterios_precotizacion' => "ALTER TABLE criterios_precotizacion ADD COLUMN unidad ENUM('horas', 'cantidad') NOT NULL DEFAULT 'horas' AFTER cantidad",
        'precotizacion_servicios' => "ALTER TABLE precotizacion_servicios ADD COLUMN unidad ENUM('horas', 'cantidad') NOT NULL DEFAULT 'horas' AFTER cantidad",
    ];

foreach ($columns as $table => $sql) {
    if (db_is_postgres($pdo)) {
        $statement = $pdo->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = :table AND column_name = :column'
        );
        $statement->execute(['table' => $table, 'column' => 'unidad']);
    } else {
        $statement = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'unidad'");
    }
    if (!$statement->fetch()) {
        $pdo->exec($sql);
        echo "Columna unidad agregada a {$table}." . PHP_EOL;
    } else {
        echo "La columna unidad ya existe en {$table}." . PHP_EOL;
    }
}

$pdo->exec("UPDATE criterios_precotizacion SET unidad = 'cantidad' WHERE tipo = 'otro'");
echo 'Unidades predeterminadas de otros gastos actualizadas.' . PHP_EOL;

$labels = [
    10 => ['Evaluación inicial', 'Profesional de evaluación (PEV) con rol de Experto Técnico'],
    20 => ['Auditoría de Sistema de Gestión (si se requiere)', 'Profesional de evaluación (PEV) con rol de Auditor de Sistema de Gestión (ASG)'],
    30 => ['Evaluación de laboratorio (si se requiere)', 'Profesional de evaluación (PEV) con rol de Evaluador de competencia de laboratorios'],
    40 => ['Atestiguamiento de ensayos (si se requiere)', 'Profesional de evaluación (PEV) con rol de Experto Técnico'],
    50 => ['Análisis de resultados', 'Profesional de evaluación (PEV) con rol de Experto Técnico'],
    60 => ['Comité de certificación', 'Experto Técnico'],
    70 => ['Otros Certificación', ''],
    80 => ['Otros gastos', 'Compra de inversor para pruebas en Colombia'],
];
$update = $pdo->prepare('UPDATE criterios_precotizacion SET servicio = :servicio, recurso = :recurso WHERE orden = :orden');
foreach ($labels as $order => [$service, $resource]) {
    $update->execute(['servicio' => $service, 'recurso' => $resource, 'orden' => $order]);
}
echo 'Ortografía de los criterios actualizada.' . PHP_EOL;
