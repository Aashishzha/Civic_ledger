<?php
/**
 * CivicLedger - Admin User Management
 * View, manage, warn, suspend, and delete users
 */
require_once 'config.php';
requireRole(['admin']);

$user = getCurrentUser();
global $conn;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'suspend':
            $targetId = intval($_POST['user_id']);
            $reason = trim($_POST['reason'] ?? 'Spam or violation of terms');
            suspendUser($targetId, $reason);
            setFlash('success', 'User suspended successfully');
            break;
            
        case 'activate':
            $targetId = intval($_POST['user_id']);
            activateUser($targetId);
            setFlash('success', 'User activated successfully');
            break;
            
        case 'warning':
            $targetId = intval($_POST['user_id']);
            $message = trim($_POST['warning_message'] ?? '');
            if ($message) {
                sendWarning($targetId, $message, $user['id']);
                setFlash('success', 'Warning sent to user');
            }
            break;
            
        case 'delete':
            $targetId = intval($_POST['user_id']);
            if ($targetId !== $user['id']) { // Can't delete yourself
                $result = deleteUser($targetId);
                if (is_array($result) && isset($result['error'])) {
                    setFlash('error', $result['error']);
                } else {
                    setFlash('success', 'User deleted successfully');
                }
            }
            break;
    }
    
    header("Location: admin_users.php");
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Get users based on filter
if ($search) {
    $users = searchUsers($search);
} elseif ($filter === 'all') {
    $users = getAllUsers(100);
} else {
    $users = getUsersByRole($filter, 100);
}

