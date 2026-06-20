<?php
/**
 * CivicLedger - API: Send Message
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : null;
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

global $conn;

// Check mentor message limits
if ($receiverId && $user['role'] === 'student') {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiverId);
    $stmt->execute();
    $r = $stmt->get_result();
    $receiver = $r ? $r->fetch_assoc() : null;
    
    if ($receiver && $receiver['role'] === 'mentor') {
        if (!canMessageMentor($user['id'])) {
            echo json_encode(['success' => false, 'error' => 'Weekly limit reached. Upgrade to Plus/Premium for unlimited.']);
            exit;
        }
    }
}

$stmt = $conn->prepare("INSERT INTO chat_messages (group_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("siis", $groupId, $user['id'], $receiverId, $message);

if ($stmt->execute()) {
    if ($receiverId && $user['role'] === 'student') {
        $sub = getUserSubscription($user['id']);
        if (!$sub['is_plus']) incrementMentorMessages($user['id']);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}