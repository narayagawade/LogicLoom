<?php
session_start();
include 'db.php';

function profile_exists($conn, $user_id) {
    $sql = "SELECT id FROM user_profiles WHERE user_id = $user_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    return mysqli_num_rows($res) > 0;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($result);

    if (!$user) { echo "User Not Found!"; exit; }
    if (!password_verify($password, $user['password'])) { echo "Wrong Password!"; exit; }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    header("Location: check_profile.php");
    exit;
}
?>
