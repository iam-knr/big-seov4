<?php
/**
 * Plugin Name: Big SEO Programmatic
 * Plugin URI:  https://iam-knr.github.io/pseo_byknr/
 * Description: Generate thousands of SEO-optimised pages from CSV. Unlimited rows, built-in schema, meta, sitemap, cron & WP-CLI — all free.
 * Version:     2.4.2
 * Author:      Kailas Nath R
 * Author URI:  https://www.linkedin.com/in/iamknr
 * License:     GPL-2.0-or-later
 * Text Domain: knr-pseo-generator
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PSEO_VERSION',     '2.4.2' );
define( 'PSEO_PLUGIN_FILE', __FILE__ );
define( 'PSEO_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'PSEO_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * FIX #1 — Autoloader: use a robust class-to-filename map rather than
 * a fragile str_replace chain.  Every PSEO_ class maps explicitly so
 * multi-word class names (e.g. PSEO_SeoMeta, PSEO_DataSource) always
 * resolve to the correct filename regardless of underscore position.
 */
spl_autoload_register( function ( $class ) {
    $map = [
        'PSEO_Admin'      => 'class-pseo-admin',
        'PSEO_Ajax'       => 'class-pseo-ajax',
        'PSEO_CLI'        => 'class-pseo-cli',
        'PSEO_Database'   => 'class-pseo-database',
        'PSEO_DataSource' => 'class-pseo-datasource',
        'PSEO_Generator'  => 'class-pseo-generator',
        'PSEO_Schema'     => 'class-pseo-schema',
        'PSEO_SeoMeta'    => 'class-pseo-seometa',
        'PSEO_Sitemap'    => 'class-pseo-sitemap',
        'PSEO_Template'   => 'class-pseo-template',
    ];
    if ( ! isset( $map[ $class ] ) ) {
        return;
    }
    $file = PSEO_PLUGIN_DIR . 'includes/' . $map[ $class ] . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/**
 * FIX #2 — Cron: register all needed schedules and store the
 * last-run timestamp per project so run_scheduled_syncs() can
 * honour daily / weekly intervals instead of always firing hourly.
 */
final class PSEO_Plugin {
    private static ?PSEO_Plugin $instance = null;

    public static function instance(): self {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( PSEO_PLUGIN_FILE, [ 'PSEO_Database', 'install' ] );
        register_deactivation_hook( PSEO_PLUGIN_FILE, [ $this, 'on_deactivate' ] );
        add_action( 'plugins_loaded', [ $this, 'boot' ] );
        // Register extra WP-Cron schedules for daily/weekly intervals.
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
    }

    /**
     * Add custom cron intervals so projects can sync daily or weekly
     * instead of being locked to the built-in hourly schedule.
     */
    public function add_cron_schedules( array $schedules ): array {
        if ( ! isset( $schedules['pseo_daily'] ) ) {
            $schedules['pseo_daily'] = [
                'interval' => DAY_IN_SECONDS,
                'display'  => __( 'Once Daily (Big SEO)', 'knr-pseo-generator' ),
            ];
        }
        if ( ! isset( $schedules['pseo_weekly'] ) ) {
            $schedules['pseo_weekly'] = [
                'interval' => WEEK_IN_SECONDS,
                'display'  => __( 'Once Weekly (Big SEO)', 'knr-pseo-generator' ),
            ];
        }
        return $schedules;
    }

    public function on_deactivate(): void {
        wp_clear_scheduled_hook( 'pseo_cron_sync' );
    }

    public function boot(): void {
        new PSEO_Admin();
        new PSEO_Ajax();
        new PSEO_SeoMeta();
        new PSEO_Sitemap();
        new PSEO_Schema();

        add_action( 'pseo_cron_sync', [ 'PSEO_Generator', 'run_scheduled_syncs' ] );

        // FIX #2: schedule at hourly — run_scheduled_syncs() now checks
        // each project's sync_interval and last-run time before generating.
        if ( ! wp_next_scheduled( 'pseo_cron_sync' ) ) {
            wp_schedule_event( time(), 'hourly', 'pseo_cron_sync' );
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            // FIX #21: command registered as 'bigseo-pages' to match CLI docs.
            WP_CLI::add_command( 'bigseo-pages', 'PSEO_CLI' );
        }
    }
}

PSEO_Plugin::instance();
