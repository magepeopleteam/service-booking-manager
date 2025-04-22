function mpwpb_price_calculation($this) {
    let parent = $this.closest('div.mpwpb_registration');
    let price = 0;
    parent.find('.mpwpb_service_area .mpwpb_service_item[data-price].mpActive').each(function () {
        let current_price = jQuery(this).data('price') ?? 0;
        current_price = current_price && current_price > 0 ? current_price : 0;
        price = price + parseFloat(current_price);
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
    parent.find('.mpwpb_total_bill').html(mpwpb_price_format(price));
}
//Registration
(function ($) {
    "use strict";
    $(document).ready(function () {
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
        parent.find('.mpwpb_extra_service_area,.next_service_area,.mpwpb_date_time_area,.mpwpb_order_proceed_area').slideUp(350);
        let target_sub_category = parent.find('.mpwpb_sub_category_area');
        let target_service = parent.find('.mpwpb_service_area');
        parent.find('[name="mpwpb_service[]"]').each(function () {
            $(this).val('');
        });
        parent.find('.mpwpb_summary_item[data-service]').slideUp('fast');
        let category = parseInt(parent.find('[name="mpwpb_category"]').val());
        let sub_category = parseInt(parent.find('[name="mpwpb_sub_category"]').val());
        target_service.find('.mpwpb_service_item[data-category]').each(function () {
            $(this).removeClass('mpActive');
            $(this).find('.mpwpb_service_button.mActive').each(function () {
                mpwpb_all_content_change($(this));
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
    $(document).on('click', 'div.mpwpb_registration .mpwpb_category_item', function () {
        let current = $(this);
        let category = current.data('category');
        if (category && !current.hasClass('mpActive')) {
            let parent = current.closest('div.mpwpb_registration');
            let target_sub_category = current.closest('.mpwpb_category_section').find('.mpwpb_sub_category_area');
            let target_service = parent.find('.mpwpb_service_area');
            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.mpwpb_summary_item[data-category]').slideDown('fast').find('h6').html(current.find('h6').html());
            parent.find('[name="mpwpb_category"]').val(category).promise().done(function () {
                refresh_sub_category(parent);
                refresh_service(parent);
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
        let current = $(this);
        let parent = current.closest('div.mpwpb_registration');
        let category = parent.find('[name="mpwpb_category"]').val();
        let sub_category = current.data('sub-category');
        if (category && sub_category && !current.hasClass('mpActive')) {
            //let target_sub_category = parent.find('.mpwpb_sub_category_area');
            let target_service = parent.find('.mpwpb_service_area');
            parent.find('.mpwpb_summary_area_left').slideDown('fast');
            parent.find('.mpwpb_summary_item[data-sub-category]').slideDown('fast').find('h6').html(current.find('h6').html());
            parent.find('[name="mpwpb_sub_category"]').val(sub_category).promise().done(function () {
                refresh_service(parent);
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
                refresh_service(parent);
            }).promise().done(function () {
                mpwpb_price_calculation(current);
            });
        }
    });
    //==========service============//
    $(document).on('click', 'div.mpwpb_registration .mpwpb_service_button', function () {
        let $this = $(this);
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
        if (date) {
            let link_id = $(this).attr('data-wc_link_id');
            let mpwpb_category = parent.find('[name="mpwpb_category"]').val();
            mpwpb_category = mpwpb_category ? parseInt(mpwpb_category) : '';
            let mpwpb_sub_category = parent.find('[name="mpwpb_sub_category"]').val();
            mpwpb_sub_category = mpwpb_sub_category ? parseInt(mpwpb_sub_category) : '';
            let mpwpb_service = {};
            let service_count = 0;
            parent.find('[name="mpwpb_service[]"]').each(function () {
                let service = $(this).val();
                if (service) {
                    mpwpb_service[service_count] = parseInt(service);
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
                    "mpwpb_date": date,
                    "mpwpb_extra_service": mpwpb_extra_service,
                    "mpwpb_extra_service_type": mpwpb_extra_service_type,
                    "mpwpb_extra_service_qty": mpwpb_extra_service_qty,
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
    //======================//
    $(document).ready(function () {
        $('.faq-header').on('click', function () {
            console.log('test');
            $(this).next('.faq-content').slideToggle();
            $(this).find('i').toggleClass('fa-plus fa-minus');
        });
    });
}(jQuery));

