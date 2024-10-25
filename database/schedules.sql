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
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `section` varchar(50) NOT NULL,
  `instructor` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `days` varchar(50) NOT NULL,
  `class_type` enum('Lecture','Laboratory') NOT NULL,
  `ay_semester` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `sched_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `user_id`, `subject_code`, `subject`, `section`, `instructor`, `start_time`, `end_time`, `days`, `class_type`, `ay_semester`, `department_id`, `sched_status`, `created_at`, `updated_at`) VALUES
(55, 17, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Monday', 'Lecture', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18'),
(56, 17, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Wednesday', 'Lecture', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18'),
(57, 17, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Tuesday', 'Lecture', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18'),
(58, 17, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Thursday', 'Lecture', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18'),
(59, 17, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Monday', 'Laboratory', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18'),
(60, 17, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Thursday', 'Laboratory', 1, 2, 'pending', '2024-10-23 13:03:18', '2024-10-23 13:03:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `subject_code` (`subject_code`),
  ADD KEY `instructor` (`instructor`),
  ADD KEY `ay_semester` (`ay_semester`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_ay_semester` FOREIGN KEY (`ay_semester`) REFERENCES `terms_tbl` (`term_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_department_id` FOREIGN KEY (`department_id`) REFERENCES `dept_tbl` (`dept_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
