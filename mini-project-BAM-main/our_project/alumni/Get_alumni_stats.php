<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once "../../auth/config.php";

$user_id = (int) $_SESSION['user_id'];

// Pending connection requests
$s = $connector->prepare("SELECT COUNT(*) FROM connections WHERE alumni_id = ? AND status = 'pending'");
$s->bind_param('i', $user_id); $s->execute(); $s->bind_result($pending_count); $s->fetch(); $s->close();

// Accepted connections
$s = $connector->prepare("SELECT COUNT(*) FROM connections WHERE alumni_id = ? AND status = 'accepted'");
$s->bind_param('i', $user_id); $s->execute(); $s->bind_result($accepted_count); $s->fetch(); $s->close();

// Unread messages
$s = $connector->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$s->bind_param('i', $user_id); $s->execute(); $s->bind_result($unread_count); $s->fetch(); $s->close();

// Total story views
$s = $connector->prepare("SELECT COALESCE(SUM(views), 0) FROM stories WHERE alumni_id = ?");
$s->bind_param('i', $user_id); $s->execute(); $s->bind_result($story_views); $s->fetch(); $s->close();

echo json_encode([
    'pending_count'  => (int) $pending_count,
    'accepted_count' => (int) $accepted_count,
    'unread_count'   => (int) $unread_count,
    'story_views'    => (int) $story_views,
]);