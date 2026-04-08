<?php
ob_start();

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'jstack_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders() {
        $origin  = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allowed = ['http://localhost', 'http://127.0.0.1'];
        if (in_array($origin, $allowed)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header('Access-Control-Allow-Origin: http://localhost');
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        if (ob_get_level()) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('jsonError')) {
    function jsonError($error, $code = 400) {
        jsonResponse(['success' => false, 'error' => $error], $code);
    }
}

if (!function_exists('jsonSuccess')) {
    function jsonSuccess($data = [], $message = '') {
        $res = ['success' => true];
        if ($message)      $res['message'] = $message;
        if (!empty($data)) $res = array_merge($res, (array)$data);
        jsonResponse($res, 200);
    }
}

if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('JSTACK DB Error: ' . $e->getMessage());
                if (ob_get_level()) ob_end_clean();
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Database connection failed. Is XAMPP running?']);
                exit;
            }
        }
        return $pdo;
    }
}

if (!function_exists('getBody')) {
    function getBody() {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        if ($data === null)  return '';
        if (is_array($data)) return array_map('sanitize', $data);
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}      

