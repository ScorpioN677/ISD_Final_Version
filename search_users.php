<?php
// search_users.php - AJAX endpoint for searching users

// Include database configuration
include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if this is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Start session to get current user ID (optional, for excluding current user from results)
session_start();
$currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Validate search query
if (empty($query)) {
    echo json_encode([
        'success' => false,
        'message' => 'Search query is required',
        'data' => []
    ]);
    exit;
}

// Minimum search length
if (strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Search query must be at least 2 characters long',
        'data' => []
    ]);
    exit;
}

// Sanitize search query
$searchQuery = mysqli_real_escape_string($con, $query);

// Prepare the search SQL query
$sql = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.bio,
        u.gender,
        u.address,
        pp.file as profile_pic,
        (SELECT COUNT(*) FROM follows f WHERE f.FollowingID = u.user_id) as followers_count,
        (SELECT COUNT(*) FROM follows f WHERE f.FollowerID = u.user_id) as following_count,
        (SELECT COUNT(*) FROM polls p WHERE p.CreatedBy = u.user_id AND p.isPublic = 1) as polls_count
    FROM 
        users u
    LEFT JOIN 
        profilepictures pp ON u.user_id = pp.user_id
    WHERE 
        (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
            u.email LIKE ? OR
            u.bio LIKE ?
        )
";

// Exclude current user from results if logged in
if ($currentUserId > 0) {
    $sql .= " AND u.user_id != ?";
}

$sql .= "
    ORDER BY 
        CASE 
            WHEN u.first_name LIKE ? THEN 1
            WHEN u.last_name LIKE ? THEN 2
            WHEN CONCAT(u.first_name, ' ', u.last_name) LIKE ? THEN 3
            WHEN u.email LIKE ? THEN 4
            ELSE 5
        END,
        u.first_name ASC,
        u.last_name ASC
    LIMIT ?, ?
";

// Prepare statement
$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($con),
        'data' => []
    ]);
    exit;
}

// Prepare search patterns
$likeQuery = "%{$searchQuery}%";
$exactQuery = "{$searchQuery}%";

// Bind parameters
if ($currentUserId > 0) {
    mysqli_stmt_bind_param($stmt, "ssssssisssii", 
        $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, // WHERE conditions
        $currentUserId, // Exclude current user
        $exactQuery, $exactQuery, $exactQuery, $exactQuery, // ORDER BY conditions
        $offset, $limit // LIMIT conditions
    );
} else {
    mysqli_stmt_bind_param($stmt, "ssssssssii", 
        $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, // WHERE conditions
        $exactQuery, $exactQuery, $exactQuery, $exactQuery, // ORDER BY conditions
        $offset, $limit // LIMIT conditions
    );
}

// Execute query
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => false,
        'message' => 'Error executing search query',
        'data' => []
    ]);
    exit;
}

// Get results
$result = mysqli_stmt_get_result($stmt);
$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Set default profile picture if none exists
    $profilePic = !empty($row['profile_pic']) ? 'uploads/profile_pics/' . $row['profile_pic'] : 'Images/profile_pic.png';
    
    // Format user data
    $user = [
        'user_id' => intval($row['user_id']),
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'email' => $row['email'],
        'bio' => !empty($row['bio']) ? $row['bio'] : 'No bio yet',
        'profile_pic' => $profilePic,
        'gender' => $row['gender'] == 1 ? 'Female' : 'Male',
        'address' => $row['address'] ?? '',
        'stats' => [
            'followers' => intval($row['followers_count']),
            'following' => intval($row['following_count']),
            'polls' => intval($row['polls_count'])
        ]
    ];
    
    // Check if current user is following this user (if logged in)
    if ($currentUserId > 0) {
        $followQuery = "SELECT COUNT(*) as is_following FROM follows WHERE FollowerID = ? AND FollowingID = ?";
        $followStmt = mysqli_prepare($con, $followQuery);
        mysqli_stmt_bind_param($followStmt, "ii", $currentUserId, $row['user_id']);
        mysqli_stmt_execute($followStmt);
        $followResult = mysqli_stmt_get_result($followStmt);
        $followData = mysqli_fetch_assoc($followResult);
        
        $user['is_following'] = $followData['is_following'] > 0;
        mysqli_stmt_close($followStmt);
    } else {
        $user['is_following'] = false;
    }
    
    $users[] = $user;
}

// Get total count for pagination (optional)
$countSql = "
    SELECT COUNT(*) as total 
    FROM users u 
    WHERE 
        (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
            u.email LIKE ? OR
            u.bio LIKE ?
        )
";

if ($currentUserId > 0) {
    $countSql .= " AND u.user_id != ?";
}

$countStmt = mysqli_prepare($con, $countSql);

if ($currentUserId > 0) {
    mysqli_stmt_bind_param($countStmt, "sssssi", $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $currentUserId);
} else {
    mysqli_stmt_bind_param($countStmt, "sssss", $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery);
}

mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$countRow = mysqli_fetch_assoc($countResult);
$totalResults = intval($countRow['total']);

// Close statements
mysqli_stmt_close($stmt);
mysqli_stmt_close($countStmt);

// Return results
echo json_encode([
    'success' => true,
    'data' => $users,
    'pagination' => [
        'total' => $totalResults,
        'limit' => $limit,
        'offset' => $offset,
        'hasMore' => ($offset + $limit) < $totalResults
    ],
    'query' => $query
]);
?>