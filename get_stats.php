<?php
// get_stats.php
session_start();
require_once 'db.php';

// Force JSON header immediately
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
// Validate month format (YYYY-MM)
$month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');

try {
    // 1. Get Total Income
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM income WHERE user_id = ? AND date LIKE ?");
    $monthParam = $month . '%';
    $stmt->bind_param("is", $userId, $monthParam);
    $stmt->execute();
    $totalIncome = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    // 2. Get Total Expenses
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND date LIKE ?");
    $stmt->bind_param("is", $userId, $monthParam);
    $stmt->execute();
    $totalExpenses = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    // 3. Get Budget Limit
    $stmt = $conn->prepare("SELECT amount FROM budget_limits WHERE user_id = ? AND month = ?");
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $budgetLimit = (float)($stmt->get_result()->fetch_assoc()['amount'] ?? 0);

    // 4. Calculations
    $savings = $totalIncome - $totalExpenses;
    $usagePct = $budgetLimit > 0 ? round(($totalExpenses / $budgetLimit) * 100, 1) : 0;

    // 5. Logic for Mood & Message (Fixes the JS "undefined" issue)
    $mood = "Neutral";
    $message = "Keep tracking your spending! 📊";

    if ($savings > 0 && $usagePct < 80) {
        $mood = "Happy";
        $message = "Great job! You're living within your means. 🌟";
    } elseif ($usagePct >= 100) {
        $mood = "Sad";
        $message = "Warning: You have exceeded your budget! ⚠️";
    } elseif ($savings < 0) {
        $mood = "Sad";
        $message = "You're spending more than you earn this month. 💸";
    }

    echo json_encode([
        'success'        => true,
        'mood'           => $mood,
        'message'        => $message, // Matches your JS logic
        'usage_pct'      => $usagePct,
        'total_income'   => $totalIncome,
        'total_expenses' => $totalExpenses,
        'savings'        => $savings,
        'budget_limit'   => $budgetLimit
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}