-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 08, 2026 at 05:32 AM
-- Server version: 8.2.0
-- PHP Version: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bni_enterprises2`
--

-- --------------------------------------------------------

--
-- Table structure for table `accessories`
--

CREATE TABLE `accessories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT '0.00',
  `selling_price` decimal(15,2) DEFAULT '0.00',
  `current_stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `accessories`
--

INSERT INTO `accessories` (`id`, `name`, `sku`, `purchase_price`, `selling_price`, `current_stock`, `created_at`, `updated_at`) VALUES
(1, 'Type', 'CST-1777618962-70', 0.00, 0.00, -1, '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(2, 'Helmet', '1231', 900.00, 1200.00, 90, '2026-05-07 06:49:31', '2026-05-07 06:49:31'),
(3, 'Helmet', 'CST-1778136637-40', 0.00, 0.00, 0, '2026-05-07 06:50:37', '2026-05-07 06:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `bank_deposits`
--

CREATE TABLE `bank_deposits` (
  `id` int NOT NULL,
  `destination_id` int NOT NULL,
  `deposit_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `deposit_type` enum('cash','cheque','transfer','online','other') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `deposited_by` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `id` int NOT NULL,
  `purchase_order_id` int DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `inventory_date` date DEFAULT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `motor_number` varchar(100) DEFAULT NULL,
  `model_id` int DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `selling_date` date DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `margin` decimal(15,2) DEFAULT '0.00',
  `status` enum('in_stock','sold','returned','returned_to_supplier','reserved','damaged_lost') DEFAULT 'in_stock',
  `return_date` date DEFAULT NULL,
  `return_amount` decimal(15,2) DEFAULT NULL,
  `return_notes` text,
  `safeguard_notes` text,
  `notes` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `purchase_order_id`, `order_date`, `inventory_date`, `chassis_number`, `motor_number`, `model_id`, `color`, `purchase_price`, `selling_price`, `selling_date`, `customer_id`, `tax_amount`, `margin`, `status`, `return_date`, `return_amount`, `return_notes`, `safeguard_notes`, `notes`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-02-03', '2026-02-05', 'LY05G48270002304', '*XRLY48052125D0002228*', 1, 'Black', 125225.00, 210000.00, '2026-05-06', NULL, 12522.50, 72252.50, 'sold', NULL, NULL, NULL, NULL, '', NULL, '2026-04-20 09:01:57', '2026-05-06 16:38:41'),
