<?php
session_start();
include "config.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollify Signup</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-signup.css">
</head>
<body>

    <img src="Images/main_icon.png" alt="Logo not found" class="logo">

    <form action="signup-secure.php" method="POST" id="signupForm">

        <p class="title">Signup</p>

        <?php if(isset($_GET['error'])): ?>
        <div class="message">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <label for="fname">First Name*</label>
            <input 
                type="text" 
                id="fname" 
                name="fname"
                placeholder="David" 
                required 
                autofocus
                pattern="[A-Za-z\s]{2,50}"
                title="First name should only contain letters and be 2-50 characters long">
        </div>

        <div class="row">
            <label for="lname">Last Name</label>
            <input 
                type="text" 
                id="lname" 
                name="lname"
                placeholder="Johnson"
                pattern="[A-Za-z\s]{0,50}"
                title="Last name should only contain letters and be 0-50 characters long">
        </div>

        <div class="row">
            <label for="email">Email*</label>
            <input 
                type="email"
                name="email" 
                id="email" 
                placeholder="david123@example.com" 
                required
                title="Please enter a valid email address">
        </div>

        <div class="row">
            <label for="password">Password*</label>
            <div>
                <input 
                    type="password" 
                    name="pass"
                    id="password" 
                    placeholder="********" 
                    required
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+]).{8,}"
                    title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character">
                <img src="Images/eye-fill.png" class="eye" id="eye">
            </div>
        </div>

        <div class="row">
            <label for="confPass">Confirm Password*</label>
            <input 
                type="password" 
                name="pass1"
                id="confPass" 
                placeholder="********" 
                required>
        </div>

        <div class="row">
            <label for="address">Address</label>
            <input 
                type="text" 
                name="address"
                id="address" 
                placeholder="Los Angeles"
                maxlength="200">
        </div>

        <button type="submit" name="submit">Sign Up</button>

        <p class="login">Already exists? <a href="login.php">Login</a></p>
    </form>

    <script src="js/signUpShowPassword.js"></script>
    
    <script>
    // Basic client-side validation
    document.getElementById('signupForm').addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confPass').value;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            event.preventDefault();
        }
    });
    </script>
</body>
</html>