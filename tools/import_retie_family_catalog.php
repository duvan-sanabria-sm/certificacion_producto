<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/ProductCatalog.php';

if ($argc < 2) {
    fwrite(STDERR, "Uso: php tools/import_retie_family_catalog.php <archivo.xlsx>\n");
    exit(1);
}

$sourcePath = (string) $argv[1];
if (!is_file($sourcePath)) {
    fwrite(STDERR, "No se encontro el archivo XLSX indicado.\n");
    exit(1);
}

try {
    $cells = xlsx_read_sheet_cells($sourcePath, 'Tabla_estructurada');
    $lastRow = 1;
    foreach (array_keys($cells) as $reference) {
        if (preg_match('/^[A-Z]+(\d+)$/', $reference, $matches)) {
            $lastRow = max($lastRow, (int) $matches[1]);
        }
    }

    $families = [];
    $familyIndexes = [];
    $seenOptions = [];
    for ($row = 2; $row <= $lastRow; $row++) {
        $number = catalog_display_name($cells['A' . $row] ?? '');
        $family = catalog_display_name($cells['B' . $row] ?? '');
        $criterionOne = catalog_display_name($cells['C' . $row] ?? '');
        $criterionTwo = catalog_display_name($cells['D' . $row] ?? '');
        $subfamily = catalog_display_name($cells['E' . $row] ?? '');
        $observation = catalog_display_name($cells['F' . $row] ?? '');
        $page = catalog_display_name($cells['G' . $row] ?? '');
        if ($family === '') {
            continue;
        }

        $familyKey = $number . '|' . catalog_normalized_key($family);
        if (!isset($familyIndexes[$familyKey])) {
            $familyIndexes[$familyKey] = count($families);
            $families[] = [
                'numero' => $number,
                'familia' => $family,
                'opciones' => [],
            ];
        }

        $segments = [];
        foreach ([$criterionOne, $criterionTwo, $subfamily] as $segment) {
            if ($segment !== '' && !in_array($segment, $segments, true)) {
                $segments[] = $segment;
            }
        }
        if (!$segments && $observation !== '') {
            $segments[] = $observation;
        }
        if (!$segments) {
            $segments[] = 'No aplica';
        }

        $option = [
            'criterio_1' => $criterionOne,
            'criterio_2' => $criterionTwo,
            'subfamilia' => $subfamily,
            'observacion' => $observation,
            'pagina' => $page,
            'etiqueta' => implode(' | ', $segments),
        ];
        $optionKey = $familyKey . '|' . catalog_normalized_key(implode('|', $option));
        if (isset($seenOptions[$optionKey])) {
            continue;
        }
        $seenOptions[$optionKey] = true;
        $families[$familyIndexes[$familyKey]]['opciones'][] = $option;
    }

    $outputDirectory = dirname(__DIR__) . '/storage/catalogos';
    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
        throw new RuntimeException('No fue posible crear la carpeta de catalogos.');
    }
    $outputPath = $outputDirectory . '/retie_familias_subfamilias.json';
    $payload = [
        'fuente' => basename($sourcePath),
        'hoja' => 'Tabla_estructurada',
        'familias' => array_values($families),
    ];
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($json === false || file_put_contents($outputPath, $json . PHP_EOL) === false) {
        throw new RuntimeException('No fue posible guardar el catalogo generado.');
    }

    $optionCount = array_sum(array_map(static fn(array $item): int => count($item['opciones']), $families));
    fwrite(STDOUT, sprintf("Catalogo generado: %d familias y %d opciones.\n%s\n", count($families), $optionCount, $outputPath));
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
