<?php
/**
 * CivicLedger - Browse Micro Gigs
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
global $conn;

$category = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT g.*, u.name as citizen_name FROM `micro_gigs` g JOIN users u ON g.citizen_id = u.id WHERE g.status = 'open'";
if (!empty($category)) {
    $sql .= " AND g.category = '" . $conn->real_escape_string($category) . "'";
}
$sql .= " ORDER BY g.created_at DESC";
$qg = $conn->query($sql);
$gigs = array();
if ($qg && $qg->num_rows > 0) {
    $gigs = $qg->fetch_all(MYSQLI_ASSOC);
}

$myApplications = array();
$stmt = $conn->prepare("SELECT gig_id FROM gig_applications WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        $appData = $r->fetch_all(MYSQLI_ASSOC);
        $myApplications = array_column($appData, 'gig_id');
    }
}

getHeader('Micro Gigs');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">
                <i class="fas fa-briefcase mr-2 text-amber-500"></i>Micro Gigs
            </h1>
            <p class="text-gray-500 mt-2">Earn money by completing small tasks for citizens (SDG 8)</p>
        </div>
        <a href="post_gig.php" class="mt-4 md:mt-0 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Post a Gig
        </a>
    </div>
    
    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="micro_gigs.php" class="px-4 py-2 rounded-full <?php echo (!$category) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            All
        </a>
        <a href="micro_gigs.php?category=Technology" class="px-4 py-2 rounded-full <?php echo ($category === 'Technology') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            Technology
        </a>
        <a href="micro_gigs.php?category=Design" class="px-4 py-2 rounded-full <?php echo ($category === 'Design') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            Design
        </a>
        <a href="micro_gigs.php?category=Writing" class="px-4 py-2 rounded-full <?php echo ($category === 'Writing') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            Writing
        </a>
        <a href="micro_gigs.php?category=Other" class="px-4 py-2 rounded-full <?php echo ($category === 'Other') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            Other
        </a>
    </div>
    
    <?php if (empty($gigs)): ?>
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-briefcase text-4xl text-gray-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Gigs Available</h2>
            <p class="text-gray-500">Check back later for new opportunities.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($gigs as $gig): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                            <?php echo e($gig['category'] ?: 'Other'); ?>
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Open
                        </span>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo e($gig['title']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo e($gig['description']); ?></p>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Posted by</p>
                            <p class="font-semibold"><?php echo e($gig['citizen_name']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600"><?php echo formatCurrency($gig['budget']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (in_array($gig['id'], $myApplications)): ?>
                        <div class="bg-blue-50 text-blue-700 font-semibold py-3 rounded-lg text-center">
                            <i class="fas fa-check-circle mr-2"></i>Application Submitted
                        </div>
                    <?php else: ?>
                        <a href="apply_gig.php?gig_id=<?php echo $gig['id']; ?>" class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg text-center transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>Apply Now
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
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
                    Micro gigs help promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all. Students can earn while learning!
                </p>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>