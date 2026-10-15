<?php
declare(strict_types=1);

function certification_service_rules(): array
{
    static $rules = null;
    if (is_array($rules)) {
        return $rules;
    }

    $lowerSchemeNote = 'En caso de no cumplir los requisitos, se debe reevaluar con un esquema inferior.';
    $missingImplementationNote = 'No se declaró un sistema de gestión implementado. El esquema 4 requiere una evaluación detallada del sistema de producción del fabricante y puede requerir una auditoría presencial en sus instalaciones.';
    $rules = [
        'iso_statuses' => [
            'certificacion_vigente' => 'Certificación vigente ISO 9001',
            'implementacion' => 'Implementado',
            'ninguna' => 'Ninguna',
        ],
        'scheme_help' => [
            '1A' => [
                'summary' => 'Certificación de una unidad única o prototipo antes de su producción en masa.',
                'applies' => 'Aplica solamente a la muestra evaluada; no cubre comercialización continua.',
                'validity' => 'Sin vigencia predeterminada para producción futura.',
                'follow_up' => 'No tiene mantenimientos ni seguimientos.',
                'conditions' => 'Solo se ensaya una muestra y puede sufrir daños durante las pruebas. La certificación depende de su conformidad.',
            ],
            '1B' => [
                'summary' => 'Certificación de cantidades exactas pertenecientes a un lote específico.',
                'applies' => 'Aplica cuando no se requiere comercialización continua del producto.',
                'validity' => 'Cubre exclusivamente el lote y las cantidades declaradas.',
                'follow_up' => 'No tiene mantenimiento; un nuevo lote requiere nuevas muestras y ensayos.',
                'conditions' => 'El cliente debe disponer las muestras. Las no conformidades deben corregirse y los ensayos se cotizan nuevamente.',
            ],
            '1B_RETILAP' => [
                'summary' => 'Certificación RETILAP de cantidades exactas pertenecientes a un lote específico.',
                'applies' => 'Aplica cuando no se requiere comercialización continua del producto.',
                'validity' => 'Cubre exclusivamente el lote y las cantidades declaradas.',
                'follow_up' => 'No tiene mantenimiento; un nuevo lote requiere nuevas muestras y ensayos.',
                'conditions' => 'El cliente debe disponer las muestras y cuenta con hasta 90 días para gestionar no conformidades.',
            ],
            '1B_RETIE' => [
                'summary' => 'Certificación RETIE de cantidades exactas pertenecientes a un lote específico.',
                'applies' => 'Aplica cuando no se requiere comercialización continua del producto.',
                'validity' => 'Cubre exclusivamente el lote y las cantidades declaradas.',
                'follow_up' => 'No tiene mantenimiento; un nuevo lote requiere nuevas muestras y ensayos.',
                'conditions' => 'El cliente debe disponer las muestras y cuenta con hasta 90 días para gestionar no conformidades.',
            ],
            '4_RETILAP' => [
                'summary' => 'Certificación continua para fabricantes sin un sistema de gestión certificado con alcance de fabricación.',
                'applies' => 'Requiere evaluar y aprobar el sistema de control de producción del fabricante.',
                'validity' => 'Dos años de comercialización continua mientras se mantengan las condiciones evaluadas.',
                'follow_up' => 'Seguimiento a los 12 meses y renovación a los 24 meses.',
                'conditions' => 'Se requieren muestras en cada mantenimiento. Las no conformidades deben gestionarse dentro de 90 días.',
            ],
            '4_RETIE' => [
                'summary' => 'Certificación continua para fabricantes sin un sistema de gestión certificado con alcance de fabricación.',
                'applies' => 'Requiere evaluar y aprobar el sistema de control de producción del fabricante.',
                'validity' => 'Dos años de comercialización continua mientras se mantengan las condiciones evaluadas.',
                'follow_up' => 'Seguimiento a los 12 meses y renovación a los 24 meses.',
                'conditions' => 'Se requieren muestras en cada mantenimiento. Las no conformidades deben gestionarse dentro de 90 días.',
            ],
            '5' => [
                'summary' => 'Certificación para comercialización continua con control directo de la fabricación.',
                'applies' => 'El fabricante debe tener un sistema de gestión implementado o certificado que cubra la manufactura.',
                'validity' => 'Tres años de cobertura para las referencias certificadas.',
                'follow_up' => 'Seguimientos a los 12 y 24 meses; renovación a los 36 meses.',
                'conditions' => 'Exige muestras en los mantenimientos y aprobar la evaluación del sistema de gestión o del control de producción.',
            ],
            '5_RETILAP' => [
                'summary' => 'Certificación RETILAP para comercialización continua con control directo de la fabricación.',
                'applies' => 'El sistema de gestión certificado debe estar vigente y cubrir la manufactura de los productos.',
                'validity' => 'Cinco años de cobertura para las referencias certificadas.',
                'follow_up' => 'Seguimientos a los 12 y 32 meses; renovación a los 60 meses.',
                'conditions' => 'Exige muestras en los mantenimientos y validar la vigencia y el alcance del certificado del sistema de gestión.',
            ],
            '5_RETIE' => [
                'summary' => 'Certificación RETIE para comercialización continua con control directo de la fabricación.',
                'applies' => 'El sistema de gestión certificado debe estar vigente y cubrir la manufactura de los productos.',
                'validity' => 'Cinco años de cobertura para las referencias certificadas.',
                'follow_up' => 'Seguimientos a los 12 y 32 meses; renovación a los 60 meses.',
                'conditions' => 'Exige muestras en los mantenimientos y validar la vigencia y el alcance del certificado del sistema de gestión.',
            ],
        ],
        'purposes' => [
            '1A' => ['Otorgamiento'],
            '1B' => ['Otorgamiento'],
            '1B_RETILAP' => ['Otorgamiento'],
            '4_RETILAP' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
            '5_RETILAP' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
            '1B_RETIE' => ['Otorgamiento'],
            '4_RETIE' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
            '5_RETIE' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
            '4' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
            '5' => ['Otorgamiento', 'Seguimiento 1', 'Seguimiento 2', 'Renovación'],
        ],
        'recommendations' => [
            'certificacion_vigente' => [
                '1A' => ['recommended' => '1A', 'optional' => ''],
                '1B' => ['recommended' => '1B', 'optional' => ''],
                '5' => ['recommended' => '5 + Verificación ISO', 'optional' => ''],
                '1B_RETILAP' => ['recommended' => '1B RETILAP', 'optional' => ''],
                '4_RETILAP' => ['recommended' => '4 RETILAP + Verificación ISO', 'optional' => ''],
                '5_RETILAP' => ['recommended' => '5 RETILAP + Verificación ISO', 'optional' => ''],
                '1B_RETIE' => ['recommended' => '1B RETIE', 'optional' => ''],
                '4_RETIE' => ['recommended' => '4 RETIE + Verificación ISO', 'optional' => ''],
                '5_RETIE' => ['recommended' => '5 RETIE + Verificación ISO', 'optional' => ''],
            ],
            'implementacion' => [
                '1A' => ['recommended' => '1A', 'optional' => ''],
                '1B' => ['recommended' => '1B', 'optional' => ''],
                '5' => [
                    'recommended' => '5 + evaluación del sistema de control de producción',
                    'optional' => '',
                ],
                '1B_RETILAP' => ['recommended' => '1B RETILAP', 'optional' => ''],
                '4_RETILAP' => ['recommended' => '4 RETILAP + evaluación del sistema de control de producción', 'optional' => ''],
                '5_RETILAP' => [
                    'recommended' => 'Recomendable: primero certificar ISO 9001 y luego 5 RETILAP + Verificación ISO, o implementar ISO 9001 y luego 4 RETILAP + EV del sistema de control de producción.',
                    'optional' => $lowerSchemeNote,
                ],
                '1B_RETIE' => ['recommended' => '1B RETIE', 'optional' => ''],
                '4_RETIE' => ['recommended' => '4 RETIE + evaluación del sistema de control de producción', 'optional' => ''],
                '5_RETIE' => [
                    'recommended' => 'Recomendable: primero certificar ISO 9001 y luego 5 RETIE + Verificación ISO.',
                    'optional' => $lowerSchemeNote,
                ],
            ],
            'ninguna' => [
                '1A' => ['recommended' => '1A', 'optional' => ''],
                '1B' => ['recommended' => '1B', 'optional' => ''],
                '5' => ['recommended' => 'Primero implementar SGC/CSG y luego 5 + EV SGC', 'optional' => $lowerSchemeNote],
                '1B_RETILAP' => ['recommended' => '1B RETILAP', 'optional' => ''],
                '4_RETILAP' => [
                    'recommended' => '4 RETILAP + evaluación detallada del sistema de producción del fabricante.',
                    'optional' => $missingImplementationNote,
                ],
                '5_RETILAP' => [
                    'recommended' => 'Recomendable: primero implementar y certificar ISO 9001 y luego 5 RETILAP + Verificación ISO, o implementar ISO 9001 y luego 4 RETILAP + EV del sistema de control de producción.',
                    'optional' => $lowerSchemeNote,
                ],
                '1B_RETIE' => ['recommended' => '1B RETIE', 'optional' => ''],
                '4_RETIE' => [
                    'recommended' => '4 RETIE + evaluación detallada del sistema de producción del fabricante.',
                    'optional' => $missingImplementationNote,
                ],
                '5_RETIE' => [
                    'recommended' => 'Recomendable: primero implementar y certificar ISO 9001 y luego 5 RETIE + Verificación ISO, o implementar ISO 9001 y luego 4 RETIE + EV del sistema de control de producción.',
                    'optional' => $lowerSchemeNote,
                ],
            ],
        ],
    ];

    return $rules;
}

function certification_scheme_key(string $scheme): string
{
    $scheme = preg_replace('/^Esquema\s+/i', '', trim($scheme)) ?? '';
    $scheme = str_replace('.', '', $scheme);
    return strtoupper((string) (preg_replace('/[\s-]+/', '_', $scheme) ?? ''));
}

function certification_purposes_for_scheme(string $scheme): array
{
    $rules = certification_service_rules();
    return $rules['purposes'][certification_scheme_key($scheme)] ?? [];
}

function certification_recommendation_for(string $isoStatus, string $scheme): ?array
{
    $rules = certification_service_rules();
    $recommendation = $rules['recommendations'][$isoStatus][certification_scheme_key($scheme)] ?? null;
    return is_array($recommendation) ? $recommendation : null;
}

function certification_iso_status_label(string $isoStatus): string
{
    $rules = certification_service_rules();
    return (string) ($rules['iso_statuses'][$isoStatus] ?? $isoStatus);
}
