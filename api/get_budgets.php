<?php
// Start session and handle errors
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (Exception $e) {
    error_log("Session start failed in get_budgets.php: " . $e->getMessage());
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Server error: Failed to start session']);
    exit();
}

require_once '../includes/db.php'; // Added this line to ensure database connection is available
require_once '../includes/budget_functions.php';

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    error_log("Session user_id is not set");
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$user_id = $_SESSION['user_id'];
error_log("Fetching budgets for user_id: $user_id");

// Fetch budgets (POST with JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'start_date' => '',
        'end_date' => '',
        'category_id' => ''
    ];
    
    // Get input data
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $filters['start_date'] = isset($decoded['start_date']) ? trim($decoded['start_date']) : '';
        $filters['end_date'] = isset($decoded['end_date']) ? trim($decoded['end_date']) : '';
        $filters['category_id'] = isset($decoded['category_id']) ? trim($decoded['category_id']) : '';
        $filters['budget_id'] = isset($decoded['budget_id']) ? trim($decoded['budget_id']) : '';
    }
    
    error_log("Received filters: " . print_r($filters, true));
    
    try {
        $budgets = get_budget_progress($user_id, $filters);
        error_log("Fetched budgets: " . print_r($budgets, true));
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'budgets' => $budgets]);
    } catch (Exception $e) {
        error_log("Error in get_budgets.php: " . $e->getMessage());
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

// If none of the above, return bad request
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => false, 'message' => 'Bad request']);
exit();
?>