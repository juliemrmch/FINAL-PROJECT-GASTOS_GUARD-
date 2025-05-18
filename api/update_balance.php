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

$new_balance = isset($data['new_balance']) ? floatval($data['new_balance']) : null;

if ($new_balance === null) {
    echo json_encode(['success' => false, 'message' => 'Balance is required']);
    exit();
}

if ($new_balance < 0) {
    echo json_encode(['success' => false, 'message' => 'Balance cannot be negative']);
    exit();
}

$result = updateUserBalance($user_id, $new_balance);
echo json_encode($result);
?>