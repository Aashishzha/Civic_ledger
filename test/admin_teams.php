<?php
/**
 * CivicLedger - Admin Team Management
 */
require_once 'config.php';
requireRole('admin');

$user = getCurrentUser();
global $conn;

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assign moderator
    if (isset($_POST['assign_moderator'])) {
        $teamId = (int)$_POST['team_id'];
        $moderatorId = (int)$_POST['moderator_id'];
        $stmt = $conn->prepare("DELETE FROM moderator_assignments WHERE team_id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $stmt = $conn->prepare("INSERT INTO moderator_assignments (team_id, user_id, assigned_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $teamId, $moderatorId, $user['id']);
        $stmt->execute();
        setFlash('success', 'Moderator assigned successfully!');
        header("Location: admin_teams.php");
        exit;
    }

    // Assign mentor
    if (isset($_POST['assign_mentor'])) {
        $teamId = (int)$_POST['team_id'];
        $mentorId = (int)$_POST['mentor_id'];
        $stmt = $conn->prepare("UPDATE teams SET mentor_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $mentorId, $teamId);
        $stmt->execute();
        setFlash('success', 'Mentor assigned successfully!');
        header("Location: admin_teams.php");
        exit;
    }

    // Archive team
    if (isset($_POST['archive_team'])) {
        $teamId = (int)$_POST['team_id'];
        $stmt = $conn->prepare("UPDATE teams SET status = 'archived' WHERE id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        setFlash('success', 'Team archived successfully!');
        header("Location: admin_teams.php");
        exit;
    }

    // Unarchive team
    if (isset($_POST['unarchive_team'])) {
        $teamId = (int)$_POST['team_id'];
        $stmt = $conn->prepare("UPDATE teams SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        setFlash('success', 'Team restored successfully!');
        header("Location: admin_teams.php");
        exit;
    }
}

// Get teams with filters
$where = "1=1";
$params = [];
$types = '';

if ($filter === 'active') $where .= " AND t.status = 'active'";
elseif ($filter === 'archived') $where .= " AND t.status = 'archived'";
elseif ($filter === 'needs_mentor') $where .= " AND t.mentor_id IS NULL";

if (!empty($search)) {
    $where .= " AND t.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$sql = "SELECT t.*, 
               u.name as leader_name, u.email as leader_email,
               m.name as mentor_name,
               (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count,
               (SELECT COUNT(*) FROM team_milestones WHERE team_id = t.id) as milestone_count
        FROM teams t
        LEFT JOIN users u ON t.leader_id = u.id
        LEFT JOIN users m ON t.mentor_id = m.id
        WHERE $where ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$r = $stmt->get_result();
$teams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get mentors for dropdown
$qm = $conn->query("SELECT id, name, email FROM users WHERE role = 'mentor' ORDER BY name"); $mentors = $qm ? $qm->fetch_all(MYSQLI_ASSOC) : [];

// Get all moderators
$qmo = $conn->query("SELECT id, name, email FROM users WHERE role = 'mentor' ORDER BY name"); $moderators = $qmo ? $qmo->fetch_all(MYSQLI_ASSOC) : [];

// Get platform stats
$stats = [];
$qs1 = $conn->query("SELECT COUNT(*) as c FROM teams"); $stats['total'] = $qs1 ? (int)$qs1->fetch_assoc()['c'] : 0;
$qs2 = $conn->query("SELECT COUNT(*) as c FROM teams WHERE status = 'active'"); $stats['active'] = $qs2 ? (int)$qs2->fetch_assoc()['c'] : 0;
$qs3 = $conn->query("SELECT COUNT(*) as c FROM teams WHERE status = 'archived'"); $stats['archived'] = $qs3 ? (int)$qs3->fetch_assoc()['c'] : 0;
$qs4 = $conn->query("SELECT COUNT(*) as c FROM teams WHERE mentor_id IS NULL AND status = 'active'"); $stats['needs_mentor'] = $qs4 ? (int)$qs4->fetch_assoc()['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - CivicLedger Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { civic: { 600: '#059669' } } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-900">

    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-white">CivicLedger <span class="text-blue-400">Nepal</span></span>
                        <span class="ml-2 px-2 py-0.5 bg-red-600 text-white text-xs rounded-full font-bold">Admin</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    <a href="admin_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Dashboard</a>
                    <a href="admin_chat_moderation.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Chat Moderation</a>
                    <a href="admin_teams.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Teams</a>
                    <a href="admin_success_stories.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Stories</a>
                    <a href="admin_commission.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Settings</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Logout</a>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-300 text-2xl"><i class="fas fa-bars"></i></button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="admin_dashboard.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Dashboard</a>
                <a href="admin_chat_moderation.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Chat Moderation</a>
                <a href="admin_teams.php" class="block py-2 px-4 bg-blue-600 text-white rounded-lg">Teams</a>
                <a href="admin_success_stories.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Stories</a>
                <a href="admin_commission.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Settings</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-600 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">
                    <i class="fas fa-users-cog text-purple-400 mr-3"></i>Team Management
                </h1>
                <p class="text-gray-400 mt-1">Manage teams, assign mentors and moderators</p>
            </div>
            <a href="admin_dashboard.php" class="mt-4 md:mt-0 text-blue-400 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Total Teams</p>
                <p class="text-2xl font-bold text-white"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Active</p>
                <p class="text-2xl font-bold text-green-400"><?= $stats['active'] ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Needs Mentor</p>
                <p class="text-2xl font-bold text-amber-400"><?= $stats['needs_mentor'] ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Archived</p>
                <p class="text-2xl font-bold text-gray-400"><?= $stats['archived'] ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="?filter=all" class="px-4 py-2 rounded-lg font-medium <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' ?>">
                All (<?= $stats['total'] ?>)
            </a>
            <a href="?filter=active" class="px-4 py-2 rounded-lg font-medium <?= $filter === 'active' ? 'bg-green-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' ?>">
                Active (<?= $stats['active'] ?>)
            </a>
            <a href="?filter=needs_mentor" class="px-4 py-2 rounded-lg font-medium <?= $filter === 'needs_mentor' ? 'bg-amber-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' ?>">
                Needs Mentor (<?= $stats['needs_mentor'] ?>)
            </a>
            <a href="?filter=archived" class="px-4 py-2 rounded-lg font-medium <?= $filter === 'archived' ? 'bg-gray-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' ?>">
                Archived (<?= $stats['archived'] ?>)
            </a>
            <form method="GET" class="ml-auto flex gap-2">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search teams..."
                       class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <!-- Teams List -->
        <?php if (empty($teams)): ?>
        <div class="bg-gray-800 rounded-2xl p-12 text-center">
            <i class="fas fa-users text-5xl text-gray-600 mb-4"></i>
            <p class="text-gray-400 text-lg">No teams found matching your criteria.</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($teams as $team): ?>
            <?php
            // Get team members
            $stmt = $conn->prepare("SELECT tm.*, u.name, u.email FROM team_members tm JOIN users u ON tm.user_id = u.id WHERE tm.team_id = ?");
            $stmt->bind_param("i", $team['id']);
            $stmt->execute();
            $r = $stmt->get_result();
            $members = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

            // Get moderator
            $stmt = $conn->prepare("SELECT u.name FROM moderator_assignments ma JOIN users u ON ma.user_id = u.id WHERE ma.team_id = ?");
            $stmt->bind_param("i", $team['id']);
            $stmt->execute();
            $r = $stmt->get_result();
            $modRow = $r ? $r->fetch_assoc() : null;
            $moderatorName = $modRow['name'] ?? 'None';
            ?>
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700 card-hover transition-all">
                <div class="flex flex-col lg:flex-row justify-between gap-4">
                    <!-- Team Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-xl font-bold text-white"><?= e($team['name']) ?></h3>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $team['status'] === 'active' ? 'bg-green-900 text-green-300' : ($team['status'] === 'archived' ? 'bg-gray-700 text-gray-400' : 'bg-amber-900 text-amber-300') ?>">
                                <?= ucfirst(e($team['status'])) ?>
                            </span>
                            <?php if (!$team['mentor_id'] && $team['status'] === 'active'): ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-900 text-amber-300">
                                <i class="fas fa-exclamation-triangle mr-1"></i>No Mentor
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Leader:</span>
                                <span class="text-white ml-1"><?= e($team['leader_name']) ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400">Members:</span>
                                <span class="text-white ml-1"><?= $team['member_count'] ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400">Mentor:</span>
                                <span class="<?= $team['mentor_name'] ? 'text-green-400' : 'text-amber-400' ?> ml-1">
                                    <?= e($team['mentor_name'] ?? 'Not assigned') ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Milestones:</span>
                                <span class="text-white ml-1"><?= $team['milestone_count'] ?></span>
                            </div>
                        </div>
                        <?php if (!empty($team['description'])): ?>
                        <p class="text-gray-400 text-sm mt-2"><?= e($team['description']) ?></p>
                        <?php endif; ?>
                        <p class="text-gray-500 text-xs mt-2">Created: <?= date('M j, Y', strtotime($team['created_at'])) ?></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 lg:w-80">
                        <!-- Assign Mentor -->
                        <div class="bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-2 font-semibold">Assign Mentor</p>
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                <select name="mentor_id" class="flex-1 px-3 py-2 bg-gray-600 border border-gray-500 rounded-lg text-white text-sm">
                                    <option value="">Select mentor...</option>
                                    <?php foreach ($mentors as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $team['mentor_id'] == $m['id'] ? 'selected' : '' ?>>
                                        <?= e($m['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_mentor" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    <i class="fas fa-user-graduate"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Assign Moderator -->
                        <div class="bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-2 font-semibold">Assign Moderator</p>
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                <select name="moderator_id" class="flex-1 px-3 py-2 bg-gray-600 border border-gray-500 rounded-lg text-white text-sm">
                                    <option value="">Select moderator...</option>
                                    <?php foreach ($moderators as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_moderator" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                                    <i class="fas fa-shield-alt"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Archive / Restore -->
                        <?php if ($team['status'] === 'archived'): ?>
                        <form method="POST">
                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                            <button type="submit" name="unarchive_team" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                <i class="fas fa-undo mr-2"></i>Restore Team
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" onsubmit="return confirm('Archive this team?');">
                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                            <button type="submit" name="archive_team" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500 text-sm">
                                <i class="fas fa-archive mr-2"></i>Archive
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Members Preview -->
                <?php if (!empty($members)): ?>
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <p class="text-xs text-gray-400 font-semibold mb-2">Team Members:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($members as $mem): ?>
                        <span class="px-3 py-1 bg-gray-700 text-gray-300 rounded-full text-xs">
                            <i class="fas fa-user mr-1"></i><?= e($mem['name']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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