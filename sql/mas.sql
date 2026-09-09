-- ============================================
-- MAS - Modelo de Asignación de Salas
-- BASE DE DATOS COMPLETA
-- ============================================

-- Eliminar la base de datos si existe
DROP DATABASE IF EXISTS mas;

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS mas;
USE mas;

-- ============================================
-- 1. Tabla: usuarios
-- ============================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'docente', 'secretaria', 'visualizador') DEFAULT 'docente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. Tabla: tipos_sala
-- ============================================
CREATE TABLE tipos_sala (
    id_tipo_sala INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(50) NOT NULL,
    descripcion TEXT
);

-- ============================================
-- 3. Tabla: salas
-- ============================================
CREATE TABLE salas (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    codigo_sala VARCHAR(10) UNIQUE NOT NULL,
    nombre_sala VARCHAR(100) NOT NULL,
    capacidad INT NOT NULL,
    id_tipo_sala INT,
    estado ENUM('disponible', 'mantencion') DEFAULT 'disponible',
    ubicacion VARCHAR(100),
    descripcion TEXT,
    FOREIGN KEY (id_tipo_sala) REFERENCES tipos_sala(id_tipo_sala)
);

-- ============================================
-- 4. Tabla: bloques_horarios
-- ============================================
CREATE TABLE bloques_horarios (
    id_bloque INT AUTO_INCREMENT PRIMARY KEY,
    numero_bloque INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    dia_semana ENUM('lunes','martes','miercoles','jueves','viernes','sabado') NOT NULL
);

-- ============================================
-- 5. Tabla: solicitudes
-- ============================================
CREATE TABLE solicitudes (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_solicitante INT NOT NULL,
    id_sala INT NOT NULL,
    id_bloque_inicio INT NOT NULL,
    id_bloque_fin INT NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_reserva DATE NOT NULL,
    motivo TEXT,
    nombre_profesor VARCHAR(100),
    carrera_sigla VARCHAR(20),
    paralelo VARCHAR(20),
    tipo_actividad VARCHAR(50),
    estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    comentarios TEXT,
    FOREIGN KEY (id_usuario_solicitante) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala),
    FOREIGN KEY (id_bloque_inicio) REFERENCES bloques_horarios(id_bloque),
    FOREIGN KEY (id_bloque_fin) REFERENCES bloques_horarios(id_bloque)
);

-- ============================================
-- 6. Tabla: asignaciones
-- ============================================
CREATE TABLE asignaciones (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    id_usuario_aprobador INT NOT NULL,
    id_sala INT NOT NULL,
    id_bloque_inicio INT NOT NULL,
    id_bloque_fin INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activa','cancelada') DEFAULT 'activa',
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id_solicitud),
    FOREIGN KEY (id_usuario_aprobador) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala),
    FOREIGN KEY (id_bloque_inicio) REFERENCES bloques_horarios(id_bloque),
    FOREIGN KEY (id_bloque_fin) REFERENCES bloques_horarios(id_bloque)
);

-- ============================================
-- 7. Tabla: requerimientos
-- ============================================
CREATE TABLE requerimientos (
    id_requerimiento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    icono VARCHAR(10) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1
);

-- ============================================
-- 8. Tabla: tipos_actividad
-- ============================================
CREATE TABLE tipos_actividad (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    icono VARCHAR(10) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1
);

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Tipos de Sala
INSERT INTO tipos_sala (nombre_tipo, descripcion) VALUES
('Aula', 'Sala de clases estandar con pizarra y proyector'),
('Laboratorio', 'Sala equipada con computadores'),
('Auditorio', 'Sala de grandes dimensiones para eventos'),
('Sala de Reuniones', 'Sala pequena para reuniones');

