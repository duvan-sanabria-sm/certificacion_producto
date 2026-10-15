<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';

$authUser = auth_require_permission('requests.view');
$canCreateRequests = auth_can('requests.create', $authUser);
$canEditRequests = auth_can('requests.edit', $authUser);
$canDeleteRequests = auth_can('requests.delete', $authUser);
$canViewMatrices = auth_can('matrices.view', $authUser);
$canManageRequestSequence = auth_can('requests.sequence.manage', $authUser);

function request_json_data(?array $request): array
{
    if (!$request || empty($request['datos_preliminares_json'])) {
        return [];
    }

    $decoded = json_decode((string) $request['datos_preliminares_json'], true);
    return is_array($decoded) ? $decoded : [];
}

function request_field(?array $request, array $json, string $field): string
{
    if (!$request) {
        return '';
    }

    $map = [
        'cliente_nombre' => 'razon_social',
        'tipo_documento' => 'tipo_documento',
        'numero_documento' => 'numero_documento',
        'direccion' => 'direccion',
        'ciudad' => 'ciudad',
        'contacto_nombre' => 'contacto_nombre',
        'contacto_correo' => 'contacto_correo',
        'contacto_telefono' => 'contacto_telefono',
    ];

    $source = $map[$field] ?? $field;
    $value = trim((string) ($request[$source] ?? ''));
    if ($value !== '') {
        return $value;
    }

    return trim((string) ($json[$field] ?? ''));
}

$dbOk = false;
$dbMessage = '';
$lineasProducto = [];
$stats = [
    'lineas' => 0,
    'matrices' => 0,
    'ensayos' => 0,
    'solicitudes' => 0,
    'laboratorios' => 0,
];
$recentMatrices = [];
$requests = [];
$editRequest = null;
$editJson = [];

try {
    $pdo = db();
    $dbOk = true;
    $lineasProducto = $pdo->query('SELECT id, codigo, nombre FROM lineas_producto WHERE activa = 1 ORDER BY nombre')->fetchAll();
    $stats['lineas'] = (int) $pdo->query('SELECT COUNT(*) FROM lineas_producto WHERE activa = 1')->fetchColumn();
    $stats['matrices'] = (int) $pdo->query('SELECT COUNT(*) FROM matrices')->fetchColumn();
    $stats['ensayos'] = (int) $pdo->query('SELECT COUNT(*) FROM matriz_ensayos')->fetchColumn();
    $stats['solicitudes'] = (int) $pdo->query('SELECT COUNT(*) FROM solicitudes_servicio')->fetchColumn();
    $stats['laboratorios'] = (int) $pdo->query('SELECT COUNT(*) FROM laboratorios')->fetchColumn();
    $requestScope = auth_request_scope_sql('s');
    $requestCountStatement = $pdo->prepare('SELECT COUNT(*) FROM solicitudes_servicio s WHERE ' . $requestScope['sql']);
    $requestCountStatement->execute($requestScope['params']);
    $stats['solicitudes'] = (int) $requestCountStatement->fetchColumn();
    if ($canViewMatrices) {
        $recentMatrices = $pdo->query(
            "SELECT m.version_matriz, m.estado, m.total_registros, l.nombre AS linea
             FROM matrices m
             INNER JOIN lineas_producto l ON l.id = m.linea_producto_id
             ORDER BY m.creado_en DESC
             LIMIT 5"
        )->fetchAll();
    }
    $requestStatement = $pdo->prepare(
        "SELECT s.*, c.razon_social, c.tipo_documento, c.numero_documento, c.contacto_nombre,
                c.contacto_correo, c.contacto_telefono, c.direccion, c.ciudad,
                l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                co.nombre_completo AS comercial_nombre, co.correo AS comercial_correo,
                rt.estado AS revision_estado, rt.codigo_revision
         FROM solicitudes_servicio s
         LEFT JOIN clientes c ON c.id = s.cliente_id
         LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
         LEFT JOIN comerciales co ON co.id = s.comercial_id
         LEFT JOIN revisiones_tecnicas rt ON rt.solicitud_servicio_id = s.id
         WHERE {$requestScope['sql']}
         ORDER BY s.actualizado_en DESC, s.creado_en DESC
         LIMIT 30"
    );
    $requestStatement->execute($requestScope['params']);
    $requests = $requestStatement->fetchAll();

    $editRequestId = (int) ($_GET['edit_request_id'] ?? 0);
    if ($editRequestId > 0) {
        if (!$canEditRequests) {
            header('Location: acceso_denegado.php?permission=requests.edit');
            exit;
        }
        $editStatement = $pdo->prepare(
            "SELECT s.*, c.razon_social, c.tipo_documento, c.numero_documento, c.contacto_nombre,
                    c.contacto_correo, c.contacto_telefono, c.direccion, c.ciudad
             FROM solicitudes_servicio s
             LEFT JOIN clientes c ON c.id = s.cliente_id
             WHERE s.id = :id AND {$requestScope['sql']}"
        );
        $editStatement->execute(array_merge(['id' => $editRequestId], $requestScope['params']));
        $editRequest = $editStatement->fetch() ?: null;
        if (!$editRequest) {
            header('Location: acceso_denegado.php?permission=requests.edit');
            exit;
        }
        $editJson = request_json_data($editRequest);
    }
} catch (Throwable) {
    $dbMessage = 'La base de datos no está lista o la conexión no está disponible.';
}

