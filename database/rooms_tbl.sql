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
-- Table structure for table `rooms_tbl`
--

CREATE TABLE `rooms_tbl` (
  `room_id` int(11) NOT NULL,
  `building_id` int(11) DEFAULT NULL,
  `room_number` varchar(50) NOT NULL,
  `room_type` enum('Lecture','Laboratory') NOT NULL,
  `room_status` enum('Available','Unavailable') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms_tbl`
--

INSERT INTO `rooms_tbl` (`room_id`, `building_id`, `room_number`, `room_type`, `room_status`, `created_at`) VALUES
(1, 4, '102', 'Lecture', 'Available', '2024-10-22 13:28:07'),
(2, 3, '101', 'Lecture', 'Available', '2024-10-22 13:13:44'),
(3, 3, '102', 'Lecture', 'Available', '2024-10-22 13:16:10'),
(4, 3, '103', 'Lecture', 'Available', '2024-10-22 13:16:40'),
(5, 3, '104', 'Lecture', 'Available', '2024-10-22 13:17:53'),
(6, 3, 'COMLAB 101', 'Laboratory', 'Available', '2024-10-22 13:19:21'),
(7, 3, 'COMLAB 102', 'Laboratory', 'Available', '2024-10-22 13:20:11'),
(8, 3, 'COMLAB 103', 'Laboratory', 'Available', '2024-10-22 13:23:16'),
(9, 3, 'COMLAB 104', 'Laboratory', 'Available', '2024-10-22 13:25:14'),
(10, 4, '101', 'Lecture', 'Available', '2024-10-22 13:26:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `building_id` (`building_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD CONSTRAINT `rooms_tbl_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings_tbl` (`building_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
