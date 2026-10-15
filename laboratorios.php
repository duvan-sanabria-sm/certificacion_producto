<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';

$authUser = auth_require_permission('laboratories.view');
$canManageLaboratories = auth_can('laboratories.manage', $authUser);

$dbOk = false;
$dbMessage = '';
$laboratorios = [];
$editLab = null;

try {
    $pdo = db();
    $dbOk = true;
    $laboratorios = $pdo->query(
        "SELECT l.*, COUNT(le.id) AS total_ensayos, MAX(li.creado_en) AS ultima_importacion
         FROM laboratorios l
         LEFT JOIN laboratorio_ensayos le ON le.laboratorio_id = l.id
         LEFT JOIN laboratorio_importaciones li ON li.laboratorio_id = l.id
         GROUP BY l.id
         ORDER BY l.nombre"
    )->fetchAll();

    $editLabId = (int) ($_GET['edit_lab_id'] ?? 0);
    if ($editLabId > 0) {
        if (!$canManageLaboratories) {
            header('Location: acceso_denegado.php?permission=laboratories.manage');
            exit;
        }
        $statement = $pdo->prepare('SELECT * FROM laboratorios WHERE id = :id');
        $statement->execute(['id' => $editLabId]);
        $editLab = $statement->fetch() ?: null;
    }
} catch (Throwable) {
    $dbMessage = 'No fue posible leer laboratorios. Verifica la conexión y el esquema de base de datos.';
}

