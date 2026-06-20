<?php
/**
 * ELITE-4 Nepal - Subscription Plans
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$sub = getUserSubscription($user['id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? '';

    if (!in_array($plan, ['plus', 'premium'])) {
        $errors[] = "Invalid plan selected";
    } else {
        global $conn;
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));

        // Check if active subscription exists
        $stmt = $conn->prepare("SELECT id FROM user_subscriptions WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE user_subscriptions SET plan = ?, start_date = ?, end_date = ?, mentor_messages_used = 0, mentor_messages_reset_at = CURDATE() WHERE user_id = ?");
            $stmt->bind_param("sssi", $plan, $startDate, $endDate, $user['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user['id'], $plan, $startDate, $endDate);
        }

        if ($stmt->execute()) {
            setFlash('success', "Successfully upgraded to " . ucfirst($plan) . "! Your benefits are now active.");
            header("Location: subscription.php");
            exit;
        } else {
            $errors[] = "Failed to process upgrade. Please try again.";
        }
    }
}

$sub = getUserSubscription($user['id']);
getHeader('Subscription Plans');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-crown text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Upgrade Your Plan</h1>
        <p class="text-gray-500 max-w-xl mx-auto">Get more mentor messages, consultation discounts, and priority listing with our premium plans.</p>
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

    <div class="grid md:grid-cols-3 gap-8 mb-8">
        <!-- Free Plan -->
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center border-2 border-gray-200">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user text-2xl text-gray-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Free</h2>
            <p class="text-4xl font-bold text-gray-800 mb-2">Rs. 0</p>
            <p class="text-gray-500 mb-6">Forever</p>
            <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Post problems</li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Browse solutions</li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Join teams</li>
                <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-times text-gray-300"></i> <del>3 mentor msgs/week</del></li>
                <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-times text-gray-300"></i> <del>Unlimited chat</del></li>
            </ul>
            <div class="px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-400 font-bold">
                Current Plan
            </div>
        </div>

        <!-- Plus Plan -->
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center border-2 border-blue-500 relative">
            <span class="absolute -top-4 left-1/2 -translate-x-1/2 bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-bold">POPULAR</span>
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-star text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Plus</h2>
            <p class="text-4xl font-bold text-blue-600 mb-2">Rs. 499</p>
            <p class="text-gray-500 mb-6">per 30 days</p>
            <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Everything in Free</li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> <strong>Unlimited mentor messages</strong></li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> <strong>10% consultation discount</strong></li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Priority email support</li>
                <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-times text-gray-300"></i> <del>Priority listing</del></li>
            </ul>
            <?php if ($sub['plan'] === 'plus'): ?>
                <div class="px-6 py-3 bg-blue-100 border-2 border-blue-300 rounded-lg text-blue-600 font-bold">
                    <i class="fas fa-check-circle mr-2"></i>Current Plan
                </div>
            <?php elseif ($sub['plan'] === 'premium'): ?>
                <div class="px-6 py-3 bg-green-100 border-2 border-green-300 rounded-lg text-green-600 font-bold">
                    Upgraded
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan" value="plus">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-lg">
                        Upgrade to Plus
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Premium Plan -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl shadow-xl p-8 text-center border-2 border-amber-400">
            <div class="w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-crown text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Premium</h2>
            <p class="text-4xl font-bold text-amber-600 mb-2">Rs. 999</p>
            <p class="text-gray-500 mb-6">per 30 days</p>
            <ul class="text-left space-y-3 mb-8">
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Everything in Plus</li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> <strong>20% consultation discount</strong></li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> <strong>Priority listing</strong></li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 1-on-1 mentor matching</li>
                <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Featured in newsletter</li>
            </ul>
            <?php if ($sub['plan'] === 'premium'): ?>
                <div class="px-6 py-3 bg-amber-100 border-2 border-amber-300 rounded-lg text-amber-600 font-bold">
                    <i class="fas fa-crown mr-2"></i>Current Plan
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan" value="premium">
                    <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-lg">
                        <i class="fas fa-crown mr-2"></i>Go Premium
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Status -->
    <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
        <p class="text-gray-500">
            Current plan: <strong class="<?= $sub['plan'] === 'free' ? 'text-gray-600' : ($sub['plan'] === 'plus' ? 'text-blue-600' : 'text-amber-600') ?>"><?= ucfirst(e($sub['plan'])) ?></strong>
            <?php if ($sub['plan'] !== 'free'): ?>
                — Expires: <?= date('M j, Y', strtotime($sub['end_date'])) ?>
            <?php endif; ?>
            <?php if ($sub['plan'] === 'free'): ?>
                — <?= max(0, 3 - $sub['mentor_messages_used']) ?> mentor messages remaining this week
            <?php endif; ?>
        </p>
    </div>
</div>

<?php getFooter(); ?>