<?php
/**
 * CivicLedger - Content Approval Dashboard
 * Manage and approve user-submitted content (problems, challenges, success stories)
 */
require_once 'config.php';
requireRole(['admin', 'moderator']);

$user = getCurrentUser();
global $conn;

$isAdmin = $user['role'] === 'admin';

// Get pending content for approval
$stmt = $conn->prepare("
    SELECT 'problem' as type, p.id, p.title, p.description, p.category, p.status, p.created_at, u.name as author_name, u.id as author_id
    FROM problems p
    JOIN users u ON p.user_id = u.id
    WHERE p.is_approved = 0
    UNION
    SELECT 'challenge' as type, c.id, c.title, c.description, c.category, c.status, c.created_at, u.name as author_name, u.id as author_id
    FROM challenges c
    JOIN users u ON c.sponsor_id = u.id
    WHERE c.status = 'open'
    ORDER BY created_at DESC
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$r = $stmt->get_result();
$pendingContent = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action']; // 'approve' or 'reject'
    $contentId = (int)($_POST['content_id'] ?? 0);
    $contentType = $_POST['content_type'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    if ($contentId && $contentType && in_array($contentType, ['problem', 'challenge'])) {
        if ($contentType === 'problem') {
            $approvalStatus = $action === 'approve' ? 1 : -1;
            $stmt = $conn->prepare("UPDATE problems SET is_approved = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
            if (!$stmt) die("Database error: " . $conn->error);
            $stmt->bind_param("iisi", $approvalStatus, $user['id'], $reason, $contentId);
        } else {
            $status = $action === 'approve' ? 'open' : 'closed';
            $stmt = $conn->prepare("UPDATE challenges SET status = ? WHERE id = ?");
            if (!$stmt) die("Database error: " . $conn->error);
            $stmt->bind_param("si", $status, $contentId);
        }
        if ($stmt->execute()) {
            setFlash('success', 'Content ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully!');
            header("Location: admin_content_approval.php");
            exit;
        }
    }
}

$approved = 0;
$rejected = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM problems WHERE is_approved = 1");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $approved += $r->fetch_assoc()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM problems WHERE is_approved = -1");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $rejected += $r->fetch_assoc()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM challenges WHERE status = 'open'");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $approved += $r->fetch_assoc()['cnt'];

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM challenges WHERE status = 'closed'");
if (!$stmt) die("DB error");
$stmt->execute();
$r = $stmt->get_result();
if ($r) $rejected += $r->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Approval - CivicLedger Admin</title>
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
                    <span class="font-bold text-xl text-white">Content Approval</span>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="mentor_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-home mr-1"></i>Dashboard</a>
                    <a href="admin_content_approval.php" class="px-4 py-2 bg-green-600 text-white rounded-lg"><i class="fas fa-file-alt mr-1"></i>Content</a>
                    <a href="admin_solution_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-check mr-1"></i>Solutions</a>
                    <a href="team_chat.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-comments mr-1"></i>Team Chat</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Pending Review</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($pendingContent); ?></p>
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
                        <p class="text-gray-600 text-sm">Total Content</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo count($pendingContent) + $approved; ?></p>
                    </div>
                    <i class="fas fa-file-alt text-blue-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Pending Content Review</h2>
                <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">
                    <?php echo count($pendingContent); ?> Items
                </span>
            </div>

            <?php if (count($pendingContent) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Title</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Author</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Submitted</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($pendingContent as $content): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?php echo $content['type'] === 'problem' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                    <?php echo ucfirst($content['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($content['title']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($content['author_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($content['category']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($content['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button onclick="showApprovalModal('<?php echo $content['type']; ?>', <?php echo $content['id']; ?>, 'approve')" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs font-semibold">
                                    <i class="fas fa-check mr-1"></i>Approve
                                </button>
                                <button onclick="showApprovalModal('<?php echo $content['type']; ?>', <?php echo $content['id']; ?>, 'reject')" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs font-semibold">
                                    <i class="fas fa-times mr-1"></i>Reject
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="px-6 py-12 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                <p class="text-gray-600">All content has been reviewed! ✓</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Approve Content?</h3>
            </div>
            <form method="POST" class="px-6 py-4 space-y-4">
                <input type="hidden" name="content_type" id="contentType">
                <input type="hidden" name="content_id" id="contentId">
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
        function showApprovalModal(type, id, action) {
            document.getElementById('contentType').value = type;
            document.getElementById('contentId').value = id;
            document.getElementById('action').value = action;
            document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Content?' : 'Reject Content?';
            document.getElementById('approvalModal').classList.remove('hidden');
        }
        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }
    </script>
</body>
</html>
