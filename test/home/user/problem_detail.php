<?php
/**
 * CivicLedger - Problem Detail Page
 * Civic & Social Impact Platform
 */
require_once 'config.php';

$problemId = intval($_GET['id'] ?? 0);

if ($problemId <= 0) {
    setFlash('error', 'Invalid problem ID');
    header("Location: index.php");
    exit;
}

global $conn;

// Get problem details with error handling
$problem = null;
$stmt = $conn->prepare("SELECT p.*, u.name as user_name, u.profile_photo, u.trust_score as reporter_trust 
                        FROM problems p 
                        LEFT JOIN users u ON p.user_id = u.id 
                        WHERE p.id = ?");
if ($stmt) {
    $stmt->bind_param("i", $problemId);
    $stmt->execute();
    $r = $stmt->get_result();
    $problem = $r ? $r->fetch_assoc() : null;
}

if (!$problem) {
    setFlash('error', 'Problem not found');
    header("Location: index.php");
    exit;
}

// Get solutions with error handling
$solutions = [];
$stmt = $conn->prepare("SELECT s.*, u.name as solver_name, u.trust_score as solver_trust 
                        FROM solutions s 
                        LEFT JOIN users u ON s.user_id = u.id 
                        WHERE s.problem_id = ? 
                        ORDER BY s.created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $problemId);
    $stmt->execute();
    $r = $stmt->get_result();
    $solutions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

// Get current user
$user = getCurrentUser();
$userVote = null;
if ($user) {
    $stmt = $conn->prepare("SELECT type FROM solution_likes WHERE user_id = ? AND solution_id = ?");
    if ($stmt) {
        foreach ($solutions as &$sol) {
            $stmt->bind_param("ii", $user['id'], $sol['id']);
            $stmt->execute();
            $r = $stmt->get_result();
            $vote = $r ? $r->fetch_assoc() : null;
            $sol['user_vote'] = $vote ? $vote['type'] : null;
        }
        unset($sol);
    }
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($problem['title']) ?> - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 500: '#059669', 600: '#047857', 700: '#065f46' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-green-50 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">Home</a>
                    <a href="public_heatmap.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-map-marked-alt mr-1"></i>Map
                    </a>
                    <a href="public_ledger.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-book mr-1"></i>Ledger
                    </a>
                    <?php if ($currentUser): ?>
                        <a href="<?= $currentUser['role'] ?>_dashboard.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                            Dashboard
                        </a>
                        <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Login</a>
                        <a href="register.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Sign Up</a>
                    <?php endif; ?>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <a href="index.php" class="block py-2 px-4 bg-green-50 text-green-700 rounded-lg">Home</a>
                <a href="public_heatmap.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Map</a>
                <a href="public_ledger.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Ledger</a>
                <?php if ($currentUser): ?>
                    <a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        
        <!-- Problem Header -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800"><?= e($problem['title']) ?></h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getCategoryClass($problem['category'] ?? 'Other') ?>">
                                <?= e($problem['category'] ?? 'Other') ?>
                            </span>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getUrgencyClass($problem['urgency'] ?? 'Medium') ?>">
                                <?= e($problem['urgency'] ?? 'Medium') ?>
                            </span>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?= getStatusClass($problem['status'] ?? 'open') ?>">
                                <?= ucfirst(e($problem['status'] ?? 'open')) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Audio button for TTS -->
                <button onclick="speakText('<?= e(addslashes($problem['title'] . '. ' . $problem['description'])) ?>')" 
                        class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-all">
                    <i class="fas fa-volume-up mr-2"></i>Listen
                </button>
            </div>
            
            <!-- Reporter Info -->
            <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-green-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800"><?= e($problem['user_name'] ?? 'Anonymous') ?></p>
                    <p class="text-sm text-gray-500">Posted <?= timeAgo($problem['created_at'] ?? '') ?></p>
                </div>
                <div class="text-right">
                    <div class="flex items-center gap-1 text-purple-600">
                        <i class="fas fa-shield-alt"></i>
                        <span class="font-semibold"><?= $problem['reporter_trust'] ?? 100 ?></span>
                    </div>
                    <p class="text-xs text-gray-500">Trust Score</p>
                </div>
            </div>
            
            <!-- Location -->
            <?php if (!empty($problem['location'])): ?>
            <div class="mb-6 p-4 bg-blue-50 rounded-xl flex items-center gap-3">
                <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                <span class="text-blue-800 font-medium"><?= e($problem['location']) ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Description -->
            <div class="prose max-w-none mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Description</h3>
                <p class="text-gray-600 leading-relaxed"><?= nl2br(e($problem['description'] ?? '')) ?></p>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?= $problem['upvotes'] ?? 0 ?></div>
                    <div class="text-sm text-gray-500">Upvotes</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= count($solutions) ?></div>
                    <div class="text-sm text-gray-500">Solutions</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600"><?= $problem['reporter_trust'] ?? 100 ?></div>
                    <div class="text-sm text-gray-500">Reporter Trust</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600"><?= timeAgo($problem['created_at'] ?? '') ?></div>
                    <div class="text-sm text-gray-500">Posted</div>
                </div>
            </div>
        </div>

        <!-- Submit Solution CTA -->
        <?php if ($currentUser): ?>
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl p-6 mb-8 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Have a Solution?</h3>
                    <p class="text-green-100 mt-1">Help solve this civic problem and earn trust score points!</p>
                </div>
                <a href="submit_solution.php?problem_id=<?= $problemId ?>" class="bg-white text-green-600 font-bold py-3 px-6 rounded-full hover:bg-green-50 transition-all">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Solution
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-blue-50 rounded-2xl p-6 mb-8 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-blue-800">Login to Submit Solutions</h3>
                <p class="text-blue-600 mt-1">Join CivicLedger to help solve civic problems and earn rewards!</p>
            </div>
            <a href="login.php" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-full hover:bg-blue-700 transition-all">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </a>
        </div>
        <?php endif; ?>

        <!-- Solutions -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-lightbulb mr-2 text-amber-500"></i>Solutions (<?= count($solutions) ?>)
            </h2>

            <?php if (empty($solutions)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No solutions yet. Be the first to propose one!</p>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($solutions as $solution): ?>
                <div class="border border-gray-200 rounded-xl p-6 hover:border-green-400 transition-all bg-gradient-to-r from-gray-50 to-green-50">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800"><?= e($solution['title'] ?? 'Solution') ?></h4>
                                <p class="text-sm text-gray-500">
                                    By <?= e($solution['solver_name'] ?? 'Anonymous') ?> • <?= timeAgo($solution['created_at'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getSolutionStatusClass($solution['status'] ?? 'pending') ?>">
                                <?= ucfirst(e($solution['status'] ?? 'pending')) ?>
                            </span>
                            <button onclick="speakText('<?= e(addslashes($solution['description'] ?? '')) ?>')" 
                                    class="px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-all text-sm">
                                <i class="fas fa-volume-up"></i>
                            </button>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-4"><?= nl2br(e($solution['description'] ?? '')) ?></p>
                    
                    <!-- Like/Dislike buttons -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                        <button onclick="likeSolution(<?= $solution['id'] ?>, 'like')" 
                                class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all <?= ($solution['user_vote'] ?? '') === 'like' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-green-100' ?>">
                            <i class="fas fa-thumbs-up"></i>
                            <span id="like-count-<?= $solution['id'] ?>"><?= $solution['likes'] ?? 0 ?></span>
                        </button>
                        <button onclick="likeSolution(<?= $solution['id'] ?>, 'dislike')" 
                                class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all <?= ($solution['user_vote'] ?? '') === 'dislike' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-red-100' ?>">
                            <i class="fas fa-thumbs-down"></i>
                            <span id="dislike-count-<?= $solution['id'] ?>"><?= $solution['dislikes'] ?? 0 ?></span>
                        </button>
                        
                        <?php if (!empty($solution['reward_gross'])): ?>
                        <div class="ml-auto flex items-center gap-2 px-4 py-2 bg-green-100 rounded-lg">
                            <i class="fas fa-coins text-green-600"></i>
                            <span class="font-semibold text-green-800"><?= formatCurrency($solution['reward_gross']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Transparency Notice -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p><i class="fas fa-shield-alt text-green-600 mr-1"></i>100% Transparent - All transactions tracked on the public ledger</p>
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

        function speakText(text) {
            if ('speechSynthesis' in window) {
                // Cancel any ongoing speech
                speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                utterance.rate = 0.9;
                speechSynthesis.speak(utterance);
            } else {
                alert('Text-to-Speech not supported in this browser');
            }
        }

        function likeSolution(solutionId, type) {
            fetch('api_like_solution.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ solution_id: solutionId, type: type })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('like-count-' + solutionId).textContent = data.likes;
                    document.getElementById('dislike-count-' + solutionId).textContent = data.dislikes;
                }
            });
        }
    </script>

</body>
</html>