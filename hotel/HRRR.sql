-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2025 at 04:27 PM
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
-- Database: `hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('Payroll Reminders','HR & Policy Updates','Holidays & Events','') DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `category`, `content`, `created_at`, `updated_at`) VALUES
(3, 'Payroll Cut-off Reminder', 'Payroll Reminders', 'All timesheets must be submitted before Friday, 5 PM.', '2025-09-26 10:19:23', NULL),
(4, 'Policy Update: Remote Work', 'HR & Policy Updates', 'Employees are allowed 2 days of remote work per week effective October 1, 2025.', '2025-09-26 10:19:23', NULL),
(5, 'Team Building Event', 'Holidays & Events', 'Join us for the annual team building on November 15 at Batangas Beach Resort.', '2025-09-26 10:19:23', NULL),
(6, 'Christmas Party', 'Holidays & Events', 'The company Christmas party will be held on December 20, 2025 at the Grand Ballroom.', '2025-09-26 10:19:23', NULL),
(7, 'General Notice', 'HR & Policy Updates', 'Please check your emails regularly for updates from management.', '2025-09-26 10:19:23', NULL),
(13, 'Overtime Submission Deadline', 'Payroll Reminders', 'Submit overtime logs before October 3, 2025.', '2025-09-26 10:28:32', NULL),
(14, '13th Month Pay Update', 'Payroll Reminders', '13th month pay will be credited on December 10, 2025.', '2025-09-26 10:28:32', NULL),
(15, 'Payslip Availability', 'Payroll Reminders', 'Payslips will be available online starting October 5, 2025.', '2025-09-26 10:28:32', NULL),
(16, 'Payroll System Maintenance', 'Payroll Reminders', 'Payroll system will undergo maintenance on October 7, 2025.', '2025-09-26 10:28:32', NULL),
(17, 'New Leave Policy', 'HR & Policy Updates', 'Employees can now carry over 5 unused leaves to the next year.', '2025-09-26 10:28:32', NULL),
(18, 'Dress Code Reminder', 'HR & Policy Updates', 'Formal attire is required every Monday.', '2025-09-26 10:28:32', NULL),
(19, 'Employee Handbook Revision', 'HR & Policy Updates', 'A revised employee handbook will be released on October 15, 2025.', '2025-09-26 10:28:32', '2025-09-26 10:37:54'),
(20, 'Work From Home Guidelines', 'HR & Policy Updates', 'WFH employees must log attendance by 10:00 AM daily.', '2025-09-26 10:28:32', '2025-09-26 16:00:02'),
(21, 'HR Helpdesk Hours', 'HR & Policy Updates', 'HR Helpdesk is now open from 8:00 AM to 6:00 PM.', '2025-09-26 10:28:32', NULL),
(22, 'Halloween Celebration', 'Holidays & Events', 'A Halloween party will be held on October 31, 2025 at the main hall.', '2025-09-26 10:28:32', NULL),
(23, 'Wellness Day', 'Holidays & Events', 'Wellness activities are scheduled every Friday morning this October.', '2025-09-26 10:28:32', NULL),
(24, 'Volunteer Program', 'Holidays & Events', 'Join the company volunteer outreach on October 20, 2025.', '2025-09-26 10:28:32', NULL),
(25, 'Sports Fest 2025', 'Holidays & Events', 'Annual sports fest will be on November 8-9, 2025.', '2025-09-26 10:28:32', NULL),
(26, 'New Year Kick-off', 'Holidays & Events', 'Kick-off celebration for 2026 will be held on January 3, 2026.', '2025-09-26 10:28:32', NULL),
(27, 'Salary Release Notice', 'Payroll Reminders', 'September salary will be released on October 2, 2025.', '2025-09-26 16:00:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_holiday` tinyint(1) DEFAULT 0,
  `holiday_percentage` decimal(5,2) DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `staff_id`, `attendance_date`, `time_in`, `time_out`, `status`, `start_time`, `end_time`, `is_holiday`, `holiday_percentage`) VALUES
