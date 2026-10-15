<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Support/UI.php';
require_once __DIR__ . '/app/Support/ProductRequestRules.php';

$authUser = auth_require_permission('requests.view');

$requestId = (int) ($_GET['id'] ?? 0);
$request = null;
$products = [];
$factories = [];
$attachments = [];
$notifications = [];
$requestData = [];
$error = '';

try {
    if ($requestId <= 0) {
        throw new RuntimeException('Solicitud no valida.');
    }
    $pdo = db();
    $requestScope = auth_request_scope_sql('s');
    $statement = $pdo->prepare(
        "SELECT s.*, c.razon_social, c.tipo_documento, c.numero_documento, c.contacto_nombre,
                c.contacto_correo, c.contacto_telefono, c.direccion, c.ciudad, c.pais,
                l.nombre AS linea_nombre, co.nombre_completo AS comercial_nombre, co.correo AS comercial_correo
         FROM solicitudes_servicio s
         LEFT JOIN clientes c ON c.id = s.cliente_id
         LEFT JOIN lineas_producto l ON l.id = s.linea_producto_id
         LEFT JOIN comerciales co ON co.id = s.comercial_id
         WHERE s.id = :id AND {$requestScope['sql']}"
    );
    $statement->execute(array_merge(['id' => $requestId], $requestScope['params']));
    $request = $statement->fetch() ?: null;
    if (!$request) {
        throw new RuntimeException('La solicitud no existe.');
    }

    $productStatement = $pdo->prepare(
        'SELECT sp.*, cp.reglamento_nombre
         FROM solicitud_productos sp
         LEFT JOIN catalogo_productos_reglamentarios cp ON cp.id = sp.catalogo_producto_id
         WHERE sp.solicitud_servicio_id = :id
         ORDER BY sp.id'
    );
    $productStatement->execute(['id' => $requestId]);
    $products = $productStatement->fetchAll();

    $factoryStatement = $pdo->prepare('SELECT * FROM solicitud_fabricas WHERE solicitud_servicio_id = :id ORDER BY id');
    $factoryStatement->execute(['id' => $requestId]);
    $factories = $factoryStatement->fetchAll();

    $attachmentStatement = $pdo->prepare(
        'SELECT sa.id, sa.categoria, a.nombre_original, a.ruta_relativa, a.extension, a.tamano_bytes
         FROM solicitud_adjuntos sa
         INNER JOIN archivos_cargados a ON a.id = sa.archivo_cargado_id
         WHERE sa.solicitud_servicio_id = :id
         ORDER BY sa.id'
    );
    $attachmentStatement->execute(['id' => $requestId]);
    $attachments = $attachmentStatement->fetchAll();

    $notificationStatement = $pdo->prepare(
        'SELECT destinatario_nombre, destinatario_correo, asunto, estado, detalle_error, intentos, enviado_en, creado_en
         FROM notificaciones_correo
         WHERE solicitud_servicio_id = :id
         ORDER BY id DESC'
    );
    $notificationStatement->execute(['id' => $requestId]);
    $notifications = $notificationStatement->fetchAll();

    $decoded = json_decode((string) ($request['datos_preliminares_json'] ?? ''), true);
    $requestData = is_array($decoded) ? $decoded : [];
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle de solicitud</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('solicitud'); ?>
    <main class="app-content">
      <header class="topbar">
        <div>
          <p class="eyebrow">Registro de solicitud</p>
          <h1><?= h($request['codigo_solicitud'] ?? 'Detalle') ?></h1>
          <p><?= h($request['razon_social'] ?? '') ?></p>
        </div>
        <div class="actions">
          <a class="button secondary" href="index.php">Volver al registro</a>
          <?php if ($request): ?>
            <?php if (auth_can('requests.edit', $authUser)): ?><a class="button ghost" href="index.php?edit_request_id=<?= h($request['id']) ?>#solicitud-form">Editar solicitud</a><?php endif; ?>
            <a class="button" href="pre_cotizacion.php?solicitud_id=<?= h($request['id']) ?>">Crear pre cotizacion</a>
          <?php endif; ?>
        </div>
      </header>

      <?php if ($error !== ''): ?>
        <div class="notice error"><?= h($error) ?></div>
      <?php elseif ($request): ?>
        <?php $representative = is_array($requestData['representante'] ?? null) ? $requestData['representante'] : []; ?>
        <section class="panel">
          <div class="panel-header">
            <div><h2>Solicitante y asignacion</h2></div>
            <span class="tag"><?= h($request['estado']) ?></span>
          </div>
          <div class="detail-grid">
            <div><span>Cliente</span><strong><?= h($request['razon_social']) ?></strong></div>
            <div><span>Documento</span><strong><?= h(trim(($request['tipo_documento'] ?? '') . ' ' . ($request['numero_documento'] ?? ''))) ?></strong></div>
            <div><span>Contacto</span><strong><?= h($request['contacto_nombre']) ?></strong></div>
            <div><span>Correo</span><strong><?= h($request['contacto_correo']) ?></strong></div>
            <div><span>Telefono</span><strong><?= h($request['contacto_telefono']) ?></strong></div>
            <div><span>Ciudad y pais</span><strong><?= h(trim(($request['ciudad'] ?? '') . ', ' . ($request['pais'] ?? ''), ', ')) ?></strong></div>
            <div><span>Reglamento</span><strong><?= h($request['reglamento_norma']) ?></strong></div>
            <div><span>Comercial</span><strong><?= h($request['comercial_nombre'] ?: 'Sin asignar') ?></strong></div>
            <div><span>Correo comercial</span><strong><?= h($request['comercial_correo'] ?: '-') ?></strong></div>
            <div><span>Estado del correo</span><strong><?= h($request['correo_notificacion_estado'] ?: 'Sin notificacion') ?></strong></div>
            <?php if (!empty($representative['aplica'])): ?>
              <div><span>Organizacion representante</span><strong><?= h($representative['nombre'] ?? '-') ?></strong></div>
              <div><span>NIT representante</span><strong><?= h($representative['nit'] ?? '-') ?></strong></div>
              <div><span>Correo representante</span><strong><?= h($representative['correo'] ?? '-') ?></strong></div>
              <div><span>Telefono representante</span><strong><?= h($representative['telefono'] ?? '-') ?></strong></div>
            <?php endif; ?>
          </div>
        </section>

        <section class="panel subsection">
          <div class="panel-header">
            <div>
              <h2>Clasificacion del servicio</h2>
              <div class="muted"><?= h($request['servicio_solicitado']) ?></div>
            </div>
          </div>
          <?php $service = is_array($requestData['servicio'] ?? null) ? $requestData['servicio'] : []; ?>
          <div class="detail-grid">
            <div><span>Proposito</span><strong><?= h($service['proposito'] ?? '-') ?></strong></div>
            <div><span>Esquemas</span><strong><?= h(isset($service['esquemas']) && is_array($service['esquemas']) ? implode(' | ', $service['esquemas']) : ($service['esquema'] ?? '-')) ?></strong></div>
            <div><span>Motivo</span><strong><?= h($service['motivo'] ?? '-') ?></strong></div>
            <div><span>Estado ISO 9001</span><strong><?= h($service['iso_9001_vigente'] ?? '-') ?></strong></div>
            <div><span>Vigencia ISO 9001</span><strong><?= h($service['vigencia_iso_9001'] ?? '-') ?></strong></div>
            <div><span>Esquema recomendado</span><strong><?= h($service['esquema_recomendado'] ?? '-') ?></strong></div>
            <?php if (!empty($service['esquemas_opcionales'])): ?>
              <div><span>Consideracion adicional</span><strong><?= h($service['esquemas_opcionales']) ?></strong></div>
            <?php endif; ?>
            <div><span>Evaluacion remota</span><strong><?= h($requestData['medios_evaluacion_remota'] ?? '-') ?></strong></div>
          </div>
        </section>

        <section class="panel subsection">
          <div class="panel-header"><div><h2>Productos solicitados</h2></div><span class="tag"><?= h(count($products)) ?></span></div>
          <?php if (!$products): ?>
            <div class="notice">Esta solicitud no tiene productos detallados.</div>
          <?php else: ?>
            <div class="table-wrap-lite">
              <table class="data-table detail-product-table">
                <thead><tr><th>Producto</th><th>Alcance y esquema</th><th>Familia y subfamilia</th><th>NACE</th><th>Referencia</th><th>Marca</th><th>Cantidad</th><th>Especificaciones</th></tr></thead>
                <tbody>
                  <?php foreach ($products as $product): ?>
                    <?php $specs = json_decode((string) ($product['especificaciones_json'] ?? ''), true) ?: []; ?>
                    <?php $analysis = json_decode((string) ($product['analisis_json'] ?? ''), true) ?: []; ?>
                    <?php $technicalValues = is_array($specs['campos_tecnicos'] ?? null) ? $specs['campos_tecnicos'] : []; ?>
                    <tr>
                      <td><strong><?= h($product['producto']) ?></strong><br><span class="muted"><?= h($specs['nombre_cliente'] ?? '') ?></span></td>
                      <td><?= h($product['reglamento_nombre'] ?: $product['reglamento_codigo']) ?><br><span class="tag"><?= h($product['esquema_solicitado'] ?: '-') ?></span><br><span class="muted"><?= h($analysis['mode'] ?? 'Registro manual') ?></span></td>
                      <?php $technicalSubfamily = $product['subfamilia_tecnica'] ?: ($specs['subfamilia_detalle'] ?? ''); ?>
                      <td><?= h($product['familia']) ?><br><span class="muted"><?= h($product['subfamilia']) ?></span><?php if ($technicalSubfamily !== ''): ?><br><span class="tag">Subfamilia técnica: <?= h($technicalSubfamily) ?></span><?php endif; ?></td>
                      <td><span class="tag"><?= h($product['codigo_nace'] ?: ($specs['codigo_nace'] ?? '-')) ?></span></td>
                      <td><?= h($product['referencia_modelo'] ?: '-') ?></td>
                      <td><?= h($product['marca'] ?: '-') ?></td>
                      <td><?= h($product['cantidad'] ?: '-') ?></td>
                      <td>
                        <?php if ($technicalValues): ?>
                          <?php foreach (product_technical_fields_for_regulation((string) $product['reglamento_codigo']) as $definition): ?>
                            <?php $technicalValue = trim((string) ($technicalValues[$definition['key']] ?? '')); ?>
                            <?php if ($technicalValue !== ''): ?><span class="muted"><?= h($definition['label']) ?>:</span> <?= h($technicalValue) ?><br><?php endif; ?>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span class="muted">Material:</span> <?= h($specs['material'] ?? '-') ?><br>
                          <span class="muted">Tension:</span> <?= h($specs['tension_nominal'] ?? '-') ?><br>
                          <span class="muted">Potencia:</span> <?= h($specs['potencia'] ?? '-') ?><br>
                          <span class="muted">Frecuencia:</span> <?= h($specs['frecuencia'] ?? '-') ?><br>
                          <span class="muted">IP:</span> <?= h($specs['grado_proteccion_ip'] ?? '-') ?><br>
                        <?php endif; ?>
                        <span class="muted">Articulo:</span> <?= h($specs['articulo_general'] ?? '-') ?><br>
                        <span class="muted">Subnumeral:</span> <?= h($specs['subnumeral'] ?? '-') ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

        <div class="dashboard-grid subsection">
          <section class="panel">
            <div class="panel-header"><div><h2>Fabricante</h2></div></div>
            <?php foreach ($factories as $factory): ?>
              <div class="detail-grid single-detail">
                <div><span>Fabrica</span><strong><?= h($factory['nombre']) ?></strong></div>
                <div><span>Pais</span><strong><?= h($factory['pais'] ?: '-') ?></strong></div>
                <div><span>Direccion</span><strong><?= h($factory['direccion'] ?: '-') ?></strong></div>
                <div><span>Certificado ISO</span><strong><?= h($factory['numero_certificado_iso'] ?: '-') ?></strong></div>
              </div>
            <?php endforeach; ?>
          </section>

          <section class="panel">
            <div class="panel-header"><div><h2>Soportes y notificaciones</h2></div></div>
            <?php if ($attachments): ?>
              <ul class="list compact-list">
                <?php foreach ($attachments as $attachment): ?>
                  <li><a href="api/download-request-attachment.php?id=<?= h($attachment['id']) ?>"><?= h($attachment['nombre_original']) ?></a><span class="tag"><?= h(str_replace('_', ' ', (string) $attachment['categoria'])) ?> · <?= h(strtoupper($attachment['extension'])) ?></span></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="muted">Sin archivos adjuntos.</p>
            <?php endif; ?>
            <?php if ($notifications): ?>
              <div class="subsection">
                <?php foreach ($notifications as $notification): ?>
                  <div class="notice <?= $notification['estado'] === 'enviado' ? 'ok' : ($notification['estado'] === 'fallido' ? 'error' : 'warn') ?>">
                    <strong><?= h($notification['estado']) ?></strong><br>
                    <?= h($notification['destinatario_nombre']) ?> · <?= h($notification['destinatario_correo']) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
