<?php
/**
 * CivicLedger - Submit Solution
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$problemId = (int)($_GET['problem_id'] ?? 0);
$challengeId = (int)($_GET['challenge_id'] ?? 0);

$problem = null;
$challenge = null;

if ($problemId) {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->bind_param("i", $problemId);
    $stmt->execute();
    $r = $stmt->get_result();
    $problem = $r ? $r->fetch_assoc() : null;
}

if ($challengeId) {
    $stmt = $conn->prepare("SELECT c.*, u.name as sponsor_name FROM challenges c JOIN users u ON c.sponsor_id = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $challengeId);
    $stmt->execute();
    $r = $stmt->get_result();
    $challenge = $r ? $r->fetch_assoc() : null;
}

if (!$problem && !$challenge) {
    setFlash('error', 'Problem or challenge not found.');
    header("Location: student_dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = (float)($_POST['budget_estimate'] ?? 0);
    $plan = trim($_POST['implementation_plan'] ?? '');
    $teamId = (int)($_POST['team_id'] ?? 0);
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    
    if (empty($errors)) {
        // Check if already submitted
        $checkStmt = $conn->prepare("SELECT id FROM solutions WHERE (problem_id = ? OR challenge_id = ?) AND user_id = ?");
        $checkStmt->bind_param("iii", $problemId, $challengeId, $user['id']);
        $checkStmt->execute();
        $r = $checkStmt->get_result();
        if ($r && $r->num_rows > 0) {
            $errors[] = "You have already submitted a solution to this " . ($problemId ? "problem" : "challenge");
        }
        
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO solutions (problem_id, challenge_id, team_id, user_id, title, description, budget_estimate, implementation_plan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $pId = $problemId ?: null;
            $cId = $challengeId ?: null;
            $stmt->bind_param("iiiissss", $pId, $cId, $teamId, $user['id'], $title, $description, $budget, $plan);
            
            if ($stmt->execute()) {
                setFlash('success', 'Solution submitted successfully! Good luck!');
                header("Location: student_dashboard.php");
                exit;
            } else {
                $errors[] = "Failed to submit solution. Please try again.";
            }
        }
    }
}

getHeader('Submit Solution');
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lightbulb text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Submit Your Solution</h1>
            <p class="text-gray-500 mt-2">
                <?php if ($problem): ?>
                    For: <?= e($problem['title']) ?>
                <?php else: ?>
                    Challenge: <?= e($challenge['title']) ?>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($challenge): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <p class="font-semibold text-amber-800">
                    <i class="fas fa-trophy mr-2"></i>Reward: <?= formatCurrency($challenge['reward_amount']) ?>
                </p>
                <p class="text-sm text-amber-700 mt-1">Deadline: <?= date('M j, Y', strtotime($challenge['deadline'])) ?></p>
                <p class="text-sm text-amber-700">By: <?= e($challenge['sponsor_name']) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-heading mr-2 text-primary"></i>Solution Title *
                </label>
                <input type="text" name="title" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Brief title for your solution">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-align-left mr-2 text-primary"></i>Description *
                </label>
                <textarea name="description" required rows="6"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Detailed explanation of your solution..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-coins mr-2 text-primary"></i>Budget Estimate (Rs)
                </label>
                <input type="number" name="budget_estimate" step="0.01" min="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Estimated cost">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-tasks mr-2 text-primary"></i>Implementation Plan
                </label>
                <textarea name="implementation_plan" rows="4"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="How will you implement this solution? Step by step..."></textarea>
            </div>
            
            <?php
            // Get user's team safely
            $stmt = $conn->prepare("SELECT * FROM teams WHERE leader_id = ? OR JSON_CONTAINS(members, JSON_ARRAY(?))");
            if ($stmt !== false) {
                $userIdJson = json_encode($user['id']);
                $stmt->bind_param("is", $user['id'], $userIdJson);
                $stmt->execute();
                $r = $stmt->get_result();
                $myTeam = $r ? $r->fetch_assoc() : null;
            } else {
                $myTeam = null;
            }
            ?>
            
            <?php if ($myTeam): ?>
                <input type="hidden" name="team_id" value="<?= $myTeam['id'] ?>">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <p class="font-semibold text-purple-800">
                        <i class="fas fa-users mr-2"></i>Submitting as part of: <?= e($myTeam['name']) ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="flex gap-4">
                <a href="javascript:history.back()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Solution
                </button>
            </div>
        </form>
    </div>
</div>

<?php getFooter(); ?>