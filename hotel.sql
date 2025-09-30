-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 10:46 AM
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
-- Database: `hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `grn`
--

CREATE TABLE `grn` (
  `grn_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Hotel Supplies','Foods & Beverages','Cleaning & Sanitation','Utility Products','Office Supplies','Kitchen Equipment','Furniture & Fixtures','Laundry & Linen','Others') NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `inspected_by` varchar(255) NOT NULL,
  `condition_status` enum('Good','Damaged','Expired') NOT NULL,
  `notes` text DEFAULT NULL,
  `date_received` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`guest_id`, `first_name`, `last_name`, `email`, `first_phone`, `second_phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Michael', 'Thompson', 'michael.thompson@email.com', '+63-917-111-2222', '+63-918-333-4444', 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'Jessica', 'Martinez', 'jessica.martinez@email.com', '+63-919-555-6666', NULL, 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'David', 'Lee', 'david.lee@email.com', '+63-920-777-8888', '+63-921-999-0000', 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'Sophie', 'Brown', 'sophie.brown@email.com', '+63-917-222-3333', NULL, 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Carlos', 'Garcia', 'carlos.garcia@email.com', '+63-918-444-5555', '+63-919-666-7777', 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(6, 'Emily', 'Wilson', 'emily.wilson@email.com', '+63-920-888-9999', NULL, 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(7, 'James', 'Anderson', 'james.anderson@email.com', '+63-917-123-9876', NULL, 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(8, 'Lisa', 'Taylor', 'lisa.taylor@email.com', '+63-918-987-6543', '+63-919-456-7890', 'active', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

--
-- Triggers `guests`
--
DELIMITER $$
CREATE TRIGGER `guests_before_update` BEFORE UPDATE ON `guests` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Hotel Supplies','Foods & Beverages','Cleaning & Sanitation','Utility Products','Office Supplies','Kitchen Equipment','Furniture & Fixtures','Laundry & Linen','Others') NOT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `used_qty` int(11) DEFAULT 0,
  `wasted_qty` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `inspected_by` varchar(255) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `quantity_in_stock`, `used_qty`, `wasted_qty`, `unit_price`, `inspected_by`, `last_updated`, `created_at`) VALUES
(1, 'Toilet Paper Rolls', 'Hotel Supplies', 150, 25, 2, 45.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'Bath Towels', 'Laundry & Linen', 75, 15, 1, 350.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'Coffee Beans (1kg)', 'Foods & Beverages', 20, 8, 0, 580.00, 'Linda Cruz', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'All-Purpose Cleaner', 'Cleaning & Sanitation', 45, 12, 1, 125.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Printer Paper (Ream)', 'Office Supplies', 30, 5, 0, 250.00, 'Mark Rivera', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(6, 'Bed Sheets (Queen)', 'Laundry & Linen', 40, 8, 2, 450.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(7, 'Hand Soap Dispensers', 'Cleaning & Sanitation', 25, 3, 0, 180.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(8, 'Light Bulbs (LED)', 'Utility Products', 60, 15, 3, 95.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(9, 'Shampoo Bottles', 'Hotel Supplies', 80, 20, 1, 85.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(10, 'Kitchen Knives Set', 'Kitchen Equipment', 12, 0, 0, 1250.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(11, 'Vacuum Cleaner Bags', 'Cleaning & Sanitation', 35, 8, 0, 75.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(12, 'Pillows', 'Laundry & Linen', 50, 10, 2, 280.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(13, 'Floor Wax', 'Cleaning & Sanitation', 18, 4, 0, 320.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(14, 'Receipt Rolls', 'Office Supplies', 100, 25, 1, 35.00, 'Linda Cruz', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(15, 'Air Fresheners', 'Hotel Supplies', 42, 8, 0, 65.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Hotel Supplies','Foods & Beverages','Cleaning & Sanitation','Utility Products','Office Supplies','Kitchen Equipment','Furniture & Fixtures','Laundry & Linen','Others') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('pending','received','partially_received','cancelled') DEFAULT 'pending',
  `received_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `condition_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `supplier_id`, `po_number`, `item_name`, `category`, `quantity`, `unit_price`, `total_amount`, `order_date`, `status`, `received_date`, `created_at`, `updated_at`, `condition_status`) VALUES
(7, 2, 'PO-20250909-210', 'bedsheets', 'Laundry & Linen', 50, NULL, 6500.00, '2025-09-09', 'pending', NULL, '2025-09-09 08:45:55', '2025-09-09 08:45:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `guest_id`, `room_id`, `status`, `remarks`, `check_in`, `check_out`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'confirmed', 'Business traveler', '2024-09-08 14:00:00', '2024-09-12 11:00:00', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 2, 4, 'pending', 'Anniversary celebration', '2024-09-15 15:00:00', '2024-09-18 12:00:00', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 3, 5, 'confirmed', 'Family vacation', '2024-09-20 14:00:00', '2024-09-25 11:00:00', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 4, 9, 'confirmed', 'Conference attendee', '2024-09-25 16:00:00', '2024-09-27 10:00:00', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

--
-- Triggers `reservations`
--
DELIMITER $$
CREATE TRIGGER `reservations_before_update` BEFORE UPDATE ON `reservations` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_calendar`
--

