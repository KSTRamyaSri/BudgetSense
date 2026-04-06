<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = isset($data['id']) ? (int)$data['id'] : 0;
$source = sanitize($conn, $data['source'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$date = sanitize($conn, $data['date'] ?? '');
$note = sanitize($conn, $data['note'] ?? '');

if (empty($source) || $amount <= 0 || empty($date)) {
    echo json_encode(['success'=>false,'error'=>'Invalid data']); exit;
}

if ($id > 0) {
    // Update
    $stmt = $conn->prepare("UPDATE income SET source=?, amount=?, date=?, note=? WHERE id=? AND user_id=?");
    $stmt->bind_param('sdssii', $source, $amount, $date, $note, $id, $user_id);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO income (user_id, source, amount, date, note) VALUES (?,?,?,?,?)");
    $stmt->bind_param('isdss', $user_id, $source, $amount, $date, $note);
}

if ($stmt->execute()) {
    $mood = getMoodData($conn, $user_id);
    echo json_encode(['success'=>true,'mood'=>$mood]);
} else {
    echo json_encode(['success'=>false,'error'=>'Database error']);
}
