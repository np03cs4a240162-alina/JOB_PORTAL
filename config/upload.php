<?php


define('UPLOAD_DIR', __DIR__ . '/../uploads/resumes/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);
define('ALLOWED_MIMES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
]);


function handleUpload(array $file): array {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 5MB allowed.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid extension. Only PDF, DOC, and DOCX are allowed.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, ALLOWED_MIMES)) {
        return ['success' => false, 'message' => 'File content does not match its extension.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }


    $newFileName = bin2hex(random_bytes(8)) . "." . $ext;
    $destination = UPLOAD_DIR . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true, 
            'message' => 'Upload successful.', 
            'file_name' => $newFileName // Store this in your 'users' or 'applications' table
        ];
    }

    return ['success' => false, 'message' => 'Failed to save file on server. Check folder permissions.'];
}

