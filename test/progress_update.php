<?php
/**
 * CivicLedger - Submit Proof of Progress
 * Rule #2: 14-Day Progress Update System
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$team = getUserTeam($user['id']);
if (!$team) {
    setFlash('error', 'You must be in a team to submit progress updates.');
    header("Location: team_formation.php");
    exit;
}

// Get team milestones
$stmt = $conn->prepare("SELECT * FROM team_milestones WHERE team_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $team['id']);
$stmt->execute();
$r = $stmt->get_result();
$milestones = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get recent updates
$recentUpdates = getTeamProgressUpdates($team['id'], 5);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateType = $_POST['update_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $milestoneId = (int)($_POST['milestone_id'] ?? 0);
    
    if (empty($updateType) || empty($title)) {
        $error = 'Please fill in all required fields.';
    } else {
        if (addProgressUpdate($team['id'], $updateType, $title, $description, $linkUrl, null, $user['id'])) {
            $success = 'Progress update submitted successfully! +2 trust points earned.';
            header("Location: progress_update.php");
            exit;
        } else {
            $error = 'Failed to submit progress update. Please try again.';
        }
    }
}

// Check if team is at risk of being marked inactive
$setting = getSetting('pop_update_interval_days', 14);
$lastUpdate = $team['last_progress_update'];
$daysSinceUpdate = $lastUpdate ? floor((time() - strtotime($lastUpdate)) / 86400) : floor((time() - strtotime($team['created_at'])) / 86400);
$daysRemaining = max(0, $setting - $daysSinceUpdate);

getHeader('Submit Progress Update');
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-chart-line text-blue-600 mr-3"></i>Submit Progress Update
            </h1>
            <p class="text-gray-500 mt-2">Proof of Progress (PoP) - Keep your team active!</p>
        </div>
        <a href="team_formation.php" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-2"></i>Back to Team
        </a>
    </div>

    <!-- Team Status Banner -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 <?php echo ($daysRemaining <= 3) ? 'border-2 border-amber-400' : ''; ?>">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-xl text-blue-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800"><?php echo e($team['name']); ?></h3>
                    <p class="text-sm text-gray-500">Rank Points: <span class="text-amber-600 font-bold"><?php echo $team['rank_points']; ?></span></p>
                </div>
            </div>
            <?php if ($team['gold_badge']): ?>
            <span class="px-4 py-2 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded-full font-bold">
                <i class="fas fa-award mr-2"></i>Gold Badge
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Progress Days Warning -->
        <div class="mt-4 p-4 rounded-xl <?php echo ($daysRemaining <= 3) ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold <?php echo ($daysRemaining <= 3) ? 'text-amber-800' : 'text-green-800'; ?>">
                        <?php if ($daysRemaining <= 3): ?>
                        <i class="fas fa-exclamation-triangle mr-2"></i>Warning: Only <?php echo $daysRemaining; ?> days left!
                        <?php else: ?>
                        <i class="fas fa-check-circle mr-2"></i>You're safe for <?php echo $daysRemaining; ?> more days
                        <?php endif; ?>
                    </p>
                    <p class="text-sm <?php echo ($daysRemaining <= 3) ? 'text-amber-700' : 'text-green-700'; ?> mt-1">
                        Last update: <?php echo $lastUpdate ? timeAgo($lastUpdate) : 'Never (team created ' . timeAgo($team['created_at']) . ')'; ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Required: Every <?php echo $setting; ?> days</p>
                    <p class="text-2xl font-bold <?php echo ($daysRemaining <= 3) ? 'text-amber-600' : 'text-green-600'; ?>">
                        <?php echo $daysRemaining; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo e($error); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
        <i class="fas fa-check-circle mr-2"></i><?php echo e($_GET['success']); ?>
    </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-8">
        <!-- Submit Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-plus text-blue-600"></i>
                </div>
                New Progress Update
            </h2>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Update Type *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="update_type" value="commit_link" class="text-blue-600" required>
                            <i class="fas fa-code text-gray-600"></i>
                            <span class="text-sm">Code Commit</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="update_type" value="photo" class="text-blue-600">
                            <i class="fas fa-camera text-gray-600"></i>
                            <span class="text-sm">Photo</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="update_type" value="mentor_signoff" class="text-blue-600">
                            <i class="fas fa-signature text-gray-600"></i>
                            <span class="text-sm">Mentor Sign-off</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="update_type" value="document" class="text-blue-600">
                            <i class="fas fa-file-alt text-gray-600"></i>
                            <span class="text-sm">Document</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" required
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., GitHub Repository Created, Prototype v1 Ready">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Link URL (optional)</label>
                    <input type="url" name="link_url"
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="https://github.com/your-project">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="4"
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                        placeholder="Describe what was accomplished in this update..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Related Milestone (optional)</label>
                    <select name="milestone_id" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- No specific milestone --</option>
                        <?php foreach ($milestones as $ms): ?>
                        <option value="<?php echo $ms['id']; ?>"><?php echo e($ms['title']); ?> (<?php echo ucfirst($ms['status']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-xl transition-all shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Progress Update (+2 Trust Points)
                </button>
            </form>
        </div>

        <!-- Recent Updates & Rules -->
        <div class="space-y-6">
            <!-- Recent Updates -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                        <i class="fas fa-history text-green-600 text-sm"></i>
                    </div>
                    Recent Updates
                </h3>

                <?php if (empty($recentUpdates)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>No progress updates yet.</p>
                    <p class="text-sm">Submit your first update above!</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recentUpdates as $update): ?>
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <?php
                                $icon = match($update['update_type']) {
                                    'commit_link' => 'fa-code',
                                    'photo' => 'fa-camera',
                                    'mentor_signoff' => 'fa-signature',
                                    'document' => 'fa-file-alt',
                                    default => 'fa-circle'
                                };
                                ?>
                                <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas <?php echo $icon; ?> text-blue-600 text-sm"></i>
                                </span>
                                <span class="font-semibold text-sm"><?php echo e($update['title']); ?></span>
                            </div>
                            <?php if ($update['mentor_approved']): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                <i class="fas fa-check mr-1"></i>Approved
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-500">
                            By <?php echo e($update['created_by_name']); ?> • <?php echo timeAgo($update['created_at']); ?>
                        </p>
                        <?php if ($update['link_url']): ?>
                        <a href="<?php echo e($update['link_url']); ?>" target="_blank" class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                            <i class="fas fa-external-link-alt mr-1"></i><?php echo e(substr($update['link_url'], 0, 50)); ?>...
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Rules Reminder -->
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl shadow-lg p-6 border border-purple-200">
                <h3 class="text-lg font-bold mb-4 text-purple-800">
                    <i class="fas fa-info-circle mr-2"></i>PoP Rules Reminder
                </h3>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-2">
                        <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">1</span>
                        <span class="text-gray-700">Submit at least one update every <strong>14 days</strong></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">2</span>
                        <span class="text-gray-700">Accepted: Code links, photos, mentor sign-offs</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">3</span>
                        <span class="text-gray-700">Earn <strong>+2 trust points</strong> per update</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0">!</span>
                        <span class="text-gray-700">Inactive teams (14+ days no update) get <strong>-10 points</strong></span>
                    </li>
                </ul>
                <a href="governance.php#rule2" class="mt-4 block text-center text-purple-700 hover:underline text-sm">
                    <i class="fas fa-external-link-alt mr-1"></i>Read full governance rules
                </a>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>