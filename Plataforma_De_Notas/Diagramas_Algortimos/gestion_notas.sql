-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-05-2025 a las 06:36:00
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_notas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_evaluables`
--

CREATE TABLE `actividades_evaluables` (
  `id_actividad` int(11) NOT NULL,
  `id_grupo_fk` int(11) NOT NULL COMMENT 'Grupo al que pertenece esta actividad',
  `titulo_actividad` varchar(255) NOT NULL,
  `descripcion_actividad` text DEFAULT NULL,
  `tipo_actividad` enum('Tarea','Examen Parcial','Examen Final','Quiz','Proyecto','Participacion','Laboratorio','Otro') NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega_limite` datetime DEFAULT NULL COMMENT 'Fecha y hora límite para la entrega o realización',
  `ponderacion` decimal(5,2) UNSIGNED DEFAULT NULL COMMENT 'Peso porcentual de la actividad en la nota final (ej: 0.20 para 20%). La suma por curso debería ser 1.00 o 100',
  `max_calificacion` decimal(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Calificación máxima posible para esta actividad (ej: 5.0, 10.0, 100.0)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Actividades evaluables de un grupo (tareas, exámenes, etc.)';

--
-- Volcado de datos para la tabla `actividades_evaluables`
--

INSERT INTO `actividades_evaluables` (`id_actividad`, `id_grupo_fk`, `titulo_actividad`, `descripcion_actividad`, `tipo_actividad`, `fecha_publicacion`, `fecha_entrega_limite`, `ponderacion`, `max_calificacion`) VALUES
(501, 301, 'Tarea 1: Diseño de API', 'Diseñar la especificación OpenAPI para el proyecto.', 'Tarea', '2025-05-12 04:56:20', '2025-05-20 23:59:59', 0.25, 5.00),
(502, 301, 'Examen Parcial 1', 'Cubre unidades 1 y 2 del curso.', 'Examen Parcial', '2025-05-12 04:56:20', '2025-06-05 18:00:00', 0.35, 5.00),
(503, 301, 'Proyecto Final: Aplicación Web', 'Desarrollo completo de una aplicación web funcional.', 'Proyecto', '2025-05-12 04:56:20', '2025-07-15 23:59:59', 0.40, 5.00),
(504, 301, 'Quiz Rápido Semanal: APIs', 'Un quiz corto para repasar los conceptos fundamentales de diseño de APIs vistos esta semana.', 'Quiz', '2025-06-10 13:00:00', '2025-06-12 23:59:00', 0.05, 5.00),
(505, 301, 'Quiz Rápido Semanal: HTML5', 'Evaluaremos rápidamente la creación de una página en HTML con encabezado y títulos H1, H2 y un párrafo.', 'Quiz', '2025-05-12 19:36:47', '2025-05-14 18:00:00', 0.05, 5.00),
(506, 301, 'Tarea 2: Diseño HTML5', 'Crear una página web usando UNICAMENTE HTML5 que contenga una noticia del momento.', 'Tarea', '2025-05-12 19:38:25', '2025-05-21 18:00:00', 0.10, 5.00),
(507, 301, 'Investigacion BOOTSTRAP', 'Apreciados estudiantes,\npor favor realizar una investigación acerca de qué es y cómo funciona Bootstrap', 'Tarea', '2025-05-14 16:11:52', '2025-05-15 20:00:00', 0.10, 5.00),
(508, 301, 'Parcial 3 corte', 'Por favor prepararse para el parcial', 'Examen Parcial', '2025-05-14 23:49:31', '2025-05-14 18:00:00', 0.35, 5.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia_clase`
--

CREATE TABLE `asistencia_clase` (
  `id_asistencia` int(11) NOT NULL,
  `id_matricula_fk` int(11) NOT NULL COMMENT 'Referencia a la matrícula del estudiante en el grupo',
  `fecha_clase` date NOT NULL,
  `estado_asistencia` enum('Presente','Ausente','Justificado','Tardanza') NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de asistencia a clases';

--
-- Volcado de datos para la tabla `asistencia_clase`
--

INSERT INTO `asistencia_clase` (`id_asistencia`, `id_matricula_fk`, `fecha_clase`, `estado_asistencia`, `observaciones`) VALUES
(1, 1, '2025-05-02', 'Presente', NULL),
(2, 1, '2025-05-07', 'Ausente', 'Sin justificar.'),
(3, 1, '2025-05-09', 'Justificado', 'Cita médica (constancia adjunta).'),
(4, 1, '2025-05-14', 'Presente', NULL),
(5, 1, '2025-05-16', 'Tardanza', 'Llegó 15 minutos tarde.'),
(6, 2, '2025-05-12', 'Presente', ''),
(7, 1, '2025-05-12', 'Presente', ''),
(8, 2, '2025-05-15', 'Ausente', ''),
(9, 1, '2025-05-15', 'Tardanza', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(255) NOT NULL COMMENT 'Ej: Cálculo I, Programación Orientada a Objetos',
  `codigo_curso` varchar(20) DEFAULT NULL COMMENT 'Ej: MAT101, INF202. Puede ser NULL si no aplica.',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción breve del contenido del curso',
  `creditos` int(10) UNSIGNED DEFAULT NULL COMMENT 'Número de créditos del curso',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de cursos o asignaturas';

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `nombre_curso`, `codigo_curso`, `descripcion`, `creditos`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(201, 'Programación Web Avanzada', 'PWA401', 'Curso enfocado en desarrollo de aplicaciones web modernas.', 4, '2025-05-12 04:56:20', '2025-05-12 04:56:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos_curso`
--

CREATE TABLE `grupos_curso` (
  `id_grupo` int(11) NOT NULL,
  `id_curso_fk` int(11) NOT NULL COMMENT 'Referencia al curso general',
  `id_profesor_fk` int(11) NOT NULL COMMENT 'Referencia al profesor asignado a este grupo',
  `nombre_grupo` varchar(100) NOT NULL COMMENT 'Ej: Grupo A, Sección 01, Mañana',
  `semestre` varchar(20) DEFAULT NULL COMMENT 'Ej: 2025-1, 2025-2. Identificador del período académico',
  `horario_descripcion` text DEFAULT NULL COMMENT 'Descripción del horario, ej: Lunes y Miércoles 8-10 AM',
  `cupo_maximo` int(10) UNSIGNED DEFAULT 30,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos o secciones específicas de un curso';

--
-- Volcado de datos para la tabla `grupos_curso`
--

INSERT INTO `grupos_curso` (`id_grupo`, `id_curso_fk`, `id_profesor_fk`, `nombre_grupo`, `semestre`, `horario_descripcion`, `cupo_maximo`, `fecha_creacion`) VALUES
(301, 201, 101, 'Grupo A - Nocturno', '2025-1', 'Martes y Jueves 6PM-9PM', 25, '2025-05-12 04:56:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informes_academicos`
--

CREATE TABLE `informes_academicos` (
  `id_informe` int(11) NOT NULL,
  `id_grupo_fk` int(11) NOT NULL COMMENT 'Grupo al que se refiere el informe',
  `id_profesor_autor_fk` int(11) NOT NULL COMMENT 'Profesor que crea el informe',
  `id_estudiante_destinatario_fk` int(11) DEFAULT NULL COMMENT 'Estudiante específico si el informe es individual, NULL si es para el grupo',
  `titulo_informe` varchar(255) NOT NULL,
  `contenido_informe` text NOT NULL,
  `archivo_adjunto_ruta` varchar(255) DEFAULT NULL COMMENT 'Ruta al archivo adjunto del informe',
  `tipo_informe` enum('Individual','Grupal','General') DEFAULT 'General',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Informes académicos generados';

--
-- Volcado de datos para la tabla `informes_academicos`
--

INSERT INTO `informes_academicos` (`id_informe`, `id_grupo_fk`, `id_profesor_autor_fk`, `id_estudiante_destinatario_fk`, `titulo_informe`, `contenido_informe`, `archivo_adjunto_ruta`, `tipo_informe`, `fecha_creacion`) VALUES
(1, 301, 101, 102, 'Informe de Desempeño Individual - PWA Q1', 'Estimada Ana,\n\nTu desempeño en el primer cuarto del curso de Programación Web Avanzada ha sido notable, especialmente en lo referente a la participación en clase y la calidad de la Tarea 1.\n\nAspectos a mejorar:\n- Profundizar en los conceptos de APIs RESTful.\n- Mayor atención a los casos de prueba unitaria.\n\nSigue así.\n\nAtentamente,\nProf. Carlos Villa', NULL, 'Individual', '2025-05-25 15:00:00'),
(2, 301, 101, NULL, 'Recordatorio Importante: Examen Parcial 1 - PWA', 'Estimados estudiantes del Grupo A - Nocturno de Programación Web Avanzada,\r\n\r\nLes recuerdo que el Examen Parcial 1 está programado para el día 05 de Abril de 2025 a las 6:00 PM.\r\n\r\nTemas a evaluar: Unidades 1 y 2.\r\nModalidad: Teórico-práctico.\r\n\r\nAsegúrense de repasar el material y llegar a tiempo.\r\n\r\nSaludos,\r\nProf. Andrés Díaz', NULL, 'General', '2025-05-28 20:30:00'),
(3, 301, 101, NULL, 'Recordatorio Importante: Examen Parcial 2 - PWA', 'Estimados estudiantes del Grupo A - Nocturno de Programación Web Avanzada,\r\n\r\nLes recuerdo que el Examen Parcial 2 está programado para el día 05 de Mayo de 2025 a las 6:00 PM.\r\n\r\nTemas a evaluar: Unidades 3 y 4.\r\nModalidad: Teórico-práctico.\r\n\r\nAsegúrense de repasar el material y llegar a tiempo.\r\n\r\nSaludos,\r\nProf. Andrés Díaz', NULL, '', '2025-05-13 04:10:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas_estudiantes`
--

CREATE TABLE `matriculas_estudiantes` (
  `id_matricula` int(11) NOT NULL,
  `id_estudiante_fk` int(11) NOT NULL COMMENT 'Referencia al estudiante (USUARIOS con rol Estudiante)',
  `id_grupo_fk` int(11) NOT NULL COMMENT 'Referencia al grupo específico del curso',
  `fecha_matricula` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado_matricula` enum('Activa','Retirada','Completada','Cancelada') DEFAULT 'Activa',
  `nota_final_numerica` decimal(5,2) DEFAULT NULL COMMENT 'Nota final numérica del curso para este estudiante',
  `nota_final_alfanumerica` varchar(20) DEFAULT NULL COMMENT 'Ej: Aprobado, Reprobado, Sobresaliente',
  `observaciones_finales` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Matrículas de estudiantes en grupos de cursos';

--
-- Volcado de datos para la tabla `matriculas_estudiantes`
--

INSERT INTO `matriculas_estudiantes` (`id_matricula`, `id_estudiante_fk`, `id_grupo_fk`, `fecha_matricula`, `estado_matricula`, `nota_final_numerica`, `nota_final_alfanumerica`, `observaciones_finales`) VALUES
(1, 102, 301, '2025-05-12 04:56:20', 'Activa', 4.20, NULL, NULL),
(2, 103, 301, '2025-05-12 05:08:54', 'Activa', 3.60, 'TRES PUNTO SEIS', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_comunicacion`
--

CREATE TABLE `mensajes_comunicacion` (
  `id_mensaje` int(11) NOT NULL,
  `id_remitente_fk` int(11) NOT NULL COMMENT 'Usuario que envía el mensaje',
  `asunto` varchar(255) NOT NULL,
  `cuerpo_mensaje` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_destinatario_usuario_fk` int(11) DEFAULT NULL COMMENT 'Destinatario individual (si aplica)',
  `id_destinatario_grupo_fk` int(11) DEFAULT NULL COMMENT 'Grupo destinatario (si aplica)'
) ;

--
-- Volcado de datos para la tabla `mensajes_comunicacion`
--

INSERT INTO `mensajes_comunicacion` (`id_mensaje`, `id_remitente_fk`, `asunto`, `cuerpo_mensaje`, `fecha_envio`, `id_destinatario_usuario_fk`, `id_destinatario_grupo_fk`) VALUES
(1, 101, 'Prueba de envío', 'Esta es una prueba de envío', '2025-05-13 05:07:26', NULL, 301);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_estados_destinatario`
--

CREATE TABLE `mensajes_estados_destinatario` (
  `id_mensaje_estado` int(11) NOT NULL,
  `id_mensaje_fk` int(11) NOT NULL,
  `id_destinatario_usuario_fk` int(11) NOT NULL COMMENT 'Usuario destinatario específico',
  `estado_lectura` enum('No Leído','Leído') DEFAULT 'No Leído',
  `fecha_lectura` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estado de lectura de mensajes para cada destinatario';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_estudiante_actividad`
--

CREATE TABLE `notas_estudiante_actividad` (
  `id_nota_actividad` int(11) NOT NULL,
  `id_matricula_fk` int(11) NOT NULL COMMENT 'Referencia a la matrícula del estudiante en el grupo',
  `id_actividad_fk` int(11) NOT NULL COMMENT 'Referencia a la actividad evaluable',
  `calificacion_obtenida` decimal(5,2) DEFAULT NULL COMMENT 'Nota obtenida por el estudiante',
  `fecha_calificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `observaciones_profesor` text DEFAULT NULL COMMENT 'Comentarios del profesor sobre esta calificación',
  `archivo_entrega_ruta` varchar(255) DEFAULT NULL COMMENT 'Ruta al archivo entregado por el estudiante (si aplica)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notas de los estudiantes para cada actividad evaluable';

--
-- Volcado de datos para la tabla `notas_estudiante_actividad`
--

INSERT INTO `notas_estudiante_actividad` (`id_nota_actividad`, `id_matricula_fk`, `id_actividad_fk`, `calificacion_obtenida`, `fecha_calificacion`, `observaciones_profesor`, `archivo_entrega_ruta`) VALUES
(1, 1, 501, 4.50, '2025-05-12 20:51:15', 'Buen diseño, faltaron detalles en la documentación.', 'entregas/ana_martinez_tarea1_api_diseno.pdf'),
(2, 1, 502, 3.80, '2025-05-13 17:03:33', 'Necesita repasar el tema de autenticación.', NULL),
(3, 2, 501, 2.20, '2025-05-12 20:51:15', 'Un trabajo que deja mucho que desear y mal ejecutado. Debes esforzarte más en la próxima.', NULL),
(4, 2, 503, 4.50, '2025-05-12 20:39:32', 'Muy buen trabajo, felicitaciones', NULL),
(5, 1, 503, 3.90, '2025-05-12 20:39:32', 'Deberás esforzarte más para la próxima.', NULL),
(6, 2, 505, 4.30, '2025-05-13 03:12:49', 'Buen trabajo.', NULL),
(7, 1, 505, 4.50, '2025-05-14 16:08:15', 'Buen trabajo.', NULL),
(8, 2, 502, 3.20, '2025-05-13 17:03:33', 'Necesita estudiar mas a fondo el tema de autenticación.', NULL),
(9, 2, 508, 4.50, '2025-05-14 23:51:23', 'Felicitaciones', NULL),
(10, 1, 508, 4.80, '2025-05-14 23:51:23', 'Felicitaciones', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL COMMENT 'Nombre del rol (Estudiante, Profesor, Administrador)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de roles de usuario';

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(3, 'Administrador'),
(1, 'Estudiante'),
(2, 'Profesor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `numero_identificacion` varchar(20) NOT NULL COMMENT 'Documento único de identidad',
  `id_rol_fk` int(11) NOT NULL COMMENT 'Llave foránea que referencia a la tabla ROLES',
  `programa` enum('Ingeniería de Sistemas','Ingeniería Mecatrónica','Administración de Empresas','Contaduría Pública','Diseño Digital Publicitario','Marketing y Negocios Digitales') NOT NULL,
  `correo_electronico` varchar(255) NOT NULL COMMENT 'Correo electrónico único para login',
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `contrasena` varchar(255) NOT NULL COMMENT 'IMPORTANTE: Almacenar siempre la contraseña HASHEADA, nunca en texto plano',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de creación del registro',
  `estado` enum('activo','inactivo') DEFAULT 'activo' COMMENT 'Estado de la cuenta del usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de usuarios del sistema';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombres`, `apellidos`, `numero_identificacion`, `id_rol_fk`, `programa`, `correo_electronico`, `direccion`, `telefono`, `contrasena`, `fecha_registro`, `estado`) VALUES
(101, 'Andrés', 'Díaz', 'PROF001', 2, 'Ingeniería de Sistemas', 'profesor.andres@email.com', 'Starway To Heaven', '3001234567', '$2y$10$iWnsKKDOekW7NBm9oWXfiuGNsigrfSHczrA9DLE4FwrcZqQEY8RDq', '2025-05-12 04:51:11', 'activo'),
(102, 'Ana', 'Martínez', 'EST001', 1, 'Ingeniería de Sistemas', 'estudiante.ana@email.com', 'Carrera 7 8 - 90', '3109876543', '$2y$10$2zBTLDdPLuj.er0OUDtdbO9EkbryJ/XYTJ.SMB7pIXCT2S6K540uC', '2025-05-12 04:53:00', 'activo'),
(103, 'Luis', 'Gomez', 'EST002', 1, 'Ingeniería de Sistemas', 'estudiante.luis@email.com', 'Carrera 7 8 - 90', '3112223344', '$2y$10$NVL74SX.XBvsdmaz3h8xG.IabIR8EsItuPaRjNJ6N4rzfKPOB/AAm', '2025-05-12 05:07:37', 'activo'),
(104, 'Andrés', 'Abello', 'EST003', 1, 'Ingeniería de Sistemas', 'estudiante.andres@email.com', 'Calle de la luna', '3005669988', '$2y$10$BhzfG9qIcrxr7pt1EiK9putrU2mJcAeE2a16pu9xvaD/BnHr0Sp5C', '2025-05-14 23:45:36', 'activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades_evaluables`
--
ALTER TABLE `actividades_evaluables`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_grupo_fk` (`id_grupo_fk`);

--
-- Indices de la tabla `asistencia_clase`
--
ALTER TABLE `asistencia_clase`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD UNIQUE KEY `uq_matricula_fecha` (`id_matricula_fk`,`fecha_clase`) COMMENT 'Un registro de asistencia por estudiante por día de clase';

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD UNIQUE KEY `codigo_curso` (`codigo_curso`);

--
-- Indices de la tabla `grupos_curso`
--
ALTER TABLE `grupos_curso`
  ADD PRIMARY KEY (`id_grupo`),
  ADD KEY `id_curso_fk` (`id_curso_fk`),
  ADD KEY `id_profesor_fk` (`id_profesor_fk`);

--
-- Indices de la tabla `informes_academicos`
--
ALTER TABLE `informes_academicos`
  ADD PRIMARY KEY (`id_informe`),
  ADD KEY `id_grupo_fk` (`id_grupo_fk`),
  ADD KEY `id_profesor_autor_fk` (`id_profesor_autor_fk`),
  ADD KEY `id_estudiante_destinatario_fk` (`id_estudiante_destinatario_fk`);

--
-- Indices de la tabla `matriculas_estudiantes`
--
ALTER TABLE `matriculas_estudiantes`
  ADD PRIMARY KEY (`id_matricula`),
  ADD UNIQUE KEY `uq_estudiante_grupo` (`id_estudiante_fk`,`id_grupo_fk`) COMMENT 'Un estudiante solo puede matricularse una vez en el mismo grupo',
  ADD KEY `id_grupo_fk` (`id_grupo_fk`);

--
-- Indices de la tabla `mensajes_comunicacion`
--
ALTER TABLE `mensajes_comunicacion`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_remitente_fk` (`id_remitente_fk`),
  ADD KEY `id_destinatario_usuario_fk` (`id_destinatario_usuario_fk`),
  ADD KEY `id_destinatario_grupo_fk` (`id_destinatario_grupo_fk`);

--
-- Indices de la tabla `mensajes_estados_destinatario`
--
ALTER TABLE `mensajes_estados_destinatario`
  ADD PRIMARY KEY (`id_mensaje_estado`),
  ADD UNIQUE KEY `uq_mensaje_destinatario` (`id_mensaje_fk`,`id_destinatario_usuario_fk`),
  ADD KEY `id_destinatario_usuario_fk` (`id_destinatario_usuario_fk`);

--
-- Indices de la tabla `notas_estudiante_actividad`
--
ALTER TABLE `notas_estudiante_actividad`
  ADD PRIMARY KEY (`id_nota_actividad`),
  ADD UNIQUE KEY `uq_matricula_actividad` (`id_matricula_fk`,`id_actividad_fk`) COMMENT 'Una sola nota por estudiante por actividad',
  ADD KEY `id_actividad_fk` (`id_actividad_fk`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `numero_identificacion` (`numero_identificacion`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`),
  ADD KEY `id_rol_fk` (`id_rol_fk`),
  ADD KEY `idx_correo` (`correo_electronico`),
  ADD KEY `idx_identificacion` (`numero_identificacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_evaluables`
--
ALTER TABLE `actividades_evaluables`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=509;

--
-- AUTO_INCREMENT de la tabla `asistencia_clase`
--
ALTER TABLE `asistencia_clase`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT de la tabla `grupos_curso`
--
ALTER TABLE `grupos_curso`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=302;

--
-- AUTO_INCREMENT de la tabla `informes_academicos`
--
ALTER TABLE `informes_academicos`
  MODIFY `id_informe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `matriculas_estudiantes`
--
ALTER TABLE `matriculas_estudiantes`
  MODIFY `id_matricula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `mensajes_comunicacion`
--
ALTER TABLE `mensajes_comunicacion`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_estados_destinatario`
--
ALTER TABLE `mensajes_estados_destinatario`
  MODIFY `id_mensaje_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notas_estudiante_actividad`
--
ALTER TABLE `notas_estudiante_actividad`
  MODIFY `id_nota_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades_evaluables`
--
ALTER TABLE `actividades_evaluables`
  ADD CONSTRAINT `actividades_evaluables_ibfk_1` FOREIGN KEY (`id_grupo_fk`) REFERENCES `grupos_curso` (`id_grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencia_clase`
--
ALTER TABLE `asistencia_clase`
  ADD CONSTRAINT `asistencia_clase_ibfk_1` FOREIGN KEY (`id_matricula_fk`) REFERENCES `matriculas_estudiantes` (`id_matricula`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupos_curso`
--
ALTER TABLE `grupos_curso`
  ADD CONSTRAINT `grupos_curso_ibfk_1` FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  ADD CONSTRAINT `grupos_curso_ibfk_2` FOREIGN KEY (`id_profesor_fk`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `informes_academicos`
--
ALTER TABLE `informes_academicos`
  ADD CONSTRAINT `informes_academicos_ibfk_1` FOREIGN KEY (`id_grupo_fk`) REFERENCES `grupos_curso` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `informes_academicos_ibfk_2` FOREIGN KEY (`id_profesor_autor_fk`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `informes_academicos_ibfk_3` FOREIGN KEY (`id_estudiante_destinatario_fk`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `matriculas_estudiantes`
--
ALTER TABLE `matriculas_estudiantes`
  ADD CONSTRAINT `matriculas_estudiantes_ibfk_1` FOREIGN KEY (`id_estudiante_fk`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_estudiantes_ibfk_2` FOREIGN KEY (`id_grupo_fk`) REFERENCES `grupos_curso` (`id_grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_comunicacion`
--
ALTER TABLE `mensajes_comunicacion`
  ADD CONSTRAINT `mensajes_comunicacion_ibfk_1` FOREIGN KEY (`id_remitente_fk`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_comunicacion_ibfk_2` FOREIGN KEY (`id_destinatario_usuario_fk`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_comunicacion_ibfk_3` FOREIGN KEY (`id_destinatario_grupo_fk`) REFERENCES `grupos_curso` (`id_grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_estados_destinatario`
--
ALTER TABLE `mensajes_estados_destinatario`
  ADD CONSTRAINT `mensajes_estados_destinatario_ibfk_1` FOREIGN KEY (`id_mensaje_fk`) REFERENCES `mensajes_comunicacion` (`id_mensaje`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_estados_destinatario_ibfk_2` FOREIGN KEY (`id_destinatario_usuario_fk`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notas_estudiante_actividad`
--
ALTER TABLE `notas_estudiante_actividad`
  ADD CONSTRAINT `notas_estudiante_actividad_ibfk_1` FOREIGN KEY (`id_matricula_fk`) REFERENCES `matriculas_estudiantes` (`id_matricula`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_estudiante_actividad_ibfk_2` FOREIGN KEY (`id_actividad_fk`) REFERENCES `actividades_evaluables` (`id_actividad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol_fk`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
