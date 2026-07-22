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

    // Happy Hours: mirrors MPWPB_Happy_Hours_Helper on the server (the real
    // charge is already discounted there via the selected slot's time) --
    // this just reflects the same discount here instantly, so the customer
    // isn't stuck watching an undiscounted total until they reach checkout.
    // Only the base service price above is discounted, never extra services
    // added below, matching that same server-side rule exactly.
    let $selectedSlot = parent.find('.mpwpb_date_time_area .mpwpb_time_btn.mpwpb_active_time');
    if ($selectedSlot.length) {
        let hhType = $selectedSlot.attr('data-hh-type');
        let hhValue = parseFloat($selectedSlot.attr('data-hh-value'));
        if (hhType && !isNaN(hhValue) && hhValue > 0) {
            if (hhType === 'fixed') {
                price = Math.max(0, price - hhValue);
            } else {
                price = Math.max(0, price - (price * (hhValue / 100)));
            }
        }
    }

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
    // "Reorder" from a past booking (Frontend/MPWPB_User_Dashboard.php's
    // Reorder link -> ?mpwpb_reorder={booking_id} -> MPWPB_Static_Template::
    // get_reorder_prefill() -> templates/registration/static_registration.php
    // inline-scripts mpwpbReorderPrefill). The old implementation clicked the
    // sidebar leaf nodes during DOM ready. That was fragile because the popup's
    // booking tree is initialized by a later footer script and, for old/backend
    // orders, the booking CPT may only contain extra-service data. Apply the
    // saved state to the real popup inputs/buttons after every ready callback
    // has finished, explicitly open the modal, and then move valid reorders to
    // the fresh Date & Time step.
    //
    // This file is enqueued without in_footer (loads in <head>), so the
    // service tree markup this needs to find doesn't exist yet at the point
    // this script tag executes -- wrapped in its own ready handler (safe to
    // nest regardless of whether the surrounding code above is already
    // inside one) rather than assumed to run after the DOM is built.
    $(function () {
        if (typeof mpwpbReorderPrefill === 'undefined' || !mpwpbReorderPrefill) {
            return;
        }

        window.setTimeout(function () {
            var $registration = $('div.mpwpb_registration').filter(function () {
                return $(this).find('.mpwpb_static, [data-popup="#mpwpb_static_popup"]').length > 0;
            }).first();
            if (!$registration.length || $registration.data('mpwpbReorderApplied')) {
                return;
            }
            $registration.data('mpwpbReorderApplied', true);

            // Open even when the historical base service is unavailable. A
            // reorder must always enter the booking flow, never appear to be a
            // link that merely reloads the service page.
            var $openButton = $registration.find('.mpwpb-tree-cta-btn').first();
            if ($openButton.length) {
                $openButton.trigger('click');
            } else {
                $('body').addClass('noScroll');
                $registration.find('[data-popup="#mpwpb_static_popup"]').addClass('in');
            }

            var $noticeHost = $registration.find('[data-popup="#mpwpb_static_popup"] .mpwpb_popup_header').first();
            if ($noticeHost.length && mpwpbReorderPrefill.notice) {
                var $reorderNotice = $(
                    '<div class="mpwpb-reorder-notice" role="status" aria-live="polite">' +
                        '<span class="mpwpb-reorder-notice-icon" aria-hidden="true"><i class="fas fa-exclamation"></i></span>' +
                        '<span class="mpwpb-reorder-notice-content">' +
                            '<strong></strong><span class="mpwpb-reorder-notice-message"></span>' +
                        '</span>' +
                    '</div>'
                );
                $reorderNotice.find('strong').text(mpwpbReorderPrefill.notice_title || 'Reorder booking');
                $reorderNotice.find('.mpwpb-reorder-notice-message').text(mpwpbReorderPrefill.notice);
                $reorderNotice.insertAfter($noticeHost);
            }

            // A booking can contain more than one service. Select every live
            // match and restore its quantity before price calculation runs.
            if ($.isArray(mpwpbReorderPrefill.service_keys)) {
                $.each(mpwpbReorderPrefill.service_keys, function (i, key) {
                    var $item = $registration.find('.mpwpb_service_item[data-service="' + key + '"]').first();
                    if (!$item.length || $item.hasClass('mpActive')) {
                        return;
                    }
                    var qty = parseInt(mpwpbReorderPrefill.service_quantities && mpwpbReorderPrefill.service_quantities[key], 10) || 1;
                    $item.attr('data-service-qty', qty).find('[name="mpwpb_service_qtt[]"]').val(qty);
                    $item.find('.mpwpb_service_button').first().trigger('click');
                });
            }

            if ($.isArray(mpwpbReorderPrefill.extra_services)) {
                $.each(mpwpbReorderPrefill.extra_services, function (i, extra) {
                    $registration.find('.mpwpb_extra_service_item').each(function () {
                        var $item = $(this);
                        var $type = $item.find('[name="mpwpb_extra_service_type[]"]');
                        if ($type.data('value') !== extra.name || $type.val()) {
                            return;
                        }
                        $item.find('[name="mpwpb_extra_service_qty[]"]').val(parseInt(extra.qty, 10) || 1);
                        $item.find('.mpwpb_ex_service_button').first().trigger('click');
                    });
                });
            }

            if (mpwpbReorderPrefill.staff_id) {
                $registration.find('[name="mpwpb_staff_member_booking"]').val(mpwpbReorderPrefill.staff_id);
            }

            if (mpwpbReorderPrefill.advance_to_schedule && $registration.find('[name="mpwpb_service[]"]').filter(function () {
                return !!$(this).val();
            }).length) {
                $registration.find('.mpwpb_service_next').first().trigger('click');
            }
        }, 0);
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
        // Progress is set by load_date_time_tab() below (only when a service is
        // actually selected) via mpwpb_set_progress() -- not here, or it would
        // advance the bar even when the "select a service" alert fires.
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
    // Single source of truth for the top step bar. Given the current step key
    // (service | date_time | staff | checkout | confirmation) it marks every
    // segment before the current one as completed (.is-done), the current one
    // .active (the only one that shows its caption -- see
    // mpwpb-service-page-modern.css), and clears the rest. Centralising this
    // fixes the old scattered add/removeClass calls that referenced step ids
    // which weren't always rendered (e.g. #mpwpb_progress_checkout), leaving
    // the bar stuck showing earlier captions with no active segment.
    function mpwpb_set_progress(parent, key) {
        var $steps = parent.find('.mpwpb_cart_progress_step');
        if (!$steps.length) { return; }
        var $current = parent.find('#mpwpb_progress_' + key);
        if (!$current.length) { return; }
        var currentIndex = $steps.index($current);
        $steps.each(function (i) {
            $(this).removeClass('active is-done')
                .addClass(i < currentIndex ? 'is-done' : (i === currentIndex ? 'active' : ''));
        });
    }
    function load_date_time_tab(parent) {
        parent.find('.mpwpb_date_time_area,.next_date_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_order_proceed_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'date_time');
        mpwpb_set_progress(parent, 'date_time');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    function load_service_tab(parent) {
        parent.find('.all_service_area,.next_service_area').slideDown(350);
        parent.find('.mpwpb_date_time_area,.mpwpb_order_proceed_area,.next_date_area').slideUp(300);
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'service');
        mpwpb_set_progress(parent, 'service');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    function load_order_proceed_tab(parent) {
        parent.find('.mpwpb_order_proceed_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_date_time_area,.next_date_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
        parent.data('mpwpbStep', 'checkout');
        mpwpb_set_progress(parent, 'checkout');
        if (typeof updateSelectedSummary === 'function') { updateSelectedSummary(parent); }
    }
    //==========date============//
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let date = parent.find('[name="mpwpb_date"]').val();
        // mpwpb_recurring_booking.js's displayRecurringDates() also writes into
        // this same #mpwpd_selected_date <h6> (it lists every occurrence, not
        // just one) -- when recurring has generated more than one date, leave
        // its content alone instead of clobbering it back down to a single date.
        let recurringDatesCount = parent.find('#mpwpb_recurring_dates_list li[data-date-time!=""]').length;
        if (date) {
            parent.find('.mpwpb_summary_item[data-date]').slideDown('fast');
            if (recurringDatesCount <= 1) {
                let current_date = parent.find('.mpwpb_date_time_area [data-radio-check="' + date + '"]').data('date');
                parent.find('.mpwpb_summary_item[data-date]').find('h6').html(current_date);
                parent.find('#mpwpb_staff_selected_datetime').text(current_date);
            }
        } else {
            parent.find('.mpwpb_summary_item[data-date]').slideUp('fast');
            parent.find('#mpwpb_staff_selected_datetime').text('—');
        }
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_next', function () {
        let $nextBtn = $(this);
        let parent = $nextBtn.closest('div.mpwpb_registration');
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

            // No payment method configured (no WooCommerce gateway, no native
            // payment set up) -- there's nothing to complete the booking against,
            // so stop here with a clear message instead of taking the customer
            // into an unusable checkout. The server enforces the same block.
            if (!mpwpb_ajax.has_payment_method) {
                mpwpb_set_progress(parent, 'checkout');
                load_order_proceed_tab(parent);
                var isBookingAdmin = !!mpwpb_ajax.is_booking_admin;
                // Customers only ever see the short friendly line -- never the
                // admin/technical wording. Admins additionally get a button
                // straight to the payment settings so they can fix it in place.
                var noPayMsg = isBookingAdmin ? mpwpb_ajax.no_payment_admin : mpwpb_ajax.no_payment_customer;
                var noPayHtml = '<div class="mpwpb-checkout-embed"><div class="mpwpb-checkout-nopay' +
                    (isBookingAdmin ? ' mpwpb-checkout-nopay--admin' : '') + '">' +
                    '<i class="fas fa-' + (isBookingAdmin ? 'exclamation-circle' : 'clock') + '"></i>' +
                    '<p>' + $('<div>').text(noPayMsg).html() + '</p>';
                if (isBookingAdmin && mpwpb_ajax.payment_settings_url) {
                    noPayHtml += '<a class="mpwpb-checkout-nopay-btn" href="' + mpwpb_ajax.payment_settings_url + '" target="_blank" rel="noopener">' +
                        '<i class="fas fa-cog"></i> ' + $('<div>').text(mpwpb_ajax.payment_settings_label || 'Go to Payment Settings').html() + '</a>';
                }
                noPayHtml += '</div></div>';
                parent.find('.mpwpb_order_proceed_area').html(noPayHtml);
                return;
            }

            mpwpb_set_progress(parent, 'checkout');

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
            // "Native" = every non-WooCommerce checkout (offline / Stripe /
            // PayPal, and whether or not Pro is active). The server already
            // routes all non-WC bookings through MPWPB_Native_Checkout, so the
            // drawer must render that checkout inline for all of them -- not
            // only the Pro "custom" mode this was originally gated to
            // (is_custom_payment_mode), which left free/native setups (WooCommerce
            // off, Pro off) falling through to the full-page redirect below.
            var isNativeMode = !mpwpb_ajax.is_wc_payment_mode;
            var isInlineWcMode = !!mpwpb_ajax.is_wc_payment_mode;
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
                    // Full/Partial is now chosen on the checkout page itself (see
                    // MPWPB_Partial_Payment::render_choice_radio()), not here --
                    // always add to cart as Full; the checkout-page toggle updates
                    // the cart/native-cart item's mpwpb_payment_choice afterwards.
                    "mpwpb_payment_choice": 'full',
                    nonce: mpwpb_ajax.nonce
                },
                beforeSend: function () {
                    // Button-level feedback in addition to the step-loader/full-page
                    // overlay below -- add_to_cart (plus, in Custom mode, the native
                    // billing form fetch that follows it) takes a couple of seconds,
                    // and without this the button just sits there looking clickable/
                    // unresponsive, which reads as broken rather than "working".
                    $nextBtn.data('mpwpb-original-html', $nextBtn.html());
                    $nextBtn.prop('disabled', true).addClass('mpwpb-cta-loading')
                        .html('<i class="fas fa-spinner fa-spin _mR_xs"></i> ' + (mpwpb_ajax.processing_text || 'Please wait...'));
                    if (isNativeMode || isInlineWcMode) {
                        // Native/WC checkout stays in the same popup afterwards (see
                        // the success handler below), so ease straight into the
                        // Checkout step now -- the same smooth slide every other step change
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
                    // Native checkout (WooCommerce off -- offline/Stripe/PayPal,
                    // Pro or free): stay in the same popup and load the native
                    // billing form into the "Checkout" step instead of navigating
                    // away -- mpwpb_add_to_cart still returns a URL string here
                    // (unchanged contract), it's just not used in this mode; the
                    // cart item it already stored server-side is what the fetched
                    // form reads.
                    if (isNativeMode) {
                        mpwpb_load_native_checkout_form(parent);
                    } else if (isInlineWcMode) {
						if (typeof data === 'string' && data.indexOf('wp-login.php') !== -1) {
							window.location.href = data;
							return;
						}
                        mpwpb_load_wc_checkout_form(parent);
                    } else {
                        window.location.href = data;
                    }
                },
                error: function (xhr) {
                    let message = mpwpb_ajax.booking_error || 'The booking could not be completed. Please review your selection and try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    if (isInlineWcMode) {
						var $inlineContent = parent.find('.mpwpb-inline-wc-checkout-content');
						parent.find('.mpwpb-inline-confirmation-host').remove();
						$inlineContent.html('<p class="mpwpb-checkout-error" role="alert">' + $('<div>').text(message).html() + '</p>');
						parent.find('form.mpwpb-inline-wc-checkout-form').show();
					} else {
						alert(message);
					}
                    if (isNativeMode || isInlineWcMode) {
                        parent.find('.mpwpb_order_proceed_area').css('min-height', '');
                        mpwpb_loaderRemove(parent.find('.mpwpb_order_proceed_area'));
                    } else {
                        mpwpb_loaderRemove();
                    }
                },
                complete: function () {
                    // Runs after success or error alike -- on WC-mode success the
                    // page is navigating away anyway so this is moot, but on error
                    // (either mode) or Custom-mode success (same popup, customer
                    // could still go back a step) the button must not stay stuck
                    // disabled/spinning.
                    $nextBtn.prop('disabled', false).removeClass('mpwpb-cta-loading');
                    if ($nextBtn.data('mpwpb-original-html') !== undefined) {
                        $nextBtn.html($nextBtn.data('mpwpb-original-html'));
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

    function mpwpb_inline_notice(parent, message) {
        var $notice = parent.find('.mpwpb-inline-checkout-notices');
        $notice.html('<div class="woocommerce-error" role="alert">' + $('<div>').text(message).html() + '</div>');
        var $body = parent.closest('.mpwpb_popup').find('.mpwpb_popup_body');
        if ($body.length) {
            $body.animate({scrollTop: Math.max(0, $notice.position().top - 20)}, 250);
        }
    }

    function mpwpb_bind_inline_wc_success($form) {
        $form.off('checkout_place_order_success.mpwpbInline').on('checkout_place_order_success.mpwpbInline', function (event, result) {
            var redirect;
            try {
                redirect = new URL(result.redirect, window.location.href);
            } catch (e) {
                return true;
            }
            if (redirect.origin !== window.location.origin) {
                return true;
            }
            var orderId = parseInt(redirect.searchParams.get('mpwpb_inline_order'), 10) || 0;
            var orderKey = redirect.searchParams.get('key') || '';
            if (!orderId) {
                var match = redirect.pathname.match(/order-received\/(\d+)/);
                orderId = match ? parseInt(match[1], 10) : 0;
            }
            if (!orderId || !orderKey) {
                return true;
            }
            mpwpb_show_wc_confirmation($(this).closest('div.mpwpb_registration'), orderId, orderKey);
            return false;
        });
    }

    function mpwpb_reset_inline_wc_submission($form) {
        $form.data('mpwpbFallbackSubmitting', false).removeClass('processing').attr('aria-busy', 'false');
        $form.find('#place_order').prop('disabled', false);
        if (typeof $form.unblock === 'function') {
            $form.unblock();
        }
    }

    function mpwpb_inline_wc_result_message(result) {
        if (result && result.messages) {
            var text = $('<div>').html(result.messages).text().replace(/\s+/g, ' ').trim();
            if (text) {
                return text;
            }
        }
        return (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.i18n_checkout_error)
            ? wc_checkout_params.i18n_checkout_error
            : 'The order could not be processed. Please review your details and try again.';
    }

    function mpwpb_load_wc_checkout_form(parent) {
        var $target = parent.find('.mpwpb_order_proceed_area');
        var $form = $target.find('form.mpwpb-inline-wc-checkout-form');
        var serviceId = parseInt(parent.attr('data-post-id'), 10) || 0;
        if ($target.data('mpwpbCheckoutLoading')) {
            return;
        }
        if (!$form.length) {
			// Recover stale cached markup in place. WooCommerce checkout uses
			// delegated form handlers, so a safe wrapper can be created without
			// forcing a disruptive full-page reload.
			$form = $('<form>', {
				name: 'checkout',
				method: 'post',
				'class': 'checkout woocommerce-checkout mpwpb-inline-wc-checkout-form',
				action: (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.checkout_url) ? wc_checkout_params.checkout_url : window.location.href,
				enctype: 'multipart/form-data',
				'aria-label': 'Booking checkout'
			}).append('<div class="mpwpb-inline-wc-checkout-content"></div>');
			$target.empty().append($form);
        }
        mpwpb_bind_inline_wc_success($form);
		$target.find('.mpwpb-inline-confirmation-host').remove();
		$form.show().removeClass('processing').attr('aria-busy', 'true');
        $target.data('mpwpbCheckoutLoading', true);
        load_order_proceed_tab(parent);
        $target.css('min-height', '260px');
        mpwpb_loader($target);
        var checkoutFinished = false;
		$form.find('.mpwpb-inline-wc-checkout-content').html('<p class="mpwpb-checkout-preparing" role="status">Securely preparing your WooCommerce checkout&hellip;</p>');

        function showCheckoutRecovery(message) {
            checkoutFinished = true;
            var safeMessage = $('<div>').text(message || 'We could not connect to checkout. Your booking selection is still safe.').html();
            $form.find('.mpwpb-inline-wc-checkout-content').html(
                '<div class="mpwpb-checkout-recovery" role="alert">' +
                    '<span class="fas fa-wifi" aria-hidden="true"></span>' +
                    '<div><strong>Checkout is taking longer than expected</strong><p>' + safeMessage + '</p></div>' +
                    '<button type="button" class="button mpwpb-inline-retry-checkout">Try Again</button>' +
                '</div>'
            );
            $form.show().attr('aria-busy', 'false');
        }

        function requestCheckout(attempt) {
            $.ajax({
                type: 'POST',
                url: mpwpb_ajax.ajax_url,
                dataType: 'json',
                timeout: 12000,
                data: {
                    action: 'mpwpb_inline_wc_checkout',
                    nonce: mpwpb_ajax.nonce,
                    service_id: serviceId,
                    source_url: window.location.href.split('#')[0]
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    if (attempt < 2) {
                        window.setTimeout(function () { requestCheckout(attempt + 1); }, 450 * (attempt + 1));
                        return;
                    }
                    showCheckoutRecovery(response && response.data && response.data.message ? response.data.message : 'Please try connecting again.');
                    return;
                }
                checkoutFinished = true;
                $form.find('.mpwpb-inline-wc-checkout-content').html(response.data.html);
                $form.show().attr('aria-busy', 'false');
                mpwpb_set_progress(parent, 'checkout');
                parent.find('.popupFooter').addClass('mpwpb-inline-checkout-footer-hidden');
                $(document.body).trigger('init_checkout');
                $(document.body).trigger('payment_method_selected');
                $form.find('input, select, textarea').filter(':visible').first().trigger('focus');
            }).fail(function (xhr) {
                if (attempt < 2) {
                    window.setTimeout(function () { requestCheckout(attempt + 1); }, 450 * (attempt + 1));
                    return;
                }
                var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Please check your connection and try again. Your booking selection has not been lost.';
                showCheckoutRecovery(message);
            }).always(function () {
                if (checkoutFinished) {
                    $target.data('mpwpbCheckoutLoading', false).css('min-height', '');
                    mpwpb_loaderRemove($target);
                }
            });
        }

        requestCheckout(0);
    }

    $(document).on('click', '.mpwpb-inline-retry-checkout', function () {
        var $parent = $(this).closest('div.mpwpb_registration');
        $parent.find('.mpwpb-inline-wc-checkout-content').empty();
        mpwpb_load_wc_checkout_form($parent);
    });

    function mpwpb_show_wc_confirmation(parent, orderId, orderKey) {
        var $target = parent.find('.mpwpb_order_proceed_area');
		var $form = $target.find('form.mpwpb-inline-wc-checkout-form');
		var $confirmation = $target.find('.mpwpb-inline-confirmation-host');
		if (!$confirmation.length) {
			$confirmation = $('<div class="mpwpb-inline-confirmation-host"></div>').appendTo($target);
		}
        if (!orderId || !orderKey || $target.data('confirmationLoading')) {
            return;
        }
        $target.data('confirmationLoading', true).attr('aria-busy', 'true');
        mpwpb_loader($target);
        $.ajax({
            type: 'POST',
            url: mpwpb_ajax.ajax_url,
            dataType: 'json',
            data: {
                action: 'mpwpb_inline_wc_confirmation',
                nonce: mpwpb_ajax.nonce,
                order_id: orderId,
                order_key: orderKey
            }
        }).done(function (response) {
            if (!response || !response.success) {
                mpwpb_inline_notice(parent, response && response.data && response.data.message ? response.data.message : 'Confirmation could not be loaded.');
                return;
            }
			$form.hide().removeClass('processing').attr('aria-busy', 'false');
			$confirmation.html(response.data.html).show();
			$target.attr('aria-busy', 'false');
            parent.data('mpwpbStep', 'confirmation');
            parent.find('.popupFooter').addClass('mpwpb-inline-checkout-footer-hidden');
            mpwpb_set_progress(parent, 'confirmation');
            var cleanUrl = window.location.href.replace(/([?&])(mpwpb_inline_order|key)=[^&#]*/g, '$1').replace(/[?&]$/, '');
            window.history.replaceState({}, document.title, cleanUrl);
			$confirmation.find('h2, .woocommerce-order-overview').first().attr('tabindex', '-1').trigger('focus');
        }).always(function () {
            $target.data('confirmationLoading', false);
            mpwpb_loaderRemove($target);
        });
    }

    $(document).on('click', '.mpwpb-inline-continue-payment', function () {
        var $form = $(this).closest('form.checkout');
        var $parent = $form.closest('div.mpwpb_registration');
        $form.find('[data-inline-stage="billing"] .input-text:visible, [data-inline-stage="billing"] select:visible, [data-inline-stage="billing"] input:checkbox:visible').trigger('validate');
        var $invalid = $form.find('[data-inline-stage="billing"] .woocommerce-invalid:visible').first();
        if ($invalid.length) {
            mpwpb_inline_notice($parent, 'Please complete all required billing fields.');
            $invalid.find('input, select, textarea').first().trigger('focus');
            return;
        }
        $form.find('[data-inline-stage="billing"]').prop('hidden', true);
        $form.find('[data-inline-stage="payment"]').prop('hidden', false);
        mpwpb_set_progress($parent, 'checkout');
        $(document.body).trigger('update_checkout');
        $form.find('[data-inline-stage="payment"] h3').first().attr('tabindex', '-1').trigger('focus');
    });

    $(document).on('click', '.mpwpb-inline-back-to-billing', function () {
        var $form = $(this).closest('form.checkout');
        $form.find('[data-inline-stage="payment"]').prop('hidden', true);
        $form.find('[data-inline-stage="billing"]').prop('hidden', false);
        $form.find('[data-inline-stage="billing"] input:visible').first().trigger('focus');
    });

    $(document).on('click', '.mpwpb-inline-apply-coupon', function () {
        var $button = $(this);
        var $form = $button.closest('form.checkout');
        var $field = $form.find('#mpwpb_inline_coupon_code');
        var $message = $form.find('.mpwpb-inline-coupon-message');
        var code = $.trim($field.val());
        if (!code || typeof wc_checkout_params === 'undefined') {
            $message.text('Please enter a coupon code.').addClass('is-error');
            $field.trigger('focus');
            return;
        }
        $button.prop('disabled', true);
        $message.removeClass('is-error is-success').text('Applying…');
        $.ajax({
            type: 'POST',
            url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
            dataType: 'html',
            data: {
                security: wc_checkout_params.apply_coupon_nonce,
                coupon_code: code,
                billing_email: $form.find('[name="billing_email"]').val() || ''
            }
        }).done(function (response) {
            var isError = response && (response.indexOf('woocommerce-error') !== -1 || response.indexOf('is-error') !== -1);
            $message.text($('<div>').html(response).text().trim()).toggleClass('is-error', isError).toggleClass('is-success', !isError);
            if (!isError) {
                $field.val('');
                $(document.body).trigger('applied_coupon_in_checkout', [code]);
                $(document.body).trigger('update_checkout', {update_shipping_method: false});
            }
        }).always(function () {
            $button.prop('disabled', false);
        });
    });

    $(document).on('click', '.mpwpb-inline-back-to-booking', function () {
        var parent = $(this).closest('div.mpwpb_registration');
        parent.find('.popupFooter').removeClass('mpwpb-inline-checkout-footer-hidden');
        load_date_time_tab(parent);
    });

    $(document).ready(function () {
        mpwpb_bind_inline_wc_success($('form.mpwpb-inline-wc-checkout-form'));

		$(document.body).on('checkout_error.mpwpbInline', function () {
			var $form = $('form.mpwpb-inline-wc-checkout-form:visible').first();
			if (!$form.length) {
				return;
			}
			var $error = $form.find('.woocommerce-error, .woocommerce-invalid').first();
			var $body = $form.closest('.mpwpb_popup').find('.mpwpb_popup_body');
			if ($error.length && $body.length) {
				$body.animate({scrollTop: Math.max(0, $error.position().top - 20)}, 250);
				$error.find('input, select, textarea').first().trigger('focus');
			}
		});

        var params = new URLSearchParams(window.location.search);
        var returnOrder = parseInt(params.get('mpwpb_inline_order'), 10) || 0;
        var returnKey = params.get('key') || '';
        if (returnOrder && returnKey && typeof mpwpb_ajax !== 'undefined' && mpwpb_ajax.is_wc_payment_mode) {
            var $parent = $('div.mpwpb_registration').first();
            $('[data-target-popup="#mpwpb_static_popup"]').first().trigger('click');
            load_order_proceed_tab($parent);
            mpwpb_show_wc_confirmation($parent, returnOrder, returnKey);
		}
    });

	/*
	 * WooCommerce captures `form.checkout` once when checkout.js initializes and
	 * binds submit directly to that collection. If a cached service template is
	 * missing the persistent form, mpwpb_load_wc_checkout_form() rebuilds it
	 * later; WooCommerce therefore never sees its submit event and the browser
	 * navigates directly to ?wc-ajax=checkout, displaying the JSON response.
	 *
	 * This delegated handler is only a safety net: a normally initialized WC
	 * form prevents/stops its submit before it reaches document, while a late
	 * form reaches this handler and is submitted through the same WC endpoint.
	 */
	$(document).on('submit.mpwpbInlineFallback', 'form.mpwpb-inline-wc-checkout-form', function (event) {
		if (event.isDefaultPrevented()) {
			return;
		}
		event.preventDefault();

		var $form = $(this);
		var parent = $form.closest('div.mpwpb_registration');
		if ($form.data('mpwpbFallbackSubmitting')) {
			return false;
		}
		if (typeof wc_checkout_params === 'undefined' || !wc_checkout_params.checkout_url) {
			mpwpb_inline_notice(parent, 'Checkout is unavailable. Please refresh the page and try again.');
			return false;
		}

		mpwpb_bind_inline_wc_success($form);
		var paymentMethod = $form.find('input[name="payment_method"]:checked').val() || '';
		var checkoutContext = {
			$checkout_form: $form,
			get_payment_method: function () { return paymentMethod; }
		};
		if ($form.triggerHandler('checkout_place_order', [checkoutContext]) === false ||
			(paymentMethod && $form.triggerHandler('checkout_place_order_' + paymentMethod, [checkoutContext]) === false)) {
			return false;
		}

		var orderAccepted = false;
		$form.data('mpwpbFallbackSubmitting', true).addClass('processing').attr('aria-busy', 'true');
		$form.find('#place_order').prop('disabled', true);
		$.ajax({
			type: 'POST',
			url: wc_checkout_params.checkout_url,
			data: $form.serialize(),
			dataType: 'json'
		}).done(function (result) {
			if (result && result.result === 'success' && result.redirect) {
				orderAccepted = true;
				if ($form.triggerHandler('checkout_place_order_success', [result, checkoutContext]) !== false) {
					window.location.assign(decodeURI(result.redirect));
				}
				return;
			}
			if (result && result.reload) {
				window.location.reload();
				return;
			}
			if (result && result.refresh) {
				$(document.body).trigger('update_checkout');
			}
			mpwpb_inline_notice(parent, mpwpb_inline_wc_result_message(result));
			$(document.body).trigger('checkout_error');
		}).fail(function (xhr) {
			var result = xhr && xhr.responseJSON ? xhr.responseJSON : null;
			mpwpb_inline_notice(parent, mpwpb_inline_wc_result_message(result));
			$(document.body).trigger('checkout_error');
		}).always(function () {
			// Once WC accepted the order, keep the form locked until the verified
			// confirmation replaces it; unlocking could create a duplicate order.
			if (!orderAccepted) {
				mpwpb_reset_inline_wc_submission($form);
			}
		});
		return false;
	});
    $(document).on('submit', 'div.mpwpb_registration .mpwpb-checkout-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var parent = $form.closest('div.mpwpb_registration');
        var $btn = $form.find('.mpwpb-checkout-submit');
        var $error = $form.siblings('.mpwpb-checkout-error');
        $error.hide().text('');
        $btn.prop('disabled', true);
        $.post(mpwpb_ajax.ajax_url, $form.serialize()).done(function (response) {
            if (response && response.success && response.data && response.data.inline_html) {
                // Offline bookings complete server-side immediately, so the
                // confirmation renders straight into the drawer (same Checkout
                // step area the form occupied) instead of navigating to a
                // separate thank-you page -- a fully in-drawer flow end to end.
                mpwpb_gdpr_save_billing_cookie($form);
                var $proceed = parent.find('.mpwpb_order_proceed_area');
                $proceed.html(response.data.inline_html);
                parent.data('mpwpbStep', 'confirmation');
                mpwpb_set_progress(parent, 'confirmation');
                // No footer recap on the confirmation screen -- the inline
                // thank-you already shows the full booking summary.
                parent.find('#mpwpb_selected_summary').empty().hide();
                parent.find('.mpwpb_popup_body').scrollTop(0);
            } else if (response && response.success && response.data && response.data.redirect) {
                // Stripe/PayPal must hand off to the gateway's own hosted page.
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

        // Back from the Staff sub-step to Date & Time (visibility-only toggle,
        // no load_* call here) -- move the bar back accordingly.
        mpwpb_set_progress($(this).closest('div.mpwpb_registration'), 'date_time');
    });

    $(document).on('click', '#mpwpb_show_hide_staff_member', function () {
        // Purely client-side (no AJAX -- the staff cards are already in the
        // DOM from page load, see mpwpbReorderPrefill's comment above), but
        // the fadeIn()/hide() swap plus whatever staff photos are loading for
        // the first time as this area becomes visible can still take a
        // moment -- same button-level "something is happening" feedback as
        // "Proceed to Checkout", just on a short fixed timer since there's no
        // request/response to hang it off of.
        let $staffBtn = $(this);
        $staffBtn.data('mpwpb-original-html', $staffBtn.html());
        $staffBtn.prop('disabled', true).addClass('mpwpb-cta-loading')
            .html('<i class="fas fa-spinner fa-spin _mR_xs"></i> ' + (mpwpb_ajax.processing_text || 'Please wait...'));

        $("#mpwpb_date_time_next_btn_id").fadeIn();
        mpwpb_set_progress($(this).closest('div.mpwpb_registration'), 'staff');
        $("#mpwpb_datetime_holder").hide();
        $("#mpwpb_staff_member_booking_area").fadeIn();

        $("#mpwpb_display_service_btn").hide();
        $("#mpwpb_display_date_time").fadeIn();

        // Mirror the running total into the staff step's own summary --
        // #mpwpd_all_total_bill is the single element mpwpb_price_calculation()
        // already keeps up to date, this just copies its current text.
        let parent = $staffBtn.closest('div.mpwpb_registration');
        parent.find('#mpwpb_staff_selected_total').text(parent.find('#mpwpd_all_total_bill').first().text());

        setTimeout(function () {
            $staffBtn.hide().prop('disabled', false).removeClass('mpwpb-cta-loading');
            if ($staffBtn.data('mpwpb-original-html') !== undefined) {
                $staffBtn.html($staffBtn.data('mpwpb-original-html'));
            }
        }, 400);
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

        // Picking a staff member only reveals the "Proceed to Checkout" button;
        // it doesn't advance to the Checkout step yet, so the bar stays on Staff
        // (mpwpb_set_progress moves it to Checkout when Proceed is clicked).
        mpwpb_set_progress($(this).closest('div.mpwpb_registration'), 'staff');
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
