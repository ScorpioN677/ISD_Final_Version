<?php
// test_follow.php - Simple test script to verify follow functionality
session_start();
include_once 'config.php';

header('Content-Type: application/json');

// Test data
$testData = [
    'session_user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'database_connection' => $con ? 'CONNECTED' : 'FAILED',
    'post_data' => $_POST,
    'get_data' => $_GET,
    'request_method' => $_SERVER['REQUEST_METHOD']
];

// Test database query
if ($con && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Test basic query
    $testQuery = "SELECT COUNT(*) as count FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($con, $testQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userCount);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        $testData['user_exists'] = $userCount > 0 ? 'YES' : 'NO';
    } else {
        $testData['user_exists'] = 'QUERY_FAILED';
    }
    
    // Test follows table
    $followsQuery = "SELECT COUNT(*) as count FROM follows WHERE FollowerID = ?";
    $stmt2 = mysqli_prepare($con, $followsQuery);
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, "i", $userId);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_bind_result($stmt2, $followCount);
        mysqli_stmt_fetch($stmt2);
        mysqli_stmt_close($stmt2);
        
        $testData['current_follows'] = $followCount;
    }
}

echo json_encode($testData, JSON_PRETTY_PRINT);
?>