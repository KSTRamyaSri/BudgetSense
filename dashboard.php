<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id      = (int)$_SESSION['user_id'];
$user_name    = $_SESSION['user_name'];
$avatar_color = $_SESSION['avatar_color'] ?? '#4F46E5';
$month        = isset($_GET['month']) ? sanitize($conn, $_GET['month']) : date('Y-m');

// Get mood data
$mood_data = getMoodData($conn, $user_id, $month);
$insights  = getInsights($conn, $user_id);

// Record daily mood snapshot
$today = date('Y-m-d');
$check = $conn->query("SELECT id FROM mood_history WHERE user_id=$user_id AND recorded_date='$today'");
if ($check->num_rows === 0 && ($mood_data['total_income'] > 0 || $mood_data['total_expenses'] > 0)) {
    $mood = $mood_data['mood'];
    $sav  = $mood_data['savings'];
    $exp  = $mood_data['total_expenses'];
    $pct  = $mood_data['budget_usage_pct'];
    $conn->query("INSERT INTO mood_history (user_id, mood, savings, total_expenses, budget_usage_pct, recorded_date) VALUES ($user_id,'$mood',$sav,$exp,$pct,'$today')");
}

// Get expenses with search/filter
$search     = sanitize($conn, $_GET['search']    ?? '');
$filter_cat = sanitize($conn, $_GET['category']  ?? '');
$date_from  = sanitize($conn, $_GET['date_from'] ?? '');
$date_to    = sanitize($conn, $_GET['date_to']   ?? '');
$sort       = in_array($_GET['sort'] ?? '', ['amount_asc','amount_desc','date_asc','date_desc']) ? $_GET['sort'] : 'date_desc';

$exp_where = "WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month'";
if ($search)     $exp_where .= " AND (title LIKE '%$search%' OR category LIKE '%$search%')";
if ($filter_cat) $exp_where .= " AND category='$filter_cat'";
if ($date_from)  $exp_where .= " AND date >= '$date_from'";
if ($date_to)    $exp_where .= " AND date <= '$date_to'";

$sort_map = ['amount_asc'=>'amount ASC','amount_desc'=>'amount DESC','date_asc'=>'date ASC','date_desc'=>'date DESC'];
$order    = $sort_map[$sort];

$expenses    = $conn->query("SELECT * FROM expenses $exp_where ORDER BY $order");
$income_rows = $conn->query("SELECT * FROM income WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");

// Category chart data
$cat_data = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' GROUP BY category ORDER BY total DESC");
$cat_labels = []; $cat_amounts = [];
while ($r = $cat_data->fetch_assoc()) {
    $cat_labels[]  = $r['category'];
    $cat_amounts[] = (float)$r['total'];
}

// Monthly bar chart (last 6 months)
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m     = date('Y-m', strtotime("-$i months"));
    $label = date('M',   strtotime("-$i months"));
    $r     = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$m'");
    $monthly_data[] = ['label' => $label, 'amount' => (float)$r->fetch_assoc()['t']];
}

// Mood trend (last 7 days)
$mood_trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d     = date('Y-m-d', strtotime("-$i days"));
    $label = date('D',     strtotime("-$i days"));
    $r     = $conn->query("SELECT mood FROM mood_history WHERE user_id=$user_id AND recorded_date='$d' ORDER BY id DESC LIMIT 1");
    $row   = $r->fetch_assoc();
    $mood_trend[] = ['label' => $label, 'mood' => $row['mood'] ?? 'None'];
}

$mood_class = strtolower($mood_data['mood']);
$budget_pct = min($mood_data['budget_usage_pct'], 100);

// Avatar initials
$initials = strtoupper(substr($user_name, 0, 1));
if (strpos($user_name, ' ') !== false) {
    $parts    = explode(' ', $user_name);
    $initials = strtoupper($parts[0][0] . end($parts)[0]);
}

// Streak
$streak      = 0;
$streak_date = date('Y-m-d');
while (true) {
    $r   = $conn->query("SELECT mood FROM mood_history WHERE user_id=$user_id AND recorded_date='$streak_date' LIMIT 1");
    $row = $r->fetch_assoc();
    if ($row && $row['mood'] === 'Happy') {
        $streak++;
        $streak_date = date('Y-m-d', strtotime($streak_date . ' -1 day'));
    } else break;
    if ($streak > 30) break;
}

