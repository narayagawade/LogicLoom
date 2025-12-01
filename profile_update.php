<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// sanitize inputs
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : null;
$qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$pronouns = isset($_POST['pronouns']) ? trim($_POST['pronouns']) : null;
$company = isset($_POST['company']) ? trim($_POST['company']) : null;
$alt_email = isset($_POST['alt_email']) ? trim($_POST['alt_email']) : null;
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

// avatar handling
$avatar_path = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $allowed = ['image/jpeg','image/png','image/jpg','image/webp'];
    if (!in_array($file['type'], $allowed)) {
        die("Unsupported avatar type.");
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        die("Avatar too large (max 2MB).");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $dir = __DIR__ . '/uploads/avatars';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $newname = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $target = $dir . '/' . $newname;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        die("Failed to save avatar.");
    }
    // path to store in DB (web path)
    $avatar_path = 'uploads/avatars/' . $newname;
}

// Check if profile exists
$stmt = $conn->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$exists = $res->num_rows > 0;
$stmt->close();

if ($exists) {
    // update
    $sql = "UPDATE user_profiles SET nickname=?, qualification=?, address=?, pronouns=?, company=?, alt_email=?, bio=?, updated_at=NOW()";
    $params = [$nickname, $qualification, $address, $pronouns, $company, $alt_email, $bio];

    if ($avatar_path) {
        $sql .= ", avatar=?";
        $params[] = $avatar_path;
    }
    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;

    // build types string
    $types = str_repeat('s', count($params)-1) . 'i';
    $stmt = $conn->prepare($sql);

    // dynamic bind
    $bind_names[] = $types;
    for ($i=0; $i < count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $stmt->close();
} else {
    // insert
    $sql = "INSERT INTO user_profiles (user_id, nickname, qualification, address, pronouns, company, alt_email, bio, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssss", $user_id, $nickname, $qualification, $address, $pronouns, $company, $alt_email, $bio, $avatar_path);
    $stmt->execute();
    $stmt->close();
}

header("Location: user_dashboard.php");
exit;
