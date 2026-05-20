<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = authorizeRole('admin');
$userId = $user['id'];
$userName = $user['name'];
$userRole = $user['role'];
$db = getDB();

switch ($method) {
    case 'GET':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $db = getDB();
        if ($id) {
            $stmt = $db->prepare('SELECT r.*, u.name AS author FROM reviews r LEFT JOIN users u ON r.user_id=u.id WHERE r.id=?');
            $stmt->execute([$id]);
            $review = $stmt->fetch();
            echo json_encode(['success'=>true,'data'=>[$review]]);
        } else {
            if ($userRole === 'admin') {
                $stmt = $db->query('SELECT r.*, u.name AS author FROM reviews r LEFT JOIN users u ON r.user_id=u.id ORDER BY r.created_at DESC');
                $reviews = $stmt->fetchAll();
            } else {
                $stmt = $db->prepare('SELECT r.*, u.name AS author FROM reviews r LEFT JOIN users u ON r.user_id=u.id WHERE r.user_id = ? ORDER BY r.created_at DESC');
                $stmt->execute([$userId]);
                $reviews = $stmt->fetchAll();
            }
            echo json_encode(['success'=>true,'data'=>$reviews]);
        }
        break;
    case 'POST':
        if ($userRole !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Forbidden']); break; }
        $payload = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO reviews (user_id,company,rating,review) VALUES (?,?,?,?)";
        $stmt = getDB()->prepare($sql);
        $stmt->execute([$userId,$payload['company'],$payload['rating'],$payload['review']]);
        $newId = (int)getDB()->lastInsertId();
        logActivity($userId, $userName, $userRole, 'Created a Company Review (Admin/CRUD)', "Company: " . ($payload['company'] ?? '') . " | Rating: " . ($payload['rating'] ?? ''));
        echo json_encode(['success'=>true,'id'=>$newId]);
        break;
    case 'PUT':
        if ($userRole !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Forbidden']); break; }
        $payload = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE reviews SET company=?,rating=?,review=? WHERE id=?";
        $stmt = getDB()->prepare($sql);
        $stmt->execute([$payload['company'],$payload['rating'],$payload['review'],$payload['id']]);
        logActivity($userId, $userName, $userRole, 'Updated a Company Review (Admin/CRUD)', "Review ID: " . ($payload['id'] ?? 0) . " | Company: " . ($payload['company'] ?? ''));
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if ($userRole !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Forbidden']); break; }
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM reviews WHERE id=?');
        $stmt->execute([$id]);
        logActivity($userId, $userName, $userRole, 'Deleted a Company Review (Admin/CRUD)', "Review ID: " . $id);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
?>
