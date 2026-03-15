-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-03-2026 a las 21:41:50
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12
USE `if0_41391897_sistema_tutorias`;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_tutorias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad`
--

CREATE TABLE `disponibilidad` (
  `id_disponibilidad` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('disponible','cancelada','completada') DEFAULT 'disponible',
  `cupo_maximo` int(11) NOT NULL DEFAULT 8,
  `cupo_actual` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad_materias`
--

CREATE TABLE `disponibilidad_materias` (
  `id_disponibilidad_materia` int(11) NOT NULL,
  `id_disponibilidad` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `codigo_estudiantil` varchar(20) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `semestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id_estudiante`, `id_usuario`, `codigo_estudiantil`, `carrera`, `semestre`) VALUES
(1, 6, '2021001', 'Ingeniería de Sistemas', 6),
(2, 7, '2021002', 'Ingeniería Electrónica', 4),
(3, 8, '2021003', 'Ingeniería Civil', 8),
(4, 9, '2021004', 'Ingeniería Química', 2),
(5, 10, '2021005', 'Arquitectura', 5),
(6, 11, '2021006', 'Medicina', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activa','inactiva') DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Matemáticas', 'Álgebra, cálculo, estadística y más', 'activa'),
(2, 'Física', 'Mecánica, termodinámica, electromagnetismo', 'activa'),
(3, 'Química', 'Química orgánica, inorgánica, analítica', 'activa'),
(4, 'Programación', 'Desarrollo de software y algoritmos', 'activa'),
(5, 'Inglés', 'Idioma inglés para comunicación técnica', 'activa'),
(6, 'Estadística', 'Análisis de datos y probabilidad', 'activa'),
(7, 'Electrónica', 'Circuitos, componentes y sistemas digitales', 'activa'),
(8, 'Mecánica', 'Diseño mecánico y análisis estructural', 'activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opiniones`
--

CREATE TABLE `opiniones` (
  `id_opinion` int(11) NOT NULL,
  `id_tutoria` int(11) NOT NULL,
  `id_emisor` int(11) NOT NULL,
  `id_receptor` int(11) NOT NULL,
  `tipo_emisor` enum('profesor','estudiante') NOT NULL,
  `tipo_receptor` enum('profesor','estudiante') NOT NULL,
  `calificacion` int(1) NOT NULL CHECK (`calificacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha_opinion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('visible','oculto') DEFAULT 'visible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `opiniones`
--

INSERT INTO `opiniones` (`id_opinion`, `id_tutoria`, `id_emisor`, `id_receptor`, `tipo_emisor`, `tipo_receptor`, `calificacion`, `comentario`, `fecha_opinion`, `estado`) VALUES
(1, 1, 4, 1, 'profesor', 'estudiante', 5, 'Excelente estudiante, muy participativo', '2026-03-14 20:26:51', 'visible'),
(2, 2, 4, 2, 'profesor', 'estudiante', 4, 'Buena participación, podría mejorar en la puntualidad', '2026-03-14 20:26:51', 'visible'),
(3, 1, 1, 1, 'estudiante', 'profesor', 5, 'El profesor explica muy bien, muy claro', '2026-03-14 20:26:51', 'visible'),
(4, 2, 2, 2, 'estudiante', 'profesor', 4, 'Buen profesor, las clases son interesantes', '2026-03-14 20:26:51', 'visible'),
(5, 4, 4, 3, 'estudiante', 'profesor', 3, 'Regular, a veces va muy rápido', '2026-03-14 20:26:51', 'visible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_profesor` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_profesor`, `id_usuario`, `especialidad`, `departamento`) VALUES
(1, 2, 'Matemáticas', 'Ciencias Exactas'),
(2, 3, 'Física', 'Ciencias Naturales'),
(3, 4, 'Programación', 'Ingeniería'),
(4, 5, 'Inglés', 'Lenguas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutoria_inscripciones`
--

CREATE TABLE `tutoria_inscripciones` (
  `id_inscripcion` int(11) NOT NULL,
  `id_disponibilidad` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `estado` enum('inscrito','cancelado','asistio','no_asistio') DEFAULT 'inscrito',
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutoria_inscripciones_materias`
--

CREATE TABLE `tutoria_inscripciones_materias` (
  `id_inscripcion_materia` int(11) NOT NULL,
  `id_inscripcion` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tutoria_inscripciones_materias`
--

INSERT INTO `tutoria_inscripciones_materias` (`id_inscripcion_materia`, `id_inscripcion`, `id_materia`) VALUES
(1, 2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','profesor','estudiante') NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `password`, `rol`, `estado`, `fecha_registro`) VALUES
(1, 'Admin Principal', 'admin@sistema.com', 'admin123', 'administrador', 'activo', '2026-03-14 20:26:51'),
(2, 'Dr. Carlos Méndez', 'carlos.mendez@sistema.com', 'profesor123', 'profesor', 'activo', '2026-03-14 20:26:51'),
(3, 'Dra. Ana Rodríguez', 'ana.rodriguez@sistema.com', 'profesor123', 'profesor', 'activo', '2026-03-14 20:26:51'),
(4, 'Dr. Luis Torres', 'luis.torres@sistema.com', 'profesor123', 'profesor', 'activo', '2026-03-14 20:26:51'),
(5, 'Dra. María González', 'maria.gonzalez@sistema.com', 'profesor123', 'profesor', 'activo', '2026-03-14 20:26:51'),
(6, 'Juan Pérez', 'juan.perez@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51'),
(7, 'María López', 'maria.lopez@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51'),
(8, 'Carlos Sánchez', 'carlos.sanchez@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51'),
(9, 'Ana Martínez', 'ana.martinez@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51'),
(10, 'Luis Ramírez', 'luis.ramirez@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51'),
(11, 'Sofía Herrera', 'sofia.herrera@sistema.com', 'estudiante123', 'estudiante', 'activo', '2026-03-14 20:26:51');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  ADD PRIMARY KEY (`id_disponibilidad`),
  ADD KEY `idx_profesor` (`id_profesor`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_materia` (`id_materia`);

--
-- Indices de la tabla `disponibilidad_materias`
--
ALTER TABLE `disponibilidad_materias`
  ADD PRIMARY KEY (`id_disponibilidad_materia`),
  ADD UNIQUE KEY `unique_disponibilidad_materia` (`id_disponibilidad`,`id_materia`),
  ADD KEY `idx_disponibilidad` (`id_disponibilidad`),
  ADD KEY `idx_materia` (`id_materia`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD UNIQUE KEY `unique_usuario` (`id_usuario`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `unique_nombre` (`nombre`);

--
-- Indices de la tabla `opiniones`
--
ALTER TABLE `opiniones`
  ADD PRIMARY KEY (`id_opinion`),
  ADD UNIQUE KEY `unique_opinion` (`id_tutoria`,`id_emisor`,`tipo_emisor`),
  ADD KEY `idx_tutoria` (`id_tutoria`),
  ADD KEY `idx_emisor` (`id_emisor`),
  ADD KEY `idx_receptor` (`id_receptor`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_profesor`),
  ADD UNIQUE KEY `unique_usuario` (`id_usuario`);

--
-- Indices de la tabla `tutoria_inscripciones`
--
ALTER TABLE `tutoria_inscripciones`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD KEY `idx_disponibilidad` (`id_disponibilidad`),
  ADD KEY `idx_estudiante` (`id_estudiante`),
  ADD KEY `idx_profesor` (`id_profesor`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_materia` (`id_materia`);

--
-- Indices de la tabla `tutoria_inscripciones_materias`
--
ALTER TABLE `tutoria_inscripciones_materias`
  ADD PRIMARY KEY (`id_inscripcion_materia`),
  ADD UNIQUE KEY `unique_inscripcion_materia` (`id_inscripcion`,`id_materia`),
  ADD KEY `idx_inscripcion` (`id_inscripcion`),
  ADD KEY `idx_materia` (`id_materia`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `unique_correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  MODIFY `id_disponibilidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `disponibilidad_materias`
--
ALTER TABLE `disponibilidad_materias`
  MODIFY `id_disponibilidad_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `opiniones`
--
ALTER TABLE `opiniones`
  MODIFY `id_opinion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tutoria_inscripciones`
--
ALTER TABLE `tutoria_inscripciones`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tutoria_inscripciones_materias`
--
ALTER TABLE `tutoria_inscripciones_materias`
  MODIFY `id_inscripcion_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
