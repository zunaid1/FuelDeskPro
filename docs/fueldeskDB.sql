-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.20.0.7320
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for fueldesk_pro
CREATE DATABASE IF NOT EXISTS `fueldesk_pro` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `fueldesk_pro`;

-- Dumping structure for table fueldesk_pro.cfg_companyprofile
CREATE TABLE IF NOT EXISTS `cfg_companyprofile` (
  `CompanyID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` bit(1) DEFAULT b'0',
  PRIMARY KEY (`CompanyID`),
  UNIQUE KEY `CompanyCode` (`CompanyCode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fueldesk_pro.cfg_companyprofile: ~1 rows (approximately)
INSERT INTO `cfg_companyprofile` (`CompanyID`, `CompanyCode`, `CompanyName`, `CompanyNameBN`, `ShortName`, `ProprietorName`, `ContactPerson`, `MobileNo`, `PhoneNo`, `Email`, `Website`, `BusinessType`, `Address`, `City`, `District`, `PostalCode`, `Country`, `Logo`, `Favicon`, `CurrencyCode`, `CurrencySymbol`, `TimeZone`, `DateFormat`, `TimeFormat`, `FinancialYearStart`, `FinancialYearEnd`, `DefaultShiftID`, `DefaultLanguage`, `AllowNegativeStock`, `AutoBackup`, `BackupPath`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 'SFS', 'Shangu LPG Filling Station', 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন', 'SFS', 'Main Uddin', 'Rokon Uddin', '01819800600', '0', 'sfs@gmail.com', 'sfs.com', 'Fuel Filling Station', 'Amilaish, Satkania, Chattogram.', 'Chattogram', 'Chattogram', '4396', 'Bangladesh', NULL, NULL, 'BDT', '৳', 'Asia/Dhaka', 'dd-MM-yyyy', '24 Hour', '2026-01-01', '2026-12-31', NULL, 'English', b'1', b'1', NULL, NULL, '2026-07-12 22:25:33', NULL, NULL, b'1', b'0');

-- Dumping structure for table fueldesk_pro.cfg_paymentmethod
CREATE TABLE IF NOT EXISTS `cfg_paymentmethod` (
  `PaymentMethodID` int(11) NOT NULL AUTO_INCREMENT,
  `MethodCode` varchar(30) NOT NULL COMMENT 'CASH, BANK, MOBILE_BANKING, CHEQUE, CARD, OTHER',
  `MethodName` varchar(100) NOT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`PaymentMethodID`),
  UNIQUE KEY `uq_cfg_PaymentMethod_Code` (`MethodCode`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment method master (Cash/Bank/Mobile Banking/Cheque/Card/Other)';

-- Dumping data for table fueldesk_pro.cfg_paymentmethod: ~2 rows (approximately)
INSERT INTO `cfg_paymentmethod` (`PaymentMethodID`, `MethodCode`, `MethodName`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'PM101', 'Cash', NULL, '2026-07-11 19:52:44', NULL, '2026-07-15 00:30:49', 1, 0),
	(102, 'PM102', 'POS', NULL, '2026-07-11 19:52:44', NULL, '2026-07-15 00:30:56', 1, 0);

-- Dumping structure for table fueldesk_pro.cfg_systemsetting
CREATE TABLE IF NOT EXISTS `cfg_systemsetting` (
  `SystemSettingID` int(11) NOT NULL AUTO_INCREMENT,
  `SettingKey` varchar(100) NOT NULL,
  `SettingValue` varchar(500) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`SystemSettingID`),
  UNIQUE KEY `uq_cfg_SystemSetting_Key` (`SettingKey`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Extensible key-value system settings (future-proofing)';

-- Dumping data for table fueldesk_pro.cfg_systemsetting: ~4 rows (approximately)
INSERT INTO `cfg_systemsetting` (`SystemSettingID`, `SettingKey`, `SettingValue`, `Description`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 'IsMeterReadingReadOnly', 'Yes', 'এই সেটিংটা যদি Yes থাকে, তাহলে প্রতিদিন হিসাব করার সময় পূর্বের মিটার রিডিং ReadOnly দেখাবে। অর্থাৎ রিডিং পরিবর্তন করতে পারবে না। \r\n\r\nযদি No থাকে, তাহলে পরিবর্তন করতে পারবে।', NULL, '2026-07-11 19:59:14', NULL, '2026-07-11 19:59:14', 1, 0),
	(2, 'InvoicePrefix', 'INV', 'Invoice Prefix', NULL, '2026-07-12 22:19:58', NULL, '2026-07-12 22:19:58', 1, 0),
	(3, 'MoneyReceiptPrefix', 'MR', 'Money Receipt Prefix', NULL, '2026-07-12 22:20:55', NULL, '2026-07-12 22:20:55', 1, 0),
	(4, 'ExpenseVoucherPrefix', 'EV', 'Expense Voucher Prefix No', NULL, '2026-07-12 22:21:38', NULL, '2026-07-12 22:22:22', 1, 0);

-- Dumping structure for view fueldesk_pro.licenseexpiringsoon
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `licenseexpiringsoon` (
	`CompanyLicenseID` INT(11) NOT NULL,
	`LicenseName` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`LicenseNo` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`ExpiryDate` DATE NULL,
	`RemainingDays` INT(7) NULL
);

-- Dumping structure for table fueldesk_pro.log_auditlog
CREATE TABLE IF NOT EXISTS `log_auditlog` (
  `AuditLogID` bigint(20) NOT NULL AUTO_INCREMENT,
  `TableName` varchar(100) NOT NULL,
  `RecordID` bigint(20) NOT NULL,
  `ActionType` varchar(20) NOT NULL,
  `OldData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`OldData`)),
  `NewData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`NewData`)),
  `ChangedBy` int(11) DEFAULT NULL COMMENT 'Logical FK -> sys_User.UserID',
  `ChangedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AuditLogID`),
  KEY `idx_log_AuditLog_Table_Record` (`TableName`,`RecordID`),
  KEY `idx_log_AuditLog_ChangedAt` (`ChangedAt`),
  CONSTRAINT `chk_log_AuditLog_ActionType` CHECK (`ActionType` in ('INSERT','UPDATE','DELETE'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Generic audit trail for all tables (future-proofing per Roadmap #5)';

-- Dumping data for table fueldesk_pro.log_auditlog: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.mst_customer
CREATE TABLE IF NOT EXISTS `mst_customer` (
  `CustomerID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CustomerID`),
  KEY `idx_mst_Customer_Mobile` (`Mobile`),
  KEY `idx_mst_Customer_Vehicle` (`VehicleNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credit customers';

