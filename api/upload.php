<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/upload.php'; 

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

/**
 * ── GET: Fetch Resumes ──
 */
if ($method === 'GET') {
    $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user['id'];

    // Security: Seekers can only fetch their own resumes.
    // Employers and Admins can fetch any seeker's resumes.
    if ($user['role'] === 'seeker' && $targetId !== $user['id']) {
        jsonResponse(['success' => false, 'error' => 'Permission denied.'], 403);
    }

    $stmt = $db->prepare('SELECT id, filename, filepath, uploaded_at FROM resumes WHERE user_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$targetId]);
    $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(['success' => true, 'data' => $resumes]);
}

/**
 * ── POST: Upload and Auto-Cleanup ──
 */
if ($method === 'POST') {
    if (!isset($_FILES['resume'])) {
        jsonResponse(['success' => false, 'error' => 'No file selected.'], 400);
    }

    $file = $_FILES['resume'];
    
    // 1. Validate using project config
    $validationResult = validateUpload($file);
    if ($validationResult !== true) {
        jsonResponse(['success' => false, 'error' => $validationResult], 400);
    }

    // 2. Strict MIME check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!defined('ALLOWED_MIMES') || !in_array($mime, ALLOWED_MIMES)) {
        jsonResponse(['success' => false, 'error' => 'Unsupported file type.'], 400);
    }

    // 3. Folder Setup
    $subDir = 'resumes/';
    $targetDir = UPLOAD_DIR . $subDir;
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    // 4. File Naming
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = 'res_' . $user['id'] . '_' . time() . '.' . $ext;
    $savePath = $targetDir . $newFilename;
    $dbPath = 'uploads/' . $subDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $savePath)) {
        try {
            $db->beginTransaction();

            // OPTIONAL: If you only want ONE resume per user, uncomment the lines below:
            /*
            $old = $db->prepare("SELECT filepath FROM resumes WHERE user_id = ?");
            $old->execute([$user['id']]);
            while($r = $old->fetch()) { 
                $oldFile = __DIR__ . '/../' . $r['filepath'];
                if(file_exists($oldFile)) unlink($oldFile);
            }
            $db->prepare("DELETE FROM resumes WHERE user_id = ?")->execute([$user['id']]);
            */

            $stmt = $db->prepare('INSERT INTO resumes (user_id, filename, filepath) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], htmlspecialchars($file['name']), $dbPath]);
            $newId = $db->lastInsertId();

            $db->commit();

            jsonResponse([
                'success' => true, 
                'message' => 'Resume uploaded successfully.',
                'data' => [
                    'id' => $newId,
                    'filename' => $file['name'],
                    'filepath' => $dbPath,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            if (file_exists($savePath)) unlink($savePath); 
            jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'File system error.'], 500);
    }
}

/**
 * ── DELETE: Remove Resume ──
 */
if ($method === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $id = (int)($_GET['id'] ?? 0);
    
    $stmt = $db->prepare('SELECT filepath, user_id FROM resumes WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $resume = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resume) {
        jsonResponse(['success' => false, 'error' => 'File not found.'], 404);
    }

    $fullPath = __DIR__ . '/../' . $resume['filepath'];
    if (file_exists($fullPath)) unlink($fullPath);

    $db->prepare('DELETE FROM resumes WHERE id = ?')->execute([$id]);
    
    jsonResponse(['success' => true, 'message' => 'Resume removed.']);
}

jsonResponse(['success' => false, 'error' => 'Invalid Request.'], 405);