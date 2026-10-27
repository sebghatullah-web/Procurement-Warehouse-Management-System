-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2026 at 07:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `procurement_warehouse_ms`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `description`, `created_at`) VALUES
(1, 'ماشین‌آلات', 15, 'ماشین‌آلات سنگین و سبک', '2026-09-24 08:19:01'),
(2, 'کانتینرها', 17, 'کانتینرهای ذخیره‌سازی و قاب‌ها', '2026-09-24 08:19:01'),
(3, 'ملزومات اداری', 14, 'مواد عمومی اداری', '2026-09-24 08:19:01'),
(4, 'مبلمان', 14, 'صندلی، میز، میزکار', '2026-09-24 08:19:01'),
(5, 'لوازم التحریر', 14, 'کاغذ، قلم، چاپ', '2026-09-24 08:19:01'),
(6, 'لوازم آشپزخانه', 18, 'ظروف آشپزخانه و سلف', '2026-09-24 08:19:01'),
(7, 'ابزار کارگاه', 15, 'ابزار دستی و برقی', '2026-09-24 08:19:01'),
(8, 'مصالح ساختمانی', 11, 'سیمان، فولاد، سنگ‌دانه', '2026-09-24 08:19:01'),
(9, 'برقی', 12, 'سیم‌کشی، اتصالات، روشنایی', '2026-09-24 08:19:01'),
(10, 'تجهیزات ایمنی', 16, 'وسایل حفاظت فردی و ایمنی', '2026-09-24 08:19:01'),
(11, 'مواد و مصالح ساختمانی', NULL, 'مواد خام و مصالح برای ساخت و ساز', '2026-09-27 06:18:30'),
(12, 'اقلام برقی و روشنایی', NULL, 'سیم‌کشی، روشنایی و اتصالات برقی', '2026-09-27 06:18:30'),
(13, 'لوازم لوله‌کشی و صحی', NULL, 'پایپ، شیرآلات و وسایل بهداشتی ساختمان', '2026-09-27 06:18:30'),
(14, 'لوازم اداری و دفتر', NULL, 'ملزومات اداری، لوازم التحریر و فرنیچر', '2026-09-27 06:18:30'),
(15, 'ماشین‌آلات، ابزار و تجهیزات', NULL, 'ماشین‌آلات، ابزار دستی و برقی', '2026-09-27 06:18:30'),
(16, 'تجهیزات ایمنی و حفاظتی', NULL, 'وسایل حفاظت فردی و ایمنی کار', '2026-09-27 06:18:30'),
(17, 'کانتینر و تجهیزات سایت', NULL, 'کانتینرها، قاب‌ها و تجهیزات ساحه', '2026-09-27 06:18:30'),
(18, 'لوازم آشپزخانه و سلف', NULL, 'وسایل پخت و غذا', '2026-09-27 06:18:30'),
(19, 'سیمان و کانکریت آماده', 11, 'سیمان، پرادخت و کانکریت آماده', '2026-09-27 06:18:30'),
(20, 'شن، ریگ و سنگ‌دانه', 11, 'شن، ریگ، جغل و سنگ‌دانه', '2026-09-27 06:18:30'),
(21, 'خشت و بلاک', 11, 'خشت، بلاک سمنتی و سفالی', '2026-09-27 06:18:30'),
(22, 'فولاد و میلگرد', 11, 'میلگرد، تیرآهن و پروقیل‌ها', '2026-09-27 06:18:30'),
(23, 'چوب، تخته و الواری', 11, 'چوب، تخته و الواری چوبی', '2026-09-27 06:18:30'),
(24, 'رنگ، روغن و مواد نقاشی', 11, 'رنگ، روغن، تینر و مواد نقاشی', '2026-09-27 06:18:30'),
(25, 'گچ، ساخف و مواد عایق', 11, 'ساخف، گچ و مواد عایق حرارتی/برقی', '2026-09-27 06:18:30'),
(26, 'شیشه و آیینه', 11, 'شیشه‌ی پنجره، آیینه و کریستال', '2026-09-27 06:18:30'),
(27, 'سیم و کیبل برقی', 12, 'سیم و کیبل انواع سایزها', '2026-09-27 06:18:30'),
(28, 'لامپ و بلب روشنایی', 12, 'لامپ، بلب و لوازم روشنایی', '2026-09-27 06:18:30'),
(29, 'سوییچ، پریز و فیوض', 12, 'سوییچ، پریز، ساکت و فیوض', '2026-09-27 06:18:30'),
(30, 'لوازم برق‌کاری', 12, 'نوار چسب، کاندویت، ترمینال و اتصالات', '2026-09-27 06:18:30'),
(31, 'پایپ و تنب', 13, 'پایپ PVC، آهنی و تنب‌ها', '2026-09-27 06:18:30'),
(32, 'شیرآلات و ولو', 13, 'شیر آب، ولو و اتصالات', '2026-09-27 06:18:30'),
(33, 'تشناب، سنک و ملزومات حمام', 13, 'تشناب، سنک و شیرهای حمام', '2026-09-27 06:18:30'),
(34, 'پمپ و موتر آب', 13, 'پمپ آب و موترهای آن', '2026-09-27 06:18:30'),
(35, 'تجهیزات آی‌تی و شبکه', 14, 'کمپیوتر، پرینتر و وسایل شبکه', '2026-09-27 06:18:30'),
(36, 'لوازم دفتری عمومی', 14, 'سوییچ قفل، مالیاتوری، سیلفون و ضمایم', '2026-09-27 06:18:30'),
(37, 'ماشین‌آلات سنگین', 15, 'لودر، کریان، کرین و دامپ‌ترک', '2026-09-27 06:18:30'),
(38, 'ماشین‌آلات سبک', 15, 'ژنراتور، کمپرسور و کراشر', '2026-09-27 06:18:30'),
(39, 'ابزار دستی', 15, 'چلون، پیچ‌کش، کلید و همرو', '2026-09-27 06:18:30'),
(40, 'ابزار برقی', 15, 'دریل، انگرایندر و برقی‌ابزارها', '2026-09-27 06:18:30'),
(41, 'ابزار نجاری و خشت‌کاری', 15, 'ابزار بنا و نجار', '2026-09-27 06:18:30'),
(42, 'لباس و وسایل حفاظت فردی', 16, 'کلاه، دستکش، ماسک و لباس کاری', '2026-09-27 06:18:30'),
(43, 'وسایل امنیت ساحه', 16, 'وسایل امنیت و نجات ساحه', '2026-09-27 06:18:30'),
(44, 'قاب‌های موقتی و اسکان', 17, 'قاب‌های اداری سایت و اسکان', '2026-09-27 06:18:30'),
(45, 'تجهیزات ساحه', 17, 'جدول‌ها، سایه‌بان و تجهیزات سایت', '2026-09-27 06:18:30'),
(46, 'ظروف و وسایل آشپزخانه', 18, 'ظروف، سطل و تجهیزات آشپزخانه', '2026-09-27 06:18:30'),
(47, 'مواد غذایی و سلف', 18, 'خشکبار و مواد غذایی', '2026-09-27 06:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `committee_approvals`
--

CREATE TABLE `committee_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_id` int(10) UNSIGNED NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `member_role` varchar(50) DEFAULT NULL COMMENT 'finance / management / procurement',
  `decision` enum('approved','rejected') NOT NULL DEFAULT 'approved',
  `comment` text DEFAULT NULL,
  `approved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `committee_approvals`
--

INSERT INTO `committee_approvals` (`id`, `purchase_id`, `member_name`, `member_role`, `decision`, `comment`, `approved_at`) VALUES
(7, 7, 'حامد', 'finance', 'approved', 'بازرگانی فولاد و لوله: 20000 افغانی', '2026-09-26 16:13:04'),
(8, 9, 'بهادری', 'management', 'approved', 'نظر به رای گیر هر سه عضو تصویب شد که از فروشگاه وسایل ساختمانی بلاک خریداری شود.', '2026-09-27 10:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `consumptions`
--

CREATE TABLE `consumptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'warehouse item (NULL = direct)',
  `request_id` int(10) UNSIGNED DEFAULT NULL,
  `purchase_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'the purchase this delivery came from',
  `department_id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(150) NOT NULL COMMENT 'snapshot of item name',
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `source` enum('warehouse','direct') NOT NULL DEFAULT 'warehouse',
  `handover_no` varchar(30) DEFAULT NULL COMMENT 'handover slip number (HND-YYYY-0001)',
  `receiver_name` varchar(150) DEFAULT NULL COMMENT 'employee that received the item',
  `receiver_position` varchar(150) DEFAULT NULL COMMENT 'job position of the receiving employee',
  `receiver_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'logged-in user confirming the receipt',
  `receiver_confirmed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = requester confirmed receiving the item',
  `confirmed_at` datetime DEFAULT NULL,
  `handover_note` text DEFAULT NULL COMMENT 'remarks on the handover slip',
  `delivery_date` date NOT NULL,
  `delivered_by` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consumptions`
--

INSERT INTO `consumptions` (`id`, `item_id`, `request_id`, `purchase_id`, `department_id`, `item_name`, `category_id`, `quantity`, `unit`, `source`, `handover_no`, `receiver_name`, `receiver_position`, `receiver_user_id`, `receiver_confirmed`, `confirmed_at`, `handover_note`, `delivery_date`, `delivered_by`, `notes`, `created_at`) VALUES
(1, 1, NULL, NULL, 1, 'سیمان (کیسه ۵۰ کیلویی لکی)', 8, 100.00, 'bag', 'warehouse', NULL, NULL, NULL, NULL, 1, '2026-01-06 00:00:00', NULL, '2026-01-06', 3, 'صادر شده برای کار فونداسیون', '2026-09-24 08:19:01'),
(2, NULL, NULL, NULL, 5, 'کانتینر فولادی ۲۰ فوت', 2, 1.00, 'pcs', 'direct', NULL, NULL, NULL, NULL, 1, '2026-02-20 00:00:00', NULL, '2026-02-20', 3, 'تحویل مستقیم هنگام خرید', '2026-09-24 08:19:01'),
(3, 3, NULL, NULL, 2, 'بسته کاغذ A4', 5, 30.00, 'ream', 'warehouse', NULL, NULL, NULL, NULL, 1, '2026-03-02 00:00:00', NULL, '2026-03-02', 3, 'صدور فصلی', '2026-09-24 08:19:01'),
(4, NULL, NULL, NULL, 4, 'کلاه ایمنی', 10, 25.00, 'pcs', 'direct', NULL, NULL, NULL, NULL, 1, '2026-04-18 00:00:00', NULL, '2026-04-18', 3, 'تحویل مستقیم هنگام خرید', '2026-09-24 08:19:01'),
(5, NULL, NULL, NULL, 7, 'دریل کارگاه', 7, 2.00, 'pcs', 'direct', NULL, NULL, NULL, NULL, 1, '2026-05-21 00:00:00', NULL, '2026-05-21', 3, 'تحویل مستقیم هنگام خرید', '2026-09-24 08:19:01'),
(6, 1, NULL, NULL, 1, 'سیمان (کیسه ۵۰ کیلویی لکی)', 8, 200.00, 'bag', 'warehouse', NULL, NULL, NULL, NULL, 1, '2025-11-04 00:00:00', NULL, '2025-11-04', 3, 'فونداسیون بلوک برج', '2026-09-24 08:19:01'),
(7, NULL, NULL, NULL, 5, 'کانتینر فولادی ۲۰ فوت', 2, 1.00, 'pcs', 'direct', NULL, NULL, NULL, NULL, 1, '2025-12-20 00:00:00', NULL, '2025-12-20', 3, 'تحویل مستقیم هنگام خرید', '2026-09-24 08:19:01'),
(8, 11, 14, 7, 2, 'UPS Unit', NULL, 2.00, 'pcs', 'warehouse', 'HND-2026-0001', 'Test Receiver', 'Engineer', 7, 1, '2026-09-27 09:02:38', NULL, '2026-09-27', 3, 'test e2e', '2026-09-27 04:31:39'),
(9, NULL, 16, 9, 2, 'کمپیوتر', 9, 2.00, 'pcs', 'direct', 'HND-2026-0002', 'بهادری', 'کارمند ای تی', 3, 1, '2026-09-27 10:25:55', NULL, '2026-09-27', 3, 'وسایل درخواست شده به آقای بهادری تحویل داده شد.', '2026-09-27 05:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT 'short code e.g. SITE, HQ',
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `parent_id`, `description`, `created_at`) VALUES
(1, 'ساخت و ساز', '', 200, 'کارهای ساختمانی و سایت', '2026-09-24 08:19:01'),
(2, 'اداری', '', 100, 'پشتیبانی دفتر و اداری', '2026-09-24 08:19:01'),
(3, 'مالی', '', 100, 'حسابداری و پرداخت‌ها', '2026-09-24 08:19:01'),
(4, 'منابع بشری', '', 100, 'مدیریت کارمندان', '2026-09-24 08:19:01'),
(5, 'گدام‌داری', 'WH', 100, 'مدیریت گدام، ذخیره و توزیع مواد', '2026-09-24 08:19:01'),
(6, 'نگهداری و تعمیرات', '', 100, 'نگهداری ماشین‌آلات و سایت', '2026-09-24 08:19:01'),
(7, 'کارگاه', '', 200, 'تولید و تعمیرات', '2026-09-24 08:19:01'),
(8, 'آشپزخانه', '', 100, 'غذا و سلف‌سرویس', '2026-09-24 08:19:01'),
(100, 'امور دفتر', 'HQ', NULL, 'بخش مرکزی امور دفتری، اداری، تدارکات و گدام‌داری', '2026-09-27 06:18:30'),
(200, 'پیمان کاران', 'CTR', NULL, 'تیم‌ها و قراردادی‌های بخش ساخت و ساز', '2026-09-27 06:18:30'),
(201, 'اجرائیه', 'EXE', 100, 'مقام و اجرائیه‌ی امور عمومی', '2026-09-27 06:18:30'),
(202, 'پالیسی', 'POL', 100, 'تدوین و تطبیق پالیسی‌ها و نصاب‌ها', '2026-09-27 06:18:30'),
(203, 'انجینیری', 'ENG', 100, 'امور انجینیری و استندردهای ساختمانی', '2026-09-27 06:18:30'),
(204, 'تدارکات', 'PRC', 100, 'امور خرید، قیمت‌گیری و تدارکات', '2026-09-27 06:18:30'),
(205, 'آی‌تی', 'IT', 100, 'تخنالوژی معلوماتی و سیستم‌های کمپیوتر', '2026-09-27 06:18:30'),
(206, 'تیم بتن‌کاری', 'CT1', 200, 'تیم‌های بتن‌ریزی و کانکریت قراردادی', '2026-09-27 06:18:30'),
(207, 'تیم خشت‌کاری و بنا', 'CT2', 200, 'بناها و خشت‌کاران قراردادی', '2026-09-27 06:18:30'),
(208, 'تیم لوله‌کشی و صحی', 'CT3', 200, 'قراردادی‌های لوله‌کشی و صحی', '2026-09-27 06:18:30'),
(209, 'تیم برق‌کاری', 'CT4', 200, 'قراردادی‌های برق‌کاری ساحه', '2026-09-27 06:18:30'),
(210, 'تیم رنگ و نقاشی', 'CT5', 200, 'قراردادی‌های رنگ‌کاری', '2026-09-27 06:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `gate_checklists`
--

CREATE TABLE `gate_checklists` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_id` int(10) UNSIGNED NOT NULL,
  `received_by` int(10) UNSIGNED NOT NULL COMMENT 'gate security user',
  `quantity_received` decimal(12,2) NOT NULL,
  `condition_ok` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `check_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gate_checklists`
--

INSERT INTO `gate_checklists` (`id`, `purchase_id`, `received_by`, `quantity_received`, `condition_ok`, `remarks`, `check_date`) VALUES
(6, 7, 4, 1.00, 1, 'سالم هستند', '2026-09-26 16:16:27'),
(7, 9, 4, 2.00, 1, 'سالم هستند', '2026-09-27 10:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE `procurement_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_no` varchar(30) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` varchar(50) NOT NULL COMMENT 'free text amount (supports kg, liter, متر, ...)',
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `request_type` enum('purchase','rental') NOT NULL DEFAULT 'purchase',
  `rental_duration` decimal(8,2) DEFAULT NULL,
  `rental_duration_unit` enum('hour','day','week','month') DEFAULT NULL,
  `rental_location` varchar(255) DEFAULT NULL,
  `operator_required` tinyint(1) NOT NULL DEFAULT 0,
  `urgency` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `direct_delivery` tinyint(1) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `details` text DEFAULT NULL COMMENT 'detailed item specifications',
  `status` enum('pending','closed','warehouse_check','purchase_required','quotation_pending','committee_pending','approved','supplier_selected','authority_approved','financed','purchased','received','rental_required','rental_active','completed') NOT NULL DEFAULT 'pending',
  `requested_by` int(10) UNSIGNED NOT NULL,
  `employee_name` varchar(150) DEFAULT NULL COMMENT 'employee name as entered on the request form',
  `employee_position` varchar(150) DEFAULT NULL COMMENT 'job position as entered on the request form',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `warehouse_note` text DEFAULT NULL,
  `request_date` date NOT NULL,
  `needed_date` date DEFAULT NULL COMMENT 'date the item must arrive by',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_requests`
--

INSERT INTO `procurement_requests` (`id`, `request_no`, `department_id`, `category_id`, `item_name`, `quantity`, `unit`, `request_type`, `rental_duration`, `rental_duration_unit`, `rental_location`, `operator_required`, `urgency`, `direct_delivery`, `reason`, `details`, `status`, `requested_by`, `employee_name`, `employee_position`, `reviewed_by`, `review_note`, `reviewed_at`, `warehouse_note`, `request_date`, `needed_date`, `created_at`, `updated_at`) VALUES
(14, 'REQ-2026-0001', 2, 9, 'پرینتر 6 رنگه همه کاره', '1', 'pcs', 'purchase', NULL, NULL, NULL, 0, 'normal', 1, 'بخش ای تی نظر به پیش برد کار های خویش نیاز به یک پرینتر 6 رنگه همه کاره دارد.', 'epson l8100', 'completed', 7, 'صبغت الله', 'بخش آی تی', 2, 'درخواست فوق را دارید یا خیر؟', '2026-09-26 16:02:56', 'کالا مورد ضرورت در انبار موجود نیست.', '2026-09-26', '2026-10-06', '2026-09-26 11:30:24', '2026-09-27 04:31:39'),
(16, 'REQ-2026-0002', 2, 9, 'کمپیوتر', '2', 'pcs', 'purchase', NULL, NULL, NULL, 0, 'normal', 1, 'نظر به پیش برد کاری های خویش بخش ای تی دو کمپیوتر های فوق را ضرورت دارد.', 'کمپیوتر دل، رم 32 گیگا بایت، کور i9، نسل 11، حافظه 1 تیرابایت', 'completed', 7, 'بهادری', 'کارمند ای تی', 2, 'آیا درخواستی فوق در گدام موجود است؟', '2026-09-27 09:31:44', 'کالا مورد نظر در انبار موجود نیست.', '2026-09-27', '2026-10-07', '2026-09-27 04:58:42', '2026-09-27 05:55:26'),
(20, 'REQ-2026-0003', 207, NULL, 'بالابر', '1', 'pcs', 'rental', 1.00, 'month', 'شهرک سبز', 0, 'normal', 0, 'جهت پیش برد خشت کاری', 'بمنتبمنتنبتمنیسشتب', 'rental_active', 1, 'مدیر سیستم', '', 1, 'یک پایه بالابر باید برای این ها به کرایه گرفته شود.', '2026-10-11 09:38:04', NULL, '2026-10-11', '2026-10-13', '2026-10-11 05:06:44', '2026-10-11 05:12:51');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_no` varchar(30) NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'final supplier',
  `selected_quotation_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'winning quotation chosen by procurement',
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT NULL,
  `currency` enum('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر',
  `purchase_date` date DEFAULT NULL,
  `urgency` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `status` enum('draft','quotation','committee_pending','approved','supplier_selected','authority_approved','financed','ordered','received','completed','rejected') NOT NULL DEFAULT 'draft',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `committee_approved` tinyint(1) NOT NULL DEFAULT 0,
  `committee_note` text DEFAULT NULL,
  `authority_name` varchar(150) DEFAULT NULL COMMENT 'name of the approving president / executive director',
  `authority_position` varchar(150) DEFAULT NULL COMMENT 'position of the approving authority',
  `authority_approved_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'user account that registered the approval',
  `authority_approved_at` datetime DEFAULT NULL,
  `authority_note` text DEFAULT NULL COMMENT 'comment of the company presidency',
  `finance_method` enum('bank_cheque','cash','bank_transfer','other') DEFAULT NULL COMMENT 'how finance delivers the money',
  `finance_ref_no` varchar(60) DEFAULT NULL COMMENT 'cheque / voucher / reference number',
  `finance_amount` decimal(14,2) DEFAULT NULL COMMENT 'amount handed over by finance',
  `finance_currency` enum('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'currency of the handed-over amount',
  `finance_note` text DEFAULT NULL COMMENT 'message from finance to the purchasing department',
  `finance_released_by` int(10) UNSIGNED DEFAULT NULL,
  `finance_released_at` datetime DEFAULT NULL,
  `order_registered_by` int(10) UNSIGNED DEFAULT NULL,
  `order_registered_at` datetime DEFAULT NULL,
  `announcement_note` text DEFAULT NULL COMMENT 'public announcement / bulk',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `purchase_no`, `request_id`, `supplier_id`, `selected_quotation_id`, `quantity`, `unit_price`, `total_cost`, `currency`, `purchase_date`, `urgency`, `payment_status`, `status`, `approved_by`, `committee_approved`, `committee_note`, `authority_name`, `authority_position`, `authority_approved_by`, `authority_approved_at`, `authority_note`, `finance_method`, `finance_ref_no`, `finance_amount`, `finance_currency`, `finance_note`, `finance_released_by`, `finance_released_at`, `order_registered_by`, `order_registered_at`, `announcement_note`, `created_at`, `updated_at`) VALUES
(7, 'PUR-2026-0001', 14, 5, NULL, 1.00, 20000.00, 20000.00, 'AFN', '2026-09-26', 'normal', 'pending', 'completed', NULL, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AFN', NULL, NULL, NULL, NULL, NULL, '', '2026-09-26 11:37:45', '2026-09-27 04:31:39'),
(9, 'PUR-2026-0002', 16, 3, 15, 2.00, 25000.00, 50000.00, 'AFN', '2026-09-27', 'normal', 'paid', 'completed', NULL, 1, 'نرخ گیری تکمیل شد.', 'عبدالعالی امین', 'مدیریت اجرائی', 6, '2026-09-27 10:13:44', '', 'cash', '', 50000.00, 'AFN', 'هزینه این درخواست به شکل پول نقد برای بخش تدارکات تسلیم کردید.', 1, '2026-09-27 10:18:46', 2, '2026-09-27 10:20:10', 'سفارش داده شد', '2026-09-27 05:07:12', '2026-09-27 05:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `currency` enum('AFN','USD') NOT NULL DEFAULT 'AFN' COMMENT 'AFN = افغانی, USD = دالر',
  `delivery_days` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `request_id`, `supplier_id`, `price`, `currency`, `delivery_days`, `notes`, `created_at`) VALUES
(10, 14, 5, 400.00, 'USD', 3, '', '2026-09-26 11:38:24'),
(11, 14, 3, 20000.00, 'AFN', 5, '', '2026-09-26 11:38:51'),
(12, 14, 1, 500.00, 'USD', 1, '', '2026-09-26 11:39:11'),
(13, 16, 9, 30000.00, 'AFN', NULL, '', '2026-09-27 05:30:44'),
(14, 16, 5, 400.00, 'USD', 5, '', '2026-09-27 05:31:29'),
(15, 16, 3, 25000.00, 'AFN', 10, '', '2026-09-27 05:32:30');

-- --------------------------------------------------------

--
-- Table structure for table `rental_contracts`
--

CREATE TABLE `rental_contracts` (
  `id` int(10) UNSIGNED NOT NULL,
  `rental_no` varchar(30) NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `supplier_contact` varchar(100) DEFAULT NULL,
  `supplier_phone` varchar(30) DEFAULT NULL,
  `equipment_name` varchar(150) NOT NULL,
  `equipment_details` text DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `rate_amount` decimal(14,2) NOT NULL,
  `rate_unit` enum('hour','day','week','month') NOT NULL,
  `currency` enum('AFN','USD') NOT NULL DEFAULT 'AFN',
  `deposit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `planned_end_date` date NOT NULL,
  `handover_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `rental_location` varchar(255) DEFAULT NULL,
  `operator_included` tinyint(1) NOT NULL DEFAULT 0,
  `operator_name` varchar(150) DEFAULT NULL,
  `fuel_responsibility` enum('company','supplier') NOT NULL DEFAULT 'company',
  `maintenance_responsibility` enum('company','supplier') NOT NULL DEFAULT 'supplier',
  `handover_condition` text DEFAULT NULL,
  `return_condition` text DEFAULT NULL,
  `meter_start` decimal(14,2) DEFAULT NULL,
  `meter_end` decimal(14,2) DEFAULT NULL,
  `status` enum('scheduled','active','returned') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `returned_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_contracts`
--

INSERT INTO `rental_contracts` (`id`, `rental_no`, `request_id`, `supplier_name`, `supplier_contact`, `supplier_phone`, `equipment_name`, `equipment_details`, `quantity`, `rate_amount`, `rate_unit`, `currency`, `deposit_amount`, `start_date`, `planned_end_date`, `handover_date`, `actual_return_date`, `rental_location`, `operator_included`, `operator_name`, `fuel_responsibility`, `maintenance_responsibility`, `handover_condition`, `return_condition`, `meter_start`, `meter_end`, `status`, `notes`, `created_by`, `returned_by`, `created_at`, `updated_at`) VALUES
(1, 'RNT-2026-0001', 20, 'حاجی باقی', NULL, NULL, 'بالابر', 'بمنتبمنتنبتمنیسشتب', 1.00, 500.00, 'hour', 'AFN', 50000.00, '2026-10-13', '2026-11-11', '2026-10-15', NULL, 'شهرک سبز', 0, NULL, 'company', 'supplier', 'سالم', NULL, 0.00, NULL, 'active', 'مبتمکنتبمن\r\nبیسبکمیستب\r\nبمنیتبمنسیشتبپبسبان\r\nبنتمسشینتب', 1, NULL, '2026-10-11 05:11:37', '2026-10-11 05:12:51');

-- --------------------------------------------------------

--
-- Table structure for table `rental_extensions`
--

CREATE TABLE `rental_extensions` (
  `id` int(10) UNSIGNED NOT NULL,
  `rental_id` int(10) UNSIGNED NOT NULL,
  `previous_end_date` date NOT NULL,
  `new_end_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_payments`
--

CREATE TABLE `rental_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `rental_id` int(10) UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` enum('AFN','USD') NOT NULL DEFAULT 'AFN',
  `method` enum('cash','bank_transfer','cheque','other') NOT NULL DEFAULT 'other',
  `reference_no` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `created_at`) VALUES
(1, 'ماشین‌آلات و ابزار حاجی عمران', 'آقای عمران', '0791023457', 'imran@kmachinery.pk', 'پارکهای صنعتی', 'ماشین‌آلات، وسایل ورکشاپ', '2026-09-24 08:19:01'),
(2, 'قرطاسیه فروشی ساجد', 'آقای ساجد', '0789056098', 'sajid@fatah.pk', 'چهار راهی صدارت دوکان نمبر 24', 'ملزومات اداری، لوازم التحریر', '2026-09-24 08:19:01'),
(3, 'فروشگاه وسایل ساختمانی بلال', 'آقای بلال', '0795467098', 'bilal@steelpipes.pk', 'کابل', 'فولاد، کانتینر، لارند، گادر و غیره...', '2026-09-24 08:19:01'),
(4, 'مبل و فرنیچر فروشی', 'حاجی قندگل', '0777890876', 'qandgul@furniturehub.pk', 'کوته سنگی', 'مبل و فرنیچر', '2026-09-24 08:19:01'),
(5, 'فروشگاه برقی', 'آقای کامران', '0789123456', 'kamran@elmart.pk', 'جاده نادر پشتون، کابل افغانستان', 'انواع کامپیوتر، انواع پرینتر و وسایل و تجهیزات کامپیوتر', '2026-09-24 08:19:01'),
(6, 'عمده فروشی مواد ساختمانی رشید', 'آقای رشید', '0761234567', 'rashid@bulkcs.pk', 'کابل', 'سیمنت، گچ، رنگهای پلاسیتکی و روغنی و تمام وسایل حمام و آشپزخانه', '2026-09-24 08:19:01'),
(9, 'کامپیوتر فروشی کابل آسیا', 'زلمی', '0784848458', 'zalmai03@gmail.com', 'مارکیت صدیق عمر، جاده نادر پشتون، کابل افغانستان', 'کمپیوتر های لبتاب و دیسکتاب و وسایل کمپیوتری', '2026-09-27 05:16:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash (password_hash)',
  `name` varchar(100) NOT NULL,
  `role` enum('employee','procurement_manager','warehouse_manager','gate_security','committee','general_manager','finance','admin') NOT NULL DEFAULT 'employee',
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `department_id`, `email`, `phone`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$N18OsByDxvB0joRCtKOJvO25.V.xwlEYrclzG9NTDyjJFUf61V6z6', 'مدیر سیستم', 'admin', 2, 'admin@khawar.pk', '0300-0000001', 1, '2026-09-24 08:19:01'),
(2, 'procurement', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'مدیر تدارکات', 'procurement_manager', 2, 'procurement@khawar.pk', '0300-0000002', 1, '2026-09-24 08:19:01'),
(3, 'warehouse', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'مدیر انبار', 'warehouse_manager', 5, 'warehouse@khawar.pk', '0300-0000003', 1, '2026-09-24 08:19:01'),
(4, 'gate', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'امنیت گیت', 'gate_security', 5, 'gate@khawar.pk', '0300-0000004', 1, '2026-09-24 08:19:01'),
(5, 'committee', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'عضو کمیته', 'committee', 3, 'committee@khawar.pk', '0300-0000005', 1, '2026-09-24 08:19:01'),
(6, 'gm', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'مدیر عمومی', 'general_manager', 1, 'gm@khawar.pk', '0300-0000006', 1, '2026-09-24 08:19:01'),
(7, 'employee', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'کارمند', 'employee', 1, 'karmand@khawar.pk', '0300-0000007', 1, '2026-09-24 08:19:01'),
(8, 'finance', '$2y$10$pGRKXtjZnTUeCOCnPWwEWeOAx7.P/PToiwCM7MlcpbvAGKFlQ9swu', 'مدیر مالی', 'finance', 3, 'finance@khawar.pk', '0300-0000008', 1, '2026-09-27 04:28:56');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_items`
--

CREATE TABLE `warehouse_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `location` varchar(60) DEFAULT NULL,
  `min_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouse_items`
--

INSERT INTO `warehouse_items` (`id`, `name`, `category_id`, `quantity`, `unit`, `location`, `min_stock`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'سیمان (کیسه ۵۰ کیلویی لکی)', 8, 250.00, 'bag', 'سوله A / قفسه ۱', 50.00, 'موجودی پس از صدور', '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(2, 'میلگرد ۱۲ میلی‌متری', 8, 12.50, 'ton', 'سوله B', 2.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(3, 'بسته کاغذ A4', 5, 180.00, 'ream', 'اتاق انبار ۱', 20.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(4, 'صندلی اداری (مدیریتی)', 4, 15.00, 'pcs', 'اتاق انبار ۲', 2.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(5, 'کلاه ایمنی', 10, 45.00, 'pcs', 'قفسه ایمنی', 10.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(6, 'ژنراتور دیزلی ۵۰۰۰ وات', 1, 2.00, 'pcs', 'محوطه ماشین‌آلات', 1.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(7, 'دریل کارگاه', 7, 6.00, 'pcs', 'قفس ابزار', 1.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(8, 'کانتینر فولادی ۲۰ فوت', 2, 3.00, 'pcs', 'حیاط بیرونی', 1.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(9, 'سطل پلاستیکی', 6, 40.00, 'pcs', 'قفسه آشپزخانه', 10.00, NULL, '2026-09-24 08:19:01', '2026-09-24 08:19:01'),
(10, 'پرینتر 6 رنگه همه کار', 9, 1.00, 'pcs', NULL, 1.00, 'شبیسشب', '2026-09-26 11:50:42', '2026-09-26 11:50:42'),
(11, 'UPS Unit', NULL, 2.00, 'pcs', 'Rack A', 1.00, 'خریداری شده PUR-2026-0001', '2026-09-27 04:31:39', '2026-09-27 04:31:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_cat_parent` (`parent_id`);

--
-- Indexes for table `committee_approvals`
--
ALTER TABLE `committee_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ca_purchase` (`purchase_id`);

--
-- Indexes for table `consumptions`
--
ALTER TABLE `consumptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cons_dept` (`department_id`),
  ADD KEY `idx_cons_date` (`delivery_date`),
  ADD KEY `fk_cons_item` (`item_id`),
  ADD KEY `fk_cons_request` (`request_id`),
  ADD KEY `fk_cons_cat` (`category_id`),
  ADD KEY `fk_cons_user` (`delivered_by`),
  ADD KEY `idx_cons_purchase` (`purchase_id`),
  ADD KEY `idx_cons_handover` (`handover_no`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_dept_parent` (`parent_id`);

--
-- Indexes for table `gate_checklists`
--
ALTER TABLE `gate_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gc_purchase` (`purchase_id`),
  ADD KEY `fk_gc_user` (`received_by`);

--
-- Indexes for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `idx_req_dept` (`department_id`),
  ADD KEY `idx_req_status` (`status`),
  ADD KEY `idx_req_date` (`request_date`),
  ADD KEY `fk_req_cat` (`category_id`),
  ADD KEY `fk_req_user` (`requested_by`),
  ADD KEY `fk_req_reviewer` (`reviewed_by`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_no` (`purchase_no`),
  ADD KEY `idx_pur_request` (`request_id`),
  ADD KEY `idx_pur_status` (`status`),
  ADD KEY `fk_pur_supplier` (`supplier_id`),
  ADD KEY `fk_pur_user` (`approved_by`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qu_request` (`request_id`),
  ADD KEY `fk_qu_supplier` (`supplier_id`);

--
-- Indexes for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rental_no` (`rental_no`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `idx_rental_status` (`status`),
  ADD KEY `idx_rental_dates` (`start_date`,`planned_end_date`),
  ADD KEY `fk_rental_created_by` (`created_by`),
  ADD KEY `fk_rental_returned_by` (`returned_by`);

--
-- Indexes for table `rental_extensions`
--
ALTER TABLE `rental_extensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rental_extension` (`rental_id`,`created_at`),
  ADD KEY `fk_rental_extension_user` (`changed_by`);

--
-- Indexes for table `rental_payments`
--
ALTER TABLE `rental_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rental_payment_date` (`rental_id`,`payment_date`),
  ADD KEY `fk_rental_payment_user` (`recorded_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_dept` (`department_id`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wi_category` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `committee_approvals`
--
ALTER TABLE `committee_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `consumptions`
--
ALTER TABLE `consumptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `gate_checklists`
--
ALTER TABLE `gate_checklists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rental_extensions`
--
ALTER TABLE `rental_extensions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_payments`
--
ALTER TABLE `rental_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `committee_approvals`
--
ALTER TABLE `committee_approvals`
  ADD CONSTRAINT `fk_ca_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `consumptions`
--
ALTER TABLE `consumptions`
  ADD CONSTRAINT `fk_cons_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cons_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_cons_item` FOREIGN KEY (`item_id`) REFERENCES `warehouse_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cons_request` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cons_user` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_parent` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gate_checklists`
--
ALTER TABLE `gate_checklists`
  ADD CONSTRAINT `fk_gc_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gc_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD CONSTRAINT `fk_req_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_req_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_req_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_req_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_pur_request` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`),
  ADD CONSTRAINT `fk_pur_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pur_user` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `fk_qu_request` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qu_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  ADD CONSTRAINT `fk_rental_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_rental_request` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`),
  ADD CONSTRAINT `fk_rental_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rental_extensions`
--
ALTER TABLE `rental_extensions`
  ADD CONSTRAINT `fk_rental_extension_contract` FOREIGN KEY (`rental_id`) REFERENCES `rental_contracts` (`id`),
  ADD CONSTRAINT `fk_rental_extension_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `rental_payments`
--
ALTER TABLE `rental_payments`
  ADD CONSTRAINT `fk_rental_payment_contract` FOREIGN KEY (`rental_id`) REFERENCES `rental_contracts` (`id`),
  ADD CONSTRAINT `fk_rental_payment_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD CONSTRAINT `fk_wi_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
