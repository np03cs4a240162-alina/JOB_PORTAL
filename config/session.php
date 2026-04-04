<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );

        if ($isAjax) {
            ob_end_clean();
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Session expired. Please login again.']);
            exit;
        }

        header('Location: /jobportalsystem/auth/login.html?error=unauthorized');
        exit;
    }

    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function requireRole($requiredRole) {
    requireLogin();

    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($userRole !== $requiredRole) {
        $redirectMap = [
            'admin'    => '/jobportalsystem/admin/dashboard.php',
            'employer' => '/jobportalsystem/employer/dashboard.php',
            'seeker'   => '/jobportalsystem/seeker/dashboard.php',
        ];
        $target = isset($redirectMap[$userRole]) ? $redirectMap[$userRole] : '/jobportalsystem/auth/login.html';
        header("Location: {$target}?error=access_denied");
        exit;
    }

    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: /jobportalsystem/auth/login.html');
    exit;
}