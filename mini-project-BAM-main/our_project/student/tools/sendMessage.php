<?php
// tools/sendMessage.php
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

$my_id = $_SESSION['user_id'];
$body  = json_decode(file_get_contents('php://input'), true);

$receiver_id = isset($body['receiver_id']) ? (int)$body['receiver_id'] : 0;
$message     = isset($body['message'])     ? trim($body['message'])     : '';

if (!$receiver_id || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Missing receiver_id or message']);
    exit;
}

// ── 1. Find existing thread or create one ─────────────────────
$thread_sql = "
    SELECT id FROM message_threads
    WHERE (student_id = ? AND alumni_id = ?)
       OR (student_id = ? AND alumni_id = ?)
    LIMIT 1
";
$ts = $connector->prepare($thread_sql);
$ts->bind_param("iiii", $my_id, $receiver_id, $receiver_id, $my_id);
$ts->execute();
$thread_row = $ts->get_result()->fetch_assoc();
$ts->close();

if ($thread_row) {
    $thread_id = $thread_row['id'];
} else {
    // Determine who is student and who is alumni based on role
    // We'll use session role if available, otherwise default order
    $role = $_SESSION['role'] ?? 'student';

    if ($role === 'student') {
        $student_id_val = $my_id;
        $alumni_id_val  = $receiver_id;
    } else {
        $student_id_val = $receiver_id;
        $alumni_id_val  = $my_id;
    }

    $create_sql = "
        INSERT INTO message_threads (student_id, alumni_id, last_message_at)
        VALUES (?, ?, NOW())
    ";
    $cs = $connector->prepare($create_sql);
    $cs->bind_param("ii", $student_id_val, $alumni_id_val);
    $cs->execute();
    $thread_id = $cs->insert_id;
    $cs->close();
}

// ── 2. Insert the message ─────────────────────────────────────
$insert_sql = "
    INSERT INTO messages (thread_id, sender_id, receiver_id, message_text, sent_at, is_read)
    VALUES (?, ?, ?, ?, NOW(), 0)
";
$is = $connector->prepare($insert_sql);
$is->bind_param("iiis", $thread_id, $my_id, $receiver_id, $message);
$is->execute();
$message_id = $is->insert_id;
$is->close();

// ── 3. Update thread's last_message_at ───────────────────────
$update_sql = "UPDATE message_threads SET last_message_at = NOW() WHERE id = ?";
$us = $connector->prepare($update_sql);
$us->bind_param("i", $thread_id);
$us->execute();
$us->close();

echo json_encode([
    'success'    => true,
    'message_id' => $message_id,
    'thread_id'  => $thread_id
]);