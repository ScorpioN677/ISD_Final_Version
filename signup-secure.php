<?php
session_start();
include "config.php"; 

if(isset($_POST["fname"], $_POST["email"], $_POST["pass"], $_POST["pass1"])) {
    if($_POST["pass"] === $_POST["pass1"]) {
        $pass = $_POST["pass"];
        $validationResult = validatePassword($pass);

        if ($validationResult !== true) {
            header("Location: signup.php?error=" . urlencode($validationResult));
            exit();
        }

        // Validate email format
        $email = $_POST['email'];
        $emailValidation = validateEmail($email);
        if ($emailValidation !== true) {
            header("Location: signup.php?error=" . urlencode($emailValidation));
            exit();
        }

        // Sanitize input data - get optional fields with default empty values
        $fname   = trim($_POST['fname']);
        $lname   = isset($_POST['lname']) ? trim($_POST['lname']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $encrypted_pwd = password_hash($pass, PASSWORD_BCRYPT);
        
        // Check if email already exists - using prepared statement
        $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE email = ?");
        if ($stmt === false) {
            header("Location: signup.php?error=" . urlencode("Database error: " . mysqli_error($con)));
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            header("Location: signup.php?error=" . urlencode("Email already exists. Please use a different email."));
            exit();
        }
        
        mysqli_stmt_close($stmt);

        // Default values for non-essential fields
        $gender = 0; // Default gender value
        $phone_number = ''; // Empty phone number
        $bio = ''; // Empty bio

        // Insert new user - using prepared statement
        $stmt = mysqli_prepare($con, "INSERT INTO users (first_name, last_name, email, password, address, gender, phone_number, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            header("Location: signup.php?error=" . urlencode("Database error: " . mysqli_error($con)));
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, "sssssiss", $fname, $lname, $email, $encrypted_pwd, $address, $gender, $phone_number, $bio);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($con);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $fname;
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            mysqli_stmt_close($stmt);
            header("Location: index.php");
            exit();
        } else {
            mysqli_stmt_close($stmt);
            header("Location: signup.php?error=" . urlencode("Database error: " . mysqli_error($con)));
            exit();
        }

    } else {
        header("Location: signup.php?error=" . urlencode("Passwords do not match."));
        exit();
    }
} else {
    header("Location: signup.php?error=" . urlencode("Please fill all the required fields."));
    exit();
}

function validatePassword($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long";
    if (!preg_match("#[0-9]+#", $password)) return "Password must contain at least one number";
    if (!preg_match("#[A-Z]+#", $password)) return "Password must contain at least one uppercase letter";
    if (!preg_match("#[a-z]+#", $password)) return "Password must contain at least one lowercase letter";
    if (!preg_match("/[\'^£$%&*()}{@#~?><>,|=_+!-]/", $password)) return "Password must contain at least one special character";
    return true;
}

function validateEmail($email) {
    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    return true;
}
?>