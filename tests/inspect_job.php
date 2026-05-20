<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();
$id = (int)($argv[1] ?? 9);
$stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
$stmt->execute([$id]);
$j = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$j) { echo "Job $id not found\n"; exit(0);} 
echo json_encode($j, JSON_PRETTY_PRINT),"\n";
