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

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = 'Email already registered. Please login.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $colors = ['#4F46E5','#06B6D4','#22C55E','#F59E0B','#EC4899','#8B5CF6'];
            $color = $colors[array_rand($colors)];
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, avatar_color) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $hashed, $color);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                // Set default budget for current month
                $month = date('Y-m');
                $conn->query("INSERT INTO budget_limits (user_id, limit_amount, month) VALUES ($user_id, 5000, '$month')");
                $success = 'Account created! Redirecting...';
                echo "<script>setTimeout(()=>{window.location='login.php'},1500)</script>";
            } else {
                $error = 'Registration failed. Try again.';
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
    <title>Join BudgetSense | Smart Student Budgeting</title>
    <link rel="stylesheet" href="signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-info">
            <div class="brand">
                <i class="fas fa-bolt"></i> BUDGETSENSE
            </div>
            <h1>JOIN THE <span class="outline-text">SQUAD.</span></h1>
            <p>Start tracking your pocket money like a pro. Set goals, beat your budget, and keep your mood happy! 😊</p>
            
            <div class="mini-workflow">
                <div class="mini-step"><i class="fas fa-check-circle"></i> Daily Tracking</div>
                <div class="mini-step"><i class="fas fa-check-circle"></i> Smart Insights</div>
                <div class="mini-step"><i class="fas fa-check-circle"></i> Mood Analysis</div>
            </div>
        </div>

        <div class="auth-form-card">
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Ex: Ramya Sri" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="student@college.com" required>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Confirm</label>
                        <div class="input-wrapper">
                            <i class="fas fa-shield-alt"></i>
                            <input type="password" name="confirm_password" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-signup">CREATE ACCOUNT <i class="fas fa-arrow-right"></i></button>
            </form>

            <p class="switch-text">
                Already part of the squad? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>