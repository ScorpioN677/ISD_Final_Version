<?php
include_once "session_protection.php";
include_once "config.php";

$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

// Process form submission
if(isset($_POST['submit'])) {
    $old_password = $_POST['oldPass'];
    $new_password = $_POST['newPass'];
    $confirm_password = $_POST['confPass'];
    
    // Check if new passwords match
    if($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        // Validate password strength
        $password_validation = validatePassword($new_password);
        if($password_validation !== true) {
            $error = $password_validation;
        } else {
            // Get current hashed password from database
            $stmt = mysqli_prepare($con, "SELECT password FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                $current_hash = $user['password'];
                
                // Verify old password
                if(password_verify($old_password, $current_hash)) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    
                    // Update password in database
                    $update_stmt = mysqli_prepare($con, "UPDATE users SET password = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user_id);
                    
                    if(mysqli_stmt_execute($update_stmt)) {
                        // Set success message in session
                        $_SESSION['message'] = "Password changed successfully";
                        $_SESSION['message_type'] = "success";
                        
                        // Redirect to profile page
                        header("Location: profile.php");
                        exit();
                    } else {
                        $error = "Failed to update password: " . mysqli_stmt_error($update_stmt);
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error = "Current password is incorrect";
                }
            } else {
                $error = "User not found";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Password validation function
function validatePassword($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long";
    if (!preg_match("#[0-9]+#", $password)) return "Password must contain at least one number";
    if (!preg_match("#[A-Z]+#", $password)) return "Password must contain at least one uppercase letter";
    if (!preg_match("#[a-z]+#", $password)) return "Password must contain at least one lowercase letter";
    if (!preg_match("/[\'^£$%&*()}{@#~?><>,|=_+!-]/", $password)) return "Password must contain at least one special character";
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
<!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
<!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-changePass.css">

</head>
<body>
    
    <p class="title">Change Password</p>
    
    <?php if(!empty($message)): ?>
    <div class="message">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($error)): ?>
    <div class="message error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">

        <div class="old">
            <label for="oldPass">Old Password*</label>
            <div>
                <input type="password" name="oldPass" id="oldPass" required autofocus>
                <img src="Images/eye-fill.png" alt="Show Password" class="eye" id="oldEye">
            </div>
        </div>

        <div class="new">
            <label for="newPass">New Password*</label>
            <div>
                <input type="password" name="newPass" id="newPass" required>
                <img src="Images/eye-fill.png" alt="Show Password" class="eye" id="newEye">
            </div>
        </div>

        <div class="confirm">
            <label for="confPass">Confirm Password*</label>
            <input type="password" name="confPass" id="confPass" required>
        </div>

        <div class="buttons">
            <button type="submit" name="submit">Confirm</button>
            <a href="editProfile.php">Cancel</a>
        </div>
    </form>

    <script src="js/changePasswordShowPassword.js"></script>
</body>
</html>