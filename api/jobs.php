<?php
// Prevent PHP warnings from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    // 1. Ajax Autocomplete
    if ($action === 'autocomplete') {
        $q = sanitize($_GET['q'] ?? '');
        $stmt = $db->prepare("SELECT DISTINCT title FROM jobs WHERE status='active' AND title LIKE ? ORDER BY title LIMIT 8");
        $stmt->execute(["%$q%"]);
        $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        jsonResponse(['success' => true, 'data' => $titles]);
    }

    // 2. Advanced Search
    if ($action === 'search') {
        $keyword  = sanitize($_GET['keyword']  ?? '');
        $category = sanitize($_GET['category'] ?? '');
        $location = sanitize($_GET['location'] ?? '');
        $company  = sanitize($_GET['company'] ?? '');
        $days     = (int)($_GET['postedDate'] ?? 0);
        
        $sql = "SELECT j.*, u.name AS employer_name 
                FROM jobs j 
                LEFT JOIN users u ON j.employer_id = u.id 
                WHERE j.status = 'active'";
        $params = [];

        if ($keyword !== '') {
            $sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.company LIKE ? OR u.name LIKE ?)";
            $kw = "%$keyword%";
            $params = array_merge($params, [$kw, $kw, $kw, $kw]);
        }
        if ($category !== '' && $category !== 'All') {
            $sql .= " AND j.category = ?";
            $params[] = $category;
        }
        if ($location !== '') {
            $sql .= " AND j.location LIKE ?";
            $params[] = "%$location%";
        }
        if ($company !== '') {
            $sql .= " AND (j.company LIKE ? OR u.name LIKE ?)";
            $comp = "%$company%";
            $params[] = $comp;
            $params[] = $comp;
        }
        if ($days > 0) {
            $sql .= " AND j.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $days;
        }
        
        $type      = sanitize($_GET['jobType'] ?? $_GET['type'] ?? '');
        $workplace = sanitize($_GET['workplace'] ?? '');
        $industry  = sanitize($_GET['industry'] ?? '');
        $expLevel  = sanitize($_GET['expLevel'] ?? $_GET['exp_level'] ?? '');
        
        if ($type !== '') {
            $sql .= " AND j.type = ?";
            $params[] = $type;
        }
        if ($workplace !== '') {
            $sql .= " AND j.workplace = ?";
            $params[] = $workplace;
        }
        if ($industry !== '') {
            $sql .= " AND j.industry = ?";
            $params[] = $industry;
        }
        if ($expLevel !== '') {
            $sql .= " AND j.experience_level = ?";
            $params[] = $expLevel;
        }

        $sql .= " ORDER BY j.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse(['success' => true, 'count' => count($jobs), 'data' => $jobs]);
    }

    // 3. Single Job Details
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT j.*, u.name AS employer_name FROM jobs j LEFT JOIN users u ON j.employer_id = u.id WHERE j.id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
        jsonResponse(['success' => true, 'data' => $job]);
    }

    // 4. Employer's Dashboard View
    if (isset($_GET['mine'])) {
        $user = authorizeRole('employer');
        $stmt = $db->prepare("SELECT j.*, COUNT(a.id) AS application_count 
                              FROM jobs j 
                              LEFT JOIN applications a ON j.id = a.job_id 
                              WHERE j.employer_id = ? 
                              GROUP BY j.id 
                              ORDER BY j.created_at DESC");
        $stmt->execute([$user['id']]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 5. Admin Panel View
    if (isset($_GET['admin_view'])) {
        authorizeRole('admin');
        $stmt = $db->query("SELECT j.*, u.name AS employer_name, COUNT(a.id) AS application_count 
                            FROM jobs j 
                            LEFT JOIN users u ON j.employer_id = u.id 
                            LEFT JOIN applications a ON j.id = a.job_id 
                            GROUP BY j.id 
                            ORDER BY j.created_at DESC");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 6. Default: All Active Jobs (Home/Listings)
    $stmt = $db->query("SELECT j.*, u.name AS employer_name FROM jobs j LEFT JOIN users u ON j.employer_id = u.id WHERE j.status = 'active' ORDER BY j.created_at DESC LIMIT 20");
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── POST: Create Job ──
if ($method === 'POST') {
    $user = authorizeRole('employer');
    requireCsrf();
    $data = getBody();
    
    $fields = ['title', 'company', 'salary', 'category', 'location', 'description', 'type', 'workplace', 'industry', 'deadline', 'experience_level'];
    $clean = [];
    foreach ($fields as $f) {
        $clean[$f] = sanitize($data[$f] ?? '');
    }

    if (!$clean['title'] || !$clean['company'] || !$clean['location'] || !$clean['description']) {
        jsonResponse(['success' => false, 'error' => 'Required fields missing.'], 400);
    }

    $stmt = $db->prepare("INSERT INTO jobs (title, company, salary, category, type, workplace, industry, experience_level, location, description, deadline, employer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $success = $stmt->execute([
        $clean['title'], $clean['company'], $clean['salary'], $clean['category'], 
        $clean['type'], $clean['workplace'], $clean['industry'], $clean['experience_level'],
        $clean['location'], $clean['description'], $clean['deadline'] ?: null, 
        $user['id']
    ]);
    
    if ($success) {
        logActivity($user['id'], $user['name'], $user['role'], 'Posted a Job vacancy', "Job Title: " . $clean['title'] . " at " . $clean['company']);

        // Attempt to reliably determine the inserted job id. Using LAST_INSERT_ID() on
        // some environments can be unreliable when multiple connections exist,
        // so query the most recent matching job for this employer.
        $fetch = $db->prepare('SELECT id FROM jobs WHERE employer_id = ? AND title = ? ORDER BY created_at DESC LIMIT 1');
        $fetch->execute([$user['id'], $clean['title']]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);
        $newId = $row ? (int)$row['id'] : null;
    } else {
        $newId = null;
    }

    jsonResponse(['success' => $success, 'id' => $newId]);
}

// ── PUT: Update Job ──
if ($method === 'PUT' && isset($_GET['id'])) {
    $jobId = (int)$_GET['id'];
    $data = getBody();
    
    requireCsrf();
    $stmt = $db->prepare('SELECT employer_id FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    $user = authorizeOwnerOrAdmin('jobs', $jobId, 'employer_id');

    $status = in_array($data['status'] ?? '', ['active', 'closed']) ? $data['status'] : 'active';
    
    $stmt = $db->prepare("UPDATE jobs SET title=?, company=?, salary=?, category=?, type=?, experience_level=?, workplace=?, industry=?, location=?, description=?, deadline=?, status=? WHERE id=?");
    $stmt->execute([
        sanitize($data['title'] ?? ''),
        sanitize($data['company'] ?? ''),
        sanitize($data['salary'] ?? ''),
        sanitize($data['category'] ?? ''),
        sanitize($data['type'] ?? 'Full Time'),
        sanitize($data['experience_level'] ?? 'entry'),
        sanitize($data['workplace'] ?? 'On-site'),
        sanitize($data['industry'] ?? ''),
        sanitize($data['location'] ?? ''),
        sanitize($data['description'] ?? ''),
        !empty($data['deadline']) ? sanitize($data['deadline']) : null,
        $status,
        $jobId
    ]);
    
    logActivity($user['id'], $user['name'], $user['role'], 'Updated a Job vacancy', "Job ID: " . $jobId . " | Title: " . sanitize($data['title'] ?? ''));
    
    jsonResponse(['success' => true, 'message' => 'Job updated.']);
}

// ── DELETE: Remove Job ──
if ($method === 'DELETE' && isset($_GET['id'])) {
    $jobId = (int)$_GET['id'];
    
    requireCsrf();
    $stmt = $db->prepare('SELECT employer_id FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    $user = authorizeOwnerOrAdmin('jobs', $jobId, 'employer_id');

    // Cascade delete related records
    $db->prepare('DELETE FROM applications WHERE job_id = ?')->execute([$jobId]);
    $db->prepare('DELETE FROM saved_jobs WHERE job_id = ?')->execute([$jobId]);
    $db->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jobId]);
    
    logActivity($user['id'], $user['name'], $user['role'], 'Deleted a Job vacancy', "Job ID: " . $jobId);
    
    jsonResponse(['success' => true, 'message' => 'Job deleted successfully.']);
}

jsonResponse(['success' => false, 'error' => 'Invalid request.'], 400);