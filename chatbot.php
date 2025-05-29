<?php
// chatbot.php - Pollify Chatbot Backend

include_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Start session to get user ID
session_start();
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to use the chatbot'
    ]);
    exit;
}

// Get user message
$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($userMessage)) {
    echo json_encode([
        'success' => false,
        'message' => 'Message cannot be empty'
    ]);
    exit;
}

// Get user's name for personalization
$userQuery = "SELECT first_name FROM users WHERE user_id = ?";
$userStmt = mysqli_prepare($con, $userQuery);
mysqli_stmt_bind_param($userStmt, "i", $userId);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$userData = mysqli_fetch_assoc($userResult);
$userName = $userData['first_name'] ?? 'User';
mysqli_stmt_close($userStmt);

// Sanitize user message
$userMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

// Generate bot response
$botResponse = generateBotResponse($userMessage, $userName, $userId, $con);

// Store conversation in database (optional)
storeChatMessage($userId, $userMessage, $botResponse, $con);

// Return response
echo json_encode([
    'success' => true,
    'user_message' => $userMessage,
    'bot_response' => $botResponse,
    'timestamp' => date('H:i')
]);

/**
 * Generate bot response based on user input
 */
function generateBotResponse($message, $userName, $userId, $con) {
    $message = strtolower($message);
    
    // Predefined responses for Pollify
    $responses = [
        // Greetings
        'greetings' => [
            'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'
        ],
        'greeting_responses' => [
            "Hi $userName! 👋 Welcome to Pollify! How can I help you today?",
            "Hello $userName! 🌟 Ready to create some awesome polls?",
            "Hey there $userName! 😊 What would you like to know about Pollify?"
        ],
        
        // Help requests
        'help' => [
            'help', 'how', 'what', 'guide', 'tutorial', 'support'
        ],
        'help_responses' => [
            "I'm here to help! 🤖 You can ask me about:\n• Creating polls\n• Managing your profile\n• Following other users\n• Finding interesting polls\n• Troubleshooting issues",
            "Need assistance? I can help you with:\n📊 Poll creation and management\n👤 Profile settings\n🔍 Discovering content\n❓ General questions about Pollify"
        ],
        
        // Poll creation
        'create_poll' => [
            'create poll', 'make poll', 'new poll', 'how to create', 'add poll'
        ],
        'create_responses' => [
            "Creating a poll is easy! 🎯\n1. Click the '+' button at the bottom\n2. Choose a category\n3. Write your question\n4. Add 2-4 answer options\n5. Set privacy options\n6. Hit 'Add' to publish!",
            "To create a poll:\n✏️ Click the big '+' button\n📝 Fill in your question and answers\n⚙️ Choose your settings (public/private, anonymous)\n🚀 Publish and watch the votes come in!"
        ],
        
        // Profile help
        'profile' => [
            'profile', 'edit profile', 'change password', 'picture', 'bio'
        ],
        'profile_responses' => [
            "Managing your profile? 👤\n• Click your profile picture in the top bar\n• Select 'Profile' to view your polls\n• Use 'Edit Profile' to update info\n• Change password from edit profile page",
            "Profile tips:\n🖼️ Upload a profile picture to stand out\n📝 Write an interesting bio\n🔒 Keep your info updated and secure\n📊 Check your poll performance"
        ],
        
        // Following
        'follow' => [
            'follow', 'followers', 'following', 'friends'
        ],
        'follow_responses' => [
            "Building your network! 🤝\n• Follow users by clicking 'Follow' on their polls\n• View your followers/following from your profile\n• Search for users using the search bar\n• Get notified when people you follow post",
            "Social features:\n👥 Follow interesting poll creators\n🔔 Get notifications from followed users\n🔍 Discover new users through search\n💬 Engage with comments and votes"
        ],
        
        // Features
        'features' => [
            'features', 'what can i do', 'functionality', 'options'
        ],
        'feature_responses' => [
            "Pollify features! ⭐\n📊 Create unlimited polls with multiple categories\n👥 Follow and interact with other users\n💬 Comment and reply to discussions\n❤️ Favorite polls you love\n🔔 Real-time notifications\n🔍 Search for users and content",
            "You can:\n🎯 Create polls (public, private, anonymous)\n🗳️ Vote and see real-time results\n💭 Join conversations with comments\n👤 Customize your profile\n🌟 Discover trending content\n📱 Get instant notifications"
        ],
        
        // Troubleshooting
        'problem' => [
            'problem', 'issue', 'bug', 'error', 'not working', 'broken'
        ],
        'problem_responses' => [
            "Having trouble? 🔧\n• Try refreshing the page\n• Clear your browser cache\n• Make sure you're logged in\n• Check your internet connection\n\nStill having issues? Contact our support team!",
            "Technical issues? Let's fix it:\n🔄 Refresh your browser\n🧹 Clear cache and cookies\n🔐 Ensure you're logged in\n📶 Check your connection\n\nIf problems persist, reach out to support!"
        ],
        
        // Categories
        'categories' => [
            'categories', 'topics', 'filter', 'category'
        ],
        'category_responses' => [
            "Poll categories help organize content! 📂\n• Use the 'Filter Polls' option\n• Choose categories that interest you\n• Create polls in relevant categories\n• Discover content by topic"
        ],
        
        // Thanks
        'thanks' => [
            'thank you', 'thanks', 'appreciate', 'helpful'
        ],
        'thanks_responses' => [
            "You're welcome, $userName! 😊 Happy polling!",
            "Glad I could help! 🌟 Enjoy using Pollify!",
            "Anytime! Feel free to ask if you need more help! 🤖"
        ],
        
        // Goodbye
        'goodbye' => [
            'bye', 'goodbye', 'see you', 'later', 'exit'
        ],
        'goodbye_responses' => [
            "Goodbye $userName! 👋 Come back anytime you need help!",
            "See you later! 🌟 Happy polling on Pollify!",
            "Take care $userName! 😊 Feel free to chat anytime!"
        ]
    ];
    
    // Check for specific patterns
    foreach ($responses as $key => $patterns) {
        if (strpos($key, '_responses') !== false) continue;
        
        foreach ($patterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                $responseKey = $key . '_responses';
                if (isset($responses[$responseKey])) {
                    return $responses[$responseKey][array_rand($responses[$responseKey])];
                }
            }
        }
    }
    
    // Get user statistics for personalized responses
    $stats = getUserStats($userId, $con);
    
    // Context-aware responses based on user activity
    if (strpos($message, 'stats') !== false || strpos($message, 'my') !== false) {
        return "Your Pollify stats! 📈\n" .
               "📊 Polls created: {$stats['polls']}\n" .
               "🗳️ Votes cast: {$stats['votes']}\n" .
               "👥 Followers: {$stats['followers']}\n" .
               "🌟 Following: {$stats['following']}\n" .
               "Keep up the great work!";
    }
    
    // Default responses for unrecognized input
    $defaultResponses = [
        "I'm not sure I understand that, $userName. 🤔 Try asking about creating polls, managing your profile, or other Pollify features!",
        "Hmm, I didn't catch that! 🤖 You can ask me about polls, followers, profile settings, or just say 'help' for more options!",
        "I'm still learning! 📚 Try asking about Pollify features like creating polls, following users, or managing your account!",
        "Not sure what you mean, $userName! 😅 Ask me about polls, profile, followers, or type 'help' to see what I can do!"
    ];
    
    return $defaultResponses[array_rand($defaultResponses)];
}

