<?php

require_once __DIR__ . '/../config/db.php';      // getDB(), getBody(), jsonResponse()
require_once __DIR__ . '/../config/session.php'; // requireLogin(), requireRole()
require_once __DIR__ . '/../config/otp.php';     // For future email verification if needed

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$data   = getBody(); // Changed from getRequestData() to match your config
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $current = requireLogin();

    if ($id) {

        if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== $id) {
            jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        jsonResponse(['success' => true, 'data' => $user]);
    }

    requireRole('admin');
    $users = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    jsonResponse(['success' => true, 'data' => $users]);
}

if ($method === 'POST') {
    requireRole('admin'); // Only admins can manually create users via this controller

    $name  = sanitize($data['name'] ?? '');
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass  = $data['password'] ?? '';
    $role  = sanitize($data['role'] ?? 'seeker');

    if (!$name || !$email || !$pass) jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    if (strlen($pass) < 6) jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);

    if (!in_array($role, ['admin', 'employer', 'seeker'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid role selected.'], 400);
    }

    $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
    $chk->execute([$email]);
    if ($chk->fetch()) jsonResponse(['success' => false, 'message' => 'Email already registered.'], 409);

    $sql = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
    $db->prepare($sql)->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
    
    jsonResponse(['success' => true, 'message' => 'User created successfully.', 'id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'PUT' && $id) {
    $current = requireLogin();

    if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== $id) {
        jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
    }

    $name = sanitize($data['name'] ?? '');
    $role = sanitize($data['role'] ?? '');

    if (!$name) jsonResponse(['success' => false, 'message' => 'Name is required.'], 400);

    if ($_SESSION['role'] !== 'admin' && !empty($role) && $role !== $_SESSION['role']) {
        jsonResponse(['success' => false, 'message' => 'You cannot change your own role.'], 403);
    }

    $db->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $id]);
    jsonResponse(['success' => true, 'message' => 'Profile updated.']);
}

if ($method === 'DELETE' && $id) {
    $current = requireLogin();

    if ($_SESSION['role'] === 'admin' && $id === (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot delete your own admin account.'], 403);
    }

    if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== $id) {
        jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ($targetUser['role'] === 'seeker') {
            $db->prepare('DELETE FROM applications WHERE seeker_id = ?')->execute([$id]);

        } elseif ($targetUser['role'] === 'employer') {

            $db->prepare("UPDATE jobs SET status = 'closed' WHERE employer_id = ?")->execute([$id]);
        }

        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        $db->commit();

        if ($id === (int)$_SESSION['user_id']) {
            session_destroy();
        }

        jsonResponse(['success' => true, 'message' => 'User and related data removed.']);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);

