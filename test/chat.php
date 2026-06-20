<?php
/**
 * CivicLedger - Team Group Chat
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
global $conn;

$teamId = (int)($_GET['team_id'] ?? 0);

if (!$teamId) {
    // Get user's team
    $stmt = $conn->prepare("SELECT id FROM teams WHERE leader_id = ? OR JSON_CONTAINS(members, JSON_ARRAY(?))");
    $userIdJson = json_encode($user['id']);
    $stmt->bind_param("is", $user['id'], $userIdJson);
    $stmt->execute();
    $r = $stmt->get_result();
    $team = $r ? $r->fetch_assoc() : null;
    $teamId = $team['id'] ?? 0;
}

if (!$teamId) {
    setFlash('error', 'You are not in a team.');
    header("Location: team_formation.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$r = $stmt->get_result();
$team = $r ? $r->fetch_assoc() : null;

if (!$team) {
    header("Location: team_formation.php");
    exit;
}

// Check membership
$members = json_decode($team['members'], true) ?: [];
if ($team['leader_id'] !== $user['id'] && !in_array($user['id'], $members)) {
    setFlash('error', 'You are not a member of this team.');
    header("Location: team_formation.php");
    exit;
}

// Get team member details
$memberDetails = [];
foreach ($members as $mid) {
    $memberDetails[] = getUserById($mid);
}

getHeader('Team Chat - ' . e($team['name']));
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Chat Header -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="gradient-bg p-6 text-white flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">
                    <i class="fas fa-comments mr-2"></i><?= e($team['name']) ?>
                </h1>
                <p class="text-purple-100">
                    <?= count($memberDetails) ?> members • Rank: <?= $team['rank_points'] ?> pts
                </p>
            </div>
            <a href="team_progress.php?team_id=<?= $teamId ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-chart-line mr-2"></i>Progress
            </a>
        </div>
        
        <!-- Team Members -->
        <div class="p-4 bg-gray-50 flex flex-wrap gap-2">
            <?php foreach ($memberDetails as $m): ?>
                <?php if ($m): ?>
                    <span class="px-3 py-1 rounded-full bg-gray-200 text-sm flex items-center gap-2">
                        <?php if ($m['id'] === $team['leader_id']): ?>
                            <i class="fas fa-crown text-amber-500"></i>
                        <?php endif; ?>
                        <?= e($m['name']) ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Messages Container -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden" style="height: 500px; display: flex; flex-direction: column;">
        <div id="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
            <!-- Messages loaded via AJAX -->
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Loading messages...</p>
            </div>
        </div>
        
        <!-- Message Input -->
        <div class="border-t p-4 bg-gray-50">
            <form id="messageForm" class="flex gap-4">
                <input type="hidden" name="group_id" value="team_<?= $teamId ?>">
                <input type="text" id="messageInput" name="message" required
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="Type your message..." autocomplete="off">
                <button type="submit" class="bg-primary hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition-all">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-refresh messages every 3 seconds
let lastMessageId = 0;

function loadMessages() {
    const groupId = 'team_<?= $teamId ?>';
    fetch(`api_get_messages.php?group_id=${groupId}&last_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('messagesContainer');
            
            if (data.messages && data.messages.length > 0) {
                let html = '';
                data.messages.forEach(msg => {
                    const isOwn = msg.sender_id == <?= $user['id'] ?>;
                    const time = new Date(msg.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    html += `
                        <div class="flex ${isOwn ? 'justify-end' : 'justify-start'} animate-fadeIn">
                            <div class="max-w-[70%] ${isOwn ? 'message-bubble-own text-white' : 'message-bubble-other text-gray-800'} rounded-2xl px-4 py-3">
                                ${!isOwn ? `<p class="text-xs font-semibold mb-1 opacity-75">${escapeHtml(msg.sender_name)}</p>` : ''}
                                <p class="break-words">${escapeHtml(msg.message)}</p>
                                <p class="text-xs mt-1 opacity-50 text-right">${time}</p>
                            </div>
                        </div>
                    `;
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                
                if (lastMessageId > 0) {
                    container.innerHTML = html;
                }
            } else if (container.innerHTML.includes('Loading')) {
                container.innerHTML = '<div class="text-center py-12 text-gray-500"><i class="fas fa-comments text-4xl mb-4"></i><p>No messages yet. Start the conversation!</p></div>';
            }
        })
        .catch(err => console.log('Error loading messages:', err));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Send message
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    fetch('api_send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `group_id=team_<?= $teamId ?>&message=${encodeURIComponent(message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadMessages();
        } else {
            alert(data.error || 'Failed to send message');
        }
    })
    .catch(err => alert('Failed to send message'));
});

// Initial load and poll
loadMessages();
setInterval(loadMessages, 3000);
</script>

<?php getFooter(); ?>