<?php
/**
 * CivicLedger - Public Team Listing
 */
require_once 'config.php';
requireRole('student');

$user = getCurrentUser();
global $conn;

$myTeam = getUserTeam($user['id']);

// Get all public teams
$stmt = $conn->prepare("SELECT t.*, u.name as leader_name FROM teams t JOIN users u ON t.leader_id = u.id WHERE t.is_public = 1 AND t.status = 'active' ORDER BY t.rank_points DESC");
$stmt->execute();
$r = $stmt->get_result();
$teams = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'])) {
    $teamId = (int)$_POST['team_id'];
    $proposal = trim($_POST['proposal'] ?? '');
    
    if ($myTeam) {
        setFlash('error', 'You are already in a team');
    } elseif (empty($proposal)) {
        setFlash('error', 'Please write a proposal explaining why you want to join');
    } else {
        $stmt = $conn->prepare("INSERT INTO join_requests (team_id, user_id, proposal) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $teamId, $user['id'], $proposal);
        if ($stmt->execute()) {
            setFlash('success', 'Join request sent! Wait for approval from the team leader or mentor.');
        }
    }
    header("Location: team_public_listing.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Teams - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; } .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }</style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-lightbulb text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">CivicLedger <span class="text-blue-600">Nepal</span></span>
                </a>
                <a href="student_dashboard.php" class="text-gray-600 hover:text-blue-600 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-users mr-3 text-blue-600"></i>Browse Teams</h1>
                <p class="text-gray-500 mt-1">Find a team to join and start solving problems together</p>
            </div>
            <?php if ($myTeam): ?>
            <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full font-semibold">
                <i class="fas fa-check mr-2"></i>You're in a team
            </span>
            <?php endif; ?>
        </div>

        <?php if (empty($teams)): ?>
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <i class="fas fa-users text-5xl mb-4 text-gray-300"></i>
            <p class="text-gray-500">No public teams available at the moment.</p>
            <a href="team_formation.php" class="inline-block mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">Create a Team</a>
        </div>
        <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($teams as $team): ?>
            <?php
            $members = json_decode($team['members'] ?? '[]', true) ?: [];
            $memberCount = count($members);
            $memberDetails = [];
            foreach ($members as $mid) {
                $u = getUserById($mid);
                if ($u) $memberDetails[] = $u;
            }
            ?>
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800"><?= e($team['name']) ?></h3>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                        <i class="fas fa-star mr-1"></i><?= $team['rank_points'] ?> pts
                    </span>
                </div>
                
                <?php if ($team['description']): ?>
                <p class="text-gray-600 text-sm mb-4"><?= e(substr($team['description'], 0, 100)) ?>...</p>
                <?php endif; ?>
                
                <?php if ($team['required_skills']): ?>
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-2">Required Skills:</p>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (explode(',', $team['required_skills']) as $skill): ?>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"><?= e(trim($skill)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-2">Members (<?= $memberCount ?>):</p>
                    <div class="flex -space-x-2">
                        <?php foreach (array_slice($memberDetails, 0, 4) as $m): ?>
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center border-2 border-white" title="<?= e($m['name']) ?>">
                            <span class="text-xs font-bold text-blue-600"><?= getInitials($m['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($memberCount > 4): ?>
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center border-2 border-white">
                            <span class="text-xs font-bold text-gray-600">+<?= $memberCount - 4 ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($myTeam): ?>
                <div class="bg-gray-100 text-gray-500 py-3 rounded-lg text-center text-sm">Already in a team</div>
                <?php else: ?>
                <button onclick="document.getElementById('joinForm<?= $team['id'] ?>').classList.toggle('hidden')" class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-3 rounded-lg transition-all">
                    <i class="fas fa-user-plus mr-2"></i>Request to Join
                </button>
                
                <form method="POST" id="joinForm<?= $team['id'] ?>" class="hidden mt-4 p-4 bg-blue-50 rounded-lg">
                    <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                    <textarea name="proposal" rows="3" required class="w-full px-3 py-2 border rounded-lg mb-3 text-sm" placeholder="Why do you want to join? Briefly describe your skills and ideas..."></textarea>
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg font-semibold text-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Send Request
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>