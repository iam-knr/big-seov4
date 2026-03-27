<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Generator {

	/**
	 * Interval map: project sync_interval value => seconds.
	 * Used by run_scheduled_syncs() to honour each project's configured
	 * interval rather than always re-generating every hour.
	 */
	private static array $interval_seconds = [
		'hourly' => HOUR_IN_SECONDS,
		'daily'  => DAY_IN_SECONDS,
		'weekly' => WEEK_IN_SECONDS,
	];

	/**
	 * Generate (or update) all pages for a project.
	 *
	 * FIX #3 — wp_update_post() return value is now checked for WP_Error
	 *           so update failures are captured in $results['errors'].
	 *
	 * FIX #4 — Orphan posts deleted from WordPress are also removed from
	 *           the pseo_pages table so the orphan list stays accurate.
	 *
	 * FIX #5 — seo_title fallback ('{{title}}') is used consistently for
	 *           both post_title and the _pseo_seo_title meta so the meta
	 *           is never stored as an empty string.
	 */
	public static function run( int $project_id, bool $delete_orphans = false ): array {
		$project = PSEO_Database::get_project( $project_id );
		if ( ! $project ) return [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [ 'Project not found.' ] ];

		$raw_rows = PSEO_DataSource::fetch( $project );
		if ( empty( $raw_rows ) ) return [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [ 'No data rows returned from source.' ] ];

		PSEO_Database::save_data_rows( $project_id, $raw_rows );

		$rows = PSEO_Database::get_data_rows( $project_id );
		if ( empty( $rows ) ) {
			$rows = array_values( $raw_rows );
			foreach ( $rows as $idx => &$row ) {
				$row['__row_id'] = $idx + 1;
			}
			unset( $row );
		}

		$template    = get_post( $project->template_id );
		$tpl_content = $template ? $template->post_content : '';
		$results     = [ 'created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => [] ];
		$existing_ids = array_map( 'intval', PSEO_Database::get_generated_page_ids( $project_id ) );
		$seen_ids    = [];
		$slugs_used  = [];

		foreach ( $rows as $row ) {
			$row_id = (int) ( $row['__row_id'] ?? 0 );
			unset( $row['__row_id'] );

			$base_slug = PSEO_Template::build_slug( $project->url_pattern, $row );

			// FIX #5: use the same title fallback for both post_title and meta.
			$seo_title_tpl = $project->seo_title ?: '{{title}}';
			$title         = PSEO_Template::render( $seo_title_tpl, $row );
			$content       = PSEO_Template::render( $tpl_content, $row );

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
				// FIX #3: check wp_update_post() for WP_Error.
				$update_result = wp_update_post( [
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
				], true );
				if ( is_wp_error( $update_result ) ) {
					$results['errors'][] = $update_result->get_error_message();
					continue;
				}
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
			// FIX #5: use consistent $seo_title_tpl with fallback.
			update_post_meta( $post_id, '_pseo_seo_title',  PSEO_Template::render( $seo_title_tpl, $row ) );
			update_post_meta( $post_id, '_pseo_seo_desc',   PSEO_Template::render( $project->seo_desc,  $row ) );
			update_post_meta( $post_id, '_pseo_robots',      $project->robots );
			update_post_meta( $post_id, '_pseo_schema_type', $project->schema_type );

			PSEO_Database::record_generated_page( $project_id, $row_id, $post_id, $slug );
			$seen_ids[] = $post_id;
		}

		if ( $delete_orphans ) {
			global $wpdb;
			foreach ( array_diff( $existing_ids, $seen_ids ) as $pid ) {
				wp_delete_post( $pid, true );
				// FIX #4: clean up the pseo_pages row so orphan detection
				// stays accurate on subsequent runs.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->prefix . 'pseo_pages', [ 'post_id' => $pid ] );
				$results['deleted']++;
			}
			wp_cache_delete( 'pseo_page_ids_' . $project_id, 'pseo' );
		}

		return $results;
	}

	/**
	 * Run cron syncs, but only for projects whose interval has elapsed
	 * since their last run (FIX #2 complement).
	 */
	public static function run_scheduled_syncs(): void {
		global $wpdb;
		$projects = wp_cache_get( 'pseo_active_projects', 'pseo' );
		if ( false === $projects ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$projects = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pseo_projects WHERE sync_interval != 'manual' AND status = 'active'" );
			wp_cache_set( 'pseo_active_projects', $projects, 'pseo', 60 );
		}
		$now = time();
		foreach ( $projects as $p ) {
			$interval_secs = self::$interval_seconds[ $p->sync_interval ] ?? HOUR_IN_SECONDS;
			$last_run      = (int) get_option( 'pseo_last_run_' . $p->id, 0 );
			if ( ( $now - $last_run ) >= $interval_secs ) {
				self::run( (int) $p->id );
				update_option( 'pseo_last_run_' . $p->id, $now, false );
			}
		}
	}

	public static function delete_generated( int $project_id ): int {
		$ids   = PSEO_Database::get_generated_page_ids( $project_id );
		$count = 0;
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
			$count++;
		}
		global $wpdb;
		wp_cache_delete( 'pseo_page_ids_' . $project_id, 'pseo' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $wpdb->prefix . 'pseo_pages', [ 'project_id' => $project_id ] );
		return $count;
	}
}
