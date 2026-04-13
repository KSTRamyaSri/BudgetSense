<?php
session_start();
require_once 'db.php'; // Ensure this file has your $conn and sanitize function

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['reset_email'] ?? '');
    $security_key = $_POST['security_key'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($email) || empty($security_key) || empty($new_password)) {
        header('Location: login.php?error=All fields are required for reset.');
        exit;
    }

    // 1. Check if the user exists and the security key matches
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND security_key = ?");
    $stmt->bind_param('ss', $email, $security_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 2. Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 3. Update the database
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param('ss', $hashed_password, $email);
        
        if ($update_stmt->execute()) {
            // Success! Redirect with a success message
            header('Location: login.php?success=Password updated! You can login now.');
        } else {
            header('Location: login.php?error=System error. Please try later.');
        }
    } else {
        // Security key or Email didn't match
        header('Location: login.php?error=Security key verification failed.');
    }
    exit;
}
?>