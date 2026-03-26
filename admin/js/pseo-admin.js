/* global PSEO, jQuery */
(function ($) {
    'use strict';

    /* ── Utilities ─────────────────────────────────────────── */
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

    /* ── Source type switcher ───────────────────────────────── */
    function updateSourcePanels() {
        var type = $('#pseo-source-type').val();
        $('.pseo-source-panel').hide();
        $('.pseo-source-' + type).show();
    }
    $(document).on('change', '#pseo-source-type', updateSourcePanels);
    updateSourcePanels();

    /* ── Schema hints ───────────────────────────────────────── */
    var schemaHints = {
        'LocalBusiness': 'Required columns: <strong>city</strong>, <strong>address</strong>, <strong>phone</strong>. Optional: business_name, state, zip, price_range.',
        'Product':       'Required: <strong>product_name</strong>, <strong>price</strong>. Optional: description, currency.',
        'FAQPage':       'Required: <strong>faq_q1</strong>, <strong>faq_a1</strong> (continue with faq_q2, faq_a2…)',
        'JobPosting':    'Required: <strong>job_title</strong>, <strong>company</strong>, <strong>city</strong>. Optional: salary, currency.',
        'Article':       'Optional: <strong>description</strong> column for the article description.',
        'BreadcrumbList':'Auto-generated — no extra columns needed.',
    };
    $(document).on('change', '#pseo-schema-type', function () {
        var $hint = $('#pseo-schema-hint');
        var hint  = schemaHints[$(this).val()];
        hint ? $hint.html('<strong>📌 Required columns:</strong> ' + hint).show() : $hint.hide();
    }).trigger('change');

    /* ── Generate ───────────────────────────────────────────── */
    $(document).on('click', '.pseo-btn-generate', function () {
        var $btn = $(this), id = $btn.data('id');
        $btn.prop('disabled', true).text(PSEO.generating);
        ajaxCall(
            'pseo_generate',
            { project_id: id, delete_orphans: $('input[name="delete_orphans"]:checked').length },
            function (data) {
                var errors = (data.errors && data.errors.length) ? ' ⚠ ' + data.errors.join(', ') : '';
                notice('✓ Created: ' + data.created + '  Updated: ' + data.updated + '  Deleted: ' + data.deleted + errors, !!errors);
                var $tr = $('tr[data-project-id="' + id + '"]');
                if ($tr.length) {
                    var old = parseInt($tr.find('.pseo-page-count').text()) || 0;
                    $tr.find('.pseo-page-count').text(old + data.created);
                }
                $btn.prop('disabled', false).text(PSEO.generate || '⚡ Generate');
            },
            function () { $btn.prop('disabled', false).text(PSEO.generate || '⚡ Generate'); }
        );
    });

    /* ── Preview data ───────────────────────────────────────── */
    $(document).on('click', '.pseo-btn-preview', function () {
        ajaxCall('pseo_preview_data', { project_id: $(this).data('id') }, function (data) {
            var html = '<p><strong>' + data.count + ' rows</strong> found.</p>';
            if (data.preview && data.preview.length) {
                html += '<div style="overflow-x:auto"><table class="pseo-preview-table"><thead><tr>';
                data.columns.forEach(function (c) { html += '<th>' + $('<s>').text(c).html() + '</th>'; });
                html += '</tr></thead><tbody>';
                data.preview.forEach(function (row) {
                    html += '<tr>';
                    data.columns.forEach(function (c) { html += '<td>' + $('<s>').text(row[c] || '').html() + '</td>'; });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                html += '<p style="color:#646970;font-size:12px;margin-top:8px">Showing first 5 of ' + data.count + ' rows.</p>';
            } else {
                html += '<p style="color:#d63638">No rows returned — check your data source settings.</p>';
            }
            $('#pseo-preview-content').html(html);
            $('#pseo-preview-modal').show();
        });
    });

    /* ── Modal close ────────────────────────────────────────── */
    $(document).on('click', '.pseo-modal-close', function () { $(this).closest('.pseo-modal').fadeOut(200); });
    $(document).on('click', '.pseo-modal', function (e) { if ($(e.target).hasClass('pseo-modal')) $(this).fadeOut(200); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') $('.pseo-modal').fadeOut(200); });

    /* ── Delete pages ───────────────────────────────────────── */
    $(document).on('click', '.pseo-btn-delete-pages', function () {
        if (!confirm(PSEO.confirm_pages)) return;
        var id = $(this).data('id'), $tr = $(this).closest('tr');
        ajaxCall('pseo_delete_pages', { project_id: id }, function (data) {
            notice('Deleted ' + data.deleted + ' pages.');
            if ($tr.length) $tr.find('.pseo-page-count').text('0');
        });
    });

    /* ── Delete project ─────────────────────────────────────── */
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

    /* ── Save project form ──────────────────────────────────── */
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
            notice('✓ ' + (PSEO.saved || 'Project saved!') + ' (ID: ' + data.id + ')');
            if (!payload.id || payload.id === '0') {
                window.location.href = PSEO.ajax_url.replace('admin-ajax.php', '') + 'admin.php?page=pseo-project-edit&id=' + data.id;
            }
        });
    });

    /* ── Copy button (Settings page) ───────────────────────── */
    $(document).on('click', '.pseo-copy-btn', function () {
        navigator.clipboard.writeText($('#' + $(this).data('target')).text())
            .then(function () { notice('✓ Copied to clipboard!'); });
    });

}(jQuery));