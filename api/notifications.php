<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    jsonResponse($stmt->fetchAll());
}
if ($method === 'PUT') {
    $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
    jsonResponse(['success' => true]);
}
if ($method === 'DELETE') {
    $db->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$user['id']]);
    jsonResponse(['success' => true]);
}
jsonResponse(['error' => 'Invalid request.'], 400);

