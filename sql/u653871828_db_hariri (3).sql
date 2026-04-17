-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 15 أبريل 2026 الساعة 06:52
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u653871828_db_hariri`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `photo_path` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `photo_path`) VALUES
(4, 'admin', '$2y$10$KGicTgh4JCuM2.HEos226OHaqaPrkjL.56sJKbflUJavXkJT2vMIi', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `content_groups`
--

CREATE TABLE `content_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `loop_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `content_groups`
--

INSERT INTO `content_groups` (`id`, `name`, `loop_enabled`, `created_at`) VALUES
(10, 'main', 1, '2026-04-08 05:31:10');

-- --------------------------------------------------------

--
-- بنية الجدول `dashboard_stat_snapshots`
--

CREATE TABLE `dashboard_stat_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `captured_at` datetime NOT NULL DEFAULT current_timestamp(),
  `available_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unavailable_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `availability_pct` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `dashboard_stat_snapshots`
--

INSERT INTO `dashboard_stat_snapshots` (`id`, `captured_at`, `available_count`, `unavailable_count`, `availability_pct`) VALUES
(1, '2026-03-31 16:59:29', 6, 34, 15.00),
(2, '2026-03-31 17:48:46', 0, 1, 0.00),
(3, '2026-03-31 17:49:02', 0, 1, 0.00),
(4, '2026-03-31 18:19:58', 1, 17, 5.60),
(5, '2026-03-31 18:56:20', 1, 17, 5.60),
(6, '2026-03-31 19:02:07', 0, 18, 0.00),
(7, '2026-03-31 19:02:11', 0, 18, 0.00),
(8, '2026-03-31 19:02:12', 0, 18, 0.00),
(9, '2026-03-31 19:02:38', 0, 18, 0.00),
(10, '2026-03-31 19:02:54', 0, 18, 0.00),
(11, '2026-03-31 19:03:27', 0, 18, 0.00),
(12, '2026-03-31 19:03:44', 0, 18, 0.00),
(13, '2026-03-31 19:03:54', 0, 18, 0.00),
(14, '2026-03-31 19:04:12', 0, 18, 0.00),
(15, '2026-04-01 10:21:24', 16, 2, 88.90),
(16, '2026-04-02 23:46:24', 0, 18, 0.00),
(17, '2026-04-03 00:02:00', 0, 18, 0.00),
(18, '2026-04-03 02:06:18', 15, 3, 83.30),
(19, '2026-04-03 05:32:04', 0, 0, 0.00),
(20, '2026-04-03 05:52:31', 0, 0, 0.00),
(21, '2026-04-03 06:05:01', 0, 0, 0.00),
(22, '2026-04-03 14:25:30', 0, 1, 0.00),
(23, '2026-04-03 14:26:05', 0, 1, 0.00),
(24, '2026-04-03 14:55:19', 0, 1, 0.00),
(25, '2026-04-03 19:53:58', 0, 0, 0.00),
(26, '2026-04-03 20:27:09', 0, 0, 0.00),
(27, '2026-04-03 20:39:57', 0, 0, 0.00),
(28, '2026-04-05 18:13:39', 0, 0, 0.00),
(29, '2026-04-06 19:31:48', 0, 0, 0.00),
(30, '2026-04-06 20:19:06', 0, 0, 0.00),
(31, '2026-04-07 01:58:17', 0, 0, 0.00),
(32, '2026-04-08 04:24:54', 0, 0, 0.00),
(33, '2026-04-08 06:05:19', 0, 0, 0.00),
(34, '2026-04-08 06:16:18', 0, 0, 0.00),
(35, '2026-04-08 06:16:27', 0, 0, 0.00),
(36, '2026-04-08 12:07:38', 0, 0, 0.00),
(37, '2026-04-08 12:23:08', 0, 0, 0.00),
(38, '2026-04-08 13:17:56', 0, 0, 0.00),
(39, '2026-04-08 13:49:38', 0, 0, 0.00),
(40, '2026-04-08 14:17:59', 0, 0, 0.00),
(41, '2026-04-08 14:39:11', 0, 0, 0.00),
(42, '2026-04-10 14:43:42', 1, 0, 100.00),
(43, '2026-04-10 15:12:14', 1, 0, 100.00),
(44, '2026-04-10 15:59:14', 1, 0, 100.00),
(45, '2026-04-11 05:25:12', 1, 0, 100.00),
(46, '2026-04-11 05:37:19', 1, 0, 100.00),
(47, '2026-04-11 05:42:58', 1, 0, 100.00),
(48, '2026-04-11 06:15:29', 1, 0, 100.00),
(49, '2026-04-11 16:07:23', 2, 0, 100.00),
(50, '2026-04-14 16:20:27', 2, 1, 66.70),
(51, '2026-04-14 21:16:26', 2, 1, 66.70),
(52, '2026-04-15 05:28:48', 1, 0, 100.00);

-- --------------------------------------------------------

--
-- بنية الجدول `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `icon` varchar(32) NOT NULL DEFAULT 'layers',
  `banner_image_path` varchar(512) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `departments`
