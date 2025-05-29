<?php
include_once "session_protection.php";
include_once "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if (isset($_POST['submit'])) {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];

    // Sanitize and validate inputs
    $category_id = isset($_POST['category']) ? (int)$_POST['category'] : 0;
    $question = isset($_POST['question']) ? trim($_POST['question']) : '';
    $answers = isset($_POST['answers']) ? $_POST['answers'] : [];
    
    // Check for options
    $isAnonymous = isset($_POST['anonymous']) ? 1 : 0;
    $isPublic = isset($_POST['followers_only']) ? 0 : 1; // If followers_only is checked, it's not public

    // Validation
    $errors = [];

    // Category validation
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category";
    } else {
        // Verify category exists in database
        $check_cat = mysqli_prepare($con, "SELECT category_id FROM categories WHERE category_id = ?");
        mysqli_stmt_bind_param($check_cat, "i", $category_id);
        mysqli_stmt_execute($check_cat);
        $cat_result = mysqli_stmt_get_result($check_cat);
        if (mysqli_num_rows($cat_result) == 0) {
            $errors[] = "Invalid category selected";
        }
        mysqli_stmt_close($check_cat);
    }

    // Question validation
    if (empty($question)) {
        $errors[] = "Question cannot be empty";
    } elseif (strlen($question) > 255) {
        $errors[] = "Question must be less than 255 characters";
    }

    // Answers validation
    $valid_answers = [];
    if (count($answers) < 2) {
        $errors[] = "At least two answers are required";
    } else {
        foreach ($answers as $index => $answer) {
            $answer = trim($answer);
            if (empty($answer)) {
                $errors[] = "Answer option " . ($index + 1) . " cannot be empty";
            } elseif (strlen($answer) > 255) {
                $errors[] = "Answer option " . ($index + 1) . " must be less than 255 characters";
            } else {
                $valid_answers[] = $answer;
            }
        }
        
        // Check for duplicate answers
        if (count($valid_answers) !== count(array_unique($valid_answers))) {
            $errors[] = "Duplicate answers are not allowed";
        }
        
        // Must have at least 2 valid answers
        if (count($valid_answers) < 2) {
            $errors[] = "At least two valid answers are required";
        }
    }

    // If there are errors, redirect back with error message and preserve form data
    if (!empty($errors)) {
        $error_message = urlencode(implode(", ", $errors));
        $query_params = array(
            'error' => $error_message,
            'category' => $category_id,
            'question' => $question,
            'anonymous' => $isAnonymous,
            'followers_only' => ($isPublic == 0) ? 1 : 0
        );
        
        // Add first two answers to preserve them
        if (isset($answers[0])) $query_params['answer1'] = $answers[0];
        if (isset($answers[1])) $query_params['answer2'] = $answers[1];
        
        $query_string = http_build_query($query_params);
        header("Location: newPost.php?" . $query_string);
        exit();
    }

    // If validation passes, insert poll into database
    try {
        // Start transaction
        mysqli_begin_transaction($con);

        // Current date in yyyy-mm-dd format
        $currentDate = date("Y-m-d");

        // Insert poll using prepared statement
        $poll_stmt = mysqli_prepare($con, "INSERT INTO polls (question, date_of_creation, isAnonymous, isPublic, CreatedBy, CategoryID) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($poll_stmt, "ssiiii", $question, $currentDate, $isAnonymous, $isPublic, $user_id, $category_id);
        
        if (!mysqli_stmt_execute($poll_stmt)) {
            throw new Exception("Failed to create poll: " . mysqli_error($con));
        }
        
        // Get the poll ID
        $poll_id = mysqli_insert_id($con);
        mysqli_stmt_close($poll_stmt);
        
        // Insert answers using prepared statement
        $answer_stmt = mysqli_prepare($con, "INSERT INTO answers (text, vote_count, poll_id) VALUES (?, 0, ?)");
        
        foreach ($valid_answers as $answer) {
            mysqli_stmt_bind_param($answer_stmt, "si", $answer, $poll_id);
            
            if (!mysqli_stmt_execute($answer_stmt)) {
                throw new Exception("Failed to create answer: " . mysqli_error($con));
            }
        }
        
        mysqli_stmt_close($answer_stmt);
        
        // Commit transaction
        mysqli_commit($con);
        
        // Redirect to success page or poll view
        header("Location: mainContent.php?success=poll_created&poll_id=" . $poll_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($con);
        
        // Log the error (in production, you should log this properly)
        error_log("Poll creation error: " . $e->getMessage());
        
        $error_message = urlencode("An error occurred while creating the poll. Please try again.");
        $query_params = array(
            'error' => $error_message,
            'category' => $category_id,
            'question' => $question,
            'anonymous' => $isAnonymous,
            'followers_only' => ($isPublic == 0) ? 1 : 0
        );
        
        if (isset($answers[0])) $query_params['answer1'] = $answers[0];
        if (isset($answers[1])) $query_params['answer2'] = $answers[1];
        
        $query_string = http_build_query($query_params);
        header("Location: newPost.php?" . $query_string);
        exit();
    }
} else {
    // If not submitted through the form, redirect to the form
    header("Location: newPost.php");
    exit();
}