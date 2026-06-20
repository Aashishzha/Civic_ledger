<?php
/**
 * CivicLedger - Admin Success Stories Management
 */
require_once 'config.php';
requireRole('admin');

$user = getCurrentUser();
global $conn;

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add story
    if (isset($_POST['add_story'])) {
        $title = trim($_POST['title'] ?? '');
        $story = trim($_POST['story'] ?? '');
        $author = trim($_POST['author_name'] ?? '');
        $role = trim($_POST['author_role'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $impact = trim($_POST['impact'] ?? '');

        if (empty($title) || empty($story) || empty($author)) {
            $error = 'Title, story, and author name are required.';
        } else {
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $conn->prepare("INSERT INTO success_stories (title, story, author_name, author_role, location, image_url, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $title, $story, $author, $role, $location, $imageUrl, $isActive, $user['id']);
            $stmt->execute();

            $success = 'Success story added successfully!';
        }
    }

    // Edit story
    if (isset($_POST['edit_story'])) {
        $id = (int)$_POST['story_id'];
        $title = trim($_POST['title'] ?? '');
        $story = trim($_POST['story'] ?? '');
        $author = trim($_POST['author_name'] ?? '');
        $role = trim($_POST['author_role'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE success_stories SET title=?, story=?, author_name=?, author_role=?, location=?, image_url=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssssii", $title, $story, $author, $role, $location, $imageUrl, $isActive, $id);
        $stmt->execute();

        $success = 'Success story updated successfully!';
    }

    // Delete story
    if (isset($_POST['delete_story'])) {
        $id = (int)$_POST['story_id'];
        $stmt = $conn->prepare("DELETE FROM success_stories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = 'Success story deleted successfully!';
    }
}

// Get all stories
$qr = $conn->query("SELECT * FROM success_stories ORDER BY created_at DESC");
$stories = $qr ? $qr->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$totalStories = count($stories);
$activeStories = count(array_filter($stories, fn($s) => $s['is_active'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Stories - CivicLedger Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { civic: { 600: '#059669' } } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-900">

    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
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
                <div class="hidden md:flex items-center space-x-3">
                    <a href="admin_dashboard.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Dashboard</a>
                    <a href="admin_users.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fas fa-users mr-1"></i>Users</a>
                    <a href="admin_approval.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fas fa-check-double mr-1"></i>Approvals</a>
                    <a href="admin_teams.php" class="px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg">Teams</a>
                    <a href="admin_success_stories.php" class="px-4 py-2 bg-green-600 text-white rounded-lg">Stories</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Logout</a>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-300 text-2xl"><i class="fas fa-bars"></i></button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="admin_dashboard.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Dashboard</a>
                <a href="admin_users.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Users</a>
                <a href="admin_approval.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Approvals</a>
                <a href="admin_teams.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 rounded-lg">Teams</a>
                <a href="admin_success_stories.php" class="block py-2 px-4 bg-green-600 text-white rounded-lg">Stories</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-600 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">
                    <i class="fas fa-star text-amber-400 mr-3"></i>Success Stories
                </h1>
                <p class="text-gray-400 mt-1">Manage featured success stories displayed on the landing page</p>
            </div>
            <a href="admin_dashboard.php" class="mt-4 md:mt-0 text-blue-400 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Total Stories</p>
                <p class="text-2xl font-bold text-white"><?= $totalStories ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Active (on Homepage)</p>
                <p class="text-2xl font-bold text-green-400"><?= $activeStories ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                <p class="text-gray-400 text-sm">Inactive</p>
                <p class="text-2xl font-bold text-gray-400"><?= $totalStories - $activeStories ?></p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-xl text-red-300">
            <i class="fas fa-exclamation-circle mr-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-xl text-green-300">
            <i class="fas fa-check-circle mr-2"></i><?= e($success) ?>
        </div>
        <?php endif; ?>

        <!-- Add Story Form -->
        <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700 mb-8">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center">
                <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-plus text-amber-600 text-sm"></i>
                </div>
                Add New Success Story
            </h2>
            <form method="POST" class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Title *</label>
                    <input type="text" name="title" placeholder="e.g., Team Green Wins Plastic-Free Award" required
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Author Name *</label>
                    <input type="text" name="author_name" placeholder="e.g., Priya Sharma" required
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Author Role</label>
                    <input type="text" name="author_role" placeholder="e.g., Team Leader"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g., Kathmandu, Nepal"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Story *</label>
                    <textarea name="story" rows="4" placeholder="Write the full success story here..." required
                              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Image URL (optional)</label>
                    <input type="url" name="image_url" placeholder="https://example.com/image.jpg"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="md:col-span-2 flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                        <span class="text-white">Show on homepage (Active)</span>
                    </label>
                    <button type="submit" name="add_story" class="ml-auto bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-6 rounded-xl transition-all">
                        <i class="fas fa-plus mr-2"></i>Add Story
                    </button>
                </div>
            </form>
        </div>

        <!-- Stories List -->
        <div class="space-y-4">
            <?php if (empty($stories)): ?>
            <div class="bg-gray-800 rounded-2xl p-12 text-center border border-gray-700">
                <i class="fas fa-book-open text-5xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">No success stories yet. Add your first story above.</p>
            </div>
            <?php else: ?>
            <?php foreach ($stories as $story): ?>
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700 card-hover transition-all">
                <div class="flex flex-col lg:flex-row gap-6">
                    <?php if (!empty($story['image_url'])): ?>
                    <div class="lg:w-48 flex-shrink-0">
                        <img src="<?= e($story['image_url']) ?>" alt="<?= e($story['title']) ?>"
                             class="w-full h-32 object-cover rounded-xl"
                             onerror="this.style.display='none'">
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-xl font-bold text-white"><?= e($story['title']) ?></h3>
                                <p class="text-gray-400 text-sm mt-1">
                                    By <?= e($story['author_name']) ?>
                                    <?php if (!empty($story['author_role'])): ?>
                                    <span class="text-gray-500">(<?= e($story['author_role']) ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($story['location'])): ?>
                                    <span class="text-gray-500 ml-2"><i class="fas fa-map-marker-alt mr-1"></i><?= e($story['location']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $story['is_active'] ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-400' ?> flex-shrink-0">
                                <?= $story['is_active'] ? '<i class="fas fa-eye mr-1"></i>Active' : '<i class="fas fa-eye-slash mr-1"></i>Inactive' ?>
                            </span>
                        </div>
                        <p class="text-gray-300 text-sm mt-2 line-clamp-3"><?= e($story['story']) ?></p>
                        <div class="flex flex-wrap items-center gap-3 mt-4">
                            <span class="text-gray-500 text-xs">Added: <?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 lg:w-40">
                        <!-- Toggle active -->
                        <form method="POST">
                            <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                            <input type="hidden" name="title" value="<?= e($story['title']) ?>">
                            <input type="hidden" name="story" value="<?= e($story['story']) ?>">
                            <input type="hidden" name="author_name" value="<?= e($story['author_name']) ?>">
                            <input type="hidden" name="author_role" value="<?= e($story['author_role']) ?>">
                            <input type="hidden" name="location" value="<?= e($story['location']) ?>">
                            <input type="hidden" name="image_url" value="<?= e($story['image_url']) ?>">
                            <input type="hidden" name="is_active" value="<?= $story['is_active'] ? '0' : '1' ?>">
                            <button type="submit" name="edit_story" class="w-full px-3 py-2 rounded-lg text-sm font-medium <?= $story['is_active'] ? 'bg-amber-900 text-amber-300 hover:bg-amber-800' : 'bg-green-900 text-green-300 hover:bg-green-800' ?>">
                                <i class="fas fa-<?= $story['is_active'] ? 'eye-slash' : 'eye' ?> mr-1"></i>
                                <?= $story['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <!-- Delete -->
                        <form method="POST" onsubmit="return confirm('Delete this story? This cannot be undone.');">
                            <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                            <button type="submit" name="delete_story" class="w-full px-3 py-2 bg-red-900 text-red-300 hover:bg-red-800 rounded-lg text-sm font-medium">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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