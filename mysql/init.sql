-- Script de Inicialización de Base de Datos - La Huerta Digital 2.0
-- Configuración de Base de Datos, Tabla de Productos, Datos Iniciales y Usuario de Solo Lectura

CREATE DATABASE IF NOT EXISTS HUERTA_DIGITAL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE HUERTA_DIGITAL;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    productor VARCHAR(100) NOT NULL,
    precio INT NOT NULL,
    stock INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO productos (nombre, productor, precio, stock, imagen) VALUES
('Papas Granel (kg)', 'Don Pedro', 1200, 150, 'papas.jpg'),
('Tomates Larga Vida (kg)', 'Doña Rosa', 1800, 80, 'tomates.jpg'),
('Lechuga Costina (unidad)', 'Don Manuel', 900, 60, 'lechuga.jpg'),
('Zanahorias (atado)', 'Doña Carmen', 1100, 95, 'zanahorias.jpg'),
('Cebollas Moradas (kg)', 'Don Pedro', 1400, 110, 'cebollas.jpg'),
('Espinaca Fresca (atado)', 'Doña Rosa', 1000, 40, 'espinaca.jpg');

-- Creación de usuario de solo lectura para auditoría y pruebas del aula
CREATE USER IF NOT EXISTS 'huerta_lector'@'%' IDENTIFIED BY 'Lector_Aula_2026';
GRANT SELECT ON HUERTA_DIGITAL.* TO 'huerta_lector'@'%';
FLUSH PRIVILEGES;
