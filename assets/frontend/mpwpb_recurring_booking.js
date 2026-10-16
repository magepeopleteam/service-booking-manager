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

        // Was this slot already the selected one? The Staff step has a "Back"
        // button that returns here, and re-clicking the same (already
        // active) slot used to unconditionally wipe the recurring dates list
        // below -- so configuring 6 recurring dates, going to Staff, then
        // back and re-clicking the same date, silently lost all of them.
        // mpwpb_registration.js's add-to-cart handler then found
        // #mpwpb_recurring_dates_list empty and fell back to sending just
        // the single base date, which is why the resulting order only ever
        // had one date no matter how many were configured.
        let alreadySelected = $(this).hasClass('mpwpb_active_time');

        $("#mpwpb_recurring_booking_area").fadeIn();

        $('div.mpwpb_registration .mpwpb_date_time_area .to-book').removeClass('mpwpb_active_time');
        $(this).addClass('mpwpb_active_time');

        // Reflect a Happy Hours discount (if this slot has one) in the
        // running total immediately -- see mpwpb_price_calculation()'s
        // data-hh-type/data-hh-value handling.
        if (typeof mpwpb_price_calculation === 'function') {
            mpwpb_price_calculation($(this));
        }

        let post_id = $(this).parent().parent().attr('id').trim();

        let parent = $(this).closest('div.mpwpb_registration');
        let recurringArea = parent.find('.mpwpb_recurring_booking_area');
        if (recurringArea.length > 0 && !alreadySelected) {
            // Reset recurring options -- only when the customer actually
            // picked a *different* date/time slot than before.
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
        // Date/time for the staff lookup come from data-radio-check
        // ("2026-10-14 10:00"), not data-date: data-date is display text and
        // now carries the slot's end time too ("October 14, 2026 10:00 am -
        // 11:00 am"), which new Date() rejects as Invalid Date -- the request
        // then sent "NaN-NaN-NaN"/NaN, no staff ever matched, and the staff
        // step was skipped for every staff-enabled service.
        let formattedDate = '';
        let hours24 = NaN;
        let slotMatch = data_radio_check.match(/^(\d{4}-\d{2}-\d{2})\s+(\d{1,2}):(\d{2})/);
        if (slotMatch) {
            formattedDate = slotMatch[1];
            hours24 = parseInt(slotMatch[2], 10) + (parseInt(slotMatch[3], 10) / 60);
        } else {
            let dateObj = new Date(date_time.split(' - ')[0]);
            let year = dateObj.getFullYear();
            let month = String(dateObj.getMonth() + 1).padStart(2, '0'); // Months are 0-based
            let day = String(dateObj.getDate()).padStart(2, '0');
            formattedDate = `${year}-${month}-${day}`;
            hours24 = dateObj.getHours() + (dateObj.getMinutes() / 60);
        }

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
                // Which of "Next Staff Member" / "Proceed to Checkout" is
                // correct depends entirely on this response (response.count),
                // so both are hidden while it's in flight -- previously
                // whichever button was left over from the *last* slot's
                // result stayed clickable during this fetch, and if the
                // customer clicked through before this (possibly slow)
                // response arrived, the success handler below would still
                // fire afterward and yank the wizard back to the staff step,
                // undoing whatever they'd already done. A loader in the
                // staff holder itself gives clear "still working" feedback
                // instead of the buttons just silently vanishing.
                $("#mpwpb_show_hide_staff_member").hide();
                $("#mpwpb_date_time_next_btn_id").hide();
                // Remembered for mpwpbRefreshStaff() (re-run on "Next Staff
                // Member" once the recurring dates are final).
                $("#mpwpb_staff_member_holder").data({ mpwpbServiceId: post_id, mpwpbSlot: data_radio_check });
                mpwpb_loader($("#mpwpb_staff_member_holder"));
            },
            success: function(response) {
                var $holder = $("#mpwpb_staff_member_holder");
                mpwpb_loaderRemove($holder);
                $holder.html( response.html );
                $holder.data('mpwpbStaffCount', response.count || 0);
                // A new list arrives with "Any Staff" pre-selected -- clear any
                // staff picked for the previous time slot so the two agree.
                $("#mpwpb_staff_member_booking").val('');
                // Staff-enabled service (the staff step is only rendered for
                // those, date_time_select.php): the Date & Time step offers
                // ONLY "Next Staff Member" -- "Proceed to Checkout" belongs to
                // the staff step. No free staff for this slot is reported on
                // that step instead of silently skipping it.
                if ($('#mpwpb_staff_member_booking_area').length) {
                    if (!response.count) {
                        $holder.html($('<p class="mpwpb_no_staff_notice"></p>').text($holder.attr('data-no-staff-text') || 'No staff member is available for this time. Please choose another time slot.'));
                    }
                    $('#mpwpb_progress_checkout').removeClass('active');
                    $("#mpwpb_progress_staff").fadeIn();
                    $("#mpwpb_date_time_next_btn_id").hide();
                    $("#mpwpb_show_hide_staff_member").fadeIn();
                    $("#mpwpb_staff_arrow").fadeIn();
                    return;
                }
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
                var $holder = $("#mpwpb_staff_member_holder");
                mpwpb_loaderRemove($holder);
                // Neither button was left visible going into this request --
                // always leave the customer a way forward: the staff step for
                // staff-enabled services (where the failure is explained),
                // otherwise straight to checkout.
                if ($('#mpwpb_staff_member_booking_area').length) {
                    $holder.data('mpwpbStaffCount', 0).html($('<p class="mpwpb_no_staff_notice"></p>').text($holder.attr('data-load-error-text') || 'Staff availability could not be loaded. Please choose the time slot again.'));
                    $("#mpwpb_date_time_next_btn_id").hide();
                    $("#mpwpb_show_hide_staff_member").fadeIn();
                    return;
                }
                $("#mpwpb_show_hide_staff_member").hide();
                $("#mpwpb_date_time_next_btn_id").fadeIn();
            }
        });

    });

    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function() {
        let parent = $(this).closest('div.mpwpb_registration');
        let recurringArea = parent.find('.mpwpb_recurring_booking_area');
        let selectedDate = $(this).val();
        // Same base-date value as last time this fired -- e.g. the "change"
        // event re-firing because something re-set [name="mpwpb_date"] to the
        // slot the customer already had selected (the Staff step's "Back"
        // button round-trip is the real-world trigger). Only reset the
        // already-configured recurring dates when the date genuinely changed.
        let previousDate = parent.data('mpwpbLastDateValue');
        parent.data('mpwpbLastDateValue', selectedDate);

        if (recurringArea.length > 0 && selectedDate && selectedDate !== previousDate) {
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
            // Start on the first allowed type (first chip) instead of nothing
            // selected. The change (re)generates the dates list straight away
            // -- also when switching back on, since switching off emptied it.
            let $type = parent.find('#mpwpb_recurring_type');
            if (!$type.val()) {
                $type.val($type.find('option').filter(function () { return !!this.value; }).first().val() || '');
            }
            if ($type.val()) {
                $type.trigger('change');
            }
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
            syncStaffStepSummaryDate( parent );
        } else {
            parent.find('.mpwpb_recurring_dates').hide();
        }
        syncRecurringUi( parent );
    }

    // The "Select Staff" step's compact recap (#mpwpb_staff_selected_datetime,
    // templates/registration/date_time_select.php) only ever showed the single
    // date picked before recurring was configured, and nothing kept it in sync
    // afterward -- re-derive it from #mpwpd_selected_date's current rows (the
    // same list the main summary shows) every time the recurring dates change
    // (generated, or a row deleted). "Already Booked" rows are wrapped in an
    // inner .mpwpb_seleted_date_text span (see Admin/settings/Recurring_Booking.php
    // ::save_recurring_booking()) -- skip those, an unavailable date isn't a
    // real service date.
    function syncStaffStepSummaryDate( parent ) {
        let availableDates = [];
        parent.find('#mpwpd_selected_date > li.mpwpd_service_date').each(function () {
            if ($(this).find('.mpwpb_seleted_date_text').length === 0) {
                let text = $(this).text().trim();
                if (text) {
                    availableDates.push(text);
                }
            }
        });
        if (availableDates.length > 0) {
            parent.find('#mpwpb_staff_selected_datetime').text(availableDates.join(', '));
        }
    }

    /* ------------------------------------------------------------------ *
     *  Redesigned recurring block (templates/registration/date_time_select.php):
     *  type chips, occurrences stepper and the "N bookings · range" summary
     *  are front-ends only -- the real #mpwpb_recurring_type <select>,
     *  #mpwpb_recurring_count input and the dates list stay the source of
     *  truth, and every existing handler above keeps reading them. This just
     *  mirrors their current state onto the new controls.
     * ------------------------------------------------------------------ */
    function syncRecurringUi( scope ) {
        $(scope || document).find('.mpwpb_recurring_booking').each(function () {
            let $box = $(this);
            let $select = $box.find('#mpwpb_recurring_type');
            let $chips = $box.find('.mpwpb_recurring_type_chips');
            if ($chips.length && !$chips.children().length) {
                $select.find('option').each(function () {
                    if (!this.value) { return; }
                    $('<button type="button" class="mpwpb_recurring_chip" role="radio"></button>')
                        .attr('data-recurring-type', this.value)
                        .text($.trim($(this).text()))
                        .appendTo($chips);
                });
            }
            let current = $select.val() || '';
            $chips.find('[data-recurring-type]').each(function () {
                let on = $(this).attr('data-recurring-type') === current;
                $(this).toggleClass('is-active', on).attr('aria-checked', on ? 'true' : 'false');
            });

            let $count = $box.find('#mpwpb_recurring_count');
            let value = parseInt($count.val(), 10) || 2;
            let min = parseInt($count.attr('min'), 10) || 2;
            let max = parseInt($count.attr('max'), 10) || 26;
            $box.find('[data-recurring-step="-1"]').prop('disabled', value <= min);
            $box.find('[data-recurring-step="1"]').prop('disabled', value >= max);

            let $dates = $box.find('.mpwpb_recurring_dates');
            let $list = $box.find('#mpwpb_recurring_dates_list');
            let $items = $list.find('li').filter(function () { return !!$(this).attr('data-date-time'); });
            $list.toggleClass('is-min', $items.length <= 2);
            if ($items.length) {
                let label = ($dates.attr('data-count-label') || '%d bookings').replace('%d', $items.length);
                $dates.find('.mpwpb_recurring_dates_count').text(label);
                // Earliest/latest, not first/last row: an edited row can move anywhere.
                let stamps = $items.map(function () { return new Date($(this).attr('data-date-time').replace(' ', 'T')).getTime(); }).get()
                    .filter(function (t) { return !isNaN(t); });
                let first = new Date(stamps.length ? Math.min.apply(null, stamps) : NaN);
                let last = new Date(stamps.length ? Math.max.apply(null, stamps) : NaN);
                let range = '';
                if (!isNaN(first) && !isNaN(last)) {
                    let short = { month: 'short', day: 'numeric' };
                    range = first.toLocaleDateString(undefined, short) + ' – ' + last.toLocaleDateString(undefined, $.extend({ year: 'numeric' }, short));
                }
                $dates.find('.mpwpb_recurring_dates_range').text(range);
            }
        });
    }
    window.mpwpbSyncRecurringUi = syncRecurringUi;

    /**
     * Reload the staff cards for EVERY booking date -- the first slot plus
     * each recurring occurrence, collected exactly like add-to-cart does
     * (mpwpb_registration.js). The list fetched on the time-slot click only
     * knew the first date, so with recurring on a staff member who is off on
     * a later occurrence was listed, then rejected at checkout with "The
     * selected staff member is not available." Keeps the customer's pick
     * when that staff member is still free.
     */
    window.mpwpbRefreshStaff = function (parent) {
        var $holder = $("#mpwpb_staff_member_holder");
        var serviceId = $holder.data('mpwpbServiceId');
        if (!serviceId) { return; }
        var dates = [];
        $('#mpwpb_recurring_dates_list li').each(function () {
            var dt = $(this).data('date-time');
            if (dt) { dates.push(String(dt).substring(0, 16)); }
        });
        if (!dates.length) {
            var single = parent.find('[name="mpwpb_date"]').val() || $holder.data('mpwpbSlot') || '';
            if (single) { dates.push(String(single).substring(0, 16)); }
        }
        var picked = $("#mpwpb_staff_member_booking").val();
        var ajaxUrl = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.ajax_url : mpwpb_ajax.ajax_url;
        var nonce = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.nonce : mpwpb_ajax.nonce;
        $("#mpwpb_date_time_next_btn_id").hide();
        mpwpb_loader($holder);
        $.post(ajaxUrl, {
            action: 'mpwpb_get_available_staff',
            service_id: serviceId,
            date_time: dates[0] || $holder.data('mpwpbSlot') || '',
            dates: dates.join(','),
            nonce: nonce
        }).done(function (response) {
            mpwpb_loaderRemove($holder);
            $holder.html(response.html);
            // Same count rule as the time-slot request above.
            $holder.data('mpwpbStaffCount', response.count || 0);
            var $keep = picked ? $holder.find('.mpwpb_selected_staff').filter(function () { return this.value === picked; }) : $();
            if ($keep.length) {
                $holder.find('.mpwp_select_staff_card').removeClass('selected').attr('aria-pressed', 'false');
                $keep.closest('.mpwp_select_staff_card').addClass('selected').attr('aria-pressed', 'true');
            } else {
                $("#mpwpb_staff_member_booking").val('');
            }
            if (!response.count) {
                $holder.html($('<p class="mpwpb_no_staff_notice"></p>').text($holder.attr('data-no-staff-text') || 'No staff member is available for this time. Please choose another time slot.'));
                $("#mpwpb_date_time_next_btn_id").hide();
            } else {
                $("#mpwpb_date_time_next_btn_id").fadeIn();
            }
        }).fail(function () {
            mpwpb_loaderRemove($holder);
            $holder.data('mpwpbStaffCount', 0).html($('<p class="mpwpb_no_staff_notice"></p>').text($holder.attr('data-load-error-text') || 'Staff availability could not be loaded. Please choose the time slot again.'));
            $("#mpwpb_date_time_next_btn_id").hide();
        });
    };

    $(document).on('click', '.mpwpb_recurring_chip', function () {
        let $box = $(this).closest('.mpwpb_recurring_booking');
        $box.find('#mpwpb_recurring_type').val($(this).attr('data-recurring-type')).trigger('change');
    });
    $(document).on('click', '[data-recurring-step]', function () {
        let $count = $(this).closest('.mpwpb_recurring_count').find('#mpwpb_recurring_count');
        let min = parseInt($count.attr('min'), 10) || 2;
        let max = parseInt($count.attr('max'), 10) || 26;
        let next = (parseInt($count.val(), 10) || min) + parseInt($(this).attr('data-recurring-step'), 10);
        next = Math.max(min, Math.min(max, next));
        if (next !== parseInt($count.val(), 10)) {
            $count.val(next).trigger('change');
        }
    });
    // After every change that can reset or regenerate these fields (the
    // handlers above run first; setTimeout lets them finish).
    $(document).on('change', '#mpwpb_recurring_type, #mpwpb_recurring_count, #mpwpb_enable_recurring_booking, div.mpwpb_registration [name="mpwpb_date"]', function () {
        let scope = $(this).closest('div.mpwpb_registration');
        setTimeout(function () { syncRecurringUi(scope.length ? scope : document); }, 0);
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_area .to-book', function () {
        let scope = $(this).closest('div.mpwpb_registration');
        setTimeout(function () { syncRecurringUi(scope); }, 0);
    });
    $(function () { syncRecurringUi(document); });

    // Delete handler
    $(document).on('click', '.mpwpb_recurring_delete_icon', function () {
        const parent = $('#mpwpb_recurring_dates_list');
        const totalItems = parent.find('li').length;
        if (totalItems <= 2) {
            alert('At least 2 dates must remain.');
            return;
        }
        const dateTime = $(this).closest('li').attr('data-date-time');
        const registrationParent = $(this).closest('div.mpwpb_registration');
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
        syncStaffStepSummaryDate( registrationParent.length ? registrationParent : $(document) );
        syncRecurringUi( registrationParent.length ? registrationParent : document );
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

    /* ---------------------------------------------------------------- *
     *  Edit one recurring date (pencil icon) -> popup with date + time.
     *  The date field is found by class, never by id: the shared datepicker
     *  setup (mpwpb_plugin_global.js) blanks the id on every re-init and
     *  jQuery UI then gives it a random "dp…" one, so `#date_type_edit_recurring`
     *  stopped matching and the field always opened empty. The hidden value
     *  holds the DATE ONLY ("Y-m-d"); it used to hold the whole datetime, so
     *  "Set" without re-picking a date built "Y-m-d H:i:s H:00:00".
     * ---------------------------------------------------------------- */
    function editPopup() { return $('.mpwpb_edit_recurring_datetime_popup'); }
    function editDateInput() { return editPopup().find('.date_type_edit_recurring'); }
    function toHm(value) {
        var m = String(value || '').trim().match(/^(\d{1,2})(?::(\d{2}))?/);
        return m ? ('0' + m[1]).slice(-2) + ':' + (m[2] || '00') : '';
    }
    function loadEditTimes(dateOnly) {
        var $holder = $('#mpwpb_recurring_time_holedr');
        var keep = toHm($('#mpwpb_get_selected_time').val());
        var ajaxUrl = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.ajax_url : mpwpb_ajax.ajax_url;
        var nonce = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.nonce : mpwpb_ajax.nonce;
        var postId = (typeof mpwpb_recurring_data !== 'undefined') ? mpwpb_recurring_data.post_id : '';
        $holder.html($('<div class="mpwpb_recurring_loader"></div>').text(editPopup().attr('data-loading-text') || 'Loading…'));
        $.post(ajaxUrl, { action: 'mpwpb_get_filtered_time_by_date', post_id: postId, dates: dateOnly, nonce: nonce })
            .done(function (response) {
                $holder.html(response && response.success && response.data ? (response.data.dates || '') : '');
                if (!$holder.find('.mpwpb_select_datetime_timeslot, .mpwpb_selected_datetime_timeslot').length) {
                    $holder.html($('<p class="mpwpb_recurring_no_slots"></p>').text(editPopup().attr('data-no-slots-text') || 'No times available on this date.'));
                }
                // Keep the previously chosen time when it's still free on this date.
                var $match = $holder.find('.mpwpb_select_datetime_timeslot').filter(function () {
                    return toHm($(this).attr('data-time')) === keep;
                });
                if ($match.length) {
                    $match.first().addClass('mpwpb_selected_time');
                } else {
                    $('#mpwpb_get_selected_time').val('');
                }
            })
            .fail(function () { $holder.html(''); $('#mpwpb_get_selected_time').val(''); });
    }

    /**
     * (Re)build the popup's own datepicker on every open with jQuery UI's
     * real destroy/create -- mpwpb_plugin_global.js's shared setup strips
     * the instance by hand (class/id/data/unbind) and re-creates it, which
     * can leave the open calendar bound to a stale "dp…" id so clicking a
     * day or the month arrows did nothing.
     */
    function initEditDatepicker($dp, dateObj) {
        var offDays = String($('#mpwpb_off_days_data').val() || '').toLowerCase().split(',').map($.trim).filter(Boolean);
        var offDates = String($('#mpwpb_off_dates_data').val() || '').split(',').map($.trim).filter(Boolean);
        var weekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dp.each(function () {
            var $el = $(this);
            if ($el.hasClass('hasDatepicker')) {
                try { $el.datepicker('destroy'); } catch (e) { $el.removeClass('hasDatepicker').removeData('datepicker'); }
            }
            $el.datepicker({
                dateFormat: (typeof mpwpb_date_format !== 'undefined' && mpwpb_date_format) ? mpwpb_date_format : 'MM d, yy',
                changeMonth: true,
                changeYear: false,
                minDate: 0,
                defaultDate: dateObj,
                beforeShow: function (input, inst) {
                    setTimeout(function () {
                        inst.dpDiv.addClass('mpwpb_custom_datepicker_style').css('z-index', 1000001);
                    }, 0);
                },
                beforeShowDay: function (date) {
                    var ymd = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
                    return [offDates.indexOf(ymd) === -1 && offDays.indexOf(weekDays[date.getDay()]) === -1];
                },
                onSelect: function () {
                    var picked = $(this).datepicker('getDate');
                    if (!picked) { return; }
                    var ymd = picked.getFullYear() + '-' + ('0' + (picked.getMonth() + 1)).slice(-2) + '-' + ('0' + picked.getDate()).slice(-2);
                    $('#mpwpb_date_edit_recurring').val(ymd);
                    loadEditTimes(ymd);
                }
            });
            $el.datepicker('setDate', dateObj);
        });
    }

    $(document).on('click', '.mpwpb_recurring_edit_icon', function () {
        var li = $(this).closest('li');
        // Unavailable rows carry no data-date-time; start from the original slot.
        var oldDateTime = String(li.attr('data-date-time') || li.attr('data-original-date-time') || '');
        if (!oldDateTime) { return; }
        var dateOnly = oldDateTime.substring(0, 10);
        var parts = dateOnly.split('-');
        var dateObj = new Date(+parts[0], +parts[1] - 1, +parts[2]);

        $('#mpwpb_date_edit_recurring').val(dateOnly);
        $('#mpwpb_get_selected_time').val(toHm(oldDateTime.substring(11)));
        var $dp = editDateInput();
        if ($.fn.datepicker) {
            initEditDatepicker($dp, dateObj);
        } else {
            $dp.val(dateObj.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }));
        }
        $('#mpwpb_recurring_datetime_set')
            .attr('data-recurringli-id', oldDateTime)
            .data('target-li', li)
            .data('recurringli-id', oldDateTime);
        editPopup().css('display', 'flex').hide().fadeIn(150);
        loadEditTimes(dateOnly);
    });

    $(document).on('click', '#mpwpb_recurring_datetime_set', function () {
        let li = $(this).data('target-li');
        let targetDateTime = String($(this).data('recurringli-id') || '').trim();
        let get_date = String($("#mpwpb_date_edit_recurring").val() || '').trim().substring(0, 10);
        let hm = toHm($("#mpwpb_get_selected_time").val());

        if (!/^\d{4}-\d{2}-\d{2}$/.test(get_date) || !hm) {
            alert(editPopup().attr('data-required-text') || 'Please select both date and time.');
            return;
        }
        let dateTime = `${get_date} ${hm}:00`;
        // Two occurrences on the same slot would be the same booking twice.
        let clash = $('#mpwpb_recurring_dates_list li').not(li).filter(function () {
            return String($(this).attr('data-date-time') || '').substring(0, 16) === dateTime.substring(0, 16);
        });
        if (clash.length) {
            alert(editPopup().attr('data-duplicate-text') || 'This date and time is already in your list.');
            return;
        }
        let formattedDate = formatDate_new(dateTime.replace(' ', 'T'));

        if (li && li.length) {
            li.attr('data-date-time', dateTime).data('date-time', dateTime);
            li.addClass('mpwpb_recurring_days').removeClass('mpwpb_invalid_recurring_days');
            let dObj = new Date(dateTime.replace(' ', 'T'));
            let dateOnly = dObj.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
            let dayTime = dObj.toLocaleDateString(undefined, { weekday: 'long' }) + ' at ' + dObj.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
            li.html(`
            <span class="mpwpb_recurring_number">${li.index() + 1}</span>
            <div class="mpwpb_recurring_date_text">
                <strong>${dateOnly}</strong>
                <span class="mpwpb_recurring_daytime">${dayTime}</span>
            </div>
            <div class="mpwpb_recurring_actions">
                <span class="mpwpb_recurring_edit_icon"><i class="fas fa-pen"></i></span>
                <span class="mpwpb_recurring_delete_icon"><i class="fas fa-times"></i></span>
            </div>
        `);
        }

        let summaryLi = $('#mpwpd_selected_date')
            .find(`li[data-cart-date-time="${targetDateTime}"]`);

        if (summaryLi.length) {
            summaryLi
                .attr('data-cart-date-time', dateTime)
                .text(formattedDate);
        } else {
            // A previously unavailable row had no summary entry -- add one.
            $('#mpwpd_selected_date').append($('<li class="mpwpd_service_date"></li>').attr('data-cart-date-time', dateTime).text(formattedDate));
        }
        if (li && li.length) {
            li.removeAttr('data-original-date-time');
        }
        hide_recurring_popup();
        if (typeof window.mpwpbSyncRecurringUi === 'function') {
            window.mpwpbSyncRecurringUi($('#mpwpb_recurring_booking_area'));
        }
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
        editDateInput().val('');
        $("#mpwpb_date_edit_recurring").val('');
        $("#mpwpb_get_selected_time").val('');
        editPopup().fadeOut(150);
    }

    
})(jQuery);
