<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];
$cat_id = $_SESSION['cat_id'];
$correct = $_SESSION['correct'];

$scorePercent = ($correct / 20) * 100;

// save score
$stmt = $conn->prepare("
    INSERT INTO user_scores (user_id, category_id, score, total_questions, correct_answers)
    VALUES (?, ?, ?, 20, ?)
");
$stmt->bind_param("iiii", $user_id, $cat_id, $scorePercent, $correct);
$stmt->execute();
$stmt->close();

// cleanup
unset($_SESSION['test_questions']);
unset($_SESSION['test_index']);
unset($_SESSION['correct']);
unset($_SESSION['cat_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#7DBE76] min-h-screen flex justify-center items-center">

    <div class="bg-white p-8 rounded-2xl w-full max-w-md text-center shadow-xl">

        <h1 class="text-3xl font-bold text-gray-800">Test Completed 🎉</h1>

        <p class="mt-4 text-gray-700 text-lg">
            Your Score:
        </p>

        <div class="text-5xl font-bold text-[#5AA053] mt-2">
            <?php echo round($scorePercent); ?>%
        </div>

        <p class="text-gray-600 mt-3">
            Correct Answers: <?php echo $correct; ?>/20
        </p>

        <a href="user_dashboard.php" class="mt-6 block bg-[#6EAF66] text-white py-3 rounded-xl">
            Back to Dashboard
        </a>

    </div>

</body>
</html>
