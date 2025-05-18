<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$user_id = intval($_POST['id'] ?? 0);
if (!$user_id) {
    echo json_encode(['error' => 'User ID is required.']);
    exit;
}

// Optionally, you can check for related data (e.g., expenses) before deleting
// $conn->query("DELETE FROM expenses WHERE user_id = $user_id");

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to delete user.']);
}
$stmt->close(); 