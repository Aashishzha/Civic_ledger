<?php
/**
 * CivicLedger - Team Formation
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$myTeam = getUserTeam($user['id']);

// Create team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $teamName = trim($_POST['team_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $skills = trim($_POST['required_skills'] ?? '');
    
    if (empty($teamName)) {
        setFlash('error', 'Team name is required');
    } elseif ($myTeam) {
        setFlash('error', 'You are already in a team');
    } else {
        $stmt = $conn->prepare("INSERT INTO teams (name, description, leader_id, required_skills, members, rank_points, status) VALUES (?, ?, ?, ?, ?, 0, 'active')");
        if ($stmt === false) {
            setFlash('error', 'Database error. Please try again.');
        } else {
            $membersJson = json_encode([$user['id']]);
            $stmt->bind_param("ssiss", $teamName, $description, $user['id'], $skills, $membersJson);
            
            if ($stmt->execute()) {
                $teamId = $conn->insert_id;
                
                // Create chat group for team
                $stmtGroup = $conn->prepare("INSERT INTO chat_groups (name, type, team_id) VALUES (?, 'team', ?)");
                if ($stmtGroup !== false) {
                    $chatName = $teamName . " Chat";
                    $stmtGroup->bind_param("si", $chatName, $teamId);
                    $stmtGroup->execute();
                }
                
                // Add self to team_members table
                $stmtMember = $conn->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'member')");
                if ($stmtMember !== false) {
                    $stmtMember->bind_param("ii", $teamId, $user['id']);
                    $stmtMember->execute();
                }
                
                setFlash('success', 'Team "' . e($teamName) . '" created successfully!');
                header("Location: team_formation.php");
                exit;
            } else {
                setFlash('error', 'Failed to create team');
            }
        }
    }
}

$myTeam = getUserTeam($user['id']);
$teamMembers = $myTeam ? getTeamMembers($myTeam) : [];
$milestones = [];
if ($myTeam) {
    $stmt = $conn->prepare("SELECT * FROM team_milestones WHERE team_id = ? ORDER BY created_at ASC");
    if ($stmt !== false) {
        $stmt->bind_param("i", $myTeam['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $milestones = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Formation - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { civic: { 600: '#059669' } } } } }</script>
    <style>body { font-family: 'Inter', sans-serif; } .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }</style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-lightbulb text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">CivicLedger <span class="text-blue-600">Nepal</span></span>
                </a>
                <a href="student_dashboard.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8"><i class="fas fa-users mr-3 text-blue-600"></i>Team Management</h1>

        <?php if ($myTeam): ?>
        <!-- Existing Team -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?= e($myTeam['name']) ?></h2>
                    <p class="text-gray-500">Rank Points: <span class="text-blue-600 font-bold"><?= $myTeam['rank_points'] ?? 0 ?></span></p>
                </div>
                <span class="px-4 py-2 rounded-full bg-green-100 text-green-800 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i>Active
                </span>
            </div>

            <h3 class="font-bold text-lg mb-4">Team Members (<?= count($teamMembers) ?>)</h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <?php foreach ($teamMembers as $member): ?>
                <div class="border border-gray-200 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="font-bold text-blue-600"><?= getInitials($member['name']) ?></span>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold"><?= e($member['name']) ?></p>
                        <p class="text-sm text-gray-500"><?= ucfirst(e($member['role'])) ?></p>
                    </div>
                    <?php if ($member['id'] === $myTeam['leader_id']): ?>
                    <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">
                        <i class="fas fa-crown mr-1"></i>Leader
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-4">
                <a href="team_progress.php?team_id=<?= $myTeam['id'] ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl text-center transition-all">
                    <i class="fas fa-chart-line mr-2"></i>View Progress
                </a>
                <a href="chat.php?team_id=<?= $myTeam['id'] ?>" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl text-center transition-all">
                    <i class="fas fa-comments mr-2"></i>Team Chat
                </a>
            </div>
        </div>

        <!-- Milestones -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold"><i class="fas fa-flag-checkered mr-2 text-amber-500"></i>Milestones</h3>
                <a href="team_progress.php?team_id=<?= $myTeam['id'] ?>" class="text-blue-600 hover:underline">+ Add Milestone</a>
            </div>

            <?php if (empty($milestones)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-flag text-4xl mb-4 text-gray-300"></i>
                <p>No milestones yet.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($milestones as $ms): ?>
                <div class="flex items-center gap-4 p-4 border rounded-xl">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $ms['status'] === 'completed' ? 'bg-green-100' : ($ms['status'] === 'in_progress' ? 'bg-amber-100' : 'bg-gray-100') ?>">
                        <?php if ($ms['status'] === 'completed'): ?><i class="fas fa-check text-green-600"></i>
                        <?php elseif ($ms['status'] === 'in_progress'): ?><i class="fas fa-spinner text-amber-600"></i>
                        <?php else: ?><i class="fas fa-clock text-gray-400"></i><?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold <?= $ms['status'] === 'completed' ? 'text-green-600 line-through' : '' ?>"><?= e($ms['title']) ?></p>
                        <?php if ($ms['description']): ?><p class="text-sm text-gray-500"><?= e($ms['description']) ?></p><?php endif; ?>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $ms['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($ms['status'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800') ?>">
                        <?= ucfirst(str_replace('_', ' ', e($ms['status']))) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- Create Team Form -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-3xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Create Your Team</h2>
                <p class="text-gray-500 mt-2">Form a team to tackle challenges together</p>
            </div>

            <form method="POST" class="max-w-md mx-auto space-y-6">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Team Name *</label>
                    <input type="text" name="team_name" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-600" placeholder="e.g., Innovation Squad">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-600" placeholder="What does your team focus on?"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Required Skills</label>
                    <input type="text" name="required_skills" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-600" placeholder="e.g., Programming, Design, Research">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Create Team
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>