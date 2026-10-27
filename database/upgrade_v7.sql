-- PWMS upgrade v7 - rental request and equipment rental tracking
-- Apply to an existing installation after upgrade_v6.sql.
-- MariaDB supports ADD COLUMN IF NOT EXISTS; on MySQL 8, remove
-- IF NOT EXISTS and run each ALTER only once.
USE `procurement_warehouse_ms`;

ALTER TABLE `procurement_requests`
  ADD COLUMN IF NOT EXISTS `request_type` ENUM('purchase','rental') NOT NULL DEFAULT 'purchase' AFTER `unit`,
  ADD COLUMN IF NOT EXISTS `rental_duration` DECIMAL(8,2) NULL AFTER `request_type`,
  ADD COLUMN IF NOT EXISTS `rental_duration_unit` ENUM('hour','day','week','month') NULL AFTER `rental_duration`,
  ADD COLUMN IF NOT EXISTS `rental_location` VARCHAR(255) NULL AFTER `rental_duration_unit`,
  ADD COLUMN IF NOT EXISTS `operator_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `rental_location`;

ALTER TABLE `procurement_requests`
  MODIFY `status` ENUM('pending','closed','warehouse_check','purchase_required',
                       'quotation_pending','committee_pending','approved',
                       'supplier_selected','authority_approved','financed',
                       'purchased','received','rental_required','rental_active','completed')
                       NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS `rental_contracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rental_no` VARCHAR(30) NOT NULL UNIQUE,
  `request_id` INT UNSIGNED NOT NULL UNIQUE,
  `supplier_name` VARCHAR(150) NOT NULL,
  `supplier_contact` VARCHAR(100) NULL,
  `supplier_phone` VARCHAR(30) NULL,
  `equipment_name` VARCHAR(150) NOT NULL,
  `equipment_details` TEXT NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `rate_amount` DECIMAL(14,2) NOT NULL,
  `rate_unit` ENUM('hour','day','week','month') NOT NULL,
  `currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN',
  `deposit_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `planned_end_date` DATE NOT NULL,
  `handover_date` DATE NULL,
  `actual_return_date` DATE NULL,
  `rental_location` VARCHAR(255) NULL,
  `operator_included` TINYINT(1) NOT NULL DEFAULT 0,
  `operator_name` VARCHAR(150) NULL,
  `fuel_responsibility` ENUM('company','supplier') NOT NULL DEFAULT 'company',
  `maintenance_responsibility` ENUM('company','supplier') NOT NULL DEFAULT 'supplier',
  `handover_condition` TEXT NULL,
  `return_condition` TEXT NULL,
  `meter_start` DECIMAL(14,2) NULL,
  `meter_end` DECIMAL(14,2) NULL,
  `status` ENUM('scheduled','active','returned') NOT NULL DEFAULT 'scheduled',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `returned_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rental_status` (`status`),
  KEY `idx_rental_dates` (`start_date`,`planned_end_date`),
  CONSTRAINT `fk_rental_request` FOREIGN KEY (`request_id`)
    REFERENCES `procurement_requests`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rental_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rental_returned_by` FOREIGN KEY (`returned_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rental_id` INT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN',
  `method` ENUM('cash','bank_transfer','cheque','other') NOT NULL DEFAULT 'other',
  `reference_no` VARCHAR(60) NULL,
  `notes` TEXT NULL,
  `recorded_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rental_payment_date` (`rental_id`,`payment_date`),
  CONSTRAINT `fk_rental_payment_contract` FOREIGN KEY (`rental_id`)
    REFERENCES `rental_contracts`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rental_payment_user` FOREIGN KEY (`recorded_by`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_extensions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rental_id` INT UNSIGNED NOT NULL,
  `previous_end_date` DATE NOT NULL,
  `new_end_date` DATE NOT NULL,
  `reason` VARCHAR(255) NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rental_extension` (`rental_id`,`created_at`),
  CONSTRAINT `fk_rental_extension_contract` FOREIGN KEY (`rental_id`)
    REFERENCES `rental_contracts`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rental_extension_user` FOREIGN KEY (`changed_by`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
