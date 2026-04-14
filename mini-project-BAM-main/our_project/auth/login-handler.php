<?php
session_start();
require_once("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_errors = [];
    
    // VALIDATION
    if (empty($email)) {
        $login_errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $login_errors[] = "Password is required";
    }
    
    // If validation passes, check database
    if (empty($login_errors)) {
        $sql = "SELECT id, password, verified, status, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_info = mysqli_fetch_assoc($result);
        
        if (!$user_info) {
            $login_errors[] = "This email is not registered yet. Please register first";
        } else {
            // User exists, now check password
            if (!password_verify($password, $user_info['password'])) {
                $login_errors[] = "Incorrect password. Please try again";
            } elseif ($user_info['verified'] == 0) {
                $login_errors[] = "Please verify your email before logging in. Check your inbox";
            } elseif ($user_info['status'] != 'accepted') {
                if ($user_info['role'] == 'alumni') {
                    $login_errors[] = "Your account is pending admin approval";
                } else {
                    $login_errors[] = "Your account is not active. Contact support";
                }
            } else {
                // LOGIN SUCCESS
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_info['id'];
                $_SESSION['role'] = $user_info['role'];
                
                if ($user_info['role'] == 'student') {
                    header("Location: ../student/01-dashboard.php");
                    exit;
                } elseif ($user_info['role'] == 'alumni') {
                    header("Location: ../alumni/dashboard_alumni.php");
                    exit;
                } else {
                    header("Location: ../admin/dashboard.php");
                    exit;
                }
            }
        }
    }
    
    // If any errors, redirect back to login
    if (!empty($login_errors)) {
        $_SESSION['login_errors'] = $login_errors;
        header("Location: ../login.php");
        exit;
    }
}
?>