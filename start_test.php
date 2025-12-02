<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cat_id = $_GET['cat'];

// fetch 20 random MCQ questions
$q = $conn->query("
    SELECT * FROM questions 
    WHERE category_id = $cat_id AND is_pattern = 0
    ORDER BY RAND() LIMIT 20
");

$questions = [];
while ($row = $q->fetch_assoc()) $questions[] = $row;

// store in session for test progress
$_SESSION['test_questions'] = $questions;
$_SESSION['test_index'] = 0;
$_SESSION['correct'] = 0;
$_SESSION['cat_id'] = $cat_id;

header("Location: test_question.php");
exit;
?>
