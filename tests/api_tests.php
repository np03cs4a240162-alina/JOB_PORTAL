<?php
require_once __DIR__ . '/../config/db.php';

function test($name, $fn) {
    echo "Testing $name... ";
    try {
        $fn();
        echo "[PASS]\n";
    } catch (Exception $e) {
        echo "[FAIL] " . $e->getMessage() . "\n";
    }
}

test("Database Connection", function() {
    $db = getDB();
    if (!$db) throw new Exception("DB connection failed");
    $db->query("SELECT 1");
});

test("API Endpoint - Jobs", function() {
    $res = file_get_contents("http://localhost/jobportalsystem/api/jobs.php");
    if (!$res) throw new Exception("Jobs API returned no data");
    $data = json_decode($res, true);
    if (!isset($data['success'])) throw new Exception("Jobs API missing success field");
});

test("API Endpoint - Profiles", function() {
    $res = file_get_contents("http://localhost/jobportalsystem/api/profiles.php");
    if (!$res) throw new Exception("Profiles API returned no data");
});

echo "\nAPI Tests Completed.\n";
