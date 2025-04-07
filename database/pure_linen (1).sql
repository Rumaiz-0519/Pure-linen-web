-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 04, 2025 at 12:17 PM
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

--
-- Dumping data for table `bulk_messages`
--

INSERT INTO `bulk_messages` (`id`, `name`, `industry`, `company_name`, `email`, `phone`, `country`, `city`, `message`, `created_at`) VALUES
(1, 'mrblack', '', 'rr', 'admin@example.com', 'rr', 'rr', 'rr', 'fabric', '2025-03-01 07:32:36'),
(2, 'ddd', 'Home Textiles', 'dd', 'admin@example.com', 'dd', 'dd', 'dd', 'dd', '2025-03-01 07:56:30'),
(3, 'rr', 'Other', 'rr', 'q@q', '123', 'ee', 'ww', 'suuii', '2025-03-04 07:00:42'),
(5, 'rr', 'Retail', 'mr', 'mrblackmaster0123@gmail.com', '0712345678', 'mm', 'rr', 'linen', '2025-03-04 11:49:07'),
(6, 'Dishan', 'Home Textiles', 'raj company', 'mrblackmaster0123@gmail.com', '123456789', 'Belrose', 'city', 'blah balah blah....', '2025-03-30 08:05:53');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_message_replies`
--

CREATE TABLE `bulk_message_replies` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `reply_subject` varchar(255) NOT NULL,
  `reply_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulk_message_replies`
--

INSERT INTO `bulk_message_replies` (`id`, `message_id`, `reply_subject`, `reply_text`, `sent_at`) VALUES
(1, 5, 'RE: Your inquiry at Pure Linen', 'Dear rr,\r\n\r\nThank you for contacting Pure Linen. Regarding your inquiry:\r\n\r\nhi my name is ronaldo\r\n\r\nBest regards,\r\nPure Linen Team\r\n                                                                            ', '2025-03-04 12:05:33'),
(2, 6, 'RE: Your inquiry at Pure Linen', 'Dear Dishan,\r\n\r\nThank you for contacting Pure Linen. Regarding your inquiry:\r\n\r\nNakuuuu\r\n\r\nBest regards,\r\nPure Linen Team\r\n                                                                            ', '2025-03-30 08:19:22');

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
(14, 'n43vcrrl5rffq719ehp5r254s0', 'pure-white', 6, 'meter', '2025-02-28 12:24:20', 0, 'pending'),
(15, 'e2eq8otbqof7c1c532jmqkoei6', 'deep-blue', 1, 'meter', '2025-03-01 08:00:32', 0, 'pending'),
(16, 'e2eq8otbqof7c1c532jmqkoei6', 'beige-tan', 9, 'cm', '2025-03-01 08:00:41', 0, 'pending'),
(17, 'f3v325dvi2pq0dn0qeioe3nmar', 'beige-tan', 4, 'cm', '2025-03-01 11:05:54', 0, 'pending'),
(26, '8nk6eiuqnadv05rnd1o1b3md0o', 'deep-blue', 1, 'meter', '2025-03-06 08:50:03', 0, 'pending'),
(27, '4eljdf15dcc3m2kpp1dt6nir42', 'Dark Blue', 5, 'meter', '2025-03-06 09:39:58', 0, 'pending'),
(28, 'k1dho89fjvohbgqvui4be9cg2d', 'beige-tan', 1, 'meter', '2025-03-06 15:37:12', 0, 'pending'),
(29, '8on3b24bc522o126ua99eek8ot', 'beige-tan', 1, 'meter', '2025-03-08 07:30:34', 0, 'pending'),
(36, '1pv26mj9tcu8268ia63hmp1hpr', 'Dark Blue', 1, 'meter', '2025-03-09 07:42:25', 0, 'pending'),
(37, 'ej0kqupr39e3ohvbi6fku011er', 'Dark Blue', 1, 'meter', '2025-03-10 08:38:26', 0, 'pending'),
(38, 'iqqpfv9nas7a89k07qe8hp1ood', 'Dark Blue', 3, 'meter', '2025-03-14 11:45:38', 0, 'pending'),
(39, 'iqqpfv9nas7a89k07qe8hp1ood', 'beige-tan', 1, 'meter', '2025-03-14 11:45:52', 0, 'pending'),
(48, 'jr90ca7b96799r2f0irnkmufc5', 'beige-tan', 1, 'meter', '2025-03-16 09:25:55', 0, 'pending'),
(50, 's0affjna78m493j3ltki52152l', 'Dark Blue', 1, 'meter', '2025-03-22 11:52:31', 0, 'pending'),
(51, 'gs1imjfev822al71gl3nifs6q4', 'beige-tan', 1, 'meter', '2025-03-29 08:35:26', 0, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `featured_products`
--

CREATE TABLE `featured_products` (
  `id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_products`
--

INSERT INTO `featured_products` (`id`, `product_id`, `position`, `created_at`) VALUES
(2, 'beige-tan', 1, '2025-03-29 15:56:18'),
(3, 'pure-white', 2, '2025-03-29 15:56:24'),
(4, 'deep-blue', 3, '2025-03-29 15:56:30'),
(6, 'Dark Blue', 5, '2025-03-29 15:56:37');

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
  `order_type` varchar(50) DEFAULT 'regular',
  `subscription_id` int(11) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_postcode` varchar(20) DEFAULT NULL,
  `delivery_country` varchar(100) DEFAULT NULL,
  `delivery_option` varchar(50) DEFAULT 'standard',
  `delivery_cost` decimal(10,2) DEFAULT 250.00,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `payment_method`, `payment_status`, `order_type`, `subscription_id`, `delivery_address`, `delivery_city`, `delivery_postcode`, `delivery_country`, `delivery_option`, `delivery_cost`, `order_date`, `created_at`) VALUES
