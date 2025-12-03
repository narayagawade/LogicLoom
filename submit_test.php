<?php
session_start();
include 'config.php';

$questions = $_SESSION['questions'];
$answers = $_SESSION['answers'];

$score = 0;

// Score calculation
foreach ($questions as $i => $q) {
    if (isset($answers[$i]) && $answers[$i] == $q['answer']) {
        $score++;
    }
}

// Save score in database
$user_id = $_SESSION['user_id'];

$stmt = $con->prepare("INSERT INTO user_scores (user_id, score) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $score);
$stmt->execute();

$_SESSION['final_score'] = $score;

header("Location: result.php");
exit();
?>
