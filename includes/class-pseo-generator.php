<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Generator {

	/**
	 * Generate (or update) all pages for a project.
	 *
	 * FIX #2 — after save_data_rows() we reload rows from the DB so every row
	 *           carries a valid __row_id (the auto-increment PK). Previously
	 *           the original $rows from DataSource::fetch() were used, which
	 *           never had __row_id set, so record_generated_page() always
	 *           stored data_row_id = 0.
	 *
	 * FIX #4 — slug collision handling: if two rows produce the same slug
	 *           (e.g. both cities sanitise to the same string) we now append a
	 *           numeric suffix (-2, -3 …) rather than silently overwriting the
	 *           first page with the second row's content.
	 */
	public static function run( int $project_id, bool $delete_orphans = false ): array {
		$project = PSEO_Database::get_project( $project_id );
		if ( ! $project ) return [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [ 'Project not found.' ] ];

		// Fetch raw rows from the configured data source.
		$raw_rows = PSEO_DataSource::fetch( $project );
		if ( empty( $raw_rows ) ) return [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [ 'No data rows returned from source.' ] ];

		// Persist raw rows to DB so they get auto-increment IDs.
		PSEO_Database::save_data_rows( $project_id, $raw_rows );

		// FIX #2: reload rows from DB so __row_id is the real PK, not 0.
		$rows = PSEO_Database::get_data_rows( $project_id );
		if ( empty( $rows ) ) {
			// Fallback: if get_data_rows is unavailable, use raw rows as-is.
			$rows = $raw_rows;
		}

		$template    = get_post( $project->template_id );
		$tpl_content = $template ? $template->post_content : '';
		$results     = [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [] ];

		$existing_ids = array_map( 'intval', PSEO_Database::get_generated_page_ids( $project_id ) );
		$seen_ids     = [];

		// Track slugs used in this run to detect within-batch collisions.
		$slugs_used = [];

		foreach ( $rows as $row ) {
			$row_id = (int) ( $row['__row_id'] ?? 0 );
			unset( $row['__row_id'] );

			$base_slug = PSEO_Template::build_slug( $project->url_pattern, $row );
			$title     = PSEO_Template::render( $project->seo_title ?: '{{title}}', $row );
			$content   = PSEO_Template::render( $tpl_content, $row );

			// FIX #4: resolve slug collisions by appending a numeric suffix.
			$slug   = $base_slug;
			$suffix = 2;
			while ( isset( $slugs_used[ $slug ] ) ) {
				$slug = $base_slug . '-' . $suffix;
				$suffix++;
			}
			$slugs_used[ $slug ] = true;

			$existing = get_posts( [
				'name'        => $slug,
				'post_type'   => $project->post_type,
				'post_status' => 'any',
				'numberposts' => 1,
			] );

			if ( $existing ) {
				$post_id = $existing[0]->ID;
				wp_update_post( [
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
				] );
				$results['updated']++;
			} else {
				$post_id = wp_insert_post( [
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => $project->post_type,
				] );
				if ( is_wp_error( $post_id ) ) {
					$results['errors'][] = $post_id->get_error_message();
					continue;
				}
				$results['created']++;
			}

			update_post_meta( $post_id, '_pseo_project_id', $project_id );
			update_post_meta( $post_id, '_pseo_row_data',   wp_json_encode( $row ) );
			update_post_meta( $post_id, '_pseo_seo_title',  PSEO_Template::render( $project->seo_title, $row ) );
			update_post_meta( $post_id, '_pseo_seo_desc',   PSEO_Template::render( $project->seo_desc, $row ) );
			update_post_meta( $post_id, '_pseo_robots',      $project->robots );
			update_post_meta( $post_id, '_pseo_schema_type', $project->schema_type );

			PSEO_Database::record_generated_page( $project_id, $row_id, $post_id, $slug );
			$seen_ids[] = $post_id;
		}

		if ( $delete_orphans ) {
			foreach ( array_diff( $existing_ids, $seen_ids ) as $pid ) {
				wp_delete_post( $pid, true );
				$results['deleted']++;
			}
		}

		return $results;
	}

	public static function run_scheduled_syncs(): void {
		global $wpdb;
		$projects = wp_cache_get( 'pseo_active_projects', 'pseo' );
		if ( false === $projects ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$projects = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pseo_projects WHERE sync_interval != 'manual' AND status = 'active'" );
			wp_cache_set( 'pseo_active_projects', $projects, 'pseo', 60 );
		}
		foreach ( $projects as $p ) self::run( (int) $p->id );
	}

	public static function delete_generated( int $project_id ): int {
		$ids   = PSEO_Database::get_generated_page_ids( $project_id );
		$count = 0;
		foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); $count++; }
		global $wpdb;
		wp_cache_delete( 'pseo_page_ids_' . $project_id, 'pseo' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $wpdb->prefix . 'pseo_pages', [ 'project_id' => $project_id ] );
		return $count;
	}
}
