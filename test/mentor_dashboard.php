<?php
/**
 * CivicLedger - Mentor Dashboard (Civic Green Theme)
 */
require_once 'config.php';
requireRole('mentor');

$user = getCurrentUser();
global $conn;

$stats = getStats();

// Get my assigned teams
$stmt = $conn->prepare("
    SELECT t.*, u.name as leader_name,
    (SELECT COUNT(*) FROM team_milestones WHERE team_id = t.id) as total_milestones,
    (SELECT COUNT(*) FROM team_milestones WHERE team_id = t.id AND status = 'completed') as completed_milestones
    FROM mentor_assignments ma
    JOIN teams t ON ma.team_id = t.id
    JOIN users u ON t.leader_id = u.id
    WHERE ma.mentor_id = ?
    ORDER BY t.created_at DESC
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$assignedTeams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get my unread messages count
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE receiver_id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$unreadMessages = $r ? $r->fetch_assoc()['cnt'] : 0;

// Get success stories
$successStories = getSuccessStories(5);
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
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Mentor Panel</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="mentor_dashboard.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">Dashboard</a>
                    <a href="mentor_messages.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg relative">
                        <i class="fas fa-inbox mr-1"></i>Inbox
                        <?php if ($unreadMessages > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="team_chat.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-comments mr-1"></i>Team Chat</a>
                    <a href="mentor_problem_approval.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-file-alt mr-1"></i>Problems</a>
                    <a href="mentor_solution_approval.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-check mr-1"></i>Solutions</a>
                    <a href="team_leaderboard.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-trophy mr-1"></i>Leaderboard</a>
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="border-l border-gray-300 pl-4 ml-4">
                        <a href="admin_content_approval.php" class="px-4 py-2 text-gray-600 hover:bg-orange-50 rounded-lg"><i class="fas fa-file-alt mr-1"></i>Content</a>
                        <a href="admin_solution_approval.php" class="px-4 py-2 text-gray-600 hover:bg-orange-50 rounded-lg"><i class="fas fa-check mr-1"></i>Solutions</a>
                    </div>
                    <?php endif; ?>
                    <a href="governance.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-gavel mr-1"></i>Governance</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="mentor_dashboard.php" class="block py-2 px-4 bg-green-50 text-green-700 rounded-lg font-medium"><i class="fas fa-chart-line mr-2"></i>Dashboard</a>
                <a href="mentor_messages.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-inbox mr-2"></i>Inbox <?= $unreadMessages > 0 ? '('.$unreadMessages.')' : '' ?></a>
                <a href="team_chat.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-comments mr-2"></i>Team Chat</a>
                <a href="mentor_problem_approval.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-file-alt mr-2"></i>Problem Review</a>
                <a href="mentor_solution_approval.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-check mr-2"></i>Solution Review</a>
                <a href="team_leaderboard.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-trophy mr-2"></i>Leaderboard</a>
                <?php if ($user['role'] === 'admin'): ?>
                <hr class="my-2">
                <a href="admin_content_approval.php" class="block py-2 px-4 text-gray-600 hover:bg-orange-50 rounded-lg"><i class="fas fa-file-alt mr-2"></i>Content Review</a>
                <a href="admin_solution_approval.php" class="block py-2 px-4 text-gray-600 hover:bg-orange-50 rounded-lg"><i class="fas fa-check mr-2"></i>Solution Approval</a>
                <?php endif; ?>
                <a href="governance.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-gavel mr-2"></i>Governance</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-700 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-chalkboard-teacher text-3xl"></i>
                        <h1 class="text-3xl font-bold">Welcome, <?= e($user['name']) ?>!</h1>
                    </div>
                    <p class="text-green-100 mb-4">Guide teams to success and make a civic impact.</p>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 px-3 py-1 bg-white/20 rounded-full">
                            <i class="fas fa-shield-alt"></i>
                            <span>Trust: <strong><?= $user['trust_score'] ?? 100 ?></strong></span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1 bg-amber-500/30 rounded-full">
                            <i class="fas fa-users"></i>
                            <span><?= count($assignedTeams) ?> Teams</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="mentor_messages.php" class="inline-block bg-white/20 hover:bg-white/30 text-white font-bold py-3 px-6 rounded-full transition-all">
                        <i class="fas fa-envelope mr-2"></i>View Messages
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Assigned Teams</p>
                        <p class="text-3xl font-bold text-blue-600"><?= count($assignedTeams) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Unread Messages</p>
                        <p class="text-3xl font-bold text-green-600"><?= $unreadMessages ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-envelope text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solutions Approved</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $stats['solutions'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all border-l-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Problems Solved</p>
                        <p class="text-3xl font-bold text-amber-600"><?= $stats['problems'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trophy text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="mentor_messages.php" class="bg-gradient-to-br from-teal-500 to-green-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-envelope text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Messages</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Student Inbox</h3>
                <p class="text-teal-100 text-sm mb-4">Respond to inquiries</p>
                <div class="text-2xl font-bold"><?= $unreadMessages ?> <span class="text-sm text-teal-100">unread</span></div>
            </a>

            <a href="team_chat.php" class="bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-comments text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Teams</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Team Chat</h3>
                <p class="text-blue-100 text-sm mb-4">Team communication</p>
                <div class="text-2xl font-bold"><?= count($assignedTeams) ?> <span class="text-sm text-blue-100">teams</span></div>
            </a>

            <a href="team_leaderboard.php" class="bg-gradient-to-br from-purple-500 to-pink-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-trophy text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Leaderboard</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Rankings</h3>
                <p class="text-purple-100 text-sm mb-4">Team performance</p>
                <div class="text-2xl font-bold"><i class="fas fa-star text-yellow-300"></i></div>
            </a>

            <?php if ($user['role'] === 'admin'): ?>
            <a href="admin_content_approval.php" class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-file-alt text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Admin</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Content Review</h3>
                <p class="text-orange-100 text-sm mb-4">Approve content</p>
                <div class="text-2xl font-bold"><i class="fas fa-check"></i></div>
            </a>

            <a href="admin_solution_approval.php" class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-lightbulb text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Admin</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Solutions</h3>
                <p class="text-orange-100 text-sm mb-4">Review solutions</p>
                <div class="text-2xl font-bold"><i class="fas fa-check-circle"></i></div>
            </a>

            <a href="mentor_problem_approval.php" class="bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-file-alt text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Mentor</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Problem Review</h3>
                <p class="text-blue-100 text-sm mb-4">Review & approve problems</p>
                <div class="text-2xl font-bold"><i class="fas fa-check-double"></i></div>
            </a>

            <a href="mentor_solution_approval.php" class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-lightbulb text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Mentor</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Solution Review</h3>
                <p class="text-emerald-100 text-sm mb-4">Review & approve solutions</p>
                <div class="text-2xl font-bold"><i class="fas fa-trophy"></i></div>
            </a>

            <a href="team_progress.php" class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-chart-line text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Progress</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Team Progress</h3>
                <p class="text-green-100 text-sm mb-4">View milestones & updates</p>
                <div class="text-2xl font-bold"><i class="fas fa-tasks"></i></div>
            </a>

            <a href="governance.php" class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all card-hover">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-gavel text-3xl opacity-80"></i>
                    <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Rules</span>
                </div>
                <h3 class="text-lg font-bold mb-2">Governance</h3>
                <p class="text-indigo-100 text-sm mb-4">Platform rules</p>
                <div class="text-2xl font-bold"><i class="fas fa-shield-alt"></i></div>
            </a>
            <?php endif; ?>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-users mr-2 text-green-600"></i>My Assigned Teams</h2>
            </div>

            <?php if (empty($assignedTeams)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-users text-5xl mb-4 text-gray-300"></i>
                <p class="text-lg">No teams assigned to you yet.</p>
                <p class="text-sm">Admin will assign teams for you to mentor.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($assignedTeams as $team): ?>
                <?php 
                $progress = $team['total_milestones'] > 0 ? round(($team['completed_milestones'] / $team['total_milestones']) * 100) : 0;
                ?>
                <div class="border border-gray-200 rounded-xl p-4 hover:border-green-400 transition-all bg-gradient-to-r from-green-50 to-emerald-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-xl text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800"><?= e($team['name']) ?></h3>
                                <p class="text-sm text-gray-500">Leader: <?= e($team['leader_name']) ?></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $team['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                            <?= ucfirst($team['status']) ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-3">
                        <div class="flex-1">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600"><?= $progress ?>%</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-tasks mr-1"></i><?= $team['completed_milestones'] ?>/<?= $team['total_milestones'] ?> milestones completed
                        </span>
                        <a href="team_progress.php?team_id=<?= $team['id'] ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-all">
                            <i class="fas fa-chart-line mr-1"></i>View Progress
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Success Stories Preview -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><i class="fas fa-star mr-2 text-amber-500"></i>Platform Success Stories</h2>
            </div>

            <?php if (!empty($successStories)): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($successStories, 0, 3) as $story): ?>
                <div class="border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4">
                    <h3 class="font-bold text-gray-800 mb-2"><?= e(substr($story['title'], 0, 60)) ?></h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= e(substr($story['story'], 0, 100)) ?>...</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500"><?= e($story['author_name']) ?></span>
                        <?php if ($story['reward_amount'] > 0): ?>
                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-semibold">
                            <i class="fas fa-coins mr-1"></i><?= formatCurrency($story['reward_amount']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-star text-4xl mb-3 text-gray-300"></i>
                <p>No success stories yet.</p>
            </div>
            <?php endif; ?>
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