-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2026 at 09:59 AM
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
  `accessories` text,
  `safeguard_notes` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `cnic`, `address`, `created_at`) VALUES
(1, 'Ahmed Ali', '0321-1234567', '35201-1234567-1', 'Dera Ghazi Khan, Punjab', '2026-04-20 08:56:23'),
(2, 'Muhammad Usman', '0333-7654321', '35201-7654321-3', 'Muzaffargarh, Punjab', '2026-04-20 08:56:23'),
(3, 'Bilal Hussain', '0345-9876543', '35201-9876543-5', 'Rajanpur, Punjab', '2026-04-20 08:56:23'),
(4, 'Zafar Iqbal', '0312-4567890', '35201-4567890-7', 'Layyah, Punjab', '2026-04-20 08:56:23');

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
  `payment_type` enum('cash','cheque','bank_transfer','online') DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `cheque_id` int DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int NOT NULL,
  `order_date` date DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `cheque_amount` decimal(15,2) DEFAULT NULL,
  `total_units` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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

--
-- Indexes for dumped tables
--

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `cheque_id` (`cheque_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cheque_register`
--
ALTER TABLE `cheque_register`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
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
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`cheque_id`) REFERENCES `cheque_register` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
