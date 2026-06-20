<?php
/**
 * CivicLedger - Configuration & Helper Functions
 * Complete application configuration and utility functions
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    die('Direct access not allowed');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'elite4_nepal');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create upload directories (Civic Platform)
$uploadDirs = ['uploads/', 'uploads/profiles/', 'uploads/voice/'];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Sanitize output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return getUserById($_SESSION['user_id']);
    }
    return null;
}

/**
 * Get user by ID
 */
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return ($r = $stmt->get_result()) ? $r->fetch_assoc() : null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    requireLogin();
    $user = getCurrentUser();
    if (!in_array($user['role'], (array)$roles)) {
        setFlash('error', 'You do not have permission to access that page.');
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format currency (Nepalese Rupees)
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount ?? 0, 2);
}

/**
 * Time ago helper
 */
function timeAgo($datetime) {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    
    return date('M j, Y', $time);
}

/**
 * Get user initials for avatar
 */
function getInitials($name) {
    $words = explode(' ', $name ?? '');
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return $initials ?: '?';
}

/**
 * AI-powered problem categorization
 * Uses keyword matching to determine category and urgency
 */
function aiClassifyProblem($title, $description) {
    $text = strtolower($title . ' ' . $description);
    
    // Category detection
    $categories = [
        'Waste' => ['garbage', 'waste', 'trash', 'dustbin', 'litter', 'sewage', 'dump', 'clean', 'sanitation', 'garbage', 'waste management', 'overflowing bin'],
        'Road' => ['road', 'pothole', 'street', 'traffic', 'sidewalk', 'walkway', 'car', 'accident', 'driving', 'signal', 'road safety', 'road damage'],
        'Health' => ['health', 'hospital', 'clinic', 'disease', 'sick', 'medical', 'doctor', 'patient', 'emergency', 'ambulance', 'contaminated', 'illness'],
        'Water' => ['water', 'drought', 'flood', 'river', 'tap', 'pipe', 'drinking', 'contamination', 'drainage', 'irrigation', 'clean water', 'water supply']
    ];
    
    $category = 'Other';
    foreach ($categories as $cat => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $category = $cat;
                break 2;
            }
        }
    }
    
    // Urgency detection
    $urgency = 'Medium';
    if (preg_match('/\b(urgent|emergency|critical|immediately|accident|danger|death|injured|overflowing|broken|crisis|fallen sick|dead|die)\b/i', $text)) {
        $urgency = 'High';
    } elseif (preg_match('/\b(slow|gradual|eventually|when possible|minor|slight|not urgent|low priority)\b/i', $text)) {
        $urgency = 'Low';
    }
    
    return ['category' => $category, 'urgency' => $urgency];
}

/**
 * Get platform commission percentage
 */
function getCommissionPercent() {
    global $conn;
    $result = $conn->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'commission_percent'");
    if ($result && $row = $result->fetch_assoc()) {
        return (float)$row['setting_value'];
    }
    return 10; // Default 10%
}

/**
 * Calculate commission and net amount
 */
function calculateCommission($gross) {
    $percent = getCommissionPercent();
    $commission = $gross * ($percent / 100);
    $net = $gross - $commission;
    return [
        'gross' => $gross,
        'commission' => $commission,
        'net' => $net,
        'percent' => $percent
    ];
}

/**
 * Get user subscription details
 * NOTE: For demo, all users have unlimited mentor messages (no limits)
 */
function getUserSubscription($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        return [
            'plan' => 'free',
            'mentor_messages_limit' => PHP_INT_MAX, // Unlimited for demo
            'mentor_messages_used' => 0,
            'is_plus' => true, // All have plus for demo
            'is_premium' => true, // All have premium for demo
            'mentor_messages_reset_at' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days'))
        ];
    }
    
    $sub = $result->fetch_assoc();
    
    return [
        'plan' => $sub['plan'],
        'mentor_messages_limit' => PHP_INT_MAX, // Unlimited for civic demo
        'mentor_messages_used' => $sub['mentor_messages_used'],
        'is_plus' => true, // All have plus for demo
        'is_premium' => true, // All have premium for demo
        'start_date' => $sub['start_date'],
        'end_date' => $sub['end_date']
    ];
}

/**
 * Check if user can message mentor
 */
function canMessageMentor($userId) {
    $sub = getUserSubscription($userId);
    return $sub['is_plus'] || $sub['mentor_messages_used'] < $sub['mentor_messages_limit'];
}

/**
 * Get remaining mentor messages
 */
function getRemainingMentorMessages($userId) {
    $sub = getUserSubscription($userId);
    if ($sub['is_plus']) return PHP_INT_MAX;
    return max(0, $sub['mentor_messages_limit'] - $sub['mentor_messages_used']);
}

/**
 * Increment mentor message count
 */
function incrementMentorMessages($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE user_subscriptions SET mentor_messages_used = mentor_messages_used + 1 WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

/**
 * Upload file helper
 */
function uploadFile($fileInput, $directory = 'uploads/') {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Create directory if not exists
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    $file = $_FILES[$fileInput];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }
    
    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return null;
}

