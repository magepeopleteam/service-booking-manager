//==========Price settings=================//
(function ($) {
	"use strict";
	$(document).on('click', '.mpwpb_add_category', function () {
		let parent = $(this).closest('.mp_settings_area');
		let target_item = $(this).next($('.mp_hidden_content')).find(' .mp_hidden_item');
		let item = target_item.html();
		load_sortable_datepicker(parent, item);
		let unique_id = Math.floor((Math.random() * 9999) + 9999);
		let sub_unique_id = Math.floor((Math.random() * 9999) + 99999);
		target_item.find('[name="mpwpb_category_hidden_id[]"]').val(unique_id);
		target_item.find('[name*="mpwpb_sub_category_hidden_id_"]').attr('name', 'mpwpb_sub_category_hidden_id_' + unique_id + '[]').val(sub_unique_id);
		target_item.find('[name*="mpwpb_service_name_"]').attr('name', 'mpwpb_service_name_' + sub_unique_id + '[]');
		target_item.find('[name*="mpwpb_service_img_"]').attr('name', 'mpwpb_service_img_' + sub_unique_id + '[]');
		target_item.find('[name*="mpwpb_service_details_"]').attr('name', 'mpwpb_service_details_' + sub_unique_id + '[]');
		target_item.find('[name*="mpwpb_service_price_"]').attr('name', 'mpwpb_service_price_' + sub_unique_id + '[]');
		target_item.find('[name*="mpwpb_service_duration_"]').attr('name', 'mpwpb_service_duration_' + sub_unique_id + '[]');
	});
	$(document).on('click', '.mpwpb_add_sub_category', function () {
		let parent = $(this).closest('.mp_settings_area');
		let target_item = $(this).next($('.mp_hidden_content')).find(' .mp_hidden_item');
		let item = target_item.html();
		load_sortable_datepicker(parent, item);
		let unique_id = Math.floor((Math.random() * 9999) + 99999);
		target_item.find('[name*="mpwpb_sub_category_hidden_id_"]').val(unique_id);
		target_item.find('[name*="mpwpb_service_name_"]').attr('name', 'mpwpb_service_name_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_service_img_"]').attr('name', 'mpwpb_service_img_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_service_details_"]').attr('name', 'mpwpb_service_details_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_service_price_"]').attr('name', 'mpwpb_service_price_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_service_duration_"]').attr('name', 'mpwpb_service_duration_' + unique_id + '[]');
	});
	$(document).on('change', '[name="mpwpb_category_active"]', function () {
		let parent = $(this).closest('.mpwpb_price_settings');
		if (!$(this).is(":checked")) {
			let target = parent.find('[name="mpwpb_sub_category_active"]');
			if (target.is(":checked")) {
				target.next($('span')).trigger('click');
			}
		}
	});
	//========extra service settings===============//
	$(document).on('click', '.mpwpb_add_group_service', function () {
		let parent = $(this).closest('.mp_settings_area');
		let target_item = $(this).next($('.mp_hidden_content')).find(' .mp_hidden_item');
		let item = target_item.html();
		load_sortable_datepicker(parent, item);
		let unique_id = Math.floor((Math.random() * 9999) + 9999);
		target_item.find('[name="mpwpb_extra_hidden_name[]"]').val(unique_id);
		target_item.find('[name*="mpwpb_extra_service_name_"]').attr('name', 'mpwpb_extra_service_name_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_extra_service_img_"]').attr('name', 'mpwpb_extra_service_img_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_extra_service_qty_"]').attr('name', 'mpwpb_extra_service_qty_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_extra_service_price_"]').attr('name', 'mpwpb_extra_service_price_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_extra_service_details_"]').attr('name', 'mpwpb_extra_service_details_' + unique_id + '[]');
	});
}(jQuery));
//==========Date time settings=================//
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_settings_date_time  .mpwpb_start_time .formControl', function () {
		let post_id = $('#post_ID').val();
		let start_time = $(this).val();
		if (start_time >= 0 && post_id > 0) {
			let parent = $(this).closest('tr');
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_end_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_end_time_slot",
					"post_id": post_id,
					"day_name": day_name,
					"start_time": start_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
	$(document).on('change', '.mpwpb_settings_date_time  .mpwpb_end_time .formControl', function () {
		let parent = $(this).closest('tr');
		let post_id = $('#post_ID').val();
		let start_time = parent.find('.mpwpb_start_time .formControl').val();
		let end_time = $(this).val();
		if (start_time >= 0 && post_id > 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_start_break_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_start_break_time",
					"post_id": post_id,
					"day_name": day_name,
					"start_time": start_time,
					"end_time": end_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
	$(document).on('change', '.mpwpb_settings_date_time  .mpwpb_start_break_time .formControl', function () {
		let parent = $(this).closest('tr');
		let post_id = $('#post_ID').val();
		let start_time = $(this).val();
		let end_time = parent.find('.mpwpb_end_time .formControl').val();
		if (start_time >= 0 && post_id > 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_end_break_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_end_break_time",
					"post_id": post_id,
					"day_name": day_name,
					"start_time": start_time,
					"end_time": end_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
}(jQuery));
//==========Staff settings=================//
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_staff_page  .mpwpb_start_time .formControl', function () {
		let user_id= $('#mpwpb_user_id').val();
		let start_time = $(this).val();
		if (start_time >= 0) {
			let parent = $(this).closest('tr');
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_end_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_staff_end_time_slot",
					"user_id": user_id,
					"day_name": day_name,
					"start_time": start_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
	$(document).on('change', '.mpwpb_staff_page  .mpwpb_end_time .formControl', function () {
		let parent = $(this).closest('tr');
		let user_id= $('#mpwpb_user_id').val();
		let start_time = parent.find('.mpwpb_start_time .formControl').val();
		let end_time = $(this).val();
		if (start_time >= 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_start_break_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_staff_start_break_time",
					"user_id": user_id,
					"day_name": day_name,
					"start_time": start_time,
					"end_time": end_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
	$(document).on('change', '.mpwpb_staff_page  .mpwpb_start_break_time .formControl', function () {
		let parent = $(this).closest('tr');
		let user_id= $('#mpwpb_user_id').val();
		let start_time = $(this).val();
		let end_time = parent.find('.mpwpb_end_time .formControl').val();
		if (start_time >= 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target = parent.find('.mpwpb_end_break_time');
			$.ajax({
				type: 'POST',
				url: mp_ajax_url,
				data: {
					"action": "get_mpwpb_staff_end_break_time",
					"user_id": user_id,
					"day_name": day_name,
					"start_time": start_time,
					"end_time": end_time,
				},
				beforeSend: function () {
					dLoader_xs_circle(target);
				},
				success: function (data) {
					target.html(data).promise().done(function () {
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
}(jQuery));
//==========Staff=================//
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_add_staff  .mpwpb_user_select', function () {
		load_staff_form(parseInt($(this).val()));
	});
	$(document).on('click', '#mpwpb_delete_staff', function () {
		if (confirm('Are You Sure , Remove this row ? \n\n 1. Ok : To Remove . \n 2. Cancel : To Cancel .')) {
			let staff_id = $(this).data('staff-id');
			let parent = $(this).closest('.mpwpb_staff_list');
			if (staff_id) {
				$.ajax({
					type: 'POST', url: mp_ajax_url, data: {
						"action": "mpwpb_delete_staff", "staff_id": staff_id
					}, beforeSend: function () {
						dLoader_circle(parent);
					}, success: function (data) {
						parent.html(data);
					}
				});
			}
			return true;
		} else {
			return false;
		}
	});
	$(document).on('click', '#mpwpb_edit_staff', function () {
		$('.mpwpb_staff_page .mpwpb_add_new_staff').trigger('click');
		load_staff_form(parseInt($(this).data('staff-id')));
	});
	function load_staff_form(user_id) {
		let target = $('.mpwpb_staff_page').find('.mpwpb_add_staff');
		$.ajax({
			type: 'POST',
			url: mp_ajax_url,
			data: {
				"action": "get_mpwpb_get_staff_form",
				"user_id": user_id,
			},
			beforeSend: function () {
				dLoader_circle(target);
			},
			success: function (data) {
				target.html(data).promise().done(function () {
					mp_load_date_picker(target);
					dLoaderRemove(target);
				});
			}
		});
	}
}(jQuery));

// ============= sidebar collapsible ======================
(function($) {
	$(document).ready(function($){

		sidebar_toggle_open_close();
		edit_faq();

		function edit_faq(){
			
			$('.edit-faq').on('click', function(e) {
				var postID =  document.getElementById('mpwpb-post-id');
				itemId = $(this).data('id');
				console.log(postId);
				console.log(itemId);
				$.ajax({
					url: mp_ajax_url,
					type: 'POST',
					data: {
						action: 'mpwpb_edit_faq_data',
						postID:postID.value,
						itemId:itemId,
					},
					success: function(response) {
						console.log(response.data);
						$('#mpwpb-faq-input-title').val();
					},
					error: function(error) {
						console.log('Error:', error);
					}
				});
			});
		}

		function sidebar_toggle_open_close(){
			$('.mpwpb-sidebar-open').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$('.mpwpb-sidebar-container').addClass('open');
				save_faq_data();
			});
			
			$('.mpwpb-sidebar-close').on('click', function() {
				$('.mpwpb-sidebar-container').removeClass('open');
			});
		}
		// ======save faq by ajax=============
		

		function save_faq_data(){
			$('input[name="faq_save"]').click(function(event) {
				event.preventDefault();

				var title = document.getElementById('mpwpb-faq-input-title');
				var content = document.getElementById('mpwpb-faq-input-content');
				var postID =  document.getElementById('mpwpb-post-id');

				$.ajax({
					url: mp_ajax_url,
					type: 'POST',
					data: {
						action: 'mpwpb_faq_data_save',
						mpwpb_faq_title:title.value,
						mpwpb_faq_content:content.value,
						mpwpb_faq_postID:postID.value,
					},
					success: function(response) {
						console.log(response.data);
						$('#mpwpb-faq-msg').html(response.data);
					},
					error: function(error) {
						console.log('Error:', error);
					}
				});
			});
		}
	});
})(jQuery);

