<?php
/**
 * CivicLedger - My Sponsorships
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
global $conn;

$tab = $_GET['tab'] ?? 'outgoing';

// Get outgoing sponsorships (for sponsors)
$stmt = $conn->prepare("SELECT sp.*, u.name as recipient_name, u.role as recipient_role FROM sponsorships sp JOIN users u ON sp.recipient_id = u.id WHERE sp.sponsor_id = ? ORDER BY sp.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$outgoing = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Get incoming sponsorships (for all users)
$stmt = $conn->prepare("SELECT sp.*, u.name as sponsor_name FROM sponsorships sp JOIN users u ON sp.sponsor_id = u.id WHERE sp.recipient_id = ? ORDER BY sp.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$r = $stmt->get_result();
$incoming = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sponsorshipId = (int)($_POST['sponsorship_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    // Verify ownership
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE sponsorships SET status = 'approved' WHERE id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $sponsorshipId, $user['id']);
        $stmt->execute();
        setFlash('success', 'Sponsorship approved!');
    } elseif ($action === 'disburse') {
        $stmt = $conn->prepare("UPDATE sponsorships SET status = 'disbursed' WHERE id = ? AND sponsor_id = ?");
        $stmt->bind_param("ii", $sponsorshipId, $user['id']);
        $stmt->execute();
        setFlash('success', 'Sponsorship disbursed!');
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE sponsorships SET status = 'rejected' WHERE id = ? AND (sponsor_id = ? OR recipient_id = ?)");
        $stmt->bind_param("iii", $sponsorshipId, $user['id'], $user['id']);
        $stmt->execute();
        setFlash('success', 'Sponsorship rejected.');
    }
    
    header("Location: my_sponsorships.php");
    exit;
}

getHeader('My Sponsorships');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">
        <i class="fas fa-hand-holding-usd mr-2 text-green-500"></i>My Sponsorships
    </h1>
    
    <!-- Tabs -->
    <div class="flex gap-4 mb-6">
        <a href="?tab=outgoing" class="px-6 py-3 rounded-lg font-semibold transition-all <?= $tab === 'outgoing' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-arrow-up mr-2"></i>Outgoing
        </a>
        <a href="?tab=incoming" class="px-6 py-3 rounded-lg font-semibold transition-all <?= $tab === 'incoming' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-arrow-down mr-2"></i>Incoming
        </a>
    </div>
    
    <?php if ($tab === 'outgoing'): ?>
        <!-- Outgoing Sponsorships -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Sponsorships I've Made</h2>
            </div>
            
            <?php if (empty($outgoing)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-hand-holding-usd text-4xl mb-4"></i>
                    <p>You haven't sponsored anyone yet.</p>
                    <?php if (hasRole('sponsor')): ?>
                        <a href="create_sponsorship.php" class="inline-block mt-4 bg-primary text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                            Create Sponsorship
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Recipient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($outgoing as $sp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-semibold"><?= e($sp['recipient_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= ucfirst(e($sp['recipient_role'])) ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-green-600 font-bold"><?= formatCurrency($sp['amount']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= e(substr($sp['description'] ?? '', 0, 50)) ?>...</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getSponsorshipStatusClass($sp['status']) ?>">
                                            <?= ucfirst(e($sp['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?= date('M j, Y', strtotime($sp['created_at'])) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($sp['status'] === 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="sponsorship_id" value="<?= $sp['id'] ?>">
                                                <input type="hidden" name="action" value="disburse">
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                    <i class="fas fa-check mr-1"></i>Disburse
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Incoming Sponsorships -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Sponsorships I've Received</h2>
            </div>
            
            <?php if (empty($incoming)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No sponsorships received yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Sponsor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($incoming as $sp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold"><?= e($sp['sponsor_name']) ?></td>
                                    <td class="px-4 py-3 text-green-600 font-bold"><?= formatCurrency($sp['amount']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= e(substr($sp['description'] ?? '', 0, 50)) ?>...</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= getSponsorshipStatusClass($sp['status']) ?>">
                                            <?= ucfirst(e($sp['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($sp['status'] === 'pending' && hasRole('citizen')): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="sponsorship_id" value="<?= $sp['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                                    <i class="fas fa-check mr-1"></i>Accept
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="sponsorship_id" value="<?= $sp['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                    <i class="fas fa-times mr-1"></i>Reject
                                                </button>
                                            </form>
                                        <?php elseif ($sp['status'] === 'approved'): ?>
                                            <span class="text-green-600 text-sm"><i class="fas fa-clock mr-1"></i>Awaiting disbursement</span>
                                        <?php elseif ($sp['status'] === 'disbursed'): ?>
                                            <span class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i>Received</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
function getSponsorshipStatusClass($status) {
    $classes = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-blue-100 text-blue-800', 
                'disbursed' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}
?>

<?php getFooter(); ?>