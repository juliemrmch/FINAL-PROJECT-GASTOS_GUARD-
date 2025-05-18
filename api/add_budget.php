<?php
// API endpoint to add a new budget
header('Content-Type: application/json');

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/budget_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get data from POST
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';
$amount = isset($_POST['amount']) ? $_POST['amount'] : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Validate required fields
if (empty($title) || empty($category_id) || empty($amount) || empty($start_date) || empty($end_date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Validate amount
if (!is_numeric($amount) || $amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Amount must be a positive number'
    ]);
    exit;
}

// Prepare data for budget
$data = [
    'title' => $title,
    'category_id' => $category_id,
    'amount' => $amount,
    'start_date' => $start_date,
    'end_date' => $end_date
];

// Add budget
$result = add_budget($user_id, $data);

if ($result) {
    echo json_encode([
        'success' => true,
        'budget_id' => $result,
        'message' => 'Budget added successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add budget'
    ]);
}
?>