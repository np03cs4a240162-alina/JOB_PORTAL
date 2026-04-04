<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: FETCH REVIEWS ───────────────────────────────────────────────────────
if ($method === 'GET') {
    $company = isset($_GET['company']) ? sanitize($_GET['company']) : '';
    
    $sql = 'SELECT r.*, u.name AS author 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id';
    
    $params = [];
    if (!empty($company)) { 
        $sql .= ' WHERE r.company LIKE ?'; 
        $params[] = "%$company%"; 
    }
    
    $sql .= ' ORDER BY r.created_at DESC';
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Improved Average Calculation
        $avg = null;
        if (!empty($company)) {
            $a = $db->prepare('SELECT AVG(rating) FROM reviews WHERE company LIKE ?');
            $a->execute(["%$company%"]);
            $res = $a->fetchColumn();
            $avg = $res ? round((float)$res, 1) : null;
        }

        jsonResponse([
            'reviews' => $reviews, 
            'average_rating' => $avg, 
            'total_count' => count($reviews)
        ]);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to fetch reviews'], 500);
    }
}

// ── POST: CREATE REVIEW ──────────────────────────────────────────────────────
if ($method === 'POST') {
    $user = requireLogin(); // Ensure this handles the session check
    
    // Support both JSON (from apiPost) and Form Data
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $company = sanitize($data['company'] ?? '');
    $rating  = (int)($data['rating'] ?? 0);
    $review  = sanitize($data['review'] ?? '');

    if (empty($company) || $rating < 1 || $rating > 5 || empty($review)) {
        jsonResponse(['error' => 'Valid company, rating (1-5), and review text are required.'], 400);
    }

    try {
        $stmt = $db->prepare('INSERT INTO reviews (user_id, company, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$user['id'], $company, $rating, $review]);
        
        jsonResponse([
            'success' => true, 
            'message' => 'Review posted!',
            'id' => (int)$db->lastInsertId()
        ]);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ── DELETE: REMOVE REVIEW ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $user = requireLogin();
    
    // Get ID from URL query string
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        jsonResponse(['error' => 'No review ID provided.'], 400);
    }

    try {
        $stmt = $db->prepare('SELECT user_id FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            jsonResponse(['error' => 'Review not found.'], 404);
        }

        // Authorization: Owner or Admin only
        $isAdmin = (isset($user['role']) && $user['role'] === 'admin');
        if ($r['user_id'] != $user['id'] && !$isAdmin) {
            jsonResponse(['error' => 'You do not have permission to delete this.'], 403);
        }

        $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Review deleted.']);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }
}

// 405 Method Not Allowed
jsonResponse(['error' => "Method $method not allowed."], 405);