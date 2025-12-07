<?php
include 'db.php';
session_start();

// If user already logged in → redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();// Auto-login after signup
    session_start();
    $_SESSION['user_id'] = mysqli_insert_id($conn);
    
    // After signup → go to profile setup
    header("Location: profile_setup.php");
    exit;
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email already exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "Email already registered!";
        exit;
    }

    $query = "INSERT INTO users (full_name, email, phone, password, role) 
              VALUES ('$full_name', '$email', '$phone', '$password', 'user')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: login.html");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
