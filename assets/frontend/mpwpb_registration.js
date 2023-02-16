function mpwpb_price_calculation($this) {
	let parent = $this.closest('.mpwpb_registration');
	let price = 0;
	parent.find('.mpwpb_service_area [data-price]').each(function () {
		if (jQuery(this).hasClass('mpActive')) {
			let current_price = jQuery(this).data('value') ?? 0;
			current_price = current_price && current_price > 0 ? current_price : 0;
			price = price + parseFloat(current_price);
		}
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
	$(document).ready(function () {
		$('.mpwpb_registration .mpwpb_category_area .mpwpb_category_item:visible:first').each(function () {
			$(this).trigger('click');
		});
	});
	$(document).on('click', '.mpwpb_registration .mpwpb_category_item', function () {
		let parent = $(this).closest('.mpwpb_registration');
		let category_type = $(this).data('category');
		let target_service = parent.find('.mpwpb_service_area');
		if (target_service.length > 0) {
			target_service.find('.sub_category_area[data-category]').each(function () {
				if ($(this).data('category') === category_type) {
					$(this).slideDown(350);
					loadBgImage();
				} else {
					$(this).slideUp(350);
				}
			});
		}
	});
	$(document).on('change', '.mpwpb_registration [name="mpwpb_service"]', function () {
		let parent = $(this).closest('.mpwpb_registration');
		let category = parent.find('[data-category].mpActive').data('category');
		let sub_category = parent.find('[data-sub-category].mpActive').data('sub-category');
		let service = $(this).val();
		parent.find('[name="mpwpb_category"]').val(category);
		parent.find('[name="mpwpb_sub_category"]').val(sub_category);
		if (service) {
			parent.find('.mpwpb_next_extra_service').slideDown(350);
			if (parent.find('.mptbm_extra_service').length > 0) {
				parent.find('.mptbm_extra_date_time').removeClass('mpDisabled');
				parent.find('.mptbm_extra_service').removeClass('mpDisabled').trigger('click');
			} else {
				parent.find('.mptbm_extra_date_time').removeClass('mpDisabled').trigger('click');
			}
		}
	});
	$(document).on('click', '.mpwpb_registration .mpwpb_next_extra_service', function () {
		let parent = $(this).closest('.mpwpb_registration');
		let service = parent.find('[name="mpwpb_service"]');
		if (service) {
			if (parent.find('.mptbm_extra_service').length > 0) {
				parent.find('.mptbm_extra_service').removeClass('mpDisabled').trigger('click');
			} else {
				parent.find('.mptbm_extra_date_time').removeClass('mpDisabled').trigger('click');
			}
		}
	});
	$(document).on('click', '.mpwpb_registration .mpwpb_prev_ex_service', function () {
		let parent = $(this).closest('.mpwpb_registration');
		if (parent.find('.mptbm_extra_service').length > 0) {
			parent.find('.mptbm_extra_service').trigger('click');
		} else {
			parent.find('.mptbm_service_tab').trigger('click');
		}
	});
	$(document).on('click', '.mpwpb_registration .mpwpb_prev_service', function () {
		let parent = $(this).closest('.mpwpb_registration');
		parent.find('.mptbm_service_tab').trigger('click');
	});
	$(document).on('click', '.mpwpb_registration .mpwpb_next_date_time', function () {
		let parent = $(this).closest('.mpwpb_registration');
		parent.find('.mptbm_extra_date_time').trigger('click');
	});
	$(document).on('change', '.mpwpb_registration [name="mpwpb_date"]', function () {
		let parent = $(this).closest('.mpwpb_registration');
		let date = parent.find('[name="mpwpb_date"]');
		let service = parent.find('[name="mpwpb_service"]');
		if (date && service) {
			parent.find('.mpwpb_book_now').slideDown(350);
		}
	});
	$(document).on("click", ".mpwpb_registration .decQty ,.mpwpb_registration .incQty", function () {
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
	$(document).on('click', '.mpwpb_registration .mpwpb_price_calculation', function () {
		mpwpb_price_calculation($(this));
	});
	$(document).on('change', '.mpwpb_registration [name="mpwpb_extra_service_qty[]"]', function () {
		mpwpb_price_calculation($(this));
	});
	$(document).on("click", ".mpwpb_registration .mpwpb_book_now[type='button']", function () {
		let parent = $(this).closest('.mpwpb_registration');
		let date = parent.find('[name="mpwpb_date"]');
		let service = parent.find('[name="mpwpb_service"]');
		if (date && service) {
			parent.find('.mpwpb_add_to_cart').trigger('click');
		}
	});
}(jQuery));