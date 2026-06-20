<?php
/**
 * CivicLedger - Sponsor Analytics Dashboard
 */
require_once 'config.php';
requireRole('sponsor');

$user = getCurrentUser();
global $conn;

// Get sponsor progress data safely
$progress = getSponsorProgress($user['id']);

// Get monthly reward data for chart with error handling
$monthlyData = [];
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(reward_gross) as gross,
           SUM(reward_net) as net,
           COUNT(*) as count
    FROM solutions s
    JOIN challenges c ON s.challenge_id = c.id
    WHERE c.sponsor_id = ? AND s.status = 'rewarded'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC LIMIT 12
");
if ($stmt !== false) {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $rm = $stmt->get_result();
    if ($rm && $rm->num_rows > 0) {
        $rawData = $rm->fetch_all(MYSQLI_ASSOC);
        $monthlyData = array_reverse($rawData);
    }
}

// Get problem categories funded with error handling
$categoryData = [];
$stmt = $conn->prepare("
    SELECT p.category, COUNT(*) as count
    FROM challenges c
    JOIN solutions s ON s.challenge_id = c.id
    JOIN problems p ON s.problem_id = p.id
    WHERE c.sponsor_id = ? AND s.status = 'rewarded'
    GROUP BY p.category
");
if ($stmt !== false) {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        $categoryData = $r->fetch_all(MYSQLI_ASSOC);
    }
}

// Get recent activity log with error handling
$activityLog = [];
$stmt = $conn->prepare("SELECT * FROM sponsor_activity_log WHERE sponsor_id = ? ORDER BY created_at DESC LIMIT 20");
if ($stmt !== false) {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        $activityLog = $r->fetch_all(MYSQLI_ASSOC);
    }
}

// Get SDG distribution with error handling
$sdgData = [];
$stmt = $conn->prepare("
    SELECT sdg_focus, COUNT(*) as count
    FROM challenges WHERE sponsor_id = ? AND sdg_focus IS NOT NULL
    GROUP BY sdg_focus
");
if ($stmt !== false) {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        $sdgData = $r->fetch_all(MYSQLI_ASSOC);
    }
}

