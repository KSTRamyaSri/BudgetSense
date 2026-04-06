<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$avatar_color = $_SESSION['avatar_color'] ?? '#4F46E5';
$month = isset($_GET['month']) ? sanitize($conn, $_GET['month']) : date('Y-m');

// Get mood data
$mood_data = getMoodData($conn, $user_id, $month);
$insights = getInsights($conn, $user_id);

// Record daily mood snapshot
$today = date('Y-m-d');
$check = $conn->query("SELECT id FROM mood_history WHERE user_id=$user_id AND recorded_date='$today'");
if ($check->num_rows === 0 && ($mood_data['total_income'] > 0 || $mood_data['total_expenses'] > 0)) {
    $mood = $mood_data['mood'];
    $sav = $mood_data['savings'];
    $exp = $mood_data['total_expenses'];
    $pct = $mood_data['budget_usage_pct'];
    $conn->query("INSERT INTO mood_history (user_id, mood, savings, total_expenses, budget_usage_pct, recorded_date) VALUES ($user_id,'$mood',$sav,$exp,$pct,'$today')");
}

// Get expenses for current month with search/filter
$search = sanitize($conn, $_GET['search'] ?? '');
$filter_cat = sanitize($conn, $_GET['category'] ?? '');
$date_from = sanitize($conn, $_GET['date_from'] ?? '');
$date_to = sanitize($conn, $_GET['date_to'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['amount_asc','amount_desc','date_asc','date_desc']) ? $_GET['sort'] : 'date_desc';

$exp_where = "WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month'";
if ($search) $exp_where .= " AND (title LIKE '%$search%' OR category LIKE '%$search%')";
if ($filter_cat) $exp_where .= " AND category='$filter_cat'";
if ($date_from) $exp_where .= " AND date >= '$date_from'";
if ($date_to) $exp_where .= " AND date <= '$date_to'";

$sort_map = ['amount_asc'=>'amount ASC','amount_desc'=>'amount DESC','date_asc'=>'date ASC','date_desc'=>'date DESC'];
$order = $sort_map[$sort];

$expenses = $conn->query("SELECT * FROM expenses $exp_where ORDER BY $order");

// Get income
$income_rows = $conn->query("SELECT * FROM income WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");

// Category breakdown for chart
$cat_data = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' GROUP BY category ORDER BY total DESC");
$cat_labels = []; $cat_amounts = [];
while($r = $cat_data->fetch_assoc()) {
    $cat_labels[] = $r['category'];
    $cat_amounts[] = (float)$r['total'];
}

// Monthly bar chart (last 6 months)
$monthly_data = [];
for($i=5; $i>=0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$m'");
    $monthly_data[] = ['label'=>$label,'amount'=>(float)$r->fetch_assoc()['t']];
}

// Mood trend (last 7 days)
$mood_trend = [];
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $r = $conn->query("SELECT mood FROM mood_history WHERE user_id=$user_id AND recorded_date='$d' ORDER BY id DESC LIMIT 1");
    $row = $r->fetch_assoc();
    $mood_trend[] = ['label'=>$label,'mood'=>$row['mood']??'None'];
}

$mood_class = strtolower($mood_data['mood']);
$budget_pct = min($mood_data['budget_usage_pct'], 100);
$bar_color = $mood_class === 'happy' ? '#22C55E' : ($mood_class === 'sad' ? '#EF4444' : '#F59E0B');

// Avatar initials
$initials = strtoupper(substr($user_name, 0, 1));
if (strpos($user_name, ' ') !== false) {
    $parts = explode(' ', $user_name);
    $initials = strtoupper($parts[0][0] . end($parts)[0]);
}

