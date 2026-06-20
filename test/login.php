<?php
/**
 * CivicLedger - User Login (Civic Green Theme)
 */
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password";
    } else {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $r = $stmt->get_result();
        $user = $r ? $r->fetch_assoc() : null;
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            setFlash('success', 'Welcome back, ' . e($user['name']) . '!');
            // Redirect based on role
            switch($user['role']) {
                case 'admin': header("Location: admin_dashboard.php"); break;
                case 'mentor': header("Location: mentor_dashboard.php"); break;
                case 'sponsor': header("Location: sponsor_dashboard.php"); break;
                case 'student': header("Location: student_dashboard.php"); break;
                case 'citizen': header("Location: citizen_dashboard.php"); break;
                default: header("Location: dashboard.php");
            }
            exit;
        } else {
            $errors[] = "Invalid email or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CivicLedger Nepal</title>
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
<body class="hero-gradient min-h-screen flex flex-col">

    <!-- Navigation -->
    <nav class="bg-white/90 backdrop-blur-md shadow-sm py-4">
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
            <a href="register.php" class="text-green-600 hover:text-green-700 font-medium font-semibold">
                Don't have an account? <span class="font-bold">Register</span>
            </a>
        </div>
    </nav>

    <div class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-10">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>
                    <p class="text-gray-500 mt-2">Login to CivicLedger Nepal</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                        <?= e($errors[0]) ?>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-green-600"></i>Email Address
                        </label>
                        <div class="relative">
                            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                                class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                placeholder="you@example.com">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-green-600"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required id="passwordField"
                                class="w-full pl-12 pr-12 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                placeholder="Enter your password">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-3"></i>Login
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-gray-500 mb-4 font-medium">Demo Accounts (password: password123)</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-green-50 rounded-lg p-2">
                            <span class="text-gray-600">Admin</span>
                            <p class="font-mono text-green-600">admin@elite4.com</p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-2">
                            <span class="text-gray-600">Mentor</span>
                            <p class="font-mono text-purple-600">mentor@elite4.com</p>
                        </div>
                        <div class="bg-amber-50 rounded-lg p-2">
                            <span class="text-gray-600">Sponsor</span>
                            <p class="font-mono text-amber-600">sponsor@elite4.com</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-2">
                            <span class="text-gray-600">Student</span>
                            <p class="font-mono text-blue-600">student@elite4.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const passwordField = document.getElementById('passwordField');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    </script>

</body>
</html>