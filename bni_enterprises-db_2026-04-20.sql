-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2026 at 08:59 AM
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
-- Database: `bni_enterprises`
--

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `id` int NOT NULL,
  `purchase_order_id` int NOT NULL,
  `order_date` date NOT NULL,
  `inventory_date` date NOT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `motor_number` varchar(100) DEFAULT NULL,
  `model_id` int NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `selling_date` date DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `margin` decimal(15,2) DEFAULT '0.00',
  `status` enum('in_stock','sold','returned','reserved') DEFAULT 'in_stock',
  `return_date` date DEFAULT NULL,
  `return_amount` decimal(15,2) DEFAULT NULL,
  `return_notes` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `purchase_order_id`, `order_date`, `inventory_date`, `chassis_number`, `motor_number`, `model_id`, `color`, `purchase_price`, `selling_price`, `selling_date`, `customer_id`, `tax_amount`, `margin`, `status`, `return_date`, `return_amount`, `return_notes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-01-01', '2024-01-02', 'CH-5001', 'MT-5001', 1, 'Red', 50000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, '2026-04-20 07:52:56', '2026-04-20 07:52:56'),
(2, 1, '2024-01-01', '2024-01-02', 'CH-5002', 'MT-5002', 2, 'Black', 60000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, NULL, '2026-04-20 07:52:56', '2026-04-20 07:52:56'),
(3, 1, '2024-01-01', '2024-01-02', 'CH-5003', 'MT-5003', 3, 'Blue', 40000.00, 55000.00, '2024-01-10', 1, 4000.00, 15000.00, 'sold', NULL, NULL, NULL, NULL, '2026-04-20 07:52:56', '2026-04-20 07:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `cheque_register`
--

CREATE TABLE `cheque_register` (
  `id` int NOT NULL,
  `cheque_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('payment','receipt','refund') NOT NULL,
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
(1, 'CHQ-10001', 'Meezan Bank', '2024-01-05', 150000.00, 'payment', 'cleared', 'purchase_order', 1, 'Default Supplier', 'Payment for PO ID: 1', '2026-04-20 07:52:56'),
(2, 'CHQ-90001', 'HBL', '2024-01-15', 55000.00, 'receipt', 'pending', 'sale', 3, 'Walk-in Customer', 'Receipt for Sale of Bikes', '2026-04-20 07:52:56');

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
(1, 'Walk-in Customer', 'N/A', 'N/A', 'N/A', '2026-04-20 07:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('debit','credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
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
(1, '2024-01-01', 'credit', 150000.00, 'supplier', 1, 'Purchase Order #1', 'purchase_order', 1, NULL, '2026-04-20 07:52:56'),
(2, '2024-01-10', 'credit', 55000.00, 'customer', 1, 'Sale of bike(s)', 'sale', 3, NULL, '2026-04-20 07:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `id` int NOT NULL,
  `model_code` varchar(50) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `short_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`id`, `model_code`, `model_name`, `category`, `short_code`, `created_at`) VALUES
(1, 'LY SI', 'LY SI', 'Electric Bike', 'LY', '2026-04-20 07:42:29'),
(2, 'T9 Sports', 'T9 Sports', 'Electric Bike', 'T9', '2026-04-20 07:42:29'),
(3, 'T9 Sports LFP', 'T9 Sports LFP', 'Electric Bike', 'T9 LFP', '2026-04-20 07:42:29'),
(4, 'T9 Eco', 'T9 Eco', 'Electric Bike', 'T9 Eco', '2026-04-20 07:42:29'),
(5, 'Thrill Pro', 'Thrill Pro', 'Electric Bike', 'TP', '2026-04-20 07:42:29'),
(6, 'Thrill Pro LFP', 'Thrill Pro LFP', 'Electric Bike', 'TP LFP', '2026-04-20 07:42:29'),
(7, 'E8S M2', 'E8S M2', 'Electric Scooter', 'E8S', '2026-04-20 07:42:29'),
(8, 'E8S Pro', 'E8S Pro', 'Electric Scooter', 'E8S Pro', '2026-04-20 07:42:29'),
(9, 'M6 K6', 'M6 K6', 'Electric Bike', 'M6', '2026-04-20 07:42:29'),
(10, 'M6 NP', 'M6 NP', 'Electric Bike', 'M6 NP', '2026-04-20 07:42:29'),
(11, 'M6 Lithium NP', 'M6 Lithium NP', 'Electric Bike', 'M6 L', '2026-04-20 07:42:29'),
(12, 'Premium', 'Premium', 'Electric Bike', 'Premium', '2026-04-20 07:42:29'),
(13, 'W. Bike H2', 'W. Bike H2', 'Electric Bike', 'W. Bike', '2026-04-20 07:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` enum('cash','cheque','bank_transfer','online') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `cheque_id` int DEFAULT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int NOT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_date`, `payment_type`, `amount`, `cheque_id`, `reference_type`, `reference_id`, `party_name`, `notes`, `created_at`) VALUES
(1, '2024-01-10', 'cheque', 55000.00, 2, 'sale', 3, 'Walk-in Customer', 'Payment for Sale', '2026-04-20 07:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int NOT NULL,
  `order_date` date NOT NULL,
  `supplier_id` int NOT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `cheque_amount` decimal(15,2) DEFAULT NULL,
  `total_units` int NOT NULL DEFAULT '0',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `order_date`, `supplier_id`, `cheque_number`, `bank_name`, `cheque_date`, `cheque_amount`, `total_units`, `notes`, `created_at`) VALUES
(1, '2024-01-01', 1, 'CHQ-10001', 'Meezan Bank', '2024-01-05', 150000.00, 3, 'Initial sample stock', '2026-04-20 07:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`) VALUES
(1, 'company_name', 'BNI Enterprises', '2026-04-20 07:42:29'),
(2, 'branch_name', 'Dera (Ahmed Metro)', '2026-04-20 07:42:29'),
(3, 'tax_rate', '0.1', '2026-04-20 07:42:29'),
(4, 'currency_symbol', 'Rs.', '2026-04-20 07:42:29'),
(5, 'tax_on', 'purchase_price', '2026-04-20 07:42:29'),
(6, 'theme', 'light', '2026-04-20 07:42:29');

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
(1, 'Default Supplier', 'N/A', 'N/A', '2026-04-20 07:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('admin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$KdSnze47ye.inqU8FvyrrO.ugHe3xSXPFiJJ1PsntZN9KJTND5Fa6', 'System Admin', 'admin@bni.com', 'admin', 'active', '2026-04-20 12:42:39', '2026-04-20 07:42:29', '2026-04-20 07:42:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chassis_number` (`chassis_number`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `customer_id` (`customer_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `model_code` (`model_code`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cheque_register`
--
ALTER TABLE `cheque_register`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bikes`
--
ALTER TABLE `bikes`
  ADD CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `bikes_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`cheque_id`) REFERENCES `cheque_register` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
