<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('laboratories.manage');

function redirect_laboratory_delete(string $status, string $message): void
{
    header('Location: ../laboratorios.php?lab_status=' . urlencode($status) . '&lab_message=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_laboratory_delete('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $id = (int) ($_POST['laboratorio_id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('Laboratorio no valido.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM laboratorio_ensayos WHERE laboratorio_id = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM laboratorio_importaciones WHERE laboratorio_id = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM laboratorios WHERE id = :id')->execute(['id' => $id]);
    $pdo->commit();

    redirect_laboratory_delete('ok', 'Laboratorio eliminado.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_laboratory_delete('error', $exception->getMessage());
}
