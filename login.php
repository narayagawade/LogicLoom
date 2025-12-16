<?php
session_start();
include 'db.php';

// ---- ADD THE FUNCTION HERE ---- //
function profile_exists($conn, $user_id) {
    $sql = "SELECT id FROM user_profiles WHERE user_id = $user_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        // optional: handle query error
        return false;
    }
    return mysqli_num_rows($res) > 0;
}
// -------------------------------- //

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
            
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
                exit;
            }
            
            // 🔥 PROFILE CHECK
            $check = mysqli_query($conn, "SELECT id FROM user_profiles WHERE user_id = {$user['id']}");
            
            if (mysqli_num_rows($check) == 0) {
                header("Location: profile_setup.php");
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