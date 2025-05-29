/**
 * notifications.js - Enhanced notification system with real-time updates
 * Handles all notification functionality with improved UX and performance
 */

$(document).ready(function() {
    // Configuration
    const NOTIFICATION_CONFIG = {
        updateInterval: 30000, // Check for new notifications every 30 seconds
        maxNotifications: 50,  // Maximum notifications to keep in memory
        fadeDelay: 5000,       // Auto-hide toast after 5 seconds
        animationSpeed: 300,   // Animation speed for UI transitions
        soundEnabled: true,    // Enable notification sounds
        browserNotifications: true // Enable browser notifications
    };
    
    // State management
    let notificationState = {
        count: 0,
        lastId: 0,
        isOpen: false,
        currentPage: 1,
        hasMore: true,
        isLoading: false,
        notifications: [],
        updateTimer: null
    };
    
    // Cache DOM elements
    const $notificationBell = $('.notification .bell');
    const $notificationCounter = $('.notification .counter');
    const $notificationArrow = $('.notification .notificationArrow');
    const $notificationList = $('#notificationList');
    
    // Initialize notification system
    initializeNotifications();
    
    /**
     * Initialize the notification system
     */
    function initializeNotifications() {
        // Load initial notification count
        loadNotificationCount();
        
        // Set up event handlers
        setupEventHandlers();
        
        // Start periodic updates
        startPeriodicUpdates();
        
        // Request browser notification permission
        requestNotificationPermission();
        
        // Create toast container if it doesn't exist
        createToastContainer();
        
        console.log('Notification system initialized');
    }
    
    /**
     * Set up all event handlers
     */
    function setupEventHandlers() {
        // Toggle notification list on bell click
        $notificationBell.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleNotificationList();
        });
        
        // Toggle notification list on arrow click
        $notificationArrow.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleNotificationList();
        });
        
        // Close notification list when clicking outside
        $(document).on('click', function(e) {
            if (notificationState.isOpen && 
                !$(e.target).closest('.notification').length) {
                hideNotificationList();
            }
        });
        
        // Handle scroll for infinite loading
        $notificationList.on('scroll', handleNotificationScroll);
        
        // Handle notification item clicks
        $notificationList.on('click', '.notification-item', handleNotificationClick);
        
        // Handle mark as read clicks
        $notificationList.on('click', '.mark-read-btn', handleMarkAsRead);
        
        // Handle delete notification clicks
        $notificationList.on('click', '.delete-notification-btn', handleDeleteNotification);
        
        // Handle mark all as read
        $notificationList.on('click', '.mark-all-read-btn', markAllAsRead);
        
        // Handle toast clicks
        $(document).on('click', '.notification-toast', handleToastClick);
        
        // Handle toast close buttons
        $(document).on('click', '.toast-close-btn', closeToast);
    }
    
    /**
     * Toggle notification list visibility
     */
    function toggleNotificationList() {
        if (notificationState.isOpen) {
            hideNotificationList();
        } else {
            showNotificationList();
        }
    }
    
    /**
     * Show notification list
     */
    function showNotificationList() {
        notificationState.isOpen = true;
        notificationState.currentPage = 1;
        notificationState.hasMore = true;
        
        // Show list with animation
        $notificationList.css('display', 'flex').hide().slideDown(NOTIFICATION_CONFIG.animationSpeed);
        
        // Load notifications
        loadNotifications();
        
        // Stop periodic updates while list is open
        stopPeriodicUpdates();
        
        // Add visual indicator
        $notificationBell.addClass('active');
    }
    
    /**
     * Hide notification list
     */
    function hideNotificationList() {
        notificationState.isOpen = false;
        
        // Hide list with animation
        $notificationList.slideUp(NOTIFICATION_CONFIG.animationSpeed);
        
        // Remove visual indicator
        $notificationBell.removeClass('active');
        
        // Restart periodic updates
        startPeriodicUpdates();
    }
    
    /**
     * Load notification count
     */
    function loadNotificationCount() {
        $.ajax({
            url: 'notification_ajax.php',
            type: 'GET',
            data: { action: 'count' },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success) {
                    const oldCount = notificationState.count;
                    notificationState.count = response.count;
                    
                    updateNotificationCounter();
                    
                    // Show toast for new notifications
                    if (notificationState.count > oldCount && oldCount >= 0) {
                        const newCount = notificationState.count - oldCount;
                        if (newCount > 0) {
                            showNotificationToast(newCount);
                            playNotificationSound();
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading notification count:', error);
            }
        });
    }
    
    /**
     * Update notification counter display
     */
    function updateNotificationCounter() {
        if (notificationState.count > 0) {
            $notificationCounter.text(notificationState.count).show();
            
            // Add pulse animation for new notifications
            $notificationCounter.addClass('pulse');
            setTimeout(() => $notificationCounter.removeClass('pulse'), 1000);
        } else {
            $notificationCounter.text('0').hide();
        }
    }
    
    /**
     * Load notifications with pagination
     */
    function loadNotifications() {
        if (notificationState.isLoading) return;
        
        notificationState.isLoading = true;
        
        // Show loading indicator
        if (notificationState.currentPage === 1) {
            $notificationList.html('<div class="notifications-loading">Loading notifications...</div>');
        } else {
            $notificationList.append('<div class="loading-more">Loading more...</div>');
        }
        
        $.ajax({
            url: 'notification_ajax.php',
            type: 'GET',
            data: {
                action: 'fetch',
                page: notificationState.currentPage,
                limit: 10
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                notificationState.isLoading = false;
                
                // Remove loading indicators
                $('.notifications-loading, .loading-more').remove();
                
                if (response.success) {
                    if (notificationState.currentPage === 1) {
                        // Clear list and add header
                        $notificationList.empty();
                        
                        if (response.data.length > 0) {
                            addNotificationHeader();
                        }
                    }
                    
                    if (response.data.length === 0 && notificationState.currentPage === 1) {
                        // No notifications
                        showEmptyNotifications();
                    } else {
                        // Add notifications
                        response.data.forEach(function(notification) {
                            const $notificationElement = createNotificationElement(notification);
                            $notificationList.append($notificationElement);
                            
                            // Update last ID
                            if (notification.notification_id > notificationState.lastId) {
                                notificationState.lastId = notification.notification_id;
                            }
                        });
                        
                        // Update pagination
                        notificationState.hasMore = response.pagination.hasMore;
                        notificationState.currentPage++;
                        
                        // Add load more indicator
                        if (notificationState.hasMore) {
                            $notificationList.append('<div class="load-more-indicator">Scroll for more</div>');
                        }
                    }
                } else {
                    showNotificationError(response.message);
                }
            },
            error: function(xhr, status, error) {
                notificationState.isLoading = false;
                $('.notifications-loading, .loading-more').remove();
                showNotificationError('Failed to load notifications');
                console.error('Error loading notifications:', error);
            }
        });
    }
    
    /**
     * Create notification element
     */
    function createNotificationElement(notification) {
        const unreadClass = notification.is_read == 0 ? 'unread' : '';
        const timeAgo = notification.time_ago || formatTimeAgo(notification.created_at);
        
        return $(`
            <div class="notification-item ${unreadClass}" data-id="${notification.notification_id}" data-url="${notification.action_url}">
                <div class="notification-content">
                    <div class="notification-icon">${notification.icon}</div>
                    <div class="notification-body">
                        <p class="notification-message">${notification.message}</p>
                        <span class="notification-time">${timeAgo}</span>
                    </div>
                </div>
                <div class="notification-actions">
                    ${notification.is_read == 0 ? '<button class="mark-read-btn" title="Mark as read">✓</button>' : ''}
                    <button class="delete-notification-btn" title="Delete">×</button>
                </div>
            </div>
        `);
    }
    
    /**
     * Add notification header with controls
     */
    function addNotificationHeader() {
        const hasUnread = notificationState.count > 0;
        const headerHtml = `
            <div class="notification-header">
                <span class="notification-title">Notifications</span>
                ${hasUnread ? '<button class="mark-all-read-btn">Mark all as read</button>' : ''}
            </div>
        `;
        $notificationList.append(headerHtml);
    }
    
    /**
     * Show empty notifications state
     */
    function showEmptyNotifications() {
        $notificationList.html(`
            <div class="empty-notifications">
                <div class="empty-icon">🔔</div>
                <p>No notifications yet</p>
                <small>You'll see notifications here when someone interacts with your content</small>
            </div>
        `);
    }
    
    /**
     * Show notification error
     */
    function showNotificationError(message) {
        $notificationList.html(`
            <div class="notification-error">
                <div class="error-icon">⚠️</div>
                <p>Error loading notifications</p>
                <small>${message}</small>
                <button class="retry-notifications-btn">Retry</button>
            </div>
        `);
        
        $('.retry-notifications-btn').on('click', function() {
            notificationState.currentPage = 1;
            loadNotifications();
        });
    }
    
    /**
     * Handle notification scroll for infinite loading
     */
    function handleNotificationScroll() {
        if (notificationState.hasMore && !notificationState.isLoading) {
            const scrollTop = $notificationList.scrollTop();
            const scrollHeight = $notificationList[0].scrollHeight;
            const clientHeight = $notificationList.height();
            
            if (scrollTop + clientHeight >= scrollHeight - 50) {
                loadNotifications();
            }
        }
    }
    
    /**
     * Handle notification item click
     */
    function handleNotificationClick(e) {
        if ($(e.target).hasClass('mark-read-btn') || $(e.target).hasClass('delete-notification-btn')) {
            return; // Let specific button handlers deal with these
        }
        
        const $notification = $(this);
        const notificationId = $notification.data('id');
        const url = $notification.data('url');
        
        // Mark as read if unread
        if ($notification.hasClass('unread')) {
            markNotificationAsRead(notificationId, $notification, false);
        }
        
        // Navigate to URL
        if (url && url !== '#') {
            window.location.href = url;
        }
    }
    
    /**
     * Handle mark as read button click
     */
    function handleMarkAsRead(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $notification = $(this).closest('.notification-item');
        const notificationId = $notification.data('id');
        
        markNotificationAsRead(notificationId, $notification);
    }
    
    /**
     * Handle delete notification button click
     */
    function handleDeleteNotification(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $notification = $(this).closest('.notification-item');
        const notificationId = $notification.data('id');
        
        deleteNotification(notificationId, $notification);
    }
    
    /**
     * Mark notification as read
     */
    function markNotificationAsRead(notificationId, $notification, updateCounter = true) {
        $.ajax({
            url: 'notification_ajax.php',
            type: 'POST',
            data: {
                action: 'mark_read',
                notification_id: notificationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $notification.removeClass('unread').addClass('read');
                    $notification.find('.mark-read-btn').remove();
                    
                    if (updateCounter && notificationState.count > 0) {
                        notificationState.count--;
                        updateNotificationCounter();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking notification as read:', error);
            }
        });
    }
    
    /**
     * Delete notification
     */
    function deleteNotification(notificationId, $notification) {
        // Add animation before removal
        $notification.addClass('deleting');
        
        $.ajax({
            url: 'notification_ajax.php',
            type: 'POST',
            data: {
                action: 'delete',
                notification_id: notificationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $notification.slideUp(NOTIFICATION_CONFIG.animationSpeed, function() {
                        $(this).remove();
                        
                        // Update counter if it was unread
                        if ($notification.hasClass('unread') && notificationState.count > 0) {
                            notificationState.count--;
                            updateNotificationCounter();
                        }
                        
                        // Check if list is empty
                        if ($notificationList.find('.notification-item').length === 0) {
                            showEmptyNotifications();
                        }
                    });
                } else {
                    $notification.removeClass('deleting');
                    showToast('Error', 'Failed to delete notification', 'error');
                }
            },
            error: function(xhr, status, error) {
                $notification.removeClass('deleting');
                console.error('Error deleting notification:', error);
                showToast('Error', 'Failed to delete notification', 'error');
            }
        });
    }
    
    /**
     * Mark all notifications as read
     */
    function markAllAsRead() {
        const $button = $('.mark-all-read-btn');
        $button.text('Marking...').prop('disabled', true);
        
        $.ajax({
            url: 'notification_ajax.php',
            type: 'POST',
            data: { action: 'mark_all_read' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('.notification-item.unread').removeClass('unread').addClass('read');
                    $('.mark-read-btn').remove();
                    $button.remove();
                    
                    notificationState.count = 0;
                    updateNotificationCounter();
                    
                    showToast('Success', 'All notifications marked as read', 'success');
                } else {
                    $button.text('Mark all as read').prop('disabled', false);
                    showToast('Error', 'Failed to mark all as read', 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.text('Mark all as read').prop('disabled', false);
                console.error('Error marking all as read:', error);
                showToast('Error', 'Failed to mark all as read', 'error');
            }
        });
    }
    
    /**
     * Show notification toast
     */
    function showNotificationToast(count) {
        const message = count === 1 ? 
            'You have 1 new notification' : 
            `You have ${count} new notifications`;
        
        showToast('New Notification', message, 'notification');
        
        // Show browser notification if permitted
        if (NOTIFICATION_CONFIG.browserNotifications && Notification.permission === 'granted') {
            new Notification('Pollify', {
                body: message,
                icon: 'Images/main_icon.png',
                badge: 'Images/main_icon.png'
            });
        }
    }
    
    /**
     * Create toast container
     */
    function createToastContainer() {
        if ($('#toast-container').length === 0) {
            $('body').append('<div id="toast-container"></div>');
        }
    }
    
    /**
     * Show toast notification
     */
    function showToast(title, message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️',
            'notification': '🔔'
        };
        
        const icon = iconMap[type] || iconMap['info'];
        
        const $toast = $(`
            <div class="notification-toast toast-${type}" id="${toastId}">
                <div class="toast-content">
                    <div class="toast-icon">${icon}</div>
                    <div class="toast-body">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close-btn">×</button>
                </div>
            </div>
        `);
        
        $('#toast-container').append($toast);
        
        // Show with animation
        setTimeout(() => $toast.addClass('show'), 10);
        
        // Auto-hide
        setTimeout(() => {
            $toast.removeClass('show');
            setTimeout(() => $toast.remove(), 300);
        }, NOTIFICATION_CONFIG.fadeDelay);
    }
    
    /**
     * Handle toast click
     */
    function handleToastClick(e) {
        if (!$(e.target).hasClass('toast-close-btn')) {
            $(this).removeClass('show');
            setTimeout(() => $(this).remove(), 300);
            
            if (!notificationState.isOpen) {
                showNotificationList();
            }
        }
    }
    
    /**
     * Close toast
     */
    function closeToast(e) {
        e.stopPropagation();
        const $toast = $(this).closest('.notification-toast');
        $toast.removeClass('show');
        setTimeout(() => $toast.remove(), 300);
    }
    
    /**
     * Start periodic updates
     */
    function startPeriodicUpdates() {
        stopPeriodicUpdates();
        notificationState.updateTimer = setInterval(function() {
            if (!notificationState.isOpen) {
                loadNotificationCount();
            }
        }, NOTIFICATION_CONFIG.updateInterval);
    }
    
    /**
     * Stop periodic updates
     */
    function stopPeriodicUpdates() {
        if (notificationState.updateTimer) {
            clearInterval(notificationState.updateTimer);
            notificationState.updateTimer = null;
        }
    }
    
    /**
     * Request browser notification permission
     */
    function requestNotificationPermission() {
        if (NOTIFICATION_CONFIG.browserNotifications && 'Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    }
    
    /**
     * Play notification sound
     */
    function playNotificationSound() {
        if (NOTIFICATION_CONFIG.soundEnabled) {
            // Create audio element for notification sound
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmEaAzWAufTUiUISKWzO8OSZRQkPMrHt46JdFxoybYKGxcSdZUCQvJmgY1wVdUqXkq+ReTozWm8yXnQ4b1oqZXNFhJSdgYe+rI4Af7VzCgIDAA==');
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Could not play notification sound:', e));
        }
    }
    
    /**
     * Format time ago helper
     */
    function formatTimeAgo(timestamp) {
        const time = new Date(timestamp).getTime();
        const now = Date.now();
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) return diff + " seconds ago";
        if (diff < 3600) return Math.floor(diff / 60) + " minutes ago";
        if (diff < 86400) return Math.floor(diff / 3600) + " hours ago";
        if (diff < 604800) return Math.floor(diff / 86400) + " days ago";
        if (diff < 2592000) return Math.floor(diff / 604800) + " weeks ago";
        if (diff < 31536000) return Math.floor(diff / 2592000) + " months ago";
        return Math.floor(diff / 31536000) + " years ago";
    }
    
    // Public API
    window.NotificationSystem = {
        refresh: loadNotificationCount,
        show: showNotificationList,
        hide: hideNotificationList,
        markAllRead: markAllAsRead,
        getCount: () => notificationState.count,
        isOpen: () => notificationState.isOpen
    };
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        stopPeriodicUpdates();
    });
});