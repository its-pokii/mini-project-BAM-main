<?php
session_start();
require_once('../../auth/config.php');

header('Content-Type: application/json');

// must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data        = json_decode(file_get_contents('C:\xampp\htdocs\AlumniProject\our_project\student\03-profile-view.php'), true);
$receiver_id = $_SESSION['profile_id']; // get receiver ID from session (set when viewing profile) 
$sender_id   = (int)$_SESSION['user_id'];
echo "Sender: $sender_id, Receiver: $receiver_id";
// can't connect to yourself
if ($receiver_id === $sender_id || $receiver_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// check if connection already exists
$stmt = mysqli_prepare($connector,
    "SELECT id FROM connection_requests 
     WHERE student_id = ? AND alumni_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $sender_id, $receiver_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already sent']);
    exit;
}

// insert connection request
$stmt = mysqli_prepare($connector,
    "INSERT INTO connection_requests (student_id, alumni_id, status) VALUES (?, ?, 'pending')"
);
mysqli_stmt_bind_param($stmt, "ii", $sender_id, $receiver_id);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true, 'message' => 'Connection sent']);
?>