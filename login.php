<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
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
                $error = 'Invalid password Buddy! Please try again.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BudgetSense</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-container">
        
        <div class="auth-info">
            <div class="brand">
                <i class="fas fa-bolt"></i> BUDGETSENSE
            </div>
            
            <div class="cartoon-box animate-pop">
                <img src="funny.jpg" alt="Funny Student Cartoon" class="cartoon-img">
            </div>

            <p class="cartoon-subtitle">Ready to see your mood stat? Dashboard is waiting.</p>
        </div>

        <div class="auth-form-card">
            <h2>Sign In</h2>
            
            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="student@university.edu" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">LOGIN NOW <i class="fas fa-arrow-right"></i></button>
            </form>

            <p class="switch-text">
                New to the squad? <a href="signup.php">Create Account Free</a>
            </p>

            <div class="demo-box">
                <p><strong>Demo Access:</strong></p>
                <p>demo@student.com | 123456</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>