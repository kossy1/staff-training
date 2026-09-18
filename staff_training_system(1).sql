-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 11:43 PM
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
  `training_id` int(11) DEFAULT NULL,
  `certification_name` varchar(255) NOT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `certification_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `position`, `date_of_joining`, `date_of_birth`, `gender`, `address`, `city`, `state`, `bio`, `profile_picture`, `employee_code`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'John', 'Doe', 'employee@example.com', '08012345678', 'Sales', 'Sales Representative', '2026-09-18', NULL, NULL, NULL, NULL, NULL, NULL, 'profile_1_1789748412.jpg', 'EMP001', NULL, 'active', '2026-09-18 15:13:20', '2026-09-18 16:20:12');

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `years_experience` decimal(3,1) DEFAULT 0.0,
  `last_updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_trainings`
--

CREATE TABLE `employee_trainings` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_gateway` varchar(50) DEFAULT 'paystack',
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `attended` tinyint(1) DEFAULT 0,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `reference` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'NGN',
  `status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `gateway` varchar(50) DEFAULT 'paystack',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'site_name', 'THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE', 'text', '2026-09-18 15:13:21'),
(2, 'site_email', 'sdc@polyibadan.edu.ng', 'email', '2026-09-18 15:13:21'),
(3, 'site_phone', '+234 800 000 0000', 'text', '2026-09-18 15:13:21'),
(4, 'items_per_page', '25', 'number', '2026-09-18 15:13:21'),
(5, 'max_file_size', '5', 'number', '2026-09-18 15:13:21'),
(6, 'enable_registration', '1', 'boolean', '2026-09-18 15:13:21'),
(7, 'enable_email_notifications', '1', 'boolean', '2026-09-18 15:13:21'),
(8, 'maintenance_mode', '0', 'boolean', '2026-09-18 15:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 15:24:58'),
(2, 1, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 15:25:17'),
(3, 2, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 15:25:20'),
(4, 2, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 15:25:26'),
(5, 3, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 15:25:28'),
(6, 3, 'trainer_logout', NULL, '127.0.0.1', NULL, '2026-09-18 15:44:02'),
(7, 1, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 15:44:48'),
(8, 1, 'profile_updated', NULL, '127.0.0.1', NULL, '2026-09-18 15:45:44'),
(9, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:13:29'),
(10, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:13:50'),
(11, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:16:47'),
(12, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:19:18'),
(13, 1, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 16:19:33'),
(14, 3, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 16:19:38'),
(15, 3, 'trainer_logout', NULL, '127.0.0.1', NULL, '2026-09-18 16:19:44'),
(16, 2, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 16:19:47'),
(17, 2, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 16:19:54'),
(18, 2, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 16:19:59'),
(19, 2, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:20:12'),
(20, 2, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 16:35:44'),
(21, 1, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 16:36:01'),
(22, 1, 'profile_picture_updated', '{\"filename\":\"profile__1789749393.jpg\",\"size\":20337}', '127.0.0.1', NULL, '2026-09-18 16:36:34'),
(23, 1, 'profile_picture_updated', '{\"filename\":\"profile__1789749394.jpg\",\"size\":20337}', '127.0.0.1', NULL, '2026-09-18 16:36:34'),
(24, 1, 'profile_picture_updated', '{\"filename\":\"profile__1789749405.jpg\",\"size\":20337}', '127.0.0.1', NULL, '2026-09-18 16:36:45'),
(25, 1, 'profile_picture_updated', '{\"filename\":\"profile__1789750221.jpg\",\"size\":20337}', '127.0.0.1', NULL, '2026-09-18 16:50:21'),
(26, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 16:58:17'),
(27, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 20:44:29'),
(28, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 20:45:50'),
(29, 1, 'profile_picture_updated', NULL, '127.0.0.1', NULL, '2026-09-18 20:59:51'),
(30, 1, 'profile_updated', NULL, '127.0.0.1', NULL, '2026-09-18 21:00:57'),
(31, 1, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 21:12:47'),
(32, 3, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 21:12:53'),
(33, 3, 'trainer_logout', NULL, '127.0.0.1', NULL, '2026-09-18 21:21:22'),
(34, 2, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 21:21:25'),
(35, 2, 'logout', NULL, '127.0.0.1', NULL, '2026-09-18 21:24:59'),
(36, 1, 'login', '{\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2026-09-18 21:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Nigeria',
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_trainings` int(11) DEFAULT 0,
  `total_sessions` int(11) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `specialization`, `qualification`, `experience_years`, `bio`, `profile_picture`, `linkedin_url`, `twitter_url`, `website_url`, `address`, `city`, `state`, `country`, `status`, `rating`, `total_trainings`, `total_sessions`, `total_students`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 'Jane', 'Trainer', 'trainer@example.com', '08087654321', 'Programming', 'PhD Computer Science', 10, '', 'trainer_1_1789746236.jpg', '', NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 15:13:20', '2026-09-18 15:43:56'),
