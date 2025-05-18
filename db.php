<?php
// Database connection file
// Define database connection constants if not already defined in config.php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// Global connection variable
$conn = null;

// Function to establish or re-establish database connection with retry
function establish_connection() {
    global $conn;

    // Close existing connection if it exists
    if ($conn instanceof mysqli) {
        $conn->close();
    }

    $max_retries = 3;
    $retry_delay = 2; // Seconds to wait between retries

    for ($retry = 0; $retry < $max_retries; $retry++) {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME); // @ suppresses initial error output

        if ($conn->connect_error) {
            $error_msg = "Connection attempt $retry failed: " . $conn->connect_error . " (Error No: " . $conn->connect_errno . ")";
            error_log($error_msg);
            if ($retry < $max_retries - 1) {
                sleep($retry_delay);
                continue;
            }
            die("Connection failed after $max_retries attempts: " . $conn->connect_error . ". " .
                "Please ensure MySQL is running in XAMPP and accessible at " . DB_HOST . ". " .
                "Check C:\\xampp\\mysql\\data\\mysql_error.log for details.");
        }

        // Connection successful, configure it
        $conn->set_charset("utf8");
        error_log("Connection established successfully on attempt $retry.");
        return $conn;
    }
}

// Function to check connection and reconnect if necessary
function check_connection() {
    global $conn;

    // If $conn is not initialized, establish connection
    if (!$conn instanceof mysqli) {
        return establish_connection();
    }

    // Check if connection is still alive
    if (!$conn->ping()) {
        error_log("MySQL server has gone away, attempting to reconnect...");
        return establish_connection();
    }

    return $conn;
}

// Establish initial connection
establish_connection();

// Include general and authentication functions
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_functions.php';

// Database-specific sanitization function for SQL injection prevention
function db_sanitize_input($data) {
    global $conn;
    check_connection();
    return $conn->real_escape_string($data);
}

// Function to execute a query and return result
function executeQuery($sql) {
    global $conn;
    check_connection();
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
    }
    return $result;
}

// Function to get a single row from a query
function getRow($sql) {
    $result = executeQuery($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

// Function to get multiple rows from a query
function getRows($sql) {
    $result = executeQuery($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Close connection on script termination
register_shutdown_function(function() {
    global $conn;
    if ($conn instanceof mysqli) {
        $conn->close();
    }
});
?>