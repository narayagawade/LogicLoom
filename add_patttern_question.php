<?php
// DB Connection
$conn = new mysqli("localhost", "root", "", "logicloom");
if ($conn->connect_error) { die("DB Error: " . $conn->connect_error); }

// Save Data When Submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $question = $_POST['question'];
    $expected_output = $_POST['expected_output'];
    $explanation = $_POST['explanation'];

    $query = "INSERT INTO pattern_questions (category_id, question, expected_output, explanation)
              VALUES ('$category', '$question', '$expected_output', '$explanation')";

    if ($conn->query($query)) {
        $msg = "Pattern Question Added Successfully!";
    } else {
        $msg = "Error: " . $conn->error;
    }
}

// Load categories
$categories = $conn->query("SELECT * FROM categories");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Pattern Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] min-h-screen p-6">

<div class="max-w-3xl mx-auto bg-white p-7 rounded-2xl shadow-xl">

    <h1 class="text-2xl font-bold mb-5 text-gray-700">Add Pattern Solve Question</h1>

    <?php if (!empty($msg)) echo "<p class='text-green-600 font-semibold mb-3'>$msg</p>"; ?>

    <form method="POST" class="space-y-4">

        <!-- Category -->
        <div>
            <label class="font-semibold">Category</label>
            <select name="category" required class="w-full mt-2 p-3 border rounded-lg">
                <?php while ($c = $categories->fetch_assoc()) { ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php } ?>
            </select>
        </div>

        <!-- Question -->
        <div>
            <label class="font-semibold">Question</label>
            <textarea name="question" required
                class="w-full mt-2 p-3 border rounded-lg h-24"></textarea>
        </div>

        <!-- Expected Output -->
        <div>
            <label class="font-semibold">Correct Pattern Output</label>
            <textarea name="expected_output" required
                class="w-full mt-2 p-3 border rounded-lg h-32 font-mono"></textarea>
        </div>

        <!-- Explanation -->
        <div>
            <label class="font-semibold">Explanation (optional)</label>
            <textarea name="explanation"
                class="w-full mt-2 p-3 border rounded-lg h-24"></textarea>
        </div>

        <!-- Submit -->
        <button class="w-full bg-[#9cd48b] hover:bg-[#8cc07e] p-3 text-white rounded-lg font-semibold">
            Add Pattern Question
        </button>

    </form>
</div>
</body>
</html>
