<?php
// vote_on_poll.php - Handle voting on polls

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
        'message' => 'You must be logged in to vote'
    ]);
    exit;
}

// Get the vote data from the request
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$answerId = isset($_POST['answer_id']) ? intval($_POST['answer_id']) : 0;

// Validate the input
if ($pollId <= 0 || $answerId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid poll or answer ID'
    ]);
    exit;
}

try {
    // Check if the poll exists and is public
    $pollCheckQuery = "SELECT p.*, u.first_name, u.last_name FROM polls p JOIN users u ON p.CreatedBy = u.user_id WHERE p.poll_id = ?";
    $pollCheckStmt = mysqli_prepare($con, $pollCheckQuery);
    mysqli_stmt_bind_param($pollCheckStmt, "i", $pollId);
    mysqli_stmt_execute($pollCheckStmt);
    $pollResult = mysqli_stmt_get_result($pollCheckStmt);
    
    if (!$pollResult || mysqli_num_rows($pollResult) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Poll not found'
        ]);
        exit;
    }
    
    $poll = mysqli_fetch_assoc($pollResult);
    mysqli_stmt_close($pollCheckStmt);
    
    // Check if poll is public (unless it's the user's own poll)
    if ($poll['isPublic'] != 1 && $poll['CreatedBy'] != $userId) {
        echo json_encode([
            'success' => false,
            'message' => 'This poll is private'
        ]);
        exit;
    }
    
    // Check if the answer belongs to this poll
    $answerCheckQuery = "SELECT answer_id FROM answers WHERE answer_id = ? AND poll_id = ?";
    $answerCheckStmt = mysqli_prepare($con, $answerCheckQuery);
    mysqli_stmt_bind_param($answerCheckStmt, "ii", $answerId, $pollId);
    mysqli_stmt_execute($answerCheckStmt);
    mysqli_stmt_store_result($answerCheckStmt);
    
    if (mysqli_stmt_num_rows($answerCheckStmt) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid answer for this poll'
        ]);
        exit;
    }
    mysqli_stmt_close($answerCheckStmt);
    
    // Check if user has already voted on this poll
    $voteCheckQuery = "SELECT response_id, answer_id FROM responses WHERE poll_id = ? AND user_id = ?";
    $voteCheckStmt = mysqli_prepare($con, $voteCheckQuery);
    mysqli_stmt_bind_param($voteCheckStmt, "ii", $pollId, $userId);
    mysqli_stmt_execute($voteCheckStmt);
    $voteResult = mysqli_stmt_get_result($voteCheckStmt);
    
    $hasVoted = false;
    $previousAnswerId = null;
    
    if ($voteResult && mysqli_num_rows($voteResult) > 0) {
        $voteData = mysqli_fetch_assoc($voteResult);
        $hasVoted = true;
        $previousAnswerId = $voteData['answer_id'];
    }
    mysqli_stmt_close($voteCheckStmt);
    
    // Begin transaction
    mysqli_begin_transaction($con);
    
    if ($hasVoted) {
        // User is changing their vote
        if ($previousAnswerId == $answerId) {
            // User clicked the same answer - remove vote
            $deleteVoteQuery = "DELETE FROM responses WHERE poll_id = ? AND user_id = ?";
            $deleteVoteStmt = mysqli_prepare($con, $deleteVoteQuery);
            mysqli_stmt_bind_param($deleteVoteStmt, "ii", $pollId, $userId);
            $deleteResult = mysqli_stmt_execute($deleteVoteStmt);
            mysqli_stmt_close($deleteVoteStmt);
            
            if ($deleteResult) {
                // Decrease vote count for the previous answer
                $decreaseVoteQuery = "UPDATE answers SET vote_count = vote_count - 1 WHERE answer_id = ?";
                $decreaseVoteStmt = mysqli_prepare($con, $decreaseVoteQuery);
                mysqli_stmt_bind_param($decreaseVoteStmt, "i", $previousAnswerId);
                $decreaseResult = mysqli_stmt_execute($decreaseVoteStmt);
                mysqli_stmt_close($decreaseVoteStmt);
                
                if ($decreaseResult) {
                    mysqli_commit($con);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Vote removed',
                        'action' => 'removed'
                    ]);
                } else {
                    mysqli_rollback($con);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error updating vote count'
                    ]);
                }
            } else {
                mysqli_rollback($con);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error removing vote'
                ]);
            }
        } else {
            // User is changing to a different answer
            $updateVoteQuery = "UPDATE responses SET answer_id = ?, response_date = NOW() WHERE poll_id = ? AND user_id = ?";
            $updateVoteStmt = mysqli_prepare($con, $updateVoteQuery);
            mysqli_stmt_bind_param($updateVoteStmt, "iii", $answerId, $pollId, $userId);
            $updateResult = mysqli_stmt_execute($updateVoteStmt);
            mysqli_stmt_close($updateVoteStmt);
            
            if ($updateResult) {
                // Decrease vote count for previous answer
                $decreaseVoteQuery = "UPDATE answers SET vote_count = vote_count - 1 WHERE answer_id = ?";
                $decreaseVoteStmt = mysqli_prepare($con, $decreaseVoteQuery);
                mysqli_stmt_bind_param($decreaseVoteStmt, "i", $previousAnswerId);
                $decreaseResult = mysqli_stmt_execute($decreaseVoteStmt);
                mysqli_stmt_close($decreaseVoteStmt);
                
                // Increase vote count for new answer
                $increaseVoteQuery = "UPDATE answers SET vote_count = vote_count + 1 WHERE answer_id = ?";
                $increaseVoteStmt = mysqli_prepare($con, $increaseVoteQuery);
                mysqli_stmt_bind_param($increaseVoteStmt, "i", $answerId);
                $increaseResult = mysqli_stmt_execute($increaseVoteStmt);
                mysqli_stmt_close($increaseVoteStmt);
                
                if ($decreaseResult && $increaseResult) {
                    mysqli_commit($con);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Vote updated',
                        'action' => 'changed'
                    ]);
                } else {
                    mysqli_rollback($con);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error updating vote counts'
                    ]);
                }
            } else {
                mysqli_rollback($con);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating vote'
                ]);
            }
        }
    } else {
        // User is voting for the first time
        $insertVoteQuery = "INSERT INTO responses (poll_id, answer_id, user_id, response_date) VALUES (?, ?, ?, NOW())";
        $insertVoteStmt = mysqli_prepare($con, $insertVoteQuery);
        mysqli_stmt_bind_param($insertVoteStmt, "iii", $pollId, $answerId, $userId);
        $insertResult = mysqli_stmt_execute($insertVoteStmt);
        mysqli_stmt_close($insertVoteStmt);
        
        if ($insertResult) {
            // Increase vote count for the answer
            $increaseVoteQuery = "UPDATE answers SET vote_count = vote_count + 1 WHERE answer_id = ?";
            $increaseVoteStmt = mysqli_prepare($con, $increaseVoteQuery);
            mysqli_stmt_bind_param($increaseVoteStmt, "i", $answerId);
            $increaseResult = mysqli_stmt_execute($increaseVoteStmt);
            mysqli_stmt_close($increaseVoteStmt);
            
            if ($increaseResult) {
                // Create notification for poll creator (if not voting on own poll)
                if ($poll['CreatedBy'] != $userId) {
                    $notificationMessage = $poll['first_name'] . ' ' . $poll['last_name'] . ' voted on your poll: "' . substr($poll['question'], 0, 30) . '..."';
                    $notificationQuery = "INSERT INTO notifications (user_id, type, related_id, message, created_at) VALUES (?, 'vote', ?, ?, NOW())";
                    $notificationStmt = mysqli_prepare($con, $notificationQuery);
                    mysqli_stmt_bind_param($notificationStmt, "iis", $poll['CreatedBy'], $pollId, $notificationMessage);
                    mysqli_stmt_execute($notificationStmt);
                    mysqli_stmt_close($notificationStmt);
                }
                
                mysqli_commit($con);
                echo json_encode([
                    'success' => true,
                    'message' => 'Vote recorded',
                    'action' => 'added'
                ]);
            } else {
                mysqli_rollback($con);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating vote count'
                ]);
            }
        } else {
            mysqli_rollback($con);
            echo json_encode([
                'success' => false,
                'message' => 'Error recording vote'
                ]);
        }
    }
    
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($con);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing vote: ' . $e->getMessage()
    ]);
}
?>