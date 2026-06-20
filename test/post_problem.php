<?php
/**
 * CivicLedger - Post a Problem (Guest-Friendly with Voice Input)
 * Supports both logged-in users and anonymous guest reporting
 */
require_once 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = $isLoggedIn ? getCurrentUser() : null;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $voiceNote = trim($_POST['voice_note'] ?? '');
    
    // For anonymous users
    $reporterName = trim($_POST['reporter_name'] ?? '');
    $reporterPhone = trim($_POST['reporter_phone'] ?? '');
    $isAnonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] == '1';
    
    // Validation
    if ($isLoggedIn) {
        if (empty($title)) $errors[] = "Title is required";
        if (empty($description)) $errors[] = "Description is required";
        $userId = $user['id'];
        $anonName = null;
        $anonPhone = null;
    } else {
        // Guest validation
        if (empty($title)) $errors[] = "Title is required";
        if (empty($description)) $errors[] = "Description is required";
        if (!$isAnonymous) {
            if (empty($reporterName)) $errors[] = "Your name is required";
            if (empty($reporterPhone)) $errors[] = "Your phone number is required";
            elseif (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $reporterPhone)) {
                $errors[] = "Please enter a valid phone number";
            }
        }
        $userId = null;
        $anonName = $isAnonymous ? 'Anonymous Citizen' : $reporterName;
        $anonPhone = $isAnonymous ? 'N/A' : $reporterPhone;
    }
    
    // Impact metrics
    $impactMetric = trim($_POST['impact_metric'] ?? '');
    
    if (empty($errors)) {
        $classification = aiClassifyProblem($title, $description);
        $category = $_POST['category'] ?? $classification['category'];
        $urgency = $_POST['urgency'] ?? $classification['urgency'];
        
        $photo = uploadFile('photo', 'uploads/');
        
        global $conn;
        
        // For anonymous users, create a temporary user or insert directly
        if (!$isLoggedIn) {
            // Check if anonymous user record exists or create one
            $anonEmail = 'anon_' . md5($anonPhone . time()) . '@guest.elite4.com';
            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmtCheck->bind_param("s", $anonEmail);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            
            if ($resultCheck->num_rows > 0) {
                $anonUser = $resultCheck->fetch_assoc();
                $userId = $anonUser['id'];
            } else {
                // Create temporary anonymous user
                $hashedPassword = password_hash('guest_' . time(), PASSWORD_DEFAULT);
                $stmtCreate = $conn->prepare("INSERT INTO users (name, email, phone, password, role, bio) VALUES (?, ?, ?, ?, 'citizen', 'Anonymous guest reporter')");
                $stmtCreate->bind_param("ssss", $anonName, $anonEmail, $anonPhone, $hashedPassword);
                $stmtCreate->execute();
                $userId = $conn->insert_id;
            }
        }
        
        // Insert the problem
        $stmt = $conn->prepare("INSERT INTO problems (user_id, title, description, location, photo, voice_note, category, urgency, local_impact_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $errors[] = "Database error. Please try again.";
        } else {
            $stmt->bind_param("issssssss", $userId, $title, $description, $location, $photo, $voiceNote, $category, $urgency, $impactMetric);
            
            if ($stmt->execute()) {
                $success = true;
                $problemId = $conn->insert_id;
            } else {
                $errors[] = "Failed to post problem. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Problem - CivicLedger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = { 
            theme: { 
                extend: { 
                    colors: { 
                        civic: { 600: '#059669' },
                        civic: { 500: '#059669', 600: '#047857' }
                    } 
                } 
            } 
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .recording-pulse { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-50 min-h-screen">

    <!-- Header -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-hands-helping text-white text-lg"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">CivicLedger <span class="text-green-600">Nepal</span></span>
                    <span class="hidden md:inline-block text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Civic Reporting</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="public_heatmap.php" class="text-gray-600 hover:text-green-600 font-medium text-sm">
                        <i class="fas fa-map mr-1"></i> View Map
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a href="citizen_dashboard.php" class="text-gray-600 hover:text-green-600 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-green-600 hover:text-green-700 font-medium text-sm">
                            <i class="fas fa-sign-in-alt mr-1"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-8">
        
        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 px-6 py-4 rounded-xl mb-6 text-center">
            <i class="fas fa-check-circle text-2xl mb-2"></i>
            <p class="font-bold">Problem Reported Successfully!</p>
            <p class="text-sm">Your civic concern has been submitted. The community will now see and upvote it.</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="public_heatmap.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-map mr-1"></i> View on Map
                </a>
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Form Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
            
            <!-- Civic Banner -->
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl p-6 mb-8 text-white text-center">
                <i class="fas fa-shield-alt text-4xl mb-3 opacity-90"></i>
                <h1 class="text-2xl font-bold mb-1">Report a Civic Problem</h1>
                <p class="text-green-100 text-sm">Your report is public, trackable, and helps solve community issues</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-6">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Guest Notice -->
            <?php if (!$isLoggedIn): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                    <div>
                        <p class="text-blue-800 font-medium text-sm">Anonymous Reporting Available</p>
                        <p class="text-blue-600 text-xs mt-1">No email required! You can report problems with just your name and phone. Your contact info is only used for follow-up and won't be displayed publicly.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Reporter Info (for guests) -->
                <?php if (!$isLoggedIn): ?>
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4"><i class="fas fa-user-circle mr-2 text-green-500"></i>Your Information</h3>
                    
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="anonymous" name="is_anonymous" value="1" class="w-5 h-5 text-green-600 rounded focus:ring-green-500" onchange="toggleAnonymous()">
                        <label for="anonymous" class="ml-2 text-gray-700 font-medium">Report anonymously (no contact info)</label>
                    </div>
                    
                    <div id="contactFields" class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" name="reporter_name" id="reporterName" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                placeholder="Your full name" value="<?php echo e($_POST['reporter_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Phone Number *</label>
                            <input type="tel" name="reporter_phone" id="reporterPhone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                placeholder="+977-98XXXXXXXX" value="<?php echo e($_POST['reporter_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Problem Details -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2 text-orange-500"></i>Problem Title *
                    </label>
                    <input type="text" name="title" id="title" required value="<?php echo e($_POST['title'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                        placeholder="e.g., Contaminated water supply in Baneshwor area">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-2 text-blue-500"></i>Description *
                    </label>
                    <textarea name="description" id="description" required rows="5"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                        placeholder="Describe the problem in detail. When did it start? How many people are affected? What have you tried?"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    
                    <div class="mt-3 flex items-center space-x-4">
                        <button type="button" id="voiceBtn" onclick="toggleVoice()" 
                            class="flex items-center bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg text-sm font-medium transition-all border border-green-300">
                            <i class="fas fa-microphone mr-2"></i>Voice Input
                        </button>
                        <button type="button" id="mediaRecorderBtn" onclick="toggleMediaRecorder()"
                            class="flex items-center bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-medium transition-all border border-red-300">
                            <i class="fas fa-record-vinyl mr-2"></i>Record Audio Note
                        </button>
                        <span id="voiceStatus" class="text-sm text-gray-500"></span>
                    </div>
                    <div id="mediaRecorderUI" class="hidden mt-3 bg-red-50 p-4 rounded-lg border border-red-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span id="recordingIndicator" class="w-3 h-3 bg-red-500 rounded-full mr-3 recording-pulse"></span>
                                <span id="recordingTime" class="text-red-700 font-mono font-medium">00:00</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="stopMediaRecording()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                    <i class="fas fa-stop mr-1"></i> Stop
                                </button>
                            </div>
                        </div>
                        <audio id="audioPreview" controls class="hidden w-full mt-3"></audio>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>Location
                        </label>
                        <input type="text" name="location" value="<?php echo e($_POST['location'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                            placeholder="e.g., Baneshwor, Kathmandu">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-users mr-2 text-blue-500"></i>Estimated People Affected
                        </label>
                        <select name="impact_metric" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            <option value="">Select impact scale</option>
                            <option value="10-50 households">10-50 households (~50-250 people)</option>
                            <option value="50-200 households">50-200 households (~250-1000 people)</option>
                            <option value="200-500 households">200-500 households (~1000-2500 people)</option>
                            <option value="500-1000 households">500-1000 households (~2500-5000 people)</option>
                            <option value="1000+ households">1000+ households (5000+ people)</option>
                            <option value="entire neighborhood">Entire neighborhood</option>
                            <option value="entire ward">Entire ward</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-camera mr-2 text-purple-500"></i>Photo Evidence
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-green-400 transition-all bg-gray-50">
                        <input type="file" name="photo" id="photo" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <label for="photo" class="cursor-pointer">
                            <div id="previewContainer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500 font-medium">Click to upload a photo</p>
                                <p class="text-gray-400 text-sm">JPG, PNG, GIF - Max 5MB</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- AI Classification -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-robot mr-2 text-green-600"></i>Problem Classification
                        <span class="text-xs font-normal text-gray-500 ml-2">(Auto-detected, adjust if needed)</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Category</label>
                            <select name="category" id="categorySelect" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 bg-white">
                                <option value="Waste" <?php echo ($_POST['category'] ?? '') === 'Waste' ? 'selected' : ''; ?>>🗑️ Waste Management</option>
                                <option value="Road" <?php echo ($_POST['category'] ?? '') === 'Road' ? 'selected' : ''; ?>>🛣️ Road & Transport</option>
                                <option value="Health" <?php echo ($_POST['category'] ?? '') === 'Health' ? 'selected' : ''; ?>>🏥 Health & Sanitation</option>
                                <option value="Water" <?php echo ($_POST['category'] ?? '') === 'Water' ? 'selected' : ''; ?>>💧 Water & Irrigation</option>
                                <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>📋 Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Urgency Level</label>
                            <select name="urgency" id="urgencySelect" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 bg-white">
                                <option value="High" <?php echo ($_POST['urgency'] ?? '') === 'High' ? 'selected' : ''; ?>>🔴 High - Immediate action needed</option>
                                <option value="Medium" <?php echo ($_POST['urgency'] ?? '') === 'Medium' ? 'selected' : ''; ?>>🟡 Medium - Address soon</option>
                                <option value="Low" <?php echo ($_POST['urgency'] ?? '') === 'Low' ? 'selected' : ''; ?>>🟢 Low - Can wait</option>
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="voice_note" id="voiceNote" value="">

                <!-- Submit -->
                <div class="flex flex-col md:flex-row gap-4 pt-4">
                    <a href="<?php echo $isLoggedIn ? 'citizen_dashboard.php' : 'index.php'; ?>" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-4 rounded-xl text-center transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Report
                    </button>
                </div>
            </form>

            <!-- Trust Badge -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <div class="inline-flex items-center bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span>Your report is anonymous and publicly visible on the Transparency Ledger</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400"><i class="fas fa-heart text-red-500 mr-1"></i> Built for Nepal's Civic Innovation</p>
            <p class="text-gray-500 text-sm mt-2">CivicLedger - The Problem Solver</p>
        </div>
    </footer>

    <script>
        // Toggle anonymous mode
        function toggleAnonymous() {
            const isAnon = document.getElementById('anonymous').checked;
            const fields = document.getElementById('contactFields');
            fields.style.display = isAnon ? 'none' : 'grid';
        }

        // Initialize anonymous toggle
        document.addEventListener('DOMContentLoaded', toggleAnonymous);

        // Voice Input (Web Speech API)
        let recognition, isRecording = false;

        function toggleVoice() {
            if (!('webkitSpeechRecognition' in window)) {
                alert('Voice input not supported. Please use Chrome browser.');
                return;
            }
            if (isRecording) {
                recognition?.stop();
                return;
            }
            recognition = new webkitSpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.onstart = function() {
                isRecording = true;
                document.getElementById('voiceBtn').classList.remove('bg-green-100', 'text-green-700');
                document.getElementById('voiceBtn').classList.add('bg-red-100', 'text-red-700', 'recording-pulse');
                document.getElementById('voiceBtn').innerHTML = '<i class="fas fa-stop mr-2"></i>Stop';
                document.getElementById('voiceStatus').textContent = 'Listening...';
            };
            recognition.onresult = function(event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                const desc = document.getElementById('description');
                desc.value = desc.value ? desc.value + ' ' + transcript : transcript;
            };
            recognition.onerror = function() { stopVoice(); };
            recognition.onend = function() { stopVoice(); };
            recognition.start();
        }

        function stopVoice() {
            isRecording = false;
            document.getElementById('voiceBtn').classList.remove('bg-red-100', 'text-red-700', 'recording-pulse');
            document.getElementById('voiceBtn').classList.add('bg-green-100', 'text-green-700');
            document.getElementById('voiceBtn').innerHTML = '<i class="fas fa-microphone mr-2"></i>Voice Input';
            document.getElementById('voiceStatus').textContent = 'Recording stopped';
        }

        // MediaRecorder for audio notes
        let mediaRecorder, audioChunks = [], recordingStartTime, recordingInterval;

        function toggleMediaRecorder() {
            const ui = document.getElementById('mediaRecorderUI');
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                stopMediaRecording();
            } else {
                startMediaRecording();
            }
        }

        async function startMediaRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = function(e) {
                    audioChunks.push(e.data);
                };

                mediaRecorder.onstop = function() {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    document.getElementById('audioPreview').src = audioUrl;
                    document.getElementById('audioPreview').classList.remove('hidden');
                    
                    // Convert to base64 for storage
                    const reader = new FileReader();
                    reader.onloadend = function() {
                        document.getElementById('voiceNote').value = reader.result;
                    };
                    reader.readAsDataURL(audioBlob);
                };

                mediaRecorder.start();
                recordingStartTime = Date.now();
                document.getElementById('mediaRecorderUI').classList.remove('hidden');
                document.getElementById('mediaRecorderBtn').classList.remove('bg-red-100', 'text-red-700');
                document.getElementById('mediaRecorderBtn').classList.add('bg-green-100', 'text-green-700');
                document.getElementById('mediaRecorderBtn').innerHTML = '<i class="fas fa-stop mr-2"></i>Stop Recording';

                recordingInterval = setInterval(updateRecordingTime, 1000);
            } catch (err) {
                alert('Microphone access denied or not available.');
            }
        }

        function stopMediaRecording() {
            if (mediaRecorder) {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
                clearInterval(recordingInterval);
                document.getElementById('mediaRecorderBtn').classList.remove('bg-green-100', 'text-green-700');
                document.getElementById('mediaRecorderBtn').classList.add('bg-red-100', 'text-red-700');
                document.getElementById('mediaRecorderBtn').innerHTML = '<i class="fas fa-record-vinyl mr-2"></i>Record Audio Note';
            }
        }

        function updateRecordingTime() {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('recordingTime').textContent = `${minutes}:${seconds}`;
        }

        // Image preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewContainer').innerHTML = `
                        <img src="${e.target.result}" class="max-h-40 mx-auto rounded-lg shadow">
                        <button type="button" onclick="resetImage()" class="mt-2 text-sm text-red-600 hover:text-red-700">
                            <i class="fas fa-trash mr-1"></i>Remove
                        </button>
                    `;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetImage() {
            document.getElementById('photo').value = '';
            document.getElementById('previewContainer').innerHTML = `
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-500 font-medium">Click to upload a photo</p>
                <p class="text-gray-400 text-sm">JPG, PNG, GIF - Max 5MB</p>
            `;
        }

        // Auto-classification based on text
        let classifyTimeout;
        function autoClassify() {
            clearTimeout(classifyTimeout);
            classifyTimeout = setTimeout(function() {
                const text = (document.getElementById('title').value + ' ' + document.getElementById('description').value).toLowerCase();
                if (text.length > 20) {
                    // Category detection
                    if (text.includes('garbage') || text.includes('waste') || text.includes('trash') || text.includes('dustbin')) {
                        document.getElementById('categorySelect').value = 'Waste';
                    } else if (text.includes('road') || text.includes('pothole') || text.includes('street') || text.includes('path')) {
                        document.getElementById('categorySelect').value = 'Road';
                    } else if (text.includes('water') || text.includes('river') || text.includes('contaminated') || text.includes('tap')) {
                        document.getElementById('categorySelect').value = 'Water';
                    } else if (text.includes('health') || text.includes('hospital') || text.includes('sick') || text.includes('disease')) {
                        document.getElementById('categorySelect').value = 'Health';
                    }
                    
                    // Urgency detection
                    if (text.includes('urgent') || text.includes('emergency') || text.includes('danger') || text.includes('accident') || text.includes('death') || text.includes('died')) {
                        document.getElementById('urgencySelect').value = 'High';
                    } else if (text.includes('slow') || text.includes('eventually') || text.includes('when possible')) {
                        document.getElementById('urgencySelect').value = 'Low';
                    } else {
                        document.getElementById('urgencySelect').value = 'Medium';
                    }
                }
            }, 800);
        }

        document.getElementById('title').addEventListener('input', autoClassify);
        document.getElementById('description').addEventListener('input', autoClassify);
    </script>
</body>
</html>