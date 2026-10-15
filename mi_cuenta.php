<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';
require_once __DIR__ . '/app/Support/UI.php';

$user = auth_require_login();
$status = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!auth_verify_csrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario venció.');
        }
        $current = (string) ($_POST['password_actual'] ?? '');
        $new = (string) ($_POST['password_nueva'] ?? '');
        $confirm = (string) ($_POST['password_confirmacion'] ?? '');
        $statement = db()->prepare('SELECT password_hash FROM usuarios WHERE id = :id');
        $statement->execute(['id' => $user['id']]);
        $hash = (string) $statement->fetchColumn();
        if (!password_verify($current, $hash)) {
            throw new RuntimeException('La contraseña actual no es correcta.');
        }
        if (strlen($new) < 10 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            throw new RuntimeException('La nueva contraseña debe tener mínimo 10 caracteres, letras y números.');
        }
        if ($new !== $confirm) {
            throw new RuntimeException('La confirmación de la contraseña no coincide.');
        }
        db()->prepare('UPDATE usuarios SET password_hash = :hash, cambiar_password = 0 WHERE id = :id')
            ->execute(['hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => $user['id']]);
        auth_log('usuarios', (string) $user['id'], 'cambio_password');
        $user = auth_current_user(true);
        $status = 'ok';
        $message = 'Contraseña actualizada correctamente.';
    } catch (Throwable $exception) {
        $status = 'error';
        $message = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi cuenta</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('cuenta'); ?>
    <main class="app-content narrow-content">
      <header class="topbar"><div><p class="eyebrow">Seguridad</p><h1>Mi cuenta</h1><p>Actualiza la contraseña de acceso al sistema.</p></div></header>
      <?php if (!empty($_GET['force']) || !empty($user['cambiar_password'])): ?><div class="notice warn">Debes cambiar la contraseña temporal antes de continuar.</div><?php endif; ?>
      <?php if ($message !== ''): ?><div class="notice <?= h($status) ?>"><?= h($message) ?></div><?php endif; ?>
      <section class="panel account-panel">
        <div class="account-summary"><div><span>Usuario</span><strong><?= h($user['usuario']) ?></strong></div><div><span>Perfil</span><strong><?= h(auth_role_label((string) $user['rol'])) ?></strong></div></div>
        <form method="post" class="auth-form">
          <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
          <label>Contraseña actual<input name="password_actual" type="password" autocomplete="current-password" required></label>
          <label>Nueva contraseña<input name="password_nueva" type="password" autocomplete="new-password" minlength="10" required></label>
          <label>Confirmar nueva contraseña<input name="password_confirmacion" type="password" autocomplete="new-password" minlength="10" required></label>
          <button type="submit">Actualizar contraseña</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
