<?php
if (!defined('ABSPATH'))
    exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file with loop variables
$projects = PSEO_Database::get_projects(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$total_pages = 0; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ($projects as $p) {
    $total_pages += count(PSEO_Database::get_generated_page_ids((int) $p->id));
}
?>
<div class="wrap pseo-wrap">
    <h1>
        <?php esc_html_e('Big SEO Programmatic –  Settings & Info', 'knr-pseo-generator'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="pseo-settings-grid">

        <!-- Stats -->
        <div class="pseo-card pseo-stats-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Overview', 'knr-pseo-generator'); ?>
            </h2>
            <div class="pseo-stats">
                <div class="pseo-stat">
                    <span class="pseo-stat__num">
                        <?php echo count($projects); ?>
                    </span>
                    <span class="pseo-stat__label">Projects</span>
                </div>
                <div class="pseo-stat">
                    <span class="pseo-stat__num">
                        <?php echo esc_html($total_pages); ?>
                    </span>
                    <span class="pseo-stat__label">Pages Generated</span>
                </div>
                <div class="pseo-stat">
                    <span class="pseo-stat__num">
                        <?php echo esc_html(PSEO_VERSION); ?>
                    </span>
                    <span class="pseo-stat__label">Plugin Version</span>
                </div>
                <div class="pseo-stat">
                    <span class="pseo-stat__num">
                        <?php echo esc_html(get_option('pseo_db_version', '—')); ?>
                    </span>
                    <span class="pseo-stat__label">DB Version</span>
                </div>
            </div>
        </div>

        <!-- Sitemap -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('XML Sitemap', 'knr-pseo-generator'); ?>
            </h2>
            <p>
                <?php esc_html_e('Your PSEO sitemap contains all generated pages. Submit it to Google Search Console.', 'knr-pseo-generator'); ?>
            </p>
            <p>
                <strong>Sitemap URL:</strong><br>
                <code id="pseo-sitemap-url"><?php echo esc_url(home_url('/pseo-sitemap.xml')); ?></code>
                <button class="button button-small pseo-copy-btn" data-target="pseo-sitemap-url">📋 Copy</button>
            </p>
            <p>
                <a href="<?php echo esc_url(home_url('/pseo-sitemap.xml')); ?>" target="_blank" class="button">
                    ↗
                    <?php esc_html_e('View Sitemap', 'knr-pseo-generator'); ?>
                </a>
            </p>
            <p class="description">⚠ If sitemap returns 404 → Settings → Permalinks → Save Changes.</p>
        </div>

        <!-- WP-CLI -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-editor-code"></span>
                <?php esc_html_e('WP-CLI Commands', 'knr-pseo-generator'); ?>
            </h2>
            <table class="widefat striped" style="font-size:13px">
                <tbody>
                    <tr>
                        <td><code>wp pseo list</code></td>
                        <td>List all projects</td>
                    </tr>
                    <tr>
                        <td><code>wp pseo generate --id=3</code></td>
                        <td>Generate for project #3</td>
                    </tr>
                    <tr>
                        <td><code>wp pseo generate --all</code></td>
                        <td>Generate for ALL projects</td>
                    </tr>
                    <tr>
                        <td><code>wp pseo generate --all --delete-orphans</code></td>
                        <td>Generate + remove orphans</td>
                    </tr>
                    <tr>
                        <td><code>wp pseo delete-pages --id=3</code></td>
                        <td>Delete all pages of project #3</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Maintenance -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php esc_html_e('Maintenance', 'knr-pseo-generator'); ?>
            </h2>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pseo-settings&pseo_rebuild=1'), 'pseo_rebuild')); ?>"
                    class="button" onclick="return confirm('Rebuild PSEO database tables? Safe to run anytime.')">
                    🔧
                    <?php esc_html_e('Rebuild DB Tables', 'knr-pseo-generator'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>" class="button">
                    🔗
                    <?php esc_html_e('Flush Permalinks', 'knr-pseo-generator'); ?>
                </a>
            </p>
            <p class="description">Flush permalinks after first activation so the sitemap URL works.</p>
        </div>

    </div><!-- .pseo-settings-grid -->
</div>