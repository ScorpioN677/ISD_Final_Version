<?php
// delete_poll.php - Handles poll deletion

// Include database configuration file
include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
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
        'message' => 'You must be logged in to delete polls'
    ]);
    exit;
}

// Get the poll ID from the request
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;

// Validate the poll ID
if ($pollId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid poll ID'
    ]);
    exit;
}

try {
    // Check if the poll exists and belongs to the current user
    $checkQuery = "SELECT CreatedBy FROM polls WHERE poll_id = ?";
    $checkStmt = mysqli_prepare($con, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $pollId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $pollCreator);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    if (!$pollCreator) {
        echo json_encode([
            'success' => false,
            'message' => 'Poll not found'
        ]);
        exit;
    }

    if ($pollCreator != $userId) {
        echo json_encode([
            'success' => false,
            'message' => 'You can only delete your own polls'
        ]);
        exit;
    }

    // Begin transaction to ensure data integrity
    mysqli_begin_transaction($con);

    // Delete related data in the correct order (due to foreign key constraints)
    
    // 1. Delete comments
    $deleteCommentsQuery = "DELETE FROM comments WHERE poll_id = ?";
    $deleteCommentsStmt = mysqli_prepare($con, $deleteCommentsQuery);
    mysqli_stmt_bind_param($deleteCommentsStmt, "i", $pollId);
    mysqli_stmt_execute($deleteCommentsStmt);
    mysqli_stmt_close($deleteCommentsStmt);

    // 2. Delete responses
    $deleteResponsesQuery = "DELETE FROM responses WHERE poll_id = ?";
    $deleteResponsesStmt = mysqli_prepare($con, $deleteResponsesQuery);
    mysqli_stmt_bind_param($deleteResponsesStmt, "i", $pollId);
    mysqli_stmt_execute($deleteResponsesStmt);
    mysqli_stmt_close($deleteResponsesStmt);

    // 3. Delete favorites
    $deleteFavoritesQuery = "DELETE FROM favorites WHERE PollID = ?";
    $deleteFavoritesStmt = mysqli_prepare($con, $deleteFavoritesQuery);
    mysqli_stmt_bind_param($deleteFavoritesStmt, "i", $pollId);
    mysqli_stmt_execute($deleteFavoritesStmt);
    mysqli_stmt_close($deleteFavoritesStmt);

    // 4. Delete answers
    $deleteAnswersQuery = "DELETE FROM answers WHERE poll_id = ?";
    $deleteAnswersStmt = mysqli_prepare($con, $deleteAnswersQuery);
    mysqli_stmt_bind_param($deleteAnswersStmt, "i", $pollId);
    mysqli_stmt_execute($deleteAnswersStmt);
    mysqli_stmt_close($deleteAnswersStmt);

    // 5. Delete notifications related to this poll
    $deleteNotificationsQuery = "DELETE FROM notifications WHERE related_id = ? AND (type = 'vote' OR type = 'comment')";
    $deleteNotificationsStmt = mysqli_prepare($con, $deleteNotificationsQuery);
    mysqli_stmt_bind_param($deleteNotificationsStmt, "i", $pollId);
    mysqli_stmt_execute($deleteNotificationsStmt);
    mysqli_stmt_close($deleteNotificationsStmt);

    // 6. Finally, delete the poll itself
    $deletePollQuery = "DELETE FROM polls WHERE poll_id = ?";
    $deletePollStmt = mysqli_prepare($con, $deletePollQuery);
    mysqli_stmt_bind_param($deletePollStmt, "i", $pollId);
    $pollDeleted = mysqli_stmt_execute($deletePollStmt);
    mysqli_stmt_close($deletePollStmt);

    if ($pollDeleted) {
        // Commit the transaction
        mysqli_commit($con);
        
        echo json_encode([
            'success' => true,
            'message' => 'Poll deleted successfully'
        ]);
    } else {
        // Rollback the transaction
        mysqli_rollback($con);
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete poll'
        ]);
    }

} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($con);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting poll: ' . $e->getMessage()
    ]);
}
?>