<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';
require_once __DIR__ . '/app/Support/ProductCatalog.php';
require_once __DIR__ . '/app/Support/CertificationServiceRules.php';
require_once __DIR__ . '/app/Support/ProductRequestRules.php';

$catalog = [];
$regulations = [];
$commercials = [];
$manager = null;
$dbMessage = '';

try {
    $pdo = db();
    $catalog = request_catalog_rows($pdo);
    $regulations = request_regulation_rows($pdo);
    $commercials = $pdo->query(
        "SELECT id, nombre_completo, correo FROM comerciales
         WHERE activo = 1 AND rol = 'comercial' ORDER BY nombre_completo"
    )->fetchAll();
    $manager = $pdo->query(
        "SELECT id, nombre_completo, correo FROM comerciales
         WHERE activo = 1 AND rol = 'jefe_comercial' ORDER BY id LIMIT 1"
    )->fetch() ?: null;
} catch (Throwable) {
    $dbMessage = 'La base de datos no está disponible. Verifica el servicio configurado y vuelve a cargar la página.';
}

foreach ($catalog as &$catalogItem) {
    $scopeClassification = product_scope_classification_for(
        (string) ($catalogItem['reglamento_codigo'] ?? ''),
        (string) ($catalogItem['familia_nombre'] ?? ''),
        (string) ($catalogItem['producto_nombre'] ?? '')
    );
    if ($scopeClassification) {
        $scopeClassification['opciones'] = product_scope_recommended_options($scopeClassification);
    }
    $catalogItem['clasificacion_alcance'] = $scopeClassification;
    $catalogItem['subfamilia_referencias'] = $scopeClassification
        ? array_values(array_map(
            static fn(array $option): string => (string) ($option['etiqueta'] ?? ''),
            (array) ($scopeClassification['opciones'] ?? [])
        ))
        : [];
}
unset($catalogItem);

