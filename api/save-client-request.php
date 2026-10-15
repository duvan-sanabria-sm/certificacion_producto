<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/UploadStorage.php';
require_once __DIR__ . '/../app/Support/SmtpMailer.php';
require_once __DIR__ . '/../app/Support/CertificationServiceRules.php';
require_once __DIR__ . '/../app/Support/ProductRequestRules.php';

function redirect_client_request(string $status, array $params = []): void
{
    header('Location: ../solicitud_cliente.php?' . http_build_query(array_merge(['status' => $status], $params)));
    exit;
}

function client_post(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function client_array(string $key): array
{
    $value = $_POST[$key] ?? [];
    return is_array($value) ? array_values($value) : [];
}

function product_manifest(string $token): array
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        throw new RuntimeException('Uno de los análisis de producto no es válido.');
    }
    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'analisis-temp' . DIRECTORY_SEPARATOR . $token;
    $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.json';
    if (!is_file($manifestPath)) {
        throw new RuntimeException('El análisis del producto venció. Vuelve a analizar sus archivos.');
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    if (!is_array($manifest) || ($manifest['token'] ?? '') !== $token) {
        throw new RuntimeException('No fue posible validar el análisis del producto.');
    }
    $manifest['_directory'] = $directory;
    return $manifest;
}

function posted_product_field(string $key, int $index, string $fallback = ''): string
{
    $values = client_array($key);
    $value = trim((string) ($values[$index] ?? ''));
    return $value !== '' ? $value : trim($fallback);
}

function posted_product_technical_fields(int $index, array $fallback): array
{
    $encoded = client_array('producto_especificaciones_json');
    $submitted = json_decode((string) ($encoded[$index] ?? '{}'), true);
    $submitted = is_array($submitted) ? $submitted : [];
    $result = [];
    foreach (product_technical_field_keys() as $key) {
        $value = $submitted[$key] ?? $fallback[$key] ?? '';
        $result[$key] = is_scalar($value) ? mb_substr(trim((string) $value), 0, 1200) : '';
    }
    return $result;
}

function copy_analysis_file(array $stored, string $category, int $requestId, int $productIndex, string $createdBy, PDO $pdo): string
{
    $source = (string) ($stored['absolute_path'] ?? '');
    if (!is_file($source)) {
        throw new RuntimeException('No se encontro uno de los archivos analizados.');
    }
    $targetDirectory = ensure_upload_directory('storage/uploads/solicitudes');
    $extension = strtolower((string) ($stored['extension'] ?? pathinfo($source, PATHINFO_EXTENSION)));
    $base = sanitize_filename(pathinfo((string) ($stored['original_name'] ?? 'archivo'), PATHINFO_FILENAME));
    $targetName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $base . '.' . $extension;
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $targetName;
    if (!copy($source, $targetPath)) {
        throw new RuntimeException('No fue posible conservar uno de los archivos del producto.');
    }
    $relativePath = 'storage/uploads/solicitudes/' . $targetName;
    $fileStatement = $pdo->prepare(
        'INSERT INTO archivos_cargados
          (tipo_archivo, nombre_original, nombre_guardado, ruta_relativa, extension, mime_type,
           tamano_bytes, hash_sha256, estado, mensaje_validacion, creado_por)
         VALUES
          (:tipo, :original, :guardado, :ruta, :extension, :mime, :tamano, :hash, :estado, :mensaje, :creado_por)'
    );
    $fileStatement->execute([
        'tipo' => 'anexo',
        'original' => (string) ($stored['original_name'] ?? $targetName),
        'guardado' => $targetName,
        'ruta' => $relativePath,
        'extension' => $extension,
        'mime' => (string) ($stored['mime_type'] ?? ''),
        'tamano' => filesize($targetPath) ?: 0,
        'hash' => hash_file('sha256', $targetPath) ?: null,
        'estado' => 'procesado',
        'mensaje' => 'Archivo obligatorio validado por el analizador de producto.',
        'creado_por' => $createdBy,
    ]);
    $fileId = db_last_insert_id($pdo);
    $pdo->prepare(
        'INSERT INTO solicitud_adjuntos (solicitud_servicio_id, archivo_cargado_id, categoria)
         VALUES (:solicitud_id, :archivo_id, :categoria)'
    )->execute([
        'solicitud_id' => $requestId,
        'archivo_id' => $fileId,
        'categoria' => $category . '_producto_' . ($productIndex + 1),
    ]);
    return $targetPath;
}

