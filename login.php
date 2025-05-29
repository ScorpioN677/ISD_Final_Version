<?php
session_start();
include "config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$remembered_email = "";
if(isset($_COOKIE['remember_email'])) {
    $remembered_email = $_COOKIE['remember_email'];
}

if (isset($_POST['submit'])) 
{
    if (empty($_POST['email']) || empty($_POST['password']))
    {
        header("Location: login.php?error=" . urlencode("Please fill the missing fields."));
        exit();
    } 
    else 
    {
        // Sanitize input and use prepared statements
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Using prepared statement to prevent SQL injection
        $stmt = mysqli_prepare($con, "SELECT user_id, password, first_name FROM users WHERE email = ?");
        
        if ($stmt === false) {
            header("Location: login.php?error=" . urlencode("Database error: " . mysqli_error($con)));
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) 
        {
            $row = mysqli_fetch_assoc($result);
            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password)) 
            {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_activity'] = time();
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['last_regeneration'] = time();
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                if(isset($_POST['remember']) && $_POST['remember'] == 'on') {
                    // Set secure cookie parameters
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; // Set secure flag if HTTPS
                    $httponly = true; // Prevent JavaScript access
                    
                    // Set cookies with improved security
                    setcookie("remember_email", $email, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => $secure,
                        'httponly' => $httponly,
                        'samesite' => 'Lax'
                    ]);
                    
                    setcookie("remember_user", $row['user_id'], [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => $secure,
                        'httponly' => $httponly,
                        'samesite' => 'Lax'
                    ]);
                }
                
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            } 
            else 
            {
                mysqli_stmt_close($stmt);
                header("Location: login.php?error=" . urlencode("Incorrect password."));
                exit();
            }
        } 
        else 
        {
            mysqli_stmt_close($stmt);
            header("Location: login.php?error=" . urlencode("User not found."));
            exit();
        }
    }
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
    <title>Pollify Login</title>

    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="NEWCSS/simplified.css">
</head>
<body>

    <img src="Images/main_icon.png" alt="Logo not found" class="logo">

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">

        <p class="title">Login</p>

        <?php if(isset($_GET['error'])): ?>
        <div class="message">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['message'])): ?>
        <div class="message">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <label for="email">Email*</label>
            <input name="email" type="email" id="email" placeholder="david123@example.com" 
                  value="<?php echo htmlspecialchars($remembered_email); ?>" required autofocus>
        </div>

        <div class="row">
            <label for="password" class="labelPass">Password*</label>
            <div>
                <input name="password" type="password" id="password" placeholder="********" required>
                <img src="Images/eye-fill.png" class="eye" id="eye">
            </div>
        </div>

        </div>

        <button type="submit" name="submit" class="login-button">Login</button>

        <a href="forgetPassword.php" class="forget">Forgot Password</a>

        <p class="signup">Don't have an account? <a href="signup.php">Signup</a></p>
    </form>

    <script src="js/loginShowPassword.js"></script>
    
    <script>
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>