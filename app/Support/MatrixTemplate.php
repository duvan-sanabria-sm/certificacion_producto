<?php
declare(strict_types=1);

function matrix_template_definition(): array
{
    return require dirname(__DIR__, 2) . '/config/matrix_template.php';
}

function matrix_columns(): array
{
    return matrix_template_definition()['columns'];
}

function matrix_ascii_fold(string $value): string
{
    return strtr($value, [
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Ã' => 'A',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ñ' => 'N', 'ñ' => 'n',
    ]);
}

function normalize_matrix_header(string $header): string
{
    $header = trim($header);
    $header = matrix_ascii_fold($header);
    $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
    $header = strtolower($header);
    $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;
    return trim($header, '_');
}

function normalize_essay_name(string $value): string
{
    $value = matrix_ascii_fold($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function matrix_truthy(mixed $value): int
{
    $value = normalize_matrix_header((string) $value);
    return in_array($value, ['1', 'si', 's', 'x', 'true', 'verdadero', 'aplica'], true) ? 1 : 0;
}

function matrix_date_or_null(mixed $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $timestamp = ((float) $value - 25569) * 86400;
        return gmdate('Y-m-d', (int) $timestamp);
    }

    $timestamp = strtotime(str_replace('/', '-', $value));
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function validate_matrix_headers(array $headers): array
{
    $expected = array_keys(matrix_columns());
    $normalized = array_map(fn ($header) => normalize_matrix_header((string) $header), $headers);
    $missing = array_values(array_diff($expected, $normalized));

    return [
        'ok' => count($missing) === 0,
        'headers' => $normalized,
        'missing' => $missing,
    ];
}

function read_matrix_file(string $path, string $extension): array
{
    return match (strtolower($extension)) {
        'csv' => read_matrix_csv($path),
        'xlsx', 'xlsm' => read_matrix_xlsx($path),
        default => throw new RuntimeException('Formato no soportado. Usa .xlsx, .xlsm o .csv.'),
    };
}

function read_matrix_csv(string $path): array
{
    $handle = fopen($path, 'rb');
    if (!$handle) {
        throw new RuntimeException('No fue posible leer el CSV.');
    }

    $headers = null;
    $rows = [];
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (count($row) === 1) {
            $row = str_getcsv($row[0], ',');
        }

        if ($headers === null) {
            $headers = array_map('normalize_matrix_header', $row);
            continue;
        }

        if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }

        $rows[] = map_matrix_row($headers, $row, count($rows) + 2);
    }

    fclose($handle);

    return [
        'headers' => $headers ?? [],
        'rows' => $rows,
    ];
}

function read_matrix_xlsx(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('No fue posible abrir el archivo XLSX.');
    }

    $sharedStrings = read_xlsx_shared_strings($zip);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('La plantilla no contiene una primera hoja valida.');
    }

    $xml = simplexml_load_string($sheetXml);
    if (!$xml) {
        throw new RuntimeException('No fue posible interpretar la primera hoja del XLSX.');
    }

    $headers = null;
    $rows = [];

    foreach ($xml->sheetData->row as $rowNode) {
        $rowNumber = (int) ($rowNode['r'] ?? 0);
        $values = [];

        foreach ($rowNode->c as $cell) {
            $reference = (string) ($cell['r'] ?? '');
            $column = xlsx_column_index($reference);
            $values[$column] = read_xlsx_cell_value($cell, $sharedStrings);
        }

        if (!$values) {
            continue;
        }

        ksort($values);
        $rowValues = [];
        $maxColumn = max(array_keys($values));
        for ($column = 1; $column <= $maxColumn; $column++) {
            $rowValues[] = $values[$column] ?? '';
        }

        if ($headers === null) {
            $headers = array_map('normalize_matrix_header', $rowValues);
            continue;
        }

        if (count(array_filter($rowValues, fn ($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }

        $rows[] = map_matrix_row($headers, $rowValues, $rowNumber ?: count($rows) + 2);
    }

    return [
        'headers' => $headers ?? [],
        'rows' => $rows,
    ];
}

function read_xlsx_shared_strings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }

    $shared = simplexml_load_string($xml);
    if (!$shared) {
        return [];
    }

    $strings = [];
    foreach ($shared->si as $item) {
        if (isset($item->t)) {
            $strings[] = (string) $item->t;
            continue;
        }

        $text = '';
        foreach ($item->r as $run) {
            $text .= (string) $run->t;
        }
        $strings[] = $text;
    }

    return $strings;
}

function read_xlsx_cell_value(SimpleXMLElement $cell, array $sharedStrings): string
{
    $type = (string) ($cell['t'] ?? '');

    if ($type === 's') {
        $index = (int) ($cell->v ?? 0);
        return $sharedStrings[$index] ?? '';
    }

    if ($type === 'inlineStr') {
        return (string) ($cell->is->t ?? '');
    }

    return (string) ($cell->v ?? '');
}

function xlsx_column_index(string $reference): int
{
    preg_match('/^[A-Z]+/i', $reference, $matches);
    $letters = strtoupper($matches[0] ?? 'A');
    $index = 0;

    for ($i = 0; $i < strlen($letters); $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max($index, 1);
}

function map_matrix_row(array $headers, array $values, int $sourceRow): array
{
    $row = ['fila_excel' => $sourceRow];

    foreach ($headers as $index => $header) {
        if ($header === '') {
            continue;
        }
        $row[$header] = trim((string) ($values[$index] ?? ''));
    }

    return $row;
}
