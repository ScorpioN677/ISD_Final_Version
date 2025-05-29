<?php
session_start();
include 'config.php';

if(isset($_POST["submit"])) {
    $email = $_SESSION['reset_email'];
    $newPass = $_POST['newPass'];
    $confPass = $_POST['confPass'];
    $validationResult = validatePassword($newPass);

    if ($newPass !== $confPass) {
        header("Location: resetPassword.php?error=" . urlencode("Passwords do not match."));
        exit();
    }

    if ($validationResult !== true) {
        header("Location: resetPassword.php?error=" . urlencode($validationResult));
        exit();
    }

    $escaped_pwd = mysqli_real_escape_string($con, password_hash($newPass, PASSWORD_BCRYPT));
    $escaped_email = mysqli_real_escape_string($con, $email);
    $sql = "UPDATE users SET password = '$escaped_pwd' WHERE email = '$escaped_email'";


    if(mysqli_query($con, $sql)) {
        session_destroy();
        header("Location: login.php");
    } else {
        header("Location: resetPassword.php?error=" . urlencode("Database error: " . mysqli_error($con)));
        exit();
    }
}

function validatePassword($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long";
    if (!preg_match("#[0-9]+#", $password)) return "Password must contain at least one number";
    if (!preg_match("#[A-Z]+#", $password)) return "Password must contain at least one uppercase letter";
    if (!preg_match("#[a-z]+#", $password)) return "Password must contain at least one lowercase letter";
    if (!preg_match("/[\'^£$%&*()}{@#~?><>,|=_+!-]/", $password)) return "Password must contain at least one special character";
    return true;
}
?>