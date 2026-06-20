<?php
/**
 * CivicLedger - Mentor Dashboard
 * Civic & Social Impact Platform
 */
require_once 'config.php';
requireRole('mentor');

$user = getCurrentUser();
global $conn;

// Get dashboard stats with error handling
$stats = [
    'total_teams' => 0,
    'total_solutions' => 0,
    'total_problems' => 0,
    'unread_messages' => 0,
    'pending_approvals' => 0,
    'recent_activity' => []
];

// Get total teams
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM teams");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $stats['total_teams'] = $r ? $r->fetch_assoc()['cnt'] : 0;
    }
} catch (Exception $e) {
    error_log("Error getting teams count: " . $e->getMessage());
}

// Get total solutions
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM solutions");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $stats['total_solutions'] = $r ? $r->fetch_assoc()['cnt'] : 0;
    }
} catch (Exception $e) {
    error_log("Error getting solutions count: " . $e->getMessage());
}

// Get total problems
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM problems");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $stats['total_problems'] = $r ? $r->fetch_assoc()['cnt'] : 0;
    }
} catch (Exception $e) {
    error_log("Error getting problems count: " . $e->getMessage());
}

// Get unread messages count
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE receiver_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $stats['unread_messages'] = $r ? $r->fetch_assoc()['cnt'] : 0;
    }
} catch (Exception $e) {
    error_log("Error getting unread messages: " . $e->getMessage());
}

// Get pending approvals
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM solutions WHERE is_approved = 0");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $stats['pending_approvals'] = $r ? $r->fetch_assoc()['cnt'] : 0;
    }
} catch (Exception $e) {
    error_log("Error getting pending approvals: " . $e->getMessage());
}

// Get recent problems for heatmap
$recentProblems = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.name as reporter_name, u.trust_score,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.id) as solution_count
        FROM problems p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $recentProblems = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
} catch (Exception $e) {
    error_log("Error getting recent problems: " . $e->getMessage());
}