// Mood icon helper (fallback when GIF not available)
function moodIcon(string $moodClass): string {
    if ($moodClass === 'happy')   return '<i class="fa-solid fa-face-smile-beam"></i>';
    if ($moodClass === 'sad')     return '<i class="fa-solid fa-face-sad-tear"></i>';
    return '<i class="fa-solid fa-face-meh"></i>';
}

function moodGifUrl(string $moodClass): string {
    $gifs = ['happy' => 'assets/happy.gif', 'sad' => 'assets/sad2.gif',];
    return $gifs[$moodClass] ?? $gifs['happy'];
}

function moodGifSidebarUrl(string $moodClass): string {
    return moodGifUrl($moodClass);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BudgetSense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
        <!-- <script src="script.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <link rel="stylesheet" href="styles/dashboard.css">
   
</head>

<body class="dashboard-body">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fa-solid fa-wallet logo-fa-icon"></i><span class="logo-text">BUDGET SENSE</span>
            </div>
            <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar"><i
                    class="fa-solid fa-chevron-left" id="sidebarToggleIcon"></i></button>
            <button class="sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sidebar-user">
            <div class="avatar" style="background:<?= htmlspecialchars($avatar_color) ?>">
                <?= htmlspecialchars($initials) ?>
                <?php if ($streak >= 3): ?><span class="streak-badge" title="<?= $streak ?> day streak!"><i
                        class="fa-solid fa-fire"></i></span><?php endif; ?>
            </div>
            <img class="sidebar-mood-gif" src="<?= moodGifSidebarUrl($mood_class) ?>"
                alt="<?= htmlspecialchars($mood_data['mood']) ?> mood" loading="lazy">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="user-mood <?= $mood_class ?>"><?= htmlspecialchars($mood_data['mood']) ?> Mode</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#overview" class="nav-item active" data-section="overview" data-label="Overview"><i
                    class="fa-solid fa-house nav-icon"></i> <span>Overview</span></a>
            <a href="#income" class="nav-item" data-section="income" data-label="Income"><i
                    class="fa-solid fa-wallet nav-icon"></i> <span>Income</span></a>
            <a href="#expenses" class="nav-item" data-section="expenses" data-label="Expenses"><i
                    class="fa-solid fa-money-bill-wave nav-icon"></i> <span>Expenses</span></a>
            <a href="#charts" class="nav-item" data-section="charts" data-label="Analytics"><i
                    class="fa-solid fa-chart-line nav-icon"></i> <span>Analytics</span></a>
            <a href="#budget" class="nav-item" data-section="budget" data-label="Budget Goal"><i
                    class="fa-solid fa-bullseye nav-icon"></i> <span>Budget Goal</span></a>
        </nav>
        <div class="sidebar-footer">
            <button class="theme-toggle" id="themeToggle"><i class="fa-solid fa-moon theme-icon"></i><span
                    class="theme-label">Dark Mode</span></button>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span></a>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-content" id="mainContent">

        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-btn" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
                <div class="page-title">
                    <h1>Dashboard</h1><span class="page-sub">Welcome back,
                        <?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
                </div>
            </div>
            <div class="topbar-right">
                <form method="GET" id="monthForm"><input type="month" name="month"
                        value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" class="month-picker">
                </form>
                <a href="export_csv.php?month=<?= urlencode($month) ?>" class="btn-export"><i
                        class="fa-solid fa-file-arrow-down"></i> Export</a>
                <div class="notif-bell" id="notifBell" style="position:relative"><i
                        class="fa-solid fa-bell"></i><?php if ($mood_data['budget_usage_pct'] >= 70): ?><span
                        class="notif-dot"></span><?php endif; ?></div>
            </div>
        </header>

        <!-- mood panel -->
        <div class="notif-panel" id="notifPanel"
            style="display:none; position:absolute; top:48px; right:16px; z-index:600;">
            <?php if ($mood_data['budget_usage_pct'] >= 90): ?><div class="notif-item notif-danger"><i
                    class="fa-solid fa-triangle-exclamation"></i>Critical: <?= $mood_data['budget_usage_pct'] ?>% budget
                used!</div><?php endif; ?>
            <?php if ($mood_data['budget_usage_pct'] >= 70 && $mood_data['budget_usage_pct'] < 90): ?><div
                class="notif-item notif-warn"><i class="fa-solid fa-triangle-exclamation"></i>Warning:
                <?= $mood_data['budget_usage_pct'] ?>% used</div><?php endif; ?>
            <?php if ($mood_data['savings'] < 0): ?><div class="notif-item notif-danger"><i
                    class="fa-solid fa-circle-exclamation"></i>Expenses exceed income!</div><?php endif; ?>
            <?php if ($streak >= 3): ?><div class="notif-item notif-success"><i
                    class="fa-solid fa-fire"></i><?= $streak ?>-day streak!</div><?php endif; ?>
            <div class="notif-item notif-info"><i
                    class="fa-solid fa-calendar-days"></i><?= date('F Y', strtotime($month . '-01')) ?></div>
        </div>

        <div class="dashboard-scroll">

            <!-- overview -->
            <section id="overview" class="section active">
                <div class="mood-card mood-<?= $mood_class ?>" id="moodCard">
                    <div class="mood-left">
                        <img class="mood-emoji-gif" src="<?= moodGifUrl($mood_class) ?>"
                            alt="<?= htmlspecialchars($mood_data['mood']) ?>" loading="lazy"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="mood-emoji-large" style="display:none;"><?= moodIcon($mood_class) ?></div>
                        <div class="mood-text">
                            <h2 class="mood-state"><?= htmlspecialchars($mood_data['mood']) ?> Mode</h2>
                            <p class="mood-message"><?= htmlspecialchars($mood_data['mood_message']) ?></p>
                        </div>
                    </div>
                    <div class="mood-right"><?php if ($streak >= 1): ?><div class="streak-display"><i
                                class="fa-solid fa-fire streak-fire"></i>
                            <div>
                                <div class="streak-num"><?= $streak ?></div>
                                <div class="streak-label">Day Streak</div>
                            </div>
                        </div><?php endif; ?></div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card glass-card" data-animate>
                        <div class="stat-icon income-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div class="stat-body">
                            <div class="stat-label">Total Income</div>
                            <div class="stat-value income-amt-val">₹<?= number_format($mood_data['total_income'], 2) ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card glass-card" data-animate>
                        <div class="stat-icon expense-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <div class="stat-body">
                            <div class="stat-label">Total Expenses</div>
                            <div class="stat-value expense-amt-val">
                                ₹<?= number_format($mood_data['total_expenses'], 2) ?></div>
                        </div>
                    </div>
                    <div class="stat-card glass-card" data-animate>
                        <div class="stat-icon savings-icon">
                            <?= $mood_data['savings'] >= 0 ? '<i class="fa-solid fa-piggy-bank"></i>' : '<i class="fa-solid fa-chart-line fa-flip-vertical"></i>' ?>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Monthly Savings</div>
                            <div class="stat-value savings-amt-val"
                                style="color: <?= $mood_data['savings'] < 0 ? '#ef4444' : 'inherit' ?>">
                                <?= $mood_data['savings'] < 0 ? '-' : '' ?>₹<?= number_format(abs($mood_data['savings']), 2) ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card glass-card" data-animate>
                        <div class="stat-icon budget-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <div class="stat-body">
                            <div class="stat-label">Budget Limit</div>
                            <div class="stat-value budget-limit-val">
                                ₹<?= number_format($mood_data['budget_limit'], 2) ?></div>
                        </div>
                    </div>
                </div>
                <div class="budget-progress-card glass-card" data-animate>
                    <div class="budget-header">
                        <h3>Budget Usage</h3><span
                            class="budget-pct-label <?= $mood_class ?>"><?= $budget_pct ?>%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill progress-<?= $mood_class ?>" style="width:0%"
                            data-target="<?= $budget_pct ?>%" id="budgetBar"></div>
                    </div>
                </div>
            </section>

            <!-- income section -->
            <section id="income" class="section">
                <div class="section-header">
                    <h2>Income <span class="section-count"><?= $income_rows->num_rows ?></span></h2><button
                        class="btn-primary" onclick="openModal('incomeModal')"><i class="fa-solid fa-plus"></i> Add
                        Income</button>
                </div>
                <div class="table-card glass-card"><?php if ($income_rows->num_rows === 0): ?><div class="empty-state">
                        <p>No income recorded</p><button class="btn-primary" onclick="openModal('incomeModal')">Add
                            First Income</button>
                    </div><?php else: ?><table class="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Note</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php while ($row = $income_rows->fetch_assoc()): ?><tr class="table-row">
                                <td><?= htmlspecialchars($row['source']) ?></td>
                                <td class="amount-cell income-amt">+₹<?= number_format($row['amount'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                                <td><?= htmlspecialchars($row['note'] ?? '—') ?></td>
                                <td><button class="btn-edit-sm"
                                        onclick="editIncome(<?= $row['id'] ?>,'<?= addslashes($row['source']) ?>','<?= $row['amount'] ?>','<?= $row['date'] ?>','<?= addslashes($row['note'] ?? '') ?>')"><i
                                            class="fa-solid fa-pen"></i></button><button class="btn-del-sm"
                                        onclick="deleteRecord('income', <?= $row['id'] ?>)"><i
                                            class="fa-solid fa-trash"></i></button></td>
                            </tr><?php endwhile; ?></tbody>
                    </table><?php endif; ?></div>
            </section>

            <!-- expenses section -->
            <section id="expenses" class="section">
                <div class="section-header">
                    <h2>Expenses <span class="section-count"><?= $expenses->num_rows ?></span></h2><button
                        class="btn-primary" onclick="openModal('expenseModal')"><i class="fa-solid fa-plus"></i> Add
                        Expense</button>
                </div>
                <div class="table-card glass-card"><?php if ($expenses->num_rows === 0): ?><div class="empty-state">
                        <p>No expenses found</p><button class="btn-primary" onclick="openModal('expenseModal')">Add
                            First Expense</button>
                    </div><?php else: ?><table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php while ($row = $expenses->fetch_assoc()): ?><tr>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><span
                                        class="cat-badge cat-<?= strtolower(htmlspecialchars($row['category'])) ?>"><?= htmlspecialchars($row['category']) ?></span>
                                </td>
                                <td class="amount-cell expense-amt">-₹<?= number_format($row['amount'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                                <td><button class="btn-edit-sm"
                                        onclick="editExpense(<?= $row['id'] ?>,'<?= addslashes($row['title']) ?>','<?= $row['category'] ?>','<?= $row['amount'] ?>','<?= $row['date'] ?>','<?= addslashes($row['note'] ?? '') ?>')"><i
                                            class="fa-solid fa-pen"></i></button><button class="btn-del-sm"
                                        onclick="deleteRecord('expense', <?= $row['id'] ?>)"><i
                                            class="fa-solid fa-trash"></i></button></td>
                            </tr><?php endwhile; ?></tbody>
                    </table><?php endif; ?></div>
            </section>

            <!-- analytics section -->
            <!-- <section id="charts" class="section">
          
                <h2 class="section-title">Analytics</h2>
                <div class="charts-grid">
                    <div class="chart-card glass-card">
                        <h3>Spending by Category</h3><?php if (empty($cat_labels)): ?><div class="empty-state small">
                            <p>No data</p>
                        </div><?php else: ?><canvas id="pieChart" height="250"></canvas><?php endif; ?>
                    </div>
                    <div class="chart-card glass-card">
                        <h3>Monthly Expenses</h3><canvas id="barChart" height="250"></canvas>
                    </div>
                    <div class="chart-card glass-card chart-full">
                        <h3>Mood Trend</h3><canvas id="moodChart" height="150"></canvas>
                    </div>
                </div>
            </section> -->

            <section id="charts" class="section" style="padding: 10px; height: calc(100vh - 100px); overflow-y: auto;">
                <h2 class="section-title" style="margin: 0 0 15px 0; font-size: 1.4rem; color: #000;">Analytics</h2>

                <div class="charts-grid"
                    style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between;">

                    <div class="chart-card glass-card"
                        style="flex: 1 1 300px; background: #fff; border: 3px solid #000; border-radius: 0px; padding: 10px; box-shadow: 8px 8px 0px #000; min-height: 300px;">
                        <h3 style="margin-top: 0; font-size: 1rem; text-transform: uppercase;">Spending by Category</h3>
                        <div style="position: relative; height: 220px; width: 100%;">
                            <?php if (empty($cat_labels)): ?>
                            <div class="empty-state small" style="text-align: center; padding-top: 50px;">No data</div>
                            <?php else: ?>
                            <canvas id="pieChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card glass-card"
                        style="flex: 1 1 300px; background: #fff; border: 3px solid #000; border-radius: 0px; padding: 10px; box-shadow: 8px 8px 0px #000; min-height: 300px;">
                        <h3 style="margin-top: 0; font-size: 1rem; text-transform: uppercase;">Monthly Expenses</h3>
                        <div style="position: relative; height: 220px; width: 100%;">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card glass-card chart-full"
                        style="flex: 1 1 100%; background: #fff; border: 3px solid #000; border-radius: 0px; padding: 10px; box-shadow: 8px 8px 0px #000; margin-top: 10px;">
                        <h3 style="margin-top: 0; font-size: 1rem; text-transform: uppercase;">Mood Trend</h3>
                        <div style="position: relative; height: 150px; width: 100%;">
                            <canvas id="moodChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            <!-- budget section -->
            <section id="budget" class="section">
                <h2 class="section-title">Budget Goal</h2>
                <div class="budget-setup glass-card">
                    <div class="budget-form-wrap">
                        <h3>Set Monthly Budget for <?= date('F Y', strtotime($month . '-01')) ?></h3>
                        <p>Define your spending limit.</p>
                        <form id="budgetForm" class="budget-form">
                            <div class="budget-input-row">
                                <div class="input-wrap big-input"><span class="input-icon">₹</span><input type="number"
                                        id="budgetAmount" value="<?= htmlspecialchars($mood_data['budget_limit']) ?>"
                                        required></div><button type="submit" class="btn-primary btn-lg"><i
                                        class="fa-solid fa-bullseye"></i> Set Budget</button>
                            </div>
                        </form>
                    </div>
                    <div class="budget-visual">
                        <div class="donut-wrap"><canvas id="budgetDonut" width="200" height="200"></canvas>
                            <div class="donut-center"><span class="donut-pct"><?= $budget_pct ?>%</span><span
                                    class="donut-label">Used</span></div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <!-- add income modal -->
    <div class="modal-overlay" id="incomeModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-circle-dollar-to-slot"></i> <span id="incomeModalTitle">Add Income</span></h3>
                <button class="modal-close" onclick="closeModal('incomeModal')"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="incomeForm" class="modal-form"><input type="hidden" id="income_id" name="id" value="">
                <div class="form-group"><label>Source</label>
                    <div class="input-wrap"><input type="text" name="source" id="income_source" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Amount (₹)</label>
                        <div class="input-wrap"><input type="number" name="amount" id="income_amount" step="0.01"
                                required></div>
                    </div>
                    <div class="form-group"><label>Date</label>
                        <div class="input-wrap"><input type="date" name="date" id="income_date" required></div>
                    </div>
                </div>
                <div class="form-group"><label>Note</label><textarea name="note" id="income_note" rows="2"></textarea>
                </div>
                <div class="modal-actions"><button type="button" class="btn-secondary"
                        onclick="closeModal('incomeModal')">Cancel</button><button type="submit" class="btn-primary"
                        id="incomeSubmitBtn">Add Income</button></div>
            </form>
        </div>
    </div>

    <!-- add expense modal -->
    <div class="modal-overlay" id="expenseModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-receipt"></i> <span id="expenseModalTitle">Add Expense</span></h3><button
                    class="modal-close" onclick="closeModal('expenseModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="expenseForm" class="modal-form"><input type="hidden" id="expense_id" name="id" value="">
                <div class="form-group"><label>Title</label>
                    <div class="input-wrap"><input type="text" name="title" id="expense_title" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Category</label><select name="category" id="expense_category"
                            class="styled-select" required>
                            <option>Food</option>
                            <option>Travel</option>
                            <option>Books</option>
                            <option>Entertainment</option>
                            <option>Shopping</option>
                            <option>Other</option>
                        </select></div>
                    <div class="form-group"><label>Amount (₹)</label>
                        <div class="input-wrap"><input type="number" name="amount" id="expense_amount" step="0.01"
                                required></div>
                    </div>
                </div>
                <div class="form-group"><label>Date</label>
                    <div class="input-wrap"><input type="date" name="date" id="expense_date" required></div>
                </div>
                <div class="form-group"><label>Note</label><textarea name="note" id="expense_note" rows="2"></textarea>
                </div>
                <div class="modal-actions"><button type="button" class="btn-secondary"
                        onclick="closeModal('expenseModal')">Cancel</button><button type="submit" class="btn-primary"
                        id="expenseSubmitBtn">Add Expense</button></div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        'use strict';
    const CHART_DATA = <?= json_encode([
    'catLabels' => $cat_labels,
    'catAmounts' => $cat_amounts,
    'monthlyLabels' => array_column($monthly_data, 'label'),
    'monthlyAmounts' => array_column($monthly_data, 'amount'),
    'budgetPct' => $budget_pct,
    'month' => $month,
]) ?>;

    const MOOD_CLASS = '<?= $mood_class ?>';

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Handle Routing
        const params = new URLSearchParams(window.location.search);
        const section = params.get('section') || (window.location.hash ? window.location.hash.substring(1) :
            'overview');
        showSection(section);

        // 2. Initialize UI Components
        initSidebar();
        initNotifications();
        animateOnLoad();
        setDefaultDates();
        createMoodShower(MOOD_CLASS);

        // 3. Initialize Data & Charts
        refreshDashboardStats(); // This updates the text
        initCharts(); // This draws the canvas
    });

    async function updateLiveStats() {
        try {
            const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
            const data = await response.json();

            if (data.success) {
                // Update total values on the cards
                // Note: Ensure your HTML elements have these IDs or classes
                console.log("Live Stats Updated:", data);
            }
        } catch (err) {
            console.error("Could not fetch live stats", err);
        }
    }

    // Run this when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        updateLiveStats();
    });

    function showSection(name) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById(name)?.classList.add('active');
        document.querySelector(`.nav-item[data-section="${name}"]`)?.classList.add('active');
    }

    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const sectionName = item.dataset.section;
            showSection(sectionName);
            history.pushState(null, '', `?month=${CHART_DATA.month}&section=${sectionName}`);
            if (window.innerWidth <= 900) {
                document.getElementById('sidebar')?.classList.remove('open');
                document.getElementById('sidebarBackdrop')?.classList.remove('show');
            }
        });
    });

    let isSidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        document.getElementById('menuBtn')?.addEventListener('click', () => {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
        });
        document.getElementById('sidebarClose')?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
        backdrop?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            isSidebarCollapsed = !isSidebarCollapsed;
            localStorage.setItem('sidebar_collapsed', isSidebarCollapsed);
            applySidebarState();
        });
        applySidebarState();
    }

    function applySidebarState() {
        if (window.innerWidth > 900) {
            document.body.classList.toggle('sidebar-is-collapsed', isSidebarCollapsed);
            document.getElementById('sidebar')?.classList.toggle('sidebar-collapsed', isSidebarCollapsed);
        }
    }
    window.addEventListener('resize', applySidebarState);

    function initNotifications() {
        const bell = document.getElementById('notifBell');
        const panel = document.getElementById('notifPanel');
        if (!bell || !panel) return;
        bell.addEventListener('click', e => {
            e.stopPropagation();
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', e => {
            if (!panel.contains(e.target) && e.target !== bell) panel.style.display = 'none';
        });
    }

    function createMoodShower(mood) {
        document.getElementById('mood-shower-container')?.remove();
        const container = document.createElement('div');
        container.id = 'mood-shower-container';
        document.body.insertAdjacentElement('afterbegin', container);
        const configs = {
            happy: {
                count: 40,
                emojis: ['😊', '🌸', '💖', '🎉', '✨']
            },
            sad: {
                count: 50,
                emojis: ['😢', '💧', '☂️'],
                isRain: true
            }
        };
        const config = configs[mood.toLowerCase()] || {};
        for (let i = 0; i < (config.count || 0); i++) {
            const p = document.createElement('div');
            p.className = config.isRain ? 'particle raindrop' : 'particle';
            if (!config.isRain) p.textContent = config.emojis[Math.floor(Math.random() * config.emojis.length)];
            p.style.left = (Math.random() * 105 - 5) + 'vw';
            p.style.animationDuration = (Math.random() * 8 + 4) + 's';
            p.style.animationDelay = (Math.random() * 5) + 's';
            container.appendChild(p);
        }
    }

    function openModal(id) {
        document.getElementById(id)?.classList.add('open');
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        const form = overlay.querySelector('form');
        if (form) form.reset();
        if (id === 'incomeModal') {
            document.getElementById('incomeModalTitle').textContent = 'Add Income';
            document.getElementById('incomeSubmitBtn').textContent = 'Add Income';
        }
        if (id === 'expenseModal') {
            document.getElementById('expenseModalTitle').textContent = 'Add Expense';
            document.getElementById('expenseSubmitBtn').textContent = 'Add Expense';
        }
    }
    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
    });

    function animateOnLoad() {
        const bar = document.getElementById('budgetBar');
        if (bar) setTimeout(() => {
            bar.style.width = bar.getAttribute('data-target');
        }, 300);
    }

    let toastTimer;

    function showToast(msg, type = 'info') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className = `toast ${type} show`;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 3500);
    }

    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        ['income_date', 'expense_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.value) el.value = today;
        });
    }


    function initCharts() {
        // 1. Pie/Donut Chart (Spending by Category)
        const pieCtx = document.getElementById('pieChart')?.getContext('2d');
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: CHART_DATA.catLabels,
                    datasets: [{
                        data: CHART_DATA.catAmounts,
                        backgroundColor: ['#ff90e8', '#ffc900', '#01d1ff', '#00f5d4', '#ff5c5c'],
                        borderWidth: 2,
                        borderColor: '#000'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        }

        // 2. Bar Chart (Monthly Expenses)
        const barCtx = document.getElementById('barChart')?.getContext('2d');
        if (barCtx) {
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: CHART_DATA.monthlyLabels,
                    datasets: [{
                        label: 'Expenses',
                        data: CHART_DATA.monthlyAmounts,
                        backgroundColor: '#ffc900',
                        borderColor: '#000',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Idi false unte chart container height ki adjust avthundi
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            position: 'bottom' // Legend valla height peruguthundi, so bottom ki marchu
                        }
                    }
                }
            });
        }

        // 3. Line Chart (Mood Trend)
        const moodCtx = document.getElementById('moodChart')?.getContext('2d');
        if (moodCtx) {
            new Chart(moodCtx, {
                type: 'line',
                data: {
                    labels: CHART_DATA.moodLabels,
                    datasets: [{
                        label: 'Mood Level',
                        data: CHART_DATA.moodData,
                        borderColor: '#01d1ff',
                        tension: 0.4,
                        fill: false,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Idi false unte chart container height ki adjust avthundi
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            position: 'bottom' // Legend valla height peruguthundi, so bottom ki marchu
                        }
                    }
                }
            });
        }
    }


    // async function refreshDashboardStats() {
    //     try {
    //         const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
    //         const data = await response.json();
    //         if (data.error) return;

    //         // Update Stat Cards
    //         document.querySelector('.income-amt-val').textContent = '₹' + parseFloat(data.total_income)
    //             .toLocaleString();
    //         document.querySelector('.expense-amt-val').textContent = '₹' + parseFloat(data.total_expenses)
    //             .toLocaleString();

    //         const savingsEl = document.querySelector('.savings-amt-val');
    //         savingsEl.textContent = (data.savings < 0 ? '-' : '') + '₹' + Math.abs(data.savings)
    //             .toLocaleString();
    //         savingsEl.style.color = data.savings < 0 ? '#ef4444' : 'inherit';

    //         document.querySelector('.budget-limit-val').textContent = '₹' + parseFloat(data.budget_limit)
    //             .toLocaleString();

    //         if (data.message) document.querySelector('.mood-message').textContent = data.message;
    //     } catch (error) {
    //         console.error('Error fetching stats:', error);
    //     }
    // }


    async function refreshDashboardStats() {
        try {
            const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
            const data = await response.json();
            if (data.error) return;

            // Update Stat Cards - Ensure these classes exist in your HTML
            const updateText = (selector, val) => {
                const el = document.querySelector(selector);
                if (el) el.textContent = '₹' + parseFloat(val).toLocaleString();
            };

            updateText('.income-amt-val', data.total_income);
            updateText('.expense-amt-val', data.total_expenses);
            updateText('.budget-limit-val', data.budget_limit);

            const savingsEl = document.querySelector('.savings-amt-val');
            if (savingsEl) {
                savingsEl.textContent = (data.savings < 0 ? '-' : '') + '₹' + Math.abs(data.savings)
                .toLocaleString();
                savingsEl.style.color = data.savings < 0 ? '#ff3f3f' : 'inherit';
            }

            // Use 'message' or 'suggestion' based on your PHP return key
            const msgEl = document.querySelector('.mood-message');
            if (msgEl) msgEl.textContent = data.message || data.suggestion || "";

        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    }


    // CORRECTED FORM SUBMISSION LOGIC
    async function handleFormSubmit(url, data, modalId, submitBtnId) {
        const btn = document.getElementById(submitBtnId);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving…';
        }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Saved successfully!', 'success');
                if (modalId) closeModal(modalId);
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(json.error || 'Failed to save', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Submit';
                }
            }
        } catch {
            showToast('Network error', 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Submit';
            }
        }
    }

    document.getElementById('incomeForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const data = {
            id: document.getElementById('income_id').value,
            source: document.getElementById('income_source').value,
            amount: document.getElementById('income_amount').value,
            date: document.getElementById('income_date').value,
            note: document.getElementById('income_note').value
        };
        handleFormSubmit('add_income.php', data, 'incomeModal', 'incomeSubmitBtn');
    });

    document.getElementById('expenseForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const data = {
            id: document.getElementById('expense_id').value,
            title: document.getElementById('expense_title').value,
            category: document.getElementById('expense_category').value,
            amount: document.getElementById('expense_amount').value,
            date: document.getElementById('expense_date').value,
            note: document.getElementById('expense_note').value
        };
        handleFormSubmit('add_expense.php', data, 'expenseModal', 'expenseSubmitBtn');
    });

    document.getElementById('budgetForm')?.addEventListener('submit', e => {
        e.preventDefault();
        handleFormSubmit('update_budget.php', {
            amount: document.getElementById('budgetAmount').value,
            month: CHART_DATA.month
        }, null);
    });

    function deleteRecord(type, id) {
        if (confirm(`Delete this ${type}?`)) {
            handleFormSubmit('delete_expense.php', {
                type,
                id
            }, null);
        }
    }

    function editIncome(id, source, amount, date, note) {
        document.getElementById('income_id').value = id;
        document.getElementById('income_source').value = source;
        document.getElementById('income_amount').value = amount;
        document.getElementById('income_date').value = date;
        document.getElementById('income_note').value = note;
        document.getElementById('incomeModalTitle').textContent = 'Edit Income';
        document.getElementById('incomeSubmitBtn').textContent = 'Update Income';
        openModal('incomeModal');
    }

    function editExpense(id, title, category, amount, date, note) {
        document.getElementById('expense_id').value = id;
        document.getElementById('expense_title').value = title;
        document.getElementById('expense_category').value = category;
        document.getElementById('expense_amount').value = amount;
        document.getElementById('expense_date').value = date;
        document.getElementById('expense_note').value = note;
        document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
        document.getElementById('expenseSubmitBtn').textContent = 'Update Expense';
        openModal('expenseModal');
    }

    </script>
</body>

</html>