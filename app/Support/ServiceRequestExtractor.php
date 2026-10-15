<?php
declare(strict_types=1);

require_once __DIR__ . '/SimpleXlsxReader.php';

function service_request_field_map(): array
{
    return [
        'formato_codigo' => ['cell' => 'C4', 'label' => 'Código del formato'],
        'formato_version' => ['cell' => 'D4', 'label' => 'Versión del formato'],
        'formato_vigencia' => ['cell' => 'E4', 'label' => 'Vigencia del formato'],
        'cliente_nombre' => ['cell' => 'E8', 'label' => 'Nombre o razón social'],
        'numero_documento' => ['cell' => 'E9', 'label' => 'NIT'],
        'direccion' => ['cell' => 'E10', 'label' => 'Dirección'],
        'ciudad' => ['cell' => 'E11', 'label' => 'Ciudad'],
        'contacto_correo' => ['cell' => 'E12', 'label' => 'E-mail'],
        'contacto_telefono' => ['cell' => 'E13', 'label' => 'Teléfono'],
        'contacto_nombre' => ['cell' => 'E14', 'label' => 'Persona de contacto'],
        'contacto_cargo' => ['cell' => 'E15', 'label' => 'Cargo'],
        'reglamento_norma' => ['cell' => 'D27', 'label' => 'Clasificación de producto y referente normativo'],
        'articulo_producto' => ['cell' => 'H27', 'label' => 'Artículo del producto'],
        'producto' => ['cell' => 'D28', 'label' => 'Producto familia'],
        'esquema_solicitado' => ['cell' => 'H28', 'label' => 'Esquema solicitado'],
        'referencia_producto' => ['cell' => 'D29', 'label' => 'Producto subfamilia'],
        'comentario_adicional' => ['cell' => 'H29', 'label' => 'Comentario adicional'],
        'motivo_servicio' => ['cell' => 'D33', 'label' => 'Motivo o finalidad'],
        'certificacion_iso_9001' => ['cell' => 'H33', 'label' => 'Certificación ISO 9001'],
        'vigencia_iso_9001' => ['cell' => 'H34', 'label' => 'Vigencia ISO 9001'],
        'servicio_solicitado' => ['cell' => 'D38', 'label' => 'Servicio recomendado'],
        'observaciones_servicio' => ['cell' => 'H38', 'label' => 'Observaciones adicionales'],
        'relacion_cliente_producto' => ['cell' => 'E53', 'label' => 'Relación de cliente con respecto al producto'],
        'solicitud_elaborada_por' => ['cell' => 'B100', 'label' => 'Solicitud elaborada por'],
    ];
}

function detect_product_line_code(string $reference, string $product = ''): ?string
{
    $text = mb_strtolower($reference . ' ' . $product, 'UTF-8');

    if (str_contains($text, 'retilap')) {
        return 'RETILAP';
    }

    if (str_contains($text, 'retie')) {
        return 'RETIE';
    }

    if (str_contains($text, 'vajilla') || str_contains($text, 'ceram') || str_contains($text, 'vidrio')) {
        return 'VAJILLAS';
    }

    if (str_contains($text, 'juguete')) {
        return 'JUGUETES';
    }

    return null;
}

function extract_service_request_from_xlsx(string $path): array
{
    $cells = xlsx_read_sheet_cells($path, 'Solicitud');
    $map = service_request_field_map();
    $fields = [];
    $sources = [];

    foreach ($map as $field => $definition) {
        $cell = $definition['cell'];
        $value = trim((string) ($cells[$cell] ?? ''));
        $fields[$field] = $value;
        $sources[$field] = [
            'sheet' => 'Solicitud',
            'cell' => $cell,
            'label' => $definition['label'],
            'value' => $value,
        ];
    }

    $fields['linea_producto_codigo'] = detect_product_line_code(
        $fields['reglamento_norma'] ?? '',
        ($fields['producto'] ?? '') . ' ' . ($fields['referencia_producto'] ?? '')
    );

    $observations = [];
    foreach (['articulo_producto', 'esquema_solicitado', 'comentario_adicional', 'motivo_servicio', 'certificacion_iso_9001', 'vigencia_iso_9001', 'observaciones_servicio', 'relacion_cliente_producto'] as $field) {
        if (($fields[$field] ?? '') !== '') {
            $observations[] = $map[$field]['label'] . ': ' . $fields[$field];
        }
    }
    $fields['observaciones'] = implode("\n", $observations);

    return [
        'fields' => $fields,
        'sources' => $sources,
    ];
}
