-- =====================================================================
--  PWMS upgrade v2 - Request form improvements (2026-09-15)
--  Applies to an EXISTING installation whose database is already running.
--
--  What changed:
--    1) `quantity` of procurement_requests becomes free text so that
--       amounts such as "5", "3 متر", "20 کیلوگرام", "نیم لیتر" can be stored.
--    2) new column `details`    -> detailed specifications of the item.
--    3) new column `needed_date`-> date the item must arrive by.
--
--  HOW TO RUN (MySQL command line, XAMPP):
--    C:\xampp\mysql\bin\mysql.exe -u root < upgrade_v2.sql
--
--  NOTE: Uses MariaDB `ADD COLUMN IF NOT EXISTS`. On pure MySQL 8 this
--        syntax is not supported -> remove "IF NOT EXISTS" and run once.
-- =====================================================================
USE `procurement_warehouse_ms`;

ALTER TABLE `procurement_requests`
  MODIFY `quantity` VARCHAR(50) NOT NULL COMMENT 'free text amount (supports kg, liter, متر, ...)';

ALTER TABLE `procurement_requests`
  ADD COLUMN IF NOT EXISTS `details` TEXT NULL COMMENT 'detailed item specifications' AFTER `reason`;

ALTER TABLE `procurement_requests`
  ADD COLUMN IF NOT EXISTS `needed_date` DATE NULL COMMENT 'date the item must arrive by' AFTER `request_date`;