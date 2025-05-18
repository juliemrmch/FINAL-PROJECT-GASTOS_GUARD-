<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Total users
$total_users = 0;
$active_users = 0;
$new_users = 0;

// Get total users, excluding admin
$res = $conn->query("SELECT COUNT(*) as total FROM users WHERE email != 'admin@gastosguard.com' AND user_type != 'admin'");
if ($res && $row = $res->fetch_assoc()) {
    $total_users = (int)$row['total'];
}

// Get active users, excluding admin
$res = $conn->query("SELECT COUNT(*) as active FROM users WHERE active = 1 AND email != 'admin@gastosguard.com' AND user_type != 'admin'");
if ($res && $row = $res->fetch_assoc()) {
    $active_users = (int)$row['active'];
}

// Get new users this month, excluding admin
$first_day = date('Y-m-01');
$last_day = date('Y-m-t');
$res = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= '$first_day' AND created_at <= '$last_day' AND email != 'admin@gastosguard.com' AND user_type != 'admin'");
if ($res && $row = $res->fetch_assoc()) {
    $new_users = (int)$row['new_users'];
}

echo json_encode([
    'total_users' => $total_users,
    'active_users' => $active_users,
    'new_users' => $new_users
]);
?>