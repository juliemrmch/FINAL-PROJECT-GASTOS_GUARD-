<?php
/**
 * Report related functions for Gastos Guard
 */

/**
 * Get monthly spending trends for a user within a date range
 * 
 * @param int $user_id The ID of the user
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @return array Spending amounts indexed by date
 */
function get_monthly_spending_trends($user_id, $start_date, $end_date) {
    global $conn;

    $sql = "SELECT DATE(date_spent) as date, SUM(amount) as total 
            FROM expenses 
            WHERE user_id = ? AND date_spent BETWEEN ? AND ?
            GROUP BY DATE(date_spent)
            ORDER BY date ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Failed to prepare statement for get_monthly_spending_trends: ' . $conn->error);
        return [];
    }
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $trends = [];
    while ($row = $result->fetch_assoc()) {
        $trends[$row['date']] = (float)$row['total'];
    }
    $stmt->close();

    // Fill in missing dates with 0
    $current_date = new DateTime($start_date);
    $end_date = new DateTime($end_date);
    while ($current_date <= $end_date) {
        $date_str = $current_date->format('Y-m-d');
        if (!isset($trends[$date_str])) {
            $trends[$date_str] = 0.00;
        }
        $current_date->modify('+1 day');
    }

    ksort($trends);
    return $trends;
}

/**
 * Get top spending categories with month-over-month comparison
 * 
 * @param int $user_id The ID of the user
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @return array Top categories with amounts and change percentages
 */
function get_top_categories($user_id, $start_date, $end_date) {
    global $conn;

    // Calculate previous period for comparison
    $date_start = new DateTime($start_date);
    $date_end = new DateTime($end_date);
    $interval = $date_end->diff($date_start);
    $days = $interval->days + 1;

    $prev_start = (clone $date_start)->modify("-$days days");
    $prev_end = (clone $date_end)->modify("-$days days");
    $last_period_start = $prev_start->format('Y-m-d');
    $last_period_end = $prev_end->format('Y-m-d');

    // Current period spending by category
    $current_sql = "SELECT ec.name, ec.color, SUM(e.amount) as current_amount 
                    FROM expenses e
                    JOIN expense_categories ec ON e.category_id = ec.category_id
                    WHERE e.user_id = ? AND e.date_spent BETWEEN ? AND ?
                    GROUP BY e.category_id
                    ORDER BY current_amount DESC
                    LIMIT 3";
    
    $stmt = $conn->prepare($current_sql);
    if (!$stmt) {
        error_log('Failed to prepare statement for get_top_categories (current): ' . $conn->error);
        return [];
    }
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $current_result = $stmt->get_result();
    $current_categories = [];
    while ($row = $current_result->fetch_assoc()) {
        $current_categories[$row['name']] = [
            'amount' => (float)$row['current_amount'],
            'color' => $row['color']
        ];
    }
    $stmt->close();

    // Last period spending by category
    $last_sql = "SELECT ec.name, SUM(e.amount) as last_amount 
                 FROM expenses e
                 JOIN expense_categories ec ON e.category_id = ec.category_id
                 WHERE e.user_id = ? AND e.date_spent BETWEEN ? AND ?
                 GROUP BY e.category_id";
    $stmt = $conn->prepare($last_sql);
    if (!$stmt) {
        error_log('Failed to prepare statement for get_top_categories (last): ' . $conn->error);
        return [];
    }
    $stmt->bind_param("iss", $user_id, $last_period_start, $last_period_end);
    $stmt->execute();
    $last_result = $stmt->get_result();
    $last_categories = [];
    while ($row = $last_result->fetch_assoc()) {
        $last_categories[$row['name']] = (float)$row['last_amount'];
    }
    $stmt->close();

    // Combine and calculate change
    $top_categories = [];
    foreach ($current_categories as $name => $data) {
        $last_amount = isset($last_categories[$name]) ? $last_categories[$name] : 0;
        $change = ($last_amount > 0) ? (($data['amount'] - $last_amount) / $last_amount) * 100 : ($data['amount'] > 0 ? 100 : 0);
        $top_categories[] = [
            'name' => $name,
            'amount' => $data['amount'],
            'color' => $data['color'],
            'change' => round($change, 2)
        ];
    }

    return $top_categories;
}

