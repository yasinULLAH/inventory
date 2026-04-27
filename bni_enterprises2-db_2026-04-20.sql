-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 27, 2026 at 04:16 PM
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
  `status` enum('in_stock','sold','returned','reserved') DEFAULT 'in_stock',
  `return_date` date DEFAULT NULL,
  `return_amount` decimal(15,2) DEFAULT NULL,
  `return_notes` text,
  `safeguard_notes` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `purchase_order_id`, `order_date`, `inventory_date`, `chassis_number`, `motor_number`, `model_id`, `color`, `purchase_price`, `selling_price`, `selling_date`, `customer_id`, `tax_amount`, `margin`, `status`, `return_date`, `return_amount`, `return_notes`, `safeguard_notes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-02-03', '2026-02-05', 'LY05G48270002304', '*XRLY48052125D0002228*', 1, 'Black', 125225.00, NULL, NULL, NULL, 125.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(2, 1, '2026-02-03', '2026-02-05', 'LY05G48270002202', '*XRLY48052125D0002322*', 1, 'Grey', 125225.00, NULL, NULL, NULL, 125.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(3, 1, '2026-02-03', '2026-02-05', 'DD35G48130001177', '*48V350WA8T454708922*', 13, 'Black', 94595.00, NULL, NULL, NULL, 95.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(4, 1, '2026-02-03', '2026-02-05', 'M615G72380002665', 'A9A756800994', 9, 'Silver', 220721.00, 242000.00, NULL, NULL, 221.00, 0.00, 'returned', NULL, NULL, NULL, NULL, 'Returned on 200,000 Cheque to be issued.', '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(5, 1, '2026-02-03', '2026-02-05', 'T910G72260006966', '*XR9S72102825N0007369*', 2, 'Red', 161261.00, 179000.00, '2026-03-05', NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(6, 1, '2026-02-03', '2026-02-05', 'T910G72260007041', '*XR9S72102825N0007701*', 2, 'Black', 161261.00, 179000.00, NULL, NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(7, 1, '2026-02-03', '2026-02-05', 'T910G72260006884', '*XR9S72102825N0007393*', 2, 'Grey', 161261.00, 179000.00, '2026-03-02', NULL, 161.00, 17578.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(8, 1, '2026-02-03', '2026-02-05', 'E820G72380002293', '*PJE872203525N0002160*', 7, 'Grey', 251351.00, 279000.00, '2026-02-23', NULL, 251.00, 27398.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(9, 1, '2026-02-03', '2026-02-05', 'TH12G72260005515', 'AIMTP721240259005364', 5, 'Grey', 179279.00, 199000.00, '2026-02-22', NULL, 179.00, 19542.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(10, 1, '2026-02-03', '2026-02-05', 'TH12G72260006004', 'AIMTP721240259006297', 5, 'Black', 179279.00, NULL, NULL, NULL, 179.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(11, 1, '2026-02-03', '2026-02-05', 'T910L72300000632', '*XR9S7210282500000640*', 3, 'Silver', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(12, 1, '2026-02-03', '2026-02-05', 'T910L72300000916', '*XR9S7210282500000927*', 3, 'Black', 193694.00, 234000.00, '2026-03-07', NULL, 194.00, 40112.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(13, 1, '2026-02-03', '2026-02-05', 'TH12L72300000445', 'AIMTP72124025N001005', 6, 'Black', 211712.00, NULL, NULL, NULL, 212.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(14, 1, '2026-02-03', '2026-02-05', 'TH12L72300000416', 'AIMTP72124025N001176', 6, 'Grey', 211712.00, 246000.00, '2026-03-18', NULL, 212.00, 34076.00, 'sold', NULL, NULL, NULL, NULL, '(2,470,276) Received', '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(15, 2, '2026-02-27', '2026-03-12', 'M615L72300006176', 'XRM672153025D0007536', 11, 'Unknown', 254955.00, 285000.00, '2026-03-12', NULL, 285.00, 29760.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(16, 2, '2026-02-27', '2026-03-12', 'M615L72300006278', 'XRM672153025D0007499', 11, 'Unknown', 254955.00, 283000.00, '2026-03-12', NULL, 285.00, 27760.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(17, 3, '2026-03-12', '2026-03-16', 'T910G72260008882', '*XR9S72102825D0007890*', 4, 'Red', 238739.00, 179000.00, NULL, NULL, 239.00, -59978.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(18, 3, '2026-03-12', '2026-03-16', 'T910G72260008478', '*XR9S72102825D0007855*', 4, 'Black', 238739.00, NULL, NULL, NULL, 239.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(19, 3, '2026-03-12', '2026-03-16', 'T910G72260008679', '*XR9S72102825D0007954*', 2, 'Grey', 161261.00, 179000.00, '2026-03-18', NULL, 179.00, 17560.00, 'sold', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(20, 3, '2026-03-12', '2026-03-16', 'TH12G72260006279', 'AIMTP721240259006047', 5, 'Unknown', 179279.00, NULL, NULL, NULL, 179.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(21, 3, '2026-03-12', '2026-03-16', 'TH12G72260006236', 'AIMTP721240259006039', 5, 'Unknown', 179279.00, NULL, NULL, NULL, 179.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, '(997,297) Receiving', '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(22, 4, '2026-03-18', '2026-03-27', 'E820G72380000466', '12ZW7271327YE*CERR116670C*', 8, 'Blue', 247748.00, NULL, NULL, NULL, 247.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(23, 4, '2026-03-18', '2026-03-27', 'P308L72300000159', 'PHPM7208352610000422', 12, 'Unknown', 234234.00, NULL, NULL, NULL, 234.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(24, 4, '2026-03-18', '2026-03-27', 'E810G72380000595', '*10ZW7273316YECKTS0000107*', 7, 'Grey', 251351.00, NULL, NULL, NULL, 251.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(25, 4, '2026-03-18', '2026-03-27', 'T910G72260008720', '*XR9S72102825D0007987*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(26, 4, '2026-03-18', '2026-03-27', 'T910G72260008894', '*XR9S72102825D0008251*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, 'Diff ledger= (70,137)+ new delivery', '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(27, 4, '2026-03-18', '2026-03-27', 'T910G72260008737', '*XR9S72102825D0008003*', 3, 'Unknown', 193694.00, NULL, NULL, NULL, 194.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, NULL, '2026-04-20 09:01:57', '2026-04-20 09:01:57'),
(28, 5, '2026-04-26', '2026-04-26', 'NW-21233', 'MT-002', 7, 'Red', 120000.00, 150000.00, '2026-04-26', 5, 120.00, 29880.00, 'returned', '2026-04-26', 190000.00, 'Said not appliable', 'must be charged 100 percent for the first time use', '', '2026-04-26 10:13:52', '2026-04-26 10:21:35'),
(30, 7, '2026-04-27', '2026-04-27', 'NW-212331', 'MT-002', 7, 'Red', 190000.00, NULL, NULL, NULL, 190.00, 0.00, 'in_stock', NULL, NULL, NULL, 'must be charged 100 percent for the first time use', '', '2026-04-27 10:25:19', '2026-04-27 10:25:19'),
(32, 9, '2026-04-27', '2026-04-27', 'NW-212331a', 'MT-002', 7, 'Red', 190000.00, NULL, NULL, NULL, 190.00, 0.00, 'in_stock', NULL, NULL, NULL, 'must be charged 100 percent for the first time use', '', '2026-04-27 10:29:45', '2026-04-27 10:29:45');

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
(5, 'Yasin Ullah', '03139842219', '11102-0356023-4', 1, 'Post Office Domel District Bannu', '2026-04-26 10:16:43');

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
(2, '2026-04-20', 'income', 'Commission', 1500.00, 'cash', 'new', '', 1, '2026-04-20 11:29:39');

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
(2, '2026-04-26', 'debit', 190000.00, 'customer', 0, 'Return for Bike ID: 28', 'return', 28, 190000.00, '2026-04-26 10:21:35');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`id`, `model_code`, `model_name`, `category`, `short_code`, `created_at`) VALUES
(1, 'LY SI', 'LY SI Electric Bike', 'Electric Bike', 'LY', '2026-04-20 08:56:23'),
(2, 'T9 Sports', 'T9 Sports Electric Bike', 'Electric Bike', 'T9', '2026-04-20 08:56:23'),
(3, 'T9 Sports LFP', 'T9 Sports LFP Electric Bike', 'Electric Bike', 'T9 LFP', '2026-04-20 08:56:23'),
(4, 'T9 Eco', 'T9 Eco Electric Bike', 'Electric Bike', 'T9 Eco', '2026-04-20 08:56:23'),
(5, 'Thrill Pro', 'Thrill Pro Electric Bike', 'Electric Bike', 'TP', '2026-04-20 08:56:23'),
(6, 'Thrill Pro LFP', 'Thrill Pro LFP Electric Bike', 'Electric Bike', 'TP LFP', '2026-04-20 08:56:23'),
(7, 'E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S', '2026-04-20 08:56:23'),
(8, 'E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro', '2026-04-20 08:56:23'),
(9, 'M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6', '2026-04-20 08:56:23'),
(10, 'M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP', '2026-04-20 08:56:23'),
(11, 'M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L', '2026-04-20 08:56:23'),
(12, 'Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium', '2026-04-20 08:56:23'),
(13, 'W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike', '2026-04-20 08:56:23');

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
  `transaction_type` enum('purchase','sale','installment','expense_payment','supplier_payment','customer_refund') NOT NULL,
  `reference_id` int DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_date`, `payment_type`, `amount`, `cheque_number`, `bank_name`, `cheque_date`, `status`, `transaction_type`, `reference_id`, `party_name`, `notes`, `created_at`) VALUES
