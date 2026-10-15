<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';

auth_require_permission('final_quotes.view');

$quotes = [];
$dbMessage = '';

try {
    $pdo = db();
    $quotes = $pdo->query(
        "SELECT p.*, s.codigo_solicitud, c.razon_social
         FROM precotizaciones p
         LEFT JOIN solicitudes_servicio s ON s.id = p.solicitud_servicio_id
         LEFT JOIN clientes c ON c.id = s.cliente_id
         WHERE p.estado = 'aprobada'
         ORDER BY p.creado_en DESC
         LIMIT 20"
    )->fetchAll();
} catch (Throwable) {
    $dbMessage = 'No fue posible leer cotizaciones.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cotizacion final</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('cotizacion'); ?>

    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Fase 4</p>
          <h1>Cotizacion final</h1>
          <p>Consolida los valores aprobados y prepara la salida comercial final del servicio.</p>
        </div>
        <div class="actions">
          <a class="button ghost" href="pre_cotizacion.php">Pre-cotización</a>
          <a class="button" href="pre_cotizacion.php">Nueva pre-cotizacion</a>
        </div>
      </header>

      <?php if ($dbMessage): ?>
        <div class="notice warn"><?= h($dbMessage) ?></div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h2>Cotizaciones disponibles</h2>
            <div class="muted">Esta bandeja recibe las pre-cotizaciones aprobadas comercialmente después de la revisión técnica.</div>
          </div>
          <span class="tag"><?= h(count($quotes)) ?> registro(s)</span>
        </div>

        <?php if (!$quotes): ?>
          <div class="notice">Aún no hay pre-cotizaciones aprobadas para preparar la oferta final.</div>
        <?php else: ?>
          <div class="table-wrap-lite">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Codigo</th>
                  <th>Cliente</th>
                  <th>Subtotal</th>
                  <th>IVA</th>
                  <th>Valor a facturar</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($quotes as $quote): ?>
                  <tr>
                    <td><strong><?= h($quote['codigo']) ?></strong><br><span class="muted"><?= h($quote['codigo_solicitud'] ?: 'Sin solicitud') ?></span></td>
                    <td><?= h($quote['razon_social'] ?: 'Cliente por validar') ?></td>
                    <td><?= h(money((float) $quote['subtotal_valores'] + (float) $quote['subtotal_servicios'] + (float) $quote['subtotal_otros'])) ?></td>
                    <td><?= h(money($quote['iva'])) ?></td>
                    <td><strong><?= h(money($quote['total'])) ?></strong></td>
                    <td><span class="tag"><?= h($quote['estado']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
