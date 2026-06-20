<?php
/**
 * CivicLedger - Solution Approval Dashboard
 * Manage and approve submitted solutions
 */
require_once 'config.php';
requireRole(['admin', 'moderator']);

$user = getCurrentUser();
global $conn;

$isAdmin = $user['role'] === 'admin';

// Get pending solutions for approval
$stmt = $conn->prepare("
    SELECT s.*, u.name as author_name, u.id as author_id, c.title as challenge_title, p.title as problem_title
    FROM solutions s
    JOIN users u ON s.user_id = u.id
    JOIN challenges c ON s.challenge_id = c.id
    LEFT JOIN problems p ON s.problem_id = p.id
    WHERE s.status = 'pending'
    ORDER BY s.created_at DESC
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$r = $stmt->get_result();
$pendingSolutions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action']; // 'approve' or 'reject'
    $solutionId = (int)($_POST['solution_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($solutionId && in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE solutions SET status = ? WHERE id = ?");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("si", $status, $solutionId);
        if ($stmt->execute()) {
            setFlash('success', 'Solution ' . $status . ' successfully!');
            header("Location: admin_solution_approval.php");
            exit;
        }
    }
}

$approved = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM solutions WHERE status = 'approved'");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $approved = $r->fetch_assoc()['cnt'];

$rejected = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM solutions WHERE status = 'rejected'");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $rejected = $r->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solution Approval - CivicLedger Admin</title>
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
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-gray-900 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-white">Solution Approval</span>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="mentor_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-home mr-1"></i>Dashboard</a>
                    <a href="admin_content_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-file-alt mr-1"></i>Content</a>
                    <a href="admin_solution_approval.php" class="px-4 py-2 bg-green-600 text-white rounded-lg"><i class="fas fa-check mr-1"></i>Solutions</a>
                    <a href="team_chat.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-comments mr-1"></i>Team Chat</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Pending Review</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($pendingSolutions); ?></p>
                    </div>
                    <i class="fas fa-hourglass-half text-yellow-500 text-3xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Approved</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $approved; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Rejected</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $rejected; ?></p>
                    </div>
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Solutions</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo count($pendingSolutions) + $approved + $rejected; ?></p>
                    </div>
                    <i class="fas fa-lightbulb text-blue-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Solutions -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Pending Solutions Review</h2>
                <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">
                    <?php echo count($pendingSolutions); ?> Items
                </span>
            </div>

            <?php if (count($pendingSolutions) > 0): ?>
            <div class="divide-y">
                <?php foreach ($pendingSolutions as $solution): ?>
                <div class="p-6 hover:bg-gray-50 card-hover transition-all">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Author</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($solution['author_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Challenge</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($solution['challenge_title']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Submitted</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo date('M d, Y H:i', strtotime($solution['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                        </div>
                    </div>

                    <div class="mb-4 p-4 bg-gray-50 rounded">
                        <p class="text-xs font-semibold text-gray-600 mb-2">Solution Preview:</p>
                        <p class="text-sm text-gray-700"><?php echo substr(htmlspecialchars($solution['description']), 0, 200); ?>...</p>
                    </div>

                    <div class="flex space-x-3">
                        <button onclick="showApprovalModal(<?php echo $solution['id']; ?>, 'approve')" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                        <button onclick="showApprovalModal(<?php echo $solution['id']; ?>, 'reject')" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
                            <i class="fas fa-times mr-2"></i>Reject
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="px-6 py-12 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                <p class="text-gray-600">All solutions have been reviewed! ✓</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Approve Solution?</h3>
            </div>
            <form method="POST" class="px-6 py-4 space-y-4">
                <input type="hidden" name="solution_id" id="solutionId">
                <input type="hidden" name="action" id="action">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Add a reason..."></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeApprovalModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showApprovalModal(id, action) {
            document.getElementById('solutionId').value = id;
            document.getElementById('action').value = action;
            document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Solution?' : 'Reject Solution?';
            document.getElementById('approvalModal').classList.remove('hidden');
        }
        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }
    </script>
</body>
</html>
