<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';
require_once __DIR__ . '/../app/Support/MatrixTemplate.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('matrices.manage');

function redirect_matrix(string $status, string $message): void
{
    header('Location: ../index.php?matrix_status=' . urlencode($status) . '&matrix_message=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_matrix('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $lineaProductoId = (int) ($_POST['linea_producto_id'] ?? 0);
    $versionMatriz = trim((string) ($_POST['version_matriz'] ?? ''));
    $activar = isset($_POST['activar_matriz']);

    if ($lineaProductoId <= 0) {
        throw new RuntimeException('Selecciona una linea de producto.');
    }

    if ($versionMatriz === '') {
        throw new RuntimeException('Indica la version de la matriz.');
    }

    $file = $_FILES['matriz_excel'] ?? null;
    if (!$file) {
        throw new RuntimeException('Adjunta la matriz en formato .xlsx o .csv.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xlsm', 'csv'], true)) {
        throw new RuntimeException('Formato no soportado. Usa .xlsx, .xlsm o .csv.');
    }

    $stored = store_uploaded_file($file, 'storage/uploads/matrices');
    $matrix = read_matrix_file($stored['absolute_path'], $stored['extension']);
    $validation = validate_matrix_headers($matrix['headers']);

    if (!$validation['ok']) {
        throw new RuntimeException('La plantilla no cumple la estructura oficial. Faltan columnas: ' . implode(', ', $validation['missing']));
    }

    if (count($matrix['rows']) === 0) {
        throw new RuntimeException('La matriz no contiene filas de ensayos para importar.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    $fileStatement = $pdo->prepare(
        'INSERT INTO archivos_cargados
          (tipo_archivo, nombre_original, nombre_guardado, ruta_relativa, extension, mime_type, tamano_bytes, hash_sha256, estado, mensaje_validacion)
         VALUES
          (:tipo_archivo, :nombre_original, :nombre_guardado, :ruta_relativa, :extension, :mime_type, :tamano_bytes, :hash_sha256, :estado, :mensaje_validacion)'
    );
    $fileStatement->execute([
        'tipo_archivo' => 'matriz',
        'nombre_original' => $stored['original_name'],
        'nombre_guardado' => $stored['stored_name'],
        'ruta_relativa' => $stored['relative_path'],
        'extension' => $stored['extension'],
        'mime_type' => $stored['mime_type'],
        'tamano_bytes' => $stored['size'],
        'hash_sha256' => $stored['sha256'],
        'estado' => 'procesado',
        'mensaje_validacion' => 'Plantilla validada e importada.',
    ]);
    $archivoId = db_last_insert_id($pdo);

    if ($activar) {
        $pdo->prepare("UPDATE matrices SET estado = 'historica' WHERE linea_producto_id = :linea_producto_id AND estado = 'activa'")
            ->execute(['linea_producto_id' => $lineaProductoId]);
    }

    $firstRow = $matrix['rows'][0];
    $fechaActualizacion = matrix_date_or_null($firstRow['fecha_actualizacion'] ?? null);
    $nombreMatriz = 'Matriz ' . $versionMatriz;
    $estado = $activar ? 'activa' : 'borrador';

    $matrixSql = db_is_postgres($pdo)
        ? 'INSERT INTO matrices
            (linea_producto_id, archivo_cargado_id, nombre, version_matriz, plantilla_version, fecha_actualizacion, estado, total_registros)
           VALUES
            (:linea_producto_id, :archivo_cargado_id, :nombre, :version_matriz, :plantilla_version, :fecha_actualizacion, :estado, 0)
           ON CONFLICT (linea_producto_id, version_matriz) DO UPDATE SET
            archivo_cargado_id = EXCLUDED.archivo_cargado_id,
            nombre = EXCLUDED.nombre,
            plantilla_version = EXCLUDED.plantilla_version,
            fecha_actualizacion = EXCLUDED.fecha_actualizacion,
            estado = EXCLUDED.estado,
            actualizado_en = CURRENT_TIMESTAMP
           RETURNING id'
        : 'INSERT INTO matrices
            (linea_producto_id, archivo_cargado_id, nombre, version_matriz, plantilla_version, fecha_actualizacion, estado, total_registros)
           VALUES
            (:linea_producto_id, :archivo_cargado_id, :nombre, :version_matriz, :plantilla_version, :fecha_actualizacion, :estado, 0)
           ON DUPLICATE KEY UPDATE
            archivo_cargado_id = VALUES(archivo_cargado_id),
            nombre = VALUES(nombre),
            plantilla_version = VALUES(plantilla_version),
            fecha_actualizacion = VALUES(fecha_actualizacion),
            estado = VALUES(estado),
            actualizado_en = CURRENT_TIMESTAMP,
            id = LAST_INSERT_ID(id)';
    $matrixStatement = $pdo->prepare($matrixSql);
    $matrixStatement->execute([
        'linea_producto_id' => $lineaProductoId,
        'archivo_cargado_id' => $archivoId,
        'nombre' => $nombreMatriz,
        'version_matriz' => $versionMatriz,
        'plantilla_version' => matrix_template_definition()['version'],
        'fecha_actualizacion' => $fechaActualizacion,
        'estado' => $estado,
    ]);
    $matrizId = db_is_postgres($pdo) ? (int) $matrixStatement->fetchColumn() : db_last_insert_id($pdo);

    $pdo->prepare('DELETE FROM matriz_ensayos WHERE matriz_id = :matriz_id')
        ->execute(['matriz_id' => $matrizId]);

    $insertRequirement = $pdo->prepare(
        'INSERT INTO matriz_ensayos
          (matriz_id, linea_producto_id, fila_excel, linea_producto_codigo, linea_producto_nombre,
           tipo_referencia_normativa, reglamento_norma, producto, clase, familia, familia_articulo,
           subfamilia, articulo, articulo_adicional_observacion, ensayo, ensayo_normalizado,
           literal_numeral, norma_tecnica, metodo_ensayo, aplica_ot, aplica_vigilancia_1,
           aplica_vigilancia_2, aplica_renovacion, esquema, codigo_nace, nace, observaciones,
           version_matriz, fecha_actualizacion)
         VALUES
          (:matriz_id, :linea_producto_id, :fila_excel, :linea_producto_codigo, :linea_producto_nombre,
           :tipo_referencia_normativa, :reglamento_norma, :producto, :clase, :familia, :familia_articulo,
           :subfamilia, :articulo, :articulo_adicional_observacion, :ensayo, :ensayo_normalizado,
           :literal_numeral, :norma_tecnica, :metodo_ensayo, :aplica_ot, :aplica_vigilancia_1,
           :aplica_vigilancia_2, :aplica_renovacion, :esquema, :codigo_nace, :nace, :observaciones,
           :version_matriz, :fecha_actualizacion)'
    );

    $importedRows = 0;
    foreach ($matrix['rows'] as $row) {
        $essay = trim((string) ($row['ensayo'] ?? ''));
        if ($essay === '') {
            continue;
        }

        $insertRequirement->execute([
            'matriz_id' => $matrizId,
            'linea_producto_id' => $lineaProductoId,
            'fila_excel' => $row['fila_excel'] ?? null,
            'linea_producto_codigo' => $row['linea_producto_codigo'] ?? null,
            'linea_producto_nombre' => $row['linea_producto_nombre'] ?? null,
            'tipo_referencia_normativa' => $row['tipo_referencia_normativa'] ?? null,
            'reglamento_norma' => $row['reglamento_norma'] ?? null,
            'producto' => $row['producto'] ?? null,
            'clase' => $row['clase'] ?? null,
            'familia' => $row['familia'] ?? null,
            'familia_articulo' => $row['familia_articulo'] ?? null,
            'subfamilia' => $row['subfamilia'] ?? null,
            'articulo' => $row['articulo'] ?? null,
            'articulo_adicional_observacion' => $row['articulo_adicional_observacion'] ?? null,
            'ensayo' => $essay,
            'ensayo_normalizado' => normalize_essay_name($essay),
            'literal_numeral' => $row['literal_numeral'] ?? null,
            'norma_tecnica' => $row['norma_tecnica'] ?? null,
            'metodo_ensayo' => $row['metodo_ensayo'] ?? null,
            'aplica_ot' => matrix_truthy($row['ot'] ?? ''),
            'aplica_vigilancia_1' => matrix_truthy($row['vigilancia_1'] ?? ''),
            'aplica_vigilancia_2' => matrix_truthy($row['vigilancia_2'] ?? ''),
            'aplica_renovacion' => matrix_truthy($row['renovacion'] ?? ''),
            'esquema' => $row['esquema'] ?? null,
            'codigo_nace' => $row['codigo_nace'] ?? null,
            'nace' => $row['nace'] ?? null,
            'observaciones' => $row['observaciones'] ?? null,
            'version_matriz' => $row['version_matriz'] ?: $versionMatriz,
            'fecha_actualizacion' => matrix_date_or_null($row['fecha_actualizacion'] ?? null),
        ]);
        $importedRows++;
    }

    $pdo->prepare('UPDATE matrices SET total_registros = :total WHERE id = :id')
        ->execute(['total' => $importedRows, 'id' => $matrizId]);

    $pdo->commit();

    redirect_matrix('ok', "Matriz importada correctamente: {$importedRows} fila(s).");
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_matrix('error', $exception->getMessage());
}
