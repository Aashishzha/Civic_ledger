<?php
/**
 * ELITE-4 Nepal - Create Challenge
 */
require_once 'config.php';
requireRole('sponsor');

$user = getCurrentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reward = (float)($_POST['reward_amount'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $sdgFocus = (int)($_POST['sdg_focus'] ?? 0) ?: null;
    $deadline = $_POST['deadline'] ?? null;

    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($reward <= 0) $errors[] = "Reward amount must be greater than 0";

    if (empty($errors)) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO challenges (sponsor_id, title, description, reward_amount, category, sdg_focus, deadline) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsis", $user['id'], $title, $description, $reward, $category, $sdgFocus, $deadline);

        if ($stmt->execute()) {
            logSponsorActivity($user['id'], 'create_challenge', 'Created challenge: ' . substr($title, 0, 80));
            setFlash('success', 'Challenge created successfully!');
            header("Location: sponsor_dashboard.php");
            exit;
        } else {
            $errors[] = "Failed to create challenge. Please try again.";
        }
    }
}

getHeader('Create Challenge');
?>

<div class="max-w-3xl mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trophy text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Create a Challenge</h1>
            <p class="text-gray-500 mt-2">Define a problem and reward innovative solutions.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-heading mr-2 text-primary"></i>Challenge Title *
                </label>
                <input type="text" name="title" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="e.g., Smart Waste Collection System">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-align-left mr-2 text-primary"></i>Description *
                </label>
                <textarea name="description" required rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all resize-none"
                    placeholder="Detailed description of what you're looking for..."></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-coins mr-2 text-amber-500"></i>Reward Amount (Rs) *
                    </label>
                    <input type="number" name="reward_amount" step="0.01" min="100" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="e.g., 50000">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2 text-primary"></i>Category
                    </label>
                    <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        <option value="">Select category</option>
                        <option value="Technology">Technology</option>
                        <option value="Environment">Environment</option>
                        <option value="Safety">Safety</option>
                        <option value="Health">Health</option>
                        <option value="Education">Education</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-globe-americas mr-2 text-green-600"></i>SDG Focus (Sustainable Development Goal)
                </label>
                <select name="sdg_focus" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    <option value="">Select SDG (optional)</option>
                    <option value="1">SDG 1 - No Poverty</option>
                    <option value="2">SDG 2 - Zero Hunger</option>
                    <option value="3">SDG 3 - Good Health & Well-being</option>
                    <option value="4">SDG 4 - Quality Education</option>
                    <option value="5">SDG 5 - Gender Equality</option>
                    <option value="6">SDG 6 - Clean Water & Sanitation</option>
                    <option value="7">SDG 7 - Affordable & Clean Energy</option>
                    <option value="8">SDG 8 - Decent Work & Economic Growth</option>
                    <option value="9">SDG 9 - Industry, Innovation & Infrastructure</option>
                    <option value="10">SDG 10 - Reduced Inequalities</option>
                    <option value="11">SDG 11 - Sustainable Cities & Communities</option>
                    <option value="12">SDG 12 - Responsible Consumption</option>
                    <option value="13">SDG 13 - Climate Action</option>
                    <option value="14">SDG 14 - Life Below Water</option>
                    <option value="15">SDG 15 - Life on Land</option>
                    <option value="16">SDG 16 - Peace & Justice</option>
                    <option value="17">SDG 17 - Partnerships</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-2 text-primary"></i>Deadline *
                </label>
                <input type="date" name="deadline" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    min="<?= date('Y-m-d') ?>">
            </div>

            <div class="flex gap-4">
                <a href="sponsor_dashboard.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold py-4 rounded-lg transition-all shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Create Challenge
                </button>
            </div>
        </form>
    </div>
</div>

<?php getFooter(); ?>