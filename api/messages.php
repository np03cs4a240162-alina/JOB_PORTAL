<?php
/**
 * MESSAGES API - JSTACK Job Portal
 * Synchronized with DB table: id, from_user, to_user, message, sent_at
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

// ── DEBUG LOGGING ──
$logFile = __DIR__ . '/api_debug.txt';
$logMsg  = "[" . date('Y-m-d H:i:s') . "] User ID: " . ($user['id'] ?? 'NONE') . " | Method: $method\n";
file_put_contents($logFile, $logMsg, FILE_APPEND);

// ── GET: Conversations or Chat History ──
if ($method === 'GET') {
    // 1. History
    if (isset($_GET['with'])) {
        $pid  = (int)$_GET['with'];
        $stmt = $db->prepare("SELECT m.*, u.name AS sender_name 
                             FROM messages m 
                             JOIN users u ON m.from_user = u.id 
                             WHERE (m.from_user=? AND m.to_user=?) 
                                OR (m.from_user=? AND m.to_user=?) 
                             ORDER BY m.sent_at ASC");
        $stmt->execute([$user['id'], $pid, $pid, $user['id']]);
        jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 2. Converations (Inbox)
    $stmt = $db->prepare("SELECT DISTINCT CASE WHEN from_user=? THEN to_user ELSE from_user END AS partner_id 
                         FROM messages 
                         WHERE from_user=? OR to_user=?");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $partners = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $convs = [];
    foreach ($partners as $pid) {
        if (!$pid) continue;
        
        $uStmt = $db->prepare('SELECT id, name, role FROM users WHERE id=?');
        $uStmt->execute([$pid]);
        $partnerInfo = $uStmt->fetch(PDO::FETCH_ASSOC);

        $mStmt = $db->prepare("SELECT message, sent_at 
                              FROM messages 
                              WHERE (from_user=? AND to_user=?) 
                                 OR (from_user=? AND to_user=?) 
                              ORDER BY sent_at DESC LIMIT 1");
        $mStmt->execute([$user['id'], $pid, $pid, $user['id']]);
        $lastMsg = $mStmt->fetch(PDO::FETCH_ASSOC);

        if ($partnerInfo) {
            $convs[] = ['partner' => $partnerInfo, 'last_message' => $lastMsg];
        }
    }
    
    $logMsg = "Found " . count($convs) . " conversations for user " . $user['id'] . "\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND);
    
    jsonResponse($convs);
}

// ── POST: Send Message ──
if ($method === 'POST') {
    $data   = getBody();
    $toUser = (int)($data['to_user'] ?? 0);
    $text   = sanitize($data['message'] ?? '');

    if (!$toUser || !$text) jsonResponse(['success' => false, 'error' => 'Input missing.'], 400);
    $stmt = $db->prepare("INSERT INTO messages (from_user, to_user, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$user['id'], $toUser, $text])) {
        jsonResponse(['success' => true]);
    }
}

jsonResponse(['success' => false, 'error' => 'Unsupported method.'], 405);