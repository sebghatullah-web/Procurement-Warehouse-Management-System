-- =====================================================================
--  PWMS upgrade v3 - Employee identity + dual currency (2026-09-21)
--  Applies to an EXISTING installation whose database is already running.
--
--  What changed:
--    1) `procurement_requests.employee_name`     -> free-text employee name
--       `procurement_requests.employee_position` -> free-text job position
--       (recorded on the request form in addition to the logged-in user)
--    2) `purchases.currency`  -> ENUM('AFN','USD') so each purchase can be
--       priced in Afghanis (افغانی) or Dollars (دالر). Default: AFN.
--    3) `quotations.currency` -> ENUM('AFN','USD') same choice per supplier quote.
--
--  HOW TO RUN (MySQL command line, XAMPP):
--    C:\xampp\mysql\bin\mysql.exe -u root < upgrade_v3.sql
--
--  NOTE: Uses MariaDB `ADD COLUMN IF NOT EXISTS`. On pure MySQL 8 this
--        syntax is not supported -> remove "IF NOT EXISTS" and run once.
-- =====================================================================
USE `procurement_warehouse_ms`;

ALTER TABLE `procurement_requests`
  ADD COLUMN IF NOT EXISTS `employee_name` VARCHAR(150) NULL COMMENT 'employee name as entered on the request form' AFTER `requested_by`;

ALTER TABLE `procurement_requests`
  ADD COLUMN IF NOT EXISTS `employee_position` VARCHAR(150) NULL COMMENT 'job position as entered on the request form' AFTER `employee_name`;

ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر' AFTER `total_cost`;

ALTER TABLE `quotations`
  ADD COLUMN IF NOT EXISTS `currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر' AFTER `price`;