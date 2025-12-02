<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
$user_id = $_SESSION['user_id'];

// fetch user info
$user = $conn->query("SELECT id, full_name, email FROM users WHERE id = $user_id")->fetch_assoc();

// fetch profile
$profileStmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profileStmt->bind_param("i", $user_id);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

// if no profile — redirect to profile setup
if (!$profile) {
    header("Location: profile_setup.php");
    exit;
}

// fetch categories/cards created by admin
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC");
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard - LogicLoom</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    .dropdown-enter {
      opacity: 0;
      transform: translateY(-8px);
    }
    .dropdown-active {
      opacity: 1;
      transform: translateY(0px);
      transition: all 0.2s ease-out;
    }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-[#A7E29E] to-[#8FCC88]">

<!-- Container -->
<div class="max-w-7xl mx-auto p-6">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between mb-6 relative">

        <!-- Left Logo -->
        <a href="user_dashboard.php" class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-white/40 p-2">
                <img src="img/loginlamge.png" class="w-full h-full object-cover" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">LogicLoom</h1>
                <p class="text-sm text-gray-700">Practice & Improve</p>
            </div>
        </a>

        <!-- Right Side Profile + Dropdown -->
        <div class="relative">
            <button id="avatarBtn" class="flex items-center gap-3 cursor-pointer">
                <div class="text-right mr-2">
                    <div class="font-semibold">
                        <?php echo htmlspecialchars($profile['nickname'] ?: $user['full_name']); ?>
                    </div>
                    <div class="text-xs text-gray-700">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>

                <!-- Avatar -->
                <img src="<?php echo htmlspecialchars($profile['avatar'] ?: 'img/loginlamge.png'); ?>"
                     class="w-12 h-12 rounded-full border-2 border-white/60 object-cover" />
            </button>

            <!-- DROPDOWN MENU -->
            <div id="dropdownMenu"
                 class="hidden absolute right-0 mt-3 w-48 bg-white shadow-lg rounded-xl py-2 z-50">

                <a href="profile_update.php"
                   class="block px-4 py-2 text-gray-700 hover:bg-[#A7E29E]">Profile</a>

                <a href="#"
                   class="block px-4 py-2 text-gray-700 hover:bg-[#A7E29E]">⭐ Starred</a>

                <a href="#"
                   class="block px-4 py-2 text-gray-700 hover:bg-[#A7E29E]">Settings</a>

                <a href="logout.php"
                   class="block px-4 py-2 text-gray-700 hover:bg-red-100">Logout</a>
            </div>
        </div>

    </div>

    <!-- Cards grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <?php while ($c = $cats->fetch_assoc()): ?>
            <div class="bg-white/80 rounded-2xl p-6 shadow-md transform transition hover:-translate-y-1">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-lg bg-[#5AA053] text-white font-bold flex items-center justify-center">
                        <?php echo strtoupper(substr($c['name'], 0, 2)); ?>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($c['name']); ?>
                        </h3>
                       
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <a href="category.php?id=<?php echo $c['id']; ?>"
                       class="px-3 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-md">
                        Open
                    </a>

                    <span class="text-sm text-gray-600">
                        <?php
                            $qid = (int)$c['id'];
                            $res = $conn->query("SELECT COUNT(*) AS cnt FROM questions WHERE category_id = $qid");
                            echo $res->fetch_assoc()['cnt'] . " questions";
                        ?>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

</div>

<script>
// Dropdown toggle
const avatarBtn = document.getElementById("avatarBtn");
const dropdownMenu = document.getElementById("dropdownMenu");

avatarBtn.addEventListener("click", () => {
    if (dropdownMenu.classList.contains("hidden")) {
        dropdownMenu.classList.remove("hidden");
        dropdownMenu.classList.add("dropdown-enter");

        setTimeout(() => {
            dropdownMenu.classList.add("dropdown-active");
            dropdownMenu.classList.remove("dropdown-enter");
        }, 10);

    } else {
        dropdownMenu.classList.add("hidden");
        dropdownMenu.classList.remove("dropdown-active");
    }
});

// Close when clicking outside
document.addEventListener("click", (e) => {
    if (!avatarBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.add("hidden");
        dropdownMenu.classList.remove("dropdown-active");
    }
});
</script>

</body>
</html>
