-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2025 a las 21:54:16
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `interbank`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id_alerta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tarjeta` int(11) NOT NULL,
  `id_transaccion` int(11) DEFAULT NULL,
  `tipo_alerta` varchar(50) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `nivel_riesgo` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('nueva','vista') NOT NULL DEFAULT 'nueva'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alertas`
--

INSERT INTO `alertas` (`id_alerta`, `id_usuario`, `id_tarjeta`, `id_transaccion`, `tipo_alerta`, `titulo`, `mensaje`, `nivel_riesgo`, `fecha_hora`, `estado`) VALUES
(1, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-14 23:51:25', 'vista'),
(3, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-15 00:11:02', 'vista'),
(5, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-15 00:39:29', 'vista'),
(6, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-15 00:42:22', 'vista'),
(7, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-15 00:52:10', 'vista'),
(9, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-15 01:57:56', 'vista'),
(11, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:42:50', 'vista'),
(12, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-15 02:43:31', 'vista'),
(13, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:43:31', 'vista'),
(14, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-15 02:43:31', 'vista'),
(15, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-15 02:50:55', 'vista'),
(16, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:50:55', 'vista'),
(17, 1, 1, NULL, 'limite_cercano', 'Consumo cercano a tu límite semanal', 'Tu gasto semanal con SmartShield Interbank se acerca al límite configurado. \r\nRevisa tus últimas compras para mantener controlado tu presupuesto.', 60, '2025-11-15 02:52:23', 'vista'),
(18, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:52:23', 'vista'),
(19, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-15 02:52:23', 'vista'),
(20, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:53:16', 'vista'),
(21, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 02:54:08', 'vista'),
(22, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 03:23:36', 'vista'),
(23, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-15 03:24:08', 'vista'),
(24, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 01:46:02', 'vista'),
(25, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 01:46:02', 'vista'),
(26, 1, 1, NULL, 'limite_cercano', 'Consumo cercano a tu límite semanal', 'Tu gasto semanal con SmartShield Interbank se acerca al límite configurado. \r\nRevisa tus últimas compras para mantener controlado tu presupuesto.', 60, '2025-11-16 01:46:49', 'vista'),
(27, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 01:46:49', 'vista'),
(28, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 01:46:49', 'vista'),
(29, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 01:55:43', 'vista'),
(30, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 21:28:26', 'vista'),
(31, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 21:29:33', 'vista'),
(32, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 21:30:40', 'vista'),
(33, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 21:34:31', 'vista'),
(34, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 21:39:02', 'vista'),
(35, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 21:54:25', 'vista'),
(36, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:06:49', 'vista'),
(37, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:11:37', 'vista'),
(38, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:18:17', 'vista'),
(39, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:19:01', 'vista'),
(40, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:19:01', 'vista'),
(41, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:19:15', 'vista'),
(42, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:19:15', 'vista'),
(43, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:19:58', 'vista'),
(44, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:21:07', 'vista'),
(45, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:21:07', 'vista'),
(46, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:22:52', 'vista'),
(47, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:22:52', 'vista'),
(48, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:23:28', 'vista'),
(49, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:24:16', 'vista'),
(50, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:24:50', 'vista'),
(51, 1, 1, NULL, 'limite_cercano', 'Consumo cercano a tu límite semanal', 'Tu gasto semanal con SmartShield Interbank se acerca al límite configurado. \r\nRevisa tus últimas compras para mantener controlado tu presupuesto.', 60, '2025-11-16 22:25:28', 'vista'),
(52, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:25:28', 'vista'),
(53, 1, 1, NULL, 'monto_inusual', 'Monto inusualmente alto detectado', 'Esta transferencia tiene un monto superior a tu consumo promedio. \r\nSi no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.', 75, '2025-11-16 22:25:28', 'vista'),
(54, 1, 1, NULL, 'limite_superado', 'Has superado tu límite semanal', 'Tu consumo semanal ha superado el tope configurado. \r\nTe recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.', 80, '2025-11-16 22:25:59', 'vista'),
(55, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-16 22:25:59', 'vista'),
(56, 1, 1, NULL, 'fuera_horario', 'Operación fuera del horario configurado', 'Se detectó una transferencia realizada fuera del horario que tienes configurado \r\npara tus compras habituales. Verifica si reconoces esta operación.', 70, '2025-11-17 15:53:14', 'vista');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_seguridad_tarjeta`
--

CREATE TABLE `config_seguridad_tarjeta` (
  `id_config` int(11) NOT NULL,
  `id_tarjeta` int(11) NOT NULL,
  `limite_diario` decimal(10,2) DEFAULT 0.00,
  `limite_mensual` decimal(10,2) DEFAULT 0.00,
  `horario_inicio` time DEFAULT '00:00:00',
  `horario_fin` time DEFAULT '23:59:59',
  `modo_viaje` tinyint(1) DEFAULT 0,
  `pais_viaje` varchar(80) DEFAULT NULL,
  `fecha_inicio_viaje` date DEFAULT NULL,
  `fecha_fin_viaje` date DEFAULT NULL,
  `notificar_email` tinyint(1) DEFAULT 1,
  `notificar_sms` tinyint(1) DEFAULT 0,
  `ultima_actualizacion` datetime DEFAULT current_timestamp(),
  `limite_semanal` decimal(10,2) DEFAULT 0.00,
  `gasto_semanal_actual` decimal(10,2) DEFAULT 0.00,
  `fecha_ultimo_reset_semanal` datetime DEFAULT NULL,
  `gasto_mensual_actual` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `config_seguridad_tarjeta`
--

INSERT INTO `config_seguridad_tarjeta` (`id_config`, `id_tarjeta`, `limite_diario`, `limite_mensual`, `horario_inicio`, `horario_fin`, `modo_viaje`, `pais_viaje`, `fecha_inicio_viaje`, `fecha_fin_viaje`, `notificar_email`, `notificar_sms`, `ultima_actualizacion`, `limite_semanal`, `gasto_semanal_actual`, `fecha_ultimo_reset_semanal`, `gasto_mensual_actual`) VALUES
(1, 1, 300.00, 1200.00, '08:00:00', '10:59:00', 0, NULL, NULL, NULL, 1, 0, '2025-11-16 22:37:59', 1000.00, 1.00, '2025-11-17 21:53:14', 450.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarjetas`
--

CREATE TABLE `tarjetas` (
  `id_tarjeta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `numero_enmascarado` varchar(25) NOT NULL,
  `tipo` enum('credito','debito') NOT NULL,
  `marca` varchar(20) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('activa','bloqueada','suspendida') DEFAULT 'activa',
  `limite_credito` decimal(10,2) DEFAULT 0.00,
  `saldo_disponible` decimal(10,2) DEFAULT 0.00,
  `uso_internacional` tinyint(1) DEFAULT 0,
  `modo_inteligente` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarjetas`
--

INSERT INTO `tarjetas` (`id_tarjeta`, `id_usuario`, `numero_enmascarado`, `tipo`, `marca`, `fecha_vencimiento`, `estado`, `limite_credito`, `saldo_disponible`, `uso_internacional`, `modo_inteligente`, `fecha_creacion`) VALUES
(1, 1, '**** **** **** 3456', 'credito', 'VISA', '2028-12-31', 'activa', 1200.00, 16935.00, 1, 1, '2025-11-13 20:27:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id_transaccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tarjeta` int(11) DEFAULT NULL,
  `tipo` enum('transferencia','compra','pago_servicio') DEFAULT 'transferencia',
  `destino` varchar(150) NOT NULL,
  `alias_destino` varchar(100) DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `moneda` enum('PEN','USD') DEFAULT 'PEN',
  `monto` decimal(10,2) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('aplicada','rechazada','pendiente') DEFAULT 'aplicada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transacciones`
--

INSERT INTO `transacciones` (`id_transaccion`, `id_usuario`, `id_tarjeta`, `tipo`, `destino`, `alias_destino`, `numero_cuenta`, `moneda`, `monto`, `fecha_hora`, `descripcion`, `estado`) VALUES
(31, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-16 21:29:03', 'Prueba alerta', 'aplicada'),
(33, 1, 1, 'transferencia', '1', 'aaa', '1234567891234', 'PEN', 1.00, '2025-11-16 21:30:40', 'Prueba alerta', 'aplicada'),
(34, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-16 21:34:31', 'Prueba alerta5', 'aplicada'),
(35, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 2.00, '2025-11-16 21:39:02', 'Prueba alerta1', 'aplicada'),
(36, 1, 1, 'transferencia', '1', 'Amazon', '1234567891234', 'PEN', 1.00, '2025-11-16 21:54:25', 'Prueba alerta1', 'aplicada'),
(37, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-16 22:06:49', 'Prueba alerta1', 'aplicada'),
(38, 1, 1, 'transferencia', '1', 'Amazon', '1234567891234', 'PEN', 1.00, '2025-11-16 22:11:37', 'Prueba alerta1', 'aplicada'),
(39, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-16 22:18:17', 'Pago del netflixc', 'aplicada'),
(42, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-16 22:19:58', 'Prueba alerta1', 'aplicada'),
(45, 1, 1, 'transferencia', '1', 'Amazon', '1234567891234', 'PEN', 1.00, '2025-11-16 22:23:28', 'Prueba alerta5', 'aplicada'),
(50, 1, 1, 'transferencia', '1', 'Netflix', '1234567891234', 'PEN', 1.00, '2025-11-17 15:53:14', 'Prueba', 'aplicada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `dni` varchar(15) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('cliente','admin') DEFAULT 'cliente',
  `estado` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `dni`, `correo`, `nombre_completo`, `password_hash`, `rol`, `estado`, `fecha_registro`) VALUES
(1, '12345678', 'prueba@correo.com', 'Usuario Prueba', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'cliente', 1, '2025-11-13 18:22:43');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id_alerta`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_tarjeta` (`id_tarjeta`);

--
-- Indices de la tabla `config_seguridad_tarjeta`
--
ALTER TABLE `config_seguridad_tarjeta`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `id_tarjeta` (`id_tarjeta`);

--
-- Indices de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD PRIMARY KEY (`id_tarjeta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id_transaccion`),
  ADD KEY `fk_trans_usuario` (`id_usuario`),
  ADD KEY `fk_trans_tarjeta` (`id_tarjeta`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id_alerta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `config_seguridad_tarjeta`
--
ALTER TABLE `config_seguridad_tarjeta`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  MODIFY `id_tarjeta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id_transaccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id_tarjeta`);

--
-- Filtros para la tabla `config_seguridad_tarjeta`
--
ALTER TABLE `config_seguridad_tarjeta`
  ADD CONSTRAINT `config_seguridad_tarjeta_ibfk_1` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id_tarjeta`);

--
-- Filtros para la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD CONSTRAINT `tarjetas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `fk_trans_tarjeta` FOREIGN KEY (`id_tarjeta`) REFERENCES `tarjetas` (`id_tarjeta`),
  ADD CONSTRAINT `fk_trans_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
