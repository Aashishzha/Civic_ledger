<?php
/**
 * CivicLedger - API: Join Team Request
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();
$teamId = (int)($_POST['team_id'] ?? 0);
$proposal = trim($_POST['proposal'] ?? '');

if (!$teamId || empty($proposal)) {
    echo json_encode(['success' => false, 'error' => 'Team ID and proposal are required']);
    exit;
}

global $conn;

// Check if user already has a team
if (getUserTeam($user['id'])) {
    echo json_encode(['success' => false, 'error' => 'You are already in a team']);
    exit;
}

// Check if already requested
$stmt = $conn->prepare("SELECT id FROM join_requests WHERE team_id = ? AND user_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $teamId, $user['id']);
$stmt->execute();
$r = $stmt->get_result();
if ($r && $r->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You have already requested to join this team']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO join_requests (team_id, user_id, proposal) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $teamId, $user['id'], $proposal);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send join request']);
}