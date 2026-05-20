<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../config/cors.php';

$method = $_SERVER['REQUEST_METHOD'];
if($method !== 'GET') jsonResponse(['error' => 'GET only'], 405);

$db = getDB();
$keyword = $_GET['keyword'] ?? '';
$type = $_GET['type'] ?? 'all';

$results = [
    'jobs' => [],
    'companies' => [],
    'applicants' => []
];

if (!$keyword) {
    jsonResponse(['success' => true, 'data' => $results]);
}

$likeKeyword = "%$keyword%";

if ($type === 'all' || $type === 'jobs') {
    $stmt = $db->prepare("SELECT j.id, j.title, j.company, j.location, 'Full Time' as job_type, j.created_at FROM jobs j WHERE (j.title LIKE ? OR j.company LIKE ?) AND j.status='active' LIMIT 20");
    $stmt->execute([$likeKeyword, $likeKeyword]);
    $results['jobs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($type === 'all' || $type === 'companies') {
    $stmt = $db->prepare("SELECT e.user_id, e.company, e.industry, e.logo, u.name FROM employer_profiles e JOIN users u ON e.user_id = u.id WHERE e.company LIKE ? OR e.industry LIKE ? LIMIT 20");
    $stmt->execute([$likeKeyword, $likeKeyword]);
    $results['companies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($type === 'all' || $type === 'applicants') {
    $stmt = $db->prepare("SELECT s.user_id, u.name, s.skills, s.experience, s.photo FROM seeker_profiles s JOIN users u ON s.user_id = u.id WHERE u.name LIKE ? OR s.skills LIKE ? LIMIT 20");
    $stmt->execute([$likeKeyword, $likeKeyword]);
    $results['applicants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

jsonResponse(['success' => true, 'data' => $results]);
