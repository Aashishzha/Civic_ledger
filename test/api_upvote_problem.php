<?php
/**
 * CivicLedger - Problem Upvote API
 * Handles community upvoting with trust score updates
 */
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Get user from session or create anonymous session
$userId = $_SESSION['user_id'] ?? null;
$sessionToken = $_SESSION['upvote_sessions'] ?? [];

// Problem ID
$problemId = intval($input['problem_id'] ?? 0);

if (!$problemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid problem ID']);
    exit;
}

global $conn;

// Check if problem exists
$stmt = $conn->prepare("SELECT id, user_id, upvotes FROM problems WHERE id = ?");
$stmt->bind_param("i", $problemId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Problem not found']);
    exit;
}

$problem = $result->fetch_assoc();

// Check if user already upvoted (prevent duplicate voting)
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$sessionKey = 'upvoted_' . $problemId;

// For logged-in users
if ($userId) {
    // Check if already upvoted in this session
    if (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === $userId) {
        // Already upvoted - remove upvote
        $removeUpvote = $conn->prepare("UPDATE problems SET upvotes = GREATEST(0, upvotes - 1) WHERE id = ?");
        $removeUpvote->bind_param("i", $problemId);
        $removeUpvote->execute();
        
        // Remove trust score for original upvoter
        updateTrustScore($userId, -1, 'upvote_removed', 'Removed upvote from problem #' . $problemId, $problemId, 'problem');
        
        echo json_encode([
            'success' => true, 
            'action' => 'removed',
            'upvotes' => $problem['upvotes'] - 1,
            'message' => 'Upvote removed'
        ]);
        exit;
    }
    
    // Add upvote
    $addUpvote = $conn->prepare("UPDATE problems SET upvotes = upvotes + 1 WHERE id = ?");
    $addUpvote->bind_param("i", $problemId);
    $addUpvote->execute();
    
    // Store upvote in session
    $_SESSION[$sessionKey] = $userId;
    
    // Update trust score for upvoter (+1 for contributing)
    updateTrustScore($userId, 1, 'upvote_given', 'Upvoted problem #' . $problemId, $problemId, 'problem');
    
    // Update trust score for problem poster (+1 per upvote received)
    if ($problem['user_id']) {
        updateTrustScore($problem['user_id'], 1, 'upvote_received', 'Received upvote on problem #' . $problemId, $problemId, 'problem');
    }
    
    echo json_encode([
        'success' => true,
        'action' => 'added',
        'upvotes' => $problem['upvotes'] + 1,
        'message' => 'Upvote added'
    ]);
    exit;
}

// For guest users - use IP-based tracking
$guestVoteKey = 'guest_upvoted_' . $problemId . '_' . md5($ipAddress);

if (isset($_SESSION[$guestVoteKey]) && $_SESSION[$guestVoteKey] === true) {
    // Already upvoted - remove
    $removeUpvote = $conn->prepare("UPDATE problems SET upvotes = GREATEST(0, upvotes - 1) WHERE id = ?");
    $removeUpvote->bind_param("i", $problemId);
    $removeUpvote->execute();
    
    unset($_SESSION[$guestVoteKey]);
    
    echo json_encode([
        'success' => true,
        'action' => 'removed',
        'upvotes' => $problem['upvotes'] - 1,
        'message' => 'Upvote removed'
    ]);
    exit;
}

// Add guest upvote
$addUpvote = $conn->prepare("UPDATE problems SET upvotes = upvotes + 1 WHERE id = ?");
$addUpvote->bind_param("i", $problemId);
$addUpvote->execute();

$_SESSION[$guestVoteKey] = true;

// Update problem poster trust score
if ($problem['user_id']) {
    updateTrustScore($problem['user_id'], 1, 'upvote_received', 'Received upvote on problem #' . $problemId, $problemId, 'problem');
}

echo json_encode([
    'success' => true,
    'action' => 'added',
    'upvotes' => $problem['upvotes'] + 1,
    'message' => 'Upvote added'
]);