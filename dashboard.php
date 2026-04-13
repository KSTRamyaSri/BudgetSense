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

// --- DATABASE FETCHING ---
$mood_data = getMoodData($conn, $user_id, $month);
$mood_class = strtolower($mood_data['mood']); 
$budget_pct = min($mood_data['budget_usage_pct'], 100);
$savings = $mood_data['savings'];

$expenses = $conn->query("SELECT * FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");
$income_rows = $conn->query("SELECT * FROM income WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");

// Monthly Analytics (6 Months)
$monthly_labels = []; $monthly_amounts = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $monthly_labels[] = date('M', strtotime("-$i months"));
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$m'");
    $monthly_amounts[] = (float)$r->fetch_assoc()['t'];
}

// Category Analytics
$cat_data = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' GROUP BY category");
$cat_labels = []; $cat_amounts = [];
while($r = $cat_data->fetch_assoc()){
    $cat_labels[] = $r['category']; $cat_amounts[] = (float)$r['total'];
}

// Mood Analytics
$mood_counts = $conn->query("SELECT mood, COUNT(*) as count FROM mood_history WHERE user_id=$user_id GROUP BY mood");
$m_labels = []; $m_counts = [];
while($row = $mood_counts->fetch_assoc()){
    $m_labels[] = $row['mood']; $m_counts[] = $row['count'];
}

