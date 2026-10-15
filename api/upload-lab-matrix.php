<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';
require_once __DIR__ . '/../app/Support/LabMatrix.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('laboratories.manage');

function redirect_lab_import(string $status, string $message, array $extra = []): void
{
    header('Location: ../laboratorios.php?' . http_build_query(array_merge([
        'lab_status' => $status,
        'lab_message' => $message,
    ], $extra)));
    exit;
}

function short_db_text(?string $value, int $length = 320): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    return mb_substr($value, 0, $length, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_lab_import('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $laboratorioId = (int) ($_POST['laboratorio_id'] ?? 0);
    if ($laboratorioId <= 0) {
        throw new RuntimeException('Selecciona un laboratorio.');
    }

    $file = $_FILES['matriz_laboratorio'] ?? null;
    if (!$file) {
        throw new RuntimeException('Adjunta la matriz del laboratorio.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xlsm', 'csv'], true)) {
        throw new RuntimeException('Formato no soportado. Usa .xlsx, .xlsm o .csv.');
    }

    $stored = store_uploaded_file($file, 'storage/uploads/laboratorios');
    $rows = read_lab_matrix_file($stored['absolute_path'], $stored['extension']);

    if (!$rows) {
        throw new RuntimeException('La matriz de laboratorio no contiene filas para importar.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    $importStatement = $pdo->prepare(
        'INSERT INTO laboratorio_importaciones
          (laboratorio_id, nombre_original, nombre_guardado, ruta_relativa, extension, total_registros, estado, mensaje_validacion)
         VALUES
          (:laboratorio_id, :nombre_original, :nombre_guardado, :ruta_relativa, :extension, 0, :estado, :mensaje_validacion)'
    );
    $importStatement->execute([
        'laboratorio_id' => $laboratorioId,
        'nombre_original' => $stored['original_name'],
        'nombre_guardado' => $stored['stored_name'],
        'ruta_relativa' => $stored['relative_path'],
        'extension' => $stored['extension'],
        'estado' => 'procesado',
        'mensaje_validacion' => 'Matriz de laboratorio validada e importada.',
    ]);
    $importacionId = db_last_insert_id($pdo);

    if (isset($_POST['reemplazar_matriz'])) {
        $pdo->prepare('DELETE FROM laboratorio_ensayos WHERE laboratorio_id = :laboratorio_id')
            ->execute(['laboratorio_id' => $laboratorioId]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO laboratorio_ensayos
          (laboratorio_id, importacion_id, codigo_sm, proveedor, ensayo, ensayo_normalizado,
           tecnica, sustancia_material_producto, intervalo_medicion, documento_normativo,
           documento_normalizado, sede, estado, tipo_pago, costo_ensayo, codigo_ensayo_lab, observaciones)
         VALUES
          (:laboratorio_id, :importacion_id, :codigo_sm, :proveedor, :ensayo, :ensayo_normalizado,
           :tecnica, :sustancia_material_producto, :intervalo_medicion, :documento_normativo,
           :documento_normalizado, :sede, :estado, :tipo_pago, :costo_ensayo, :codigo_ensayo_lab, :observaciones)'
    );

    $imported = 0;
    $firstProveedor = '';
    foreach ($rows as $row) {
        $essay = lab_row_value($row, 'ensayo');
        if ($essay === '') {
            continue;
        }

        if (normalize_essay_name($essay) === 'ensayo') {
            continue;
        }

        $proveedor = lab_row_value($row, 'proveedor');
        if ($firstProveedor === '' && $proveedor !== '') {
            $firstProveedor = $proveedor;
        }

        $documento = lab_row_value($row, 'documento_normativo');
        $insert->execute([
            'laboratorio_id' => $laboratorioId,
            'importacion_id' => $importacionId,
            'codigo_sm' => lab_row_value($row, 'codigo_sm') ?: null,
            'proveedor' => $proveedor ?: null,
            'ensayo' => short_db_text($essay, 320),
            'ensayo_normalizado' => short_db_text(normalize_essay_name($essay), 320),
            'tecnica' => lab_row_value($row, 'tecnica') ?: null,
            'sustancia_material_producto' => lab_row_value($row, 'sustancia_material_elemento_o_producto_a_ensayar') ?: null,
            'intervalo_medicion' => lab_row_value($row, 'intervalo_de_medicion') ?: null,
            'documento_normativo' => $documento ?: null,
            'documento_normalizado' => short_db_text(normalize_document_text($documento), 320),
            'sede' => lab_row_value($row, 'sede') ?: null,
            'estado' => lab_row_value($row, 'estado') ?: null,
            'tipo_pago' => lab_row_value($row, 'tipo_de_pago') ?: null,
            'costo_ensayo' => parse_money_value($row['costo_de_ensayo'] ?? 0),
            'codigo_ensayo_lab' => lab_row_value($row, 'codigo_de_ensayo_lab') ?: null,
            'observaciones' => lab_row_value($row, 'observaciones') ?: null,
        ]);
        $imported++;
    }

    $pdo->prepare('UPDATE laboratorio_importaciones SET total_registros = :total WHERE id = :id')
        ->execute(['total' => $imported, 'id' => $importacionId]);

    if ($firstProveedor !== '') {
        $pdo->prepare('UPDATE laboratorios SET proveedor = COALESCE(NULLIF(proveedor, \'\'), :proveedor) WHERE id = :id')
            ->execute(['proveedor' => $firstProveedor, 'id' => $laboratorioId]);
    }

    $pdo->commit();

    redirect_lab_import('ok', "Matriz importada: {$imported} ensayo(s).", ['edit_lab_id' => $laboratorioId]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_lab_import('error', $exception->getMessage());
}
