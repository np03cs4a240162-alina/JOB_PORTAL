<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

/**
 * ── GET: Fetch Trainings ──
 */
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    // 1. Fetch Single Training Details
    if ($id > 0) {
        $stmt = $db->prepare('SELECT t.*, u.name AS employer_name FROM trainings t LEFT JOIN users u ON t.employer_id = u.id WHERE t.id = ?');
        $stmt->execute([$id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$t) jsonResponse(['success' => false, 'error' => 'Training not found.'], 404);
        jsonResponse(['success' => true, 'data' => $t]);
    }

    // 2. Fetch Employer's own trainings
    if (isset($_GET['mine'])) {
        $user = authorizeRole('employer');
        $stmt = $db->prepare('SELECT * FROM trainings WHERE employer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 3. Admin view of all trainings
    if (isset($_GET['admin_view'])) {
        authorizeRole('admin');
        $stmt = $db->query('SELECT t.*, u.name AS employer_name FROM trainings t LEFT JOIN users u ON t.employer_id = u.id ORDER BY t.created_at DESC');
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 4. Public view of active trainings
    $stmt = $db->query("SELECT t.*, u.name AS employer_name FROM trainings t LEFT JOIN users u ON t.employer_id = u.id WHERE t.status = 'active' ORDER BY t.created_at DESC");
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/**
 * ── POST: Create or Enroll ──
 */
if ($method === 'POST') {
    requireCsrf();
    $action = $_GET['action'] ?? '';

    // A. ENROLL IN TRAINING
    if ($action === 'enroll') {
        $user = requireLogin();
        $data = JSON_decode(file_get_contents('php://input'), true);
        $trainingId = (int)($data['training_id'] ?? 0);

        if (!$trainingId) jsonResponse(['success' => false, 'error' => 'Training ID is required.'], 400);

        try {
            $stmt = $db->prepare('INSERT INTO training_enrollments (training_id, user_id) VALUES (?, ?)');
            $stmt->execute([$trainingId, $user['id']]);
            
            logActivity($user['id'], $user['name'], $user['role'], 'Registered for training workshop', "Training ID: " . $trainingId);
            
            jsonResponse(['success' => true, 'message' => 'Enrolled successfully!']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) jsonResponse(['success' => false, 'error' => 'You are already registered for this training.'], 409);
            jsonResponse(['success' => false, 'error' => 'Enrollment failed.'], 500);
        }
    }

    // B. CREATE TRAINING (Employer Only)
    $user = authorizeRole('employer');
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
    
    if ($success) {
        logActivity($user['id'], $user['name'], $user['role'], 'Posted a Training Program', "Title: " . $title);
    }
    
    jsonResponse(['success' => $success, 'id' => (int)$db->lastInsertId()]);
}

/**
 * ── PUT: Update Training Status ──
 */
if ($method === 'PUT') {
    requireCsrf();
    $user = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    $data = JSON_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare('SELECT employer_id FROM trainings WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if (!$t) jsonResponse(['success' => false, 'error' => 'Training not found.'], 404);
    authorizeOwnerOrAdmin('trainings', $id, 'employer_id');

    $title = isset($data['title']) ? sanitize($data['title']) : '';
    if (!empty($title)) {
        // Full training edit
        $description = isset($data['description']) ? sanitize($data['description']) : '';
        $price       = isset($data['price']) ? sanitize($data['price']) : '';
        $duration    = isset($data['duration']) ? sanitize($data['duration']) : '';
        $status      = in_array($data['status'] ?? '', ['active', 'closed']) ? $data['status'] : 'active';

        $stmt = $db->prepare('UPDATE trainings SET title = ?, description = ?, price = ?, duration = ?, status = ? WHERE id = ?');
        $stmt->execute([$title, $description, $price, $duration, $status, $id]);
        
        logActivity($user['id'], $user['name'], $user['role'], 'Updated a Training Program', "Training ID: " . $id . " | Title: " . $title);
    } else {
        // Only status change
        $status = in_array($data['status'] ?? '', ['active', 'closed']) ? $data['status'] : 'active';
        $stmt = $db->prepare('UPDATE trainings SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        
        logActivity($user['id'], $user['name'], $user['role'], 'Updated Training status', "Training ID: " . $id . " | Status: " . $status);
    }

    jsonResponse(['success' => true]);
}

/**
 * ── DELETE: Remove Training ──
 */
if ($method === 'DELETE') {
    requireCsrf();
    $user = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    
    $stmt = $db->prepare('SELECT employer_id FROM trainings WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if (!$t) jsonResponse(['success' => false, 'error' => 'Training not found.'], 404);
    authorizeOwnerOrAdmin('trainings', $id, 'employer_id');

    $db->prepare('DELETE FROM trainings WHERE id = ?')->execute([$id]);
    
    logActivity($user['id'], $user['name'], $user['role'], 'Deleted a Training Program', "Training ID: " . $id);
    
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Invalid request.'], 400);
