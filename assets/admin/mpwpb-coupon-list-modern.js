(function ($) {
	"use strict";

	var config = window.mpwpbCouponModal || {};
	var $root;
	var lastTrigger = null;
	var activeFilter = 'all';

	function message(text, isError) {
		var $message = $root.find('[data-coupon-modal-message]');
		$message.text(text || '').toggleClass('is-error', !!isError);
	}

	function clearValidation($form) {
		$form.find('.has-validation-error').removeClass('has-validation-error');
		$form.find('.mpwpb-coupon-validation-error').remove();
	}

	function showFieldError($form, fieldName, errorMessage, tab) {
		var $field = $form.find('[name="' + fieldName + '"]').first();
		var $target = $field;
		if (fieldName === 'mpwpb_coupon_services[]') {
			$target = $form.find('.mpwpb-coupon-service-picker');
		}
		if (tab && tab !== 'identity') {
			$form.find('[data-tabs-target="' + tab + '"]').trigger('click');
		}
		var $container = $target.closest('label.label, .mpwpb-coupon-modal__identity label');
		if (!$container.length) {
			$container = $target;
		}
		$container.addClass('has-validation-error');
		$('<span class="mpwpb-coupon-validation-error" role="alert"></span>').text(errorMessage).appendTo($container);
		var $focus = $field;
		if (fieldName === 'mpwpb_coupon_services[]') {
			$focus = $form.find('.mpwpb-coupon-service-picker__search input');
		}
		if ($focus.length) {
			$focus[0].scrollIntoView({behavior: 'smooth', block: 'center'});
			window.setTimeout(function () { $focus.trigger('focus'); }, 180);
		}
		message(errorMessage, true);
		return false;
	}

	function validateCouponForm($form) {
		clearValidation($form);
		message('', false);
		function value(name) {
			return String($form.find('[name="' + name + '"]').val() || '').trim();
		}
		function optionalNumber(name) {
			var raw = value(name);
			return raw === '' ? null : Number(raw);
		}
		function validDate(raw) {
			if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
				return false;
			}
			var parts = raw.split('-').map(Number);
			var date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
			return date.getUTCFullYear() === parts[0] && date.getUTCMonth() === parts[1] - 1 && date.getUTCDate() === parts[2];
		}

		var title = value('post_title');
		if (!title) {
			return showFieldError($form, 'post_title', 'Enter a coupon name.', 'identity');
		}
		if (title.length > 160) {
			return showFieldError($form, 'post_title', 'Coupon name must be 160 characters or fewer.', 'identity');
		}
		var code = value('mpwpb_coupon_code').toUpperCase();
		if (!code) {
			return showFieldError($form, 'mpwpb_coupon_code', 'Enter a coupon code.', '#mpwpb_coupon_general');
		}
		if (code.length > 64 || !/^[A-Z0-9][A-Z0-9_-]*$/.test(code)) {
			return showFieldError($form, 'mpwpb_coupon_code', 'Use letters, numbers, hyphens or underscores only.', '#mpwpb_coupon_general');
		}

		var startDate = value('mpwpb_coupon_start_date');
		var expiryDate = value('mpwpb_coupon_expiry_date');
		if (startDate && !validDate(startDate)) {
			return showFieldError($form, 'mpwpb_coupon_start_date', 'Enter a valid start date.', '#mpwpb_coupon_general');
		}
		if (expiryDate && !validDate(expiryDate)) {
			return showFieldError($form, 'mpwpb_coupon_expiry_date', 'Enter a valid expiry date.', '#mpwpb_coupon_general');
		}
		if (startDate && expiryDate && expiryDate < startDate) {
			return showFieldError($form, 'mpwpb_coupon_expiry_date', 'Expiry date must be on or after the start date.', '#mpwpb_coupon_general');
		}

		var discountType = value('mpwpb_coupon_discount_type');
		var discount = optionalNumber('mpwpb_coupon_discount_value');
		if (discountType !== 'free' && (discount === null || !Number.isFinite(discount) || discount <= 0)) {
			return showFieldError($form, 'mpwpb_coupon_discount_value', 'Enter a discount value greater than zero.', '#mpwpb_coupon_discount');
		}
		if (discountType === 'percentage' && discount > 100) {
			return showFieldError($form, 'mpwpb_coupon_discount_value', 'Percentage discount cannot exceed 100%.', '#mpwpb_coupon_discount');
		}

		if (value('mpwpb_coupon_service_scope') === 'specific' && !$form.find('[name="mpwpb_coupon_services[]"] option:selected').length) {
			return showFieldError($form, 'mpwpb_coupon_services[]', 'Select at least one service.', '#mpwpb_coupon_services');
		}

		var minTotal = optionalNumber('mpwpb_coupon_min_total');
		var maxTotal = optionalNumber('mpwpb_coupon_max_total');
		if (minTotal !== null && (!Number.isFinite(minTotal) || minTotal < 0)) {
			return showFieldError($form, 'mpwpb_coupon_min_total', 'Minimum booking total cannot be negative.', '#mpwpb_coupon_restrictions');
		}
		if (maxTotal !== null && (!Number.isFinite(maxTotal) || maxTotal < 0)) {
			return showFieldError($form, 'mpwpb_coupon_max_total', 'Maximum booking total cannot be negative.', '#mpwpb_coupon_restrictions');
		}
		if (minTotal !== null && maxTotal !== null && maxTotal < minTotal) {
			return showFieldError($form, 'mpwpb_coupon_max_total', 'Maximum total must be at least the minimum total.', '#mpwpb_coupon_restrictions');
		}
		var minQty = optionalNumber('mpwpb_coupon_min_qty');
		var maxQty = optionalNumber('mpwpb_coupon_max_qty');
		if (minQty !== null && (!Number.isInteger(minQty) || minQty < 0)) {
			return showFieldError($form, 'mpwpb_coupon_min_qty', 'Minimum quantity must be a whole number.', '#mpwpb_coupon_restrictions');
		}
		if (maxQty !== null && (!Number.isInteger(maxQty) || maxQty < 0)) {
			return showFieldError($form, 'mpwpb_coupon_max_qty', 'Maximum quantity must be a whole number.', '#mpwpb_coupon_restrictions');
		}
		if (minQty !== null && maxQty !== null && maxQty < minQty) {
			return showFieldError($form, 'mpwpb_coupon_max_qty', 'Maximum quantity must be at least the minimum.', '#mpwpb_coupon_restrictions');
		}

		var dateMode = value('mpwpb_coupon_booking_date_mode');
		if (dateMode === 'allowlist' || dateMode === 'blacklist') {
			var bookingDates = value('mpwpb_coupon_booking_dates').split(',').map(function (item) { return item.trim(); }).filter(Boolean);
			if (!bookingDates.length) {
				return showFieldError($form, 'mpwpb_coupon_booking_dates', 'Enter at least one booking date.', '#mpwpb_coupon_scheduling_staff');
			}
			if (bookingDates.some(function (item) { return !validDate(item); })) {
				return showFieldError($form, 'mpwpb_coupon_booking_dates', 'Use YYYY-MM-DD for every booking date.', '#mpwpb_coupon_scheduling_staff');
			}
		}
		if (value('mpwpb_coupon_time_mode') === 'range') {
			var timeStart = value('mpwpb_coupon_time_range_start');
			var timeEnd = value('mpwpb_coupon_time_range_end');
			if (!timeStart) {
				return showFieldError($form, 'mpwpb_coupon_time_range_start', 'Select a start time.', '#mpwpb_coupon_scheduling_staff');
			}
			if (!timeEnd || timeEnd === timeStart) {
				return showFieldError($form, 'mpwpb_coupon_time_range_end', 'Select a different end time.', '#mpwpb_coupon_scheduling_staff');
			}
		}
		var staffScope = value('mpwpb_coupon_staff_scope');
		if ((staffScope === 'include' || staffScope === 'exclude') && !$form.find('[name="mpwpb_coupon_staff_ids[]"] option:selected').length) {
			return showFieldError($form, 'mpwpb_coupon_staff_ids[]', 'Select at least one staff member.', '#mpwpb_coupon_scheduling_staff');
		}

		var totalLimit = optionalNumber('mpwpb_coupon_usage_limit_total');
		var customerLimit = optionalNumber('mpwpb_coupon_usage_limit_per_customer');
		if (totalLimit !== null && (!Number.isInteger(totalLimit) || totalLimit < 1)) {
			return showFieldError($form, 'mpwpb_coupon_usage_limit_total', 'Total usage limit must be a whole number greater than zero.', '#mpwpb_coupon_usage_limits');
		}
		if (customerLimit !== null && (!Number.isInteger(customerLimit) || customerLimit < 1)) {
			return showFieldError($form, 'mpwpb_coupon_usage_limit_per_customer', 'Per-customer limit must be a whole number greater than zero.', '#mpwpb_coupon_usage_limits');
		}
		if (totalLimit !== null && customerLimit !== null && customerLimit > totalLimit) {
			return showFieldError($form, 'mpwpb_coupon_usage_limit_per_customer', 'Per-customer limit cannot exceed the total limit.', '#mpwpb_coupon_usage_limits');
		}
		return true;
	}

	function closeModal() {
		$root.empty();
		$('body').removeClass('mpwpb-coupon-modal-open');
		if (lastTrigger) {
			lastTrigger.focus();
		}
	}

	function activateFirstTab() {
		var $modal = $root.find('.mpwpb-coupon-modal');
		var $first = $modal.find('[data-tabs-target]').first();
		$modal.find('[data-tabs-target], .tabsItem').removeClass('active');
		$first.addClass('active');
		$modal.find('[data-tabs="' + $first.attr('data-tabs-target') + '"]').addClass('active');
	}

	function setConditional($element, visible) {
		$element.prop('hidden', !visible).toggleClass('is-condition-visible', !!visible);
	}

	function syncConditionalFields() {
		var scope = $root.find('#mpwpb_coupon_service_scope').val();
		var dateMode = $root.find('#mpwpb_coupon_booking_date_mode').val();
		var timeMode = $root.find('#mpwpb_coupon_time_mode').val();
		var staffScope = $root.find('#mpwpb_coupon_staff_scope').val();
		setConditional($root.find('#mpwpb_coupon_services_wrap'), scope === 'specific');
		setConditional($root.find('#mpwpb_coupon_booking_dates_wrap'), dateMode && dateMode !== 'none');
		setConditional($root.find('.mpwpb_coupon_time_bucket_wrap'), timeMode === 'bucket');
		setConditional($root.find('.mpwpb_coupon_time_range_wrap'), timeMode === 'range');
		setConditional($root.find('#mpwpb_coupon_staff_ids_wrap'), staffScope && staffScope !== 'all');
		setConditional($root.find('#mpwpb_coupon_discount_value_wrap'), $root.find('#mpwpb_coupon_discount_type').val() !== 'free');
	}

	function enhanceServicePicker() {
		var $select = $root.find('select[name="mpwpb_coupon_services[]"]');
		if (!$select.length || $select.next('.mpwpb-coupon-service-picker').length) {
			return;
		}

		var $picker = $('<div class="mpwpb-coupon-service-picker"></div>');
		var $toolbar = $('<div class="mpwpb-coupon-service-picker__toolbar"></div>');
		var $searchWrap = $('<label class="mpwpb-coupon-service-picker__search"><span class="dashicons dashicons-search"></span></label>');
		var $search = $('<input type="search" autocomplete="off" placeholder="Search services…" aria-label="Search services">');
		var $count = $('<span class="mpwpb-coupon-service-picker__count"></span>');
		var $actions = $('<div class="mpwpb-coupon-service-picker__actions"></div>');
		var $selectAll = $('<button type="button">Select all</button>');
		var $clear = $('<button type="button">Clear</button>');
		var $list = $('<div class="mpwpb-coupon-service-picker__list"></div>');
		var $empty = $('<p class="mpwpb-coupon-service-picker__empty" hidden>No matching services found.</p>');

		$searchWrap.append($search);
		$actions.append($selectAll, $clear);
		$toolbar.append($searchWrap, $count, $actions);
		$select.find('option').each(function (index) {
			var option = this;
			var label = $(option).text();
			var separator = label.lastIndexOf(' — ');
			var serviceName = separator > -1 ? label.substring(0, separator) : label;
			var serviceGroup = separator > -1 ? label.substring(separator + 3) : '';
			var inputId = 'mpwpb-coupon-service-' + index;
			var $item = $('<label class="mpwpb-coupon-service-option"></label>').attr('for', inputId).attr('data-search', label.toLowerCase());
			var $checkbox = $('<input type="checkbox">').attr('id', inputId).prop('checked', option.selected);
			var $check = $('<span class="mpwpb-coupon-service-option__check"><span class="dashicons dashicons-yes"></span></span>');
			var $copy = $('<span class="mpwpb-coupon-service-option__copy"></span>');
			$copy.append($('<strong></strong>').text(serviceName));
			if (serviceGroup) {
				$copy.append($('<small></small>').text(serviceGroup));
			}
			$checkbox.on('change', function () {
				option.selected = this.checked;
				updateCount();
			});
			$item.append($checkbox, $check, $copy);
			$list.append($item);
		});

		function updateCount() {
			var selected = $select.find('option:selected').length;
			$count.text(selected ? selected + ' selected' : 'None selected').toggleClass('has-selection', selected > 0);
		}
		function setAll(checked) {
			$list.find('input[type="checkbox"]:visible').each(function () {
				this.checked = checked;
				$select.find('option').eq($(this).closest('.mpwpb-coupon-service-option').index()).prop('selected', checked);
			});
			updateCount();
		}
		$search.on('input', function () {
			var query = $(this).val().toLowerCase().trim();
			var visible = 0;
			$list.find('.mpwpb-coupon-service-option').each(function () {
				var matches = !query || String($(this).attr('data-search')).indexOf(query) !== -1;
				$(this).toggle(matches);
				visible += matches ? 1 : 0;
			});
			$empty.prop('hidden', visible !== 0);
		});
		$selectAll.on('click', function () { setAll(true); });
		$clear.on('click', function () { setAll(false); });

		$picker.append($toolbar, $list, $empty);
		$select.addClass('mpwpb-coupon-native-service-select').attr({'aria-hidden': 'true', 'tabindex': '-1'}).after($picker);
		updateCount();
	}

	function openModal(postId, trigger) {
		lastTrigger = trigger || null;
		$('body').addClass('mpwpb-coupon-modal-open');
		$root.html('<div class="mpwpb-coupon-modal mpwpb-coupon-modal--loading"><div class="mpwpb-coupon-modal__backdrop"></div><div class="mpwpb-coupon-modal__loader"><span class="spinner is-active"></span>' + (config.loading || 'Loading…') + '</div></div>');
		$.post(config.ajaxUrl, {
			action: 'mpwpb_coupon_modal_form',
			nonce: config.nonce,
			post_id: postId || 0
		}).done(function (response) {
			if (!response || !response.success) {
				closeModal();
				window.alert((response && response.data && response.data.message) || config.error);
				return;
			}
			$root.html(response.data.html);
			activateFirstTab();
			enhanceServicePicker();
			syncConditionalFields();
			$root.find('input[name="post_title"]').trigger('focus');
		}).fail(function (xhr) {
			closeModal();
			window.alert((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || config.error);
		});
	}

	function applyListFilter() {
		var query = String($('[data-coupon-search]').val() || '').toLowerCase().trim();
		$('#mpwpb_coupon_list_result tbody tr').each(function () {
			var $row = $(this);
			var statusMatch = activeFilter === 'all' || $row.attr('data-coupon-status') === activeFilter;
			var searchMatch = !query || $row.text().toLowerCase().indexOf(query) !== -1;
			$row.toggle(statusMatch && searchMatch);
		});
	}

	$(function () {
		$root = $('#mpwpb-coupon-modal-root');
		if (!$root.length) {
			return;
		}

		$(document).on('click', '[data-coupon-modal-open]', function (event) {
			event.preventDefault();
			openModal(parseInt($(this).attr('data-post-id'), 10) || 0, this);
		});

		$root.on('click', '[data-coupon-modal-close]', function (event) {
			event.preventDefault();
			closeModal();
		});

		$root.on('click', '[data-tabs-target]', function () {
			var $item = $(this);
			var $modal = $item.closest('.mpwpb-coupon-modal');
			$modal.find('[data-tabs-target], .tabsItem').removeClass('active');
			$item.addClass('active');
			$modal.find('[data-tabs="' + $item.attr('data-tabs-target') + '"]').addClass('active');
		});

		$root.on('change', '#mpwpb_coupon_service_scope', function () {
			setConditional($root.find('#mpwpb_coupon_services_wrap'), $(this).val() === 'specific');
		});
		$root.on('change', '#mpwpb_coupon_discount_type', function () {
			setConditional($root.find('#mpwpb_coupon_discount_value_wrap'), $(this).val() !== 'free');
		});
		$root.on('change', '#mpwpb_coupon_booking_date_mode', function () {
			setConditional($root.find('#mpwpb_coupon_booking_dates_wrap'), $(this).val() !== 'none');
		});
		$root.on('change', '#mpwpb_coupon_time_mode', function () {
			var mode = $(this).val();
			setConditional($root.find('.mpwpb_coupon_time_bucket_wrap'), mode === 'bucket');
			setConditional($root.find('.mpwpb_coupon_time_range_wrap'), mode === 'range');
		});
		$root.on('change', '#mpwpb_coupon_staff_scope', function () {
			setConditional($root.find('#mpwpb_coupon_staff_ids_wrap'), $(this).val() !== 'all');
		});

		$root.on('submit', '[data-coupon-modal-form]', function (event) {
			event.preventDefault();
			var $form = $(this);
			var $button = $form.find('[data-coupon-modal-save]');
			var originalLabel = $button.text();
			if (!validateCouponForm($form) || !$form[0].reportValidity()) {
				return;
			}
			message('', false);
			$button.prop('disabled', true).text(config.saving || 'Saving…');
			var data = $form.serializeArray();
			data.push({name: 'action', value: 'mpwpb_coupon_modal_save'});
			data.push({name: 'nonce', value: config.nonce});
			$.post(config.ajaxUrl, data).done(function (response) {
				if (!response || !response.success) {
					var errorData = response && response.data ? response.data : {};
					if (errorData.field) {
						showFieldError($form, errorData.field, errorData.message || config.error, errorData.tab || '');
					} else {
						message(errorData.message || config.error, true);
					}
					return;
				}
				$('#mpwpb_coupon_list_result').html(response.data.listHtml);
				applyListFilter();
				closeModal();
			}).fail(function (xhr) {
				var errorData = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : {};
				if (errorData.field) {
					showFieldError($form, errorData.field, errorData.message || config.error, errorData.tab || '');
				} else {
					message(errorData.message || config.error, true);
				}
			}).always(function () {
				$button.prop('disabled', false).text(originalLabel);
			});
		});

		$root.on('input change', '[data-coupon-modal-form] input, [data-coupon-modal-form] select, [data-coupon-modal-form] textarea', function () {
			var $container = $(this).closest('.has-validation-error');
			$container.removeClass('has-validation-error').find('.mpwpb-coupon-validation-error').remove();
		});

		$(document).on('click', '#mpwpb_coupon_list_result [data-filter-item]', function (event) {
			if ($(this).attr('data-filter-item') === 'trash') {
				return;
			}
			event.preventDefault();
			activeFilter = $(this).attr('data-filter-item') || 'all';
			$('#mpwpb_coupon_list_result [data-filter-item]').removeClass('ttbm_filter_btn_active_bg_color').addClass('ttbm_filter_btn_bg_color');
			$(this).removeClass('ttbm_filter_btn_bg_color').addClass('ttbm_filter_btn_active_bg_color');
			applyListFilter();
		});
		$(document).on('input', '[data-coupon-search]', applyListFilter);

		$(document).on('click', '[data-coupon-duplicate]', function (event) {
			event.preventDefault();
			var button = this;
			var $button = $(button).prop('disabled', true);
			$.post(config.ajaxUrl, {
				action: 'mpwpb_coupon_modal_duplicate',
				nonce: config.nonce,
				post_id: parseInt($button.attr('data-post-id'), 10) || 0
			}).done(function (response) {
				if (!response || !response.success) {
					window.alert((response && response.data && response.data.message) || config.error);
					return;
				}
				$('#mpwpb_coupon_list_result').html(response.data.listHtml);
				applyListFilter();
				openModal(response.data.postId, button);
			}).fail(function (xhr) {
				window.alert((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || config.error);
			}).always(function () {
				$button.prop('disabled', false);
			});
		});

		$(document).on('click', '[data-coupon-trash]', function (event) {
			event.preventDefault();
			if (!window.confirm(config.trashConfirm || 'Move this coupon to Trash?')) {
				return;
			}
			var $button = $(this).prop('disabled', true);
			$.post(config.ajaxUrl, {
				action: 'mpwpb_coupon_modal_trash',
				nonce: config.nonce,
				post_id: parseInt($button.attr('data-post-id'), 10) || 0
			}).done(function (response) {
				if (!response || !response.success) {
					window.alert((response && response.data && response.data.message) || config.error);
					return;
				}
				$('#mpwpb_coupon_list_result').html(response.data.listHtml);
				applyListFilter();
			}).fail(function (xhr) {
				window.alert((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || config.error);
			}).always(function () {
				$button.prop('disabled', false);
			});
		});

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape' && $root.children().length) {
				closeModal();
			}
		});
	});
})(jQuery);
