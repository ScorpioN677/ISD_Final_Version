<?php
include_once "session_protection.php";
include_once "config.php";

// Get categories using prepared statement
$cat_stmt = mysqli_prepare($con, "SELECT * FROM categories ORDER BY name");
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self';">
    <title>New Post</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="NEWCSS/simplified-newPost.css">
</head>
<body>
    
    <img src="Images/main_icon.png" alt="Logo not found" class="logo">

    <form action="process_poll.php" method="POST">

        <p class="title">Add New Post</p>
        
        <?php if(isset($_GET['error'])): ?>
        <div class="message">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['success'])): ?>
        <div class="success-message">
            Poll created successfully!
        </div>
        <?php endif; ?>

        <div class="question-answers">
           <select name="category" id="category" required>
    <option value="">-- Select a Category --</option>
    <?php
    // Display categories from the prepared statement results
    if ($cat_result && mysqli_num_rows($cat_result) > 0) {
        while ($cat = mysqli_fetch_assoc($cat_result)) {
            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['category_id']) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($cat['category_id']) . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
        }
    }
    mysqli_stmt_close($cat_stmt);
    ?>
</select>

            <input type="text" name="question" placeholder="Question" class="question" 
                   value="<?php echo isset($_GET['question']) ? htmlspecialchars($_GET['question']) : ''; ?>" 
                   required autofocus>

            <div id="poll-answers">
                <input type="text" name="answers[]" placeholder="Answer 1" 
                       value="<?php echo isset($_GET['answer1']) ? htmlspecialchars($_GET['answer1']) : ''; ?>" required>
                <input type="text" name="answers[]" placeholder="Answer 2" 
                       value="<?php echo isset($_GET['answer2']) ? htmlspecialchars($_GET['answer2']) : ''; ?>" required>

                <div class="more"></div>

                <button type="button" class="addAnswer" id="addAnswerBtn">+</button>
            </div>
        </div>

        <fieldset class="boxes">
            <legend>Post Options</legend>

            <div>
                <input type="checkbox" id="anonymous" name="anonymous" 
                       <?php echo (isset($_GET['anonymous']) && $_GET['anonymous'] == '1') ? 'checked' : ''; ?>>
                <label for="anonymous">Post Anonymously <img src="Images/question-circle.png" alt="Info Image" title="Others won't see your name or profile. You can't be followed while posting anonymously"></label>
            </div>

            <div>
                <input type="checkbox" id="followers" name="followers_only"
                       <?php echo (isset($_GET['followers_only']) && $_GET['followers_only'] == '1') ? 'checked' : ''; ?>>
                <label for="followers">Followers Only <img src="Images/question-circle.png" alt="Info Image" title="Post can be shown only by followers"></label>
            </div>
        </fieldset>

        <div class="buttons">
            <button type="submit" name="submit" class="add">Add</button>
            <button type="button" id="return">Cancel</button>
        </div>
    </form>

    <script src="js/addAnswer.js"></script>
    <script src="js/returnToMain.js"></script>
</body>
</html>