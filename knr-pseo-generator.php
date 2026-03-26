<?php
/**
 * Plugin Name: Big SEO Programmatic
 * Plugin URI:  https://iam-knr.github.io/pseo_byknr/
 * Description: Generate thousands of SEO-optimised pages from CSV. Unlimited rows, built-in schema, meta, sitemap, cron & WP-CLI — all free.
 * Version:     2.4.1
 * Author:      Kailas Nath R
 * Author URI:  https://www.linkedin.com/in/iamknr
 * License:     GPL-2.0-or-later
 * Text Domain: knr-pseo-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PSEO_VERSION', '2.4.1');
define('PSEO_PLUGIN_FILE', __FILE__);
define('PSEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PSEO_PLUGIN_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function ($class) {
    if (strpos($class, 'PSEO_') !== 0)
        return;
    $slug = strtolower(str_replace(['PSEO_', '_'], ['', '-'], $class));
    $file = PSEO_PLUGIN_DIR . 'includes/class-pseo-' . $slug . '.php';
    if (file_exists($file))
        require_once $file;
});

final class PSEO_Plugin
{
    private static ?PSEO_Plugin $instance = null;

    public static function instance(): self
    {
        if (is_null(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    private function __construct()
    {
        register_activation_hook(PSEO_PLUGIN_FILE, ['PSEO_Database', 'install']);
        register_deactivation_hook(PSEO_PLUGIN_FILE, [$this, 'on_deactivate']);
        add_action('plugins_loaded', [$this, 'boot']);
    }

    public function on_deactivate(): void
    {
        wp_clear_scheduled_hook('pseo_cron_sync');
    }

    public function boot(): void
    {
        new PSEO_Admin();
        new PSEO_Ajax();
        new PSEO_SeoMeta();
        new PSEO_Sitemap();
        new PSEO_Schema();
        add_action('pseo_cron_sync', ['PSEO_Generator', 'run_scheduled_syncs']);
        if (!wp_next_scheduled('pseo_cron_sync')) {
            wp_schedule_event(time(), 'hourly', 'pseo_cron_sync');
        }
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('bigseo-pages', 'PSEO_CLI');
        }
    }
}

PSEO_Plugin::instance();
