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



   



        <div class="flex flex-col gap-4">



            <!-- Past History -->

            <a href="past_histroy.php?cat=<?php echo $cat_id; ?>"

                class="px-4 py-3 bg-[#7DBE76] text-white font-semibold rounded-xl text-center">

                📘 View Past History

            </a>



            <!-- Start Practice -->

          <!-- Always show MCQ Practice -->
    <a href="start_test.php?cat=<?php echo $cat_id; ?>"
       class="px-6 py-4 bg-[#7DBE76] hover:bg-[#6aae65] text-white font-bold rounded-xl text-center shadow-lg transition transform hover:-translate-y-1">
        📝 Start MCQ Practice (20 Questions)
    </a>

    <!-- Show Pattern Practice ONLY if category type is 'coding' -->
    <?php if ($cat['type'] == 'coding'): ?>
    <a href="select_pattern_language.php?cat=<?php echo $cat_id; ?>"
       class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-xl text-center shadow-lg transition transform hover:-translate-y-1 flex items-center justify-center gap-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
        </svg>
        ⭐ Start Pattern Coding Practice
    </a>
    <?php endif; ?>

</div>



</body>

</html>