<?php
/**
 * CivicLedger - Sponsor Message Admin
 */
require_once 'config.php';
requireRole('sponsor');

$user = getCurrentUser();
global $conn;

$error = '';
$success = '';

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $error = 'Please fill in both subject and message.';
    } elseif (strlen($message) < 10) {
        $error = 'Message must be at least 10 characters.';
    } else {
        $stmt = $conn->prepare("INSERT INTO admin_messages (sender_id, subject, message, sender_role) VALUES (?, ?, ?, 'sponsor')");
        if ($stmt === false) {
            $error = 'Database error. Please try again.';
        } else {
            $stmt->bind_param("iss", $user['id'], $subject, $message);
            if ($stmt->execute()) {
                $success = 'Message sent to admin successfully!';
                logSponsorActivity($user['id'], 'message_admin', 'Sent message: ' . substr($subject, 0, 50));
            } else {
                $error = 'Failed to send message. Please try again.';
            }
        }
    }
}

// Get conversation with admin - all messages sent by this sponsor
$stmt = $conn->prepare("SELECT * FROM admin_messages WHERE sender_id = ? ORDER BY created_at DESC LIMIT 50");
if ($stmt === false) {
    $messages = [];
} else {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    $messages = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

// Get admin replies (messages where sender is admin and addressed to this sponsor)
$stmt2 = $conn->prepare("SELECT * FROM admin_messages WHERE sender_id = (SELECT id FROM users WHERE role = 'admin' LIMIT 1) AND (receiver_id = ? OR message LIKE ?) ORDER BY created_at DESC LIMIT 50");
if ($stmt2 !== false) {
    $searchPattern = '%' . $user['id'] . '%';
    $stmt2->bind_param("is", $user['id'], $searchPattern);
    $stmt2->execute();
    $r2 = $stmt2->get_result();
    $replies = $r2 ? $r2->fetch_all(MYSQLI_ASSOC) : [];
    
    // Merge and sort all messages by date
    $allMessages = array_merge($messages, $replies);
    usort($allMessages, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    $messages = $allMessages;
} else {
    $messages = $messages ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Admin - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { civic: { 600: '#059669' } } } } }</script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-gray-800">CivicLedger <span class="text-blue-600">Nepal</span></span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="sponsor_dashboard.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Dashboard</a>
                    <a href="create_challenge.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Create Challenge</a>
                    <a href="create_sponsorship.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Sponsor</a>
                    <a href="sponsor_progress.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Analytics</a>
                    <a href="sponsor_message_admin.php" class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg font-medium">Message Admin</a>
                    <a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl"><i class="fas fa-bars"></i></button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="sponsor_dashboard.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Dashboard</a>
                <a href="create_challenge.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Create Challenge</a>
                <a href="create_sponsorship.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Sponsor</a>
                <a href="sponsor_progress.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Analytics</a>
                <a href="sponsor_message_admin.php" class="block py-2 px-4 bg-indigo-50 text-indigo-700 rounded-lg">Message Admin</a>
                <a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-envelope text-indigo-600 mr-3"></i>Message Admin
                </h1>
                <p class="text-gray-500 mt-1">Contact platform administration for support or inquiries</p>
            </div>
            <a href="sponsor_dashboard.php" class="mt-4 md:mt-0 text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Compose Box -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-pen text-indigo-600 text-sm"></i>
                </div>
                Compose New Message
            </h2>

            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i><?= e($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                <i class="fas fa-check-circle mr-2"></i><?= e($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                    <input type="text" name="subject" placeholder="e.g., Challenge submission issue, Partnership inquiry..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           value="<?= e($_POST['subject'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="6" placeholder="Describe your inquiry or issue in detail..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                              required><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="send_message" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all">
                    <i class="fas fa-paper-plane mr-2"></i>Send to Admin
                </button>
            </form>
        </div>

        <!-- Conversation History -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold mb-4 flex items-center">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-comments text-blue-600 text-sm"></i>
                </div>
                Conversation History
                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs"><?= count($messages) ?> messages</span>
            </h2>

            <?php if (empty($messages)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-inbox text-5xl mb-4"></i>
                <p>No messages yet. Start a conversation above.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4 max-h-[500px] overflow-y-auto">
                <?php 
                // Group messages by conversation thread
                $groupedMessages = [];
                foreach ($messages as $msg) {
                    $key = $msg['subject'];
                    if (!isset($groupedMessages[$key])) {
                        $groupedMessages[$key] = [];
                    }
                    $groupedMessages[$key][] = $msg;
                }
                
                foreach ($groupedMessages as $subject => $thread): 
                    $firstMsg = $thread[0];
                    $isFromSponsor = ($firstMsg['sender_id'] == $user['id']);
                ?>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <h4 class="font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-gray-400"></i><?= e($subject) ?></h4>
                    </div>
                    <div class="p-4 space-y-3">
                        <?php foreach ($thread as $msg): 
                            $isOutgoing = ($msg['sender_id'] == $user['id']);
                            $msgClass = $isOutgoing ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-200';
                        ?>
                        <div class="p-4 rounded-xl <?= $msgClass ?> border">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm <?= $isOutgoing ? 'text-indigo-700' : 'text-gray-700' ?>">
                                        <?php if ($isOutgoing): ?>
                                            <i class="fas fa-arrow-up mr-1"></i>You (to Admin)
                                        <?php else: ?>
                                            <i class="fas fa-arrow-down mr-1"></i>Admin Reply
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400"><?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?></span>
                            </div>
                            <p class="text-sm text-gray-700"><?= nl2br(e($msg['message'])) ?></p>
                            <?php if (!empty($msg['reply_message'])): ?>
                            <div class="mt-3 p-3 bg-white rounded-lg border border-green-200">
                                <p class="text-xs font-semibold text-green-600 mb-1"><i class="fas fa-reply mr-1"></i>Admin Reply:</p>
                                <p class="text-sm text-gray-700"><?= nl2br(e($msg['reply_message'])) ?></p>
                                <?php if (!empty($msg['replied_at'])): ?>
                                <p class="text-xs text-gray-400 mt-1">Replied: <?= date('M j, Y g:i A', strtotime($msg['replied_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
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