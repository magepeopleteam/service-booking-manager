jQuery(document).ready(function($) {
    'use strict';
    
    // Cancel booking
    $('.mpwpb-cancel-btn').on('click', function(e) {
        e.preventDefault();
        
        const bookingId = $(this).data('id');
        
        if (confirm(mpwpb_dashboard.cancel_confirm)) {
            $.ajax({
                type: 'POST',
                url: mpwpb_dashboard.ajaxurl,
                data: {
                    action: 'mpwpb_cancel_booking',
                    booking_id: bookingId,
                    nonce: mpwpb_dashboard.nonce
                },
                beforeSend: function() {
                    // Show loading state
                    $(e.target).prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message and reload page
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message);
                        $(e.target).prop('disabled', false).text('Cancel');
                    }
                },
                error: function() {
                    // Show error message
                    alert('An error occurred. Please try again.');
                    $(e.target).prop('disabled', false).text('Cancel');
                }
            });
        }
    });
    
    // Reschedule booking - open modal
    $('.mpwpb-reschedule-btn').on('click', function(e) {
        e.preventDefault();
        
        const bookingId = $(this).data('id');
        const serviceId = $(this).data('service');
        
        // Set values in the form
        $('#booking_id').val(bookingId);
        $('#service_id').val(serviceId);
        
        // Clear previous options
        $('#new_date').html('<option value="">Select Date</option>');
        $('#new_time').html('<option value="">Select Time</option>');
        
        // Get available dates
        $.ajax({
            type: 'POST',
            url: mpwpb_dashboard.ajaxurl,
            data: {
                action: 'mpwpb_get_available_dates',
                service_id: serviceId,
                nonce: mpwpb_dashboard.nonce
            },
            beforeSend: function() {
                $('#new_date').prop('disabled', true);
            },
            success: function(response) {
                if (response.success && response.data.dates) {
                    // Populate date dropdown
                    $.each(response.data.dates, function(index, date) {
                        $('#new_date').append('<option value="' + date.value + '">' + date.label + '</option>');
                    });
                }
                $('#new_date').prop('disabled', false);
            },
            error: function() {
                alert('Error loading available dates. Please try again.');
                $('#new_date').prop('disabled', false);
            }
        });
        
        // Show modal
        $('#mpwpb-reschedule-modal').css('display', 'block');
    });
    
    // Close modal when clicking the X
    $('.mpwpb-close').on('click', function() {
        $('#mpwpb-reschedule-modal').css('display', 'none');
    });
    
    // Close modal when clicking outside the content
    $(window).on('click', function(e) {
        if ($(e.target).is('#mpwpb-reschedule-modal')) {
            $('#mpwpb-reschedule-modal').css('display', 'none');
        }
    });
    
    // Get time slots when date is selected
    $('#new_date').on('change', function() {
        const date = $(this).val();
        const serviceId = $('#service_id').val();
        
        if (!date) {
            $('#new_time').html('<option value="">Select Time</option>').prop('disabled', true);
            return;
        }
        
        // Get available time slots for the selected date
        $.ajax({
            type: 'POST',
            url: mpwpb_dashboard.ajaxurl,
            data: {
                action: 'mpwpb_get_available_times',
                service_id: serviceId,
                date: date,
                nonce: mpwpb_dashboard.nonce
            },
            beforeSend: function() {
                $('#new_time').html('<option value="">Loading...</option>').prop('disabled', true);
            },
            success: function(response) {
                $('#new_time').html('<option value="">Select Time</option>');
                
                if (response.success && response.data.times) {
                    // Populate time dropdown
                    $.each(response.data.times, function(index, time) {
                        $('#new_time').append('<option value="' + time.value + '">' + time.label + '</option>');
                    });
                }
                
                $('#new_time').prop('disabled', false);
            },
            error: function() {
                $('#new_time').html('<option value="">Select Time</option>').prop('disabled', false);
                alert('Error loading available times. Please try again.');
            }
        });
    });
    
    // Submit reschedule form
    $('#mpwpb-reschedule-form').on('submit', function(e) {
        e.preventDefault();
        
        const bookingId = $('#booking_id').val();
        const newDate = $('#new_date').val();
        const newTime = $('#new_time').val();
        
        if (!newDate || !newTime) {
            alert('Please select both date and time.');
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: mpwpb_dashboard.ajaxurl,
            data: {
                action: 'mpwpb_reschedule_booking',
                booking_id: bookingId,
                new_date: newDate,
                new_time: newTime,
                nonce: mpwpb_dashboard.nonce
            },
            beforeSend: function() {
                $('.mpwpb-submit-btn').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data.message);
                    $('.mpwpb-submit-btn').prop('disabled', false).text('Confirm Reschedule');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('.mpwpb-submit-btn').prop('disabled', false).text('Confirm Reschedule');
            }
        });
    });

    $(document).on('click', '.mpwpb_view_selected_service_staff', function(e) {
        e.preventDefault();

        $('.mpwpb-service-staff_card').fadeOut();
        $(this).closest('.mpwpb-service-wrapper')
            .find('.mpwpb-service-staff_card')
            .fadeIn();
    });

    // Profile update form
    $('#mpwpb-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate password confirmation
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirm').val();
        
        if (password && password !== passwordConfirm) {
            $('#mpwpb-profile-message').removeClass('success').addClass('error').text('Passwords do not match').show();
            return;
        }
        
        // Get form data
        const formData = $(this).serialize();
        
        $.ajax({
            type: 'POST',
            url: mpwpb_dashboard.ajaxurl,
            data: {
                action: 'mpwpb_update_user_profile',
                nonce: mpwpb_dashboard.nonce,
                ...formData
            },
            beforeSend: function() {
                $('.mpwpb-submit-btn').prop('disabled', true).text('Updating...');
                $('#mpwpb-profile-message').hide();
            },
            success: function(response) {
                if (response.success) {
                    $('#mpwpb-profile-message').removeClass('error').addClass('success').text(response.data.message).show();
                    
                    // Clear password fields
                    $('#password, #password_confirm').val('');
                } else {
                    $('#mpwpb-profile-message').removeClass('success').addClass('error').text(response.data.message).show();
                }
                
                $('.mpwpb-submit-btn').prop('disabled', false).text('Update Profile');
            },
            error: function() {
                $('#mpwpb-profile-message').removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $('.mpwpb-submit-btn').prop('disabled', false).text('Update Profile');
            }
        });
    });
});