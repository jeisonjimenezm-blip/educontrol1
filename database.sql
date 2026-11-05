-- =========================================================
-- EduControl - Base de datos de inscripciones
-- Autor: Jeison Johan Jiménez Mora
-- Motor: MySQL / MariaDB (XAMPP)
-- =========================================================

CREATE DATABASE IF NOT EXISTS educontrol
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educontrol;

-- Oferta: cursos, técnicas y auxiliares
CREATE TABLE programas (
  id_programa   INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(150) NOT NULL,
  tipo          ENUM('curso','tecnica','auxiliar') NOT NULL,
  descripcion   VARCHAR(500) DEFAULT NULL,
  jornada       VARCHAR(50)  NOT NULL DEFAULT 'Tarde',
  duracion      VARCHAR(50)  DEFAULT NULL,
  cupos         INT NOT NULL DEFAULT 30,
  estado        TINYINT(1) NOT NULL DEFAULT 1          -- 1 activo, 0 inactivo
) ENGINE=InnoDB;

-- Personas que se inscriben
CREATE TABLE aspirantes (
  id_aspirante     INT AUTO_INCREMENT PRIMARY KEY,
  tipo_documento   VARCHAR(5)   NOT NULL,              -- CC, TI, CE, PPT
  documento        VARCHAR(20)  NOT NULL UNIQUE,
  nombres          VARCHAR(80)  NOT NULL,
  apellidos        VARCHAR(80)  NOT NULL,
  celular          VARCHAR(15)  NOT NULL,              -- 10 dígitos, ej. 3001234567
  correo           VARCHAR(120) NOT NULL,
  autoriza_datos   TINYINT(1)   NOT NULL DEFAULT 0,    -- Ley 1581 de 2012
  fecha_registro   DATETIME     NOT NULL
) ENGINE=InnoDB;

-- Inscripción de un aspirante a un programa
CREATE TABLE inscripciones (
  id_inscripcion       INT AUTO_INCREMENT PRIMARY KEY,
  codigo               VARCHAR(20) NOT NULL UNIQUE,    -- ej. EDU-2026-00015
  id_aspirante         INT NOT NULL,
  id_programa          INT NOT NULL,
  fecha                DATETIME NOT NULL,
  estado_confirmacion  VARCHAR(20) NOT NULL DEFAULT 'pendiente', -- whatsapp, sms, simulado, fallido
  UNIQUE KEY uk_aspirante_programa (id_aspirante, id_programa),  -- evita duplicados
  FOREIGN KEY (id_aspirante) REFERENCES aspirantes(id_aspirante),
  FOREIGN KEY (id_programa)  REFERENCES programas(id_programa)
) ENGINE=InnoDB;

-- Usuarios del panel administrativo
CREATE TABLE administradores (
  id_admin            INT AUTO_INCREMENT PRIMARY KEY,
  usuario             VARCHAR(50)  NOT NULL UNIQUE,
  contrasena_cifrada  VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Datos de ejemplo
-- ---------------------------------------------------------
INSERT INTO programas (nombre, tipo, descripcion, jornada, duracion, cupos) VALUES
('Excel básico e intermedio', 'curso', 'Fórmulas, tablas dinámicas y gráficos para el trabajo.', 'Tarde', '40 horas', 25),
('Inglés conversacional A1', 'curso', 'Vocabulario y conversación para la vida diaria.', 'Tarde', '60 horas', 20),
('Técnico en Desarrollo de Aplicaciones Informáticas y Digitales', 'tecnica', 'Programación web, bases de datos y aplicaciones móviles.', 'Tarde', '3 semestres', 30),
('Técnico en Asistencia Administrativa', 'tecnica', 'Gestión documental, atención al cliente y ofimática.', 'Tarde', '3 semestres', 30),
('Auxiliar en Enfermería', 'auxiliar', 'Cuidado básico del paciente y apoyo en servicios de salud.', 'Tarde', '3 semestres', 30),
('Auxiliar Contable y Financiero', 'auxiliar', 'Registro contable, nómina y facturación.', 'Tarde', '3 semestres', 30);

-- Usuario administrador: admin / Admin2026*
-- (la contraseña está cifrada con password_hash de PHP; cámbiela después del primer ingreso)
INSERT INTO administradores (usuario, contrasena_cifrada) VALUES
('admin', '$2y$10$CiVZnNAg49OEGxx4wqzXl.0jHU8p7zUHp0l.rQjeJqUsgjz3xbW6y');
