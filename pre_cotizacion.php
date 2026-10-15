<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';
require_once __DIR__ . '/app/Support/PrequoteCalculator.php';

$authUser = auth_require_permission('prequotes.view');
$canCreatePrequotes = auth_can('prequotes.create', $authUser);
$canEditPrequotes = $canCreatePrequotes || auth_can('prequotes.edit', $authUser);
$canApprovePrequotes = auth_can('prequotes.approve', $authUser);

function numeric_default_quantity(?string $value): float
{
    if (!$value) {
        return 1.0;
    }

    preg_match('/\d+(?:[.,]\d+)?/', $value, $matches);
    if (!$matches) {
        return 1.0;
    }

    return max((float) str_replace(',', '.', $matches[0]), 1.0);
}

function stored_service_mode_label(?string $json, array $labels): string
{
    $data = json_decode((string) $json, true);
    $mode = is_array($data) ? (string) ($data['tipo_servicio'] ?? '') : '';
    return $labels[$mode] ?? 'Modalidad anterior';
}

$dbOk = false;
$dbMessage = '';
$requests = [];
$selectedRequest = null;
$quoteRows = [];
$criteria = [];
$recentQuotes = [];
$selectedQuote = null;
$selectedQuoteId = (int) ($_GET['precotizacion_id'] ?? 0);
$printMode = ($_GET['print'] ?? '') === '1';
$rentabilidad = isset($_GET['rentabilidad']) ? (float) str_replace(',', '.', (string) $_GET['rentabilidad']) : 10.0;
$defaultQuantity = isset($_GET['cantidad']) ? max((float) str_replace(',', '.', (string) $_GET['cantidad']), 1.0) : 1.0;
$selectedRequestId = (int) ($_GET['solicitud_id'] ?? 0);
$serviceMode = in_array(($_GET['tipo_servicio'] ?? ''), ['certificacion', 'certificacion_ensayos'], true)
    ? (string) $_GET['tipo_servicio']
    : 'certificacion_ensayos';
$includesTests = $serviceMode === 'certificacion_ensayos';
$includesCertification = true;
$serviceModeLabels = [
    'certificacion' => 'Solo certificación',
    'certificacion_ensayos' => 'Certificación + ensayos',
    'ensayos' => 'Solo ensayos',
];

