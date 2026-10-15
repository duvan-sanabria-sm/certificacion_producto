<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/runtime.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = app_root_path() . '/storage/uploads/analisis-temp';
$retentionHours = max(1, (int) app_env('SERVIMETERS_ANALYSIS_RETENTION_HOURS', '24'));
$threshold = time() - ($retentionHours * 3600);
$removed = 0;

if (is_dir($root)) {
    foreach (new DirectoryIterator($root) as $entry) {
        if ($entry->isDot() || !$entry->isDir() || $entry->isLink() || $entry->getMTime() >= $threshold) {
            continue;
        }
        $directory = $entry->getPathname();
        foreach (new DirectoryIterator($directory) as $child) {
            if (!$child->isDot() && $child->isFile() && !$child->isLink()) {
                @unlink($child->getPathname());
            }
        }
        if (@rmdir($directory)) {
            $removed++;
        }
    }
}

echo "Directorios temporales eliminados: {$removed}" . PHP_EOL;
