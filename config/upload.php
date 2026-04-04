<?php
/**
 * JSTACK File Upload Helper
 * Securely handles resume and document uploads.
 */

define('UPLOAD_DIR', __DIR__ . '/../uploads/resumes/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);
define('ALLOWED_MIMES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
]);

/**
 * Validates and moves the uploaded file to the secure directory.
 * @return array ['success' => bool, 'message' => string, 'file_path' => string|null]
 */
function handleUpload(array $file): array {
    // 1. Basic PHP Upload Errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    // 2. Size Validation
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 5MB allowed.'];
    }

    // 3. Extension Validation
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid extension. Only PDF, DOC, and DOCX are allowed.'];
    }

    // 4. DEEP MIME-TYPE VALIDATION (Security Critical)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, ALLOWED_MIMES)) {
        return ['success' => false, 'message' => 'File content does not match its extension.'];
    }

    // 5. Ensure Directory Exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // 6. Generate Secure Unique Filename
    // Example: user_5_resume_65f1b2c3d4.pdf
    $newFileName = bin2hex(random_bytes(8)) . "." . $ext;
    $destination = UPLOAD_DIR . $newFileName;

    // 7. Move File
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true, 
            'message' => 'Upload successful.', 
            'file_name' => $newFileName // Store this in your 'users' or 'applications' table
        ];
    }

    return ['success' => false, 'message' => 'Failed to save file on server. Check folder permissions.'];
}