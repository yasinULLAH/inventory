-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 22, 2026 at 06:15 AM
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
-- Database: `inventory_ms2`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 07:17:42'),
(2, 1, 'CHANGE_PASSWORD', 'users', 'User changed their password', '::1', '2026-04-20 07:18:00'),
(3, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 15:01:36'),
(4, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 15:22:10'),
(5, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260420-001', '::1', '2026-04-20 15:56:45'),
(6, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 16:56:17'),
(7, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260420-002', '::1', '2026-04-20 18:09:01'),
(8, 1, 'UPDATE', 'users', 'Updated user: manager1', '::1', '2026-04-20 18:23:51'),
(9, 1, 'LOGOUT', 'auth', 'User logged out', '::1', '2026-04-20 18:31:05'),
(10, 2, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 18:31:11'),
(11, 2, 'LOGOUT', 'auth', 'User logged out', '::1', '2026-04-20 18:44:35'),
(12, 2, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 18:44:39'),
(13, 2, 'LOGOUT', 'auth', 'User logged out', '::1', '2026-04-20 18:50:29'),
(14, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-20 18:50:55'),
(15, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-21 04:59:53'),
(16, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-21 06:06:40'),
(17, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-21 10:15:52'),
(18, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-001', '::1', '2026-04-21 10:41:53'),
(19, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-002', '::1', '2026-04-21 10:43:20'),
(20, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-003', '::1', '2026-04-21 11:18:41'),
(21, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-004', '::1', '2026-04-21 11:47:43'),
(22, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-005', '::1', '2026-04-21 11:47:58'),
(23, 1, 'UPDATE', 'settings', 'Updated system settings', '::1', '2026-04-21 11:50:22'),
(24, 1, 'CREATE', 'pos', 'Created POS invoice INV-20260421-006', '::1', '2026-04-21 11:58:04'),
(25, 1, 'LOGIN', 'auth', 'User logged in from ::1', '::1', '2026-04-21 15:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int UNSIGNED NOT NULL,
  `platform` enum('shopify','woocommerce','amazon') NOT NULL,
  `name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', NULL, 'Electronic devices and components', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(2, 'Furniture', NULL, 'Office and home furniture', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(3, 'Stationery', NULL, 'Office supplies and stationery', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(4, 'Food & Beverage', NULL, 'Food items and beverages', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(5, 'Laptops & Computers', 1, 'Laptops, desktops, accessories', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(6, 'Mobile Phones', 1, 'Smartphones and accessories', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(7, 'Office Chairs', 2, 'Ergonomic and standard office chairs', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(8, 'Pens & Pencils', 3, 'Writing instruments', 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `name`, `description`, `status`, `created_by`, `created_at`) VALUES
(1, 'best customer', '', 'active', 1, '2026-04-21 05:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `collection_items`
--

CREATE TABLE `collection_items` (
  `collection_id` int UNSIGNED NOT NULL,
  `item_type` enum('product','customer','supplier') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `collection_items`
--

INSERT INTO `collection_items` (`collection_id`, `item_type`, `item_id`, `added_at`) VALUES
(1, 'customer', 1, '2026-04-21 05:19:49'),
(1, 'customer', 2, '2026-04-21 05:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `composite_items`
--

CREATE TABLE `composite_items` (
  `parent_id` int UNSIGNED NOT NULL,
  `child_id` int UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `code` char(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(5) NOT NULL,
  `rate_to_base` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `is_base` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `customer_type` enum('walk-in','regular','wholesale') NOT NULL DEFAULT 'walk-in',
  `credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `city`, `country`, `customer_type`, `credit_limit`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Hassan Enterprises', 'contact@hassanent.com', '+92-51-4441234', 'Blue Area, Office 12', 'Islamabad', 'Pakistan', 'wholesale', 500000.00, 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 18:19:35'),
(2, 'Raza & Sons Trading', 'raza@razasons.com', '+92-300-2223456', 'Saddar Bazaar, Shop 45', 'Rawalpindi', 'Pakistan', 'regular', 100000.00, 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(3, 'Nadia Boutique', 'nadia@boutique.pk', '+92-321-8889012', 'F-7 Markaz, Shop 8', 'Islamabad', 'Pakistan', 'regular', 50000.00, 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(4, 'Walk-in Customer', 'walkin@inventorypro.com', '+92-000-0000000', 'N/A', 'N/A', 'Pakistan', 'walk-in', 0.00, 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledgers`
--

CREATE TABLE `customer_ledgers` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_type` enum('opening','invoice','payment','return','credit_note') NOT NULL,
  `reference_id` int UNSIGNED DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_ledgers`
--

INSERT INTO `customer_ledgers` (`id`, `customer_id`, `transaction_date`, `reference_type`, `reference_id`, `debit`, `credit`, `balance`, `notes`) VALUES
(1, 1, '2026-04-20', 'invoice', 2, 643.50, 500.00, 143.50, 'POS Sale #INV-20260420-002'),
(2, 1, '2026-04-20', 'payment', 1, 0.00, 143.50, 0.00, 'Payment for Invoice #INV-20260420-002'),
(3, 4, '2026-04-21', 'invoice', 3, 643.50, 643.50, 0.00, 'POS Sale #INV-20260421-001'),
(4, 4, '2026-04-21', 'invoice', 4, 643.50, 643.50, 0.00, 'POS Sale #INV-20260421-002'),
(5, 4, '2026-04-21', 'invoice', 5, 29248.83, 29248.83, 0.00, 'POS Sale #INV-20260421-003'),
(6, 4, '2026-04-21', 'invoice', 6, 643.50, 643.50, 0.00, 'POS Sale #INV-20260421-004'),
(7, 4, '2026-04-21', 'invoice', 7, 588.50, 588.50, 0.00, 'POS Sale #INV-20260421-005'),
(8, 4, '2026-04-21', 'invoice', 8, 566.50, 566.50, 0.00, 'POS Sale #INV-20260421-006');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int UNSIGNED NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_categories`
--

CREATE TABLE `finance_categories` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `id` int UNSIGNED NOT NULL,
  `income_date` date NOT NULL,
  `source` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `walkin_name` varchar(150) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `payment_method` enum('cash','card','bank_transfer','credit') NOT NULL DEFAULT 'cash',
  `notes` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `customer_id`, `walkin_name`, `invoice_date`, `due_date`, `subtotal`, `tax_percent`, `discount`, `total_amount`, `payment_status`, `payment_method`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-20260420-001', 1, NULL, '2026-04-20', NULL, 24999.00, 17.00, 0.00, 29248.83, 'paid', 'cash', NULL, 1, '2026-04-20 15:56:45', '2026-04-20 15:56:45'),
(2, 'INV-20260420-002', 1, NULL, '2026-04-20', NULL, 550.00, 17.00, 0.00, 643.50, 'paid', 'cash', NULL, 1, '2026-04-20 18:09:01', '2026-04-20 18:19:35'),
(3, 'INV-20260421-001', 4, NULL, '2026-04-21', NULL, 550.00, 17.00, 0.00, 643.50, 'paid', 'cash', NULL, 1, '2026-04-21 10:41:53', '2026-04-21 10:41:53'),
(4, 'INV-20260421-002', 4, NULL, '2026-04-21', NULL, 550.00, 17.00, 0.00, 643.50, 'paid', 'cash', NULL, 1, '2026-04-21 10:43:20', '2026-04-21 10:43:20'),
(5, 'INV-20260421-003', 4, NULL, '2026-04-21', NULL, 24999.00, 17.00, 0.00, 29248.83, 'paid', 'cash', NULL, 1, '2026-04-21 11:18:41', '2026-04-21 11:18:41'),
(6, 'INV-20260421-004', 4, NULL, '2026-04-21', NULL, 550.00, 17.00, 0.00, 643.50, 'paid', 'cash', NULL, 1, '2026-04-21 11:47:43', '2026-04-21 11:47:43'),
(7, 'INV-20260421-005', 4, NULL, '2026-04-21', NULL, 550.00, 17.00, 55.00, 588.50, 'paid', 'cash', NULL, 1, '2026-04-21 11:47:58', '2026-04-21 11:47:58'),
(8, 'INV-20260421-006', 4, 'Khan Gull', '2026-04-21', NULL, 550.00, 3.00, 0.00, 566.50, 'paid', 'cash', NULL, 1, '2026-04-21 11:58:04', '2026-04-21 11:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount_pct`, `total_price`) VALUES
(1, 1, 3, 1, 24999.00, 0.00, 24999.00),
(2, 2, 4, 1, 550.00, 0.00, 550.00),
(3, 3, 4, 1, 550.00, 0.00, 550.00),
(4, 4, 4, 1, 550.00, 0.00, 550.00),
(5, 5, 3, 1, 24999.00, 0.00, 24999.00),
(6, 6, 4, 1, 550.00, 0.00, 550.00),
(7, 7, 4, 1, 550.00, 0.00, 550.00),
(8, 8, 4, 1, 550.00, 0.00, 550.00);

-- --------------------------------------------------------

--
-- Table structure for table `login_log`
--

CREATE TABLE `login_log` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `status` enum('success','failed','locked') NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_log`
--

INSERT INTO `login_log` (`id`, `username`, `ip_address`, `status`, `user_agent`, `created_at`) VALUES
(1, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 07:17:41'),
(2, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 15:01:36'),
(3, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 15:22:10'),
(4, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 16:56:17'),
(5, 'manager1', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 18:31:11'),
(6, 'manager1', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 18:44:39'),
(7, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 18:50:55'),
(8, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:59:53'),
(9, 'admin', '::1', 'success', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-21 06:06:39'),
(10, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:15:52'),
(11, 'admin', '::1', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 15:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `type` enum('low_stock','overdue_payment','overdue_purchase','system') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `related_id` int UNSIGNED DEFAULT NULL,
  `related_module` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int UNSIGNED NOT NULL,
  `receipt_number` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `supplier_id` int UNSIGNED DEFAULT NULL,
  `invoice_id` int UNSIGNED DEFAULT NULL,
  `po_id` int UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_receipts`
--

INSERT INTO `payment_receipts` (`id`, `receipt_number`, `customer_id`, `supplier_id`, `invoice_id`, `po_id`, `amount`, `payment_date`, `method`, `reference`, `created_by`, `created_at`) VALUES
(1, 'REC-20260420-B08FAF', 1, NULL, 2, NULL, 143.50, '2026-04-20', 'cash', '', 1, '2026-04-20 18:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `module`, `action`, `display_name`) VALUES
(1, 'dashboard', 'view', 'Dashboard View'),
(2, 'pos', 'view', 'Pos View'),
(3, 'pos', 'sell', 'Pos Sell'),
(4, 'products', 'view', 'Products View'),
(5, 'products', 'create', 'Products Create'),
(6, 'products', 'edit', 'Products Edit'),
(7, 'products', 'delete', 'Products Delete'),
(8, 'categories', 'view', 'Categories View'),
(9, 'categories', 'manage', 'Categories Manage'),
(10, 'purchases', 'view', 'Purchases View'),
(11, 'purchases', 'create', 'Purchases Create'),
(12, 'purchases', 'edit', 'Purchases Edit'),
(13, 'sales', 'view', 'Sales View'),
(14, 'sales', 'create', 'Sales Create'),
(15, 'sales', 'edit', 'Sales Edit'),
(16, 'sales', 'return', 'Sales Return'),
(17, 'quotations', 'view', 'Quotations View'),
(18, 'quotations', 'create', 'Quotations Create'),
(19, 'quotations', 'convert', 'Quotations Convert'),
(20, 'customers', 'view', 'Customers View'),
(21, 'customers', 'manage', 'Customers Manage'),
(22, 'suppliers', 'view', 'Suppliers View'),
(23, 'suppliers', 'manage', 'Suppliers Manage'),
(24, 'reports', 'view', 'Reports View'),
(25, 'reports', 'export', 'Reports Export'),
(26, 'expenses', 'view', 'Expenses View'),
(27, 'expenses', 'manage', 'Expenses Manage'),
(28, 'income', 'view', 'Income View'),
(29, 'income', 'manage', 'Income Manage'),
(30, 'users', 'view', 'Users View'),
(31, 'users', 'manage', 'Users Manage'),
(32, 'roles', 'view', 'Roles View'),
(33, 'roles', 'manage', 'Roles Manage'),
(34, 'settings', 'view', 'Settings View'),
(35, 'settings', 'manage', 'Settings Manage'),
(36, 'stock_audit', 'view', 'Stock_audit View'),
(37, 'stock_audit', 'manage', 'Stock_audit Manage'),
(38, 'collections', 'view', 'Collections View'),
(39, 'collections', 'manage', 'Collections Manage');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int UNSIGNED NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `is_variant` tinyint(1) NOT NULL DEFAULT '0',
  `is_composite` tinyint(1) NOT NULL DEFAULT '0',
  `sku` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `category_id` int UNSIGNED DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_id` int UNSIGNED DEFAULT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `min_stock_level` int NOT NULL DEFAULT '5',
  `max_stock_level` int NOT NULL DEFAULT '1000',
  `warehouse_id` int UNSIGNED DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `track_serial` tinyint(1) NOT NULL DEFAULT '0',
  `track_batch` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `parent_id`, `is_variant`, `is_composite`, `sku`, `name`, `description`, `category_id`, `brand`, `unit`, `purchase_price`, `selling_price`, `tax_id`, `current_stock`, `min_stock_level`, `max_stock_level`, `warehouse_id`, `barcode`, `image_url`, `image_path`, `track_serial`, `track_batch`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 0, 0, 'SKU-00001', 'Dell Inspiron 15 Laptop', '15.6 inch FHD, Intel Core i5, 8GB RAM, 512GB SSD', 5, 'Dell', 'pcs', 85000.00, 99999.00, NULL, 15, 3, 50, 1, '8001234567890', NULL, NULL, 0, 0, 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(2, NULL, 0, 0, 'SKU-00002', 'Samsung Galaxy A54', '6.4 inch Super AMOLED, 128GB, 8GB RAM', 6, 'Samsung', 'pcs', 45000.00, 54999.00, NULL, 8, 5, 100, 1, '8009876543210', NULL, NULL, 0, 0, 'active', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(3, NULL, 0, 0, 'SKU-00003', 'Ergonomic Office Chair', 'Mesh back, adjustable height, lumbar support', 7, 'Herman Miller', 'pcs', 18000.00, 24999.00, NULL, 23, 5, 80, 2, '8005555555555', NULL, NULL, 0, 0, 'active', '2026-04-20 07:09:17', '2026-04-21 11:18:41'),
(4, NULL, 0, 0, 'SKU-00004', 'Ballpoint Pens Box (50 pcs)', 'Blue ink, medium point, smooth writing', 8, 'Pilot', 'box', 350.00, 550.00, NULL, 114, 20, 500, 2, '8007777777777', NULL, NULL, 0, 0, 'active', '2026-04-20 07:09:17', '2026-04-21 11:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('text','number','select','date') NOT NULL DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `product_id` int UNSIGNED NOT NULL,
  `attribute_id` int UNSIGNED NOT NULL,
  `value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_url` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int UNSIGNED NOT NULL,
  `po_number` varchar(30) NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `po_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `customs_duty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `freight_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `landed_cost_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `order_status` enum('pending','partial','received','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int UNSIGNED NOT NULL,
  `po_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `received_qty` int NOT NULL DEFAULT '0',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int UNSIGNED NOT NULL,
  `return_number` varchar(30) NOT NULL,
  `po_id` int UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` int UNSIGNED NOT NULL,
  `return_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int UNSIGNED NOT NULL,
  `quote_number` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `quote_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','sent','accepted','rejected','converted') NOT NULL DEFAULT 'draft',
  `converted_invoice_id` int UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int UNSIGNED NOT NULL,
  `quote_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `id` int UNSIGNED NOT NULL,
  `req_number` varchar(30) NOT NULL,
  `requested_by` int UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected','ordered') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `id` int UNSIGNED NOT NULL,
  `req_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_system`, `created_at`) VALUES
(1, 'admin', 'Full access', 1, '2026-04-20 15:17:21'),
(2, 'manager', 'Manage operations', 1, '2026-04-20 15:17:21'),
(3, 'cashier', 'POS only', 1, '2026-04-20 15:17:21'),
(4, 'warehouse', 'Inventory only', 1, '2026-04-20 15:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(2, 2),
(1, 3),
(2, 3),
(1, 4),
(2, 4),
(1, 5),
(2, 5),
(1, 6),
(2, 6),
(1, 7),
(2, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39);

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` int UNSIGNED NOT NULL,
  `return_number` varchar(30) NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `refund_method` enum('cash','card','bank_transfer','credit_note') NOT NULL DEFAULT 'cash',
  `reason` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` int UNSIGNED NOT NULL,
  `return_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `batch_id` int UNSIGNED DEFAULT NULL,
  `serial_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `serial_numbers`
--

CREATE TABLE `serial_numbers` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `serial` varchar(150) NOT NULL,
  `status` enum('in_stock','sold','returned','damaged') NOT NULL DEFAULT 'in_stock',
  `invoice_id` int UNSIGNED DEFAULT NULL,
  `purchase_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'InventoryPro Solutions', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(2, 'company_address', '123 Business Park, Main Boulevard', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(3, 'company_phone', '+92-51-1234567', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(4, 'company_email', 'info@inventorypro.com', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(5, 'company_website', 'https://inventorypro.com', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(6, 'company_tax_number', 'TAX-2024-001', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(7, 'company_logo_url', '', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(8, 'invoice_prefix', 'INV', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(9, 'invoice_start_number', '1001', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(10, 'po_prefix', 'PO', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(11, 'po_start_number', '1001', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(12, 'default_tax_percent', '3', '2026-04-20 07:09:17', '2026-04-21 11:50:22'),
(13, 'default_payment_terms', 'Net 30', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(14, 'currency_symbol', 'Rs.', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(15, 'currency_code', 'PKR', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(16, 'date_format', 'Y-m-d', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(17, 'timezone', 'Asia/Karachi', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(18, 'low_stock_threshold', '10', '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(19, 'items_per_page', '20', '2026-04-20 07:09:17', '2026-04-20 07:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `open_time` datetime NOT NULL,
  `close_time` datetime DEFAULT NULL,
  `opening_float` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cash_sales` decimal(15,2) NOT NULL DEFAULT '0.00',
  `card_sales` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closing_cash` decimal(15,2) DEFAULT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('open','closed') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int UNSIGNED NOT NULL,
  `recipient` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `provider_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_settings`
--

CREATE TABLE `sms_settings` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `provider` varchar(50) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `sender_id` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_triggers`
--

CREATE TABLE `sms_triggers` (
  `id` int UNSIGNED NOT NULL,
  `trigger_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `template` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sms_triggers`
--

INSERT INTO `sms_triggers` (`id`, `trigger_name`, `is_active`, `template`) VALUES
(1, 'low_stock', 0, 'Alert: {product} is low on stock ({qty} left)'),
(2, 'overdue_payment', 0, 'Reminder: Invoice {invoice} of {amount} is overdue'),
(3, 'new_sale', 0, 'Thank you! Your order {invoice} for {amount} is confirmed'),
(4, 'shipping', 0, 'Your order {invoice} has shipped');

-- --------------------------------------------------------

--
-- Table structure for table `smtp_settings`
--

CREATE TABLE `smtp_settings` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `host` varchar(255) DEFAULT NULL,
  `port` int DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `encryption` varchar(10) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` int UNSIGNED NOT NULL,
  `reference_no` varchar(30) NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `adjustment_type` enum('addition','subtraction','damage','expired','correction','opening_stock') NOT NULL,
  `quantity` int NOT NULL,
  `before_stock` int NOT NULL,
  `after_stock` int NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_by` int UNSIGNED NOT NULL,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_audits`
--

CREATE TABLE `stock_audits` (
  `id` int UNSIGNED NOT NULL,
  `audit_number` varchar(30) NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `audit_date` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_by` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_audits`
--

INSERT INTO `stock_audits` (`id`, `audit_number`, `warehouse_id`, `audit_date`, `status`, `created_by`) VALUES
(1, 'AUD-20260420690', 1, '2026-04-20', 'closed', 1),
(2, 'AUD-20260420833', 1, '2026-04-20', 'closed', 1),
(3, 'AUD-20260420224', 1, '2026-04-20', 'closed', 1),
(4, 'AUD-20260420909', 1, '2026-04-20', 'closed', 1),
(5, 'AUD-20260420-5A29', 1, '2026-04-20', 'open', 1);

-- --------------------------------------------------------

--
-- Table structure for table `stock_audit_items`
--

CREATE TABLE `stock_audit_items` (
  `id` int UNSIGNED NOT NULL,
  `audit_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `system_qty` int NOT NULL,
  `counted_qty` int NOT NULL,
  `difference` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_audit_items`
--

INSERT INTO `stock_audit_items` (`id`, `audit_id`, `product_id`, `system_qty`, `counted_qty`, `difference`) VALUES
(1, 5, 1, 15, 15, 0),
(2, 5, 2, 8, 8, 0),
(3, 5, 3, 24, 24, 0),
(4, 5, 4, 120, 120, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int UNSIGNED NOT NULL,
  `reference_no` varchar(30) NOT NULL,
  `from_warehouse_id` int UNSIGNED NOT NULL,
  `to_warehouse_id` int UNSIGNED NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` int UNSIGNED NOT NULL,
  `transfer_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int UNSIGNED NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `city`, `country`, `tax_id`, `payment_terms`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'TechSource Pakistan', 'Bilal Ahmed', 'bilal@techsource.pk', '+92-321-5551234', 'Plot 45, Industrial Area', 'Lahore', 'Pakistan', 'PKR-TAX-001', 'Net 30', 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(2, 'Global Office Supplies', 'Fatima Khan', 'fatima@globaloffice.com', '+92-300-6667890', 'Office Tower, Floor 3', 'Karachi', 'Pakistan', 'PKR-TAX-002', 'Net 15', 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(3, 'Fresh Foods Distributors', 'Usman Ali', 'usman@freshfoods.pk', '+92-333-9990123', 'Market Road, Sector G', 'Islamabad', 'Pakistan', 'PKR-TAX-003', 'Cash on Delivery', 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(4, 'ProFurniture Ltd', 'Zara Siddiqui', 'zara@profurniture.pk', '+92-42-7774567', 'Furniture Market, GT Road', 'Rawalpindi', 'Pakistan', 'PKR-TAX-004', 'Net 45', 0.00, 'active', NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledgers`
--

CREATE TABLE `supplier_ledgers` (
  `id` int UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_type` enum('opening','po','payment','return','credit_note') NOT NULL,
  `reference_id` int UNSIGNED DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_logs`
--

CREATE TABLE `sync_logs` (
  `id` int UNSIGNED NOT NULL,
  `integration_id` int UNSIGNED NOT NULL,
  `sync_type` enum('products_push','products_pull','orders_pull','inventory_sync') NOT NULL,
  `status` enum('success','failed','partial') NOT NULL,
  `items_processed` int NOT NULL DEFAULT '0',
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `role_id` int UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `avatar`, `commission_rate`, `role`, `role_id`, `status`, `last_login`, `login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$s.JygRgZjg2f8geMc1FfG.4wpUK/g/TTTSXOWliq3AaDEm2USiyci', 'System Administrator', 'admin@inventorypro.com', NULL, NULL, 0.00, 'admin', 1, 'active', '2026-04-21 20:47:51', 0, NULL, '2026-04-20 07:09:17', '2026-04-21 15:47:51'),
(2, 'manager1', '$2y$12$IXpzQHFBJ9B0Fx4oWAeD2u/Q6/abt/coBdlxb5xR9xPQnSCRx9zxS', 'Sarah Johnson', 'sarah.johnson@inventorypro.com', NULL, NULL, 0.00, 'manager', 2, 'active', '2026-04-20 23:44:39', 0, NULL, '2026-04-20 07:09:17', '2026-04-20 18:44:39'),
(3, 'staff1', '$2y$12$nDPgFj.aEHCX5hpjAFMSdOy3CtH0gi.df2e/8J5yS03fAjElUpZGe', 'Michael Chen', 'michael.chen@inventorypro.com', NULL, NULL, 0.00, 'staff', NULL, 'active', NULL, 0, NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17'),
(4, 'staff2', '$2y$12$nDPgFj.aEHCX5hpjAFMSdOy3CtH0gi.df2e/8J5yS03fAjElUpZGe', 'Aisha Malik', 'aisha.malik@inventorypro.com', NULL, NULL, 0.00, 'staff', NULL, 'active', NULL, 0, NULL, '2026-04-20 07:09:17', '2026-04-20 07:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `location`, `status`, `created_at`) VALUES
(1, 'Main Warehouse', 'Ground Floor, Building A', 'active', '2026-04-20 07:09:17'),
(2, 'Secondary Warehouse', 'Second Floor, Building B', 'active', '2026-04-20 07:09:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_batch` (`product_id`,`batch_number`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collection_items`
--
ALTER TABLE `collection_items`
  ADD PRIMARY KEY (`collection_id`,`item_type`,`item_id`);

--
-- Indexes for table `composite_items`
--
ALTER TABLE `composite_items`
  ADD PRIMARY KEY (`parent_id`,`child_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_type` (`customer_type`);

--
-- Indexes for table `customer_ledgers`
--
ALTER TABLE `customer_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`,`transaction_date`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_categories`
--
ALTER TABLE `finance_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `login_log`
--
ALTER TABLE `login_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_module_action` (`module`,`action`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`product_id`,`attribute_id`),
  ADD KEY `attribute_id` (`attribute_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_po_date` (`po_date`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_id` (`po_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quote_number` (`quote_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`quote_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `req_number` (`req_number`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `req_id` (`req_id`);

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
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`);

--
-- Indexes for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial` (`serial`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_triggers`
--
ALTER TABLE `sms_triggers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trigger_name` (`trigger_name`);

--
-- Indexes for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `stock_audits`
--
ALTER TABLE `stock_audits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_number` (`audit_number`);

--
-- Indexes for table `stock_audit_items`
--
ALTER TABLE `stock_audit_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_id` (`audit_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `from_warehouse_id` (`from_warehouse_id`),
  ADD KEY `to_warehouse_id` (`to_warehouse_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transfer_id` (`transfer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `supplier_ledgers`
--
ALTER TABLE `supplier_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`,`transaction_date`);

--
-- Indexes for table `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `integration_id` (`integration_id`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_ledgers`
--
ALTER TABLE `customer_ledgers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_categories`
--
ALTER TABLE `finance_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `login_log`
--
ALTER TABLE `login_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_triggers`
--
ALTER TABLE `sms_triggers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_audits`
--
ALTER TABLE `stock_audits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_audit_items`
--
ALTER TABLE `stock_audit_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_ledgers`
--
ALTER TABLE `supplier_ledgers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_logs`
--
ALTER TABLE `sync_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `collection_items`
--
ALTER TABLE `collection_items`
  ADD CONSTRAINT `collection_items_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `composite_items`
--
ALTER TABLE `composite_items`
  ADD CONSTRAINT `composite_items_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `composite_items_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `customer_ledgers`
--
ALTER TABLE `customer_ledgers`
  ADD CONSTRAINT `customer_ledgers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`);

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`req_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `sales_returns_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `sales_return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `serial_numbers`
--
ALTER TABLE `serial_numbers`
  ADD CONSTRAINT `serial_numbers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_audit_items`
--
ALTER TABLE `stock_audit_items`
  ADD CONSTRAINT `stock_audit_items_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `stock_audits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `supplier_ledgers`
--
ALTER TABLE `supplier_ledgers`
  ADD CONSTRAINT `supplier_ledgers_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD CONSTRAINT `sync_logs_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
