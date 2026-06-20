<?php
require_once 'config.php';

$page_title = "Civic Problem Heatmap";

// Fetch all problems with locations
$problems = $conn->query("
    SELECT p.*, u.name as reporter_name
    FROM problems p
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");

$problems_data = [];
if ($problems && $problems->num_rows > 0) {
    $problems_data = $problems->fetch_all(MYSQLI_ASSOC);
}

// Get location counts for stats
$location_stats = [];
foreach ($problems_data as $p) {
    $loc = $p['location'] ?? 'Unknown';
    if (!isset($location_stats[$loc])) {
        $location_stats[$loc] = ['count' => 0, 'category' => $p['category']];
    }
    $location_stats[$loc]['count']++;
}

// Category stats
$category_stats = [
    'Health' => 0,
    'Waste' => 0,
    'Road' => 0,
    'Water' => 0,
    'Other' => 0
];
foreach ($problems_data as $p) {
    $cat = $p['category'] ?? 'Other';
    if (isset($category_stats[$cat])) {
        $category_stats[$cat]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 500px; width: 100%; border-radius: 12px; }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-red-700 to-orange-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-map-marked-alt text-3xl"></i>
                    <div>
                        <h1 class="text-xl font-bold">CivicLedger</h1>
                        <p class="text-xs text-orange-100">Civic Problem Heatmap</p>
                    </div>
                </div>
                <nav class="hidden md:flex space-x-4">
                    <a href="index.php" class="hover:text-orange-200"><i class="fas fa-home mr-1"></i> Home</a>
                    <a href="public_ledger.php" class="hover:text-orange-200"><i class="fas fa-book mr-1"></i> Ledger</a>
                    <a href="public_heatmap.php" class="text-white font-semibold"><i class="fas fa-map mr-1"></i> Map</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="md:hidden bg-orange-700 text-white p-4">
        <button id="mobileMenuBtn" class="w-full text-left">
            <i class="fas fa-bars mr-2"></i> Menu
        </button>
        <div id="mobileMenu" class="hidden mt-4 space-y-2">
            <a href="index.php" class="block py-2"><i class="fas fa-home mr-2"></i> Home</a>
            <a href="public_ledger.php" class="block py-2"><i class="fas fa-book mr-2"></i> Ledger</a>
            <a href="public_heatmap.php" class="block py-2 font-semibold"><i class="fas fa-map mr-2"></i> Map</a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        
        <!-- Page Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-4">
                <i class="fas fa-map-location-dot text-4xl text-orange-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Civic Problem Heatmap</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">See what problems your community is facing. Every pin represents a citizen's voice.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-red-100 rounded-xl p-4 text-center border-2 border-red-300">
                <div class="text-3xl font-bold text-red-700"><?php echo $category_stats['Health']; ?></div>
                <div class="text-sm text-red-600 font-medium"><i class="fas fa-heartbeat mr-1"></i> Health</div>
            </div>
            <div class="bg-orange-100 rounded-xl p-4 text-center border-2 border-orange-300">
                <div class="text-3xl font-bold text-orange-700"><?php echo $category_stats['Waste']; ?></div>
                <div class="text-sm text-orange-600 font-medium"><i class="fas fa-trash mr-1"></i> Waste</div>
            </div>
            <div class="bg-gray-100 rounded-xl p-4 text-center border-2 border-gray-300">
                <div class="text-3xl font-bold text-gray-700"><?php echo $category_stats['Road']; ?></div>
                <div class="text-sm text-gray-600 font-medium"><i class="fas fa-road mr-1"></i> Road</div>
            </div>
            <div class="bg-blue-100 rounded-xl p-4 text-center border-2 border-blue-300">
                <div class="text-3xl font-bold text-blue-700"><?php echo $category_stats['Water']; ?></div>
                <div class="text-sm text-blue-600 font-medium"><i class="fas fa-tint mr-1"></i> Water</div>
            </div>
            <div class="bg-purple-100 rounded-xl p-4 text-center border-2 border-purple-300">
                <div class="text-3xl font-bold text-purple-700"><?php echo $category_stats['Other']; ?></div>
                <div class="text-sm text-purple-600 font-medium"><i class="fas fa-ellipsis-h mr-1"></i> Other</div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-map text-orange-500 mr-2"></i>Problem Locations in Nepal</h2>
                <div class="flex items-center space-x-4 text-sm">
                    <span class="legend-item"><span class="w-4 h-4 rounded-full bg-red-500"></span> Health (High)</span>
                    <span class="legend-item"><span class="w-4 h-4 rounded-full bg-orange-500"></span> Waste</span>
                    <span class="legend-item"><span class="w-4 h-4 rounded-full bg-blue-500"></span> Water</span>
                    <span class="legend-item"><span class="w-4 h-4 rounded-full bg-gray-500"></span> Road</span>
                    <span class="legend-item"><span class="w-4 h-4 rounded-full bg-purple-500"></span> Other</span>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <!-- Problem List -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-orange-600 to-red-600 text-white px-6 py-4">
                <h2 class="font-bold text-lg"><i class="fas fa-list mr-2"></i>Recent Civic Reports</h2>
            </div>
            
            <div class="divide-y divide-gray-100">
                <?php if (count($problems_data) > 0): ?>
                    <?php foreach (array_slice($problems_data, 0, 10) as $idx => $problem): ?>
                        <?php
                        $urgency_color = match($problem['urgency'] ?? 'Medium') {
                            'High' => 'bg-red-100 text-red-800 border-red-200',
                            'Medium' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            default => 'bg-green-100 text-green-800 border-green-200'
                        };
                        $category_color = match($problem['category'] ?? 'Other') {
                            'Health' => 'bg-red-500',
                            'Waste' => 'bg-orange-500',
                            'Road' => 'bg-gray-500',
                            'Water' => 'bg-blue-500',
                            default => 'bg-purple-500'
                        };
                        ?>
                        <div class="p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full <?php echo $category_color; ?> text-white font-bold text-sm">
                                        <?php echo strtoupper(substr($problem['category'] ?? 'O', 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <h3 class="font-semibold text-gray-800 truncate"><?php echo e($problem['title']); ?></h3>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $urgency_color; ?> border">
                                            <?php echo e($problem['urgency'] ?? 'Medium'); ?>
                                        </span>
                                        <?php if ($problem['status'] === 'solved'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                <i class="fas fa-check-circle mr-1"></i>SOLVED
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2 mb-2"><?php echo e(substr($problem['description'], 0, 150)); ?>...</p>
                                    <div class="flex items-center text-xs text-gray-500 space-x-4">
                                        <span><i class="fas fa-user mr-1"></i><?php echo e($problem['reporter_name'] ?? 'Anonymous'); ?></span>
                                        <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo e($problem['location'] ?? 'Location not specified'); ?></span>
                                        <span><i class="fas fa-arrow-up mr-1"></i><?php echo $problem['upvotes'] ?? 0; ?> upvotes</span>
                                        <span><i class="fas fa-clock mr-1"></i><?php echo date('M d, Y', strtotime($problem['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-center">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                                        <i class="fas fa-thumbs-up text-orange-500 text-sm"></i>
                                    </div>
                                    <div class="text-xs font-bold text-gray-600 mt-1"><?php echo $problem['upvotes'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-map-pin text-4xl mb-3 text-gray-300"></i>
                        <p>No problems reported yet. Be the first to report a civic issue!</p>
                        <a href="post_problem.php" class="inline-block mt-4 px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                            <i class="fas fa-plus mr-2"></i>Report a Problem
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Report CTA -->
        <div class="mt-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-8 text-white text-center">
            <h2 class="text-2xl font-bold mb-2">See a Problem? Report It!</h2>
            <p class="mb-6 text-orange-100">Your report could help solve a civic issue affecting your community.</p>
            <a href="post_problem.php" class="inline-flex items-center px-8 py-3 bg-white text-orange-600 font-bold rounded-full hover:bg-orange-50 transition shadow-lg">
                <i class="fas fa-exclamation-circle mr-2"></i> Report a Civic Problem
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400"><i class="fas fa-heart text-red-500 mr-1"></i> Built for Nepal's Civic Innovation</p>
            <p class="text-gray-500 text-sm mt-2">CivicLedger - The Problem Solver</p>
        </div>
    </footer>

    <script>
        // Initialize map centered on Nepal
        const map = L.map('map').setView([27.7172, 85.324], 12);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Problem locations with coordinates (for demo - using Kathmandu area)
        const problems = <?php echo json_encode($problems_data); ?>;
        
        // Kathmandu area coordinates for demo
        const kathmanduCenter = { lat: 27.7172, lng: 85.324 };
        const locations = [
            { lat: 27.7215, lng: 85.3111, name: 'Kapan' },
            { lat: 27.7095, lng: 85.3140, name: 'Baneshwor' },
            { lat: 27.7285, lng: 85.3340, name: 'Thamel' },
            { lat: 27.7045, lng: 85.3090, name: 'Satdobato' },
            { lat: 27.7355, lng: 85.3510, name: 'New Road' },
            { lat: 27.6895, lng: 85.3420, name: 'Kirtipur' }
        ];

        problems.forEach((problem, idx) => {
            // Determine color based on category
            let color = '#6c757d'; // default gray
            let urgencyLevel = 'Medium';
            
            if (problem.category === 'Health') {
                color = '#dc3545'; // red
                urgencyLevel = 'High';
            } else if (problem.category === 'Waste') {
                color = '#fd7e14'; // orange
            } else if (problem.category === 'Water') {
                color = '#0d6efd'; // blue
            } else if (problem.category === 'Road') {
                color = '#6c757d'; // gray
            }

            // Add marker with popup
            const loc = locations[idx % locations.length];
            const urgencyIcon = problem.urgency === 'High' ? '<span class="pulse-dot inline-block w-3 h-3 rounded-full bg-red-500 mr-2"></span>' : '';
            
            const popupContent = `
                <div style="min-width: 200px;">
                    <h3 style="font-weight: bold; margin-bottom: 8px; color: #333;">${urgencyIcon}${problem.title || 'Untitled'}</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 8px;">${(problem.description || '').substring(0, 100)}...</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                        <span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${problem.category || 'Other'}</span>
                        <span style="background: ${problem.urgency === 'High' ? '#dc3545' : '#ffc107'}; color: ${problem.urgency === 'High' ? 'white' : 'black'}; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${problem.urgency || 'Medium'}</span>
                    </div>
                    <p style="font-size: 11px; color: #888;"><i class="fas fa-map-marker-alt"></i> ${problem.location || 'Location not specified'}</p>
                    <p style="font-size: 11px; color: #888;"><i class="fas fa-thumbs-up"></i> ${problem.upvotes || 0} upvotes</p>
                    <a href="problem_detail.php?id=${problem.id}" style="display: inline-block; margin-top: 8px; padding: 4px 12px; background: #fd7e14; color: white; border-radius: 4px; text-decoration: none; font-size: 12px;">View Details</a>
                </div>
            `;

            const marker = L.circleMarker([loc.lat, loc.lng], {
                radius: problem.upvotes > 10 ? 15 : (problem.upvotes > 5 ? 12 : 10),
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(popupContent);
            
            // Add tooltip
            marker.bindTooltip(`${problem.title} (${problem.upvotes || 0} upvotes)`, {
                permanent: false,
                direction: 'top'
            });
        });

        // Add legend
        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'bg-white p-4 rounded-lg shadow-lg');
            div.innerHTML = `
                <div style="font-weight: bold; margin-bottom: 8px; font-size: 14px;">Problem Categories</div>
                <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px;">
                    <div style="display: flex; align-items: center;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #dc3545; margin-right: 8px;"></span>Health (High Urgency)</div>
                    <div style="display: flex; align-items: center;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #fd7e14; margin-right: 8px;"></span>Waste Management</div>
                    <div style="display: flex; align-items: center;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #0d6efd; margin-right: 8px;"></span>Water Supply</div>
                    <div style="display: flex; align-items: center;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #6c757d; margin-right: 8px;"></span>Road Safety</div>
                    <div style="display: flex; align-items: center;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #6c757d; margin-right: 8px; opacity: 0.5;"></span>Other</div>
                </div>
            `;
            return div;
        };
        legend.addTo(map);

        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    </script>
</body>
</html>