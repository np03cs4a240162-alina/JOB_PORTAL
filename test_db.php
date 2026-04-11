<?php
require 'c:/xampp/htdocs/newjob/config/db.php';
$db = getDB();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$res = [];
foreach($tables as $t) {
    // wait I should get describe
    $stmt = $db->query("DESCRIBE " . $t);
    $res[$t] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($res, JSON_PRETTY_PRINT);
