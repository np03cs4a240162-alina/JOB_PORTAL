<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = authorizeRole('employer');
$userId = $user['id'];
$userName = $user['name'];
$userRole = $user['role'];
$db = getDB();

switch ($method) {
    // ----------------- READ (list or single) -----------------
    case 'GET':
        // if id is provided -> single record, else list all
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $db = getDB();
        if ($id) {
            $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
            $stmt->execute([$id]);
            $job = $stmt->fetch();
            echo json_encode(['success'=>true,'data'=>[$job]]);
        } else {
            $stmt = $db->query('SELECT j.*, u.name AS employer_name FROM jobs j LEFT JOIN users u ON j.employer_id = u.id ORDER BY j.created_at DESC');
            $jobs = $stmt->fetchAll();
            echo json_encode(['success'=>true,'data'=>$jobs]);
        }
        break;

    // ----------------- CREATE -----------------
    case 'POST':
        $payload = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO jobs (title,company,salary,category,type,experience_level,workplace,location,description,employer_id,status,industry) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = getDB()->prepare($sql);
        $stmt->execute([
            $payload['title'] ?? null,
            $payload['company'] ?? null,
            $payload['salary'] ?? null,
            $payload['category'] ?? null,
            $payload['type'] ?? null,
            $payload['experience_level'] ?? null,
            $payload['workplace'] ?? null,
            $payload['location'] ?? null,
            $payload['description'] ?? null,
            $userId, // employer_id derived from logged‑in user
            $payload['status'] ?? 'active',
            $payload['industry'] ?? null
        ]);
        $newId = (int)getDB()->lastInsertId();
        logActivity($userId, $userName, $userRole, 'Posted a Job vacancy (Admin/CRUD)', "Job Title: " . ($payload['title'] ?? '') . " at " . ($payload['company'] ?? ''));
        echo json_encode(['success'=>true,'id'=>$newId]);
        break;

    // ----------------- UPDATE -----------------
    case 'PUT':
        $payload = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE jobs SET title=?,company=?,salary=?,category=?,type=?,experience_level=?,workplace=?,location=?,description=?,status=?,industry=? WHERE id=?";
        $stmt = getDB()->prepare($sql);
        $stmt->execute([
            $payload['title'] ?? null,
            $payload['company'] ?? null,
            $payload['salary'] ?? null,
            $payload['category'] ?? null,
            $payload['type'] ?? null,
            $payload['experience_level'] ?? null,
            $payload['workplace'] ?? null,
            $payload['location'] ?? null,
            $payload['description'] ?? null,
            $payload['status'] ?? 'active',
            $payload['industry'] ?? null,
            $payload['id'] ?? 0
        ]);
        logActivity($userId, $userName, $userRole, 'Updated a Job vacancy (Admin/CRUD)', "Job ID: " . ($payload['id'] ?? 0) . " | Title: " . ($payload['title'] ?? ''));
        echo json_encode(['success'=>true]);
        break;

    // ----------------- DELETE -----------------
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        logActivity($userId, $userName, $userRole, 'Deleted a Job vacancy (Admin/CRUD)', "Job ID: " . $id);
        echo json_encode(['success'=>true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
?>
