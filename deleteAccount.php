<?php
include_once "session_protection.php";
include_once "config.php";

$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

// Process the final confirmation
if(isset($_POST['confirm_delete'])) {
    // Start transaction
    mysqli_begin_transaction($con);
    
    try {
        // First, delete any profile pictures
        $delete_pics = mysqli_prepare($con, "DELETE FROM profilepictures WHERE user_id = ?");
        mysqli_stmt_bind_param($delete_pics, "i", $user_id);
        mysqli_stmt_execute($delete_pics);
        mysqli_stmt_close($delete_pics);
        
        // Delete favorites
        $delete_favorites = mysqli_prepare($con, "DELETE FROM favorites WHERE UserID = ?");
        mysqli_stmt_bind_param($delete_favorites, "i", $user_id);
        mysqli_stmt_execute($delete_favorites);
        mysqli_stmt_close($delete_favorites);
        
        // Delete follows where user is following or followed
        $delete_follows1 = mysqli_prepare($con, "DELETE FROM follows WHERE FollowerID = ?");
        mysqli_stmt_bind_param($delete_follows1, "i", $user_id);
        mysqli_stmt_execute($delete_follows1);
        mysqli_stmt_close($delete_follows1);
        
        $delete_follows2 = mysqli_prepare($con, "DELETE FROM follows WHERE FollowingID = ?");
        mysqli_stmt_bind_param($delete_follows2, "i", $user_id);
        mysqli_stmt_execute($delete_follows2);
        mysqli_stmt_close($delete_follows2);
        
        // Delete responses
        $delete_responses = mysqli_prepare($con, "DELETE FROM responses WHERE user_id = ?");
        mysqli_stmt_bind_param($delete_responses, "i", $user_id);
        mysqli_stmt_execute($delete_responses);
        mysqli_stmt_close($delete_responses);
        
        // Delete comments
        $delete_comments = mysqli_prepare($con, "DELETE FROM comments WHERE user_id = ?");
        mysqli_stmt_bind_param($delete_comments, "i", $user_id);
        mysqli_stmt_execute($delete_comments);
        mysqli_stmt_close($delete_comments);
        
        // Get the poll IDs created by this user to delete associated answers
        $poll_stmt = mysqli_prepare($con, "SELECT poll_id FROM polls WHERE CreatedBy = ?");
        mysqli_stmt_bind_param($poll_stmt, "i", $user_id);
        mysqli_stmt_execute($poll_stmt);
        $poll_result = mysqli_stmt_get_result($poll_stmt);
        
        while($poll = mysqli_fetch_assoc($poll_result)) {
            $poll_id = $poll['poll_id'];
            
            // Delete answers for this poll
            $delete_answers = mysqli_prepare($con, "DELETE FROM answers WHERE poll_id = ?");
            mysqli_stmt_bind_param($delete_answers, "i", $poll_id);
            mysqli_stmt_execute($delete_answers);
            mysqli_stmt_close($delete_answers);
        }
        
        mysqli_stmt_close($poll_stmt);
        
        // Delete polls created by the user
        $delete_polls = mysqli_prepare($con, "DELETE FROM polls WHERE CreatedBy = ?");
        mysqli_stmt_bind_param($delete_polls, "i", $user_id);
        mysqli_stmt_execute($delete_polls);
        mysqli_stmt_close($delete_polls);
        
        // Finally, delete the user account
        $delete_user = mysqli_prepare($con, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($delete_user, "i", $user_id);
        mysqli_stmt_execute($delete_user);
        mysqli_stmt_close($delete_user);
        
        // Commit the transaction
        mysqli_commit($con);
        
        // Destroy the session
        session_start();
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        // Redirect to login page with success message
        header("Location: login.php?message=" . urlencode("Your account has been successfully deleted."));
        exit();
        
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        mysqli_rollback($con);
        $error = "Error deleting account: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Pollify</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    
    <!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    
    <!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="NEWCSS/simplified-deleteAcc.css">
</head>
<body>
    
    <p class="title">Delete Account</p>

    <!-- Back button -->
    <div style="text-align: center; margin-bottom: 20px; width: 100%;">
        <a href="editProfile.php" class="back-button">← Back to Edit Profile</a>
    </div>

    <?php if(!empty($error)): ?>
    <div class="message error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <p class="confirmation">
        Are you sure you want to delete your account? 
        Note that this action is permanent and cannot be undone. All your data, polls, 
        and activity will be removed from our system. If you're certain, 
        please confirm to proceed with the deletion.
    </p>

    <button type="button" class="confirm" id="conf">Confirm Deletion</button>

    <div class="lastConfirm" id="lastConfirm">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <p>Are you absolutely sure?</p>

            <div class="buttons">
                <button type="submit" name="confirm_delete">Delete Forever</button>
                <button type="button" id="cancel">Cancel</button>
            </div>
        </form>
    </div>

    <script src="js/deleteAccount.js"></script>

    <script>
    // Wait for the DOM to be fully loaded before adding event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Get the confirmation button and modal elements
        var confirmButton = document.getElementById('conf');
        var confirmModal = document.getElementById('lastConfirm');
        var cancelButton = document.getElementById('cancel');
        
        // Show the confirmation modal when the confirm button is clicked
        confirmButton.addEventListener('click', function() {
            confirmModal.style.display = 'flex';
            confirmModal.classList.add('show');
            console.log('Confirm button clicked, showing modal');
        });
        
        // Hide the confirmation modal when the cancel button is clicked
        cancelButton.addEventListener('click', function() {
            confirmModal.style.display = 'none';
            confirmModal.classList.remove('show');
            console.log('Cancel button clicked, hiding modal');
        });
        
        // Close modal when clicking outside of it
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                confirmModal.style.display = 'none';
                confirmModal.classList.remove('show');
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmModal.style.display === 'flex') {
                confirmModal.style.display = 'none';
                confirmModal.classList.remove('show');
            }
        });
        
        // Debugging logs
        console.log('DOM loaded, event listeners attached');
        console.log('Confirm button:', confirmButton);
        console.log('Confirm modal:', confirmModal);
        console.log('Cancel button:', cancelButton);
    });
    </script>
</body>
</html>