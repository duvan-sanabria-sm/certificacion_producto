<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este comando solo se puede ejecutar desde consola.\n");
}

$pdo = db();
if (db_is_postgres($pdo)) {
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS revisiones_tecnicas (
  id BIGSERIAL PRIMARY KEY,
  solicitud_servicio_id BIGINT NOT NULL UNIQUE,
  codigo_revision VARCHAR(80) NOT NULL UNIQUE,
  tipo_evaluacion VARCHAR(80),
  estado VARCHAR(30) NOT NULL DEFAULT 'borrador' CHECK (estado IN ('borrador', 'en_revision', 'requiere_informacion', 'aprobada')),
  producto_en_alcance VARCHAR(10), iso_conforme VARCHAR(10), test_reports_suficientes VARCHAR(10),
  tipo_auditoria VARCHAR(40), decision_viabilidad VARCHAR(10), documentos_json JSONB,
  validaciones_json JSONB, analisis_iso_json JSONB, tiempos_json JSONB,
  factor_seguridad NUMERIC(6,2) NOT NULL DEFAULT 15.00,
  dias_base NUMERIC(8,2) NOT NULL DEFAULT 0.00, dias_ajustados NUMERIC(8,2) NOT NULL DEFAULT 0.00,
  observaciones TEXT, revisado_por_usuario_id BIGINT, aprobado_por_usuario_id BIGINT,
  aprobado_en TIMESTAMP, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS ix_revision_estado ON revisiones_tecnicas (estado);
CREATE INDEX IF NOT EXISTS ix_revision_revisor ON revisiones_tecnicas (revisado_por_usuario_id);
SQL;
} else {
    $sql = (string) file_get_contents(dirname(__DIR__) . '/database/20260917_revision_tecnica.sql');
}

$pdo->exec($sql);
echo "Migración de revisión técnica aplicada correctamente.\n";
