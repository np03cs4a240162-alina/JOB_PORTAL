<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Basic CSRF protection for non-GET requests (skip for auth endpoints)
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','OPTIONS'])) {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Allow auth flows (OTP/login) which may not have CSRF token yet
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	if (strpos($uri, 'auth.php') !== false) {
		// skip CSRF check for auth.php endpoints
	} else {
		$headers = getallheaders();
		$token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
		if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
			http_response_code(401);
			echo json_encode(['error' => 'Invalid or missing CSRF token.']);
			exit;
		}
	}
}