(2, NULL, 'Adebayo', 'Ogundipe', 'adebayo.ogundipe@polyibadan.edu.ng', '08012345001', 'Programming', 'PhD Computer Science', 12, 'Senior software engineer with 12 years of experience in full-stack development, specializing in Python, JavaScript, and cloud architecture.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(3, NULL, 'Folake', 'Adeyemi', 'folake.adeyemi@polyibadan.edu.ng', '08012345002', 'Leadership', 'MBA Business Administration', 15, 'Leadership coach and management consultant with over 15 years of experience training executives across various industries.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(4, NULL, 'Emeka', 'Nwosu', 'emeka.nwosu@polyibadan.edu.ng', '08012345003', 'Data Science', 'MSc Data Analytics', 8, 'Data scientist specializing in machine learning, statistical analysis, and business intelligence solutions.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(5, NULL, 'Chioma', 'Okonkwo', 'chioma.okonkwo@polyibadan.edu.ng', '08012345004', 'Soft Skills', 'MA Communication Studies', 10, 'Communication expert and corporate trainer focused on soft skills development and workplace effectiveness.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(6, NULL, 'Tunde', 'Bakare', 'tunde.bakare@polyibadan.edu.ng', '08012345005', 'Cybersecurity', 'CISSP, CEH Certified', 11, 'Cybersecurity specialist with extensive experience in network security, penetration testing, and security audits.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(7, NULL, 'Ngozi', 'Eze', 'ngozi.eze@polyibadan.edu.ng', '08012345006', 'Finance', 'FCA Chartered Accountant', 14, 'Chartered accountant and financial consultant with expertise in corporate finance and risk management.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(8, NULL, 'Segun', 'Akinlade', 'segun.akinlade@polyibadan.edu.ng', '08012345007', 'Project Management', 'PMP Certified', 13, 'Project management professional with PMP certification and 13 years of experience delivering complex projects.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(9, NULL, 'Aisha', 'Mohammed', 'aisha.mohammed@polyibadan.edu.ng', '08012345008', 'HR Management', 'MCIPM Human Resources', 9, 'Human resources expert specializing in talent development, performance management, and HR analytics.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(10, NULL, 'Kunle', 'Adebayo', 'kunle.adebayo@polyibadan.edu.ng', '08012345009', 'Digital Marketing', 'Google Certified', 10, 'Digital marketing specialist with expertise in SEO, SEM, social media, and content marketing strategies.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(11, NULL, 'Blessing', 'Obi', 'blessing.obi@polyibadan.edu.ng', '08012345010', 'Cloud Computing', 'AWS Solutions Architect', 7, 'Cloud architect certified in AWS and Azure with hands-on experience in cloud migration and DevOps.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(12, NULL, 'Ibrahim', 'Yusuf', 'ibrahim.yusuf@polyibadan.edu.ng', '08012345011', 'Business Analysis', 'CBAP Certified', 11, 'Business analyst with CBAP certification and expertise in requirements gathering and process improvement.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43'),
(13, NULL, 'Modupe', 'Afolabi', 'modupe.afolabi@polyibadan.edu.ng', '08012345012', 'UI/UX Design', 'MFA Design', 8, 'Creative designer specializing in user experience, product design, and design thinking methodologies.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nigeria', 'active', 0.00, 0, 0, 0, NULL, '2026-09-18 21:24:43', '2026-09-18 21:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_requests`
--

CREATE TABLE `trainer_requests` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `training_id` int(11) DEFAULT NULL,
  `request_type` enum('enrollment','feedback','support','reschedule') DEFAULT 'enrollment',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected','resolved') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_sessions`
--

CREATE TABLE `trainer_sessions` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `training_id` int(11) DEFAULT NULL,
  `session_date` datetime NOT NULL,
  `duration_hours` int(11) DEFAULT 2,
  `location` varchar(255) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `attendance_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('technical','soft_skill','management','compliance','other') DEFAULT 'technical',
  `category` varchar(100) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL,
  `trainer_email` varchar(100) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 20,
  `current_participants` int(11) DEFAULT 0,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `prerequisites` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `is_certified` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `training_programs`
--

INSERT INTO `training_programs` (`id`, `title`, `description`, `type`, `category`, `duration_hours`, `start_date`, `end_date`, `location`, `trainer_id`, `trainer_name`, `trainer_email`, `max_participants`, `current_participants`, `cost`, `status`, `prerequisites`, `learning_objectives`, `is_certified`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Full-Stack Web Development Bootcamp', 'Comprehensive training covering HTML5, CSS3, JavaScript, React, Node.js, Express, and MongoDB. Build 5 real-world projects including an e-commerce site, a dashboard, and a REST API. Learn industry best practices, version control with Git, and deployment strategies.', 'technical', 'Programming', 40, '2026-10-02', '2026-11-01', 'Computer Lab A, SDC Building', 1, 'Adebayo Ogundipe', 'adebayo.ogundipe@polyibadan.edu.ng', 25, 0, 250000.00, 'upcoming', 'Basic computer literacy required. Prior exposure to any programming language is helpful but not mandatory.', 'By the end of this training, participants will be able to build complete full-stack web applications, work with databases, implement authentication, and deploy applications to production.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(2, 'Advanced Python Programming & Automation', 'Deep dive into Python advanced concepts: decorators, generators, context managers, async/await, type hints, and testing. Learn to build automation scripts, web scrapers, and CLI tools. Includes working with REST APIs and database integration.', 'technical', 'Programming', 30, '2026-09-25', '2026-10-25', 'Computer Lab B, SDC Building', 1, 'Adebayo Ogundipe', 'adebayo.ogundipe@polyibadan.edu.ng', 20, 0, 200000.00, 'upcoming', 'Intermediate Python knowledge required (variables, functions, loops, basic OOP).', 'Master advanced Python techniques, build automation tools, and write production-quality Python code.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(3, 'Cloud Computing with AWS', 'Master AWS services: EC2, S3, Lambda, RDS, VPC, IAM, CloudFormation. Prepare for AWS Certified Solutions Architect Associate exam. Includes hands-on labs with real AWS accounts and cost optimization strategies.', 'technical', 'Cloud Computing', 45, '2026-10-09', '2026-11-23', 'Virtual Training (Zoom + AWS Console)', 10, 'Blessing Obi', 'blessing.obi@polyibadan.edu.ng', 20, 0, 350000.00, 'upcoming', 'Basic understanding of networking and Linux command line.', 'Deploy and manage applications on AWS, architect highly available systems, and pass the AWS Solutions Architect certification.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(4, 'Cybersecurity Fundamentals & Ethical Hacking', 'Learn network security, cryptography, penetration testing, incident response, and security best practices. Hands-on labs with Kali Linux, Wireshark, Metasploit, and Burp Suite. Covers OWASP Top 10 vulnerabilities.', 'technical', 'Cybersecurity', 35, '2026-09-28', '2026-11-02', 'Computer Lab C, SDC Building', 5, 'Tunde Bakare', 'tunde.bakare@polyibadan.edu.ng', 15, 0, 300000.00, 'upcoming', 'Basic networking knowledge and familiarity with Linux.', 'Identify security vulnerabilities, perform ethical hacking tests, and implement security best practices.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(5, 'Data Science & Machine Learning Fundamentals', 'Complete data science pipeline: data collection, cleaning, analysis, visualization, and machine learning. Uses Python with pandas, numpy, scikit-learn, matplotlib, and TensorFlow. Includes real-world case studies.', 'technical', 'Data Science', 50, '2026-10-18', '2026-12-07', 'Computer Lab A, SDC Building', 3, 'Emeka Nwosu', 'emeka.nwosu@polyibadan.edu.ng', 25, 0, 400000.00, 'upcoming', 'Python programming basics and statistics fundamentals.', 'Build machine learning models, perform data analysis, and create data visualizations to drive business insights.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(6, 'UI/UX Design Masterclass', 'Master user-centered design principles, wireframing, prototyping, user research, and design systems. Hands-on with Figma, Adobe XD, and design thinking methodologies. Build a complete design portfolio.', 'technical', 'UI/UX Design', 30, '2026-10-02', '2026-11-01', 'Design Studio, SDC Building', 12, 'Modupe Afolabi', 'modupe.afolabi@polyibadan.edu.ng', 20, 0, 250000.00, 'upcoming', 'No prior design experience required. Creative mindset is a plus.', 'Design user-friendly interfaces, conduct user research, and create interactive prototypes.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(7, 'Mobile App Development with React Native', 'Build cross-platform mobile apps for iOS and Android using React Native. Learn state management, navigation, native modules, and publishing to app stores. Complete 3 mobile apps during the training.', 'technical', 'Programming', 40, '2026-11-02', '2026-12-12', 'Computer Lab B, SDC Building', 1, 'Adebayo Ogundipe', 'adebayo.ogundipe@polyibadan.edu.ng', 20, 0, 320000.00, 'upcoming', 'JavaScript knowledge and basic React understanding.', 'Build and deploy production-ready mobile applications for both iOS and Android.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(8, 'Effective Communication Skills', 'Master verbal and non-verbal communication, active listening, presentation skills, and conflict resolution. Interactive sessions with role-plays and real-world scenarios. Includes public speaking practice.', 'soft_skill', 'Communication', 16, '2026-09-25', '2026-10-02', 'Conference Room, SDC Building', 4, 'Chioma Okonkwo', 'chioma.okonkwo@polyibadan.edu.ng', 30, 0, 80000.00, 'upcoming', 'Open to all employees who want to improve communication skills.', 'Communicate effectively in professional settings, deliver compelling presentations, and handle difficult conversations.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(9, 'Emotional Intelligence at Work', 'Develop self-awareness, empathy, social skills, and emotional regulation. Learn to manage stress, build better relationships, and lead with emotional intelligence. Includes personality assessments.', 'soft_skill', 'Leadership', 12, '2026-10-09', '2026-10-16', 'Training Room 1, SDC Building', 4, 'Chioma Okonkwo', 'chioma.okonkwo@polyibadan.edu.ng', 25, 0, 65000.00, 'upcoming', 'Open to all employees.', 'Understand and manage emotions, build stronger workplace relationships, and increase leadership effectiveness.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(10, 'Public Speaking & Presentation Mastery', 'Overcome stage fright, structure compelling presentations, use visual aids effectively, and engage audiences. Video-recorded practice sessions with detailed feedback from expert coaches.', 'soft_skill', 'Communication', 14, '2026-10-18', '2026-11-01', 'Auditorium, SDC Building', 4, 'Chioma Okonkwo', 'chioma.okonkwo@polyibadan.edu.ng', 20, 0, 100000.00, 'upcoming', 'Willingness to speak in front of groups.', 'Deliver confident, engaging presentations and overcome public speaking anxiety.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(11, 'Team Collaboration & Conflict Resolution', 'Build high-performing teams, foster collaboration, resolve conflicts constructively, and create a positive team culture. Includes DISC personality assessment and team simulations.', 'soft_skill', 'Leadership', 10, '2026-09-28', '2026-10-05', 'Training Room 2, SDC Building', 2, 'Folake Adeyemi', 'folake.adeyemi@polyibadan.edu.ng', 25, 0, 70000.00, 'upcoming', 'Open to all employees.', 'Work effectively in teams, handle conflicts professionally, and contribute to positive team culture.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(12, 'Time Management & Productivity', 'Master prioritization techniques, time-blocking, the Pomodoro technique, task management, and eliminating distractions. Learn to use productivity tools like Notion, Trello, and Google Calendar.', 'soft_skill', 'Leadership', 8, '2026-09-23', '2026-09-30', 'Training Room 1, SDC Building', 2, 'Folake Adeyemi', 'folake.adeyemi@polyibadan.edu.ng', 30, 0, 55000.00, 'upcoming', 'Open to all employees.', 'Manage time effectively, increase productivity, and achieve work-life balance.', 0, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(13, 'Leadership Essentials for New Managers', 'Essential leadership skills for first-time managers: decision-making, delegation, motivation, feedback, and leading through change. Includes 360-degree feedback assessment.', 'management', 'Leadership', 20, '2026-10-02', '2026-10-22', 'Executive Boardroom, SDC Building', 2, 'Folake Adeyemi', 'folake.adeyemi@polyibadan.edu.ng', 20, 0, 180000.00, 'upcoming', 'Must be a current or aspiring team leader.', 'Lead teams effectively, delegate appropriately, and motivate team members to achieve goals.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(14, 'Agile Project Management (Scrum & Kanban)', 'Master Agile methodologies: Scrum, Kanban, and Lean. Learn sprint planning, daily standups, retrospectives, and backlog management. Prepare for Professional Scrum Master certification.', 'management', 'Project Management', 24, '2026-10-09', '2026-11-02', 'Conference Room, SDC Building', 7, 'Segun Akinlade', 'segun.akinlade@polyibadan.edu.ng', 25, 0, 200000.00, 'upcoming', 'Experience working on projects or in a team environment.', 'Lead Agile teams, manage sprints effectively, and deliver value iteratively.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(15, 'Financial Management for Non-Finance Managers', 'Understand financial statements, budgeting, forecasting, ROI analysis, and financial decision-making. Demystify finance for managers without a finance background.', 'management', 'Finance', 16, '2026-10-18', '2026-11-01', 'Training Room 3, SDC Building', 6, 'Ngozi Eze', 'ngozi.eze@polyibadan.edu.ng', 20, 0, 150000.00, 'upcoming', 'For managers and team leads without finance background.', 'Read financial statements, create budgets, and make data-driven financial decisions.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(16, 'Strategic HR Management', 'Talent acquisition, performance management, employee engagement, retention strategies, and HR analytics. Case studies from Nigerian organizations. Includes HR metrics dashboard training.', 'management', 'HR Management', 24, '2026-10-28', '2026-11-21', 'Conference Room, SDC Building', 8, 'Aisha Mohammed', 'aisha.mohammed@polyibadan.edu.ng', 20, 0, 220000.00, 'upcoming', 'HR practitioners or managers with people responsibilities.', 'Implement strategic HR initiatives, measure HR effectiveness, and drive organizational performance.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(17, 'Business Analysis & Process Improvement', 'Learn requirements gathering, process modeling, gap analysis, and business process improvement. Uses BPMN, UML, and modern business analysis techniques. Prepares for CBAP certification.', 'management', 'Business Analysis', 30, '2026-11-17', '2026-12-22', 'Training Room 2, SDC Building', 11, 'Ibrahim Yusuf', 'ibrahim.yusuf@polyibadan.edu.ng', 15, 0, 250000.00, 'upcoming', 'Basic understanding of business processes.', 'Analyze business needs, document requirements, and drive process improvements.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(18, 'Data Privacy & GDPR Compliance', 'Understanding data protection laws, GDPR requirements, data handling procedures, and privacy compliance. Critical for all employees handling customer data.', 'compliance', 'Security', 8, '2026-09-25', '2026-10-02', 'Virtual Training (Zoom)', 5, 'Tunde Bakare', 'tunde.bakare@polyibadan.edu.ng', 50, 0, 50000.00, 'upcoming', 'Mandatory for all staff handling customer data.', 'Handle personal data responsibly, ensure GDPR compliance, and prevent data breaches.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(19, 'Workplace Health & Safety', 'Occupational health, safety protocols, emergency procedures, first aid basics, and creating a safe work environment. Includes fire safety and evacuation drills.', 'compliance', 'Safety', 6, '2026-09-23', '2026-09-28', 'SDC Main Hall', 5, 'Tunde Bakare', 'tunde.bakare@polyibadan.edu.ng', 60, 0, 30000.00, 'upcoming', 'Mandatory for all staff.', 'Identify workplace hazards, respond to emergencies, and maintain a safe work environment.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(20, 'Anti-Money Laundering (AML) Compliance', 'AML regulations, KYC procedures, suspicious activity reporting, and financial crime prevention. Essential for staff in finance, customer service, and compliance roles.', 'compliance', 'Finance', 10, '2026-11-02', '2026-11-12', 'Executive Boardroom, SDC Building', 6, 'Ngozi Eze', 'ngozi.eze@polyibadan.edu.ng', 25, 0, 120000.00, 'upcoming', 'Staff in finance, compliance, or customer-facing roles.', 'Recognize money laundering patterns, follow KYC procedures, and report suspicious activities.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(21, 'Digital Marketing Masterclass', 'Complete digital marketing course covering SEO, SEM, social media marketing, content marketing, email marketing, and analytics. Get Google Ads and Analytics certified.', 'technical', 'Digital Marketing', 35, '2026-10-09', '2026-11-13', 'Computer Lab A, SDC Building', 9, 'Kunle Adebayo', 'kunle.adebayo@polyibadan.edu.ng', 25, 0, 280000.00, 'upcoming', 'Basic computer skills. No prior marketing experience required.', 'Plan and execute digital marketing campaigns, analyze performance, and grow online presence.', 1, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45'),
(22, 'Social Media Management & Content Strategy', 'Learn to manage social media accounts professionally, create engaging content, build communities, and measure ROI. Includes Instagram, LinkedIn, Twitter, Facebook, and TikTok strategies.', 'technical', 'Digital Marketing', 20, '2026-10-23', '2026-11-12', 'Computer Lab B, SDC Building', 9, 'Kunle Adebayo', 'kunle.adebayo@polyibadan.edu.ng', 20, 0, 150000.00, 'upcoming', 'Basic familiarity with social media platforms.', 'Create effective social media strategies, produce engaging content, and grow brand presence.', 0, 1, '2026-09-18 21:24:45', '2026-09-18 21:24:45');

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
  `role` enum('admin','manager','employee','trainer') DEFAULT 'employee',
  `employee_id` int(11) DEFAULT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `employee_id`, `trainer_id`, `first_name`, `last_name`, `phone`, `profile_picture`, `status`, `email_verified`, `verification_token`, `remember_token`, `last_login`, `login_attempts`, `locked_until`, `password_changed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'superadmin@example.com', '$2y$10$rIR.wB6zIeh.zQk34Fkw.uNCyx0xjdL.Dofp/cLeC7QCCTJq0pUE2', 'admin', NULL, NULL, 'System', 'Administrator', NULL, NULL, 'active', 1, NULL, NULL, NULL, 0, NULL, '2026-09-18 15:13:20', NULL, '2026-09-18 15:13:20', '2026-09-18 15:22:06'),
(2, 'employee', 'employee@example.com', '$2y$10$9QCfl.TvUrf/MW8krR1N5Ok.JXO/ptomPx5Akf4dBRJ6uryxLDii.', 'employee', 1, NULL, 'John', 'Doe', NULL, NULL, 'active', 1, NULL, NULL, NULL, 0, NULL, '2026-09-18 15:13:20', NULL, '2026-09-18 15:13:20', '2026-09-18 15:23:09'),
(3, 'trainer', 'trainer@example.com', '$2y$10$ULriYX4.4a6eFfviSgAozuoLIToLVsVezc82XRCL8svcs1goXVgVW', 'trainer', NULL, 1, 'Jane', 'Trainer', NULL, NULL, 'active', 1, NULL, NULL, NULL, 0, NULL, '2026-09-18 15:13:21', NULL, '2026-09-18 15:13:21', '2026-09-18 15:23:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_training` (`training_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry` (`expiry_date`);

--
-- Indexes for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`employee_code`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_skill` (`employee_id`,`skill_id`),
  ADD KEY `idx_employee` (`employee_id`);

--
-- Indexes for table `employee_trainings`
--
ALTER TABLE `employee_trainings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_training` (`training_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment` (`payment_status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `idx_reference` (`reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_training` (`training_id`);

--
-- Indexes for table `plan_tasks`
--
ALTER TABLE `plan_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan` (`plan_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `trainer_requests`
--
ALTER TABLE `trainer_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `trainer_sessions`
--
ALTER TABLE `trainer_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_training` (`training_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`session_date`);

--
-- Indexes for table `training_materials`
--
ALTER TABLE `training_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training` (`training_id`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_status` (`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_trainings`
--
ALTER TABLE `employee_trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
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
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `trainer_requests`
--
ALTER TABLE `trainer_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_sessions`
--
ALTER TABLE `trainer_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_materials`
--
ALTER TABLE `training_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
