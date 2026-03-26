<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Usage:
  *   wp bigseo-pages list
  *   wp bigseo-pages generate --id=3
  *   wp bigseo-pages generate --all --delete-orphans
  *   wp bigseo-pages delete-pages --id=3
 */
class PSEO_CLI {

    /** @subcommand list */
    public function list_projects( array $args, array $assoc ): void {
        $projects = PSEO_Database::get_projects();
        if ( empty( $projects ) ) { WP_CLI::line( 'No projects.' ); return; }
        $rows = array_map( fn( $p ) => [ 'ID' => $p->id, 'Name' => $p->name, 'Source' => $p->source_type, 'Sync' => $p->sync_interval ], $projects );
        WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $rows, [ 'ID', 'Name', 'Source', 'Sync' ] );
    }

    public function generate( array $args, array $assoc ): void {
        $del = isset( $assoc['delete-orphans'] );
        if ( isset( $assoc['all'] ) ) {
            foreach ( PSEO_Database::get_projects() as $p ) $this->_run( (int) $p->id, $del );
        } elseif ( isset( $assoc['id'] ) ) {
            $this->_run( (int) $assoc['id'], $del );
        } else {
            WP_CLI::error( 'Provide --id=<id> or --all.' );
        }
    }

    private function _run( int $id, bool $del ): void {
        WP_CLI::line( "Project #{$id}…" );
        $r = PSEO_Generator::run( $id, $del );
        foreach ( $r['errors'] as $e ) WP_CLI::warning( $e );
        WP_CLI::success( "Created {$r['created']}  Updated {$r['updated']}  Deleted {$r['deleted']}" );
    }

    /** @subcommand delete-pages */
    public function delete_pages( array $args, array $assoc ): void {
        WP_CLI::success( 'Deleted ' . PSEO_Generator::delete_generated( (int) ( $assoc['id'] ?? 0 ) ) . ' pages.' );
    }
}