-- Dumping data for table fueldesk_pro.mst_customer: ~1 rows (approximately)
INSERT INTO `mst_customer` (`CustomerID`, `CustomerName`, `Mobile`, `VehicleNumber`, `Address`, `OpeningDue`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'Walking Customer', '0', '0', '...', 0.00, NULL, '2026-07-11 20:02:43', NULL, '2026-07-11 20:02:43', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_dispenser
CREATE TABLE IF NOT EXISTS `mst_dispenser` (
  `DisID` int(11) NOT NULL AUTO_INCREMENT,
  `DisName` varchar(100) NOT NULL,
  `DisCode` varchar(30) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`DisID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispenser units housing nozzles';

-- Dumping data for table fueldesk_pro.mst_dispenser: ~1 rows (approximately)
INSERT INTO `mst_dispenser` (`DisID`, `DisName`, `DisCode`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'Dispenser-1', 'Dis-1', NULL, '2026-07-12 20:00:47', NULL, '2026-07-12 20:00:47', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_employee
CREATE TABLE IF NOT EXISTS `mst_employee` (
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `EmployeeId` (`EmployeeId`),
  CONSTRAINT `chk_mst_Employee_Salary` CHECK (`Salary` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Staff registry (bilingual)';

-- Dumping data for table fueldesk_pro.mst_employee: ~5 rows (approximately)
INSERT INTO `mst_employee` (`Id`, `EmployeeId`, `NameEN`, `NameBN`, `FatherName`, `MotherName`, `DateOfBirth`, `JoiningDate`, `Mobile`, `Address`, `NationalID`, `Guarantor`, `Salary`, `ImagePath`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'EMP101', 'Mohammad Zunaid Hossain', 'মুহাম্মদ জুনাইদ হোসাইন', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-11 20:11:41', NULL, '2026-07-14 17:25:38', 1, 0),
	(102, 'EMP102', 'Rokon Uddin', 'রোকন উদ্দিন', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-11 20:13:31', NULL, '2026-07-14 17:25:38', 1, 0),
	(103, 'EMP103', 'Fahim Uddin', 'ফাহিম উদ্দিন', '...', '...', NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:15:14', NULL, '2026-07-14 17:25:38', 1, 0),
	(104, 'EMP104', 'Anik Chowdhury', 'অনিক চৌধুরী', '...', '...', NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:15:14', NULL, '2026-07-14 17:25:38', 1, 0),
	(105, 'EMP105', 'Mamunul Islam', 'মামুনুল ইসলাম', NULL, NULL, NULL, NULL, '', NULL, '', NULL, 0.00, NULL, NULL, NULL, '2026-07-12 00:16:18', NULL, '2026-07-14 17:25:38', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_expensecategory
CREATE TABLE IF NOT EXISTS `mst_expensecategory` (
  `ExpenseCategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryNameEN` varchar(150) NOT NULL,
  `CategoryNameBN` varchar(150) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ExpenseCategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=2024 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Top-level expense buckets (bilingual)';

-- Dumping data for table fueldesk_pro.mst_expensecategory: ~23 rows (approximately)
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

-- Dumping structure for table fueldesk_pro.mst_expenseparticular
CREATE TABLE IF NOT EXISTS `mst_expenseparticular` (
  `ExpenseParticularID` int(11) NOT NULL AUTO_INCREMENT,
  `ParticularID` varchar(30) DEFAULT NULL,
  `ExpenseCategoryID` int(11) NOT NULL COMMENT 'Logical FK -> mst_ExpenseCategory.ExpenseCategoryID',
  `ParticularNameEN` varchar(150) NOT NULL,
  `ParticularNameBN` varchar(150) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ExpenseParticularID`),
  KEY `idx_mst_ExpenseParticular_Category` (`ExpenseCategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=2065 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Line items under expense category; also used by Others Collection (resolved conflict, migration authoritative)';

-- Dumping data for table fueldesk_pro.mst_expenseparticular: ~64 rows (approximately)
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
	(64, 'PRT64', 23, 'Signboard', 'সাইনবোর্ড', NULL, '2026-07-12 06:11:59', NULL, '2026-07-14 17:19:11', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_fueltype
CREATE TABLE IF NOT EXISTS `mst_fueltype` (
  `FuelTypeID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`FuelTypeID`),
  UNIQUE KEY `uq_mst_FuelType_Name` (`FuelName`),
  KEY `idx_mst_FuelType_Active` (`IsActive`,`IsDeleted`),
  CONSTRAINT `chk_mst_FuelType_SellingRate` CHECK (`SellingRate` >= 0),
  CONSTRAINT `chk_mst_FuelType_PurchaseRate` CHECK (`PurchaseRate` >= 0),
  CONSTRAINT `chk_mst_FuelType_CommissionRate` CHECK (`CommissionRate` >= 0),
  CONSTRAINT `chk_mst_FuelType_TaxPercent` CHECK (`TaxPercent` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fuel catalogue (Petrol/Diesel/LPG) with pricing';

-- Dumping data for table fueldesk_pro.mst_fueltype: ~2 rows (approximately)
INSERT INTO `mst_fueltype` (`FuelTypeID`, `FuelName`, `FuelCode`, `UnitOfMeasure`, `SellingRate`, `PurchaseRate`, `CommissionRate`, `TaxPercent`, `Density`, `ColorCode`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'LPG', 'LPG', '0', 70.40, 62.40, 8.0000, 0.000, NULL, NULL, '', NULL, '2026-07-11 20:16:00', 1, '2026-07-14 22:35:48', 1, 0),
	(102, 'Test Fuel Type X', 'TFX', '0', 12.50, 10.50, 0.5000, 0.000, NULL, NULL, 'Test entry', 1, '2026-07-14 22:10:35', NULL, '2026-07-14 22:11:03', 1, 1);

-- Dumping structure for table fueldesk_pro.mst_licensetype
CREATE TABLE IF NOT EXISTS `mst_licensetype` (
  `LicenseTypeID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` bit(1) DEFAULT b'0',
  PRIMARY KEY (`LicenseTypeID`),
  UNIQUE KEY `LicenseCode` (`LicenseCode`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fueldesk_pro.mst_licensetype: ~8 rows (approximately)
INSERT INTO `mst_licensetype` (`LicenseTypeID`, `LicenseCode`, `LicenseName`, `LicenseNameBN`, `GovernmentAuthority`, `Description`, `IsExpiryRequired`, `DefaultValidityMonths`, `DefaultNotifyBefore`, `IsAllowNotification`, `Icon`, `SortOrder`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 'TRADE', 'Trade License', 'ট্রেড লাইসেন্স', 'City Corporation', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(2, 'BIN', 'BIN Registration', 'বিআইএন নিবন্ধন', 'National Board of Revenue', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(3, 'TIN', 'TIN Certificate', 'টিআইএন', 'National Board of Revenue', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(4, 'FIRE', 'Fire License', 'ফায়ার লাইসেন্স', 'Fire Service & Civil Defence', NULL, b'1', 12, 60, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(5, 'DOE', 'Environment Clearance', 'পরিবেশ অধিদপ্তর', 'Department of Environment', NULL, b'1', 12, 60, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(6, 'UNO', 'NOC', 'উপজেলা নির্বাহী কর্মকর্তার অনাপত্তি', 'UNO Office', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(7, 'EXPLOSIVE', 'Explosive License', 'বিস্ফোরক লাইসেন্স', 'Department of Explosives', NULL, b'1', 12, 90, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0'),
	(8, 'BSTI', 'BSTI Certificate', 'বিএসটিআই', 'BSTI', NULL, b'1', 12, 30, b'1', NULL, 1, NULL, '2026-07-12 23:08:17', NULL, NULL, b'1', b'0');

-- Dumping structure for table fueldesk_pro.mst_nozzle
CREATE TABLE IF NOT EXISTS `mst_nozzle` (
  `NozzleID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`NozzleID`),
  UNIQUE KEY `uq_mst_Nozzle` (`DisID`,`NozzleNo`) USING BTREE,
  KEY `idx_mst_Nozzle_FuelType` (`FuelTypeID`),
  KEY `idx_mst_Nozzle_Pump` (`DisID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual fuel-dispensing nozzles (dual-meter: general + master)';

-- Dumping data for table fueldesk_pro.mst_nozzle: ~2 rows (approximately)
INSERT INTO `mst_nozzle` (`NozzleID`, `DisID`, `NozzleNo`, `FuelTypeID`, `TankGroupID`, `OpeningGeneral`, `NozzleName`, `OpeningMaster`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 101, 1, 101, 101, 279699.980, 'N1', 505567.000, NULL, '2026-07-12 20:09:52', NULL, '2026-07-14 20:06:39', 1, 0),
	(102, 101, 2, 101, 101, 337055.640, 'N2', 561640.000, NULL, '2026-07-12 21:23:22', NULL, '2026-07-14 20:06:44', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_otherscollectionparticular
CREATE TABLE IF NOT EXISTS `mst_otherscollectionparticular` (
  `OthersCollectionParticularID` int(11) NOT NULL AUTO_INCREMENT,
  `NameEN` varchar(150) DEFAULT NULL,
  `NameBN` varchar(150) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`OthersCollectionParticularID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DEPRECATED: legacy particulars, retained for historical data only';

-- Dumping data for table fueldesk_pro.mst_otherscollectionparticular: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.mst_shareholder
CREATE TABLE IF NOT EXISTS `mst_shareholder` (
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `ShareHolderID` (`ShareHolderID`),
  CONSTRAINT `chk_mst_Shareholder_Investment` CHECK (`InvestmentAmount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Owners / investors (bilingual)';

-- Dumping data for table fueldesk_pro.mst_shareholder: ~6 rows (approximately)
INSERT INTO `mst_shareholder` (`Id`, `ShareHolderID`, `NameEN`, `NameBN`, `Mobile`, `Address`, `InvestmentAmount`, `ImagePath`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'SH101', 'Main Uddin', 'মঈন উদ্দিন', '01819800600', 'Ukil Bari, Jonar Kewsia, Satkania, Chattogram.', 0.00, NULL, NULL, NULL, '2026-07-12 21:27:12', NULL, '2026-07-14 17:43:31', 1, 0),
	(102, 'SH102', 'Mohammad Ali', 'মোহাম্মদ আলী', '0', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:06:37', NULL, '2026-07-14 17:43:31', 1, 0),
	(103, 'SH103', 'Abul Kashem', 'আবুল কাশেম', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:08', NULL, '2026-07-14 17:43:31', 1, 0),
	(104, 'SH104', 'Jahir Uddin', 'জহির উদ্দিন', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:27', NULL, '2026-07-14 17:43:31', 1, 0),
	(105, 'SH105', 'Dr. Abul Hashem', 'ডা. আবুল হাশেম', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:07:47', NULL, '2026-07-14 17:43:31', 1, 0),
	(106, 'SH106', 'Mohiuddin Md. Kachir', 'মহিউদ্দিন মো. কচির', '', NULL, 0.00, NULL, NULL, NULL, '2026-07-13 12:08:17', NULL, '2026-07-14 17:43:31', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_shift
CREATE TABLE IF NOT EXISTS `mst_shift` (
  `ShiftID` int(11) NOT NULL AUTO_INCREMENT,
  `ShiftName` varchar(100) NOT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ShiftID`),
  UNIQUE KEY `uq_mst_Shift_Name` (`ShiftName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Operating shifts (Morning/Evening/Night)';

-- Dumping data for table fueldesk_pro.mst_shift: ~3 rows (approximately)
INSERT INTO `mst_shift` (`ShiftID`, `ShiftName`, `StartTime`, `EndTime`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 'Morning Shift', '06:00:00', '14:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0),
	(2, 'Evening Shift', '14:00:00', '22:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0),
	(3, 'Night Shift', '22:00:00', '06:00:00', 1, '2026-07-12 21:32:28', NULL, '0000-00-00 00:00:00', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_supplier
CREATE TABLE IF NOT EXISTS `mst_supplier` (
  `SupplierID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`SupplierID`),
  KEY `idx_mst_Supplier_Name` (`SupplierName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fuel vendors / suppliers';

-- Dumping data for table fueldesk_pro.mst_supplier: ~1 rows (approximately)
INSERT INTO `mst_supplier` (`SupplierID`, `SupplierName`, `ContactPerson`, `Mobile`, `Email`, `Address`, `OpeningBalance`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 'AyGayz LPG Ltd.', '...', '0', NULL, NULL, 0.00, NULL, '2026-07-12 23:41:29', NULL, '2026-07-12 23:41:29', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_tank
CREATE TABLE IF NOT EXISTS `mst_tank` (
  `TankID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`TankID`),
  KEY `idx_mst_Tank_FuelType` (`FuelTypeID`),
  KEY `idx_mst_Tank_TankGroup` (`TankGroupID`),
  CONSTRAINT `chk_mst_Tank_Capacity` CHECK (`Capacity` >= 0),
  CONSTRAINT `chk_mst_Tank_MinLevel` CHECK (`MinLevel` >= 0),
  CONSTRAINT `chk_mst_Tank_OpeningStockPercent` CHECK (`OpeningStockPercent` between 0 and 100)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Physical underground storage tanks';

-- Dumping data for table fueldesk_pro.mst_tank: ~1 rows (approximately)
INSERT INTO `mst_tank` (`TankID`, `TankName`, `TankCode`, `TankGroupID`, `FuelTypeID`, `Capacity`, `MinLevel`, `OpeningStockPercent`, `Priority`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'LPG-Tank-1', 'LPG-1', 101, 101, 20000.000, 2000.000, 23.00, 1, NULL, '2026-07-12 23:50:45', NULL, '2026-07-12 23:50:45', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_tankgroup
CREATE TABLE IF NOT EXISTS `mst_tankgroup` (
  `TankGroupID` int(11) NOT NULL AUTO_INCREMENT,
  `TankGroupName` varchar(100) NOT NULL,
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`TankGroupID`),
  UNIQUE KEY `uq_mst_TankGroup` (`TankGroupName`,`FuelTypeID`),
  KEY `idx_mst_TankGroup_FuelType` (`FuelTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Group of physically-connected tanks sharing stock';

-- Dumping data for table fueldesk_pro.mst_tankgroup: ~1 rows (approximately)
INSERT INTO `mst_tankgroup` (`TankGroupID`, `TankGroupName`, `FuelTypeID`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, 'LPG', 101, NULL, '2026-07-12 20:05:52', NULL, '2026-07-12 20:05:52', 1, 0);

-- Dumping structure for table fueldesk_pro.mst_unitofmeasure
CREATE TABLE IF NOT EXISTS `mst_unitofmeasure` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UnitId` varchar(20) DEFAULT NULL,
  `UnitNameEN` varchar(50) DEFAULT NULL,
  `UnitNameBN` varchar(50) DEFAULT NULL,
  `Remark` varchar(250) DEFAULT NULL,
  `CreateAt` datetime DEFAULT NULL,
  `CreatedBy` varchar(50) DEFAULT NULL,
  `UpdateAt` datetime DEFAULT NULL,
  `UpdatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UnitId` (`UnitId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fueldesk_pro.mst_unitofmeasure: ~1 rows (approximately)
INSERT INTO `mst_unitofmeasure` (`Id`, `UnitId`, `UnitNameEN`, `UnitNameBN`, `Remark`, `CreateAt`, `CreatedBy`, `UpdateAt`, `UpdatedBy`) VALUES
	(1, 'U101', 'Liter', 'লিটার', 'লিটার/Liter', NULL, NULL, NULL, NULL);

-- Dumping structure for table fueldesk_pro.sys_role
CREATE TABLE IF NOT EXISTS `sys_role` (
  `RoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleCode` varchar(30) NOT NULL COMMENT 'e.g. SUPER_ADMIN, OWNER, MANAGER, OPERATOR, ACCOUNTANT',
  `RoleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`RoleID`),
  UNIQUE KEY `uq_sys_Role_RoleCode` (`RoleCode`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application role catalog (RBAC)';

-- Dumping data for table fueldesk_pro.sys_role: ~12 rows (approximately)
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

-- Dumping structure for table fueldesk_pro.sys_user
CREATE TABLE IF NOT EXISTS `sys_user` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `uq_sys_User_Email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login users';

-- Dumping data for table fueldesk_pro.sys_user: ~1 rows (approximately)
INSERT INTO `sys_user` (`UserID`, `FullName`, `Email`, `Mobile`, `PasswordHash`, `AvatarPath`, `LastLoginAt`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(100, 'Mohammad Zunaid Hossain', 'zunaidctg11@gmail.com', '01812793369', '123', NULL, NULL, NULL, '2026-07-13 00:06:08', NULL, '2026-07-13 00:06:08', 1, 0);

-- Dumping structure for table fueldesk_pro.sys_userrole
CREATE TABLE IF NOT EXISTS `sys_userrole` (
  `UserRoleID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL COMMENT 'Logical FK -> sys_User.UserID',
  `RoleID` int(11) NOT NULL COMMENT 'Logical FK -> sys_Role.RoleID',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`UserRoleID`),
  UNIQUE KEY `uq_sys_UserRole` (`UserID`,`RoleID`),
  KEY `idx_sys_UserRole_User` (`UserID`),
  KEY `idx_sys_UserRole_Role` (`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Junction table: user to role assignment';

-- Dumping data for table fueldesk_pro.sys_userrole: ~1 rows (approximately)
INSERT INTO `sys_userrole` (`UserRoleID`, `UserID`, `RoleID`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(1, 100, 12, NULL, '2026-07-13 00:09:26', NULL, '2026-07-13 00:09:26', 1, 0);

-- Dumping structure for table fueldesk_pro.trn_companylicense
CREATE TABLE IF NOT EXISTS `trn_companylicense` (
  `CompanyLicenseID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` bit(1) DEFAULT b'0',
  PRIMARY KEY (`CompanyLicenseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fueldesk_pro.trn_companylicense: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trn_companylicensehistory
CREATE TABLE IF NOT EXISTS `trn_companylicensehistory` (
  `HistoryID` int(11) NOT NULL AUTO_INCREMENT,
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
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`HistoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fueldesk_pro.trn_companylicensehistory: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_cashcollection
CREATE TABLE IF NOT EXISTS `trx_cashcollection` (
  `CashCollectionID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CashCollectionID`),
  KEY `idx_trx_CashCollection_Date` (`CollectionDate`),
  KEY `idx_trx_CashCollection_Person` (`CollectedByType`,`CollectedPersonID`),
  CONSTRAINT `chk_trx_CashCollection_Amount` CHECK (`Amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cash handover records from operator/employee to owner/shareholder';

-- Dumping data for table fueldesk_pro.trx_cashcollection: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_customercollection
CREATE TABLE IF NOT EXISTS `trx_customercollection` (
  `CustomerCollectionID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CustomerCollectionID`),
  KEY `idx_trx_CustomerCollection_Due` (`CustomerDueID`),
  KEY `idx_trx_CustomerCollection_CustomerDate` (`CustomerID`,`TxnDate`),
  CONSTRAINT `chk_trx_CustomerCollection_Amount` CHECK (`Amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payments recovered against customer dues';

-- Dumping data for table fueldesk_pro.trx_customercollection: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_customerdue
CREATE TABLE IF NOT EXISTS `trx_customerdue` (
  `CustomerDueID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CustomerDueID`),
  KEY `idx_trx_CustomerDue_Date` (`TxnDate`),
  KEY `idx_trx_CustomerDue_Customer` (`CustomerID`,`DueAmount`),
  CONSTRAINT `chk_trx_CustomerDue_Quantity` CHECK (`Quantity` >= 0),
  CONSTRAINT `chk_trx_CustomerDue_Rate` CHECK (`Rate` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credit sale entries per customer invoice';

-- Dumping data for table fueldesk_pro.trx_customerdue: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_expense
CREATE TABLE IF NOT EXISTS `trx_expense` (
  `ExpenseID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`ExpenseID`),
  KEY `idx_trx_Expense_Date` (`ExpenseDate`),
  KEY `idx_trx_Expense_Particular` (`ParticularID`) USING BTREE,
  CONSTRAINT `chk_trx_Expense_Amount` CHECK (`Amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily operational expenses';

-- Dumping data for table fueldesk_pro.trx_expense: ~7 rows (approximately)
INSERT INTO `trx_expense` (`ExpenseID`, `ExpenseDate`, `ParticularID`, `Amount`, `PaymentMethodID`, `ReferenceNo`, `Remarks`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`) VALUES
	(101, '2026-07-17', 'PRT1', 100.00, 1, 'simple text', 'abc', NULL, '2026-07-17 00:34:55', 1, '2026-07-24 11:34:59', 1, 1),
	(102, '2026-07-23', 'PRT2', 100.00, 0, NULL, 'aaa', 1, '2026-07-23 23:36:00', 1, '2026-07-24 11:34:53', 1, 1),
	(103, '2026-07-23', 'PETROL-001', 150.00, 1, 'REF-2026-001', 'Test insert', 1, '2026-07-24 11:18:21', 1, '2026-07-24 11:34:57', 1, 1),
	(104, '2026-07-24', 'PETROL-002', 200.00, 2, 'REF-2026-002', '0', 1, '2026-07-24 11:19:06', 1, '2026-07-24 11:19:06', 1, 1),
	(105, '2026-07-24', 'UPDATED-001', 250.00, 2, 'API-REF-002', 'API test update', 1, '2026-07-24 11:20:49', 1, '2026-07-24 11:22:56', 1, 1),
	(106, '2026-07-23', 'API-TEST-001', 100.00, 1, 'API-REF-001', 'API test insert', 1, '2026-07-24 11:22:00', 1, '2026-07-24 11:34:54', 1, 1),
	(107, '2026-07-24', 'PRT1', 50.00, 0, 'ডপটডযটপ', 'কককক', 1, '2026-07-24 14:13:04', 1, '2026-07-24 14:13:04', 1, 0);

-- Dumping structure for table fueldesk_pro.trx_fuelpriceadjustment
CREATE TABLE IF NOT EXISTS `trx_fuelpriceadjustment` (
  `FuelPriceAdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`FuelPriceAdjustmentID`),
  UNIQUE KEY `uq_trx_FuelPriceAdjustment` (`FuelTypeID`,`EffectiveDate`),
  KEY `idx_trx_FuelPriceAdjustment_Date` (`EffectiveDate`),
  KEY `idx_trx_FuelPriceAdjustment_FuelType` (`FuelTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock revaluation gain/loss on government price changes';

-- Dumping data for table fueldesk_pro.trx_fuelpriceadjustment: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_fuelpurchase
CREATE TABLE IF NOT EXISTS `trx_fuelpurchase` (
  `FuelPurchaseID` int(11) NOT NULL AUTO_INCREMENT,
  `InvoiceNo` varchar(100) DEFAULT NULL,
  `PurchaseDate` date NOT NULL,
  `SupplierID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Supplier.SupplierID',
  `FuelTypeID` int(11) NOT NULL COMMENT 'Logical FK -> mst_FuelType.FuelTypeID',
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Rate` decimal(14,2) NOT NULL DEFAULT 0.00,
  `Amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `PaymentStatus` varchar(20) NOT NULL DEFAULT 'Due',
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`FuelPurchaseID`),
  KEY `idx_trx_FuelPurchase_Date` (`PurchaseDate`),
  KEY `idx_trx_FuelPurchase_Supplier` (`SupplierID`),
  KEY `idx_trx_FuelPurchase_FuelType` (`FuelTypeID`),
  KEY `idx_trx_FuelPurchase_Tank` (`TankID`),
  CONSTRAINT `chk_trx_FuelPurchase_Quantity` CHECK (`Quantity` >= 0),
  CONSTRAINT `chk_trx_FuelPurchase_Rate` CHECK (`Rate` >= 0),
  CONSTRAINT `chk_trx_FuelPurchase_PaymentStatus` CHECK (`PaymentStatus` in ('Paid','Partial','Due'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fuel delivery / purchase records';

-- Dumping data for table fueldesk_pro.trx_fuelpurchase: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_nozzlereading
CREATE TABLE IF NOT EXISTS `trx_nozzlereading` (
  `NozzleReadingID` int(11) NOT NULL AUTO_INCREMENT,
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
  `CommissionAmt` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`NozzleReadingID`),
  UNIQUE KEY `uq_trx_NozzleReading` (`NozzleID`,`ReadingDate`,`ShiftID`),
  KEY `idx_trx_NozzleReading_Lookup` (`NozzleID`,`ReadingDate`),
  KEY `idx_trx_NozzleReading_Date` (`ReadingDate`),
  CONSTRAINT `chk_trx_NozzleReading_General` CHECK (`GeneralReading` >= `PreviousGeneral`),
  CONSTRAINT `chk_trx_NozzleReading_Master` CHECK (`MasterReading` >= `PreviousMaster`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily nozzle meter readings (dual-meter: general + master)';

-- Dumping data for table fueldesk_pro.trx_nozzlereading: ~2 rows (approximately)
INSERT INTO `trx_nozzlereading` (`NozzleReadingID`, `ReadingDate`, `ShiftID`, `StationID`, `DisID`, `NozzleID`, `GeneralReading`, `MasterReading`, `PreviousGeneral`, `PreviousMaster`, `Notes`, `RecordedAt`, `CreatedBy`, `CreatedAt`, `UpdatedBy`, `UpdatedAt`, `IsActive`, `IsDeleted`, `SaleQuantity`, `SellingRate`, `PurchaseRate`, `SalesAmt`, `CommissionRate`, `CommissionAmt`) VALUES
	(1, '2026-07-01', 2, 1, 101, 101, 279983.680, 505850.000, 279699.980, 505567.000, '', '2026-07-14 20:57:09', 1, '2026-07-14 20:57:09', NULL, '2026-07-14 23:33:05', 1, 0, 283.700, 86.930, 0.000, 24662.04, 8.000, 2269.60),
	(7, '2026-07-01', 2, 1, 101, 102, 337442.930, 562028.000, 337055.640, 561640.000, '', '2026-07-14 21:51:32', 1, '2026-07-14 21:51:32', NULL, '2026-07-14 23:33:05', 1, 0, 387.290, 86.930, 0.000, 33667.12, 8.000, 3098.32);

-- Dumping structure for table fueldesk_pro.trx_nozzletest
CREATE TABLE IF NOT EXISTS `trx_nozzletest` (
  `NozzleTestID` int(11) NOT NULL AUTO_INCREMENT,
  `TestDate` date NOT NULL,
  `MeterID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Meter.MeterID',
  `Quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`NozzleTestID`),
  KEY `idx_trx_NozzleTest_Date` (`TestDate`),
  CONSTRAINT `chk_trx_NozzleTest_Quantity` CHECK (`Quantity` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Calibration test dispenses (test-can, returned to tank)';

-- Dumping data for table fueldesk_pro.trx_nozzletest: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_otherscollection
CREATE TABLE IF NOT EXISTS `trx_otherscollection` (
  `OthersCollectionID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`OthersCollectionID`),
  UNIQUE KEY `uq_trx_OthersCollection_Dedup` (`CollectionDate`,`ParticularID`,`Amount`,`ReferenceNo`),
  KEY `idx_trx_OthersCollection_Date` (`CollectionDate`),
  CONSTRAINT `chk_trx_OthersCollection_Amount` CHECK (`Amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Miscellaneous non-fuel income (air/water/rent etc.)';

-- Dumping data for table fueldesk_pro.trx_otherscollection: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_supplierpayment
CREATE TABLE IF NOT EXISTS `trx_supplierpayment` (
  `SupplierPaymentID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`SupplierPaymentID`),
  KEY `idx_trx_SupplierPayment_Date` (`PaymentDate`),
  KEY `idx_trx_SupplierPayment_Supplier` (`SupplierID`),
  CONSTRAINT `chk_trx_SupplierPayment_Amount` CHECK (`Amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payments disbursed to suppliers (ledger only, excluded from daily Net Cash)';

-- Dumping data for table fueldesk_pro.trx_supplierpayment: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_tankdip
CREATE TABLE IF NOT EXISTS `trx_tankdip` (
  `TankDipID` int(11) NOT NULL AUTO_INCREMENT,
  `DipDate` date NOT NULL,
  `TankID` int(11) NOT NULL COMMENT 'Logical FK -> mst_Tank.TankID',
  `PhysicalStock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`TankDipID`),
  KEY `idx_trx_TankDip_Date` (`DipDate`),
  KEY `idx_trx_TankDip_Tank` (`TankID`),
  CONSTRAINT `chk_trx_TankDip_PhysicalStock` CHECK (`PhysicalStock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Physical stick dip reading used for Stock Variance Report';

-- Dumping data for table fueldesk_pro.trx_tankdip: ~0 rows (approximately)

-- Dumping structure for table fueldesk_pro.trx_tankreading
CREATE TABLE IF NOT EXISTS `trx_tankreading` (
  `TankReadingID` int(11) NOT NULL AUTO_INCREMENT,
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
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`TankReadingID`),
  UNIQUE KEY `uq_trx_TankReading` (`TankID`,`ReadingDate`),
  KEY `idx_trx_TankReading_Date` (`ReadingDate`),
  CONSTRAINT `chk_trx_TankReading_Previous` CHECK (`PreviousReadingPercent` between 0 and 100),
  CONSTRAINT `chk_trx_TankReading_Current` CHECK (`CurrentReadingPercent` between 0 and 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily percent-based tank stock tracking (operational gauge)';

-- Dumping data for table fueldesk_pro.trx_tankreading: ~0 rows (approximately)

-- Dumping structure for view fueldesk_pro.vw_expparticular
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `vw_expparticular` (
	`ParticularID` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`ParticularNameEN` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`ParticularNameBN` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci'
);

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `licenseexpiringsoon`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `licenseexpiringsoon` AS SELECT
      cl.CompanyLicenseID,
      lt.LicenseName,
      cl.LicenseNo,
      cl.ExpiryDate,
      DATEDIFF(cl.ExpiryDate,CURDATE()) RemainingDays

FROM trn_companylicense cl

INNER JOIN mst_licensetype lt
ON lt.LicenseTypeID=cl.LicenseTypeID

WHERE
cl.IsDeleted=0
AND cl.IsActive=1
AND cl.IsAllowNotification=1

AND DATEDIFF(cl.ExpiryDate,CURDATE())<=cl.NotifyBefore 
;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `vw_expparticular`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vw_expparticular` AS SELECT ParticularID, ParticularNameEN, ParticularNameBN 
FROM mst_expenseparticular

UNION

SELECT EmployeeId, NameEN, NameBN 
FROM mst_employee

UNION

SELECT ShareHOlderID, NameEN, NameBN 
FROM mst_shareholder 
;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
