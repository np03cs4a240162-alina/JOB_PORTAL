<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

if ($method === 'GET') {
    $role = $user['role'];
    $userId = $user['id'];

    if ($role === 'admin') {
        // Admin: View all activity history
        $stmt = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } else {
        // Seeker or Employer: View their own personal activity logs
        $stmt = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }
}

jsonResponse(['success' => false, 'error' => 'Forbidden method.'], 405);
