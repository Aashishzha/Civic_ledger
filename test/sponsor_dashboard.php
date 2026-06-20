<?php
/**
 * CivicLedger - Sponsor Dashboard
 * Fund civic innovations with full transparency
 */
require_once 'config.php';
requireRole('sponsor');

$user = getCurrentUser();
global $conn;
$trustScore = getTrustScore($user['id']);
$isVerified = isSponsorVerified($user['id']);

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM challenges WHERE sponsor_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$myChallenges = $r ? ($r->fetch_assoc()['cnt']) : 0;

$stmt = $conn->prepare("SELECT SUM(amount) as total FROM sponsorships WHERE sponsor_id = ? AND status = 'disbursed'");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$totalDisbursed = $r ? ($r->fetch_assoc()['total'] ?? 0) : 0;

// Get progress
$progress = getSponsorProgress($user['id']);

// Get my challenges
$stmt = $conn->prepare("SELECT * FROM challenges WHERE sponsor_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$challenges = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get solutions to my challenges
$stmt = $conn->prepare("SELECT s.*, c.title as challenge_title, u.name as solver_name FROM solutions s JOIN challenges c ON s.challenge_id = c.id JOIN users u ON s.user_id = u.id WHERE c.sponsor_id = ? ORDER BY s.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$solutions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle approve action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_solution') {
    $solutionId = (int)$_POST['solution_id'];
    $stmt = $conn->prepare("SELECT * FROM solutions WHERE id = ?");
    $stmt->bind_param("i", $solutionId);
    $stmt->execute();
    $r = $stmt->get_result();
    $solution = $r ? $r->fetch_assoc() : null;
    
    if ($solution && $solution['status'] === 'pending') {
        $gross = $solution['budget_estimate'] > 0 ? $solution['budget_estimate'] : 10000;
        $commissionCalc = calculateCommission($gross);
        
        $stmt = $conn->prepare("UPDATE solutions SET status = 'rewarded', reward_gross = ?, reward_commission = ?, reward_net = ? WHERE id = ?");
        $stmt->bind_param("dddi", $commissionCalc['gross'], $commissionCalc['commission'], $commissionCalc['net'], $solutionId);
        $stmt->execute();
        
        logSponsorActivity($user['id'], 'reward_solution', 'Rewarded solution ID: ' . $solutionId . ', Net: ' . $commissionCalc['net']);
        
        setFlash('success', 'Solution approved! Reward of ' . formatCurrency($commissionCalc['net']) . ' (net) processed.');
        header("Location: sponsor_dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Dashboard - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 500: '#059669', 600: '#047857' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-hand-holding-usd text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="sponsor_dashboard.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">Dashboard</a>
                    <a href="create_challenge.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Create Challenge</a>
                    <a href="create_sponsorship.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Sponsor</a>
                    <a href="sponsor_progress.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Analytics</a>
                    <a href="public_ledger.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-book mr-1"></i> Ledger</a>
                    <a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                    <?php if ($isVerified): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                        <i class="fas fa-certificate mr-1"></i>Verified
                    </span>
                    <?php endif; ?>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="sponsor_dashboard.php" class="block py-2 px-4 bg-green-50 text-green-700 rounded-lg">Dashboard</a>
                <a href="create_challenge.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Create Challenge</a>
                <a href="sponsor_progress.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Analytics</a>
                <a href="public_ledger.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Ledger</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-700 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-building text-3xl"></i>
                            <h1 class="text-3xl font-bold">Welcome, <?= e($user['name']) ?>!</h1>
                        </div>
                        <p class="text-green-100 mb-4">Fund civic innovations with full transparency and escrow protection.</p>
                        
                        <div class="flex flex-wrap items-center gap-3">
                            <?php if ($isVerified): ?>
                            <div class="flex items-center gap-2 px-3 py-1 bg-green-400/30 rounded-full">
                                <i class="fas fa-certificate text-amber-300"></i>
                                <span>Verified Startup</span>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center gap-2 px-3 py-1 bg-amber-400/30 rounded-full">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Not Verified - Limited to Rs. 25k challenges</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex gap-3">
                        <a href="create_challenge.php" class="inline-block bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-full transition-all shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Create Challenge
                        </a>
                        <a href="create_sponsorship.php" class="inline-block bg-white text-green-600 font-bold py-3 px-6 rounded-full transition-all hover:bg-green-50 shadow-lg">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Sponsor
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transparency Notice -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white rounded-xl p-4 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-2xl"></i>
                <div>
                    <p class="font-bold">100% Transparent - 0% Commission</p>
                    <p class="text-green-100 text-sm">Every rupee is tracked. Funds held in escrow until work is verified.</p>
                </div>
            </div>
            <a href="public_ledger.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium">
                View Ledger <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Impact Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">My Challenges</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $myChallenges ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trophy text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solutions Funded</p>
                        <p class="text-3xl font-bold text-green-600"><?= $progress['solutions_funded'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-lightbulb text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Rewards</p>
                        <p class="text-2xl font-bold text-amber-600"><?= formatCurrency($progress['total_reward_given']) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-coins text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Problems Solved</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $progress['problems_solved'] ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-double text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- My Challenges -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold"><i class="fas fa-trophy mr-2 text-amber-500"></i>My Challenges</h2>
                    <a href="create_challenge.php" class="text-green-600 hover:underline text-sm">+ Create New</a>
                </div>

                <?php if (empty($challenges)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-trophy text-5xl mb-4 text-gray-300"></i>
                    <p>No challenges created yet.</p>
                    <a href="create_challenge.php" class="inline-block mt-4 bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        Create Your First Challenge
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($challenges as $challenge): ?>
                    <div class="border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold text-gray-800"><?= e($challenge['title']) ?></h3>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $challenge['status'] === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst(e($challenge['status'])) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-xl font-bold text-green-600"><?= formatCurrency($challenge['reward_amount']) ?></span>
                            <span class="px-2 py-1 rounded text-xs <?= $challenge['escrow_status'] === 'deposited' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>">
                                <i class="fas fa-lock mr-1"></i>Escrow: <?= formatCurrency($challenge['escrow_deposit']) ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Deadline: <?= date('M j, Y', strtotime($challenge['deadline'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Incoming Solutions -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6"><i class="fas fa-paper-plane mr-2 text-green-600"></i>Solutions Submitted</h2>

                <?php if (empty($solutions)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                    <p>No solutions submitted yet.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($solutions as $sol): ?>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold"><?= e($sol['title']) ?></span>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getStatusClass($sol['status']) ?>">
                                <?= ucfirst(e($sol['status'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600">For: <?= e($sol['challenge_title']) ?></p>
                        <p class="text-xs text-gray-400 mt-1">By: <?= e($sol['solver_name']) ?> • <?= timeAgo($sol['created_at']) ?></p>
                        <?php if ($sol['status'] === 'pending'): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="approve_solution">
                            <input type="hidden" name="solution_id" value="<?= $sol['id'] ?>">
                            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg text-sm font-semibold transition-all">
                                <i class="fas fa-check mr-2"></i>Approve & Release Funds
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- View Analytics Button -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold"><i class="fas fa-chart-line mr-2 text-purple-600"></i>Track Your Impact</h2>
                    <p class="text-gray-500 mt-1">View detailed analytics of your sponsored solutions and their civic impact.</p>
                </div>
                <a href="sponsor_progress.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-all">
                    <i class="fas fa-chart-bar mr-2"></i>View Analytics
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
            <a href="create_challenge.php" class="bg-amber-50 p-4 rounded-xl text-center hover:bg-amber-100 transition-all card-hover">
                <i class="fas fa-plus-circle text-2xl text-amber-600 mb-2"></i>
                <p class="text-sm font-semibold text-amber-800">Create Challenge</p>
            </a>
            <a href="create_sponsorship.php" class="bg-green-50 p-4 rounded-xl text-center hover:bg-green-100 transition-all card-hover">
                <i class="fas fa-hand-holding-usd text-2xl text-green-600 mb-2"></i>
                <p class="text-sm font-semibold text-green-800">Direct Sponsor</p>
            </a>
            <a href="sponsor_progress.php" class="bg-purple-50 p-4 rounded-xl text-center hover:bg-purple-100 transition-all card-hover">
                <i class="fas fa-chart-line text-2xl text-purple-600 mb-2"></i>
                <p class="text-sm font-semibold text-purple-800">Analytics</p>
            </a>
            <a href="public_ledger.php" class="bg-blue-50 p-4 rounded-xl text-center hover:bg-blue-100 transition-all card-hover">
                <i class="fas fa-book text-2xl text-blue-600 mb-2"></i>
                <p class="text-sm font-semibold text-blue-800">Ledger</p>
            </a>
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

<?php
?>

</body>
</html>