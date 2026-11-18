-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 12:37 PM
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
-- Database: `hotel_reservation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_status`
--

CREATE TABLE `account_status` (
  `account_Status_ID` int(10) UNSIGNED NOT NULL,
  `account_Status_Name` varchar(40) NOT NULL,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_status`
--

INSERT INTO `account_status` (`account_Status_ID`, `account_Status_Name`, `is_Active`) VALUES
(1, 'Active', 1),
(2, 'Suspended', 1),
(3, 'Disabled', 1);

-- --------------------------------------------------------

--
-- Table structure for table `addon`
--

CREATE TABLE `addon` (
  `addon_ID` int(10) UNSIGNED NOT NULL,
  `addon_Name` varchar(100) NOT NULL,
  `addon_Price` decimal(12,2) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `addon`
--

INSERT INTO `addon` (`addon_ID`, `addon_Name`, `addon_Price`, `deleted_At`) VALUES
(1, 'Breakfast', 250.00, NULL),
(2, 'Extra Pillow', 100.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bed_type`
--

CREATE TABLE `bed_type` (
  `bed_Type_ID` int(10) UNSIGNED NOT NULL,
  `bed_Type_Name` varchar(80) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_ID` int(10) UNSIGNED NOT NULL,
  `user_ID` int(10) UNSIGNED NOT NULL,
  `booking_Status_ID` int(10) UNSIGNED NOT NULL,
  `checkin_Date` datetime NOT NULL,
  `checkout_Date` datetime NOT NULL,
  `booking_Amount` decimal(12,2) NOT NULL,
  `required_Downpayment` decimal(12,2) NOT NULL,
  `confirmation_Code` varchar(60) DEFAULT NULL COMMENT 'Generated once confirmed',
  `hold_Expires_At` datetime DEFAULT NULL COMMENT 'Optional time limit for Pending Payment holds',
  `created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_ID`, `user_ID`, `booking_Status_ID`, `checkin_Date`, `checkout_Date`, `booking_Amount`, `required_Downpayment`, `confirmation_Code`, `hold_Expires_At`, `created_At`, `deleted_At`) VALUES
(1, 1, 2, '2026-11-16 14:00:00', '2026-11-19 12:00:00', 11100.00, 3330.00, NULL, '2026-11-16 18:20:57', '2026-11-16 17:50:57', NULL),
(2, 2, 1, '2026-11-16 14:00:00', '2026-11-19 12:00:00', 10700.00, 3210.00, NULL, '2026-11-16 18:46:12', '2026-11-16 18:16:12', NULL),
(3, 3, 1, '2026-11-16 14:00:00', '2026-11-19 12:00:00', 18000.00, 5400.00, NULL, '2026-11-16 21:02:23', '2026-11-16 20:32:23', NULL),
(4, 4, 1, '2026-11-16 14:00:00', '2026-11-19 12:00:00', 18000.00, 5400.00, NULL, '2026-11-16 22:31:35', '2026-11-16 22:01:35', NULL),
(5, 5, 2, '2026-11-17 14:00:00', '2026-11-20 12:00:00', 10500.00, 3150.00, NULL, '2026-11-17 16:45:45', '2026-11-17 16:15:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_addon`
--

CREATE TABLE `booking_addon` (
  `booking_ID` int(10) UNSIGNED NOT NULL,
  `addon_ID` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_Price` decimal(12,2) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `booking_addon`
--

INSERT INTO `booking_addon` (`booking_ID`, `addon_ID`, `quantity`, `unit_Price`, `deleted_At`) VALUES
(1, 1, 2, 250.00, NULL),
(1, 2, 1, 100.00, NULL),
(2, 2, 2, 100.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_log`
--

CREATE TABLE `booking_log` (
  `log_ID` int(10) UNSIGNED NOT NULL,
  `booking_ID` int(10) UNSIGNED NOT NULL,
  `user_ID` int(10) UNSIGNED DEFAULT NULL,
  `booking_Status_ID` int(10) UNSIGNED NOT NULL,
  `action_Type` varchar(60) NOT NULL,
  `action_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_log`
--

INSERT INTO `booking_log` (`log_ID`, `booking_ID`, `user_ID`, `booking_Status_ID`, `action_Type`, `action_Date`, `remarks`) VALUES
(1, 1, 1, 1, 'Created', '2026-11-16 17:50:57', 'Pending downpayment.'),
(2, 1, 1, 2, 'Downpayment Submitted', '2026-11-16 18:07:30', 'Payment reference submitted; awaiting verification.'),
(3, 2, 2, 1, 'Created', '2026-11-16 18:16:12', 'Pending downpayment.'),
(4, 3, 3, 1, 'Created', '2026-11-16 20:32:23', 'Pending downpayment.'),
(5, 4, 4, 1, 'Created', '2026-11-16 22:01:35', 'Pending downpayment.'),
(6, 5, 5, 1, 'Created', '2026-11-17 16:15:45', 'Pending downpayment.'),
(7, 5, 5, 2, 'Downpayment Submitted', '2026-11-17 16:27:58', 'Payment reference submitted; awaiting verification.');

-- --------------------------------------------------------

--
-- Table structure for table `booking_room`
--

CREATE TABLE `booking_room` (
  `booking_ID` int(10) UNSIGNED NOT NULL,
  `room_ID` int(10) UNSIGNED NOT NULL,
  `num_Guest` int(10) UNSIGNED NOT NULL,
  `nightly_Rate` decimal(12,2) NOT NULL,
  `extra_Guest_Fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Booking-time extra-person rate; policy must define frequency',
  `included_Guests_Snapshot` int(10) UNSIGNED NOT NULL,
  `checked_In_At` datetime DEFAULT NULL COMMENT 'Actual check-in for this room',
  `checked_Out_At` datetime DEFAULT NULL COMMENT 'Actual checkout triggers housekeeping',
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `booking_room`
--

INSERT INTO `booking_room` (`booking_ID`, `room_ID`, `num_Guest`, `nightly_Rate`, `extra_Guest_Fee`, `included_Guests_Snapshot`, `checked_In_At`, `checked_Out_At`, `deleted_At`) VALUES
(1, 1, 2, 3500.00, 1000.00, 2, NULL, NULL, NULL),
(2, 2, 2, 3500.00, 1000.00, 2, NULL, NULL, NULL),
(3, 4, 4, 6000.00, 0.00, 4, NULL, NULL, NULL),
(4, 4, 4, 6000.00, 0.00, 4, NULL, NULL, NULL),
(5, 2, 2, 3500.00, 1000.00, 2, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_status`
--

CREATE TABLE `booking_status` (
  `booking_Status_ID` int(10) UNSIGNED NOT NULL,
  `booking_Status_Name` varchar(50) NOT NULL,
  `blocks_Availability` tinyint(1) NOT NULL DEFAULT 0,
  `requires_Hold_Expiry_Check` tinyint(1) NOT NULL DEFAULT 0,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_status`
--

INSERT INTO `booking_status` (`booking_Status_ID`, `booking_Status_Name`, `blocks_Availability`, `requires_Hold_Expiry_Check`, `is_Active`) VALUES
(1, 'Pending Payment', 1, 1, 1),
(2, 'Pending Approval', 1, 0, 1),
(3, 'Confirmed', 1, 0, 1),
(4, 'Declined', 0, 0, 1),
(5, 'Cancellation Requested', 1, 0, 1),
(6, 'Cancelled', 0, 0, 1),
(7, 'Checked In', 1, 0, 1),
(8, 'Checked Out', 0, 0, 1),
(9, 'Completed', 0, 0, 1),
(10, 'Expired', 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `feature`
--

CREATE TABLE `feature` (
  `feature_ID` int(10) UNSIGNED NOT NULL,
  `feature_Name` varchar(100) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `floor`
--

CREATE TABLE `floor` (
  `floor_ID` int(10) UNSIGNED NOT NULL,
  `floor_Number` int(11) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_setting`
--

CREATE TABLE `hotel_setting` (
  `setting_Key` varchar(80) NOT NULL,
  `setting_Value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_At` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hotel_setting`
--

INSERT INTO `hotel_setting` (`setting_Key`, `setting_Value`, `description`, `updated_At`) VALUES
('checkin_time', '14:00:00', 'Standard check-in time: 2:00 PM Philippine time', '2026-11-14 22:34:43'),
('checkout_time', '12:00:00', 'Standard checkout time: 12:00 NN Philippine time', '2026-11-14 22:34:43'),
('downpayment_percent', '30', 'Required downpayment is 30 percent of total booking amount', '2026-11-16 16:55:04'),
('hotel_timezone', 'Asia/Manila', 'Official hotel timezone: Philippine Standard Time (UTC+08:00)', '2026-11-14 22:34:43'),
('late_cancellation_nonrefundable_days', '3', 'Non-refundable when cancellation is less than 3 days before check-in', '2026-11-14 22:34:43'),
('pending_hold_minutes', '30', 'Proposed number of minutes to complete the downpayment before a pending hold expires', '2026-11-14 22:34:43');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_status`
--

CREATE TABLE `housekeeping_status` (
  `housekeeping_Status_ID` int(10) UNSIGNED NOT NULL,
  `housekeeping_Status_Name` varchar(50) NOT NULL,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `housekeeping_status`
--

INSERT INTO `housekeeping_status` (`housekeeping_Status_ID`, `housekeeping_Status_Name`, `is_Active`) VALUES
(1, 'Assigned', 1),
(2, 'In Progress', 1),
(3, 'Pending Verification', 1),
(4, 'Verified', 1),
(5, 'Needs Recleaning', 1),
(6, 'Cancelled', 1);

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_task`
--

CREATE TABLE `housekeeping_task` (
  `task_ID` int(10) UNSIGNED NOT NULL,
  `room_ID` int(10) UNSIGNED NOT NULL,
  `assigned_To` int(10) UNSIGNED NOT NULL,
  `assigned_By` int(10) UNSIGNED NOT NULL,
  `verified_By` int(10) UNSIGNED DEFAULT NULL,
  `housekeeping_Status_ID` int(10) UNSIGNED NOT NULL,
  `assigned_At` datetime NOT NULL DEFAULT current_timestamp(),
  `started_At` datetime DEFAULT NULL,
  `completed_At` datetime DEFAULT NULL,
  `verified_At` datetime DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `deleted_At` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_ID` int(10) UNSIGNED NOT NULL,
  `booking_ID` int(10) UNSIGNED NOT NULL,
  `payment_Method_ID` int(10) UNSIGNED NOT NULL,
  `processed_By` int(10) UNSIGNED DEFAULT NULL,
  `payment_Status_ID` int(10) UNSIGNED NOT NULL,
  `payment_Type_ID` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference_No` varchar(120) DEFAULT NULL,
  `payment_Date` datetime DEFAULT NULL,
  `created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_ID`, `booking_ID`, `payment_Method_ID`, `processed_By`, `payment_Status_ID`, `payment_Type_ID`, `amount`, `reference_No`, `payment_Date`, `created_At`, `deleted_At`) VALUES
(1, 1, 3, NULL, 1, 1, 3330.00, 'GCASH20261116001', NULL, '2026-11-16 18:07:30', NULL),
(2, 5, 2, NULL, 1, 1, 3150.00, 'GCASH20261116001', NULL, '2026-11-17 16:27:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `payment_Method_ID` int(10) UNSIGNED NOT NULL,
  `payment_Method_Name` varchar(60) NOT NULL,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`payment_Method_ID`, `payment_Method_Name`, `is_Active`) VALUES
(1, 'Cash', 1),
(2, 'Bank Transfer', 1),
(3, 'GCash', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_status`
--

CREATE TABLE `payment_status` (
  `payment_Status_ID` int(10) UNSIGNED NOT NULL,
  `payment_Status_Name` varchar(40) NOT NULL,
  `counts_As_Paid` tinyint(1) NOT NULL DEFAULT 0,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_status`
--

INSERT INTO `payment_status` (`payment_Status_ID`, `payment_Status_Name`, `counts_As_Paid`, `is_Active`) VALUES
(1, 'Pending', 0, 1),
(2, 'Verified', 1, 1),
(3, 'Failed', 0, 1),
(4, 'Cancelled', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_type`
--

CREATE TABLE `payment_type` (
  `payment_Type_ID` int(10) UNSIGNED NOT NULL,
  `payment_Type_Name` varchar(40) NOT NULL,
  `balance_Effect` tinyint(4) NOT NULL,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `payment_type`
--

INSERT INTO `payment_type` (`payment_Type_ID`, `payment_Type_Name`, `balance_Effect`, `is_Active`) VALUES
(1, 'Downpayment', 1, 1),
(2, 'Remaining Balance', 1, 1),
(3, 'Refund', -1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_ID` int(10) UNSIGNED NOT NULL,
  `room_Type_ID` int(10) UNSIGNED NOT NULL,
  `floor_ID` int(10) UNSIGNED DEFAULT NULL COMMENT 'Optional for facilities such as Event Hall',
  `room_Status_ID` int(10) UNSIGNED NOT NULL,
  `room_Code` varchar(40) NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_ID`, `room_Type_ID`, `floor_ID`, `room_Status_ID`, `room_Code`, `deleted_At`) VALUES
(1, 1, NULL, 1, 'Room A', NULL),
(2, 1, NULL, 1, 'Room B', NULL),
(3, 1, NULL, 1, 'Room C', NULL),
(4, 2, NULL, 1, 'FR1', NULL),
(5, 2, NULL, 1, 'FR2', NULL),
(6, 2, NULL, 1, 'FR3', NULL),
(7, 3, NULL, 1, 'Event Hall', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_status`
--

CREATE TABLE `room_status` (
  `room_Status_ID` int(10) UNSIGNED NOT NULL,
  `room_Status_Name` varchar(50) NOT NULL,
  `ready_For_Checkin` tinyint(1) NOT NULL DEFAULT 0,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_status`
--

INSERT INTO `room_status` (`room_Status_ID`, `room_Status_Name`, `ready_For_Checkin`, `is_Active`) VALUES
(1, 'Ready', 1, 1),
(2, 'Needs Cleaning', 0, 1),
(3, 'Cleaning', 0, 1),
(4, 'Pending Inspection', 0, 1),
(5, 'Under Maintenance', 0, 1),
(6, 'Out of Service', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

CREATE TABLE `room_type` (
  `room_Type_ID` int(10) UNSIGNED NOT NULL,
  `room_Type` varchar(90) NOT NULL,
  `base_Price` decimal(12,2) NOT NULL,
  `included_Guests` int(10) UNSIGNED NOT NULL,
  `max_Guests` int(10) UNSIGNED NOT NULL,
  `extra_Person_Fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deleted_At` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `room_type`
--

INSERT INTO `room_type` (`room_Type_ID`, `room_Type`, `base_Price`, `included_Guests`, `max_Guests`, `extra_Person_Fee`, `deleted_At`) VALUES
(1, 'Standard Room', 3500.00, 2, 3, 1000.00, NULL),
(2, 'Family Room', 6000.00, 4, 5, 0.00, NULL),
(3, 'Event Hall', 15000.00, 30, 40, 500.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_type_bed_type`
--

CREATE TABLE `room_type_bed_type` (
  `room_Type_ID` int(10) UNSIGNED NOT NULL,
  `bed_Type_ID` int(10) UNSIGNED NOT NULL,
  `num_Beds` int(10) UNSIGNED NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `room_type_feature`
--

CREATE TABLE `room_type_feature` (
  `room_Type_ID` int(10) UNSIGNED NOT NULL,
  `feature_ID` int(10) UNSIGNED NOT NULL,
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_ID` int(10) UNSIGNED NOT NULL,
  `role_ID` int(10) UNSIGNED NOT NULL,
  `account_Status_ID` int(10) UNSIGNED NOT NULL,
  `first_Name` varchar(80) NOT NULL,
  `last_Name` varchar(80) NOT NULL,
  `email_Address` varchar(190) NOT NULL,
  `phone_Number` varchar(25) DEFAULT NULL,
  `password_Hash` varchar(255) DEFAULT NULL COMMENT 'NULL for a guest checkout without an account',
  `created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_ID`, `role_ID`, `account_Status_ID`, `first_Name`, `last_Name`, `email_Address`, `phone_Number`, `password_Hash`, `created_At`, `deleted_At`) VALUES
(1, 3, 1, 'Russel', 'Cabucos', 'rjohncabucos@gmail.com', '09943035821', NULL, '2026-11-16 17:50:57', NULL),
(2, 3, 1, 'Ashly', 'Galman', 'ashly@gmail.com', '09758531894', NULL, '2026-11-16 18:16:12', NULL),
(3, 3, 1, 'Russel', 'Cabucos', 'russ@gmail.com', '09943035821', NULL, '2026-11-16 20:32:23', NULL),
(4, 3, 1, 'Russel', 'Cabucos', 'rj@gmail.com', '09943035821', NULL, '2026-11-16 22:01:35', NULL),
(5, 3, 1, 'matthew', 'aquino', 'matt@gmail.com', '09943035821', NULL, '2026-11-17 16:15:45', NULL),
(6, 3, 1, 'Mary', 'Hammed', 'mary@gmail.com', '09943035821', '$2y$10$Nv8ZtqlsFqcmCYZ/hfkOU.fozzxiASzJH2YYpnzH7F9uuwpknDyRO', '2026-11-17 21:12:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

CREATE TABLE `user_role` (
  `role_ID` int(10) UNSIGNED NOT NULL,
  `role_Name` varchar(40) NOT NULL,
  `is_Active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`role_ID`, `role_Name`, `is_Active`) VALUES
(1, 'Admin', 1),
(2, 'Housekeeping', 1),
(3, 'Guest', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_status`
--
ALTER TABLE `account_status`
  ADD PRIMARY KEY (`account_Status_ID`),
  ADD UNIQUE KEY `uq_account_status_name` (`account_Status_Name`);

--
-- Indexes for table `addon`
--
ALTER TABLE `addon`
  ADD PRIMARY KEY (`addon_ID`),
  ADD UNIQUE KEY `uq_addon_name` (`addon_Name`);

--
-- Indexes for table `bed_type`
--
ALTER TABLE `bed_type`
  ADD PRIMARY KEY (`bed_Type_ID`),
  ADD UNIQUE KEY `uq_bed_type_name` (`bed_Type_Name`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_ID`),
  ADD UNIQUE KEY `uq_booking_confirmation` (`confirmation_Code`),
  ADD KEY `idx_booking_guest` (`user_ID`),
  ADD KEY `idx_booking_status_dates` (`booking_Status_ID`,`checkin_Date`,`checkout_Date`),
  ADD KEY `idx_booking_dates` (`checkin_Date`,`checkout_Date`),
  ADD KEY `idx_booking_hold` (`hold_Expires_At`),
  ADD KEY `idx_booking_deleted` (`deleted_At`);

--
-- Indexes for table `booking_addon`
--
ALTER TABLE `booking_addon`
  ADD PRIMARY KEY (`booking_ID`,`addon_ID`),
  ADD KEY `idx_booking_addon_addon` (`addon_ID`);

--
-- Indexes for table `booking_log`
--
ALTER TABLE `booking_log`
  ADD PRIMARY KEY (`log_ID`),
  ADD KEY `fk_blog_status` (`booking_Status_ID`),
  ADD KEY `idx_blog_booking_date` (`booking_ID`,`action_Date`),
  ADD KEY `idx_blog_actor` (`user_ID`);

--
-- Indexes for table `booking_room`
--
ALTER TABLE `booking_room`
  ADD PRIMARY KEY (`booking_ID`,`room_ID`),
  ADD KEY `idx_booking_room_room` (`room_ID`,`booking_ID`);

--
-- Indexes for table `booking_status`
--
ALTER TABLE `booking_status`
  ADD PRIMARY KEY (`booking_Status_ID`),
  ADD UNIQUE KEY `uq_booking_status_name` (`booking_Status_Name`);

--
-- Indexes for table `feature`
--
ALTER TABLE `feature`
  ADD PRIMARY KEY (`feature_ID`),
  ADD UNIQUE KEY `uq_feature_name` (`feature_Name`);

--
-- Indexes for table `floor`
--
ALTER TABLE `floor`
  ADD PRIMARY KEY (`floor_ID`),
  ADD UNIQUE KEY `uq_floor_number` (`floor_Number`);

--
-- Indexes for table `hotel_setting`
--
ALTER TABLE `hotel_setting`
  ADD PRIMARY KEY (`setting_Key`);

--
-- Indexes for table `housekeeping_status`
--
ALTER TABLE `housekeeping_status`
  ADD PRIMARY KEY (`housekeeping_Status_ID`),
  ADD UNIQUE KEY `uq_housekeeping_status_name` (`housekeeping_Status_Name`);

--
-- Indexes for table `housekeeping_task`
--
ALTER TABLE `housekeeping_task`
  ADD PRIMARY KEY (`task_ID`),
  ADD KEY `fk_hkt_status` (`housekeeping_Status_ID`),
  ADD KEY `idx_hkt_assigned_staff` (`assigned_To`,`housekeeping_Status_ID`),
  ADD KEY `idx_hkt_room` (`room_ID`,`assigned_At`),
  ADD KEY `idx_hkt_assigned_by` (`assigned_By`),
  ADD KEY `idx_hkt_verified_by` (`verified_By`),
  ADD KEY `idx_hkt_deleted` (`deleted_At`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_ID`),
  ADD UNIQUE KEY `uq_payment_reference` (`payment_Method_ID`,`reference_No`),
  ADD KEY `idx_payment_booking` (`booking_ID`),
  ADD KEY `idx_payment_status` (`payment_Status_ID`),
  ADD KEY `idx_payment_processor` (`processed_By`),
  ADD KEY `idx_payment_type` (`payment_Type_ID`),
  ADD KEY `idx_payment_deleted` (`deleted_At`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`payment_Method_ID`),
  ADD UNIQUE KEY `uq_payment_method_name` (`payment_Method_Name`);

--
-- Indexes for table `payment_status`
--
ALTER TABLE `payment_status`
  ADD PRIMARY KEY (`payment_Status_ID`),
  ADD UNIQUE KEY `uq_payment_status_name` (`payment_Status_Name`);

--
-- Indexes for table `payment_type`
--
ALTER TABLE `payment_type`
  ADD PRIMARY KEY (`payment_Type_ID`),
  ADD UNIQUE KEY `uq_payment_type_name` (`payment_Type_Name`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_ID`),
  ADD UNIQUE KEY `uq_room_code` (`room_Code`),
  ADD KEY `fk_room_floor` (`floor_ID`),
  ADD KEY `idx_room_type` (`room_Type_ID`),
  ADD KEY `idx_room_status` (`room_Status_ID`),
  ADD KEY `idx_room_deleted` (`deleted_At`);

--
-- Indexes for table `room_status`
--
ALTER TABLE `room_status`
  ADD PRIMARY KEY (`room_Status_ID`),
  ADD UNIQUE KEY `uq_room_status_name` (`room_Status_Name`);

--
-- Indexes for table `room_type`
--
ALTER TABLE `room_type`
  ADD PRIMARY KEY (`room_Type_ID`),
  ADD UNIQUE KEY `uq_room_type_name` (`room_Type`);

--
-- Indexes for table `room_type_bed_type`
--
ALTER TABLE `room_type_bed_type`
  ADD PRIMARY KEY (`room_Type_ID`,`bed_Type_ID`),
  ADD KEY `idx_rtb_bed_type` (`bed_Type_ID`);

--
-- Indexes for table `room_type_feature`
--
ALTER TABLE `room_type_feature`
  ADD PRIMARY KEY (`room_Type_ID`,`feature_ID`),
  ADD KEY `idx_rtf_feature` (`feature_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_ID`),
  ADD UNIQUE KEY `uq_users_email` (`email_Address`),
  ADD KEY `fk_users_account_status` (`account_Status_ID`),
  ADD KEY `idx_users_role` (`role_ID`),
  ADD KEY `idx_users_deleted` (`deleted_At`);

--
-- Indexes for table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`role_ID`),
  ADD UNIQUE KEY `uq_user_role_name` (`role_Name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_status`
--
ALTER TABLE `account_status`
  MODIFY `account_Status_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `addon`
--
ALTER TABLE `addon`
  MODIFY `addon_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bed_type`
--
ALTER TABLE `bed_type`
  MODIFY `bed_Type_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_log`
--
ALTER TABLE `booking_log`
  MODIFY `log_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `booking_status`
--
ALTER TABLE `booking_status`
  MODIFY `booking_Status_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feature`
--
ALTER TABLE `feature`
  MODIFY `feature_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `floor`
--
ALTER TABLE `floor`
  MODIFY `floor_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_status`
--
ALTER TABLE `housekeeping_status`
  MODIFY `housekeeping_Status_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `housekeeping_task`
--
ALTER TABLE `housekeeping_task`
  MODIFY `task_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `payment_Method_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_status`
--
ALTER TABLE `payment_status`
  MODIFY `payment_Status_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_type`
--
ALTER TABLE `payment_type`
  MODIFY `payment_Type_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `room_status`
--
ALTER TABLE `room_status`
  MODIFY `room_Status_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `room_type`
--
ALTER TABLE `room_type`
  MODIFY `room_Type_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_role`
--
ALTER TABLE `user_role`
  MODIFY `role_ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_guest` FOREIGN KEY (`user_ID`) REFERENCES `users` (`user_ID`),
  ADD CONSTRAINT `fk_booking_status` FOREIGN KEY (`booking_Status_ID`) REFERENCES `booking_status` (`booking_Status_ID`);

--
-- Constraints for table `booking_addon`
--
ALTER TABLE `booking_addon`
  ADD CONSTRAINT `fk_booking_addon_addon` FOREIGN KEY (`addon_ID`) REFERENCES `addon` (`addon_ID`),
  ADD CONSTRAINT `fk_booking_addon_booking` FOREIGN KEY (`booking_ID`) REFERENCES `booking` (`booking_ID`);

--
-- Constraints for table `booking_log`
--
ALTER TABLE `booking_log`
  ADD CONSTRAINT `fk_blog_actor` FOREIGN KEY (`user_ID`) REFERENCES `users` (`user_ID`),
  ADD CONSTRAINT `fk_blog_booking` FOREIGN KEY (`booking_ID`) REFERENCES `booking` (`booking_ID`),
  ADD CONSTRAINT `fk_blog_status` FOREIGN KEY (`booking_Status_ID`) REFERENCES `booking_status` (`booking_Status_ID`);

--
-- Constraints for table `booking_room`
--
ALTER TABLE `booking_room`
  ADD CONSTRAINT `fk_booking_room_booking` FOREIGN KEY (`booking_ID`) REFERENCES `booking` (`booking_ID`),
  ADD CONSTRAINT `fk_booking_room_room` FOREIGN KEY (`room_ID`) REFERENCES `room` (`room_ID`);

--
-- Constraints for table `housekeeping_task`
--
ALTER TABLE `housekeeping_task`
  ADD CONSTRAINT `fk_hkt_assigned_by` FOREIGN KEY (`assigned_By`) REFERENCES `users` (`user_ID`),
  ADD CONSTRAINT `fk_hkt_assigned_to` FOREIGN KEY (`assigned_To`) REFERENCES `users` (`user_ID`),
  ADD CONSTRAINT `fk_hkt_room` FOREIGN KEY (`room_ID`) REFERENCES `room` (`room_ID`),
  ADD CONSTRAINT `fk_hkt_status` FOREIGN KEY (`housekeeping_Status_ID`) REFERENCES `housekeeping_status` (`housekeeping_Status_ID`),
  ADD CONSTRAINT `fk_hkt_verified_by` FOREIGN KEY (`verified_By`) REFERENCES `users` (`user_ID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_ID`) REFERENCES `booking` (`booking_ID`),
  ADD CONSTRAINT `fk_payment_method` FOREIGN KEY (`payment_Method_ID`) REFERENCES `payment_method` (`payment_Method_ID`),
  ADD CONSTRAINT `fk_payment_processor` FOREIGN KEY (`processed_By`) REFERENCES `users` (`user_ID`),
  ADD CONSTRAINT `fk_payment_status` FOREIGN KEY (`payment_Status_ID`) REFERENCES `payment_status` (`payment_Status_ID`),
  ADD CONSTRAINT `fk_payment_type` FOREIGN KEY (`payment_Type_ID`) REFERENCES `payment_type` (`payment_Type_ID`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `fk_room_floor` FOREIGN KEY (`floor_ID`) REFERENCES `floor` (`floor_ID`),
  ADD CONSTRAINT `fk_room_status` FOREIGN KEY (`room_Status_ID`) REFERENCES `room_status` (`room_Status_ID`),
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`room_Type_ID`) REFERENCES `room_type` (`room_Type_ID`);

--
-- Constraints for table `room_type_bed_type`
--
ALTER TABLE `room_type_bed_type`
  ADD CONSTRAINT `fk_rtb_bed_type` FOREIGN KEY (`bed_Type_ID`) REFERENCES `bed_type` (`bed_Type_ID`),
  ADD CONSTRAINT `fk_rtb_room_type` FOREIGN KEY (`room_Type_ID`) REFERENCES `room_type` (`room_Type_ID`);

--
-- Constraints for table `room_type_feature`
--
ALTER TABLE `room_type_feature`
  ADD CONSTRAINT `fk_rtf_feature` FOREIGN KEY (`feature_ID`) REFERENCES `feature` (`feature_ID`),
  ADD CONSTRAINT `fk_rtf_room_type` FOREIGN KEY (`room_Type_ID`) REFERENCES `room_type` (`room_Type_ID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_account_status` FOREIGN KEY (`account_Status_ID`) REFERENCES `account_status` (`account_Status_ID`),
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_ID`) REFERENCES `user_role` (`role_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
