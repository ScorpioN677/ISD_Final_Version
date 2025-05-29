<?php
// notification_ajax.php - Enhanced AJAX endpoints for notifications

// Include database configuration and notification functions
include_once 'config.php';
include_once 'notifications.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start session to get user ID
session_start();

// Check if user is logged in
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to access notifications'
    ]);
    exit;
}

// Get the requested action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Process based on action
switch ($action) {
    case 'fetch':
        fetchNotifications($userId);
        break;
    
    case 'count':
        countUnreadNotifications($userId);
        break;
    
    case 'mark_read':
        $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        markAsRead($userId, $notificationId);
        break;
    
    case 'mark_all_read':
        markAllAsRead($userId);
        break;
    
    case 'get_new':
        $lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
        getNewNotifications($userId, $lastId);
        break;
    
    case 'delete':
        $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        deleteNotification($userId, $notificationId);
        break;
    
    case 'get_activity':
        getUserActivity($userId);
        break;
        
    case 'cleanup':
        cleanupNotifications();
        break;
    
    case 'test':
        createTestNotification($userId);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
}

/**
 * Fetch paginated notifications for a user
 * 
 * @param int $userId - User ID
 */
function fetchNotifications($userId) {
    global $con;
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Validate parameters
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 50) $limit = 10;
    
    // Query to get notifications with poll_id for direct navigation
    $query = "
        SELECT 
            n.notification_id,
            n.type,
            n.related_id,
            n.message,
            n.is_read,
            n.created_at,
            CASE 
                WHEN n.type = 'comment' OR n.type = 'reply' THEN 
                    (SELECT c.poll_id FROM comments c WHERE c.comment_id = n.related_id)
                WHEN n.type = 'vote' THEN 
                    (SELECT r.poll_id FROM responses r WHERE r.response_id = n.related_id)
                WHEN n.type = 'poll_like' THEN n.related_id
                ELSE NULL
            END as poll_id,
            CASE 
                WHEN n.type = 'follow' THEN 
                    (SELECT CONCAT(u.first_name, ' ', u.last_name) 
                     FROM users u WHERE u.user_id = n.related_id)
                ELSE NULL
            END as actor_name
        FROM 
            notifications n
        WHERE 
            n.user_id = ?
        ORDER BY 
            n.created_at DESC
        LIMIT ?, ?
    ";
    
    $stmt = mysqli_prepare($con, $query);
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "iii", $userId, $offset, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format time ago
        $row['time_ago'] = format_time_ago($row['created_at']);
        $row['formatted_date'] = date('M j, Y g:i A', strtotime($row['created_at']));
        
        // Add icon based on type
        $row['icon'] = getNotificationIcon($row['type']);
        $row['action_url'] = getNotificationUrl($row);
        $notifications[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
    $countStmt = mysqli_prepare($con, $countQuery);
    
    if (!$countStmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($countStmt, "i", $userId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    $total = intval($countRow['total']);
    
    mysqli_stmt_close($countStmt);
    
    // Check if there are more notifications
    $hasMore = ($page * $limit) < $total;
    
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'hasMore' => $hasMore
        ]
    ]);
}

/**
 * Count unread notifications for a user
 * 
 * @param int $userId - User ID
 */
function countUnreadNotifications($userId) {
    $count = get_unread_count($userId);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

/**
 * Mark a notification as read
 * 
 * @param int $userId - User ID
 * @param int $notificationId - Notification ID
 */
function markAsRead($userId, $notificationId) {
    // Validate notification ID
    if ($notificationId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid notification ID'
        ]);
        exit;
    }
    
    $result = mark_notification_read($notificationId, $userId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
    ]);
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $userId - User ID
 */
function markAllAsRead($userId) {
    $result = mark_all_notifications_read($userId);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'All notifications marked as read' : 'Failed to mark all notifications as read'
    ]);
}

/**
 * Get new notifications since a specific ID
 * 
 * @param int $userId - User ID
 * @param int $lastId - Last notification ID seen
 */
function getNewNotifications($userId, $lastId) {
    global $con;
    
    $query = "
        SELECT 
            n.notification_id,
            n.type,
            n.related_id,
            n.message,
            n.is_read,
            n.created_at,
            CASE 
                WHEN n.type = 'comment' OR n.type = 'reply' THEN 
                    (SELECT c.poll_id FROM comments c WHERE c.comment_id = n.related_id)
                WHEN n.type = 'vote' THEN 
                    (SELECT r.poll_id FROM responses r WHERE r.response_id = n.related_id)
                WHEN n.type = 'poll_like' THEN n.related_id
                ELSE NULL
            END as poll_id,
            CASE 
                WHEN n.type = 'follow' THEN 
                    (SELECT CONCAT(u.first_name, ' ', u.last_name) 
                     FROM users u WHERE u.user_id = n.related_id)
                ELSE NULL
            END as actor_name
        FROM 
            notifications n
        WHERE 
            n.user_id = ? AND n.notification_id > ?
        ORDER BY 
            n.created_at DESC
    ";
    
    $stmt = mysqli_prepare($con, $query);
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $userId, $lastId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format time ago
        $row['time_ago'] = format_time_ago($row['created_at']);
        $row['formatted_date'] = date('M j, Y g:i A', strtotime($row['created_at']));
        $row['icon'] = getNotificationIcon($row['type']);
        $row['action_url'] = getNotificationUrl($row);
        $notifications[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'count' => count($notifications)
    ]);
}

