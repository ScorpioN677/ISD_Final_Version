/**
 * poll_interactions.js - Handle poll interactions (vote, follow, comment) with AJAX
 * FIXED VERSION with proper vote indicators
 */

$(document).ready(function() {
    // ===== Voting =====
    
    // Handle answer option click (voting)
    $(document).on('click', '.answer-option', function() {
        const $option = $(this);
        const $pollCard = $option.closest('.poll-card');
        
        // Skip if already submitting vote, poll is locked, or option is already selected
        if ($pollCard.hasClass('submitting-vote') || 
            $pollCard.hasClass('poll-locked') || 
            $option.hasClass('selected')) {
            return;
        }
        
        const pollId = $pollCard.data('poll-id');
        const answerId = $option.data('answer-id');
        
        // Add loading state
        $pollCard.addClass('submitting-vote');
        $option.addClass('loading-vote');
        
        // Submit vote via AJAX
        $.ajax({
            url: 'submit_vote.php',
            type: 'POST',
            data: {
                poll_id: pollId,
                answer_id: answerId
            },
            dataType: 'json',
            success: function(response) {
                // Remove loading state
                $pollCard.removeClass('submitting-vote');
                $option.removeClass('loading-vote');
                
                if (response.success) {
                    // FIXED: Update UI for this specific poll only
                    updatePollVoteDisplay($pollCard, response.data, answerId);
                    
                    // Add visual feedback for successful vote
                    $pollCard.addClass('vote-success');
                    setTimeout(function() {
                        $pollCard.removeClass('vote-success');
                    }, 1500);
                } else {
                    // Show error message
                    showToast('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                // Remove loading state
                $pollCard.removeClass('submitting-vote');
                $option.removeClass('loading-vote');
                
                // Show error message
                showToast('Error', 'Failed to submit vote. Please try again.', 'error');
                console.error('Error submitting vote:', error);
            }
        });
    });
    
    // FIXED: Function to update vote display for specific poll
    function updatePollVoteDisplay($pollCard, responseData, selectedAnswerId) {
        // Remove all selected states from this poll
        $pollCard.find('.answer-option').removeClass('selected');
        $pollCard.find('.vote-indicator').remove();

        // Add the user-voted class to the poll card
        $pollCard.addClass('user-voted');

        // Update percentages and progress bars for all answers in this poll
        if (responseData && responseData.answers) {
            responseData.answers.forEach(function (answer) {
                const $answerOption = $pollCard.find(
                    `.answer-option[data-answer-id="${answer.answer_id}"]`
                );
                
                if ($answerOption.length) {
                    // Update percentage text
                    $answerOption
                        .find('.answer-percentage')
                        .text(`${answer.percentage}%`);
                    
                    // Update progress bar width
                    $answerOption
                        .find('.answer-progress')
                        .css('width', `${answer.percentage}%`);

                    // Mark the selected answer
                    if (parseInt(answer.answer_id) === parseInt(selectedAnswerId)) {
                        $answerOption.addClass('selected');
                        $answerOption.append('<span class="vote-indicator">✓ Your vote</span>');
                    }
                }
            });
        }
    }
    
    // ===== Follow/Unfollow =====
    
    // Handle follow button clicks
    $(document).on('click', '.follow-btn', function() {
        const $button = $(this);
        const $pollCard = $button.closest('.poll-card');
        const pollId = $pollCard.data('poll-id');
        const creatorId = $button.data('creator-id');
        
        // Determine action based on current state
        const isFollowing = $button.hasClass('following');
        const action = isFollowing ? 'unfollow' : 'follow';
        
        // Optimistic UI update
        if (action === 'follow') {
            $button.addClass('following').text('Following');
        } else {
            $button.removeClass('following').text('Follow');
        }
        
        // Send AJAX request
        $.ajax({
            url: 'toggle_follow.php',
            type: 'POST',
            data: {
                poll_id: pollId,
                action: action
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update all instances of this user's follow button
                    $(`.follow-btn[data-creator-id="${creatorId}"]`).each(function() {
                        if (action === 'follow') {
                            $(this).addClass('following').text('Following');
                        } else {
                            $(this).removeClass('following').text('Follow');
                        }
                    });
                } else {
                    // Revert UI on error
                    if (action === 'follow') {
                        $button.removeClass('following').text('Follow');
                    } else {
                        $button.addClass('following').text('Following');
                    }
                    
                    // Show error message
                    showToast('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                // Revert UI on error
                if (action === 'follow') {
                    $button.removeClass('following').text('Follow');
                } else {
                    $button.addClass('following').text('Following');
                }
                
                // Show error message
                showToast('Error', 'Failed to update follow status.', 'error');
                console.error('Error toggling follow:', error);
            }
        });
    });
    
    // ===== Star/Favorite =====
    
    // Handle star button clicks
    $(document).on('click', '.star-btn', function() {
        const $button = $(this);
        const pollId = $button.data('poll-id');

        // Toggle active state for immediate feedback
        $button.toggleClass('active');

        // Determine action based on current state
        const action = $button.hasClass('active') ? 'add' : 'remove';

        // Send AJAX request to update favorite status
        $.ajax({
            url: 'toggle_favorite.php',
            type: 'POST',
            data: {
                poll_id: pollId,
                action: action,
            },
            dataType: 'json',
            success: function (response) {
                if (!response.success) {
                    // Revert button state if the operation failed
                    $button.toggleClass('active');
                    showToast('Error', response.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                // Revert button state on error
                $button.toggleClass('active');
                showToast('Error', 'Error connecting to server', 'error');
            },
        });
    });
    
    // ===== Comments =====
    
    // Show/hide comment section
    $(document).on('click', '.comment-count', function() {
        const $commentCount = $(this);
        const $pollCard = $commentCount.closest('.poll-card');
        const $commentSection = $pollCard.find('.comment-section');
        const pollId = $pollCard.data('poll-id');
        
        // Toggle comment section
        if ($commentSection.is(':visible')) {
            $commentSection.slideUp(200);
        } else {
            // Load comments if not already loaded
            if (!$commentSection.data('loaded')) {
                loadComments(pollId, $commentSection);
                $commentSection.data('loaded', true);
            }
            
            $commentSection.slideDown(200);
        }
    });
    
    // Submit comment
    $(document).on('click', '.post-comment-btn', function() {
        const $button = $(this);
        const $form = $button.closest('.comment-form');
        const $textarea = $form.find('textarea');
        const $pollCard = $button.closest('.poll-card');
        const $commentSection = $pollCard.find('.comment-section');
        const $commentsContainer = $commentSection.find('.comments-container');
        
        const pollId = $pollCard.data('poll-id');
        const commentText = $textarea.val().trim();
        const parentCommentId = $form.data('parent-comment-id') || null;
        
        // Validate comment text
        if (!commentText) {
            $textarea.addClass('error').focus();
            return;
        }
        
        // Remove error class
        $textarea.removeClass('error');
        
        // Disable form while submitting
        $textarea.prop('disabled', true);
        $button.prop('disabled', true).text('Posting...');
        
        // Submit comment via AJAX
        $.ajax({
            url: 'add_comment.php',
            type: 'POST',
            data: {
                poll_id: pollId,
                text: commentText,
                parent_comment_id: parentCommentId
            },
            dataType: 'json',
            success: function(response) {
                // Re-enable form
                $textarea.prop('disabled', false).val('');
                $button.prop('disabled', false).text('Post');
                
                if (response.success) {
                    if (parentCommentId) {
                        // This is a reply - add to parent comment
                        const $parentComment = $(`.comment[data-comment-id="${parentCommentId}"]`);
                        
                        // Get or create replies container
                        let $repliesContainer = $parentComment.find('.comment-replies');
                        if ($repliesContainer.length === 0) {
                            $parentComment.append('<div class="comment-replies"></div>');
                            $repliesContainer = $parentComment.find('.comment-replies');
                        }
                        
                        // Add the reply
                        const $reply = createReplyElement(response.data);
                        $repliesContainer.prepend($reply);
                        
                        // Update reply count
                        let replyCount = parseInt($parentComment.find('.reply-count span').text()) || 0;
                        $parentComment.find('.reply-count span').text(replyCount + 1);
                        
                        // Show replies if they were hidden
                        $repliesContainer.show();
                        $parentComment.find('.toggle-replies')
                            .addClass('showing-replies')
                            .text('Hide replies');
                        
                        // Remove reply form
                        $form.remove();
                    } else {
                        // This is a new top-level comment
                        const $comment = createCommentElement(response.data);
                        
                        // Remove "no comments" message if it exists
                        $commentsContainer.find('.no-comments').remove();
                        
                        // Add comment at the top
                        $commentsContainer.prepend($comment);
                    }
                    
                    // Update comment count in footer
                    const $countSpan = $pollCard.find('.comment-count span:last-child');
                    const currentCount = parseInt($countSpan.text()) || 0;
                    $countSpan.text(currentCount + 1);
                } else {
                    // Show error message
                    showToast('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                // Re-enable form
                $textarea.prop('disabled', false);
                $button.prop('disabled', false).text('Post');
                
                // Show error message
                showToast('Error', 'Failed to post comment. Please try again.', 'error');
                console.error('Error posting comment:', error);
            }
        });
    });
    
    // Reply to comment
    $(document).on('click', '.reply-btn', function() {
        const $replyButton = $(this);
        const $comment = $replyButton.closest('.comment');
        const commentId = $comment.data('comment-id');
        const commentAuthor = $comment.find('.comment-username').text();
        
        // Remove any existing reply forms
        $('.reply-form').remove();
        
        // Create reply form
        const $replyForm = $(`
            <div class="comment-form reply-form" data-parent-comment-id="${commentId}">
                <textarea placeholder="Reply to ${commentAuthor}..."></textarea>
                <div class="reply-form-buttons">
                    <button class="cancel-reply-btn">Cancel</button>
                    <button class="post-comment-btn">Reply</button>
                </div>
            </div>
        `);
        
        // Add form after comment
        $comment.append($replyForm);
        
        // Focus textarea
        $replyForm.find('textarea').focus();
        
        // Handle cancel button
        $replyForm.find('.cancel-reply-btn').on('click', function() {
            $replyForm.remove();
        });
    });
    
    // Toggle comment replies
    $(document).on('click', '.toggle-replies', function() {
        const $button = $(this);
        const $comment = $button.closest('.comment');
        const $repliesContainer = $comment.find('.comment-replies');
        
        // Toggle replies visibility
        $repliesContainer.slideToggle(200);
        
        // Toggle button text
        if ($button.hasClass('showing-replies')) {
            $button.removeClass('showing-replies').text('Show replies');
        } else {
            $button.addClass('showing-replies').text('Hide replies');
        }
    });
    
    // ===== Helper Functions =====
    
    /**
     * Load comments for a poll via AJAX
     * 
     * @param {number} pollId - Poll ID
     * @param {jQuery} $commentSection - Comment section container
     * @param {number} page - Page number (default: 1)
     */
    function loadComments(pollId, $commentSection, page = 1) {
        const $commentsContainer = $commentSection.find('.comments-container');
        
        // Show loading indicator
        if (page === 1) {
            $commentsContainer.html('<div class="loading">Loading comments...</div>');
        } else {
            $commentsContainer.append('<div class="loading loading-more">Loading more comments...</div>');
        }
        
        // Fetch comments via AJAX
        $.ajax({
            url: 'fetch_comments.php',
            type: 'GET',
            data: {
                poll_id: pollId,
                page: page,
                limit: 10
            },
            dataType: 'json',
            success: function(response) {
                // Remove loading indicators
                $commentsContainer.find('.loading').remove();
                
                if (response.success) {
                    // If first page, clear container
                    if (page === 1) {
                        $commentsContainer.empty();
                    }
                    
                    // Check for empty result
                    if (response.data.length === 0 && page === 1) {
                        $commentsContainer.html('<div class="no-comments">No comments yet. Be the first to comment!</div>');
                    } else {
                        // Append comments
                        response.data.forEach(function(comment) {
                            const $comment = createCommentElement(comment);
                            $commentsContainer.append($comment);
                        });
                        
                        // Add "load more" button if needed
                        if (response.pagination.hasMore) {
                            const $loadMoreBtn = $(`
                                <button class="load-more-comments" data-page="${page + 1}">
                                    Load more comments
                                </button>
                            `);
                            
                            $loadMoreBtn.on('click', function() {
                                $(this).remove();
                                loadComments(pollId, $commentSection, page + 1);
                            });
                            
                            $commentsContainer.append($loadMoreBtn);
                        }
                    }
                    
                    // Check for highlighted comment from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const highlightCommentId = urlParams.get('highlight_comment');
                    
                    if (highlightCommentId) {
                        highlightComment(highlightCommentId);
                    }
                } else {
                    // Show error
                    $commentsContainer.html(`
                        <div class="comments-error">
                            <p>Error loading comments: ${response.message}</p>
                            <button class="retry-button">Retry</button>
                        </div>
                    `);
                    
                    $('.retry-button', $commentsContainer).on('click', function() {
                        loadComments(pollId, $commentSection, page);
                    });
                }
            },
            error: function(xhr, status, error) {
                // Remove loading indicators
                $commentsContainer.find('.loading').remove();
                
                // Show error
                $commentsContainer.html(`
                    <div class="comments-error">
                        <p>Error loading comments. Please try again.</p>
                        <button class="retry-button">Retry</button>
                    </div>
                `);
                
                $('.retry-button', $commentsContainer).on('click', function() {
                    loadComments(pollId, $commentSection, page);
                });
                
                console.error('Error loading comments:', error);
            }
        });
    }
    
    /**
     * Create comment element
     * 
     * @param {Object} comment - Comment data
     * @returns {jQuery} - jQuery element
     */
    function createCommentElement(comment) {
        // Check if has replies
        const hasReplies = comment.replies && comment.replies.length > 0;
        const replyCount = comment.reply_count || (hasReplies ? comment.replies.length : 0);
        
        // Create base comment element
        const $comment = $(`
            <div class="comment" data-comment-id="${comment.comment_id}">
                <div class="comment-user">
                    <img src="${comment.user.profile_pic}" alt="Profile" class="comment-profile-image">
                    <span class="comment-username">${comment.user.name}</span>
                </div>
                <div class="comment-content">
                    <p>${comment.text}</p>
                </div>
                <div class="comment-actions">
                    <button class="reply-btn">Reply</button>
                    ${replyCount > 0 ? 
                        `<button class="toggle-replies">Show replies</button>
                         <span class="reply-count">(<span>${replyCount}</span>)</span>` : ''}
                </div>
            </div>
        `);
        
        // If comment has replies, add them
        if (hasReplies) {
            const $repliesContainer = $('<div class="comment-replies" style="display: none;"></div>');
            
            comment.replies.forEach(function(reply) {
                const $reply = createReplyElement(reply);
                $repliesContainer.append($reply);
            });
            
            $comment.append($repliesContainer);
        }
        
        return $comment;
    }
    
    /**
     * Create reply element
     * 
     * @param {Object} reply - Reply data
     * @returns {jQuery} - jQuery element
     */
    function createReplyElement(reply) {
        return $(`
            <div class="comment reply" data-comment-id="${reply.comment_id}" data-parent-id="${reply.parent_comment_id}">
                <div class="comment-user">
                    <img src="${reply.user.profile_pic}" alt="Profile" class="comment-profile-image">
                    <span class="comment-username">${reply.user.name}</span>
                </div>
                <div class="comment-content">
                    <p>${reply.text}</p>
                </div>
                <div class="comment-actions">
                    <button class="reply-btn">Reply</button>
                </div>
            </div>
        `);
    }
    
    /**
     * Highlight a comment (when coming from a notification)
     * 
     * @param {number} commentId - Comment ID to highlight
     */
    function highlightComment(commentId) {
        // Find the comment
        const $comment = $(`.comment[data-comment-id="${commentId}"]`);
        
        if ($comment.length) {
            // Open comment section if needed
            const $pollCard = $comment.closest('.poll-card');
            const $commentSection = $pollCard.find('.comment-section');
            
            if (!$commentSection.is(':visible')) {
                $pollCard.find('.comment-count').click();
            }
            
            // If comment is in a replies container, show it
            const $repliesContainer = $comment.closest('.comment-replies');
            if ($repliesContainer.length && !$repliesContainer.is(':visible')) {
                $repliesContainer.closest('.comment').find('.toggle-replies').click();
            }
            
            // Wait for animations to complete
            setTimeout(function() {
                // Scroll to comment
                $('html, body').animate({
                    scrollTop: $comment.offset().top - 100
                }, 500);
                
                // Highlight effect
                $comment.addClass('highlight-comment');
                setTimeout(function() {
                    $comment.removeClass('highlight-comment');
                }, 3000);
            }, 500);
        }
    }
    
    /**
     * Show toast notification
     * 
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, info)
     */
    function showToast(title, message, type = 'info') {
        // Create toast container if it doesn't exist
        if ($('#toast-container').length === 0) {
            $('body').append('<div id="toast-container"></div>');
        }
        
        // Create toast element
        const $toast = $(`
            <div class="toast toast-${type}">
                <div class="toast-header">
                    <strong>${title}</strong>
                    <button class="toast-close">&times;</button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        // Add to container
        $('#toast-container').append($toast);
        
        // Show toast with animation
        setTimeout(function() {
            $toast.addClass('show');
        }, 10);
        
        // Hide toast after 5 seconds
        setTimeout(function() {
            $toast.removeClass('show');
            
            // Remove after animation completes
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 5000);
        
        // Add close button handler
        $toast.find('.toast-close').on('click', function() {
            $toast.removeClass('show');
            
            // Remove after animation completes
            setTimeout(function() {
                $toast.remove();
            }, 300);
        });
    }
});