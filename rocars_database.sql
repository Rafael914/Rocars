-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 04:20 PM
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
-- Database: `rocars_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `accessories_details`
--

CREATE TABLE `accessories_details` (
  `accessories_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `typeofaccessories` text NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model_number` varchar(50) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `fitment_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accessories_details`
--

INSERT INTO `accessories_details` (`accessories_id`, `product_id`, `typeofaccessories`, `brand`, `model_number`, `material`, `color`, `fitment_details`) VALUES
(7, 1, 'Cellphone Holder', NULL, NULL, NULL, NULL, NULL),
(17, 93, 'dsfmmf', 'dgds', 'fdskf', 'fdmv', 'ssd l', 'dfl'),
(18, 98, 'GHDFGHF', 'FGHFD', 'GHDFGHD', 'FGHDFGH', 'FDGHFDG', 'dfl'),
(19, 99, 'DDSDASD', 'ASDASD', 'ASDAS', 'DSADAS', 'ASDAS', 'DASDSA'),
(20, 105, 'sad', 'sdsa', 'dsad', 'sadsa', 'dsad', 'dfdsf'),
(21, 106, 'asdasd', 'asd', 'sads', 'adsa', 'dsad', 'sad'),
(22, 107, 'wdsad', 'sadsa', 'dsad', 'sad', 'sadsa', 'dsa'),
(23, 117, '546', '546', '54', '654', '654', '6');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('ADD','UPDATE','DELETE','RESTORE','ARCHIVE','LOGIN','EXPORT') NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`audit_id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `created_at`) VALUES
(255, 5, 'UPDATE', 'expenses', 62, 'Updated expense ID 62: Date \'0000-00-00 00:00:00\' → \'\', Category \'65\' → \'paid\'', '2026-01-17 03:01:18'),
(256, 5, 'DELETE', 'expenses', 64, 'Deleted expense ID: 64 (Amount: 10000.00, Category: payed, Date: 0000-00-00 00:00:00)', '2026-01-17 03:01:26'),
(257, 5, 'UPDATE', 'expenses', 66, 'Updated expense ID 66: Date \'2026-01-17 02:53:41\' → \'\', Category \'DONE\' → \'NONE\'', '2026-01-17 03:02:15'),
(258, 5, 'UPDATE', 'expenses', 66, 'Updated expense ID 66: Date \'0000-00-00 00:00:00\' → \'\'', '2026-01-17 03:02:15'),
(259, 5, 'UPDATE', 'expenses', 34, 'Updated expense ID 34: Date \'2025-12-03 00:00:00\' → \'\', Code \'TRV01\' → \'TRV00\'', '2026-01-17 03:03:18'),
(260, 5, 'UPDATE', 'expenses', 67, 'Updated expense ID 67: Date \'2026-01-17 03:03:08\' → \'\'', '2026-01-17 03:03:24'),
(261, 5, 'UPDATE', 'expenses', 32, 'Updated expense ID 32: Date \'2025-12-02 00:00:00\' → \'\', Category \'Invoice\' → \'InvoiceS\'', '2026-01-17 03:05:08'),
(262, 5, 'UPDATE', 'expenses', 68, 'Updated expense ID 68: Date \'2026-01-17 03:05:23\' → \'\', Classification \'546\' → \'546S\'', '2026-01-17 03:05:30'),
(263, 5, 'DELETE', 'expenses', 68, 'Deleted expense ID: 68 (Amount: 465454.00, Category: 6546, Date: 0000-00-00 00:00:00)', '2026-01-17 03:06:48'),
(264, 5, 'DELETE', 'expenses', 66, 'Deleted expense ID: 66 (Amount: 5000.00, Category: NONE, Date: 0000-00-00 00:00:00)', '2026-01-17 03:06:50'),
(265, 5, 'UPDATE', 'expenses', 69, 'Updated expense ID 69: Date \'2026-01-17\' → \'1970-01-01\', CA 5.00 → 6', '2026-01-17 03:11:32'),
(266, 5, 'UPDATE', 'expenses', 69, 'Updated expense ID 69: Date \'-0001-11-30\' → \'1970-01-01\'', '2026-01-17 03:11:32'),
(267, 5, 'DELETE', 'expenses', 31, 'Deleted expense ID: 31 (Amount: 1800.00, Category: Utility Bill, Date: 2025-12-01 00:00:00)', '2026-01-17 03:13:28'),
(268, 5, 'DELETE', 'expenses', 32, 'Deleted expense ID: 32 (Amount: 4500.00, Category: InvoiceS, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:29'),
(269, 5, 'DELETE', 'expenses', 33, 'Deleted expense ID: 33 (Amount: 900.00, Category: Receipt Attached, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:31'),
(270, 5, 'DELETE', 'expenses', 34, 'Deleted expense ID: 34 (Amount: 6000.00, Category: Receipt Attached, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:33'),
(271, 5, 'DELETE', 'expenses', 35, 'Deleted expense ID: 35 (Amount: 1500.00, Category: Receipt Attached, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:35'),
(272, 5, 'DELETE', 'expenses', 62, 'Deleted expense ID: 62 (Amount: 99999999.99, Category: paid, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:37'),
(273, 5, 'DELETE', 'expenses', 67, 'Deleted expense ID: 67 (Amount: 10000.00, Category: PAID, Date: 0000-00-00 00:00:00)', '2026-01-17 03:13:39'),
(296, 5, 'LOGIN', 'users', 5, 'Rafael215was logged in', '2026-02-20 22:34:12'),
(297, 5, 'EXPORT', 'users', 5, 'Rafael215 was export the activitylog', '2026-02-20 22:41:41'),
(298, 5, 'LOGIN', 'users', 5, 'Rafael215 was logged in', '2026-02-20 23:08:49'),
(299, 5, 'LOGIN', 'users', 5, 'Rafael215 was logged in', '2026-02-20 23:09:16'),
(300, 5, 'ARCHIVE', 'users', 13, 'User archived: raffy', '2026-02-20 23:09:30'),
(301, 5, 'ARCHIVE', 'users', 14, 'User archived: luoie caballero', '2026-02-20 23:09:33'),
(302, 5, 'ARCHIVE', 'users', 15, 'User archived: Jerelyn Abantao', '2026-02-20 23:09:37'),
(303, 5, 'ARCHIVE', 'users', 5, 'User archived: Rafael Rodelas', '2026-02-20 23:09:43'),
(304, 5, 'RESTORE', 'users', 5, 'User account restored: Rafael Rodelas', '2026-02-20 23:09:50'),
(305, 5, 'LOGIN', 'users', 5, 'Rafael215 was logged in', '2026-02-20 23:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `battery_details`
--

CREATE TABLE `battery_details` (
  `battery_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `voltage` decimal(4,2) NOT NULL,
  `model_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `battery_details`
--

INSERT INTO `battery_details` (`battery_id`, `product_id`, `brand`, `voltage`, `model_number`) VALUES
(13, 15, 'Energizer', 12.00, NULL),
(14, 16, 'Motolite Gold', 12.00, NULL),
(15, 17, 'Amaron', 12.00, NULL),
(16, 18, 'Energizer', 12.00, NULL),
(17, 19, 'Motolite Gold', 12.00, NULL),
(18, 20, 'Amaron', 12.00, NULL),
(19, 109, 'fghgfh', 0.00, 'hgfh'),
(20, 110, 'jhkjfgy', 20.00, 'kjh'),
(21, 116, 'hkhg', 0.00, '468546'),
(22, 120, 'kjhgk', 0.00, 'kj'),
(23, 126, 'Kenda Legends', 0.00, 'qnhh'),
(24, 132, '46546', 99.99, '5465');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`) VALUES
(1, 'salawag'),
(2, 'lipa'),
(3, 'lipa tire express'),
(4, 'salitran');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'accessories'),
(2, 'battery'),
(3, 'engine oil'),
(4, 'filter'),
(5, 'lugnuts'),
(6, 'mags'),
(7, 'mechanical product'),
(9, 'tire'),
(10, 'tire valve'),
(11, 'wheel weights'),
(12, 'motorcycle tires'),
(13, 'others'),
(16, 'services'),
(25, 'sGFDGF');

-- --------------------------------------------------------

--
-- Table structure for table `engineoil_details`
--

CREATE TABLE `engineoil_details` (
  `oil_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `oiltype` varchar(50) DEFAULT NULL,
  `capacity` decimal(4,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `engineoil_details`
--

INSERT INTO `engineoil_details` (`oil_id`, `product_id`, `brand`, `oiltype`, `capacity`) VALUES
(1, 3, 'Petrol', 'Synthetic', 0.0),
(4, 103, 'gvgh', 'vg', 0.0),
(5, 108, 'cdsfcds', 'cdc', 0.0),
(6, 121, 'kenda', 'lkajdl', 0.0),
(7, 131, '5646', '56', 456.0);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `month_name` varchar(20) DEFAULT NULL,
  `date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `details` text DEFAULT NULL,
  `ca` decimal(10,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `branch_id`, `month_name`, `date`, `amount`, `details`, `ca`, `category`, `classification`, `remarks`, `code`) VALUES
(21, 1, 'November', '2025-11-15 00:00:00', 550.00, 'Office supplies: paper, pens, toner cartridge', 0.00, 'Receipt Attached', 'Administrative', 'Restocked general supplies', 'SUP001'),
(22, 1, 'November', '2025-11-15 00:00:00', 1250.00, 'Lunch for client meeting with ABC Corp.', 0.00, 'Receipt Attached', 'Marketing/Sales', 'Business development lunch', 'MEET02'),
(23, 1, 'November', '2025-11-16 00:00:00', 3500.00, 'Monthly office rent payment', 0.00, 'Invoice', 'Operating Expenses', 'November rent', 'RENT11'),
(24, 1, 'November', '2025-11-16 00:00:00', 850.50, 'Electric bill for the month of October', 0.00, 'Utility Bill', 'Utilities', 'Paid via online banking', 'UTIL01'),
(25, 1, 'November', '2025-11-17 00:00:00', 250.00, 'Local transportation (Taxi to bank)', 0.00, 'Receipt Attached', 'Administrative', 'Bank deposit trip', 'TRN03'),
(26, 1, 'November', '2025-11-17 00:00:00', 15000.00, 'Advance for employee travel (CA)', 15000.00, 'Cash Advance', 'Personnel', 'CA for Manila trip', 'CA123'),
(27, 1, 'November', '2025-11-18 00:00:00', 450.00, 'Water dispenser refill service', 0.00, 'Receipt Attached', 'Utilities', 'Regular service', 'UTIL02'),
(28, 1, 'October', '2025-10-30 00:00:00', 900.00, 'Repair of leaky faucet in comfort room', 0.00, 'Invoice', 'Maintenance', 'Plumbing repair', 'MNT05'),
(29, 1, 'October', '2025-10-25 00:00:00', 120.00, 'Courier service to ship documents', 0.00, 'Receipt Attached', 'Administrative', 'Shipped contracts', 'SHP01'),
(30, 1, 'October', '2025-10-20 00:00:00', 1800.00, 'Software subscription renewal (Annual)', 0.00, 'Invoice', 'Technology', 'Yearly license fee', 'SWT07'),
(70, 2, 'January', '2026-01-17 03:12:57', 45654.00, '65465', 4654.00, 'WITH APPROVAL', '654', '654', '65'),
(71, 2, 'January', '2026-01-17 03:22:41', 5000.00, 'MERALCO', 5000.00, 'WITH APPROVAL', 'RETAIL EXPENSES', '654', 'SDF'),
(72, 2, 'January', '2026-01-17 03:24:51', 5000.00, 'MAYNILAD', 5000.00, 'WITH RECEIPT', 'RETAIL EXPENSES', 'NONE', 'NONE'),
(73, 2, 'January', '2026-01-17 03:25:42', 5200.00, 'FOOD', 5200.00, 'WITH RECEIPT', 'NONE', 'NONE', 'NONE');

-- --------------------------------------------------------

--
-- Table structure for table `filter_details`
--

CREATE TABLE `filter_details` (
  `filter_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `typeoffilter` varchar(50) NOT NULL,
  `vehicle_application` varchar(255) DEFAULT NULL,
  `filter_specs` varchar(100) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `housing_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `filter_details`
--

INSERT INTO `filter_details` (`filter_id`, `product_id`, `brand`, `typeoffilter`, `vehicle_application`, `filter_specs`, `material`, `housing_type`) VALUES
(2, 4, 'Fram', 'Fuel Filter', NULL, NULL, NULL, NULL),
(3, 4, 'KSY', 'Oil Filter', NULL, NULL, NULL, NULL),
(4, 97, 'RTERT', 'ERTER', 'TERTRE', 'ERTRET', 'ERTERT', NULL),
(5, 100, '', 'sdssss', 'fsdfd', 'fsdf', 'sdfsdf', NULL),
(6, 134, 'kjlkjhk', 'jhkjh', 'kjh', 'k23', '513', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `branch_id`, `product_id`, `quantity`) VALUES
(51, 1, 45, 0),
(53, 1, 49, 3),
(127, 2, 127, 6),
(137, 2, 137, 43),
(140, 2, 140, 7),
(141, 2, 141, 9),
(142, 2, 142, 19),
(143, 2, 143, 50),
(144, 2, 144, 50),
(145, 2, 145, 50),
(146, 2, 146, 30),
(147, 2, 147, 12),
(148, 2, 148, 20),
(149, 2, 149, 16),
(150, 2, 150, 20),
(151, 2, 151, 10),
(152, 2, 152, 100),
(153, 2, 153, 150),
(154, 2, 154, 56),
(155, 2, 155, 15);

-- --------------------------------------------------------

--
-- Table structure for table `lugnuts_details`
--

CREATE TABLE `lugnuts_details` (
  `lugnut_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `typeoflugnut` varchar(50) NOT NULL,
  `size` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lugnuts_details`
--

INSERT INTO `lugnuts_details` (`lugnut_id`, `product_id`, `typeoflugnut`, `size`) VALUES
(1, 5, 'Cone type', 2.75),
(2, 112, 'sdasds', 99.99),
(3, 152, 'Standard Hex', 12.50),
(4, 153, 'Locking Tuner', 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `mags_details`
--

CREATE TABLE `mags_details` (
  `mags_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `size` int(11) NOT NULL,
  `material` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mags_details`
--

INSERT INTO `mags_details` (`mags_id`, `product_id`, `brand`, `model`, `size`, `material`) VALUES
(1, 6, 'Rota', 'Boom Mags', 16, 'Alloy'),
(2, 57, 'sadfs', 'sdfsdf', 0, 'fsdfsd'),
(3, 58, 'sadfs', 'sdfsdf', 0, 'fsdfsd'),
(4, 59, 'sadfs', 'sdfsdf', 0, 'fsdfsd'),
(5, 60, 'sadfs', 'sdfsdf', 0, 'fsdfsd'),
(6, 61, 'kfjsa', 'jsalfas', 89, 'jdsfj'),
(7, 147, 'Volk', 'Racing Edition', 18, 'Aluminum'),
(8, 148, 'Enkei', 'Lightweight', 17, 'Alloy'),
(9, 149, 'Cosmis', 'Drift Series', 18, 'Cast Aluminum'),
(10, 150, 'Rota', 'Deep Dish', 17, 'Alloy'),
(11, 151, 'Work', 'CR Kai', 17, 'High-Silica');

-- --------------------------------------------------------

--
-- Table structure for table `mechanical_details`
--

CREATE TABLE `mechanical_details` (
  `mechanical_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `part_name` varchar(50) NOT NULL,
  `made` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `technical_spec` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanical_details`
--

INSERT INTO `mechanical_details` (`mechanical_id`, `product_id`, `part_name`, `made`, `model`, `technical_spec`) VALUES
(1, 7, 'Brake Pad', 'Toyota', 'Corolla', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mechanics`
--

CREATE TABLE `mechanics` (
  `mechanic_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `mechanic_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`mechanic_id`, `branch_id`, `mechanic_name`, `contact_number`, `email`, `created_at`, `archived_at`) VALUES
(1, 2, 'Jerelyn', '09784567895', NULL, '2026-01-03 01:54:24', NULL),
(4, 1, 'John Doe', '09123456789', 'john.doe@email.com', '2026-01-03 03:15:00', '2026-01-03 13:22:45'),
(5, 1, 'Jane Smith', '09223334444', 'jane.smith@email.com', '2026-01-03 03:15:00', '2026-01-03 13:22:04'),
(6, 2, 'Robert Wilson', '09556667777', 'robert.w@email.com', '2026-01-03 03:15:00', NULL),
(9, 1, 'louie e caballero', '0948465465', 'rafaelrodelas88@gmail.com', '2026-01-03 15:24:06', NULL),
(10, 2, 'JR Romulo', '09475618537', 'jr@gmail.com', '2026-01-17 08:26:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `motorcycle_tires_details`
--

CREATE TABLE `motorcycle_tires_details` (
  `motortire_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motorcycle_tires_details`
--

INSERT INTO `motorcycle_tires_details` (`motortire_id`, `product_id`, `brand`, `model`, `type`, `size`) VALUES
(1, 12, 'Thailand', 'Kawazaki', 'Sport', 34);

-- --------------------------------------------------------

--
-- Table structure for table `nitrogen_details`
--

CREATE TABLE `nitrogen_details` (
  `nitrogen_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `nitrogen_percentage` decimal(4,2) NOT NULL,
  `input_date` date NOT NULL,
  `type_of_vehicle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `other_details`
--

CREATE TABLE `other_details` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `detail1` varchar(255) DEFAULT NULL,
  `detail2` varchar(255) DEFAULT NULL,
  `detail3` varchar(255) DEFAULT NULL,
  `detail4` varchar(255) DEFAULT NULL,
  `detail5` varchar(255) DEFAULT NULL,
  `detail6` varchar(255) DEFAULT NULL,
  `critical_stock_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `cat_id`, `product_name`, `price`, `cost`, `detail1`, `detail2`, `detail3`, `detail4`, `detail5`, `detail6`, `critical_stock_level`) VALUES
(45, 9, 'hotfog', 20.00, 10.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 1, 'tiresyune', 20.00, 10.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(92, 5, 'rafaellllssllslsls', 45.00, 6.00, '', '', '', '', '', '', NULL),
(97, 4, 'RTERT', 87.00, 78.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(98, 1, 'TUTUUTU', 45.00, 54.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 1, 'rere', 45.00, 45.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(127, 9, 'ARIVO155R12C', 1800.00, 1564.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(137, 9, 'APOLLO265/70R16', 279.00, 6800.00, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(140, 9, 'APOLLO265/60R18', 6500.00, 6300.10, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(141, 9, 'APOLLO235/75R15', 6750.00, 7000.05, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(142, 9, 'ATLAS235/75R15', 4800.00, 4500.00, NULL, NULL, NULL, NULL, NULL, NULL, 8),
(143, 9, 'ATLAS265/65R17', 4850.00, 4200.00, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(144, 9, 'BRIDGESTONE265/60R18', 11180.00, 10780.00, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(145, 9, 'LENSO265/70R16', 9064.00, 8664.00, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(146, 9, 'MAXXISS205/70R15', 5361.17, 4961.17, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(147, 6, 'TE37 Sport', 15000.00, 12000.00, NULL, NULL, NULL, NULL, NULL, NULL, 4),
(148, 6, 'RPF1 Classic', 10500.00, 8500.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(149, 6, 'XT-206R', 13000.00, 10000.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(150, 6, 'Grid Concave', 7500.00, 5500.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(151, 6, 'Work Emotion', 14500.00, 11500.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(152, 5, 'Steel Hex Nut', 150.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(153, 5, 'Spline Drive Black12x1.25mm', 110.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, 10),
(154, 9, 'KENDA700/28C', 5000.00, 4000.00, NULL, NULL, NULL, NULL, NULL, NULL, 5),
(155, 9, 'Tire exodus<br>Kenda Legends 5, STRIPES japan', 45.50, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, 10);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sales_id` int(11) NOT NULL,
  `si_number` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `mechanic_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `vehicle` varchar(100) DEFAULT NULL,
  `plate_no` varchar(50) DEFAULT NULL,
  `odometer` varchar(50) DEFAULT NULL,
  `cp_number` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `gross_profit` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `discrepancy` text DEFAULT NULL,
  `front_incentive` decimal(10,2) DEFAULT NULL,
  `skill_incentive` decimal(10,2) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sales_id`, `si_number`, `date`, `mechanic_id`, `category`, `quantity`, `item_name`, `customer_name`, `vehicle`, `plate_no`, `odometer`, `cp_number`, `total_amount`, `total_cost`, `gross_profit`, `remarks`, `discrepancy`, `front_incentive`, `skill_incentive`, `branch_id`, `payment_method`, `description`) VALUES
(335, '46546', '2026-01-17 01:31:10', 6, 'tire', 1, 'ARIVO155R12C', 'Jade Advincula', 'Mustang', 'PLE24345', '45', '09475618537', 1800.00, 1564.00, 236.00, '', 'none', 50.00, 100.00, 2, 'Cash', ''),
(336, '46546', '2026-01-17 01:31:10', 6, 'Service', 1, 'tire allignment', 'Jade Advincula', 'Mustang', 'PLE24345', '45', '09475618537', 300.00, 300.00, 0.00, '', 'none', 50.00, 100.00, 2, 'Cash', ''),
(337, '564656', '2026-01-17 01:42:30', 1, 'tire', 6, 'APOLLO265/60R18', 'Rafael Rodelas', 'Mustang', 'IUIU5456', '665', '09475618537', 39534.60, 37800.60, 1734.00, '656', '45', 654.00, 5456.00, 2, 'Cash', 'hi low`'),
(338, '564656', '2026-01-17 01:42:30', 1, 'tire', 5, 'APOLLO265/70R16', 'Rafael Rodelas', 'Mustang', 'IUIU5456', '665', '09475618537', 35350.00, 34000.00, 1350.00, '656', '45', 654.00, 5456.00, 2, 'Cash', 'hi low`'),
(339, '564656', '2026-01-17 01:42:30', 1, 'tire', 3, 'ARIVO155R12C', 'Rafael Rodelas', 'Mustang', 'IUIU5456', '665', '09475618537', 5400.00, 4692.00, 708.00, '656', '45', 654.00, 5456.00, 2, 'Cash', 'hi low`'),
(340, '564656', '2026-01-17 01:42:30', 1, 'Service', 1, 'change oil', 'Rafael Rodelas', 'Mustang', 'IUIU5456', '665', '09475618537', 100.00, 100.00, 0.00, '656', '45', 654.00, 5456.00, 2, 'Cash', 'hi low`'),
(341, '546546', '2026-01-17 02:27:03', 6, 'tire', 1, 'APOLLO265/60R18', 'Jiji san Totomas', 'Mirage', '55465465', '45', '09475618537', 6589.10, 6300.10, 289.00, '65465', '46654', 50.00, 654.00, 2, 'Cash', '465'),
(342, '546546', '2026-01-17 02:27:03', 6, 'tire', 1, 'APOLLO265/65R17', 'Jiji san Totomas', 'Mirage', '55465465', '45', '09475618537', 6.00, 6.00, 0.00, '65465', '46654', 50.00, 654.00, 2, 'Cash', '465'),
(343, '5465', '2026-01-17 02:47:45', 1, 'tire', 1, 'APOLLO235/75R15', 'Marinhel Saludar', 'Mirage', 'HIUHI5465', '654', '09475618537', 7500.00, 7000.05, 499.95, 'none', '45', 42.00, 50.00, 2, 'Cash', 'none'),
(344, '5465', '2026-01-17 02:47:45', 1, 'tire', 1, 'APOLLO265/60R18', 'Marinhel Saludar', 'Mirage', 'HIUHI5465', '654', '09475618537', 6589.10, 6300.10, 289.00, 'none', '45', 42.00, 50.00, 2, 'Cash', 'none'),
(345, '0365', '2026-01-17 02:48:38', 1, 'tire', 6, 'APOLLO235/75R15', 'Jade Advincula', 'Mustang', 'PLE24345', '45', '09475618537', 45000.00, 42000.30, 2999.70, 'none', '56', 20.00, 50.00, 2, 'Cash', 'none'),
(346, '4655', '2026-01-17 02:59:59', 1, 'tire', 1, 'APOLLO235/75R15', 'RAFAEL RODELAS', 'Mustang', 'RA54654', '46', '09475618537', 7500.00, 7000.05, 499.95, 'none', 'none', 54.00, 54.00, 2, 'Cash', 'NONE'),
(347, '4655', '2026-01-17 02:59:59', 1, 'tire', 1, 'APOLLO265/60R18', 'RAFAEL RODELAS', 'Mustang', 'RA54654', '46', '09475618537', 6589.10, 6300.10, 289.00, 'none', 'none', 54.00, 54.00, 2, 'Cash', 'NONE'),
(348, '46546', '2026-01-17 03:28:50', 1, 'tire', 1, 'APOLLO265/70R16', 'RAFAEL', 'MUSTANG', 'YGJHG45', '45', '09475618537', 279.00, 6800.00, -6521.00, '548', 'NONE', 45.00, 5455.00, 2, 'Cash', 'NONE'),
(349, '46654', '2026-01-17 07:13:45', 1, '54', 1, 'Service', 'Jerelyn', '0', '654654`', '45', '54', 45.00, 0.00, 45.00, '455', '54', 45.00, 45.00, 2, 'Cash', '54'),
(350, '6546', '2026-01-17 07:15:08', 1, 'tire', 1, 'APOLLO235/75R15', 'RAFAEL RODELAS', 'Mustang', 'RA54654', '46', '09475618537', 7500.00, 7000.05, 499.95, '545', '45', 45.00, 45.00, 2, 'Cash', 'none'),
(351, '6546', '2026-01-17 07:15:08', 1, 'tire', 1, 'APOLLO265/60R18', 'RAFAEL RODELAS', 'Mustang', 'RA54654', '46', '09475618537', 6500.00, 6300.10, 199.90, '545', '45', 45.00, 45.00, 2, 'Cash', 'none'),
(352, '6546', '2026-01-17 07:15:08', 1, 'Service', 1, 'tire allignment', 'RAFAEL RODELAS', 'Mustang', 'RA54654', '46', '09475618537', 50.00, 50.00, 0.00, '545', '45', 45.00, 45.00, 2, 'Cash', 'none'),
(353, '65465', '2026-01-17 08:42:17', 6, 'tire', 1, 'APOLLO265/60R18', 'jray yugo', 'mustang', '64465465', '45', '09475618537', 6500.00, 6300.10, 199.90, '654', '45', 45.00, 45.00, 2, 'Cash', '645'),
(354, '4654', '2026-01-17 13:55:03', 1, 'NONE', 1, 'Service', 'RAFAEL RODELAS', '0', 'GHJ456', 'NONE', '0947756184564', 768.00, 0.00, 768.00, 'NONE', '0', 0.00, 0.00, 2, 'Cash', 'NONE'),
(355, '2345', '2026-01-17 13:59:20', 1, 'tire', 1, 'APOLLO235/75R15', 'JEANN', 'szfdsfs', 'RT6', '', '0989', 9000.00, 7000.05, 1999.95, '', '', 0.00, 0.00, 2, 'Cash', ''),
(356, '2345', '2026-01-17 13:59:20', 1, 'tire', 1, 'APOLLO265/70R16', 'JEANN', 'szfdsfs', 'RT6', '', '0989', 279.00, 6800.00, -6521.00, '', '', 0.00, 0.00, 2, 'Cash', ''),
(357, '2345', '2026-01-17 13:59:20', 1, 'Service', 1, '100', 'JEANN', 'szfdsfs', 'RT6', '', '0989', 100.00, 100.00, 0.00, '', '', 0.00, 0.00, 2, 'Cash', ''),
(358, '46546456', '2026-02-18 13:05:53', 1, 'tire', 1, 'ATLAS235/75R15', 'Rafael Rodelas', 'Mustang', '263826', '454', '09475618537', 4800.00, 4500.00, 300.00, '6546', '263826', 100.00, 80.00, 2, 'Cash', 'Order of rafael Rodelas for production');

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `sales_item_id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tirevalve_details`
--

CREATE TABLE `tirevalve_details` (
  `tirevalve_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `valve_type` varchar(5) NOT NULL,
  `material` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tirevalve_details`
--

INSERT INTO `tirevalve_details` (`tirevalve_id`, `product_id`, `valve_type`, `material`, `color`) VALUES
(1, 10, 'High ', 'Aluminum', 'Green');

-- --------------------------------------------------------

--
-- Table structure for table `tire_details`
--

CREATE TABLE `tire_details` (
  `tire_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `pattern` varchar(100) DEFAULT NULL,
  `made` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tire_details`
--

INSERT INTO `tire_details` (`tire_id`, `product_id`, `brand`, `size`, `pattern`, `made`) VALUES
(36, 45, 'Kenda Legends', '3', 'STRIPES', 'japan'),
(38, 49, 'Kenda Legends', '3', 'STRIPES', 'japan'),
(48, 127, 'ARIVO', '155R12C', 'TRANSITO ARZ 6-M', ''),
(55, 137, 'APOLLO', '265/70R16', 'APTERRA AT 2', 'INDIA'),
(58, 140, 'APOLLO', '265/60R18', 'APTERRA AT 2', 'INDIA'),
(59, 141, 'APOLLO', '265/50R20', 'APTERRA AT 2', 'INDIA'),
(60, 142, 'ATLAS', '235/75R15', 'PARALLER AT', 'THAILAND'),
(61, 143, 'ATLAS', '265/65R17', 'PARALLER AT 2', 'THAILAND'),
(62, 144, 'BRIDGESTONE', '265/60R18', 'DUELLER A/T 002', 'THAILAND'),
(63, 145, 'LENSO', '265/70R16', 'RTX / RT', 'THAILAND'),
(64, 146, 'MAXXISS', '205/70R15', 'BRAVO AT771', 'THAILAND'),
(65, 154, 'KENDA', '700/28C', 'JKHAS78', 'CHINA'),
(66, 155, 'Kenda Legends', '3', 'STRIPES', 'japan');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('master_admin','inventory_staff','cashier','admin_staff') NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lock_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `contact_number`, `branch_id`, `email`, `fullname`, `created_at`, `updated_at`, `archived_at`, `failed_attempts`, `lock_until`) VALUES
(5, 'Rafael215', '$2y$10$BqG8pOt3ZnKWBeb0.MzkdeqZfhOVWpVjkuqFK24WzJ1Q1wgpoAFHW', 'master_admin', '09475618533231', 2, 'rafaelrodelas88@gmail.com', 'Rafael Rodelas', '2025-12-30 18:36:51', '2026-02-20 15:17:24', NULL, 0, NULL),
(16, 'JerelynHeart', '$2y$10$kfxChTtfU6WQdWNO2Xf.Veu3sb1ZV3dJHUHyW2SBaGXmJhb7Vdogu', 'admin_staff', '09478731589', 2, 'jerelyn@gmail.com', 'Jerelyn Abantao', '2026-02-20 15:11:23', '2026-02-20 15:17:01', NULL, 5, '2026-02-20 08:20:01'),
(17, 'Louie56', '$2y$10$/nBYs6Q2bzb2JzKNYOso4uvcStCAF0T79JPQxoPdFabDhqWQ0rOjy', 'admin_staff', '09678961234', 2, 'Louie@gmail.com', 'Louie Caballero', '2026-02-20 15:15:39', '2026-02-20 15:15:39', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wheelweights_details`
--

CREATE TABLE `wheelweights_details` (
  `wheel_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `weight` decimal(4,2) NOT NULL,
  `material` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wheelweights_details`
--

INSERT INTO `wheelweights_details` (`wheel_id`, `product_id`, `model`, `weight`, `material`) VALUES
(1, 11, 'Boom mags', 0.00, 'Alloy'),
(7, 53, '78374ygdd', 3.00, 'nanotech');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accessories_details`
--
ALTER TABLE `accessories_details`
  ADD PRIMARY KEY (`accessories_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `battery_details`
--
ALTER TABLE `battery_details`
  ADD PRIMARY KEY (`battery_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `engineoil_details`
--
ALTER TABLE `engineoil_details`
  ADD PRIMARY KEY (`oil_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `filter_details`
--
ALTER TABLE `filter_details`
  ADD PRIMARY KEY (`filter_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `lugnuts_details`
--
ALTER TABLE `lugnuts_details`
  ADD PRIMARY KEY (`lugnut_id`);

--
-- Indexes for table `mags_details`
--
ALTER TABLE `mags_details`
  ADD PRIMARY KEY (`mags_id`);

--
-- Indexes for table `mechanical_details`
--
ALTER TABLE `mechanical_details`
  ADD PRIMARY KEY (`mechanical_id`);

--
-- Indexes for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`mechanic_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `motorcycle_tires_details`
--
ALTER TABLE `motorcycle_tires_details`
  ADD PRIMARY KEY (`motortire_id`);

--
-- Indexes for table `nitrogen_details`
--
ALTER TABLE `nitrogen_details`
  ADD PRIMARY KEY (`nitrogen_id`);

--
-- Indexes for table `other_details`
--
ALTER TABLE `other_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sales_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `mechanic_id` (`mechanic_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`sales_item_id`),
  ADD KEY `sales_id` (`sales_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tirevalve_details`
--
ALTER TABLE `tirevalve_details`
  ADD PRIMARY KEY (`tirevalve_id`);

--
-- Indexes for table `tire_details`
--
ALTER TABLE `tire_details`
  ADD PRIMARY KEY (`tire_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `wheelweights_details`
--
ALTER TABLE `wheelweights_details`
  ADD PRIMARY KEY (`wheel_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accessories_details`
--
ALTER TABLE `accessories_details`
  MODIFY `accessories_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=306;

--
-- AUTO_INCREMENT for table `battery_details`
--
ALTER TABLE `battery_details`
  MODIFY `battery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `engineoil_details`
--
ALTER TABLE `engineoil_details`
  MODIFY `oil_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `filter_details`
--
ALTER TABLE `filter_details`
  MODIFY `filter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `lugnuts_details`
--
ALTER TABLE `lugnuts_details`
  MODIFY `lugnut_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mags_details`
--
ALTER TABLE `mags_details`
  MODIFY `mags_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `mechanical_details`
--
ALTER TABLE `mechanical_details`
  MODIFY `mechanical_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `mechanic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `motorcycle_tires_details`
--
ALTER TABLE `motorcycle_tires_details`
  MODIFY `motortire_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nitrogen_details`
--
ALTER TABLE `nitrogen_details`
  MODIFY `nitrogen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `other_details`
--
ALTER TABLE `other_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sales_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=359;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `sales_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `tirevalve_details`
--
ALTER TABLE `tirevalve_details`
  MODIFY `tirevalve_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tire_details`
--
ALTER TABLE `tire_details`
  MODIFY `tire_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `wheelweights_details`
--
ALTER TABLE `wheelweights_details`
  MODIFY `wheel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD CONSTRAINT `mechanics_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `other_details`
--
ALTER TABLE `other_details`
  ADD CONSTRAINT `other_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`mechanic_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`sales_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `tire_details`
--
ALTER TABLE `tire_details`
  ADD CONSTRAINT `tire_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
