/**
 * Recurring Booking JavaScript
 */
(function($) {
    "use strict";

    // Make initialization function available globally
    window.initRecurringBooking = function() {
        console.log('Initializing recurring booking functionality');

        // Load CSS
        if ($('.mpwpb_recurring_booking_area').length > 0) {
            if ($('link[href*="mpwpb_recurring_booking.css"]').length === 0) {
                $('head').append('<link rel="stylesheet" type="text/css" href="' + mpwpb_ajax.plugin_url + '/assets/frontend/mpwpb_recurring_booking.css">');
            }
        }

        // Make sure post ID is set for all registration forms
        $('div.mpwpb_registration').each(function() {
            let parent = $(this);
            if (!parent.data('post-id')) {
                let postId = 0;
                // Try to get post ID from URL
                let urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('id')) {
                    postId = urlParams.get('id');
                } else if (typeof mpwpb_ajax !== 'undefined' && mpwpb_ajax.post_id) {
                    postId = mpwpb_ajax.post_id;
                }

                if (postId) {
                    parent.attr('data-post-id', postId);
                    console.log('Set post ID:', postId);
                }
            }
        });
    };

    // Initialize recurring booking functionality
    $(document).ready(function() {
        initRecurringBooking();
    });
    
    // Show recurring options when a date is selected
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_area .to-book', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringArea = parent.find('.mpwpb_recurring_booking_area');

        if (recurringArea.length > 0) {
            // Reset recurring options
            parent.find('#mpwpb_enable_recurring_booking').prop('checked', false);
            parent.find('.mpwpb_recurring_settings').hide();
            parent.find('#mpwpb_recurring_type').val('');
            parent.find('#mpwpb_recurring_count').val(2);
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();

            // Show recurring area after a short delay to ensure date is selected
            setTimeout(function() {
                recurringArea.slideDown(350);
                console.log('Showing recurring booking area');
            }, 300);
        }
    });

    // Also handle the radio-check event which is triggered when a date is selected
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringArea = parent.find('.mpwpb_recurring_booking_area');
        let selectedDate = $(this).val();

        if (recurringArea.length > 0 && selectedDate) {
            // Reset recurring options
            parent.find('#mpwpb_enable_recurring_booking').prop('checked', false);
            parent.find('.mpwpb_recurring_settings').hide();
            parent.find('#mpwpb_recurring_type').val('');
            parent.find('#mpwpb_recurring_count').val(2);
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();

            // Show recurring area
            recurringArea.slideDown(350);
            console.log('Date selected, showing recurring booking area');
        }
    });
    
    // Toggle recurring settings
    $(document).on('change', '#mpwpb_enable_recurring_booking', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let isChecked = $(this).is(':checked');
        
        if (isChecked) {
            parent.find('.mpwpb_recurring_settings').slideDown(350);
        } else {
            parent.find('.mpwpb_recurring_settings').slideUp(350);
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();
        }
    });
    
    // Generate recurring dates when type or count changes
    $(document).on('change', '#mpwpb_recurring_type, #mpwpb_recurring_count', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringType = parent.find('#mpwpb_recurring_type').val();
        let recurringCount = parseInt(parent.find('#mpwpb_recurring_count').val());
        let selectedDate = parent.find('[name="mpwpb_date"]').val();
        
        if (recurringType && recurringCount >= 2 && selectedDate) {
            generateRecurringDates(parent, selectedDate, recurringType, recurringCount);
        } else {
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();
        }
    });
    
    // Function to generate recurring dates
    function generateRecurringDates(parent, startDate, recurringType, recurringCount) {
        // Get the post ID
        let postId = parent.data('post-id');
        if (!postId) {
            postId = parent.closest('.mpwpb_details').data('post-id');
        }

        // If still no post ID, try to get it from the URL
        if (!postId) {
            let urlParams = new URLSearchParams(window.location.search);
            postId = urlParams.get('id');
        }

        // If still no post ID, use the data from localized script
        if (!postId && typeof mpwpb_recurring_data !== 'undefined' && mpwpb_recurring_data.post_id) {
            postId = mpwpb_recurring_data.post_id;
        }

        // If still no post ID, use the current page ID from ajax object
        if (!postId && typeof mpwpb_ajax !== 'undefined' && mpwpb_ajax.post_id) {
            postId = mpwpb_ajax.post_id;
        }

        console.log('Generating recurring dates for post ID:', postId);
        console.log('Start date:', startDate);
        console.log('Recurring type:', recurringType);
        console.log('Recurring count:', recurringCount);

        // Determine which AJAX URL and nonce to use
        let ajaxUrl = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.ajax_url : mpwpb_ajax.ajax_url;
        let nonce = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.nonce : mpwpb_ajax.nonce;

        // AJAX call to generate recurring dates
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'mpwpb_save_recurring_booking',
                post_id: postId,
                recurring_type: recurringType,
                recurring_count: recurringCount,
                dates: [startDate],
                nonce: nonce
            },
            beforeSend: function() {
                parent.find('#mpwpb_recurring_dates_list').html('<li>Loading...</li>');
                parent.find('.mpwpb_recurring_dates').show();
            },
            success: function(response) {
                console.log('Recurring dates response:', response);

                if (response.success && response.data && response.data.dates) {
                    displayRecurringDates(parent, response.data.dates);
                } else {
                    let errorMessage = 'Error generating dates';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    parent.find('#mpwpb_recurring_dates_list').html('<li>' + errorMessage + '</li>');
                    console.error('Error response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                parent.find('#mpwpb_recurring_dates_list').html('<li>Error: ' + error + '</li>');
            }
        });
    }
    
    // Function to display recurring dates
    function displayRecurringDates(parent, dates) {
        let datesList = parent.find('#mpwpb_recurring_dates_list');
        datesList.empty();
        
        if (dates && dates.length > 0) {
            $.each(dates, function(index, date) {
                let formattedDate = formatDate(date);
                let listItem = $('<li>').text(formattedDate);
                
                if (index === 0) {
                    listItem.prepend('<strong>' + (index + 1) + '. </strong>');
                } else {
                    listItem.prepend((index + 1) + '. ');
                }
                
                datesList.append(listItem);
            });
            
            parent.find('.mpwpb_recurring_dates').show();
        } else {
            parent.find('.mpwpb_recurring_dates').hide();
        }
    }
    
    // Format date for display
    function formatDate(dateString) {
        let date = new Date(dateString);
        let options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString(undefined, options);
    }
    
    // Modify the add to cart process to include recurring booking data
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_next', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let isRecurringEnabled = parent.find('#mpwpb_enable_recurring_booking').is(':checked');

        if (isRecurringEnabled) {
            let recurringType = parent.find('#mpwpb_recurring_type').val();
            let recurringCount = parseInt(parent.find('#mpwpb_recurring_count').val());

            // Validate recurring data
            if (!recurringType || recurringCount < 2) {
                alert('Please select a recurring type and ensure the number of occurrences is at least 2.');
                return false;
            }

            // Add recurring data to the form
            if (!parent.find('[name="mpwpb_is_recurring"]').length) {
                parent.append('<input type="hidden" name="mpwpb_is_recurring" value="1">');
                parent.append('<input type="hidden" name="mpwpb_recurring_type" value="' + recurringType + '">');
                parent.append('<input type="hidden" name="mpwpb_recurring_count" value="' + recurringCount + '">');
            } else {
                parent.find('[name="mpwpb_is_recurring"]').val(1);
                parent.find('[name="mpwpb_recurring_type"]').val(recurringType);
                parent.find('[name="mpwpb_recurring_count"]').val(recurringCount);
            }

            // Log for debugging
            console.log('Recurring booking enabled:', {
                type: recurringType,
                count: recurringCount,
                dates: parent.find('#mpwpb_recurring_dates_list').html()
            });
        } else {
            // Remove recurring data if it exists
            if (parent.find('[name="mpwpb_is_recurring"]').length) {
                parent.find('[name="mpwpb_is_recurring"]').val(0);
            }
        }
    });
    
})(jQuery);