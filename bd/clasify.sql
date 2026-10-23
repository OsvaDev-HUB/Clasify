-- ============================================================
-- Base de datos del sistema Clasify
-- Se puede importar desde phpMyAdmin (pestaña Importar)
-- o pegar completo en la pestaña SQL.
-- OJO: borra la base clasify si ya existe.
-- ============================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS clasify;
CREATE DATABASE clasify CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE clasify;

-- ------------------------------------------------------------
-- Usuarios del sistema (alumno, apoderado, profesor y administrativo)
-- Todos inician sesión con su RUT, por eso el RUT no se puede repetir.
-- ------------------------------------------------------------
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    clave VARCHAR(255) NOT NULL,
    rol ENUM('alumno', 'apoderado', 'profesor', 'administrativo') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Datos que solo tiene el alumno.
-- id_alumno es el mismo id del usuario.
-- Cada alumno tiene un solo apoderado (id_apoderado).
-- ------------------------------------------------------------
CREATE TABLE alumno (
    id_alumno INT PRIMARY KEY,
    id_apoderado INT NOT NULL,
    estado ENUM('regular', 'egresado', 'retirado') NOT NULL DEFAULT 'regular',
    FOREIGN KEY (id_alumno) REFERENCES usuario (id_usuario),
    FOREIGN KEY (id_apoderado) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Año académico (por ejemplo 2026)
-- ------------------------------------------------------------
CREATE TABLE anio_academico (
    id_anio INT AUTO_INCREMENT PRIMARY KEY,
    anio INT NOT NULL UNIQUE,
    estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto'
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Cursos de cada año.
-- nivel: 1 a 8 son 1° a 8° Básico, 9 a 12 son 1° a 4° Medio.
-- No se puede repetir el mismo nivel y letra en un mismo año.
-- ------------------------------------------------------------
CREATE TABLE curso (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    id_anio INT NOT NULL,
    nivel INT NOT NULL,
    letra CHAR(1) NOT NULL,
    id_profesor_jefe INT NOT NULL,
    estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto',
    UNIQUE (id_anio, nivel, letra),
    FOREIGN KEY (id_anio) REFERENCES anio_academico (id_anio),
    FOREIGN KEY (id_profesor_jefe) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Asignaturas. influye_promocion = 0 para Religión,
-- Consejo de Curso y Orientación (Decreto 67).
-- ------------------------------------------------------------
CREATE TABLE asignatura (
    id_asignatura INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    influye_promocion TINYINT(1) NOT NULL DEFAULT 1
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Qué profesor hace cada asignatura en cada curso.
-- ------------------------------------------------------------
CREATE TABLE asignacion_docente (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_asignatura INT NOT NULL,
    id_profesor INT NOT NULL,
    UNIQUE (id_curso, id_asignatura),
    FOREIGN KEY (id_curso) REFERENCES curso (id_curso),
    FOREIGN KEY (id_asignatura) REFERENCES asignatura (id_asignatura),
    FOREIGN KEY (id_profesor) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Inscripción (matrícula): une a un alumno con un curso de un año.
-- De aquí cuelgan las notas, la asistencia y las cuotas,
-- así se guarda el historial de cada año.
-- situacion_final queda vacía hasta el cierre de año.
-- ------------------------------------------------------------
CREATE TABLE inscripcion (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_curso INT NOT NULL,
    situacion_final ENUM('promovido', 'no_cumple', 'promovido_excepcion') NULL,
    UNIQUE (id_alumno, id_curso),
    FOREIGN KEY (id_alumno) REFERENCES alumno (id_alumno),
    FOREIGN KEY (id_curso) REFERENCES curso (id_curso)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Notas de 1,0 a 7,0 con un decimal.
-- ------------------------------------------------------------
CREATE TABLE nota (
    id_nota INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    id_asignatura INT NOT NULL,
    valor DECIMAL(2,1) NOT NULL,
    fecha DATE NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    semestre TINYINT NOT NULL,
    CHECK (valor >= 1.0 AND valor <= 7.0),
    CHECK (semestre = 1 OR semestre = 2),
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion (id_inscripcion),
    FOREIGN KEY (id_asignatura) REFERENCES asignatura (id_asignatura)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Asistencia diaria (1 = presente, 0 = ausente).
-- Un alumno tiene un solo registro por día.
-- ------------------------------------------------------------
CREATE TABLE asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    fecha DATE NOT NULL,
    presente TINYINT(1) NOT NULL,
    UNIQUE (id_inscripcion, fecha),
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion (id_inscripcion)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Anotaciones que escribe un profesor sobre un alumno.
-- ------------------------------------------------------------
CREATE TABLE anotacion (
    id_anotacion INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_profesor INT NOT NULL,
    texto TEXT NOT NULL,
    fecha DATE NOT NULL,
    FOREIGN KEY (id_alumno) REFERENCES alumno (id_alumno),
    FOREIGN KEY (id_profesor) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Cuotas de mensualidad de cada inscripción.
-- ------------------------------------------------------------
CREATE TABLE cuota (
    id_cuota INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    numero INT NOT NULL,
    monto INT NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('pendiente', 'atrasada', 'pagada') NOT NULL DEFAULT 'pendiente',
    UNIQUE (id_inscripcion, numero),
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion (id_inscripcion)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Pago de una cuota. Una cuota tiene como máximo un pago
-- (por eso id_cuota es UNIQUE). Se guarda quién lo registró.
-- ------------------------------------------------------------
CREATE TABLE pago (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_cuota INT NOT NULL UNIQUE,
    fecha DATE NOT NULL,
    monto INT NOT NULL,
    registrado_por INT NOT NULL,
    FOREIGN KEY (id_cuota) REFERENCES cuota (id_cuota),
    FOREIGN KEY (registrado_por) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Excepción del cierre de año. Una inscripción tiene como
-- máximo una excepción. Se guarda el motivo, quién y cuándo.
-- ------------------------------------------------------------
CREATE TABLE excepcion (
    id_excepcion INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL UNIQUE,
    motivo TEXT NOT NULL,
    fecha DATE NOT NULL,
    registrada_por INT NOT NULL,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion (id_inscripcion),
    FOREIGN KEY (registrada_por) REFERENCES usuario (id_usuario)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Registro de cada correo de aviso de cuota impaga.
-- ------------------------------------------------------------
CREATE TABLE aviso_correo (
    id_aviso INT AUTO_INCREMENT PRIMARY KEY,
    id_cuota INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    correo_destino VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_cuota) REFERENCES cuota (id_cuota)
) ENGINE = InnoDB;


-- ============================================================
-- DATOS DE PRUEBA
-- La contraseña de todos los usuarios de prueba es: clasify123
-- ============================================================

INSERT INTO usuario (id_usuario, rut, nombre, correo, clave, rol) VALUES
(1, '11111111-1', 'Marcela Rojas Pinto', 'mrojas@clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'administrativo'),
(2, '15234567-4', 'Jorge Paredes Soto', 'jparedes@clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'profesor'),
(3, '13876543-1', 'Carolina Soto Fuentes', 'csoto@clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'profesor'),
(4, '16456789-3', 'Luis Vera Cárcamo', 'lvera@clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'profesor'),
(5, '12987654-9', 'Patricia Muñoz Leal', 'pmunoz@correo.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'apoderado'),
(6, '14345678-1', 'Ricardo Álvarez Mella', 'ralvarez@correo.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'apoderado'),
(7, '17654321-3', 'Claudia Herrera Ruiz', 'cherrera@correo.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'apoderado'),
(8, '18234567-9', 'Andrea Vargas Toledo', 'avargas@correo.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'apoderado'),
(9, '21345678-4', 'Tomás Fuentes Muñoz', 'tfuentes@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno'),
(10, '22123456-1', 'Sofía Fuentes Muñoz', 'sfuentes@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno'),
(11, '21456789-K', 'Benjamín Álvarez Ortiz', 'balvarez@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno'),
(12, '22234567-7', 'Martina Díaz Herrera', 'mdiaz@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno'),
(13, '21567890-3', 'Diego Herrera Ruiz', 'dherrera@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno'),
(14, '22345678-2', 'Josefa Vargas Toledo', 'jvargas@alumnos.clasify.cl', '$2y$10$vWFtXI538vWUl7jy2rL02ep0/0Rb1G.vHVQWRAP6fhm7lsmiG3FkS', 'alumno');

-- Patricia es apoderada de Tomás y Sofía (dos hijos)
INSERT INTO alumno (id_alumno, id_apoderado) VALUES
(9, 5),
(10, 5),
(11, 6),
(12, 7),
(13, 7),
(14, 8);

INSERT INTO anio_academico (id_anio, anio, estado) VALUES
(1, 2025, 'cerrado'),
(2, 2026, 'abierto');

-- 2025: 8° Básico A y 1° Medio A (ya cerrados)
-- 2026: 1° Medio A y 2° Medio A
INSERT INTO curso (id_curso, id_anio, nivel, letra, id_profesor_jefe, estado) VALUES
(1, 1, 8, 'A', 3, 'cerrado'),
(2, 1, 9, 'A', 2, 'cerrado'),
(3, 2, 9, 'A', 2, 'abierto'),
(4, 2, 10, 'A', 3, 'abierto');

INSERT INTO asignatura (id_asignatura, nombre, influye_promocion) VALUES
(1, 'Lenguaje y Literatura', 1),
(2, 'Matemática', 1),
(3, 'Historia y Geografía', 1),
(4, 'Ciencias Naturales', 1),
(5, 'Inglés', 1),
(6, 'Educación Física', 1),
(7, 'Religión', 0),
(8, 'Orientación', 0);

INSERT INTO asignacion_docente (id_curso, id_asignatura, id_profesor) VALUES
(3, 1, 3), (3, 2, 2), (3, 3, 4), (3, 4, 4), (3, 5, 4), (3, 6, 2), (3, 7, 3), (3, 8, 2),
(4, 1, 3), (4, 2, 2), (4, 3, 4), (4, 4, 4), (4, 5, 4), (4, 6, 2), (4, 7, 3), (4, 8, 3);

-- Inscripciones 2025 (con su situación final) y 2026
INSERT INTO inscripcion (id_inscripcion, id_alumno, id_curso, situacion_final) VALUES
(1, 10, 1, 'promovido'),
(2, 12, 1, 'promovido'),
(3, 14, 1, 'promovido'),
(4, 9, 2, 'promovido'),
(5, 11, 2, 'promovido'),
(6, 13, 2, 'promovido'),
(7, 10, 3, NULL),
(8, 12, 3, NULL),
(9, 14, 3, NULL),
(10, 9, 4, NULL),
(11, 11, 4, NULL),
(12, 13, 4, NULL);

-- Algunas notas del año 2025 (para probar el historial)
INSERT INTO nota (id_inscripcion, id_asignatura, valor, fecha, descripcion, semestre) VALUES
(1, 1, 6.2, '2025-05-10', 'Prueba 1', 1),
(1, 2, 5.8, '2025-05-12', 'Prueba 1', 1),
(1, 1, 6.5, '2025-10-08', 'Prueba 2', 2),
(1, 2, 6.0, '2025-10-10', 'Prueba 2', 2),
(4, 1, 5.1, '2025-05-10', 'Prueba 1', 1),
(4, 2, 4.4, '2025-05-12', 'Prueba 1', 1),
(4, 1, 5.6, '2025-10-08', 'Prueba 2', 2),
(4, 2, 4.9, '2025-10-10', 'Prueba 2', 2);

-- Notas 2026 de 1° Medio A
INSERT INTO nota (id_inscripcion, id_asignatura, valor, fecha, descripcion, semestre) VALUES
(7, 2, 6.1, '2026-04-15', 'Prueba 1', 1),
(8, 2, 3.8, '2026-04-15', 'Prueba 1', 1),
(9, 2, 5.5, '2026-04-15', 'Prueba 1', 1),
(7, 2, 6.7, '2026-06-03', 'Trabajo grupal', 1),
(8, 2, 4.5, '2026-06-03', 'Trabajo grupal', 1),
(9, 2, 5.0, '2026-06-03', 'Trabajo grupal', 1),
(7, 1, 5.9, '2026-05-20', 'Control de lectura', 1),
(8, 1, 6.3, '2026-05-20', 'Control de lectura', 1),
(9, 1, 4.8, '2026-05-20', 'Control de lectura', 1),
(7, 2, 5.4, '2026-09-02', 'Prueba 2', 2),
(8, 2, 4.1, '2026-09-02', 'Prueba 2', 2),
(9, 2, 6.0, '2026-09-02', 'Prueba 2', 2);

-- Notas 2026 de 2° Medio A
INSERT INTO nota (id_inscripcion, id_asignatura, valor, fecha, descripcion, semestre) VALUES
(10, 2, 4.6, '2026-04-16', 'Prueba 1', 1),
(11, 2, 5.2, '2026-04-16', 'Prueba 1', 1),
(12, 2, 3.5, '2026-04-16', 'Prueba 1', 1),
(10, 1, 5.0, '2026-05-21', 'Ensayo', 1),
(11, 1, 6.0, '2026-05-21', 'Ensayo', 1),
(12, 1, 4.2, '2026-05-21', 'Ensayo', 1);
