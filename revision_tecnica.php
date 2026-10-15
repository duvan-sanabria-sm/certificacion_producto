<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';

$authUser = auth_require_permission('technical_review.view');
$canEditReview = auth_can('technical_review.edit', $authUser);
$canApproveReview = auth_can('technical_review.approve', $authUser);
$selectedRequestId = (int) ($_GET['solicitud_id'] ?? 0);
$requests = [];
$selectedRequest = null;
$products = [];
$attachments = [];
$review = null;
$dbMessage = '';
$reviewStatus = (string) ($_GET['review_status'] ?? '');
$reviewMessage = (string) ($_GET['review_message'] ?? '');

function review_radio_group(string $name, string $value, bool $allowNA = true): void
{
    foreach (['SI' => 'Sí', 'NO' => 'No'] + ($allowNA ? ['NA' => 'N/A'] : []) as $option => $label) {
        ?>
        <label class="review-radio-option"><input type="radio" name="<?= h($name) ?>" value="<?= h($option) ?>" <?= $value === $option ? 'checked' : '' ?> required><span><?= h($label) ?></span></label>
        <?php
    }
}

try {
    $pdo = db();
    $requestScope = auth_request_scope_sql('s');
    $listStatement = $pdo->prepare(
        "SELECT s.id, s.codigo_solicitud, s.producto, s.reglamento_norma, s.fecha_solicitud,
                c.razon_social, rt.codigo_revision, rt.estado AS revision_estado,
                rt.dias_ajustados, rt.actualizado_en AS revision_actualizada,
                (SELECT COUNT(*) FROM precotizaciones p WHERE p.solicitud_servicio_id = s.id) AS total_precotizaciones
         FROM solicitudes_servicio s
         LEFT JOIN clientes c ON c.id = s.cliente_id
         LEFT JOIN revisiones_tecnicas rt ON rt.solicitud_servicio_id = s.id
         WHERE {$requestScope['sql']} AND s.estado <> 'archivada'
         ORDER BY CASE WHEN rt.estado IS NULL THEN 0 WHEN rt.estado = 'requiere_informacion' THEN 1 ELSE 2 END,
                  s.actualizado_en DESC
         LIMIT 60"
    );
    $listStatement->execute($requestScope['params']);
    $requests = $listStatement->fetchAll();

    if ($selectedRequestId > 0) {
        $detailStatement = $pdo->prepare(
            "SELECT s.*, c.razon_social, c.numero_documento, c.contacto_nombre, c.contacto_correo,
                    c.contacto_telefono, l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                    rt.id AS revision_id, rt.codigo_revision, rt.tipo_evaluacion, rt.estado AS revision_estado,
                    rt.producto_en_alcance, rt.iso_conforme, rt.test_reports_suficientes,
                    rt.tipo_auditoria, rt.decision_viabilidad, rt.documentos_json, rt.validaciones_json,
                    rt.analisis_iso_json, rt.tiempos_json, rt.factor_seguridad, rt.dias_base,
                    rt.dias_ajustados, rt.observaciones AS revision_observaciones
             FROM solicitudes_servicio s
             LEFT JOIN clientes c ON c.id = s.cliente_id
             LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
             LEFT JOIN revisiones_tecnicas rt ON rt.solicitud_servicio_id = s.id
             WHERE s.id = :id AND {$requestScope['sql']}"
        );
        $detailStatement->execute(array_merge(['id' => $selectedRequestId], $requestScope['params']));
        $selectedRequest = $detailStatement->fetch() ?: null;
        if (!$selectedRequest) {
            throw new RuntimeException('La solicitud no existe o no está disponible para tu perfil.');
        }
        $review = !empty($selectedRequest['revision_id']) ? $selectedRequest : null;

        $productStatement = $pdo->prepare('SELECT * FROM solicitud_productos WHERE solicitud_servicio_id = :id ORDER BY id');
        $productStatement->execute(['id' => $selectedRequestId]);
        $products = $productStatement->fetchAll();

        $attachmentStatement = $pdo->prepare(
            'SELECT sa.id AS adjunto_id, sa.categoria, ac.nombre_original, ac.extension, ac.tamano_bytes
             FROM solicitud_adjuntos sa
             INNER JOIN archivos_cargados ac ON ac.id = sa.archivo_cargado_id
             WHERE sa.solicitud_servicio_id = :id ORDER BY sa.id'
        );
        $attachmentStatement->execute(['id' => $selectedRequestId]);
        $attachments = $attachmentStatement->fetchAll();
    }
} catch (Throwable $exception) {
    $dbMessage = 'No fue posible cargar la revisión técnica: ' . $exception->getMessage();
}