/**
 * Get platform stats
 */
function getStats() {
    global $conn;
    
    $r1 = $conn->query("SELECT COUNT(*) as cnt FROM problems");
    $problems = ($r1 && $r1->num_rows > 0) ? (int)$r1->fetch_assoc()['cnt'] : 0;
    
    $r2 = $conn->query("SELECT COUNT(*) as cnt FROM solutions WHERE status IN ('approved', 'rewarded')");
    $solutions = ($r2 && $r2->num_rows > 0) ? (int)$r2->fetch_assoc()['cnt'] : 0;
    
    $r3 = $conn->query("SELECT COALESCE(SUM(reward_net),0) as total FROM solutions WHERE status = 'rewarded'");
    $rewards = ($r3 && $r3->num_rows > 0) ? (float)($r3->fetch_assoc()['total'] ?? 0) : 0;
    
    $r4 = $conn->query("SELECT COUNT(*) as cnt FROM sponsorships WHERE status = 'disbursed'");
    $sponsors = ($r4 && $r4->num_rows > 0) ? (int)$r4->fetch_assoc()['cnt'] : 0;
    
    return [
        'problems' => $problems,
        'solutions' => $solutions,
        'rewards' => $rewards,
        'sponsors' => $sponsors
    ];
}

/**
 * Get recent problems
 */
function getRecentProblems($limit = 6) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.name as user_name, u.profile_photo 
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Check if user is team moderator for a team
 */
function isTeamModerator($userId, $teamId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM moderator_assignments WHERE team_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $teamId, $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    return ($r && $r->num_rows > 0);
}

/**
 * Check if user can delete a message
 */
