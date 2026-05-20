<?php
// Usage: php tests/check_user.php user@example.com
if ($argc < 2) {
    fwrite(STDERR, "Usage: php tests/check_user.php email\n");
    exit(2);
}
$email = $argv[1];
require_once __DIR__ . '/../config/db.php';
$db = getDB();
$stmt = $db->prepare('SELECT id,name,email,role,is_verified,created_at FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['found' => false, 'email' => $email]) . PHP_EOL;
    exit(0);
}
echo json_encode(['found' => true, 'user' => $row], JSON_PRETTY_PRINT) . PHP_EOL;
exit(0);
