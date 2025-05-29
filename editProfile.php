<?php
include_once "session_protection.php";
include_once "config.php";

$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

// Use prepared statement to get user data
$stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(!$result || mysqli_num_rows($result) == 0) {
    $error = "Error loading user profile";
} else {
    $user_data = mysqli_fetch_assoc($result);
}
mysqli_stmt_close($stmt);

if(isset($_POST['submit'])) {
    // Sanitize and validate input
    $bio = trim($_POST['bio']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']); // Added phone number
    $gender = $_POST['gender'] == 'female' ? 1 : 0;
    
    // Use prepared statement for update - now including phone_number
    $update_stmt = mysqli_prepare($con, "UPDATE users SET 
                 bio = ?, 
                 first_name = ?, 
                 last_name = ?, 
                 email = ?, 
                 address = ?, 
                 phone_number = ?,
                 gender = ?
                 WHERE user_id = ?");
    
    mysqli_stmt_bind_param($update_stmt, "ssssssii", $bio, $fname, $lname, $email, $address, $phone_number, $gender, $user_id);
    $update_result = mysqli_stmt_execute($update_stmt);
    
    if($update_result) {
        $message = "Profile updated successfully";
        
        // Refresh user data
        $refresh_stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($refresh_stmt, "i", $user_id);
        mysqli_stmt_execute($refresh_stmt);
        $result = mysqli_stmt_get_result($refresh_stmt);
        $user_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($refresh_stmt);
    } else {
        $error = "Error updating profile: " . mysqli_stmt_error($update_stmt);
    }
    mysqli_stmt_close($update_stmt);
    
    // Handle profile picture upload - no changes needed here
    if(isset($_FILES['edit_pic']) && $_FILES['edit_pic']['error'] == 0) {
        // Existing file upload code remains the same
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['edit_pic']['name'];
        $filetype = $_FILES['edit_pic']['type'];
        $filesize = $_FILES['edit_pic']['size'];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(!in_array(strtolower(string: $ext), $allowed)) {
            $error = "Error: Please select a valid image file format.";
        } else if($filesize > 5242880) { // 5MB 
            $error = "Error: File size is too large. Maximum 5MB allowed.";
        } else {
            $new_filename = uniqid() . '.' . $ext;
            $upload_dir = 'uploads/profile_pics/';

            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Check if file is actually an image
            $check = getimagesize($_FILES['edit_pic']['tmp_name']);
            if($check === false) {
                $error = "Error: File is not a valid image.";
            } else if(move_uploaded_file($_FILES['edit_pic']['tmp_name'], $upload_dir . $new_filename)) {
                // Check if user already has a profile picture
                $check_stmt = mysqli_prepare($con, "SELECT * FROM profilepictures WHERE user_id = ?");
                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if(mysqli_num_rows($check_result) > 0) {
                    // Update existing record
                    $update_pic_stmt = mysqli_prepare($con, "UPDATE profilepictures SET file = ?, type = ?, size = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($update_pic_stmt, "ssii", $new_filename, $filetype, $filesize, $user_id);
                    $pic_result = mysqli_stmt_execute($update_pic_stmt);
                    mysqli_stmt_close($update_pic_stmt);
                } else {
                    // Insert new record
                    $insert_stmt = mysqli_prepare($con, "INSERT INTO profilepictures (file, type, size, user_id) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($insert_stmt, "ssii", $new_filename, $filetype, $filesize, $user_id);
                    $pic_result = mysqli_stmt_execute($insert_stmt);
                    mysqli_stmt_close($insert_stmt);
                }
                
                mysqli_stmt_close($check_stmt);
                
                if($pic_result) {
                    $message .= " Profile picture updated successfully.";
                } else {
                    $error = "Error saving profile picture information.";
                }
            } else {
                $error = "Error uploading profile picture.";
            }
        }
    }
}

// Get profile picture
$pic_stmt = mysqli_prepare($con, "SELECT * FROM profilepictures WHERE user_id = ?");
mysqli_stmt_bind_param($pic_stmt, "i", $user_id);
mysqli_stmt_execute($pic_stmt);
$pic_result = mysqli_stmt_get_result($pic_stmt);
$profile_pic = "Images/edit_pic.png";

if($pic_result && mysqli_num_rows($pic_result) > 0) {
    $pic_data = mysqli_fetch_assoc($pic_result);
    $profile_pic = 'uploads/profile_pics/' . $pic_data['file'];
}
mysqli_stmt_close($pic_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
<!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
<!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-edit.css">
</head>
<body>

    <p class="title">Edit Profile</p>
    
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

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">

        <div class="profile_pic">
            <input type="file" id="edit_pic" name="edit_pic" accept="image/*">
            <label for="edit_pic">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" id="preview_pic">
            </label>
        </div>

        <div class="bio">
            <input type="text" name="bio" placeholder="Bio" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['bio']) : ''; ?>">
        </div>

        <div class="fName">
            <label for="fname">First Name</label>
            <input type="text" id="fname" name="fname" placeholder="David" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['first_name']) : ''; ?>" required>
        </div>

        <div class="lName">
            <label for="lname">Last Name</label>
            <input type="text" id="lname" name="lname" placeholder="Johnson" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['last_name']) : ''; ?>">
        </div>

        <div class="email">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="example_123@gmail.com" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['email']) : ''; ?>" required>
        </div>

        <!-- New phone number field -->
        <div class="phone">
            <label for="phone_number">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" placeholder="+961 03030303" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['phone_number']) : ''; ?>" pattern="[0-9+\-\(\) ]{7,20}">
        </div>

        <div class="address">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" placeholder="Los Angeles" value="<?php echo isset($user_data) ? htmlspecialchars($user_data['address']) : ''; ?>">
        </div>
        
        <div class="gender">
            <label for="gender">Gender</label>
            <select name="gender" id="gender">
                <option value="male" <?php echo (isset($user_data) && $user_data['gender'] == 0) ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo (isset($user_data) && $user_data['gender'] == 1) ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>

        <div class="buttons">
            <button type="submit" name="submit" class="save">Save</button>    
            <a href="changePassword.php">Change Password</a>
            <a href="deleteAccount.php">Delete Account</a>
            <a href="profile.php">Profile</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_pic').onchange = function(e) {
                if (e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(result) {
                        document.getElementById('preview_pic').src = result.target.result;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            };
        });
</script>
</body>
</html>