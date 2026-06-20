<?php
/**
 * CivicLedger - Create Sponsorship
 */
require_once 'config.php';
requireRole('sponsor');

$user = getCurrentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    if (!$recipientId) $errors[] = "Please select a recipient";
    if ($amount <= 0) $errors[] = "Amount must be greater than 0";
    
    if (empty($errors)) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO sponsorships (sponsor_id, recipient_id, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $user['id'], $recipientId, $amount, $description);
        
        if ($stmt->execute()) {
            setFlash('success', 'Sponsorship request sent successfully!');
            header("Location: my_sponsorships.php");
            exit;
        } else {
            $errors[] = "Failed to create sponsorship. Please try again.";
        }
    }
}

// Get all users for selection
global $conn;
$qr = $conn->query("SELECT id, name, role FROM users WHERE id != " . $user['id'] . " ORDER BY role, name");
$users = $qr ? $qr->fetch_all(MYSQLI_ASSOC) : [];

getHeader('Create Sponsorship');
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-hand-holding-usd text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Create Sponsorship</h1>
            <p class="text-gray-500 mt-2">Directly sponsor students or citizens</p>
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
                    <i class="fas fa-user mr-2 text-primary"></i>Select Recipient *
                </label>
                <select name="recipient_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    <option value="">-- Select a user --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= ucfirst(e($u['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-coins mr-2 text-amber-500"></i>Amount (Rs) *
                </label>
                <input type="number" name="amount" step="0.01" min="100" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="e.g., 5000">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-comment mr-2 text-primary"></i>Description
                </label>
                <textarea name="description" rows="4"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Describe your sponsorship purpose..."></textarea>
            </div>
            
            <div class="flex gap-4">
                <a href="sponsor_dashboard.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Send Sponsorship
                </button>
            </div>
        </form>
    </div>
</div>

<?php getFooter(); ?>