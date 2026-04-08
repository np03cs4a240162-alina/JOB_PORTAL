<?php
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    if ($db) {
        $db->query("SELECT 1");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
