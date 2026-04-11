<?php
require 'config/db.php';
$db = getDB();
try {
  echo "--- JOBS ---\n";
  $stmt = $db->query("DESCRIBE jobs"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
  echo "--- SEEKER ---\n";
  $stmt = $db->query("DESCRIBE seeker_profiles"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
  echo "--- EMPLOYER ---\n";
  $stmt = $db->query("DESCRIBE employer_profiles"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo $e->getMessage(); }
