<?php
/**
 * CivicLedger - API: Get Messages
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$lastId = (int)($_GET['last_id'] ?? 0);
$user1 = isset($_GET['user1']) ? (int)$_GET['user1'] : null;
$user2 = isset($_GET['user2']) ? (int)$_GET['user2'] : null;

global $conn;

if ($groupId) {
    $stmt = $conn->prepare("SELECT cm.*, u.name as sender_name FROM chat_messages cm JOIN users u ON cm.sender_id = u.id WHERE cm.group_id = ? AND cm.id > ? AND cm.is_deleted = 0 ORDER BY cm.created_at ASC LIMIT 50");
    $stmt->bind_param("ii", $groupId, $lastId);
    $stmt->execute();
    $r = $stmt->get_result();
    $messages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
} elseif ($user1 && $user2) {
    $stmt = $conn->prepare("SELECT cm.*, u.name as sender_name FROM chat_messages cm JOIN users u ON cm.sender_id = u.id WHERE ((cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?)) AND cm.id > ? AND cm.is_deleted = 0 ORDER BY cm.created_at ASC LIMIT 50");
    $stmt->bind_param("iiiiii", $user1, $user2, $user2, $user1, $lastId);
    $stmt->execute();
    $r = $stmt->get_result();
    $messages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

echo json_encode(['success' => true, 'messages' => $messages]);