<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

$authUser = auth_require_permission('technical_review.edit');

function redirect_technical_review(string $status, string $message, int $requestId): never
{
    header('Location: ../revision_tecnica.php?' . http_build_query([
        'solicitud_id' => $requestId,
        'review_status' => $status,
        'review_message' => $message,
    ]));
    exit;
}

function review_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function review_decimal(string $key, float $default = 0.0): float
{
    $value = str_replace(',', '.', review_value($key));
    return is_numeric($value) ? (float) $value : $default;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$requestId = (int) ($_POST['solicitud_id'] ?? 0);
try {
    auth_assert_csrf();
    if ($requestId <= 0) {
        throw new RuntimeException('Selecciona una solicitud válida.');
    }
    $pdo = db();
    if (!auth_can_access_request($pdo, $requestId)) {
        throw new RuntimeException('No tienes acceso a la solicitud seleccionada.');
    }
    $requestStatement = $pdo->prepare('SELECT id, codigo_solicitud, datos_preliminares_json FROM solicitudes_servicio WHERE id = :id');
    $requestStatement->execute(['id' => $requestId]);
    $request = $requestStatement->fetch();
    if (!$request) {
        throw new RuntimeException('La solicitud no existe.');
    }

    $allowedTriState = ['SI', 'NO', 'NA'];
    $documents = [
        'solicitud' => review_value('doc_solicitud'),
        'ficha_tecnica' => review_value('doc_ficha_tecnica'),
        'registro_fotografico' => review_value('doc_registro_fotografico'),
        'iso_9001' => review_value('doc_iso_9001'),
        'test_reports' => review_value('doc_test_reports'),
        'carta_no_cambios' => review_value('doc_carta_no_cambios'),
        'idioma_fichas' => review_value('idioma_fichas'),
    ];
    foreach (['solicitud', 'ficha_tecnica', 'registro_fotografico', 'iso_9001', 'test_reports', 'carta_no_cambios'] as $key) {
        if (!in_array($documents[$key], $allowedTriState, true)) {
            throw new RuntimeException('Completa todo el checklist de documentos aportados.');
        }
    }
    if ($documents['solicitud'] !== 'SI' || $documents['ficha_tecnica'] !== 'SI') {
        throw new RuntimeException('La solicitud diligenciada y las fichas técnicas son obligatorias para completar la revisión.');
    }

    $productScope = review_value('producto_en_alcance');
    $isoConformity = review_value('iso_conforme');
    $testReports = review_value('test_reports_suficientes');
    $decision = review_value('decision_viabilidad');
    if (!in_array($productScope, ['SI', 'NO'], true)
        || !in_array($isoConformity, $allowedTriState, true)
        || !in_array($testReports, $allowedTriState, true)
        || !in_array($decision, ['SI', 'NO'], true)) {
        throw new RuntimeException('Completa las validaciones y la decisión de viabilidad.');
    }

    $preliminary = json_decode((string) ($request['datos_preliminares_json'] ?? '{}'), true) ?: [];
    $hasRemoteTechnology = !empty($preliminary['servicio']['medios_tecnologicos_remotos']);
    $auditType = review_value('tipo_auditoria');
    if (!in_array($auditType, ['En sitio', 'Remota', 'Mixta'], true)) {
        throw new RuntimeException('Selecciona el tipo de auditoría técnica.');
    }
    if (!$hasRemoteTechnology && in_array($auditType, ['Remota', 'Mixta'], true)) {
        throw new RuntimeException('El cliente indicó que no dispone de medios tecnológicos; la auditoría debe realizarse en sitio.');
    }

    $times = json_decode(review_value('tiempos_json'), true);
    if (!is_array($times)) {
        throw new RuntimeException('Calcula los tiempos de evaluación antes de guardar la revisión.');
    }
    $daysBase = review_decimal('dias_base');
    $daysAdjusted = review_decimal('dias_ajustados');
    $safetyFactor = review_decimal('factor_seguridad', 15.0);
    if ($daysBase <= 0 || $daysAdjusted <= 0 || (float) ($times['evaluacion_inicial']['base'] ?? 0) <= 0) {
        throw new RuntimeException('La evaluación inicial y el total de días técnicos deben ser mayores que cero.');
    }

    $isoAnalysis = json_decode(review_value('analisis_iso_json'), true);
    $isoAnalysis = is_array($isoAnalysis) ? $isoAnalysis : [];
    $analysisResult = (string) ($isoAnalysis['validation']['resultado'] ?? '');
    if ($analysisResult === 'no_conforme' && $isoConformity === 'SI') {
        throw new RuntimeException('El análisis ISO presenta una no conformidad. Corrige la validación o documenta un nuevo certificado conforme.');
    }

    $submitAction = review_value('submit_action') ?: 'guardar';
    if ($submitAction === 'aprobar') {
        if (!auth_can('technical_review.approve', $authUser)) {
            throw new RuntimeException('Tu perfil puede preparar la revisión, pero no aprobarla.');
        }
        if ($decision !== 'SI' || $productScope !== 'SI') {
            throw new RuntimeException('Solo se puede aprobar una solicitud viable y dentro del alcance de Servimeters.');
        }
        $state = 'aprobada';
    } else {
        $state = $decision === 'NO' ? 'requiere_informacion' : 'en_revision';
    }

    $validations = [
        'producto_en_alcance' => $productScope,
        'iso_conforme' => $isoConformity,
        'test_reports_suficientes' => $testReports,
        'medios_tecnologicos_remotos' => $hasRemoteTechnology,
        'tipo_auditoria' => $auditType,
        'requiere_ensayos' => $testReports === 'NO',
    ];
    $code = 'REV-' . date('Y') . '-' . str_pad((string) $requestId, 6, '0', STR_PAD_LEFT);
    $parameters = [
        'solicitud' => $requestId,
        'codigo' => $code,
        'tipo_evaluacion' => review_value('tipo_evaluacion'),
        'estado' => $state,
        'producto_en_alcance' => $productScope,
        'iso_conforme' => $isoConformity,
        'test_reports' => $testReports,
        'tipo_auditoria' => $auditType,
        'decision' => $decision,
        'documentos' => json_encode($documents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'validaciones' => json_encode($validations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'analisis_iso' => $isoAnalysis ? json_encode($isoAnalysis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        'tiempos' => json_encode($times, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'factor' => $safetyFactor,
        'dias_base' => $daysBase,
        'dias_ajustados' => $daysAdjusted,
        'observaciones' => mb_substr(review_value('observaciones_revision'), 0, 2000),
        'revisor' => (int) $authUser['id'],
        'aprobador' => $state === 'aprobada' ? (int) $authUser['id'] : null,
        'aprobado_en' => $state === 'aprobada' ? date('Y-m-d H:i:s') : null,
    ];

    if (db_is_postgres($pdo)) {
        $sql = "INSERT INTO revisiones_tecnicas
          (solicitud_servicio_id, codigo_revision, tipo_evaluacion, estado, producto_en_alcance,
           iso_conforme, test_reports_suficientes, tipo_auditoria, decision_viabilidad,
           documentos_json, validaciones_json, analisis_iso_json, tiempos_json, factor_seguridad,
           dias_base, dias_ajustados, observaciones, revisado_por_usuario_id, aprobado_por_usuario_id, aprobado_en)
         VALUES
          (:solicitud, :codigo, :tipo_evaluacion, :estado, :producto_en_alcance, :iso_conforme,
           :test_reports, :tipo_auditoria, :decision, CAST(:documentos AS JSONB), CAST(:validaciones AS JSONB),
           CAST(:analisis_iso AS JSONB), CAST(:tiempos AS JSONB), :factor, :dias_base, :dias_ajustados,
           :observaciones, :revisor, :aprobador, :aprobado_en)
         ON CONFLICT (solicitud_servicio_id) DO UPDATE SET
           tipo_evaluacion = EXCLUDED.tipo_evaluacion, estado = EXCLUDED.estado,
           producto_en_alcance = EXCLUDED.producto_en_alcance, iso_conforme = EXCLUDED.iso_conforme,
           test_reports_suficientes = EXCLUDED.test_reports_suficientes, tipo_auditoria = EXCLUDED.tipo_auditoria,
           decision_viabilidad = EXCLUDED.decision_viabilidad, documentos_json = EXCLUDED.documentos_json,
           validaciones_json = EXCLUDED.validaciones_json, analisis_iso_json = EXCLUDED.analisis_iso_json,
           tiempos_json = EXCLUDED.tiempos_json, factor_seguridad = EXCLUDED.factor_seguridad,
           dias_base = EXCLUDED.dias_base, dias_ajustados = EXCLUDED.dias_ajustados,
           observaciones = EXCLUDED.observaciones, revisado_por_usuario_id = EXCLUDED.revisado_por_usuario_id,
           aprobado_por_usuario_id = EXCLUDED.aprobado_por_usuario_id, aprobado_en = EXCLUDED.aprobado_en,
           actualizado_en = CURRENT_TIMESTAMP";
    } else {
        $sql = "INSERT INTO revisiones_tecnicas
          (solicitud_servicio_id, codigo_revision, tipo_evaluacion, estado, producto_en_alcance,
           iso_conforme, test_reports_suficientes, tipo_auditoria, decision_viabilidad,
           documentos_json, validaciones_json, analisis_iso_json, tiempos_json, factor_seguridad,
           dias_base, dias_ajustados, observaciones, revisado_por_usuario_id, aprobado_por_usuario_id, aprobado_en)
         VALUES
          (:solicitud, :codigo, :tipo_evaluacion, :estado, :producto_en_alcance, :iso_conforme,
           :test_reports, :tipo_auditoria, :decision, :documentos, :validaciones, :analisis_iso, :tiempos,
           :factor, :dias_base, :dias_ajustados, :observaciones, :revisor, :aprobador, :aprobado_en)
         ON DUPLICATE KEY UPDATE
           tipo_evaluacion = VALUES(tipo_evaluacion), estado = VALUES(estado),
           producto_en_alcance = VALUES(producto_en_alcance), iso_conforme = VALUES(iso_conforme),
           test_reports_suficientes = VALUES(test_reports_suficientes), tipo_auditoria = VALUES(tipo_auditoria),
           decision_viabilidad = VALUES(decision_viabilidad), documentos_json = VALUES(documentos_json),
           validaciones_json = VALUES(validaciones_json), analisis_iso_json = VALUES(analisis_iso_json),
           tiempos_json = VALUES(tiempos_json), factor_seguridad = VALUES(factor_seguridad),
           dias_base = VALUES(dias_base), dias_ajustados = VALUES(dias_ajustados),
           observaciones = VALUES(observaciones), revisado_por_usuario_id = VALUES(revisado_por_usuario_id),
           aprobado_por_usuario_id = VALUES(aprobado_por_usuario_id), aprobado_en = VALUES(aprobado_en)";
    }
    $pdo->prepare($sql)->execute($parameters);
    auth_log('revisiones_tecnicas', (string) $requestId, $state === 'aprobada' ? 'aprobar' : 'guardar', [
        'codigo_revision' => $code,
        'estado' => $state,
        'dias_ajustados' => $daysAdjusted,
    ]);
    $message = $state === 'aprobada'
        ? 'Revisión aprobada. La solicitud ya está habilitada para precotización.'
        : ($state === 'requiere_informacion'
            ? 'Revisión guardada con solicitud de información adicional.'
            : 'Revisión guardada y enviada para aprobación técnica.');
    redirect_technical_review('ok', $message, $requestId);
} catch (Throwable $exception) {
    redirect_technical_review('error', $exception->getMessage(), $requestId);
}
