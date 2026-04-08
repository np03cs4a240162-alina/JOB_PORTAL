<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_exception_handler(function(Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine()
    ]);
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => "PHP Error: $errstr",
        'file'    => basename($errfile),
        'line'    => $errline
    ]);
    exit;
});

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/otp.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$data   = getBody();
$action = $_GET['action'] ?? $data['action'] ?? '';

if ($method === 'GET' && $action === 'me') {
    $user = (isset($_SESSION['user_id'])) ? [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ] : null;

    ob_end_clean();
    jsonResponse([
        'success' => $user !== null,
        'user'    => $user
    ]);
}

if ($method === 'POST' && $action === 'login') {
    $email = trim($data['email'] ?? '');
    $pass  = $data['password']   ?? '';

    if (!$email || !$pass) jsonError('Email and password are required.', 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format.', 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password']))
        jsonError('Invalid email or password.', 401);

    unset($user['password']);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
    session_write_close();

    ob_end_clean();
    jsonResponse(['success' => true, 'user' => $user]);
}

if ($method === 'POST' && $action === 'logout') {
    session_unset();
    session_destroy();
    ob_end_clean();
    jsonResponse(['success' => true]);
}

if ($method === 'POST' && $action === 'send-otp') {
    $email = trim(strtolower($data['email'] ?? ''));
    $pass  = $data['password'] ?? '';
    $name  = trim($data['name'] ?? '');
    $role  = $data['role']     ?? 'seeker';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format.', 400);
    if (!$name)           jsonError('Name is required.', 400);
    if (strlen($pass) < 6) jsonError('Password must be at least 6 characters.', 400);
    if ($role === 'admin') $role = 'seeker';

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) jsonError('Email already registered.', 409);

    $_SESSION['pending_reg'] = [
        'name'     => $name,
        'email'    => $email,
        'password' => password_hash($pass, PASSWORD_DEFAULT),
        'role'     => $role,
    ];

    $otp = generateOtp($email);

    ob_end_clean();
    jsonResponse(['success' => true, 'message' => 'Verification code sent.', 'dev_otp' => $otp]);
}

if ($method === 'POST' && $action === 'verify-otp') {
    $email = trim(strtolower($data['email'] ?? ''));
    $otp   = trim($data['otp'] ?? '');

    if (!$otp || strlen($otp) !== 6) jsonError('Please enter the 6-digit code.', 400);
    if (!verifyOtp($email, $otp))    jsonError('Invalid or expired code.', 400);

    $pending = $_SESSION['pending_reg'] ?? null;
    if (!$pending || strtolower($pending['email']) !== $email)
        jsonError('Registration session expired. Please start again.', 400);

    $db = getDB();
    try {
        $db->beginTransaction();
        $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$pending['name'], $pending['email'], $pending['password'], $pending['role']]);
        $newId = (int)$db->lastInsertId();

        if ($pending['role'] === 'seeker')
            $db->prepare('INSERT INTO seeker_profiles (user_id) VALUES (?)')->execute([$newId]);
        elseif ($pending['role'] === 'employer')
            $db->prepare('INSERT INTO employer_profiles (user_id) VALUES (?)')->execute([$newId]);

        $db->commit();
        clearOtp($email);
        unset($_SESSION['pending_reg']);

        $welcomeMsg = ($pending['role'] === 'seeker') 
            ? "Welcome to JSTACK! Complete your profile to start applying for jobs."
            : "Welcome to JSTACK! Start by posting your first job listing to find talent.";
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$newId, $welcomeMsg]);

        ob_end_clean();
        jsonResponse(['success' => true, 'message' => 'Account created! Please login.']);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonError('Registration failed: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST' && $action === 'forgot-password') {
    $email = trim(strtolower($data['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format.', 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if (!$stmt->fetch()) jsonError('No account found with this email.', 404);

    $_SESSION['reset_email'] = $email;
    $otp = generateOtp($email);

    ob_end_clean();
    jsonResponse(['success' => true, 'message' => 'Reset code sent.', 'dev_otp' => $otp]);
}

if ($method === 'POST' && $action === 'reset-password') {
    $email   = trim(strtolower($data['email'] ?? ''));
    $otp     = trim($data['otp'] ?? '');
    $newPass = $data['new_password'] ?? '';

    if (!$otp || strlen($otp) !== 6) jsonError('Please enter the 6-digit reset code.', 400);
    if (strlen($newPass) < 6)        jsonError('Password must be at least 6 characters.', 400);
    if (!verifyOtp($email, $otp))    jsonError('Invalid or expired reset code.', 400);

    $sessionEmail = strtolower($_SESSION['reset_email'] ?? '');
    if ($sessionEmail !== $email) jsonError('Reset session expired. Please start again.', 400);

    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $email]);
    if ($stmt->rowCount() === 0) jsonError('Password update failed.', 500);

    $uStmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $uStmt->execute([$email]);
    $userId = $uStmt->fetchColumn();

    if ($userId) {
        $msg = "Security Alert: Your password was successfully reset.";
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$userId, $msg]);
    }

    clearOtp($email);
    unset($_SESSION['reset_email']);

    ob_end_clean();
    jsonResponse(['success' => true, 'message' => 'Password reset successfully.']);
}

ob_end_clean();
jsonError("Action '$action' not found.", 404);

