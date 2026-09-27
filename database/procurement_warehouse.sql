-- =====================================================================
--  Procurement & Warehouse Management System - Khawar Construction Co.
--  Database Script  :  procurement_warehouse_ms
--  MySQL / MariaDB  :  run under XAMPP  (utf8mb4)
--
--  HOW TO INSTALL:
--    Option 1 : `mysql -u root -p < procurement_warehouse.sql`
--    Option 2 : Open http://localhost/ProcurementWarehouseMS/install.php
--
--  Default users (username / password):
--    admin       / admin123    Administrator
--    procurement / password    Procurement Manager
--    warehouse   / password    Warehouse Manager
--    gate        / password    Gate Security
--    committee   / password    Committee Member
--    gm          / password    General Manager
--    employee    / password    Employee (site worker)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `procurement_warehouse_ms`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `procurement_warehouse_ms`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  DROP TABLES (reverse dependency order) - safe re-install
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `committee_approvals`;
DROP TABLE IF EXISTS `gate_checklists`;
DROP TABLE IF EXISTS `quotations`;
DROP TABLE IF EXISTS `consumptions`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `procurement_requests`;
DROP TABLE IF EXISTS `warehouse_items`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
--  USERS
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `password`      VARCHAR(255) NOT NULL COMMENT 'bcrypt hash (password_hash)',
  `name`          VARCHAR(100) NOT NULL,
  `role`          ENUM('employee','procurement_manager','warehouse_manager',
                       'gate_security','committee','general_manager','finance','admin')
                       NOT NULL DEFAULT 'employee',
  `department_id` INT UNSIGNED NULL,
  `email`         VARCHAR(120) NULL,
  `phone`         VARCHAR(30)  NULL,
  `status`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_users_dept` (`department_id`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  DEPARTMENTS
-- ---------------------------------------------------------------------
CREATE TABLE `departments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL UNIQUE,
  `code`       VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'short code e.g. SITE, HQ',
  `parent_id`  INT UNSIGNED NULL COMMENT 'NULL = بخش اصلی؛ عدد = دیپارتمنت زیر آن بخش',
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept_parent` (`parent_id`),
  CONSTRAINT `fk_dept_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  CATEGORIES (add/remove supported at runtime)
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL UNIQUE,
  `parent_id`  INT UNSIGNED NULL COMMENT 'NULL = گروه اصلی؛ عدد = دسته زیر آن گروه',
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SUPPLIERS
-- ---------------------------------------------------------------------
CREATE TABLE `suppliers` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NULL,
  `phone`          VARCHAR(30)  NULL,
  `email`          VARCHAR(120) NULL,
  `address`        VARCHAR(255) NULL,
  `notes`          TEXT         NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  WAREHOUSE ITEMS (inventory)