--

INSERT INTO `departments` (`id`, `name`, `icon`, `banner_image_path`, `sort_order`, `created_at`) VALUES
(11, 'عام', 'layers', NULL, 0, '2026-03-31 16:59:26'),
(12, 'Pédiatrie', 'baby', NULL, 110, '2026-03-31 17:52:03'),
(13, 'Gastro-entérologie et endoscopie', 'stethoscope', NULL, 120, '2026-03-31 17:52:03'),
(14, 'Gynéco-Obstétricien', 'heart', NULL, 130, '2026-03-31 17:52:03'),
(15, 'Dentisterie', 'tooth', NULL, 140, '2026-03-31 17:52:03'),
(16, 'Urologie', 'activity', '0140c49e8486e9e5657607d5.png', 150, '2026-03-31 17:52:03'),
(17, 'Neurologue', 'layers', NULL, 160, '2026-03-31 17:52:03'),
(18, 'Chirurgien ORL', 'hospital', 'cc25102158ee92e0326a2ad6.png', 170, '2026-03-31 17:52:03'),
(19, 'Médecine générale', 'stethoscope', NULL, 180, '2026-03-31 17:52:03');

-- --------------------------------------------------------

--
-- بنية الجدول `display_contents`
--

CREATE TABLE `display_contents` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `content_type` enum('image','video','gif') NOT NULL DEFAULT 'image',
  `file_path` varchar(512) NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `doctor_id` int(10) UNSIGNED DEFAULT NULL,
  `duration_seconds` tinyint(3) UNSIGNED NOT NULL DEFAULT 8,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `display_contents`
--

