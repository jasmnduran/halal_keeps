-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 01:42 PM
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
-- Database: `halal_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `module`, `created_at`) VALUES
(1, 1, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-04-28 06:42:26'),
(2, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 06:42:44'),
(3, 1, 'Role Applied', 'User applied as Business Owner', '::1', 'auth', '2026-04-28 06:43:40'),
(4, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 06:47:53'),
(5, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 06:51:31'),
(6, 1, 'Role Applied', 'User applied as Business Owner (auto-approved)', '::1', 'auth', '2026-04-28 06:53:25'),
(7, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 07:15:54'),
(8, 2, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-04-28 07:17:11'),
(9, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 07:17:15'),
(10, 2, 'Role Applied', 'User applied as Evaluator (auto-approved)', '::1', 'auth', '2026-04-28 07:17:53'),
(11, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 09:12:02'),
(12, 2, 'LOI Verified', 'LOI #1 verified', '::1', 'evaluation', '2026-04-28 09:17:55'),
(13, 1, 'LOI Created', 'Created LOI for halal keeps', '::1', 'loi', '2026-04-28 09:40:09'),
(14, 2, 'LOI Verified', 'LOI #1 verified', '::1', 'evaluation', '2026-04-28 09:40:15'),
(15, 2, 'LOI Verified', 'LOI #2 returned', '::1', 'evaluation', '2026-04-28 09:41:05'),
(16, 2, 'LOI Verified', 'LOI #2 verified', '::1', 'evaluation', '2026-04-28 09:45:58'),
(17, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 09:50:20'),
(18, 2, 'LOI Verified', 'LOI #3 verified', '::1', 'evaluation', '2026-04-28 09:52:27'),
(19, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 10:03:00'),
(20, 2, 'LOI Verified', 'LOI #4 verified', '::1', 'evaluation', '2026-04-28 10:04:03'),
(21, 2, 'LOI Verified', 'LOI #4 verified', '::1', 'evaluation', '2026-04-28 10:06:04'),
(22, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 10:16:00'),
(23, 2, 'LOI Verified', 'LOI #5 verified', '::1', 'evaluation', '2026-04-28 10:19:55'),
(24, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 10:20:00'),
(25, 2, 'LOI Verified', 'LOI #6 verified', '::1', 'evaluation', '2026-04-28 10:26:14'),
(26, 2, 'LOI Verified', 'LOI #6 verified', '::1', 'evaluation', '2026-04-28 10:37:14'),
(27, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 10:43:24'),
(28, 2, 'Application Verified', 'App #1 verified', '::1', 'evaluation', '2026-04-28 10:47:19'),
(29, 2, 'Application Verified', 'App #1 verified', '::1', 'evaluation', '2026-04-28 10:48:42'),
(30, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 11:26:47'),
(31, 2, 'LOI Verified', 'LOI #7 returned', '::1', 'evaluation', '2026-04-28 11:28:05'),
(32, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-04-28 11:28:09'),
(33, 2, 'LOI Verified', 'LOI #8 verified', '::1', 'evaluation', '2026-04-28 11:29:12'),
(34, 2, 'LOI Verified', 'LOI #8 verified', '::1', 'evaluation', '2026-04-28 11:31:25'),
(35, 2, 'Application Verified', 'App #2 verified', '::1', 'evaluation', '2026-04-28 11:33:01'),
(36, 2, 'Application Verified', 'App #2 verified', '::1', 'evaluation', '2026-04-28 11:33:27'),
(37, 3, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-04-28 11:35:12'),
(38, 3, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-04-28 11:35:27'),
(39, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 02:48:19'),
(40, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 02:49:42'),
(41, 1, 'LOI Created', 'Created LOI for halal keeps', '::1', 'loi', '2026-05-08 03:02:08'),
(42, 2, 'LOI Verified', 'LOI #9 verified', '::1', 'evaluation', '2026-05-08 03:02:21'),
(43, 2, 'Application Verified', 'App #3 verified', '::1', 'evaluation', '2026-05-08 03:19:33'),
(44, 5, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-08 03:22:21'),
(45, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:22:24'),
(46, 5, 'Role Applied', 'User applied as Auditor - Technical (auto-approved)', '::1', 'auth', '2026-05-08 03:26:10'),
(47, 6, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-08 03:27:07'),
(48, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:27:10'),
(49, 6, 'Role Applied', 'User applied as Auditor - Shariah (auto-approved)', '::1', 'auth', '2026-05-08 03:27:16'),
(50, 2, 'TOR Sent', 'TOR sent for application #3', '::1', 'evaluation', '2026-05-08 03:27:55'),
(51, 8, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:45:11'),
(52, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:48:37'),
(53, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:50:28'),
(54, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:51:14'),
(55, 2, 'TOR Sent', 'TOR sent for application #3', '::1', 'evaluation', '2026-05-08 03:52:15'),
(56, 2, 'Inspection Scheduled', 'Inspection scheduled for App #3 on 2026-05-12', '::1', 'evaluation', '2026-05-08 03:56:57'),
(57, 2, 'Inspection Scheduled', 'Inspection scheduled for App #3 on 2026-05-12', '::1', 'evaluation', '2026-05-08 03:57:41'),
(58, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:57:51'),
(59, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 03:58:01'),
(60, 6, 'Shariah Auditor Findings Submitted', 'Inspection #1', '::1', 'audit', '2026-05-08 03:59:44'),
(61, 5, 'Technical Auditor Findings Submitted', 'Inspection #1', '::1', 'audit', '2026-05-08 04:00:17'),
(62, 5, 'Technical Auditor Findings Submitted', 'Inspection #1', '::1', 'audit', '2026-05-08 04:02:23'),
(63, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 04:39:44'),
(64, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 04:48:32'),
(65, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 04:50:48'),
(66, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 05:13:57'),
(67, NULL, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-08 05:22:08'),
(68, NULL, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 05:22:15'),
(69, NULL, 'Role Applied', 'User applied as Business Owner (auto-approved)', '::1', 'auth', '2026-05-08 05:22:35'),
(70, NULL, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-08 05:26:00'),
(71, NULL, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 05:27:51'),
(72, NULL, 'Role Applied', 'User applied as Business Owner (auto-approved)', '::1', 'auth', '2026-05-08 05:28:10'),
(73, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 06:00:32'),
(74, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 06:00:49'),
(75, 1, 'LOI Created', 'Created LOI for Halal Keeps', '::1', 'loi', '2026-05-08 06:01:18'),
(76, 8, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 06:01:49'),
(77, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 06:02:43'),
(78, 2, 'LOI Verified', 'LOI #10 verified', '::1', 'evaluation', '2026-05-08 06:02:49'),
(79, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 06:06:46'),
(80, 2, 'Application Verified', 'App #4 verified', '::1', 'evaluation', '2026-05-08 06:07:45'),
(81, 2, 'TOR Sent', 'TOR sent for application #4', '::1', 'evaluation', '2026-05-08 06:08:36'),
(82, 2, 'Inspection Scheduled', 'Inspection scheduled for App #4 on 2026-05-13', '::1', 'evaluation', '2026-05-08 06:09:13'),
(83, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 07:43:21'),
(84, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 07:45:33'),
(85, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 07:45:39'),
(86, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 07:58:46'),
(87, 2, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 07:59:24'),
(88, 5, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-08 08:00:15'),
(89, 5, 'Technical Auditor Findings Submitted', 'Inspection #2', '::1', 'audit', '2026-05-08 08:19:16'),
(90, 6, 'Shariah Auditor Findings Submitted', 'Inspection #2', '::1', 'audit', '2026-05-08 08:25:20'),
(91, 6, 'NCR Created', 'NCR NCR-2026-20667 for App #4', '::1', 'audit', '2026-05-08 08:31:41'),
(92, 5, 'Technical Auditor Findings Submitted', 'Inspection #2', '::1', 'audit', '2026-05-08 08:40:04'),
(93, 5, 'NCR Created', 'NCR NCR-2026-74516 for App #4', '::1', 'audit', '2026-05-08 08:44:50'),
(94, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 04:55:18'),
(95, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 05:14:20'),
(96, 6, 'NCR Created', 'NCR NCR-2026-65883 for App #4', '::1', 'audit', '2026-05-13 05:26:11'),
(97, 6, 'NCR Created', 'NCR NCR-2026-97427 for App #4', '::1', 'audit', '2026-05-13 05:28:28'),
(98, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 05:29:05'),
(99, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 06:08:31'),
(100, 11, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-13 06:48:57'),
(101, 11, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 06:49:10'),
(102, 11, 'Role Applied', 'User applied as Receiving Officer (auto-approved)', '::1', 'auth', '2026-05-13 06:49:21'),
(103, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 07:04:53'),
(104, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 10:25:21'),
(105, 11, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 10:26:25'),
(106, 6, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 10:47:14'),
(107, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 10:51:46'),
(108, 11, 'Lab Request Approved', 'Approved lab request #2 - awaiting payment', '::1', 'laboratory', '2026-05-13 10:53:52'),
(109, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 10:54:00'),
(110, 12, 'Registration', 'User registered via email/password', '::1', 'auth', '2026-05-13 10:59:14'),
(111, 12, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 10:59:46'),
(112, 12, 'Role Applied', 'User applied as Laboratory Analyst (auto-approved)', '::1', 'auth', '2026-05-13 11:00:00'),
(113, 11, 'Lab Request Approved', 'Approved lab request #1 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:02:14'),
(114, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 11:10:08'),
(115, 11, 'Lab Request Approved', 'Approved lab request #4 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:10:28'),
(116, 1, 'Lab Payment Initiated', 'Payment session created for lab request #4', '::1', 'payment', '2026-05-13 11:13:41'),
(117, 11, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 11:15:46'),
(118, 1, 'Login', 'User logged in via email/password', '::1', 'auth', '2026-05-13 11:16:10'),
(119, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 11:19:05'),
(120, 11, 'Lab Request Approved', 'Approved lab request #5 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:19:27'),
(121, 1, 'Lab Payment Initiated', 'Payment session created for lab request #5', '::1', 'payment', '2026-05-13 11:19:57'),
(122, 1, 'Lab Request Submitted', 'Laboratory test request for App #4', '::1', 'laboratory', '2026-05-13 11:20:59'),
(123, 11, 'Lab Request Approved', 'Approved lab request #5 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:21:05'),
(124, 11, 'Lab Request Approved', 'Approved lab request #6 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:21:18'),
(125, 1, 'Lab Payment Initiated', 'Payment session created for lab request #6', '::1', 'payment', '2026-05-13 11:21:32'),
(126, 11, 'Lab Request Approved', 'Approved lab request #6 - awaiting payment', '::1', 'laboratory', '2026-05-13 11:22:05'),
(127, 1, 'Lab Payment Completed', 'Payment verified for lab request #6', '::1', 'payment', '2026-05-13 11:24:58'),
(128, 12, 'Lab Report Created', 'Report R11-2026-59700', '::1', 'laboratory', '2026-05-13 11:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_requirement_reviews`
--

