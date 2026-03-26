<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file with loop variables
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$project_id   = (int) ( $_GET['id'] ?? 0 );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$project      = $project_id ? PSEO_Database::get_project( $project_id ) : null;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$config       = $project ? ( json_decode( $project->source_config, true ) ?: [] ) : [];
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$template_posts = get_posts( [ 'post_type' => [ 'page', 'post' ], 'numberposts' => -1,
                               'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ] );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$post_types     = get_post_types( [ 'public' => true ], 'objects' );
?>
<div class="wrap pseo-wrap">

    <h1>
        <?php echo $project
            ? esc_html__( 'Edit Project', 'knr-pseo-generator' ) . ' — <em>' . esc_html( $project->name ) . '</em>'
            : esc_html__( 'New Project', 'knr-pseo-generator' ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pseo' ) ); ?>" class="page-title-action">
        ← <?php esc_html_e( 'All Projects', 'knr-pseo-generator' ); ?>
    </a>
    <hr class="wp-header-end">

    <form id="pseo-project-form" class="pseo-form" novalidate>
        <?php wp_nonce_field( 'pseo_nonce', 'nonce' ); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr( $project_id ); ?>">

        <!-- CARD 1 – Basic Details -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Project Details', 'knr-pseo-generator' ); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th><label for="pseo-name"><?php esc_html_e( 'Project Name', 'knr-pseo-generator' ); ?> *</label></th>
                    <td>
                        <input id="pseo-name" type="text" name="name" class="regular-text"
                               value="<?php echo esc_html( $project->name ?? '' ); ?>"
                               placeholder="e.g. Plumber Pages by City" required>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Generate as Post Type', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select name="post_type">
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						                            <?php foreach ( $post_types as $pt ) : ?>
                                <option value="<?php echo esc_html( $pt->name ); ?>"
                                    <?php selected( $project->post_type ?? 'page', $pt->name ); ?>>
                                    <?php echo esc_html( $pt->label ); ?> (<?php echo esc_html( $pt->name ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Content Template', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select name="template_id">
                            <option value="0"><?php esc_html_e( '— None (blank content) —', 'knr-pseo-generator' ); ?></option>
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						                            <?php foreach ( $template_posts as $tp ) : ?>
						<option value="<?php echo esc_attr( $tp->ID ); ?>" <?php selected( $project->template_id ?? 0, $tp->ID ); ?>>                                    <?php echo esc_html( $tp->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Syntax: <code>{{city}}</code> value &nbsp;|&nbsp;
                            <code>{{raw:html_col}}</code> unescaped HTML &nbsp;|&nbsp;
                            <code>{Best|Top|Leading}</code> spintax &nbsp;|&nbsp;
                            <code>[if:price>0]text[/if]</code> conditional
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- CARD 2 – Data Source -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-database"></span>
                <?php esc_html_e( 'Data Source', 'knr-pseo-generator' ); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Source Type', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select id="pseo-source-type" name="source_type">
                            <?php $st = $project->source_type ?? 'csv_url'; ?>
                            <option value="csv_url"       <?php selected( $st, 'csv_url' ); ?>>       📄 CSV via URL</option>
                        </select>
                    </td>
                </tr>

                <!-- CSV / CSV Upload -->
                <tr class="pseo-source-panel pseo-source-csv_url pseo-source-csv_upload">
                    <th><?php esc_html_e( 'CSV File URL / Server Path', 'knr-pseo-generator' ); ?></th>
                    <td>
                        <input type="text" name="source_config[file_url]" class="large-text"
                               value="<?php echo esc_html( $config['file_url'] ?? '' ); ?>"
                               placeholder="https://docs.google.com/spreadsheets/d/.../export?format=csv">
                    </td>
                </tr>
						</table>
	</div>
        <!-- CARD 3 – URL Structure -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-admin-links"></span>
                <?php esc_html_e( 'URL Structure', 'knr-pseo-generator' ); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'URL Pattern', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <div class="pseo-url-preview-wrap">
                            <span class="pseo-url-base"><?php echo esc_html( trailingslashit( home_url() ) ); ?></span>
                            <input type="text" name="url_pattern" class="large-text"
                                   value="<?php echo esc_html( $project->url_pattern ?? '' ); ?>"
                                   placeholder="{{service}}/{{city}}">
                        </div>
                        <p class="description">
                            <strong>Example:</strong> <code>services/{{service}}/{{city}}</code>
                            → <code>/services/plumbing/bangalore/</code>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- CARD 4 – SEO Meta -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'SEO Meta', 'knr-pseo-generator' ); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Title Tag Template', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <input type="text" name="seo_title" class="large-text"
                               value="<?php echo esc_html( $project->seo_title ?? '' ); ?>"
                               placeholder="Best {{service}} in {{city}} | {{brand}}">
                        <p class="description">Recommended: under 60 characters after placeholder substitution.</p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description Template', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <textarea name="seo_desc" class="large-text" rows="3"
                                  placeholder="Looking for {{service}} in {{city}}? Get free quotes starting from ₹{{price}}."
                        ><?php echo esc_textarea( $project->seo_desc ?? '' ); ?></textarea>
                        <p class="description">Recommended: 120–160 characters.</p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Robots Directive', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select name="robots">
                            <?php $r = $project->robots ?? 'index,follow'; ?>
                            <option value="index,follow"     <?php selected($r,'index,follow'); ?>>      index, follow (default)</option>
                            <option value="noindex,follow"   <?php selected($r,'noindex,follow'); ?>>    noindex, follow</option>
                            <option value="index,nofollow"   <?php selected($r,'index,nofollow'); ?>>    index, nofollow</option>
                            <option value="noindex,nofollow" <?php selected($r,'noindex,nofollow'); ?>>  noindex, nofollow</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Schema Markup Type', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select id="pseo-schema-type" name="schema_type">
                            <?php $sc = $project->schema_type ?? ''; ?>
                            <option value=""              <?php selected($sc,''); ?>>             None</option>
                            <option value="Article"       <?php selected($sc,'Article'); ?>>      Article — blog posts, guides</option>
                            <option value="LocalBusiness" <?php selected($sc,'LocalBusiness'); ?>>LocalBusiness — service + city pages</option>
                            <option value="Product"       <?php selected($sc,'Product'); ?>>      Product — ecommerce pages</option>
                            <option value="FAQPage"       <?php selected($sc,'FAQPage'); ?>>      FAQPage — FAQ pages</option>
                            <option value="BreadcrumbList"<?php selected($sc,'BreadcrumbList'); ?>>BreadcrumbList — all pages</option>
                            <option value="JobPosting"    <?php selected($sc,'JobPosting'); ?>>   JobPosting — job listing pages</option>
                        </select>
                        <div id="pseo-schema-hint" class="pseo-schema-hint" style="display:none;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- CARD 5 – Auto-Sync -->
        <div class="pseo-card">
            <h2 class="pseo-card__title">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Auto-Sync Settings', 'knr-pseo-generator' ); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Sync Interval', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <select name="sync_interval">
                            <?php $si = $project->sync_interval ?? 'manual'; ?>
                            <option value="manual" <?php selected($si,'manual'); ?>>🖱 Manual only</option>
                            <option value="hourly" <?php selected($si,'hourly'); ?>>⏱ Hourly</option>
                            <option value="daily"  <?php selected($si,'daily'); ?>> 📅 Daily</option>
                            <option value="weekly" <?php selected($si,'weekly'); ?>>📆 Weekly</option>
                        </select>
                        <p class="description">Auto re-fetches data source and updates pages via WP Cron.</p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Delete Orphaned Pages', 'knr-pseo-generator' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delete_orphans" value="1"
                                   <?php checked( $project->delete_orphans ?? 0, 1 ); ?>>
                            <?php esc_html_e( 'Auto-delete pages whose data row was removed from the source.', 'knr-pseo-generator' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Submit Bar -->
        <div class="pseo-submit-bar">
            <button type="submit" class="button button-primary button-large">
                💾 <?php esc_html_e( 'Save Project', 'knr-pseo-generator' ); ?>
            </button>
            <?php if ( $project_id ) : ?>
                <button type="button" class="button button-large pseo-btn-generate" data-id="<?php echo esc_attr( $project_id ); ?>">
                    ⚡ <?php esc_html_e( 'Generate Pages Now', 'knr-pseo-generator' ); ?>
                </button>
                			<button type="button" class="button button-large pseo-btn-preview" data-id="<?php echo esc_attr( $project_id ); ?>">
                    👁 <?php esc_html_e( 'Preview Data', 'knr-pseo-generator' ); ?>
                </button>
				<button type="button" class="button button-link-delete pseo-btn-delete-project" data-id="<?php echo esc_attr( $project_id ); ?>">                    🗑 <?php esc_html_e( 'Delete Project', 'knr-pseo-generator' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Preview Modal -->
    <div id="pseo-preview-modal" class="pseo-modal" style="display:none;" role="dialog">
        <div class="pseo-modal-inner">
            <div class="pseo-modal-header">
                <h2><?php esc_html_e( 'Data Preview (first 5 rows)', 'knr-pseo-generator' ); ?></h2>
                <button class="pseo-modal-close">&times;</button>
            </div>
            <div id="pseo-preview-content"></div>
        </div>
    </div>
    <div id="pseo-notice" class="pseo-notice" style="display:none;" role="alert"></div>

</div>