$initials = strtoupper(substr($user_name, 0, 1));
if (strpos($user_name, ' ') !== false) {
    $parts = explode(' ', $user_name);
    $initials = strtoupper($parts[0][0] . end($parts)[0]);
}
?>
<!DOCTYPE html>
<html lang="en" id="mainHtml" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BudgetSense Professional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { neon: '#39FF14', darkBg: '#080808', cardDark: '#121212' } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; transition: 0.3s; overflow-x: hidden; }
        #weather-layer { position: fixed; inset: 0; pointer-events: none; z-index: 1; overflow: hidden; }
        
        /* Particle Shower Logic */
        .rain { position: absolute; background: #64748b; width: 1.5px; height: 18px; animation: fall linear infinite; opacity: 0.4; top: -20px; }
        .shower-icon { position: absolute; animation: floatDown linear infinite; z-index: 1; top: -50px; }
        
        @keyframes fall { to { transform: translateY(110vh); } }
        @keyframes floatDown { 
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            100% { transform: translateY(110vh) rotate(360deg); opacity: 0; } 
        }
        
        .sidebar-active { background: #4f46e5 !important; color: white !important; }
        .dark .sidebar-active { background: #39FF14 !important; color: black !important; }
        .section { display: none; }
        .section.active { display: block; }
        .modal-overlay { background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); display: none; position: fixed; inset: 0; z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }

        .mood-box-container {
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.1);
        }

        .progress-bar { transition: width 1s ease-in-out; }
    </style>
</head>
<body class="flex min-h-screen bg-slate-50 dark:bg-darkBg text-slate-900 dark:text-zinc-100">

    <div id="weather-layer"></div>

    <aside class="w-64 bg-white dark:bg-cardDark border-r dark:border-zinc-800 hidden lg:flex flex-col sticky top-0 h-screen z-20">
        <div class="p-8">
            <div class="flex items-center gap-2 mb-10">
                <i class="fas fa-bolt text-indigo-600 dark:text-neon text-2xl"></i>
                <span class="text-xl font-black uppercase tracking-tighter italic">BudgetSense</span>
            </div>
            <nav class="space-y-2">
                <button onclick="switchTab('overview', this)" class="nav-btn sidebar-active w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition text-left">
                    <i class="fas fa-house"></i> Overview
                </button>
                <button onclick="switchTab('income', this)" class="nav-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 dark:text-zinc-400 transition hover:bg-slate-50 dark:hover:bg-zinc-800 text-left">
                    <i class="fas fa-wallet"></i> Income
                </button>
                <button onclick="switchTab('expenses', this)" class="nav-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 dark:text-zinc-400 transition hover:bg-slate-50 dark:hover:bg-zinc-800 text-left">
                    <i class="fas fa-receipt"></i> Expenses
                </button>
                <button onclick="switchTab('analytics', this)" class="nav-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 dark:text-zinc-400 transition hover:bg-slate-50 dark:hover:bg-zinc-800 text-left">
                    <i class="fas fa-chart-line"></i> Analytics
                </button>
                <button onclick="switchTab('budget', this)" class="nav-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 dark:text-zinc-400 transition hover:bg-slate-50 dark:hover:bg-zinc-800 text-left">
                    <i class="fas fa-sliders"></i> Settings
                </button>
            </nav>
        </div>
        <div class="mt-auto p-6 border-t dark:border-zinc-800">
            <button onclick="toggleDarkMode()" class="w-full mb-4 py-2 rounded-lg border dark:border-zinc-700 flex items-center justify-center gap-2 text-xs font-black uppercase tracking-widest">
                <i class="fas fa-moon"></i> Appearance
            </button>
            <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-zinc-900 rounded-2xl">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black" style="background:<?= $avatar_color ?>"><?= $initials ?></div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-[10px] font-black uppercase text-indigo-500 dark:text-neon tracking-widest"><?= strtoupper($mood_data['mood']) ?></p>
                    <p class="text-xs font-bold truncate"><?= $user_name ?></p>
                </div>
            </div>
            <a href="logout.php" class="block text-center mt-4 text-[10px] font-black uppercase tracking-[3px] text-slate-400 hover:text-red-500">Terminate</a>
        </div>
    </aside>

    <main class="flex-1 p-6 lg:p-10 z-10 relative overflow-y-auto">
        
        <div id="overview" class="section active space-y-10">
            <header class="flex justify-between items-center">
                <h1 class="text-3xl font-black italic uppercase">Workspace.</h1>
                <div class="flex gap-4">
                    <a href="export_csv.php?month=<?= $month ?>" class="bg-white dark:bg-zinc-800 px-4 py-2 rounded-xl border dark:border-zinc-700 text-xs font-black uppercase tracking-widest"><i class="fas fa-download mr-2"></i> CSV</a>
                    <form method="GET"><input type="month" name="month" value="<?= $month ?>" onchange="this.form.submit()" class="dark:bg-cardDark p-2 rounded-xl border dark:border-zinc-700 font-bold text-sm"></form>
                </div>
            </header>

            <div id="moodBox" class="mood-box-container p-10 md:p-12 rounded-[3rem] shadow-2xl flex flex-col justify-center text-left border-b-8 border-indigo-600 dark:border-neon min-h-[220px]">
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-transparent z-0"></div>
                
                <div class="z-10 relative max-w-2xl">
                    <span class="text-[10px] font-black uppercase tracking-[4px] text-indigo-300 dark:text-neon mb-3 block drop-shadow-md">Current System Status</span>
                    <h2 class="text-4xl md:text-5xl font-black uppercase italic mb-3 text-white tracking-tighter drop-shadow-lg"><?= $mood_data['mood'] ?> MODE.</h2>
                    <p class="text-white/90 text-lg font-semibold leading-relaxed drop-shadow-md max-w-xl"><?= $mood_data['mood_message'] ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-cardDark p-6 rounded-[2rem] shadow-sm border dark:border-zinc-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Inflow</p>
                        <h3 class="text-2xl font-black text-emerald-500">₹<?= number_format($mood_data['total_income'], 2) ?></h3>
                    </div>
                    <div class="bg-white dark:bg-cardDark p-6 rounded-[2rem] shadow-sm border dark:border-zinc-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Expenses</p>
                        <h3 class="text-2xl font-black text-red-500">₹<?= number_format($mood_data['total_expenses'], 2) ?></h3>
                    </div>
                    <div class="bg-white dark:bg-cardDark p-6 rounded-[2rem] shadow-sm border dark:border-zinc-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Savings</p>
                        <h3 class="text-2xl font-black <?= $savings < 0 ? 'text-red-600' : 'text-indigo-600 dark:text-neon' ?>">
                            <?= $savings < 0 ? '- ' : '' ?>₹<?= number_format(abs($savings), 2) ?>
                        </h3>
                    </div>
                    <div class="bg-white dark:bg-cardDark p-6 rounded-[2rem] shadow-sm border dark:border-zinc-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Budget Amount</p>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">₹<?= number_format($mood_data['budget_limit'], 2) ?></h3>
                    </div>
                </div>

                <div class="bg-white dark:bg-cardDark p-8 rounded-[3rem] shadow-sm border dark:border-zinc-800 flex flex-col justify-center">
                    <div class="flex justify-between items-end mb-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Monthly Spending Analysis</p>
                            <h4 class="text-xl font-black uppercase italic">Budget Usage</h4>
                        </div>
                        <span class="text-3xl font-black <?= $budget_pct >= 90 ? 'text-red-600' : 'text-indigo-600 dark:text-neon' ?>"><?= $budget_pct ?>%</span>
                    </div>
                    <div class="w-full h-6 bg-slate-100 dark:bg-zinc-800 rounded-full overflow-hidden p-1">
                        <div class="progress-bar h-full rounded-full <?= $budget_pct >= 90 ? 'bg-red-500 shadow-[0_0_15px_#ef4444]' : 'bg-indigo-600 dark:bg-neon shadow-[0_0_15px_rgba(57,255,20,0.5)]' ?>" style="width: <?= $budget_pct ?>%"></div>
                    </div>
                    <p class="mt-4 text-xs font-bold text-slate-400 italic">
                        <?= $budget_pct >= 100 ? "WARNING: Systems exceeded budget capacity!" : "Efficiency: Remaining within designated limits." ?>
                    </p>
                </div>
            </div>
        </div>

        <div id="income" class="section space-y-8">
            <div class="flex justify-between items-center">
                <h2 class="text-3xl font-black italic uppercase">Income Logs.</h2>
                <button onclick="openModal('incomeModal')" class="bg-slate-900 dark:bg-neon dark:text-black text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg">+ Add Entry</button>
            </div>
            <div class="bg-white dark:bg-cardDark rounded-[3rem] p-8 shadow-2xl border dark:border-zinc-800 overflow-hidden">
                <table class="w-full text-left">
                    <thead><tr class="text-[10px] font-black uppercase text-slate-400 border-b dark:border-zinc-800"><th class="pb-6 px-4">Source</th><th class="pb-6">Amount</th><th class="pb-6">Date</th><th class="pb-6">Note</th><th class="pb-6 text-right pr-4">Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $income_rows->fetch_assoc()): ?>
                        <tr class="border-b dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-900 transition">
                            <td class="py-6 px-4 font-bold"><?= htmlspecialchars($row['source']) ?></td>
                            <td class="py-6 text-emerald-500 font-black">+₹<?= number_format($row['amount'], 2) ?></td>
                            <td class="py-6 text-slate-400 text-sm"><?= $row['date'] ?></td>
                            <td class="py-6 text-slate-500 italic text-xs max-w-xs truncate"><?= htmlspecialchars($row['note'] ?? 'No notes') ?></td>
                            <td class="py-6 text-right pr-4"><button onclick="deleteRecord('income', <?= $row['id'] ?>)" class="text-slate-300 hover:text-red-500 transition"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="expenses" class="section space-y-8">
            <div class="flex justify-between items-center">
                <h2 class="text-3xl font-black italic uppercase">Expense Logs.</h2>
                <button onclick="openModal('expenseModal')" class="bg-slate-900 dark:bg-neon dark:text-black text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg">+ Add Entry</button>
            </div>
            <div class="bg-white dark:bg-cardDark rounded-[3rem] p-8 shadow-2xl border dark:border-zinc-800">
                <table class="w-full text-left">
                    <thead><tr class="text-[10px] font-black uppercase text-slate-400 border-b dark:border-zinc-800"><th class="pb-6 px-4">Category</th><th class="pb-6">Title</th><th class="pb-6">Amount</th><th class="pb-6">Date</th><th class="pb-6 text-right pr-4">Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $expenses->fetch_assoc()): ?>
                        <tr class="border-b dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-900 transition">
                            <td class="py-6 px-4"><span class="px-4 py-1 bg-slate-100 dark:bg-zinc-800 rounded-full text-[10px] font-black uppercase"><?= $row['category'] ?></span></td>
                            <td class="py-6 font-bold uppercase tracking-tight"><?= htmlspecialchars($row['title']) ?></td>
                            <td class="py-6 text-red-500 font-black">-₹<?= number_format($row['amount'], 2) ?></td>
                            <td class="py-6 text-slate-400 text-sm"><?= $row['date'] ?></td>
                            <td class="py-6 text-right pr-4"><button onclick="deleteRecord('expense', <?= $row['id'] ?>)" class="text-slate-300 hover:text-red-500 transition"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="analytics" class="section space-y-10">
            <h2 class="text-3xl font-black italic uppercase text-center">Financial Intelligence.</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white dark:bg-cardDark p-8 rounded-[3rem] shadow-xl border dark:border-zinc-800">
                    <h3 class="text-xs font-black uppercase mb-6 italic">Spending Categories</h3>
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="bg-white dark:bg-cardDark p-8 rounded-[3rem] shadow-xl border dark:border-zinc-800">
                    <h3 class="text-xs font-black uppercase mb-6 italic">Cashflow Dynamics</h3>
                    <canvas id="trendChart"></canvas>
                </div>
                <div class="bg-white dark:bg-cardDark p-8 rounded-[3rem] shadow-xl border dark:border-zinc-800 md:col-span-2">
                    <h3 class="text-xs font-black uppercase mb-6 italic text-center">Mood History Metrics</h3>
                    <div class="h-64 flex justify-center">
                        <canvas id="moodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div id="budget" class="section space-y-8">
             <h2 class="text-3xl font-black italic uppercase text-center">System Settings.</h2>
             <div class="max-w-xl mx-auto bg-white dark:bg-cardDark p-12 rounded-[4rem] shadow-2xl border dark:border-zinc-800 text-center">
                 <p class="text-sm font-bold text-slate-400 uppercase tracking-[4px] mb-8">Set Active System Limit</p>
                 <h3 class="text-6xl font-black mb-12 italic">₹<?= number_format($mood_data['budget_limit'], 0) ?></h3>
                 <form id="budgetSetForm" class="space-y-6">
                     <input type="number" id="newBudgetAmount" placeholder="New Limit (₹)" class="w-full p-5 bg-slate-50 dark:bg-zinc-900 rounded-3xl outline-none border dark:border-zinc-800 text-center text-3xl font-black">
                     <button type="submit" class="w-full py-5 bg-indigo-600 dark:bg-neon dark:text-black text-white rounded-3xl font-black text-xs uppercase tracking-[5px] hover:shadow-2xl transition active:scale-95">Update Configuration</button>
                 </form>
             </div>
        </div>

    </main>

    <div class="modal-overlay" id="incomeModal">
        <div class="bg-white dark:bg-cardDark w-full max-w-md p-10 rounded-[3.5rem] relative shadow-2xl">
            <button onclick="closeModal('incomeModal')" class="absolute top-8 right-8 text-slate-300"><i class="fas fa-times text-xl"></i></button>
            <h3 class="text-2xl font-black uppercase italic mb-8">Add Inflow.</h3>
            <form id="incomeForm" class="space-y-4">
                <input type="text" id="income_source" placeholder="Source (e.g. Salary)" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <input type="number" id="income_amount" placeholder="Amount (₹)" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <input type="date" id="income_date" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <textarea id="income_note" placeholder="Note (Optional)" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" rows="2"></textarea>
                <button type="submit" class="w-full py-5 bg-slate-900 dark:bg-neon dark:text-black text-white rounded-2xl font-black uppercase tracking-widest mt-4">Confirm Entry</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="expenseModal">
        <div class="bg-white dark:bg-cardDark w-full max-w-md p-10 rounded-[3.5rem] relative shadow-2xl">
            <button onclick="closeModal('expenseModal')" class="absolute top-8 right-8 text-slate-300"><i class="fas fa-times text-xl"></i></button>
            <h3 class="text-2xl font-black uppercase italic mb-8">Add Outflow.</h3>
            <form id="expenseForm" class="space-y-4">
                <input type="text" id="expense_title" placeholder="Description" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <select id="expense_category" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none">
                    <option>Food</option><option>Travel</option><option>Books</option><option>Entertainment</option><option>Shopping</option><option>Other</option>
                </select>
                <input type="number" id="expense_amount" placeholder="Amount (₹)" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <input type="date" id="expense_date" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" required>
                <textarea id="expense_note" placeholder="Note (Optional)" class="w-full p-4 bg-slate-50 dark:bg-zinc-900 rounded-2xl outline-none" rows="2"></textarea>
                <button type="submit" class="w-full py-5 bg-slate-900 dark:bg-neon dark:text-black text-white rounded-2xl font-black uppercase tracking-widest mt-4">Confirm Entry</button>
            </form>
        </div>
    </div>

    <script>
        const MOOD = "<?= $mood_class ?>";
        let isDark = localStorage.getItem('darkMode') === 'true';

        function applyTheme() {
            const html = document.getElementById('mainHtml');
            isDark ? html.classList.add('dark') : html.classList.remove('dark');
            const mb = document.getElementById('moodBox');

            // BOX BACKGROUND GIFS
            if(MOOD === 'sad') {
                mb.style.backgroundImage = "url('assets/sad.gif')";
            } else if (MOOD === 'neutral') {
                mb.style.backgroundImage = "url('assets/neutral.gif')";
            } else {
                mb.style.backgroundImage = isDark ? "url('assets/night sky GIF.gif')" : "url('assets/happy.gif')";
            }
            initWeather();
        }

        function toggleDarkMode() { isDark = !isDark; localStorage.setItem('darkMode', isDark); applyTheme(); }

        // --- DYNAMIC SHOWER ---
        function initWeather() {
            const layer = document.getElementById('weather-layer');
            layer.innerHTML = '';
            
            setInterval(() => {
                const p = document.createElement(MOOD === 'sad' ? 'div' : 'i');
                if(MOOD === 'sad') {
                    p.className = 'rain';
                } else if(MOOD === 'happy') {
                    const icons = isDark ? 
                        [{i:'fa-moon', c:'text-slate-100'}, {i:'fa-star', c:'text-yellow-200'}] : 
                        [{i:'fa-sun', c:'text-orange-400'}, {i:'fa-spa', c:'text-pink-400'}];
                    const choice = icons[Math.floor(Math.random()*icons.length)];
                    p.className = `fas ${choice.i} ${choice.c} shower-icon`;
                    p.style.fontSize = Math.random()*20+10+'px';
                } else { return; }

                p.style.left = Math.random()*100+'vw';
                p.style.animationDuration = Math.random()*3+2+'s';
                layer.appendChild(p);
                setTimeout(() => p.remove(), 5000);
            }, MOOD === 'sad' ? 100 : 300);
        }

        function switchTab(id, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(n => n.classList.remove('sidebar-active'));
            document.getElementById(id).classList.add('active');
            btn.classList.add('sidebar-active');
        }

        function openModal(id) { document.getElementById(id).classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); }

        async function handleForm(url, data) {
            const res = await fetch(url, { method: 'POST', body: JSON.stringify(data), headers: {'Content-Type': 'application/json'}});
            const json = await res.json();
            if(json.success) location.reload();
        }

        document.getElementById('incomeForm').onsubmit = e => {
            e.preventDefault();
            handleForm('add_income.php', { source: document.getElementById('income_source').value, amount: document.getElementById('income_amount').value, date: document.getElementById('income_date').value, note: document.getElementById('income_note').value });
        }
        document.getElementById('expenseForm').onsubmit = e => {
            e.preventDefault();
            handleForm('add_expense.php', { title: document.getElementById('expense_title').value, category: document.getElementById('expense_category').value, amount: document.getElementById('expense_amount').value, date: document.getElementById('expense_date').value, note: document.getElementById('expense_note').value });
        }
        document.getElementById('budgetSetForm').onsubmit = e => {
            e.preventDefault();
            handleForm('update_budget.php', { amount: document.getElementById('newBudgetAmount').value, month: "<?= $month ?>" });
        }
        function deleteRecord(type, id) { if(confirm('Delete permanently?')) handleForm('delete_expense.php', {type, id}); }

        // --- CHARTS ---
        const colors = ['#39FF14', '#4f46e5', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899'];
        new Chart(document.getElementById('categoryChart'), { type: 'doughnut', data: { labels: <?= json_encode($cat_labels) ?>, datasets: [{ data: <?= json_encode($cat_amounts) ?>, backgroundColor: colors, borderColor: 'transparent' }] }, options: { plugins: { legend: { position: 'right', labels: { color: isDark ? '#fff' : '#000' } } } } });
        new Chart(document.getElementById('trendChart'), { type: 'bar', data: { labels: <?= json_encode($monthly_labels) ?>, datasets: [{ label: 'Cash Outflow', data: <?= json_encode($monthly_amounts) ?>, backgroundColor: isDark ? '#39FF14' : '#4f46e5', borderRadius: 10 }] }, options: { plugins: { legend: { labels: { color: isDark ? '#fff' : '#000' } } } } });
        new Chart(document.getElementById('moodChart'), { type: 'pie', data: { labels: <?= json_encode($m_labels) ?>, datasets: [{ data: <?= json_encode($m_counts) ?>, backgroundColor: ['#39FF14','#4f46e5','#ef4444'] }] }, options: { plugins: { legend: { labels: { color: isDark ? '#fff' : '#000' } } } } });

        applyTheme();
    </script>
</body>
</html>