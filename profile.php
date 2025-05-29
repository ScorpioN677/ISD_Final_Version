<?php
session_start();
include "config.php";

// Session checks
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();  
    session_destroy();   
    header("Location: login.php?message=" . urlencode("Your session has expired. Please log in again."));
    exit();
}

$_SESSION['last_activity'] = time();

// Function to fetch categories
function fetchCategories() {
    global $con;
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = mysqli_query($con, $sql);
    $categories = array();
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// Get categories for the edit modal
$categories = fetchCategories();

// Get user ID - either from URL parameter or session (for own profile)
$current_user_id = $_SESSION['user_id'];
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$is_own_profile = ($profile_user_id === $current_user_id);

// Get user information with prepared statement
$stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (!$user_result || mysqli_num_rows($user_result) === 0) {
    if ($is_own_profile) {
        header("Location: login.php?error=" . urlencode("User not found"));
    } else {
        header("Location: mainContent.php?error=" . urlencode("User profile not found"));
    }
    exit();
}

$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// Calculate age if date of birth exists
$age = "";
if (isset($user['date_of_birth']) && !empty($user['date_of_birth'])) {
    $dob = new DateTime($user['date_of_birth']);
    $now = new DateTime();
    $interval = $now->diff($dob);
    $age = $interval->y;
}

// Get profile picture with prepared statement
$stmt = mysqli_prepare($con, "SELECT * FROM profilepictures WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
mysqli_stmt_execute($stmt);
$pic_result = mysqli_stmt_get_result($stmt);
$profile_pic = "Images/profile_pic.png";

if ($pic_result && mysqli_num_rows($pic_result) > 0) {
    $pic_data = mysqli_fetch_assoc($pic_result);
    $profile_pic = 'uploads/profile_pics/' . $pic_data['file'];
}
mysqli_stmt_close($stmt);

// Get followers count with prepared statement
$stmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM follows WHERE FollowingID = ?");
mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
mysqli_stmt_execute($stmt);
$followers_result = mysqli_stmt_get_result($stmt);
$followers_count = 0;

if ($followers_result) {
    $followers_data = mysqli_fetch_assoc($followers_result);
    $followers_count = $followers_data['count'];
}
mysqli_stmt_close($stmt);

// Get following count with prepared statement
$stmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM follows WHERE FollowerID = ?");
mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
mysqli_stmt_execute($stmt);
$following_result = mysqli_stmt_get_result($stmt);
$following_count = 0;

if ($following_result) {
    $following_data = mysqli_fetch_assoc($following_result);
    $following_count = $following_data['count'];
}
mysqli_stmt_close($stmt);

// Check if current user is following this profile (if viewing someone else's profile)
$is_following = false;
if (!$is_own_profile) {
    $stmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM follows WHERE FollowerID = ? AND FollowingID = ?");
    mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $profile_user_id);
    mysqli_stmt_execute($stmt);
    $follow_result = mysqli_stmt_get_result($stmt);
    if ($follow_result) {
        $follow_data = mysqli_fetch_assoc($follow_result);
        $is_following = $follow_data['count'] > 0;
    }
    mysqli_stmt_close($stmt);
}

// Handle session messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';

if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $is_own_profile ? 'My Profile' : htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ' - Profile'; ?></title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-profile.css">
    <style>
        /* Enhanced voting styles for profile page */
        .poll-answers.voting-enabled .answer-option {
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
        }
        
        .poll-answers.voting-enabled .answer-option:hover {
            background: #e9ecef;
            border-color: #BE6AFF;
            transform: translateY(-1px);
        }
        
        .poll-answers.voting-enabled .answer-option.selected {
            background: #e8f5e8;
            border-color: #28a745;
        }
        
        .poll-answers.voting-enabled .answer-option.voting-disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .answer-text {
            font-weight: 500;
            color: #333;
        }
        
        .vote-count {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .vote-indicator {
            color: #28a745;
            font-weight: bold;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .voting-success {
            animation: pulse-green 1s ease-in-out;
        }
        
        @keyframes pulse-green {
            0% { background-color: #d4edda; }
            50% { background-color: #c3e6cb; }
            100% { background-color: #d4edda; }
        }
        
        /* Loading state for voting */
        .answer-option.loading-vote {
            position: relative;
            pointer-events: none;
        }
        
        .answer-option.loading-vote::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 10px;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #BE6AFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translateY(-50%);
        }
        
        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
        
        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            background: #28a745;
        }
        
        .toast.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <?php if(!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <p class="title"><?php echo $is_own_profile ? 'My Profile' : htmlspecialchars($user['first_name'] . "'s Profile"); ?></p>

    <!-- Back button - always show one -->
    <div style="text-align: center; margin-bottom: 20px;">
        <a href="mainContent.php" class="back-button">← Back to Feed</a>
    </div>

    <div class="info">
        <div class="personal_info">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Pic" id="profile_pic">
            <br>
            <div class="name_age">
                <p id="name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <?php if(!empty($age)): ?>
                <p id="age"><?php echo htmlspecialchars($age); ?> years old</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bio">
            <?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio yet'; ?>
        </div>

        <div class="contact-info">
            <?php if (!empty($user['email'])): ?>
            <div class="email-info">
                <span class="info-label">📧</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($user['phone_number'])): ?>
            <div class="phone-info">
                <span class="info-label">📞</span>
                <span class="info-value"><?php echo htmlspecialchars($user['phone_number']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="follows">
            <?php if ($is_own_profile): ?>
                <a href="followers.php"><button type="button" tabindex="-1"><span><?php echo htmlspecialchars($followers_count); ?></span> Followers</button></a>
                <a href="following.php"><button type="button" tabindex="-1"><span><?php echo htmlspecialchars($following_count); ?></span> Following</button></a>
            <?php else: ?>
                <a href="followers.php?user_id=<?php echo $profile_user_id; ?>"><button type="button" tabindex="-1"><span><?php echo htmlspecialchars($followers_count); ?></span> Followers</button></a>
                <a href="following.php?user_id=<?php echo $profile_user_id; ?>"><button type="button" tabindex="-1"><span><?php echo htmlspecialchars($following_count); ?></span> Following</button></a>
                <!-- Follow/Unfollow button -->
                <button type="button" class="follow-toggle-btn <?php echo $is_following ? 'following' : ''; ?>" 
                        data-user-id="<?php echo $profile_user_id; ?>" tabindex="-1">
                    <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if ($is_own_profile): ?>
        <div class="edit">
            <a href="editProfile.php"><button type="button" tabindex="-1">Edit Profile</button></a>
            <a href="logout.php"><button type="button" tabindex="-1">Logout</button></a>
        </div>
        <?php endif; ?>
    </div>

    <div class="posts">
        <div class="buttons">
            <button type="button" class="firstButton posts-btn active" tabindex="-1" onclick="showPosts()">
                <img src="Images/posts.png" alt="Posts">
            </button>

            <?php if ($is_own_profile): ?>
            <button type="button" class="favorites-btn" tabindex="-1" onclick="showFavorites()">
                <img src="Images/favorites.png" alt="Favorites">
            </button>
            <?php endif; ?>
        </div>

        <div class="post-container" id="posts-container">
            <?php
            // Get user's polls with prepared statement
            if ($is_own_profile) {
                // Show all polls for own profile
                $stmt = mysqli_prepare($con, "SELECT p.*, c.name as category_name 
                               FROM polls p 
                               JOIN categories c ON p.CategoryID = c.category_id 
                               WHERE p.CreatedBy = ? 
                               ORDER BY p.date_of_creation DESC");
            } else {
                // Show only public polls for other profiles
                $stmt = mysqli_prepare($con, "SELECT p.*, c.name as category_name 
                               FROM polls p 
                               JOIN categories c ON p.CategoryID = c.category_id 
                               WHERE p.CreatedBy = ? AND p.isPublic = 1
                               ORDER BY p.date_of_creation DESC");
            }
            
            mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
            mysqli_stmt_execute($stmt);
            $polls_result = mysqli_stmt_get_result($stmt);
            
            if ($polls_result && mysqli_num_rows($polls_result) > 0) {
                while ($poll = mysqli_fetch_assoc($polls_result)) {
                    echo '<div class="post poll-item" data-poll-id="' . $poll['poll_id'] . '">';
                    echo '<div class="post-header">';
                    echo '<div>Category: ' . htmlspecialchars($poll['category_name']) . '</div>';
                    echo '<div>Date: ' . date('M d, Y', strtotime($poll['date_of_creation'])) . '</div>';
                    if (!$poll['isPublic']) {
                        echo '<div style="color: #BE6AFF;">Private</div>';
                    }
                    if ($is_own_profile) {
                        echo '<div class="poll-actions">';
                        echo '<button type="button" class="edit-poll-btn" data-poll-id="' . $poll['poll_id'] . '" title="Edit poll">✏️</button>';
                        echo '<button type="button" class="delete-poll-btn" data-poll-id="' . $poll['poll_id'] . '" title="Delete poll">🗑️</button>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '<div class="post-question">' . htmlspecialchars($poll['question']) . '</div>';
                    
                    // Get answers for this poll with prepared statement
                    $answer_stmt = mysqli_prepare($con, "SELECT a.*, 
                        CASE 
                            WHEN (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id) > 0 
                            THEN ROUND((a.vote_count * 100.0) / (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id), 1)
                            ELSE 0 
                        END as percentage
                        FROM answers a WHERE poll_id = ? ORDER BY a.answer_id");
                    mysqli_stmt_bind_param($answer_stmt, "i", $poll['poll_id']);
                    mysqli_stmt_execute($answer_stmt);
                    $answers_result = mysqli_stmt_get_result($answer_stmt);
                    
                    if ($answers_result && mysqli_num_rows($answers_result) > 0) {
                        // Check if current user has voted on this poll (for all profiles)
                        $vote_check_stmt = mysqli_prepare($con, "SELECT answer_id FROM responses WHERE poll_id = ? AND user_id = ?");
                        mysqli_stmt_bind_param($vote_check_stmt, "ii", $poll['poll_id'], $current_user_id);
                        mysqli_stmt_execute($vote_check_stmt);
                        $vote_result = mysqli_stmt_get_result($vote_check_stmt);
                        $user_voted_answer = null;
                        if ($vote_result && mysqli_num_rows($vote_result) > 0) {
                            $vote_data = mysqli_fetch_assoc($vote_result);
                            $user_voted_answer = $vote_data['answer_id'];
                        }
                        mysqli_stmt_close($vote_check_stmt);
                        
                        // Show voting interface for all polls (including own polls now)
                        echo '<div class="poll-answers voting-enabled" data-poll-id="' . $poll['poll_id'] . '">';
                        while ($answer = mysqli_fetch_assoc($answers_result)) {
                            $is_selected = ($user_voted_answer == $answer['answer_id']);
                            $selected_class = $is_selected ? ' selected' : '';
                            echo '<div class="answer-option' . $selected_class . '" data-answer-id="' . $answer['answer_id'] . '" data-poll-id="' . $poll['poll_id'] . '">';
                            echo '<div class="answer-text">' . htmlspecialchars($answer['text']) . '</div>';
                            echo '<div class="vote-count">' . htmlspecialchars($answer['vote_count']) . ' votes (' . $answer['percentage'] . '%)</div>';
                            if ($is_selected) {
                                echo '<span class="vote-indicator">✓ Your vote</span>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    mysqli_stmt_close($answer_stmt);
                    
                    echo '</div>';
                }
            } else {
                if ($is_own_profile) {
                    echo '<div class="empty-message">You haven\'t created any polls yet.</div>';
                } else {
                    echo '<div class="empty-message">This user hasn\'t created any public polls yet.</div>';
                }
            }
            mysqli_stmt_close($stmt);
            ?>
        </div>

        <?php if ($is_own_profile): ?>
        <div class="favorites-container" id="favorites-container">
            <?php
            // Get user's favorite polls with prepared statement
            $stmt = mysqli_prepare($con, "SELECT p.*, c.name as category_name, u.first_name, u.last_name 
                                   FROM favorites f 
                                   JOIN polls p ON f.PollID = p.poll_id 
                                   JOIN categories c ON p.CategoryID = c.category_id 
                                   JOIN users u ON p.CreatedBy = u.user_id
                                   WHERE f.UserID = ? 
                                   ORDER BY p.date_of_creation DESC");
            mysqli_stmt_bind_param($stmt, "i", $current_user_id);
            mysqli_stmt_execute($stmt);
            $favorites_result = mysqli_stmt_get_result($stmt);
            
            if ($favorites_result && mysqli_num_rows($favorites_result) > 0) {
                while ($favorite = mysqli_fetch_assoc($favorites_result)) {
                    echo '<div class="post" data-poll-id="' . $favorite['poll_id'] . '">';
                    echo '<div class="post-header">';
                    echo '<div>Category: ' . htmlspecialchars($favorite['category_name']) . '</div>';
                    echo '<div>By: ' . ($favorite['isAnonymous'] ? 'Anonymous' : htmlspecialchars($favorite['first_name'] . ' ' . $favorite['last_name'])) . '</div>';
                    echo '<button type="button" class="unfavorite-btn" data-poll-id="' . $favorite['poll_id'] . '" title="Remove from favorites">❤️</button>';
                    echo '</div>';
                    echo '<div class="post-question">' . htmlspecialchars($favorite['question']) . '</div>';
                    
                    // Get answers for favorite polls with voting capability
                    $answer_stmt = mysqli_prepare($con, "SELECT a.*, 
                        CASE 
                            WHEN (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id) > 0 
                            THEN ROUND((a.vote_count * 100.0) / (SELECT SUM(vote_count) FROM answers WHERE poll_id = a.poll_id), 1)
                            ELSE 0 
                        END as percentage
                        FROM answers a WHERE poll_id = ? ORDER BY a.answer_id");
                    mysqli_stmt_bind_param($answer_stmt, "i", $favorite['poll_id']);
                    mysqli_stmt_execute($answer_stmt);
                    $answers_result = mysqli_stmt_get_result($answer_stmt);
                    
                    if ($answers_result && mysqli_num_rows($answers_result) > 0) {
                        // Check if user voted on this favorite poll
                        $vote_check_stmt = mysqli_prepare($con, "SELECT answer_id FROM responses WHERE poll_id = ? AND user_id = ?");
                        mysqli_stmt_bind_param($vote_check_stmt, "ii", $favorite['poll_id'], $current_user_id);
                        mysqli_stmt_execute($vote_check_stmt);
                        $vote_result = mysqli_stmt_get_result($vote_check_stmt);
                        $user_voted_answer = null;
                        if ($vote_result && mysqli_num_rows($vote_result) > 0) {
                            $vote_data = mysqli_fetch_assoc($vote_result);
                            $user_voted_answer = $vote_data['answer_id'];
                        }
                        mysqli_stmt_close($vote_check_stmt);
                        
                        echo '<div class="poll-answers voting-enabled" data-poll-id="' . $favorite['poll_id'] . '">';
                        while ($answer = mysqli_fetch_assoc($answers_result)) {
                            $is_selected = ($user_voted_answer == $answer['answer_id']);
                            $selected_class = $is_selected ? ' selected' : '';
                            echo '<div class="answer-option' . $selected_class . '" data-answer-id="' . $answer['answer_id'] . '" data-poll-id="' . $favorite['poll_id'] . '">';
                            echo '<div class="answer-text">' . htmlspecialchars($answer['text']) . '</div>';
                            echo '<div class="vote-count">' . htmlspecialchars($answer['vote_count']) . ' votes (' . $answer['percentage'] . '%)</div>';
                            if ($is_selected) {
                                echo '<span class="vote-indicator">✓ Your vote</span>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    mysqli_stmt_close($answer_stmt);
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-message">You don\'t have any favorite polls yet.</div>';
            }
            mysqli_stmt_close($stmt);
            ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Poll Modal -->
    <?php if ($is_own_profile): ?>
    <div id="editPollModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Poll</h3>
                <span class="close-modal" id="closeEditModal">&times;</span>
            </div>
            <form id="editPollForm">
                <input type="hidden" id="editPollId" name="poll_id">
                
                <div class="form-group">
                    <label for="editQuestion">Question:</label>
                    <textarea id="editQuestion" name="question" required maxlength="255" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="editCategory">Category:</label>
                    <select id="editCategory" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Answers:</label>
                    <div id="editAnswersList">
                        <!-- Answers will be populated dynamically -->
                    </div>
                    <button type="button" id="addEditAnswer" class="btn-secondary">+ Add Answer</button>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="editIsPublic" name="is_public" value="1">
                        Make this poll public
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="editIsAnonymous" name="is_anonymous" value="1">
                        Post anonymously
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Update Poll</button>
                    <button type="button" class="btn-secondary" id="cancelEdit">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // JavaScript to toggle between posts and favorites
        function showPosts() {
            document.getElementById('posts-container').style.display = 'flex';
            <?php if ($is_own_profile): ?>
            document.getElementById('favorites-container').style.display = 'none';
            document.querySelector('.favorites-btn').classList.remove('active');
            <?php endif; ?>
            document.querySelector('.posts-btn').classList.add('active');
        }
        
        <?php if ($is_own_profile): ?>
        function showFavorites() {
            document.getElementById('posts-container').style.display = 'none';
            document.getElementById('favorites-container').style.display = 'flex';
            document.querySelector('.posts-btn').classList.remove('active');
            document.querySelector('.favorites-btn').classList.add('active');
        }
        <?php endif; ?>
        
        // Check URL hash on page load and show appropriate section
        function handleHashNavigation() {
            const hash = window.location.hash;
            <?php if ($is_own_profile): ?>
            if (hash === '#favorites' || hash === '#favorite') {
                showFavorites();
            } else {
                showPosts();
            }
            <?php else: ?>
            showPosts();
            <?php endif; ?>
        }
        
        // Initialize based on hash or default to posts
        handleHashNavigation();
        
        // Listen for hash changes
        window.addEventListener('hashchange', handleHashNavigation);

        // Enhanced voting functionality for profile page
        $(document).ready(function() {
            // Universal voting handler for both own and other users' polls
            $(document).on('click', '.answer-option', function() {
                const $option = $(this);
                const $pollContainer = $option.closest('.poll-answers');
                const pollId = $option.data('poll-id');
                const answerId = $option.data('answer-id');
                
                // Prevent multiple clicks or if already processing
                if ($option.hasClass('loading-vote') || $pollContainer.hasClass('voting-disabled')) {
                    return;
                }
                
                // Add loading state
                $option.addClass('loading-vote');
                $pollContainer.addClass('voting-disabled');
                
                // Submit vote via AJAX
                $.ajax({
                    url: 'submit_vote.php',
                    type: 'POST',
                    data: {
                        poll_id: pollId,
                        answer_id: answerId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update UI based on response
                            updateVoteDisplay($pollContainer, response.data, answerId);
                            
                            // Show success feedback
                            $pollContainer.addClass('voting-success');
                            setTimeout(function() {
                                $pollContainer.removeClass('voting-success');
                            }, 1000);
                            
                            showToast('Success', response.action === 'removed' ? 'Vote removed' : 'Vote recorded', 'success');
                        } else {
                            showToast('Error', response.message || 'Failed to record vote', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Vote submission error:', error);
                        showToast('Error', 'Network error. Please try again.', 'error');
                    },
                    complete: function() {
                        // Remove loading states
                        $option.removeClass('loading-vote');
                        $pollContainer.removeClass('voting-disabled');
                    }
                });
            });

            // Function to update vote display
            function updateVoteDisplay($pollContainer, responseData, selectedAnswerId) {
                // Remove all selected states and vote indicators
                $pollContainer.find('.answer-option').removeClass('selected');
                $pollContainer.find('.vote-indicator').remove();

                // Update each answer with new data
                if (responseData && responseData.answers) {
                    responseData.answers.forEach(function(answer) {
                        const $answerOption = $pollContainer.find(`.answer-option[data-answer-id="${answer.answer_id}"]`);
                        
                        if ($answerOption.length) {
                            // Update vote count and percentage
                            $answerOption.find('.vote-count').text(`${answer.vote_count} votes (${answer.percentage}%)`);
                            
                            // Mark the selected answer if it matches
                            if (parseInt(answer.answer_id) === parseInt(selectedAnswerId)) {
                                $answerOption.addClass('selected');
                                $answerOption.append('<span class="vote-indicator">✓ Your vote</span>');
                            }
                        }
                    });
                }
            }

            // Show toast notification
            function showToast(title, message, type) {
                const $toast = $(`
                    <div class="toast ${type}">
                        <strong>${title}:</strong> ${message}
                    </div>
                `);
                
                $('body').append($toast);
                
                // Show toast
                setTimeout(function() {
                    $toast.addClass('show');
                }, 10);
                
                // Hide toast after 3 seconds
                setTimeout(function() {
                    $toast.removeClass('show');
                    setTimeout(function() {
                        $toast.remove();
                    }, 300);
                }, 3000);
            }

            // Handle unfavorite button clicks
            <?php if ($is_own_profile): ?>
            $('.unfavorite-btn').on('click', function() {
                const $button = $(this);
                const pollId = $button.data('poll-id');
                const $pollContainer = $button.closest('.post');
                
                // Confirm unfavorite action
                if (!confirm('Are you sure you want to remove this poll from your favorites?')) {
                    return;
                }
                
                // Disable button and show loading state
                $button.prop('disabled', true).text('...');
                
                // Send AJAX request to remove from favorites
                $.ajax({
                    url: 'toggle_favorite.php',
                    type: 'POST',
                    data: {
                        poll_id: pollId,
                        action: 'remove'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the poll from the DOM with animation
                            $pollContainer.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Check if there are any favorites left
                                if ($('#favorites-container .post').length === 0) {
                                    $('#favorites-container').append('<div class="empty-message">You don\'t have any favorite polls yet.</div>');
                                }
                            });
                        } else {
                            // Re-enable button and show error
                            $button.prop('disabled', false).text('❤️');
                            showToast('Error', response.message || 'Failed to remove from favorites', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable button and show error
                        $button.prop('disabled', false).text('❤️');
                        showToast('Error', 'Network error. Please try again.', 'error');
                        console.error('Error:', error);
                    }
                });
            });

            // Delete poll functionality
            $('.delete-poll-btn').on('click', function() {
                const $button = $(this);
                const pollId = $button.data('poll-id');
                const $pollContainer = $button.closest('.post');
                
                // Confirm delete action
                if (!confirm('Are you sure you want to delete this poll? This action cannot be undone and will remove all votes, comments, and favorites associated with it.')) {
                    return;
                }
                
                // Disable button and show loading state
                $button.prop('disabled', true).text('...');
                
                // Send AJAX request to delete poll
                $.ajax({
                    url: 'delete_poll.php',
                    type: 'POST',
                    data: {
                        poll_id: pollId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the poll from the DOM with animation
                            $pollContainer.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Check if there are any polls left
                                if ($('#posts-container .post').length === 0) {
                                    $('#posts-container').append('<div class="empty-message">You haven\'t created any polls yet.</div>');
                                }
                            });
                        } else {
                            // Re-enable button and show error
                            $button.prop('disabled', false).text('🗑️');
                            showToast('Error', response.message || 'Failed to delete poll', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable button and show error
                        $button.prop('disabled', false).text('🗑️');
                        showToast('Error', 'Network error. Please try again.', 'error');
                        console.error('Error:', error);
                    }
                });
            });

            // Edit poll functionality
            $('.edit-poll-btn').on('click', function() {
                const pollId = $(this).data('poll-id');
                openEditModal(pollId);
            });

            // Modal functionality
            $('#closeEditModal, #cancelEdit').on('click', function() {
                closeEditModal();
            });

            // Close modal when clicking outside
            $('#editPollModal').on('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });

            // Add answer functionality in edit modal
            $('#addEditAnswer').on('click', function() {
                addEditAnswer();
            });

            // Remove answer functionality (delegated)
            $(document).on('click', '.remove-edit-answer-btn', function() {
                if ($('#editAnswersList .edit-answer-item').length > 2) {
                    $(this).closest('.edit-answer-item').remove();
                } else {
                    alert('A poll must have at least 2 answers.');
                }
            });

            // Edit form submission
            $('#editPollForm').on('submit', function(e) {
                e.preventDefault();
                submitEditForm();
            });
            <?php endif; ?>
        });

        // Function to open edit modal
        function openEditModal(pollId) {
            // Get poll data from the DOM
            const $pollContainer = $(`.post[data-poll-id="${pollId}"]`);
            const question = $pollContainer.find('.post-question').text().trim();
            
            // Set poll ID
            $('#editPollId').val(pollId);
            $('#editQuestion').val(question);
            
            // Get poll details via AJAX to populate form properly
            $.ajax({
                url: 'get_poll_details.php',
                type: 'POST',
                data: { poll_id: pollId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const poll = response.poll;
                        const answers = response.answers;
                        
                        // Populate form
                        $('#editQuestion').val(poll.question);
                        $('#editCategory').val(poll.CategoryID);
                        $('#editIsPublic').prop('checked', poll.isPublic == 1);
                        $('#editIsAnonymous').prop('checked', poll.isAnonymous == 1);
                        
                        // Populate answers
                        $('#editAnswersList').empty();
                        answers.forEach(function(answer) {
                            addEditAnswer(answer.text, answer.answer_id);
                        });
                        
                        // Show modal
                        $('#editPollModal').show();
                    } else {
                        alert('Failed to load poll details: ' + response.message);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        }

        // Function to close edit modal
        function closeEditModal() {
            $('#editPollModal').hide();
            $('#editPollForm')[0].reset();
            $('#editAnswersList').empty();
        }

        // Function to add answer input in edit modal
        function addEditAnswer(text = '', answerId = 0) {
            const answerHtml = `
                <div class="edit-answer-item">
                    <input type="hidden" name="answers[${Date.now()}][id]" value="${answerId}">
                    <input type="text" name="answers[${Date.now()}][text]" value="${text}" placeholder="Enter answer option" required maxlength="100">
                    <button type="button" class="remove-edit-answer-btn">✕</button>
                </div>
            `;
            $('#editAnswersList').append(answerHtml);
        }

        // Function to submit edit form
        function submitEditForm() {
            const formData = new FormData($('#editPollForm')[0]);
            
            // Convert FormData to regular object for easier handling
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('answers[')) {
                    if (!data.answers) data.answers = [];
                    const match = key.match(/answers\[(\d+)\]\[(\w+)\]/);
                    if (match) {
                        const index = match[1];
                        const field = match[2];
                        if (!data.answers[index]) data.answers[index] = {};
                        data.answers[index][field] = value;
                    }
                } else {
                    data[key] = value;
                }
            }
            
            // Filter out empty answers and convert to array
            if (data.answers) {
                data.answers = Object.values(data.answers).filter(answer => answer.text && answer.text.trim());
            }
            
            $.ajax({
                url: 'update_poll.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the poll in the DOM
                        updatePollInDOM(response.poll, response.answers);
                        closeEditModal();
                        alert('Poll updated successfully!');
                    } else {
                        alert('Failed to update poll: ' + response.message);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        }

        // Function to update poll in DOM
        function updatePollInDOM(poll, answers) {
            const $pollContainer = $(`.post[data-poll-id="${poll.poll_id}"]`);
            
            // Update question
            $pollContainer.find('.post-question').text(poll.question);
            
            // Update category in header
            $pollContainer.find('.post-header div:first-child').text(`Category: ${poll.category_name}`);
            
            // Update privacy indicator
            const $privateIndicator = $pollContainer.find('.post-header div[style*="color: #BE6AFF"]');
            if (poll.isPublic == 1) {
                $privateIndicator.remove();
            } else if ($privateIndicator.length === 0) {
                $pollContainer.find('.post-header').append('<div style="color: #BE6AFF;">Private</div>');
            }
            
            // Update answers
            let answersHtml = '';
            answers.forEach(function(answer) {
                answersHtml += `<div>${answer.text} - ${answer.vote_count} votes</div>`;
            });
            $pollContainer.find('.post-answers').html(answersHtml);
        }

        // Handle follow/unfollow button for other profiles
        <?php if (!$is_own_profile): ?>
        $('.follow-toggle-btn').on('click', function() {
            const $button = $(this);
            const userId = $button.data('user-id');
            const isFollowing = $button.hasClass('following');
            const action = isFollowing ? 'unfollow' : 'follow';
            
            // Optimistic UI update
            $button.prop('disabled', true);
            
            if (action === 'follow') {
                $button.addClass('following').text('Unfollow');
            } else {
                $button.removeClass('following').text('Follow');
            }
            
            // Send AJAX request
            $.ajax({
                url: 'toggle_follow_user.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    $button.prop('disabled', false);
                    
                    if (response.success) {
                        // Update followers count
                        const $followerCount = $('.follows button:first span');
                        let currentCount = parseInt($followerCount.text()) || 0;
                        
                        if (action === 'follow') {
                            $followerCount.text(currentCount + 1);
                        } else {
                            $followerCount.text(Math.max(0, currentCount - 1));
                        }
                    } else {
                        // Revert UI on error
                        if (action === 'follow') {
                            $button.removeClass('following').text('Follow');
                        } else {
                            $button.addClass('following').text('Unfollow');
                        }
                        
                        alert('Failed to ' + action + ' user: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    
                    // Revert UI on error
                    if (action === 'follow') {
                        $button.removeClass('following').text('Follow');
                    } else {
                        $button.addClass('following').text('Unfollow');
                    }
                    
                    alert('Network error. Please try again.');
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>