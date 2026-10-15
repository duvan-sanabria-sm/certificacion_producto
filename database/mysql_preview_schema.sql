-- MySQL preview schema
-- Project: Constructor ISO de juguetes y ensayos sin repetir
-- Purpose: Import this file into MySQL/phpMyAdmin/MySQL Workbench to preview the proposed database structure.
-- Note: The official target database for Servimeters is SQL Server 2019. This file is only a MySQL-compatible preview.

CREATE DATABASE IF NOT EXISTS servimeters_constructor_normativo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servimeters_constructor_normativo;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS project_toy_subfamily;
DROP TABLE IF EXISTS project_toy;
DROP TABLE IF EXISTS project;
DROP TABLE IF EXISTS subfamily_combination_item;
DROP TABLE IF EXISTS subfamily_combination;
DROP TABLE IF EXISTS classification_combination_family;
DROP TABLE IF EXISTS classification_combination;
DROP TABLE IF EXISTS subfamily_test_requirement;
DROP TABLE IF EXISTS normative_test;
DROP TABLE IF EXISTS subfamily;
DROP TABLE IF EXISTS family;
DROP TABLE IF EXISTS product_line;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE product_line (
    product_line_id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(120) NOT NULL,
    name VARCHAR(250) NOT NULL,
    country VARCHAR(80) NULL,
    regulation VARCHAR(250) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (product_line_id),
    UNIQUE KEY uq_product_line_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE family (
    family_id INT NOT NULL AUTO_INCREMENT,
    product_line_id INT NOT NULL,
    code VARCHAR(180) NOT NULL,
    name VARCHAR(250) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (family_id),
    UNIQUE KEY uq_family_product_line_code (product_line_id, code),
    KEY ix_family_product_line_id (product_line_id),
    CONSTRAINT fk_family_product_line
        FOREIGN KEY (product_line_id) REFERENCES product_line(product_line_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subfamily (
    subfamily_id INT NOT NULL AUTO_INCREMENT,
    family_id INT NOT NULL,
    code VARCHAR(220) NOT NULL,
    name VARCHAR(300) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (subfamily_id),
    UNIQUE KEY uq_subfamily_family_code (family_id, code),
    KEY ix_subfamily_family_id (family_id),
    CONSTRAINT fk_subfamily_family
        FOREIGN KEY (family_id) REFERENCES family(family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE normative_test (
    test_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(300) NOT NULL,
    normalized_name VARCHAR(300) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (test_id),
    UNIQUE KEY uq_normative_test_normalized_name (normalized_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subfamily_test_requirement (
    requirement_id INT NOT NULL AUTO_INCREMENT,
    subfamily_id INT NOT NULL,
    test_id INT NOT NULL,
    source_row VARCHAR(50) NULL,
    literal_code VARCHAR(120) NULL,
    technical_standard VARCHAR(250) NULL,
    applies_ot TINYINT(1) NOT NULL DEFAULT 0,
    applies_vig1 TINYINT(1) NOT NULL DEFAULT 0,
    applies_vig2 TINYINT(1) NOT NULL DEFAULT 0,
    applies_renovation TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (requirement_id),
    KEY ix_requirement_subfamily_id (subfamily_id),
    KEY ix_requirement_test_id (test_id),
    CONSTRAINT fk_requirement_subfamily
        FOREIGN KEY (subfamily_id) REFERENCES subfamily(subfamily_id),
    CONSTRAINT fk_requirement_test
        FOREIGN KEY (test_id) REFERENCES normative_test(test_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classification_combination (
    classification_combination_id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(500) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (classification_combination_id),
    UNIQUE KEY uq_classification_combination_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classification_combination_family (
    classification_combination_id INT NOT NULL,
    family_id INT NOT NULL,
    PRIMARY KEY (classification_combination_id, family_id),
    KEY ix_classification_combination_family_family_id (family_id),
    CONSTRAINT fk_classification_combination_family_combination
        FOREIGN KEY (classification_combination_id)
        REFERENCES classification_combination(classification_combination_id),
    CONSTRAINT fk_classification_combination_family_family
        FOREIGN KEY (family_id) REFERENCES family(family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subfamily_combination (
    subfamily_combination_id INT NOT NULL AUTO_INCREMENT,
    classification_combination_id INT NOT NULL,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(700) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    PRIMARY KEY (subfamily_combination_id),
    UNIQUE KEY uq_subfamily_combination_code (code),
    KEY ix_subfamily_combination_classification_id (classification_combination_id),
    CONSTRAINT fk_subfamily_combination_classification
        FOREIGN KEY (classification_combination_id)
        REFERENCES classification_combination(classification_combination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subfamily_combination_item (
    subfamily_combination_id INT NOT NULL,
    subfamily_id INT NOT NULL,
    PRIMARY KEY (subfamily_combination_id, subfamily_id),
    KEY ix_subfamily_combination_item_subfamily_id (subfamily_id),
    CONSTRAINT fk_subfamily_combination_item_combination
        FOREIGN KEY (subfamily_combination_id)
        REFERENCES subfamily_combination(subfamily_combination_id),
    CONSTRAINT fk_subfamily_combination_item_subfamily
        FOREIGN KEY (subfamily_id) REFERENCES subfamily(subfamily_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project (
    project_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(250) NOT NULL,
    created_by VARCHAR(120) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (project_id),
    KEY ix_project_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_toy (
    project_toy_id INT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    name VARCHAR(250) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (project_toy_id),
    KEY ix_project_toy_project_id (project_id),
    CONSTRAINT fk_project_toy_project
        FOREIGN KEY (project_id) REFERENCES project(project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_toy_subfamily (
    project_toy_id INT NOT NULL,
    subfamily_id INT NOT NULL,
    PRIMARY KEY (project_toy_id, subfamily_id),
    KEY ix_project_toy_subfamily_subfamily_id (subfamily_id),
    CONSTRAINT fk_project_toy_subfamily_project_toy
        FOREIGN KEY (project_toy_id) REFERENCES project_toy(project_toy_id),
    CONSTRAINT fk_project_toy_subfamily_subfamily
        FOREIGN KEY (subfamily_id) REFERENCES subfamily(subfamily_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    audit_log_id BIGINT NOT NULL AUTO_INCREMENT,
    entity_name VARCHAR(120) NOT NULL,
    entity_id VARCHAR(80) NULL,
    action_name VARCHAR(40) NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    created_by VARCHAR(120) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (audit_log_id),
    KEY ix_audit_log_entity (entity_name, entity_id),
    KEY ix_audit_log_created_by (created_by),
    KEY ix_audit_log_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_line (code, name, country, regulation)
VALUES
('JUGUETES_CO_0686_2018', 'Juguetes Colombia Resolucion 0686 de 2018', 'Colombia', 'Resolucion 0686 de 2018'),
('RETIE_CO_40117_2024', 'Reglamento Tecnico de Instalaciones Electricas - RETIE', 'Colombia', 'Resolucion 40117 de 2024'),
('RETILAP_CO_40150_2024', 'Reglamento Tecnico de Iluminacion y Alumbrado Publico - RETILAP', 'Colombia', 'Resolucion 40150 de 2024');

-- Preview query: list generated tables
SHOW TABLES;

