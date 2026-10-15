<?php
declare(strict_types=1);

function product_technical_fields_config(): array
{
    return [
        'groups' => [
            'common' => [
                'label' => 'Información común del producto',
                'note' => 'Se extrae de la ficha técnica y de la fotografía cuando sea legible. Es información de apoyo y no es obligatoria para continuar.',
                'fields' => [
                    ['key' => 'model', 'label' => 'Referencia o modelo', 'type' => 'text'],
                    ['key' => 'brand', 'label' => 'Marca comercial', 'type' => 'text'],
                    ['key' => 'manufacturer', 'label' => 'Fabricado por', 'type' => 'text'],
                    ['key' => 'manufacturer_address', 'label' => 'Dirección del fabricante', 'type' => 'text', 'wide' => true],
                    ['key' => 'country', 'label' => 'País de fabricación', 'type' => 'text'],
                    ['key' => 'marketer', 'label' => 'Comercializado por', 'type' => 'text'],
                    ['key' => 'tariff_code', 'label' => 'Partida o subpartida arancelaria', 'type' => 'text'],
                    ['key' => 'material', 'label' => 'Material de fabricación', 'type' => 'text', 'wide' => true],
                    ['key' => 'description', 'label' => 'Nombre o descripción técnica', 'type' => 'textarea', 'wide' => true],
                    ['key' => 'business_use', 'label' => 'Uso o aplicación del producto', 'type' => 'textarea', 'wide' => true],
                ],
            ],
            'batch' => [
                'label' => 'Información exclusiva del lote - Esquema 1B',
                'note' => 'El número de lote o serial es obligatorio para solicitudes bajo esquema 1B.',
                'fields' => [
                    ['key' => 'lot_serial', 'label' => 'Número de lote o serial', 'type' => 'text', 'required' => true],
                    ['key' => 'invoice_number', 'label' => 'Factura del lote', 'type' => 'text'],
                ],
            ],
            'electric' => [
                'label' => 'Características eléctricas',
                'note' => 'Información obligatoria para este reglamento. Si una característica no corresponde al producto, escribe "No aplica".',
                'fields' => [
                    ['key' => 'technical_standard', 'label' => 'Norma técnica aplicable', 'type' => 'text', 'wide' => true, 'required' => true],
                    ['key' => 'voltage', 'label' => 'Tensión nominal (Vn)', 'type' => 'text', 'required' => true],
                    ['key' => 'current', 'label' => 'Corriente nominal y máxima (In)', 'type' => 'text', 'required' => true],
                    ['key' => 'frequency', 'label' => 'Frecuencia', 'type' => 'text', 'required' => true],
                    ['key' => 'ip_rating', 'label' => 'Grado de protección IP', 'type' => 'text', 'required' => true],
                    ['key' => 'power', 'label' => 'Potencia, balasto, eficacia u otras características', 'type' => 'textarea', 'wide' => true, 'required' => true],
                    ['key' => 'bulb_type', 'label' => 'Tipo de bombilla, uso o tipo eléctrico', 'type' => 'text', 'wide' => true, 'required' => true],
                ],
            ],
            'toys' => [
                'label' => 'Características para juguetes',
                'note' => 'Información obligatoria para validar el alcance de juguetes.',
                'fields' => [
                    ['key' => 'age_range', 'label' => 'Edad de uso', 'type' => 'text', 'required' => true],
                    ['key' => 'battery_powered', 'label' => '¿Usa baterías o medio eléctrico?', 'type' => 'select', 'options' => ['Sí', 'No', 'No aplica'], 'required' => true],
                ],
            ],
            'tableware' => [
                'label' => 'Características para vajillas',
                'note' => 'Información obligatoria para validar presentación, acabado y capacidad. Usa "No aplica" cuando corresponda.',
                'fields' => [
                    ['key' => 'presentation', 'label' => 'Presentación', 'type' => 'text', 'wide' => true, 'required' => true],
                    ['key' => 'color_finish', 'label' => 'Color o acabado', 'type' => 'text', 'required' => true],
                    ['key' => 'capacity', 'label' => 'Capacidad (l o ml)', 'type' => 'text', 'required' => true],
                ],
            ],
            'metals' => [
                'label' => 'Características para barras y mallas',
                'note' => 'La norma del material y las dimensiones son obligatorias para esta clasificación.',
                'fields' => [
                    ['key' => 'material_standard', 'label' => 'Norma aplicable al material', 'type' => 'text', 'wide' => true, 'required' => true],
                    ['key' => 'dimensions', 'label' => 'Dimensiones', 'type' => 'text', 'wide' => true, 'required' => true],
                ],
            ],
            'helmets' => [
                'label' => 'Características para cascos',
                'note' => 'Esta respuesta es obligatoria para validar la clasificación del casco.',
                'fields' => [
                    ['key' => 'chin_protection', 'label' => '¿Cuenta con protección de la barbilla?', 'type' => 'select', 'options' => ['Sí', 'No', 'No aplica'], 'required' => true],
                ],
            ],
            'traceability' => [
                'label' => 'Trazabilidad final',
                'note' => 'Información complementaria; puede completarse durante la revisión documental.',
                'fields' => [
                    ['key' => 'manufacture_date', 'label' => 'Fecha de fabricación', 'type' => 'date'],
                    ['key' => 'manufacturer_importer_id', 'label' => 'ID del fabricante o importador', 'type' => 'text'],
                ],
            ],
        ],
        'profiles' => [
            'RETIE_40117' => ['common', 'electric', 'traceability'],
            'RETILAP_40150' => ['common', 'electric', 'traceability'],
            'VOLUNTARIO' => ['common', 'electric', 'traceability'],
            'RTE_INEN_057_2R' => ['common', 'electric', 'traceability'],
            'RTE_INEN_069_1R' => ['common', 'electric', 'traceability'],
            'RTS_29' => ['common', 'electric', 'traceability'],
            'JUGUETES_CO_0686' => ['common', 'toys', 'traceability'],
            'JUGUETES_EC_089' => ['common', 'toys', 'traceability'],
            'VAJILLAS_1440' => ['common', 'tableware', 'traceability'],
            'MALLAS_0277' => ['common', 'metals', 'traceability'],
            'MALLAS_1019' => ['common', 'metals', 'traceability'],
            'BARRAS_2003' => ['common', 'metals', 'traceability'],
            'CASCOS_1080' => ['common', 'helmets', 'traceability'],
        ],
        'default_profile' => ['common', 'traceability'],
    ];
}

