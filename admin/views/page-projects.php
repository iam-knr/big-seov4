<?php
if (!defined('ABSPATH'))
    exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file with loop variables
$projects = PSEO_Database::get_projects(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wrap pseo-wrap">

    <h1 class="wp-heading-inline">
        <?php esc_html_e('Big SEO Programmatic –  Projects', 'knr-pseo-generator'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=pseo-project-edit')); ?>" class="page-title-action">
        <?php esc_html_e('+ New Project', 'knr-pseo-generator'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (empty($projects)): ?>
        <div class="pseo-empty-state">
            <span class="dashicons dashicons-chart-area"></span>
            <h2><?php esc_html_e('No projects yet.', 'knr-pseo-generator'); ?></h2>
            <p><?php esc_html_e('Create your first project to start generating programmatic SEO pages.', 'knr-pseo-generator'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=pseo-project-edit')); ?>"
                class="button button-primary button-hero">
                <?php esc_html_e('Create First Project', 'knr-pseo-generator'); ?>
            </a>
        </div>

    <?php else: ?>
        <table class="wp-list-table widefat fixed striped pseo-table">
            <thead>
                <tr>
                    <th style="width:30px">ID</th>
                    <th><?php esc_html_e('Project Name', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Source', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Post Type', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Schema', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Auto-Sync', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Pages', 'knr-pseo-generator'); ?></th>
                    <th><?php esc_html_e('Actions', 'knr-pseo-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p):
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                    $page_ids = PSEO_Database::get_generated_page_ids((int) $p->id);
                    ?>
                    <tr data-project-id="<?php echo (int) $p->id; ?>">
                        <td><?php echo (int) $p->id; ?></td>

                        <td>
                            <strong>
                                <a
                                    href="<?php echo esc_url(admin_url('admin.php?page=pseo-project-edit&id=' . $p->id)); ?>">
                                    <?php echo esc_html($p->name); ?>
                                </a>
                            </strong>
                        </td>

                        <td>
                            <span class="pseo-badge pseo-badge--source">
                                <?php echo esc_html($p->source_type); ?>
                            </span>
                        </td>

                        <td><?php echo esc_html($p->post_type); ?></td>

                        <td>
                            <?php echo $p->schema_type
                                ? '<span class="pseo-badge pseo-badge--schema">' . esc_html($p->schema_type) . '</span>'
                                : '—'; ?>
                        </td>

                        <td>
                            <?php echo esc_html($p->sync_interval) === 'manual'
                                ? '<span style="color:#646970">Manual</span>'
                                : '<span style="color:#00a32a">⏱ ' . esc_html(ucfirst($p->sync_interval)) . '</span>'; ?>
                        </td>

                        <td>
                            <strong class="pseo-page-count"><?php echo count($page_ids); ?></strong>
                            <?php if (!empty($page_ids)): ?>
                                <br>
                                <a href="<?php echo esc_url(get_permalink($page_ids[0])); ?>" target="_blank"
                                    style="font-size:11px">
                                    <?php esc_html_e('View sample ↗', 'knr-pseo-generator'); ?>
                                </a>
                            <?php endif; ?>
                        </td>

                        <td class="pseo-actions">
                            <button class="button button-primary button-small pseo-btn-generate"
                                data-id="<?php echo (int) $p->id; ?>">
                                ⚡ <?php esc_html_e('Generate', 'knr-pseo-generator'); ?>
                            </button>
                            <button class="button button-small pseo-btn-preview" data-id="<?php echo (int) $p->id; ?>">
                                👁 <?php esc_html_e('Preview', 'knr-pseo-generator'); ?>
                            </button>
                            <button class="button button-small button-link-delete pseo-btn-delete-pages"
                                data-id="<?php echo (int) $p->id; ?>">
                                <?php esc_html_e('Delete Pages', 'knr-pseo-generator'); ?>
                            </button>
                            <button class="button button-small button-link-delete pseo-btn-delete-project"
                                data-id="<?php echo (int) $p->id; ?>">
                                <?php esc_html_e('Delete Project', 'knr-pseo-generator'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Preview Modal -->
    <div id="pseo-preview-modal" class="pseo-modal" style="display:none;" role="dialog">
        <div class="pseo-modal-inner">
            <div class="pseo-modal-header">
                <h2><?php esc_html_e('Data Preview (first 5 rows)', 'knr-pseo-generator'); ?></h2>
                <button class="pseo-modal-close">&times;</button>
            </div>
            <div id="pseo-preview-content"></div>
        </div>
    </div>

    <div id="pseo-progress" class="pseo-progress-bar" style="display:none;">
        <div class="pseo-progress-inner"></div>
    </div>
    <div id="pseo-notice" class="pseo-notice" style="display:none;" role="alert"></div>

</div>