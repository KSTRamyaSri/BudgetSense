<?php
// update_expense.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId     = $_SESSION['user_id'];
$expenseId  = (int)($_POST['expense_id'] ?? 0);
$title      = trim($_POST['title'] ?? '');
$category   = $_POST['category'] ?? 'Other';
$amount     = (float)($_POST['amount'] ?? 0);
$date       = $_POST['date'] ?? date('Y-m-d');
$note       = trim($_POST['note'] ?? '');

$validCats = ['Food','Travel','Books','Entertainment','Shopping','Other'];
if (!$expenseId || !$title || $amount <= 0 || !in_array($category, $validCats)) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

try {
    // Ensure user owns this expense
    $stmt = $pdo->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expenseId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE expenses SET title=?, category=?, amount=?, date=?, note=? WHERE id=? AND user_id=?");
    $stmt->execute([$title, $category, $amount, $date, $note, $expenseId, $userId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
