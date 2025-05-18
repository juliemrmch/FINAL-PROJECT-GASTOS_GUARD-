<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Get POST data
$user_id = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$status = trim($_POST['status'] ?? 'Active');

if (!$user_id || !$username || !$email || !$full_name) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// Check for duplicate username/email (excluding this user)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
$stmt->bind_param("ssi", $username, $email, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['error' => 'Username or email already exists.']);
    exit;
}
$stmt->close();

// Convert status to 1/0
$active = ($status === 'Active') ? 1 : (($status === 'Pending') ? 0 : 0);

// Update user
$stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, active = ? WHERE user_id = ?");
$stmt->bind_param("sssii", $username, $email, $full_name, $active, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update user.']);
}
$stmt->close(); 