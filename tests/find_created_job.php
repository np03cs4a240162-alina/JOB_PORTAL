<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();
$stmt = $db->prepare('SELECT id,title,employer_id,created_at FROM jobs WHERE title LIKE ? ORDER BY created_at DESC LIMIT 20');
$stmt->execute(['%QA Created Job%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT)."\n";
