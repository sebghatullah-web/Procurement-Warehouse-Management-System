-- =====================================================================
--  PWMS upgrade v5 - Post-committee approval chain + warehouse handover
--  Applies to an EXISTING installation whose database is already running.
--
--  What changed (management meeting feedback 2026-09-27):
--    1) The purchase no longer jumps from "committee approved" straight to
--       "ordered". Four controlled stages are inserted in between:
--          approved          -> committee approved, supplier not chosen yet
--          supplier_selected -> winning company + price fixed, waiting for
--                               the company presidency / executive director
--          authority_approved-> presidency approved, waiting for the finance
--                               department to hand the money over
--          financed          -> finance delivered the funds (bank cheque /
--                               cash / bank transfer / other)
--          ordered           -> purchasing finally registers the order
--
--    2) A new role `finance` (مالی) is added so the finance department can
--       release the money and send a message to the purchasing department.
--
--    3) `consumptions` becomes a real handover (فورم تسلیمی) record:
--       handover_no, receiving employee name/position, electronic
--       confirmation by the requester and a link back to the purchase.
--
--  HOW TO RUN (MySQL command line, XAMPP):
--    C:\xampp\mysql\bin\mysql.exe -u root < upgrade_v5.sql
--
--  NOTE: Uses MariaDB `ADD COLUMN IF NOT EXISTS`. On pure MySQL 8 this
--        syntax is not supported -> remove "IF NOT EXISTS" and run once.
--        Safe to run several times.
-- =====================================================================
USE `procurement_warehouse_ms`;

-- ---------------------------------------------------------------------
--  1) USERS - add the "finance" role
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  MODIFY `role` ENUM('employee','procurement_manager','warehouse_manager',
                     'gate_security','committee','general_manager','finance','admin')
                     NOT NULL DEFAULT 'employee';

-- ---------------------------------------------------------------------
--  2) REQUESTS - new post-committee statuses
--     (mirror the purchase statuses so the requester can follow along)
-- ---------------------------------------------------------------------
ALTER TABLE `procurement_requests`
  MODIFY `status` ENUM('pending','closed','warehouse_check','purchase_required',
                       'quotation_pending','committee_pending','approved',
                       'supplier_selected','authority_approved','financed',
                       'purchased','received','completed')
                       NOT NULL DEFAULT 'pending';

-- ---------------------------------------------------------------------
--  3) PURCHASES - new post-committee statuses
-- ---------------------------------------------------------------------
ALTER TABLE `purchases`
  MODIFY `status` ENUM('draft','quotation','committee_pending','approved',
                       'supplier_selected','authority_approved','financed',
                       'ordered','received','completed','rejected')
                       NOT NULL DEFAULT 'draft';


-- ---------------------------------------------------------------------
--  4) PURCHASES - supplier selection / authority approval / finance
-- ---------------------------------------------------------------------
ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `selected_quotation_id` INT UNSIGNED NULL
    COMMENT 'winning quotation chosen by procurement' AFTER `supplier_id`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `authority_name` VARCHAR(150) NULL
    COMMENT 'name of the approving president / executive director' AFTER `committee_note`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `authority_position` VARCHAR(150) NULL
    COMMENT 'position of the approving authority' AFTER `authority_name`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `authority_approved_by` INT UNSIGNED NULL
    COMMENT 'user account that registered the approval' AFTER `authority_position`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `authority_approved_at` DATETIME NULL AFTER `authority_approved_by`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `authority_note` TEXT NULL
    COMMENT 'comment of the company presidency' AFTER `authority_approved_at`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_method` ENUM('bank_cheque','cash','bank_transfer','other') NULL
    COMMENT 'how finance delivers the money' AFTER `authority_note`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_ref_no` VARCHAR(60) NULL
    COMMENT 'cheque / voucher / reference number' AFTER `finance_method`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_amount` DECIMAL(14,2) NULL
    COMMENT 'amount handed over by finance' AFTER `finance_ref_no`;


-- ---------------------------------------------------------------------
--  5) CONSUMPTIONS - handover (فورم تسلیمی) information
-- ---------------------------------------------------------------------
ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `purchase_id` INT UNSIGNED NULL
    COMMENT 'the purchase this delivery came from' AFTER `request_id`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `handover_no` VARCHAR(30) NULL
    COMMENT 'handover slip number (HND-YYYY-0001)' AFTER `source`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `receiver_name` VARCHAR(150) NULL
    COMMENT 'employee that received the item' AFTER `handover_no`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `receiver_position` VARCHAR(150) NULL
    COMMENT 'job position of the receiving employee' AFTER `receiver_name`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `receiver_user_id` INT UNSIGNED NULL
    COMMENT 'logged-in user confirming the receipt' AFTER `receiver_position`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `receiver_confirmed` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = requester confirmed receiving the item' AFTER `receiver_user_id`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `confirmed_at` DATETIME NULL AFTER `receiver_confirmed`;

ALTER TABLE `consumptions`
  ADD COLUMN IF NOT EXISTS `handover_note` TEXT NULL
    COMMENT 'remarks on the handover slip' AFTER `confirmed_at`;

ALTER TABLE `consumptions` ADD KEY `idx_cons_purchase` (`purchase_id`);
ALTER TABLE `consumptions` ADD KEY `idx_cons_handover` (`handover_no`);

-- ---------------------------------------------------------------------
--  6) SEED - finance user (username: finance / password: password)
-- ---------------------------------------------------------------------
INSERT INTO `users`
(`id`,`username`,`password`,`name`,`role`,`department_id`,`email`,`phone`,`status`) VALUES
(8,'finance','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','مدیر مالی','finance',3,'finance@khawar.pk','0300-0000008',1)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- ---------------------------------------------------------------------
--  7) Backfill - link existing deliveries to their purchase
-- ---------------------------------------------------------------------
UPDATE `consumptions` c
   JOIN `purchases` p ON p.request_id = c.request_id
    SET c.purchase_id = p.id
  WHERE c.purchase_id IS NULL
    AND c.request_id IS NOT NULL;

UPDATE `consumptions`
   SET `receiver_confirmed` = 1,
       `confirmed_at`       = `delivery_date`
 WHERE `handover_no` IS NULL
   AND `receiver_confirmed` = 0;

-- database upgraded to v5 successfully

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN'
    COMMENT 'currency of the handed-over amount' AFTER `finance_amount`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_note` TEXT NULL
    COMMENT 'message from finance to the purchasing department' AFTER `finance_currency`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_released_by` INT UNSIGNED NULL AFTER `finance_note`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `finance_released_at` DATETIME NULL AFTER `finance_released_by`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `order_registered_by` INT UNSIGNED NULL AFTER `finance_released_at`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `order_registered_at` DATETIME NULL AFTER `order_registered_by`;
