CREATE DATABASE IF NOT EXISTS software_producto
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE software_producto;

CREATE TABLE IF NOT EXISTS lineas_producto (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  tipo_referencia VARCHAR(120) NULL,
  reglamento_norma VARCHAR(180) NULL,
  descripcion TEXT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lineas_producto_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archivos_cargados (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_archivo ENUM('matriz', 'solicitud_servicio', 'anexo') NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  nombre_guardado VARCHAR(255) NOT NULL,
  ruta_relativa VARCHAR(500) NOT NULL,
  extension VARCHAR(20) NOT NULL,
  mime_type VARCHAR(180) NULL,
  tamano_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  hash_sha256 CHAR(64) NULL,
  estado ENUM('recibido', 'procesado', 'rechazado') NOT NULL DEFAULT 'recibido',
  mensaje_validacion TEXT NULL,
  creado_por VARCHAR(120) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_archivos_tipo_fecha (tipo_archivo, creado_en),
  KEY ix_archivos_hash (hash_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matrices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  linea_producto_id INT UNSIGNED NOT NULL,
  archivo_cargado_id INT UNSIGNED NULL,
  nombre VARCHAR(180) NOT NULL,
  version_matriz VARCHAR(80) NOT NULL,
  plantilla_version VARCHAR(40) NOT NULL DEFAULT '1.0',
  fecha_actualizacion DATE NULL,
  estado ENUM('borrador', 'activa', 'historica', 'rechazada') NOT NULL DEFAULT 'borrador',
  total_registros INT UNSIGNED NOT NULL DEFAULT 0,
  observaciones TEXT NULL,
  creado_por VARCHAR(120) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_matrices_linea_version (linea_producto_id, version_matriz),
  KEY ix_matrices_linea_estado (linea_producto_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matriz_ensayos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matriz_id INT UNSIGNED NOT NULL,
  linea_producto_id INT UNSIGNED NOT NULL,
  fila_excel INT UNSIGNED NULL,
  linea_producto_codigo VARCHAR(80) NULL,
  linea_producto_nombre VARCHAR(180) NULL,
  tipo_referencia_normativa VARCHAR(120) NULL,
  reglamento_norma VARCHAR(180) NULL,
  producto VARCHAR(220) NULL,
  clase VARCHAR(180) NULL,
  familia VARCHAR(220) NULL,
  familia_articulo VARCHAR(220) NULL,
  subfamilia VARCHAR(260) NULL,
  articulo VARCHAR(180) NULL,
  articulo_adicional_observacion TEXT NULL,
  ensayo VARCHAR(320) NOT NULL,
  ensayo_normalizado VARCHAR(320) NOT NULL,
  literal_numeral VARCHAR(180) NULL,
  norma_tecnica VARCHAR(260) NULL,
  metodo_ensayo VARCHAR(260) NULL,
  aplica_ot TINYINT(1) NOT NULL DEFAULT 0,
  aplica_vigilancia_1 TINYINT(1) NOT NULL DEFAULT 0,
  aplica_vigilancia_2 TINYINT(1) NOT NULL DEFAULT 0,
  aplica_renovacion TINYINT(1) NOT NULL DEFAULT 0,
  esquema VARCHAR(180) NULL,
  codigo_nace VARCHAR(80) NULL,
  nace VARCHAR(260) NULL,
  observaciones TEXT NULL,
  version_matriz VARCHAR(80) NULL,
  fecha_actualizacion DATE NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_matriz_ensayos_matriz (matriz_id),
  KEY ix_matriz_ensayos_linea (linea_producto_id),
  KEY ix_matriz_ensayos_ensayo (ensayo_normalizado),
  KEY ix_matriz_ensayos_producto (producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_documento VARCHAR(30) NULL,
  numero_documento VARCHAR(60) NULL,
  razon_social VARCHAR(220) NOT NULL,
  contacto_nombre VARCHAR(160) NULL,
  contacto_correo VARCHAR(160) NULL,
  contacto_telefono VARCHAR(80) NULL,
  direccion VARCHAR(240) NULL,
  ciudad VARCHAR(120) NULL,
  pais VARCHAR(80) NULL DEFAULT 'Colombia',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_clientes_documento (tipo_documento, numero_documento),
  KEY ix_clientes_razon_social (razon_social)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitudes_servicio (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  archivo_cargado_id INT UNSIGNED NULL,
  cliente_id INT UNSIGNED NULL,
  linea_producto_id INT UNSIGNED NULL,
  codigo_solicitud VARCHAR(80) NULL,
  fecha_solicitud DATE NULL,
  servicio_solicitado VARCHAR(220) NULL,
  producto VARCHAR(220) NULL,
  referencia_producto VARCHAR(220) NULL,
  cantidad_muestras VARCHAR(80) NULL,
  reglamento_norma VARCHAR(180) NULL,
  estado ENUM('preliminar', 'validada', 'convertida_proyecto', 'archivada') NOT NULL DEFAULT 'preliminar',
  datos_preliminares_json JSON NULL,
  observaciones TEXT NULL,
  creado_por VARCHAR(120) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_solicitudes_codigo (codigo_solicitud),
  KEY ix_solicitudes_cliente (cliente_id),
  KEY ix_solicitudes_linea (linea_producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comerciales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(80) NOT NULL,
  nombre_completo VARCHAR(180) NOT NULL,
  correo VARCHAR(180) NOT NULL,
  rol ENUM('comercial', 'jefe_comercial') NOT NULL DEFAULT 'comercial',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comerciales_codigo (codigo),
  UNIQUE KEY uq_comerciales_correo (correo),
  KEY ix_comerciales_rol_activo (rol, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(180) NOT NULL,
  usuario VARCHAR(80) NOT NULL,
  correo VARCHAR(180) NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('administrador', 'comercial_1', 'comercial_2', 'coordinador_tecnico', 'jefe_tecnico') NOT NULL,
  comercial_id INT UNSIGNED NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  cambiar_password TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_usuario (usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY ix_usuarios_rol_activo (rol, activo),
  KEY ix_usuarios_comercial (comercial_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE solicitudes_servicio
  ADD COLUMN IF NOT EXISTS comercial_id INT UNSIGNED NULL AFTER linea_producto_id,
  ADD COLUMN IF NOT EXISTS canal_entrada VARCHAR(40) NOT NULL DEFAULT 'interno' AFTER comercial_id,
  ADD COLUMN IF NOT EXISTS atendido_por_comercial TINYINT(1) NOT NULL DEFAULT 0 AFTER canal_entrada,
  ADD COLUMN IF NOT EXISTS correo_notificacion_estado VARCHAR(40) NULL AFTER atendido_por_comercial,
  ADD KEY IF NOT EXISTS ix_solicitudes_comercial (comercial_id);

CREATE TABLE IF NOT EXISTS reglamentos_catalogo (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(120) NOT NULL,
  nombre VARCHAR(220) NOT NULL,
  fuente_nombre VARCHAR(260) NOT NULL,
  pais VARCHAR(100) NULL,
  esquemas_json JSON NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reglamentos_catalogo_codigo (codigo),
  KEY ix_reglamentos_catalogo_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_productos_reglamentarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registro_hash CHAR(64) NOT NULL,
  reglamento_codigo VARCHAR(120) NOT NULL,
  reglamento_nombre VARCHAR(220) NOT NULL,
  producto_fuente VARCHAR(700) NOT NULL,
  producto_nombre VARCHAR(700) NOT NULL,
  familia_fuente VARCHAR(700) NULL,
  familia_nombre VARCHAR(700) NULL,
  subfamilia_fuente VARCHAR(700) NULL,
  subfamilia_nombre VARCHAR(700) NULL,
  articulo VARCHAR(180) NULL,
  numeral VARCHAR(180) NULL,
  codigo_nace VARCHAR(20) NULL,
  documento_normativo LONGTEXT NULL,
  documentos_json JSON NULL,
  esquemas_json JSON NULL,
  texto_busqueda LONGTEXT NULL,
  fuente_archivo VARCHAR(255) NULL,
  fila_excel INT UNSIGNED NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalogo_registro_hash (registro_hash),
  KEY ix_catalogo_reglamento_activo (reglamento_codigo, activo),
  KEY ix_catalogo_fila (fila_excel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE catalogo_productos_reglamentarios
  MODIFY COLUMN reglamento_codigo VARCHAR(120) NOT NULL,
  MODIFY COLUMN reglamento_nombre VARCHAR(220) NOT NULL,
  ADD COLUMN IF NOT EXISTS codigo_nace VARCHAR(20) NULL AFTER numeral,
  ADD COLUMN IF NOT EXISTS documentos_json JSON NULL AFTER documento_normativo,
  ADD COLUMN IF NOT EXISTS esquemas_json JSON NULL AFTER documentos_json,
  ADD COLUMN IF NOT EXISTS texto_busqueda LONGTEXT NULL AFTER esquemas_json;

CREATE TABLE IF NOT EXISTS solicitud_productos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NOT NULL,
  catalogo_producto_id INT UNSIGNED NOT NULL,
  reglamento_codigo VARCHAR(120) NOT NULL,
  producto VARCHAR(700) NOT NULL,
  familia VARCHAR(700) NULL,
  subfamilia VARCHAR(700) NULL,
  subfamilia_tecnica VARCHAR(700) NULL,
  codigo_nace VARCHAR(20) NULL,
  referencia_modelo VARCHAR(260) NULL,
  marca VARCHAR(180) NULL,
  cantidad VARCHAR(80) NULL,
  fabricante VARCHAR(220) NULL,
  norma_tecnica VARCHAR(500) NULL,
  esquema_solicitado VARCHAR(100) NULL,
  analisis_json JSON NULL,
  especificaciones_json JSON NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_solicitud_productos_solicitud (solicitud_servicio_id),
  KEY ix_solicitud_productos_catalogo (catalogo_producto_id),
  KEY ix_solicitud_productos_reglamento (reglamento_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE solicitud_productos
  MODIFY COLUMN reglamento_codigo VARCHAR(120) NOT NULL,
  ADD COLUMN IF NOT EXISTS codigo_nace VARCHAR(20) NULL AFTER subfamilia,
  ADD COLUMN IF NOT EXISTS subfamilia_tecnica VARCHAR(700) NULL AFTER subfamilia,
  ADD COLUMN IF NOT EXISTS esquema_solicitado VARCHAR(100) NULL AFTER norma_tecnica,
  ADD COLUMN IF NOT EXISTS analisis_json JSON NULL AFTER esquema_solicitado;

CREATE TABLE IF NOT EXISTS solicitud_fabricas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(220) NOT NULL,
  pais VARCHAR(120) NULL,
  direccion VARCHAR(260) NULL,
  zona_muestreo VARCHAR(180) NULL,
  alcance_iso_9001 TEXT NULL,
  numero_certificado_iso VARCHAR(180) NULL,
  organismo_certificador VARCHAR(220) NULL,
  organismo_acreditador VARCHAR(220) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_solicitud_fabricas_solicitud (solicitud_servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitud_adjuntos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NOT NULL,
  archivo_cargado_id INT UNSIGNED NOT NULL,
  categoria VARCHAR(120) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_solicitud_adjuntos_solicitud (solicitud_servicio_id),
  KEY ix_solicitud_adjuntos_archivo (archivo_cargado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS revisiones_tecnicas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NOT NULL,
  codigo_revision VARCHAR(80) NOT NULL,
  tipo_evaluacion VARCHAR(80) NULL,
  estado ENUM('borrador', 'en_revision', 'requiere_informacion', 'aprobada') NOT NULL DEFAULT 'borrador',
  producto_en_alcance VARCHAR(10) NULL,
  iso_conforme VARCHAR(10) NULL,
  test_reports_suficientes VARCHAR(10) NULL,
  tipo_auditoria VARCHAR(40) NULL,
  decision_viabilidad VARCHAR(10) NULL,
  documentos_json JSON NULL,
  validaciones_json JSON NULL,
  analisis_iso_json JSON NULL,
  tiempos_json JSON NULL,
  factor_seguridad DECIMAL(6,2) NOT NULL DEFAULT 15.00,
  dias_base DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  dias_ajustados DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  observaciones TEXT NULL,
  revisado_por_usuario_id INT UNSIGNED NULL,
  aprobado_por_usuario_id INT UNSIGNED NULL,
  aprobado_en DATETIME NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_revision_solicitud (solicitud_servicio_id),
  UNIQUE KEY uq_revision_codigo (codigo_revision),
  KEY ix_revision_estado (estado),
  KEY ix_revision_revisor (revisado_por_usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificaciones_correo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NOT NULL,
  comercial_id INT UNSIGNED NULL,
  destinatario_nombre VARCHAR(180) NULL,
  destinatario_correo VARCHAR(180) NOT NULL,
  asunto VARCHAR(255) NOT NULL,
  estado ENUM('pendiente_configuracion', 'pendiente', 'enviado', 'fallido') NOT NULL DEFAULT 'pendiente',
  detalle_error TEXT NULL,
  intentos INT UNSIGNED NOT NULL DEFAULT 0,
  enviado_en DATETIME NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_notificaciones_solicitud (solicitud_servicio_id),
  KEY ix_notificaciones_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyectos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  tipo_referencia VARCHAR(120) NULL,
  linea_producto VARCHAR(120) NULL,
  linea_producto_id INT UNSIGNED NULL,
  matriz_id INT UNSIGNED NULL,
  solicitud_servicio_id INT UNSIGNED NULL,
  estado ENUM('borrador', 'en_revision', 'finalizado', 'archivado') NOT NULL DEFAULT 'borrador',
  datos_json JSON NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_proyectos_linea (linea_producto_id),
  KEY ix_proyectos_matriz (matriz_id),
  KEY ix_proyectos_solicitud (solicitud_servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(220) NOT NULL,
  producto VARCHAR(220) NULL,
  familia VARCHAR(220) NULL,
  subfamilia VARCHAR(260) NULL,
  datos_json JSON NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_proyecto_items_proyecto (proyecto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bitacora_sistema (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entidad VARCHAR(120) NOT NULL,
  entidad_id VARCHAR(80) NULL,
  accion VARCHAR(80) NOT NULL,
  detalle_json JSON NULL,
  creado_por VARCHAR(120) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_bitacora_entidad (entidad, entidad_id),
  KEY ix_bitacora_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratorios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  proveedor VARCHAR(180) NULL,
  descuento_porcentaje DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  observaciones TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_laboratorios_codigo (codigo),
  KEY ix_laboratorios_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratorio_importaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laboratorio_id INT UNSIGNED NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  nombre_guardado VARCHAR(255) NOT NULL,
  ruta_relativa VARCHAR(500) NOT NULL,
  extension VARCHAR(20) NOT NULL,
  total_registros INT UNSIGNED NOT NULL DEFAULT 0,
  estado ENUM('recibido', 'procesado', 'rechazado') NOT NULL DEFAULT 'recibido',
  mensaje_validacion TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_laboratorio_importaciones_laboratorio (laboratorio_id),
  KEY ix_laboratorio_importaciones_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratorio_ensayos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laboratorio_id INT UNSIGNED NOT NULL,
  importacion_id INT UNSIGNED NULL,
  codigo_sm VARCHAR(80) NULL,
  proveedor VARCHAR(180) NULL,
  ensayo VARCHAR(320) NOT NULL,
  ensayo_normalizado VARCHAR(320) NOT NULL,
  tecnica VARCHAR(220) NULL,
  sustancia_material_producto TEXT NULL,
  intervalo_medicion VARCHAR(220) NULL,
  documento_normativo TEXT NULL,
  documento_normalizado VARCHAR(320) NULL,
  sede VARCHAR(160) NULL,
  estado VARCHAR(80) NULL,
  tipo_pago VARCHAR(120) NULL,
  costo_ensayo DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  codigo_ensayo_lab VARCHAR(120) NULL,
  observaciones TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_lab_ensayos_laboratorio (laboratorio_id),
  KEY ix_lab_ensayos_codigo_sm (codigo_sm),
  KEY ix_lab_ensayos_ensayo (ensayo_normalizado),
  KEY ix_lab_ensayos_documento (documento_normalizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS criterios_precotizacion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  servicio VARCHAR(180) NOT NULL,
  recurso TEXT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unidad ENUM('horas', 'cantidad') NOT NULL DEFAULT 'horas',
  valor_unitario DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  tipo ENUM('certificacion', 'otro') NOT NULL DEFAULT 'certificacion',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_criterios_servicio (servicio),
  KEY ix_criterios_tipo_orden (tipo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS precotizaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_servicio_id INT UNSIGNED NULL,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(220) NOT NULL,
  rentabilidad_porcentaje DECIMAL(6,2) NOT NULL DEFAULT 10.00,
  estado ENUM('borrador', 'en_revision', 'aprobada', 'archivada') NOT NULL DEFAULT 'borrador',
  subtotal_costos DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_valores DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_servicios DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_otros DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  iva DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  datos_json JSON NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_precotizaciones_codigo (codigo),
  KEY ix_precotizaciones_solicitud (solicitud_servicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS precotizacion_ensayos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  precotizacion_id INT UNSIGNED NOT NULL,
  laboratorio_ensayo_id BIGINT UNSIGNED NULL,
  laboratorio_id INT UNSIGNED NULL,
  codigo VARCHAR(80) NULL,
  ensayo VARCHAR(320) NOT NULL,
  producto VARCHAR(220) NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  metodo_norma TEXT NULL,
  costo_unitario DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_unitario DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_costo DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_valor DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_precotizacion_ensayos_precotizacion (precotizacion_id),
  KEY ix_precotizacion_ensayos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS precotizacion_servicios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  precotizacion_id INT UNSIGNED NOT NULL,
  servicio VARCHAR(180) NOT NULL,
  recurso TEXT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unidad ENUM('horas', 'cantidad') NOT NULL DEFAULT 'horas',
  valor_unitario DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal_valor DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  tipo ENUM('certificacion', 'otro') NOT NULL DEFAULT 'certificacion',
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_precotizacion_servicios_precotizacion (precotizacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lineas_producto (codigo, nombre, tipo_referencia, reglamento_norma, descripcion)
VALUES
  ('JUGUETES', 'Juguetes', 'Reglamento tecnico nacional', 'Resolucion 0686 de 2018', 'Linea de juguetes y subfamilias asociadas.'),
  ('VAJILLAS', 'Vajillas y articulos en contacto con alimentos', 'Reglamento tecnico nacional', NULL, 'Linea para matrices de vajillas y articulos relacionados.'),
  ('RETIE', 'RETIE', 'Reglamento tecnico nacional', 'Resolucion 40117 de 2024', 'Reglamento Tecnico de Instalaciones Electricas.'),
  ('RETILAP', 'RETILAP', 'Reglamento tecnico nacional', 'Resolucion 40150 de 2024', 'Reglamento Tecnico de Iluminacion y Alumbrado Publico.')
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  tipo_referencia = VALUES(tipo_referencia),
  reglamento_norma = VALUES(reglamento_norma),
  descripcion = VALUES(descripcion),
  activa = 1;

INSERT INTO criterios_precotizacion (servicio, recurso, cantidad, unidad, valor_unitario, tipo, orden)
VALUES
  ('Evaluación inicial', 'Profesional de evaluación (PEV) con rol de Experto Técnico', 1, 'horas', 1500000, 'certificacion', 10),
  ('Auditoría de Sistema de Gestión (si se requiere)', 'Profesional de evaluación (PEV) con rol de Auditor de Sistema de Gestión (ASG)', 1, 'horas', 1500000, 'certificacion', 20),
  ('Evaluación de laboratorio (si se requiere)', 'Profesional de evaluación (PEV) con rol de Evaluador de competencia de laboratorios', 1, 'horas', 1500000, 'certificacion', 30),
  ('Atestiguamiento de ensayos (si se requiere)', 'Profesional de evaluación (PEV) con rol de Experto Técnico', 0, 'horas', 1500000, 'certificacion', 40),
  ('Análisis de resultados', 'Profesional de evaluación (PEV) con rol de Experto Técnico', 1, 'horas', 1500000, 'certificacion', 50),
  ('Comité de certificación', 'Experto Técnico', 1, 'horas', 1500000, 'certificacion', 60),
  ('Otros Certificación', '', 0, 'cantidad', 0, 'otro', 70),
  ('Otros gastos', 'Compra de inversor para pruebas en Colombia', 0, 'cantidad', 0, 'otro', 80)
ON DUPLICATE KEY UPDATE
  recurso = VALUES(recurso),
  cantidad = VALUES(cantidad),
  unidad = VALUES(unidad),
  valor_unitario = VALUES(valor_unitario),
  tipo = VALUES(tipo),
  activo = 1,
  orden = VALUES(orden);

INSERT INTO comerciales (codigo, nombre_completo, correo, rol, activo)
VALUES
  ('maryury-herrera', 'Maryury Otilia Herrera Arias', 'maryury.herrera@servimeters.com', 'comercial', 1),
  ('camilo-restrepo', 'Camilo Ernesto Restrepo Duque', 'camilo.restrepo@servimeters.com', 'comercial', 1),
  ('elkin-avila', 'Elkin Daniel Avila Soler', 'analista.innovacion@servimeters.com', 'jefe_comercial', 1)
ON DUPLICATE KEY UPDATE
  nombre_completo = VALUES(nombre_completo),
  correo = VALUES(correo),
  rol = VALUES(rol),
  activo = VALUES(activo);
