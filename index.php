<?php
session_start();
include"session_protection.php";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollify - Dashboard</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">

<!-- Whole body font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
<!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="NEWCSS/simplified-index.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo-container">
                <img src="Images/main_icon.png" alt="Pollify Logo" class="logo">
                <h1 class="site-title">Pollify</h1>
            </div>
            <div class="user-nav">
                <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>!</div>
                <div class="nav-links">
                    <a href="mainContent.php">Home</a>
                    <a href="newPost.php">Create Poll</a>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="card">
                <h2 class="card-title">Recent Polls</h2>
                <div class="card-content">
                    <p>View and participate in the most recent polls from the community.</p>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">My Polls</h2>
                <div class="card-content">
                    <p>Manage and view statistics for polls you've created.</p>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Popular Polls</h2>
                <div class="card-content">
                    <p>Discover trending polls with the most votes and engagement.</p>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="mainContent.php" class="action-button">Browse Polls</a>
        </div>
    </div>
</body>
</html>