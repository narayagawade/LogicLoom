<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit;
}
$username = "Admin"; // replace with DB username if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - LogicLoom</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] min-h-screen flex">

  <!-- ========= Sidebar ========= -->
  <aside class="w-64 bg-white/30 backdrop-blur-xl shadow-xl border-r border-white/40 p-6 hidden md:block">
    <h1 class="text-2xl font-bold text-gray-800 mb-8">LogicLoom</h1>

    <nav class="space-y-4">
      <a href="admin_dashboard.php" class="block px-4 py-2 rounded-xl bg-white/40 text-gray-800 font-medium">
        Dashboard
      </a>

      <a href="add_card.php" class="block px-4 py-2 rounded-xl hover:bg-white/40 text-gray-700">
        Add Category Card
      </a>

      <a href="manage_questions.php" class="block px-4 py-2 rounded-xl hover:bg-white/40 text-gray-700">
        Manage Questions
      </a>
    </nav>

    <a href="Report_issue.pdf" class="block mt-6 px-4 py-2 rounded-xl hover:bg-white/40 text-gray-700">
      Report Issue
</a>
</nav>

    <div class="absolute bottom-6 left-6">
      <a href="logout.php" class="px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-xl">
        Logout
      </a>
    </div>
  </aside>

  <!-- ========= Main Content ========= -->
  <main class="flex-1 p-6">

    <!-- Top Bar -->
    <div class="w-full bg-white/40 backdrop-blur-xl shadow-md rounded-xl p-4 flex justify-between items-center border border-white/30">
      <h2 class="text-xl font-semibold text-gray-800">Admin Dashboard</h2>

      <div class="flex items-center gap-4">
        <span class="font-medium text-gray-700">👤 <?php echo $username; ?></span>

        <a href="logout.php" class="px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-xl">
          Logout
        </a>
      </div>
    </div>

    <!-- Cards Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">

      <!-- Category Card -->
      <div class="p-6 bg-white/30 backdrop-blur-xl rounded-2xl shadow-lg border border-white/40 hover:scale-[1.02] transition">
        <h3 class="text-lg font-semibold text-gray-800">Add Category</h3>
        <p class="text-gray-600 mt-2">Create a new practice category card for users.</p>
        <a href="admin_add_card.php" class="mt-4 inline-block px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-xl">
          Add Card
        </a>
      </div>

      <!-- Add Questions -->
      <div class="p-6 bg-white/30 backdrop-blur-xl rounded-2xl shadow-lg border border-white/40 hover:scale-[1.02] transition">
        <h3 class="text-lg font-semibold text-gray-800">Add Questions</h3>
        <p class="text-gray-600 mt-2">Insert new questions for each category.</p>
        <a href="add_question.php" class="mt-4 inline-block px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-xl">
          Add Question
        </a>
      </div>

      <!-- AI Auto Question Generator -->
      <div class="p-6 bg-white/30 backdrop-blur-xl rounded-2xl shadow-lg border border-white/40 hover:scale-[1.02] transition">
        <h3 class="text-lg font-semibold text-gray-800">AI Question Generator</h3>
        <p class="text-gray-600 mt-2">Let AI auto-generate questions based on category.</p>
        <a href="ai_generator.php" class="mt-4 inline-block px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-xl">
          Generate
        </a>
      </div>

    </div>
  </main>

</body>
</html>
