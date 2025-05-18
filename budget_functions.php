<?php
/**
 * Budget-related functions for Gastos Guard
 */

if (!function_exists('check_connection')) {
    function check_connection() {
        global $conn;
        if (!isset($conn) || !$conn) {
            error_log("Database connection not available in check_connection");
            throw new Exception("Database connection not available");
        }
        if (!$conn->ping()) {
            error_log("Database connection failed in check_connection: " . $conn->error);
            throw new Exception("Database connection failed: " . $conn->error);
        }
    }
}

/**
 * Helper function to ensure UTF-8 encoding for strings in an array
 * 
 * @param mixed $data The data to encode (array or string)
 * @return mixed The UTF-8 encoded data
 */
function ensure_utf8($data) {
    if (is_array($data)) {
        return array_map('ensure_utf8', $data);
    }
    if (is_string($data)) {
        if (extension_loaded('mbstring')) {
            $encoding = mb_detect_encoding($data, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
            if ($encoding !== 'UTF-8') {
                $data = mb_convert_encoding($data, 'UTF-8', $encoding);
            }
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } elseif (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $data);
            return $converted !== false ? $converted : $data;
        } else {
            return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $data);
        }
    }
    return $data;
}

/**
 * Get all expense categories
 * 
 * @return array List of expense categories
 */
function get_expense_categories() {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in get_expense_categories: " . $e->getMessage());
        return [];
    }
    
    $sql = "SELECT category_id, name, icon, color FROM expense_categories WHERE is_default = 1 ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement in get_expense_categories: " . $conn->error);
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = ensure_utf8($row);
    }
    
    $stmt->close();
    return $categories;
}

/**
 * Add a new budget for a user
 * 
 * @param int $user_id The ID of the user
 * @param array $data Budget data (title, category_id, amount, start_date, end_date)
 * @return array Result of the operation
 */
function add_budget($user_id, $data) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in add_budget: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    $title = sanitize_input($data['title']);
    $category_id = (int)$data['category_id'];
    $amount = (float)$data['amount'];
    $start_date = $data['start_date'];
    $end_date = $data['end_date'];
    
    if (empty($title)) {
        return ['success' => false, 'message' => 'Budget title cannot be empty'];
    }
    
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Amount must be greater than 0'];
    }
    
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        return ['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.'];
    }
    
    if (strtotime($end_date) <= strtotime($start_date)) {
        return ['success' => false, 'message' => 'End date must be after start date'];
    }
    
    $category_exists = getRow("SELECT category_id FROM expense_categories WHERE category_id = $category_id AND is_default = 1");
    if (!$category_exists) {
        return ['success' => false, 'message' => 'Invalid category selected'];
    }
    
    $stmt = $conn->prepare("INSERT INTO budgets (user_id, category_id, title, amount, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Failed to prepare statement in add_budget: " . $conn->error);
        return ['success' => false, 'message' => 'Failed to add budget'];
    }
    
    $stmt->bind_param("iisdss", $user_id, $category_id, $title, $amount, $start_date, $end_date);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Budget added successfully'];
    } else {
        error_log("Failed to execute statement in add_budget: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to add budget'];
    }
}

/**
 * Get budgets for a user with optional filters
 * 
 * @param int $user_id The ID of the user
 * @param array $filters Optional filters (start_date, end_date, category_id)
 * @return array List of budgets
 */
function get_budgets($user_id, $filters = []) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in get_budgets: " . $e->getMessage());
        return [];
    }
    
    $sql = "SELECT b.budget_id, b.title, b.amount, b.start_date, b.end_date, COALESCE(ec.name, 'Unknown') as category, COALESCE(ec.category_id, b.category_id) as category_id
            FROM budgets b
            LEFT JOIN expense_categories ec ON b.category_id = ec.category_id
            WHERE b.user_id = ?";
    
    $params = [$user_id];
    $types = "i";
    
    // Apply date filter only if both start_date and end_date are provided
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $sql .= " AND (b.start_date BETWEEN ? AND ? OR b.end_date BETWEEN ? AND ?)";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $types .= "ssss";
    }
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND b.category_id = ?";
        $params[] = (int)$filters['category_id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY b.start_date DESC";
    
    error_log("Executing query: $sql with params: " . print_r($params, true));
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement in get_budgets: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $budgets[] = ensure_utf8($row);
    }
    
    error_log("Fetched budgets count: " . count($budgets));
    $stmt->close();
    return $budgets;
}

/**
 * Get budget progress with spending data
 * 
 * @param int $user_id The ID of the user
 * @param array $filters Optional filters (start_date, end_date, category_id, budget_id)
 * @return array Budget progress data
 */