CREATE TABLE `reservation_calendar` (
  `calendar_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('reserved','occupied','maintenance','checked_in','checked_out','extend_stay','note') NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_calendar`
--

INSERT INTO `reservation_calendar` (`calendar_id`, `room_id`, `guest_id`, `event_date`, `event_type`, `note`) VALUES
(1, 2, 1, '2024-09-08', 'checked_in', 'Guest arrived early, room ready'),
(2, 1, 5, '2024-09-07', 'checked_in', 'Walk-in guest, extended stay requested'),
(3, 3, 6, '2024-09-08', 'checked_in', 'Late check-in, no issues'),
(4, 6, NULL, '2024-09-08', 'maintenance', 'AC unit repair scheduled'),
(5, 4, 2, '2024-09-15', 'reserved', 'Anniversary package requested'),
(6, 5, 3, '2024-09-20', 'reserved', 'Family suite, extra bedding needed');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` enum('Single Room','Double Room','Twin Room','Deluxe Room','Suite','Family Room') NOT NULL,
  `max_occupancy` int(11) DEFAULT NULL,
  `price_rate` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','reserved','under maintenance','dirty') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `room_type`, `max_occupancy`, `price_rate`, `status`, `created_at`, `updated_at`) VALUES
(1, '101', 'Single Room', 1, 2500.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, '102', 'Double Room', 2, 3500.00, 'occupied', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, '103', 'Twin Room', 2, 3500.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, '201', 'Deluxe Room', 2, 4500.00, 'reserved', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, '202', 'Suite', 4, 7500.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(6, '203', 'Family Room', 6, 6500.00, 'under maintenance', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(7, '301', 'Single Room', 1, 2500.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(8, '302', 'Double Room', 2, 3500.00, 'dirty', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(9, '303', 'Deluxe Room', 2, 4500.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(10, '401', 'Suite', 4, 8000.00, 'available', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

--
-- Triggers `rooms`
--
DELIMITER $$
CREATE TRIGGER `rooms_before_update` BEFORE UPDATE ON `rooms` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `last_name`, `position`, `department`, `email`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'Mark', 'Rivera', 'Front Desk Manager', 'Reception', 'mark.rivera@hotel.com', '+63-917-100-2001', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'Grace', 'Santos', 'Housekeeping Supervisor', 'Housekeeping', 'grace.santos@hotel.com', '+63-918-200-3002', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'Tony', 'Mendoza', 'Maintenance Chief', 'Maintenance', 'tony.mendoza@hotel.com', '+63-919-300-4003', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'Linda', 'Cruz', 'Cashier', 'POS', 'linda.cruz@hotel.com', '+63-920-400-5004', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Peter', 'Reyes', 'Room Attendant', 'Housekeeping', 'peter.reyes@hotel.com', '+63-921-500-6005', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(6, 'Mary', 'Gonzales', 'Receptionist', 'Reception', 'mary.gonzales@hotel.com', '+63-917-600-7006', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(7, 'Jose', 'Hernandez', 'Maintenance Worker', 'Maintenance', 'jose.hernandez@hotel.com', '+63-918-700-8007', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(8, 'Anna', 'Flores', 'Room Service', 'POS', 'anna.flores@hotel.com', '+63-919-800-9008', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `stock_usage`
--

CREATE TABLE `stock_usage` (
  `usage_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `used_qty` int(11) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `date_used` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_usage`
--

INSERT INTO `stock_usage` (`usage_id`, `item_id`, `used_qty`, `used_by`, `date_used`) VALUES
(1, 1, 15, 'Housekeeping', '2024-09-01 08:30:00'),
(2, 2, 8, 'Housekeeping', '2024-09-01 10:15:00'),
(3, 3, 3, 'POS', '2024-09-02 06:45:00'),
(4, 4, 5, 'Housekeeping', '2024-09-02 14:20:00'),
(5, 5, 2, 'POS', '2024-09-03 09:00:00'),
(6, 6, 4, 'Housekeeping', '2024-09-03 16:30:00'),
(7, 7, 2, 'Housekeeping', '2024-09-04 11:45:00'),
(8, 8, 8, 'Maintenance', '2024-09-04 13:15:00'),
(9, 9, 12, 'Housekeeping', '2024-09-05 07:30:00'),
(10, 11, 4, 'Housekeeping', '2024-09-05 15:00:00'),
(11, 12, 6, 'Housekeeping', '2024-09-06 12:20:00'),
(12, 13, 2, 'Maintenance', '2024-09-06 14:45:00'),
(13, 14, 15, 'POS', '2024-09-07 08:00:00'),
(14, 15, 5, 'Housekeeping', '2024-09-07 16:15:00'),
(15, 1, 10, 'Housekeeping', '2024-09-08 09:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CleanCorp Supplies', 'Maria Santos', 'maria@cleancorp.com', '+63-917-123-4567', '123 Makati Ave, Makati City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'FreshFood Distributors', 'Juan Dela Cruz', 'juan@freshfood.ph', '+63-918-234-5678', '456 Quezon Blvd, Quezon City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'HotelMax Equipment', 'Sarah Johnson', 'sarah@hotelmax.com', '+63-919-345-6789', '789 BGC Drive, Taguig City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'Metro Office Solutions', 'Robert Kim', 'robert@metrooffice.ph', '+63-920-456-7890', '321 Ortigas Center, Pasig City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Premium Linens Co.', 'Ana Rodriguez', 'ana@premiumlinens.com', '+63-921-567-8901', '654 Shaw Blvd, Mandaluyong City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `walk_ins`
--

CREATE TABLE `walk_ins` (
  `walk_in_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `expected_check_out` datetime NOT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `status` enum('checked_in','checked_out') DEFAULT 'checked_in',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `walk_ins`
--

INSERT INTO `walk_ins` (`walk_in_id`, `guest_id`, `room_id`, `check_in_time`, `expected_check_out`, `actual_check_out`, `status`, `payment_status`, `total_amount`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 5, 1, '2024-09-07 18:30:00', '2024-09-09 11:00:00', NULL, 'checked_in', 'paid', 5000.00, NULL, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 6, 3, '2024-09-08 20:15:00', '2024-09-10 12:00:00', NULL, 'checked_in', 'partial', 7000.00, NULL, '2025-09-09 08:42:18', '2025-09-09 08:42:18');

--
-- Triggers `walk_ins`
--
DELIMITER $$
CREATE TRIGGER `walk_ins_before_update` BEFORE UPDATE ON `walk_ins` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grn`
--
ALTER TABLE `grn`
  ADD PRIMARY KEY (`grn_id`),
  ADD KEY `idx_po_id` (`po_id`),
  ADD KEY `idx_date_received` (`date_received`),
  ADD KEY `idx_condition` (`condition_status`),
  ADD KEY `idx_inspector` (`inspected_by`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_stock_level` (`quantity_in_stock`),
  ADD KEY `idx_item_category` (`item_name`,`category`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_po_number` (`po_number`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `reservation_calendar`
--
ALTER TABLE `reservation_calendar`
  ADD PRIMARY KEY (`calendar_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `guest_id` (`guest_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `idx_name` (`first_name`,`last_name`);

--
-- Indexes for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD PRIMARY KEY (`usage_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `walk_ins`
--
ALTER TABLE `walk_ins`
  ADD PRIMARY KEY (`walk_in_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grn`
--
ALTER TABLE `grn`
  MODIFY `grn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservation_calendar`
--
ALTER TABLE `reservation_calendar`
  MODIFY `calendar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock_usage`
--
ALTER TABLE `stock_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `walk_ins`
--
ALTER TABLE `walk_ins`
  MODIFY `walk_in_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grn`
--
ALTER TABLE `grn`
  ADD CONSTRAINT `grn_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `reservation_calendar`
--
ALTER TABLE `reservation_calendar`
  ADD CONSTRAINT `reservation_calendar_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `reservation_calendar_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`);

--
-- Constraints for table `walk_ins`
--
ALTER TABLE `walk_ins`
  ADD CONSTRAINT `walk_ins_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `walk_ins_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