(1, 'EMP344096', '2025-09-23', '02:14:20', '02:14:33', 'Present', NULL, NULL, 0, 100.00),
(2, 'EMP5181995', '2025-09-23', '02:14:22', '08:52:30', 'Present', NULL, NULL, 0, 100.00),
(3, 'EMP', '2025-09-26', '09:10:00', '10:15:43', 'Present', NULL, NULL, 0, 100.00),
(4, 'EMP490735', '2025-09-26', '01:51:23', NULL, 'Present', NULL, NULL, 0, 100.00),
(5, 'EMP636819', '2025-09-27', '08:15:24', NULL, 'Present', NULL, NULL, 0, 100.00),
(6, 'EMP5181995', '2026-01-01', '02:30:08', '08:30:00', 'Present', NULL, NULL, 0, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `bonuses_incentives`
--

CREATE TABLE `bonuses_incentives` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `type` enum('Bonus','Incentive') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bonuses_incentives`
--

INSERT INTO `bonuses_incentives` (`id`, `staff_id`, `type`, `amount`, `created_at`) VALUES
(9, 'EMP490735', 'Bonus', 200.00, '2025-09-27 14:41:26'),
(10, 'EMP490735', 'Incentive', 5000.00, '2025-09-27 14:41:34'),
(11, 'EMP490735', 'Incentive', 500.00, '2025-09-27 14:43:37'),
(13, 'EMP910260', 'Incentive', 2000.00, '2025-09-27 15:24:02'),
(14, 'EMP52269643', 'Bonus', 500.00, '2025-09-27 19:27:23'),
(15, 'EMP5181995', 'Incentive', 1000.00, '2025-09-27 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `ceo`
--

CREATE TABLE `ceo` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `position_name` varchar(50) NOT NULL DEFAULT 'CEO',
  `department_name` varchar(50) NOT NULL DEFAULT 'Executive',
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `base_salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ceo`
--

INSERT INTO `ceo` (`id`, `staff_id`, `first_name`, `last_name`, `position_name`, `department_name`, `photo`, `email`, `phone`, `address`, `hire_date`, `base_salary`) VALUES
(1, 'CEO', 'Monica', 'Montes', 'CEO', 'Executive', 'ceo.jpg', 'alice.garcia@hotel.com', '+639171234567', '123 Ayala Avenue, Makati City, Philippines', '2020-01-01', 150000.00);

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reason_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deductions`
--

INSERT INTO `deductions` (`id`, `staff_id`, `amount`, `reason_type`, `description`, `proof_image`, `month`, `year`, `created_at`) VALUES
(1, 'EMP910260', 200.00, 'Cash Advance', 'dddd', 'C:/xampp/htdocs/hotel/hr/payroll/uploads/deductions/EMP910260_1758963591_7d6de32675c42d8916772b6dd0b775ae.jpg', 9, 2025, '2025-09-27 08:59:51'),
(2, 'EMP636819', 200.00, 'Penalty', 'ddd', 'C:/xampp/htdocs/hotel/hr/payroll/uploads/deductions/EMP636819_1758970685_51d16e03414aee66bedc4281a8dd5905.jpg', 9, 2025, '2025-09-27 10:58:05'),
(3, 'EMP636819', 200.00, 'Loan', 'aaa', 'C:\\xampp\\htdocs\\hotel\\hr\\agreements\\EMP636819_1758972067_51d16e03414aee66bedc4281a8dd5905.jpg', 9, 2025, '2025-09-27 11:21:07'),
(4, 'EMP723571', 50.00, 'Penalty', 'sss', 'C:\\xampp\\htdocs\\hotel\\hr\\agreements\\EMP723571_1759037870_logo.png', 9, 2025, '2025-09-28 05:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`) VALUES
(1, 'Front Office'),
(2, 'Housekeeping'),
(3, 'Food & Beverage Service'),
(4, 'Kitchen / Food Production'),
(5, 'Engineering / Maintenance'),
(6, 'Security'),
(7, 'Sales & Marketing'),
(8, 'Finance & Accounting'),
(9, 'Human Resources'),
(10, 'Recreation / Spa / Leisure'),
(11, 'Events & Banquets');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `sss_no` varchar(20) DEFAULT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL,
  `pagibig_no` varchar(20) DEFAULT NULL,
  `tin_no` varchar(20) DEFAULT NULL,
  `nbi_clearance` varchar(255) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `diploma` varchar(255) DEFAULT NULL,
  `tor` varchar(255) DEFAULT NULL,
  `barangay_clearance` varchar(255) DEFAULT NULL,
  `police_clearance` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_documents`
--

INSERT INTO `employee_documents` (`id`, `staff_id`, `contract_file`, `sss_no`, `philhealth_no`, `pagibig_no`, `tin_no`, `nbi_clearance`, `birth_certificate`, `diploma`, `tor`, `barangay_clearance`, `police_clearance`) VALUES
(0, 'EMPGEN001', NULL, '', '', '', '', NULL, 'uploads/68d2eb2db5509_68c599c72b6d8_68c060d7707cd_24cb9_birth-certificate.jpg', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `percentage` decimal(5,2) DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `date`, `created_at`, `percentage`) VALUES
(1, 'New Year\'s Day', '2026-01-01', '2025-09-28 06:23:40', 50.00),
(2, 'Maundy Thursday', '2026-04-17', '2025-09-28 06:23:40', 100.00),
(3, 'Good Friday', '2026-04-18', '2025-09-28 06:23:40', 50.00),
(4, 'Labor Day', '2026-05-01', '2025-09-28 06:23:40', 100.00),
(5, 'Independence Day', '2026-06-12', '2025-09-28 06:23:40', 100.00),
(6, 'National Heroes Day', '2026-08-25', '2025-09-28 06:23:40', 100.00),
(7, 'Christmas Day', '2025-12-25', '2025-09-28 06:23:40', 100.00),
(8, 'Boxing Day', '2025-12-26', '2025-09-28 06:23:40', 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `job_experience`
--

CREATE TABLE `job_experience` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `staff_id`, `start_date`, `end_date`, `status`, `reason`, `created_at`) VALUES
(0, 'EMPGEN001', '2025-09-24', '2025-09-25', 'Approved', 'Sick', '2025-09-23 18:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `orientations`
--

CREATE TABLE `orientations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orientations`
--

INSERT INTO `orientations` (`id`, `title`, `description`, `date`, `time`) VALUES
(1, 'Workplace Safety', 'Learn safety rules and emergency protocols.', '2025-09-15', '09:00:00'),
(2, 'Customer Service Training', 'Orientation for excellent customer service.', '2025-09-16', '13:00:00'),
(3, 'Fire Drill & Emergency Evacuation', 'Practice emergency evacuation procedures.', '2025-09-17', '10:00:00'),
(4, 'Workplace Safety', 'Learn safety rules and emergency protocols.', '2025-09-15', '09:00:00'),
(5, 'Customer Service Training', 'Orientation for excellent customer service.', '2025-09-16', '13:00:00'),
(6, 'Fire Drill & Emergency Evacuation', 'Practice emergency evacuation procedures.', '2025-09-17', '10:00:00'),
(7, 'Hotel Policies Overview', 'Introduction to hotel policies and standards.', '2025-09-18', '09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `orientation_attendance`
--

CREATE TABLE `orientation_attendance` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `orientation_id` int(11) NOT NULL,
  `status` enum('Pending','Attended') DEFAULT 'Pending',
  `attended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orientation_attendance`
--

INSERT INTO `orientation_attendance` (`id`, `staff_id`, `orientation_id`, `status`, `attended_at`) VALUES
(0, 'EMP180969', 1, 'Attended', '2025-09-23 20:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `overtime`
--

CREATE TABLE `overtime` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `overtime_date` date NOT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 125.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime`
--

INSERT INTO `overtime` (`id`, `staff_id`, `overtime_date`, `hours`, `percentage`) VALUES
(0, 'EMP636819', '2025-09-23', 2, 125.00),
(0, 'EMP636819', '2025-09-23', 2, 125.00),
(0, 'EMP490735', '2025-09-23', 2, 125.00),
(0, 'EMP', '2025-09-26', 3, 125.00),
(0, 'EMP5181995', '2025-09-27', 3, 125.00);

-- --------------------------------------------------------

--
-- Table structure for table `payslip`
--

CREATE TABLE `payslip` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `sss` decimal(10,2) DEFAULT 0.00,
  `philhealth` decimal(10,2) DEFAULT 0.00,
  `pagibig` decimal(10,2) DEFAULT 0.00,
  `withholding_tax` decimal(10,2) DEFAULT 0.00,
  `other_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `pdf_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslip`
--

INSERT INTO `payslip` (`id`, `staff_id`, `month`, `year`, `amount`, `status`, `sss`, `philhealth`, `pagibig`, `withholding_tax`, `other_deduction`, `total_deductions`, `net_salary`, `pdf_file`) VALUES
(2, 'EMP104006', '09', 2025, 0.00, 'paid', 1125.00, 687.50, 100.00, 1000.00, 0.00, 2912.50, 22087.50, 'EMP104006_September.pdf'),
(3, 'EMP128638', '09', 2025, 0.00, 'paid', 810.00, 495.00, 100.00, 0.00, 0.00, 1405.00, 16595.00, 'EMP128638_September.pdf'),
(4, 'EMP132824', '09', 2025, 0.00, 'paid', 990.00, 605.00, 100.00, 400.00, 0.00, 2095.00, 19905.00, 'EMP132824_September.pdf'),
(5, 'EMP143950', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP143950_September.pdf'),
(6, 'EMP180969', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP180969_September.pdf'),
(7, 'EMP222430', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP222430_September.pdf'),
(8, 'EMP234689', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP234689_September.pdf'),
(9, 'EMP305438', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP305438_September.pdf'),
(10, 'EMP328916', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP328916_September.pdf'),
(11, 'EMP344096', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP344096_September.pdf'),
(12, 'EMP354777', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP354777_September.pdf'),
(13, 'EMP405676', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP405676_September.pdf'),
(14, 'EMP471209', '09', 2025, 0.00, 'paid', 900.00, 550.00, 100.00, 0.00, 0.00, 1750.00, 18250.00, 'EMP471209_September.pdf'),
(15, 'EMP490735', '09', 2025, 5929.77, 'paid', 900.00, 550.00, 100.00, 0.00, 0.00, 1750.00, 18250.00, 'EMP490735_September.pdf'),
(16, 'EMP505219', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP505219_September.pdf'),
(17, 'EMP519331', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP519331_September.pdf'),
(18, 'EMP552091', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP552091_September.pdf'),
(19, 'EMP564471', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP564471_September.pdf'),
(20, 'EMP573610', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP573610_September.pdf'),
(21, 'EMP575330', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP575330_September.pdf'),
(22, 'EMP577457', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP577457_September.pdf'),
(23, 'EMP598813', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP598813_September.pdf'),
(24, 'EMP633259', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP633259_September.pdf'),
(26, 'EMP706934', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP706934_September.pdf'),
(27, 'EMP723571', '09', 2025, 20000.00, 'paid', 900.00, 550.00, 100.00, 0.00, 0.00, 1600.00, 18400.00, 'EMP723571_September.pdf'),
(28, 'EMP738778', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP738778_September.pdf'),
(29, 'EMP772760', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP772760_September.pdf'),
(30, 'EMP772910', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP772910_September.pdf'),
(31, 'EMP774690', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP774690_September.pdf'),
(32, 'EMP798632', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP798632_September.pdf'),
(33, 'EMP804127', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP804127_September.pdf'),
(34, 'EMP814687', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP814687_September.pdf'),
(35, 'EMP850049', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP850049_September.pdf'),
(36, 'EMP862997', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP862997_September.pdf'),
(37, 'EMP880782', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP880782_September.pdf'),
(38, 'EMP910260', '09', 2025, 2000.00, 'paid', 1260.00, 770.00, 100.00, 1433.40, 0.00, 3763.40, 24236.60, 'EMP910260_September.pdf'),
(39, 'EMP910327', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP910327_September.pdf'),
(40, 'EMP912822', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP912822_September.pdf'),
(41, 'EMP926087', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP926087_September.pdf'),
(42, 'EMP928943', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP928943_September.pdf'),
(43, 'EMP981855', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP981855_September.pdf'),
(91, 'EMP981821', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP981821_September.pdf'),
(93, 'EMP981966', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP981966_September.pdf'),
(8335, 'EMP981853', '09', 2025, 0.00, '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'EMP981853_September.pdf'),
(8815, 'EMP5181995', '09', 2025, 10099.21, 'paid', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 10099.21, 'EMP5181995_September.pdf'),
(8817, 'EMP52269643', '09', 2025, 500.00, 'paid', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'EMP52269643_September.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `required_count` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_name`, `department_name`, `required_count`) VALUES
(25, 'Inventory Supervisor', 'Finance & Accounting', 5),
(30, 'Cashier', 'Food & Beverage Service\n', 5),
(79, 'Front Office Manager', 'Front Office', 3),
(80, 'Assistant Front Office Manager', 'Front Office', 3),
(81, 'Receptionist / Front Desk Agent', 'Front Office', 5),
(82, 'Guest Relations Officer', 'Front Office', 2),
(83, 'Bellboy / Porter', 'Front Office', 3),
(84, 'Concierge', 'Front Office', 2),
(85, 'Minibar Supervisor', 'Minibar', 2),
(86, 'Telephone Operator', 'Front Office', 2),
(87, 'Duty Manager', 'Front Office', 3),
(88, 'Executive Housekeeper', 'Housekeeping', 3),
(89, 'Assistant Housekeeper', 'Housekeeping', 2),
(90, 'Floor Supervisor', 'Housekeeping', 2),
(91, 'Room Attendant', 'Housekeeping', 10),
(92, 'Public Area Attendant', 'Housekeeping', 5),
(93, 'Laundry Supervisor', 'Housekeeping', 3),
(94, 'Laundry Attendant', 'Housekeeping', 4),
(95, 'Linen Room Attendant', 'Housekeeping', 2),
(96, 'Tailor / Seamstress', 'Housekeeping', 3),
(97, 'F&B Manager', 'Food & Beverage Service', 3),
(98, 'Assistant F&B Manager', 'Food & Beverage Service', 2),
(99, 'Restaurant Manager', 'Food & Beverage Service', 1),
(100, 'Banquet Manager', 'Food & Beverage Service', 1),
(101, 'Bar Manager', 'Food & Beverage Service', 1),
(102, 'Head Waiter / Captain', 'Food & Beverage Service', 2),
(103, 'Waiter / Waitress', 'Food & Beverage Service', 10),
(104, 'Host / Hostess', 'Food & Beverage Service', 2),
(105, 'Bartender', 'Food & Beverage Service', 3),
(106, 'Banquet Server', 'Food & Beverage Service', 5),
(110, 'Chef', 'Kitchen / Food Production', 3),
(111, 'server', 'Kitchen / Food Production', 10),
(115, 'Baker', 'Kitchen / Food Production', 2),
(116, 'Butcher', 'Kitchen / Food Production', 1),
(117, 'Cook', 'Kitchen / Food Production', 3),
(118, 'Chief Engineer', 'Engineering / Maintenance', 1),
(119, 'Assistant Engineer', 'Engineering / Maintenance', 2),
(120, 'Technician', 'Engineering / Maintenance', 3),
(121, 'Carpenter', 'Engineering / Maintenance', 2),
(122, 'Painter', 'Engineering / Maintenance', 1),
(123, 'Security Manager', 'Security', 1),
(124, 'Assistant Security Manager', 'Security', 1),
(125, 'Security Supervisor', 'Security', 2),
(126, 'Security Officer', 'Security', 5),
(127, 'CCTV Operator', 'Security', 2),
(128, 'Director of Sales & Marketing', 'Sales & Marketing', 1),
(129, 'Sales Manager', 'Sales & Marketing', 2),
(130, 'Marketing Manager', 'Sales & Marketing', 1),
(131, 'PR Manager', 'Sales & Marketing', 1),
(132, 'Sales Executive', 'Sales & Marketing', 3),
(133, 'Reservation Manager', 'Sales & Marketing', 1),
(134, 'Reservation Agent', 'Sales & Marketing', 3),
(135, 'Financial Controller', 'Finance & Accounting', 1),
(136, 'Chief Accountant', 'Finance & Accounting', 1),
(137, 'Accounts Payable Officer', 'Finance & Accounting', 2),
(138, 'Accounts Receivable Officer', 'Finance & Accounting', 2),
(139, 'Payroll Officer', 'Finance & Accounting', 1),
(140, 'Income Auditor', 'Finance & Accounting', 1),
(141, 'General Cashier', 'Finance & Accounting', 2),
(142, 'HR Manager', 'Human Resources', 1),
(143, 'Assistant HR Manager', 'Human Resources', 1),
(144, 'Training Manager', 'Human Resources', 1),
(145, 'Recruitment Officer', 'Human Resources', 2),
(146, 'Payroll & Benefits Officer', 'Human Resources', 1),
(147, 'HR Assistant', 'Human Resources', 2),
(148, 'Recreation Manager', 'Recreation / Spa / Leisure', 1),
(149, 'Spa Manager', 'Recreation / Spa / Leisure', 1),
(150, 'Spa Therapist', 'Recreation / Spa / Leisure', 3),
(151, 'Gym Instructor', 'Recreation / Spa / Leisure', 2),
(152, 'Lifeguard', 'Recreation / Spa / Leisure', 2),
(153, 'Pool Attendant', 'Recreation / Spa / Leisure', 2),
(154, 'Events Manager', 'Events & Banquets', 1),
(155, 'Banquet Coordinator', 'Events & Banquets', 2),
(156, 'Banquet Setup Crew', 'Events & Banquets', 5);

-- --------------------------------------------------------

--
-- Table structure for table `recruitment`
--

CREATE TABLE `recruitment` (
  `id` int(11) NOT NULL,
  `candidate_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `applied_position` varchar(100) DEFAULT NULL,
  `applied_date` datetime DEFAULT current_timestamp(),
  `status` enum('Consider','Rejected','Fit to the Job') NOT NULL DEFAULT 'Consider',
  `interview_datetime` datetime DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `benefits` varchar(255) DEFAULT NULL,
  `salary` varchar(255) DEFAULT NULL,
  `interview_result` varchar(20) DEFAULT NULL,
  `assessment_result` varchar(20) DEFAULT NULL,
  `assessment_retake` tinyint(1) DEFAULT 0,
  `stage` varchar(50) DEFAULT 'Resume Review'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruitment`
--

INSERT INTO `recruitment` (`id`, `candidate_id`, `first_name`, `last_name`, `birth_date`, `email`, `phone`, `address`, `applied_position`, `applied_date`, `status`, `interview_datetime`, `resume`, `benefits`, `salary`, `interview_result`, `assessment_result`, `assessment_retake`, `stage`) VALUES
(8, 'CAND-0008', 'Mark', 'Velasco', '1993-08-09', 'mark.velasco@example.com', '09170008888', 'Taguig', 'Telephone Operator / Call Center Agent', '2025-09-08 12:30:00', '', '2025-09-12 09:30:00', '1758647106_1757521961_Full-Hotel-Database.pdf', 'Health Insurance', '28000', 'Pending', 'Pending', 0, 'Application'),
(32, 'CAND-0031', 'Mia', 'Cruz', '1996-08-17', 'mia.cruz@example.com', '09170303333', 'Taguig', 'Chef de Partie (CDP)', '2025-10-01 10:00:00', '', '2025-10-05 09:30:00', '1758647106_1757521961_Full-Hotel-Database.pdf', 'Health Insurance', '42000', 'Pending', 'Pending', 0, 'Application'),
(42, 'CAND-0041', 'Carlos', 'Lim', '1994-05-18', 'carlos.lim@example.com', '09170403333', 'Taguig', 'Technician (Electrical / Mechanical / HVAC / Plumbing)', '2025-10-01 10:00:00', '', '2025-10-05 09:30:00', '1758647106_1757521961_Full-Hotel-Database.pdf', 'Health Insurance', '42000', 'Pending', 'Pending', 0, 'Application'),
(47, 'CAND-0041', 'Carlos', 'Lim', '1994-05-18', 'carlos.lim@example.com', '09170403333', 'Taguig', 'Technician (Electrical / Mechanical / HVAC / Plumbing)', '2025-10-01 10:00:00', '', '2025-10-05 09:30:00', '1758647106_1757521961_Full-Hotel-Database.pdf', 'Health Insurance', '42000', 'Pending', 'Pending', 0, 'Application'),
(52, 'CAND-0045', 'ally', 'sandoval', '1996-06-05', 'all@yahho.com', '09077915906', 'sjdm', 'Room Attendant', '2025-09-23 19:35:48', 'Fit to the Job', '2025-09-24 10:00:00', '1758648948_1757521961_Full-Hotel-Database.pdf', NULL, NULL, NULL, 'Pending', 0, 'Interview Scheduled'),
(53, 'CAND-0046', 'Anna', 'Montereal', '2005-11-25', 'anna.montereal@gmail.com', '09077915906', 'sjdm bulacan', 'Room Attendant', '2025-09-24 06:16:27', 'Rejected', NULL, '1758687387_1757513628_Full-Hotel-Database.pdf', NULL, NULL, NULL, NULL, 0, 'Resume Review'),
(57, 'CAND-0047', 'kathlyn', 'Sapunto', '2001-10-27', 'kathlyn.sapunto@gmail.com', '09510116523', 'sjdm bulacan', 'Public Area Attendant', '2025-09-26 10:40:10', '', NULL, '1758876010_Document copy.docx', NULL, NULL, NULL, NULL, 0, 'Resume Review'),
(58, 'CAND-0048', 'kathlyn', 'sapunto', '2000-10-26', 'kathlyb@saputo.com', '09510116523', 'sjdm bulacan', 'Inventory Supervisor', '2025-09-26 10:41:04', '', NULL, '1758876064_ID.docx', NULL, NULL, NULL, NULL, 0, 'Resume Review'),
(59, 'CAND-0049', 'kathlyn ', 'sapunto', '2002-10-26', 'sqpunto@gmail.com', '09510116523', 'sjdm bulacan', 'server', '2025-09-26 10:42:27', '', NULL, '1758876147_cheska.pdf', NULL, NULL, NULL, NULL, 0, 'Resume Review');

-- --------------------------------------------------------

--
-- Table structure for table `reimbursements`
--

CREATE TABLE `reimbursements` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reimbursements`
--

INSERT INTO `reimbursements` (`id`, `staff_id`, `amount`, `reason`, `proof_file`, `status`, `submitted_at`, `updated_at`, `notes`) VALUES
(15, 'EMP5181995', 500.00, 'Travel', '../uploads/reimbursements/68d80de784a54.jpg', 'Pending', '2025-09-28 00:16:39', '2025-09-28 00:41:49', 'ssss'),
(16, 'EMP5181995', 8000.00, 'Travel', '../uploads/reimbursements/68d810fc58592.jpg', 'Approved', '2025-09-28 00:29:48', '2025-09-28 00:30:11', '11111'),
(17, 'EMP5181995', 8000.00, 'Meals', '../uploads/reimbursements/68d812044c714.jpg', 'Rejected', '2025-09-28 00:34:12', '2025-09-28 00:41:56', 'ffff'),
(18, 'EMP5181995', 255.00, 'Supplies', '../uploads/reimbursements/68d8e670b8ea6.jpg', 'Pending', '2025-09-28 15:40:32', '2025-09-28 15:40:32', 'ffff'),
(19, 'EMP5181995', 2000.00, 'Meals', '../uploads/reimbursements/68d8e74eb4050.jpg', 'Pending', '2025-09-28 15:44:14', '2025-09-28 15:44:14', 'fff');

-- --------------------------------------------------------

--
-- Table structure for table `salary_dispute`
--

CREATE TABLE `salary_dispute` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `payout_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `dispute_details` text NOT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `date_filed` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_dispute`
--

INSERT INTO `salary_dispute` (`id`, `staff_id`, `payout_date`, `amount`, `reason`, `notes`, `proof_file`, `dispute_details`, `status`, `date_filed`, `created_at`) VALUES
(4, 'EMP5181995', '2025-08-12', 5200.00, 'Missing Payment', '1111', '../uploads/disputes/68d8e93ac6c12.jpg', 'Payout Date: 2025-08-12\nAmount: 5200\nReason: Missing Payment\nNotes: 1111', 'pending', '2025-09-28', '2025-09-28 15:52:26'),
(5, 'EMP5181995', '2025-05-12', 500.00, 'Missing Payment', '2222', '../uploads/disputes/68d8e95320487.jpg', 'Payout Date: 2025-05-12\nAmount: 500\nReason: Missing Payment\nNotes: 2222', 'resolved', '2025-09-28', '2025-09-28 15:52:51');

-- --------------------------------------------------------

--
-- Table structure for table `school_attainment`
--

CREATE TABLE `school_attainment` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `graduation_year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL DEFAULT '2000-01-01',
  `gender` enum('Male','Female') NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `hire_date` date NOT NULL,
  `employment_status` enum('Active','Inactive','Probation','Resigned','Terminated','Floating','Lay Off') NOT NULL,
  `position_name` enum('Front Office Manager','Assistant Front Office Manager','Receptionist / Front Desk Agent','Guest Relations Officer','Bellboy / Porter','Concierge','Night Auditor','Telephone Operator','Duty Manager','Executive Housekeeper','Assistant Housekeeper','Floor Supervisor','Room Attendant','Public Area Attendant','Laundry Supervisor','Laundry Attendant','Linen Room Attendant','Tailor / Seamstress','F&B Manager','Assistant F&B Manager','Restaurant Manager','Banquet Manager','Bar Manager','Head Waiter / Captain','Waiter / Waitress','Host / Hostess','Bartender','Chef','Server','Pastry Chef','Baker','Butcher','Kitchen Steward','Chief Engineer','Assistant Engineer','Technician','Carpenter','Painter','Security Manager','Assistant Security Manager','Security Supervisor','Security Officer','CCTV Operator','Director of Sales & Marketing','Sales Manager','Marketing Manager','PR Manager','Sales Executive','Reservation Manager','Reservation Agent','Financial Controller','Chief Accountant','Accounts Payable Officer','Accounts Receivable Officer','Payroll Officer','Income Auditor','General Cashier','HR Manager','Assistant HR Manager','Training Manager','Recruitment Officer','Payroll & Benefits Officer','HR Assistant','Recreation Manager','Spa Manager','Spa Therapist','Gym Instructor','Lifeguard','Pool Attendant','Events Manager','Banquet Coordinator','Banquet Setup Crew','Cashier','Inventory Supervisor') NOT NULL,
  `schedule_start_time` time DEFAULT NULL,
  `schedule_end_time` time DEFAULT NULL,
  `manager` varchar(200) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract','Internship') NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `id_proof` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `health_insurance` varchar(100) DEFAULT NULL,
  `vacation_days` int(11) DEFAULT 0,
  `department_name` enum('Front Office','Housekeeping','Food & Beverage Service','Kitchen / Food Production','Engineering / Maintenance','Security','Sales & Marketing','Finance & Accounting','Human Resources','Recreation / Spa / Leisure','Events & Banquets') NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT 'temp123',
  `contract_signed` tinyint(1) NOT NULL DEFAULT 0,
  `contract_signed_at` datetime DEFAULT NULL,
  `job_experience` text DEFAULT NULL,
  `school` text DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `last_failed_at` datetime DEFAULT NULL,
  `sss_no` varchar(50) DEFAULT NULL,
  `philhealth_no` varchar(50) DEFAULT NULL,
  `pagibig_no` varchar(50) DEFAULT NULL,
  `tin_no` varchar(50) DEFAULT NULL,
  `nbi_clearance` varchar(255) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `diploma` varchar(255) DEFAULT NULL,
  `tor` varchar(255) DEFAULT NULL,
  `barangay_clearance` varchar(255) DEFAULT NULL,
  `police_clearance` varchar(255) DEFAULT NULL,
  `other_deduction` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `last_name`, `birth_date`, `gender`, `email`, `phone`, `address`, `hire_date`, `employment_status`, `position_name`, `schedule_start_time`, `schedule_end_time`, `manager`, `employment_type`, `base_salary`, `contract_file`, `id_proof`, `photo`, `bank_name`, `account_name`, `account_number`, `emergency_contact`, `health_insurance`, `vacation_days`, `department_name`, `department_id`, `password`, `contract_signed`, `contract_signed_at`, `job_experience`, `school`, `failed_attempts`, `last_failed_at`, `sss_no`, `philhealth_no`, `pagibig_no`, `tin_no`, `nbi_clearance`, `birth_certificate`, `diploma`, `tor`, `barangay_clearance`, `police_clearance`, `other_deduction`) VALUES
('EMP104006', 'Annie', 'San Miguel', '2000-01-01', 'Male', 'anni@gmail.com', '09077915906', 'sjdm Bulacan', '2025-09-23', 'Inactive', 'Host / Hostess', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP104006.pdf', NULL, 'EMP104006_photo.jpg', NULL, NULL, NULL, '', 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'Admin123*', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP128638', 'Sophie', 'Lopez', '2000-01-01', 'Male', 'sophie.lopez@example.com', '09170103333', 'Taguig', '2025-09-23', 'Active', 'Floor Supervisor', NULL, NULL, 'Brian Reyes', '', 20000.00, '../contracts/contract_EMP128638.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP132824', 'Samantha', 'Garcia', '2000-01-01', 'Male', 'samantha.garcia@example.com', '09170205555', 'Makati', '2025-09-23', 'Probation', 'Bar Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP132824.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP143950', 'John', 'Tan', '2000-01-01', 'Male', 'john.tan@example.com', '09170002222', 'Quezon City', '2025-09-23', 'Active', 'Assistant Front Office Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP143950.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP180969', 'Kevin', 'Cruz', '2000-01-01', 'Male', 'kevin.cruz@example.com', '09170204444', 'Mandaluyong', '2025-09-23', 'Active', 'Banquet Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP180969.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP222430', 'Victor', 'Lim', '2000-01-01', 'Male', 'victor.lim@example.com', '09170108888', 'Taguig', '2025-09-23', 'Floating', 'Linen Room Attendant', NULL, NULL, 'Jessica Lopez', '', 20000.00, '../contracts/contract_EMP222430.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP234689', 'Olivia', 'Navarro', '2000-01-01', 'Male', 'olivia.navarro@example.com', '09170207777', 'Taguig', '2025-09-23', 'Active', 'Waiter / Waitress', NULL, NULL, 'Anna Delos Reyes', '', 20000.00, '../contracts/contract_EMP234689.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP305438', 'Michelle', 'Tan', '2000-01-01', 'Male', 'michelle.tan@example.com', '09170203333', 'Taguig', '2025-09-23', 'Active', 'Restaurant Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP305438.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP328916', 'Sarah', 'Discaya', '2000-01-01', 'Male', 'discaya.sarah@gmail.com', '09077915906', 'sjdm bulacan', '2025-09-25', 'Active', 'General Cashier', NULL, NULL, 'Jessica Lopez', '', 100.00, '../contracts/contract_EMP328916.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP344096', 'Ryan', 'Ildefonso', '2000-01-01', 'Male', 'ryan.santos@example.com', '09170106666', 'Mandaluyong', '2025-09-23', 'Active', 'Laundry Supervisor', NULL, NULL, 'Michelle Tan', '', 20000.00, '../contracts/contract_EMP344096.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP354777', 'Clara', 'Velasco', '2000-01-01', 'Male', 'clara.velasco@example.com', '09170309999', 'Makati', '2025-09-23', 'Lay Off', 'Baker', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP354777.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Kitchen / Food Production', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP405676', 'Leo', 'Santos', '2000-01-01', 'Male', 'leo.santos@example.com', '09170006666', 'Mandaluyong', '2025-09-23', 'Active', 'Concierge', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP405676.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP471209', 'Bella', 'Reyes', '2000-01-01', 'Male', 'bella.reyes@example.com', '09170402222', 'Quezon', '2025-09-23', 'Active', 'Assistant Engineer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP471209.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Engineering / Maintenance', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP490735', 'Anna', 'Delos Reyes', '2000-01-01', 'Male', 'anna.delosreyes@example.com', '09170009999', 'Makati City', '2025-09-23', 'Active', 'Duty Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP490735.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP505219', 'Queen Mae', 'Sapunto', '2000-01-01', 'Male', 'queenmae@gmail.com', '09077915906', 'Sjdm Bulacan', '2025-09-26', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Samantha Garcia', '', 20000000.00, '../contracts/contract_EMP505219.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP5181995', 'Cheska', 'Bautista', '2000-01-01', 'Male', 'cheska.bautista@gmail.com', '09170001111', 'Makati City', '2025-09-23', 'Active', 'Front Office Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP636819.pdf', NULL, 'EMP636819_1758815673_515491318_1940225643385875_3575146492547846042_n.jpg', NULL, NULL, NULL, '', 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'Michiie18*', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP519331', 'Isabel', 'Reyes', '2000-01-01', 'Male', 'isabel.reyes@example.com', '09170105555', 'Makati City', '2025-09-23', 'Active', 'Public Area Attendant', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP519331.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP52269643', 'Dante', 'Ramos', '1992-11-11', 'Male', 'dante.ramos@example.com', '09173445566', 'Quezon City', '2025-09-25', 'Active', 'Chef', '08:30:00', '17:30:00', 'Leo Gonzales', 'Full-time', 29000.00, '../contracts/contract_CHEF004.pdf', NULL, NULL, 'Metrobank', 'Dante Ramos', '0987234567', 'Lucia Ramos - 09170002222', 'Health Insurance, Paid Leave, 13th Month Pay', 15, 'Kitchen / Food Production', NULL, 'temp123', 0, '2025-09-25 09:30:00', '8 years experience as Chef', 'Culinary Institute of the Philippines', 0, NULL, 'SSS876543', 'PH876543', 'PG876543', 'TIN876543', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 0.00),
('EMP552091', 'Daniel', 'Lim', '2000-01-01', 'Male', 'daniel.lim@example.com', '09170206666', 'Quezon', '2025-09-23', 'Active', 'Head Waiter / Captain', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP552091.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP564471', 'Carlos', 'Dela Cruz', '2000-01-01', 'Male', 'carlos.delacruz@example.com', '09170102222', 'Quezon City', '2025-09-23', 'Active', 'Assistant Housekeeper', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP564471.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP573610', 'Abegail', 'Montenegro', '2000-01-01', 'Male', 'abegail@gmail.com', '09077915906', 'sjdm bulacan', '2025-09-25', 'Active', 'Inventory Supervisor', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP573610.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP575330', 'Clara', 'Navarro', '2000-01-01', 'Male', 'clara.navarro@example.com', '09170109999', 'Makati City', '2025-09-23', 'Active', 'Tailor / Seamstress', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP575330.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP577457', 'Jessica', 'Lopez', '2000-01-01', 'Male', 'jessica.lopez@example.com', '09170201111', 'Makati', '2025-09-23', 'Active', 'F&B Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP577457.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP598813', 'Sophia', 'Velasco', '2000-01-01', 'Male', 'sophia.velasco@example.com', '09170209999', 'Makati', '2025-09-23', 'Active', 'Bartender', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP598813.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP633259', 'Maria', 'Sandoval', '2000-01-01', 'Male', 'maria@sandoval.com', '09077915906', 'Sjdm Bulacan', '2025-09-24', 'Active', 'Room Attendant', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP633259.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP706934', 'Brian', 'Reyes', '2000-01-01', 'Male', 'brian.reyes@example.com', '09170202222', 'Quezon', '2025-09-23', 'Active', 'Assistant F&B Manager', NULL, NULL, 'Monica Montes', '', 20000.00, '../contracts/contract_EMP706934.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP723571', 'Adrian', 'Santos', '2000-01-01', 'Male', 'adrian.santos@example.com', '09170401111', 'Makati', '2025-09-24', 'Active', 'Chief Engineer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP723571.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Engineering / Maintenance', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP738778', 'Rita', 'Gonzales', '2000-01-01', 'Male', 'rita.gonzales@example.com', '09170005555', 'Makati City', '2025-09-23', 'Active', 'Bellboy / Porter', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP738778.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP772760', 'Leah', 'Gonzales', '2000-01-01', 'Male', 'leah.gonzales@example.com', '09170107777', 'Quezon City', '2025-09-23', 'Active', 'Laundry Attendant', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP772760.pdf', NULL, NULL, 'BDO', 'Leah Gonzales', '096523232332', '', 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP772910', 'Eric', 'Gonzales', '2000-01-01', 'Male', 'eric.gonzales@example.com', '09170405555', 'Makati', '2025-09-24', 'Active', 'Painter', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP772910.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Engineering / Maintenance', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP774690', 'Paul', 'Tan', '2000-01-01', 'Male', 'paul.tan@example.com', '09170302222', 'Quezon', '2025-09-23', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP774690.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP798632', 'Nina', 'Reyes', '2000-01-01', 'Male', 'nina.reyes@example.com', '09170007777', 'Quezon City', '2025-09-23', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP798632.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP804127', 'Daniel', 'Torres', '2000-01-01', 'Male', 'daniel.torres@example.com', '09170104444', 'Mandaluyong', '2025-09-23', 'Active', 'Room Attendant', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP804127.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP814687', 'James', 'Garcia', '2000-01-01', 'Male', 'james.garcia@example.com', '09170304444', 'Mandaluyong', '2025-09-23', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP814687.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP850049', 'Diana', 'Torres', '2000-01-01', 'Male', 'diana.torres@example.com', '09170404444', 'Mandaluyong', '2025-09-24', 'Active', 'Carpenter', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP850049.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Engineering / Maintenance', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP862997', 'Mark', 'Gonzales', '2000-01-01', 'Male', 'mark.gonzales@example.com', '09170211111', 'Quezon', '2025-09-23', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Kimberly Lababo', '', 20000.00, '../contracts/contract_EMP862997.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP880782', 'Laura', 'Reyes', '2000-01-01', 'Male', 'laura.reyes@example.com', '09170301111', 'Makati', '2025-09-23', 'Active', 'Accounts Payable Officer', NULL, NULL, 'Kimberly Lababo', '', 20000.00, '../contracts/contract_EMP880782.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Finance & Accounting', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP910260', 'Bianca', 'Mendoza', '1993-05-20', 'Female', 'bianca.mendoza@example.com', '09172339999', 'Makati City', '2025-09-25', 'Active', 'Chef', '09:00:00', '18:00:00', 'Leo Gonzales', 'Full-time', 28000.00, '../contracts/contract_CHEF003.pdf', NULL, NULL, 'BPI', 'Bianca Mendoza', '0987123456', 'Carlos Mendoza - 09170001111', 'Health Insurance, Paid Leave, 13th Month Pay', 15, 'Kitchen / Food Production', NULL, 'temp123', 0, '2025-09-25 09:15:00', '7 years experience as Chef', 'Center for Culinary Arts Manila', 0, NULL, 'SSS765432', 'PH765432', 'PG765432', 'TIN765432', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 0.00),
('EMP910327', 'Leo', 'Gonzales', '2000-01-01', 'Male', 'leo.gonzales@example.com', '09170308888', 'Mandaluyong', '2025-09-23', 'Active', 'Chef', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP910327.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Kitchen / Food Production', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP912822', 'Paul', 'Cruz', '2000-01-01', 'Male', 'paul.cruz@example.com', '09170004444', 'Taguig', '2025-09-23', 'Active', 'Guest Relations Officer', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP912822.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'Admin123*', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP926087', 'Emma', 'Garcia', '2000-01-01', 'Male', 'emma.garcia@example.com', '09170101111', 'Makati City', '2025-09-23', 'Active', 'Executive Housekeeper', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP926087.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Housekeeping', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP928943', 'Mary', 'Lopez', '2000-01-01', 'Male', 'mary.lopez@example.com', '09170003333', 'Mandaluyong', '2025-09-23', 'Active', 'Receptionist / Front Desk Agent', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP928943.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Front Office', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP981821', 'Kimberly', 'Lababo', '1980-05-15', 'Female', 'kimberlylababo@gmail.com', '09077933311', 'sjdm bulacan', '2020-01-01', 'Active', 'Front Office Manager', '08:00:00', '17:00:00', 'Monica Montes', '', 60000.00, 'contracts/EMPGEN001.pdf', 'EMPGEN001_id.jpg', 'EMPGEN001.jpg', 'BPI', 'Kimberly Lababo', '99988877766', '', 'Health Insurance, Paid Leave, 13th Month Pay, Car Allowance', 20, 'Front Office', NULL, 'Admin123*', 1, '2025-09-24 00:38:57', '10+ years in hotel management', 'University of the Philippines', 0, NULL, 'SSS9001', 'PH9001', 'PG9001', 'TIN9001', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 0.00),
('EMP981853', 'Luz', 'Reyes', '1992-12-01', 'Female', 'luz.reyes@example.com', '09173456789', '78 Rizal St., Makati', '2022-03-18', 'Probation', 'Technician', '08:00:00', '17:00:00', 'Samantha Garcia', '', 28000.00, NULL, NULL, NULL, 'Metrobank', 'Luz Reyes', '4567890123', 'Carlos Reyes - 09175553333', 'Maxicare', 10, 'Engineering / Maintenance', 5, 'temp123', 0, NULL, '3 years in hotel technical support', 'FEU', 0, NULL, '36-7890123-4', '34-567890123-4', '345678901', '345-678-901-2', NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP981855', 'Nerie Ann', 'Sarabia', '2000-01-01', 'Male', 'nerie@gmail.com', '09077915906', 'sjdm Bulacan', '2025-09-23', 'Active', 'Waiter / Waitress', NULL, NULL, 'Samantha Garcia', '', 20000.00, '../contracts/contract_EMP981855.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'Health Insurance, Paid Leave, 13th Month Pay', 0, 'Food & Beverage Service', NULL, 'temp123', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
('EMP981966', 'Maria', 'Lopez', '1995-06-30', 'Female', 'maria.lopez@example.com', '09175678901', '90 Mabini St., Pasay', '2023-01-05', 'Active', 'Painter', '10:00:00', '19:00:00', 'Samantha Garcia', '', 30000.00, NULL, NULL, NULL, 'BDO', 'Maria Lopez', '6789012345', 'Jose Lopez - 09178889999', 'Maxicare', 12, 'Engineering / Maintenance', 5, 'temp123', 1, '2023-01-05 10:00:00', '2 years experience painting interiors', 'UP Manila', 0, NULL, '38-9012345-6', '56-789012345-6', '567890123', '567-890-123-4', NULL, NULL, NULL, NULL, NULL, NULL, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bonuses_incentives`
--
ALTER TABLE `bonuses_incentives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staff_id` (`staff_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslip`
--
ALTER TABLE `payslip`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payslip` (`staff_id`,`month`,`year`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `recruitment`
--
ALTER TABLE `recruitment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `salary_dispute`
--
ALTER TABLE `salary_dispute`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bonuses_incentives`
--
ALTER TABLE `bonuses_incentives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payslip`
--
ALTER TABLE `payslip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8984;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `recruitment`
--
ALTER TABLE `recruitment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `reimbursements`
--
ALTER TABLE `reimbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `salary_dispute`
--
ALTER TABLE `salary_dispute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bonuses_incentives`
--
ALTER TABLE `bonuses_incentives`
  ADD CONSTRAINT `fk_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD CONSTRAINT `reimbursements_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
