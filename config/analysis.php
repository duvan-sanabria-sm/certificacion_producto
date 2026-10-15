<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime.php';

$venvPython = PHP_OS_FAMILY === 'Windows'
    ? app_root_path() . '\\.venv\\Scripts\\python.exe'
    : app_root_path() . '/.venv/bin/python';

return [
    'python_path' => app_env('SERVIMETERS_PYTHON_PATH', $venvPython),
    'script_path' => __DIR__ . '/../python/product_analyzer.py',
    'timeout_seconds' => max(10, (int) app_env('SERVIMETERS_ANALYSIS_TIMEOUT', '90')),
];
