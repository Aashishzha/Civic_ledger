<?php
/**
 * CivicLedger - My Posted Gigs (Citizen)
 */
require_once 'config.php';
requireRole('citizen');

$user = getCurrentUser();
global $conn;

$message = '';
$success = '';

// Handle accept/reject application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($applicationId && in_array($action, ['accept', 'reject'])) {
        // Verify this gig belongs to current user
        $stmt = $conn->prepare("
            SELECT g.id, g.citizen_id, ga.student_id 
            FROM micro_gigs g 
            JOIN gig_applications ga ON ga.gig_id = g.id 
            WHERE ga.id = ?
        ");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $r = $stmt->get_result();
        $app = $r ? $r->fetch_assoc() : null;
        
        if ($app && $app['citizen_id'] == $user['id']) {
            if ($action === 'accept') {
                // Accept application and assign gig
                $stmt = $conn->prepare("UPDATE gig_applications SET status = 'accepted' WHERE id = ?");
                $stmt->bind_param("i", $applicationId);
                $stmt->execute();
                
                // Update gig status and assigned_to
                $stmt = $conn->prepare("UPDATE micro_gigs SET status = 'assigned', assigned_to = ? WHERE id = ?");
                $stmt->bind_param("ii", $app['student_id'], $app['id']);
                $stmt->execute();
                
                // Reject other applications
                $stmt = $conn->prepare("UPDATE gig_applications SET status = 'rejected' WHERE gig_id = ? AND id != ?");
                $stmt->bind_param("ii", $app['id'], $applicationId);
                $stmt->execute();
                
                setFlash('success', 'Application accepted! The student has been assigned to your gig.');
            } else {
                // Reject application
                $stmt = $conn->prepare("UPDATE gig_applications SET status = 'rejected' WHERE id = ?");
                $stmt->bind_param("i", $applicationId);
                $stmt->execute();
                
                setFlash('info', 'Application rejected.');
            }
            header("Location: my_gigs.php");
            exit;
        }
    }
}

// Get all gigs posted by this citizen with their applications
$stmt = $conn->prepare("
    SELECT g.*, 
    (SELECT COUNT(*) FROM gig_applications WHERE gig_id = g.id) as total_applications,
    (SELECT COUNT(*) FROM gig_applications WHERE gig_id = g.id AND status = 'accepted') as accepted_count,
    (SELECT COUNT(*) FROM gig_applications WHERE gig_id = g.id AND status = 'pending') as pending_count
    FROM micro_gigs g
    WHERE g.citizen_id = ?
    ORDER BY g.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$myGigs = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

getHeader('My Posted Gigs');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">
                <i class="fas fa-briefcase mr-2 text-amber-500"></i>My Posted Gigs
            </h1>
            <p class="text-gray-500 mt-2">Manage your posted gigs and review applications</p>
        </div>
        <a href="post_gig.php" class="mt-4 md:mt-0 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Post New Gig
        </a>
    </div>

    <?php if (empty($myGigs)): ?>
    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-briefcase text-4xl text-gray-400"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">No Gigs Posted Yet</h2>
        <p class="text-gray-500 mb-6">Start posting gigs to get help from students!</p>
        <a href="post_gig.php" class="inline-block bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Post Your First Gig
        </a>
    </div>
    <?php else: ?>
    
    <?php foreach ($myGigs as $gig): ?>
    <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
        <!-- Gig Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-800"><?= e($gig['title']) ?></h3>
                <p class="text-sm text-gray-500">Posted <?= timeAgo($gig['created_at']) ?></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $gig['status'] === 'open' ? 'bg-green-100 text-green-800' : ($gig['status'] === 'assigned' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') ?>">
                    <?= ucfirst($gig['status']) ?>
                </span>
                <span class="text-2xl font-bold text-green-600"><?= formatCurrency($gig['budget']) ?></span>
            </div>
        </div>

        <!-- Gig Description -->
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-gray-600"><?= nl2br(e($gig['description'])) ?></p>
            <?php if ($gig['category']): ?>
            <span class="inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                <i class="fas fa-tag mr-1"></i><?= e($gig['category']) ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Applications Section -->
        <div class="px-6 py-4">
            <h4 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-users mr-2 text-blue-600"></i>
                Applications (<?= $gig['total_applications'] ?>)
                <?php if ($gig['pending_count'] > 0): ?>
                <span class="ml-2 px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs"><?= $gig['pending_count'] ?> pending</span>
                <?php endif; ?>
            </h4>

            <?php
            // Get applications for this gig
            $stmt = $conn->prepare("
                SELECT ga.*, u.name as student_name, u.email as student_email, u.skills
                FROM gig_applications ga
                JOIN users u ON ga.student_id = u.id
                WHERE ga.gig_id = ?
                ORDER BY ga.created_at DESC
            ");
            $stmt->bind_param("i", $gig['id']);
            $stmt->execute();
            $r = $stmt->get_result();
            $applications = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
            ?>

            <?php if (empty($applications)): ?>
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p>No applications yet.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($applications as $app): ?>
                <div class="border border-gray-200 rounded-xl p-4 <?= $app['status'] === 'accepted' ? 'bg-green-50 border-green-300' : ($app['status'] === 'rejected' ? 'bg-gray-50 opacity-60' : 'bg-white') ?>">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="font-bold text-blue-600"><?= getInitials($app['student_name']) ?></span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?= e($app['student_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= e($app['student_email']) ?></p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $app['status'] === 'accepted' ? 'bg-green-100 text-green-800' : ($app['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>">
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm text-gray-500 mb-1">Proposal:</p>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg"><?= nl2br(e($app['proposal'])) ?></p>
                            </div>
                            
                            <?php if ($app['skills']): ?>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-tools mr-1"></i>Skills: <?= e($app['skills']) ?>
                            </p>
                            <?php endif; ?>
                            
                            <p class="text-xs text-gray-400 mt-2">Applied <?= timeAgo($app['created_at']) ?></p>
                        </div>

                        <?php if ($gig['status'] === 'open' && $app['status'] === 'pending'): ?>
                        <div class="flex flex-col gap-2">
                            <form method="POST">
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                                    <i class="fas fa-check mr-1"></i>Accept
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                                    <i class="fas fa-times mr-1"></i>Reject
                                </button>
                            </form>
                        </div>
                        <?php elseif ($app['status'] === 'accepted'): ?>
                        <div class="text-green-600 font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>Assigned
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>

    <!-- SDG 8 Info -->
    <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-2xl p-8 mt-12">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-xl">8</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800">SDG 8: Decent Work and Economic Growth</h3>
                <p class="text-gray-600 mt-2">
                    Your gigs help students earn money while learning valuable skills. It's a win-win for the community!
                </p>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>