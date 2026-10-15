<?php
declare(strict_types=1);

function xlsx_shared_strings(ZipArchive $zip): array
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

function xlsx_read_cell(SimpleXMLElement $cell, array $shared): string
{
    $type = (string) ($cell['t'] ?? '');

    if ($type === 's') {
        $index = (int) ($cell->v ?? 0);
        return $shared[$index] ?? '';
    }

    if ($type === 'inlineStr') {
        return (string) ($cell->is->t ?? '');
    }

    return (string) ($cell->v ?? '');
}

function xlsx_sheet_targets(ZipArchive $zip): array
{
    $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
    $rels = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));

    if (!$workbook || !$rels) {
        throw new RuntimeException('El XLSX no contiene libro valido.');
    }

    $relationships = [];
    foreach ($rels->Relationship as $relationship) {
        $relationships[(string) $relationship['Id']] = 'xl/' . ltrim((string) $relationship['Target'], '/');
    }

    $targets = [];
    foreach ($workbook->sheets->sheet as $sheet) {
        $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relation = (string) ($attrs['id'] ?? '');
        $targets[(string) $sheet['name']] = $relationships[$relation] ?? '';
    }

    return $targets;
}

function xlsx_read_sheet_cells(string $path, ?string $sheetName = null): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('No fue posible abrir el XLSX.');
    }

    $shared = xlsx_shared_strings($zip);
    $targets = xlsx_sheet_targets($zip);
    $target = '';

    if ($sheetName !== null && isset($targets[$sheetName])) {
        $target = $targets[$sheetName];
    } else {
        $target = reset($targets) ?: '';
    }

    if ($target === '') {
        $zip->close();
        throw new RuntimeException('No se encontro hoja para leer.');
    }

    $sheetXml = $zip->getFromName($target);
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('No fue posible leer la hoja XLSX.');
    }

    $xml = simplexml_load_string($sheetXml);
    if (!$xml) {
        throw new RuntimeException('La hoja XLSX no es valida.');
    }

    $cells = [];
    foreach ($xml->sheetData->row as $row) {
        foreach ($row->c as $cell) {
            $reference = (string) ($cell['r'] ?? '');
            if ($reference === '') {
                continue;
            }
            $cells[$reference] = trim(xlsx_read_cell($cell, $shared));
        }
    }

    return $cells;
}
