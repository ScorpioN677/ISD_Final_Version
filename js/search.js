/**
 * Enhanced Search System for Pollify - Mobile Responsive Version
 * @version 3.0.0
 * @description Complete responsive search functionality for both desktop and mobile platforms
 */
$(document).ready(function () {
    'use strict';

    const CONFIG = {
        minLength: 2,
        debounce: 300,
        maxResults: 12,
        timeout: 10000,
        cacheTime: 300000
    };

    // Desktop elements
    const $desktopInput = $('.search input');
    const $desktopResults = $('.search .namesList');
    const $desktopWrapper = $desktopInput.closest('.search');
    
    // Mobile elements
    const $mobileInput = $('.mobile-search-input');
    const $mobileResults = $('.mobile-search-results');
    const $mobileWrapper = $mobileInput.closest('.mobile-search-container');
    
    let timeout, currentQuery = '', isSearching = false;
    let desktopSelectedIndex = -1, mobileSelectedIndex = -1;
    const cache = new Map();

    // Initialize the search system
    init();

    function init() {
        createDesktopWrapper();
        bindDesktopEvents();
        bindMobileEvents();
        setupA11y();
        setupGlobalEvents();
        console.log('Responsive search system initialized');
    }

    function createDesktopWrapper() {
        if (!$desktopInput.parent('.search-wrapper').length) {
            $desktopInput.wrap('<div class="search-wrapper"><div class="search-input-container"></div></div>');
            if (!$('.search-icon-container').length) {
                $desktopInput.parent().append('<div class="search-icon-container"><img src="Images/search.png" id="searchIcon" alt="Search"></div>');
            }
        }
    }

    function bindDesktopEvents() {
        if ($desktopInput.length) {
            $desktopInput
                .on('input', function(e) { handleInput.call(this, e, 'desktop'); })
                .on('focus', function() { handleFocus('desktop'); })
                .on('blur', function() { 
                    setTimeout(() => {
                        if (!$desktopResults.is(':hover')) {
                            hideResults('desktop');
                        }
                    }, 200);
                })
                .on('keydown', function(e) { handleKeys(e, 'desktop'); });

            $('.search-icon-container').on('click', function() { 
                const query = $desktopInput.val().trim();
                if (query) {
                    search(query, 'desktop');
                } else {
                    $desktopInput.focus();
                }
            });
            
            $desktopResults
                .on('click', '.search-result-item', handleClick)
                .on('click', '.follow-btn-search', handleFollow)
                .on('mouseenter', function() {
                    // Keep results open when hovering
                })
                .on('mouseleave', function() {
                    // Close results when leaving if input not focused
                    if (!$desktopInput.is(':focus')) {
                        setTimeout(() => hideResults('desktop'), 200);
                    }
                });
        }
    }

    function bindMobileEvents() {
        if ($mobileInput.length) {
            $mobileInput
                .on('input', function(e) { handleInput.call(this, e, 'mobile'); })
                .on('focus', function() { handleFocus('mobile'); })
                .on('blur', function() { 
                    setTimeout(() => {
                        if (!$mobileResults.is(':hover')) {
                            hideResults('mobile');
                        }
                    }, 200);
                })
                .on('keydown', function(e) { handleKeys(e, 'mobile'); });

            $('.mobile-search-icon').on('click', function() { 
                const query = $mobileInput.val().trim();
                if (query) {
                    search(query, 'mobile');
                } else {
                    $mobileInput.focus();
                }
            });
            
            $mobileResults
                .on('click', '.search-result-item', handleClick)
                .on('click', '.follow-btn-search', handleFollow)
                .on('mouseenter touchstart', function() {
                    // Keep results open when hovering/touching
                })
                .on('mouseleave touchend', function() {
                    // Close results when leaving if input not focused
                    if (!$mobileInput.is(':focus')) {
                        setTimeout(() => hideResults('mobile'), 200);
                    }
                });
        }
    }

    function handleInput(e, platform) {
        const query = $(this).val().trim();
        clearTimeout(timeout);
        
        // Sync the other input field
        if (platform === 'desktop' && $mobileInput.length) {
            $mobileInput.val(query);
        } else if (platform === 'mobile' && $desktopInput.length) {
            $desktopInput.val(query);
        }
        
        if (!query) {
            hideResults('both');
            return;
        }
        
        if (query.length < CONFIG.minLength) {
            showMessage('Type at least 2 characters to search', platform);
            return;
        }
        
        if (query === currentQuery) {
            return;
        }

        showTyping(platform);
        timeout = setTimeout(() => search(query, platform), CONFIG.debounce);
    }

    function handleFocus(platform) {
        const $input = platform === 'desktop' ? $desktopInput : $mobileInput;
        const $wrapper = platform === 'desktop' ? $desktopWrapper : $mobileWrapper;
        
        $wrapper.addClass('focused');
        $input.attr('aria-expanded', 'true');
        
        const query = $input.val().trim();
        if (query.length >= CONFIG.minLength) {
            search(query, platform);
        }
    }

    function handleKeys(e, platform) {
        const $results = platform === 'desktop' ? $desktopResults : $mobileResults;
        const items = $results.find('.search-result-item');
        let selectedIndex = platform === 'desktop' ? desktopSelectedIndex : mobileSelectedIndex;
        
        switch(e.keyCode) {
            case 13: // Enter
                e.preventDefault();
                if (selectedIndex >= 0) {
                    const $selectedItem = items.eq(selectedIndex);
                    const userId = $selectedItem.data('user-id');
                    if (userId) {
                        window.location.href = `profile.php?user_id=${userId}`;
                    }
                } else {
                    const $input = platform === 'desktop' ? $desktopInput : $mobileInput;
                    const query = $input.val().trim();
                    if (query) {
                        search(query, platform);
                    }
                }
                break;
                
            case 27: // Escape
                e.preventDefault();
                hideResults(platform);
                const $input = platform === 'desktop' ? $desktopInput : $mobileInput;
                $input.blur();
                break;
                
            case 38: // Up
                e.preventDefault();
                navigate(-1, items, platform);
                break;
                
            case 40: // Down
                e.preventDefault();
                navigate(1, items, platform);
                break;
        }
    }

    function navigate(direction, items, platform) {
        if (!items.length) return;
        
        items.removeClass('highlighted').attr('aria-selected', 'false');
        
        let selectedIndex = platform === 'desktop' ? desktopSelectedIndex : mobileSelectedIndex;
        
        if (direction > 0) {
            // Down arrow
            selectedIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
        } else {
            // Up arrow
            selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
        }
        
        // Update the selected index for the platform
        if (platform === 'desktop') {
            desktopSelectedIndex = selectedIndex;
        } else {
            mobileSelectedIndex = selectedIndex;
        }
        
        const $selected = items.eq(selectedIndex).addClass('highlighted').attr('aria-selected', 'true');
        scrollToElement($selected, platform);
    }

    function search(query, platform) {
        if (isSearching || !query) return;
        
        // Check cache first
        const cacheKey = query.toLowerCase();
        const cached = cache.get(cacheKey);
        if (cached && Date.now() - cached.time < CONFIG.cacheTime) {
            displayResults(cached.data, query, platform);
            return;
        }

        isSearching = true;
        currentQuery = query;
        showLoading(platform);

        $.ajax({
            url: 'search_users.php',
            method: 'GET',
            data: { 
                q: query, 
                limit: CONFIG.maxResults 
            },
            timeout: CONFIG.timeout,
            success: function(response) {
                isSearching = false;
                try {
                    if (response.success && Array.isArray(response.data)) {
                        // Cache the results
                        cache.set(cacheKey, { 
                            data: response.data, 
                            time: Date.now() 
                        });
                        displayResults(response.data, query, platform);
                    } else {
                        showError(response.message || 'No results found', platform);
                    }
                } catch (error) {
                    console.error('Search response parsing error:', error);
                    showError('Error processing search results', platform);
                }
            },
            error: function(xhr, status, error) {
                isSearching = false;
                console.error('Search request failed:', status, error);
                let errorMessage = 'Search failed. Please try again.';
                
                if (status === 'timeout') {
                    errorMessage = 'Search timed out. Please try again.';
                } else if (xhr.status === 0) {
                    errorMessage = 'No internet connection. Please check your connection.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                showError(errorMessage, platform);
            }
        });
    }

    function displayResults(users, query, platform) {
        if (!users || users.length === 0) {
            showNoResults(query, platform);
            return;
        }
        
        const html = `
            <div class="search-header">
                <span class="search-results-count">${users.length} user${users.length !== 1 ? 's' : ''} found</span>
            </div>
            <div class="search-results-list">
                ${users.map((user, index) => createUserHTML(user, query, index)).join('')}
            </div>
        `;
        
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.html(html);
            showResults('desktop');
        }
        
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.html(html);
            showResults('mobile');
        }
        
        // Reset selection indexes
        if (platform === 'desktop' || platform === 'both') {
            desktopSelectedIndex = -1;
        }
        if (platform === 'mobile' || platform === 'both') {
            mobileSelectedIndex = -1;
        }
        
        animateResults(platform);
    }

    function createUserHTML(user, query, index) {
        const name = highlightText(user.name || '', query);
        const isFollowing = user.is_following === true || user.is_following === 1;
        const followingClass = isFollowing ? 'following' : '';
        const btnText = isFollowing ? 'Following' : 'Follow';
        const profilePic = user.profile_pic || 'Images/profile_pic.png';
        const bio = user.bio && user.bio !== 'No bio yet' ? user.bio : '';
        
        return `
            <div class="search-result-item" data-user-id="${user.user_id}" data-index="${index}" role="option" tabindex="-1">
                <div class="user-avatar">
                    <img src="${profilePic}" alt="${escapeHtml(user.name || '')}" class="user-profile-pic" loading="lazy" onerror="this.src='Images/profile_pic.png'">
                </div>
                <div class="user-info">
                    <a href="profile.php?user_id=${user.user_id}" class="user-name" tabindex="-1">${name}</a>
                    ${bio ? `<div class="user-bio">${escapeHtml(bio.substring(0, 60))}${bio.length > 60 ? '...' : ''}</div>` : ''}
                </div>
                <div class="user-actions">
                    <button class="follow-btn-search ${followingClass}" 
                            data-user-id="${user.user_id}" 
                            aria-label="${btnText} ${escapeHtml(user.name || '')}"
                            tabindex="-1">
                        ${btnText}
                    </button>
                </div>
            </div>
        `;
    }

    function handleClick(e) {
        // Don't navigate if clicking on follow button
        if ($(e.target).closest('.follow-btn-search').length) {
            return;
        }
        
        const userId = $(this).data('user-id');
        if (userId) {
            $(this).addClass('clicked');
            // Small delay for visual feedback
            setTimeout(() => {
                window.location.href = `profile.php?user_id=${userId}`;
            }, 100);
        }
    }

    function handleFollow(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const isFollowing = $btn.hasClass('following');
        const action = isFollowing ? 'unfollow' : 'follow';
        
        // Disable button and show loading state
        $btn.prop('disabled', true).addClass('loading');
        const originalText = $btn.text();
        $btn.text('...');

        $.ajax({
            url: 'toggle_follow_user.php',
            type: 'POST',
            data: { 
                user_id: userId, 
                action: action 
            },
            timeout: 5000,
            success: function(response) {
                $btn.prop('disabled', false).removeClass('loading');
                
                if (response.success) {
                    // Update button state
                    const newIsFollowing = response.is_following === true || response.is_following === 1;
                    $btn.toggleClass('following', newIsFollowing);
                    $btn.text(newIsFollowing ? 'Following' : 'Follow');
                    
                    // Update all instances of this user's follow button
                    $(`.follow-btn-search[data-user-id="${userId}"]`).each(function() {
                        $(this).toggleClass('following', newIsFollowing);
                        $(this).text(newIsFollowing ? 'Following' : 'Follow');
                    });
                    
                    showToast(`User ${action}ed successfully!`, 'success');
                } else {
                    // Revert button state
                    $btn.text(originalText);
                    showToast(response.message || `Failed to ${action} user`, 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.text(originalText);
                
                let errorMessage = 'Network error. Please try again.';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                }
                
                showToast(errorMessage, 'error');
                console.error('Follow/unfollow error:', status, error);
            }
        });
    }

    function highlightText(text, query) {
        if (!query || query.length < 2 || !text) {
            return escapeHtml(text);
        }
        
        const escapedText = escapeHtml(text);
        const escapedQuery = escapeRegex(query);
        const regex = new RegExp(`(${escapedQuery})`, 'gi');
        
        return escapedText.replace(regex, '<mark class="search-highlight">$1</mark>');
    }

    function showResults(platform) {
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.addClass('show').attr('aria-hidden', 'false');
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.addClass('show').attr('aria-hidden', 'false');
        }
    }

    function hideResults(platform) {
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.removeClass('show').attr('aria-hidden', 'true');
            $desktopWrapper.removeClass('focused');
            $desktopInput.attr('aria-expanded', 'false');
            desktopSelectedIndex = -1;
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.removeClass('show').attr('aria-hidden', 'true');
            $mobileWrapper.removeClass('focused');
            $mobileInput.attr('aria-expanded', 'false');
            mobileSelectedIndex = -1;
        }
    }

    function showLoading(platform) {
        const html = `
            <div class="search-loading">
                <div class="loading-spinner" aria-hidden="true"></div>
                <p>Searching users...</p>
            </div>
        `;
        
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.html(html).addClass('show');
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.html(html).addClass('show');
        }
    }

    function showTyping(platform) {
        const html = `
            <div class="search-typing">
                <div class="typing-indicator" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
                <p>Keep typing...</p>
            </div>
        `;
        
        if (platform === 'desktop' && !$desktopResults.hasClass('show')) {
            $desktopResults.html(html).addClass('show');
        }
        if (platform === 'mobile' && !$mobileResults.hasClass('show')) {
            $mobileResults.html(html).addClass('show');
        }
    }

    function showMessage(msg, platform) {
        const html = `
            <div class="search-message">
                <p>${escapeHtml(msg)}</p>
            </div>
        `;
        
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.html(html).addClass('show');
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.html(html).addClass('show');
        }
    }

    function showNoResults(query, platform) {
        const html = `
            <div class="no-results">
                <div class="no-results-icon" aria-hidden="true">🔍</div>
                <p>No users found for "${escapeHtml(query)}"</p>
                <p class="search-suggestion">Try different keywords or check spelling</p>
            </div>
        `;
        
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.html(html).addClass('show');
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.html(html).addClass('show');
        }
    }

    function showError(msg, platform) {
        const html = `
            <div class="search-error">
                <p>❌ ${escapeHtml(msg)}</p>
                <button class="retry-btn" onclick="UserSearch.retrySearch('${platform}')" aria-label="Retry search">
                    Try Again
                </button>
            </div>
        `;
        
        if (platform === 'desktop' || platform === 'both') {
            $desktopResults.html(html).addClass('show');
        }
        if (platform === 'mobile' || platform === 'both') {
            $mobileResults.html(html).addClass('show');
        }
    }

    function animateResults(platform) {
        const $results = platform === 'desktop' ? $desktopResults : 
                        platform === 'mobile' ? $mobileResults : 
                        $('.namesList');
        
        $results.find('.search-result-item').each(function(index) {
            const $item = $(this);
            setTimeout(() => {
                $item.addClass('animate-in');
            }, index * 50);
        });
    }

    function scrollToElement($element, platform) {
        if (!$element.length) return;
        
        const containerSelector = platform === 'desktop' ? '.search .search-results-list' : 
                                 '.mobile-search-results .search-results-list';
        const $container = $(containerSelector);
        
        if (!$container.length) return;
        
        const elementTop = $element.position().top;
        const containerHeight = $container.height();
        const elementHeight = $element.outerHeight();
        const scrollTop = $container.scrollTop();
        
        if (elementTop < 0) {
            // Element is above the visible area
            $container.scrollTop(scrollTop + elementTop - 10);
        } else if (elementTop + elementHeight > containerHeight) {
            // Element is below the visible area
            $container.scrollTop(scrollTop + elementTop + elementHeight - containerHeight + 10);
        }
    }

    function setupA11y() {
        // Desktop accessibility
        if ($desktopInput.length) {
            $desktopInput.attr({
                'role': 'combobox',
                'aria-expanded': 'false',
                'aria-autocomplete': 'list',
                'aria-haspopup': 'listbox',
                'aria-label': 'Search users'
            });
            $desktopResults.attr({ 
                'role': 'listbox', 
                'aria-hidden': 'true',
                'aria-label': 'Search results'
            });
        }
        
        // Mobile accessibility
        if ($mobileInput.length) {
            $mobileInput.attr({
                'role': 'combobox',
                'aria-expanded': 'false',
                'aria-autocomplete': 'list',
                'aria-haspopup': 'listbox',
                'aria-label': 'Search users'
            });
            $mobileResults.attr({ 
                'role': 'listbox', 
                'aria-hidden': 'true',
                'aria-label': 'Mobile search results'
            });
        }
    }

    function setupGlobalEvents() {
        // Handle clicks outside search areas
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search, .mobile-search-container').length) {
                hideResults('both');
            }
        });

        // Handle window resize
        $(window).on('resize', debounce(function() {
            const width = $(window).width();
            
            // Clear search results when switching between desktop/mobile
            if (width <= 992) {
                // Mobile mode - hide desktop results
                hideResults('desktop');
            } else {
                // Desktop mode - hide mobile results
                hideResults('mobile');
            }
        }, 250));

        // Handle page visibility change
        $(document).on('visibilitychange', function() {
            if (document.hidden) {
                hideResults('both');
            }
        });
    }

    function showToast(message, type = 'info', duration = 3000) {
        // Remove existing toasts
        $('.search-toast').remove();
        
        const toastId = 'toast-' + Date.now();
        const $toast = $(`
            <div id="${toastId}" class="search-toast toast-${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${getToastColor(type)};
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                font-weight: 600;
                font-size: 14px;
                z-index: 10000;
                box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                transform: translateX(400px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 300px;
                font-family: 'Kameron', serif;
                backdrop-filter: blur(20px);
                border: 2px solid rgba(255,255,255,0.2);
            ">
                <span>${escapeHtml(message)}</span>
                <button onclick="$('#${toastId}').remove()" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
            </div>
        `);
        
        $('body').append($toast);
        
        // Animate in
        setTimeout(() => $toast.css('transform', 'translateX(0)'), 10);
        
        // Auto remove
        setTimeout(() => {
            $toast.css('transform', 'translateX(400px)');
            setTimeout(() => $toast.remove(), 300);
        }, duration);
    }

    function getToastColor(type) {
        switch (type) {
            case 'success':
                return 'linear-gradient(135deg, #00C851, #007E33)';
            case 'error':
                return 'linear-gradient(135deg, #FF4444, #CC0000)';
            case 'warning':
                return 'linear-gradient(135deg, #FF8800, #CC6600)';
            default:
                return 'linear-gradient(135deg, #BE6AFF, #9AC1FF)';
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Public API
    window.UserSearch = {
        search: function(query, platform = 'both') {
            if (query && query.trim()) {
                search(query.trim(), platform);
            }
        },
        
        clear: function(platform = 'both') {
            if (platform === 'desktop' || platform === 'both') {
                $desktopInput.val('');
                hideResults('desktop');
            }
            if (platform === 'mobile' || platform === 'both') {
                $mobileInput.val('');
                hideResults('mobile');
            }
            currentQuery = '';
        },
        
        show: function(platform = 'both') {
            showResults(platform);
        },
        
        hide: function(platform = 'both') {
            hideResults(platform);
        },
        
        initializeMobile: function() {
            if ($mobileInput.length && !$mobileInput.data('search-initialized')) {
                bindMobileEvents();
                $mobileInput.data('search-initialized', true);
                console.log('Mobile search re-initialized');
            }
        },
        
        retrySearch: function(platform) {
            const $input = platform === 'desktop' ? $desktopInput : $mobileInput;
            const query = $input.val().trim();
            if (query) {
                search(query, platform);
            } else {
                hideResults(platform);
                $input.focus();
            }
        },
        
        isMobileView: function() {
            return $(window).width() <= 992;
        },
        
        getCurrentQuery: function() {
            return currentQuery;
        },
        
        isSearching: function() {
            return isSearching;
        },
        
        clearCache: function() {
            cache.clear();
            console.log('Search cache cleared');
        },
        
        getCache: function() {
            return {
                size: cache.size,
                entries: Array.from(cache.keys())
            };
        },
        
        showToast: showToast,
        
        // Development/debugging methods
        debug: {
            getState: function() {
                return {
                    currentQuery,
                    isSearching,
                    desktopSelectedIndex,
                    mobileSelectedIndex,
                    cacheSize: cache.size,
                    config: CONFIG
                };
            },
            
            simulateError: function(platform = 'both') {
                showError('Simulated error for testing', platform);
            },
            
            simulateLoading: function(platform = 'both') {
                showLoading(platform);
            }
        }
    };

    // Expose to global scope for debugging
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        window.SearchDebug = window.UserSearch.debug;
    }

    // Debug logging
    console.log('UserSearch initialized with config:', CONFIG);
    console.log('Desktop elements:', {
        input: $desktopInput.length,
        results: $desktopResults.length,
        wrapper: $desktopWrapper.length
    });
    console.log('Mobile elements:', {
        input: $mobileInput.length,
        results: $mobileResults.length,
        wrapper: $mobileWrapper.length
    });
});