<?php
require 'config/db.php';
$db = getDB();
try {
  $stmt = $db->query("DESCRIBE applications"); 
  print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { 
  echo "ERROR: " . $e->getMessage(); 
}
