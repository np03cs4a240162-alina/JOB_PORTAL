<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireRole('seeker');

if ($method === 'GET') {
    $stmt = $db->prepare("SELECT j.*,s.saved_at FROM saved_jobs s JOIN jobs j ON s.job_id=j.id WHERE s.user_id=? ORDER BY s.saved_at DESC");
    $stmt->execute([$user['id']]);
    jsonResponse($stmt->fetchAll());
}
if ($method === 'POST') {
    $data  = getBody(); $jobId = (int)($data['job_id'] ?? 0);
    if (!$jobId) jsonResponse(['error' => 'Job ID required.'], 400);
    $chk = $db->prepare('SELECT id FROM saved_jobs WHERE user_id=? AND job_id=?');
    $chk->execute([$user['id'], $jobId]);
    if ($chk->fetch()) jsonResponse(['error' => 'Already saved.'], 409);
    $db->prepare('INSERT INTO saved_jobs (user_id,job_id) VALUES (?,?)')->execute([$user['id'], $jobId]);
    jsonResponse(['success' => true]);
}
if ($method === 'DELETE') {
    $jobId = (int)($_GET['job_id'] ?? 0);
    if (!$jobId) jsonResponse(['error' => 'Job ID required.'], 400);
    $stmt = $db->prepare('DELETE FROM saved_jobs WHERE user_id=? AND job_id=?');
    $stmt->execute([$user['id'], $jobId]);
    jsonResponse($stmt->rowCount() ? ['success' => true] : ['error' => 'Not found.'], $stmt->rowCount() ? 200 : 404);
}
jsonResponse(['error' => 'Invalid.'], 405);

