<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

$authUser = auth_require_permission('technical_review.approve');

function redirect_review_status(string $status, string $message): void
{
    header('Location: ../revision_tecnica.php?' . http_build_query([
        'review_status' => $status,
        'review_message' => $message,
    ]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_review_status('error', 'Método no permitido.');
}

try {
    auth_assert_csrf();
    $quoteId = (int) ($_POST['precotizacion_id'] ?? 0);
    if ($quoteId <= 0) {
        throw new RuntimeException('Selecciona una pre-cotización válida.');
    }

    $pdo = db();
    $statement = $pdo->prepare('SELECT id, codigo, estado FROM precotizaciones WHERE id = :id');
    $statement->execute(['id' => $quoteId]);
    $quote = $statement->fetch();
    if (!$quote) {
        throw new RuntimeException('La pre-cotización no existe.');
    }
    if ((string) $quote['estado'] !== 'en_revision') {
        throw new RuntimeException('Solo se pueden aprobar pre-cotizaciones que estén en revisión.');
    }

    $pdo->prepare("UPDATE precotizaciones SET estado = 'aprobada' WHERE id = :id")
        ->execute(['id' => $quoteId]);
    auth_log('precotizaciones', (string) $quoteId, 'aprobar', [
        'codigo' => $quote['codigo'],
        'aprobado_por' => $authUser['usuario'],
    ]);

    redirect_review_status('ok', 'Pre-cotización aprobada correctamente.');
} catch (Throwable $exception) {
    redirect_review_status('error', $exception->getMessage());
}
