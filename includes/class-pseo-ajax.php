<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Ajax {

	public function __construct() {
		foreach ( [ 'pseo_generate', 'pseo_delete_pages', 'pseo_preview_data', 'pseo_save_project', 'pseo_delete_project', 'pseo_upload_csv' ] as $a )
			add_action( "wp_ajax_{$a}", [ $this, str_replace( 'pseo_', '', $a ) ] );
	}

	private function verify(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
		check_ajax_referer( 'pseo_nonce', 'nonce' );
	}

	public function generate(): void {
		$this->verify();
		$project_id    = (int) ( $_POST['project_id'] ?? 0 );
		$delete_orphans = ! empty( $_POST['delete_orphans'] );
		if ( ! $project_id ) wp_send_json_error( [ 'message' => 'Missing project ID.' ] );
		$result = PSEO_Generator::run( $project_id, $delete_orphans );
		wp_send_json_success( $result );
	}

	public function delete_pages(): void {
		$this->verify();
		$project_id = (int) ( $_POST['project_id'] ?? 0 );
		if ( ! $project_id ) wp_send_json_error( [ 'message' => 'Missing project ID.' ] );
		$deleted = PSEO_Generator::delete_generated( $project_id );
		wp_send_json_success( [ 'deleted' => $deleted ] );
	}

	public function preview_data(): void {
		$this->verify();
		$project_id = (int) ( $_POST['project_id'] ?? 0 );
		if ( ! $project_id ) wp_send_json_error( [ 'message' => 'Missing project ID.' ] );
		$project = PSEO_Database::get_project( $project_id );
		if ( ! $project ) wp_send_json_error( [ 'message' => 'Project not found.' ] );
		$all_rows = PSEO_DataSource::fetch( $project );
		$total    = count( $all_rows );
		$preview  = array_slice( $all_rows, 0, 5 );
		$columns  = ! empty( $preview ) ? array_keys( $preview[0] ) : [];
		wp_send_json_success( [ 'rows' => $preview, 'preview' => $preview, 'columns' => $columns, 'count' => $total ] );
	}

	public function save_project(): void {
		$this->verify();
		$project_id  = (int) ( $_POST['id'] ?? $_POST['project_id'] ?? 0 );
		$source_type = sanitize_text_field( wp_unslash( $_POST['source_type'] ?? '' ) );

		// Decode and individually sanitize source_config sub-fields.
		$raw_config   = json_decode( wp_unslash( $_POST['source_config'] ?? '{}' ), true ) ?: [];
		$clean_config = [];
		switch ( $source_type ) {
			case 'csv_upload':
				$clean_config['file_url']  = esc_url_raw( $raw_config['file_url'] ?? '' );
				$clean_config['file_path'] = sanitize_text_field( $raw_config['file_path'] ?? '' );
				break;
			case 'csv_url':
				// FIX: DataSource::fetch_csv() reads $config['file_url'], not $config['url'].
				$clean_config['file_url'] = esc_url_raw( $raw_config['file_url'] ?? $raw_config['url'] ?? '' );
				break;
			case 'google_sheets':
				$clean_config['sheet_id'] = sanitize_text_field( $raw_config['sheet_id'] ?? '' );
				$clean_config['gid']      = sanitize_text_field( $raw_config['gid'] ?? '' );
				break;
			case 'rest_api':
				$clean_config['url']        = esc_url_raw( $raw_config['url'] ?? '' );
				$clean_config['data_path']  = sanitize_text_field( $raw_config['data_path'] ?? '' );
				$clean_config['per_page']   = (int) ( $raw_config['per_page'] ?? 100 );
				$clean_config['max_pages']  = (int) ( $raw_config['max_pages'] ?? 10 );
				$clean_config['page_param'] = sanitize_text_field( $raw_config['page_param'] ?? 'page' );
				if ( ! empty( $raw_config['headers'] ) && is_array( $raw_config['headers'] ) ) {
					foreach ( $raw_config['headers'] as $k => $v ) {
						$clean_config['headers'][ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
					}
				}
				break;
			default:
				array_walk_recursive( $raw_config, function( &$v ) { $v = sanitize_text_field( (string) $v ); } );
				$clean_config = $raw_config;
		}

		$data = [
			'id'            => $project_id, // needed by save_project() to decide insert vs update
			'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'source_type'   => $source_type,
			'source_config' => wp_json_encode( $clean_config ),
			'url_pattern'   => sanitize_text_field( wp_unslash( $_POST['url_pattern'] ?? '' ) ),
			'post_type'     => sanitize_key( $_POST['post_type'] ?? 'page' ),
			'template_id'   => (int) ( $_POST['template_id'] ?? 0 ), // FIX: was 'template' (wrong key + no cast)
			'schema_type'   => sanitize_text_field( wp_unslash( $_POST['schema_type'] ?? '' ) ),
			'seo_title'     => sanitize_text_field( wp_unslash( $_POST['seo_title'] ?? '' ) ),
			'seo_desc'      => sanitize_text_field( wp_unslash( $_POST['seo_desc'] ?? '' ) ),
			'robots'        => sanitize_text_field( wp_unslash( $_POST['robots'] ?? 'index,follow' ) ),
			'sync_interval' => sanitize_key( $_POST['sync_interval'] ?? 'manual' ), // FIX: was missing
		];
		// FIX: 'post_status' is not a column in pseo_projects — removed to prevent DB error.

		$saved_id = PSEO_Database::save_project( $data );
		if ( ! $saved_id ) wp_send_json_error( [ 'message' => 'Failed to save project.' ] );
		$action = $project_id ? 'updated' : 'created';
		// FIX: JS reads data.id, not data.project_id.
		wp_send_json_success( [ 'id' => $saved_id, 'project_id' => $saved_id, 'action' => $action ] );
	}

	public function delete_project(): void {
		$this->verify();
		$project_id = (int) ( $_POST['project_id'] ?? 0 );
		if ( ! $project_id ) wp_send_json_error( [ 'message' => 'Missing project ID.' ] );
		PSEO_Database::delete_project( $project_id );
		wp_send_json_success( [ 'deleted' => true ] );
	}

	public function upload_csv(): void {
		$this->verify();
		if ( empty( $_FILES['csv_file'] ) ) wp_send_json_error( [ 'message' => 'No file uploaded.' ] );
		$file = $_FILES['csv_file'];
		$mime = wp_check_filetype( $file['name'], [ 'csv' => 'text/csv' ] );
		if ( ! $mime['type'] ) wp_send_json_error( [ 'message' => 'Invalid file type. Only CSV allowed.' ] );
		$upload = wp_handle_upload( $file, [ 'test_form' => false, 'mimes' => [ 'csv' => 'text/csv' ] ] );
		if ( isset( $upload['error'] ) ) wp_send_json_error( [ 'message' => $upload['error'] ] );
		wp_send_json_success( [ 'url' => $upload['url'], 'file' => $upload['file'] ] );
	}
}