(1, 1, 2650.00, 'card', 'completed', 'regular', NULL, 'wcw', 'wcw', NULL, 'wdcw', 'standard', 250.00, '2025-03-05 15:53:16', '2025-03-05 15:53:16'),
(2, 1, 1450.00, 'bank', 'pending', 'regular', NULL, 'h', 'h h', NULL, 'h', 'standard', 250.00, '2025-03-06 07:46:58', '2025-03-06 07:46:58'),
(3, 1, 1450.00, 'bank', 'pending', 'regular', NULL, 'hj', 'njn', NULL, 'n', 'standard', 250.00, '2025-03-06 07:53:53', '2025-03-06 07:53:53'),
(4, 1, 19300.00, 'cod', 'cancelled', 'regular', NULL, 'cbda cbwd', 'ad cbjs', NULL, 'NDA CJS', 'express', 600.00, '2025-03-06 08:01:34', '2025-03-06 08:01:34'),
(5, 1, 1800.00, 'bank', 'completed', 'regular', NULL, 'ece', 'ewvce', NULL, 'ewdce', 'express', 600.00, '2025-03-06 08:24:56', '2025-03-06 08:24:56'),
(6, 1, 12000.00, 'card', 'completed', 'subscription', 1, 'sfv', 'vsfdv', NULL, 'sfv', 'standard', 250.00, '2025-03-06 16:01:20', '2025-03-06 16:01:20'),
(7, 1, 4000.00, 'bank', 'completed', 'subscription', 2, 'cwc', 'ewd', NULL, 'cwec', 'standard', 250.00, '2025-03-06 16:03:47', '2025-03-06 16:03:47'),
(8, 1, 1700.00, 'card', 'pending', 'regular', NULL, 'zsxdcfgvh', 'colombo', '01500', 'sri lanka', 'express', 600.00, '2025-03-08 16:12:14', '2025-03-08 16:12:14'),
(9, 1, 13800.00, 'card', 'pending', 'regular', NULL, 'zsxdcfgvh', 'colombo', '01500', 'sri lanka', 'express', 600.00, '2025-03-08 17:12:44', '2025-03-08 17:12:44'),
(10, 1, 1450.00, 'card', 'pending', 'regular', NULL, 'zsxdcfgvh', 'hgf', NULL, 'hgfch', 'standard', 250.00, '2025-03-08 17:14:47', '2025-03-08 17:14:47'),
(11, 1, 1350.00, 'card', 'shipped', 'regular', NULL, 'zsxdcfgvh', 'colombo', '01500', 'sri lanka', 'standard', 250.00, '2025-03-08 17:42:03', '2025-03-08 17:42:03'),
(12, 1, 20000.00, 'card', 'active', 'subscription', 3, '69/378', 'colombo', NULL, 'sri lanka', 'standard', 250.00, '2025-03-15 10:32:42', '2025-03-15 10:32:42'),
(14, 1, 20000.00, 'card', 'active', 'subscription', 5, 'zsxdcfgvh', 'cwqc', NULL, 'cwcd', 'standard', 250.00, '2025-03-15 11:25:24', '2025-03-15 11:25:24'),
(15, 1, 2800.00, 'cod', 'pending', 'regular', NULL, 'zsxdcfgvh', 'colombo', '01500', 'sri lanka', 'Express Delivery', 600.00, '2025-03-15 15:39:38', '2025-03-15 15:39:38'),
(16, 1, 43550.00, 'card', 'pending', 'regular', NULL, 'zsxdcfgvh', 'colombo', '01500', 'sri lanka', 'express', 600.00, '2025-03-15 15:55:35', '2025-03-15 15:55:35');

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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `size`) VALUES
(1, 1, 'beige-tan', 2, 1200.00, 'meter'),
(2, 2, 'beige-tan', 1, 1200.00, 'meter'),
(3, 3, 'beige-tan', 1, 1200.00, 'meter'),
(4, 4, 'Dark Blue', 17, 1100.00, 'meter'),
(5, 5, 'deep-blue', 1, 1200.00, 'meter'),
(6, 8, 'Dark Blue', 1, 1100.00, 'meter'),
(7, 9, 'beige-tan', 9, 1200.00, 'meter'),
(8, 9, 'deep-blue', 1, 1200.00, 'meter'),
(9, 9, 'pure-white', 1, 1200.00, 'meter'),
(10, 10, 'deep-blue', 1, 1200.00, 'meter'),
(11, 11, 'Dark Blue', 1, 1100.00, 'meter'),
(12, 15, 'Dark Blue', 2, 1100.00, 'meter'),
(13, 16, 'beige-tan', 1, 1200.00, 'meter'),
(14, 16, 'Dark Blue', 1, 1100.00, 'meter'),
(15, 16, 'deep-blue', 1, 1200.00, 'meter'),
(16, 16, 'golden tones', 15, 1800.00, 'meter'),
(17, 16, 'pure-white', 1, 1200.00, 'meter'),
(18, 16, 'Sage Green', 27, 400.00, 'cm'),
(19, 16, 'viscose', 1, 450.00, 'meter');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
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
('10', 'Blue linen', 'blue', 19999.00, 999.00, 'img/dyed-linen/10.png', 'vk jbadhfbv hfd', 'dyed linen', 'dhsbvchjsdv', 'v sh vhsd'),
('aaa', 'Blue linen', 'bule', 77.00, 7.00, 'img/dyed-linen/aaa.png', 'g hg', 'dyed linen', 'dcdwc', 'wcece'),
('beige-tan', 'Plain Color Pure Linen 100%', 'beige tan', 1200.00, 650.00, 'img/pure-linen/beige-tan.webp', 'Rich beige tan pure linen fabric.', 'pure linen', 'Premium Beige', '100% Pure Linen'),
('bluee', 'Blue linen', 'Test Color', 9.00, 9.00, 'img/dyed-linen/bluee.png', 'jwvche', 'dyed linen', 'vfesved', 'efvev'),
('blueee', 'Blue linen', '0', 5900.00, 590.00, 'img/dyed-linen/blueee.png', 'fb vhd f', 'dyed linen', 'jhgu', 'v sh vhsd'),
('blueeee', 'Blue linen', '0', 44444.00, 4444.00, 'img/dyed-linen/blueeee.png', 'vrfvtgvtgv', 'dyed linen', 'dc', 'hjvhj'),
('Dark Blue', 'Plain Yarn Pure Linen', 'Dark Blue', 1100.00, 700.00, 'img/dyed linen/1.jpg', 'Exquisite 60 Lea pure linen fabric.', 'Pure Linen', '60 Lea count', '100% Pure Linen'),
('deep-blue', 'Plain Color Pure Linen 100%', 'Deep Blue', 1200.00, 650.00, 'img/pure linen/deep blue.webp', 'Rich deep blue pure linen fabric.', 'Pure Linen', 'Premium Blue', '100% Pure Linen'),
('golden tones', 'Warm Golden Tones Printed Linen', 'Golden Brown', 1800.00, 1100.00, 'img/printed linen/1.jpg', 'Beautiful printed linen fabric with floral patterns.', 'Printed Linen', 'Lightweight', 'Pure Linen'),
('pure-white', 'Plain Color Pure Linen 100%', 'Pure White', 1200.00, 650.00, 'img/pure linen/pure white.webp', 'Luxurious 100% pure linen in a pristine white shade.', 'Pure Linen', 'Premium White', '100% Pure Linen'),
('Sage Green', 'Cotton Linen', 'Sage Green', 750.00, 400.00, 'img/cotton linen/3.webp', 'A versatile cotton-linen shirting fabric.', 'Cotton Linen', 'Lightweight', 'Cotton Linen Mix'),
('uu', 'orange linen', '0', 4444.00, 4444.00, 'img/pure-linen/uu.png', 'cjhsabdjhcva', 'pure linen', 'dhsbvchjsdv', 'sdc'),
('viscose', 'Viscose Linen Blend Suiting', 'Dull Sky Blue', 450.00, 250.00, 'img/blend linen/1-viscose.jpg', 'A sophisticated blend of viscose and linen.', 'blend linen', 'Lightweight', 'Viscose Linen Blend'),
('zzz', 'linen', 'purple', 6900.00, 9000.00, 'img/dyed-linen/zzz.png', 'vbj sdh', 'dyed linen', 'dbhdsbc', 'c sgc');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_deliveries`
--

CREATE TABLE `subscription_deliveries` (
  `id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `delivery_number` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `delivered_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_deliveries`