-- Tipos de Actividad
INSERT INTO tipos_actividad (nombre, icono, activo) VALUES
('Cátedra', '📚', 1),
('Control', '📝', 1),
('Examen', '📄', 1),
('Examen de Título', '🎓', 1),
('Charla', '🎤', 1),
('Taller', '🔧', 1),
('Reunión', '🤝', 1),
('Otro', '📌', 1);

-- Requerimientos
INSERT INTO requerimientos (nombre, icono, activo) VALUES
('Data', '📊', 1),
('Parlante', '🔊', 1),
('Micrófono', '🎤', 1),
('Telón móvil', '🎭', 1),
('Sillas', '🪑', 1),
('Mesas', '🪑', 1),
('TV', '📺', 1),
('Audio', '🎵', 1),
('Sala activa', '✅', 1),
('Sillas universitarias', '🪑', 1),
('Mesones', '📐', 1);

-- Usuarios (contraseñas en texto plano)
INSERT INTO usuarios (rut, nombre_completo, email, contrasena, rol, estado) VALUES
('11111111-1', 'Administrador MAS', 'admin@mas.cl', 'admin123', 'administrador', 'activo'),
('22222222-2', 'Docente Ejemplo', 'docente@mas.cl', 'docente123', 'docente', 'activo'),
('33333333-3', 'Secretaria Ejemplo', 'secretaria@mas.cl', 'secretaria123', 'secretaria', 'activo'),
('44444444-4', 'Visualizador Ejemplo', 'visualizador@mas.cl', 'visualizador123', 'visualizador', 'activo');

-- Salas de Ejemplo
INSERT INTO salas (codigo_sala, nombre_sala, capacidad, id_tipo_sala, ubicacion, estado) VALUES
('A001', 'Aula 1', 40, 1, 'Edificio A, Piso 1', 'disponible'),
('A002', 'Aula 2', 35, 1, 'Edificio A, Piso 1', 'disponible'),
('A003', 'Aula 3', 45, 1, 'Edificio A, Piso 2', 'disponible'),
('B001', 'Laboratorio de Informatica', 25, 2, 'Edificio B, Piso 1', 'disponible'),
('B002', 'Laboratorio de Redes', 20, 2, 'Edificio B, Piso 2', 'disponible'),
('C001', 'Auditorio Central', 150, 3, 'Edificio C, Piso 1', 'disponible'),
('D001', 'Sala de Reuniones', 12, 4, 'Edificio D, Piso 1', 'disponible');

