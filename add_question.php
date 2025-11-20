<?php
// ============================
// DATABASE CONNECTION
// ============================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "logicloom";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ============================
// FETCH CATEGORIES FOR DROPDOWN
// ============================
$cat_sql = "SELECT * FROM categories";
$categories = $conn->query($cat_sql);

// ============================
// INSERT QUESTION LOGIC
// ============================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_id = $_POST['category'];
    $question = $_POST['question'];
    $pattern_code = isset($_POST['pattern_code']) ? $_POST['pattern_code'] : NULL;

    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $answer = $_POST['correct_option'];  // Should be 1/2/3/4

    // FINAL SQL QUERY
    $sql = "INSERT INTO questions 
            (category_id, question, pattern_code, option1, option2, option3, option4, answer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi", $category_id, $question, $pattern_code, $option1, $option2, $option3, $option4, $answer);

    if ($stmt->execute()) {
        echo "<script>alert('Question Added Successfully');</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Question</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        function checkCategory() {
            const selText = document.getElementById("categorySelect").selectedOptions[0].text.toLowerCase();
            const patternBox = document.getElementById("patternBox");

            if (selText.includes("pattern")) {
                patternBox.classList.remove("hidden");
            } else {
                patternBox.classList.add("hidden");
            }
        }
    </script>
</head>

<body class="bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] min-h-screen p-6">

    <div class="max-w-3xl mx-auto bg-white p-7 rounded-2xl shadow-xl">
        <h1 class="text-2xl font-bold mb-5 text-gray-700">Add New Question</h1>

        <form method="POST" class="space-y-4">

            <!-- Category -->
            <div>
                <label class="font-semibold">Select Category</label>
                <select name="category" id="categorySelect" onchange="checkCategory()" 
                        required class="w-full mt-2 p-3 border rounded-lg">

                    <option value="">Choose…</option>

                    <?php while ($c = $categories->fetch_assoc()) { ?>
                        <option value="<?= $c['id']; ?>"><?= $c['name']; ?></option>
                    <?php } ?>

                </select>
            </div>

            <!-- Question -->
            <div>
                <label class="font-semibold">Question</label>
                <textarea name="question" required
                    class="w-full mt-2 p-3 border rounded-lg h-24"></textarea>
            </div>

            <!-- Pattern Code Box -->
            <div id="patternBox" class="hidden">
                <label class="font-semibold">Pattern Code / Diagram</label>
                <textarea name="pattern_code"
                    class="w-full mt-2 p-3 border rounded-lg h-32 font-mono"></textarea>
            </div>

            <!-- OPTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input name="option1" placeholder="Option 1" required class="p-3 border rounded-lg">
                <input name="option2" placeholder="Option 2" required class="p-3 border rounded-lg">
                <input name="option3" placeholder="Option 3" required class="p-3 border rounded-lg">
                <input name="option4" placeholder="Option 4" required class="p-3 border rounded-lg">
            </div>

            <!-- Correct Option -->
            <div>
                <label class="font-semibold">Correct Option</label>
                <select name="correct_option" required class="w-full mt-2 p-3 border rounded-lg">
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <!-- Submit -->
            <button class="w-full bg-[#9cd48b] hover:bg-[#8cc07e] p-3 text-white rounded-lg font-semibold">
                Add Question
            </button>

        </form>
    </div>
</body>
</html>
