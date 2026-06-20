<?php
/**
 * CivicLedger - API: Sponsor Message to Admin
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();

if ($user['role'] !== 'sponsor' && $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Handle reply from admin
if ($user['role'] === 'admin' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $messageId = (int)($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    
    if ($messageId && $reply) {
        global $conn;
        $stmt = $conn->prepare("SELECT sender_id FROM admin_messages WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $r = $stmt->get_result();
        $original = $r ? $r->fetch_assoc() : null;
        
        if ($original) {
            $stmt = $conn->prepare("UPDATE admin_messages SET reply_message = ?, replied_at = NOW(), is_read = 1 WHERE id = ?");
            $stmt->bind_param("si", $reply, $messageId);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// Handle mark as read
if (isset($_POST['action']) && $_POST['action'] === 'read') {
    $messageId = (int)($_POST['message_id'] ?? 0);
    global $conn;
    $stmt = $conn->prepare("UPDATE admin_messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Handle new message from sponsor
if ($user['role'] === 'sponsor') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
        exit;
    }
    
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_messages (sender_id, subject, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user['id'], $subject, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);