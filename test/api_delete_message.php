<?php
/**
 * CivicLedger - API: Delete Message
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();
$messageId = (int)($_POST['message_id'] ?? 0);

if (!$messageId) {
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    exit;
}

if (!canDeleteMessage($user['id'], $messageId)) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

global $conn;
$stmt = $conn->prepare("UPDATE chat_messages SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("ii", $user['id'], $messageId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}