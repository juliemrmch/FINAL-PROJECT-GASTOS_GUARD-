<?php
// api/generate_report.php
header('Content-Type: application/json');

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';
require_once '../includes/report_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get filter parameters from request
$input = json_decode(file_get_contents('php://input'), true);
$year = isset($input['year']) ? (int)$input['year'] : date('Y');
$month = isset($input['month']) ? (int)$input['month'] : date('n');

try {
    // Calculate date range for the selected month
    $start_date = date('Y-m-01', strtotime("$year-$month-01"));
    $end_date = date('Y-m-t', strtotime("$year-$month-01"));

    // Fetch report data
    $spending_by_category = get_expenses_by_category($user_id, $start_date, $end_date);
    
    // New: Get monthly spending for the whole year
    $monthly_spending = [];
    for ($m = 1; $m <= 12; $m++) {
        $month_start = date('Y-m-01', strtotime("$year-$m-01"));
        $month_end = date('Y-m-t', strtotime("$year-$m-01"));
        $total = get_total_expenses($user_id, $month_start, $month_end);
        $month_label = date('M', strtotime($month_start));
        $monthly_spending[$month_label] = $total;
    }
    
    $top_categories = get_top_categories($user_id, $start_date, $end_date);
    $expense_growth = calculate_expense_growth($user_id, $year, $month);

    // Add percent field to each category in spending_by_category
    $total_spent = array_sum(array_column($spending_by_category, 'amount'));
    foreach ($spending_by_category as &$cat) {
        $cat['percent'] = $total_spent > 0 ? round(($cat['amount'] / $total_spent) * 100, 2) : 0;
    }
    unset($cat);

    // Add percent field to each top category
    foreach ($top_categories as &$cat) {
        $cat['percent'] = $total_spent > 0 ? round(($cat['amount'] / $total_spent) * 100, 2) : 0;
    }
    unset($cat);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'spending_by_category' => $spending_by_category,
        'monthly_spending_trends' => $monthly_spending,
        'top_categories' => $top_categories,
        'expense_growth' => $expense_growth
    ]);
} catch (Exception $e) {
    error_log('Error generating report: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to generate report']);
}
?>