CREATE TABLE `application_requirement_reviews` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `requirement_label` varchar(255) NOT NULL,
  `review_status` enum('accepted','rejected') NOT NULL,
  `remarks` text DEFAULT NULL,
  `reviewed_by` int(11) NOT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_requirement_reviews`
--

INSERT INTO `application_requirement_reviews` (`id`, `application_id`, `requirement_label`, `review_status`, `remarks`, `reviewed_by`, `reviewed_at`) VALUES
(1, 3, '1.1 Letter of Intent', 'accepted', '', 2, '2026-05-08 03:18:12'),
(2, 3, '1.2 Company Profile', 'accepted', '', 2, '2026-05-08 03:18:13'),
(3, 3, '1.3 SEC Registration / DTI License', 'accepted', '', 2, '2026-05-08 03:18:16'),
(4, 3, '1.4 Mayor\'s Permit', 'accepted', '', 2, '2026-05-08 03:18:17'),
(5, 3, '1.5 Business Permit', 'accepted', '', 2, '2026-05-08 03:19:00'),
(6, 3, '1.6 Barangay Permit', 'accepted', '', 2, '2026-05-08 03:19:00'),
(7, 3, '1.7 Sanitary Permit', 'accepted', '', 2, '2026-05-08 03:19:01'),
(8, 3, '1.8 Fire Clearance Certificate', 'accepted', '', 2, '2026-05-08 03:19:02'),
(9, 3, '1.9 DENR Environment Certificate', 'accepted', '', 2, '2026-05-08 03:19:03'),
(10, 3, '1.10 FDA License to Operate (LTO)', 'accepted', '', 2, '2026-05-08 03:19:04'),
(11, 3, '1.11 Certificate of Product Registration (CPR)', 'accepted', '', 2, '2026-05-08 03:19:12'),
(12, 3, '2.2 Halal Assurance System (HAS) Manual', 'accepted', '', 2, '2026-05-08 03:19:13'),
(13, 3, '2.3 Waste Disposal Management Plan', 'accepted', '', 2, '2026-05-08 03:19:14'),
(14, 3, '2.4 Pest Control Program', 'accepted', '', 2, '2026-05-08 03:19:14'),
(15, 3, '2.5 Kitchen Layout', 'accepted', '', 2, '2026-05-08 03:19:16'),
(16, 3, '2.6 Flow Chart of Product Processing', 'accepted', '', 2, '2026-05-08 03:19:17'),
(17, 3, '3.1 Full List of Products / Menu with Corresponding Ingredients', 'accepted', '', 2, '2026-05-08 03:19:17'),
(18, 3, '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)', 'accepted', '', 2, '2026-05-08 03:19:19'),
(19, 3, '3.3 Packaging Materials List with Corresponding Halal Certificates', 'accepted', '', 2, '2026-05-08 03:19:19'),
(20, 3, '4.1 Halal Certificates for All Raw Materials (especially meat products)', 'accepted', '', 2, '2026-05-08 03:19:20'),
(21, 3, '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members', 'accepted', '', 2, '2026-05-08 03:19:21'),
(22, 3, '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen', 'accepted', '', 2, '2026-05-08 03:19:22'),
(23, 3, '4.5 Warehouse Halal Certificate and Storage System Description', 'accepted', '', 2, '2026-05-08 03:19:23'),
(24, 3, '4.6 Transportation Details for Halal Products', 'accepted', '', 2, '2026-05-08 03:19:24'),
(25, 4, '1.1 Letter of Intent', 'accepted', '', 2, '2026-05-08 06:07:20'),
(26, 4, '1.2 Company Profile', 'accepted', '', 2, '2026-05-08 06:07:21'),
(27, 4, '1.3 SEC Registration / DTI License', 'accepted', '', 2, '2026-05-08 06:07:22'),
(28, 4, '1.4 Mayor\'s Permit', 'accepted', '', 2, '2026-05-08 06:07:23'),
(29, 4, '1.5 Business Permit', 'accepted', '', 2, '2026-05-08 06:07:25'),
(30, 4, '1.6 Barangay Permit', 'accepted', '', 2, '2026-05-08 06:07:25'),
(31, 4, '1.7 Sanitary Permit', 'accepted', '', 2, '2026-05-08 06:07:26'),
(32, 4, '1.8 Fire Clearance Certificate', 'accepted', '', 2, '2026-05-08 06:07:26'),
(33, 4, '1.9 DENR Environment Certificate', 'accepted', '', 2, '2026-05-08 06:07:27'),
(34, 4, '1.10 FDA License to Operate (LTO)', 'accepted', '', 2, '2026-05-08 06:07:29'),
(35, 4, '1.11 Certificate of Product Registration (CPR)', 'accepted', '', 2, '2026-05-08 06:07:29'),
(36, 4, '2.2 Halal Assurance System (HAS) Manual', 'accepted', '', 2, '2026-05-08 06:07:30'),
(37, 4, '2.3 Waste Disposal Management Plan', 'accepted', '', 2, '2026-05-08 06:07:32'),
(38, 4, '2.4 Pest Control Program', 'accepted', '', 2, '2026-05-08 06:07:33'),
(39, 4, '2.5 Kitchen Layout', 'accepted', '', 2, '2026-05-08 06:07:34'),
(40, 4, '2.6 Flow Chart of Product Processing', 'accepted', '', 2, '2026-05-08 06:07:35'),
(41, 4, '3.1 Full List of Products / Menu with Corresponding Ingredients', 'accepted', '', 2, '2026-05-08 06:07:35'),
(42, 4, '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)', 'accepted', '', 2, '2026-05-08 06:07:36'),
(43, 4, '3.3 Packaging Materials List with Corresponding Halal Certificates', 'accepted', '', 2, '2026-05-08 06:07:37'),
(44, 4, '4.1 Halal Certificates for All Raw Materials (especially meat products)', 'accepted', '', 2, '2026-05-08 06:07:37'),
(45, 4, '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members', 'accepted', '', 2, '2026-05-08 06:07:38'),
(46, 4, '4.5 Warehouse Halal Certificate and Storage System Description', 'accepted', '', 2, '2026-05-08 06:07:40'),
(47, 4, '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen', 'accepted', '', 2, '2026-05-08 06:07:40'),
(48, 4, '4.6 Transportation Details for Halal Products', 'accepted', '', 2, '2026-05-08 06:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `application_requirement_uploads`
--

CREATE TABLE `application_requirement_uploads` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `requirement_label` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_requirement_uploads`
--