$requestStatus = $_GET['request_status'] ?? null;
$requestMessage = $_GET['request_message'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitud de servicio</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('solicitud'); ?>

    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Fase 1</p>
          <h1>Solicitud de servicio</h1>
          <p>Registra o corrige la información del cliente, producto y norma antes de iniciar la revisión técnica.</p>
        </div>
        <div class="actions">
          <?php if ($canCreateRequests): ?><a class="button secondary" href="solicitud_cliente.php">Formulario para clientes</a><?php endif; ?>
          <?php if ($canViewMatrices): ?><a class="button ghost" href="matrices.php">Matrices normativas</a><?php endif; ?>
          <?php if (auth_can('technical_review.view', $authUser)): ?><a class="button" href="revision_tecnica.php">Ir a revisión técnica</a><?php endif; ?>
        </div>
      </header>

      <?php if (!$dbOk): ?>
        <div class="notice warn"><?= h($dbMessage) ?></div>
      <?php endif; ?>

      <?php if ($requestStatus && $requestMessage): ?>
        <div class="notice <?= h($requestStatus === 'ok' ? 'ok' : 'error') ?>"><?= h($requestMessage) ?></div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h2>Estado general</h2>
            <div class="muted">Resumen de datos cargados para el flujo de certificacion de producto.</div>
          </div>
          <span class="tag"><?= $dbOk ? 'Base conectada' : 'Sin conexion' ?></span>
        </div>
        <div class="stats">
          <div class="stat"><strong><?= h($stats['lineas']) ?></strong><span>Lineas activas</span></div>
          <?php if ($canViewMatrices): ?><div class="stat"><strong><?= h($stats['matrices']) ?></strong><span>Matrices normativas</span></div><div class="stat"><strong><?= h($stats['ensayos']) ?></strong><span>Ensayos normativos</span></div><?php endif; ?>
          <?php if (auth_can('laboratories.view', $authUser)): ?><div class="stat"><strong><?= h($stats['laboratorios']) ?></strong><span>Laboratorios</span></div><?php endif; ?>
          <div class="stat"><strong><?= h($stats['solicitudes']) ?></strong><span>Solicitudes</span></div>
        </div>
      </section>

      <main class="dashboard-grid" style="margin-top:16px;">
        <div class="stack">
          <?php if ($canCreateRequests || ($canEditRequests && $editRequest)): ?>
          <section class="panel" id="solicitud-form">
            <div class="panel-header">
              <div>
                <h2><?= $editRequest ? 'Editar solicitud' : 'Nueva solicitud' ?></h2>
                <div class="muted">Completa o corrige directamente la información de la solicitud.</div>
              </div>
              <?php if ($editRequest): ?>
                <a class="button secondary" href="index.php">Nueva solicitud</a>
              <?php endif; ?>
            </div>

            <form action="api/save-service-request.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
              <input type="hidden" name="solicitud_id" value="<?= h($editRequest['id'] ?? '') ?>">
              <div class="form-grid">
                <div>
                  <label for="requestCode">Consecutivo de solicitud</label>
                  <input id="requestCode" name="codigo_solicitud" type="text" value="<?= h(request_field($editRequest, $editJson, 'codigo_solicitud')) ?>" placeholder="<?= $canManageRequestSequence ? 'Ej. SS-2026-000027' : 'Se asigna automáticamente' ?>" <?= $canManageRequestSequence ? '' : 'readonly' ?>>
                  <div class="muted small-text"><?= $canManageRequestSequence ? 'Como administrador puedes corregir el código visible; el identificador interno no cambia.' : 'Solo el administrador puede modificar este consecutivo.' ?></div>
                </div>
                <div>
                  <label for="clientName">Cliente</label>
                  <input id="clientName" name="cliente_nombre" type="text" value="<?= h(request_field($editRequest, $editJson, 'cliente_nombre')) ?>" placeholder="Razon social">
                </div>
                <div>
                  <label for="clientDoc">NIT / documento</label>
                  <input id="clientDoc" name="numero_documento" type="text" value="<?= h(request_field($editRequest, $editJson, 'numero_documento')) ?>" placeholder="900000000-0">
                  <input type="hidden" name="tipo_documento" value="<?= h(request_field($editRequest, $editJson, 'tipo_documento') ?: 'NIT') ?>">
                </div>
                <div>
                  <label for="clientAddress">Direccion</label>
                  <input id="clientAddress" name="direccion" type="text" value="<?= h(request_field($editRequest, $editJson, 'direccion')) ?>">
                </div>
                <div>
                  <label for="clientCity">Ciudad</label>
                  <input id="clientCity" name="ciudad" type="text" value="<?= h(request_field($editRequest, $editJson, 'ciudad')) ?>">
                </div>
                <div>
                  <label for="contactName">Contacto</label>
                  <input id="contactName" name="contacto_nombre" type="text" value="<?= h(request_field($editRequest, $editJson, 'contacto_nombre')) ?>">
                </div>
                <div>
                  <label for="contactRole">Cargo</label>
                  <input id="contactRole" name="contacto_cargo" type="text" value="<?= h(request_field($editRequest, $editJson, 'contacto_cargo')) ?>">
                </div>
                <div>
                  <label for="contactEmail">Correo</label>
                  <input id="contactEmail" name="contacto_correo" type="email" value="<?= h(request_field($editRequest, $editJson, 'contacto_correo')) ?>">
                </div>
                <div>
                  <label for="contactPhone">Telefono</label>
                  <input id="contactPhone" name="contacto_telefono" type="text" value="<?= h(request_field($editRequest, $editJson, 'contacto_telefono')) ?>">
                </div>
                <div>
                  <label for="requestLine">Linea de producto</label>
                  <select id="requestLine" name="linea_producto_id">
                    <option value="">Por definir</option>
                    <?php foreach ($lineasProducto as $linea): ?>
                      <option value="<?= h($linea['id']) ?>" data-code="<?= h($linea['codigo']) ?>" <?= (int) ($editRequest['linea_producto_id'] ?? 0) === (int) $linea['id'] ? 'selected' : '' ?>><?= h($linea['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="serviceName">Servicio solicitado</label>
                  <input id="serviceName" name="servicio_solicitado" type="text" value="<?= h(request_field($editRequest, $editJson, 'servicio_solicitado')) ?>" placeholder="Certificacion, ensayo, inspeccion...">
                </div>
                <div>
                  <label for="productName">Producto</label>
                  <input id="productName" name="producto" type="text" value="<?= h(request_field($editRequest, $editJson, 'producto')) ?>">
                </div>
                <div>
                  <label for="productRef">Referencia</label>
                  <input id="productRef" name="referencia_producto" type="text" value="<?= h(request_field($editRequest, $editJson, 'referencia_producto')) ?>">
                </div>
                <div>
                  <label for="sampleQty">Cantidad de muestras</label>
                  <input id="sampleQty" name="cantidad_muestras" type="text" value="<?= h(request_field($editRequest, $editJson, 'cantidad_muestras')) ?>">
                </div>
                <div>
                  <label for="regulation">Reglamento / norma</label>
                  <input id="regulation" name="reglamento_norma" type="text" value="<?= h(request_field($editRequest, $editJson, 'reglamento_norma')) ?>">
                </div>
                <div class="full">
                  <label for="requestNotes">Observaciones</label>
                  <textarea id="requestNotes" name="observaciones"><?= h(request_field($editRequest, $editJson, 'observaciones')) ?></textarea>
                </div>
              </div>
              <div class="actions">
                <button type="submit"><?= $editRequest ? 'Guardar cambios' : 'Registrar solicitud' ?></button>
                <?php if ($editRequest): ?>
                  <?php if (auth_can('technical_review.view', $authUser)): ?><a class="button ghost" href="revision_tecnica.php?solicitud_id=<?= h($editRequest['id']) ?>">Revisar técnicamente</a><?php endif; ?>
                <?php endif; ?>
              </div>
            </form>
          </section>
          <?php endif; ?>

          <section class="panel">
            <div class="panel-header">
              <div>
                <h2>Solicitudes registradas</h2>
                <div class="muted">Puedes corregir datos antes de la revisión técnica o eliminar registros creados por error.</div>
              </div>
            </div>

            <?php if (!$requests): ?>
              <div class="notice">Aun no hay solicitudes registradas.</div>
            <?php else: ?>
              <div class="table-wrap-lite">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Solicitud</th>
                      <th>Cliente</th>
                      <th>Producto / linea</th>
                      <th>Comercial</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($requests as $request): ?>
                      <tr>
                        <td>
                          <strong><?= h($request['codigo_solicitud'] ?: 'Sin código') ?></strong>
                        </td>
                        <td>
                          <?= h($request['razon_social'] ?: 'Cliente por validar') ?><br>
                          <span class="muted"><?= h($request['numero_documento'] ?: '') ?></span>
                        </td>
                        <td>
                          <?= h($request['producto'] ?: $request['referencia_producto'] ?: 'Producto por validar') ?><br>
                          <span class="muted"><?= h($request['linea_nombre'] ?: 'Linea por definir') ?></span>
                        </td>
                        <td>
                          <?= h($request['comercial_nombre'] ?: 'Sin asignar') ?><br>
                          <span class="muted"><?= h($request['correo_notificacion_estado'] ?: '') ?></span>
                        </td>
                        <td><span class="tag"><?= h($request['estado']) ?></span><br><span class="muted"><?= h(workflow_review_state_label($request['revision_estado'] ?? null)) ?></span></td>
                        <td>
                          <div class="actions compact">
                            <a class="button ghost" href="solicitud_detalle.php?id=<?= h($request['id']) ?>">Ver</a>
                            <?php if ($canEditRequests): ?><a class="button secondary" href="index.php?edit_request_id=<?= h($request['id']) ?>#solicitud-form">Editar</a><?php endif; ?>
                            <?php if (auth_can('technical_review.view', $authUser)): ?><a class="button ghost" href="revision_tecnica.php?solicitud_id=<?= h($request['id']) ?>">Revisar</a><?php endif; ?>
                            <?php if (auth_can('prequotes.create', $authUser) && ($request['revision_estado'] ?? '') === 'aprobada'): ?><a class="button ghost" href="pre_cotizacion.php?solicitud_id=<?= h($request['id']) ?>">Pre-cotizar</a><?php endif; ?>
                            <?php if ($canDeleteRequests): ?>
                            <form class="inline-form" action="api/delete-service-request.php" method="post" onsubmit="return confirm('Eliminar esta solicitud?');">
                              <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
                              <input type="hidden" name="solicitud_id" value="<?= h($request['id']) ?>">
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
        </div>

        <aside class="stack">
          <?php if ($canViewMatrices): ?><section class="panel">
            <div class="panel-header">
              <div>
                <h2>Matrices recientes</h2>
                <div class="muted">Ultimas versiones cargadas.</div>
              </div>
            </div>
            <?php if (!$recentMatrices): ?>
              <div class="notice">Sin matrices registradas.</div>
            <?php else: ?>
              <ul class="list">
                <?php foreach ($recentMatrices as $matrix): ?>
                  <li>
                    <span>
                      <strong><?= h($matrix['linea']) ?></strong><br>
                      <span class="muted"><?= h($matrix['version_matriz']) ?> · <?= h($matrix['total_registros']) ?> filas</span>
                    </span>
                    <span class="tag"><?= h($matrix['estado']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section><?php endif; ?>
        </aside>
      </main>
    </main>
  </div>
  <script src="assets/js/system.js"></script>
</body>
</html>
