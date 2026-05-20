<?php
// RBAC helper for API endpoints
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

function authorizeRole($roles) {
    $user = requireLogin();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'] ?? '', $allowed)) {
        jsonResponse(['success' => false, 'error' => 'Forbidden. Insufficient role.'], 403);
    }
    return $user;
}

function authorizeOwnerOrAdmin($table, $id, $ownerCol = 'user_id') {
    $user = requireLogin();
    if (($user['role'] ?? '') === 'admin') return $user;

    $db = getDB();
    $stmt = $db->prepare("SELECT {$ownerCol} FROM {$table} WHERE id = ?");
    $stmt->execute([(int)$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonResponse(['success' => false, 'error' => 'Resource not found.'], 404);
    if ($row[$ownerCol] != $user['id']) jsonResponse(['success' => false, 'error' => 'Permission denied.'], 403);
    return $user;
}

function authorizeEmployerOrAdminForApplication($applicationId) {
    $user = requireLogin();
    if (($user['role'] ?? '') === 'admin') return $user;

    $db = getDB();
    $stmt = $db->prepare("SELECT j.employer_id FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = ?");
    $stmt->execute([(int)$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonResponse(['success' => false, 'error' => 'Application not found.'], 404);
    if ($row['employer_id'] != $user['id']) jsonResponse(['success' => false, 'error' => 'Permission denied.'], 403);
    return $user;
}

// Convenience wrapper: require login and allow admin or specific role
function authorizeRoleOrAdmin($role) {
    $user = requireLogin();
    if ($user['role'] === 'admin') return $user;
    if ($user['role'] !== $role) jsonResponse(['success' => false, 'error' => 'Forbidden. Insufficient role.'], 403);
    return $user;
}

?>
