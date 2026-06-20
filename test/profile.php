<?php
/**
 * CivicLedger - User Profile
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$sub = getUserSubscription($user['id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $dob = $_POST['dob'] ?? null;
    
    if (empty($name)) $errors[] = "Name is required";
    
    if (empty($errors)) {
        // Handle photo upload
        $profilePhoto = $user['profile_photo'];
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $newPhoto = uploadFile('profile_photo', 'uploads/profiles/');
            if ($newPhoto) {
                $profilePhoto = $newPhoto;
                // Delete old photo if exists
                if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
            }
        }
        
        global $conn;
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, bio = ?, skills = ?, date_of_birth = ?, profile_photo = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $phone, $bio, $skills, $dob, $profilePhoto, $user['id']);
        
        if ($stmt->execute()) {
            setFlash('success', 'Profile updated successfully!');
            header("Location: profile.php");
            exit;
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Refresh user data
$user = getCurrentUser();
getHeader('Profile');
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Profile Header -->
        <div class="gradient-bg p-8 text-white">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="relative">
                    <?php if ($user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                        <img src="<?= e($user['profile_photo']) ?>" class="w-32 h-32 rounded-full object-cover border-4 border-white">
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center border-4 border-white">
                            <span class="text-4xl font-bold"><?= getInitials($user['name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-bold"><?= e($user['name']) ?></h1>
                    <p class="text-purple-100 mt-1">
                        <i class="fas fa-tag mr-2"></i><?= ucfirst(e($user['role'])) ?>
                    </p>
                    <p class="text-purple-100">
                        <i class="fas fa-envelope mr-2"></i><?= e($user['email']) ?>
                    </p>
                </div>
                <div class="md:ml-auto text-center">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold <?= $sub['is_premium'] ? 'bg-amber-500' : ($sub['is_plus'] ? 'bg-purple-500' : 'bg-gray-500') ?>">
                        <i class="fas fa-crown mr-1"></i><?= ucfirst($sub['plan']) ?> Plan
                    </span>
                    <?php if (!$sub['is_plus']): ?>
                        <a href="subscription.php" class="block mt-2 text-sm text-amber-300 hover:text-amber-200">
                            Upgrade for unlimited mentor messages
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Profile Form -->
        <div class="p-8">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-primary"></i>Full Name *
                        </label>
                        <input type="text" name="name" required value="<?= e($user['name']) ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    
                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-2 text-primary"></i>Phone
                        </label>
                        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    
                    <!-- DOB -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-2 text-primary"></i>Date of Birth
                        </label>
                        <input type="date" name="dob" value="<?= e($user['date_of_birth'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    
                    <!-- Profile Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-camera mr-2 text-primary"></i>Profile Photo
                        </label>
                        <input type="file" name="profile_photo" accept="image/*"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                </div>
                
                <!-- Bio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-info-circle mr-2 text-primary"></i>Bio
                    </label>
                    <textarea name="bio" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="Tell us about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
                </div>
                
                <!-- Skills -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tools mr-2 text-primary"></i>Skills
                    </label>
                    <input type="text" name="skills" value="<?= e($user['skills'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="e.g., Programming, Design, Research (comma separated)">
                </div>
                
                <!-- Submit -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-primary to-secondary hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="dashboard.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg text-center transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Subscription Info -->
        <div class="bg-gray-50 p-6 border-t">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-crown mr-2 text-amber-500"></i>Subscription Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Current Plan</p>
                    <p class="text-xl font-bold text-primary"><?= ucfirst($sub['plan']) ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Mentor Messages</p>
                    <p class="text-xl font-bold text-green-600">
                        <?php if ($sub['is_plus']): ?>
                            Unlimited
                        <?php else: ?>
                            <?= $sub['mentor_messages_limit'] - $sub['mentor_messages_used'] ?> / <?= $sub['mentor_messages_limit'] ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="bg-white p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Valid Until</p>
                    <p class="text-xl font-bold text-gray-600">
                        <?= isset($sub['end_date']) ? date('M j, Y', strtotime($sub['end_date'])) : 'N/A' ?>
                    </p>
                </div>
            </div>
            <?php if (!$sub['is_plus']): ?>
                <div class="mt-4 text-center">
                    <a href="subscription.php" class="inline-block bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-full transition-all transform hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i>Upgrade Your Plan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php getFooter(); ?>