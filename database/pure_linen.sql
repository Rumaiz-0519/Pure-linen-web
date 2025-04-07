-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 01, 2025 at 07:30 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pure_linen`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `admin_name`, `email`, `password`, `created_at`, `firstName`, `lastName`) VALUES
(1, 'admin', 'admin@example.com', 'apple', '2025-02-27 17:22:08', NULL, NULL),
(2, 'New Admin', 'newadmin@example.com', '$2y$10$bqDylTx9j5kxvKX3U0rRQuoL30I5SvpyXrG3v2mYvIEPQFR6OlEYm', '2025-02-28 16:23:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bulk_messages`
--

CREATE TABLE `bulk_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `size` enum('meter','cm') DEFAULT 'meter',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_bulk` tinyint(1) NOT NULL DEFAULT 0,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `session_id`, `product_id`, `quantity`, `size`, `created_at`, `is_bulk`, `payment_status`) VALUES
(1, '9a37obj92g9f2mi52ri20mb29m', 'pure-white', 1, 'meter', '2025-02-22 16:40:53', 0, 'pending'),
(2, '9a37obj92g9f2mi52ri20mb29m', 'deep-blue', 5, 'meter', '2025-02-22 16:40:57', 0, 'pending'),
(3, 't4fn8h6tudt0vhfg1qiuc809jq', 'pure-white', 1, 'meter', '2025-02-23 10:07:07', 0, 'pending'),
(4, 'imavddbnuhv880s1m533sbi1vs', 'pure-white', 1, 'meter', '2025-02-27 08:30:28', 0, 'pending'),
(5, 'tuuba7dvoj4sfodnm97q6ceonh', 'deep-blue', 1, 'meter', '2025-02-27 10:06:08', 0, 'pending'),
(6, 'tuuba7dvoj4sfodnm97q6ceonh', 'pure-white', 11, 'meter', '2025-02-27 11:49:26', 0, 'pending'),
(7, 'tuuba7dvoj4sfodnm97q6ceonh', 'pure-white', 7, 'cm', '2025-02-27 11:54:59', 0, 'pending'),
(8, 'tuuba7dvoj4sfodnm97q6ceonh', 'viscose', 1, 'meter', '2025-02-27 11:58:53', 0, 'pending'),
(9, 'tuuba7dvoj4sfodnm97q6ceonh', 'deep-blue', 4, 'cm', '2025-02-27 12:02:18', 0, 'pending'),
(12, 'u5bdct0u10sjop0ck47kl4n08j', 'pure-white', 5, 'meter', '2025-02-27 15:15:11', 0, 'pending'),
(14, 'n43vcrrl5rffq719ehp5r254s0', 'pure-white', 6, 'meter', '2025-02-28 12:24:20', 0, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `size` varchar(10) NOT NULL DEFAULT 'meter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `half_price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `properties` varchar(255) DEFAULT NULL,
  `composition` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `color`, `price`, `half_price`, `image_url`, `description`, `type`, `properties`, `composition`) VALUES
('beige-tan', 'Plain Color Pure Linen 100%', 'beige tan', 1200.00, 650.00, 'img/pure linen/beige-tan.webp', 'Rich beige tan pure linen fabric.', 'Pure Linen', 'Premium Beige', '100% Pure Linen'),
('Dark Blue', 'Plain Yarn Pure Linen', 'Dark Blue', 1100.00, 700.00, 'img/dyed linen/1.jpg', 'Exquisite 60 Lea pure linen fabric.', 'Pure Linen', '60 Lea count', '100% Pure Linen'),
('deep-blue', 'Plain Color Pure Linen 100%', 'Deep Blue', 1200.00, 650.00, 'img/pure linen/deep blue.webp', 'Rich deep blue pure linen fabric.', 'Pure Linen', 'Premium Blue', '100% Pure Linen'),
('golden tones', 'Warm Golden Tones Printed Linen', 'Golden Brown', 1800.00, 1100.00, 'img/printed linen/1.jpg', 'Beautiful printed linen fabric with floral patterns.', 'Printed Linen', 'Lightweight', 'Pure Linen'),
('pure-white', 'Plain Color Pure Linen 100%', 'Pure White', 1200.00, 650.00, 'img/pure linen/pure white.webp', 'Luxurious 100% pure linen in a pristine white shade.', 'Pure Linen', 'Premium White', '100% Pure Linen'),
('Sage Green', 'Cotton Linen', 'Sage Green', 750.00, 400.00, 'img/cotton linen/3.webp', 'A versatile cotton-linen shirting fabric.', 'Cotton Linen', 'Lightweight', 'Cotton Linen Mix'),
('viscose', 'Viscose Linen Blend Suiting', 'Dull Sky Blue', 450.00, 250.00, 'img/blend linen/1-viscose.jpg', 'A sophisticated blend of viscose and linen.', 'Cotton Linen', 'Lightweight', 'Viscose Linen Blend');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_type` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `email`, `password`, `created_at`, `phone`, `address`, `user_type`) VALUES
(1, 'rumaiz', 'rr', 'r@r', '$2y$10$P7qxW3NIPd9LjEdKOYyJ..AV22uIVk2ULFheQhLMHe9VST87fBYRa', '2025-02-23 13:40:22', NULL, NULL, 'user'),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$yourHashedPasswordHere', '2025-02-28 14:49:12', NULL, NULL, 'admin'),
(3, 'Admin', 'User', 'admin@purelinen.com', '$2y$10$mNuRVTRvtA4fSYQORt7kf.oQR5oCYn9Gbo.hA2WpWpVmOVJqTcmTi', '2025-02-28 14:54:23', NULL, NULL, 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bulk_messages`
--
ALTER TABLE `bulk_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bulk_messages`
--
ALTER TABLE `bulk_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
