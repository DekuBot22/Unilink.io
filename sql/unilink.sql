CREATE DATABASE IF NOT EXISTS unilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unilink;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    carrera VARCHAR(120) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    telefono VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'estudiante',
    estado VARCHAR(30) NOT NULL DEFAULT 'activo',
    foto_perfil VARCHAR(255) NULL DEFAULT NULL,
    google_id VARCHAR(64) NULL DEFAULT NULL,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    email_token VARCHAR(64) NULL DEFAULT NULL,
    password_token VARCHAR(64) NULL DEFAULT NULL,
    password_token_expira DATETIME NULL DEFAULT NULL,
    ban_motivo TEXT NULL,
    ban_fecha DATETIME NULL,
    creado_en DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS postulaciones_tutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(10) NOT NULL,
    carrera VARCHAR(120) NOT NULL,
    semestre VARCHAR(50) NOT NULL,
    materia TEXT NOT NULL,
    promedio INT NOT NULL,
    motivacion TEXT NOT NULL,
    modalidad VARCHAR(60) NOT NULL,
    creado_en DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS tutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre VARCHAR(120) NOT NULL,
    carrera VARCHAR(120) NOT NULL,
    semestre VARCHAR(50) NOT NULL,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    sesiones INT NOT NULL DEFAULT 0,
    num_calificaciones INT NOT NULL DEFAULT 0,
    materias TEXT NOT NULL,
    tags TEXT NOT NULL,
    bio TEXT NOT NULL,
    disponibilidad TEXT NOT NULL,
    resenas TEXT NOT NULL,
    creado_en DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS contenidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    tutor_nombre VARCHAR(120) NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    descripcion TEXT NULL,
    materia VARCHAR(120) NOT NULL DEFAULT 'General',
    tema VARCHAR(120) NOT NULL DEFAULT 'Recurso',
    tipo VARCHAR(30) NOT NULL,
    archivo_nombre VARCHAR(255) NOT NULL,
    archivo_ruta VARCHAR(255) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL
);

