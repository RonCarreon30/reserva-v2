-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2024 at 11:29 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `dept_tbl`
--

CREATE TABLE `dept_tbl` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `building_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dept_tbl`
--

INSERT INTO `dept_tbl` (`dept_id`, `dept_name`, `building_id`) VALUES
(1, 'Information Technology', 3),
(2, 'Civil Engineering', 3),
(3, 'Accountancy', 4),
(4, 'Business Administration', 4),
(5, 'N/A', 5);

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `building` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `descri` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `facility_name`, `building`, `status`, `descri`) VALUES
(9, 'Assembly Hall', 'COED', 'Available', 'Located at College of Education Building'),
(10, 'Auditorium', 'SC/MAIN', 'Available', 'Located at the Main building'),
(11, 'Collab', 'CEIT', 'Available', 'Located at 3rd floor of CEIT Building'),
(19, 'Collab 1', 'CEIT', 'Available', 'Located at the 3rd floor of CEIT\r\n'),
(20, 'Multi-purpose Hall', 'CEIT', 'Available', 'MPH is located at the 6th floor of CEIT Building'),
(21, 'Collab 2', 'CEIT', 'Available', 'CEIT Collab room at 3rd Floor'),
(22, 'Collab 3', 'CEIT', 'Available', 'Located at 3rd foor of CEIT building'),
(26, 'Business Office Simulation Room', 'CABA', 'Available', 'Located at CABA Building Ground Floor'),
(27, 'Pre-school Simulation', 'CABA', 'Available', 'Located at CABA Building Ground Floor'),
(28, 'Collaboration Room A', 'CABA', 'Available', 'Located at CABA Building Third Floor'),
(29, 'Collaboration Room B', 'CABA', 'Available', 'Located at CABA Building Third Floor'),
(30, 'Collaboration Room C', 'CABA', 'Available', 'Located at CABA Building Third Floor'),
(31, 'Training Room', 'CABA', 'Available', 'Located at CABA Building Sixth Floor'),
(32, 'MPH 6A', 'CABA', 'Available', 'Multi-purpose Hall Located at CABA Building Sixth Floor'),
(33, 'MPH 6B', 'CABA', 'Available', 'Multi-purpose Hall Located at CABA Building Sixth Floor'),
(34, 'Consultation Room', 'CEIT', 'Available', 'Located at CEIT Third Floor'),
(35, 'Engineering Gallery', 'CEIT', 'Available', 'Located at CEIT Third Floor'),
(36, 'Collaboration Room 1', 'CEIT', 'Available', 'Located at CEIT Third Floor'),
(37, 'Collaboration Room 2', 'CEIT', 'Available', 'Located at CEIT Third Floor\r\n\r\n'),
(38, 'Collaboration Room 3', 'CEIT', 'Available', 'Located at CEIT Third Floor'),
(39, 'Collaboration Room 4', 'CEIT', 'Available', 'Located at CEIT Third Floor'),
(40, 'Training Room', 'CEIT', 'Available', 'Located at CEIT Sixth Floor'),
(41, 'MPH 6C', 'CEIT', 'Available', 'Multi-purpose Hall Located at CEIT Sixth Floor'),
(42, 'MPH 6D', 'CEIT', 'Available', 'Multi-purpose Hall Located at CEIT Building Sixth Floor');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `rooms_tbl`
--

