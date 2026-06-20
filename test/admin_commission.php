<?php
/**
 * CivicLedger - Admin Commission Settings
 */
require_once 'config.php';
requireRole('admin');

$user = getCurrentUser();
global $conn;
$errors = [];
$success = false;

$commission = getCommissionPercent();

// Get platform stats
$stats = getStats();

// Get recent transactions
$qr = $conn->query("SELECT s.*, u.name as user_name FROM solutions s JOIN users u ON s.user_id = u.id WHERE s.status = 'rewarded' ORDER BY s.created_at DESC LIMIT 10");
$recentSolutions = $qr ? $qr->fetch_all(MYSQLI_ASSOC) : [];

// Update commission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCommission = (float)($_POST['commission_percent'] ?? 10);
    
    if ($newCommission < 0 || $newCommission > 50) {
        $errors[] = "Commission must be between 0% and 50%";
    } else {
        $stmt = $conn->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = 'commission_percent'");
        $stmt->bind_param("s", $newCommission);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            // Insert if not exists
            $conn->query("INSERT INTO platform_settings (setting_key, setting_value) VALUES ('commission_percent', '$newCommission') ON DUPLICATE KEY UPDATE setting_value = '$newCommission'");
        }
        
        $success = true;
        $commission = $newCommission;
        setFlash('success', "Commission updated to {$newCommission}%");
        header("Location: admin_commission.php");
        exit;
    }
}

getHeader('Admin - Commission Settings');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="gradient-bg rounded-2xl p-8 text-white mb-8">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-cog text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold">Admin Dashboard</h1>
                <p class="text-purple-100">Welcome, <?= e($user['name']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="flex flex-wrap gap-4 mb-8">
        <a href="admin_commission.php" class="px-6 py-3 rounded-lg font-semibold bg-primary text-white">
            <i class="fas fa-percentage mr-2"></i>Commission Settings
        </a>
        <a href="dashboard.php" class="px-6 py-3 rounded-lg font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300">
            <i class="fas fa-home mr-2"></i>View Dashboard
        </a>
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
    
    <!-- Commission Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-percentage mr-2 text-primary"></i>Commission Settings
            </h2>
            
            <form method="POST" class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Commission is deducted from rewards before disbursement. Current rate: <strong><?= $commission ?>%</strong>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Commission Percentage
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="number" name="commission_percent" step="0.1" min="0" max="50" required
                            value="<?= $commission ?>"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        <span class="text-2xl font-bold text-gray-600">%</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Recommended: 5-15%</p>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-save mr-2"></i>Update Commission
                </button>
            </form>
        </div>
        
        <!-- Commission Calculator -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-calculator mr-2 text-amber-500"></i>Commission Calculator
            </h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reward Amount (Rs)</label>
                    <input type="number" id="calcReward" step="0.01" min="0" value="10000"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        oninput="calculateCommission()">
                </div>
                
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Gross Reward:</span>
                        <span class="font-bold" id="calcGross">Rs. 10,000</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Commission (<?= $commission ?>%):</span>
                        <span class="font-bold text-red-600" id="calcCommission">Rs. 1,000</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-gray-800 font-semibold">Net to Recipient:</span>
                        <span class="font-bold text-green-600" id="calcNet">Rs. 9,000</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Platform Stats -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6">
            <i class="fas fa-chart-bar mr-2 text-primary"></i>Platform Overview
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center p-6 bg-blue-50 rounded-xl">
                <p class="text-4xl font-bold text-primary"><?= $stats['problems'] ?></p>
                <p class="text-gray-600 mt-2">Total Problems</p>
            </div>
            <div class="text-center p-6 bg-green-50 rounded-xl">
                <p class="text-4xl font-bold text-green-600"><?= $stats['solutions'] ?></p>
                <p class="text-gray-600 mt-2">Solutions</p>
            </div>
            <div class="text-center p-6 bg-amber-50 rounded-xl">
                <p class="text-4xl font-bold text-amber-600"><?= formatCurrency($stats['rewards']) ?></p>
                <p class="text-gray-600 mt-2">Rewards Disbursed</p>
            </div>
            <div class="text-center p-6 bg-purple-50 rounded-xl">
                <p class="text-4xl font-bold text-purple-600"><?= $stats['sponsors'] ?></p>
                <p class="text-gray-600 mt-2">Sponsorships</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Reward Transactions -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">
            <i class="fas fa-receipt mr-2 text-green-500"></i>Recent Reward Transactions
        </h2>
        
        <?php if (empty($recentSolutions)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-receipt text-4xl mb-4"></i>
                <p>No reward transactions yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Recipient</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Solution</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Gross</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Commission</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Net</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recentSolutions as $sol): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold"><?= e($sol['user_name']) ?></td>
                                <td class="px-4 py-3"><?= e(substr($sol['title'], 0, 30)) ?>...</td>
                                <td class="px-4 py-3"><?= formatCurrency($sol['reward_gross']) ?></td>
                                <td class="px-4 py-3 text-red-600"><?= formatCurrency($sol['reward_commission']) ?></td>
                                <td class="px-4 py-3 text-green-600 font-bold"><?= formatCurrency($sol['reward_net']) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500"><?= timeAgo($sol['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <a href="register.php" class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all text-center">
            <i class="fas fa-user-plus text-3xl text-primary mb-4"></i>
            <p class="font-bold">Add User</p>
            <p class="text-sm text-gray-500">Create new account</p>
        </a>
        <a href="index.php" class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all text-center">
            <i class="fas fa-globe text-3xl text-green-600 mb-4"></i>
            <p class="font-bold">View Site</p>
            <p class="text-sm text-gray-500">Go to public site</p>
        </a>
        <a href="logout.php" class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all text-center">
            <i class="fas fa-sign-out-alt text-3xl text-red-600 mb-4"></i>
            <p class="font-bold">Logout</p>
            <p class="text-sm text-gray-500">Sign out</p>
        </a>
    </div>
</div>

<script>
function calculateCommission() {
    var reward = parseFloat(document.getElementById('calcReward').value) || 0;
    var commissionRate = 0; // 0% commission
    var commission = reward * commissionRate;
    var net = reward - commission;
    
    document.getElementById('calcCommission').textContent = 'Rs. ' + commission.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('calcNet').textContent = 'Rs. ' + net.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Initial calculation
calculateCommission();
</script>

<?php getFooter(); ?>