<?php
session_start();
include 'db.php';

// If already logged in → redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($query);

    if ($user) {

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;

        } else {
            echo "Wrong Password!";
        }

    } else {
        echo "User Not Found!";
    }
}
?>