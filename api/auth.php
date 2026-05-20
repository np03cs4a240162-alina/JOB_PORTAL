<?php
/**
 * Prevent PHP warnings from breaking JSON output.
 * If mail() fails, it won't crash the frontend.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/otp.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── SIGNUP STEP 1: Send OTP ───────────────────────────────────────────────────
if ($method === 'POST' && $action === 'send-otp') {
    $data  = getBody();
    $name  = sanitize($data['name']  ?? '');
    $email = sanitize($data['email'] ?? '');
    $pass  = $data['password']       ?? '';
    $role  = $data['role']           ?? 'seeker';

    if (!$name || !$email || !$pass) jsonResponse(['error' => 'All fields required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email format.'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
    
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) jsonResponse(['error' => 'This email is already registered.'], 409);

    // Save registration details in session while waiting for OTP
    $_SESSION['pending_reg'] = [
        'name'     => $name,
        'email'    => $email,
        'password' => password_hash($pass, PASSWORD_DEFAULT),
        'role'     => $role,
    ];

    // Check Rate Limit Cooldown (30 seconds)
    $wait = checkOtpRateLimit($email, 'verification');
    if ($wait !== null) {
        jsonResponse(['error' => "Please wait {$wait} second(s) before requesting another code."], 429);
    }

    $otp  = generateOtp($email, 'verification');
    $dispatch = sendOtpEmail($email, $otp, 'verification');

    if (!$dispatch['success']) {
        jsonResponse(['error' => $dispatch['error']], 400);
    }

    $response = [
        'success' => true,
        'message' => 'Verification code sent to ' . $email
    ];
    if (isset($dispatch['dev']) && $dispatch['dev']) {
        $response['dev_otp'] = $otp; // Auto-fill support for developer review flow
    }
    jsonResponse($response);
}

// ── SIGNUP STEP 2: Verify OTP & Create Account ──────────────────────────────
if ($method === 'POST' && $action === 'verify-otp') {
    $data  = getBody();
    $email = sanitize($data['email'] ?? '');
    $otp   = trim($data['otp']       ?? '');

    if (!$email || !$otp) jsonResponse(['error' => 'Email and OTP are required.'], 400);
    
    $res = verifyOtp($email, $otp, 'verification');
    if (!$res['success']) {
        jsonResponse(['error' => $res['error']], 400);
    }

    $pending = $_SESSION['pending_reg'] ?? null;
    if (!$pending || $pending['email'] !== $email) {
        jsonResponse(['error' => 'Session expired. Please start registration again.'], 400);
    }

    $db = getDB();
    try {
        $db->beginTransaction();
        
        // 1. Insert User
        $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)');
        $stmt->execute([$pending['name'], $pending['email'], $pending['password'], $pending['role']]);
        $newId = (int)$db->lastInsertId();

        // 2. Create matching Profile based on role
        if ($pending['role'] === 'seeker') {
            $db->prepare('INSERT INTO seeker_profiles (user_id) VALUES (?)')->execute([$newId]);
        } else if ($pending['role'] === 'employer') {
            $db->prepare('INSERT INTO employer_profiles (user_id) VALUES (?)')->execute([$newId]);
        }

        $db->commit();
        
        // Cleanup
        unset($_SESSION['pending_reg']);
        
        jsonResponse(['success' => true, 'message' => 'Account created! You can now login.']);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ── LOGIN STEP 1: Send OTP for Passwordless Login ──────────────────────────
if ($method === 'POST' && $action === 'login-otp-send') {
    $data  = getBody();
    $email = sanitize($data['email'] ?? '');

    if (!$email) jsonResponse(['error' => 'Email is required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email format.'], 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'No account is registered under this email.'], 404);
    }

    // Check Rate Limit Cooldown (30 seconds)
    $wait = checkOtpRateLimit($email, 'login');
    if ($wait !== null) {
        jsonResponse(['error' => "Please wait {$wait} second(s) before requesting another code."], 429);
    }

    $otp  = generateOtp($email, 'login');
    $dispatch = sendOtpEmail($email, $otp, 'login');

    if (!$dispatch['success']) {
        jsonResponse(['error' => $dispatch['error']], 400);
    }

    $response = [
        'success' => true,
        'message' => 'Secure verification code sent to ' . $email
    ];
    if (isset($dispatch['dev']) && $dispatch['dev']) {
        $response['dev_otp'] = $otp; // Auto-fill support for developer review flow
    }
    jsonResponse($response);
}

// ── FORGOT PASSWORD STEP 1: Send OTP for Reset ────────────────────────────────
if ($method === 'POST' && $action === 'forgot-otp-send') {
    $data  = getBody();
    $email = sanitize($data['email'] ?? '');

    if (!$email) jsonResponse(['error' => 'Email is required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email format.'], 400);

    // Verify account exists
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['error' => 'No account found for this email.'], 404);

    // Rate limit check
    $wait = checkOtpRateLimit($email, 'reset');
    if ($wait !== null) jsonResponse(['error' => "Please wait {$wait} second(s) before requesting another code."], 429);

    $otp = generateOtp($email, 'reset');
    $dispatch = sendOtpEmail($email, $otp, 'reset');
    if (!$dispatch['success']) jsonResponse(['error' => $dispatch['error']], 400);

    $response = ['success' => true, 'message' => 'Password reset code sent to ' . $email];
    if (isset($dispatch['dev']) && $dispatch['dev']) $response['dev_otp'] = $otp;
    jsonResponse($response);
}

// ── FORGOT PASSWORD STEP 2: Verify OTP & Set New Password ───────────────────────
if ($method === 'POST' && $action === 'forgot-otp-verify') {
    $data  = getBody();
    $email = sanitize($data['email'] ?? '');
    $otp   = trim($data['otp'] ?? '');
    $newpass = $data['newpass'] ?? '';
    $confirm = $data['confirm'] ?? '';

    if (!$email || !$otp || !$newpass || !$confirm) jsonResponse(['error' => 'All fields are required.'], 400);
    if ($newpass !== $confirm) jsonResponse(['error' => 'Passwords do not match.'], 400);
    if (strlen($newpass) < 6) jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);

    $res = verifyOtp($email, $otp, 'reset');
    if (!$res['success']) jsonResponse(['error' => $res['error']], 400);

    // Update password
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([password_hash($newpass, PASSWORD_DEFAULT), $email]);

    jsonResponse(['success' => true, 'message' => 'Password updated. You can now log in.']);
}
if ($method === 'POST' && $action === 'login-otp-verify') {
    $data  = getBody();
    $email = sanitize($data['email'] ?? '');
    $otp   = trim($data['otp']       ?? '');

    if (!$email || !$otp) jsonResponse(['error' => 'Email and OTP code are required.'], 400);

    $res = verifyOtp($email, $otp, 'login');
    if (!$res['success']) {
        jsonResponse(['error' => $res['error']], 400);
    }

    // Load active user row
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Failed to retrieve authenticated user.'], 500);
    }

    unset($user['password']);
    $_SESSION['user'] = $user;
    // Provide CSRF token for the new session
    require_once '../config/session.php';
    $token = generateCsrfToken();
    session_write_close();
    jsonResponse(['success' => true, 'user' => $user, 'csrf_token' => $token]);
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $data  = getBody();
    $email = sanitize($data['email']    ?? '');
    $pass  = $data['password']          ?? '';

    if (!$email || !$pass) jsonResponse(['error' => 'Email and password required.'], 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonResponse(['error' => 'Invalid email or password.'], 401);
    }

    // Never send the password hash back to the frontend
    unset($user['password']);
    
    $_SESSION['user'] = $user;
    // Ensure CSRF token is generated for the session and returned to frontend
    require_once '../config/session.php';
    $token = generateCsrfToken();
    session_write_close();
    jsonResponse(['success' => true, 'user' => $user, 'csrf_token' => $token]);
}

// ── LOGOUT ────────────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

// ── SESSION CHECK ─────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    $user = $_SESSION['user'] ?? null;
    if ($user) {
        // Ensure a CSRF token is available for the frontend
        require_once '../config/session.php';
        $token = generateCsrfToken();
        jsonResponse(['success' => true, 'user' => $user, 'csrf_token' => $token]);
    } else {
        jsonResponse(['success' => false, 'user' => null], 401);
    }
}

// Fallback for unknown actions
jsonResponse(['error' => 'Requested action not found.'], 404);