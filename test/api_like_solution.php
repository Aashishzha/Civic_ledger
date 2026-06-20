<?php
/**
 * CivicLedger - Solution Like/Dislike API
 */
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$solutionId = intval($input['solution_id'] ?? 0);
$type = $input['type'] ?? 'like'; // 'like' or 'dislike'

if (!$solutionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid solution ID']);
    exit;
}

// Check if user is logged in
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Please login to like/dislike']);
    exit;
}

global $conn;

// Check if solution exists
$stmt = $conn->prepare("SELECT id, likes, dislikes FROM solutions WHERE id = ?");
$stmt->bind_param("i", $solutionId);
$stmt->execute();
$r = $stmt->get_result();
if (!$r || $r->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Solution not found']);
    exit;
}

$solution = $r->fetch_assoc();

if ($type === 'like') {
    likeSolution($userId, $solutionId);
} else {
    dislikeSolution($userId, $solutionId);
}

// Get updated counts
$stmt = $conn->prepare("SELECT likes, dislikes FROM solutions WHERE id = ?");
$stmt->bind_param("i", $solutionId);
$stmt->execute();
$r = $stmt->get_result();
$updated = $r->fetch_assoc();

echo json_encode([
    'success' => true,
    'likes' => (int)($updated['likes'] ?? 0),
    'dislikes' => (int)($updated['dislikes'] ?? 0)
]);