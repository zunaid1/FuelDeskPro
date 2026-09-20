-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3309
-- Generation Time: Aug 03, 2026 at 01:02 PM
-- Server version: 12.3.2-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fueldesk_pro`
--

-- --------------------------------------------------------

--
-- Table structure for table `cfg_companyprofile`
--

CREATE TABLE `cfg_companyprofile` (
  `CompanyID` int(11) NOT NULL,
  `CompanyCode` varchar(20) NOT NULL,
  `CompanyName` varchar(200) NOT NULL,
  `CompanyNameBN` varchar(200) DEFAULT NULL,
  `ShortName` varchar(50) DEFAULT NULL,
  `ProprietorName` varchar(150) DEFAULT NULL,
  `ContactPerson` varchar(150) DEFAULT NULL,
  `MobileNo` varchar(20) DEFAULT NULL,
  `PhoneNo` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Website` varchar(150) DEFAULT NULL,
  `BusinessType` varchar(100) DEFAULT NULL,
  `Address` varchar(500) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `District` varchar(100) DEFAULT NULL,
  `PostalCode` varchar(20) DEFAULT NULL,
  `Country` varchar(100) DEFAULT NULL,
  `Logo` varchar(255) DEFAULT NULL,
  `Favicon` varchar(255) DEFAULT NULL,
  `CurrencyCode` varchar(10) DEFAULT 'BDT',
  `CurrencySymbol` varchar(10) DEFAULT '৳',
  `TimeZone` varchar(100) DEFAULT 'Asia/Dhaka',
  `DateFormat` varchar(20) DEFAULT 'dd-MM-yyyy',
  `TimeFormat` varchar(20) DEFAULT '24 Hour',
  `FinancialYearStart` date DEFAULT NULL,
  `FinancialYearEnd` date DEFAULT NULL,
  `DefaultShiftID` int(11) DEFAULT NULL,
  `DefaultLanguage` varchar(20) DEFAULT 'English',
  `AllowNegativeStock` bit(1) DEFAULT b'0',
  `AutoBackup` bit(1) DEFAULT b'1',
  `BackupPath` varchar(500) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT NULL,
  `IsActive` bit(1) DEFAULT b'1',
  `IsDeleted` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cfg_companyprofile`
--

INSERT INTO `cfg_companyprofile` (`CompanyID`, `CompanyCode`, `CompanyName`, `CompanyNameBN`, `ShortName`, `ProprietorName`, `ContactPerson`, `MobileNo`, `PhoneNo`, `Email`, `Website`, `BusinessType`, `Address`, `City`, `District`, `PostalCode`, `Country`, `Logo`, `Favicon`, `CurrencyCode`, `CurrencySymbol`, `TimeZone`, `DateFormat`, `TimeFormat`, `FinancialYearStart`, `FinancialYearEnd`, `DefaultShiftID`, `DefaultLanguage`, `AllowNegativeStock`, `AutoBackup`, `BackupPath`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'SFS', 'Shangu LPG Filling Station', 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন', 'SFS', 'Main Uddin', 'Rokon Uddin', '01819800600', '0', 'sfs@gmail.com', 'sfs.com', 'Fuel Filling Station', 'Amilaish, Satkania, Chattogram.', 'Chattogram', 'Chattogram', '4396', 'Bangladesh', NULL, NULL, 'BDT', '৳', 'Asia/Dhaka', 'dd-MM-yyyy', '24 Hour', '2026-01-01', '2026-12-31', NULL, 'English', b'1', b'1', NULL, NULL, '2026-07-12 22:25:33', NULL, NULL, b'1', b'0');

-- --------------------------------------------------------

--
-- Table structure for table `cfg_paymentmethod`
--

CREATE TABLE `cfg_paymentmethod` (
  `PaymentMethodID` int(11) NOT NULL,
  `MethodCode` varchar(30) NOT NULL COMMENT 'CASH, BANK, MOBILE_BANKING, CHEQUE, CARD, OTHER',
  `MethodName` varchar(100) NOT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment method master (Cash/Bank/Mobile Banking/Cheque/Card/Other)';

--
-- Dumping data for table `cfg_paymentmethod`
--

INSERT INTO `cfg_paymentmethod` (`PaymentMethodID`, `MethodCode`, `MethodName`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'PM101', 'Cash', NULL, '2026-07-11 19:52:44', NULL, '2026-07-15 00:30:49', 1, 0),
(102, 'PM102', 'POS', NULL, '2026-07-11 19:52:44', NULL, '2026-07-15 00:30:56', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cfg_systemsetting`
--

