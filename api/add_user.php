<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Get POST data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');

// Basic validation
if (!$username || !$email || !$password || !$full_name) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// Check for duplicate username/email
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['error' => 'Username or email already exists.']);
    exit;
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_type, active) VALUES (?, ?, ?, ?, 'student', 1)");
$stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to add user.']);
}
$stmt->close();
// $conn->close(); // Removed to avoid double close error 