<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// REQUIRED FIELD CHECK
if (empty($_POST['nickname'])) {
    die("Nickname is required");
}

$nickname = $_POST['nickname'];
$qualification = $_POST['qualification'] ?? null;
$pronouns = $_POST['pronouns'] ?? null;
$company = $_POST['company'] ?? null;
$alt_email = $_POST['alt_email'] ?? null;
$address = $_POST['address'] ?? null;
$bio = $_POST['bio'] ?? null;

// Avatar upload
$avatarPath = null;
if (!empty($_FILES['avatar']['name'])) {
    $dir = "uploads/avatars/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $avatarPath = $dir . "avatar_" . $user_id . "_" . time() . ".jpg";
    move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
}

// INSERT PROFILE
$stmt = $conn->prepare("
    INSERT INTO user_profiles
    (user_id, nickname, qualification, address, pronouns, company, alt_email, bio, avatar)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssss",
    $user_id,
    $nickname,
    $qualification,
    $address,
    $pronouns,
    $company,
    $alt_email,
    $bio,
    $avatarPath
);

$stmt->execute();
$stmt->close();

// GO TO DASHBOARD
header("Location: user_dashboard.php");
exit;