-- ---------------------------------------------------------------------
CREATE TABLE `warehouse_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150) NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `quantity`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit`        VARCHAR(20)  NOT NULL DEFAULT 'pcs',
  `location`    VARCHAR(60)  NULL,
  `min_stock`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `notes`       TEXT         NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wi_category` (`category_id`),
  CONSTRAINT `fk_wi_category` FOREIGN KEY (`category_id`)
    REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  PROCUREMENT REQUESTS  (statuses drive the whole workflow)
--    pending            -> waiting procurement review
--    closed             -> declared unnecessary by procurement
--    warehouse_check    -> approved; warehouse checks availability
--    purchase_required  -> not available; purchase flow starts
--    quotation_pending  -> normal purchase; collecting supplier quotes
--    committee_pending  -> quotes ready; awaiting committee approval
--    approved           -> approved by committee / urgent approval
--    supplier_selected  -> winning company + price fixed; awaiting presidency
--    authority_approved -> presidency approved; awaiting finance hand-over
--    financed           -> finance handed the money over; ready to order
--    purchased          -> ordered at final supplier
--    received           -> received at gate, checklist complete
--    completed          -> fulfilled (delivered / stored)
-- ---------------------------------------------------------------------
CREATE TABLE `procurement_requests` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_no`     VARCHAR(30)  NOT NULL UNIQUE,
  `department_id`  INT UNSIGNED NOT NULL,
  `category_id`    INT UNSIGNED NULL,
  `item_name`      VARCHAR(150) NOT NULL,
  `quantity`       VARCHAR(50)  NOT NULL COMMENT 'free text amount (supports kg, liter, متر, ...)',
  `unit`           VARCHAR(20)  NOT NULL DEFAULT 'pcs',
  `urgency`        ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
  `direct_delivery` TINYINT(1)  NOT NULL DEFAULT 0,
  `reason`         TEXT         NULL,
  `details`        TEXT         NULL COMMENT 'detailed item specifications',
  `status`         ENUM('pending','closed','warehouse_check','purchase_required',
                        'quotation_pending','committee_pending','approved',
                        'supplier_selected','authority_approved','financed',
                        'purchased','received','completed')
                        NOT NULL DEFAULT 'pending',
  `requested_by`   INT UNSIGNED NOT NULL,
  `employee_name`  VARCHAR(150) NULL COMMENT 'employee name as entered on the request form',
  `employee_position` VARCHAR(150) NULL COMMENT 'job position as entered on the request form',
  `reviewed_by`    INT UNSIGNED NULL,
  `review_note`    TEXT         NULL,
  `reviewed_at`    DATETIME     NULL,
  `warehouse_note` TEXT         NULL,
  `request_date`   DATE         NOT NULL,
  `needed_date`    DATE         NULL COMMENT 'date the item must arrive by',
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_req_dept` (`department_id`),
  KEY `idx_req_status` (`status`),
  KEY `idx_req_date` (`request_date`),
  CONSTRAINT `fk_req_dept` FOREIGN KEY (`department_id`)
    REFERENCES `departments`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_req_cat` FOREIGN KEY (`category_id`)
    REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_req_user` FOREIGN KEY (`requested_by`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_req_reviewer` FOREIGN KEY (`reviewed_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  QUOTATIONS (min 3 for normal purchases)
-- ---------------------------------------------------------------------
CREATE TABLE `quotations` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`    INT UNSIGNED NOT NULL,
  `supplier_id`   INT UNSIGNED NOT NULL,
  `price`         DECIMAL(12,2) NOT NULL,
  `currency`      ENUM('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر',
  `delivery_days` INT          NULL,
  `notes`         TEXT         NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qu_request` (`request_id`),
  CONSTRAINT `fk_qu_request` FOREIGN KEY (`request_id`)
    REFERENCES `procurement_requests`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qu_supplier` FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  PURCHASES
--    draft -> quotation -> committee_pending -> approved
--    -> supplier_selected -> authority_approved -> financed -> ordered
--    -> received -> completed   (rejected only for committee rejection)
-- ---------------------------------------------------------------------
CREATE TABLE `purchases` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_no`      VARCHAR(30)  NOT NULL UNIQUE,
  `request_id`       INT UNSIGNED NOT NULL,
  `supplier_id`      INT UNSIGNED NULL COMMENT 'final supplier',
  `selected_quotation_id` INT UNSIGNED NULL COMMENT 'winning quotation chosen by procurement',
  `quantity`         DECIMAL(12,2) NOT NULL,
  `unit_price`       DECIMAL(12,2) NULL,
  `total_cost`       DECIMAL(12,2) NULL,
  `currency`         ENUM('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر',
  `purchase_date`    DATE         NULL,
  `urgency`          ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
  `payment_status`   ENUM('pending','paid')  NOT NULL DEFAULT 'pending',
  `status`           ENUM('draft','quotation','committee_pending','approved',
                          'supplier_selected','authority_approved','financed',
                          'ordered','received','completed','rejected')
                          NOT NULL DEFAULT 'draft',
  `approved_by`      INT UNSIGNED NULL,
  `committee_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `committee_note`   TEXT         NULL,
  `authority_name`   VARCHAR(150) NULL COMMENT 'name of the approving president / executive director',
  `authority_position` VARCHAR(150) NULL COMMENT 'position of the approving authority',
  `authority_approved_by` INT UNSIGNED NULL COMMENT 'user account that registered the approval',
  `authority_approved_at` DATETIME NULL,
  `authority_note`   TEXT         NULL COMMENT 'comment of the company presidency',
  `finance_method`   ENUM('bank_cheque','cash','bank_transfer','other') NULL COMMENT 'how finance delivers the money',
  `finance_ref_no`   VARCHAR(60)  NULL COMMENT 'cheque / voucher / reference number',
  `finance_amount`   DECIMAL(14,2) NULL COMMENT 'amount handed over by finance',
  `finance_currency` ENUM('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'currency of the handed-over amount',
  `finance_note`     TEXT         NULL COMMENT 'message from finance to the purchasing department',
  `finance_released_by` INT UNSIGNED NULL,
  `finance_released_at` DATETIME   NULL,
  `order_registered_by` INT UNSIGNED NULL,
  `order_registered_at` DATETIME   NULL,
  `announcement_note` TEXT        NULL COMMENT 'public announcement / bulk',
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pur_request` (`request_id`),
  KEY `idx_pur_status` (`status`),
  CONSTRAINT `fk_pur_request` FOREIGN KEY (`request_id`)
    REFERENCES `procurement_requests`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pur_supplier` FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pur_user` FOREIGN KEY (`approved_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  COMMITTEE APPROVALS  (finance + management + procurement log)
-- ---------------------------------------------------------------------
CREATE TABLE `committee_approvals` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id`   INT UNSIGNED NOT NULL,
  `member_name`   VARCHAR(100) NOT NULL,
  `member_role`   VARCHAR(50)  NULL COMMENT 'finance / management / procurement',
  `decision`      ENUM('approved','rejected') NOT NULL DEFAULT 'approved',
  `comment`       TEXT         NULL,
  `approved_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ca_purchase` (`purchase_id`),
  CONSTRAINT `fk_ca_purchase` FOREIGN KEY (`purchase_id`)
    REFERENCES `purchases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  GATE CHECKLIST  (security check for incoming items)
-- ---------------------------------------------------------------------
CREATE TABLE `gate_checklists` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id`      INT UNSIGNED NOT NULL,
  `received_by`      INT UNSIGNED NOT NULL COMMENT 'gate security user',
  `quantity_received` DECIMAL(12,2) NOT NULL,
  `condition_ok`     TINYINT(1)   NOT NULL DEFAULT 1,
  `remarks`          TEXT         NULL,
  `check_date`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gc_purchase` (`purchase_id`),
  CONSTRAINT `fk_gc_purchase` FOREIGN KEY (`purchase_id`)
    REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gc_user` FOREIGN KEY (`received_by`)
    REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  CONSUMPTIONS  (issued from warehouse OR direct delivery to department)
--  Doubles as the handover slip (فورم تسلیمی): handover_no, receiving
--  employee, electronic confirmation + a link back to the purchase.
-- ---------------------------------------------------------------------
CREATE TABLE `consumptions` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`       INT UNSIGNED NULL COMMENT 'warehouse item (NULL = direct)',
  `request_id`    INT UNSIGNED NULL,
  `purchase_id`   INT UNSIGNED NULL COMMENT 'the purchase this delivery came from',
  `department_id` INT UNSIGNED NOT NULL,
  `item_name`     VARCHAR(150) NOT NULL COMMENT 'snapshot of item name',
  `category_id`   INT UNSIGNED NULL,
  `quantity`      DECIMAL(12,2) NOT NULL,
  `unit`          VARCHAR(20)  NOT NULL DEFAULT 'pcs',
  `source`        ENUM('warehouse','direct') NOT NULL DEFAULT 'warehouse',
  `handover_no`   VARCHAR(30)  NULL COMMENT 'handover slip number (HND-YYYY-0001)',
  `receiver_name` VARCHAR(150) NULL COMMENT 'employee that received the item',
  `receiver_position` VARCHAR(150) NULL COMMENT 'job position of the receiving employee',
  `receiver_user_id` INT UNSIGNED NULL COMMENT 'logged-in user confirming the receipt',
  `receiver_confirmed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = requester confirmed receiving the item',
  `confirmed_at`  DATETIME     NULL,
  `handover_note` TEXT         NULL COMMENT 'remarks on the handover slip',
  `delivery_date` DATE         NOT NULL,
  `delivered_by`  INT UNSIGNED NULL,
  `notes`         TEXT         NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cons_dept` (`department_id`),
  KEY `idx_cons_date` (`delivery_date`),
  KEY `idx_cons_purchase` (`purchase_id`),
  KEY `idx_cons_handover` (`handover_no`),
  CONSTRAINT `fk_cons_item` FOREIGN KEY (`item_id`)
    REFERENCES `warehouse_items`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cons_request` FOREIGN KEY (`request_id`)
    REFERENCES `procurement_requests`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cons_dept` FOREIGN KEY (`department_id`)
    REFERENCES `departments`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cons_cat` FOREIGN KEY (`category_id`)
    REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cons_user` FOREIGN KEY (`delivered_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- =====================================================================
--  SEED DATA (reference + demo data; safe to edit or delete)
-- =====================================================================
--  Passwords (bcrypt, generated with PHP password_hash()):
--    'password' : $2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu
--    'admin123' : $2y$10$N18OsByDxvB0joRCtKOJvO25.V.xwlEYrclzG9NTDyjJFUf61V6z6
-- ---------------------------------------------------------------------
INSERT INTO `departments` (`id`,`name`,`code`,`description`,`parent_id`) VALUES
-- شاخه‌های اصلی
(100,'امور دفتر','HQ','بخش مرکزی امور دفتری، اداری، تدارکات و گدام‌داری',NULL),
(200,'پیمان کاران','CTR','تیم‌ها و قراردادی‌های بخش ساخت و ساز',NULL),
-- دیپارتمنت‌های دفتری (زیر «امور دفتر»)
(2,'اداری','ADM','پشتیبانی دفتر و اداری',100),
(3,'مالی','FIN','حسابداری و پرداخت‌ها',100),
(4,'منابع بشری','HR','مدیریت کارمندان',100),
(5,'گدام‌داری','WH','مدیریت گدام، ذخیره و توزیع مواد',100),
(6,'نگهداری و تعمیرات','MNT','نگهداری ماشین‌آلات و سایت',100),
(8,'آشپزخانه','KIT','غذا و سلف‌سرویس',100),
(201,'اجرائیه','EXE','مقام و اجرائیه‌ی امور عمومی',100),
(202,'پالیسی','POL','تدوین و تطبیق پالیسی‌ها و نصاب‌ها',100),
(203,'انجینیری','ENG','امور انجینیری و استندردهای ساختمانی',100),
(204,'تدارکات','PRC','امور خرید، قیمت‌گیری و تدارکات',100),
(205,'آی‌تی','IT','تخنالوژی معلوماتی و سیستم‌های کمپیوتر',100),
-- ساحه و تیم‌های قراردادی (زیر «پیمان کاران»)
(1,'ساخت و ساز','SITE','کارهای ساختمانی و سایت',200),
(7,'کارگاه','WRK','تولید و تعمیرات',200),
(206,'تیم بتن‌کاری','CT1','تیم‌های بتن‌ریزی و کانکریت قراردادی',200),
(207,'تیم خشت‌کاری و بنا','CT2','بناها و خشت‌کاران قراردادی',200),
(208,'تیم لوله‌کشی و صحی','CT3','قراردادی‌های لوله‌کشی و صحی',200),
(209,'تیم برق‌کاری','CT4','قراردادی‌های برق‌کاری ساحه',200),
(210,'تیم رنگ و نقاشی','CT5','قراردادی‌های رنگ‌کاری',200)
ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `categories` (`id`,`name`,`description`,`parent_id`) VALUES
-- گروه‌های اصلی (بخش بالایی)
(11,'مواد و مصالح ساختمانی','مواد خام و مصالح برای ساخت و ساز',NULL),
(12,'اقلام برقی و روشنایی','سیم‌کشی، روشنایی و اتصالات برقی',NULL),
(13,'لوازم لوله‌کشی و صحی','پایپ، شیرآلات و وسایل بهداشتی ساختمان',NULL),
(14,'لوازم اداری و دفتر','ملزومات اداری، لوازم التحریر و فرنیچر',NULL),
(15,'ماشین‌آلات، ابزار و تجهیزات','ماشین‌آلات، ابزار دستی و برقی',NULL),
(16,'تجهیزات ایمنی و حفاظتی','وسایل حفاظت فردی و ایمنی کار',NULL),
(17,'کانتینر و تجهیزات سایت','کانتینرها، قاب‌ها و تجهیزات ساحه',NULL),
(18,'لوازم آشپزخانه و سلف','وسایل پخت و غذا',NULL),
-- دسته‌های موجود
(1,'ماشین‌آلات','ماشین‌آلات سنگین و سبک',15),
(2,'کانتینرها','کانتینرهای ذخیره‌سازی و قاب‌ها',17),
(3,'ملزومات اداری','مواد عمومی اداری',14),
(4,'مبلمان','صندلی، میز، میزکار',14),
(5,'لوازم التحریر','کاغذ، قلم، چاپ',14),
(6,'لوازم آشپزخانه','ظروف آشپزخانه و سلف',18),
(7,'ابزار کارگاه','ابزار دستی و برقی',15),
(8,'مصالح ساختمانی','سیمان، فولاد، سنگ‌دانه',11),
(9,'برقی','سیم‌کشی، اتصالات، روشنایی',12),
(10,'تجهیزات ایمنی','وسایل حفاظت فردی و ایمنی',16),
-- دسته‌های جدید (به دری افغانستانی)
(19,'سیمان و کانکریت آماده','سیمان، پرادخت و کانکریت آماده',11),
(20,'شن، ریگ و سنگ‌دانه','شن، ریگ، جغل و سنگ‌دانه',11),
(21,'خشت و بلاک','خشت، بلاک سمنتی و سفالی',11),
(22,'فولاد و میلگرد','میلگرد، تیرآهن و پروقیل‌ها',11),
(23,'چوب، تخته و الواری','چوب، تخته و الواری چوبی',11),
(24,'رنگ، روغن و مواد نقاشی','رنگ، روغن، تینر و مواد نقاشی',11),
(25,'گچ، ساخف و مواد عایق','ساخف، گچ و مواد عایق حرارتی/برقی',11),
(26,'شیشه و آیینه','شیشه‌ی پنجره، آیینه و کریستال',11),
(27,'سیم و کیبل برقی','سیم و کیبل انواع سایزها',12),
(28,'لامپ و بلب روشنایی','لامپ، بلب و لوازم روشنایی',12),
(29,'سوییچ، پریز و فیوض','سوییچ، پریز، ساکت و فیوض',12),
(30,'لوازم برق‌کاری','نوار چسب، کاندویت، ترمینال و اتصالات',12),
(31,'پایپ و تنب','پایپ PVC، آهنی و تنب‌ها',13),
(32,'شیرآلات و ولو','شیر آب، ولو و اتصالات',13),
(33,'تشناب، سنک و ملزومات حمام','تشناب، سنک و شیرهای حمام',13),
(34,'پمپ و موتر آب','پمپ آب و موترهای آن',13),
(35,'تجهیزات آی‌تی و شبکه','کمپیوتر، پرینتر و وسایل شبکه',14),
(36,'لوازم دفتری عمومی','سوییچ قفل، مالیاتوری، سیلفون و ضمایم',14),
(37,'ماشین‌آلات سنگین','لودر، کریان، کرین و دامپ‌ترک',15),
(38,'ماشین‌آلات سبک','ژنراتور، کمپرسور و کراشر',15),
(39,'ابزار دستی','چلون، پیچ‌کش، کلید و همرو',15),
(40,'ابزار برقی','دریل، انگرایندر و برقی‌ابزارها',15),
(41,'ابزار نجاری و خشت‌کاری','ابزار بنا و نجار',15),
(42,'لباس و وسایل حفاظت فردی','کلاه، دستکش، ماسک و لباس کاری',16),
(43,'وسایل امنیت ساحه','وسایل امنیت و نجات ساحه',16),
(44,'قاب‌های موقتی و اسکان','قاب‌های اداری سایت و اسکان',17),
(45,'تجهیزات ساحه','جدول‌ها، سایه‌بان و تجهیزات سایت',17),
(46,'ظروف و وسایل آشپزخانه','ظروف، سطل و تجهیزات آشپزخانه',18),
(47,'مواد غذایی و سلف','خشکبار و مواد غذایی',18)
ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `users`
(`id`,`username`,`password`,`name`,`role`,`department_id`,`email`,`phone`,`status`) VALUES
(1,'admin','$2y$10$N18OsByDxvB0joRCtKOJvO25.V.xwlEYrclzG9NTDyjJFUf61V6z6','مدیر سیستم','admin',2,'admin@khawar.pk','0300-0000001',1),
(2,'procurement','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','مدیر تدارکات','procurement_manager',2,'procurement@khawar.pk','0300-0000002',1),
(3,'warehouse','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','مدیر انبار','warehouse_manager',5,'warehouse@khawar.pk','0300-0000003',1),
(4,'gate','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','امنیت گیت','gate_security',5,'gate@khawar.pk','0300-0000004',1),
(5,'committee','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','عضو کمیته','committee',3,'committee@khawar.pk','0300-0000005',1),
(6,'gm','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','مدیر عمومی','general_manager',1,'gm@khawar.pk','0300-0000006',1),
(7,'employee','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','علی خان','employee',1,'ali.khan@khawar.pk','0300-0000007',1),
(8,'finance','$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu','مدیر مالی','finance',3,'finance@khawar.pk','0300-0000008',1)
ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `suppliers`
(`id`,`name`,`contact_person`,`phone`,`email`,`address`,`notes`) VALUES
(1,'ماشین‌آلات و ابزار کراچی','آقای عمران','0300-1234567','imran@kmachinery.pk','کراچی','ماشین‌آلات، ابزار کارگاه'),
(2,'لوازم التحریر الفتح','آقای ساجد','0321-7654321','sajid@fatah.pk','لاهور','ملزومات اداری، لوازم التحریر'),
(3,'بازرگانی فولاد و لوله','آقای بلال','0333-1112233','bilal@steelpipes.pk','فیصل‌آباد','فولاد، کانتینر، ساخت‌وساز'),
(4,'مرکز مبلمان','خانم عایشه','0345-4455667','ayesha@furniturehub.pk','اسلام‌آباد','مبلمان اداری و سایت'),
(5,'فروشگاه برقی','آقای کامران','0311-9988776','kamran@elmart.pk','کراچی','اقلام برقی'),
(6,'تأمین مصالح عمده ساختمانی','آقای رشید','0301-5566778','rashid@bulkcs.pk','حیدرآباد','سیمان، شن، سنگ‌دانه، اقلام عمده')
ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `warehouse_items`
(`id`,`name`,`category_id`,`quantity`,`unit`,`location`,`min_stock`,`notes`) VALUES
(1,'سیمان (کیسه ۵۰ کیلویی لکی)',8,250.00,'bag','سوله A / قفسه ۱',50.00,'موجودی پس از صدور'),
(2,'میلگرد ۱۲ میلی‌متری',8,12.50,'ton','سوله B',2.00,NULL),
(3,'بسته کاغذ A4',5,180.00,'ream','اتاق انبار ۱',20.00,NULL),
(4,'صندلی اداری (مدیریتی)',4,15.00,'pcs','اتاق انبار ۲',2.00,NULL),
(5,'کلاه ایمنی',10,45.00,'pcs','قفسه ایمنی',10.00,NULL),
(6,'ژنراتور دیزلی ۵۰۰۰ وات',1,2.00,'pcs','محوطه ماشین‌آلات',1.00,NULL),
(7,'دریل کارگاه',7,6.00,'pcs','قفس ابزار',1.00,NULL),
(8,'کانتینر فولادی ۲۰ فوت',2,3.00,'pcs','حیاط بیرونی',1.00,NULL),
(9,'سطل پلاستیکی',6,40.00,'pcs','قفسه آشپزخانه',10.00,NULL)
ON DUPLICATE KEY UPDATE `name`=`name`;
-- =====================================================================
--  SAMPLE WORKFLOW DATA (demo records - delete freely)
-- =====================================================================
INSERT INTO `procurement_requests`
(`id`,`request_no`,`department_id`,`category_id`,`item_name`,`quantity`,`unit`,
 `urgency`,`direct_delivery`,`reason`,`status`,`requested_by`,`reviewed_by`,
 `review_note`,`reviewed_at`,`warehouse_note`,`request_date`) VALUES
(1,'REQ-2026-0001',1,8,'سیمان (کیسه ۵۰ کیلویی لکی)',100.00,'bag','urgent',0,
 'کار فونداسیون سایت','completed',7,2,'تأیید شد - صدور از انبار',
 '2026-01-05 09:10:00','صادر شده از سوله A','2026-01-05'),
(2,'REQ-2026-0002',5,2,'کانتینر فولادی ۲۰ فوت',1.00,'pcs','urgent',1,
 'توسعه فضای انبار','completed',7,2,'تأیید شد - خرید فوری',
 '2026-02-08 11:25:00','تحویل مستقیم به لجستیک','2026-02-08'),
(3,'REQ-2026-0003',2,5,'بسته کاغذ A4',30.00,'ream','normal',0,
 'لوازم التحریر فصلی','completed',7,2,'تأیید شد - صدور از انبار',
 '2026-03-01 10:05:00','صادر شده از اتاق انبار ۱','2026-03-01'),
(4,'REQ-2026-0004',4,10,'کلاه ایمنی',25.00,'pcs','normal',1,
 'تجهیزات ایمنی برای کارمندان جدید','completed',6,2,'تأیید شد - خرید با قیمت‌گیری',
 '2026-04-03 12:20:00',NULL,'2026-04-02'),
(5,'REQ-2026-0005',7,7,'دریل کارگاه',2.00,'pcs','urgent',1,
 'موتور دریل سوخته','completed',7,2,'تأیید شد - خرید فوری',
 '2026-05-09 16:40:00','تحویل مستقیم به کارگاه','2026-05-09'),
(6,'REQ-2026-0006',3,4,'صندلی اداری (مدیریتی)',4.00,'pcs','normal',0,
 'صندلی واحد مالی','received',7,2,'تأیید شد - خرید با قیمت‌گیری',
 '2026-06-13 09:30:00',NULL,'2026-06-12'),
(7,'REQ-2026-0007',6,9,'کویل سیم مسی ۵۰ متری',10.00,'pcs','normal',0,
 'نگهداری برق','committee_pending',7,2,'تأیید شد - در انتظار کمیته',
 '2026-07-06 10:00:00',NULL,'2026-07-05'),
(8,'REQ-2026-0008',8,6,'سطل پلاستیکی',20.00,'pcs','normal',0,
 'تعویض لوازم آشپزخانه','closed',7,2,'رد شد - از موجودی استفاده شود',
 '2026-08-02 14:15:00',NULL,'2026-08-01'),
(9,'REQ-2026-0009',2,3,'کارتریج جوهر چاپگر اداری',6.00,'pcs','urgent',1,
 'جوهر چاپگر تمام شده','purchase_required',7,2,'تأیید شد - در انبار موجود نیست',
 '2026-08-21 09:00:00','موجود نیست (بازبینی ۲۰۲۶-۰۸-۲۱)','2026-08-20'),
(10,'REQ-2026-0010',1,8,'میلگرد ۱۲ میلی‌متری',3.00,'ton','normal',1,
 'میلگرد برای بتن‌ریزی دال','pending',7,NULL,NULL,NULL,NULL,'2026-09-01'),
(11,'REQ-2026-0011',1,1,'تیغه‌های باکت بیل مکانیکی',8.00,'pcs','urgent',1,
 'ساییدگی تیغه‌های لودر','warehouse_check',7,2,'تأیید شد - بررسی انبار',
 '2026-09-10 09:15:00',NULL,'2026-09-10'),
(12,'REQ-2025-0001',1,8,'سیمان (کیسه ۵۰ کیلویی لکی)',200.00,'bag','urgent',0,
 'فونداسیون بلوک برج','completed',7,2,'تأیید شد - صدور از انبار',
 '2025-11-03 10:20:00','صادر شده از سوله A','2025-11-03'),
(13,'REQ-2025-0002',5,2,'کانتینر فولادی ۲۰ فوت',1.00,'pcs','urgent',1,
 'توسعه فضای انبار','completed',7,2,'تأیید شد - خرید فوری',
 '2025-12-12 13:00:00','تحویل مستقیم','2025-12-14')
ON DUPLICATE KEY UPDATE `item_name`=`item_name`;

-- Sample values for the new request fields (details + needed_date)
UPDATE `procurement_requests` SET
  `needed_date` = DATE_ADD(`request_date`, INTERVAL 7 DAY),
  `details` = CASE `id`
    WHEN 1  THEN 'سیمان پورتی درجه یک، کیسه ۵۰ کیلویی - لکی'
    WHEN 2  THEN 'کانتینر ۲۰ فوت استاندارد دریایی، گمرک‌کشیده'
    WHEN 5  THEN 'دریل چکشی برند بوش، ۱۱۰۰ وات'
    WHEN 6  THEN 'صندلی مدیریتی با گارانتی، چرم مصنوعی'
    WHEN 10 THEN 'کمپیوتر دل: رم ۳۲ گیگابایت، حافظه ۱ ترابایت، کور i9 نسل ۱۰، گرافیک ۸ گیگابایت'
    ELSE NULL
  END;

INSERT INTO `consumptions`
(`id`,`item_id`,`request_id`,`purchase_id`,`department_id`,`item_name`,`category_id`,`quantity`,
 `unit`,`source`,`handover_no`,`receiver_name`,`receiver_position`,`receiver_confirmed`,`confirmed_at`,
 `delivery_date`,`delivered_by`,`notes`) VALUES
(1,1,1,NULL,1,'سیمان (کیسه ۵۰ کیلویی لکی)',8,100.00,'bag','warehouse','HND-2026-0001','علی خان','کارگر ساخت',1,'2026-01-06 14:00:00','2026-01-06',3,'صادر شده برای کار فونداسیون'),
(2,NULL,2,1,5,'کانتینر فولادی ۲۰ فوت',2,1.00,'pcs','direct','HND-2026-0002','علی خان','کارگر ساخت',1,'2026-02-20 11:00:00','2026-02-20',3,'تحویل مستقیم هنگام خرید'),
(3,3,3,NULL,2,'بسته کاغذ A4',5,30.00,'ream','warehouse','HND-2026-0003','علی خان','کارگر ساخت',1,'2026-03-02 10:30:00','2026-03-02',3,'صدور فصلی'),
(4,NULL,4,2,4,'کلاه ایمنی',10,25.00,'pcs','direct','HND-2026-0004','علی خان','کارگر ساخت',1,'2026-04-18 09:15:00','2026-04-18',3,'تحویل مستقیم هنگام خرید'),
(5,NULL,5,3,7,'دریل کارگاه',7,2.00,'pcs','direct','HND-2026-0005','علی خان','کارگر ساخت',1,'2026-05-21 15:45:00','2026-05-21',3,'تحویل مستقیم هنگام خرید'),
(6,1,12,NULL,1,'سیمان (کیسه ۵۰ کیلویی لکی)',8,200.00,'bag','warehouse','HND-2025-0001','علی خان','کارگر ساخت',1,'2025-11-04 13:20:00','2025-11-04',3,'فونداسیون بلوک برج'),
(7,NULL,13,6,5,'کانتینر فولادی ۲۰ فوت',2,1.00,'pcs','direct','HND-2025-0002','علی خان','کارگر ساخت',1,'2025-12-20 10:00:00','2025-12-20',3,'تحویل مستقیم هنگام خرید')
ON DUPLICATE KEY UPDATE `item_name`=`item_name`;
INSERT INTO `purchases`
(`id`,`purchase_no`,`request_id`,`supplier_id`,`quantity`,`unit_price`,`total_cost`,
 `purchase_date`,`urgency`,`payment_status`,`status`,`approved_by`,`committee_approved`,
 `committee_note`,`announcement_note`) VALUES
(1,'PUR-2026-0001',2,3,1.00,850000.00,850000.00,'2026-02-10','urgent','paid','completed',2,1,'فوری - تأیید فوری',NULL),
(2,'PUR-2026-0002',4,1,25.00,1500.00,37500.00,'2026-04-05','normal','paid','completed',2,1,'کمترین قیمت پیشنهادی',NULL),
(3,'PUR-2026-0003',5,1,2.00,18500.00,37000.00,'2026-05-11','urgent','paid','completed',2,1,'فوری - تأیید فوری',NULL),
(4,'PUR-2026-0004',6,4,4.00,12000.00,48000.00,'2026-06-15','normal','paid','received',2,1,'کمترین قیمت پیشنهادی',NULL),
(5,'PUR-2026-0005',7,5,10.00,3500.00,35000.00,'2026-07-10','normal','pending','committee_pending',2,0,'در انتظار کمیته',NULL),
(6,'PUR-2025-0001',13,3,1.00,820000.00,820000.00,'2025-12-15','urgent','paid','completed',2,1,'فوری - تأیید فوری',NULL)
ON DUPLICATE KEY UPDATE `purchase_no`=`purchase_no`;

INSERT INTO `quotations` (`id`,`request_id`,`supplier_id`,`price`,`delivery_days`,`notes`) VALUES
(1,4,1,1500.00,10,'درجه ساخت‌وساز اصلی'),
(2,4,5,1650.00,7,'تحویل سریع'),
(3,4,4,1480.00,12,'تخفیف عمده'),
(4,6,4,12000.00,15,'نو - گارانتی ۲ سال'),
(5,6,1,12500.00,20,NULL),
(6,6,2,12150.00,25,NULL),
(7,7,5,3500.00,5,'دارای نشان ISI'),
(8,7,1,3800.00,10,NULL),
(9,7,6,3600.00,8,'تحویل در سایت')
ON DUPLICATE KEY UPDATE `price`=`price`;

INSERT INTO `committee_approvals`
(`purchase_id`,`member_name`,`member_role`,`decision`,`comment`,`approved_at`) VALUES
(2,'فیصل - مالی','finance','approved','بودجه موجود است','2026-04-06 10:00:00'),
(2,'ریضوان - مدیریت','management','approved','کمترین قیمت پذیرفته شد','2026-04-06 11:30:00'),
(2,'مدیر تدارکات','procurement','approved','توصیه به کمترین قیمت','2026-04-06 12:00:00'),
(4,'فیصل - مالی','finance','approved','در چارچوب بودجه','2026-06-16 10:00:00'),
(4,'ریضوان - مدیریت','management','approved','تأیید','2026-06-16 11:00:00'),
(4,'مدیر تدارکات','procurement','approved','تصویب شد','2026-06-16 12:00:00');

INSERT INTO `gate_checklists`
(`purchase_id`,`received_by`,`quantity_received`,`condition_ok`,`remarks`,`check_date`) VALUES
(1,4,1.00,1,'مطابق سفارش خرید','2026-02-18 10:00:00'),
(2,4,25.00,1,'همه کلاه‌ها نو در جعبه','2026-04-16 09:30:00'),
(3,4,2.00,1,'هر دو دستگاه تست شد','2026-05-20 15:00:00'),
(4,4,4.00,1,'جعبه‌ها مهر و موم شده','2026-06-25 11:00:00'),
(6,4,1.00,1,'کانتینر در وضعیت خوب','2025-12-28 10:30:00');

-- database created & seeded successfully
-- /end