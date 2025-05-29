<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self';">
    <title>Reset Password</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/resetPassword.css">
</head>
<body>
    
    <form action="resetPasswordConf.php" method="POST">

        <p class="title">Reset Password</p>
        
        <?php if(isset($_GET['error'])): ?>
        <div class="message">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['success'])): ?>
        <div class="success-message">
            Password reset successfully!
        </div>
        <?php endif; ?>

        <div class="input-group">
            <label for="newPass">New Password*</label>
            <div class="password-container">
                <input type="password" id="newPass" name="newPass" placeholder="Enter new password" required autofocus>
                <img src="Images/eye-fill.png" class="eye" id="newEye" alt="Show password">
            </div>
        </div>

        <div class="input-group">
            <label for="confPass">Confirm Password*</label>
            <div class="password-container">
                <input type="password" id="confPass" name="confPass" placeholder="Confirm new password" required>
            </div>
        </div>

        <div class="buttons">
            <button type="submit" name="submit">Reset Password</button>
            <a href="login.php">Cancel</a>
        </div>
    </form>

    <script src="js/resetPassword.js"></script>
    <script>
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPass = document.getElementById('newPass').value;
            const confPass = document.getElementById('confPass').value;

            if (newPass !== confPass) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (newPass.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>