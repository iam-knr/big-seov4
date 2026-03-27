/* global PSEO, jQuery */
(function ($) {
	'use strict';

	/* — Utilities ————————————————————————————————— */
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
		}).fail(function () { progress(false); notice('Request failed. Check your connection.', true); });
	}

	/* — Source type switcher ——————————————————————— */
	function updateSourcePanels() {
		var type = $('#pseo-source-type').val();
		$('.pseo-source-panel').hide();
		$('.pseo-source-' + type).show();
	}
	$(document).on('change', '#pseo-source-type', updateSourcePanels);
	updateSourcePanels();

		/* — CSV File Upload ———————————————————————————— */
		$(document).on('click', '#pseo-upload-csv-btn', function () {
				var fileFrame;
				if (fileFrame) {
					fileFrame.open();
					return;
				}
				fileFrame = wp.media({
					title: 'Select CSV File',
					button: { text: 'Use this file' },
					multiple: false
				});
				fileFrame.on('select', function () {
					var attachment = fileFrame.state().get('selection').first().toJSON();
					$('#pseo-csv-url').val(attachment.url);
					$('#pseo-csv-path').val(attachment.filesizeInBytes ? attachment.url : '');
					$('#pseo-csv-filename').text(attachment.filename);
				});
				fileFrame.open();
		});

	/* — Preview data ——————————————————————————————— */
	$(document).on('click', '#pseo-preview-btn', function () {
		var project_id = $('#pseo-project-id').val();
		if (!project_id) { notice('Save the project first before previewing data.', true); return; }
		ajaxCall('pseo_preview_data', { project_id: project_id }, function (data) {
			var $table = $('#pseo-preview-table');
			$table.find('thead tr, tbody').empty();
			if (!data.rows || !data.rows.length) { notice('No data rows found.', true); return; }
			var headers = Object.keys(data.rows[0]);
			var $hr = $('<tr>');
			$.each(headers, function (i, h) { $hr.append($('<th>').text(h)); });
			$table.find('thead').append($hr);
			$.each(data.rows, function (i, row) {
				var $tr = $('<tr>');
				$.each(headers, function (j, h) { $tr.append($('<td>').text(row[h] || '')); });
				$table.find('tbody').append($tr);
			});
			$table.show();
			notice('Showing ' + data.rows.length + ' sample rows.');
		});
	});

	/* — Generate pages ————————————————————————————— */
	$(document).on('click', '#pseo-generate-btn', function () {
		var project_id = $(this).data('project-id');
		if (!project_id) { notice('No project ID found.', true); return; }
		if (!confirm(PSEO.confirm_generate || 'Generate pages for this project?')) return;
		ajaxCall('pseo_generate', { project_id: project_id }, function (data) {
			notice('Done! Created: ' + data.created + ', Updated: ' + data.updated + ', Skipped: ' + data.skipped);
			var $tr = $(this).closest('tr');
			if ($tr.length) $tr.find('.pseo-page-count').text(data.created + data.updated);
		});
	});

	/* — Delete pages ——————————————————————————————— */
	$(document).on('click', '.pseo-btn-delete-pages', function () {
		if (!confirm(PSEO.confirm_delete || 'Delete all generated pages for this project?')) return;
		var project_id = $(this).data('id');
		ajaxCall('pseo_delete_pages', { project_id: project_id }, function (data) {
			notice('Deleted ' + data.deleted + ' pages.');
			var $tr = $(this).closest('tr');
			if ($tr.length) $tr.find('.pseo-page-count').text('0');
		});
	});

	/* — Delete project ————————————————————————————— */
	$(document).on('click', '.pseo-btn-delete-project', function () {
		if (!confirm(PSEO.confirm_delete)) return;
		var id = $(this).data('id'), $tr = $(this).closest('tr');
		ajaxCall('pseo_delete_project', { project_id: id }, function () {
			notice('Project deleted.');
			$tr.length
				? $tr.fadeOut(400, function () { $tr.remove(); })
				: (window.location.href = PSEO.ajax_url.replace('admin-ajax.php', '') + 'admin.php?page=pseo');
		});
	});

	/* — Save project form ————————————————————————— */
	$('#pseo-project-form').on('submit', function (e) {
		e.preventDefault();
		var $form = $(this), payload = {};
		$form.serializeArray().forEach(function (f) {
			if (f.name.indexOf('source_config') === -1) payload[f.name] = f.value;
		});
		var sourceConfig = {};
		$form.find('[name^="source_config["]').each(function () {
			var parts = this.name.match(/\[([^\]]+)\]/g).map(function (s) { return s.slice(1,-1); });
			if (parts.length === 1) sourceConfig[parts[0]] = $(this).val();
			else if (parts.length === 2) { if (!sourceConfig[parts[0]]) sourceConfig[parts[0]] = {}; sourceConfig[parts[0]][parts[1]] = $(this).val(); }
		});
		payload.source_config = JSON.stringify(sourceConfig);
		ajaxCall('pseo_save_project', payload, function (data) {
			// FIXED: response key is project_id, not id
			notice('\u2713 ' + (PSEO.saved || 'Project saved!') + ' (ID: ' + data.project_id + ')');
			if (!payload.id || payload.id === '0') {
				// FIXED: use data.project_id for redirect after new project creation
				window.location.href = PSEO.ajax_url.replace('admin-ajax.php', '') + 'admin.php?page=pseo-project-edit&id=' + data.project_id;
			}
		});
	});

	/* — Copy button (Settings page) ——————————————— */
	$(document).on('click', '.pseo-copy-btn', function () {
		navigator.clipboard.writeText($('#' + $(this).data('target')).text())
			.then(function () { notice('\u2713 Copied to clipboard!'); });
	});

}(jQuery));
