/**
 * Recurring Booking JavaScript
 */
(function($) {
    "use strict";

    window.initRecurringBooking = function() {
        if ($('.mpwpb_recurring_booking_area').length > 0) {
            if ($('link[href*="mpwpb_recurring_booking.css"]').length === 0) {
                $('head').append('<link rel="stylesheet" type="text/css" href="' + mpwpb_ajax.plugin_url + '/assets/frontend/mpwpb_recurring_booking.css">');
            }
        }

        $('div.mpwpb_registration').each(function() {
            let parent = $(this);
            if (!parent.data('post-id')) {
                let postId = 0;
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

    $(document).ready(function() {
        initRecurringBooking();
    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_area .to-book', function() {

        $("#mpwpb_recurring_booking_area").fadeIn();

        $('div.mpwpb_registration .mpwpb_date_time_area .to-book').removeClass('mpwpb_active_time');
        $(this).addClass('mpwpb_active_time');

        let post_id = $(this).parent().parent().attr('id').trim();

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

            setTimeout(function() {
                console.log('Showing recurring booking area');
            }, 300);
        }


        let date_time = $(this).attr('data-date').trim();
        let data_radio_check = $(this).attr('data-radio-check').trim();
        let dateObj = new Date(date_time);
        let year = dateObj.getFullYear();
        let month = String(dateObj.getMonth() + 1).padStart(2, '0'); // Months are 0-based
        let day = String(dateObj.getDate()).padStart(2, '0');

        let formattedDate = `${year}-${month}-${day}`;
        let hours24 = dateObj.getHours();

        let ajaxUrl = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.ajax_url : mpwpb_ajax.ajax_url;
        let nonce = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.nonce : mpwpb_ajax.nonce;

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'mpwpb_get_available_staff',
                service_id: post_id,
                staff_date: formattedDate,
                staff_time: hours24,
                date_time: data_radio_check,
                nonce: nonce
            },
            beforeSend: function() {
                parent.find('#mpwpb_recurring_dates_list').html('<li>Loading...</li>');
                parent.find('.mpwpb_recurring_dates').show();
            },
            success: function(response) {
                $("#mpwpb_staff_member_holder").html( response.html );
                if( response.count < 1 ){
                    $("#mpwpb_progress_staff").fadeOut();
                    $("#mpwpb_progress_checkout").addClass('active');
                    $("#mpwpb_show_hide_staff_member").hide();

                    $("#mpwpb_staff_arrow").hide();

                    $("#mpwpb_date_time_next_btn_id").fadeIn();
                }else{
                    if ($('#mpwpb_progress_checkout').hasClass('active')) {
                        $('#mpwpb_progress_checkout').removeClass('active');
                    }
                    $("#mpwpb_progress_staff").fadeIn();
                    $("#mpwpb_show_hide_staff_member").fadeIn();
                    $("#mpwpb_date_time_next_btn_id").hide();
                    $("#mpwpb_staff_arrow").fadeIn();

                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                parent.find('#mpwpb_recurring_dates_list').html('<li>Error: ' + error + '</li>');
            }
        });

    });

    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringArea = parent.find('.mpwpb_recurring_booking_area');
        let selectedDate = $(this).val();

        if (recurringArea.length > 0 && selectedDate) {
            parent.find('#mpwpb_enable_recurring_booking').prop('checked', false);
            parent.find('.mpwpb_recurring_settings').hide();
            parent.find('#mpwpb_recurring_type').val('');
            parent.find('#mpwpb_recurring_count').val(2);
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();

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

            let parent_div = $('.next_date_area');
            let total_bill = $('#mpwpb_total_before_recurring').text();
            parent_div.find('#mpwpd_all_total_bill').text( total_bill );

            parent.find('.mpwpb_recurring_settings').slideUp(350);
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_order_display').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();

            $('#mpwpd_selected_date li.mpwpd_service_date').not(':first').hide();


        }
    });

    let price_in_number = 0;
    $(document).on('change', '#mpwpb_recurring_type, #mpwpb_recurring_count', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringType = parent.find('#mpwpb_recurring_type').val();
        if( recurringType !== 'daily' ){
            // $("#mpwpb_weekday_selector").fadeIn();
        }else{
            // $("#mpwpb_weekday_selector").fadeOut();
        }
        let recurringCount = parseInt(parent.find('#mpwpb_recurring_count').val());
        let selectedDate = parent.find('[name="mpwpb_date"]').val();

        let recurring_discount_price = 0;
        let recurring_discount = $('.mpwpb_recurring_discount');
        if( recurring_discount.find('p').length > 0){
            recurring_discount_price = parseInt( recurring_discount.find('p' ).attr('data-discount').trim() );
        }

        let selectedRecurringDays = [];
        $('input[name="recurring_days[]"]:checked').each(function () {
            selectedRecurringDays.push($(this).val()); // ['mon', 'wed', 'fri']
        });

        if (recurringType && recurringCount >= 2 && selectedDate) {
            generateRecurringDates(parent, selectedDate, recurringType, recurringCount, selectedRecurringDays, recurring_discount_price );
        } else {
            parent.find('.mpwpb_recurring_dates').hide();
            parent.find('#mpwpb_recurring_dates_list').empty();
        }

        let total_recurring = 4;
        parent.find('#mpwpb_recurring_order_display').fadeIn();
        parent.find('#mpwpb_recurring_discount_value').text( recurring_discount_price+'%' );

    });

    function recuring_price_with_discount( recurringCount, recurring_discount_price ){
        let parent_div = $('.next_date_area');
        let total_bill = parent_div.find('#mpwpd_all_total_bill').text();
        if( price_in_number === 0 ){
            price_in_number =  parseInt( total_bill.replace(/[^\d.]/g, '') );
        }

        let currency = total_bill.replace(/[0-9.]/g, '');
        let total_bill_new = price_in_number * recurringCount ;
        let discountAmount = ( total_bill_new * recurring_discount_price ) / 100;
        total_bill_new = total_bill_new - discountAmount;
        let bill = total_bill_new.toFixed(2)+   currency;
        parent_div.find('#mpwpd_all_total_bill').text( bill );

    }
    
    function generateRecurringDates( parent, startDate, recurringType, recurringCount, selectedRecurringDays, recurring_discount_price ) {
        let postId = parent.data('post-id');
        if (!postId) {
            postId = parent.closest('.mpwpb_details').data('post-id');
        }

        if (!postId) {
            let urlParams = new URLSearchParams(window.location.search);
            postId = urlParams.get('id');
        }

        if (!postId && typeof mpwpb_recurring_data !== 'undefined' && mpwpb_recurring_data.post_id) {
            postId = mpwpb_recurring_data.post_id;
        }

        if (!postId && typeof mpwpb_ajax !== 'undefined' && mpwpb_ajax.post_id) {
            postId = mpwpb_ajax.post_id;
        }
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
                selectedRecurringDays: selectedRecurringDays,
                dates: [startDate],
                nonce: nonce
            },
            beforeSend: function() {
                parent.find('#mpwpb_recurring_dates_list').html('<li>Loading...</li>');
                parent.find('.mpwpb_recurring_dates').show();
            },
            success: function(response) {
                if (response.success && response.data && response.data.dates) {
                    displayRecurringDates(parent, response.data.dates,  response.data.dates_html, response.data.selected_html, recurring_discount_price );
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
    function displayRecurringDates(parent, dates, html, selected_html, recurring_discount_price ) {
        let datesList = parent.find('#mpwpb_recurring_dates_list');
        datesList.empty();
        
        if (dates && dates.length > 0) {
            parent.find('#mpwpd_selected_date').empty();
            datesList.append( html );
            parent.find('#mpwpb_summary_date_item').find('#mpwpd_selected_date').append( selected_html );
            parent.find('.mpwpb_recurring_dates').show();

            let total_recurring = $('#mpwpb_recurring_dates_list li[data-date-time!=""]').length;
            parent.find('#mpwpb_recurring_count_hidden').val( total_recurring );
            parent.find('#mpwpb_recurring_number').text( total_recurring );

            recuring_price_with_discount( total_recurring, recurring_discount_price );
        } else {
            parent.find('.mpwpb_recurring_dates').hide();
        }
    }

    // Delete handler
    $(document).on('click', '.mpwpb_recurring_delete_icon', function () {
        const parent = $('#mpwpb_recurring_dates_list');
        const totalItems = parent.find('li').length;
        if (totalItems <= 2) {
            alert('At least 2 dates must remain.');
            return;
        }
        const dateTime = $(this).closest('li').attr('data-date-time');
        $(this).closest('li').remove();
        $('#mpwpd_selected_date')
            .find(`li[data-cart-date-time="${dateTime}"]`)
            .remove();

        let total_recurring = $('#mpwpb_recurring_dates_list li[data-date-time!=""]').length;
        $('#mpwpb_recurring_count_hidden').val( total_recurring );
        $('#mpwpb_recurring_number').text( total_recurring );

        let recurring_discount_price = 0;
        let recurring_discount = $('.mpwpb_recurring_discount');
        if( recurring_discount.find('p').length > 0){
            recurring_discount_price = parseInt( recurring_discount.find('p' ).attr('data-discount').trim() );
        }
        recuring_price_with_discount( total_recurring, recurring_discount_price );
    });
    // Edit handler



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
    function formatDate_new(dateString) {
        let date = new Date(dateString);
        let options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        // Check if 24-hour format is enabled
        let use24Hour = 'no';
        if (typeof mpwpb_recurring_data !== 'undefined' && mpwpb_recurring_data.use_24hour) {
            use24Hour = mpwpb_recurring_data.use_24hour;
        }
        
        if (use24Hour === 'yes') {
            options.hour12 = false; // Use 24-hour format
        }
        
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

        } else {
            // Remove recurring data if it exists
            if (parent.find('[name="mpwpb_is_recurring"]').length) {
                parent.find('[name="mpwpb_is_recurring"]').val(0);
            }
        }
    });

    $(document).on('click', '.mpwpb_recurring_edit_icon', function () {
        $('.mpwpb_edit_recurring_datetime_popup').fadeIn();

    });

    $(document).on('click', '.mpwpb_recurring_edit_icon', function () {

        // let parent = $(this).closest('div.mpwpb_registration');
        $('.mpwpb_edit_recurring_datetime_popup').fadeIn();

        let li = $(this).closest('li');
        let oldDateTime = li.attr('data-date-time'); // original value
        let newDate =  oldDateTime;

        if ( newDate ) {
            let dateOnly = oldDateTime.split(' ')[0];
            let ajaxUrl = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.ajax_url : mpwpb_ajax.ajax_url;
            let nonce = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.nonce : mpwpb_ajax.nonce;
            let postId = mpwpb_recurring_data.post_id;

            $.ajax({
                type: 'POST',
                url: ajaxUrl,
                data: {
                    action: 'mpwpb_get_filtered_time_by_date',
                    post_id: postId,
                    dates: dateOnly,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.dates) {
                        $("#mpwpb_recurring_time_holedr").html(response.data.dates );
                    } else {
                        console.error('AJAX Data Loadinf error:');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                }
            });

            let formattedDate = formatDate_new( newDate ); // your existing formatting function
            $("#mpwpb_recurring_datetime_set").attr('data-recurringli-id', oldDateTime );
            $("#date_type_edit_recurring").val( formattedDate );
            $("#mpwpb_date_edit_recurring").val( oldDateTime );

            let li = $(this).closest('li');
            let currentDateTime = li.attr('data-date-time');
            $('#mpwpb_recurring_datetime_set')
                .data('target-li', li) // attach li element directly
                .data('recurringli-id', currentDateTime);

        }
    });

    $(document).on('click', '#mpwpb_recurring_datetime_set', function () {
        let li = $(this).data('target-li');
        let targetDateTime = $(this).data('recurringli-id').trim(); // পুরাতন datetime

        let get_date = $("#mpwpb_date_edit_recurring").val().trim(); // যেমন: 2025-07-14
        let get_time = $("#mpwpb_get_selected_time").val();

        if (get_date === '' || get_time === '') {
            alert('Please select both date and time.');
            return;
        }

        let hour = ('0' + get_time).slice(-2);
        let dateTime = `${get_date} ${hour}:00:00`; // Final format: 2025-07-14 04:00:00
        let formattedDate = formatDate_new(dateTime); // যেমন: July 14, 2025 at 04:00 AM

        if (li && li.length) {
            li.attr('data-date-time', dateTime);
            li.addClass('mpwpb_recurring_days');
            li.html(`
            <div>${li.index() + 1} ${formattedDate}</div>
            <div class="mpwpb_recurring_actions">
                <span class="mpwpb_recurring_edit_icon">✏️</span>
                <span class="mpwpb_recurring_delete_icon">✖</span>
            </div>
        `);
        }

        let summaryLi = $('#mpwpd_selected_date')
            .find(`li[data-cart-date-time="${targetDateTime}"]`);

        if (summaryLi.length) {
            summaryLi
                .attr('data-cart-date-time', dateTime)
                .text(formattedDate);
        }
        hide_recurring_popup();
    });

    $(document).on('click', '.mpwpb_edit_recurring_datetime_close, .mpwpb_edit_recurring_datetime_overlay', function () {
        hide_recurring_popup();
    });

    $(document).on('click', '.mpwpb_select_datetime_timeslot', function () {
        $('.mpwpb_select_datetime_timeslot').removeClass( 'mpwpb_selected_time' );
        let time = $(this).attr('data-time');
        if( time !== '' ){
            $("#mpwpb_get_selected_time").val( time );
        }
        $(this).addClass( 'mpwpb_selected_time' );
    });

    function hide_recurring_popup(){
        $("#date_type_edit_recurring").val('');
        $("#mpwpb_date_edit_recurring").val('');
        $('.mpwpb_edit_recurring_datetime_popup').fadeOut();
    }

    
})(jQuery);