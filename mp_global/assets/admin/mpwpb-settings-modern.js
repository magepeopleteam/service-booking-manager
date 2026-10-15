/**
 * Global Settings page (Admin/MPWPB_Settings_Global.php): grouped sidebar,
 * General split into setting cards, Yes/No dropdowns shown as switches, and
 * a save bar that appears once a tab has unsaved changes.
 *
 * Presentation only: every field stays the real Settings API input inside its
 * own <form> (rows are moved between tables, never out of the form), each tab
 * still saves through options.php, and the shared [data-tabs-target] tab
 * handler (mpwpb_plugin_global.js) keeps switching tabs.
 */
(function ($) {
	'use strict';

	var $page = $('.mpwpb_global_settings');
	if (!$page.length) {
		return;
	}
	var t = window.mpwpbSettingsModern || {};

	/* ---------- Sidebar: group the tabs ---------- */
	var groups = [
		[t.gBasics || 'Basics', ['#mpwpb_general_settings', '#mpwpb_global_settings', '#mpwpb_currency_settings']],
		[t.gPayments || 'Payments', ['#mpwpb_payment_method_settings', '#mpwpb_partial_payment_settings']],
		[t.gMessages || 'Customer messages', ['#mpwpb_email_settings', '#mpwpb_pdf_settings']],
		[t.gAppearance || 'Appearance', ['#mpwpb_style_settings', '#mpwpb_slider_settings', '#mpwpb_custom_css']],
		[t.gSecurity || 'Security & privacy', ['#mpwpb_gdpr_settings', '#mpwpb_2fa_settings', '#mpwpb_verification_settings']],
		[t.gIntegrations || 'Integrations', ['#mp_google_calendar_settings', '#mpwpb_google_sheet_settings']],
		[t.gAccount || 'Account', ['#mpwpb_license_settings']]
	];
	var $list = $page.find('.mpwpb-settings-sidebar .tabLists').first();
	if ($list.length) {
		var placed = {};
		var $frag = $('<div></div>');
		groups.forEach(function (g) {
			var $items = $();
			g[1].forEach(function (id) {
				var $li = $list.children('[data-tabs-target="' + id + '"]');
				if ($li.length) { $items = $items.add($li); placed[id] = true; }
			});
			if ($items.length) {
				$frag.append($('<li class="mpwpb-st-group" role="presentation"></li>').text(g[0])).append($items);
			}
		});
		// Tabs added by other code (e.g. Pro) that aren't in the map keep working under "More".
		var $rest = $list.children('[data-tabs-target]').filter(function () { return !placed[$(this).attr('data-tabs-target')]; });
		if ($rest.length) {
			$frag.append($('<li class="mpwpb-st-group" role="presentation"></li>').text(t.gMore || 'More')).append($rest);
		}
		$list.append($frag.children());
	}

	/* ---------- Page heading per tab ---------- */
	$list.children('[data-tabs-target]').each(function () {
		var id = $(this).attr('data-tabs-target');
		var $item = $page.find('.tabsItem[data-tabs="' + id + '"]');
		if ($item.length && !$item.children('.mpwpb-st-head').length) {
			$('<div class="mpwpb-st-head"></div>').append($('<h1></h1>').text($.trim($(this).text()))).prependTo($item);
		}
	});
	var intro = t.introGeneral;
	if (intro) {
		$page.find('.tabsItem[data-tabs="#mpwpb_general_settings"] .mpwpb-st-head').append($('<p></p>').text(intro));
	}

	/* ---------- General: split the one long table into cards ---------- */
	var $genForm = $page.find('.tabsItem[data-tabs="#mpwpb_general_settings"] > form');
	var $genTable = $genForm.children('table.form-table').first();
	if ($genTable.length) {
		var cards = [
			[t.cMenu || 'Menu & web address', ['label', 'slug', 'icon'], false],
			[t.cRules || 'Booking rules', ['buffer_time', 'cancellation_lead_time', 'reschedule_lead_time'], false],
			[t.cCheckout || 'Checkout & booking form', ['single_page_checkout', 'booking_widget_sticky_on_scrolling'], false],
			[t.cWords || 'Words & labels', ['category_label', 'category_slug', 'organizer_label', 'organizer_slug', 'category_text', 'sub_category_text', 'service_text'], true]
		];
		var $anchor = $genTable;
		cards.forEach(function (c) {
			var $rows = $();
			c[1].forEach(function (cls) { $rows = $rows.add($genTable.find('> tbody > tr.' + cls)); });
			if (!$rows.length) { return; }
			var $tbl = $('<table class="form-table mpwpb-st-table" role="presentation"><tbody></tbody></table>');
			$tbl.find('tbody').append($rows);
			var $card;
			if (c[2]) {
				$card = $('<details class="mpwpb-st-card mpwpb-st-card--fold"></details>');
				$card.append($('<summary></summary>').append($('<span class="mpwpb-st-card-title"></span>').text(c[0])).append($('<span class="mpwpb-st-card-note"></span>').text(' · ' + $rows.length + ' ' + (t.settings || 'settings'))));
			} else {
				$card = $('<section class="mpwpb-st-card"></section>').append($('<h2 class="mpwpb-st-card-title"></h2>').text(c[0]));
			}
			$card.append($tbl);
			$anchor.after($card);
			$anchor = $card;
		});
		// Anything not mapped (new fields) stays in a card of its own.
		if ($genTable.find('> tbody > tr').length) {
			$genTable.addClass('mpwpb-st-table').wrap('<section class="mpwpb-st-card"></section>');
		} else {
			$genTable.remove();
		}
	}

	/* ---------- Units after number inputs ---------- */
	var units = {
		'mpwpb_general_settings[buffer_time]': t.minutes || 'minutes',
		'mpwpb_general_settings[cancellation_lead_time]': t.hoursBefore || 'hours before the start',
		'mpwpb_general_settings[reschedule_lead_time]': t.hoursBefore || 'hours before the start'
	};
	$.each(units, function (name, unit) {
		var $in = $page.find('input[name="' + name + '"]');
		if ($in.length && !$in.next('.mpwpb-st-unit').length) {
			$in.addClass('mpwpb-st-num').after($('<span class="mpwpb-st-unit"></span>').text(unit));
		}
	});

	/* ---------- Yes/No dropdowns as switches ---------- */
	// A few "Disable …" settings read better as their positive: the switch is
	// shown inverted (On = the select's "no"); the saved value is unchanged.
	var inverted = {
		'mpwpb_general_settings[single_page_checkout]': t.onePageCheckout || 'One-page checkout',
		'mpwpb_global_settings[disable_block_editor]': t.useBlockEditor || 'Use the block editor for services'
	};
	var invertedHelp = {
		'mpwpb_general_settings[single_page_checkout]': t.onePageHelp || '',
		'mpwpb_global_settings[disable_block_editor]': t.blockEditorHelp || ''
	};
	$page.find('select').each(function () {
		var $sel = $(this);
		var vals = $sel.find('option').map(function () { return this.value; }).get().sort().join(',');
		if (vals !== 'no,yes' || $sel.data('stSwitch')) { return; }
		var name = $sel.attr('name') || '';
		var inv = Object.prototype.hasOwnProperty.call(inverted, name);
		var $row = $sel.closest('tr');
		var labelText = inv ? inverted[name] : $.trim($row.find('th label').clone().children().remove().end().text());
		if (inv) {
			var $lab = $row.find('th label');
			var $help = $lab.find('.info_text').detach();
			// The original help explains the old "Disable … Yes/No" wording, so it is replaced too.
			if (invertedHelp[name]) { $help = $('<i class="info_text"></i>').text(invertedHelp[name]); }
			$lab.empty().text(inverted[name]).append('<br>').append($help);
		}
		var $btn = $('<button type="button" class="mpwpb-st-switch" role="switch"><span></span></button>').attr('aria-label', labelText);
		function paint() {
			var on = ($sel.val() === 'yes') !== inv;
			$btn.attr('aria-checked', on ? 'true' : 'false');
		}
		$btn.on('click', function () {
			var on = $btn.attr('aria-checked') === 'true';
			$sel.val((!on) !== inv ? 'yes' : 'no').trigger('change');
			paint();
		});
		$sel.data('stSwitch', true).addClass('mpwpb-st-hidden-select').attr({ tabindex: '-1', 'aria-hidden': 'true' }).after($btn);
		$sel.on('change.stSwitch', paint);
		paint();
	});

	/* ---------- Save bar per tab (appears once something changes) ---------- */
	$page.find('.tabsItem > form').each(function () {
		var $form = $(this);
		var $submit = $form.find('input[type="submit"], button[type="submit"]').first();
		if (!$submit.length) { return; }
		var $bar = $('<div class="mpwpb-st-savebar" role="region"></div>').attr('aria-label', t.saveBar || 'Save changes')
			.append($('<span class="mpwpb-st-savebar-text"></span>').text(t.unsaved || 'You have unsaved changes'))
			.append($('<button type="button" class="mpwpb-st-discard"></button>').text(t.discard || 'Discard'))
			.append($('<button type="button" class="mpwpb-st-save"></button>').text(t.save || 'Save changes'));
		$submit.closest('.justifyBetween, p.submit').addClass('mpwpb-st-orig-submit');
		$form.append($bar);
		var $text = $bar.find('.mpwpb-st-savebar-text');
		function setDirty(on) {
			$form.toggleClass('is-dirty', on);
			$text.text(on ? (t.unsaved || 'You have unsaved changes') : (t.clean || 'No unsaved changes'));
			$bar.find('.mpwpb-st-discard').prop('hidden', !on);
		}
		function dirty() { setDirty(true); }
		$form.on('input change', ':input', dirty);
		$bar.on('click', '.mpwpb-st-save', function () { $submit.trigger('click'); });
		$bar.on('click', '.mpwpb-st-discard', function () {
			$form[0].reset();
			$form.find('select').trigger('change.stSwitch');
			setDirty(false);
		});
		setDirty(false);
	});
})(jQuery);
