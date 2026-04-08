<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/session.php';
require_once '../config/db.php';

$user = requireLogin();
$id   = $user['id'];
$role = $user['role'];

try {
    $db = getDB();
    
    $stats = [];
    $recentUsers = null;

    if ($role === 'admin') {
        $stats['total_users']           = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['active_jobs']           = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
        $stats['total_applications']    = (int)$db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $stats['total_employers']       = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
        $stats['total_seekers']         = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'seeker'")->fetchColumn();
        $stats['accepted_applications'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE status = 'accepted'")->fetchColumn();
        $recentUsers = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
    } 
    elseif ($role === 'employer') {
        $stats['total_jobs']           = (int)$db->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?")->execute([$id]) ? $db->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?")->execute([$id]) : 0; 

        $stmtJobs = $db->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
        $stmtJobs->execute([$id]);
        $stats['total_jobs'] = (int)$stmtJobs->fetchColumn();

        $stmtApps = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?");
        $stmtApps->execute([$id]);
        $stats['total_applications'] = (int)$stmtApps->fetchColumn();

        $stmtAcc = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'accepted'");
        $stmtAcc->execute([$id]);
        $stats['accepted'] = (int)$stmtAcc->fetchColumn();

        $stmtPend = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'pending'");
        $stmtPend->execute([$id]);
        $stats['pending'] = (int)$stmtPend->fetchColumn();
    }
    elseif ($role === 'seeker') {
        $stmt1 = $db->prepare("SELECT COUNT(*) FROM applications WHERE seeker_id = ?");
        $stmt1->execute([$id]);
        $stats['applied'] = (int)$stmt1->fetchColumn();

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
        $stmt2->execute([$id]);
        $stats['saved'] = (int)$stmt2->fetchColumn();
    }

    echo json_encode([
        'success'      => true,
        'role'         => $role,
        'stats'        => $stats,
        'recent_users' => $recentUsers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


