-- =====================================================================
--  PWMS upgrade v4 - departments.code hotfix (2026-09-21)
--  The admin/departments.php page already had a "code" field in the
--  form, INSERT and UPDATE statements, but the column was missing from
--  the live database (and the seed schema). This adds it and backfills
--  a short code from the department name for legacy rows.
--
--  HOW TO RUN (MySQL command line, XAMPP):
--    C:\xampp\mysql\bin\mysql.exe -u root < upgrade_v4.sql
-- =====================================================================
USE `procurement_warehouse_ms`;

ALTER TABLE `departments`
  ADD COLUMN IF NOT EXISTS `code` VARCHAR(20) NOT NULL DEFAULT ''
    COMMENT 'short code e.g. SITE, HQ' AFTER `name`;

-- Backfill a readable code for rows created before this column existed.
UPDATE `departments`
  SET `code` = LEFT(UPPER(REPLACE(REPLACE(`name`, ' ', ''), '–', '')), 12)
  WHERE `code` = '';