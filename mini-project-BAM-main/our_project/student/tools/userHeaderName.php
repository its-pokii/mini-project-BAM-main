<?php
session_start(); 
require_once('../auth/config.php');
$user_id = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare($connector, 
    "SELECT * FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
?>