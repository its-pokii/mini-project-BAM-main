<?php
// tools/getMessages.php
session_start();
$connector = new mysqli("localhost", "root", "", "alumni_network");
if ($connector->connect_error) {
    echo json_encode(["success" => false, "message" => "DB error: " . $connector->connect_error]);
    exit;
} // adjust path to your DB connection file

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$my_id      = $_SESSION['user_id'];
$other_id   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$other_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Find the thread between the two users
$thread_sql = "
    SELECT id FROM message_threads
    WHERE (student_id = ? AND alumni_id = ?)
       OR (student_id = ? AND alumni_id = ?)
    LIMIT 1
";
$ts = $connector->prepare($thread_sql);
$ts->bind_param("iiii", $my_id, $other_id, $other_id, $my_id);
$ts->execute();
$thread_result = $ts->get_result()->fetch_assoc();
$ts->close();

if (!$thread_result) {
    // No thread yet — return empty array (not an error)
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$thread_id = $thread_result['id'];

// Mark messages sent to me in this thread as read
$mark_sql = "
    UPDATE messages
    SET is_read = 1
    WHERE thread_id = ? AND receiver_id = ? AND is_read = 0
";
$ms = $connector->prepare($mark_sql);
$ms->bind_param("ii", $thread_id, $my_id);
$ms->execute();
$ms->close();

// Fetch all messages — alias columns to match what the JS expects
$msg_sql = "
    SELECT
        id,
        sender_id,
        receiver_id,
        message_text  AS message,
        sent_at       AS created_at,
        is_read
    FROM messages
    WHERE thread_id = ?
    ORDER BY sent_at ASC
";
$ms2 = $connector->prepare($msg_sql);
$ms2->bind_param("i", $thread_id);
$ms2->execute();
$result = $ms2->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$ms2->close();

echo json_encode(['success' => true, 'data' => $messages]);