<?php
// get_stats.php — Returns calculated financial stats + mood
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$month  = currentMonth();

// Totals
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM income WHERE user_id = ? AND DATE_FORMAT(date,'%Y-%m') = ?");
$stmt->execute([$userId, $month]);
$totalIncome = (float)$stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date,'%Y-%m') = ?");
$stmt->execute([$userId, $month]);
$totalExpenses = (float)$stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT limit_amount FROM budget_limits WHERE user_id = ? AND month = ?");
$stmt->execute([$userId, $month]);
$budget = $stmt->fetch();
$budgetLimit = $budget ? (float)$budget['limit_amount'] : 0;

$savings = $totalIncome - $totalExpenses;
$moodData = calculateMood($savings, $budgetLimit, $totalExpenses);
$usagePct = $budgetLimit > 0 ? min(round(($totalExpenses / $budgetLimit) * 100, 1), 100) : 0;

// AI Suggestion
$suggestions = [
    'Happy'   => ['Keep it up! Consider saving an extra 10% this month 🎯', 'You\'re doing great! Why not invest your savings? 📈', 'Excellent control! Try the 50/30/20 rule for optimal savings 💡'],
    'Neutral' => ['Reduce Food spending by 20% to stay in the green 🍔', 'Consider cutting Entertainment costs this week 🎮', 'Review your Shopping list — do you really need everything? 🛍️'],
    'Sad'     => ['You\'ve exceeded your budget! Freeze discretionary spending now 🚫', 'Create a spending plan for the rest of the month 📋', 'Look for free alternatives to your top expense category 💭'],
];
$mood = $moodData['mood'];
$suggestion = $suggestions[$mood][array_rand($suggestions[$mood])];

// Update mood history
$today = date('Y-m-d');
$stmt = $pdo->prepare("INSERT INTO mood_history (user_id, mood, savings, budget_usage, recorded_date) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE mood=VALUES(mood)");
$stmt->execute([$userId, $mood, $savings, $usagePct, $today]);

echo json_encode([
    'mood'          => $mood,
    'emoji'         => $moodData['emoji'],
    'message'       => $moodData['message'],
    'class'         => $moodData['class'],
    'usage_pct'     => $usagePct,
    'total_income'  => $totalIncome,
    'total_expenses'=> $totalExpenses,
    'savings'       => $savings,
    'budget_limit'  => $budgetLimit,
    'suggestion'    => $suggestion,
]);
