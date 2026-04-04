<?php
/**
 * JSTACK Security & CORS Headers
 * Place this at the very top of your API files (signin.php, signup.php, etc.)
 */

// 1. Set the Origin dynamically or allow your specific dev URL
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

// 2. Handle Pre-flight OPTIONS request (Used by browser fetch)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // "No Content" is standard for OPTIONS
    exit;
}

// 3. Include your Database Config (Relative to this file)
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}

// Now you can safely use getBody() and getDB() below...