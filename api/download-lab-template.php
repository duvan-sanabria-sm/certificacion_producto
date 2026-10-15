<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Support/LabMatrix.php';
require_once __DIR__ . '/../app/Support/SimpleXlsxWriter.php';
require_once __DIR__ . '/../app/Support/Auth.php';

auth_require_permission('laboratories.view');

$columns = array_values(lab_matrix_columns());

output_xlsx_download('plantilla_matriz_laboratorio.xlsx', [
    'TODOS LOS LABORATORIOS' => [
        'rows' => [
            $columns,
            [
                'LEN-507',
                'Proveedor ejemplo',
                'Accesibilidad de una parte o de un elemento 8.10',
                'Inspeccion y ensayo',
                'Juguetes',
                'N/A',
                'NTC EN 71-1:2016 / EN 71-1:2014+A1:2018',
                'Bogota',
                'Activo',
                'Pago por ensayo',
                '67990',
                'LAB-001',
                'Fila de ejemplo; reemplazar antes de importar.',
            ],
        ],
        'widths' => array_fill(1, count($columns), 24),
    ],
    'Instrucciones' => [
        'rows' => [
            ['Campo', 'Instruccion'],
            ['COSTO DE ENSAYO', 'Usa numero limpio o moneda COP; la app lo normaliza.'],
            ['CÓDIGO SM', 'Codigo usado por el cotizador para arrastrar costo y valor.'],
            ['PROVEEDOR', 'Proveedor o laboratorio origen del ensayo.'],
            ['DOCUMENTO NORMATIVO', 'Norma o metodo que se cruza con la matriz normativa.'],
        ],
        'widths' => [1 => 28, 2 => 90],
    ],
]);
