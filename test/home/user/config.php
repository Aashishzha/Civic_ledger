<?php
/**
 * CivicLedger - Configuration & Helper Functions
 * Anti-Corruption Civic Platform for Nepal
 * Theme: Civic & Social Impact
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'elite4_nepal');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Create upload directories
@mkdir('uploads', 0777, true);
@mkdir('uploads/profiles', 0777, true);
@mkdir('uploads/voice', 0777, true);

// ============================================
// HELPER FUNCTIONS
// ============================================

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return getUserById($_SESSION['user_id']);
    }
    return null;
}

function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result();
        $user = $r->fetch_assoc();
        $stmt->close();
        return $user;
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $user = getCurrentUser();
    if (!$user) {
        header("Location: login.php");
        exit;
    }
    $roles = (array)$roles;
    if (!in_array($user['role'], $roles)) {
        header("Location: index.php");
        exit;
    }
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount ?? 0, 2);
}

function timeAgo($datetime) {
    if (!$datetime) return 'Unknown';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

function getInitials($name) {
    $words = explode(' ', $name ?? '');
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials ?: 'U';
}

function getCommissionPercent() {
    return 0;
}

function calculateCommission($gross) {
    return $gross * (getCommissionPercent() / 100);
}

function getUserSubscription($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active'");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $r = $stmt->get_result();
        $sub = $r->fetch_assoc();
        $stmt->close();
        return $sub;
    }
    return null;
}

function getStats() {
    global $conn;
    $stats = [];
    $tables = ['users', 'problems', 'solutions', 'teams', 'challenges'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        $stats[$table] = $result ? $result->fetch_assoc()['cnt'] : 0;
    }
    return $stats;
}

function getRecentProblems($limit = 6) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as reporter_name FROM problems p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $r = $stmt->get_result();
        $problems = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $problems;
    }
    return [];
}

function getSuccessStories($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM success_stories WHERE is_active = 1 ORDER BY id ASC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $r = $stmt->get_result();
        $stories = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $stories;
    }
    return [];
}

function uploadFile($fileInput, $directory = 'uploads/') {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fileInput];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp3', 'wav', 'webm'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $target = $directory . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $target;
    }
    return null;
}

// ============================================
// TRUST SCORE FUNCTIONS
// ============================================

function getTrustScore($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT trust_score FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();
        return $row ? ($row['trust_score'] ?? 100) : 100;
    }
    return 100;
}

function updateTrustScore($userId, $pointsChange, $actionType, $description = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET trust_score = GREATEST(0, COALESCE(trust_score, 100) + ?) WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $pointsChange, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->query("INSERT INTO trust_score_logs (user_id, points_change, action_type, description) VALUES ($userId, $pointsChange, '$actionType', '$description')");
        return true;
    }
    return false;
}

// ============================================
// CSS HELPER FUNCTIONS
// ============================================

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

function getTrustLabel($score) {
    if ($score >= 120) return 'Elite';
    if ($score >= 100) return 'Trusted';
    if ($score >= 80) return 'Verified';
    if ($score >= 60) return 'Active';
    return 'At Risk';
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

// ============================================
// LIKE/DISLIKE FUNCTIONS
// ============================================

function likeSolution($userId, $solutionId) {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS solution_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        solution_id INT NOT NULL,
        type ENUM('like','dislike') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_vote (user_id, solution_id)
    )");
    $stmt = $conn->prepare("SELECT id, type FROM solution_likes WHERE user_id = ? AND solution_id = ?");
    if (!$stmt) return ['success' => false, 'error' => 'Database error'];
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $existing = $r->fetch_assoc();
    if ($existing) {
        if ($existing['type'] === 'like') {
            $stmt = $conn->prepare("DELETE FROM solution_likes WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $conn->query("UPDATE solutions SET likes = GREATEST(0, likes - 1) WHERE id = $solutionId");
            return ['success' => true, 'action' => 'removed'];
        } else {
            $stmt = $conn->prepare("UPDATE solution_likes SET type = 'like' WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $conn->query("UPDATE solutions SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE id = $solutionId");
            return ['success' => true, 'action' => 'changed'];
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO solution_likes (user_id, solution_id, type) VALUES (?, ?, 'like')");
        $stmt->bind_param("ii", $userId, $solutionId);
        $stmt->execute();
        $conn->query("UPDATE solutions SET likes = likes + 1 WHERE id = $solutionId");
        return ['success' => true, 'action' => 'added'];
    }
}

function dislikeSolution($userId, $solutionId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, type FROM solution_likes WHERE user_id = ? AND solution_id = ?");
    if (!$stmt) return ['success' => false, 'error' => 'Database error'];
    $stmt->bind_param("ii", $userId, $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $existing = $r->fetch_assoc();
    if ($existing) {
        if ($existing['type'] === 'dislike') {
            $stmt = $conn->prepare("DELETE FROM solution_likes WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $conn->query("UPDATE solutions SET dislikes = GREATEST(0, dislikes - 1) WHERE id = $solutionId");
            return ['success' => true, 'action' => 'removed'];
        } else {
            $stmt = $conn->prepare("UPDATE solution_likes SET type = 'dislike' WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $conn->query("UPDATE solutions SET dislikes = dislikes + 1, likes = GREATEST(0, likes - 1) WHERE id = $solutionId");
            return ['success' => true, 'action' => 'changed'];
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO solution_likes (user_id, solution_id, type) VALUES (?, ?, 'dislike')");
        $stmt->bind_param("ii", $userId, $solutionId);
        $stmt->execute();
        $conn->query("UPDATE solutions SET dislikes = dislikes + 1 WHERE id = $solutionId");
        return ['success' => true, 'action' => 'added'];
    }
}

// ============================================
// ADMIN FUNCTIONS
// ============================================

function approveProblem($problemId, $approverId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE problems SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $approverId, $problemId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function rejectProblem($problemId, $approverId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE problems SET is_approved = -1, rejection_reason = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $reason, $problemId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function approveSolution($solutionId, $approverId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE solutions SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $approverId, $solutionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function rejectSolution($solutionId, $approverId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE solutions SET is_approved = -1, rejection_reason = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $reason, $solutionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function getPendingProblems() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as reporter_name FROM problems p LEFT JOIN users u ON p.user_id = u.id WHERE p.is_approved = 0 ORDER BY p.created_at DESC LIMIT 50");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $problems = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $problems;
    }
    return [];
}

function getPendingSolutions() {
    global $conn;
    $stmt = $conn->prepare("SELECT s.*, u.name as solver_name, p.title as problem_title FROM solutions s LEFT JOIN users u ON s.user_id = u.id LEFT JOIN problems p ON s.problem_id = p.id WHERE s.is_approved = 0 ORDER BY s.created_at DESC LIMIT 50");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $solutions = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $solutions;
    }
    return [];
}

function getAllUsers($limit = 50, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, trust_score, is_active, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
    if ($stmt) {
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $r = $stmt->get_result();
        $users = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }
    return [];
}

function getUsersByRole($role, $limit = 50) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, trust_score, is_active, created_at FROM users WHERE role = ? ORDER BY trust_score DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("si", $role, $limit);
        $stmt->execute();
        $r = $stmt->get_result();
        $users = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }
    return [];
}

function suspendUser($userId, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_active = 0, is_suspended = 1, suspended_at = NOW(), suspension_reason = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $reason, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function activateUser($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_active = 1, is_suspended = 0, suspended_at = NULL, suspension_reason = NULL WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function sendWarning($userId, $message, $fromAdminId) {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS user_warnings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        sent_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $conn->prepare("INSERT INTO user_warnings (user_id, message, sent_by) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isi", $userId, $message, $fromAdminId);
        $result = $stmt->execute();
        $stmt->close();
        updateTrustScore($userId, -5, 'warning', 'Received warning: ' . substr($message, 0, 50));
        return $result;
    }
    return false;
}

function getUserWarnings($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT w.*, u.name as admin_name FROM user_warnings w LEFT JOIN users u ON w.sent_by = u.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $r = $stmt->get_result();
        $warnings = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $warnings;
    }
    return [];
}

function deleteUser($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function searchUsers($query) {
    global $conn;
    $searchTerm = '%' . $query . '%';
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name LIMIT 20");
    if ($stmt) {
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $r = $stmt->get_result();
        $users = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }
    return [];
}

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

// ============================================
// TEMPLATE FUNCTIONS
// ============================================

function getHeader($title = 'CivicLedger') {
    $user = getCurrentUser();
    $roleNav = '';
    $mobileNav = '';
    
    if ($user) {
        $name = e($user['name']);
        $role = $user['role'];
        $navItems = [];
        switch ($role) {
            case 'student': 
                $navItems = [['student_dashboard.php','Dashboard'],['team_formation.php','Teams'],['micro_gigs.php','Gigs'],['mentor_chat.php','Mentor'],['profile.php','Profile']]; 
                break;
            case 'citizen': 
                $navItems = [['citizen_dashboard.php','Dashboard'],['post_problem.php','Report'],['profile.php','Profile']]; 
                break;
            case 'mentor': 
                $navItems = [['mentor_dashboard.php','Dashboard'],['mentor_messages.php','Messages'],['profile.php','Profile']]; 
                break;
            case 'sponsor': 
                $navItems = [['sponsor_dashboard.php','Dashboard'],['create_challenge.php','Challenge'],['profile.php','Profile']]; 
                break;
            case 'admin': 
                $navItems = [['admin_dashboard.php','Dashboard'],['admin_users.php','Users'],['admin_approval.php','Approvals'],['admin_teams.php','Teams']]; 
                break;
            default: 
                $navItems = [['dashboard.php','Dashboard'],['profile.php','Profile']];
        }
        
        $desktopNav = '';
        foreach ($navItems as $item) {
            $desktopNav .= '<a href="'.$item[0].'" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">'.$item[1].'</a>';
        }
        
        $roleNav = $desktopNav . '<a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-user mr-1"></i>'.$name.'</a><a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>';
        $mobileNav = '';
        foreach ($navItems as $item) {
            $mobileNav .= '<a href="'.$item[0].'" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">'.$item[1].'</a>';
        }
        $mobileNav .= '<a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a><a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>';
    } else {
        $roleNav = '<a href="login.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Login</a><a href="register.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">Sign Up</a>';
        $mobileNav = '<a href="login.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Login</a><a href="register.php" class="block py-2 px-4 bg-green-600 text-white rounded-lg font-semibold">Sign Up</a>';
    }
    
    $flash = getFlash();
    $flashHtml = '';
    if ($flash) {
        $bg = $flash['type']==='success'?'bg-green-600':'bg-red-600';
        $ic = $flash['type']==='success'?'check-circle':'exclamation-circle';
        $flashHtml = '<div id="flashMsg" class="fixed top-20 right-6 z-50 p-4 rounded-xl shadow-2xl '.$bg.' text-white max-w-sm"><div class="flex items-center gap-3"><i class="fas fa-'.$ic.'"></i><span>'.e($flash['message']).'</span></div></div>';
    }
    
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.e($title).' - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: "Inter", sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: all 0.3s; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50">
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    '.$roleNav.'
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                '.$mobileNav.'
            </div>
        </div>
    </nav>
    '.$flashHtml.'
    <div class="max-w-7xl mx-auto px-4 py-8">
        <script>
            document.getElementById("mobileMenuBtn").addEventListener("click", function() {
                document.getElementById("mobileMenu").classList.toggle("hidden");
            });
            var fe = document.getElementById("flashMsg");
            if (fe) setTimeout(function() { fe.remove(); }, 4000);
        </script>';
}

function getFooter() {
    echo '</div>
    <div class="text-center text-gray-500 text-sm py-8">
        © 2024 CivicLedger - Nepal\'s Anti-Corruption Civic Platform
    </div>
</body>
</html>';
}
?>