CREATE TABLE `rooms_tbl` (
  `room_id` int(11) NOT NULL,
  `building_id` int(11) DEFAULT NULL,
  `room_name` varchar(50) NOT NULL,
  `room_type` enum('Lecture','Laboratory') NOT NULL,
  `room_status` enum('Available','Unavailable') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms_tbl`
--

INSERT INTO `rooms_tbl` (`room_id`, `building_id`, `room_name`, `room_type`, `room_status`, `created_at`) VALUES
(1, 3, 'Mechanical Engineering Laboratory', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(2, 3, 'Materials Testing Laboratory', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(4, 3, 'Electrical Engineering Laboratory', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(5, 3, 'Electronics Engineering Laboratory', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(6, 3, 'Fluid Mechanics Laboratory', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(7, 3, 'CL 201', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(8, 3, 'CL 202', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(9, 3, 'CL 203', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(10, 3, 'CL 204', 'Laboratory', 'Available', '2024-10-25 07:16:26'),
(11, 3, 'DR 201', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(12, 3, 'DR 202', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(13, 3, 'CEIT 501', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(14, 3, 'CEIT 502', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(15, 3, 'CEIT 503', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(16, 3, 'CEIT 504', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(17, 3, 'CEIT 505', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(18, 3, 'CEIT 506', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(19, 3, 'CEIT 507', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(20, 3, 'CEIT 508', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(21, 3, 'CEIT 509', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(22, 3, 'CEIT 510', 'Lecture', 'Available', '2024-10-25 07:16:26'),
(23, 4, 'CABA 101', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(24, 4, 'CABA 102', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(25, 4, 'CABA 103', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(26, 4, 'CABA 104', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(27, 4, 'Graduate Studies Room', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(28, 4, 'Physics Laboratory', 'Laboratory', 'Available', '2024-10-25 07:22:46'),
(29, 4, 'Chemistry Laboratory', 'Laboratory', 'Available', '2024-10-25 07:22:46'),
(30, 4, 'Biology Laboratory', 'Laboratory', 'Available', '2024-10-25 07:22:46'),
(31, 4, 'CABA 207', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(32, 4, 'COMLAB 3A', 'Laboratory', 'Available', '2024-10-25 07:22:46'),
(33, 4, 'Lecture Room 3A', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(34, 4, 'Lecture Room 3B', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(35, 4, 'CABA 401', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(36, 4, 'CABA 402', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(37, 4, 'CABA 403', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(38, 4, 'CABA 404', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(39, 4, 'CABA 405', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(40, 4, 'CABA 406', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(41, 4, 'CABA 407', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(42, 4, 'CABA 501', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(43, 4, 'CABA 502', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(44, 4, 'CABA 503', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(45, 4, 'CABA 504', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(46, 4, 'CABA 505', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(47, 4, 'CABA 506', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(48, 4, 'CABA 507', 'Lecture', 'Available', '2024-10-25 07:22:46'),
(49, 4, 'CABA 508', 'Lecture', 'Available', '2024-10-25 07:22:46');

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
-- Dumping data for table `room_assignments_tbl`
--

INSERT INTO `room_assignments_tbl` (`assignment_id`, `room_id`, `schedule_id`, `assigned_date`) VALUES
(109, 11, 157, '2024-10-27 13:24:26'),
(110, 11, 158, '2024-10-27 13:24:26'),
(111, 11, 159, '2024-10-27 13:24:26'),
(112, 11, 160, '2024-10-27 13:24:26'),
(113, 1, 161, '2024-10-27 13:24:26'),
(114, 1, 162, '2024-10-27 13:24:26'),
(115, 12, 163, '2024-10-27 13:25:04'),
(116, 12, 164, '2024-10-27 13:25:04'),
(117, 12, 165, '2024-10-27 13:25:04'),
(118, 12, 166, '2024-10-27 13:25:04'),
(119, 2, 167, '2024-10-27 13:25:04'),
(120, 2, 168, '2024-10-27 13:25:04'),
(121, 11, 169, '2024-10-27 13:31:55'),
(122, 11, 170, '2024-10-27 13:31:55'),
(123, 11, 171, '2024-10-27 13:31:55'),
(124, 11, 172, '2024-10-27 13:31:55'),
(125, 1, 173, '2024-10-27 13:31:55'),
(126, 1, 174, '2024-10-27 13:31:55');

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
  `sched_status` enum('pending','assigned','conflicted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `user_id`, `subject_code`, `subject`, `section`, `instructor`, `start_time`, `end_time`, `days`, `class_type`, `ay_semester`, `department_id`, `sched_status`, `created_at`, `updated_at`) VALUES
(157, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Dr. Alan Turing', '09:00:00', '10:30:00', 'Monday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(158, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Dr. Alan Turing', '09:00:00', '10:30:00', 'Wednesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(159, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Prof. Jane Austen', '11:00:00', '12:30:00', 'Tuesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(160, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Prof. Jane Austen', '11:00:00', '12:30:00', 'Thursday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(161, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Dr. Albert Einstein', '14:00:00', '15:30:00', 'Monday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(162, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Dr. Albert Einstein', '14:00:00', '15:30:00', 'Thursday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:24:26', '2024-10-27 13:24:26'),
(163, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Monday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(164, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '09:00:00', '10:30:00', 'Wednesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(165, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Tuesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(166, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '11:00:00', '12:30:00', 'Thursday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(167, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Monday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(168, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '14:00:00', '15:30:00', 'Thursday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:25:04', '2024-10-27 13:25:04'),
(169, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '11:00:00', '14:00:00', 'Monday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55'),
(170, 12, 'MATH101', 'Calculus I', 'BSCE 1-1', 'Teacher A', '11:00:00', '14:00:00', 'Wednesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55'),
(171, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '13:00:00', '16:00:00', 'Tuesday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55'),
(172, 12, 'ENG202', 'English Literature', 'BSCE 1-1', 'Teacher A', '13:00:00', '16:00:00', 'Thursday', 'Lecture', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55'),
(173, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '16:00:00', '19:00:00', 'Monday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55'),
(174, 12, 'SCI303', 'Physics', 'BSCE 1-2', 'Teacher B', '16:00:00', '19:00:00', 'Thursday', 'Laboratory', 1, 1, 'assigned', '2024-10-27 13:31:55', '2024-10-27 13:31:55');

-- --------------------------------------------------------

--
-- Table structure for table `terms_tbl`
--

CREATE TABLE `terms_tbl` (
  `term_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `term_status` enum('Current','Upcoming','Expired') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_tbl`
--

INSERT INTO `terms_tbl` (`term_id`, `academic_year`, `semester`, `term_status`) VALUES
(1, '2024-2025', '1st Semester', 'Current'),
(2, '2024-2025', '2nd Semester', 'Upcoming'),
(3, '2024-2025', 'Summer', 'Upcoming');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `id_number` varchar(20) NOT NULL,
  `userRole` varchar(50) DEFAULT NULL,
  `userPassword` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `department_id`, `id_number`, `userRole`, `userPassword`) VALUES
(12, 'Ronnie', 'Carreon', 'ronniecarreon30@gmail.com', 5, '21-0826', 'Admin', '$2y$10$zpzhBmImZMw4T1G6y09.4uVs9el6n59xfQ4kMH6J8RU98tA/5Gm5O'),
(28, 'IT', 'Student', 'student.IT@email.com', 1, '5', 'Student Rep', '$2y$10$jzZ1B3LgQvmWAUbnYBBuc.0Lw9JL8WWujtw9N1TFlFXvKMUVBjoge'),
(31, 'IT', 'Head', 'head.IT@email.com', 1, '6', 'Dept. Head', '$2y$10$e7VAarZpK.kDwkumj8WYo.AC/V4leMSXeTYre2G3ve2rqX2XgG1HW'),
(32, 'CE', 'Student', 'student.CE@email.com', 2, '7', 'Student Rep', '$2y$10$bhKuejIAfLXySXeIYyXiYuxHKtj.Ptl8ZXM0a5jqGdPQ.sprm0Fx2'),
(34, 'CE', 'Head', 'head.CE@email.com', 2, '8', 'Dept. Head', '$2y$10$/r0NrJd8FcEvrpnLeM0YuumkSXEGkwa1QyRoaZ07NWrvsfp3S9YjS'),
(37, 'Ashley', 'Alejandro', 'ashley.admin@email.com', 5, '21-0001', 'Admin', '$2y$10$vvYtxr0dIRhB.33cVGU.huh1LR47Dv6GouCLjhJ2PVmq6.QnYzKw2'),
(38, 'Marianne', 'Macalino', 'ianne.admin@email.com', 5, '21-0002', 'Admin', '$2y$10$LQNIJehwPHAyEYnOInm0UehwQ7z9DK4WlMvZrujJHBh9Ea83XU6R2'),
(39, 'Mikaela', 'Garcia', 'mikaela.admin@email.com', 5, '21-0003', 'Admin', '$2y$10$1/UBVhjpMbmb9TEjshOqFuOVCXMU1sQfFoWyPI9oL/Tyda0EavNmK'),
(40, 'admin', 'admin', 'registrar@email.com', 5, '00-0000', 'Registrar', '$2y$10$1qgLWComqh1.9rzcKTDmn.72/DN4IKeh6SwgK6D175q2Vze7H13d2'),
(41, 'Facility', 'Head', 'gso@email.com', 5, '00-0001', 'Facility Head', '$2y$10$v8kn.igvnk165iSG4rLKfO9j2QPq4E/myqc45eF.zvl8T8fuCk5G6');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buildings_tbl`
--
ALTER TABLE `buildings_tbl`
  ADD PRIMARY KEY (`building_id`);

--
-- Indexes for table `dept_tbl`
--
ALTER TABLE `dept_tbl`
  ADD PRIMARY KEY (`dept_id`),
  ADD KEY `fk_building` (`building_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`);

--
-- Indexes for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `schedule_id` (`schedule_id`);

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
-- Indexes for table `terms_tbl`
--
ALTER TABLE `terms_tbl`
  ADD PRIMARY KEY (`term_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `fk_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buildings_tbl`
--
ALTER TABLE `buildings_tbl`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dept_tbl`
--
ALTER TABLE `dept_tbl`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `terms_tbl`
--
ALTER TABLE `terms_tbl`
  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dept_tbl`
--
ALTER TABLE `dept_tbl`
  ADD CONSTRAINT `fk_building` FOREIGN KEY (`building_id`) REFERENCES `buildings_tbl` (`building_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`facility_id`);

--
-- Constraints for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD CONSTRAINT `rooms_tbl_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings_tbl` (`building_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_assignments_tbl`
--
ALTER TABLE `room_assignments_tbl`
  ADD CONSTRAINT `room_assignments_tbl_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms_tbl` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_assignments_tbl_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_ay_semester` FOREIGN KEY (`ay_semester`) REFERENCES `terms_tbl` (`term_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_department_id` FOREIGN KEY (`department_id`) REFERENCES `dept_tbl` (`dept_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `dept_tbl` (`dept_id`) ON DELETE SET NULL ON UPDATE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_reservation_status` ON SCHEDULE EVERY 1 MINUTE STARTS '2024-10-07 19:58:42' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE reservations
    SET reservation_status = 'Expired' 
    WHERE reservation_date < CURDATE() 
      AND reservation_status <> 'Expired'$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
