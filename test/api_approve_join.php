<?php
/**
 * CivicLedger - API: Approve/Reject Join Request
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();
$requestId = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$requestId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

global $conn;

// Get the request
$stmt = $conn->prepare("SELECT jr.*, t.mentor_id, t.leader_id FROM join_requests jr JOIN teams t ON jr.team_id = t.id WHERE jr.id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$r = $stmt->get_result();
$request = $r ? $r->fetch_assoc() : null;

if (!$request) {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
    exit;
}

// Check permission (mentor, team leader, or admin)
if ($user['role'] !== 'admin' && $user['role'] !== 'mentor' && $request['leader_id'] !== $user['id']) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if ($action === 'approve') {
    // Add student to team members
    $members = json_decode($request['members'] ?? '[]', true) ?: [];
    if (!in_array($request['user_id'], $members)) {
        $members[] = $request['user_id'];
    }
    
    $stmt = $conn->prepare("UPDATE teams SET members = ? WHERE id = ?");
    $membersJson = json_encode($members);
    $stmt->bind_param("si", $membersJson, $request['team_id']);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE join_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $user['id'], $requestId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Join request approved']);
} else {
    $stmt = $conn->prepare("UPDATE join_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $user['id'], $requestId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Join request rejected']);
}