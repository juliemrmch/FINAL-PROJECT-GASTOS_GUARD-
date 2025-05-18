<?php
// API endpoint to delete an expense for the logged-in user
header('Content-Type: application/json');

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get expense ID from request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required field
if (!isset($input['expense_id'])) {
    error_log('Expense ID missing in delete_expense.php');
    echo json_encode([
        'success' => false,
        'message' => 'Expense ID is required'
    ]);
    exit;
}

$expense_id = (int)$input['expense_id'];

// Delete the expense
$result = delete_expense($user_id, $expense_id);

if (!$result['success']) {
    error_log('Delete expense failed in delete_expense.php: ' . $result['message']);
}

// Return JSON response
echo json_encode($result);
?>