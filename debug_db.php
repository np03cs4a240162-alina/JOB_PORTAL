<?php
require 'config/db.php';
$db = getDB();

echo "--- JOBS TABLE ---\n";
$stmt = $db->query("SELECT * FROM jobs ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- APPLICATIONS TABLE ---\n";
$stmt = $db->query("SELECT * FROM applications ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- NOTIFICATIONS SCHEMA ---\n";
try {
    $stmt = $db->query("DESCRIBE notifications");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo $e->getMessage(); }
