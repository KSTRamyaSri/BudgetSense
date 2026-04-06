<?php
// delete_income.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

try {
    $stmt = $pdo->prepare("DELETE FROM income WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode(['success' => $stmt->rowCount() > 0]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
