<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_budget');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

function getMoodData($conn, $user_id, $month = null) {
    if (!$month) $month = date('Y-m');
    
    // Get total income for month
    $income_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM income 
                   WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$month'";
    $income_result = $conn->query($income_sql);
    $total_income = $income_result->fetch_assoc()['total'];

    // Get total expenses for month
    $expense_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                    WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$month'";
    $expense_result = $conn->query($expense_sql);
    $total_expenses = $expense_result->fetch_assoc()['total'];

    // Get budget limit
    $budget_sql = "SELECT limit_amount FROM budget_limits 
                   WHERE user_id = $user_id AND month = '$month'";
    $budget_result = $conn->query($budget_sql);
    $budget_row = $budget_result->fetch_assoc();
    $budget_limit = $budget_row ? $budget_row['limit_amount'] : 0;

    $savings = $total_income - $total_expenses;
    $budget_usage_pct = $budget_limit > 0 ? ($total_expenses / $budget_limit) * 100 : 0;

    // Determine mood
    $mood = 'Neutral';
    $mood_emoji = '😐';
    $mood_color = 'yellow';
    $mood_message = "You're close to your limit. Spend carefully ⚠️";

    if ($savings >= $budget_limit && $budget_limit > 0) {
        $mood = 'Happy';
        $mood_emoji = '😄';
        $mood_color = 'green';
        $mood_message = "Great job! You are managing your money well 🎉";
    } elseif ($total_expenses > $budget_limit && $budget_limit > 0 || $savings <= 0) {
        $mood = 'Sad';
        $mood_emoji = '😢';
        $mood_color = 'red';
        $mood_message = "Overspending detected! Control your expenses 🚫";
    } elseif ($budget_limit > 0 && $budget_usage_pct >= 70 && $budget_usage_pct < 100) {
        $mood = 'Neutral';
    } elseif ($savings > 0 && ($budget_limit == 0 || $budget_usage_pct < 70)) {
        $mood = 'Happy';
        $mood_emoji = '😄';
        $mood_color = 'green';
        $mood_message = "Great job! You are managing your money well 🎉";
    }

    return [
        'total_income' => (float)$total_income,
        'total_expenses' => (float)$total_expenses,
        'savings' => (float)$savings,
        'budget_limit' => (float)$budget_limit,
        'budget_usage_pct' => round($budget_usage_pct, 1),
        'mood' => $mood,
        'mood_emoji' => $mood_emoji,
        'mood_color' => $mood_color,
        'mood_message' => $mood_message,
        'month' => $month
    ];
}

function getInsights($conn, $user_id) {
    $insights = [];
    $current_month = date('Y-m');
    $week_ago = date('Y-m-d', strtotime('-7 days'));

    // Overspend count this week
    $overspend_sql = "SELECT COUNT(*) as cnt FROM mood_history 
                      WHERE user_id = $user_id AND mood = 'Sad' 
                      AND recorded_date >= '$week_ago'";
    $r = $conn->query($overspend_sql);
    $overspend_count = $r->fetch_assoc()['cnt'];
    if ($overspend_count >= 3) {
        $insights[] = ["icon" => "😢", "text" => "You overspent $overspend_count times this week", "type" => "warning"];
    }

    // Budget success streak
    $streak_sql = "SELECT COUNT(*) as cnt FROM mood_history 
                   WHERE user_id = $user_id AND mood = 'Happy' 
                   AND recorded_date >= '$week_ago'";
    $r = $conn->query($streak_sql);
    $streak = $r->fetch_assoc()['cnt'];
    if ($streak >= 3) {
        $insights[] = ["icon" => "🔥", "text" => "You stayed within budget for $streak days!", "type" => "success"];
    }

    // Top spending category
    $cat_sql = "SELECT category, SUM(amount) as total FROM expenses 
                WHERE user_id = $user_id AND DATE_FORMAT(date,'%Y-%m') = '$current_month'
                GROUP BY category ORDER BY total DESC LIMIT 1";
    $r = $conn->query($cat_sql);
    if ($r && $r->num_rows > 0) {
        $top = $r->fetch_assoc();
        $insights[] = ["icon" => "📊", "text" => "Most spending in " . $top['category'] . " (₹" . number_format($top['total'], 2) . ")", "type" => "info"];
    }

    // AI suggestion: reduce top category
    $ai_sql = "SELECT category, SUM(amount) as total FROM expenses 
               WHERE user_id = $user_id AND DATE_FORMAT(date,'%Y-%m') = '$current_month'
               GROUP BY category ORDER BY total DESC LIMIT 1";
    $r = $conn->query($ai_sql);
    if ($r && $r->num_rows > 0) {
        $top = $r->fetch_assoc();
        $reduce = round($top['total'] * 0.2, 2);
        $insights[] = ["icon" => "🤖", "text" => "AI Tip: Reduce spending on " . $top['category'] . " by 20% to save ₹$reduce", "type" => "ai"];
    }

    return $insights;
}
