<?php
declare(strict_types=1);

function app_root_path(): string
{
    return dirname(__DIR__);
}

function app_load_local_environment(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $path = app_root_path() . DIRECTORY_SEPARATOR . '.env.local';
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name) || getenv($name) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function app_env(string $name, ?string $default = null): ?string
{
    app_load_local_environment();
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }
    return trim((string) $value);
}

function app_env_required(string $name): string
{
    $value = app_env($name);
    if ($value === null || $value === '') {
        throw new RuntimeException('Falta configurar la variable de entorno ' . $name . '.');
    }
    return $value;
}

function app_env_bool(string $name, bool $default = false): bool
{
    $value = app_env($name);
    if ($value === null || $value === '') {
        return $default;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function app_runtime_path(string $environmentName, string $relativeDefault): string
{
    $configured = app_env($environmentName);
    if ($configured !== null && $configured !== '') {
        return rtrim($configured, '/\\');
    }
    return app_root_path() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDefault);
}

function app_ensure_directory(string $path): string
{
    if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) {
        throw new RuntimeException('No fue posible crear el directorio requerido por la aplicación.');
    }
    return $path;
}

function app_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!app_env_bool('SERVIMETERS_TRUST_PROXY', false)) {
        return false;
    }
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    return $forwardedProto === 'https';
}

date_default_timezone_set((string) app_env('SERVIMETERS_TIMEZONE', 'America/Bogota'));
