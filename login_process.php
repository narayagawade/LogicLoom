<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        echo "User Not Found!";
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        echo "Wrong Password!";
        exit;
    }

    // SUCCESS → store session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] == "admin") {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}
?>
