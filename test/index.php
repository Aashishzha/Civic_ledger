<?php
/**
 * CivicLedger - Public Landing Page (Civic & Social Impact Theme)
 * "The Anti-Corruption Platform" - Show all problems and solutions publicly
 */
require_once 'config.php';

$successStories = getSuccessStories(5);
$stats = getStats();

// Get all PUBLIC problems (approved and awaiting)
$publicProblems = $conn->query("
    SELECT p.*, u.name as reporter_name, u.trust_score as reporter_trust
    FROM problems p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status IN ('open', 'in_progress')
    AND p.is_approved >= 0
    ORDER BY p.upvotes DESC, p.created_at DESC
");

$allProblems = $publicProblems ? $publicProblems->fetch_all(MYSQLI_ASSOC) : [];

// Get all SOLVED problems with solutions
$solvedProblems = $conn->query("
    SELECT p.*, u.name as reporter_name,
           s.id as solution_id, s.title as solution_title, s.description as solution_desc,
           s.budget_estimate as solution_budget,
           solver.name as solver_name, solver.trust_score as solver_trust,
           t.name as team_name
    FROM problems p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN solutions s ON s.problem_id = p.id AND s.status IN ('approved', 'rewarded')
    LEFT JOIN users solver ON s.user_id = solver.id
    LEFT JOIN teams t ON s.team_id = t.id
    WHERE p.status = 'solved'
    AND p.is_approved >= 0
    ORDER BY p.solved_at DESC
    LIMIT 10
");

$solvedList = $solvedProblems ? $solvedProblems->fetch_all(MYSQLI_ASSOC) : [];

// Get recent solutions (approved)
$recentSolutions = $conn->query("
    SELECT s.*, u.name as solver_name, u.trust_score as solver_trust,
           p.title as problem_title, p.id as problem_id,
           t.name as team_name
    FROM solutions s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN problems p ON s.problem_id = p.id
    LEFT JOIN teams t ON s.team_id = t.id
    WHERE s.is_approved >= 0 AND s.status IN ('approved', 'rewarded')
    ORDER BY s.created_at DESC
    LIMIT 8
");

$solutionsList = $recentSolutions ? $recentSolutions->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$openProblems = count(array_filter($allProblems, fn($p) => $p['status'] === 'open'));
$solvedCount = count($solvedList);
$activeSolutions = count($solutionsList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicLedger - Nepal's Anti-Corruption Civic Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' },
                        civic: { 600: '#059669' }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .hero-gradient {
            background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .carousel-container {
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .carousel-container::-webkit-scrollbar { display: none; }
        .carousel-item { scroll-snap-align: start; }
        
        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #10b981, #059669); border-radius: 4px; }
        
        .audio-btn {
            transition: all 0.3s ease;
        }
        .audio-btn:hover {
            transform: scale(1.1);
        }
        .audio-playing {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation - Civic Theme -->
    <nav class="fixed w-full z-50 bg-white/95 backdrop-blur-md shadow-sm border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                            <span class="text-sm text-green-600 block -mt-1">Nepal</span>
                        </div>
                    </a>
                    <span class="hidden md:inline-block text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Anti-Corruption Platform</span>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="#problems" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-exclamation-circle mr-1"></i>Problems</a>
                    <a href="#solutions" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-lightbulb mr-1"></i>Solutions</a>
                    <a href="#solved" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-check-circle mr-1"></i>Solved</a>
                    <a href="#stories" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-star mr-1"></i>Stories</a>
                    <a href="public_heatmap.php" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-map-marked-alt mr-1"></i>Map</a>
                    <a href="public_ledger.php" class="text-gray-600 hover:text-green-600 transition-colors font-medium"><i class="fas fa-book mr-1"></i>Ledger</a>
                    <a href="login.php" class="text-green-600 hover:text-green-700 font-medium"><i class="fas fa-sign-in-alt mr-1"></i>Login</a>
                    <a href="register.php" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-5 py-2 rounded-full font-medium hover:shadow-lg transition-all">
                        <i class="fas fa-user-plus mr-1"></i>Join
                    </a>
                </div>
                
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-4 space-y-3">
                <a href="#problems" class="block py-2 text-gray-600 hover:text-green-600">Problems</a>
                <a href="#solutions" class="block py-2 text-gray-600 hover:text-green-600">Solutions</a>
                <a href="#solved" class="block py-2 text-gray-600 hover:text-green-600">Solved</a>
                <a href="public_heatmap.php" class="block py-2 text-gray-600 hover:text-green-600"><i class="fas fa-map mr-2"></i>Map</a>
                <a href="public_ledger.php" class="block py-2 text-gray-600 hover:text-green-600"><i class="fas fa-book mr-2"></i>Ledger</a>
                <a href="login.php" class="block py-2 text-green-600 font-medium">Login</a>
                <a href="register.php" class="block bg-gradient-to-r from-green-600 to-emerald-600 text-white px-5 py-3 rounded-full font-medium text-center">Join Now</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center pt-16 relative overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-green-500/20 rounded-full blur-3xl"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <div class="inline-flex items-center bg-white/10 px-4 py-2 rounded-full mb-6">
                        <span class="w-2 h-2 bg-amber-400 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-sm">Civic Platform Live • Every Rupee is Trackable</span>
                    </div>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        <span class="text-white">Solve Nepal's</span>
                        <br>
                        <span class="text-amber-300">Real Problems</span>
                    </h1>
                    
                    <p class="text-xl text-green-100 mb-8 leading-relaxed">
                        Citizens report civic issues. Students form teams to solve them. 
                        <strong class="text-white">All transactions are 100% transparent.</strong> No corruption possible.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="post_problem.php" class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-4 px-8 rounded-full text-lg transition-all transform hover:scale-105 shadow-xl pulse-ring">
                            <i class="fas fa-exclamation-triangle mr-3"></i>Report a Problem
                        </a>
                        <a href="#problems" class="inline-flex items-center justify-center bg-white/10 hover:bg-white/20 text-white font-semibold py-4 px-8 rounded-full text-lg transition-all border-2 border-white/30">
                            <i class="fas fa-list mr-3"></i>View All Problems
                        </a>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div class="flex items-center bg-white/10 px-3 py-2 rounded-full">
                            <i class="fas fa-shield-alt text-amber-400 mr-2"></i>
                            <span>Anti-Corruption</span>
                        </div>
                        <div class="flex items-center bg-white/10 px-3 py-2 rounded-full">
                            <i class="fas fa-lock text-green-400 mr-2"></i>
                            <span>Escrow Protected</span>
                        </div>
                        <div class="flex items-center bg-white/10 px-3 py-2 rounded-full">
                            <i class="fas fa-eye text-blue-400 mr-2"></i>
                            <span>100% Transparent</span>
                        </div>
                    </div>
                </div>
                
                <div class="hidden lg:block relative">
                    <div class="relative bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/20">
                        <div class="text-center text-white mb-6">
                            <i class="fas fa-hand-holding-usd text-5xl mb-3 text-amber-400"></i>
                            <h3 class="font-bold text-xl">Public Transparency Ledger</h3>
                            <p class="text-green-200 text-sm">Every transaction is public and trackable</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between bg-white/10 rounded-xl p-4">
                                <span class="text-green-100">Total Problems Reported</span>
                                <span class="text-white font-bold"><?= $openProblems + $solvedCount ?></span>
                            </div>
                            <div class="flex items-center justify-between bg-white/10 rounded-xl p-4">
                                <span class="text-green-100">Solutions Delivered</span>
                                <span class="text-white font-bold"><?= $activeSolutions ?></span>
                            </div>
                            <div class="flex items-center justify-between bg-white/10 rounded-xl p-4">
                                <span class="text-green-100">Platform Commission</span>
                                <span class="text-green-400 font-bold">0% (Civic)</span>
                            </div>
                        </div>
                        
                        <a href="public_ledger.php" class="mt-6 block text-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-medium transition-all">
                            <i class="fas fa-external-link-alt mr-2"></i>View Full Ledger
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Stats -->
    <section class="py-12 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold text-red-600" id="statProblems"><?= $openProblems ?></div>
                    <div class="text-gray-500 font-medium">Open Problems</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600" id="statSolutions"><?= $activeSolutions ?></div>
                    <div class="text-gray-500 font-medium">Active Solutions</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-amber-600" id="statSolved"><?= $solvedCount ?></div>
                    <div class="text-gray-500 font-medium">Problems Solved</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600" id="statUsers"><?= $stats['sponsors'] ?></div>
                    <div class="text-gray-500 font-medium">Active Sponsors</div>
                </div>
            </div>
        </div>
    </section>

    <!-- PUBLIC PROBLEMS SECTION -->
    <section id="problems" class="py-16 bg-gradient-to-br from-gray-50 to-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="inline-block bg-red-100 text-red-700 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <i class="fas fa-fire mr-2"></i>Civic Problems - Publicly Visible
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">All Civic Problems</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Every problem reported is public. Upvote what matters most to your community.</p>
            </div>
            
            <?php if (empty($allProblems)): ?>
            <div class="text-center py-16">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-xl">No problems reported yet. Be the first!</p>
                <a href="post_problem.php" class="inline-block mt-6 bg-green-600 text-white px-8 py-3 rounded-full font-bold hover:bg-green-700 transition-all">
                    <i class="fas fa-plus mr-2"></i>Report First Problem
                </a>
            </div>
            <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($allProblems as $problem): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                    <?php if ($problem['photo']): ?>
                    <img src="<?= e($problem['photo']) ?>" alt="Problem photo" class="w-full h-40 object-cover">
                    <?php else: ?>
                    <div class="w-full h-40 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getCategoryClass($problem['category']) ?>"><?= e($problem['category']) ?></span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getUrgencyClass($problem['urgency']) ?>"><?= e($problem['urgency']) ?> Urgency</span>
                            <?php if ($problem['status'] === 'in_progress'): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">In Progress</span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="font-bold text-gray-800 text-lg mb-2"><?= e($problem['title']) ?></h3>
                        <p class="text-sm text-gray-600 mb-3 line-clamp-3"><?= e(substr($problem['description'], 0, 150)) ?>...</p>
                        
                        <!-- Audio/Listen Button -->
                        <button onclick="speakText('<?= e(addslashes($problem['title'])) . '. ' . e(addslashes($problem['description'])) ?>', this)" 
                            class="audio-btn w-full bg-green-100 hover:bg-green-200 text-green-700 py-2 px-4 rounded-lg text-sm font-medium mb-3 flex items-center justify-center gap-2">
                            <i class="fas fa-volume-up"></i> Listen to Problem
                        </button>
                        
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                            <span><i class="fas fa-user mr-1"></i><?= e($problem['reporter_name']) ?></span>
                            <span><i class="fas fa-map-marker-alt mr-1"></i><?= e($problem['location'] ?: 'N/A') ?></span>
                        </div>
                        
                        <?php if ($problem['local_impact_category']): ?>
                        <div class="bg-green-50 text-green-700 px-3 py-2 rounded-lg text-xs mb-3">
                            <i class="fas fa-globe mr-1"></i><?= e($problem['local_impact_category']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Upvote Button -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button onclick="upvoteProblem(<?= $problem['id'] ?>, this)" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-medium">
                                <i class="fas fa-thumbs-up"></i>
                                <span id="upvote-<?= $problem['id'] ?>"><?= $problem['upvotes'] ?? 0 ?></span> Upvotes
                            </button>
                            <a href="problem_detail.php?id=<?= $problem['id'] ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                View Details <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-10">
                <a href="post_problem.php" class="inline-flex items-center bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white font-bold py-4 px-8 rounded-full transition-all shadow-lg">
                    <i class="fas fa-exclamation-triangle mr-3"></i>Report a Civic Problem
                </a>
            </div>
        </div>
    </section>

    <!-- SOLUTIONS SECTION -->
    <section id="solutions" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="inline-block bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <i class="fas fa-lightbulb mr-2"></i>Solutions - Publicly Visible
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">All Approved Solutions</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Solutions submitted by students and teams. Like/dislike to help identify the best.</p>
            </div>
            
            <?php if (empty($solutionsList)): ?>
            <div class="text-center py-16">
                <i class="fas fa-lightbulb text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-xl">No solutions yet. Problems need solvers!</p>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($solutionsList as $solution): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-200 card-hover">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php if ($solution['problem_title']): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-link mr-1"></i><?= e(substr($solution['problem_title'], 0, 40)) ?>
                                </span>
                                <?php endif; ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $solution['status'] === 'rewarded' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= ucfirst($solution['status']) ?>
                                </span>
                            </div>
                            
                            <h3 class="font-bold text-gray-800 text-xl mb-2"><?= e($solution['title']) ?></h3>
                            <p class="text-gray-600 mb-4"><?= e(substr($solution['description'], 0, 300)) ?>...</p>
                            
                            <!-- Audio/Listen Button -->
                            <button onclick="speakText('Solution: <?= e(addslashes($solution['title'])) ?>. <?= e(addslashes($solution['description'])) ?>', this)" 
                                class="audio-btn bg-green-100 hover:bg-green-200 text-green-700 py-2 px-4 rounded-lg text-sm font-medium mb-4 flex items-center gap-2">
                                <i class="fas fa-volume-up"></i> Listen to Solution
                            </button>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-user-circle mr-1 text-green-600"></i>
                                    <?= e($solution['solver_name']) ?>
                                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700"><?= $solution['solver_trust'] ?? 100 ?></span>
                                </span>
                                <?php if ($solution['team_name']): ?>
                                <span class="flex items-center">
                                    <i class="fas fa-users mr-1 text-purple-600"></i>
                                    <?= e($solution['team_name']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="flex items-center">
                                    <i class="fas fa-coins mr-1 text-amber-600"></i>
                                    Budget: <?= formatCurrency($solution['budget_estimate']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-center justify-center p-4 bg-white rounded-xl min-w-[120px]">
                            <!-- Like Button -->
                            <button onclick="likeSolution(<?= $solution['id'] ?>, 'like', this)" class="text-green-600 hover:text-green-700 p-2 rounded-full hover:bg-green-100 transition-all">
                                <i class="fas fa-thumbs-up text-xl"></i>
                            </button>
                            <span id="likes-<?= $solution['id'] ?>" class="text-2xl font-bold text-green-600 my-2"><?= $solution['likes'] ?? 0 ?></span>
                            
                            <!-- Dislike Button -->
                            <button onclick="likeSolution(<?= $solution['id'] ?>, 'dislike', this)" class="text-red-600 hover:text-red-700 p-2 rounded-full hover:bg-red-100 transition-all">
                                <i class="fas fa-thumbs-down text-xl"></i>
                            </button>
                            <span id="dislikes-<?= $solution['id'] ?>" class="text-sm text-gray-500"><?= $solution['dislikes'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SUCCESS STORIES SECTION -->
    <section id="stories" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="inline-block bg-amber-100 text-amber-700 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <i class="fas fa-star mr-2"></i>Success Stories
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Real Impact, Real Change</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">See how civic problems were solved with transparency and measurable impact.</p>
            </div>
            
            <?php if (!empty($successStories)): ?>
            <div class="carousel-container flex gap-6 pb-4">
                <?php foreach ($successStories as $story): ?>
                <div class="carousel-item min-w-[350px] bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-200 card-hover flex-shrink-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-trophy text-2xl text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800"><?= e($story['title']) ?></h3>
                            <p class="text-sm text-gray-500"><?= e($story['author_name']) ?></p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-4 line-clamp-4"><?= e(substr($story['story'], 0, 200)) ?>...</p>
                    
                    <?php if ($story['impact_metric']): ?>
                    <div class="bg-white rounded-lg p-3 mb-4">
                        <p class="text-xs text-gray-500 mb-1">Impact Metric</p>
                        <p class="text-sm font-semibold text-green-700"><?= e($story['impact_metric']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($story['reward_amount'] > 0): ?>
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
                            <i class="fas fa-coins mr-1"></i><?= formatCurrency($story['reward_amount']) ?>
                        </span>
                        <span class="text-sm text-gray-500"><?= e($story['location']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl">Success stories coming soon!</p>
                <p class="text-sm mt-2">Be the first to create an impact story.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SOLVED PROBLEMS SECTION -->
    <section id="solved" class="py-16 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="inline-block bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <i class="fas fa-trophy mr-2"></i>Impact Achieved
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Problems SOLVED</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Real impact, real change. See how civic problems were solved with full transparency.</p>
            </div>
            
            <?php if (empty($solvedList)): ?>
            <div class="text-center py-16">
                <i class="fas fa-check-circle text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-xl">No problems solved yet. Help us make the first impact!</p>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($solvedList as $solved): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 card-hover">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-3xl text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-xl"><?= e($solved['title']) ?></h3>
                                    <p class="text-sm text-gray-500">
                                        Reported by <?= e($solved['reporter_name']) ?>
                                        <span class="mx-2">•</span>
                                        Solved on <?= date('M j, Y', strtotime($solved['solved_at'] ?? $solved['created_at'])) ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>SOLVED
                                </span>
                            </div>
                            
                            <?php if ($solved['solution_title']): ?>
                            <div class="mt-4 p-4 bg-green-50 rounded-xl border border-green-200">
                                <h4 class="font-semibold text-green-800 mb-2">
                                    <i class="fas fa-lightbulb mr-2"></i>Solution Applied
                                </h4>
                                <p class="text-gray-700"><?= e(substr($solved['solution_desc'], 0, 200)) ?>...</p>
                                <div class="flex items-center gap-4 mt-3 text-sm">
                                    <span class="text-green-700 font-medium">
                                        <i class="fas fa-user mr-1"></i><?= e($solved['solver_name']) ?>
                                    </span>
                                    <?php if ($solved['team_name']): ?>
                                    <span class="text-purple-700">
                                        <i class="fas fa-users mr-1"></i><?= e($solved['team_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-amber-700">
                                        <i class="fas fa-coins mr-1"></i><?= formatCurrency($solved['solution_budget']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 hero-gradient text-white text-center">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Make a Civic Impact?</h2>
            <p class="text-xl text-green-100 mb-10">Report a problem. Submit a solution. Track every rupee.</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="post_problem.php" class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-4 px-10 rounded-full text-lg transition-all transform hover:scale-105 shadow-xl">
                    <i class="fas fa-exclamation-triangle mr-3"></i>Report a Problem
                </a>
                <a href="register.php" class="inline-flex items-center justify-center bg-white/10 hover:bg-white/20 text-white font-semibold py-4 px-10 rounded-full text-lg transition-all border-2 border-white/30">
                    <i class="fas fa-user-plus mr-3"></i>Join as Student
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xl">CivicLedger</span>
                            <span class="text-sm text-green-400 block">Nepal</span>
                        </div>
                    </div>
                    <p class="text-gray-400">The Anti-Corruption Civic Platform. 100% transparent. 0% commission.</p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Civic Tools</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="post_problem.php" class="hover:text-white">Report Problem</a></li>
                        <li><a href="public_heatmap.php" class="hover:text-white">Problem Map</a></li>
                        <li><a href="public_ledger.php" class="hover:text-white">Transparency Ledger</a></li>
                        <li><a href="governance.php" class="hover:text-white">Governance Rules</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#problems" class="hover:text-white">All Problems</a></li>
                        <li><a href="#solutions" class="hover:text-white">All Solutions</a></li>
                        <li><a href="#solved" class="hover:text-white">Solved</a></li>
                        <li><a href="login.php" class="hover:text-white">Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i>support@civicedger.nepal</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Kathmandu, Nepal</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> CivicLedger Nepal. Civic Innovation Platform. Built for Nepal.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Text-to-Speech Function
        let currentUtterance = null;
        
        function speakText(text, button) {
            if ('speechSynthesis' in window) {
                // Stop any current speech
                if (currentUtterance) {
                    speechSynthesis.cancel();
                    document.querySelectorAll('.audio-playing').forEach(b => b.classList.remove('audio-playing'));
                }
                
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                utterance.rate = 0.9;
                utterance.pitch = 1;
                
                button.classList.add('audio-playing');
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Playing...';
                
                utterance.onend = function() {
                    button.classList.remove('audio-playing');
                    button.innerHTML = '<i class="fas fa-volume-up"></i> Listen Again';
                };
                
                utterance.onerror = function() {
                    button.classList.remove('audio-playing');
                    button.innerHTML = '<i class="fas fa-volume-up"></i> Listen';
                };
                
                currentUtterance = utterance;
                speechSynthesis.speak(utterance);
            } else {
                alert('Text-to-speech not supported in this browser. Please use Chrome.');
            }
        }

        // Upvote Problem
        function upvoteProblem(problemId, button) {
            fetch('api_upvote_problem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ problem_id: problemId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('upvote-' + problemId).textContent = data.upvotes;
                    button.classList.add('text-green-700', 'bg-green-100');
                }
            });
        }

        // Like/Dislike Solution
        function likeSolution(solutionId, type, button) {
            fetch('api_like_solution.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ solution_id: solutionId, type: type })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('likes-' + solutionId).textContent = data.likes;
                    document.getElementById('dislikes-' + solutionId).textContent = data.dislikes;
                }
            });
        }
    </script>

<?php
?>

</body>
</html>