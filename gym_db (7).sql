-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 06:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp(),
  `checkout_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `member_id`, `checkin_time`, `checkout_time`, `notes`, `created_by`) VALUES
(75, 196, '2026-01-27 13:36:56', NULL, NULL, NULL),
(78, 210, '2026-03-22 12:20:57', '2026-03-22 12:21:07', '', NULL),
(79, 211, '2026-05-04 13:02:08', '2026-05-04 13:02:37', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gym_settings`
--

CREATE TABLE `gym_settings` (
  `id` int(11) NOT NULL,
  `gym_name` varchar(255) NOT NULL DEFAULT 'Gym Management System',
  `logo_path` varchar(255) NOT NULL DEFAULT 'gym logo.jpg',
  `background_path` varchar(255) NOT NULL DEFAULT 'gym background.jpg',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sidebar_theme` varchar(50) NOT NULL DEFAULT 'primary',
  `student_discount_enabled` tinyint(1) DEFAULT 1,
  `per_session_fee` decimal(10,2) DEFAULT 50.00,
  `half_month_fee` decimal(10,2) DEFAULT 300.00,
  `one_month_fee` decimal(10,2) DEFAULT 500.00,
  `two_months_fee` decimal(10,2) DEFAULT 900.00,
  `three_months_fee` decimal(10,2) DEFAULT 1300.00,
  `four_months_fee` decimal(10,2) DEFAULT 1700.00,
  `five_months_fee` decimal(10,2) DEFAULT 2100.00,
  `six_months_fee` decimal(10,2) DEFAULT 2500.00,
  `seven_months_fee` decimal(10,2) DEFAULT 2900.00,
  `eight_months_fee` decimal(10,2) DEFAULT 3300.00,
  `nine_months_fee` decimal(10,2) DEFAULT 3700.00,
  `ten_months_fee` decimal(10,2) DEFAULT 4100.00,
  `eleven_months_fee` decimal(10,2) DEFAULT 4500.00,
  `one_year_fee` decimal(10,2) DEFAULT 5000.00,
  `two_years_fee` decimal(10,2) DEFAULT 9000.00,
  `three_years_fee` decimal(10,2) DEFAULT 13000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_settings`
--

INSERT INTO `gym_settings` (`id`, `gym_name`, `logo_path`, `background_path`, `updated_at`, `sidebar_theme`, `student_discount_enabled`, `per_session_fee`, `half_month_fee`, `one_month_fee`, `two_months_fee`, `three_months_fee`, `four_months_fee`, `five_months_fee`, `six_months_fee`, `seven_months_fee`, `eight_months_fee`, `nine_months_fee`, `ten_months_fee`, `eleven_months_fee`, `one_year_fee`, `two_years_fee`, `three_years_fee`) VALUES
(1, 'Olympic Fitness Gym', 'gym_logo_1781195582.jpg', 'gym_background_1781195603.jpg', '2026-06-11 16:33:23', 'secondary', 1, 55.00, 250.00, 500.00, 900.00, 1300.00, 1700.00, 2100.00, 2500.00, 2900.00, 3300.00, 3700.00, 4100.00, 4500.00, 5000.00, 9000.00, 13000.00);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_code` varchar(50) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `plan` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','EXPIRED','SUSPENDED') DEFAULT 'ACTIVE',
  `is_student` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_code`, `fullname`, `email`, `phone`, `address`, `plan`, `start_date`, `end_date`, `status`, `is_student`, `student_id`, `created_at`, `created_by`, `qr_code`, `qr_token`) VALUES
(196, 'MEM2026A59ED0', 'John Rey Peru', '', '', '', '1 Month', '2026-01-27', '2026-02-27', 'EXPIRED', 1, '561', '2026-01-27 05:36:29', 1, 'qr_codes/f72137692b9920b587254293b181d276e675538607794cc2e56621569030c460.png', 'f72137692b9920b587254293b181d276e675538607794cc2e56621569030c460'),
(210, 'MEM2026A4F7C0', 'test', '', '', '', '2 Months', '2026-03-22', '2026-05-22', 'EXPIRED', 0, NULL, '2026-03-22 04:15:33', 1, 'qr_codes/a739bc314e9e01b7dd4c8b666a3157ddf9d9a1b32612b990fff034998bd78123.png', 'a739bc314e9e01b7dd4c8b666a3157ddf9d9a1b32612b990fff034998bd78123'),
(211, 'MEM20268AF786', 'vvien', NULL, '', NULL, 'Per Session', '2026-05-04', '2026-05-04', 'EXPIRED', 0, NULL, '2026-05-04 05:00:29', 1, 'qr_codes/2d18e991301fdf8875df033138669c76773a854f941e0afe588d944a46ff2694.png', '2d18e991301fdf8875df033138669c76773a854f941e0afe588d944a46ff2694');

