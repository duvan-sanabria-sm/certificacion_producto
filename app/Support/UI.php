<?php
declare(strict_types=1);

require_once __DIR__ . '/Auth.php';

if (!function_exists('h')) {
    function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function phase_links(): array
{
    return [
        'solicitud' => ['label' => 'Solicitudes', 'description' => 'Registro y seguimiento', 'icon' => '+', 'url' => 'index.php', 'permission' => 'requests.view'],
        'revision' => ['label' => 'Revisión técnica', 'description' => 'Documentos y tiempos', 'icon' => '✓', 'url' => 'revision_tecnica.php', 'permission' => 'technical_review.view'],
        'precotizacion' => ['label' => 'Pre-cotización', 'description' => 'Costos y propuesta', 'icon' => '$', 'url' => 'pre_cotizacion.php', 'permission' => 'prequotes.view'],
        'cotizacion' => ['label' => 'Cotización final', 'description' => 'Cierre comercial', 'icon' => '▤', 'url' => 'cotizacion_final.php', 'permission' => 'final_quotes.view'],
    ];
}

function render_sidebar(string $active): void
{
    $user = auth_current_user();
    $tools = [
        ['label' => 'Formulario clientes', 'description' => 'Nueva solicitud pública', 'icon' => '✎', 'url' => 'solicitud_cliente.php', 'permission' => 'requests.create'],
        ['label' => 'Matrices normativas', 'description' => 'Plantillas y alcance', 'icon' => '▦', 'url' => 'matrices.php', 'permission' => 'matrices.view'],
        ['label' => 'Laboratorios', 'description' => 'Ensayos y tarifas', 'icon' => '⌁', 'url' => 'laboratorios.php', 'permission' => 'laboratories.view'],
        ['label' => 'Criterios', 'description' => 'Pre-cotización', 'icon' => '%', 'url' => 'criterios_precotizacion.php', 'permission' => 'prequote_criteria.manage'],
        ['label' => 'Usuarios y roles', 'description' => 'Accesos del sistema', 'icon' => '♙', 'url' => 'usuarios.php', 'permission' => 'users.manage'],
        ['label' => 'Estado de la base', 'description' => 'Diagnóstico de conexión', 'icon' => '◉', 'url' => 'api/test-db.php', 'permission' => 'database.test'],
    ];
    $home = $user ? auth_default_home($user) : 'login.php';
    $initials = '';
    foreach (preg_split('/\s+/', trim((string) ($user['nombre_completo'] ?? 'SM'))) ?: [] as $part) {
        if ($part !== '') {
            $initials .= mb_substr($part, 0, 1, 'UTF-8');
        }
        if (mb_strlen($initials, 'UTF-8') >= 2) break;
    }
    $initials = mb_strtoupper($initials ?: 'SM', 'UTF-8');
    ?>
    <aside class="sidebar" id="appSidebar" aria-label="Navegación principal">
      <div class="sidebar-header">
        <a class="sidebar-brand" href="<?= h($home) ?>" aria-label="Servimeters - Inicio">
          <span class="brand-mark">SM</span>
          <span class="brand-copy"><strong>SERVIMETERS</strong><small>Certificación de producto</small></span>
        </a>
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="appSidebar" aria-expanded="true" title="Ocultar panel">
          <span aria-hidden="true">‹</span><span class="sr-only">Mostrar u ocultar navegación</span>
        </button>
      </div>
      <div class="sidebar-scroll">
        <p class="sidebar-section-title">Fases del proceso</p>
        <nav class="phase-nav" aria-label="Fases del proceso">
          <?php foreach (phase_links() as $key => $link): ?>
            <?php if (!auth_can($link['permission'], $user)) continue; ?>
            <a class="sidebar-link <?= $key === $active ? 'active' : '' ?>" href="<?= h($link['url']) ?>" title="<?= h($link['label']) ?>">
              <span class="sidebar-icon" aria-hidden="true"><?= h($link['icon']) ?></span>
              <span class="sidebar-link-copy"><strong><?= h($link['label']) ?></strong><small><?= h($link['description']) ?></small></span>
            </a>
          <?php endforeach; ?>
        </nav>
        <p class="sidebar-section-title tools-title">Herramientas</p>
        <nav class="sidebar-tools" aria-label="Herramientas">
          <?php foreach ($tools as $tool): ?>
            <?php if (!auth_can($tool['permission'], $user)) continue; ?>
            <a class="sidebar-link <?= $active === basename($tool['url'], '.php') ? 'active' : '' ?>" href="<?= h($tool['url']) ?>" title="<?= h($tool['label']) ?>">
              <span class="sidebar-icon" aria-hidden="true"><?= h($tool['icon']) ?></span>
              <span class="sidebar-link-copy"><strong><?= h($tool['label']) ?></strong><small><?= h($tool['description']) ?></small></span>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>
      <?php if ($user): ?>
      <div class="sidebar-account">
        <a class="account-link <?= $active === 'cuenta' ? 'active' : '' ?>" href="mi_cuenta.php">
          <span class="account-avatar" aria-hidden="true"><?= h($initials) ?></span>
          <span class="sidebar-link-copy"><strong><?= h($user['nombre_completo']) ?></strong><small><?= h(auth_role_label((string) $user['rol'])) ?></small></span>
        </a>
        <form action="logout.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
          <button type="submit" title="Cerrar sesión"><span class="logout-icon" aria-hidden="true">↪</span><span class="sidebar-link-copy">Cerrar sesión</span></button>
        </form>
      </div>
      <?php endif; ?>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-toggle aria-label="Cerrar navegación" tabindex="-1"></button>
    <script src="assets/js/sidebar.js" defer></script>
    <?php
}

function money(float|int|string|null $value): string
{
    return '$ ' . number_format((float) $value, 0, ',', '.');
}

function workflow_review_state_label(?string $state): string
{
    return match ($state) {
        'aprobada' => 'Revisión aprobada',
        'en_revision' => 'Pendiente de aprobación',
        'requiere_informacion' => 'Requiere información',
        'borrador' => 'Revisión en borrador',
        default => 'Sin revisión técnica',
    };
}

function redirect_to(string $path, array $params = []): void
{
    $query = $params ? '?' . http_build_query($params) : '';
    header('Location: ' . $path . $query);
    exit;
}
