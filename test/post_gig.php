<?php
/**
 * CivicLedger - Post a Micro Gig
 */
require_once 'config.php';
requireRole('citizen');

$user = getCurrentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = (float)($_POST['budget'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($budget <= 0) $errors[] = "Budget must be greater than 0";
    
    if (empty($errors)) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO micro_gigs (citizen_id, title, description, budget, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $user['id'], $title, $description, $budget, $category);
        
        if ($stmt->execute()) {
            setFlash('success', 'Gig posted successfully!');
            header("Location: citizen_dashboard.php");
            exit;
        } else {
            $errors[] = "Failed to post gig. Please try again.";
        }
    }
}

getHeader('Post Micro Gig');
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-briefcase text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Post a Micro Gig</h1>
            <p class="text-gray-500 mt-2">Create paid tasks for students to help you</p>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-green-800 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>SDG 8:</strong> Micro gigs promote decent work and economic growth. Students earn 100% of the gig payment - no commission!
            </p>
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
                    <i class="fas fa-heading mr-2 text-primary"></i>Task Title *
                </label>
                <input type="text" name="title" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="e.g., Website Design for Community Center">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-align-left mr-2 text-primary"></i>Description *
                </label>
                <textarea name="description" required rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Describe the task in detail..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-coins mr-2 text-amber-500"></i>Budget (Rs) *
                    </label>
                    <input type="number" name="budget" step="0.01" min="100" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="e.g., 5000">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2 text-primary"></i>Category
                    </label>
                    <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        <option value="">Select category</option>
                        <option value="Technology">Technology</option>
                        <option value="Design">Design</option>
                        <option value="Writing">Writing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-4">
                <a href="citizen_dashboard.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Post Gig
                </button>
            </div>
        </form>
    </div>
</div>

<?php getFooter(); ?>