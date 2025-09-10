-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 09:40 AM
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
(1, 'CAB20250001', 'Cabinet 1', 'uploads/1757487559_Cabinet 1.jpg', '', '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(2, 'CAB20250002', 'Cabinet 2', 'uploads/1757488135_Cabinet 2.jpg', '', '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(3, 'CAB20250003', 'Cabinet 3', 'uploads/1757488329_Cabinet 3.jpg', '', '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(4, 'CAB20250004', 'Cabinet 4', 'uploads/1757489015_Cabinet 4.jpg', '', '2025-09-10 07:23:35', '2025-09-10 07:23:35');

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
(1, 1, 1, 'Memorandum', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(2, 1, 1, 'Documents about schools', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(3, 1, 1, 'Referral Slips/List of Assignments', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(4, 1, 1, 'Letters (to Persons)', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(5, 1, 1, 'DepEd Order', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(6, 1, 1, 'Recent Files (2020-2023)', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(7, 1, 1, 'Basic Education Data', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(8, 1, 1, 'Research', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(9, 1, 1, 'Regional Memorandum/Addendum', 1, '2025-09-10 06:59:19', '2025-09-10 06:59:19'),
(10, 2, 2, 'Adventure plastic envelopes', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(11, 2, 2, 'Steno advanced notebooks', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(12, 2, 2, 'Everyready Batteries', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(13, 2, 2, 'Clear Book', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(14, 2, 2, 'Colored Plastic Envelopes', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(15, 2, 2, 'Stapler Wire', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(16, 2, 2, 'ID Lace', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(17, 2, 2, 'ID Case', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(18, 2, 2, 'Kit Bag', 1, '2025-09-10 07:08:55', '2025-09-10 07:08:55'),
(19, 3, 2, 'Batteries', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(20, 3, 2, 'Bond Papers', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(21, 3, 2, 'Staples', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(22, 3, 2, 'Books', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(23, 3, 2, 'Storage Files', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(24, 3, 1, 'Basic Education Data', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(25, 3, 1, 'Elementary Public&amp;Private', 1, '2025-09-10 07:12:09', '2025-09-10 07:12:09'),
(26, 4, 1, 'Advisory Memorandum Regional Letter (March - Present 2018)', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(27, 4, 1, 'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2018)', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(28, 4, 1, 'IPCRF 2018', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(29, 4, 1, 'Project Proposals (Purchase Request for BAC Action)', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(30, 4, 1, 'Early Registration Report (DATA)', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(31, 4, 1, 'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2019)', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(32, 4, 1, '2019 Miscellaneous Files', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(33, 4, 1, 'Miscellaneous CY 2020', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(34, 4, 1, 'Files CY 2020', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(35, 4, 1, 'Directory Report Data 2020', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(36, 4, 1, 'Recent Files/Data', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35'),
(37, 4, 1, 'DepEd Advisory Letters/Notice of Meeting', 1, '2025-09-10 07:23:35', '2025-09-10 07:23:35');

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
(1, 'System', 'Administrator', 'IT', 'Admin', 'admin@example.com', '1234567890', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-09-10 02:51:19');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
