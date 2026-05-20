SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS jstack_db;
USE jstack_db;

CREATE TABLE `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `seeker_id` int NOT NULL,
  `resume_note` text,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_app` (`job_id`,`seeker_id`),
  KEY `seeker_id` (`seeker_id`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13  ;

INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('1', '6', '6', 'skilled in figma and experience over two years.', 'accepted', '2026-04-04 22:55:48');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('2', '5', '6', 'Skilll in react node.js and html css js', 'rejected', '2026-04-05 00:08:04');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('3', '7', '6', 'seo good knowledge and experience over 5 years.', 'rejected', '2026-04-05 00:54:09');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('4', '8', '6', 'Experience of over 3 years abd worked with good brands.', 'rejected', '2026-04-05 01:10:18');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('5', '9', '6', 'scsaxashortlist buttonm still not working and add feature of how many notification unread in the notifcation icon if 2 unread 2 should dispalshortlist buttonm still not working and add feature of how many notification unread in the notifcation icon if 2 unread 2 should dispalshortlist buttonm still not working and add feature of how many notification unread in the notifcation icon if 2 unread 2 should dispalshortlist buttonm still not working and add feature of how many notification unread in the notifcation icon if 2 unread 2 should dispalshortlist buttonm still not working and add feature of how many notification unread in the notifcation icon if 2 unread 2 should dispal', 'accepted', '2026-04-05 01:15:50');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('6', '10', '6', 'hello . I  have worked as wordpress developer for 2 years', 'accepted', '2026-04-05 01:32:52');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('7', '11', '6', 'dvcbdzjcvbdjkbvsjkdvbkdsjv', 'pending', '2026-04-05 08:56:57');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('8', '14', '9', 'experience and enthusisatic to learn', 'accepted', '2026-04-12 01:08:44');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('9', '10', '11', 'mn m,', 'pending', '2026-04-12 02:28:04');
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `resume_note`, `status`, `applied_at`) VALUES ('10', '13', '9', 'experienced', 'rejected', '2026-04-12 02:56:13');

CREATE TABLE `employer_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `about` text,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `employer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6  ;

INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES ('1', '2', 'Cedar abc', 'hghjghjg', 'ghjghjghjg.hghjg.com', 'hghj', NULL);
INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES ('2', '4', 'Yatrik', 'IT', 'yatrik.com', '', 'img_69e397aff1e2d.png');
INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES ('3', '7', 'Cedar Gate Tech', 'IT', 'cedar.com', 'Established in 2016', 'uploads/logos/logo_7_1775333929.jpg');
INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES ('4', '10', 'Cedar Gate Technologies', 'IT', 'cedargate.com', 'IT company since 2015AD.', 'img_69daa1395fd10.png');
INSERT INTO `employer_profiles` (`id`, `user_id`, `company`, `industry`, `website`, `about`, `logo`) VALUES ('5', '12', NULL, NULL, NULL, NULL, NULL);

CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `company` varchar(150) NOT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Full Time',
  `experience_level` varchar(50) DEFAULT 'entry',
  `workplace` varchar(50) DEFAULT 'On-site',
  `industry` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `description` text,
  `deadline` date DEFAULT NULL,
  `employer_id` int NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18  ;

INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('1', 'hjghjg', 'hjghjgjhg', '56565', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'hjghjghjvhvhjfyfyf', 'hgfghf', NULL, '2', 'active', '2026-03-28 19:02:10');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('4', 'backend senior level', 'yatrik', '1200', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'remote', '6 years experience', NULL, '4', 'active', '2026-03-28 19:38:33');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('5', 'frontend dev', 'cedargate', '1200', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'sanepa', 'experienced', NULL, '4', 'active', '2026-03-29 08:30:36');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('6', 'designer', 'cedar', '800', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'sanepa', '2 year experience', NULL, '4', 'active', '2026-04-04 22:06:10');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('7', 'SEO Specialist', 'Wevolve', '400', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Naxal', 'Experience:2 years', NULL, '4', 'active', '2026-04-05 00:44:20');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('8', 'Graphics Designer', 'Yatrik', '200', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Putalisadak', 'Experience:Mid-Level', NULL, '4', 'active', '2026-04-05 01:09:16');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('9', 'Junior Designer', 'Cedar', '100', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Sanepa', 'junior designer experience:1 year', NULL, '4', 'active', '2026-04-05 01:15:27');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('10', 'Wordpress Developer', 'Cedar Gate Tech', '1000', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Banepa', 'Experience:2 years\nSkills: Wordpress Tools', NULL, '7', 'active', '2026-04-05 01:32:01');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('11', 'Senior Designer', 'Cedar', '800', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Hadigaun', 'Experience:5 years', NULL, '7', 'active', '2026-04-05 08:56:40');
INSERT INTO `jobs` (`id`, `title`, `company`, `salary`, `category`, `type`, `experience_level`, `workplace`, `industry`, `location`, `description`, `deadline`, `employer_id`, `status`, `created_at`) VALUES ('13', 'UI/ux designer', 'Cedar gate', '$800', 'IT', 'Full Time', 'entry', 'On-site', NULL, 'Naxal', 'Exp:1 year', NULL, '10', 'active', '2026-04-11 23:55:57');

CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_user` int NOT NULL,
  `to_user` int NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_user` (`from_user`),
  KEY `to_user` (`to_user`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  ;

CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27  ;

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('1', '4', 'New applicant for \'designer\' from Digital Marketing', '1', '2026-04-04 22:55:48');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('2', '6', 'Update: Your application for \'designer\' has been shortlisted/accepted.', '1', '2026-04-04 22:56:35');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('3', '4', 'New applicant for \'frontend dev\' from Digital Marketing', '0', '2026-04-05 00:08:04');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('4', '6', 'Update: Your application for \'frontend dev\' has been rejected.', '1', '2026-04-05 00:16:04');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('5', '4', 'New applicant for \'SEO Specialist\' from Digital Marketing', '0', '2026-04-05 00:54:09');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('6', '6', 'Your application for \'SEO Specialist\' has been submitted successfully.', '1', '2026-04-05 00:54:09');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('7', '6', 'Update: Your application for \'SEO Specialist\' has been rejected.', '1', '2026-04-05 01:07:26');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('8', '4', 'New applicant for \'Graphics Designer\' from fbfbcf', '0', '2026-04-05 01:10:18');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('9', '6', 'Your application for \'Graphics Designer\' has been submitted successfully.', '1', '2026-04-05 01:10:18');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES ('10', '6', 'Update: Your application for \'Graphics Designer\' has been rejected.', '1', '2026-04-05 01:13:44');

CREATE TABLE `otp_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3  ;

CREATE TABLE `resumes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `resumes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7  ;

INSERT INTO `resumes` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`) VALUES ('1', '1', '5CS048 Week 3 - S3 Static Website.docx.pdf', 'uploads/resumes/res_1_1774703483.pdf', '2026-03-28 18:56:23');
INSERT INTO `resumes` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`) VALUES ('3', '3', 'myths_facts_abridge_part1.pdf', 'uploads/resumes/res_3_1775012568.pdf', '2026-04-01 08:47:48');
INSERT INTO `resumes` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`) VALUES ('4', '6', 'Smart Job Portal System.docx', 'uploads/resumes/65fdde040a9c4644.docx', '2026-04-05 00:51:31');
INSERT INTO `resumes` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`) VALUES ('5', '6', 'Smart Job Portal System.docx', 'uploads/resumes/7a23552fe836edce.docx', '2026-04-05 00:51:54');
INSERT INTO `resumes` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`) VALUES ('6', '9', 'mom1 (6).pdf', 'uploads/resumes/res_9_1775934230.pdf', '2026-04-12 00:48:50');

CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company` varchar(150) NOT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=4  ;

INSERT INTO `reviews` (`id`, `user_id`, `company`, `rating`, `review`, `created_at`) VALUES ('1', '6', 'Cedar Gate', '5', 'good', '2026-04-05 00:10:06');
INSERT INTO `reviews` (`id`, `user_id`, `company`, `rating`, `review`, `created_at`) VALUES ('2', '6', 'Yatrik Production', '4', 'Very good', '2026-04-05 07:22:41');
INSERT INTO `reviews` (`id`, `user_id`, `company`, `rating`, `review`, `created_at`) VALUES ('3', '10', 'Codavatar', '3', 'cscsc', '2026-04-12 07:49:41');

CREATE TABLE `saved_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL,
  `saved_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_save` (`user_id`,`job_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15  ;

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('1', '6', '7', '2026-04-05 00:47:03');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('2', '6', '8', '2026-04-05 01:10:31');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('3', '6', '10', '2026-04-05 01:32:30');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('4', '11', '14', '2026-04-12 02:10:10');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('5', '9', '15', '2026-04-12 02:32:52');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('6', '11', '15', '2026-04-12 02:34:36');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('7', '11', '4', '2026-04-12 02:36:29');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('8', '9', '14', '2026-04-12 02:43:31');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('9', '9', '7', '2026-04-12 02:53:19');
INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES ('10', '9', '10', '2026-04-12 02:53:21');

CREATE TABLE `seeker_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `skills` text,
  `experience` varchar(100) DEFAULT NULL,
  `bio` text,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `seeker_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9  ;

INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('1', '1', '9800111000', 'php, sql', '1', 'Random info', NULL);
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('2', '3', '9767990227', 'php', '1', 'alinais', 'img_69dabc0e626df.jpg');
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('3', '5', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('4', '6', '2424242424', 'php', '5', 'ddbfbfbfbvbv', 'uploads/photos/photo_6_1775329676.png');
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('5', '8', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('6', '9', '9767990227', 'Php', '2', 'Skilled', 'img_69daa9e9b121d.png');
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('7', '11', '', '', '', '', 'img_69dab02a147ed.png');
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `skills`, `experience`, `bio`, `photo`) VALUES ('8', '13', NULL, NULL, NULL, NULL, NULL);

CREATE TABLE `training_enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_id` int NOT NULL,
  `user_id` int NOT NULL,
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`training_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `training_enrollments_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5  ;

INSERT INTO `training_enrollments` (`id`, `training_id`, `user_id`, `registered_at`, `status`) VALUES ('1', '5', '10', '2026-04-12 07:54:23', 'pending');
INSERT INTO `training_enrollments` (`id`, `training_id`, `user_id`, `registered_at`, `status`) VALUES ('3', '1', '10', '2026-04-12 07:54:38', 'pending');

CREATE TABLE `trainings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employer_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `price` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  CONSTRAINT `trainings_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7  ;

INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('1', '2', 'Full-stack Web Development', 'Master React, Node.js, and SQL to build modern applications.', 'Rs. 25,000', '3 Months', 'active', '2026-04-12 03:43:37');
INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('2', '2', 'UI/UX Design Essentials', 'Learn Figma and user-centric design principles for mobile and web.', 'Rs. 15,000', '6 Weeks', 'active', '2026-04-12 03:43:37');
INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('3', '2', 'Digital Marketing Growth', 'Master SEO, Ads, and Content Strategy to scale businesses.', 'Rs. 12,000', '1 Month', 'active', '2026-04-12 03:43:37');
INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('4', '10', 'Advance Graphics Seminae', 'Advance tools in Graphics like Figma, Photoshop,Indesign and Illustrator', '5000rs', '2 days', 'active', '2026-04-12 03:45:04');
INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('5', '10', 'Basic Code Camp', 'The students will learn about the basic coding languages fundamental including html, css , js and php with some knowledge of frameworks like react,node.js .', '20000', '8 weeks', 'active', '2026-04-12 07:16:59');
INSERT INTO `trainings` (`id`, `employer_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`) VALUES ('6', '4', 'Mental health Workshop', 'You will learn techniques to keep your brain healthy and stay happy.', '5000rs', '2 days', 'active', '2026-04-18 20:21:49');

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employer','seeker') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14  ;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('1', 'hjghghjghj', 'sth@gmail.com', '$2y$10$KMvwwjA/.EzqTC9dwQaZvOPUrm6nWvFSq0iMh9j0KZvO7eRsMidb.', 'seeker', '2026-03-28 18:51:35');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('2', 'Cedsr', 'cedar@gmail.com', '$2y$10$xWGTF1saD4X4zLmXRyK0QO.zofxwmStmfjWEGCtHGitRfnqGG6W6O', 'employer', '2026-03-28 18:59:32');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('3', 'Alinaaaa', 'alina@gmail.com', '$2y$10$8O95i7pVXPzR5OYPfogfROMHbuHs6ieaNf79Q3VkTVhx/sRlUTUcG', 'seeker', '2026-03-28 19:18:04');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('4', 'yatrik', 'yatrik@gmail.com', '$2y$10$dRnDn/ercg3.4mbgXooL6OWNHSDEn4kTbG62C40nr8vyDTJV/shiy', 'employer', '2026-03-28 19:22:42');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('5', 'Roshan', 'roshan@13gmai.com', '$2y$10$f286ft5dRbuAx6Hs61T35ewByYpGp.PdAUfgswHtuURxtNANyzOAS', 'seeker', '2026-04-01 08:45:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('6', 'Alina', 'digitalmarketing.ab7@gmail.com', '$2y$10$2s49JwGxAfArPbMK7npc4.s5o6hay77/3AHq.tUHXubr7DnbXQTBG', 'seeker', '2026-04-04 22:47:18');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('7', 'Cedar Gate Tech', 'cedargate@gmail.com', '$2y$10$BnRemZAY.fMh97JvsHPA3.xW./.a4fecsGKKbJxR2UBXGQazkyF9O', 'employer', '2026-04-05 01:30:35');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('8', 'ALINA', 'alinamanandhar2019@gmail.com', '$2y$10$UN210UoLU51HwDZ1LIG4DOSUcRur/16CKiD2f94Lqoib7pe8UfsDW', 'seeker', '2026-04-07 23:47:51');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('9', 'alina', 'alisha@gmail.com', '$2y$10$cJs.kIvsT5YrgjTbj4iDq.CLc0/5iurLXtERBmzhHtMLcKZqn/c2K', 'seeker', '2026-04-11 21:05:36');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES ('10', 'alish', 'alisha1@gmail.com', '$2y$10$3PxMdp4G8.h5WVJcI4OEz.AWsFhUBxrfzY8FW3Y9bsDoyROMtApV.', 'employer', '2026-04-11 21:06:41');

SET FOREIGN_KEY_CHECKS = 1;
