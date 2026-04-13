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
    <title>BudgetSense | Precision Finance Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; overflow-x: hidden; }
        
        /* 1. HERO ORGANIC SHAPES */
        .hero-bg-shape {
            position: absolute;
            top: 0;
            right: 0;
            width: 60%;
            height: 100%;
            background: #f8fafc;
            clip-path: polygon(25% 0%, 100% 0%, 100% 100%, 0% 100%);
            z-index: 0;
        }

        .hero-curve {
            position: absolute;
            top: 15%;
            right: -5%;
            width: 55%;
            height: 75%;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border-radius: 45% 55% 40% 60% / 60% 40% 60% 40%;
            opacity: 0.12;
            animation: morph 12s ease-in-out infinite;
            z-index: 0;
        }

        /* 2. IMAGE MASK (Fixed Size) */
        .image-mask {
            border-radius: 48% 52% 68% 32% / 43% 46% 54% 57%;
            overflow: hidden;
            border: 10px solid white;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.15);
            background: white;
            width: 320px; /* Kept fixed as requested */
            height: 440px;
        }

        @keyframes morph {
            0% { border-radius: 45% 55% 40% 60% / 60% 40% 60% 40%; transform: scale(1); }
            50% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; transform: scale(1.05); }
            100% { border-radius: 45% 55% 40% 60% / 60% 40% 60% 40%; transform: scale(1); }
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0px); } }

        /* 3. VARIETY WORKFLOW BOXES (Vibrant Design) */
        .wf-box {
            position: relative;
            padding: 3rem 2rem;
            border-radius: 2.5rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: white;
            border: 1px solid #f1f5f9;
        }
        .wf-box:hover { transform: translateY(-15px); }

        .wf-box-1:hover { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; }
        .wf-box-2:hover { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white; }
        .wf-box-3:hover { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }

        .wf-icon {
            width: 60px;
            height: 60px;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            transition: 0.5s;
        }
        .wf-box:hover .wf-icon { background: rgba(255,255,255,0.2); color: white; transform: rotate(10deg); }

        .wf-box-1 .wf-icon { background: #eef2ff; color: #6366f1; }
        .wf-box-2 .wf-icon { background: #f5f3ff; color: #a855f7; }
        .wf-box-3 .wf-icon { background: #ecfdf5; color: #10b981; }

        .wf-box p { transition: 0.5s; }
        .wf-box:hover p { color: rgba(255,255,255,0.8); }

    </style>
</head>
<body class="text-slate-800">

    <nav class="fixed w-full z-50 top-0 left-0 bg-white/80 backdrop-blur-md border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 p-2 rounded-xl text-white shadow-lg shadow-indigo-200">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <span class="text-xl font-black tracking-tighter text-slate-900 uppercase">BudgetSense</span>
                </div>
                <div class="hidden md:flex items-center space-x-10">
                    <a href="#about" class="text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition">About</a>
                    <a href="login.php" class="text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition">Login</a>
                    <a href="signup.php" class="bg-indigo-600 text-white px-7 py-3 rounded-full text-[11px] font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-xl shadow-indigo-100">Initialize Account</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden bg-white">
        <div class="hero-bg-shape"></div>
        <div class="hero-curve"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                
                <div class="lg:w-1/2 text-center lg:text-left">
                    <div class="w-16 h-1.5 bg-indigo-600 mb-8 mx-auto lg:mx-0 rounded-full"></div>
                    <h4 class="text-indigo-600 font-black text-xs uppercase tracking-[5px] mb-4">Core Management</h4>
                    <h1 class="text-6xl lg:text-8xl font-black leading-[0.95] mb-8 tracking-tighter uppercase text-slate-900">
                        Expense <br><span class="text-indigo-600 italic">Tracker.</span>
                    </h1>
                    <p class="text-lg text-slate-500 mb-10 max-w-lg mx-auto lg:mx-0 font-medium">
                        Monitor daily outflows, configure smart limits, and maintain a happy financial sentiment with our high-end encrypted dashboard.
                    </p>
                    <div class="flex justify-center lg:justify-start">
                        <a href="signup.php" class="bg-slate-900 text-white px-10 py-5 rounded-full text-xs font-black uppercase tracking-[3px] hover:bg-indigo-600 transition shadow-2xl">
                            Explore Portal
                        </a>
                    </div>
                </div>

                <div class="lg:w-1/2 flex justify-center relative">
                    <div class="relative animate-float">
                        <div class="image-mask">
                            <img src="assets/welcome2.png" alt="Interface" class="w-full h-full object-cover">
                        </div>
                        <div class="absolute -top-6 -right-6 bg-white p-5 rounded-[2rem] shadow-2xl hidden md:block">
                            <i class="fas fa-shield-halved text-indigo-600 text-3xl"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="about" class="py-32 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center gap-20">
            <div class="md:w-1/4 relative"> <div class="absolute inset-0 bg-indigo-50 rounded-[3rem] transform rotate-6 scale-105 z-0"></div>
                <img src="assets/welcome1.png" alt="About" class="relative z-10 w-full max-w-[240px] mx-auto drop-shadow-2xl rounded-[2.5rem]">
            </div>
            <div class="md:w-3/4 text-center md:text-left">
                <h4 class="text-indigo-600 font-black text-[10px] uppercase tracking-[5px] mb-4">Architecture</h4>
                <h3 class="text-4xl lg:text-5xl font-black text-slate-900 mb-8 italic uppercase tracking-tight">Engineered for Precision.</h3>
                <p class="text-lg text-slate-500 leading-relaxed mb-10 font-medium">
                    BudgetSense provides a strictly professional workspace to monitor your financial health. By focusing on data integrity and clean visualizations, we ensure you stay ahead of your spending habits.
                </p>
                <div class="flex flex-wrap gap-12 justify-center md:justify-start">
                    <div><h5 class="text-3xl font-black text-slate-900">256-Bit</h5><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">SSL Security</p></div>
                    <div><h5 class="text-3xl font-black text-indigo-600">Active</h5><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Cloud Servers</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-32 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center mb-20">
            <h2 class="text-4xl font-black text-slate-900 italic uppercase">System Workflow.</h2>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="wf-box wf-box-1 shadow-sm">
                <div class="wf-icon"><i class="fas fa-database"></i></div>
                <h3 class="text-xl font-black uppercase mb-4 tracking-tight">Asset Input</h3>
                <p class="text-slate-500 font-medium leading-relaxed">Securely log your monthly income and allowances to establish a comprehensive financial baseline.</p>
            </div>

            <div class="wf-box wf-box-2 shadow-sm">
                <div class="wf-icon"><i class="fas fa-sliders"></i></div>
                <h3 class="text-xl font-black uppercase mb-4 tracking-tight">Limit Config</h3>
                <p class="text-slate-500 font-medium leading-relaxed">Configure smart budget limits and savings goals tailored to your monthly operational requirements.</p>
            </div>

            <div class="wf-box wf-box-3 shadow-sm">
                <div class="wf-icon"><i class="fas fa-chart-line"></i></div>
                <h3 class="text-xl font-black uppercase mb-4 tracking-tight">Habit Intel</h3>
                <p class="text-slate-500 font-medium leading-relaxed">Leverage professional-grade analytics to visualize spending patterns and optimize your trajectory.</p>
            </div>
        </div>
    </section>

    <footer class="bg-white py-16 border-t border-slate-100 text-center">
        <p class="text-slate-400 font-black tracking-[4px] uppercase text-[9px]">
            &copy; 2026 Core Systems. Engineered for Tulasi.
        </p>
    </footer>

</body>
</html>