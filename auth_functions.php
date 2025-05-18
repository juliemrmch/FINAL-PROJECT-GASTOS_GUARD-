<?php
// includes/auth_functions.php
// Authentication functions

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "You must be logged in to view this page";
        $_SESSION['message_type'] = "error";
        header("Location: /GASTOS_GUARD/login.php");
        exit();
    }
}

// Function to get current user data
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    
    check_connection();
    $stmt = $conn->prepare("SELECT user_id, username, email, full_name, user_type, profile_image, current_balance, created_at, password
                          FROM users 
                          WHERE user_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in getCurrentUser: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    
    $stmt->close();
    return null;
}

// Function to require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['message'] = "You must be an administrator to view this page";
        $_SESSION['message_type'] = "error";
        header("Location: /GASTOS_GUARD/user/dashboard.php");
        exit();
    }
}

// Function to authenticate user
function authenticateUser($username, $password) {
    global $conn;
    
    $username = sanitize_input($username);
    
    // Use prepared statement to prevent SQL injection
    check_connection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in authenticateUser: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login time
            check_connection();
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            if (!$stmt) {
                error_log("Failed to prepare statement for last login update: " . $conn->error);
                return false;
            }
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $stmt->close();
            return true;
        }
    }
    
    $stmt->close();
    return false;
}

// Function to validate registration inputs
function validate_registration($username, $email, $full_name, $password, $confirm_password, $current_balance) {
    global $conn;
    $errors = [];

    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $errors[] = "Full name must be between 2 and 100 characters";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
        $errors[] = "Full name can only contain letters and spaces";
    }

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    } elseif (usernameExists($username)) {
        $errors[] = "Username is already taken";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email must be less than 100 characters";
    } elseif (emailExists($email)) {
        $errors[] = "Email is already registered";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    }

    // Validate confirm password
    if (empty($confirm_password)) {
        $errors[] = "Confirm password is required";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Validate current balance
    if (!is_numeric($current_balance) || $current_balance < 0) {
        $errors[] = "Current balance must be a non-negative number";
    }

    return $errors;
}

// Function to register new user
function register_user($conn, $username, $email, $full_name, $password, $current_balance) {
    $username = sanitize_input($username);
    $email = sanitize_input($email);
    $full_name = sanitize_input($full_name);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into users table, including current_balance
    check_connection();
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_type, active, current_balance) VALUES (?, ?, ?, ?, 'student', 1, ?)");
    if (!$stmt) {
        error_log("Failed to prepare statement in register_user: " . $conn->error);
        return [
            'success' => false,
            'message' => 'Registration failed: Database error'
        ];
    }
    $stmt->bind_param("ssssd", $username, $email, $hashed_password, $full_name, $current_balance);
    
    if ($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'message' => 'Registration successful! Redirecting to login...'
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $error
        ];
    }
}

// Function to log out user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

// Function to check if username exists
function usernameExists($username) {
    global $conn;
    
    $username = sanitize_input($username);
    
    check_connection();
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in usernameExists: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

// Function to check if email exists
function emailExists($email) {
    global $conn;
    
    $email = sanitize_input($email);
    
    check_connection();
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in emailExists: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

// Function to get user by ID
function getUserById($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    check_connection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement in getUserById: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    
    $stmt->close();
    return false;
}

// Function to update user profile
function updateUserProfile($user_id, $data) {
    global $conn;
    
    $user_id = (int)$user_id;
    $updates = [];
    $params = [];
    $types = "";
    
    // Build update parts
    foreach ($data as $key => $value) {
        if ($key === 'password') {
            $hashed_password = password_hash($value, PASSWORD_DEFAULT);
            $updates[] = "$key = ?";
            $params[] = $hashed_password;
            $types .= "s";
        } elseif ($key === 'current_balance') {
            $updates[] = "$key = ?";
            $params[] = (float)$value;
            $types .= "d";
        } else {
            $sanitized_value = sanitize_input($value);
            $updates[] = "$key = ?";
            $params[] = $sanitized_value;
            $types .= "s";
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $update_string = implode(', ', $updates);
    check_connection();
    
    // Start transaction
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE users SET $update_string, updated_at = NOW() WHERE user_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            throw new Exception("Database prepare error");
        }
        
        $params[] = $user_id;
        $types .= "i";
        $stmt->bind_param($types, ...$params);
        
        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed: " . $stmt->error);
            throw new Exception("Database execute error");
        }
        
        $conn->commit();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        return false;
    }
}
?>