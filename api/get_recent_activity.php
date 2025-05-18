<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../includes/db.php';

$activities = [];

// Recent expenses added
$res = $conn->query("SELECT u.full_name, e.amount, e.created_at FROM expenses e JOIN users u ON e.user_id = u.user_id ORDER BY e.created_at DESC LIMIT 3");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'type' => 'expense_added',
            'user' => $row['full_name'],
            'description' => 'added a new expense',
            'datetime' => $row['created_at'],
        ];
    }
}

// Recent profile updates (simulate with updated_at on users table, skip admin)
$res = $conn->query("SELECT full_name, updated_at FROM users WHERE updated_at > created_at AND user_type != 'admin' ORDER BY updated_at DESC LIMIT 2");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'type' => 'profile_updated',
            'user' => $row['full_name'],
            'description' => 'updated their profile',
            'datetime' => $row['updated_at'],
        ];
    }
}

// Sort all activities by datetime desc and limit to 5
usort($activities, function($a, $b) {
    return strtotime($b['datetime']) - strtotime($a['datetime']);
});
$activities = array_slice($activities, 0, 5);

echo json_encode($activities); 