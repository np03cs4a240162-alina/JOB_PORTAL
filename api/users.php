<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {
    $current = requireLogin();
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($current['role'] !== 'admin' && $current['id'] != $id) jsonResponse(['error' => 'Forbidden.'], 403);
        $stmt = $db->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) jsonResponse(['error' => 'Not found.'], 404);
        
        $u['profile'] = null;
        if ($u['role'] === 'seeker') {
            $profStmt = $db->prepare('SELECT phone, skills, experience, bio, photo FROM seeker_profiles WHERE user_id=?');
            $profStmt->execute([$id]);
            $prof = $profStmt->fetch();
            if ($prof) $u['profile'] = $prof;
        } elseif ($u['role'] === 'employer') {
            $profStmt = $db->prepare('SELECT company, industry, website, about, logo FROM employer_profiles WHERE user_id=?');
            $profStmt->execute([$id]);
            $prof = $profStmt->fetch();
            if ($prof) $u['profile'] = $prof;
        }
        
        jsonResponse($u);
    }
    authorizeRole('admin');
    jsonResponse($db->query('SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC')->fetchAll());
}

if ($method === 'POST') {
    requireCsrf();
    $current = authorizeRole('admin');
    $data  = getBody();
    $name  = sanitize($data['name']  ?? '');
    $email = sanitize($data['email'] ?? '');
    $pass  = $data['password']       ?? '';
    $role  = sanitize($data['role']  ?? 'seeker');
    if (!$name || !$email || !$pass) jsonResponse(['error' => 'All fields required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email.'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Password min 6 chars.'], 400);
    $chk = $db->prepare('SELECT id FROM users WHERE email=?'); $chk->execute([$email]);
    if ($chk->fetch()) jsonResponse(['error' => 'Email exists.'], 409);
    $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
    
    logActivity($current['id'], $current['name'], $current['role'], 'Created user account via Admin console', "Name: " . $name . " | Email: " . $email . " | Role: " . $role);
    
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()]);
}

if ($method === 'PUT' && isset($_GET['id'])) {
    requireCsrf();
    $id = (int)$_GET['id'];
    $current = authorizeOwnerOrAdmin('users', $id, 'id');
    $data = getBody();
    
    $name = sanitize($data['name'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $role = sanitize($data['role'] ?? '');
    $password = $data['password'] ?? '';
    
    if (!$name || !$email) jsonResponse(['error' => 'Name and Email are required.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email address.'], 400);
    
    // Check if email already taken by someone else
    $chk = $db->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $chk->execute([$email, $id]);
    if ($chk->fetch()) jsonResponse(['error' => 'Email already in use.'], 409);
    
    // Fetch user before update to know the role
    $stmt = $db->prepare('SELECT role FROM users WHERE id=?');
    $stmt->execute([$id]);
    $oldUser = $stmt->fetch();
    if (!$oldUser) jsonResponse(['error' => 'User not found.'], 404);
    $oldRole = $oldUser['role'];
    
    // Update core fields
    if (!empty($password)) {
        if (strlen($password) < 6) jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        if ($current['role'] === 'admin' && !empty($role)) {
            $db->prepare('UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?')->execute([$name, $email, $role, $passHash, $id]);
        } else {
            $db->prepare('UPDATE users SET name=?, email=?, password=? WHERE id=?')->execute([$name, $email, $passHash, $id]);
        }
    } else {
        if ($current['role'] === 'admin' && !empty($role)) {
            $db->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?')->execute([$name, $email, $role, $id]);
        } else {
            $db->prepare('UPDATE users SET name=?, email=? WHERE id=?')->execute([$name, $email, $id]);
        }
    }
    
    $finalRole = ($current['role'] === 'admin' && !empty($role)) ? $role : $oldRole;
    
    // If role changed, ensure profile table records are managed
    if ($finalRole === 'seeker') {
        $phone = sanitize($data['phone'] ?? '');
        $skills = sanitize($data['skills'] ?? '');
        $experience = sanitize($data['experience'] ?? '');
        $bio = sanitize($data['bio'] ?? '');
        
        $pchk = $db->prepare('SELECT id FROM seeker_profiles WHERE user_id=?');
        $pchk->execute([$id]);
        if ($pchk->fetch()) {
            $db->prepare('UPDATE seeker_profiles SET phone=?, skills=?, experience=?, bio=? WHERE user_id=?')->execute([$phone, $skills, $experience, $bio, $id]);
        } else {
            $db->prepare('INSERT INTO seeker_profiles (user_id, phone, skills, experience, bio) VALUES (?, ?, ?, ?, ?)')->execute([$id, $phone, $skills, $experience, $bio]);
        }
    } elseif ($finalRole === 'employer') {
        $company = sanitize($data['company'] ?? '');
        $industry = sanitize($data['industry'] ?? '');
        $website = sanitize($data['website'] ?? '');
        $about = sanitize($data['about'] ?? '');
        
        $pchk = $db->prepare('SELECT id FROM employer_profiles WHERE user_id=?');
        $pchk->execute([$id]);
        if ($pchk->fetch()) {
            $db->prepare('UPDATE employer_profiles SET company=?, industry=?, website=?, about=? WHERE user_id=?')->execute([$company, $industry, $website, $about, $id]);
        } else {
            $db->prepare('INSERT INTO employer_profiles (user_id, company, industry, website, about) VALUES (?, ?, ?, ?, ?)')->execute([$id, $company, $industry, $website, $about]);
        }
    }
    
    logActivity($current['id'], $current['name'], $current['role'], 'Updated user account details via Admin', "Target User ID: " . $id . " | New Name: " . $name . " | Final Role: " . $finalRole);
    
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE' && isset($_GET['id'])) {
    requireCsrf();
    $id = (int)$_GET['id'];
    $current = requireLogin();
    if ($current['role'] === 'admin' && $id === (int)$current['id']) jsonResponse(['error' => 'Cannot delete yourself.'], 403);
    if ($current['role'] !== 'admin') {
        // non-admins may delete only their own account
        if ($current['id'] != $id) jsonResponse(['error' => 'Forbidden.'], 403);
    }
    $db->beginTransaction();
    $stmt = $db->prepare('SELECT role FROM users WHERE id=?'); $stmt->execute([$id]); $u = $stmt->fetch();
    if (!$u) { $db->rollBack(); jsonResponse(['error' => 'Not found.'], 404); }
    if ($u['role'] === 'seeker') {
        $db->prepare('DELETE FROM seeker_profiles WHERE user_id=?')->execute([$id]);
        $db->prepare('DELETE FROM applications   WHERE seeker_id=?')->execute([$id]);
        $db->prepare('DELETE FROM saved_jobs     WHERE user_id=?')->execute([$id]);
        $db->prepare('DELETE FROM resumes        WHERE user_id=?')->execute([$id]);
    } elseif ($u['role'] === 'employer') {
        $db->prepare('DELETE FROM employer_profiles WHERE user_id=?')->execute([$id]);
        $db->prepare("UPDATE jobs SET status='closed' WHERE employer_id=?")->execute([$id]);
    }
    $db->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$id]);
    $db->prepare('DELETE FROM messages WHERE from_user=? OR to_user=?')->execute([$id,$id]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    $db->commit();
    
    logActivity($current['id'], $current['name'], $current['role'], 'Deleted user account', "Target User ID: " . $id . " | Target Role: " . $u['role']);
    
    if ($id === (int)$current['id']) session_destroy();
    jsonResponse(['success' => true]);
}
jsonResponse(['error' => 'Invalid request.'], 400);