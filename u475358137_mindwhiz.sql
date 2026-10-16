-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 18, 2026 at 10:12 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u475358137_mindwhiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Bcrypt hashed password',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin users for dashboard access';

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'mehul shihora', '$2y$10$ZQwupY6itAbtMvb1qHk0n..bM6p83Nxi8vJJRTUEMZdWwSobjOU3q', '2026-01-18 10:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `attempt_flags`
--

CREATE TABLE `attempt_flags` (
  `attempt_id` bigint(20) NOT NULL COMMENT 'Foreign key to student_attempts table',
  `memorize_expired` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=memorization phase timer expired, cannot re-enter page4',
  `recall_expired` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=recall phase timer expired, cannot re-enter page6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Timer expiry flags to block page re-entry';

-- --------------------------------------------------------

--
-- Table structure for table `attempt_words`
--

CREATE TABLE `attempt_words` (
  `id` bigint(20) NOT NULL,
  `attempt_id` bigint(20) NOT NULL COMMENT 'Foreign key to student_attempts table',
  `words` text NOT NULL COMMENT 'JSON array of words shown to student: ["word1","word2",..."word60"]',
  `student_answers` text DEFAULT NULL COMMENT 'JSON array of student answers: ["ans1","ans2",..."ans60"]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Word lists and answers for each attempt';

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(120) NOT NULL COMMENT 'Email address being verified',
  `verification_code` varchar(6) NOT NULL COMMENT '6-digit verification code',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=successfully verified, 0=pending',
  `attempts` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of verification attempts (rate limiting)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When code was generated',
  `expires_at` datetime NOT NULL COMMENT 'Code expiration timestamp',
  `verified_at` datetime DEFAULT NULL COMMENT 'When verification succeeded'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email verification tracking with rate limiting';

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL COMMENT 'Student first name',
  `surname` varchar(120) DEFAULT NULL COMMENT 'Student last name',
  `email` varchar(120) NOT NULL COMMENT 'Student email',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Contact phone number',
  `school` varchar(200) DEFAULT NULL COMMENT 'School name',
  `city` varchar(100) DEFAULT NULL COMMENT 'City name',
  `parent_name` varchar(120) DEFAULT NULL COMMENT 'Parent or guardian name',
  `standard` varchar(20) DEFAULT NULL COMMENT 'Grade/Standard (e.g., "10th", "12th")',
  `language` enum('eng','guj') NOT NULL DEFAULT 'eng' COMMENT 'Preferred test language: eng=English, guj=Gujarati',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Registration timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Student registration records - allows multiple people per email';

-- --------------------------------------------------------

--
-- Table structure for table `student_attempts`
--

CREATE TABLE `student_attempts` (
  `id` bigint(20) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'Foreign key to students table',
  `attempt_number` int(11) NOT NULL COMMENT 'Sequential attempt number for this student (1, 2, 3)',
  `score` int(11) DEFAULT NULL COMMENT 'Number of correctly recalled words',
  `passed` tinyint(1) DEFAULT NULL COMMENT '1=passed, 0=failed, NULL=not yet graded',
  `attempt_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When attempt was started'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Student test attempts with scores';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `attempt_flags`
--
ALTER TABLE `attempt_flags`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `attempt_words`
--
ALTER TABLE `attempt_words`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attempt` (`attempt_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_code` (`verification_code`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_verified` (`is_verified`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_name` (`email`,`name`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_language` (`language`);

--
-- Indexes for table `student_attempts`
--
ALTER TABLE `student_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_attempt` (`student_id`,`attempt_number`),
  ADD KEY `idx_attempts_student_date` (`student_id`,`attempt_at`),
  ADD KEY `idx_attempt_at` (`attempt_at`),
  ADD KEY `idx_passed` (`passed`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attempt_words`
--
ALTER TABLE `attempt_words`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attempts`
--
ALTER TABLE `student_attempts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attempt_flags`
--
ALTER TABLE `attempt_flags`
  ADD CONSTRAINT `fk_attempt_flags` FOREIGN KEY (`attempt_id`) REFERENCES `student_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attempt_words`
--
ALTER TABLE `attempt_words`
  ADD CONSTRAINT `fk_attempt_words` FOREIGN KEY (`attempt_id`) REFERENCES `student_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_attempts`
--
ALTER TABLE `student_attempts`
  ADD CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
