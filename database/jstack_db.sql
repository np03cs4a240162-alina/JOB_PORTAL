-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 09:37 PM
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
-- Database: `jstack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_resume_logs`
--

CREATE TABLE `ai_resume_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `extracted_json` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `seeker_id` int(11) NOT NULL,
  `resume_note` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES
(1, 6, 6, 'skilled in figma...', 'accepted', '2026-04-04 17:10:48'),
(2, 5, 6, 'Skilll in react...', 'rejected', '2026-04-04 18:23:04'),
(3, 7, 6, 'seo good knowledge...', 'rejected', '2026-04-04 19:09:09'),
(4, 8, 6, 'Experience over 3 years...', 'rejected', '2026-04-04 19:25:18'),
(5, 9, 6, 'accepted', 'accepted', '2026-04-04 19:30:50');

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('verification','reset') NOT NULL,
  `expiry` datetime NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employer_profiles`
--

INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES
(1, 2, 'Cedar abc', 'IT', 'cedar.com', 'hghj', NULL),
(2, 4, 'Yatrik', 'IT', 'yatrik.com', '', 'img_69e397aff1e2d.png'),
(3, 7, 'Cedar Gate Tech', 'IT', 'cedar.com', 'Established in 2016', 'uploads/logos/logo_7_1775333929.jpg'),
(4, 10, 'Cedar Gate Technologies', 'IT', 'cedargate.com', 'IT company since 2015AD.', 'img_69daa1395fd10.png');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `company` varchar(150) NOT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Full Time',
  `experience_level` varchar(50) DEFAULT 'entry',
  `workplace` varchar(50) DEFAULT 'On-site',
  `location` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `employer_id` int(11) NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `industry` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`, `industry`) VALUES
(1, 'hjghjg', 'hjghjgjhg', '56565', 'IT', 'Full Time', 'entry', 'On-site', 'hjghjghjvhvhjfyfyf', 'hgfghf', NULL, 2, 'active', '2026-03-28 13:17:10', NULL),
(4, 'backend senior level', 'yatrik', '1200', 'IT', 'Full Time', 'entry', 'On-site', 'remote', '6 years experience', NULL, 4, 'active', '2026-03-28 13:53:33', NULL),
(5, 'frontend dev', 'cedargate', '1200', 'IT', 'Full Time', 'entry', 'On-site', 'sanepa', 'experienced', NULL, 4, 'active', '2026-03-29 02:45:36', NULL),
(6, 'designer', 'cedar', '800', 'IT', 'Full Time', 'entry', 'On-site', 'sanepa', '2 year experience', NULL, 4, 'active', '2026-04-04 16:21:10', NULL),
(7, 'SEO Specialist', 'Wevolve', '400', 'IT', 'Full Time', 'entry', 'On-site', 'Naxal', 'Experience:2 years', NULL, 4, 'active', '2026-04-04 18:59:20', NULL),
(8, 'Graphics Designer', 'Yatrik', '200', 'IT', 'Full Time', 'entry', 'On-site', 'Putalisadak', 'Experience:Mid-Level', NULL, 4, 'active', '2026-04-04 19:24:16', NULL),
(9, 'Junior Designer', 'Cedar', '100', 'IT', 'Full Time', 'entry', 'On-site', 'Sanepa', 'junior designer experience:1 year', NULL, 4, 'active', '2026-04-04 19:30:27', NULL),
(10, 'Wordpress Developer', 'Cedar Gate Tech', '1000', 'IT', 'Full Time', 'entry', 'On-site', 'Banepa', 'Experience:2 years', NULL, 7, 'active', '2026-04-04 19:47:01', NULL),
(11, 'Senior Designer', 'Cedar', '800', 'IT', 'Full Time', 'entry', 'On-site', 'Hadigaun', 'Experience:5 years', NULL, 7, 'active', '2026-04-05 03:11:40', NULL),
(13, 'UI/ux designer', 'Cedar gate', '$800', 'IT', 'Full Time', 'entry', 'On-site', 'Naxal', 'Exp:1 year', NULL, 10, 'active', '2026-04-11 18:10:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(150) NOT NULL,
  `rating` int(1) NOT NULL,
  `review` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seeker_profiles`
