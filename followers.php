<?php
include_once "session_protection.php";
include_once "config.php";

// Get user ID - either from URL parameter or session (for own followers)
$current_user_id = $_SESSION['user_id'];
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$is_own_profile = ($profile_user_id === $current_user_id);

// Get user information to display in title
$user_stmt = mysqli_prepare($con, "SELECT first_name, last_name FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $profile_user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);

if (!$user_result || mysqli_num_rows($user_result) === 0) {
    header("Location: mainContent.php?error=" . urlencode("User not found"));
    exit();
}

$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

// Get followers from database
$followers_stmt = mysqli_prepare($con, "
    SELECT u.user_id, u.first_name, u.last_name, u.bio, u.email, pp.file as profile_pic 
    FROM follows f
    JOIN users u ON f.FollowerID = u.user_id
    LEFT JOIN profilepictures pp ON u.user_id = pp.user_id
    WHERE f.FollowingID = ?
    ORDER BY u.first_name, u.last_name
");

mysqli_stmt_bind_param($followers_stmt, "i", $profile_user_id);
mysqli_stmt_execute($followers_stmt);
$result = mysqli_stmt_get_result($followers_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_own_profile ? 'My Followers' : htmlspecialchars($user['first_name'] . "'s Followers"); ?> - Pollify</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    
    <!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    
    <!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="NEWCSS/simplified-followers.css">
</head>
<body>
    <p class="title"><?php echo $is_own_profile ? 'My Followers' : htmlspecialchars($user['first_name'] . "'s Followers"); ?></p>
    
    <a href="profile.php?user_id=<?php echo $profile_user_id; ?>" class="back-button">← Back to Profile</a>
    
    <div class="followers-container">
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($follower = mysqli_fetch_assoc($result)) {
                $profile_pic = $follower['profile_pic'] ? 'uploads/profile_pics/' . $follower['profile_pic'] : 'Images/profile_pic.png';
                
                echo '<div class="follower-item">';
                echo '<img src="' . htmlspecialchars($profile_pic) . '" alt="Profile Picture" class="follower-pic">';
                echo '<div class="follower-info">';
                echo '<a href="profile.php?user_id=' . htmlspecialchars($follower['user_id']) . '" class="follower-name-link">';
                echo '<p class="follower-name">' . htmlspecialchars($follower['first_name'] . ' ' . $follower['last_name']) . '</p>';
                echo '</a>';
                echo '<p class="follower-bio">' . (empty($follower['bio']) ? 'No bio yet' : htmlspecialchars($follower['bio'])) . '</p>';
                echo '<p class="follower-email">' . htmlspecialchars($follower['email']) . '</p>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            if ($is_own_profile) {
                echo '<p class="empty-message">You don\'t have any followers yet.</p>';
            } else {
                echo '<p class="empty-message">' . htmlspecialchars($user['first_name']) . ' doesn\'t have any followers yet.</p>';
            }
        }
        
        mysqli_stmt_close($followers_stmt);
        ?>
    </div>
</body>
</html>