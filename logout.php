<?php
// Start session
session_start();

// Include functions
require_once 'includes/functions.php';

// Log the logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    // You can add logout logging here if needed
    // log_user_action($conn, $_SESSION['user_id'], 'logout');
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with logged out message
header("Location: login.php?logged_out=true");
exit();
?>