<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';

function auth_roles(): array
{
    return [
        'administrador' => [
            'label' => 'Administrador del software',
            'description' => 'Acceso total al sistema, usuarios, configuración y todas las fases.',
            'permissions' => ['*'],
        ],
        'comercial_1' => [
            'label' => 'Comercial 1',
            'description' => 'Gestiona sus solicitudes asignadas y prepara sus pre-cotizaciones.',
            'permissions' => ['requests.view', 'requests.create', 'requests.edit', 'prequotes.view', 'prequotes.create'],
        ],
        'comercial_2' => [
            'label' => 'Comercial 2',
            'description' => 'Gestiona todas las solicitudes comerciales, pre-cotizaciones y cotización final.',
            'permissions' => ['requests.view', 'requests.create', 'requests.edit', 'requests.delete', 'prequotes.view', 'prequotes.create', 'prequotes.approve', 'final_quotes.view'],
        ],
        'coordinador_tecnico' => [
            'label' => 'Coordinador técnico',
            'description' => 'Consulta solicitudes, matrices y laboratorios; realiza la revisión técnica.',
            'permissions' => ['requests.view', 'prequotes.view', 'prequotes.edit', 'technical_review.view', 'technical_review.edit', 'matrices.view', 'laboratories.view'],
        ],
        'jefe_tecnico' => [
            'label' => 'Jefe técnico',
            'description' => 'Dirige la revisión técnica y administra matrices y laboratorios.',
            'permissions' => ['requests.view', 'prequotes.view', 'prequotes.edit', 'technical_review.view', 'technical_review.edit', 'technical_review.approve', 'final_quotes.view', 'matrices.view', 'matrices.manage', 'laboratories.view', 'laboratories.manage'],
        ],
    ];
}

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = app_ensure_directory(app_runtime_path('SERVIMETERS_SESSION_PATH', 'storage/sessions'));
    session_save_path($sessionPath);
    session_name('servimeters_auth');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => app_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

auth_start_session();

