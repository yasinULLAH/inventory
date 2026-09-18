-- Migration: Add Subscription / Licensing System
-- Date: 2026-09-18
-- Author: Yasin Ullah

CREATE TABLE IF NOT EXISTS `app_subscription` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `license_key` VARCHAR(128) DEFAULT NULL,
    `license_hash` VARCHAR(64) DEFAULT NULL,
    `email_sent_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `grace_days` INT NOT NULL DEFAULT 8,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `activated_at` DATETIME NOT NULL,
    `activated_by_ip` VARCHAR(45) DEFAULT NULL,
    `last_validated_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default subscription record (starts now, expires in 6 months)
INSERT INTO `app_subscription` (`expires_at`, `grace_days`, `is_active`, `activated_at`)
VALUES (DATE_ADD(NOW(), INTERVAL 6 MONTH), 8, 1, NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;