--

INSERT INTO `subscription_deliveries` (`id`, `subscription_id`, `delivery_number`, `scheduled_date`, `status`, `delivered_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '2025-03-06', 'delivered', '2025-03-15', NULL, '2025-03-15 10:23:10', '2025-03-15 10:23:10'),
(2, 1, 1, '2025-03-06', 'delivered', '2025-03-15', NULL, '2025-03-15 10:23:34', '2025-03-15 10:23:34'),
(3, 1, 2, '2025-04-06', 'delivered', '2025-03-15', NULL, '2025-03-15 10:40:29', '2025-03-15 10:40:29'),
(4, 3, 1, '2025-03-15', 'delivered', '2025-03-15', NULL, '2025-03-15 10:51:20', '2025-03-15 10:51:20'),
(5, 3, 2, '2025-04-15', 'delivered', '2025-03-15', NULL, '2025-03-15 10:51:22', '2025-03-15 10:51:22'),
(6, 1, 3, '2025-05-06', 'delivered', '2025-03-15', NULL, '2025-03-15 10:51:35', '2025-03-15 10:51:35'),
(7, 5, 1, '2025-03-15', 'delivered', '2025-03-15', NULL, '2025-03-15 11:26:39', '2025-03-15 11:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `swatch_subscriptions`
--

CREATE TABLE `swatch_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `swatch_type` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `swatch_subscriptions`
--

INSERT INTO `swatch_subscriptions` (`id`, `user_id`, `swatch_type`, `duration`, `start_date`, `end_date`, `total_amount`, `payment_method`, `payment_status`, `created_at`) VALUES
(1, 1, 'dark', 3, '2025-03-06', '2025', 12000.00, 'card', 'completed', '2025-03-06 16:01:20'),
(2, 1, 'dark', 1, '2025-03-06', '2025', 4000.00, 'bank', 'completed', '2025-03-06 16:03:47'),
(3, 1, 'dark', 6, '2025-03-15', '2025', 20000.00, 'card', 'active', '2025-03-15 10:32:42'),
(4, 1, 'light', 6, '2025-03-15', '2025', 20000.00, 'card', 'active', '2025-03-15 10:59:37'),
(5, 1, 'dark', 6, '2025-03-15', '2025', 20000.00, 'card', 'active', '2025-03-15 11:25:24');

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
  `postcode` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `user_type` enum('user','admin') DEFAULT 'user',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `code_expiry` bigint(20) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `email`, `password`, `created_at`, `phone`, `address`, `postcode`, `country`, `city`, `user_type`, `email_verified`, `verification_code`, `code_expiry`, `reset_token`, `token_expiry`) VALUES