function get_budget_progress($user_id, $filters = []) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in get_budget_progress: " . $e->getMessage());
        return [];
    }
    
    $sql = "SELECT b.budget_id, b.title, b.amount as total, b.start_date, b.end_date, 
                   COALESCE(ec.name, 'Unknown') as category, ec.color, COALESCE(ec.category_id, b.category_id) as category_id,
                   COALESCE(SUM(e.amount), 0) as current
            FROM budgets b
            LEFT JOIN expense_categories ec ON b.category_id = ec.category_id
            LEFT JOIN expenses e ON e.category_id = b.category_id 
                AND e.user_id = b.user_id 
                AND e.date_spent BETWEEN b.start_date AND b.end_date
            WHERE b.user_id = ?";
    
    $params = [$user_id];
    $types = "i";
    
    if (!empty($filters['budget_id'])) {
        $sql .= " AND b.budget_id = ?";
        $params[] = (int)$filters['budget_id'];
        $types .= "i";
    }
    
    // Only apply date filter if BOTH start_date and end_date are provided and non-empty
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        // Modified this condition to correctly check for budgets overlapping with filter period
        $sql .= " AND (
            (b.start_date <= ? AND b.end_date >= ?) OR
            (b.start_date >= ? AND b.start_date <= ?) OR
            (b.end_date >= ? AND b.end_date <= ?)
        )";
        $params[] = $filters['end_date'];    // End of filter period ≥ start of budget
        $params[] = $filters['start_date'];  // Start of filter period ≤ end of budget
        $params[] = $filters['start_date'];  // Start of budget ≥ start of filter
        $params[] = $filters['end_date'];    // Start of budget ≤ end of filter
        $params[] = $filters['start_date'];  // End of budget ≥ start of filter
        $params[] = $filters['end_date'];    // End of budget ≤ end of filter
        $types .= "ssssss";
    }
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND b.category_id = ?";
        $params[] = (int)$filters['category_id'];
        $types .= "i";
    }
    
    $sql .= " GROUP BY b.budget_id ORDER BY b.start_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement in get_budget_progress: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $progress = ($row['total'] > 0) ? ($row['current'] / $row['total']) * 100 : 0;
        $row['progress_percentage'] = round($progress, 2);
        $row['status'] = $progress >= 100 ? 'exceeded' : ($progress >= 80 ? 'warning' : 'normal');
        $budgets[] = ensure_utf8($row);
    }
    
    $stmt->close();
    return $budgets;
}

/**
 * Get a specific budget by its ID
 * 
 * @param int $budget_id The ID of the budget
 * @return array|null Budget details or null if not found
 */
function get_budget_by_id($budget_id) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in get_budget_by_id: " . $e->getMessage());
        return null;
    }
    
    $sql = "SELECT budget_id, user_id, category_id, title, amount, start_date, end_date
            FROM budgets
            WHERE budget_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement in get_budget_by_id: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budget = $result->fetch_assoc();
    
    $stmt->close();
    return $budget ? ensure_utf8($budget) : null;
}

/**
 * Update an existing budget
 * 
 * @param int $budget_id The ID of the budget to update
 * @param string $title The updated title of the budget
 * @param int $category_id The updated category ID
 * @param float $amount The updated amount
 * @param string $start_date The updated start date (YYYY-MM-DD)
 * @param string $end_date The updated end date (YYYY-MM-DD)
 * @return array Result of the operation
 */
function update_budget($budget_id, $title, $category_id, $amount, $start_date, $end_date) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in update_budget: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    $title = sanitize_input($title);
    $category_id = (int)$category_id;
    $amount = (float)$amount;
    
    if (empty($title)) {
        return ['success' => false, 'message' => 'Budget title cannot be empty'];
    }
    
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Amount must be greater than 0'];
    }
    
    $startDateObj = DateTime::createFromFormat('Y-m-d', $start_date);
    $endDateObj = DateTime::createFromFormat('Y-m-d', $end_date);
    if (!$startDateObj || !$endDateObj || $startDateObj->format('Y-m-d') !== $start_date || $endDateObj->format('Y-m-d') !== $end_date) {
        return ['success' => false, 'message' => 'Invalid date format for start_date or end_date. Use YYYY-MM-DD.'];
    }
    
    if ($endDateObj <= $startDateObj) {
        return ['success' => false, 'message' => 'End date must be after start date'];
    }
    
    $category_exists = getRow("SELECT category_id FROM expense_categories WHERE category_id = $category_id AND is_default = 1");
    if (!$category_exists) {
        return ['success' => false, 'message' => 'Invalid category selected'];
    }
    
    $stmt = $conn->prepare("UPDATE budgets SET title = ?, category_id = ?, amount = ?, start_date = ?, end_date = ? WHERE budget_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in update_budget: " . $conn->error);
        return ['success' => false, 'message' => 'Failed to prepare database statement'];
    }
    
    $stmt->bind_param("sidssi", $title, $category_id, $amount, $start_date, $end_date, $budget_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Budget updated successfully'];
    } else {
        error_log("Failed to execute statement in update_budget: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update budget in database'];
    }
}

/**
 * Delete a budget by its ID
 * 
 * @param int $budget_id The ID of the budget to delete
 * @return array Result of the operation
 */
function delete_budget($budget_id) {
    global $conn;
    
    try {
        check_connection();
    } catch (Exception $e) {
        error_log("Error in delete_budget: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    $stmt = $conn->prepare("DELETE FROM budgets WHERE budget_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in delete_budget: " . $conn->error);
        return ['success' => false, 'message' => 'Failed to prepare database statement'];
    }
    
    $stmt->bind_param("i", $budget_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        if ($affected_rows > 0) {
            return ['success' => true, 'message' => 'Budget deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'No budget found with the specified ID'];
        }
    } else {
        error_log("Failed to execute statement in delete_budget: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to delete budget in database'];
    }
}
?>