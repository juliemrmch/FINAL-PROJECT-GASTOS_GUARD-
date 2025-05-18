<?php
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/budget_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$filters = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST requests from loadBudgets()
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: [];
    $filters = [
        'start_date' => $data['start_date'] ?? '',
        'end_date' => $data['end_date'] ?? '',
        'category_id' => $data['category_id'] ?? ''
    ];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET requests from openEditModal()
    $budget_id = isset($_GET['budget_id']) && is_numeric($_GET['budget_id']) ? intval($_GET['budget_id']) : null;
    if ($budget_id) {
        $filters['budget_id'] = $budget_id;
    }
}

$budgets = get_budget_progress($user_id, $filters);

if (empty($budgets)) {
    echo json_encode(['success' => false, 'message' => 'No budgets found', 'budgets' => []]);
} else {
    echo json_encode(['success' => true, 'budgets' => $budgets]);
}
exit();
?>