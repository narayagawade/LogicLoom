<?php
$conn = new mysqli("localhost", "root", "", "logicloom");

// Get question
$id = $_GET['id'];
$q = $conn->query("SELECT * FROM pattern_questions WHERE id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Solve Pattern</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">

<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-6">

    <h2 class="text-2xl font-bold mb-4"><?= $q['question'] ?></h2>

    <h3 class="font-semibold text-gray-700 mb-2">Expected Output:</h3>
    <pre class="bg-gray-200 p-3 rounded-lg font-mono mb-4"><?= $q['expected_output'] ?></pre>

    <form action="validate_pattern.php" method="POST" class="space-y-3">
        <input type="hidden" name="question_id" value="<?= $q['id'] ?>">

        <label class="font-semibold">Choose Language</label>
        <select name="language" class="p-3 border rounded-lg w-full">
            <option value="python">Python</option>
            <option value="cpp">C++</option>
            <option value="c">C</option>
            <option value="java">Java</option>
        </select>

        <label class="font-semibold">Write Your Code</label>
        <textarea name="user_code" required
            class="w-full p-3 border rounded-lg h-48 font-mono"></textarea>

        <button class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg">
            Submit
        </button>
    </form>

</div>

</body>
</html>
