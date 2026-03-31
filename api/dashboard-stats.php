<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'GET only.'], 405);
$user = requireLogin();
$db   = getDB();

if ($user['role'] === 'admin') {
    // 1. Fetch General System Stats
    $row = $db->query("SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM users WHERE role='employer') AS total_employers,
        (SELECT COUNT(*) FROM users WHERE role='seeker') AS total_seekers,
        (SELECT COUNT(*) FROM jobs) AS total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE status='active') AS active_jobs,
        (SELECT COUNT(*) FROM applications) AS total_applications,
        (SELECT COUNT(*) FROM applications WHERE status='accepted') AS accepted_applications,
        (SELECT COUNT(*) FROM applications WHERE status='pending') AS pending_applications,
        (SELECT COUNT(*) FROM applications WHERE status='rejected') AS rejected_applications,
        (SELECT COUNT(*) FROM reviews) AS total_reviews
    ")->fetch();

    // 2. NEW: Fetch Average Ratings per Company for the Dashboard Chart
    // This groups reviews by company and calculates the mean rating
    $companyStats = $db->query("SELECT 
            company, 
            ROUND(AVG(rating), 1) as avg_rating, 
            COUNT(*) as review_count 
        FROM reviews 
        GROUP BY company 
        ORDER BY avg_rating DESC 
        LIMIT 10"
    )->fetchAll();

    $recentJobs  = $db->query("SELECT j.id,j.title,j.company,j.status,j.created_at,u.name AS employer_name FROM jobs j LEFT JOIN users u ON j.employer_id=u.id ORDER BY j.created_at DESC LIMIT 5")->fetchAll();
    $recentUsers = $db->query("SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
    // Return the new company_stats along with existing data
    jsonResponse([
        'success' => true, 
        'stats' => array_map('intval', $row), 
        'company_stats' => $companyStats, // Data for your Chart.js
        'recent_jobs' => $recentJobs, 
        'recent_users' => $recentUsers
    ]);
}

// Logic for Employer and Seeker roles remains unchanged
if ($user['role'] === 'employer') {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT j.id) AS total_jobs, COUNT(DISTINCT a.id) AS total_applications, SUM(a.status='accepted') AS accepted, SUM(a.status='pending') AS pending, SUM(a.status='rejected') AS rejected FROM jobs j LEFT JOIN applications a ON j.id=a.job_id WHERE j.employer_id=?");
    $stmt->execute([$user['id']]);
    jsonResponse(['success'=>true, 'stats'=>array_map('intval',$stmt->fetch())]);
}

if ($user['role'] === 'seeker') {
    $stmt = $db->prepare("SELECT COUNT(*) AS total_applications, SUM(status='accepted') AS accepted, SUM(status='pending') AS pending, SUM(status='rejected') AS rejected FROM applications WHERE seeker_id=?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    $saved = $db->prepare('SELECT COUNT(*) FROM saved_jobs WHERE user_id=?');
    $saved->execute([$user['id']]);
    $row['saved_jobs'] = (int)$saved->fetchColumn();
    jsonResponse(['success'=>true, 'stats'=>array_map('intval',$row)]);
}

jsonResponse(['error' => 'Role not recognized.'], 403);