-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2024 at 03:43 PM
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
-- Database: `reservadb`
--

-- --------------------------------------------------------

--
-- Table structure for table `buildings_tbl`
--

CREATE TABLE `buildings_tbl` (
  `building_id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `building_desc` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings_tbl`
--

INSERT INTO `buildings_tbl` (`building_id`, `building_name`, `building_desc`, `created_at`) VALUES
(2, 'Sample', 'Sample', '2024-10-22 01:30:59'),
(3, 'CEIT', 'Civil Engineering and Information Technology Building', '2024-10-22 01:45:51'),
(4, 'CABA', 'sample', '2024-10-22 04:22:04'),
(5, 'SC/MAIN', 'Main Building in Maysan Campus', '2024-10-23 05:26:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buildings_tbl`
--
ALTER TABLE `buildings_tbl`
  ADD PRIMARY KEY (`building_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buildings_tbl`
--
ALTER TABLE `buildings_tbl`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
