<?php
// toggle_follow_user.php - Handle follow/unfollow actions for users from search

// Include database configuration and notification functions
include_once 'config.php';
include_once 'notifications.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Start session to get user ID
session_start();
$followerId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Check if user is logged in
if ($followerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to follow users']);
    exit;
}

// Get action and user ID from POST data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$followingId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

// Validate inputs
if (!in_array($action, ['follow', 'unfollow']) || $followingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Prevent users from following themselves
if ($followerId == $followingId) {
    echo json_encode(['success' => false, 'message' => 'You cannot follow yourself']);
    exit;
}

// Check if the user to follow exists
$userCheckQuery = "SELECT user_id, first_name, last_name FROM users WHERE user_id = ?";
$userCheckStmt = mysqli_prepare($con, $userCheckQuery);
mysqli_stmt_bind_param($userCheckStmt, "i", $followingId);
mysqli_stmt_execute($userCheckStmt);
$userCheckResult = mysqli_stmt_get_result($userCheckStmt);

if (!$userCheckResult || mysqli_num_rows($userCheckResult) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    mysqli_stmt_close($userCheckStmt);
    exit;
}

$userData = mysqli_fetch_assoc($userCheckResult);
mysqli_stmt_close($userCheckStmt);

// SECURITY CHECK: Check if user has any anonymous polls and if they should be protected
// For direct user following, we'll allow it unless the user is currently posting anonymously
// This is different from poll-based following where we check the specific poll's anonymous status
// You might want to adjust this logic based on your requirements

// Optional: Check if user has recent anonymous activity (uncomment if needed)
/*
$anonymousCheckStmt = mysqli_prepare($con, "SELECT COUNT(*) as anonymous_count FROM polls WHERE CreatedBy = ? AND isAnonymous = 1 AND date_of_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
mysqli_stmt_bind_param($anonymousCheckStmt, "i", $followingId);
mysqli_stmt_execute($anonymousCheckStmt);
mysqli_stmt_bind_result($anonymousCheckStmt, $anonymousCount);
mysqli_stmt_fetch($anonymousCheckStmt);
mysqli_stmt_close($anonymousCheckStmt);

if ($anonymousCount > 0) {
    echo json_encode(['success' => false, 'message' => 'This user cannot be followed at this time']);
    exit;
}
*/

// Begin transaction
mysqli_begin_transaction($con);

try {
    if ($action === 'follow') {
        // Check if already following
        $checkStmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM follows WHERE FollowerID = ? AND FollowingID = ?");
        mysqli_stmt_bind_param($checkStmt, "ii", $followerId, $followingId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_bind_result($checkStmt, $followCount);
        mysqli_stmt_fetch($checkStmt);
        mysqli_stmt_close($checkStmt);
        
        if ($followCount > 0) {
            // Already following - not an error, just return success
            mysqli_commit($con);
            echo json_encode([
                'success' => true, 
                'message' => 'Already following',
                'action' => 'follow',
                'user' => [
                    'user_id' => $followingId,
                    'name' => $userData['first_name'] . ' ' . $userData['last_name']
                ]
            ]);
            exit;
        }
        
        // Add follow relationship
        $insertStmt = mysqli_prepare($con, "INSERT INTO follows (FollowerID, FollowingID) VALUES (?, ?)");
        mysqli_stmt_bind_param($insertStmt, "ii", $followerId, $followingId);
        
        if (mysqli_stmt_execute($insertStmt)) {
            mysqli_stmt_close($insertStmt);
            
            // Create notification
            if (function_exists('create_follow_notification')) {
                create_follow_notification($followerId, $followingId);
            }
            
            // Commit transaction
            mysqli_commit($con);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Now following user',
                'action' => 'follow',
                'user' => [
                    'user_id' => $followingId,
                    'name' => $userData['first_name'] . ' ' . $userData['last_name']
                ]
            ]);
        } else {
            mysqli_stmt_close($insertStmt);
            throw new Exception("Failed to add follow relationship: " . mysqli_error($con));
        }
        
    } else { // action === 'unfollow'
        // Remove follow relationship
        $deleteStmt = mysqli_prepare($con, "DELETE FROM follows WHERE FollowerID = ? AND FollowingID = ?");
        mysqli_stmt_bind_param($deleteStmt, "ii", $followerId, $followingId);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            $affectedRows = mysqli_stmt_affected_rows($deleteStmt);
            mysqli_stmt_close($deleteStmt);
            
            // Commit transaction
            mysqli_commit($con);
            
            if ($affectedRows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Unfollowed user',
                    'action' => 'unfollow',
                    'user' => [
                        'user_id' => $followingId,
                        'name' => $userData['first_name'] . ' ' . $userData['last_name']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'User was not being followed',
                    'action' => 'unfollow',
                    'user' => [
                        'user_id' => $followingId,
                        'name' => $userData['first_name'] . ' ' . $userData['last_name']
                    ]
                ]);
            }
        } else {
            mysqli_stmt_close($deleteStmt);
            throw new Exception("Failed to remove follow relationship: " . mysqli_error($con));
        }
    }
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($con);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'action' => $action
    ]);
}

// Close database connection
mysqli_close($con);
?>