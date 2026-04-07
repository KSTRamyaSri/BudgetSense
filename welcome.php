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
    <title>Welcome | BudgetSense</title>
    <link rel="stylesheet" href="styles/welcome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<section class="hero-wrapper">
    <div class="container">
        <nav class="nb-nav">
            <div class="logo"><i class="fas fa-bolt"></i> BUDGETSENSE</div>
            <div class="nav-links">
                <a href="login.php" class="link-item">LOGIN</a>
                <a href="signup.php" class="btn-cta-sm">JOIN NOW</a>
            </div>
        </nav>

        <header class="nb-hero">
            <div class="hero-text">
                <span class="badge">#STUDENT_FINANCE_2026</span>
                <h1>TRACK YOUR <span class="outline-text">CASH</span></h1>
                <p>Stop wondering where your money went. Take control of your allowance with our smart student dashboard.</p>
                <div class="hero-btns">
                    <a href="signup.php" class="btn-main">GET STARTED <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-img-container">
                <img src="assets/hero-char.jpg" alt="Mascot" class="floating-img">
            </div>
        </header>
    </div>
</section>

<div class="ticker-wrap">
    <div class="ticker">
        <div class="ticker-item">NO MORE OVERSPENDING 💸</div>
        <div class="ticker-item">SMART SAVINGS START HERE 📈</div>
        <div class="ticker-item">LOG YOUR INCOME 💵</div>
        <div class="ticker-item">TRACK EVERY RUPEE 🪙</div>
        <div class="ticker-item">STAY IN A HAPPY MOOD 😊</div>
        <div class="ticker-item">NO MORE OVERSPENDING 💸</div>
        <div class="ticker-item">SMART SAVINGS START HERE 📈</div>
    </div>
</div>

<section class="workflow-wrapper">
    <div class="container">
        <h2 class="section-title">THE STUDENT WORKFLOW</h2>
        <div class="grid-layout">
            
            <div class="nb-card yellow animate-card">
                <div class="step-num">01</div>
                <div class="icon-box"><i class="fas fa-wallet"></i></div>
                <h3>Income</h3>
                <p>Record your monthly allowance or earnings first. Know your starting point.</p>
            </div>

            <div class="nb-card pink animate-card" style="animation-delay: 0.2s;">
                <div class="step-num">02</div>
                <div class="icon-box"><i class="fas fa-bullseye"></i></div>
                <h3>Budget</h3>
                <p>Set a realistic spending limit. Challenge yourself to save at least 20%.</p>
            </div>

            <div class="nb-card green animate-card" style="animation-delay: 0.4s;">
                <div class="step-num">03</div>
                <div class="icon-box"><i class="fas fa-utensils"></i></div>
                <h3>Expenses</h3>
                <p>Log your daily spends on food, travel, and fun. Watch your mood update!</p>
            </div>

        </div>
    </div>
</section>

<footer class="nb-footer">
    <div class="container">
        <p>BUDGETSENSE &copy; 2026 | BUILT FOR FUTURE MILLIONAIRES </p>
    </div>
</footer>

</body>
</html>