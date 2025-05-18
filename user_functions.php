<?php
require_once 'db.php';

// Function to update user profile (first name, last name, email)
function updateUserProfileDetails($user_id, $first_name, $last_name, $email) {
    global $conn;
    check_connection();

    $first_name = db_sanitize_input($first_name);
    $last_name = db_sanitize_input($last_name);
    $email = db_sanitize_input($email);
    $full_name = $first_name . ' ' . $last_name;

    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    if (!$stmt) {
        error_log("Email check prepare failed: " . $conn->error);
        return ['success' => false, 'message' => 'Database error'];
    }
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Email is already in use by another account'];
    }
    $stmt->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
        if (!$stmt) {
            error_log("Profile update prepare failed: " . $conn->error);
            throw new Exception("Database prepare error");
        }
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Profile update execute failed: " . $stmt->error);
            throw new Exception("Database execute error");
        }
        $conn->commit();
        $stmt->close();
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Profile update transaction failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// Function to update user password
function updateUserPassword($user_id, $current_password, $new_password) {
    global $conn;
    check_connection();

    // Fetch current password hash
    $user = getUserById($user_id);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }

    // Validate new password
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long'];
    }

    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $sql = "UPDATE users SET password = '$new_password_hash', updated_at = NOW() WHERE user_id = $user_id";
    $result = executeQuery($sql);

    if ($result) {
        return ['success' => true, 'message' => 'Password updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update password'];
    }
}

// Function to update user balance
function updateUserBalance($user_id, $new_balance) {
    global $conn;
    check_connection();

    if ($new_balance < 0) {
        return ['success' => false, 'message' => 'Balance cannot be negative'];
    }

    $sql = "UPDATE users SET current_balance = $new_balance, updated_at = NOW() WHERE user_id = $user_id";
    $result = executeQuery($sql);

    if ($result) {
        return ['success' => true, 'message' => 'Balance updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update balance'];
    }
}