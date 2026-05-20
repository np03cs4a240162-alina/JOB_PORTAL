-- ============================================
-- MESSAGING SYSTEM MIGRATION SCHEMA
-- Smart Job Portal - Notification, Chat & Messaging
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. ENHANCED NOTIFICATIONS TABLE
-- ============================================
ALTER TABLE `notifications` ADD COLUMN `type` VARCHAR(50) DEFAULT 'general' COMMENT 'general, application, message, chat, system';
ALTER TABLE `notifications` ADD COLUMN `priority` ENUM('low','normal','high') DEFAULT 'normal';
ALTER TABLE `notifications` ADD COLUMN `read_at` DATETIME DEFAULT NULL;
ALTER TABLE `notifications` ADD COLUMN `related_id` INT DEFAULT NULL COMMENT 'ID of related entity (job_id, message_id, etc)';
ALTER TABLE `notifications` ADD COLUMN `related_type` VARCHAR(50) DEFAULT NULL COMMENT 'Type of related entity (job, message, chat, etc)';
ALTER TABLE `notifications` ADD INDEX `idx_user_read` (`user_id`, `is_read`);
ALTER TABLE `notifications` ADD INDEX `idx_type` (`type`);
ALTER TABLE `notifications` ADD INDEX `idx_priority` (`priority`);

-- ============================================
-- 2. CHATS TABLE - Real-time chat sessions
-- ============================================
CREATE TABLE IF NOT EXISTS `chats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user1_id` INT NOT NULL,
  `user2_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `unique_chat` (`user1_id`, `user2_id`),
  FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. CHAT MESSAGES TABLE - Individual messages in chats
-- ============================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chat_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message` LONGTEXT NOT NULL,
  `message_type` ENUM('text','file','image','emoji') DEFAULT 'text',
  `file_url` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_chat_created` (`chat_id`, `created_at`),
  INDEX `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. USER ONLINE STATUS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `user_online_status` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `is_online` TINYINT(1) DEFAULT 0,
  `last_seen` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `typing_to_user_id` INT DEFAULT NULL COMMENT 'User they are typing to (NULL if not typing)',
  `typing_started_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_online` (`is_online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. MESSAGE THREADS TABLE - Thread-based messaging
-- ============================================
CREATE TABLE IF NOT EXISTS `message_threads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `from_user` INT NOT NULL,
  `to_user` INT NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `last_message` LONGTEXT,
  `last_message_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_archived` TINYINT(1) DEFAULT 0,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_thread` (`from_user`, `to_user`),
  FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_from_user` (`from_user`),
  INDEX `idx_to_user` (`to_user`),
  INDEX `idx_archived` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. THREAD MESSAGES TABLE - Messages in threads
-- ============================================
CREATE TABLE IF NOT EXISTS `thread_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `thread_id` INT NOT NULL,
  `from_user` INT NOT NULL,
  `message` LONGTEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_thread_created` (`thread_id`, `created_at`),
  INDEX `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. AUTO REPLIES TABLE - AI-generated suggestions
-- ============================================
CREATE TABLE IF NOT EXISTS `auto_replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `trigger_type` VARCHAR(50) DEFAULT 'general' COMMENT 'general, greeting, farewell, question, etc',
  `message` TEXT NOT NULL,
  `rating` INT DEFAULT 0 COMMENT 'User satisfaction rating',
  `usage_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_type` (`user_id`, `trigger_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. SPAM DETECTION TABLE - Track spam messages
-- ============================================
CREATE TABLE IF NOT EXISTS `spam_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `reported_user_id` INT NOT NULL,
  `message_id` INT DEFAULT NULL COMMENT 'Reference to message or chat_message',
  `reason` VARCHAR(255) NOT NULL,
  `status` ENUM('pending','confirmed','dismissed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_reported_user` (`reported_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. MESSAGE ATTACHMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `message_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chat_message_id` INT DEFAULT NULL,
  `thread_message_id` INT DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(500) NOT NULL,
  `file_size` INT,
  `file_type` VARCHAR(50),
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`chat_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`thread_message_id`) REFERENCES `thread_messages` (`id`) ON DELETE CASCADE,
  INDEX `idx_chat_msg` (`chat_message_id`),
  INDEX `idx_thread_msg` (`thread_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. MESSAGE SEARCH INDEX TABLE - For faster search
-- ============================================
CREATE TABLE IF NOT EXISTS `message_search_index` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `message_id` INT NOT NULL,
  `message_type` ENUM('chat','thread') DEFAULT 'chat',
  `search_text` LONGTEXT NOT NULL,
  `user_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT KEY `ft_search_text` (`search_text`),
  INDEX `idx_message` (`message_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INITIALIZE USER ONLINE STATUS
-- ============================================
INSERT IGNORE INTO `user_online_status` (`user_id`, `is_online`)
SELECT `id`, 0 FROM `users`;

SET FOREIGN_KEY_CHECKS = 1;
