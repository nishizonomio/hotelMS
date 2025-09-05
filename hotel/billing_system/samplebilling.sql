-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2025 at 03:55 PM
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
-- Database: `samplebilling`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `booking_type` enum('reservation','walk-in') NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `check_in` timestamp NULL DEFAULT NULL,
  `check_out` timestamp NULL DEFAULT NULL,
  `booking_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `guest_id`, `room_id`, `booking_type`, `status`, `remarks`, `check_in`, `check_out`, `booking_date`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 'reservation', 'checked_in', NULL, '2025-09-03 01:01:40', '2025-09-04 01:01:40', '2025-09-02', '2025-09-03 01:03:59', '2025-09-03 01:03:59'),
(2, 1, 1, 'walk-in', 'checked_in', NULL, '2025-09-03 01:01:40', '2025-09-05 01:01:40', '2025-09-02', '2025-09-03 01:03:59', '2025-09-03 01:03:59');

-- --------------------------------------------------------

--
-- Table structure for table `folio_transactions`
--

CREATE TABLE `folio_transactions` (
  `transaction_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `service_type` enum('inroom','restaurant','minibar','giftshop','bar') DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `transaction_time` time DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_billing`
--

CREATE TABLE `group_billing` (
  `group_billing_id` int(11) NOT NULL,
  `group_name` varchar(100) DEFAULT NULL,
  `total_group_amount` decimal(10,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_billing_members`
--

CREATE TABLE `group_billing_members` (
  `group_billing_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `share_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `first_phone` varchar(50) DEFAULT NULL,
  `second_phone` varchar(50) DEFAULT NULL,
  `status` enum('regular','vip','banned') DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`guest_id`, `first_name`, `last_name`, `email`, `first_phone`, `second_phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Russell Ike', 'Cabrido', 'russcabz28@gmail.com', '09324534987', '09213654781', 'regular', '2025-09-03 00:55:00', '2025-09-03 00:55:00'),
(2, 'Ian Charles', 'Gohetia', 'iancharles@gmail.com', '09786574312', '09386531298', 'regular', '2025-09-03 00:55:00', '2025-09-03 00:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `additionalCharges` int(10) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_time` time DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('unpaid','paid','cancelled','refunded') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `booking_id`, `additionalCharges`, `invoice_date`, `invoice_time`, `total_amount`, `status`) VALUES
(1, 1, 0, '2025-09-03', '15:04:22', 5000.00, 'paid'),
(2, 2, 0, '2025-09-03', '15:57:57', 0.00, ''),
(3, NULL, 0, NULL, NULL, NULL, NULL),
(4, NULL, 0, NULL, NULL, NULL, NULL),
(5, 1, 0, NULL, NULL, NULL, NULL),
(6, 1, 0, NULL, NULL, NULL, NULL),
(7, 1, 0, NULL, NULL, NULL, NULL),
(8, 1, 0, '2025-09-04', '10:44:25', NULL, NULL),
(9, 1, 0, '2025-09-04', '10:44:43', 6000.00, NULL),
(10, 1, 0, '2025-09-04', '10:47:24', 6000.00, NULL),
(11, 1, 0, '2025-09-04', '10:47:55', 6000.00, NULL),
(12, 1, 0, '2025-09-04', '10:48:26', 6000.00, NULL),
(13, 1, 0, '2025-09-04', '10:50:55', 6000.00, 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `group_billing_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','credit_card','debit_card','gcash','bank_transfer') DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `invoice_id`, `group_billing_id`, `payment_method`, `amount_paid`, `payment_date`, `payment_time`) VALUES
(1, 13, NULL, 'gcash', 5000.00, '2025-09-05', '22:23:47');

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_transactions`
--

CREATE TABLE `payment_gateway_transactions` (
  `transaction_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `gateway_name` varchar(50) DEFAULT NULL,
  `response_code` varchar(20) DEFAULT NULL,
  `response_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_method` enum('cash','credit_card','debit_card','gcash','bank_transfer') DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` int(11) NOT NULL,
  `room_types` enum('Single Room','Double Room','Twin Room','Deluxe Room','Suite','Family \r\nRoom','VIPRoom') DEFAULT NULL,
  `max_occupancy` int(11) NOT NULL,
  `room_price` decimal(10,2) NOT NULL,
  `status` enum('available','reserved','occupied','to be clean','under maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `room_types`, `max_occupancy`, `room_price`, `status`) VALUES
(1, 302, 'Single Room', 2, 4300.00, 'available'),
(2, 501, 'Deluxe Room', 1, 6000.00, 'available');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `group_billing`
--
ALTER TABLE `group_billing`
  ADD PRIMARY KEY (`group_billing_id`);

--
-- Indexes for table `group_billing_members`
--
ALTER TABLE `group_billing_members`
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `group_billing_id` (`group_billing_id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `group_billing_id` (`group_billing_id`);

--
-- Indexes for table `payment_gateway_transactions`
--
ALTER TABLE `payment_gateway_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`refund_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_billing`
--
ALTER TABLE `group_billing`
  MODIFY `group_billing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_gateway_transactions`
--
ALTER TABLE `payment_gateway_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  ADD CONSTRAINT `folio_transactions_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`);

--
-- Constraints for table `group_billing_members`
--
ALTER TABLE `group_billing_members`
  ADD CONSTRAINT `group_billing_members_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`),
  ADD CONSTRAINT `group_billing_members_ibfk_2` FOREIGN KEY (`group_billing_id`) REFERENCES `group_billing` (`group_billing_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`group_billing_id`) REFERENCES `group_billing` (`group_billing_id`);

--
-- Constraints for table `payment_gateway_transactions`
--
ALTER TABLE `payment_gateway_transactions`
  ADD CONSTRAINT `payment_gateway_transactions_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
