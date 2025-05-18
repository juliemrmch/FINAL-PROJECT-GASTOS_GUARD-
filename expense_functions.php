<?php
     /**
      * Expense related functions for Gastos Guard
      */

     /**
      * Add a new expense for a user
      * 
      * @param int $user_id The ID of the user
      * @param array $data Expense data (description, date_spent, category_id, amount)
      * @return array Result of the operation
      */
     function add_expense($user_id, $data) {
         global $conn;
         
         // Sanitize and validate inputs
         $description = sanitize_input($data['description']);
         $date_spent = $data['date_spent'];
         $category_id = (int)$data['category_id'];
         $amount = (float)$data['amount'];
         
         // Validate date format
         if (!DateTime::createFromFormat('Y-m-d', $date_spent)) {
             error_log('Invalid date format in add_expense: ' . $date_spent);
             return ['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.'];
         }
         
         // Validate amount
         if ($amount <= 0) {
             error_log('Invalid amount in add_expense: ' . $amount);
             return ['success' => false, 'message' => 'Amount must be greater than 0'];
         }
         
         // Validate description
         if (empty($description)) {
             error_log('Empty description in add_expense');
             return ['success' => false, 'message' => 'Description cannot be empty'];
         }
         
         // Validate category
         $category_exists = getRow("SELECT category_id FROM expense_categories WHERE category_id = $category_id");
         if (!$category_exists) {
             error_log('Invalid category in add_expense: ' . $category_id);
             return ['success' => false, 'message' => 'Invalid category selected'];
         }
         
         // Get and handle user balance
         $user = getRow("SELECT current_balance FROM users WHERE user_id = $user_id");
         $current_balance = $user ? ($user['current_balance'] ?? 0.00) : 0.00; // Default to 0 if NULL
         
         if ($current_balance < $amount) {
             error_log('Insufficient balance in add_expense for user ' . $user_id . ': Balance=' . $current_balance . ', Amount=' . $amount);
             return ['success' => false, 'message' => 'Insufficient balance'];
         }
         
         // Begin transaction to ensure balance update and expense addition are atomic
         $conn->begin_transaction();
         
         try {
             // Insert expense
             $stmt = $conn->prepare("INSERT INTO expenses (user_id, description, date_spent, category_id, amount) VALUES (?, ?, ?, ?, ?)");
             if (!$stmt) {
                 throw new Exception('Failed to prepare statement: ' . $conn->error);
             }
             $stmt->bind_param("issid", $user_id, $description, $date_spent, $category_id, $amount);
             
             if (!$stmt->execute()) {
                 throw new Exception('Failed to add expense: ' . $stmt->error);
             }
             $stmt->close();
             
             // Update user's balance
             $stmt = $conn->prepare("UPDATE users SET current_balance = current_balance - ? WHERE user_id = ?");
             if (!$stmt) {
                 throw new Exception('Failed to prepare balance update statement: ' . $conn->error);
             }
             $stmt->bind_param("di", $amount, $user_id);
             
             if (!$stmt->execute()) {
                 throw new Exception('Failed to update balance: ' . $stmt->error);
             }
             $stmt->close();
             
             // Commit transaction
             $conn->commit();
             return ['success' => true, 'message' => 'Expense added successfully'];
         } catch (Exception $e) {
             // Rollback transaction on error
             $conn->rollback();
             error_log('Add expense error for user ' . $user_id . ': ' . $e->getMessage());
             return ['success' => false, 'message' => 'Failed to add expense: ' . $e->getMessage()];
         }
     }

     /**
      * Edit an existing expense for a user
      * 
      * @param int $user_id The ID of the user
      * @param array $data Expense data (expense_id, description, date_spent, category_id, amount)
      * @return array Result of the operation
      */
     function edit_expense($user_id, $data) {
         global $conn;
         
         // Sanitize and validate inputs
         $expense_id = (int)$data['expense_id'];
         $description = sanitize_input($data['description']);
         $date_spent = $data['date_spent'];
         $category_id = (int)$data['category_id'];
         $amount = (float)$data['amount'];
         
         // Validate date format
         if (!DateTime::createFromFormat('Y-m-d', $date_spent)) {
             error_log('Invalid date format in edit_expense for expense ' . $expense_id . ': ' . $date_spent);
             return ['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.'];
         }
         
         // Validate amount
         if ($amount <= 0) {
             error_log('Invalid amount in edit_expense for expense ' . $expense_id . ': ' . $amount);
             return ['success' => false, 'message' => 'Amount must be greater than 0'];
         }
         
         // Validate description
         if (empty($description)) {
             error_log('Empty description in edit_expense for expense ' . $expense_id);
             return ['success' => false, 'message' => 'Description cannot be empty'];
         }
         
         // Validate category
         $category_exists = getRow("SELECT category_id FROM expense_categories WHERE category_id = $category_id");
         if (!$category_exists) {
             error_log('Invalid category in edit_expense for expense ' . $expense_id . ': ' . $category_id);
             return ['success' => false, 'message' => 'Invalid category selected'];
         }
         
         // Get the existing expense
         $expense = getRow("SELECT amount FROM expenses WHERE expense_id = $expense_id AND user_id = $user_id");
         if (!$expense) {
             error_log('Expense not found or unauthorized in edit_expense: Expense ID=' . $expense_id . ', User ID=' . $user_id);
             return ['success' => false, 'message' => 'Expense not found or not authorized'];
         }
         
         $old_amount = (float)$expense['amount'];
         $amount_difference = $amount - $old_amount;
         
         // Check user balance before updating
         if ($amount_difference > 0) { // Only check if the new amount is higher
             $user = getRow("SELECT current_balance FROM users WHERE user_id = $user_id");
             $current_balance = $user ? ($user['current_balance'] ?? 0.00) : 0.00;
             
             if ($current_balance < $amount_difference) {
                 error_log('Insufficient balance in edit_expense for user ' . $user_id . ': Balance=' . $current_balance . ', Amount Difference=' . $amount_difference);
                 return ['success' => false, 'message' => 'Insufficient balance to cover the updated expense amount'];
             }
         }
         
         // Begin transaction
         $conn->begin_transaction();
         
         try {
             // Update expense
             $stmt = $conn->prepare("UPDATE expenses SET description = ?, date_spent = ?, category_id = ?, amount = ? WHERE expense_id = ? AND user_id = ?");
             if (!$stmt) {
                 throw new Exception('Failed to prepare update statement: ' . $conn->error);
             }
             $stmt->bind_param("ssiddi", $description, $date_spent, $category_id, $amount, $expense_id, $user_id);
             
             if (!$stmt->execute()) {
                 throw new Exception('Failed to update expense: ' . $stmt->error);
             }
             $stmt->close();
             
             // Update user's balance if amount changed
             if ($amount_difference != 0) {
                 $stmt = $conn->prepare("UPDATE users SET current_balance = current_balance - ? WHERE user_id = ?");
                 if (!$stmt) {
                     throw new Exception('Failed to prepare balance update statement: ' . $conn->error);
                 }
                 $stmt->bind_param("di", $amount_difference, $user_id);
                 
                 if (!$stmt->execute()) {
                     throw new Exception('Failed to update balance: ' . $stmt->error);
                 }
                 $stmt->close();
             }
             
             // Commit transaction
             $conn->commit();
             return ['success' => true, 'message' => 'Expense updated successfully'];
         } catch (Exception $e) {
             // Rollback transaction on error
             $conn->rollback();
             error_log('Edit expense error for expense ' . $expense_id . ': ' . $e->getMessage());
             return ['success' => false, 'message' => 'Failed to update expense: ' . $e->getMessage()];
         }
     }

     /**
      * Get total expenses for a user within a given date range
      * 
      * @param int $user_id The ID of the user
      * @param string $start_date Start date in Y-m-d format
      * @param string $end_date End date in Y-m-d format
      * @param int $category_id Optional category ID to filter by
      * @return float Total amount spent
      */
     function get_total_expenses($user_id, $start_date = null, $end_date = null, $category_id = null) {
         global $conn;
         
         // Default to current month if dates not provided
         if (!$start_date) {
             $start_date = date('Y-m-01'); // First day of current month
         }
         
         if (!$end_date) {
             $end_date = date('Y-m-t'); // Last day of current month
         }
         
         $sql = "SELECT SUM(amount) as total FROM expenses 
                 WHERE user_id = ? AND date_spent BETWEEN ? AND ?";
         $params = [$user_id, $start_date, $end_date];
         $types = "iss";
         
         if ($category_id !== null) {
             $sql .= " AND category_id = ?";
             $params[] = $category_id;
             $types .= "i";
         }
         
         $stmt = $conn->prepare($sql);
         if (!$stmt) {
             error_log('Failed to prepare statement for get_total_expenses: ' . $conn->error);
             return 0;
         }
         $stmt->bind_param($types, ...$params);
         $stmt->execute();
         $result = $stmt->get_result();
         $row = $result->fetch_assoc();
         $stmt->close();
         
         return $row['total'] ? (float)$row['total'] : 0;
     }

     /**
      * Compare current month's expenses with previous month
      * 
      * @param int $user_id The ID of the user
      * @return array Containing total amounts and percentage difference
      */
     function compare_with_last_month($user_id) {
         // Current month
         $current_month_start = date('Y-m-01');
         $current_month_end = date('Y-m-t');
         $current_month_total = get_total_expenses($user_id, $current_month_start, $current_month_end);
         
         // Last month
         $last_month_start = date('Y-m-01', strtotime('-1 month'));
         $last_month_end = date('Y-m-t', strtotime('-1 month'));
         $last_month_total = get_total_expenses($user_id, $last_month_start, $last_month_end);
         
         // Calculate percentage difference
         $difference = 0;
         $percentage = 0;
         
         if ($last_month_total > 0) {
             $difference = $current_month_total - $last_month_total;
             $percentage = ($difference / $last_month_total) * 100;
         }
         
         return [
             'current_total' => $current_month_total,
             'last_total' => $last_month_total,
             'difference' => $difference,
             'percentage' => round($percentage, 2)
         ];
     }

     /**
      * Get expenses by category for a user within a given date range
      * 
      * @param int $user_id The ID of the user
      * @param string $start_date Start date in Y-m-d format
      * @param string $end_date End date in Y-m-d format
      * @return array Expenses grouped by category
      */
     function get_expenses_by_category($user_id, $start_date = null, $end_date = null) {
    global $conn;

    // Default to current month if dates not provided
    if (!$start_date) {
        $start_date = date('Y-m-01'); // First day of current month
    }
    if (!$end_date) {
        $end_date = date('Y-m-t'); // Last day of current month
    }

    $sql = "SELECT ec.category_id, ec.name, ec.color, ec.icon, COALESCE(SUM(e.amount), 0) as amount 
            FROM expense_categories ec
            LEFT JOIN expenses e ON e.category_id = ec.category_id 
                AND e.user_id = ? 
                AND e.date_spent BETWEEN ? AND ?
            WHERE ec.is_default = 1
            GROUP BY ec.category_id, ec.name, ec.color, ec.icon
            HAVING amount > 0
            ORDER BY amount DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Failed to prepare statement for get_expenses_by_category: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Ensure color and icon have fallbacks
    foreach ($categories as &$category) {
        if (empty($category['color'])) {
            $category['color'] = get_default_category_color($category['name']);
        }
        if (empty($category['icon'])) {
            $category['icon'] = get_default_category_icon($category['name']);
        }
    }

    return $categories;
}

     /**
      * Get recent expenses for a user
      * 
      * @param int $user_id The ID of the user
      * @param int $limit Number of expenses to return
      * @return array Recent expenses
      */
     function get_recent_expenses($user_id, $limit = 5) {
         global $conn;
         
         $sql = "SELECT e.expense_id as id, e.description as name, e.date_spent as date, 
                 ec.name as category, e.amount, ec.icon, ec.color
                 FROM expenses e
                 JOIN expense_categories ec ON e.category_id = ec.category_id
                 WHERE e.user_id = ?
                 ORDER BY e.date_spent DESC
                 LIMIT ?";
         
         $stmt = $conn->prepare($sql);
         if (!$stmt) {
             error_log('Failed to prepare statement for get_recent_expenses: ' . $conn->error);
             return [];
         }
         $stmt->bind_param("ii", $user_id, $limit);
         $stmt->execute();
         $result = $stmt->get_result();
         
         $expenses = [];
         while ($row = $result->fetch_assoc()) {
             $expenses[] = $row;
         }
         $stmt->close();
         
         return $expenses;
     }

     /**
      * Calculate average daily spending for a user
      * 
      * @param int $user_id The ID of the user
      * @param int $days Number of days to consider
      * @return array Average spending data
      */
     function get_average_daily_spending($user_id, $days = 30) {
         global $conn;
         
         $end_date = date('Y-m-d');
         $start_date = date('Y-m-d', strtotime("-$days days"));
         
         // Calculate total spending in period
         $total_spent = get_total_expenses($user_id, $start_date, $end_date);
         
         // Calculate average per day
         $average_daily = $total_spent > 0 ? $total_spent / $days : 0;
         
         // Get spending by category
         $categories = get_expenses_by_category($user_id, $start_date, $end_date);
         
         // Calculate percentage and daily amount for each category
         $daily_spending = [];
         foreach ($categories as $category) {
             $percentage = $total_spent > 0 ? ($category['amount'] / $total_spent) * 100 : 0;
             $daily_amount = $total_spent > 0 ? $category['amount'] / $days : 0;
             
             $daily_spending[] = [
                 'category' => $category['name'],
                 'amount' => round($daily_amount, 2),
                 'percentage' => round($percentage),
                 'color' => $category['color']
             ];
         }
         
         return [
             'average_daily' => round($average_daily, 2),
             'daily_spending' => $daily_spending
         ];
     }

     /**
      * Get spending data for monthly overview
      * 
      * @param int $user_id The ID of the user
      * @param string $month Month in Y-m format (e.g., 2023-06)
      * @return array Monthly spending data
      */
     function get_monthly_spending($user_id, $month = null) {
         if (!$month) {
             $month = date('Y-m');
         }
         
         $start_date = $month . '-01';
         $end_date = date('Y-m-t', strtotime($start_date));
         
         $total_spent = get_total_expenses($user_id, $start_date, $end_date);
         $categories = get_expenses_by_category($user_id, $start_date, $end_date);
         
         return [
             'total' => $total_spent,
             'month' => date('F Y', strtotime($start_date)),
             'categories' => $categories
         ];
     }

     /**
      * Get expenses for a user with optional filters
      * 
      * @param int $user_id The ID of the user
      * @param array $filters Optional filters (date_start, date_end, category_id)
      * @return array List of expenses
      */
     function get_user_expenses($user_id, $filters = []) {
         global $conn;
         
         $sql = "SELECT e.expense_id, e.description, e.date_spent, e.amount, ec.name as category, ec.category_id
                 FROM expenses e
                 JOIN expense_categories ec ON e.category_id = ec.category_id
                 WHERE e.user_id = ?";
         
         $params = [$user_id];
         $types = "i";
         
         // Apply date filters
         if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
             $sql .= " AND e.date_spent BETWEEN ? AND ?";
             $params[] = $filters['date_start'];
             $params[] = $filters['date_end'];
             $types .= "ss";
         }
         
         // Apply category filter
         if (!empty($filters['category_id'])) {
             $sql .= " AND e.category_id = ?";
             $params[] = (int)$filters['category_id'];
             $types .= "i";
         }
         
         $sql .= " ORDER BY e.date_spent DESC";
         
         $stmt = $conn->prepare($sql);
         if (!$stmt) {
             error_log('Failed to prepare statement for get_user_expenses: ' . $conn->error);
             return [];
         }
         
         $stmt->bind_param($types, ...$params);
         $stmt->execute();
         $result = $stmt->get_result();
         
         $expenses = [];
         while ($row = $result->fetch_assoc()) {
             $expenses[] = $row;
         }
         
         $stmt->close();
         // Add icon and color inline for each expense
         foreach ($expenses as &$expense) {
             $cat_id = (int)$expense['category_id'];
             $cat = getRow("SELECT icon, color FROM expense_categories WHERE category_id = $cat_id");
             $expense['icon'] = $cat['icon'] ?? 'fa-ellipsis-h';
             $expense['color'] = $cat['color'] ?? '#10b981';
         }
         unset($expense);
         return $expenses;
     }

     /**
      * Delete an expense for a user
      * 
      * @param int $user_id The ID of the user
      * @param int $expense_id The ID of the expense to delete
      * @return array Result of the operation
      */
     function delete_expense($user_id, $expense_id) {
         global $conn;
         
         $expense_id = (int)$expense_id;
         
         // Verify the expense belongs to the user
         $expense = getRow("SELECT amount FROM expenses WHERE expense_id = $expense_id AND user_id = $user_id");
         if (!$expense) {
             error_log('Expense not found or unauthorized in delete_expense: Expense ID=' . $expense_id . ', User ID=' . $user_id);
             return ['success' => false, 'message' => 'Expense not found or not authorized'];
         }
         
         $amount = (float)$expense['amount'];
         
         // Begin transaction
         $conn->begin_transaction();
         
         try {
             // Delete the expense
             $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ? AND user_id = ?");
             if (!$stmt) {
                 throw new Exception('Failed to prepare delete statement: ' . $conn->error);
             }
             $stmt->bind_param("ii", $expense_id, $user_id);
             
             if (!$stmt->execute()) {
                 throw new Exception('Failed to delete expense: ' . $stmt->error);
             }
             $stmt->close();
             
             // Restore the user's balance
             $stmt = $conn->prepare("UPDATE users SET current_balance = current_balance + ? WHERE user_id = ?");
             if (!$stmt) {
                 throw new Exception('Failed to prepare balance restore statement: ' . $conn->error);
             }
             $stmt->bind_param("di", $amount, $user_id);
             
             if (!$stmt->execute()) {
                 throw new Exception('Failed to update balance: ' . $stmt->error);
             }
             $stmt->close();
             
             // Commit transaction
             $conn->commit();
             return ['success' => true, 'message' => 'Expense deleted successfully'];
         } catch (Exception $e) {
             // Rollback transaction on error
             $conn->rollback();
             error_log('Delete expense error for expense ' . $expense_id . ': ' . $e->getMessage());
             return ['success' => false, 'message' => 'Failed to delete expense: ' . $e->getMessage()];
         }
     }
     ?>