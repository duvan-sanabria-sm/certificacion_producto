<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/runtime.php';

function xlsx_xml_text(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function xlsx_column_letter(int $index): string
{
    $letter = '';
    while ($index > 0) {
        $index--;
        $letter = chr(65 + ($index % 26)) . $letter;
        $index = intdiv($index, 26);
    }
    return $letter;
}

function xlsx_inline_cell(int $column, int $row, string $value, int $style = 0): string
{
    $reference = xlsx_column_letter($column) . $row;
    $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';
    return '<c r="' . $reference . '" t="inlineStr"' . $styleAttribute . '><is><t>' . xlsx_xml_text($value) . '</t></is></c>';
}

function xlsx_sheet_xml(array $rows, array $widths = []): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';

    if ($widths) {
        $xml .= '<cols>';
        foreach ($widths as $column => $width) {
            $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
    }

    $xml .= '<sheetData>';
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $xml .= '<row r="' . $excelRow . '">';
        foreach (array_values($row) as $columnIndex => $value) {
            $style = $excelRow === 1 ? 1 : 0;
            $xml .= xlsx_inline_cell($columnIndex + 1, $excelRow, (string) $value, $style);
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';

    if (count($rows) > 0 && count($rows[0]) > 0) {
        $xml .= '<autoFilter ref="A1:' . xlsx_column_letter(count($rows[0])) . '1"/>';
    }

    $xml .= '</worksheet>';
    return $xml;
}

function xlsx_add(ZipArchive $zip, string $path, string $content): void
{
    $zip->addFromString($path, $content);
}

function build_xlsx_binary(array $sheets): string
{
    $tempDirectory = app_ensure_directory(app_runtime_path('SERVIMETERS_TEMP_PATH', 'tmp'));
    $temp = tempnam($tempDirectory, 'xlsx_');
    if ($temp === false) {
        throw new RuntimeException('No fue posible crear archivo temporal XLSX.');
    }

    $zip = new ZipArchive();
    if ($zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No fue posible crear XLSX.');
    }

    $sheetOverrides = '';
    $sheetEntries = '';
    $sheetRels = '';
    $sheetIndex = 1;

    foreach ($sheets as $name => $sheet) {
        $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $sheetIndex . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $sheetEntries .= '<sheet name="' . xlsx_xml_text((string) $name) . '" sheetId="' . $sheetIndex . '" r:id="rId' . $sheetIndex . '"/>';
        $sheetRels .= '<Relationship Id="rId' . $sheetIndex . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetIndex . '.xml"/>';
        $sheetIndex++;
    }

    xlsx_add($zip, '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  ' . $sheetOverrides . '
</Types>');

    xlsx_add($zip, '_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    xlsx_add($zip, 'xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>' . $sheetEntries . '</sheets>
</workbook>');

    xlsx_add($zip, 'xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  ' . $sheetRels . '
  <Relationship Id="rId' . $sheetIndex . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

    xlsx_add($zip, 'xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>
  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>
</styleSheet>');

    $sheetIndex = 1;
    foreach ($sheets as $sheet) {
        xlsx_add($zip, 'xl/worksheets/sheet' . $sheetIndex . '.xml', xlsx_sheet_xml($sheet['rows'], $sheet['widths'] ?? []));
        $sheetIndex++;
    }

    $zip->close();
    $binary = file_get_contents($temp);
    unlink($temp);

    if ($binary === false) {
        throw new RuntimeException('No fue posible leer XLSX generado.');
    }

    return $binary;
}

function output_xlsx_download(string $filename, array $sheets): void
{
    $binary = build_xlsx_binary($sheets);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary));
    echo $binary;
    exit;
}
