<?php
/**
 * CivicLedger - User Registration (Civic Green Theme)
 */
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'citizen';
    $dob = $_POST['dob'] ?? null;
    $skills = trim($_POST['skills'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (empty($name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (!in_array($role, ['citizen', 'student', 'sponsor', 'mentor'])) {
        $errors[] = "Please select a valid role";
    }
    
    // Check email uniqueness
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0)
        $errors[] = "This email is already registered";
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, date_of_birth, skills, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $email, $phone, $hashedPassword, $role, $dob, $skills, $bio);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // Create free subscription
            $stmtSub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan, start_date, end_date, mentor_messages_used, mentor_messages_reset_at) VALUES (?, 'free', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, CURDATE())");
            $stmtSub->bind_param("i", $userId);
            $stmtSub->execute();
            
            $_SESSION['user_id'] = $userId;
            setFlash('success', 'Welcome to CivicLedger Nepal! Your account has been created.');
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$selectedRole = $_GET['role'] ?? $_POST['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CivicLedger Nepal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        civic: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%); }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm py-4">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white"></i>
                </div>
                <div>
                    <span class="font-bold text-xl text-gray-800">CivicLedger</span>
                    <span class="text-xs text-green-600 block -mt-1">Nepal</span>
                </div>
            </a>
            <a href="login.php" class="text-green-600 hover:text-green-700 font-medium font-semibold">
                Already have an account? <span class="font-bold">Login</span>
            </a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-user-plus text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Create Your Account</h1>
                <p class="text-gray-500 mt-2">Join CivicLedger Nepal - Anti-Corruption Platform</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-6">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-green-600"></i>Full Name *
                        </label>
                        <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="Enter your full name">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-green-600"></i>Email Address *
                        </label>
                        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="you@example.com">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-2 text-green-600"></i>Phone Number
                        </label>
                        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="+977-98XXXXXXXX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-2 text-green-600"></i>Date of Birth
                        </label>
                        <input type="date" name="dob" value="<?= e($_POST['dob'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-green-600"></i>Password *
                        </label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="Minimum 6 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-green-600"></i>Confirm Password *
                        </label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="Re-enter your password">
                    </div>
                </div>

                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-users mr-2 text-green-600"></i>Select Your Role
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php
                        $roles = [
                            'citizen' => ['icon' => 'users', 'title' => 'Citizen', 'desc' => 'Report civic problems', 'color' => 'green'],
                            'student' => ['icon' => 'graduation-cap', 'title' => 'Student', 'desc' => 'Solve problems', 'color' => 'blue'],
                            'sponsor' => ['icon' => 'hand-holding-usd', 'title' => 'Sponsor', 'desc' => 'Fund solutions', 'color' => 'amber'],
                            'mentor' => ['icon' => 'chalkboard-teacher', 'title' => 'Mentor', 'desc' => 'Guide teams', 'color' => 'purple']
                        ];
                        foreach ($roles as $value => $r):
                            $colorClass = $r['color'] === 'green' ? 'green' : ($r['color'] === 'blue' ? 'blue' : ($r['color'] === 'amber' ? 'amber' : 'purple'));
                        ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="<?= $value ?>" class="sr-only peer" <?= $selectedRole === $value ? 'checked' : '' ?>>
                            <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 text-center">
                                <div class="w-12 h-12 bg-<?= $colorClass ?>-100 rounded-xl flex items-center justify-center mb-3 mx-auto">
                                    <i class="fas fa-<?= $r['icon'] ?> text-<?= $colorClass ?>-600 text-xl"></i>
                                </div>
                                <h4 class="font-bold text-gray-800"><?= $r['title'] ?></h4>
                                <p class="text-xs text-gray-500 mt-1"><?= $r['desc'] ?></p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tools mr-2 text-green-600"></i>Skills (comma separated)
                        </label>
                        <input type="text" name="skills" value="<?= e($_POST['skills'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="e.g., Programming, Design">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-info-circle mr-2 text-green-600"></i>Bio
                        </label>
                        <textarea name="bio" rows="2"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="Tell us about yourself..."><?= e($_POST['bio'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                    <a href="index.php" class="px-6 py-4 border border-gray-300 rounded-xl text-gray-600 hover:bg-gray-50 transition-all flex items-center">
                        Cancel
                    </a>
                </div>
            </form>

            <div class="mt-8 p-4 bg-green-50 rounded-xl border border-green-200">
                <p class="text-sm text-green-800 font-medium mb-2"><i class="fas fa-shield-alt mr-2"></i>CivicLedger - Anti-Corruption Platform</p>
                <p class="text-xs text-green-600">0% Commission | 100% Transparent | Trust Score System</p>
            </div>
        </div>
    </div>

</body>
</html>