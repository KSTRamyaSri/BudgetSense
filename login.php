<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once 'db.php';

$error = '';
$success = '';

// Check for messages passed from reset_logic.php
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Credentials are required for system access.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, avatar_color FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['avatar_color'] = $user['avatar_color'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Security violation: Incorrect password.';
            }
        } else {
            $error = 'Authentication failed: Account not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BudgetSense Professional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; min-height: 100vh; }
        .ticker-scroll { animation: scroll 35s linear infinite; }
        @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .side-panel { background: linear-gradient(145deg, #4f46e5, #7c3aed); }
        .modal-overlay { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: none; position: fixed; inset: 0; z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-4 pt-24 pb-12">

    <div class="fixed top-0 w-full bg-white border-b border-slate-100 h-10 flex items-center overflow-hidden z-50">
        <div class="flex ticker-scroll whitespace-nowrap text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <span class="px-10"><i class="fas fa-shield-halved text-indigo-500 mr-2"></i> Encrypted Session</span>
            <span class="px-10"><i class="fas fa-circle-nodes text-indigo-500 mr-2"></i> System Status: Online</span>
            <span class="px-10"><i class="fas fa-bolt text-indigo-500 mr-2"></i> Analytics Engine Ready</span>
            <span class="px-10"><i class="fas fa-shield-halved text-indigo-500 mr-2"></i> Encrypted Session</span>
            <span class="px-10"><i class="fas fa-circle-nodes text-indigo-500 mr-2"></i> System Status: Online</span>
        </div>
    </div>

    <div class="fixed top-14 right-6 z-40">
        <a href="index.php" class="bg-white px-5 py-2 rounded-full border border-slate-200 text-[11px] font-bold text-slate-500 hover:text-indigo-600 transition flex items-center gap-2 shadow-sm">
            EXPLORE HOME <i class="fas fa-arrow-right-long"></i>
        </a>
    </div>

    <div class="w-full max-w-3xl bg-white rounded-[2rem] overflow-hidden flex flex-col md:flex-row shadow-2xl shadow-slate-200/50 my-auto">
        
        <div class="md:w-5/12 side-panel p-8 text-white flex flex-col justify-between relative">
            <div class="z-10">
                <div class="flex items-center gap-2 mb-8">
                    <i class="fas fa-chart-pie text-xl"></i>
                    <span class="text-sm font-black uppercase tracking-[3px]">BudgetSense</span>
                </div>
                <h2 class="text-2xl font-black leading-tight mb-2">ACCESS<br>PORTAL.</h2>
                <p class="text-indigo-100 text-[11px] font-medium opacity-70">Monitor your assets securely.</p>
            </div>
            
            <div class="mt-6 flex justify-center md:block">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/secure-login-5381017.png" class="w-24 md:w-36 opacity-90 drop-shadow-xl mx-auto md:mx-0">
            </div>
        </div>

        <div class="md:w-7/12 p-8 md:p-12 flex flex-col justify-center bg-white">
            <header class="mb-8">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Sign In</h1>
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

            <form method="POST" action="" class="space-y-5">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 block">Account Email</label>
                    <input type="email" name="email" required 
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium"
                        placeholder="e.g. name@student.com">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 block">Password</label>
                    <input type="password" name="password" required 
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition text-sm text-slate-700 font-medium"
                        placeholder="Enter your password">
                </div>

                <div class="flex justify-end">
                    <button type="button" onclick="toggleModal()" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition">Forgot Security Key?</button>
                </div>

                <button type="submit" name="login_submit" class="w-full py-4 bg-slate-900 text-white rounded-xl font-bold text-[11px] uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                    Authorize Account
                </button>
            </form>

            <p class="mt-8 text-center text-[11px] text-slate-400 font-bold uppercase tracking-widest">
                New User? <a href="signup.php" class="text-indigo-600 ml-1 hover:underline">Create Account</a>
            </p>
        </div>
    </div>

    <div class="mt-8 opacity-20">
        <p class="text-[9px] font-black uppercase tracking-[5px]">Core Systems v2.6.0</p>
    </div>

    <div id="forgotModal" class="modal-overlay p-4">
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600">
                <i class="fas fa-times text-lg"></i>
            </button>
            <h3 class="text-xl font-black text-slate-900 mb-2 uppercase tracking-tight">Reset Request</h3>
            <p class="text-slate-400 text-[10px] font-bold mb-8 uppercase tracking-widest">Identify via Security Key</p>
            
            <form action="reset_logic.php" method="POST" class="space-y-4">
                <input type="email" name="reset_email" placeholder="Registered Email" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-indigo-500 font-medium">
                <input type="text" name="security_key" placeholder="Unique Security Key" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-indigo-500 font-medium">
                <input type="password" name="new_password" placeholder="New Password" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-xl text-sm outline-none focus:border-indigo-500 font-medium">
                
                <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-xl font-bold text-[11px] uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg mt-2">
                    Update Credentials
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.toggle('active');
        }
    </script>

</body>
</html>