<?php
// Start output buffering
ob_start();

// Set error reporting for debugging (remove in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('Processing edit_expense.php request');

header('Content-Type: application/json');

// Verify include files
if (!file_exists('../includes/config.php') || !file_exists('../includes/db.php') || 
    !file_exists('../includes/functions.php') || !file_exists('../includes/auth_functions.php') || 
    !file_exists('../includes/expense_functions.php')) {
    error_log('Required include file missing in edit_expense.php');
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    ob_end_flush();
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get and decode input
$input_data = file_get_contents('php://input');
$input = json_decode($input_data, true);

if (!$input) {
    error_log('Invalid JSON input received in edit_expense.php: ' . $input_data);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    ob_end_flush();
    exit();
}

// Validate required fields
if (!isset($input['expense_id']) || !isset($input['date_spent']) || 
    !isset($input['description']) || !isset($input['category_id']) || 
    !isset($input['amount'])) {
    $missing_fields = [];
    if (!isset($input['expense_id'])) $missing_fields[] = 'expense_id';
    if (!isset($input['date_spent'])) $missing_fields[] = 'date_spent';
    if (!isset($input['description'])) $missing_fields[] = 'description';
    if (!isset($input['category_id'])) $missing_fields[] = 'category_id';
    if (!isset($input['amount'])) $missing_fields[] = 'amount';
    
    error_log('Missing required fields in edit_expense.php: ' . implode(', ', $missing_fields));
    echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
    ob_end_flush();
    exit();
}

// Prepare and sanitize data
$data = [
    'expense_id' => (int)$input['expense_id'],
    'date_spent' => sanitize_input($input['date_spent']),
    'description' => sanitize_input($input['description']),
    'category_id' => (int)$input['category_id'],
    'amount' => (float)$input['amount'],
];

// Validate date
if (!DateTime::createFromFormat('Y-m-d', $data['date_spent'])) {
    error_log('Invalid date format in edit_expense.php: ' . $data['date_spent']);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
    ob_end_flush();
    exit();
}

// Validate amount
if ($data['amount'] <= 0) {
    error_log('Invalid amount in edit_expense.php: ' . $data['amount']);
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    ob_end_flush();
    exit();
}

// Execute the update with error handling
try {
    $result = edit_expense($user_id, $data);
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Exception in edit_expense.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to edit expense: ' . $e->getMessage()]);
}

// Clear buffer and exit
ob_end_flush();
exit();
?>