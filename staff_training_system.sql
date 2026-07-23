-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 05:24 PM
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
-- Database: `staff_training_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `certifications`
--

CREATE TABLE `certifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `certification_name` varchar(255) NOT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certification_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `development_plans`
--

CREATE TABLE `development_plans` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('not_started','in_progress','completed','delayed') DEFAULT 'not_started',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `email`, `phone`, `department`, `position`, `bio`, `date_of_joining`, `date_of_birth`, `profile_picture`, `employee_code`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System', 'Administrator', 'admin@example.com', '1234567890', 'IT', 'System Administrator', NULL, '2026-07-22', NULL, NULL, 'ADMIN001', NULL, 'active', '2026-07-22 17:53:02', '2026-07-22 17:53:02'),
(2, 'System', 'Administrator', 'superadmin@admin.com', '08039149400', 'IT', 'System Administrator', NULL, NULL, NULL, 'profile_2_1784780644.jpg', 'ADMIN20260722294', NULL, 'active', '2026-07-22 23:57:42', '2026-07-23 04:24:05'),
(3, 'Sunday', 'Ajeoda', 'kossyvibes@gmail.com', '08139149400', 'Finance', 'Accontant', NULL, '2026-07-06', '2000-04-20', '', 'EMP202607231491', NULL, 'active', '2026-07-23 04:25:43', '2026-07-23 04:25:43'),
(4, 'John', 'Doe', 'employee@example.com', '08094144185', 'Sales', 'Sales Representative', 'the account', NULL, NULL, 'profile_4_1784784331.jpg', 'EMP202607239571', NULL, 'active', '2026-07-23 04:38:59', '2026-07-23 05:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `years_experience` decimal(3,1) DEFAULT NULL,
  `last_updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_trainings`
--

CREATE TABLE `employee_trainings` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `completion_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `attended` tinyint(1) DEFAULT 0,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_trainings`
--

INSERT INTO `employee_trainings` (`id`, `employee_id`, `training_id`, `enrollment_date`, `completion_date`, `progress`, `status`, `score`, `feedback`, `rating`, `attended`, `certificate_issued`, `certificate_number`) VALUES
(1, 4, 13, '2026-07-23', NULL, 0.00, 'enrolled', NULL, NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_tasks`
--

CREATE TABLE `plan_tasks` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'login', '{\"ip\":\"::1\"}', '::1', '2026-07-22 23:58:03'),
(2, 2, 'profile_picture_updated', NULL, '::1', '2026-07-23 04:24:05'),
(3, 2, 'employee_created', '{\"employee_id\":3}', '::1', '2026-07-23 04:25:43'),
(4, 3, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', '2026-07-23 04:39:36'),
(5, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:52:37'),
(6, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:53:24'),
(7, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:53:29'),
(8, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:56:34'),
(9, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:56:48'),
(10, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:57:34'),
(11, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:58:31'),
(12, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 04:59:47'),
(13, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:02:30'),
(14, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:11:12'),
(15, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:17:24'),
(16, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:17:45'),
(17, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:20:28'),
(18, 3, 'profile_picture_updated', NULL, '127.0.0.1', '2026-07-23 05:25:31'),
(19, 3, 'profile_updated', NULL, '127.0.0.1', '2026-07-23 05:25:35'),
(20, 3, 'training_applied', '{\"training_id\":13}', '127.0.0.1', '2026-07-23 15:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `training_materials`
--

CREATE TABLE `training_materials` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `resource_url` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `type` enum('technical','soft_skill','management','compliance','other') DEFAULT 'technical',
  `category` varchar(100) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL,
  `trainer_email` varchar(100) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 20,
  `current_participants` int(11) DEFAULT 0,
  `cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Cost in Naira (₦)',
  `currency` varchar(10) DEFAULT 'NGN',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `is_certified` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_programs`
--

INSERT INTO `training_programs` (`id`, `title`, `description`, `prerequisites`, `learning_objectives`, `type`, `category`, `duration_hours`, `start_date`, `end_date`, `location`, `trainer_name`, `trainer_email`, `max_participants`, `current_participants`, `cost`, `currency`, `status`, `is_certified`, `created_by`, `created_at`) VALUES
(1, 'Full-Stack Web Development Bootcamp', 'Comprehensive training covering HTML, CSS, JavaScript, React, Node.js, and MongoDB. Hands-on projects and real-world applications. Participants will build a complete web application from scratch.', NULL, NULL, 'technical', 'Programming', 40, '2026-08-23', '2026-09-23', 'Virtual - Zoom', 'Dr. Sarah Johnson', 'sarah.johnson@techacademy.com', 30, 0, 250000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(2, 'Advanced Python Programming', 'Deep dive into Python advanced concepts including decorators, generators, context managers, async programming, and data science libraries. Includes hands-on projects and real-world applications.', NULL, NULL, 'technical', 'Programming', 24, '2026-05-23', '2026-06-23', 'Lagos Training Center', 'Mr. Michael Chen', 'michael.chen@pythonpro.com', 25, 25, 150000.00, 'NGN', 'completed', 0, 1, '2026-07-23 15:14:04'),
(3, 'Cloud Computing with AWS', 'Master AWS services including EC2, S3, Lambda, RDS, VPC, and IAM. Prepare for AWS Certified Solutions Architect exam with practical labs and real-world scenarios.', NULL, NULL, 'technical', 'Cloud Computing', 32, '2026-10-23', '2026-11-23', 'Virtual - AWS Platform', 'Ms. Amara Okafor', 'amara@cloudmaster.com', 20, 0, 300000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(4, 'Cybersecurity Fundamentals', 'Learn network security, encryption, threat detection, incident response, and security best practices. Includes hands-on labs and real-world case studies.', NULL, NULL, 'technical', 'Cybersecurity', 20, '2026-05-23', '2026-06-23', 'Abuja Training Center', 'Mr. David Okonkwo', 'david.okonkwo@cybersec.com', 15, 15, 180000.00, 'NGN', 'completed', 0, 1, '2026-07-23 15:14:04'),
(5, 'Data Science & Machine Learning', 'Complete data science pipeline including pandas, numpy, scikit-learn, tensorflow, and data visualization. Build and deploy machine learning models.', NULL, NULL, 'technical', 'Data Analysis', 40, '2026-09-23', '2026-10-23', 'Virtual - Online', 'Dr. Chioma Eze', 'chioma.eze@datasci.com', 25, 0, 350000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(6, 'Effective Communication Skills', 'Master verbal and non-verbal communication, active listening, presentation skills, and conflict resolution. Transform your professional communication.', NULL, NULL, 'soft_skill', 'Communication', 16, '2026-08-06', '2026-08-23', 'Lagos Business School', 'Mr. Kunle Adebayo', 'kunle.adebayo@softskills.com', 40, 0, 75000.00, 'NGN', 'cancelled', 0, 1, '2026-07-23 15:14:04'),
(7, 'Emotional Intelligence at Work', 'Develop self-awareness, empathy, social skills, and emotional regulation for professional success. Build stronger relationships and leadership presence.', NULL, NULL, 'soft_skill', 'Leadership', 12, '2026-08-13', '2026-08-23', 'Virtual - Zoom', 'Ms. Ngozi Obi', 'ngozi.obi@eqworks.com', 35, 0, 65000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(8, 'Public Speaking & Presentation', 'Overcome stage fright, structure compelling presentations, use visual aids effectively, and engage audiences. Become a confident speaker.', NULL, NULL, 'soft_skill', 'Communication', 14, '2026-08-23', '2026-09-23', 'Abuja Conference Center', 'Mr. Tunde Bakare', 'tunde.bakare@speakup.com', 25, 0, 85000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(9, 'Team Collaboration & Building', 'Build high-performing teams, foster collaboration, resolve conflicts, and create a positive team culture. Learn proven team building strategies.', NULL, NULL, 'soft_skill', 'Leadership', 10, '2026-08-06', '2026-08-13', 'Virtual - Teams', 'Ms. Funmi Akinlade', 'funmi.akinlade@teamwork.com', 30, 0, 55000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(10, 'Leadership Essentials', 'Essential leadership skills: decision-making, delegation, motivation, and leading through change. Perfect for new and aspiring leaders.', NULL, NULL, 'soft_skill', 'Leadership', 15, '2026-07-30', '2026-08-23', 'Lagos Training Center', 'Mr. Adeolu Ogunleye', 'adeolu@leadershipex.com', 25, 0, 90000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(11, 'Agile Project Management', 'Scrum, Kanban, agile methodologies, sprint planning, retrospectives, and delivering value iteratively. Get certified in Agile practices.', NULL, NULL, 'management', 'Project Management', 24, '2026-08-23', '2026-09-23', 'Virtual - Jira Platform', 'Mr. Chidi Nwosu', 'chidi.nwosu@agilepro.com', 20, 0, 220000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(12, 'Digital Transformation Leadership', 'Leading organizational change, digital strategy, innovation culture, and technology adoption. Navigate the digital landscape effectively.', NULL, NULL, 'management', 'Leadership', 20, '2026-05-23', '2026-06-23', 'Abuja Business Center', 'Dr. Olumide Ola', 'olumide.ola@digitallead.com', 15, 15, 280000.00, 'NGN', 'completed', 0, 1, '2026-07-23 15:14:04'),
(13, 'Financial Management for Non-Finance Managers', 'Understanding financial statements, budgeting, forecasting, ROI analysis, and financial decision-making. Demystify finance for managers.', NULL, NULL, 'management', 'Finance', 16, '2026-08-13', '2026-08-23', 'Lagos Financial District', 'Ms. Bose Adebayo', 'bose.adebayo@finpro.com', 20, 1, 160000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(14, 'Data-Driven Decision Making', 'Using data analytics for strategic decisions, KPI development, dashboard design, and data storytelling. Transform data into business insights.', NULL, NULL, 'management', 'Data Analysis', 14, '2026-07-16', '2026-10-23', 'Virtual - Online', 'Mr. Emeka Okafor', 'emeka.okafor@datadriven.com', 20, 6, 190000.00, 'NGN', 'ongoing', 0, 1, '2026-07-23 15:14:04'),
(15, 'Leading Remote & Hybrid Teams', 'Managing distributed teams, virtual collaboration, maintaining culture, and remote team productivity. Master the future of work.', NULL, NULL, 'management', 'Leadership', 12, '2026-07-30', '2026-08-06', 'Virtual - Zoom', 'Ms. Zainab Mohammed', 'zainab@remotework.com', 25, 0, 120000.00, 'NGN', 'cancelled', 0, 1, '2026-07-23 15:14:04'),
(16, 'Data Privacy & GDPR Compliance', 'Understanding data protection laws, GDPR requirements, data handling procedures, and privacy compliance. Essential for all organizations.', NULL, NULL, 'compliance', 'Security', 8, '2026-07-16', '2026-08-23', 'Virtual - Online', 'Mr. Tony Uche', 'tony.uche@dataprivacy.com', 50, 0, 50000.00, 'NGN', 'cancelled', 0, 1, '2026-07-23 15:14:04'),
(17, 'Workplace Safety & Health', 'Occupational health, safety protocols, emergency procedures, and creating a safe work environment. Ensure workplace safety compliance.', NULL, NULL, 'compliance', 'Safety', 6, '2026-07-30', '2026-08-06', 'Lagos Training Center', 'Ms. Grace Eze', 'grace.eze@safetypro.com', 40, 0, 35000.00, 'NGN', 'upcoming', 0, 1, '2026-07-23 15:14:04'),
(18, 'Diversity & Inclusion in the Workplace', 'Understanding unconscious bias, inclusive workplace practices, and promoting diversity in organizations. Build an inclusive culture.', NULL, NULL, 'compliance', 'HR', 8, '2026-05-23', '2026-06-23', 'Virtual - Zoom', 'Ms. Funke Williams', 'funke.williams@diversity.com', 35, 35, 45000.00, 'NGN', 'completed', 0, 1, '2026-07-23 15:14:04'),
(19, 'Anti-Money Laundering (AML)', 'AML regulations, KYC procedures, suspicious activity reporting, and financial crime prevention. Essential for financial institutions.', NULL, NULL, 'compliance', 'Finance', 10, '2026-05-23', '2026-06-23', 'Abuja Finance Center', 'Mr. Segun Akinlade', 'segun.akinlade@amlpro.com', 20, 20, 120000.00, 'NGN', 'completed', 0, 1, '2026-07-23 15:14:04'),
(20, 'ISO 27001 Information Security', 'Information security management systems, risk assessment, security controls, and ISO compliance. Prepare for ISO 27001 certification.', NULL, NULL, 'compliance', 'Security', 14, '2026-07-16', '2026-11-23', 'Virtual - Online', 'Mr. Charles Okafor', 'charles.okafor@isosec.com', 15, 7, 200000.00, 'NGN', 'ongoing', 0, 1, '2026-07-23 15:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') DEFAULT 'employee',
  `employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `employee_id`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-07-22 17:53:02', '2026-07-22 17:53:02'),
(2, 'superadmin', 'superadmin@admin.com', '$2y$10$F/nZIwT8Z1hrPyUJYTVTy.4UJb9jBGWRferzqImicpndwx1X62HAG', 'admin', 2, '2026-07-22 23:57:42', '2026-07-22 23:57:42'),
(3, 'employee', 'employee@example.com', '$2y$10$LftjNwLiAyby9Wz554tWYuuxXDnlvNgv6LTjqhCGk81ysOBjtOTfO', 'employee', 4, '2026-07-23 04:39:00', '2026-07-23 04:39:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_skill` (`employee_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `employee_trainings`
--
ALTER TABLE `employee_trainings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`employee_id`,`training_id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `plan_tasks`
--
ALTER TABLE `plan_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skill_name` (`skill_name`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `training_materials`
--
ALTER TABLE `training_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `development_plans`
--
ALTER TABLE `development_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_trainings`
--
ALTER TABLE `employee_trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_tasks`
--
ALTER TABLE `plan_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `training_materials`
--
ALTER TABLE `training_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certifications`
--
ALTER TABLE `certifications`
  ADD CONSTRAINT `certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `certifications_ibfk_2` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`);

--
-- Constraints for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD CONSTRAINT `development_plans_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `development_plans_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Constraints for table `employee_trainings`
--
ALTER TABLE `employee_trainings`
  ADD CONSTRAINT `employee_trainings_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_trainings_ibfk_2` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `plan_tasks`
--
ALTER TABLE `plan_tasks`
  ADD CONSTRAINT `plan_tasks_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `development_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `training_materials`
--
ALTER TABLE `training_materials`
  ADD CONSTRAINT `training_materials_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD CONSTRAINT `training_programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