$status = (string) ($_GET['status'] ?? '');
$requestCode = (string) ($_GET['codigo'] ?? '');
$mailStatus = (string) ($_GET['mail'] ?? '');
$requestDataJson = json_encode(
    [
        'catalog' => $catalog,
        'regulations' => $regulations,
        'serviceRules' => certification_service_rules(),
        'technicalFields' => product_technical_fields_config(),
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE
);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitud de certificación | Servimeters</title>
  <link rel="stylesheet" href="assets/css/client-request.css">
</head>
<body>
  <header class="app-bar">
    <a class="brand" href="solicitud_cliente.php" aria-label="Inicio de la solicitud">
      <span class="brand-mark">S</span>
      <span><strong>SERVIMETERS</strong><small>Certificación de producto</small></span>
    </a>
    <a class="internal-link" href="index.php">Registro interno</a>
  </header>

  <main class="request-app">
    <?php if ($status === 'ok' && $requestCode !== ''): ?>
      <section class="success-view" role="status">
        <span class="success-icon">OK</span>
        <p class="kicker">Solicitud recibida</p>
        <h1><?= h($requestCode) ?></h1>
        <p>La información del servicio, los productos y sus evidencias quedaron registrados.</p>
        <?php if ($mailStatus === 'enviado'): ?>
          <p class="status-line good">El responsable comercial ya fue notificado.</p>
        <?php elseif ($mailStatus === 'fallido'): ?>
          <p class="status-line warn">La solicitud se guardo, pero el correo no pudo enviarse.</p>
        <?php else: ?>
          <p class="status-line neutral">El correo queda pendiente hasta configurar SMTP.</p>
        <?php endif; ?>
        <div class="button-row centered">
          <a class="button primary" href="solicitud_cliente.php">Nueva solicitud</a>
          <a class="button secondary" href="index.php">Ver registro</a>
        </div>
      </section>
    <?php else: ?>
      <section class="request-heading">
        <div>
          <p class="kicker">Clasificador semiautomatizado</p>
          <h1>Solicitud de certificación</h1>
          <p>Selecciona el servicio y valida cada producto contra la matriz oficial.</p>
        </div>
        <div class="scope-counter">
          <strong><?= h(count($regulations)) ?></strong>
          <span>alcances oficiales</span>
        </div>
      </section>

      <?php if ($dbMessage !== ''): ?>
        <div class="notice error"><?= h($dbMessage) ?></div>
      <?php elseif (!$catalog): ?>
        <div class="notice warn">No hay productos activos en la matriz de alcance.</div>
      <?php endif; ?>
      <?php if ($status === 'error'): ?>
        <div class="notice error"><?= h((string) ($_GET['message'] ?? 'No fue posible registrar la solicitud.')) ?></div>
      <?php endif; ?>

      <form id="assistedRequestForm" action="api/save-client-request.php" method="post" enctype="multipart/form-data" novalidate>
        <nav class="progress-nav" aria-label="Etapas de la solicitud">
          <button class="progress-step active" type="button" data-step="0"><span>1</span><b>Cliente</b></button>
          <button class="progress-step" type="button" data-step="1"><span>2</span><b>Servicio</b></button>
          <button class="progress-step" type="button" data-step="2"><span>3</span><b>Productos</b></button>
          <button class="progress-step" type="button" data-step="3"><span>4</span><b>Envío</b></button>
        </nav>

        <div class="stage-frame">
          <section class="request-stage active" data-stage="0">
            <div class="stage-title">
              <div><span>Paso 1 de 4</span><h2>Datos del cliente</h2></div>
              <p>Información de contacto para la solicitud.</p>
            </div>
            <div class="field-grid three-columns">
              <label class="span-two">Nombre o razón social<input name="cliente_nombre" type="text" autocomplete="organization" required></label>
              <label>Tipo de documento<select name="tipo_documento" required><option value="NIT">NIT</option><option value="CC">Cédula</option><option value="CE">Cédula de extranjería</option><option value="Pasaporte">Pasaporte</option></select></label>
              <label>Número de documento<input name="numero_documento" type="text" required></label>
              <label>Persona de contacto<input name="contacto_nombre" type="text" autocomplete="name" required></label>
              <label>Cargo<input name="contacto_cargo" type="text" required></label>
              <label>Correo electrónico<input name="contacto_correo" type="email" autocomplete="email" required></label>
              <label>Teléfono<input name="contacto_telefono" type="tel" autocomplete="tel" required></label>
              <label>País<input name="pais" type="text" value="Colombia" autocomplete="country-name" required></label>
              <label>Ciudad<input name="ciudad" type="text" autocomplete="address-level2"></label>
              <label class="span-two">Dirección<input name="direccion" type="text" autocomplete="street-address"></label>
            </div>

            <label class="representative-toggle">
              <input id="hasRepresentative" name="tiene_representante" type="checkbox" value="1">
              <span>Otra organización representa al solicitante ante Servimeters</span>
            </label>
            <div id="representativeFields" class="representative-fields" hidden>
              <div class="section-heading"><h3>Organización representante</h3><span>Contacto autorizado ante Servimeters</span></div>
              <div class="field-grid three-columns">
                <label class="span-two">Nombre o razón social<input name="representante_nombre" type="text" data-representative-required></label>
                <label>NIT<input name="representante_nit" type="text" data-representative-required></label>
                <label>Correo electrónico<input name="representante_correo" type="email" data-representative-required></label>
                <label>Teléfono<input name="representante_telefono" type="tel" data-representative-required></label>
                <label class="span-two">Dirección<input name="representante_direccion" type="text"></label>
              </div>
            </div>
          </section>

          <section class="request-stage" data-stage="1" hidden>
            <div class="stage-title">
              <div><span>Paso 2 de 4</span><h2>Clasificación del servicio</h2></div>
              <p>El alcance elegido filtra las familias, subfamilias y numerales.</p>
            </div>
            <div class="field-grid three-columns">
              <label class="span-two">Reglamento o alcance aplicable
                <select id="serviceRegulation" name="reglamento_codigo" required>
                  <option value="">Selecciona el alcance</option>
                  <?php foreach ($regulations as $regulation): ?>
                    <option value="<?= h($regulation['codigo']) ?>"><?= h($regulation['nombre']) ?><?= !empty($regulation['pais']) ? ' - ' . h($regulation['pais']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <div class="field-control scheme-field">
                <div class="field-label-row">
                  <label for="serviceScheme">Esquema solicitado</label>
                  <button id="schemeHelpToggle" class="info-dot" type="button" aria-label="Ver definición del esquema" aria-expanded="false" title="Definición del esquema">i</button>
                </div>
                <select id="serviceScheme" name="esquema_solicitado" required disabled><option value="">Selecciona el alcance</option></select>
                <aside id="schemeHelpPanel" class="field-help-popover" hidden>
                  <div><span>Esquema</span><strong id="schemeHelpTitle"></strong></div>
                  <p id="schemeHelpSummary"></p>
                  <dl>
                    <div><dt>Aplicación</dt><dd id="schemeHelpApplies"></dd></div>
                    <div><dt>Vigencia</dt><dd id="schemeHelpValidity"></dd></div>
                    <div><dt>Seguimiento</dt><dd id="schemeHelpFollowUp"></dd></div>
                    <div><dt>Condiciones</dt><dd id="schemeHelpConditions"></dd></div>
                  </dl>
                </aside>
              </div>
              <label>Propósito del servicio<select id="servicePurpose" name="proposito_servicio" required disabled><option value="">Selecciona primero el esquema</option></select></label>
              <label class="span-two">Motivo o finalidad del servicio<select name="motivo_servicio" required><option value="">Selecciona</option><option>Fabricar, importar y comercializar de forma continua</option><option>Importar y comercializar un lote específico</option><option>Mantener o renovar una certificación vigente</option><option>Modificar el alcance de una certificación vigente</option></select></label>
              <fieldset class="inline-choice span-two"><legend>Estado del sistema de gestión ISO 9001</legend><label><input type="radio" name="iso_9001_vigente" value="certificacion_vigente" required>Certificación vigente</label><label><input type="radio" name="iso_9001_vigente" value="implementacion" required>Implementado</label><label><input type="radio" name="iso_9001_vigente" value="ninguna" required>Ninguna</label></fieldset>
              <label id="isoValidityField" hidden>Vigencia ISO 9001<input name="vigencia_iso_9001" type="date"></label>
              <label id="isoCertificateField" hidden>Número de certificado vigente<input name="numero_certificado_vigente" type="text" maxlength="120"></label>
              <p id="isoExpiryAlert" class="iso-expiry-alert span-all" role="alert" hidden></p>
              <label class="span-two">Observaciones<textarea name="observaciones" rows="3" placeholder="Información adicional para el equipo comercial"></textarea></label>
              <fieldset class="remote-evaluation-choice span-all">
                <legend>Medios tecnológicos para evaluación remota</legend>
                <p>Por favor, indique si cuenta con los medios tecnológicos necesarios para realizar actividades de evaluación de forma remota cuando sea necesario.</p>
                <div>
                  <label><input name="medios_tecnologicos_remotos" type="radio" value="1" required><span>Sí</span></label>
                  <label><input name="medios_tecnologicos_remotos" type="radio" value="0" required><span>No</span></label>
                </div>
              </fieldset>
            </div>
            <div id="serviceScopePreview" class="scope-preview" hidden></div>
            <section id="schemeRecommendation" class="scheme-recommendation" hidden aria-live="polite">
              <div class="recommendation-heading"><span>Resultado de la evaluación</span><strong>Esquema recomendado</strong></div>
              <div class="recommendation-context">
                <div><span>Estado ISO</span><strong id="recommendationIso"></strong></div>
                <div><span>Esquema evaluado</span><strong id="recommendationScheme"></strong></div>
              </div>
              <p id="recommendationResult"></p>
              <small id="recommendationOptional" hidden></small>
            </section>
          </section>

          <input id="schemeWarningAccepted" name="acepta_alerta_esquema" type="hidden" value="0">

          <section class="request-stage" data-stage="2" hidden>
            <div class="stage-title">
              <div><span>Paso 3 de 4</span><h2>Clasificación y evidencias</h2></div>
              <p>Primero clasifica el producto y después valida su ficha.</p>
            </div>
            <div id="selectedServiceSummary" class="selected-service-summary"></div>

            <div class="classifier-layout">
              <div class="classifier-tool">
                <div class="tool-tabs" role="tablist">
                  <button class="tool-tab active" type="button" data-tool-tab="manual" role="tab">1. Clasificación</button>
                  <button class="tool-tab" type="button" data-tool-tab="evidence" role="tab">2. Archivos</button>
                  <button class="tool-tab" type="button" data-tool-tab="result" role="tab" disabled>3. Validación</button>
                </div>

                <div class="tool-panel active" data-tool-panel="manual">
                  <div class="tool-intro"><strong>Selecciona la clasificación oficial</strong><span>La aplicación usará esta selección para orientar el análisis de los archivos.</span></div>
                  <section id="retieClassificationFallback" class="retie-classification-fallback" hidden>
                    <div class="retie-fallback-heading">
                      <div><span id="scopeClassificationKicker">Clasificación acreditada</span><h3>Categoría, producto y subfamilia</h3></div>
                      <small>17-CPR-002</small>
                    </div>
                    <div class="field-grid retie-fallback-grid">
                      <label><span id="scopeFamilyLabel">Familia acreditada</span>
                        <select id="retieFamilySelect"><option value="">Selecciona una familia</option></select>
                      </label>
                      <label><span id="scopeProductLabel">Producto acreditado</span>
                        <select id="retieSubfamilySelect" disabled><option value="">Selecciona un producto</option></select>
                      </label>
                      <label><span id="scopeSubfamilyLabel">Subfamilia técnica</span>
                        <select id="scopeTechnicalSubfamilySelect" disabled><option value="">Selecciona una subfamilia</option></select>
                      </label>
                    </div>
                    <p class="scope-subfamily-note">Si no conoce con precisión las características técnicas de la subfamilia, déjela por validar. La ficha técnica y la fotografía ayudarán a completar esta clasificación.</p>
                    <div id="retieClassificationDetail" class="retie-classification-detail" hidden></div>
                    <div class="analysis-actions">
                      <p id="retieClassificationCount"></p>
                      <button id="useRetieClassification" class="button primary" type="button" disabled>Confirmar clasificación</button>
                    </div>
                  </section>
                  <div id="manualCatalogBlock">
                    <div class="field-grid manual-search-grid">
                      <label class="span-two">Buscar producto, familia o subfamilia<input id="manualCatalogSearch" type="search" placeholder="Ej. aislador, juguete, vajilla"></label>
                      <label>Familia<select id="manualCatalogFamily"><option value="">Todas las familias</option></select></label>
                      <label>Producto o subfamilia<select id="manualCatalogProduct"><option value="">Selecciona un producto</option></select></label>
                    </div>
                    <div id="manualCatalogDetail" class="manual-catalog-detail" hidden></div>
                    <div class="analysis-actions">
                      <p id="manualCatalogCount">Selecciona primero el servicio.</p>
                      <button id="useManualProduct" class="button primary" type="button" disabled>Confirmar categoría</button>
                    </div>
                  </div>
                </div>

                <div class="tool-panel" data-tool-panel="evidence" hidden>
                  <label class="prominent-field">Nombre genérico del producto
                    <input id="productName" type="text" placeholder="Ej. aislador polimerico de suspension" autocomplete="off">
                  </label>
                  <div class="upload-grid">
                    <label class="upload-box" for="productImage">
                      <span class="upload-symbol">IMG</span>
                      <strong>Fotografía del producto</strong>
                      <small>JPG, PNG o WEBP. Es opcional y ayuda a reconocer la subfamilia.</small>
                      <input id="productImage" type="file" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label class="upload-box" for="productDatasheet">
                      <span class="upload-symbol">DOC</span>
                      <strong>Ficha técnica del producto</strong>
                      <small>PDF, Word, Excel o imagen. Este archivo es obligatorio.</small>
                      <input id="productDatasheet" type="file" accept=".pdf,.docx,.xlsx,.xlsm,.jpg,.jpeg,.png,.webp,.txt">
                    </label>
                  </div>
                  <div class="evidence-requirements">
                    <strong>Contenido necesario para una validación precisa</strong>
                    <p>Adjunta un documento legible que identifique el producto, la referencia, el fabricante y las especificaciones técnicas aplicables. Según el reglamento, debe incluir valores eléctricos, edad de uso, presentación y capacidad, norma y dimensiones del material o protección de la barbilla. Las fotografías nítidas de la placa o etiqueta mejoran la lectura.</p>
                  </div>
                  <div id="uploadPreview" class="upload-preview" hidden>
                    <img id="productImagePreview" alt="Vista previa del producto">
                    <div><strong id="previewProductName"></strong><span id="previewFileNames"></span></div>
                  </div>
                  <div class="analysis-actions">
                    <p>La ficha técnica del producto es obligatoria. La fotografía es complementaria.</p>
                    <button id="analyzeProduct" class="button primary" type="button" <?= !$catalog ? 'disabled' : '' ?>>Analizar producto</button>
                  </div>
                  <div id="analysisStatus" class="analysis-status" hidden></div>
                </div>

                <div class="tool-panel" data-tool-panel="result" hidden>
                  <div class="result-header">
                    <div><span id="analysisMode" class="mode-badge"></span><h3>Coincidencias dentro del alcance</h3></div>
                    <button class="text-button" type="button" data-tool-tab="evidence">Cambiar evidencia</button>
                  </div>
                  <p id="analysisWarning" class="analysis-warning" hidden></p>
                  <div id="analysisCandidates" class="candidate-list"></div>
                  <div id="identifiedClassification" class="identified-classification" hidden></div>

                  <div class="extracted-section">
                    <div class="section-heading"><h3>Información extraída</h3><span>Editable para corregir la lectura</span></div>
                    <div id="technicalFields" class="technical-fields"></div>
                  </div>

                  <div class="selection-footer">
                    <div class="fixed-scheme"><span>Esquema del servicio</span><strong id="resultScheme"></strong></div>
                    <label id="analysisQuantityField">Cantidad del lote<input id="analysisQuantity" type="number" min="1" step="1" value="1"></label>
                    <button id="addAnalyzedProduct" class="button primary" type="button">Agregar a la solicitud</button>
                  </div>
                </div>
              </div>

              <aside class="product-basket">
                <div class="basket-heading"><h3>Productos</h3><span id="productCount">0</span></div>
                <div id="productBasketEmpty" class="empty-basket">Los productos validados aparecerán aquí.</div>
                <div id="productBasketItems" class="basket-items"></div>
              </aside>
            </div>
            <div id="productHiddenInputs"></div>
          </section>

          <section class="request-stage" data-stage="3" hidden>
            <div class="stage-title">
              <div><span>Paso 4 de 4</span><h2>Documentos, asignación y envío</h2></div>
              <p>Adjunta los soportes disponibles y revisa el checklist antes de registrar la solicitud.</p>
            </div>
            <div class="final-layout">
              <div>
                <fieldset class="commercial-choice"><legend>¿Un comercial de Servimeters ya te atendió?</legend><label><input type="radio" name="atendido_comercial" value="1" required>Sí</label><label><input type="radio" name="atendido_comercial" value="0" required>No</label></fieldset>
                <label id="commercialSelectWrap" class="commercial-select" hidden>Comercial
                  <select name="comercial_id">
                    <option value="">Selecciona</option>
                    <?php foreach ($commercials as $commercial): ?>
                      <option value="<?= h($commercial['id']) ?>"><?= h($commercial['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <?php if ($manager): ?>
                  <p id="managerNote" class="assignment-note" hidden>Si no te atendió un comercial, se asignará a <?= h($manager['nombre_completo']) ?>.</p>
                <?php endif; ?>

                <section class="client-documents" aria-labelledby="clientDocumentsTitle">
                  <div class="section-heading prominent-section-heading">
                    <div>
                      <h3 id="clientDocumentsTitle">Documentos de soporte</h3>
                      <p>Estos archivos facilitan la revisión técnica. Podrán solicitarse posteriormente cuando sean necesarios.</p>
                    </div>
                  </div>
                  <div class="support-upload-grid">
                    <label class="support-upload" for="clientIsoCertificate">
                      <span class="upload-symbol">ISO</span>
                      <strong>Certificado ISO 9001</strong>
                      <small>PDF o imagen legible.</small>
                      <b class="optional-badge">Opcional</b>
                      <input id="clientIsoCertificate" name="certificado_iso" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                      <span id="clientIsoFileName" class="selected-file-name">Sin archivo seleccionado</span>
                    </label>
                    <label class="support-upload" for="clientTestReports">
                      <span class="upload-symbol">TR</span>
                      <strong>Test Reports</strong>
                      <small>Puede adjuntar uno o varios reportes de ensayo.</small>
                      <b class="optional-badge">Opcional</b>
                      <input id="clientTestReports" name="test_reports[]" type="file" multiple accept=".pdf,.docx,.xlsx,.xlsm,.jpg,.jpeg,.png,.webp">
                      <span id="clientTestReportNames" class="selected-file-name">Sin archivos seleccionados</span>
                    </label>
                  </div>

                  <div class="document-checklist-wrap">
                    <div class="section-heading compact-heading">
                      <h3>Checklist de documentos aportados</h3>
                      <span>Se actualiza automáticamente</span>
                    </div>
                    <table class="document-checklist">
                      <thead><tr><th>Documento</th><th>Estado</th></tr></thead>
                      <tbody>
                        <tr><td>Solicitud diligenciada</td><td><span class="document-status provided">Aportado</span></td></tr>
                        <tr><td>Fichas técnicas de los productos</td><td><span id="datasheetChecklistStatus" class="document-status pending">Pendiente</span></td></tr>
                        <tr><td>Registro fotográfico</td><td><span id="photoChecklistStatus" class="document-status optional">Opcional</span></td></tr>
                        <tr><td>Certificado ISO 9001 del fabricante</td><td><span id="isoChecklistStatus" class="document-status optional">Opcional</span></td></tr>
                        <tr><td>Test Reports</td><td><span id="testReportChecklistStatus" class="document-status optional">Opcional</span></td></tr>
                      </tbody>
                    </table>
                  </div>
                </section>
                <div class="privacy-checks">
                  <label class="privacy-check"><input type="checkbox" name="reviso_politica_tratamiento" value="1" required><span>Confirmo que revisé la <a href="https://www.servimeters.com/politica-tratamiento-informacion" target="_blank" rel="noopener noreferrer">política de tratamiento de información</a> de Servimeters.</span></label>
                  <label class="privacy-check"><input type="checkbox" name="acepta_tratamiento_datos" value="1" required><span>Autorizo el tratamiento de los datos suministrados para gestionar esta solicitud.</span></label>
                </div>
              </div>
              <div id="finalSummary" class="final-summary"></div>
            </div>
            <div class="final-notes">
              <strong>Nota:</strong>
              <p>Para el esquema 5 de reglamentos diferentes a RETIE y RETILAP es suficiente que el fabricante tenga implementado un sistema de gestión o de control de producción. Si aporta un certificado ISO 9001, este debe estar vigente y cubrir específicamente el producto y sus procesos de manufactura, construcción, ensamblaje o fabricación.</p>
              <p>Para los reglamentos RETIE Resolución 40284 y RETILAP Resolución 40286, el esquema 5 requiere un certificado ISO 9001 vigente y con alcance de fabricación. Si no se cuenta con esta certificación, el proceso debe gestionarse bajo el esquema 4 y requiere una evaluación detallada del sistema de producción del fabricante.</p>
              <p class="privacy-policy-note"><strong>Nota:</strong> Al formalizar la solicitud se acepta la política de tratamiento de datos de Servimeters S.A.S., disponible en <a href="https://www.servimeters.com/politica-tratamiento-informacion" target="_blank" rel="noopener noreferrer">Política de tratamiento de información</a>.</p>
            </div>
          </section>
        </div>

        <dialog id="schemeWarningDialog" class="confirmation-dialog" aria-labelledby="schemeWarningTitle">
          <div class="dialog-icon" aria-hidden="true">!</div>
          <h2 id="schemeWarningTitle">Recomendación técnica pendiente</h2>
          <p>La combinación seleccionada presenta una observación técnica que debe revisarse antes de continuar.</p>
          <strong id="schemeWarningText"></strong>
          <div class="dialog-actions">
            <button id="reviewSchemeButton" class="button secondary" type="button">Revisar selección</button>
            <button id="continueSchemeButton" class="button primary" type="button">Enviar la solicitud así</button>
          </div>
        </dialog>

        <footer class="form-navigation">
          <button id="previousStep" class="button secondary" type="button" hidden>Anterior</button>
          <span id="stepMessage" role="status"></span>
          <button id="nextStep" class="button primary" type="button">Continuar</button>
          <button id="submitRequest" class="button primary" type="submit" hidden>Enviar solicitud</button>
        </footer>
      </form>
      <script id="requestCatalogData" type="application/json"><?= $requestDataJson ?></script>
    <?php endif; ?>
  </main>
  <script src="assets/js/client-request.js"></script>
</body>
</html>
