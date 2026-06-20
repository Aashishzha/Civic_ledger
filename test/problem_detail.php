<?php
/**
 * CivicLedger - Problem Detail Page
 */
require_once 'config.php';
requireLogin();

$problemId = (int)($_GET['id'] ?? 0);
if (!$problemId) {
    header("Location: index.php");
    exit;
}

global $conn;
$stmt = $conn->prepare("SELECT p.*, u.name as user_name, u.profile_photo FROM problems p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $problemId);
$stmt->execute();
$r = $stmt->get_result();
$problem = $r ? $r->fetch_assoc() : null;

if (!$problem) {
    setFlash('error', 'Problem not found.');
    header("Location: index.php");
    exit;
}

// Get solutions for this problem
$stmt = $conn->prepare("SELECT s.*, u.name as solver_name FROM solutions s JOIN users u ON s.user_id = u.id WHERE s.problem_id = ? ORDER BY s.created_at DESC");
$stmt->bind_param("i", $problemId);
$stmt->execute();
$r = $stmt->get_result();
$solutions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

$user = getCurrentUser();
getHeader('Problem Details');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Back Button -->
    <a href="javascript:history.back()" class="inline-flex items-center text-gray-600 hover:text-primary mb-6">
        <i class="fas fa-arrow-left mr-2"></i>Back
    </a>
    
    <!-- Problem Card -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
        <?php if ($problem['photo'] && file_exists($problem['photo'])): ?>
            <img src="<?= e($problem['photo']) ?>" alt="Problem photo" class="w-full h-64 object-cover">
        <?php else: ?>
            <div class="w-full h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                <i class="fas fa-image text-6xl text-gray-400"></i>
            </div>
        <?php endif; ?>
        
        <div class="p-8">
            <!-- Status Badges -->
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getCategoryClass($problem['category']) ?>">
                    <i class="fas fa-tag mr-1"></i><?= e($problem['category']) ?>
                </span>
                <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getUrgencyClass($problem['urgency']) ?>">
                    <i class="fas fa-exclamation-circle mr-1"></i><?= e($problem['urgency']) ?> Urgency
                </span>
                <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getStatusClass($problem['status']) ?>">
                    <i class="fas fa-circle mr-1"></i><?= ucfirst(e($problem['status'])) ?>
                </span>
            </div>
            
            <!-- Title & Description -->
            <h1 class="text-3xl font-bold text-gray-800 mb-4"><?= e($problem['title']) ?></h1>
            <p class="text-gray-600 text-lg mb-6"><?= nl2br(e($problem['description'])) ?></p>
            
            <!-- Meta Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                        <?php if ($problem['profile_photo']): ?>
                            <img src="<?= e($problem['profile_photo']) ?>" class="w-10 h-10 rounded-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Posted by</p>
                        <p class="font-semibold"><?= e($problem['user_name']) ?></p>
                    </div>
                </div>
                
                <?php if ($problem['location']): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-map-marker-alt text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Location</p>
                            <p class="font-semibold"><?= e($problem['location']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Posted</p>
                        <p class="font-semibold"><?= timeAgo($problem['created_at']) ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($problem['voice_note']): ?>
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="font-semibold text-gray-800 mb-2"><i class="fas fa-microphone mr-2 text-blue-600"></i>Voice Note</p>
                    <p class="text-gray-600"><?= e($problem['voice_note']) ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <?php if ($user['role'] === 'student'): ?>
                <div class="mt-8 flex gap-4">
                    <a href="submit_solution.php?problem_id=<?= $problemId ?>" class="flex-1 bg-gradient-to-r from-primary to-secondary hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 rounded-lg text-center transition-all transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-lightbulb mr-2"></i>Submit Solution
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Solutions Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">
            <i class="fas fa-check-circle mr-2 text-green-500"></i>Solutions (<?= count($solutions) ?>)
        </h2>
        
        <?php if (empty($solutions)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-inbox text-5xl mb-4"></i>
                <p class="text-lg">No solutions submitted yet.</p>
                <?php if ($user['role'] === 'student'): ?>
                    <p class="mt-2">Be the first to propose a solution!</p>
                    <a href="submit_solution.php?problem_id=<?= $problemId ?>" class="inline-block mt-4 bg-primary text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
                        Submit Solution
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($solutions as $solution): ?>
                    <div class="border border-gray-200 rounded-xl p-6 hover:border-primary transition-all">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800"><?= e($solution['title']) ?></h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-user mr-1"></i><?= e($solution['solver_name']) ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock mr-1"></i><?= timeAgo($solution['created_at']) ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?= getSolutionStatusClass($solution['status']) ?>">
                                <?= ucfirst(e($solution['status'])) ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-600 mb-4"><?= nl2br(e($solution['description'])) ?></p>
                        
                        <div class="flex items-center justify-between pt-4 border-t">
                            <span class="text-lg font-bold text-green-600">
                                <i class="fas fa-coins mr-2"></i>Budget: <?= formatCurrency($solution['budget_estimate']) ?>
                            </span>
                            
                            <?php if ($solution['status'] === 'rewarded'): ?>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Gross: <?= formatCurrency($solution['reward_gross']) ?></p>
                                    <p class="text-sm text-gray-500">Commission: <?= formatCurrency($solution['reward_commission']) ?></p>
                                    <p class="text-lg font-bold text-green-600">Net: <?= formatCurrency($solution['reward_net']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($solution['implementation_plan']): ?>
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <p class="font-semibold text-gray-700 mb-2"><i class="fas fa-tasks mr-2"></i>Implementation Plan</p>
                                <p class="text-gray-600"><?= nl2br(e($solution['implementation_plan'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
?>

<?php getFooter(); ?>