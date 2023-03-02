function mpwpb_price_calculation($this) {
	let parent = $this.closest('form.mpwpb_registration');
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
	parent.find('.mpwpb_total_bill').html(mp_price_format(price));
}

//Registration
(function ($) {
	"use strict";

	function on_active_category(parent) {
		let category = parent.find('[name="mpwpb_category"]').val();
		parent.find('.mpwpb_summary_area_left').slideDown('fast');
		parent.find('.mpwpb_summary_item[data-category]').slideDown('fast').find('h6').html(category);
		parent.find('.mpwpb_summary_area[data-category]').slideDown('fast').find('h6').html(category);
		parent.find('.mpwpb_summary_item[data-sub-category]').slideUp('fast');
		parent.find('.mpwpb_summary_area[data-sub-category]').slideUp('fast');
		parent.find('.mpwpb_summary_item[data-service]').slideUp('fast');
		parent.find('.mpwpb_summary_area[data-service]').slideUp('fast');
		parent.find('.mpwpb_sub_category_item.mpActive').each(function () {
			$(this).removeClass('mpActive');
		});
		parent.find('.mpwpb_service_item.mpActive').each(function () {
			$(this).removeClass('mpActive');
		});
	}

	function on_active_sub_category(parent) {
		let sub_category = parent.find('[name="mpwpb_sub_category"]').val();
		parent.find('.mpwpb_summary_area_left').slideDown('fast');
		parent.find('.mpwpb_summary_item[data-sub-category]').slideDown('fast').find('h6').html(sub_category);
		parent.find('.mpwpb_summary_area[data-sub-category]').slideDown('fast').find('h6').html(sub_category);
		parent.find('.mpwpb_summary_area[data-service]').slideUp('fast');
		parent.find('.mpwpb_summary_item[data-service]').slideUp('fast');
		parent.find('.mpwpb_service_item.mpActive').each(function () {
			$(this).removeClass('mpActive');
		});
	}

	$(document).ready(function () {
		$('form.mpwpb_registration').each(function () {
			$(this).find('.mptbm_service_tab').trigger('click');
		});
	});
	//==========tab============//
	$(document).on('click', 'form.mpwpb_registration .mptbm_service_tab', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		if (parent.find('.mpwpb_category_area').length > 0) {
			parent.find('.mpwpb_category_area').slideDown(350);
			parent.find('.mpwpb_sub_category_area,.mpwpb_service_area,.mpwpb_extra_service_area,.mpwpb_date_time_area,.mpwpb_summary_area').slideUp(300);
		} else {
			parent.find('.mpwpb_service_area').slideDown(350);
			parent.find('.mpwpb_sub_category_area,.mpwpb_category_area,.mpwpb_extra_service_area,.mpwpb_date_time_area,.mpwpb_summary_area').slideUp(300);
		}
		loadBgImage();
	});
	$(document).on('click', 'form.mpwpb_registration .mptbm_extra_service_tab', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mpwpb_extra_service_area').slideDown(350);
		parent.find('.mpwpb_category_area,.mpwpb_sub_category_area,.mpwpb_service_area,.mpwpb_date_time_area,.mpwpb_summary_area').slideUp(300)
		loadBgImage();
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_date_time', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mpwpb_date_time_area').slideDown(350);
		parent.find('.mpwpb_category_area,.mpwpb_sub_category_area,.mpwpb_service_area,.mpwpb_extra_service_area,.mpwpb_summary_area').slideUp(300)
		loadBgImage();
	});
	$(document).on('click', 'form.mpwpb_registration .mptbm_summary_tab', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mpwpb_summary_area').slideDown(350);
		parent.find('.mpwpb_category_area,.mpwpb_sub_category_area,.mpwpb_service_area,.mpwpb_extra_service_area,.mpwpb_date_time_area').slideUp(300)
		loadBgImage();
	});
	//==========category============//
	$(document).on('click', 'form.mpwpb_registration .mpwpb_category_item', function () {
		let current = $(this);
		if (!current.hasClass('mpActive')) {
			let parent = $(this).closest('form.mpwpb_registration');
			let category = current.data('category');
			parent.find('[name="mpwpb_category"]').val(category);
			parent.find('[name="mpwpb_sub_category"]').val('');
			parent.find('[name="mpwpb_service"]').val('');
			on_active_category(parent);
			parent.find('.mpwpb_category_item.mpActive').each(function () {
				$(this).removeClass('mpActive');
			}).promise().done(function () {
				current.addClass('mpActive');
				mpwpb_price_calculation(current);
				parent.find('.mpwpb_category_next').trigger('click');
			});
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_category_next', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let category = parent.find('[name="mpwpb_category"]').val();
		if (category) {
			parent.find('.mpwpb_category_area').slideUp(350);
			let target_service = parent.find('.mpwpb_service_area');
			let target_sub_category = parent.find('.mpwpb_sub_category_area');
			if (target_sub_category.length > 0) {
				target_sub_category.find('.mpwpb_sub_category_item[data-category]').each(function () {
					if ($(this).data('category') === category) {
						$(this).slideDown(350);
					} else {
						$(this).slideUp(350);
					}
				}).promise().done(function () {
					target_sub_category.slideDown(250);
					loadBgImage();
				});
			} else {
				if (target_service.length > 0) {
					target_service.find('.mpwpb_service_item[data-category]').each(function () {
						if ($(this).data('category') === category) {
							$(this).slideDown(350);
						} else {
							$(this).slideUp(350);
						}
					}).promise().done(function () {
						target_service.slideDown(250);
						loadBgImage();
					});
				}
			}
		}
	});
	//=========sub category=============//
	$(document).on('click', 'form.mpwpb_registration .mpwpb_sub_category_item', function () {
		let current = $(this);
		let parent = $(this).closest('form.mpwpb_registration');
		let category = parent.find('[name="mpwpb_category"]').val();
		if (category && !current.hasClass('mpActive')) {
			let sub_category = current.data('sub-category');
			parent.find('[name="mpwpb_service"]').val('');
			parent.find('[name="mpwpb_sub_category"]').val(sub_category);
			on_active_sub_category(parent);
			parent.find('.mpwpb_sub_category_item.mpActive').each(function () {
				$(this).removeClass('mpActive');
			}).promise().done(function () {
				current.addClass('mpActive');
				mpwpb_price_calculation(current);
				parent.find('.mpwpb_sub_category_next').trigger('click');
			});
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_sub_category_prev', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mptbm_service_tab').trigger('click');
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_sub_category_next', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let sub_category = parent.find('[name="mpwpb_sub_category"]').val();
		let category = parent.find('[name="mpwpb_category"]').val();
		if (category && sub_category) {
			let target_service = parent.find('.mpwpb_service_area');
			if (target_service.length > 0) {
				target_service.find('.mpwpb_service_item[data-category]').each(function () {
					if ($(this).data('category') === category && $(this).data('sub-category') === sub_category) {
						$(this).slideDown(350);
					} else {
						$(this).slideUp(350);
					}
				}).promise().done(function () {
					parent.find('.mpwpb_sub_category_area').slideUp(350);
					target_service.slideDown(250);
					loadBgImage();
				});
			}
		}
	});
	//==========service============//
	$(document).on('click', 'form.mpwpb_registration .mpwpb_service_item', function () {
		let current = $(this);
		let parent = $(this).closest('form.mpwpb_registration');
		if (!current.hasClass('mpActive')) {
			let service = current.data('service');
			let price = parseFloat(current.data('price'));
			parent.find('[name="mpwpb_service"]').val(service);
			parent.find('.mpwpb_summary_item[data-service]').slideDown('fast').find('h6').html(service);
			parent.find('.mpwpb_summary_area[data-service]').slideDown('fast').find('h6').html(service);
			parent.find('.mpwpb_summary_item').find('.service_price').html(mp_price_format(price));
			parent.find('.mpwpb_summary_area').find('.service_price').html(mp_price_format(price));
			parent.find('.mpwpb_service_item.mpActive').each(function () {
				$(this).removeClass('mpActive');
			}).promise().done(function () {
				current.addClass('mpActive');
				mpwpb_price_calculation(current);
				parent.find('.mpwpb_summary_area_left').slideDown('fast');
				parent.find('.mpwpb_service_next').trigger('click');
			});
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_service_prev', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mpwpb_service_area').slideUp(350);
		if (parent.find('.mpwpb_sub_category_area').length > 0) {
			parent.find('.mpwpb_sub_category_area').slideDown(250);
		} else {
			parent.find('.mpwpb_category_area').slideDown(250);
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_service_next', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let service = parent.find('[name="mpwpb_service"]').val();
		if (service) {
			parent.find('.mptbm_service_tab').addClass('mpActive').removeClass('mpDisabled');
			parent.find('.mpwpb_service_area').slideUp(350);
			if (parent.find('.mpwpb_extra_service_area').length > 0) {
				parent.find('.mptbm_extra_service_tab').addClass('mpActive').removeClass('mpDisabled').trigger('click');
			} else {
				parent.find('.mpwpb_date_time').addClass('mpActive').removeClass('mpDisabled').trigger('click');
			}
			loadBgImage();
		}
	});
	//=========extra service=============//
	$(document).on('click', 'form.mpwpb_registration .mpwpb_extra_service_next', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let service = parent.find('[name="mpwpb_service"]').val();
		if (service) {
			parent.find('.mpwpb_date_time').addClass('mpActive').removeClass('mpDisabled').trigger('click');
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_extra_service_prev', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		parent.find('.mptbm_service_tab').addClass('mpActive').removeClass('mpDisabled').trigger('click');
	});
	//==========date============//
	$(document).on('change', 'form.mpwpb_registration [name="mpwpb_date"]', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let date = parent.find('[name="mpwpb_date"]').val();
		if (date) {
			let current_date = parent.find('.mpwpb_date_time_area [data-radio-check="' + date + '"]').data('date');
			parent.find('.mpwpb_summary_item[data-date]').slideDown('fast').find('h6').html(current_date);
		} else {
			parent.find('.mpwpb_summary_item[data-date]').slideUp('fast');
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_date_time_next', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let date = parent.find('[name="mpwpb_date"]').val();
		if (date) {
			parent.find('.mptbm_summary_tab').addClass('mpActive').removeClass('mpDisabled').trigger('click');
		}
	});
	$(document).on('click', 'form.mpwpb_registration .mpwpb_date_time_prev', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		if (parent.find('.mpwpb_extra_service_area').length > 0) {
			parent.find('.mptbm_extra_service_tab').addClass('mpActive').removeClass('mpDisabled').trigger('click');
		} else {
			parent.find('.mptbm_service_tab').addClass('mpActive').removeClass('mpDisabled').trigger('click');
		}
	});
	//========Extra service==============//
	$(document).on('change', 'form.mpwpb_registration [name="mpwpb_extra_service_qty[]"]', function () {
		$(this).closest('.mpwpb_extra_service_item').find('[name="mpwpb_extra_service_type[]"]').trigger('change');
	});
	$(document).on('change', 'form.mpwpb_registration [name="mpwpb_extra_service_type[]"]', function () {
		let parent = $(this).closest('form.mpwpb_registration');
		let service_name = $(this).data('value');
		let service_value = $(this).val();
		if (service_value) {
			let qty = $(this).closest('.mpwpb_extra_service_item').find('[name="mpwpb_extra_service_qty[]"]').val();
			parent.find('[data-extra-service="' + service_name + '"]').slideDown(350).find('.ex_service_qty').html('x' + qty);
		} else {
			parent.find('[data-extra-service="' + service_name + '"]').slideUp(350);
		}
		mpwpb_price_calculation($(this));
	})
	$(document).on('click', 'form.mpwpb_registration .mpwpb_price_calculation', function () {
		mpwpb_price_calculation($(this));
	});
	$(document).on("click", "form.mpwpb_registration .decQty ,form.mpwpb_registration .incQty", function () {
		let current = $(this);
		let target = current.closest('.qtyIncDec').find('input');
		let currentValue = parseInt(target.val());
		let value = current.hasClass('incQty') ? (currentValue + 1) : ((currentValue - 1) > 0 ? (currentValue - 1) : 0);
		let min = parseInt(target.attr('min'));
		let max = parseInt(target.attr('max'));
		target.parents('.qtyIncDec').find('.incQty , .decQty').removeClass('mpDisabled');
		if (value < min || isNaN(value) || value === 0) {
			value = min;
			target.parents('.qtyIncDec').find('.decQty').addClass('mpDisabled');
		}
		if (value > max) {
			value = max;
			target.parents('.qtyIncDec').find('.incQty').addClass('mpDisabled');
		}
		target.val(value).trigger('change').trigger('input');
	});
	//======================//
	$(document).on("click", "form.mpwpb_registration .mpwpb_book_now[type='button']", function () {
		let parent = $(this).closest('.mpwpb_registration');
		let date = parent.find('[name="mpwpb_date"]').val();
		let service = parent.find('[name="mpwpb_service"]').val();
		if (date && service) {
			parent.find('.mpwpb_add_to_cart').trigger('click');
		}
	});
}(jQuery));