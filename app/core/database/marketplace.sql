-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-02-2025 a las 00:18:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `marketplace`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Frutas y Verduras', '2025-02-04 22:49:31'),
(2, 'Carnes y Derivados', '2025-02-04 22:49:31'),
(3, 'Granos y Cereales', '2025-02-04 22:49:31'),
(4, 'Productos Lacteos', '2025-02-04 22:49:31'),
(5, 'Miel y Endulzantes Naturales', '2025-02-04 22:49:31'),
(6, 'Especias y Condimentos', '2025-02-04 22:49:31'),
(7, 'Plantas y Hierbas Medicinales', '2025-02-04 22:49:31'),
(8, 'Flores y Plantas', '2025-02-04 22:49:31'),
(9, 'Abonos y Fertilizantes Organicos', '2025-02-04 22:49:31'),
(10, 'Herramientas y Equipos para el Campo', '2025-02-04 22:49:31'),
(11, 'Animales y Productos Pecuarios', '2025-02-04 22:49:31'),
(12, 'Artesanias y Productos Hechos a Mano', '2025-02-04 22:49:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `title`, `description`, `category`, `price`, `image`, `user_id`, `created_at`, `category_id`, `updated_at`) VALUES
(1, 'Miel de abejas', 'Miel 100% natural', NULL, 20000.00, 'foto 1.jpeg', 3, '2025-02-03 02:46:23', 5, '2025-02-19 23:05:04'),
(2, 'Platanos', 'Platanos atranca puertas', NULL, 2700.00, 'foto 5.jpeg', 3, '2025-02-03 02:57:59', 1, '2025-02-19 23:05:18'),
(3, 'Aguacate', 'aguacate de la mejor calidad', NULL, 7500.00, '67a035baf34ca.jpg', 3, '2025-02-03 03:19:23', 1, '2025-02-19 23:05:33'),
(4, 'Arboles de aguacate', 'Arboles de aguacate injertados y naturales ', NULL, 19999.99, '67a036c4ea8c2.jpeg', 3, '2025-02-03 03:23:48', 9, '2025-02-19 23:05:54'),
(5, 'Aji guagualito', 'Aji picante', NULL, 10000.00, '67a03a81bbd9c.jpg', 3, '2025-02-03 03:39:45', 1, '2025-02-19 23:06:09'),
(6, 'Queso', 'Queso costeño precio por libra de producto', NULL, 8000.00, 'Blog-Alqueria-Productos-lacteos.jpg', 3, '2025-02-03 03:40:26', 4, '2025-02-19 23:06:24'),
(7, 'DecoraciÃ³n', '100% natural', NULL, 40000.00, '67a04a998a75a.jpg', 5, '2025-02-03 04:48:25', 12, '2025-02-20 00:03:20'),
(8, 'Aretes por unidad', '100% artesanales', NULL, 5000.00, '67a13abb8cf5b.jpeg', 6, '2025-02-03 21:52:59', 12, '2025-02-20 18:23:26'),
(10, 'Guajiro', 'Frijol guajiro por kilo', NULL, 4000.00, '67a36595ad912.jpeg', 6, '2025-02-05 13:20:21', 3, '2025-02-19 20:08:04'),
(11, 'Cuarentano', 'Frijol cuarentano por kilo', NULL, 4000.00, '67a365bc1e2e8.jpeg', 6, '2025-02-05 13:21:00', 3, '2025-02-19 01:04:45'),
(12, 'Pipilongo', 'Afrodisiaca', NULL, 14999.98, '67a39750d3ea5.jpg', 6, '2025-02-05 16:52:32', 7, '2025-02-19 01:06:16'),
(14, 'Artesanias', 'Artesanias', NULL, 2000000.00, '67b525be2fb07.jpeg', 6, '2025-02-19 00:28:46', 9, '2025-02-19 01:05:32'),
(15, 'Se hacen uÃ±as ', 'Se realizan todo tipo de uÃ±as, semi permanentes,acrÃ­licas en poli gel.', NULL, 20000.00, '67b67301b0105.jpg', 7, '2025-02-20 00:10:41', 12, '2025-02-20 00:10:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correoelectronico` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) NOT NULL DEFAULT 'default-avatar.jpg',
  `business_name` varchar(100) NOT NULL DEFAULT '',
  `business_description` text DEFAULT NULL,
  `business_category` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `profile_header` varchar(255) DEFAULT 'default-header.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `telefono`, `correoelectronico`, `created_at`, `profile_pic`, `business_name`, `business_description`, `business_category`, `website`, `profile_header`) VALUES
(3, 'jose', '$2y$10$kzFKP/Nn0SR6NLn8h1xjWOWQFJwMrMEGHr3xMbRd2z2LhHrY31XLG', '3216659724', 'guitarrista1127@gmail.com', '2025-02-03 02:40:10', 'default-avatar.jpg', '', NULL, NULL, NULL, 'default-header.jpg'),
(5, 'KarenCastilla', '$2y$10$EXdpAuF7j499PdXIYuScke0EdhbuB6NRkhsEmKfMHCvnYNfaltNHu', '3207471463', 'karencastilla28@gmail.com', '2025-02-03 04:45:48', '67b670e560f3e.jpg', '', NULL, NULL, NULL, 'default-header.jpg'),
(6, 'JoseFelix', '$2y$10$MlYlaPsajWyImPJ38KOR0ufkOp01trLrPeZaj/2VjsdNM/PFnHhMK', '3216659724', 'MAYLOFOTOS05@GMAIL.COM', '2025-02-03 21:41:26', '67b77bc480bb6.png', 'AGROROMA', 'En AGROROMA, la melicultura se ha convertido en una actividad clave, basada en prÃ¡cticas sostenibles y respetuosas con el medio ambiente. Se adoptÃ³ un acuerdo de no uso de agroquÃ­micos, reduciendo su aplicaciÃ³n a cero y creando sistemas silvopastoriles donde las abejas y el ganado pueden convivir sin afectar la biodiversidad. Estos sistemas permiten la producciÃ³n de miel de manera orgÃ¡nica y segura, obteniendo el Sello Verde por su compromiso con el cuidado ambiental.\r\n\r\nLa organizaciÃ³n ha establecido nodos de ayuda colectiva, donde familias integran sus esfuerzos productivos. AdemÃ¡s, se habilitaron cuatro jagÃ¼eyes con capacidad de 54 mil metros cÃºbicos de agua, lo que ha permitido enfrentar los periodos de sequÃ­a y evitar desplazamientos forzados.', 'Miel y Endulzantes Naturales', 'https://agroroma.org/authentication/login', '67b77a9d94cbd.png'),
(7, 'Katerine Castilla', '$2y$10$5rMuSV83lTfI.ML6a96Y0.wUWGsS7eviDiKDfZGgm1DMRIQI3WAjC', '3104557678', 'kate09riher@gmail.com', '2025-02-20 00:06:30', '67b673d5e297b.jpg', '', NULL, NULL, NULL, 'default-header.jpg');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `fk_category_id` (`category_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
