<?php
// tools/getConversations.php

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// ── DB connection — create it directly here ───────────────────
$connector = new mysqli("localhost", "root", "", "alumni_network");
if ($connector->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connect error: ' . $connector->connect_error]);
    exit;
}

$my_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT
        t.id AS thread_id,
        t.last_message_at,

        CASE WHEN t.student_id = ? THEN t.alumni_id
             ELSE t.student_id
        END AS user_id,

        u.first_name,
        u.last_name,
        u.profile_photo,

        (SELECT m2.message_text FROM messages m2
         WHERE m2.thread_id = t.id ORDER BY m2.sent_at DESC LIMIT 1) AS last_message,

        (SELECT m3.sender_id FROM messages m3
         WHERE m3.thread_id = t.id ORDER BY m3.sent_at DESC LIMIT 1) AS sender_id,

        (SELECT m4.sent_at FROM messages m4
         WHERE m4.thread_id = t.id ORDER BY m4.sent_at DESC LIMIT 1) AS last_time,

        (SELECT COUNT(*) FROM messages m5
         WHERE m5.thread_id = t.id
           AND m5.receiver_id = ?
           AND m5.is_read = 0) AS unread_count

    FROM message_threads t
    JOIN users u ON u.id = CASE WHEN t.student_id = ? THEN t.alumni_id
                                ELSE t.student_id END
    WHERE t.student_id = ? OR t.alumni_id = ?
    ORDER BY t.last_message_at DESC
";

$stmt = $connector->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL error: ' . $connector->error]);
    exit;
}

$stmt->bind_param("iiiii", $my_id, $my_id, $my_id, $my_id, $my_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $conversations]);