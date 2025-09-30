-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2025 at 11:54 PM
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
-- Table structure for table `pos_charges`
--

CREATE TABLE `pos_charges` (
  `pos_charge_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `source_module` enum('Restaurant','Minibar','InRoomDining','GiftShop','Bar') NOT NULL,
  `source_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `charge_date` datetime DEFAULT current_timestamp(),
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
