<?php
/**
 * CivicLedger - Mentor Chat (Student to Mentor)
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
global $conn;

$sub = getUserSubscription($user['id']);

// Get all mentors
$qm = $conn->query("SELECT * FROM users WHERE role = 'mentor'");
$mentors = $qm ? $qm->fetch_all(MYSQLI_ASSOC) : [];

// Selected mentor
$mentorId = (int)($_GET['mentor_id'] ?? 0);
$selectedMentor = $mentorId ? getUserById($mentorId) : null;

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message'] ?? '');
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    
    if ($message && $receiverId) {
        // Check subscription (for students only)
        if ($user['role'] === 'student' && !canMessageMentor($user['id'])) {
            setFlash('error', 'Please upgrade your subscription for unlimited mentor messages.');
            header("Location: subscription.php");
            exit;
        }
        
        // Insert message
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user['id'], $receiverId, $message);
        $stmt->execute();
        
        // Increment message count if student
        if ($user['role'] === 'student') {
            incrementMentorMessages($user['id']);
        }
        
        setFlash('success', 'Message sent to mentor!');
        header("Location: mentor_chat.php?mentor_id=" . $receiverId);
        exit;
    }
}

// Get messages with selected mentor
$messages = [];
if ($selectedMentor) {
    // Mark as read
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $mentorId, $user['id']);
    $stmt->execute();
    
    $stmt = $conn->prepare("SELECT m.*, u.name as sender_name FROM chat_messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC");
    $stmt->bind_param("iiii", $user['id'], $mentorId, $mentorId, $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    $messages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

// Get remaining messages
$remainingMessages = getRemainingMentorMessages($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Chat - CivicLedger</title>
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
        .gradient-bg { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .message-bubble-own { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .message-bubble-other { background: #f3f4f6; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="student_dashboard.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Dashboard</a>
                    <a href="mentor_chat.php" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg font-medium">Mentor Chat</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="student_dashboard.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Dashboard</a>
                <a href="mentor_chat.php" class="block py-2 px-4 bg-purple-50 text-purple-700 rounded-lg">Mentor Chat</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Mentor List Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="gradient-bg p-4 text-white">
                        <h2 class="font-bold"><i class="fas fa-chalkboard-teacher mr-2"></i>Available Mentors</h2>
                        <p class="text-sm text-purple-200"><?= count($mentors) ?> mentors</p>
                    </div>
                    
                    <!-- Message Limit Info -->
                    <div class="p-4 <?= $sub['is_plus'] ? 'bg-green-50' : 'bg-amber-50' ?> border-b">
                        <?php if ($sub['is_plus']): ?>
                            <p class="text-sm text-green-800 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>
                                Unlimited messages (Plus/Premium)
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-amber-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <?= $remainingMessages === PHP_INT_MAX ? 'Unlimited' : $remainingMessages ?> messages left this week
                            </p>
                            <a href="subscription.php" class="text-xs text-amber-600 hover:underline font-medium">Upgrade for unlimited →</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="divide-y max-h-96 overflow-y-auto">
                        <?php if (empty($mentors)): ?>
                            <div class="p-8 text-center text-gray-400">
                                <i class="fas fa-user-graduate text-3xl mb-2"></i>
                                <p>No mentors available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mentors as $mentor): ?>
                                <a href="mentor_chat.php?mentor_id=<?= $mentor['id'] ?>" 
                                   class="flex items-center gap-3 p-4 hover:bg-gray-50 transition-all <?= $mentorId === $mentor['id'] ? 'bg-purple-50' : '' ?>">
                                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                        <?php if ($mentor['profile_photo']): ?>
                                            <img src="<?= e($mentor['profile_photo']) ?>" class="w-12 h-12 rounded-full object-cover">
                                        <?php else: ?>
                                            <span class="font-bold text-purple-600"><?= getInitials($mentor['name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold truncate"><?= e($mentor['name']) ?></p>
                                        <p class="text-xs text-gray-500 truncate"><?= e($mentor['skills'] ?? 'Mentor') ?></p>
                                    </div>
                                    <?php if ($mentorId === $mentor['id']): ?>
                                        <i class="fas fa-comment text-purple-600"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="lg:col-span-3">
                <?php if ($selectedMentor): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden" style="height: 500px; display: flex; flex-direction: column;">
                        <!-- Chat Header -->
                        <div class="gradient-bg p-4 text-white flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <?php if ($selectedMentor['profile_photo']): ?>
                                    <img src="<?= e($selectedMentor['profile_photo']) ?>" class="w-12 h-12 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="font-bold text-xl"><?= getInitials($selectedMentor['name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg"><?= e($selectedMentor['name']) ?></h3>
                                <p class="text-sm text-purple-200"><?= e($selectedMentor['skills'] ?? 'Expert Mentor') ?></p>
                            </div>
                            <a href="profile.php?user_id=<?= $selectedMentor['id'] ?>" class="ml-auto bg-white/20 hover:bg-white/30 px-3 py-2 rounded-lg text-sm">
                                <i class="fas fa-user mr-1"></i>Profile
                            </a>
                        </div>
                        
                        <!-- Messages -->
                        <div id="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                            <?php if (empty($messages)): ?>
                                <div class="text-center py-12 text-gray-500">
                                    <i class="fas fa-comments text-4xl mb-4 text-gray-300"></i>
                                    <p class="text-lg font-medium">No messages yet</p>
                                    <p class="text-sm">Start the conversation with this mentor!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <?php $isOwn = $msg['sender_id'] == $user['id']; ?>
                                    <div class="flex <?= $isOwn ? 'justify-end' : 'justify-start' ?>">
                                        <div class="max-w-[70%] <?= $isOwn ? 'message-bubble-own text-white' : 'message-bubble-other text-gray-800' ?> rounded-2xl px-4 py-3 shadow-sm">
                                            <?php if (!$isOwn): ?>
                                                <p class="text-xs font-semibold mb-1 opacity-75"><?= e($msg['sender_name']) ?></p>
                                            <?php endif; ?>
                                            <p class="break-words"><?= e($msg['message']) ?></p>
                                            <p class="text-xs mt-1 opacity-50"><?= timeAgo($msg['created_at']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="border-t p-4 bg-gray-50">
                            <form method="POST" class="flex gap-4">
                                <input type="hidden" name="receiver_id" value="<?= $mentorId ?>">
                                <input type="text" name="message" required
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Type your message to mentor...">
                                <button type="submit" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-6 py-3 rounded-lg transition-all shadow-lg">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center h-full flex items-center justify-center">
                        <div>
                            <div class="w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comment-dots text-4xl text-purple-600"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">Select a Mentor</h2>
                            <p class="text-gray-500">Choose a mentor from the left to start chatting</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
        // Auto scroll to bottom
        var messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    </script>

</body>
</html>