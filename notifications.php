<?php
// notifications.php - Complete notification functionality

// Include database configuration
if (!defined('DB_INCLUDED')) {
    include_once 'config.php';
    define('DB_INCLUDED', true);
}

/**
 * Create a new notification
 * 
 * @param int $user_id - The recipient user ID
 * @param string $type - Notification type (follow, comment, reply, vote, poll_like)
 * @param int $related_id - Related entity ID
 * @param string $message - Notification message
 * @param int $actor_id - The user who performed the action (optional)
 * @return int|bool - New notification ID or false on failure
 */
function create_notification($user_id, $type, $related_id, $message, $actor_id = null) {
    global $con;
    
    // Don't create notification if user is acting on their own content
    if ($actor_id && $actor_id == $user_id) {
        return true;
    }
    
    // Check if similar notification already exists (prevent spam)
    if ($actor_id) {
        $check_stmt = mysqli_prepare($con, "
            SELECT notification_id 
            FROM notifications 
            WHERE user_id = ? AND type = ? AND related_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        mysqli_stmt_bind_param($check_stmt, "isi", $user_id, $type, $related_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            mysqli_stmt_close($check_stmt);
            return true; // Notification already exists
        }
        mysqli_stmt_close($check_stmt);
    }
    
    $stmt = mysqli_prepare($con, "
        INSERT INTO notifications (user_id, type, related_id, message, is_read, created_at) 
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "isis", $user_id, $type, $related_id, $message);
    $result = mysqli_stmt_execute($stmt);
    $notification_id = $result ? mysqli_insert_id($con) : false;
    
    mysqli_stmt_close($stmt);
    return $notification_id;
}

/**
 * Create a follow notification
 * 
 * @param int $follower_id - ID of user who is following
 * @param int $following_id - ID of user being followed
 * @return int|bool - New notification ID or false on failure
 */
function create_follow_notification($follower_id, $following_id) {
    global $con;
    
    // Don't create notification if user is following themselves
    if ($follower_id == $following_id) {
        return true;
    }
    
    // Get follower's name
    $stmt = mysqli_prepare($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $follower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $follower = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $message = $follower['name'] . " is now following you";
    
    return create_notification($following_id, 'follow', $follower_id, $message, $follower_id);
}

/**
 * Create a comment notification
 * 
 * @param int $comment_id - ID of new comment
 * @param int $commenter_id - ID of user who commented
 * @param int $poll_id - ID of poll being commented on
 * @param int $poll_owner_id - ID of poll owner
 * @return int|bool - New notification ID or false on failure
 */
function create_comment_notification($comment_id, $commenter_id, $poll_id, $poll_owner_id) {
    global $con;
    
    // Don't notify if user is commenting on their own poll
    if ($commenter_id == $poll_owner_id) {
        return true;
    }
    
    // Get commenter's name
    $stmt = mysqli_prepare($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $commenter_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $commenter = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Get poll question (first 30 chars)
    $stmt = mysqli_prepare($con, "SELECT LEFT(question, 30) AS question FROM polls WHERE poll_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $poll_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $poll = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $poll_preview = $poll['question'];
    if (strlen($poll['question']) >= 30) {
        $poll_preview .= '...';
    }
    
    $message = $commenter['name'] . " commented on your poll: \"" . $poll_preview . "\"";
    
    return create_notification($poll_owner_id, 'comment', $comment_id, $message, $commenter_id);
}

/**
 * Create a reply notification
 * 
 * @param int $reply_id - ID of reply comment
 * @param int $replier_id - ID of user who replied
 * @param int $parent_comment_id - ID of parent comment
 * @param int $parent_commenter_id - ID of parent comment author
 * @return int|bool - New notification ID or false on failure
 */
function create_reply_notification($reply_id, $replier_id, $parent_comment_id, $parent_commenter_id) {
    global $con;
    
    // Don't notify if user is replying to their own comment
    if ($replier_id == $parent_commenter_id) {
        return true;
    }
    
    // Get replier's name
    $stmt = mysqli_prepare($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $replier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $replier = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $message = $replier['name'] . " replied to your comment";
    
    return create_notification($parent_commenter_id, 'reply', $reply_id, $message, $replier_id);
}

/**
 * Create a vote notification
 * 
 * @param int $response_id - ID of vote response
 * @param int $voter_id - ID of user who voted
 * @param int $poll_id - ID of poll being voted on
 * @param int $poll_owner_id - ID of poll owner
 * @return int|bool - New notification ID or false on failure
 */
function create_vote_notification($response_id, $voter_id, $poll_id, $poll_owner_id) {
    global $con;
    
    // Don't notify if user is voting on their own poll
    if ($voter_id == $poll_owner_id) {
        return true;
    }
    
    // Get voter's name
    $stmt = mysqli_prepare($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $voter_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $voter = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Get poll question (first 30 chars)
    $stmt = mysqli_prepare($con, "SELECT LEFT(question, 30) AS question FROM users WHERE poll_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $poll_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $poll = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $poll_preview = $poll['question'];
    if (strlen($poll['question']) >= 30) {
        $poll_preview .= '...';
    }
    
    $message = $voter['name'] . " voted on your poll: \"" . $poll_preview . "\"";
    
    return create_notification($poll_owner_id, 'vote', $response_id, $message, $voter_id);
}

/**
 * Create a poll like notification
 * 
 * @param int $poll_id - ID of poll being liked
 * @param int $liker_id - ID of user who liked
 * @param int $poll_owner_id - ID of poll owner
 * @return int|bool - New notification ID or false on failure
 */
function create_poll_like_notification($poll_id, $liker_id, $poll_owner_id) {
    global $con;
    
    // Don't notify if user is liking their own poll
    if ($liker_id == $poll_owner_id) {
        return true;
    }
    
    // Get liker's name
    $stmt = mysqli_prepare($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $liker_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $liker = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Get poll question (first 30 chars)
    $stmt = mysqli_prepare($con, "SELECT LEFT(question, 30) AS question FROM polls WHERE poll_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $poll_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $poll = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $poll_preview = $poll['question'];
    if (strlen($poll['question']) >= 30) {
        $poll_preview .= '...';
    }
    
    $message = $liker['name'] . " liked your poll: \"" . $poll_preview . "\"";
    
    return create_notification($poll_owner_id, 'poll_like', $poll_id, $message, $liker_id);
}

/**
 * Get user notifications with pagination
 * 
 * @param int $user_id - User ID
 * @param int $page - Page number (default: 1)
 * @param int $limit - Results per page (default: 10)
 * @return array - Array of notifications
 */
function get_user_notifications($user_id, $page = 1, $limit = 10) {
    global $con;
    
    $offset = ($page - 1) * $limit;
    
    $stmt = mysqli_prepare($con, "
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
            END as poll_id
        FROM notifications n
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?, ?
    ");
    
    if (!$stmt) {
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $offset, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $notifications;
}

/**
 * Get unread notification count for user
 * 
 * @param int $user_id - User ID
 * @return int - Count of unread notifications
 */
function get_unread_count($user_id) {
    global $con;
    
    $stmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if (!$stmt) {
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    return intval($row['count']);
}

/**
 * Mark notification as read
 * 
 * @param int $notification_id - Notification ID
 * @param int $user_id - User ID (for security)
 * @return bool - Success status
 */
function mark_notification_read($notification_id, $user_id) {
    global $con;
    
    $stmt = mysqli_prepare($con, "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Mark all notifications as read for user
 * 
 * @param int $user_id - User ID
 * @return bool - Success status
 */
function mark_all_notifications_read($user_id) {
    global $con;
    
    $stmt = mysqli_prepare($con, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Delete old notifications (cleanup function)
 * 
 * @param int $days - Delete notifications older than X days (default: 30)
 * @return bool - Success status
 */
function cleanup_old_notifications($days = 30) {
    global $con;
    
    $stmt = mysqli_prepare($con, "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $days);
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Get recent activity for user (for activity feed)
 * 
 * @param int $user_id - User ID
 * @param int $limit - Number of activities to get
 * @return array - Array of activities
 */
function get_user_activity($user_id, $limit = 20) {
    global $con;
    
    $stmt = mysqli_prepare($con, "
        SELECT 
            'notification' as type,
            notification_id as id,
            message,
            created_at,
            is_read
        FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    
    if (!$stmt) {
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $activities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $activities;
}

/**
 * Format time ago helper function
 * 
 * @param string $timestamp - MySQL timestamp
 * @return string - Formatted time ago string
 */
function format_time_ago($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . " seconds ago";
    } else if ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } else if ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } else if ($diff < 604800) {
        return floor($diff / 86400) . " days ago";
    } else if ($diff < 2592000) {
        return floor($diff / 604800) . " weeks ago";
    } else if ($diff < 31536000) {
        return floor($diff / 2592000) . " months ago";
    } else {
        return floor($diff / 31536000) . " years ago";
    }
}

/**
 * Send email notification (optional feature)
 * 
 * @param int $user_id - User ID to send email to
 * @param string $subject - Email subject
 * @param string $message - Email message
 * @return bool - Success status
 */
function send_email_notification($user_id, $subject, $message) {
    global $con;
    
    // Get user email
    $stmt = mysqli_prepare($con, "SELECT email, first_name FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Simple email sending (you can integrate with PHPMailer or other services)
    $to = $user['email'];
    $headers = "From: noreply@pollify.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $email_body = "
        <h2>Hello {$user['first_name']},</h2>
        <p>{$message}</p>
        <p>Visit Pollify to see more: <a href='http://yoursite.com'>Pollify</a></p>
        <hr>
        <p><small>This is an automated message from Pollify.</small></p>
    ";
    
    return mail($to, $subject, $email_body, $headers);
}
?>