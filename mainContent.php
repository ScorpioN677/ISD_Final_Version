<!-- Updated mainContent.php with responsive search functionality -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollify</title>
    <link rel="icon" type="image/x-icon" href="Images/main_icon.png">
    <!-- Whole page font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400.700&display=swap" rel="stylesheet">
    <!-- Title font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200.900&display=swap" rel="stylesheet">
    <!-- Add polls.css for styling the polls -->
    <link rel="stylesheet" href="css/polls.css">
    <link rel="stylesheet" href="CSS/notifications.css">
    <!-- Add search CSS -->
    <link rel="stylesheet" href="CSS/search_users.css">
    <link rel="stylesheet" href="CSS/mainContent.css">
</head>
<body>
    <?php
    // Include database configuration file
    include_once 'config.php';
    
    // Function to fetch categories from the database
    function fetchCategories() {
        global $con; // Use the connection from config.php
        
        // Query to get all categories
        $sql = "SELECT * FROM categories ORDER BY name";
        $result = mysqli_query($con, $sql);
        
        $categories = array();
        
        // Check if any categories were found
        if ($result && mysqli_num_rows($result) > 0) {
            // Loop through each category and add to array
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    // Get all categories
    $categories = fetchCategories();
    ?>

    <nav class="navTop">
        <img src="Images/main_icon.png" alt="Logo" class="logo">

        <div class="navbar">
            <h2 class="title">Pollify</h2>

            <!-- Desktop Navigation Items -->
            <div class="nav-items">
                <div class="search" id="searchContainer">
                    <input type="search" placeholder="Search users..." aria-label="Search users">
                    <div class="namesList" id="searchResults"></div>
                </div>

                <div class="filter" id="filter">
                    <img src="Images/check.png" alt="Filtered" id="checkMark">
                    <p>Filter Polls</p>
                    <img src="Images/arrow.png" alt="Arrow" class="filterArrow">

                    <form action="" class="filterPolls" id="filtering">
                        <fieldset>
                            <legend>Filter Lists based on your interests</legend>
                            
                            <?php 
                            // Display all categories from the database
                            foreach ($categories as $category) {
                                echo '<div>';
                                echo '<input type="checkbox" id="cat' . $category['category_id'] . '" name="categories[]" value="' . $category['category_id'] . '">';
                                echo '<label for="cat' . $category['category_id'] . '">' . $category['name'] . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </fieldset>

                        <div style="cursor: text;">Chosen Categories:</div>
                        <div class="checkedCat"></div>

                        <div class="buttons">
                            <button type="submit">Filter</button>
                            <button type="button">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="profile">
                    <img src="Images/profile_pic.png" alt="Profile Pic" class="profilePic">
                    <img src="Images/arrow.png" alt="Arrow" class="profileArrow">

                    <div id="profileList">
                        <a href="profile.php">Profile</a>
                        <a href="index.php">About Us</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Bar (Always Visible) -->
            <div class="mobile-search-container" id="mobileSearchContainer">
                <div class="mobile-search-wrapper">
                    <input type="search" placeholder="Search users..." aria-label="Search users" class="mobile-search-input">
                    <div class="mobile-search-icon">
                        <img src="Images/search.png" alt="Search">
                    </div>
                </div>
                <div class="namesList mobile-search-results" id="mobileSearchResults"></div>
            </div>

            <!-- Hamburger Menu Button -->
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Popup -->
    <div class="mobile-menu" id="mobileMenu">
        <!-- Filter Polls -->
        <div class="mobile-menu-item" id="mobileFilterToggle">
            <h3>🔍 Filter Polls</h3>
            <p>Customize your feed by interests</p>
        </div>

        <!-- Profile -->
        <div class="mobile-menu-item" onclick="window.location.href='profile.php'">
            <h3>👤 Profile</h3>
            <p>View and edit your profile</p>
        </div>

        <!-- About Us -->
        <div class="mobile-menu-item" onclick="window.location.href='index.php'">
            <h3>ℹ️ About Us</h3>
            <p>Learn more about Pollify</p>
        </div>

        <!-- Logout -->
        <div class="mobile-menu-item" onclick="window.location.href='logout.php'">
            <h3>🚪 Logout</h3>
            <p>Sign out of your account</p>
        </div>
    </div>

    <!-- Mobile Filter Popup -->
    <div class="mobile-filter-popup" id="mobileFilterPopup">
        <div class="mobile-filter-popup-content">
            <div class="mobile-filter-header">
                <h3>Filter Polls</h3>
                <span class="mobile-filter-close" id="mobileFilterClose">&times;</span>
            </div>
            
            <form action="" id="mobileFiltering">
                <fieldset>
                    <legend>Filter Lists based on your interests</legend>
                    
                    <?php 
                    // Display all categories from the database for mobile
                    foreach ($categories as $category) {
                        echo '<div>';
                        echo '<input type="checkbox" id="mobileCat' . $category['category_id'] . '" name="categories[]" value="' . $category['category_id'] . '">';
                        echo '<label for="mobileCat' . $category['category_id'] . '">' . $category['name'] . '</label>';
                        echo '</div>';
                    }
                    ?>
                </fieldset>

                <div style="cursor: text; margin: 15px 0 10px 0; font-weight: 600;">Chosen Categories:</div>
                <div class="checkedCatMobile"></div>

                <div class="buttons">
                    <button type="submit">Apply Filter</button>
                    <button type="button" id="mobileFilterCancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <nav class="navBot">
        <div class="notification">
            <div class="counter">0</div>
            <img src="Images/bell.png" alt="Notification" class="bell">

            <div id="notificationList"></div>
        </div>

        <div class="myPolls">
            <a href="profile.php#myPolls" tabindex="-1"><img src="Images/posts.png" alt="My Polls"></a>
        </div>

        <a href="newPost.php" class="newPost" tabindex="-1">+</a>

        <div class="favorites">
            <a href="profile.php#favorite" tabindex="-1"><img src="Images/favorites.png" alt="Favorites"></a>
        </div>

        <div class="chatbot">
            <img src="Images/chatbot.png" alt="Chatbot" id="chatbotImg">

            <div id="chatbotList">
                <img src="Images/arrow.png" alt="arrow" id="chatbotArrow">

                <div class="messages" id="messages"></div>

                <div class="sendMessage">
                    <input type="text" id="userInput" placeholder="Ask Anything...">
                    <button type="button" id="sendBtn">Send</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content area - Polls Container -->
    <main>
        <div id="pollsContainer" class="polls-container">
            <!-- Polls will be loaded here via AJAX -->
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/lists.js"></script>
    <script src="js/polls.js"></script>
    <script src="js/search.js"></script>
    <script src="js/chatbot.js"></script>
    
    <script>
    $(document).ready(function() {
        // Make usernames clickable to go to their profile (non-anonymous only)
        $(document).on('click', '.username:not(.username-anonymous)', function() {
            const userId = $(this).data('user-id') || 
                           $(this).closest('.poll-card').data('creator-id') || 
                           $(this).closest('.user-profile').data('user-id');
            
            if (userId) {
                window.location.href = `profile.php?user_id=${userId}`;
            }
        });

        // Style usernames to look clickable (non-anonymous only)
        $(document).on('mouseenter', '.username:not(.username-anonymous)', function() {
            $(this).css({
                'cursor': 'pointer',
                'color': '#BE6AFF',
                'text-decoration': 'underline'
            });
        }).on('mouseleave', '.username:not(.username-anonymous)', function() {
            $(this).css({
                'text-decoration': 'none'
            });
        });
        
        // Debug: Log when page is ready
        console.log('mainContent.php ready - responsive search implemented');

        // Hamburger menu functionality
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileFilterToggle = document.getElementById('mobileFilterToggle');
        const mobileFilterPopup = document.getElementById('mobileFilterPopup');
        const mobileFilterClose = document.getElementById('mobileFilterClose');
        const mobileFilterCancel = document.getElementById('mobileFilterCancel');

        // Toggle mobile menu
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('show');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });

        // Open mobile filter popup
        mobileFilterToggle.addEventListener('click', function() {
            mobileFilterPopup.classList.add('show');
        });

        // Close mobile filter popup
        function closeMobileFilterPopup() {
            mobileFilterPopup.classList.remove('show');
        }

        mobileFilterClose.addEventListener('click', closeMobileFilterPopup);
        mobileFilterCancel.addEventListener('click', closeMobileFilterPopup);

        // Close filter popup when clicking outside
        mobileFilterPopup.addEventListener('click', function(e) {
            if (e.target === mobileFilterPopup) {
                closeMobileFilterPopup();
            }
        });

        // Close mobile menu when clicking on menu items (except filter)
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu || 
                (e.target.closest('.mobile-menu-item') && 
                 !e.target.closest('#mobileFilterToggle'))) {
                closeMobileMenu();
            }
        });

        function closeMobileMenu() {
            hamburger.classList.remove('active');
            mobileMenu.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (mobileFilterPopup.classList.contains('show')) {
                    closeMobileFilterPopup();
                } else if (mobileMenu.classList.contains('show')) {
                    closeMobileMenu();
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeMobileMenu();
                closeMobileFilterPopup();
            }
        });

        // Prevent mobile menu from showing on desktop
        window.addEventListener('load', function() {
            if (window.innerWidth > 992) {
                mobileMenu.classList.remove('show');
                hamburger.classList.remove('active');
            }
        });

        // Mobile filter form submission
        document.getElementById('mobileFiltering').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get selected categories
            const selectedCategories = [];
            const checkboxes = this.querySelectorAll('input[name="categories[]"]:checked');
            checkboxes.forEach(function(checkbox) {
                selectedCategories.push(checkbox.value);
            });
            
            // Update checked categories display
            updateCheckedCategoriesMobile(selectedCategories);
            
            // Close popups
            closeMobileFilterPopup();
            closeMobileMenu();
            
            // Apply filter (you can integrate with your existing filter logic)
            console.log('Mobile filter applied with categories:', selectedCategories);
            
            // Show checkmark if categories are selected
            if (selectedCategories.length > 0) {
                $('#checkMark').show();
            }
        });

        // Update checked categories display for mobile
        function updateCheckedCategoriesMobile(selectedCategories) {
            const checkedCatDiv = $('.checkedCatMobile');
            checkedCatDiv.empty();
            
            if (selectedCategories.length > 0) {
                selectedCategories.forEach(function(categoryId) {
                    const label = $(`#mobileCat${categoryId}`).next('label').text();
                    checkedCatDiv.append(`<span class="selected-category">${label}</span>`);
                });
            } else {
                checkedCatDiv.append('<span>No categories selected</span>');
            }
        }

        // Sync desktop and mobile filter selections
        function syncFilterSelections(source) {
            const sourcePrefix = source === 'desktop' ? 'cat' : 'mobileCat';
            const targetPrefix = source === 'desktop' ? 'mobileCat' : 'cat';
            
            $(`input[id^="${sourcePrefix}"]`).each(function() {
                const categoryId = this.id.replace(sourcePrefix, '');
                const targetCheckbox = $(`#${targetPrefix}${categoryId}`);
                targetCheckbox.prop('checked', this.checked);
            });
        }

        // Listen for changes in desktop filter
        $(document).on('change', 'input[id^="cat"]:not([id^="mobileCat"])', function() {
            syncFilterSelections('desktop');
        });

        // Listen for changes in mobile filter
        $(document).on('change', 'input[id^="mobileCat"]', function() {
            syncFilterSelections('mobile');
        });

        // Initialize search for both desktop and mobile
        if (typeof UserSearch !== 'undefined') {
            UserSearch.initializeMobile();
        }
    });
    </script>
</body>
</html>