// Waiting List Functionality
(function ($) {
    "use strict";
    
    // Open waiting list modal when clicking on "Join Waiting List" button
    $(document).on('click', '.waiting-list', function() {
        let slot = $(this).data('slot');
        let post_id = $(this).data('post-id');
        
        // Set values in the form
        $('#waiting_list_post_id').val(post_id);
        $('#waiting_list_date').val(slot);
        
        // Show the modal
        $('.mpwpb_waiting_list_modal').fadeIn(300);
        
        // Clear previous messages
        $('.mpwpb_waiting_list_message').html('').removeClass('success error');
    });
    
    // Close modal when clicking on close button or outside the modal
    $(document).on('click', '.mpwpb_close_modal, .mpwpb_waiting_list_modal', function(e) {
        if (e.target === this) {
            $('.mpwpb_waiting_list_modal').fadeOut(300);
        }
    });
    
    // Prevent modal content clicks from closing the modal
    $(document).on('click', '.mpwpb_modal_content', function(e) {
        e.stopPropagation();
    });
    
    // Handle waiting list form submission
    $(document).on('submit', '#mpwpb_waiting_list_form', function(e) {
        e.preventDefault();
        
        let form = $(this);
        let message_container = form.find('.mpwpb_waiting_list_message');
        
        // Get form data
        let post_id = $('#waiting_list_post_id').val();
        let date = $('#waiting_list_date').val();
        let name = $('#waiting_list_name').val();
        let email = $('#waiting_list_email').val();
        let phone = $('#waiting_list_phone').val();
        
        // Validate form
        if (!name || !email) {
            message_container.html('<p class="error">Please fill all required fields</p>').addClass('error');
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            type: 'POST',
            url: mpwpb_ajax.ajax_url,
            data: {
                action: 'mpwpb_join_waiting_list',
                post_id: post_id,
                date: date,
                name: name,
                email: email,
                phone: phone,
                nonce: mpwpb_ajax.nonce
            },
            beforeSend: function() {
                message_container.html('<p>Processing your request...</p>').removeClass('success error');
                form.find('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    message_container.html('<p class="success">' + response.data.message + '</p>').addClass('success');
                    
                    // Clear form
                    $('#waiting_list_name').val('');
                    $('#waiting_list_email').val('');
                    $('#waiting_list_phone').val('');
                    
                    // Close modal after 3 seconds
                    setTimeout(function() {
                        $('.mpwpb_waiting_list_modal').fadeOut(300);
                    }, 3000);
                    
                    // Update the button to show "Waiting List Joined"
                    $('button.waiting-list[data-slot="' + date + '"]').text('Waiting List Joined').removeClass('waiting-list').addClass('waiting-list-joined');
                } else {
                    message_container.html('<p class="error">' + response.data.message + '</p>').addClass('error');
                }
            },
            error: function() {
                message_container.html('<p class="error">An error occurred. Please try again.</p>').addClass('error');
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    });
    
    // Check waiting list status on page load
    $(document).ready(function() {
        $('.waiting-list').each(function() {
            let button = $(this);
            let post_id = button.data('post-id');
            let slot = button.data('slot');
            
            // Check if user is already on waiting list
            $.ajax({
                type: 'POST',
                url: mpwpb_ajax.ajax_url,
                data: {
                    action: 'mpwpb_check_waiting_list',
                    post_id: post_id,
                    date: slot,
                    nonce: mpwpb_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (!response.data.available) {
                            button.text('Fully Booked').removeClass('waiting-list').addClass('booked');
                        }
                    }
                }
            });
        });
    });
    
}(jQuery));