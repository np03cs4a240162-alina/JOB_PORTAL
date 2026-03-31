<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

if ($method === 'GET' && !isset($_GET['with'])) {
    $stmt = $db->prepare("SELECT DISTINCT CASE WHEN from_user=? THEN to_user ELSE from_user END AS partner_id FROM messages WHERE from_user=? OR to_user=?");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $partners = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $convs = [];
    foreach ($partners as $pid) {
        $p = $db->prepare('SELECT id,name,role FROM users WHERE id=?');
        $p->execute([$pid]);
        $l = $db->prepare("SELECT message,sent_at FROM messages WHERE (from_user=? AND to_user=?) OR (from_user=? AND to_user=?) ORDER BY sent_at DESC LIMIT 1");
        $l->execute([$user['id'],$pid,$pid,$user['id']]);
        $convs[] = ['partner' => $p->fetch(), 'last_message' => $l->fetch()];
    }
    jsonResponse($convs);
}

if ($method === 'GET' && isset($_GET['with'])) {
    $pid  = (int)$_GET['with'];
    $stmt = $db->prepare("SELECT m.*,u.name AS sender_name FROM messages m JOIN users u ON m.from_user=u.id WHERE (m.from_user=? AND m.to_user=?) OR (m.from_user=? AND m.to_user=?) ORDER BY m.sent_at ASC");
    $stmt->execute([$user['id'],$pid,$pid,$user['id']]);
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $data    = getBody();
    $toUser  = (int)($data['to_user']  ?? 0);
    $message = sanitize($data['message'] ?? '');
    if (!$toUser || !$message) jsonResponse(['error' => 'Recipient and message required.'], 400);
    if ($toUser === (int)$user['id']) jsonResponse(['error' => 'Cannot message yourself.'], 400);
    $chk = $db->prepare('SELECT id FROM users WHERE id=?'); $chk->execute([$toUser]);
    if (!$chk->fetch()) jsonResponse(['error' => 'Recipient not found.'], 404);
    $db->prepare('INSERT INTO messages (from_user,to_user,message) VALUES (?,?,?)')->execute([$user['id'],$toUser,$message]);
    $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")->execute([$toUser, "New message from ".htmlspecialchars($user['name'])]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()]);
}

if ($method === 'DELETE' && isset($_GET['id'])) {
    $msgId = (int)$_GET['id'];
    $stmt  = $db->prepare('SELECT from_user FROM messages WHERE id=?'); $stmt->execute([$msgId]);
    $msg   = $stmt->fetch();
    if (!$msg) jsonResponse(['error' => 'Message not found.'], 404);
    if ($msg['from_user'] != $user['id']) jsonResponse(['error' => 'Can only delete your own messages.'], 403);
    $db->prepare('DELETE FROM messages WHERE id=?')->execute([$msgId]);
    jsonResponse(['success' => true]);
}
jsonResponse(['error' => 'Invalid request.'], 400);