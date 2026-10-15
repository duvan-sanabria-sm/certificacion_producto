<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';
require_once __DIR__ . '/app/Support/UI.php';

$user = auth_require_login();
http_response_code(403);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso restringido</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body class="auth-page">
  <main class="auth-shell compact-shell">
    <section class="auth-card">
      <p class="eyebrow">Acceso restringido</p>
      <h1>Tu perfil no tiene este permiso</h1>
      <p>Ingresaste como <strong><?= h(auth_role_label((string) $user['rol'])) ?></strong>. Puedes regresar a una sección habilitada o consultar al administrador.</p>
      <a class="button" href="<?= h(auth_default_home($user)) ?>">Volver al sistema</a>
    </section>
  </main>
</body>
</html>
