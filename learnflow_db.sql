-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 05:35 PM
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
-- Database: `company_db`
--
CREATE DATABASE IF NOT EXISTS `company_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company_db`;
--
-- Database: `company_dblcd`
--
CREATE DATABASE IF NOT EXISTS `company_dblcd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company_dblcd`;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`) VALUES
(1, 'HR'),
(2, 'IT'),
(3, 'SALES'),
(4, 'Marketing');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_name`, `dept_id`, `hire_date`) VALUES
(2, 'Bob Johnson', 1, '2019-11-10'),
(3, 'Charlie Brown', 2, '2021-03-22'),
(4, 'Daisy Lee', 3, '2018-05-05'),
(5, 'Edward Harres', 4, '2021-03-22'),
(6, 'Lemuel Duran', 2, '2026-04-20'),
(7, 'Lemuel Duran', 2, '2026-04-20'),
(8, 'Lee Du', 2, '2026-04-20'),
(9, 'Nics Aba', 3, '2026-04-20'),
(10, 'Lemuel Duran', 2, '2026-04-20'),
(11, 'Juan Dela Cruz', 1, '2026-02-12'),
(12, 'Maria Santos', 2, '2026-02-12'),
(13, 'Pedro Reyes', 1, '2026-02-12'),
(14, 'Anna Garcia', 2, '2026-02-12'),
(15, 'Else Garcia', 2, '2026-02-12'),
(16, 'Nora Garcia', 3, '2026-02-12');

--
-- Triggers `employees`
--
DELIMITER $$
CREATE TRIGGER `after_department_update` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
IF OLD.dept_id <> NEW.dept_id THEN
UPDATE salaries SET salary = salary + 1000 WHERE employee_id = NEW.employee_id;
END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_employee_insert` AFTER INSERT ON `employees` FOR EACH ROW BEGIN
INSERT INTO salaries (employee_id, salary) VALUES (NEW.employee_id, 20000);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `employee_id` int(11) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`employee_id`, `salary`) VALUES
(2, 77000.00),
(3, 35000.00),
(4, 60000.00),
(5, 10000.00),
(9, 100000.00),
(11, 30000.00),
(12, 28000.00),
(13, 25000.00),
(14, 20000.00),
(15, 20000.00),
(16, 20000.00);

--
-- Triggers `salaries`
--
DELIMITER $$
CREATE TRIGGER `before_salary_update` BEFORE UPDATE ON `salaries` FOR EACH ROW BEGIN
IF NEW.salary < 15000 THEN
SET NEW.salary = OLD.salary;
END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
--
-- Database: `company_db_duran`
--
CREATE DATABASE IF NOT EXISTS `company_db_duran` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company_db_duran`;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);
--
-- Database: `company_db_lcd`
--
CREATE DATABASE IF NOT EXISTS `company_db_lcd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company_db_lcd`;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`) VALUES
(1, 'HR'),
(2, 'IT'),
(3, 'SALES'),
(4, 'Marketing');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `dept_id`, `hire_date`) VALUES
(2, 'Bob', 'Johnson', 2, '2019-11-10'),
(3, 'Charlie', 'Brown', 2, '2021-03-22'),
(4, 'Daisy', 'Lee', 3, '2018-05-05'),
(5, 'Edward', 'Harres', 4, '2021-03-22'),
(6, 'lemuel', 'duran', 2, '2026-04-20'),
(7, 'lemuel', 'duran', 2, '2026-04-20'),
(8, 'lee', 'du', 2, '2026-04-20'),
(9, 'nics', 'aba', 3, '2026-04-20'),
(10, 'lemuel', 'duran', 2, '2026-04-20');

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `employee_id` int(11) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`employee_id`, `salary`) VALUES
(2, 75000.00),
(3, 68000.00),
(4, 60000.00),
(5, 10000.00),
(9, 100000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
--
-- Database: `company_lab`
--
CREATE DATABASE IF NOT EXISTS `company_lab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company_lab`;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`) VALUES
(1, 'HR'),
(2, 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` int(11) NOT NULL,
  `emp_name` varchar(50) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp_id`, `emp_name`, `dept_id`, `salary`) VALUES
