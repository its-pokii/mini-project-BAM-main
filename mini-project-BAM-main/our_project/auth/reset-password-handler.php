<?php
session_start();
require_once("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    $reset_password_errors = [];

    if (empty($new_password)) {
        $reset_password_errors[] = "Password is required";
    } else if (strlen($new_password) < 8) {
        $reset_password_errors[] = "Password must be at least 8 characters";
    }
    if (empty($confirm_new_password)) {
        $reset_password_errors[] = "Please confirm your password.";
    } else if ($confirm_new_password != $new_password) {
        $reset_password_errors[] = "Passwords don't match. Please retry!";
    }

    // ====CHECKING ERR====
    if (!empty($reset_password_errors)) {
        $_SESSION['reset_password_errors'] = $reset_password_errors;
        $_SESSION['user_input'] = $_POST;
        header("Location: ../register.php");
        exit;
    }

    // ====HASH NEW PASSWORD====
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // ====INSERT INFO INTO USERS TABLE====
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($connector, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $new_password, $email);
    mysqli_stmt_execute($stmt);
    
    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['reset_password_errors'] = ["Registration failed. Please try again."];
        header("Location: ../register.php");
        exit;
    }

    header("Location: ../login.php");
    exit;
}