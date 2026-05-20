<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jstack_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get Database Connection (Singleton Pattern)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            
            // Automatic migration: Create activity_logs table if missing
            $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `user_name` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) NOT NULL,
                `action` VARCHAR(255) NOT NULL,
                `details` TEXT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // ── Auto-Migrations for RBAC & Smart Features ──
            // Add account_status to users (for ban/unban)
            try {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `account_status` ENUM('active','banned') NOT NULL DEFAULT 'active'");
            } catch (\PDOException $e) { /* Column already exists */ }

            // Add shortlisted flag to applications (for employer shortlisting)
            try {
                $pdo->exec("ALTER TABLE `applications` ADD COLUMN `shortlisted` TINYINT(1) NOT NULL DEFAULT 0");
            } catch (\PDOException $e) { /* Column already exists */ }

            // Add interview_date to applications (for interview scheduling)
            try {
                $pdo->exec("ALTER TABLE `applications` ADD COLUMN `interview_date` DATETIME DEFAULT NULL");
            } catch (\PDOException $e) { /* Column already exists */ }

            // Add interview_notes to applications
            try {
                $pdo->exec("ALTER TABLE `applications` ADD COLUMN `interview_notes` TEXT DEFAULT NULL");
            } catch (\PDOException $e) { /* Column already exists */ }

            // ── Auto-Migrations for Notifications & Messages Extensions ──
            try { $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `type` VARCHAR(50) DEFAULT 'general'"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `priority` ENUM('low', 'normal', 'high') DEFAULT 'normal'"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `related_id` INT DEFAULT NULL"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `related_type` VARCHAR(50) DEFAULT NULL"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `read_at` DATETIME DEFAULT NULL"); } catch (\PDOException $e) {}

            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `is_read` TINYINT(1) DEFAULT 0"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `is_archived` TINYINT(1) DEFAULT 0"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `is_spam` TINYINT(1) DEFAULT 0"); } catch (\PDOException $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `file_path` VARCHAR(255) DEFAULT NULL"); } catch (\PDOException $e) {}

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection failed. Check XAMPP MySQL. ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Log System Activity / CRUD Audit Trail
 */
if (!function_exists('logActivity')) {
    function logActivity($userId, $userName, $role, $action, $details = '') {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_name, role, action, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $userName, $role, $action, $details]);
        } catch (Exception $e) {
            // Silently capture errors
            @file_put_contents(__DIR__ . '/../activity_logs_error.txt', "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

/**
 * Global helper to send JSON responses easily
 * Wrapped to prevent redeclaration errors with session.php
 */
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/**
 * Sanitize user input to prevent XSS (Cross-Site Scripting)
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if ($data === null) return '';
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Get the JSON body sent from JavaScript's apiPost/apiPut
 */
if (!function_exists('getBody')) {
    function getBody() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}