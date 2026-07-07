-- BNI Enterprises Full Database Backup
-- Generated: 2026-07-07 11:30:21
-- Author: Yasin Ullah

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;
SET AUTOCOMMIT=0;
START TRANSACTION;

-- --------------------------------------------
-- Table: `accessories`
-- --------------------------------------------
DROP TABLE IF EXISTS `accessories`;
CREATE TABLE `accessories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT '0.00',
  `selling_price` decimal(15,2) DEFAULT '0.00',
  `current_stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `accessories` (`id`,`name`,`sku`,`purchase_price`,`selling_price`,`current_stock`,`created_at`,`updated_at`) VALUES ('1','Type','CST-1777618962-70','0.00','0.00','-1','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `accessories` (`id`,`name`,`sku`,`purchase_price`,`selling_price`,`current_stock`,`created_at`,`updated_at`) VALUES ('2','Helmet','1231','900.00','1200.00','90','2026-05-07 11:49:31','2026-05-07 11:49:31');
INSERT INTO `accessories` (`id`,`name`,`sku`,`purchase_price`,`selling_price`,`current_stock`,`created_at`,`updated_at`) VALUES ('3','Helmet','CST-1778136637-40','0.00','0.00','0','2026-05-07 11:50:37','2026-05-07 11:50:37');

-- --------------------------------------------
-- Table: `bank_deposits`
-- --------------------------------------------
DROP TABLE IF EXISTS `bank_deposits`;
CREATE TABLE `bank_deposits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destination_id` int NOT NULL,
  `deposit_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `deposit_type` enum('cash','cheque','transfer','online','other') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `deposited_by` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `destination_id` (`destination_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `bank_deposits_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bank_deposits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------
-- Table: `bike_requests`
-- --------------------------------------------
DROP TABLE IF EXISTS `bike_requests`;
CREATE TABLE `bike_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_details` text,
  `status` enum('pending','contacted','fulfilled','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------
-- Table: `bikes`
-- --------------------------------------------
DROP TABLE IF EXISTS `bikes`;
CREATE TABLE `bikes` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chassis_number` (`chassis_number`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_selling_date` (`selling_date`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_bikes_status` (`status`),
  KEY `idx_bikes_model` (`model_id`),
  KEY `idx_bikes_customer` (`customer_id`),
  CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bikes_ibfk_3` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('1','1','2026-02-03','2026-02-05','LY05G48270002304','*XRLY48052125D0002228*','1','Black','125225.00','210000.00','2026-05-06',NULL,'12522.50','72252.50','sold',NULL,NULL,NULL,NULL,'',NULL,'2026-04-20 14:01:57','2026-05-06 21:38:41');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('2','1','2026-02-03','2026-02-05','LY05G48270002202','*XRLY48052125D0002322*','1','Grey','125225.00',NULL,NULL,NULL,'125.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('3','1','2026-02-03','2026-02-05','DD35G48130001177','*48V350WA8T454708922*','13','Black','94595.00','130000.00','2026-04-28','5','9459.50','25945.50','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_13_w_bike_h2_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('4','1','2026-02-03','2026-02-05','M615G72380002665','A9A756800994','9','Silver','220721.00','242000.00',NULL,NULL,'221.00','0.00','returned',NULL,NULL,NULL,NULL,'Returned on 200,000 Cheque to be issued.','uploads/models/model_9_m6_k6_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('5','1','2026-02-03','2026-02-05','T910G72260006966','*XR9S72102825N0007369*','2','Red','161261.00','179000.00','2026-03-05',NULL,'161.00','17578.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('6','1','2026-02-03','2026-02-05','T910G72260007041','*XR9S72102825N0007701*','2','Black','161261.00','179000.00',NULL,NULL,'161.00','17578.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('7','1','2026-02-03','2026-02-05','T910G72260006884','*XR9S72102825N0007393*','2','Grey','161261.00','179000.00','2026-03-02',NULL,'161.00','17578.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('8','1','2026-02-03','2026-02-05','E820G72380002293','*PJE872203525N0002160*','7','Grey','251351.00','279000.00','2026-02-23',NULL,'251.00','27398.00','sold',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('9','1','2026-02-03','2026-02-05','TH12G72260005515','AIMTP721240259005364','5','Grey','179279.00','199000.00','2026-02-22',NULL,'179.00','19542.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_5_thrill_pro_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('10','1','2026-02-03','2026-02-05','TH12G72260006004','AIMTP721240259006297','5','Black','179279.00','200000.00','2026-05-06',NULL,'17927.90','2793.10','returned','2026-05-07','0.00','30000',NULL,'','uploads/models/model_5_thrill_pro_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('11','1','2026-02-03','2026-02-05','T910L72300000632','*XR9S7210282500000640*','3','Silver','193694.00',NULL,NULL,NULL,'194.00','0.00','damaged_lost',NULL,NULL,NULL,'','','uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('12','1','2026-02-03','2026-02-05','T910L72300000916','*XR9S7210282500000927*','3','Black','193694.00','234000.00','2026-03-07',NULL,'194.00','40112.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('13','1','2026-02-03','2026-02-05','TH12L72300000445','AIMTP72124025N001005','6','Black','211712.00',NULL,NULL,NULL,'212.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_6_thrill_pro_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('14','1','2026-02-03','2026-02-05','TH12L72300000416','AIMTP72124025N001176','6','Grey','211712.00','246000.00','2026-03-18',NULL,'212.00','34076.00','sold',NULL,NULL,NULL,NULL,'(2,470,276) Received','uploads/models/model_6_thrill_pro_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('15','2','2026-02-27','2026-03-12','M615L72300006176','XRM672153025D0007536','11','Unknown','254955.00','285000.00','2026-03-12',NULL,'285.00','29760.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_11_m6_lithium_np_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('16','2','2026-02-27','2026-03-12','M615L72300006278','XRM672153025D0007499','11','Unknown','254955.00','283000.00','2026-03-12',NULL,'285.00','27760.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_11_m6_lithium_np_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('17','3','2026-03-12','2026-03-16','T910G72260008882','*XR9S72102825D0007890*','4','Red','238739.00','179000.00',NULL,NULL,'239.00','-59978.00','sold',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('18','3','2026-03-12','2026-03-16','T910G72260008478','*XR9S72102825D0007855*','4','Black','238739.00',NULL,NULL,NULL,'239.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('19','3','2026-03-12','2026-03-16','T910G72260008679','*XR9S72102825D0007954*','2','Grey','161261.00','179000.00','2026-03-18',NULL,'179.00','17560.00','sold',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('20','3','2026-03-12','2026-03-16','TH12G72260006279','AIMTP721240259006047','5','Unknown','179279.00',NULL,NULL,NULL,'179.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_5_thrill_pro_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('21','3','2026-03-12','2026-03-16','TH12G72260006236','AIMTP721240259006039','5','Unknown','179279.00',NULL,NULL,NULL,'179.00','0.00','in_stock',NULL,NULL,NULL,NULL,'(997,297) Receiving','uploads/models/model_5_thrill_pro_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('22','4','2026-03-18','2026-03-27','E820G72380000466','12ZW7271327YE*CERR116670C*','8','Blue','247748.00',NULL,NULL,NULL,'247.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('23','4','2026-03-18','2026-03-27','P308L72300000159','PHPM7208352610000422','12','Unknown','234234.00',NULL,NULL,NULL,'234.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_12_premium_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('24','4','2026-03-18','2026-03-27','E810G72380000595','*10ZW7273316YECKTS0000107*','7','Grey','251351.00',NULL,NULL,NULL,'251.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-20 14:01:57','2026-04-20 14:01:57');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('25','4','2026-03-18','2026-03-27','T910G72260008720','*XR9S72102825D0007987*','3','Unknown','193694.00',NULL,NULL,NULL,'194.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('26','4','2026-03-18','2026-03-27','T910G72260008894','*XR9S72102825D0008251*','3','Unknown','193694.00',NULL,NULL,NULL,'194.00','0.00','in_stock',NULL,NULL,NULL,NULL,'Diff ledger= (70,137)+ new delivery','uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('27','4','2026-03-18','2026-03-27','T910G72260008737','*XR9S72102825D0008003*','3','Unknown','193694.00',NULL,NULL,NULL,'194.00','0.00','in_stock',NULL,NULL,NULL,NULL,NULL,'uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 14:01:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('28','5','2026-04-26','2026-04-26','NW-21233','MT-002','7','Red','120000.00','150000.00','2026-04-26','5','120.00','29880.00','returned','2026-04-26','190000.00','Said not appliable','must be charged 100 percent for the first time use','',NULL,'2026-04-26 15:13:52','2026-04-26 15:21:35');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('30','7','2026-04-27','2026-04-27','NW-212331','MT-002','7','Red','190000.00','240000.00','2026-04-28','5','19000.00','31000.00','sold',NULL,NULL,NULL,'must be charged 100 percent for the first time use','',NULL,'2026-04-27 15:25:19','2026-04-28 09:50:16');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('32','9','2026-04-27','2026-04-27','NW-212331a','MT-002','7','Red','190000.00','230000.00','2026-05-02','1','19000.00','21000.00','sold',NULL,NULL,NULL,'must be charged 100 percent for the first time use','',NULL,'2026-04-27 15:29:45','2026-05-01 18:22:14');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('33','10','2026-04-28','2026-04-28','NW-2123353','MT-GT-022','8','Newd','20000.00','60000.00','2026-04-28','1','2000.00','38000.00','sold',NULL,NULL,NULL,'must be charged 100 percent for the first time use a','',NULL,'2026-04-28 09:24:26','2026-04-28 10:08:07');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('34','11','2026-04-28','2026-04-28','NW-212331aa','MT-GT-02a','11','Newda','40000.00','60000.00','2026-04-28','1','4000.00','16000.00','sold',NULL,NULL,NULL,'must be charged 100 percent for the first time use aa','','uploads/models/model_11_m6_lithium_np_electric_bike.webp','2026-04-28 09:29:37','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('35','12','2026-04-28','2026-04-28','NW-21233213','MT-002414','10','Reda','90000.00','130000.00','2026-04-28','5','9000.00','31000.00','sold',NULL,NULL,NULL,'new hai','','uploads/models/model_10_m6_np_electric_bike.webp','2026-04-28 09:33:26','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('36','13','2026-04-28','2026-04-28','NW-21233132','MT-0021231','2','Yellow','90000.00','220000.00','2026-04-28','6','9000.00','121000.00','sold',NULL,NULL,NULL,'theek hai','','uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-28 09:36:57','2026-05-12 14:47:11');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('37','14','2026-05-01','2026-05-01','NW-212335123','MT-GT-02aa','7','Yellow','290000.00','340000.00','2026-05-01','5','29000.00','21000.00','sold',NULL,NULL,NULL,'','','uploads/bike_6a2e70a3860e6.webp','2026-05-01 11:59:16','2026-06-14 14:13:08');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('38','15','2026-05-06','2026-05-06','T929283007','TI8399uue','2','Rad','200000.00','220000.00','2026-05-06',NULL,'20000.00','0.00','sold',NULL,NULL,NULL,'','','uploads/img_69fb71525af01.jpg','2026-05-06 21:50:26','2026-05-06 21:51:52');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('39','16','2026-05-07','2026-05-07','NW-21233123','MT-GT-0223','14','Red','250000.00',NULL,NULL,NULL,'25000.00','0.00','in_stock',NULL,NULL,NULL,'','','uploads/bike_6a0308a7eb3fc.webp','2026-05-07 11:41:42','2026-05-12 16:02:00');
INSERT INTO `bikes` (`id`,`purchase_order_id`,`order_date`,`inventory_date`,`chassis_number`,`motor_number`,`model_id`,`color`,`purchase_price`,`selling_price`,`selling_date`,`customer_id`,`tax_amount`,`margin`,`status`,`return_date`,`return_amount`,`return_notes`,`safeguard_notes`,`notes`,`image`,`created_at`,`updated_at`) VALUES ('40','16','2026-05-07','2026-05-07','qrqwer','12341','14','Yellow','250000.00','320000.00','2026-05-07','2','25000.00','45000.00','sold',NULL,NULL,NULL,'','','uploads/models/model_14_super_star_70.webp','2026-05-07 11:41:42','2026-05-12 14:47:11');

-- --------------------------------------------
-- Table: `cheque_register`
-- --------------------------------------------
DROP TABLE IF EXISTS `cheque_register`;
CREATE TABLE `cheque_register` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('1','03420810','UBL','2026-02-03','2535000.00','payment','cleared','purchase_order','1','Default Supplier','First Order','2026-04-20 14:01:57');
INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('2','03420811','UBL','2026-02-27','509910.00','payment','cleared','purchase_order','2','Default Supplier','Second Order','2026-04-20 14:01:57');
INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('3','03420809','UBL','2026-03-12','1002710.00','payment','cleared','purchase_order','3','Default Supplier','Third Order','2026-04-20 14:01:57');
INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('4','D72981756','Meezan','2026-03-18','1241441.00','payment','cleared','purchase_order','4','Default Supplier','Fourth Order','2026-04-20 14:01:57');
INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('5','CHQ-1123','HBL','2026-04-26','150000.00','receipt','pending','sale','28','Yasin Ullah','','2026-04-26 15:17:45');
INSERT INTO `cheque_register` (`id`,`cheque_number`,`bank_name`,`cheque_date`,`amount`,`type`,`status`,`reference_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('6','CHQ-1123','HBL','2026-04-30','120000.00','payment','cleared','purchase_order','9','Default Supplier','','2026-04-27 15:29:45');

-- --------------------------------------------
-- Table: `customers`
-- --------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `is_filer` tinyint(1) DEFAULT '1',
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('1','Ahmed Ali','0321-1234567','35201-1234567-1','1','Dera Ghazi Khan, Punjab','2026-04-20 13:56:23');
INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('2','Muhammad Usman','0333-7654321','35201-7654321-3','1','Muzaffargarh, Punjab','2026-04-20 13:56:23');
INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('3','Bilal Hussain','0345-9876543','35201-9876543-5','1','Rajanpur, Punjab','2026-04-20 13:56:23');
INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('4','Zafar Iqbal','0312-4567890','35201-4567890-7','1','Layyah, Punjab','2026-04-20 13:56:23');
INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('5','Yasin Ullah','03139842219','11102-0356023-4','1','Post Office Domel District Bannu','2026-04-26 15:16:43');
INSERT INTO `customers` (`id`,`name`,`phone`,`cnic`,`is_filer`,`address`,`created_at`) VALUES ('6','Shams Uddin','03338870707','11102-0356233-4','1','Al-Mandoos Shoes near Qasaban Gate Mazari Mandi Bannu','2026-04-28 09:38:53');

-- --------------------------------------------
-- Table: `deposit_allocations`
-- --------------------------------------------
DROP TABLE IF EXISTS `deposit_allocations`;
CREATE TABLE `deposit_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deposit_id` int NOT NULL,
  `allocation_id` int DEFAULT NULL,
  `bike_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deposit_id` (`deposit_id`),
  KEY `allocation_id` (`allocation_id`),
  KEY `bike_id` (`bike_id`),
  CONSTRAINT `deposit_allocations_ibfk_1` FOREIGN KEY (`deposit_id`) REFERENCES `bank_deposits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deposit_allocations_ibfk_2` FOREIGN KEY (`allocation_id`) REFERENCES `sale_money_allocations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_allocations_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------
-- Table: `gallery`
-- --------------------------------------------
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------
-- Table: `income_expenses`
-- --------------------------------------------
DROP TABLE IF EXISTS `income_expenses`;
CREATE TABLE `income_expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','cheque','bank_transfer','online','other') DEFAULT 'cash',
  `reference` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `income_expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('1','2026-04-20','expense','Bijli','900.00','cash','yasin ne diye','','2','2026-04-20 16:21:25');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('2','2026-04-20','income','Commission','1500.00','cash','new','','1','2026-04-20 16:29:39');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('3','2026-04-28','expense','Bijli','9000.00','cash','','','1','2026-04-28 14:37:47');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('4','2026-05-07','expense','Bijli','599.00','cash','','','1','2026-05-07 09:23:48');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('5','2026-05-07','income','Commission','12000.00','cash','','','1','2026-05-07 09:24:01');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('6','2026-05-07','expense','Monthly Expense','15000.00','cash','','','1','2026-05-07 09:24:28');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('7','2026-05-07','income','Monthly Income','19000.00','cash','','','1','2026-05-07 09:24:55');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('10','2026-05-07','expense','Inventory Loss','193694.00','other','Bike ID: 11 (T910L72300000632)','Automated expense for Damaged/Lost bike.','1','2026-05-07 10:26:12');
INSERT INTO `income_expenses` (`id`,`entry_date`,`type`,`category`,`amount`,`payment_method`,`reference`,`notes`,`created_by`,`created_at`) VALUES ('11','2026-05-07','expense','Rent','12000.00','cash','','','1','2026-05-07 11:57:30');

-- --------------------------------------------
-- Table: `installments`
-- --------------------------------------------
DROP TABLE IF EXISTS `installments`;
CREATE TABLE `installments` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bike_id` (`bike_id`),
  KEY `payment_id` (`payment_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  KEY `idx_installments_customer` (`customer_id`),
  CONSTRAINT `installments_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `installments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `installments_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('7','36','6','2026-05-28','33000.00','33000.00','0.00','paid','23','Installment 1 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:24:51');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('8','36','6','2026-06-28','33000.00','33000.00','9000.00','paid','24','Installment 2 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:25:07');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('9','36','6','2026-07-28','33000.00','0.00','0.00','pending',NULL,'Installment 3 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:24:33');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('10','36','6','2026-08-28','33000.00','0.00','0.00','pending',NULL,'Installment 4 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:24:33');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('11','36','6','2026-09-28','33000.00','0.00','0.00','pending',NULL,'Installment 5 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:24:33');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('12','36','6','2026-10-28','33000.00','0.00','0.00','pending',NULL,'Installment 6 for Chassis NW-21233132','2026-04-28 15:24:33','2026-04-28 15:24:33');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('13','37','5','2026-06-01','26666.67','26666.67','9000.00','paid','27','Installment 1 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:03:28');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('14','37','5','2026-07-01','26666.67','0.00','0.00','pending',NULL,'Installment 2 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('15','37','5','2026-08-01','26666.67','0.00','0.00','pending',NULL,'Installment 3 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('16','37','5','2026-09-01','26666.67','0.00','0.00','pending',NULL,'Installment 4 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('17','37','5','2026-10-01','26666.67','0.00','0.00','pending',NULL,'Installment 5 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('18','37','5','2026-11-01','26666.67','0.00','0.00','pending',NULL,'Installment 6 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('19','37','5','2026-12-01','26666.67','0.00','0.00','pending',NULL,'Installment 7 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('20','37','5','2027-01-01','26666.67','0.00','0.00','pending',NULL,'Installment 8 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('21','37','5','2027-02-01','26666.67','0.00','0.00','pending',NULL,'Installment 9 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('22','37','5','2027-03-01','26666.67','0.00','0.00','pending',NULL,'Installment 10 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('23','37','5','2027-04-01','26666.67','0.00','0.00','pending',NULL,'Installment 11 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');
INSERT INTO `installments` (`id`,`bike_id`,`customer_id`,`due_date`,`installment_amount`,`amount_paid`,`penalty_fee`,`status`,`payment_id`,`notes`,`created_at`,`updated_at`) VALUES ('24','37','5','2027-05-01','26666.67','0.00','0.00','pending',NULL,'Installment 12 for Chassis NW-212335123','2026-05-01 12:02:42','2026-05-01 12:02:42');

-- --------------------------------------------
-- Table: `leadership`
-- --------------------------------------------
DROP TABLE IF EXISTS `leadership`;
CREATE TABLE `leadership` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `message` text,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `leadership` (`id`,`name`,`position`,`image`,`message`,`sort_order`,`created_at`) VALUES ('1','Yasin Ullah','CEO','uploads/img_6a02d8ea25491.jpg','This is the message from the CEO of the company deal with it','0','2026-05-12 12:38:18');

-- --------------------------------------------
-- Table: `ledger`
-- --------------------------------------------
DROP TABLE IF EXISTS `ledger`;
CREATE TABLE `ledger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entry_date` date DEFAULT NULL,
  `entry_type` enum('debit','credit') DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `party_type` enum('customer','supplier','other') DEFAULT NULL,
  `party_id` int DEFAULT NULL,
  `description` text,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ledger_party` (`party_type`,`party_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('1','2026-04-26','credit','150000.00','customer','5','Sale of Chassis: NW-21233','sale','28','150000.00','2026-04-26 15:17:45');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('2','2026-04-26','debit','190000.00','customer','0','Return for Bike ID: 28','return','28','190000.00','2026-04-26 15:21:35');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('4','2026-04-28','credit','130000.00','customer','5','Sale of Chassis: NW-21233213','sale','35','130000.00','2026-04-28 09:43:01');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('5','2026-04-28','debit','240000.00','customer','5','Sale of Chassis: NW-212331','sale','30','240000.00','2026-04-28 09:50:16');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('6','2026-04-28','credit','220000.00','customer','5','Down Payment for Chassis: NW-212331','down_payment','30','220000.00','2026-04-28 09:50:16');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('7','2026-04-28','debit','60000.00','customer','1','Sale of Chassis: NW-2123353','sale','33','60000.00','2026-04-28 10:08:07');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('8','2026-04-28','credit','30000.00','customer','1','Down Payment for Chassis: NW-2123353','down_payment','33','30000.00','2026-04-28 10:08:07');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('9','2026-04-28','debit','60000.00','customer','1','Sale of Chassis: NW-212331aa','sale','34','60000.00','2026-04-28 10:09:01');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('10','2026-04-28','credit','60000.00','customer','1','Down Payment for Chassis: NW-212331aa','down_payment','34','60000.00','2026-04-28 10:09:01');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('11','2026-04-28','credit','30000.00','customer','1','Payment Received: baqaya','payment',NULL,NULL,'2026-04-28 10:21:39');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('12','2026-04-28','credit','600.00','customer','1','Payment Received: ','payment',NULL,NULL,'2026-04-28 10:25:33');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('13','2026-04-28','debit','130000.00','customer','5','Sale of Chassis: DD35G48130001177 from Quote #1','sale','3','130000.00','2026-04-28 14:37:06');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('14','2026-04-28','credit','130000.00','customer','5','Payment for Quote #1','payment','3','130000.00','2026-04-28 14:37:06');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('24','2026-04-28','debit','220000.00','customer','6','Sale of Chassis: NW-21233132','sale','36','220000.00','2026-04-28 15:24:33');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('25','2026-04-28','credit','22000.00','customer','6','Down Payment for Chassis: NW-21233132','down_payment','36','22000.00','2026-04-28 15:24:33');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('26','2026-04-28','credit','33000.00','customer','6','Installment payment for Chassis: NW-21233132','installment','7','33000.00','2026-04-28 15:24:51');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('27','2026-04-28','debit','9000.00','customer','6','Penalty fee for Chassis: NW-21233132','penalty','8','9000.00','2026-04-28 15:25:07');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('28','2026-04-28','credit','42000.00','customer','6','Installment payment for Chassis: NW-21233132','installment','8','42000.00','2026-04-28 15:25:07');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('29','2026-05-01','debit','340000.00','customer','5','Sale of Chassis: NW-212335123','sale','37','340000.00','2026-05-01 12:02:42');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('30','2026-05-01','credit','20000.00','customer','5','Down Payment for Chassis: NW-212335123','down_payment','37','20000.00','2026-05-01 12:02:42');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('31','2026-05-01','debit','9000.00','customer','5','Penalty fee for Chassis: NW-212335123','penalty','13','9000.00','2026-05-01 12:03:28');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('32','2026-05-01','credit','35666.67','customer','5','Installment payment for Chassis: NW-212335123','installment','13','35666.67','2026-05-01 12:03:28');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('33','2026-05-02','debit','230000.00','customer','1','Sale of Chassis: NW-212331a','sale','32','230000.00','2026-05-01 18:22:14');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('34','2026-05-02','credit','230000.00','customer','1','Down Payment for Chassis: NW-212331a','down_payment','32','230000.00','2026-05-01 18:22:14');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('35','2026-05-06','debit','210000.00','customer',NULL,'Sale of Chassis: LY05G48270002304','sale','1','210000.00','2026-05-06 21:38:41');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('36','2026-05-06','credit','210000.00','customer',NULL,'Down Payment for Chassis: LY05G48270002304','down_payment','1','210000.00','2026-05-06 21:38:41');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('37','2026-05-06','debit','220000.00','customer',NULL,'Sale of Chassis: T929283007','sale','38','220000.00','2026-05-06 21:51:52');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('38','2026-05-06','credit','220000.00','customer',NULL,'Down Payment for Chassis: T929283007','down_payment','38','220000.00','2026-05-06 21:51:52');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('39','2026-05-06','debit','200000.00','customer',NULL,'Sale of Chassis: TH12G72260006004','sale','10','200000.00','2026-05-06 21:54:50');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('40','2026-05-06','credit','200000.00','customer',NULL,'Down Payment for Chassis: TH12G72260006004','down_payment','10','200000.00','2026-05-06 21:54:50');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('41','2026-05-07','credit','200000.00','customer',NULL,'Bike Return (Reversal) for Chassis: TH12G72260006004','return_reversal','10','200000.00','2026-05-06 21:57:35');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('42','2026-05-07','debit','320000.00','customer','2','Sale of Chassis: qrqwer','sale','40','320000.00','2026-05-07 11:50:37');
INSERT INTO `ledger` (`id`,`entry_date`,`entry_type`,`amount`,`party_type`,`party_id`,`description`,`reference_type`,`reference_id`,`balance`,`created_at`) VALUES ('43','2026-05-07','credit','320000.00','customer','2','Down Payment for Chassis: qrqwer','down_payment','40','320000.00','2026-05-07 11:50:37');

-- --------------------------------------------
-- Table: `models`
-- --------------------------------------------
DROP TABLE IF EXISTS `models`;
CREATE TABLE `models` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_code` varchar(50) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `short_code` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `top_speed` varchar(50) DEFAULT NULL,
  `max_range` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('1','LY SI','LY SI Electric Bike','Electric Bike','LY',NULL,'2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('2','T9 Sports','T9 Sports Electric Bike','Electric Bike','T9','uploads/models/model_2_t9_sports_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('3','T9 Sports LFP','T9 Sports LFP Electric Bike','Electric Bike','T9 LFP','uploads/models/model_3_t9_sports_lfp_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('4','T9 Eco','T9 Eco Electric Bike','Electric Bike','T9 Eco',NULL,'2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('5','Thrill Pro','Thrill Pro Electric Bike','Electric Bike','TP','uploads/models/model_5_thrill_pro_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('6','Thrill Pro LFP','Thrill Pro LFP Electric Bike','Electric Bike','TP LFP','uploads/models/model_6_thrill_pro_lfp_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('7','E8S M2','E8S M2 Electric Scooter','Electric Scooter','E8S',NULL,'2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('8','E8S Pro','E8S Pro Electric Scooter','Electric Scooter','E8S Pro',NULL,'2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('9','M6 K6','M6 K6 Electric Bike','Electric Bike','M6','uploads/models/model_9_m6_k6_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('10','M6 NP','M6 NP Electric Bike','Electric Bike','M6 NP','uploads/models/model_10_m6_np_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('11','M6 Lithium NP','M6 Lithium NP Electric Bike','Electric Bike','M6 L','uploads/models/model_11_m6_lithium_np_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('12','Premium','Premium Electric Bike','Electric Bike','Premium','uploads/models/model_12_premium_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('13','W. Bike H2','W. Bike H2 Electric Bike','Electric Bike','W. Bike','uploads/models/model_13_w_bike_h2_electric_bike.webp','2026-04-20 13:56:23',NULL,NULL);
INSERT INTO `models` (`id`,`model_code`,`model_name`,`category`,`short_code`,`image`,`created_at`,`top_speed`,`max_range`) VALUES ('14','SP12','Super Star 70','Electric Bike','123','uploads/bike_6a0310a76dc22.webp','2026-05-07 11:34:51','200','120');

-- --------------------------------------------
-- Table: `money_destinations`
-- --------------------------------------------
DROP TABLE IF EXISTS `money_destinations`;
CREATE TABLE `money_destinations` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('1','bank','HBL - Habib Bank','Main Branch Account',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('2','bank','MCB - Muslim Commercial Bank','Business Account',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('3','bank','UBL - United Bank','Savings Account',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('4','person','Owner / Proprietor','Main business owner',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('5','person','Partner','Business partner',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('6','person','Manager','Shop manager',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('7','wallet','JazzCash','Mobile wallet',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('8','wallet','Easypaisa','Mobile wallet',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');
INSERT INTO `money_destinations` (`id`,`type`,`name`,`details`,`account_title`,`account_no`,`branch`,`opening_balance`,`contact_person`,`contact_phone`,`is_active`,`created_at`,`updated_at`) VALUES ('9','wallet','Cash Drawer','Shop cash register',NULL,NULL,NULL,'0.00',NULL,NULL,'1','2026-05-16 17:10:44','2026-05-16 17:10:44');

-- --------------------------------------------
-- Table: `payments`
-- --------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_payments_ref` (`transaction_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('1','2026-04-26','cheque','150000.00',NULL,NULL,NULL,'cleared','sale','28','Yasin Ullah','','2026-04-26 15:17:45');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('2','2026-04-28','cash','20000.00',NULL,NULL,NULL,'pending','supplier_payment','10','0','','2026-04-28 09:24:26');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('3','2026-04-28','cash','40000.00',NULL,NULL,NULL,'pending','supplier_payment','11','0','','2026-04-28 09:29:37');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('4','2026-04-28','cash','90000.00',NULL,NULL,NULL,'pending','supplier_payment','12','0','new','2026-04-28 09:33:26');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('5','2026-04-28','cheque','90000.00','nw1023900','0','2026-04-28','pending','supplier_payment','13','0','','2026-04-28 09:36:57');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('6','2026-04-28','cash','110000.00','','0',NULL,'pending','sale','36','0','Down Payment for Chassis: NW-21233132','2026-04-28 09:40:25');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('7','2026-04-28','cash','110000.00','','0',NULL,'pending','sale','35','0','Down Payment for Chassis: NW-21233213','2026-04-28 09:43:01');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('8','2026-04-28','cash','220000.00','','0',NULL,'pending','sale','30','0','Down Payment for Chassis: NW-212331','2026-04-28 09:50:16');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('9','2026-04-28','cash','30000.00','','0',NULL,'pending','sale','33','0','Down Payment for Chassis: NW-2123353','2026-04-28 10:08:07');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('10','2026-04-28','cash','60000.00','','0',NULL,'pending','sale','34','0','Down Payment for Chassis: NW-212331aa','2026-04-28 10:09:01');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('11','2026-04-28','cash','30000.00',NULL,NULL,NULL,'pending','sale',NULL,'Ahmed Ali','baqaya','2026-04-28 10:21:39');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('12','2026-04-28','cash','600.00',NULL,NULL,NULL,'pending','sale',NULL,'Ahmed Ali','','2026-04-28 10:25:33');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('13','2026-04-28','cash','130000.00',NULL,NULL,NULL,'pending','sale','3','Yasin Ullah','Sale from Quotation #1','2026-04-28 14:37:06');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('22','2026-04-28','cash','22000.00','','',NULL,'pending','sale','36','Shams Uddin','Down Payment for Chassis: NW-21233132','2026-04-28 15:24:33');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('23','2026-04-28','cash','33000.00','','',NULL,'pending','installment','7','Shams Uddin','Installment payment for Chassis NW-21233132 (ID: 7)','2026-04-28 15:24:51');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('24','2026-04-28','cash','42000.00','','',NULL,'pending','installment','8','Shams Uddin','Installment payment for Chassis NW-21233132 (ID: 8)','2026-04-28 15:25:07');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('25','2026-05-01','cash','290000.00',NULL,NULL,NULL,'pending','supplier_payment','14','Yasin Ullah','','2026-05-01 11:59:16');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('26','2026-05-01','cash','20000.00','','',NULL,'pending','sale','37','Yasin Ullah','Down Payment for Chassis: NW-212335123','2026-05-01 12:02:42');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('27','2026-05-01','cash','35666.67','','',NULL,'pending','installment','13','Yasin Ullah','Installment payment for Chassis NW-212335123 (ID: 13)','2026-05-01 12:03:28');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('28','2026-05-02','cash','230000.00','','',NULL,'pending','sale','32','Ahmed Ali','Down Payment for Chassis: NW-212331a','2026-05-01 18:22:14');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('29','2026-05-06','cash','210000.00','','',NULL,'pending','sale','1','Walk-in Customer','Down Payment for Chassis: LY05G48270002304','2026-05-06 21:38:41');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('30','2026-05-06','cash','220000.00','','',NULL,'pending','sale','38','Walk-in Customer','Down Payment for Chassis: T929283007','2026-05-06 21:51:52');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('31','2026-05-06','online','200000.00','','',NULL,'pending','sale','10','Walk-in Customer','Down Payment for Chassis: TH12G72260006004','2026-05-06 21:54:50');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('32','2026-05-07','cash','0.00','','',NULL,'pending','customer_refund','10','Unknown Customer','30000','2026-05-06 21:57:35');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('33','2026-05-07','cash','500000.00',NULL,NULL,NULL,'pending','supplier_payment','16','Khan Gull','','2026-05-07 11:41:42');
INSERT INTO `payments` (`id`,`payment_date`,`payment_type`,`amount`,`cheque_number`,`bank_name`,`cheque_date`,`status`,`transaction_type`,`reference_id`,`party_name`,`notes`,`created_at`) VALUES ('34','2026-05-07','cash','320000.00','','',NULL,'pending','sale','40','Muhammad Usman','Down Payment for Chassis: qrqwer','2026-05-07 11:50:37');

-- --------------------------------------------
-- Table: `purchase_orders`
-- --------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_date` date DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `total_units` int DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('1','2026-02-03','1','14','0.00','First Order','2026-04-20 14:01:57');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('2','2026-02-27','1','2','0.00','Second Order','2026-04-20 14:01:57');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('3','2026-03-12','1','5','0.00','Third Order','2026-04-20 14:01:57');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('4','2026-03-18','1','6','0.00','Fourth Order','2026-04-20 14:01:57');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('5','2026-04-26','1','1','0.00','','2026-04-26 15:13:52');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('6','2026-04-27','1','1','0.00','','2026-04-27 15:25:09');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('7','2026-04-27','1','1','0.00','','2026-04-27 15:25:19');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('8','2026-04-27','1','1','0.00','','2026-04-27 15:29:22');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('9','2026-04-27','1','1','0.00','','2026-04-27 15:29:45');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('10','2026-04-28','5','1','20000.00','','2026-04-28 09:24:26');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('11','2026-04-28','2','1','40000.00','','2026-04-28 09:29:37');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('12','2026-04-28','2','1','90000.00','new','2026-04-28 09:33:26');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('13','2026-04-28','3','1','90000.00','','2026-04-28 09:36:57');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('14','2026-05-01','2','1','290000.00','','2026-05-01 11:59:16');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('15','2026-05-06','4','1','200000.00','','2026-05-06 21:50:26');
INSERT INTO `purchase_orders` (`id`,`order_date`,`supplier_id`,`total_units`,`total_amount`,`notes`,`created_at`) VALUES ('16','2026-05-07','6','2','500000.00','','2026-05-07 11:41:42');

-- --------------------------------------------
-- Table: `quotations`
-- --------------------------------------------
DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `bike_id` (`bike_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `quotations` (`id`,`quote_date`,`customer_id`,`bike_id`,`accessories_json`,`quoted_price`,`is_installment`,`down_payment`,`total_installments`,`installment_amount`,`valid_until`,`status`,`notes`,`created_by`,`created_at`) VALUES ('1','2026-04-28','5','3','[]','130000.00','0','0.00','0','0.00','2026-05-05','converted','0','1','2026-04-28 14:17:02');

-- --------------------------------------------
-- Table: `quote_requests`
-- --------------------------------------------
DROP TABLE IF EXISTS `quote_requests`;
CREATE TABLE `quote_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `bike_id` int DEFAULT NULL,
  `details` text,
  `status` enum('pending','sent','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bike_id` (`bike_id`),
  CONSTRAINT `quote_requests_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `quote_requests` (`id`,`customer_name`,`customer_phone`,`bike_id`,`details`,`status`,`created_at`) VALUES ('1','Yasin Ullah','03139842219','39','All','sent','2026-05-12 16:05:45');
INSERT INTO `quote_requests` (`id`,`customer_name`,`customer_phone`,`bike_id`,`details`,`status`,`created_at`) VALUES ('2','Yasin Ullah','03139842219','2','new','sent','2026-05-12 16:09:51');

-- --------------------------------------------
-- Table: `role_permissions`
-- --------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `page` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT '0',
  `can_add` tinyint(1) DEFAULT '0',
  `can_edit` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_page` (`role_id`,`page`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('1','3','dashboard','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('2','3','inventory','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('3','3','purchase','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('4','3','sale','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('5','3','customers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('6','3','suppliers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('7','3','models','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('8','3','reports','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('9','3','returns','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('10','3','cheques','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('11','3','settings','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('12','3','roles','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('13','3','users','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('14','3','income_expense','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('15','4','dashboard','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('16','4','inventory','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('17','4','purchase','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('18','4','sale','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('19','4','customers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('20','4','suppliers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('21','4','models','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('22','4','reports','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('23','4','returns','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('24','4','payments','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('25','4','settings','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('26','4','roles','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('27','4','users','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('28','4','income_expense','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('29','4','accessories','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('30','4','quotations','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('31','4','installments','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('49','5','dashboard','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('50','5','inventory','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('51','5','purchase','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('52','5','sale','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('53','5','customers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('54','5','suppliers','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('55','5','models','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('56','5','reports','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('57','5','returns','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('58','5','payments','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('59','5','settings','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('60','5','roles','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('61','5','users','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('62','5','income_expense','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('63','5','accessories','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('64','5','quotations','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('65','5','installments','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('84','2','dashboard','1','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('88','1','dashboard','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('89','1','inventory','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('90','1','purchase','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('91','1','sale','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('92','1','customers','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('93','1','suppliers','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('94','1','models','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('95','1','reports','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('96','1','returns','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('97','1','payments','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('98','1','settings','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('99','1','roles','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('100','1','users','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('101','1','income_expense','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('102','1','accessories','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('103','1','quotations','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('104','1','installments','1','1','1','1');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('105','1','money_destinations','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('106','1','money_tracking','0','0','0','0');
INSERT INTO `role_permissions` (`id`,`role_id`,`page`,`can_view`,`can_add`,`can_edit`,`can_delete`) VALUES ('107','1','bank_deposits','0','0','0','0');

-- --------------------------------------------
-- Table: `roles`
-- --------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `roles` (`id`,`name`,`description`,`created_at`) VALUES ('1','Administrator','Full access','2026-04-20 15:50:00');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`) VALUES ('2','Manager','Limited access','2026-04-20 15:50:00');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`) VALUES ('3','income and expenses guy','only handle income and expenses','2026-04-20 16:19:38');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`) VALUES ('4','income and expense','','2026-05-01 12:06:02');
INSERT INTO `roles` (`id`,`name`,`description`,`created_at`) VALUES ('5','Sales man','','2026-05-07 12:02:14');

-- --------------------------------------------
-- Table: `sale_accessories`
-- --------------------------------------------
DROP TABLE IF EXISTS `sale_accessories`;
CREATE TABLE `sale_accessories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bike_id` int NOT NULL,
  `accessory_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `final_price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accessory_id` (`accessory_id`),
  KEY `idx_sa_bike` (`bike_id`),
  CONSTRAINT `sale_accessories_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_accessories_ibfk_2` FOREIGN KEY (`accessory_id`) REFERENCES `accessories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `sale_accessories` (`id`,`bike_id`,`accessory_id`,`quantity`,`unit_price`,`discount_amount`,`final_price`) VALUES ('1','37','1','1','0.00','0.00','0.00');
INSERT INTO `sale_accessories` (`id`,`bike_id`,`accessory_id`,`quantity`,`unit_price`,`discount_amount`,`final_price`) VALUES ('2','40','3','1','1200.00','1200.00','0.00');

-- --------------------------------------------
-- Table: `sale_money_allocations`
-- --------------------------------------------
DROP TABLE IF EXISTS `sale_money_allocations`;
CREATE TABLE `sale_money_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bike_id` int NOT NULL,
  `destination_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `allocation_date` date NOT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bike_id` (`bike_id`),
  KEY `destination_id` (`destination_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sale_money_allocations_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_money_allocations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `money_destinations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sale_money_allocations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------
-- Table: `settings`
-- --------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('1','company_name','BNI Enterprises');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('2','branch_name','Dera (Ahmed Metro)');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('3','tax_rate','0.1');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('4','currency','Rs.');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('5','tax_on','purchase_price');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('6','theme','light');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('7','admin_password','$2y$10$8348koW6nh9Q5tigyeHj7.P7PMnTxPbWb7hM8P1mtS.k8sfUsguU.');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('8','show_purchase_on_invoice','0');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('17','session_timeout_idle','2400');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('18','session_timeout_absolute','28800');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('19','landing_hero_title','Experience the Future of Mobility');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('20','landing_hero_subtitle','Premium Electric Bikes for a Greener Tomorrow');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('21','company_address','123 Bike Street, Dera Ghazi Khan, Punjab, Pakistan');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('22','company_map_iframe','https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d10500.14144614541!2d73.07594429999999!3d33.6494707!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2s!4v1778569478700!5m2!1sen!2s');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('23','company_whatsapp','923000000000, 923309313131');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('24','company_email','info@bnienterprises.com');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('25','social_facebook','https://facebook.com');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('26','social_instagram','https://instagram.com');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('27','social_twitter','https://twitter.com');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('28','vision_statement','To be the leading provider of eco-friendly transportation in the region.');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`) VALUES ('29','mission_statement','Providing high-quality electric bikes and exceptional service to our customers.');

-- --------------------------------------------
-- Table: `suppliers`
-- --------------------------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('1','Default Supplier','0300-0000000','Pakistan','2026-04-20 13:56:23');
INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('2','Yasin Ullah','03139842219','New bannu wala','2026-04-28 09:12:55');
INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('3','Shams Uddin','0322213222','New Bannu','2026-04-28 09:16:21');
INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('4','Noor udin','0322213222','new','2026-04-28 09:17:05');
INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('5','Nasim','001239919023','newd','2026-04-28 09:19:27');
INSERT INTO `suppliers` (`id`,`name`,`contact`,`address`,`created_at`) VALUES ('6','Khan Gull','0322213222','domel bannu','2026-05-07 11:36:10');

-- --------------------------------------------
-- Table: `users`
-- --------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`,`username`,`password_hash`,`full_name`,`role_id`,`is_active`,`created_at`) VALUES ('1','admin','$2y$10$KdSnze47ye.inqU8FvyrrO.ugHe3xSXPFiJJ1PsntZN9KJTND5Fa6','System Administrator','1','1','2026-04-20 15:50:00');
INSERT INTO `users` (`id`,`username`,`password_hash`,`full_name`,`role_id`,`is_active`,`created_at`) VALUES ('2','admin1','$2y$10$Yk7.BNVuTU6lpNYVo10UfuQ0cprC2y84gMMGj6ei0T8q0tLXhHyW2','Yasin Ullah','3','1','2026-04-20 16:20:03');
INSERT INTO `users` (`id`,`username`,`password_hash`,`full_name`,`role_id`,`is_active`,`created_at`) VALUES ('3','admin3','$2y$10$N1XGevQPOl7HgShVSSnsuuDXFX2gW/MAxoIbEUgpUG.jPIWWN.8sG','Hussain','4','1','2026-05-01 12:06:32');

COMMIT;
SET FOREIGN_KEY_CHECKS=1;
