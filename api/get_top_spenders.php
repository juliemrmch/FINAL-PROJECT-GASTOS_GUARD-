<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Get top 3 spenders
$sql = "SELECT u.full_name, u.username, COUNT(e.expense_id) as expenses, COALESCE(SUM(e.amount),0) as total_spent
        FROM users u
        LEFT JOIN expenses e ON u.user_id = e.user_id
        GROUP BY u.user_id
        ORDER BY total_spent DESC
        LIMIT 3";

$result = $conn->query($sql);
$spenders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $spenders[] = [
            'full_name' => $row['full_name'],
            'username' => $row['username'],
            'expenses' => $row['expenses'],
            'total_spent' => $row['total_spent']
        ];
    }
}
echo json_encode($spenders); 