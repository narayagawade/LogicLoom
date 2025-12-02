<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cat_id = $_GET['cat'];

// Fetch category name
$cat = $conn->query("SELECT name FROM categories WHERE id = $cat_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Past History</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#8FCC88] min-h-screen p-10">

    <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-lg">

        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Past History – <?php echo $cat['name']; ?>
        </h1>

        <?php
        $q = $conn->query("
            SELECT * FROM user_scores 
            WHERE user_id = $user_id AND category_id = $cat_id
            ORDER BY created_at DESC
        ");

        if ($q->num_rows == 0): ?>
            <p class="text-gray-700">No past history found.</p>

        <?php else: ?>
            <table class="w-full text-left mt-4">
                <tr class="bg-gray-200">
                    <th class="p-3">Date</th>
                    <th class="p-3">Score</th>
                    <th class="p-3">Correct</th>
                </tr>

                <?php while ($row = $q->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="p-3"><?php echo $row['created_at']; ?></td>
                    <td class="p-3 font-semibold"><?php echo $row['score']; ?>%</td>
                    <td class="p-3"><?php echo $row['correct_answers']; ?>/<?php echo $row['total_questions']; ?></td>
                </tr>
                <?php endwhile; ?>

            </table>
        <?php endif; ?>

    </div>

</body>
</html>
