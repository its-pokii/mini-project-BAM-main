<?php
// alumni/connection-handler.php

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Auth ──────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

// ── CSRF check ────────────────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die('Invalid CSRF token.');
}

// ── DB ────────────────────────────────────────────────────────
$connector = new mysqli("localhost", "root", "", "alumni_network");
if ($connector->connect_error) {
    die('DB connection failed.');
}

// ── Input ─────────────────────────────────────────────────────
$action        = $_POST['action']        ?? '';
$connection_id = (int)($_POST['connection_id'] ?? 0);
$alumni_id     = (int)$_SESSION['user_id'];

if (!$connection_id || !in_array($action, ['accept', 'decline'])) {
    header("Location: connections.php");
    exit;
}

// ── Verify request belongs to this alumni ─────────────────────
$stmt = $connector->prepare("
    SELECT id, student_id, status
    FROM connection_requests
    WHERE id = ? AND alumni_id = ?
");
$stmt->bind_param("ii", $connection_id, $alumni_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request || $request['status'] !== 'pending') {
    header("Location: requests.php");
    exit;
}

$student_id = (int)$request['student_id'];

// ── Handle decline ────────────────────────────────────────────
if ($action === 'decline') {
    $stmt = $connector->prepare("
        UPDATE connection_requests SET status = 'declined' WHERE id = ?
    ");
    $stmt->bind_param("i", $connection_id);
    $stmt->execute();
    $stmt->close();

    header("Location: requests.php");
    exit;
}

// ── Handle accept ─────────────────────────────────────────────
// 1. Mark as accepted
$stmt = $connector->prepare("
    UPDATE connection_requests SET status = 'accepted' WHERE id = ?
");
$stmt->bind_param("i", $connection_id);
$stmt->execute();
$stmt->close();

// 2. Create message thread if one doesn't exist yet
$stmt = $connector->prepare("
    SELECT id FROM message_threads
    WHERE (student_id = ? AND alumni_id = ?)
       OR (student_id = ? AND alumni_id = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $student_id, $alumni_id, $alumni_id, $student_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existing) {
    $stmt = $connector->prepare("
        INSERT INTO message_threads (student_id, alumni_id, connection_request_id, last_message_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iii", $student_id, $alumni_id, $connection_id);
    $stmt->execute();
    $stmt->close();
}

// ── Redirect back ─────────────────────────────────────────────
header("Location: requests.php");
exit;