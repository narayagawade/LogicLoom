<?php
// admin_delete_card.php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'db.php';

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid id']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Delete failed']);
}
$stmt->close();
$conn->close();
