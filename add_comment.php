<?php
// add_comment.php - Handles adding comments and replies to polls

// Include database configuration file
include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log errors for debugging
function logError($message, $data = null) {
    $logFile = 'comment_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method - must be POST'
    ]);
    exit;
}

// Start session to get user ID
session_start();

// Get the user ID from the session
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Check if the user is logged in
// Check if the user is logged in
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to add comments'
    ]);
    exit;
}


// Get the required parameters
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$text = isset($_POST['text']) ? trim($_POST['text']) : '';
$parentCommentId = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== '' 
                 ? intval($_POST['parent_comment_id']) 
                 : null;

// Log received data for debugging
logError("Received comment data", [
    'user_id' => $userId,
    'poll_id' => $pollId,
    'text' => $text,
    'parent_comment_id' => $parentCommentId
]);

// Validate parameters
if ($pollId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid poll ID: ' . $pollId
    ]);
    exit;
}

if (empty($text)) {
    echo json_encode([
        'success' => false,
        'message' => 'Comment text is required'
    ]);
    exit;
}

// Limit comment length
if (strlen($text) > 250) { // Changed to 250 to match VARCHAR(255)
    echo json_encode([
        'success' => false,
        'message' => 'Comment text is too long (maximum 250 characters)'
    ]);
    exit;
}

// Sanitize the comment text
$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

// Check if the poll exists
$pollQuery = "SELECT poll_id FROM polls WHERE poll_id = ?";
$pollStmt = mysqli_prepare($con, $pollQuery);

if (!$pollStmt) {
    logError("Failed to prepare poll query", mysqli_error($con));
    echo json_encode([
        'success' => false,
        'message' => 'Database error when checking poll'
    ]);
    exit;
}

mysqli_stmt_bind_param($pollStmt, "i", $pollId);
mysqli_stmt_execute($pollStmt);
mysqli_stmt_store_result($pollStmt);

if (mysqli_stmt_num_rows($pollStmt) === 0) {
    mysqli_stmt_close($pollStmt);
    echo json_encode([
        'success' => false,
        'message' => 'Poll not found with ID: ' . $pollId
    ]);
    exit;
}
mysqli_stmt_close($pollStmt);

// If this is a reply, verify the parent comment exists and belongs to this poll
if ($parentCommentId !== null) {
    $parentQuery = "SELECT c.comment_id, c.user_id, CONCAT(u.first_name, ' ', u.last_name) AS author_name 
                   FROM comments c
                   JOIN users u ON c.user_id = u.user_id
                   WHERE c.comment_id = ? AND c.poll_id = ?";
    $parentStmt = mysqli_prepare($con, $parentQuery);
    
    if (!$parentStmt) {
        logError("Failed to prepare parent comment query", mysqli_error($con));
        echo json_encode([
            'success' => false,
            'message' => 'Database error when checking parent comment'
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($parentStmt, "ii", $parentCommentId, $pollId);
    mysqli_stmt_execute($parentStmt);
    $result = mysqli_stmt_get_result($parentStmt);
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($parentStmt);
        echo json_encode([
            'success' => false,
            'message' => 'Parent comment not found or does not belong to this poll. Comment ID: ' . $parentCommentId . ', Poll ID: ' . $pollId
        ]);
        exit;
    }
    
    // Get parent comment author name
    $parentData = mysqli_fetch_assoc($result);
    $parentAuthorName = $parentData['author_name'];
    $parentUserId = $parentData['user_id'];
    mysqli_stmt_close($parentStmt);
} else {
    $parentAuthorName = null;
    $parentUserId = null;
}

// Begin transaction
mysqli_begin_transaction($con);

try {
    // Insert the comment - MODIFIED FOR YOUR SCHEMA
    $insertQuery = "INSERT INTO comments (text, user_id, poll_id, parent_comment_id) 
                   VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($con, $insertQuery);
    
    if (!$insertStmt) {
        throw new Exception("Failed to prepare insert statement: " . mysqli_error($con));
    }
    
    mysqli_stmt_bind_param($insertStmt, "siis", $text, $userId, $pollId, $parentCommentId);
    $result = mysqli_stmt_execute($insertStmt);
    
    if (!$result) {
        throw new Exception("Error adding comment: " . mysqli_stmt_error($insertStmt));
    }
    
    // Get the inserted comment ID
    $commentId = mysqli_insert_id($con);
    mysqli_stmt_close($insertStmt);
    
    // Get user information
    $userQuery = "
        SELECT 
            u.first_name, 
            u.last_name,
            (SELECT pp.file FROM profilepictures pp WHERE pp.user_id = u.user_id ORDER BY pp.picture_id DESC LIMIT 1) AS profile_pic
        FROM 
            users u
        WHERE 
            u.user_id = ?
    ";
    $userStmt = mysqli_prepare($con, $userQuery);
    
    if (!$userStmt) {
        throw new Exception("Failed to prepare user query: " . mysqli_error($con));
    }
    
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    
    if (!$userResult || mysqli_num_rows($userResult) === 0) {
        throw new Exception("User data not found");
    }
    
    $userData = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    // Set profile picture default if none exists
    $profilePic = !empty($userData['profile_pic']) ? 'uploads/profile_pics/' . $userData['profile_pic'] : 'Images/profile_pic.png';
    
    // If this is a reply, handle notification
    if ($parentCommentId !== null && $userId !== $parentUserId) {
        // Include notifications.php for creating notifications
        include_once 'notifications.php';
        
        // Create reply notification
        if (function_exists('create_reply_notification')) {
            create_reply_notification($commentId, $userId, $parentCommentId, $parentUserId);
        }
    } else {
        // For top-level comments, notify the poll creator
        // Get poll creator ID
        $creatorQuery = "SELECT CreatedBy FROM polls WHERE poll_id = ?";
        $creatorStmt = mysqli_prepare($con, $creatorQuery);
        mysqli_stmt_bind_param($creatorStmt, "i", $pollId);
        mysqli_stmt_execute($creatorStmt);
        $creatorResult = mysqli_stmt_get_result($creatorStmt);
        
        if ($creatorResult && mysqli_num_rows($creatorResult) > 0) {
            $creatorData = mysqli_fetch_assoc($creatorResult);
            $creatorId = $creatorData['CreatedBy'];
            
            // Don't notify if commenting on your own poll
            if ($userId !== $creatorId) {
                include_once 'notifications.php';
                if (function_exists('create_comment_notification')) {
                    create_comment_notification($commentId, $userId, $pollId, $creatorId);
                }
            }
        }
        
        mysqli_stmt_close($creatorStmt);
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    // Return success and comment data
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'data' => [
            'comment_id' => $commentId,
            'text' => $text,
            'user' => [
                'user_id' => $userId,
                'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'profile_pic' => $profilePic
            ],
            'poll_id' => $pollId,
            'parent_comment_id' => $parentCommentId,
            'parent_author_name' => $parentAuthorName,
            'timestamp' => date('Y-m-d H:i:s'),
            'is_reply' => ($parentCommentId !== null)
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($con);
    
    logError("Transaction failed", $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error adding comment: ' . $e->getMessage()
    ]);
}
?>