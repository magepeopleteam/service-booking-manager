//==========Price settings=================//
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_price_settings [name="mpwpb_service_type"]', function () {
		let service_type = $(this).val();
		let parent = $(this).closest('.mpwpb_price_settings');
		parent.find('[data-service-type]').slideUp('fast');
		parent.find('[data-service-type="' + service_type + '"]').slideDown('fast');
	});
	$(document).on('click', '.mpwpb_add_category', function () {
		let parent = $(this).closest('.mp_settings_area');
		let target_item = parent.find('>.mp_hidden_content').find('.mp_hidden_item');
		let item = target_item.html();
		load_sortable_datepicker(parent, item);
		let unique_id = 'hidden_id_' + Math.floor((Math.random() * 9999) + 999);
		target_item.find('[name="mpwpb_hidden_name[]"]').val(unique_id);
		target_item.find('[name*="mpwpb_service_name"]').attr('name', 'mpwpb_service_name_' + unique_id + '[]');
		target_item.find('[name*="mpwpb_service_price"]').attr('name', 'mpwpb_service_price_' + unique_id + '[]');
	});
}(jQuery));
//==========Date time settings=================//
(function ($) {
	"use strict";
	$(document).on('change', '.mpwpb_settings_date_time  .mpwpb_start_time .formControl', function () {
		let post_id = $('#post_ID').val();
		let start_time = $(this).val();
		if (start_time>=0 && post_id > 0) {
			let parent = $(this).closest('tr');
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target=parent.find('.mpwpb_end_time');
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
					target.html(data).promise().done(function (){
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
		if (start_time>=0 && post_id > 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target=parent.find('.mpwpb_start_break_time');
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
					target.html(data).promise().done(function (){
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
		if (start_time>=0 && post_id > 0) {
			let day_name = parent.find('[data-day-name]').data('day-name');
			let target=parent.find('.mpwpb_end_break_time');
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
					target.html(data).promise().done(function (){
						target.find('.formControl').trigger('change');
					});
				}
			});
		}
	});
}(jQuery));
//==========Setup=================//
jQuery(document).ready(function ($) {
	$(document).on('click', '.welcome-tabs .tab-nav', function () {
		$(this).parent().parent().children('.tab-navs').children('.tab-nav').removeClass('active');
		$(this).addClass('active');
		id = $(this).attr('data-id');
		$(this).parent().parent().children('.tab-content').removeClass('active');
		$(this).parent().parent().children('.tab-content#' + id).addClass('active');
		if (id === 'start') {
			$('.prev').slideUp('fast');
			$('.next').slideDown('fast');
		}
		if (id === 'general') {
			$('.prev').slideDown('fast');
			$('.next').slideDown('fast');
		}
		if (id === 'done') {
			$('.prev').slideDown('fast');
			$('.next').slideUp('fast');
		}
	})
	$(document).on('click', '.welcome-tabs .next-prev .next', function () {
		welcomeTabs = $('.welcome-tabs .tab-nav');
		welcomeTabsContent = $('.welcome-tabs .tab-content ');
		totalTab = welcomeTabs.length;
		for (i = 0; i < welcomeTabs.length; i++) {
			tab = welcomeTabs[i];
			content = welcomeTabsContent[i];
			if (tab.classList.contains('active')) {
				currentTabIndex = i;
				tab.classList.remove('active');
				content.classList.remove('active');
			}
		}
		for (j = 0; j <= currentTabIndex; j++) {
			tab = welcomeTabs[j];
			tab.classList.add('done');
		}
		if (typeof welcomeTabs[currentTabIndex + 1] != 'undefined') {
			welcomeTabs[currentTabIndex + 1].classList.add('active');
			welcomeTabsContent[currentTabIndex + 1].classList.add('active');
		}
		if (currentTabIndex === 0) {
			$('.prev').slideDown('fast');
		}
		if (currentTabIndex === 1) {
			$('.next').slideUp('fast');
		}
	})
	$(document).on('click', '.welcome-tabs .next-prev .prev', function () {
		welcomeTabs = $('.welcome-tabs .tab-nav');
		welcomeTabsContent = $('.welcome-tabs .tab-content ');
		for (i = 0; i < welcomeTabs.length; i++) {
			tab = welcomeTabs[i];
			content = welcomeTabsContent[i];
			if (tab.classList.contains('active')) {
				currentTabIndex = i;
				tab.classList.remove('active');
				content.classList.remove('active');
			}
		}
		welcomeTabs[currentTabIndex - 1].classList.remove('done');
		if (typeof welcomeTabs[currentTabIndex - 1] != 'undefined') {
			welcomeTabs[currentTabIndex - 1].classList.add('active');
			welcomeTabsContent[currentTabIndex - 1].classList.add('active');
		}
		if (currentTabIndex === 1) {
			$('.prev').slideUp('fast');
		}
		if (currentTabIndex === 2) {
			$('.next').slideDown('fast');
		}
	})
});