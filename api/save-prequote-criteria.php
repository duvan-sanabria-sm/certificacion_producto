<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('prequote_criteria.manage');

function redirect_criteria(string $status, string $message): void
{
    header('Location: ../criterios_precotizacion.php?criteria_status=' . urlencode($status) . '&criteria_message=' . urlencode($message));
    exit;
}

function decimal_from_post_value(mixed $value): float
{
    $text = trim((string) $value);
    $text = str_replace(['$', 'COP', 'cop', ' '], '', $text);
    if (str_contains($text, ',') && str_contains($text, '.')) {
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
    } elseif (substr_count($text, ',') === 1 && !str_contains($text, '.')) {
        $text = str_replace(',', '.', $text);
    } elseif (substr_count($text, '.') > 1) {
        $text = str_replace('.', '', $text);
    }

    return is_numeric($text) ? (float) $text : 0.0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_criteria('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $ids = $_POST['id'] ?? [];
    $services = $_POST['servicio'] ?? [];
    $resources = $_POST['recurso'] ?? [];
    $quantities = $_POST['cantidad'] ?? [];
    $units = $_POST['unidad'] ?? [];
    $unitValues = $_POST['valor_unitario'] ?? [];
    $types = $_POST['tipo'] ?? [];
    $orders = $_POST['orden'] ?? [];
    $active = $_POST['activo'] ?? [];

    $pdo = db();
    $pdo->beginTransaction();

    $update = $pdo->prepare(
        'UPDATE criterios_precotizacion
         SET servicio = :servicio,
             recurso = :recurso,
             cantidad = :cantidad,
             unidad = :unidad,
             valor_unitario = :valor_unitario,
             tipo = :tipo,
             activo = :activo,
             orden = :orden
         WHERE id = :id'
    );
    $insert = $pdo->prepare(
        'INSERT INTO criterios_precotizacion (servicio, recurso, cantidad, unidad, valor_unitario, tipo, activo, orden)
         VALUES (:servicio, :recurso, :cantidad, :unidad, :valor_unitario, :tipo, :activo, :orden)'
    );

    $saved = 0;
    foreach (array_keys($services) as $index) {
        $servicio = trim((string) ($services[$index] ?? ''));
        if ($servicio === '') {
            continue;
        }

        $id = (int) ($ids[$index] ?? 0);
        $payload = [
            'servicio' => $servicio,
            'recurso' => trim((string) ($resources[$index] ?? '')) ?: null,
            'cantidad' => decimal_from_post_value($quantities[$index] ?? 0),
            'unidad' => in_array(($units[$index] ?? 'horas'), ['horas', 'cantidad'], true) ? $units[$index] : 'horas',
            'valor_unitario' => decimal_from_post_value($unitValues[$index] ?? 0),
            'tipo' => in_array(($types[$index] ?? 'certificacion'), ['certificacion', 'otro'], true) ? $types[$index] : 'certificacion',
            'activo' => isset($active[$index]) ? 1 : 0,
            'orden' => max((int) ($orders[$index] ?? 0), 0),
        ];

        if ($id > 0) {
            $update->execute(array_merge($payload, ['id' => $id]));
        } else {
            $insert->execute($payload);
        }
        $saved++;
    }

    $pdo->commit();
    redirect_criteria('ok', "Criterios actualizados: {$saved} fila(s).");
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_criteria('error', $exception->getMessage());
}