try {
    $pdo = db();
    $dbOk = true;
    $requestScope = auth_request_scope_sql('s');

    if ($selectedQuoteId > 0) {
        $selectedQuoteStatement = $pdo->prepare(
            "SELECT p.*, s.codigo_solicitud, s.producto AS solicitud_producto,
                    s.referencia_producto, s.cantidad_muestras, s.reglamento_norma,
                    s.linea_producto_id, c.razon_social, l.nombre AS linea_nombre, l.codigo AS linea_codigo
             FROM precotizaciones p
             INNER JOIN solicitudes_servicio s ON s.id = p.solicitud_servicio_id
             LEFT JOIN clientes c ON c.id = s.cliente_id
             LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
             WHERE p.id = :precotizacion_id AND {$requestScope['sql']}"
        );
        $selectedQuoteStatement->execute(array_merge(
            ['precotizacion_id' => $selectedQuoteId],
            $requestScope['params']
        ));
        $selectedQuote = $selectedQuoteStatement->fetch() ?: null;
        if (!$selectedQuote) {
            throw new RuntimeException('La pre-cotización solicitada no existe o no está disponible para tu perfil.');
        }

        $selectedRequestId = (int) $selectedQuote['solicitud_servicio_id'];
        $rentabilidad = (float) $selectedQuote['rentabilidad_porcentaje'];
        $storedQuoteData = json_decode((string) ($selectedQuote['datos_json'] ?? ''), true);
        $storedMode = is_array($storedQuoteData) ? (string) ($storedQuoteData['tipo_servicio'] ?? '') : '';
        if (in_array($storedMode, ['certificacion', 'certificacion_ensayos'], true)) {
            $serviceMode = $storedMode;
        }
        $includesTests = $serviceMode === 'certificacion_ensayos';
    }

    $requestsStatement = $pdo->prepare(
        "SELECT s.id, s.codigo_solicitud, s.producto, s.referencia_producto, s.cantidad_muestras,
                s.reglamento_norma, s.linea_producto_id, c.razon_social, l.nombre AS linea_nombre,
                rt.codigo_revision, rt.dias_ajustados AS dias_evaluacion
         FROM solicitudes_servicio s
         INNER JOIN revisiones_tecnicas rt ON rt.solicitud_servicio_id = s.id AND rt.estado = 'aprobada'
         LEFT JOIN clientes c ON c.id = s.cliente_id
         LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
         WHERE {$requestScope['sql']}
         ORDER BY s.actualizado_en DESC, s.creado_en DESC"
    );
    $requestsStatement->execute($requestScope['params']);
    $requests = $requestsStatement->fetchAll();

    if ($selectedQuoteId <= 0 && $selectedRequestId <= 0 && $requests) {
        $selectedRequestId = (int) $requests[0]['id'];
    }

    if ($selectedRequestId > 0) {
        $approvedReviewCondition = $selectedQuote
            ? ''
            : " AND EXISTS (SELECT 1 FROM revisiones_tecnicas rt_check
                             WHERE rt_check.solicitud_servicio_id = s.id AND rt_check.estado = 'aprobada')";
        $statement = $pdo->prepare(
            "SELECT s.*, c.razon_social, c.numero_documento, c.contacto_nombre, c.contacto_correo,
                    l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                    rt.codigo_revision, rt.tiempos_json AS revision_tiempos_json,
                    rt.dias_ajustados AS dias_evaluacion
             FROM solicitudes_servicio s
             LEFT JOIN clientes c ON c.id = s.cliente_id
             LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
             LEFT JOIN revisiones_tecnicas rt ON rt.solicitud_servicio_id = s.id
             WHERE s.id = :id AND {$requestScope['sql']}{$approvedReviewCondition}"
        );
        $statement->execute(array_merge(['id' => $selectedRequestId], $requestScope['params']));
        $selectedRequest = $statement->fetch() ?: null;
    }

    if ($selectedRequest && !$selectedQuote && !isset($_GET['cantidad'])) {
        $defaultQuantity = numeric_default_quantity($selectedRequest['cantidad_muestras'] ?? null);
    }

    if ($selectedQuote) {
        if ($includesTests) {
            $quoteRowsStatement = $pdo->prepare(
                'SELECT pe.*, l.nombre AS laboratorio_nombre, le.codigo_ensayo_lab AS codigo_lab
                 FROM precotizacion_ensayos pe
                 LEFT JOIN laboratorios l ON l.id = pe.laboratorio_id
                 LEFT JOIN laboratorio_ensayos le ON le.id = pe.laboratorio_ensayo_id
                 WHERE pe.precotizacion_id = :precotizacion_id
                 ORDER BY pe.id'
            );
            $quoteRowsStatement->execute(['precotizacion_id' => $selectedQuoteId]);
            $quoteRows = $quoteRowsStatement->fetchAll();
            foreach ($quoteRows as &$quoteRow) {
                $quoteRow['laboratorio'] = $quoteRow['laboratorio_nombre'] ?: 'Sin laboratorio';
            }
            unset($quoteRow);
        }

        $criteriaStatement = $pdo->prepare(
            'SELECT * FROM precotizacion_servicios
             WHERE precotizacion_id = :precotizacion_id
             ORDER BY orden, id'
        );
        $criteriaStatement->execute(['precotizacion_id' => $selectedQuoteId]);
        $criteria = $criteriaStatement->fetchAll();
    } else {
        if ($selectedRequest && $includesTests) {
            $quoteRows = prequote_rows($pdo, $selectedRequest, $rentabilidad, $defaultQuantity);
        }
        $criteria = $pdo->query(
            "SELECT *
             FROM criterios_precotizacion
             WHERE activo = 1
             ORDER BY orden, id"
        )->fetchAll();
        if ($selectedRequest && !empty($selectedRequest['revision_tiempos_json'])) {
            $reviewTimes = json_decode((string) $selectedRequest['revision_tiempos_json'], true) ?: [];
            $timeByService = [
                'evaluacion inicial' => (float) ($reviewTimes['evaluacion_inicial']['adjusted'] ?? 0),
                'auditoria de sistema de gestion' => (float) ($reviewTimes['sistema_gestion']['adjusted'] ?? 0),
                'evaluacion de laboratorio' => (float) ($reviewTimes['evaluacion_laboratorio']['adjusted'] ?? 0),
                'atestiguamiento de ensayos' => (float) ($reviewTimes['atestiguamiento']['adjusted'] ?? 0),
                'analisis de resultados' => (float) ($reviewTimes['analisis_resultados']['adjusted'] ?? 0),
                'comite de certificacion' => (float) ($reviewTimes['comite_certificacion']['adjusted'] ?? 1),
            ];
            foreach ($criteria as &$criterion) {
                $normalizedService = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $criterion['servicio']) ?: (string) $criterion['servicio']);
                foreach ($timeByService as $needle => $quantity) {
                    if (str_contains($normalizedService, $needle)) {
                        $criterion['cantidad'] = $quantity;
                        break;
                    }
                }
            }
            unset($criterion);
        }
    }
    if (!$includesCertification) {
        $criteria = [];
    }

    $recentStatement = $pdo->prepare(
        "SELECT p.*, s.codigo_solicitud, c.razon_social
         FROM precotizaciones p
         LEFT JOIN solicitudes_servicio s ON s.id = p.solicitud_servicio_id
         LEFT JOIN clientes c ON c.id = s.cliente_id
         WHERE {$requestScope['sql']}
         ORDER BY p.creado_en DESC
         LIMIT 8"
    );
    $recentStatement->execute($requestScope['params']);
    $recentQuotes = $recentStatement->fetchAll();
} catch (Throwable $exception) {
    $dbMessage = 'No fue posible generar la pre-cotizacion: ' . $exception->getMessage();
}

