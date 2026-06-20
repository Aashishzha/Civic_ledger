<?php
/**
 * CivicLedger - Admin Approval System
 * Approve or reject problems and solutions to prevent spam/fake content
 */
require_once 'config.php';
requireRole(['admin', 'mentor']);

$user = getCurrentUser();
global $conn;
$isAdmin = $user['role'] === 'admin';

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($action === 'approve_problem') {
        approveProblem($id, $user['id']);
        setFlash('success', 'Problem approved and published');
    } elseif ($action === 'reject_problem') {
        rejectProblem($id, $user['id'], $reason ?: 'Violates platform guidelines');
        setFlash('success', 'Problem rejected as spam');
    } elseif ($action === 'approve_solution') {
        approveSolution($id, $user['id']);
        setFlash('success', 'Solution approved');
    } elseif ($action === 'reject_solution') {
        rejectSolution($id, $user['id'], $reason ?: 'Violates platform guidelines');
        setFlash('success', 'Solution rejected as spam');
    }
    
    header("Location: admin_approval.php");
    exit;
}

// Get pending items
$pendingProblems = getPendingProblems();
$pendingSolutions = getPendingSolutions();

// Also show recently approved/rejected for admin
$recentActivity = [];
if ($isAdmin) {
    $stmt = $conn->prepare("
        (SELECT 'problem' as type, p.id, p.title, p.is_approved, p.approved_at, u.name as actor_name
         FROM problems p LEFT JOIN users u ON p.approved_by = u.id
         WHERE p.is_approved != 0 ORDER BY p.approved_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'solution' as type, s.id, s.title, s.is_approved, s.approved_at, u.name as actor_name
         FROM solutions s LEFT JOIN users u ON s.approved_by = u.id
         WHERE s.is_approved != 0 ORDER BY s.approved_at DESC LIMIT 5)
        ORDER BY approved_at DESC LIMIT 10
    ");
    $stmt->execute();
    $r = $stmt->get_result();
    $recentActivity = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Approval - CivicLedger Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-gray-900 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-white">CivicLedger <span class="text-green-400 text-sm">Admin</span></span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Dashboard</a>
                    <a href="admin_users.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Users</a>
                    <a href="admin_approval.php" class="px-4 py-2 bg-green-600 text-white rounded-lg">Approvals</a>
                    <a href="admin_teams.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-check-double mr-3 text-green-600"></i>Content Approval</h1>
                    <p class="text-gray-500 mt-1">Review and approve pending problems and solutions. Reject spam/fake content.</p>
                </div>
                
                <div class="flex gap-4">
                    <div class="bg-amber-100 text-amber-700 px-4 py-3 rounded-xl text-center">
                        <div class="text-2xl font-bold"><?= count($pendingProblems) ?></div>
                        <div class="text-xs">Problems Pending</div>
                    </div>
                    <div class="bg-blue-100 text-blue-700 px-4 py-3 rounded-xl text-center">
                        <div class="text-2xl font-bold"><?= count($pendingSolutions) ?></div>
                        <div class="text-xs">Solutions Pending</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problems to Approve -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6"><i class="fas fa-exclamation-circle text-red-500 mr-2"></i>Problems Awaiting Approval</h2>
            
            <?php if (empty($pendingProblems)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-check-circle text-5xl mb-4 text-green-300"></i>
                <p class="text-xl">No problems pending approval!</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingProblems as $problem): ?>
                <div class="border border-gray-200 rounded-xl p-5 <?= $problem['is_approved'] < 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50' ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getCategoryClass($problem['category']) ?>"><?= e($problem['category']) ?></span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getUrgencyClass($problem['urgency']) ?>"><?= e($problem['urgency']) ?></span>
                            </div>
                            
                            <h3 class="font-bold text-gray-800 text-lg"><?= e($problem['title']) ?></h3>
                            <p class="text-gray-600 mt-2"><?= e(substr($problem['description'], 0, 300)) ?>...</p>
                            
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i><?= e($problem['reporter_name']) ?></span>
                                <span><i class="fas fa-shield-alt mr-1"></i>Trust: <?= $problem['reporter_trust'] ?? 100 ?></span>
                                <span><i class="fas fa-map-marker-alt mr-1"></i><?= e($problem['location'] ?: 'N/A') ?></span>
                                <span><i class="fas fa-clock mr-1"></i><?= timeAgo($problem['created_at']) ?></span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-2 ml-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="approve_problem">
                                <input type="hidden" name="id" value="<?= $problem['id'] ?>">
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                    <i class="fas fa-check mr-1"></i>Approve
                                </button>
                            </form>
                            
                            <button onclick="showRejectModal('problem', <?= $problem['id'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Solutions to Approve -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6"><i class="fas fa-lightbulb text-blue-500 mr-2"></i>Solutions Awaiting Approval</h2>
            
            <?php if (empty($pendingSolutions)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-check-circle text-5xl mb-4 text-green-300"></i>
                <p class="text-xl">No solutions pending approval!</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingSolutions as $solution): ?>
                <div class="border border-blue-200 rounded-xl p-5 bg-blue-50/50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <?php if ($solution['problem_title']): ?>
                            <div class="mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                    <i class="fas fa-link mr-1"></i><?= e(substr($solution['problem_title'], 0, 50)) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <h3 class="font-bold text-gray-800 text-lg"><?= e($solution['title']) ?></h3>
                            <p class="text-gray-600 mt-2"><?= e(substr($solution['description'], 0, 300)) ?>...</p>
                            
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i><?= e($solution['solver_name']) ?></span>
                                <span><i class="fas fa-shield-alt mr-1"></i>Trust: <?= $solution['solver_trust'] ?? 100 ?></span>
                                <span><i class="fas fa-coins mr-1"></i><?= formatCurrency($solution['budget_estimate']) ?></span>
                                <span><i class="fas fa-clock mr-1"></i><?= timeAgo($solution['created_at']) ?></span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-2 ml-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="approve_solution">
                                <input type="hidden" name="id" value="<?= $solution['id'] ?>">
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                    <i class="fas fa-check mr-1"></i>Approve
                                </button>
                            </form>
                            
                            <button onclick="showRejectModal('solution', <?= $solution['id'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-times-circle text-red-500 mr-2"></i>Reject Content</h3>
            <p class="text-gray-600 mb-4">Please provide a reason for rejection:</p>
            <form method="POST">
                <input type="hidden" name="id" id="rejectId" value="">
                <input type="hidden" name="action" id="rejectAction" value="">
                <textarea name="reason" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 mb-4" rows="3" placeholder="Reason for rejection..."></textarea>
                <div class="flex gap-3">
                    <button type="button" onclick="hideRejectModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 rounded-xl font-medium transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl font-medium transition-all">Reject</button>
                </div>
            </form>
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
    <script>setTimeout(() => document.querySelector('.fixed.bottom-6').remove(), 4000);</script>
    <?php endif; ?>

    <script>
        function showRejectModal(type, id) {
            document.getElementById('rejectId').value = id;
            document.getElementById('rejectAction').value = type === 'problem' ? 'reject_problem' : 'reject_solution';
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) hideRejectModal();
        });
    </script>

<?php
?>

</body>
</html>