(2, 1, '2026-02-03', '2026-02-05', 'LY05G48270002202', '*XRLY48052125D0002322*', 1, 'Grey', 125225.00, NULL, NULL, NULL, 125.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(3, 1, '2026-02-03', '2026-02-05', 'DD35G48130001177', '*48V350WA8T454708922*', 13, 'Black', 94595.00, 130000.00, '2026-04-28', 5, 9459.50, 25945.50, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_13_w_bike_h2_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(4, 1, '2026-02-03', '2026-02-05', 'M615G72380002665', 'A9A756800994', 9, 'Silver', 220721.00, 242000.00, NULL, NULL, 221.00, 0.00, 'returned', NULL, NULL, NULL, NULL, 'Returned on 200,000 Cheque to be issued.', 'uploads/models/model_9_m6_k6_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(5, 1, '2026-02-03', '2026-02-05', 'T910G72260006966', '*XR9S72102825N0007369*', 2, 'Red', 161261.00, 179000.00, '2026-03-05', NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(6, 1, '2026-02-03', '2026-02-05', 'T910G72260007041', '*XR9S72102825N0007701*', 2, 'Black', 161261.00, 179000.00, NULL, NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(7, 1, '2026-02-03', '2026-02-05', 'T910G72260006884', '*XR9S72102825N0007393*', 2, 'Grey', 161261.00, 179000.00, '2026-03-02', NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(8, 1, '2026-02-03', '2026-02-05', 'E820G72380002293', '*PJE872203525N0002160*', 7, 'Grey', 251351.00, 279000.00, '2026-02-23', NULL, 251.00, 27398.00, 'sold', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(9, 1, '2026-02-03', '2026-02-05', 'TH12G72260005515', 'AIMTP721240259005364', 5, 'Grey', 179279.00, 199000.00, '2026-02-22', NULL, 179.00, 19542.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_5_thrill_pro_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(10, 1, '2026-02-03', '2026-02-05', 'TH12G72260006004', 'AIMTP721240259006297', 5, 'Black', 179279.00, 200000.00, '2026-05-06', NULL, 17927.90, 2793.10, 'returned', '2026-05-07', 0.00, '30000', NULL, '', 'uploads/models/model_5_thrill_pro_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(11, 1, '2026-02-03', '2026-02-05', 'T910L72300000632', '*XR9S7210282500000640*', 3, 'Silver', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'damaged_lost', NULL, NULL, NULL, '', '', 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(12, 1, '2026-02-03', '2026-02-05', 'T910L72300000916', '*XR9S7210282500000927*', 3, 'Black', 193694.00, 234000.00, '2026-03-07', NULL, 194.00, 40112.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(13, 1, '2026-02-03', '2026-02-05', 'TH12L72300000445', 'AIMTP72124025N001005', 6, 'Black', 211712.00, NULL, NULL, NULL, 212.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_6_thrill_pro_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(14, 1, '2026-02-03', '2026-02-05', 'TH12L72300000416', 'AIMTP72124025N001176', 6, 'Grey', 211712.00, 246000.00, '2026-03-18', NULL, 212.00, 34076.00, 'sold', NULL, NULL, NULL, NULL, '(2,470,276) Received', 'uploads/models/model_6_thrill_pro_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(15, 2, '2026-02-27', '2026-03-12', 'M615L72300006176', 'XRM672153025D0007536', 11, 'Unknown', 254955.00, 285000.00, '2026-03-12', NULL, 285.00, 29760.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_11_m6_lithium_np_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(16, 2, '2026-02-27', '2026-03-12', 'M615L72300006278', 'XRM672153025D0007499', 11, 'Unknown', 254955.00, 283000.00, '2026-03-12', NULL, 285.00, 27760.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_11_m6_lithium_np_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(17, 3, '2026-03-12', '2026-03-16', 'T910G72260008882', '*XR9S72102825D0007890*', 4, 'Red', 238739.00, 179000.00, NULL, NULL, 239.00, -59978.00, 'sold', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(18, 3, '2026-03-12', '2026-03-16', 'T910G72260008478', '*XR9S72102825D0007855*', 4, 'Black', 238739.00, NULL, NULL, NULL, 239.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(19, 3, '2026-03-12', '2026-03-16', 'T910G72260008679', '*XR9S72102825D0007954*', 2, 'Grey', 161261.00, 179000.00, '2026-03-18', NULL, 179.00, 17560.00, 'sold', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(20, 3, '2026-03-12', '2026-03-16', 'TH12G72260006279', 'AIMTP721240259006047', 5, 'Unknown', 179279.00, NULL, NULL, NULL, 179.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_5_thrill_pro_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(21, 3, '2026-03-12', '2026-03-16', 'TH12G72260006236', 'AIMTP721240259006039', 5, 'Unknown', 179279.00, NULL, NULL, NULL, 179.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, '(997,297) Receiving', 'uploads/models/model_5_thrill_pro_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(22, 4, '2026-03-18', '2026-03-27', 'E820G72380000466', '12ZW7271327YE*CERR116670C*', 8, 'Blue', 247748.00, NULL, NULL, NULL, 247.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(23, 4, '2026-03-18', '2026-03-27', 'P308L72300000159', 'PHPM7208352610000422', 12, 'Unknown', 234234.00, NULL, NULL, NULL, 234.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_12_premium_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(24, 4, '2026-03-18', '2026-03-27', 'E810G72380000595', '*10ZW7273316YECKTS0000107*', 7, 'Grey', 251351.00, NULL, NULL, NULL, 251.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(25, 4, '2026-03-18', '2026-03-27', 'T910G72260008720', '*XR9S72102825D0007987*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(26, 4, '2026-03-18', '2026-03-27', 'T910G72260008894', '*XR9S72102825D0008251*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, 'Diff ledger= (70,137)+ new delivery', 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(27, 4, '2026-03-18', '2026-03-27', 'T910G72260008737', '*XR9S72102825D0008003*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 09:01:57', '2026-05-12 09:47:11'),
(28, 5, '2026-04-26', '2026-04-26', 'NW-21233', 'MT-002', 7, 'Red', 120000.00, 150000.00, '2026-04-26', 5, 120.00, 29880.00, 'returned', '2026-04-26', 190000.00, 'Said not appliable', 'must be charged 100 percent for the first time use', '', NULL, '2026-04-26 10:13:52', '2026-04-26 10:21:35'),
(30, 7, '2026-04-27', '2026-04-27', 'NW-212331', 'MT-002', 7, 'Red', 190000.00, 240000.00, '2026-04-28', 5, 19000.00, 31000.00, 'sold', NULL, NULL, NULL, 'must be charged 100 percent for the first time use', '', NULL, '2026-04-27 10:25:19', '2026-04-28 04:50:16'),
(32, 9, '2026-04-27', '2026-04-27', 'NW-212331a', 'MT-002', 7, 'Red', 190000.00, 230000.00, '2026-05-02', 1, 19000.00, 21000.00, 'sold', NULL, NULL, NULL, 'must be charged 100 percent for the first time use', '', NULL, '2026-04-27 10:29:45', '2026-05-01 13:22:14'),
(33, 10, '2026-04-28', '2026-04-28', 'NW-2123353', 'MT-GT-022', 8, 'Newd', 20000.00, 60000.00, '2026-04-28', 1, 2000.00, 38000.00, 'sold', NULL, NULL, NULL, 'must be charged 100 percent for the first time use a', '', NULL, '2026-04-28 04:24:26', '2026-04-28 05:08:07'),
(34, 11, '2026-04-28', '2026-04-28', 'NW-212331aa', 'MT-GT-02a', 11, 'Newda', 40000.00, 60000.00, '2026-04-28', 1, 4000.00, 16000.00, 'sold', NULL, NULL, NULL, 'must be charged 100 percent for the first time use aa', '', 'uploads/models/model_11_m6_lithium_np_electric_bike.webp', '2026-04-28 04:29:37', '2026-05-12 09:47:11'),
(35, 12, '2026-04-28', '2026-04-28', 'NW-21233213', 'MT-002414', 10, 'Reda', 90000.00, 130000.00, '2026-04-28', 5, 9000.00, 31000.00, 'sold', NULL, NULL, NULL, 'new hai', '', 'uploads/models/model_10_m6_np_electric_bike.webp', '2026-04-28 04:33:26', '2026-05-12 09:47:11'),
(36, 13, '2026-04-28', '2026-04-28', 'NW-21233132', 'MT-0021231', 2, 'Yellow', 90000.00, 220000.00, '2026-04-28', 6, 9000.00, 121000.00, 'sold', NULL, NULL, NULL, 'theek hai', '', 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-28 04:36:57', '2026-05-12 09:47:11'),
(37, 14, '2026-05-01', '2026-05-01', 'NW-212335123', 'MT-GT-02aa', 7, 'Yellow', 290000.00, 340000.00, '2026-05-01', 5, 29000.00, 21000.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/bike_6a2e70a3860e6.webp', '2026-05-01 06:59:16', '2026-06-14 09:13:08'),
(38, 15, '2026-05-06', '2026-05-06', 'T929283007', 'TI8399uue', 2, 'Rad', 200000.00, 220000.00, '2026-05-06', NULL, 20000.00, 0.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/img_69fb71525af01.jpg', '2026-05-06 16:50:26', '2026-05-06 16:51:52'),
(39, 16, '2026-05-07', '2026-05-07', 'NW-21233123', 'MT-GT-0223', 14, 'Red', 250000.00, NULL, NULL, NULL, 25000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', 'uploads/bike_6a0308a7eb3fc.webp', '2026-05-07 06:41:42', '2026-05-12 11:02:00'),
(40, 16, '2026-05-07', '2026-05-07', 'qrqwer', '12341', 14, 'Yellow', 250000.00, 320000.00, '2026-05-07', 2, 25000.00, 45000.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/models/model_14_super_star_70.webp', '2026-05-07 06:41:42', '2026-05-12 09:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `bike_requests`
--

CREATE TABLE `bike_requests` (
  `id` int NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_details` text,
  `status` enum('pending','contacted','fulfilled','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cheque_register`
--

CREATE TABLE `cheque_register` (
  `id` int NOT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `type` enum('payment','receipt','refund') DEFAULT NULL,
  `status` enum('pending','cleared','bounced','cancelled') DEFAULT 'pending',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cheque_register`
--

INSERT INTO `cheque_register` (`id`, `cheque_number`, `bank_name`, `cheque_date`, `amount`, `type`, `status`, `reference_type`, `reference_id`, `party_name`, `notes`, `created_at`) VALUES
(1, '03420810', 'UBL', '2026-02-03', 2535000.00, 'payment', 'cleared', 'purchase_order', 1, 'Default Supplier', 'First Order', '2026-04-20 09:01:57'),
(2, '03420811', 'UBL', '2026-02-27', 509910.00, 'payment', 'cleared', 'purchase_order', 2, 'Default Supplier', 'Second Order', '2026-04-20 09:01:57'),
(3, '03420809', 'UBL', '2026-03-12', 1002710.00, 'payment', 'cleared', 'purchase_order', 3, 'Default Supplier', 'Third Order', '2026-04-20 09:01:57'),
(4, 'D72981756', 'Meezan', '2026-03-18', 1241441.00, 'payment', 'cleared', 'purchase_order', 4, 'Default Supplier', 'Fourth Order', '2026-04-20 09:01:57'),
(5, 'CHQ-1123', 'HBL', '2026-04-26', 150000.00, 'receipt', 'pending', 'sale', 28, 'Yasin Ullah', '', '2026-04-26 10:17:45'),
(6, 'CHQ-1123', 'HBL', '2026-04-30', 120000.00, 'payment', 'cleared', 'purchase_order', 9, 'Default Supplier', '', '2026-04-27 10:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `is_filer` tinyint(1) DEFAULT '1',
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `cnic`, `is_filer`, `address`, `created_at`) VALUES
(1, 'Ahmed Ali', '0321-1234567', '35201-1234567-1', 1, 'Dera Ghazi Khan, Punjab', '2026-04-20 08:56:23'),
(2, 'Muhammad Usman', '0333-7654321', '35201-7654321-3', 1, 'Muzaffargarh, Punjab', '2026-04-20 08:56:23'),
(3, 'Bilal Hussain', '0345-9876543', '35201-9876543-5', 1, 'Rajanpur, Punjab', '2026-04-20 08:56:23'),
(4, 'Zafar Iqbal', '0312-4567890', '35201-4567890-7', 1, 'Layyah, Punjab', '2026-04-20 08:56:23'),
(5, 'Yasin Ullah', '03139842219', '11102-0356023-4', 1, 'Post Office Domel District Bannu', '2026-04-26 10:16:43'),
(6, 'Shams Uddin', '03338870707', '11102-0356233-4', 1, 'Al-Mandoos Shoes near Qasaban Gate Mazari Mandi Bannu', '2026-04-28 04:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_allocations`
--

CREATE TABLE `deposit_allocations` (
  `id` int NOT NULL,
  `deposit_id` int NOT NULL,
  `allocation_id` int DEFAULT NULL,
  `bike_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income_expenses`
--

CREATE TABLE `income_expenses` (
  `id` int NOT NULL,
  `entry_date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','cheque','bank_transfer','online','other') DEFAULT 'cash',
  `reference` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income_expenses`
--

INSERT INTO `income_expenses` (`id`, `entry_date`, `type`, `category`, `amount`, `payment_method`, `reference`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-04-20', 'expense', 'Bijli', 900.00, 'cash', 'yasin ne diye', '', 2, '2026-04-20 11:21:25'),
(2, '2026-04-20', 'income', 'Commission', 1500.00, 'cash', 'new', '', 1, '2026-04-20 11:29:39'),
(3, '2026-04-28', 'expense', 'Bijli', 9000.00, 'cash', '', '', 1, '2026-04-28 09:37:47'),
(4, '2026-05-07', 'expense', 'Bijli', 599.00, 'cash', '', '', 1, '2026-05-07 04:23:48'),
(5, '2026-05-07', 'income', 'Commission', 12000.00, 'cash', '', '', 1, '2026-05-07 04:24:01'),
(6, '2026-05-07', 'expense', 'Monthly Expense', 15000.00, 'cash', '', '', 1, '2026-05-07 04:24:28'),
(7, '2026-05-07', 'income', 'Monthly Income', 19000.00, 'cash', '', '', 1, '2026-05-07 04:24:55'),
(10, '2026-05-07', 'expense', 'Inventory Loss', 193694.00, 'other', 'Bike ID: 11 (T910L72300000632)', 'Automated expense for Damaged/Lost bike.', 1, '2026-05-07 05:26:12'),
(11, '2026-05-07', 'expense', 'Rent', 12000.00, 'cash', '', '', 1, '2026-05-07 06:57:30');

-- --------------------------------------------------------

--
-- Table structure for table `installments`
--

CREATE TABLE `installments` (
  `id` int NOT NULL,
  `bike_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `due_date` date NOT NULL,
  `installment_amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT '0.00',
  `penalty_fee` decimal(15,2) DEFAULT '0.00',
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `payment_id` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `installments`
--

INSERT INTO `installments` (`id`, `bike_id`, `customer_id`, `due_date`, `installment_amount`, `amount_paid`, `penalty_fee`, `status`, `payment_id`, `notes`, `created_at`, `updated_at`) VALUES
(7, 36, 6, '2026-05-28', 33000.00, 33000.00, 0.00, 'paid', 23, 'Installment 1 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:24:51'),
(8, 36, 6, '2026-06-28', 33000.00, 33000.00, 9000.00, 'paid', 24, 'Installment 2 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:25:07'),
(9, 36, 6, '2026-07-28', 33000.00, 0.00, 0.00, 'pending', NULL, 'Installment 3 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:24:33'),
(10, 36, 6, '2026-08-28', 33000.00, 0.00, 0.00, 'pending', NULL, 'Installment 4 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:24:33'),
(11, 36, 6, '2026-09-28', 33000.00, 0.00, 0.00, 'pending', NULL, 'Installment 5 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:24:33'),
(12, 36, 6, '2026-10-28', 33000.00, 0.00, 0.00, 'pending', NULL, 'Installment 6 for Chassis NW-21233132', '2026-04-28 10:24:33', '2026-04-28 10:24:33'),
(13, 37, 5, '2026-06-01', 26666.67, 26666.67, 9000.00, 'paid', 27, 'Installment 1 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:03:28'),
(14, 37, 5, '2026-07-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 2 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(15, 37, 5, '2026-08-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 3 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(16, 37, 5, '2026-09-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 4 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(17, 37, 5, '2026-10-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 5 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(18, 37, 5, '2026-11-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 6 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(19, 37, 5, '2026-12-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 7 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(20, 37, 5, '2027-01-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 8 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(21, 37, 5, '2027-02-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 9 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(22, 37, 5, '2027-03-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 10 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(23, 37, 5, '2027-04-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 11 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42'),
(24, 37, 5, '2027-05-01', 26666.67, 0.00, 0.00, 'pending', NULL, 'Installment 12 for Chassis NW-212335123', '2026-05-01 07:02:42', '2026-05-01 07:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `leadership`
--

CREATE TABLE `leadership` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `message` text,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leadership`
--

INSERT INTO `leadership` (`id`, `name`, `position`, `image`, `message`, `sort_order`, `created_at`) VALUES
(1, 'Yasin Ullah', 'CEO', 'uploads/img_6a02d8ea25491.jpg', 'This is the message from the CEO of the company deal with it', 0, '2026-05-12 07:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int NOT NULL,
  `entry_date` date DEFAULT NULL,
  `entry_type` enum('debit','credit') DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `party_type` enum('customer','supplier','other') DEFAULT NULL,
  `party_id` int DEFAULT NULL,
  `description` text,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`id`, `entry_date`, `entry_type`, `amount`, `party_type`, `party_id`, `description`, `reference_type`, `reference_id`, `balance`, `created_at`) VALUES
(1, '2026-04-26', 'credit', 150000.00, 'customer', 5, 'Sale of Chassis: NW-21233', 'sale', 28, 150000.00, '2026-04-26 10:17:45'),
(2, '2026-04-26', 'debit', 190000.00, 'customer', 0, 'Return for Bike ID: 28', 'return', 28, 190000.00, '2026-04-26 10:21:35'),
(4, '2026-04-28', 'credit', 130000.00, 'customer', 5, 'Sale of Chassis: NW-21233213', 'sale', 35, 130000.00, '2026-04-28 04:43:01'),
(5, '2026-04-28', 'debit', 240000.00, 'customer', 5, 'Sale of Chassis: NW-212331', 'sale', 30, 240000.00, '2026-04-28 04:50:16'),
(6, '2026-04-28', 'credit', 220000.00, 'customer', 5, 'Down Payment for Chassis: NW-212331', 'down_payment', 30, 220000.00, '2026-04-28 04:50:16'),
(7, '2026-04-28', 'debit', 60000.00, 'customer', 1, 'Sale of Chassis: NW-2123353', 'sale', 33, 60000.00, '2026-04-28 05:08:07'),
(8, '2026-04-28', 'credit', 30000.00, 'customer', 1, 'Down Payment for Chassis: NW-2123353', 'down_payment', 33, 30000.00, '2026-04-28 05:08:07'),
(9, '2026-04-28', 'debit', 60000.00, 'customer', 1, 'Sale of Chassis: NW-212331aa', 'sale', 34, 60000.00, '2026-04-28 05:09:01'),
(10, '2026-04-28', 'credit', 60000.00, 'customer', 1, 'Down Payment for Chassis: NW-212331aa', 'down_payment', 34, 60000.00, '2026-04-28 05:09:01'),
(11, '2026-04-28', 'credit', 30000.00, 'customer', 1, 'Payment Received: baqaya', 'payment', NULL, NULL, '2026-04-28 05:21:39'),
(12, '2026-04-28', 'credit', 600.00, 'customer', 1, 'Payment Received: ', 'payment', NULL, NULL, '2026-04-28 05:25:33'),
(13, '2026-04-28', 'debit', 130000.00, 'customer', 5, 'Sale of Chassis: DD35G48130001177 from Quote #1', 'sale', 3, 130000.00, '2026-04-28 09:37:06'),
(14, '2026-04-28', 'credit', 130000.00, 'customer', 5, 'Payment for Quote #1', 'payment', 3, 130000.00, '2026-04-28 09:37:06'),
(24, '2026-04-28', 'debit', 220000.00, 'customer', 6, 'Sale of Chassis: NW-21233132', 'sale', 36, 220000.00, '2026-04-28 10:24:33'),
(25, '2026-04-28', 'credit', 22000.00, 'customer', 6, 'Down Payment for Chassis: NW-21233132', 'down_payment', 36, 22000.00, '2026-04-28 10:24:33'),
(26, '2026-04-28', 'credit', 33000.00, 'customer', 6, 'Installment payment for Chassis: NW-21233132', 'installment', 7, 33000.00, '2026-04-28 10:24:51'),
(27, '2026-04-28', 'debit', 9000.00, 'customer', 6, 'Penalty fee for Chassis: NW-21233132', 'penalty', 8, 9000.00, '2026-04-28 10:25:07'),
(28, '2026-04-28', 'credit', 42000.00, 'customer', 6, 'Installment payment for Chassis: NW-21233132', 'installment', 8, 42000.00, '2026-04-28 10:25:07'),
(29, '2026-05-01', 'debit', 340000.00, 'customer', 5, 'Sale of Chassis: NW-212335123', 'sale', 37, 340000.00, '2026-05-01 07:02:42'),
(30, '2026-05-01', 'credit', 20000.00, 'customer', 5, 'Down Payment for Chassis: NW-212335123', 'down_payment', 37, 20000.00, '2026-05-01 07:02:42'),
(31, '2026-05-01', 'debit', 9000.00, 'customer', 5, 'Penalty fee for Chassis: NW-212335123', 'penalty', 13, 9000.00, '2026-05-01 07:03:28'),
(32, '2026-05-01', 'credit', 35666.67, 'customer', 5, 'Installment payment for Chassis: NW-212335123', 'installment', 13, 35666.67, '2026-05-01 07:03:28'),
(33, '2026-05-02', 'debit', 230000.00, 'customer', 1, 'Sale of Chassis: NW-212331a', 'sale', 32, 230000.00, '2026-05-01 13:22:14'),
(34, '2026-05-02', 'credit', 230000.00, 'customer', 1, 'Down Payment for Chassis: NW-212331a', 'down_payment', 32, 230000.00, '2026-05-01 13:22:14'),
(35, '2026-05-06', 'debit', 210000.00, 'customer', NULL, 'Sale of Chassis: LY05G48270002304', 'sale', 1, 210000.00, '2026-05-06 16:38:41'),
(36, '2026-05-06', 'credit', 210000.00, 'customer', NULL, 'Down Payment for Chassis: LY05G48270002304', 'down_payment', 1, 210000.00, '2026-05-06 16:38:41'),
(37, '2026-05-06', 'debit', 220000.00, 'customer', NULL, 'Sale of Chassis: T929283007', 'sale', 38, 220000.00, '2026-05-06 16:51:52'),
(38, '2026-05-06', 'credit', 220000.00, 'customer', NULL, 'Down Payment for Chassis: T929283007', 'down_payment', 38, 220000.00, '2026-05-06 16:51:52'),
(39, '2026-05-06', 'debit', 200000.00, 'customer', NULL, 'Sale of Chassis: TH12G72260006004', 'sale', 10, 200000.00, '2026-05-06 16:54:50'),
(40, '2026-05-06', 'credit', 200000.00, 'customer', NULL, 'Down Payment for Chassis: TH12G72260006004', 'down_payment', 10, 200000.00, '2026-05-06 16:54:50'),
(41, '2026-05-07', 'credit', 200000.00, 'customer', NULL, 'Bike Return (Reversal) for Chassis: TH12G72260006004', 'return_reversal', 10, 200000.00, '2026-05-06 16:57:35'),
(42, '2026-05-07', 'debit', 320000.00, 'customer', 2, 'Sale of Chassis: qrqwer', 'sale', 40, 320000.00, '2026-05-07 06:50:37'),
(43, '2026-05-07', 'credit', 320000.00, 'customer', 2, 'Down Payment for Chassis: qrqwer', 'down_payment', 40, 320000.00, '2026-05-07 06:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `id` int NOT NULL,
  `model_code` varchar(50) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `short_code` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `top_speed` varchar(50) DEFAULT NULL,
  `max_range` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`id`, `model_code`, `model_name`, `category`, `short_code`, `image`, `created_at`, `top_speed`, `max_range`) VALUES
(1, 'LY SI', 'LY SI Electric Bike', 'Electric Bike', 'LY', NULL, '2026-04-20 08:56:23', NULL, NULL),
(2, 'T9 Sports', 'T9 Sports Electric Bike', 'Electric Bike', 'T9', 'uploads/models/model_2_t9_sports_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(3, 'T9 Sports LFP', 'T9 Sports LFP Electric Bike', 'Electric Bike', 'T9 LFP', 'uploads/models/model_3_t9_sports_lfp_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(4, 'T9 Eco', 'T9 Eco Electric Bike', 'Electric Bike', 'T9 Eco', NULL, '2026-04-20 08:56:23', NULL, NULL),
(5, 'Thrill Pro', 'Thrill Pro Electric Bike', 'Electric Bike', 'TP', 'uploads/models/model_5_thrill_pro_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(6, 'Thrill Pro LFP', 'Thrill Pro LFP Electric Bike', 'Electric Bike', 'TP LFP', 'uploads/models/model_6_thrill_pro_lfp_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(7, 'E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S', NULL, '2026-04-20 08:56:23', NULL, NULL),
(8, 'E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro', NULL, '2026-04-20 08:56:23', NULL, NULL),
(9, 'M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6', 'uploads/models/model_9_m6_k6_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(10, 'M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP', 'uploads/models/model_10_m6_np_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(11, 'M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L', 'uploads/models/model_11_m6_lithium_np_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(12, 'Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium', 'uploads/models/model_12_premium_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(13, 'W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike', 'uploads/models/model_13_w_bike_h2_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(14, 'SP12', 'Super Star 70', 'Electric Bike', '123', 'uploads/bike_6a0310a76dc22.webp', '2026-05-07 06:34:51', '200', '120');

-- --------------------------------------------------------

--
-- Table structure for table `money_destinations`
--

CREATE TABLE `money_destinations` (
  `id` int NOT NULL,
  `type` enum('bank','person','wallet') NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text,
  `account_title` varchar(255) DEFAULT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `money_destinations`
--

INSERT INTO `money_destinations` (`id`, `type`, `name`, `details`, `account_title`, `account_no`, `branch`, `opening_balance`, `contact_person`, `contact_phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'bank', 'HBL - Habib Bank', 'Main Branch Account', 'Yasin Ullah', '003231665656555', 'Bannu Branch', 0.00, 'Khan', '03313626566', 1, '2026-05-16 12:10:44', '2026-07-07 10:31:07'),
(2, 'bank', 'MCB - Muslim Commercial Bank', 'Business Account', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(3, 'bank', 'UBL - United Bank', 'Savings Account', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(4, 'person', 'Owner / Proprietor', 'Main business owner', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(5, 'person', 'Partner', 'Business partner', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(6, 'person', 'Manager', 'Shop manager', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(7, 'wallet', 'JazzCash', 'Mobile wallet', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(8, 'wallet', 'Easypaisa', 'Mobile wallet', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44'),
(9, 'wallet', 'Cash Drawer', 'Shop cash register', NULL, NULL, NULL, 0.00, NULL, NULL, 1, '2026-05-16 12:10:44', '2026-05-16 12:10:44');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_type` enum('cash','cheque','bank_transfer','online','other') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `status` enum('pending','cleared','bounced','cancelled') DEFAULT 'pending',
  `transaction_type` enum('purchase','sale','installment','expense_payment','supplier_payment','customer_refund','customer_advance','supplier_refund') NOT NULL,
  `reference_id` int DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_date`, `payment_type`, `amount`, `cheque_number`, `bank_name`, `cheque_date`, `status`, `transaction_type`, `reference_id`, `party_name`, `notes`, `created_at`) VALUES
(1, '2026-04-26', 'cheque', 150000.00, NULL, NULL, NULL, 'cleared', 'sale', 28, 'Yasin Ullah', '', '2026-04-26 10:17:45'),
(2, '2026-04-28', 'cash', 20000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 10, '0', '', '2026-04-28 04:24:26'),
(3, '2026-04-28', 'cash', 40000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 11, '0', '', '2026-04-28 04:29:37'),
(4, '2026-04-28', 'cash', 90000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 12, '0', 'new', '2026-04-28 04:33:26'),
(5, '2026-04-28', 'cheque', 90000.00, 'nw1023900', '0', '2026-04-28', 'pending', 'supplier_payment', 13, '0', '', '2026-04-28 04:36:57'),
(6, '2026-04-28', 'cash', 110000.00, '', '0', NULL, 'pending', 'sale', 36, '0', 'Down Payment for Chassis: NW-21233132', '2026-04-28 04:40:25'),
(7, '2026-04-28', 'cash', 110000.00, '', '0', NULL, 'pending', 'sale', 35, '0', 'Down Payment for Chassis: NW-21233213', '2026-04-28 04:43:01'),
(8, '2026-04-28', 'cash', 220000.00, '', '0', NULL, 'pending', 'sale', 30, '0', 'Down Payment for Chassis: NW-212331', '2026-04-28 04:50:16'),
(9, '2026-04-28', 'cash', 30000.00, '', '0', NULL, 'pending', 'sale', 33, '0', 'Down Payment for Chassis: NW-2123353', '2026-04-28 05:08:07'),
(10, '2026-04-28', 'cash', 60000.00, '', '0', NULL, 'pending', 'sale', 34, '0', 'Down Payment for Chassis: NW-212331aa', '2026-04-28 05:09:01'),
(11, '2026-04-28', 'cash', 30000.00, NULL, NULL, NULL, 'pending', 'sale', NULL, 'Ahmed Ali', 'baqaya', '2026-04-28 05:21:39'),
(12, '2026-04-28', 'cash', 600.00, NULL, NULL, NULL, 'pending', 'sale', NULL, 'Ahmed Ali', '', '2026-04-28 05:25:33'),
(13, '2026-04-28', 'cash', 130000.00, NULL, NULL, NULL, 'pending', 'sale', 3, 'Yasin Ullah', 'Sale from Quotation #1', '2026-04-28 09:37:06'),
(22, '2026-04-28', 'cash', 22000.00, '', '', NULL, 'pending', 'sale', 36, 'Shams Uddin', 'Down Payment for Chassis: NW-21233132', '2026-04-28 10:24:33'),
(23, '2026-04-28', 'cash', 33000.00, '', '', NULL, 'pending', 'installment', 7, 'Shams Uddin', 'Installment payment for Chassis NW-21233132 (ID: 7)', '2026-04-28 10:24:51'),
(24, '2026-04-28', 'cash', 42000.00, '', '', NULL, 'pending', 'installment', 8, 'Shams Uddin', 'Installment payment for Chassis NW-21233132 (ID: 8)', '2026-04-28 10:25:07'),
(25, '2026-05-01', 'cash', 290000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 14, 'Yasin Ullah', '', '2026-05-01 06:59:16'),
(26, '2026-05-01', 'cash', 20000.00, '', '', NULL, 'pending', 'sale', 37, 'Yasin Ullah', 'Down Payment for Chassis: NW-212335123', '2026-05-01 07:02:42'),
(27, '2026-05-01', 'cash', 35666.67, '', '', NULL, 'pending', 'installment', 13, 'Yasin Ullah', 'Installment payment for Chassis NW-212335123 (ID: 13)', '2026-05-01 07:03:28'),
(28, '2026-05-02', 'cash', 230000.00, '', '', NULL, 'pending', 'sale', 32, 'Ahmed Ali', 'Down Payment for Chassis: NW-212331a', '2026-05-01 13:22:14'),
(29, '2026-05-06', 'cash', 210000.00, '', '', NULL, 'pending', 'sale', 1, 'Walk-in Customer', 'Down Payment for Chassis: LY05G48270002304', '2026-05-06 16:38:41'),
(30, '2026-05-06', 'cash', 220000.00, '', '', NULL, 'pending', 'sale', 38, 'Walk-in Customer', 'Down Payment for Chassis: T929283007', '2026-05-06 16:51:52'),
(31, '2026-05-06', 'online', 200000.00, '', '', NULL, 'pending', 'sale', 10, 'Walk-in Customer', 'Down Payment for Chassis: TH12G72260006004', '2026-05-06 16:54:50'),
(32, '2026-05-07', 'cash', 0.00, '', '', NULL, 'pending', 'customer_refund', 10, 'Unknown Customer', '30000', '2026-05-06 16:57:35'),
(33, '2026-05-07', 'cash', 500000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 16, 'Khan Gull', '', '2026-05-07 06:41:42'),
(34, '2026-05-07', 'cash', 320000.00, '', '', NULL, 'pending', 'sale', 40, 'Muhammad Usman', 'Down Payment for Chassis: qrqwer', '2026-05-07 06:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int NOT NULL,
  `order_date` date DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `total_units` int DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `order_date`, `supplier_id`, `total_units`, `total_amount`, `notes`, `created_at`) VALUES
(1, '2026-02-03', 1, 14, 0.00, 'First Order', '2026-04-20 09:01:57'),
(2, '2026-02-27', 1, 2, 0.00, 'Second Order', '2026-04-20 09:01:57'),
(3, '2026-03-12', 1, 5, 0.00, 'Third Order', '2026-04-20 09:01:57'),
(4, '2026-03-18', 1, 6, 0.00, 'Fourth Order', '2026-04-20 09:01:57'),
(5, '2026-04-26', 1, 1, 0.00, '', '2026-04-26 10:13:52'),
(6, '2026-04-27', 1, 1, 0.00, '', '2026-04-27 10:25:09'),
(7, '2026-04-27', 1, 1, 0.00, '', '2026-04-27 10:25:19'),
(8, '2026-04-27', 1, 1, 0.00, '', '2026-04-27 10:29:22'),
(9, '2026-04-27', 1, 1, 0.00, '', '2026-04-27 10:29:45'),
(10, '2026-04-28', 5, 1, 20000.00, '', '2026-04-28 04:24:26'),
(11, '2026-04-28', 2, 1, 40000.00, '', '2026-04-28 04:29:37'),
(12, '2026-04-28', 2, 1, 90000.00, 'new', '2026-04-28 04:33:26'),
(13, '2026-04-28', 3, 1, 90000.00, '', '2026-04-28 04:36:57'),
(14, '2026-05-01', 2, 1, 290000.00, '', '2026-05-01 06:59:16'),
(15, '2026-05-06', 4, 1, 200000.00, '', '2026-05-06 16:50:26'),
(16, '2026-05-07', 6, 2, 500000.00, '', '2026-05-07 06:41:42');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int NOT NULL,
  `quote_date` date NOT NULL,
  `customer_id` int DEFAULT NULL,
  `bike_id` int DEFAULT NULL,
  `accessories_json` text,
  `quoted_price` decimal(15,2) NOT NULL,
  `is_installment` tinyint(1) DEFAULT '0',
  `down_payment` decimal(15,2) DEFAULT '0.00',
  `total_installments` int DEFAULT '0',
  `installment_amount` decimal(15,2) DEFAULT '0.00',
  `valid_until` date DEFAULT NULL,
  `status` enum('pending','accepted','rejected','converted') DEFAULT 'pending',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_date`, `customer_id`, `bike_id`, `accessories_json`, `quoted_price`, `is_installment`, `down_payment`, `total_installments`, `installment_amount`, `valid_until`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-04-28', 5, 3, '[]', 130000.00, 0, 0.00, 0, 0.00, '2026-05-05', 'converted', '0', 1, '2026-04-28 09:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` int NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_id` int DEFAULT NULL,
  `details` text,
  `status` enum('pending','sent','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quote_requests`
--

INSERT INTO `quote_requests` (`id`, `customer_name`, `customer_phone`, `bike_id`, `details`, `status`, `created_at`) VALUES
(1, 'Yasin Ullah', '03139842219', 39, 'All', 'sent', '2026-05-12 11:05:45'),
(2, 'Yasin Ullah', '03139842219', 2, 'new', 'sent', '2026-05-12 11:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Administrator', 'Full access', '2026-04-20 10:50:00'),
(2, 'Manager', 'Limited access', '2026-04-20 10:50:00'),
(3, 'income and expenses guy', 'only handle income and expenses', '2026-04-20 11:19:38'),
(4, 'income and expense', '', '2026-05-01 07:06:02'),
(5, 'Sales man', '', '2026-05-07 07:02:14');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int NOT NULL,
  `role_id` int NOT NULL,
  `page` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT '0',
  `can_add` tinyint(1) DEFAULT '0',
  `can_edit` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `page`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES
(1, 3, 'dashboard', 0, 0, 0, 0),
(2, 3, 'inventory', 0, 0, 0, 0),
(3, 3, 'purchase', 0, 0, 0, 0),
(4, 3, 'sale', 0, 0, 0, 0),
(5, 3, 'customers', 0, 0, 0, 0),
(6, 3, 'suppliers', 0, 0, 0, 0),
(7, 3, 'models', 0, 0, 0, 0),
(8, 3, 'reports', 0, 0, 0, 0),
(9, 3, 'returns', 0, 0, 0, 0),
(10, 3, 'cheques', 0, 0, 0, 0),
(11, 3, 'settings', 0, 0, 0, 0),
(12, 3, 'roles', 0, 0, 0, 0),
(13, 3, 'users', 0, 0, 0, 0),
(14, 3, 'income_expense', 1, 1, 1, 1),
(15, 4, 'dashboard', 0, 0, 0, 0),
(16, 4, 'inventory', 0, 0, 0, 0),
(17, 4, 'purchase', 0, 0, 0, 0),
(18, 4, 'sale', 0, 0, 0, 0),
(19, 4, 'customers', 0, 0, 0, 0),
(20, 4, 'suppliers', 0, 0, 0, 0),
(21, 4, 'models', 0, 0, 0, 0),
(22, 4, 'reports', 0, 0, 0, 0),
(23, 4, 'returns', 0, 0, 0, 0),
(24, 4, 'payments', 0, 0, 0, 0),
(25, 4, 'settings', 0, 0, 0, 0),
(26, 4, 'roles', 0, 0, 0, 0),
(27, 4, 'users', 0, 0, 0, 0),
(28, 4, 'income_expense', 1, 1, 1, 1),
(29, 4, 'accessories', 0, 0, 0, 0),
(30, 4, 'quotations', 0, 0, 0, 0),
(31, 4, 'installments', 0, 0, 0, 0),
(49, 5, 'dashboard', 0, 0, 0, 0),
(50, 5, 'inventory', 0, 0, 0, 0),
(51, 5, 'purchase', 0, 0, 0, 0),
(52, 5, 'sale', 1, 1, 1, 1),
(53, 5, 'customers', 0, 0, 0, 0),
(54, 5, 'suppliers', 0, 0, 0, 0),
(55, 5, 'models', 0, 0, 0, 0),
(56, 5, 'reports', 0, 0, 0, 0),
(57, 5, 'returns', 0, 0, 0, 0),
(58, 5, 'payments', 0, 0, 0, 0),
(59, 5, 'settings', 0, 0, 0, 0),
(60, 5, 'roles', 0, 0, 0, 0),
(61, 5, 'users', 0, 0, 0, 0),
(62, 5, 'income_expense', 0, 0, 0, 0),
(63, 5, 'accessories', 0, 0, 0, 0),
(64, 5, 'quotations', 0, 0, 0, 0),
(65, 5, 'installments', 0, 0, 0, 0),
(84, 2, 'dashboard', 1, 0, 0, 0),
(88, 1, 'dashboard', 1, 1, 1, 1),
(89, 1, 'inventory', 1, 1, 1, 1),
(90, 1, 'purchase', 1, 1, 1, 1),
(91, 1, 'sale', 1, 1, 1, 1),
(92, 1, 'customers', 1, 1, 1, 1),
(93, 1, 'suppliers', 1, 1, 1, 1),
(94, 1, 'models', 1, 1, 1, 1),
(95, 1, 'reports', 1, 1, 1, 1),
(96, 1, 'returns', 1, 1, 1, 1),
(97, 1, 'payments', 1, 1, 1, 1),
(98, 1, 'settings', 1, 1, 1, 1),
(99, 1, 'roles', 1, 1, 1, 1),
(100, 1, 'users', 1, 1, 1, 1),
(101, 1, 'income_expense', 1, 1, 1, 1),
(102, 1, 'accessories', 1, 1, 1, 1),
(103, 1, 'quotations', 1, 1, 1, 1),
(104, 1, 'installments', 1, 1, 1, 1),
(105, 1, 'money_destinations', 0, 0, 0, 0),
(106, 1, 'money_tracking', 0, 0, 0, 0),
(107, 1, 'bank_deposits', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sale_accessories`
--

CREATE TABLE `sale_accessories` (
  `id` int NOT NULL,
  `bike_id` int NOT NULL,
  `accessory_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `final_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_accessories`
--

INSERT INTO `sale_accessories` (`id`, `bike_id`, `accessory_id`, `quantity`, `unit_price`, `discount_amount`, `final_price`) VALUES
(1, 37, 1, 1, 0.00, 0.00, 0.00),
(2, 40, 3, 1, 1200.00, 1200.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_money_allocations`
--

CREATE TABLE `sale_money_allocations` (
  `id` int NOT NULL,
  `bike_id` int NOT NULL,
  `destination_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `allocation_date` date NOT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_money_allocations`
--

INSERT INTO `sale_money_allocations` (`id`, `bike_id`, `destination_id`, `amount`, `allocation_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 40, 1, 320000.00, '2026-07-07', '', 1, '2026-07-07 10:48:23', '2026-07-07 10:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'company_name', 'BNI Enterprises'),
(2, 'branch_name', 'Dera (Ahmed Metro)'),
(3, 'tax_rate', '0.1'),
(4, 'currency', 'Rs.'),
(5, 'tax_on', 'purchase_price'),
(6, 'theme', 'light'),
(7, 'admin_password', '$2y$10$8348koW6nh9Q5tigyeHj7.P7PMnTxPbWb7hM8P1mtS.k8sfUsguU.'),
(8, 'show_purchase_on_invoice', '0'),
(17, 'session_timeout_idle', '2400'),
(18, 'session_timeout_absolute', '28800'),
(19, 'landing_hero_title', 'Experience the Future of Mobility'),
(20, 'landing_hero_subtitle', 'Premium Electric Bikes for a Greener Tomorrow'),
(21, 'company_address', '123 Bike Street, Dera Ghazi Khan, Punjab, Pakistan'),
(22, 'company_map_iframe', 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d10500.14144614541!2d73.07594429999999!3d33.6494707!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2s!4v1778569478700!5m2!1sen!2s'),
(23, 'company_whatsapp', '923000000000, 923309313131'),
(24, 'company_email', 'info@bnienterprises.com'),
(25, 'social_facebook', 'https://facebook.com'),
(26, 'social_instagram', 'https://instagram.com'),
(27, 'social_twitter', 'https://twitter.com'),
(28, 'vision_statement', 'To be the leading provider of eco-friendly transportation in the region.'),
(29, 'mission_statement', 'Providing high-quality electric bikes and exceptional service to our customers.');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact`, `address`, `created_at`) VALUES
(1, 'Default Supplier', '0300-0000000', 'Pakistan', '2026-04-20 08:56:23'),
(2, 'Yasin Ullah', '03139842219', 'New bannu wala', '2026-04-28 04:12:55'),
(3, 'Shams Uddin', '0322213222', 'New Bannu', '2026-04-28 04:16:21'),
(4, 'Noor udin', '0322213222', 'new', '2026-04-28 04:17:05'),
(5, 'Nasim', '001239919023', 'newd', '2026-04-28 04:19:27'),
(6, 'Khan Gull', '0322213222', 'domel bannu', '2026-05-07 06:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$KdSnze47ye.inqU8FvyrrO.ugHe3xSXPFiJJ1PsntZN9KJTND5Fa6', 'System Administrator', 1, 1, '2026-04-20 10:50:00'),
(2, 'admin1', '$2y$10$Yk7.BNVuTU6lpNYVo10UfuQ0cprC2y84gMMGj6ei0T8q0tLXhHyW2', 'Yasin Ullah', 3, 1, '2026-04-20 11:20:03'),
(3, 'admin3', '$2y$10$N1XGevQPOl7HgShVSSnsuuDXFX2gW/MAxoIbEUgpUG.jPIWWN.8sG', 'Hussain', 4, 1, '2026-05-01 07:06:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accessories`
--
ALTER TABLE `accessories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `bank_deposits`
--
ALTER TABLE `bank_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chassis_number` (`chassis_number`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_selling_date` (`selling_date`),
  ADD KEY `idx_model_id` (`model_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_bikes_status` (`status`),
  ADD KEY `idx_bikes_model` (`model_id`),
  ADD KEY `idx_bikes_customer` (`customer_id`);

--
-- Indexes for table `bike_requests`
--
ALTER TABLE `bike_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cheque_register`
--
ALTER TABLE `cheque_register`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposit_allocations`
--
ALTER TABLE `deposit_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deposit_id` (`deposit_id`),
  ADD KEY `allocation_id` (`allocation_id`),
  ADD KEY `bike_id` (`bike_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income_expenses`
--
ALTER TABLE `income_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `installments`
--
ALTER TABLE `installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_installments_customer` (`customer_id`);

--
-- Indexes for table `leadership`
--
ALTER TABLE `leadership`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_party` (`party_type`,`party_id`);

--
-- Indexes for table `models`
--
ALTER TABLE `models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `money_destinations`
--
ALTER TABLE `money_destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_payments_ref` (`transaction_type`,`reference_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bike_id` (`bike_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_page` (`role_id`,`page`);

--
-- Indexes for table `sale_accessories`
--
ALTER TABLE `sale_accessories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accessory_id` (`accessory_id`),
  ADD KEY `idx_sa_bike` (`bike_id`);

--
-- Indexes for table `sale_money_allocations`
--
ALTER TABLE `sale_money_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accessories`
--
ALTER TABLE `accessories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bank_deposits`
--
ALTER TABLE `bank_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `bike_requests`
--
ALTER TABLE `bike_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cheque_register`
--
ALTER TABLE `cheque_register`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deposit_allocations`
--
ALTER TABLE `deposit_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `income_expenses`
--
ALTER TABLE `income_expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `leadership`
--
ALTER TABLE `leadership`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `money_destinations`
--
ALTER TABLE `money_destinations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `sale_accessories`
--
ALTER TABLE `sale_accessories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sale_money_allocations`
--
ALTER TABLE `sale_money_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bank_deposits`
--
ALTER TABLE `bank_deposits`
  ADD CONSTRAINT `bank_deposits_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `bank_deposits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bikes`
--
ALTER TABLE `bikes`
  ADD CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bikes_ibfk_3` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deposit_allocations`
--
ALTER TABLE `deposit_allocations`
  ADD CONSTRAINT `deposit_allocations_ibfk_1` FOREIGN KEY (`deposit_id`) REFERENCES `bank_deposits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deposit_allocations_ibfk_2` FOREIGN KEY (`allocation_id`) REFERENCES `sale_money_allocations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deposit_allocations_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `income_expenses`
--
ALTER TABLE `income_expenses`
  ADD CONSTRAINT `income_expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `installments`
--
ALTER TABLE `installments`
  ADD CONSTRAINT `installments_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `installments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `installments_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD CONSTRAINT `quote_requests_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_accessories`
--
ALTER TABLE `sale_accessories`
  ADD CONSTRAINT `sale_accessories_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_accessories_ibfk_2` FOREIGN KEY (`accessory_id`) REFERENCES `accessories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sale_money_allocations`
--
ALTER TABLE `sale_money_allocations`
  ADD CONSTRAINT `sale_money_allocations_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_money_allocations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `sale_money_allocations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
