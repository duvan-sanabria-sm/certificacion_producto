<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/EmbeddedMatrix.php';

foreach (embedded_matrix_summary() as $matrix) {
    echo $matrix['code'] . ': ' . $matrix['rows'] . PHP_EOL;
}
