<?php
declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Uso: php tools/inspect_xlsx.php archivo.xlsx\n");
    exit(1);
}

$path = $argv[1];
$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    fwrite(STDERR, "No se pudo abrir {$path}\n");
    exit(1);
}

function inspect_shared_strings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }
    $shared = simplexml_load_string($xml);
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

function inspect_cell_value(SimpleXMLElement $cell, array $shared): string
{
    $type = (string) ($cell['t'] ?? '');
    if ($type === 's') {
        return $shared[(int) ($cell->v ?? 0)] ?? '';
    }
    if ($type === 'inlineStr') {
        return (string) ($cell->is->t ?? '');
    }
    return (string) ($cell->v ?? '');
}

$shared = inspect_shared_strings($zip);
$workbookXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
$relsXml = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
$relsXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

$rels = [];
foreach ($relsXml->Relationship as $relationship) {
    $rels[(string) $relationship['Id']] = 'xl/' . ltrim((string) $relationship['Target'], '/');
}

$workbookXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

foreach ($workbookXml->sheets->sheet as $sheet) {
    $name = (string) $sheet['name'];
    $relationAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relation = (string) ($relationAttributes['id'] ?? '');
    $target = $rels[$relation] ?? '';
    echo "=== {$name} ({$target}) ===\n";
    $sheetXml = $target ? $zip->getFromName($target) : false;
    if ($sheetXml === false) {
        echo "No se pudo leer la hoja.\n";
        continue;
    }
    $xml = simplexml_load_string($sheetXml);
    $count = 0;
    foreach ($xml->sheetData->row as $row) {
        foreach ($row->c as $cell) {
            $value = trim(inspect_cell_value($cell, $shared));
            if ($value === '') {
                continue;
            }
            $ref = (string) ($cell['r'] ?? '');
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
            echo $ref . ': ' . mb_substr($value, 0, 180) . "\n";
            $count++;
            if ($count >= 220) {
                echo "... truncado ...\n";
                break 2;
            }
        }
    }
}

$zip->close();
