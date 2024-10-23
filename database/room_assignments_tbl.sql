-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2024 at 03:44 PM
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
-- Table structure for table `room_assignments_tbl`
--

CREATE TABLE `room_assignments_tbl` (
  `assignment_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  ADD CONSTRAINT `room_assignments_tbl_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms_tbl` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_assignments_tbl_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