/**
 * Delete a notification
 * 
 * @param int $userId - User ID
 * @param int $notificationId - Notification ID
 */
function deleteNotification($userId, $notificationId) {
    global $con;
    
    if ($notificationId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid notification ID'
        ]);
        exit;
    }
    
    $stmt = mysqli_prepare($con, "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
    ]);
}

/**
 * Get user activity feed
 * 
 * @param int $userId - User ID
 */
function getUserActivity($userId) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    // Validate limit
    if ($limit < 1 || $limit > 100) $limit = 20;
    
    $activities = get_user_activity($userId, $limit);
    
    // Format activities
    foreach ($activities as &$activity) {
        $activity['time_ago'] = format_time_ago($activity['created_at']);
        $activity['formatted_date'] = date('M j, Y g:i A', strtotime($activity['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
}

/**
 * Cleanup old notifications (admin function)
 */
function cleanupNotifications() {
    global $con;
    
    // Check if user has admin privileges (you may need to adjust this based on your user system)
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin privileges required'
        ]);
        exit;
    }
    
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    
    // Validate days parameter
    if ($days < 1 || $days > 365) $days = 30;
    
    $result = cleanup_old_notifications($days);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? "Cleaned up notifications older than {$days} days" : 'Failed to cleanup notifications'
    ]);
}

/**
 * Create a test notification (for development/testing)
 * 
 * @param int $userId - User ID
 */
function createTestNotification($userId) {
    if (isset($_POST['test']) && $_POST['test'] === 'true') {
        $message = "This is a test notification created at " . date('Y-m-d H:i:s');
        $result = create_notification($userId, 'test', 0, $message);
        
        echo json_encode([
            'success' => $result !== false,
            'message' => $result !== false ? 'Test notification created' : 'Failed to create test notification',
            'notification_id' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Test parameter required'
        ]);
    }
}

/**
 * Get notification icon based on type
 * 
 * @param string $type - Notification type
 * @return string - Icon character or emoji
 */
function getNotificationIcon($type) {
    switch ($type) {
        case 'follow':
            return '👤';
        case 'comment':
            return '💬';
        case 'reply':
            return '↩️';
        case 'vote':
            return '🗳️';
        case 'poll_like':
            return '❤️';
        case 'test':
            return '🧪';
        default:
            return '🔔';
    }
}

/**
 * Get notification action URL
 * 
 * @param array $notification - Notification data
 * @return string - URL to navigate to
 */
function getNotificationUrl($notification) {
    switch ($notification['type']) {
        case 'follow':
            return "profile.php?user_id=" . $notification['related_id'];
        case 'comment':
        case 'reply':
            if ($notification['poll_id']) {
                return "mainContent.php?poll_id=" . $notification['poll_id'] . "&highlight_comment=" . $notification['related_id'];
            }
            return "mainContent.php";
        case 'vote':
        case 'poll_like':
            if ($notification['poll_id']) {
                return "mainContent.php?poll_id=" . $notification['poll_id'];
            }
            return "mainContent.php";
        case 'test':
            return "notifications.php";
        default:
            return "mainContent.php";
    }
}

/**
 * Sanitize and validate input
 * 
 * @param mixed $input - Input to sanitize
 * @param string $type - Type of sanitization
 * @return mixed - Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return intval($input);
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Log notification action for debugging
 * 
 * @param string $action - Action performed
 * @param int $userId - User ID
 * @param array $data - Additional data
 */
function logNotificationAction($action, $userId, $data = []) {
    if (defined('DEBUG_NOTIFICATIONS') && DEBUG_NOTIFICATIONS) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'user_id' => $userId,
            'data' => $data
        ];
        
        error_log('Notification Action: ' . json_encode($logData), 3, 'notification_debug.log');
    }
}

/**
 * Handle errors and return appropriate response
 * 
 * @param string $message - Error message
 * @param string $code - Error code (optional)
 */
function handleError($message, $code = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($code) {
        $response['error_code'] = $code;
    }
    
    echo json_encode($response);
    exit;
}
?>