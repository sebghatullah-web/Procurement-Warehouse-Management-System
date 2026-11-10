-- PWMS upgrade v9 - rental owner identification and case numbers
-- Apply after upgrade_v8.sql. Back up the database before upgrading.
USE `procurement_warehouse_ms`;

ALTER TABLE `rental_contracts`
  ADD COLUMN `supplier_tazkira_no` VARCHAR(100) NULL AFTER `supplier_name`,
  ADD COLUMN `case_no` VARCHAR(100) NULL AFTER `supplier_tazkira_no`;
