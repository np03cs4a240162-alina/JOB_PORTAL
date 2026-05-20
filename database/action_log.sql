-- action_log table to track CRUD actions
CREATE TABLE IF NOT EXISTS `action_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `entity` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT NOT NULL,
    `action` ENUM('create','read','update','delete') NOT NULL,
    `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `details` JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
