<?php
/**
 * JSTACK USER MANAGEMENT CONTROLLER
 * Handles CRUD for Users with role-based access control.
 */

// 1. Fixed Pathing & Includes
require_once __DIR__ . '/../config/db.php';      // getDB(), getBody(), jsonResponse()
require_once __DIR__ . '/../config/session.php'; // requireLogin(), requireRole()
require_once __DIR__ . '/../config/otp.php';     // For future email verification if needed

// 2. Setup environment
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$data   = getBody(); // Changed from getRequestData() to match your config
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// --- 1. READ (GET) ---
if ($method === 'GET') {
    $current = requireLogin();

    // Specific User Details
    if ($id) {
        // Authorization: Only Admins or the User themselves
        if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== $id) {
            jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        jsonResponse(['success' => true, 'data' => $user]);
    }

    // List All Users (Admin Only)
    requireRole('admin');
    $users = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    jsonResponse(['success' => true, 'data' => $users]);
}

// --- 2. CREATE (POST) ---
if ($method === 'POST') {
    requireRole('admin'); // Only admins can manually create users via this controller

    $name  = sanitize($data['name'] ?? '');
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass  = $data['password'] ?? '';
    $role  = sanitize($data['role'] ?? 'seeker');

    // Validation
    if (!$name || !$email || !$pass) jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    if (strlen($pass) < 6) jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
    
    // Strict Role Check (Matches your DB ENUM)
    if (!in_array($role, ['admin', 'employer', 'seeker'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid role selected.'], 400);
    }

    // Check Duplicate Email
    $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
    $chk->execute([$email]);
    if ($chk->fetch()) jsonResponse(['success' => false, 'message' => 'Email already registered.'], 409);

    $sql = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
    $db->prepare($sql)->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
    
    jsonResponse(['success' => true, 'message' => 'User created successfully.', 'id' => (int)$db->lastInsertId()], 201);
}

// --- 3. UPDATE (PUT) ---
if ($method === 'PUT' && $id) {
    $current = requireLogin();
    
    // Only Admin or Self
    if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== $id) {
        jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
    }

    $name = sanitize($data['name'] ?? '');
    $role = sanitize($data['role'] ?? '');

    if (!$name) jsonResponse(['success' => false, 'message' => 'Name is required.'], 400);

    // If non-admin tries to change their role, block it
    if ($_SESSION['role'] !== 'admin' && !empty($role) && $role !== $_SESSION['role']) {
        jsonResponse(['success' => false, 'message' => 'You cannot change your own role.'], 403);
    }

    $db->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $id]);
    jsonResponse(['success' => true, 'message' => 'Profile updated.']);
}

// --- 4. DELETE (DELETE) ---
if ($method === 'DELETE' && $id) {
    $current = requireLogin();

    // Prevent Self-Deletion for Admins
    if ($_SESSION['role'] === 'admin' && $id === (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot delete your own admin account.'], 403);
    }

    // Authorization: Only Admin or Self
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

        // Clean up based on JSTACK Database relationships
        if ($targetUser['role'] === 'seeker') {
            $db->prepare('DELETE FROM applications WHERE seeker_id = ?')->execute([$id]);
            // Note: If you have a 'resumes' table, delete those files physically here too
        } elseif ($targetUser['role'] === 'employer') {
            // Close jobs instead of deleting to maintain application history
            $db->prepare("UPDATE jobs SET status = 'closed' WHERE employer_id = ?")->execute([$id]);
        }

        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        $db->commit();

        // Self-deletion cleanup
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