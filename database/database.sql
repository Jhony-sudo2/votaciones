-- Esquema NUEVO para demostración. No aplicar directamente sobre una BD previa sin migración.
CREATE DATABASE IF NOT EXISTS sistema_votacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_votacion;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    dpi CHAR(13) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    foto_anverso VARCHAR(80) NOT NULL,
    estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    usuario VARCHAR(100) NULL,
    password VARCHAR(255) NULL,
    ha_votado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_usuarios_dpi (dpi),
    UNIQUE KEY ux_usuarios_email (email),
    UNIQUE KEY ux_usuarios_usuario (usuario),
    INDEX ix_usuarios_estado (estado)
) ENGINE=InnoDB;

-- Separación lógica: no hay usuario_id ni fecha_voto para minimizar correlaciones.
-- No equivale a un sistema criptográfico de voto secreto/verificable.
CREATE TABLE IF NOT EXISTS votos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    opcion_voto ENUM('opcion1','opcion2') NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS operadores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY ux_operadores_usuario (usuario)
) ENGINE=InnoDB;
