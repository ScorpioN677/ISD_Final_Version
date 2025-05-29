<?php
session_start();
include_once 'config.php';

// Debug logging
error_log("fetch_polls.php called at " . date('Y-m-d H:i:s'));

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view polls'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Get category filter if provided
$categories = isset($_GET['categories']) ? $_GET['categories'] : [];

try {
    // Build the WHERE clause for categories
    $category_where = "";
    $category_params = "";
    $category_values = [];
    
    if (!empty($categories) && is_array($categories)) {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $category_where = " AND p.CategoryID IN ($placeholders)";
        $category_params = str_repeat('i', count($categories));
        $category_values = array_map('intval', $categories);
    }

    // UPDATED: Main query to fetch polls with followers-only logic
    $polls_sql = "
        SELECT 
            p.poll_id,
            p.question,
            p.date_of_creation,
            p.isAnonymous,
            p.isPublic,
            p.CreatedBy,
            p.CategoryID,
            c.name as category_name,
            u.first_name,
            u.last_name,
            u.user_id as creator_user_id,
            pp.file as profile_pic,
            r.answer_id as user_vote_answer_id,
            (SELECT COUNT(*) FROM comments cm WHERE cm.poll_id = p.poll_id) as comment_count,
            (SELECT COUNT(*) FROM favorites f WHERE f.PollID = p.poll_id AND f.UserID = ?) as is_favorite,
            (SELECT COUNT(*) FROM follows fo WHERE fo.FollowingID = p.CreatedBy AND fo.FollowerID = ?) as is_following
        FROM polls p
        LEFT JOIN categories c ON p.CategoryID = c.category_id
        LEFT JOIN users u ON p.CreatedBy = u.user_id
        LEFT JOIN profilepictures pp ON u.user_id = pp.user_id
        LEFT JOIN responses r ON p.poll_id = r.poll_id AND r.user_id = ?
        WHERE (
            p.isPublic = 1 
            OR (
                p.isPublic = 0 
                AND (
                    p.CreatedBy = ? 
                    OR EXISTS (
                        SELECT 1 FROM follows f 
                        WHERE f.FollowingID = p.CreatedBy 
                        AND f.FollowerID = ?
                    )
                )
            )
        )" . $category_where . "
        ORDER BY RAND()
        LIMIT ? OFFSET ?
    ";

    // UPDATED: Prepare parameters with additional user_id parameters for followers-only logic
    $params = [$user_id, $user_id, $user_id, $user_id, $user_id];
    $param_types = "iiiii";
    
    if (!empty($category_values)) {
        $params = array_merge($params, $category_values);
        $param_types .= $category_params;
    }
    
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= "ii";

    $polls_stmt = mysqli_prepare($con, $polls_sql);
    mysqli_stmt_bind_param($polls_stmt, $param_types, ...$params);
    mysqli_stmt_execute($polls_stmt);
    $polls_result = mysqli_stmt_get_result($polls_stmt);

    $polls = [];
    
    while ($poll = mysqli_fetch_assoc($polls_result)) {
        // Get answers for this poll with vote counts
        $answers_sql = "
            SELECT 
                a.answer_id,
                a.text,
                a.vote_count,
                CASE 
                    WHEN (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id) > 0 
                    THEN ROUND((a.vote_count * 100.0) / (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id), 1)
                    ELSE 0 
                END as percentage
            FROM answers a 
            WHERE a.poll_id = ? 
            ORDER BY a.answer_id
        ";
        
        $answers_stmt = mysqli_prepare($con, $answers_sql);
        mysqli_stmt_bind_param($answers_stmt, "i", $poll['poll_id']);
        mysqli_stmt_execute($answers_stmt);
        $answers_result = mysqli_stmt_get_result($answers_stmt);
        
        $answers = [];
        while ($answer = mysqli_fetch_assoc($answers_result)) {
            $answers[] = [
                'id' => $answer['answer_id'],
                'answer_id' => $answer['answer_id'], // For compatibility
                'text' => $answer['text'],
                'vote_count' => $answer['vote_count'],
                'percentage' => (float)$answer['percentage']
            ];
        }
        
        // Handle anonymous posts properly
        $creator_name = '';
        $creator_profile_pic = '';
        $show_follow_button = true;
        $creator_user_id = $poll['creator_user_id'];
        $show_favorite_button = true;
        
        if ($poll['isAnonymous'] == 1) {
            // Anonymous post - hide real identity completely
            $creator_name = 'Anonymous';
            $creator_profile_pic = 'Images/profile_pic.png'; // Default anonymous profile pic
            $show_follow_button = false; // Can't follow anonymous users
            $show_favorite_button = false; // Can't favorite anonymous posts
            $creator_user_id = null; // Don't expose real user ID for anonymous posts
        } else {
            // Regular post - show real identity
            $creator_name = $poll['first_name'] . ' ' . $poll['last_name'];
            $creator_profile_pic = !empty($poll['profile_pic']) ? 'uploads/profile_pics/' . $poll['profile_pic'] : 'Images/profile_pic.png';
            $show_follow_button = true;
            $show_favorite_button = true;
        }
        
        // Format poll data
        $poll_data = [
            'poll_id' => $poll['poll_id'],
            'question' => $poll['question'],
            'date' => date('M j, Y', strtotime($poll['date_of_creation'])),
            'date_raw' => $poll['date_of_creation'],
            'isAnonymous' => (bool)$poll['isAnonymous'],
            'isPublic' => (bool)$poll['isPublic'],
            'answers' => $answers,
            'category' => [
                'id' => $poll['CategoryID'],
                'name' => $poll['category_name']
            ],
            'created_by' => [
                'user_id' => $creator_user_id, // Will be null for anonymous posts
                'name' => $creator_name,
                'profile_pic' => $creator_profile_pic,
                'show_follow_button' => $show_follow_button
            ],
            'comment_count' => (int)$poll['comment_count'],
            'is_favorite' => $show_favorite_button ? (bool)$poll['is_favorite'] : false,
            'is_following' => (bool)$poll['is_following'],
            'user_voted' => !is_null($poll['user_vote_answer_id']),
            'user_vote_id' => $poll['user_vote_answer_id'],
            'show_favorite_button' => $show_favorite_button
        ];
        
        $polls[] = $poll_data;
        
        mysqli_stmt_close($answers_stmt);
    }

    // UPDATED: Check if there are more polls for pagination with followers-only logic
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM polls p 
        WHERE (
            p.isPublic = 1 
            OR (
                p.isPublic = 0 
                AND (
                    p.CreatedBy = ? 
                    OR EXISTS (
                        SELECT 1 FROM follows f 
                        WHERE f.FollowingID = p.CreatedBy 
                        AND f.FollowerID = ?
                    )
                )
            )
        )" . $category_where;
    
    // UPDATED: Prepare count query with user_id parameters
    $count_params = [$user_id, $user_id];
    $count_param_types = "ii";
    
    if (!empty($category_values)) {
        $count_params = array_merge($count_params, $category_values);
        $count_param_types .= $category_params;
    }
    
    $count_stmt = mysqli_prepare($con, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $count_param_types, ...$count_params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_polls = mysqli_fetch_assoc($count_result)['total'];
    
    $has_more = ($offset + $limit) < $total_polls;

    // Return response
    echo json_encode([
        'success' => true,
        'data' => $polls,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total_polls,
            'hasMore' => $has_more
        ]
    ]);

    mysqli_stmt_close($polls_stmt);
    mysqli_stmt_close($count_stmt);

} catch (Exception $e) {
    error_log("Exception in fetch_polls.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug_info' => [
            'user_id' => $user_id,
            'page' => $page,
            'limit' => $limit,
            'categories' => $categories
        ]
    ]);
}

mysqli_close($con);
?>