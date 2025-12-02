
<?php
session_start();

if (!isset($_SESSION['test_questions'])) {
    header("Location: user_dashboard.php");
    exit;
}

$index = $_SESSION['test_index'];
$questions = $_SESSION['test_questions'];

if ($index >= count($questions)) {
    header("Location: test_result.php");
    exit;
}

$q = $questions[$index];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#A7E29E] min-h-screen flex justify-center items-center">

<div class="bg-white p-8 rounded-2xl w-full max-w-xl shadow-lg">

    <h2 class="text-xl font-bold mb-4">Question <?php echo $index+1; ?>/20</h2>

    <p class="text-gray-900 font-semibold mb-6"><?php echo $q['question']; ?></p>

    <form method="post" action="test_save.php">
        <?php foreach (['option1', 'option2', 'option3', 'option4'] as $opt): ?>
            <label class="block mb-3">
                <input type="radio" name="answer" value="<?php echo $q[$opt]; ?>" required>
                <span class="ml-2"><?php echo $q[$opt]; ?></span>
            </label>
        <?php endforeach; ?>

        <button class="mt-6 w-full bg-[#5AA053] text-white py-3 rounded-xl">
            Next
        </button>
    </form>

</div>

</body>
</html>
