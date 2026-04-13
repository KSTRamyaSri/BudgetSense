<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $s_key = sanitize($conn, $_POST['security_key'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($s_key)) {
        $error = 'All fields are mandatory for registration.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid institutional email.';
    } elseif (strlen($password) < 6) {
        $error = 'Security: Password must be 6+ characters.';
    } elseif ($password !== $confirm) {
        $error = 'Mismatch: Passwords do not match.';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = 'This email is already linked to an account.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $colors = ['#4F46E5','#06B6D4','#22C55E','#F59E0B','#EC4899','#8B5CF6'];
            $color = $colors[array_rand($colors)];
            
            // Added security_key to the insertion
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, avatar_color, security_key) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $email, $hashed, $color, $s_key);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $month = date('Y-m');
                $conn->query("INSERT INTO budget_limits (user_id, limit_amount, month) VALUES ($user_id, 5000, '$month')");
                $success = 'Account verified! Redirecting to login...';
                echo "<script>setTimeout(()=>{window.location='login.php'},1500)</script>";
            } else {
                $error = 'System error: Registration failed.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join BudgetSense | Smart Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; min-height: 100vh; }
        .ticker-scroll { animation: scroll 35s linear infinite; }
        @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .side-panel { background: linear-gradient(145deg, #4f46e5, #7c3aed); }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-4 pt-24 pb-12">

    <div class="fixed top-0 w-full bg-white border-b border-slate-100 h-10 flex items-center overflow-hidden z-50">
        <div class="flex ticker-scroll whitespace-nowrap text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <span class="px-10"><i class="fas fa-user-plus text-indigo-500 mr-2"></i> New User Enrollment Active</span>
            <span class="px-10"><i class="fas fa-shield-check text-indigo-500 mr-2"></i> Security Key Required</span>
            <span class="px-10"><i class="fas fa-database text-indigo-500 mr-2"></i> Cloud Sync Enabled</span>
            <span class="px-10"><i class="fas fa-user-plus text-indigo-500 mr-2"></i> New User Enrollment Active</span>
        </div>
    </div>

    <div class="fixed top-14 right-6 z-40">
        <a href="index.php" class="bg-white px-5 py-2 rounded-full border border-slate-200 text-[11px] font-bold text-slate-500 hover:text-indigo-600 transition flex items-center gap-2 shadow-sm">
            EXPLORE HOME <i class="fas fa-arrow-right-long"></i>
        </a>
    </div>

    <div class="w-full max-w-4xl bg-white rounded-[2rem] overflow-hidden flex flex-col md:flex-row shadow-2xl shadow-slate-200/50 my-auto">
        
        <div class="md:w-5/12 side-panel p-8 md:p-12 text-white flex flex-col justify-between relative">
            <div class="z-10">
                <div class="flex items-center gap-2 mb-8">
                    <i class="fas fa-chart-pie text-xl"></i>
                    <span class="text-sm font-black uppercase tracking-[3px]">BudgetSense</span>
                </div>
                <h2 class="text-3xl font-black leading-tight mb-4 uppercase">Join The<br>Squad.</h2>
                <div class="space-y-3 mt-8">
                    <div class="flex items-center gap-3 text-xs font-bold opacity-80 uppercase tracking-wider">
                        <i class="fas fa-check-circle"></i> Daily Tracking
                    </div>
                    <div class="flex items-center gap-3 text-xs font-bold opacity-80 uppercase tracking-wider">
                        <i class="fas fa-check-circle"></i> Smart Insights
                    </div>
                    <div class="flex items-center gap-3 text-xs font-bold opacity-80 uppercase tracking-wider">
                        <i class="fas fa-check-circle"></i> Mood Analysis
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-center md:block">
                <img src="assets/welcome1.jpg" class="w-24 md:w-44 opacity-90 drop-shadow-xl mx-auto md:mx-0">
            </div>
        </div>

        <div class="md:w-7/12 p-8 md:p-12 flex flex-col justify-center bg-white">
            <header class="mb-8">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Register</h1>
                <div class="h-1 w-6 bg-indigo-600 rounded-full mt-1"></div>
            </header>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-600 text-red-700 text-[11px] font-bold flex items-center gap-3">
                    <i class="fas fa-circle-exclamation text-sm"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-700 text-[11px] font-bold flex items-center gap-3">
                    <i class="fas fa-circle-check text-sm"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. John"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium">
                </div>

                <div class="md:col-span-2 space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                    <input type="email" name="email" required placeholder="name@domain.com"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Confirm</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium">
                </div>

                <div class="md:col-span-2 space-y-1.5">
                    <label class="text-[10px] font-black text-indigo-600 uppercase tracking-widest ml-1">Security Key (For Reset)</label>
                    <input type="text" name="security_key" required placeholder="Ex: MyDogMax2026"
                        class="w-full px-5 py-3 bg-indigo-50/30 border border-indigo-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-bold">
                </div>

                <button type="submit" class="md:col-span-2 mt-4 py-4 bg-slate-900 text-white rounded-xl font-bold text-[11px] uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                    Create Member Account
                </button>
            </form>

            <p class="mt-8 text-center text-[11px] text-slate-400 font-bold uppercase tracking-widest">
                Already registered? <a href="login.php" class="text-indigo-600 ml-1 hover:underline">Login here</a>
            </p>
        </div>
    </div>

    <div class="mt-8 opacity-20">
        <p class="text-[9px] font-black uppercase tracking-[5px]">Core Systems v2.6.0</p>
    </div>

</body>
</html>