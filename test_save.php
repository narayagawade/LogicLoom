<?php
session_start();

if (!isset($_SESSION['test_questions'])) {
    header("Location: user_dashboard.php");
    exit;
}

$index = $_SESSION['test_index'];
$questions = $_SESSION['test_questions'];

$user_answer = $_POST['answer'];
$correct = $questions[$index]['correct_option'];

if ($user_answer == $correct) {
    $_SESSION['correct']++;
}

$_SESSION['test_index']++;

header("Location: test_question.php");
exit;
?>
