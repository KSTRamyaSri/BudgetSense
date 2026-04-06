<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$amount = floatval($data['amount'] ?? 0);
$month = sanitize($conn, $data['month'] ?? date('Y-m'));

if ($amount <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid amount']); exit; }

$stmt = $conn->prepare("INSERT INTO budget_limits (user_id, limit_amount, month) VALUES (?,?,?) ON DUPLICATE KEY UPDATE limit_amount=?");
$stmt->bind_param('idsd', $user_id, $amount, $month, $amount);

if ($stmt->execute()) {
    $mood = getMoodData($conn, $user_id, $month);
    echo json_encode(['success'=>true,'mood'=>$mood]);
} else {
    echo json_encode(['success'=>false,'error'=>'Failed to update budget']);
}
