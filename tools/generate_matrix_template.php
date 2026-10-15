<?php
declare(strict_types=1);

$definition = require __DIR__ . '/../config/matrix_template.php';
$output = __DIR__ . '/../templates/plantilla_matriz_normativa.xlsx';

function xml_text(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function column_letter(int $index): string
{
    $letter = '';
    while ($index > 0) {
        $index--;
        $letter = chr(65 + ($index % 26)) . $letter;
        $index = intdiv($index, 26);
    }
    return $letter;
}

function inline_cell(int $column, int $row, string $value, int $style = 0): string
{
    $reference = column_letter($column) . $row;
    $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';
    return '<c r="' . $reference . '" t="inlineStr"' . $styleAttribute . '><is><t>' . xml_text($value) . '</t></is></c>';
}

function build_sheet(array $rows, array $widths = []): string
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
        foreach ($row as $columnIndex => $value) {
            $style = $excelRow === 1 ? 1 : 0;
            $xml .= inline_cell($columnIndex + 1, $excelRow, (string) $value, $style);
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';
    $xml .= '<autoFilter ref="A1:' . column_letter(count($rows[0] ?? [])) . '1"/>';
    $xml .= '</worksheet>';

    return $xml;
}

function add_zip_entry(ZipArchive $zip, string $path, string $content): void
{
    $zip->addFromString($path, $content);
}

$headers = array_keys($definition['columns']);
$matrixRows = [$headers, ...$definition['examples']];

$instructions = [
    ['Plantilla oficial de matrices normativas'],
    ['Version de plantilla', $definition['version']],
    ['Uso', 'Copia tus registros desde la fila 3. No cambies los encabezados tecnicos de la fila 1.'],
    ['Columnas requeridas', implode(', ', $definition['required'])],
    ['Valores para fases', 'Usa SI/NO, X/vacio o 1/0 para OT, vigilancia_1, vigilancia_2 y renovacion.'],
    ['Fecha actualizacion', 'Usa formato AAAA-MM-DD.'],
    ['Carga en sistema', 'Sube este archivo desde la pantalla inicial del sistema.'],
];

$lists = [
    ['Lineas de producto sugeridas', 'Valores booleanos', 'Estados de matriz'],
    ['JUGUETES', 'SI', 'borrador'],
    ['VAJILLAS', 'NO', 'activa'],
    ['RETIE', 'X', 'historica'],
    ['RETILAP', '1', 'rechazada'],
    ['', '0', ''],
];

$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('No fue posible crear la plantilla Excel.');
}

add_zip_entry($zip, '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>');

add_zip_entry($zip, '_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>');

add_zip_entry($zip, 'xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Matriz_normativa" sheetId="1" r:id="rId1"/>
    <sheet name="Listas" sheetId="2" r:id="rId2"/>
    <sheet name="Instrucciones" sheetId="3" r:id="rId3"/>
  </sheets>
</workbook>');

add_zip_entry($zip, 'xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

add_zip_entry($zip, 'xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color theme="1"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="2">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
  </cellXfs>
</styleSheet>');

add_zip_entry($zip, 'xl/worksheets/sheet1.xml', build_sheet($matrixRows, array_fill(1, count($headers), 22)));
add_zip_entry($zip, 'xl/worksheets/sheet2.xml', build_sheet($lists, [1 => 28, 2 => 20, 3 => 22]));
add_zip_entry($zip, 'xl/worksheets/sheet3.xml', build_sheet($instructions, [1 => 28, 2 => 110]));

add_zip_entry($zip, 'docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Plantilla matriz normativa</dc:title>
  <dc:creator>Software producto</dc:creator>
  <cp:lastModifiedBy>Software producto</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified>
</cp:coreProperties>');

add_zip_entry($zip, 'docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Software producto</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>3</vt:i4></vt:variant></vt:vector></HeadingPairs>
  <TitlesOfParts><vt:vector size="3" baseType="lpstr"><vt:lpstr>Matriz_normativa</vt:lpstr><vt:lpstr>Listas</vt:lpstr><vt:lpstr>Instrucciones</vt:lpstr></vt:vector></TitlesOfParts>
</Properties>');

$zip->close();

echo "Plantilla generada: {$output}" . PHP_EOL;