INSERT INTO `application_requirement_uploads` (`id`, `application_id`, `requirement_label`, `file_path`, `uploaded_at`) VALUES
(49, 4, '1.1 Letter of Intent', 'documents/69fd7ca5b9881_1778220197.pdf', '2026-05-08 06:03:17'),
(50, 4, '1.2 Company Profile', 'documents/69fd7caf8dbad_1778220207.jpg', '2026-05-08 06:03:27'),
(51, 4, '1.3 SEC Registration / DTI License', 'documents/69fd7cba92281_1778220218.png', '2026-05-08 06:03:38'),
(52, 4, '1.4 Mayor\'s Permit', 'documents/69fd7cc09b96e_1778220224.png', '2026-05-08 06:03:44'),
(54, 4, '1.6 Barangay Permit', 'documents/69fd7cd896136_1778220248.png', '2026-05-08 06:04:08'),
(56, 4, '1.5 Business Permit', 'documents/69fd7ceb3329b_1778220267.png', '2026-05-08 06:04:27'),
(57, 4, '1.7 Sanitary Permit', 'documents/69fd7cf2834cc_1778220274.png', '2026-05-08 06:04:34'),
(58, 4, '1.8 Fire Clearance Certificate', 'documents/69fd7cf6e63d5_1778220278.png', '2026-05-08 06:04:38'),
(59, 4, '1.9 DENR Environment Certificate', 'documents/69fd7cf9f1b24_1778220281.jpg', '2026-05-08 06:04:41'),
(60, 4, '1.10 FDA License to Operate (LTO)', 'documents/69fd7cfe88c0e_1778220286.jpg', '2026-05-08 06:04:46'),
(61, 4, '1.11 Certificate of Product Registration (CPR)', 'documents/69fd7d038a19e_1778220291.jpg', '2026-05-08 06:04:51'),
(62, 4, '2.2 Halal Assurance System (HAS) Manual', 'documents/69fd7d096a625_1778220297.jpg', '2026-05-08 06:04:57'),
(63, 4, '2.3 Waste Disposal Management Plan', 'documents/69fd7d0ee1201_1778220302.pdf', '2026-05-08 06:05:02'),
(64, 4, '2.4 Pest Control Program', 'documents/69fd7d1291c4f_1778220306.pdf', '2026-05-08 06:05:06'),
(65, 4, '2.5 Kitchen Layout', 'documents/69fd7d1f75d49_1778220319.jpg', '2026-05-08 06:05:19'),
(66, 4, '2.6 Flow Chart of Product Processing', 'documents/69fd7d282df6e_1778220328.jpg', '2026-05-08 06:05:28'),
(67, 4, '3.1 Full List of Products / Menu with Corresponding Ingredients', 'documents/69fd7d30afeb0_1778220336.jpg', '2026-05-08 06:05:36'),
(68, 4, '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)', 'documents/69fd7d36e3c46_1778220342.png', '2026-05-08 06:05:42'),
(69, 4, '3.3 Packaging Materials List with Corresponding Halal Certificates', 'documents/69fd7d410516e_1778220353.jpg', '2026-05-08 06:05:53'),
(70, 4, '4.1 Halal Certificates for All Raw Materials (especially meat products)', 'documents/69fd7d50c1106_1778220368.jpg', '2026-05-08 06:06:08'),
(71, 4, '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members', 'documents/69fd7d576169d_1778220375.jpg', '2026-05-08 06:06:15'),
(72, 4, '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen', 'documents/69fd7d5bb789e_1778220379.pdf', '2026-05-08 06:06:19'),
(73, 4, '4.5 Warehouse Halal Certificate and Storage System Description', 'documents/69fd7d63ca35f_1778220387.jpg', '2026-05-08 06:06:27'),
(74, 4, '4.6 Transportation Details for Halal Products', 'documents/69fd7d67c66b4_1778220391.pdf', '2026-05-08 06:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `application_verification`
--

CREATE TABLE `application_verification` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `verification_status` enum('pending','verified','returned','incomplete') DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `verified_requirements` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_verification`
--

INSERT INTO `application_verification` (`id`, `application_id`, `evaluator_id`, `verification_status`, `feedback`, `verified_requirements`, `verified_at`, `created_at`) VALUES
(7, 4, 2, 'verified', '', NULL, '2026-05-08 06:07:45', '2026-05-08 06:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `corrective_action_reports`
--

CREATE TABLE `corrective_action_reports` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `laboratory_report_id` int(11) DEFAULT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `report_content` text NOT NULL,
  `corrective_actions` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','approved') DEFAULT 'draft',
  `prepared_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_decisions`
--

CREATE TABLE `final_decisions` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `potential_decision_id` int(11) DEFAULT NULL,
  `committee_member_id` int(11) NOT NULL,
  `decision` enum('approved','rejected','deferred','pending') DEFAULT 'pending',
  `decision_notes` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `halal_certificates`
--

CREATE TABLE `halal_certificates` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `final_decision_id` int(11) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_address` text DEFAULT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `halal_logo_path` varchar(500) DEFAULT NULL,
  `certificate_file_path` varchar(500) DEFAULT NULL,
  `decorated_by` int(11) DEFAULT NULL,
  `awarded_by` int(11) DEFAULT NULL,
  `status` enum('prepared','decorated','awarded','active','expired','revoked','suspended') DEFAULT 'prepared',
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `halal_restaurants`
--

CREATE TABLE `halal_restaurants` (
  `id` int(11) NOT NULL,
  `business_owner_id` int(11) NOT NULL,
  `certificate_id` int(11) DEFAULT NULL,
  `restaurant_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `operating_hours` text DEFAULT NULL,
  `cuisine_type` varchar(100) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hdp_applications`
--

CREATE TABLE `hdp_applications` (
  `id` int(11) NOT NULL,
  `loi_id` int(11) NOT NULL,
  `business_owner_id` int(11) NOT NULL,
  `application_form_path` varchar(500) DEFAULT NULL,
  `supporting_papers` text DEFAULT NULL,
  `request_form_path` varchar(500) DEFAULT NULL,
  `reviews_ratings` text DEFAULT NULL,
  `laboratory_report_path` varchar(500) DEFAULT NULL,
  `requirements_complete` tinyint(1) DEFAULT 0,
  `enterprise_type` enum('micro','small','medium') DEFAULT NULL,
  `status` enum('draft','submitted','under_review','verified','incomplete','approved') DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hdp_applications`
--

INSERT INTO `hdp_applications` (`id`, `loi_id`, `business_owner_id`, `application_form_path`, `supporting_papers`, `request_form_path`, `reviews_ratings`, `laboratory_report_path`, `requirements_complete`, `enterprise_type`, `status`, `submitted_at`, `created_at`, `updated_at`) VALUES
(4, 10, 1, NULL, NULL, NULL, NULL, NULL, 0, 'micro', 'verified', '2026-05-08 06:07:06', '2026-05-08 06:03:04', '2026-05-08 06:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `auditor_technical_id` int(11) DEFAULT NULL,
  `auditor_shariah_id` int(11) DEFAULT NULL,
  `audit_findings` text DEFAULT NULL,
  `tech_audit_findings` text DEFAULT NULL,
  `tech_conformity_status` enum('conforming','non_conforming','partial','pending') DEFAULT 'pending',
  `tech_remarks` text DEFAULT NULL,
  `tech_completed_at` timestamp NULL DEFAULT NULL,
  `shariah_audit_findings` text DEFAULT NULL,
  `shariah_conformity_status` enum('conforming','non_conforming','partial','pending') DEFAULT 'pending',
  `shariah_remarks` text DEFAULT NULL,
  `shariah_completed_at` timestamp NULL DEFAULT NULL,
  `conformity_status` enum('conforming','non_conforming','partial','pending') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `inspection_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`id`, `schedule_id`, `application_id`, `auditor_technical_id`, `auditor_shariah_id`, `audit_findings`, `tech_audit_findings`, `tech_conformity_status`, `tech_remarks`, `tech_completed_at`, `shariah_audit_findings`, `shariah_conformity_status`, `shariah_remarks`, `shariah_completed_at`, `conformity_status`, `remarks`, `inspection_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 3, 4, 5, 6, NULL, 'take note of the documents that needs to be fixed', 'partial', '', '2026-05-08 08:40:04', 'a lab test is needed for each dish in the menu', 'partial', '', '2026-05-08 08:25:20', 'partial', NULL, '2026-05-08', 'completed', '2026-05-08 06:24:37', '2026-05-08 08:40:04');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_document_conformity`
--

CREATE TABLE `inspection_document_conformity` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `auditor_id` int(11) NOT NULL DEFAULT 0,
  `auditor_role` enum('technical','shariah') NOT NULL DEFAULT 'technical',
  `checklist_key` varchar(100) NOT NULL,
  `requirement_label` varchar(255) NOT NULL,
  `uploaded_file_id` int(11) DEFAULT NULL,
  `conformity_status` enum('conforming','non_conforming','not_applicable') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspection_document_conformity`
--

INSERT INTO `inspection_document_conformity` (`id`, `inspection_id`, `application_id`, `auditor_id`, `auditor_role`, `checklist_key`, `requirement_label`, `uploaded_file_id`, `conformity_status`, `remarks`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 6, 'shariah', 'business_permit', 'Business Permit', 29, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(2, 1, 3, 6, 'shariah', 'license_to_operate', 'License to Operate', 34, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(3, 1, 3, 6, 'shariah', 'mayors_permit', 'Mayor\'s Permit', 28, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(4, 1, 3, 6, 'shariah', 'barangay_permit', 'Barangay Permit', 30, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(5, 1, 3, 6, 'shariah', 'fda_cpr', 'FDA CPR of the Products', 35, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(6, 1, 3, 6, 'shariah', 'dti_sec_license', 'DTI / SEC Registration License', 27, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(7, 1, 3, 6, 'shariah', 'sanitary_permit', 'Sanitary Permit', 31, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(8, 1, 3, 6, 'shariah', 'fire_clearance', 'Fire Clearance Certificate', 32, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(9, 1, 3, 6, 'shariah', 'denr_certificate', 'Environment Certificate - DENR', 33, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(10, 1, 3, 6, 'shariah', 'quality_certificates', 'GMP, HACCP, ISO Certificate (if any)', NULL, 'not_applicable', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(11, 1, 3, 6, 'shariah', 'packaging_halal_certificate', 'Packaging Halal Certificate', 43, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(12, 1, 3, 6, 'shariah', 'warehouse_halal_certificate', 'Warehouse Halal Certificate', 47, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(13, 1, 3, 6, 'shariah', 'products_menu_ingredients', 'Products / Menu and Ingredients', 41, 'conforming', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(14, 1, 3, 6, 'shariah', 'raw_material_halal_certificate', 'Halal Certificate of Raw Materials', 44, 'non_conforming', 'outdated', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(15, 1, 3, 6, 'shariah', 'previous_hdip_certificate', 'Previous Halal Certificate from HDIP', NULL, 'not_applicable', '', 6, '2026-05-08 03:59:44', '2026-05-08 03:59:44', '2026-05-08 03:59:44'),
(16, 1, 3, 5, 'technical', 'business_permit', 'Business Permit', 29, 'conforming', '', 5, '2026-05-08 04:02:22', '2026-05-08 04:00:17', '2026-05-08 04:02:22'),
(17, 1, 3, 5, 'technical', 'license_to_operate', 'License to Operate', 34, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(18, 1, 3, 5, 'technical', 'mayors_permit', 'Mayor\'s Permit', 28, 'non_conforming', 'outdated', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(19, 1, 3, 5, 'technical', 'barangay_permit', 'Barangay Permit', 30, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(20, 1, 3, 5, 'technical', 'fda_cpr', 'FDA CPR of the Products', 35, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(21, 1, 3, 5, 'technical', 'dti_sec_license', 'DTI / SEC Registration License', 27, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(22, 1, 3, 5, 'technical', 'sanitary_permit', 'Sanitary Permit', 31, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(23, 1, 3, 5, 'technical', 'fire_clearance', 'Fire Clearance Certificate', 32, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(24, 1, 3, 5, 'technical', 'denr_certificate', 'Environment Certificate - DENR', 33, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(25, 1, 3, 5, 'technical', 'quality_certificates', 'GMP, HACCP, ISO Certificate (if any)', NULL, 'not_applicable', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(26, 1, 3, 5, 'technical', 'packaging_halal_certificate', 'Packaging Halal Certificate', 43, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(27, 1, 3, 5, 'technical', 'warehouse_halal_certificate', 'Warehouse Halal Certificate', 47, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(28, 1, 3, 5, 'technical', 'products_menu_ingredients', 'Products / Menu and Ingredients', 41, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(29, 1, 3, 5, 'technical', 'raw_material_halal_certificate', 'Halal Certificate of Raw Materials', 44, 'conforming', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(30, 1, 3, 5, 'technical', 'previous_hdip_certificate', 'Previous Halal Certificate from HDIP', NULL, 'not_applicable', '', 5, '2026-05-08 04:02:23', '2026-05-08 04:00:17', '2026-05-08 04:02:23'),
(46, 2, 4, 5, 'technical', 'business_permit', 'Business Permit', 56, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(47, 2, 4, 5, 'technical', 'license_to_operate', 'License to Operate', 60, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(48, 2, 4, 5, 'technical', 'mayors_permit', 'Mayor\'s Permit', 52, 'non_conforming', 'pls update', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(49, 2, 4, 5, 'technical', 'barangay_permit', 'Barangay Permit', 54, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(50, 2, 4, 5, 'technical', 'fda_cpr', 'FDA CPR of the Products', 61, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(51, 2, 4, 5, 'technical', 'dti_sec_license', 'DTI / SEC Registration License', 51, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(52, 2, 4, 5, 'technical', 'sanitary_permit', 'Sanitary Permit', 57, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(53, 2, 4, 5, 'technical', 'fire_clearance', 'Fire Clearance Certificate', 58, 'non_conforming', 'display it on your establishment', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(54, 2, 4, 5, 'technical', 'denr_certificate', 'Environment Certificate - DENR', 59, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(55, 2, 4, 5, 'technical', 'quality_certificates', 'GMP, HACCP, ISO Certificate (if any)', NULL, 'not_applicable', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(56, 2, 4, 5, 'technical', 'packaging_halal_certificate', 'Packaging Halal Certificate', 69, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(57, 2, 4, 5, 'technical', 'warehouse_halal_certificate', 'Warehouse Halal Certificate', 73, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(58, 2, 4, 5, 'technical', 'products_menu_ingredients', 'Products / Menu and Ingredients', 67, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(59, 2, 4, 5, 'technical', 'raw_material_halal_certificate', 'Halal Certificate of Raw Materials', 70, 'conforming', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(60, 2, 4, 5, 'technical', 'previous_hdip_certificate', 'Previous Halal Certificate from HDIP', NULL, 'not_applicable', '', 5, '2026-05-08 08:40:04', '2026-05-08 08:19:16', '2026-05-08 08:40:04'),
(61, 2, 4, 6, 'shariah', 'business_permit', 'Business Permit', 56, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(62, 2, 4, 6, 'shariah', 'license_to_operate', 'License to Operate', 60, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(63, 2, 4, 6, 'shariah', 'mayors_permit', 'Mayor\'s Permit', 52, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(64, 2, 4, 6, 'shariah', 'barangay_permit', 'Barangay Permit', 54, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(65, 2, 4, 6, 'shariah', 'fda_cpr', 'FDA CPR of the Products', 61, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(66, 2, 4, 6, 'shariah', 'dti_sec_license', 'DTI / SEC Registration License', 51, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(67, 2, 4, 6, 'shariah', 'sanitary_permit', 'Sanitary Permit', 57, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(68, 2, 4, 6, 'shariah', 'fire_clearance', 'Fire Clearance Certificate', 58, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(69, 2, 4, 6, 'shariah', 'denr_certificate', 'Environment Certificate - DENR', 59, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(70, 2, 4, 6, 'shariah', 'quality_certificates', 'GMP, HACCP, ISO Certificate (if any)', NULL, 'not_applicable', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(71, 2, 4, 6, 'shariah', 'packaging_halal_certificate', 'Packaging Halal Certificate', 69, 'non_conforming', 'display it properly', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(72, 2, 4, 6, 'shariah', 'warehouse_halal_certificate', 'Warehouse Halal Certificate', 73, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(73, 2, 4, 6, 'shariah', 'products_menu_ingredients', 'Products / Menu and Ingredients', 67, 'non_conforming', 'conduct a lab test for each dish in your menu', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(74, 2, 4, 6, 'shariah', 'raw_material_halal_certificate', 'Halal Certificate of Raw Materials', 70, 'conforming', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20'),
(75, 2, 4, 6, 'shariah', 'previous_hdip_certificate', 'Previous Halal Certificate from HDIP', NULL, 'not_applicable', '', 6, '2026-05-08 08:25:20', '2026-05-08 08:25:20', '2026-05-08 08:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_schedules`
--

CREATE TABLE `inspection_schedules` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `tor_id` int(11) DEFAULT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` time DEFAULT NULL,
  `location` text DEFAULT NULL,
  `inspectors` text DEFAULT NULL,
  `inspection_fees` decimal(10,2) DEFAULT 0.00,
  `status` enum('scheduled','confirmed','in_progress','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspection_schedules`
--

INSERT INTO `inspection_schedules` (`id`, `application_id`, `evaluator_id`, `tor_id`, `schedule_date`, `schedule_time`, `location`, `inspectors`, `inspection_fees`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 4, 2, NULL, '2026-05-13', '13:31:00', 'TORIL', '', 1000.00, 'completed', '', '2026-05-08 06:09:13', '2026-05-08 08:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `laboratory_analyses`
--

CREATE TABLE `laboratory_analyses` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `analysis_method` text DEFAULT NULL,
  `analysis_results` text DEFAULT NULL,
  `conclusion` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laboratory_reports`
--

CREATE TABLE `laboratory_reports` (
  `id` int(11) NOT NULL,
  `laboratory_request_id` int(11) DEFAULT NULL,
  `application_id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `report_number` varchar(50) DEFAULT NULL,
  `report_content` text NOT NULL,
  `report_file_path` varchar(500) DEFAULT NULL,
  `halal_status` enum('halal','haram','mushbooh') DEFAULT NULL,
  `status` enum('draft','finalized','sent') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laboratory_reports`
--

INSERT INTO `laboratory_reports` (`id`, `laboratory_request_id`, `application_id`, `analyst_id`, `report_number`, `report_content`, `report_file_path`, `halal_status`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 4, 12, 'R11-2026-59700', 'REPORT OF ANALYSIS — Halal Verification Testing\nRequest Reference No: R11-2026-59700\nDate Analyzed: 2026-05-13 | Date Reported: 2026-05-13\nSample Submitted: \n\nSAMPLE RESULTS:\n  HVL-00001 | sample | Thermometry Calibration | Negative\n  HVL-00002 | sample | Hygrometer | Negative\n  HVL-00003 | sample | Coliform Count, MPN | Negative\n  HVL-00004 | sample | E. coli Detection, MPN | Negative\n  HVL-00005 | sample | Salmonella | Negative\n  HVL-00006 | sample | Yeast and Mold Count | Negative\n  HVL-00007 | sample | Crude Fat (Soxhlet) | Negative\n  HVL-00008 | sample | Nutritional Facts (Formulation) | Negative\n  HVL-00009 | sample | Nutritional Facts (Computation) | Negative\n\nMETHODOLOGY: ehhh\n\nREMARKS: The results given in this report were obtained at the time of test and refer only to the particular sample submitted.\r\nThis report shall not be reproduced except in full, without the written approval of the laboratory.\r\nAll text with a single asterisk are provided by the customer at the time of submission.\n\nAnalyzed By: joseph clamucha (PRC )\nReviewed By:  (PRC )\nApproved By: ', NULL, 'halal', 'draft', '2026-05-13 11:36:53', '2026-05-13 11:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `laboratory_requests`
--

CREATE TABLE `laboratory_requests` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `business_owner_id` int(11) NOT NULL,
  `request_number` varchar(50) DEFAULT NULL,
  `sample_description` text NOT NULL,
  `analysis_requirements` text NOT NULL,
  `payment_testing_fee` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','paid','waived') DEFAULT 'unpaid',
  `status` enum('submitted','pending_payment','received','testing','completed','rejected') DEFAULT 'submitted',
  `received_by` int(11) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `receiving_notes` text DEFAULT NULL,
  `analyst_id` int(11) DEFAULT NULL,
  `testing_started_at` timestamp NULL DEFAULT NULL,
  `test_result` enum('pass','fail','inconclusive') DEFAULT NULL,
  `analysis_details` text DEFAULT NULL,
  `halal_status` enum('halal','haram','mushbooh') DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laboratory_requests`
--

INSERT INTO `laboratory_requests` (`id`, `application_id`, `business_owner_id`, `request_number`, `sample_description`, `analysis_requirements`, `payment_testing_fee`, `payment_status`, `status`, `received_by`, `received_at`, `receiving_notes`, `analyst_id`, `testing_started_at`, `test_result`, `analysis_details`, `halal_status`, `completed_at`, `created_at`, `updated_at`) VALUES
(6, 4, 1, NULL, 'HVL-00001: sample\nHVL-00002: sample\nHVL-00003: sample\nHVL-00004: sample\nHVL-00005: sample\nHVL-00006: sample\nHVL-00007: sample\nHVL-00008: sample\nHVL-00009: sample', '{\"tests\":[{\"test_name\":\"Thermometry Calibration\",\"quantity\":\"1\",\"unit_cost\":\"1700.00\",\"total\":\"1700.00\"},{\"test_name\":\"Hygrometer\",\"quantity\":\"1\",\"unit_cost\":\"700.00\",\"total\":\"700.00\"},{\"test_name\":\"Coliform Count, MPN\",\"quantity\":\"1\",\"unit_cost\":\"525.00\",\"total\":\"525.00\"},{\"test_name\":\"E. coli Detection, MPN\",\"quantity\":\"1\",\"unit_cost\":\"550.00\",\"total\":\"550.00\"},{\"test_name\":\"Salmonella\",\"quantity\":\"1\",\"unit_cost\":\"1100.00\",\"total\":\"1100.00\"},{\"test_name\":\"Yeast and Mold Count\",\"quantity\":\"1\",\"unit_cost\":\"550.00\",\"total\":\"550.00\"},{\"test_name\":\"Crude Fat (Soxhlet)\",\"quantity\":\"1\",\"unit_cost\":\"1200.00\",\"total\":\"1200.00\"},{\"test_name\":\"Nutritional Facts (Formulation)\",\"quantity\":\"1\",\"unit_cost\":\"5150.00\",\"total\":\"5150.00\"},{\"test_name\":\"Nutritional Facts (Computation)\",\"quantity\":\"1\",\"unit_cost\":\"5150.00\",\"total\":\"5150.00\"}],\"request_interpretation\":\"no\",\"delivery_method\":\"pickup\",\"special_instructions\":\"\"}', 16625.00, 'paid', 'completed', 11, '2026-05-13 11:22:05', NULL, 12, '2026-05-13 11:25:18', 'pass', 'compliant', 'halal', '2026-05-13 11:36:53', '2026-05-13 11:20:59', '2026-05-13 11:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `letter_of_intent`
--

CREATE TABLE `letter_of_intent` (
  `id` int(11) NOT NULL,
  `business_owner_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` text NOT NULL,
  `company_info` text NOT NULL,
  `date_of_intent` date DEFAULT NULL,
  `menu_list` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `letter_content` text NOT NULL,
  `supporting_documents` text DEFAULT NULL,
  `status` enum('draft','submitted','under_review','verified','returned','rejected') DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `application_type` varchar(255) DEFAULT NULL,
  `certifying_body` varchar(255) DEFAULT 'HDIP',
  `signature_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `letter_of_intent`
--

INSERT INTO `letter_of_intent` (`id`, `business_owner_id`, `company_name`, `company_address`, `company_info`, `date_of_intent`, `menu_list`, `contact_person`, `contact_email`, `contact_phone`, `letter_content`, `supporting_documents`, `status`, `remarks`, `submitted_at`, `created_at`, `updated_at`, `application_type`, `certifying_body`, `signature_data`) VALUES
(10, 1, 'Halal Keeps', 'toril', '', '2026-05-08', 'Adobo', 'Jasmine Duran', 'jasmine123@gmail.com', '', '', NULL, 'verified', '', '2026-05-08 00:01:18', '2026-05-08 06:01:18', '2026-05-08 06:02:49', 'Initial Application', 'HDIP', 'data:image/png;base64,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');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `user_agent`, `success`, `attempt_time`) VALUES
(1, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 0, '2026-05-08 02:48:08'),
(2, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 02:48:19'),
(3, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 02:49:42'),
(4, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:22:24'),
(5, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:27:10'),
(6, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 0, '2026-05-08 03:41:28'),
(7, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 0, '2026-05-08 03:42:03'),
(8, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 0, '2026-05-08 03:42:16'),
(9, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 1, '2026-05-08 03:45:11'),
(10, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:48:37'),
(11, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:50:28'),
(12, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 0, '2026-05-08 03:50:54'),
(13, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 0, '2026-05-08 03:51:06'),
(14, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:51:14'),
(15, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:57:51'),
(16, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 03:58:01'),
(17, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 04:39:44'),
(18, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 04:48:32'),
(19, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 04:50:48'),
(20, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 05:13:57'),
(21, 'jasmineduran234@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 05:22:15'),
(22, 'jasmineduran234@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 05:27:51'),
(23, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 06:00:32'),
(24, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 06:00:49'),
(25, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 0, '2026-05-08 06:01:43'),
(26, 'admin@halal.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 1, '2026-05-08 06:01:49'),
(27, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 06:02:43'),
(28, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 06:06:45'),
(29, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 0, '2026-05-08 07:43:14'),
(30, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 07:43:21'),
(31, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 07:45:33'),
(32, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 07:45:39'),
(33, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 07:58:46'),
(34, 'jamieduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 07:59:24'),
(35, 'jamsduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-08 08:00:15'),
(36, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 1, '2026-05-13 04:55:18'),
(37, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 1, '2026-05-13 05:14:20'),
(38, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-05-13 05:28:58'),
(39, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 1, '2026-05-13 05:29:05'),
(40, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 0, '2026-05-13 06:08:26'),
(41, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 1, '2026-05-13 06:08:31'),
(42, 'jayduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 1, '2026-05-13 06:49:10'),
(43, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 10:25:21'),
(44, 'jayduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 10:26:25'),
(45, 'jamiedeporos@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 10:47:14'),
(46, 'josephclamucha@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 10:59:46'),
(47, 'jayduran@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 11:15:46'),
(48, 'jasmine123@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 1, '2026-05-13 11:16:10');

-- --------------------------------------------------------

--
-- Table structure for table `loi_requirements`
--

CREATE TABLE `loi_requirements` (
  `id` int(11) NOT NULL,
  `loi_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `requirements` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loi_requirements`
--

INSERT INTO `loi_requirements` (`id`, `loi_id`, `evaluator_id`, `requirements`, `sent_at`) VALUES
(10, 10, 2, '1. Administrative & Legal Documentation\n1.1 Letter of Intent\n1.2 Company Profile\n1.3 SEC Registration / DTI License / Mayor\'s or Business Permit\n1.4 Barangay Permit and Sanitary Permit\n1.5 Fire Clearance Certificate and DENR Environment Certificate\n1.6 FDA License to Operate (LTO) and Certificate of Product Registration (CPR) for all products\n1.7 Previous Halal Certificate from HDIP (if applicable/optional)\n2. Technical & Quality Management\n2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available/optional)\n2.2 Halal Assurance System (HAS) Manual\n2.3 Waste Disposal Management Plan\n2.4 Pest Control Program\n2.5 Kitchen Layout\n2.6 Flow Chart of Product Processing\n3. Product & Raw Material Specifications\n3.1 Full List of Products / Menu with Corresponding Ingredients\n3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)\n3.3 Packaging Materials List with Corresponding Halal Certificates\n4. Halal Compliance & Logistics\n4.1 Halal Certificates for All Raw Materials (especially meat products)\n4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members\n4.3 Proof of Dedicated Halal Prayer Room\n4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen\n4.5 Warehouse Halal Certificate and Storage System Description\n4.6 Transportation Details for Halal Products', '2026-05-08 06:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `loi_verification`
--

CREATE TABLE `loi_verification` (
  `id` int(11) NOT NULL,
  `loi_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `verification_status` enum('pending','verified','returned') DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `verified_letter_path` varchar(500) DEFAULT NULL,
  `notify_inspection` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loi_verification`
--

INSERT INTO `loi_verification` (`id`, `loi_id`, `evaluator_id`, `verification_status`, `feedback`, `verified_letter_path`, `notify_inspection`, `verified_at`, `created_at`) VALUES
(15, 10, 2, 'verified', '', NULL, 0, '2026-05-08 06:02:49', '2026-05-08 06:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ncr_reports`
--

CREATE TABLE `ncr_reports` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `ncr_number` varchar(50) DEFAULT NULL,
  `finding_details` text NOT NULL,
  `category` enum('minor','major','serious','observation') DEFAULT 'minor',
  `corrective_action_required` text DEFAULT NULL,
  `corrective_action_taken` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `time_of_inspection` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `person_in_charge` varchar(255) DEFAULT NULL,
  `document_reference` varchar(255) DEFAULT NULL,
  `brief_summary` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `followup_date` date DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `requires_lab_test` tinyint(1) NOT NULL DEFAULT 0,
  `auditor_type` enum('technical','shariah') DEFAULT NULL,
  `lab_test_details` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ncr_reports`
--

INSERT INTO `ncr_reports` (`id`, `inspection_id`, `application_id`, `ncr_number`, `finding_details`, `category`, `corrective_action_required`, `corrective_action_taken`, `deadline`, `time_of_inspection`, `location`, `person_in_charge`, `document_reference`, `brief_summary`, `recommendation`, `followup_date`, `prepared_by`, `requires_lab_test`, `auditor_type`, `lab_test_details`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 4, 'NCR-2026-74516', 'take note of the documents that needs to be fixed\r\nfix the orientation of your table for easy access of the servers', 'minor', 'Fire Clearance Certificate: display it on your establishment\r\nMayor&#039;s Permit: pls update\r\nDisplay your business invoice number more visibly', NULL, '2026-05-11', '13:31:00', 'TORIL', 'Jasmine Duran', 'HAS/MANUAL/IHA-CHKLIST/01-2016', 'Halal Keeps is partially compliant with some documents needed to be updated', 'Install more ventilation in the toilet and the kitchen', '2026-05-18', 5, 0, NULL, NULL, 'open', '2026-05-08 08:44:50', '2026-05-08 08:44:50'),
(4, 2, 4, 'NCR-2026-97427', 'a lab test is needed for each dish in the menu', 'minor', 'Packaging Halal Certificate: display it properly\r\nProducts / Menu and Ingredients: conduct a lab test for each dish in your menu', NULL, '2026-05-18', '13:31:00', 'TORIL', 'Jasmine Duran', 'HAS/MANUAL/IHA-CHKLIST/01-2016', 'halal keeps needs lab tests to be settled and before creating a corrective action report', 'pls comply', '2026-05-22', 6, 1, 'shariah', '{&quot;calibration&quot;:[&quot;thermometry_all_types&quot;,&quot;hygrometer&quot;],&quot;microbiology&quot;:[&quot;coliform_count_mpn&quot;,&quot;e_coli_detection_mpn&quot;,&quot;salmonella&quot;,&quot;yeast_and_mold_count&quot;],&quot;chemistry&quot;:[&quot;crude_fat_soxhlet_and_hydrolysis&quot;,&quot;nutritional_facts_formulation_drafting_nutritional_facts&quot;,&quot;nutritional_facts_computation_nutritional_facts&quot;]}', 'open', '2026-05-13 05:28:28', '2026-05-13 05:28:28');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','action_required') DEFAULT 'info',
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(26, 2, 'New Letter of Intent', 'Jasmine Duran has submitted a Letter of Intent for Halal Keeps', 'action_required', '/halal_final/dashboard/evaluator/verify_loi.php', 0, '2026-04-28 11:26:47'),
(27, 1, 'LOI Returned', 'Your Letter of Intent has been returned. Please check the feedback and resubmit.', 'warning', '/halal_final/dashboard/business_owner/letter_of_intent.php?action=view&id=7', 1, '2026-04-28 11:28:05'),
(28, 2, 'New Letter of Intent', 'Jasmine Duran has submitted a Letter of Intent for Halal Keeps', 'action_required', '/halal_final/dashboard/evaluator/verify_loi.php', 0, '2026-04-28 11:28:09'),
(29, 1, 'LOI Approved — Requirements Sent', 'Your Letter of Intent has been approved. The list of requirements you need to submit has been sent. Please review and proceed with your application.', 'success', '/halal_final/dashboard/business_owner/letter_of_intent.php?action=view&id=8', 0, '2026-04-28 11:29:12'),
(30, 2, 'New HDP Application', 'Jasmine Duran has submitted an HDP application.', 'action_required', '/halal_final/dashboard/evaluator/verify_applications.php', 1, '2026-04-28 11:31:19'),
(31, 1, 'LOI Approved — Requirements Sent', 'Your Letter of Intent has been approved. The list of requirements you need to submit has been sent. Please review and proceed with your application.', 'success', '/halal_final/dashboard/business_owner/letter_of_intent.php?action=view&id=8', 0, '2026-04-28 11:31:25'),
(32, 1, 'Application Verified', 'Your HDP application has been verified.', 'success', '/halal_final/dashboard/business_owner/applications.php', 1, '2026-04-28 11:33:01'),
(33, 2, 'New HDP Application', 'Jasmine Duran has submitted an HDP application.', 'action_required', '/halal_final/dashboard/evaluator/verify_applications.php', 1, '2026-04-28 11:33:07'),
(34, 1, 'Application Verified', 'Your HDP application has been verified.', 'success', '/halal_final/dashboard/business_owner/applications.php', 1, '2026-04-28 11:33:27'),
(35, 2, 'New Letter of Intent', 'Jasmine Duran has submitted a Letter of Intent for halal keeps', 'action_required', '/halal_final/dashboard/evaluator/verify_loi.php', 1, '2026-05-08 03:02:08'),
(36, 1, 'LOI Approved — Requirements Sent', 'Your Letter of Intent has been approved. The list of requirements you need to submit has been sent. Please review and proceed with your application.', 'success', '/halal_final/dashboard/business_owner/letter_of_intent.php?action=view&id=9', 1, '2026-05-08 03:02:21'),
(37, 2, 'New HDP Application', 'Jasmine Duran has submitted an HDP application.', 'action_required', '/halal_final/dashboard/evaluator/verify_applications.php', 1, '2026-05-08 03:17:56'),
(38, 1, 'Application Approved', 'Your HDP application has been approved.', 'success', '/halal_final/dashboard/business_owner/applications.php', 1, '2026-05-08 03:19:33'),
(39, 1, 'Terms of Reference Sent', 'The evaluator has sent you the Terms of Reference for your halal certification. Please review and respond.', 'action_required', '/halal_final/dashboard/business_owner/terms_of_reference.php', 1, '2026-05-08 03:27:55'),
(40, 2, 'TOR Response: Rejected', 'Jasmine Duran has rejected the Terms of Reference and left remarks.', 'warning', '/halal_final/dashboard/evaluator/terms_of_reference.php?action=view&id=1', 1, '2026-05-08 03:28:03'),
(41, 1, 'Terms of Reference Sent', 'The evaluator has sent you the Terms of Reference for your halal certification. Please review and respond.', 'action_required', '/halal_final/dashboard/business_owner/terms_of_reference.php', 1, '2026-05-08 03:52:15'),
(42, 2, 'TOR Response: Accepted', 'Jasmine Duran has accepted the Terms of Reference.', 'success', '/halal_final/dashboard/evaluator/terms_of_reference.php?action=view&id=2', 1, '2026-05-08 03:56:29'),
(43, 1, 'Inspection Scheduled — Payment Required', 'An on-site inspection has been scheduled for 2026-05-12 at 01:30. Please settle the inspection fee of ₱1,000.00 before the inspection date to confirm your slot.', 'action_required', '/halal_final/dashboard/business_owner/inspection_schedules.php', 1, '2026-05-08 03:56:56'),
(44, 2, 'Inspection Payment Verified', 'Jasmine Duran completed PayMongo payment for the inspection schedule of halal keeps.', 'success', '/halal_final/dashboard/evaluator/inspection_schedules.php', 0, '2026-05-08 03:57:18'),
(45, 6, 'Inspection Payment Confirmed — Ready to Start', 'halal keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 1, '2026-05-08 03:57:18'),
(46, 5, 'Inspection Payment Confirmed — Ready to Start', 'halal keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 1, '2026-05-08 03:57:18'),
(47, 1, 'Inspection Scheduled — Payment Required', 'An on-site inspection has been scheduled for 2026-05-12 at 01:30. Please settle the inspection fee of ₱1,000.00 before the inspection date to confirm your slot.', 'action_required', '/halal_final/dashboard/business_owner/inspection_schedules.php', 1, '2026-05-08 03:57:41'),
(48, 2, 'Inspection Payment Verified', 'Jasmine Duran completed PayMongo payment for the inspection schedule of halal keeps.', 'success', '/halal_final/dashboard/evaluator/inspection_schedules.php', 0, '2026-05-08 03:58:20'),
(49, 6, 'Inspection Payment Confirmed — Ready to Start', 'halal keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 0, '2026-05-08 03:58:20'),
(50, 5, 'Inspection Payment Confirmed — Ready to Start', 'halal keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 0, '2026-05-08 03:58:20'),
(51, 1, 'Inspection Completed — Partial Conformity', 'Both auditors have completed their inspection with partial conformity. Some documents require attention. Please review the audit findings and any NCR reports issued.', 'warning', '/halal_final/dashboard/business_owner/audit_findings.php', 1, '2026-05-08 04:02:23'),
(52, 2, 'New Letter of Intent', 'Jasmine Duran has submitted a Letter of Intent for Halal Keeps', 'action_required', '/halal_final/dashboard/evaluator/verify_loi.php', 1, '2026-05-08 06:01:18'),
(53, 1, 'LOI Approved — Requirements Sent', 'Your Letter of Intent has been approved. The list of requirements you need to submit has been sent. Please review and proceed with your application.', 'success', '/halal_final/dashboard/business_owner/letter_of_intent.php?action=view&id=10', 1, '2026-05-08 06:02:49'),
(54, 2, 'New HDP Application', 'Jasmine Duran has submitted an HDP application.', 'action_required', '/halal_final/dashboard/evaluator/verify_applications.php', 0, '2026-05-08 06:07:07'),
(55, 1, 'Application Approved', 'Your HDP application has been approved.', 'success', '/halal_final/dashboard/business_owner/applications.php', 1, '2026-05-08 06:07:45'),
(56, 1, 'Terms of Reference Sent', 'The evaluator has sent you the Terms of Reference for your halal certification. Please review and respond.', 'action_required', '/halal_final/dashboard/business_owner/terms_of_reference.php', 0, '2026-05-08 06:08:36'),
(57, 2, 'TOR Response: Accepted', 'Jasmine Duran has accepted the Terms of Reference.', 'success', '/halal_final/dashboard/evaluator/terms_of_reference.php?action=view&id=3', 0, '2026-05-08 06:08:52'),
(58, 1, 'Inspection Scheduled — Payment Required', 'An on-site inspection has been scheduled for 2026-05-13 at 13:31. Please settle the inspection fee of ₱1,000.00 before the inspection date to confirm your slot.', 'action_required', '/halal_final/dashboard/business_owner/inspection_schedules.php', 1, '2026-05-08 06:09:13'),
(59, 2, 'TOR Response: Accepted', 'Jasmine Duran has accepted the Terms of Reference.', 'success', '/halal_final/dashboard/evaluator/terms_of_reference.php?action=view&id=3', 0, '2026-05-08 06:09:21'),
(60, 2, 'Inspection Payment Verified', 'Jasmine Duran completed PayMongo payment for the inspection schedule of Halal Keeps.', 'success', '/halal_final/dashboard/evaluator/inspection_schedules.php', 1, '2026-05-08 06:23:39'),
(61, 6, 'Inspection Payment Confirmed — Ready to Start', 'Halal Keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 0, '2026-05-08 06:23:39'),
(62, 5, 'Inspection Payment Confirmed — Ready to Start', 'Halal Keeps has settled the inspection fee. You may now proceed with the on-site inspection.', 'action_required', '/halal_final/dashboard/auditor/inspections.php', 1, '2026-05-08 06:23:39'),
(63, 1, 'NCR Issued: NCR-2026-20667', 'A Non-Conformance Report (MINOR) has been issued for your application. Please review and take corrective action.', 'warning', '/halal_final/dashboard/business_owner/audit_findings.php', 1, '2026-05-08 08:31:41'),
(64, 1, 'NCR Issued: NCR-2026-74516', 'A Non-Conformance Report (MINOR) has been issued for your application. Please review and take corrective action.', 'warning', '/halal_final/dashboard/business_owner/audit_findings.php', 1, '2026-05-08 08:44:50'),
(65, 1, 'NCR Issued: NCR-2026-65883', 'A Non-Conformance Report (MINOR) has been issued for your application. Please review and take corrective action.', 'warning', '/halal_final/dashboard/business_owner/audit_findings.php', 1, '2026-05-13 05:26:11'),
(66, 1, 'NCR Issued: NCR-2026-97427', 'A Non-Conformance Report (MINOR) has been issued for your application. Please review and take corrective action.', 'warning', '/halal_final/dashboard/business_owner/audit_findings.php', 1, '2026-05-13 05:28:28'),
(67, 1, 'Laboratory Request Approved - Payment Required', 'Your laboratory request has been approved. Please proceed with payment of ₱16,625.00 to continue.', 'action_required', '/halal_final/dashboard/business_owner/laboratory.php', 1, '2026-05-13 10:53:52'),
(68, 12, 'New Laboratory Test Request', 'A new laboratory test request has been submitted by Halal Keeps', 'action_required', '/halal_final/dashboard/lab_analyst/', 0, '2026-05-13 11:10:08'),
(69, 1, 'Laboratory Request Approved - Payment Required', 'Your laboratory request has been approved. Please proceed with payment of ₱16,625.00 to continue.', 'action_required', '/halal_final/dashboard/business_owner/laboratory.php', 1, '2026-05-13 11:10:28'),
(70, 12, 'New Laboratory Test Request', 'A new laboratory test request has been submitted by Halal Keeps', 'action_required', '/halal_final/dashboard/lab_analyst/', 0, '2026-05-13 11:19:05'),
(71, 1, 'Laboratory Request Approved - Payment Required', 'Your laboratory request has been approved. Please proceed with payment of ₱16,625.00 to continue.', 'action_required', '/halal_final/dashboard/business_owner/laboratory.php', 1, '2026-05-13 11:19:27'),
(72, 12, 'New Laboratory Test Request', 'A new laboratory test request has been submitted by Halal Keeps', 'action_required', '/halal_final/dashboard/lab_analyst/', 1, '2026-05-13 11:20:59'),
(73, 1, 'Laboratory Request Approved - Payment Required', 'Your laboratory request has been approved. Please proceed with payment of ₱16,625.00 to continue.', 'action_required', '/halal_final/dashboard/business_owner/laboratory.php', 1, '2026-05-13 11:21:18'),
(74, 1, 'Laboratory Request Approved - Payment Required', 'Your laboratory request has been approved. Please proceed with payment of ₱16,625.00 to continue.', 'action_required', '/halal_final/dashboard/business_owner/laboratory.php', 0, '2026-05-13 11:22:05'),
(75, 12, 'Laboratory Payment Received', 'Payment received for laboratory request from Halal Keeps. Ready for analysis.', 'action_required', '/halal_final/dashboard/lab_analyst/analyze_samples.php', 1, '2026-05-13 11:24:58'),
(76, 2, 'Lab Report Ready: R11-2026-59700', 'Laboratory analysis is complete. Report is available for review.', 'info', '/halal_final/dashboard/evaluator/', 0, '2026-05-13 11:36:53'),
(77, 5, 'Lab Report Ready: R11-2026-59700', 'Laboratory analysis is complete. Report is available for review.', 'info', '/halal_final/dashboard/evaluator/', 0, '2026-05-13 11:36:53'),
(78, 1, 'Laboratory Results Ready', 'Your laboratory testing is complete. Status: Halal', 'success', '/halal_final/dashboard/business_owner/laboratory.php', 1, '2026-05-13 11:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `items_json` text NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_method` enum('cash','gcash','bank_transfer','card') DEFAULT 'cash',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reference_type` enum('inspection','laboratory','certification','order') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `paymongo_checkout_url` text DEFAULT NULL,
  `paymongo_raw_response` longtext DEFAULT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `reference_type`, `reference_id`, `amount`, `payment_method`, `transaction_reference`, `paymongo_checkout_url`, `paymongo_raw_response`, `receipt_path`, `status`, `verified_by`, `paid_at`, `created_at`) VALUES
(1, 1, 'inspection', 1, 1000.00, 'paymongo', 'cs_6038fca9bfdb1971d0d84c52', 'https://checkout.paymongo.com/6038fca9bfdb1971d0d84c52', '{\"data\":{\"id\":\"cs_6038fca9bfdb1971d0d84c52\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_cancelled=1&schedule_id=1\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/6038fca9bfdb1971d0d84c52\",\"client_key\":\"cs_6038fca9bfdb1971d0d84c52_client_2019c11549f6266f533d93b7\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Inspection Fee - halal keeps\",\"line_items\":[{\"amount\":100000,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Inspection Fee - halal keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"1\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_xbb4FEJfwkFnyNE1eAxwcKa7\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":100000,\"capture_type\":\"automatic\",\"client_key\":\"pi_xbb4FEJfwkFnyNE1eAxwcKa7_client_UCLCjGwCpmCSZwtVmWJ6NSfk\",\"currency\":\"PHP\",\"description\":\"Inspection Fee - halal keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"1\"},\"next_action\":null,\"original_amount\":100000,\"payment_method_allowed\":[\"paymaya\",\"gcash\",\"card\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778212625,\"updated_at\":1778212625}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_success=1&schedule_id=1\",\"created_at\":1778212625,\"updated_at\":1778212625}}}', NULL, 'verified', NULL, '2026-05-08 03:57:18', '2026-05-08 03:57:03'),
(2, 1, 'inspection', 2, 1000.00, 'paymongo', 'cs_cce0daa68566d2db69a0db44', 'https://checkout.paymongo.com/cce0daa68566d2db69a0db44', '{\"data\":{\"id\":\"cs_cce0daa68566d2db69a0db44\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_cancelled=1&schedule_id=2\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/cce0daa68566d2db69a0db44\",\"client_key\":\"cs_cce0daa68566d2db69a0db44_client_581c3f5427248d1bdd7d8988\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Inspection Fee - halal keeps\",\"line_items\":[{\"amount\":100000,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Inspection Fee - halal keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"2\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_pMPcFJmuR36iTaDFEfRZxBxx\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":100000,\"capture_type\":\"automatic\",\"client_key\":\"pi_pMPcFJmuR36iTaDFEfRZxBxx_client_3gGApxuCvSBhakrZybU3yWDZ\",\"currency\":\"PHP\",\"description\":\"Inspection Fee - halal keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"2\"},\"next_action\":null,\"original_amount\":100000,\"payment_method_allowed\":[\"paymaya\",\"gcash\",\"card\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778212688,\"updated_at\":1778212688}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_success=1&schedule_id=2\",\"created_at\":1778212687,\"updated_at\":1778212687}}}', NULL, 'verified', NULL, '2026-05-08 03:58:20', '2026-05-08 03:58:06'),
(3, 1, 'inspection', 3, 1000.00, 'paymongo', 'cs_88cc1cdee7b793da7abd400a', 'https://checkout.paymongo.com/88cc1cdee7b793da7abd400a', '{\"data\":{\"id\":\"cs_88cc1cdee7b793da7abd400a\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_cancelled=1&schedule_id=3\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/88cc1cdee7b793da7abd400a\",\"client_key\":\"cs_88cc1cdee7b793da7abd400a_client_457207f65403c5088834765e\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Inspection Fee - Halal Keeps\",\"line_items\":[{\"amount\":100000,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Inspection Fee - Halal Keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"3\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_NKwCJ2aDTj1F2FFatR66yESt\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":100000,\"capture_type\":\"automatic\",\"client_key\":\"pi_NKwCJ2aDTj1F2FFatR66yESt_client_hAektDRgWQLWkxRXefLVeGJj\",\"currency\":\"PHP\",\"description\":\"Inspection Fee - Halal Keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"business_owner_id\":\"1\",\"reference_type\":\"inspection\",\"schedule_id\":\"3\"},\"next_action\":null,\"original_amount\":100000,\"payment_method_allowed\":[\"paymaya\",\"card\",\"gcash\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778220568,\"updated_at\":1778220568}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/inspection_schedules.php?paymongo_success=1&schedule_id=3\",\"created_at\":1778220567,\"updated_at\":1778220567}}}', NULL, 'verified', NULL, '2026-05-08 06:23:39', '2026-05-08 06:09:26'),
(4, 1, 'laboratory', 4, 16625.00, 'paymongo', NULL, NULL, '{\"data\":{\"id\":\"cs_78a9a9cce15a74719da72b13\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"\\/halal_final\\/dashboard\\/business_owner\\/pay_laboratory.php?id=4&status=cancelled\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/78a9a9cce15a74719da72b13\",\"client_key\":\"cs_78a9a9cce15a74719da72b13_client_52c1cdd3027b43d47ffb8d48\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"line_items\":[{\"amount\":1662500,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Laboratory Testing Fee - Halal Keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"4\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_PHjb9uFoAvjMb64zU8GCkU2d\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":1662500,\"capture_type\":\"automatic\",\"client_key\":\"pi_PHjb9uFoAvjMb64zU8GCkU2d_client_gghY2nEmCxCLmLYV88VC8S4W\",\"currency\":\"PHP\",\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"4\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"next_action\":null,\"original_amount\":1662500,\"payment_method_allowed\":[\"card\",\"gcash\",\"paymaya\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778670821,\"updated_at\":1778670821}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"\\/halal_final\\/dashboard\\/business_owner\\/laboratory_payment_status.php?id=4&status=success\",\"created_at\":1778670821,\"updated_at\":1778670821}}}', NULL, 'pending', NULL, NULL, '2026-05-13 11:13:41'),
(5, 1, 'laboratory', 5, 16625.00, 'paymongo', 'cs_a7036fb2580988ac15e04f65', 'https://checkout.paymongo.com/a7036fb2580988ac15e04f65', '{\"data\":{\"id\":\"cs_a7036fb2580988ac15e04f65\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/laboratory_payment_status.php?id=5&status=cancelled\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/a7036fb2580988ac15e04f65\",\"client_key\":\"cs_a7036fb2580988ac15e04f65_client_c6313537ffb20a7ddc4ae126\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"line_items\":[{\"amount\":1662500,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Laboratory Testing Fee - Halal Keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"5\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_SMvgaAzsGBM27Xi75dXsQGXz\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":1662500,\"capture_type\":\"automatic\",\"client_key\":\"pi_SMvgaAzsGBM27Xi75dXsQGXz_client_NAkv378Nqt6j1yEK8NhVBZEV\",\"currency\":\"PHP\",\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"5\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"next_action\":null,\"original_amount\":1662500,\"payment_method_allowed\":[\"card\",\"paymaya\",\"gcash\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778671197,\"updated_at\":1778671197}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/laboratory_payment_status.php?id=5&status=success\",\"created_at\":1778671197,\"updated_at\":1778671197}}}', NULL, 'pending', NULL, NULL, '2026-05-13 11:19:57'),
(6, 1, 'laboratory', 6, 16625.00, 'paymongo', 'cs_e89c20ecab478dea9522f885', 'https://checkout.paymongo.com/e89c20ecab478dea9522f885', '{\"data\":{\"id\":\"cs_e89c20ecab478dea9522f885\",\"type\":\"checkout_session\",\"attributes\":{\"billing\":{\"address\":{\"city\":null,\"country\":null,\"line1\":null,\"line2\":null,\"postal_code\":null,\"state\":null},\"email\":\"jasmine123@gmail.com\",\"name\":\"Jasmine Duran\",\"phone\":null},\"billing_information_fields_editable\":\"enabled\",\"cancel_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/laboratory_payment_status.php?id=6&status=cancelled\",\"checkout_url\":\"https:\\/\\/checkout.paymongo.com\\/e89c20ecab478dea9522f885\",\"client_key\":\"cs_e89c20ecab478dea9522f885_client_fa42e7074573b8bcc93fd21e\",\"collection\":{\"customer_info\":{\"email\":{\"state\":\"auto\"},\"name\":{\"state\":\"auto\"},\"mobile_phone\":{\"state\":\"auto\"},\"address\":{\"state\":\"auto\"}}},\"customer_email\":null,\"customer_id\":null,\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"line_items\":[{\"amount\":1662500,\"currency\":\"PHP\",\"description\":null,\"images\":[],\"name\":\"Laboratory Testing Fee - Halal Keeps\",\"quantity\":1}],\"livemode\":false,\"merchant\":\"Halal Keeps\",\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"6\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"organization_id\":\"org_xUMxuFWNUuhoQ6jrT8X9aruu\",\"pass_on_fees\":false,\"payment_intent\":{\"id\":\"pi_2TNFQvyL3P9CR3g2UJe8uEJD\",\"type\":\"payment_intent\",\"attributes\":{\"amount\":1662500,\"capture_type\":\"automatic\",\"client_key\":\"pi_2TNFQvyL3P9CR3g2UJe8uEJD_client_DDxs44WLjY1vZWCh9kp1AMDN\",\"currency\":\"PHP\",\"description\":\"Laboratory Testing Fee - Halal Keeps\",\"last_payment_error\":null,\"livemode\":false,\"metadata\":{\"company_name\":\"Halal Keeps\",\"request_id\":\"6\",\"type\":\"laboratory_testing\",\"user_id\":\"1\"},\"next_action\":null,\"original_amount\":1662500,\"payment_method_allowed\":[\"gcash\",\"paymaya\",\"card\"],\"payment_method_options\":{\"card\":{\"request_three_d_secure\":\"any\"}},\"payments\":[],\"setup_future_usage\":null,\"statement_descriptor\":\"Halal Keeps\",\"status\":\"awaiting_payment_method\",\"created_at\":1778671292,\"updated_at\":1778671292}},\"payment_method_types\":[\"card\",\"gcash\",\"paymaya\"],\"payments\":[],\"public_key\":\"pk_test_C5zKbzLsJ8VrotGgrZWzByad\",\"reference_number\":null,\"send_email_receipt\":true,\"show_description\":true,\"show_line_items\":true,\"status\":\"active\",\"success_url\":\"http:\\/\\/localhost\\/halal_final\\/dashboard\\/business_owner\\/laboratory_payment_status.php?id=6&status=success\",\"created_at\":1778671292,\"updated_at\":1778671292}}}', NULL, 'verified', NULL, '2026-05-13 05:24:58', '2026-05-13 11:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `potential_decisions`
--

CREATE TABLE `potential_decisions` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `corrective_action_id` int(11) DEFAULT NULL,
  `committee_member_id` int(11) NOT NULL,
  `evidence_summary` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `decision` enum('recommend_approve','recommend_reject','need_more_info','pending') DEFAULT 'pending',
  `notify_payment` tinyint(1) DEFAULT 0,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `food_rating` int(11) DEFAULT NULL CHECK (`food_rating` >= 1 and `food_rating` <= 5),
  `service_rating` int(11) DEFAULT NULL CHECK (`service_rating` >= 1 and `service_rating` <= 5),
  `comment` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `response_by` int(11) DEFAULT NULL,
  `response_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_category` enum('customer','business','hcb_employee','lab_employee') NOT NULL,
  `description` text DEFAULT NULL,
  `requires_approval` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_category`, `description`, `requires_approval`, `created_at`) VALUES
(1, 'Customer', 'customer', 'End customer who orders food from halal-certified restaurants', 0, '2026-04-28 06:20:28'),
(2, 'Business Owner', 'business', 'Halal business owner applying for halal certification', 1, '2026-04-28 06:20:28'),
(3, 'Evaluator', 'hcb_employee', 'Evaluates and verifies applications and creates inspection schedules', 1, '2026-04-28 06:20:28'),
(4, 'Auditor - Technical', 'hcb_employee', 'Conducts technical audits and inspections', 1, '2026-04-28 06:20:28'),
(5, 'Auditor - Shariah', 'hcb_employee', 'Conducts Shariah compliance audits and inspections', 1, '2026-04-28 06:20:28'),
(6, 'Impartial Committee', 'hcb_employee', 'Reviews potential decisions and evidence for certification', 1, '2026-04-28 06:20:28'),
(7, 'Decision Committee', 'hcb_employee', 'Makes final decisions on halal certification grants', 1, '2026-04-28 06:20:28'),
(8, 'President', 'hcb_employee', 'Awards halal certificates and logos to certified businesses', 1, '2026-04-28 06:20:28'),
(9, 'Receiving Officer', 'lab_employee', 'Receives and manages laboratory analysis requirements', 1, '2026-04-28 06:20:28'),
(10, 'Laboratory Analyst', 'lab_employee', 'Conducts laboratory analysis on submitted samples', 1, '2026-04-28 06:20:28'),
(11, 'Admin', 'customer', NULL, 1, '2026-05-08 03:40:36');

-- --------------------------------------------------------

--
-- Table structure for table `role_applications`
--

CREATE TABLE `role_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `application_letter` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `credentials_path` varchar(500) DEFAULT NULL,
  `additional_docs` text DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Halal Institute of Development Philippines', 'text', 'System name', '2026-04-28 06:20:29'),
(2, 'site_tagline', 'Halal Certification Body', 'text', 'Site tagline', '2026-04-28 06:20:29'),
(3, 'certification_validity_years', '2', 'text', 'Number of years a halal certificate is valid', '2026-04-28 06:20:29'),
(4, 'inspection_base_fee', '5000.00', 'text', 'Base inspection fee in PHP', '2026-04-28 06:20:29'),
(5, 'laboratory_base_fee', '3000.00', 'text', 'Base laboratory analysis fee in PHP', '2026-04-28 06:20:29'),
(6, 'certification_fee', '10000.00', 'text', 'Halal certification fee in PHP', '2026-04-28 06:20:29'),
(7, 'google_client_id', '', 'text', 'Google OAuth Client ID', '2026-04-28 06:20:29'),
(8, 'google_client_secret', '', 'text', 'Google OAuth Client Secret', '2026-04-28 06:20:29'),
(9, 'session_timeout_minutes', '30', 'text', 'Global session timeout in minutes for DLP policy', '2026-05-08 03:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `terms_of_reference`
--

CREATE TABLE `terms_of_reference` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `tor_content` text NOT NULL,
  `tor_file_path` varchar(500) DEFAULT NULL,
  `enterprise_type` enum('micro','small','medium') DEFAULT NULL,
  `auditor_assignments` longtext DEFAULT NULL,
  `auditor_total_amount` decimal(10,2) DEFAULT 0.00,
  `registration_agreement` text DEFAULT NULL,
  `payment_for_inspection` decimal(10,2) DEFAULT 0.00,
  `inspection_date` date DEFAULT NULL,
  `inspection_fees` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected') DEFAULT 'draft',
  `business_response` enum('pending','accepted','rejected') DEFAULT 'pending',
  `business_remarks` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_of_reference`
--

INSERT INTO `terms_of_reference` (`id`, `application_id`, `evaluator_id`, `tor_content`, `tor_file_path`, `enterprise_type`, `auditor_assignments`, `auditor_total_amount`, `registration_agreement`, `payment_for_inspection`, `inspection_date`, `inspection_fees`, `status`, `business_response`, `business_remarks`, `responded_at`, `created_at`, `updated_at`) VALUES
(3, 4, 2, 'TERMS OF REFERENCE\r\nIncluding particular conditions for the assessment of management systems by Halal Development Institute and Development, herein after termed \"HDIP\", with its contracting partners, hereinafter termed \"client\" or \"clients\".\r\n\r\n1. Scope\r\n1.1 These conditions apply to contracts agreed between HDIP and its clients, unless it is otherwise agreed in writing or so prescribed by statutory instruments.\r\n1.2 In the following text, audits and assessments are referred to as \"assessments\", Auditors, assessors and Technical Experts are referred to as \"assessors\" and reports on audits and assessments are referred to as \"assessment reports\".\r\n\r\n2. Assessment of Halal Management System, Products and Processes\r\n2.1 HDIP assesses the processes, products and management system of its client, or parts thereof, with the goal of determining its conformity with Islamic rites and specified requirements, including the effectiveness of the system. The client receives an audit report and a HDIP certificate or confirmation.\r\n2.2 HDIP is independent, neutral and objective in its assessments. Assessments are performed at the client\'s place of operations. The type, extent and time schedule of the procedure are subject to separate agreement by the parties. If nonconformities with Islamic rites and the requirements of the respective specification are identified during an assessment, the corrective actions must demonstrably be carried out by the client within the time frame specified in the reference document or by an appropriate agreed deadline, before a HDIP certificate can be issued. HDIP strives to minimize any disturbances of the business process while conducting the assessment on the client\'s premises.\r\n2.3 Porcine and its derivatives may not be used in the facility that produces Halal products.\r\n2.4 Where Ovine, Bovine, Caprine, Cervine and Avian slaughtering and processing takes place:\r\n2.4.1 The correct number of Muslim delegates shall be maintained in accordance with HDIP requirements and importing country requirements such as GSO 993, MS1500:2011\r\n2.4.2 Slaughter and stunning procedures must be adhered to as per GSO 993, MS1500:2011\r\n2.4.3 Stunning methods (such as electric shock and gassing) are not acceptable in the GSO 993 for poultry and therefore processed poultry used in goods cannot be accepted into the Gulf Countries.\r\n2.4.4 Captive Bolt as a stunning method is not acceptable as Halal for any animal\r\n2.5 All equipment, machinery, utensils, stoves, receptacles, benches and ovens used for Halal goods preparation shall be cleaned prior to use under the overall supervision of the Site Manager or their appointee.\r\n2.6 Storage and preparation areas reserved for the preparation of Halal goods shall be segregated.\r\n2.7 Storage, preparation, heating and/or cooking of Halal goods shall be carried out under the overall supervision of the Site Manager or their appointee.\r\n2.8 Halal and non-Halal goods shall not be prepared, mixed, cooked or heated in/on the same equipment at the same time.\r\n2.9 All raw, frozen, dried, processed and prepared ingredients required for the preparation of Halal goods, shall be acquired from suppliers approved by HDIP and kept segregated in storage from non-acceptable ingredients.\r\n2.10 Any product, ingredient or ready-made goods not approved by HDIP shall not be used in the preparation of Halal goods.\r\n2.11 The HDIP requirements for specific industry types are adhered to as per importing country standards such as GSO 993, GSO 2055, MS1500.\r\n2.12 Abide by Food Safety requirements set out in importing country requirements i.e. GSO 993, GSO 2055, MS1500 and other country standards.\r\n2.13 Cosmetics, Personal Care and Pharmaceuticals under the standards of Therapeutics Goods Administration shall adhere to Importing country Food and Drug Authorities such as GSO2055.\r\n\r\n3. Certification Cycles\r\n3.1 The Halal Certification period is 3 years subject to annual surveillance audits in Year 1 and Year 2 and recertification in year 3.\r\n3.2 Certificates shall be subject to update after each surveillance audit.\r\n3.3 HDIP has a certification period starting from 1st January 2017 to 31 December 2019 and every 3 year the certification periods shall be ongoing.\r\n\r\n4. Audits\r\n4.1.1 The number and choice of auditors is incumbent upon HDIP, who will nominate the auditors (Sharia and Technical).\r\n4.1.2 HDIP commits itself to use only auditors who are suitable for the task on the basis of their understanding of Islamic rites and technical qualifications, their experience and their personal abilities.\r\n4.2.1 Clients will be advised of scheduled audits via email and shall be confirmed or rescheduled by return email.\r\n4.2.2 Should the client not respond, the audit shall be assumed as confirmed.\r\n4.2.3 Cancelling or rescheduling the audit within fourteen (14) days of the scheduled audit will cause the cost of the audit stated in the Audit Plan as a cancellation fee.\r\n\r\n5. Use of HDIP Trademarks and Certificates\r\n5.1 The use of the HDIP trademark is governed by HDIP, protected by Intellectual property laws, and requires HDIP approval.\r\n\r\n6. Breaches of Certification\r\n6.1.1 HDIP may only issue certificates if all Halal requirements have been fulfilled following the audit.\r\n6.2.1 HDIP is entitled to suspend a certificate for a limited period of time if the client demonstrably violates Islamic rites, halal rules and contractual or financial obligations towards HDIP.\r\n\r\n7. Appeals and Complaints\r\n7.1 Every client has the right to have services performed within the agreed scope. In case of a difference of opinion, each client has the right to submit an appeal or a complaint via email hdiphilippines@gmail.com or phone +63 917 980 6317.\r\n\r\n8. Arbitration\r\n8.1 In the event of failure to resolve a major complaint, an independent arbitration may be deployed.\r\n\r\n9. Jurisdiction and Applicable Laws\r\n9.1 Court of jurisdiction is New South Wales and the NSW law applies in all respects.\r\n\r\n10. Diverging Agreements\r\n10.1 Diverging or supplementary agreements have to be made in writing.\r\n\r\n11. Additional Conditions\r\n11.1 Specific requirements of individual standards or specifications obtain in their current versions.\r\n11.2 Clients shall be given one calendar months\' notice of any changes to the Terms & Conditions.\r\n\r\n12. Management of Impartiality\r\n12.1 HDIP management is committed to maintenance of impartiality throughout the certification process. Certification decisions shall be based on objective evidence of conformity obtained during audits and shall not be influenced by any other interests or by other parties.', '', 'micro', '[{\"role\":\"shariah\",\"auditor_id\":6,\"auditor_name\":\"jamie deporos\",\"amount\":500},{\"role\":\"technical\",\"auditor_id\":5,\"auditor_name\":\"jams duran\",\"amount\":500}]', 1000.00, NULL, 1000.00, NULL, 1000.00, 'sent', 'accepted', '', '2026-05-08 06:09:21', '2026-05-08 06:08:36', '2026-05-08 06:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `role_status` enum('pending','approved','rejected','none') DEFAULT 'none',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `failed_login_attempts` int(11) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `email`, `full_name`, `first_name`, `last_name`, `avatar`, `password`, `phone`, `address`, `role_id`, `role_status`, `is_active`, `email_verified`, `last_login`, `created_at`, `updated_at`, `failed_login_attempts`, `account_locked`) VALUES
(1, NULL, 'jasmine123@gmail.com', 'Jasmine Duran', 'Jasmine', 'Duran', NULL, '$2y$10$hqMRO.jHHLvzxkxty0xzmOVByCM/drGsA/skx5ufSh2nsL2P7ZQx.', '09054541332', 'PUROK 12, SIRAWAN, TORIL, DAVAO CITY', 2, 'approved', 1, 1, '2026-05-13 11:16:10', '2026-04-28 06:42:26', '2026-05-13 11:16:10', 0, 0),
(2, NULL, 'jamieduran@gmail.com', 'Jamie Duran', 'Jamie', 'Duran', NULL, '$2y$10$gvyjSS1iUxwsE624.DMKB.cjM/HEj69firjeykDCl/ixtDpJQtXmO', '0912345678', 'Toril, Davao City', 3, 'approved', 1, 1, '2026-05-08 07:59:24', '2026-04-28 07:17:11', '2026-05-08 07:59:24', 0, 0),
(3, NULL, 'jasminedeporosduran@gmail.com', 'Jams Duran', 'Jams', 'Duran', NULL, '$2y$10$cn/wek.e8OwpcUe1j3kS.eaDlzSN1pbdU.b736flS4cizg.edD6si', '09054541332', NULL, NULL, 'none', 1, 1, '2026-04-28 11:35:27', '2026-04-28 11:35:12', '2026-04-28 11:35:27', 0, 0),
(5, NULL, 'jamsduran@gmail.com', 'jams duran', 'jams', 'duran', NULL, '$2y$10$/vO.ZmQm3gi0UojEC4XZfO6HMiRSmiwur0/jCyKdDnmZOmlyxcrJC', '09054541332', NULL, 4, 'approved', 1, 1, '2026-05-08 08:00:15', '2026-05-08 03:22:21', '2026-05-08 08:00:15', 0, 0),
(6, NULL, 'jamiedeporos@gmail.com', 'jamie deporos', 'jamie', 'deporos', NULL, '$2y$10$RBlRJ20KaqFwKcLtEHi5UuFA3NGXGc4W1EcmC//ZfX8r9xBJSzTm2', '09054541332', NULL, 5, 'approved', 1, 1, '2026-05-13 10:47:14', '2026-05-08 03:27:07', '2026-05-13 10:47:14', 0, 0),
(8, NULL, 'admin@halal.com', 'System Admin', NULL, NULL, NULL, '$2y$10$1bDH5ydoTsgw9ofIfxh6t.Jbe/WrwkyHn6em.sZRnPCKh09JWmkBq', NULL, NULL, 11, 'approved', 1, 0, '2026-05-08 06:01:49', '2026-05-08 03:40:36', '2026-05-08 06:01:49', 0, 0),
(11, NULL, 'jayduran@gmail.com', 'Jay Duran', 'Jay', 'Duran', NULL, '$2y$10$4P6A6E.HCSufi.I9hkGhMe5nzGDny9cn4zpx75kCQi1f.k8c8i4e.', '09054541332', NULL, 9, 'approved', 1, 1, '2026-05-13 11:15:46', '2026-05-13 06:48:57', '2026-05-13 11:15:46', 0, 0),
(12, NULL, 'josephclamucha@gmail.com', 'joseph clamucha', 'joseph', 'clamucha', NULL, '$2y$10$IlayDeP0AqRi51S39rCg0OGMckJjRupXDG3h652a/CL8ZWIoar6ga', '09054541332', NULL, 10, 'approved', 1, 1, '2026-05-13 10:59:46', '2026-05-13 10:59:14', '2026-05-13 11:00:00', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `application_requirement_reviews`
--
ALTER TABLE `application_requirement_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `application_requirement_uploads`
--
ALTER TABLE `application_requirement_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_verification`
--
ALTER TABLE `application_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `corrective_action_reports`
--
ALTER TABLE `corrective_action_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `laboratory_report_id` (`laboratory_report_id`),
  ADD KEY `inspection_id` (`inspection_id`),
  ADD KEY `prepared_by` (`prepared_by`);

--
-- Indexes for table `final_decisions`
--
ALTER TABLE `final_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `potential_decision_id` (`potential_decision_id`),
  ADD KEY `committee_member_id` (`committee_member_id`);

--
-- Indexes for table `halal_certificates`
--
ALTER TABLE `halal_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `final_decision_id` (`final_decision_id`),
  ADD KEY `decorated_by` (`decorated_by`),
  ADD KEY `awarded_by` (`awarded_by`);

--
-- Indexes for table `halal_restaurants`
--
ALTER TABLE `halal_restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_owner_id` (`business_owner_id`),
  ADD KEY `certificate_id` (`certificate_id`);

--
-- Indexes for table `hdp_applications`
--
ALTER TABLE `hdp_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loi_id` (`loi_id`),
  ADD KEY `idx_hdp_owner` (`business_owner_id`),
  ADD KEY `idx_hdp_status` (`status`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `auditor_technical_id` (`auditor_technical_id`),
  ADD KEY `auditor_shariah_id` (`auditor_shariah_id`),
  ADD KEY `idx_inspections_schedule` (`schedule_id`);

--
-- Indexes for table `inspection_document_conformity`
--
ALTER TABLE `inspection_document_conformity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_inspection_auditor_checklist` (`inspection_id`,`auditor_id`,`checklist_key`),
  ADD KEY `idx_idc_application` (`application_id`),
  ADD KEY `idx_idc_upload` (`uploaded_file_id`);

--
-- Indexes for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `tor_id` (`tor_id`);

--
-- Indexes for table `laboratory_analyses`
--
ALTER TABLE `laboratory_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `analyst_id` (`analyst_id`);

--
-- Indexes for table `laboratory_reports`
--
ALTER TABLE `laboratory_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_number` (`report_number`),
  ADD KEY `laboratory_request_id` (`laboratory_request_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `analyst_id` (`analyst_id`);

--
-- Indexes for table `laboratory_requests`
--
ALTER TABLE `laboratory_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `business_owner_id` (`business_owner_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `analyst_id` (`analyst_id`);

--
-- Indexes for table `letter_of_intent`
--
ALTER TABLE `letter_of_intent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loi_owner` (`business_owner_id`),
  ADD KEY `idx_loi_status` (`status`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loi_requirements`
--
ALTER TABLE `loi_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loi_id` (`loi_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `loi_verification`
--
ALTER TABLE `loi_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loi_id` (`loi_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `ncr_reports`
--
ALTER TABLE `ncr_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ncr_number` (`ncr_number`),
  ADD KEY `inspection_id` (`inspection_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_restaurant` (`restaurant_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `potential_decisions`
--
ALTER TABLE `potential_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `corrective_action_id` (`corrective_action_id`),
  ADD KEY `committee_member_id` (`committee_member_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `response_by` (`response_by`),
  ADD KEY `idx_reviews_restaurant` (`restaurant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_applications`
--
ALTER TABLE `role_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `terms_of_reference`
--
ALTER TABLE `terms_of_reference`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_users_google_id` (`google_id`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_requirement_reviews`
--
ALTER TABLE `application_requirement_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `application_requirement_uploads`
--
ALTER TABLE `application_requirement_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `application_verification`
--
ALTER TABLE `application_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `corrective_action_reports`
--
ALTER TABLE `corrective_action_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_decisions`
--
ALTER TABLE `final_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `halal_certificates`
--
ALTER TABLE `halal_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `halal_restaurants`
--
ALTER TABLE `halal_restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hdp_applications`
--
ALTER TABLE `hdp_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inspection_document_conformity`
--
ALTER TABLE `inspection_document_conformity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `laboratory_analyses`
--
ALTER TABLE `laboratory_analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laboratory_reports`
--
ALTER TABLE `laboratory_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `laboratory_requests`
--
ALTER TABLE `laboratory_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `letter_of_intent`
--
ALTER TABLE `letter_of_intent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `loi_requirements`
--
ALTER TABLE `loi_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `loi_verification`
--
ALTER TABLE `loi_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ncr_reports`
--
ALTER TABLE `ncr_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `potential_decisions`
--
ALTER TABLE `potential_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `role_applications`
--
ALTER TABLE `role_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `terms_of_reference`
--
ALTER TABLE `terms_of_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_requirement_uploads`
--
ALTER TABLE `application_requirement_uploads`
  ADD CONSTRAINT `application_requirement_uploads_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_verification`
--
ALTER TABLE `application_verification`
  ADD CONSTRAINT `application_verification_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_verification_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `corrective_action_reports`
--
ALTER TABLE `corrective_action_reports`
  ADD CONSTRAINT `corrective_action_reports_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `corrective_action_reports_ibfk_2` FOREIGN KEY (`laboratory_report_id`) REFERENCES `laboratory_reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `corrective_action_reports_ibfk_3` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `corrective_action_reports_ibfk_4` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `final_decisions`
--
ALTER TABLE `final_decisions`
  ADD CONSTRAINT `final_decisions_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_decisions_ibfk_2` FOREIGN KEY (`potential_decision_id`) REFERENCES `potential_decisions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `final_decisions_ibfk_3` FOREIGN KEY (`committee_member_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `halal_certificates`
--
ALTER TABLE `halal_certificates`
  ADD CONSTRAINT `halal_certificates_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `halal_certificates_ibfk_2` FOREIGN KEY (`final_decision_id`) REFERENCES `final_decisions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `halal_certificates_ibfk_3` FOREIGN KEY (`decorated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `halal_certificates_ibfk_4` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `halal_restaurants`
--
ALTER TABLE `halal_restaurants`
  ADD CONSTRAINT `halal_restaurants_ibfk_1` FOREIGN KEY (`business_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `halal_restaurants_ibfk_2` FOREIGN KEY (`certificate_id`) REFERENCES `halal_certificates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hdp_applications`
--
ALTER TABLE `hdp_applications`
  ADD CONSTRAINT `hdp_applications_ibfk_1` FOREIGN KEY (`loi_id`) REFERENCES `letter_of_intent` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hdp_applications_ibfk_2` FOREIGN KEY (`business_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `inspection_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_3` FOREIGN KEY (`auditor_technical_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inspections_ibfk_4` FOREIGN KEY (`auditor_shariah_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  ADD CONSTRAINT `inspection_schedules_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspection_schedules_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspection_schedules_ibfk_3` FOREIGN KEY (`tor_id`) REFERENCES `terms_of_reference` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `laboratory_analyses`
--
ALTER TABLE `laboratory_analyses`
  ADD CONSTRAINT `laboratory_analyses_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laboratory_analyses_ibfk_2` FOREIGN KEY (`analyst_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laboratory_reports`
--
ALTER TABLE `laboratory_reports`
  ADD CONSTRAINT `laboratory_reports_ibfk_1` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `laboratory_reports_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laboratory_reports_ibfk_3` FOREIGN KEY (`analyst_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laboratory_requests`
--
ALTER TABLE `laboratory_requests`
  ADD CONSTRAINT `laboratory_requests_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laboratory_requests_ibfk_2` FOREIGN KEY (`business_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laboratory_requests_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `laboratory_requests_ibfk_4` FOREIGN KEY (`analyst_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `letter_of_intent`
--
ALTER TABLE `letter_of_intent`
  ADD CONSTRAINT `letter_of_intent_ibfk_1` FOREIGN KEY (`business_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loi_requirements`
--
ALTER TABLE `loi_requirements`
  ADD CONSTRAINT `loi_requirements_ibfk_1` FOREIGN KEY (`loi_id`) REFERENCES `letter_of_intent` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loi_requirements_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loi_verification`
--
ALTER TABLE `loi_verification`
  ADD CONSTRAINT `loi_verification_ibfk_1` FOREIGN KEY (`loi_id`) REFERENCES `letter_of_intent` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loi_verification_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `halal_restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ncr_reports`
--
ALTER TABLE `ncr_reports`
  ADD CONSTRAINT `ncr_reports_ibfk_1` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ncr_reports_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `halal_restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `potential_decisions`
--
ALTER TABLE `potential_decisions`
  ADD CONSTRAINT `potential_decisions_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `potential_decisions_ibfk_2` FOREIGN KEY (`corrective_action_id`) REFERENCES `corrective_action_reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `potential_decisions_ibfk_3` FOREIGN KEY (`committee_member_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `halal_restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`response_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_applications`
--
ALTER TABLE `role_applications`
  ADD CONSTRAINT `role_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_applications_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_applications_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `terms_of_reference`
--
ALTER TABLE `terms_of_reference`
  ADD CONSTRAINT `terms_of_reference_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `hdp_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `terms_of_reference_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
