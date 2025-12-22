<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cat_id = (int)($_GET['cat'] ?? 0);
$lang = $_GET['lang'] ?? 'python';

// Validate language
$valid_langs = ['c', 'cpp', 'python', 'java', 'javascript'];
if (!in_array($lang, $valid_langs)) {
    $lang = 'python';
}

// Fetch all pattern question IDs from the correct table: pattern_questions
$stmt = $conn->prepare("SELECT id FROM pattern_questions WHERE category_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $cat_id);
$stmt->execute();
$res = $stmt->get_result();

$question_ids = [];
while ($row = $res->fetch_assoc()) {
    $question_ids[] = (int)$row['id'];
}
$stmt->close();

if (empty($question_ids)) {
    // Friendly message if no questions
    echo "<div class='min-h-screen bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center p-6'>
            <div class='bg-white p-10 rounded-3xl shadow-2xl text-center max-w-md'>
                <p class='text-2xl font-bold text-red-600 mb-4'>No Pattern Questions Found</p>
                <p class='text-gray-700 mb-8'>This category doesn't have any pattern coding challenges yet.</p>
                <a href='category.php?id=$cat_id' class='px-8 py-4 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition'>
                    ← Back to Category
                </a>
            </div>
          </div>";
    exit;
}

// Initialize the pattern coding session
$_SESSION['pattern_category']      = $cat_id;
$_SESSION['pattern_lang']          = $lang;
$_SESSION['pattern_questions']     = $question_ids;        // Array of question IDs
$_SESSION['pattern_current_index'] = 0;                    // Start from first question

// Redirect to the main coding page
header("Location: pattern_coding.php");
exit;
?>