<?php
/**
 * HTML wrapper functions for pages that use getHeader/getFooter
 */

function getHeader($title = 'ELITE-4 Nepal') {
    $user = getCurrentUser();
    $roleNav = '';
    $mobileNav = '';

    if ($user) {
        $name = e($user['name']);
        $role = $user['role'];
        $navItems = [];

        switch ($role) {
            case 'student':
                $navItems = [
                    ['url' => 'dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'team_formation.php', 'label' => 'Teams'],
                    ['url' => 'micro_gigs.php', 'label' => 'Gigs'],
                    ['url' => 'mentor_chat.php', 'label' => 'Mentor Chat'],
                    ['url' => 'profile.php', 'label' => 'Profile'],
                ];
                break;
            case 'citizen':
                $navItems = [
                    ['url' => 'dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'post_problem.php', 'label' => 'Post Problem'],
                    ['url' => 'post_gig.php', 'label' => 'Post Gig'],
                    ['url' => 'profile.php', 'label' => 'Profile'],
                ];
                break;
            case 'mentor':
                $navItems = [
                    ['url' => 'mentor_dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'team_leaderboard.php', 'label' => 'Leaderboard'],
                    ['url' => 'profile.php', 'label' => 'Profile'],
                ];
                break;
            case 'sponsor':
                $navItems = [
                    ['url' => 'sponsor_dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'create_challenge.php', 'label' => 'Challenge'],
                    ['url' => 'create_sponsorship.php', 'label' => 'Sponsor'],
                    ['url' => 'sponsor_progress.php', 'label' => 'Analytics'],
                    ['url' => 'profile.php', 'label' => 'Profile'],
                ];
                break;
            case 'admin':
                $navItems = [
                    ['url' => 'admin_dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'admin_chat_moderation.php', 'label' => 'Moderation'],
                    ['url' => 'admin_teams.php', 'label' => 'Teams'],
                    ['url' => 'admin_success_stories.php', 'label' => 'Stories'],
                    ['url' => 'admin_commission.php', 'label' => 'Settings'],
                ];
                break;
            default:
                $navItems = [
                    ['url' => 'dashboard.php', 'label' => 'Dashboard'],
                    ['url' => 'profile.php', 'label' => 'Profile'],
                ];
        }

        $desktopNav = '';
        $mobileNavItems = '';
        foreach ($navItems as $item) {
            $desktopNav .= '<a href="' . $item['url'] . '" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">' . $item['label'] . '</a>';
            $mobileNavItems .= '<a href="' . $item['url'] . '" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">' . $item['label'] . '</a>';
        }

        $roleNav = $desktopNav . '<a href="profile.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-user mr-1"></i>' . $name . '</a><a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Logout</a>';
        $mobileNav = $mobileNavItems . '<a href="profile.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-lg">Profile</a><a href="logout.php" class="block py-2 px-4 bg-red-500 text-white rounded-lg">Logout</a>';
    } else {
        $roleNav = '<a href="login.php" class="px-4 py-2 text-white/80 hover:text-white">Login</a><a href="register.php" class="px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-gray-100 font-semibold">Sign Up</a>';
        $mobileNav = '<a href="login.php" class="block py-2 px-4 text-white/80 hover:bg-white/10 rounded-lg">Login</a><a href="register.php" class="block py-2 px-4 bg-white text-blue-600 rounded-lg font-semibold">Sign Up</a>';
    }

    $flash = getFlash();
    $flashHtml = '';
    if ($flash) {
        $bgColor = $flash['type'] === 'success' ? 'bg-green-600' : 'bg-red-600';
        $icon = $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle';
        $flashHtml = '<div id="flashMsg" class="fixed top-20 right-6 z-50 p-4 rounded-xl shadow-2xl ' . $bgColor . ' text-white max-w-sm"><div class="flex items-center gap-3"><i class="fas fa-' . $icon . '"></i><span>' . e($flash['message']) . '</span></div></div>';
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - ELITE-4 Nepal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#4f46e5', secondary: '#7c3aed', elite: { 600: '#2563eb' } } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); transition: all 0.3s; }
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }
        .carousel-container { scroll-snap-type: x mandatory; overflow-x: auto; }
        .carousel-container::-webkit-scrollbar { display: none; }
        .carousel-item { scroll-snap-align: start; flex-shrink: 0; }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <span class="font-bold text-xl text-gray-800">ELITE-4 <span class="text-blue-600">Nepal</span></span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    <?= $roleNav ?>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-600 text-2xl"><i class="fas fa-bars"></i></button>
            </div>
            <div id="mobileMenu" class="hidden md:hidden pb-4 space-y-2">
                <?= $mobileNav ?>
            </div>
        </div>
    </nav>
    <?= $flashHtml ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
        var flashEl = document.getElementById('flashMsg');
        if (flashEl) setTimeout(function() { flashEl.remove(); }, 4000);
    </script>
<?php
}

function getFooter() {
    echo '</div><!-- end container -->
</body>
</html>';
}