// Get top teams by trust score
$topTeams = [];
try {
    $stmt = $conn->prepare("
        SELECT t.*, 
               COUNT(DISTINCT tm.user_id) as member_count,
               COALESCE(AVG(u.trust_score), 100) as avg_trust
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        LEFT JOIN users u ON tm.user_id = u.id
        GROUP BY t.id
        ORDER BY avg_trust DESC
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->execute();
        $r = $stmt->get_result();
        $topTeams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
} catch (Exception $e) {
    error_log("Error getting top teams: " . $e->getMessage());
}

// Get success stories
$successStories = getSuccessStories(3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 500: '#059669', 600: '#047857', 700: '#065f46' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: scale(1.02); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50 min-h-screen">

    <!-- Navigation - Civic Green Theme -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="mentor_dashboard.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="mentor_messages.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg relative">
                        <i class="fas fa-envelope mr-2"></i>Messages
                        <?php if ($stats['unread_messages'] > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center"><?= $stats['unread_messages'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="mentor_chat.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-users mr-2"></i>Student Chat
                    </a>
                    <a href="team_leaderboard.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-trophy mr-2"></i>Leaderboard
                    </a>
                    <a href="governance.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-balance-scale mr-2"></i>Governance
                    </a>
                    <a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-user mr-2"></i>Profile
                    </a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="mentor_dashboard.php" class="block py-2 px-4 bg-green-50 text-green-700 rounded-lg">Dashboard</a>
                <a href="mentor_messages.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">
                    Messages <?php if ($stats['unread_messages'] > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $stats['unread_messages'] ?></span><?php endif; ?>
                </a>
                <a href="mentor_chat.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Student Chat</a>
                <a href="team_leaderboard.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Leaderboard</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-green-700 to-emerald-600 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-user-graduate text-3xl"></i>
                        <h1 class="text-3xl font-bold">Welcome, <?= e($user['name']) ?>!</h1>
                    </div>
                    <p class="text-green-100 mb-4">Guide the next generation of problem solvers. Your mentorship matters!</p>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full">
                        <i class="fas fa-star text-amber-400"></i>
                        <span class="text-sm">Mentor Account</span>
                    </div>
                </div>
                <a href="mentor_messages.php" class="mt-4 md:mt-0 bg-white text-green-700 font-bold py-3 px-6 rounded-full hover:bg-green-50 transition-all flex items-center gap-2">
                    <i class="fas fa-envelope"></i>
                    <?= $stats['unread_messages'] > 0 ? 'You have ' . $stats['unread_messages'] . ' unread messages' : 'Check Messages' ?>
                </a>
            </div>
        </div>

        <!-- Anti-Corruption Banner -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white rounded-xl p-4 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-2xl"></i>
                <div>
                    <p class="font-bold">100% Transparent Platform</p>
                    <p class="text-green-100 text-sm">Every solution is tracked. Every team is accountable.</p>
                </div>
            </div>
            <a href="public_ledger.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium">
                View Ledger <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg stat-card border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Teams</p>
                        <p class="text-3xl font-bold text-gray-800"><?= $stats['total_teams'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-lg stat-card border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solutions</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $stats['total_solutions'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-lightbulb text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-lg stat-card border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Problems</p>
                        <p class="text-3xl font-bold text-red-600"><?= $stats['total_problems'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-lg stat-card border-l-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Approval</p>
                        <p class="text-3xl font-bold text-amber-600"><?= $stats['pending_approvals'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-6"><i class="fas fa-bolt mr-2 text-green-600"></i>Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="mentor_messages.php" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-envelope text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">View Messages</p>
                </a>
                <a href="mentor_chat.php" class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-comments text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Student Chat</p>
                </a>
                <a href="public_heatmap.php" class="bg-gradient-to-r from-red-500 to-orange-600 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-map-marked-alt text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Problem Map</p>
                </a>
                <a href="public_ledger.php" class="bg-gradient-to-r from-purple-500 to-violet-600 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-book text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Transparency</p>
                </a>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Problems -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold"><i class="fas fa-fire mr-2 text-red-500"></i>Recent Civic Problems</h2>
                    <a href="public_heatmap.php" class="text-green-600 hover:underline text-sm font-medium">View All</a>
                </div>

                <?php if (empty($recentProblems)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                    <p>No problems reported yet.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recentProblems as $problem): ?>
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-green-400 transition-all">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getCategoryClass($problem['category'] ?? 'Other') ?>">
                                        <?= e($problem['category'] ?? 'Other') ?>
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getUrgencyClass($problem['urgency'] ?? 'Medium') ?>">
                                        <?= e($problem['urgency'] ?? 'Medium') ?>
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-800"><?= e($problem['title'] ?? 'Untitled') ?></h3>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    <span><i class="fas fa-user mr-1"></i><?= e($problem['reporter_name'] ?? 'Anonymous') ?></span>
                                    <span><i class="fas fa-lightbulb mr-1"></i><?= $problem['solution_count'] ?? 0 ?> solutions</span>
                                    <span><i class="fas fa-clock mr-1"></i><?= timeAgo($problem['created_at'] ?? '') ?></span>
                                </div>
                            </div>
                            <a href="problem_detail.php?id=<?= $problem['id'] ?? 0 ?>" class="text-green-600 hover:text-green-800 ml-4">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Teams -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold"><i class="fas fa-trophy mr-2 text-amber-500"></i>Top Performing Teams</h2>
                    <a href="team_leaderboard.php" class="text-green-600 hover:underline text-sm font-medium">View All</a>
                </div>

                <?php if (empty($topTeams)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-users text-5xl mb-4 text-gray-300"></i>
                    <p>No teams registered yet.</p>
                </div>
                <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($topTeams as $index => $team): ?>
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-green-400 transition-all bg-gradient-to-r from-gray-50 to-green-50">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white font-bold text-lg">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800"><?= e($team['name'] ?? 'Team') ?></h4>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                                    <span><i class="fas fa-users mr-1"></i><?= $team['member_count'] ?? 0 ?> members</span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-shield-alt text-purple-600 mr-1"></i>
                                        <span class="text-purple-600 font-semibold"><?= round($team['avg_trust'] ?? 100) ?></span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <?php if ($team['badge'] ?? ''): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-medal text-yellow-600 mr-1"></i><?= e($team['badge']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success Stories Carousel -->
        <?php if (!empty($successStories)): ?>
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-star mr-2 text-amber-500"></i>Success Stories</h2>
                <a href="public_ledger.php" class="text-green-600 hover:underline text-sm font-medium">View All</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($successStories as $story): ?>
                <div class="border border-green-200 rounded-xl p-6 bg-gradient-to-br from-green-50 to-emerald-50 card-hover transition-all">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-trophy text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800"><?= e($story['title'] ?? 'Success Story') ?></h3>
                            <p class="text-sm text-gray-500"><?= e($story['team_name'] ?? 'Anonymous Team') ?></p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4"><?= e(substr($story['description'] ?? '', 0, 100)) ?>...</p>
                    <?php if (!empty($story['impact_metrics'])): ?>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                            <i class="fas fa-chart-line mr-1"></i><?= e($story['impact_metrics']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>&copy; 2024 CivicLedger - Nepal's Anti-Corruption Civic Platform</p>
        </div>
    </div>

    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="fixed bottom-6 right-6 z-50 p-4 rounded-xl shadow-2xl <?= $flash['type'] === 'success' ? 'bg-green-600' : 'bg-red-600' ?> text-white">
        <div class="flex items-center gap-3">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <span><?= e($flash['message']) ?></span>
        </div>
    </div>
    <script>setTimeout(() => { document.querySelector('.fixed.bottom-6').remove(); }, 4000);</script>
    <?php endif; ?>

    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    </script>

</body>
</html>