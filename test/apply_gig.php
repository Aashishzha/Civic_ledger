<?php
/**
 * CivicLedger - Apply for a Gig
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$gigId = (int)($_GET['gig_id'] ?? 0);
if (!$gigId) {
    header("Location: micro_gigs.php");
    exit;
}

$stmt = $conn->prepare("SELECT g.*, u.name as citizen_name FROM micro_gigs g JOIN users u ON g.citizen_id = u.id WHERE g.id = ?");
$stmt->bind_param("i", $gigId);
$stmt->execute();
$r = $stmt->get_result();
$gig = $r ? $r->fetch_assoc() : null;

if (!$gig) {
    setFlash('error', 'Gig not found.');
    header("Location: micro_gigs.php");
    exit;
}

// Check if already applied
$stmt = $conn->prepare("SELECT id FROM gig_applications WHERE gig_id = ? AND student_id = ?");
$stmt->bind_param("ii", $gigId, $user['id']);
$stmt->execute();
$r = $stmt->get_result();
if ($r && $r->num_rows > 0) {
    setFlash('error', 'You have already applied for this gig.');
    header("Location: micro_gigs.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proposal = trim($_POST['proposal'] ?? '');
    
    if (empty($proposal)) {
        $errors[] = "Proposal is required";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO gig_applications (gig_id, student_id, proposal) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $gigId, $user['id'], $proposal);
        
        if ($stmt->execute()) {
            setFlash('success', 'Application submitted successfully!');
            header("Location: micro_gigs.php");
            exit;
        } else {
            $errors[] = "Failed to submit application.";
        }
    }
}

getHeader('Apply for Gig');
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="mb-6">
            <a href="micro_gigs.php" class="text-primary hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Gigs
            </a>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">
                    <?= e($gig['category'] ?: 'Other') ?>
                </span>
                <span class="text-2xl font-bold text-green-600"><?= formatCurrency($gig['budget']) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= e($gig['title']) ?></h1>
            <p class="text-gray-600 mb-4"><?= nl2br(e($gig['description'])) ?></p>
            <p class="text-sm text-gray-500">
                <i class="fas fa-user mr-1"></i>Posted by <?= e($gig['citizen_name']) ?>
            </p>
        </div>
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-paper-plane text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Submit Your Proposal</h2>
            <p class="text-gray-500 mt-2">Tell the citizen why you're the best fit for this job</p>
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
                    <i class="fas fa-file-alt mr-2 text-primary"></i>Your Proposal *
                </label>
                <textarea name="proposal" required rows="6"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Describe your approach, relevant experience, and how you'll complete the task..."></textarea>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>100% Payment:</strong> Students earn the full gig amount with no commission deducted!
                </p>
            </div>
            
            <div class="flex gap-4">
                <a href="micro_gigs.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-gradient-to-r from-primary to-secondary hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<?php getFooter(); ?>