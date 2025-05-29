<?php
session_start();
include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to vote'
    ]);
    exit();
}

// Check if required POST data is present
if (!isset($_POST['poll_id']) || !isset($_POST['answer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$poll_id = (int)$_POST['poll_id'];
$answer_id = (int)$_POST['answer_id'];

try {
    // Start transaction
    mysqli_begin_transaction($con);
    
    // Check if user has already voted on this poll
    $check_vote_sql = "SELECT response_id FROM responses WHERE user_id = ? AND poll_id = ?";
    $check_stmt = mysqli_prepare($con, $check_vote_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $poll_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // User has already voted - update their vote
        $existing_vote = mysqli_fetch_assoc($check_result);
        
        // Get the old answer ID to decrement its vote count
        $old_vote_sql = "SELECT answer_id FROM responses WHERE response_id = ?";
        $old_stmt = mysqli_prepare($con, $old_vote_sql);
        mysqli_stmt_bind_param($old_stmt, "i", $existing_vote['response_id']);
        mysqli_stmt_execute($old_stmt);
        $old_result = mysqli_stmt_get_result($old_stmt);
        $old_answer = mysqli_fetch_assoc($old_result);
        
        // Decrement old answer vote count
        $decrement_sql = "UPDATE answers SET vote_count = vote_count - 1 WHERE answer_id = ?";
        $decrement_stmt = mysqli_prepare($con, $decrement_sql);
        mysqli_stmt_bind_param($decrement_stmt, "i", $old_answer['answer_id']);
        mysqli_stmt_execute($decrement_stmt);
        
        // Update the response with new answer
        $update_sql = "UPDATE responses SET answer_id = ?, response_date = NOW() WHERE response_id = ?";
        $update_stmt = mysqli_prepare($con, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $answer_id, $existing_vote['response_id']);
        mysqli_stmt_execute($update_stmt);
        
        mysqli_stmt_close($old_stmt);
        mysqli_stmt_close($decrement_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // User hasn't voted yet - create new vote
        $insert_sql = "INSERT INTO responses (poll_id, answer_id, user_id, response_date) VALUES (?, ?, ?, NOW())";
        $insert_stmt = mysqli_prepare($con, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iii", $poll_id, $answer_id, $user_id);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    
    // Increment the vote count for the selected answer
    $increment_sql = "UPDATE answers SET vote_count = vote_count + 1 WHERE answer_id = ?";
    $increment_stmt = mysqli_prepare($con, $increment_sql);
    mysqli_stmt_bind_param($increment_stmt, "i", $answer_id);
    mysqli_stmt_execute($increment_stmt);
    mysqli_stmt_close($increment_stmt);
    
    // Get updated vote counts and calculate percentages
    $answers_sql = "SELECT answer_id, text, vote_count FROM answers WHERE poll_id = ?";
    $answers_stmt = mysqli_prepare($con, $answers_sql);
    mysqli_stmt_bind_param($answers_stmt, "i", $poll_id);
    mysqli_stmt_execute($answers_stmt);
    $answers_result = mysqli_stmt_get_result($answers_stmt);
    
    $answers = [];
    $total_votes = 0;
    
    while ($answer = mysqli_fetch_assoc($answers_result)) {
        $answers[] = $answer;
        $total_votes += $answer['vote_count'];
    }
    
    // Calculate percentages
    foreach ($answers as &$answer) {
        if ($total_votes > 0) {
            $answer['percentage'] = round(($answer['vote_count'] / $total_votes) * 100, 1);
        } else {
            $answer['percentage'] = 0;
        }
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    mysqli_stmt_close($check_stmt);
    mysqli_stmt_close($answers_stmt);
    
    // Return success response with updated data
    echo json_encode([
        'success' => true,
        'message' => 'Vote submitted successfully',
        'data' => [
            'answers' => $answers,
            'total_votes' => $total_votes,
            'user_vote_id' => $answer_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($con);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
mysqli_close($con);
?>