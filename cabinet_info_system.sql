-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 02:06 PM
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
-- Database: `cabinet_info_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cabinets`
--

CREATE TABLE `cabinets` (
  `id` int(11) NOT NULL,
  `cabinet_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `qr_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabinets`
--

INSERT INTO `cabinets` (`id`, `cabinet_number`, `name`, `photo_path`, `qr_path`, `created_at`, `updated_at`) VALUES
(1, 'CAB20250001', 'Cabinet 1', 'uploads/1757641698_Cabinet 1.jpg', 'qrcodes/cabinet_CAB20250001.png', '2025-09-10 06:59:19', '2025-09-12 01:48:18'),
(2, 'CAB20250002', 'Cabinet 2', 'uploads/1757640475_Cabinet 2.jpg', 'qrcodes/cabinet_CAB20250002.png', '2025-09-10 07:08:55', '2025-09-12 01:27:55'),
(3, 'CAB20250003', 'Cabinet 3', 'uploads/1757640485_Cabinet 3.jpg', 'qrcodes/cabinet_CAB20250003.png', '2025-09-10 07:12:09', '2025-09-12 01:28:05'),
(4, 'CAB20250004', 'Cabinet 4', 'uploads/1757640430_Cabinet 4.jpg', 'qrcodes/cabinet_CAB20250004.png', '2025-09-10 07:23:35', '2025-09-12 01:27:10'),
(5, 'CAB20250005', 'Cabinet 5', 'uploads/1757641922_Cabinet 5.jpg', NULL, '2025-09-12 01:52:02', '2025-09-12 01:58:08'),
(6, 'CAB20250007', 'Cabinet 7', 'uploads/1757642233_Cabinet 7.jpg', NULL, '2025-09-12 01:57:13', '2025-09-12 01:58:01'),
(7, 'CAB20250008', 'Cabinet 8', 'uploads/1757655666_Cabinet 8.jpg', NULL, '2025-09-12 02:02:43', '2025-09-12 05:41:06'),
(8, 'CAB20250009', 'Cabinet 9', 'uploads/1757649393_Cabinet 9.jpg', NULL, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(9, 'CAB20250010', 'Cabinet 10', 'uploads/1757649558_Cabinet 10.jpg', NULL, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(10, 'CAB20250012', 'Cabinet 12', 'uploads/1757649662_Cabinet 12.jpg', NULL, '2025-09-12 04:01:02', '2025-09-12 04:01:11'),
(11, 'CAB20250013', 'Cabinet 13', NULL, NULL, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(12, 'CAB20250014', 'Cabinet 14', 'uploads/1757660350_Cabinet 14.jpg', NULL, '2025-09-12 06:59:10', '2025-09-12 06:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(6, 'Documents'),
(3, 'Electronics'),
(7, 'Equipment'),
(1, 'Files'),
(5, 'Stationery'),
(2, 'Supplies'),
(4, 'Tools');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `cabinet_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `cabinet_id`, `category_id`, `name`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 4, 1, '2019 Miscellaneous Files', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(2, 4, 1, 'Advisory Memorandum Regional Letter (March - Present 2018)', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(3, 4, 1, 'DepEd Advisory Letters/Notice of Meeting', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(4, 4, 1, 'Directory Report Data 2020', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(5, 4, 1, 'Early Registration Report (DATA)', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(6, 4, 1, 'Files CY 2020', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(7, 4, 1, 'IPCRF 2018', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(8, 4, 1, 'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2018)', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(9, 4, 1, 'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2019)', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(10, 4, 1, 'Miscellaneous CY 2020', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(11, 4, 1, 'Project Proposals (Purchase Request for BAC Action)', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(12, 4, 1, 'Recent Files/Data', 1, '2025-09-12 01:27:10', '2025-09-12 01:27:10'),
(13, 2, 2, 'Adventure Plastic Envelopes', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(14, 2, 2, 'Clear Book', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(15, 2, 2, 'Colored Plastic Envelopes', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(16, 2, 2, 'Everyready Batteries', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(17, 2, 2, 'ID Case', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(18, 2, 2, 'ID Lace', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(19, 2, 2, 'Kit Bag', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(20, 2, 2, 'Stapler Wire', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(21, 2, 2, 'Steno Advanced Notebooks', 1, '2025-09-12 01:27:55', '2025-09-12 01:27:55'),
(22, 3, 1, 'Basic Education Data', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(23, 3, 1, 'Elementary Public&Private', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(24, 3, 2, 'Batteries', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(25, 3, 2, 'Bond Papers', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(26, 3, 2, 'Books', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(27, 3, 2, 'Staples', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(28, 3, 2, 'Storage Files', 1, '2025-09-12 01:28:05', '2025-09-12 01:28:05'),
(29, 1, 1, 'Basic Education Data', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(30, 1, 1, 'DepEd Order', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(31, 1, 1, 'Documents about schools', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(32, 1, 1, 'Letter (to Persons)', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(33, 1, 1, 'Memorandum', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(34, 1, 1, 'Recent Files (2020-2023)', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(35, 1, 1, 'Referral Slips/List of Assignments', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(36, 1, 1, 'Regional Memorandum/Addendum', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(37, 1, 1, 'Research', 1, '2025-09-12 01:48:18', '2025-09-12 01:48:18'),
(38, 5, 2, 'Bond Papers (A3 & Legal Sizes)', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(39, 5, 2, 'Photo Papers', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(40, 5, 2, 'Masking Tape', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(41, 5, 2, 'Double Sided Tape', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(42, 5, 2, 'Jumbo and Small Paper Clips', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(43, 5, 2, 'Binder Clips', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(44, 5, 2, 'Staples', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(45, 5, 2, 'Air Fresheners', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(46, 5, 2, 'Steno Notebooks', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(47, 5, 2, 'Storage Box', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(48, 5, 2, 'Blades', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(49, 5, 2, 'Ballpens', 1, '2025-09-12 01:52:02', '2025-09-12 01:52:02'),
(50, 6, 1, 'Archiving', 1, '2025-09-12 01:57:13', '2025-09-12 01:57:13'),
(51, 6, 1, 'Implementation Monitoring', 1, '2025-09-12 01:57:13', '2025-09-12 01:57:13'),
(52, 6, 1, 'Dissemination of Research Result', 1, '2025-09-12 01:57:13', '2025-09-12 01:57:13'),
(53, 8, 1, 'DECS Office Directory', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(54, 8, 1, 'Directory Officials (2004-2011)', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(55, 8, 1, 'NEAT/INSAT Files (1999-2015)', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(56, 8, 1, 'Letter to School Divisions', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(57, 8, 1, 'Reports/Halth', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(58, 8, 1, 'Budget/OR', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(59, 8, 1, 'Letters to Persons', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(60, 8, 1, 'UDGS Files (1997-2002)', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(61, 8, 1, 'BEIS-PI', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(62, 8, 1, 'Orientation of Central Office and Regional Core Teams', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(63, 8, 1, 'National Action Plan', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(64, 8, 1, 'IQRs', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(65, 8, 1, 'DepEd National Planning Conference', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(66, 8, 1, 'Census', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(67, 8, 2, 'Magazines/CDs', 1, '2025-09-12 03:56:33', '2025-09-12 03:56:33'),
(68, 9, 2, 'Certificates', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(69, 9, 2, 'Rugs', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(70, 9, 2, 'Brown Envelope', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(71, 9, 2, 'Boxes', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(72, 9, 2, 'Folders', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(73, 9, 2, 'Manuscripts', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(74, 9, 2, 'Staples', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(75, 9, 2, 'Chlorine Dioxide', 1, '2025-09-12 03:59:18', '2025-09-12 03:59:18'),
(76, 10, 2, 'Cartridges for Printers', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(77, 10, 2, 'HP Invent Box', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(78, 10, 2, 'PVC Name Badges', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(79, 10, 2, 'Steno Notebooks', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(80, 10, 2, 'Toner Kits for Printers', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(81, 10, 2, 'Bookpapers', 1, '2025-09-12 04:01:02', '2025-09-12 04:01:02'),
(82, 7, 1, 'Research File Holders', 16, '2025-09-12 05:41:06', '2025-09-12 05:41:06'),
(83, 11, 1, 'Caloocan GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(84, 11, 1, 'Las Pinas', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(85, 11, 1, 'Makati GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(86, 11, 1, 'Malabon GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(87, 11, 1, 'Malabon / Navotas GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(88, 11, 1, 'Mandaluyong GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(89, 11, 1, 'Manila GESP', 1, '2025-09-12 06:57:14', '2025-09-12 06:57:14'),
(90, 12, 1, 'Quezon City / Manila', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(91, 12, 1, 'Quezon City (12)', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(92, 12, 1, 'Pasay / Manila GESP', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(93, 12, 1, 'Makati GESP', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(94, 12, 1, 'Quezon City GESP (3)', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(95, 12, 1, 'Taguig / Pateros GESP (3)', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10'),
(96, 12, 1, 'Valenzuela GESP (7)', 1, '2025-09-12 06:59:10', '2025-09-12 06:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `office` varchar(100) DEFAULT NULL,
  `division` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','encoder') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `office`, `division`, `email`, `mobile`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'System', 'Administrator', 'IT', 'Admin', 'admin@example.com', '1234567890', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-09-10 02:51:19'),
(2, 'Mico', 'Intertas', 'IT', 'PPRD', 'intertas.mico.dichoso@gmail.com', '12345678901', 'Encoder', '$2y$10$DpBOVvNSRPcbGZCvACKP1e71VKkRQF96GMCM2JwdXDAtDHfTW66o.', 'encoder', '2025-09-11 00:47:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cabinets`
--
ALTER TABLE `cabinets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cabinet_number` (`cabinet_number`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabinet_id` (`cabinet_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cabinets`
--
ALTER TABLE `cabinets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