--

CREATE TABLE `seeker_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `parsed_skills` text DEFAULT NULL,
  `parsed_education` text DEFAULT NULL,
  `parsed_experience` text DEFAULT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seeker_profiles`
--

INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`, `parsed_skills`, `parsed_education`, `parsed_experience`, `resume_file_path`) VALUES
(1, 1, '9800111000', 'php, sql', '1', 'Random info', NULL, NULL, NULL, NULL, NULL),
(2, 3, '9767990227', 'php', '1', 'alinais', 'img_69dabc0e626df.jpg', NULL, NULL, NULL, NULL),
(4, 6, '2424242424', 'php', '5', 'ddbfbfbfbvbv', 'uploads/photos/photo_6_1775329676.png', NULL, NULL, NULL, NULL),
(6, 9, '9767990227', 'Php', '2', 'Skilled', 'img_69daa9e9b121d.png', NULL, NULL, NULL, NULL),
(7, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 17, NULL, NULL, NULL, NULL, 'img_69ff792692f7a.jpeg', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES
(1, 2, 'Full-stack Web Development', 'Master React, Node.js, and SQL to build modern applications.', 'Rs. 25,000', '3 Months', 'active', '2026-04-12 03:43:37'),
(2, 2, 'UI/UX Design Essentials', 'Learn Figma and user-centric design principles for mobile and web.', 'Rs. 15,000', '6 Weeks', 'active', '2026-04-12 03:43:37'),
(3, 2, 'Digital Marketing Growth', 'Master SEO, Ads, and Content Strategy to scale businesses.', 'Rs. 12,000', '1 Month', 'active', '2026-04-12 03:43:37'),
(4, 10, 'Advance Graphics Seminae', 'Advance tools in Graphics like Figma, Photoshop,Indesign and Illustrator', '5000rs', '2 days', 'active', '2026-04-12 03:45:04'),
(5, 10, 'Basic Code Camp', 'The students will learn about the basic coding languages fundamental including html, css , js and php with some knowledge of frameworks like react,node.js .', '20000', '8 weeks', 'active', '2026-04-12 07:16:59'),
(6, 4, 'Mental health Workshop', 'You will learn techniques to keep your brain healthy and stay happy.', '5000rs', '2 days', 'active', '2026-04-18 20:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `training_enrollments`
--

CREATE TABLE `training_enrollments` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registered_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_enrollments`
--

