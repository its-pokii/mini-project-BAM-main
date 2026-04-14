<?php
include("../includes/headers/free-header.php");
include("../includes/script-var.php");
session_start();
require_once("config.php"); // DB connection

// Get token from URL
$token = $_GET['token'] ?? '';

if (!$token) {
    echo "<p style='color: red;'>Invalid or expired verification link!</p>";
}
$message = "This window is expired. You can close it!";
// Look for the user with this token
$sql = "SELECT id, email, verified FROM users WHERE verification_token = ?";
$stmt = mysqli_prepare($connector, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "<p class='text-center font-bold text-2xl' style='color: red;'>Invalid or expired verification link!</p>";
} else {
    // Mark user as verified
    $update_sql = "UPDATE users SET status = 'accepted', verified = '1', verification_token = NULL WHERE id = ?";
    $update_stmt = mysqli_prepare($connector, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
    mysqli_stmt_execute($update_stmt);

    $message = "<p style='color: green;'>Your account has been successfully verified! You can now log in.</p>";
    
}


?>
<style>
    body {
        font-family: 'Roboto';
    }
</style>
<body class="bg-[#f8fafc] box-border p-0 m-0 font-">
    <div class="flex items-center justify-center h-[80vh]">
        <div class="mt-[50px] px-[1em] py-[3em] bg-white md:px-[4em] md:py-[5em] rounded-3xl shadow-lg text-center">
            <div class="flex justify-center mb-8">
                <div class=mt-[-30px]><img class="w-16" src="../uploads/assets/yes.png"></div>
            </div>
            <h1 class="text-2xl font-bold mb-4">Email Verification</h1>
            <p class="text-gray-600 mb-4"><?= $message?></p>
            <a href="../login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                Go to Login
            </a>
        </div>
    </div>
</body>