$preliminary = $selectedRequest ? (json_decode((string) ($selectedRequest['datos_preliminares_json'] ?? '{}'), true) ?: []) : [];
$service = (array) ($preliminary['servicio'] ?? []);
$storedDocuments = $review ? (json_decode((string) ($review['documentos_json'] ?? '{}'), true) ?: []) : [];
$storedTimes = $review ? (json_decode((string) ($review['tiempos_json'] ?? '{}'), true) ?: []) : [];
$storedIsoAnalysis = $review ? (json_decode((string) ($review['analisis_iso_json'] ?? '{}'), true) ?: []) : [];
$attachmentCategories = array_column($attachments, 'categoria');
$hasDatasheet = (bool) array_filter($attachmentCategories, static fn(string $value): bool => str_starts_with($value, 'ficha_tecnica'));
$hasPhoto = (bool) array_filter($attachmentCategories, static fn(string $value): bool => str_starts_with($value, 'imagen'));
$hasIso = (bool) array_filter($attachmentCategories, static fn(string $value): bool => str_starts_with($value, 'certificado_iso_9001'));
$hasTestReport = in_array('test_report', $attachmentCategories, true);
$isoAttachments = array_values(array_filter($attachments, static fn(array $item): bool => str_starts_with((string) $item['categoria'], 'certificado_iso_9001')));
$scheme = (string) ($service['esquema'] ?? '');
$isoStatus = (string) ($service['iso_9001_vigente'] ?? 'No informado');
$remoteTechnology = !empty($service['medios_tecnologicos_remotos']);
$reviewDataJson = json_encode([
    'scheme' => $scheme,
    'remoteTechnology' => $remoteTechnology,
    'times' => $storedTimes,
    'isoAnalysis' => $storedIsoAnalysis,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Revisión y tiempos de evaluación</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('revision'); ?>
    <main class="app-content">
      <header class="topbar review-page-heading">
        <div>
          <p class="eyebrow">Fase 2</p>
          <h1>Aplicación de revisión y tiempos de evaluación</h1>
          <p>Valida la información del cliente, define la viabilidad y estima siempre el esfuerzo de evaluación, con o sin ensayos.</p>
        </div>
        <div class="actions">
          <?php if ($selectedRequest): ?><a class="button ghost" href="revision_tecnica.php">Volver a la bandeja</a><?php endif; ?>
          <?php if ($review && (string) $review['revision_estado'] === 'aprobada'): ?>
            <a class="button" href="pre_cotizacion.php?solicitud_id=<?= h($selectedRequestId) ?>">Ir a precotización</a>
          <?php endif; ?>
        </div>
      </header>

      <?php if ($dbMessage !== ''): ?><div class="notice warn"><?= h($dbMessage) ?></div><?php endif; ?>
      <?php if ($reviewStatus !== '' && $reviewMessage !== ''): ?>
        <div class="notice <?= $reviewStatus === 'ok' ? 'ok' : 'error' ?>"><?= h($reviewMessage) ?></div>
      <?php endif; ?>

      <?php if (!$selectedRequest): ?>
        <section class="panel">
          <div class="panel-header">
            <div><h2>Bandeja de solicitudes</h2><div class="muted">La revisión aprobada habilita la solicitud para precotización.</div></div>
            <span class="tag"><?= h(count($requests)) ?> solicitud(es)</span>
          </div>
          <?php if (!$requests): ?>
            <div class="notice">No hay solicitudes disponibles para revisión.</div>
          <?php else: ?>
            <div class="table-wrap-lite"><table class="data-table">
              <thead><tr><th>Solicitud</th><th>Cliente</th><th>Producto</th><th>Reglamento</th><th>Revisión</th><th>Días</th><th>Acción</th></tr></thead>
              <tbody>
                <?php foreach ($requests as $request): ?>
                  <tr>
                    <td><strong><?= h($request['codigo_solicitud']) ?></strong><br><span class="muted"><?= h($request['fecha_solicitud']) ?></span></td>
                    <td><?= h($request['razon_social'] ?: 'Cliente por validar') ?></td>
                    <td><?= h($request['producto'] ?: 'Producto por validar') ?></td>
                    <td><?= h($request['reglamento_norma'] ?: 'Por validar') ?></td>
                    <td><span class="tag review-state-<?= h($request['revision_estado'] ?: 'nueva') ?>"><?= h(workflow_review_state_label($request['revision_estado'])) ?></span></td>
                    <td><?= $request['dias_ajustados'] !== null ? h(number_format((float) $request['dias_ajustados'], 1, ',', '.')) : '-' ?></td>
                    <td><a class="button secondary" href="revision_tecnica.php?solicitud_id=<?= h($request['id']) ?>"><?= $request['revision_estado'] ? 'Abrir' : 'Revisar' ?></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        </section>
      <?php else: ?>
        <section class="review-request-summary">
          <div><span>Solicitud</span><strong><?= h($selectedRequest['codigo_solicitud']) ?></strong></div>
          <div><span>Cliente</span><strong><?= h($selectedRequest['razon_social']) ?></strong></div>
          <div><span>Esquema</span><strong><?= h($scheme ?: 'Por validar') ?></strong></div>
          <div><span>Estado</span><strong><?= h(workflow_review_state_label($review['revision_estado'] ?? null)) ?></strong></div>
        </section>

        <form id="technicalReviewForm" class="review-workspace" action="api/save-technical-review.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
          <input type="hidden" name="solicitud_id" value="<?= h($selectedRequestId) ?>">
          <input id="isoAnalysisJson" type="hidden" name="analisis_iso_json" value="<?= h($review['analisis_iso_json'] ?? '') ?>">
          <input id="evaluationTimesJson" type="hidden" name="tiempos_json" value="<?= h($review['tiempos_json'] ?? '') ?>">
          <input id="daysBaseInput" type="hidden" name="dias_base" value="<?= h($review['dias_base'] ?? '0') ?>">
          <input id="daysAdjustedInput" type="hidden" name="dias_ajustados" value="<?= h($review['dias_ajustados'] ?? '0') ?>">

          <div class="review-tabs" role="tablist">
            <button class="review-tab active" type="button" data-review-tab="documents">1. Revisión documental</button>
            <button class="review-tab" type="button" data-review-tab="times">2. Tiempos de evaluación</button>
          </div>

          <section class="review-tab-panel active" data-review-panel="documents">
            <div class="review-section-title"><span>1.1</span><div><h2>Información de la solicitud</h2><p>Datos registrados por el cliente y clasificación acreditada.</p></div></div>
            <div class="review-info-grid">
              <div><span>Organización</span><strong><?= h($selectedRequest['razon_social']) ?></strong></div>
              <div><span>Tipo de evaluación</span><select name="tipo_evaluacion" required>
                <?php $evaluationType = (string) ($review['tipo_evaluacion'] ?? ($service['proposito'] ?? '')); ?>
                <?php foreach (['Otorgamiento', 'Modificación de alcance', 'Extraordinaria', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'] as $option): ?>
                  <option value="<?= h($option) ?>" <?= $evaluationType === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                <?php endforeach; ?>
              </select></div>
              <div><span>Reglamento</span><strong><?= h($selectedRequest['reglamento_norma']) ?></strong></div>
              <div><span>Estado ISO declarado</span><strong><?= h($isoStatus) ?></strong></div>
              <div><span>Medios tecnológicos</span><strong class="<?= $remoteTechnology ? 'text-good' : 'text-alert' ?>"><?= $remoteTechnology ? 'Sí dispone' : 'No dispone' ?></strong></div>
              <div><span>Fecha de solicitud</span><strong><?= h($selectedRequest['fecha_solicitud']) ?></strong></div>
            </div>

            <div class="review-section-title"><span>1.2</span><div><h2>Productos incluidos</h2><p>La evaluación del servicio es obligatoria aunque el proceso no requiera ensayos nuevos.</p></div></div>
            <div class="table-wrap-lite"><table class="data-table compact-review-table">
              <thead><tr><th>Producto</th><th>Familia / subfamilia</th><th>Fabricante</th><th>NACE</th><th>Referencia</th></tr></thead>
              <tbody><?php foreach ($products as $product): ?><tr>
                <td><strong><?= h($product['producto']) ?></strong></td>
                <td><?= h($product['familia']) ?><br><span class="muted"><?= h($product['subfamilia_tecnica'] ?: $product['subfamilia']) ?></span></td>
                <td><?= h($product['fabricante'] ?: 'Por validar') ?></td>
                <td><span class="tag"><?= h($product['codigo_nace'] ?: '-') ?></span></td>
                <td><?= h($product['referencia_modelo'] ?: '-') ?></td>
              </tr><?php endforeach; ?></tbody>
            </table></div>

            <div class="review-two-columns">
              <section>
                <div class="review-section-title compact"><span>1.3</span><div><h2>Documentos aportados</h2><p>Verifica disponibilidad e idioma de la evidencia.</p></div></div>
                <div class="review-checklist">
                  <?php
                    $documentRows = [
                        ['Solicitud diligenciada', 'doc_solicitud', $storedDocuments['solicitud'] ?? 'SI', false],
                        ['Fichas técnicas', 'doc_ficha_tecnica', $storedDocuments['ficha_tecnica'] ?? ($hasDatasheet ? 'SI' : 'NO'), false],
                        ['Registro fotográfico', 'doc_registro_fotografico', $storedDocuments['registro_fotografico'] ?? ($hasPhoto ? 'SI' : 'NA'), true],
                        ['ISO 9001 del fabricante', 'doc_iso_9001', $storedDocuments['iso_9001'] ?? ($hasIso ? 'SI' : 'NO'), true],
                        ['Test Reports', 'doc_test_reports', $storedDocuments['test_reports'] ?? ($hasTestReport ? 'SI' : 'NO'), true],
                        ['Carta de no cambios', 'doc_carta_no_cambios', $storedDocuments['carta_no_cambios'] ?? 'NA', true],
                    ];
                  ?>
                  <?php foreach ($documentRows as [$label, $name, $value, $allowNA]): ?>
                    <div class="review-check-row"><strong><?= h($label) ?></strong><div><?php review_radio_group($name, (string) $value, (bool) $allowNA); ?></div></div>
                  <?php endforeach; ?>
                  <label class="review-select-row"><span>Idioma de las fichas técnicas</span><select name="idioma_fichas" required>
                    <?php $language = (string) ($storedDocuments['idioma_fichas'] ?? 'Español'); ?>
                    <?php foreach (['Español', 'Inglés', 'Otro'] as $option): ?><option <?= $language === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?>
                  </select></label>
                </div>
                <div class="attachment-list">
                  <h3>Archivos de la solicitud</h3>
                  <?php foreach ($attachments as $attachment): ?>
                    <a href="api/download-request-attachment.php?id=<?= h($attachment['adjunto_id']) ?>"><span><?= h(strtoupper((string) $attachment['extension'])) ?></span><strong><?= h($attachment['nombre_original']) ?></strong><small><?= h(str_replace('_', ' ', (string) $attachment['categoria'])) ?></small></a>
                  <?php endforeach; ?>
                </div>
              </section>

              <section>
                <div class="review-section-title compact"><span>1.4</span><div><h2>Validación de información</h2><p>Confirma alcance, sistema de gestión y suficiencia de reportes.</p></div></div>
                <div class="validation-card">
                  <div><strong>Producto dentro del alcance de Servimeters</strong><div><?php review_radio_group('producto_en_alcance', (string) ($review['producto_en_alcance'] ?? ''), false); ?></div></div>
                  <div><strong>ISO 9001 vigente y con alcance de fabricación</strong><div><?php review_radio_group('iso_conforme', (string) ($review['iso_conforme'] ?? ''), true); ?></div></div>
                  <div><strong>Suficiencia y cumplimiento de Test Reports</strong><div><?php review_radio_group('test_reports_suficientes', (string) ($review['test_reports_suficientes'] ?? ''), true); ?></div></div>
                  <label><span>Tipo de auditoría técnica</span><select name="tipo_auditoria" required>
                    <?php $auditType = (string) ($review['tipo_auditoria'] ?? ($remoteTechnology ? 'Remota' : 'En sitio')); ?>
                    <?php foreach (['En sitio', 'Remota', 'Mixta'] as $option): ?><option value="<?= h($option) ?>" <?= $auditType === $option ? 'selected' : '' ?> <?= !$remoteTechnology && $option !== 'En sitio' ? 'disabled' : '' ?>><?= h($option) ?></option><?php endforeach; ?>
                  </select></label>
                </div>

                <div class="iso-analysis-module">
                  <div class="section-heading"><div><h3>Análisis asistido del certificado ISO</h3><p>Python compara titular, vigencia y alcance con los productos solicitados.</p></div><span class="tag">Validación técnica</span></div>
                  <?php if ($isoAttachments): ?>
                    <label>Certificado ya aportado<select id="existingIsoAttachment">
                      <?php foreach ($isoAttachments as $attachment): ?><option value="<?= h($attachment['adjunto_id']) ?>"><?= h($attachment['nombre_original']) ?></option><?php endforeach; ?>
                    </select></label>
                  <?php endif; ?>
                  <label class="iso-review-upload">Adjuntar otro certificado <b>Opcional</b><input id="reviewIsoFile" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small>El archivo nuevo quedará asociado a la solicitud.</small></label>
                  <button id="analyzeIsoCertificate" class="button secondary" type="button">Analizar certificado</button>
                  <div id="isoAnalysisStatus" class="analysis-status" hidden></div>
                  <div id="isoAnalysisResult" class="iso-analysis-result" hidden></div>
                </div>
              </section>
            </div>

            <section class="review-conclusion">
              <div class="review-section-title compact"><span>1.5</span><div><h2>Conclusión de la revisión documental</h2><p>La aprobación habilita la solicitud para construir la precotización.</p></div></div>
              <div class="review-conclusion-grid">
                <label>Decisión de viabilidad<select name="decision_viabilidad" required>
                  <option value="">Selecciona</option>
                  <option value="SI" <?= ($review['decision_viabilidad'] ?? '') === 'SI' ? 'selected' : '' ?>>Sí, proceder con el servicio</option>
                  <option value="NO" <?= ($review['decision_viabilidad'] ?? '') === 'NO' ? 'selected' : '' ?>>No, solicitar información o rechazar</option>
                </select></label>
                <label>Observaciones<textarea name="observaciones_revision" maxlength="2000" rows="4" placeholder="Registra faltantes, alertas o justificaciones técnicas."><?= h($review['revision_observaciones'] ?? '') ?></textarea></label>
              </div>
              <button class="button secondary" type="button" data-open-review-tab="times">Continuar a tiempos de evaluación</button>
            </section>
          </section>

          <section class="review-tab-panel" data-review-panel="times" hidden>
            <div class="review-section-title"><span>2.1</span><div><h2>Tiempo determinado de evaluación</h2><p>La evaluación inicial, el análisis de resultados y el comité permanecen incluso cuando no se requieren ensayos.</p></div></div>
            <div class="time-guidance"><strong>Regla del proceso</strong><p>Seleccione <b>No requiere</b> en laboratorio o atestiguamiento cuando existan reportes aceptables. Esto no elimina las demás actividades de certificación.</p></div>
            <div class="evaluation-time-grid">
              <label>Factor de seguridad (%)<input id="safetyFactor" name="factor_seguridad" type="number" min="0" max="50" step="1" value="<?= h($review['factor_seguridad'] ?? '15') ?>"></label>
              <label>1. Evaluación inicial<select id="timeInitial" required><option value="0.5">Muy baja - Proceso documental</option><option value="0.75" selected>Baja - Revisión estándar</option><option value="1.25">Media - Revisión mixta</option><option value="1.6">Alta - Complejidad media</option><option value="2">Muy alta - Alta complejidad</option></select></label>
              <label>2. Evaluación del sistema de gestión<select id="timeManagement" required><option value="0">No requiere / validación documental</option><option value="0.5">Baja - Verificación básica</option><option value="1">Media - Evaluación estándar</option><option value="2">Alta - Evaluación en sitio</option></select></label>
              <label>3. Evaluación de laboratorio<select id="timeLaboratory" required><option value="0">No requiere / laboratorio acreditado</option><option value="0.6">Baja - Revisión de alcance menor</option><option value="1">Media - Evaluación de segunda parte</option><option value="2">Muy alta - Múltiples laboratorios</option></select></label>
              <label>4. Atestiguamiento de ensayos<select id="timeWitness" required><option value="0">No requiere / reportes aceptados</option><option value="0.5">Baja - Ensayos de rutina</option><option value="1">Media - Ensayos de baja complejidad</option><option value="1.5">Alta - Complejidad media</option><option value="2.5">Muy alta - Alta complejidad</option></select></label>
              <label>5. Análisis de resultados<select id="timeAnalysis" required><option value="0.25">Muy baja - Una referencia</option><option value="0.5" selected>Baja - Hasta 7 referencias</option><option value="1">Media - Entre 8 y 20 referencias</option><option value="1.5">Alta - Más de 20 referencias</option></select></label>
            </div>
            <button id="calculateEvaluationTimes" class="button" type="button">Calcular días técnicos</button>
            <div id="evaluationResults" class="evaluation-results" hidden>
              <div class="evaluation-metrics"><div><span>Total base</span><strong id="baseDaysOutput">0</strong></div><div><span>Total ajustado</span><strong id="adjustedDaysOutput">0 días</strong></div></div>
              <div class="table-wrap-lite"><table class="data-table"><thead><tr><th>Actividad</th><th>Nivel</th><th>Días base</th><th>Factor esquema</th><th>Días ajustados</th></tr></thead><tbody id="evaluationBreakdown"></tbody></table></div>
            </div>
          </section>

          <?php if ($canEditReview): ?>
            <footer class="review-actions">
              <span>La precotización se habilita únicamente después de aprobar esta revisión.</span>
              <button class="button secondary" type="submit" name="submit_action" value="guardar">Guardar revisión</button>
              <?php if ($canApproveReview): ?><button class="button" type="submit" name="submit_action" value="aprobar">Aprobar y habilitar precotización</button><?php endif; ?>
            </footer>
          <?php endif; ?>
        </form>
        <script id="technicalReviewData" type="application/json"><?= $reviewDataJson ?></script>
        <script src="assets/js/technical-review.js"></script>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
