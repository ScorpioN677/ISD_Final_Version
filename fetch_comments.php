<?php
// fetch_comments.php - Enhanced version with reply support

// Include database configuration file
include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if this is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method - must be GET'
    ]);
    exit;
}

// Start session to get user ID
session_start();

// Get the user ID from the session
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Check if the user is logged in
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view comments'
    ]);
    exit;
}

// Get the required parameters
$pollId = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Validate poll ID
if ($pollId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid poll ID'
    ]);
    exit;
}

// Check if the poll exists
$pollQuery = "SELECT poll_id FROM polls WHERE poll_id = ?";
$pollStmt = mysqli_prepare($con, $pollQuery);

if (!$pollStmt) {
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
        'message' => 'Poll not found'
    ]);
    exit;
}
mysqli_stmt_close($pollStmt);

try {
    // First, get top-level comments (parent_comment_id IS NULL)
    $commentsQuery = "
        SELECT 
            c.comment_id,
            c.text,
            c.user_id,
            c.parent_comment_id,
            u.first_name,
            u.last_name,
            (SELECT pp.file FROM profilepictures pp WHERE pp.user_id = u.user_id ORDER BY pp.picture_id DESC LIMIT 1) AS profile_pic,
            (SELECT COUNT(*) FROM comments replies WHERE replies.parent_comment_id = c.comment_id) AS reply_count
        FROM 
            comments c
        JOIN 
            users u ON c.user_id = u.user_id
        WHERE 
            c.poll_id = ? AND c.parent_comment_id IS NULL
        ORDER BY 
            c.comment_id DESC
        LIMIT ? OFFSET ?
    ";

    $commentsStmt = mysqli_prepare($con, $commentsQuery);
    if (!$commentsStmt) {
        throw new Exception("Failed to prepare comments query: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($commentsStmt, "iii", $pollId, $limit, $offset);
    mysqli_stmt_execute($commentsStmt);
    $commentsResult = mysqli_stmt_get_result($commentsStmt);

    $comments = [];
    $commentIds = [];

    // Process top-level comments
    while ($comment = mysqli_fetch_assoc($commentsResult)) {
        $commentIds[] = $comment['comment_id'];
        
        // Set profile picture default if none exists
        $profilePic = !empty($comment['profile_pic']) ? 'uploads/profile_pics/' . $comment['profile_pic'] : 'Images/profile_pic.png';
        
        $commentData = [
            'comment_id' => $comment['comment_id'],
            'text' => htmlspecialchars_decode($comment['text']),
            'user' => [
                'user_id' => $comment['user_id'],
                'name' => $comment['first_name'] . ' ' . $comment['last_name'],
                'profile_pic' => $profilePic
            ],
            'reply_count' => intval($comment['reply_count']),
            'replies' => [] // Will be populated below
        ];
        
        $comments[] = $commentData;
    }
    mysqli_stmt_close($commentsStmt);

    // If we have comments, get their replies
    if (!empty($commentIds)) {
        $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';
        $repliesQuery = "
            SELECT 
                c.comment_id,
                c.text,
                c.user_id,
                c.parent_comment_id,
                u.first_name,
                u.last_name,
                (SELECT pp.file FROM profilepictures pp WHERE pp.user_id = u.user_id ORDER BY pp.picture_id DESC LIMIT 1) AS profile_pic
            FROM 
                comments c
            JOIN 
                users u ON c.user_id = u.user_id
            WHERE 
                c.parent_comment_id IN ($placeholders)
            ORDER BY 
                c.parent_comment_id, c.comment_id ASC
        ";

        $repliesStmt = mysqli_prepare($con, $repliesQuery);
        if (!$repliesStmt) {
            throw new Exception("Failed to prepare replies query: " . mysqli_error($con));
        }

        // Bind parameters for the IN clause
        $types = str_repeat('i', count($commentIds));
        mysqli_stmt_bind_param($repliesStmt, $types, ...$commentIds);
        mysqli_stmt_execute($repliesStmt);
        $repliesResult = mysqli_stmt_get_result($repliesStmt);

        // Group replies by parent comment ID
        $repliesByParent = [];
        while ($reply = mysqli_fetch_assoc($repliesResult)) {
            $parentId = $reply['parent_comment_id'];
            
            // Set profile picture default if none exists
            $profilePic = !empty($reply['profile_pic']) ? 'uploads/profile_pics/' . $reply['profile_pic'] : 'Images/profile_pic.png';
            
            $replyData = [
                'comment_id' => $reply['comment_id'],
                'text' => htmlspecialchars_decode($reply['text']),
                'parent_comment_id' => $reply['parent_comment_id'],
                'user' => [
                    'user_id' => $reply['user_id'],
                    'name' => $reply['first_name'] . ' ' . $reply['last_name'],
                    'profile_pic' => $profilePic
                ]
            ];
            
            if (!isset($repliesByParent[$parentId])) {
                $repliesByParent[$parentId] = [];
            }
            $repliesByParent[$parentId][] = $replyData;
        }
        mysqli_stmt_close($repliesStmt);

        // Add replies to their parent comments
        for ($i = 0; $i < count($comments); $i++) {
            $commentId = $comments[$i]['comment_id'];
            if (isset($repliesByParent[$commentId])) {
                $comments[$i]['replies'] = $repliesByParent[$commentId];
            }
        }
    }

    // Check if there are more comments for pagination
    $countQuery = "SELECT COUNT(*) as total FROM comments WHERE poll_id = ? AND parent_comment_id IS NULL";
    $countStmt = mysqli_prepare($con, $countQuery);
    mysqli_stmt_bind_param($countStmt, "i", $pollId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalComments = mysqli_fetch_assoc($countResult)['total'];
    mysqli_stmt_close($countStmt);

    $hasMore = ($offset + $limit) < $totalComments;

    // Return the comments with pagination info
    echo json_encode([
        'success' => true,
        'data' => $comments,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($totalComments),
            'hasMore' => $hasMore
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_comments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading comments: ' . $e->getMessage()
    ]);
}

// Close database connection
mysqli_close($con);
?>