<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = isset($data['id']) ? (int)$data['id'] : 0;
$title = sanitize($conn, $data['title'] ?? '');
$category = sanitize($conn, $data['category'] ?? 'Other');
$amount = floatval($data['amount'] ?? 0);
$date = sanitize($conn, $data['date'] ?? '');
$note = sanitize($conn, $data['note'] ?? '');

$valid_cats = ['Food','Travel','Books','Entertainment','Shopping','Other'];
if (!in_array($category, $valid_cats)) $category = 'Other';

if (empty($title) || $amount <= 0 || empty($date)) {
    echo json_encode(['success'=>false,'error'=>'Invalid data']); exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE expenses SET title=?, category=?, amount=?, date=?, note=? WHERE id=? AND user_id=?");
    $stmt->bind_param('ssdssii', $title, $category, $amount, $date, $note, $id, $user_id);
} else {
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, title, category, amount, date, note) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('issdss', $user_id, $title, $category, $amount, $date, $note);
}

if ($stmt->execute()) {
    $mood = getMoodData($conn, $user_id);
    echo json_encode(['success'=>true,'mood'=>$mood]);
} else {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$conn->error]);
}
