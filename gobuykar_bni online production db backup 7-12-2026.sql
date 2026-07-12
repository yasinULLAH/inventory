-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 12, 2026 at 12:52 PM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gobuykar_bni`
--

-- --------------------------------------------------------

--
-- Table structure for table `accessories`
--

CREATE TABLE `accessories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `selling_price` decimal(15,2) DEFAULT 0.00,
  `current_stock` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `deposit_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `deposit_type` enum('cash','cheque','transfer','online','other') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `deposited_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_deposits`
--

INSERT INTO `bank_deposits` (`id`, `destination_id`, `deposit_date`, `amount`, `deposit_type`, `reference_no`, `receipt_image`, `deposited_by`, `notes`, `created_by`, `created_at`) VALUES
(2, 10, '2026-03-03', 179000.00, 'cash', '8064938', NULL, 'Murtaza', 'T9 Sports Grey 6884', 1, '2026-07-11 07:34:00'),
(3, 10, '2026-03-11', 179000.00, 'cash', '8664992', NULL, 'Murtaza', 'T9 Sports Black 7041', 1, '2026-07-11 07:56:14'),
(4, 10, '2026-03-09', 234000.00, 'cash', '8064977', NULL, 'Murtaza', 'T9 Sports Black 0916', 1, '2026-07-11 08:01:26'),
(5, 10, '2026-03-06', 179000.00, 'cash', '8064968  One thousand difference', NULL, 'Murtaza', 'T9 Sports Red 6966', 1, '2026-07-11 08:03:37'),
(7, 10, '2026-02-24', 199000.00, 'cash', '8100589', NULL, 'Murtaza', 'E8S 2293 and Trill Pro 5515', 1, '2026-07-11 09:22:01'),
(8, 10, '2026-02-24', 279000.00, 'cash', '8100589', NULL, 'Murtaza', 'E8S 2293 and Trill Pro 5515', 1, '2026-07-11 09:31:15'),
(9, 11, '2026-03-16', 83000.00, 'cash', '3201691', NULL, 'Murtaza', '83000 out of 283000. 2 lacs online by Dr Shabbir', 1, '2026-07-11 09:46:42'),
(10, 11, '2026-03-16', 200000.00, 'cash', '3201691', NULL, 'Murtaza', '200000 out of 242000. 2 lacs online by Dr Shabbir', 1, '2026-07-11 14:02:33'),
(11, 11, '2026-03-16', 285000.00, 'cash', '3201691', NULL, 'Murtaza', 'M6 Black LFP 3201691', 1, '2026-07-11 14:06:04');

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `inventory_date` date DEFAULT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `motor_number` varchar(100) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `selling_date` date DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `margin` decimal(15,2) DEFAULT 0.00,
  `status` enum('in_stock','sold','returned','returned_to_supplier','reserved','damaged_lost') DEFAULT 'in_stock',
  `return_date` date DEFAULT NULL,
  `return_amount` decimal(15,2) DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `safeguard_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `purchase_order_id`, `order_date`, `inventory_date`, `chassis_number`, `motor_number`, `model_id`, `color`, `purchase_price`, `selling_price`, `selling_date`, `customer_id`, `tax_amount`, `margin`, `status`, `return_date`, `return_amount`, `return_notes`, `safeguard_notes`, `notes`, `image`, `created_at`, `updated_at`) VALUES
