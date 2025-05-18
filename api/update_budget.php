<?php
// Start output buffering to capture any unintended output
ob_start();

// Set error reporting for debugging (remove in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('Processing update_budget.php request');

header('Content-Type: application/json');

// Verify session and include files with error checking
if (!file_exists(__DIR__ . '/../includes/db.php') || !file_exists(__DIR__ . '/../includes/budget_functions.php')) {
    error_log('Required include file missing in update_budget.php');
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    ob_end_flush();
    exit();
}

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/budget_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('Unauthorized access attempt in update_budget.php');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit();
}

$user_id = $_SESSION['user_id'];

// Log all POST data for debugging
error_log('Received POST data in update_budget.php: ' . print_r($_POST, true));

// Validate and sanitize input
$budget_id = isset($_POST['budget_id']) && is_numeric($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;
$title = isset($_POST['title']) && !empty(trim($_POST['title'])) ? trim($_POST['title']) : '';
$category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? intval($_POST['category_id']) : null;
$amount = isset($_POST['amount']) && is_numeric($_POST['amount']) && floatval($_POST['amount']) > 0 ? floatval($_POST['amount']) : 0;
$start_date = isset($_POST['start_date']) && !empty(trim($_POST['start_date'])) ? trim($_POST['start_date']) : '';
$end_date = isset($_POST['end_date']) && !empty(trim($_POST['end_date'])) ? trim($_POST['end_date']) : '';

// Log the received input for debugging
error_log('Validated input in update_budget.php: budget_id=' . $budget_id . ', title=' . $title . ', category_id=' . $category_id . ', amount=' . $amount . ', start_date=' . $start_date . ', end_date=' . $end_date);

// Validate inputs
if ($budget_id <= 0 || empty($title) || !$category_id || $amount <= 0 || empty($start_date) || empty($end_date)) {
    error_log('Invalid input data in update_budget.php: budget_id=' . $budget_id . ', title=' . $title . ', category_id=' . $category_id . ', amount=' . $amount . ', start_date=' . $start_date . ', end_date=' . $end_date);
    echo json_encode(['success' => false, 'message' => 'Invalid input data. Please fill all required fields.']);
    ob_end_flush();
    exit();
}

// Check for invalid dates (e.g., 0000-00-00)
if ($start_date === '0000-00-00' || $end_date === '0000-00-00') {
    error_log('Invalid date value (0000-00-00) in update_budget.php: start_date=' . $start_date . ', end_date=' . $end_date);
    echo json_encode(['success' => false, 'message' => 'Invalid date value. Please provide valid start and end dates.']);
    ob_end_flush();
    exit();
}

// Validate date format
$startDateObj = DateTime::createFromFormat('Y-m-d', $start_date);
$endDateObj = DateTime::createFromFormat('Y-m-d', $end_date);

if (!$startDateObj || !$endDateObj || $startDateObj->format('Y-m-d') !== $start_date || $endDateObj->format('Y-m-d') !== $end_date) {
    error_log('Invalid date format in update_budget.php: start_date=' . $start_date . ', end_date=' . $end_date);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Please use YYYY-MM-DD.']);
    ob_end_flush();
    exit();
}

// Validate date range
if ($endDateObj <= $startDateObj) {
    error_log('End date before or equal to start date in update_budget.php: start=' . $start_date . ', end=' . $end_date);
    echo json_encode(['success' => false, 'message' => 'End date must be after start date.']);
    ob_end_flush();
    exit();
}

// Use the database connection (assuming $conn is defined in db.php)
try {
    global $conn;
    if (!$conn) {
        throw new Exception('Database connection not established');
    }

    // Verify budget belongs to user
    $budget = get_budget_by_id($budget_id);
    if (!$budget || $budget['user_id'] != $user_id) {
        error_log('Budget not found or unauthorized for user_id: ' . $user_id . ', budget_id: ' . $budget_id);
        echo json_encode(['success' => false, 'message' => 'Budget not found or unauthorized']);
        ob_end_flush();
        exit();
    }

    // Log the end_date value before calling update_budget
    error_log('End date before calling update_budget: ' . $end_date);

    // Update budget with the validated end_date
    $result = update_budget($budget_id, $title, $category_id, $amount, $start_date, $end_date);
    
    if ($result === false || (is_array($result) && !$result['success'])) {
        $errorMessage = is_array($result) ? $result['message'] : 'Failed to update budget in database';
        error_log('Failed to update budget: ' . $errorMessage);
        throw new Exception($errorMessage);
    }

    echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);
} catch (Exception $e) {
    error_log('Exception in update_budget.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update budget: ' . $e->getMessage()]);
}

// Clear buffer and exit
ob_end_flush();
exit();
?>