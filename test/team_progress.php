<?php
/**
 * CivicLedger - Team Progress
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$teamId = (int)($_GET['team_id'] ?? 0);

if (!$teamId) {
    // Get user's team
    $stmt = $conn->prepare("SELECT id FROM teams WHERE leader_id = ? OR JSON_CONTAINS(members, JSON_ARRAY(?))");
    $userIdJson = json_encode($user['id']);
    $stmt->bind_param("is", $user['id'], $userIdJson);
    $stmt->execute();
    $r = $stmt->get_result();
    $team = $r ? $r->fetch_assoc() : null;
    $teamId = $team['id'] ?? 0;
}

if (!$teamId) {
    setFlash('error', 'You are not in a team.');
    header("Location: team_formation.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$r = $stmt->get_result();
$team = $r ? $r->fetch_assoc() : null;

if (!$team) {
    header("Location: team_formation.php");
    exit;
}

// Check if user is part of this team
$members = json_decode($team['members'], true) ?: [];
if ($team['leader_id'] !== $user['id'] && !in_array($user['id'], $members)) {
    setFlash('error', 'You are not a member of this team.');
    header("Location: team_formation.php");
    exit;
}

// Get milestones
$stmt = $conn->prepare("SELECT * FROM team_milestones WHERE team_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$r = $stmt->get_result();
$milestones = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Calculate progress
$completed = count(array_filter($milestones, fn($m) => $m['status'] === 'completed'));
$inProgress = count(array_filter($milestones, fn($m) => $m['status'] === 'in_progress'));
$total = count($milestones);
$progressPercent = $total > 0 ? round(($completed / $total) * 100) : 0;

// Add milestone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_milestone') {
    if ($team['leader_id'] !== $user['id']) {
        setFlash('error', 'Only team leader can add milestones.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueDate = $_POST['due_date'] ?? null;
        
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO team_milestones (team_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $teamId, $title, $description, $dueDate);
            $stmt->execute();
            setFlash('success', 'Milestone added!');
            header("Location: team_progress.php?team_id=" . $teamId);
            exit;
        }
    }
}

// Update milestone status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_milestone') {
    $milestoneId = (int)($_POST['milestone_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    
    $stmt = $conn->prepare("UPDATE team_milestones SET status = ?, completed_at = ? WHERE id = ?");
    $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    $stmt->bind_param("ssi", $status, $completedAt, $milestoneId);
    $stmt->execute();
    
    // Update team rank points if completed
    if ($status === 'completed') {
        $conn->query("UPDATE teams SET rank_points = rank_points + 10 WHERE id = $teamId");
    }
    
    setFlash('success', 'Milestone updated!');
    header("Location: team_progress.php?team_id=" . $teamId);
    exit;
}

getHeader('Team Progress');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Back Button -->
    <a href="team_formation.php" class="inline-flex items-center text-gray-600 hover:text-primary mb-6">
        <i class="fas fa-arrow-left mr-2"></i>Back to Teams
    </a>
    
    <!-- Team Header -->
    <div class="gradient-bg rounded-2xl p-8 text-white mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-users mr-2"></i><?= e($team['name']) ?>
                </h1>
                <p class="text-purple-100">
                    Rank Points: <span class="font-bold text-amber-400"><?= $team['rank_points'] ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <a href="chat.php?team_id=<?= $teamId ?>" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-comments mr-2"></i>Team Chat
                </a>
            </div>
        </div>
    </div>
    
    <!-- Progress Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-4xl font-bold text-primary"><?= $total ?></p>
            <p class="text-gray-500">Total Milestones</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-4xl font-bold text-green-600"><?= $completed ?></p>
            <p class="text-gray-500">Completed</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-4xl font-bold text-amber-600"><?= $inProgress ?></p>
            <p class="text-gray-500">In Progress</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-4xl font-bold text-purple-600"><?= $progressPercent ?>%</p>
            <p class="text-gray-500">Progress</p>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
        <h3 class="font-bold text-lg mb-4">Team Progress</h3>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="bg-gradient-to-r from-primary to-secondary h-4 rounded-full transition-all duration-500" style="width: <?= $progressPercent ?>%"></div>
        </div>
        <p class="text-sm text-gray-500 mt-2 text-center"><?= $completed ?> of <?= $total ?> milestones completed</p>
    </div>
    
    <!-- Milestones Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold"><i class="fas fa-flag-checkered mr-2 text-amber-500"></i>Milestones</h2>
            <?php if ($team['leader_id'] === $user['id']): ?>
                <button onclick="document.getElementById('addMilestoneForm').classList.toggle('hidden')" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-plus mr-2"></i>Add Milestone
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Add Milestone Form -->
        <?php if ($team['leader_id'] === $user['id']): ?>
            <div id="addMilestoneForm" class="hidden bg-gray-50 rounded-lg p-6 mb-6">
                <h4 class="font-bold mb-4">Add New Milestone</h4>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_milestone">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                        <input type="text" name="title" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                        <input type="date" name="due_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" class="bg-primary hover:bg-indigo-700 text-white px-6 py-2 rounded-lg">Add Milestone</button>
                        <button type="button" onclick="document.getElementById('addMilestoneForm').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded-lg">Cancel</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (empty($milestones)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-flag text-4xl mb-4"></i>
                <p>No milestones yet. Add one to track your progress!</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($milestones as $ms): ?>
                    <div class="border border-gray-200 rounded-lg p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $ms['status'] === 'completed' ? 'bg-green-100' : ($ms['status'] === 'in_progress' ? 'bg-amber-100' : 'bg-gray-100') ?>">
                            <?php if ($ms['status'] === 'completed'): ?>
                                <i class="fas fa-check text-green-600 text-xl"></i>
                            <?php elseif ($ms['status'] === 'in_progress'): ?>
                                <i class="fas fa-spinner fa-spin text-amber-600 text-xl"></i>
                            <?php else: ?>
                                <i class="fas fa-clock text-gray-400 text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-lg <?= $ms['status'] === 'completed' ? 'text-green-600 line-through' : '' ?>"><?= e($ms['title']) ?></p>
                            <?php if ($ms['description']): ?>
                                <p class="text-sm text-gray-500"><?= e($ms['description']) ?></p>
                            <?php endif; ?>
                            <?php if ($ms['due_date']): ?>
                                <p class="text-xs text-gray-400 mt-1">Due: <?= date('M j, Y', strtotime($ms['due_date'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="update_milestone">
                                <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="px-3 py-1 border rounded-lg text-sm">
                                    <option value="pending" <?= $ms['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="in_progress" <?= $ms['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $ms['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php getFooter(); ?>