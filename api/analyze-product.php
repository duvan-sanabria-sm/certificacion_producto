<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';
require_once __DIR__ . '/../app/Support/ProductRequestRules.php';

header('Content-Type: application/json; charset=utf-8');

function analysis_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function analysis_upload(string $key, array $extensions, int $maxBytes): ?array
{
    $file = $_FILES[$key] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No fue posible recibir uno de los archivos.');
    }
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Uno de los archivos supera el tamano permitido.');
    }
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions, true)) {
        throw new RuntimeException('Uno de los archivos tiene un formato no permitido.');
    }
    return $file;
}

function analysis_cleanup_directory(?string $directory): void
{
    if ($directory === null || !is_dir($directory)) {
        return;
    }
    $temporaryRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'analisis-temp');
    $resolved = realpath($directory);
    if ($temporaryRoot === false || $resolved === false || !str_starts_with($resolved, $temporaryRoot . DIRECTORY_SEPARATOR)) {
        return;
    }
    foreach (scandir($resolved) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $resolved . DIRECTORY_SEPARATOR . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    @rmdir($resolved);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    analysis_response(['ok' => false, 'message' => 'Metodo no permitido.'], 405);
}

$tokenDirectory = null;
try {
    $name = trim((string) ($_POST['nombre_producto'] ?? ''));
    $regulationCode = trim((string) ($_POST['reglamento_codigo'] ?? ''));
    $scheme = trim((string) ($_POST['esquema_solicitado'] ?? ''));
    $preferredCatalogId = (int) ($_POST['catalogo_id_preseleccionado'] ?? 0);
    if ($name === '') {
        throw new RuntimeException('Escribe el nombre genérico del producto.');
    }
    $image = analysis_upload('imagen_producto', ['jpg', 'jpeg', 'png', 'webp'], 12 * 1024 * 1024);
    $datasheet = analysis_upload('ficha_tecnica', ['pdf', 'docx', 'xlsx', 'xlsm', 'jpg', 'jpeg', 'png', 'webp', 'txt'], 20 * 1024 * 1024);
    if ($datasheet === null) {
        throw new RuntimeException('Adjunta la ficha técnica del producto.');
    }

    $pdo = db();
    $regulationStatement = $pdo->prepare(
        'SELECT codigo, esquemas_json FROM reglamentos_catalogo WHERE codigo = :codigo AND activo = 1 LIMIT 1'
    );
    $regulationStatement->execute(['codigo' => $regulationCode]);
    $regulation = $regulationStatement->fetch();
    $regulationSchemes = json_decode((string) ($regulation['esquemas_json'] ?? '[]'), true) ?: [];
    if (!$regulation || !in_array($scheme, $regulationSchemes, true)) {
        throw new RuntimeException('El alcance o el esquema del servicio no es valido.');
    }
    $token = bin2hex(random_bytes(16));
    $relativeDirectory = 'storage/uploads/analisis-temp/' . $token;
    $tokenDirectory = ensure_upload_directory($relativeDirectory);
    $storedImage = $image !== null ? store_uploaded_file($image, $relativeDirectory) : null;
    $storedDatasheet = $datasheet !== null ? store_uploaded_file($datasheet, $relativeDirectory) : null;

    $catalogStatement = $pdo->prepare(
        'SELECT id, reglamento_codigo, reglamento_nombre, producto_nombre, familia_nombre,
                subfamilia_nombre, articulo, numeral, codigo_nace, documento_normativo, documentos_json, esquemas_json
         FROM catalogo_productos_reglamentarios
         WHERE activo = 1 AND reglamento_codigo = :reglamento'
    );
    $catalogStatement->execute(['reglamento' => $regulationCode]);
    $rows = $catalogStatement->fetchAll();
    foreach ($rows as &$row) {
        $row['documentos'] = json_decode((string) ($row['documentos_json'] ?? '[]'), true) ?: [];
        $row['esquemas'] = json_decode((string) ($row['esquemas_json'] ?? '[]'), true) ?: [];
        $scopeClassification = product_scope_classification_for(
            (string) ($row['reglamento_codigo'] ?? ''),
            (string) ($row['familia_nombre'] ?? ''),
            (string) ($row['producto_nombre'] ?? '')
        );
        if ($scopeClassification) {
            $scopeClassification['opciones'] = product_scope_recommended_options($scopeClassification);
        }
        $row['subfamilia_referencias'] = $scopeClassification
            ? array_values(array_map(
                static fn(array $option): string => (string) ($option['etiqueta'] ?? ''),
                (array) ($scopeClassification['opciones'] ?? [])
            ))
            : [];
        unset($row['documentos_json'], $row['esquemas_json']);
    }
    unset($row);
    $rows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => in_array($scheme, (array) ($row['esquemas'] ?? []), true)
    ));
    if (!$rows) {
        throw new RuntimeException('No hay productos compatibles con el alcance y el esquema seleccionados.');
    }
    if ($preferredCatalogId > 0 && !array_filter($rows, static fn(array $row): bool => (int) $row['id'] === $preferredCatalogId)) {
        throw new RuntimeException('La categoría preseleccionada no corresponde al servicio.');
    }

    $catalogPath = $tokenDirectory . DIRECTORY_SEPARATOR . 'catalog.json';
    file_put_contents($catalogPath, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $config = require __DIR__ . '/../config/analysis.php';
    if (!is_file((string) $config['python_path']) || !is_file((string) $config['script_path'])) {
        throw new RuntimeException('El analizador Python no esta disponible en este equipo.');
    }

    $command = [
        (string) $config['python_path'], '-X', 'utf8', (string) $config['script_path'],
        '--catalog', $catalogPath,
        '--name', $name,
    ];
    if ($storedImage !== null) {
        $command[] = '--image';
        $command[] = $storedImage['absolute_path'];
    }
    if ($storedDatasheet !== null) {
        $command[] = '--datasheet';
        $command[] = $storedDatasheet['absolute_path'];
    }
    if ($preferredCatalogId > 0) {
        $command[] = '--preferred-id';
        $command[] = (string) $preferredCatalogId;
    }
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        throw new RuntimeException('No fue posible iniciar el analizador Python.');
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = '';
    $stderr = '';
    $startedAt = microtime(true);
    do {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if ((microtime(true) - $startedAt) > (int) $config['timeout_seconds']) {
            proc_terminate($process);
            throw new RuntimeException('El análisis excedió el tiempo permitido.');
        }
        usleep(100000);
    } while (true);
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $analysis = json_decode(trim($stdout), true);
    if (!is_array($analysis) || empty($analysis['ok'])) {
        $detail = trim($stderr) !== '' ? trim($stderr) : trim($stdout);
        throw new RuntimeException(
            'Python no pudo completar el análisis (código ' . $exitCode . '). ' . mb_substr($detail, 0, 800)
        );
    }
    if ($preferredCatalogId > 0) {
        $analysis['mode'] = 'manual_assisted';
    }

    $manifest = [
        'token' => $token,
        'created_at' => date(DATE_ATOM),
        'name' => $name,
        'regulation_code' => $regulationCode,
        'scheme' => $scheme,
        'image' => $storedImage,
        'datasheet' => $storedDatasheet,
        'accredited_catalog_id' => $preferredCatalogId ?: null,
        'analysis' => $analysis,
        'python_exit_code' => $exitCode,
    ];
    file_put_contents(
        $tokenDirectory . DIRECTORY_SEPARATOR . 'manifest.json',
        json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );

    analysis_response(['ok' => true, 'token' => $token, 'analysis' => $analysis]);
} catch (Throwable $exception) {
    analysis_cleanup_directory($tokenDirectory);
    analysis_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
