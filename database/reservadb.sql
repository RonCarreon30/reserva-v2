-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2024 at 01:48 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `dept_tbl`
--

CREATE TABLE `dept_tbl` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_building` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dept_tbl`
--

INSERT INTO `dept_tbl` (`dept_id`, `dept_name`, `dept_building`) VALUES
(1, 'Information Technology', 'CEIT'),
(2, 'Civil Engineering', 'CEIT'),
(3, 'Electrical Engineering', 'CEIT'),
(4, 'Business Administration', 'CABA'),
(5, 'Education', 'COED'),
(8, 'Accountancy', 'CABA');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `building` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `descri` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `facility_name`, `building`, `status`, `descri`) VALUES
(9, 'Assembly Hall', 'COED', 'Unavailable', 'Located at College of Education Building'),
(10, 'Auditorium', 'SC/MAIN', 'Available', 'Located at the Main building'),
(11, 'Collab', 'CEIT', 'Available', 'Located at 3rd floor of CEIT Building'),
(19, 'Collab 1', 'CEIT', 'Available', 'Located at the 3rd floor of CEIT\r\n'),
(20, 'Multi-purpose Hall', 'CEIT', 'Available', 'MPH is located at the 6th floor of CEIT Building'),
(21, 'Collab 2', 'CEIT', 'Available', 'CEIT Collab room at 3rd Floor'),
(22, 'Collab 3', 'CEIT', 'Available', 'Located at 3rd foor of CEIT building'),
(23, 'Sample', 'SC/MAIN', 'Available', 'Sample');

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
(17, 18, 'Civil Engineering', 20, 'Multi-purpose Hall', NULL, NULL, '2024-08-31', '07:00:00', '09:00:00', 'CEIT Event', '', 'Symposium', 'Expired', '', '2024-05-29 01:23:19'),
(18, 18, 'Civil Engineering', 21, 'Collab 2', NULL, NULL, '2024-08-31', '07:00:00', '10:00:00', 'IT Defense', 'Update', 'Defense', 'Expired', '', '2024-05-29 02:09:48'),
(19, 18, 'Civil Engineering', 20, 'Multi-purpose Hall', NULL, NULL, '2024-08-31', '09:30:00', '12:00:00', 'CEIT Event', '', 'Symposium', 'Expired', '', '2024-05-29 01:23:19'),
(34, 18, 'Civil Engineering', 10, 'Auditorium', NULL, NULL, '2024-10-16', '07:00:00', '10:00:00', 'CEIT Event\n', 'Einstein', 'Symposium', 'In Review', '', '2024-10-07 13:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `building` varchar(20) NOT NULL,
  `room_type` enum('Lecture','Laboratory','Mechanical') NOT NULL,
  `room_status` enum('Available','Unavailable') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `departments` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `building`, `room_type`, `room_status`, `created_at`, `departments`) VALUES
(1, '101', 'CABA', 'Lecture', 'Available', '2024-05-27 01:32:24', 'Accountancy and Business Administration'),
(2, '102', 'CABA', 'Lecture', 'Available', '2024-05-27 01:32:36', 'Accountancy and Business Administration'),
(3, '201', 'COED', 'Lecture', 'Available', '2024-05-27 03:10:20', 'Education'),
(4, '202', 'COED', 'Lecture', 'Available', '2024-05-27 03:11:23', 'Education'),
(5, '101', 'CEIT', 'Lecture', 'Available', '2024-05-27 03:12:11', 'Civil Engineering and Information Technology'),
(6, '102', 'CEIT', 'Lecture', 'Available', '2024-05-27 03:20:42', 'Civil Engineering and Information Technology'),
(7, 'COMLAB 101', 'CEIT', 'Laboratory', 'Available', '2024-05-28 03:39:02', 'Civil Engineering and Information Technology'),
(8, '501', 'COED', 'Lecture', 'Available', '2024-05-28 14:56:20', 'Education'),
(9, '103', 'CABA', 'Lecture', 'Available', '2024-05-28 15:21:19', 'Accountancy and Business Administration'),
(10, 'COMLAB 102', 'CEIT', 'Laboratory', 'Available', '2024-10-12 14:06:01', ''),
(12, 'COMLAB 103', 'CEIT', 'Laboratory', 'Available', '2024-10-13 08:42:12', '');

-- --------------------------------------------------------

--
-- Table structure for table `schedules_tbl`
--

