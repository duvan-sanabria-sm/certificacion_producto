<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/ServiceRequestExtractor.php';

$path = __DIR__ . '/../Requisitos calidad producto/GMV-RG-20 SOLICITUD SERVICIO CERTIFICACIÓN PRODUCTO V22.xlsx';
$result = extract_service_request_from_xlsx($path);

echo json_encode([
    'fields' => $result['fields'],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
