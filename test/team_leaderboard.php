<?php
/**
 * CivicLedger - Team Leaderboard
 */
require_once 'config.php';
requireLogin();

global $conn;
$qr = $conn->query("SELECT t.*, u.name as leader_name FROM teams t JOIN users u ON t.leader_id = u.id ORDER BY t.rank_points DESC LIMIT 20");
$topTeams = $qr ? $qr->fetch_all(MYSQLI_ASSOC) : [];

getHeader('Team Leaderboard');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trophy text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800">Team Leaderboard</h1>
        <p class="text-gray-500 mt-2">Top performing teams based on milestones and solutions</p>
    </div>
    
    <!-- Top 3 Podium -->
    <?php if (count($topTeams) >= 3): ?>
        <div class="flex justify-center items-end gap-4 mb-12">
            <!-- 2nd Place -->
            <div class="text-center">
                <div class="w-20 h-20 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2 overflow-hidden border-4 border-gray-400">
                    <i class="fas fa-users text-3xl text-gray-500"></i>
                </div>
                <div class="bg-gray-200 rounded-t-lg p-4 w-32">
                    <p class="font-bold text-gray-700"><?= e($topTeams[1]['name']) ?></p>
                    <p class="text-2xl font-bold text-gray-600"><?= $topTeams[1]['rank_points'] ?></p>
                    <p class="text-xs text-gray-500">points</p>
                </div>
                <div class="bg-gray-400 text-white py-2 rounded-b-lg w-32">
                    <i class="fas fa-medal mr-1"></i>2nd
                </div>
            </div>
            
            <!-- 1st Place -->
            <div class="text-center">
                <div class="w-24 h-24 bg-amber-400 rounded-full flex items-center justify-center mx-auto mb-2 overflow-hidden border-4 border-amber-600">
                    <i class="fas fa-crown text-4xl text-white"></i>
                </div>
                <div class="bg-amber-100 rounded-t-lg p-4 w-36">
                    <p class="font-bold text-amber-800"><?= e($topTeams[0]['name']) ?></p>
                    <p class="text-3xl font-bold text-amber-600"><?= $topTeams[0]['rank_points'] ?></p>
                    <p class="text-xs text-amber-600">points</p>
                </div>
                <div class="bg-amber-500 text-white py-2 rounded-b-lg w-36">
                    <i class="fas fa-trophy mr-1"></i>1st
                </div>
            </div>
            
            <!-- 3rd Place -->
            <div class="text-center">
                <div class="w-20 h-20 bg-orange-300 rounded-full flex items-center justify-center mx-auto mb-2 overflow-hidden border-4 border-orange-500">
                    <i class="fas fa-users text-3xl text-orange-600"></i>
                </div>
                <div class="bg-orange-50 rounded-t-lg p-4 w-32">
                    <p class="font-bold text-orange-700"><?= e($topTeams[2]['name']) ?></p>
                    <p class="text-2xl font-bold text-orange-600"><?= $topTeams[2]['rank_points'] ?></p>
                    <p class="text-xs text-orange-500">points</p>
                </div>
                <div class="bg-orange-400 text-white py-2 rounded-b-lg w-32">
                    <i class="fas fa-medal mr-1"></i>3rd
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Full Leaderboard -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-xl font-bold"><i class="fas fa-list-ol mr-2 text-primary"></i>All Teams</h2>
        </div>
        
        <?php if (empty($topTeams)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-users text-4xl mb-4"></i>
                <p>No teams yet.</p>
            </div>
        <?php else: ?>
            <div class="divide-y">
                <?php foreach ($topTeams as $idx => $team): ?>
                    <div class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-all <?= $idx < 3 ? 'bg-gradient-to-r ' . ($idx === 0 ? 'from-amber-50 to-transparent' : ($idx === 1 ? 'from-gray-50 to-transparent' : 'from-orange-50 to-transparent')) : '' ?>">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $idx === 0 ? 'bg-amber-500 text-white' : ($idx === 1 ? 'bg-gray-400 text-white' : ($idx === 2 ? 'bg-orange-400 text-white' : 'bg-gray-200 text-gray-600')) ?>">
                            <?php if ($idx < 3): ?>
                                <i class="fas fa-trophy"></i>
                            <?php else: ?>
                                <?= $idx + 1 ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-lg"><?= e($team['name']) ?></p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-crown mr-1"></i><?= e($team['leader_name']) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold <?= $idx === 0 ? 'text-amber-600' : 'text-gray-600' ?>"><?= $team['rank_points'] ?></p>
                            <p class="text-xs text-gray-500">points</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Points System Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-info-circle mr-2 text-blue-600"></i>How Points Are Earned</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-flag-checkered text-green-600"></i>
                <span>Milestone Completed: <strong>+10 pts</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-lightbulb text-amber-600"></i>
                <span>Solution Approved: <strong>+20 pts</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-trophy text-purple-600"></i>
                <span>Solution Rewarded: <strong>+50 pts</strong></span>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>