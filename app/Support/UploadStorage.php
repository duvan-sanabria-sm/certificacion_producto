<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/runtime.php';

function ensure_upload_directory(string $relativeDirectory): string
{
    $root = dirname(__DIR__, 2);
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);

    if (!is_dir($absolute)) {
        app_ensure_directory($absolute);
    }

    return $absolute;
}

function sanitize_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? 'archivo';
    $name = trim($name, '._-');

    return $name !== '' ? $name : 'archivo';
}

function store_uploaded_file(array $file, string $targetDirectory): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibio un archivo valido.');
    }

    $directory = ensure_upload_directory($targetDirectory);
    $original = (string) ($file['name'] ?? 'archivo');
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $baseName = pathinfo($original, PATHINFO_FILENAME);
    $safeName = sanitize_filename($baseName);
    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeName . '.' . $extension;
    $absolutePath = $directory . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('No fue posible guardar el archivo cargado.');
    }

    return [
        'original_name' => $original,
        'stored_name' => $storedName,
        'relative_path' => str_replace('\\', '/', trim($targetDirectory, '/\\') . '/' . $storedName),
        'absolute_path' => $absolutePath,
        'extension' => $extension,
        'mime_type' => $file['type'] ?? null,
        'size' => (int) ($file['size'] ?? 0),
        'sha256' => hash_file('sha256', $absolutePath) ?: null,
    ];
}
