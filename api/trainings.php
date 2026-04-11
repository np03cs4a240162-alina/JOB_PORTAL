<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

/**
 * ── GET: Fetch Trainings ──
 */
if ($method === 'GET') {
    // 1. Fetch Employer's own trainings
    if (isset($_GET['mine'])) {
        $user = checkAuth('employer');
        $stmt = $db->prepare('SELECT * FROM trainings WHERE employer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 2. Admin view of all trainings
    if (isset($_GET['admin_view'])) {
        checkAuth('admin');
        $stmt = $db->query('SELECT t.*, u.name AS employer_name FROM trainings t LEFT JOIN users u ON t.employer_id = u.id ORDER BY t.created_at DESC');
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 3. Public view of active trainings
    $stmt = $db->query("SELECT t.*, u.name AS employer_name FROM trainings t LEFT JOIN users u ON t.employer_id = u.id WHERE t.status = 'active' ORDER BY t.created_at DESC");
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/**
 * ── POST: Create Training ──
 */
if ($method === 'POST') {
    $user = checkAuth('employer');
    $data = JSON_decode(file_get_contents('php://input'), true);

    $title = sanitize($data['title'] ?? '');
    $description = sanitize($data['description'] ?? '');
    $price = sanitize($data['price'] ?? '');
    $duration = sanitize($data['duration'] ?? '');

    if (empty($title) || empty($description)) {
        jsonResponse(['success' => false, 'error' => 'Title and Description are required.'], 400);
    }

    $stmt = $db->prepare('INSERT INTO trainings (employer_id, title, description, price, duration) VALUES (?, ?, ?, ?, ?)');
    $success = $stmt->execute([$user['id'], $title, $description, $price, $duration]);
    
    jsonResponse(['success' => $success, 'id' => (int)$db->lastInsertId()]);
}

/**
 * ── PUT: Update Training Status ──
 */
if ($method === 'PUT') {
    $user = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    $data = JSON_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare('SELECT employer_id FROM trainings WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if (!$t) jsonResponse(['success' => false, 'error' => 'Training not found.'], 404);
    
    if ($user['role'] !== 'admin' && $t['employer_id'] != $user['id']) {
        jsonResponse(['success' => false, 'error' => 'Permission denied.'], 403);
    }

    $status = in_array($data['status'] ?? '', ['active', 'closed']) ? $data['status'] : 'active';
    $stmt = $db->prepare('UPDATE trainings SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);

    jsonResponse(['success' => true]);
}

/**
 * ── DELETE: Remove Training ──
 */
if ($method === 'DELETE') {
    $user = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    
    $stmt = $db->prepare('SELECT employer_id FROM trainings WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if (!$t) jsonResponse(['success' => false, 'error' => 'Training not found.'], 404);
    
    if ($user['role'] !== 'admin' && $t['employer_id'] != $user['id']) {
        jsonResponse(['success' => false, 'error' => 'Permission denied.'], 403);
    }

    $db->prepare('DELETE FROM trainings WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Invalid request.'], 400);
