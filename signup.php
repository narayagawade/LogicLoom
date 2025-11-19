<?php
include 'db.php';

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

    $query = "INSERT INTO users (full_name, email, phone, password) 
              VALUES ('$full_name', '$email', '$phone', '$password')";
    
    if (mysqli_query($conn, $query)) {
        echo "Signup Successful!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
