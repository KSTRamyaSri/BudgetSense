<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($data['id'] ?? 0);
$type = $data['type'] ?? 'expense';

if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit; }

if ($type === 'income') {
    $stmt = $conn->prepare("DELETE FROM income WHERE id=? AND user_id=?");
} else {
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id=? AND user_id=?");
}

$stmt->bind_param('ii', $id, $user_id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    $mood = getMoodData($conn, $user_id);
    echo json_encode(['success'=>true,'mood'=>$mood]);
} else {
    echo json_encode(['success'=>false,'error'=>'Not found or unauthorized']);
}
