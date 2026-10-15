<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('laboratories.manage');

function redirect_laboratory(string $status, string $message, array $extra = []): void
{
    header('Location: ../laboratorios.php?' . http_build_query(array_merge([
        'lab_status' => $status,
        'lab_message' => $message,
    ], $extra)));
    exit;
}

function post_text(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function post_decimal_value(string $key): float
{
    $value = post_text($key);
    if (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } else {
        $value = str_replace(',', '.', $value);
    }

    return is_numeric($value) ? (float) $value : 0.0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_laboratory('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $id = (int) ($_POST['laboratorio_id'] ?? 0);
    $codigo = strtoupper(post_text('codigo'));
    $nombre = post_text('nombre');
    $proveedor = post_text('proveedor');
    $descuento = max(min(post_decimal_value('descuento_porcentaje'), 100), 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $observaciones = post_text('observaciones');

    if ($codigo === '' || $nombre === '') {
        throw new RuntimeException('Indica codigo y nombre del laboratorio.');
    }

    $pdo = db();

    if ($id > 0) {
        $statement = $pdo->prepare(
            'UPDATE laboratorios
             SET codigo = :codigo,
                 nombre = :nombre,
                 proveedor = :proveedor,
                 descuento_porcentaje = :descuento_porcentaje,
                 activo = :activo,
                 observaciones = :observaciones
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'proveedor' => $proveedor ?: null,
            'descuento_porcentaje' => $descuento,
            'activo' => $activo,
            'observaciones' => $observaciones ?: null,
        ]);
        redirect_laboratory('ok', 'Laboratorio actualizado.', ['edit_lab_id' => $id]);
    }

    $sql = db_is_postgres($pdo)
        ? 'INSERT INTO laboratorios (codigo, nombre, proveedor, descuento_porcentaje, activo, observaciones)
           VALUES (:codigo, :nombre, :proveedor, :descuento_porcentaje, :activo, :observaciones)
           ON CONFLICT (codigo) DO UPDATE SET
             nombre = EXCLUDED.nombre,
             proveedor = EXCLUDED.proveedor,
             descuento_porcentaje = EXCLUDED.descuento_porcentaje,
             activo = EXCLUDED.activo,
             observaciones = EXCLUDED.observaciones
           RETURNING id'
        : 'INSERT INTO laboratorios (codigo, nombre, proveedor, descuento_porcentaje, activo, observaciones)
           VALUES (:codigo, :nombre, :proveedor, :descuento_porcentaje, :activo, :observaciones)
           ON DUPLICATE KEY UPDATE
             nombre = VALUES(nombre),
             proveedor = VALUES(proveedor),
             descuento_porcentaje = VALUES(descuento_porcentaje),
             activo = VALUES(activo),
             observaciones = VALUES(observaciones),
             id = LAST_INSERT_ID(id)';
    $statement = $pdo->prepare($sql);
    $statement->execute([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'proveedor' => $proveedor ?: null,
        'descuento_porcentaje' => $descuento,
        'activo' => $activo,
        'observaciones' => $observaciones ?: null,
    ]);
    $id = db_is_postgres($pdo) ? (int) $statement->fetchColumn() : db_last_insert_id($pdo);

    redirect_laboratory('ok', 'Laboratorio guardado.', ['edit_lab_id' => $id]);
} catch (Throwable $exception) {
    redirect_laboratory('error', $exception->getMessage());
}