(1, 'rumaizgvgv', 'rizan', 'r@r', '$2y$10$P7qxW3NIPd9LjEdKOYyJ..AV22uIVk2ULFheQhLMHe9VST87fBYRa', '2025-02-23 13:40:22', '12345', 'zsxdcfgvh', '01500', 'sri lanka', 'colombo', 'user', 0, NULL, NULL, 'abf043834a324c067b8bcc6ec1d3f0f68e126f2047cb58965da5bb5eace7a5ac', 1743235855),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$yourHashedPasswordHere', '2025-02-28 14:49:12', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(3, 'Admin', 'User', 'admin@purelinen.com', '$2y$10$mNuRVTRvtA4fSYQORt7kf.oQR5oCYn9Gbo.hA2WpWpVmOVJqTcmTi', '2025-02-28 14:54:23', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(5, 'Admin', 'User', 'admin1@example.com', '$2y$10$mNuRVTRvtA4fSYQORt7kf.oQR5oCYn9Gbo.hA2WpWpVmOVJqTcmTi', '2025-03-01 07:14:26', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(7, 'll', 'll', 'l@l', '$2y$10$qfzyQg4eKJexDCXRQWdkbe7rh.UeSmdcj47cWlUWL1QgdszXE0sOG', '2025-03-06 09:39:35', NULL, NULL, NULL, NULL, NULL, 'user', 0, NULL, NULL, NULL, NULL),
(8, 'Mohamed', 'Rumaiz', 'purelinen@gmail.com', '$2y$10$W2k4FiNRvshq8p8xPX.Gj.LGlIRtT7qOHWLdr6cr7cJ67FnkV6Pvi', '2025-03-14 12:53:33', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(10, 'rumaiz', 'rr', 'rumaiz@gmail.com', '$2y$10$LgS1fcJJYRs/SSOcp9CKhezVckVEH11sTKmrI6CGatrzUvg5LL246', '2025-03-15 16:23:34', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(11, 'ww', 'ww', 'w@w', '$2y$10$jsR6NQ3tTBTJhlDuD8m0deH69YZIcV5mPgCWeHRvP6d1ubDIILG06', '2025-03-15 16:26:57', NULL, NULL, NULL, NULL, NULL, 'admin', 0, NULL, NULL, NULL, NULL),
(12, 'rum', 'riz', 'rum@gmail.com', '$2y$10$aoSsv1.kiZZ57SXCxXMPEeeXRqh4SCHoRjX4XAdZjJwK9XoOPTHeu', '2025-03-16 08:52:01', NULL, NULL, NULL, NULL, NULL, 'user', 0, NULL, NULL, NULL, NULL),
(18, 'rum', 'rizan', 'mrblackmaster0123@gmail.com', '$2y$10$s.YrcEr28dhL2E1LyhP4oeZO8y0FjxUcxJyha5m5oni1hknGj2oYW', '2025-03-26 16:42:31', NULL, NULL, NULL, NULL, NULL, 'user', 0, '324243', 1743009151, NULL, NULL),
(19, 'rum', 'riz', 'mohamedrumaiz05@gmail.com', '$2y$10$q2X9C3.0uwuuDZea1QRsj.amPgO36UeEzOuQnUwusJVDwupVdv8CC', '2025-03-27 08:12:17', NULL, NULL, NULL, NULL, NULL, 'user', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_name`, `address`, `city`, `country`, `is_default`, `created_at`) VALUES
(3, 1, 'sdcssdvs', 'dscsd', '1234567', '`12347', 1, '2025-03-08 11:38:15');

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
-- Indexes for table `bulk_message_replies`
--
ALTER TABLE `bulk_message_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `featured_products`
--
ALTER TABLE `featured_products`
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
-- Indexes for table `subscription_deliveries`
--
ALTER TABLE `subscription_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `swatch_subscriptions`
--
ALTER TABLE `swatch_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email_verification` (`email`,`verification_code`),
  ADD KEY `idx_password_reset` (`email`,`reset_token`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bulk_message_replies`
--
ALTER TABLE `bulk_message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `featured_products`
--
ALTER TABLE `featured_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `subscription_deliveries`
--
ALTER TABLE `subscription_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `swatch_subscriptions`
--
ALTER TABLE `swatch_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
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
-- Constraints for table `featured_products`
--
ALTER TABLE `featured_products`
  ADD CONSTRAINT `featured_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `subscription_deliveries`
--
ALTER TABLE `subscription_deliveries`
  ADD CONSTRAINT `subscription_deliveries_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `swatch_subscriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `swatch_subscriptions`
--
ALTER TABLE `swatch_subscriptions`
  ADD CONSTRAINT `swatch_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;








-- testing comment