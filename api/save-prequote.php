<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

$authUser = auth_require_login();

function redirect_prequote(string $status, string $message, array $extra = []): void
{
    header('Location: ../pre_cotizacion.php?' . http_build_query(array_merge([
        'quote_status' => $status,
        'quote_message' => $message,
    ], $extra)));
    exit;
}

function prequote_decimal(mixed $value): float
{
    $text = trim((string) $value);
    $text = str_replace(['$', 'COP', 'cop', ' '], '', $text);
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_prequote('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $quoteId = (int) ($_POST['precotizacion_id'] ?? 0);
    $isUpdate = $quoteId > 0;
    if ($isUpdate) {
        if (!auth_can('prequotes.create', $authUser) && !auth_can('prequotes.edit', $authUser)) {
            throw new RuntimeException('Tu perfil no puede editar pre-cotizaciones.');
        }
    } elseif (!auth_can('prequotes.create', $authUser)) {
        throw new RuntimeException('Tu perfil no puede crear pre-cotizaciones.');
    }

    $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);
    $rentabilidad = prequote_decimal($_POST['rentabilidad_porcentaje'] ?? 10);
    $requestLabel = trim((string) ($_POST['request_label'] ?? 'Solicitud sin codigo'));
    $serviceMode = (string) ($_POST['tipo_servicio'] ?? '');
    $submitAction = (string) ($_POST['submit_action'] ?? 'guardar');
    if (!in_array($serviceMode, ['certificacion', 'certificacion_ensayos'], true)) {
        throw new RuntimeException('La modalidad solo ensayos no está disponible. Selecciona certificación o certificación con ensayos.');
    }

    $codes = $_POST['codigo'] ?? [];
    $labEssayIds = $_POST['laboratorio_ensayo_id'] ?? [];
    $labIds = $_POST['laboratorio_id'] ?? [];
    $essays = $_POST['ensayo'] ?? [];
    $products = $_POST['producto'] ?? [];
    $quantities = $_POST['cantidad'] ?? [];
    $methods = $_POST['metodo_norma'] ?? [];
    $costs = $_POST['costo_unitario'] ?? [];
    $values = $_POST['valor_unitario'] ?? [];

    $serviceNames = $_POST['servicio'] ?? [];
    $serviceResources = $_POST['recurso'] ?? [];
    $serviceQuantities = $_POST['servicio_cantidad'] ?? [];
    $serviceUnits = $_POST['servicio_unidad'] ?? [];
    $serviceValues = $_POST['servicio_valor_unitario'] ?? [];
    $serviceTypes = $_POST['servicio_tipo'] ?? [];
    $serviceOrders = $_POST['servicio_orden'] ?? [];

    $pdo = db();
    $existingQuote = null;
    if ($isUpdate) {
        $existingStatement = $pdo->prepare('SELECT * FROM precotizaciones WHERE id = :id');
        $existingStatement->execute(['id' => $quoteId]);
        $existingQuote = $existingStatement->fetch() ?: null;
        if (!$existingQuote) {
            throw new RuntimeException('La pre-cotización que intentas editar no existe.');
        }
        if (in_array((string) $existingQuote['estado'], ['aprobada', 'archivada'], true)) {
            throw new RuntimeException('La pre-cotización aprobada o archivada no puede modificarse.');
        }
        $solicitudId = (int) $existingQuote['solicitud_servicio_id'];
    }
    if ($solicitudId <= 0 || !auth_can_access_request($pdo, $solicitudId)) {
        throw new RuntimeException('No tienes acceso a la solicitud seleccionada.');
    }
    if (!$isUpdate) {
        $reviewStatement = $pdo->prepare(
            "SELECT COUNT(*) FROM revisiones_tecnicas
             WHERE solicitud_servicio_id = :solicitud AND estado = 'aprobada'"
        );
        $reviewStatement->execute(['solicitud' => $solicitudId]);
        if ((int) $reviewStatement->fetchColumn() === 0) {
            throw new RuntimeException('La solicitud debe tener una revisión técnica aprobada antes de crear la precotización.');
        }
    }
    $pdo->beginTransaction();

    $quoteCode = $existingQuote ? (string) $existingQuote['codigo'] : 'PRE-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2));
    $quoteName = $existingQuote ? (string) $existingQuote['nombre'] : 'Pre-cotizacion ' . $requestLabel;
    if ($submitAction === 'aprobar' && !auth_can('prequotes.approve', $authUser)) {
        throw new RuntimeException('Tu perfil no puede aprobar pre-cotizaciones para la cotización final.');
    }
    $targetState = $submitAction === 'aprobar' ? 'aprobada' : 'borrador';

    $subtotalCosts = 0.0;
    $subtotalValues = 0.0;
    $serviceSubtotal = 0.0;
    $otherSubtotal = 0.0;

    $essayRows = [];
    foreach ($serviceMode === 'certificacion_ensayos' ? array_keys($essays) : [] as $index) {
        $essay = trim((string) ($essays[$index] ?? ''));
        if ($essay === '') {
            continue;
        }

        $quantity = prequote_decimal($quantities[$index] ?? 0);
        $cost = prequote_decimal($costs[$index] ?? 0);
        $value = prequote_decimal($values[$index] ?? 0);
        $subtotalCost = $quantity * $cost;
        $subtotalValue = $quantity * $value;

        $essayRows[] = [
            'laboratorio_ensayo_id' => (int) ($labEssayIds[$index] ?? 0) ?: null,
            'laboratorio_id' => (int) ($labIds[$index] ?? 0) ?: null,
            'codigo' => trim((string) ($codes[$index] ?? '')),
            'ensayo' => $essay,
            'producto' => trim((string) ($products[$index] ?? '')),
            'cantidad' => $quantity,
            'metodo_norma' => trim((string) ($methods[$index] ?? '')),
            'costo_unitario' => $cost,
            'valor_unitario' => $value,
            'subtotal_costo' => $subtotalCost,
            'subtotal_valor' => $subtotalValue,
        ];
        $subtotalCosts += $subtotalCost;
        $subtotalValues += $subtotalValue;
    }

    $serviceRows = [];
    foreach (array_keys($serviceNames) as $index) {
        $name = trim((string) ($serviceNames[$index] ?? ''));
        if ($name === '') {
            continue;
        }

        $quantity = prequote_decimal($serviceQuantities[$index] ?? 0);
        $unitValue = prequote_decimal($serviceValues[$index] ?? 0);
        $subtotal = $quantity * $unitValue;
        $type = in_array(($serviceTypes[$index] ?? 'certificacion'), ['certificacion', 'otro'], true) ? $serviceTypes[$index] : 'certificacion';

        $serviceRows[] = [
            'servicio' => $name,
            'recurso' => trim((string) ($serviceResources[$index] ?? '')),
            'cantidad' => $quantity,
            'unidad' => in_array(($serviceUnits[$index] ?? 'horas'), ['horas', 'cantidad'], true) ? $serviceUnits[$index] : 'horas',
            'valor_unitario' => $unitValue,
            'subtotal_valor' => $subtotal,
            'tipo' => $type,
            'orden' => (int) ($serviceOrders[$index] ?? 0),
        ];

        if ($type === 'otro') {
            $otherSubtotal += $subtotal;
        } else {
            $serviceSubtotal += $subtotal;
        }
    }

    $taxBase = $subtotalValues + $serviceSubtotal + $otherSubtotal;
    $iva = $taxBase * 0.19;
    $total = $taxBase + $iva;

    $quotePayload = [
        'solicitud_servicio_id' => $solicitudId > 0 ? $solicitudId : null,
        'codigo' => $quoteCode,
        'nombre' => $quoteName,
        'estado' => $targetState,
        'rentabilidad_porcentaje' => $rentabilidad,
        'subtotal_costos' => $subtotalCosts,
        'subtotal_valores' => $subtotalValues,
        'subtotal_servicios' => $serviceSubtotal,
        'subtotal_otros' => $otherSubtotal,
        'iva' => $iva,
        'total' => $total,
        'datos_json' => json_encode(['request_label' => $requestLabel, 'tipo_servicio' => $serviceMode], JSON_UNESCAPED_UNICODE),
    ];

    if ($isUpdate) {
        $quoteStatement = $pdo->prepare(
            'UPDATE precotizaciones
             SET solicitud_servicio_id = :solicitud_servicio_id,
                 codigo = :codigo,
                 nombre = :nombre,
                 estado = :estado,
                 rentabilidad_porcentaje = :rentabilidad_porcentaje,
                 subtotal_costos = :subtotal_costos,
                 subtotal_valores = :subtotal_valores,
                 subtotal_servicios = :subtotal_servicios,
                 subtotal_otros = :subtotal_otros,
                 iva = :iva,
                 total = :total,
                 datos_json = :datos_json
             WHERE id = :id'
        );
        $quoteStatement->execute(array_merge($quotePayload, ['id' => $quoteId]));
        $pdo->prepare('DELETE FROM precotizacion_ensayos WHERE precotizacion_id = :id')->execute(['id' => $quoteId]);
        $pdo->prepare('DELETE FROM precotizacion_servicios WHERE precotizacion_id = :id')->execute(['id' => $quoteId]);
    } else {
        $quoteStatement = $pdo->prepare(
            'INSERT INTO precotizaciones
              (solicitud_servicio_id, codigo, nombre, estado, rentabilidad_porcentaje, subtotal_costos,
               subtotal_valores, subtotal_servicios, subtotal_otros, iva, total, datos_json)
             VALUES
              (:solicitud_servicio_id, :codigo, :nombre, :estado, :rentabilidad_porcentaje, :subtotal_costos,
               :subtotal_valores, :subtotal_servicios, :subtotal_otros, :iva, :total, :datos_json)'
        );
        $quoteStatement->execute($quotePayload);
        $quoteId = db_last_insert_id($pdo);
    }

    $essayStatement = $pdo->prepare(
        'INSERT INTO precotizacion_ensayos
          (precotizacion_id, laboratorio_ensayo_id, laboratorio_id, codigo, ensayo, producto,
           cantidad, metodo_norma, costo_unitario, valor_unitario, subtotal_costo, subtotal_valor)
         VALUES
          (:precotizacion_id, :laboratorio_ensayo_id, :laboratorio_id, :codigo, :ensayo, :producto,
           :cantidad, :metodo_norma, :costo_unitario, :valor_unitario, :subtotal_costo, :subtotal_valor)'
    );
    foreach ($essayRows as $row) {
        $essayStatement->execute(array_merge($row, ['precotizacion_id' => $quoteId]));
    }

    $serviceStatement = $pdo->prepare(
        'INSERT INTO precotizacion_servicios
          (precotizacion_id, servicio, recurso, cantidad, unidad, valor_unitario, subtotal_valor, tipo, orden)
         VALUES
          (:precotizacion_id, :servicio, :recurso, :cantidad, :unidad, :valor_unitario, :subtotal_valor, :tipo, :orden)'
    );
    foreach ($serviceRows as $row) {
        $serviceStatement->execute(array_merge($row, ['precotizacion_id' => $quoteId]));
    }

    $pdo->commit();

    auth_log('precotizaciones', (string) $quoteId, $isUpdate ? 'actualizar' : 'crear', [
        'solicitud_id' => $solicitudId,
        'estado' => $targetState,
    ]);

    redirect_prequote('ok', "Pre-cotización guardada como {$quoteCode}.", [
        'solicitud_id' => $solicitudId,
        'precotizacion_id' => $quoteId,
        'tipo_servicio' => $serviceMode,
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_prequote('error', $exception->getMessage());
}
