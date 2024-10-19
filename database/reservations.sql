-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2024 at 04:44 AM
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
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_department` varchar(255) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `additional_info` text DEFAULT NULL,
  `facultyInCharge` text NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `reservation_status` varchar(50) NOT NULL,
  `rejection_reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `user_department`, `facility_id`, `facility_name`, `start_date`, `end_date`, `reservation_date`, `start_time`, `end_time`, `additional_info`, `facultyInCharge`, `purpose`, `reservation_status`, `rejection_reason`, `created_at`) VALUES
(16, 18, 'Civil Engineering', 21, 'Multi-purpose Hall', NULL, NULL, '2024-08-31', '12:30:00', '16:30:00', 'IT Students Capstone Defense', '', 'Defense', 'Reserved', '', '2024-05-28 15:29:11'),
(17, 18, 'Civil Engineering', 20, 'Multi-purpose Hall', NULL, NULL, '2024-08-31', '07:00:00', '09:00:00', 'CEIT Event', '', 'Symposium', 'Reserved', '', '2024-05-29 01:23:19'),
(18, 18, 'Civil Engineering', 21, 'Collab 2', NULL, NULL, '2024-08-31', '07:00:00', '10:00:00', 'IT Defense', 'Sample', 'Defense', 'Declined', 'Faculty In Charge', '2024-05-29 02:09:48'),
(19, 18, 'Civil Engineering', 20, 'Multi-purpose Hall', NULL, NULL, '2024-08-31', '09:30:00', '12:00:00', 'CEIT Event', '', 'Symposium', 'Reserved', '', '2024-05-29 01:23:19'),
(25, 18, 'Civil Engineering', 22, 'Collab 3', NULL, NULL, '2024-09-02', '07:00:00', '07:30:00', 'sample', '', 'sample', 'Reserved', '', '2024-09-01 12:19:21'),
(26, 18, 'Civil Engineering', 23, 'Sample', NULL, NULL, '2024-09-06', '07:00:00', '09:00:00', 'da', '', 'da', 'Reserved', '', '2024-09-05 00:42:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