-- --------------------------------------------------------

--
-- Table structure for table `member_programs`
--

CREATE TABLE `member_programs` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `is_student_discount` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `member_id`, `amount`, `receipt_no`, `payment_method`, `payment_date`, `notes`, `is_student_discount`, `student_id`, `discount_amount`, `reference_no`, `created_by`) VALUES
(159, 196, 500.00, 'R20260127FAECC0', 'Cash', '2026-01-27 00:00:00', NULL, 1, '561', 100.00, '', NULL),
(173, 210, 400.00, 'R20260322BA067A', 'GCash', '2026-03-22 00:00:00', NULL, 0, '', 0.00, '166565', NULL),
(174, 211, 55.00, 'R2026050472EBB5', NULL, '2026-05-04 00:00:00', NULL, 0, NULL, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pos_items`
--

CREATE TABLE `pos_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('beverage','snack','supplement','other') NOT NULL DEFAULT 'beverage',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_items`
--

INSERT INTO `pos_items` (`id`, `name`, `category`, `price`, `stock_quantity`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Mineral Water (500ml)', 'beverage', 15.00, 90, NULL, 1, '2026-01-26 04:48:46', '2026-03-22 04:11:36'),
(2, 'Mineral Water (1L)', 'beverage', 25.00, 35, NULL, 1, '2026-01-26 04:48:46', '2026-03-22 04:11:36'),
(3, 'Coca-Cola (330ml)', 'beverage', 20.00, 69, 'CocaCola 330ml_1773805731.png', 1, '2026-01-26 04:48:46', '2026-03-22 04:22:19'),
(4, 'Sprite (330ml)', 'beverage', 20.00, 78, NULL, 1, '2026-01-26 04:48:46', '2026-01-27 06:51:42'),
(5, 'Red Bull Energy Drink', 'beverage', 85.00, 30, NULL, 1, '2026-01-26 04:48:46', '2026-01-26 04:48:46'),
(6, 'Monster Energy Drink', 'beverage', 80.00, 9, NULL, 1, '2026-01-26 04:48:46', '2026-03-18 03:53:48'),
(7, 'Protein Bar', 'snack', 45.00, 41, NULL, 1, '2026-01-26 04:48:46', '2026-01-29 06:27:08'),
(8, 'Mixed Nuts (100g)', 'snack', 35.00, 60, NULL, 1, '2026-01-26 04:48:46', '2026-01-26 04:48:46'),
(9, 'Banana', 'snack', 10.00, 183, NULL, 1, '2026-01-26 04:48:46', '2026-01-29 06:29:44'),
(10, 'Apple', 'snack', 15.00, 140, NULL, 1, '2026-01-26 04:48:46', '2026-04-28 05:38:07'),
(11, 'Whey Protein (1kg)', 'supplement', 1200.00, 0, NULL, 1, '2026-01-26 04:48:46', '2026-01-27 05:19:55'),
(12, 'Creatine Monohydrate', 'supplement', 800.00, 10, NULL, 1, '2026-01-26 04:48:46', '2026-04-28 05:38:07'),
(13, 'Gym Towel', 'other', 50.00, 20, NULL, 1, '2026-01-26 04:48:46', '2026-01-26 04:48:46'),
(14, 'Lockers Key', 'other', 5.00, 49, NULL, 1, '2026-01-26 04:48:46', '2026-01-27 06:08:43'),
(15, 'Boiled Eggs', 'other', 20.00, 37, NULL, 1, '2026-01-26 05:17:16', '2026-03-22 04:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `pos_sales`
--

CREATE TABLE `pos_sales` (
  `id` int(11) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','gcash','maya','card') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_sales`
--

INSERT INTO `pos_sales` (`id`, `sale_date`, `total_amount`, `payment_method`, `reference_no`, `customer_name`, `customer_phone`, `member_id`, `created_by`, `created_at`) VALUES
(1, '2026-01-26 04:51:27', 80.00, 'cash', '', '', '', NULL, 1, '2026-01-26 04:51:27'),
(2, '2026-01-26 04:51:36', 910.00, 'cash', '', '', '', NULL, 1, '2026-01-26 04:51:36'),
(3, '2026-01-26 04:58:28', 25.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 04:58:28'),
(4, '2026-01-26 05:01:16', 800.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:01:16'),
(5, '2026-01-26 05:05:39', 800.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:05:39'),
(6, '2026-01-26 05:08:03', 800.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:08:03'),
(7, '2026-01-26 05:08:17', 800.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:08:17'),
(8, '2026-01-26 05:08:45', 25.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:08:45'),
(9, '2026-01-26 05:09:13', 25.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:09:13'),
(10, '2026-01-26 05:10:05', 25.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:10:05'),
(11, '2026-01-26 05:11:48', 25.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:11:48'),
(12, '2026-01-26 05:17:36', 120.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:17:36'),
(13, '2026-01-26 05:18:45', 120.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-26 05:18:45'),
(15, '2026-01-27 03:02:27', 25.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:02:27'),
(16, '2026-01-27 03:22:36', 45.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:22:36'),
(17, '2026-01-27 03:22:49', 25.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:22:49'),
(18, '2026-01-27 03:24:49', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:24:49'),
(19, '2026-01-27 03:26:10', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:26:10'),
(20, '2026-01-27 03:26:17', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:26:17'),
(21, '2026-01-27 03:29:42', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:29:42'),
(22, '2026-01-27 03:29:51', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 03:29:51'),
(31, '2026-01-27 03:51:44', 80.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-27 03:51:44'),
(32, '2026-01-27 03:56:00', 80.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-27 03:56:00'),
(33, '2026-01-27 04:01:28', 10.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-27 04:01:28'),
(34, '2026-01-27 04:07:29', 15.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 04:07:29'),
(35, '2026-01-27 05:14:15', 80.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 05:14:15'),
(36, '2026-01-27 05:17:18', 160.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 05:17:18'),
(37, '2026-01-27 05:19:55', 12000.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 05:19:55'),
(38, '2026-01-27 05:27:29', 30.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-27 05:27:29'),
(39, '2026-01-27 06:01:35', 20.00, 'gcash', '500', NULL, NULL, NULL, 1, '2026-01-27 06:01:35'),
(40, '2026-01-27 06:08:43', 5.00, 'gcash', '555', NULL, NULL, NULL, 1, '2026-01-27 06:08:43'),
(41, '2026-01-27 06:51:42', 45.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-27 06:51:42'),
(42, '2026-01-27 07:22:55', 160.00, 'cash', '', NULL, NULL, NULL, 8, '2026-01-27 07:22:55'),
(43, '2026-01-27 09:16:46', 50.00, 'gcash', '6644', NULL, NULL, NULL, 1, '2026-01-27 09:16:46'),
(44, '2026-01-29 06:27:25', 10.00, 'cash', '', NULL, NULL, NULL, 1, '2026-01-29 06:27:25'),
(45, '2026-01-29 06:29:44', 25.00, 'cash', '', NULL, NULL, NULL, 2, '2026-01-29 06:29:44'),
(46, '2026-03-18 03:49:18', 20.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:49:18'),
(47, '2026-03-18 03:49:27', 20.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:49:27'),
(48, '2026-03-18 03:49:41', 35.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:49:41'),
(49, '2026-03-18 03:51:39', 45.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:51:39'),
(50, '2026-03-18 03:51:53', 80.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:51:53'),
(51, '2026-03-18 03:53:10', 45.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:53:10'),
(52, '2026-03-18 03:53:21', 15.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:53:21'),
(53, '2026-03-18 03:53:48', 80.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-18 03:53:48'),
(54, '2026-03-22 04:11:36', 40.00, 'cash', '', NULL, NULL, NULL, 1, '2026-03-22 04:11:36'),
(55, '2026-03-22 04:22:19', 40.00, 'cash', '', NULL, NULL, NULL, 2, '2026-03-22 04:22:19'),
(56, '2026-04-28 05:38:07', 815.00, 'cash', '', NULL, NULL, NULL, 1, '2026-04-28 05:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `pos_sale_items`
--

CREATE TABLE `pos_sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_sale_items`
--

INSERT INTO `pos_sale_items` (`id`, `sale_id`, `item_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 6, 1, 80.00, 80.00, '2026-01-26 04:51:27'),
(2, 2, 6, 1, 80.00, 80.00, '2026-01-26 04:51:36'),
(3, 2, 4, 1, 20.00, 20.00, '2026-01-26 04:51:36'),
(4, 2, 9, 1, 10.00, 10.00, '2026-01-26 04:51:36'),
(5, 2, 12, 1, 800.00, 800.00, '2026-01-26 04:51:36'),
(6, 3, 10, 1, 15.00, 15.00, '2026-01-26 04:58:28'),
(7, 3, 9, 1, 10.00, 10.00, '2026-01-26 04:58:28'),
(8, 4, 12, 1, 800.00, 800.00, '2026-01-26 05:01:16'),
(9, 5, 12, 1, 800.00, 800.00, '2026-01-26 05:05:39'),
(10, 6, 12, 1, 800.00, 800.00, '2026-01-26 05:08:03'),
(11, 7, 12, 1, 800.00, 800.00, '2026-01-26 05:08:17'),
(12, 8, 9, 1, 10.00, 10.00, '2026-01-26 05:08:45'),
(13, 8, 10, 1, 15.00, 15.00, '2026-01-26 05:08:45'),
(14, 9, 9, 1, 10.00, 10.00, '2026-01-26 05:09:13'),
(15, 9, 10, 1, 15.00, 15.00, '2026-01-26 05:09:13'),
(16, 10, 9, 1, 10.00, 10.00, '2026-01-26 05:10:05'),
(17, 10, 10, 1, 15.00, 15.00, '2026-01-26 05:10:05'),
(18, 11, 9, 1, 10.00, 10.00, '2026-01-26 05:11:48'),
(19, 11, 10, 1, 15.00, 15.00, '2026-01-26 05:11:48'),
(20, 12, 15, 6, 20.00, 120.00, '2026-01-26 05:17:36'),
(21, 13, 15, 6, 20.00, 120.00, '2026-01-26 05:18:45'),
(23, 15, 2, 1, 25.00, 25.00, '2026-01-27 03:02:27'),
(24, 16, 2, 1, 25.00, 25.00, '2026-01-27 03:22:36'),
(25, 16, 3, 1, 20.00, 20.00, '2026-01-27 03:22:36'),
(26, 17, 2, 1, 25.00, 25.00, '2026-01-27 03:22:49'),
(27, 18, 1, 1, 15.00, 15.00, '2026-01-27 03:24:49'),
(28, 19, 10, 1, 15.00, 15.00, '2026-01-27 03:26:10'),
(29, 20, 10, 1, 15.00, 15.00, '2026-01-27 03:26:17'),
(30, 21, 1, 1, 15.00, 15.00, '2026-01-27 03:29:42'),
(31, 22, 1, 1, 15.00, 15.00, '2026-01-27 03:29:51'),
(40, 31, 6, 1, 80.00, 80.00, '2026-01-27 03:51:44'),
(41, 32, 6, 1, 80.00, 80.00, '2026-01-27 03:56:00'),
(42, 33, 9, 1, 10.00, 10.00, '2026-01-27 04:01:28'),
(43, 34, 10, 1, 15.00, 15.00, '2026-01-27 04:07:29'),
(44, 35, 6, 1, 80.00, 80.00, '2026-01-27 05:14:15'),
(45, 36, 6, 2, 80.00, 160.00, '2026-01-27 05:17:18'),
(46, 37, 11, 10, 1200.00, 12000.00, '2026-01-27 05:19:55'),
(47, 38, 9, 3, 10.00, 30.00, '2026-01-27 05:27:29'),
(48, 39, 3, 1, 20.00, 20.00, '2026-01-27 06:01:35'),
(49, 40, 14, 1, 5.00, 5.00, '2026-01-27 06:08:43'),
(50, 41, 4, 1, 20.00, 20.00, '2026-01-27 06:51:42'),
(51, 41, 10, 1, 15.00, 15.00, '2026-01-27 06:51:42'),
(52, 41, 9, 1, 10.00, 10.00, '2026-01-27 06:51:42'),
(53, 42, 6, 2, 80.00, 160.00, '2026-01-27 07:22:55'),
(54, 43, 2, 2, 25.00, 50.00, '2026-01-27 09:16:46'),
(55, 44, 9, 1, 10.00, 10.00, '2026-01-29 06:27:25'),
(56, 45, 1, 1, 15.00, 15.00, '2026-01-29 06:29:44'),
(57, 45, 9, 1, 10.00, 10.00, '2026-01-29 06:29:44'),
(58, 46, 3, 1, 20.00, 20.00, '2026-03-18 03:49:18'),
(59, 47, 3, 1, 20.00, 20.00, '2026-03-18 03:49:27'),
(60, 48, 3, 1, 20.00, 20.00, '2026-03-18 03:49:41'),
(61, 48, 1, 1, 15.00, 15.00, '2026-03-18 03:49:41'),
(62, 49, 3, 1, 20.00, 20.00, '2026-03-18 03:51:39'),
(63, 49, 2, 1, 25.00, 25.00, '2026-03-18 03:51:39'),
(64, 50, 3, 2, 20.00, 40.00, '2026-03-18 03:51:53'),
(65, 50, 1, 1, 15.00, 15.00, '2026-03-18 03:51:53'),
(66, 50, 2, 1, 25.00, 25.00, '2026-03-18 03:51:53'),
(67, 51, 2, 1, 25.00, 25.00, '2026-03-18 03:53:10'),
(68, 51, 3, 1, 20.00, 20.00, '2026-03-18 03:53:10'),
(69, 52, 1, 1, 15.00, 15.00, '2026-03-18 03:53:21'),
(70, 53, 6, 1, 80.00, 80.00, '2026-03-18 03:53:48'),
(71, 54, 2, 1, 25.00, 25.00, '2026-03-22 04:11:36'),
(72, 54, 1, 1, 15.00, 15.00, '2026-03-22 04:11:36'),
(73, 55, 3, 1, 20.00, 20.00, '2026-03-22 04:22:19'),
(74, 55, 15, 1, 20.00, 20.00, '2026-03-22 04:22:19'),
(75, 56, 12, 1, 800.00, 800.00, '2026-04-28 05:38:07'),
(76, 56, 10, 1, 15.00, 15.00, '2026-04-28 05:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fitness','supplement','other') NOT NULL DEFAULT 'fitness',
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `description`, `type`, `price`, `duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Boxing Training', 'Professional boxing training sessions', 'fitness', 1500.00, 30, 1, '2026-01-26 04:17:05', '2026-01-26 04:17:05'),
(2, 'Zumba Classes', 'Fun dance fitness classes', 'fitness', 800.00, 30, 1, '2026-01-26 04:17:05', '2026-01-26 04:17:05'),
(3, 'Protein Supplements', 'High-quality protein supplements', 'supplement', 2500.00, NULL, 1, '2026-01-26 04:17:05', '2026-01-26 04:17:05'),
(4, 'General Gym Membership', 'Access to all gym facilities', 'fitness', 1200.00, 30, 1, '2026-01-26 04:17:05', '2026-01-26 04:17:05'),
(5, 'Per Session', 'Pay per gym session', 'fitness', 50.00, 1, 1, '2026-01-26 04:17:05', '2026-01-26 04:17:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `on_duty` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `password`, `role`, `created_at`, `on_duty`) VALUES
(1, 'Gym Administrator', 'admin', '$2y$10$z0JNe43ik6B65LxzXVZTveM5vQJDEVrmvrf1CzAiXcJR6k3yK9hK6', 'admin', '2026-01-14 05:43:14', 1),
(2, 'Gym Cashier', 'cashier', '$2y$10$mNWq/LEmbhESmkANUdX4juSPg49P3/c3IWhdxlL0SVjcadI80tBOG', 'cashier', '2026-01-14 05:43:14', 1),
(8, 'ate cashier', 'ate', '$2y$10$/RhcgkJR7w8fy4C7.4QBv.rj9uni/zqKDzhRxWPaf9hrjkKdFyysW', 'cashier', '2026-01-27 07:17:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 2, 'hide_recent_sales', '1', '2026-01-27 03:02:49', '2026-01-27 03:02:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `fk_attendance_created_by` (`created_by`);

--
-- Indexes for table `gym_settings`
--
ALTER TABLE `gym_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_code` (`member_code`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `fk_members_created_by` (`created_by`);

--
-- Indexes for table `member_programs`
--
ALTER TABLE `member_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member_program` (`member_id`,`program_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `fk_payments_created_by` (`created_by`);

--
-- Indexes for table `pos_items`
--
ALTER TABLE `pos_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item_name` (`name`);

--
-- Indexes for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `gym_settings`
--
ALTER TABLE `gym_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `member_programs`
--
ALTER TABLE `member_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `pos_items`
--
ALTER TABLE `pos_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `member_programs`
--
ALTER TABLE `member_programs`
  ADD CONSTRAINT `member_programs_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_programs_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD CONSTRAINT `pos_sales_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pos_sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  ADD CONSTRAINT `pos_sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pos_sale_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `pos_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
