<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Método no permitido.');
}

auth_logout();
header('Location: login.php');
exit;
