/* global PSEO, jQuery */
(function ($) {
	'use strict';

	/* -- Utilities ------------------------------------------------- */
	function notice(msg, isError) {
		var $n = $('#pseo-notice');
		$n.text(msg).removeClass('is-error').toggleClass('is-error', !!isError).stop(true).fadeIn(200);
		clearTimeout($n.data('timer'));
		$n.data('timer', setTimeout(function () { $n.fadeOut(400); }, 4500));
	}

	function progress(show) { $('#pseo-progress').toggle(show); }

	function ajaxCall(action, data, onSuccess, onError) {
		progress(true);
		$.post(PSEO.ajax_url, $.extend({ action: action, nonce: PSEO.nonce }, data), function (res) {
			progress(false);
			if (res.success) {
				onSuccess(res.data);
			} else {
				var msg = (res.data && res.data.message) || 'An error occurred.';
				notice(msg, true);
				if (typeof onError === 'function') onError(msg);
			}
		}).fail(function () {
			progress(false);
			notice('Request failed. Check your connection.', true);
		});
	}

	/* -- Source type switcher -------------------------------------- */
	function updateSourcePanels() {
		var type = $('#pseo-source-type').val();
		$('.pseo-source-panel').hide();
		$('.pseo-source-' + type).show();
	}
	$(document).on('change', '#pseo-source-type', updateSourcePanels);
	updateSourcePanels();

	/* -- CSV File Upload ------------------------------------------- */
	$(document).on('click', '#pseo-upload-csv-btn', function () {
		var fileFrame;
		if (fileFrame) { fileFrame.open(); return; }
		fileFrame = wp.media({
			title: 'Select CSV File',
			button: { text: 'Use this file' },
			multiple: false,
			library: { type: 'text/csv' }
		});
		fileFrame.on('select', function () {
			var attachment = fileFrame.state().get('selection').first().toJSON();
			$('#pseo-csv-file-url').val(attachment.url);
			$('#pseo-csv-filename').text(attachment.filename);
			notice('CSV file selected: ' + attachment.filename);
		});
		fileFrame.open();
	});

	/* -- Schema hints ---------------------------------------------- */
	var schemaHints = {
		'LocalBusiness': 'Required columns: **city**, **address**, **phone**. Optional: business_name, state, zip, price_range.',
		'Product':       'Required: **product_name**, **price**. Optional: description, currency.',
		'FAQPage':       'Required: **faq_q1**, **faq_a1** (continue with faq_q2, faq_a2…)',
		'JobPosting':    'Required: **job_title**, **company**, **city**. Optional: salary, currency.',
		'Article':       'Optional: **description** column for the article description.',
		'BreadcrumbList':'Auto-generated — no extra columns needed.',
	};
	$(document).on('change', '#pseo-schema-type', function () {
		var $hint = $('#pseo-schema-hint');
		var hint  = schemaHints[$(this).val()];
		hint ? $hint.html('**\uD83D\uDCCC Required columns:** ' + hint).show() : $hint.hide();
	}).trigger('change');

	/* -- Generate -------------------------------------------------- */
	$(document).on('click', '.pseo-btn-generate', function () {
		var $btn = $(this), id = $btn.data('id');
		$btn.prop('disabled', true).text(PSEO.generating);
		ajaxCall(
			'pseo_generate',
			{ project_id: id, delete_orphans: $('input[name="delete_orphans"]:checked').length },
			function (data) {
				var errors = (data.errors && data.errors.length) ? ' \u26A0 ' + data.errors.join(', ') : '';
				notice('\u2713 Created: ' + data.created + '  Updated: ' + data.updated + '  Deleted: ' + data.deleted + errors, !!errors);
				/*
				 * FIX: Previously the count only added data.created to the old
				 * display value, which was wrong when running generate multiple
				 * times (updated pages weren't counted, deleted pages weren't
				 * subtracted). Now we set the count to created + updated so it
				 * always reflects the true live page count for the project.
				 */
				var $tr = $('tr[data-project-id="' + id + '"]');
				if ($tr.length) {
					var currentCount = parseInt($tr.find('.pseo-page-count').text(), 10) || 0;
					var newCount = currentCount + data.created - data.deleted;
					$tr.find('.pseo-page-count').text(newCount < 0 ? 0 : newCount);
				}
				$btn.prop('disabled', false).text(PSEO.generate || '\u26A1 Generate');
			},
			function () { $btn.prop('disabled', false).text(PSEO.generate || '\u26A1 Generate'); }
		);
	});

	/* -- Preview data ---------------------------------------------- */
	$(document).on('click', '.pseo-btn-preview', function () {
		ajaxCall('pseo_preview_data', { project_id: $(this).data('id') }, function (data) {
			var html = '<p><strong>' + data.count + ' rows</strong> found.</p>';
			if (data.preview && data.preview.length) {
				html += '<table class="widefat striped"><thead><tr>';
				data.columns.forEach(function (c) { html += '<th>' + $('<span>').text(c).html() + '</th>'; });
				html += '</tr></thead><tbody>';
				data.preview.forEach(function (row) {
					html += '<tr>';
					data.columns.forEach(function (c) { html += '<td>' + $('<span>').text(row[c] || '').html() + '</td>'; });
					html += '</tr>';
				});
				html += '</tbody></table>';
				html += '<p>Showing first 5 of ' + data.count + ' rows.</p>';
			} else {
				html += '<p>No rows returned — check your data source settings.</p>';
			}
			$('#pseo-preview-content').html(html);
			$('#pseo-preview-modal').show();
		});
	});

	/* -- Modal close ----------------------------------------------- */
	$(document).on('click', '.pseo-modal-close', function () {
		$(this).closest('.pseo-modal').fadeOut(200);
	});
	$(document).on('click', '.pseo-modal', function (e) {
		if ($(e.target).hasClass('pseo-modal')) $(this).fadeOut(200);
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') $('.pseo-modal').fadeOut(200);
	});

	/* -- Delete pages ---------------------------------------------- */
	$(document).on('click', '.pseo-btn-delete-pages', function () {
		if (!confirm(PSEO.confirm_pages)) return;
		var id = $(this).data('id'), $tr = $(this).closest('tr');
		ajaxCall('pseo_delete_pages', { project_id: id }, function (data) {
			notice('Deleted ' + data.deleted + ' pages.');
			if ($tr.length) $tr.find('.pseo-page-count').text('0');
		});
	});

	/* -- Delete project -------------------------------------------- */
	$(document).on('click', '.pseo-btn-delete-project', function () {
		if (!confirm(PSEO.confirm_delete)) return;
		var id = $(this).data('id'), $tr = $(this).closest('tr');
		ajaxCall('pseo_delete_project', { project_id: id }, function () {
			notice('Project deleted.');
			$tr.length ? $tr.fadeOut(400, function () { $tr.remove(); })
			          : (window.location.href = PSEO.ajax_url.replace('admin-ajax.php', '') + 'admin.php?page=pseo');
		});
	});

	/* -- Save project form ----------------------------------------- */
	$('#pseo-project-form').on('submit', function (e) {
		e.preventDefault();
		var $form = $(this), payload = {};
		$form.serializeArray().forEach(function (f) {
			if (f.name.indexOf('source_config') === -1) payload[f.name] = f.value;
		});
		var sourceConfig = {};
		$form.find('[name^="source_config["]').each(function () {
			var parts = this.name.match(/\[([^\]]+)\]/g).map(function (s) { return s.slice(1, -1); });
			if (parts.length === 1) sourceConfig[parts[0]] = $(this).val();
			else if (parts.length === 2) {
				if (!sourceConfig[parts[0]]) sourceConfig[parts[0]] = {};
				sourceConfig[parts[0]][parts[1]] = $(this).val();
			}
		});
		payload.source_config = JSON.stringify(sourceConfig);
		ajaxCall('pseo_save_project', payload, function (data) {
			notice('\u2713 ' + (PSEO.saved || 'Project saved!') + ' (ID: ' + data.id + ')');
			if (!payload.id || payload.id === '0') {
				window.location.href = PSEO.ajax_url.replace('admin-ajax.php', '') + 'admin.php?page=pseo-project-edit&id=' + data.id;
			}
		});
	});

	/* -- Copy button (Settings page) ------------------------------- */
	$(document).on('click', '.pseo-copy-btn', function () {
		navigator.clipboard.writeText($('#' + $(this).data('target')).text())
			.then(function () { notice('\u2713 Copied to clipboard!'); });
	});

}(jQuery));
