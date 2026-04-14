<?php
session_start();
require_once("config.php");
require 'reset-password-mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $get_password_errors = [];


    // VALIDATION
    if (empty($email)) {
        $get_password_errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $get_password_errors[] = "Invalid email format";
    }
    $sql = "SELECT id, first_name, password, verified, status, role FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connector, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_info = mysqli_fetch_assoc($result);

    if (!$user_info) {
        $get_password_errors[] = "This email is not registered yet. Please register first";
    } else {
        // User exists, now check password
        if ($user_info['verified'] == 0) {
            $get_password_errors[] = "Please verify your email before logging in. Check your inbox";
        } elseif ($user_info['status'] != 'accepted') {
            if ($user_info['role'] == 'alumni') {
                $get_password_errors[] = "Your account is pending admin approval";
            } else {
                $get_password_errors[] = "Your account is not active. Contact support";
            }
        }
    }

    if (!empty($get_password_errors)) {
        $_SESSION['get_password_errors'] = $get_password_errors;
        header("Location: ../forgot-password.php");
        exit;
    }

    $_SESSION['success_reset'] = "Please check your email for the password reset link";

    $reset_password_link = "../reset-password.php";
    send_verification_email($email, $user_info['first_name'], $reset_password_link);
    $_SESSION['reset_password_link'] = $reset_password_link;
    header("Location: ../forgot-password.php");
    exit;
}