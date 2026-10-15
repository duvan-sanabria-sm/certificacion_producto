<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';
require_once __DIR__ . '/app/Support/UI.php';

$currentUser = auth_require_permission('users.manage');
$roles = auth_roles();
$status = (string) ($_GET['status'] ?? '');
$message = (string) ($_GET['message'] ?? '');
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!auth_verify_csrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La sesión del formulario venció.');
        }

        $id = (int) ($_POST['usuario_id'] ?? 0);
        $name = trim((string) ($_POST['nombre_completo'] ?? ''));
        $username = strtolower(trim((string) ($_POST['usuario'] ?? '')));
        $email = strtolower(trim((string) ($_POST['correo'] ?? '')));
        $role = (string) ($_POST['rol'] ?? '');
        $commercialId = (int) ($_POST['comercial_id'] ?? 0);
        $active = isset($_POST['activo']) ? 1 : 0;
        $forceChange = isset($_POST['cambiar_password']) ? 1 : 0;
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || !preg_match('/^[a-z0-9._-]{4,80}$/', $username)) {
            throw new RuntimeException('Indica un nombre y un usuario de mínimo 4 caracteres; usa letras, números, punto, guion o guion bajo.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo electrónico no es válido.');
        }
        if (!isset($roles[$role])) {
            throw new RuntimeException('Selecciona un perfil válido.');
        }
        if ($role === 'comercial_1' && $commercialId <= 0) {
            throw new RuntimeException('El perfil Comercial 1 debe estar asociado a un comercial para limitar sus solicitudes.');
        }
        if ($id === (int) $currentUser['id']) {
            $role = 'administrador';
            $active = 1;
        }
        if ($id <= 0 && $password === '') {
            throw new RuntimeException('La contraseña temporal es obligatoria para un usuario nuevo.');
        }
        if ($password !== '' && (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
            throw new RuntimeException('La contraseña debe tener mínimo 10 caracteres, letras y números.');
        }

        $pdo = db();
        if ($commercialId > 0) {
            $commercialStatement = $pdo->prepare('SELECT COUNT(*) FROM comerciales WHERE id = :id AND activo = 1');
            $commercialStatement->execute(['id' => $commercialId]);
            if ((int) $commercialStatement->fetchColumn() === 0) {
                throw new RuntimeException('El comercial asociado no está disponible.');
            }
        }

        $payload = [
            'nombre' => $name,
            'usuario' => $username,
            'correo' => $email !== '' ? $email : null,
            'rol' => $role,
            'comercial_id' => $commercialId > 0 ? $commercialId : null,
            'activo' => $active,
            'cambiar_password' => $forceChange,
        ];

        if ($id > 0) {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE id = :id');
            $exists->execute(['id' => $id]);
            if ((int) $exists->fetchColumn() === 0) {
                throw new RuntimeException('El usuario que intentas editar no existe.');
            }
            $sql = 'UPDATE usuarios SET nombre_completo = :nombre, usuario = :usuario, correo = :correo,
                    rol = :rol, comercial_id = :comercial_id, activo = :activo, cambiar_password = :cambiar_password,
                    intentos_fallidos = 0, bloqueado_hasta = NULL';
            if ($password !== '') {
                $sql .= ', password_hash = :password_hash, cambiar_password = 1';
                $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $payload['id'] = $id;
            $pdo->prepare($sql)->execute($payload);
            auth_log('usuarios', (string) $id, 'actualizar', ['rol' => $role, 'activo' => $active]);
            $savedMessage = 'Usuario actualizado correctamente.';
        } else {
            $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO usuarios (nombre_completo, usuario, correo, password_hash, rol, comercial_id, activo, cambiar_password)
                 VALUES (:nombre, :usuario, :correo, :password_hash, :rol, :comercial_id, :activo, :cambiar_password)'
            )->execute($payload);
            $id = db_last_insert_id($pdo);
            auth_log('usuarios', (string) $id, 'crear', ['rol' => $role]);
            $savedMessage = 'Usuario creado correctamente.';
        }

        header('Location: usuarios.php?status=ok&message=' . urlencode($savedMessage) . '&edit=' . $id);
        exit;
    } catch (PDOException $exception) {
        $status = 'error';
        $message = in_array((string) $exception->getCode(), ['23000', '23505'], true)
            ? 'El usuario o el correo ya están registrados.'
            : 'No fue posible guardar el usuario.';
        $editId = (int) ($_POST['usuario_id'] ?? 0);
    } catch (Throwable $exception) {
        $status = 'error';
        $message = $exception->getMessage();
        $editId = (int) ($_POST['usuario_id'] ?? 0);
    }
}

