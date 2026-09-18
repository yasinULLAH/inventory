-- Migration: Add Featured Bike & Running Discount functionality
-- Date: 2026-09-18
-- Author: Yasin Ullah

-- Add new columns to bikes table for featured/discount functionality
ALTER TABLE `bikes`
    ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `image`,
    ADD COLUMN `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `is_featured`,
    ADD COLUMN `discount_type` ENUM('flat','percentage') NOT NULL DEFAULT 'flat' AFTER `discount_amount`,
    ADD COLUMN `discount_label` VARCHAR(255) NULL AFTER `discount_type`,
    ADD COLUMN `discount_start` DATE NULL AFTER `discount_label`,
    ADD COLUMN `discount_end` DATE NULL AFTER `discount_start`,
    ADD COLUMN `display_priority` INT NOT NULL DEFAULT 0 AFTER `discount_end`;

-- Add index for efficient featured bike queries
ALTER TABLE `bikes` ADD INDEX `idx_featured` (`is_featured`, `status`);
ALTER TABLE `bikes` ADD INDEX `idx_discount` (`discount_amount`, `discount_start`, `discount_end`);
