<?php
// get_data.php — Returns income/expenses as JSON
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$type   = $_GET['type'] ?? 'expenses';

if ($type === 'expenses') {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([$userId]);
    echo json_encode(['expenses' => $stmt->fetchAll()]);
} elseif ($type === 'income') {
    $stmt = $pdo->prepare("SELECT * FROM income WHERE user_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([$userId]);
    echo json_encode(['income' => $stmt->fetchAll()]);
} else {
    echo json_encode(['error' => 'Invalid type']);
}
