/**
 * Modern service editor — shell behaviour only (stepper, save, per-user
 * switch, toast, featured image).
 *
 * IMPORTANT: This script drives ONLY the modern shell. All section
 * functionality (repeaters, AJAX-saved Service/Extra-Service/Category/FAQ
 * cards, schedule table, icon/image picker) is still driven by the existing
 * admin scripts (mpwpb_admin.js / mpwpb_admin_settings.js / mpwpb_plugin_global.js),
 * which remain enqueued. We never touch those nodes here beyond relocating
 * the real WP title/content fields (never duplicating them).
 */
(function ($) {
	'use strict';

	var cfg = window.mpwpbSme || {};

	/* ---------------------------------------------------------------- *
	 *  Per-user editor switch (works in both classic & modern screens)
	 * ---------------------------------------------------------------- */
	function setUi(ui) {
		if (!cfg.ajax) {
			return;
		}
		$.post(cfg.ajax, {
			action: 'mpwpb_set_service_edit_ui',
			nonce: cfg.nonce,
			ui: ui
		}).always(function () {
			window.location.reload();
		});
	}

	$(document).on('click', '[data-sme-ui]', function (e) {
		e.preventDefault();
		setUi($(this).data('sme-ui'));
	});

	/* ---------------------------------------------------------------- *
	 *  Everything below requires the modern shell to be present
	 * ---------------------------------------------------------------- */
	var $root = $('#mpwpb-sme');
	if (!$root.length) {
		return;
	}

	// Full-screen takeover hook for the <html> element (CSS removes the
	// admin-bar padding only when this class is present).
	document.documentElement.classList.add('mpwpb-sme-html');

	var $steps = $root.find('.mpwpb-sme__step');
	var order = $steps.map(function () { return $(this).data('sme-go'); }).get();
	var total = parseInt($root.data('total'), 10) || order.length;
	var cur = 0;

	// Remembers which wizard step was last open, keyed per edited post (the
	// URL's post= query arg already tells different services apart), so a
	// plain page refresh reopens that step instead of always restarting the
	// wizard at General.
	function smeStepStorageKey() {
		return 'mpwpb_sme_step::' + location.pathname + location.search;
	}

	function goStep(index) {
		if (index < 0) { index = 0; }
		if (index > order.length - 1) { index = order.length - 1; }
		cur = index;
		var name = order[cur];
		try { sessionStorage.setItem(smeStepStorageKey(), name); } catch (e) {}
		// Expose the current step so CSS can gate the rail's progressive
		// summary cards per step.
		$root.attr('data-step', name);

		$root.find('.mpwpb-sme__panel').each(function () {
			$(this).toggleClass('active', $(this).data('sme-panel') === name);
		});
		$steps.each(function () {
			var i = parseInt($(this).data('sme-index'), 10);
			$(this).toggleClass('active', i === cur).toggleClass('done', i < cur);
		});
		$root.find('.mpwpb-sme__conn').each(function () {
			var ci = parseInt($(this).data('sme-conn'), 10);
			$(this).toggleClass('done', ci <= cur);
		});
		$root.find('[data-sme-stepof]').text('Step ' + (cur + 1) + ' of ' + total);
		$root.find('[data-sme-prev]').prop('disabled', cur === 0);

		var $next = $root.find('[data-sme-next]');
		$next.text(cur === order.length - 1 ? (cfg.updateTxt || 'Update') : (cfg.nextTxt || 'Next Step'));

		var top = $root.offset() ? $root.offset().top - 60 : 0;
		$('html, body').animate({ scrollTop: top }, 200);

		// Repaint the relocated content editor when returning to General.
		if (name === 'general') {
			setTimeout(function () {
				if (window.tinymce) {
					var ed = tinymce.get('content');
					if (ed) { ed.execCommand('mceRepaint'); }
				}
			}, 50);
		}
	}

	$steps.on('click', function () {
		goStep(parseInt($(this).data('sme-index'), 10));
	});
	$root.on('click', '[data-sme-prev]', function () {
		if (cur > 0) { goStep(cur - 1); }
	});
	$root.on('click', '[data-sme-next]', function () {
		if (cur < order.length - 1) {
			goStep(cur + 1);
		} else {
			submitForm();
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Required-field guard, run before the real Publish/Update proxy
	 *  below. mpwpb_shortcode_title has no visible field in this editor
	 *  (see relocateServiceTitleFields() further down — it's discarded in
	 *  favour of the WP post title, which mpwpb_shortcode_title no longer
	 *  tracks), so the real WP title (#title, mirrored by the topbar
	 *  "Service name" input) stands in for it here instead. Featured Image
	 *  lives in the sticky rail and is checked directly.
	 * ---------------------------------------------------------------- */
	function goStepForPanel($panel) {
		if (!$panel.length) { return; }
		var stepName = $panel.data('sme-panel');
		var idx = order.indexOf(stepName);
		if (idx > -1 && idx !== cur) { goStep(idx); }
	}
	function focusInvalidField($field) {
		goStepForPanel($field.closest('.mpwpb-sme__panel[data-sme-panel]'));
		setTimeout(function () {
			var top = ($field.offset() ? $field.offset().top : 0) - 100;
			$('html, body').animate({ scrollTop: top }, 200);
			if ($field.is('input,textarea,select')) { $field.trigger('focus'); }
		}, 60);
	}
	function validateRequiredFields() {
		var $firstInvalid = null;
		var $titleField = ($svcNameInline && $svcNameInline.length) ? $svcNameInline : $svcName;
		var titleOk = !!$.trim($title.length ? ($title.val() || '') : '');
		if ($titleField && $titleField.length) {
			$titleField.toggleClass('mpwpb-sme__field-invalid', !titleOk);
			if (!titleOk && !$firstInvalid) { $firstInvalid = $titleField; }
		}
		var $thumbCard = $root.find('.mpwpb-sme__feat-card');
		var thumbOk = !!$('#mpwpb-sme-thumbnail').val();
		$thumbCard.toggleClass('mpwpb-sme__field-invalid', !thumbOk);
		if (!thumbOk && !$firstInvalid) { $firstInvalid = $thumbCard; }

		if ($firstInvalid) {
			toast(cfg.requiredTxt || 'Please fill in the required fields.');
			focusInvalidField($firstInvalid);
			return false;
		}
		return true;
	}

	/* ---------------------------------------------------------------- *
	 *  Save the complete native WordPress post form over AJAX. The server
	 *  passes it through core edit_post(), preserving every save_post hook
	 *  without navigating away from the current wizard step.
	 * ---------------------------------------------------------------- */
	var isSaving = false;
	var formDirty = false;
	var localDraftTimer = null;
	var localDraftPersisted = false;
	var localDraftRestoring = false;
	var localDraftReady = false;
	var localDraftMaxAge = 30 * 24 * 60 * 60 * 1000;
	var localDraftStorageKey = '';
	var changedDuringServerSave = false;
	var localDraftLastSignature = '';

	function eachEditor(callback) {
		if (!window.tinymce) { return; }
		var editors = window.tinymce.editors || (window.tinymce.EditorManager && window.tinymce.EditorManager.editors) || [];
		$.each(editors, function (key, editor) {
			if (editor) { callback(editor); }
		});
	}

	function editorsAreDirty() {
		var dirty = false;
		eachEditor(function (editor) {
			if (editor.isDirty && editor.isDirty()) { dirty = true; }
		});
		return dirty;
	}

	function markSaved() {
		formDirty = false;
		eachEditor(function (editor) {
			if (editor.setDirty) { editor.setDirty(false); }
		});
		clearLocalDraft();
	}

	function setSaveButtonsDisabled(disabled) {
		$root.find('[data-sme-save], [data-sme-next], [data-sme-save-as]')
			.prop('disabled', disabled)
			.toggleClass('disabled', disabled);
	}

	function applySavedState(data) {
		if (!data) { return; }
		if (data.postRevision) { cfg.postRevision = data.postRevision; }
		$('#post_status, #original_post_status').val(data.postStatus || '');
		if (data.postNonce) { $('#_wpnonce').val(data.postNonce); }
		if (data.formNonce) { $('input[name="mpwpb_nonce"]').val(data.formNonce); }
		if (data.editLock) { $('#active_post_lock').val(data.editLock); }
		$('#auto_draft').val('');

		$root.find('.mpwpb-sme__status-pill')
			.text(data.statusLabel || '')
			.toggleClass('is-published', !!data.isPublished)
			.toggleClass('is-draft', !data.isPublished);
		$root.find('[data-sme-save]').text(data.primaryLabel || (cfg.updateTxt || 'Update'));
		$root.find('[data-sme-save-as="draft"]').text(data.draftLabel || 'Save Draft');
		if (cur === order.length - 1) {
			$root.find('[data-sme-next]').text(data.primaryLabel || (cfg.updateTxt || 'Update'));
		}

		if (data.editUrl && window.history && window.history.replaceState) {
			window.history.replaceState({}, document.title, data.editUrl);
			document.body.classList.remove('post-new-php');
			document.body.classList.add('post-php');
		}
	}

	/* ---------------------------------------------------------------- *
	 *  Browser-only draft autosave
	 *
	 *  Persists the complete modern-editor form in localStorage. Nothing
	 *  in this path calls admin-ajax.php: WordPress' server autosave stays
	 *  suspended, while Publish/Update remains the explicit server write.
	 * ---------------------------------------------------------------- */
	function localDraftKey() {
		if (localDraftStorageKey) { return localDraftStorageKey; }
		var postId = cfg.postId || $('#post_ID').val() || $('input[name="mpwpb_sme_post_id"]').val() || 0;
		var identity = 'post-' + postId;
		if (document.body.classList.contains('post-new-php')) {
			var state = (window.history && window.history.state) || {};
			var token = state.mpwpbSmeDraftToken;
			if (!token) {
				token = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
				if (window.history && window.history.replaceState) {
					var nextState = $.extend({}, state, { mpwpbSmeDraftToken: token });
					window.history.replaceState(nextState, document.title, window.location.href);
				}
			}
			identity = 'new-' + token;
		}
		var sitePath = location.pathname.split('/wp-admin/')[0] || '/';
		localDraftStorageKey = 'mpwpb_sme_local_draft_v1::' + location.host + sitePath + '::' + (cfg.userId || 0) + '::' + identity;
		return localDraftStorageKey;
	}

	function setLocalDraftStatus(state, text) {
		var $status = $root.find('[data-sme-local-save]');
		if (!$status.length) { return; }
		$status.removeClass('is-ready is-saving is-saved is-error').addClass('is-' + state);
		$status.find('[data-sme-local-save-text]').text(text || '');
	}

	function localDraftFieldExcluded(field) {
		var type = (field.type || '').toLowerCase();
		var name = field.name || '';
		return !name ||
			type === 'button' || type === 'submit' || type === 'reset' ||
			type === 'file' || type === 'password' ||
			name === '_wpnonce' || name === '_wp_http_referer' || name === 'mpwpb_nonce' ||
			name === 'action' || name.indexOf('mpwpb_payment_method_settings') === 0;
	}

	function collectLocalDraftFields() {
		var occurrences = Object.create(null);
		var fields = [];
		$root.find(':input[name]').each(function () {
			if (localDraftFieldExcluded(this)) { return; }
			var name = this.name;
			var type = (this.type || this.tagName || '').toLowerCase();
			var index = occurrences[name] || 0;
			occurrences[name] = index + 1;
			var item = { name: name, index: index, type: type };
			if (type === 'checkbox' || type === 'radio') {
				item.checked = !!this.checked;
				item.value = $(this).val();
			} else if (this.tagName === 'SELECT' && this.multiple) {
				item.value = $(this).val() || [];
			} else {
				item.value = $(this).val();
			}
			fields.push(item);
		});
		return fields;
	}

	function collectLocalDraftEditors() {
		var editors = {};
		eachEditor(function (editor) {
			var element = editor.getElement ? editor.getElement() : null;
			if (element && $.contains($root[0], element) && editor.getContent) {
				editors[editor.id] = editor.getContent();
			}
		});
		return editors;
	}

	function collectLocalDraftRepeaters() {
		return $root.find('.mp_item_insert.mp_sortable_area').map(function (index) {
			return {
				index: index,
				count: $(this).children('.mp_remove_area').length
			};
		}).get();
	}

	function currentLocalDraftSignature() {
		return JSON.stringify({
			fields: collectLocalDraftFields(),
			editors: collectLocalDraftEditors(),
			repeaters: collectLocalDraftRepeaters(),
			heroUrl: $('#mpwpb-sme-hero-img').attr('src') || ''
		});
	}

	function writeLocalDraft() {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		var draft = {
			version: 1,
			timestamp: Date.now(),
			revision: cfg.postRevision || '',
			isNew: document.body.classList.contains('post-new-php'),
			fields: collectLocalDraftFields(),
			editors: collectLocalDraftEditors(),
			repeaters: collectLocalDraftRepeaters(),
			heroUrl: $('#mpwpb-sme-hero-img').attr('src') || ''
		};
		try {
			localStorage.setItem(localDraftKey(), JSON.stringify(draft));
			localDraftLastSignature = JSON.stringify({
				fields: draft.fields,
				editors: draft.editors,
				repeaters: draft.repeaters,
				heroUrl: draft.heroUrl
			});
			localDraftPersisted = true;
			setLocalDraftStatus('saved', cfg.localSavedTxt || 'Draft saved locally');
		} catch (error) {
			localDraftPersisted = false;
			setLocalDraftStatus('error', cfg.localUnavailableTxt || 'Local autosave unavailable');
		}
	}

	function scheduleLocalDraft() {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		localDraftPersisted = false;
		setLocalDraftStatus('saving', cfg.localSavingTxt || 'Saving locally…');
		clearTimeout(localDraftTimer);
		localDraftTimer = setTimeout(writeLocalDraft, 650);
	}

	function clearLocalDraft() {
		clearTimeout(localDraftTimer);
		localDraftPersisted = false;
		try { localStorage.removeItem(localDraftKey()); } catch (error) {}
		localDraftLastSignature = currentLocalDraftSignature();
		setLocalDraftStatus('ready', cfg.localReadyTxt || 'Local autosave ready');
	}

	function restoreLocalDraftRepeaters(repeaters) {
		if (!$.isArray(repeaters)) { return; }
		var $containers = $root.find('.mp_item_insert.mp_sortable_area');
		$.each(repeaters, function (_, repeater) {
			var $container = $containers.eq(parseInt(repeater.index, 10));
			var wanted = Math.max(0, parseInt(repeater.count, 10) || 0);
			if (!$container.length) { return; }
			var current = $container.children('.mp_remove_area').length;
			if (wanted < current) {
				$container.children('.mp_remove_area').slice(wanted).remove();
				return;
			}
			var $add = $container.closest('.mp_settings_area').find('.mp_add_item').first();
			while ($add.length && current < wanted) {
				var previous = current;
				$add.trigger('click');
				current = $container.children('.mp_remove_area').length;
				// A malformed/missing repeater template must never create a loop.
				if (current >= wanted || current <= previous) { break; }
			}
		});
	}

	function restoreLocalDraftFields(fields) {
		if (!$.isArray(fields)) { return; }
		$.each(fields, function (_, item) {
			var $field = $root.find(':input[name]').filter(function () {
				return this.name === item.name;
			}).eq(parseInt(item.index, 10) || 0);
			if (!$field.length || localDraftFieldExcluded($field[0])) { return; }
			if (item.type === 'checkbox' || item.type === 'radio') {
				$field.prop('checked', !!item.checked);
			} else {
				$field.val(item.value);
			}
		});
	}

	function restoreLocalDraftEditors(editors, attempt) {
		if (!editors || typeof editors !== 'object') { return; }
		if (!window.tinymce) {
			if (attempt < 12) {
				setTimeout(function () { restoreLocalDraftEditors(editors, attempt + 1); }, 250);
			}
			return;
		}
		var pending = false;
		$.each(editors, function (id, content) {
			var editor = tinymce.get(id);
			if (editor && editor.setContent) {
				editor.setContent(content || '');
				if (editor.setDirty) { editor.setDirty(true); }
			} else {
				pending = true;
			}
		});
		if (pending && attempt < 12) {
			setTimeout(function () { restoreLocalDraftEditors(editors, attempt + 1); }, 250);
		}
	}

	function syncRestoredLocalDraftUi(draft) {
		var restoredTitle = $('#mpwpb-sme-title').val() || $('#mpwpb-sme-title-inline').val() || '';
		$('#title, #mpwpb-sme-title, #mpwpb-sme-title-inline').val(restoredTitle);

		$root.find('input[type="checkbox"][data-collapse-target]').each(function () {
			var $field = $(this);
			var $target = $root.find('[data-collapse="' + $field.data('collapse-target') + '"]');
			$target.toggleClass('mActive', this.checked).toggle(this.checked);
		});
		$root.find('select[data-collapse-target]').each(function () {
			$(this).find('option[data-option-target], option[data-option-target-multi]').each(function () {
				var option = this;
				var targets = $(option).attr('data-option-target-multi') || $(option).attr('data-option-target') || '';
				$.each(targets.toString().split(/\s+/), function (_, target) {
					if (!target) { return; }
					$root.find('[data-collapse="' + target + '"]')
						.toggleClass('mActive', option.selected).toggle(option.selected);
				});
			});
		});
		$root.find('select.select2-hidden-accessible').trigger('change.select2');
		$root.find('.mpwpb-staff-tile input[type="checkbox"]').each(function () {
			$(this).closest('.mpwpb-staff-tile').toggleClass('is-selected', this.checked);
		});

		var thumbnailId = $('#mpwpb-sme-thumbnail').val();
		if (thumbnailId || draft.heroUrl) {
			setHero(thumbnailId, draft.heroUrl || $('#mpwpb-sme-hero-img').attr('src') || '');
		} else {
			setHero('', '');
		}
	}

	function restoreLocalDraft() {
		var raw;
		try { raw = localStorage.getItem(localDraftKey()); } catch (error) {
			setLocalDraftStatus('error', cfg.localUnavailableTxt || 'Local autosave unavailable');
			return false;
		}
		if (!raw) { return false; }
		try {
			var draft = JSON.parse(raw);
			if (!draft || draft.version !== 1 || !draft.timestamp || Date.now() - draft.timestamp > localDraftMaxAge) {
				clearLocalDraft();
				return false;
			}
			if (!draft.isNew && draft.revision && cfg.postRevision && draft.revision !== cfg.postRevision) {
				clearLocalDraft();
				return false;
			}
			localDraftRestoring = true;
			restoreLocalDraftRepeaters(draft.repeaters);
			restoreLocalDraftFields(draft.fields);
			restoreLocalDraftEditors(draft.editors, 0);
			syncRestoredLocalDraftUi(draft);
			localDraftRestoring = false;
			localDraftPersisted = true;
			formDirty = true;
			setLocalDraftStatus('saved', cfg.localRestoredTxt || 'Local draft restored');
			toast(cfg.localRestoredTxt || 'Local draft restored');
			return true;
		} catch (error) {
			localDraftRestoring = false;
			clearLocalDraft();
			return false;
		}
	}

	function suspendServerAutosave(attempt) {
		attempt = attempt || 0;
		if (window.wp && wp.autosave && wp.autosave.server && wp.autosave.server.suspend) {
			wp.autosave.server.suspend();
			return;
		}
		if (attempt < 20) {
			setTimeout(function () { suspendServerAutosave(attempt + 1); }, 250);
		}
	}

	function saveForm(mode) {
		if (isSaving || !cfg.ajax) { return; }
		if (mode !== 'draft' && !validateRequiredFields()) { return; }

		var form = document.getElementById('post');
		if (!form || !window.FormData) { return; }
		if (window.tinyMCE) { window.tinyMCE.triggerSave(); }

		var data = new FormData(form);
		data.set('action', 'mpwpb_save_service_editor');
		data.set('save_mode', mode === 'draft' ? 'draft' : 'primary');
		isSaving = true;
		changedDuringServerSave = false;
		setSaveButtonsDisabled(true);
		toast(cfg.savingTxt || 'Saving…');
		if (window.wp && wp.autosave && wp.autosave.server) {
			wp.autosave.server.suspend();
		}

		$.ajax({
			url: cfg.ajax,
			type: 'POST',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json'
		}).done(function (resp) {
			if (resp && resp.success) {
				applySavedState(resp.data);
				markSaved();
				if (resp.data && resp.data.postId) {
					cfg.postId = resp.data.postId;
					localDraftStorageKey = '';
				}
				toast((resp.data && resp.data.message) || cfg.savedTxt || 'Saved');
				$root.trigger('mpwpb:sme-saved', [resp.data]);
			} else {
				toast((resp && resp.data && resp.data.message) || cfg.saveErrorTxt || 'The service could not be saved.');
			}
		}).fail(function (xhr) {
			var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
			toast(message || cfg.saveErrorTxt || 'The service could not be saved.');
		}).always(function () {
			isSaving = false;
			setSaveButtonsDisabled(false);
			suspendServerAutosave();
			if (changedDuringServerSave) {
				formDirty = true;
				scheduleLocalDraft();
			}
		});
	}

	function submitForm() {
		saveForm('primary');
	}
	$root.on('click', '[data-sme-save]', function (e) {
		e.preventDefault();
		submitForm();
	});

	/* ---------------------------------------------------------------- *
	 *  Split-button dropdown — one extra option, always the opposite of
	 *  whatever the primary button already does ("Update"/"Publish"),
	 *  plus "Classic editor". "Save as Draft"/"Switch to Draft" sends
	 *  WordPress' core 'saveasdraft' intent through the same AJAX save, so
	 *  post_status ends up 'draft' regardless of the primary action.
	 * ---------------------------------------------------------------- */
	var $split = $root.find('[data-sme-split]');
	var $splitToggle = $split.find('[data-sme-split-toggle]');
	var $splitMenu = $split.find('[data-sme-split-menu]');

	function closeSplitMenu() {
		$splitMenu.attr('hidden', true);
		$splitToggle.attr('aria-expanded', 'false');
	}
	function openSplitMenu() {
		$splitMenu.removeAttr('hidden');
		$splitToggle.attr('aria-expanded', 'true');
	}

	$splitToggle.on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		if ($splitMenu.attr('hidden')) { openSplitMenu(); } else { closeSplitMenu(); }
	});
	$(document).on('click', function (e) {
		if ($split.length && !$split.is(e.target) && $split.has(e.target).length === 0) {
			closeSplitMenu();
		}
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' || e.keyCode === 27) { closeSplitMenu(); }
	});

	function submitFormAs(status) {
		saveForm(status === 'draft' ? 'draft' : 'primary');
	}
	$splitMenu.on('click', '[data-sme-save-as]', function (e) {
		e.preventDefault();
		closeSplitMenu();
		submitFormAs($(this).data('sme-save-as'));
	});
	$splitMenu.on('click', '[data-sme-ui]', function () {
		closeSplitMenu();
	});

	/* ---------------------------------------------------------------- *
	 *  Preview intentionally has no JavaScript click proxy. The visible
	 *  anchor already contains WordPress' canonical preview URL and target;
	 *  allowing the browser's trusted navigation keeps left-, middle- and
	 *  keyboard activation consistent and avoids popup blocking.
	 * ---------------------------------------------------------------- */

	/* ---------------------------------------------------------------- *
	 *  Service name <-> hidden WP #title sync (title box is CSS-hidden).
	 *  Two visual proxies (topbar + the inline "Title" field in the Basic
	 *  Information card) both mirror the one real #title input.
	 * ---------------------------------------------------------------- */
	var $title = $('#title');
	var $svcName = $('#mpwpb-sme-title'); // the editable topbar title
	var $svcNameInline = $('#mpwpb-sme-title-inline'); // inline "Title" field
	if ($title.length && ($svcName.length || $svcNameInline.length)) {
		if ($svcName.length && !$svcName.val() && $title.val()) {
			$svcName.val($title.val());
		}
		if ($svcNameInline.length && !$svcNameInline.val() && $title.val()) {
			$svcNameInline.val($title.val());
		}
		$svcName.add($svcNameInline).on('input', function () {
			var val = $(this).val();
			$title.val(val);
			$svcName.add($svcNameInline).not(this).val(val);
			$('#title-prompt-text').addClass('screen-reader-text');
			$svcName.add($svcNameInline).toggleClass('mpwpb-sme__field-invalid', !$.trim(val));
		});
	}

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Overview" editor (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details() further
	 *  down this same step) up into the Basic Information card — a real DOM
	 *  node moved via appendTo(), not a duplicate render, so
	 *  mpwpb_service_overview_content still submits exactly once. The
	 *  now-empty heading + toggle wrapper left behind in the Service
	 *  Details card are discarded entirely (including the real classic
	 *  on/off checkbox) — the modern editor always submits Service Overview
	 *  as "on" via a hidden field placed in Basic Information instead
	 *  (Classic mode's own toggle is untouched and still fully functional
	 *  there).
	 * ---------------------------------------------------------------- */
	(function relocateServiceOverview() {
		var $overviewSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-overview');
		var $contentSlot = $root.find('[data-sme-overview-slot]');
		if (!$overviewSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleSection = $overviewSection.prev();
		var $headingSection = $toggleSection.prev();

		$overviewSection.appendTo($contentSlot);
		$toggleSection.remove();
		$headingSection.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate "Service Sub Title" (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings() further down this same
	 *  step) up into the Basic Information card — only the real <textarea>
	 *  is moved (so mpwpb_shortcode_sub_title still submits exactly once);
	 *  the classic section wrapper + its own label text are decorative-only
	 *  once the field is gone, so it's removed to avoid an empty leftover
	 *  row. "Service Title" is discarded outright, not relocated — the
	 *  modern editor doesn't show it at all (Basic Information carries a
	 *  hidden field preserving its existing value instead, so nothing is
	 *  lost on save); Classic mode's own field is untouched.
	 * ---------------------------------------------------------------- */
	(function relocateServiceTitleFields() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		if (!$card.length) {
			return;
		}
		$card.find('.service-title').remove();

		var $subTitleSection = $card.find('.service-sub-title');
		var $subTitleSlot = $root.find('[data-sme-service-subtitle-slot]');
		if ($subTitleSection.length && $subTitleSlot.length) {
			$subTitleSection.find('textarea[name="mpwpb_shortcode_sub_title"]').appendTo($subTitleSlot);
			$subTitleSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate a reused section's own on/off switch (e.g. Extra Service's
	 *  real "mpwpb_extra_service_active" checkbox) up into the card header,
	 *  next to the title — moving the real .roundSwitchLabel node (not a
	 *  copy), so the checkbox is still submitted exactly once regardless
	 *  of where in the DOM it visually sits.
	 * ---------------------------------------------------------------- */
	(function relocateHeaderToggles() {
		var sectionsWithHeaderToggle = {
			'MPWPB_Extra_Service_Modern': 'mpwpb_extra_service_active',
			'MPWPB_Service_Details': 'mpwpb_service_details_status',
			'MPWPB_Faq_Settings': 'mpwpb_faq_active',
			'Tax_Settings': 'mpwpb_tax_enabled',
			'MPWPB_Happy_Hours_Settings': 'mpwpb_happy_hours_enabled'
		};
		Object.keys(sectionsWithHeaderToggle).forEach(function (sectionClass) {
			var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
			var $slot = $card.find('[data-sme-header-actions]').first();
			var $toggle = $card.find('input[name="' + sectionsWithHeaderToggle[sectionClass] + '"]').closest('.roundSwitchLabel');
			if ($slot.length && $toggle.length) {
				$toggle.appendTo($slot);
			}
		});
	})();

	/* ---------------------------------------------------------------- *
	 *  Clean up what's left in the Service Details card after Service
	 *  Features and Service Review were relocated elsewhere and its own
	 *  toggle moved to the card header above:
	 *   1) the classic "Enable Service Details" row (label text + toggle,
	 *      now toggle-less) — only removed once confirmed empty (no
	 *      checkbox left inside), so this is a no-op if the header-toggle
	 *      relocation didn't run.
	 *   2) the "Service Details" sub-heading — now redundant with this
	 *      card's own title, since the editor below is the only content
	 *      left in the card. Safe to remove even though it's a
	 *      section.section: Service Features' (hidden, further up, never
	 *      removed) remains the true first-of-type in this card either way.
	 * ---------------------------------------------------------------- */
	(function cleanupServiceDetailsCard() {
		var $detailsSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-details');
		if (!$detailsSection.length) {
			return;
		}
		var $toggleRow = $detailsSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
		var $headingSection = $detailsSection.prev();
		if ($headingSection.length && $headingSection.is('section.section')) {
			$headingSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  The classic "Enable FAQ Section" row (label text + the toggle just
	 *  relocated to the FAQ card header above) would otherwise be left
	 *  behind as an empty leftover line. Only removed once confirmed empty
	 *  (no checkbox left inside), so it's a no-op if the header-toggle
	 *  relocation didn't run.
	 * ---------------------------------------------------------------- */
	(function cleanupFaqToggleRow() {
		var $faqSection = $root.find('[data-sme-section="MPWPB_Faq_Settings"] .mpwpb-faq-section');
		if (!$faqSection.length) {
			return;
		}
		var $toggleRow = $faqSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  The classic "Enable Tax" row (label text + the toggle just
	 *  relocated to the Tax Settings card header above) would otherwise be
	 *  left behind as an empty leftover line. Only removed once confirmed
	 *  empty (no checkbox left inside), so it's a no-op if the
	 *  header-toggle relocation didn't run.
	 * ---------------------------------------------------------------- */
	(function cleanupTaxToggleRow() {
		var $taxClassRow = $root.find('[data-sme-section="Tax_Settings"] #mpwpb_tax_class_row');
		if (!$taxClassRow.length) {
			return;
		}
		var $toggleRow = $taxClassRow.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Same cleanup as cleanupTaxToggleRow(), for the "Enable Happy
	 *  Hours" row after its toggle is relocated to the card header.
	 * ---------------------------------------------------------------- */
	(function cleanupHappyHoursToggleRow() {
		var $row = $root.find('[data-sme-section="MPWPB_Happy_Hours_Settings"] #mpwpb_happy_hours_row');
		if (!$row.length) {
			return;
		}
		var $toggleRow = $row.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Happy Hours rule builder. Move the four original classic labels into
	 *  two modern groups, keeping every real input/name intact. The summary
	 *  below updates immediately as the rule changes.
	 * ---------------------------------------------------------------- */
	(function modernizeHappyHours() {
		var $card = $root.find('[data-sme-section="MPWPB_Happy_Hours_Settings"]');
		var $row = $card.find('#mpwpb_happy_hours_row');
		var $fields = $row.children('label.label');
		if (!$row.length || $fields.length < 4 || $row.data('sme-modernized')) {
			return;
		}
		$row.data('sme-modernized', true).addClass('mpwpb-hh');

		function group(icon, title, subtitle, modifier) {
			return $('<div class="mpwpb-hh__group ' + modifier + '">' +
				'<div class="mpwpb-hh__group-head">' +
					'<span class="dashicons ' + icon + ' mpwpb-hh__group-icon" aria-hidden="true"></span>' +
					'<div><h4></h4><p></p></div>' +
				'</div>' +
				'<div class="mpwpb-hh__fields"></div>' +
			'</div>')
				.find('h4').text(title).end()
				.find('.mpwpb-hh__group-head p').text(subtitle).end();
		}

		var $layout = $('<div class="mpwpb-hh__layout"></div>');
		var $windowGroup = group('dashicons-clock', cfg.hhWindowTitle || 'Time Window', cfg.hhWindowSub || 'Choose when the special price applies.', 'mpwpb-hh__group--window');
		var $offerGroup = group('dashicons-tickets-alt', cfg.hhOfferTitle || 'Discount Offer', cfg.hhOfferSub || 'Set the discount customers receive.', 'mpwpb-hh__group--offer');

		$fields.eq(0).addClass('mpwpb-hh__field mpwpb-hh__field--start').appendTo($windowGroup.find('.mpwpb-hh__fields'));
		$fields.eq(1).addClass('mpwpb-hh__field mpwpb-hh__field--end').appendTo($windowGroup.find('.mpwpb-hh__fields'));
		$fields.eq(2).addClass('mpwpb-hh__field mpwpb-hh__field--type').appendTo($offerGroup.find('.mpwpb-hh__fields'));
		$fields.eq(3).addClass('mpwpb-hh__field mpwpb-hh__field--value').appendTo($offerGroup.find('.mpwpb-hh__fields'));

		var $valueControl = $offerGroup.find('.mpwpb-hh__field--value > div').last().addClass('mpwpb-hh__value-control');
		var $suffix = $('<span class="mpwpb-hh__value-suffix" aria-hidden="true"></span>').appendTo($valueControl);
		var $preview = $('<div class="mpwpb-hh__preview">' +
			'<span class="dashicons dashicons-megaphone mpwpb-hh__preview-icon" aria-hidden="true"></span>' +
			'<div class="mpwpb-hh__preview-copy"><span class="mpwpb-hh__preview-label"></span><strong><span data-hh-preview-value></span> <span class="mpwpb-hh__preview-discount"></span> &middot; <span data-hh-preview-time></span></strong></div>' +
			'<span class="mpwpb-hh__preview-badge"></span>' +
		'</div>');
		$preview.find('.mpwpb-hh__preview-label').text(cfg.hhRuleLabel || 'Active pricing rule');
		$preview.find('.mpwpb-hh__preview-discount').text(cfg.hhDiscountTxt || 'discount');
		$preview.find('.mpwpb-hh__preview-badge').text(cfg.hhAutomaticTxt || 'Automatic');
		$layout.append($windowGroup, $offerGroup);
		$row.append($layout, $preview);

		function updateRulePreview() {
			var start = $row.find('[name="mpwpb_happy_hours_start_time"]').val() || '--:--';
			var end = $row.find('[name="mpwpb_happy_hours_end_time"]').val() || '--:--';
			var type = $row.find('[name="mpwpb_happy_hours_discount_type"]').val();
			var value = $row.find('[name="mpwpb_happy_hours_discount_value"]').val() || '0';
			var suffix = type === 'percent' ? '%' : (cfg.hhCurrencySymbol || '');
			$valueControl.toggleClass('is-prefix', type === 'fixed' && !!cfg.hhCurrencySymbol);
			$suffix.text(suffix).toggle(!!suffix);
			$preview.find('[data-hh-preview-value]').text(type === 'fixed' && cfg.hhCurrencySymbol ? cfg.hhCurrencySymbol + value : value + (type === 'percent' ? '%' : ''));
			$preview.find('[data-hh-preview-time]').text(start + ' – ' + end);
		}

		$row.on('input change', 'input, select', updateRulePreview);
		updateRulePreview();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the "Enable Recurring Bookings" / "Enable Staff Member Add"
	 *  toggles (Pro-only cards, rendered normally by the reused classic
	 *  methods) up into their own card headers, next to the title — same
	 *  pattern as the other header-toggle relocations above, just written
	 *  as dedicated functions since neither card has a uniquely-classed
	 *  content section to anchor a .prev() cleanup off of. Instead, the
	 *  toggle's original wrapping <section> is captured by reference
	 *  *before* the toggle itself is moved out of it, so it can be safely
	 *  discarded afterwards regardless of DOM order (it never held anything
	 *  but decorative label text once the switch is gone).
	 * ---------------------------------------------------------------- */
	function relocateToggleToCardHeader(sectionClass, inputName) {
		var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
		var $slot = $card.find('[data-sme-header-actions]').first();
		if (!$card.length || !$slot.length) {
			return;
		}
		var $toggleInput = $card.find('input[name="' + inputName + '"]');
		var $toggleRow = $toggleInput.closest('section');
		var $toggle = $toggleInput.closest('.roundSwitchLabel');
		if ($toggle.length) {
			$toggle.appendTo($slot);
		}
		if ($toggleRow.length) {
			$toggleRow.remove();
		}
	}
	relocateToggleToCardHeader('MPWPB_Recurring_Booking_Settings', 'mpwpb_enable_recurring');
	relocateToggleToCardHeader('Staff_Member', 'mpwpb_staff_member_add');

	/* ---------------------------------------------------------------- *
	 *  Recurring Booking — convert the remaining classic rows into a repeat
	 *  pattern picker plus a limits/discount panel. Original controls move;
	 *  names and values are never duplicated.
	 * ---------------------------------------------------------------- */
	(function modernizeRecurringBooking() {
		var $card = $root.find('[data-sme-section="MPWPB_Recurring_Booking_Settings"]');
		var $tab = $card.find('.tabsItem').first();
		var $sections = $tab.children('section').not('.section');
		if (!$tab.length || $sections.length < 3 || $tab.data('sme-modernized')) { return; }
		$tab.data('sme-modernized', true).addClass('mpwpb-recur');

		var $pattern = $('<div class="mpwpb-recur__group mpwpb-recur__group--pattern"><div class="mpwpb-recur__head"><span class="dashicons dashicons-update" aria-hidden="true"></span><div><h4></h4><p></p></div></div><div class="mpwpb-recur__pattern-body"></div></div>');
		var $limits = $('<div class="mpwpb-recur__group mpwpb-recur__group--limits"><div class="mpwpb-recur__head"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><div><h4></h4><p></p></div></div><div class="mpwpb-recur__limit-fields"></div></div>');
		$pattern.find('h4').text(cfg.recurPatternTitle || 'Repeat Pattern');
		$pattern.find('.mpwpb-recur__head p').text(cfg.recurPatternSub || 'Choose the recurrence options customers can use.');
		$limits.find('h4').text(cfg.recurLimitsTitle || 'Limits & Incentive');
		$limits.find('.mpwpb-recur__head p').text(cfg.recurLimitsSub || 'Control the maximum series length and optional discount.');

		$sections.eq(0).addClass('mpwpb-recur__patterns').appendTo($pattern.find('.mpwpb-recur__pattern-body'));
		$sections.eq(1).addClass('mpwpb-recur__field mpwpb-recur__field--count').appendTo($limits.find('.mpwpb-recur__limit-fields'));
		$sections.eq(2).addClass('mpwpb-recur__field mpwpb-recur__field--discount').appendTo($limits.find('.mpwpb-recur__limit-fields'));
		$limits.find('[name="mpwpb_recurring_discount"]').wrap('<div class="mpwpb-recur__discount-control"></div>').after('<span aria-hidden="true">%</span>');

		var $layout = $('<div class="mpwpb-recur__layout"></div>').append($pattern, $limits);
		var $summary = $('<div class="mpwpb-recur__summary"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><div><small></small><strong><span data-recur-pattern></span> &middot; <span data-recur-count></span> &middot; <span data-recur-discount></span></strong></div></div>');
		$summary.find('small').text(cfg.recurRuleLabel || 'Booking series');
		var $toggle = $card.find('[name="mpwpb_enable_recurring"]');
		var isEnabled = $toggle.is(':checked');
		var $body = $('<div class="mpwpb-recur__body" data-collapse="#mpwpb_enable_recurring"></div>')
			.toggleClass('mActive', isEnabled)
			.css('display', isEnabled ? 'block' : 'none')
			.attr('aria-hidden', isEnabled ? 'false' : 'true')
			.append($layout, $summary);
		$tab.append($body);
		$toggle.on('change.mpwpb-sme-recurring', function () {
			var enabled = $(this).is(':checked');
			$body.attr('aria-hidden', enabled ? 'false' : 'true');
		});

		function updateRecurringSummary() {
			var patterns = [];
			$tab.find('[name="mpwpb_recurring_types[]"]:checked').each(function () {
				patterns.push($(this).next('.customCheckbox').text());
			});
			var count = $tab.find('[name="mpwpb_max_recurring_count"]').val() || '0';
			var discount = $tab.find('[name="mpwpb_recurring_discount"]').val() || '0';
			$summary.find('[data-recur-pattern]').text(patterns.join(', ') || cfg.recurNoneTxt || 'Select a repeat pattern');
			$summary.find('[data-recur-count]').text(count + ' ' + (cfg.recurOccurrencesTxt || 'occurrences maximum'));
			$summary.find('[data-recur-discount]').text(discount + '% ' + (cfg.recurDiscountTxt || 'recurring discount'));
		}
		$tab.on('input change', 'input', updateRecurringSummary);
		updateRecurringSummary();
	})();

	/* ---------------------------------------------------------------- *
	 *  Tax Settings — modern two-field configuration with a live class/rate
	 *  summary, using the original select and number input.
	 * ---------------------------------------------------------------- */
	(function modernizeTaxSettings() {
		var $card = $root.find('[data-sme-section="Tax_Settings"]');
		var $row = $card.find('#mpwpb_tax_class_row');
		var $fields = $row.children('label.label');
		if (!$row.length || $fields.length < 2 || $row.data('sme-modernized')) { return; }
		$row.data('sme-modernized', true).addClass('mpwpb-tax-modern');
		$fields.addClass('mpwpb-tax-modern__field').wrapAll('<div class="mpwpb-tax-modern__fields"></div>');
		var $rateControl = $row.find('#mpwpb_tax_rate_row > div').last().addClass('mpwpb-tax-modern__rate-control');
		$rateControl.append('<span aria-hidden="true">%</span>');
		var $summary = $('<div class="mpwpb-tax-modern__summary"><span class="dashicons dashicons-shield" aria-hidden="true"></span><div><small></small><strong><span data-tax-class></span> &middot; <span data-tax-rate></span></strong></div><span class="mpwpb-tax-modern__badge"></span></div>');
		$summary.find('small').text(cfg.taxRuleLabel || 'Applied tax rule');
		$summary.find('.mpwpb-tax-modern__badge').text(cfg.taxAutomaticTxt || 'Automatic');
		$row.append($summary);
		function updateTaxSummary() {
			var className = $row.find('[name="mpwpb_tax_class"] option:selected').text();
			var rate = $row.find('[name="mpwpb_tax_rate"]').val() || '0';
			$summary.find('[data-tax-class]').text(className);
			$summary.find('[data-tax-rate]').text(rate + '%');
		}
		$row.on('input change', 'input, select', updateTaxSummary);
		updateTaxSummary();
	})();

	/* ---------------------------------------------------------------- *
	 *  Staff Member — retain the existing avatar tile selector and AJAX save,
	 *  adding modern card integration plus live selection feedback.
	 * ---------------------------------------------------------------- */
	(function modernizeStaffSelection() {
		var $card = $root.find('[data-sme-section="Staff_Member"]');
		var $container = $card.find('#mpwpb_add_staff_container');
		var $grid = $container.find('.mpwpb-staff-grid');
		if (!$container.length || $container.data('sme-modernized')) { return; }
		$container.data('sme-modernized', true).addClass('mpwpb-staff-modern');
		var $head = $('<div class="mpwpb-staff-modern__head"><div><span class="dashicons dashicons-groups" aria-hidden="true"></span><div><h4></h4><p></p></div></div><strong data-staff-count></strong></div>');
		$head.find('h4').text(cfg.staffSelectTitle || 'Select Staff');
		$head.find('p').text(cfg.staffSelectSub || 'Choose the team members customers can book.');
		$grid.attr('data-empty', cfg.staffNoneTxt || 'No staff members available.');
		$container.find('.mpwpb_add_staff_container > label').hide();
		$container.find('.mpwpb_add_staff_container').prepend($head);
		function updateStaffCount() {
			var count = $grid.find('.mpwpb-staff-tile.is-selected').length;
			$head.find('[data-staff-count]').text(count + ' ' + (cfg.staffSelectedTxt || 'staff selected'));
		}
		$card.find('#mpwpb_staff_selector').on('change.mpwpb-sme-staff', function () {
			setTimeout(updateStaffCount, 0);
		});
		updateStaffCount();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Features" repeater (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details()) out of
	 *  the Service Details card into its own "Service Feature Details"
	 *  card — a real DOM node moved via appendTo(), not a duplicate render,
	 *  so mpwpb_features[] still submits exactly once. Its "Enable Service
	 *  Features" toggle moves to the new card's header (next to the title)
	 *  instead of staying with Service Details, since it now gates this
	 *  card's content, not that one. The now-empty toggle row left behind
	 *  is discarded (decorative only, no form fields); its heading row is
	 *  left in place (already hidden via CSS's section.section:first-of-
	 *  type rule) rather than removed — removing it would make the *next*
	 *  section.section (Service Details' own heading) become the new
	 *  first-of-type and get hidden by that same rule instead.
	 * ---------------------------------------------------------------- */
	(function relocateServiceFeatures() {
		var $featuresSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-features');
		var $contentSlot = $root.find('[data-sme-features-slot]');
		if (!$featuresSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleRow = $featuresSection.prev();
		var $toggle = $toggleRow.find('input[name="mpwpb_features_status"]').closest('.roundSwitchLabel');
		var $headerSlot = $root.find('[data-sme-section="MPWPB_Service_Features_Modern"] [data-sme-header-actions]').first();

		if ($toggle.length && $headerSlot.length) {
			$toggle.appendTo($headerSlot);
		}
		$featuresSection.appendTo($contentSlot);
		$toggleRow.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Review" heading + its 3 fields (rating,
	 *  scale, text — rendered normally, once, by the reused
	 *  MPWPB_Service_Details::service_details()) into the General
	 *  Settings card (now titled "Customer Reviews", since Shortcode and
	 *  Service template are relocated out of it below) — whole <section>
	 *  nodes moved via appendTo() (each is entirely self-contained: one
	 *  label + one input, nothing else to leave behind), so
	 *  mpwpb_service_review_ratings / _rating_scale / _rating_text still
	 *  submit exactly once. Appended at the end of that card's body, after
	 *  its own (CSS-hidden) first section.section, so that heading stays
	 *  correctly "first" there — same first-of-type reasoning as the
	 *  Service Features relocation above.
	 * ---------------------------------------------------------------- */
	(function relocateServiceReview() {
		var $card = $root.find('[data-sme-section="MPWPB_Service_Details"]');
		var $generalBody = $root.find('[data-sme-section="MPWPB_General_Settings"] .mpwpb-sme__postfields-body');
		if (!$card.length || !$generalBody.length) {
			return;
		}
		var $ratingSection = $card.find('input[name="mpwpb_service_review_ratings"]').closest('section');
		var $scaleSection = $card.find('input[name="mpwpb_service_rating_scale"]').closest('section');
		var $textSection = $card.find('input[name="mpwpb_service_rating_text"]').closest('section');
		if (!$ratingSection.length) {
			return;
		}
		var $headingSection = $ratingSection.prev();

		$headingSection.appendTo($generalBody);
		$ratingSection.appendTo($generalBody);
		$scaleSection.appendTo($generalBody);
		$textSection.appendTo($generalBody);
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Add To Cart Form Shortcode" display + "Service
	 *  template" <select> (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings()) into their own rail
	 *  card in the sticky sidebar, right after Featured Image — whole
	 *  <section> nodes moved via appendTo() (.shortcode / .service-template
	 *  are each self-contained: one label + one control), so mpwpb_template
	 *  still submits exactly once.
	 * ---------------------------------------------------------------- */
	(function relocateShortcodeAndTemplate() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		var $shortcodeSlot = $root.find('[data-sme-shortcode-slot]');
		var $templateSlot = $root.find('[data-sme-template-slot]');
		if (!$card.length) {
			return;
		}
		var $shortcodeSection = $card.find('.shortcode');
		var $templateSection = $card.find('.service-template');
		if ($shortcodeSection.length && $shortcodeSlot.length) {
			$shortcodeSection.appendTo($shortcodeSlot);
		}
		if ($templateSection.length && $templateSlot.length) {
			$templateSection.appendTo($templateSlot);
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Live toast feedback
	 * ---------------------------------------------------------------- */
	var toastTimer;
	function toast(msg) {
		var $t = $root.find('[data-sme-toast]');
		if (!$t.length) { return; }
		$t.find('[data-sme-toast-msg]').text(msg);
		$t.addClass('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { $t.removeClass('show'); }, 2200);
	}

	/* ---------------------------------------------------------------- *
	 *  Featured image (WP post thumbnail) uploader in the preview rail
	 * ---------------------------------------------------------------- */
	function setHero(id, url) {
		var $img = $('#mpwpb-sme-hero-img');
		var $ph = $root.find('.mpwpb-sme__rail-hero-ph');
		$('#mpwpb-sme-thumbnail').val(id || '');
		if (id) {
			$root.find('.mpwpb-sme__feat-card').removeClass('mpwpb-sme__field-invalid');
		}
		if (url) {
			$img.attr('src', url).show();
			$ph.hide();
			$root.find('[data-sme-feat-remove]').show();
		} else {
			$img.attr('src', '').hide();
			$ph.show();
			$root.find('[data-sme-feat-remove]').hide();
		}
		$root.find('[data-sme-feat-set]').text(url ? 'Change image' : 'Set image');
		scheduleLocalDraft();
	}
	var featFrame;
	$root.on('click', '[data-sme-feat-set]', function (e) {
		e.preventDefault();
		if (typeof wp === 'undefined' || !wp.media) { return; }
		if (featFrame) { featFrame.open(); return; }
		featFrame = wp.media({ title: (cfg.featTitle || 'Select featured image'), button: { text: (cfg.featBtn || 'Use image') }, library: { type: 'image' }, multiple: false });
		featFrame.on('select', function () {
			var a = featFrame.state().get('selection').first().toJSON();
			var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
			setHero(a.id, url);
			toast(cfg.featSet || 'Featured image set');
		});
		featFrame.open();
	});
	$root.on('click', '[data-sme-feat-remove]', function (e) {
		e.preventDefault();
		setHero('', '');
		toast(cfg.featRemoved || 'Featured image removed');
	});

	/* ---------------------------------------------------------------- *
	 *  Skeleton / shimmer placeholder loaders for each panel/rail card.
	 *  Skeleton HTML is server-rendered in PHP so it shows immediately on
	 *  page load — JS only removes the --loading class per panel.
	 * ---------------------------------------------------------------- */
	(function initSkeletons() {
		function removeLoading(el) {
			var ov = el.querySelector('.mpwpb-sme__skel-ov');
			if (ov) {
				ov.classList.add('out');
				setTimeout(function () {
					el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
				}, 260);
			} else {
				el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
			}
		}

		$root.find('.mpwpb-sme__panel').each(function () {
			var panel = this;
			var done = false;
			var obs = new MutationObserver(function () {
				if (!done && panel.classList.contains('active')) {
					done = true;
					obs.disconnect();
					setTimeout(function () { removeLoading(panel); }, 400);
				}
			});
			obs.observe(panel, { attributes: true, attributeFilter: ['class'] });
		});

		setTimeout(function () {
			var panel = $root.find('.mpwpb-sme__panel.active')[0];
			if (panel) { removeLoading(panel); }

			$root.find('.mpwpb-sme__rail-card--loading').each(function () {
				removeLoading(this);
			});
		}, 500);
	})();

	/* ---------------------------------------------------------------- *
	 *  Payment Method modal -- reuses the real Settings > Payment Method
	 *  panel (rendered server-side into #mpwpb-sme-payment-modal-body).
	 *  Its own toggle/accordion/gateway-save JS is part of that reused
	 *  markup and needs no wiring here. The one thing it can't do on its
	 *  own is save: its "Save Changes" button is a plain submit_button()
	 *  expecting a real <form action="options.php"> ancestor (which it
	 *  has on the actual Settings page) -- here it would otherwise submit
	 *  this screen's #post form instead, so that click is intercepted and
	 *  redirected to a dedicated AJAX action.
	 * ---------------------------------------------------------------- */
	var $paymentModal = $('#mpwpb-sme-payment-modal');
	if ($paymentModal.length) {
		$(document).on('click', '[data-sme-payment-modal-open]', function (e) {
			e.preventDefault();
			$paymentModal.css('display', 'flex');
		});
		$(document).on('click', '[data-sme-payment-modal-close]', function () {
			$paymentModal.hide();
		});
		$paymentModal.on('click', function (e) {
			if (e.target === this) { $paymentModal.hide(); }
		});
		$(document).on('keydown', function (e) {
			if ((e.key === 'Escape' || e.keyCode === 27) && $paymentModal.is(':visible')) {
				$paymentModal.hide();
			}
		});
		$(document).on('click', '#mpwpb-sme-payment-modal-body #submit', function (e) {
			e.preventDefault();
			if (!cfg.ajax) { return; }
			var $btn = $(this);
			var $status = $btn.next('.mpwpb-sme-payment-save-status');
			if (!$status.length) {
				$status = $('<span class="mpwpb-sme-payment-save-status" style="margin-left:10px;"></span>');
				$btn.after($status);
			}
			var data = $('#mpwpb-sme-payment-modal-body').find(':input[name^="mpwpb_payment_method_settings"]').serializeArray();
			data.push({ name: 'action', value: 'mpwpb_save_payment_method_settings' });
			data.push({ name: 'nonce', value: cfg.paymentNonce });
			$btn.prop('disabled', true);
			$status.css('color', '').text(cfg.savingTxt || 'Saving…');
			$.post(cfg.ajax, data).done(function (resp) {
				if (resp && resp.success) {
					$status.css('color', '#1c9a5b').text((resp.data && resp.data.message) || cfg.paymentSaved || 'Saved.');
					setTimeout(function () { window.location.reload(); }, 700);
				} else {
					$status.css('color', '#b32d2e').text((resp && resp.data && resp.data.message) || cfg.paymentError || 'Something went wrong.');
				}
			}).fail(function () {
				$status.css('color', '#b32d2e').text(cfg.paymentError || 'Something went wrong.');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});
	}

	// Initialise client-only draft recovery before interaction tracking starts.
	suspendServerAutosave();
	restoreLocalDraft();
	localDraftReady = true;
	localDraftLastSignature = currentLocalDraftSignature();
	if (!localDraftPersisted) {
		setLocalDraftStatus('ready', cfg.localReadyTxt || 'Local autosave ready');
	}

	// TinyMCE edits happen inside an iframe and do not bubble through #post,
	// so attach their change stream separately once each editor is ready.
	(function bindEditorDraftEvents(attempt) {
		var found = false;
		eachEditor(function (editor) {
			var element = editor.getElement ? editor.getElement() : null;
			if (!element || !$.contains($root[0], element) || editor._mpwpbLocalDraftBound) { return; }
			editor._mpwpbLocalDraftBound = true;
			editor.on('input change undo redo', function () {
				formDirty = true;
				if (isSaving) { changedDuringServerSave = true; }
				scheduleLocalDraft();
			});
			found = true;
		});
		if ((!found || attempt < 4) && attempt < 20) {
			setTimeout(function () { bindEditorDraftEvents(attempt + 1); }, 300);
		}
	})(0);

	// Repeater add/remove operations mutate the DOM rather than changing an
	// input immediately. A child-list observer captures those structural edits.
	if (window.MutationObserver) {
		(new MutationObserver(function (mutations) {
			var changed = mutations.some(function (mutation) {
				return (mutation.addedNodes.length || mutation.removedNodes.length) &&
					$(mutation.target).closest('.mp_item_insert.mp_sortable_area').length;
			});
			if (changed) {
				formDirty = true;
				if (isSaving) { changedDuringServerSave = true; }
				scheduleLocalDraft();
			}
		})).observe($root[0], { childList: true, subtree: true });
	}

	// Some legacy media/icon widgets update hidden inputs with .val() and do
	// not emit an input/change event. This lightweight fallback detects those
	// programmatic changes without making any network request.
	setInterval(function () {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		var signature = currentLocalDraftSignature();
		if (signature !== localDraftLastSignature) {
			localDraftLastSignature = signature;
			formDirty = true;
			scheduleLocalDraft();
		}
	}, 2000);

	// Resume the last-viewed step for this post, if any.
	var smeStartIndex = 0;
	try {
		var smeRememberedIndex = order.indexOf(sessionStorage.getItem(smeStepStorageKey()));
		if (smeRememberedIndex > -1) { smeStartIndex = smeRememberedIndex; }
	} catch (e) {}
	goStep(smeStartIndex);

	// WordPress' classic-editor warning compares against values captured at
	// page load, which remain stale after an AJAX save. Replace it with a
	// baseline that is reset after each successful in-place save.
	$('#post').on('input.mpwpb-sme-dirty change.mpwpb-sme-dirty', ':input', function () {
		formDirty = true;
		if (isSaving) { changedDuringServerSave = true; }
		scheduleLocalDraft();
	});
	$(window).off('beforeunload.edit-post').on('beforeunload.mpwpb-sme', function (event) {
		if ((formDirty || editorsAreDirty()) && !localDraftPersisted) {
			event.preventDefault();
			return 'The changes you made will be lost if you navigate away from this page.';
		}
	});

})(jQuery);
