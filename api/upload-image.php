<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'POST only allow.'], 405);
}

$user = requireLogin();
$db = getDB();

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'error' => 'No file uploaded or upload error.'], 400);
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB limit
if ($file['size'] > $maxSize) {
    jsonResponse(['success' => false, 'error' => 'File too large. Max 5MB allowed.'], 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMimes)) {
    jsonResponse(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.'], 400);
}

$uploadDir = __DIR__ . '/../uploads/images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('img_') . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    if ($user['role'] === 'employer') {
        $db->prepare("UPDATE employer_profiles SET logo = ? WHERE user_id = ?")->execute([$filename, $user['id']]);
    } else if ($user['role'] === 'seeker') {
        $db->prepare("UPDATE seeker_profiles SET photo = ? WHERE user_id = ?")->execute([$filename, $user['id']]);
    }
    jsonResponse(['success' => true, 'message' => 'Image uploaded successfully.', 'filename' => $filename]);
} else {
    jsonResponse(['success' => false, 'error' => 'Failed to save file.'], 500);
}
