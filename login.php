<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';
require_once __DIR__ . '/app/Support/UI.php';

$next = auth_safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? ''));
$error = '';

if (auth_current_user()) {
    header('Location: ' . auth_default_home());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Intenta nuevamente.';
    } else {
        $result = auth_attempt_login((string) ($_POST['usuario'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($result['ok']) {
            $user = auth_current_user(true);
            if (!empty($user['cambiar_password'])) {
                header('Location: mi_cuenta.php?force=1');
            } else {
                header('Location: ' . ($next !== '' ? $next : auth_default_home($user)));
            }
            exit;
        }
        $error = (string) $result['message'];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingreso al sistema | Servimeters</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-brand">
      <span class="auth-logo">S</span>
      <div><strong>SERVIMETERS</strong><small>Certificación de producto</small></div>
    </section>
    <section class="auth-card">
      <p class="eyebrow">Acceso interno</p>
      <h1>Iniciar sesión</h1>
      <p>Ingresa con el perfil asignado para acceder a las fases autorizadas.</p>
      <?php if ($error !== ''): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
      <form method="post" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <label>Usuario<input name="usuario" type="text" autocomplete="username" required autofocus></label>
        <label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label>
        <button type="submit">Ingresar</button>
      </form>
      <a class="auth-public-link" href="solicitud_cliente.php">Ir al formulario público para clientes</a>
    </section>
  </main>
</body>
</html>
