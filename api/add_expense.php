<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get expense data from request body
$input_data = file_get_contents('php://input');
$input = json_decode($input_data, true);

// Validate input
if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate required fields
if (!isset($input['date_spent']) || !isset($input['description']) || 
    !isset($input['category_id']) || !isset($input['amount'])) {
    $missing_fields = [];
    if (!isset($input['date_spent'])) $missing_fields[] = 'date_spent';
    if (!isset($input['description'])) $missing_fields[] = 'description';
    if (!isset($input['category_id'])) $missing_fields[] = 'category_id';
    if (!isset($input['amount'])) $missing_fields[] = 'amount';
    
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// Prepare expense data
$data = [
    'date_spent' => sanitize_input($input['date_spent']),
    'description' => sanitize_input($input['description']),
    'category_id' => (int)$input['category_id'],
    'amount' => (float)$input['amount'],
];

if (!DateTime::createFromFormat('Y-m-d', $data['date_spent'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format. Use YYYY-MM-DD.'
    ]);
    exit;
}

if ($data['amount'] <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Amount must be greater than 0'
    ]);
    exit;
}

if (empty($data['description'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Description cannot be empty'
    ]);
    exit;
}

// Add the expense
$result = add_expense($user_id, $data);

if (!$result['success']) {
    error_log('Add expense failed: ' . $result['message']);
}

echo json_encode($result);