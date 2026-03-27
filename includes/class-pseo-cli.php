<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * FIX #21 — Command is registered as 'bigseo-pages' in bootstrap (knr-pseo-generator.php).
 *           Usage examples below now match that registered name.
 *
 * FIX #22 — All sub-commands now accept --project-id (and --id as a legacy alias)
 *           so users following the Settings page docs work without error.
 *
 * FIX #23 — delete-pages now verifies the project exists and requires an explicit
 *           --yes flag (or uses WP_CLI confirm) before deleting pages.
 *
 * Usage:
 *   wp bigseo-pages list
 *   wp bigseo-pages generate --project-id=3
 *   wp bigseo-pages generate --project-id=3 --delete-orphans
 *   wp bigseo-pages generate --all
 *   wp bigseo-pages delete-pages --project-id=3 --yes
 */
class PSEO_CLI {

	/**
	 * Resolve --project-id (preferred) or legacy --id from assoc args.
	 * Returns 0 if neither is set.
	 */
	private function get_project_id( array $assoc ): int {
		// FIX #22: accept --project-id as the primary flag; fall back to --id.
		return (int) ( $assoc['project-id'] ?? $assoc['id'] ?? 0 );
	}

	/** @subcommand list */
	public function list_projects( array $args, array $assoc ): void {
		$projects = PSEO_Database::get_projects();
		if ( empty( $projects ) ) {
			WP_CLI::line( 'No projects.' );
			return;
		}
		$rows = array_map(
			fn( $p ) => [
				'ID'     => $p->id,
				'Name'   => $p->name,
				'Source' => $p->source_type,
				'Sync'   => $p->sync_interval,
			],
			$projects
		);
		WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $rows, [ 'ID', 'Name', 'Source', 'Sync' ] );
	}

	/**
	 * Generate pages for one or all projects.
	 *
	 * ## OPTIONS
	 *
	 * [--project-id=<n>]
	 * : Project ID to generate. Alias: --id
	 *
	 * [--all]
	 * : Run all projects.
	 *
	 * [--delete-orphans]
	 * : Delete pages no longer in the data source.
	 *
	 * @subcommand generate
	 */
	public function generate( array $args, array $assoc ): void {
		$del = isset( $assoc['delete-orphans'] );
		if ( isset( $assoc['all'] ) ) {
			foreach ( PSEO_Database::get_projects() as $p ) {
				$this->_run( (int) $p->id, $del );
			}
			return;
		}
		// FIX #22: resolve project ID via shared helper.
		$id = $this->get_project_id( $assoc );
		if ( ! $id ) {
			WP_CLI::error( 'Provide --project-id=<id> or --all.' );
		}
		$this->_run( $id, $del );
	}

	private function _run( int $id, bool $del ): void {
		WP_CLI::line( "Project #{$id}…" );
		$r = PSEO_Generator::run( $id, $del );
		foreach ( $r['errors'] as $e ) WP_CLI::warning( $e );
		WP_CLI::success( "Created {$r['created']}  Updated {$r['updated']}  Deleted {$r['deleted']}" );
	}

	/**
	 * Delete all generated pages for a project.
	 *
	 * ## OPTIONS
	 *
	 * --project-id=<n>
	 * : Project ID whose pages should be deleted. Alias: --id
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * @subcommand delete-pages
	 */
	public function delete_pages( array $args, array $assoc ): void {
		// FIX #22: resolve project ID via shared helper.
		$id = $this->get_project_id( $assoc );
		if ( ! $id ) {
			WP_CLI::error( 'Provide --project-id=<id>.' );
		}

		// FIX #23: verify the project exists before doing anything.
		$project = PSEO_Database::get_project( $id );
		if ( ! $project ) {
			WP_CLI::error( "Project #{$id} not found." );
		}

		$count = count( PSEO_Database::get_generated_page_ids( $id ) );
		if ( $count === 0 ) {
			WP_CLI::line( "Project #{$id} has no generated pages." );
			return;
		}

		// FIX #23: require explicit confirmation before irreversible deletion.
		WP_CLI::confirm(
			sprintf( 'Delete %d page(s) for project "%s" (#%d)? This cannot be undone.', $count, $project->name, $id ),
			$assoc
		);

		$deleted = PSEO_Generator::delete_generated( $id );
		WP_CLI::success( 'Deleted ' . $deleted . ' page(s).' );
	}
}
