<?php
// update_poll.php - Handles poll updates

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
        'message' => 'You must be logged in to update polls'
    ]);
    exit;
}

// Get the poll data from the request
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$question = isset($_POST['question']) ? trim($_POST['question']) : '';
$categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$isPublic = isset($_POST['is_public']) ? intval($_POST['is_public']) : 1;
$isAnonymous = isset($_POST['is_anonymous']) ? intval($_POST['is_anonymous']) : 0;
$answers = isset($_POST['answers']) ? $_POST['answers'] : [];

// Validate the input
if ($pollId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid poll ID'
    ]);
    exit;
}

if (empty($question)) {
    echo json_encode([
        'success' => false,
        'message' => 'Question is required'
    ]);
    exit;
}

if ($categoryId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid category'
    ]);
    exit;
}

if (!is_array($answers) || count($answers) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'At least 2 answers are required'
    ]);
    exit;
}

// Validate answers
$validAnswers = [];
foreach ($answers as $answer) {
    $trimmedAnswer = trim($answer['text'] ?? '');
    if (!empty($trimmedAnswer)) {
        $validAnswers[] = [
            'id' => isset($answer['id']) ? intval($answer['id']) : 0,
            'text' => $trimmedAnswer
        ];
    }
}

if (count($validAnswers) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'At least 2 valid answers are required'
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
            'message' => 'You can only update your own polls'
        ]);
        exit;
    }

    // Verify category exists
    $categoryCheckQuery = "SELECT category_id FROM categories WHERE category_id = ?";
    $categoryCheckStmt = mysqli_prepare($con, $categoryCheckQuery);
    mysqli_stmt_bind_param($categoryCheckStmt, "i", $categoryId);
    mysqli_stmt_execute($categoryCheckStmt);
    mysqli_stmt_store_result($categoryCheckStmt);

    if (mysqli_stmt_num_rows($categoryCheckStmt) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid category selected'
        ]);
        exit;
    }
    mysqli_stmt_close($categoryCheckStmt);

    // Begin transaction
    mysqli_begin_transaction($con);

    // Update the poll
    $updatePollQuery = "UPDATE polls SET question = ?, CategoryID = ?, isPublic = ?, isAnonymous = ? WHERE poll_id = ?";
    $updatePollStmt = mysqli_prepare($con, $updatePollQuery);
    mysqli_stmt_bind_param($updatePollStmt, "siiii", $question, $categoryId, $isPublic, $isAnonymous, $pollId);
    $pollUpdated = mysqli_stmt_execute($updatePollStmt);
    mysqli_stmt_close($updatePollStmt);

    if (!$pollUpdated) {
        mysqli_rollback($con);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update poll'
        ]);
        exit;
    }

    // Get existing answers to compare
    $existingAnswersQuery = "SELECT answer_id, text FROM answers WHERE poll_id = ?";
    $existingAnswersStmt = mysqli_prepare($con, $existingAnswersQuery);
    mysqli_stmt_bind_param($existingAnswersStmt, "i", $pollId);
    mysqli_stmt_execute($existingAnswersStmt);
    $existingAnswersResult = mysqli_stmt_get_result($existingAnswersStmt);
    
    $existingAnswers = [];
    while ($row = mysqli_fetch_assoc($existingAnswersResult)) {
        $existingAnswers[$row['answer_id']] = $row['text'];
    }
    mysqli_stmt_close($existingAnswersStmt);

    // Update existing answers and add new ones
    $processedAnswerIds = [];
    
    foreach ($validAnswers as $answer) {
        if ($answer['id'] > 0 && isset($existingAnswers[$answer['id']])) {
            // Update existing answer
            $updateAnswerQuery = "UPDATE answers SET text = ? WHERE answer_id = ? AND poll_id = ?";
            $updateAnswerStmt = mysqli_prepare($con, $updateAnswerQuery);
            mysqli_stmt_bind_param($updateAnswerStmt, "sii", $answer['text'], $answer['id'], $pollId);
            mysqli_stmt_execute($updateAnswerStmt);
            mysqli_stmt_close($updateAnswerStmt);
            
            $processedAnswerIds[] = $answer['id'];
        } else {
            // Add new answer
            $insertAnswerQuery = "INSERT INTO answers (text, poll_id, vote_count) VALUES (?, ?, 0)";
            $insertAnswerStmt = mysqli_prepare($con, $insertAnswerQuery);
            mysqli_stmt_bind_param($insertAnswerStmt, "si", $answer['text'], $pollId);
            mysqli_stmt_execute($insertAnswerStmt);
            mysqli_stmt_close($insertAnswerStmt);
        }
    }

    // Delete answers that were removed
    foreach ($existingAnswers as $answerId => $answerText) {
        if (!in_array($answerId, $processedAnswerIds)) {
            // Delete responses first (foreign key constraint)
            $deleteResponsesQuery = "DELETE FROM responses WHERE answer_id = ?";
            $deleteResponsesStmt = mysqli_prepare($con, $deleteResponsesQuery);
            mysqli_stmt_bind_param($deleteResponsesStmt, "i", $answerId);
            mysqli_stmt_execute($deleteResponsesStmt);
            mysqli_stmt_close($deleteResponsesStmt);
            
            // Delete the answer
            $deleteAnswerQuery = "DELETE FROM answers WHERE answer_id = ?";
            $deleteAnswerStmt = mysqli_prepare($con, $deleteAnswerQuery);
            mysqli_stmt_bind_param($deleteAnswerStmt, "i", $answerId);
            mysqli_stmt_execute($deleteAnswerStmt);
            mysqli_stmt_close($deleteAnswerStmt);
        }
    }

    // Commit the transaction
    mysqli_commit($con);

    // Get updated poll data to return
    $getUpdatedPollQuery = "SELECT p.*, c.name as category_name FROM polls p JOIN categories c ON p.CategoryID = c.category_id WHERE p.poll_id = ?";
    $getUpdatedPollStmt = mysqli_prepare($con, $getUpdatedPollQuery);
    mysqli_stmt_bind_param($getUpdatedPollStmt, "i", $pollId);
    mysqli_stmt_execute($getUpdatedPollStmt);
    $updatedPollResult = mysqli_stmt_get_result($getUpdatedPollStmt);
    $updatedPoll = mysqli_fetch_assoc($updatedPollResult);
    mysqli_stmt_close($getUpdatedPollStmt);

    // Get updated answers
    $getUpdatedAnswersQuery = "SELECT * FROM answers WHERE poll_id = ? ORDER BY answer_id";
    $getUpdatedAnswersStmt = mysqli_prepare($con, $getUpdatedAnswersQuery);
    mysqli_stmt_bind_param($getUpdatedAnswersStmt, "i", $pollId);
    mysqli_stmt_execute($getUpdatedAnswersStmt);
    $updatedAnswersResult = mysqli_stmt_get_result($getUpdatedAnswersStmt);
    
    $updatedAnswers = [];
    while ($row = mysqli_fetch_assoc($updatedAnswersResult)) {
        $updatedAnswers[] = $row;
    }
    mysqli_stmt_close($getUpdatedAnswersStmt);

    echo json_encode([
        'success' => true,
        'message' => 'Poll updated successfully',
        'poll' => $updatedPoll,
        'answers' => $updatedAnswers
    ]);

} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($con);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error updating poll: ' . $e->getMessage()
    ]);
}
?>