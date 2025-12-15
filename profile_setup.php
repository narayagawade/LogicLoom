<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---------------------------
   HANDLE FORM SUBMISSION ONLY
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nickname = trim($_POST['nickname'] ?? '');

    if ($nickname === '') {
        echo "Nickname is required";
        exit;
    }

    $qualification = $_POST['qualification'] ?? null;
    $pronouns      = $_POST['pronouns'] ?? null;
    $company       = $_POST['company'] ?? null;
    $alt_email     = $_POST['alt_email'] ?? null;
    $address       = $_POST['address'] ?? null;
    $bio           = $_POST['bio'] ?? null;

    /* Avatar upload */
    $avatarPath = null;
    if (!empty($_FILES['avatar']['name'])) {
        $dir = "uploads/avatars/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $avatarPath = $dir . "avatar_" . $user_id . "_" . time() . ".jpg";
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
    }

    /* Insert profile */
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

    if ($stmt->execute()) {
        header("Location: user_dashboard.php");
        exit;
    } else {
        echo "DB Error: " . $stmt->error;
        exit;
    }
}
?>

<!-- =====================
     PROFILE SETUP FORM
===================== -->
<!DOCTYPE html>
<html>
<head>
    <title>Profile Setup</title>
</head>
<body>

<h2>Complete Your Profile</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="nickname" placeholder="Nickname" required><br><br>

    <input type="text" name="qualification" placeholder="Qualification"><br><br>
    <input type="text" name="pronouns" placeholder="Pronouns"><br><br>
    <input type="text" name="company" placeholder="Company"><br><br>
    <input type="email" name="alt_email" placeholder="Alternate Email"><br><br>
    <textarea name="address" placeholder="Address"></textarea><br><br>
    <textarea name="bio" placeholder="Bio"></textarea><br><br>

    <input type="file" name="avatar"><br><br>

    <button type="submit">Save & Continue</button>
</form>

</body>
</html>
