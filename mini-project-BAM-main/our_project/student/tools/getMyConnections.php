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

$user_id = (int)$_SESSION['user_id'];

// get accepted connections
$stmt = mysqli_prepare($connector,
    "SELECT 
        connection_requests.id           AS connection_id,
        connection_requests.status,
        connection_requests.created_at,
        users.id                AS user_id,
        users.first_name,
        users.last_name,
        users.profile_photo,
        alumni_profiles.current_position,
        alumni_profiles.current_company,
        alumni_profiles.major,
        alumni_profiles.graduation_year
     FROM connection_requests
     INNER JOIN users 
        ON (CASE 
              WHEN connection_requests.student_id   = ? THEN connection_requests.alumni_id = users.id
              WHEN connection_requests.alumni_id = ? THEN connection_requests.student_id   = users.id
            END)
     LEFT JOIN alumni_profiles 
        ON users.id = alumni_profiles.user_id
     WHERE (connection_requests.student_id = ? OR connection_requests.alumni_id = ?)
     AND connection_requests.status = 'accepted'
     ORDER BY connection_requests.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "iiii", $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$accepted = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accepted[] = $row;
}

// get pending requests sent BY this user
$stmt = mysqli_prepare($connector,
    "SELECT 
        connection_requests.id          AS connection_id,
        connection_requests.status,
        connection_requests.created_at,
        users.id                AS user_id,
        users.first_name,
        users.last_name,
        users.profile_photo,
        alumni_profiles.current_position,
        alumni_profiles.current_company,
        alumni_profiles.major,
        alumni_profiles.graduation_year
     FROM connection_requests
     INNER JOIN users 
        ON connection_requests.alumni_id = users.id
     LEFT JOIN alumni_profiles 
        ON users.id = alumni_profiles.user_id
     WHERE connection_requests.student_id = ?
     AND   connection_requests.status    = 'pending'
     ORDER BY connection_requests.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$pending = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pending[] = $row;
}

echo json_encode([
    'success'  => true,
    'accepted' => $accepted,
    'pending'  => $pending
]);
?>