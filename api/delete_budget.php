<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/budget_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get budget_id from POST data
$budget_id = isset($_POST['budget_id']) ? intval($_POST['budget_id']) : 0;

if ($budget_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
    exit();
}

// Verify budget exists and belongs to user
$budget = get_budget_by_id($budget_id);
if (!$budget || $budget['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Budget not found or unauthorized']);
    exit();
}

// Delete the budget
$result = delete_budget($budget_id);

if ($result && $result['success']) {
    echo json_encode(['success' => true, 'message' => 'Budget deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to delete budget']);
}
?>