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
</head>
<body class="min-h-screen bg-gradient-to-br from-[#A7E29E] to-[#8FCC88]">

  <div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="user_dashboard.php" class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-white/40 p-2">
            <!-- small logo -->
            <img src="img/loginlamge.png" alt="logo" class="w-full h-full object-cover"/>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">LogicLoom</h1>
            <p class="text-sm text-gray-700">Practice & Improve</p>
          </div>
        </a>
      </div>

      <div class="flex items-center gap-4">
        <div class="text-right mr-3">
          <div class="font-semibold"><?php echo htmlspecialchars($profile['nickname'] ?: $user['full_name']); ?></div>
          <div class="text-xs text-gray-700"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>

        <a href="profile_setup.php" class="w-12 h-12 rounded-full overflow-hidden border-2 border-white/60">
          <img src="<?php echo htmlspecialchars($profile['avatar'] ?: 'img/loginlamge.png'); ?>" alt="avatar" class="w-full h-full object-cover"/>
        </a>
      </div>
    </div>

    <!-- Cards grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php while ($c = $cats->fetch_assoc()): ?>
        <div class="bg-white/80 rounded-2xl p-6 shadow-md transform transition hover:-translate-y-1">
          <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-lg bg-[#5AA053] flex items-center justify-center text-white font-bold text-lg">
              <?php echo strtoupper(substr($c['name'], 0, 2)); ?>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($c['name']); ?></h3>
              <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($c['description'] ?? 'Practice problems for ' . $c['name']); ?></p>
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <a href="category.php?id=<?php echo $c['id']; ?>" class="px-3 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-md">Open</a>
            <span class="text-sm text-gray-600"><?php
              // count questions per category
              $qid = (int)$c['id'];
              $res = $conn->query("SELECT COUNT(*) as cnt FROM questions WHERE category_id = $qid");
              $count = $res->fetch_assoc()['cnt'] ?? 0;
              echo $count . ' questions';
            ?></span>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

  </div>

</body>
</html>