$pdo = db();
$commercials = $pdo->query('SELECT id, nombre_completo, correo FROM comerciales WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();
$users = $pdo->query(
    'SELECT u.*, c.nombre_completo AS comercial_nombre
     FROM usuarios u LEFT JOIN comerciales c ON c.id = u.comercial_id
     ORDER BY u.activo DESC, u.nombre_completo'
)->fetchAll();
$editUser = null;
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
    $statement->execute(['id' => $editId]);
    $editUser = $statement->fetch() ?: null;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Usuarios y roles</title>
  <link rel="stylesheet" href="assets/css/system.css">
</head>
<body>
  <div class="app-frame">
    <?php render_sidebar('usuarios'); ?>
    <main class="app-content">
      <header class="topbar">
        <div><p class="eyebrow">Administración</p><h1>Usuarios y roles</h1><p>Crea accesos internos y limita las fases disponibles para cada responsabilidad.</p></div>
        <a class="button secondary" href="usuarios.php">Nuevo usuario</a>
      </header>

      <?php if ($message !== ''): ?><div class="notice <?= h($status === 'ok' ? 'ok' : 'error') ?>"><?= h($message) ?></div><?php endif; ?>

      <section class="role-grid" aria-label="Perfiles disponibles">
        <?php foreach ($roles as $key => $definition): ?>
          <article class="role-card <?= $key === 'administrador' ? 'role-admin' : '' ?>">
            <strong><?= h($definition['label']) ?></strong>
            <p><?= h($definition['description']) ?></p>
          </article>
        <?php endforeach; ?>
      </section>

      <div class="management-grid">
        <section class="panel">
          <div class="panel-header"><div><h2><?= $editUser ? 'Editar usuario' : 'Crear usuario' ?></h2><div class="muted">Las contraseñas nuevas deben cambiarse en el primer ingreso.</div></div></div>
          <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= h(auth_csrf_token()) ?>">
            <input type="hidden" name="usuario_id" value="<?= h($editUser['id'] ?? '') ?>">
            <div><label>Nombre completo</label><input name="nombre_completo" value="<?= h($editUser['nombre_completo'] ?? '') ?>" required></div>
            <div><label>Usuario</label><input name="usuario" value="<?= h($editUser['usuario'] ?? '') ?>" pattern="[A-Za-z0-9._-]{4,80}" autocomplete="off" required></div>
            <div><label>Correo</label><input name="correo" type="email" value="<?= h($editUser['correo'] ?? '') ?>"></div>
            <div><label>Perfil</label><select name="rol" required <?= (int) ($editUser['id'] ?? 0) === (int) $currentUser['id'] ? 'disabled' : '' ?>><?php foreach ($roles as $key => $definition): ?><option value="<?= h($key) ?>" <?= ($editUser['rol'] ?? 'comercial_1') === $key ? 'selected' : '' ?>><?= h($definition['label']) ?></option><?php endforeach; ?></select><?php if ((int) ($editUser['id'] ?? 0) === (int) $currentUser['id']): ?><input type="hidden" name="rol" value="administrador"><?php endif; ?></div>
            <div class="full"><label>Comercial asociado <span class="muted">(obligatorio para limitar Comercial 1)</span></label><select name="comercial_id"><option value="">Sin asociación</option><?php foreach ($commercials as $commercial): ?><option value="<?= h($commercial['id']) ?>" <?= (int) ($editUser['comercial_id'] ?? 0) === (int) $commercial['id'] ? 'selected' : '' ?>><?= h($commercial['nombre_completo'] . ' - ' . $commercial['correo']) ?></option><?php endforeach; ?></select></div>
            <div class="full"><label><?= $editUser ? 'Nueva contraseña temporal (opcional)' : 'Contraseña temporal' ?></label><input name="password" type="password" minlength="10" autocomplete="new-password" <?= $editUser ? '' : 'required' ?>></div>
            <label class="check-label"><input name="activo" type="checkbox" value="1" <?= !isset($editUser['activo']) || !empty($editUser['activo']) ? 'checked' : '' ?> <?= (int) ($editUser['id'] ?? 0) === (int) $currentUser['id'] ? 'disabled' : '' ?>> Usuario activo</label>
            <?php if ((int) ($editUser['id'] ?? 0) === (int) $currentUser['id']): ?><input type="hidden" name="activo" value="1"><?php endif; ?>
            <label class="check-label"><input name="cambiar_password" type="checkbox" value="1" <?= !isset($editUser['cambiar_password']) || !empty($editUser['cambiar_password']) ? 'checked' : '' ?>> Solicitar cambio de contraseña</label>
            <div class="full actions"><button type="submit"><?= $editUser ? 'Guardar cambios' : 'Crear usuario' ?></button><?php if ($editUser): ?><a class="button ghost" href="usuarios.php">Cancelar</a><?php endif; ?></div>
          </form>
        </section>

        <section class="panel">
          <div class="panel-header"><div><h2>Usuarios registrados</h2><div class="muted"><?= h(count($users)) ?> cuenta(s) configurada(s).</div></div></div>
          <div class="user-list">
            <?php foreach ($users as $user): ?>
              <article class="user-row <?= empty($user['activo']) ? 'is-disabled' : '' ?>">
                <div><strong><?= h($user['nombre_completo']) ?></strong><span><?= h($user['usuario']) ?> · <?= h($user['correo'] ?: 'Sin correo') ?></span><?php if ($user['comercial_nombre']): ?><small>Asignación: <?= h($user['comercial_nombre']) ?></small><?php endif; ?></div>
                <div class="user-row-meta"><span class="tag"><?= h(auth_role_label((string) $user['rol'])) ?></span><small><?= !empty($user['activo']) ? 'Activo' : 'Inactivo' ?></small></div>
                <a class="button secondary" href="usuarios.php?edit=<?= h($user['id']) ?>">Editar</a>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
    </main>
  </div>
</body>
</html>
