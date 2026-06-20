<?php
/**
 * CivicLedger - Subscription Plans
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$sub = getUserSubscription($user['id']);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? '';
    
    if (!in_array($plan, ['plus', 'premium'])) {
        $errors[] = "Invalid plan selected";
    } else {
        global $conn;
        $price = $plan === 'plus' ? 499 : 999;
        
        // Update subscription (simulated payment)
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        
        // Check if active subscription exists
        $stmt = $conn->prepare("SELECT id FROM user_subscriptions WHERE user_id = ? AND status = 'active'");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $existing = $r ? $r->fetch_assoc() : null;
        
        if ($existing) {
            // Update existing
            $stmt = $conn->prepare("UPDATE user_subscriptions SET plan = ?, start_date = ?, end_date = ?, mentor_messages_used = 0, mentor_messages_reset_at = CURDATE() WHERE user_id = ?");
            $stmt->bind_param("sssi", $plan, $startDate, $endDate, $user['id']);
        } else {
            // Create new
            $stmt = $conn->prepare("INSERT INTO user_subsributions (user_id, plan, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user['id'], $plan, $startDate, $endDate);
        }
        
        if ($stmt->execute()) {
            $success = true;
            setFlash('success', "Successfully upgraded to {$plan}! Your benefits are now active.");
            header("Location: subscription.php");
            exit;
        } else {
            $errors[] = "Failed to process upgrade. Please try again.";
        }
    }
}

$user = getCurrentUser();
$sub = getUserSubscription($user['id']);
getHeader('Subscription Plans');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-crown text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800">Upgrade Your Plan</h1>
        <p class="text-gray-500 mt-2">Get more features and unlock your potential</p>
    </div>
    
    <!-- Current Plan Banner -->
    <?php if ($sub['is_plus']): ?>
        <div class="bg-gradient-to-r from-purple-500 to-purple-700 text-white rounded-2xl p-6 mb-8 text-center">
            <i class="fas fa-check-circle text-2xl mr-2"></i>
            You're on the <?= ucfirst($sub['plan']) ?> plan! Enjoy all benefits.
        </div>
    <?php else: ?>
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8 text-center">
            <p class="text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                You're currently on the Free plan. 
                <?= $sub['mentor_messages_limit'] - $sub['mentor_messages_used'] ?> mentor messages remaining this week.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <!-- Free Plan -->
        <div class="bg-white rounded-2xl shadow-lg p-8 border-2 <?= $sub['plan'] === 'free' ? 'border-primary' : 'border-gray-200' ?>">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-gray-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Free</h3>
                <p class="text-4xl font-bold text-gray-600 mt-4">Rs. 0</p>
                <p class="text-gray-500">Forever</p>
            </div>
            
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Post problems</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Submit solutions</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Form teams</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>3 mentor messages/week</span>
                </li>
                <li class="flex items-center gap-2 text-gray-400">
                    <i class="fas fa-times text-gray-300"></i>
                    <span>Unlimited mentor chat</span>
                </li>
                <li class="flex items-center gap-2 text-gray-400">
                    <i class="fas fa-times text-gray-300"></i>
                    <span>Priority support</span>
                </li>
            </ul>
            
            <?php if ($sub['plan'] === 'free'): ?>
                <div class="text-center text-gray-500 font-semibold">Current Plan</div>
            <?php else: ?>
                <button disabled class="w-full bg-gray-200 text-gray-500 font-bold py-3 rounded-lg cursor-not-allowed">
                    Free Plan
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Plus Plan -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border-2 <?= $sub['plan'] === 'plus' ? 'border-purple-500' : 'border-purple-200' ?> relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-purple-500 text-white px-4 py-1 text-sm font-semibold rounded-bl-lg">
                POPULAR
            </div>
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-star text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Plus</h3>
                <p class="text-4xl font-bold text-purple-600 mt-4">Rs. 499</p>
                <p class="text-gray-500">/ 30 days</p>
            </div>
            
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Everything in Free</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span><strong>Unlimited</strong> mentor messages</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Priority support</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>10% consultation discount</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Featured profile</span>
                </li>
            </ul>
            
            <?php if ($sub['plan'] === 'plus'): ?>
                <div class="text-center bg-purple-100 text-purple-700 font-semibold py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>Current Plan
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan" value="plus">
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-rocket mr-2"></i>Upgrade to Plus
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Premium Plan -->
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl shadow-lg p-8 border-2 <?= $sub['plan'] === 'premium' ? 'border-amber-500' : 'border-amber-200' ?> relative">
            <div class="absolute top-0 right-0 bg-amber-500 text-white px-4 py-1 text-sm font-semibold rounded-bl-lg">
                BEST VALUE
            </div>
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Premium</h3>
                <p class="text-4xl font-bold text-amber-600 mt-4">Rs. 999</p>
                <p class="text-gray-500">/ 30 days</p>
            </div>
            
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Everything in Plus</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span><strong>Priority</strong> challenge listing</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>20% consultation discount</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Badges & certificates</span>
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-check text-green-500"></i>
                    <span>Exclusive webinars</span>
                </li>
            </ul>
            
            <?php if ($sub['plan'] === 'premium'): ?>
                <div class="text-center bg-amber-500 text-white font-semibold py-3 rounded-lg">
                    <i class="fas fa-crown mr-2"></i>Current Plan
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan" value="premium">
                    <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-crown mr-2"></i>Upgrade to Premium
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Features Comparison -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6 text-center"><i class="fas fa-balance-scale mr-2 text-primary"></i>Plan Comparison</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Feature</th>
                        <th class="px-4 py-3 text-center">Free</th>
                        <th class="px-4 py-3 text-center">Plus</th>
                        <th class="px-4 py-3 text-center">Premium</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-3">Mentor Messages</td>
                        <td class="px-4 py-3 text-center">3/week</td>
                        <td class="px-4 py-3 text-center">Unlimited</td>
                        <td class="px-4 py-3 text-center">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Consultation Discount</td>
                        <td class="px-4 py-3 text-center">0%</td>
                        <td class="px-4 py-3 text-center">10%</td>
                        <td class="px-4 py-3 text-center">20%</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Priority Support</td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-times text-red-400"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-check text-green-500"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-check text-green-500"></i></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Challenge Priority</td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-times text-red-400"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-times text-red-400"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-check text-green-500"></i></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Badges & Certificates</td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-times text-red-400"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-times text-red-400"></i></td>
                        <td class="px-4 py-3 text-center"><i class="fas fa-check text-green-500"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>