-- Bloques Horarios (16 bloques para Lunes a Sábado)
INSERT INTO bloques_horarios (numero_bloque, hora_inicio, hora_fin, dia_semana) VALUES
-- LUNES (1-16)
(1, '08:15', '09:05', 'lunes'),
(2, '09:10', '10:00', 'lunes'),
(3, '10:05', '10:55', 'lunes'),
(4, '11:00', '11:50', 'lunes'),
(5, '11:55', '12:45', 'lunes'),
(6, '12:50', '13:40', 'lunes'),
(7, '13:45', '14:35', 'lunes'),
(8, '14:40', '15:30', 'lunes'),
(9, '15:35', '16:25', 'lunes'),
(10, '16:30', '17:20', 'lunes'),
(11, '17:25', '18:15', 'lunes'),
(12, '18:20', '19:10', 'lunes'),
(13, '19:15', '20:05', 'lunes'),
(14, '20:10', '21:00', 'lunes'),
(15, '21:05', '21:55', 'lunes'),
(16, '22:00', '22:50', 'lunes'),
-- MARTES (17-32)
(17, '08:15', '09:05', 'martes'),
(18, '09:10', '10:00', 'martes'),
(19, '10:05', '10:55', 'martes'),
(20, '11:00', '11:50', 'martes'),
(21, '11:55', '12:45', 'martes'),
(22, '12:50', '13:40', 'martes'),
(23, '13:45', '14:35', 'martes'),
(24, '14:40', '15:30', 'martes'),
(25, '15:35', '16:25', 'martes'),
(26, '16:30', '17:20', 'martes'),
(27, '17:25', '18:15', 'martes'),
(28, '18:20', '19:10', 'martes'),
(29, '19:15', '20:05', 'martes'),
(30, '20:10', '21:00', 'martes'),
(31, '21:05', '21:55', 'martes'),
(32, '22:00', '22:50', 'martes'),
-- MIERCOLES (33-48)
(33, '08:15', '09:05', 'miercoles'),
(34, '09:10', '10:00', 'miercoles'),
(35, '10:05', '10:55', 'miercoles'),
(36, '11:00', '11:50', 'miercoles'),
(37, '11:55', '12:45', 'miercoles'),
(38, '12:50', '13:40', 'miercoles'),
(39, '13:45', '14:35', 'miercoles'),
(40, '14:40', '15:30', 'miercoles'),
(41, '15:35', '16:25', 'miercoles'),
(42, '16:30', '17:20', 'miercoles'),
(43, '17:25', '18:15', 'miercoles'),
(44, '18:20', '19:10', 'miercoles'),
(45, '19:15', '20:05', 'miercoles'),
(46, '20:10', '21:00', 'miercoles'),
(47, '21:05', '21:55', 'miercoles'),
(48, '22:00', '22:50', 'miercoles'),
-- JUEVES (49-64)
(49, '08:15', '09:05', 'jueves'),
(50, '09:10', '10:00', 'jueves'),
(51, '10:05', '10:55', 'jueves'),
(52, '11:00', '11:50', 'jueves'),
(53, '11:55', '12:45', 'jueves'),
(54, '12:50', '13:40', 'jueves'),
(55, '13:45', '14:35', 'jueves'),
(56, '14:40', '15:30', 'jueves'),
(57, '15:35', '16:25', 'jueves'),
(58, '16:30', '17:20', 'jueves'),
(59, '17:25', '18:15', 'jueves'),
(60, '18:20', '19:10', 'jueves'),
(61, '19:15', '20:05', 'jueves'),
(62, '20:10', '21:00', 'jueves'),
(63, '21:05', '21:55', 'jueves'),
(64, '22:00', '22:50', 'jueves'),
-- VIERNES (65-80)
(65, '08:15', '09:05', 'viernes'),
(66, '09:10', '10:00', 'viernes'),
(67, '10:05', '10:55', 'viernes'),
(68, '11:00', '11:50', 'viernes'),
(69, '11:55', '12:45', 'viernes'),
(70, '12:50', '13:40', 'viernes'),
(71, '13:45', '14:35', 'viernes'),
(72, '14:40', '15:30', 'viernes'),
(73, '15:35', '16:25', 'viernes'),
(74, '16:30', '17:20', 'viernes'),
(75, '17:25', '18:15', 'viernes'),
(76, '18:20', '19:10', 'viernes'),
(77, '19:15', '20:05', 'viernes'),
(78, '20:10', '21:00', 'viernes'),
(79, '21:05', '21:55', 'viernes'),
(80, '22:00', '22:50', 'viernes'),
-- SABADO (81-96)
(81, '08:15', '09:05', 'sabado'),
(82, '09:10', '10:00', 'sabado'),
(83, '10:05', '10:55', 'sabado'),
(84, '11:00', '11:50', 'sabado'),
(85, '11:55', '12:45', 'sabado'),
(86, '12:50', '13:40', 'sabado'),
(87, '13:45', '14:35', 'sabado'),
(88, '14:40', '15:30', 'sabado'),
(89, '15:35', '16:25', 'sabado'),
(90, '16:30', '17:20', 'sabado'),
(91, '17:25', '18:15', 'sabado'),
(92, '18:20', '19:10', 'sabado'),
(93, '19:15', '20:05', 'sabado'),
(94, '20:10', '21:00', 'sabado'),
(95, '21:05', '21:55', 'sabado'),
(96, '22:00', '22:50', 'sabado');