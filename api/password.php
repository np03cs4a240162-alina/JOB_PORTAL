<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST only.'], 405);
$data   = getBody();
$action = $_GET['action'] ?? '';
$db     = getDB();

if ($action === 'change') {
    $user    = requireLogin();
    $current = $data['current_password'] ?? '';
    $new     = $data['new_password']     ?? '';
    $confirm = $data['confirm_password'] ?? '';
    if (!$current || !$new || !$confirm) jsonResponse(['error' => 'All fields required.'], 400);
    if ($new !== $confirm) jsonResponse(['error' => 'Passwords do not match.'], 400);
    if (strlen($new) < 6) jsonResponse(['error' => 'Min 6 characters.'], 400);
    $stmt = $db->prepare('SELECT password FROM users WHERE id=?'); $stmt->execute([$user['id']]);
    $row  = $stmt->fetch();
    if (!password_verify($current, $row['password'])) jsonResponse(['error' => 'Current password incorrect.'], 401);
    $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
    jsonResponse(['success' => true, 'message' => 'Password changed.']);
}

if ($action === 'reset') {
    $email = sanitize($data['email']        ?? '');
    $new   = $data['new_password']          ?? '';
    if (!$email || !$new) jsonResponse(['error' => 'Email and new password required.'], 400);
    $stmt = $db->prepare('SELECT id FROM users WHERE email=?'); $stmt->execute([$email]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Email not found.'], 404);
    $db->prepare('UPDATE users SET password=? WHERE email=?')->execute([password_hash($new, PASSWORD_DEFAULT), $email]);
    jsonResponse(['success' => true, 'message' => 'Password reset.']);
}
jsonResponse(['error' => 'Invalid action.'], 400);