$status = $_GET['lab_status'] ?? null;
$message = $_GET['lab_message'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laboratorios</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('laboratorios'); ?>

    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Base de ensayos</p>
          <h1>Laboratorios y matrices de precios</h1>
          <p><?= $canManageLaboratories ? 'Administra proveedores, descuentos y matrices tipo TODOS LOS LABORATORIOS para alimentar la pre-cotización.' : 'Consulta proveedores, descuentos y matrices disponibles para los ensayos.' ?></p>
        </div>
        <div class="actions">
          <a class="button ghost" href="api/download-lab-template.php">Plantilla laboratorio</a>
          <?php if (auth_can('prequotes.view', $authUser)): ?><a class="button" href="pre_cotizacion.php">Pre-cotización</a><?php endif; ?>
        </div>
      </header>

      <?php if (!$dbOk): ?>
        <div class="notice warn"><?= h($dbMessage) ?></div>
      <?php endif; ?>

      <?php if ($status && $message): ?>
        <div class="notice <?= h($status === 'ok' ? 'ok' : 'error') ?>"><?= h($message) ?></div>
      <?php endif; ?>

      <?php if ($canManageLaboratories): ?><main class="dashboard-grid">
        <section class="panel">
          <div class="panel-header">
            <div>
              <h2><?= $editLab ? 'Editar laboratorio' : 'Nuevo laboratorio' ?></h2>
              <div class="muted">El descuento se aplica antes de calcular el valor con rentabilidad.</div>
            </div>
            <?php if ($editLab): ?>
              <a class="button secondary" href="laboratorios.php">Nuevo</a>
            <?php endif; ?>
          </div>

          <form action="api/save-laboratory.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
            <input type="hidden" name="laboratorio_id" value="<?= h($editLab['id'] ?? '') ?>">
            <div class="form-grid">
              <div>
                <label for="labCode">Codigo</label>
                <input id="labCode" name="codigo" type="text" value="<?= h($editLab['codigo'] ?? '') ?>" placeholder="Ej. LENOR" required>
              </div>
              <div>
                <label for="labName">Nombre</label>
                <input id="labName" name="nombre" type="text" value="<?= h($editLab['nombre'] ?? '') ?>" required>
              </div>
              <div>
                <label for="provider">Proveedor</label>
                <input id="provider" name="proveedor" type="text" value="<?= h($editLab['proveedor'] ?? '') ?>">
              </div>
              <div>
                <label for="discount">Descuento (%)</label>
                <input id="discount" name="descuento_porcentaje" type="number" min="0" max="100" step="0.01" value="<?= h($editLab['descuento_porcentaje'] ?? '0') ?>">
              </div>
              <div class="full">
                <label for="labNotes">Observaciones</label>
                <textarea id="labNotes" name="observaciones"><?= h($editLab['observaciones'] ?? '') ?></textarea>
              </div>
              <div class="full">
                <label class="check-label">
                  <input type="checkbox" name="activo" value="1" <?= !$editLab || (int) $editLab['activo'] === 1 ? 'checked' : '' ?>>
                  Laboratorio activo para cotizar
                </label>
              </div>
            </div>
            <div class="actions">
              <button type="submit"><?= $editLab ? 'Guardar cambios' : 'Crear laboratorio' ?></button>
            </div>
          </form>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Actualizar matriz</h2>
              <div class="muted">Acepta `.xlsx`, `.xlsm` o `.csv` con la estructura oficial.</div>
            </div>
          </div>

          <form action="api/upload-lab-matrix.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
            <div class="form-grid single">
              <div>
                <label for="uploadLab">Laboratorio</label>
                <select id="uploadLab" name="laboratorio_id" required>
                  <option value="">Selecciona...</option>
                  <?php foreach ($laboratorios as $lab): ?>
                    <option value="<?= h($lab['id']) ?>" <?= $editLab && (int) $editLab['id'] === (int) $lab['id'] ? 'selected' : '' ?>><?= h($lab['nombre']) ?> (<?= h($lab['codigo']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="labMatrix">Archivo matriz</label>
                <input id="labMatrix" name="matriz_laboratorio" type="file" accept=".xlsx,.xlsm,.csv" required>
              </div>
              <div>
                <label class="check-label">
                  <input type="checkbox" name="reemplazar_matriz" value="1" checked>
                  Reemplazar ensayos anteriores del laboratorio
                </label>
              </div>
            </div>
            <div class="actions">
              <button type="submit">Importar matriz</button>
              <a class="button secondary" href="api/download-lab-template.php">Bajar plantilla</a>
            </div>
          </form>
        </section>
      </main><?php endif; ?>

      <section class="panel" style="margin-top:16px;">
        <div class="panel-header">
          <div>
            <h2>Laboratorios registrados</h2>
            <div class="muted"><?= $canManageLaboratories ? 'Cada laboratorio puede editarse, descargarse o eliminarse junto con sus ensayos.' : 'Listado de laboratorios y matrices disponibles para consulta.' ?></div>
          </div>
          <span class="tag"><?= h(count($laboratorios)) ?> laboratorio(s)</span>
        </div>

        <?php if (!$laboratorios): ?>
          <div class="notice">Aun no hay laboratorios registrados.</div>
        <?php else: ?>
          <div class="table-wrap-lite">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Laboratorio</th>
                  <th>Proveedor</th>
                  <th>Descuento</th>
                  <th>Ensayos</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($laboratorios as $lab): ?>
                  <tr>
                    <td><strong><?= h($lab['nombre']) ?></strong><br><span class="muted"><?= h($lab['codigo']) ?></span></td>
                    <td><?= h($lab['proveedor'] ?: 'Sin proveedor') ?></td>
                    <td><?= h(number_format((float) $lab['descuento_porcentaje'], 2, ',', '.')) ?>%</td>
                    <td><?= h($lab['total_ensayos']) ?><br><span class="muted"><?= h($lab['ultima_importacion'] ?: 'Sin importacion') ?></span></td>
                    <td><span class="tag"><?= (int) $lab['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                      <div class="actions compact">
                        <?php if ($canManageLaboratories): ?><a class="button secondary" href="laboratorios.php?edit_lab_id=<?= h($lab['id']) ?>">Editar</a><?php endif; ?>
                        <a class="button ghost" href="api/download-lab-matrix.php?laboratorio_id=<?= h($lab['id']) ?>">Descargar</a>
                        <?php if ($canManageLaboratories): ?>
                        <form class="inline-form" action="api/delete-laboratory.php" method="post" onsubmit="return confirm('Eliminar este laboratorio y sus ensayos?');">
                          <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
                          <input type="hidden" name="laboratorio_id" value="<?= h($lab['id']) ?>">
                          <button class="danger" type="submit">Eliminar</button>
                        </form>
                        <?php endif; ?>
                      </div>
                    </td>
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