INSERT INTO `display_contents` (`id`, `group_id`, `name`, `content_type`, `file_path`, `department_id`, `doctor_id`, `duration_seconds`, `sort_order`, `is_active`, `created_at`) VALUES
(54, 10, 'التصميم الاساسي (1)', 'video', 'b0be858ef64c36488a64ec9b.mp4', NULL, NULL, 8, 1, 1, '2026-04-08 05:32:01'),
(55, 10, 'التصميم الاساسي (5)', 'video', '68886f7c0706e425cca8229b.mp4', NULL, NULL, 8, 5, 1, '2026-04-08 05:33:20'),
(56, 10, 'التصميم الاساسي (4)', 'video', '8ef89c77d310022e02cb3972.mp4', NULL, NULL, 8, 4, 1, '2026-04-08 05:33:20'),
(57, 10, 'التصميم الاساسي (3)', 'video', 'ae91c3a549c57eb6ea2b597c.mp4', NULL, NULL, 8, 3, 0, '2026-04-08 05:33:20'),
(58, 10, 'التصميم الاساسي (2)', 'video', 'e8a8be6f422d71bb9668d2c2.mp4', NULL, NULL, 8, 2, 0, '2026-04-08 05:33:20'),
(59, 10, 'التصميم الاساسي (10)', 'video', 'b7a03d882954d71e44d65aff.mp4', NULL, NULL, 8, 10, 1, '2026-04-08 05:35:17'),
(60, 10, 'التصميم الاساسي (9)', 'video', 'ba615ff2297b5d6ee191a1d7.mp4', NULL, NULL, 8, 9, 1, '2026-04-08 05:35:17'),
(61, 10, 'التصميم الاساسي (8)', 'video', 'bdf3557a695c7430e4d10a84.mp4', NULL, NULL, 8, 8, 1, '2026-04-08 05:35:17'),
(62, 10, 'التصميم الاساسي (7)', 'video', 'aa95007f214b74bd5ebc76b8.mp4', NULL, NULL, 8, 7, 1, '2026-04-08 05:35:17'),
(63, 10, 'التصميم الاساسي (6)', 'video', 'ad1f4a459145315bc28d971f.mp4', NULL, NULL, 8, 6, 1, '2026-04-08 05:35:17'),
(64, 10, 'التصميم الاساسي (15)', 'video', '562572747cdc3ca39fa65fde.mp4', NULL, NULL, 8, 15, 1, '2026-04-08 05:37:30'),
(65, 10, 'التصميم الاساسي (14)', 'video', 'c15151a914f8a183dc64611c.mp4', NULL, NULL, 8, 14, 1, '2026-04-08 05:37:30'),
(66, 10, 'التصميم الاساسي (13)', 'video', '80a6fa7da3fdcbf081229024.mp4', NULL, NULL, 8, 13, 1, '2026-04-08 05:37:30'),
(67, 10, 'التصميم الاساسي (12)', 'video', '2376cd91e5d8c9e213044256.mp4', NULL, NULL, 8, 12, 1, '2026-04-08 05:37:30'),
(68, 10, 'التصميم الاساسي (11)', 'video', 'b8c8362104be18621644e6a8.mp4', NULL, NULL, 8, 11, 1, '2026-04-08 05:37:30'),
(69, 10, 'التصميم الاساسي (16)', 'video', 'f91e67721c6f8adc951b4c0d.mp4', NULL, NULL, 8, 16, 1, '2026-04-08 05:42:12'),
(70, 10, 'التصميم الاساسي (17)', 'video', '5e1dc2ce7279a120500b71e1.mp4', NULL, NULL, 8, 17, 1, '2026-04-08 05:42:29'),
(71, 10, 'التصميم الاساسي (18)', 'video', '9222c9ebe6f5257f4d0d2c2f.mp4', NULL, NULL, 8, 18, 1, '2026-04-08 05:42:50'),
(72, 10, 'التصميم الاساسي (19)', 'video', '4632d48d79b3afd65bcb9434.mp4', NULL, NULL, 8, 19, 1, '2026-04-08 05:43:10'),
(73, 10, 'التصميم الاساسي (20)', 'video', '665f101d26495ed302d83384.mp4', NULL, NULL, 8, 20, 1, '2026-04-08 05:43:24'),
(74, 10, 'التصميم الاساسي (21)', 'video', '8d3b0f3c998c2a1b1046f19e.mp4', NULL, NULL, 8, 21, 1, '2026-04-08 05:43:39'),
(75, 10, 'التصميم الاساسي (22)', 'video', '8cf485907aa476f8bdd9e558.mp4', NULL, NULL, 8, 22, 1, '2026-04-08 05:44:13'),
(76, 10, 'التصميم الاساسي (23)', 'video', '334298e97044ce79af4aaea6.mp4', NULL, NULL, 8, 23, 1, '2026-04-08 05:44:36');

-- --------------------------------------------------------

--
-- بنية الجدول `display_styles`
--

