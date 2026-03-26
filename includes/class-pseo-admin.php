<?php
if (!defined('ABSPATH'))
	exit;

class PSEO_Admin
{

	public function __construct()
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('admin_init', [$this, 'handle_db_rebuild']);
		add_action('admin_notices', [$this, 'admin_notices']);
	}

	/* ── Menu ─────────────────────────────────────────────── */

	public function register_menu(): void
	{
		add_menu_page(
			__('Big SEO Programmatic', 'knr-pseo-generator'),
			__('Big SEO Programmatic', 'knr-pseo-generator'),
			'manage_options',
			'pseo',
			[$this, 'page_projects'],
			PSEO_PLUGIN_URL . 'admin/images/icon.png',
			30
		);
		add_submenu_page('pseo', __('All Projects', 'knr-pseo-generator'), __('All Projects', 'knr-pseo-generator'), 'manage_options', 'knr-pseo-generator', [$this, 'page_projects']);
		add_submenu_page('pseo', __('New / Edit Project', 'knr-pseo-generator'), __('+ New Project', 'knr-pseo-generator'), 'manage_options', 'pseo-project-edit', [$this, 'page_project_edit']);
		add_submenu_page('pseo', __('Settings', 'knr-pseo-generator'), __('Settings', 'knr-pseo-generator'), 'manage_options', 'pseo-settings', [$this, 'page_settings']);
	}

	/* ── Assets ───────────────────────────────────────────── */

	public function enqueue_assets(string $hook): void
	{
		if (strpos($hook, 'pseo') === false)
			return;

		wp_enqueue_style('pseo-admin', PSEO_PLUGIN_URL . 'admin/css/pseo-admin.css', [], PSEO_VERSION);
		wp_enqueue_script('pseo-admin', PSEO_PLUGIN_URL . 'admin/js/pseo-admin.js', ['jquery'], PSEO_VERSION, true);

		wp_localize_script('pseo-admin', 'PSEO', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pseo_nonce'),
			'confirm_delete' => __('Delete this project and ALL its generated pages? This cannot be undone.', 'knr-pseo-generator'),
			'confirm_pages' => __('Delete all generated pages for this project?', 'knr-pseo-generator'),
			'generating' => __('Generating…', 'knr-pseo-generator'),
			'generate' => __('Generate', 'knr-pseo-generator'),
			'saved' => __('Project saved!', 'knr-pseo-generator'),
		]);
	}

	/* ── DB rebuild handler ───────────────────────────────── */

	public function handle_db_rebuild(): void
	{
		if (
			isset($_GET['pseo_rebuild']) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			current_user_can('manage_options') &&
			check_admin_referer('pseo_rebuild')
		) {
			PSEO_Database::install();
			wp_safe_redirect(admin_url('admin.php?page=pseo-settings&pseo_rebuilt=1'));
			exit;
		}
	}

	/* ── Admin notices ────────────────────────────────────── */

	public function admin_notices(): void
	{
		if (isset($_GET['pseo_rebuilt'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>'
				. esc_html__('Big SEO Programmatic: database tables rebuilt successfully.', 'knr-pseo-generator')
				. '</p></div>';
		}
	}

	/* ── Page renderers ───────────────────────────────────── */

	public function page_projects(): void
	{
		require_once PSEO_PLUGIN_DIR . 'admin/views/page-projects.php';
	}
	public function page_project_edit(): void
	{
		require_once PSEO_PLUGIN_DIR . 'admin/views/page-project-edit.php';
	}
	public function page_settings(): void
	{
		require_once PSEO_PLUGIN_DIR . 'admin/views/page-settings.php';
	}
}