function client_uploaded_files(string $key): array
{
    $source = $_FILES[$key] ?? null;
    if (!is_array($source) || !isset($source['error'])) {
        return [];
    }
    if (!is_array($source['error'])) {
        return (int) $source['error'] === UPLOAD_ERR_NO_FILE ? [] : [$source];
    }

    $files = [];
    foreach ($source['error'] as $index => $error) {
        if ((int) $error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => $source['name'][$index] ?? '',
            'type' => $source['type'][$index] ?? '',
            'tmp_name' => $source['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => $source['size'][$index] ?? 0,
        ];
    }
    return $files;
}

function validate_client_support_file(array $file, array $allowedExtensions, int $maxBytes, string $label): void
{
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No fue posible recibir el archivo de ' . $label . '.');
    }
    if ((int) ($file['size'] ?? 0) <= 0 || (int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('El archivo de ' . $label . ' está vacío o supera el tamaño permitido.');
    }
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('El archivo de ' . $label . ' tiene un formato no permitido.');
    }
}

function store_client_support_file(
    array $file,
    string $category,
    string $message,
    int $requestId,
    string $createdBy,
    PDO $pdo
): string {
    $stored = store_uploaded_file($file, 'storage/uploads/solicitudes');
    $fileStatement = $pdo->prepare(
        'INSERT INTO archivos_cargados
          (tipo_archivo, nombre_original, nombre_guardado, ruta_relativa, extension, mime_type,
           tamano_bytes, hash_sha256, estado, mensaje_validacion, creado_por)
         VALUES
          (:tipo, :original, :guardado, :ruta, :extension, :mime, :tamano, :hash, :estado, :mensaje, :creado_por)'
    );
    $fileStatement->execute([
        'tipo' => 'anexo',
        'original' => $stored['original_name'],
        'guardado' => $stored['stored_name'],
        'ruta' => $stored['relative_path'],
        'extension' => $stored['extension'],
        'mime' => $stored['mime_type'],
        'tamano' => $stored['size'],
        'hash' => $stored['sha256'],
        'estado' => 'procesado',
        'mensaje' => $message,
        'creado_por' => $createdBy,
    ]);
    $fileId = db_last_insert_id($pdo);
    $pdo->prepare(
        'INSERT INTO solicitud_adjuntos (solicitud_servicio_id, archivo_cargado_id, categoria)
         VALUES (:solicitud_id, :archivo_id, :categoria)'
    )->execute([
        'solicitud_id' => $requestId,
        'archivo_id' => $fileId,
        'categoria' => $category,
    ]);
    return (string) $stored['absolute_path'];
}

function cleanup_product_manifest(array $manifest): void
{
    $directory = realpath((string) ($manifest['_directory'] ?? ''));
    $temporaryRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'analisis-temp');
    if ($directory === false || $temporaryRoot === false || !str_starts_with($directory, $temporaryRoot . DIRECTORY_SEPARATOR)) {
        return;
    }
    $paths = [
        $manifest['image']['absolute_path'] ?? '',
        $manifest['datasheet']['absolute_path'] ?? '',
        $directory . DIRECTORY_SEPARATOR . 'catalog.json',
        $directory . DIRECTORY_SEPARATOR . 'manifest.json',
    ];
    foreach ($paths as $path) {
        $path = (string) $path;
        if ($path !== '' && is_file($path) && str_starts_with(realpath($path) ?: '', $directory . DIRECTORY_SEPARATOR)) {
            @unlink($path);
        }
    }
    @rmdir($directory);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_client_request('error', ['message' => 'Metodo no permitido.']);
}

