<?php
// toggle_favorite.php - Handles toggling favorite status for polls

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
        'message' => 'You must be logged in to favorite polls'
    ]);
    exit;
}

// Get the poll ID and action from the request
$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate the parameters
if ($pollId <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

// Check if the poll exists
$pollCheckQuery = "SELECT poll_id FROM polls WHERE poll_id = ?";
$pollCheckStmt = mysqli_prepare($con, $pollCheckQuery);
mysqli_stmt_bind_param($pollCheckStmt, "i", $pollId);
mysqli_stmt_execute($pollCheckStmt);
mysqli_stmt_store_result($pollCheckStmt);

if (mysqli_stmt_num_rows($pollCheckStmt) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Poll not found'
    ]);
    exit;
}

try {
    if ($action === 'add') {
        // Check if the favorite already exists
        $checkQuery = "SELECT COUNT(*) FROM favorites WHERE UserID = ? AND PollID = ?";
        $checkStmt = mysqli_prepare($con, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ii", $userId, $pollId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_bind_result($checkStmt, $favoriteCount);
        mysqli_stmt_fetch($checkStmt);
        mysqli_stmt_close($checkStmt);
        
        if ($favoriteCount > 0) {
            // Favorite already exists
            echo json_encode([
                'success' => true,
                'message' => 'Poll is already in your favorites'
            ]);
            exit;
        }
        
        // Add the favorite
        $insertQuery = "INSERT INTO favorites (UserID, PollID) VALUES (?, ?)";
        $insertStmt = mysqli_prepare($con, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, "ii", $userId, $pollId);
        $result = mysqli_stmt_execute($insertStmt);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Poll added to favorites'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding to favorites: ' . mysqli_error($con)
            ]);
        }
        
    } else { // action === 'remove'
        // Remove the favorite
        $deleteQuery = "DELETE FROM favorites WHERE UserID = ? AND PollID = ?";
        $deleteStmt = mysqli_prepare($con, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "ii", $userId, $pollId);
        $result = mysqli_stmt_execute($deleteStmt);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Poll removed from favorites'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error removing from favorites: ' . mysqli_error($con)
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing request: ' . $e->getMessage()
    ]);
}