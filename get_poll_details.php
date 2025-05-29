<?php
// get_poll_details.php - Fetch poll details for editing

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
        'message' => 'You must be logged in to view poll details'
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
    // Get poll details with category information
    $pollQuery = "SELECT p.*, c.name as category_name 
                  FROM polls p 
                  JOIN categories c ON p.CategoryID = c.category_id 
                  WHERE p.poll_id = ? AND p.CreatedBy = ?";
    
    $pollStmt = mysqli_prepare($con, $pollQuery);
    mysqli_stmt_bind_param($pollStmt, "ii", $pollId, $userId);
    mysqli_stmt_execute($pollStmt);
    $pollResult = mysqli_stmt_get_result($pollStmt);
    
    if (!$pollResult || mysqli_num_rows($pollResult) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Poll not found or you do not have permission to edit it'
        ]);
        exit;
    }
    
    $poll = mysqli_fetch_assoc($pollResult);
    mysqli_stmt_close($pollStmt);
    
    // Get poll answers
    $answersQuery = "SELECT * FROM answers WHERE poll_id = ? ORDER BY answer_id";
    $answersStmt = mysqli_prepare($con, $answersQuery);
    mysqli_stmt_bind_param($answersStmt, "i", $pollId);
    mysqli_stmt_execute($answersStmt);
    $answersResult = mysqli_stmt_get_result($answersStmt);
    
    $answers = [];
    if ($answersResult) {
        while ($row = mysqli_fetch_assoc($answersResult)) {
            $answers[] = $row;
        }
    }
    mysqli_stmt_close($answersStmt);
    
    // Return the poll and answers data
    echo json_encode([
        'success' => true,
        'poll' => $poll,
        'answers' => $answers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching poll details: ' . $e->getMessage()
    ]);
}
?>