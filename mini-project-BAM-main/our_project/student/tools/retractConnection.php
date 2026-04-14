<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../../auth/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message_text' => 'Not logged in']);
    exit;
}

$data          = json_decode(file_get_contents('C:\xampp\htdocs\AlumniProject\our_project\student\04-connections.php'), true);
$connection_id = (int)($data['connection_id'] ?? 0);
$user_id       = (int)$_SESSION['user_id'];

if ($connection_id === 0) {
    echo json_encode(['success' => false, 'message_text' => 'Invalid request']);
    exit;
}

// only delete if this user is the sender
$stmt = mysqli_prepare($connector,
    "DELETE FROM connection_requests WHERE id = ? AND student_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $connection_id, $user_id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message_text' => 'Not found']);
}
?>