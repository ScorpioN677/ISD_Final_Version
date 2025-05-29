<?php
// toggle_follow.php - Clean, optimized version

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'config.php';

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Enable error logging
error_log("toggle_follow.php called at " . date('Y-m-d H:i:s'));

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$followerId = intval($_SESSION['user_id']);

// Get and validate POST data
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;

// Log received data
error_log("Received - Action: $action, Poll ID: $pollId, Follower ID: $followerId");

// Validate input
if (!in_array($action, ['follow', 'unfollow'])) {
    error_log("Invalid action: $action");
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

if ($pollId <= 0) {
    error_log("Invalid poll ID: $pollId");
    echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
    exit;
}

try {
    // Get poll information
    $pollStmt = mysqli_prepare($con, "SELECT CreatedBy, isAnonymous FROM polls WHERE poll_id = ? LIMIT 1");
    if (!$pollStmt) {
        throw new Exception("Database prepare failed: " . mysqli_error($con));
    }
    
    mysqli_stmt_bind_param($pollStmt, "i", $pollId);
    mysqli_stmt_execute($pollStmt);
    $pollResult = mysqli_stmt_get_result($pollStmt);

    if (!$pollResult || mysqli_num_rows($pollResult) === 0) {
        mysqli_stmt_close($pollStmt);
        error_log("Poll not found: $pollId");
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit;
    }

    $poll = mysqli_fetch_assoc($pollResult);
    $followingId = intval($poll['CreatedBy']);
    $isAnonymous = intval($poll['isAnonymous']);
    mysqli_stmt_close($pollStmt);

    error_log("Poll data - Creator: $followingId, Anonymous: $isAnonymous");

    // Security checks
    if ($isAnonymous === 1) {
        error_log("Attempted to follow anonymous user");
        echo json_encode(['success' => false, 'message' => 'Cannot follow anonymous users']);
        exit;
    }

    if ($followerId === $followingId) {
        error_log("User attempted to follow themselves");
        echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
        exit;
    }

    // Verify target user exists
    $userStmt = mysqli_prepare($con, "SELECT 1 FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($userStmt, "i", $followingId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    
    if (mysqli_num_rows($userResult) === 0) {
        mysqli_stmt_close($userStmt);
        error_log("Target user not found: $followingId");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    mysqli_stmt_close($userStmt);

    // Start transaction
    mysqli_begin_transaction($con);

    if ($action === 'follow') {
        // Use INSERT IGNORE to avoid duplicate key errors
        $insertStmt = mysqli_prepare($con, "INSERT IGNORE INTO follows (FollowerID, FollowingID) VALUES (?, ?)");
        mysqli_stmt_bind_param($insertStmt, "ii", $followerId, $followingId);
        
        if (mysqli_stmt_execute($insertStmt)) {
            $affectedRows = mysqli_stmt_affected_rows($insertStmt);
            mysqli_stmt_close($insertStmt);
            
            if ($affectedRows > 0) {
                error_log("Successfully followed user $followingId");
                
                // Try to create notification (don't fail if this fails)
                try {
                    if (file_exists('notifications.php')) {
                        include_once 'notifications.php';
                        if (function_exists('create_follow_notification')) {
                            create_follow_notification($followerId, $followingId);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Notification creation failed: " . $e->getMessage());
                }
                
                mysqli_commit($con);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Successfully followed user',
                    'action' => 'follow'
                ]);
            } else {
                error_log("Already following user $followingId");
                mysqli_commit($con);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Already following user',
                    'action' => 'follow'
                ]);
            }
        } else {
            mysqli_stmt_close($insertStmt);
            throw new Exception("Failed to insert follow relationship");
        }
        
    } else { // unfollow
        // Remove follow relationship
        $deleteStmt = mysqli_prepare($con, "DELETE FROM follows WHERE FollowerID = ? AND FollowingID = ? LIMIT 1");
        mysqli_stmt_bind_param($deleteStmt, "ii", $followerId, $followingId);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            $affectedRows = mysqli_stmt_affected_rows($deleteStmt);
            mysqli_stmt_close($deleteStmt);
            
            mysqli_commit($con);
            
            if ($affectedRows > 0) {
                error_log("Successfully unfollowed user $followingId");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Successfully unfollowed user',
                    'action' => 'unfollow'
                ]);
            } else {
                error_log("Was not following user $followingId");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Was not following user',
                    'action' => 'unfollow'
                ]);
            }
        } else {
            mysqli_stmt_close($deleteStmt);
            throw new Exception("Failed to delete follow relationship");
        }
    }

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($con);
    
    // Log error
    error_log("Follow operation error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}

// Close database connection
if ($con) {
    mysqli_close($con);
}

error_log("toggle_follow.php completed");
?>