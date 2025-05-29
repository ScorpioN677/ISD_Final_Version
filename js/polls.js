// polls.js - Enhanced version with better filter support and complete reply functionality

$(document).ready(function () {
  // Variables for pagination
  let currentPage = 1;
  let isLoading = false;
  let hasMorePolls = true;
  const pollsContainer = $("#pollsContainer");

  // Load initial polls when page loads
  loadPolls();

  // Detect when user scrolls near the bottom of the page
  $(window).scroll(function () {
    if (
      $(window).scrollTop() + $(window).height() >
      $(document).height() - 200
    ) {
      if (!isLoading && hasMorePolls) {
        loadPolls();
      }
    }
  });

  // Handle filter form submission - ENHANCED
  $("#filtering").submit(function (e) {
    e.preventDefault();
    console.log('Filter form submitted');
    
    currentPage = 1;
    hasMorePolls = true;
    pollsContainer.empty();
    
    // Load filtered polls
    loadPolls();
    
    // Hide the filter dropdown
    $(".filterPolls").hide();
    
    // Update filter display
    if (typeof window.updateCheckedCategories === 'function') {
      window.updateCheckedCategories();
    }
  });

  // Function to load polls via AJAX - ENHANCED
  function loadPolls() {
    if (isLoading) return;

    isLoading = true;

    // Show loading indicator
    if (currentPage === 1) {
      pollsContainer.html('<div class="loading">Loading polls...</div>');
    } else {
      pollsContainer.append('<div class="loading">Loading more polls...</div>');
    }

    // Get selected categories for filtering
    const selectedCategories = [];
    $('input[name="categories[]"]:checked').each(function () {
      selectedCategories.push($(this).val());
    });

    // Prepare request data
    const requestData = {
      page: currentPage,
      limit: 5,
    };

    if (selectedCategories.length > 0) {
      requestData.categories = selectedCategories;
      console.log('Loading polls with categories:', selectedCategories);
    } else {
      console.log('Loading all polls');
    }

    // Make AJAX request
    $.ajax({
      url: "fetch_polls.php",
      type: "GET",
      data: requestData,
      dataType: "json",
      timeout: 15000,
      success: function (response) {
        $(".loading").remove();

        if (response.success) {
          hasMorePolls = response.pagination.hasMore;
          currentPage++;
          renderPolls(response.data);
          
          // Log results
          console.log(`Loaded ${response.data.length} polls, hasMore: ${hasMorePolls}`);
        } else {
          if (currentPage === 1) {
            pollsContainer.html('<div class="error">' + response.message + "</div>");
          } else {
            pollsContainer.append('<div class="error">' + response.message + "</div>");
          }
        }

        isLoading = false;
      },
      error: function (xhr, status, error) {
        $(".loading").remove();
        const errorMsg = status === 'timeout' ? 'Request timed out' : 'Error loading polls';
        if (currentPage === 1) {
          pollsContainer.html('<div class="error">' + errorMsg + "</div>");
        } else {
          pollsContainer.append('<div class="error">' + errorMsg + "</div>");
        }
        isLoading = false;
        console.error('Load polls error:', {status, error});
      },
    });
  }

  // Function to render polls on the page
  function renderPolls(polls) {
    if (polls.length === 0 && currentPage === 2) {
      pollsContainer.html('<div class="no-polls">No polls found for the selected categories</div>');
      return;
    }

    polls.forEach(function (poll) {
      const pollHTML = createPollElement(poll);
      pollsContainer.append(pollHTML);
    });

    // Initialize event handlers for new polls
    initializeEvents();
  }

  // Function to create a poll element
  function createPollElement(poll) {
    // Create answer options HTML
    let answersHTML = "";

    poll.answers.forEach(function (answer) {
      const isUserChoice = poll.user_voted && 
                          poll.user_vote_id && 
                          parseInt(poll.user_vote_id) === parseInt(answer.id);
      
      const selectedClass = isUserChoice ? "selected" : "";
      const progressWidth = `${answer.percentage}%`;

      answersHTML += `
        <div class="answer-option ${selectedClass}" 
             data-answer-id="${answer.id}" 
             data-poll-id="${poll.poll_id}">
            <div class="answer-circle"></div>
            <div class="answer-text">${answer.text}</div>
            <div class="answer-progress" style="width: ${progressWidth}"></div>
            <div class="answer-percentage">${answer.percentage}%</div>
            ${isUserChoice ? '<span class="vote-indicator">✓ Your vote</span>' : ''}
        </div>
      `;
    });

    // Handle anonymous posts and button visibility
    const isAnonymous = poll.isAnonymous;
    const showFollowButton = poll.created_by.show_follow_button;
    const showStarButton = poll.show_favorite_button;
    
    // Determine follow button state
    const isFollowing = poll.is_following || false;
    const followBtnClass = isFollowing ? "following" : "";
    const followBtnText = isFollowing ? "Following" : "Follow";
    
    // Create follow button HTML
    const followButtonHTML = showFollowButton ? 
        `<button class="follow-btn ${followBtnClass}" data-creator-id="${poll.created_by.user_id}">${followBtnText}</button>` : 
        '';

    // Determine favorite button state
    const isFavorite = poll.is_favorite || false;
    const starBtnClass = isFavorite ? "active" : "";
    
    // Create star button HTML
    const starButtonHTML = showStarButton ? 
        `<button class="star-btn ${starBtnClass}" data-poll-id="${poll.poll_id}">⭐</button>` : 
        '';

    // Determine if user has voted
    const hasVoted = poll.user_voted || false;
    const votedClass = hasVoted ? "user-voted" : "";

    // Get comment count
    const commentCount = poll.comment_count || 0;

    // Handle clickable username and profile image for anonymous posts
    const usernameClass = isAnonymous ? "username-anonymous" : "username";
    const userClickable = isAnonymous ? "" : `data-user-id="${poll.created_by.user_id}"`;
    const profileImageClass = isAnonymous ? "profile-image-anonymous" : "profile-image";

    // Create the poll HTML structure
    return `
      <div class="poll-card ${votedClass}" 
           data-poll-id="${poll.poll_id}" 
           data-creator-id="${poll.created_by.user_id}"
           data-is-anonymous="${isAnonymous}">
          <div class="poll-header">
              <div class="user-profile">
                  <img src="${poll.created_by.profile_pic}" alt="Profile" class="${profileImageClass}" ${userClickable}>
                  <span class="${usernameClass}" ${userClickable}>${poll.created_by.name}</span>
                  <span class="poll-date">${poll.date}</span>
              </div>
              <div class="poll-actions">
                  ${followButtonHTML}
                  ${starButtonHTML}
              </div>
          </div>
          
          <div class="poll-question">
              <p>${poll.question}</p>
              <span class="category-tag">${poll.category.name}</span>
          </div>
          
          <div class="poll-answers">
              ${answersHTML}
          </div>
          
          <div class="poll-footer">
              <div class="comment-count" data-poll-id="${poll.poll_id}">
                  <span class="comment-icon">💬</span>
                  <span>${commentCount}</span>
              </div>
          </div>
          
          <div class="comment-section" style="display: none;">
              <div class="comments-container"></div>
              <div class="comment-form">
                  <textarea placeholder="Add a comment..."></textarea>
                  <button class="post-comment-btn">Post</button>
              </div>
          </div>
      </div>
    `;
  }

  // Initialize event handlers
  function initializeEvents() {
    // Remove any existing handlers first to prevent duplicates
    $(document).off('click.polls');
    
    // FOLLOW BUTTON HANDLER - FIXED VERSION
    $(document).on('click.polls', '.follow-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $button = $(this);
      const $pollCard = $button.closest(".poll-card");
      
      console.log('Follow button clicked!');
      
      // Prevent multiple clicks
      if ($button.hasClass('processing') || $button.prop('disabled')) {
        console.log('Button is processing, ignoring click');
        return false;
      }
      
      // Get required data
      const pollId = parseInt($pollCard.data("poll-id"));
      const creatorId = parseInt($button.data("creator-id"));
      
      console.log('Poll ID:', pollId, 'Creator ID:', creatorId);
      
      // Validate data
      if (!pollId || !creatorId || pollId <= 0 || creatorId <= 0) {
        console.error('Invalid data - Poll ID:', pollId, 'Creator ID:', creatorId);
        return false;
      }
      
      // Check for anonymous posts
      const isAnonymous = $pollCard.data('is-anonymous');
      if (isAnonymous === true || isAnonymous === 'true' || isAnonymous === '1') {
        console.log('Cannot follow anonymous users');
        return false;
      }
      
      // Determine action
      const isCurrentlyFollowing = $button.hasClass('following');
      const action = isCurrentlyFollowing ? 'unfollow' : 'follow';
      
      console.log('Action:', action);
      
      // Set processing state
      $button.addClass('processing').prop('disabled', true);
      
      // Store original state for rollback
      const originalText = $button.text();
      const originalClass = $button.hasClass('following');
      
      // Optimistic UI update
      if (action === 'follow') {
        $button.addClass('following').text('Following');
      } else {
        $button.removeClass('following').text('Follow');
      }
      
      // AJAX request
      $.ajax({
        url: 'toggle_follow.php',
        type: 'POST',
        data: {
          poll_id: pollId,
          action: action
        },
        dataType: 'json',
        timeout: 10000,
        beforeSend: function() {
          console.log('Sending AJAX request...');
        }
      })
      .done(function(response) {
        console.log('AJAX Response:', response);
        
        if (response && response.success === true) {
          // Success - update all buttons for this creator
          $(`.follow-btn[data-creator-id="${creatorId}"]`).each(function() {
            const $btn = $(this);
            if (action === 'follow') {
              $btn.addClass('following').text('Following');
            } else {
              $btn.removeClass('following').text('Follow');
            }
          });
          
          console.log(`Successfully ${action}ed user`);
          showToast('Success', action === 'follow' ? 'Now following user' : 'Unfollowed user', 'success');
        } else {
          // Server error - rollback
          console.error('Server error:', response);
          rollbackButton($button, originalText, originalClass);
          showToast('Error', response.message || 'Operation failed', 'error');
        }
      })
      .fail(function(xhr, status, error) {
        console.error('AJAX failed:', {status, error, responseText: xhr.responseText});
        
        // Network error - rollback
        rollbackButton($button, originalText, originalClass);
        showToast('Error', 'Network error. Please try again.', 'error');
      })
      .always(function() {
        // Always remove processing state
        $button.removeClass('processing').prop('disabled', false);
        console.log('AJAX request completed');
      });
      
      return false;
    });

    // VOTING HANDLER
    $(document).on('click.polls', '.answer-option', function () {
      const $option = $(this);
      const $pollCard = $option.closest(".poll-card");
      const pollId = parseInt($pollCard.data("poll-id"));
      const answerId = parseInt($option.data("answer-id"));

      if ($pollCard.hasClass("submitting-vote") || 
          $option.hasClass("selected") || 
          $option.hasClass("processing")) {
        return false;
      }

      $option.addClass("processing");
      $pollCard.addClass("submitting-vote");
      $option.addClass("loading-vote");

      $.ajax({
        url: "submit_vote.php",
        type: "POST",
        data: {
          poll_id: pollId,
          answer_id: answerId,
        },
        dataType: "json",
        timeout: 10000,
        success: function (response) {
          if (response.success) {
            updatePollVoteDisplay($pollCard, response.data, answerId);
            $pollCard.addClass("vote-success");
            setTimeout(function () {
              $pollCard.removeClass("vote-success");
            }, 1500);
          } else {
            console.error("Error submitting vote:", response.message);
          }
        },
        error: function (xhr, status, error) {
          console.error("Error connecting to server:", status, error);
        },
        complete: function() {
          $pollCard.removeClass("submitting-vote");
          $option.removeClass("loading-vote processing");
        }
      });
      
      return false;
    });

    // STAR BUTTON HANDLER
    $(document).on('click.polls', '.star-btn', function () {
      const $button = $(this);
      const $pollCard = $button.closest(".poll-card");
      const pollId = $button.data("poll-id");

      if ($button.hasClass('processing')) {
        return false;
      }

      if ($pollCard.data("is-anonymous") === true || $pollCard.data("is-anonymous") === "true") {
        console.warn("Attempted to favorite anonymous post");
        return false;
      }

      $button.addClass('processing');
      $button.toggleClass("active");
      const action = $button.hasClass("active") ? "add" : "remove";

      $.ajax({
        url: "toggle_favorite.php",
        type: "POST",
        data: {
          poll_id: pollId,
          action: action,
        },
        dataType: "json",
        timeout: 10000,
        success: function (response) {
          if (!response.success) {
            $button.toggleClass("active");
            console.error("Favorite operation failed:", response.message);
          }
        },
        error: function (xhr, status, error) {
          $button.toggleClass("active");
          console.error("Favorite request failed:", status, error);
        },
        complete: function() {
          $button.removeClass('processing');
        }
      });
      
      return false;
    });

    // COMMENT HANDLERS
    $(document).on('click.polls', '.comment-count', function () {
      const $commentCount = $(this);
      const $pollCard = $commentCount.closest(".poll-card");
      const pollId = $pollCard.data("poll-id");
      const $commentSection = $pollCard.find(".comment-section");

      if ($commentSection.is(":visible")) {
        $commentSection.slideUp(200);
        return false;
      }

      $commentSection.slideDown(200);
      const $commentsContainer = $commentSection.find(".comments-container");
      $commentsContainer.html('<div class="comments-loading">Loading comments...</div>');
      loadComments(pollId, $commentsContainer);
      
      return false;
    });

    // POST COMMENT HANDLER
    $(document).on('click.polls', '.post-comment-btn', function () {
      const $button = $(this);
      const $commentForm = $button.closest(".comment-form");
      const $textarea = $commentForm.find("textarea");
      const commentText = $textarea.val().trim();

      if ($button.hasClass('processing')) {
        return false;
      }

      if (!commentText) {
        $textarea.addClass("error").focus();
        return false;
      }

      $textarea.removeClass("error");
      const $pollCard = $button.closest(".poll-card");
      const pollId = $pollCard.data("poll-id");
      const parentCommentId = $commentForm.data("parent-comment-id") || null;

      $button.addClass('processing');
      $textarea.prop("disabled", true);
      $button.prop("disabled", true).text("Posting...");

      $.ajax({
        url: "add_comment.php",
        type: "POST",
        data: {
          poll_id: pollId,
          text: commentText,
          parent_comment_id: parentCommentId,
        },
        dataType: "json",
        timeout: 10000,
        success: function (response) {
          if (response.success) {
            $textarea.val("");

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
              let $replyCount = $parentComment.find('.reply-count span');
              if ($replyCount.length === 0) {
                $parentComment.find('.comment-actions').append('<span class="reply-count">(<span>1</span>)</span>');
              } else {
                let currentCount = parseInt($replyCount.text()) || 0;
                $replyCount.text(currentCount + 1);
              }
              
              // Show replies if they were hidden
              $repliesContainer.show();
              
              // Update toggle button
              let $toggleBtn = $parentComment.find('.toggle-replies');
              if ($toggleBtn.length === 0) {
                $parentComment.find('.reply-btn').after('<button class="toggle-replies showing-replies">Hide replies</button>');
              } else {
                $toggleBtn.addClass('showing-replies').text('Hide replies');
              }
              
              // Remove reply form
              $commentForm.remove();
            } else {
              // This is a new top-level comment - reload comments
              const $commentsContainer = $pollCard.find(".comments-container");
              loadComments(pollId, $commentsContainer);
            }
            
            // Update comment count in footer
            const $countSpan = $pollCard.find(".comment-count span:last-child");
            const currentCount = parseInt($countSpan.text()) || 0;
            $countSpan.text(currentCount + 1);
          } else {
            console.error("Comment submission failed:", response.message);
            showToast('Error', response.message || 'Failed to post comment', 'error');
          }
        },
        error: function (xhr, status, error) {
          console.error("Comment request failed:", status, error);
          showToast('Error', 'Failed to post comment. Please try again.', 'error');
        },
        complete: function() {
          $button.removeClass('processing');
          $textarea.prop("disabled", false);
          $button.prop("disabled", false).text("Post");
        }
      });
      
      return false;
    });

    // REPLY BUTTON HANDLER
    $(document).on('click.polls', '.reply-btn', function() {
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
      
      return false;
    });

    // CANCEL REPLY HANDLER
    $(document).on('click.polls', '.cancel-reply-btn', function() {
      const $button = $(this);
      const $replyForm = $button.closest('.reply-form');
      $replyForm.remove();
      return false;
    });

    // TOGGLE REPLIES HANDLER
    $(document).on('click.polls', '.toggle-replies', function() {
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
      
      return false;
    });

    // USERNAME/PROFILE CLICK HANDLERS
    $(document).on('click.polls', '.username:not(.username-anonymous)', function () {
      const userId = $(this).data("user-id");
      if (userId) {
        window.location.href = `profile.php?user_id=${userId}`;
      }
      return false;
    });

    $(document).on('click.polls', '.profile-image:not(.profile-image-anonymous)', function () {
      const userId = $(this).data("user-id");
      if (userId) {
        window.location.href = `profile.php?user_id=${userId}`;
      }
      return false;
    });

    // HOVER EFFECTS
    $(document).on('mouseenter.polls', '.username:not(.username-anonymous)', function () {
      $(this).css({
        'cursor': 'pointer',
        'color': '#BE6AFF',
        'text-decoration': 'underline'
      });
    }).on('mouseleave.polls', '.username:not(.username-anonymous)', function () {
      $(this).css({
        'text-decoration': 'none'
      });
    });

    $(document).on('mouseenter.polls', '.profile-image:not(.profile-image-anonymous)', function () {
      $(this).css({
        'cursor': 'pointer',
        'transform': 'scale(1.05)',
        'box-shadow': '0 4px 12px rgba(190, 106, 255, 0.3)'
      });
    }).on('mouseleave.polls', '.profile-image:not(.profile-image-anonymous)', function () {
      $(this).css({
        'transform': 'scale(1)',
        'box-shadow': ''
      });
    });
  }

  // Helper function to rollback button state
  function rollbackButton($button, originalText, wasFollowing) {
    if (wasFollowing) {
      $button.addClass('following');
    } else {
      $button.removeClass('following');
    }
    $button.text(originalText);
    console.log('Button state rolled back');
  }

  // Function to update vote display for specific poll
  function updatePollVoteDisplay($pollCard, responseData, selectedAnswerId) {
    $pollCard.find(".answer-option").removeClass("selected");
    $pollCard.find(".vote-indicator").remove();
    $pollCard.addClass("user-voted");

    if (responseData && responseData.answers) {
      responseData.answers.forEach(function (answer) {
        const $answerOption = $pollCard.find(
          `.answer-option[data-answer-id="${answer.answer_id}"]`
        );
        
        if ($answerOption.length) {
          $answerOption
            .find(".answer-percentage")
            .text(`${answer.percentage}%`);
          
          $answerOption
            .find(".answer-progress")
            .css("width", `${answer.percentage}%`);

          if (parseInt(answer.answer_id) === parseInt(selectedAnswerId)) {
            $answerOption.addClass("selected");
            $answerOption.append('<span class="vote-indicator">✓ Your vote</span>');
          }
        }
      });
    }
  }

  // Function to load comments for a poll
  function loadComments(pollId, $container, page = 1) {
    $.ajax({
      url: "fetch_comments.php",
      type: "GET",
      data: {
        poll_id: pollId,
        page: page,
        limit: 10,
      },
      dataType: "json",
      timeout: 10000,
      success: function (response) {
        $container.find(".comments-loading").remove();

        if (response.success) {
          const comments = response.data;

          if (comments.length === 0 && page === 1) {
            $container.html(
              '<div class="no-comments">No comments yet. Be the first to comment!</div>'
            );
          } else {
            if (page === 1) {
              $container.empty();
            }

            comments.forEach(function (comment) {
              const $comment = createCommentElement(comment);
              $container.append($comment);
            });
          }
        } else {
          $container.html(
            '<div class="comments-error">Error loading comments: ' +
              response.message +
              "</div>"
          );
        }
      },
      error: function (xhr, status, error) {
        $container.find(".comments-loading").remove();
        $container.html(
          '<div class="comments-error">Error connecting to server. Please try again.</div>'
        );
        console.error('Load comments error:', {status, error});
      },
    });
  }

  // Function to create comment element with replies
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

  // Function to create reply element
  function createReplyElement(reply) {
    return $(`
      <div class="comment reply" data-comment-id="${reply.comment_id}" data-parent-id="${reply.parent_comment_id || ''}">
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

  // Simple toast function
  function showToast(title, message, type) {
    console.log(`${type.toUpperCase()}: ${title} - ${message}`);
    
    if (type === 'success' || type === 'error') {
      const $toast = $(`
        <div class="simple-toast" style="
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${type === 'success' ? '#4CAF50' : '#f44336'};
          color: white;
          padding: 12px 20px;
          border-radius: 6px;
          z-index: 10000;
          font-size: 14px;
          transform: translateX(100%);
          transition: transform 0.3s ease;
        ">
          ${type === 'success' ? '✓' : '✗'} ${message}
        </div>
      `);
      
      $('body').append($toast);
      
      setTimeout(() => $toast.css('transform', 'translateX(0)'), 10);
      
      setTimeout(() => {
        $toast.css('transform', 'translateX(100%)');
        setTimeout(() => $toast.remove(), 300);
      }, 3000);
    }
  }

  // Enhanced reloadPolls function for filter system
  function reloadPolls() {
    console.log('Reloading polls...');
    currentPage = 1;
    hasMorePolls = true;
    isLoading = false;
    pollsContainer.empty();
    loadPolls();
  }

  // Debug function to check button states
  function debugFollowButtons() {
    setTimeout(function() {
      const followButtons = $('.follow-btn');
      console.log('Found', followButtons.length, 'follow buttons');
      
      followButtons.each(function(index) {
        const $btn = $(this);
        console.log(`Button ${index}:`, {
          creatorId: $btn.data('creator-id'),
          isFollowing: $btn.hasClass('following'),
          text: $btn.text(),
          isAnonymous: $btn.closest('.poll-card').data('is-anonymous'),
          isProcessing: $btn.hasClass('processing'),
          isDisabled: $btn.prop('disabled')
        });
      });
    }, 1000);
  }

  // Call debug function
  debugFollowButtons();

  // Expose useful functions globally for debugging and filter system
  window.pollsDebug = {
    loadPolls: loadPolls,
    showToast: showToast,
    debugFollowButtons: debugFollowButtons,
    currentPage: function() { return currentPage; },
    hasMorePolls: function() { return hasMorePolls; },
    isLoading: function() { return isLoading; },
    reloadPolls: reloadPolls, // Enhanced for filter system
    testFollow: function(pollId, action) {
      console.log('Testing follow functionality...');
      $.ajax({
        url: "toggle_follow.php",
        method: 'POST',
        data: {
          poll_id: pollId,
          action: action
        },
        dataType: 'json',
        timeout: 10000
      })
      .done(function(response) {
        console.log('Test follow response:', response);
      })
      .fail(function(xhr, status, error) {
        console.error('Test follow failed:', {status, error, responseText: xhr.responseText});
      });
    }
  };

});