CREATE TABLE `schedules_tbl` (
  `schedule_id` int(11) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `subject` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `days` varchar(10) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `schedule_status` varchar(50) DEFAULT 'pending',
  `user_dept` varchar(100) NOT NULL,
  `pref_dept` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules_tbl`
--

INSERT INTO `schedules_tbl` (`schedule_id`, `subject_code`, `subject`, `section`, `instructor`, `start_time`, `end_time`, `days`, `type`, `term_id`, `schedule_status`, `user_dept`, `pref_dept`) VALUES
(427, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Monday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(428, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Wednesday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(429, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Tuesday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(430, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Thursday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(431, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Monday', 'Laboratory', 1, 'Scheduled', 'Civil Engineering', 2),
(432, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Thursday', 'Laboratory', 1, 'Scheduled', 'Civil Engineering', 2),
(433, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Dr. Alan Turing', '09:00:00', '10:30:00', 'Monday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(434, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Dr. Alan Turing', '09:00:00', '10:30:00', 'Wednesday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(435, 'ENG202', 'English Literature', 'BSCE 1-1', 'Prof. Jane Austen', '11:00:00', '12:30:00', 'Tuesday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(436, 'ENG202', 'English Literature', 'BSCE 1-1', 'Prof. Jane Austen', '11:00:00', '12:30:00', 'Thursday', 'Lecture', 1, 'Scheduled', 'Civil Engineering', 2),
(437, 'SCI303', 'Physics', 'BSCE 1-2', 'Dr. Albert Einstein', '14:00:00', '15:30:00', 'Monday', 'Laboratory', 1, 'Scheduled', 'Civil Engineering', 2),
(438, 'SCI303', 'Physics', 'BSCE 1-2', 'Dr. Albert Einstein', '14:00:00', '15:30:00', 'Thursday', 'Laboratory', 1, 'Scheduled', 'Civil Engineering', 2);

-- --------------------------------------------------------

--
-- Table structure for table `terms_tbl`
--

CREATE TABLE `terms_tbl` (
  `term_id` int(11) NOT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `term_status` varchar(20) NOT NULL DEFAULT 'Current'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_tbl`
--

INSERT INTO `terms_tbl` (`term_id`, `academic_year`, `semester`, `term_status`) VALUES
(1, '2024-2025', '1st Semester', 'Current');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `userRole` varchar(50) DEFAULT NULL,
  `userPassword` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `contact_number`, `department`, `userRole`, `userPassword`) VALUES
(12, 'Ronnie', 'Carreon', 'ronniecarreon30@gmail.com', '0123456789', 'N/A', 'Admin', '$2y$10$zpzhBmImZMw4T1G6y09.4uVs9el6n59xfQ4kMH6J8RU98tA/5Gm5O'),
(17, 'John', 'Mayer', 'tce@email.com', '01234579', 'Civil Engineering', 'Dept. Head', '$2y$10$C0J9fsk.q9sjU5IxCsUgYOnaPMkJYVOOV05DfhSEY0T3cqvTVUtHW'),
(18, 'Jane', 'Doe', 'stud@email.com', '0123456789', 'Civil Engineering', 'Student Rep', '$2y$10$gvSMe1SalqUDTnv6Zvx2NeV5qS6VTL1ZBEyqCvOatRsp.SykkVduW'),
(19, 'Aida', 'Bugg', 'gso@email.com', '0123456789', 'N/A', 'Facility Head', '$2y$10$.El9UgRRXVZBnpc9InQ00.DwORuCAFraNYSebnJING8iLFc7YQ8dy'),
(20, 'Agape', 'Balbon', 'ageypp@email.com', '0123012', 'N/A', 'Registrar', '$2y$10$JqXEgNNMWCMeBDYfcEO7juFZw7FcN3paoi8se/dgoZaspa.T6Dfce'),
(25, 'Juan', 'Dela Cruz', 'jdc@email.com', '0123456789', 'Information Technology', 'Facility Head', '$2y$10$kw93M5x0kqyhIQxERnfCrujv1JTnEJkuP/EHHseDLz5CSfom.Y52u');

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
-- Indexes for table `dept_tbl`
--
ALTER TABLE `dept_tbl`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `schedules_tbl`
--
ALTER TABLE `schedules_tbl`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `fk_pref_dept` (`pref_dept`);

--
-- Indexes for table `terms_tbl`
--
ALTER TABLE `terms_tbl`
  ADD PRIMARY KEY (`term_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_rooms_tbl`
--
ALTER TABLE `assigned_rooms_tbl`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `schedules_tbl`
--
ALTER TABLE `schedules_tbl`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=439;

--
-- AUTO_INCREMENT for table `terms_tbl`
--
ALTER TABLE `terms_tbl`
  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_rooms_tbl`
--
ALTER TABLE `assigned_rooms_tbl`
  ADD CONSTRAINT `fk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `fk_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules_tbl` (`schedule_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`);

--
-- Constraints for table `schedules_tbl`
--
ALTER TABLE `schedules_tbl`
  ADD CONSTRAINT `fk_pref_dept` FOREIGN KEY (`pref_dept`) REFERENCES `dept_tbl` (`dept_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `schedules_tbl_ibfk_1` FOREIGN KEY (`term_id`) REFERENCES `terms_tbl` (`term_id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_reservation_status` ON SCHEDULE EVERY 1 HOUR STARTS '2024-10-07 19:58:42' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE reservations
    SET reservation_status = 'Expired' 
    WHERE reservation_date < CURDATE() 
      AND reservation_status <> 'Expired'$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
