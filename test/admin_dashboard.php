<?php
/**
 * CivicLedger - Admin Dashboard
 * Full platform moderation with anti-corruption transparency
 */
require_once 'config.php';
requireRole(['admin', 'moderator']);

$user = getCurrentUser();
global $conn;
$isAdmin = $user['role'] === 'admin';

// Stats
$stats = getStats();

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM teams");
$stmt->execute();
$r = $stmt->get_result();
$totalTeams = $r ? ($r->fetch_assoc()['cnt']) : 0;

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE is_active = 1");
$stmt->execute();
$r = $stmt->get_result();
$totalUsers = $r ? ($r->fetch_assoc()['cnt']) : 0;

// Commission - showing 0% for civic platform
$commission = 0; // Always 0% for CivicLedger
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 500: '#059669', 600: '#047857' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50">

    <!-- Navigation -->
    <nav class="bg-gray-900 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <div>
                        <span class="font-bold text-xl text-white">CivicLedger</span>
                        <span class="text-xs text-green-400 block -mt-1">Admin Panel</span>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium">Dashboard</a>
                    <?php if ($isAdmin): ?>
                    <a href="admin_users.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-users mr-1"></i>Users</a>
                    <a href="admin_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-check-double mr-1"></i>Approvals</a>
                    <a href="admin_chat_moderation.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Moderation</a>
                    <a href="admin_teams.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                    <a href="admin_success_stories.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Stories</a>
                    <?php endif; ?>
                    <a href="public_ledger.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-book mr-1"></i>Ledger</a>
                    <a href="governance.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Governance</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-white text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="admin_dashboard.php" class="block py-2 px-4 bg-green-600 text-white rounded-lg">Dashboard</a>
                <?php if ($isAdmin): ?>
                <a href="admin_users.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-users mr-1"></i>Users</a>
                <a href="admin_approval.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-check-double mr-1"></i>Approvals</a>
                <a href="admin_chat_moderation.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Moderation</a>
                <a href="admin_teams.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                <a href="admin_success_stories.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Stories</a>
                <?php endif; ?>
                <a href="public_ledger.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Ledger</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-green-500/10 rounded-full -mr-32 -mt-32"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">CivicLedger Admin</h1>
                        <p class="text-gray-400">Welcome, <?= e($user['name']) ?> • Anti-corruption platform control</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex items-center gap-3">
                    <span class="px-4 py-2 bg-green-500/30 rounded-full text-sm">
                        <i class="fas fa-check-circle mr-2 text-green-400"></i>Commission: 0%
                    </span>
                    <a href="public_ledger.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all">
                        <i class="fas fa-book mr-2"></i>View Ledger
                    </a>
                </div>
            </div>
        </div>

        <!-- Anti-Corruption Notice -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white rounded-xl p-4 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-2xl"></i>
                <div>
                    <p class="font-bold">100% Transparent Civic Platform</p>
                    <p class="text-green-100 text-sm">Every transaction is public. Every action is logged. 0% commission on all civic projects.</p>
                </div>
            </div>
            <a href="governance.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium">
                View Governance <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Platform Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Civic Problems</p>
                        <p class="text-3xl font-bold text-gray-800"><?= $stats['problems'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solutions Delivered</p>
                        <p class="text-3xl font-bold text-green-600"><?= $stats['solutions'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active Teams</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $totalTeams ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Users</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $totalUsers ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-friends text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Admin Actions Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="admin_chat_moderation.php" class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-blue-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-comments text-2xl text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Chat Moderation</h3>
                        <p class="text-sm text-gray-500">Review team messages</p>
                    </div>
                </div>
            </a>

            <a href="admin_teams.php" class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-purple-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users-cog text-2xl text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Team Management</h3>
                        <p class="text-sm text-gray-500">Assign mentors</p>
                    </div>
                </div>
            </a>

            <a href="admin_success_stories.php" class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-green-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-trophy text-2xl text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Success Stories</h3>
                        <p class="text-sm text-gray-500">Manage impact stories</p>
                    </div>
                </div>
            </a>

            <a href="public_ledger.php" class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-green-600">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-book text-2xl text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Transparency</h3>
                        <p class="text-sm text-gray-500">View financial ledger</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Team Work Progress Section -->
        <?php
        $stmt = $conn->prepare("
            SELECT t.*, u.name as leader_name,
            (SELECT COUNT(*) FROM team_milestones WHERE team_id = t.id) as total_milestones,
            (SELECT COUNT(*) FROM team_milestones WHERE team_id = t.id AND status = 'completed') as completed_milestones
            FROM teams t
            JOIN users u ON t.leader_id = u.id
            WHERE t.status = 'active'
            ORDER BY t.rank_points DESC
            LIMIT 10
        ");
        $stmt->execute();
        $r = $stmt->get_result();
        $allTeams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        ?>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-chart-line mr-2 text-green-600"></i>Team Progress (Proof of Progress)</h2>
                <a href="admin_teams.php" class="text-green-600 hover:underline text-sm">View All Teams</a>
            </div>

            <?php if (empty($allTeams)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                <p>No active teams yet.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-sm text-gray-500">
                            <th class="px-4 py-3">Team</th>
                            <th class="px-4 py-3">Leader</th>
                            <th class="px-4 py-3">Points</th>
                            <th class="px-4 py-3">Milestones</th>
                            <th class="px-4 py-3">Progress</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($allTeams as $team): ?>
                        <?php 
                        $total = $team['total_milestones'] ?: 1;
                        $completed = $team['completed_milestones'] ?: 0;
                        $progress = round(($completed / $total) * 100);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="team_progress.php?team_id=<?= $team['id'] ?>" class="font-semibold text-green-600 hover:underline">
                                        <?= e($team['name']) ?>
                                    </a>
                                    <?php if ($team['gold_badge']): ?>
                                    <span class="px-2 py-0.5 bg-amber-400 text-white rounded text-xs font-bold">
                                        <i class="fas fa-award"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-gray-600"><?= e($team['leader_name']) ?></td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm font-bold">
                                    <?= $team['rank_points'] ?? 0 ?> pts
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                <span class="text-green-600"><?= $completed ?></span> / <?= $total ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500"><?= $progress ?>%</span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($team['is_inactive']): ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">Inactive</span>
                                <?php else: ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Solutions -->
        <?php
        $stmt = $conn->prepare("
            SELECT s.*, u.name as solver_name, p.title as problem_title, c.title as challenge_title
            FROM solutions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN problems p ON s.problem_id = p.id
            LEFT JOIN challenges c ON s.challenge_id = c.id
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $r = $stmt->get_result();
        $recentSolutions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        ?>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-lightbulb mr-2 text-green-600"></i>Recent Solutions</h2>
            </div>

            <?php if (empty($recentSolutions)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                <p>No solutions submitted yet.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recentSolutions as $sol): ?>
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800"><?= e($sol['title']) ?></h4>
                        <p class="text-sm text-gray-500">
                            By <?= e($sol['solver_name']) ?> 
                            <?php if ($sol['problem_title']): ?> for "<?= e($sol['problem_title']) ?>"<?php endif; ?>
                            <?php if ($sol['challenge_title']): ?> on "<?= e($sol['challenge_title']) ?>"<?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?= $sol['status'] === 'rewarded' ? 'bg-green-100 text-green-800' : 
                               ($sol['status'] === 'approved' ? 'bg-blue-100 text-blue-800' : 
                               ($sol['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) ?>">
                            <?= ucfirst($sol['status']) ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1"><?= timeAgo($sol['created_at']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Recent Admin Messages -->
        <?php
        $stmt = $conn->prepare("SELECT am.*, u.name as sender_name, u.role as sender_role FROM admin_messages am JOIN users u ON am.sender_id = u.id WHERE am.is_read = 0 ORDER BY am.created_at DESC LIMIT 5");
        $stmt->execute();
        $r = $stmt->get_result();
        $unreadMessages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        ?>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-envelope mr-2 text-green-600"></i>Sponsor Messages</h2>
                <?php if (!empty($unreadMessages)): ?>
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm"><?= count($unreadMessages) ?> new</span>
                <?php endif; ?>
            </div>

            <?php if (empty($unreadMessages)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                <p>No unread messages.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($unreadMessages as $msg): ?>
                <div class="border border-green-200 rounded-xl p-4 hover:bg-green-50 transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold"><?= e($msg['sender_name']) ?></span>
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs"><?= ucfirst(e($msg['sender_role'])) ?></span>
                            </div>
                            <h4 class="font-medium text-gray-800"><?= e($msg['subject']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1"><?= e(substr($msg['message'], 0, 100)) ?>...</p>
                        </div>
                        <span class="text-xs text-gray-400"><?= timeAgo($msg['created_at']) ?></span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="api_sponsor_message.php" class="inline">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="action" value="read">
                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-check mr-1"></i>Mark as Read
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="public_heatmap.php" class="bg-gradient-to-r from-red-500 to-orange-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                <i class="fas fa-map-marked-alt text-2xl mb-2"></i>
                <p class="text-sm font-semibold">Problem Map</p>
            </a>
            <a href="public_ledger.php" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                <i class="fas fa-book text-2xl mb-2"></i>
                <p class="text-sm font-semibold">Ledger</p>
            </a>
            <a href="team_leaderboard.php" class="bg-gradient-to-r from-purple-500 to-violet-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                <i class="fas fa-trophy text-2xl mb-2"></i>
                <p class="text-sm font-semibold">Leaderboard</p>
            </a>
            <a href="index.php" class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                <i class="fas fa-globe text-2xl mb-2"></i>
                <p class="text-sm font-semibold">View Site</p>
            </a>
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