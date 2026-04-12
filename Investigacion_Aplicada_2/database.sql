-- ============================================================
-- database.sql  —  Ejecutar en phpMyAdmin antes de iniciar
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventario_api
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE inventario_api;

CREATE TABLE IF NOT EXISTS productos (
    id          INT           NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100)  NOT NULL,
    precio      DECIMAL(10,2) NOT NULL,
    cantidad    INT           NOT NULL DEFAULT 0,
    descripcion TEXT,
    creado_en   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Datos de ejemplo para probar el GET de inmediato
INSERT INTO productos (nombre, precio, cantidad, descripcion) VALUES
    ('Monitor 24"',       149.99, 15, 'Full HD 1080p, 75Hz'),
    ('Teclado Mecánico',   49.99, 30, 'Retroiluminado, switches azules'),
    ('Mouse Inalámbrico',  25.00, 50, 'Ergonómico 2.4GHz, 1600 DPI'),
    ('Auriculares USB',    39.99, 20, 'Con micrófono para videoconferencias');