// All SDG labels
$sdgLabels = [
    1 => 'No Poverty', 2 => 'Zero Hunger', 3 => 'Good Health',
    4 => 'Quality Education', 5 => 'Gender Equality', 6 => 'Clean Water',
    7 => 'Clean Energy', 8 => 'Decent Work', 9 => 'Industry & Innovation',
    10 => 'Reduced Inequalities', 11 => 'Sustainable Cities',
    12 => 'Responsible Consumption', 13 => 'Climate Action',
    14 => 'Life Below Water', 15 => 'Life on Land',
    16 => 'Peace & Justice', 17 => 'Partnerships'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Analytics - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>tailwind.config = { theme: { extend: { colors: { civic: { 600: '#059669' } } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .chart-container { position: relative; height: 300px; }
    </style>
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
                    <a href="sponsor_progress.php" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg font-medium">Analytics</a>
                    <a href="sponsor_message_admin.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Message Admin</a>
                    <a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl"><i class="fas fa-bars"></i></button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="sponsor_dashboard.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Dashboard</a>
                <a href="create_challenge.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Create Challenge</a>
                <a href="create_sponsorship.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Sponsor</a>
                <a href="sponsor_progress.php" class="block py-2 px-4 bg-purple-50 text-purple-700 rounded-lg">Analytics</a>
                <a href="sponsor_message_admin.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Message Admin</a>
                <a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-chart-line text-purple-600 mr-3"></i>Sponsor Analytics
                </h1>
                <p class="text-gray-500 mt-1">Track your impact and funding distribution</p>
            </div>
            <a href="sponsor_dashboard.php" class="mt-4 md:mt-0 text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Top Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Reward Given</p>
                        <p class="text-2xl font-bold text-purple-600"><?= formatCurrency($progress['total_reward_given']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-coins text-xl text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solutions Funded</p>
                        <p class="text-2xl font-bold text-green-600"><?= $progress['solutions_funded'] ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-lightbulb text-xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Problems Solved</p>
                        <p class="text-2xl font-bold text-blue-600"><?= $progress['problems_solved'] ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-double text-xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avg. Commission</p>
                        <p class="text-2xl font-bold text-amber-600"><?= formatCurrency($progress['total_commission_paid']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-percentage text-xl text-amber-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Rewards Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold mb-4"><i class="fas fa-chart-bar text-blue-600 mr-2"></i>Monthly Rewards Disbursed</h3>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Problem Categories Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold mb-4"><i class="fas fa-chart-pie text-green-600 mr-2"></i>Problems by Category</h3>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SDG Distribution & Impact -->
        <div class="grid lg:grid-cols-2 gap-8 mb-8">
            <!-- SDG Focus Distribution -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold mb-4"><i class="fas fa-globe-americas text-indigo-600 mr-2"></i>SDG Focus Areas</h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php
                    $sdgDist = [];
                    $stmt = $conn->prepare("SELECT sdg_focus, COUNT(*) as count, SUM(reward_amount) as total FROM challenges WHERE sponsor_id = ? AND sdg_focus IS NOT NULL GROUP BY sdg_focus ORDER BY count DESC");
                    if ($stmt !== false) {
                        $stmt->bind_param("i", $user['id']);
                        $stmt->execute();
                        $r = $stmt->get_result();
                        if ($r && $r->num_rows > 0) {
                            $sdgDist = $r->fetch_all(MYSQLI_ASSOC);
                        }
                    }
                    $totalSdgs = array_sum(array_column($sdgDist, 'count'));
                    foreach ($sdgDist as $sdg):
                        $pct = $totalSdgs > 0 ? round($sdg['count'] / $totalSdgs * 100) : 0;
                        $label = $sdgLabels[$sdg['sdg_focus']] ?? 'SDG ' . $sdg['sdg_focus'];
                    ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium"><?= e($label) ?></span>
                            <span class="text-gray-500"><?= $sdg['count'] ?> (<?= formatCurrency($sdg['total'] ?? 0) ?>)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($sdgDist)): ?>
                    <p class="text-gray-400 text-center py-8">No SDG data yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold mb-4"><i class="fas fa-history text-amber-600 mr-2"></i>Recent Activity</h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (empty($activityLog)): ?>
                    <p class="text-gray-400 text-center py-8">No activity recorded yet</p>
                    <?php else: ?>
                    <?php foreach ($activityLog as $log): ?>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-<?= getActivityColor($log['action']) ?>-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas <?= getActivityIcon($log['action']) ?> text-<?= getActivityColor($log['action']) ?>-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800"><?= e($log['details'] ?? $log['action']) ?></p>
                            <p class="text-xs text-gray-400"><?= timeAgo($log['created_at']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fund Flow Summary -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-bold mb-6"><i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Fund Flow Summary</h3>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                    <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-coins text-2xl text-white"></i>
                    </div>
                    <p class="text-3xl font-bold text-green-700"><?= formatCurrency($progress['total_reward_given']) ?></p>
                    <p class="text-green-600 mt-2 font-medium">Total Rewards</p>
                </div>
                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                    <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hand-holding-usd text-2xl text-white"></i>
                    </div>
                    <p class="text-3xl font-bold text-purple-700"><?= formatCurrency($progress['total_commission_paid']) ?></p>
                    <p class="text-purple-600 mt-2 font-medium">Platform Commission</p>
                </div>
                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                    <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-network-wired text-2xl text-white"></i>
                    </div>
                    <p class="text-3xl font-bold text-blue-700"><?= formatCurrency($progress['total_reward_given'] + $progress['total_commission_paid']) ?></p>
                    <p class="text-blue-600 mt-2 font-medium">Total Disbursed</p>
                </div>
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

        // Monthly Rewards Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyLabels = <?= json_encode(array_column($monthlyData, 'month')) ?>;
        const monthlyGross = <?= json_encode(array_column($monthlyData, 'gross')) ?>;
        const monthlyNet = <?= json_encode(array_column($monthlyData, 'net')) ?>;

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyLabels.map(m => m.substring(5)),
                datasets: [
                    { label: 'Gross Reward', data: monthlyGross, backgroundColor: 'rgba(168,85,247,0.7)', borderRadius: 6 },
                    { label: 'Net to Solver', data: monthlyNet, backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v } } }
            }
        });

        // Category Pie Chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const catLabels = <?= json_encode(array_column($categoryData, 'category')) ?>;
        const catCounts = <?= json_encode(array_column($categoryData, 'count')) ?>;
        const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16'];

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catCounts,
                    backgroundColor: colors.slice(0, catLabels.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    </script>

<?php
function getActivityColor($action) {
    $colors = ['reward_solution' => 'green', 'create_challenge' => 'blue', 'sponsor' => 'amber',
                'approve_solution' => 'purple', 'message_admin' => 'indigo', 'default' => 'gray'];
    return $colors[$action] ?? 'gray';
}
function getActivityIcon($action) {
    $icons = ['reward_solution' => 'fa-coins', 'create_challenge' => 'fa-trophy', 'sponsor' => 'fa-hand-holding-usd',
              'approve_solution' => 'fa-check', 'message_admin' => 'fa-envelope', 'default' => 'fa-circle'];
    return $icons[$action] ?? 'fa-circle';
}
?>

</body>
</html>