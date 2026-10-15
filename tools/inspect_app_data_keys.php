<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/EmbeddedMatrix.php';

$data = embedded_app_data('preview (28).php');
foreach ($data as $key => $value) {
    echo $key . ': ' . (is_array($value) ? count($value) : gettype($value)) . PHP_EOL;
}