function product_technical_field_keys(): array
{
    $keys = [
        'subfamily_detail',
        'classification_category',
        'classification_item',
        'classification_family',
        'classification_subfamily',
        'classification_criterion_1',
        'classification_criterion_2',
        'classification_observation',
        'retie_family',
        'retie_criterion_1',
        'retie_criterion_2',
        'retie_subfamily',
        'retie_observation',
    ];
    foreach (product_technical_fields_config()['groups'] as $group) {
        foreach ($group['fields'] as $field) {
            $keys[] = (string) $field['key'];
        }
    }
    return array_values(array_unique($keys));
}

function product_retie_family_catalog(): array
{
    static $catalog = null;
    if (is_array($catalog)) {
        return $catalog;
    }

    $path = dirname(__DIR__, 2) . '/storage/catalogos/retie_familias_subfamilias.json';
    if (!is_file($path)) {
        return $catalog = [];
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    return $catalog = is_array($decoded['familias'] ?? null) ? array_values($decoded['familias']) : [];
}

function product_retie_classification_selection(int $familyIndex, int $optionIndex): ?array
{
    $family = product_retie_family_catalog()[$familyIndex] ?? null;
    $option = is_array($family) ? ($family['opciones'][$optionIndex] ?? null) : null;
    if (!is_array($family) || !is_array($option)) {
        return null;
    }

    return [
        'numero' => (string) ($family['numero'] ?? ''),
        'familia' => (string) ($family['familia'] ?? ''),
        'criterio_1' => (string) ($option['criterio_1'] ?? ''),
        'criterio_2' => (string) ($option['criterio_2'] ?? ''),
        'subfamilia' => (string) ($option['subfamilia'] ?? ''),
        'observacion' => (string) ($option['observacion'] ?? ''),
        'pagina' => (string) ($option['pagina'] ?? ''),
        'etiqueta' => (string) ($option['etiqueta'] ?? ''),
    ];
}

function product_technical_fields_for_regulation(string $regulationCode): array
{
    $config = product_technical_fields_config();
    $groupKeys = $config['profiles'][$regulationCode] ?? $config['default_profile'];
    $fields = [];
    foreach ($groupKeys as $groupKey) {
        foreach (($config['groups'][$groupKey]['fields'] ?? []) as $field) {
            $fields[] = $field;
        }
    }
    return $fields;
}

function product_required_technical_fields_for_regulation(string $regulationCode): array
{
    return array_values(array_filter(
        product_technical_fields_for_regulation($regulationCode),
        static fn(array $field): bool => !empty($field['required'])
    ));
}

function product_rule_normalize(string $value): string
{
    $value = strtr($value, [
        "\u{00E1}" => 'a', "\u{00E9}" => 'e', "\u{00ED}" => 'i', "\u{00F3}" => 'o', "\u{00FA}" => 'u',
        "\u{00C1}" => 'a', "\u{00C9}" => 'e', "\u{00CD}" => 'i', "\u{00D3}" => 'o', "\u{00DA}" => 'u',
        "\u{00F1}" => 'n', "\u{00D1}" => 'n', "\u{00FC}" => 'u', "\u{00DC}" => 'u',
    ]);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = strtolower($ascii !== false ? $ascii : $value);
    return trim((string) (preg_replace('/[^a-z0-9]+/', ' ', $value) ?? ''));
}

function product_subfamily_reference_rules(): array
{
    return [
        ['match' => ['aisladores electricos'], 'options' => [
            'Aislador de porcelana tipo poste', 'Aislador de porcelana tipo pin', 'Aislador de vidrio por nivel de tensión y carga mecánica',
            'Aislador polimérico de silicona tipo suspensión', 'Aislador polimérico tipo pin', 'Aislador polimérico tipo poste',
            'Aislador en resina tipo poste menor o igual a 1.000 V', 'Aislador en resina tipo poste mayor a 1.000 V',
            'Aislador en resina de otra configuración', 'Espaciador o separador de fases por nivel de tensión',
            'Pasatapas para transformador por nivel de tensión',
        ]],
        ['match' => ['baterias o sistemas de acumulacion'], 'options' => [
            'Batería de plomo-ácido', 'Batería de níquel-cadmio', 'Batería de ion de litio', 'Banco o paquete de baterías',
        ]],
        ['match' => ['cargadores de baterias'], 'options' => [
            'Carga doméstica Schuko', 'Conector Tipo 1', 'Conector Tipo 2', 'Conector CCS', 'Conector CHAdeMO',
        ]],
        ['match' => ['cajas de empalme', 'cajas y armarios'], 'options' => [
            'Caja metálica para aparatos', 'Caja plástica para aparatos', 'Caja híbrida', 'Caja para medidores',
            'Caja portaborneras', 'Caja de empalme', 'Conduleta metálica', 'Conduleta plástica',
        ]],
        ['match' => ['cintas aislantes'], 'options' => ['Cinta aislante de PVC', 'Cinta aislante de caucho']],
        ['match' => ['clavijas y tomacorrientes'], 'options' => [
            'Uso doméstico o similar hasta 32 A y 250 V', 'Uso industrial mayor a 15 A hasta 1.000 V',
            'Tomacorriente GFCI', 'Tomacorriente grado hospitalario', 'Tomacorriente industrial hasta 125 A',
        ]],
        ['match' => ['condensadores de potencia'], 'options' => [
            'Condensador de baja tensión', 'Condensador de media tensión', 'Banco de condensadores por capacidad nominal',
        ]],
        ['match' => ['conductores y cables'], 'options' => ['Subfamilia definida por la norma de fabricación']],
        ['match' => ['elementos de conexion'], 'options' => [
            'Conector para red aérea', 'Conector para red subterránea', 'Conector para puesta a tierra',
            'Conector para tablero eléctrico', 'Conector aislado para uso interior y control', 'Conector para aparato',
            'Conector separable aislado', 'Conector para subestación', 'Bornera o bloque terminal', 'Empalme por norma técnica',
        ]],
        ['match' => ['equipos de maniobra y control'], 'options' => [
            'Arrancador de estado sólido', 'Arrancador electromagnético a tensión plena', 'Arrancador electromagnético a tensión reducida',
            'Contactor por norma y nivel de tensión', 'Pulsador por tipo', 'Relé térmico', 'Relé termomagnético', 'Relé electrónico',
        ]],
        ['match' => ['dps dispositivos'], 'options' => [
            'DPS menor a 1.000 V por clase o tipo', 'DPS de distribución 2,5 kA', 'DPS de distribución 5 kA',
            'DPS de distribución 10 kA', 'DPS de estación 10 kA', 'DPS de estación 20 kA',
        ]],
        ['match' => ['duchas y calentadores'], 'options' => ['Clase 0', 'Clase 0I', 'Clase I', 'Clase II']],
        ['match' => ['electrobombas'], 'options' => [
            'Superficial hasta 15 hp', 'Superficial mayor a 15 hp hasta 50 hp', 'Superficial mayor a 50 hp',
            'Sumergible hasta 15 hp', 'Sumergible mayor a 15 hp hasta 50 hp', 'Sumergible mayor a 50 hp',
        ]],
        ['match' => ['electrodos de puesta'], 'options' => [
            'Varilla de cobre', 'Pletina de cobre', 'Acero inoxidable', 'Acero recubierto en cobre',
            'Acero con recubrimiento galvanizado', 'Puesta a tierra temporal por nivel de tensión',
        ]],
        ['match' => ['extensiones y multitomas'], 'options' => [
            'Hasta 250 V y menor a 50 A', 'Mayor a 250 V y menor a 50 A',
            'Hasta 250 V y mayor o igual a 50 A', 'Mayor a 250 V y mayor o igual a 50 A',
        ]],
        ['match' => ['fusibles y portafusibles'], 'options' => [
            'Hasta 10 kA', 'Mayor a 10 kA hasta 50 kA', 'Mayor a 50 kA', 'Por nivel de tensión y norma técnica',
        ]],
        ['match' => ['interruptores y equipos'], 'options' => [
            'Interruptor enchufable o tipo riel', 'Interruptor limitador de corriente', 'Interruptor de disparo instantáneo',
            'Interruptor con protección contra falla a tierra', 'Interruptor con fusible integrado', 'Interruptor ajustable',
            'Interruptor removible', 'Interruptor diferencial', 'Interruptor en aire',
            'Interruptor de media tensión por sistema de interrupción y capacidad en kA',
        ]],
        ['match' => ['inversores'], 'options' => ['Inversor hasta 50 kW', 'Inversor mayor a 50 kW']],
        ['match' => ['motores generadores'], 'options' => [
            'Motor monofásico de baja tensión', 'Motor trifásico de baja tensión', 'Motor de corriente continua',
            'Motor sincrónico', 'Motor de media tensión', 'Generador hasta 7,5 kVA',
            'Generador mayor a 7,5 kVA hasta 75 kVA', 'Generador mayor a 75 kVA hasta 400 kVA', 'Generador mayor a 400 kVA',
        ]],
        ['match' => ['paneles solares'], 'options' => ['Panel monocristalino', 'Panel policristalino', 'Panel amorfo', 'Otro tipo fotovoltaico']],
        ['match' => ['postes crucetas', 'postes exclusivos'], 'options' => [
            'Poste de concreto', 'Poste metálico', 'Poste de madera', 'Poste en PRFV',
            'Cruceta por tipo de material', 'Torrecilla', 'Estructura para línea de transmisión',
        ]],
        ['match' => ['sistemas de bandejas'], 'options' => [
            'Bandeja portacables por tipo y material', 'Canal o conducto cerrado metálico', 'Canal o conducto cerrado no metálico',
            'Electrobarra de baja tensión por capacidad de corriente', 'Electrobarra de media tensión',
            'Tubo conduit metálico por tipo', 'Tubo conduit no metálico por tipo',
        ]],
        ['match' => ['tableros electricos y celdas'], 'options' => [
            'Celda de medida', 'Celda de corte y seccionamiento', 'Celda de protección y transferencia',
            'Celda aislada en aire', 'Celda aislada en gas', 'Tablero de potencia', 'Banco de condensadores',
            'Tablero de transferencia', 'Tablero de sincronismo', 'Centro de control de motores',
            'Tablero general de acometidas', 'Tablero de control', 'Tablero de distribución hasta 250 A',
        ]],
        ['match' => ['transformadores electricos'], 'options' => [
            'Transformador sumergido en líquido refrigerante', 'Transformador tipo pedestal',
            'Transformador seco aislado en aire', 'Transformador seco aislado en resina',
            'Hasta 1.000 V', 'Mayor a 1.000 V hasta 36 kV', 'Mayor a 36 kV',
        ]],
        ['match' => ['sistemas de potencia ininterrumpida'], 'options' => ['UPS monofásica', 'UPS bifásica', 'UPS trifásica']],
        ['match' => ['unidades de tension regulada'], 'options' => [
            'Regulador electromecánico', 'Regulador electromagnético', 'Regulador electrónico',
            'Monofásico', 'Bifásico', 'Trifásico',
        ]],
        ['match' => ['ascensores', 'andenes moviles', 'escaleras moviles', 'elevadores de carga', 'duplicadores de parqueo'], 'options' => [
            'Ascensor', 'Andén móvil', 'Escalera electromecánica', 'Pasillo móvil', 'Rampa para personas',
            'Grúa colgante', 'Elevador de carga', 'Polipasto', 'Duplicador de parqueo',
        ]],
    ];
}

function product_subfamily_references_for(string $family, string $product = ''): array
{
    $haystack = product_rule_normalize($family . ' ' . $product);
    foreach (product_subfamily_reference_rules() as $rule) {
        foreach ($rule['match'] as $needle) {
            if (str_contains($haystack, product_rule_normalize((string) $needle))) {
                return array_values(array_unique(array_map('strval', $rule['options'])));
            }
        }
    }
    return [];
}

function product_retilap_category_catalog(): array
{
    return [
        ['categoria' => 'Fuentes luminosas', 'items' => [
            ['item' => 1, 'producto' => 'Bombillas de estado sólido'],
            ['item' => 2, 'producto' => 'Tubos LED'],
            ['item' => 3, 'producto' => 'Bombillas de estado sólido decorativas'],
            ['item' => 4, 'producto' => 'Cintas LED'],
            ['item' => 5, 'producto' => 'Módulos LED'],
        ]],
        ['categoria' => 'Luminarias para espacios interiores', 'items' => [
            ['item' => 6, 'producto' => 'Luminarias para fuentes tubulares'],
            ['item' => 7, 'producto' => 'Luminarias para iluminación de emergencia'],
            ['item' => 8, 'producto' => 'Luminarias tipo Downlight'],
            ['item' => 9, 'producto' => 'Luminarias tipo Tracklight'],
            ['item' => 10, 'producto' => 'Paneles LED'],
            ['item' => 11, 'producto' => 'Luminarias Herméticas'],
            ['item' => 12, 'producto' => 'Luminarias High Bay'],
            ['item' => 13, 'producto' => 'Luminarias Lineales'],
            ['item' => 14, 'producto' => 'Luminarias de incrustar para interior'],
        ]],
        ['categoria' => 'Productos de iluminación para espacios exteriores', 'items' => [
            ['item' => 15, 'producto' => 'Apliques de sobreponer y de empotrar en pared para exterior'],
            ['item' => 16, 'producto' => 'Bolardos para iluminación'],
            ['item' => 17, 'producto' => 'Luminarias con dispositivos de generación fotovoltaica incorporados'],
            ['item' => 18, 'producto' => 'Luminarias para balizaje en aeropuertos (Sistemas de señalización de pistas) y luces aeronáuticas de superficie'],
            ['item' => 19, 'producto' => 'Proyectores para iluminación'],
            ['item' => 20, 'producto' => 'Luminarias para empotrar a piso'],
        ]],
        ['categoria' => 'Productos de iluminación para alumbrado público', 'items' => [
            ['item' => 21, 'producto' => 'Luminarias de alumbrado público'],
        ]],
        ['categoria' => 'Áreas clasificadas y especiales', 'items' => [
            ['item' => 22, 'producto' => 'Productos de iluminación para áreas clasificadas'],
            ['item' => 23, 'producto' => 'Productos de iluminación para áreas especiales'],
            ['item' => 24, 'producto' => 'Productos para áreas limpias'],
            ['item' => 25, 'producto' => 'Productos de iluminación para sitios de preparación de alimentos'],
            ['item' => 26, 'producto' => 'Productos de iluminación sumergibles para fuentes ornamentales de agua, piscinas, jacuzzis o similares'],
        ]],
        ['categoria' => 'Productos de iluminación para túneles', 'items' => [
            ['item' => 27, 'producto' => 'Luminarias de túneles'],
            ['item' => 28, 'producto' => 'Luminarias para balizaje en sistemas de túneles'],
            ['item' => 29, 'producto' => 'Luminarias de emergencia en sistemas de túneles'],
        ]],
        ['categoria' => 'Productos para iluminación decorativa', 'items' => [
            ['item' => 30, 'producto' => 'Luminarias decorativas'],
            ['item' => 31, 'producto' => 'Luminarias tipo guirnalda'],
        ]],
        ['categoria' => 'Accesorios eléctricos y electrónicos', 'items' => [
            ['item' => 32, 'producto' => 'Portabombillas, portalámparas, sockets y en general accesorios de soporte o conexión de cualquier fuente lumínica para uso de iluminación'],
            ['item' => 33, 'producto' => 'Fotocontroles y fotoceldas'],
            ['item' => 34, 'producto' => 'Bases para fotocontrol'],
            ['item' => 35, 'producto' => 'Dispositivos de control - Equipos para control automático de iluminación'],
            ['item' => 36, 'producto' => 'Atenuadores de intensidad luminosa (manuales y automáticos)'],
            ['item' => 37, 'producto' => 'Sensores para control de iluminación'],
            ['item' => 38, 'producto' => 'Drivers y balastos electrónicos'],
            ['item' => 39, 'producto' => 'Kit balasto - batería para sistemas de emergencia'],
        ]],
        ['categoria' => 'Productos usados en telegestión', 'items' => [
            ['item' => 40, 'producto' => 'Controladores de luminarias para Telegestión'],
            ['item' => 41, 'producto' => 'Concentradores (Gateway)'],
        ]],
    ];
}

function product_rule_tokens(string $value): array
{
    $stopWords = [
        'para', 'por', 'con', 'del', 'las', 'los', 'una', 'uno', 'otros', 'otro', 'tipo', 'uso',
        'producto', 'productos', 'electrico', 'electrica', 'electricos', 'electricas', 'sistema', 'sistemas',
    ];
    $tokens = preg_split('/\s+/', product_rule_normalize($value)) ?: [];
    $tokens = array_map(
        static fn(string $token): string => strlen($token) > 4 && str_ends_with($token, 's')
            ? substr($token, 0, -1)
            : $token,
        $tokens
    );
    $tokens = array_filter($tokens, static fn(string $token): bool => strlen($token) > 2 && !in_array($token, $stopWords, true));
    return array_values(array_unique($tokens));
}

function product_rule_coverage(string $source, string $target): float
{
    $sourceTokens = product_rule_tokens($source);
    if (!$sourceTokens) {
        return 0.0;
    }
    $targetTokens = product_rule_tokens($target);
    return count(array_intersect($sourceTokens, $targetTokens)) / count($sourceTokens);
}

function product_retie_option_is_compatible(string $product, string $optionText): bool
{
    $productValue = ' ' . product_rule_normalize($product) . ' ';
    $optionValue = ' ' . product_rule_normalize($optionText) . ' ';

    $typeRules = [
        ' pasatapas ' => [' pasatapas '],
        ' espaciadores ' => [' espaciador ', ' separador '],
        ' separadores ' => [' espaciador ', ' separador '],
        ' pin ' => [' pin '],
        ' suspension ' => [' suspension '],
    ];
    foreach ($typeRules as $productNeedle => $optionNeedles) {
        if (str_contains($productValue, $productNeedle)
            && !array_filter($optionNeedles, static fn(string $needle): bool => str_contains($optionValue, $needle))) {
            return false;
        }
    }

    $polymeric = str_contains($productValue, ' polimer') || str_contains($productValue, ' resina ')
        || str_contains($productValue, ' compuestos ');
    $ceramic = str_contains($productValue, ' porcelana ') || str_contains($productValue, ' vidrio ')
        || str_contains($productValue, ' ceramica ');
    if ($polymeric && !$ceramic
        && !(str_contains($optionValue, ' polimer') || str_contains($optionValue, ' resina '))) {
        return false;
    }
    if ($ceramic && !$polymeric
        && !(str_contains($optionValue, ' porcelana ') || str_contains($optionValue, ' vidrio ')
            || str_contains($optionValue, ' ceramica '))) {
        return false;
    }

    $lowVoltage = str_contains($productValue, ' baja tension ') || str_contains($productValue, ' hasta 600v ')
        || str_contains($productValue, ' menores a 1000v ');
    $mediumVoltage = str_contains($productValue, ' media tension ')
        || str_contains($productValue, ' media y alta tension ');
    $highVoltage = str_contains($productValue, ' alta tension ');
    if ($lowVoltage && !$mediumVoltage && !$highVoltage && !str_contains($optionValue, ' baja tension ')) {
        return false;
    }
    if ($mediumVoltage && !$lowVoltage && !$highVoltage && !str_contains($optionValue, ' media tension ')) {
        return false;
    }
    if ($highVoltage && !$lowVoltage && !$mediumVoltage && !str_contains($optionValue, ' alta tension ')) {
        return false;
    }
    if ($mediumVoltage && $highVoltage
        && !str_contains($optionValue, ' media tension ') && !str_contains($optionValue, ' alta tension ')) {
        return false;
    }
    return true;
}

function product_retilap_classification_for(string $product): ?array
{
    $productKey = product_rule_normalize($product);
    $best = null;
    $bestScore = 0.0;
    foreach (product_retilap_category_catalog() as $category) {
        foreach ($category['items'] as $item) {
            $officialKey = product_rule_normalize((string) $item['producto']);
            $score = $productKey === $officialKey ? 10.0 : product_rule_coverage($product, (string) $item['producto']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = array_merge(['categoria' => $category['categoria']], $item);
            }
        }
    }
    if ($best === null || $bestScore < 0.45) {
        return null;
    }
    $label = (string) $best['producto'];
    return [
        'categoria' => (string) $best['categoria'],
        'familia_oficial' => (string) $best['categoria'],
        'item' => (string) $best['item'],
        'fuente' => 'RETILAP 2024 - categorías de productos',
        'opciones' => [[
            'id' => 'retilap-' . $best['item'],
            'etiqueta' => $label,
            'criterio_1' => $label,
            'criterio_2' => '',
            'subfamilia' => $label,
            'observacion' => '',
            'pagina' => '',
            'probable' => true,
        ]],
    ];
}

function product_retie_official_numbers_for(string $family, string $product): ?array
{
    $productValue = product_rule_normalize($product);
    if (str_contains($productValue, 'rayos x')) {
        return [];
    }
    if (str_contains($productValue, 'baterias o sistemas de acumulacion')) {
        return ['4'];
    }
    if (str_contains($productValue, 'clavijas y tomacorrientes')) {
        return ['10', '48'];
    }
    if (str_contains($productValue, 'dps de media') || str_contains($productValue, 'dps de alta')) {
        return ['18'];
    }
    if (str_contains($productValue, 'dps de baja')) {
        return ['17'];
    }
    if (str_contains($productValue, 'electrodos de puesta a tierra')) {
        return ['21'];
    }
    if (str_contains($productValue, 'puestas a tierra temporales')) {
        return ['38'];
    }
    if (str_contains($productValue, 'contactores y arrancadores')) {
        return ['3', '13'];
    }
    if (str_contains($productValue, 'pulsadores y selectores')) {
        return ['39'];
    }
    if (str_contains($productValue, 'reles de proteccion')) {
        return ['41'];
    }
    if (str_contains($productValue, 'equipos de media tension seccionadores')) {
        return ['29', '40'];
    }
    if (str_contains($productValue, 'interruptores automaticos de baja')) {
        return ['27'];
    }
    if (str_contains($productValue, 'interruptores manuales de baja')) {
        return ['28'];
    }
    if (str_contains($productValue, 'motores y generadores electricos')) {
        return ['25', '31'];
    }
    if (str_contains($productValue, 'grupos electrogenos')) {
        return ['25'];
    }
    if (str_contains($productValue, 'crucetas')) {
        return ['15'];
    }
    if (str_starts_with($productValue, 'postes ')) {
        return ['34'];
    }
    if (str_contains($productValue, 'canalizaciones electricas prefabricadas') || str_contains($productValue, 'electroductos')) {
        return ['45'];
    }
    if (str_contains($productValue, 'bandejas portacables')) {
        return ['43'];
    }
    if (str_contains($productValue, 'conductos cerrados de seccion no circular') || str_contains($productValue, 'canales y de ductos')) {
        return ['44'];
    }
    if (str_contains($productValue, 'sistemas de tubos')) {
        return ['46'];
    }
    if (str_contains($productValue, 'celdas de media tension')) {
        return ['8'];
    }
    return null;
}

function product_retie_accredited_fallback(string $family, string $product): array
{
    return [
        'categoria' => $family,
        'familia_oficial' => $family,
        'item' => '',
        'fuente' => 'Alcance acreditado 17-CPR-002',
        'opciones' => [[
            'id' => substr(hash('sha256', $family . '|' . $product), 0, 16),
            'etiqueta' => $product,
            'criterio_1' => $product,
            'criterio_2' => '',
            'subfamilia' => $product,
            'observacion' => 'Clasificación pendiente de homologación con la tabla 4.2.2.a.',
            'pagina' => '',
            'probable' => true,
        ]],
    ];
}

function product_retie_classification_for(string $family, string $product): ?array
{
    static $cache = [];
    $cacheKey = product_rule_normalize($family . '|' . $product);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $officialCatalog = product_retie_family_catalog();
    $forcedNumbers = product_retie_official_numbers_for($family, $product);
    if ($forcedNumbers === []) {
        return $cache[$cacheKey] = product_retie_accredited_fallback($family, $product);
    }

    $matchedFamilies = [];
    if (is_array($forcedNumbers)) {
        $matchedFamilies = array_values(array_filter(
            $officialCatalog,
            static fn(array $officialFamily): bool => in_array((string) ($officialFamily['numero'] ?? ''), $forcedNumbers, true)
        ));
    }

    $bestFamilyScore = -1.0;
    if (!$matchedFamilies) {
        $bestFamily = null;
        foreach ($officialCatalog as $officialFamily) {
        $optionsText = implode(' ', array_map(
            static fn(array $option): string => implode(' ', [
                (string) ($option['criterio_1'] ?? ''),
                (string) ($option['criterio_2'] ?? ''),
                (string) ($option['subfamilia'] ?? ''),
                (string) ($option['observacion'] ?? ''),
            ]),
            (array) ($officialFamily['opciones'] ?? [])
        ));
        $familyScore = product_rule_coverage($family, (string) ($officialFamily['familia'] ?? ''));
        $reverseFamilyScore = product_rule_coverage((string) ($officialFamily['familia'] ?? ''), $family);
        $productScore = product_rule_coverage($product, $optionsText);
        $score = ($familyScore * 4.0) + $reverseFamilyScore + ($productScore * 2.0);
            if ($score > $bestFamilyScore) {
                $bestFamilyScore = $score;
                $bestFamily = $officialFamily;
            }
        }
        if (!is_array($bestFamily) || $bestFamilyScore < 0.25) {
            return $cache[$cacheKey] = product_retie_accredited_fallback($family, $product);
        }
        $matchedFamilies = [$bestFamily];
    }

    $options = [];
    $maximumScore = 0.0;
    $maximumCompatibleScore = 0.0;
    foreach ($matchedFamilies as $matchedFamily) {
      foreach ((array) ($matchedFamily['opciones'] ?? []) as $option) {
        $segments = [];
        foreach (['subfamilia', 'criterio_1', 'criterio_2'] as $key) {
            $segment = trim((string) ($option[$key] ?? ''));
            if ($segment !== '' && product_rule_normalize($segment) !== 'no aplica' && !in_array($segment, $segments, true)) {
                $segments[] = $segment;
            }
        }
        if (!$segments) {
            $segments[] = trim((string) ($option['observacion'] ?? '')) ?: 'No aplica';
        }
        $searchText = implode(' ', $segments);
        $score = product_rule_coverage($product, $searchText)
            + (product_rule_coverage($searchText, $product) * 0.5);
        $compatible = product_retie_option_is_compatible($product, $searchText);
        $maximumScore = max($maximumScore, $score);
        if ($compatible) {
            $maximumCompatibleScore = max($maximumCompatibleScore, $score);
        }
        $options[] = [
            'id' => substr(hash('sha256', implode('|', [
                (string) ($matchedFamily['numero'] ?? ''),
                (string) ($option['criterio_1'] ?? ''),
                (string) ($option['criterio_2'] ?? ''),
                (string) ($option['subfamilia'] ?? ''),
                (string) ($option['observacion'] ?? ''),
            ])), 0, 16),
            'etiqueta' => (count($matchedFamilies) > 1 ? 'Ítem ' . ($matchedFamily['numero'] ?? '') . ' - ' : '')
                . implode(' | ', $segments),
            'criterio_1' => (string) ($option['criterio_1'] ?? ''),
            'criterio_2' => (string) ($option['criterio_2'] ?? ''),
            'subfamilia' => (string) ($option['subfamilia'] ?? ''),
            'observacion' => (string) ($option['observacion'] ?? ''),
            'pagina' => (string) ($option['pagina'] ?? ''),
            'score' => $score,
            'compatible' => $compatible,
        ];
      }
    }
    $hasCompatibleOptions = $maximumCompatibleScore > 0.0;
    $probableThreshold = max(0.3, ($hasCompatibleOptions ? $maximumCompatibleScore : $maximumScore) - 0.06);
    foreach ($options as &$option) {
        $option['probable'] = count($matchedFamilies) > 1
            || (($hasCompatibleOptions ? $option['compatible'] : true) && $option['score'] >= $probableThreshold);
    }
    unset($option);
    usort($options, static function (array $left, array $right): int {
        if ($left['probable'] !== $right['probable']) {
            return $left['probable'] ? -1 : 1;
        }
        $scoreComparison = ($right['score'] <=> $left['score']);
        if ($scoreComparison !== 0) {
            return $scoreComparison;
        }
        return strcasecmp((string) $left['etiqueta'], (string) $right['etiqueta']);
    });
    foreach ($options as &$option) {
        unset($option['score'], $option['compatible']);
    }
    unset($option);

    return $cache[$cacheKey] = [
        'categoria' => $family,
        'familia_oficial' => implode(' / ', array_map(
            static fn(array $matchedFamily): string => (string) ($matchedFamily['familia'] ?? ''),
            $matchedFamilies
        )),
        'item' => implode(' / ', array_map(
            static fn(array $matchedFamily): string => (string) ($matchedFamily['numero'] ?? ''),
            $matchedFamilies
        )),
        'fuente' => 'Tabla 4.2.2.a RETIE 2026',
        'opciones' => array_values($options),
    ];
}

function product_scope_recommended_options(?array $classification): array
{
    $options = is_array($classification) ? array_values((array) ($classification['opciones'] ?? [])) : [];
    $recommended = array_values(array_filter(
        $options,
        static fn(array $option): bool => !empty($option['probable'])
    ));
    return $recommended ?: $options;
}

function product_scope_classification_for(string $regulationCode, string $family, string $product): ?array
{
    if ($regulationCode === 'RETIE_40117') {
        return product_retie_classification_for($family, $product);
    }
    if ($regulationCode === 'RETILAP_40150') {
        return product_retilap_classification_for($product);
    }
    return null;
}
