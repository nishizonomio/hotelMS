-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 03:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('email','sms','both') DEFAULT 'email',
  `target_audience` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('draft','scheduled','active','completed') DEFAULT 'draft',
  `schedule` datetime DEFAULT NULL,
  `sent_count` int(11) DEFAULT 0,
  `open_rate` decimal(5,2) DEFAULT 0.00,
  `click_rate` decimal(5,2) DEFAULT 0.00,
  `created_by_user` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `name`, `description`, `type`, `target_audience`, `message`, `status`, `schedule`, `sent_count`, `open_rate`, `click_rate`, `created_by_user`, `created_at`, `updated_at`) VALUES
(1, 'Summer Welcome Package', 'Special welcome offer for summer guests', 'email', 'all', 'Welcome to our hotel! Enjoy complimentary breakfast with your summer stay.', 'active', NULL, 1250, 68.50, 24.30, NULL, '2025-09-13 02:16:35', '2025-09-13 08:31:19'),
(2, 'Loyalty Member Rewards', 'Exclusive rewards for platinum members', 'email', 'platinum', 'As a valued platinum member, enjoy these exclusive perks during your stay!', 'active', NULL, 245, 75.20, 32.10, NULL, '2025-09-13 02:16:35', '2025-09-13 03:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `guest_name` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `type` enum('complaint','suggestion','compliment') DEFAULT 'complaint',
  `rating` int(1) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `status` enum('pending','in-progress','resolved','dismissed') DEFAULT 'pending',
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `guest_name` varchar(255) NOT NULL,
  `type` enum('review','service_feedback') DEFAULT 'review',
  `rating` int(1) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `message` text NOT NULL,
  `comment` text GENERATED ALWAYS AS (`message`) STORED,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_phone` varchar(20) DEFAULT NULL,
  `second_phone` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `loyalty_tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `location` varchar(255) DEFAULT 'Unknown',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_programs`
--

CREATE TABLE `loyalty_programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tier` enum('bronze','silver','gold','platinum') NOT NULL,
  `points_rate` decimal(4,2) NOT NULL DEFAULT 1.00,
  `benefits` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `members_count` int(11) DEFAULT 0,
  `points_redeemed` int(11) DEFAULT 0,
  `rewards_given` int(11) DEFAULT 0,
  `revenue_impact` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `loyalty_programs`
--
ALTER TABLE `loyalty_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_tier` (`tier`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `loyalty_programs`
--
ALTER TABLE `loyalty_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
