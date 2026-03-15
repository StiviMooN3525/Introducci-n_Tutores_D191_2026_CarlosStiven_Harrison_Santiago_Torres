-- Base de Datos Sistema de Tutorías - Versión InfinityFree Optimizada
-- Compatible con hosting gratuito (sin triggers, sin views, sin claves foráneas complejas)

USE `if0_41391897_sistema_tutorias`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','profesor','estudiante') NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `unique_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id_estudiante` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `codigo_estudiantil` varchar(20) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `semestre` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `unique_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE IF NOT EXISTS `profesores` (
  `id_profesor` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_profesor`),
  UNIQUE KEY `unique_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE IF NOT EXISTS `materias` (
  `id_materia` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  PRIMARY KEY (`id_materia`),
  UNIQUE KEY `unique_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`nombre`, `descripcion`) VALUES
('Matemáticas', 'Álgebra, cálculo, estadística y más'),
('Física', 'Mecánica, termodinámica, electromagnetismo'),
('Química', 'Química orgánica, inorgánica, analítica'),
('Programación', 'Desarrollo de software y algoritmos'),
('Inglés', 'Idioma inglés para comunicación técnica'),
('Estadística', 'Análisis de datos y probabilidad'),
('Electrónica', 'Circuitos, componentes y sistemas digitales'),
('Mecánica', 'Diseño mecánico y análisis estructural');

--
-- Estructura de tabla para la tabla `disponibilidad`
--

CREATE TABLE IF NOT EXISTS `disponibilidad` (
  `id_disponibilidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_profesor` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('disponible','cancelada','completada') DEFAULT 'disponible',
  `cupo_maximo` int(11) NOT NULL DEFAULT 8,
  `cupo_actual` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_disponibilidad`),
  KEY `idx_profesor` (`id_profesor`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_estado` (`estado`),
  KEY `idx_materia` (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `tutoria_inscripciones`
--

CREATE TABLE IF NOT EXISTS `tutoria_inscripciones` (
  `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_disponibilidad` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `estado` enum('inscrito','cancelado','asistio','no_asistio') DEFAULT 'inscrito',
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_inscripcion`),
  KEY `idx_disponibilidad` (`id_disponibilidad`),
  KEY `idx_estudiante` (`id_estudiante`),
  KEY `idx_profesor` (`id_profesor`),
  KEY `idx_estado` (`estado`),
  KEY `idx_materia` (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `disponibilidad_materias` (sistema flexible)
--

CREATE TABLE IF NOT EXISTS `disponibilidad_materias` (
  `id_disponibilidad_materia` int(11) NOT NULL AUTO_INCREMENT,
  `id_disponibilidad` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  PRIMARY KEY (`id_disponibilidad_materia`),
  UNIQUE KEY `unique_disponibilidad_materia` (`id_disponibilidad`, `id_materia`),
  KEY `idx_disponibilidad` (`id_disponibilidad`),
  KEY `idx_materia` (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `tutoria_inscripciones_materias` (sistema flexible)
--

CREATE TABLE IF NOT EXISTS `tutoria_inscripciones_materias` (
  `id_inscripcion_materia` int(11) NOT NULL AUTO_INCREMENT,
  `id_inscripcion` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  PRIMARY KEY (`id_inscripcion_materia`),
  UNIQUE KEY `unique_inscripcion_materia` (`id_inscripcion`, `id_materia`),
  KEY `idx_inscripcion` (`id_inscripcion`),
  KEY `idx_materia` (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Estructura de tabla para la tabla `opiniones` (sistema de calificaciones)
--

CREATE TABLE IF NOT EXISTS `opiniones` (
  `id_opinion` int(11) NOT NULL AUTO_INCREMENT,
  `id_tutoria` int(11) NOT NULL,
  `id_emisor` int(11) NOT NULL,
  `id_receptor` int(11) NOT NULL,
  `tipo_emisor` enum('profesor','estudiante') NOT NULL,
  `tipo_receptor` enum('profesor','estudiante') NOT NULL,
  `calificacion` int(1) NOT NULL CHECK (`calificacion` BETWEEN 1 AND 5),
  `comentario` text DEFAULT NULL,
  `fecha_opinion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('visible','oculto') DEFAULT 'visible',
  PRIMARY KEY (`id_opinion`),
  UNIQUE KEY `unique_opinion` (`id_tutoria`, `id_emisor`, `tipo_emisor`),
  KEY `idx_tutoria` (`id_tutoria`),
  KEY `idx_emisor` (`id_emisor`),
  KEY `idx_receptor` (`id_receptor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- NOTA: Sin TRIGGERS (compatible con hosting gratuito)
-- La lógica de cupos se maneja en el código PHP
--

--
-- NOTA: Sin VIEWS (compatible con hosting gratuito)
-- Las consultas complejas se manejan en el código PHP
--

--
-- NOTA: Sin claves foráneas complejas (compatible con hosting gratuito)
-- Las relaciones se validan en el código PHP
--

COMMIT;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
