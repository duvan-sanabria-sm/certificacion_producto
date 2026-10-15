<?php
declare(strict_types=1);

require_once __DIR__ . '/MatrixTemplate.php';

function embedded_app_data(string $relativeFile): array
{
    $path = dirname(__DIR__, 2) . '/' . $relativeFile;
    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException('No fue posible leer ' . $relativeFile);
    }

    if (!preg_match('/<script type="application\/json" id="app-data">(.*?)<\/script>/s', $content, $matches)) {
        throw new RuntimeException('No se encontro app-data en ' . $relativeFile);
    }

    $data = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);
    if (!is_array($data)) {
        throw new RuntimeException('El app-data no es JSON valido en ' . $relativeFile);
    }

    return $data;
}

function matrix_scope_definitions(): array
{
    return [
        'JUGUETES' => [
            'code' => 'JUGUETES',
            'name' => 'Juguetes',
            'regulation' => 'Resolucion 0686 de 2018',
            'source_file' => 'constructor_iso_juguetes.php',
            'source_type' => 'tests',
            'status' => 'Ensayos disponibles en codigo inicial',
        ],
        'VAJILLAS' => [
            'code' => 'VAJILLAS',
            'name' => 'Vajillas',
            'regulation' => 'Resolucion 1440 de 2019',
            'source_file' => 'preview (28).php',
            'source_type' => 'tests',
            'line_filter' => 'Vajillas_Resolucion_1440_de_2019',
            'status' => 'Ensayos disponibles en constructor ampliado',
        ],
        'RETIE' => [
            'code' => 'RETIE',
            'name' => 'RETIE',
            'regulation' => 'Resolucion 40117 de 2024',
            'source_file' => 'preview (28).php',
            'source_type' => 'regulatory',
            'line_filter' => 'Reglamento_RETIE_resolución_40117',
            'status' => 'Catalogo normativo cargado; ensayos especificos pendientes',
        ],
        'RETILAP' => [
            'code' => 'RETILAP',
            'name' => 'RETILAP',
            'regulation' => 'Resolucion 40150 de 2024',
            'source_file' => 'preview (28).php',
            'source_type' => 'regulatory',
            'line_filter' => 'Reglamento_RETILAP_resolución_40150',
            'status' => 'Catalogo normativo cargado; ensayos especificos pendientes',
        ],
    ];
}

function embedded_matrix_rows(string $scopeCode): array
{
    $scope = matrix_scope_definitions()[$scopeCode] ?? null;
    if (!$scope) {
        throw new RuntimeException('Alcance no reconocido.');
    }

    $data = embedded_app_data($scope['source_file']);
    $rows = [];

    if ($scope['source_type'] === 'tests') {
        foreach (($data['tests'] ?? []) as $test) {
            $line = $test['lineaProducto'] ?? $scope['line_filter'] ?? null;
            if (isset($scope['line_filter']) && $line !== $scope['line_filter']) {
                continue;
            }

            $rows[] = [
                'linea_producto_codigo' => $scope['code'],
                'linea_producto_nombre' => $scope['name'],
                'tipo_referencia_normativa' => 'Reglamento tecnico nacional',
                'reglamento_norma' => $scope['regulation'],
                'producto' => $scope['name'],
                'clase' => $line ?: $scope['code'],
                'familia' => $test['familia'] ?? '',
                'familia_articulo' => '',
                'subfamilia' => $test['subfamilia'] ?? '',
                'articulo' => '',
                'articulo_adicional_observacion' => '',
                'ensayo' => $test['ensayo'] ?? '',
                'literal_numeral' => $test['literal'] ?? '',
                'norma_tecnica' => $test['norma'] ?? '',
                'metodo_ensayo' => '',
                'ot' => !empty($test['OT']) ? 'SI' : 'NO',
                'vigilancia_1' => !empty($test['VIG_1']) ? 'SI' : 'NO',
                'vigilancia_2' => !empty($test['VIG_2']) ? 'SI' : 'NO',
                'renovacion' => !empty($test['RENOVACION']) ? 'SI' : 'NO',
                'esquema' => '',
                'codigo_nace' => '',
                'nace' => '',
                'observaciones' => 'Fila fuente: ' . ($test['filaFuente'] ?? ''),
                'version_matriz' => 'codigo_inicial',
                'fecha_actualizacion' => date('Y-m-d'),
            ];
        }
    }

    if ($scope['source_type'] === 'regulatory') {
        foreach (($data['regulatoryArticles'] ?? []) as $record) {
            if (($record['clase'] ?? '') !== ($scope['line_filter'] ?? '')) {
                continue;
            }

            $rows[] = [
                'linea_producto_codigo' => $scope['code'],
                'linea_producto_nombre' => $scope['name'],
                'tipo_referencia_normativa' => 'Reglamento tecnico nacional',
                'reglamento_norma' => $scope['regulation'],
                'producto' => $record['producto'] ?? '',
                'clase' => $record['clase'] ?? '',
                'familia' => $record['familia'] ?? '',
                'familia_articulo' => $record['familiaArticulo'] ?? '',
                'subfamilia' => $record['subfamilia'] ?? '',
                'articulo' => $record['articuloEnsayosMinimos'] ?? '',
                'articulo_adicional_observacion' => $record['articuloAdicional'] ?? '',
                'ensayo' => 'PENDIENTE MATRIZ DE ENSAYOS',
                'literal_numeral' => $record['articuloEnsayosMinimos'] ?? '',
                'norma_tecnica' => $record['documento'] ?? '',
                'metodo_ensayo' => '',
                'ot' => 'SI',
                'vigilancia_1' => 'NO',
                'vigilancia_2' => 'NO',
                'renovacion' => 'SI',
                'esquema' => implode(' | ', $record['esquemas'] ?? []),
                'codigo_nace' => $record['codigoNace'] ?? '',
                'nace' => $record['nace'] ?? '',
                'observaciones' => 'Base normativa precargada; reemplazar ensayo cuando se actualice matriz.',
                'version_matriz' => 'codigo_inicial',
                'fecha_actualizacion' => date('Y-m-d'),
            ];
        }
    }

    return $rows;
}

function embedded_matrix_summary(): array
{
    $summary = [];
    foreach (matrix_scope_definitions() as $code => $scope) {
        try {
            $rows = embedded_matrix_rows($code);
            $summary[] = array_merge($scope, [
                'rows' => count($rows),
            ]);
        } catch (Throwable) {
            $summary[] = array_merge($scope, [
                'rows' => 0,
            ]);
        }
    }
    return $summary;
}