// Calculate streak
$streak = 0;
$streak_date = date('Y-m-d');
while(true) {
    $r = $conn->query("SELECT mood FROM mood_history WHERE user_id=$user_id AND recorded_date='$streak_date' LIMIT 1");
    $row = $r->fetch_assoc();
    if ($row && $row['mood'] === 'Happy') { $streak++; $streak_date = date('Y-m-d', strtotime($streak_date . ' -1 day')); }
    else break;
    if ($streak > 30) break;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — BudgetSense</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
</head>
<body class="dashboard-body">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">
      <span class="logo-icon">💰</span>
      <span class="logo-text">BudgetSense</span>
    </div>
    <button class="sidebar-close" id="sidebarClose">✕</button>
  </div>

  <div class="sidebar-user">
    <div class="avatar" style="background: <?= $avatar_color ?>">
      <?= $initials ?>
      <?php if($streak >= 3): ?>
      <span class="streak-badge" title="<?= $streak ?> day streak!">🔥</span>
      <?php endif; ?>
    </div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
      <div class="user-mood <?= $mood_class ?>">
        <?= $mood_data['mood_emoji'] ?> <?= $mood_data['mood'] ?> Mode
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <a href="#overview" class="nav-item active" data-section="overview">
      <span class="nav-icon">📊</span> Overview
    </a>
    <a href="#income" class="nav-item" data-section="income">
      <span class="nav-icon">💵</span> Income
    </a>
    <a href="#expenses" class="nav-item" data-section="expenses">
      <span class="nav-icon">💸</span> Expenses
    </a>
    <a href="#charts" class="nav-item" data-section="charts">
      <span class="nav-icon">📈</span> Analytics
    </a>
    <a href="#budget" class="nav-item" data-section="budget">
      <span class="nav-icon">🎯</span> Budget Goal
    </a>
  </nav>

  <div class="sidebar-footer">
    <button class="theme-toggle" id="themeToggle">
      <span class="theme-icon">🌙</span>
      <span class="theme-label">Dark Mode</span>
    </button>
    <a href="logout.php" class="btn-logout">
      <span>🚪</span> Logout
    </a>
  </div>
</aside>

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-btn" id="menuBtn">☰</button>
      <div class="page-title">
        <h1>Dashboard</h1>
        <span class="page-sub">Welcome back, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?> <?= $mood_data['mood_emoji'] ?></span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="month-nav">
        <form method="GET" id="monthForm">
          <input type="month" name="month" value="<?= $month ?>" onchange="this.form.submit()" class="month-picker">
        </form>
      </div>
      <a href="export_csv.php?month=<?= $month ?>" class="btn-export">
        📥 Export CSV
      </a>
      <div class="notif-bell" id="notifBell">
        🔔
        <?php if($mood_data['budget_usage_pct'] >= 70): ?>
        <span class="notif-dot"></span>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Notification Panel -->
  <div class="notif-panel" id="notifPanel" style="display:none">
    <?php if($mood_data['budget_usage_pct'] >= 90): ?>
    <div class="notif-item notif-danger">⚠️ Critical: You've used <?= $mood_data['budget_usage_pct'] ?>% of your budget!</div>
    <?php elseif($mood_data['budget_usage_pct'] >= 70): ?>
    <div class="notif-item notif-warn">⚠️ Warning: Budget usage at <?= $mood_data['budget_usage_pct'] ?>%</div>
    <?php endif; ?>
    <?php if($mood_data['savings'] < 0): ?>
    <div class="notif-item notif-danger">🚨 Your expenses exceed your income this month!</div>
    <?php endif; ?>
    <?php if($streak >= 3): ?>
    <div class="notif-item notif-success">🔥 Amazing! <?= $streak ?>-day budget success streak!</div>
    <?php endif; ?>
    <div class="notif-item notif-info">📅 Month: <?= date('F Y', strtotime($month . '-01')) ?></div>
  </div>

  <div class="dashboard-scroll">

    <!-- ===== OVERVIEW SECTION ===== -->
    <section id="overview" class="section active">

      <!-- Mood Card -->
      <div class="mood-card mood-<?= $mood_class ?>" id="moodCard">
        <div class="mood-left">
          <div class="mood-emoji-large"><?= $mood_data['mood_emoji'] ?></div>
          <div class="mood-text">
            <h2 class="mood-state"><?= $mood_data['mood'] ?> Mode</h2>
            <p class="mood-message"><?= $mood_data['mood_message'] ?></p>
          </div>
        </div>
        <div class="mood-right">
          <?php if($streak >= 1): ?>
          <div class="streak-display">
            <span class="streak-fire">🔥</span>
            <div>
              <div class="streak-num"><?= $streak ?></div>
              <div class="streak-label">Day Streak</div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card glass-card" data-animate>
          <div class="stat-icon income-icon">💵</div>
          <div class="stat-body">
            <div class="stat-label">Total Income</div>
            <div class="stat-value" data-value="<?= $mood_data['total_income'] ?>">
              ₹<?= number_format($mood_data['total_income'], 2) ?>
            </div>
          </div>
          <div class="stat-trend up">↑</div>
        </div>

        <div class="stat-card glass-card" data-animate>
          <div class="stat-icon expense-icon">💸</div>
          <div class="stat-body">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value">₹<?= number_format($mood_data['total_expenses'], 2) ?></div>
          </div>
          <div class="stat-trend down">↓</div>
        </div>

        <div class="stat-card glass-card <?= $mood_data['savings'] >= 0 ? 'savings-positive' : 'savings-negative' ?>" data-animate>
          <div class="stat-icon savings-icon"><?= $mood_data['savings'] >= 0 ? '🏦' : '📉' ?></div>
          <div class="stat-body">
            <div class="stat-label">Monthly Savings</div>
            <div class="stat-value">₹<?= number_format(abs($mood_data['savings']), 2) ?></div>
          </div>
          <div class="stat-badge <?= $mood_data['savings'] >= 0 ? 'badge-green' : 'badge-red' ?>">
            <?= $mood_data['savings'] >= 0 ? 'Saving' : 'Deficit' ?>
          </div>
        </div>

        <div class="stat-card glass-card" data-animate>
          <div class="stat-icon budget-icon">🎯</div>
          <div class="stat-body">
            <div class="stat-label">Budget Limit</div>
            <div class="stat-value">₹<?= number_format($mood_data['budget_limit'], 2) ?></div>
          </div>
          <div class="stat-badge <?= $mood_class === 'happy' ? 'badge-green' : ($mood_class === 'sad' ? 'badge-red' : 'badge-yellow') ?>">
            <?= $budget_pct ?>% Used
          </div>
        </div>
      </div>

      <!-- Budget Progress -->
      <div class="budget-progress-card glass-card" data-animate>
        <div class="budget-header">
          <h3>Budget Usage — <?= date('F Y', strtotime($month.'-01')) ?></h3>
          <span class="budget-pct-label <?= $mood_class ?>"><?= $budget_pct ?>%</span>
        </div>
        <div class="progress-track">
          <div class="progress-fill progress-<?= $mood_class ?>" 
               style="width: 0%" 
               data-target="<?= $budget_pct ?>%"
               id="budgetBar">
          </div>
          <div class="progress-markers">
            <span class="marker" style="left:70%">70%</span>
            <span class="marker" style="left:100%">Limit</span>
          </div>
        </div>
        <div class="budget-stats-row">
          <span>Spent: ₹<?= number_format($mood_data['total_expenses'],2) ?></span>
          <span>Remaining: ₹<?= number_format(max(0, $mood_data['budget_limit'] - $mood_data['total_expenses']),2) ?></span>
          <span>Limit: ₹<?= number_format($mood_data['budget_limit'],2) ?></span>
        </div>
      </div>

      <!-- Insights -->
      <?php if(!empty($insights)): ?>
      <div class="insights-card glass-card" data-animate>
        <h3 class="insights-title">💡 Smart Insights</h3>
        <div class="insights-grid">
          <?php foreach($insights as $ins): ?>
          <div class="insight-item insight-<?= $ins['type'] ?>">
            <span class="insight-icon"><?= $ins['icon'] ?></span>
            <span class="insight-text"><?= htmlspecialchars($ins['text']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </section>

    <!-- ===== INCOME SECTION ===== -->
    <section id="income" class="section">
      <div class="section-header">
        <h2>Income <span class="section-count"><?= $income_rows->num_rows ?></span></h2>
        <button class="btn-primary" onclick="openModal('incomeModal')">+ Add Income</button>
      </div>

      <div class="table-card glass-card">
        <?php if($income_rows->num_rows === 0): ?>
        <div class="empty-state">
          <div class="empty-emoji">💵</div>
          <p>No income recorded for this month</p>
          <button class="btn-primary" onclick="openModal('incomeModal')">Add First Income</button>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr>
              <th>Source</th><th>Amount</th><th>Date</th><th>Note</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php $income_rows->data_seek(0); while($row = $income_rows->fetch_assoc()): ?>
            <tr class="table-row" data-id="<?= $row['id'] ?>">
              <td><span class="source-badge">💵</span> <?= htmlspecialchars($row['source']) ?></td>
              <td class="amount-cell income-amt">+₹<?= number_format($row['amount'],2) ?></td>
              <td><?= date('d M Y', strtotime($row['date'])) ?></td>
              <td class="note-cell"><?= htmlspecialchars($row['note'] ?? '—') ?></td>
              <td class="actions-cell">
                <button class="btn-edit-sm" onclick="editIncome(<?= $row['id'] ?>,'<?= addslashes($row['source']) ?>','<?= $row['amount'] ?>','<?= $row['date'] ?>','<?= addslashes($row['note']??'') ?>')">✏️</button>
                <button class="btn-del-sm" onclick="deleteRecord('income', <?= $row['id'] ?>)">🗑️</button>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===== EXPENSES SECTION ===== -->
    <section id="expenses" class="section">
      <div class="section-header">
        <h2>Expenses <span class="section-count"><?= $expenses->num_rows ?></span></h2>
        <button class="btn-primary" onclick="openModal('expenseModal')">+ Add Expense</button>
      </div>

      <!-- Filters -->
      <form method="GET" class="filter-bar glass-card">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="section" value="expenses">
        <div class="filter-row">
          <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search expenses..." value="<?= htmlspecialchars($_GET['search']??'') ?>" class="search-input">
          </div>
          <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php foreach(['Food','Travel','Books','Entertainment','Shopping','Other'] as $c): ?>
            <option value="<?=$c?>" <?= ($_GET['category']??'')===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
          <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from']??'') ?>" class="filter-input" placeholder="From">
          <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to']??'') ?>" class="filter-input" placeholder="To">
          <select name="sort" class="filter-select">
            <option value="date_desc" <?= $sort==='date_desc'?'selected':'' ?>>Date ↓</option>
            <option value="date_asc" <?= $sort==='date_asc'?'selected':'' ?>>Date ↑</option>
            <option value="amount_desc" <?= $sort==='amount_desc'?'selected':'' ?>>Amount ↓</option>
            <option value="amount_asc" <?= $sort==='amount_asc'?'selected':'' ?>>Amount ↑</option>
          </select>
          <button type="submit" class="btn-filter">Filter</button>
          <a href="dashboard.php?month=<?=$month?>&section=expenses" class="btn-clear">Clear</a>
        </div>
      </form>

      <div class="table-card glass-card">
        <?php if($expenses->num_rows === 0): ?>
        <div class="empty-state">
          <div class="empty-emoji">💸</div>
          <p>No expenses found<?= $search ? ' for "'.$search.'"' : '' ?></p>
          <button class="btn-primary" onclick="openModal('expenseModal')">Add First Expense</button>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr>
              <th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Note</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php $cat_emojis = ['Food'=>'🍔','Travel'=>'✈️','Books'=>'📚','Entertainment'=>'🎮','Shopping'=>'🛍️','Other'=>'📦'];
            $expenses->data_seek(0);
            while($row = $expenses->fetch_assoc()): ?>
            <tr class="table-row" data-id="<?= $row['id'] ?>">
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td>
                <span class="cat-badge cat-<?= strtolower($row['category']) ?>">
                  <?= $cat_emojis[$row['category']] ?? '📦' ?> <?= $row['category'] ?>
                </span>
              </td>
              <td class="amount-cell expense-amt">-₹<?= number_format($row['amount'],2) ?></td>
              <td><?= date('d M Y', strtotime($row['date'])) ?></td>
              <td class="note-cell"><?= htmlspecialchars($row['note'] ?? '—') ?></td>
              <td class="actions-cell">
                <button class="btn-edit-sm" onclick="editExpense(<?= $row['id'] ?>,'<?= addslashes($row['title']) ?>','<?= $row['category'] ?>','<?= $row['amount'] ?>','<?= $row['date'] ?>','<?= addslashes($row['note']??'') ?>')">✏️</button>
                <button class="btn-del-sm" onclick="deleteRecord('expense', <?= $row['id'] ?>)">🗑️</button>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===== CHARTS SECTION ===== -->
    <section id="charts" class="section">
      <h2 class="section-title">Analytics</h2>
      <div class="charts-grid">
        <div class="chart-card glass-card">
          <h3>Spending by Category</h3>
          <?php if(empty($cat_labels)): ?>
          <div class="empty-state small"><div class="empty-emoji">📊</div><p>No data yet</p></div>
          <?php else: ?>
          <canvas id="pieChart" height="250"></canvas>
          <?php endif; ?>
        </div>
        <div class="chart-card glass-card">
          <h3>Monthly Expenses (6 Months)</h3>
          <canvas id="barChart" height="250"></canvas>
        </div>
        <div class="chart-card glass-card chart-full">
          <h3>Mood Trend (Last 7 Days)</h3>
          <canvas id="moodChart" height="150"></canvas>
        </div>
      </div>
    </section>

    <!-- ===== BUDGET SECTION ===== -->
    <section id="budget" class="section">
      <h2 class="section-title">Budget Goal</h2>
      <div class="budget-setup glass-card">
        <div class="budget-form-wrap">
          <h3>Set Monthly Budget for <?= date('F Y', strtotime($month.'-01')) ?></h3>
          <p class="budget-desc">Define your spending limit to track your financial health and get mood-based feedback.</p>
          <form id="budgetForm" class="budget-form">
            <div class="budget-input-row">
              <div class="input-wrap big-input">
                <span class="input-icon">₹</span>
                <input type="number" id="budgetAmount" placeholder="Enter budget limit (e.g. 10000)" 
                       value="<?= $mood_data['budget_limit'] ?>" min="1" step="100" required>
              </div>
              <button type="submit" class="btn-primary btn-lg">Set Budget 🎯</button>
            </div>
          </form>
          <div id="budgetMsg" class="budget-feedback"></div>
        </div>
        <div class="budget-visual">
          <div class="donut-wrap">
            <canvas id="budgetDonut" width="200" height="200"></canvas>
            <div class="donut-center">
              <span class="donut-pct"><?= $budget_pct ?>%</span>
              <span class="donut-label">Used</span>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div><!-- end dashboard-scroll -->
</div><!-- end main-content -->

<!-- ===== MODALS ===== -->
<!-- Income Modal -->
<div class="modal-overlay" id="incomeModal">
  <div class="modal glass-card">
    <div class="modal-header">
      <h3>💵 <span id="incomeModalTitle">Add Income</span></h3>
      <button class="modal-close" onclick="closeModal('incomeModal')">✕</button>
    </div>
    <form id="incomeForm" class="modal-form">
      <input type="hidden" id="income_id" name="id" value="">
      <div class="form-group">
        <label>Income Source</label>
        <div class="input-wrap">
          <span class="input-icon">💵</span>
          <input type="text" name="source" id="income_source" placeholder="e.g. Part-time job, Scholarship" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Amount (₹)</label>
          <div class="input-wrap">
            <span class="input-icon">₹</span>
            <input type="number" name="amount" id="income_amount" placeholder="0.00" min="0.01" step="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label>Date</label>
          <div class="input-wrap">
            <span class="input-icon">📅</span>
            <input type="date" name="date" id="income_date" required>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Note (Optional)</label>
        <textarea name="note" id="income_note" placeholder="Add a note..." rows="2"></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeModal('incomeModal')">Cancel</button>
        <button type="submit" class="btn-primary" id="incomeSubmitBtn">Add Income</button>
      </div>
    </form>
  </div>
</div>

<!-- Expense Modal -->
<div class="modal-overlay" id="expenseModal">
  <div class="modal glass-card">
    <div class="modal-header">
      <h3>💸 <span id="expenseModalTitle">Add Expense</span></h3>
      <button class="modal-close" onclick="closeModal('expenseModal')">✕</button>
    </div>
    <form id="expenseForm" class="modal-form">
      <input type="hidden" id="expense_id" name="id" value="">
      <div class="form-group">
        <label>Title</label>
        <div class="input-wrap">
          <span class="input-icon">📝</span>
          <input type="text" name="title" id="expense_title" placeholder="e.g. Lunch, Uber ride" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <select name="category" id="expense_category" class="styled-select" required>
            <option value="Food">🍔 Food</option>
            <option value="Travel">✈️ Travel</option>
            <option value="Books">📚 Books</option>
            <option value="Entertainment">🎮 Entertainment</option>
            <option value="Shopping">🛍️ Shopping</option>
            <option value="Other">📦 Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount (₹)</label>
          <div class="input-wrap">
            <span class="input-icon">₹</span>
            <input type="number" name="amount" id="expense_amount" placeholder="0.00" min="0.01" step="0.01" required>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Date</label>
        <div class="input-wrap">
          <span class="input-icon">📅</span>
          <input type="date" name="date" id="expense_date" required>
        </div>
      </div>
      <div class="form-group">
        <label>Note (Optional)</label>
        <textarea name="note" id="expense_note" placeholder="Add a note..." rows="2"></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeModal('expenseModal')">Cancel</button>
        <button type="submit" class="btn-primary" id="expenseSubmitBtn">Add Expense</button>
      </div>
    </form>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Chart Data -->
<script>
const CHART_DATA = {
  catLabels: <?= json_encode($cat_labels) ?>,
  catAmounts: <?= json_encode($cat_amounts) ?>,
  monthlyLabels: <?= json_encode(array_column($monthly_data,'label')) ?>,
  monthlyAmounts: <?= json_encode(array_column($monthly_data,'amount')) ?>,
  moodLabels: <?= json_encode(array_column($mood_trend,'label')) ?>,
  moodData: <?= json_encode(array_column($mood_trend,'mood')) ?>,
  budgetPct: <?= $budget_pct ?>,
  budgetRemaining: <?= max(0, 100 - $budget_pct) ?>,
  currentMood: '<?= $mood_data['mood'] ?>',
  month: '<?= $month ?>'
};
</script>
<script src="script.js"></script>
</body>
</html>
