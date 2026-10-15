<?php
declare(strict_types=1);

require_once __DIR__ . '/MatrixTemplate.php';

function lab_matrix_columns(): array
{
    return [
        'codigo_sm' => 'CODIGO SM',
        'proveedor' => 'PROVEEDOR',
        'ensayo' => 'ENSAYO',
        'tecnica' => 'TECNICA',
        'sustancia_material_elemento_o_producto_a_ensayar' => 'SUSTANCIA, MATERIAL, ELEMENTO O PRODUCTO A ENSAYAR',
        'intervalo_de_medicion' => 'INTERVALO DE MEDICION',
        'documento_normativo' => 'DOCUMENTO NORMATIVO',
        'sede' => 'SEDE',
        'estado' => 'ESTADO',
        'tipo_de_pago' => 'TIPO DE PAGO',
        'costo_de_ensayo' => 'COSTO DE ENSAYO',
        'codigo_de_ensayo_lab' => 'CODIGO DE ENSAYO LAB',
        'observaciones' => 'OBSERVACIONES',
    ];
}

function lab_required_columns(): array
{
    return ['codigo_sm', 'proveedor', 'ensayo', 'documento_normativo', 'costo_de_ensayo'];
}

function parse_money_value(mixed $value): float
{
    $text = trim((string) $value);
    if ($text === '') {
        return 0.0;
    }

    $text = str_replace(["\xc2\xa0", '$', 'COP', 'cop', ' '], '', $text);
    $text = preg_replace('/[^0-9,.-]/', '', $text) ?? '';

    if (str_contains($text, ',') && str_contains($text, '.')) {
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
    } elseif (substr_count($text, ',') === 1 && !str_contains($text, '.')) {
        $text = str_replace(',', '.', $text);
    } elseif (substr_count($text, '.') > 1) {
        $text = str_replace('.', '', $text);
    }

    return is_numeric($text) ? (float) $text : 0.0;
}

function read_lab_matrix_file(string $path, string $extension): array
{
    $matrix = read_matrix_file($path, $extension);
    $rows = canonicalize_lab_rows($matrix['rows']);
    $headers = canonicalize_lab_headers($matrix['headers']);
    $headers = array_unique(array_merge($headers, $rows ? array_keys($rows[0]) : []));
    $missing = array_values(array_diff(lab_required_columns(), $headers));

    if ($missing) {
        throw new RuntimeException('La matriz de laboratorio no cumple la plantilla. Faltan columnas: ' . implode(', ', $missing));
    }

    return $rows;
}

function canonicalize_lab_headers(array $headers): array
{
    if (in_array('codigo', $headers, true) && !in_array('codigo_sm', $headers, true)) {
        $headers[] = 'codigo_sm';
    }

    if (in_array('sustancia_material_elemento_producto_a_ensayar', $headers, true)
        && !in_array('sustancia_material_elemento_o_producto_a_ensayar', $headers, true)) {
        $headers[] = 'sustancia_material_elemento_o_producto_a_ensayar';
    }

    return $headers;
}

function canonicalize_lab_rows(array $rows): array
{
    foreach ($rows as &$row) {
        if (empty($row['codigo_sm']) && !empty($row['codigo'])) {
            $row['codigo_sm'] = $row['codigo'];
        }

        if (empty($row['sustancia_material_elemento_o_producto_a_ensayar']) && !empty($row['sustancia_material_elemento_producto_a_ensayar'])) {
            $row['sustancia_material_elemento_o_producto_a_ensayar'] = $row['sustancia_material_elemento_producto_a_ensayar'];
        }

        if (empty($row['codigo_de_ensayo_lab']) && !empty($row['codigo_ensayo_lab'])) {
            $row['codigo_de_ensayo_lab'] = $row['codigo_ensayo_lab'];
        }
    }
    unset($row);

    return $rows;
}

function lab_row_value(array $row, string $key): string
{
    return trim((string) ($row[$key] ?? ''));
}

function normalize_document_text(string $value): string
{
    return normalize_essay_name($value);
}
