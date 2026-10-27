-- PWMS upgrade v8 - four-part request committee approvals
-- Apply after upgrade_v7.sql. Back up the database before upgrading.
USE `procurement_warehouse_ms`;

ALTER TABLE `users`
  MODIFY `role` ENUM('employee','procurement_manager','warehouse_manager',
                     'gate_security','committee','general_manager','finance',
                     'contracting_manager','admin')
                     NOT NULL DEFAULT 'employee';

ALTER TABLE `procurement_requests`
  MODIFY `status` ENUM('pending','closed','warehouse_check','purchase_required',
                       'quotation_pending','committee_pending','approved',
                       'committee_review','supplier_selected','authority_approved',
                       'financed','purchased','received','rental_required',
                       'rental_active','completed')
                       NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS `request_committee_approvals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `committee_key` ENUM('procurement','contracting','finance','department') NOT NULL,
  `department_id` INT UNSIGNED NULL,
  `assigned_user_id` INT UNSIGNED NULL,
  `decision` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `comment` TEXT NULL,
  `decided_by` INT UNSIGNED NULL,
  `decided_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_committee` (`request_id`,`committee_key`),
  KEY `idx_committee_assignment` (`committee_key`,`assigned_user_id`,`decision`),
  CONSTRAINT `fk_req_committee_request` FOREIGN KEY (`request_id`)
    REFERENCES `procurement_requests`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_committee_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_req_committee_assignee` FOREIGN KEY (`assigned_user_id`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_req_committee_decider` FOREIGN KEY (`decided_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