INSERT INTO tutores (
    usuario_id,
    nombre,
    carrera,
    semestre,
    rating,
    sesiones,
    materias,
    tags,
    bio,
    disponibilidad,
    resenas,
    creado_en
) VALUES
    (NULL, 'Maria Lopez', 'Ing. de Sistemas', '7mo semestre', 4.9, 120,
     '["Calculo I","Programacion","Algebra Lineal"]',
     '["calculo","programacion","algebra"]',
     'Estudiante de Ingenieria de Sistemas enfocada en apoyar a sus companeros en matematicas y programacion.',
     '[{"dia":"Lunes","horas":"Manana"},{"dia":"Miercoles","horas":"Tarde"},{"dia":"Sabado","horas":"Manana"}]',
     '[{"nombre":"Luisa C.","estrellas":5,"texto":"Explica de forma clara y ordenada."},{"nombre":"Felipe M.","estrellas":4,"texto":"Me ayudo a entender calculo."}]',
     NOW()),
    (NULL, 'Carlos Perez', 'Medicina', '5to semestre', 4.8, 95,
     '["Quimica","Biologia","Anatomia"]',
     '["quimica","biologia"]',
     'Tutor con experiencia en ciencias basicas de Medicina. Enfocado en resolver dudas con ejemplos practicos.',
     '[{"dia":"Martes","horas":"Manana"},{"dia":"Jueves","horas":"Tarde"},{"dia":"Sabado","horas":"Manana"}]',
     '[{"nombre":"Daniela P.","estrellas":5,"texto":"Muy paciente y claro explicando."}]',
     NOW()),
    (NULL, 'Ana Gomez', 'Derecho', '6to semestre', 4.9, 80,
     '["Derecho Constitucional","Redaccion"]',
     '["derecho"]',
     'Estudiante de Derecho comprometida con la ensenanza. Me encanta ayudar a entender textos juridicos.',
     '[{"dia":"Lunes","horas":"Manana"},{"dia":"Viernes","horas":"Manana"},{"dia":"Sabado","horas":"Manana"}]',
     '[{"nombre":"Luisa C.","estrellas":5,"texto":"Excelente metodologia y explicaciones claras."},{"nombre":"Felipe A.","estrellas":5,"texto":"Constitucional fue mas facil con Ana."}]',
     NOW()),
    (NULL, 'Luis Martinez', 'Ing. Civil', '8vo semestre', 4.7, 70,
     '["Fisica I","Calculo II","Estatica"]',
     '["fisica","calculo"]',
     'Pasion por la fisica y las matematicas aplicadas a la ingenieria. Tutorias con enfoque practico.',
     '[{"dia":"Martes","horas":"Tarde"},{"dia":"Jueves","horas":"Tarde, Noche"},{"dia":"Sabado","horas":"Manana"}]',
     '[{"nombre":"Andrea F.","estrellas":5,"texto":"Explica muy bien y con paciencia."},{"nombre":"Mario C.","estrellas":4,"texto":"Muy recomendado."}]',
     NOW()),
    (NULL, 'Sofia Diaz', 'Administracion', '6to semestre', 4.8, 60,
     '["Estadistica","Contabilidad"]',
     '["estadistica"]',
     'Me apasiona simplificar la estadistica y la contabilidad para todos.',
     '[{"dia":"Lunes","horas":"Noche"},{"dia":"Miercoles","horas":"Tarde"},{"dia":"Viernes","horas":"Tarde, Noche"}]',
     '[{"nombre":"Carlos M.","estrellas":5,"texto":"Hace la estadistica facil."}]',
     NOW()),
    (NULL, 'Juan Ramos', 'Ing. de Sistemas', '9no semestre', 4.6, 110,
     '["Programacion","Bases de Datos","Redes"]',
     '["programacion"]',
     'Desarrollador y tutor. Especialista en programacion web y bases de datos relacionales.',
     '[{"dia":"Lunes","horas":"Noche"},{"dia":"Jueves","horas":"Noche"},{"dia":"Sabado","horas":"Manana, Tarde"}]',
     '[{"nombre":"Laura P.","estrellas":5,"texto":"Me salvo en Bases de Datos."},{"nombre":"Diego R.","estrellas":4,"texto":"Muy buen tutor."}]',
     NOW());

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    tutor_id INT NULL,
    tutor_nombre VARCHAR(120) NOT NULL,
    estudiante_nombre VARCHAR(120) NOT NULL,
    estudiante_correo VARCHAR(150) NOT NULL,
    materia VARCHAR(120) NOT NULL,
    modalidad VARCHAR(60) NOT NULL DEFAULT 'Virtual (Meet/Zoom)',
    enlace_reunion VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    calificacion INT NULL,
    cancelada_por VARCHAR(30) NULL,
    cancelacion_motivo TEXT NULL,
    creado_en DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS tutor_calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    calificacion INT NOT NULL,
    creado_en DATETIME NOT NULL,
    UNIQUE KEY unique_user_tutor (tutor_id, usuario_id)
);

CREATE TABLE IF NOT EXISTS tutor_resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nombre_usuario VARCHAR(120) NOT NULL,
    texto TEXT NOT NULL,
    creado_en DATETIME NOT NULL,
    UNIQUE KEY unique_user_tutor_resena (tutor_id, usuario_id)
);

CREATE TABLE IF NOT EXISTS cita_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    creado_en DATETIME NOT NULL,
    UNIQUE KEY unique_cita_usuario (cita_id, usuario_id)
);



CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    url VARCHAR(255) NULL DEFAULT NULL,
    creado_en DATETIME NOT NULL,
    INDEX idx_notif_usuario (usuario_id, leida),
    INDEX idx_notif_fecha (usuario_id, creado_en)
);



CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emisor_id INT NOT NULL,
    receptor_id INT NOT NULL,
    texto TEXT NOT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL,
    INDEX idx_conv (emisor_id, receptor_id),
    INDEX idx_receptor (receptor_id, leido)
);