function canDeleteMessage($userId, $messageId) {
    global $conn;
    $user = getUserById($userId);
    
    // Admin can delete any message
    if ($user['role'] === 'admin') return true;
    
    // Get message details
    $stmt = $conn->prepare("SELECT cm.*, cg.team_id FROM chat_messages cm LEFT JOIN chat_groups cg ON cm.group_id = cg.id WHERE cm.id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $msgResult = $stmt->get_result();
    $message = ($msgResult && $msgResult->num_rows > 0) ? $msgResult->fetch_assoc() : null;
    
    if (!$message) return false;
    
    // Moderator of the team can delete
    if ($message['team_id'] && isTeamModerator($userId, $message['team_id'])) return true;
    
    // Sender can delete their own message
    if ($message['sender_id'] === $userId) return true;
    
    return false;
}

/**
 * Get success stories for carousel
 */
function getSuccessStories($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM success_stories WHERE is_active = 1 ORDER BY id ASC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return ($result = $stmt->get_result()) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Log sponsor activity
 */
function logSponsorActivity($sponsorId, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO sponsor_activity_log (sponsor_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $sponsorId, $action, $details);
    $stmt->execute();
}

// ==========================================
// NEPAL STARTUP GOVERNANCE RULES
// ==========================================

/**
 * Rule #1: Trust Score System
 * Get user's current trust score
 */
function getTrustScore($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT trust_score FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    $user = $r ? $r->fetch_assoc() : null;
    return $user ? (int)$user['trust_score'] : 100;
}

/**
 * Update trust score with logging
 */
function updateTrustScore($userId, $pointsChange, $actionType, $description = '', $relatedId = null, $relatedType = null) {
    global $conn;
    
    // Update user's trust score
    $stmt = $conn->prepare("UPDATE users SET trust_score = GREATEST(0, trust_score + ?) WHERE id = ?");
    $stmt->bind_param("ii", $pointsChange, $userId);
    $stmt->execute();
    
    // Log the change
    $stmt = $conn->prepare("INSERT INTO trust_score_logs (user_id, action_type, points_change, description, related_id, related_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $userId, $actionType, $pointsChange, $description, $relatedId, $relatedType);
    $stmt->execute();
    
    // Check if user is a team leader - update team score too
    $stmt = $conn->prepare("SELECT id, rank_points FROM teams WHERE leader_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    $team = $r ? $r->fetch_assoc() : null;
    if ($team) {
        $newRankPoints = max(0, $team['rank_points'] + $pointsChange);
        $stmt = $conn->prepare("UPDATE teams SET rank_points = ? WHERE id = ?");
        $stmt->bind_param("ii", $newRankPoints, $team['id']);
        $stmt->execute();
    }
}

/**
 * Check if user can apply for high-paying gigs
 * Rule: Trust score must be >= 60 for high-paying gigs
 */
function canApplyForHighGig($userId, $gigBudget) {
    global $conn;
    $setting = getSetting('min_trust_score_for_high_gigs', 60);
    $minScore = (int)$setting;
    
    $trustScore = getTrustScore($userId);
    
    // High-paying gig is above Rs. 25,000
    if ($gigBudget > 25000 && $trustScore < $minScore) {
        return false;
    }
    return true;
}

/**
 * Rule #2: Proof of Progress (PoP) System
 * Add a progress update for a team
 */
function addProgressUpdate($teamId, $updateType, $title, $description = '', $linkUrl = null, $photoUrl = null, $createdBy) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO progress_updates (team_id, update_type, title, description, link_url, photo_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $teamId, $updateType, $title, $description, $linkUrl, $photoUrl, $createdBy);
    $result = $stmt->execute();
    
    if ($result) {
        // Update team's last progress update time
        $stmt = $conn->prepare("UPDATE teams SET last_progress_update = NOW(), is_inactive = 0 WHERE id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        
        // Give trust score bonus for progress
        updateTrustScore($createdBy, 2, 'progress_update', 'Submitted progress update: ' . $title);
    }
    
    return $result;
}

/**
 * Check if team is inactive (no progress for 14+ days)
 */
function checkTeamInactivity() {
    global $conn;
    $setting = getSetting('pop_update_interval_days', 14);
    $days = (int)$setting;
    
    // Find teams that haven't updated progress in 14+ days
    $stmt = $conn->prepare("
        SELECT t.*, DATEDIFF(NOW(), COALESCE(t.last_progress_update, t.created_at)) as days_since_update 
        FROM teams t 
        WHERE t.status = 'active' 
        AND t.is_inactive = 0 
        AND DATEDIFF(NOW(), COALESCE(t.last_progress_update, t.created_at)) > ?
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $r = $stmt->get_result();
    
    if ($r && $r->num_rows > 0) {
        $inactiveTeams = $r->fetch_all(MYSQLI_ASSOC);
        foreach ($inactiveTeams as $team) {
            // Mark team as inactive
            $stmt2 = $conn->prepare("UPDATE teams SET is_inactive = 1, status = 'inactive' WHERE id = ?");
            $stmt2->bind_param("i", $team['id']);
            $stmt2->execute();
            
            // Penalize trust score of all team members
            $memberIds = json_decode($team['members'] ?? '[]', true) ?: [];
            foreach ($memberIds as $mid) {
                updateTrustScore($mid, -10, 'team_inactive', 'Team marked inactive due to no progress updates', $team['id'], 'team');
            }
            
            // Alert the sponsor if there's a challenge
            $stmt3 = $conn->prepare("SELECT sponsor_id FROM challenges WHERE team_id = ? OR status = 'open' LIMIT 1");
            $stmt3->bind_param("i", $team['id']);
            $stmt3->execute();
            $cr = $stmt3->get_result();
            $challenge = $cr ? $cr->fetch_assoc() : null;
            if ($challenge) {
                logSponsorActivity($challenge['sponsor_id'], 'team_inactive', 'Team ' . $team['name'] . ' marked inactive after ' . $days . ' days');
            }
        }
    }
}

/**
 * Get team progress updates
 */
function getTeamProgressUpdates($teamId, $limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT pu.*, u.name as created_by_name FROM progress_updates pu JOIN users u ON pu.created_by = u.id WHERE pu.team_id = ? ORDER BY pu.created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $teamId, $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Rule #5: Startup Verification
 * Check if sponsor is verified
 */
function isSponsorVerified($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT is_verified, badge_level FROM startup_verifications WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    $verification = $r ? $r->fetch_assoc() : null;
    return $verification ? (bool)$verification['is_verified'] : false;
}

/**
 * Check if sponsor can post challenge above threshold
 */
function canPostHighValueChallenge($userId, $amount) {
    global $conn;
    
    // Unverified sponsors cannot post above Rs. 25,000
    if ($amount > 25000 && !isSponsorVerified($userId)) {
        return false;
    }
    return true;
}

/**
 * Rule #8: Startup Commitment Deposit
 * Calculate escrow deposit (10-20% of challenge value)
 */
function calculateEscrowDeposit($challengeAmount) {
    global $conn;
    $setting = getSetting('escrow_deposit_percent', 10);
    $percent = min(20, max(10, (int)$setting));
    return $challengeAmount * ($percent / 100);
}

/**
 * Rule #3: First-Look Rights
 * Check if sponsor has first-look access to a challenge
 */
function hasFirstLookAccess($sponsorId, $challengeId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM first_look_access WHERE sponsor_id = ? AND challenge_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("ii", $sponsorId, $challengeId);
    $stmt->execute();
    $r = $stmt->get_result();
    return ($r && $r->num_rows > 0);
}

/**
 * Get sponsor's successful funded milestones count
 */
function getSponsorFundedMilestonesCount($sponsorId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM challenges WHERE sponsor_id = ? AND status = 'rewarded'");
    $stmt->bind_param("i", $sponsorId);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? (int)$r->fetch_assoc()['cnt'] : 0;
}

/**
 * Rule #10: Talent Pipeline - Check for Gold Badge eligibility
 */
function checkAndUpdateGoldBadge($teamId) {
    global $conn;
    $setting = getSetting('gold_badge_projects_required', 3);
    $required = (int)$setting;
    
    $stmt = $conn->prepare("SELECT projects_completed FROM teams WHERE id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $r = $stmt->get_result();
    $team = $r ? $r->fetch_assoc() : null;
    
    if ($team && $team['projects_completed'] >= $required && !$team['gold_badge']) {
        // Award gold badge
        $stmt = $conn->prepare("UPDATE teams SET gold_badge = 1 WHERE id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        
        // Update all team members
        $members = json_decode($team['members'] ?? '[]', true) ?: [];
        foreach ($members as $mid) {
            updateTrustScore($mid, 15, 'gold_badge_earned', 'Team earned Gold Badge after completing ' . $required . ' projects', $teamId, 'team');
        }
        return true;
    }
    return false;
}

/**
 * Rule #6: IP Protection - Create submission record
 */
function createIPSubmission($teamId, $userId, $submissionType, $title, $description = '', $fileUrl = null) {
    global $conn;
    
    // Generate hash for proof
    $ipHash = hash('sha256', $teamId . $userId . $title . time());
    
    $stmt = $conn->prepare("INSERT INTO ip_submissions (team_id, user_id, submission_type, title, description, file_url, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $teamId, $userId, $submissionType, $title, $description, $fileUrl, $ipHash);
    return $stmt->execute();
}

/**
 * Get user's IP submissions
 */
function getUserIPSubmissions($userId, $limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM ip_submissions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Rule #9: Mentor Validation - Check if challenge requires mentor
 */
function requiresMentorValidation($challengeId) {
    global $conn;
    $stmt = $conn->prepare("SELECT reward_amount, requires_mentor, assigned_mentor_id FROM challenges WHERE id = ?");
    $stmt->bind_param("i", $challengeId);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_assoc() : null;
}

/**
 * Check if challenge has valid mentor assigned
 */
function hasValidMentor($challengeId) {
    global $conn;
    $challenge = requiresMentorValidation($challengeId);
    if (!$challenge) return false;
    
    // Challenges above Rs. 100,000 require mentor
    if ($challenge['reward_amount'] >= 100000 && !$challenge['assigned_mentor_id']) {
        return false;
    }
    return true;
}

/**
 * Get top teams by trust score for featured section
 */
function getTopTeamsByTrust($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT t.*, u.name as leader_name, 
        (SELECT COUNT(*) FROM progress_updates WHERE team_id = t.id) as progress_count,
        (SELECT MAX(created_at) FROM progress_updates WHERE team_id = t.id) as last_update
        FROM teams t 
        JOIN users u ON t.leader_id = u.id 
        WHERE t.status = 'active' 
        ORDER BY t.rank_points DESC, t.trust_score DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get top problems by upvotes for first-look
 */
function getTopProblemsByUpvotes($limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as user_name, u.trust_score as poster_trust FROM problems p JOIN users u ON p.user_id = u.id WHERE p.status = 'open' ORDER BY p.upvotes DESC, p.created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Helper: Get platform setting
 */
function getSetting($key, $default = null) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

/**
 * Trust score badge class for display
 */
function getTrustBadgeClass($score) {
    if ($score >= 120) return 'bg-purple-100 text-purple-800 border-purple-300';
    if ($score >= 100) return 'bg-green-100 text-green-800 border-green-300';
    if ($score >= 80) return 'bg-blue-100 text-blue-800 border-blue-300';
    if ($score >= 60) return 'bg-amber-100 text-amber-800 border-amber-300';
    return 'bg-red-100 text-red-800 border-red-300';
}

/**
 * Trust score label
 */
function getTrustLabel($score) {
    if ($score >= 120) return 'Elite';
    if ($score >= 100) return 'Trusted';
    if ($score >= 80) return 'Verified';
    if ($score >= 60) return 'Active';
    return 'At Risk';
}

/**
 * Local impact categories for SDG alignment
 */
function getLocalImpactCategories() {
    return [
        'Employment Creation' => 'Employment Creation',
        'Education Improvement' => 'Education Improvement',
        'Agriculture Innovation' => 'Agriculture Innovation',
        'Tourism Development' => 'Tourism Development',
        'Environment Protection' => 'Environment Protection',
        'Digital Transformation' => 'Digital Transformation',
        'Healthcare Access' => 'Healthcare Access',
        'Infrastructure Development' => 'Infrastructure Development'
    ];
}

/**
 * Check compliance violation
 */
function reportComplianceViolation($userId, $violationType, $description, $evidenceUrl = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO compliance_violations (user_id, violation_type, description, evidence_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $violationType, $description, $evidenceUrl);
    $result = $stmt->execute();
    
    if ($result) {
        // Apply trust score penalty based on severity
        $penalty = 0;
        switch ($violationType) {
            case 'fraud':
            case 'hate_speech':
                $penalty = -50;
                break;
            case 'academic_cheating':
            case 'copyright_infringement':
                $penalty = -30;
                break;
            default:
                $penalty = -15;
        }
        updateTrustScore($userId, $penalty, 'compliance_violation', $violationType . ': ' . substr($description, 0, 50));
    }
    
    return $result;
}

/**
 * Milestone completed - award trust points
 */
function onMilestoneCompleted($userId, $milestoneId, $teamId) {
    updateTrustScore($userId, 5, 'milestone_completed', 'Completed milestone on time', $milestoneId, 'milestone');
    
    // Check if all milestones are complete
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as total, (SELECT COUNT(*) FROM team_milestones WHERE team_id = ? AND status = 'completed') as completed FROM team_milestones WHERE team_id = ?");
    $stmt->bind_param("ii", $teamId, $teamId);
    $stmt->execute();
    $r = $stmt->get_result();
    $counts = $r ? $r->fetch_assoc() : null;
    
    if ($counts && $counts['total'] == $counts['completed']) {
        // All milestones complete - project done
        $stmt = $conn->prepare("UPDATE teams SET projects_completed = projects_completed + 1, status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        
        // Check for gold badge
        checkAndUpdateGoldBadge($teamId);
    }
}

/**
 * Get sponsor progress data
 */
function getSponsorProgress($sponsorId) {
    global $conn;
    $sid = (int)$sponsorId;
    
    $result = [
        'solutions_funded' => 0,
        'total_reward_given' => 0,
        'problems_solved' => 0,
        'sponsorship_total' => 0,
        'total_commission_paid' => 0,
        'monthly_data' => []
    ];
    
    // Total solutions funded (count solutions from challenges created by this sponsor)
    $sql1 = "SELECT COUNT(*) as cnt, COALESCE(SUM(s.reward_net),0) as total, COALESCE(SUM(s.reward_commission),0) as comm "
          . "FROM challenges c "
          . "LEFT JOIN solutions s ON s.challenge_id = c.id AND s.status = 'rewarded' "
          . "WHERE c.sponsor_id = $sid";
    $r1 = $conn->query($sql1);
    if ($r1 && $r1->num_rows > 0) {
        $rw = $r1->fetch_assoc();
        $result['solutions_funded'] = (int)($rw['cnt'] ?: 0);
        $result['total_reward_given'] = (float)($rw['total'] ?: 0);
        $result['total_commission_paid'] = (float)($rw['comm'] ?: 0);
    }
    
    // Total problems solved (count rewarded solutions for challenges by this sponsor that have a problem_id)
    $sql2 = "SELECT COUNT(DISTINCT s.problem_id) as cnt "
          . "FROM challenges c "
          . "LEFT JOIN solutions s ON s.challenge_id = c.id AND s.status = 'rewarded' AND s.problem_id IS NOT NULL "
          . "WHERE c.sponsor_id = $sid";
    $r2 = $conn->query($sql2);
    if ($r2 && $r2->num_rows > 0) {
        $result['problems_solved'] = (int)($r2->fetch_assoc()['cnt'] ?: 0);
    }
    
    // Total sponsorship amount
    $sql3 = "SELECT COALESCE(SUM(amount),0) as total FROM sponsorships WHERE sponsor_id = $sid AND status = 'disbursed'";
    $r3 = $conn->query($sql3);
    if ($r3 && $r3->num_rows > 0) {
        $result['sponsorship_total'] = (float)($r3->fetch_assoc()['total'] ?: 0);
    }
    
    // Monthly activity
    $sql4 = "SELECT DATE_FORMAT(s.created_at, '%Y-%m') as month, COUNT(*) as count, COALESCE(SUM(s.reward_net),0) as amount "
          . "FROM challenges c "
          . "LEFT JOIN solutions s ON s.challenge_id = c.id AND s.status = 'rewarded' "
          . "WHERE c.sponsor_id = $sid AND s.created_at IS NOT NULL "
          . "GROUP BY month ORDER BY month DESC LIMIT 6";
    $r4 = $conn->query($sql4);
    if ($r4 && $r4->num_rows > 0) {
        $result['monthly_data'] = array_reverse($r4->fetch_all(MYSQLI_ASSOC));
    }
    
    return $result;
}

/**
 * Get users team (if any)
 */
function getUserTeam($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM teams WHERE leader_id = ? OR JSON_CONTAINS(members, JSON_ARRAY(?))");
    $membersJson = json_encode($userId);
    $stmt->bind_param("is", $userId, $membersJson);
    $stmt->execute();
    return ($r = $stmt->get_result()) ? $r->fetch_assoc() : null;
}

/**
 * Get team members
 */
function getTeamMembers($team) {
    if (!$team) return [];
    $memberIds = json_decode($team['members'] ?? '[]', true) ?: [];
    $members = [];
    foreach ($memberIds as $mid) {
        $user = getUserById($mid);
        if ($user) $members[] = $user;
    }
    return $members;
}

// ==========================================
// SOLUTION LIKES/DISLIKES SYSTEM
// ==========================================

/**
 * Get solution likes count
 */
function getSolutionLikes($solutionId) {
    global $conn;
    $stmt = $conn->prepare("SELECT likes FROM solutions WHERE id = ?");
    $stmt->bind_param("i", $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $sol = $r ? $r->fetch_assoc() : null;
    return $sol ? (int)$sol['likes'] : 0;
}

/**
 * Get solution dislikes count
 */
function getSolutionDislikes($solutionId) {
    global $conn;
    $stmt = $conn->prepare("SELECT dislikes FROM solutions WHERE id = ?");
    $stmt->bind_param("i", $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $sol = $r ? $r->fetch_assoc() : null;
    return $sol ? (int)$sol['dislikes'] : 0;
}

/**
 * Like a solution
 */
function likeSolution($userId, $solutionId) {
    global $conn;
    // Check if already liked
    $stmt = $conn->prepare("SELECT id FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'like'");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) return false; // Already liked
    
    // Check if previously disliked - remove dislike
    $stmt = $conn->prepare("SELECT id FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'dislike'");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        // Remove dislike
        $stmt = $conn->prepare("DELETE FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'dislike'");
        $stmt->bind_param("ii", $userId, $solutionId);
        $stmt->execute();
        // Decrement dislikes
        $conn->query("UPDATE solutions SET dislikes = GREATEST(0, dislikes - 1) WHERE id = $solutionId");
    }
    
    // Add like
    $stmt = $conn->prepare("INSERT INTO solution_likes (user_id, solution_id, type) VALUES (?, ?, 'like')");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    
    // Increment likes
    $conn->query("UPDATE solutions SET likes = likes + 1 WHERE id = $solutionId");
    
    // Update trust score of solution creator
    $stmt = $conn->prepare("SELECT user_id FROM solutions WHERE id = ?");
    $stmt->bind_param("i", $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $sol = $r ? $r->fetch_assoc() : null;
    if ($sol) {
        updateTrustScore($sol['user_id'], 2, 'solution_liked', 'Solution received a like', $solutionId, 'solution');
    }
    
    return true;
}

/**
 * Dislike a solution
 */
function dislikeSolution($userId, $solutionId) {
    global $conn;
    // Check if already disliked
    $stmt = $conn->prepare("SELECT id FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'dislike'");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) return false; // Already disliked
    
    // Check if previously liked - remove like
    $stmt = $conn->prepare("SELECT id FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'like'");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        // Remove like
        $stmt = $conn->prepare("DELETE FROM solution_likes WHERE user_id = ? AND solution_id = ? AND type = 'like'");
        $stmt->bind_param("ii", $userId, $solutionId);
        $stmt->execute();
        // Decrement likes
        $conn->query("UPDATE solutions SET likes = GREATEST(0, likes - 1) WHERE id = $solutionId");
    }
    
    // Add dislike
    $stmt = $conn->prepare("INSERT INTO solution_likes (user_id, solution_id, type) VALUES (?, ?, 'dislike')");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    
    // Increment dislikes
    $conn->query("UPDATE solutions SET dislikes = dislikes + 1 WHERE id = $solutionId");
    
    return true;
}

/**
 * Check if user has liked/disliked a solution
 */
function getUserSolutionVote($userId, $solutionId) {
    global $conn;
    $stmt = $conn->prepare("SELECT type FROM solution_likes WHERE user_id = ? AND solution_id = ?");
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $vote = $r ? $r->fetch_assoc() : null;
    return $vote ? $vote['type'] : null;
}

// ==========================================
// PROBLEM/SOLUTION APPROVAL SYSTEM
// ==========================================

/**
 * Approve a problem
 */
function approveProblem($problemId, $approverId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE problems SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $approverId, $problemId);
    return $stmt->execute();
}

/**
 * Reject a problem (mark as spam)
 */
function rejectProblem($problemId, $approverId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE problems SET is_approved = -1, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("iss", $approverId, $reason, $problemId);
    $result = $stmt->execute();
    
    if ($result) {
        // Get problem poster and penalize trust score
        $stmt = $conn->prepare("SELECT user_id FROM problems WHERE id = ?");
        $stmt->bind_param("i", $problemId);
        $stmt->execute();
        $r = $stmt->get_result();
        $problem = $r ? $r->fetch_assoc() : null;
        if ($problem) {
            updateTrustScore($problem['user_id'], -10, 'problem_rejected', 'Problem rejected as spam: ' . substr($reason, 0, 50), $problemId, 'problem');
        }
    }
    
    return $result;
}

/**
 * Approve a solution
 */
function approveSolution($solutionId, $approverId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE solutions SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $approverId, $solutionId);
    return $stmt->execute();
}

/**
 * Reject a solution (mark as spam)
 */
function rejectSolution($solutionId, $approverId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE solutions SET is_approved = -1, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("iss", $approverId, $reason, $solutionId);
    $result = $stmt->execute();
    
    if ($result) {
        // Get solution poster and penalize trust score
        $stmt = $conn->prepare("SELECT user_id FROM solutions WHERE id = ?");
        $stmt->bind_param("i", $solutionId);
        $stmt->execute();
        $r = $stmt->get_result();
        $solution = $r ? $r->fetch_assoc() : null;
        if ($solution) {
            updateTrustScore($solution['user_id'], -10, 'solution_rejected', 'Solution rejected as spam: ' . substr($reason, 0, 50), $solutionId, 'solution');
        }
    }
    
    return $result;
}

/**
 * Get pending problems for approval
 */
function getPendingProblems() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.name as reporter_name, u.trust_score as reporter_trust
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.is_approved = 0 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get pending solutions for approval
 */
function getPendingSolutions() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT s.*, u.name as solver_name, u.trust_score as solver_trust,
               p.title as problem_title, c.title as challenge_title
        FROM solutions s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN problems p ON s.problem_id = p.id
        LEFT JOIN challenges c ON s.challenge_id = c.id
        WHERE s.is_approved = 0 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

// ==========================================
// ADMIN USER MANAGEMENT
// ==========================================

/**
 * Get all users (for admin)
 */
function getAllUsers($limit = 50, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get users by role
 */
function getUsersByRole($role, $limit = 50) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("si", $role, $limit);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Suspend a user
 */
function suspendUser($userId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_active = 0, suspension_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $userId);
    return $stmt->execute();
}

/**
 * Activate a suspended user
 */
function activateUser($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_active = 1, suspension_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Send warning to user
 */
function sendWarning($userId, $message, $fromAdminId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_warnings (user_id, message, sent_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $message, $fromAdminId, $userId);
    $result = $stmt->execute();
    
    if ($result) {
        // Reduce trust score by 5 for receiving warning
        updateTrustScore($userId, -5, 'warning_received', 'Received warning from admin', null, 'warning');
    }
    
    return $result;
}

/**
 * Get user warnings
 */
function getUserWarnings($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT w.*, u.name as sent_by_name FROM user_warnings w JOIN users u ON w.sent_by = u.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Delete a user (admin action)
 */
function deleteUser($userId) {
    global $conn;
    // Get user's trust score for logging
    $user = getUserById($userId);
    if (!$user) return false;
    
    // Check if user has active teams/challenges
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM teams WHERE leader_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $r = $stmt->get_result();
    $hasTeams = $r ? (int)$r->fetch_assoc()['cnt'] : 0;
    
    if ($hasTeams > 0) {
        return ['error' => 'Cannot delete user with active teams'];
    }
    
    // Soft delete - just mark as inactive
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Search users
 */
function searchUsers($query) {
    global $conn;
    $searchTerm = '%' . $query . '%';
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get user count by role
 */
function getUserCountByRole() {
    global $conn;
    $result = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
    $counts = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row['role']] = (int)$row['cnt'];
        }
    }
    return $counts;
}

// ==========================================
// ADMIN WARNINGS TABLE (Create if not exists)
// ==========================================

function ensureWarningsTable() {
    global $conn;
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_warnings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            sent_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// Run on config load
ensureWarningsTable();

function ensureSolutionLikesTable() {
    global $conn;
    $conn->query("
        CREATE TABLE IF NOT EXISTS solution_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            solution_id INT NOT NULL,
            type ENUM('like', 'dislike') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (solution_id) REFERENCES solutions(id) ON DELETE CASCADE,
            UNIQUE KEY unique_vote (user_id, solution_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Add likes/dislikes columns if not exist
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0");
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS dislikes INT DEFAULT 0");
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS is_approved INT DEFAULT 0");
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL DEFAULT NULL");
    $conn->query("ALTER TABLE solutions ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE problems ADD COLUMN IF NOT EXISTS is_approved INT DEFAULT 0");
    $conn->query("ALTER TABLE problems ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
    $conn->query("ALTER TABLE problems ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL DEFAULT NULL");
    $conn->query("ALTER TABLE problems ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL");
}

// Run on config load
ensureSolutionLikesTable();

function getCategoryClass($cat) {
    $classes = [
        'Waste' => 'bg-green-100 text-green-800',
        'Road' => 'bg-gray-100 text-gray-800',
        'Health' => 'bg-red-100 text-red-800',
        'Water' => 'bg-blue-100 text-blue-800',
        'Education' => 'bg-purple-100 text-purple-800',
        'Electricity' => 'bg-yellow-100 text-yellow-800',
        'Security' => 'bg-red-100 text-red-900',
        'Corruption' => 'bg-orange-100 text-orange-800',
        'Other' => 'bg-gray-100 text-gray-800'
    ];
    return $classes[$cat] ?? 'bg-gray-100 text-gray-800';
}

function getUrgencyClass($urg) {
    $classes = [
        'High' => 'bg-red-500 text-white',
        'Medium' => 'bg-amber-500 text-white',
        'Low' => 'bg-green-100 text-green-800'
    ];
    return $classes[$urg] ?? 'bg-gray-100 text-gray-800';
}

function getStatusClass($status) {
    $classes = [
        'open' => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-amber-100 text-amber-800',
        'solved' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'rejected' => 'bg-red-100 text-red-800'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

function getTrustBadgeColor($score) {
    if ($score >= 120) return 'purple';
    if ($score >= 100) return 'green';
    if ($score >= 80) return 'blue';
    if ($score >= 60) return 'amber';
    return 'red';
}

function getSolutionStatusClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'implemented' => 'bg-blue-100 text-blue-800'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

function getHeader($title = 'CivicLedger') {
    $user = getCurrentUser();
    $roleNav = '';
    $mobileNav = '';
    if ($user) {
        $name = e($user['name']);
        $role = $user['role'];
        $navItems = [];
        switch ($role) {
            case 'student': $navItems = [['dashboard.php','Dashboard'],['team_formation.php','Teams'],['micro_gigs.php','Gigs'],['mentor_chat.php','Mentor Chat'],['governance.php','Governance'],['profile.php','Profile']]; break;
            case 'citizen': $navItems = [['dashboard.php','Dashboard'],['post_problem.php','Post Problem'],['post_gig.php','Post Gig'],['my_gigs.php','My Gigs'],['governance.php','Governance'],['profile.php','Profile']]; break;
            case 'mentor': $navItems = [['mentor_dashboard.php','Dashboard'],['mentor_messages.php','Inbox'],['team_leaderboard.php','Leaderboard'],['governance.php','Governance'],['profile.php','Profile']]; break;
            case 'sponsor': $navItems = [['sponsor_dashboard.php','Dashboard'],['create_challenge.php','Challenge'],['create_sponsorship.php','Sponsor'],['sponsor_progress.php','Analytics'],['sponsor_message_admin.php','Message Admin'],['governance.php','Governance'],['profile.php','Profile']]; break;
            case 'admin': $navItems = [['admin_dashboard.php','Dashboard'],['admin_chat_moderation.php','Moderation'],['admin_teams.php','Teams'],['admin_success_stories.php','Stories'],['admin_commission.php','Settings'],['governance.php','Governance']]; break;
            default: $navItems = [['dashboard.php','Dashboard'],['governance.php','Governance'],['profile.php','Profile']];
        }
        $desktopNav = ''; $mobileNavItems = '';
        foreach ($navItems as $item) {
            $desktopNav .= '<a href="'.$item[0].'" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">'.$item[1].'</a>';
            $mobileNavItems .= '<a href="'.$item[0].'" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">'.$item[1].'</a>';
        }
        $roleNav = $desktopNav.'<a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-user mr-1"></i>'.$name.'</a><a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>';
        $mobileNav = $mobileNavItems.'<a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a><a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>';
    } else {
        $roleNav = '<a href="login.php" class="px-4 py-2 text-white/80 hover:text-white">Login</a><a href="register.php" class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-gray-100 font-semibold">Sign Up</a>';
        $mobileNav = '<a href="login.php" class="block py-2 px-4 text-white/80 hover:bg-white/10 rounded-lg">Login</a><a href="register.php" class="block py-2 px-4 bg-white text-green-600 rounded-lg font-semibold">Sign Up</a>';
    }
    $flash = getFlash();
    $flashHtml = '';
    if ($flash) { $bg = $flash['type']==='success'?'bg-green-600':'bg-red-600'; $ic = $flash['type']==='success'?'check-circle':'exclamation-circle'; $flashHtml = '<div id="flashMsg" class="fixed top-20 right-6 z-50 p-4 rounded-xl shadow-2xl '.$bg.' text-white max-w-sm"><div class="flex items-center gap-3"><i class="fas fa-'.$ic.'"></i><span>'.e($flash['message']).'</span></div></div>'; }
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?=e($title)?> - CivicLedger</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><script>tailwind.config={theme:{extend:{colors:{primary:'#059669',secondary:'#047857'}}}}</script><style>body{font-family:'Inter',sans-serif}.gradient-bg{background:linear-gradient(135deg,#059669,#047857)}.card-hover:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.1);transition:all .3s}.glass-card{background:rgba(255,255,255,.9);backdrop-filter:blur(10px)}::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-track{background:#f1f1f1}::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:4px}.carousel-container{scroll-snap-type:x mandatory;overflow-x:auto}.carousel-container::-webkit-scrollbar{display:none}.carousel-item{scroll-snap-align:start;flex-shrink:0}</style></head><body class="bg-gray-50"><nav class="bg-white shadow-sm sticky top-0 z-50"><div class="max-w-7xl mx-auto px-4"><div class="flex justify-between items-center h-16"><div class="flex items-center space-x-3"><a href="index.php" class="flex items-center space-x-2"><div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center"><i class="fas fa-shield-alt text-white"></i></div><span class="font-bold text-xl text-gray-800">CivicLedger <span class="text-green-600">Nepal</span></span></a></div><div class="hidden md:flex items-center space-x-3"><?=$roleNav?></div><button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl"><i class="fas fa-bars"></i></button></div><div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2"><?=$mobileNav?></div></div></nav><?= $flashHtml ?><div class="max-w-7xl mx-auto px-4 py-8"><script>document.getElementById('mobileMenuBtn').addEventListener('click',function(){document.getElementById('mobileMenu').classList.toggle('hidden')});var fe=document.getElementById('flashMsg');if(fe)setTimeout(function(){fe.remove()},4000);</script><?php
}
function getFooter() {
    echo '</div></body></html>';
}

?>