(44, 20, '2026-02-05', '2026-02-05', 'LY05G48270002304', 'XRLY48052125D0002228', 1, 'Back', 125225.00, 139000.00, '2026-04-13', 54, 6950.00, 6825.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 05:49:38', '2026-07-09 18:26:59'),
(45, 21, '2026-02-05', '2026-02-05', 'LY05G48270002202', 'XRLY48052125D0002322', 1, 'GREY', 125225.00, 143000.00, '2026-05-24', 55, 7150.00, 10625.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 05:55:32', '2026-07-09 18:28:15'),
(46, 22, '2026-02-05', '2026-02-05', 'M615G72380002665', 'A9A756800994', 9, 'Silver', 220721.00, 242000.00, '2026-03-15', 68, 12100.00, 9179.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 06:01:41', '2026-07-11 10:47:25'),
(47, 25, '2026-02-05', '2026-02-05', 'T910G72260006966', 'XR9S72102825N0007369', 2, 'Red', 161261.00, 178000.00, '2026-03-05', 56, 8900.00, 7839.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/bike_6a2cf36eab123.webp', '2026-06-13 06:06:39', '2026-07-06 09:01:00'),
(48, 26, '2026-02-05', '2026-02-05', 'T910G72260007041', 'XR9S72102825N0007701', 2, 'BLACk', 161261.00, 179000.00, '2026-03-10', 7, 8950.00, 8789.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 06:16:30', '2026-07-06 09:07:49'),
(49, 27, '2026-02-05', '2026-02-05', 'T910G72260006884', 'Xr9s72102825N0007393', 2, 'GREY', 161261.00, 179000.00, '2026-03-02', 9, 8950.00, 8789.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/bike_6a2cf6b2ded64.webp', '2026-06-13 06:20:35', '2026-07-06 09:08:36'),
(50, 32, '2026-02-05', '2026-02-05', 'TH12G72260005515', 'AIMTP721240259005364', 5, 'GREY', 179279.00, 199000.00, '2026-02-22', NULL, 9950.00, 9771.00, 'sold', NULL, NULL, NULL, '', 'Name Noman Akbar  Name F . Muhammad Akbar Ali.                 CNIC no. 12101.9339619.      Cont no 03367595840', 'uploads/bike_6a2cfb6fd6ee6.webp', '2026-06-13 06:40:48', '2026-07-06 09:11:37'),
(51, 33, '2026-02-05', '2026-02-05', 'TH12G72260006004', 'AIMTP721240259006297', 5, '', 179279.00, 195000.00, '2026-06-14', NULL, 9750.00, 5971.00, 'sold', NULL, NULL, NULL, '', 'Name Muhammad Umer   F Name Muhammad Ashiv.             CNIC no . 12101.8661600.7.              Cont no..03459827323', NULL, '2026-06-13 06:45:27', '2026-07-09 18:34:21'),
(52, 34, '2026-02-05', '2026-02-05', 'T910L72300000632', 'XR9S7210282500000640', 3, 'Silver', 193694.00, 229000.00, '2026-03-28', 10, 11450.00, 23856.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/bike_6a2cfdf01c3e4.webp', '2026-06-13 06:51:28', '2026-07-11 14:39:59'),
(54, 36, '2026-03-05', '2026-02-05', 'TH12L72300000445', 'AIMT72124025N001005', 6, 'BLACk', 200000.00, 250000.00, '2026-04-04', NULL, 20000.00, 30000.00, 'sold', NULL, NULL, NULL, '', 'Name. Muzammil Ahmed  F Name . Zia Ahmed Khan ..    CNIC no 12101.8541384.7.    Cont n0..03127990667', NULL, '2026-06-13 07:12:19', '2026-06-14 06:41:18'),
(55, 37, '2026-02-05', '2026-02-05', 'TH12L72300000416', 'AIMTP72124025N001176', 6, 'GREY', 211712.00, 246000.00, '2026-03-18', 27, 12300.00, 21988.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 07:15:16', '2026-07-11 14:42:45'),
(56, 39, '2026-02-05', '2026-02-05', 'T910L72300000916', 'XR9S72102825N0000927', 3, 'BLACK', 193694.00, 234000.00, '2026-03-07', 11, 11700.00, 28606.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:03:36', '2026-07-11 14:41:00'),
(57, 40, '2026-02-05', '2026-02-05', 'DD35G48130001177', '48V350WA8T454708922', 16, 'BLACK', 94595.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:08:45', '2026-07-09 18:29:03'),
(58, 41, '2026-02-05', '2026-02-05', 'E820G72380002293', 'PJE872203525N0002160', 7, 'GREY', 251351.00, 279000.00, '2026-02-21', NULL, 13950.00, 13699.00, 'sold', NULL, NULL, NULL, '', 'Name Esse khan      F Name Ghulam Nabbi.          Cnic no 12101.0923412.3.     Cont.03464300982', NULL, '2026-06-13 08:11:02', '2026-07-06 09:10:33'),
(59, 42, '2026-03-03', '2026-03-03', 'M615L72300006176', 'XRM672153025D0007536', 11, 'BLACK', 211712.00, 285000.00, '2026-03-12', 12, 14250.00, 59038.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:16:03', '2026-07-11 14:54:26'),
(60, 43, '2026-03-09', '2026-03-09', 'M615L72300006278', 'XRM672153025D0007499', 11, 'Silver', 254955.00, 283000.00, '2026-03-12', 13, 14150.00, 13895.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:18:22', '2026-07-11 14:55:25'),
(61, 44, '2026-03-16', '2026-06-16', 'T910G72260008882', 'XR9S72102825D0007890', 2, 'Red', 161261.00, 178000.00, '2026-06-16', 14, 8900.00, 7839.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:21:19', '2026-07-11 14:56:25'),
(62, 45, '2026-03-16', '2026-06-16', 'T910G72260008478', 'XR9S72102825D0007858', 2, 'BLACK', 161261.00, 177500.00, '2026-03-26', 21, 8875.00, 7364.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:23:04', '2026-07-11 14:57:14'),
(63, 46, '2026-03-16', '2026-03-16', 'T910G72260008679', 'XR9S72102825D0007954', 2, 'GREY', 161261.00, 179000.00, '2026-03-18', 22, 8950.00, 8789.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:25:29', '2026-07-11 14:58:07'),
(64, 47, '2026-03-16', '2026-03-16', 'TH12G72260006279', 'AIMTP721240259006047', 5, 'GREY', 179279.00, 198000.00, '2026-04-11', 23, 9900.00, 8821.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:41:31', '2026-07-11 14:59:30'),
(65, 48, '2026-03-16', '2026-03-16', 'TH12G72260006236', 'AIMTP721240259006039', 5, 'BLACK', 179279.00, 198000.00, '2026-03-25', 25, 9900.00, 8821.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:43:53', '2026-07-11 15:00:19'),
(66, 49, '2026-03-25', '2026-03-25', 'E820G72380000466', '12ZW7271327YE*CERR116670c*', 8, 'BLUE', 247748.00, 274000.00, '2026-03-30', 24, 13700.00, 12552.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:47:32', '2026-07-11 15:01:09'),
(69, 52, '2026-06-25', '2026-06-25', 'T910G72260008720', 'XR9S72102825D0007987', 2, 'Red', 161261.00, 178000.00, '2026-04-02', 28, 8900.00, 7839.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:54:46', '2026-07-11 15:07:24'),
(70, 53, '2026-03-25', '2026-03-25', 'T910G72260008894', 'XR9S72102825D0008251', 2, 'GREY', 161261.00, 178000.00, '2026-04-04', 20, 8900.00, 7839.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:56:33', '2026-07-11 15:08:08'),
(71, 54, '2026-03-25', '2026-03-25', 'T910G72260008737', 'XR9S72102825D0008003', 2, 'GREY', 161261.00, 178000.00, '2026-04-04', 15, 8900.00, 7839.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 08:58:25', '2026-07-11 15:08:45'),
(72, 55, '2026-03-25', '2026-03-25', 'P308L72300000159', 'PHPM7208352610000422', 18, 'OFF WHITE', 234234.00, 277000.00, '2026-04-12', 16, 13850.00, 28916.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:04:29', '2026-07-11 15:02:02'),
(73, 56, '2026-03-25', '2026-03-25', 'E810G72380000595', '10ZW7273316YECKTS0000107', 17, 'GREY', 211712.00, 235000.00, '2026-04-05', 26, 11750.00, 11538.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:07:37', '2026-07-11 15:06:23'),
(74, 57, '2026-04-04', '2026-04-04', 'T912G72380001172', 'YD2025PAK1924919277', 19, 'RED', 239693.00, 265000.00, '2026-04-05', 13, 13250.00, 12057.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:11:44', '2026-07-11 16:47:11'),
(75, 58, '2026-04-04', '2026-04-04', 'T912G72380001156', 'YD2025PAK1924919277', 20, 'Silver', 200000.00, 264000.00, '2026-04-08', 17, 2000.00, 62000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:13:18', '2026-06-15 07:29:42'),
(76, 59, '2026-04-04', '2026-04-04', 'T910L72300001018', 'XR9S 72102825N0001172', 3, 'BLACK', 211653.00, 232500.00, '2026-04-08', 19, 11625.00, 9222.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:30:09', '2026-07-11 16:46:34'),
(77, 60, '2026-04-04', '2026-04-04', 'T910L72300001272', 'XR9S72102825N0001172', 3, 'BLACK', 211653.00, 232500.00, '2026-04-11', 18, 11625.00, 9222.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 09:36:50', '2026-07-11 16:16:45'),
(79, 62, '2026-04-04', '2026-04-04', 'TH12L7200001147', 'AIMTP72124025N001000', 6, 'BLACK', 229743.00, 250000.00, '2026-04-08', 36, 12500.00, 7757.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:22:24', '2026-07-11 16:11:08'),
(80, 63, '2026-04-04', '2026-04-04', 'TH12L7200001157', 'AIMTP72124025N001248', 6, 'BLACK', 229743.00, 250000.00, '2026-04-09', 30, 12500.00, 7757.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:30:19', '2026-07-11 16:49:34'),
(81, 64, '2026-04-23', '2026-04-23', 'T910G72260010603', 'XR9S7210282610010246', 2, 'GREY', 200000.00, 178000.00, '2026-04-27', 31, 2000.00, -24000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:36:04', '2026-06-15 09:56:27'),
(82, 65, '2026-04-23', '2026-04-23', 'T910L72300002099', 'XR9S72102826100001996', 3, '', 200000.00, 230000.00, '2026-04-29', 52, 2000.00, 28000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:41:16', '2026-06-16 06:49:38'),
(83, 66, '2026-04-23', '2026-04-23', 'T910L72300002152', 'XR9S721028266100002009', 3, '', 200000.00, 230000.00, '2026-04-27', 46, 2000.00, 28000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:48:21', '2026-06-16 06:21:21'),
(84, 67, '2026-04-23', '2026-04-23', 'M615L72300001539', 'XRM67215302580003024', 11, 'BLACK', 200000.00, 300000.00, '2026-04-27', 32, 2000.00, 98000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 10:53:39', '2026-06-15 10:47:32'),
(85, 68, '2026-06-13', '2026-06-13', 'DB12G72260004432', 'QSTD721235262004335', 21, 'Red', 200000.00, 190000.00, '2026-05-24', 33, 2000.00, -12000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 11:18:13', '2026-06-15 10:54:44'),
(86, 69, '2026-04-23', '2026-04-23', 'TH12G72260007145', 'AIMTP72124025D007486', 5, 'GREY', 200000.00, 200000.00, '2026-04-27', 43, 2000.00, -2000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 11:24:19', '2026-06-16 06:12:45'),
(87, 70, '2026-04-23', '2026-04-23', 'TH12G72260007314', 'AIMTP72124025D007223', 5, 'BLACK', 200000.00, 200000.00, '2026-05-05', 44, 2000.00, -2000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 11:30:39', '2026-06-16 06:15:19'),
(88, 71, '2026-04-23', '2026-04-23', 'TH12L72300002298', 'AIMTP72124025D002319', 6, 'GREY', 200000.00, 252000.00, '2026-04-27', 35, 2000.00, 50000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 11:34:37', '2026-06-15 11:05:31'),
(89, 72, '2026-04-23', '2026-04-23', 'TH12L72300002097', 'AIMTP72124025D002188', 6, 'BLACK', 200000.00, 249000.00, '2026-05-02', 34, 2000.00, 47000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 11:37:45', '2026-06-15 11:02:22'),
(90, 73, '2026-05-20', '2026-05-20', 'T912G72380001154', '12W7253318YE0000841', 19, 'Silver', 200000.00, 265000.00, '2026-06-14', 63, 2000.00, 63000.00, 'sold', NULL, NULL, NULL, '', 'Date 29.6.26 (1) Cheque HBL 00000096...82500\r\nDate 14.7.26 (2) Cheque HBL 00000098....82500', NULL, '2026-06-13 12:55:18', '2026-06-22 07:40:58'),
(91, 74, '2026-05-20', '2026-05-20', 'M615L72300004400', 'XRM67215302580001887', 11, 'BLACK', 200000.00, 287000.00, '2026-06-21', 64, 2000.00, 85000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 12:58:48', '2026-06-27 07:25:06'),
(92, 75, '2026-05-20', '2026-05-20', 'M615L72300004039', 'XRM67215302580002825', 11, 'BLACK', 200000.00, 288000.00, '2026-05-24', 40, 2000.00, 86000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:00:48', '2026-06-15 13:10:20'),
(93, 76, '2026-05-20', '2026-05-20', 'M615L72300004251', 'XRM67215302580002310', 11, 'BLACK', 200000.00, 288000.00, '2026-06-21', 13, 2000.00, 86000.00, 'returned', '2026-07-07', 265000.00, '', '', '', NULL, '2026-06-13 13:02:50', '2026-07-07 18:07:39'),
(94, 77, '2026-05-20', '2026-05-20', 'M615L72300004264', 'XRM67215302580001972', 11, 'BLACK', 200000.00, 288000.00, '2026-05-24', 41, 2000.00, 86000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:05:22', '2026-06-15 13:15:05'),
(96, 79, '2026-05-20', '2026-05-20', 'M615L72300004278', 'XRM67215302580002311', 11, 'Silver', 200000.00, 288000.00, '2026-05-24', 50, 2000.00, 86000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:21:52', '2026-06-16 06:32:41'),
(97, 80, '2026-05-20', '2026-05-20', 'M615L72300002106', 'XRM67215302580002048', 11, 'Silver', 200000.00, NULL, NULL, NULL, 20000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:22:55', '2026-06-13 13:22:55'),
(98, 81, '2026-05-20', '2026-05-20', 'M615L72300002099', 'XRM67215302580002363', 11, 'Silver', 200000.00, NULL, NULL, NULL, 20000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:24:12', '2026-06-13 13:24:12'),
(99, 82, '2026-05-20', '2026-05-20', 'M615L72300002311', 'XRM67215302580001933', 11, 'Silver', 200000.00, 286000.00, '2026-06-10', 37, 2000.00, 84000.00, 'sold', NULL, NULL, NULL, '', '', 'uploads/bike_6a2e72a88098e.jpg', '2026-06-13 13:25:44', '2026-06-15 11:14:59'),
(100, 83, '2026-05-20', '2026-05-20', 'DB12G72260004159', 'QSTB21235262004452', 21, 'GREY', 200000.00, 189000.00, '2026-05-24', 39, 2000.00, -13000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-13 13:33:34', '2026-06-15 11:16:44'),
(101, 84, '2026-05-20', '2026-05-20', 'TH12L72300001015', 'AIMTP72124025N001102', 6, 'BLACK', 200000.00, 261000.00, '2026-06-10', 42, 2000.00, 59000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-15 12:31:39', '2026-06-15 13:24:49'),
(102, 85, '2026-05-20', '2026-05-20', 'TH12L72300000747', 'AIMTP721240259000494', 6, 'GREY', 200000.00, 263000.00, '2026-06-09', 51, 2000.00, 61000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-15 12:34:23', '2026-06-16 06:38:34'),
(103, 86, '2026-05-20', '2026-05-20', 'TH12L72300000520', 'AIMTP721240259000577', 6, 'GREY', 200000.00, 263000.00, '2026-06-13', 53, 2000.00, 61000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-15 12:35:48', '2026-06-16 07:55:39'),
(107, 90, '2026-05-20', '2026-05-20', 'DB12L72300005332', 'QSTDB721235262005177', 22, 'Red', 200000.00, NULL, NULL, NULL, 2000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-06-15 12:46:13', '2026-06-15 12:46:13'),
(109, 92, '2026-05-20', '2026-05-20', 'MX20G72380000538', 'VQMS72203525N0000554', 23, 'BLACK', 200000.00, 250000.00, '2026-05-26', 45, 2000.00, 48000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 05:59:15', '2026-06-16 06:17:50'),
(110, 93, '2026-05-20', '2026-05-20', 'MX20G72380000463', 'VQMS72203525N0000571', 23, 'BLACK', 200000.00, 250000.00, '2026-06-01', 47, 2000.00, 48000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 06:03:37', '2026-06-16 06:23:14'),
(111, 94, '2026-05-20', '2026-05-20', 'MX20G72380000660', 'VQMS72203525N0000379', 23, 'Silver', 200000.00, 247000.00, '2026-05-25', 48, 2000.00, 45000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 06:06:01', '2026-06-16 06:25:05'),
(112, 95, '2026-05-20', '2026-05-20', 'MX20G72380000567', 'VQMS72203525N0000536', 23, 'Silver', 200000.00, 247000.00, '2026-05-25', 49, 2000.00, 45000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 06:09:02', '2026-06-16 06:26:31'),
(113, 96, '2026-05-20', '2026-05-20', 'DB12L72300005119', 'QSTB721235262005451', 22, 'GREY', 200000.00, 238000.00, '2026-05-24', 60, 2000.00, 36000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 08:57:00', '2026-06-16 09:49:34'),
(114, 97, '2026-05-20', '2026-05-20', 'DB12G72260004208', 'QSTDB721235262004166', 21, 'Red', 200000.00, 186500.00, '2026-05-31', 58, 2000.00, -15500.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 09:13:58', '2026-06-16 09:39:37'),
(115, 98, '2026-05-20', '2026-05-20', 'DB12L72300005310', 'QSTD721235262005386', 22, 'Red', 200000.00, 250000.00, '2026-06-08', 59, 2000.00, 48000.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 09:18:23', '2026-06-16 09:46:29'),
(116, 99, '2026-05-20', '2026-05-20', 'DB12L72300005366', 'QSTD721235262005029', 22, 'GREY', 225000.00, 238000.00, '2026-05-24', 61, 2250.00, 10750.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 09:19:46', '2026-07-06 03:58:25'),
(117, 100, '2026-05-20', '2026-05-20', 'M615L72300004014', 'XRM67215302580001972', 11, 'BLACK', 255000.00, 286000.00, '2026-05-24', 62, 2550.00, 28450.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-06-16 11:38:07', '2026-07-06 03:57:26'),
(118, 101, '2026-06-02', '2026-07-06', 'T910G72260012197', 'XR9S7210282620011865', 2, 'GREY', 200000.00, 182000.00, '2026-07-08', 67, 1820.00, -19820.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:13:31', '2026-07-08 11:37:17'),
(119, 102, '2026-07-02', '2026-07-06', 'T910G72260012249', 'XR9S7210282620012109', 2, 'GREY', 200000.00, NULL, NULL, NULL, 10000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:15:30', '2026-07-07 11:15:30'),
(120, 103, '2026-07-02', '2026-07-06', 'E820G72380002334', 'JZPE8722035263002441', 7, 'GREY', 200000.00, NULL, NULL, NULL, 10000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:20:58', '2026-07-07 11:20:58'),
(121, 104, '2026-07-02', '2026-07-06', 'E820G72380002364', 'JZPE8722035263002372', 7, '', 200000.00, 282000.00, '2026-07-07', 13, 2820.00, 79180.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:24:34', '2026-07-08 07:32:15'),
(122, 105, '2026-07-02', '2026-07-06', 'T910G72260012324', 'XR9S7210282620011943', 2, 'BLACk', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:26:27', '2026-07-07 11:27:34'),
(123, 106, '2026-07-02', '2026-07-06', 'T910G72260012119', 'XR9S7210282620011859', 2, 'BLACK', 200000.00, NULL, NULL, NULL, 10000.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-07 11:29:46', '2026-07-07 11:29:46'),
(124, 107, '2026-06-30', '2026-07-06', 'T910G72260011701', 'XR9S7210282610011425', 2, 'Red', 200000.00, 182000.00, '2026-07-04', 65, 1820.00, -19820.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:35:38', '2026-07-08 11:31:37'),
(125, 108, '2026-07-02', '2026-07-06', 'T910G72260011642', 'XR9S7210282610011247', 2, 'Red', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:38:16', '2026-07-08 09:38:16'),
(126, 109, '2026-07-02', '2026-07-06', 'T910G72260011574', 'XR9S7210282610011598', 2, 'Red', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:41:05', '2026-07-08 09:41:05'),
(127, 110, '2026-07-02', '2026-07-06', 'Th12G72260007792', 'Aimtp721240261008669', 2, 'GREY', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:44:32', '2026-07-08 09:44:32'),
(128, 111, '2026-07-02', '2026-07-07', 'Th12G72260009288', 'Aimtp721240261008696', 5, 'BLACK', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:47:39', '2026-07-08 09:47:39'),
(129, 112, '2026-07-02', '2026-07-06', 'Th12G72260009451', 'Aimtp721240261008793', 5, 'BLACK', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:50:52', '2026-07-08 09:50:52'),
(130, 113, '2026-07-02', '2026-07-06', 'DB12G72260004861', 'QSTDB721235262004980', 21, 'GREY', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:52:45', '2026-07-08 09:52:45'),
(131, 114, '2026-07-02', '2026-07-06', 'DB12G72260004600', 'QSTB721235262004936', 21, 'GREY', 200000.00, 187000.00, '2026-07-05', 66, 1870.00, -14870.00, 'sold', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:55:27', '2026-07-08 11:34:11'),
(132, 115, '2026-07-02', '2026-07-06', 'DB12G72260004508', 'QSTDB721235263006319', 21, 'Red', 200000.00, NULL, NULL, NULL, 0.00, 0.00, 'in_stock', NULL, NULL, NULL, '', '', NULL, '2026-07-08 09:57:46', '2026-07-08 09:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `bike_requests`
--

CREATE TABLE `bike_requests` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_details` text DEFAULT NULL,
  `status` enum('pending','contacted','fulfilled','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bike_requests`
--

INSERT INTO `bike_requests` (`id`, `customer_name`, `customer_phone`, `bike_details`, `status`, `created_at`) VALUES
(1, 'Yasin Ullah', '03139842219', 'I need an electric honda Civic', 'fulfilled', '2026-05-31 13:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `cheque_register`
--

CREATE TABLE `cheque_register` (
  `id` int(11) NOT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `type` enum('payment','receipt','refund') DEFAULT NULL,
  `status` enum('pending','cleared','bounced','cancelled') DEFAULT 'pending',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `is_filer` tinyint(1) DEFAULT 1,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `cnic`, `is_filer`, `address`, `created_at`) VALUES
(7, 'Muhammad Tariv', '03467873344', '12102.9511357.3', 1, 'D I khan', '2026-06-14 10:11:50'),
(8, 'Noman AkBar', '03367595840', '12101.9339619.1', 1, 'D I khan', '2026-06-14 10:29:09'),
(9, 'Noman AkBar', '03367595840', '12101.9339619.1', 1, 'D I Khan', '2026-06-14 10:32:32'),
(10, 'Muhammad kashif', '03297746061', '12101.2912756.9', 1, 'D I khan', '2026-06-15 06:50:58'),
(11, 'Muhammad FaiZan Ali', '03459823559', '12101.6209013.5', 1, 'D I Khan', '2026-06-15 06:54:28'),
(12, 'Hamaz Khan', '03351838603', '12101.6364257.5', 1, 'D I Khan', '2026-06-15 06:59:04'),
(13, 'Dr Shabir Ahmed', '03459838941', '12101', 1, 'D I Khan', '2026-06-15 07:02:09'),
(14, 'Ahmed din', '03419392580', '21504', 1, '', '2026-06-15 07:06:01'),
(15, 'Muhammad Jahan zaib', '03439720135', '22601.2036354.3', 1, '', '2026-06-15 07:11:41'),
(16, 'Muhammad idress', '03121936001', '12201.8537937.1', 1, '', '2026-06-15 07:14:13'),
(17, 'Imran Khan', '03432844803', '121019073502.9', 1, '', '2026-06-15 07:29:27'),
(18, 'Muhammad ASif', '03416963772', '12101.2010105.9', 0, 'D I Khan', '2026-06-15 07:34:15'),
(19, 'Waheed ullah', '03466533270', '21702.3886916.5', 1, '', '2026-06-15 07:39:31'),
(20, 'Umer Shehzad', '03449393347', '12101.4462495.3', 1, '', '2026-06-15 07:44:28'),
(21, 'Ashfaq Ahmed', '03347210584', '12101', 1, '', '2026-06-15 07:56:57'),
(22, 'Fayyaz Masih', '03419609874', '121018947151.5', 1, '', '2026-06-15 08:01:12'),
(23, 'Hasnain mehmood', '03464474523', '12101.3004905.5', 1, '', '2026-06-15 08:08:02'),
(24, 'Muhammad Haseeb Rehman', '03495675373', '12101.0870963.5', 0, '', '2026-06-15 08:45:05'),
(25, 'Muhammad Alam Mehsud', '03339887798', '21702.7923847.5', 1, '', '2026-06-15 08:49:17'),
(26, 'Tanueer khan', '03306780668', '12101.0502400.5', 1, '', '2026-06-15 08:52:55'),
(27, 'Muhammad Atif Majeed', '03365567568', '12101.1288092.3', 1, '', '2026-06-15 09:05:40'),
(28, 'Jerryson', '03179845077', '38312.3593817.1', 1, '', '2026-06-15 09:10:36'),
(29, 'Farid ullah', '03064872747', '21506.9073312.7', 1, '', '2026-06-15 09:34:59'),
(30, 'Muhammad Sadiv', '03459820307', '12101.29978809', 1, '', '2026-06-15 09:52:16'),
(31, 'Rashid Rauf', '0344 8024331', '12101.9776029.3', 1, '', '2026-06-15 09:56:18'),
(32, 'Sher Afghan', '03150110588', '21706.0379303.1', 1, '', '2026-06-15 10:47:17'),
(33, 'Sheikh Qaiser Hayal', '03467864407', '12103.7880818.7', 1, '', '2026-06-15 10:54:19'),
(34, 'Qudral Ullah', '03315135183', '12101.4963705.5', 1, '', '2026-06-15 11:02:18'),
(35, 'Abdul Basit', '03337625006', '12101', 1, '', '2026-06-15 11:05:26'),
(36, 'Farid ullah', '03064872747', '21506.9073312.7', 1, '', '2026-06-15 11:11:52'),
(37, 'Zahid khan', '03467842270', '11101.9774366.3', 1, '', '2026-06-15 11:14:42'),
(38, 'Ali Ammar', '03403228748', '12101.8395017.9', 1, '', '2026-06-15 11:16:40'),
(39, 'Ali Ammar', '03403228748', '12101.8395017.9', 1, '', '2026-06-15 11:16:40'),
(40, 'Hashmat Ali', '03436664277', '12103.8886121.3', 1, '', '2026-06-15 13:10:13'),
(41, 'Muhammad ASif Saleem', '03139311000', '12101.951574.3', 1, '', '2026-06-15 13:14:59'),
(42, 'Malik Muhammad Jauid', '03341634038', '12101.3076821.9', 0, '', '2026-06-15 13:23:44'),
(43, 'Karim Khan', '03425139312', '12101.1895836.5', 1, '', '2026-06-16 06:12:29'),
(44, 'Muhammad Akhtar', '03414643688', '12101.7192382.3', 1, '', '2026-06-16 06:15:02'),
(45, 'Fazal Urrehman', '03146932673', '12101.4057760.5', 1, '', '2026-06-16 06:17:29'),
(46, 'Muhammad Mujtaba', '03459770194', '12101.0969958.5', 1, '', '2026-06-16 06:20:48'),
(47, 'Muhammad Ali Hasnain', '03430990137', '12101.7180551.7', 1, '', '2026-06-16 06:23:04'),
(48, 'Muhammad Khalid Raza', '03358426381', '12101.3624851.9', 1, '', '2026-06-16 06:24:59'),
(49, 'Muhammad ASif', '03459888719', '12101.2520476.7', 1, '', '2026-06-16 06:26:25'),
(50, 'Muhammad Abu Huaira', '03448368196', '12101.1096601.7', 1, '', '2026-06-16 06:32:30'),
(51, 'Yasir Wazir', '03360716014', '12101.6663595.5', 1, '', '2026-06-16 06:38:30'),
(52, 'Muhammad zakir', '03358001173', '21705.5097050.7', 1, '', '2026-06-16 06:49:34'),
(53, 'Sattar Shah', '0333993400', '121010.6368643.5', 1, '', '2026-06-16 07:55:32'),
(54, 'Shafi Ullah', '03429343239', '12101', 1, '', '2026-06-16 08:00:12'),
(55, 'Muhammad Rizwan', '03460659037', '12101.0463136.7', 0, '', '2026-06-16 08:02:04'),
(56, 'Burhan khan', '03285657562', '12101.0687828.9', 1, '', '2026-06-16 08:04:04'),
(57, 'Muhammad Anwar ul hav', '03339980542', '12101.8737855.', 1, '', '2026-06-16 09:35:48'),
(58, 'Muhammad Anwar ul hav', '03339980542', '12101.8737855.1', 1, '', '2026-06-16 09:39:28'),
(59, 'Muhammad jamil', '03009094742', '12101.5703927.9', 1, '', '2026-06-16 09:46:14'),
(60, 'Haseeb Bilal', '03320764144', '12101.0793983.9', 1, '', '2026-06-16 09:49:27'),
(61, 'Muhammad Bilal', '03426445342', '12101.6368643.5', 1, '', '2026-06-16 11:10:34'),
(62, 'Tahir Ahmed Wasil', '03449376090', '12101.0905893.3', 1, '', '2026-06-16 11:41:05'),
(63, 'Muneer javid', '03484448384', '12103.2059022.3', 1, '', '2026-06-22 07:34:56'),
(64, 'Muhammad Humam', '03140665031', '12101.3311235.9', 1, 'D I Khan', '2026-06-27 07:24:37'),
(65, 'M Sharif', '03449275449', '12101.8612817.7', 1, 'Booking 18.6.2026\r\n180000\r\nBk', '2026-07-08 11:31:26'),
(66, 'M ilyas khan', '03363350007', '12101.8461663.5', 1, '', '2026-07-08 11:34:00'),
(67, 'Nighat Shaheen', '03441906769', '12101.0883790.6', 1, '19.6.2026\r\n180000', '2026-07-08 11:37:08'),
(68, 'M Zain ul abideen', '03279712623', '12101.8901035.3', 1, '', '2026-07-11 10:46:52');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_allocations`
--

CREATE TABLE `deposit_allocations` (
  `id` int(11) NOT NULL,
  `deposit_id` int(11) NOT NULL,
  `allocation_id` int(11) DEFAULT NULL,
  `bike_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposit_allocations`
--

INSERT INTO `deposit_allocations` (`id`, `deposit_id`, `allocation_id`, `bike_id`, `amount`) VALUES
(3, 2, 3, 49, 179000.00),
(4, 3, 6, 48, 179000.00),
(5, 4, 4, 56, 234000.00),
(6, 5, 5, 47, 178000.00),
(7, 7, 1, 50, 199000.00),
(8, 8, 9, 58, 279000.00),
(9, 9, 10, 60, 83000.00),
(10, 10, 12, 46, 200000.00),
(11, 11, 11, 59, 285000.00);

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income_expenses`
--

CREATE TABLE `income_expenses` (
  `id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','cheque','bank_transfer','online','other') DEFAULT 'cash',
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income_expenses`
--

INSERT INTO `income_expenses` (`id`, `entry_date`, `type`, `category`, `amount`, `payment_method`, `reference`, `notes`, `created_by`, `created_at`) VALUES
(12, '2026-03-05', 'expense', 'Rent', 14000.00, 'cash', 'February 2026', '', 1, '2026-07-06 05:38:06'),
(13, '2026-03-05', 'expense', 'Chowkidaar', 1000.00, 'cash', '', '', 1, '2026-07-06 05:39:30'),
(14, '2026-04-06', 'expense', 'Rent', 14000.00, 'cash', 'March 2026', '', 1, '2026-07-06 05:41:38'),
(15, '2026-04-06', 'expense', 'Chowkidaar', 1000.00, 'cash', '', '', 1, '2026-07-06 05:42:03'),
(16, '2026-05-06', 'expense', 'Rent', 14000.00, 'cash', 'April 2026', '', 1, '2026-07-06 05:42:42'),
(17, '2026-05-06', 'expense', 'Chowkidaar', 1000.00, 'cash', '', '', 1, '2026-07-06 05:43:09'),
(18, '2026-06-06', 'expense', 'Rent', 14000.00, 'cash', 'May 2026', '', 1, '2026-07-06 05:43:36'),
(19, '2026-06-06', 'expense', 'Chowkidaar', 1000.00, 'cash', '', '', 1, '2026-07-06 05:45:00'),
(20, '2026-07-06', 'expense', 'Rent', 14000.00, 'cash', 'June 2026', '', 1, '2026-07-06 05:47:32'),
(21, '2026-07-06', 'expense', 'Chowkidaar', 1000.00, 'cash', '', '', 1, '2026-07-06 05:48:07'),
(22, '2026-06-01', 'expense', 'Part Time Salary', 13000.00, 'cash', 'May 2026', 'Aminullah Khan', 1, '2026-07-06 05:50:04'),
(23, '2026-07-01', 'expense', 'Part Time Salary', 13000.00, 'cash', 'June 2026', '', 1, '2026-07-06 05:51:00'),
(24, '2026-05-01', 'expense', 'Printing Material', 24000.00, 'cash', '', '5 Bill Books, 5 Reselling Books, 5 Advance Booking, 5 Quotation Book', 1, '2026-07-06 05:57:26'),
(25, '2026-04-01', 'expense', 'Shop Extention', 20900.00, 'cash', '', 'Cement, Paint and Other Charges', 1, '2026-07-06 05:58:53'),
(26, '2026-05-01', 'expense', 'Ceiling Fans', 18000.00, 'cash', '', '2 Ceiling Fans', 1, '2026-07-06 05:59:52'),
(27, '2026-05-01', 'expense', 'Solar Plates', 16000.00, 'cash', '', '2 Plates', 1, '2026-07-06 06:00:35'),
(28, '2026-05-01', 'expense', 'Paint', 4000.00, 'cash', 'Volt &amp; reverse to Metro', '', 1, '2026-07-06 06:02:23'),
(29, '2026-05-01', 'expense', 'Electrician', 1000.00, 'cash', '', '', 1, '2026-07-06 06:03:17'),
(30, '2026-05-01', 'expense', 'Decoration Shoppers', 2800.00, 'cash', '', '2 Roll Decoration Paper', 1, '2026-07-06 06:04:21'),
(31, '2026-06-01', 'expense', 'Rewards', 4000.00, 'cash', '', '', 1, '2026-07-06 06:05:06'),
(32, '2026-06-01', 'expense', 'Floor Mates / Carpets', 6500.00, 'cash', '', '', 1, '2026-07-06 06:06:48'),
(33, '2026-06-01', 'expense', 'Almirah', 12000.00, 'cash', '', '', 1, '2026-07-06 06:08:23'),
(34, '2026-06-01', 'expense', 'Printing Material', 2500.00, 'cash', '', 'Letter Book', 1, '2026-07-06 06:09:07'),
(35, '2026-06-03', 'expense', 'Decoration Shoppers', 2800.00, 'cash', '', '2 Rolls', 1, '2026-07-06 06:11:23'),
(36, '2026-05-31', 'expense', 'Electricity Bill', 4300.00, 'cash', 'April 2026', '', 1, '2026-07-06 06:12:19'),
(37, '2026-06-30', 'expense', 'Electricity Bill', 4000.00, 'cash', '', '', 1, '2026-07-06 06:12:48'),
(38, '2026-06-30', 'expense', 'Gifts', 3000.00, 'cash', '', '', 1, '2026-07-06 06:14:10'),
(39, '2026-06-01', 'expense', 'Misc / Lunch / Dinner', 4700.00, 'cash', 'RSM', '', 1, '2026-07-06 06:15:40'),
(40, '2026-06-10', 'expense', 'Software website', 66650.00, 'online', 'Yaseen', '', 1, '2026-07-11 08:00:30'),
(41, '2026-02-03', 'expense', 'Paint', 4000.00, 'cash', 'Shatters', '', 1, '2026-07-11 08:04:03'),
(42, '2026-07-11', 'expense', '4 piece sofa set', 15500.00, 'cash', '', '', 1, '2026-07-11 08:04:50'),
(43, '2026-07-11', 'expense', '1 piece office table', 9500.00, 'cash', '', '', 1, '2026-07-11 08:05:25'),
(44, '2026-02-03', 'expense', 'One piece office chair', 5300.00, 'cash', '', '', 1, '2026-07-11 08:06:06'),
(45, '2026-02-07', 'expense', 'Sheet', 10500.00, 'cash', '', '', 1, '2026-07-11 08:06:53'),
(46, '2026-03-01', 'expense', 'Grass carpet', 13900.00, 'cash', '', '', 1, '2026-07-11 08:07:32'),
(47, '2026-03-10', 'expense', '3D wallpaper', 8800.00, 'cash', '', '', 1, '2026-07-11 08:08:14'),
(48, '2026-04-08', 'expense', 'Electricity connection and wires', 6500.00, 'cash', '', '', 1, '2026-07-11 08:09:19'),
(49, '2026-02-11', 'expense', 'Weapon and bullets', 19000.00, 'cash', '', '', 1, '2026-07-11 08:10:00'),
(50, '2026-02-27', 'expense', 'Eid gift banner charsada d.i.khan', 2200.00, 'cash', '', '', 1, '2026-07-11 08:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `installments`
--

CREATE TABLE `installments` (
  `id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `installment_amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `penalty_fee` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `installments`
--

INSERT INTO `installments` (`id`, `bike_id`, `customer_id`, `due_date`, `installment_amount`, `amount_paid`, `penalty_fee`, `status`, `payment_id`, `notes`, `created_at`, `updated_at`) VALUES
(25, 90, 63, '2026-07-22', 82500.00, 0.00, 0.00, 'pending', NULL, 'Installment 1 for Chassis T912G72380001154', '2026-06-22 07:40:58', '2026-06-22 07:40:58'),
(26, 90, 63, '2026-08-22', 82500.00, 0.00, 0.00, 'pending', NULL, 'Installment 2 for Chassis T912G72380001154', '2026-06-22 07:40:58', '2026-06-22 07:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `leadership`
--

CREATE TABLE `leadership` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leadership`
--

INSERT INTO `leadership` (`id`, `name`, `position`, `image`, `message`, `sort_order`, `created_at`) VALUES
(1, 'Murtaza Khan', 'Manager', 'uploads/img_6a21229376f6e.jpg', 'At BNI Enterprises, we are driving the future of electric mobility. Our commitment is to provide eco-friendly, reliable, and premium transportation solutions that empower communities and protect our environment.', 0, '2026-05-12 07:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `entry_date` date DEFAULT NULL,
  `entry_type` enum('debit','credit') DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `party_type` enum('customer','supplier','other') DEFAULT NULL,
  `party_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`id`, `entry_date`, `entry_type`, `amount`, `party_type`, `party_id`, `description`, `reference_type`, `reference_id`, `balance`, `created_at`) VALUES
(46, '2026-02-21', 'debit', 279000.00, 'customer', NULL, 'Sale of Chassis: E820G72380002293', 'sale', 58, 279000.00, '2026-06-14 06:31:44'),
(47, '2026-02-21', 'credit', 279000.00, 'customer', NULL, 'Down Payment for Chassis: E820G72380002293', 'down_payment', 58, 279000.00, '2026-06-14 06:31:44'),
(48, '2026-04-04', 'debit', 250000.00, 'customer', NULL, 'Sale of Chassis: TH12L72300000445', 'sale', 54, 250000.00, '2026-06-14 06:41:18'),
(49, '2026-04-04', 'credit', 250000.00, 'customer', NULL, 'Down Payment for Chassis: TH12L72300000445', 'down_payment', 54, 250000.00, '2026-06-14 06:41:18'),
(50, '2026-02-22', 'debit', 199000.00, 'customer', NULL, 'Sale of Chassis: TH12G72260005515', 'sale', 50, 199000.00, '2026-06-14 06:50:37'),
(51, '2026-02-22', 'credit', 199000.00, 'customer', NULL, 'Down Payment for Chassis: TH12G72260005515', 'down_payment', 50, 199000.00, '2026-06-14 06:50:37'),
(52, '2026-06-14', 'debit', 195000.00, 'customer', NULL, 'Sale of Chassis: TH12G72260006004', 'sale', 51, 195000.00, '2026-06-14 08:39:07'),
(53, '2026-06-14', 'credit', 195000.00, 'customer', NULL, 'Down Payment for Chassis: TH12G72260006004', 'down_payment', 51, 195000.00, '2026-06-14 08:39:07'),
(54, '2026-03-10', 'debit', 179000.00, 'customer', 7, 'Sale of Chassis: T910G72260007041', 'sale', 48, 179000.00, '2026-06-14 10:12:19'),
(55, '2026-03-10', 'credit', 179000.00, 'customer', 7, 'Down Payment for Chassis: T910G72260007041', 'down_payment', 48, 179000.00, '2026-06-14 10:12:19'),
(56, '2026-03-02', 'debit', 179000.00, 'customer', 9, 'Sale of Chassis: T910G72260006884', 'sale', 49, 179000.00, '2026-06-14 10:32:44'),
(57, '2026-03-02', 'credit', 179000.00, 'customer', 9, 'Down Payment for Chassis: T910G72260006884', 'down_payment', 49, 179000.00, '2026-06-14 10:32:44'),
(58, '2026-03-28', 'debit', 229000.00, 'customer', 10, 'Sale of Chassis: T910L72300000632', 'sale', 52, 229000.00, '2026-06-15 06:51:13'),
(59, '2026-03-28', 'credit', 229000.00, 'customer', 10, 'Down Payment for Chassis: T910L72300000632', 'down_payment', 52, 229000.00, '2026-06-15 06:51:13'),
(60, '2026-03-07', 'debit', 234000.00, 'customer', 11, 'Sale of Chassis: T910L72300000916', 'sale', 56, 234000.00, '2026-06-15 06:54:44'),
(61, '2026-03-07', 'credit', 234000.00, 'customer', 11, 'Down Payment for Chassis: T910L72300000916', 'down_payment', 56, 234000.00, '2026-06-15 06:54:44'),
(62, '2026-03-12', 'debit', 285000.00, 'customer', 12, 'Sale of Chassis: M615L72300006176', 'sale', 59, 285000.00, '2026-06-15 06:59:10'),
(63, '2026-03-12', 'credit', 285000.00, 'customer', 12, 'Down Payment for Chassis: M615L72300006176', 'down_payment', 59, 285000.00, '2026-06-15 06:59:10'),
(64, '2026-03-12', 'debit', 283000.00, 'customer', 13, 'Sale of Chassis: M615L72300006278', 'sale', 60, 283000.00, '2026-06-15 07:02:37'),
(65, '2026-03-12', 'credit', 283000.00, 'customer', 13, 'Down Payment for Chassis: M615L72300006278', 'down_payment', 60, 283000.00, '2026-06-15 07:02:37'),
(66, '2026-04-04', 'debit', 178000.00, 'customer', 15, 'Sale of Chassis: T910G72260008737', 'sale', 71, 178000.00, '2026-06-15 07:12:07'),
(67, '2026-04-04', 'credit', 178000.00, 'customer', 15, 'Down Payment for Chassis: T910G72260008737', 'down_payment', 71, 178000.00, '2026-06-15 07:12:07'),
(68, '2026-04-12', 'debit', 277000.00, 'customer', 16, 'Sale of Chassis: P308L72300000159', 'sale', 72, 277000.00, '2026-06-15 07:14:23'),
(69, '2026-04-12', 'credit', 277000.00, 'customer', 16, 'Down Payment for Chassis: P308L72300000159', 'down_payment', 72, 277000.00, '2026-06-15 07:14:23'),
(70, '2026-04-05', 'debit', 265000.00, 'customer', 13, 'Sale of Chassis: T912G72380001156', 'sale', 75, 265000.00, '2026-06-15 07:15:37'),
(71, '2026-04-05', 'credit', 265000.00, 'customer', 13, 'Down Payment for Chassis: T912G72380001156', 'down_payment', 75, 265000.00, '2026-06-15 07:15:37'),
(72, '2026-04-05', 'debit', 265000.00, 'customer', 13, 'Sale of Chassis: T912G72380001172', 'sale', 74, 265000.00, '2026-06-15 07:26:23'),
(73, '2026-04-05', 'credit', 265000.00, 'customer', 13, 'Down Payment for Chassis: T912G72380001172', 'down_payment', 74, 265000.00, '2026-06-15 07:26:23'),
(74, '2026-04-08', 'debit', 264000.00, 'customer', 17, 'Sale of Chassis: T912G72380001156', 'sale', 75, 264000.00, '2026-06-15 07:29:42'),
(75, '2026-04-08', 'credit', 264000.00, 'customer', 17, 'Down Payment for Chassis: T912G72380001156', 'down_payment', 75, 264000.00, '2026-06-15 07:29:42'),
(76, '2026-04-11', 'debit', 232500.00, 'customer', 18, 'Sale of Chassis: T910L72300001272', 'sale', 77, 232500.00, '2026-06-15 07:34:28'),
(77, '2026-04-11', 'credit', 232500.00, 'customer', 18, 'Down Payment for Chassis: T910L72300001272', 'down_payment', 77, 232500.00, '2026-06-15 07:34:28'),
(78, '2026-04-08', 'debit', 232500.00, 'customer', 19, 'Sale of Chassis: T910L72300001018', 'sale', 76, 232500.00, '2026-06-15 07:39:51'),
(79, '2026-04-08', 'credit', 232500.00, 'customer', 19, 'Down Payment for Chassis: T910L72300001018', 'down_payment', 76, 232500.00, '2026-06-15 07:39:51'),
(80, '2026-04-04', 'debit', 178000.00, 'customer', 20, 'Sale of Chassis: T910G72260008894', 'sale', 70, 178000.00, '2026-06-15 07:44:45'),
(81, '2026-04-04', 'credit', 178000.00, 'customer', 20, 'Down Payment for Chassis: T910G72260008894', 'down_payment', 70, 178000.00, '2026-06-15 07:44:45'),
(82, '2026-03-26', 'debit', 177500.00, 'customer', 21, 'Sale of Chassis: T910G72260008478', 'sale', 62, 177500.00, '2026-06-15 07:57:04'),
(83, '2026-03-26', 'credit', 177500.00, 'customer', 21, 'Down Payment for Chassis: T910G72260008478', 'down_payment', 62, 177500.00, '2026-06-15 07:57:04'),
(84, '2026-03-18', 'debit', 179000.00, 'customer', 22, 'Sale of Chassis: T910G72260008679', 'sale', 63, 179000.00, '2026-06-15 08:02:02'),
(85, '2026-03-18', 'credit', 179000.00, 'customer', 22, 'Down Payment for Chassis: T910G72260008679', 'down_payment', 63, 179000.00, '2026-06-15 08:02:02'),
(86, '2026-04-11', 'debit', 198000.00, 'customer', 23, 'Sale of Chassis: TH12G72260006279', 'sale', 64, 198000.00, '2026-06-15 08:08:11'),
(87, '2026-04-11', 'credit', 198000.00, 'customer', 23, 'Down Payment for Chassis: TH12G72260006279', 'down_payment', 64, 198000.00, '2026-06-15 08:08:11'),
(88, '2026-03-30', 'debit', 274000.00, 'customer', 24, 'Sale of Chassis: E820G72380000466', 'sale', 66, 274000.00, '2026-06-15 08:45:17'),
(89, '2026-03-30', 'credit', 274000.00, 'customer', 24, 'Down Payment for Chassis: E820G72380000466', 'down_payment', 66, 274000.00, '2026-06-15 08:45:17'),
(90, '2026-03-25', 'debit', 198000.00, 'customer', 25, 'Sale of Chassis: TH12G72260006236', 'sale', 65, 198000.00, '2026-06-15 08:49:22'),
(91, '2026-03-25', 'credit', 198000.00, 'customer', 25, 'Down Payment for Chassis: TH12G72260006236', 'down_payment', 65, 198000.00, '2026-06-15 08:49:22'),
(92, '2026-04-05', 'debit', 235000.00, 'customer', 26, 'Sale of Chassis: E810G72380000595', 'sale', 73, 235000.00, '2026-06-15 08:53:18'),
(93, '2026-04-05', 'credit', 235000.00, 'customer', 26, 'Down Payment for Chassis: E810G72380000595', 'down_payment', 73, 235000.00, '2026-06-15 08:53:18'),
(94, '2026-03-18', 'debit', 246000.00, 'customer', 27, 'Sale of Chassis: TH12L72300000416', 'sale', 55, 246000.00, '2026-06-15 09:05:44'),
(95, '2026-03-18', 'credit', 246000.00, 'customer', 27, 'Down Payment for Chassis: TH12L72300000416', 'down_payment', 55, 246000.00, '2026-06-15 09:05:44'),
(96, '2026-04-02', 'debit', 178000.00, 'customer', 28, 'Sale of Chassis: T910G72260008720', 'sale', 69, 178000.00, '2026-06-15 09:10:40'),
(97, '2026-04-02', 'credit', 178000.00, 'customer', 28, 'Down Payment for Chassis: T910G72260008720', 'down_payment', 69, 178000.00, '2026-06-15 09:10:40'),
(98, '2026-04-09', 'debit', 250000.00, 'customer', 30, 'Sale of Chassis: TH12L7200001157', 'sale', 80, 250000.00, '2026-06-15 09:52:22'),
(99, '2026-04-09', 'credit', 250000.00, 'customer', 30, 'Down Payment for Chassis: TH12L7200001157', 'down_payment', 80, 250000.00, '2026-06-15 09:52:22'),
(100, '2026-04-27', 'debit', 178000.00, 'customer', 31, 'Sale of Chassis: T910G72260010603', 'sale', 81, 178000.00, '2026-06-15 09:56:27'),
(101, '2026-04-27', 'credit', 178000.00, 'customer', 31, 'Down Payment for Chassis: T910G72260010603', 'down_payment', 81, 178000.00, '2026-06-15 09:56:27'),
(102, '2026-04-27', 'debit', 300000.00, 'customer', 32, 'Sale of Chassis: M615L72300001539', 'sale', 84, 300000.00, '2026-06-15 10:47:32'),
(103, '2026-04-27', 'credit', 300000.00, 'customer', 32, 'Down Payment for Chassis: M615L72300001539', 'down_payment', 84, 300000.00, '2026-06-15 10:47:32'),
(104, '2026-05-24', 'debit', 190000.00, 'customer', 33, 'Sale of Chassis: DB12G72260004432', 'sale', 85, 190000.00, '2026-06-15 10:54:44'),
(105, '2026-05-24', 'credit', 190000.00, 'customer', 33, 'Down Payment for Chassis: DB12G72260004432', 'down_payment', 85, 190000.00, '2026-06-15 10:54:44'),
(106, '2026-05-02', 'debit', 249000.00, 'customer', 34, 'Sale of Chassis: TH12L72300002097', 'sale', 89, 249000.00, '2026-06-15 11:02:22'),
(107, '2026-05-02', 'credit', 249000.00, 'customer', 34, 'Down Payment for Chassis: TH12L72300002097', 'down_payment', 89, 249000.00, '2026-06-15 11:02:22'),
(108, '2026-04-27', 'debit', 252000.00, 'customer', 35, 'Sale of Chassis: TH12L72300002298', 'sale', 88, 252000.00, '2026-06-15 11:05:31'),
(109, '2026-04-27', 'credit', 252000.00, 'customer', 35, 'Down Payment for Chassis: TH12L72300002298', 'down_payment', 88, 252000.00, '2026-06-15 11:05:31'),
(110, '2026-04-08', 'debit', 250000.00, 'customer', 36, 'Sale of Chassis: TH12L7200001147', 'sale', 79, 250000.00, '2026-06-15 11:11:57'),
(111, '2026-04-08', 'credit', 250000.00, 'customer', 36, 'Down Payment for Chassis: TH12L7200001147', 'down_payment', 79, 250000.00, '2026-06-15 11:11:57'),
(112, '2026-06-10', 'debit', 286000.00, 'customer', 37, 'Sale of Chassis: M615L72300002311', 'sale', 99, 286000.00, '2026-06-15 11:14:59'),
(113, '2026-06-10', 'credit', 286000.00, 'customer', 37, 'Down Payment for Chassis: M615L72300002311', 'down_payment', 99, 286000.00, '2026-06-15 11:14:59'),
(114, '2026-05-24', 'debit', 189000.00, 'customer', 39, 'Sale of Chassis: DB12G72260004159', 'sale', 100, 189000.00, '2026-06-15 11:16:44'),
(115, '2026-05-24', 'credit', 189000.00, 'customer', 39, 'Down Payment for Chassis: DB12G72260004159', 'down_payment', 100, 189000.00, '2026-06-15 11:16:44'),
(116, '2026-05-24', 'debit', 288000.00, 'customer', 40, 'Sale of Chassis: M615L72300004039', 'sale', 92, 288000.00, '2026-06-15 13:10:20'),
(117, '2026-05-24', 'credit', 288000.00, 'customer', 40, 'Down Payment for Chassis: M615L72300004039', 'down_payment', 92, 288000.00, '2026-06-15 13:10:20'),
(118, '2026-05-24', 'debit', 288000.00, 'customer', 41, 'Sale of Chassis: M615L72300004264', 'sale', 94, 288000.00, '2026-06-15 13:15:05'),
(119, '2026-05-24', 'credit', 288000.00, 'customer', 41, 'Down Payment for Chassis: M615L72300004264', 'down_payment', 94, 288000.00, '2026-06-15 13:15:05'),
(120, '2026-06-10', 'debit', 261000.00, 'customer', 42, 'Sale of Chassis: TH12L72300001015', 'sale', 101, 261000.00, '2026-06-15 13:24:49'),
(121, '2026-06-10', 'credit', 261000.00, 'customer', 42, 'Down Payment for Chassis: TH12L72300001015', 'down_payment', 101, 261000.00, '2026-06-15 13:24:49'),
(122, '2026-04-27', 'debit', 200000.00, 'customer', 43, 'Sale of Chassis: TH12G72260007145', 'sale', 86, 200000.00, '2026-06-16 06:12:45'),
(123, '2026-04-27', 'credit', 200000.00, 'customer', 43, 'Down Payment for Chassis: TH12G72260007145', 'down_payment', 86, 200000.00, '2026-06-16 06:12:45'),
(124, '2026-05-05', 'debit', 200000.00, 'customer', 44, 'Sale of Chassis: TH12G72260007314', 'sale', 87, 200000.00, '2026-06-16 06:15:19'),
(125, '2026-05-05', 'credit', 200000.00, 'customer', 44, 'Down Payment for Chassis: TH12G72260007314', 'down_payment', 87, 200000.00, '2026-06-16 06:15:19'),
(126, '2026-05-26', 'debit', 250000.00, 'customer', 45, 'Sale of Chassis: MX20G72380000538', 'sale', 109, 250000.00, '2026-06-16 06:17:50'),
(127, '2026-05-26', 'credit', 250000.00, 'customer', 45, 'Down Payment for Chassis: MX20G72380000538', 'down_payment', 109, 250000.00, '2026-06-16 06:17:50'),
(128, '2026-04-27', 'debit', 230000.00, 'customer', 46, 'Sale of Chassis: T910L72300002152', 'sale', 83, 230000.00, '2026-06-16 06:21:21'),
(129, '2026-04-27', 'credit', 230000.00, 'customer', 46, 'Down Payment for Chassis: T910L72300002152', 'down_payment', 83, 230000.00, '2026-06-16 06:21:21'),
(130, '2026-06-01', 'debit', 250000.00, 'customer', 47, 'Sale of Chassis: MX20G72380000463', 'sale', 110, 250000.00, '2026-06-16 06:23:14'),
(131, '2026-06-01', 'credit', 250000.00, 'customer', 47, 'Down Payment for Chassis: MX20G72380000463', 'down_payment', 110, 250000.00, '2026-06-16 06:23:14'),
(132, '2026-05-25', 'debit', 247000.00, 'customer', 48, 'Sale of Chassis: MX20G72380000660', 'sale', 111, 247000.00, '2026-06-16 06:25:05'),
(133, '2026-05-25', 'credit', 247000.00, 'customer', 48, 'Down Payment for Chassis: MX20G72380000660', 'down_payment', 111, 247000.00, '2026-06-16 06:25:05'),
(134, '2026-05-25', 'debit', 247000.00, 'customer', 49, 'Sale of Chassis: MX20G72380000567', 'sale', 112, 247000.00, '2026-06-16 06:26:31'),
(135, '2026-05-25', 'credit', 247000.00, 'customer', 49, 'Down Payment for Chassis: MX20G72380000567', 'down_payment', 112, 247000.00, '2026-06-16 06:26:31'),
(136, '2026-05-24', 'debit', 288000.00, 'customer', 50, 'Sale of Chassis: M615L72300004278', 'sale', 96, 288000.00, '2026-06-16 06:32:41'),
(137, '2026-05-24', 'credit', 288000.00, 'customer', 50, 'Down Payment for Chassis: M615L72300004278', 'down_payment', 96, 288000.00, '2026-06-16 06:32:41'),
(138, '2026-06-09', 'debit', 263000.00, 'customer', 51, 'Sale of Chassis: TH12L72300000747', 'sale', 102, 263000.00, '2026-06-16 06:38:34'),
(139, '2026-06-09', 'credit', 263000.00, 'customer', 51, 'Down Payment for Chassis: TH12L72300000747', 'down_payment', 102, 263000.00, '2026-06-16 06:38:34'),
(140, '2026-04-29', 'debit', 230000.00, 'customer', 52, 'Sale of Chassis: T910L72300002099', 'sale', 82, 230000.00, '2026-06-16 06:49:38'),
(141, '2026-04-29', 'credit', 230000.00, 'customer', 52, 'Down Payment for Chassis: T910L72300002099', 'down_payment', 82, 230000.00, '2026-06-16 06:49:38'),
(142, '2026-06-13', 'debit', 263000.00, 'customer', 53, 'Sale of Chassis: TH12L72300000520', 'sale', 103, 263000.00, '2026-06-16 07:55:39'),
(143, '2026-06-13', 'credit', 263000.00, 'customer', 53, 'Down Payment for Chassis: TH12L72300000520', 'down_payment', 103, 263000.00, '2026-06-16 07:55:39'),
(144, '2026-06-16', 'debit', 178000.00, 'customer', 14, 'Sale of Chassis: T910G72260008882', 'sale', 61, 178000.00, '2026-06-16 07:58:16'),
(145, '2026-06-16', 'credit', 178000.00, 'customer', 14, 'Down Payment for Chassis: T910G72260008882', 'down_payment', 61, 178000.00, '2026-06-16 07:58:16'),
(146, '2026-04-13', 'debit', 139000.00, 'customer', 54, 'Sale of Chassis: LY05G48270002304', 'sale', 44, 139000.00, '2026-06-16 08:00:16'),
(147, '2026-04-13', 'credit', 139000.00, 'customer', 54, 'Down Payment for Chassis: LY05G48270002304', 'down_payment', 44, 139000.00, '2026-06-16 08:00:16'),
(148, '2026-05-24', 'debit', 143000.00, 'customer', 55, 'Sale of Chassis: LY05G48270002202', 'sale', 45, 143000.00, '2026-06-16 08:02:18'),
(149, '2026-05-24', 'credit', 143000.00, 'customer', 55, 'Down Payment for Chassis: LY05G48270002202', 'down_payment', 45, 143000.00, '2026-06-16 08:02:18'),
(150, '2026-03-05', 'debit', 178000.00, 'customer', 56, 'Sale of Chassis: T910G72260006966', 'sale', 47, 178000.00, '2026-06-16 08:04:08'),
(151, '2026-03-05', 'credit', 178000.00, 'customer', 56, 'Down Payment for Chassis: T910G72260006966', 'down_payment', 47, 178000.00, '2026-06-16 08:04:08'),
(152, '2026-05-31', 'debit', 186500.00, 'customer', 58, 'Sale of Chassis: DB12G72260004208', 'sale', 114, 186500.00, '2026-06-16 09:39:37'),
(153, '2026-05-31', 'credit', 186500.00, 'customer', 58, 'Down Payment for Chassis: DB12G72260004208', 'down_payment', 114, 186500.00, '2026-06-16 09:39:37'),
(154, '2026-06-08', 'debit', 250000.00, 'customer', 59, 'Sale of Chassis: DB12L72300005310', 'sale', 115, 250000.00, '2026-06-16 09:46:29'),
(155, '2026-06-08', 'credit', 250000.00, 'customer', 59, 'Down Payment for Chassis: DB12L72300005310', 'down_payment', 115, 250000.00, '2026-06-16 09:46:29'),
(156, '2026-05-24', 'debit', 238000.00, 'customer', 60, 'Sale of Chassis: DB12L72300005119', 'sale', 113, 238000.00, '2026-06-16 09:49:34'),
(157, '2026-05-24', 'credit', 238000.00, 'customer', 60, 'Down Payment for Chassis: DB12L72300005119', 'down_payment', 113, 238000.00, '2026-06-16 09:49:34'),
(158, '2026-05-24', 'debit', 238000.00, 'customer', 61, 'Sale of Chassis: DB12L72300005366', 'sale', 116, 238000.00, '2026-06-16 11:10:52'),
(159, '2026-05-24', 'credit', 238000.00, 'customer', 61, 'Down Payment for Chassis: DB12L72300005366', 'down_payment', 116, 238000.00, '2026-06-16 11:10:52'),
(160, '2026-05-24', 'debit', 286000.00, 'customer', 62, 'Sale of Chassis: M615L72300004014', 'sale', 117, 286000.00, '2026-06-16 11:41:10'),
(161, '2026-05-24', 'credit', 286000.00, 'customer', 62, 'Down Payment for Chassis: M615L72300004014', 'down_payment', 117, 286000.00, '2026-06-16 11:41:10'),
(162, '2026-06-21', 'debit', 288000.00, 'customer', 13, 'Sale of Chassis: M615L72300004251', 'sale', 93, 288000.00, '2026-06-22 07:31:49'),
(163, '2026-06-21', 'credit', 288000.00, 'customer', 13, 'Down Payment for Chassis: M615L72300004251', 'down_payment', 93, 288000.00, '2026-06-22 07:31:49'),
(164, '2026-06-14', 'debit', 265000.00, 'customer', 63, 'Sale of Chassis: T912G72380001154', 'sale', 90, 265000.00, '2026-06-22 07:40:58'),
(165, '2026-06-14', 'credit', 100000.00, 'customer', 63, 'Down Payment for Chassis: T912G72380001154', 'down_payment', 90, 100000.00, '2026-06-22 07:40:58'),
(166, '2026-06-21', 'debit', 287000.00, 'customer', 64, 'Sale of Chassis: M615L72300004400', 'sale', 91, 287000.00, '2026-06-27 07:25:06'),
(167, '2026-06-21', 'credit', 287000.00, 'customer', 64, 'Down Payment for Chassis: M615L72300004400', 'down_payment', 91, 287000.00, '2026-06-27 07:25:06'),
(168, '2026-07-07', 'credit', 288000.00, 'customer', 13, 'Bike Return (Reversal) for Chassis: M615L72300004251', 'return_reversal', 93, 288000.00, '2026-07-07 18:07:39'),
(169, '2026-07-07', 'debit', 265000.00, 'customer', 13, 'Refund given for Chassis: M615L72300004251', 'return_refund', 93, 265000.00, '2026-07-07 18:07:39'),
(170, '2026-07-07', 'debit', 282000.00, 'customer', 13, 'Sale of Chassis: E820G72380002364', 'sale', 121, 282000.00, '2026-07-08 07:32:15'),
(171, '2026-07-07', 'credit', 17000.00, 'customer', 13, 'Down Payment for Chassis: E820G72380002364', 'down_payment', 121, 17000.00, '2026-07-08 07:32:15'),
(172, '2026-07-04', 'debit', 182000.00, 'customer', 65, 'Sale of Chassis: T910G72260011701', 'sale', 124, 182000.00, '2026-07-08 11:31:37'),
(173, '2026-07-04', 'credit', 182000.00, 'customer', 65, 'Down Payment for Chassis: T910G72260011701', 'down_payment', 124, 182000.00, '2026-07-08 11:31:37'),
(174, '2026-07-05', 'debit', 187000.00, 'customer', 66, 'Sale of Chassis: DB12G72260004600', 'sale', 131, 187000.00, '2026-07-08 11:34:11'),
(175, '2026-07-05', 'credit', 187000.00, 'customer', 66, 'Down Payment for Chassis: DB12G72260004600', 'down_payment', 131, 187000.00, '2026-07-08 11:34:11'),
(176, '2026-07-08', 'debit', 182000.00, 'customer', 67, 'Sale of Chassis: T910G72260012197', 'sale', 118, 182000.00, '2026-07-08 11:37:17'),
(177, '2026-07-08', 'credit', 182000.00, 'customer', 67, 'Down Payment for Chassis: T910G72260012197', 'down_payment', 118, 182000.00, '2026-07-08 11:37:17'),
(178, '2026-03-15', 'debit', 242000.00, 'customer', 68, 'Sale of Chassis: M615G72380002665', 'sale', 46, 242000.00, '2026-07-11 10:47:25'),
(179, '2026-03-15', 'credit', 242000.00, 'customer', 68, 'Down Payment for Chassis: M615G72380002665', 'down_payment', 46, 242000.00, '2026-07-11 10:47:25');

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `id` int(11) NOT NULL,
  `model_code` varchar(50) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `short_code` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
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
(7, 'E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S', NULL, '2026-04-20 08:56:23', '120', ''),
(8, 'E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro', NULL, '2026-04-20 08:56:23', NULL, NULL),
(9, 'M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6', 'uploads/models/model_9_m6_k6_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(10, 'M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP', 'uploads/models/model_10_m6_np_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(11, 'M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L', 'uploads/models/model_11_m6_lithium_np_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(12, 'Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium', 'uploads/models/model_12_premium_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(13, 'W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike', 'uploads/models/model_13_w_bike_h2_electric_bike.webp', '2026-04-20 08:56:23', NULL, NULL),
(14, 'SP12', 'Super Star 70', 'Electric Bike', '123', 'uploads/bike_6a0310a76dc22.webp', '2026-05-07 06:34:51', '200', '120'),
(15, 'Pak Star', 'E8S Mountain', 'Electric Bike', '', NULL, '2026-06-13 06:33:11', '', ''),
(16, 'WONDER BIKE H2', 'W Bike h2', 'Electric Bike', '', NULL, '2026-06-13 08:08:26', '', ''),
(17, 'E8s', 'E8S', 'Electric Bike', '', NULL, '2026-06-13 08:51:07', '', ''),
(18, 'PREMUM', 'PREMUM', 'Electric Bike', '', NULL, '2026-06-13 09:03:41', '', ''),
(19, 'T9 PR0', 'T9 PR0', 'Electric Bike', '', NULL, '2026-06-13 09:11:10', '', ''),
(20, 'T9 PR0', 'T9 PR0', 'Electric Bike', '', NULL, '2026-06-13 09:11:11', '', ''),
(21, 'DABANG', 'DABANG', 'Electric Bike', '', NULL, '2026-06-13 11:17:50', '', ''),
(22, 'Metro', 'Dabang LFP', 'Electric Bike', '', NULL, '2026-06-15 12:43:03', '', ''),
(23, 'Metro', 'Malrix', 'Electric Bike', '7', NULL, '2026-06-16 05:59:04', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `money_destinations`
--

CREATE TABLE `money_destinations` (
  `id` int(11) NOT NULL,
  `type` enum('bank','person','wallet') NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `account_title` varchar(255) DEFAULT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `money_destinations`
--

INSERT INTO `money_destinations` (`id`, `type`, `name`, `details`, `account_title`, `account_no`, `branch`, `opening_balance`, `contact_person`, `contact_phone`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 'bank', 'HBL Micro Finance', '', '', '', 'Dera Ismail Khan', 10000.00, '', '', 1, '2026-07-09 17:47:45', '2026-07-09 17:56:33'),
(11, 'bank', 'Meezan Bank Limited', '', 'BNIEnterprizes', '', 'Gulbahar Peshawar', 0.00, '', '', 1, '2026-07-09 17:48:44', '2026-07-09 17:48:44');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_type` enum('cash','cheque','bank_transfer','online','other') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `status` enum('pending','cleared','bounced','cancelled') DEFAULT 'pending',
  `transaction_type` enum('purchase','sale','installment','expense_payment','supplier_payment','customer_refund','customer_advance','supplier_refund') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_date`, `payment_type`, `amount`, `cheque_number`, `bank_name`, `cheque_date`, `status`, `transaction_type`, `reference_id`, `party_name`, `notes`, `created_at`) VALUES
(36, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 20, 'Pak Star', '', '2026-06-13 05:49:38'),
(37, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 21, 'Pak Star', '', '2026-06-13 05:55:32'),
(38, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 22, 'Pak Star', '', '2026-06-13 06:01:41'),
(39, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 25, 'Pak Star', '', '2026-06-13 06:06:39'),
(40, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 26, 'Pak Star', '', '2026-06-13 06:16:30'),
(41, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 27, 'Pak Star', '', '2026-06-13 06:20:35'),
(42, '2026-03-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 28, 'Pak Star', '', '2026-06-13 06:28:00'),
(43, '2026-03-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 29, 'Pak Star', '', '2026-06-13 06:33:26'),
(44, '2026-03-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 30, 'Pak Star', '', '2026-06-13 06:34:11'),
(45, '2026-03-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 31, 'Pak Star', '', '2026-06-13 06:35:10'),
(46, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 32, 'Pak Star', '', '2026-06-13 06:40:48'),
(47, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 33, 'Pak Star', '', '2026-06-13 06:45:27'),
(48, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 34, 'Pak Star', '', '2026-06-13 06:51:28'),
(49, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 35, 'Pak Star', '', '2026-06-13 06:55:51'),
(50, '2026-03-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 36, 'Pak Star', '', '2026-06-13 07:12:19'),
(51, '2026-02-05', 'cash', 2000000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 37, 'Pak Star', '', '2026-06-13 07:15:16'),
(52, '2026-02-05', 'cash', 2000000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 38, 'Pak Star', '', '2026-06-13 07:16:14'),
(53, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 39, 'Pak Star', '', '2026-06-13 08:03:36'),
(54, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 40, 'Pak Star', '', '2026-06-13 08:08:45'),
(55, '2026-02-05', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 41, 'Pak Star', '', '2026-06-13 08:11:02'),
(56, '2026-03-03', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 42, 'Pak Star', '', '2026-06-13 08:16:03'),
(57, '2026-03-09', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 43, 'Pak Star', '', '2026-06-13 08:18:22'),
(58, '2026-03-16', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 44, 'Pak Star', '', '2026-06-13 08:21:19'),
(59, '2026-03-16', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 45, 'Pak Star', '', '2026-06-13 08:23:04'),
(60, '2026-03-16', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 46, 'Pak Star', '', '2026-06-13 08:25:29'),
(61, '2026-03-16', 'cash', 2000000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 47, 'Pak Star', '', '2026-06-13 08:41:31'),
(62, '2026-03-16', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 48, 'Pak Star', '', '2026-06-13 08:43:53'),
(63, '2026-03-25', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 49, 'Pak Star', '', '2026-06-13 08:47:32'),
(64, '2026-06-25', 'cash', 2000000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 52, 'Pak Star', '', '2026-06-13 08:54:46'),
(65, '2026-03-25', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 53, 'Pak Star', '', '2026-06-13 08:56:33'),
(66, '2026-03-25', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 54, 'Pak Star', '', '2026-06-13 08:58:25'),
(67, '2026-03-25', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 55, 'Pak Star', '', '2026-06-13 09:04:29'),
(68, '2026-03-25', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 56, 'Pak Star', '', '2026-06-13 09:07:37'),
(69, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 57, 'Pak Star', '', '2026-06-13 09:11:44'),
(70, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 58, 'Pak Star', '', '2026-06-13 09:13:18'),
(71, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 59, 'Pak Star', '', '2026-06-13 09:30:09'),
(72, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 60, 'Pak Star', '', '2026-06-13 09:36:50'),
(73, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 62, 'Pak Star', '', '2026-06-13 10:22:24'),
(74, '2026-04-04', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 63, 'Pak Star', '', '2026-06-13 10:30:19'),
(75, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 64, 'Pak Star', '', '2026-06-13 10:36:04'),
(76, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 65, 'Pak Star', '', '2026-06-13 10:41:16'),
(77, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 66, 'Pak Star', '', '2026-06-13 10:48:21'),
(78, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 67, 'Pak Star', '', '2026-06-13 10:53:39'),
(79, '2026-06-13', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 68, 'Pak Star', '', '2026-06-13 11:18:13'),
(80, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 69, 'Pak Star', '', '2026-06-13 11:24:19'),
(81, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 70, 'Pak Star', '', '2026-06-13 11:30:39'),
(82, '2026-04-23', 'cash', 2000000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 71, 'Pak Star', '', '2026-06-13 11:34:37'),
(83, '2026-04-23', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 72, 'Pak Star', '', '2026-06-13 11:37:45'),
(84, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 73, 'Pak Star', '', '2026-06-13 12:55:18'),
(85, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 74, 'Pak Star', '', '2026-06-13 12:58:48'),
(86, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 75, 'Pak Star', '', '2026-06-13 13:00:48'),
(87, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 76, 'Pak Star', '', '2026-06-13 13:02:50'),
(88, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 77, 'Pak Star', '', '2026-06-13 13:05:22'),
(89, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 78, 'Pak Star', '', '2026-06-13 13:07:36'),
(90, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 79, 'Pak Star', '', '2026-06-13 13:21:52'),
(91, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 80, 'Pak Star', '', '2026-06-13 13:22:55'),
(92, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 81, 'Pak Star', '', '2026-06-13 13:24:12'),
(93, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 82, 'Pak Star', '', '2026-06-13 13:25:44'),
(94, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 83, 'Pak Star', '', '2026-06-13 13:33:34'),
(95, '2026-02-21', 'cash', 279000.00, '', '', NULL, 'pending', 'sale', 58, 'Walk-in Customer', 'Down Payment for Chassis: E820G72380002293', '2026-06-14 06:31:44'),
(96, '2026-04-04', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 54, 'Walk-in Customer', 'Down Payment for Chassis: TH12L72300000445', '2026-06-14 06:41:18'),
(97, '2026-02-22', 'cash', 199000.00, '', '', NULL, 'pending', 'sale', 50, 'Walk-in Customer', 'Down Payment for Chassis: TH12G72260005515', '2026-06-14 06:50:37'),
(98, '2026-06-14', 'cash', 195000.00, '', '', NULL, 'pending', 'sale', 51, 'Walk-in Customer', 'Down Payment for Chassis: TH12G72260006004', '2026-06-14 08:39:07'),
(99, '2026-03-10', 'cash', 179000.00, '', '', NULL, 'pending', 'sale', 48, 'Muhammad Tariv', 'Down Payment for Chassis: T910G72260007041', '2026-06-14 10:12:19'),
(100, '2026-03-02', 'cash', 179000.00, '', '', NULL, 'pending', 'sale', 49, 'Noman AkBar', 'Down Payment for Chassis: T910G72260006884', '2026-06-14 10:32:44'),
(101, '2026-03-28', 'cash', 229000.00, '', '', NULL, 'pending', 'sale', 52, 'Muhammad kashif', 'Down Payment for Chassis: T910L72300000632', '2026-06-15 06:51:13'),
(102, '2026-03-07', 'cash', 234000.00, '', '', NULL, 'pending', 'sale', 56, 'Muhammad FaiZan Ali', 'Down Payment for Chassis: T910L72300000916', '2026-06-15 06:54:44'),
(103, '2026-03-12', 'cash', 285000.00, '', '', NULL, 'pending', 'sale', 59, 'Hamaz Khan', 'Down Payment for Chassis: M615L72300006176', '2026-06-15 06:59:10'),
(104, '2026-03-12', 'cash', 283000.00, '', '', NULL, 'pending', 'sale', 60, 'Dr Shabir Ahmed', 'Down Payment for Chassis: M615L72300006278', '2026-06-15 07:02:37'),
(105, '2026-04-04', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 71, 'Muhammad Jahan zaib', 'Down Payment for Chassis: T910G72260008737', '2026-06-15 07:12:07'),
(106, '2026-04-12', 'cash', 277000.00, '', '', NULL, 'pending', 'sale', 72, 'Muhammad idress', 'Down Payment for Chassis: P308L72300000159', '2026-06-15 07:14:23'),
(107, '2026-04-05', 'cash', 265000.00, '', '', NULL, 'pending', 'sale', 75, 'Dr Shabir Ahmed', 'Down Payment for Chassis: T912G72380001156', '2026-06-15 07:15:37'),
(108, '2026-04-05', 'cash', 265000.00, '', '', NULL, 'pending', 'sale', 74, 'Dr Shabir Ahmed', 'Down Payment for Chassis: T912G72380001172', '2026-06-15 07:26:23'),
(109, '2026-04-08', 'cash', 264000.00, '', '', NULL, 'pending', 'sale', 75, 'Imran Khan', 'Down Payment for Chassis: T912G72380001156', '2026-06-15 07:29:42'),
(110, '2026-04-11', 'cash', 232500.00, '', '', NULL, 'pending', 'sale', 77, 'Muhammad ASif', 'Down Payment for Chassis: T910L72300001272', '2026-06-15 07:34:28'),
(111, '2026-04-08', 'cash', 232500.00, '', '', NULL, 'pending', 'sale', 76, 'Waheed ullah', 'Down Payment for Chassis: T910L72300001018', '2026-06-15 07:39:51'),
(112, '2026-04-04', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 70, 'Umer Shehzad', 'Down Payment for Chassis: T910G72260008894', '2026-06-15 07:44:45'),
(113, '2026-03-26', 'cash', 177500.00, '', '', NULL, 'pending', 'sale', 62, 'Ashfaq Ahmed', 'Down Payment for Chassis: T910G72260008478', '2026-06-15 07:57:04'),
(114, '2026-03-18', 'cash', 179000.00, '', '', NULL, 'pending', 'sale', 63, 'Fayyaz Masih', 'Down Payment for Chassis: T910G72260008679', '2026-06-15 08:02:02'),
(115, '2026-04-11', 'cash', 198000.00, '', '', NULL, 'pending', 'sale', 64, 'Hasnain mehmood', 'Down Payment for Chassis: TH12G72260006279', '2026-06-15 08:08:11'),
(116, '2026-03-30', 'cash', 274000.00, '', '', NULL, 'pending', 'sale', 66, 'Muhammad Haseeb Rehman', 'Down Payment for Chassis: E820G72380000466', '2026-06-15 08:45:17'),
(117, '2026-03-25', 'cash', 198000.00, '', '', NULL, 'pending', 'sale', 65, 'Muhammad Alam Mehsud', 'Down Payment for Chassis: TH12G72260006236', '2026-06-15 08:49:22'),
(118, '2026-04-05', 'cash', 235000.00, '', '', NULL, 'pending', 'sale', 73, 'Tanueer khan', 'Down Payment for Chassis: E810G72380000595', '2026-06-15 08:53:18'),
(119, '2026-03-18', 'cash', 246000.00, '', '', NULL, 'pending', 'sale', 55, 'Muhammad Atif Majeed', 'Down Payment for Chassis: TH12L72300000416', '2026-06-15 09:05:44'),
(120, '2026-04-02', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 69, 'Jerryson', 'Down Payment for Chassis: T910G72260008720', '2026-06-15 09:10:40'),
(121, '2026-04-09', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 80, 'Muhammad Sadiv', 'Down Payment for Chassis: TH12L7200001157', '2026-06-15 09:52:22'),
(122, '2026-04-27', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 81, 'Rashid Rauf', 'Down Payment for Chassis: T910G72260010603', '2026-06-15 09:56:27'),
(123, '2026-04-27', 'cash', 300000.00, '', '', NULL, 'pending', 'sale', 84, 'Sher Afghan', 'Down Payment for Chassis: M615L72300001539', '2026-06-15 10:47:32'),
(124, '2026-05-24', 'cash', 190000.00, '', '', NULL, 'pending', 'sale', 85, 'Sheikh Qaiser Hayal', 'Down Payment for Chassis: DB12G72260004432', '2026-06-15 10:54:44'),
(125, '2026-05-02', 'cash', 249000.00, '', '', NULL, 'pending', 'sale', 89, 'Qudral Ullah', 'Down Payment for Chassis: TH12L72300002097', '2026-06-15 11:02:22'),
(126, '2026-04-27', 'cash', 252000.00, '', '', NULL, 'pending', 'sale', 88, 'Abdul Basit', 'Down Payment for Chassis: TH12L72300002298', '2026-06-15 11:05:31'),
(127, '2026-04-08', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 79, 'Farid ullah', 'Down Payment for Chassis: TH12L7200001147', '2026-06-15 11:11:57'),
(128, '2026-06-10', 'cash', 286000.00, '', '', NULL, 'pending', 'sale', 99, 'Zahid khan', 'Down Payment for Chassis: M615L72300002311', '2026-06-15 11:14:59'),
(129, '2026-05-24', 'cash', 189000.00, '', '', NULL, 'pending', 'sale', 100, 'Ali Ammar', 'Down Payment for Chassis: DB12G72260004159', '2026-06-15 11:16:44'),
(130, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 90, 'Pak Star', '', '2026-06-15 12:46:13'),
(131, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 91, 'Pak Star', '', '2026-06-15 12:49:00'),
(132, '2026-05-24', 'cash', 288000.00, '', '', NULL, 'pending', 'sale', 92, 'Hashmat Ali', 'Down Payment for Chassis: M615L72300004039', '2026-06-15 13:10:20'),
(133, '2026-05-24', 'cash', 288000.00, '', '', NULL, 'pending', 'sale', 94, 'Muhammad ASif Saleem', 'Down Payment for Chassis: M615L72300004264', '2026-06-15 13:15:05'),
(134, '2026-06-10', 'cash', 261000.00, '', '', NULL, 'pending', 'sale', 101, 'Malik Muhammad Jauid', 'Down Payment for Chassis: TH12L72300001015', '2026-06-15 13:24:49'),
(135, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 92, 'Pak Star', '', '2026-06-16 05:59:15'),
(136, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 93, 'Pak Star', '', '2026-06-16 06:03:37'),
(137, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 94, 'Pak Star', '', '2026-06-16 06:06:01'),
(138, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 95, 'Pak Star', '', '2026-06-16 06:09:02'),
(139, '2026-04-27', 'cash', 200000.00, '', '', NULL, 'pending', 'sale', 86, 'Karim Khan', 'Down Payment for Chassis: TH12G72260007145', '2026-06-16 06:12:45'),
(140, '2026-05-05', 'cash', 200000.00, '', '', NULL, 'pending', 'sale', 87, 'Muhammad Akhtar', 'Down Payment for Chassis: TH12G72260007314', '2026-06-16 06:15:19'),
(141, '2026-05-26', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 109, 'Fazal Urrehman', 'Down Payment for Chassis: MX20G72380000538', '2026-06-16 06:17:50'),
(142, '2026-04-27', 'cash', 230000.00, '', '', NULL, 'pending', 'sale', 83, 'Muhammad Mujtaba', 'Down Payment for Chassis: T910L72300002152', '2026-06-16 06:21:21'),
(143, '2026-06-01', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 110, 'Muhammad Ali Hasnain', 'Down Payment for Chassis: MX20G72380000463', '2026-06-16 06:23:14'),
(144, '2026-05-25', 'cash', 247000.00, '', '', NULL, 'pending', 'sale', 111, 'Muhammad Khalid Raza', 'Down Payment for Chassis: MX20G72380000660', '2026-06-16 06:25:05'),
(145, '2026-05-25', 'cash', 247000.00, '', '', NULL, 'pending', 'sale', 112, 'Muhammad ASif', 'Down Payment for Chassis: MX20G72380000567', '2026-06-16 06:26:31'),
(146, '2026-05-24', 'cash', 288000.00, '', '', NULL, 'pending', 'sale', 96, 'Muhammad Abu Huaira', 'Down Payment for Chassis: M615L72300004278', '2026-06-16 06:32:41'),
(147, '2026-06-09', 'cash', 263000.00, '', '', NULL, 'pending', 'sale', 102, 'Yasir Wazir', 'Down Payment for Chassis: TH12L72300000747', '2026-06-16 06:38:34'),
(148, '2026-04-29', 'cash', 230000.00, '', '', NULL, 'pending', 'sale', 82, 'Muhammad zakir', 'Down Payment for Chassis: T910L72300002099', '2026-06-16 06:49:38'),
(149, '2026-06-13', 'cash', 263000.00, '', '', NULL, 'pending', 'sale', 103, 'Sattar Shah', 'Down Payment for Chassis: TH12L72300000520', '2026-06-16 07:55:39'),
(150, '2026-06-16', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 61, 'Ahmed din', 'Down Payment for Chassis: T910G72260008882', '2026-06-16 07:58:16'),
(151, '2026-04-13', 'cash', 139000.00, '', '', NULL, 'pending', 'sale', 44, 'Shafi Ullah', 'Down Payment for Chassis: LY05G48270002304', '2026-06-16 08:00:16'),
(152, '2026-05-24', 'cash', 143000.00, '', '', NULL, 'pending', 'sale', 45, 'Muhammad Rizwan', 'Down Payment for Chassis: LY05G48270002202', '2026-06-16 08:02:18'),
(153, '2026-03-05', 'cash', 178000.00, '', '', NULL, 'pending', 'sale', 47, 'Burhan khan', 'Down Payment for Chassis: T910G72260006966', '2026-06-16 08:04:08'),
(154, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 96, 'Pak Star', '', '2026-06-16 08:57:00'),
(155, '2026-05-20', 'cash', 20000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 97, 'Pak Star', '', '2026-06-16 09:13:58'),
(156, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 98, 'Pak Star', '', '2026-06-16 09:18:23'),
(157, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 99, 'Pak Star', '', '2026-06-16 09:19:46'),
(158, '2026-05-31', 'cash', 186500.00, '', '', NULL, 'pending', 'sale', 114, 'Muhammad Anwar ul hav', 'Down Payment for Chassis: DB12G72260004208', '2026-06-16 09:39:37'),
(159, '2026-06-08', 'cash', 250000.00, '', '', NULL, 'pending', 'sale', 115, 'Muhammad jamil', 'Down Payment for Chassis: DB12L72300005310', '2026-06-16 09:46:29'),
(160, '2026-05-24', 'cash', 238000.00, '', '', NULL, 'pending', 'sale', 113, 'Haseeb Bilal', 'Down Payment for Chassis: DB12L72300005119', '2026-06-16 09:49:34'),
(161, '2026-05-24', 'cash', 238000.00, '', '', NULL, 'pending', 'sale', 116, 'Muhammad Bilal', 'Down Payment for Chassis: DB12L72300005366', '2026-06-16 11:10:52'),
(162, '2026-05-20', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 100, 'Pak Star', '', '2026-06-16 11:38:07'),
(163, '2026-05-24', 'cash', 286000.00, '', '', NULL, 'pending', 'sale', 117, 'Tahir Ahmed Wasil', 'Down Payment for Chassis: M615L72300004014', '2026-06-16 11:41:10'),
(164, '2026-06-21', 'cash', 288000.00, '', '', NULL, 'pending', 'sale', 93, 'Dr Shabir Ahmed', 'Down Payment for Chassis: M615L72300004251', '2026-06-22 07:31:49'),
(165, '2026-06-14', 'cash', 100000.00, '', 'HBL', NULL, 'pending', 'sale', 90, 'Muneer javid', 'Down Payment for Chassis: T912G72380001154', '2026-06-22 07:40:58'),
(166, '2026-06-21', 'cash', 287000.00, '', '', NULL, 'pending', 'sale', 91, 'Muhammad Humam', 'Down Payment for Chassis: M615L72300004400', '2026-06-27 07:25:06'),
(167, '2026-06-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 101, 'Pak Star', '', '2026-07-07 11:13:31'),
(168, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 102, 'Pak Star', '', '2026-07-07 11:15:30'),
(169, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 103, 'Pak Star', '', '2026-07-07 11:20:58'),
(170, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 104, 'Pak Star', '', '2026-07-07 11:24:34'),
(171, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 105, 'Pak Star', '', '2026-07-07 11:26:27'),
(172, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 106, 'Pak Star', '', '2026-07-07 11:29:46'),
(173, '2026-07-07', 'cash', 265000.00, '', '', NULL, 'pending', 'customer_refund', 93, 'Dr Shabir Ahmed', '', '2026-07-07 18:07:39'),
(174, '2026-07-07', 'cash', 17000.00, '', '', NULL, 'pending', 'sale', 121, 'Dr Shabir Ahmed', 'Down Payment for Chassis: E820G72380002364', '2026-07-08 07:32:15'),
(175, '2026-06-30', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 107, 'Pak Star', '', '2026-07-08 09:35:38'),
(176, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 108, 'Pak Star', '', '2026-07-08 09:38:16'),
(177, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 109, 'Pak Star', '', '2026-07-08 09:41:05'),
(178, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 110, 'Pak Star', '', '2026-07-08 09:44:32'),
(179, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 111, 'Pak Star', '', '2026-07-08 09:47:39'),
(180, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 112, 'Pak Star', '', '2026-07-08 09:50:52'),
(181, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 113, 'Pak Star', '', '2026-07-08 09:52:45'),
(182, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 114, 'Pak Star', '', '2026-07-08 09:55:27'),
(183, '2026-07-02', 'cash', 200000.00, NULL, NULL, NULL, 'pending', 'supplier_payment', 115, 'Pak Star', '', '2026-07-08 09:57:46'),
(184, '2026-07-04', 'cash', 182000.00, '', '', NULL, 'pending', 'sale', 124, 'M Sharif', 'Down Payment for Chassis: T910G72260011701', '2026-07-08 11:31:37'),
(185, '2026-07-05', 'cash', 187000.00, '', '', NULL, 'pending', 'sale', 131, 'M ilyas khan', 'Down Payment for Chassis: DB12G72260004600', '2026-07-08 11:34:11'),
(186, '2026-07-08', 'cash', 182000.00, '', '', NULL, 'pending', 'sale', 118, 'Nighat Shaheen', 'Down Payment for Chassis: T910G72260012197', '2026-07-08 11:37:17'),
(187, '2026-03-15', 'cash', 242000.00, '', '', NULL, 'pending', 'sale', 46, 'M Zain ul abideen', 'Down Payment for Chassis: M615G72380002665', '2026-07-11 10:47:25');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `order_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_units` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `order_date`, `supplier_id`, `total_units`, `total_amount`, `notes`, `created_at`) VALUES
(20, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 05:49:38'),
(21, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 05:55:32'),
(22, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:01:41'),
(23, '2026-02-05', 8, 1, 0.00, '', '2026-06-13 06:04:32'),
(24, '2026-02-05', 8, 1, 0.00, '', '2026-06-13 06:04:36'),
(25, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:06:38'),
(26, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:16:30'),
(27, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:20:34'),
(28, '2026-03-05', 8, 2, 100000.00, '', '2026-06-13 06:28:00'),
(29, '2026-03-05', 8, 1, 0.00, '', '2026-06-13 06:33:26'),
(30, '2026-03-05', 8, 1, 0.00, '', '2026-06-13 06:34:11'),
(31, '2026-03-05', 8, 1, 0.00, '', '2026-06-13 06:35:10'),
(32, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:40:47'),
(33, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:45:27'),
(34, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:51:28'),
(35, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 06:55:51'),
(36, '2026-03-05', 8, 1, 200000.00, '', '2026-06-13 07:12:19'),
(37, '2026-02-05', 8, 2, 2000000.00, '', '2026-06-13 07:15:16'),
(38, '2026-02-05', 8, 1, 0.00, '', '2026-06-13 07:16:14'),
(39, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 08:03:36'),
(40, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 08:08:45'),
(41, '2026-02-05', 8, 1, 200000.00, '', '2026-06-13 08:11:02'),
(42, '2026-03-03', 8, 1, 200000.00, '', '2026-06-13 08:16:03'),
(43, '2026-03-09', 8, 1, 200000.00, '', '2026-06-13 08:18:22'),
(44, '2026-03-16', 8, 1, 200000.00, '', '2026-06-13 08:21:19'),
(45, '2026-03-16', 8, 1, 200000.00, '', '2026-06-13 08:23:04'),
(46, '2026-03-16', 8, 1, 200000.00, '', '2026-06-13 08:25:29'),
(47, '2026-03-16', 8, 1, 2000000.00, '', '2026-06-13 08:41:31'),
(48, '2026-03-16', 8, 1, 200000.00, '', '2026-06-13 08:43:53'),
(49, '2026-03-25', 8, 1, 200000.00, '', '2026-06-13 08:47:32'),
(52, '2026-06-25', 8, 1, 2000000.00, '', '2026-06-13 08:54:46'),
(53, '2026-03-25', 8, 1, 200000.00, '', '2026-06-13 08:56:33'),
(54, '2026-03-25', 8, 1, 200000.00, '', '2026-06-13 08:58:25'),
(55, '2026-03-25', 8, 1, 200000.00, '', '2026-06-13 09:04:29'),
(56, '2026-03-25', 8, 1, 200000.00, '', '2026-06-13 09:07:37'),
(57, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 09:11:44'),
(58, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 09:13:18'),
(59, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 09:30:09'),
(60, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 09:36:50'),
(62, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 10:22:24'),
(63, '2026-04-04', 8, 1, 200000.00, '', '2026-06-13 10:30:19'),
(64, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 10:36:04'),
(65, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 10:41:16'),
(66, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 10:48:21'),
(67, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 10:53:39'),
(68, '2026-06-13', 8, 1, 200000.00, '', '2026-06-13 11:18:13'),
(69, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 11:24:19'),
(70, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 11:30:39'),
(71, '2026-04-23', 8, 1, 2000000.00, '', '2026-06-13 11:34:36'),
(72, '2026-04-23', 8, 1, 200000.00, '', '2026-06-13 11:37:45'),
(73, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 12:55:18'),
(74, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 12:58:48'),
(75, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:00:48'),
(76, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:02:50'),
(77, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:05:22'),
(78, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:07:36'),
(79, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:21:52'),
(80, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:22:55'),
(81, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:24:12'),
(82, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:25:44'),
(83, '2026-05-20', 8, 1, 200000.00, '', '2026-06-13 13:33:34'),
(84, '2026-05-20', 8, 1, 200000.00, '', '2026-06-15 12:31:39'),
(85, '2026-05-20', 8, 1, 200000.00, '', '2026-06-15 12:34:23'),
(86, '2026-05-20', 8, 2, 200000.00, '', '2026-06-15 12:35:48'),
(89, '2026-05-20', 8, 1, 200000.00, '', '2026-06-15 12:43:34'),
(90, '2026-05-20', 8, 1, 200000.00, '', '2026-06-15 12:46:13'),
(91, '2026-05-20', 8, 1, 200000.00, '', '2026-06-15 12:49:00'),
(92, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 05:59:15'),
(93, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 06:03:37'),
(94, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 06:06:01'),
(95, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 06:09:02'),
(96, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 08:57:00'),
(97, '2026-05-20', 8, 1, 20000.00, '', '2026-06-16 09:13:58'),
(98, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 09:18:23'),
(99, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 09:19:46'),
(100, '2026-05-20', 8, 1, 200000.00, '', '2026-06-16 11:38:07'),
(101, '2026-06-02', 8, 1, 200000.00, '', '2026-07-07 11:13:31'),
(102, '2026-07-02', 8, 1, 200000.00, '', '2026-07-07 11:15:30'),
(103, '2026-07-02', 8, 1, 200000.00, '', '2026-07-07 11:20:58'),
(104, '2026-07-02', 8, 1, 200000.00, '', '2026-07-07 11:24:34'),
(105, '2026-07-02', 8, 1, 200000.00, '', '2026-07-07 11:26:27'),
(106, '2026-07-02', 8, 1, 200000.00, '', '2026-07-07 11:29:46'),
(107, '2026-06-30', 8, 1, 200000.00, '', '2026-07-08 09:35:38'),
(108, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:38:16'),
(109, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:41:05'),
(110, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:44:32'),
(111, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:47:39'),
(112, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:50:52'),
(113, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:52:45'),
(114, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:55:27'),
(115, '2026-07-02', 8, 1, 200000.00, '', '2026-07-08 09:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quote_date` date NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `bike_id` int(11) DEFAULT NULL,
  `accessories_json` text DEFAULT NULL,
  `quoted_price` decimal(15,2) NOT NULL,
  `is_installment` tinyint(1) DEFAULT 0,
  `down_payment` decimal(15,2) DEFAULT 0.00,
  `total_installments` int(11) DEFAULT 0,
  `installment_amount` decimal(15,2) DEFAULT 0.00,
  `valid_until` date DEFAULT NULL,
  `status` enum('pending','accepted','rejected','converted') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','sent','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `page` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
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
(88, 2, 'dashboard', 1, 1, 1, 1),
(89, 2, 'inventory', 1, 1, 1, 1),
(90, 2, 'purchase', 1, 1, 1, 1),
(91, 2, 'sale', 1, 1, 1, 0),
(92, 2, 'customers', 0, 0, 0, 0),
(93, 2, 'suppliers', 1, 1, 1, 1),
(94, 2, 'models', 1, 1, 1, 1),
(95, 2, 'reports', 0, 0, 0, 0),
(96, 2, 'returns', 1, 1, 1, 0),
(97, 2, 'payments', 0, 0, 0, 0),
(98, 2, 'settings', 0, 0, 0, 0),
(99, 2, 'roles', 0, 0, 0, 0),
(100, 2, 'users', 1, 0, 0, 0),
(101, 2, 'income_expense', 1, 1, 1, 1),
(102, 2, 'accessories', 0, 0, 0, 0),
(103, 2, 'quotations', 0, 0, 0, 0),
(104, 2, 'installments', 1, 1, 1, 1),
(105, 2, 'money_destinations', 0, 0, 0, 0),
(106, 2, 'money_tracking', 0, 0, 0, 0),
(107, 1, 'dashboard', 1, 1, 1, 1),
(108, 1, 'inventory', 1, 1, 1, 1),
(109, 1, 'purchase', 1, 1, 1, 1),
(110, 1, 'sale', 1, 1, 1, 1),
(111, 1, 'customers', 1, 1, 1, 1),
(112, 1, 'suppliers', 1, 1, 1, 1),
(113, 1, 'models', 1, 1, 1, 1),
(114, 1, 'reports', 1, 1, 1, 1),
(115, 1, 'returns', 1, 1, 1, 1),
(116, 1, 'payments', 1, 1, 1, 1),
(117, 1, 'settings', 1, 1, 1, 1),
(118, 1, 'roles', 1, 1, 1, 1),
(119, 1, 'users', 1, 1, 1, 1),
(120, 1, 'income_expense', 1, 1, 1, 1),
(121, 1, 'accessories', 1, 1, 1, 1),
(122, 1, 'quotations', 1, 1, 1, 1),
(123, 1, 'installments', 1, 1, 1, 1),
(124, 1, 'money_destinations', 0, 0, 0, 0),
(125, 1, 'money_tracking', 0, 0, 0, 0),
(126, 1, 'bank_deposits', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sale_accessories`
--

CREATE TABLE `sale_accessories` (
  `id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `accessory_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `final_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_money_allocations`
--

CREATE TABLE `sale_money_allocations` (
  `id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `allocation_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_money_allocations`
--

INSERT INTO `sale_money_allocations` (`id`, `bike_id`, `destination_id`, `amount`, `allocation_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 50, 10, 199000.00, '2026-02-24', '8100589', 1, '2026-07-09 17:58:46', '2026-07-11 09:20:17'),
(3, 49, 10, 179000.00, '2026-03-03', '', 1, '2026-07-11 07:11:17', '2026-07-11 07:11:17'),
(4, 56, 10, 234000.00, '2026-03-09', '8064977', 1, '2026-07-11 07:13:38', '2026-07-11 07:18:22'),
(5, 47, 10, 178000.00, '2026-03-06', '8064968', 1, '2026-07-11 07:23:50', '2026-07-11 07:23:50'),
(6, 48, 10, 179000.00, '2026-03-11', '8664992', 1, '2026-07-11 07:26:20', '2026-07-11 07:26:20'),
(9, 58, 10, 279000.00, '2026-02-24', '8100589', 1, '2026-07-11 09:29:36', '2026-07-11 09:29:36'),
(10, 60, 11, 283000.00, '2026-03-16', '3201691 M6 Siver', 1, '2026-07-11 09:36:11', '2026-07-11 09:36:11'),
(11, 59, 11, 285000.00, '2026-03-16', 'M6 LFP Black 3201691', 1, '2026-07-11 09:38:13', '2026-07-11 09:38:13'),
(12, 46, 11, 242000.00, '2026-03-16', 'M6 Silver 3201691', 1, '2026-07-11 13:59:43', '2026-07-11 13:59:43'),
(13, 55, 11, 246000.00, '2026-03-18', 'Thrill Pro Grey 0416', 1, '2026-07-11 14:09:34', '2026-07-11 14:09:34'),
(14, 63, 11, 179000.00, '2026-03-18', 'T9 Sports Grey 8679', 1, '2026-07-11 14:11:15', '2026-07-11 14:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'company_name', 'BNI Enterprises'),
(2, 'branch_name', 'Dera (Ahmed Metro)'),
(3, 'tax_rate', '0.01'),
(4, 'currency', 'Rs.'),
(5, 'tax_on', 'selling_price'),
(6, 'theme', 'light'),
(7, 'admin_password', '$2y$10$8348koW6nh9Q5tigyeHj7.P7PMnTxPbWb7hM8P1mtS.k8sfUsguU.'),
(8, 'show_purchase_on_invoice', '0'),
(17, 'session_timeout_idle', '24000'),
(18, 'session_timeout_absolute', '28800'),
(19, 'landing_hero_title', 'Experience the Future of Mobility'),
(20, 'landing_hero_subtitle', 'Premium Electric Bikes for a Greener Tomorrow'),
(21, 'company_address', 'Opposite WENSAM college D.I Khan'),
(22, 'company_map_iframe', 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d2679.7450456256533!2d70.8899272756232!3d31.810894074082274!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMzHCsDQ4JzM5LjIiTiA3MMKwNTMnMzMuMCJF!5e1!3m2!1sen!2s!4v1780484592678!5m2!1sen!2s'),
(23, 'company_whatsapp', '923499222411, 923309313131'),
(24, 'company_email', 'info@gobuykar.com'),
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
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact`, `address`, `created_at`) VALUES
(8, 'Pak Star', '', '', '2026-06-13 05:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$r2A4TgWoYu6TojSVaf/Se.PfYop4XzPcjU2q7aO2y5l4L2yXAU9d.', 'System Administrator', 1, 1, '2026-04-20 10:50:00'),
(5, 'Murtaza', '$2y$10$rjh0LGWhRC3.r/U1WCe0Xe5ayZcNPRzMrziTJkHeKt1rVU9YfQibS', 'Murtaza Khan', 2, 0, '2026-07-06 07:05:48');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bank_deposits`
--
ALTER TABLE `bank_deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `bike_requests`
--
ALTER TABLE `bike_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cheque_register`
--
ALTER TABLE `cheque_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `deposit_allocations`
--
ALTER TABLE `deposit_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `income_expenses`
--
ALTER TABLE `income_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `leadership`
--
ALTER TABLE `leadership`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `money_destinations`
--
ALTER TABLE `money_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `sale_accessories`
--
ALTER TABLE `sale_accessories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sale_money_allocations`
--
ALTER TABLE `sale_money_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bank_deposits`
--
ALTER TABLE `bank_deposits`
  ADD CONSTRAINT `bank_deposits_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`),
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
  ADD CONSTRAINT `deposit_allocations_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`);

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
  ADD CONSTRAINT `sale_accessories_ibfk_2` FOREIGN KEY (`accessory_id`) REFERENCES `accessories` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `sale_money_allocations`
--
ALTER TABLE `sale_money_allocations`
  ADD CONSTRAINT `sale_money_allocations_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_money_allocations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`) ON DELETE NO ACTION,
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
