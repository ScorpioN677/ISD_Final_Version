<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$session_timeout = 30 * 60; // 30 minutes in seconds
$regeneration_timeout = 15 * 60; // Regenerate session ID every 15 minutes

// Function to end session and redirect to login
function end_session($message = "Please log in to continue.") {
    // Clear session data
    $_SESSION = array();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Set cache control headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    
    // Redirect to login page
    header("Location: login.php?error=" . urlencode($message));
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    end_session();
}

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    end_session("Your session has expired due to inactivity. Please log in again.");
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > $regeneration_timeout)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Optional: Validate user agent hasn't changed dramatically (basic check)
if (isset($_SESSION['user_agent'])) {
    if ($_SESSION['user_agent'] != $_SERVER['HTTP_USER_AGENT']) {
        end_session("Session validation failed. Please log in again.");
    }
} else {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

// Optional: Basic IP validation (can be improved to handle proxies)
if (isset($_SESSION['ip_address'])) {
    if ($_SESSION['ip_address'] != $_SERVER['REMOTE_ADDR']) {
        // IP has changed - could be due to legitimate reasons or session hijacking
        end_session("Your IP address has changed. Please log in again for security.");
    }
} else {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
}

// Check if user still exists in database and account is active
require_once "config.php";

try {
    // Get the user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Prepare the SQL statement
    $sql = "SELECT user_id FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($con, $sql);
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($con));
    }
    
    // Bind parameters and execute
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }
    
    // Get the result
    $result = mysqli_stmt_get_result($stmt);
    
    // Check if user exists
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        end_session("User account no longer exists. Please contact support.");
    }
    
    // Clean up
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    // Log the error (in a production environment)
    // error_log('Session protection error: ' . $e->getMessage());
    
    // End the session but with a generic message (don't expose the error to the user)
    end_session("An error occurred during session validation. Please log in again.");
}

// Enforce HTTPS if not on localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Set security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");
?>