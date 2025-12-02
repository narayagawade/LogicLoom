<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cat_id = $_GET['id'];

// Fetch category info
$cat = $conn->query("SELECT * FROM categories WHERE id = $cat_id")->fetch_assoc();

if (!$cat) {
    echo "Category not found!";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $cat['name']; ?> - Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#A7E29E] min-h-screen p-10">

    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-lg">

        <h1 class="text-3xl font-bold text-gray-800 mb-4">
            <?php echo $cat['name']; ?>
        </h1>

        <p class="text-gray-700 mb-8">
            <?php echo $cat['description']; ?>
        </p>

        <div class="flex flex-col gap-4">

            <!-- Past History -->
            <a href="past_history.php?cat=<?php echo $cat_id; ?>"
                class="px-4 py-3 bg-[#7DBE76] text-white font-semibold rounded-xl text-center">
                📘 View Past History
            </a>

            <!-- Start Practice -->
            <a href="start_test.php?cat=<?php echo $cat_id; ?>"
                class="px-4 py-3 bg-[#5AA053] text-white font-semibold rounded-xl text-center">
                📝 Start Practice (20 Questions)
            </a>

        </div>
    </div>

</body>
</html>