$status = $_GET['quote_status'] ?? null;
$message = $_GET['quote_message'] ?? null;
$requestLabel = $selectedRequest
    ? (($selectedRequest['codigo_solicitud'] ?: 'Solicitud ' . $selectedRequest['id']) . ' - ' . ($selectedRequest['razon_social'] ?: 'Cliente por validar'))
    : '';
$quoteLocked = $selectedQuote && in_array((string) $selectedQuote['estado'], ['aprobada', 'archivada'], true);
$canSaveQuote = $canEditPrequotes && !$quoteLocked;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pre-cotización</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body data-auto-print="<?= $printMode ? '1' : '0' ?>">
  <div class="app-frame">
    <?php render_sidebar('precotizacion'); ?>

    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Fase 3</p>
          <h1>Pre-cotización</h1>
          <p>Calcula servicios de certificación con o sin los ensayos de laboratorio requeridos.</p>
        </div>
        <div class="actions">
          <?php if ($selectedQuote): ?><button class="secondary" type="button" data-print-page>Imprimir</button><?php endif; ?>
          <?php if (auth_can('laboratories.view', $authUser)): ?><a class="button ghost" href="laboratorios.php">Laboratorios</a><?php endif; ?>
          <?php if (auth_can('prequote_criteria.manage', $authUser)): ?><a class="button secondary" href="criterios_precotizacion.php">Criterios</a><?php endif; ?>
        </div>
      </header>

      <?php if (!$dbOk): ?>
        <div class="notice warn"><?= h($dbMessage) ?></div>
      <?php endif; ?>

      <?php if ($status && $message): ?>
        <div class="notice <?= h($status === 'ok' ? 'ok' : 'error') ?>"><?= h($message) ?></div>
      <?php endif; ?>

      <?php if ($canCreatePrequotes): ?><section class="panel no-print">
        <form method="get" action="pre_cotizacion.php">
          <fieldset class="mode-selector two-options"><legend>Tipo de servicio</legend>
            <label><input type="radio" name="tipo_servicio" value="certificacion" <?= $serviceMode === 'certificacion' ? 'checked' : '' ?>><span>Solo certificación<small>Horas o cantidades de evaluación</small></span></label>
            <label><input type="radio" name="tipo_servicio" value="certificacion_ensayos" <?= $serviceMode === 'certificacion_ensayos' ? 'checked' : '' ?>><span>Certificación + ensayos<small>Servicio completo</small></span></label>
          </fieldset>
          <div class="form-grid">
            <div>
              <label for="quoteRequest">Solicitud</label>
              <select id="quoteRequest" name="solicitud_id" required>
                <?php if (!$requests): ?>
                  <option value="">No hay solicitudes con revisión aprobada</option>
                <?php endif; ?>
                <?php foreach ($requests as $request): ?>
                  <option value="<?= h($request['id']) ?>" <?= (int) $request['id'] === $selectedRequestId ? 'selected' : '' ?>>
                    <?= h(($request['codigo_solicitud'] ?: 'Sin codigo') . ' - ' . ($request['razon_social'] ?: 'Cliente por validar')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div <?= !$includesTests ? 'hidden' : '' ?>>
              <label for="margin">Rentabilidad (%)</label>
              <input id="margin" name="rentabilidad" type="number" min="0" max="95" step="0.01" value="<?= h($rentabilidad) ?>">
            </div>
            <div <?= !$includesTests ? 'hidden' : '' ?>>
              <label for="quantity">Cantidad base</label>
              <input id="quantity" name="cantidad" type="number" min="1" step="1" value="<?= h($defaultQuantity) ?>">
            </div>
            <div class="form-actions-end">
              <button type="submit">Generar pre-cotización</button>
            </div>
          </div>
        </form>
      </section><?php endif; ?>

      <?php if ($selectedRequest && ($canCreatePrequotes || ($selectedQuote && $canEditPrequotes))): ?>
        <section class="panel print-quote" style="margin-top:16px;">
          <div class="panel-header">
            <div>
              <h2><?= h($selectedQuote ? $selectedQuote['codigo'] . ' · ' . $requestLabel : $requestLabel) ?></h2>
              <div class="muted"><?= h($selectedRequest['producto'] ?: 'Producto por validar') ?> · <?= h($selectedRequest['linea_nombre'] ?: 'Línea por definir') ?> · <?= h($selectedRequest['reglamento_norma'] ?: 'Norma por validar') ?></div>
            </div>
            <div class="actions">
              <?php if (auth_can('requests.edit', $authUser)): ?><a class="button secondary no-print" href="index.php?edit_request_id=<?= h($selectedRequest['id']) ?>#solicitud-form">Editar solicitud</a><?php endif; ?>
              <span class="tag"><?= h($serviceModeLabels[$serviceMode]) ?></span>
              <?php if ($selectedQuote): ?><span class="tag"><?= h($selectedQuote['estado']) ?></span><?php endif; ?>
            </div>
          </div>

          <?php if (!empty($selectedRequest['codigo_revision'])): ?>
            <div class="notice ok no-print">Revisión técnica <?= h($selectedRequest['codigo_revision']) ?> aprobada. Los servicios de evaluación se precargaron con <?= h(number_format((float) $selectedRequest['dias_evaluacion'], 1, ',', '.')) ?> día(s) técnicos estimados.</div>
          <?php endif; ?>

          <?php if ($quoteLocked): ?>
            <div class="notice ok no-print">Esta pre-cotización está <?= h($selectedQuote['estado']) ?> y permanece bloqueada para conservar la aprobación.</div>
          <?php endif; ?>

          <?php if ($includesTests && !$quoteRows): ?>
            <div class="notice warn">No se encontraron ensayos para esta solicitud. Revisa que exista una matriz normativa activa y una matriz de laboratorio importada.</div>
          <?php elseif (!$includesTests): ?>
            <div class="notice">Esta modalidad calcula únicamente los servicios de certificación según sus horas o cantidades.</div>
          <?php endif; ?>

          <form action="api/save-prequote.php" method="post" data-prequote>
            <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
            <input type="hidden" name="precotizacion_id" value="<?= h($selectedQuoteId ?: '') ?>">
            <input type="hidden" name="solicitud_id" value="<?= h($selectedRequest['id']) ?>">
            <input type="hidden" name="rentabilidad_porcentaje" value="<?= h($rentabilidad) ?>">
            <input type="hidden" name="request_label" value="<?= h($requestLabel) ?>">
            <input type="hidden" name="tipo_servicio" value="<?= h($serviceMode) ?>">

            <?php if ($includesTests): ?>
            <section class="subsection quote-component">
              <div class="panel-header slim"><div><h2>Ensayos de laboratorio</h2><div class="muted"><?= h(count($quoteRows)) ?> ensayo(s) encontrados; ajusta cantidades, costos y valores.</div></div></div>
            <div class="table-wrap-lite">
              <table class="data-table quote-table">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Ensayo</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Metodo / norma</th>
                    <th>Costo unit.</th>
                    <th>Valor unit.</th>
                    <th>Subtotal costo</th>
                    <th>Subtotal valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($quoteRows as $index => $row): ?>
                    <tr data-quote-row>
                      <td>
                        <input type="hidden" name="laboratorio_ensayo_id[<?= h($index) ?>]" value="<?= h($row['laboratorio_ensayo_id']) ?>">
                        <input type="hidden" name="laboratorio_id[<?= h($index) ?>]" value="<?= h($row['laboratorio_id']) ?>">
                        <input name="codigo[<?= h($index) ?>]" type="text" value="<?= h($row['codigo'] ?: $row['codigo_lab']) ?>">
                        <div class="muted small-text"><?= h($row['laboratorio']) ?></div>
                      </td>
                      <td><textarea name="ensayo[<?= h($index) ?>]"><?= h($row['ensayo']) ?></textarea></td>
                      <td><input name="producto[<?= h($index) ?>]" type="text" value="<?= h($row['producto']) ?>"></td>
                      <td><input class="quote-qty" name="cantidad[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h($row['cantidad']) ?>"></td>
                      <td><textarea name="metodo_norma[<?= h($index) ?>]"><?= h($row['metodo_norma']) ?></textarea></td>
                      <td><input class="unit-cost" name="costo_unitario[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h((string) $row['costo_unitario']) ?>"></td>
                      <td><input class="unit-value" name="valor_unitario[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h((string) $row['valor_unitario']) ?>"></td>
                      <td class="money-cell subtotal-cost"><?= h(money($row['subtotal_costo'])) ?></td>
                      <td class="money-cell subtotal-value"><?= h(money($row['subtotal_valor'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            </section>
            <?php endif; ?>

            <?php if ($includesCertification): ?>
            <section class="subsection">
              <div class="panel-header slim">
                <div>
                  <h2>Servicios de certificación</h2>
                  <div class="muted">Calcula cada actividad por horas o por cantidad. Los valores siguen siendo editables.</div>
                </div>
                <?php if (auth_can('prequote_criteria.manage', $authUser)): ?><a class="button secondary no-print" href="criterios_precotizacion.php">Modificar criterios</a><?php endif; ?>
              </div>
              <div class="table-wrap-lite">
                <table class="data-table quote-table service-table">
                  <thead>
                    <tr>
                      <th>Servicio</th>
                      <th>Recurso</th>
                      <th>Horas / cantidad</th>
                      <th>Unidad</th>
                      <th>Valor unitario</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($criteria as $index => $row): ?>
                      <tr data-service-row data-service-type="<?= h($row['tipo']) ?>">
                        <td>
                          <input type="hidden" name="servicio_tipo[<?= h($index) ?>]" value="<?= h($row['tipo']) ?>">
                          <input type="hidden" name="servicio_orden[<?= h($index) ?>]" value="<?= h($row['orden']) ?>">
                          <input name="servicio[<?= h($index) ?>]" type="text" value="<?= h($row['servicio']) ?>">
                        </td>
                        <td><textarea name="recurso[<?= h($index) ?>]"><?= h($row['recurso']) ?></textarea></td>
                        <td><input class="service-qty" name="servicio_cantidad[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h($row['cantidad']) ?>"></td>
                        <td><select name="servicio_unidad[<?= h($index) ?>]"><option value="horas" <?= ($row['unidad'] ?? 'horas') === 'horas' ? 'selected' : '' ?>>Horas</option><option value="cantidad" <?= ($row['unidad'] ?? '') === 'cantidad' ? 'selected' : '' ?>>Cantidad</option></select></td>
                        <td><input class="service-unit" name="servicio_valor_unitario[<?= h($index) ?>]" type="number" min="0" step="0.01" value="<?= h($row['valor_unitario']) ?>"></td>
                        <td class="money-cell service-subtotal"><?= h(money((float) $row['cantidad'] * (float) $row['valor_unitario'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </section>
            <?php endif; ?>

            <div class="total-grid" aria-live="polite">
              <?php if ($includesTests): ?><div class="total-card"><span>Subtotal ensayos</span><strong id="subtotalEnsayos">$ 0</strong></div><?php endif; ?>
              <?php if ($includesCertification): ?><div class="total-card"><span>Servicios</span><strong id="subtotalServicios">$ 0</strong></div><?php endif; ?>
              <div class="total-card"><span>Otros</span><strong id="subtotalOtros">$ 0</strong></div>
              <div class="total-card"><span>IVA 19%</span><strong id="ivaTotal">$ 0</strong></div>
              <div class="total-card highlight"><span>Valor a facturar</span><strong id="valorFacturar">$ 0</strong></div>
            </div>

            <div class="actions no-print">
              <?php if ($canSaveQuote): ?>
                <button type="submit" name="submit_action" value="guardar"><?= $selectedQuote ? 'Guardar cambios' : 'Guardar borrador' ?></button>
                <?php if ($canApprovePrequotes): ?>
                  <button class="secondary" type="submit" name="submit_action" value="aprobar">Aprobar y pasar a cotización final</button>
                <?php endif; ?>
              <?php endif; ?>
              <button class="ghost" type="button" data-print-page>Imprimir</button>
              <?php if ($selectedRequest): ?><a class="button ghost" href="revision_tecnica.php?solicitud_id=<?= h($selectedRequest['id']) ?>">Ver revisión aprobada</a><?php endif; ?>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <section class="panel no-print" style="margin-top:16px;">
        <div class="panel-header">
          <div>
            <h2>Pre-cotizaciones recientes</h2>
            <div class="muted">Borradores guardados desde este módulo.</div>
          </div>
        </div>
        <?php if (!$recentQuotes): ?>
          <div class="notice">Aún no hay pre-cotizaciones guardadas.</div>
        <?php else: ?>
          <div class="table-wrap-lite">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Solicitud</th>
                  <th>Cliente</th>
                  <th>Modalidad</th>
                  <th>Total</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentQuotes as $quote): ?>
                  <tr>
                    <td><strong><?= h($quote['codigo']) ?></strong><br><span class="muted"><?= h($quote['nombre']) ?></span></td>
                    <td><?= h($quote['codigo_solicitud'] ?: 'Sin solicitud') ?></td>
                    <td><?= h($quote['razon_social'] ?: 'Cliente por validar') ?></td>
                    <td><?= h(stored_service_mode_label($quote['datos_json'] ?? null, $serviceModeLabels)) ?></td>
                    <td><?= h(money($quote['total'])) ?></td>
                    <td><span class="tag"><?= h($quote['estado']) ?></span></td>
                    <?php
                      $recentQuoteLocked = in_array((string) $quote['estado'], ['aprobada', 'archivada'], true);
                      $recentQuoteCanEdit = !$recentQuoteLocked && $canEditPrequotes;
                    ?>
                    <td><div class="actions compact"><a class="button secondary" href="pre_cotizacion.php?precotizacion_id=<?= h($quote['id']) ?>"><?= $recentQuoteCanEdit ? 'Ver / editar' : 'Ver' ?></a><a class="button ghost" href="pre_cotizacion.php?precotizacion_id=<?= h($quote['id']) ?>&amp;print=1">Imprimir</a></div></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="assets/js/system.js"></script>
</body>
</html>
