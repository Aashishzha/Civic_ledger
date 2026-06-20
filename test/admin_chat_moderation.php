<?php
/**
 * CivicLedger - Admin Chat Moderation
 */
require_once 'config.php';
requireRole('admin');

$user = getCurrentUser();
global $conn;

// Get all chat groups
$stmt = $conn->prepare("SELECT cg.*, t.name as team_name FROM chat_groups cg LEFT JOIN teams t ON cg.team_id = t.id ORDER BY cg.created_at DESC");
$stmt->execute();
$r = $stmt->get_result();
$chatGroups = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Filter variables
$filterGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$filterUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for messages
$sql = "SELECT cm.*, u.name as sender_name, cg.name as group_name, t.name as team_name 
        FROM chat_messages cm 
        JOIN users u ON cm.sender_id = u.id 
        LEFT JOIN chat_groups cg ON cm.group_id = cg.id 
        LEFT JOIN teams t ON cg.team_id = t.id 
        WHERE cm.is_deleted = 0";
$params = [];

if ($filterGroup > 0) {
    $sql .= " AND cm.group_id = ?";
    $params[] = $filterGroup;
}

if ($filterUser > 0) {
    $sql .= " AND cm.sender_id = ?";
    $params[] = $filterUser;
}

if (!empty($searchKeyword)) {
    $sql .= " AND cm.message LIKE ?";
    $params[] = '%' . $searchKeyword . '%';
}

$sql .= " ORDER BY cm.created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('i', count($params) - 1) . 's';
    // Fix for search parameter being string
    $allParams = $params;
    if (!empty($searchKeyword)) {
        $types = str_repeat('i', count($params) - 1) . 's';
    }
    $stmt->bind_param($types, ...$allParams);
}
$stmt->execute();
$r = $stmt->get_result();
$messages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get all users for filter
$stmt = $conn->prepare("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$r = $stmt->get_result();
$allUsers = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    $messageId = (int)$_POST['message_id'];
    
    $stmt = $conn->prepare("UPDATE chat_messages SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $user['id'], $messageId);
    $stmt->execute();
    
    setFlash('success', 'Message deleted successfully.');
    header("Location: admin_chat_moderation.php" . ($filterGroup ? "?group_id=$filterGroup" : ""));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Moderation - CivicLedger Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">

    <nav class="bg-gray-900 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-white">CivicLedger <span class="text-blue-400">Admin</span></span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Dashboard</a>
                    <a href="admin_chat_moderation.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Chat Moderation</a>
                    <a href="admin_teams.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                    <a href="admin_commission.php" class="px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg">Settings</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-white text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="admin_dashboard.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Dashboard</a>
                <a href="admin_chat_moderation.php" class="block py-2 px-4 bg-blue-600 text-white rounded-lg">Chat Moderation</a>
                <a href="admin_teams.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Teams</a>
                <a href="admin_commission.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-800 rounded-lg">Settings</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-gavel mr-3 text-red-600"></i>Chat Moderation
                </h1>
                <p class="text-gray-500 mt-1">Review all team chats and messages. Delete offensive content.</p>
            </div>
            <a href="admin_dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" class="grid md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chat Group</label>
                    <select name="group_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Groups</option>
                        <?php foreach ($chatGroups as $group): ?>
                        <option value="<?= $group['id'] ?>" <?= $filterGroup == $group['id'] ? 'selected' : '' ?>>
                            <?= e($group['name'] ?? 'Direct Chat') ?> <?= $group['team_name'] ? '(' . e($group['team_name']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <select name="user_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Users</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (<?= ucfirst(e($u['role'])) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Keyword</label>
                    <input type="text" name="search" value="<?= e($searchKeyword) ?>" placeholder="Search in messages..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-all">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="admin_chat_moderation.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-all">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Messages Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between">
                <h2 class="font-bold text-gray-800">
                    <i class="fas fa-comments mr-2 text-blue-600"></i>Messages (<?= count($messages) ?>)
                </h2>
            </div>

            <?php if (empty($messages)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                <p>No messages found.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Sender</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Group/Chat</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($messages as $msg): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium"><?= e($msg['sender_name']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="text-gray-600"><?= e($msg['group_name'] ?? 'Direct') ?></span>
                                <?php if ($msg['team_name']): ?>
                                <span class="text-xs text-blue-600 block"><?= e($msg['team_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 max-w-md">
                                <p class="text-sm text-gray-800 truncate"><?= e($msg['message']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?= date('M j, g:i A', strtotime($msg['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this message?');">
                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                    <input type="hidden" name="action" value="delete_message">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
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
    <script>setTimeout(() => { document.querySelector('.fixed.bottom-6').remove(); }, 4000);</script>
    <?php endif; ?>

    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    </script>

</body>
</html>