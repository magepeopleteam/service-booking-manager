function mpwpb_price_calculation($this) {
    let parent = $this.closest('div.mpwpb_registration');
    let price = 0;
    parent.find(' .mpwpb_service_item[data-price].mpActive').each(function () {
        let qty = parseInt( jQuery(this).attr('data-service-qty') );
        qty = qty > 0 ? qty : 1;
        let current_price = jQuery(this).data('price') ?? 0;
        current_price = parseFloat(current_price);
        current_price = !isNaN(current_price) && current_price > 0 ? current_price : 0;
        price = price + current_price * qty ;
    });
    parent.find('.mpwpb_extra_service_item').each(function () {
        let service_name = jQuery(this).find('[name="mpwpb_extra_service_type[]"]').val();
        if (service_name) {
            let ex_target = jQuery(this).find('[name="mpwpb_extra_service_qty[]"]');
            let ex_qty = parseInt(ex_target.val());
            ex_qty = ex_qty > 0 ? ex_qty : 1;
            let ex_price = ex_target.data('price');
            ex_price = parseFloat(ex_price);
            ex_price = !isNaN(ex_price) && ex_price > 0 ? ex_price : 0;
            price = price + ex_price * ex_qty;
        }
    });

    /*if( price > 0 ){
        parent.find("#mpwpb_progress_service").addClass('active');
    }else{
        parent.find("#mpwpb_progress_service").removeClass('active');
    }*/

    parent.find('.mpwpb_total_bill').html(mpwpb_price_format(price));

    // Static template's "what you've picked" summary (#mpwpb_selected_summary,
    // static_registration.php) -- defined in mpwpb-booking-tree.js, which
    // loads after this file; guarded since this function also runs on
    // templates/pages that don't have that summary container at all.
    if (typeof updateSelectedSummary === 'function') {
        updateSelectedSummary(parent);
    }
}
//Registration
(function ($) {
    "use strict";
    $(document).ready(function () {
        // Initialize recurring booking script if it exists
        if (typeof initRecurringBooking === 'function') {
            initRecurringBooking();
        }

        $('div.mpwpb_registration').each(function () {
            let parent = $(this);
            let target = parent.find('.all_service_area');
            mpwpb_loader(target);
            if (parent.find('.mpwpb_category_area').length > 0) {
                parent.find('.mpwpb_category_area').slideDown(350).promise().done(function () {
                    mpwpb_load_bg_image();
                    mpwpb_loaderRemove(target);
                });
            } else {
                parent.find('.mpwpb_service_area').slideDown(350).promise().done(function () {
                    mpwpb_load_bg_image();
                    mpwpb_loaderRemove(target);
                });
            }

            // Store post ID in data attribute for recurring booking
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
                }
            }
        });
    });
    //==========tab============//
    $(document).on('click', 'div.mpwpb_registration .mpwpb_service_tab', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        load_service_tab(parent);
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_tab', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        load_date_time_tab(parent);
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_order_proceed_tab', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        load_order_proceed_tab(parent);
    });
    //==========category============//
    function refresh_sub_category(parent) {

        let selectServiceCount = parent.find('.mpwpb_service_item.mpActive').length;
        if( selectServiceCount > 0 ){
            parent.find('.next_service_area').slideDown(350);
            $("#mpwpd_btn_proceed").hide();
        }else{
            parent.find('.next_service_area').slideUp(350);
        }

        parent.find('.mpwpb_service_area,.mpwpb_extra_service_area,.mpwpb_date_time_area,.mpwpb_order_proceed_area').slideUp(350);
        let target_sub_category = parent.find('.mpwpb_sub_category_area');
        parent.find('[name="mpwpb_sub_category"]').val('');
        if (target_sub_category.length > 0) {
            parent.find('.mpwpb_summary_item[data-sub-category]').slideUp('fast');
            let category = parseInt(parent.find('[name="mpwpb_category"]').val());
            target_sub_category.find('.mpwpb_sub_category_item[data-category]').each(function () {
                $(this).removeClass('mpActive');
                if (parseInt($(this).data('category')) === category) {
                    $(this).slideDown(350);
                } else {
                    $(this).slideUp(350);
                }
            });
        }
    }
    function refresh_service(parent) {

        let selectServiceCount = parent.find('.mpwpb_service_item.mpActive').length;
        if( selectServiceCount < 1 ){
            $('#mpwpd_btn_proceed').fadeIn();
        }
        let is_multi_select = $("#mpwpb_multi_category_select").val().trim();
        if( is_multi_select !== 'on' ) {
            parent.find('.mpwpb_extra_service_area,.next_service_area,.mpwpb_date_time_area,.mpwpb_order_proceed_area').slideUp(350);
        }
        let target_sub_category = parent.find('.mpwpb_sub_category_area');
        let target_service = parent.find('.mpwpb_service_area');
        if( is_multi_select !== 'on' ) {
            parent.find('[name="mpwpb_service[]"]').each(function () {
                $(this).val('');
            });
        }
        if( is_multi_select !== 'on' ) {
            parent.find('.mpwpb_summary_item[data-service]').slideUp('fast');
        }
        let category = parseInt(parent.find('[name="mpwpb_category"]').val());
        let sub_category = parseInt(parent.find('[name="mpwpb_sub_category"]').val());
        target_service.find('.mpwpb_service_item[data-category]').each(function () {
            if( is_multi_select !== 'on' ) {
                $(this).removeClass('mpActive');
            }
            $(this).find('.mpwpb_service_button.mActive').each(function () {
                if( is_multi_select !== 'on' ) {
                    mpwpb_all_content_change($(this));
                }
            });
            if (parseInt($(this).data('category')) === category) {
                if (target_sub_category.length > 0) {
                    if (parseInt($(this).data('sub-category')) === sub_category || isNaN(sub_category)) {
                        $(this).slideDown(350);
                    } else {
                        $(this).slideUp(350);
                    }
                } else {
                    $(this).slideDown(350);
                }
            } else {
                $(this).slideUp(350);
            }
        });
    }

    $(document).on('click', '#mpwpb_show_all_category', function () {
        $(this).parent().fadeOut();

        $('.mpwpb_category_itemaa').fadeOut();
        $('.mpwpb_category_section').fadeIn();
        $('.mpwpb_category_item').fadeIn();
        $('.mpwpb_sub_category_area').fadeOut();
        // $('.mpwpb_service_area').fadeOut();
        $('.mpwpb_arrow_icon_holder').fadeOut();
        $('.mpwpb_category_area').fadeIn();

        $('.mpwpb_selected_category_text').text('');
        $('.mpwpb_selected_sub_category_text').text('');

        let parent =  $(this).closest('div.mpwpb_registration');

        parent.find('.mpwpb_category_item').removeClass('mpActive')
        // parent.find('.mpwpb_category_item').removeClass('mpActive');
        parent.find('.mpwpb_sub_category_item').removeClass('mpActive');

        refresh_service( parent );

        refresh_sub_category(parent);
        mpwpb_price_calculation($(this));

    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_category_item', function () {

        let selectedTabText = $(this).find('h6').text().trim();
        $('.mpwpb_selected_category_text').text( selectedTabText );
        $('.mpwpb_category_itemaa').fadeIn();
       $('.mpwpb_sub_category_area').fadeIn();

       if( $('.mpwpb_category_area').find('.mpwpb_sub_category_area').length == 0 ){
           $('.mpwpb_category_area').fadeOut();
       }

        $(this).parent().siblings().fadeOut();
        $(this).fadeOut();

        $("#mpwpb_selected_control").fadeIn();
        let current = $(this);
        let category = current.data('category');

        $('.mpwpb_selected_category_text').attr('data-category', category);

        if (category && !current.hasClass('mpActive')) {
            let parent = current.closest('div.mpwpb_registration');
            let target_sub_category = current.closest('.mpwpb_category_section').find('.mpwpb_sub_category_area');
            let target_service = parent.find('.mpwpb_service_area');

            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.mpwpb_summary_item[data-category]').slideDown('fast').find('h6').html(current.find('h6').html());
            parent.find('[name="mpwpb_category"]').val(category).promise().done(function () {
                refresh_sub_category(parent);
                refresh_service( parent );
            }).promise().done(function () {
                parent.find('.mpwpb_category_item.mpActive').each(function () {
                    $(this).removeClass('mpActive');
                }).promise().done(function () {
                    current.addClass('mpActive');
                    mpwpb_price_calculation(current);
                });
                if (target_sub_category.length > 0) {
                    target_sub_category.slideDown(250);
                    target_service.slideUp('fast');
                    mpwpb_load_bg_image();
                } else {
                    if (target_service.length > 0) {
                        target_service.slideDown(250);
                        mpwpb_load_bg_image();
                    }
                }
            });
        }
    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_category_selected_item', function () {
        $('.mpwpb_arrow_icon_holder').fadeOut();
        $('.mpwpb_selected_sub_category_text').text('');
        $("#mpwpb_selected_control").fadeIn();

        let current = $(this);
        let parent = current.closest('div.mpwpb_registration');
        let category = current.data('category');

        if( $('.mpwpb_category_area').find('.mpwpb_sub_category_area').length == 0 ){
            $('.mpwpb_category_area').fadeOut();
        }else{
            $('.mpwpb_category_area').fadeIn();
        }

        $('.mpwpb_sub_category_area').fadeIn();
        $(this).parent().fadeIn();


        $('.mpwpb_selected_category_text').attr('data-category', category);

        if (category && !current.hasClass('mpActive')) {
            let target_sub_category = current.closest('.mpwpb_category_section').find('.mpwpb_sub_category_area');
            let target_service = parent.find('.mpwpb_service_area');
            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.mpwpb_summary_item[data-category]').slideDown('fast').find('h6').html(current.find('h6').html());
            parent.find('[name="mpwpb_category"]').val(category).promise().done(function () {
                refresh_sub_category(parent);
                refresh_service( parent );
            }).promise().done(function () {
                parent.find('.mpwpb_category_item.mpActive').each(function () {
                    $(this).removeClass('mpActive');
                }).promise().done(function () {
                    current.addClass('mpActive');
                    mpwpb_price_calculation(current);
                });
                if (target_sub_category.length > 0) {
                    target_sub_category.slideDown(250);
                    target_service.slideUp('fast');
                    mpwpb_load_bg_image();
                } else {
                    if (target_service.length > 0) {
                        target_service.slideDown(250);
                        mpwpb_load_bg_image();
                    }
                }
            });
        }
    });
    $(document).on('click', 'div.mpwpb_static .mpwpb_item_box', function () {
        let current = $(this);
        let parent = current.closest('div.mpwpb_registration');
        let category = parseInt(current.data('category'));
        let service = parseInt(current.data('service'));
        load_service_tab(parent);
        // A leaf service box (data-service set) should pre-select that exact
        // service directly -- checked before the category-only branch, since
        // every real service leaf also carries its parent data-category and
        // would otherwise only reveal the category without selecting anything.
        if (service && service > 0) {
            parent.find('.mpwpb_service_item').each(function () {
                if (parseInt($(this).data('service')) === service) {
                    $(this).find('.mpwpb_service_button').trigger('click');
                }
            });
        } else if (category && category > 0) {
            parent.find('.mpwpb_category_item').each(function () {
                if (parseInt($(this).data('category')) === category) {
                    $(this).trigger('click');
                }
            });
        }
    });
    //=========sub category=============//
    $(document).on('click', 'div.mpwpb_registration .mpwpb_sub_category_item', function () {

        let selectedTabText = $(this).find('h6').text().trim();
        let sub_category_show_click = $('.mpwpb_selected_sub_category_text');

        sub_category_show_click.text( selectedTabText );

        // $(this).parent().fadeIn();
        let current = $(this);
        if( !current.hasClass('mpActive')){
            $('.mpwpb_sub_category_area').fadeOut();
            $('.mpwpb_category_area').fadeOut();
            $('.mpwpb_arrow_icon_holder').fadeIn();
        }else{
            $('.mpwpb_selected_sub_category_text').text('');
            $('.mpwpb_arrow_icon_holder').fadeOut();
        }

        let parent = current.closest('div.mpwpb_registration');
        let category = parent.find('[name="mpwpb_category"]').val();
        let sub_category = current.data('sub-category');

        sub_category_show_click.attr('data-category', category);
        sub_category_show_click.attr('data-sub-category', sub_category);

        if (category && sub_category && !current.hasClass('mpActive')) {
            //let target_sub_category = parent.find('.mpwpb_sub_category_area');
            let target_service = parent.find('.mpwpb_service_area');
            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.mpwpb_summary_item[data-sub-category]').slideDown('fast').find('h6').html(current.find('h6').html());
            parent.find('[name="mpwpb_sub_category"]').val(sub_category).promise().done(function () {
                refresh_service( parent );
            }).promise().done(function () {
                parent.find('.mpwpb_sub_category_item.mpActive').each(function () {
                    $(this).removeClass('mpActive');
                }).promise().done(function () {
                    current.addClass('mpActive');
                    mpwpb_price_calculation(current);
                    target_service.slideDown(250);
                    mpwpb_load_bg_image();
                });
            });
        } else {
            $(this).removeClass('mpActive');
            parent.find('.mpwpb_summary_item[data-sub-category]').slideUp('fast').find('h6').html('');
            parent.find('[name="mpwpb_sub_category"]').val('').promise().done(function () {
                refresh_sub_category(parent);
                refresh_service( parent );
            }).promise().done(function () {
                mpwpb_price_calculation(current);
            });
        }
    });
    //==========service============//
    /*$(document).on('click', 'div.mpwpb_registration .incQty', function () {
        let $this = $(this);
        let current = $this.closest('.mpwpb_service_item');
        let current_service_qty = current.attr('data-service-qty');

        let target = current.closest('.qtyIncDec').find('input');
        let currentValue = parseInt(target.val());

    });*/

    $(document).on("click", ".service_decQty, .service_incQty", function () {
        let current = $(this);
        let target = current.closest('.qtyIncDec').find('input');
        let currentValue = parseInt(target.val());
        let value = current.hasClass('service_incQty') ? (currentValue + 1) : ((currentValue - 1) > 0 ? (currentValue - 1) : 0);
        let min = parseInt(target.attr('min'));
        let max = parseInt(target.attr('max'));
        target.parents('.qtyIncDec').find('.service_incQty , .service_decQty').removeClass('mpDisabled');
        if (value < min || isNaN(value) || value === 0) {
            value = min;
            target.parents('.qtyIncDec').find('.service_decQty').addClass('mpDisabled');
        }
        if (value > max) {
            value = max;
            target.parents('.qtyIncDec').find('.service_incQty').addClass('mpDisabled');
        }
        target.val(value).trigger('change').trigger('input');

        let data_current = current.closest('.mpwpb_service_item');
        data_current.attr('data-service-qty', value);

        let curren_service_data= data_current.data('service');
        let summary_cart_id = 'mpwpb_summary_cart_item'+curren_service_data;
        summary_cart_id = $( '#'+summary_cart_id );
        summary_cart_id.find('.mpwpd_cart_service_qty').text('x'+value);

        mpwpb_price_calculation(current);

    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_service_button_remove', function () {

        let $this = $(this);
        let this_current = $this.closest('.mpwpb_summary_item');
        let this_current_service = this_current.data('service');
        let curent_id = 'mpwpb_service_item'+this_current_service;
        let current = $('#'+curent_id);
        let selected_this = current.find('.mpwpb_service_button');
        let inc_dec_holder = current.find('.mpwpb_service_inc_dec_holder');
        current.find('[name="mpwpb_service[]"]').val('');
        current.removeClass('mpActive');
        this_current.slideUp('fast');
        inc_dec_holder.fadeOut();
        mpwpb_price_calculation(current);
        mpwpb_all_content_change(selected_this);

    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_ex_service_button_remove', function () {

        let $this = $(this);
        let this_current = $this.closest('.mpwpb_summary_item');
        let this_current_service = this_current.data('ex_service');
        let curent_id = 'mpwpb_ex_service_item'+this_current_service;
        let current = $('#'+curent_id);
        let selected_this = current.find('.mpwpb_ex_service_button');
        let inc_dec_holder = current.find('.mpwpd_ex_service_qty_inc_dec_holder');

        current.removeClass('mpActive');
        this_current.slideUp('fast');
        inc_dec_holder.fadeOut();
        mpwpb_price_calculation(current);
        mpwpb_all_content_change(selected_this);

    });

    $(document).on('click', 'div.mpwpb_registration .mpwpb_service_button', function () {
        let $this = $(this);

        $('#mpwpd_btn_proceed').fadeOut();

        let is_multiple_service = true;
        if( is_multiple_service ){
            if( $this.hasClass('mActive')){
                $this.siblings('.mpwpb_service_inc_dec_holder').fadeOut();
            }else{
                $this.siblings('.mpwpb_service_inc_dec_holder').fadeIn();
            }
        }

        let current = $this.closest('.mpwpb_service_item');
        let parent = $(this).closest('div.mpwpb_registration');
        let current_category = current.data('category');
        let current_sub_category = current.data('sub-category');
        let current_service = current.data('service');
        if (!current.hasClass('mpActive')) {
            current.find('[name="mpwpb_service[]"]').val(current_service);
            parent.find('.mpwpb_summary_item[data-service]').each(function () {
                mpwpb_load_bg_image();
                let service = $(this).data('service');
                let category = $(this).data('service-category');
                let sub_category = $(this).data('service-sub-category');
                if (service === current_service && category === current_category && sub_category === current_sub_category) {
                    $(this).slideDown('fast');
                    mpwpb_load_bg_image();
                }
            });
            current.addClass('mpActive');
            mpwpb_price_calculation(current);
            let target_extra_service = parent.find('.mpwpb_extra_service_area');
            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.next_service_area').slideDown('fast');
            parent.find('.next_date_area').slideUp('fast');
            if (target_extra_service.length > 0) {
                target_extra_service.slideDown(350);
                mpwpb_load_bg_image();
            }
        } else {
            current.removeClass('mpActive');
            current.find('[name="mpwpb_service[]"]').val('');
            parent.find('.mpwpb_summary_item[data-service]').each(function () {
                let service = $(this).data('service');
                let category = $(this).data('service-category');
                let sub_category = $(this).data('service-sub-category');
                if (service === current_service && category === current_category && sub_category === current_sub_category) {
                    $(this).slideUp('fast');
                }
            });
            mpwpb_price_calculation(current);
        }
        mpwpb_all_content_change($this);
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_service_next', function () {
        $("#mpwpb_progress_date_time").addClass('active');
        let parent = $(this).closest('div.mpwpb_registration');
        let mpwpb_service = {};
        let service_count = 0;
        parent.find('[name="mpwpb_service[]"]').each(function () {
            let service = $(this).val();
            if (service) {
                mpwpb_service[service_count] = service;
                service_count++;
            }
        });

        if (service_count > 0) {
            parent.find('.all_service_area').slideUp(350);
            parent.find('.mpwpb_date_time_tab').addClass('mpActive').removeClass('mpDisabled');
            load_date_time_tab(parent);

            $("#mpwpb_datetime_holder").fadeIn();
            $("#mpwpb_staff_member_holder").fadeIn();
        } else {
            mpwpb_alert($(this));
        }

        $(".mpwpb_service_button_remove").css({ 'display': 'none' });
        $(".mpwpb_ex_service_button_remove").css({ 'display': 'none' });

        $("#mpwpb_staff_member_booking_area").hide();
        // #mpwpb_staff_member_booking_area is only ever rendered server-side
        // (date_time_select.php) when this service has staff selection
        // enabled -- checking for it directly, instead of for staff CARDS
        // already being loaded into #mpwpb_staff_member_holder (which can't
        // happen yet: that AJAX only fires after a date/time is picked,
        // i.e. strictly later than this handler), is what actually reflects
        // "does this service need a staff step" at this point in the flow.
        if ($('#mpwpb_staff_member_booking_area').length > 0) {
            $("#mpwpb_date_time_next_btn_id").hide();
            $("#mpwpb_show_hide_staff_member").fadeIn();
        } else {
            $("#mpwpb_date_time_next_btn_id").fadeIn();
        }


    });
    // Tracked on the registration wrapper so updateSelectedSummary()
    // (mpwpb-booking-tree.js) knows which step is active without needing
    // its own copy of this show/hide logic -- the summary is deliberately
    // hidden on the Service step (the tree above it already shows what's
    // selected) and only shown from Date & Time onward.
    function load_date_time_tab(parent) {
        parent.find('.mpwpb_date_time_area,.next_date_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_order_proceed_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'date_time');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    function load_service_tab(parent) {
        parent.find('.all_service_area,.next_service_area').slideDown(350);
        parent.find('.mpwpb_date_time_area,.mpwpb_order_proceed_area,.next_date_area').slideUp(300);
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'service');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    function load_order_proceed_tab(parent) {
        parent.find('.mpwpb_order_proceed_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_date_time_area,.next_date_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'checkout');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    //==========date============//
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let date = parent.find('[name="mpwpb_date"]').val();
        if (date) {
            let current_date = parent.find('.mpwpb_date_time_area [data-radio-check="' + date + '"]').data('date');
            parent.find('.mpwpb_summary_item[data-date]').slideDown('fast').find('h6').html(current_date);
            parent.find('#mpwpb_staff_selected_datetime').text(current_date);
        } else {
            parent.find('.mpwpb_summary_item[data-date]').slideUp('fast');
            parent.find('#mpwpb_staff_selected_datetime').text('—');
        }
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_next', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let date = parent.find('[name="mpwpb_date"]').val();

        let is_recurring_on = 'off';
        let recurringCount = 1;
        is_recurring_on = $('#mpwpb_enable_recurring_booking').is(':checked');
        if( is_recurring_on ){
            recurringCount = parseInt(parent.find('#mpwpb_recurring_count_hidden').val());
            is_recurring_on = 'on';
        }
        let dateTimes = [];
        $('#mpwpb_recurring_dates_list li').each(function () {
            var dateTime = $(this).data('date-time');
            if (dateTime) {
                dateTime = dateTime.substring(0, 16);
                dateTimes.push(dateTime);
            }
        });

        if (dateTimes.length === 0) {
            dateTimes.push(date);
        }
        var dateTimeString = dateTimes.join(',');

        if (date) {

            $("#mpwpb_progress_checkout").addClass('active');

            let link_id = $(this).attr('data-wc_link_id');
            let mpwpb_category = parent.find('[name="mpwpb_category"]').val();
            mpwpb_category = mpwpb_category ? parseInt(mpwpb_category) : '';
            let mpwpb_sub_category = parent.find('[name="mpwpb_sub_category"]').val();
            mpwpb_sub_category = mpwpb_sub_category ? parseInt(mpwpb_sub_category) : '';
            let mpwpb_service = {};
            let mpwpb_service_qty = {};
            let service_count = 0;

            let staff_member = parent.find('[name="mpwpb_staff_member_booking"]').val();

            parent.find('[name="mpwpb_service[]"]').each(function () {
                let service = $(this).val();
                if (service) {
                    mpwpb_service[service_count] = parseInt(service);

                    let ex_parent = $(this).closest('.mpwpb_service_item');
                    let ex_qty = parseInt(ex_parent.find('[name="mpwpb_service_qtt[]"]').val());
                    ex_qty = ex_qty > 0 ? ex_qty : 1;
                    mpwpb_service_qty[service] = ex_qty;

                    service_count++;
                }
            });
            let mpwpb_extra_service = {};
            let mpwpb_extra_service_type = {};
            let mpwpb_extra_service_qty = {};
            let count = 0;
            parent.find('[name="mpwpb_extra_service_type[]"]').each(function () {
                let ex_name = $(this).val();
                if (ex_name) {
                    let ex_parent = $(this).closest('.mpwpb_extra_service_item');
                    mpwpb_extra_service[count] = ex_parent.find('[name="mpwpb_extra_service[]"]').val();
                    mpwpb_extra_service_type[count] = ex_name;
                    let ex_qty = parseInt(ex_parent.find('[name="mpwpb_extra_service_qty[]"]').val());
                    ex_qty = ex_qty > 0 ? ex_qty : 1;
                    mpwpb_extra_service_qty[count] = ex_qty;
                    count++;
                }
            });
            var isCustomPaymentMode = !!mpwpb_ajax.is_custom_payment_mode;
            $.ajax({
                type: 'POST',
                url: mpwpb_ajax.ajax_url,
                data: {
                    "action": "mpwpb_add_to_cart",
                    //"product_id": post_id,
                    "link_id": link_id,
                    "mpwpb_category": mpwpb_category,
                    "mpwpb_sub_category": mpwpb_sub_category,
                    "mpwpb_service": mpwpb_service,
                    "mpwpb_service_qty": mpwpb_service_qty,
                    "mpwpb_date": dateTimeString,
                    "recurringCount": recurringCount,
                    "is_recurring_on": is_recurring_on,
                    "mpwpb_extra_service": mpwpb_extra_service,
                    "mpwpb_extra_service_type": mpwpb_extra_service_type,
                    "mpwpb_extra_service_qty": mpwpb_extra_service_qty,
                    "mpwpb_staff_member": staff_member,
                    nonce: mpwpb_ajax.nonce
                },
                beforeSend: function () {
                    if (isCustomPaymentMode) {
                        // Custom Payment stays in the same popup afterwards (see the
                        // success handler below), so ease straight into the Checkout
                        // step now -- the same smooth slide every other step change
                        // uses -- instead of freezing the whole screen behind the
                        // full-page loader while mpwpb_add_to_cart (and the native
                        // billing form fetch that follows it) run in the background.
                        // A small local spinner in that step's own area is enough.
                        // It's still empty at this point though (the fetched form
                        // hasn't arrived yet), so without a floor height the spinner
                        // has almost nothing to center inside and ends up pinned to
                        // the top instead of looking centered on the page -- the
                        // temporary min-height is cleared again once real content
                        // replaces it (mpwpb_load_native_checkout_form() below).
                        load_order_proceed_tab(parent);
                        parent.find('.mpwpb_order_proceed_area').css('min-height', '260px');
                        mpwpb_loader(parent.find('.mpwpb_order_proceed_area'));
                    } else {
                        // WooCommerce mode navigates away entirely on success, so
                        // there's no "next step" to ease into -- the full-page
                        // overlay (mpwpb_loaderBody, position:fixed on <body>) is
                        // appropriate here.
                        mpwpb_loaderBody();
                    }
                },
                success: function (data) {
                    // Custom Payment (WooCommerce off): stay in the same popup and
                    // load the native billing form into the "Checkout" step instead
                    // of navigating away -- mpwpb_add_to_cart still returns a URL
                    // string here (unchanged contract), it's just not used in this
                    // mode; the cart item it already stored server-side is what the
                    // fetched form reads.
                    if (isCustomPaymentMode) {
                        mpwpb_load_native_checkout_form(parent);
                    } else {
                        window.location.href = data;
                    }
                },
                error: function () {
                    if (isCustomPaymentMode) {
                        parent.find('.mpwpb_order_proceed_area').css('min-height', '');
                        mpwpb_loaderRemove(parent.find('.mpwpb_order_proceed_area'));
                    } else {
                        mpwpb_loaderRemove();
                    }
                }
            });
        } else {
            mpwpb_alert($(this));
        }
    });
    // GDPR "remember my info" cookie (Frontend/MPWPB_Gdpr_Cookie_Banner.php) --
    // only ever read/written when the visitor has explicitly accepted the
    // cookie banner (window.mpwpbGdprConsentAccepted(), exposed by
    // mpwpb-gdpr-cookie-banner.js -- only enqueued at all when the GDPR
    // feature is on, hence the typeof guard everywhere it's used below).
    function mpwpb_gdpr_get_cookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }
    function mpwpb_gdpr_set_cookie(name, value, days) {
        var expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
    }
    function mpwpb_gdpr_prefill_billing($scope) {
        if (typeof window.mpwpbGdprConsentAccepted !== 'function' || !window.mpwpbGdprConsentAccepted()) {
            return;
        }
        var raw = mpwpb_gdpr_get_cookie('mpwpb_customer_info');
        if (!raw) {
            return;
        }
        var info;
        try {
            info = JSON.parse(raw);
        } catch (e) {
            return;
        }
        var fieldMap = {
            first_name: 'mpwpb_billing_first_name',
            last_name: 'mpwpb_billing_last_name',
            email: 'mpwpb_billing_email',
            phone: 'mpwpb_billing_phone',
            address_1: 'mpwpb_billing_address_1'
        };
        $.each(fieldMap, function (infoKey, fieldName) {
            var $field = $scope.find('[name="' + fieldName + '"]');
            if ($field.length && !$field.val() && info[infoKey]) {
                $field.val(info[infoKey]);
            }
        });
    }
    function mpwpb_gdpr_save_billing_cookie($form) {
        if (typeof window.mpwpbGdprConsentAccepted !== 'function' || !window.mpwpbGdprConsentAccepted()) {
            return;
        }
        var info = {
            first_name: $form.find('[name="mpwpb_billing_first_name"]').val() || '',
            last_name: $form.find('[name="mpwpb_billing_last_name"]').val() || '',
            email: $form.find('[name="mpwpb_billing_email"]').val() || '',
            phone: $form.find('[name="mpwpb_billing_phone"]').val() || '',
            address_1: $form.find('[name="mpwpb_billing_address_1"]').val() || ''
        };
        mpwpb_gdpr_set_cookie('mpwpb_customer_info', JSON.stringify(info), 30);
    }
    function mpwpb_load_native_checkout_form(parent) {
        var $target = parent.find('.mpwpb_order_proceed_area');
        $.ajax({
            type: 'POST',
            url: mpwpb_ajax.ajax_url,
            data: {
                action: 'mpwpb_native_checkout_form',
                nonce: mpwpb_ajax.nonce
            },
            success: function (response) {
                mpwpb_loaderRemove();
                $target.css('min-height', '');
                if (response && response.success) {
                    $target.html(response.data.html);
                    mpwpb_gdpr_prefill_billing($target);
                    load_order_proceed_tab(parent);
                } else {
                    $target.html('<p class="mpwpb-checkout-error">' + ((response && response.data && response.data.message) ? response.data.message : 'Something went wrong.') + '</p>');
                    load_order_proceed_tab(parent);
                }
            },
            error: function () {
                mpwpb_loaderRemove();
                $target.css('min-height', '');
                $target.html('<p class="mpwpb-checkout-error">Request failed. Please try again.</p>');
                load_order_proceed_tab(parent);
            }
        });
    }
    $(document).on('submit', 'div.mpwpb_registration .mpwpb-checkout-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var parent = $form.closest('div.mpwpb_registration');
        var $btn = $form.find('.mpwpb-checkout-submit');
        var $error = $form.siblings('.mpwpb-checkout-error');
        $error.hide().text('');
        $btn.prop('disabled', true);
        $.post(mpwpb_ajax.ajax_url, $form.serialize()).done(function (response) {
            if (response && response.success && response.data && response.data.redirect) {
                mpwpb_gdpr_save_billing_cookie($form);
                window.location.href = response.data.redirect;
            } else {
                $btn.prop('disabled', false);
                $error.text((response && response.data && response.data.message) ? response.data.message : 'Something went wrong.').show();
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            $error.text('Request failed. Please try again.').show();
        });
    });
    $(document).on('click', 'div.mpwpb_registration [data-checkout-back-to-date]', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        $("#mpwpb_progress_checkout").removeClass('active');
        load_date_time_tab(parent);
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_prev', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        parent.find('.mpwpb_service_tab').addClass('mpActive').removeClass('mpDisabled');
        load_service_tab(parent);
        $(".mpwpb_service_button_remove").css({ 'display': 'flex' });
        $(".mpwpb_ex_service_button_remove").css({ 'display': 'flex' });

        $("#mpwpb_progress_date_time").removeClass('active');
        $("#mpwpb_progress_staff").removeClass('active');
        $("#mpwpb_progress_checkout").removeClass('active');
    });
    //========Extra service==============//
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_extra_service_qty[]"]', function () {
        $(this).closest('.mpwpb_extra_service_item').find('[name="mpwpb_extra_service_type[]"]').trigger('change');
    });
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_extra_service_type[]"]', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let service_name = $(this).data('value');
        let service_value = $(this).val();
        if (service_value) {
            let qty = $(this).closest('.mpwpb_extra_service_item').find('[name="mpwpb_extra_service_qty[]"]').val();
            parent.find('[data-extra-service="' + service_name + '"]').slideDown(350).find('.ex_service_qty').html('x' + qty);
        } else {
            parent.find('[data-extra-service="' + service_name + '"]').slideUp(350);
        }
        mpwpb_price_calculation($(this));
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_price_calculation', function () {
        mpwpb_price_calculation($(this));
    });

    $(document).on('click', '#mpwpb_display_date_time', function () {
        $(this).hide();
        $("#mpwpb_staff_member_booking_area").hide();
        $("#mpwpb_date_time_next_btn_id").hide();
        $("#mpwpb_show_hide_staff_member").fadeIn();
        $("#mpwpb_datetime_holder").fadeIn();
        $("#mpwpb_display_service_btn").fadeIn();

        $("#mpwpb_progress_staff").removeClass('active');
        $("#mpwpb_progress_checkout").removeClass('active');
    });

    $(document).on('click', '#mpwpb_show_hide_staff_member', function () {
        $(this).hide();
        $("#mpwpb_date_time_next_btn_id").fadeIn();
        $("#mpwpb_progress_staff").addClass('active');
        $("#mpwpb_datetime_holder").hide();
        $("#mpwpb_staff_member_booking_area").fadeIn();

        $("#mpwpb_display_service_btn").hide();
        $("#mpwpb_display_date_time").fadeIn();

        // Mirror the running total into the staff step's own summary --
        // #mpwpd_all_total_bill is the single element mpwpb_price_calculation()
        // already keeps up to date, this just copies its current text.
        let parent = $(this).closest('div.mpwpb_registration');
        parent.find('#mpwpb_staff_selected_total').text(parent.find('#mpwpd_all_total_bill').first().text());
    });

    $(document).on('click', '.mpwpb_get_date', function () {
        let parent = $(this).closest('div.mpwpb_registration');

        $(".mpwpb_get_date").removeClass('mpwpb_get_date_selected');
        $(this).addClass('mpwpb_get_date_selected');

        let selectedDate = $(this).attr('data-find-time');

        // Hide all date sections first
        $('.mpwpb_time_display').hide();

        // Show only the one that matches the selected date
        $('.mpwpb_time_display[data-date-filder="' + selectedDate + '"]').fadeIn();
    });

    $(document).on('click', '.mpwp_select_staff_card', function () {
        $('.mpwp_select_staff_card').removeClass('selected');
        $(this).addClass('selected');
        let staffId = $(this).find('.mpwpb_selected_staff').val();

        $("#mpwpb_staff_member_booking").val( staffId );

        // Only one progress circle should read as "active" at a time --
        // #mpwpb_progress_staff was turned on when this step was entered
        // (#mpwpb_show_hide_staff_member click handler) and never turned
        // back off, so picking a staff member used to leave both Staff and
        // Checkout marked active simultaneously.
        $("#mpwpb_progress_staff").removeClass('active');
        $("#mpwpb_progress_checkout").addClass('active');
    });

    $(document).on('click', '#mpwpb_mobile_booking_mobile', function () {
        $(this).hide();
        $("#mpwpb_make_static_booking").fadeIn();
    });

    $(document).on('click', '#mpwpb_static_registration_popup_close', function () {
        $("#mpwpb_make_static_booking").fadeOut();
        $("#mpwpb_mobile_booking_mobile").fadeIn();
    });


    $(document).ready(function () {
        $('.faq-header').on('click', function () {
            $(this).next('.faq-content').slideToggle();
            $(this).find('i').toggleClass('fa-plus fa-minus');
        });
    });

    $(document).on('click', '.mpwpb-details-page-tab li a', function (e) {
        $('.mpwpb-details-page-tab li').removeClass('active');
        $(this).closest('li').addClass('active');
        $('.mpwpb-details-page-content .tab-content').removeClass('active');
        $(tabId).addClass('active');
    });

}(jQuery));

