<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/user_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$first_name = isset($data['first_name']) ? trim($data['first_name']) : '';
$last_name = isset($data['last_name']) ? trim($data['last_name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!preg_match("/^[a-zA-Z\s]+$/", $first_name . ' ' . $last_name)) {
    echo json_encode(['success' => false, 'message' => 'Name can only contain letters and spaces']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$result = updateUserProfileDetails($user_id, $first_name, $last_name, $email);
if ($result['success']) {
    $_SESSION['full_name'] = $first_name . ' ' . $last_name;
    $_SESSION['email'] = $email;
    // Refresh user data to ensure consistency
    $user = getCurrentUser();
    if ($user) {
        $_SESSION['current_balance'] = $user['current_balance']; // Sync balance if needed
    }
}
echo json_encode($result);
?>