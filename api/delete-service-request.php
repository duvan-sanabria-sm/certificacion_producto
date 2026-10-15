<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('requests.delete');

function redirect_request_delete(string $status, string $message): void
{
    header('Location: ../index.php?request_status=' . urlencode($status) . '&request_message=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_request_delete('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $requestId = (int) ($_POST['solicitud_id'] ?? 0);
    if ($requestId <= 0) {
        throw new RuntimeException('Solicitud no valida.');
    }

    $pdo = db();
    if (!auth_can_access_request($pdo, $requestId)) {
        throw new RuntimeException('No tienes acceso a esta solicitud.');
    }
    $pdo->beginTransaction();

    $quoteStatement = $pdo->prepare('SELECT id FROM precotizaciones WHERE solicitud_servicio_id = :id');
    $quoteStatement->execute(['id' => $requestId]);
    $quoteIds = array_map('intval', array_column($quoteStatement->fetchAll(), 'id'));

    if ($quoteIds) {
        $placeholders = implode(',', array_fill(0, count($quoteIds), '?'));
        $pdo->prepare("DELETE FROM precotizacion_ensayos WHERE precotizacion_id IN ({$placeholders})")->execute($quoteIds);
        $pdo->prepare("DELETE FROM precotizacion_servicios WHERE precotizacion_id IN ({$placeholders})")->execute($quoteIds);
        $pdo->prepare("DELETE FROM precotizaciones WHERE id IN ({$placeholders})")->execute($quoteIds);
    }

    $pdo->prepare('DELETE FROM notificaciones_correo WHERE solicitud_servicio_id = :id')->execute(['id' => $requestId]);
    $pdo->prepare('DELETE FROM solicitud_adjuntos WHERE solicitud_servicio_id = :id')->execute(['id' => $requestId]);
    $pdo->prepare('DELETE FROM solicitud_fabricas WHERE solicitud_servicio_id = :id')->execute(['id' => $requestId]);
    $pdo->prepare('DELETE FROM solicitud_productos WHERE solicitud_servicio_id = :id')->execute(['id' => $requestId]);

    $delete = $pdo->prepare('DELETE FROM solicitudes_servicio WHERE id = :id');
    $delete->execute(['id' => $requestId]);

    $pdo->commit();
    auth_log('solicitudes_servicio', (string) $requestId, 'eliminar');
    redirect_request_delete('ok', 'Solicitud eliminada correctamente.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_request_delete('error', $exception->getMessage());
}