(1, '2026-04-26', 'cheque', 150000.00, NULL, NULL, NULL, 'pending', 'sale', 28, 'Yasin Ullah', '', '2026-04-26 10:17:45');

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
(9, '2026-04-27', 1, 1, 0.00, '', '2026-04-27 10:29:45');

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
  `valid_until` date DEFAULT NULL,
  `status` enum('pending','accepted','rejected','converted') DEFAULT 'pending',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(3, 'income and expenses guy', 'only handle income and expenses', '2026-04-20 11:19:38');

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
(14, 3, 'income_expense', 1, 1, 1, 1);

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
(8, 'show_purchase_on_invoice', '0');

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
(1, 'Default Supplier', '0300-0000000', 'Pakistan', '2026-04-20 08:56:23');

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
(2, 'admin1', '$2y$10$Yk7.BNVuTU6lpNYVo10UfuQ0cprC2y84gMMGj6ei0T8q0tLXhHyW2', 'Yasin Ullah', 3, 1, '2026-04-20 11:20:03');

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
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chassis_number` (`chassis_number`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

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
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `models`
--
ALTER TABLE `models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `accessory_id` (`accessory_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `cheque_register`
--
ALTER TABLE `cheque_register`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `income_expenses`
--
ALTER TABLE `income_expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sale_accessories`
--
ALTER TABLE `sale_accessories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bikes`
--
ALTER TABLE `bikes`
  ADD CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bikes_ibfk_3` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
