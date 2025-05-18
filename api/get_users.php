<?php
header('Content-Type: application/json');

// Include DB connection (adjust path if needed)
require_once '../includes/db.php';

// Get filter and sorting parameters from the request
$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
$status = $_GET['status'] ?? '';
$join_date = $_GET['join_date'] ?? '';
$sort = $_GET['sort'] ?? 'created_at'; // From filter form
$order = $_GET['order'] ?? 'desc'; // From filter form

// Build the SQL query with filters and sorting
$sql = "SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.active,
        COALESCE(SUM(e.amount), 0) as total_spent, COUNT(e.expense_id) as expenses
        FROM users u
        LEFT JOIN expenses e ON u.user_id = e.user_id
        WHERE u.email != 'admin@gastosguard.com' AND u.user_type != 'admin'";
$params = [];
$types = "";

// Apply filters
if ($name) {
    $sql .= " AND u.full_name LIKE ?";
    $params[] = "%$name%";
    $types .= "s";
}
if ($email) {
    $sql .= " AND u.email LIKE ?";
    $params[] = "%$email%";
    $types .= "s";
}
if ($status && $status !== 'all') {
    if ($status === 'active') {
        $sql .= " AND u.active = ?";
        $params[] = 1;
        $types .= "i";
    } elseif ($status === 'inactive') {
        $sql .= " AND u.active = ?";
        $params[] = 0;
        $types .= "i";
    } elseif ($status === 'pending') {
        $sql .= " AND u.active = ?";
        $params[] = 2;
        $types .= "i";
    }
}
if ($join_date) {
    $sql .= " AND DATE(u.created_at) = ?";
    $params[] = $join_date;
    $types .= "s";
}

// Group by user to aggregate expenses
$sql .= " GROUP BY u.user_id, u.username, u.full_name, u.email, u.created_at, u.active";

// Apply sorting based on filter form
$validSorts = ['full_name', 'email', 'created_at', 'active'];
$sort = in_array($sort, $validSorts) ? $sort : 'created_at';
$order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
$sql .= " ORDER BY $sort $order";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'name' => $row['full_name'],
            'email' => $row['email'],
            'join_date' => date('Y-m-d', strtotime($row['created_at'])),
            'status' => $row['active'] == 1 ? 'Active' : ($row['active'] == 0 ? 'Inactive' : 'Pending'),
            'expenses' => $row['expenses'],
            'total_spent' => $row['total_spent']
        ];
    }
}

$stmt->close();
echo json_encode($users);
?>