function auth_current_user(bool $refresh = false): ?array
{
    if ($refresh) {
        unset($GLOBALS['servimeters_auth_user']);
    }
    if (array_key_exists('servimeters_auth_user', $GLOBALS)) {
        return $GLOBALS['servimeters_auth_user'];
    }

    $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
    if ($userId <= 0) {
        return $GLOBALS['servimeters_auth_user'] = null;
    }

    try {
        $statement = db()->prepare(
            'SELECT u.id, u.nombre_completo, u.usuario, u.correo, u.rol, u.comercial_id,
                    u.activo, u.cambiar_password, c.nombre_completo AS comercial_nombre
             FROM usuarios u
             LEFT JOIN comerciales c ON c.id = u.comercial_id
             WHERE u.id = :id AND u.activo = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
    } catch (Throwable) {
        $user = false;
    }

    if (!$user || !isset(auth_roles()[$user['rol']])) {
        unset($_SESSION['auth_user_id']);
        return $GLOBALS['servimeters_auth_user'] = null;
    }

    return $GLOBALS['servimeters_auth_user'] = $user;
}

function auth_can(string $permission, ?array $user = null): bool
{
    $user ??= auth_current_user();
    if (!$user) {
        return false;
    }
    $permissions = auth_roles()[$user['rol']]['permissions'] ?? [];
    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function auth_role_label(string $role): string
{
    return (string) (auth_roles()[$role]['label'] ?? $role);
}

function auth_is_api_request(): bool
{
    return str_contains(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/api/');
}

function auth_root_prefix(): string
{
    return auth_is_api_request() ? '../' : '';
}

function auth_safe_next(string $next): string
{
    $next = trim($next);
    if ($next === '' || str_starts_with($next, '//') || str_starts_with($next, '\\') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $next)) {
        return '';
    }
    return $next;
}

function auth_default_home(?array $user = null): string
{
    $user ??= auth_current_user();
    foreach ([
        'requests.view' => 'index.php',
        'prequotes.view' => 'pre_cotizacion.php',
        'technical_review.view' => 'revision_tecnica.php',
        'final_quotes.view' => 'cotizacion_final.php',
        'matrices.view' => 'matrices.php',
        'laboratories.view' => 'laboratorios.php',
        'users.manage' => 'usuarios.php',
    ] as $permission => $path) {
        if (auth_can($permission, $user)) {
            return $path;
        }
    }
    return 'acceso_denegado.php';
}

function auth_require_login(): array
{
    $user = auth_current_user();
    if (!$user) {
        $next = auth_safe_next((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $url = auth_root_prefix() . 'login.php' . ($next !== '' ? '?next=' . urlencode($next) : '');
        header('Location: ' . $url);
        exit;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!empty($user['cambiar_password']) && !in_array($script, ['mi_cuenta.php', 'logout.php'], true)) {
        header('Location: ' . auth_root_prefix() . 'mi_cuenta.php?force=1');
        exit;
    }
    return $user;
}

function auth_require_permission(string $permission): array
{
    $user = auth_require_login();
    if (auth_can($permission, $user)) {
        return $user;
    }

    if (auth_is_api_request()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'No tienes permiso para realizar esta acción.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: acceso_denegado.php?permission=' . urlencode($permission));
    exit;
}

function auth_attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'Ingresa usuario y contraseña.'];
    }

    try {
        $pdo = db();
        $statement = $pdo->prepare('SELECT * FROM usuarios WHERE LOWER(usuario) = LOWER(:usuario) LIMIT 1');
        $statement->execute(['usuario' => $username]);
        $user = $statement->fetch();
    } catch (Throwable) {
        return ['ok' => false, 'message' => 'La autenticación aún no está disponible. Verifica la conexión y aplica el esquema de usuarios.'];
    }

    if (!$user || empty($user['activo'])) {
        return ['ok' => false, 'message' => 'Usuario o contraseña incorrectos.'];
    }
    if (!empty($user['bloqueado_hasta']) && strtotime((string) $user['bloqueado_hasta']) > time()) {
        return ['ok' => false, 'message' => 'La cuenta está bloqueada temporalmente por varios intentos fallidos.'];
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        $attempts = (int) ($user['intentos_fallidos'] ?? 0) + 1;
        $blockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
        $pdo->prepare('UPDATE usuarios SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueado WHERE id = :id')
            ->execute(['intentos' => $attempts >= 5 ? 0 : $attempts, 'bloqueado' => $blockedUntil, 'id' => $user['id']]);
        return ['ok' => false, 'message' => 'Usuario o contraseña incorrectos.'];
    }

    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $user['id'];
    $pdo->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id')
        ->execute(['id' => $user['id']]);
    auth_current_user(true);
    auth_log('usuarios', (string) $user['id'], 'inicio_sesion');
    return ['ok' => true, 'message' => 'Sesión iniciada.'];
}

function auth_logout(): void
{
    $user = auth_current_user();
    if ($user) {
        auth_log('usuarios', (string) $user['id'], 'cierre_sesion');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    unset($GLOBALS['servimeters_auth_user']);
}

function auth_csrf_token(): string
{
    if (empty($_SESSION['auth_csrf'])) {
        $_SESSION['auth_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['auth_csrf'];
}

function auth_verify_csrf(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(auth_csrf_token(), $token);
}

function auth_assert_csrf(): void
{
    if (!auth_verify_csrf($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('La sesión del formulario venció. Recarga la página e intenta nuevamente.');
    }
}

function auth_log(string $entity, string $entityId, string $action, array $detail = []): void
{
    $user = auth_current_user();
    try {
        db()->prepare(
            'INSERT INTO bitacora_sistema (entidad, entidad_id, accion, detalle_json, creado_por)
             VALUES (:entidad, :entidad_id, :accion, :detalle, :creado_por)'
        )->execute([
            'entidad' => $entity,
            'entidad_id' => $entityId,
            'accion' => $action,
            'detalle' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            'creado_por' => $user['usuario'] ?? null,
        ]);
    } catch (Throwable) {
        // La bitácora no debe interrumpir la operación principal.
    }
}

function auth_request_scope_sql(string $alias = 's'): array
{
    $user = auth_current_user();
    if (($user['rol'] ?? '') !== 'comercial_1') {
        return ['sql' => '1 = 1', 'params' => []];
    }
    $commercialId = (int) ($user['comercial_id'] ?? 0);
    if ($commercialId <= 0) {
        return ['sql' => '1 = 0', 'params' => []];
    }
    return ['sql' => $alias . '.comercial_id = :auth_comercial_id', 'params' => ['auth_comercial_id' => $commercialId]];
}

function auth_can_access_request(PDO $pdo, int $requestId): bool
{
    if ($requestId <= 0 || !auth_can('requests.view')) {
        return false;
    }
    $scope = auth_request_scope_sql('s');
    $statement = $pdo->prepare('SELECT COUNT(*) FROM solicitudes_servicio s WHERE s.id = :id AND ' . $scope['sql']);
    $statement->execute(array_merge(['id' => $requestId], $scope['params']));
    return (int) $statement->fetchColumn() > 0;
}