(101, 'Alice', 1, 15000.00),
(102, 'Bob', 2, 28000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`);
--
-- Database: `debugmaster`
--
CREATE DATABASE IF NOT EXISTS `debugmaster` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `debugmaster`;
--
-- Database: `debugmaster_db`
--
CREATE DATABASE IF NOT EXISTS `debugmaster_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `debugmaster_db`;

-- --------------------------------------------------------

--
-- Table structure for table `session_history`
--

CREATE TABLE `session_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `language` enum('PHP','Java','C++') DEFAULT 'PHP',
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Easy',
  `correct_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wrong_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_questions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `accuracy` decimal(5,2) NOT NULL DEFAULT 0.00,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `hints_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `best_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `played_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_history`
--

INSERT INTO `session_history` (`id`, `username`, `language`, `difficulty`, `correct_answers`, `wrong_answers`, `total_questions`, `accuracy`, `points_earned`, `hints_used`, `best_streak`, `duration_seconds`, `played_at`) VALUES
(19, 'lem', 'PHP', 'Medium', 0, 0, 0, 0.00, 0, 0, 0, 21, '2026-03-22 21:37:40'),
(20, 'guest', 'PHP', 'Easy', 0, 0, 0, 0.00, 0, 0, 10, 16, '2026-03-22 21:41:58'),
(21, 'guest', 'PHP', 'Medium', 1, 0, 1, 100.00, 10, 0, 1, 36, '2026-03-22 21:47:18'),
(22, 'guest', 'PHP', 'Easy', 2, 0, 2, 100.00, 20, 0, 2, 11, '2026-03-22 21:47:37'),
(23, 'guest', 'PHP', 'Easy', 8, 0, 8, 100.00, 80, 0, 8, 50, '2026-03-22 21:48:52'),
(24, 'guest', 'PHP', 'Easy', 0, 0, 0, 0.00, 0, 0, 8, 8, '2026-03-22 21:49:36');

-- --------------------------------------------------------

--
-- Table structure for table `unanswered_questions`
--

CREATE TABLE `unanswered_questions` (
  `id` int(11) NOT NULL,
  `username` varchar(64) DEFAULT NULL,
  `language` varchar(32) DEFAULT NULL,
  `difficulty` varchar(32) DEFAULT NULL,
  `question_index` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unanswered_questions`
--

INSERT INTO `unanswered_questions` (`id`, `username`, `language`, `difficulty`, `question_index`, `created_at`) VALUES
(416, 'guest', 'PHP', 'Medium', 7, '2026-03-22 13:47:18'),
(417, 'guest', 'PHP', 'Medium', 6, '2026-03-22 13:47:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 150,
  `correct_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wrong_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `best_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_answered` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `games_played` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_language` enum('PHP','Java','C++') DEFAULT 'PHP',
  `last_difficulty` enum('Easy','Medium','Hard') DEFAULT 'Easy',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_stats`
--

INSERT INTO `user_stats` (`id`, `username`, `points`, `correct_answers`, `wrong_answers`, `best_streak`, `total_answered`, `games_played`, `last_language`, `last_difficulty`, `created_at`, `updated_at`) VALUES
(1, 'guest', 260, 22, 0, 8, 22, 14, 'PHP', 'Easy', '2026-03-22 15:54:20', '2026-03-22 21:49:36'),
(15, 'lem', 150, 0, 0, 0, 0, 1, 'PHP', 'Medium', '2026-03-22 20:43:53', '2026-03-22 21:37:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_leaderboard`
-- (See below for the actual view)
--
CREATE TABLE `vw_leaderboard` (
`rank` bigint(21)
,`username` varchar(80)
,`points` int(11)
,`correct_answers` int(10) unsigned
,`wrong_answers` int(10) unsigned
,`best_streak` int(10) unsigned
,`total_answered` int(10) unsigned
,`games_played` int(10) unsigned
,`last_language` enum('PHP','Java','C++')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_accuracy`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_accuracy` (
`username` varchar(80)
,`total_questions_lifetime` decimal(32,0)
,`total_correct_lifetime` decimal(32,0)
,`lifetime_accuracy_pct` decimal(38,2)
,`all_time_best_streak` int(10) unsigned
,`sessions_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_leaderboard`
--
DROP TABLE IF EXISTS `vw_leaderboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_leaderboard`  AS SELECT rank() over ( order by `user_stats`.`points` desc,`user_stats`.`correct_answers` desc) AS `rank`, `user_stats`.`username` AS `username`, `user_stats`.`points` AS `points`, `user_stats`.`correct_answers` AS `correct_answers`, `user_stats`.`wrong_answers` AS `wrong_answers`, `user_stats`.`best_streak` AS `best_streak`, `user_stats`.`total_answered` AS `total_answered`, `user_stats`.`games_played` AS `games_played`, `user_stats`.`last_language` AS `last_language` FROM `user_stats` ORDER BY `user_stats`.`points` DESC LIMIT 0, 10 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_accuracy`
--
DROP TABLE IF EXISTS `vw_user_accuracy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_accuracy`  AS SELECT `session_history`.`username` AS `username`, sum(`session_history`.`total_questions`) AS `total_questions_lifetime`, sum(`session_history`.`correct_answers`) AS `total_correct_lifetime`, round(sum(`session_history`.`correct_answers`) / nullif(sum(`session_history`.`total_questions`),0) * 100,2) AS `lifetime_accuracy_pct`, max(`session_history`.`best_streak`) AS `all_time_best_streak`, count(0) AS `sessions_count` FROM `session_history` GROUP BY `session_history`.`username` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `session_history`
--
ALTER TABLE `session_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sh_user` (`username`),
  ADD KEY `idx_sh_played_at` (`played_at`);

--
-- Indexes for table `unanswered_questions`
--
ALTER TABLE `unanswered_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_unanswered` (`username`,`language`,`difficulty`,`question_index`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`username`),
  ADD KEY `idx_points` (`points`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `session_history`
--
ALTER TABLE `session_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `unanswered_questions`
--
ALTER TABLE `unanswered_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=475;

--
-- AUTO_INCREMENT for table `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `session_history`
--
ALTER TABLE `session_history`
  ADD CONSTRAINT `fk_sh_user` FOREIGN KEY (`username`) REFERENCES `user_stats` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `home_base_db_lcd`
--
CREATE DATABASE IF NOT EXISTS `home_base_db_lcd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `home_base_db_lcd`;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `balance` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `balance`) VALUES
(1, 'Student', 800);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);
--
-- Database: `hospital_lab`
--
CREATE DATABASE IF NOT EXISTS `hospital_lab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hospital_lab`;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `fee`, `status`) VALUES
(1, 1, 1, '2026-03-20', 1500.00, 'Completed'),
(2, 2, 2, '2026-03-21', 1000.00, 'Completed'),
(3, 3, 3, '2026-03-22', 1200.00, 'Pending'),
(4, 1, 2, '2026-03-01', 1500.00, 'Completed'),
(5, 2, 3, '2026-04-15', 2500.00, 'Pending'),
(6, 1, 1, '2026-03-20', 1500.00, 'Completed'),
(7, 2, 2, '2026-03-21', 1000.00, 'Completed'),
(8, 3, 3, '2026-03-22', 1200.00, 'Completed'),
(9, 1, 2, '2026-03-01', 1500.00, 'Completed'),
(10, 4, 3, '2020-03-24', 2000.00, 'Completed'),
(11, 8, 3, '2026-08-16', 200.00, 'pending');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `appointment_log` AFTER UPDATE ON `appointments` FOR EACH ROW begin 
insert into appointment_log () 
values (new.appointment_id, new.patient_id, new.doctor_id, new.appointment_date, new.fee, new.status);
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `default_status` BEFORE INSERT ON `appointments` FOR EACH ROW begin
if new.status is null then 
set new.status = 'pending';
end if;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `log_new_appointment` AFTER INSERT ON `appointments` FOR EACH ROW begin
insert into appointment_log (appointment_id, patient_id, doctor_id, appointment_date, fee, status)
VALUES (NEW.appointment_id, NEW.patient_id, NEW.doctor_id, NEW.appointment_date, NEW.fee, NEW.status);
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `min_discount` BEFORE UPDATE ON `appointments` FOR EACH ROW begin
if new.fee < 300 then
set new.status = 300;
end if;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_status` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
IF NEW.appointment_date < CURDATE() THEN
SET NEW.status = 'Completed';
END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_log`
--

CREATE TABLE `appointment_log` (
  `log_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_log`
--

INSERT INTO `appointment_log` (`log_id`, `appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `fee`, `status`) VALUES
(1, 5, 2, 3, '2026-04-15', 2500.00, 'Pending'),
(2, 6, 1, 1, '2026-03-20', 1500.00, 'Completed'),
(3, 7, 2, 2, '2026-03-21', 1000.00, 'Completed'),
(4, 8, 3, 3, '2026-03-22', 1200.00, 'Completed'),
(5, 9, 1, 2, '2026-03-01', 1500.00, 'Completed'),
(6, 10, 4, 3, '2020-03-24', 2000.00, 'Completed'),
(7, 11, 8, 3, '2026-08-16', 200.00, 'pending');

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointment_view`
-- (See below for the actual view)
--
CREATE TABLE `appointment_view` (
`patient_name` varchar(100)
,`doctor_name` varchar(100)
,`specialization` varchar(100)
,`appointment_date` date
,`fee` decimal(10,2)
,`status` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `cardiology_schedule`
-- (See below for the actual view)
--
CREATE TABLE `cardiology_schedule` (
`patient_name` varchar(100)
,`appointment_date` date
,`status` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `name`, `specialization`) VALUES
(1, 'Dr. Cruz', 'Cardiology'),
(2, 'Dr. Lim', 'Pediatrics'),
(3, 'Dr. Santos', 'General Medicine'),
(4, 'Dr. Cruz', 'Cardiology'),
(5, 'Dr. Lim', 'Pediatrics'),
(6, 'Dr. Santos', 'General Medicine');

-- --------------------------------------------------------

--
-- Stand-in structure for view `high_revenue`
-- (See below for the actual view)
--
CREATE TABLE `high_revenue` (
`patient_name` varchar(100)
,`doctor_name` varchar(100)
,`appointment_date` date
,`fee` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `name`, `age`, `gender`) VALUES
(1, 'Juan Dela Cruz', 30, 'Male'),
(2, 'Maria Santos', 25, 'Female'),
(3, 'Pedro Reyes', 40, 'Male'),
(4, 'Juan Dela Cruz', NULL, NULL),
(5, 'Maria Santos', NULL, NULL),
(6, 'Pedro Reyes', NULL, NULL),
(7, 'nathan', NULL, NULL),
(8, 'lem', 21, 'male');

-- --------------------------------------------------------

--
-- Stand-in structure for view `pediatrics_sched`
-- (See below for the actual view)
--
CREATE TABLE `pediatrics_sched` (
`name` varchar(100)
,`appointment_date` date
,`status` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_appointments`
-- (See below for the actual view)
--
CREATE TABLE `pending_appointments` (
`patient_name` varchar(100)
,`doctors_name` varchar(100)
,`appointment_date` date
,`fee` decimal(10,2)
,`status` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vip_completed_services`
-- (See below for the actual view)
--
CREATE TABLE `vip_completed_services` (
`patient_name` varchar(100)
,`doctor_name` varchar(100)
,`specialization` varchar(100)
,`fee` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Structure for view `appointment_view`
--
DROP TABLE IF EXISTS `appointment_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_view`  AS SELECT `p`.`name` AS `patient_name`, `d`.`name` AS `doctor_name`, `d`.`specialization` AS `specialization`, `a`.`appointment_date` AS `appointment_date`, `a`.`fee` AS `fee`, `a`.`status` AS `status` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `cardiology_schedule`
--
DROP TABLE IF EXISTS `cardiology_schedule`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cardiology_schedule`  AS SELECT `p`.`name` AS `patient_name`, `a`.`appointment_date` AS `appointment_date`, `a`.`status` AS `status` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `d`.`specialization` = 'cardiology' ;

-- --------------------------------------------------------

--
-- Structure for view `high_revenue`
--
DROP TABLE IF EXISTS `high_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `high_revenue`  AS SELECT `p`.`name` AS `patient_name`, `d`.`name` AS `doctor_name`, `a`.`appointment_date` AS `appointment_date`, `a`.`fee` AS `fee` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `a`.`fee` > 1000.00 ;

-- --------------------------------------------------------

--
-- Structure for view `pediatrics_sched`
--
DROP TABLE IF EXISTS `pediatrics_sched`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pediatrics_sched`  AS SELECT `p`.`name` AS `name`, `a`.`appointment_date` AS `appointment_date`, `a`.`status` AS `status` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `d`.`specialization` = 'pediatrics' ;

-- --------------------------------------------------------

--
-- Structure for view `pending_appointments`
--
DROP TABLE IF EXISTS `pending_appointments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_appointments`  AS SELECT `p`.`name` AS `patient_name`, `d`.`name` AS `doctors_name`, `a`.`appointment_date` AS `appointment_date`, `a`.`fee` AS `fee`, `a`.`status` AS `status` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `a`.`status` = 'Pending' ;

-- --------------------------------------------------------

--
-- Structure for view `vip_completed_services`
--
DROP TABLE IF EXISTS `vip_completed_services`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vip_completed_services`  AS SELECT `p`.`name` AS `patient_name`, `d`.`name` AS `doctor_name`, `d`.`specialization` AS `specialization`, `a`.`fee` AS `fee` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `a`.`fee` > 1200.00 AND `a`.`status` = 'completed' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `appointment_log`
--
ALTER TABLE `appointment_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `appointment_log`
--
ALTER TABLE `appointment_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);
--
-- Database: `learnflow_db`
--
CREATE DATABASE IF NOT EXISTS `learnflow_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `learnflow_db`;

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `id` int(10) UNSIGNED NOT NULL,
  `label` varchar(80) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_terms`
--

INSERT INTO `academic_terms` (`id`, `label`, `academic_year`, `semester`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, '1st Semester AY 2025-2026', '2025-2026', '1st', '2025-08-01', '2025-12-31', 0, '2026-05-07 09:04:06'),
(2, '2nd Semester AY 2025-2026', '2025-2026', '2nd', '2026-01-05', '2026-05-31', 1, '2026-05-07 09:04:06'),
(3, 'Summer Term AY 2025-2026', '2025-2026', 'Summer', '2026-06-02', '2026-07-25', 0, '2026-05-07 01:04:06'),
(4, '1st Semester AY 2024-2025', '2024-2025', '1st', '2024-08-05', '2024-12-20', 0, '2024-08-01 00:00:00'),
(5, '2nd Semester AY 2024-2025', '2024-2025', '2nd', '2025-01-06', '2025-05-30', 0, '2025-01-03 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `scope` enum('platform','department','section') NOT NULL,
  `scope_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` longtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `author_id`, `scope`, `scope_id`, `title`, `body`, `is_pinned`, `published_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 36, 'section', 1, 'Lab 5 Posted - Exception Handling', 'Lab Activity 5 on Exception Handling has been posted. Please submit via the Assignments tab before May 10. Review Chapter 9 of the textbook as preparation.', 1, '2026-05-01 08:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(2, 36, 'section', 1, 'Midterm Exam Coverage', 'The midterm will cover Chapters 1-6: Classes, Inheritance, Polymorphism, Interfaces, and Abstract Classes. Bring your student ID and a laptop for the coding portion.', 0, '2026-05-04 09:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(3, 36, 'section', 2, 'Project 2 Requirements Updated', 'The requirements for Project 2 (Responsive Portfolio Site) have been updated. Check the Assignments section for the revised rubric. Deadline remains April 28.', 1, '2026-05-05 10:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(4, 36, 'section', 3, 'Extra Credit Opportunity', 'Students who submit a bonus analysis of sorting algorithm complexities (Big-O) will receive 5 extra points on the finals. Submit by May 15.', 0, '2026-05-04 11:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(5, 36, 'section', 1, 'OOP Lab Quiz 2 Grades Released', 'Grades for Lab Quiz 2 are now available. Average score was 83%. Please review the feedback in your submission portal.', 0, '2026-04-09 08:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(6, 36, 'section', 2, 'Web Quiz 3 Results Available', 'Results for Web Programming Quiz 3 have been released. Class average was 81%. Well done! Students below 75 please see me during consultation hours.', 0, '2026-04-21 09:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(7, 36, 'section', 3, 'Midterm Reminder - Sorting Quiz Live', 'The Sorting and Searching Quiz is now live and available until May 14. You have 20 minutes to complete it. Good luck!', 1, '2026-05-07 07:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(8, 36, 'section', 4, 'Capstone Proposal Submission Reminder', 'Reminder: Capstone Proposal drafts are due May 5. Feedback will be given within 5 working days. Please follow the document template provided.', 0, '2026-04-30 10:00:00', NULL, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(9, 36, 'section', 1, 'Lab Activity 6 Now Available', 'Lab Activity 6 on Java Collections Framework has been posted. Please review the ArrayList and HashMap documentation before starting. Due May 24.', 1, '2026-05-12 08:00:00', NULL, '2026-05-12 00:00:00', '2026-05-12 00:00:00'),
(10, 36, 'section', 2, 'Project 3 Groups Announced', 'Project 3 groups have been finalized and posted in the Resources tab. Please coordinate with your group members immediately. Deadline is May 20.', 1, '2026-05-02 09:00:00', NULL, '2026-05-02 01:00:00', '2026-05-02 01:00:00'),
(11, 36, 'section', 3, 'Problem Set 4 Released', 'Problem Set 4 covering Trees and Graphs is now available. Focus on BST operations and BFS/DFS traversal. Due May 15. No extensions allowed.', 0, '2026-05-02 10:00:00', NULL, '2026-05-02 02:00:00', '2026-05-02 02:00:00'),
(12, 36, 'section', 4, 'Capstone Defense Schedule', 'Preliminary defense schedules will be released by May 20. Ensure Chapter 2 is submitted beforehand. Review format requirements in the Capstone Handbook.', 1, '2026-05-10 08:00:00', NULL, '2026-05-10 00:00:00', '2026-05-10 00:00:00'),
(13, 57, 'section', 6, 'Lab 3 Released: Routing Protocol Config', 'Lab 3 on OSPF routing configuration is now available in Cisco Packet Tracer format. Download the starter file from the Modules section. Due April 23.', 1, '2026-04-07 08:00:00', NULL, '2026-04-07 00:00:00', '2026-04-07 00:00:00'),
(14, 57, 'section', 6, 'Midterm Results Posted', 'Midterm exam results are now available. Class average was 79.2%. Students below 65 are advised to schedule a consultation. Strong performance overall!', 0, '2026-04-14 09:00:00', NULL, '2026-04-14 01:00:00', '2026-04-14 01:00:00'),
(15, 57, 'section', 7, 'Sprint 2 Submission Guidelines Updated', 'Updated UML diagram requirements for Sprint 2 have been posted. Ensure all sequence diagrams follow the revised notation. See Resources for the updated rubric.', 0, '2026-04-01 08:00:00', NULL, '2026-04-01 00:00:00', '2026-04-01 00:00:00'),
(16, 59, 'section', 9, 'SQL Lab 3 Available', 'SQL Lab 3 covering stored procedures, views, and triggers is now open. Use the provided hospital database schema as your base. Due May 5.', 1, '2026-04-21 08:00:00', NULL, '2026-04-21 00:00:00', '2026-04-21 00:00:00'),
(17, 59, 'section', 9, 'SQL Lab 2 Grades Released', 'Grades for SQL Lab 2 are now posted. Class average was 85%. Detailed feedback available in the Grades tab. Common issue: missing HAVING clause in aggregation queries.', 0, '2026-04-10 09:00:00', NULL, '2026-04-10 01:00:00', '2026-04-10 01:00:00'),
(18, 60, 'section', 13, 'Midterm Reviewer Posted', 'The midterm reviewer covering Chapters 1-4 (Number Theory, Logic, Statistics, and Financial Math) has been uploaded to the Modules section. Exam is on March 20.', 1, '2026-03-13 08:00:00', NULL, '2026-03-13 00:00:00', '2026-03-13 00:00:00'),
(19, 36, 'platform', NULL, 'End-of-Semester Reminder', 'Finals week is approaching! Please review your submission statuses in each course. Missing submissions will receive a grade of 0. Contact your instructors for any concerns.', 1, '2026-05-11 08:00:00', NULL, '2026-05-11 00:00:00', '2026-05-11 00:00:00'),
(20, 1, 'platform', NULL, 'Platform Maintenance - May 15', 'LearnFlow will undergo scheduled maintenance on May 15, 2026, from 1:00 AM to 5:00 AM. Please download any needed materials beforehand. We apologize for the inconvenience.', 0, '2026-05-11 09:00:00', '2026-05-16 00:00:00', '2026-05-11 01:00:00', '2026-05-11 01:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int(10) UNSIGNED NOT NULL,
  `announcement_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_reads`
--

INSERT INTO `announcement_reads` (`id`, `announcement_id`, `user_id`, `read_at`) VALUES
(1, 1, 37, '2026-05-01 01:00:00'),
(2, 1, 38, '2026-05-01 01:05:00'),
(3, 1, 39, '2026-05-01 01:10:00'),
(4, 1, 40, '2026-05-01 01:30:00'),
(5, 1, 41, '2026-05-01 02:00:00'),
(6, 1, 42, '2026-05-01 02:15:00'),
(7, 2, 37, '2026-05-04 02:00:00'),
(8, 2, 38, '2026-05-04 02:05:00'),
(9, 2, 39, '2026-05-04 02:10:00'),
(10, 2, 44, '2026-05-04 03:00:00'),
(11, 2, 45, '2026-05-04 03:15:00'),
(12, 3, 43, '2026-05-05 03:00:00'),
(13, 3, 37, '2026-05-05 03:05:00'),
(14, 3, 38, '2026-05-05 03:10:00'),
(15, 3, 52, '2026-05-05 04:00:00'),
(16, 4, 47, '2026-05-04 04:00:00'),
(17, 4, 48, '2026-05-04 04:05:00'),
(18, 4, 49, '2026-05-04 04:10:00'),
(19, 4, 50, '2026-05-04 04:15:00'),
(20, 7, 47, '2026-05-07 00:00:00'),
(21, 7, 48, '2026-05-07 00:05:00'),
(22, 7, 49, '2026-05-07 00:10:00'),
(23, 8, 55, '2026-04-30 03:00:00'),
(24, 8, 56, '2026-04-30 03:05:00'),
(25, 12, 55, '2026-05-10 01:00:00'),
(26, 12, 56, '2026-05-10 01:10:00'),
(27, 19, 37, '2026-05-11 01:00:00'),
(28, 19, 38, '2026-05-11 01:05:00'),
(29, 19, 47, '2026-05-11 01:10:00'),
(30, 19, 61, '2026-05-11 01:15:00'),
(31, 19, 48, '2026-05-16 00:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_targets`
--

CREATE TABLE `announcement_targets` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_targets`
--

INSERT INTO `announcement_targets` (`announcement_id`, `user_id`) VALUES
(20, 37),
(20, 38),
(20, 39),
(20, 40),
(20, 41),
(20, 42),
(20, 43),
(20, 44),
(20, 45),
(20, 46),
(20, 47),
(20, 48),
(20, 49),
(20, 50),
(20, 51),
(20, 52),
(20, 53),
(20, 54),
(20, 55),
(20, 56),
(20, 61),
(20, 62),
(20, 63),
(20, 64),
(20, 65),
(20, 66),
(20, 67),
(20, 68),
(20, 69),
(20, 70);

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `instructions` longtext DEFAULT NULL,
  `assignment_type` enum('individual','group') NOT NULL DEFAULT 'individual',
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `passing_score` decimal(6,2) NOT NULL DEFAULT 60.00,
  `due_date` datetime NOT NULL,
  `allow_late` tinyint(1) NOT NULL DEFAULT 0,
  `late_penalty_pct` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `section_id`, `title`, `instructions`, `assignment_type`, `max_score`, `passing_score`, `due_date`, `allow_late`, `late_penalty_pct`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lab Activity 5: Exception Handling', 'Implement a Java program demonstrating try-catch-finally, custom exceptions, and multi-catch blocks. Submit your .java source files and a brief report explaining your exception strategy.', 'individual', 100.00, 75.00, '2026-05-10 23:59:00', 1, 0, 'published', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(2, 2, 'Project 2: Responsive Portfolio Site', 'Build a fully responsive personal portfolio website using HTML5, CSS Grid/Flexbox, and vanilla JavaScript. Must include a hero section, projects gallery, and contact form.', 'individual', 100.00, 75.00, '2026-04-28 23:59:00', 0, 0, 'published', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(3, 3, 'Problem Set 3: Sorting Algorithms', 'Implement Bubble Sort, Selection Sort, Merge Sort, and Quick Sort in Java. Include Big-O analysis for each and a comparison table of their time complexities.', 'individual', 100.00, 75.00, '2026-04-25 23:59:00', 1, 0, 'published', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(4, 4, 'Capstone Proposal Draft', 'Submit a 5-8 page research proposal for your capstone project including problem statement, objectives, scope, methodology, and a preliminary review of related literature.', 'individual', 100.00, 75.00, '2026-05-05 23:59:00', 1, 0, 'published', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(5, 1, 'Lab Activity 4: Inheritance and Polymorphism', 'Create a class hierarchy demonstrating inheritance, method overriding, and polymorphism. Implement at least 3 levels of inheritance with a real-world domain (e.g., shapes, animals, vehicles).', 'individual', 100.00, 75.00, '2026-04-20 23:59:00', 1, 0, 'closed', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(6, 2, 'module 10', 'please pass on time', 'individual', 100.00, 60.00, '2026-05-12 00:00:00', 1, 0, 'published', '2026-05-11 09:27:19', '2026-05-11 09:27:19'),
(7, 2, 'Project 3: Full-Stack CRUD App', 'Build a full-stack CRUD web application using HTML, CSS, JavaScript, and a simple PHP/MySQL backend.', 'group', 100.00, 75.00, '2026-05-20 23:59:00', 1, 10, 'published', '2026-05-01 00:00:00', '2026-05-01 00:00:00'),
(8, 3, 'Problem Set 4: Trees and Graphs', 'Implement BST insert/delete/search and BFS/DFS graph traversal. Include time complexity analysis.', 'individual', 100.00, 75.00, '2026-05-15 23:59:00', 1, 5, 'published', '2026-05-01 00:00:00', '2026-05-01 00:00:00'),
(9, 4, 'Capstone Chapter 2 - RRL', 'Submit a 10-page Review of Related Literature with at least 15 recent references (APA format).', 'individual', 100.00, 75.00, '2026-05-20 23:59:00', 1, 0, 'published', '2026-04-15 00:00:00', '2026-04-15 00:00:00'),
(10, 6, 'Lab 1: Network Topology Design', 'Use Cisco Packet Tracer to design and configure a small office network with proper IP addressing.', 'individual', 100.00, 75.00, '2026-03-05 23:59:00', 0, 0, 'closed', '2026-02-20 00:00:00', '2026-02-20 00:00:00'),
(11, 6, 'Lab 2: Subnetting Worksheet', 'Complete the subnetting exercises for Class A, B, and C networks and provide your calculations.', 'individual', 100.00, 75.00, '2026-03-26 23:59:00', 1, 5, 'closed', '2026-03-10 00:00:00', '2026-03-10 00:00:00'),
(12, 6, 'Lab 3: Routing Protocol Configuration', 'Configure OSPF routing between three routers using Cisco IOS commands in Packet Tracer.', 'individual', 100.00, 75.00, '2026-04-23 23:59:00', 1, 5, 'published', '2026-04-07 00:00:00', '2026-04-07 00:00:00'),
(13, 7, 'Sprint 1 - Requirements Document', 'Produce a Software Requirements Specification (SRS) document for your chosen project following IEEE standards.', 'group', 100.00, 75.00, '2026-03-10 23:59:00', 0, 0, 'closed', '2026-02-24 00:00:00', '2026-02-24 00:00:00'),
(14, 7, 'Sprint 2 - System Design Document', 'Create UML class diagrams, sequence diagrams, and an ER diagram for your proposed system.', 'group', 100.00, 75.00, '2026-04-14 23:59:00', 1, 5, 'published', '2026-03-30 00:00:00', '2026-03-30 00:00:00'),
(15, 9, 'SQL Lab 1: DDL and DML Queries', 'Create tables with proper constraints and perform INSERT, UPDATE, DELETE, and SELECT operations.', 'individual', 100.00, 75.00, '2026-03-10 23:59:00', 0, 0, 'closed', '2026-02-24 00:00:00', '2026-02-24 00:00:00'),
(16, 9, 'SQL Lab 2: Joins and Subqueries', 'Write complex SQL queries involving INNER JOIN, LEFT JOIN, nested subqueries, and aggregate functions.', 'individual', 100.00, 75.00, '2026-04-07 23:59:00', 1, 5, 'published', '2026-03-24 00:00:00', '2026-03-24 00:00:00'),
(17, 9, 'SQL Lab 3: Stored Procedures and Views', 'Implement stored procedures, functions, and views. Include one trigger for audit logging.', 'individual', 100.00, 75.00, '2026-05-05 23:59:00', 1, 5, 'published', '2026-04-21 00:00:00', '2026-04-21 00:00:00'),
(18, 15, 'Lab Activity 1: Binary Conversion', 'Convert 20 decimal numbers to binary, octal, and hexadecimal. Show complete step-by-step solutions.', 'individual', 50.00, 30.00, '2026-03-05 23:59:00', 1, 0, 'closed', '2026-02-20 00:00:00', '2026-02-20 00:00:00'),
(19, 15, 'Lab Activity 2: Hardware Components', 'Identify and label the components of a motherboard diagram. Research the function of each component.', 'individual', 50.00, 30.00, '2026-04-02 23:59:00', 0, 0, 'published', '2026-03-17 00:00:00', '2026-03-17 00:00:00'),
(20, 2, 'Midterm Exam - Web Programming', 'Take-home midterm covering HTML5 semantics, CSS Grid/Flexbox layouts, and basic JavaScript DOM manipulation.', 'individual', 100.00, 75.00, '2026-03-28 23:59:00', 0, 0, 'closed', '2026-03-18 00:00:00', '2026-03-18 00:00:00'),
(21, 2, 'my assignment', 'do this', 'individual', 100.00, 60.00, '2026-05-12 00:00:00', 1, 0, 'published', '2026-05-12 03:56:20', '2026-05-12 03:56:20');

--
-- Triggers `assignments`
--
DELIMITER $$
CREATE TRIGGER `trg_assignments_after_insert` AFTER INSERT ON `assignments` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NULL, 'assignment_created', 'assignments', NEW.id,



          JSON_OBJECT('section_id', NEW.section_id, 'title', NEW.title));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_assignments_after_update` AFTER UPDATE ON `assignments` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status OR OLD.due_date <> NEW.due_date THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL, 'assignment_updated', 'assignments', NEW.id,



            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,



                        'old_due', OLD.due_date, 'new_due', NEW.due_date));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_attachments`
--

CREATE TABLE `assignment_attachments` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detail`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(536, 36, 'magic_link_login', 'users', 36, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 00:56:35'),
(537, NULL, 'quiz_created', 'quizzes', 11, '{\"section_id\": 2, \"title\": \"testing\"}', NULL, NULL, '2026-05-16 00:57:48'),
(538, NULL, 'quiz_created', 'quizzes', 12, '{\"section_id\": 1, \"title\": \"testing\"}', NULL, NULL, '2026-05-16 00:58:39'),
(539, NULL, 'quiz_created', 'quizzes', 13, '{\"section_id\": 2, \"title\": \"testing 2\"}', NULL, NULL, '2026-05-16 00:59:19'),
(540, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:03:09'),
(541, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:17:33'),
(542, 36, 'magic_link_login', 'users', 36, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:18:09'),
(543, NULL, 'quiz_created', 'quizzes', 14, '{\"section_id\": 2, \"title\": \"testing\"}', NULL, NULL, '2026-05-16 01:20:26'),
(544, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:21:06'),
(545, 37, 'quiz_attempt_started', 'quiz_attempts', 53, '{\"quiz_id\": 14, \"attempt_number\": 1}', NULL, NULL, '2026-05-16 01:27:57'),
(546, 36, 'magic_link_login', 'users', 36, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:28:29'),
(547, NULL, 'quiz_created', 'quizzes', 15, '{\"section_id\": 2, \"title\": \"Quiz#4\"}', NULL, NULL, '2026-05-16 01:31:16'),
(548, NULL, 'quiz_created', 'quizzes', 16, '{\"section_id\": 2, \"title\": \"testing2\"}', NULL, NULL, '2026-05-16 01:32:19'),
(549, NULL, 'quiz_created', 'quizzes', 17, '{\"section_id\": 1, \"title\": \"test3\"}', NULL, NULL, '2026-05-16 01:33:22'),
(550, NULL, 'quiz_created', 'quizzes', 18, '{\"section_id\": 1, \"title\": \"Final Quiz#5\"}', NULL, NULL, '2026-05-16 01:35:35'),
(551, NULL, 'quiz_created', 'quizzes', 19, '{\"section_id\": 1, \"title\": \"test4\"}', NULL, NULL, '2026-05-16 01:36:23'),
(552, 36, 'magic_link_login', 'users', 36, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:40:29'),
(553, 36, 'course_updated', 'courses', 3, '{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Data Structures and Algorithms\", \"new_title\": \"Data Structures and Algorithms\"}', NULL, NULL, '2026-05-16 01:41:23'),
(554, 36, 'course_updated', 'courses', 3, '{\"old_status\": \"archived\", \"new_status\": \"published\", \"old_title\": \"Data Structures and Algorithms\", \"new_title\": \"Data Structures and Algorithms\"}', NULL, NULL, '2026-05-16 01:41:35'),
(555, NULL, 'quiz_created', 'quizzes', 20, '{\"section_id\": 1, \"title\": \"test5\"}', NULL, NULL, '2026-05-16 01:43:42'),
(556, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:46:37'),
(557, 37, 'submission_created', 'submissions', 50, '{\"assignment_id\": 21, \"is_late\": 0}', NULL, NULL, '2026-05-16 01:47:48'),
(558, 37, 'quiz_attempt_started', 'quiz_attempts', 54, '{\"quiz_id\": 20, \"attempt_number\": 1}', NULL, NULL, '2026-05-16 01:49:39'),
(559, 36, 'magic_link_login', 'users', 36, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:53:26'),
(560, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:54:55'),
(561, 112, 'user_registered', 'users', 112, '{\"email\": \"navarro_adrian@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:20'),
(562, 113, 'user_registered', 'users', 113, '{\"email\": \"reyes_bianca@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(563, 114, 'user_registered', 'users', 114, '{\"email\": \"torres_carl@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(564, 115, 'user_registered', 'users', 115, '{\"email\": \"mendoza_daphne@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(565, 116, 'user_registered', 'users', 116, '{\"email\": \"flores_ethan@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(566, 117, 'user_registered', 'users', 117, '{\"email\": \"castillo_faith@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(567, 118, 'user_registered', 'users', 118, '{\"email\": \"rivera_gabriel@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(568, 119, 'user_registered', 'users', 119, '{\"email\": \"aquino_hannah@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(569, 120, 'user_registered', 'users', 120, '{\"email\": \"salazar_ivan@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(570, 121, 'user_registered', 'users', 121, '{\"email\": \"domingo_jasmine@plpasig.edu.ph\", \"role\": \"student\"}', NULL, NULL, '2026-05-16 01:55:21'),
(571, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 03:12:33'),
(572, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 04:02:02'),
(573, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:22:52'),
(574, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:24:33'),
(575, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:26:06'),
(576, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:31:20'),
(577, 37, 'magic_link_login', 'users', 37, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:32:34'),
(578, 1, 'magic_link_login', 'users', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 15:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `token_type` enum('email_verify','password_reset','magic_link') NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_email` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`id`, `user_id`, `token_hash`, `token_type`, `expires_at`, `used_at`, `created_at`, `login_email`) VALUES
(43, 1, '691d0238505ba316b552bf0134c456643abc71b78a5623ff4da529da22b443a4', 'password_reset', '2026-05-04 10:40:46', '2026-05-04 10:40:46', '2026-05-04 10:40:42', 'duran_lemuel@plpasig.edu.ph'),
(45, 1, 'e139bd389231afc703ecd0d936171171179a10511e7d3221614c9ecb6bf96a4c', 'password_reset', '2026-05-04 11:45:11', '2026-05-04 11:45:11', '2026-05-04 11:45:05', 'duran_lemuel@plpasig.edu.ph'),
(49, 1, '2d45a69848a9f6924f43412aa747ba22d50a3e92d9930556f1fe5df199339de0', 'password_reset', '2026-05-05 01:38:31', '2026-05-05 01:38:31', '2026-05-05 01:38:27', 'duran_lemuel@plpasig.edu.ph'),
(53, 1, '98eea4497222f25077d94f08c6fd31cc3c0dd5bacdc8ea3ef0360af979e5a754', 'password_reset', '2026-05-05 01:53:11', '2026-05-05 01:53:11', '2026-05-05 01:53:08', 'duran_lemuel@plpasig.edu.ph'),
(57, 1, '162ad9c1cdef7382634fd202db6252dfd11091d059477edc02312f6dfdb54caa', 'password_reset', '2026-05-05 02:06:13', '2026-05-05 02:06:13', '2026-05-05 02:06:10', 'duran_lemuel@plpasig.edu.ph'),
(58, 1, 'b40f72b709288863424b259405300ec0a56a61a01ed57e36afd9d0f4492d58b7', 'password_reset', '2026-05-05 02:07:51', '2026-05-05 02:07:51', '2026-05-05 02:07:47', 'duran_lemuel@plpasig.edu.ph'),
(59, 1, '14f6e120c3dc02c5c0939abe8b90d42b0768467ef6c99b5bd6a9a286d4ff1742', 'password_reset', '2026-05-05 02:11:47', '2026-05-05 02:11:47', '2026-05-05 02:11:44', 'duran_lemuel@plpasig.edu.ph'),
(60, 1, 'ff93e79e9d6e0267e1eeec86c8d261cfef66b63f8ad6c6697a687480a4655c82', 'password_reset', '2026-05-05 02:26:18', '2026-05-05 02:26:18', '2026-05-05 02:26:13', 'duran_lemuel@plpasig.edu.ph'),
(62, 1, 'a8633f9f857a9f0ddf93d26ee67e9315e269c18833bc871cde9d1626e31d28b1', 'password_reset', '2026-05-05 02:45:36', '2026-05-05 02:45:36', '2026-05-05 02:45:33', 'duran_lemuel@plpasig.edu.ph'),
(70, 1, '738f7d71435c53b9e9a55de5edc8dc48da849439fd19d0cd63176687cd17f29e', 'password_reset', '2026-05-11 06:26:34', '2026-05-11 06:26:34', '2026-05-11 06:26:28', 'duran_lemuel@plpasig.edu.ph'),
(71, 36, 'f65460803dd919abf1b01e2da997e41fe5435271a28bb92aaa561c5a7730c1b7', 'password_reset', '2026-05-11 06:29:00', '2026-05-11 06:29:00', '2026-05-11 06:28:50', 'santos_cath@plpasig.edu.ph'),
(72, 1, 'a48f215b4468a189f179958eb4f5c1749f07beba8ade89902126ec6678bfd8e4', 'password_reset', '2026-05-11 06:29:27', '2026-05-11 06:29:27', '2026-05-11 06:29:23', 'duran_lemuel@plpasig.edu.ph'),
(73, 36, '1be5e6aff34eb612ef99d3995e3761d2e405a3ac9a3d3d7639bddfe0254b52cc', 'password_reset', '2026-05-11 06:30:55', '2026-05-11 06:30:55', '2026-05-11 06:30:50', 'santos_cath@plpasig.edu.ph'),
(74, 37, '4989b859895aeba0c579e47bb6900a7d1892fb10968f425a79edb67865cf0ff8', 'password_reset', '2026-05-11 07:07:30', '2026-05-11 07:07:30', '2026-05-11 07:07:26', 'gabriel_ryza@plpasig.edu.ph'),
(75, 36, 'a9091e921a598bc9a45179d6ac48c7d9a0bb4baa638111b07eb3b96251de9626', 'password_reset', '2026-05-11 07:08:32', '2026-05-11 07:08:32', '2026-05-11 07:08:27', 'santos_cath@plpasig.edu.ph'),
(76, 37, '0afda62b4adbc2c332e9affece827691da2e97ae2e7f3194a92f2a18984c4609', 'password_reset', '2026-05-11 07:13:16', '2026-05-11 07:13:16', '2026-05-11 07:13:06', 'gabriel_ryza@plpasig.edu.ph'),
(77, 36, 'c5ad879067d50a5069b802a49594bfa965f2e5eba1e2241bdf0ef8352a2cc354', 'password_reset', '2026-05-11 07:13:51', '2026-05-11 07:13:51', '2026-05-11 07:13:48', 'santos_cath@plpasig.edu.ph'),
(78, 37, '9d688eb0141c647ad75514138c29f43bff30c3fbd52c95887c450cf7b4f399ec', 'password_reset', '2026-05-11 07:14:48', '2026-05-11 07:14:48', '2026-05-11 07:14:45', 'gabriel_ryza@plpasig.edu.ph'),
(79, 1, 'ab74cd26300b6d2a92de561fc00cc219d8b42555a125cdeb15a360e27beb34f5', 'password_reset', '2026-05-11 07:16:01', '2026-05-11 07:16:01', '2026-05-11 07:15:58', 'duran_lemuel@plpasig.edu.ph'),
(80, 36, 'dc89dab8ba65182cfc5596752bbf257cc543349f379d92555be529aaa086ae0d', 'password_reset', '2026-05-11 07:37:53', '2026-05-11 07:37:53', '2026-05-11 07:37:49', 'santos_cath@plpasig.edu.ph'),
(81, 37, '74e626407d8bbda4a7e4cfcc5f63d6ed044e341d783be6309f3796614861f2dc', 'password_reset', '2026-05-11 07:39:26', '2026-05-11 07:39:26', '2026-05-11 07:39:23', 'gabriel_ryza@plpasig.edu.ph'),
(82, 36, 'a6ec48f6cb4ca69f208cdf0ef1f3c090b9f21bed1c32cdc4ee8d0a96c5ab23f3', 'password_reset', '2026-05-11 07:40:19', '2026-05-11 07:40:19', '2026-05-11 07:40:16', 'santos_cath@plpasig.edu.ph'),
(83, 37, '03523e9b82faf088fa245918587873f0db918481f1f3351299ce36c500421ae7', 'password_reset', '2026-05-11 07:41:35', '2026-05-11 07:41:35', '2026-05-11 07:41:31', 'gabriel_ryza@plpasig.edu.ph'),
(84, 36, '917394e8b2096c52a842c7e910e68795373d54e617608e976be2829905f8dfae', 'password_reset', '2026-05-11 07:50:58', '2026-05-11 07:50:58', '2026-05-11 07:50:53', 'santos_cath@plpasig.edu.ph'),
(85, 37, '1c1e5b0de1890980036eb7fa8d28f5da1a32aee1d0c5cf657bb606e8769e842f', 'password_reset', '2026-05-11 07:51:57', '2026-05-11 07:51:57', '2026-05-11 07:51:54', 'gabriel_ryza@plpasig.edu.ph'),
(86, 36, '930edf5f0977bb74f44dcbbfb8dac3cc2f7053e319c412c5dac5abebb3510620', 'password_reset', '2026-05-11 07:57:51', '2026-05-11 07:57:51', '2026-05-11 07:57:47', 'santos_cath@plpasig.edu.ph'),
(87, 37, '2edc43465e8cee83567293244c090325695321a765974c6cd5fb725d8d39fe74', 'password_reset', '2026-05-11 07:58:58', '2026-05-11 07:58:58', '2026-05-11 07:58:55', 'gabriel_ryza@plpasig.edu.ph'),
(88, 36, 'b560328d8cbae99427a28ba014d1e0b0ccc76c9ddc1bca3520c9870104fd051f', 'password_reset', '2026-05-11 08:04:50', '2026-05-11 08:04:50', '2026-05-11 08:04:47', 'santos_cath@plpasig.edu.ph'),
(89, 37, 'c20e7a0c7af32c6823493bf845dfaffaaa5b3424f903102ec8bb6830fa379ab6', 'password_reset', '2026-05-11 08:05:31', '2026-05-11 08:05:31', '2026-05-11 08:05:27', 'gabriel_ryza@plpasig.edu.ph'),
(90, 36, '437c802e6d003089cb7741e87e4c622bb91ca4baf7191c82bec2ceecaba7ced9', 'password_reset', '2026-05-11 08:35:13', '2026-05-11 08:35:13', '2026-05-11 08:35:09', 'santos_cath@plpasig.edu.ph'),
(91, 37, '8643a5d6854cc109a8e51f1f7cf0c4941a88abe102040e7f6d9dbb15d14772e4', 'password_reset', '2026-05-11 08:36:00', '2026-05-11 08:36:00', '2026-05-11 08:35:56', 'gabriel_ryza@plpasig.edu.ph'),
(92, 36, 'ea8a5b89eb54ae3bc89d7ceb5ce39435d5a528eb9d3edfc1c895ddf215a9ec0e', 'password_reset', '2026-05-11 08:43:35', '2026-05-11 08:43:35', '2026-05-11 08:43:31', 'santos_cath@plpasig.edu.ph'),
(93, 37, '2d9ff8a38369284b7a5f54f5e271657738137c6dbde059c15d1a94b9d3461f3a', 'password_reset', '2026-05-11 08:44:16', '2026-05-11 08:44:16', '2026-05-11 08:44:13', 'gabriel_ryza@plpasig.edu.ph'),
(94, 36, '27a34b57f4a1d2eff0221e386461efd7270ac3d1a7648f1ce1421574ddfc6d81', 'password_reset', '2026-05-11 08:45:53', '2026-05-11 08:45:53', '2026-05-11 08:45:48', 'santos_cath@plpasig.edu.ph'),
(95, 1, '1e133bcaabdb150afe845d8aaa5d58c98afacac1c4cce23ea282d5c84a9f8682', 'password_reset', '2026-05-11 12:23:45', '2026-05-11 12:23:45', '2026-05-11 12:23:40', 'duran_lemuel@plpasig.edu.ph'),
(96, 37, 'b0f8b7f021689a8a1d7b2925037806c5a21435154a51c30b8135225b6c011147', 'password_reset', '2026-05-11 12:50:06', '2026-05-11 12:50:06', '2026-05-11 12:50:03', 'gabriel_ryza@plpasig.edu.ph'),
(97, 1, 'a5f437c592d93925666d02954c6137810869220fd6c241d978724142b1f29d2b', 'password_reset', '2026-05-11 21:24:27', '2026-05-11 21:24:27', '2026-05-11 21:24:22', 'duran_lemuel@plpasig.edu.ph'),
(107, 36, '98d2757d186819d1fa7bfea310f93dd3d2234ff10ce357596058f1c527513497', 'password_reset', '2026-05-11 21:41:54', '2026-05-11 21:41:54', '2026-05-11 21:41:49', 'santos_cath@plpasig.edu.ph'),
(108, 36, '0ae98276db5c34250a3952a83417dddd0a8736af21c04b5d050e31fc23be53b8', 'password_reset', '2026-05-11 21:51:08', '2026-05-11 21:51:08', '2026-05-11 21:51:01', 'santos_cath@plpasig.edu.ph'),
(109, 37, '808d03da6c4cb21f94abd296dc90664cc4c45d7bbf66f43e4b4adb1db0cb5ae5', 'password_reset', '2026-05-11 21:52:44', '2026-05-11 21:52:44', '2026-05-11 21:52:39', 'gabriel_ryza@plpasig.edu.ph'),
(110, 36, '69cc477081a535fd7c387b0c24a21c5288cb466ac4806e455d607ccf5b51cf1e', 'password_reset', '2026-05-12 01:50:36', '2026-05-12 01:50:36', '2026-05-12 01:50:30', 'santos_cath@plpasig.edu.ph'),
(111, 37, 'cadf95568e57b61670a084a18c7f73e31b4fc2b7cde8df88955702b1c2a6bc6e', 'password_reset', '2026-05-12 04:02:49', '2026-05-12 04:02:49', '2026-05-12 04:02:45', 'gabriel_ryza@plpasig.edu.ph'),
(112, 37, '5b5d0d8494d6b4d23a0fa374e1f791f79631b8c6ccf41aee3e7325f9201315b4', 'password_reset', '2026-05-12 04:50:35', '2026-05-12 04:50:35', '2026-05-12 04:50:29', 'gabriel_ryza@plpasig.edu.ph'),
(113, 36, '8236676d2c7438d530fe22a26fef14b8bbfccf90c95d27ffb44b8b13ec94c0f7', 'password_reset', '2026-05-12 04:51:12', '2026-05-12 04:51:12', '2026-05-12 04:51:05', 'santos_cath@plpasig.edu.ph'),
(114, 37, '9beafceafffa086e8da45154dd6da29e68696d28d3b19f2850fd46853f66f3ef', 'password_reset', '2026-05-12 04:52:36', '2026-05-12 04:52:36', '2026-05-12 04:52:32', 'gabriel_ryza@plpas\nig.edu.ph'),
(115, 37, '6b5dd17a9db86f1c381f24d043490d7048ee4941700a78c82b435788238e9bab', 'password_reset', '2026-05-15 23:43:59', '2026-05-15 23:43:59', '2026-05-15 23:43:54', 'gabriel_ryza@plpasig.edu.ph'),
(116, 37, 'a6fcc7fbe809065b677600ec046a88c700122a8032f26926b4709e5da1f611bf', 'password_reset', '2026-05-15 23:44:19', '2026-05-15 23:44:19', '2026-05-15 23:44:16', 'gabriel_ryza@plpasig.edu.ph'),
(117, 36, '99765bdfccc4b2c2ce211d61779d56127eb0900afde836d6cc25fd36eedfebbd', 'password_reset', '2026-05-15 23:44:45', '2026-05-15 23:44:45', '2026-05-15 23:44:42', 'santos_cath@plpasig.edu.ph'),
(118, 37, 'ad443c39d2028078f2a65dc051afcaaa63545eaf7a9512bf2a9f85bf9391ef83', 'password_reset', '2026-05-15 23:45:23', '2026-05-15 23:45:23', '2026-05-15 23:45:20', 'gabriel_ryza@plpasig.edu.ph'),
(119, 36, '1590b539f9705aae7e3cc965a9bd6e2fdb1ab739752e2e0a688bdac14f5baf99', 'password_reset', '2026-05-15 23:54:33', '2026-05-15 23:54:33', '2026-05-15 23:54:31', 'santos_cath@plpasig.edu.ph'),
(120, 37, '11534bedb952a50b0457c9c2b522a9f322a5251b1a3aab836304295483dc63aa', 'password_reset', '2026-05-15 23:55:11', '2026-05-15 23:55:11', '2026-05-15 23:55:09', 'gabriel_ryza@plpasig.edu.ph'),
(121, 36, 'e1cb0c78ba90246bd526e9d800ea91f61483a26619ea60df197657a3520d952f', 'password_reset', '2026-05-16 00:56:35', '2026-05-16 00:56:35', '2026-05-16 00:56:29', 'santos_cath@plpasig.edu.ph'),
(122, 37, '044bedd03f4a9aee9f76c48678b8d6eae88aab6c19322701e467c6799d64ed73', 'password_reset', '2026-05-16 01:03:09', '2026-05-16 01:03:09', '2026-05-16 01:03:03', 'gabriel_ryza@plpasig.edu.ph'),
(124, 37, 'a3732b80c63867c87178b12f4437030bcc50c5ea3834b12c521b272f3a22dffa', 'password_reset', '2026-05-16 01:17:33', '2026-05-16 01:17:33', '2026-05-16 01:17:27', 'gabriel_ryza@plpasig.edu.ph'),
(125, 36, '8cccb81b9c8abc05bfefb03cfbce04422dcfa3baa7bcd07d698e8edc39317400', 'password_reset', '2026-05-16 01:18:09', '2026-05-16 01:18:09', '2026-05-16 01:18:05', 'santos_cath@plpasig.edu.ph'),
(126, 37, 'ebb63f2eeebc7572ea097296110e153f04be115ecedeacbad4e7f7babc74c134', 'password_reset', '2026-05-16 01:21:06', '2026-05-16 01:21:06', '2026-05-16 01:21:00', 'gabriel_ryza@plpasig.edu.ph'),
(127, 36, 'b5476663a86aab048a529caf29bfd7e5c940b129608c7cf855dd4ac70fc99015', 'password_reset', '2026-05-16 01:28:29', '2026-05-16 01:28:29', '2026-05-16 01:28:24', 'santos_cath@plpasig.edu.ph'),
(128, 36, '5b66c43d7f6874b0bcc2df43520a19d690041e022d3b9b29e8e18181c5b56ea5', 'password_reset', '2026-05-16 01:40:29', '2026-05-16 01:40:29', '2026-05-16 01:40:12', 'santos_cath@plpasig.edu.ph'),
(129, 37, 'b435df1a1484c88546a01436fab93489aa8196d1c8cd7e69c2715bc411c3f46d', 'password_reset', '2026-05-16 01:46:37', '2026-05-16 01:46:37', '2026-05-16 01:46:32', 'gabriel_ryza@plpasig.edu.ph'),
(130, 36, '78634ba8c971bad7ca9dbe86ccb18cea07054b69732b3479d1516eddec75a6b5', 'password_reset', '2026-05-16 01:53:26', '2026-05-16 01:53:26', '2026-05-16 01:52:55', 'santos_cath@plpasig.edu.ph'),
(131, 1, '1f9beaf6716934ac88e4a6138b2dfa89c6516ccf14fd5f8b178f98aad91906b9', 'password_reset', '2026-05-16 01:54:55', '2026-05-16 01:54:55', '2026-05-16 01:54:47', 'duran_lemuel@plpasig.edu.ph'),
(132, 112, '02a23b4857e438c9c518e12466b62edbf49f57b79a4def30cfa6a748069d1a8b', 'password_reset', '2026-05-15 20:10:20', NULL, '2026-05-16 01:55:20', 'navarro_adrian@plpasig.edu.ph'),
(133, 113, 'c99daf299c3655e3cefb8844aa0884357abe79f7cab4069e0d8391a638b85bf8', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'reyes_bianca@plpasig.edu.ph'),
(134, 114, 'f33623d4bd0fe3a7f5efb9688a4db36c44d58c2bdb5da015bc4cb53bc7d0c947', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'torres_carl@plpasig.edu.ph'),
(135, 115, '9431494b38510e814c26fe44690aae33401fe74fdd423f6ef1788945bf495929', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'mendoza_daphne@plpasig.edu.ph'),
(136, 116, '85ca90729b48acdae330b4d11204d0dfa8232387d4fb83a68289e779d91b6373', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'flores_ethan@plpasig.edu.ph'),
(137, 117, '146c7149fc42df1b9ab78f55d6a9cd4f36ae13da9a73d1be18d607e6fac6e18e', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'castillo_faith@plpasig.edu.ph'),
(138, 118, '2a652d1f95aa218ee49ceb4c37ee07e901aad65d14d4f2c91858a316ae0895a2', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'rivera_gabriel@plpasig.edu.ph'),
(139, 119, '065ae93d285b7e7c9490e19f1b032260bd37fbee65fd547477330d1d7e08a7f4', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'aquino_hannah@plpasig.edu.ph'),
(140, 120, '1e6b1e699e44ec32a5aa5c1c51138584a5555cf593e1402e7349fbb35f066b55', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'salazar_ivan@plpasig.edu.ph'),
(141, 121, '8a437c6318d22ab6d7a1a6c8d00a4ebd3b02f66c97d86a60d20e8ae7a534f5e7', 'password_reset', '2026-05-15 20:10:21', NULL, '2026-05-16 01:55:21', 'domingo_jasmine@plpasig.edu.ph'),
(142, 1, '7be94898878b241aa9d71694ca7e641f2ac7dc0a4e9c9a358bc34a54dd8efb6e', 'password_reset', '2026-05-16 03:12:33', '2026-05-16 03:12:33', '2026-05-16 03:12:28', 'duran_lemuel@plpasig.edu.ph'),
(143, 1, 'e58d5183b5f24e2e6ca9cef61d52ad5eefcbdee6c21a0533ed82e9d950834551', 'password_reset', '2026-05-16 04:02:02', '2026-05-16 04:02:02', '2026-05-16 04:01:58', 'duran_lemuel@plpasig.edu.ph'),
(144, 1, '452106e7a5c52c3fee6e3c649561e957b01cdcd6df760b8dcca369cb80800592', 'password_reset', '2026-05-17 15:22:52', '2026-05-17 15:22:52', '2026-05-17 15:22:46', 'duran_lemuel@plpasig.edu.ph'),
(145, 37, '70fabedf4b8b7226379a62dcd88e15dba10000f2c3bdf81d67f2a0615f6b7acc', 'password_reset', '2026-05-17 15:24:33', '2026-05-17 15:24:33', '2026-05-17 15:24:03', 'gabriel_ryza@plpasig.edu.ph'),
(146, 37, '574baffb830e940169e7daa19faf5660a5e7e76ae11b7ed598489e9dfc3f49e5', 'password_reset', '2026-05-17 15:26:05', '2026-05-17 15:26:05', '2026-05-17 15:26:01', 'gabriel_ryza@plpasig.edu.ph'),
(147, 1, '3d8dc79b4bad5f78b7553f15d0c4158ce9604ccb0ab151d1aed4a0465f50dba3', 'password_reset', '2026-05-17 15:31:20', '2026-05-17 15:31:20', '2026-05-17 15:31:17', 'duran_lemuel@plpasig.edu.ph'),
(148, 37, 'c667d67ba70b0d525c8e1b77658bd4aa9a2ca4aaf4abd86f32003202cc7e570c', 'password_reset', '2026-05-17 15:32:34', '2026-05-17 15:32:34', '2026-05-17 15:32:27', 'gabriel_ryza@plpasig.edu.ph'),
(149, 1, 'dd3b4e9c33bad1713469de3d23e7b38ee228c273f712eeeafedb608248b98142', 'password_reset', '2026-05-17 15:34:12', '2026-05-17 15:34:12', '2026-05-17 15:34:10', 'duran_lemuel@plpasig.edu.ph');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `units` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `cover_image_url` varchar(500) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(10) UNSIGNED DEFAULT NULL,
  `archive_reason` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `title`, `description`, `department_id`, `units`, `status`, `cover_image_url`, `created_by`, `created_at`, `updated_at`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(1, 'IT 106', 'Object-Oriented Programming', NULL, 1, 3, 'published', NULL, 36, '2026-05-07 09:04:06', '2026-05-11 10:48:02', NULL, NULL, NULL),
(2, 'IT 301', 'Web Programming', NULL, 1, 3, 'published', NULL, 36, '2026-05-07 09:04:06', '2026-05-11 09:43:33', NULL, NULL, NULL),
(3, 'IT 201', 'Data Structures and Algorithms', NULL, 1, 3, 'published', NULL, 36, '2026-05-07 09:04:06', '2026-05-16 01:41:35', NULL, NULL, NULL),
(4, 'IT 411', 'Capstone Project', NULL, 1, 3, 'archived', NULL, 36, '2026-05-07 09:04:06', '2026-05-11 11:08:15', '2026-05-11 19:08:15', 36, 'just because'),
(5, 'IT103', 'Advance Database Management', NULL, NULL, 3, 'published', NULL, 36, '2026-05-08 10:55:22', '2026-05-11 06:32:25', NULL, NULL, NULL);

--
-- Triggers `courses`
--
DELIMITER $$
CREATE TRIGGER `trg_courses_after_delete` AFTER DELETE ON `courses` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NULL, 'course_deleted', 'courses', OLD.id,



          JSON_OBJECT('code', OLD.code, 'title', OLD.title));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_courses_after_insert` AFTER INSERT ON `courses` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NEW.created_by, 'course_created', 'courses', NEW.id,



          JSON_OBJECT('code', NEW.code, 'title', NEW.title));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_courses_after_update` AFTER UPDATE ON `courses` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status OR OLD.title <> NEW.title THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.created_by, 'course_updated', 'courses', NEW.id,



            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,



                        'old_title',  OLD.title,  'new_title',  NEW.title));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `course_archives`
--

CREATE TABLE `course_archives` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('archived','restored') NOT NULL DEFAULT 'archived',
  `performed_by` int(10) UNSIGNED NOT NULL COMMENT 'user_id of instructor',
  `reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_archives`
--

INSERT INTO `course_archives` (`id`, `course_id`, `section_id`, `action`, `performed_by`, `reason`, `created_at`) VALUES
(1, 5, 5, 'archived', 36, 'sadhfnsjfes', '2026-05-08 10:56:30'),
(2, 5, 5, 'restored', 36, NULL, '2026-05-11 06:32:25'),
(3, 2, 2, 'archived', 36, 'Archived by instructor', '2026-05-11 06:32:32'),
(4, 1, 1, 'archived', 36, 'dausdausd', '2026-05-11 09:42:24'),
(5, 2, 2, 'restored', 36, NULL, '2026-05-11 09:43:33'),
(6, 1, 1, 'restored', 36, NULL, '2026-05-11 10:48:02'),
(7, 4, 4, 'archived', 36, 'just because', '2026-05-11 10:48:15'),
(8, 4, 4, 'restored', 36, NULL, '2026-05-11 11:08:05'),
(9, 4, 4, 'archived', 36, 'just because', '2026-05-11 11:08:15'),
(10, 3, 3, 'archived', 36, 'Archived by instructor', '2026-05-16 01:41:23'),
(11, 3, 3, 'restored', 36, NULL, '2026-05-16 01:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `course_posts`
--

CREATE TABLE `course_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `post_type` enum('announcement','module') NOT NULL DEFAULT 'module',
  `title` varchar(255) NOT NULL,
  `body` longtext DEFAULT NULL COMMENT 'Rich-text or markdown content for the post',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_posts`
--

INSERT INTO `course_posts` (`id`, `section_id`, `author_id`, `post_type`, `title`, `body`, `is_pinned`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 36, '', 'OOP Cheat Sheet - Key Concepts Summary', 'Attached is a one-page summary of all key OOP concepts covered this semester: Encapsulation, Inheritance, Polymorphism, Abstraction, Interfaces, and Exception Handling. Use this as a reference during coding exercises.', 0, 1, '2026-01-20 00:00:00', '2026-01-20 00:00:00', '2026-01-20 00:00:00'),
(2, 1, 36, '', 'Week 3-4 Lecture Slides: Inheritance', 'Lecture slides for Weeks 3 and 4 are now available. Topics covered: single/multi-level inheritance, the \"is-a\" relationship, method overriding, the super keyword, and constructor chaining.', 0, 1, '2026-01-27 00:00:00', '2026-01-27 00:00:00', '2026-01-27 00:00:00'),
(3, 1, 36, 'announcement', 'Consultation Hours This Week', 'I will be available for consultation in Room 104 every Tuesday 2:00-4:00 PM and Thursday 1:00-3:00 PM. Bring your code and specific questions. No walk-in on May 9 (Faculty Meeting).', 0, 1, '2026-05-04 23:00:00', '2026-05-04 23:00:00', '2026-05-04 23:00:00'),
(4, 2, 36, '', 'Module 3 Lecture Video - JavaScript Events', 'The lecture video for Module 3 (JavaScript DOM and Events) is now uploaded. Watch before the Thursday session. Duration: 45 minutes. Focus on the difference between addEventListener and inline event handlers.', 0, 1, '2026-02-17 00:00:00', '2026-02-17 00:00:00', '2026-02-17 00:00:00'),
(5, 2, 36, '', 'Responsive Design Portfolio Examples', 'Here are 5 examples of outstanding student portfolio sites from previous semesters. Study their mobile layouts, typography choices, and use of whitespace. These are the quality bar for Project 2.', 1, 1, '2026-04-10 00:00:00', '2026-04-10 00:00:00', '2026-04-10 00:00:00'),
(6, 3, 36, '', 'Big-O Notation Reference Card', 'Quick reference for algorithm complexity: O(1) constant, O(log n) binary search, O(n) linear, O(n log n) merge sort, O(n?) bubble/insertion sort, O(2^n) exponential. Practice identifying these in code.', 1, 1, '2026-02-01 00:00:00', '2026-02-01 00:00:00', '2026-02-01 00:00:00'),
(7, 3, 36, '', 'Unit 3 Supplementary Reading - Graph Algorithms', 'Supplementary reading on Dijkstra\'s shortest path algorithm and minimum spanning trees (Prim\'s and Kruskal\'s). This is not in the textbook but will appear in the final exam as bonus questions.', 0, 1, '2026-03-12 00:00:00', '2026-03-12 00:00:00', '2026-03-12 00:00:00'),
(8, 4, 36, '', 'APA 7th Edition Citation Guide', 'All references in your capstone must follow APA 7th edition format. This guide covers journal articles, websites, and books. Pay attention to DOI formatting and author order. Use Zotero or Mendeley for reference management.', 1, 1, '2026-01-20 00:00:00', '2026-01-20 00:00:00', '2026-01-20 00:00:00'),
(9, 4, 36, 'announcement', 'Guest Speaker: Industry Researcher - May 14', 'We have a guest speaker next week: Dr. Paulo Buenaventura, a Senior Research Scientist at Accenture PH, will speak on \"AI-Powered Systems in Philippine Enterprise Settings\". Attendance is required and counts as a class activity.', 1, 1, '2026-05-09 00:00:00', '2026-05-09 00:00:00', '2026-05-09 00:00:00'),
(10, 6, 57, '', 'Cisco Packet Tracer Lab Files - Labs 1-3', 'All Packet Tracer lab starter files (.pkt) are now available in this post. Download and save them to your working folder. Open with Cisco Packet Tracer 8.2 or higher. Older versions may have rendering issues.', 1, 1, '2026-01-14 00:00:00', '2026-01-14 00:00:00', '2026-01-14 00:00:00'),
(11, 6, 57, '', 'Subnetting Quick Reference - IPv4', 'A one-page subnetting cheat sheet covering prefix lengths (/8 to /30), host count per subnet, and network/broadcast address formulas. Practice until you can subnet Class C in under 60 seconds.', 0, 1, '2026-02-05 00:00:00', '2026-02-05 00:00:00', '2026-02-05 00:00:00'),
(12, 7, 57, '', 'Sprint 1 SRS Template (IEEE Standard)', 'Attached is the IEEE Software Requirements Specification template for Sprint 1. Fill in all sections: Introduction, Overall Description, Specific Requirements (functional and non-functional), and Appendices.', 1, 1, '2026-02-24 00:00:00', '2026-02-24 00:00:00', '2026-02-24 00:00:00'),
(13, 7, 57, '', 'Recommended UML Tools for Sprint 2', 'Recommended free tools: draw.io (web-based, easiest), StarUML (desktop, feature-rich), PlantUML (text-based, great for version control). All are acceptable for submission. Export diagrams as PNG at 300dpi minimum.', 0, 1, '2026-03-30 00:00:00', '2026-03-30 00:00:00', '2026-03-30 00:00:00'),
(14, 9, 59, '', 'SQL Lab Environment Setup Guide', 'Use MySQL 8.0 with MySQL Workbench for all SQL labs. XAMPP with MariaDB is also acceptable. This guide covers installation, creating your lab database, and connecting with Workbench. Use the provided schema scripts.', 1, 1, '2026-01-16 00:00:00', '2026-01-16 00:00:00', '2026-01-16 00:00:00'),
(15, 9, 59, '', 'Normalization Worked Examples', 'Step-by-step normalization of a Hospital Appointment system from UNF to 3NF. Includes functional dependency identification, candidate key selection, and decomposition with lossless join verification.', 0, 1, '2026-02-27 00:00:00', '2026-02-27 00:00:00', '2026-02-27 00:00:00'),
(16, 13, 60, '', 'Statistics Review: Measures of Central Tendency', 'Lecture notes and worked examples covering mean, median, and mode. Includes when to use each measure, effects of outliers, and grouped frequency distribution calculations. Includes 15 practice problems with answers.', 0, 1, '2026-02-10 00:00:00', '2026-02-10 00:00:00', '2026-02-10 00:00:00'),
(17, 13, 60, 'announcement', 'Midterm Exam Details', 'Midterm exam is on March 20, 2026, Room 201. Coverage: Number Theory (Chapters 1-2), Logic and Propositions (Chapter 3), and Descriptive Statistics (Chapter 4). Bring a scientific calculator and student ID. No formula sheets allowed.', 1, 1, '2026-03-12 23:00:00', '2026-03-12 23:00:00', '2026-03-12 23:00:00'),
(18, 15, 36, '', 'Unit 2 Supplementary: Number System Converter', 'Download this spreadsheet tool that validates your binary/hexadecimal conversions. Enter the decimal number and check your answers. Use it to practice 10 conversions per day before the lab activity deadline.', 0, 1, '2026-02-06 00:00:00', '2026-02-06 00:00:00', '2026-02-06 00:00:00'),
(19, 15, 36, 'announcement', 'Welcome to IT 101!', 'Welcome everyone to Introduction to Computing! This is your foundation course for all IT/CS subjects. By the end of the semester, you will understand how computers work, how to think computationally, and write your first simple programs. Let\'s make this term great!', 1, 1, '2026-01-12 23:00:00', '2026-01-12 23:00:00', '2026-01-12 23:00:00'),
(20, 1, 36, 'announcement', 'Reminder: Finals Coverage and Format', 'The finals will be a 3-hour practical exam. You will be given a problem specification and must implement a Java solution demonstrating OOP principles. Bring your laptop fully charged. IDEs allowed: IntelliJ IDEA or Eclipse. No internet access during exam.', 1, 1, '2026-05-10 00:00:00', '2026-05-10 00:00:00', '2026-05-10 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_post_files`
--

CREATE TABLE `course_post_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_post_reads`
--

CREATE TABLE `course_post_reads` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `section_code` varchar(30) NOT NULL,
  `room` varchar(80) DEFAULT NULL,
  `schedule` varchar(150) DEFAULT NULL,
  `max_students` smallint(5) UNSIGNED NOT NULL DEFAULT 40,
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_id`, `term_id`, `instructor_id`, `section_code`, `room`, `schedule`, `max_students`, `status`, `created_at`) VALUES
(1, 1, 2, 36, 'BSIT-2A', NULL, NULL, 40, 'open', '2026-05-07 09:04:06'),
(2, 2, 2, 36, 'BSIT-2B', NULL, NULL, 40, 'open', '2026-05-07 09:04:06'),
(3, 3, 2, 36, 'BSCS-2A', NULL, NULL, 40, 'open', '2026-05-07 09:04:06'),
(4, 4, 2, 36, 'BSIT-4A', NULL, NULL, 30, '', '2026-05-07 09:04:06'),
(5, 5, 2, 36, 'IT103-BSIT-2A', NULL, NULL, 40, 'open', '2026-05-08 10:55:22'),
(6, 6, 2, 57, 'BSIT-3A-IT204', 'Room 301', 'TTh 07:30-09:00', 40, 'open', '2026-01-06 00:00:00'),
(7, 7, 2, 57, 'BSCS-3A-IT305', 'Room 302', 'MWF 09:00-10:00', 40, 'open', '2026-01-06 00:00:00'),
(8, 8, 2, 59, 'BSIT-3A-IT208', 'Room 303', 'TTh 10:30-12:00', 40, 'open', '2026-01-06 00:00:00'),
(9, 9, 2, 59, 'BSIT-3B-IT310', 'Room 304', 'MWF 13:00-14:00', 40, 'open', '2026-01-06 00:00:00'),
(10, 10, 2, 58, 'BSCS-3B-IT315', 'Room 305', 'TTh 13:30-15:00', 40, 'open', '2026-01-06 00:00:00'),
(11, 11, 2, 58, 'BSIT-4A-IT320', 'Room 306', 'MWF 07:30-08:30', 35, 'open', '2026-01-06 00:00:00'),
(12, 12, 2, 57, 'BSCS-4A-IT412', 'Room 307', 'TTh 15:00-16:30', 30, 'open', '2026-01-06 00:00:00'),
(13, 13, 2, 60, 'BSIT-1A-GE001', 'Room 201', 'MWF 10:00-11:00', 45, 'open', '2026-01-06 00:00:00'),
(14, 14, 2, 60, 'BSIT-1B-GE005', 'Room 202', 'TTh 07:30-09:00', 45, 'open', '2026-01-06 00:00:00'),
(15, 15, 2, 36, 'BSIT-1A-IT101', 'Room 101', 'MWF 13:00-14:00', 45, 'open', '2026-01-06 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `head_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `description`, `head_user_id`, `created_at`) VALUES
(1, 'CSS', 'College of Computer Studies', '', NULL, '2026-05-03 20:05:33'),
(2, 'COED', 'College of Education', '', NULL, '2026-05-03 20:05:33'),
(3, 'CBA', 'College of Business and Accountancy', '', NULL, '2026-05-03 20:05:33'),
(4, 'CAS', 'College of Arts and Sciences', '', NULL, '2026-05-03 20:05:33'),
(5, 'CHM', 'College of Hospitality Management', '', NULL, '2026-05-04 09:43:02');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `status` enum('enrolled','dropped','completed','failed') NOT NULL DEFAULT 'enrolled',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `section_id`, `status`, `final_grade`, `enrolled_at`, `updated_at`) VALUES
(1, 37, 1, 'enrolled', NULL, '2026-01-10 00:00:00', '2026-05-08 10:54:34'),
(2, 38, 1, 'enrolled', NULL, '2026-01-10 00:05:00', '2026-05-08 10:54:34'),
(3, 39, 1, 'enrolled', NULL, '2026-01-10 00:10:00', '2026-05-08 10:54:34'),
(4, 40, 1, 'enrolled', NULL, '2026-01-10 00:15:00', '2026-05-08 10:54:34'),
(5, 41, 1, 'enrolled', NULL, '2026-01-10 00:20:00', '2026-05-08 10:54:34'),
(6, 42, 1, 'enrolled', NULL, '2026-01-10 00:25:00', '2026-05-08 10:54:34'),
(7, 43, 1, 'enrolled', NULL, '2026-01-10 00:30:00', '2026-05-08 10:54:34'),
(8, 44, 1, 'enrolled', NULL, '2026-01-10 00:35:00', '2026-05-08 10:54:34'),
(9, 45, 1, 'enrolled', NULL, '2026-01-10 00:40:00', '2026-05-08 10:54:34'),
(10, 46, 1, 'enrolled', NULL, '2026-01-10 00:45:00', '2026-05-08 10:54:34'),
(11, 37, 2, 'enrolled', NULL, '2026-01-10 01:00:00', '2026-05-08 10:54:34'),
(12, 38, 2, 'enrolled', NULL, '2026-01-10 01:05:00', '2026-05-08 10:54:34'),
(13, 39, 2, 'enrolled', NULL, '2026-01-10 01:10:00', '2026-05-08 10:54:34'),
(14, 43, 2, 'enrolled', NULL, '2026-01-10 01:15:00', '2026-05-08 10:54:34'),
(15, 52, 2, 'enrolled', NULL, '2026-01-10 01:20:00', '2026-05-08 10:54:34'),
(16, 53, 2, 'enrolled', NULL, '2026-01-10 01:25:00', '2026-05-08 10:54:34'),
(17, 54, 2, 'enrolled', NULL, '2026-01-10 01:30:00', '2026-05-08 10:54:34'),
(18, 44, 2, 'enrolled', NULL, '2026-01-10 01:35:00', '2026-05-08 10:54:34'),
(19, 45, 2, 'enrolled', NULL, '2026-01-10 01:40:00', '2026-05-08 10:54:34'),
(20, 41, 2, 'enrolled', NULL, '2026-01-10 01:45:00', '2026-05-08 10:54:34'),
(21, 47, 3, 'enrolled', NULL, '2026-01-10 02:00:00', '2026-05-08 10:54:34'),
(22, 48, 3, 'enrolled', NULL, '2026-01-10 02:05:00', '2026-05-08 10:54:34'),
(23, 49, 3, 'enrolled', NULL, '2026-01-10 02:10:00', '2026-05-08 10:54:34'),
(24, 50, 3, 'enrolled', NULL, '2026-01-10 02:15:00', '2026-05-08 10:54:34'),
(25, 51, 3, 'enrolled', NULL, '2026-01-10 02:20:00', '2026-05-08 10:54:34'),
(26, 42, 3, 'enrolled', NULL, '2026-01-10 02:25:00', '2026-05-08 10:54:34'),
(27, 46, 3, 'enrolled', NULL, '2026-01-10 02:30:00', '2026-05-08 10:54:34'),
(28, 40, 3, 'enrolled', NULL, '2026-01-10 02:35:00', '2026-05-08 10:54:34'),
(29, 55, 4, 'enrolled', NULL, '2026-01-10 03:00:00', '2026-05-08 10:54:34'),
(30, 56, 4, 'enrolled', NULL, '2026-01-10 03:05:00', '2026-05-08 10:54:34'),
(31, 48, 4, 'enrolled', NULL, '2026-01-10 03:10:00', '2026-05-08 10:54:34'),
(32, 37, 4, 'enrolled', NULL, '2026-01-10 03:15:00', '2026-05-08 10:54:34'),
(33, 38, 4, 'enrolled', NULL, '2026-01-10 03:20:00', '2026-05-08 10:54:34'),
(34, 61, 6, 'enrolled', NULL, '2026-01-09 20:00:00', '2026-01-09 20:00:00'),
(35, 62, 6, 'enrolled', NULL, '2026-01-09 20:05:00', '2026-01-09 20:05:00'),
(36, 63, 6, 'enrolled', NULL, '2026-01-09 20:10:00', '2026-01-09 20:10:00'),
(37, 64, 6, 'enrolled', NULL, '2026-01-09 20:15:00', '2026-01-09 20:15:00'),
(38, 65, 6, 'enrolled', NULL, '2026-01-09 20:20:00', '2026-01-09 20:20:00'),
(39, 83, 6, 'enrolled', NULL, '2026-01-09 20:25:00', '2026-01-09 20:25:00'),
(40, 84, 6, 'enrolled', NULL, '2026-01-09 20:30:00', '2026-01-09 20:30:00'),
(41, 87, 6, 'enrolled', NULL, '2026-01-09 20:35:00', '2026-01-09 20:35:00'),
(42, 73, 7, 'enrolled', NULL, '2026-01-09 21:00:00', '2026-01-09 21:00:00'),
(43, 74, 7, 'enrolled', NULL, '2026-01-09 21:05:00', '2026-01-09 21:05:00'),
(44, 89, 7, 'enrolled', NULL, '2026-01-09 21:10:00', '2026-01-09 21:10:00'),
(45, 95, 7, 'enrolled', NULL, '2026-01-09 21:15:00', '2026-01-09 21:15:00'),
(46, 64, 7, 'enrolled', NULL, '2026-01-09 21:20:00', '2026-01-09 21:20:00'),
(47, 65, 7, 'enrolled', NULL, '2026-01-09 21:25:00', '2026-01-09 21:25:00'),
(48, 69, 8, 'enrolled', NULL, '2026-01-09 22:00:00', '2026-01-09 22:00:00'),
(49, 70, 8, 'enrolled', NULL, '2026-01-09 22:05:00', '2026-01-09 22:05:00'),
(50, 81, 8, 'enrolled', NULL, '2026-01-09 22:10:00', '2026-01-09 22:10:00'),
(51, 82, 8, 'enrolled', NULL, '2026-01-09 22:15:00', '2026-01-09 22:15:00'),
(52, 85, 8, 'enrolled', NULL, '2026-01-09 22:20:00', '2026-01-09 22:20:00'),
(53, 86, 8, 'enrolled', NULL, '2026-01-09 22:25:00', '2026-01-09 22:25:00'),
(54, 61, 9, 'enrolled', NULL, '2026-01-09 23:00:00', '2026-01-09 23:00:00'),
(55, 73, 9, 'enrolled', NULL, '2026-01-09 23:05:00', '2026-01-09 23:05:00'),
(56, 83, 9, 'enrolled', NULL, '2026-01-09 23:10:00', '2026-01-09 23:10:00'),
(57, 90, 9, 'enrolled', NULL, '2026-01-09 23:15:00', '2026-01-09 23:15:00'),
(58, 92, 9, 'enrolled', NULL, '2026-01-09 23:20:00', '2026-01-09 23:20:00'),
(59, 70, 10, 'enrolled', NULL, '2026-01-10 00:00:00', '2026-01-10 00:00:00'),
(60, 71, 10, 'enrolled', NULL, '2026-01-10 00:05:00', '2026-01-10 00:05:00'),
(61, 72, 10, 'enrolled', NULL, '2026-01-10 00:10:00', '2026-01-10 00:10:00'),
(62, 74, 10, 'enrolled', NULL, '2026-01-10 00:15:00', '2026-01-10 00:15:00'),
(63, 95, 10, 'enrolled', NULL, '2026-01-10 00:20:00', '2026-01-10 00:20:00'),
(64, 76, 11, 'enrolled', NULL, '2026-01-10 01:00:00', '2026-01-10 01:00:00'),
(65, 77, 11, 'enrolled', NULL, '2026-01-10 01:05:00', '2026-01-10 01:05:00'),
(66, 91, 11, 'enrolled', NULL, '2026-01-10 01:10:00', '2026-01-10 01:10:00'),
(67, 92, 11, 'enrolled', NULL, '2026-01-10 01:15:00', '2026-01-10 01:15:00'),
(68, 77, 12, 'enrolled', NULL, '2026-01-10 02:00:00', '2026-01-10 02:00:00'),
(69, 78, 12, 'enrolled', NULL, '2026-01-10 02:05:00', '2026-01-10 02:05:00'),
(70, 91, 12, 'enrolled', NULL, '2026-01-10 02:10:00', '2026-01-10 02:10:00'),
(71, 93, 12, 'enrolled', NULL, '2026-01-10 02:15:00', '2026-01-10 02:15:00'),
(72, 66, 13, 'enrolled', NULL, '2026-01-10 03:00:00', '2026-01-10 03:00:00'),
(73, 67, 13, 'enrolled', NULL, '2026-01-10 03:05:00', '2026-01-10 03:05:00'),
(74, 68, 13, 'enrolled', NULL, '2026-01-10 03:10:00', '2026-01-10 03:10:00'),
(75, 79, 13, 'enrolled', NULL, '2026-01-10 03:15:00', '2026-01-10 03:15:00'),
(76, 80, 13, 'enrolled', NULL, '2026-01-10 03:20:00', '2026-01-10 03:20:00'),
(77, 93, 13, 'enrolled', NULL, '2026-01-10 03:25:00', '2026-01-10 03:25:00'),
(78, 94, 13, 'enrolled', NULL, '2026-01-10 03:30:00', '2026-01-10 03:30:00'),
(79, 66, 14, 'enrolled', NULL, '2026-01-10 04:00:00', '2026-01-10 04:00:00'),
(80, 67, 14, 'enrolled', NULL, '2026-01-10 04:05:00', '2026-01-10 04:05:00'),
(81, 79, 14, 'enrolled', NULL, '2026-01-10 04:10:00', '2026-01-10 04:10:00'),
(82, 80, 14, 'enrolled', NULL, '2026-01-10 04:15:00', '2026-01-10 04:15:00'),
(83, 94, 14, 'enrolled', NULL, '2026-01-10 04:20:00', '2026-01-10 04:20:00'),
(84, 66, 15, 'enrolled', NULL, '2026-01-10 05:00:00', '2026-01-10 05:00:00'),
(85, 67, 15, 'enrolled', NULL, '2026-01-10 05:05:00', '2026-01-10 05:05:00'),
(86, 68, 15, 'enrolled', NULL, '2026-01-10 05:10:00', '2026-01-10 05:10:00'),
(87, 79, 15, 'enrolled', NULL, '2026-01-10 05:15:00', '2026-01-10 05:15:00'),
(88, 87, 15, 'enrolled', NULL, '2026-01-10 05:20:00', '2026-01-10 05:20:00'),
(89, 88, 15, 'enrolled', NULL, '2026-01-10 05:25:00', '2026-01-10 05:25:00');

--
-- Triggers `enrollments`
--
DELIMITER $$
CREATE TRIGGER `trg_enrollments_after_insert` AFTER INSERT ON `enrollments` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NEW.student_id, 'enrollment_created', 'enrollments', NEW.id,



          JSON_OBJECT('student_id', NEW.student_id, 'section_id', NEW.section_id));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_enrollments_after_update` AFTER UPDATE ON `enrollments` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status OR OLD.final_grade <> NEW.final_grade THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.student_id, 'enrollment_updated', 'enrollments', NEW.id,



            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,



                        'old_grade',  OLD.final_grade, 'new_grade', NEW.final_grade));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE `forums` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forums`
--

INSERT INTO `forums` (`id`, `section_id`, `title`, `description`, `is_locked`, `created_at`) VALUES
(1, 1, 'IT 106 OOP General Discussion', 'Ask questions and discuss topics related to Object-Oriented Programming.', 0, '2026-05-08 10:54:34'),
(2, 2, 'IT 301 Web Programming Discussion', 'Discussion board for Web Programming topics and project help.', 0, '2026-05-08 10:54:34'),
(3, 3, 'IT 201 Data Structures Discussion', 'Discuss algorithms, data structures, and problem-solving approaches.', 0, '2026-05-08 10:54:34'),
(4, 4, 'IT 411 Capstone Forum', 'Research discussions, proposal feedback, and capstone project updates.', 0, '2026-05-08 10:54:34'),
(6, 6, 'IT 204 Computer Networks Discussion', 'Ask questions about networking concepts, labs, and configurations.', 0, '2026-01-06 00:00:00'),
(7, 7, 'IT 305 Software Engineering Discussion', 'Discussion on SDLC models, UML, agile practices, and project management.', 0, '2026-01-06 00:00:00'),
(8, 9, 'IT 310 Database Systems Discussion', 'SQL queries, normalization, and database design questions here.', 0, '2026-01-06 00:00:00'),
(9, 11, 'IT 320 HCI Discussion', 'Discuss UI/UX principles, usability testing, and design tools like Figma.', 0, '2026-01-06 00:00:00'),
(10, 13, 'GE 001 Math in the Modern World', 'Q&A for mathematics topics, statistics problems, and concept clarifications.', 0, '2026-01-06 00:00:00'),
(11, 15, 'IT 101 Introduction to Computing', 'General Q&A for beginners. No question is too basic here!', 0, '2026-01-06 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `parent_reply_id` int(10) UNSIGNED DEFAULT NULL,
  `body` longtext NOT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `upvotes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_replies`
--

INSERT INTO `forum_replies` (`id`, `thread_id`, `author_id`, `parent_reply_id`, `body`, `is_flagged`, `upvotes`, `created_at`, `updated_at`) VALUES
(2, 1, 40, NULL, 'The Singleton pattern is used in Java\'s Runtime class - there\'s only one JVM runtime per application. For Factory, think of a PaymentProcessorFactory that returns either a CreditCardProcessor or a GCashProcessor based on input.', 0, 8, '2026-05-05 01:00:00', '2026-05-05 01:00:00'),
(3, 1, 36, NULL, 'For Observer pattern, think of event listeners in JavaScript. When you add an event listener to a button click, the button (subject) notifies all listeners (observers) when clicked. Java\'s PropertyChangeListener is a classic example.', 0, 12, '2026-05-05 02:00:00', '2026-05-05 02:00:00'),
(4, 2, 37, NULL, 'I\'ve found CSS Grid most effective for page-level layouts (header, sidebar, main, footer) and Flexbox for component-level layouts (navigation items, card collections). Using both together is very powerful.', 0, 10, '2026-05-06 01:00:00', '2026-05-06 01:00:00'),
(5, 2, 54, NULL, 'Always design mobile-first - start with the smallest screen and add breakpoints upward. It\'s much easier than trying to shrink a desktop layout down. Also, use rem units instead of px for font sizes.', 0, 7, '2026-05-06 02:00:00', '2026-05-06 02:00:00'),
(6, 3, 36, NULL, 'Recursion is cleaner for problems with naturally recursive structure like tree traversal or quicksort. Iteration is more memory-efficient for simple loops since recursion adds stack frames. For deep recursion, prefer iteration to avoid StackOverflowError.', 0, 15, '2026-05-04 00:00:00', '2026-05-04 00:00:00'),
(7, 3, 50, NULL, 'I ran a benchmark for fibonacci - iterative was 10x faster than naive recursive for large inputs. But memoized recursion (dynamic programming) was comparable to iterative. So it depends on whether you cache results.', 0, 6, '2026-05-04 01:00:00', '2026-05-04 01:00:00'),
(8, 5, 39, NULL, 'Check if your grid template columns are set with fr units or fixed pixel values. Fixed px won\'t shrink. Also make sure your media query is inside the stylesheet and not overridden by another rule with higher specificity.', 0, 9, '2026-05-06 06:00:00', '2026-05-06 06:00:00'),
(9, 5, 36, NULL, 'Try adding `display: block` inside your mobile media query to force the grid to collapse to single column. Also paste your CSS here and I can take a look at what\'s conflicting.', 0, 5, '2026-05-07 00:00:00', '2026-05-07 00:00:00'),
(10, 6, 46, NULL, 'My mental model: count how many times the main operation executes as input n grows. Single loop = O(n). Nested loops = O(n?). Halving the input each iteration (like binary search) = O(log n). Recursive calls that split input = O(n log n) often.', 0, 14, '2026-05-05 02:00:00', '2026-05-05 02:00:00'),
(12, 9, 36, NULL, 'Yes, you may use a CSS framework for styling only. The JavaScript must remain vanilla. Using Tailwind or Bootstrap for classes is fine as long as you write your own JS for interactivity. Note this in your README.', 0, 18, '2026-05-03 06:00:00', '2026-05-03 06:00:00'),
(13, 9, 53, NULL, 'Thanks for the clarification! I\'ll use Tailwind for responsiveness and write clean vanilla JS for the CRUD operations. Will mention it in the README as instructed.', 0, 3, '2026-05-03 07:00:00', '2026-05-03 07:00:00'),
(14, 11, 48, NULL, 'Think of in-order as: LEFT \Z ROOT \Z RIGHT. The recursion unwinds like a call stack. The deepest left call returns first, then its parent prints itself, then goes right. Draw a small BST and trace each call manually - it clicks instantly.', 0, 11, '2026-04-20 06:00:00', '2026-04-20 06:00:00'),
(15, 12, 57, NULL, 'The most common cause is a missing `ip helper-address` command on the router interface. If the DHCP server is on a different network segment than the clients, the router needs to forward DHCP broadcast requests. Add: `ip helper-address <DHCP_server_IP>` on the client-facing interface.', 0, 16, '2026-03-15 06:00:00', '2026-03-15 06:00:00'),
(16, 12, 63, NULL, 'That was it! I added the helper-address command and the PCs started receiving proper IPs from the pool. Thank you so much!', 0, 5, '2026-03-15 08:00:00', '2026-03-15 08:00:00'),
(17, 14, 57, NULL, 'For a Library Management System with clear, stable requirements, Waterfall is defensible. However, Agile (Scrum) lets you deliver usable features in sprints and adapt when stakeholder needs change. For academic purposes, Scrum is strongly recommended as it teaches iteration.', 0, 9, '2026-02-10 06:00:00', '2026-02-10 06:00:00'),
(18, 15, 59, NULL, 'A VIEW is essentially a saved SELECT query - good for simplifying complex joins you query frequently. A Stored Procedure can contain full logic: INSERT, UPDATE, loops, conditionals. Use VIEWs for read-only abstractions and stored procedures for business logic that modifies data.', 0, 13, '2026-03-20 06:00:00', '2026-03-20 06:00:00'),
(19, 17, 58, NULL, 'Figma is the industry standard and free for education. Balsamiq is great for low-fidelity wireframes quickly. For this course, Figma is preferred since you can create both wireframes and high-fidelity prototypes in one tool. Either is acceptable but Figma is recommended.', 0, 8, '2026-03-10 06:00:00', '2026-03-10 06:00:00'),
(20, 18, 60, NULL, 'Example: Average salaries in a company where the CEO earns 10M and 100 employees earn 25K. The mean would be skewed high by the CEO\'s salary and not represent most employees. The median (middle value) better represents what a \"typical\" employee earns. Always check for outliers first.', 0, 12, '2026-02-25 06:00:00', '2026-02-25 06:00:00'),
(21, 19, 36, NULL, 'Binary is fundamental at the hardware level - all transistors operate as binary switches (on/off = 1/0). Machine code, ASCII/Unicode encoding, image pixels (RGB values), network MAC addresses, and even file permissions in Linux use binary. It\'s literally the language of computers.', 0, 17, '2026-02-08 06:00:00', '2026-02-08 06:00:00'),
(22, 21, 36, NULL, 'hi', 0, 0, '2026-05-12 04:52:19', '2026-05-12 04:52:19'),
(23, 22, 37, NULL, 'hi mam', 0, 0, '2026-05-16 01:50:49', '2026-05-16 01:50:49');

--
-- Triggers `forum_replies`
--
DELIMITER $$
CREATE TRIGGER `trg_forum_replies_after_update` AFTER UPDATE ON `forum_replies` FOR EACH ROW BEGIN



  IF OLD.is_flagged <> NEW.is_flagged THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL,



            IF(NEW.is_flagged = 1, 'reply_flagged', 'reply_unflagged'),



            'forum_replies', NEW.id,



            JSON_OBJECT('thread_id', NEW.thread_id));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_threads`
--

CREATE TABLE `forum_threads` (
  `id` int(10) UNSIGNED NOT NULL,
  `forum_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(300) NOT NULL,
  `body` longtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_threads`
--

INSERT INTO `forum_threads` (`id`, `forum_id`, `author_id`, `title`, `body`, `is_pinned`, `is_locked`, `is_flagged`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 1, 42, 'Design Patterns in OOP - Real World Examples', 'I\'ve been reading about design patterns but I\'m struggling to connect them to real-world scenarios. Can anyone share concrete examples of the Factory, Observer, or Singleton patterns in actual applications?', 0, 0, 0, 45, '2026-05-05 01:30:00', '2026-05-08 10:54:34'),
(2, 2, 36, 'Best Practices for Responsive Web Design', 'Let\'s discuss best practices for building responsive layouts. What techniques, frameworks, or CSS features have you found most effective in your projects?', 1, 0, 0, 62, '2026-05-06 02:00:00', '2026-05-08 10:54:34'),
(3, 3, 47, 'Recursion vs Iteration - Which is Better?', 'We covered both recursion and iteration in class. When is it better to use one over the other? Is there always a performance difference?', 0, 0, 0, 58, '2026-05-04 06:00:00', '2026-05-08 10:54:34'),
(4, 1, 36, 'Midterm Exam Clarifications & FAQ', 'Please post all your clarifications about the midterm exam here. I\'ll answer each one. Topics covered: Inheritance, Polymorphism, Interfaces, and Abstract Classes.', 1, 0, 0, 120, '2026-05-02 00:00:00', '2026-05-08 10:54:34'),
(5, 2, 37, 'CSS Grid not working on mobile - help!', 'I\'m building my portfolio project and my CSS grid layout breaks on screens below 480px. I\'ve tried adding a media query but it doesn\'t seem to override the desktop layout. Any advice?', 0, 0, 0, 33, '2026-05-06 06:30:00', '2026-05-08 10:54:34'),
(6, 3, 48, 'Understanding Big-O Notation Practically', 'I understand the theory of Big-O but I\'m struggling to apply it when analyzing my own code. What mental model do you use to estimate the time complexity of a loop or recursive function?', 0, 0, 0, 41, '2026-05-05 08:00:00', '2026-05-08 10:54:34'),
(8, 1, 36, 'Study Guide: OOP Finals Coverage', 'Here is the final exam coverage: OOP Principles, Inheritance, Polymorphism, Interfaces, Exception Handling, and Collections. Focus on applied problems not just definitions.', 1, 0, 0, 210, '2026-04-30 23:00:00', '2026-04-30 23:00:00'),
(9, 2, 53, 'Can we use a CSS framework for Project 3?', 'The instructions say to use vanilla JS but it doesn\'t mention CSS frameworks. Can we use Tailwind or Bootstrap for styling the front-end?', 0, 0, 0, 55, '2026-05-03 01:00:00', '2026-05-03 01:00:00'),
(10, 2, 36, 'Important: Project 3 Final Rubric', 'The final rubric for Project 3 is now set. 40pts functionality, 30pts UI/UX, 20pts code quality, 10pts documentation. Use the attached template for the README file.', 1, 0, 0, 195, '2026-05-04 00:00:00', '2026-05-04 00:00:00'),
(11, 3, 50, 'Confused about recursive tree traversal', 'I understand how to traverse iteratively but recursive in-order traversal is confusing me. When exactly does the recursion \"unwind\"? A diagram would help.', 0, 0, 0, 62, '2026-04-20 02:00:00', '2026-04-20 02:00:00'),
(12, 6, 63, 'Packet Tracer: DHCP not assigning IPs', 'I configured a DHCP server in Packet Tracer but the PCs are getting APIPA addresses (169.254.x.x) instead of addresses from the pool. What am I missing?', 0, 0, 0, 47, '2026-03-15 01:00:00', '2026-03-15 01:00:00'),
(13, 6, 57, 'Lab 3 Tips and Common Mistakes', 'For Lab 3 OSPF config, make sure you declare the correct wildcard mask and enable OSPF on the correct interfaces. A common mistake is using subnet mask instead of wildcard.', 1, 0, 0, 130, '2026-04-07 23:00:00', '2026-04-07 23:00:00'),
(14, 7, 73, 'Choosing between Agile and Waterfall for Sprint 1', 'Our group is debating which SDLC to use for our project. Our project is a Library Management System. Which methodology fits better and why?', 0, 0, 0, 44, '2026-02-10 02:00:00', '2026-02-10 02:00:00'),
(15, 8, 83, 'Difference between VIEW and stored procedure?', 'Both seem to encapsulate SQL logic. When do you use a VIEW versus a stored procedure? Are there performance implications?', 0, 0, 0, 71, '2026-03-20 01:00:00', '2026-03-20 01:00:00'),
(16, 8, 59, 'SQL Lab 3 Reminder and Tips', 'For Lab 3, focus on AFTER INSERT triggers for audit logs and make sure your stored procedures handle NULL inputs gracefully. Use DELIMITER correctly in your script.', 1, 0, 0, 155, '2026-04-21 23:00:00', '2026-04-21 23:00:00'),
(17, 9, 76, 'What tools to use for UX wireframing?', 'We were asked to create wireframes for our HCI project. The instructor mentioned Figma but is Balsamiq or Adobe XD acceptable? Any recommendations?', 0, 0, 0, 39, '2026-03-10 02:00:00', '2026-03-10 02:00:00'),
(18, 10, 93, 'Mean vs Median - when to use which?', 'In our statistics module, the professor said to use median for skewed data. Can someone explain with a real-world example? I\'m still confused.', 0, 0, 0, 33, '2026-02-25 03:00:00', '2026-02-25 03:00:00'),
(19, 11, 66, 'Is binary still used in modern computing?', 'We learned about binary in Unit 1. I\'m curious - where is binary actually used in modern hardware and software? I know CPUs use it but can someone elaborate?', 0, 0, 0, 52, '2026-02-08 01:00:00', '2026-02-08 01:00:00'),
(20, 11, 36, 'Finals Review: Key Concepts from Unit 1 and 2', 'For the finals, focus on: computer history timeline, binary/hex conversions, and the fetch-decode-execute cycle. Practice all conversion problems from the worksheets.', 1, 0, 0, 180, '2026-05-07 23:00:00', '2026-05-07 23:00:00'),
(21, 1, 36, 'test discussion', 'a', 0, 0, 0, 1, '2026-05-12 04:52:06', '2026-05-12 04:52:19'),
(22, 1, 36, 'discussion 1', 'discussion 1', 0, 0, 0, 0, '2026-05-16 01:45:58', '2026-05-16 01:45:58');

--
-- Triggers `forum_threads`
--
DELIMITER $$
CREATE TRIGGER `trg_forum_threads_after_update` AFTER UPDATE ON `forum_threads` FOR EACH ROW BEGIN



  IF OLD.is_flagged <> NEW.is_flagged THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL,



            IF(NEW.is_flagged = 1, 'thread_flagged', 'thread_unflagged'),



            'forum_threads', NEW.id,



            JSON_OBJECT('forum_id', NEW.forum_id, 'title', NEW.title));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `gradebook_items`
--

CREATE TABLE `gradebook_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `component_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('assignment','quiz') NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_components`
--

CREATE TABLE `grade_components` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight_pct` decimal(5,2) NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_components`
--

INSERT INTO `grade_components` (`id`, `section_id`, `name`, `weight_pct`, `sort_order`) VALUES
(1, 1, 'Quizzes', 20.00, 1),
(2, 1, 'Laboratory', 30.00, 2),
(3, 1, 'Midterm Exam', 25.00, 3),
(4, 1, 'Finals Exam', 25.00, 4),
(5, 2, 'Quizzes', 20.00, 1),
(6, 2, 'Projects', 40.00, 2),
(7, 2, 'Midterm Exam', 20.00, 3),
(8, 2, 'Finals Exam', 20.00, 4),
(9, 3, 'Quizzes', 25.00, 1),
(10, 3, 'Problem Sets', 35.00, 2),
(11, 3, 'Midterm Exam', 20.00, 3),
(12, 3, 'Finals Exam', 20.00, 4),
(13, 4, 'Submissions', 40.00, 1),
(14, 4, 'Oral Defense', 30.00, 2),
(15, 4, 'Written Report', 30.00, 3),
(16, 6, 'Quizzes', 20.00, 1),
(17, 6, 'Laboratory', 40.00, 2),
(18, 6, 'Midterm Exam', 20.00, 3),
(19, 6, 'Finals Exam', 20.00, 4),
(20, 7, 'Quizzes', 15.00, 1),
(21, 7, 'Sprint Output', 45.00, 2),
(22, 7, 'Midterm Exam', 20.00, 3),
(23, 7, 'Finals Exam', 20.00, 4),
(24, 9, 'Quizzes', 20.00, 1),
(25, 9, 'Laboratory', 40.00, 2),
(26, 9, 'Midterm Exam', 20.00, 3),
(27, 9, 'Finals Exam', 20.00, 4),
(28, 15, 'Quizzes', 20.00, 1),
(29, 15, 'Lab Activities', 30.00, 2),
(30, 15, 'Midterm Exam', 25.00, 3),
(31, 15, 'Finals Exam', 25.00, 4);

-- --------------------------------------------------------

--
-- Table structure for table `instructor_profiles`
--

CREATE TABLE `instructor_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructor_profiles`
--

INSERT INTO `instructor_profiles` (`user_id`, `employee_id`, `department_id`, `designation`, `specialization`) VALUES
(36, 'EMP-2024-036', 1, 'Instructor I', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `lesson_type` enum('reading','video','audio','slide','link','scorm') NOT NULL DEFAULT 'reading',
  `content` longtext DEFAULT NULL,
  `resource_url` varchar(500) DEFAULT NULL,
  `duration_min` smallint(5) UNSIGNED DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `module_id`, `title`, `lesson_type`, `content`, `resource_url`, `duration_min`, `sort_order`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, 'Introduction to Java Classes', 'reading', 'Core concepts of Java class definition, fields, and methods.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(2, 1, 'Lab Demo: Your First Java Class (Video)', 'video', 'Walkthrough of creating a basic Student class.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(3, 2, 'Inheritance in Java - Slides', 'slide', 'PowerPoint covering extends keyword and super() calls.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(4, 2, 'Polymorphism Examples', 'reading', 'Text guide on method overriding vs overloading.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(5, 3, 'Interfaces vs Abstract Classes', 'reading', 'When to use interface vs abstract class with real examples.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(6, 3, 'Lab: Shape Hierarchy Implementation', 'reading', 'Step-by-step guide to implementing a shape class hierarchy.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(7, 4, 'Exception Handling - Slides', 'slide', 'PowerPoint deck covering try-catch-finally patterns.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(8, 4, 'Custom Exception Workshop (Video)', 'video', 'Live coding session creating custom exception classes.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(9, 5, 'HTML5 Semantic Tags Reference', 'reading', 'Complete guide to header, nav, section, article, aside, footer.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(10, 5, 'CSS Variables and Custom Properties', 'reading', 'How to use :root variables to build consistent design systems.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(11, 6, 'CSS Grid Complete Guide', 'reading', 'Comprehensive guide to grid-template-columns, rows, and areas.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(12, 6, 'Flexbox vs Grid Decision Cheatsheet', 'reading', 'When to use Flexbox vs Grid - practical decision guide.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(13, 7, 'Arrays in Java - Detailed Notes', 'reading', 'Single and multidimensional arrays, ArrayList, and time complexities.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(14, 7, 'Linked List Implementation Guide', 'reading', 'Singly linked list from scratch with insert, delete, search.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(15, 8, 'Research Problem Formulation', 'reading', 'How to identify, frame, and articulate a research problem.', NULL, NULL, 1, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(16, 8, 'Literature Review Writing Guide', 'reading', 'How to structure a literature review for a capstone proposal.', NULL, NULL, 2, 1, '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(17, 9, 'mod_1_9_1778240312.pdf', 'slide', NULL, 'uploads/modules/mod_2_9_1778481112.pdf', NULL, 1, 1, '2026-05-11 06:31:52', '2026-05-11 06:31:52'),
(18, 10, 'mod_1_9_1778240312.pdf', 'slide', NULL, 'uploads/modules/mod_5_10_1778489191.pdf', NULL, 1, 1, '2026-05-11 08:46:31', '2026-05-11 08:46:31'),
(19, 11, '12 - IT104 - IPT1.pptx', 'slide', NULL, 'uploads/modules/mod_1_11_1778492384.pptx', NULL, 1, 1, '2026-05-11 09:39:44', '2026-05-11 09:39:44');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_sec` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

CREATE TABLE `media_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `uploader_id` int(10) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size_kb` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_path` varchar(500) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_files`
--

INSERT INTO `media_files` (`id`, `uploader_id`, `original_name`, `stored_name`, `mime_type`, `file_size_kb`, `file_path`, `is_public`, `uploaded_at`) VALUES
(1, 36, 'mod_1_9_1778240312.pdf', 'mod_2_9_1778481112.pdf', 'application/pdf', 310, 'uploads/modules/mod_2_9_1778481112.pdf', 1, '2026-05-11 06:31:52'),
(2, 36, 'dicebg.jpg', 'avatar_36_1778481213.jpg', 'image/jpeg', 27, 'uploads/avatars/avatar_36_1778481213.jpg', 1, '2026-05-11 06:33:33'),
(3, 36, 'mod_1_9_1778240312.pdf', 'mod_5_10_1778489191.pdf', 'application/pdf', 310, 'uploads/modules/mod_5_10_1778489191.pdf', 1, '2026-05-11 08:46:31'),
(4, 36, '12 - IT104 - IPT1.pptx', 'mod_1_11_1778492384.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 10415, 'uploads/modules/mod_1_11_1778492384.pptx', 1, '2026-05-11 09:39:44'),
(5, 36, 'toro inoue classroom.jpg', 'avatar_36_1778497754.jpg', 'image/jpeg', 59, 'uploads/avatars/avatar_36_1778497754.jpg', 1, '2026-05-11 11:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `section_id`, `title`, `description`, `sort_order`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Week 1-2: Introduction to OOP', 'Covers classes, objects, attributes, methods, and basic OOP principles.', 1, 1, '2026-01-13 00:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(2, 1, 'Week 3-4: Inheritance and Polymorphism', 'Deep dive into inheritance hierarchies, method overriding, and polymorphism.', 2, 1, '2026-01-27 00:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(3, 1, 'Week 5-6: Interfaces and Abstract Classes', 'Understanding contracts, abstract methods, and default implementations.', 3, 1, '2026-02-10 00:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(4, 1, 'Week 7-8: Exception Handling', 'Covers try-catch-finally, checked/unchecked exceptions, and custom exceptions.', 4, 1, '2026-02-24 00:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(5, 2, 'Module 1: HTML5 & CSS3 Fundamentals', 'Semantic HTML, CSS specificity, the box model, and CSS variables.', 1, 1, '2026-01-13 01:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(6, 2, 'Module 2: Flexbox and CSS Grid', 'Modern layout techniques for responsive and complex page designs.', 2, 1, '2026-02-03 01:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(7, 3, 'Unit 1: Arrays and Linked Lists', 'Data structure fundamentals: arrays, singly/doubly linked lists.', 1, 1, '2026-01-14 02:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(8, 4, 'Chapter 1: Research Methodology', 'Introduction to research methods, problem identification, and proposal writing.', 1, 1, '2026-01-15 03:00:00', '2026-05-08 10:54:34', '2026-05-08 10:54:34'),
(9, 2, 'introduction', 'hello testing', 3, 1, '2026-05-11 06:31:52', '2026-05-11 06:31:52', '2026-05-11 06:31:52'),
(10, 5, 's', 's', 1, 1, '2026-05-11 08:46:31', '2026-05-11 08:46:31', '2026-05-11 08:46:31'),
(11, 1, 'Week 6', 'my module', 5, 1, '2026-05-11 09:39:44', '2026-05-11 09:39:44', '2026-05-11 09:39:44'),
(12, 1, 'Week 11-12: File I/O and Serialization', 'Reading/writing files, ObjectInputStream/OutputStream, and data persistence.', 6, 1, '2026-03-23 16:00:00', '2026-03-23 00:00:00', '2026-03-23 00:00:00'),
(13, 2, 'Module 3: JavaScript Fundamentals', 'Variables, functions, DOM manipulation, events, and ES6+ features.', 3, 1, '2026-02-16 17:00:00', '2026-02-16 00:00:00', '2026-02-16 00:00:00'),
(14, 2, 'Module 4: AJAX and REST APIs', 'Fetch API, Axios, JSON handling, and consuming RESTful web services.', 4, 1, '2026-03-09 17:00:00', '2026-03-09 00:00:00', '2026-03-09 00:00:00'),
(15, 3, 'Unit 2: Stacks and Queues', 'Stack and queue ADTs, applications in expression evaluation and BFS/DFS.', 2, 1, '2026-01-27 18:00:00', '2026-01-27 00:00:00', '2026-01-27 00:00:00'),
(16, 3, 'Unit 3: Trees and Graphs', 'Binary trees, BSTs, AVL trees, graph representations, and traversals.', 3, 1, '2026-02-17 18:00:00', '2026-02-17 00:00:00', '2026-02-17 00:00:00'),
(17, 3, 'Unit 4: Sorting and Searching', 'Bubble, selection, merge, quick sort, and binary search with complexity analysis.', 4, 1, '2026-03-10 18:00:00', '2026-03-10 00:00:00', '2026-03-10 00:00:00'),
(18, 4, 'Chapter 2: Review of Related Literature', 'Strategies for finding, evaluating, and synthesizing academic literature.', 2, 1, '2026-01-28 19:00:00', '2026-01-28 00:00:00', '2026-01-28 00:00:00'),
(19, 4, 'Chapter 3: System Design', 'Architecture diagrams, ER diagrams, flowcharts, and prototyping methods.', 3, 1, '2026-02-18 19:00:00', '2026-02-18 00:00:00', '2026-02-18 00:00:00'),
(20, 6, 'Module 1: OSI and TCP/IP Models', 'Seven-layer OSI model, TCP/IP stack, encapsulation, and protocol comparison.', 1, 1, '2026-01-13 20:00:00', '2026-01-13 00:00:00', '2026-01-13 00:00:00'),
(21, 6, 'Module 2: IP Addressing and Subnetting', 'IPv4/IPv6 addressing, CIDR notation, subnetting calculations, and VLSM.', 2, 1, '2026-02-03 20:00:00', '2026-02-03 00:00:00', '2026-02-03 00:00:00'),
(22, 6, 'Module 3: Routing and Switching', 'Static routing, dynamic routing protocols (OSPF, RIP), and VLANs.', 3, 1, '2026-02-24 20:00:00', '2026-02-24 00:00:00', '2026-02-24 00:00:00'),
(23, 7, 'Week 1-2: SDLC Models', 'Waterfall, Agile, Scrum, and DevOps methodologies compared.', 1, 1, '2026-01-14 21:00:00', '2026-01-14 00:00:00', '2026-01-14 00:00:00'),
(24, 7, 'Week 3-4: Requirements Engineering', 'Functional/non-functional requirements, use case diagrams, and user stories.', 2, 1, '2026-01-28 21:00:00', '2026-01-28 00:00:00', '2026-01-28 00:00:00'),
(25, 7, 'Week 5-6: System Design and UML', 'Class diagrams, sequence diagrams, activity diagrams, and design patterns.', 3, 1, '2026-02-11 21:00:00', '2026-02-11 00:00:00', '2026-02-11 00:00:00'),
(26, 9, 'Module 1: Relational Model and SQL', 'Tables, primary/foreign keys, DDL, DML, and basic SELECT queries.', 1, 1, '2026-01-15 22:00:00', '2026-01-15 00:00:00', '2026-01-15 00:00:00'),
(27, 9, 'Module 2: Advanced SQL', 'Joins, subqueries, aggregate functions, views, stored procedures, and triggers.', 2, 1, '2026-02-05 22:00:00', '2026-02-05 00:00:00', '2026-02-05 00:00:00'),
(28, 9, 'Module 3: Database Normalization', '1NF, 2NF, 3NF, BCNF, and denormalization trade-offs with real-world case studies.', 3, 1, '2026-02-26 22:00:00', '2026-02-26 00:00:00', '2026-02-26 00:00:00'),
(29, 15, 'Unit 1: History of Computing', 'Evolution from vacuum tubes to modern microprocessors, key inventors and milestones.', 1, 1, '2026-01-15 23:00:00', '2026-01-15 00:00:00', '2026-01-15 00:00:00'),
(30, 15, 'Unit 2: Binary and Number Systems', 'Binary, octal, hexadecimal conversions and arithmetic operations.', 2, 1, '2026-02-05 23:00:00', '2026-02-05 00:00:00', '2026-02-05 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `notification_type` enum('new_announcement','new_assignment','assignment_graded','quiz_available','submission_received','new_reply','enrollment','grade_released','system_alert','new_module') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_id`, `sender_id`, `notification_type`, `title`, `message`, `related_type`, `related_id`, `is_read`, `read_at`, `created_at`) VALUES
(1, 55, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:12:45'),
(2, 56, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:12:45'),
(3, 48, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 1, NULL, '2026-05-11 07:12:45'),
(4, 37, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:12:45'),
(5, 38, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:12:45'),
(6, 37, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(7, 38, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(8, 39, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(9, 40, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(10, 41, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(11, 42, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(12, 43, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(13, 44, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(14, 45, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(15, 46, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:14:34'),
(16, 37, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(17, 38, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(18, 39, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(19, 40, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(20, 41, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(21, 42, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(22, 43, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(23, 44, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(24, 45, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(25, 46, NULL, 'quiz_available', 'New Quiz: quiz 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:39:05'),
(26, 37, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(27, 38, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(28, 39, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(29, 40, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(30, 41, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(31, 42, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(32, 43, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(33, 44, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(34, 45, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(35, 46, NULL, 'quiz_available', 'New Quiz: TESTING', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:51:40'),
(36, 37, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(37, 38, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(38, 39, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(39, 40, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(40, 41, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(41, 42, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(42, 43, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(43, 44, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(44, 45, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(45, 46, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 07:58:34'),
(46, 37, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(47, 38, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(48, 39, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(49, 40, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(50, 41, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(51, 42, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(52, 43, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(53, 44, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(54, 45, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(55, 46, NULL, 'quiz_available', 'New Quiz: test', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 08:05:16'),
(56, 37, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(57, 38, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(58, 39, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(59, 43, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(60, 52, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(61, 53, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(62, 54, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(63, 44, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(64, 45, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(65, 41, NULL, 'new_assignment', 'New Assignment: module 10', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-11 09:27:19'),
(66, 38, 36, 'assignment_graded', 'Lab 5 Graded - Score: 96/100', 'Your Lab Activity 5 submission has been graded. Score: 96/100. Check feedback in the Assignments tab.', 'submissions', 2, 1, '2026-05-10 01:00:00', '2026-05-08 18:00:00'),
(67, 40, 36, 'assignment_graded', 'Lab 5 Graded - Score: 72/100', 'Your Lab Activity 5 submission has been graded. Score: 72/100. Late penalty was applied.', 'submissions', 4, 1, '2026-05-11 02:00:00', '2026-05-10 22:00:00'),
(68, 42, 36, 'assignment_graded', 'Lab 5 Graded - Score: 89/100', 'Your Lab Activity 5 submission has been graded. Score: 89/100.', 'submissions', 5, 0, NULL, '2026-05-09 17:00:00'),
(69, 47, 36, 'assignment_graded', 'Problem Set 3 Graded - Score: 84/100', 'Your Problem Set 3 submission has been graded. Score: 84/100.', 'submissions', 11, 1, '2026-04-27 00:00:00', '2026-04-25 17:00:00'),
(70, 48, 36, 'assignment_graded', 'Problem Set 3 Graded - Score: 91/100', 'Your Problem Set 3 submission has been graded. Score: 91/100. Excellent work!', 'submissions', 13, 1, '2026-04-27 01:00:00', '2026-04-25 18:00:00'),
(71, 37, 36, 'new_assignment', 'New Assignment: Lab Activity 6', 'A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.', 'assignments', 6, 0, NULL, '2026-05-12 00:00:00'),
(72, 38, 36, 'new_assignment', 'New Assignment: Lab Activity 6', 'A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.', 'assignments', 6, 0, NULL, '2026-05-12 00:00:00'),
(73, 41, 36, 'new_assignment', 'New Assignment: Lab Activity 6', 'A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.', 'assignments', 6, 0, NULL, '2026-05-12 00:00:00'),
(74, 61, 57, 'new_assignment', 'New Assignment: Lab 3 Routing Config', 'A new assignment has been posted in IT 204 Computer Networks. Due: April 23, 2026.', 'assignments', 12, 1, '2026-04-08 02:00:00', '2026-04-07 00:00:00'),
(75, 62, 57, 'new_assignment', 'New Assignment: Lab 3 Routing Config', 'A new assignment has been posted in IT 204 Computer Networks. Due: April 23, 2026.', 'assignments', 12, 1, '2026-04-08 03:00:00', '2026-04-07 00:00:00'),
(76, 37, 36, 'new_announcement', 'New Announcement: Lab Activity 6 Now Available', 'A new announcement has been posted in IT 106 OOP.', 'announcements', 9, 0, NULL, '2026-05-12 00:00:00'),
(77, 38, 36, 'new_announcement', 'New Announcement: Lab Activity 6 Now Available', 'A new announcement has been posted in IT 106 OOP.', 'announcements', 9, 0, NULL, '2026-05-12 00:00:00'),
(78, 55, 36, 'new_announcement', 'New Announcement: Capstone Defense Schedule', 'A new announcement has been posted in IT 411 Capstone.', 'announcements', 12, 1, '2026-05-11 01:00:00', '2026-05-10 00:00:00'),
(79, 56, 36, 'new_announcement', 'New Announcement: Capstone Defense Schedule', 'A new announcement has been posted in IT 411 Capstone.', 'announcements', 12, 0, NULL, '2026-05-10 00:00:00'),
(80, 37, 36, 'new_module', 'New Module: Week 9-10 Collections and Generics', 'A new module has been published in IT 106 OOP.', 'modules', 11, 1, '2026-03-11 00:00:00', '2026-03-10 00:00:00'),
(81, 38, 36, 'new_module', 'New Module: Week 9-10 Collections and Generics', 'A new module has been published in IT 106 OOP.', 'modules', 11, 0, NULL, '2026-03-10 00:00:00'),
(82, 61, 57, 'new_module', 'New Module: Module 3 Routing and Switching', 'A new module has been published in IT 204 Computer Networks.', 'modules', 22, 1, '2026-02-26 02:00:00', '2026-02-25 00:00:00'),
(83, 36, 37, 'submission_received', 'Submission: Ryza Marie Gabriel - Lab 5', 'A new submission has been received for Lab Activity 5.', 'submissions', 1, 1, '2026-05-09 00:00:00', '2026-05-08 22:15:00'),
(84, 36, 39, 'submission_received', 'Submission: Nicole Abalos - Lab 5', 'A new submission has been received for Lab Activity 5.', 'submissions', 3, 1, '2026-05-09 00:00:00', '2026-05-08 16:30:00'),
(85, 57, 61, 'submission_received', 'Submission: Josh Dela Pena - Lab 1', 'A new submission has been received for Lab 1: Network Topology.', 'submissions', NULL, 1, '2026-03-05 01:00:00', '2026-03-04 06:00:00'),
(86, 36, 37, 'new_reply', 'New Reply in: Best Practices for Responsive Web Design', 'Ryza Marie Gabriel replied to a thread in IT 301 Web Programming Discussion.', 'forum_threads', 2, 1, '2026-05-07 01:00:00', '2026-05-06 01:00:00'),
(87, 42, 36, 'new_reply', 'New Reply in: CSS Grid not working on mobile', 'Instructor replied to your thread.', 'forum_threads', 5, 1, '2026-05-07 02:00:00', '2026-05-07 00:00:00'),
(88, 37, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 1, NULL, '2026-05-12 03:56:20'),
(89, 38, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(90, 39, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(91, 43, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(92, 52, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(93, 53, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(94, 54, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(95, 44, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(96, 45, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(97, 41, NULL, 'new_assignment', 'New Assignment: my assignment', 'A new assignment has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-12 03:56:20'),
(98, 37, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(99, 38, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(100, 39, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(101, 43, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(102, 52, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(103, 53, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(104, 54, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(105, 44, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(106, 45, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(107, 41, NULL, 'quiz_available', 'New Quiz: test 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:45:04'),
(108, 37, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(109, 38, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(110, 39, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(111, 43, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(112, 52, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(113, 53, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(114, 54, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(115, 44, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '0000-00-00 00:00:00'),
(116, 45, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(117, 41, NULL, 'quiz_available', 'New Quiz: lala', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-15 23:54:59'),
(118, 37, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(119, 38, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(120, 39, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(121, 43, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(122, 52, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(123, 53, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(124, 54, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(125, 44, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(126, 45, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(127, 41, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:57:48'),
(128, 37, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(129, 38, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(130, 39, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(131, 40, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(132, 41, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(133, 42, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(134, 43, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(135, 44, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(136, 45, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(137, 46, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:58:39'),
(138, 37, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(139, 38, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(140, 39, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(141, 43, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(142, 52, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(143, 53, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(144, 54, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(145, 44, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(146, 45, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(147, 41, NULL, 'quiz_available', 'New Quiz: testing 2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 00:59:20'),
(148, 37, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(149, 38, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(150, 39, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(151, 43, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(152, 52, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(153, 53, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(154, 54, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(155, 44, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(156, 45, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(157, 41, NULL, 'quiz_available', 'New Quiz: testing', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:20:26'),
(158, 37, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(159, 38, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(160, 39, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(161, 43, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(162, 52, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(163, 53, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(164, 54, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(165, 44, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(166, 45, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(167, 41, NULL, 'quiz_available', 'New Quiz: Quiz#4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:31:16'),
(168, 37, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(169, 38, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(170, 39, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(171, 43, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(172, 52, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(173, 53, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(174, 54, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(175, 44, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(176, 45, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(177, 41, NULL, 'quiz_available', 'New Quiz: testing2', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:32:19'),
(178, 37, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(179, 38, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(180, 39, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(181, 40, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(182, 41, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(183, 42, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(184, 43, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(185, 44, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(186, 45, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(187, 46, NULL, 'quiz_available', 'New Quiz: test3', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:33:22'),
(188, 37, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(189, 38, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(190, 39, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(191, 40, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(192, 41, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(193, 42, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(194, 43, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(195, 44, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(196, 45, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(197, 46, NULL, 'quiz_available', 'New Quiz: Final Quiz#5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:35:35'),
(198, 37, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(199, 38, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(200, 39, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(201, 40, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(202, 41, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(203, 42, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(204, 43, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(205, 44, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(206, 45, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(207, 46, NULL, 'quiz_available', 'New Quiz: test4', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:36:23'),
(208, 37, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(209, 38, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(210, 39, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(211, 40, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(212, 41, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(213, 42, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(214, 43, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(215, 44, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(216, 45, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(217, 46, NULL, 'quiz_available', 'New Quiz: test5', 'A new quiz has been posted in your course.', NULL, NULL, 0, NULL, '2026-05-16 01:43:42'),
(218, 36, NULL, '', 'New Reply in Discussion', 'Ryza Marie Gabriel replied to \"discussion 1\"', NULL, NULL, 0, NULL, '2026-05-16 01:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `platform_settings`
--
DELIMITER $$
CREATE TRIGGER `trg_platform_settings_after_upd` AFTER UPDATE ON `platform_settings` FOR EACH ROW BEGIN



  IF OLD.setting_value <> NEW.setting_value THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL, 'platform_setting_changed', 'platform_settings', NEW.id,



            JSON_OBJECT('key', NEW.setting_key,



                        'old_value', OLD.setting_value,



                        'new_value', NEW.setting_value));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `profile_pictures`
--

CREATE TABLE `profile_pictures` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL COMMENT 'Server path or cloud URL',
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL COMMENT 'Bytes',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = current avatar',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `college` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `code`, `name`, `college`, `created_at`) VALUES
(1, 'BSCS-ST', 'BS in Computer Science (Specialization in Software Technology)', 'College of Computer Studies', '2026-05-05 03:02:46'),
(2, 'BSIT-TP', 'BS in Information Technology (Specialization in Technopreneurship)', 'College of Computer Studies', '2026-05-05 03:02:46'),
(3, 'BEEd', 'Bachelor in Elementary Education', 'College of Education', '2026-05-05 03:02:46'),
(4, 'BSEd-ENG', 'Bachelor in Secondary Education (Major in English)', 'College of Education', '2026-05-05 03:02:46'),
(5, 'BSEd-FIL', 'Bachelor in Secondary Education (Major in Filipino)', 'College of Education', '2026-05-05 03:02:46'),
(6, 'BSEd-MATH', 'Bachelor in Secondary Education (Major in Mathematics)', 'College of Education', '2026-05-05 03:02:46'),
(7, 'BSA', 'BS in Accountancy', 'College of Business and Accountancy', '2026-05-05 03:02:46'),
(8, 'BSBA-MM', 'BS in Business Administration (Major in Marketing Management)', 'College of Business and Accountancy', '2026-05-05 03:02:46'),
(9, 'BSBA-HRDM', 'BS in Business Administration (Major in Human Resource Development Management)', 'College of Business and Accountancy', '2026-05-05 03:02:46'),
(10, 'BSEntrep', 'BS in Entrepreneurship', 'College of Business and Accountancy', '2026-05-05 03:02:46'),
(11, 'BSEcE', 'BS in Electronics Engineering', 'College of Engineering', '2026-05-05 03:02:46'),
(12, 'BSN', 'BS in Nursing', 'College of Nursing', '2026-05-05 03:02:46'),
(13, 'BPA', 'Bachelor of Public Administration', 'College of Public Administration', '2026-05-05 03:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay','matching') NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_choices`
--

CREATE TABLE `question_choices` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `quiz_type` enum('quiz','midterm','final','activity') NOT NULL DEFAULT 'quiz',
  `time_limit_min` smallint(5) UNSIGNED DEFAULT NULL,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `passing_score` decimal(6,2) NOT NULL DEFAULT 60.00,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_choices` tinyint(1) NOT NULL DEFAULT 0,
  `show_answers_after` tinyint(1) NOT NULL DEFAULT 1,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `section_id`, `title`, `description`, `due_date`, `instructions`, `quiz_type`, `time_limit_min`, `max_attempts`, `max_score`, `passing_score`, `shuffle_questions`, `shuffle_choices`, `show_answers_after`, `available_from`, `available_until`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, 'quiz 2', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 07:12:45', '2026-05-11 07:12:45'),
(4, 1, 'quiz 2', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 07:14:34', '2026-05-11 07:14:34'),
(5, 1, 'quiz 2', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 07:39:05', '2026-05-11 07:39:05'),
(6, 1, 'TESTING', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 07:51:40', '2026-05-11 07:51:40'),
(7, 1, 'test', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 07:58:34', '2026-05-11 07:58:34'),
(8, 1, 'test', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-11 08:05:16', '2026-05-11 08:05:16'),
(9, 2, 'test 2', NULL, NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-15 23:45:04', '2026-05-15 23:45:04'),
(11, 2, 'testing', NULL, '2026-05-16 20:59:00', NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 00:57:48', '2026-05-16 00:57:48'),
(14, 2, 'testing', NULL, '2026-05-16 09:20:00', NULL, 'quiz', 60, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:20:26', '2026-05-16 01:20:26'),
(15, 2, 'Quiz#4', 'JavaScript Quiz', '2026-05-16 10:30:00', NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:31:16', '2026-05-16 01:31:16'),
(16, 2, 'testing2', NULL, '2026-05-16 09:31:00', NULL, 'quiz', 30, 1, 99.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:32:19', '2026-05-16 01:32:19'),
(17, 1, 'test3', 'JavaScript Quiz', NULL, NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:33:22', '2026-05-16 01:33:22'),
(18, 1, 'test6', 'OOP Principles', '2026-05-16 11:34:00', NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:35:35', '2026-05-16 01:36:52'),
(19, 1, 'test4', NULL, '2026-05-17 09:35:00', NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:36:23', '2026-05-16 01:36:23'),
(20, 1, 'test5', 'quiz 5', '2026-05-17 09:43:00', NULL, 'quiz', 10, 1, 100.00, 60.00, 0, 0, 1, NULL, NULL, 'published', '2026-05-16 01:43:42', '2026-05-16 01:43:42');

--
-- Triggers `quizzes`
--
DELIMITER $$
CREATE TRIGGER `trg_quizzes_after_insert` AFTER INSERT ON `quizzes` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NULL, 'quiz_created', 'quizzes', NEW.id,



          JSON_OBJECT('section_id', NEW.section_id, 'title', NEW.title));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_quizzes_after_update` AFTER UPDATE ON `quizzes` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL, 'quiz_status_changed', 'quizzes', NEW.id,



            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_choice` int(10) UNSIGNED DEFAULT NULL,
  `text_answer` text DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `instructor_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_taken_sec` int(10) UNSIGNED DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `is_passed` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `quiz_id`, `student_id`, `attempt_number`, `started_at`, `submitted_at`, `time_taken_sec`, `score`, `is_passed`, `status`) VALUES
(17, 3, 47, 1, '2026-05-07 00:15:00', '2026-05-07 00:33:00', 1080, 90.00, 1, 'graded'),
(18, 3, 48, 1, '2026-05-07 00:10:00', '2026-05-07 00:25:00', 900, 95.00, 1, 'graded'),
(19, 3, 49, 1, '2026-05-07 00:20:00', '2026-05-07 00:40:00', 1200, 82.00, 1, 'graded'),
(20, 3, 50, 1, '2026-05-07 00:18:00', '2026-05-07 00:37:00', 1140, 76.00, 1, 'graded'),
(21, 3, 51, 1, '2026-05-07 00:14:00', '2026-05-07 00:31:00', 1020, 88.00, 1, 'graded'),
(22, 5, 37, 1, '2026-04-08 00:05:00', '2026-04-08 00:27:00', 1320, 88.00, 1, 'graded'),
(23, 5, 38, 1, '2026-04-08 00:03:00', '2026-04-08 00:23:00', 1200, 94.00, 1, 'graded'),
(24, 5, 39, 1, '2026-04-08 00:04:00', '2026-04-08 00:28:00', 1440, 81.00, 1, 'graded'),
(25, 5, 40, 1, '2026-04-08 00:06:00', '2026-04-08 00:31:00', 1500, 67.00, 0, 'graded'),
(26, 5, 42, 1, '2026-04-08 00:02:00', '2026-04-08 00:25:00', 1380, 76.00, 1, 'graded'),
(27, 9, 37, 1, '2026-02-09 23:05:00', '2026-02-09 23:22:00', 1020, 88.00, 1, 'graded'),
(28, 9, 38, 1, '2026-02-09 23:08:00', '2026-02-09 23:27:00', 1140, 92.00, 1, 'graded'),
(29, 9, 39, 1, '2026-02-09 23:06:00', '2026-02-09 23:26:00', 1200, 76.00, 1, 'graded'),
(30, 9, 40, 1, '2026-02-09 23:10:00', '2026-02-09 23:32:00', 1320, 68.00, 1, 'graded'),
(31, 9, 41, 1, '2026-02-09 23:03:00', '2026-02-09 23:21:00', 1080, 84.00, 1, 'graded'),
(32, 9, 42, 1, '2026-02-09 23:07:00', '2026-02-09 23:28:00', 1260, 60.00, 1, 'graded'),
(33, 9, 44, 1, '2026-02-09 23:09:00', '2026-02-09 23:24:00', 900, 96.00, 1, 'graded'),
(34, 11, 37, 1, '2026-02-02 23:05:00', '2026-02-02 23:26:00', 1260, 90.00, 1, 'graded'),
(35, 11, 38, 1, '2026-02-02 23:08:00', '2026-02-02 23:30:00', 1320, 86.00, 1, 'graded'),
(36, 11, 39, 1, '2026-02-02 23:06:00', '2026-02-02 23:27:00', 1260, 74.00, 1, 'graded'),
(37, 11, 43, 1, '2026-02-02 23:10:00', '2026-02-02 23:32:00', 1320, 80.00, 1, 'graded'),
(38, 11, 52, 1, '2026-02-02 23:03:00', '2026-02-02 23:24:00', 1260, 58.00, 0, 'graded'),
(39, 11, 53, 1, '2026-02-02 23:07:00', '2026-02-02 23:25:00', 1080, 92.00, 1, 'graded'),
(45, 15, 61, 1, '2026-01-27 23:05:00', '2026-01-27 23:22:00', 1020, 90.00, 1, 'graded'),
(46, 15, 62, 1, '2026-01-27 23:08:00', '2026-01-27 23:25:00', 1020, 86.00, 1, 'graded'),
(47, 15, 63, 1, '2026-01-27 23:06:00', '2026-01-27 23:24:00', 1080, 78.00, 1, 'graded'),
(48, 15, 65, 1, '2026-01-27 23:10:00', '2026-01-27 23:28:00', 1080, 64.00, 1, 'graded'),
(49, 18, 61, 1, '2026-02-05 23:05:00', '2026-02-05 23:22:00', 1020, 88.00, 1, 'graded'),
(50, 18, 73, 1, '2026-02-05 23:08:00', '2026-02-05 23:25:00', 1020, 92.00, 1, 'graded'),
(51, 18, 83, 1, '2026-02-05 23:06:00', '2026-02-05 23:26:00', 1200, 76.00, 1, 'graded'),
(52, 18, 90, 1, '2026-02-05 23:10:00', '2026-02-05 23:30:00', 1200, 60.00, 1, 'graded'),
(53, 14, 37, 1, '2026-05-16 01:27:57', '2026-05-16 01:27:57', 5, 100.00, 0, 'submitted'),
(54, 20, 37, 1, '2026-05-16 01:49:39', '2026-05-16 01:49:39', 5, 100.00, 0, 'submitted');

--
-- Triggers `quiz_attempts`
--
DELIMITER $$
CREATE TRIGGER `trg_quiz_attempts_after_insert` AFTER INSERT ON `quiz_attempts` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NEW.student_id, 'quiz_attempt_started', 'quiz_attempts', NEW.id,



          JSON_OBJECT('quiz_id', NEW.quiz_id, 'attempt_number', NEW.attempt_number));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_quiz_attempts_after_update` AFTER UPDATE ON `quiz_attempts` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status AND NEW.status = 'submitted' THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.student_id, 'quiz_attempt_submitted', 'quiz_attempts', NEW.id,



            JSON_OBJECT('quiz_id', NEW.quiz_id, 'score', NEW.score,



                        'is_passed', NEW.is_passed));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') NOT NULL DEFAULT 'multiple_choice',
  `question_text` text NOT NULL,
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of choice strings for MC/TF' CHECK (json_valid(`choices`)),
  `correct_answer` varchar(500) DEFAULT NULL COMMENT 'Index (MC/TF) or text (short_answer)',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `order_num` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_type`, `question_text`, `choices`, `correct_answer`, `points`, `sort_order`, `order_num`, `created_at`) VALUES
(3, 3, 'multiple_choice', 'q', '[\"qq\",\"q\",\"qqq\",\"qqqq\"]', '0', 1.00, 1, 1, '2026-05-11 07:12:45'),
(4, 3, 'true_false', 'qqq', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 2, 2, '2026-05-11 07:12:45'),
(5, 3, 'short_answer', 'qqq', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 3, 3, '2026-05-11 07:12:45'),
(6, 4, 'true_false', 'a', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 1, 1, '2026-05-11 07:14:34'),
(7, 4, 'multiple_choice', 'a', '[\"a\",\"a\",\"a\",\"a\"]', '0', 1.00, 2, 2, '2026-05-11 07:14:34'),
(8, 4, 'short_answer', 'a', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 3, 3, '2026-05-11 07:14:34'),
(9, 5, 'multiple_choice', '1', '[\"1\",\"1\",\"1\",\"1\"]', '0', 1.00, 1, 1, '2026-05-11 07:39:05'),
(10, 5, 'true_false', '1', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 2, 2, '2026-05-11 07:39:05'),
(11, 5, 'short_answer', '1', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 3, 3, '2026-05-11 07:39:05'),
(12, 6, 'multiple_choice', 'Q', '[\"Q\",\"Q\",\"Q\",\"Q\"]', '0', 1.00, 1, 1, '2026-05-11 07:51:40'),
(13, 6, 'true_false', 'Q', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 2, 2, '2026-05-11 07:51:40'),
(14, 6, 'short_answer', 'Q', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 3, 3, '2026-05-11 07:51:40'),
(15, 7, 'true_false', 's', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 1, 1, '2026-05-11 07:58:34'),
(16, 8, 'true_false', 's', '[\"\",\"\",\"\",\"\"]', '0', 1.00, 1, 1, '2026-05-11 08:05:16'),
(17, 9, 'multiple_choice', 'Which keyword is used to create an object in Java?', '[\"new\",\"create\",\"object\",\"instantiate\"]', '0', 2.00, 1, 1, '2026-02-09 00:00:00'),
(18, 9, 'true_false', 'Encapsulation is achieved by making class attributes public.', '[\"True\",\"False\"]', '1', 2.00, 2, 2, '2026-02-09 00:00:00'),
(19, 9, 'multiple_choice', 'Which access modifier restricts access to the same class only?', '[\"public\",\"protected\",\"private\",\"default\"]', '2', 2.00, 3, 3, '2026-02-09 00:00:00'),
(20, 9, 'true_false', 'A constructor must always have a return type.', '[\"True\",\"False\"]', '1', 2.00, 4, 4, '2026-02-09 00:00:00'),
(21, 9, 'short_answer', 'What is the purpose of the \"this\" keyword in Java?', '[]', 'Refers to the current object instance', 2.00, 5, 5, '2026-02-09 00:00:00'),
(22, 11, 'multiple_choice', 'Which HTML5 tag is used for navigation links?', '[\"<nav>\",\"<link>\",\"<menu>\",\"<header>\"]', '0', 2.00, 1, 1, '2026-02-02 00:00:00'),
(23, 11, 'true_false', 'The CSS specificity of an id selector is higher than a class.', '[\"True\",\"False\"]', '0', 2.00, 2, 2, '2026-02-02 00:00:00'),
(24, 11, 'multiple_choice', 'Which CSS property sets the space INSIDE an element border?', '[\"margin\",\"padding\",\"border\",\"spacing\"]', '1', 2.00, 3, 3, '2026-02-02 00:00:00'),
(25, 11, 'short_answer', 'What does CSS stand for?', '[]', 'Cascading Style Sheets', 2.00, 4, 4, '2026-02-02 00:00:00'),
(30, 15, 'multiple_choice', 'Which OSI layer is responsible for end-to-end communication?', '[\"Network\",\"Transport\",\"Session\",\"Application\"]', '1', 2.00, 1, 1, '2026-01-27 00:00:00'),
(31, 15, 'multiple_choice', 'HTTP operates at which OSI layer?', '[\"Layer 3\",\"Layer 4\",\"Layer 7\",\"Layer 2\"]', '2', 2.00, 2, 2, '2026-01-27 00:00:00'),
(32, 15, 'true_false', 'The Physical layer converts data to binary signals.', '[\"True\",\"False\"]', '0', 2.00, 3, 3, '2026-01-27 00:00:00'),
(33, 15, 'short_answer', 'What protocol is used at the Data Link layer for local network addressing?', '[]', 'MAC (Media Access Control)', 2.00, 4, 4, '2026-01-27 00:00:00'),
(34, 18, 'multiple_choice', 'Which SQL command is used to retrieve data from a table?', '[\"INSERT\",\"SELECT\",\"UPDATE\",\"DELETE\"]', '1', 2.00, 1, 1, '2026-02-05 00:00:00'),
(35, 18, 'true_false', 'A PRIMARY KEY can contain NULL values.', '[\"True\",\"False\"]', '1', 2.00, 2, 2, '2026-02-05 00:00:00'),
(36, 18, 'multiple_choice', 'Which clause is used to filter rows in a SELECT statement?', '[\"ORDER BY\",\"GROUP BY\",\"WHERE\",\"HAVING\"]', '2', 2.00, 3, 3, '2026-02-05 00:00:00'),
(37, 18, 'short_answer', 'What SQL statement is used to remove a table and all its data permanently?', '[]', 'DROP TABLE', 2.00, 4, 4, '2026-02-05 00:00:00'),
(38, 9, 'true_false', 'hi', '[\"True\",\"False\"]', '1', 1.00, 1, 1, '2026-05-15 23:45:04'),
(41, 11, 'multiple_choice', 'who', '[\"a\",\"b\",\"c\",\"d\"]', '3', 1.00, 1, 1, '2026-05-16 00:57:48'),
(43, 14, 'true_false', 'is it true?', '[\"True\",\"False\"]', '0', 1.00, 1, 1, '2026-05-16 01:20:26'),
(44, 15, 'true_false', 'JavaScript is a programming language', '[\"True\",\"False\"]', '1', 1.00, 1, 1, '2026-05-16 01:31:16'),
(45, 15, 'true_false', 'JavaScript is a server-side', '[\"True\",\"False\"]', '1', 1.00, 2, 2, '2026-05-16 01:31:16'),
(46, 16, 'true_false', 'nothing beat the jeat 2 holiday?', '[\"True\",\"False\"]', '0', 1.00, 1, 1, '2026-05-16 01:32:19'),
(47, 17, 'true_false', 'do you love me?', '[\"True\",\"False\"]', '0', 1.00, 1, 1, '2026-05-16 01:33:22'),
(48, 18, 'true_false', 'OOP stands for Object Oriented Programming', '[\"True\",\"False\"]', '0', 1.00, 1, 1, '2026-05-16 01:35:35'),
(49, 19, 'true_false', 'OOP stands for Object Project', '[\"True\",\"False\"]', '1', 1.00, 1, 1, '2026-05-16 01:36:23'),
(50, 20, 'true_false', 'tst 5', '[\"True\",\"False\"]', '0', 1.00, 1, 1, '2026-05-16 01:43:42');

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `component_id` int(10) UNSIGNED NOT NULL,
  `computed_score` decimal(5,2) DEFAULT NULL,
  `override_score` decimal(5,2) DEFAULT NULL,
  `override_note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `student_grades`
--
DELIMITER $$
CREATE TRIGGER `trg_student_grades_after_update` AFTER UPDATE ON `student_grades` FOR EACH ROW BEGIN



  IF (OLD.override_score IS NULL AND NEW.override_score IS NOT NULL)



     OR (OLD.override_score <> NEW.override_score) THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NULL, 'grade_override', 'student_grades', NEW.id,



            JSON_OBJECT('enrollment_id', NEW.enrollment_id,



                        'component_id',  NEW.component_id,



                        'old_override',  OLD.override_score,



                        'new_override',  NEW.override_score,



                        'note',          NEW.override_note));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `section` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`user_id`, `student_id`, `program`, `year_level`, `section`) VALUES
(37, '2024-00037', 'BSIT-TP', 2, 'BSIT-2A'),
(38, '2024-00038', 'BSIT-TP', 2, 'BSIT-2A'),
(39, '2024-00039', 'BSIT-TP', 2, 'BSIT-2A'),
(40, '2024-00040', 'BSIT-TP', 2, 'BSIT-2A'),
(41, '2024-00041', 'BSIT-TP', 2, 'BSIT-2A'),
(42, '2024-00042', 'BSIT-TP', 2, 'BSIT-2A'),
(43, '2024-00043', 'BSIT-TP', 2, 'BSIT-2B'),
(44, '2024-00044', 'BSIT-TP', 2, 'BSIT-2B'),
(45, '2024-00045', 'BSIT-TP', 2, 'BSIT-2B'),
(46, '2024-00046', 'BSCS-ST', 2, 'BSCS-2A'),
(47, '2024-00047', 'BSCS-ST', 2, 'BSCS-2A'),
(48, '2024-00048', 'BSCS-ST', 2, 'BSCS-2A'),
(49, '2024-00049', 'BSCS-ST', 2, 'BSCS-2A'),
(50, '2024-00050', 'BSCS-ST', 2, 'BSCS-2A'),
(51, '2024-00051', 'BSCS-ST', 2, 'BSCS-2A'),
(52, '2024-00052', 'BSIT-TP', 2, 'BSIT-2B'),
(53, '2024-00053', 'BSIT-TP', 2, 'BSIT-2B'),
(54, '2024-00054', 'BSIT-TP', 2, 'BSIT-2B'),
(55, '2024-00055', 'BSCS-ST', 4, 'BSIT-4A'),
(56, '2024-00056', 'BSCS-ST', 4, 'BSIT-4A'),
(61, '2024-00061', 'BSIT-TP', 3, 'BSIT-3A'),
(62, '2024-00062', 'BSIT-TP', 3, 'BSIT-3A'),
(63, '2024-00063', 'BSIT-TP', 3, 'BSIT-3A'),
(64, '2024-00064', 'BSCS-ST', 3, 'BSCS-3A'),
(65, '2024-00065', 'BSCS-ST', 3, 'BSCS-3A'),
(66, '2024-00066', 'BSCS-ST', 1, 'BSCS-1A'),
(67, '2024-00067', 'BSIT-TP', 1, 'BSIT-1A'),
(68, '2024-00068', 'BSIT-TP', 1, 'BSIT-1A'),
(69, '2024-00069', 'BSCS-ST', 2, 'BSCS-2B'),
(70, '2024-00070', 'BSCS-ST', 2, 'BSCS-2B'),
(71, '2024-00071', 'BSIT-TP', 2, 'BSIT-2C'),
(72, '2024-00072', 'BSIT-TP', 2, 'BSIT-2C'),
(73, '2024-00073', 'BSCS-ST', 3, 'BSCS-3B'),
(74, '2024-00074', 'BSCS-ST', 3, 'BSCS-3B'),
(75, '2024-00075', 'BSIT-TP', 4, 'BSIT-4B'),
(76, '2024-00076', 'BSIT-TP', 4, 'BSIT-4B'),
(77, '2024-00077', 'BSCS-ST', 4, 'BSCS-4A'),
(78, '2024-00078', 'BSCS-ST', 4, 'BSCS-4A'),
(79, '2024-00079', 'BSIT-TP', 1, 'BSIT-1B'),
(80, '2024-00080', 'BSIT-TP', 1, 'BSIT-1B'),
(81, '2024-00081', 'BSCS-ST', 2, 'BSCS-2C'),
(82, '2024-00082', 'BSCS-ST', 2, 'BSCS-2C'),
(83, '2024-00083', 'BSIT-TP', 3, 'BSIT-3B'),
(84, '2024-00084', 'BSIT-TP', 3, 'BSIT-3B'),
(85, '2024-00085', 'BSCS-ST', 2, 'BSCS-2A'),
(86, '2024-00086', 'BSCS-ST', 2, 'BSCS-2A'),
(87, '2024-00087', 'BSIT-TP', 2, 'BSIT-2A'),
(88, '2024-00088', 'BSIT-TP', 2, 'BSIT-2B'),
(89, '2024-00089', 'BSCS-ST', 3, 'BSCS-3A'),
(90, '2024-00090', 'BSIT-TP', 3, 'BSIT-3A'),
(91, '2024-00091', 'BSCS-ST', 4, 'BSCS-4A'),
(92, '2024-00092', 'BSIT-TP', 4, 'BSIT-4A'),
(93, '2024-00093', 'BSCS-ST', 1, 'BSCS-1A'),
(94, '2024-00094', 'BSIT-TP', 1, 'BSIT-1A'),
(95, '2024-00095', 'BSCS-ST', 3, 'BSCS-3B'),
(96, '2024-00096', 'BSIT-TP', 3, 'BSIT-3B'),
(98, '2026-0200', 'BSEd-ENG', 4, ''),
(112, '25-00112', 'BSIT-TP', 3, 'BSIT-TP-3A'),
(113, '25-00113', 'BEEd', 1, 'BEEd-1E'),
(114, '25-00114', 'BSEd-FIL', 2, 'BSEd-FIL-2A'),
(115, '25-00115', 'BSBA-MM', 1, 'BSBA-MM-1A'),
(116, '25-00116', 'BSCS-ST', 1, 'BSCS-ST-1A'),
(117, '25-00117', 'BSCS-ST', 3, 'BSCS-ST-3B'),
(118, '25-00118', 'BEEd', 2, 'BEEd-2A'),
(119, '25-00119', 'BSEcE', 3, 'BSEcE-3B'),
(120, '25-00120', 'BSIT-TP', 4, 'BSIT-TP-4A'),
(121, '25-00121', 'BSCS', 2, 'CS4A');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `content` longtext DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(10) UNSIGNED DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('submitted','graded','returned','resubmitted') NOT NULL DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `assignment_id`, `student_id`, `content`, `submitted_at`, `is_late`, `score`, `feedback`, `graded_by`, `graded_at`, `status`) VALUES
(1, 1, 37, 'Submitted via file upload', '2026-05-09 06:15:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(2, 1, 38, 'Submitted via file upload', '2026-05-08 14:00:00', 0, 96.00, 'Excellent exception handling and clean code structure.', 36, '2026-05-09 02:00:00', 'graded'),
(3, 1, 39, 'Submitted via file upload', '2026-05-09 00:30:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(4, 1, 40, 'Submitted via file upload', '2026-05-11 01:00:00', 1, 72.00, 'Late submission. Good effort but missed multi-catch blocks.', 36, '2026-05-11 06:00:00', 'graded'),
(5, 1, 42, 'Submitted via file upload', '2026-05-09 05:00:00', 0, 89.00, 'Well-structured. Minor issue with finally block logic.', 36, '2026-05-10 01:00:00', 'graded'),
(6, 2, 37, 'Submitted via file upload', '2026-04-27 15:59:00', 0, 92.00, 'Great responsive layout. Excellent mobile breakpoints.', 36, '2026-04-29 02:00:00', 'graded'),
(7, 2, 38, 'Submitted via file upload', '2026-04-28 00:00:00', 1, 80.00, 'Late by a few hours. Good overall design but missing contact form validation.', 36, '2026-04-29 04:00:00', 'graded'),
(8, 2, 39, 'Submitted via file upload', '2026-04-27 09:00:00', 0, 95.00, 'Outstanding design sense. Clean and accessible.', 36, '2026-04-29 03:00:00', 'graded'),
(9, 2, 52, 'Submitted via file upload', '2026-04-27 12:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(10, 2, 53, 'Submitted via file upload', '2026-04-28 03:00:00', 1, NULL, NULL, NULL, NULL, 'submitted'),
(11, 3, 47, 'Submitted via file upload', '2026-04-24 09:00:00', 0, 84.00, 'Correct Big-O analysis. Missed optimizing bubble sort.', 36, '2026-04-26 01:00:00', 'graded'),
(12, 3, 49, 'Submitted via file upload', '2026-04-26 01:00:00', 1, 70.00, '2 days late. Deducted 10pts. Solutions mostly correct.', 36, '2026-04-27 00:00:00', 'graded'),
(13, 3, 48, 'Submitted via file upload', '2026-04-24 06:00:00', 0, 91.00, 'Perfect Big-O table. Code is clean and well-commented.', 36, '2026-04-26 02:00:00', 'graded'),
(14, 3, 50, 'Submitted via file upload', '2026-04-24 08:00:00', 0, 78.00, 'Good work overall. Quick sort implementation had a bug.', 36, '2026-04-26 03:00:00', 'graded'),
(15, 3, 51, 'Submitted via file upload', '2026-04-24 10:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(16, 4, 55, 'Submitted via file upload', '2026-05-04 07:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(17, 4, 56, 'Submitted via file upload', '2026-05-04 10:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(18, 4, 48, 'Submitted via file upload', '2026-05-05 02:00:00', 1, NULL, NULL, NULL, NULL, 'submitted'),
(19, 5, 39, 'Submitted via file upload', '2026-04-22 00:00:00', 0, 88.00, 'Good use of super(), minor issue with method overriding.', 36, '2026-04-23 01:00:00', 'graded'),
(20, 5, 40, 'Submitted via file upload', '2026-04-20 07:45:00', 1, 75.00, 'Submitted 2 days late. Deducted 10pts.', 36, '2026-04-22 02:00:00', 'graded'),
(21, 5, 42, 'Submitted via file upload', '2026-04-18 08:00:00', 0, 82.00, 'Good design. Abstract class could be more generic.', 36, '2026-04-20 00:00:00', 'graded'),
(22, 5, 43, 'Submitted via file upload', '2026-04-19 03:00:00', 0, 79.00, 'Solid implementation. Polymorphism example needs refinement.', 36, '2026-04-20 01:00:00', 'graded'),
(23, 4, 37, 'my assignments', '2026-05-11 09:26:11', 0, NULL, NULL, NULL, NULL, 'submitted'),
(24, 6, 37, '', '2026-05-11 09:33:47', 0, NULL, NULL, NULL, NULL, 'submitted'),
(25, 6, 38, 'Submitted via file upload', '2026-05-23 01:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(26, 6, 41, 'Submitted via file upload', '2026-05-24 14:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(27, 7, 43, 'Submitted via file upload', '2026-05-19 07:00:00', 0, 87.00, 'Functional CRUD. UI could be polished further but meets all requirements.', 36, '2026-05-20 02:00:00', 'graded'),
(28, 7, 44, 'Submitted via file upload', '2026-05-19 12:00:00', 0, 91.00, 'Excellent implementation. Clean code structure and responsive design.', 36, '2026-05-20 03:00:00', 'graded'),
(29, 7, 52, 'Submitted via file upload', '2026-05-20 15:30:00', 1, 78.00, 'Late by 30 mins. Good effort. Minor backend validation issues.', 36, '2026-05-21 01:00:00', 'graded'),
(30, 8, 47, 'Submitted via file upload', '2026-05-14 02:00:00', 0, 88.00, 'Good BST implementation. BFS output was correct. DFS had a minor bug in visited tracking.', 36, '2026-05-15 01:00:00', 'graded'),
(31, 8, 48, 'Submitted via file upload', '2026-05-13 06:00:00', 0, 95.00, 'Excellent work. Graph adjacency list well-structured and all traversals correct.', 36, '2026-05-15 02:00:00', 'graded'),
(32, 8, 49, 'Submitted via file upload', '2026-05-14 14:00:00', 0, 82.00, 'BST delete function was incomplete. BFS/DFS correct.', 36, '2026-05-15 03:00:00', 'graded'),
(33, 8, 51, 'Submitted via file upload', '2026-05-14 17:00:00', 1, 71.00, 'Late by 2 hours. Penalty applied. Solutions mostly correct but lacking complexity analysis.', 36, '2026-05-16 01:00:00', 'graded'),
(34, 9, 55, 'Submitted via file upload', '2026-05-19 02:00:00', 0, 88.00, 'Well-written RRL with 17 references. Synthesis could be stronger.', 36, '2026-05-21 00:00:00', 'graded'),
(35, 9, 56, 'Submitted via file upload', '2026-05-18 06:00:00', 0, 92.00, 'Excellent literature review. References are recent and well-cited.', 36, '2026-05-21 01:00:00', 'graded'),
(36, 9, 48, 'Submitted via file upload', '2026-05-20 14:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(37, 10, 61, 'Submitted via file upload', '2026-03-04 06:00:00', 0, 90.00, 'Correct topology. Proper IP addressing and device configuration.', 57, '2026-03-06 01:00:00', 'graded'),
(38, 10, 62, 'Submitted via file upload', '2026-03-05 02:00:00', 0, 85.00, 'Good design. DHCP configuration had a misconfigured pool range.', 57, '2026-03-06 02:00:00', 'graded'),
(39, 10, 63, 'Submitted via file upload', '2026-03-04 12:00:00', 0, 88.00, 'Network functioned correctly. Documentation was clear.', 57, '2026-03-06 03:00:00', 'graded'),
(40, 10, 64, 'Submitted via file upload', '2026-03-05 00:00:00', 0, 76.00, 'Topology is correct but missing VLAN segmentation as required.', 57, '2026-03-06 04:00:00', 'graded'),
(41, 15, 61, 'Submitted via file upload', '2026-03-09 07:00:00', 0, 92.00, 'All tables created correctly. Constraints properly defined.', 59, '2026-03-11 00:00:00', 'graded'),
(42, 15, 73, 'Submitted via file upload', '2026-03-10 02:00:00', 0, 88.00, 'Good work. One foreign key constraint was incorrect.', 59, '2026-03-11 01:00:00', 'graded'),
(43, 15, 83, 'Submitted via file upload', '2026-03-09 12:00:00', 0, 95.00, 'Excellent. Clean SQL script with comments. All constraints correct.', 59, '2026-03-11 02:00:00', 'graded'),
(44, 16, 61, 'Submitted via file upload', '2026-04-06 06:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(45, 16, 83, 'Submitted via file upload', '2026-04-07 00:00:00', 0, NULL, NULL, NULL, NULL, 'submitted'),
(46, 18, 66, 'Submitted via file upload', '2026-03-04 06:00:00', 0, 45.00, 'Good effort. Two hexadecimal conversions had arithmetic errors.', 36, '2026-03-06 00:00:00', 'graded'),
(47, 18, 67, 'Submitted via file upload', '2026-03-04 08:00:00', 0, 48.00, 'Almost perfect. One binary conversion had a carry-over error.', 36, '2026-03-06 01:00:00', 'graded'),
(48, 18, 68, 'Submitted via file upload', '2026-03-05 01:00:00', 0, 50.00, 'Perfect score. All conversions correct and clearly showed steps.', 36, '2026-03-06 02:00:00', 'graded'),
(50, 21, 37, 'note', '2026-05-16 01:47:48', 0, NULL, NULL, NULL, NULL, 'submitted');

--
-- Triggers `submissions`
--
DELIMITER $$
CREATE TRIGGER `trg_submissions_after_insert` AFTER INSERT ON `submissions` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NEW.student_id, 'submission_created', 'submissions', NEW.id,



          JSON_OBJECT('assignment_id', NEW.assignment_id, 'is_late', NEW.is_late));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_submissions_after_update` AFTER UPDATE ON `submissions` FOR EACH ROW BEGIN



  IF OLD.status <> NEW.status THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.graded_by, 'submission_graded', 'submissions', NEW.id,



            JSON_OBJECT('student_id', NEW.student_id, 'score', NEW.score,



                        'old_status', OLD.status, 'new_status', NEW.status));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `submission_files`
--

CREATE TABLE `submission_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submission_files`
--

INSERT INTO `submission_files` (`id`, `submission_id`, `file_name`, `file_url`, `file_size_kb`, `uploaded_at`) VALUES
(1, 1, 'Lab5_Gabriel.zip', 'uploads/submissions/Lab5_Gabriel.zip', 240, '2026-05-08 10:54:34'),
(2, 2, 'Lab5_Sarmiento.zip', 'uploads/submissions/Lab5_Sarmiento.zip', 218, '2026-05-08 10:54:34'),
(3, 3, 'Lab5_Abalos.zip', 'uploads/submissions/Lab5_Abalos.zip', 195, '2026-05-08 10:54:34'),
(4, 4, 'Lab5_Antipolo.zip', 'uploads/submissions/Lab5_Antipolo.zip', 187, '2026-05-08 10:54:34'),
(5, 5, 'Lab5_Cruz.zip', 'uploads/submissions/Lab5_Cruz.zip', 210, '2026-05-08 10:54:34'),
(6, 6, 'Project2_Gabriel.zip', 'uploads/submissions/Project2_Gabriel.zip', 1840, '2026-05-08 10:54:34'),
(7, 7, 'Project2_Sarmiento.zip', 'uploads/submissions/Project2_Sarmiento.zip', 1620, '2026-05-08 10:54:34'),
(8, 8, 'Project2_Abalos.zip', 'uploads/submissions/Project2_Abalos.zip', 1755, '2026-05-08 10:54:34'),
(9, 9, 'Project2_Bello.zip', 'uploads/submissions/Project2_Bello.zip', 1480, '2026-05-08 10:54:34'),
(10, 10, 'Project2_Aguilar.zip', 'uploads/submissions/Project2_Aguilar.zip', 1510, '2026-05-08 10:54:34'),
(11, 11, 'PS3_Cruz.pdf', 'uploads/submissions/PS3_Cruz.pdf', 320, '2026-05-08 10:54:34'),
(12, 12, 'PS3_Bautista.pdf', 'uploads/submissions/PS3_Bautista.pdf', 298, '2026-05-08 10:54:34'),
(13, 13, 'PS3_Park.pdf', 'uploads/submissions/PS3_Park.pdf', 345, '2026-05-08 10:54:34'),
(14, 14, 'PS3_Santos.pdf', 'uploads/submissions/PS3_Santos.pdf', 310, '2026-05-08 10:54:34'),
(15, 16, 'Capstone_Cruz.docx', 'uploads/submissions/Capstone_Cruz.docx', 890, '2026-05-08 10:54:34'),
(16, 17, 'Capstone_Reyes.docx', 'uploads/submissions/Capstone_Reyes.docx', 920, '2026-05-08 10:54:34'),
(17, 19, 'Lab4_Abalos.zip', 'uploads/submissions/Lab4_Abalos.zip', 188, '2026-05-08 10:54:34'),
(18, 20, 'Lab4_Antipolo.zip', 'uploads/submissions/Lab4_Antipolo.zip', 175, '2026-05-08 10:54:34'),
(19, 21, 'Lab4_Cruz.zip', 'uploads/submissions/Lab4_Cruz.zip', 192, '2026-05-08 10:54:34'),
(20, 22, 'Lab4_Santos.zip', 'uploads/submissions/Lab4_Santos.zip', 180, '2026-05-08 10:54:34');

-- --------------------------------------------------------

--
-- Table structure for table `theme_settings`
--

CREATE TABLE `theme_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL DEFAULT 'Rose Pink',
  `primary_color` varchar(60) NOT NULL DEFAULT '336 67% 52%',
  `primary_dark` varchar(60) NOT NULL DEFAULT '336 67% 40%',
  `primary_light` varchar(60) NOT NULL DEFAULT '336 100% 97%',
  `bg_color` varchar(60) NOT NULL DEFAULT '336 100% 97%',
  `surface_color` varchar(60) NOT NULL DEFAULT '0 0% 100%',
  `border_color` varchar(60) NOT NULL DEFAULT '336 60% 87%',
  `text_color` varchar(60) NOT NULL DEFAULT '336 60% 10%',
  `text_secondary` varchar(60) NOT NULL DEFAULT '336 40% 47%',
  `accent_color` varchar(60) DEFAULT '207 80% 60%',
  `is_dark` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theme_settings`
--

INSERT INTO `theme_settings` (`id`, `name`, `primary_color`, `primary_dark`, `primary_light`, `bg_color`, `surface_color`, `border_color`, `text_color`, `text_secondary`, `accent_color`, `is_dark`, `updated_at`) VALUES
(1, 'Custom', '18 79% 42%', '18 79% 30%', '18 79% 87%', '18 79% 87%', '0 0% 100%', '18 79% 77%', '18 79% 2%', '18 79% 37%', '202 77% 60%', 0, '2026-05-17 15:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','instructor','student') NOT NULL,
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `theme_pref` enum('light','dark','system') NOT NULL DEFAULT 'system',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `email_verified`, `theme_pref`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'duran_lemuel@plpasig.edu.ph', '$2y$10$Y...', 'admin', 'active', 1, 'system', '2026-05-17 15:34:12', '2026-05-04 10:40:21', '2026-05-17 15:34:12'),
(36, 'santos_cath@plpasig.edu.ph', '$2y$10$placeholder_hash_replace_me_000000000000000000000000000', 'instructor', 'active', 1, 'system', '2026-05-16 01:53:26', '2026-05-07 09:04:06', '2026-05-16 01:53:26'),
(37, 'gabriel_ryza@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000037', 'student', 'active', 1, 'system', '2026-05-17 15:32:34', '2026-04-30 16:00:00', '2026-05-17 15:32:34'),
(38, 'sarmiento_aric@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000038', 'student', 'active', 1, 'system', '2026-05-07 00:05:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(39, 'abalos_nicole@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000039', 'student', 'active', 1, 'system', '2026-05-06 06:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(40, 'antipolo_micah@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000040', 'student', 'active', 1, 'system', '2026-05-05 01:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(41, 'ordaniel_win@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000041', 'student', 'active', 1, 'system', '2026-05-04 02:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(42, 'delacruz_juan@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000042', 'student', 'active', 1, 'system', '2026-05-06 23:30:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(43, 'santos_maria@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000043', 'student', 'active', 1, 'system', '2026-05-06 03:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(44, 'bautista_carlo@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000044', 'student', 'active', 1, 'system', '2026-05-06 01:30:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(45, 'reyes_ana@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000045', 'student', 'active', 1, 'system', '2026-05-05 05:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(46, 'torres_mark@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000046', 'student', 'active', 1, 'system', '2026-05-05 07:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(47, 'cruz_rico@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000047', 'student', 'active', 1, 'system', '2026-05-06 22:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(48, 'park_lena@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000048', 'student', 'active', 1, 'system', '2026-05-06 23:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(49, 'bautista_mario@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000049', 'student', 'active', 1, 'system', '2026-05-04 04:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(50, 'santos_karl@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000050', 'student', 'active', 1, 'system', '2026-05-06 00:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(51, 'castro_ana@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000051', 'student', 'active', 1, 'system', '2026-05-06 02:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(52, 'bello_wren@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000052', 'student', 'active', 1, 'system', '2026-05-05 08:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(53, 'aguilar_yvonne@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000053', 'student', 'active', 1, 'system', '2026-05-05 06:30:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(54, 'navarro_arlo@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000054', 'student', 'active', 1, 'system', '2026-05-06 04:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(55, 'cruz_abby@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000055', 'student', 'active', 1, 'system', '2026-05-07 01:00:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(56, 'reyes_bruno@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000056', 'student', 'active', 1, 'system', '2026-05-06 03:30:00', '2026-04-30 16:00:00', '2026-04-30 16:00:00'),
(57, 'delos_reyes_mark@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000057', 'instructor', 'active', 1, 'system', '2026-05-10 00:00:00', '2026-05-01 00:00:00', '2026-05-10 00:00:00'),
(58, 'lim_grace@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000058', 'instructor', 'active', 1, 'light', '2026-05-10 23:30:00', '2026-05-01 00:00:00', '2026-05-10 23:30:00'),
(59, 'ramos_julius@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000059', 'instructor', 'active', 1, 'dark', '2026-05-09 02:00:00', '2026-05-01 00:00:00', '2026-05-09 02:00:00'),
(60, 'villanueva_rose@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000060', 'instructor', 'active', 1, 'system', '2026-05-08 01:00:00', '2026-05-01 00:00:00', '2026-05-08 01:00:00'),
(61, 'dela_pena_josh@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000061', 'student', 'active', 1, 'system', '2026-05-09 23:00:00', '2026-01-08 00:00:00', '2026-05-09 23:00:00'),
(62, 'salazar_claire@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000062', 'student', 'active', 1, 'light', '2026-05-09 00:00:00', '2026-01-08 00:00:00', '2026-05-09 00:00:00'),
(63, 'mendoza_ryan@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000063', 'student', 'active', 1, 'system', '2026-05-07 22:30:00', '2026-01-08 00:00:00', '2026-05-07 22:30:00'),
(64, 'hernandez_paula@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000064', 'student', 'active', 1, 'dark', '2026-05-07 01:00:00', '2026-01-08 00:00:00', '2026-05-07 01:00:00'),
(65, 'ramos_felix@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000065', 'student', 'active', 1, 'system', '2026-05-10 21:00:00', '2026-01-08 00:00:00', '2026-05-10 21:00:00'),
(66, 'garcia_lea@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000066', 'student', 'active', 1, 'system', '2026-05-10 02:00:00', '2026-01-08 00:00:00', '2026-05-10 02:00:00'),
(67, 'aquino_lance@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000067', 'student', 'active', 1, 'light', '2026-05-09 03:00:00', '2026-01-08 00:00:00', '2026-05-09 03:00:00'),
(68, 'miranda_liza@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000068', 'student', 'active', 1, 'system', '2026-05-07 23:30:00', '2026-01-08 00:00:00', '2026-05-07 23:30:00'),
(69, 'austria_ivan@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000069', 'student', 'active', 1, 'system', '2026-05-07 00:00:00', '2026-01-08 00:00:00', '2026-05-07 00:00:00'),
(70, 'rojas_diana@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000070', 'student', 'active', 1, 'dark', '2026-05-06 01:00:00', '2026-01-08 00:00:00', '2026-05-06 01:00:00'),
(71, 'navarro_carl@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000071', 'student', 'active', 1, 'system', '2026-05-05 02:00:00', '2026-01-08 00:00:00', '2026-05-05 02:00:00'),
(72, 'padilla_faye@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000072', 'student', 'active', 1, 'light', '2026-05-03 23:00:00', '2026-01-08 00:00:00', '2026-05-03 23:00:00'),
(73, 'santos_leo@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000073', 'student', 'active', 1, 'system', '2026-05-03 00:00:00', '2026-01-08 00:00:00', '2026-05-03 00:00:00'),
(74, 'enriquez_nina@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000074', 'student', 'active', 1, 'system', '2026-05-02 01:00:00', '2026-01-08 00:00:00', '2026-05-02 01:00:00'),
(75, 'morales_edgar@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000075', 'student', 'active', 1, 'dark', '2026-05-01 02:00:00', '2026-01-08 00:00:00', '2026-05-01 02:00:00'),
(76, 'flores_anna@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000076', 'student', 'active', 1, 'system', '2026-04-30 00:00:00', '2026-01-08 00:00:00', '2026-04-30 00:00:00'),
(77, 'reyes_joseph@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000077', 'student', 'active', 1, 'system', '2026-04-29 01:00:00', '2026-01-08 00:00:00', '2026-04-29 01:00:00'),
(78, 'guerrero_trisha@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000078', 'student', 'active', 1, 'light', '2026-04-28 02:00:00', '2026-01-08 00:00:00', '2026-04-28 02:00:00'),
(79, 'valdez_kevin@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000079', 'student', 'active', 1, 'system', '2026-04-26 23:00:00', '2026-01-08 00:00:00', '2026-04-26 23:00:00'),
(80, 'ocampo_joanna@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000080', 'student', 'active', 1, 'system', '2026-04-26 00:00:00', '2026-01-08 00:00:00', '2026-04-26 00:00:00'),
(81, 'dela_cruz_daniel@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000081', 'student', 'active', 1, 'dark', '2026-05-10 03:00:00', '2026-01-08 00:00:00', '2026-05-10 03:00:00'),
(82, 'bernardo_camille@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000082', 'student', 'active', 1, 'system', '2026-05-08 22:00:00', '2026-01-08 00:00:00', '2026-05-08 22:00:00'),
(83, 'espiritu_alvin@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000083', 'student', 'active', 1, 'light', '2026-05-07 21:30:00', '2026-01-08 00:00:00', '2026-05-07 21:30:00'),
(84, 'santiago_rina@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000084', 'student', 'active', 1, 'system', '2026-05-07 02:00:00', '2026-01-08 00:00:00', '2026-05-07 02:00:00'),
(85, 'chua_ben@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000085', 'student', 'active', 1, 'system', '2026-05-06 03:00:00', '2026-01-08 00:00:00', '2026-05-06 03:00:00'),
(86, 'tan_mia@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000086', 'student', 'active', 1, 'dark', '2026-05-05 04:00:00', '2026-01-08 00:00:00', '2026-05-05 04:00:00'),
(87, 'lim_peter@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000087', 'student', 'active', 1, 'system', '2026-05-04 00:30:00', '2026-01-08 00:00:00', '2026-05-04 00:30:00'),
(88, 'go_patricia@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000088', 'student', 'active', 1, 'system', '2026-05-02 23:00:00', '2026-01-08 00:00:00', '2026-05-02 23:00:00'),
(89, 'uy_dennis@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000089', 'student', 'active', 1, 'light', '2026-05-02 00:00:00', '2026-01-08 00:00:00', '2026-05-02 00:00:00'),
(90, 'sy_rachel@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000090', 'student', 'active', 1, 'system', '2026-05-01 01:00:00', '2026-01-08 00:00:00', '2026-05-01 01:00:00'),
(91, 'tiu_harold@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000091', 'student', 'active', 1, 'system', '2026-04-30 02:00:00', '2026-01-08 00:00:00', '2026-04-30 02:00:00'),
(92, 'ong_carla@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000092', 'student', 'active', 1, 'dark', '2026-04-29 03:00:00', '2026-01-08 00:00:00', '2026-04-29 03:00:00'),
(93, 'kho_victor@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000093', 'student', 'active', 1, 'system', '2026-04-27 22:00:00', '2026-01-08 00:00:00', '2026-04-27 22:00:00'),
(94, 'chan_sheila@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000094', 'student', 'active', 1, 'light', '2026-04-26 23:30:00', '2026-01-08 00:00:00', '2026-04-26 23:30:00'),
(95, 'ang_jerome@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000095', 'student', 'active', 1, 'system', '2026-04-26 00:00:00', '2026-01-08 00:00:00', '2026-04-26 00:00:00'),
(96, 'yap_elaine@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_00000096', 'student', 'active', 1, 'system', '2026-04-25 01:00:00', '2026-01-08 00:00:00', '2026-04-25 01:00:00'),
(97, 'martinez_aaron@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000101', 'student', 'active', 1, 'system', '2026-05-12 00:00:00', '2026-04-30 16:00:00', '2026-05-12 00:00:00'),
(98, 'ramirez_bea@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000102', 'student', 'active', 1, 'system', '2026-05-12 00:01:00', '2026-04-30 16:00:00', '2026-05-12 00:01:00'),
(99, 'gomez_carl@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000103', 'student', 'active', 1, 'system', '2026-05-12 00:02:00', '2026-04-30 16:00:00', '2026-05-12 00:02:00'),
(100, 'navarro_diana@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000104', 'student', 'active', 1, 'system', '2026-05-12 00:03:00', '2026-04-30 16:00:00', '2026-05-12 00:03:00'),
(101, 'perez_ethan@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000105', 'student', 'active', 1, 'system', '2026-05-12 00:04:00', '2026-04-30 16:00:00', '2026-05-12 00:04:00'),
(102, 'aquino_faith@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000106', 'student', 'active', 1, 'system', '2026-05-12 00:05:00', '2026-04-30 16:00:00', '2026-05-12 00:05:00'),
(103, 'cruz_gabriel@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000107', 'student', 'active', 1, 'system', '2026-05-12 00:06:00', '2026-04-30 16:00:00', '2026-05-12 00:06:00'),
(104, 'santos_hannah@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000108', 'student', 'active', 1, 'system', '2026-05-12 00:07:00', '2026-04-30 16:00:00', '2026-05-12 00:07:00'),
(105, 'reyes_ivan@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000109', 'student', 'active', 1, 'system', '2026-05-12 00:08:00', '2026-04-30 16:00:00', '2026-05-12 00:08:00'),
(106, 'flores_jasmine@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000110', 'student', 'active', 1, 'system', '2026-05-12 00:09:00', '2026-04-30 16:00:00', '2026-05-12 00:09:00'),
(107, 'torres_kevin@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000111', 'student', 'active', 1, 'system', '2026-05-12 00:10:00', '2026-04-30 16:00:00', '2026-05-12 00:10:00'),
(108, 'mendoza_lara@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000112', 'student', 'active', 1, 'system', '2026-05-12 00:11:00', '2026-04-30 16:00:00', '2026-05-12 00:11:00'),
(109, 'castillo_matthew@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000113', 'student', 'active', 1, 'system', '2026-05-12 00:12:00', '2026-04-30 16:00:00', '2026-05-12 00:12:00'),
(110, 'garcia_nicole@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000114', 'student', 'active', 1, 'system', '2026-05-12 00:13:00', '2026-04-30 16:00:00', '2026-05-12 00:13:00'),
(111, 'herrera_oscar@plpasig.edu.ph', '$2y$10$sample_hash_placeholder_0000115', 'student', 'active', 1, 'system', '2026-05-12 00:14:00', '2026-04-30 16:00:00', '2026-05-12 00:14:00'),
(112, 'navarro_adrian@plpasig.edu.ph', '$2y$10$iI74DPBd/0UoI8/ygP6gDOwPXYK3HHyK6veVdZx0.OyoWFzJw.9xS', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:20', '2026-05-16 01:55:20'),
(113, 'reyes_bianca@plpasig.edu.ph', '$2y$10$53LXRs8M/DyQST/0qThqIOszPj4wvj/wdALM21tIYJecRo2UOYZIS', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(114, 'torres_carl@plpasig.edu.ph', '$2y$10$iOKvxvXSfbvdSWLwW6SXvOZwvmH55upTTsyVsnMaov5rqqPIOhs6W', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(115, 'mendoza_daphne@plpasig.edu.ph', '$2y$10$/8sNWVouMJGgcRkiABdJSe/e/wIoeZQZ.uJJ8ecmRbCANA43tcbOi', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(116, 'flores_ethan@plpasig.edu.ph', '$2y$10$XI48MPZmcZSwm7a5ChrxBuhZjWnIrObGk7AU4D62oHpiymVJgA31W', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(117, 'castillo_faith@plpasig.edu.ph', '$2y$10$gv9K9d9ujzdDvg7eBJ4gMOXu0qP83CQsXlwptabnoAqlEtTxxNXYO', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(118, 'rivera_gabriel@plpasig.edu.ph', '$2y$10$jmRkvNqglpz31GqdiKMAfeEizdMKEiB6C3wo8wZEs/b/id3csadf.', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(119, 'aquino_hannah@plpasig.edu.ph', '$2y$10$5tZuZVfWnUDDLr.1SJPbFeENiCPjyV1QNyvZTBfrNC7xONxZ9z3hy', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(120, 'salazar_ivan@plpasig.edu.ph', '$2y$10$pAb55nNzjMGZ.ZmybB8sJuEyH0OvtpHC7Iw/18EyaCxAvqZX05EN6', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21'),
(121, 'domingo_jasmine@plpasig.edu.ph', '$2y$10$qv3I5hcFKNs.DTYZI88SuOQQIQ09zhmSBKb2TKXPYKRhLue/J3.Lm', 'student', 'active', 0, 'system', NULL, '2026-05-16 01:55:21', '2026-05-16 01:55:21');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_after_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NULL, 'user_deleted', 'users', OLD.id,



          JSON_OBJECT('email', OLD.email, 'role', OLD.role));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN



  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



  VALUES (NEW.id, 'user_registered', 'users', NEW.id,



          JSON_OBJECT('email', NEW.email, 'role', NEW.role));



END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN



  IF OLD.email <> NEW.email THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.id, 'user_updated', 'users', NEW.id,



            JSON_OBJECT('field','email','old', OLD.email,'new', NEW.email));



  END IF;



  IF OLD.role <> NEW.role THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.id, 'user_updated', 'users', NEW.id,



            JSON_OBJECT('field','role','old', OLD.role,'new', NEW.role));



  END IF;



  IF OLD.status <> NEW.status THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.id, 'user_updated', 'users', NEW.id,



            JSON_OBJECT('field','status','old', OLD.status,'new', NEW.status));



  END IF;



  IF OLD.email_verified <> NEW.email_verified THEN



    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)



    VALUES (NEW.id, 'user_updated', 'users', NEW.id,



            JSON_OBJECT('field','email_verified','old', OLD.email_verified,'new', NEW.email_verified));



  END IF;



END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `display_name` varchar(180) GENERATED ALWAYS AS (concat(`first_name`,' ',`last_name`)) STORED,
  `phone` varchar(30) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `phone`, `avatar_url`, `bio`) VALUES
(1, 'LEMUEL', 'DURAN', NULL, NULL, NULL, NULL),
(36, 'Catherine', 'Santos', NULL, NULL, 'uploads/avatars/avatar_36_1778497754.jpg', NULL),
(37, 'Ryza Marie', 'Gabriel', NULL, NULL, NULL, 'Dean\'s list student passionate about software engineering.'),
(38, 'Aricelle', 'Sarmiento', NULL, NULL, NULL, 'Web developer enthusiast and front-end hobbyist.'),
(39, 'Nicole', 'Abalos', NULL, NULL, NULL, 'Aspiring software developer with a love for clean code.'),
(40, 'Micah Lorraine', 'Antipolo', NULL, NULL, NULL, 'Interested in AI and machine learning.'),
(41, 'Win Heart', 'Ordaniel', NULL, NULL, NULL, 'Loves game development and creative coding.'),
(42, 'Juan', 'Dela Cruz', NULL, NULL, NULL, 'Passionate about back-end development and databases.'),
(43, 'Maria', 'Santos', NULL, NULL, NULL, 'UI/UX enthusiast and graphic design hobbyist.'),
(44, 'Carlo', 'Bautista', NULL, NULL, NULL, 'Network engineering and cybersecurity student.'),
(45, 'Ana', 'Reyes', NULL, NULL, NULL, 'Robotics and IoT enthusiast.'),
(46, 'Mark', 'Torres', NULL, NULL, NULL, 'Enjoys competitive programming and algorithms.'),
(47, 'Rico', 'Cruz', NULL, NULL, NULL, 'Algorithm enthusiast and coding contest participant.'),
(48, 'Lena', 'Park', NULL, NULL, NULL, 'Strong interest in data science and analytics.'),
(49, 'Mario', 'Bautista', NULL, NULL, NULL, 'Enjoys full-stack web development projects.'),
(50, 'Karl', 'Santos', NULL, NULL, NULL, 'Passionate about system architecture and DevOps.'),
(51, 'Ana', 'Castro', NULL, NULL, NULL, 'Mobile app developer with React Native experience.'),
(52, 'Wren', 'Bello', NULL, NULL, NULL, 'Creative coder interested in digital media.'),
(53, 'Yvonne', 'Aguilar', NULL, NULL, NULL, 'Focuses on responsive design and accessibility.'),
(54, 'Arlo', 'Navarro', NULL, NULL, NULL, 'Interested in cloud computing and microservices.'),
(55, 'Abby', 'Cruz', NULL, NULL, NULL, 'Capstone researcher studying e-health platforms.'),
(56, 'Bruno', 'Reyes', NULL, NULL, NULL, 'Innovator in fintech and mobile banking solutions.'),
(58, 'Grace', 'Lim', 'Marie', '09181234568', NULL, 'Full-stack developer turned educator. Passionate about front-end frameworks and UI/UX.'),
(61, 'Josh', 'Dela Pena', 'Miguel', NULL, NULL, 'BSIT junior. Interested in game development and mobile apps.'),
(62, 'Claire', 'Salazar', 'Ann', NULL, NULL, 'Aspiring front-end developer. Loves React and design systems.'),
(63, 'Ryan', 'Mendoza', 'Gabriel', NULL, NULL, 'Backend-focused student. Enjoys building REST APIs with Laravel.'),
(64, 'Paula', 'Hernandez', 'Grace', NULL, NULL, 'Data enthusiast. Exploring machine learning and data visualization.'),
(65, 'Felix', 'Ramos', 'Jose', NULL, NULL, 'Cybersecurity student passionate about ethical hacking and network security.'),
(66, 'Lea', 'Garcia', 'Santos', NULL, NULL, 'Creative coder with a flair for UI design and Figma prototyping.'),
(67, 'Lance', 'Aquino', 'Paul', NULL, NULL, 'Systems programmer who enjoys low-level computing and OS internals.'),
(68, 'Liza', 'Miranda', 'Rose', NULL, NULL, 'Aspiring project manager. Combines IT skills with communication and teamwork.'),
(69, 'Ivan', 'Austria', 'Luis', NULL, NULL, 'Algorithm geek and competitive programmer. Regular contestant in regional hackathons.'),
(70, 'Diana', 'Rojas', 'Mae', NULL, NULL, 'Mobile developer experimenting with Flutter and cross-platform apps.'),
(71, 'Carl', 'Navarro', 'James', NULL, NULL, 'Network engineer aspirant. Interested in Cisco and cloud networking.'),
(72, 'Faye', 'Padilla', 'Christine', NULL, NULL, 'Web developer with strong CSS and animation skills.'),
(73, 'Leo', 'Santos', 'Arthur', NULL, NULL, 'Enjoys DevOps and container technologies like Docker and Kubernetes.'),
(74, 'Nina', 'Enriquez', 'Victoria', NULL, NULL, 'Data analytics student with SQL and Power BI skills.'),
(75, 'Edgar', 'Morales', 'Santiago', NULL, NULL, 'Embedded systems hobbyist. Works on Arduino and Raspberry Pi projects.'),
(76, 'Anna', 'Flores', 'Maria', NULL, NULL, 'Interested in UX research and human-computer interaction.'),
(77, 'Joseph', 'Reyes', 'David', NULL, NULL, 'Full-stack developer in training. Loves MERN stack projects.'),
(78, 'Trisha', 'Guerrero', 'Lyn', NULL, NULL, 'Cloud computing enthusiast pursuing AWS certifications.'),
(79, 'Kevin', 'Valdez', 'Patrick', NULL, NULL, 'Blockchain researcher exploring decentralized applications.'),
(80, 'Joanna', 'Ocampo', 'Faith', NULL, NULL, 'AI enthusiast focusing on natural language processing.'),
(81, 'Daniel', 'Dela Cruz', 'Emmanuel', NULL, NULL, 'Software tester interested in automation with Selenium and Cypress.'),
(82, 'Camille', 'Bernardo', 'Rose', NULL, NULL, 'Graphic design and UI/UX student bridging art and technology.'),
(83, 'Alvin', 'Espiritu', 'Mark', NULL, NULL, 'Linux power user and open-source contributor.'),
(84, 'Rina', 'Santiago', 'Claire', NULL, NULL, 'Interested in fintech and mobile payment systems.'),
(85, 'Ben', 'Chua', 'Eric', NULL, NULL, 'Math-oriented programmer. Strong in discrete math and algorithms.'),
(86, 'Mia', 'Tan', 'Joy', NULL, NULL, 'Healthcare informatics student combining IT and medical knowledge.'),
(87, 'Peter', 'Lim', 'John', NULL, NULL, 'IoT developer working on smart home and automation projects.'),
(88, 'Patricia', 'Go', 'Anne', NULL, NULL, 'E-commerce developer with Shopify and WooCommerce experience.'),
(89, 'Dennis', 'Uy', 'Rafael', NULL, NULL, 'Cybersecurity and digital forensics enthusiast.'),
(90, 'Rachel', 'Sy', 'Marie', NULL, NULL, 'Frontend developer passionate about accessibility and web standards.'),
(91, 'Harold', 'Tiu', 'James', NULL, NULL, 'Game developer using Unity for 2D and 3D projects.'),
(92, 'Carla', 'Ong', 'Frances', NULL, NULL, 'Data science student learning Python and pandas for analytics.'),
(93, 'Victor', 'Kho', 'George', NULL, NULL, 'Systems analyst with strong business process modeling skills.'),
(94, 'Sheila', 'Chan', 'Luz', NULL, NULL, 'Software project coordinator learning agile and Scrum methodologies.'),
(95, 'Jerome', 'Ang', 'Michael', NULL, NULL, 'Network security researcher studying penetration testing frameworks.'),
(96, 'Elaine', 'Yap', 'Grace', NULL, NULL, 'Digital marketing and IT student exploring growth hacking and analytics.'),
(98, 'bea', 'ramirez', NULL, NULL, NULL, NULL),
(112, 'Adrian', 'Navarro', NULL, NULL, NULL, NULL),
(113, 'Bianca', 'Reyes', NULL, NULL, NULL, NULL),
(114, 'Carl', 'Torres', NULL, NULL, NULL, NULL),
(115, 'Daphne', 'Mendoza', NULL, NULL, NULL, NULL),
(116, 'Ethan', 'Flores', NULL, NULL, NULL, NULL),
(117, 'Faith', 'Castillo', NULL, NULL, NULL, NULL),
(118, 'Gabriel', 'Rivera', NULL, NULL, NULL, NULL),
(119, 'Hannah', 'Aquino', NULL, NULL, NULL, NULL),
(120, 'Ivan', 'Salazar', NULL, NULL, NULL, NULL),
(121, 'Jasmine', 'Domingo', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `payload` text DEFAULT NULL COMMENT 'JSON: user, role, email, dept, instructor_name, etc.',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_enrollments`
-- (See below for the actual view)
--
CREATE TABLE `v_active_enrollments` (
`enrollment_id` int(10) unsigned
,`student_id` int(10) unsigned
,`student_name` varchar(161)
,`student_number` varchar(30)
,`section_id` int(10) unsigned
,`section_code` varchar(30)
,`course_id` int(10) unsigned
,`course_code` varchar(30)
,`course_title` varchar(200)
,`instructor_name` varchar(161)
,`term_label` varchar(80)
,`enrollment_status` enum('enrolled','dropped','completed','failed')
,`final_grade` decimal(5,2)
,`enrolled_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_instructor_course_feed`
-- (See below for the actual view)
--
CREATE TABLE `v_instructor_course_feed` (
`post_id` int(10) unsigned
,`section_id` int(10) unsigned
,`section_code` varchar(30)
,`course_code` varchar(30)
,`course_title` varchar(200)
,`post_type` enum('announcement','module')
,`title` varchar(255)
,`body` longtext
,`is_pinned` tinyint(1)
,`is_published` tinyint(1)
,`published_at` timestamp
,`author_id` int(10) unsigned
,`author_name` varchar(161)
,`file_count` bigint(21)
,`read_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_instructor_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `v_instructor_dashboard` (
`instructor_id` int(10) unsigned
,`section_id` int(10) unsigned
,`section_code` varchar(30)
,`course_code` varchar(30)
,`course_title` varchar(200)
,`term_label` varchar(80)
,`enrolled_students` bigint(21)
,`pending_submissions` bigint(21)
,`total_posts` bigint(21)
,`total_modules` bigint(21)
,`total_assignments` bigint(21)
,`total_quizzes` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_pending_submissions`
-- (See below for the actual view)
--
CREATE TABLE `v_pending_submissions` (
`submission_id` int(10) unsigned
,`assignment_id` int(10) unsigned
,`assignment_title` varchar(200)
,`student_id` int(10) unsigned
,`student_name` varchar(161)
,`submitted_at` timestamp
,`is_late` tinyint(1)
,`section_id` int(10) unsigned
,`section_code` varchar(30)
,`course_code` varchar(30)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_grades_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_student_grades_summary` (
`enrollment_id` int(10) unsigned
,`student_id` int(10) unsigned
,`student_name` varchar(161)
,`course_code` varchar(30)
,`course_title` varchar(200)
,`section_code` varchar(30)
,`term_label` varchar(80)
,`final_grade` decimal(5,2)
,`enrollment_status` enum('enrolled','dropped','completed','failed')
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_enrollments`
--
DROP TABLE IF EXISTS `v_active_enrollments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_enrollments`  AS SELECT `e`.`id` AS `enrollment_id`, `e`.`student_id` AS `student_id`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, `sp`.`student_id` AS `student_number`, `cs`.`id` AS `section_id`, `cs`.`section_code` AS `section_code`, `c`.`id` AS `course_id`, `c`.`code` AS `course_code`, `c`.`title` AS `course_title`, concat(`ui`.`first_name`,' ',`ui`.`last_name`) AS `instructor_name`, `at`.`label` AS `term_label`, `e`.`status` AS `enrollment_status`, `e`.`final_grade` AS `final_grade`, `e`.`enrolled_at` AS `enrolled_at` FROM ((((((((`enrollments` `e` join `users` `u` on(`u`.`id` = `e`.`student_id`)) join `user_profiles` `up` on(`up`.`user_id` = `e`.`student_id`)) join `student_profiles` `sp` on(`sp`.`user_id` = `e`.`student_id`)) join `course_sections` `cs` on(`cs`.`id` = `e`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `users` `ui2` on(`ui2`.`id` = `cs`.`instructor_id`)) join `user_profiles` `ui` on(`ui`.`user_id` = `cs`.`instructor_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) WHERE `e`.`status` = 'enrolled' ;

-- --------------------------------------------------------

--
-- Structure for view `v_instructor_course_feed`
--
DROP TABLE IF EXISTS `v_instructor_course_feed`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_instructor_course_feed`  AS SELECT `cp`.`id` AS `post_id`, `cp`.`section_id` AS `section_id`, `cs`.`section_code` AS `section_code`, `c`.`code` AS `course_code`, `c`.`title` AS `course_title`, `cp`.`post_type` AS `post_type`, `cp`.`title` AS `title`, `cp`.`body` AS `body`, `cp`.`is_pinned` AS `is_pinned`, `cp`.`is_published` AS `is_published`, `cp`.`published_at` AS `published_at`, `cp`.`author_id` AS `author_id`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `author_name`, (select count(0) from `course_post_files` `cpf` where `cpf`.`post_id` = `cp`.`id`) AS `file_count`, (select count(0) from `course_post_reads` `cpr` where `cpr`.`post_id` = `cp`.`id`) AS `read_count` FROM (((`course_posts` `cp` join `course_sections` `cs` on(`cs`.`id` = `cp`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `user_profiles` `up` on(`up`.`user_id` = `cp`.`author_id`)) WHERE `cp`.`is_published` = 1 ORDER BY `cp`.`is_pinned` DESC, `cp`.`published_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_instructor_dashboard`
--
DROP TABLE IF EXISTS `v_instructor_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_instructor_dashboard`  AS SELECT `cs`.`instructor_id` AS `instructor_id`, `cs`.`id` AS `section_id`, `cs`.`section_code` AS `section_code`, `c`.`code` AS `course_code`, `c`.`title` AS `course_title`, `at`.`label` AS `term_label`, count(distinct `e`.`student_id`) AS `enrolled_students`, count(distinct `sub`.`id`) AS `pending_submissions`, count(distinct `cp`.`id`) AS `total_posts`, count(distinct `m`.`id`) AS `total_modules`, count(distinct `a`.`id`) AS `total_assignments`, count(distinct `q`.`id`) AS `total_quizzes` FROM ((((((((`course_sections` `cs` join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) left join `enrollments` `e` on(`e`.`section_id` = `cs`.`id` and `e`.`status` = 'enrolled')) left join `assignments` `a` on(`a`.`section_id` = `cs`.`id`)) left join `submissions` `sub` on(`sub`.`assignment_id` = `a`.`id` and `sub`.`status` = 'submitted')) left join `quizzes` `q` on(`q`.`section_id` = `cs`.`id`)) left join `modules` `m` on(`m`.`section_id` = `cs`.`id` and `m`.`is_published` = 1)) left join `course_posts` `cp` on(`cp`.`section_id` = `cs`.`id` and `cp`.`is_published` = 1)) GROUP BY `cs`.`instructor_id`, `cs`.`id`, `cs`.`section_code`, `c`.`code`, `c`.`title`, `at`.`label` ;

-- --------------------------------------------------------

--
-- Structure for view `v_pending_submissions`
--
DROP TABLE IF EXISTS `v_pending_submissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pending_submissions`  AS SELECT `s`.`id` AS `submission_id`, `s`.`assignment_id` AS `assignment_id`, `a`.`title` AS `assignment_title`, `s`.`student_id` AS `student_id`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, `s`.`submitted_at` AS `submitted_at`, `s`.`is_late` AS `is_late`, `cs`.`id` AS `section_id`, `cs`.`section_code` AS `section_code`, `c`.`code` AS `course_code` FROM ((((`submissions` `s` join `assignments` `a` on(`a`.`id` = `s`.`assignment_id`)) join `course_sections` `cs` on(`cs`.`id` = `a`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `user_profiles` `up` on(`up`.`user_id` = `s`.`student_id`)) WHERE `s`.`status` = 'submitted' ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_grades_summary`
--
DROP TABLE IF EXISTS `v_student_grades_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_grades_summary`  AS SELECT `e`.`id` AS `enrollment_id`, `e`.`student_id` AS `student_id`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, `c`.`code` AS `course_code`, `c`.`title` AS `course_title`, `cs`.`section_code` AS `section_code`, `at`.`label` AS `term_label`, `e`.`final_grade` AS `final_grade`, `e`.`status` AS `enrollment_status` FROM ((((`enrollments` `e` join `user_profiles` `up` on(`up`.`user_id` = `e`.`student_id`)) join `course_sections` `cs` on(`cs`.`id` = `e`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_term` (`academic_year`,`semester`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scope` (`scope`,`scope_id`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `fk_ann_author` (`author_id`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ar` (`announcement_id`,`user_id`),
  ADD KEY `idx_ar_user` (`user_id`);

--
-- Indexes for table `announcement_targets`
--
ALTER TABLE `announcement_targets`
  ADD PRIMARY KEY (`announcement_id`,`user_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section_due` (`section_id`,`due_date`);

--
-- Indexes for table `assignment_attachments`
--
ALTER TABLE `assignment_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aa_assignment` (`assignment_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token_hash` (`token_hash`),
  ADD KEY `idx_at_user` (`user_id`),
  ADD KEY `idx_at_login_email` (`login_email`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_code` (`code`),
  ADD KEY `idx_course_dept` (`department_id`),
  ADD KEY `idx_course_status` (`status`),
  ADD KEY `fk_course_creator` (`created_by`),
  ADD KEY `fk_courses_archived_by` (`archived_by`);

--
-- Indexes for table `course_archives`
--
ALTER TABLE `course_archives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ca_course` (`course_id`),
  ADD KEY `idx_ca_section` (`section_id`),
  ADD KEY `idx_ca_actor` (`performed_by`);

--
-- Indexes for table `course_posts`
--
ALTER TABLE `course_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cp_section` (`section_id`),
  ADD KEY `idx_cp_author` (`author_id`),
  ADD KEY `idx_cp_type` (`post_type`),
  ADD KEY `idx_cp_published` (`published_at`);

--
-- Indexes for table `course_post_files`
--
ALTER TABLE `course_post_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cpf_post` (`post_id`);

--
-- Indexes for table `course_post_reads`
--
ALTER TABLE `course_post_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cpr` (`post_id`,`user_id`),
  ADD KEY `idx_cpr_user` (`user_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_section` (`course_id`,`term_id`,`section_code`),
  ADD KEY `idx_cs_term` (`term_id`),
  ADD KEY `idx_cs_instructor` (`instructor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dept_code` (`code`),
  ADD KEY `fk_dept_head` (`head_user_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`student_id`,`section_id`),
  ADD KEY `idx_enr_section` (`section_id`);

--
-- Indexes for table `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_forum_section` (`section_id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_thread` (`thread_id`),
  ADD KEY `idx_fr_parent` (`parent_reply_id`),
  ADD KEY `fk_fr_author` (`author_id`);

--
-- Indexes for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum_created` (`forum_id`,`created_at`),
  ADD KEY `fk_ft_author` (`author_id`);

--
-- Indexes for table `gradebook_items`
--
ALTER TABLE `gradebook_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gi_section` (`section_id`),
  ADD KEY `idx_gi_component` (`component_id`);

--
-- Indexes for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gc_section` (`section_id`);

--
-- Indexes for table `instructor_profiles`
--
ALTER TABLE `instructor_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_employee_id` (`employee_id`),
  ADD KEY `fk_ip_dept` (`department_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module_order` (`module_id`,`sort_order`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lp` (`student_id`,`lesson_id`),
  ADD KEY `idx_lp_lesson` (`lesson_id`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stored_name` (`stored_name`),
  ADD KEY `idx_uploader` (`uploader_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section_order` (`section_id`,`sort_order`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_read` (`recipient_id`,`is_read`),
  ADD KEY `idx_notif_created` (`created_at`),
  ADD KEY `idx_notif_sender` (`sender_id`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_setting_key` (`setting_key`),
  ADD KEY `idx_group` (`setting_group`);

--
-- Indexes for table `profile_pictures`
--
ALTER TABLE `profile_pictures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pp_user` (`user_id`),
  ADD KEY `idx_pp_active` (`user_id`,`is_active`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_program_code` (`code`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_order` (`quiz_id`,`sort_order`);

--
-- Indexes for table `question_choices`
--
ALTER TABLE `question_choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qc_question` (`question_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_section` (`section_id`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_answer` (`attempt_id`,`question_id`),
  ADD KEY `idx_ans_question` (`question_id`),
  ADD KEY `idx_ans_choice` (`selected_choice`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attempt` (`quiz_id`,`student_id`,`attempt_number`),
  ADD KEY `idx_qa_student` (`student_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qq_quiz` (`quiz_id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sg` (`enrollment_id`,`component_id`),
  ADD KEY `idx_sg_component` (`component_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_student_id` (`student_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_submission` (`assignment_id`,`student_id`),
  ADD KEY `idx_sub_student` (`student_id`),
  ADD KEY `idx_sub_graded` (`graded_by`);

--
-- Indexes for table `submission_files`
--
ALTER TABLE `submission_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sf_submission` (`submission_id`);

--
-- Indexes for table `theme_settings`
--
ALTER TABLE `theme_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_token` (`session_token`),
  ADD KEY `idx_us_user` (`user_id`),
  ADD KEY `idx_us_expires` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `assignment_attachments`
--
ALTER TABLE `assignment_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=579;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course_archives`
--
ALTER TABLE `course_archives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `course_posts`
--
ALTER TABLE `course_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `course_post_files`
--
ALTER TABLE `course_post_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_post_reads`
--
ALTER TABLE `course_post_reads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `forum_threads`
--
ALTER TABLE `forum_threads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `gradebook_items`
--
ALTER TABLE `gradebook_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_pictures`
--
ALTER TABLE `profile_pictures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_choices`
--
ALTER TABLE `question_choices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `submission_files`
--
ALTER TABLE `submission_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `theme_settings`
--
ALTER TABLE `theme_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `fk_ar_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_asgn_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assignment_attachments`
--
ALTER TABLE `assignment_attachments`
  ADD CONSTRAINT `fk_aa_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `fk_at_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_courses_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `course_archives`
--
ALTER TABLE `course_archives`
  ADD CONSTRAINT `fk_ca_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ca_performer` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ca_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `course_posts`
--
ALTER TABLE `course_posts`
  ADD CONSTRAINT `fk_cp_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cp_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_post_files`
--
ALTER TABLE `course_post_files`
  ADD CONSTRAINT `fk_cpf_post` FOREIGN KEY (`post_id`) REFERENCES `course_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_post_reads`
--
ALTER TABLE `course_post_reads`
  ADD CONSTRAINT `fk_cpr_post` FOREIGN KEY (`post_id`) REFERENCES `course_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cpr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `fk_cs_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cs_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cs_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enr_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enr_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `fk_forum_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `fk_fr_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fr_parent` FOREIGN KEY (`parent_reply_id`) REFERENCES `forum_replies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fr_thread` FOREIGN KEY (`thread_id`) REFERENCES `forum_threads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD CONSTRAINT `fk_ft_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ft_forum` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `gradebook_items`
--
ALTER TABLE `gradebook_items`
  ADD CONSTRAINT `fk_gi_component` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gi_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD CONSTRAINT `fk_gc_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `instructor_profiles`
--
ALTER TABLE `instructor_profiles`
  ADD CONSTRAINT `fk_ip_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ip_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_les_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `fk_lp_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lp_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `media_files`
--
ALTER TABLE `media_files`
  ADD CONSTRAINT `fk_mf_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `fk_mod_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `profile_pictures`
--
ALTER TABLE `profile_pictures`
  ADD CONSTRAINT `fk_pp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_q_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question_choices`
--
ALTER TABLE `question_choices`
  ADD CONSTRAINT `fk_qc_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `fk_ans_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ans_choice` FOREIGN KEY (`selected_choice`) REFERENCES `question_choices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ans_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_qa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_qa_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_qq_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `fk_sg_component` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sg_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_sp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submission_files`
--
ALTER TABLE `submission_files`
  ADD CONSTRAINT `fk_sf_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `mydb`
--
CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mydb`;
--
-- Database: `ojt_management_systemdb`
--
CREATE DATABASE IF NOT EXISTS `ojt_management_systemdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ojt_management_systemdb`;

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `CompanyID` varchar(10) NOT NULL,
  `CompanyName` varchar(150) NOT NULL,
  `CompanyAddress` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`CompanyID`, `CompanyName`, `CompanyAddress`) VALUES
('CMP001', 'Concentrix', 'Mandaluyong'),
('CMP002', 'Allorica', 'Makati'),
('CMP003', 'iStaff Solutions Inc.', 'Manila'),
('CMP004', 'Q Asia Magazine Inc.', 'Quezon City'),
('CMP005', 'Forever Flawless Face and Body Center Inc.', 'Makati'),
('CMP006', 'Pasig City Government', 'Pasig City'),
('CMP007', 'IPSolutions Inc.', 'Mandaluyong'),
('CMP008', 'Pacific Sun Solutions, Inc.', 'Taguig'),
('CMP009', 'Philippine Batteries Inc.', 'Muntinlupa'),
('CMP010', 'Datamatics Global Service Corporation', 'Pasig'),
('CMP011', 'JQB Digital Marketing Services', 'Makati');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` varchar(10) NOT NULL,
  `CourseName` varchar(20) NOT NULL,
  `DeptID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `CourseName`, `DeptID`) VALUES
('C001', 'BSIT', 'D001'),
('C002', 'BSCS', 'D001');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `DeptID` varchar(10) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DeptID`, `DepartmentName`) VALUES
('D001', 'CCS');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE `evaluation` (
  `EvaluationID` int(11) NOT NULL,
  `InternshipID` varchar(10) NOT NULL,
  `FirstVisitScore` int(11) DEFAULT NULL,
  `SecondVisitScore` int(11) DEFAULT NULL,
  `ThirdVisitScore` int(11) DEFAULT NULL,
  `FourthVisitScore` int(11) DEFAULT NULL,
  `FifthVisitScore` int(11) DEFAULT NULL,
  `TotalScore` int(11) DEFAULT NULL,
  `FinalGrade` int(11) DEFAULT NULL,
  `EvaluationDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation`
--

INSERT INTO `evaluation` (`EvaluationID`, `InternshipID`, `FirstVisitScore`, `SecondVisitScore`, `ThirdVisitScore`, `FourthVisitScore`, `FifthVisitScore`, `TotalScore`, `FinalGrade`, `EvaluationDate`) VALUES
(1, 'I002', 3, 1, 3, 2, 3, 12, 80, '2025-12-04 02:10:06'),
(2, 'I001', 2, 2, 3, 3, 3, 13, 87, '2025-11-26 17:14:07'),
(3, 'I004', 2, 3, 3, 3, 3, 14, 93, '2025-12-03 00:46:03'),
(4, 'I003', 3, 3, 3, 3, 2, 14, 93, '2025-12-03 00:46:14'),
(5, 'I006', 3, 3, 3, 3, 3, 15, 100, '2025-12-03 15:38:53'),
(6, 'I007', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 02:09:26'),
(7, 'I005', 3, 1, 3, 3, 1, 11, 73, '2025-12-04 02:11:38'),
(8, 'I008', 3, 3, 2, 3, 3, 14, 93, '2025-12-04 02:11:21'),
(9, 'I005', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(10, 'I006', 3, 3, 3, 2, 3, 14, 96, '2025-12-04 14:07:48'),
(11, 'I007', 3, 3, 3, 3, 3, 15, 98, '2025-12-04 14:07:48'),
(12, 'I008', 3, 3, 3, 2, 3, 14, 96, '2025-12-04 14:07:48'),
(13, 'I009', 3, 3, 3, 3, 3, 15, 98, '2025-12-04 14:07:48'),
(14, 'I010', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(15, 'I011', 3, 3, 3, 2, 2, 13, 93, '2025-12-04 14:07:48'),
(16, 'I012', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(17, 'I013', 3, 3, 3, 2, 3, 14, 96, '2025-12-04 14:07:48'),
(18, 'I014', 3, 3, 3, 3, 3, 15, 97, '2025-12-04 14:07:48'),
(19, 'I015', 3, 3, 3, 3, 3, 15, 98, '2025-12-04 14:07:48'),
(20, 'I016', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(21, 'I017', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(22, 'I018', 3, 3, 3, 2, 3, 14, 96, '2025-12-04 14:07:48'),
(23, 'I019', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(24, 'I020', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(25, 'I021', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(26, 'I022', 3, 3, 3, 2, 3, 14, 96, '2025-12-04 14:07:48'),
(27, 'I023', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(28, 'I024', 3, 3, 3, 3, 3, 15, 100, '2025-12-04 14:07:48'),
(29, 'I025', 3, 3, 3, 3, 3, 15, 100, '2025-12-11 15:09:16'),
(30, 'I026', 3, 3, 3, 3, 3, 15, 100, '2025-12-11 15:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `FacultyID` varchar(10) NOT NULL,
  `FacultyName` varchar(100) NOT NULL,
  `FacultyEmail` varchar(100) NOT NULL,
  `DeptID` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`FacultyID`, `FacultyName`, `FacultyEmail`, `DeptID`) VALUES
('F001', 'Prof. Warren', 'warren@school.edu', 'D001'),
('F002', 'Prof. Quhan', 'quhan@school.edu', 'D001');

-- --------------------------------------------------------

--
-- Table structure for table `internship`
--

CREATE TABLE `internship` (
  `InternshipID` varchar(10) NOT NULL,
  `StudentID` varchar(10) NOT NULL,
  `CompanyID` varchar(10) NOT NULL,
  `FacultyID` varchar(10) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Status` varchar(20) NOT NULL,
  `TotalHoursRendered` varchar(50) DEFAULT NULL,
  `EvaluationGrade` int(11) DEFAULT NULL,
  `Remarks` varchar(50) DEFAULT NULL,
  `DaysPresent` int(11) DEFAULT 0,
  `DaysAbsent` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internship`
--

INSERT INTO `internship` (`InternshipID`, `StudentID`, `CompanyID`, `FacultyID`, `StartDate`, `EndDate`, `Status`, `TotalHoursRendered`, `EvaluationGrade`, `Remarks`, `DaysPresent`, `DaysAbsent`) VALUES
('I001', '24-00221', 'CMP001', 'F001', '2025-03-01', '2025-10-01', 'Completed', '555 Hours', 87, 'Very Good', 52, 4),
('I002', '24-00222', 'CMP001', 'F001', '2025-10-10', '2026-04-10', 'Completed', '218 Hours', 80, 'Very Good', 35, 6),
('I003', '24-00223', 'CMP002', 'F002', '2025-10-18', '2026-04-18', 'Completed', '133 Hours', 93, 'Excellent', 52, 4),
('I0030', '24-00236', 'CMP003', 'F001', '2025-09-04', '2025-12-03', 'Active', '341', 95, 'Excellent', 23, 3),
('I004', '24-00224', 'CMP002', 'F002', '2025-10-23', '2026-04-23', 'Completed', '56 Hours', 93, 'Excellent', 54, 2),
('I0042', '24-00654', 'CMP010', 'F001', '2025-12-12', '2026-12-18', 'Active', NULL, NULL, NULL, 0, 0),
('I0043', '24-00226', 'CMP006', 'F001', '2025-12-12', '2026-12-10', 'Active', '450', NULL, NULL, 35, 2),
('I0044', '24-00044', 'CMP007', 'F001', '2025-12-12', '2026-02-17', 'Pending', '450', NULL, NULL, 33, 5),
('I0045', '24-00236', 'CMP001', 'F001', '2025-12-12', '2026-02-17', 'Pending', '450', NULL, NULL, 45, 0),
('I005', '24-00222', 'CMP002', 'F001', '2025-12-03', '2026-04-01', 'Completed', '420 Hours', 73, 'Good', 54, 2),
('I006', '24-00654', 'CMP002', 'F002', '2025-12-03', '2026-04-01', 'Completed', '420 Hours', 100, 'Excellent', 54, 2),
('I007', '24-00651', 'CMP001', 'F001', '2025-12-03', '2026-03-01', 'Completed', '378 Hours', 100, 'Excellent', 55, 1),
('I008', '24-00651', 'CMP001', 'F001', '2025-12-03', '2026-01-01', 'Completed', '168 Hours', 93, 'Excellent', 52, 4),
('I009', '24-00229', 'CMP006', 'F002', '2024-09-01', '2025-03-01', 'Completed', '168 Hours', 98, 'Excellent', 54, 2),
('I010', '24-00230', 'CMP007', 'F002', '2024-09-01', '2025-03-01', 'Completed', '489 Hours', 100, 'Excellent', 54, 2),
('I011', '24-00231', 'CMP008', 'F002', '2024-09-01', '2025-03-01', 'Completed', '493 Hours', 93, 'Good', 56, 0),
('I012', '24-00232', 'CMP007', 'F002', '2024-09-01', '2025-03-01', 'Completed', '482 Hours', 100, 'Excellent', 55, 1),
('I013', '24-00233', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 96, 'Very Good', 54, 2),
('I014', '24-00234', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 97, 'Excellent', 35, 6),
('I015', '24-00235', 'CMP009', 'F002', '2024-09-01', '2025-03-01', 'Completed', '485 Hours', 98, 'Excellent', 55, 1),
('I016', '24-00236', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 100, 'Excellent', 53, 3),
('I017', '24-00237', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 100, 'Excellent', 53, 3),
('I018', '24-00238', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '292 Hours', 96, 'Very Good', 54, 2),
('I019', '24-00239', 'CMP011', 'F002', '2024-09-01', '2025-03-01', 'Completed', '494 Hours', 100, 'Excellent', 51, 5),
('I020', '24-00240', 'CMP011', 'F002', '2024-09-01', '2025-03-01', 'Completed', '492 Hours', 100, 'Excellent', 50, 6),
('I021', '24-00241', 'CMP007', 'F002', '2024-09-01', '2025-03-01', 'Completed', '488 Hours', 100, 'Excellent', 50, 6),
('I022', '24-00242', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 96, 'Very Good', 22, 6),
('I023', '24-00243', 'CMP011', 'F002', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 100, 'Excellent', 33, 6),
('I024', '24-00244', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '486 Hours', 100, 'Excellent', 45, 6),
('I025', '24-00211', 'CMP002', 'F001', '2025-12-11', '2025-12-11', 'Completed', '420 Hours', 100, 'Excellent', 0, 0),
('I026', '24-00044', 'CMP002', 'F001', '2025-12-11', '2025-12-11', 'Completed', '420 Hours', 100, 'Excellent', 0, 0),
('I027', '24-00301', 'CMP001', 'F001', '2024-09-01', '2025-03-01', 'Completed', '480 Hours', 95, 'Excellent', 54, 2),
('I028', '24-00302', 'CMP002', 'F001', '2024-09-01', '2025-03-01', 'Completed', '450 Hours', 88, 'Very Good', 52, 4),
('I029', '24-00303', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '400 Hours', 82, 'Very Good', 50, 6),
('I030', '24-00304', 'CMP001', 'F001', '2024-09-01', '2025-03-01', 'Completed', '350 Hours', 75, 'Good', 48, 8),
('I031', '24-00305', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '495 Hours', 98, 'Excellent', 55, 1),
('I032', '24-00306', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '470 Hours', 92, 'Excellent', 53, 3),
('I033', '24-00307', 'CMP007', 'F001', '2024-09-01', '2025-03-01', 'Completed', '440 Hours', 86, 'Very Good', 51, 5),
('I034', '24-00308', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '460 Hours', 90, 'Excellent', 54, 2),
('I035', '24-00309', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '420 Hours', 84, 'Very Good', 49, 7),
('I036', '24-00310', 'CMP001', 'F001', '2024-09-01', '2025-03-01', 'Completed', '490 Hours', 97, 'Excellent', 56, 0),
('I037', '24-00311', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '475 Hours', 94, 'Excellent', 53, 3),
('I038', '24-00312', 'CMP004', 'F001', '2024-09-01', '2025-03-01', 'Completed', '430 Hours', 78, 'Good', 47, 9),
('I039', '24-00313', 'CMP001', 'F001', '2024-09-01', '2025-03-01', 'Completed', '485 Hours', 91, 'Excellent', 52, 4),
('I040', '24-00314', 'CMP007', 'F001', '2024-09-01', '2025-03-01', 'Completed', '455 Hours', 85, 'Very Good', 50, 6),
('I041', '24-00315', 'CMP010', 'F001', '2024-09-01', '2025-03-01', 'Completed', '500 Hours', 99, 'Excellent', 56, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` varchar(10) NOT NULL,
  `StudentName` varchar(30) NOT NULL,
  `StudentEmail` varchar(50) NOT NULL,
  `CourseID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `StudentName`, `StudentEmail`, `CourseID`) VALUES
('24-00044', 'Lhaidylyn duran', 'lhaidylyn@plp.edu', 'C001'),
('24-00211', 'Bato Bongbong', 'bato@plp.edu', 'C001'),
('24-00221', 'Ryza Gabriel', 'ryza@plp.edu', 'C001'),
('24-00222', 'Nicole Abalos', 'nicole@plp.edu', 'C001'),
('24-00223', 'Aricelle Sarmiento', 'aricelle@plp.edu', 'C002'),
('24-00224', 'Lemuel Duran', 'lemuel@plp.edu', 'C002'),
('24-00225', 'BAGOLOR, PATRICIA MAE CALUNIA', 'bagolor.p@plp.edu', 'C001'),
('24-00226', 'BINUYA, ISAIAH IAN GONZALES', 'binuya.i@plp.edu', 'C001'),
('24-00227', 'CANTOR, DELNARD RUSTE', 'cantor.d@plp.edu', 'C001'),
('24-00228', 'CUTAMORA, MICHELLE JOY MALINAO', 'cutamora.m@plp.edu', 'C001'),
('24-00229', 'DE JESUS, HANS BRIAN BIMBO', 'dejesus.h@plp.edu', 'C001'),
('24-00230', 'DELA CRUZ, JIM JULIAN VELASCO', 'delacruz.j@plp.edu', 'C001'),
('24-00231', 'DIONGLAY, AARON ANDRE SANTOS', 'dionglay.a@plp.edu', 'C001'),
('24-00232', 'DUA, CHARLS DAVE REALISTA', 'dua.c@plp.edu', 'C001'),
('24-00233', 'GUEVARA, KEITH GABRIEL REFIL', 'guevara.k@plp.edu', 'C001'),
('24-00234', 'ISIDRO, ANNE REI JUNNEL DE GUZ', 'isidro.a@plp.edu', 'C001'),
('24-00235', 'OFRACIO, JIRECK STEVE BANTOY', 'ofracio.j@plp.edu', 'C001'),
('24-00236', 'PALANGANAN, JELLY MAE BERDAL', 'palanganan.j@plp.edu', 'C001'),
('24-00237', 'PATAUEG, ANGELINA ABAD', 'pataueg.a@plp.edu', 'C001'),
('24-00238', 'RAMOS, EUNICE KHATE MARTINEZ', 'ramos.e@plp.edu', 'C001'),
('24-00239', 'REYES, IRUMI ZSOLDEK BATAAN', 'reyes.i@plp.edu', 'C001'),
('24-00240', 'ROXAS, CHRISTIAN GREY MANAHAN', 'roxas.c@plp.edu', 'C001'),
('24-00241', 'SALAMANCA, HURRY JOHN LIBOON', 'salamanca.h@plp.edu', 'C001'),
('24-00242', 'SALIVIO, JERWEH BIB OBALDO', 'salivio.j@plp.edu', 'C001'),
('24-00243', 'SAMPANG, ALEEXO ALAIN ISON', 'sampang.a@plp.edu', 'C001'),
('24-00244', 'SAN JOSE, LORRAINE NATHALIE DE', 'sanjose.l@plp.edu', 'C001'),
('24-00301', 'ALVAREZ, JOHN MICHAEL REYES', 'alvarez.j@plp.edu', 'C001'),
('24-00302', 'SANTOS, MARIA CLARA TORRES', 'santos.m@plp.edu', 'C001'),
('24-00303', 'CRUZ, MARK ANTHONY VILLANUEVA', 'cruz.m@plp.edu', 'C002'),
('24-00304', 'GARCIA, ANNA MARIE PASCUAL', 'garcia.a@plp.edu', 'C001'),
('24-00305', 'FERNANDEZ, DAVID LEE SANTIAGO', 'fernandez.d@plp.edu', 'C002'),
('24-00306', 'LOPEZ, EMILY ROSE MENDOZA', 'lopez.e@plp.edu', 'C001'),
('24-00307', 'MARTINEZ, CHRISTOPHER JAY RAMO', 'martinez.c@plp.edu', 'C002'),
('24-00308', 'RODRIGUEZ, LISA MARIE FLORES', 'rodriguez.l@plp.edu', 'C001'),
('24-00309', 'HERNANDEZ, JAMES PAUL CASTRO', 'hernandez.j@plp.edu', 'C002'),
('24-00310', 'GONZALES, SOPHIA GRACE RIVERA', 'gonzales.s@plp.edu', 'C001'),
('24-00311', 'PEREZ, DANIEL JOSEPH SANTOS', 'perez.d@plp.edu', 'C001'),
('24-00312', 'TORRES, ISABELLA KATE GARCIA', 'torres.i@plp.edu', 'C002'),
('24-00313', 'RIVERA, NATHANIEL JAY CRUZ', 'rivera.n@plp.edu', 'C001'),
('24-00314', 'FLORES, SAMANTHA JOY MORALES', 'flores.s@plp.edu', 'C002'),
('24-00315', 'REYES, BENJAMIN LUKE AQUINO', 'reyes.b@plp.edu', 'C001'),
('24-00651', 'juan duran', 'juan@plp.edu', 'C001'),
('24-00654', 'Micah Lorraine Antipolo', 'micah@plp.edu', 'C001');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor`
--

CREATE TABLE `supervisor` (
  `SupervisorID` varchar(10) NOT NULL,
  `CompanyID` varchar(10) NOT NULL,
  `SupervisorName` varchar(100) NOT NULL,
  `SupervisorEmail` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor`
--

INSERT INTO `supervisor` (`SupervisorID`, `CompanyID`, `SupervisorName`, `SupervisorEmail`) VALUES
('S001', 'CMP001', 'Mr. Zaide', 'zaide@concentrix.com'),
('S002', 'CMP002', 'Engr. Loren', 'loren@allorica.com');

-- --------------------------------------------------------

--
-- Table structure for table `visitlog`
--

CREATE TABLE `visitlog` (
  `VisitID` varchar(10) NOT NULL,
  `InternshipID` varchar(10) NOT NULL,
  `VisitDate` date NOT NULL,
  `TimeIn` time NOT NULL,
  `TimeOut` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitlog`
--

INSERT INTO `visitlog` (`VisitID`, `InternshipID`, `VisitDate`, `TimeIn`, `TimeOut`) VALUES
('V001', 'I001', '2025-05-05', '08:00:00', '16:30:00'),
('V002', 'I002', '2025-10-01', '09:00:00', '16:30:00'),
('V003', 'I003', '2025-10-02', '07:00:00', '17:00:00'),
('V004', 'I004', '2025-10-02', '11:00:00', '17:15:00'),
('V005', 'I006', '2025-12-03', '15:39:56', '00:39:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`CompanyID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD KEY `DeptID` (`DeptID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DeptID`);

--
-- Indexes for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`EvaluationID`),
  ADD KEY `InternshipID` (`InternshipID`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`FacultyID`),
  ADD KEY `fk_faculty_dept` (`DeptID`);

--
-- Indexes for table `internship`
--
ALTER TABLE `internship`
  ADD PRIMARY KEY (`InternshipID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `CompanyID` (`CompanyID`),
  ADD KEY `FacultyID` (`FacultyID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD PRIMARY KEY (`SupervisorID`),
  ADD KEY `CompanyID` (`CompanyID`);

--
-- Indexes for table `visitlog`
--
ALTER TABLE `visitlog`
  ADD PRIMARY KEY (`VisitID`),
  ADD KEY `InternshipID` (`InternshipID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `EvaluationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`DeptID`) REFERENCES `department` (`DeptID`);

--
-- Constraints for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `evaluation_ibfk_1` FOREIGN KEY (`InternshipID`) REFERENCES `internship` (`InternshipID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `fk_faculty_dept` FOREIGN KEY (`DeptID`) REFERENCES `department` (`DeptID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `internship`
--
ALTER TABLE `internship`
  ADD CONSTRAINT `internship_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `internship_ibfk_2` FOREIGN KEY (`CompanyID`) REFERENCES `company` (`CompanyID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `internship_ibfk_3` FOREIGN KEY (`FacultyID`) REFERENCES `faculty` (`FacultyID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`);

--
-- Constraints for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD CONSTRAINT `supervisor_ibfk_1` FOREIGN KEY (`CompanyID`) REFERENCES `company` (`CompanyID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `visitlog`
--
ALTER TABLE `visitlog`
  ADD CONSTRAINT `visitlog_ibfk_1` FOREIGN KEY (`InternshipID`) REFERENCES `internship` (`InternshipID`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `ojt_management__systmdb`
--
CREATE DATABASE IF NOT EXISTS `ojt_management__systmdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ojt_management__systmdb`;
--
-- Database: `ojt_managment_systemdb`
--
CREATE DATABASE IF NOT EXISTS `ojt_managment_systemdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ojt_managment_systemdb`;
--
-- Database: `phpconnectdb`
--
CREATE DATABASE IF NOT EXISTS `phpconnectdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `phpconnectdb`;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"learnflow_db\",\"table\":\"users\"},{\"db\":\"learnflow_db\",\"table\":\"user_profiles\"},{\"db\":\"learnflow_db\",\"table\":\"departments\"},{\"db\":\"learnflow_db\",\"table\":\"programs\"},{\"db\":\"learnflow_db\",\"table\":\"user_sessions\"},{\"db\":\"learnflow_db\",\"table\":\"profile_pictures\"},{\"db\":\"regessystem\",\"table\":\"account\"},{\"db\":\"tryphp\",\"table\":\"users\"},{\"db\":\"learnflow_db\",\"table\":\"quizzes\"},{\"db\":\"learnflow_db\",\"table\":\"assignments\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-05-17 15:35:14', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `regessystem`
--
CREATE DATABASE IF NOT EXISTS `regessystem` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `regessystem`;

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id` int(11) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id`, `fullname`, `username`, `email`, `password`) VALUES
(1, 'Lemuel Duran', 'green', 'leonapersy@gmail.com', 'tinolangmanok'),
(2, 'Lemuel Duran', 'green', 'leonapersy@gmail.com', '$2y$10$hOxjQsv6uNiaAcLU/ID3oeZGkSUaucNKsvaM2Jcy4J6go9KO5Bg16'),
(3, 'andrea gianan', 'andreaG', 'leonapersy@gmail.com', '$2y$10$Wb9Y9zmRT4ayWtSz.yxI9uTe4etVex7EqMD2QP8ywTK9qzrjna.2O'),
(4, 'andrea gianan', 'andreaG', 'leonapersy@gmail.com', '$2y$10$dvPPs70ERWIGQP4MneAqfuOblBY0qwreX2cW71R4BNQ.kklwQQgla'),
(5, 'andrea gianan', 'andrea123', 'leonapersy@gmail.com', '$2y$10$mtTB6OhNr0TJ/Bf9H2q4tez85UFGpj9DUrRh070rN4tGkuRIwkPDu'),
(6, 'lemuel duran', 'duranLem', 'leonapersy@gmail.com', '$2y$10$ZdKCQ9iDZoTvWmfEcURXxeXbvQ0pLA8FBMAzuUEB/e.huIsQgtKaC');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
--
-- Database: `training_center_db`
--
CREATE DATABASE IF NOT EXISTS `training_center_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `training_center_db`;

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `BranchID` int(11) NOT NULL,
  `BranchName` varchar(100) NOT NULL,
  `City` varchar(50) NOT NULL,
  `Address` varchar(200) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `ManagerName` varchar(100) DEFAULT NULL,
  `EstablishedDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`BranchID`, `BranchName`, `City`, `Address`, `Phone`, `ManagerName`, `EstablishedDate`, `Status`) VALUES
(1, 'Manila Main Branch', 'Manila', '123 Rizal Ave, Ermita, Manila', '02-1234-5678', 'Maria Santos', '2020-01-15', 'Active'),
(2, 'Cebu Technology Hub', 'Cebu', '456 Osmena Blvd, Cebu City', '032-987-6543', 'Juan Dela Cruz', '2021-06-01', 'Active'),
(3, 'Davao Learning Center', 'Davao', '789 J.P. Laurel Ave, Davao City', '082-555-1234', 'Ana Reyes', '2022-03-10', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `branch_course_offering`
--

CREATE TABLE `branch_course_offering` (
  `OfferingID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `BranchPrice` decimal(10,2) DEFAULT NULL,
  `IsAvailable` tinyint(1) NOT NULL DEFAULT 1,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branch_course_offering`
--

INSERT INTO `branch_course_offering` (`OfferingID`, `BranchID`, `CourseID`, `BranchPrice`, `IsAvailable`, `StartDate`, `EndDate`) VALUES
(1, 1, 1, NULL, 1, '2025-01-01', NULL),
(2, 1, 2, 8000.00, 1, '2025-01-01', NULL),
(3, 1, 3, NULL, 1, '2025-01-01', NULL),
(4, 1, 4, 9500.00, 1, '2025-01-01', NULL),
(5, 1, 5, NULL, 1, '2025-01-01', NULL),
(6, 1, 6, NULL, 1, '2025-01-01', NULL),
(7, 2, 1, 5500.00, 1, '2025-02-01', NULL),
(8, 2, 2, 8200.00, 1, '2025-02-01', NULL),
(9, 2, 3, NULL, 1, '2025-02-01', NULL),
(10, 2, 5, 6200.00, 1, '2025-02-01', NULL),
(11, 3, 1, 5200.00, 1, '2025-03-01', NULL),
(12, 3, 3, NULL, 1, '2025-03-01', NULL),
(13, 3, 5, NULL, 1, '2025-03-01', NULL),
(14, 3, 6, 7800.00, 1, '2025-03-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` int(11) NOT NULL,
  `CourseCode` varchar(20) NOT NULL,
  `CourseName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `DurationWeeks` int(11) DEFAULT NULL,
  `DurationHours` int(11) DEFAULT NULL,
  `BaseFee` decimal(10,2) NOT NULL,
  `DeliveryMode` enum('In-Person','Online','Hybrid') NOT NULL DEFAULT 'In-Person',
  `MaxCapacity` int(11) DEFAULT 30,
  `PrerequisiteCourseID` int(11) DEFAULT NULL,
  `CourseLevel` enum('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `CourseCode`, `CourseName`, `Description`, `DurationWeeks`, `DurationHours`, `BaseFee`, `DeliveryMode`, `MaxCapacity`, `PrerequisiteCourseID`, `CourseLevel`, `IsActive`) VALUES
(1, 'DB101', 'Database Design Fundamentals', 'Introduction to database concepts, ER modeling, and normalization', 4, NULL, 5000.00, 'In-Person', 30, NULL, 'Beginner', 1),
(2, 'DB201', 'Advanced Database Management', 'SQL optimization, indexing, transactions, and stored procedures', 6, NULL, 7500.00, 'Hybrid', 25, 1, 'Advanced', 1),
(3, 'WEB101', 'Web Development Basics', 'HTML, CSS, JavaScript fundamentals for beginners', NULL, 40, 4500.00, 'Online', 40, NULL, 'Beginner', 1),
(4, 'WEB201', 'Full-Stack Web Development', 'Complete web application development with modern frameworks', 8, NULL, 9000.00, 'In-Person', 20, 3, 'Advanced', 1),
(5, 'PY101', 'Python Programming', 'Python basics, data structures, and simple applications', 5, NULL, 6000.00, 'Hybrid', 35, NULL, 'Beginner', 1),
(6, 'DATA201', 'Data Analytics with Python', 'Pandas, NumPy, data visualization, and analysis', 6, NULL, 8000.00, 'Online', 30, 5, 'Intermediate', 1);

-- --------------------------------------------------------

--
-- Table structure for table `course_session`
--

CREATE TABLE `course_session` (
  `SessionID` int(11) NOT NULL,
  `OfferingID` int(11) NOT NULL,
  `SessionName` varchar(100) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Schedule` varchar(100) DEFAULT NULL,
  `MaxEnrollment` int(11) NOT NULL DEFAULT 30,
  `CurrentEnrollment` int(11) NOT NULL DEFAULT 0,
  `Status` enum('Scheduled','InProgress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `Location` varchar(150) DEFAULT NULL
) ;

--
-- Dumping data for table `course_session`
--

INSERT INTO `course_session` (`SessionID`, `OfferingID`, `SessionName`, `StartDate`, `EndDate`, `Schedule`, `MaxEnrollment`, `CurrentEnrollment`, `Status`, `Location`) VALUES
(1, 1, 'DB101-MNL-2025-Q1', '2025-02-01', '2025-02-28', 'Mon/Wed 6-9 PM', 30, 3, 'Scheduled', 'Room 301'),
(2, 1, 'DB101-MNL-2025-Q2', '2025-05-01', '2025-05-30', 'Tue/Thu 6-9 PM', 30, 1, 'Scheduled', 'Room 302'),
(3, 2, 'DB201-MNL-2025-Q1', '2025-03-01', '2025-04-15', 'Sat 9AM-5PM', 25, 0, 'Scheduled', 'Room 401'),
(4, 7, 'DB101-CEB-2025-Q1', '2025-02-05', '2025-03-05', 'Mon/Wed 6-9 PM', 30, 2, 'InProgress', 'Cebu Lab 1'),
(5, 7, 'DB101-CEB-2025-Q2', '2025-05-10', '2025-06-10', 'Sat 1-6 PM', 30, 1, 'Scheduled', 'Cebu Lab 2'),
(6, 3, 'WEB101-MNL-2025-Q1', '2025-02-15', '2025-03-30', 'Online: Tue/Thu 7-9 PM', 40, 2, 'Scheduled', 'Zoom Platform'),
(7, 4, 'WEB201-MNL-2025-Q1', '2025-04-01', '2025-05-30', 'Mon/Wed/Fri 6-9 PM', 20, 1, 'Scheduled', 'Room 501'),
(8, 5, 'PY101-MNL-2025-Q1', '2025-02-10', '2025-03-15', 'Tue/Thu 6-9 PM', 35, 3, 'Scheduled', 'Room 201'),
(9, 10, 'PY101-CEB-2025-Q1', '2025-03-01', '2025-04-05', 'Sat 9AM-5PM', 35, 0, 'Scheduled', 'Cebu Lab 3');

-- --------------------------------------------------------

--
-- Table structure for table `discount`
--

CREATE TABLE `discount` (
  `DiscountID` int(11) NOT NULL,
  `DiscountCode` varchar(50) NOT NULL,
  `DiscountName` varchar(100) NOT NULL,
  `DiscountType` enum('Percentage','FixedAmount','GroupRate') NOT NULL,
  `DiscountValue` decimal(10,2) NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date NOT NULL,
  `MinimumStudents` int(11) DEFAULT 1,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `discount`
--

INSERT INTO `discount` (`DiscountID`, `DiscountCode`, `DiscountName`, `DiscountType`, `DiscountValue`, `ValidFrom`, `ValidUntil`, `MinimumStudents`, `IsActive`) VALUES
(1, 'EARLY2025', 'Early Bird Discount 2025', 'Percentage', 10.00, '2025-01-01', '2025-03-31', 1, 1),
(2, 'GROUP5', 'Group of 5 Discount', 'Percentage', 15.00, '2025-01-01', '2025-12-31', 5, 1),
(3, 'STUDENT500', 'Student Fixed Discount', 'FixedAmount', 500.00, '2025-01-01', '2025-12-31', 1, 1),
(4, 'SUMMER2025', 'Summer Promo', 'Percentage', 20.00, '2025-05-01', '2025-07-31', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollmentID` int(11) NOT NULL,
  `TraineeID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `EnrollmentDate` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Waitlisted','Enrolled','Active','Completed','Dropped','Withdrawn') NOT NULL DEFAULT 'Enrolled',
  `Grade` varchar(5) DEFAULT NULL,
  `CertificateIssueDate` date DEFAULT NULL,
  `CertificateNumber` varchar(50) DEFAULT NULL,
  `AttendancePercentage` decimal(5,2) DEFAULT 0.00,
  `RetakeNumber` int(11) NOT NULL DEFAULT 1,
  `TotalDue` decimal(10,2) NOT NULL,
  `TotalPaid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Notes` text DEFAULT NULL
) ;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`EnrollmentID`, `TraineeID`, `SessionID`, `EnrollmentDate`, `Status`, `Grade`, `CertificateIssueDate`, `CertificateNumber`, `AttendancePercentage`, `RetakeNumber`, `TotalDue`, `TotalPaid`, `Notes`) VALUES
(1, 1, 1, '2025-01-20 10:30:00', 'Active', NULL, NULL, NULL, 85.50, 1, 4500.00, 2000.00, NULL),
(2, 1, 6, '2025-02-05 14:15:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 4500.00, 4500.00, NULL),
(3, 2, 1, '2025-01-22 09:00:00', 'Active', NULL, NULL, NULL, 90.00, 1, 4750.00, 4750.00, NULL),
(4, 2, 8, '2025-02-01 11:00:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 6000.00, 3000.00, NULL),
(5, 3, 4, '2025-01-25 16:30:00', 'Active', NULL, NULL, NULL, 75.00, 1, 5500.00, 5500.00, NULL),
(6, 3, 5, '2025-04-20 10:00:00', 'Waitlisted', NULL, NULL, NULL, 0.00, 1, 5500.00, 0.00, NULL),
(7, 4, 4, '2025-01-26 13:45:00', 'Active', NULL, NULL, NULL, 60.00, 1, 4950.00, 2000.00, NULL),
(8, 4, 5, '2025-04-22 15:00:00', 'Enrolled', NULL, NULL, NULL, 0.00, 2, 5500.00, 0.00, NULL),
(9, 5, 8, '2025-02-03 10:30:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 6000.00, 6000.00, NULL),
(10, 6, 1, '2025-01-21 12:00:00', 'Active', NULL, NULL, NULL, 88.00, 1, 5000.00, 5000.00, NULL),
(11, 6, 7, '2025-03-15 09:30:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 9500.00, 5000.00, NULL),
(12, 7, 6, '2025-02-10 14:00:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 4500.00, 4500.00, NULL),
(13, 8, 4, '2025-01-28 11:15:00', 'Dropped', NULL, NULL, NULL, 25.00, 1, 5500.00, 1000.00, NULL),
(14, 9, 8, '2025-02-08 16:45:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 5500.00, 0.00, NULL),
(15, 10, 2, '2025-04-01 10:00:00', 'Enrolled', NULL, NULL, NULL, 0.00, 1, 5000.00, 2500.00, NULL);

--
-- Triggers `enrollment`
--
DELIMITER $$
CREATE TRIGGER `trg_enrollment_insert` AFTER INSERT ON `enrollment` FOR EACH ROW BEGIN
    IF NEW.Status IN ('Enrolled', 'Active') THEN
        UPDATE COURSE_SESSION 
        SET CurrentEnrollment = CurrentEnrollment + 1
        WHERE SessionID = NEW.SessionID;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_enrollment_update` AFTER UPDATE ON `enrollment` FOR EACH ROW BEGIN
    
    IF OLD.Status IN ('Enrolled', 'Active') AND NEW.Status IN ('Dropped', 'Withdrawn') THEN
        UPDATE COURSE_SESSION 
        SET CurrentEnrollment = CurrentEnrollment - 1
        WHERE SessionID = NEW.SessionID;
    END IF;
    
    
    IF OLD.Status = 'Waitlisted' AND NEW.Status IN ('Enrolled', 'Active') THEN
        UPDATE COURSE_SESSION 
        SET CurrentEnrollment = CurrentEnrollment + 1
        WHERE SessionID = NEW.SessionID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_discount`
--

CREATE TABLE `enrollment_discount` (
  `EnrollmentDiscountID` int(11) NOT NULL,
  `EnrollmentID` int(11) NOT NULL,
  `DiscountID` int(11) NOT NULL,
  `DiscountAmountApplied` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_discount`
--

INSERT INTO `enrollment_discount` (`EnrollmentDiscountID`, `EnrollmentID`, `DiscountID`, `DiscountAmountApplied`) VALUES
(1, 1, 1, 500.00),
(2, 2, 1, 250.00),
(3, 2, 3, 500.00),
(4, 7, 1, 550.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `EnrollmentID` int(11) NOT NULL,
  `PaymentDate` datetime NOT NULL DEFAULT current_timestamp(),
  `Amount` decimal(10,2) NOT NULL,
  `PaymentMethod` enum('Cash','Card','BankTransfer','Online') NOT NULL,
  `TransactionReference` varchar(100) DEFAULT NULL,
  `PaymentType` enum('Full','Partial','Installment') NOT NULL,
  `Notes` text DEFAULT NULL
) ;

--
-- Triggers `payment`
--
DELIMITER $$
CREATE TRIGGER `trg_payment_delete` AFTER DELETE ON `payment` FOR EACH ROW BEGIN
    UPDATE ENROLLMENT 
    SET TotalPaid = TotalPaid - OLD.Amount
    WHERE EnrollmentID = OLD.EnrollmentID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payment_insert` AFTER INSERT ON `payment` FOR EACH ROW BEGIN
    UPDATE ENROLLMENT 
    SET TotalPaid = TotalPaid + NEW.Amount
    WHERE EnrollmentID = NEW.EnrollmentID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `session_trainer`
--

CREATE TABLE `session_trainer` (
  `SessionTrainerID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `TrainerID` int(11) NOT NULL,
  `TrainerRole` enum('Primary','CoTrainer','Assistant') NOT NULL DEFAULT 'Primary',
  `PaymentAmount` decimal(10,2) DEFAULT NULL,
  `PaymentStatus` enum('Pending','Paid') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_trainer`
--

INSERT INTO `session_trainer` (`SessionTrainerID`, `SessionID`, `TrainerID`, `TrainerRole`, `PaymentAmount`, `PaymentStatus`) VALUES
(1, 1, 1, 'Primary', NULL, 'Paid'),
(2, 1, 2, 'CoTrainer', NULL, 'Paid'),
(3, 2, 2, 'Primary', NULL, 'Pending'),
(4, 3, 1, 'Primary', NULL, 'Pending'),
(5, 3, 2, 'Assistant', NULL, 'Pending'),
(6, 4, 1, 'Primary', NULL, 'Paid'),
(7, 5, 2, 'Primary', NULL, 'Pending'),
(8, 6, 3, 'Primary', NULL, 'Pending'),
(9, 7, 5, 'Primary', 80000.00, 'Pending'),
(10, 8, 6, 'Primary', NULL, 'Pending'),
(11, 8, 4, 'CoTrainer', 45000.00, 'Pending'),
(12, 9, 4, 'Primary', 52000.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `trainee`
--

CREATE TABLE `trainee` (
  `TraineeID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `RegistrationDate` datetime NOT NULL DEFAULT current_timestamp(),
  `DateOfBirth` date DEFAULT NULL,
  `Address` varchar(200) DEFAULT NULL,
  `City` varchar(50) DEFAULT NULL,
  `EmergencyContact` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainee`
--

INSERT INTO `trainee` (`TraineeID`, `FirstName`, `LastName`, `Email`, `Phone`, `RegistrationDate`, `DateOfBirth`, `Address`, `City`, `EmergencyContact`) VALUES
(1, 'John', 'Doe', 'john.doe@email.com', '0917-123-4567', '2026-02-01 12:49:42', '1995-03-15', '123 Main St, Quezon City', 'Manila', 'Jane Doe: 0918-234-5678'),
(2, 'Jane', 'Smith', 'jane.smith@email.com', '0918-234-5678', '2026-02-01 12:49:42', '1998-07-22', '456 Oak Ave, Makati', 'Manila', 'John Smith: 0917-345-6789'),
(3, 'Carlos', 'Rodriguez', 'carlos.r@email.com', '0919-345-6789', '2026-02-01 12:49:42', '1996-11-08', '789 Pine Rd, Cebu City', 'Cebu', 'Maria Rodriguez: 0920-456-7890'),
(4, 'Maria', 'Garcia', 'maria.g@email.com', '0920-456-7890', '2026-02-01 12:49:42', '1997-05-30', '321 Elm St, Mandaue', 'Cebu', 'Carlos Garcia: 0919-567-8901'),
(5, 'Robert', 'Wilson', 'robert.w@email.com', '0921-567-8901', '2026-02-01 12:49:42', '1994-09-12', '654 Maple Dr, Davao City', 'Davao', 'Lisa Wilson: 0922-678-9012'),
(6, 'Lisa', 'Anderson', 'lisa.a@email.com', '0922-678-9012', '2026-02-01 12:49:42', '1999-01-25', '987 Cedar Ln, BGC, Taguig', 'Manila', 'Robert Anderson: 0921-789-0123'),
(7, 'Michael', 'Taylor', 'michael.t@email.com', '0923-789-0123', '2026-02-01 12:49:42', '1995-06-18', '147 Birch Ct, Pasig', 'Manila', 'Sarah Taylor: 0924-890-1234'),
(8, 'Sarah', 'Martinez', 'sarah.m@email.com', '0924-890-1234', '2026-02-01 12:49:42', '1998-12-03', '258 Spruce Way, Lapu-Lapu', 'Cebu', 'Michael Martinez: 0923-901-2345'),
(9, 'David', 'Thompson', 'david.t@email.com', '0925-901-2345', '2026-02-01 12:49:42', '1996-04-27', '369 Willow Rd, Davao City', 'Davao', 'Emma Thompson: 0926-012-3456'),
(10, 'Emma', 'White', 'emma.w@email.com', '0926-012-3456', '2026-02-01 12:49:42', '1997-08-14', '741 Ash Blvd, Manila', 'Manila', 'David White: 0925-123-4567');

-- --------------------------------------------------------

--
-- Table structure for table `trainer`
--

CREATE TABLE `trainer` (
  `TrainerID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `Specialization` varchar(100) DEFAULT NULL,
  `HireDate` date DEFAULT NULL,
  `TrainerType` enum('FullTime','PartTime','Guest') NOT NULL,
  `HourlyRate` decimal(10,2) DEFAULT NULL,
  `Bio` text DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainer`
--

INSERT INTO `trainer` (`TrainerID`, `FirstName`, `LastName`, `Email`, `Phone`, `Specialization`, `HireDate`, `TrainerType`, `HourlyRate`, `Bio`, `IsActive`) VALUES
(1, 'Robert', 'Smith', 'robert.smith@training.com', '0917-111-2222', 'Database Systems', '2020-02-01', 'FullTime', NULL, NULL, 1),
(2, 'Linda', 'Lee', 'linda.lee@training.com', '0918-333-4444', 'Database & SQL', '2020-05-15', 'FullTime', NULL, NULL, 1),
(3, 'Michael', 'Johnson', 'michael.j@training.com', '0919-555-6666', 'Web Development', '2021-01-10', 'FullTime', NULL, NULL, 1),
(4, 'Sarah', 'Chen', 'sarah.chen@training.com', '0920-777-8888', 'Python & Data Science', '2021-07-01', 'PartTime', 1500.00, NULL, 1),
(5, 'David', 'Garcia', 'david.garcia@freelance.com', '0921-999-0000', 'Full-Stack Development', '2022-01-01', 'Guest', 2000.00, NULL, 1),
(6, 'Emily', 'Wong', 'emily.wong@training.com', '0922-111-2222', 'Python Programming', '2022-06-15', 'FullTime', NULL, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`BranchID`),
  ADD KEY `idx_branch_city` (`City`);

--
-- Indexes for table `branch_course_offering`
--
ALTER TABLE `branch_course_offering`
  ADD PRIMARY KEY (`OfferingID`),
  ADD UNIQUE KEY `uk_branch_course` (`BranchID`,`CourseID`),
  ADD KEY `idx_branch_offerings` (`BranchID`),
  ADD KEY `idx_course_branches` (`CourseID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD UNIQUE KEY `CourseCode` (`CourseCode`),
  ADD KEY `fk_prerequisite` (`PrerequisiteCourseID`),
  ADD KEY `idx_course_code` (`CourseCode`),
  ADD KEY `idx_course_level` (`CourseLevel`);

--
-- Indexes for table `course_session`
--
ALTER TABLE `course_session`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `idx_session_status` (`Status`),
  ADD KEY `idx_session_dates` (`StartDate`,`EndDate`),
  ADD KEY `idx_course_popularity` (`OfferingID`,`Status`);

--
-- Indexes for table `discount`
--
ALTER TABLE `discount`
  ADD PRIMARY KEY (`DiscountID`),
  ADD UNIQUE KEY `DiscountCode` (`DiscountCode`),
  ADD KEY `idx_discount_code` (`DiscountCode`),
  ADD KEY `idx_discount_validity` (`ValidFrom`,`ValidUntil`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD UNIQUE KEY `uk_trainee_session` (`TraineeID`,`SessionID`),
  ADD UNIQUE KEY `CertificateNumber` (`CertificateNumber`),
  ADD KEY `fk_enrollment_session` (`SessionID`),
  ADD KEY `idx_enrollment_status` (`Status`),
  ADD KEY `idx_enrollment_date` (`EnrollmentDate`),
  ADD KEY `idx_certificate_date` (`CertificateIssueDate`),
  ADD KEY `idx_overdue_payments` (`TotalPaid`,`TotalDue`,`Status`),
  ADD KEY `idx_trainee_course_history` (`TraineeID`,`SessionID`,`RetakeNumber`);

--
-- Indexes for table `enrollment_discount`
--
ALTER TABLE `enrollment_discount`
  ADD PRIMARY KEY (`EnrollmentDiscountID`),
  ADD KEY `idx_enrollment_discounts` (`EnrollmentID`),
  ADD KEY `idx_discount_usage` (`DiscountID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `fk_payment_enrollment` (`EnrollmentID`),
  ADD KEY `idx_payment_date` (`PaymentDate`),
  ADD KEY `idx_payment_method` (`PaymentMethod`);

--
-- Indexes for table `session_trainer`
--
ALTER TABLE `session_trainer`
  ADD PRIMARY KEY (`SessionTrainerID`),
  ADD UNIQUE KEY `uk_session_trainer` (`SessionID`,`TrainerID`),
  ADD KEY `idx_trainer_sessions` (`TrainerID`),
  ADD KEY `idx_trainer_workload` (`TrainerID`,`SessionID`);

--
-- Indexes for table `trainee`
--
ALTER TABLE `trainee`
  ADD PRIMARY KEY (`TraineeID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_trainee_email` (`Email`),
  ADD KEY `idx_trainee_name` (`LastName`,`FirstName`);

--
-- Indexes for table `trainer`
--
ALTER TABLE `trainer`
  ADD PRIMARY KEY (`TrainerID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_trainer_email` (`Email`),
  ADD KEY `idx_trainer_type` (`TrainerType`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `BranchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `branch_course_offering`
--
ALTER TABLE `branch_course_offering`
  MODIFY `OfferingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `CourseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_session`
--
ALTER TABLE `course_session`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discount`
--
ALTER TABLE `discount`
  MODIFY `DiscountID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_discount`
--
ALTER TABLE `enrollment_discount`
  MODIFY `EnrollmentDiscountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_trainer`
--
ALTER TABLE `session_trainer`
  MODIFY `SessionTrainerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `trainee`
--
ALTER TABLE `trainee`
  MODIFY `TraineeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `trainer`
--
ALTER TABLE `trainer`
  MODIFY `TrainerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch_course_offering`
--
ALTER TABLE `branch_course_offering`
  ADD CONSTRAINT `fk_offering_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_offering_course` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `fk_prerequisite` FOREIGN KEY (`PrerequisiteCourseID`) REFERENCES `course` (`CourseID`) ON DELETE SET NULL;

--
-- Constraints for table `course_session`
--
ALTER TABLE `course_session`
  ADD CONSTRAINT `fk_session_offering` FOREIGN KEY (`OfferingID`) REFERENCES `branch_course_offering` (`OfferingID`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `fk_enrollment_session` FOREIGN KEY (`SessionID`) REFERENCES `course_session` (`SessionID`),
  ADD CONSTRAINT `fk_enrollment_trainee` FOREIGN KEY (`TraineeID`) REFERENCES `trainee` (`TraineeID`);

--
-- Constraints for table `enrollment_discount`
--
ALTER TABLE `enrollment_discount`
  ADD CONSTRAINT `fk_enrollment_discount_discount` FOREIGN KEY (`DiscountID`) REFERENCES `discount` (`DiscountID`),
  ADD CONSTRAINT `fk_enrollment_discount_enrollment` FOREIGN KEY (`EnrollmentID`) REFERENCES `enrollment` (`EnrollmentID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_enrollment` FOREIGN KEY (`EnrollmentID`) REFERENCES `enrollment` (`EnrollmentID`);

--
-- Constraints for table `session_trainer`
--
ALTER TABLE `session_trainer`
  ADD CONSTRAINT `fk_session_trainer_session` FOREIGN KEY (`SessionID`) REFERENCES `course_session` (`SessionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_trainer_trainer` FOREIGN KEY (`TrainerID`) REFERENCES `trainer` (`TrainerID`);
--
-- Database: `tryphp`
--
CREATE DATABASE IF NOT EXISTS `tryphp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tryphp`;

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `balance`) VALUES
(1, '', 0.00),
(5, 'lemuel', 10.00),
(6, 'lemuel', 10.00),
(7, 'lemuel', 10.00),
(8, 'lemuel', 10.00),
(9, '', 0.00),
(10, '', 0.00),
(11, '', 0.00),
(12, '', 0.00),
(13, '', 0.00),
(14, '', 0.00),
(15, '', 0.00),
(16, '', 0.00),
(17, '', 0.00),
(18, '', 0.00),
(19, '', 0.00),
(20, '', 0.00),
(21, '', 0.00),
(22, 'winner', 55.00),
(23, '', 0.00),
(24, '', 0.00),
(25, '', 0.00),
(26, '', 0.00),
(27, '', 0.00),
(28, '', 0.00),
(29, '', 0.00),
(30, '', 0.00),
(31, '', 0.00),
(32, '', 0.00),
(33, '', 0.00),
(34, '', 0.00),
(35, '', 0.00),
(36, '', 0.00),
(37, '', 0.00),
(38, '', 0.00),
(39, '', 0.00),
(40, '', 0.00),
(41, '', 0.00),
(42, '', 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
--
-- Database: `voter_db`
--
CREATE DATABASE IF NOT EXISTS `voter_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `voter_db`;

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `election_id` int(11) NOT NULL,
  `election_name` varchar(100) NOT NULL,
  `election_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`election_id`, `election_name`, `election_date`) VALUES
(1, 'Barangay Election 2024', '2024-10-30'),
(2, 'National Election 2025', '2025-05-13'),
(3, 'Local Election 2026', '2026-05-10'),
(4, 'Youth Election 2024', '2024-06-15'),
(5, 'Midterm Election 2025', '2025-11-20'),
(6, 'Special Election 2026', '2026-02-14'),
(7, 'Regional Election 2025', '2025-08-18'),
(8, 'City Election 2024', '2024-12-05'),
(9, 'Provincial Election 2026', '2026-07-22'),
(10, 'General Election 2025', '2025-03-03');

-- --------------------------------------------------------

--
-- Table structure for table `precincts`
--

CREATE TABLE `precincts` (
  `precinct_id` int(11) NOT NULL,
  `precinct_name` varchar(50) NOT NULL,
  `location` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `precincts`
--

INSERT INTO `precincts` (`precinct_id`, `precinct_name`, `location`) VALUES
(1, 'Precinct 1', 'Barangay A'),
(2, 'Precinct 2', 'Barangay B'),
(3, 'Precinct 3', 'Barangay C'),
(4, 'Precinct 4', 'Barangay D'),
(5, 'Precinct 5', 'Barangay E'),
(6, 'Precinct 6', 'Barangay F'),
(7, 'Precinct 7', 'Barangay G'),
(8, 'Precinct 8', 'Barangay H'),
(9, 'Precinct 9', 'Barangay I'),
(10, 'Precinct 10', 'Barangay J');

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `voter_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `precinct_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voters`
--

INSERT INTO `voters` (`voter_id`, `first_name`, `last_name`, `birth_date`, `gender`, `address`, `precinct_id`) VALUES
(1, 'Juan', 'Dela Cruz', '1990-05-12', 'Male', 'Manila', 1),
(2, 'Maria', 'Santos', '1985-07-20', 'Female', 'Quezon City', 2),
(3, 'Pedro', 'Reyes', '2000-01-15', 'Male', 'Caloocan', 1),
(4, 'Ana', 'Lopez', '1995-03-30', 'Female', 'Pasig', 3),
(5, 'Jose', 'Garcia', '1988-09-10', 'Male', 'Taguig', 2),
(6, 'Luisa', 'Martinez', '1992-11-25', 'Female', 'Makati', 4),
(7, 'Carlos', 'Torres', '1999-06-18', 'Male', 'Para?aque', 5),
(8, 'Elena', 'Gomez', '1993-08-05', 'Female', 'Las Pi?as', 3),
(9, 'Miguel', 'Fernandez', '1980-12-01', 'Male', 'Marikina', 6),
(10, 'Sofia', 'Rivera', '2002-04-22', 'Female', 'Navotas', 7);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`election_id`);

--
-- Indexes for table `precincts`
--
ALTER TABLE `precincts`
  ADD PRIMARY KEY (`precinct_id`);

--
-- Indexes for table `voters`
--
ALTER TABLE `voters`
  ADD PRIMARY KEY (`voter_id`),
  ADD KEY `precinct_id` (`precinct_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `election_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `precincts`
--
ALTER TABLE `precincts`
  MODIFY `precinct_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `voter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `voters`
--
ALTER TABLE `voters`
  ADD CONSTRAINT `voters_ibfk_1` FOREIGN KEY (`precinct_id`) REFERENCES `precincts` (`precinct_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
