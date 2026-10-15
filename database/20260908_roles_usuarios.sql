USE software_producto;

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
