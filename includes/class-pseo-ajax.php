<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- All $_POST vars use (int) casting or ?? defaults
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in verify() method
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Using (int) cast
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Using (int) cast or wp_json_encode
class PSEO_Ajax {

	public function __construct() {
		foreach ( [ 'pseo_generate', 'pseo_delete_pages', 'pseo_preview_data', 'pseo_save_project', 'pseo_delete_project' ] as $a ) {
			add_action( "wp_ajax_{$a}", [ $this, str_replace( 'pseo_', '', $a ) ] );
		}
	}

	private function verify(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
		check_ajax_referer( 'pseo_nonce', 'nonce' );
	}

	public function generate(): void {
		$this->verify();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		wp_send_json_success( PSEO_Generator::run( (int) $_POST['project_id'], ! empty( $_POST['delete_orphans'] ) ) );
	}

	public function delete_pages(): void {
		$this->verify();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		wp_send_json_success( [ 'deleted' => PSEO_Generator::delete_generated( (int) $_POST['project_id'] ) ] );
	}

	public function preview_data(): void {
		$this->verify();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		$project = PSEO_Database::get_project( (int) $_POST['project_id'] );
		if ( ! $project ) wp_send_json_error( [ 'message' => 'Project not found.' ] );
		$rows = PSEO_DataSource::fetch( $project );
		wp_send_json_success( [ 'count' => count( $rows ), 'preview' => array_slice( $rows, 0, 5 ), 'columns' => array_keys( $rows[0] ?? [] ) ] );
	}

	public function save_project(): void {
		$this->verify();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		$data = [
			'name'			=> sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'post_type'		=> sanitize_key( wp_unslash( $_POST['post_type'] ?? 'page' ) ),
			'template_id'	=> (int) ( $_POST['template_id'] ?? 0 ),
			'source_type'	=> sanitize_key( wp_unslash( $_POST['source_type'] ?? 'csv_url' ) ),
						'source_config' => sanitize_textarea_field( wp_unslash( $_POST['source_config'] ?? '{}' ) ),
			'url_pattern'	=> sanitize_text_field( wp_unslash( $_POST['url_pattern'] ?? '' ) ),
			'seo_title'		=> sanitize_text_field( wp_unslash( $_POST['seo_title'] ?? '' ) ),
			'seo_desc'		=> sanitize_textarea_field( wp_unslash( $_POST['seo_desc'] ?? '' ) ),
			'robots'		=> sanitize_text_field( wp_unslash( $_POST['robots'] ?? 'index,follow' ) ),
			'schema_type'	=> sanitize_text_field( wp_unslash( $_POST['schema_type'] ?? '' ) ),
			'sync_interval'	=> sanitize_key( wp_unslash( $_POST['sync_interval'] ?? 'manual' ) ),
		];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		if ( ! empty( $_POST['id'] ) ) $data['id'] = (int) $_POST['id'];
		wp_send_json_success( [ 'id' => PSEO_Database::save_project( $data ) ] );
	}

	public function delete_project(): void {
		$this->verify();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify().
		$id = (int) ( $_POST['project_id'] ?? 0 );
		PSEO_Generator::delete_generated( $id );
		PSEO_Database::delete_project( $id );
		wp_send_json_success();
	}
}
