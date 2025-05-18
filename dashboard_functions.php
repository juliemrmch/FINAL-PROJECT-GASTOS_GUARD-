<?php
/**
 * Dashboard components and data preparation for Gastos Guard
 */

require_once 'expense_functions.php';
require_once 'budget_functions.php'; // Added to use get_budget_progress()

/**
 * Get user data for the dashboard
 * 
 * @param int $user_id The ID of the logged-in user
 * @return array User data
 */
function get_user_data($user_id) {
    global $conn;
    
    $sql = "SELECT user_id, username, email, full_name, profile_image, current_balance
            FROM users WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Return default user data if not found
    return [
        'user_id' => $user_id,
        'username' => 'user',
        'email' => 'user@example.com',
        'full_name' => 'User',
        'profile_image' => null,
        'current_balance' => 0.00
    ];
}

/**
 * Get all data needed for the dashboard
 * 
 * @param int $user_id The ID of the logged-in user
 * @return array Dashboard data
 */
function get_dashboard_data($user_id) {
    // Get user information
    $user = get_user_data($user_id);
    
    // Get current month and comparison with last month
    $comparison = compare_with_last_month($user_id);
    $total_spent = $comparison['current_total'] ?? 0;
    $last_month_comparison = $comparison['percentage'] * -1; // Invert percentage for UI display
    
    // Get spending categories with fallbacks for color/icon
    $spending_categories = get_expenses_by_category($user_id);
    foreach ($spending_categories as &$category) {
        $category['color'] = $category['color'] ?? get_default_category_color($category['name']);
        $category['icon'] = $category['icon'] ?? get_default_category_icon($category['name']);
    }
    
    // Get recent expenses
    $recent_expenses = get_recent_expenses($user_id);
    foreach ($recent_expenses as &$expense) {
        $expense['color'] = $expense['color'] ?? get_default_category_color($expense['category']);
        $expense['icon'] = $expense['icon'] ?? get_default_category_icon($expense['category']);
        $expense['name'] = $expense['name'] ?: "Expense on " . format_date($expense['date'], 'F j, Y');
    }
    
    // Get average daily spending
    $daily_spending_data = get_average_daily_spending($user_id);
    $daily_spending = $daily_spending_data['daily_spending'];
    $average_daily = $daily_spending_data['average_daily'];
    foreach ($daily_spending as &$category) {
        $category['color'] = $category['color'] ?? get_default_category_color($category['category']);
    }
    
    // Get budget progress (using budget_functions.php)
    $budget_progress = get_budget_progress($user_id);
    foreach ($budget_progress as &$budget) {
        // Adapt data structure to match dashboard expectations
        $budget['color'] = $budget['color'] ?? get_default_category_color($budget['category']);
    }
    
    // Get monthly overview
    $monthly_data = get_monthly_spending($user_id);
    $monthly_overview = $monthly_data['categories'];
    $current_month = $monthly_data['month'];
    foreach ($monthly_overview as &$category) {
        $category['color'] = $category['color'] ?? get_default_category_color($category['name']);
    }
    
    return [
        'user' => $user,
        'total_spent' => $total_spent,
        'last_month_comparison' => $last_month_comparison,
        'spending_categories' => $spending_categories,
        'recent_expenses' => $recent_expenses,
        'daily_spending' => $daily_spending,
        'average_daily' => $average_daily,
        'budget_progress' => $budget_progress,
        'monthly_overview' => $monthly_overview,
        'current_month' => $current_month
    ];
}

/**
 * Get default color for a category based on its name
 * 
 * @param string $category_name Category name
 * @return string Hex color code
 */
function get_default_category_color($category_name) {
    $category_name = strtolower($category_name);
    if (strpos($category_name, 'food') !== false || strpos($category_name, 'groceries') !== false) {
        return '#ff6b3d'; // Orange
    } elseif (strpos($category_name, 'transport') !== false) {
        return '#3b82f6'; // Blue
    } elseif (strpos($category_name, 'entertainment') !== false) {
        return '#d946ef'; // Purple
    } elseif (strpos($category_name, 'shopping') !== false) {
        return '#10b981'; // Green
    } elseif (strpos($category_name, 'utilities') !== false || strpos($category_name, 'housing') !== false) {
        return '#f59e0b'; // Yellow
    } elseif (strpos($category_name, 'health') !== false) {
        return '#ef4444'; // Red
    } else {
        return '#10b981'; // Default green
    }
}

/**
 * Get default icon for a category based on its name
 * 
 * @param string $category_name Category name
 * @return string Font Awesome icon class
 */
function get_default_category_icon($category_name) {
    $category_name = strtolower($category_name);
    if (strpos($category_name, 'food') !== false || strpos($category_name, 'groceries') !== false) {
        return 'fa-utensils';
    } elseif (strpos($category_name, 'transport') !== false) {
        return 'fa-bus';
    } elseif (strpos($category_name, 'entertainment') !== false) {
        return 'fa-film';
    } elseif (strpos($category_name, 'shopping') !== false) {
        return 'fa-shopping-bag';
    } elseif (strpos($category_name, 'utilities') !== false || strpos($category_name, 'housing') !== false) {
        return 'fa-home';
    } elseif (strpos($category_name, 'health') !== false) {
        return 'fa-heartbeat';
    } else {
        return 'fa-ellipsis-h';
    }
}
?>