/**
 * Get user statistics for personalized responses
 */
function getUserStats($userId, $con) {
    $stats = [
        'polls' => 0,
        'votes' => 0,
        'followers' => 0,
        'following' => 0
    ];
    
    // Get polls count
    $pollsQuery = "SELECT COUNT(*) as count FROM polls WHERE CreatedBy = ?";
    $pollsStmt = mysqli_prepare($con, $pollsQuery);
    mysqli_stmt_bind_param($pollsStmt, "i", $userId);
    mysqli_stmt_execute($pollsStmt);
    $result = mysqli_stmt_get_result($pollsStmt);
    $data = mysqli_fetch_assoc($result);
    $stats['polls'] = $data['count'];
    mysqli_stmt_close($pollsStmt);
    
    // Get votes count
    $votesQuery = "SELECT COUNT(*) as count FROM responses WHERE user_id = ?";
    $votesStmt = mysqli_prepare($con, $votesQuery);
    mysqli_stmt_bind_param($votesStmt, "i", $userId);
    mysqli_stmt_execute($votesStmt);
    $result = mysqli_stmt_get_result($votesStmt);
    $data = mysqli_fetch_assoc($result);
    $stats['votes'] = $data['count'];
    mysqli_stmt_close($votesStmt);
    
    // Get followers count
    $followersQuery = "SELECT COUNT(*) as count FROM follows WHERE FollowingID = ?";
    $followersStmt = mysqli_prepare($con, $followersQuery);
    mysqli_stmt_bind_param($followersStmt, "i", $userId);
    mysqli_stmt_execute($followersStmt);
    $result = mysqli_stmt_get_result($followersStmt);
    $data = mysqli_fetch_assoc($result);
    $stats['followers'] = $data['count'];
    mysqli_stmt_close($followersStmt);
    
    // Get following count
    $followingQuery = "SELECT COUNT(*) as count FROM follows WHERE FollowerID = ?";
    $followingStmt = mysqli_prepare($con, $followingQuery);
    mysqli_stmt_bind_param($followingStmt, "i", $userId);
    mysqli_stmt_execute($followingStmt);
    $result = mysqli_stmt_get_result($followingStmt);
    $data = mysqli_fetch_assoc($result);
    $stats['following'] = $data['count'];
    mysqli_stmt_close($followingStmt);
    
    return $stats;
}

/**
 * Store chat message in database (optional - for chat history)
 */
function storeChatMessage($userId, $userMessage, $botResponse, $con) {
    // Create chat_history table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_message TEXT NOT NULL,
            bot_response TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ";
    
    mysqli_query($con, $createTable);
    
    // Store the conversation
    $insertQuery = "INSERT INTO chat_history (user_id, user_message, bot_response) VALUES (?, ?, ?)";
    $insertStmt = mysqli_prepare($con, $insertQuery);
    
    if ($insertStmt) {
        mysqli_stmt_bind_param($insertStmt, "iss", $userId, $userMessage, $botResponse);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
    }
}
?>