<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user main data
$userStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Fetch profile details
$profileStmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profileStmt->bind_param("i", $user_id);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

$avatar = $profile['avatar'] ?? 'img/loginlamge.png';
$nickname = $profile['nickname'] ?? $user['full_name'];
$bio = $profile['bio'] ?? "No bio added";
$company = $profile['company'] ?? "No company";
$pronouns = $profile['pronouns'] ?? "N/A";
$location = $profile['address'] ?? "Unknown";
$altEmail = $profile['alt_email'] ?? "Not added";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - LogicLoom</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

<!-- TOP NAV -->
<div class="w-full px-6 py-4 bg-gray-800 flex justify-between items-center shadow-md">
    <div class="flex items-center gap-3 cursor-pointer" onclick="window.location='user_dashboard.php'">
        <img src="img/loginlamge.png" class="w-10 h-10 rounded-lg" />
        <h1 class="text-xl font-semibold">LogicLoom</h1>
    </div>

    <div class="relative">
        <img src="<?php echo $avatar; ?>" onclick="toggleDropdown()" class="w-11 h-11 rounded-full border-2 border-white cursor-pointer" />

        <!-- Dropdown -->
        <div id="dropdownMenu" class="hidden absolute right-0 mt-3 w-40 bg-white text-gray-900 rounded-lg shadow-lg py-2">
            <a href="user_profile.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Stars</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Settings</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-100">Logout</a>
        </div>
    </div>
</div>

<!-- PROFILE SECTION -->
<div class="max-w-6xl mx-auto mt-10 p-6">

    <!-- Avatar + Name -->
    <div class="flex items-center gap-6">
        <img src="<?php echo $avatar; ?>" class="w-40 h-40 rounded-full border-4 border-gray-700" />

        <div>
            <h2 class="text-3xl font-bold"><?php echo $nickname; ?> <span class="text-gray-400 text-lg">· <?php echo $pronouns; ?></span></h2>
            <p class="text-gray-400 text-base mt-1"><?php echo $bio; ?></p>

            <div class="flex gap-6 mt-4 text-gray-300">
                <div>📍 <?php echo $location; ?></div>
                <div>🏢 <?php echo $company; ?></div>
                <div>📧 <?php echo $altEmail; ?></div>
            </div>
        </div>
    </div>

    <!-- STATS SECTION -->
    <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Pinned Section -->
        <div class="bg-gray-800 p-6 rounded-2xl shadow-lg">
            <h3 class="text-xl font-semibold mb-4">Pinned</h3>

            <div class="bg-gray-700 p-4 rounded-xl mb-3">
                <h4 class="text-lg font-semibold">LogicLoom Score</h4>
                <p class="text-gray-300 text-sm mt-1">Your total score from completed categories</p>
                <div class="text-3xl font-bold mt-3 text-green-400">
                    <?php
                    $score = 0;
                    $s = $conn->query("SELECT SUM(score) AS total FROM user_scores WHERE user_id = $user_id");
                    if ($s && $row = $s->fetch_assoc()) { echo $row['total'] ?? 0; }
                    ?> pts
                </div>
            </div>

        </div>

        <!-- Contribution heatmap placeholder -->
        <div class="bg-gray-800 p-6 rounded-2xl shadow-lg">
            <h3 class="text-xl font-semibold mb-4">Contribution Activity</h3>
            <p class="text-gray-400 text-sm mb-4">Daily category completion history</p>

            <div class="grid grid-cols-30 gap-1">
                <?php
                // Render 1-year heatmap (placeholder squares)
                for ($i=0; $i<180; $i++): ?>
                    <div class="w-3 h-3 rounded-sm <?php echo rand(0,1) ? 'bg-green-600' : 'bg-gray-700'; ?>"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("hidden");
}
</script>

</body>
</html>
