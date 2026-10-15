<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime.php';

return [
    'enabled' => app_env_bool('SERVIMETERS_SMTP_ENABLED', false),
    'host' => app_env('SERVIMETERS_SMTP_HOST', 'smtp.office365.com'),
    'port' => (int) app_env('SERVIMETERS_SMTP_PORT', '587'),
    'encryption' => app_env('SERVIMETERS_SMTP_ENCRYPTION', 'tls'),
    'username' => app_env('SERVIMETERS_SMTP_USERNAME', ''),
    'password' => app_env('SERVIMETERS_SMTP_PASSWORD', ''),
    'from_email' => app_env('SERVIMETERS_SMTP_FROM_EMAIL', 'analista.innovacion@servimeters.com'),
    'from_name' => app_env('SERVIMETERS_SMTP_FROM_NAME', 'Servimeters - Solicitudes de producto'),
    'timeout' => (int) app_env('SERVIMETERS_SMTP_TIMEOUT', '15'),
];