// Get user counts
$roleCounts = getUserCountByRole();
$totalUsers = array_sum($roleCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - CivicLedger Admin</title>
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
                    <a href="admin_users.php" class="px-4 py-2 bg-green-600 text-white rounded-lg"><i class="fas fa-users mr-1"></i>Users</a>
                    <a href="admin_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-check-double mr-1"></i>Approvals</a>
                    <a href="admin_teams.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                    <a href="admin_success_stories.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Stories</a>
                    <a href="public_ledger.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg"><i class="fas fa-book mr-1"></i>Ledger</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-users mr-3 text-green-600"></i>User Management</h1>
                    <p class="text-gray-500 mt-1">Manage all platform users, send warnings, suspend or delete accounts.</p>
                </div>
                
                <!-- Stats -->
                <div class="flex gap-4 mt-4 md:mt-0">
                    <div class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-center">
                        <div class="text-2xl font-bold"><?= $totalUsers ?></div>
                        <div class="text-xs">Total Users</div>
                    </div>
                    <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-center">
                        <div class="text-2xl font-bold"><?= $roleCounts['student'] ?? 0 ?></div>
                        <div class="text-xs">Students</div>
                    </div>
                    <div class="bg-amber-100 text-amber-700 px-4 py-2 rounded-lg text-center">
                        <div class="text-2xl font-bold"><?= $roleCounts['citizen'] ?? 0 ?></div>
                        <div class="text-xs">Citizens</div>
                    </div>
                    <div class="bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-center">
                        <div class="text-2xl font-bold"><?= $roleCounts['mentor'] ?? 0 ?></div>
                        <div class="text-xs">Mentors</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Role Filters -->
                <div class="flex flex-wrap gap-2">
                    <a href="?filter=all" class="px-4 py-2 rounded-lg <?= $filter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        All (<?= $totalUsers ?>)
                    </a>
                    <a href="?filter=student" class="px-4 py-2 rounded-lg <?= $filter === 'student' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        Students
                    </a>
                    <a href="?filter=citizen" class="px-4 py-2 rounded-lg <?= $filter === 'citizen' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        Citizens
                    </a>
                    <a href="?filter=sponsor" class="px-4 py-2 rounded-lg <?= $filter === 'sponsor' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        Sponsors
                    </a>
                    <a href="?filter=mentor" class="px-4 py-2 rounded-lg <?= $filter === 'mentor' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        Mentors
                    </a>
                    <a href="?filter=admin" class="px-4 py-2 rounded-lg <?= $filter === 'admin' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-all">
                        Admins
                    </a>
                </div>
                
                <!-- Search -->
                <form method="GET" class="flex-1">
                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                    <div class="flex gap-2">
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, or phone..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-all">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-sm text-gray-600">
                            <th class="px-6 py-4 font-semibold">User</th>
                            <th class="px-6 py-4 font-semibold">Role</th>
                            <th class="px-6 py-4 font-semibold">Trust Score</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Joined</th>
                            <th class="px-6 py-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                                        <?= getInitials($u['name']) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800"><?= e($u['name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= e($u['email']) ?></div>
                                        <?php if (!empty($u['phone'])): ?>
                                        <div class="text-xs text-gray-400"><i class="fas fa-phone mr-1"></i><?= e($u['phone']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $roleColors = [
                                    'admin' => 'bg-red-100 text-red-700',
                                    'mentor' => 'bg-purple-100 text-purple-700',
                                    'sponsor' => 'bg-amber-100 text-amber-700',
                                    'student' => 'bg-blue-100 text-blue-700',
                                    'citizen' => 'bg-green-100 text-green-700'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $roleColors[$u['role']] ?? 'bg-gray-100 text-gray-700' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= getTrustBadgeClass($u['trust_score']) ?>">
                                        <?= $u['trust_score'] ?? 100 ?>
                                    </span>
                                    <span class="text-xs text-gray-500"><?= getTrustLabel($u['trust_score'] ?? 100) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($u['is_active']): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                                <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    <i class="fas fa-ban mr-1"></i>Suspended
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('M j, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <!-- Send Warning -->
                                    <button onclick="showWarningModal(<?= $u['id'] ?>, '<?= e(addslashes($u['name'])) ?>')" class="bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1 rounded-lg text-xs font-medium transition-all" title="Send Warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    
                                    <?php if ($u['is_active']): ?>
                                    <!-- Suspend -->
                                    <form method="POST" class="inline" onsubmit="return confirm('Suspend this user?')">
                                        <input type="hidden" name="action" value="suspend">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="reason" value="Violation of platform terms">
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded-lg text-xs font-medium transition-all" title="Suspend">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <!-- Activate -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded-lg text-xs font-medium transition-all" title="Activate">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['id'] !== $user['id']): ?>
                                    <!-- Delete -->
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-600 px-3 py-1 rounded-lg text-xs font-medium transition-all" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- View Warnings -->
                                    <button onclick="showWarnings(<?= $u['id'] ?>)" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded-lg text-xs font-medium transition-all" title="View Warnings">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p>No users found.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div id="warningModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>Send Warning</h3>
            <p class="text-gray-600 mb-4">Send warning to: <span id="warningUserName" class="font-semibold"></span></p>
            <form method="POST">
                <input type="hidden" name="action" value="warning">
                <input type="hidden" name="user_id" id="warningUserId" value="">
                <textarea name="warning_message" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 mb-4" rows="4" placeholder="Enter warning message..."></textarea>
                <div class="flex gap-3">
                    <button type="button" onclick="hideWarningModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 rounded-xl font-medium transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-3 rounded-xl font-medium transition-all">Send Warning</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Warnings Modal -->
    <div id="warningsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-history text-blue-500 mr-2"></i>Warning History</h3>
            <div id="warningsContent"></div>
            <button onclick="hideWarningsModal()" class="mt-4 w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 rounded-xl font-medium transition-all">Close</button>
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
        function showWarningModal(userId, userName) {
            document.getElementById('warningUserId').value = userId;
            document.getElementById('warningUserName').textContent = userName;
            document.getElementById('warningModal').classList.remove('hidden');
        }

        function hideWarningModal() {
            document.getElementById('warningModal').classList.add('hidden');
        }

        function showWarnings(userId) {
            fetch('api_get_user_warnings.php?user_id=' + userId)
                .then(r => r.json())
                .then(data => {
                    let html = '';
                    if (data.length === 0) {
                        html = '<p class="text-gray-500 text-center py-8">No warnings sent yet.</p>';
                    } else {
                        data.forEach(w => {
                            html += `
                                <div class="border border-gray-200 rounded-xl p-4 mb-3 bg-amber-50">
                                    <p class="text-gray-800">${escapeHtml(w.message)}</p>
                                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                                        <span>By: ${escapeHtml(w.sent_by_name)}</span>
                                        <span>${w.created_at}</span>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    document.getElementById('warningsContent').innerHTML = html;
                    document.getElementById('warningsModal').classList.remove('hidden');
                });
        }

        function hideWarningsModal() {
            document.getElementById('warningsModal').classList.add('hidden');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modals on outside click
        document.getElementById('warningModal').addEventListener('click', function(e) {
            if (e.target === this) hideWarningModal();
        });
        document.getElementById('warningsModal').addEventListener('click', function(e) {
            if (e.target === this) hideWarningsModal();
        });
    </script>

<?php
?>

</body>
</html>