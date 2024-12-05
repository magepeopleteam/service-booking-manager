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

// ============= Faq sidebar modal ======================
(function($) {
	$(document).on('click', '.mpwpb-faq-item-new', function (e) {
		$('#mpwpb-faq-msg').html('');
		$('.mpwpb_faq_save_buttons').show();
		$('.mpwpb_faq_update_buttons').hide();
		empty_faq_form();
	});

	

	function close_sidebar_modal(e){
		e.preventDefault();
		e.stopPropagation();
		$('.mpwpb-modal-container').removeClass('open');
	}

	$(document).on('click', '.mpwpb-faq-item-edit', function (e) {
		$('#mpwpb-faq-msg').html('');
		$('.mpwpb_faq_save_buttons').hide();
		$('.mpwpb_faq_update_buttons').show();
		var itemId = $(this).closest('.mpwpb-faq-item').data('id');
		var parent = $(this).closest('.mpwpb-faq-item');
		var headerText = parent.find('.faq-header p').text().trim();
		var faqContentId = parent.find('.faq-content').text().trim();
		var editorId = 'mpwpb_faq_content';
		$('input[name="mpwpb_faq_title"]').val(headerText);
		$('input[name="mpwpb_faq_item_id"]').val(itemId);
		if (tinymce.get(editorId)) {
			tinymce.get(editorId).setContent(faqContentId);
		} else {
			$('#' + editorId).val(faqContentId);
		}
	});

	$(document).on('click', '.mpwpb-faq-item-delete', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var itemId = $(this).closest('.mpwpb-faq-item').data('id');

		var isConfirmed = confirm('Are you sure you want to delete this row?');
		if (isConfirmed) {
			delete_faq_item(itemId);
		} else {
			console.log('Deletion canceled.'+itemId);
		}
	});
	

	function empty_faq_form(){
		$('input[name="mpwpb_faq_title"]').val('');
		tinyMCE.get('mpwpb_faq_content').setContent('');
		$('input[name="mpwpb_faq_item_id"]').val('');
	}
	

	$(document).on('click', '#mpwpb_faq_update', function (e) {
		e.preventDefault();
		update_faq();
	});

	$(document).on('click', '#mpwpb_faq_save', function (e) {
		e.preventDefault();
		save_faq();
	});

	$(document).on('click', '#mpwpb_faq_save_close', function (e) {
		e.preventDefault();
		save_faq();
		close_sidebar_modal(e);
	});

	function update_faq(){
		var title   = $('input[name="mpwpb_faq_title"]');
		var content = tinyMCE.get('mpwpb_faq_content').getContent();
		var postID  = $('input[name="mpwpb_post_id"]');
		var itemId = $('input[name="mpwpb_faq_item_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_faq_data_update',
				mpwpb_faq_title:title.val(),
				mpwpb_faq_content:content,
				mpwpb_faq_postID:postID.val(),
				mpwpb_faq_itemID:itemId.val(),
			},
			success: function(response) {
				$('#mpwpb-faq-msg').html(response.data.message);
				$('.mpwpb-faq-items').html('');
				$('.mpwpb-faq-items').append(response.data.html);
				setTimeout(function(){
					$('.mpwpb-modal-container').removeClass('open');
					empty_faq_form();
				},1000);
				
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	function save_faq(){
		var title   = $('input[name="mpwpb_faq_title"]');
		var content = tinyMCE.get('mpwpb_faq_content').getContent();
		var postID  = $('input[name="mpwpb_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_faq_data_save',
				mpwpb_faq_title:title.val(),
				mpwpb_faq_content:content,
				mpwpb_faq_postID:postID.val(),
			},
			success: function(response) {
				$('#mpwpb-faq-msg').html(response.data.message);
				$('.mpwpb-faq-items').html('');
				$('.mpwpb-faq-items').append(response.data.html);
				empty_faq_form();
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	function delete_faq_item(itemId){
		var postID  = $('input[name="mpwpb_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_faq_delete_item',
				mpwpb_faq_postID:postID.val(),
				itemId:itemId,
			},
			success: function(response) {
				$('.mpwpb-faq-items').html('');
				$('.mpwpb-faq-items').append(response.data.html);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}
	// ============= Service sidebar modal ======================
		
	$(document).on('click', '.mpwpb-service-new', function (e) {
		$('#mpwpb-service-msg').html('');
		$('.mpwpb_service_save_button').show();
		$('.mpwpb_service_update_button').hide();
		empty_service_form();
	});
	function empty_service_form(){
		$('input[name="service_name"]').val('');
		$('input[name="service_price"]').val('');
		$('input[name="service_duration"]').val('');
		$('textarea[name="service_description"]').val('');
		$('textarea[name="service_image_icon"]').val('');
		$('input[name="mpwpb_show_category_status"]').val('off');
		$('input[name="mpwpb_show_category_status"]').prop('checked', false);
		$('[data-collapse="#mpwpb_show_category_status"]').slideUp();
		$('select[name="mpwpb_parent_cat"]').val('');
		$('select[name="mpwpb_sub_category"]').val('');
	}

	$(document).on('change','input[name="mpwpb_show_category_status"]',function(){
		if ($(this).is(':checked')) {
			$('input[name="mpwpb_show_category_status"]').val('on');
        } else {
			$('input[name="mpwpb_show_category_status"]').val('off');
        }
	});

	$(document).on('click', '#mpwpb_service_save', function (e) {
		e.preventDefault();
		save_service();
	});
	$(document).on('click', '#mpwpb_service_save_close', function (e) {
		e.preventDefault();
		save_service();
		close_sidebar_modal(e);
	});
	function save_service(){
		var postID  = $('input[name="mpwpb_post_id"]');
		var service_name   = $('input[name="service_name"]');
		var service_price = $('input[name="service_price"]');
		var service_duration = $('input[name="service_duration"]');
		var service_description = $('textarea[name="service_description"]');
		var service_image_icon = $('input[name="service_image_icon"]');
		var show_category_status = $('input[name="mpwpb_show_category_status"]');
		var parent_cat = $('input[name="mpwpb_parent_cat_id"]');
		var sub_cat = $('input[name="mpwpb_sub_cat_id"]');
		$.ajax({
			url: mp_ajax_url,
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_save_service',
				service_postID:postID.val(),
				service_name:service_name.val(),
				service_price:service_price.val(),
				service_duration:service_duration.val(),
				service_description:service_description.val(),
				service_image_icon:service_image_icon.val(),
				service_category_status:show_category_status.val(),
				service_parent_cat:parent_cat.val(),
				service_sub_cat:sub_cat.val(),
			},
			success: function(response) {
				$('#mpwpb-service-msg').html(response.data.message);
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').append(response.data.html);
				empty_service_form();
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}
	
	$(document).on('click', '.mpwpb-service-edit', function (e) {
		$('#mpwpb-service-msg').html('');
		$('.mpwpb_service_save_button').hide();
		$('.mpwpb_service_update_button').show();

		var itemId = $(this).closest('tr').data('id');
		var catStatus = $(this).closest('tr').data('cat-status');
		var parentCat = $(this).closest('tr').data('parent-cat');
		var subCat = $(this).closest('tr').data('sub-cat');
		var details = $(this).closest('tr').attr('title');
		var parent = $(this).closest('tr');
		var icon = parent.find('td:nth-child(1) i').attr('class');
		var imageId = parent.find('td:nth-child(1) img').attr('data-imageId');
		var name = parent.find('td:nth-child(2)').text().trim();
		var price = parent.find('td:nth-child(3)').text().trim();
		var duratoin = parent.find('td:nth-child(4)').text().trim();

		$('input[name="service_item_id"]').val(itemId);
		if (icon) {
			$('input[name="service_image_icon"]').val(icon);
		} else if (imageId) {
			$('input[name="service_image_icon"]').val(imageId);
		}
		$('input[name="service_name"]').val(name);
		$('input[name="service_price"]').val(price);
		$('input[name="service_duration"]').val(duratoin);
		$('textarea[name="service_description"]').val(details);
		$('input[name="mpwpb_show_category_status"]').val(catStatus);
		if(catStatus=='on'){
			$('input[name="mpwpb_show_category_status"]').prop('checked', true);
			$('[data-collapse="#mpwpb_show_category_status"]').slideDown();
			$('select[name="mpwpb_parent_cat"]').val(parentCat);
			if(subCat!=''){
				$('.sub-category-container').slideDown('fast');
				$('select[name="mpwpb_sub_category"]').val(subCat);
			}
			
		}else{
			$('input[name="mpwpb_show_category_status"]').val('off');
			$('input[name="mpwpb_show_category_status"]').prop('checked', false);
			$('[data-collapse="#mpwpb_show_category_status"]').slideUp();
			
		}
	});

	$(document).on('click', '#mpwpb_service_update', function (e) {
		e.preventDefault();
		update_service();
	});

	function update_service(){

		var postID  = $('input[name="mpwpb_post_id"]');
		var itemId = $('input[name="service_item_id"]');
		var service_image_icon = $('input[name="service_image_icon"]');
		var service_name = $('input[name="service_name"]');
		var service_price = $('input[name="service_price"]');
		var service_duration = $('input[name="service_duration"]');
		var service_description = $('textarea[name="service_description"]');
		var show_category_status = $('input[name="mpwpb_show_category_status"]');
		var parent_cat = $('input[name="mpwpb_parent_cat_id"]');
		var sub_cat = $('input[name="mpwpb_sub_cat_id"]');

		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_service_update',
				service_image_icon:service_image_icon.val(),
				service_name:service_name.val(),
				service_price:service_price.val(),
				service_duration:service_duration.val(),
				service_description:service_description.val(),
				service_postID:postID.val(),
				service_itemId:itemId.val(),
				service_category_status:show_category_status.val(),
				service_parent_cat:parent_cat.val(),
				service_sub_cat:sub_cat.val(),
			},
			success: function(response) {
				$('#mpwpb-service-msg').html(response.data.message);
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').append(response.data.html);
				setTimeout(function(){
					$('.mpwpb-modal-container').removeClass('open');
					empty_service_form();
				},1000);
				
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-service-delete', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var itemId = $(this).closest('tr').data('id');
		var isConfirmed = confirm('Are you sure you want to delete this row?');
		if (isConfirmed) {
			delete_service(itemId);
		} else {
			console.log('Deletion canceled.'+itemId);
		}
	});

	function delete_service(itemId){
		var postID  = $('input[name="mpwpb_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_service_delete_item',
				service_postID:postID.val(),
				itemId:itemId,
			},
			success: function(response) {
	
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').append(response.data.html);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click','.show-all-services',function(){
		var postID  = $('input[name="mpwpb_post_id"]');
		$.ajax({
			url:mp_ajax_url,
			type:'POST',
			data:{
				action:'mpwpb_show_all_services',
				postID:postID.val(),
			},
			success:function(response){
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').html(response.data.html);
				
			},
			error:function(error){},
		});
	});

	// ============= Extra service sidebar modal ======================
	$(document).on('click', '.mpwpb-extra-service-new', function (e) {
		$('#mpwpb-ex-service-msg').html('');
		$('.mpwpb_ex_service_save_button').show();
		$('.mpwpb_ex_service_update_button').hide();
		empty_ex_service_form();
	});
	function empty_ex_service_form(){
		$('input[name="mpwpb_ext_service_name"]').val('');
		$('input[name="mpwpb_ext_service_price"]').val('');
		$('input[name="mpwpb_ext_service_qty"]').val('');
		$('textarea[name="mpwpb_ext_service_description"]').val('');
		$('input[name="mpwpb_ext_service_image_icon"]').val('');
	}

	$(document).on('click', '#mpwpb_ex_service_save', function (e) {
		e.preventDefault();
		save_ex_service();
	});

	$(document).on('click', '#mpwpb_ex_service_save_close', function (e) {
		e.preventDefault();
		save_ex_service();
		close_sidebar_modal(e);
	});
	function save_ex_service(){

		var postID  = $('input[name="mpwpb_ext_post_id"]');
		var service_name   = $('input[name="mpwpb_ext_service_name"]');
		var service_price = $('input[name="mpwpb_ext_service_price"]');
		var service_qty = $('input[name="mpwpb_ext_service_qty"]');
		var service_description = $('textarea[name="mpwpb_ext_service_description"]');
		var service_image_icon = $('input[name="mpwpb_ext_service_image_icon"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_save_ex_service',
				service_name:service_name.val(),
				service_price:service_price.val(),
				service_qty:service_qty.val(),
				service_description:service_description.val(),
				service_image_icon:service_image_icon.val(),
				service_postID:postID.val(),
			},
			success: function(response) {
				$('#mpwpb-ex-service-msg').html(response.data.message);
				$('.extra-service-table tbody').html('');
				$('.extra-service-table tbody').append(response.data.html);
				empty_ex_service_form();
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-ext-service-delete', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var itemId = $(this).closest('tr').data('id');
		var isConfirmed = confirm('Are you sure you want to delete this row?');
		if (isConfirmed) {
			delete_ext_service(itemId);
		} else {
			console.log('Deletion canceled.'+itemId);
		}
	});

	function delete_ext_service(itemId){
		var postID  = $('input[name="mpwpb_ext_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_ext_service_delete_item',
				service_postID:postID.val(),
				itemId:itemId,
			},
			success: function(response) {
				$('.extra-service-table tbody').html('');
				$('.extra-service-table tbody').append(response.data.html);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-ext-service-edit', function (e) {
		$('#mpwpb-ex-service-msg').html('');
		$('.mpwpb_ex_service_save_button').hide();
		$('.mpwpb_ex_service_update_button').show();

		var itemId = $(this).closest('tr').data('id');
		var parent = $(this).closest('tr');
		var icon = parent.find('td:nth-child(1) i').attr('class');
		var imageId = parent.find('td:nth-child(1) img').attr('data-imageId');
		var name = parent.find('td:nth-child(2)').text().trim();
		var details = parent.find('td:nth-child(3)').text().trim();
		var qty = parent.find('td:nth-child(4)').text().trim();
		var price = parent.find('td:nth-child(5)').text().trim();
		var price = parent.find('td:nth-child(5)').text().trim();
		
		$('input[name="mpwpb_ext_service_item_id"]').val(itemId);
		if (icon) {
			$('input[name="mpwpb_ext_service_image_icon"]').val(icon);
		} else if (imageId) {
			$('input[name="mpwpb_ext_service_image_icon"]').val(imageId);
		}
		$('input[name="mpwpb_ext_service_name"]').val(name);
		$('input[name="mpwpb_ext_service_price"]').val(price);
		$('input[name="mpwpb_ext_service_qty"]').val(qty);
		$('textarea[name="mpwpb_ext_service_description"]').val(details);
	});

	$(document).on('click', '#mpwpb_ex_service_update', function (e) {
		e.preventDefault();
		update_ext_service();
	});

	function update_ext_service(){

		var postID  = $('input[name="mpwpb_ext_post_id"]');
		var itemId = $('input[name="mpwpb_ext_service_item_id"]');
		var service_name = $('input[name="mpwpb_ext_service_name"]');
		var service_price = $('input[name="mpwpb_ext_service_price"]');
		var service_qty = $('input[name="mpwpb_ext_service_qty"]');
		var service_description = $('textarea[name="mpwpb_ext_service_description"]');
		var service_image_icon = $('input[name="mpwpb_ext_service_image_icon"]');

		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_ext_service_update',
				service_image_icon:service_image_icon.val(),
				service_name:service_name.val(),
				service_price:service_price.val(),
				service_qty:service_qty.val(),
				service_description:service_description.val(),
				service_postID:postID.val(),
				service_itemId:itemId.val(),
			},
			success: function(response) {
				$('#mpwpb-ex-service-msg').html(response.data.message);
				$('.extra-service-table tbody').html('');
				$('.extra-service-table tbody').append(response.data.html);
				setTimeout(function(){
					$('.mpwpb-modal-container').removeClass('open');
					empty_ex_service_form();
				},1000);
				
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	// =============Service Category sidebar modal ======================
	$(document).on('click', '.mpwpb-category-new', function (e) {
		$('#mpwpb-category-service-msg').html('');
		$('.mpwpb_category_service_save_button').show();
		$('.mpwpb_category_service_update_button').hide();
		
		empty_category_service_form();
	});

	$(document).on('change', 'input[name="mpwpb_use_sub_category"]', function(){
		mpwpb_load_parent_category();
        if ($(this).is(':checked')) {
			$('input[name="mpwpb_use_sub_category"]').val('on');
        } else {
			$('input[name="mpwpb_use_sub_category"]').val('off');
        }
    });

	$(document).on('change', '.load-parent-category', function(){
		$('input[name="mpwpb_parent_item_id"]').val($(this).val()); 
		$('input[name="mpwpb_parent_cat_id"]').val($(this).val()); 
		if ($(this).val() !== '') {
			mpwpb_load_sub_category($(this).val());
			$('.sub-category-container').slideDown(); 
		} else {
			$('.sub-category-container').slideUp();
		}
		
    });

	$(document).on('change', 'select[name="mpwpb_sub_category"]', function(){
		$('input[name="mpwpb_sub_cat_id"]').val($(this).val()); 
    });

	function empty_category_service_form(){
		$('input[name="mpwpb_category_name"]').val('');
		$('input[name="mpwpb_category_image_icon"]').val('');
		$('input[name="mpwpb_category_item_id"]').val('');
		$('input[name="mpwpb_parent_item_id"]').val('');
		$('input[name="mpwpb_use_sub_category"]').val('off');
		$('input[name="mpwpb_use_sub_category"]').prop('checked', false);
		$('[data-collapse="#mpwpb_use_sub_category"]').slideUp();
		$('select[name="mpwpb_parent_cat"]').val('').change();
		if($('.mpwpb-category-items').length > 0){
			$('.mpwpb-sub-category-enable').show();
		}
		else{
			$('.mpwpb-sub-category-enable').hide();
		}
		mpwpb_load_parent_category();
	}

	function mpwpb_load_parent_category(){
		$.ajax({
			url:mp_ajax_url,
			type:'POST',
			data:{
				action:'mpwpb_load_parent_category',
				postID:$('input[name="mpwpb_category_post_id"]').val(),
			},
			success:function(response){
				$('.mpwpb-parent-category').html('');
				$('.mpwpb-parent-category').append(response.data.html);
			},
			error:function(error){
				console.log('Error:', error);
			}
		});
	}

	function mpwpb_load_sub_category(parentId){
		var postID = $('input[name="mpwpb_category_post_id"]').val();
		$.ajax({
			url:mp_ajax_url,
			type:'POST',
			data:{
				action:'mpwpb_load_sub_category',
				postID:postID,
				parentId:parentId,
			},
			success:function(response){
				$('.mpwpb-sub-category').html('');
				$('.mpwpb-sub-category').append(response.data.html);
			},
			error:function(error){
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '#mpwpb_category_service_save', function (e) {
		e.preventDefault();
		save_category_service();
	});

	$(document).on('click', '#mpwpb_category_service_save_close', function (e) {
		e.preventDefault();
		save_category_service();
		close_sidebar_modal(e);
	});

	function save_category_service(){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		var category_name   = $('input[name="mpwpb_category_name"]');
		var category_image_icon = $('input[name="mpwpb_category_image_icon"]');
		var use_sub_category = $('input[name="mpwpb_use_sub_category"]');
		var parent_category = $('select[name="mpwpb_parent_cat"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_save_category_service',
				category_name:category_name.val(),
				category_image_icon:category_image_icon.val(),
				use_sub_category:use_sub_category.val(),
				parent_category:parent_category.val(),
				category_postID:postID.val(),
			},
			success: function(response) {
				$('#mpwpb-category-service-msg').html(response.data.message);
				$('.mpwpb-category-lists').html('');
				$('.mpwpb-category-lists').append(response.data.html);
				empty_category_service_form();
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-category-edit', function (e) {
		$('#mpwpb-category-service-msg').html('');
		$('.mpwpb_category_service_save_button').hide();
		$('.mpwpb_category_service_update_button').show();

		$('input[name="mpwpb_use_sub_category"]').val('off');
		$('input[name="mpwpb_use_sub_category"]').prop('checked', false);
		$('[data-collapse="#mpwpb_use_sub_category"]').slideUp();
		$('select[name="mpwpb_parent_cat"]').val('').change();
		
		$('.mpwpb-sub-category-enable').hide();
		// if($('.mpwpb-category-items').length > 1){
		// 	$('.mpwpb-sub-category-enable').show();
		// }
		// else{
		// 	$('.mpwpb-sub-category-enable').hide();
			
		// }

		var itemId = $(this).closest('.mpwpb-category-items').data('id');
		var parent = $(this).closest('.mpwpb-category-items');
		var icon = parent.find('.image-block i').attr('class');
		var imageId = parent.find('.image-block img').attr('data-imageId');
		var name = parent.find('.title').text().trim();

		$('input[name="mpwpb_category_item_id"]').val(itemId);
		if (icon) {
			$('input[name="mpwpb_category_image_icon"]').val(icon);
		} else if (imageId) {
			$('input[name="mpwpb_category_image_icon"]').val(imageId);
		}
		$('input[name="mpwpb_category_name"]').val(name);
	});

	$(document).on('click', '#mpwpb_category_service_update', function (e) {
		e.preventDefault();
		var use_sub_category = $('input[name="mpwpb_use_sub_category"]').val();
		if(use_sub_category=='on'){
			update_sub_category_service();
		}
		else{
			update_category_service();
		}
	});

	function update_sub_category_service(){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		var itemId = $('input[name="mpwpb_category_item_id"]');
		var parentId = $('input[name="mpwpb_parent_item_id"]');
		var category_image_icon = $('input[name="mpwpb_category_image_icon"]');
		var category_name = $('input[name="mpwpb_category_name"]');

		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_update_sub_category',
				category_image_icon:category_image_icon.val(),
				category_name:category_name.val(),
				category_postID:postID.val(),
				category_itemId:itemId.val(),
				category_parentId:parentId.val(),
			},
			success: function(response) {
				$('#mpwpb-category-service-msg').html(response.data.message);
				$('.mpwpb-category-lists').html('');
				$('.mpwpb-category-lists').append(response.data.html);
				setTimeout(function(){
					$('.mpwpb-modal-container').removeClass('open');
					empty_category_service_form();
				},1000);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	function update_category_service(){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		var itemId = $('input[name="mpwpb_category_item_id"]');
		var category_image_icon = $('input[name="mpwpb_category_image_icon"]');
		var category_name = $('input[name="mpwpb_category_name"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_update_category_service',
				category_image_icon:category_image_icon.val(),
				category_name:category_name.val(),
				category_postID:postID.val(),
				category_itemId:itemId.val(),
			},
			success: function(response) {
				$('#mpwpb-category-service-msg').html(response.data.message);
				$('.mpwpb-category-lists').html('');
				$('.mpwpb-category-lists').append(response.data.html);
				setTimeout(function(){
					$('.mpwpb-modal-container').removeClass('open');
					empty_category_service_form();
				},1000);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-sub-category-edit', function (e) {
		$('.mpwpb-sub-category-enable').show();
		$('input[name="mpwpb_use_sub_category"]').val('on');
		$('input[name="mpwpb_use_sub_category"]').prop('checked', true);
		$('[data-collapse="#mpwpb_use_sub_category"]').slideDown();
		$('#mpwpb-category-service-msg').html('');
		$('.mpwpb_category_service_save_button').hide();
		$('.mpwpb_category_service_update_button').show();

		var itemId = $(this).closest('.mpwpb-sub-category-items').data('id');
		var parentId = $(this).closest('.mpwpb-sub-category-items').data('parent-id');
		var parent = $(this).closest('.mpwpb-sub-category-items');

		var icon = parent.find('.image-block i').attr('class');
		var imageId = parent.find('.image-block img').attr('data-imageId');
		var name = parent.find('.title').text().trim();
		$('select[name="mpwpb_parent_cat"]').val(parentId).change();
		$('input[name="mpwpb_parent_item_id"]').val(parentId);
		$('input[name="mpwpb_category_item_id"]').val(itemId);

		if (icon) {
			$('input[name="mpwpb_category_image_icon"]').val(icon);
		} else if (imageId) {
			$('input[name="mpwpb_category_image_icon"]').val(imageId);
		}
		$('input[name="mpwpb_category_name"]').val(name);

	});

	$(document).on('click', '.mpwpb-category-delete', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var itemId = $(this).closest('.mpwpb-category-items').data('id');
		var isConfirmed = confirm('Are you sure you want to delete this row?');
		if (isConfirmed) {
			delete_category_service(itemId);
		} else {
			console.log('Deletion canceled.'+itemId);
		}
	});

	function delete_category_service(itemId){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_category_service_delete_item',
				category_postID:postID.val(),
				itemId:itemId,
			},
			success: function(response) {
				$('.mpwpb-category-lists').html('');
				$('.mpwpb-category-lists').append(response.data.html);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	$(document).on('click', '.mpwpb-sub-category-delete', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var itemId = $(this).closest('.mpwpb-sub-category-items').data('id');
		var isConfirmed = confirm('Are you sure you want to delete this row?');
		if (isConfirmed) {
			delete_sub_category_service(itemId);
		} else {
			console.log('Deletion canceled.'+itemId);
		}
	});

	function delete_sub_category_service(itemId){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		$.ajax({
			url: mp_ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_sub_category_delete',
				category_postID:postID.val(),
				itemId:itemId,
			},
			success: function(response) {
				$('.mpwpb-category-lists').html('');
				$('.mpwpb-category-lists').append(response.data.html);
			},
			error: function(error) {
				console.log('Error:', error);
			}
		});
	}

	// ====================category show service===============
	$(document).on('click','.mpwpb-category-items', function(){
		var itemId =$(this).data('id');
		show_service_by_cat(itemId);
	});
	$(document).on('click','.mpwpb-sub-category-items', function(){
		var itemId =$(this).data('id');
		var parentId =$(this).data('parent-id');
		show_service_by_sub_cat(itemId,parentId);
	});
	function show_service_by_cat(itemId){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		$.ajax({
			url:mp_ajax_url,
			type:'POST',
			data:{
				action:'mpwpb_load_service_by_category',
				postId:postID.val(),
				itemId:itemId,
			},
			success:function(response){
				console.log(response);
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').html(response.data.html);
			},
			error:function(error){

			}
		});
	}
	function show_service_by_sub_cat(itemId,parentId){
		var postID  = $('input[name="mpwpb_category_post_id"]');
		$.ajax({
			url:mp_ajax_url,
			type:'POST',
			data:{
				action:'mpwpb_load_service_by_sub_category',
				postId:postID.val(),
				itemId:itemId,
				parentId:parentId,
			},
			success:function(response){
				console.log(response);
				$('.mpwpb-service-rows').html('');
				$('.mpwpb-service-rows').html(response.data.html);
			},
			error:function(error){

			}
		});
	}
	

	// =====================sidebar modal open close=============
	$(document).on('click', '[data-modal]', function (e) {
		const modalTarget = $(this).data('modal');
		$(`[data-modal-target="${modalTarget}"]`).addClass('open');
	});

	$(document).on('click', '[data-modal-target] .mpwpb-modal-close', function (e) {
		$(this).closest('[data-modal-target]').removeClass('open');
	});
	// ==================icon add========================
	$(document).on('click', '.mpwpb-icon-add', function (e) {
		$('.mpwpb-show-icon-lists').slideToggle();
	});	
})(jQuery);

