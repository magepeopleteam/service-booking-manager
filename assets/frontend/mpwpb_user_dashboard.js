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


    function getAllOffDates() {
        let dates = [];
        $('input[name="mpwpb_off_dates[]"]').each(function() {
            let val = $(this).val();
            if (val) {
                dates.push(val);
            }
        });
        return dates;
    }
    function updateOffDaysValue() {
        let selectedDays = [];
        $('.groupCheckBox input[type="checkbox"]').each(function() {
            if ($(this).is(':checked')) {
                selectedDays.push($(this).data('checked'));
            }
        });

        $('input[name="mpwpb_off_days"]').val(selectedDays.join(','));
    }

    $('.groupCheckBox input[type="checkbox"]').on('change', function() {
        updateOffDaysValue();
    });
    updateOffDaysValue();

    $(document).on('click', '#saveScheduleBtn', function(e) {

        e.preventDefault();

        let offDays = $('input[name="mpwpb_off_days"]').val();
        let offDates = getAllOffDates();
        let offDates_str = JSON.stringify( offDates );
        // console.log( offDates_str );

        var days = ['default', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        var schedule = {};

        days.forEach(function(day) {
            const start       = $(`select[name="mpwpb_${day}_start_time"]`).val();
            const end         = $(`select[name="mpwpb_${day}_end_time"]`).val();
            const break_start = $(`select[name="mpwpb_${day}_start_break_time"]`).val();
            const break_end   = $(`select[name="mpwpb_${day}_end_break_time"]`).val();
            schedule[day] = {
                start_time: start,
                end_time: end,
                start_break_time: break_start || "",
                end_break_time: break_end || ""
            };
        });
        if (!schedule['default']) {
            alert("Default time must have both Start and End time.");
            return;
        }
        $.post( mpwpb_ajax_url , {
            action: 'mpwpb_save_specific_schedule',
            schedule: schedule,
            offDays: offDays,
            offDates_str: offDates_str,
        }, function(response) {
            if (response.success) {
                alert('Schedule saved successfully!');
            } else {
                alert('Error: ' + response.data);
            }
        });

    });

    $('#mpmw_staff_add_off_date').on('click', function() {
        $('#mpmw_staff_off_dates_wrapper').append(`
            <div class="mpmw_staff_off_date_row">
                <input type="date" class="mpmw_staff_off_date_input" name="mpmw_staff_off_dates[]">
                <button type="button" class="mpmw_staff_delete_btn">🗑️</button>
            </div>
        `);
    });

    // Remove date row
    $(document).on('click', '.mpmw_staff_delete_btn', function() {
        $(this).closest('.mpmw_staff_off_date_row').remove();
    });

    // Get all selected off dates
    function getOffDates() {
        let offDates = [];
        $('.mpmw_staff_off_date_input').each(function() {
            const val = $(this).val();
            if (val) {
                offDates.push(val);
            }
        });
        return offDates;
    }

    // Example: Get on button click
    $('#mpmw_staff_get_dates').on('click', function() {
        const dates = getOffDates();
        console.log('Selected Dates:', dates);
    });

    function load_sortable_datepicker(parent, item) {
        if(parent.find('.mp_item_insert_before').length>0){
            jQuery(item).insertBefore(parent.find('.mp_item_insert_before').first()).promise().done(function () {
                parent.find('.mp_sortable_area').sortable({
                    handle: jQuery(this).find('.mpwpb_sortable_button')
                });
                mpwpb_load_date_picker(parent);
            });
        }else {
            parent.find('.mp_item_insert').first().append(item).promise().done(function () {
                parent.find('.mp_sortable_area').sortable({
                    handle: jQuery(this).find('.mpwpb_sortable_button')
                });
                mpwpb_load_date_picker(parent);
            });
        }
        return true;
    }
    $(document).find('.mp_sortable_area').sortable({
        handle: $(this).find('.mpwpb_sortable_button')
    });

    $(document).on('click', '.mpwpb_item_remove', function (e) {
        e.preventDefault();
        if (confirm('Are You Sure , Remove this row ? \n\n 1. Ok : To Remove . \n 2. Cancel : To Cancel .')) {
            $(this).closest('.mp_remove_area').slideUp(250).remove();
            return true;
        } else {
            return false;
        }
    });

    $(document).on('click', '.mp_add_item', function () {
        let parent = $(this).closest('.mp_settings_area');
        let item = $(this).next($('.mpwpb_hidden_content')).find(' .mpwpb_hidden_item').html();
        if (!item || item === "undefined" || item === " ") {
            item = parent.find('.mpwpb_hidden_content').first().find('.mpwpb_hidden_item').html();
        }
        load_sortable_datepicker(parent, item);
        parent.find('.mp_item_insert').find('.add_mpwpb_select2').select2({});
        return true;
    });



});