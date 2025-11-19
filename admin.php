<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit;
}

echo "<h1>Welcome Admin</h1>";
?>
<a href="logout.php">Logout</a>
