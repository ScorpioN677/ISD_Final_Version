//-----Enhanced Categories List with Clear Filters----- 
$(document).ready(function () {
    // Remove any existing handlers to prevent conflicts
    $(document).off('change', 'input[name="categories[]"]');
    $(document).off('click', '.clear-filters-btn');
    
    // Single, unified handler for category changes
    $(document).on('change', 'input[name="categories[]"]', function () {
        console.log('Category changed:', $(this).val(), 'checked:', $(this).is(':checked'));
        updateCheckedCategories();
    });
    
    // Unified function to update checked categories
    function updateCheckedCategories() {
        let checkedLabels = [];
        $('.checkedCat').empty();
        
        $('input[name="categories[]"]:checked').each(function () {
            const categoryId = $(this).val();
            const categoryName = $(`label[for="cat${categoryId}"]`).text();
            checkedLabels.push(categoryName);
        });
        
        // Update the display
        if (checkedLabels.length > 0) {
            $('.checkedCat').html(checkedLabels.map(text => `<span>${text}</span>`).join('<br>'));
            $('#checkMark').show();
            showClearFiltersButton();
        } else {
            $('.checkedCat').empty();
            $('#checkMark').hide();
            hideClearFiltersButton();
        }
        
        console.log('Updated categories:', checkedLabels);
    }
    
    // Show clear filters button
    function showClearFiltersButton() {
        // Remove existing button if it exists
        $('.clear-filters-btn').remove();
        
        // Add clear filters button to the main polls container
        const $clearButton = $(`
            <div class="clear-filters-container">
                <button class="clear-filters-btn">
                    <span class="clear-icon">✕</span>
                    Clear Filters & Show All Polls
                </button>
                <div class="active-filters">
                    <span class="filter-label">Active filters:</span>
                    <div class="filter-tags"></div>
                </div>
            </div>
        `);
        
        // Insert before the polls container
        $('#pollsContainer').before($clearButton);
        
        // Update filter tags
        updateFilterTags();
    }
    
    // Hide clear filters button
    function hideClearFiltersButton() {
        $('.clear-filters-container').remove();
    }
    
    // Update filter tags display
    function updateFilterTags() {
        const $filterTags = $('.filter-tags');
        $filterTags.empty();
        
        $('input[name="categories[]"]:checked').each(function () {
            const categoryId = $(this).val();
            const categoryName = $(`label[for="cat${categoryId}"]`).text();
            
            const $tag = $(`
                <span class="filter-tag" data-category-id="${categoryId}">
                    ${categoryName}
                    <button class="remove-filter" data-category-id="${categoryId}">×</button>
                </span>
            `);
            
            $filterTags.append($tag);
        });
    }
    
    // Handle clear all filters button click
    $(document).on('click', '.clear-filters-btn', function() {
        console.log('Clear filters button clicked');
        clearAllFilters();
    });
    
    // Handle individual filter tag removal
    $(document).on('click', '.remove-filter', function() {
        const categoryId = $(this).data('category-id');
        console.log('Removing filter for category:', categoryId);
        
        // Uncheck the specific category
        $(`#cat${categoryId}`).prop('checked', false);
        
        // Update display
        updateCheckedCategories();
        
        // If no filters left, reload all polls
        if ($('input[name="categories[]"]:checked').length === 0) {
            reloadAllPolls();
        } else {
            // Re-apply remaining filters
            $('#filtering').trigger('submit');
        }
    });
    
    // Function to clear all filters
    function clearAllFilters() {
        // Uncheck all categories
        $('input[name="categories[]"]').prop('checked', false);
        
        // Update display
        updateCheckedCategories();
        
        // Hide filter dropdown
        $('.filterPolls').hide();
        
        // Reload all polls
        reloadAllPolls();
    }
    
    // Function to reload all polls (clear filters)
    function reloadAllPolls() {
        console.log('Reloading all polls without filters');
        
        // Check if pollsDebug exists (from polls.js)
        if (typeof window.pollsDebug !== 'undefined' && window.pollsDebug.reloadPolls) {
            window.pollsDebug.reloadPolls();
        } else {
            // Fallback: reload the page
            window.location.reload();
        }
    }
    
    // Handle cancel button
    $(document).on('click', '#filtering button[type="button"]', function() {
        console.log('Cancel button clicked');
        $('.filterPolls').hide();
        // Don't clear the checkboxes when canceling, just hide the dropdown
    });
    
    // Handle filter form submission
    $(document).on('submit', '#filtering', function(e) {
        console.log('Filter form submitted');
        // Hide the filter dropdown after submission
        setTimeout(function() {
            $('.filterPolls').hide();
        }, 100);
    });
    
    // Show filter dropdown when filter button is clicked
    $(document).on('click', '#filter', function() {
        console.log('Filter button clicked');
        $('.filterPolls').toggle();
    });
    
    // Close filter dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#filter').length && !$(e.target).closest('.filterPolls').length) {
            $('.filterPolls').hide();
        }
    });
    
    // Initialize on page load
    setTimeout(function() {
        updateCheckedCategories();
    }, 500);
    
    // Expose functions globally for other scripts to use
    window.updateCheckedCategories = updateCheckedCategories;
    window.clearAllFilters = clearAllFilters;
    window.reloadAllPolls = reloadAllPolls;
});

$(document).ready(function () {
    // chatbot toggle handlers - safer and consistent with jQuery
    const $chatbotImg = $('#chatbotImg');
    const $chatbotList = $('#chatbotList');
    const $chatbotArrow = $('#chatbotArrow');

    if ($chatbotImg.length && $chatbotList.length && $chatbotArrow.length) {
        $chatbotImg.on('click', function(e) {
            e.preventDefault();
            $chatbotList.show();
        });

        $chatbotArrow.on('click', function() {
            $chatbotList.hide();
        });
    }
});