/**
 * Calculate expense growth metrics (week-over-week, month-over-month, year-over-year)
 * 
 * @param int $user_id The ID of the user
 * @param int $year The selected year for the report
 * @param int $month The selected month for the report
 * @return array Growth percentages
 */
function calculate_expense_growth($user_id, $year, $month) {
    global $conn;

    // Define the selected month's date range
    $selected_month_start = date('Y-m-01', strtotime("$year-$month-01"));
    $selected_month_end = date('Y-m-t', strtotime("$year-$month-01"));

    // Week-over-week: Compare the last full week of the selected month to the previous full week
    $last_day_of_month = new DateTime($selected_month_end);
    $last_day_of_month->modify('last day of this month');
    
    // Find the last Monday of the month to define the last full week
    while ($last_day_of_month->format('N') != 1) { // 1 = Monday
        $last_day_of_month->modify('-1 day');
    }
    $last_week_end = $last_day_of_month->format('Y-m-d');
    $last_week_start = (clone $last_day_of_month)->modify('-6 days')->format('Y-m-d');
    
    // Previous full week (7 days before the last week)
    $prev_week_end = (clone $last_day_of_month)->modify('-7 days')->format('Y-m-d');
    $prev_week_start = (clone $last_day_of_month)->modify('-13 days')->format('Y-m-d');

    // Month-over-month: Compare the selected month to the previous month
    $prev_month_start = date('Y-m-01', strtotime("$year-$month-01 -1 month"));
    $prev_month_end = date('Y-m-t', strtotime("$year-$month-01 -1 month"));

    // Year-over-year: Compare the selected month to the same month in the previous year
    $prev_year_start = date('Y-m-01', strtotime("$year-$month-01 -1 year"));
    $prev_year_end = date('Y-m-t', strtotime("$year-$month-01 -1 year"));

    // Fetch totals for each period
    $current_week_total = get_total_expenses($user_id, $last_week_start, $last_week_end);
    $prev_week_total = get_total_expenses($user_id, $prev_week_start, $prev_week_end);
    
    $current_month_total = get_total_expenses($user_id, $selected_month_start, $selected_month_end);
    $prev_month_total = get_total_expenses($user_id, $prev_month_start, $prev_month_end);
    
    $current_year_month_total = get_total_expenses($user_id, $selected_month_start, $selected_month_end);
    $prev_year_month_total = get_total_expenses($user_id, $prev_year_start, $prev_year_end);

    // Log totals for debugging
    error_log("Week-over-week: Last week ($last_week_start to $last_week_end) = $current_week_total, Previous week ($prev_week_start to $prev_week_end) = $prev_week_total");
    error_log("Month-over-month: Current month ($selected_month_start to $selected_month_end) = $current_month_total, Previous month ($prev_month_start to $prev_month_end) = $prev_month_total");
    error_log("Year-over-year: Current year month ($selected_month_start to $selected_month_end) = $current_year_month_total, Previous year month ($prev_year_start to $prev_year_end) = $prev_year_month_total");

    // Calculate growth percentages
    $week_over_week = ($prev_week_total > 0) ? (($current_week_total - $prev_week_total) / $prev_week_total) * 100 : ($current_week_total > 0 ? 100 : 0);
    $month_over_month = ($prev_month_total > 0) ? (($current_month_total - $prev_month_total) / $prev_month_total) * 100 : ($current_month_total > 0 ? 100 : 0);
    $year_over_year = ($prev_year_month_total > 0) ? (($current_year_month_total - $prev_year_month_total) / $prev_year_month_total) * 100 : ($current_year_month_total > 0 ? 100 : 0);

    return [
        'week_over_week' => round($week_over_week, 2),
        'month_over_month' => round($month_over_month, 2),
        'year_over_year' => round($year_over_year, 2)
    ];
}
?>