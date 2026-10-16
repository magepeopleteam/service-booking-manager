/* First-run onboarding wizard (Admin/MPWPB_Onboarding.php). */
(function ($) {
	'use strict';
	var cfg = window.mpwpbOb || {};
	var t = cfg.i18n || {};
	var $root = $('[data-mpwpb-ob]');
	if (!$root.length) { return; }

	var state = { type: '', template: '', created: null, demo: 0, payLabel: '' };
	var order = ['type', 'service', 'payment'];

	function post(action, data) {
		return $.post(cfg.ajaxUrl, $.extend({ action: action, nonce: cfg.nonce }, data || {}));
	}
	function busy(text) {
		$root.find('[data-ob-busy]').prop('hidden', !text).find('[data-ob-busy-text]').text(text || '');
	}
	function showError($step, msg) {
		$step.find('[data-ob-error]').text(msg || '').prop('hidden', !msg);
	}

	function go(name) {
		$root.find('[data-ob-step]').each(function () {
			var on = $(this).attr('data-ob-step') === name;
			$(this).prop('hidden', !on).toggleClass('is-active', on);
		});
		var idx = order.indexOf(name);
		$root.find('[data-ob-head]').prop('hidden', idx < 0);
		$root.find('[data-ob-dot]').each(function () {
			var i = order.indexOf($(this).attr('data-ob-dot'));
			$(this).toggleClass('is-current', i === idx).toggleClass('is-done', i < idx);
		});
		if (name === 'service') { renderTemplates(); }
		if (name === 'done') { renderDone(); }
		var $focus = $root.find('[data-ob-step="' + name + '"] h1, [data-ob-step="' + name + '"] h2').first();
		$focus.attr('tabindex', '-1').trigger('focus');
	}

	/* ---- Step 1: business type ---- */
	$root.on('click', '[data-ob-type]', function () {
		state.type = $(this).attr('data-ob-type');
		state.template = '';
		$root.find('[data-ob-type]').attr('aria-checked', 'false').removeClass('is-selected');
		$(this).attr('aria-checked', 'true').addClass('is-selected');
		$root.find('[data-ob-need-type]').prop('disabled', false);
	});

	/* ---- Step 2: template, scratch or demo ---- */
	function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

	function renderTemplates() {
		var list = (cfg.templates || []).filter(function (tpl) { return tpl.group === state.type; });
		var html = '';
		list.forEach(function (tpl) {
			var sel = tpl.key === state.template;
			html += '<button type="button" class="mpwpb-ob__tpl' + (sel ? ' is-selected' : '') + '" role="radio" aria-checked="' + (sel ? 'true' : 'false') + '" data-ob-tpl="' + esc(tpl.key) + '">' +
				'<span class="mpwpb-ob__tpl-icon" style="--tpl:' + esc(tpl.color) + '" aria-hidden="true"><span class="dashicons ' + esc(tpl.icon) + '"></span></span>' +
				'<span class="mpwpb-ob__tpl-body"><strong>' + esc(tpl.title) + '</strong><span>' + esc(tpl.subtitle) + '</span></span>' +
				'<span class="mpwpb-ob__tpl-count">' + esc((t.services || '%d services').replace('%d', tpl.count)) + '</span>' +
				'</button>';
		});
		var $box = $root.find('[data-ob-templates]');
		$box.attr('role', list.length ? 'radiogroup' : null).html(html || '<p class="mpwpb-ob__empty">' + esc(t.noTemplates) + '</p>');
		if (!list.length) { openScratch(true); }
		syncCreate();
	}

	function openScratch(open) {
		$root.find('[data-ob-scratch]').prop('hidden', !open);
		$root.find('[data-ob-scratch-toggle]').attr('aria-expanded', open ? 'true' : 'false').toggleClass('is-selected', open);
		if (open) {
			state.template = '';
			$root.find('[data-ob-tpl]').removeClass('is-selected').attr('aria-checked', 'false');
		}
		syncCreate();
	}

	function syncCreate() {
		var scratch = !$root.find('[data-ob-scratch]').prop('hidden');
		$root.find('[data-ob-create]').prop('disabled', !(state.template || scratch));
	}

	$root.on('click', '[data-ob-tpl]', function () {
		state.template = $(this).attr('data-ob-tpl');
		$root.find('[data-ob-tpl]').removeClass('is-selected').attr('aria-checked', 'false');
		$(this).addClass('is-selected').attr('aria-checked', 'true');
		$root.find('[data-ob-scratch]').prop('hidden', true);
		$root.find('[data-ob-scratch-toggle]').attr('aria-expanded', 'false').removeClass('is-selected');
		syncCreate();
	});
	$root.on('click', '[data-ob-scratch-toggle]', function () {
		openScratch($root.find('[data-ob-scratch]').prop('hidden'));
	});

	// A template or "from scratch" service is ready: finish the wizard and
	// open the Services list with that service selected.
	function afterService(summary) {
		var id = summary && summary.post_id;
		busy(t.working);
		post('mpwpb_onboarding_finish', { status: 'done' }).always(function () {
			window.location.href = cfg.servicesUrl + (id ? '&mpwpb_service=' + encodeURIComponent(id) : '');
		});
	}

	$root.on('click', '[data-ob-create]', function () {
		var $step = $root.find('[data-ob-step="service"]');
		showError($step, '');
		if (state.template) {
			busy(t.working);
			$.post(cfg.ajaxUrl, { action: 'mpwpb_import_business_template', nonce: cfg.templateNonce, template_key: state.template })
				.done(function (res) {
					if (!res || !res.success) { busy(''); showError($step, (res && res.data && res.data.message) || t.error); return; }
					afterService({ post_id: res.data.post_id });
				})
				.fail(function () { busy(''); showError($step, t.error); });
			return;
		}
		var $form = $root.find('[data-ob-scratch]');
		var missing = $form.find('[required]').filter(function () { return !$.trim($(this).val()); });
		if (missing.length) {
			missing.first().trigger('focus');
			showError($step, t.required);
			return;
		}
		var days = $form.find('input[name="days[]"]:checked').map(function () { return this.value; }).get();
		if (!days.length) { showError($step, t.pickDay); return; }
		busy(t.working);
		post('mpwpb_onboarding_create_service', {
			business: $form.find('[name="business"]').val(),
			service: $form.find('[name="service"]').val(),
			price: $form.find('[name="price"]').val(),
			duration: $form.find('[name="duration"]').val(),
			start: $form.find('[name="start"]').val(),
			end: $form.find('[name="end"]').val(),
			days: days
		}).done(function (res) {
			if (res && res.success) { afterService(res.data); return; }
			busy('');
			showError($step, (res && res.data && res.data.message) || t.error);
		}).fail(function () { busy(''); showError($step, t.error); });
	});

	// Demo import: the same one-service-per-request import the Service List widget uses.
	function importDemo($step, label, onDone) {
		showError($step, '');
		busy(label);
		(function run(step) {
			$.post(cfg.ajaxUrl, { action: 'mpwpb_import_dummy_step', nonce: cfg.dummyNonce, step: step })
				.done(function (res) {
					if (!res || !res.success) { busy(''); showError($step, t.error); return; }
					var d = res.data || {};
					busy(label + (d.total ? ' ' + Math.min(d.step, d.total) + '/' + d.total : ''));
					if (d.done) { onDone(d.total || 0); } else { run(d.step); }
				})
				.fail(function () { busy(''); showError($step, t.error); });
		})(0);
	}

	$root.on('click', '[data-ob-demo]', function () {
		importDemo($root.find('[data-ob-step="service"]'), t.importing, function (total) {
			state.created = null;
			state.demo = total;
			busy('');
			go('payment');
		});
	});

	// Welcome screen: import the full demo site in one click, finish the
	// wizard and open the Services page with everything ready.
	$root.on('click', '[data-ob-ready-site]', function () {
		importDemo($root.find('[data-ob-step="welcome"]'), t.readySite, function () {
			post('mpwpb_onboarding_finish', { status: 'done' }).always(function () {
				window.location.href = cfg.servicesUrl;
			});
		});
	});

	/* ---- Step 3: payments ---- */
	$root.on('click', '[data-ob-pay]', function () {
		if ($(this).prop('disabled')) { return; }
		$root.find('[data-ob-pay]').removeClass('is-selected').attr('aria-checked', 'false');
		$(this).addClass('is-selected').attr('aria-checked', 'true');
	});
	$root.on('click', '[data-ob-save-pay]', function () {
		var $step = $root.find('[data-ob-step="payment"]');
		var type = $root.find('[data-ob-pay].is-selected').attr('data-ob-pay') || '';
		showError($step, '');
		busy(t.working);
		post('mpwpb_onboarding_payment', { type: type }).done(function (res) {
			busy('');
			if (res && res.success) { state.payLabel = res.data.label; go('done'); return; }
			showError($step, (res && res.data && res.data.message) || t.error);
		}).fail(function () { busy(''); showError($step, t.error); });
	});

	/* ---- Done ---- */
	function renderDone() {
		var s = state.created;
		var $sum = $root.find('[data-ob-summary]');
		if (s) {
			$root.find('[data-ob-done-title]').text(t.liveTitle);
			$root.find('[data-ob-done-lead]').text(t.liveLead);
			$sum.prop('hidden', false);
			$sum.find('[data-ob-sum="title"]').text(s.title);
			$sum.find('[data-ob-sum="services"]').text(s.services);
			$sum.find('[data-ob-sum="from"]').text(s.from);
			$sum.find('[data-ob-sum-row="from"]').prop('hidden', !s.from);
			$sum.find('[data-ob-sum="hours"]').text(s.hours + (s.days ? ' · ' + s.days : ''));
			$sum.find('[data-ob-sum-row="hours"]').prop('hidden', !s.hours);
			$root.find('[data-ob-edit]').attr('href', s.edit_url).prop('hidden', !s.edit_url);
			$root.find('[data-ob-view]').attr('href', s.view_url).prop('hidden', !s.view_url);
		} else if (state.demo) {
			$root.find('[data-ob-done-title]').text(t.demoTitle);
			$root.find('[data-ob-done-lead]').text((t.demoBody || '').replace('%d', state.demo));
		}
		$sum.find('[data-ob-sum="pay"]').text(state.payLabel);
		$sum.find('[data-ob-sum-row="pay"]').prop('hidden', !state.payLabel);
		post('mpwpb_onboarding_finish', { status: 'done' });
	}

	/* ---- Navigation ---- */
	$root.on('click', '[data-ob-go]', function () {
		if ($(this).prop('disabled')) { return; }
		go($(this).attr('data-ob-go'));
	});
	// Skip & Explore: go straight to the plugin, like Eventin's -- the status
	// is recorded in the background (a beacon survives the page change).
	$root.on('click', '[data-ob-skip]', function () {
		var sent = false;
		if (window.navigator && navigator.sendBeacon && window.FormData) {
			var fd = new FormData();
			fd.append('action', 'mpwpb_onboarding_finish');
			fd.append('nonce', cfg.nonce);
			fd.append('status', 'skipped');
			sent = navigator.sendBeacon(cfg.ajaxUrl, fd);
		}
		if (!sent) {
			post('mpwpb_onboarding_finish', { status: 'skipped' });
		}
		window.location.href = cfg.servicesUrl;
	});
})(jQuery);
