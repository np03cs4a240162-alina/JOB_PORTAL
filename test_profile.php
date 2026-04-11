<?php
require 'config/db.php';
$db = getDB();

try {
    $stmt = $db->query("DESCRIBE seeker_profiles");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(PDOException $e) {
    echo "NO SEEKER PROFILES TABLE! " . $e->getMessage();
}
