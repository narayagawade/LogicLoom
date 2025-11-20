<?php
// admin_add_card.php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'db.php';

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$languages = $_POST['languages'] ?? []; // array of langs
$difficulty = $_POST['difficulty'] ?? 'easy';
$created_by = $_SESSION['user_id'];

if ($title === '' || $category === '') {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// store languages as JSON string
$languages_json = json_encode(array_values($languages));

$stmt = $conn->prepare("INSERT INTO cards (title, category, description, languages, difficulty, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $title, $category, $description, $languages_json, $difficulty, $created_by);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    // return the new card data
    echo json_encode([
        'success' => true,
        'card' => [
            'id' => $newId,
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'languages' => json_decode($languages_json),
            'difficulty' => $difficulty
        ]
    ]);
} else {
    echo json_encode(['error' => 'Insert failed: ' . $conn->error]);
}
$stmt->close();
$conn->close();
