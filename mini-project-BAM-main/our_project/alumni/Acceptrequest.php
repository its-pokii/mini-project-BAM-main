<?php
// alumni/tools/acceptRequest.php  (or wherever your alumni tools live)

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ── DB ────────────────────────────────────────────────────────
$connector = new mysqli("localhost", "root", "", "alumni_network");
if ($connector->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $connector->connect_error]);
    exit;
}

// ── Input ─────────────────────────────────────────────────────
$data       = json_decode(file_get_contents('php://input'), true);
$request_id = isset($data['request_id']) ? (int)$data['request_id'] : 0;
$alumni_id  = (int)$_SESSION['user_id'];

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Missing request_id']);
    exit;
}

// ── 1. Verify the request exists and belongs to this alumni ───
$stmt = $connector->prepare("
    SELECT id, student_id, alumni_id, status
    FROM connection_requests
    WHERE id = ? AND alumni_id = ?
");
$stmt->bind_param("ii", $request_id, $alumni_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

if ($request['status'] === 'accepted') {
    echo json_encode(['success' => false, 'message' => 'Already accepted']);
    exit;
}

$student_id = (int)$request['student_id'];

// ── 2. Mark request as accepted ───────────────────────────────
$stmt = $connector->prepare("
    UPDATE connection_requests SET status = 'accepted' WHERE id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

// ── 3. Create message thread if one doesn't exist yet ─────────
$stmt = $connector->prepare("
    SELECT id FROM message_threads
    WHERE (student_id = ? AND alumni_id = ?)
       OR (student_id = ? AND alumni_id = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $student_id, $alumni_id, $alumni_id, $student_id);
$stmt->execute();
$existing_thread = $stmt->get_result()->fetch_assoc();
$stmt->close();

$thread_id = null;

if ($existing_thread) {
    // Thread already exists — reuse it
    $thread_id = $existing_thread['id'];
} else {
    // Create a fresh thread
    $stmt = $connector->prepare("
        INSERT INTO message_threads (student_id, alumni_id, connection_request_id, last_message_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iii", $student_id, $alumni_id, $request_id);
    $stmt->execute();
    $thread_id = $stmt->insert_id;
    $stmt->close();
}

echo json_encode([
    'success'   => true,
    'message'   => 'Connection accepted',
    'thread_id' => $thread_id
]);