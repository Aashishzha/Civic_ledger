<?php
/**
 * CivicLedger - Citizen Dashboard
 * Civic & Social Impact Platform
 */
require_once 'config.php';
requireRole('citizen');

$user = getCurrentUser();
global $conn;

$trustScore = getTrustScore($user['id']);

// Get citizen's problems
$stmt = $conn->prepare("SELECT * FROM problems WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$problems = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$totalProblems = count($problems);
$solvedProblems = count(array_filter($problems, fn($p) => $p['status'] === 'solved'));

// Get public problems (for community upvoting)
$stmt = $conn->prepare("SELECT p.*, u.name as reporter_name FROM problems p JOIN users u ON p.user_id = u.id WHERE p.status = 'open' ORDER BY p.upvotes DESC, p.created_at DESC LIMIT 10");
$stmt->execute();
$r = $stmt->get_result();
$communityProblems = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 500: '#059669', 600: '#047857', 700: '#065f46' },
                        trust: { civic: '#7c3aed', trusted: '#059669', active: '#3b82f6', risk: '#f59e0b', danger: '#ef4444' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .trust-badge { backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50">

    <!-- Navigation - Civic Theme -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="citizen_dashboard.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">Dashboard</a>
                    <a href="post_problem.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Report Problem</a>
                    <a href="public_heatmap.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-map-marked-alt mr-1"></i> Map
                    </a>
                    <a href="public_ledger.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-book mr-1"></i> Transparency
                    </a>
                    <a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                    <div class="flex items-center gap-2 px-3 py-2 bg-<?= getTrustBadgeColor($trustScore) ?>-100 rounded-lg">
                        <i class="fas fa-shield-alt text-<?= getTrustBadgeColor($trustScore) ?>-600"></i>
                        <span class="text-sm font-semibold">Trust: <?= $trustScore ?></span>
                    </div>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="citizen_dashboard.php" class="block py-2 px-4 bg-green-50 text-green-700 rounded-lg">Dashboard</a>
                <a href="post_problem.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Report Problem</a>
                <a href="public_heatmap.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-map mr-2"></i>Problem Map</a>
                <a href="public_ledger.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-book mr-2"></i>Transparency</a>
                <a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a>
                <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Welcome Header - Civic Theme -->
        <div class="bg-gradient-to-r from-green-700 to-emerald-600 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-hands-helping text-3xl"></i>
                            <h1 class="text-3xl font-bold">Welcome, <?= e($user['name']) ?>!</h1>
                        </div>
                        <p class="text-green-100 mb-4">Your civic voice matters. Help solve your community's problems.</p>
                        
                        <!-- Trust Score Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full">
                            <i class="fas fa-shield-alt"></i>
                            <span>Trust Score: <strong><?= $trustScore ?></strong></span>
                            <span class="px-2 py-0.5 bg-<?= getTrustBadgeColor($trustScore) ?>-500 text-white text-xs rounded-full"><?= getTrustLabel($trustScore) ?></span>
                        </div>
                    </div>
                    <a href="post_problem.php" class="mt-4 md:mt-0 bg-amber-500 hover:bg-amber-600 text-white font-bold py-4 px-8 rounded-full transition-all transform hover:scale-105 shadow-lg flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>Report a Problem
                    </a>
                </div>
            </div>
        </div>

        <!-- Anti-Corruption Notice -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white rounded-xl p-4 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-2xl"></i>
                <div>
                    <p class="font-bold">100% Transparent Platform</p>
                    <p class="text-green-100 text-sm">Every report is public. Every transaction is trackable. No corruption.</p>
                </div>
            </div>
            <a href="public_ledger.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium">
                View Ledger <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">My Reports</p>
                        <p class="text-3xl font-bold text-gray-800"><?= $totalProblems ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Solved</p>
                        <p class="text-3xl font-bold text-green-600"><?= $solvedProblems ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Community Issues</p>
                        <p class="text-3xl font-bold text-blue-600"><?= count($communityProblems) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg card-hover transition-all duration-300 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Trust Score</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $trustScore ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- My Problems -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold"><i class="fas fa-list-alt mr-2 text-green-600"></i>My Civic Reports</h2>
                    <a href="post_problem.php" class="text-green-600 hover:underline text-sm font-medium">+ New Report</a>
                </div>

                <?php if (empty($problems)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-file-alt text-5xl mb-4 text-gray-300"></i>
                    <p>You haven't reported any problems yet.</p>
                    <a href="post_problem.php" class="inline-block mt-4 bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700 transition-all">
                        Report Your First Problem
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4 max-h-80 overflow-y-auto">
                    <?php foreach (array_slice($problems, 0, 5) as $problem): ?>
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-green-400 transition-all">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getCategoryClass($problem['category']) ?>"><?= e($problem['category']) ?></span>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getUrgencyClass($problem['urgency']) ?>"><?= e($problem['urgency']) ?></span>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getStatusClass($problem['status']) ?>"><?= ucfirst(e($problem['status'])) ?></span>
                                </div>
                                <h3 class="font-semibold text-gray-800"><?= e($problem['title']) ?></h3>
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                    <span><i class="fas fa-thumbs-up mr-1"></i><?= $problem['upvotes'] ?? 0 ?> upvotes</span>
                                    <span><i class="fas fa-clock mr-1"></i><?= timeAgo($problem['created_at']) ?></span>
                                </div>
                            </div>
                            <a href="problem_detail.php?id=<?= $problem['id'] ?>" class="text-green-600 hover:text-green-800 ml-4">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($problems) > 5): ?>
                <p class="text-center mt-4 text-gray-500 text-sm">+ <?= count($problems) - 5 ?> more reports</p>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Community Problems - Upvoting -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold"><i class="fas fa-fire mr-2 text-orange-500"></i>Community Problems</h2>
                    <a href="public_heatmap.php" class="text-blue-600 hover:underline text-sm">View Map</a>
                </div>

                <?php if (empty($communityProblems)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-map-marked-alt text-5xl mb-4 text-gray-300"></i>
                    <p>No community problems yet.</p>
                </div>
                <?php else: ?>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php foreach (array_slice($communityProblems, 0, 6) as $problem): ?>
                    <div class="border border-gray-200 rounded-xl p-3 hover:border-orange-400 transition-all bg-orange-50/30">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold <?= getCategoryClass($problem['category']) ?>"><?= e($problem['category']) ?></span>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold <?= getUrgencyClass($problem['urgency']) ?>"><?= e($problem['urgency']) ?></span>
                                </div>
                                <h4 class="font-medium text-gray-800 text-sm"><?= e($problem['title']) ?></h4>
                                <p class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i><?= e($problem['location'] ?? 'N/A') ?></p>
                            </div>
                            <div class="text-center ml-3">
                                <button onclick="upvoteProblem(<?= $problem['id'] ?>)" class="w-10 h-10 rounded-full bg-green-100 hover:bg-green-200 flex items-center justify-center text-green-600 transition-all">
                                    <i class="fas fa-thumbs-up"></i>
                                </button>
                                <div class="text-xs font-bold text-gray-600 mt-1" id="upvote-count-<?= $problem['id'] ?>"><?= $problem['upvotes'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Civic Tools -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <h2 class="text-xl font-bold mb-6"><i class="fas fa-tools mr-2 text-gray-600"></i>Civic Tools</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="post_problem.php" class="bg-gradient-to-r from-red-500 to-orange-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Report Problem</p>
                </a>
                <a href="public_heatmap.php" class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-map-marked-alt text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Problem Map</p>
                </a>
                <a href="public_ledger.php" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-book text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Transparency Ledger</p>
                </a>
                <a href="profile.php" class="bg-gradient-to-r from-purple-500 to-violet-500 text-white p-4 rounded-xl text-center hover:opacity-90 transition-all card-hover">
                    <i class="fas fa-user-edit text-2xl mb-2"></i>
                    <p class="text-sm font-semibold">Edit Profile</p>
                </a>
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

        function upvoteProblem(problemId) {
            fetch('api_upvote_problem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ problem_id: problemId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('upvote-count-' + problemId).textContent = data.upvotes;
                }
            });
        }
    </script>

<?php
?>

</body>
</html>