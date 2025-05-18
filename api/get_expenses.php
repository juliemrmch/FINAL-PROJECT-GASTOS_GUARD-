<?php
// API endpoint to fetch expenses for the logged-in user
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

// Get filter parameters from request body
$input = json_decode(file_get_contents('php://input'), true);
$filters = [
    'date_start' => isset($input['date_start']) && !empty($input['date_start']) ? $input['date_start'] : null,
    'date_end' => isset($input['date_end']) && !empty($input['date_end']) ? $input['date_end'] : null,
    'category_id' => isset($input['category_id']) && !empty($input['category_id']) ? (int)$input['category_id'] : null,
];

// Fetch expenses
$expenses = get_user_expenses($user_id, $filters);

// Return JSON response
echo json_encode([
    'success' => true,
    'expenses' => $expenses
]);
?>