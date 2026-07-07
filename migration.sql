-- ============================================================
-- BNI Enterprises — Money Destination & Bank Deposit Migration
-- Compatible: MySQL 5.5+ / MariaDB 10+ / phpMyAdmin
-- ============================================================

-- STEP 1: Add new columns to money_destinations
-- If you get "Duplicate column" error for any line below,
-- that column already exists — SKIP that line and continue.
ALTER TABLE `money_destinations` ADD COLUMN `account_title` VARCHAR(255) NULL AFTER `details`;
ALTER TABLE `money_destinations` ADD COLUMN `account_no` VARCHAR(100) NULL AFTER `account_title`;
ALTER TABLE `money_destinations` ADD COLUMN `branch` VARCHAR(255) NULL AFTER `account_no`;
ALTER TABLE `money_destinations` ADD COLUMN `opening_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `branch`;
ALTER TABLE `money_destinations` ADD COLUMN `contact_person` VARCHAR(255) NULL AFTER `opening_balance`;
ALTER TABLE `money_destinations` ADD COLUMN `contact_phone` VARCHAR(50) NULL AFTER `contact_person`;

-- STEP 2: Create bank_deposits table
CREATE TABLE IF NOT EXISTS `bank_deposits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `destination_id` INT NOT NULL,
  `deposit_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `deposit_type` ENUM('cash','cheque','transfer','online','other') NOT NULL DEFAULT 'cash',
  `reference_no` VARCHAR(100) NULL,
  `receipt_image` VARCHAR(255) NULL,
  `deposited_by` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`destination_id`) REFERENCES `money_destinations`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- STEP 3: Create deposit_allocations table
CREATE TABLE IF NOT EXISTS `deposit_allocations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `deposit_id` INT NOT NULL,
  `allocation_id` INT NULL,
  `bike_id` INT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (`deposit_id`) REFERENCES `bank_deposits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`allocation_id`) REFERENCES `sale_money_allocations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
