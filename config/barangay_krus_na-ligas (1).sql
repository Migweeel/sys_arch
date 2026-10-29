-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2026 at 09:55 PM
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
-- Database: `barangay_krus_na-ligas`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(100) NOT NULL DEFAULT '',
  `role` varchar(50) NOT NULL DEFAULT 'resident',
  `action` varchar(50) NOT NULL DEFAULT 'activity',
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `username`, `role`, `action`, `description`, `created_at`) VALUES
(1, 7, 'admin', 'admin', 'login', 'User logged in to the system', '2026-07-29 21:22:09'),
(2, 8, 'tenderfoot1', 'resident', 'login', 'User logged in to the system', '2026-07-29 21:38:29'),
(3, 7, 'admin', 'admin', 'login', 'User logged in to the system', '2026-07-29 21:39:35'),
(4, 8, 'tenderfoot1', 'resident', 'login', 'User logged in to the system', '2026-07-29 21:51:01'),
(5, 7, 'admin', 'admin', 'login', 'User logged in to the system', '2026-07-29 21:52:41'),
(6, 7, 'admin', 'admin', 'user_created', 'Admin created a STAFF account for mig1259.', '2026-07-29 22:23:36'),
(7, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-29 22:23:51'),
(8, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-29 22:31:48'),
(9, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-29 22:32:22'),
(10, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-29 22:46:01'),
(11, 9, 'mig1259', 'staff', 'template_created', 'Staff created document template: asdasdas.', '2026-07-29 22:59:22'),
(12, 9, 'mig1259', 'staff', 'template_created', 'Staff created document template: dasdsadas.', '2026-07-29 23:12:47'),
(13, 9, 'mig1259', 'staff', 'template_deleted', 'Staff deleted a document template.', '2026-07-29 23:18:48'),
(14, 9, 'mig1259', 'staff', 'template_deleted', 'Staff deleted a document template.', '2026-07-29 23:18:52'),
(15, 9, 'mig1259', 'staff', 'template_created', 'Staff created document template: asdasda.', '2026-07-29 23:27:54'),
(16, 9, 'mig1259', 'staff', 'template_deleted', 'Staff deleted a document template.', '2026-07-29 23:28:06'),
(17, 7, 'admin', 'admin', 'login', 'User logged in to the system', '2026-07-29 23:57:08'),
(18, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-29 23:57:41'),
(19, 9, 'mig1259', 'staff', 'template_created', 'Staff created document template: asdasd.', '2026-07-29 23:58:01'),
(20, 9, 'mig1259', 'staff', 'template_deleted', 'Staff deleted a document template.', '2026-07-29 23:58:04'),
(21, 9, 'mig1259', 'staff', 'template_created', 'Staff created document template: asdads.', '2026-07-30 00:00:16'),
(22, 9, 'mig1259', 'staff', 'template_deleted', 'Staff deleted a document template.', '2026-07-30 00:00:24'),
(23, 9, 'mig1259', 'staff', 'login', 'User logged in to the system', '2026-07-30 00:01:33'),
(24, 7, 'admin', 'admin', 'login', 'User logged in to the system', '2026-07-30 00:57:57'),
(25, 7, 'admin', 'admin', 'user_created', 'Admin created a ADMIN account for admin1.', '2026-07-30 01:04:22'),
(26, 7, 'admin', 'admin', 'user_created', 'Admin created a STAFF account for kenneth.', '2026-07-30 01:05:14'),
(27, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 01:05:44'),
(28, 0, 'nethhh', 'resident', 'registration_submitted', 'A new resident account was registered.', '2026-07-30 01:11:29'),
(29, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 01:11:37'),
(30, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 01:13:17'),
(31, 10, 'admin1', 'admin', 'verification_verified', 'User verification was verified.', '2026-07-30 01:14:55'),
(32, 10, 'admin1', 'admin', 'verification_verified', 'User verification was verified.', '2026-07-30 01:14:57'),
(33, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 01:15:23'),
(34, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 01:15:49'),
(35, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 01:16:12'),
(36, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 01:16:47'),
(37, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 01:17:25'),
(38, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 01:17:59'),
(39, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 13:55:02'),
(40, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 13:58:14'),
(41, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:16:08'),
(42, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:17:02'),
(43, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 14:17:41'),
(44, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:18:25'),
(45, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 14:23:46'),
(46, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:27:34'),
(47, 11, 'kenneth', 'staff', 'request_approved', 'REQ-2026-006 was approved by staff.', '2026-07-30 14:44:04'),
(48, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 14:44:32'),
(49, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 14:44:43'),
(50, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 14:44:57'),
(51, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:45:31'),
(52, 11, 'kenneth', 'staff', 'request_rejected', 'REQ-2026-007 was rejected by staff.', '2026-07-30 14:45:42'),
(53, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 14:46:40'),
(54, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 14:46:47'),
(55, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 14:47:12'),
(56, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 14:47:23'),
(57, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 14:47:36'),
(58, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 14:47:45'),
(59, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 14:47:54'),
(60, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 14:48:02'),
(61, 11, 'kenneth', 'staff', 'request_rejected', 'REQ-2026-012 was rejected by staff.', '2026-07-30 14:48:08'),
(62, 11, 'kenneth', 'staff', 'request_approved', 'REQ-2026-011 was approved by staff.', '2026-07-30 14:48:10'),
(63, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 14:48:24'),
(64, 10, 'admin1', 'admin', 'request_approved', 'REQ-2026-010 was approved by admin.', '2026-07-30 14:49:17'),
(65, 10, 'admin1', 'admin', 'request_rejected', 'REQ-2026-009 was rejected by admin.', '2026-07-30 14:49:42'),
(66, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 15:03:28'),
(67, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 15:03:51'),
(68, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 15:04:19'),
(69, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Residency.', '2026-07-30 15:04:49'),
(70, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 15:04:59'),
(71, 12, 'nethhh', 'resident', 'document_requested', 'Resident submitted a document request: Certificate of Indigency.', '2026-07-30 15:05:11'),
(72, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 15:05:21'),
(73, 11, 'kenneth', 'staff', 'request_rejected', 'REQ-2026-017 was rejected by staff.', '2026-07-30 15:05:33'),
(74, 11, 'kenneth', 'staff', 'request_approved', 'REQ-2026-016 was approved by staff.', '2026-07-30 15:05:41'),
(75, 11, 'kenneth', 'staff', 'request_released', 'REQ-2026-016 was released by staff.', '2026-07-30 15:06:01'),
(76, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 15:06:16'),
(77, 10, 'admin1', 'admin', 'request_rejected', 'REQ-2026-015 was rejected by admin.', '2026-07-30 15:07:04'),
(78, 10, 'admin1', 'admin', 'request_approved', 'REQ-2026-014 was approved by admin.', '2026-07-30 15:07:09'),
(79, 10, 'admin1', 'admin', 'request_released', 'REQ-2026-014 was released by admin.', '2026-07-30 15:07:12'),
(80, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:18:45'),
(81, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 21:27:01'),
(82, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:27:49'),
(83, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:33:21'),
(84, 12, 'nethhh', 'resident', 'login', 'User logged in to the system', '2026-07-30 21:34:23'),
(85, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 21:34:56'),
(86, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:35:30'),
(87, 0, 'tbag', 'resident', 'registration_submitted', 'A new resident account was registered.', '2026-07-30 21:40:56'),
(88, 13, 'tbag', 'resident', 'login', 'User logged in to the system', '2026-07-30 21:41:06'),
(89, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:41:18'),
(90, 10, 'admin1', 'admin', 'user_created', 'Admin created a ADMIN account for admin2.', '2026-07-30 21:48:54'),
(91, 10, 'admin1', 'admin', 'user_deleted', 'Admin deleted user account #7.', '2026-07-30 21:57:41'),
(92, 10, 'admin1', 'admin', 'user_deleted', 'Admin deleted user account #4.', '2026-07-30 21:57:45'),
(93, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 21:58:09'),
(94, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 21:58:35'),
(95, 11, 'kenneth', 'staff', 'login', 'User logged in to the system', '2026-07-30 22:05:39'),
(96, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 22:07:09'),
(97, 10, 'admin1', 'admin', 'user_updated', 'Admin updated user account #14.', '2026-07-30 22:29:08'),
(98, 10, 'admin1', 'admin', 'user_updated', 'Admin updated user account: Daniel Padilla (username: daniel, role: Staff).', '2026-07-30 22:44:27'),
(99, 10, 'admin1', 'admin', 'login', 'User logged in to the system', '2026-07-30 22:45:26'),
(100, 14, 'joshua', 'admin', 'login', 'User logged in to the system', '2026-07-30 22:45:45'),
(101, 14, 'joshua', 'admin', 'user_updated', 'Admin updated user account: Kenneth Tarroza (username: michael, role: Resident).', '2026-07-30 22:46:58'),
(102, 14, 'joshua', 'admin', 'user_updated', 'Admin updated user account: Kenneth Tarroza (username: michael, role: Resident).', '2026-07-30 22:47:19'),
(103, 12, 'michael', 'resident', 'login', 'User logged in to the system', '2026-07-30 22:49:38'),
(104, 14, 'joshua', 'admin', 'login', 'User logged in to the system', '2026-07-30 22:50:02'),
(105, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: Theodor Bagwell (username: tbag, role: Resident).', '2026-07-30 22:56:07'),
(106, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: Michael Scofield (username: michael, role: Resident).', '2026-07-30 22:56:10'),
(107, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: Daniel Padilla (username: daniel, role: Staff).', '2026-07-30 22:56:12'),
(108, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: admin1 (username: admin1, role: Admin).', '2026-07-30 22:56:14'),
(109, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: mig1259 (username: mig1259, role: Staff).', '2026-07-30 22:56:15'),
(110, 14, 'joshua', 'admin', 'user_deleted', 'Admin deleted user account: Miguel Rivera (username: tenderfoot1, role: Resident).', '2026-07-30 22:56:17'),
(111, 14, 'joshua', 'admin', 'request_rejected', 'Admin rejected document request: REQ-2026-013 (Certificate of Indigency).', '2026-07-30 22:56:43'),
(112, 14, 'joshua', 'admin', 'request_released', 'Admin released document request: REQ-2026-010 (Certificate of Residency).', '2026-07-30 22:56:51'),
(113, 14, 'joshua', 'admin', 'request_released', 'Admin released document request: REQ-2026-011 (Certificate of Residency).', '2026-07-30 22:56:52'),
(114, 14, 'joshua', 'admin', 'user_created', 'Admin created user account: Kenneth Tarroza (username: nethhh, role: Admin).', '2026-07-30 22:58:35'),
(115, 14, 'joshua', 'admin', 'user_created', 'Admin created user account: Michael Scofield (username: michael, role: Staff).', '2026-07-30 22:59:37'),
(116, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:00:48'),
(117, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-30 23:00:58'),
(118, 16, 'michael', 'staff', 'request_rejected', 'Staff rejected document request: REQ-2026-008 (Certificate of Indigency).', '2026-07-30 23:01:07'),
(119, 17, 'fernando', 'resident', 'registration_submitted', 'Resident registered account: Fernando Sucre (username: fernando, role: Resident). Pending admin verification.', '2026-07-30 23:02:22'),
(120, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:02:33'),
(121, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:02:59'),
(122, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:03:09'),
(123, 15, 'nethhh', 'admin', 'user_deleted', 'Admin deleted user account: Joshua Garcia (username: joshua, role: Admin).', '2026-07-30 23:03:19'),
(124, 15, 'nethhh', 'admin', 'verification_verified', 'Admin set verification status to verified for user account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:03:24'),
(125, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:03:49'),
(126, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-018 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:04:24'),
(127, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-019 (Certificate of Residency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:04:38'),
(128, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-020 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:04:53'),
(129, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-021 (Certificate of Residency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:05:04'),
(130, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-022 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:05:16'),
(131, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-023 (Certificate of Residency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:05:26'),
(132, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:05:43'),
(133, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-023 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:06:00'),
(134, 15, 'nethhh', 'admin', 'request_rejected', 'Admin rejected document request: REQ-2026-022 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:06:10'),
(135, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-004 (Certificate of Indigency).', '2026-07-30 23:06:28'),
(136, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-006 (Certificate of Indigency).', '2026-07-30 23:06:35'),
(137, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-021 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:06:57'),
(138, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-30 23:07:16'),
(139, 16, 'michael', 'staff', 'request_approved', 'Staff approved document request: REQ-2026-020 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:07:24'),
(140, 16, 'michael', 'staff', 'request_approved', 'Staff approved document request: REQ-2026-019 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:07:28'),
(141, 16, 'michael', 'staff', 'request_rejected', 'Staff rejected document request: REQ-2026-018 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:07:34'),
(142, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:07:45'),
(143, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:08:09'),
(144, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-024 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:08:35'),
(145, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-025 (Certificate of Residency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-30 23:08:44'),
(146, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:08:56'),
(147, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-019 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:09:04'),
(148, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-30 23:09:16'),
(149, 16, 'michael', 'staff', 'request_released', 'Staff released document request: REQ-2026-020 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-07-30 23:09:22'),
(150, 18, 'alexander', 'resident', 'registration_submitted', 'Resident registered account: Alexander Mahone (username: alexander, role: Resident). Pending admin verification.', '2026-07-30 23:11:07'),
(151, 19, 'lincoln', 'resident', 'registration_submitted', 'Resident registered account: Lincoln Burrows (username: lincoln, role: Resident). Pending admin verification.', '2026-07-30 23:12:15'),
(152, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:12:23'),
(153, 18, 'alexander', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:12:37'),
(154, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:12:50'),
(155, 15, 'nethhh', 'admin', 'verification_rejected', 'Admin set verification status to rejected for user account: Lincoln Burrows (username: lincoln, role: Resident). Reason: wfvdwvwe', '2026-07-30 23:13:13'),
(156, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:14:00'),
(157, 19, 'lincoln', 'resident', 'verification_resubmitted', 'Resident resubmitted verification proof for account: Lincoln Burrows (username: lincoln, role: Resident).', '2026-07-30 23:14:27'),
(158, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:14:40'),
(159, 15, 'nethhh', 'admin', 'verification_rejected', 'Admin set verification status to rejected for user account: Lincoln Burrows (username: lincoln, role: Resident). Reason: fewfewvbwe', '2026-07-30 23:15:02'),
(160, 15, 'nethhh', 'admin', 'verification_rejected', 'Admin set verification status to rejected for user account: Lincoln Burrows (username: lincoln, role: Resident). Reason: fewfewvbwe', '2026-07-30 23:15:04'),
(161, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:15:19'),
(162, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:18:17'),
(163, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:22:31'),
(164, 18, 'alexander', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:22:45'),
(165, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-30 23:23:00'),
(166, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:23:17'),
(167, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:23:49'),
(168, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:24:45'),
(169, 15, 'nethhh', 'admin', 'verification_rejected', 'Admin set verification status to rejected for user account: Lincoln Burrows (username: lincoln, role: Resident). Reason: wqfwevwev', '2026-07-30 23:24:54'),
(170, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-07-30 23:29:57'),
(171, 19, 'lincoln', 'resident', 'verification_resubmitted', 'Resident resubmitted verification proof for account: Lincoln Burrows (username: lincoln, role: Resident).', '2026-07-30 23:30:07'),
(172, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-30 23:30:19'),
(173, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-025 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-07-31 00:01:03'),
(174, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-31 00:01:23'),
(175, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-026 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-07-31 00:01:34'),
(176, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-31 00:01:43'),
(177, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-31 15:24:46'),
(178, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-31 15:38:35'),
(179, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-31 15:39:57'),
(180, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-31 15:58:40'),
(181, 0, 'Guest', 'resident', 'complaint_submitted', 'Complaint submitted: CMP-0001.', '2026-07-31 16:09:35'),
(182, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-07-31 16:09:50'),
(183, 16, 'michael', 'staff', 'complaint_done', 'Staff marked complaint CMP-0001 as done.', '2026-07-31 16:15:13'),
(184, 16, 'michael', 'staff', 'complaint_pending', 'Staff marked complaint CMP-0001 as pending.', '2026-07-31 16:15:14'),
(185, 16, 'michael', 'staff', 'complaint_done', 'Staff marked complaint CMP-0001 as done.', '2026-07-31 16:15:16'),
(186, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-07-31 16:16:12'),
(187, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-07-31 16:16:26'),
(188, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 20:42:06'),
(189, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 20:56:57'),
(190, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 21:00:39'),
(191, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 21:01:46'),
(192, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 21:02:14'),
(193, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-08-02 21:13:31'),
(194, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 21:16:11'),
(195, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 21:18:43'),
(196, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 21:18:52'),
(197, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-02 21:27:18'),
(198, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 21:27:26'),
(199, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 21:27:35'),
(200, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 21:27:41'),
(201, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 21:35:13'),
(202, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 21:35:46'),
(203, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-02 21:39:35'),
(204, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 21:42:21'),
(205, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 21:42:38'),
(206, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 21:42:47'),
(207, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 22:00:24'),
(208, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 22:00:41'),
(209, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 22:00:58'),
(210, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 22:01:05'),
(211, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 22:06:16'),
(212, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 22:06:23'),
(213, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 22:13:33'),
(214, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 22:13:44'),
(215, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-02 22:21:58'),
(216, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 22:22:03'),
(217, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 22:31:11'),
(218, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 22:31:52'),
(219, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 22:32:20'),
(220, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 22:32:27'),
(221, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 22:40:06'),
(222, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 22:40:22'),
(223, 15, 'nethhh', 'admin', 'verification_verified', 'Admin set verification status to verified for user account: Alexander Mahone (username: alexander, role: Resident).', '2026-08-02 22:40:32'),
(224, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 22:40:35'),
(225, 18, 'alexander', 'resident', 'login', 'User logged in to the system', '2026-08-02 22:40:43'),
(226, 18, 'alexander', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-027 (Certificate of Indigency) for account: Alexander Mahone (username: alexander, role: Resident).', '2026-08-02 22:40:52'),
(227, 18, 'alexander', 'resident', 'logout', 'User logged out of the system', '2026-08-02 22:41:03'),
(228, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-02 22:41:10'),
(229, 16, 'michael', 'staff', 'request_approved', 'Staff approved document request: REQ-2026-027 (Certificate of Indigency) for resident Alexander Mahone (username: alexander).', '2026-08-02 22:41:17'),
(230, 16, 'michael', 'staff', 'request_released', 'Staff released document request: REQ-2026-027 (Certificate of Indigency) for resident Alexander Mahone (username: alexander).', '2026-08-02 22:46:16'),
(231, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-02 22:46:18'),
(232, 18, 'alexander', 'resident', 'login', 'User logged in to the system', '2026-08-02 22:46:25'),
(233, 18, 'alexander', 'resident', 'logout', 'User logged out of the system', '2026-08-02 22:46:51'),
(234, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-02 22:47:38'),
(235, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-02 22:57:00'),
(236, 18, 'alexander', 'resident', 'login', 'User logged in to the system', '2026-08-02 22:57:06'),
(237, 18, 'alexander', 'resident', 'logout', 'User logged out of the system', '2026-08-02 23:01:44'),
(238, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-02 23:01:53'),
(239, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-02 23:02:45'),
(240, 19, 'lincoln', 'resident', 'login', 'User logged in to the system', '2026-08-19 21:46:27'),
(241, 19, 'lincoln', 'resident', 'logout', 'User logged out of the system', '2026-08-19 21:47:45'),
(242, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-19 21:48:02'),
(243, 16, 'michael', 'staff', 'certificate_printed', 'Staff printed sample certificate preview for REQ-2026-025 (Certificate of Residency) for Fernando Sucre.', '2026-08-19 21:48:37'),
(244, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-19 21:49:38'),
(245, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-19 21:49:57'),
(246, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-19 21:52:43'),
(247, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-23 22:21:35'),
(248, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-23 22:22:18'),
(249, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-23 22:22:42'),
(250, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-23 22:23:24'),
(251, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 15:05:35'),
(252, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 15:05:46'),
(253, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 15:05:52'),
(254, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 15:05:59'),
(255, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 15:06:15'),
(256, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 15:23:01'),
(257, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 15:24:09'),
(258, 15, 'nethhh', 'admin', 'request_printed', 'Admin printed document request: REQ-2026-025 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-08-24 15:37:19'),
(259, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 15:37:36'),
(260, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 15:39:26'),
(261, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 15:46:12'),
(262, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 15:46:21'),
(263, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 15:47:14'),
(264, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 15:47:21'),
(265, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 15:47:46'),
(266, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 15:50:46'),
(267, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 15:52:59'),
(268, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 15:53:06'),
(269, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260824_095308.zip.', '2026-08-24 15:53:11'),
(270, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 16:19:02'),
(271, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 16:19:11'),
(272, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 16:22:59'),
(273, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 16:23:08'),
(274, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-028 (Certificate of Residency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-08-24 16:23:25'),
(275, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 16:23:28'),
(276, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 16:23:35'),
(277, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 16:27:19'),
(278, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 16:27:25'),
(279, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 16:28:08'),
(280, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 16:28:14'),
(281, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 16:41:15'),
(282, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 16:41:27'),
(283, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 16:54:02'),
(284, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 16:54:08'),
(285, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 16:57:43'),
(286, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 16:57:50'),
(287, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 16:58:13'),
(288, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 16:58:18'),
(289, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 17:08:45'),
(290, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 17:08:55'),
(291, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 17:36:44'),
(292, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 17:36:51'),
(293, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 17:54:13'),
(294, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 17:54:21'),
(295, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 17:56:38'),
(296, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 17:56:57'),
(297, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 17:59:01'),
(298, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 17:59:16'),
(299, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 18:00:56'),
(300, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-24 18:20:34'),
(301, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-24 18:30:29'),
(302, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 18:30:38'),
(303, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 18:53:51'),
(304, 16, 'michael', 'staff', 'login', 'User logged in to the system', '2026-08-24 18:53:58'),
(305, 16, 'michael', 'staff', 'logout', 'User logged out of the system', '2026-08-24 19:07:22'),
(306, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 19:07:36'),
(307, 15, 'nethhh', 'admin', 'user_deleted', 'Admin deleted user account: Michael Scofield (username: michael, role: Staff).', '2026-08-24 19:07:57'),
(308, 15, 'nethhh', 'admin', 'user_created', 'Admin created user account: Theodore Bagwell (username: tbag, role: Staff).', '2026-08-24 19:09:41'),
(309, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 19:10:04'),
(310, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-08-24 19:10:11'),
(311, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-08-24 19:10:15'),
(312, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 19:14:27'),
(313, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 19:16:24'),
(314, 21, 'scofield', 'resident', 'registration_submitted', 'Resident registered account: Michael Scofied (username: scofield, role: Resident). Pending admin verification.', '2026-08-24 19:17:19'),
(315, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-24 19:18:39'),
(316, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-24 19:19:06'),
(317, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 19:19:12'),
(318, 15, 'nethhh', 'admin', 'verification_verified', 'Admin set verification status to verified for user account: Michael Scofied (username: scofield, role: Resident).', '2026-08-24 19:19:24'),
(319, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 19:19:28'),
(320, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-24 19:19:36'),
(321, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-24 19:21:00'),
(322, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 20:22:48'),
(323, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 20:25:13'),
(324, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-24 20:26:13'),
(325, 21, 'scofield', 'resident', 'profile_updated', 'Resident updated their profile information.', '2026-08-24 20:26:43'),
(326, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-24 20:26:46'),
(327, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-08-24 20:58:16'),
(328, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-08-24 20:58:26'),
(329, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-24 20:58:34'),
(330, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-24 20:59:07'),
(331, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 01:10:42'),
(332, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 01:13:57'),
(333, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-08-25 01:14:03'),
(334, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-08-25 01:29:32'),
(335, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 01:29:39'),
(336, 21, 'scofield', 'resident', 'logout', 'User was logged out due to inactivity.', '2026-08-25 01:53:17'),
(337, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 02:18:59'),
(338, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 02:44:53'),
(339, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 02:44:58'),
(340, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 02:45:06'),
(341, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 03:08:49'),
(342, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 03:12:34'),
(343, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 03:22:55'),
(344, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 03:23:07'),
(345, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 03:23:10'),
(346, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 03:23:16'),
(347, 15, 'nethhh', 'admin', 'template_created', 'Admin created document template: Certificate of Residency.', '2026-08-25 03:25:06'),
(348, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 03:25:15'),
(349, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 03:25:23'),
(350, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 03:31:21'),
(351, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 03:31:28'),
(352, 15, 'nethhh', 'admin', 'template_updated', 'Admin updated document template: Certificate of Residency.', '2026-08-25 03:31:42'),
(353, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 03:31:43'),
(354, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 03:31:50'),
(355, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 03:31:56'),
(356, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-08-25 03:32:08'),
(357, 20, 'tbag', 'staff', 'template_updated', 'Staff updated document template: Certificate of Residency.', '2026-08-25 03:32:28'),
(358, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-08-25 03:32:30'),
(359, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 03:32:37'),
(360, 21, 'scofield', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-029 (Certificate of Residency) for account: Michael Scofied (username: scofield, role: Resident).', '2026-08-25 03:32:47'),
(361, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 03:32:54'),
(362, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 03:33:01'),
(363, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-029 (Certificate of Residency) for resident Michael Scofied (username: scofield).', '2026-08-25 03:33:44'),
(364, 15, 'nethhh', 'admin', 'template_deleted', 'Admin deleted document template: Certificate of Residency (Certificates).', '2026-08-25 03:57:39'),
(365, 15, 'nethhh', 'admin', 'request_printed', 'Admin printed document request: REQ-2026-029 (Certificate of Residency) for resident Michael Scofied (username: scofield).', '2026-08-25 04:39:17'),
(366, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-029 (Certificate of Residency) for resident Michael Scofied (username: scofield).', '2026-08-25 04:39:33'),
(367, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-025 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-08-25 04:39:34'),
(368, 15, 'nethhh', 'admin', 'template_created', 'Admin created document template: Barangay Clearance.', '2026-08-25 04:48:02'),
(369, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 04:48:06'),
(370, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 04:48:19'),
(371, 21, 'scofield', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-030 (Barangay Clearance) for account: Michael Scofied (username: scofield, role: Resident).', '2026-08-25 04:48:37'),
(372, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 04:48:42'),
(373, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 04:48:54'),
(374, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-030 (Barangay Clearance) for resident Michael Scofied (username: scofield).', '2026-08-25 04:49:01'),
(375, 15, 'nethhh', 'admin', 'template_updated', 'Admin updated document template: Barangay Clearance.', '2026-08-25 04:58:00'),
(376, 15, 'nethhh', 'admin', 'template_created', 'Admin created document template: Certificate of Indigency.', '2026-08-25 05:07:39'),
(377, 15, 'nethhh', 'admin', 'template_updated', 'Admin updated document template: Barangay Clearance.', '2026-08-25 05:10:41'),
(378, 15, 'nethhh', 'admin', 'template_updated', 'Admin updated document template: Certificate of Indigency.', '2026-08-25 05:10:55'),
(379, 15, 'nethhh', 'admin', 'template_updated', 'Admin updated document template: Certificate of Indigency.', '2026-08-25 05:11:37'),
(380, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 05:11:42'),
(381, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 05:11:49'),
(382, 21, 'scofield', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-031 (Certificate of Indigency) for account: Michael Scofied (username: scofield, role: Resident).', '2026-08-25 05:12:23'),
(383, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 05:12:25'),
(384, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-08-25 05:12:34'),
(385, 20, 'tbag', 'staff', 'request_approved', 'Staff approved document request: REQ-2026-031 (Certificate of Indigency) for resident Michael Scofied (username: scofield).', '2026-08-25 05:12:38'),
(386, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:07:12'),
(387, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:07:17'),
(388, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:07:23'),
(389, 15, 'nethhh', 'admin', 'user_deleted', 'Admin deleted user account: Lincoln Burrows (username: lincoln, role: Resident).', '2026-08-25 17:07:47'),
(390, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:08:12'),
(391, 21, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:09:33'),
(392, 21, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:09:39'),
(393, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:09:48'),
(394, 15, 'nethhh', 'admin', 'user_deleted', 'Admin deleted user account: Michael Scofied (username: scofield, role: Resident).', '2026-08-25 17:10:34'),
(395, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:10:40'),
(396, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:26:16'),
(397, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:27:09'),
(398, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:27:15'),
(399, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-028 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-08-25 17:27:20'),
(400, 15, 'nethhh', 'admin', 'request_printed', 'Admin printed document request: REQ-2026-028 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-08-25 17:27:27'),
(401, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-028 (Certificate of Residency) for resident Fernando Sucre (username: fernando).', '2026-08-25 17:27:41'),
(402, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:27:44'),
(403, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:27:54'),
(404, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:34:28'),
(405, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:37:16'),
(406, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:37:38'),
(407, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:41:09'),
(408, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:51:46'),
(409, 22, 'scofield', 'resident', 'registration_submitted', 'Resident registered account: Michael Scofield (username: scofield, role: Resident). Pending admin verification.', '2026-08-25 17:54:41'),
(410, 22, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:56:28'),
(411, 22, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:56:54'),
(412, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:57:01'),
(413, 15, 'nethhh', 'admin', 'verification_verified', 'Admin set verification status to verified for user account: Michael Scofield (username: scofield, role: Resident).', '2026-08-25 17:57:24'),
(414, 15, 'nethhh', 'admin', 'verification_verified', 'Admin set verification status to verified for user account: Michael Scofield (username: scofield, role: Resident).', '2026-08-25 17:57:31'),
(415, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 17:57:40'),
(416, 22, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 17:57:46'),
(417, 22, 'scofield', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-032 (Certificate of Indigency) for account: Michael Scofield (username: scofield, role: Resident).', '2026-08-25 17:58:16'),
(418, 22, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 17:58:55'),
(419, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 17:59:04'),
(420, 15, 'nethhh', 'admin', 'user_created', 'Admin created user account: Lincoln Burrows (username: lincoln, role: Staff).', '2026-08-25 18:00:06'),
(421, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-032 (Certificate of Indigency) for resident Michael Scofield (username: scofield).', '2026-08-25 18:00:52'),
(422, 15, 'nethhh', 'admin', 'request_printed', 'Admin printed document request: REQ-2026-032 (Certificate of Indigency) for resident Michael Scofield (username: scofield).', '2026-08-25 18:01:12'),
(423, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:02:00'),
(424, 22, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 18:02:11'),
(425, 22, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 18:02:24'),
(426, 22, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 18:02:40'),
(427, 22, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 18:03:12'),
(428, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 18:03:19'),
(429, 15, 'nethhh', 'admin', 'request_released', 'Admin released document request: REQ-2026-032 (Certificate of Indigency) for resident Michael Scofield (username: scofield).', '2026-08-25 18:03:29'),
(430, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:03:38'),
(431, 22, 'scofield', 'resident', 'login', 'User logged in to the system', '2026-08-25 18:03:48'),
(432, 22, 'scofield', 'resident', 'logout', 'User logged out of the system', '2026-08-25 18:04:11'),
(433, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 18:04:18');
INSERT INTO `activity_logs` (`id`, `user_id`, `username`, `role`, `action`, `description`, `created_at`) VALUES
(434, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260825_121226.zip.', '2026-08-25 18:12:31'),
(435, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260825_121231.zip.', '2026-08-25 18:12:34'),
(436, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260825_121234.zip.', '2026-08-25 18:12:37'),
(437, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260825_121237.zip.', '2026-08-25 18:12:39'),
(438, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:16:08'),
(439, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 18:19:16'),
(440, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:19:20'),
(441, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 18:19:48'),
(442, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:23:54'),
(443, 23, 'lincoln', 'staff', 'login', 'User logged in to the system', '2026-08-25 18:24:07'),
(444, 23, 'lincoln', 'staff', 'logout', 'User logged out of the system', '2026-08-25 18:26:01'),
(445, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 18:29:04'),
(446, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 18:42:04'),
(447, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-08-25 20:42:19'),
(448, 15, 'nethhh', 'admin', 'system_backup_created', 'Admin created system backup: system_backup_20260825_144238.zip.', '2026-08-25 20:42:43'),
(449, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-08-25 20:59:16'),
(450, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-09-26 13:34:04'),
(451, 15, 'nethhh', 'admin', 'database_restored', 'Admin restored database backup: database_backup_20260926_133611.enc.', '2026-09-26 05:44:57'),
(452, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-09-26 06:33:22'),
(453, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-09-26 06:33:34'),
(454, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-09-26 06:34:03'),
(455, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-09-26 06:34:13'),
(456, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-09-26 06:34:58'),
(457, 17, 'fernando', 'resident', 'login', 'User logged in to the system', '2026-09-26 06:35:07'),
(458, 17, 'fernando', 'resident', 'document_requested', 'Resident submitted document request: REQ-2026-033 (Certificate of Indigency) for account: Fernando Sucre (username: fernando, role: Resident).', '2026-09-26 06:35:49'),
(459, 17, 'fernando', 'resident', 'logout', 'User logged out of the system', '2026-09-26 06:35:51'),
(460, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-09-26 06:35:59'),
(461, 15, 'nethhh', 'admin', 'request_approved', 'Admin approved document request: REQ-2026-033 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-09-26 06:36:13'),
(462, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-09-26 06:47:50'),
(463, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-09-26 06:47:58'),
(464, 20, 'tbag', 'staff', 'request_printed', 'Staff printed document request: REQ-2026-033 (Certificate of Indigency) for resident Fernando Sucre (username: fernando).', '2026-09-26 06:50:14'),
(465, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-09-26 06:51:12'),
(466, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-09-26 06:51:24'),
(467, 20, 'tbag', 'staff', 'logout', 'User logged out of the system', '2026-09-26 06:53:17'),
(468, 15, 'nethhh', 'admin', 'login', 'User logged in to the system', '2026-09-26 06:53:24'),
(469, 15, 'nethhh', 'admin', 'logout', 'User logged out of the system', '2026-09-26 07:12:11'),
(470, 20, 'tbag', 'staff', 'login', 'User logged in to the system', '2026-09-26 07:12:19'),
(471, 20, 'tbag', 'staff', 'request_printed', 'Staff printed document request: REQ-2026-030 (Barangay Clearance).', '2026-09-26 07:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `categories` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','done') NOT NULL DEFAULT 'pending',
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `categories`, `location`, `description`, `attachment_name`, `attachment_path`, `status`, `completed_by`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Draining / Flooding', 'rwenvierv', 'erberbcdbvebrf', 'cat hitler.jpg', 'uploads/complaints/complaint_6a6c583fd8c391.76754598_cat_hitler.jpg', 'done', 16, '2026-07-31 16:15:16', '2026-07-31 16:09:35', '2026-07-31 16:15:16');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `request_id` int(11) NOT NULL,
  `request_number` varchar(30) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','approved','printed','released','rejected') NOT NULL DEFAULT 'pending',
  `generated_file_path` varchar(255) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`request_id`, `request_number`, `user_id`, `template_id`, `document_type`, `purpose`, `status`, `generated_file_path`, `admin_remarks`, `reviewed_by`, `reviewed_at`, `date_submitted`, `date_updated`) VALUES
(1, 'REQ-2026-001', 6, NULL, 'Certificate of Residency', 'sample', 'rejected', NULL, NULL, 9, '2026-06-24 22:08:17', '2026-06-24 20:56:23', '2026-06-24 22:08:17'),
(2, 'REQ-2026-002', 10, NULL, 'Certificate of Residency', 'purpose', 'released', NULL, NULL, 9, '2026-06-27 00:15:54', '2026-06-27 00:14:37', '2026-06-27 00:15:54'),
(3, 'REQ-2026-003', 10, NULL, 'Certificate of Residency', 'purpose', 'released', NULL, NULL, 7, '2026-07-29 20:47:02', '2026-06-27 00:16:24', '2026-07-29 20:47:02'),
(4, 'REQ-2026-004', 10, NULL, 'Certificate of Indigency', 'purpose', 'released', NULL, NULL, 15, '2026-07-30 23:06:28', '2026-06-27 00:19:22', '2026-07-30 23:06:28'),
(5, 'REQ-2026-005', 10, NULL, 'Certificate of Indigency', 'purpose', 'rejected', NULL, 'Invalid requirement', 9, '2026-06-27 00:23:26', '2026-06-27 00:19:38', '2026-06-27 00:23:26'),
(6, 'REQ-2026-006', 12, NULL, 'Certificate of Indigency', 'sadwqf', 'released', 'uploads/generated_documents/REQ-2026-000_1785345349.pdf', NULL, 15, '2026-07-30 23:06:35', '2026-07-30 01:15:49', '2026-07-30 23:06:35'),
(7, 'REQ-2026-007', 12, NULL, 'Certificate of Residency', 'awdwvdsv', 'rejected', 'uploads/generated_documents/REQ-2026-007_1785393883.pdf', 'sawevewv', 11, '2026-07-30 14:45:42', '2026-07-30 14:44:43', '2026-07-30 14:45:42'),
(8, 'REQ-2026-008', 12, NULL, 'Certificate of Indigency', 'awfwqfwe', 'rejected', 'uploads/generated_documents/REQ-2026-008_1785394007.pdf', 'wefewvwv', 16, '2026-07-30 23:01:07', '2026-07-30 14:46:47', '2026-07-30 23:01:07'),
(9, 'REQ-2026-009', 12, NULL, 'Certificate of Indigency', 'ewfewvwe', 'rejected', 'uploads/generated_documents/REQ-2026-009_1785394043.pdf', 'wefwef', 10, '2026-07-30 14:49:42', '2026-07-30 14:47:23', '2026-07-30 14:49:42'),
(10, 'REQ-2026-010', 12, NULL, 'Certificate of Residency', 'cagerwvbreb', 'released', 'uploads/generated_documents/REQ-2026-010_1785394056.pdf', NULL, 14, '2026-07-30 22:56:51', '2026-07-30 14:47:36', '2026-07-30 22:56:51'),
(11, 'REQ-2026-011', 12, NULL, 'Certificate of Residency', 'cwevewvwe', 'released', 'uploads/generated_documents/REQ-2026-011_1785394065.pdf', NULL, 14, '2026-07-30 22:56:52', '2026-07-30 14:47:45', '2026-07-30 22:56:52'),
(12, 'REQ-2026-012', 12, NULL, 'Certificate of Residency', 'ascewgvew', 'rejected', 'uploads/generated_documents/REQ-2026-012_1785394074.pdf', 'wefwevwe', 11, '2026-07-30 14:48:08', '2026-07-30 14:47:54', '2026-07-30 14:48:08'),
(13, 'REQ-2026-013', 12, NULL, 'Certificate of Indigency', 'fwqfgewg', 'rejected', 'uploads/generated_documents/REQ-2026-013_1785395031.pdf', 'dqwfwqf', 14, '2026-07-30 22:56:43', '2026-07-30 15:03:51', '2026-07-30 22:56:43'),
(14, 'REQ-2026-014', 12, NULL, 'Certificate of Residency', 'qwefwegwe', 'released', 'uploads/generated_documents/REQ-2026-014_1785395059.pdf', NULL, 10, '2026-07-30 15:07:12', '2026-07-30 15:04:19', '2026-07-30 15:07:12'),
(15, 'REQ-2026-015', 12, NULL, 'Certificate of Residency', 'fwqfwqf', 'rejected', 'uploads/generated_documents/REQ-2026-015_1785395089.pdf', 'fwqefewf', 10, '2026-07-30 15:07:04', '2026-07-30 15:04:49', '2026-07-30 15:07:04'),
(16, 'REQ-2026-016', 12, NULL, 'Certificate of Indigency', 'wefewggewg', 'released', 'uploads/generated_documents/REQ-2026-016_1785395099.pdf', NULL, 11, '2026-07-30 15:06:01', '2026-07-30 15:04:59', '2026-07-30 15:06:01'),
(17, 'REQ-2026-017', 12, NULL, 'Certificate of Indigency', 'fewqfwebwe', 'rejected', 'uploads/generated_documents/REQ-2026-017_1785395111.pdf', 'wefsvewg', 11, '2026-07-30 15:05:33', '2026-07-30 15:05:11', '2026-07-30 15:05:33'),
(18, 'REQ-2026-018', 17, NULL, 'Certificate of Indigency', 'wefew', 'rejected', 'uploads/generated_documents/REQ-2026-018_1785423864.pdf', 'ewfewvwv', 16, '2026-07-30 23:07:34', '2026-07-30 23:04:24', '2026-07-30 23:07:34'),
(19, 'REQ-2026-019', 17, NULL, 'Certificate of Residency', 'cwefew', 'released', 'uploads/generated_documents/REQ-2026-019_1785423878.pdf', NULL, 15, '2026-07-30 23:09:04', '2026-07-30 23:04:38', '2026-07-30 23:09:04'),
(20, 'REQ-2026-020', 17, NULL, 'Certificate of Indigency', 'cwerfwe', 'released', 'uploads/generated_documents/REQ-2026-020_1785423893.pdf', NULL, 16, '2026-07-30 23:09:22', '2026-07-30 23:04:53', '2026-07-30 23:09:22'),
(21, 'REQ-2026-021', 17, NULL, 'Certificate of Residency', 'cascewv', 'approved', 'uploads/generated_documents/REQ-2026-021_1785423904.pdf', NULL, 15, '2026-07-30 23:06:57', '2026-07-30 23:05:04', '2026-07-30 23:06:57'),
(22, 'REQ-2026-022', 17, NULL, 'Certificate of Indigency', 'casfewvfdbv', 'rejected', 'uploads/generated_documents/REQ-2026-022_1785423916.pdf', 'wefvewvew', 15, '2026-07-30 23:06:10', '2026-07-30 23:05:16', '2026-07-30 23:06:10'),
(23, 'REQ-2026-023', 17, NULL, 'Certificate of Residency', 'sacregrebc', 'approved', 'uploads/generated_documents/REQ-2026-023_1785423926.pdf', NULL, 15, '2026-07-30 23:06:00', '2026-07-30 23:05:26', '2026-07-30 23:06:00'),
(24, 'REQ-2026-024', 17, NULL, 'Certificate of Indigency', 'ewfvewvwe', 'pending', 'uploads/generated_documents/REQ-2026-024_1785424115.pdf', NULL, NULL, NULL, '2026-07-30 23:08:35', '2026-07-30 23:08:35'),
(25, 'REQ-2026-025', 17, NULL, 'Certificate of Residency', 'ascngfrt', 'released', 'uploads/generated_documents/REQ-2026-025_1785424124.pdf', NULL, 15, '2026-08-25 04:39:34', '2026-07-30 23:08:44', '2026-08-25 04:39:34'),
(26, 'REQ-2026-026', 17, NULL, 'Certificate of Indigency', 'sacewgvew', 'pending', 'uploads/generated_documents/REQ-2026-026_1785427294.pdf', NULL, NULL, NULL, '2026-07-31 00:01:34', '2026-07-31 00:01:34'),
(27, 'REQ-2026-027', 18, NULL, 'Certificate of Indigency', 'asdewqcvew', 'released', 'uploads/generated_documents/REQ-2026-027_1785681652.pdf', NULL, 16, '2026-08-02 22:46:16', '2026-08-02 22:40:52', '2026-08-02 22:46:16'),
(28, 'REQ-2026-028', 17, NULL, 'Certificate of Residency', 'sacs', 'released', 'uploads/generated_documents/REQ-2026-028_1787650040.pdf', NULL, 15, '2026-08-25 17:27:41', '2026-08-24 16:23:25', '2026-08-25 17:27:41'),
(29, 'REQ-2026-029', 21, 7, 'Certificate of Residency', 'ewfdvsdv', 'released', 'uploads/generated_documents/REQ-2026-029_1787600024.pdf', NULL, 15, '2026-08-25 04:39:33', '2026-08-25 03:32:47', '2026-08-25 04:39:33'),
(30, 'REQ-2026-030', 21, 8, 'Barangay Clearance', 'wevwsrvfrvre', 'printed', 'uploads/generated_documents/REQ-2026-030_1787605841.pdf', NULL, 20, '2026-09-26 15:14:04', '2026-08-25 04:48:37', '2026-09-26 15:14:04'),
(31, 'REQ-2026-031', 21, 9, 'Certificate of Indigency', 'ewfwefewv', 'approved', 'uploads/generated_documents/REQ-2026-031_1787605958.pdf', NULL, 20, '2026-08-25 05:12:38', '2026-08-25 05:12:23', '2026-08-25 05:12:38'),
(32, 'REQ-2026-032', 22, 9, 'Certificate of Indigency', 'sdjbvewbvjdsvnuewb', 'released', 'uploads/generated_documents/REQ-2026-032_1787652052.pdf', NULL, 15, '2026-08-25 18:03:29', '2026-08-25 17:58:16', '2026-08-25 18:03:29'),
(33, 'REQ-2026-033', 17, 9, 'Certificate of Indigency', 'fqw3fwewegewgewhwe', 'printed', 'uploads/generated_documents/REQ-2026-033_1790404573.pdf', NULL, 20, '2026-09-26 14:50:14', '2026-09-26 14:35:49', '2026-09-26 14:50:14');

-- --------------------------------------------------------

--
-- Table structure for table `document_request_files`
--

CREATE TABLE `document_request_files` (
  `file_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_request_files`
--

INSERT INTO `document_request_files` (`file_id`, `request_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 1, 'cat hitler.jpg', 'uploads/requirements/6a3bd3f706adf_cathitler.jpg', '2026-06-24 20:56:23'),
(2, 2, 'cat hitler.jpg', 'uploads/requirements/6a3ea56de6552_cathitler.jpg', '2026-06-27 00:14:37'),
(3, 3, 'cat hitler.jpg', 'uploads/requirements/6a3ea5d8928b7_cathitler.jpg', '2026-06-27 00:16:24'),
(4, 4, 'cat hitler.jpg', 'uploads/requirements/6a3ea68aa5283_cathitler.jpg', '2026-06-27 00:19:22'),
(5, 5, 'cat hitler.jpg', 'uploads/requirements/6a3ea69a3d7a0_cathitler.jpg', '2026-06-27 00:19:38'),
(6, 6, 'cat hitler.jpg', 'uploads/requirements/6a6a3545d7596_cathitler.jpg', '2026-07-30 01:15:49'),
(7, 7, 'cat hitler.jpg', 'uploads/requirements/6a6af2db09528_cathitler.jpg', '2026-07-30 14:44:43'),
(8, 8, 'cat hitler.jpg', 'uploads/requirements/6a6af35793b27_cathitler.jpg', '2026-07-30 14:46:47'),
(9, 9, 'cat hitler.jpg', 'uploads/requirements/6a6af37be7880_cathitler.jpg', '2026-07-30 14:47:23'),
(10, 10, 'cat hitler.jpg', 'uploads/requirements/6a6af38852469_cathitler.jpg', '2026-07-30 14:47:36'),
(11, 11, 'cat hitler.jpg', 'uploads/requirements/6a6af39155920_cathitler.jpg', '2026-07-30 14:47:45'),
(12, 12, 'cat hitler.jpg', 'uploads/requirements/6a6af39a71812_cathitler.jpg', '2026-07-30 14:47:54'),
(13, 13, 'cat hitler.jpg', 'uploads/requirements/6a6af757533a7_cathitler.jpg', '2026-07-30 15:03:51'),
(14, 14, 'cat hitler.jpg', 'uploads/requirements/6a6af773cb222_cathitler.jpg', '2026-07-30 15:04:19'),
(15, 15, 'cat hitler.jpg', 'uploads/requirements/6a6af7914b588_cathitler.jpg', '2026-07-30 15:04:49'),
(16, 16, 'cat hitler.jpg', 'uploads/requirements/6a6af79ba6374_cathitler.jpg', '2026-07-30 15:04:59'),
(17, 17, 'cat hitler.jpg', 'uploads/requirements/6a6af7a744d13_cathitler.jpg', '2026-07-30 15:05:11'),
(18, 18, 'cat hitler.jpg', 'uploads/requirements/6a6b67f82d1e6_cathitler.jpg', '2026-07-30 23:04:24'),
(19, 19, 'cat hitler.jpg', 'uploads/requirements/6a6b680682972_cathitler.jpg', '2026-07-30 23:04:38'),
(20, 20, 'cat hitler.jpg', 'uploads/requirements/6a6b68153e2eb_cathitler.jpg', '2026-07-30 23:04:53'),
(21, 21, 'cat hitler.jpg', 'uploads/requirements/6a6b682034266_cathitler.jpg', '2026-07-30 23:05:04'),
(22, 22, 'cat hitler.jpg', 'uploads/requirements/6a6b682cd0f96_cathitler.jpg', '2026-07-30 23:05:16'),
(23, 23, 'cat hitler.jpg', 'uploads/requirements/6a6b683646f8b_cathitler.jpg', '2026-07-30 23:05:26'),
(24, 24, 'cat hitler.jpg', 'uploads/requirements/6a6b68f381df9_cathitler.jpg', '2026-07-30 23:08:35'),
(25, 25, 'cat hitler.jpg', 'uploads/requirements/6a6b68fc7b758_cathitler.jpg', '2026-07-30 23:08:44'),
(26, 26, 'cat hitler.jpg', 'uploads/requirements/6a6b755e7b569_cathitler.jpg', '2026-07-31 00:01:34'),
(27, 27, 'cat hitler.jpg', 'uploads/requirements/6a6f56f45aa3a_cathitler.jpg', '2026-08-02 22:40:52'),
(28, 28, 'cat hitler.jpg', 'uploads/requirements/6a8bff7dcbe66_cathitler.jpg', '2026-08-24 16:23:25'),
(29, 29, 'cat hitler.jpg', 'uploads/requirements/6a8c9c5f6636e_cathitler.jpg', '2026-08-25 03:32:47'),
(30, 30, 'cat hitler.jpg', 'uploads/requirements/6a8cae25c7943_cathitler.jpg', '2026-08-25 04:48:37'),
(31, 30, 'cat hitler.jpg', 'uploads/requirements/6a8cae25c7b9f_cathitler.jpg', '2026-08-25 04:48:37'),
(32, 31, 'cat hitler.jpg', 'uploads/requirements/6a8cb3b72abe3_cathitler.jpg', '2026-08-25 05:12:23'),
(33, 31, 'cat hitler.jpg', 'uploads/requirements/6a8cb3b72ae24_cathitler.jpg', '2026-08-25 05:12:23'),
(34, 32, 'cat hitler.jpg', 'uploads/requirements/6a8d67384ad82_cathitler.jpg', '2026-08-25 17:58:16'),
(35, 32, 'cat hitler.jpg', 'uploads/requirements/6a8d67384af64_cathitler.jpg', '2026-08-25 17:58:16'),
(36, 33, 'cat hitler.jpg', 'uploads/requirements/6ab767c556898_cathitler.jpg', '2026-09-26 14:35:49'),
(37, 33, 'cat hitler.jpg', 'uploads/requirements/6ab767c556c90_cathitler.jpg', '2026-09-26 14:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `document_templates`
--

CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `template_content` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_templates`
--

INSERT INTO `document_templates` (`id`, `name`, `category`, `description`, `fee`, `usage_count`, `file_name`, `file_path`, `template_content`, `requirements`, `created_at`, `last_modified`) VALUES
(8, 'Barangay Clearance', 'Certificates', '', 0.00, 1, '', '', 'This further certifies that, based on the available records of this Office, the above-named resident has no pending case, complaint, or derogatory record on file as of the date of issuance.', 'Valid ID\r\nProof of Residency', '2026-08-25 04:48:02', '2026-08-25 05:10:41'),
(9, 'Certificate of Indigency', 'Certificates', '', 1.00, 3, '', '', 'This is to certify the above-mentioned name is classified \"INDIGENT\" in this barangay.', 'Valid Id\r\nProof of Residency', '2026-08-25 05:07:39', '2026-09-26 06:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(2, 15, '74a378cdf4e05d209f0c52ec2feca8a67aaae64c0b6b0abcdf7abdbb4eef41dd', '2026-08-24 15:27:02', '2026-08-24 20:46:08', '2026-08-24 20:27:02'),
(3, 15, '323c64119353d43b871fc3b4c8fdfc0fddd9b9071c7d031ce3cc913b868f9687', '2026-08-24 15:46:08', '2026-08-24 20:51:01', '2026-08-24 20:46:08'),
(4, 15, '3f9b3b3c81b9b4c91d2d8dc77117a9ba71b66f656defd4117550865be27f0398', '2026-08-24 15:51:01', NULL, '2026-08-24 20:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doc_type` varchar(255) NOT NULL,
  `date_submitted` datetime NOT NULL,
  `date_requested` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'processing',
  `purpose` text DEFAULT NULL,
  `files` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `doc_type`, `date_submitted`, `date_requested`, `status`, `purpose`, `files`, `created_at`, `updated_at`) VALUES
(1, 4, 'Certificate of Indigency', '2026-05-09 11:38:53', NULL, 'processing', 'zzz', '{\"government_id\":\"69ff00adc1c82_picka.jpg\"}', '2026-05-09 17:38:53', '2026-05-09 17:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `residentId` int(11) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `dateOfBirth` date NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `civilStatus` enum('Single','Married','Widowed','Separated') NOT NULL,
  `fullAddress` text NOT NULL,
  `phoneNumber` varchar(20) DEFAULT NULL,
  `emailAddress` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`residentId`, `firstName`, `lastName`, `middleName`, `suffix`, `dateOfBirth`, `sex`, `civilStatus`, `fullAddress`, `phoneNumber`, `emailAddress`, `created_at`, `updated_at`) VALUES
(4, 'Miguel', 'Rivera', 'N/A', 'N/A', '2001-09-29', 'Male', 'Single', '12333 street', '09300659494', 'mglrvr55555@gmail.com', '2026-05-09 13:57:16', '2026-05-09 13:57:16'),
(5, 'admin', 'admin', 'N/A', 'N/A', '2001-09-12', 'Male', 'Single', '1212123 street', '09121232211', 'admin@gmail.com', '2026-05-09 18:30:00', '2026-05-09 18:30:00'),
(8, 'Miguel', 'Rivera', 'Lopez', NULL, '2001-09-19', 'Male', 'Single', 'quezon city', '09206899729', 'midguel7@gmail.com', '2026-07-29 19:47:21', '2026-07-29 19:47:21'),
(10, 'Theodor', 'Bagwell', NULL, NULL, '1980-10-01', 'Male', 'Separated', 'San Mateo, Rizal', '09111111111', 'tbag@gmail.com', '2026-07-30 21:40:56', '2026-07-30 21:40:56'),
(11, 'Fernando', 'Sucre', NULL, NULL, '1990-01-20', 'Male', 'Married', 'San Mateo, Rizal', '09385003281', 'fernandosucre@gmail.com', '2026-07-30 23:02:22', '2026-07-30 23:02:22'),
(12, 'Alexander', 'Mahone', NULL, NULL, '1980-05-05', 'Male', 'Separated', 'San Mateo, Rizal', '09839902141', 'alexmahone@gmail.com', '2026-07-30 23:11:07', '2026-07-30 23:11:07'),
(13, 'Lincoln', 'Burrows', NULL, NULL, '1980-04-11', 'Male', 'Single', 'San Mateo, Rizal', '09469480012', 'lincolnburrows@gmail.com', '2026-07-30 23:12:15', '2026-07-30 23:12:15'),
(14, 'Michael', 'Scofied', NULL, NULL, '2000-11-02', 'Male', 'Single', 'San Mateo, Rizal', '09425683385', 'kennethtarroza22@gmail.com', '2026-08-24 19:17:19', '2026-08-24 20:26:43'),
(15, 'Michael', 'Scofield', NULL, NULL, '2000-11-01', 'Male', 'Single', 'Krus na Ligas', '09318748921', 'michaelscofield@gmail.com', '2026-08-25 17:54:41', '2026-08-25 17:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('resident','admin','staff') NOT NULL,
  `residentId` int(11) DEFAULT NULL,
  `account_status` varchar(20) NOT NULL DEFAULT 'pending',
  `verification_proof` varchar(255) DEFAULT NULL,
  `verification_rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avatar_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `username`, `first_name`, `last_name`, `contact_email`, `contact_phone`, `password`, `role`, `residentId`, `account_status`, `verification_proof`, `verification_rejection_reason`, `created_at`, `updated_at`, `avatar_path`) VALUES
(15, 'nethhh', 'Kenneth', 'Tarroza', 'kennethtarroza22@gmail.com', '09129048371', '$2y$10$iUQ6/4XVeAhimt96JiAIIeQkZKGQ.PpUzIrfb6vzzSMBOQzQ4N/qi', 'admin', NULL, 'verified', NULL, NULL, '2026-07-30 22:58:35', '2026-07-30 22:58:35', NULL),
(17, 'fernando', NULL, NULL, NULL, NULL, '$2y$10$iNCt.PQKUyfaK0xlrHSeeu9.cODzEr6Co7i0gM4z.34vW7h8zdAIi', 'resident', 11, 'verified', 'uploads/verification_proofs/proof_6a6b677eb6e284.63244289_cathitler.jpg', NULL, '2026-07-30 23:02:22', '2026-08-02 21:35:53', '/updated/uploads/avatars/avatar_17_1785677753.jpg'),
(18, 'alexander', NULL, NULL, NULL, NULL, '$2y$10$Y9TXXYY4/mSHeU7JR4u6m.UuiIeZsU8NIhSNcLfpnt4NUHZLSxlvC', 'resident', 12, 'verified', 'uploads/verification_proofs/proof_6a6b698b1f8ba5.74669941_cathitler.jpg', NULL, '2026-07-30 23:11:07', '2026-08-02 22:40:32', NULL),
(20, 'tbag', 'Theodore', 'Bagwell', 'tbag@gmail.com', '09483719044', '$2y$10$E3.F55ngROscDENPpRCGVejxSBop0NYEq9s8Jghk8126ENl55BQK.', 'staff', NULL, 'verified', NULL, NULL, '2026-08-24 19:09:41', '2026-08-24 19:09:41', NULL),
(22, 'scofield', NULL, NULL, NULL, NULL, '$2y$10$SxGu8oLLrkclllxDBHITEOW8ZZbzrdnPSIzJPcm3n8AEQZ1PV3ocy', 'resident', 15, 'verified', 'uploads/verification_proofs/proof_6a8d666138c7e3.95472757_cathitler.jpg', NULL, '2026-08-25 17:54:41', '2026-08-25 17:57:31', NULL),
(23, 'lincoln', 'Lincoln', 'Burrows', 'lincolnburrows@gmail.com', '09123488471', '$2y$10$L8vfk3NWzak7xWSAq/.9xOGsPeau30GNk91YliXtgRgo5021sJ6R6', 'staff', NULL, 'verified', NULL, NULL, '2026-08-25 18:00:06', '2026-08-25 18:00:06', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `idx_complaints_status_created` (`status`,`created_at`),
  ADD KEY `idx_complaints_completed_by` (`completed_by`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_document_requests_template_id` (`template_id`);

--
-- Indexes for table `document_request_files`
--
ALTER TABLE `document_request_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_document_request_files_request_id` (`request_id`);

--
-- Indexes for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `idx_password_resets_token_hash` (`token_hash`),
  ADD KEY `idx_password_resets_user_id` (`user_id`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`residentId`),
  ADD UNIQUE KEY `phoneNumber` (`phoneNumber`),
  ADD UNIQUE KEY `emailAddress` (`emailAddress`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `residentId` (`residentId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=472;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `document_request_files`
--
ALTER TABLE `document_request_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `residentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`residentId`) REFERENCES `residents` (`residentId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
