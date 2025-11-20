<?php
session_start();
include "db.php";

// Fetch card count
$cardCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
$cardCount = mysqli_fetch_assoc($cardCountResult)['total'];

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];

    // Auto Avatar — first letter
    $avatar = strtoupper($name[0]);

    // Insert into DB
    mysqli_query($conn, "INSERT INTO categories (name, avatar) VALUES ('$name', '$avatar')");

    header("Location: add_card.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Card</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: linear-gradient(135deg, #c9e9c1, #b8e0a6);
        }
    </style>
</head>

<body class="min-h-screen flex">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white/70 backdrop-blur-xl shadow-xl p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Admin Panel</h2>
        <ul class="space-y-4">

            <li>
                <a href="admin_dashboard.php" class="block px-4 py-2 rounded-xl bg-green-300/60 hover:bg-green-300/90 transition">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="add_card.php" class="block px-4 py-2 rounded-xl bg-green-500 text-white">
                    Add Cards
                </a>
            </li>

            <li>
                <a href="reports.php" class="block px-4 py-2 rounded-xl bg-green-300/60 hover:bg-green-300/90 transition">
                    Reports
                </a>
            </li>

            <li>
                <a href="logout.php" class="block px-4 py-2 rounded-xl bg-red-400 text-white">
                    Logout
                </a>
            </li>

        </ul>
    </aside>

    <!-- MAIN AREA -->
    <main class="flex-1 p-10">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800">Create New Category Card</h1>

            <!-- Card Count -->
            <div class="px-6 py-3 bg-white/70 backdrop-blur-xl rounded-xl shadow-lg">
                <p class="text-xl font-semibold text-gray-700">
                    Total Cards: <span class="text-green-600 font-bold"><?= $cardCount ?></span>
                </p>
            </div>
        </div>

        <!-- SUCCESS MESSAGE -->
        <?php if (isset($_GET['success'])): ?>
            <div class="p-4 mb-6 bg-green-200 text-green-800 rounded-xl">
                Card Created Successfully!
            </div>
        <?php endif; ?>

        <!-- FORM CARD -->
        <div class="bg-white/80 backdrop-blur-xl p-8 rounded-2xl shadow-xl w-full max-w-xl">

            <form method="POST">

                <label class="block mb-2 text-gray-800 font-medium">Card Name</label>
                <input type="text" name="name" required
                       class="w-full px-4 py-3 mb-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-400 outline-none">

                <button type="submit"
                        class="w-full py-3 text-white font-semibold rounded-xl bg-green-500 hover:bg-green-600 transition mb-3">
                    Add Card
                </button>

            </form>

            <!-- AI BUTTON -->
            <button
                class="w-full py-3 text-white font-semibold rounded-xl bg-blue-500 hover:bg-blue-600 transition">
                Generate Card with AI
            </button>

        </div>

    </main>

</body>
</html>
