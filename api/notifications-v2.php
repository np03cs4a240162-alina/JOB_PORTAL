<?php
/**
 * Notification System API
 * Handles real-time notifications with read/unread status
 */

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();
$action = $_GET['action'] ?? '';

// ============================================
// GET: Fetch notifications
// ============================================
if ($method === 'GET' && $action === 'list') {
    $type     = $_GET['type'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $unread   = isset($_GET['unread']) ? (bool)$_GET['unread'] : false;
    $limit    = (int)($_GET['limit'] ?? 20);
    $offset   = (int)($_GET['offset'] ?? 0);

    $query = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$user['id']];

    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
    }
    if ($priority) {
        $query .= " AND priority = ?";
        $params[] = $priority;
    }
    if ($unread) {
        $query .= " AND is_read = 0";
    }

    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();

    jsonResponse($notifications);
}

// ============================================
// GET: Fetch unread count
// ============================================
if ($method === 'GET' && $action === 'unread-count') {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    
    jsonResponse(['unread_count' => (int)$result['count']]);
}

// ============================================
// POST: Create notification (admin/system use)
// ============================================
if ($method === 'POST' && $action === 'create') {
    requireCsrf();
    $data = getBody();

    $userId = (int)($data['user_id'] ?? 0);
    $message = sanitize($data['message'] ?? '');
    $type = sanitize($data['type'] ?? 'general');
    $priority = sanitize($data['priority'] ?? 'normal');
    $relatedId = (int)($data['related_id'] ?? 0);
    $relatedType = sanitize($data['related_type'] ?? null);

    if (!$userId || !$message) {
        jsonResponse(['error' => 'User ID and message required'], 400);
    }

    // Verify target user exists
    $chk = $db->prepare('SELECT id FROM users WHERE id = ?');
    $chk->execute([$userId]);
    if (!$chk->fetch()) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO notifications 
            (user_id, message, type, priority, related_id, related_type) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $message, $type, $priority, $relatedId ?: null, $relatedType]);
        
        jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to create notification: ' . $e->getMessage()], 500);
    }
}

// ============================================
// PUT: Mark notification as read
// ============================================
if ($method === 'PUT' && $action === 'mark-read') {
    requireCsrf();
    $data = getBody();
    $notifId = (int)($data['notification_id'] ?? 0);

    if (!$notifId) {
        jsonResponse(['error' => 'Notification ID required'], 400);
    }

    // Verify ownership
    $stmt = $db->prepare('SELECT user_id FROM notifications WHERE id = ?');
    $stmt->execute([$notifId]);
    $notif = $stmt->fetch();

    if (!$notif || $notif['user_id'] != $user['id']) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }

    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->execute([$notifId]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to update notification: ' . $e->getMessage()], 500);
    }
}

// ============================================
// PUT: Mark all notifications as read
// ============================================
if ($method === 'PUT' && $action === 'mark-all-read') {
    requireCsrf();

    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user['id']]);
        
        jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to update notifications: ' . $e->getMessage()], 500);
    }
}

// ============================================
// DELETE: Delete notification
// ============================================
if ($method === 'DELETE') {
    requireCsrf();
    $notifId = (int)($_GET['id'] ?? 0);

    if (!$notifId) {
        jsonResponse(['error' => 'Notification ID required'], 400);
    }

    // Verify ownership
    $stmt = $db->prepare('SELECT user_id FROM notifications WHERE id = ?');
    $stmt->execute([$notifId]);
    $notif = $stmt->fetch();

    if (!$notif || $notif['user_id'] != $user['id']) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }

    try {
        $stmt = $db->prepare('DELETE FROM notifications WHERE id = ?');
        $stmt->execute([$notifId]);
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Failed to delete notification: ' . $e->getMessage()], 500);
    }
}

// ============================================
// GET: Fetch high priority notifications (dashboard)
// ============================================
if ($method === 'GET' && $action === 'dashboard') {
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND priority = 'high' AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    
    jsonResponse($stmt->fetchAll());
}

// Default: List notifications
if (!$action || !in_array($method . '::' . $action, ['GET::list', 'GET::unread-count', 'POST::create', 'PUT::mark-read', 'PUT::mark-all-read', 'DELETE::', 'GET::dashboard'])) {
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user['id']]);
        jsonResponse($stmt->fetchAll());
    } else {
        jsonResponse(['error' => 'Invalid action'], 400);
    }
}
