<?php
/**
 * CivicLedger - Team Chat
 * Team members communicate and collaborate
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
global $conn;

// Get user's teams
$stmt = $conn->prepare("
    SELECT t.* FROM teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ? OR t.leader_id = ?
    ORDER BY t.name
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("ii", $user['id'], $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$userTeams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Selected team
$teamId = (int)($_GET['team_id'] ?? 0);
$selectedTeam = null;
$teamMessages = [];
$teamMembers = [];

if ($teamId) {
    // Verify user is member of team
    $stmt = $conn->prepare("SELECT t.* FROM teams t WHERE t.id = ? AND (t.leader_id = ? OR EXISTS(SELECT 1 FROM team_members WHERE team_id = t.id AND user_id = ?))");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("iii", $teamId, $user['id'], $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    $selectedTeam = $r && $r->num_rows > 0 ? $r->fetch_assoc() : null;
    
    if ($selectedTeam) {
        // Get team members
        $stmt = $conn->prepare("
            SELECT DISTINCT u.* FROM users u
            WHERE u.id = ? OR u.id IN (SELECT user_id FROM team_members WHERE team_id = ?)
        ");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("ii", $selectedTeam['leader_id'], $teamId);
        $stmt->execute();
        $r = $stmt->get_result();
        $teamMembers = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        
        // Get team messages
        $stmt = $conn->prepare("
            SELECT tm.*, u.name as sender_name, u.profile_photo
            FROM team_messages tm
            JOIN users u ON tm.sender_id = u.id
            WHERE tm.team_id = ?
            ORDER BY tm.created_at ASC
        ");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $r = $stmt->get_result();
        $teamMessages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $selectedTeam) {
    $message = trim($_POST['message'] ?? '');
    
    if ($message && $teamId) {
        $stmt = $conn->prepare("INSERT INTO team_messages (team_id, sender_id, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("iis", $teamId, $user['id'], $message);
        if ($stmt->execute()) {
            header("Location: team_chat.php?team_id=" . $teamId);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Chat - CivicLedger</title>
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
        .message-container { max-height: calc(100vh - 300px); overflow-y: auto; }
        .message { word-wrap: break-word; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-leaf text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-gray-900">Civic<span class="text-green-600">Ledger</span></span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <?php if ($user['role'] === 'mentor'): ?>
                    <a href="mentor_dashboard.php" class="text-gray-600 hover:text-gray-900"><i class="fas fa-chart-line mr-1"></i>Dashboard</a>
                    <a href="team_chat.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium"><i class="fas fa-comments mr-1"></i>Team Chat</a>
                    <a href="admin_content_approval.php" class="text-gray-600 hover:text-gray-900"><i class="fas fa-file-alt mr-1"></i>Content</a>
                    <a href="admin_solution_approval.php" class="text-gray-600 hover:text-gray-900"><i class="fas fa-check mr-1"></i>Solutions</a>
                    <?php elseif ($user['role'] === 'student'): ?>
                    <a href="student_dashboard.php" class="text-gray-600 hover:text-gray-900"><i class="fas fa-graduation-cap mr-1"></i>Dashboard</a>
                    <a href="team_chat.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium"><i class="fas fa-comments mr-1"></i>Team Chat</a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-600 hover:text-gray-900"><i class="fas fa-user mr-1"></i>Profile</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 h-screen">
            <!-- Teams List -->
            <div class="bg-white rounded-lg shadow md:col-span-1">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-bold text-gray-900">My Teams</h2>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    <?php if (count($userTeams) > 0): ?>
                        <?php foreach ($userTeams as $team): ?>
                        <a href="team_chat.php?team_id=<?php echo $team['id']; ?>" class="block p-4 hover:bg-green-50 <?php echo $selectedTeam && $selectedTeam['id'] == $team['id'] ? 'bg-green-100 border-l-4 border-green-600' : ''; ?>">
                            <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($team['name']); ?></p>
                            <p class="text-xs text-gray-600 mt-1">Leader: <?php 
                                $leader = $team['leader_id'] === $user['id'] ? 'You' : 'Member'; 
                                echo $leader; 
                            ?></p>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="p-4 text-center">
                        <p class="text-sm text-gray-600">No teams yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="bg-white rounded-lg shadow md:col-span-3 flex flex-col">
                <?php if ($selectedTeam): ?>
                    <!-- Team Header -->
                    <div class="p-4 border-b bg-gradient-to-r from-green-50 to-emerald-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($selectedTeam['name']); ?></h2>
                                <p class="text-sm text-gray-600 mt-1"><?php echo count($teamMembers); ?> members</p>
                            </div>
                            <button onclick="toggleMemberList()" class="px-3 py-1 bg-green-600 text-white rounded text-sm">
                                <i class="fas fa-users mr-1"></i>Members
                            </button>
                        </div>
                        
                        <!-- Members List (Hidden by default) -->
                        <div id="memberList" class="hidden mt-4 p-4 bg-white rounded border border-gray-200">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Team Members:</p>
                            <div class="space-y-2">
                                <?php foreach ($teamMembers as $member): ?>
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-green-700 text-xs"></i>
                                    </div>
                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($member['name']); ?></span>
                                    <?php if ($member['id'] === $user['id']): ?>
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">(You)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 message-container p-4 space-y-4 overflow-y-auto">
                        <?php if (count($teamMessages) > 0): ?>
                            <?php foreach ($teamMessages as $msg): ?>
                            <div class="message <?php echo $msg['sender_id'] === $user['id'] ? 'text-right' : ''; ?>">
                                <div class="inline-block max-w-xs <?php echo $msg['sender_id'] === $user['id'] ? 'bg-green-600' : 'bg-gray-100'; ?> <?php echo $msg['sender_id'] === $user['id'] ? 'text-white' : 'text-gray-900'; ?> rounded-lg px-4 py-2">
                                    <?php if ($msg['sender_id'] !== $user['id']): ?>
                                    <p class="text-xs font-semibold <?php echo $msg['sender_id'] === $user['id'] ? 'text-white' : 'text-green-700'; ?> mb-1"><?php echo htmlspecialchars($msg['sender_name']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comments text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No messages yet. Start the conversation!</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 border-t bg-gray-50">
                        <form method="POST" class="flex space-x-2">
                            <input type="text" name="message" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <i class="fas fa-comments text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg">Select a team to start chatting</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleMemberList() {
            const list = document.getElementById('memberList');
            list.classList.toggle('hidden');
        }

        // Auto-scroll to bottom
        const messageContainer = document.querySelector('.message-container');
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    </script>
</body>
</html>
