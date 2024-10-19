-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2024 at 06:42 AM
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
-- Table structure for table `assigned_rooms_tbl`
--

CREATE TABLE `assigned_rooms_tbl` (
  `assignment_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_rooms_tbl`
--

INSERT INTO `assigned_rooms_tbl` (`assignment_id`, `schedule_id`, `room_id`, `day`, `start_time`, `end_time`) VALUES
(243, 427, 9, 'Monday', '09:00:00', '10:30:00'),
(244, 428, 5, 'Wednesday', '09:00:00', '10:30:00'),
(245, 429, 5, 'Tuesday', '11:00:00', '12:30:00'),
(246, 430, 5, 'Thursday', '11:00:00', '12:30:00'),
(247, 431, 7, 'Monday', '14:00:00', '15:30:00'),
(248, 432, 7, 'Thursday', '14:00:00', '15:30:00'),
(249, 433, 4, 'Monday', '09:00:00', '10:30:00'),
(250, 434, 6, 'Wednesday', '09:00:00', '10:30:00'),
(251, 435, 6, 'Tuesday', '11:00:00', '12:30:00'),
(252, 436, 6, 'Thursday', '11:00:00', '12:30:00'),
(253, 437, 7, 'Monday', '14:00:00', '15:30:00'),
(254, 438, 7, 'Thursday', '14:00:00', '15:30:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_rooms_tbl`
--
ALTER TABLE `assigned_rooms_tbl`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `fk_schedule` (`schedule_id`),
  ADD KEY `fk_room` (`room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_rooms_tbl`
--
ALTER TABLE `assigned_rooms_tbl`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_rooms_tbl`
--
ALTER TABLE `assigned_rooms_tbl`
  ADD CONSTRAINT `fk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `fk_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules_tbl` (`schedule_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