CREATE TABLE `display_styles` (
  `id` int(10) UNSIGNED NOT NULL,
  `style_key` varchar(64) NOT NULL,
  `name` varchar(191) NOT NULL,
  `style_type` varchar(32) NOT NULL DEFAULT 'custom',
  `config_json` longtext NOT NULL,
  `css_text` longtext DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `display_styles`
--

INSERT INTO `display_styles` (`id`, `style_key`, `name`, `style_type`, `config_json`, `css_text`, `metadata_json`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'immersive_signature', 'Immersive Medical Signature', 'hero', '{\"layout\":\"hero\",\"image_behavior\":{\"doctor\":\"doctor_hero\",\"department\":\"department_background\"},\"overlay\":{\"gradient\":true,\"blur\":30,\"opacity\":0.48},\"typography\":{\"name_size\":\"clamp(1.9rem, 6.2vw, 4rem)\",\"spec_size\":\"clamp(1rem, 2.7vw, 1.45rem)\",\"time_size\":\"clamp(1.05rem, 3.1vw, 1.55rem)\",\"spacing\":1.05},\"colors\":{\"primary\":\"#2fae66\",\"secondary\":\"#2c7fb8\",\"surface\":\"#0a1628\",\"text\":\"#ffffff\"},\"animations\":{\"type\":\"fade\",\"duration_ms\":950}}', '.cinematic-stack__ambient-img { filter: blur(34px) saturate(1.12); opacity: .44; } .cinematic-stack__photo { object-position: center 16%; filter: saturate(1.05) contrast(1.03); } .cinematic-name { letter-spacing: .01em; text-shadow: 0 3px 24px rgba(0, 0, 0, .62); } .cinematic-row--time { background: rgba(8, 16, 28, .52); border: 1px solid rgba(255, 255, 255, .28); } .cinematic-stack--ok .cinematic-status-line { background: linear-gradient(90deg, #2fae66, rgba(47, 174, 102, .55)); } .cinematic-stack--no .cinematic-status-line { background: linear-gradient(90deg, #334155, rgba(71, 85, 105, .72)); }', '{\"version\":1,\"source\":\"system\",\"preset\":\"immersive_signature\"}', 1, '2026-03-31 16:57:20', '2026-03-31 16:57:20'),
(2, 'aurora_split_signature', 'Aurora Split Signature', 'split', '{\"layout\":\"split\",\"image_behavior\":{\"doctor\":\"doctor_hero\",\"department\":\"department_background\"},\"overlay\":{\"gradient\":true,\"blur\":20,\"opacity\":0.5},\"typography\":{\"name_size\":\"clamp(1.8rem, 5.5vw, 3.8rem)\",\"spec_size\":\"clamp(1rem, 2.5vw, 1.35rem)\",\"time_size\":\"clamp(1.05rem, 2.9vw, 1.45rem)\",\"spacing\":1.08},\"colors\":{\"primary\":\"#2fae66\",\"secondary\":\"#2c7fb8\",\"surface\":\"#081425\",\"text\":\"#eaf3ff\"},\"animations\":{\"type\":\"fade\",\"duration_ms\":1000}}', '/* Aurora Split Signature */ .card-display{background:radial-gradient(100% 80% at 0% 0%,rgba(47,174,102,.18),transparent 62%),radial-gradient(90% 80% at 100% 100%,rgba(44,127,184,.23),transparent 60%),#050b15}.card-display__card{width:min(98vw,1500px);min-height:min(92vh,980px);border-radius:30px;background:linear-gradient(120deg,rgba(8,20,38,.92),rgba(11,28,50,.92));border:1px solid rgba(170,210,255,.18);box-shadow:0 32px 90px rgba(2,6,23,.58),inset 0 0 0 1px rgba(255,255,255,.04)}', '{\"version\":1,\"source\":\"system\",\"preset\":\"aurora_split_signature\"}', 1, '2026-03-31 16:57:20', '2026-03-31 16:57:20'),
(3, 'soft_card_medical', 'Soft Card Medical (Neumorphism)', 'card', '{\"layout\":\"card\",\"image_behavior\":{\"doctor\":\"doctor_hero\",\"department\":\"department_background\"},\"overlay\":{\"gradient\":true,\"blur\":12,\"opacity\":0.26},\"typography\":{\"name_size\":\"clamp(1.48rem, 4.5vw, 2.6rem)\",\"spec_size\":\"clamp(0.98rem, 2.3vw, 1.22rem)\",\"time_size\":\"clamp(1.02rem, 2.6vw, 1.3rem)\",\"spacing\":1.02},\"colors\":{\"primary\":\"#2fae66\",\"secondary\":\"#2c7fb8\",\"surface\":\"#e7edf5\",\"text\":\"#1f2937\"},\"animations\":{\"type\":\"hero_intro\",\"duration_ms\":1300}}', '/* Soft Card Medical v3 */ .card-display__hero-img,.card-display__hero-empty{width:min(100%,460px);height:clamp(38vh,46vh,54vh);animation:soft-hero-intro 1.35s cubic-bezier(.2,.75,.18,1) forwards} @media (max-width:900px),(orientation:portrait){.card-display__hero-img,.card-display__hero-empty{width:100%;max-width:none;height:70vh;animation:none!important;transform:none!important}} .card-display__badge svg{width:2.5em;height:2.5em}', '{\"version\":3,\"source\":\"system\",\"preset\":\"soft_card_medical\"}', 1, '2026-03-31 16:57:20', '2026-03-31 16:57:20'),
(4, 'minimal_clear', 'بسيط وواضح (صورة كاملة + متاح/غير متاح)', 'minimal', '{\"layout\":\"minimal\",\"image_behavior\":{\"doctor\":\"contain\",\"department\":\"none\"},\"overlay\":{\"gradient\":false,\"blur\":0,\"opacity\":0},\"typography\":{\"name_size\":\"clamp(1.8rem, 5.8vw, 3.1rem)\",\"spec_size\":\"clamp(1.08rem, 3.1vw, 1.55rem)\",\"time_size\":\"clamp(1rem, 2.8vw, 1.2rem)\",\"spacing\":1.08},\"colors\":{\"primary\":\"#22c55e\",\"secondary\":\"#f87171\",\"surface\":\"#0f172a\",\"text\":\"#f8fafc\"},\"animations\":{\"type\":\"fade\",\"duration_ms\":850}}', '', '{\"version\":1,\"source\":\"system\",\"preset\":\"minimal_clear\"}', 1, '2026-04-03 05:56:32', '2026-04-03 05:56:32'),
(6, 'hariri_template', 'قالب الحريري — البطاقة الثابتة', 'hariri', '{\"layout\":\"hariri\",\"source\":\"theme_folder\"}', '', '{\"version\":1,\"source\":\"system\",\"preset\":\"hariri_template\"}', 1, '2026-04-11 05:42:58', '2026-04-11 05:42:58');

-- --------------------------------------------------------

--
-- بنية الجدول `doctors`
--

CREATE TABLE `doctors` (
  `id` int(10) UNSIGNED NOT NULL,
  `screen_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `specialty` varchar(191) NOT NULL DEFAULT '',
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `image_path` varchar(512) DEFAULT NULL,
  `work_start` time NOT NULL,
  `work_end` time NOT NULL,
  `status_mode` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `manual_status` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `doctors`
--

INSERT INTO `doctors` (`id`, `screen_id`, `name`, `specialty`, `department_id`, `image_path`, `work_start`, `work_end`, `status_mode`, `manual_status`, `sort_order`, `created_at`) VALUES
(61, 19, 'dr adam hassan', 'Urologie', 16, '6424dba9af2d2b3ce4869914.png', '02:00:00', '14:00:00', 'manual', 'available', 2, '2026-04-11 06:37:42');

-- --------------------------------------------------------

--
-- بنية الجدول `doctor_weekly_schedule`
--

CREATE TABLE `doctor_weekly_schedule` (
  `id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `weekday` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Mon..7=Sun ISO-8601',
  `work_start` time NOT NULL,
  `work_end` time NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `doctor_weekly_schedule`
--

INSERT INTO `doctor_weekly_schedule` (`id`, `doctor_id`, `weekday`, `work_start`, `work_end`, `sort_order`) VALUES
(749, 61, 1, '02:00:00', '14:00:00', 0),
(750, 61, 2, '08:00:00', '14:00:00', 0),
(751, 61, 3, '02:00:00', '14:00:00', 0),
(752, 61, 4, '02:00:00', '14:00:00', 0),
(753, 61, 5, '02:00:00', '14:00:00', 0),
(754, 61, 6, '08:00:00', '14:00:00', 0),
(755, 61, 7, '02:00:00', '14:00:00', 0);

-- --------------------------------------------------------

--
-- بنية الجدول `hospital_settings`
--

CREATE TABLE `hospital_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL DEFAULT '',
  `logo_path` varchar(512) DEFAULT NULL,
  `phone` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(191) NOT NULL DEFAULT '',
  `address` text DEFAULT NULL,
  `website` varchar(255) NOT NULL DEFAULT '',
  `social_facebook` varchar(255) NOT NULL DEFAULT '',
  `social_instagram` varchar(255) NOT NULL DEFAULT '',
  `social_x` varchar(255) NOT NULL DEFAULT '',
  `social_youtube` varchar(255) NOT NULL DEFAULT '',
  `default_display_shell` varchar(16) NOT NULL DEFAULT 'classic' COMMENT 'classic|hariri',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hospital_settings`
--

INSERT INTO `hospital_settings` (`id`, `name`, `logo_path`, `phone`, `email`, `address`, `website`, `social_facebook`, `social_instagram`, `social_x`, `social_youtube`, `default_display_shell`, `updated_at`) VALUES
(1, 'مستشفى الحريري العالمي', 'logo_32e4fc34f5ffc152.webp', '+235 68 77 52 47', 'cliniquehariri@gmail.com', 'rue de 40 ndjamena', 'https://cliniquehariri.com/', '', '', '', '', 'classic', '2026-04-11 05:44:51');

-- --------------------------------------------------------

--
-- بنية الجدول `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` int(10) UNSIGNED NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
(2, '2026-03-31 16:57:20'),
(3, '2026-03-31 16:57:20'),
(4, '2026-03-31 16:57:20'),
(5, '2026-03-31 16:57:20'),
(6, '2026-03-31 16:57:20'),
(7, '2026-03-31 16:57:20'),
(8, '2026-03-31 16:57:20'),
(9, '2026-03-31 16:57:20'),
(10, '2026-03-31 16:57:20'),
(11, '2026-03-31 16:57:20'),
(12, '2026-03-31 16:57:20'),
(13, '2026-03-31 16:57:20'),
(14, '2026-03-31 16:57:34'),
(15, '2026-04-03 05:30:45'),
(16, '2026-04-03 05:56:32'),
(17, '2026-04-10 15:12:03'),
(18, '2026-04-11 05:37:19'),
(19, '2026-04-11 05:42:58');

-- --------------------------------------------------------

--
-- بنية الجدول `screens`
--

CREATE TABLE `screens` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` char(32) NOT NULL,
  `slide_seconds` tinyint(3) UNSIGNED NOT NULL DEFAULT 8,
  `refresh_seconds` smallint(5) UNSIGNED NOT NULL DEFAULT 20,
  `display_style` varchar(32) NOT NULL DEFAULT 'hero_medical',
  `display_mode` enum('doctors','content') NOT NULL DEFAULT 'doctors',
  `content_group_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `screens`
--

INSERT INTO `screens` (`id`, `name`, `token`, `slide_seconds`, `refresh_seconds`, `display_style`, `display_mode`, `content_group_id`, `created_at`) VALUES
(16, 'الحريري', '4e67a016bacb86d7a073d8c9e2b15ccc', 8, 20, 'aurora_split_signature', 'content', 10, '2026-04-08 12:26:14'),
(17, 'الحوادث', '42f530703f3a7ba5c8aa45ac37344b1a', 8, 20, 'aurora_split_signature', 'content', 10, '2026-04-08 13:52:28'),
(18, 'الحوادث سستم', '4681c90106addd05c6bcf2bef85b6433', 8, 20, 'hero_medical', 'doctors', NULL, '2026-04-08 14:58:37'),
(19, 'جديد', 'a16dabae7020378fec25549867516bd2', 15, 20, 'aurora_split_signature', 'doctors', NULL, '2026-04-11 06:48:25');

-- --------------------------------------------------------

--
-- بنية الجدول `welcome_broadcast`
--

CREATE TABLE `welcome_broadcast` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `show_logo` tinyint(1) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL DEFAULT '',
  `subtitle` varchar(512) NOT NULL DEFAULT '',
  `image_path` varchar(512) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `welcome_broadcast`
--

INSERT INTO `welcome_broadcast` (`id`, `is_enabled`, `show_logo`, `title`, `subtitle`, `image_path`, `updated_at`) VALUES
(1, 0, 1, 'مرحبا بالسيد فخامة الرئيس محمد ادريس دبي', 'فخورين بزيارتكم لنا', 'welcome_9f67b125598e3686dba0.png', '2026-04-08 14:18:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- Indexes for table `content_groups`
--
ALTER TABLE `content_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_content_group_name` (`name`);

--
-- Indexes for table `dashboard_stat_snapshots`
--
ALTER TABLE `dashboard_stat_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_snapshot_captured_at` (`captured_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dep_name` (`name`);

--
-- Indexes for table `display_contents`
--
ALTER TABLE `display_contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_group` (`group_id`),
  ADD KEY `idx_content_department` (`department_id`),
  ADD KEY `idx_content_doctor` (`doctor_id`);

--
-- Indexes for table `display_styles`
--
ALTER TABLE `display_styles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_display_style_key` (`style_key`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_screen` (`screen_id`),
  ADD KEY `idx_department` (`department_id`);

--
-- Indexes for table `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dws_doctor_weekday` (`doctor_id`,`weekday`);

--
-- Indexes for table `hospital_settings`
--
ALTER TABLE `hospital_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `screens`
--
ALTER TABLE `screens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`),
  ADD KEY `idx_screen_content_group` (`content_group_id`);

--
-- Indexes for table `welcome_broadcast`
--
ALTER TABLE `welcome_broadcast`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `content_groups`
--
ALTER TABLE `content_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dashboard_stat_snapshots`
--
ALTER TABLE `dashboard_stat_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `display_contents`
--
ALTER TABLE `display_contents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `display_styles`
--
ALTER TABLE `display_styles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=783;

--
-- AUTO_INCREMENT for table `screens`
--
ALTER TABLE `screens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `display_contents`
--
ALTER TABLE `display_contents`
  ADD CONSTRAINT `fk_content_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_content_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_content_group` FOREIGN KEY (`group_id`) REFERENCES `content_groups` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `fk_doctors_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doctors_screen` FOREIGN KEY (`screen_id`) REFERENCES `screens` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  ADD CONSTRAINT `fk_dws_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `screens`
--
ALTER TABLE `screens`
  ADD CONSTRAINT `fk_screens_content_group` FOREIGN KEY (`content_group_id`) REFERENCES `content_groups` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