INSERT INTO `training_enrollments` (`id`, `training_id`, `user_id`, `registered_at`, `status`) VALUES
(1, 5, 10, '2026-04-12 07:54:23', 'pending'),
(3, 1, 10, '2026-04-12 07:54:38', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employer','seeker') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `skills` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_verified`, `verification_token`, `created_at`, `skills`, `education`, `experience`) VALUES
(1, 'hjghghjghj', 'sth@gmail.com', '$2y$10$KMvwwjA/.EzqTC9dwQaZvOPUrm6nWvFSq0iMh9j0KZvO7eRsMidb.', 'seeker', 1, NULL, '2026-03-28 13:06:35', NULL, NULL, NULL),
(2, 'Cedsr', 'cedar@gmail.com', '$2y$10$xWGTF1saD4X4zLmXRyK0QO.zofxwmStmfjWEGCtHGitRfnqGG6W6O', 'employer', 1, NULL, '2026-03-28 13:14:32', NULL, NULL, NULL),
(3, 'Alinaaaa', 'alina@gmail.com', '$2y$10$8O95i7pVXPzR5OYPfogfROMHbuHs6ieaNf79Q3VkTVhx/sRlUTUcG', 'seeker', 1, NULL, '2026-03-28 13:33:04', NULL, NULL, NULL),
(4, 'yatrik', 'yatrik@gmail.com', '$2y$10$dRnDn/ercg3.4mbgXooL6OWNHSDEn4kTbG62C40nr8vyDTJV/shiy', 'employer', 1, NULL, '2026-03-28 13:37:42', NULL, NULL, NULL),
(5, 'Roshan', 'roshan@13gmai.com', 'roshan@123', 'seeker', 1, NULL, '2026-04-01 03:00:44', NULL, NULL, NULL),
(6, 'Alina', 'digitalmarketing.ab7@gmail.com', '$2y$10$2s49JwGxAfArPbMK7npc4.s5o6hay77/3AHq.tUHXubr7DnbXQTBG', 'seeker', 1, NULL, '2026-04-04 17:02:18', NULL, NULL, NULL),
(7, 'Cedar Gate Tech', 'cedargate@gmail.com', '$2y$10$BnRemZAY.fMh97JvsHPA3.xW./.a4fecsGKKbJxR2UBXGQazkyF9O', 'employer', 1, NULL, '2026-04-04 19:45:35', NULL, NULL, NULL),
(8, 'ALINA', 'alinamanandhar2019@gmail.com', '$2y$10$UN210UoLU51HwDZ1LIG4DOSUcRur/16CKiD2f94Lqoib7pe8UfsDW', 'seeker', 1, NULL, '2026-04-07 18:02:51', NULL, NULL, NULL),
(9, 'alina', 'alisha@gmail.com', '$2y$10$cJs.kIvsT5YrgjTbj4iDq.CLc0/5iurLXtERBmzhHtMLcKZqn/c2K', 'seeker', 1, NULL, '2026-04-11 15:20:36', NULL, NULL, NULL),
(10, 'alish', 'alisha1@gmail.com', '$2y$10$3PxMdp4G8.h5WVJcI4OEz.AWsFhUBxrfzY8FW3Y9bsDoyROMtApV.', 'employer', 1, NULL, '2026-04-11 15:21:41', NULL, NULL, NULL),
(14, 'Roshan Kumar Yadav', 'roshanadkhari68@gmail.com', '$2y$10$FfxIx4Ra72mBupK1zMF5tusEZDqXdtNvkn5n8Bb0caEGMUai42M9G', 'seeker', 1, NULL, '2026-05-08 03:04:34', 'Python, HTML & CSS, JS', 'Bachelor', '3 Years'),
(15, 'Test User', 'test@example.com', '$2y$10$vIUqXbuJFB4BozWn8czmTO9rVG6wW/U2TDnS87BCp6yBk7OWFiglq', 'seeker', 1, NULL, '2026-05-09 17:58:21', NULL, NULL, NULL),
(16, 'Ram', 'by920706@gmail.com', '$2y$10$EvS9LaJDM0bGx/EOw4FdJeHn2mzBpiBhJuyoaChpS0LmMEsVfFIoK', 'seeker', 1, NULL, '2026-05-09 18:05:00', NULL, NULL, NULL),
(17, 'Roshan Kumar Yadav', 'yadavroshankumar746@gmail.com', '$2y$10$WsDhXUzzh0qbhdXAKsP9kutIMhmJ1wClYGBMlrEMxkzXw/5d/9LQe', 'seeker', 1, NULL, '2026-05-09 18:07:33', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_resume_logs`
--
ALTER TABLE `ai_resume_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_resume_logs_ibfk_1` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_app` (`job_id`,`seeker_id`),
  ADD KEY `seeker_id` (`seeker_id`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_ibfk_1` (`from_user`),
  ADD KEY `messages_ibfk_2` (`to_user`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resumes_ibfk_1` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_ibfk_1` (`user_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_save` (`user_id`,`job_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `seeker_profiles`
--
ALTER TABLE `seeker_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`training_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `ai_resume_logs`
--
ALTER TABLE `ai_resume_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seeker_profiles`
--
ALTER TABLE `seeker_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_resume_logs`
--
ALTER TABLE `ai_resume_logs`
  ADD CONSTRAINT `ai_resume_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `employer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `resumes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seeker_profiles`
--
ALTER TABLE `seeker_profiles`
  ADD CONSTRAINT `seeker_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trainings`
--
ALTER TABLE `trainings`
  ADD CONSTRAINT `trainings_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  ADD CONSTRAINT `training_enrollments_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
