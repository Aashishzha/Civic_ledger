<?php
require_once 'config.php';

$page_title = "Public Transparency Ledger";

// Fetch all financial transactions
$transactions = $conn->query("
    SELECT 
        c.id as challenge_id,
        c.title as challenge_title,
        c.reward_amount,
        c.escrow_deposit,
        c.escrow_status,
        c.created_at,
        u_sponsor.name as sponsor_name,
        u_sponsor.email as sponsor_email,
        t.name as team_name,
        s.id as solution_id,
        s.reward_gross,
        s.reward_commission,
        s.reward_net,
        s.status as solution_status
    FROM challenges c
    LEFT JOIN users u_sponsor ON c.sponsor_id = u_sponsor.id
    LEFT JOIN solutions s ON s.challenge_id = c.id
    LEFT JOIN teams t ON s.team_id = t.id
    ORDER BY c.created_at DESC
");

$total_rewards = 0;
$total_escrow = 0;
$total_released = 0;

if ($transactions && $transactions->num_rows > 0) {
    $transactions_data = $transactions->fetch_all(MYSQLI_ASSOC);
    foreach ($transactions_data as $t) {
        if ($t['reward_amount']) $total_rewards += $t['reward_amount'];
        if ($t['escrow_deposit']) $total_escrow += $t['escrow_deposit'];
        if ($t['escrow_status'] === 'released') $total_released += $t['escrow_deposit'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .escrow-held { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%); }
        .escrow-released { background: linear-gradient(135deg, #51cf66 0%, #40c057 100%); }
        .escrow-pending { background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-green-700 to-green-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-3xl"></i>
                    <div>
                        <h1 class="text-xl font-bold">CivicLedger</h1>
                        <p class="text-xs text-green-100">Civic Transparency Platform</p>
                    </div>
                </div>
                <nav class="hidden md:flex space-x-4">
                    <a href="index.php" class="hover:text-green-200"><i class="fas fa-home mr-1"></i> Home</a>
                    <a href="public_ledger.php" class="text-white font-semibold"><i class="fas fa-book mr-1"></i> Ledger</a>
                    <a href="public_heatmap.php" class="hover:text-green-200"><i class="fas fa-map mr-1"></i> Problem Map</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="md:hidden bg-green-800 text-white p-4">
        <button id="mobileMenuBtn" class="w-full text-left">
            <i class="fas fa-bars mr-2"></i> Menu
        </button>
        <div id="mobileMenu" class="hidden mt-4 space-y-2">
            <a href="index.php" class="block py-2"><i class="fas fa-home mr-2"></i> Home</a>
            <a href="public_ledger.php" class="block py-2 font-semibold"><i class="fas fa-book mr-2"></i> Ledger</a>
            <a href="public_heatmap.php" class="block py-2"><i class="fas fa-map mr-2"></i> Problem Map</a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        
        <!-- Page Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                <i class="fas fa-hand-holding-dollar text-4xl text-green-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Public Transparency Ledger</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">Every Nepali rupee is tracked. Every transaction is public. No corruption possible.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Rewards Posted</p>
                        <p class="text-2xl font-bold text-gray-800">Rs. <?php echo number_format($total_rewards); ?></p>
                    </div>
                    <i class="fas fa-coins text-3xl text-blue-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Escrow Held</p>
                        <p class="text-2xl font-bold text-gray-800">Rs. <?php echo number_format($total_escrow - $total_released); ?></p>
                    </div>
                    <i class="fas fa-lock text-3xl text-orange-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Escrow Released</p>
                        <p class="text-2xl font-bold text-gray-800">Rs. <?php echo number_format($total_released); ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $transactions ? $transactions->num_rows : 0; ?></p>
                    </div>
                    <i class="fas fa-receipt text-3xl text-purple-400"></i>
                </div>
            </div>
        </div>

        <!-- Transparency Info -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-shield-alt text-3xl text-green-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-green-800 mb-2">Anti-Corruption Guarantee</h3>
                    <ul class="text-green-700 text-sm space-y-1">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>All sponsor funds are held in escrow until milestones are verified</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Platform commission is fixed at 0% for civic projects</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Every transaction is publicly visible and timestamped</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Disputes are resolved by independent mentors, not platform operators</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transaction Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-gray-700 to-gray-600 text-white px-6 py-4">
                <h2 class="font-bold text-lg"><i class="fas fa-table mr-2"></i>Transaction History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Challenge</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sponsor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Team</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Reward</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Commission</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Net</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Escrow Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($transactions && $transactions->num_rows > 0): ?>
                            <?php foreach ($transactions_data as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800"><?php echo e($row['challenge_title'] ?? 'Pending'); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800"><?php echo e($row['sponsor_name'] ?? 'N/A'); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo e(substr($row['sponsor_email'] ?? '', 0, 20)); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($row['team_name']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-users mr-1"></i> <?php echo e($row['team_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">No team assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                        Rs. <?php echo number_format($row['reward_amount'] ?? 0); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        Rs. 0 <span class="text-xs text-green-600">(Waived)</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-green-600">
                                        Rs. <?php echo number_format($row['reward_net'] ?? $row['reward_amount'] ?? 0); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                        $status = $row['escrow_status'] ?? 'none';
                                        $statusClass = '';
                                        $statusText = '';
                                        switch($status) {
                                            case 'deposited':
                                                $statusClass = 'escrow-held';
                                                $statusText = 'HELD';
                                                break;
                                            case 'released':
                                                $statusClass = 'escrow-released';
                                                $statusText = 'RELEASED';
                                                break;
                                            case 'pending':
                                                $statusClass = 'escrow-pending';
                                                $statusText = 'PENDING';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-200';
                                                $statusText = 'N/A';
                                        }
                                        ?>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold text-white <?php echo $statusClass; ?>">
                                            <i class="fas fa-shield-alt mr-1"></i><?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No transactions yet. Be the first to create a civic challenge!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- How It Works -->
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-piggy-bank text-2xl text-blue-600"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">1. Funds Deposited</h3>
                <p class="text-gray-600 text-sm">Sponsors deposit 10-20% of reward into escrow before work begins.</p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tasks text-2xl text-orange-600"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">2. Milestones Verified</h3>
                <p class="text-gray-600 text-sm">Independent mentors verify completion of each milestone.</p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hand-holding-usd text-2xl text-green-600"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">3. Funds Released</h3>
                <p class="text-gray-600 text-sm">Money released only when work is verified. No corruption possible.</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400"><i class="fas fa-heart text-red-500 mr-1"></i> Built for Nepal's Civic Innovation</p>
            <p class="text-gray-500 text-sm mt-2">CivicLedger - The Problem Solver</p>
        </div>
    </footer>

    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    </script>
</body>
</html>