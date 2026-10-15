<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/EmbeddedMatrix.php';
require_once __DIR__ . '/app/Support/UI.php';

auth_require_permission('matrices.view');

$dbOk = false;
$dbMatrices = [];
$dbMessage = '';
$embeddedMatrices = embedded_matrix_summary();

try {
    $pdo = db();
    $dbOk = true;
    $dbMatrices = $pdo->query(
        "SELECT m.id, m.version_matriz, m.estado, m.total_registros, m.creado_en,
                l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                a.nombre_original
         FROM matrices m
         INNER JOIN lineas_producto l ON l.id = m.linea_producto_id
         LEFT JOIN archivos_cargados a ON a.id = m.archivo_cargado_id
         ORDER BY m.creado_en DESC"
    )->fetchAll();
} catch (Throwable $exception) {
    $dbMessage = 'No fue posible leer matrices. Verifica la conexión y aplica el esquema correspondiente.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Matrices normativas</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('matrices'); ?>

    <main class="app-content">
    <header class="topbar">
      <div>
        <p class="eyebrow">Matrices normativas</p>
        <h1>Plantillas y matrices por alcance</h1>
        <p>Consulta las matrices importadas en base de datos, descarga matrices base extraídas del código inicial y baja una plantilla oficial limpia por cada alcance.</p>
      </div>
      <div class="actions">
        <a class="button ghost" href="index.php">Volver al inicio</a>
        <a class="button" href="templates/plantilla_matriz_normativa.xlsx">Plantilla general</a>
      </div>
    </header>

    <?php if (!$dbOk): ?>
      <div class="notice warn"><?= h($dbMessage) ?></div>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-header">
        <div>
          <h2>Plantillas oficiales por alcance</h2>
          <div class="muted">Cada descarga trae la estructura oficial de columnas para cargar nuevas matrices.</div>
        </div>
      </div>
      <div class="table-wrap-lite">
        <table class="data-table">
          <thead>
            <tr>
              <th>Alcance</th>
              <th>Reglamento / norma</th>
              <th>Estado código inicial</th>
              <th>Registros base</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($embeddedMatrices as $matrix): ?>
              <tr>
                <td><strong><?= h($matrix['name']) ?></strong><br><span class="muted"><?= h($matrix['code']) ?></span></td>
                <td><?= h($matrix['regulation']) ?></td>
                <td><?= h($matrix['status']) ?></td>
                <td><?= h($matrix['rows']) ?></td>
                <td>
                  <div class="actions compact">
                    <a class="button secondary" href="api/download-template.php?scope=<?= h($matrix['code']) ?>">Plantilla</a>
                    <a class="button ghost" href="api/download-embedded-matrix.php?scope=<?= h($matrix['code']) ?>">Matriz base</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel" style="margin-top:16px;">
      <div class="panel-header">
        <div>
          <h2>Matrices cargadas en base de datos</h2>
          <div class="muted">Versiones que ya fueron importadas desde la pantalla inicial.</div>
        </div>
        <span class="tag"><?= $dbOk ? 'Base conectada' : 'Sin conexión' ?></span>
      </div>

      <?php if (!$dbMatrices): ?>
        <div class="notice">Aún no hay matrices importadas en la base de datos.</div>
      <?php else: ?>
        <div class="table-wrap-lite">
          <table class="data-table">
            <thead>
              <tr>
                <th>Línea</th>
                <th>Versión</th>
                <th>Estado</th>
                <th>Registros</th>
                <th>Archivo origen</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dbMatrices as $matrix): ?>
                <tr>
                  <td><strong><?= h($matrix['linea_nombre']) ?></strong><br><span class="muted"><?= h($matrix['linea_codigo']) ?></span></td>
                  <td><?= h($matrix['version_matriz']) ?></td>
                  <td><span class="tag"><?= h($matrix['estado']) ?></span></td>
                  <td><?= h($matrix['total_registros']) ?></td>
                  <td><?= h($matrix['nombre_original'] ?: 'Sin archivo') ?></td>
                  <td><a class="button secondary" href="api/download-db-matrix.php?id=<?= h($matrix['id']) ?>">Descargar</a></td>
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