CREATE TABLE `cfg_systemsetting` (
  `SystemSettingID` int(11) NOT NULL,
  `SettingKey` varchar(100) NOT NULL,
  `SettingValue` varchar(500) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Extensible key-value system settings (future-proofing)';

--
-- Dumping data for table `cfg_systemsetting`
--

INSERT INTO `cfg_systemsetting` (`SystemSettingID`, `SettingKey`, `SettingValue`, `Description`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'IsMeterReadingReadOnly', 'Yes', 'এই সেটিংটা যদি Yes থাকে, তাহলে প্রতিদিন হিসাব করার সময় পূর্বের মিটার রিডিং ReadOnly দেখাবে। অর্থাৎ রিডিং পরিবর্তন করতে পারবে না। \r\n\r\nযদি No থাকে, তাহলে পরিবর্তন করতে পারবে।', NULL, '2026-07-11 19:59:14', NULL, '2026-07-24 21:45:07', 1, 0),
(2, 'InvoicePrefix', 'INV', 'Invoice Prefix', NULL, '2026-07-12 22:19:58', NULL, '2026-07-12 22:19:58', 1, 0),
(3, 'MoneyReceiptPrefix', 'MR', 'Money Receipt Prefix', NULL, '2026-07-12 22:20:55', NULL, '2026-07-12 22:20:55', 1, 0),
(4, 'ExpenseVoucherPrefix', 'EV', 'Expense Voucher Prefix No', NULL, '2026-07-12 22:21:38', NULL, '2026-07-12 22:22:22', 1, 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `licenseexpiringsoon`
-- (See below for the actual view)
--
CREATE TABLE `licenseexpiringsoon` (
`CompanyLicenseID` int(11)
,`LicenseName` varchar(150)
,`LicenseNo` varchar(100)
,`ExpiryDate` date
,`RemainingDays` int(8)
);

-- --------------------------------------------------------

--
-- Table structure for table `log_auditlog`
--

CREATE TABLE `log_auditlog` (
  `AuditLogID` bigint(20) NOT NULL,
  `TableName` varchar(100) NOT NULL,
  `RecordID` bigint(20) NOT NULL,
  `ActionType` varchar(20) NOT NULL,
  `OldData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`OldData`)),
  `NewData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`NewData`)),
  `ChangedBy` int(11) DEFAULT NULL COMMENT 'Logical FK -> sys_User.UserID',
  `ChangedAt` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `mst_customer`
--

CREATE TABLE `mst_customer` (
  `CustomerID` int(11) NOT NULL,
  `CustomerName` varchar(150) NOT NULL,
  `Mobile` varchar(30) DEFAULT NULL,
  `VehicleNumber` varchar(50) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `OpeningDue` decimal(14,2) NOT NULL DEFAULT 0.00,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credit customers';

--
-- Dumping data for table `mst_customer`
--

INSERT INTO `mst_customer` (`CustomerID`, `CustomerName`, `Mobile`, `VehicleNumber`, `Address`, `OpeningDue`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'Walking Customer', '0', '0', '...', 0.00, NULL, '2026-07-11 20:02:43', NULL, '2026-07-11 20:02:43', 1, 0),
(102, 'MR. ZUNAID', '016720082932', 'L-2588524', '', 0.00, 100, '2026-07-25 18:43:32', NULL, '2026-07-25 18:43:32', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_dispenser`
--

CREATE TABLE `mst_dispenser` (
  `DisID` int(11) NOT NULL,
  `DisName` varchar(100) NOT NULL,
  `DisCode` varchar(30) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispenser units housing nozzles';

--
-- Dumping data for table `mst_dispenser`
--

INSERT INTO `mst_dispenser` (`DisID`, `DisName`, `DisCode`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'Dispenser-1', 'D1', NULL, '2026-07-12 20:00:47', 100, '2026-07-25 14:19:42', 1, 0),
(102, 'Dispenser-2', 'D2', 100, '2026-07-25 14:19:28', 100, '2026-07-25 14:19:36', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_employee`
--

CREATE TABLE `mst_employee` (
  `Id` int(11) NOT NULL,
  `EmployeeId` varchar(30) DEFAULT NULL,
  `NameEN` varchar(150) NOT NULL,
  `NameBN` varchar(150) DEFAULT NULL,
  `FatherName` varchar(150) DEFAULT NULL,
  `MotherName` varchar(150) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `JoiningDate` date DEFAULT NULL,
  `Mobile` varchar(30) NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `NationalID` varchar(50) NOT NULL,
  `Guarantor` varchar(150) DEFAULT NULL,
  `Salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ImagePath` varchar(255) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `mst_employee`
--

INSERT INTO `mst_employee` (`Id`, `EmployeeId`, `NameEN`, `NameBN`, `FatherName`, `MotherName`, `DateOfBirth`, `JoiningDate`, `Mobile`, `Address`, `NationalID`, `Guarantor`, `Salary`, `ImagePath`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'EMP101', 'Mohammad Zunaid Hossain', 'মুহাম্মদ জুনাইদ হোসাইন', NULL, NULL, NULL, NULL, '01672002552', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-11 20:11:41', NULL, '2026-07-25 22:37:10', 1, 0),
(102, 'EMP102', 'Rokon Uddin', 'রোকন উদ্দিন', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-11 20:13:31', NULL, '2026-07-14 17:25:38', 1, 0),
(103, 'EMP103', 'Fahim Uddin', 'ফাহিম উদ্দিন', '...', '...', NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:15:14', NULL, '2026-07-14 17:25:38', 1, 0),
(104, 'EMP104', 'Anik Chowdhury', 'অনিক চৌধুরী', '...', '...', NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:15:14', NULL, '2026-07-14 17:25:38', 1, 0),
(105, 'EMP105', 'Mamunul Islam', 'মামুনুল ইসলাম', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:16:18', NULL, '2026-07-14 17:25:38', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_expensecategory`
--

CREATE TABLE `mst_expensecategory` (
  `ExpenseCategoryID` int(11) NOT NULL,
  `CategoryNameEN` varchar(150) NOT NULL,
  `CategoryNameBN` varchar(150) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Top-level expense buckets (bilingual)';

--
-- Dumping data for table `mst_expensecategory`
--

INSERT INTO `mst_expensecategory` (`ExpenseCategoryID`, `CategoryNameEN`, `CategoryNameBN`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'Fire Extinguisher', 'অগ্নি নির্বাপক', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(2, 'WiFi Bill', 'ওয়াইফাই বিল', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(3, 'Purchase', 'ক্রয়', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(4, 'Food', 'খাবার', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(5, 'Fine/Penalty', 'জরিমানা', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(6, 'Donation', 'দান', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(7, 'Administrative', 'প্রশাসনিক', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(8, 'Gratuity/Entertainment', 'বখশিস/আপ্যায়ন', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(9, 'Electricity', 'বিদ্যুৎ', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(10, 'Miscellaneous', 'বিবিধ', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(11, 'Salary', 'বেতন', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(12, 'Allowance/Honorarium', 'ভাতা-সম্মানী', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(13, 'Land', 'ভূমি', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(14, 'Marketing', 'মার্কেটিং', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(15, 'Mobile Bill', 'মোবাইল বিল', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(16, 'Transportation', 'যাতায়াত', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(17, 'Maintenance', 'রক্ষণাবেক্ষণ', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(18, 'Construction', 'নির্মাণ', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(19, 'License', 'লাইসেন্স', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(20, 'Stationery', 'ষ্টেশনারী', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(21, 'Vegetable Farming', 'সবজি চাষ', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0),
(22, 'Commission Payment to Customer', 'সমন্বয়/গ্রাহককে কমিশন প্রদান', 0, '2026-07-12 00:00:00', 0, '2026-07-12 10:12:21', 1, 0),
(23, 'Beautification', 'সৌন্দর্য বর্ধন', 0, '2026-07-12 00:00:00', 0, '2026-07-12 00:00:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_expenseparticular`
--

CREATE TABLE `mst_expenseparticular` (
  `ExpenseParticularID` int(11) NOT NULL,
  `ParticularID` varchar(30) DEFAULT NULL,
  `ExpenseCategoryID` int(11) NOT NULL COMMENT 'Logical FK -> mst_ExpenseCategory.ExpenseCategoryID',
  `ParticularNameEN` varchar(150) NOT NULL,
  `ParticularNameBN` varchar(150) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Line items under expense category; also used by Others Collection (resolved conflict, migration authoritative)';

--
-- Dumping data for table `mst_expenseparticular`
--

INSERT INTO `mst_expenseparticular` (`ExpenseParticularID`, `ParticularID`, `ExpenseCategoryID`, `ParticularNameEN`, `ParticularNameBN`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'PRT1', 1, 'Fire Extinguisher Purchase / Refill', 'অগ্নি নির্বাপক', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(2, 'PRT2', 2, 'WiFi Bill', 'ওয়াইফাই বিল', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(3, 'PRT3', 3, 'Nozzle / Nozzle Head Purchase', 'নজেল/ নজেলের মুখ ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(4, 'PRT4', 3, 'IPS Purchase', 'IPS ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(5, 'PRT5', 3, 'Engine Oil Purchase', 'ইঞ্জিন অয়েল ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(6, 'PRT6', 3, 'Grease Purchase', 'গ্রীজ ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(7, 'PRT7', 3, 'Socket / Electrical Socket', 'চকেট', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(8, 'PRT8', 3, 'Generator Water (Coolant)', 'জেনারেটরের পানি', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(9, 'PRT9', 3, 'Ceiling Fan / Electrical Goods Purchase', 'ঝুঁড়ি ফ্যান/ ইলেকট্রিক মালামাল ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(10, 'PRT10', 3, 'Tape', 'টেপ', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(11, 'PRT11', 3, 'Diesel', 'ডিজেল', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(12, 'PRT12', 3, 'Bulb / Flashlight / Electrical Equipment / Router etc.', 'বাল্ব/ফ্লাশ লাইট /ইলেকট্রিক সরঞ্জাম/রাউটার ইত্যাদি', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(13, 'PRT13', 3, 'Miscellaneous Purchase', 'বিবিধ ক্রয়', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(14, 'PRT14', 3, 'Motor / Water Pump', 'মটর', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(15, 'PRT15', 3, 'Rubbish (Concret)', 'রাবিশ / কংক্রিট', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(16, 'PRT16', 4, 'Water', 'পানি', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(17, 'PRT17', 4, 'Gas for Use', 'ব্যবহারের গ্যাস', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(18, 'PRT18', 4, 'Staff Food / Snacks / Iftar / Medicine', 'স্টাপদের খাবার/নাস্তা/ ইফতার /ঔষধ', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(19, 'PRT19', 5, 'Mobile Court Fine for Expired Fire Extinguisher', 'মেয়াত্তেীর্ণ অগ্নি নির্বাপকের কারণে ভ্রাম্যমান আদালতের জরিমানা', NULL, '2026-07-12 06:11:58', NULL, '2026-07-14 17:19:11', 1, 0),
(20, 'PRT20', 6, 'Donation - Patiya Madrasa', 'দান/অনুদান (পটিয়া মাদরাসা)', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(21, 'PRT21', 6, 'Donation - Transgender Community', 'দান/অনুদান: হিজড়া সম্প্রদায়', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(22, 'PRT22', 6, 'Sadaqah (Voluntary Charity)', 'ছদকা', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(23, 'PRT23', 7, 'Highway Police (Conveince)', 'হাইওয়ে থানা', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(24, 'PRT24', 7, 'Land Measurement - Surveyor Fee', 'জমির পরিমাপ-সার্ভেয়ার', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(25, 'PRT25', 7, 'Administrative / Political / Other Expenses', 'প্রশাসনিক/ রাজনৈতিক/অন্যান্য খরচ', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(26, 'PRT26', 7, 'Fire Service Fee', 'ফায়ার সার্ভিস', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(27, 'PRT27', 7, 'Expenses Related to Arrest of Mr. Rokun Uddin', 'জনাব রোকন উদ্দিন গ্রেফতার হওয়া বাবদ বিবিধ খাতে খরচ', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(28, 'PRT28', 7, 'Roads and Highway (Sharif U. Majumdar, Road Building)', 'রোডস এন্ড হাইওয়ে(শরীফ উ. মজুমদার, সড়ক ভবন)', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(29, 'PRT29', 7, 'Roads and Highway (Nurul Haq, Dohazari)', 'রোডস এন্ড হাইওয়ে (নুরুল হক, দোহাজারী)', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(30, 'PRT30', 7, 'LPG Central Association Annual Subscription', 'এল.পি.জি কেন্দ্রীয় সমিতি বাৎসরিক চাঁদা', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(31, 'PRT31', 8, 'Convenience - Gas Supply Related Entertainment / Gratuity', 'বখশিষ- গ্যাস সরবরাহ সংশ্লিষ্টদের আপ্যায়ন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(32, 'PRT32', 8, 'Gratuity / Entertainment / Labor / Guest', 'বখশিস/আপ্যায়ন/শ্রমিক/অতিথি', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(33, 'PRT33', 9, 'Electricity Prepaid Card', 'বিদ্যুৎ কার্ড', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(34, 'PRT34', 10, 'Miscellaneous - Rocket (Mobile Banking)', 'বিবিধ (রকেট)', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(35, 'PRT35', 13, 'Land - Anwar Munshi', 'আনোয়ার মুন্সি', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(36, 'PRT36', 13, 'Land/ Dakhila', 'খাজনা/দাখিলা', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(37, 'PRT37', 14, 'Promotion / Marketing / Newspaper Advertisement', 'প্রচার/মার্কেটিং/পত্রিকা বিজ্ঞাপন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(38, 'PRT38', 15, 'Mobile Bill', 'মোবাইল বিল', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(39, 'PRT39', 16, 'Transportation - Others', 'যাতায়াত-অন্যান্য', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(40, 'PRT40', 16, 'Transportation - Junaid', 'যাতায়াত-জুনাইদ', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(41, 'PRT41', 16, 'Transportation - Mamun', 'যাতায়াত-মামুন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(42, 'PRT42', 17, 'IPS Repair', 'IPS মেরামত', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(43, 'PRT43', 17, 'Generator Repair', 'জেনারেটর মেরামত', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(44, 'PRT44', 17, 'Technician Charge', 'টেকনেশিয়ান', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(45, 'PRT45', 17, 'Dispenser Filter Replacement', 'ডিস্পেন্সার ফিল্টার', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(46, 'PRT46', 17, 'Dispenser Servicing', 'ডেসপেন্সার সার্ভিসিং', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(47, 'PRT47', 17, 'Desh Engineering Service', 'দেশ ইঞ্জিনিয়ারিং', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(48, 'PRT48', 17, 'Water Motor / Line / Pipe / Tank / Drainage Repair etc.', 'পানির মোটর/লাইন/পাইপ/ট্যাংক/ পানি নিস্কাশন/ইত্যাদি মেরামত', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(49, 'PRT49', 17, 'Refrigerator Repair', 'ফ্রিজ মেরামত', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(50, 'PRT50', 17, 'Electrical Line Repair / Electrician', 'বিদ্যুৎ লাইন মেরামত/ বিদ্যুৎ মিস্ত্রী', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(51, 'PRT51', 17, 'Motor Mechanic - Hossain', 'মোটর মিস্ত্রি (হোছাইন)', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(52, 'PRT52', 17, 'Labor Charge', 'লেবার', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(53, 'PRT53', 17, 'Miscellaneous Maintenance - Excavator', 'বিবিধ রক্ষণাবেক্ষন - স্কেভেটর', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(54, 'PRT54', 17, 'CCTV Camera Maintenance', 'সি সি ক্যামেরা রক্ষণাবেক্ষন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(55, 'PRT55', 17, 'Mustafiz - Bicycle Repair', 'মোস্তাফিজ - সাইকেল মেরামত', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(56, 'PRT56', 18, 'Side Wall Repair / Construction', 'পার্শ্ব দেয়াল মেরামত/নির্মাণ', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(57, 'PRT57', 18, 'Side Wall Repair: Drum Sheet / Nails / Wood & Other Purchase', 'পার্শ্ব দেয়াল মেরামত: ড্রাম সীট/পেরেক/গাছ ও অন্যান্য ক্রয়', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(58, 'PRT58', 19, 'Trade License Renewal', 'ট্রেড লাইসেন্স-নবায়ন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(59, 'PRT59', 19, 'Environment License Renewal', 'পরিবেশ লাইসেন্স নবায়ন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(60, 'PRT60', 19, 'Fire License Renewal', 'ফায়ার লাইসেন্স-নবায়ন', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(61, 'PRT61', 20, 'Pen / Pencil / Pin / Broom / Photocopy / Paper etc.', 'কলম/পেন্সিল/পিন/ঝাড়ু/ফটোকপি/কাগজ ইত্যাদি', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(62, 'PRT62', 21, 'Seedlings / Vegetable Farming / Fertilizer / Herbicide / Pesticide', 'গাছের চারা/ সবজি চাষ/ সার/ঘাসের ঔষধ ক্রয়/কীটনাশক', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(63, 'PRT63', 22, 'Commission Payment to Customer', 'গ্রাহককে কমিশন প্রদান', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(64, 'PRT64', 23, 'Signboard', 'সাইনবোর্ড', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0),
(2065, 'PRT65', 11, 'Asif - Salary Test', 'Asif - Salary Test', 100, '2026-07-25 11:51:04', NULL, '2026-07-25 11:52:45', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_fueltype`
--

CREATE TABLE `mst_fueltype` (
  `FuelTypeID` int(11) NOT NULL,
  `FuelName` varchar(100) NOT NULL,
  `FuelCode` varchar(20) DEFAULT NULL,
  `UnitOfMeasure` varchar(20) NOT NULL DEFAULT 'Litre',
  `SellingRate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `PurchaseRate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `CommissionRate` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `TaxPercent` decimal(6,3) NOT NULL DEFAULT 0.000,
  `Density` decimal(6,4) DEFAULT NULL,
  `ColorCode` varchar(20) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `mst_fueltype`
--

INSERT INTO `mst_fueltype` (`FuelTypeID`, `FuelName`, `FuelCode`, `UnitOfMeasure`, `SellingRate`, `PurchaseRate`, `CommissionRate`, `TaxPercent`, `Density`, `ColorCode`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'LPG', 'LPG', '0', 86.93, 78.93, 8.0000, 0.000, NULL, '', '', NULL, '2026-07-11 20:16:00', 100, '2026-07-24 21:56:06', 1, 0),
(102, 'Test Fuel Type X', 'TFX', '0', 12.50, 10.50, 0.5000, 0.000, NULL, NULL, 'Test entry', 1, '2026-07-14 22:10:35', NULL, '2026-07-14 22:11:03', 1, 1),
(103, 'Octane', 'OCT', 'Litre', 145.00, 130.00, 15.0000, 0.000, NULL, '', '', 100, '2026-07-25 13:23:11', NULL, '2026-07-25 13:23:11', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_licensetype`
--

CREATE TABLE `mst_licensetype` (
  `LicenseTypeID` int(11) NOT NULL,
  `LicenseCode` varchar(30) NOT NULL,
  `LicenseName` varchar(150) NOT NULL,
  `LicenseNameBN` varchar(150) DEFAULT NULL,
  `GovernmentAuthority` varchar(200) DEFAULT NULL,
  `Description` varchar(500) DEFAULT NULL,
  `IsExpiryRequired` bit(1) DEFAULT b'1',
  `DefaultValidityMonths` int(11) DEFAULT 12,
  `DefaultNotifyBefore` int(11) DEFAULT 30,
  `IsAllowNotification` bit(1) DEFAULT b'1',
  `Icon` varchar(100) DEFAULT NULL,
  `SortOrder` int(11) DEFAULT 1,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT NULL,
  `IsActive` bit(1) DEFAULT b'1',
  `IsDeleted` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mst_licensetype`
--

INSERT INTO `mst_licensetype` (`LicenseTypeID`, `LicenseCode`, `LicenseName`, `LicenseNameBN`, `GovernmentAuthority`, `Description`, `IsExpiryRequired`, `DefaultValidityMonths`, `DefaultNotifyBefore`, `IsAllowNotification`, `Icon`, `SortOrder`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'TRADE', 'Trade License', 'ট্রেড লাইসেন্স', 'City Corporation', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(2, 'BIN', 'BIN Registration', 'বিআইএন নিবন্ধন', 'National Board of Revenue', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(3, 'TIN', 'TIN Certificate', 'টিআইএন', 'National Board of Revenue', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(4, 'FIRE', 'Fire License', 'ফায়ার লাইসেন্স', 'Fire Service & Civil Defence', NULL, b'1', 12, 60, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(5, 'DOE', 'Environment Clearance', 'পরিবেশ অধিদপ্তর', 'Department of Environment', NULL, b'1', 12, 60, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(6, 'UNO', 'NOC', 'উপজেলা নির্বাহী কর্মকর্তার অনাপত্তি', 'UNO Office', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(7, 'EXPLOSIVE', 'Explosive License', 'বিস্ফোরক লাইসেন্স', 'Department of Explosives', NULL, b'1', 12, 90, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
(8, 'BSTI', 'BSTI Certificate', 'বিএসটিআই', 'BSTI', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0');

-- --------------------------------------------------------

--
-- Table structure for table `mst_nozzle`
--

CREATE TABLE `mst_nozzle` (
  `NozzleID` int(11) NOT NULL,
  `DisID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Pump.PumpID',
  `NozzleNo` int(11) NOT NULL,
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `TankGroupID` int(11) DEFAULT NULL COMMENT 'Logical FK -> mst_TankGroup.TankGroupID',
  `OpeningGeneral` decimal(14,3) NOT NULL DEFAULT 0.000,
  `NozzleName` varchar(50) NOT NULL DEFAULT '',
  `OpeningMaster` decimal(14,3) NOT NULL DEFAULT 0.000,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual fuel-dispensing nozzles (dual-meter: general + master)';

--
-- Dumping data for table `mst_nozzle`
--

INSERT INTO `mst_nozzle` (`NozzleID`, `DisID`, `NozzleNo`, `FuelTypeID`, `TankGroupID`, `OpeningGeneral`, `NozzleName`, `OpeningMaster`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 101, 1, 101, 101, 279699.980, 'N1', 505567.000, NULL, '2026-07-12 20:09:52', NULL, '2026-07-14 20:06:39', 1, 0),
(102, 101, 2, 101, 101, 337055.640, 'N2', 561640.000, NULL, '2026-07-12 21:23:22', NULL, '2026-07-14 20:06:44', 1, 0),
(113, 102, 3, 103, 102, 5000.000, 'N3', 6000.000, 100, '2026-07-25 13:25:16', 100, '2026-07-25 15:54:24', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_otherscollectionparticular`
--

CREATE TABLE `mst_otherscollectionparticular` (
  `OthersCollectionParticularID` int(11) NOT NULL,
  `NameEN` varchar(150) DEFAULT NULL,
  `NameBN` varchar(150) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DEPRECATED: legacy particulars, retained for historical data only';

-- --------------------------------------------------------

--
-- Table structure for table `mst_shareholder`
--

CREATE TABLE `mst_shareholder` (
  `Id` int(11) NOT NULL,
  `ShareHolderID` varchar(50) DEFAULT NULL,
  `NameEN` varchar(150) NOT NULL,
  `NameBN` varchar(150) DEFAULT NULL,
  `Mobile` varchar(30) NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `InvestmentAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ImagePath` varchar(255) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `mst_shareholder`
--

INSERT INTO `mst_shareholder` (`Id`, `ShareHolderID`, `NameEN`, `NameBN`, `Mobile`, `Address`, `InvestmentAmount`, `ImagePath`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'SH101', 'Main Uddin', 'মঈন উদ্দিন', '01819800600', 'Ukil Bari, Jonar Kewsia, Satkania, Chattogram.', 0.00, NULL, NULL, NULL, '2026-07-12 21:27:12', NULL, '2026-07-14 17:43:31', 1, 0),
(102, 'SH102', 'Mohammad Ali', 'মোহাম্মদ আলী', '0', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:06:37', NULL, '2026-07-14 17:43:31', 1, 0),
(103, 'SH103', 'Abul Kashem', 'আবুল কাশেম', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:08', NULL, '2026-07-14 17:43:31', 1, 0),
(104, 'SH104', 'Jahir Uddin', 'জহির উদ্দিন', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:27', NULL, '2026-07-14 17:43:31', 1, 0),
(105, 'SH105', 'Dr. Abul Hashem', 'ডা. আবুল হাশেম', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:47', NULL, '2026-07-14 17:43:31', 1, 0),
(106, 'SH106', 'Mohiuddin Md. Kachir', 'মহিউদ্দিন মো. কচির', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:08:17', NULL, '2026-07-14 17:43:31', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_shift`
--

CREATE TABLE `mst_shift` (
  `ShiftID` int(11) NOT NULL,
  `ShiftName` varchar(100) NOT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Operating shifts (Morning/Evening/Night)';

--
-- Dumping data for table `mst_shift`
--

INSERT INTO `mst_shift` (`ShiftID`, `ShiftName`, `StartTime`, `EndTime`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'Morning Shift', '06:00:00', '14:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0),
(2, 'Evening Shift', '14:00:00', '22:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0),
(3, 'Night Shift', '22:00:00', '06:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0),
(4, 'Test Shit', '09:14:00', '13:19:00', 100, '2026-07-24 20:14:42', 100, '2026-07-24 20:14:51', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `mst_supplier`
--

CREATE TABLE `mst_supplier` (
  `SupplierID` int(11) NOT NULL,
  `SupplierName` varchar(150) NOT NULL,
  `ContactPerson` varchar(150) DEFAULT NULL,
  `Mobile` varchar(30) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `OpeningBalance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fuel vendors / suppliers';

--
-- Dumping data for table `mst_supplier`
--

INSERT INTO `mst_supplier` (`SupplierID`, `SupplierName`, `ContactPerson`, `Mobile`, `Email`, `Address`, `OpeningBalance`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'AyGayz LPG Ltd.', '...', '0', NULL, NULL, 0.00, NULL, '2026-07-12 23:41:29', NULL, '2026-07-12 23:41:29', 1, 0),
(2, 'Zunaid Mahmud', '01672008293', '01672008293', 'md.shraban@gmail.com', 'asdfasdf', 100.00, 100, '2026-07-24 19:55:10', NULL, '2026-07-24 19:55:10', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_tank`
--

CREATE TABLE `mst_tank` (
  `TankID` int(11) NOT NULL,
  `TankName` varchar(100) NOT NULL,
  `TankCode` varchar(30) DEFAULT NULL,
  `TankGroupID` int(11) DEFAULT NULL COMMENT 'Logical FK -> mst_TankGroup.TankGroupID',
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `Capacity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `MinLevel` decimal(14,3) NOT NULL DEFAULT 0.000,
  `OpeningStockPercent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `Priority` int(11) NOT NULL DEFAULT 100,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `mst_tank`
--

INSERT INTO `mst_tank` (`TankID`, `TankName`, `TankCode`, `TankGroupID`, `FuelTypeID`, `Capacity`, `MinLevel`, `OpeningStockPercent`, `Priority`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'LPG-Tank-1', 'LPG-1', 101, 101, 20000.000, 2000.000, 23.00, 1, NULL, '2026-07-12 23:50:45', 100, '2026-07-24 20:18:55', 1, 0),
(102, 'Octane', 'OCT', 101, 101, 500.000, 50.000, 0.00, 100, 100, '2026-07-24 20:17:48', NULL, '2026-07-24 20:17:48', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_tankgroup`
--

CREATE TABLE `mst_tankgroup` (
  `TankGroupID` int(11) NOT NULL,
  `TankGroupName` varchar(100) NOT NULL,
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Group of physically-connected tanks sharing stock';

--
-- Dumping data for table `mst_tankgroup`
--

INSERT INTO `mst_tankgroup` (`TankGroupID`, `TankGroupName`, `FuelTypeID`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(101, 'LPG', 101, NULL, '2026-07-12 20:05:52', NULL, '2026-07-12 20:05:52', 1, 0),
(102, 'Octane', 103, 100, '2026-07-25 14:20:16', NULL, '2026-07-25 14:20:16', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mst_unitofmeasure`
--

CREATE TABLE `mst_unitofmeasure` (
  `Id` int(11) NOT NULL,
  `UnitId` varchar(20) DEFAULT NULL,
  `UnitNameEN` varchar(50) DEFAULT NULL,
  `UnitNameBN` varchar(50) DEFAULT NULL,
  `Remark` varchar(250) DEFAULT NULL,
  `CreateAt` datetime DEFAULT NULL,
  `CreatedBy` varchar(50) DEFAULT NULL,
  `UpdateAt` datetime DEFAULT NULL,
  `UpdatedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mst_unitofmeasure`
--

INSERT INTO `mst_unitofmeasure` (`Id`, `UnitId`, `UnitNameEN`, `UnitNameBN`, `Remark`, `CreateAt`, `CreatedBy`, `UpdateAt`, `UpdatedBy`) VALUES
(1, 'U101', 'Liter', 'লিটার', 'লিটার/Liter', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sys_role`
--

CREATE TABLE `sys_role` (
  `RoleID` int(11) NOT NULL,
  `RoleCode` varchar(30) NOT NULL COMMENT 'e.g. SUPER_ADMIN, OWNER, MANAGER, OPERATOR, ACCOUNTANT',
  `RoleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application role catalog (RBAC)';

--
-- Dumping data for table `sys_role`
--

INSERT INTO `sys_role` (`RoleID`, `RoleCode`, `RoleName`, `Description`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 'SUPER_ADMIN', 'Super Administrator', 'Full system access with all permissions.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(2, 'ADMIN', 'Administrator', 'Manage company settings, users, roles, and system configuration.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(3, 'MANAGER', 'Station Manager', 'Manage station operations, sales, purchases, reports, and employees.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(4, 'ACCOUNTANT', 'Accountant', 'Manage accounts, vouchers, expenses, collections, and financial reports.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(5, 'SHIFT_MANAGER', 'Shift Manager', 'Manage shift opening, closing, and daily operations.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(6, 'SALES_OPERATOR', 'Sales Operator', 'Process fuel sales and issue invoices.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(7, 'CASHIER', 'Cashier', 'Receive customer payments and manage cash transactions.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(8, 'PURCHASE_OFFICER', 'Purchase Officer', 'Manage fuel purchases, suppliers, and stock receiving.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(9, 'STORE_KEEPER', 'Store Keeper', 'Manage lubricant inventory and store stock.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(10, 'AUDITOR', 'Auditor', 'View all reports and audit logs without modification rights.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(11, 'REPORT_VIEWER', 'Report Viewer', 'View reports only.', 1, '2026-07-12 23:58:12', NULL, '0000-00-00 00:00:00', 1, 0),
(12, 'developer', 'Developer', 'He Can Manage Everything.', NULL, '2026-07-13 00:03:08', NULL, '2026-07-13 00:03:08', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sys_user`
--

CREATE TABLE `sys_user` (
  `UserID` int(11) NOT NULL,
  `FullName` varchar(150) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Mobile` varchar(30) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `AvatarPath` varchar(255) DEFAULT NULL,
  `LastLoginAt` datetime DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login users';

--
-- Dumping data for table `sys_user`
--

INSERT INTO `sys_user` (`UserID`, `FullName`, `Email`, `Mobile`, `PasswordHash`, `AvatarPath`, `LastLoginAt`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(100, 'Mohammad Zunaid Hossain', 'zunaidctg11@gmail.com', '01812793369', '123', NULL, '2026-07-25 22:10:57', NULL, '2026-07-13 00:06:08', NULL, '2026-07-25 22:10:57', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sys_userrole`
--

CREATE TABLE `sys_userrole` (
  `UserRoleID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL COMMENT 'Logical FK -> sys_User.UserID',
  `RoleID` int(11) NOT NULL COMMENT 'Logical FK -> sys_Role.RoleID',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Junction table: user to role assignment';

--
-- Dumping data for table `sys_userrole`
--

INSERT INTO `sys_userrole` (`UserRoleID`, `UserID`, `RoleID`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, 100, 12, NULL, '2026-07-13 00:09:26', NULL, '2026-07-13 00:09:26', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `trn_companylicense`
--

CREATE TABLE `trn_companylicense` (
  `CompanyLicenseID` int(11) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `LicenseTypeID` int(11) NOT NULL,
  `LicenseNo` varchar(100) NOT NULL,
  `IssueDate` date DEFAULT NULL,
  `EffectiveDate` date DEFAULT NULL,
  `ExpiryDate` date DEFAULT NULL,
  `IssuedBy` varchar(200) DEFAULT NULL,
  `IssuingOffice` varchar(250) DEFAULT NULL,
  `RenewalDate` date DEFAULT NULL,
  `RenewalReferenceNo` varchar(100) DEFAULT NULL,
  `Status` enum('Pending','Valid','Expired','Cancelled','Suspended') DEFAULT 'Valid',
  `IsAllowNotification` bit(1) DEFAULT b'1',
  `NotifyBefore` int(11) DEFAULT 30,
  `LastNotificationDate` datetime DEFAULT NULL,
  `NotificationCount` int(11) DEFAULT 0,
  `FileName` varchar(255) DEFAULT NULL,
  `OriginalFileName` varchar(255) DEFAULT NULL,
  `FileExtension` varchar(20) DEFAULT NULL,
  `FileSize` bigint(20) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `MimeType` varchar(100) DEFAULT NULL,
  `FileHash` varchar(100) DEFAULT NULL,
  `IsVerified` bit(1) DEFAULT b'0',
  `VerifiedBy` int(11) DEFAULT NULL,
  `VerifiedAt` datetime DEFAULT NULL,
  `Remarks` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT NULL,
  `IsActive` bit(1) DEFAULT b'1',
  `IsDeleted` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trn_companylicensehistory`
--

CREATE TABLE `trn_companylicensehistory` (
  `HistoryID` int(11) NOT NULL,
  `CompanyLicenseID` int(11) NOT NULL,
  `OldLicenseNo` varchar(100) DEFAULT NULL,
  `NewLicenseNo` varchar(100) DEFAULT NULL,
  `PreviousExpiryDate` date DEFAULT NULL,
  `NewExpiryDate` date DEFAULT NULL,
  `RenewalDate` date DEFAULT NULL,
  `RenewalFee` decimal(18,2) DEFAULT NULL,
  `PaymentReference` varchar(100) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `Remarks` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trx_cashcollection`
--

CREATE TABLE `trx_cashcollection` (
  `CashCollectionID` int(11) NOT NULL,
  `CollectionDate` date NOT NULL,
  `CollectionTime` time NOT NULL,
  `Amount` decimal(14,2) NOT NULL,
  `CollectedByType` enum('Employee','Shareholder') NOT NULL,
  `CollectedPersonID` int(11) NOT NULL COMMENT 'Polymorphic: mst_Employee.EmployeeID or mst_Shareholder.ShareholderID',
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_customercollection`
--

CREATE TABLE `trx_customercollection` (
  `CustomerCollectionID` int(11) NOT NULL,
  `TxnDate` date NOT NULL,
  `CollectionTime` time DEFAULT NULL,
  `CustomerID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Customer.CustomerID',
  `CustomerDueID` int(11) DEFAULT NULL COMMENT 'Logical FK -> trx_CustomerDue.CustomerDueID (nullable)',
  `Amount` decimal(14,2) NOT NULL,
  `PaymentMethodID` int(11) NOT NULL COMMENT 'Logical FK -> cfg_PaymentMethod.PaymentMethodID',
  `ReferenceNo` varchar(150) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `trx_customercollection`
--

INSERT INTO `trx_customercollection` (`CustomerCollectionID`, `TxnDate`, `CollectionTime`, `CustomerID`, `CustomerDueID`, `Amount`, `PaymentMethodID`, `ReferenceNo`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
(1, '2026-07-24', NULL, 101, NULL, 50.00, 101, 'asd', 'asdfasd', 100, '2026-07-24 20:01:26', NULL, '2026-07-24 20:01:26', 1, 0),
(2, '2026-07-24', NULL, 101, NULL, 1000.00, 101, 'obn n', '', 100, '2026-07-24 20:31:06', NULL, '2026-07-24 20:31:06', 1, 0),
(3, '2026-07-25', NULL, 102, NULL, 100.00, 101, '', '', 100, '2026-07-25 18:45:01', NULL, '2026-07-25 18:45:01', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `trx_customerdue`
--

CREATE TABLE `trx_customerdue` (
  `CustomerDueID` int(11) NOT NULL,
  `TxnDate` date NOT NULL,
  `CustomerID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Customer.CustomerID',
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `VehicleNumber` varchar(50) DEFAULT NULL,
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Rate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `PaidAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `DueAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_customerdue_Previous`
--

CREATE TABLE `trx_customerdue_Previous` (
  `CustomerDuePreviousID` int(11) NOT NULL AUTO_INCREMENT,
  `TxnDate` date NOT NULL,
  `CustomerID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Customer.CustomerID',
  `TotalAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `PaidAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `DueAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CustomerDuePreviousID`),
  KEY `idx_prev_date` (`TxnDate`),
  KEY `idx_prev_customer` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historical customer due records prior to software launch cutoff';

-- --------------------------------------------------------

--
-- Table structure for table `trx_Customer_Due_Opening`
--

CREATE TABLE `trx_Customer_Due_Opening` (
  `OpeningDueID` int(11) NOT NULL AUTO_INCREMENT,
  `TxnDate` date NOT NULL,
  `CustomerID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Customer / mst_Employee / mst_Shareholder',
  `CustomerType` varchar(30) NOT NULL DEFAULT 'Customer',
  `SalesAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Payment` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`OpeningDueID`),
  KEY `idx_open_date` (`TxnDate`),
  KEY `idx_open_customer` (`CustomerID`, `CustomerType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historical customer due and payment records prior to software launch';

-- --------------------------------------------------------

--
-- Table structure for table `trx_expense`
--

CREATE TABLE `trx_expense` (
  `ExpenseID` int(11) NOT NULL,
  `ExpenseDate` date NOT NULL,
  `ParticularID` varchar(40) DEFAULT NULL,
  `Amount` decimal(14,2) DEFAULT NULL,
  `PaymentMethodID` int(11) DEFAULT NULL COMMENT 'Logical FK -> cfg_PaymentMethod.PaymentMethodID',
  `ReferenceNo` varchar(150) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trx_fuelpriceadjustment`
--

CREATE TABLE `trx_fuelpriceadjustment` (
  `FuelPriceAdjustmentID` int(11) NOT NULL,
  `EffectiveDate` date NOT NULL,
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `OldSellingPrice` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `NewSellingPrice` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `StockQuantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `PriceDifference` decimal(14,4) GENERATED ALWAYS AS (`NewSellingPrice` - `OldSellingPrice`) STORED,
  `AdjustmentAmount` decimal(14,2) GENERATED ALWAYS AS (round(`StockQuantity` * (`NewSellingPrice` - `OldSellingPrice`),2)) STORED,
  `AdjustmentType` varchar(10) GENERATED ALWAYS AS (case when `NewSellingPrice` > `OldSellingPrice` then 'Profit' when `NewSellingPrice` < `OldSellingPrice` then 'Loss' else 'None' end) STORED,
  `ReferenceNo` varchar(150) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock revaluation gain/loss on government price changes';

-- --------------------------------------------------------

--
-- Table structure for table `trx_stockadjustment`
--

CREATE TABLE `trx_stockadjustment` (
  `StockAdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
  `AdjustmentDate` date NOT NULL,
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `AdjustmentType` enum('Stock IN','Stock OUT') NOT NULL,
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Reason` varchar(100) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`StockAdjustmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Manual Stock IN and Stock OUT adjustments';

-- --------------------------------------------------------

--
-- Table structure for table `trx_fuelpurchase`
--

CREATE TABLE `trx_fuelpurchase` (
  `FuelPurchaseID` int(11) NOT NULL,
  `InvoiceNo` varchar(100) DEFAULT NULL,
  `PurchaseDate` date NOT NULL,
  `SupplierID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Supplier.SupplierID',
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Rate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `DiscountType` varchar(20) NOT NULL DEFAULT 'Fixed',
  `DiscountValue` decimal(14,2) NOT NULL DEFAULT 0.00,
  `DiscountAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `PaymentStatus` varchar(20) NOT NULL DEFAULT 'Due',
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_purchase_details`
--

CREATE TABLE `trx_purchase_details` (
  `PurchaseDetailID` int(11) NOT NULL AUTO_INCREMENT,
  `FuelPurchaseID` int(11) NOT NULL,
  `FuelTypeID` int(11) NOT NULL,
  `TankID` int(11) NOT NULL,
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Rate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`PurchaseDetailID`),
  KEY `idx_fuel_purchase` (`FuelPurchaseID`),
  KEY `idx_fuel_type` (`FuelTypeID`),
  KEY `idx_tank` (`TankID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trx_stock_in`
--

CREATE TABLE `trx_stock_in` (
  `StockInID` int(11) NOT NULL AUTO_INCREMENT,
  `StockInDate` date NOT NULL,
  `ReferenceType` varchar(50) NOT NULL DEFAULT 'FuelPurchase',
  `ReferenceID` int(11) NOT NULL,
  `ReferenceDetailID` int(11) DEFAULT NULL,
  `FuelTypeID` int(11) NOT NULL,
  `TankID` int(11) NOT NULL,
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `UnitRate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TotalValue` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`StockInID`),
  KEY `idx_ref` (`ReferenceType`, `ReferenceID`),
  KEY `idx_fuel_tank` (`FuelTypeID`, `TankID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trx_stockadjustment`
--

CREATE TABLE `trx_stockadjustment` (
  `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
  `AdjustmentDate` date NOT NULL,
  `AdjustmentType` enum('ADD','DEDUCT') NOT NULL DEFAULT 'ADD',
  `FuelTypeID` int(11) NOT NULL,
  `TankID` int(11) NOT NULL,
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `UnitRate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TotalValue` decimal(14,2) NOT NULL DEFAULT 0.00,
  `AdjustmentReason` varchar(100) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`AdjustmentID`),
  KEY `idx_adj_date` (`AdjustmentDate`),
  KEY `idx_fuel_tank` (`FuelTypeID`, `TankID`),
  KEY `idx_adj_type` (`AdjustmentType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trx_nozzlereading`
--

CREATE TABLE `trx_nozzlereading` (
  `NozzleReadingID` int(11) NOT NULL,
  `ReadingDate` date NOT NULL,
  `ShiftID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Shift.ShiftID',
  `StationID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Station.StationID',
  `DisID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Pump.PumpID',
  `NozzleID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Nozzle.NozzleID',
  `GeneralReading` decimal(14,3) NOT NULL,
  `MasterReading` decimal(14,3) NOT NULL,
  `PreviousGeneral` decimal(14,3) NOT NULL DEFAULT 0.000,
  `PreviousMaster` decimal(14,3) NOT NULL DEFAULT 0.000,
  `DiffGeneral` decimal(14,3) GENERATED ALWAYS AS (`GeneralReading` - `PreviousGeneral`) STORED,
  `DiffMaster` decimal(14,3) GENERATED ALWAYS AS (`MasterReading` - `PreviousMaster`) STORED,
  `Notes` varchar(255) DEFAULT NULL,
  `RecordedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `SaleQuantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `SellingRate` decimal(18,3) NOT NULL DEFAULT 0.000,
  `PurchaseRate` decimal(18,3) NOT NULL DEFAULT 0.000,
  `SalesAmt` decimal(18,2) NOT NULL DEFAULT 0.00,
  `CommissionRate` decimal(18,3) NOT NULL DEFAULT 0.000,
  `CommissionAmt` decimal(18,2) NOT NULL DEFAULT 0.00
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_nozzletest`
--

CREATE TABLE `trx_nozzletest` (
  `NozzleTestID` int(11) NOT NULL,
  `TestDate` date NOT NULL,
  `MeterID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Meter.MeterID',
  `Quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_otherscollection`
--

CREATE TABLE `trx_otherscollection` (
  `OthersCollectionID` int(11) NOT NULL,
  `CollectionDate` date NOT NULL,
  `ParticularID` int(11) NOT NULL COMMENT 'Logical FK -> mst_ExpenseParticular.ExpenseParticularID',
  `PartyName` varchar(150) DEFAULT NULL,
  `Amount` decimal(14,2) NOT NULL,
  `PaymentMethodID` int(11) NOT NULL COMMENT 'Logical FK -> cfg_PaymentMethod.PaymentMethodID',
  `ReferenceNo` varchar(150) DEFAULT NULL,
  `Narration` varchar(255) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_supplierpayment`
--

CREATE TABLE `trx_supplierpayment` (
  `SupplierPaymentID` int(11) NOT NULL,
  `PaymentDate` date NOT NULL,
  `SupplierID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Supplier.SupplierID',
  `Amount` decimal(14,2) NOT NULL,
  `PaymentMethodID` int(11) NOT NULL COMMENT 'Logical FK -> cfg_PaymentMethod.PaymentMethodID',
  `ReferenceNo` varchar(150) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_tankdip`
--

CREATE TABLE `trx_tankdip` (
  `TankDipID` int(11) NOT NULL,
  `DipDate` date NOT NULL,
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `PhysicalStock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `trx_tankreading`
--

CREATE TABLE `trx_tankreading` (
  `TankReadingID` int(11) NOT NULL,
  `ReadingDate` date NOT NULL,
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `PreviousReadingPercent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `CurrentReadingPercent` decimal(6,2) NOT NULL,
  `TodaySoldPercent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_customerlistall`
-- (See below for the actual view)
--
CREATE TABLE `vw_customerlistall` (
`CustomerId` varchar(50)
,`CustomerNameEN` varchar(150)
,`CustomerNameBN` varchar(150)
,`Mobile` varchar(30)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_expparticular`
-- (See below for the actual view)
--
CREATE TABLE `vw_expparticular` (
`ParticularID` varchar(50)
,`ParticularNameEN` varchar(150)
,`ParticularNameBN` varchar(150)
);

-- --------------------------------------------------------

--
-- Structure for view `licenseexpiringsoon`
--
DROP TABLE IF EXISTS `licenseexpiringsoon`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `licenseexpiringsoon`  AS SELECT `cl`.`CompanyLicenseID` AS `CompanyLicenseID`, `lt`.`LicenseName` AS `LicenseName`, `cl`.`LicenseNo` AS `LicenseNo`, `cl`.`ExpiryDate` AS `ExpiryDate`, to_days(`cl`.`ExpiryDate`) - to_days(curdate()) AS `RemainingDays` FROM (`trn_companylicense` `cl` join `mst_licensetype` `lt` on(`lt`.`LicenseTypeID` = `cl`.`LicenseTypeID`)) WHERE `cl`.`IsDeleted` = 0 AND `cl`.`IsActive` = 1 AND `cl`.`IsAllowNotification` = 1 AND to_days(`cl`.`ExpiryDate`) - to_days(curdate()) <= `cl`.`NotifyBefore` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_customerlistall`
--
DROP TABLE IF EXISTS `vw_customerlistall`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_customerlistall`  AS SELECT `mst_customer`.`CustomerID` AS `CustomerId`, `mst_customer`.`CustomerName` AS `CustomerNameEN`, `mst_customer`.`CustomerName` AS `CustomerNameBN`, `mst_customer`.`Mobile` AS `Mobile` FROM `mst_customer`union all select `mst_employee`.`EmployeeId` AS `EmployeeId`,`mst_employee`.`NameEN` AS `NameEN`,`mst_employee`.`NameBN` AS `NameBN`,`mst_employee`.`Mobile` AS `Mobile` from `mst_employee` union all select `mst_shareholder`.`ShareHolderID` AS `shareHolderID`,`mst_shareholder`.`NameEN` AS `NameEN`,`mst_shareholder`.`NameBN` AS `NameBN`,`mst_shareholder`.`Mobile` AS `Mobile` from `mst_shareholder`  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_expparticular`
--
DROP TABLE IF EXISTS `vw_expparticular`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_expparticular`  AS SELECT `mst_expenseparticular`.`ParticularID` AS `ParticularID`, `mst_expenseparticular`.`ParticularNameEN` AS `ParticularNameEN`, `mst_expenseparticular`.`ParticularNameBN` AS `ParticularNameBN` FROM `mst_expenseparticular`union select `mst_employee`.`EmployeeId` AS `EmployeeId`,`mst_employee`.`NameEN` AS `NameEN`,`mst_employee`.`NameBN` AS `NameBN` from `mst_employee` union select `mst_shareholder`.`ShareHolderID` AS `ShareHOlderID`,`mst_shareholder`.`NameEN` AS `NameEN`,`mst_shareholder`.`NameBN` AS `NameBN` from `mst_shareholder`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cfg_companyprofile`
--
ALTER TABLE `cfg_companyprofile`
  ADD PRIMARY KEY (`CompanyID`),
  ADD UNIQUE KEY `CompanyCode` (`CompanyCode`);

--
-- Indexes for table `cfg_paymentmethod`
--
ALTER TABLE `cfg_paymentmethod`
  ADD PRIMARY KEY (`PaymentMethodID`),
  ADD UNIQUE KEY `uq_cfg_PaymentMethod_Code` (`MethodCode`);

--
-- Indexes for table `cfg_systemsetting`
--
ALTER TABLE `cfg_systemsetting`
  ADD PRIMARY KEY (`SystemSettingID`),
  ADD UNIQUE KEY `uq_cfg_SystemSetting_Key` (`SettingKey`);

--
-- Indexes for table `log_auditlog`
--
ALTER TABLE `log_auditlog`
  ADD PRIMARY KEY (`AuditLogID`),
  ADD KEY `idx_log_AuditLog_Table_Record` (`TableName`,`RecordID`),
  ADD KEY `idx_log_AuditLog_ChangedAt` (`ChangedAt`);

--
-- Indexes for table `mst_customer`
--
ALTER TABLE `mst_customer`
  ADD PRIMARY KEY (`CustomerID`),
  ADD KEY `idx_mst_Customer_Mobile` (`Mobile`),
  ADD KEY `idx_mst_Customer_Vehicle` (`VehicleNumber`);

--
-- Indexes for table `mst_dispenser`
--
ALTER TABLE `mst_dispenser`
  ADD PRIMARY KEY (`DisID`) USING BTREE;

--
-- Indexes for table `mst_employee`
--
ALTER TABLE `mst_employee`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `EmployeeId` (`EmployeeId`);

--
-- Indexes for table `mst_expensecategory`
--
ALTER TABLE `mst_expensecategory`
  ADD PRIMARY KEY (`ExpenseCategoryID`);

--
-- Indexes for table `mst_expenseparticular`
--
ALTER TABLE `mst_expenseparticular`
  ADD PRIMARY KEY (`ExpenseParticularID`),
  ADD KEY `idx_mst_ExpenseParticular_Category` (`ExpenseCategoryID`);

--
-- Indexes for table `mst_fueltype`
--
ALTER TABLE `mst_fueltype`
  ADD PRIMARY KEY (`FuelTypeID`),
  ADD UNIQUE KEY `uq_mst_FuelType_Name` (`FuelName`),
  ADD KEY `idx_mst_FuelType_Active` (`IsActive`,`IsDeleted`);

--
-- Indexes for table `mst_licensetype`
--
ALTER TABLE `mst_licensetype`
  ADD PRIMARY KEY (`LicenseTypeID`),
  ADD UNIQUE KEY `LicenseCode` (`LicenseCode`);

--
-- Indexes for table `mst_nozzle`
--
ALTER TABLE `mst_nozzle`
  ADD PRIMARY KEY (`NozzleID`),
  ADD UNIQUE KEY `uq_mst_Nozzle` (`DisID`,`NozzleNo`) USING BTREE,
  ADD KEY `idx_mst_Nozzle_FuelType` (`FuelTypeID`),
  ADD KEY `idx_mst_Nozzle_Pump` (`DisID`) USING BTREE;

--
-- Indexes for table `mst_otherscollectionparticular`
--
ALTER TABLE `mst_otherscollectionparticular`
  ADD PRIMARY KEY (`OthersCollectionParticularID`);

--
-- Indexes for table `mst_shareholder`
--
ALTER TABLE `mst_shareholder`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `ShareHolderID` (`ShareHolderID`);

--
-- Indexes for table `mst_shift`
--
ALTER TABLE `mst_shift`
  ADD PRIMARY KEY (`ShiftID`),
  ADD UNIQUE KEY `uq_mst_Shift_Name` (`ShiftName`);

--
-- Indexes for table `mst_supplier`
--
ALTER TABLE `mst_supplier`
  ADD PRIMARY KEY (`SupplierID`),
  ADD KEY `idx_mst_Supplier_Name` (`SupplierName`);

--
-- Indexes for table `mst_tank`
--
ALTER TABLE `mst_tank`
  ADD PRIMARY KEY (`TankID`),
  ADD KEY `idx_mst_Tank_FuelType` (`FuelTypeID`),
  ADD KEY `idx_mst_Tank_TankGroup` (`TankGroupID`);

--
-- Indexes for table `mst_tankgroup`
--
ALTER TABLE `mst_tankgroup`
  ADD PRIMARY KEY (`TankGroupID`),
  ADD UNIQUE KEY `uq_mst_TankGroup` (`TankGroupName`,`FuelTypeID`),
  ADD KEY `idx_mst_TankGroup_FuelType` (`FuelTypeID`);

--
-- Indexes for table `mst_unitofmeasure`
--
ALTER TABLE `mst_unitofmeasure`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `UnitId` (`UnitId`);

--
-- Indexes for table `sys_role`
--
ALTER TABLE `sys_role`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `uq_sys_Role_RoleCode` (`RoleCode`);

--
-- Indexes for table `sys_user`
--
ALTER TABLE `sys_user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `uq_sys_User_Email` (`Email`);

--
-- Indexes for table `sys_userrole`
--
ALTER TABLE `sys_userrole`
  ADD PRIMARY KEY (`UserRoleID`),
  ADD UNIQUE KEY `uq_sys_UserRole` (`UserID`,`RoleID`),
  ADD KEY `idx_sys_UserRole_User` (`UserID`),
  ADD KEY `idx_sys_UserRole_Role` (`RoleID`);

--
-- Indexes for table `trn_companylicense`
--
ALTER TABLE `trn_companylicense`
  ADD PRIMARY KEY (`CompanyLicenseID`);

--
-- Indexes for table `trn_companylicensehistory`
--
ALTER TABLE `trn_companylicensehistory`
  ADD PRIMARY KEY (`HistoryID`);

--
-- Indexes for table `trx_cashcollection`
--
ALTER TABLE `trx_cashcollection`
  ADD PRIMARY KEY (`CashCollectionID`),
  ADD KEY `idx_trx_CashCollection_Date` (`CollectionDate`),
  ADD KEY `idx_trx_CashCollection_Person` (`CollectedByType`,`CollectedPersonID`);

--
-- Indexes for table `trx_customercollection`
--
ALTER TABLE `trx_customercollection`
  ADD PRIMARY KEY (`CustomerCollectionID`),
  ADD KEY `idx_trx_CustomerCollection_Due` (`CustomerDueID`),
  ADD KEY `idx_trx_CustomerCollection_CustomerDate` (`CustomerID`,`TxnDate`);

--
-- Indexes for table `trx_customerdue`
--
ALTER TABLE `trx_customerdue`
  ADD PRIMARY KEY (`CustomerDueID`),
  ADD KEY `idx_trx_CustomerDue_Date` (`TxnDate`),
  ADD KEY `idx_trx_CustomerDue_Customer` (`CustomerID`,`DueAmount`);

--
-- Indexes for table `trx_expense`
--
ALTER TABLE `trx_expense`
  ADD PRIMARY KEY (`ExpenseID`),
  ADD KEY `idx_trx_Expense_Date` (`ExpenseDate`),
  ADD KEY `idx_trx_Expense_Particular` (`ParticularID`) USING BTREE;

--
-- Indexes for table `trx_fuelpriceadjustment`
--
ALTER TABLE `trx_fuelpriceadjustment`
  ADD PRIMARY KEY (`FuelPriceAdjustmentID`),
  ADD UNIQUE KEY `uq_trx_FuelPriceAdjustment` (`FuelTypeID`,`EffectiveDate`),
  ADD KEY `idx_trx_FuelPriceAdjustment_Date` (`EffectiveDate`),
  ADD KEY `idx_trx_FuelPriceAdjustment_FuelType` (`FuelTypeID`);

--
-- Indexes for table `trx_fuelpurchase`
--
ALTER TABLE `trx_fuelpurchase`
  ADD PRIMARY KEY (`FuelPurchaseID`),
  ADD KEY `idx_trx_FuelPurchase_Date` (`PurchaseDate`),
  ADD KEY `idx_trx_FuelPurchase_Supplier` (`SupplierID`),
  ADD KEY `idx_trx_FuelPurchase_FuelType` (`FuelTypeID`),
  ADD KEY `idx_trx_FuelPurchase_Tank` (`TankID`);

--
-- Indexes for table `trx_nozzlereading`
--
ALTER TABLE `trx_nozzlereading`
  ADD PRIMARY KEY (`NozzleReadingID`),
  ADD UNIQUE KEY `uq_trx_NozzleReading` (`NozzleID`,`ReadingDate`,`ShiftID`),
  ADD KEY `idx_trx_NozzleReading_Lookup` (`NozzleID`,`ReadingDate`),
  ADD KEY `idx_trx_NozzleReading_Date` (`ReadingDate`);

--
-- Indexes for table `trx_nozzletest`
--
ALTER TABLE `trx_nozzletest`
  ADD PRIMARY KEY (`NozzleTestID`),
  ADD KEY `idx_trx_NozzleTest_Date` (`TestDate`);

--
-- Indexes for table `trx_otherscollection`
--
ALTER TABLE `trx_otherscollection`
  ADD PRIMARY KEY (`OthersCollectionID`),
  ADD UNIQUE KEY `uq_trx_OthersCollection_Dedup` (`CollectionDate`,`ParticularID`,`Amount`,`ReferenceNo`),
  ADD KEY `idx_trx_OthersCollection_Date` (`CollectionDate`);

--
-- Indexes for table `trx_supplierpayment`
--
ALTER TABLE `trx_supplierpayment`
  ADD PRIMARY KEY (`SupplierPaymentID`),
  ADD KEY `idx_trx_SupplierPayment_Date` (`PaymentDate`),
  ADD KEY `idx_trx_SupplierPayment_Supplier` (`SupplierID`);

--
-- Indexes for table `trx_tankdip`
--
ALTER TABLE `trx_tankdip`
  ADD PRIMARY KEY (`TankDipID`),
  ADD KEY `idx_trx_TankDip_Date` (`DipDate`),
  ADD KEY `idx_trx_TankDip_Tank` (`TankID`);

--
-- Indexes for table `trx_tankreading`
--
ALTER TABLE `trx_tankreading`
  ADD PRIMARY KEY (`TankReadingID`),
  ADD UNIQUE KEY `uq_trx_TankReading` (`TankID`,`ReadingDate`),
  ADD KEY `idx_trx_TankReading_Date` (`ReadingDate`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cfg_companyprofile`
--
ALTER TABLE `cfg_companyprofile`
  MODIFY `CompanyID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cfg_paymentmethod`
--
ALTER TABLE `cfg_paymentmethod`
  MODIFY `PaymentMethodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `cfg_systemsetting`
--
ALTER TABLE `cfg_systemsetting`
  MODIFY `SystemSettingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `log_auditlog`
--
ALTER TABLE `log_auditlog`
  MODIFY `AuditLogID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mst_customer`
--
ALTER TABLE `mst_customer`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `mst_dispenser`
--
ALTER TABLE `mst_dispenser`
  MODIFY `DisID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `mst_expensecategory`
--
ALTER TABLE `mst_expensecategory`
  MODIFY `ExpenseCategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2024;

--
-- AUTO_INCREMENT for table `mst_expenseparticular`
--
ALTER TABLE `mst_expenseparticular`
  MODIFY `ExpenseParticularID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2066;

--
-- AUTO_INCREMENT for table `mst_fueltype`
--
ALTER TABLE `mst_fueltype`
  MODIFY `FuelTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mst_licensetype`
--
ALTER TABLE `mst_licensetype`
  MODIFY `LicenseTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mst_nozzle`
--
ALTER TABLE `mst_nozzle`
  MODIFY `NozzleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `mst_otherscollectionparticular`
--
ALTER TABLE `mst_otherscollectionparticular`
  MODIFY `OthersCollectionParticularID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mst_shift`
--
ALTER TABLE `mst_shift`
  MODIFY `ShiftID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mst_supplier`
--
ALTER TABLE `mst_supplier`
  MODIFY `SupplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mst_tank`
--
ALTER TABLE `mst_tank`
  MODIFY `TankID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mst_tankgroup`
--
ALTER TABLE `mst_tankgroup`
  MODIFY `TankGroupID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `mst_unitofmeasure`
--
ALTER TABLE `mst_unitofmeasure`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sys_role`
--
ALTER TABLE `sys_role`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sys_user`
--
ALTER TABLE `sys_user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `sys_userrole`
--
ALTER TABLE `sys_userrole`
  MODIFY `UserRoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `trn_companylicense`
--
ALTER TABLE `trn_companylicense`
  MODIFY `CompanyLicenseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trn_companylicensehistory`
--
ALTER TABLE `trn_companylicensehistory`
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_cashcollection`
--
ALTER TABLE `trx_cashcollection`
  MODIFY `CashCollectionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_customercollection`
--
ALTER TABLE `trx_customercollection`
  MODIFY `CustomerCollectionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_customerdue`
--
ALTER TABLE `trx_customerdue`
  MODIFY `CustomerDueID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_expense`
--
ALTER TABLE `trx_expense`
  MODIFY `ExpenseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_fuelpriceadjustment`
--
ALTER TABLE `trx_fuelpriceadjustment`
  MODIFY `FuelPriceAdjustmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_fuelpurchase`
--
ALTER TABLE `trx_fuelpurchase`
  MODIFY `FuelPurchaseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_nozzlereading`
--
ALTER TABLE `trx_nozzlereading`
  MODIFY `NozzleReadingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_nozzletest`
--
ALTER TABLE `trx_nozzletest`
  MODIFY `NozzleTestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_otherscollection`
--
ALTER TABLE `trx_otherscollection`
  MODIFY `OthersCollectionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_supplierpayment`
--
ALTER TABLE `trx_supplierpayment`
  MODIFY `SupplierPaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_tankdip`
--
ALTER TABLE `trx_tankdip`
  MODIFY `TankDipID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trx_tankreading`
--
ALTER TABLE `trx_tankreading`
  MODIFY `TankReadingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Table structure for table `mst_bankaccount`
--
CREATE TABLE IF NOT EXISTS `mst_bankaccount` (
  `BankAccountID` int(11) NOT NULL AUTO_INCREMENT,
  `BankName` varchar(150) NOT NULL,
  `BranchName` varchar(150) DEFAULT NULL,
  `AccountName` varchar(150) NOT NULL,
  `AccountNumber` varchar(50) NOT NULL,
  `AccountType` varchar(50) DEFAULT 'Current',
  `RoutingNumber` varchar(50) DEFAULT NULL,
  `OpeningBalance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `OpeningDate` date DEFAULT NULL,
  `CurrentBalance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `IsDefault` tinyint(1) DEFAULT 0,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  `IsDeleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`BankAccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Add BankAccountID column to trx_cashcollection if not exists
--
ALTER TABLE `trx_cashcollection` ADD COLUMN IF NOT EXISTS `BankAccountID` INT(11) NULL DEFAULT NULL AFTER `CollectedPersonID`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
