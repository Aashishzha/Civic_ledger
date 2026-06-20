<?php
/**
 * CivicLedger - Mentor Problem Approval
 * Mentors approve problems posted by students in their teams
 */
require_once 'config.php';
requireRole('mentor');

$user = getCurrentUser();
global $conn;

// Get pending problems from students in mentor's assigned teams
$stmt = $conn->prepare("
    SELECT p.*, u.name as author_name, u.id as author_id,
    (SELECT COUNT(*) FROM solutions WHERE problem_id = p.id) as solution_count
    FROM problems p
    JOIN users u ON p.user_id = u.id
    WHERE p.is_approved = 0
    LIMIT 100
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$r = $stmt->get_result();
$pendingProblems = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $problemId = (int)($_POST['problem_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($problemId && in_array($action, ['approve', 'reject'])) {
        $approvalStatus = $action === 'approve' ? 1 : -1;
        $stmt = $conn->prepare("UPDATE problems SET is_approved = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("iisi", $approvalStatus, $user['id'], $reason, $problemId);
        if ($stmt->execute()) {
            setFlash('success', 'Problem ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully!');
            header("Location: mentor_problem_approval.php");
            exit;
        }
    }
}

$approved = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM problems WHERE is_approved = 1");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $approved = $r->fetch_assoc()['cnt'];

$rejected = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM problems WHERE is_approved = -1");
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
    <title>Problem Approval - CivicLedger Mentor</title>
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
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-white">Problem Approval</span>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="mentor_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-home mr-1"></i>Dashboard</a>
                    <a href="mentor_problem_approval.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg"><i class="fas fa-file-alt mr-1"></i>Problems</a>
                    <a href="mentor_solution_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-check mr-1"></i>Solutions</a>
                    <a href="mentor_messages.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-inbox mr-1"></i>Messages</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
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
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($pendingProblems); ?></p>
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
                        <p class="text-gray-600 text-sm">Total Problems</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo count($pendingProblems) + $approved + $rejected; ?></p>
                    </div>
                    <i class="fas fa-exclamation-circle text-blue-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Problems -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Pending Problems from Your Team Members</h2>
                <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">
                    <?php echo count($pendingProblems); ?> Items
                </span>
            </div>

            <?php if (count($pendingProblems) > 0): ?>
            <div class="divide-y">
                <?php foreach ($pendingProblems as $problem): ?>
                <div class="p-6 hover:bg-gray-50 card-hover transition-all">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Reporter</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($problem['author_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Category</p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                <?php echo htmlspecialchars($problem['category']); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Urgency</p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?php echo $problem['urgency'] === 'High' ? 'bg-red-100 text-red-800' : ($problem['urgency'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo $problem['urgency']; ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Location</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($problem['location'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Solutions</p>
                            <p class="text-sm font-bold text-green-600"><?php echo $problem['solution_count']; ?> submitted</p>
                        </div>
                    </div>

                    <div class="mb-4 p-4 bg-gray-50 rounded border-l-4 border-blue-500">
                        <p class="text-xs font-semibold text-gray-600 mb-2"><i class="fas fa-quote-left mr-1"></i>Problem:</p>
                        <h3 class="text-sm font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($problem['title']); ?></h3>
                        <p class="text-sm text-gray-700"><?php echo substr(htmlspecialchars($problem['description']), 0, 300); ?><?php echo strlen($problem['description']) > 300 ? '...' : ''; ?></p>
                    </div>

                    <div class="flex space-x-3 mb-4">
                        <?php if (!empty($problem['photo'])): ?>
                        <span class="inline-block px-3 py-1 rounded text-xs font-semibold bg-purple-100 text-purple-800"><i class="fas fa-image mr-1"></i>Has Photo</span>
                        <?php endif; ?>
                        <?php if (!empty($problem['voice_note'])): ?>
                        <span class="inline-block px-3 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-800"><i class="fas fa-microphone mr-1"></i>Voice Note</span>
                        <?php endif; ?>
                        <span class="inline-block px-3 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                            <i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($problem['created_at'])); ?>
                        </span>
                    </div>

                    <div class="flex space-x-3">
                        <button onclick="showApprovalModal(<?php echo $problem['id']; ?>, 'approve', '<?php echo htmlspecialchars($problem['author_name']); ?>')" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                        <button onclick="showApprovalModal(<?php echo $problem['id']; ?>, 'reject', '<?php echo htmlspecialchars($problem['author_name']); ?>')" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
                            <i class="fas fa-times mr-2"></i>Reject
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="px-6 py-12 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                <p class="text-gray-600">All problems have been reviewed! ✓</p>
                <p class="text-sm text-gray-500 mt-2">New problems from your team members will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Approve Problem?</h3>
                <p class="text-sm text-gray-600 mt-1">From: <span id="studentName" class="font-semibold"></span></p>
            </div>
            <form method="POST" class="px-6 py-4 space-y-4">
                <input type="hidden" name="problem_id" id="problemId">
                <input type="hidden" name="action" id="action">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                    <textarea name="reason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Add a reason for approval/rejection..."></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeApprovalModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showApprovalModal(id, action, name) {
            document.getElementById('problemId').value = id;
            document.getElementById('action').value = action;
            document.getElementById('studentName').textContent = name;
            document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Problem?' : 'Reject Problem?';
            document.getElementById('approvalModal').classList.remove('hidden');
        }
        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }
    </script>
</body>
</html>
