function mpwpb_price_calculation($this) {
    let parent = $this.closest('div.mpwpb_registration');
    let price = 0;
    parent.find('.mpwpb_service_area .mpwpb_service_item[data-price].mpActive').each(function () {
        let qty = parseInt( jQuery(this).attr('data-service-qty') );
        let current_price = jQuery(this).data('price') ?? 0;
        current_price = current_price && current_price > 0 ? current_price : 0;
        price = price + parseFloat(current_price) * qty ;
    });
    parent.find('.mpwpb_extra_service_item').each(function () {
        let service_name = jQuery(this).find('[name="mpwpb_extra_service_type[]"]').val();
        if (service_name) {
            let ex_target = jQuery(this).find('[name="mpwpb_extra_service_qty[]');
            let ex_qty = parseInt(ex_target.val());
            let ex_price = ex_target.data('price');
            ex_price = ex_price && ex_price > 0 ? ex_price : 0;
            price = price + parseFloat(ex_price) * ex_qty;
        }
    });

    /*if( price > 0 ){
        parent.find("#mpwpb_progress_service").addClass('active');
    }else{
        parent.find("#mpwpb_progress_service").removeClass('active');
    }*/

    parent.find('.mpwpb_total_bill').html(mpwpb_price_format(price));

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
        parent.find('.mpwpb_service_area,.mpwpb_extra_service_area,.next_service_area,.mpwpb_date_time_area,.mpwpb_order_proceed_area').slideUp(350);
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
        $('#mpwpd_btn_proceed').fadeIn();
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
        load_service_tab(parent);
        if (category && category > 0) {
            parent.find('.mpwpb_category_item').each(function () {
                if (parseInt($(this).data('category')) === category) {
                    $(this).trigger('click');
                }
            });
        } else {
            let service = parseInt(current.data('service'));
            parent.find('.mpwpb_service_item').each(function () {
                if (parseInt($(this).data('service')) === service) {
                    $(this).find('.mpwpb_service_button').trigger('click');
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
        } else {
            mpwpb_alert($(this));
        }

        $(".mpwpb_service_button_remove").css({ 'display': 'none' });
        $(".mpwpb_ex_service_button_remove").css({ 'display': 'none' });
    });
    function load_date_time_tab(parent) {
        parent.find('.mpwpb_date_time_area,.next_date_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_order_proceed_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
    }
    function load_service_tab(parent) {
        parent.find('.all_service_area,.next_service_area').slideDown(350);
        parent.find('.mpwpb_date_time_area,.mpwpb_order_proceed_area,.next_date_area').slideUp(300);
        mpwpb_load_bg_image();
    }
    function load_order_proceed_tab(parent) {
        parent.find('.mpwpb_order_proceed_area').slideDown(350);
        parent.find('.all_service_area,.mpwpb_date_time_area,.next_date_area,.next_service_area').slideUp(300)
        mpwpb_load_bg_image();
    }
    //==========date============//
    $(document).on('change', 'div.mpwpb_registration [name="mpwpb_date"]', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let date = parent.find('[name="mpwpb_date"]').val();
        if (date) {
            let current_date = parent.find('.mpwpb_date_time_area [data-radio-check="' + date + '"]').data('date');
            parent.find('.mpwpb_summary_item[data-date]').slideDown('fast').find('h6').html(current_date);
        } else {
            parent.find('.mpwpb_summary_item[data-date]').slideUp('fast');
        }
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_next', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        let date = parent.find('[name="mpwpb_date"]').val();

        let is_recurring_on = 'off';
        let recurringCount = 1;
        is_recurring_on = $('#mpwpb_enable_recurring_booking').is(':checked');
        if( is_recurring_on ){
            recurringCount = parseInt(parent.find('#mpwpb_recurring_count').val());
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
                    mpwpb_loader(parent);
                },
                success: function (data) {
                    window.location.href = data;
                },
                error: function (response) {
                    console.log(response);
                }
            });
        } else {
            mpwpb_alert($(this));
        }
    });
    $(document).on('click', 'div.mpwpb_registration .mpwpb_date_time_prev', function () {
        let parent = $(this).closest('div.mpwpb_registration');
        parent.find('.mpwpb_service_tab').addClass('mpActive').removeClass('mpDisabled');
        load_service_tab(parent);
        $(".mpwpb_service_button_remove").css({ 'display': 'flex' });
        $(".mpwpb_ex_service_button_remove").css({ 'display': 'flex' });
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


    $(document).on('click', '#mpwpb_show_hide_date_time', function () {
        $("#mpwpb_datetime_holder").fadeIn();
        $("#mpwpb_carousel_area").fadeIn();

        $("#mpwpb_staff_member_holder").fadeOut();
    });

    $(document).on('click', '#mpwpb_show_hide_staff_member', function () {
        $("#mpwpb_datetime_holder").fadeOut();
        $("#mpwpb_carousel_area").fadeOut();

        $("#mpwpb_staff_member_holder").fadeIn();
    });

    $(document).on('click', '.mpwp_select_staff_card', function () {
        $('.mpwp_select_staff_card').removeClass('selected');
        $(this).addClass('selected');
        let staffId = $(this).find('.mpwpb_selected_staff').val();

        $("#mpwpb_staff_member_booking").val( staffId );

        // $("#mpwpb_progress_staff").addClass('active');
        $("#mpwpb_progress_checkout").addClass('active');
    });

    //======================//
    $(document).ready(function () {
        $('.faq-header').on('click', function () {
            console.log('test');
            $(this).next('.faq-content').slideToggle();
            $(this).find('i').toggleClass('fa-plus fa-minus');
        });
    });
}(jQuery));

