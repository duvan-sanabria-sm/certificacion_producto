<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';

auth_require_permission('prequote_criteria.manage');

$dbOk = false;
$dbMessage = '';
$criteria = [];

try {
    $pdo = db();
    $dbOk = true;
    $criteria = $pdo->query(
        "SELECT *
         FROM criterios_precotizacion
         ORDER BY orden, id"
    )->fetchAll();
} catch (Throwable) {
    $dbMessage = 'No fue posible leer los criterios. Verifica la conexión y el esquema de base de datos.';
}

$status = $_GET['criteria_status'] ?? null;
$message = $_GET['criteria_message'] ?? null;
$rows = $criteria;
$rows[] = [
    'id' => '',
    'servicio' => '',
    'recurso' => '',
    'cantidad' => '0',
    'unidad' => 'horas',
    'valor_unitario' => '0',
    'tipo' => 'certificacion',
    'activo' => 1,
    'orden' => (count($criteria) + 1) * 10,
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Criterios de pre-cotizacion</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('criterios_precotizacion'); ?>

    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Reglas editables</p>
          <h1>Criterios de pre-cotizacion</h1>
          <p>Define servicios administrativos, recursos, cantidades sugeridas y valores base que se arrastran al autocotizador.</p>
        </div>
        <div class="actions">
          <a class="button ghost" href="laboratorios.php">Laboratorios</a>
          <a class="button" href="pre_cotizacion.php">Volver a pre-cotizacion</a>
        </div>
      </header>

      <?php if (!$dbOk): ?>
        <div class="notice warn"><?= h($dbMessage) ?></div>
      <?php endif; ?>

      <?php if ($status && $message): ?>
        <div class="notice <?= h($status === 'ok' ? 'ok' : 'error') ?>"><?= h($message) ?></div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h2>Servicios y otros valores</h2>
            <div class="muted">La ultima fila vacia permite agregar un criterio nuevo.</div>
          </div>
          <span class="tag"><?= h(count($criteria)) ?> activo(s)</span>
        </div>

        <form action="api/save-prequote-criteria.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
          <div class="table-wrap-lite">
            <table class="data-table editable-table">
              <thead>
                <tr>
                  <th>Orden</th>
                  <th>Servicio</th>
                  <th>Recurso</th>
                  <th>Horas / cantidad</th>
                  <th>Unidad</th>
                  <th>Valor unitario</th>
                  <th>Tipo</th>
                  <th>Activo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $index => $row): ?>
                  <tr>
                    <td>
                      <input type="hidden" name="id[<?= h($index) ?>]" value="<?= h($row['id']) ?>">
                      <input name="orden[<?= h($index) ?>]" type="number" min="0" step="1" value="<?= h($row['orden']) ?>">
                    </td>
                    <td><input name="servicio[<?= h($index) ?>]" type="text" value="<?= h($row['servicio']) ?>"></td>
                    <td><textarea name="recurso[<?= h($index) ?>]"><?= h($row['recurso']) ?></textarea></td>
                    <td><input name="cantidad[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h($row['cantidad']) ?>"></td>
                    <td><select name="unidad[<?= h($index) ?>]"><option value="horas" <?= ($row['unidad'] ?? 'horas') === 'horas' ? 'selected' : '' ?>>Horas</option><option value="cantidad" <?= ($row['unidad'] ?? '') === 'cantidad' ? 'selected' : '' ?>>Cantidad</option></select></td>
                    <td><input name="valor_unitario[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h($row['valor_unitario']) ?>"></td>
                    <td>
                      <select name="tipo[<?= h($index) ?>]">
                        <option value="certificacion" <?= ($row['tipo'] ?? '') === 'certificacion' ? 'selected' : '' ?>>Certificación</option>
                        <option value="otro" <?= ($row['tipo'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                      </select>
                    </td>
                    <td>
                      <label class="check-label compact-check">
                        <input type="checkbox" name="activo[<?= h($index) ?>]" value="1" <?= (int) ($row['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="actions">
            <button type="submit">Guardar criterios</button>
          </div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
