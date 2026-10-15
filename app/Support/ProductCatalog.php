<?php
declare(strict_types=1);

require_once __DIR__ . '/SimpleXlsxReader.php';

function catalog_display_name(?string $value): string
{
    $value = trim((string) $value);
    $value = str_replace(["\r", "\n", '_'], [' ', ' ', ' '], $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
}

function catalog_normalized_key(?string $value): string
{
    $value = catalog_display_name($value);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = $ascii !== false ? $ascii : $value;
    $value = mb_strtoupper($value, 'UTF-8');
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function catalog_regulation_meta(string $class): ?array
{
    $key = catalog_normalized_key($class);

    if ($key === 'VOLUNTARIO') {
        return ['code' => 'VOLUNTARIO', 'name' => 'Normas voluntarias', 'country' => null, 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'RETILAP') && str_contains($key, '40150')) {
        return ['code' => 'RETILAP_40150', 'name' => 'RETILAP - Resolución 40150 de 2024', 'country' => 'Colombia', 'schemes' => ['Esquema 1B RETILAP', 'Esquema 4 RETILAP', 'Esquema 5 RETILAP']];
    }
    if (str_contains($key, 'RETIE') && str_contains($key, '40117')) {
        return ['code' => 'RETIE_40117', 'name' => 'RETIE - Resolución 40117 de 2024', 'country' => 'Colombia', 'schemes' => ['Esquema 1B RETIE', 'Esquema 4 RETIE', 'Esquema 5 RETIE']];
    }
    if (str_contains($key, 'JUGUETES') && str_contains($key, 'ECUATORIANO')) {
        return ['code' => 'JUGUETES_EC_089', 'name' => 'Juguetes Ecuador - RTE INEN 089', 'country' => 'Ecuador', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'JUGUETES') && str_contains($key, '0686')) {
        return ['code' => 'JUGUETES_CO_0686', 'name' => 'Juguetes Colombia - Resolución 0686 de 2018', 'country' => 'Colombia', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'VAJILLAS') && str_contains($key, '1440')) {
        return ['code' => 'VAJILLAS_1440', 'name' => 'Vajillas - Resolución 1440 de 2019', 'country' => 'Colombia', 'schemes' => ['Esquema 1A', 'Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'ALAMBRE') && str_contains($key, '1019')) {
        return ['code' => 'MALLAS_1019', 'name' => 'Alambre y mallas - Resolución 1019 de 2024', 'country' => 'Colombia', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'ALAMBRE') && str_contains($key, '0277')) {
        return ['code' => 'MALLAS_0277', 'name' => 'Alambre y mallas - Resolución 0277 de 2015', 'country' => 'Colombia', 'schemes' => ['Esquema 1A', 'Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'BARRAS') && str_contains($key, '2003')) {
        return ['code' => 'BARRAS_2003', 'name' => 'Barras corrugadas - Resolución 2003 de 2022', 'country' => 'Colombia', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'CASCOS') && str_contains($key, '1080')) {
        return ['code' => 'CASCOS_1080', 'name' => 'Cascos - Resolución 1080 de 2019', 'country' => 'Colombia', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'RTE INEN 069')) {
        return ['code' => 'RTE_INEN_069_1R', 'name' => 'RTE INEN 069 1R - Alumbrado público', 'country' => 'Ecuador', 'schemes' => ['Esquema 5']];
    }
    if (str_contains($key, 'RTE INEN 057')) {
        return ['code' => 'RTE_INEN_057_2R', 'name' => 'RTE INEN 057 2R - Tableros eléctricos', 'country' => 'Ecuador', 'schemes' => ['Esquema 1A', 'Esquema 1B', 'Esquema 5']];
    }
    if (str_contains($key, 'RTS 29')) {
        return ['code' => 'RTS_29', 'name' => 'RTS 29 - Productos eléctricos', 'country' => 'El Salvador', 'schemes' => ['Esquema 1B', 'Esquema 5']];
    }

    return null;
}

function catalog_extract_schemes(string $value, array $meta): array
{
    $key = catalog_normalized_key($value);
    preg_match_all('/\b(1A|1B|4|5)\b/', $key, $matches);
    $found = array_values(array_unique($matches[1] ?? []));
    if (!$found) {
        return $meta['schemes'];
    }

    $order = ['1A' => 10, '1B' => 20, '4' => 30, '5' => 40];
    usort($found, static fn(string $left, string $right): int => ($order[$left] ?? 99) <=> ($order[$right] ?? 99));
    $suffix = $meta['code'] === 'RETIE_40117' ? ' RETIE' : ($meta['code'] === 'RETILAP_40150' ? ' RETILAP' : '');
    return array_map(static fn(string $scheme): string => 'Esquema ' . $scheme . $suffix, $found);
}

function import_request_catalog(PDO $pdo, string $xlsxPath, ?string $sourceName = null): array
{
    if (!is_file($xlsxPath)) {
        throw new RuntimeException('No se encontro el archivo Excel del catalogo.');
    }

    $cells = xlsx_read_sheet_cells($xlsxPath, 'MATRIZ ALCANCE');
    if (!$cells) {
        throw new RuntimeException('La hoja MATRIZ ALCANCE no contiene datos para importar.');
    }

    $maxRow = 0;
    foreach (array_keys($cells) as $reference) {
        if (preg_match('/(\d+)$/', $reference, $matches)) {
            $maxRow = max($maxRow, (int) $matches[1]);
        }
    }

    $groups = [];
    $regulations = [];
    for ($row = 2; $row <= $maxRow; $row++) {
        $productSource = trim((string) ($cells['C' . $row] ?? ''));
        $classSource = trim((string) ($cells['D' . $row] ?? ''));
        $meta = catalog_regulation_meta($classSource);
        if ($productSource === '' || $meta === null) {
            continue;
        }

        $familySource = trim((string) ($cells['E' . $row] ?? ''));
        $familyArticle = trim((string) ($cells['F' . $row] ?? ''));
        $subfamilySource = trim((string) ($cells['G' . $row] ?? ''));
        $additionalArticle = trim((string) ($cells['H' . $row] ?? ''));
        $minimumArticle = trim((string) ($cells['I' . $row] ?? ''));
        $document = trim((string) ($cells['J' . $row] ?? ''));
        $secondaryScheme = trim((string) ($cells['L' . $row] ?? ''));
        $schemeSource = (string) ($cells['K' . $row] ?? '');
        if (str_contains(catalog_normalized_key($secondaryScheme), 'ESQUEMA')) {
            $schemeSource .= ' ' . $secondaryScheme;
        }
        $schemes = in_array($meta['code'], ['RETIE_40117', 'RETILAP_40150'], true)
            ? $meta['schemes']
            : catalog_extract_schemes($schemeSource, $meta);
        $naceCode = catalog_display_name((string) ($cells['M' . $row] ?? ''));
        $groupKey = hash('sha256', implode('|', [
            $meta['code'], $productSource, $familySource, $familyArticle, $subfamilySource, $additionalArticle,
            $minimumArticle, $naceCode,
        ]));

        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'hash' => $groupKey,
                'meta' => $meta,
                'class_source' => $classSource,
                'product_source' => $productSource,
                'family_source' => $familySource,
                'family_article' => $familyArticle,
                'subfamily_source' => $subfamilySource,
                'additional_article' => $additionalArticle,
                'minimum_article' => $minimumArticle,
                'nace_code' => $naceCode,
                'documents' => [],
                'schemes' => [],
                'row' => $row,
            ];
        }
        if ($document !== '' && !in_array($document, $groups[$groupKey]['documents'], true)) {
            $groups[$groupKey]['documents'][] = $document;
        }
        foreach ($schemes as $scheme) {
            if (!in_array($scheme, $groups[$groupKey]['schemes'], true)) {
                $groups[$groupKey]['schemes'][] = $scheme;
            }
        }
        $regulations[$meta['code']] = ['meta' => $meta, 'source' => $classSource];
    }

    if (!$groups) {
        throw new RuntimeException('No se encontraron productos reglamentarios validos en MATRIZ ALCANCE.');
    }

    $pdo->exec('UPDATE catalogo_productos_reglamentarios SET activo = 0');
    $pdo->exec('UPDATE reglamentos_catalogo SET activo = 0');
    $sourceName = $sourceName ?: basename($xlsxPath);

    $regulationStatement = $pdo->prepare(db_is_postgres($pdo)
        ? 'INSERT INTO reglamentos_catalogo (codigo, nombre, fuente_nombre, pais, esquemas_json, activo)
           VALUES (:codigo, :nombre, :fuente, :pais, :esquemas, 1)
           ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre, fuente_nombre = EXCLUDED.fuente_nombre,
             pais = EXCLUDED.pais, esquemas_json = EXCLUDED.esquemas_json, activo = 1'
        : 'INSERT INTO reglamentos_catalogo (codigo, nombre, fuente_nombre, pais, esquemas_json, activo)
           VALUES (:codigo, :nombre, :fuente, :pais, :esquemas, 1)
           ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), fuente_nombre = VALUES(fuente_nombre),
             pais = VALUES(pais), esquemas_json = VALUES(esquemas_json), activo = 1');
    $lineStatement = $pdo->prepare(db_is_postgres($pdo)
        ? 'INSERT INTO lineas_producto (codigo, nombre, tipo_referencia, reglamento_norma, descripcion, activa)
           VALUES (:codigo, :nombre, :tipo, :norma, :descripcion, 1)
           ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre, tipo_referencia = EXCLUDED.tipo_referencia,
             reglamento_norma = EXCLUDED.reglamento_norma, descripcion = EXCLUDED.descripcion, activa = 1'
        : 'INSERT INTO lineas_producto (codigo, nombre, tipo_referencia, reglamento_norma, descripcion, activa)
           VALUES (:codigo, :nombre, :tipo, :norma, :descripcion, 1)
           ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo_referencia = VALUES(tipo_referencia),
             reglamento_norma = VALUES(reglamento_norma), descripcion = VALUES(descripcion), activa = 1');
    foreach ($regulations as $regulation) {
        $meta = $regulation['meta'];
        $regulationStatement->execute([
            'codigo' => $meta['code'],
            'nombre' => $meta['name'],
            'fuente' => $regulation['source'],
            'pais' => $meta['country'],
            'esquemas' => json_encode($meta['schemes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $lineStatement->execute([
            'codigo' => $meta['code'],
            'nombre' => $meta['name'],
            'tipo' => 'Alcance de certificacion de producto',
            'norma' => $meta['name'],
            'descripcion' => 'Catalogo importado desde ' . $sourceName,
        ]);
    }

    $productStatement = $pdo->prepare(db_is_postgres($pdo)
        ? 'INSERT INTO catalogo_productos_reglamentarios
          (registro_hash, reglamento_codigo, reglamento_nombre, producto_fuente, producto_nombre,
           familia_fuente, familia_nombre, subfamilia_fuente, subfamilia_nombre, articulo, numeral,
           codigo_nace, documento_normativo, documentos_json, esquemas_json, texto_busqueda, fuente_archivo, fila_excel, activo)
         VALUES
          (:hash, :codigo, :reglamento, :producto_fuente, :producto, :familia_fuente, :familia,
           :subfamilia_fuente, :subfamilia, :articulo, :numeral, :nace, :documento, :documentos,
           :esquemas, :texto_busqueda, :fuente, :fila, 1)
         ON CONFLICT (registro_hash) DO UPDATE SET reglamento_codigo = EXCLUDED.reglamento_codigo,
           reglamento_nombre = EXCLUDED.reglamento_nombre, producto_fuente = EXCLUDED.producto_fuente,
           producto_nombre = EXCLUDED.producto_nombre, familia_fuente = EXCLUDED.familia_fuente,
           familia_nombre = EXCLUDED.familia_nombre, subfamilia_fuente = EXCLUDED.subfamilia_fuente,
           subfamilia_nombre = EXCLUDED.subfamilia_nombre, articulo = EXCLUDED.articulo, numeral = EXCLUDED.numeral,
           codigo_nace = EXCLUDED.codigo_nace, documento_normativo = EXCLUDED.documento_normativo,
           documentos_json = EXCLUDED.documentos_json,
           esquemas_json = EXCLUDED.esquemas_json, texto_busqueda = EXCLUDED.texto_busqueda,
           fuente_archivo = EXCLUDED.fuente_archivo, fila_excel = EXCLUDED.fila_excel, activo = 1'
        : 'INSERT INTO catalogo_productos_reglamentarios
          (registro_hash, reglamento_codigo, reglamento_nombre, producto_fuente, producto_nombre,
           familia_fuente, familia_nombre, subfamilia_fuente, subfamilia_nombre, articulo, numeral,
           codigo_nace, documento_normativo, documentos_json, esquemas_json, texto_busqueda, fuente_archivo, fila_excel, activo)
         VALUES
          (:hash, :codigo, :reglamento, :producto_fuente, :producto, :familia_fuente, :familia,
           :subfamilia_fuente, :subfamilia, :articulo, :numeral, :nace, :documento, :documentos,
           :esquemas, :texto_busqueda, :fuente, :fila, 1)
         ON DUPLICATE KEY UPDATE reglamento_codigo = VALUES(reglamento_codigo),
           reglamento_nombre = VALUES(reglamento_nombre), producto_fuente = VALUES(producto_fuente),
           producto_nombre = VALUES(producto_nombre), familia_fuente = VALUES(familia_fuente),
           familia_nombre = VALUES(familia_nombre), subfamilia_fuente = VALUES(subfamilia_fuente),
           subfamilia_nombre = VALUES(subfamilia_nombre), articulo = VALUES(articulo), numeral = VALUES(numeral),
           codigo_nace = VALUES(codigo_nace), documento_normativo = VALUES(documento_normativo),
           documentos_json = VALUES(documentos_json),
           esquemas_json = VALUES(esquemas_json), texto_busqueda = VALUES(texto_busqueda),
           fuente_archivo = VALUES(fuente_archivo), fila_excel = VALUES(fila_excel), activo = 1');

    $counts = [];
    foreach ($groups as $group) {
        $meta = $group['meta'];
        $product = catalog_display_name($group['product_source']);
        $family = catalog_display_name($group['family_source']) ?: $product;
        $subfamily = catalog_display_name($group['subfamily_source']) ?: $family;
        $article = catalog_display_name(implode(' ', array_filter([$group['family_article'], $group['additional_article']])));
        $documents = $group['documents'];
        $schemes = $group['schemes'] ?: $meta['schemes'];
        $searchText = catalog_display_name(implode(' ', array_merge([
            $meta['name'], $product, $family, $subfamily, $article, $group['minimum_article'], $group['nace_code'],
        ], $documents)));

        $productStatement->execute([
            'hash' => $group['hash'],
            'codigo' => $meta['code'],
            'reglamento' => $meta['name'],
            'producto_fuente' => $group['product_source'],
            'producto' => $product,
            'familia_fuente' => $group['family_source'] ?: null,
            'familia' => $family,
            'subfamilia_fuente' => $group['subfamily_source'] ?: null,
            'subfamilia' => $subfamily,
            'articulo' => $article ?: null,
            'numeral' => $group['minimum_article'] ?: null,
            'nace' => $group['nace_code'] ?: null,
            'documento' => $documents ? implode("\n\n", $documents) : null,
            'documentos' => json_encode($documents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'esquemas' => json_encode($schemes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'texto_busqueda' => $searchText,
            'fuente' => $sourceName,
            'fila' => $group['row'],
        ]);
        $counts[$meta['code']] = ($counts[$meta['code']] ?? 0) + 1;
    }

    ksort($counts);
    return ['total' => array_sum($counts), 'counts' => $counts, 'source' => $sourceName];
}

function request_catalog_rows(PDO $pdo): array
{
    $rows = $pdo->query(
        'SELECT id, reglamento_codigo, reglamento_nombre, producto_nombre, familia_nombre,
                subfamilia_nombre, articulo, numeral, codigo_nace, documento_normativo, documentos_json, esquemas_json
         FROM catalogo_productos_reglamentarios
         WHERE activo = 1
         ORDER BY reglamento_nombre, familia_nombre, producto_nombre'
    )->fetchAll();

    foreach ($rows as &$row) {
        $row['reglamento_nombre'] = str_replace('Resolucion', 'Resolución', (string) $row['reglamento_nombre']);
        $row['documentos'] = json_decode((string) ($row['documentos_json'] ?? '[]'), true) ?: [];
        $row['esquemas'] = json_decode((string) ($row['esquemas_json'] ?? '[]'), true) ?: [];
        unset($row['documentos_json'], $row['esquemas_json']);
    }
    unset($row);
    return $rows;
}

function request_regulation_rows(PDO $pdo): array
{
    $rows = $pdo->query(
        'SELECT codigo, nombre, pais, esquemas_json
         FROM reglamentos_catalogo
         WHERE activo = 1
         ORDER BY CASE WHEN pais = \'Colombia\' THEN 0 ELSE 1 END, nombre'
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['nombre'] = str_replace('Resolucion', 'Resolución', (string) $row['nombre']);
        $row['esquemas'] = json_decode((string) ($row['esquemas_json'] ?? '[]'), true) ?: [];
        unset($row['esquemas_json']);
    }
    unset($row);
    return $rows;
}
