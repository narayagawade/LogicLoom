<?php
session_start();

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.html");
    exit;
}

echo "Welcome Admin!";
?>
<a href="logout.php">Logout</a>