$copiedFiles = [];
try {
    $clientName = client_post('cliente_nombre');
    $documentType = client_post('tipo_documento') ?: 'NIT';
    $documentNumber = client_post('numero_documento');
    $address = client_post('direccion');
    $city = client_post('ciudad');
    $country = client_post('pais') ?: 'Colombia';
    $contactName = client_post('contacto_nombre');
    $contactRole = client_post('contacto_cargo');
    $contactEmail = client_post('contacto_correo');
    $contactPhone = client_post('contacto_telefono');
    $purpose = client_post('proposito_servicio');
    $requestReason = client_post('motivo_servicio');
    $isoStatus = client_post('iso_9001_vigente');
    $remoteTechnologyValue = client_post('medios_tecnologicos_remotos');
    $hasRemoteTechnology = $remoteTechnologyValue === '1';
    $regulationCode = client_post('reglamento_codigo');
    $requestedScheme = client_post('esquema_solicitado');
    $attendedByCommercial = client_post('atendido_comercial') === '1';
    $catalogIds = array_map('intval', client_array('producto_catalogo_id'));
    $tokens = array_map('strval', client_array('producto_analisis_token'));
    $schemes = array_map('strval', client_array('producto_esquema'));

    if ($clientName === '' || $documentNumber === '' || $contactName === '' || $contactPhone === '' || $contactRole === '') {
        throw new RuntimeException('Completa la información obligatoria del solicitante.');
    }
    if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('El correo del contacto no es valido.');
    }
    if (client_post('tiene_representante') === '1') {
        if (client_post('representante_nombre') === '' || client_post('representante_nit') === ''
            || client_post('representante_telefono') === ''
            || !filter_var(client_post('representante_correo'), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Completa los datos obligatorios de la organizacion representante.');
        }
    }
    if ($purpose === '' || $requestReason === '' || $isoStatus === '' || $regulationCode === '' || $requestedScheme === '') {
        throw new RuntimeException('Completa la información del servicio.');
    }
    if ($isoStatus === 'certificacion_vigente'
        && (client_post('vigencia_iso_9001') === '' || client_post('numero_certificado_vigente') === '')) {
        throw new RuntimeException('La vigencia ISO 9001 y el número de certificado vigente son obligatorios.');
    }
    $isoExpiryWarning = '';
    if ($isoStatus === 'certificacion_vigente') {
        $isoExpiryValue = client_post('vigencia_iso_9001');
        $isoExpiry = DateTimeImmutable::createFromFormat('!Y-m-d', $isoExpiryValue);
        if (!$isoExpiry || $isoExpiry->format('Y-m-d') !== $isoExpiryValue) {
            throw new RuntimeException('La fecha de vigencia ISO 9001 no es válida.');
        }
        $today = new DateTimeImmutable('today');
        if ($isoExpiry < $today) {
            throw new RuntimeException('El certificado ISO 9001 está vencido. Registra una vigencia válida o cambia el estado del sistema de gestión.');
        }
        if ($isoExpiry <= $today->modify('+3 months')) {
            $daysRemaining = (int) $today->diff($isoExpiry)->format('%a');
            $isoExpiryWarning = sprintf(
                'El certificado ISO 9001 vence el %s, en %d día(s). El proceso puede tardar entre dos y tres meses; se recomienda renovar el certificado para evitar demoras o inconvenientes.',
                $isoExpiry->format('d/m/Y'),
                $daysRemaining
            );
        }
    }
    if (!in_array($remoteTechnologyValue, ['0', '1'], true)) {
        throw new RuntimeException('Indica si cuentas o no con medios tecnológicos para realizar evaluaciones remotas.');
    }
    if (!$catalogIds || count($catalogIds) !== count($tokens) || count($catalogIds) !== count($schemes)) {
        throw new RuntimeException('Agrega al menos un producto analizado correctamente.');
    }
    if (count($catalogIds) > 20) {
        throw new RuntimeException('La solicitud supera el maximo de 20 productos.');
    }
    if (client_post('reviso_politica_tratamiento') !== '1') {
        throw new RuntimeException('Debes confirmar que revisaste la política de tratamiento de información.');
    }
    if (client_post('acepta_tratamiento_datos') !== '1') {
        throw new RuntimeException('Debes aceptar la politica de tratamiento de datos.');
    }

    $isoCertificateFiles = client_uploaded_files('certificado_iso');
    $testReportFiles = client_uploaded_files('test_reports');
    if (count($isoCertificateFiles) > 1) {
        throw new RuntimeException('Adjunta un solo certificado ISO 9001 en esta etapa.');
    }
    if (count($testReportFiles) > 8) {
        throw new RuntimeException('Puedes adjuntar máximo ocho Test Reports por solicitud.');
    }
    foreach ($isoCertificateFiles as $file) {
        validate_client_support_file($file, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 20 * 1024 * 1024, 'certificado ISO 9001');
    }
    foreach ($testReportFiles as $file) {
        validate_client_support_file($file, ['pdf', 'docx', 'xlsx', 'xlsm', 'jpg', 'jpeg', 'png', 'webp'], 30 * 1024 * 1024, 'Test Report');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $regulationStatement = $pdo->prepare(
        'SELECT codigo, nombre, esquemas_json FROM reglamentos_catalogo WHERE codigo = :codigo AND activo = 1 LIMIT 1'
    );
    $regulationStatement->execute(['codigo' => $regulationCode]);
    $selectedRegulation = $regulationStatement->fetch();
    $regulationSchemes = json_decode((string) ($selectedRegulation['esquemas_json'] ?? '[]'), true) ?: [];
    if (!$selectedRegulation || !in_array($requestedScheme, $regulationSchemes, true)) {
        throw new RuntimeException('El alcance o el esquema seleccionado ya no esta disponible.');
    }
    $serviceRules = certification_service_rules();
    if (!isset($serviceRules['iso_statuses'][$isoStatus])) {
        throw new RuntimeException('Selecciona un estado ISO 9001 valido.');
    }
    $allowedPurposes = certification_purposes_for_scheme($requestedScheme);
    if (!in_array($purpose, $allowedPurposes, true)) {
        throw new RuntimeException('El proposito no corresponde al esquema seleccionado.');
    }
    $schemeRecommendation = certification_recommendation_for($isoStatus, $requestedScheme);
    if ($schemeRecommendation === null) {
        throw new RuntimeException('No existe una recomendacion para la combinacion de esquema y estado ISO seleccionada.');
    }
    if (!empty($schemeRecommendation['optional']) && client_post('acepta_alerta_esquema') !== '1') {
        throw new RuntimeException('Confirma la alerta técnica o reevalúa la solicitud con un esquema inferior.');
    }
    $placeholders = implode(',', array_fill(0, count($catalogIds), '?'));
    $catalogStatement = $pdo->prepare(
        "SELECT * FROM catalogo_productos_reglamentarios WHERE activo = 1 AND id IN ({$placeholders})"
    );
    $catalogStatement->execute($catalogIds);
    $catalogRows = [];
    foreach ($catalogStatement->fetchAll() as $row) {
        $catalogRows[(int) $row['id']] = $row;
    }
    if (count($catalogRows) !== count(array_unique($catalogIds))) {
        throw new RuntimeException('Uno de los productos seleccionados ya no esta activo en el alcance.');
    }

    if ($attendedByCommercial) {
        $commercialStatement = $pdo->prepare(
            "SELECT id, nombre_completo, correo, rol FROM comerciales
             WHERE id = :id AND activo = 1 AND rol = 'comercial'"
        );
        $commercialStatement->execute(['id' => (int) client_post('comercial_id')]);
        $commercial = $commercialStatement->fetch();
        if (!$commercial) {
            throw new RuntimeException('Selecciona el comercial que atendio la solicitud.');
        }
    } else {
        $commercial = $pdo->query(
            "SELECT id, nombre_completo, correo, rol FROM comerciales
             WHERE activo = 1 AND rol = 'jefe_comercial' ORDER BY id LIMIT 1"
        )->fetch();
        if (!$commercial) {
            throw new RuntimeException('No existe un jefe comercial activo para asignar la solicitud.');
        }
    }

    $products = [];
    foreach ($catalogIds as $index => $catalogId) {
        $catalog = $catalogRows[$catalogId];
        if ((string) $catalog['reglamento_codigo'] !== $regulationCode) {
            throw new RuntimeException('Uno de los productos no pertenece al alcance seleccionado.');
        }
        $manifest = product_manifest($tokens[$index]);
        if (empty($manifest['datasheet'])) {
            throw new RuntimeException('Cada producto debe tener una ficha técnica.');
        }
        if (($manifest['regulation_code'] ?? '') !== $regulationCode || ($manifest['scheme'] ?? '') !== $requestedScheme) {
            throw new RuntimeException('El análisis de un producto corresponde a otro servicio.');
        }
        $analysis = (array) ($manifest['analysis'] ?? []);
        $candidateIds = array_map(
            static fn(array $candidate): int => (int) ($candidate['id'] ?? 0),
            (array) ($analysis['candidates'] ?? [])
        );
        if (!in_array($catalogId, $candidateIds, true)) {
            throw new RuntimeException('La categoría elegida no pertenece al resultado del análisis.');
        }
        $allowedSchemes = json_decode((string) ($catalog['esquemas_json'] ?? '[]'), true) ?: [];
        $scheme = trim($schemes[$index]);
        if ($scheme !== $requestedScheme || !in_array($scheme, $allowedSchemes, true)) {
            throw new RuntimeException('El esquema elegido no corresponde al reglamento del producto.');
        }
        $fields = (array) ($analysis['fields'] ?? []);
        $technicalFields = posted_product_technical_fields($index, $fields);
        $missingTechnicalFields = [];
        foreach (product_required_technical_fields_for_regulation($regulationCode) as $definition) {
            $key = (string) ($definition['key'] ?? '');
            if ($key !== '' && trim((string) ($technicalFields[$key] ?? '')) === '') {
                $missingTechnicalFields[] = (string) ($definition['label'] ?? $key);
            }
        }
        if ($missingTechnicalFields) {
            throw new RuntimeException(
                'Completa la información técnica obligatoria del producto: ' . implode(', ', $missingTechnicalFields) . '.'
            );
        }
        $submittedQuantity = posted_product_field('producto_cantidad', $index, '1');
        if (str_starts_with(certification_scheme_key($scheme), '1B')) {
            if (trim((string) ($technicalFields['lot_serial'] ?? '')) === ''
                || (float) str_replace(',', '.', $submittedQuantity) <= 0) {
                throw new RuntimeException('El número de lote y la cantidad de productos son obligatorios para el esquema 1B.');
            }
        }
        $standardFallback = trim((string) ($technicalFields['technical_standard'] ?? ''));
        if ($standardFallback === '') {
            $standardFallback = (string) ($catalog['documento_normativo'] ?? '');
        }
        $products[] = [
            'catalog' => $catalog,
            'manifest' => $manifest,
            'analysis' => $analysis,
            'scheme' => $scheme,
            'client_name' => posted_product_field('producto_nombre_cliente', $index, (string) ($fields['generic_name'] ?? '')),
            'reference' => posted_product_field('producto_referencia', $index, (string) ($technicalFields['model'] ?? '')),
            'brand' => posted_product_field('producto_marca', $index, (string) ($technicalFields['brand'] ?? '')),
            'quantity' => $submittedQuantity,
            'manufacturer' => posted_product_field('producto_fabricante', $index, (string) ($technicalFields['manufacturer'] ?? '')),
            'manufacturer_address' => (string) ($technicalFields['manufacturer_address'] ?? ''),
            'manufacturer_country' => posted_product_field('producto_pais_fabricante', $index, (string) ($technicalFields['country'] ?? '')),
            'standard' => posted_product_field('producto_norma_tecnica', $index, $standardFallback),
            'material' => posted_product_field('producto_material', $index, (string) ($technicalFields['material'] ?? '')),
            'voltage' => posted_product_field('producto_tension', $index, (string) ($technicalFields['voltage'] ?? '')),
            'current' => posted_product_field('producto_corriente', $index, (string) ($technicalFields['current'] ?? '')),
            'power' => posted_product_field('producto_potencia', $index, (string) ($technicalFields['power'] ?? '')),
            'frequency' => posted_product_field('producto_frecuencia', $index, (string) ($technicalFields['frequency'] ?? '')),
            'ip' => posted_product_field('producto_ip', $index, (string) ($technicalFields['ip_rating'] ?? '')),
            'description' => posted_product_field('producto_descripcion', $index, (string) ($technicalFields['description'] ?? '')),
            'business_use' => posted_product_field('producto_uso', $index, (string) ($technicalFields['business_use'] ?? '')),
            'technical_fields' => $technicalFields,
        ];
    }

    $clientSql = db_is_postgres($pdo)
        ? 'INSERT INTO clientes
            (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo,
             contacto_telefono, direccion, ciudad, pais)
           VALUES
            (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo,
             :contacto_telefono, :direccion, :ciudad, :pais)
           ON CONFLICT (tipo_documento, numero_documento) DO UPDATE SET
             razon_social = EXCLUDED.razon_social, contacto_nombre = EXCLUDED.contacto_nombre,
             contacto_correo = EXCLUDED.contacto_correo, contacto_telefono = EXCLUDED.contacto_telefono,
             direccion = EXCLUDED.direccion, ciudad = EXCLUDED.ciudad, pais = EXCLUDED.pais
           RETURNING id'
        : 'INSERT INTO clientes
            (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo,
             contacto_telefono, direccion, ciudad, pais)
           VALUES
            (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo,
             :contacto_telefono, :direccion, :ciudad, :pais)
           ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), contacto_nombre = VALUES(contacto_nombre),
             contacto_correo = VALUES(contacto_correo), contacto_telefono = VALUES(contacto_telefono),
             direccion = VALUES(direccion), ciudad = VALUES(ciudad), pais = VALUES(pais), id = LAST_INSERT_ID(id)';
    $clientStatement = $pdo->prepare($clientSql);
    $clientStatement->execute([
        'tipo_documento' => $documentType,
        'numero_documento' => $documentNumber,
        'razon_social' => $clientName,
        'contacto_nombre' => $contactName,
        'contacto_correo' => $contactEmail,
        'contacto_telefono' => $contactPhone,
        'direccion' => $address ?: null,
        'ciudad' => $city ?: null,
        'pais' => $country,
    ]);
    $clientId = db_is_postgres($pdo) ? (int) $clientStatement->fetchColumn() : db_last_insert_id($pdo);

    $firstCatalog = $products[0]['catalog'];
    $lineStatement = $pdo->prepare('SELECT id FROM lineas_producto WHERE codigo = :codigo AND activa = 1 LIMIT 1');
    $lineStatement->execute(['codigo' => $firstCatalog['reglamento_codigo']]);
    $lineId = (int) ($lineStatement->fetchColumn() ?: 0);
    $regulationNames = array_values(array_unique(array_map(
        static fn(array $product): string => (string) $product['catalog']['reglamento_nombre'],
        $products
    )));
    $preliminaryData = [
        'origen' => 'solicitud_web_cliente_asistida',
        'contacto_cargo' => $contactRole,
        'representante' => [
            'aplica' => client_post('tiene_representante') === '1',
            'nombre' => client_post('representante_nombre'),
            'nit' => client_post('representante_nit'),
            'direccion' => client_post('representante_direccion'),
            'correo' => client_post('representante_correo'),
            'telefono' => client_post('representante_telefono'),
        ],
        'servicio' => [
            'reglamento_codigo' => $regulationCode,
            'reglamento' => (string) $selectedRegulation['nombre'],
            'proposito' => $purpose,
            'motivo' => $requestReason,
            'iso_9001_estado' => $isoStatus,
            'iso_9001_vigente' => certification_iso_status_label($isoStatus),
            'vigencia_iso_9001' => client_post('vigencia_iso_9001'),
            'numero_certificado_vigente' => client_post('numero_certificado_vigente'),
            'alerta_vigencia_iso_9001' => $isoExpiryWarning,
            'medios_tecnologicos_remotos' => $hasRemoteTechnology,
            'reglamentos' => $regulationNames,
            'esquema' => $requestedScheme,
            'esquemas' => [$requestedScheme],
            'esquema_recomendado' => (string) $schemeRecommendation['recommended'],
            'esquemas_opcionales' => (string) $schemeRecommendation['optional'],
            'alerta_esquema_aceptada' => client_post('acepta_alerta_esquema') === '1',
        ],
        'documentos_aportados' => array_values(array_unique(array_merge(
            array_merge(...array_map(
                static function (array $product): array {
                    $documents = [];
                    if (!empty($product['manifest']['image'])) {
                        $documents[] = 'Fotografia de producto';
                    }
                    if (!empty($product['manifest']['datasheet'])) {
                        $documents[] = 'Ficha tecnica';
                    }
                    return $documents;
                },
                $products
            )),
            $isoCertificateFiles ? ['Certificado ISO 9001'] : [],
            $testReportFiles ? ['Test Reports'] : []
        ))),
        'tratamiento_datos' => [
            'politica_revisada' => true,
            'autorizacion_aceptada' => true,
            'url' => 'https://www.servimeters.com/politica-tratamiento-informacion',
        ],
        'comercial_asignado' => [
            'atendido_previamente' => $attendedByCommercial,
            'id' => (int) $commercial['id'],
            'nombre' => (string) $commercial['nombre_completo'],
            'correo' => (string) $commercial['correo'],
        ],
    ];

    $temporaryCode = 'WEB-' . strtoupper(bin2hex(random_bytes(5)));
    $requestStatement = $pdo->prepare(
        'INSERT INTO solicitudes_servicio
          (cliente_id, linea_producto_id, comercial_id, canal_entrada, atendido_por_comercial,
           codigo_solicitud, fecha_solicitud, servicio_solicitado, producto, referencia_producto,
           cantidad_muestras, reglamento_norma, estado, datos_preliminares_json, observaciones, creado_por)
         VALUES
          (:cliente_id, :linea_producto_id, :comercial_id, :canal, :atendido, :codigo, CURRENT_DATE,
           :servicio, :producto, :referencia, :cantidad, :reglamento, :estado, :datos, :observaciones, :creado_por)'
    );
    $requestObservations = client_post('observaciones');
    if ($isoExpiryWarning !== '') {
        $requestObservations = trim($requestObservations . ($requestObservations !== '' ? "\n\n" : '') . '[Alerta ISO 9001] ' . $isoExpiryWarning);
    }
    $requestStatement->execute([
        'cliente_id' => $clientId,
        'linea_producto_id' => $lineId ?: null,
        'comercial_id' => (int) $commercial['id'],
        'canal' => 'web_cliente_asistida',
        'atendido' => $attendedByCommercial ? 1 : 0,
        'codigo' => $temporaryCode,
        'servicio' => 'Certificacion de producto - ' . $purpose,
        'producto' => (string) $firstCatalog['producto_nombre'] . (count($products) > 1 ? ' y ' . (count($products) - 1) . ' adicional(es)' : ''),
        'referencia' => $products[0]['reference'] ?: null,
        'cantidad' => implode(', ', array_column($products, 'quantity')),
        'reglamento' => implode(' | ', $regulationNames),
        'estado' => 'preliminar',
        'datos' => json_encode($preliminaryData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'observaciones' => $requestObservations ?: null,
        'creado_por' => $contactName,
    ]);
    $requestId = db_last_insert_id($pdo);
    $requestCode = sprintf('SS-%s-%06d', date('Y'), $requestId);
    $pdo->prepare('UPDATE solicitudes_servicio SET codigo_solicitud = :codigo WHERE id = :id')
        ->execute(['codigo' => $requestCode, 'id' => $requestId]);

    $productStatement = $pdo->prepare(
        'INSERT INTO solicitud_productos
          (solicitud_servicio_id, catalogo_producto_id, reglamento_codigo, producto, familia, subfamilia, subfamilia_tecnica, codigo_nace,
           referencia_modelo, marca, cantidad, fabricante, norma_tecnica, esquema_solicitado,
           analisis_json, especificaciones_json)
         VALUES
          (:solicitud, :catalogo, :reglamento, :producto, :familia, :subfamilia, :subfamilia_tecnica, :nace, :referencia, :marca,
           :cantidad, :fabricante, :norma, :esquema, :analisis, :especificaciones)'
    );
    $factoryStatement = $pdo->prepare(
        'INSERT INTO solicitud_fabricas (solicitud_servicio_id, nombre, pais, direccion)
         VALUES (:solicitud, :nombre, :pais, :direccion)'
    );
    $factories = [];
    foreach ($products as $index => $product) {
        $catalog = $product['catalog'];
        $productStatement->execute([
            'solicitud' => $requestId,
            'catalogo' => (int) $catalog['id'],
            'reglamento' => $catalog['reglamento_codigo'],
            'producto' => $catalog['producto_nombre'],
            'familia' => $catalog['familia_nombre'] ?: null,
            'subfamilia' => $catalog['subfamilia_nombre'] ?: null,
            'subfamilia_tecnica' => ($product['technical_fields']['subfamily_detail']
                ?? $product['technical_fields']['classification_subfamily'] ?? '') ?: null,
            'nace' => $catalog['codigo_nace'] ?: null,
            'referencia' => $product['reference'] ?: null,
            'marca' => $product['brand'] ?: null,
            'cantidad' => $product['quantity'] ?: null,
            'fabricante' => $product['manufacturer'] ?: null,
            'norma' => $product['standard'] ?: null,
            'esquema' => $product['scheme'],
            'analisis' => json_encode($product['analysis'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'especificaciones' => json_encode([
                'nombre_cliente' => $product['client_name'],
                'material' => $product['material'],
                'tension_nominal' => $product['voltage'],
                'corriente_nominal' => $product['current'],
                'potencia' => $product['power'],
                'frecuencia' => $product['frequency'],
                'grado_proteccion_ip' => $product['ip'],
                'descripcion_tecnica' => $product['description'],
                'uso_negocio' => $product['business_use'],
                'pais_fabricante' => $product['manufacturer_country'],
                'subfamilia_detalle' => $product['technical_fields']['subfamily_detail']
                    ?? $product['technical_fields']['classification_subfamily'] ?? '',
                'codigo_nace' => $catalog['codigo_nace'] ?? '',
                'campos_tecnicos' => $product['technical_fields'],
                'articulo_general' => $catalog['articulo'] ?? '',
                'subnumeral' => $catalog['numeral'] ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        if ($product['manufacturer'] !== '' && !isset($factories[$product['manufacturer']])) {
            $factoryStatement->execute([
                'solicitud' => $requestId,
                'nombre' => $product['manufacturer'],
                'pais' => $product['manufacturer_country'] ?: null,
                'direccion' => $product['manufacturer_address'] ?: null,
            ]);
            $factories[$product['manufacturer']] = true;
        }
        if (!empty($product['manifest']['image'])) {
            $copiedFiles[] = copy_analysis_file($product['manifest']['image'], 'imagen', $requestId, $index, $contactName, $pdo);
        }
        if (!empty($product['manifest']['datasheet'])) {
            $copiedFiles[] = copy_analysis_file($product['manifest']['datasheet'], 'ficha_tecnica', $requestId, $index, $contactName, $pdo);
        }
    }

    foreach ($isoCertificateFiles as $file) {
        $copiedFiles[] = store_client_support_file(
            $file,
            'certificado_iso_9001',
            'Certificado ISO 9001 aportado opcionalmente por el cliente; pendiente de validación técnica.',
            $requestId,
            $contactName,
            $pdo
        );
    }
    foreach ($testReportFiles as $file) {
        $copiedFiles[] = store_client_support_file(
            $file,
            'test_report',
            'Test Report aportado por el cliente; pendiente de revisión de suficiencia y alcance.',
            $requestId,
            $contactName,
            $pdo
        );
    }

    $pdo->commit();
    $notificationProducts = array_map(static fn(array $product): array => [
        'producto' => $product['catalog']['producto_nombre'],
        'referencia' => $product['reference'],
    ], $products);
    try {
        $mailStatus = notify_service_request($pdo, [
            'id' => $requestId,
            'codigo_solicitud' => $requestCode,
            'cliente_nombre' => $clientName,
            'contacto_nombre' => $contactName,
            'contacto_correo' => $contactEmail,
            'contacto_telefono' => $contactPhone,
            'reglamento' => implode(' | ', $regulationNames),
        ], $commercial, $notificationProducts);
    } catch (Throwable) {
        $mailStatus = 'fallido';
    }
    $pdo->prepare('UPDATE solicitudes_servicio SET correo_notificacion_estado = :estado WHERE id = :id')
        ->execute(['estado' => $mailStatus, 'id' => $requestId]);
    foreach ($products as $product) {
        cleanup_product_manifest($product['manifest']);
    }
    redirect_client_request('ok', ['codigo' => $requestCode, 'mail' => $mailStatus]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($copiedFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    redirect_client_request('error', ['message' => $exception->getMessage()]);
}
