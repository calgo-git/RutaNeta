-- =====================================================================
-- RutaNeta · sql/rutaneta.sql
-- Script completo de la base de datos: BD, tablas, reglas y datos de prueba.
--
-- Cómo usarlo: en http://localhost/phpmyadmin abre la pestaña "Importar",
-- elige este archivo y pulsa "Importar". Crea todo desde cero.
--
-- OJO: si lo importas otra vez, BORRA las tablas con todos sus datos y las
-- vuelve a crear. Sirve para regresar el proyecto a los datos de prueba.
-- =====================================================================

-- Este archivo está en UTF-8. La línea siguiente se lo avisa a MySQL para que
-- los acentos y la ñ de los datos de prueba se guarden bien, importes desde
-- phpMyAdmin o desde la consola (que en muchos equipos usa latin1).
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS rutaneta
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rutaneta;

-- Primero se borran las tablas hijas (ingresos y gastos apuntan a usuarios)
DROP TABLE IF EXISTS gastos;
DROP TABLE IF EXISTS ingresos;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------
-- usuarios: un conductor por fila.
-- registro.php inserta aquí; login.php busca por email y compara el hash.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,   -- UNIQUE: la BD impide dos cuentas con el mismo email
  password    VARCHAR(255) NOT NULL,          -- hash de password_hash(), nunca la contraseña real
  plataforma  VARCHAR(50)  NOT NULL,          -- plataforma principal del conductor
  vehiculo    VARCHAR(50)  NOT NULL,
  fecha_reg   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ingresos: lo que pagan las plataformas.
-- ingresos.php inserta y borra; dashboard.php e historial.php suman por mes.
-- ---------------------------------------------------------------------
CREATE TABLE ingresos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT NOT NULL,
  plataforma  VARCHAR(50)   NOT NULL,
  monto       DECIMAL(10,2) NOT NULL,
  horas_trab  DECIMAL(5,2)  NOT NULL DEFAULT 0,
  km_recorr   DECIMAL(8,2)  NOT NULL DEFAULT 0,
  fecha       DATE NOT NULL,
  notas       TEXT,
  -- Si se borra un usuario, sus ingresos se borran con él
  CONSTRAINT fk_ingresos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  -- Última barrera: aunque falle la validación de PHP, la BD rechaza valores inválidos
  CONSTRAINT chk_ingresos_monto CHECK (monto > 0),
  CONSTRAINT chk_ingresos_horas CHECK (horas_trab >= 0),
  CONSTRAINT chk_ingresos_km    CHECK (km_recorr >= 0),
  -- Todas las consultas filtran por usuario y rango de fechas: este índice las acelera
  INDEX idx_ingresos_usuario_fecha (usuario_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- gastos: todo lo que cuesta trabajar.
-- gastos.php inserta, borra y agrupa por categoría; dashboard.php e
-- historial.php los restan a los ingresos.
-- ---------------------------------------------------------------------
CREATE TABLE gastos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT NOT NULL,
  -- Los 14 valores deben ser idénticos a los del <select> de gastos.php
  categoria    ENUM('gasolina','aceite','llantas','frenos','mantenimiento_general',
                    'lavado','tenencia','seguro','verificacion','multa',
                    'estacionamiento','telefono','datos_internet','otros') NOT NULL,
  descripcion  VARCHAR(200),
  monto        DECIMAL(10,2) NOT NULL,
  fecha        DATE NOT NULL,
  es_fijo      TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = recurrente (solo etiqueta, no cambia cálculos)
  CONSTRAINT fk_gastos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT chk_gastos_monto CHECK (monto > 0),
  INDEX idx_gastos_usuario_fecha (usuario_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATOS DE PRUEBA
-- =====================================================================

-- Usuario de prueba
--   Email:      demo@rutaneta.test
--   Contraseña: demo1234
-- La columna password guarda el resultado de password_hash('demo1234', PASSWORD_DEFAULT)
INSERT INTO usuarios (id, nombre, email, password, plataforma, vehiculo) VALUES
(1, 'Luis Hernández', 'demo@rutaneta.test',
 '$2y$10$fvTCn4KDzkH7ESGubjgDBeySKnIiFO5V3kgVqEC0UQgp/nLRpIUfC', 'Uber', 'Auto');

-- Fechas relativas: se calculan el día que importas el script.
-- Así el dashboard (mes actual) y el historial (mes anterior) siempre tienen datos.
SET @mes_actual   = DATE_FORMAT(CURDATE(), '%Y-%m-01');
SET @mes_anterior = DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01');

-- 5 ingresos: 3 del mes anterior y 2 del mes actual
INSERT INTO ingresos (usuario_id, plataforma, monto, horas_trab, km_recorr, fecha, notas) VALUES
(1, 'Uber',                 1850.00,  9.50, 160.00, @mes_anterior + INTERVAL 4 DAY,  'Viernes con tarifa dinámica'),
(1, 'DiDi',                 1320.50,  7.00, 118.50, @mes_anterior + INTERVAL 11 DAY, NULL),
(1, 'Rappi',                 640.00,  4.50,  52.00, @mes_anterior + INTERVAL 19 DAY, 'Tarde de lluvia, muchos pedidos'),
(1, 'Uber',                 2100.00, 10.00, 185.00, @mes_actual,                     'Fin de semana de quincena'),
(1, 'Mercado Libre Envíos',  980.00,  6.00,  95.00, CURDATE(),                       'Ruta de paquetería');

-- 8 gastos de categorías distintas; seguro y datos móviles son fijos (es_fijo = 1)
INSERT INTO gastos (usuario_id, categoria, descripcion, monto, fecha, es_fijo) VALUES
(1, 'llantas',        '2 llantas delanteras',          2400.00, @mes_anterior + INTERVAL 2 DAY,  0),
(1, 'aceite',         'Cambio de aceite y filtro',      650.00, @mes_anterior + INTERVAL 9 DAY,  0),
(1, 'multa',          'Multa de tránsito',             1100.00, @mes_anterior + INTERVAL 14 DAY, 0),
(1, 'telefono',       'Funda y mica para el celular',   349.00, @mes_anterior + INTERVAL 21 DAY, 0),
(1, 'seguro',         'Mensualidad del seguro',        1150.00, @mes_actual,                     1),
(1, 'datos_internet', 'Paquete de datos móviles',       299.00, @mes_actual,                     1),
(1, 'gasolina',       'Gasolina de la semana',          820.00, CURDATE(),                       0),
(1, 'lavado',         'Lavado por dentro y por fuera',  120.00, CURDATE(),                       0);

-- Resultado esperado con estos datos:
--   Mes anterior: ingresos $3,810.50 − gastos $4,499.00 = neto −$688.50 (pérdida)
--   Mes actual:   ingresos $3,080.00 − gastos $2,389.00 = neto  $691.00
