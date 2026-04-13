<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetSense | Professional Finance Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        /* Professional Scrolling Ticker */
        .ticker-container {
            overflow: hidden;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 0;
        }

        .ticker-wrapper {
            display: flex;
            white-space: nowrap;
            animation: scroll 30s linear infinite;
        }

        .ticker-item {
            display: flex;
            align-items: center;
            padding: 0 40px;
            color: #64748b;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticker-item i { margin-right: 10px; color: #6366f1; }

        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .card-hover:hover {
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="text-slate-800">

    <nav class="glass-nav fixed w-full z-50 top-0 left-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-2 rounded-lg text-white">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">BUDGET<span class="text-indigo-600">SENSE</span></span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="login.php" class="font-semibold text-slate-600 hover:text-indigo-600 transition">Login</a>
                    <a href="signup.php" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-semibold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Get Started</a>
                </div>
                <div class="md:hidden">
                    <a href="login.php" class="text-indigo-600 font-bold">LOGIN</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2 text-center lg:text-left">
                <span class="inline-block py-1 px-3 rounded-full bg-indigo-50 text-indigo-600 text-sm font-bold mb-6">
                    FINANCE MANAGEMENT 2026
                </span>
                <h1 class="text-5xl lg:text-7xl font-extrabold leading-tight mb-6">
                    Master your <span class="gradient-text">Capital</span> with Precision.
                </h1>
                <p class="text-lg text-slate-500 mb-10 max-w-lg mx-auto lg:mx-0">
                    A professional-grade dashboard designed for students and professionals to track every rupee, set goals, and gain financial freedom.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="signup.php" class="bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 transition text-center shadow-xl">
                        Create Free Account
                    </a>
                    <a href="#" class="flex items-center justify-center gap-2 px-8 py-4 font-semibold text-slate-600 hover:text-indigo-600 transition">
                        <i class="fas fa-play-circle text-2xl"></i> View Demo
                    </a>
                </div>
            </div>
            <div class="lg:w-1/2 mt-16 lg:mt-0 px-4">
                <div class="relative">
                    <div class="absolute -top-10 -left-10 w-64 h-64 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
                    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
                    <img src="assets/welcome1.jpg" alt="Professional Finance" class="relative z-10 w-full max-w-md mx-auto">
                </div>
            </div>
        </div>
    </section>

    <div class="ticker-container">
        <div class="ticker-wrapper">
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Real-time Expense Tracking</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Automated Budget Alerts</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Secure Data Encryption</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Financial Insight Reports</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Multi-device Sync</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Real-time Expense Tracking</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Automated Budget Alerts</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Secure Data Encryption</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Financial Insight Reports</div>
            <div class="ticker-item"><i class="fas fa-check-circle"></i> Multi-device Sync</div>
        </div>
    </div>

    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">The BudgetSense Workflow</h2>
                <div class="w-20 h-1 bg-indigo-600 mx-auto rounded-full"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 card-hover">
                    <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 text-2xl mb-6">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Asset Income</h3>
                    <p class="text-slate-500 leading-relaxed">Systematically record your monthly allowance or earnings. Establish your financial baseline.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 card-hover">
                    <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-2xl mb-6">
                        <i class="fas fa-crosshairs"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Budget Planning</h3>
                    <p class="text-slate-500 leading-relaxed">Define smart spending thresholds. Optimize your resources and target a 20% savings ratio.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 card-hover">
                    <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl mb-6">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Expense Analytics</h3>
                    <p class="text-slate-500 leading-relaxed">Monitor daily outflows across categories. Visualize your habits through professional analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white py-12 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-slate-400 font-medium tracking-wide text-sm">
                &copy; 2026 BUDGETSENSE CORE SYSTEMS. ALL RIGHTS RESERVED.
            </p>
        </div>
    </footer>

</body>
</html>