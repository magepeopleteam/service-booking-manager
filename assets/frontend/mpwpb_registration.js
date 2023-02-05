//Registration
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_registration [name="mpwpb_category_type"]', function () {
		let parent = $(this).closest('.mpwpb_registration');
		let category_type = $(this).val();
		let target = parent.find('.mpwpb_registration_section');
		if (target.length > 0 && category_type) {
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_category_service_type",
					"post_id": parent.find('[name="mpwpb_post_id"]').val(),
					"category_type": category_type
				},
				beforeSend: function () {
					dLoader_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						loadBgImage();
						mpwpb_active_carousel($('.mpwpb_date_carousel'));
					});
				}
			});
		}
	});
	$(document).on('click', '.mpwpb_registration_area .mpwpb_price_calculation', function () {
		let parent = $(this).closest('.mpwpb_registration_area');
		let price = 0;
		parent.find('[data-price]').each(function () {
			let current_price = $(this).val() ?? 0;
			current_price = current_price && current_price > 0 ? current_price : 0;
			price = price + parseFloat(current_price);
		});
		parent.find('[data-extra-price]').each(function () {
			let current_price = $(this).val() ?? 0;
			current_price = current_price && current_price > 0 ? current_price : 0;
			price = price + parseFloat(current_price);
		});
		parent.find('.mpwpb_total_bill').html(mp_price_format(price));
	});
	$(document).on("click", ".mpwpb_registration_area .mpwpb_book_now[type='button']", function () {
		let parent = $(this).closest('.mpwpb_registration_area');
		let date = parent.find('[name="mpwpb_date"]');
		if (date.val().trim() !== "") {
			parent.find('.mpwpb_add_to_cart').trigger('click');
		}
	});
}(jQuery));