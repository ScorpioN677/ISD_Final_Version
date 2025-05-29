<?php
include_once "session_protection.php";
include_once "config.php";

// Get user ID - either from URL parameter or session (for own following)
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

// Clean up any duplicate follows first (only for own profile)
if ($is_own_profile) {
    $check_duplicates = mysqli_prepare($con, "
        SELECT FollowerID, FollowingID, COUNT(*) as count 
        FROM follows 
        WHERE FollowerID = ? 
        GROUP BY FollowerID, FollowingID 
        HAVING COUNT(*) > 1
    ");

    mysqli_stmt_bind_param($check_duplicates, "i", $profile_user_id);
    mysqli_stmt_execute($check_duplicates);
    $duplicates_result = mysqli_stmt_get_result($check_duplicates);

    if (mysqli_num_rows($duplicates_result) > 0) {
        // There are duplicates - keep one record and delete others for each duplicate pair
        while ($duplicate = mysqli_fetch_assoc($duplicates_result)) {
            $followingID = $duplicate['FollowingID'];
            
            // Find all duplicate records except the one with the minimum primary key
            $clean_stmt = mysqli_prepare($con, "
                DELETE f1 FROM follows f1
                WHERE f1.FollowerID = ? 
                AND f1.FollowingID = ?
                AND f1.FollowerID IN (
                    SELECT * FROM (
                        SELECT f2.FollowerID 
                        FROM follows f2 
                        WHERE f2.FollowerID = ? 
                        AND f2.FollowingID = ?
                        ORDER BY f2.FollowerID
                        LIMIT 1 OFFSET 1
                    ) as temp
                )
            ");
            
            mysqli_stmt_bind_param($clean_stmt, "iiii", $profile_user_id, $followingID, $profile_user_id, $followingID);
            mysqli_stmt_execute($clean_stmt);
            mysqli_stmt_close($clean_stmt);
        }
    }
    mysqli_stmt_close($check_duplicates);
}

// Get following users from database
$following_stmt = mysqli_prepare($con, "
    SELECT u.user_id, u.first_name, u.last_name, u.bio, u.email, pp.file as profile_pic 
    FROM follows f
    JOIN users u ON f.FollowingID = u.user_id
    LEFT JOIN profilepictures pp ON u.user_id = pp.user_id
    WHERE f.FollowerID = ?
    ORDER BY u.first_name, u.last_name
");

mysqli_stmt_bind_param($following_stmt, "i", $profile_user_id);
mysqli_stmt_execute($following_stmt);
$result = mysqli_stmt_get_result($following_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_own_profile ? 'Following' : htmlspecialchars($user['first_name'] . " is Following"); ?> - Pollify</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    
    <!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    
    <!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-following.css">
</head>
<body>
    <p class="title"><?php echo $is_own_profile ? 'Following' : htmlspecialchars($user['first_name'] . " is Following"); ?></p>
    
    <a href="profile.php?user_id=<?php echo $profile_user_id; ?>" class="back-button">← Back to Profile</a>
    
    <div class="following-container">
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($following = mysqli_fetch_assoc($result)) {
                $profile_pic = $following['profile_pic'] ? 'uploads/profile_pics/' . $following['profile_pic'] : 'Images/profile_pic.png';
                
                echo '<div class="following-item">';
                echo '<img src="' . htmlspecialchars($profile_pic) . '" alt="Profile Picture" class="following-pic">';
                echo '<div class="following-info">';
                echo '<a href="profile.php?user_id=' . htmlspecialchars($following['user_id']) . '" class="following-name-link">';
                echo '<p class="following-name">' . htmlspecialchars($following['first_name'] . ' ' . $following['last_name']) . '</p>';
                echo '</a>';
                echo '<p class="following-bio">' . (empty($following['bio']) ? 'No bio yet' : htmlspecialchars($following['bio'])) . '</p>';
                echo '<p class="following-email">' . htmlspecialchars($following['email']) . '</p>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            if ($is_own_profile) {
                echo '<p class="empty-message">You aren\'t following anyone yet.</p>';
            } else {
                echo '<p class="empty-message">' . htmlspecialchars($user['first_name']) . ' isn\'t following anyone yet.</p>';
            }
        }
        
        mysqli_stmt_close($following_stmt);
        ?>
    </div>
</body>
</html>