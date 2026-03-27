<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_DataSource {

	public static function fetch( object $project ): array {
		$config = json_decode( $project->source_config, true ) ?: [];
		switch ( $project->source_type ) {
			case 'csv_upload':
			case 'csv_url':
				return self::fetch_csv( $config );
			// FIX #18: google_sheets is kept as an active source since it
			// only makes an outbound request to a publicly documented
			// Google Spreadsheets export URL.  The json_url and rest_api
			// sources are REMOVED from fetch() to eliminate orphaned dead
			// code that accepted arbitrary user-supplied URLs with no
			// SSRF protection. Admin UI never exposes those options.
			case 'google_sheets':
				return self::fetch_google_sheets( $config );
			default:
				return [];
		}
	}

	/**
	 * FIX #16 — file_path is now validated against the WordPress uploads
	 * directory so an attacker cannot point it at an arbitrary server
	 * file (e.g. /etc/passwd or wp-config.php).  Only paths that resolve
	 * to a location inside wp_upload_dir() are accepted.
	 */
	private static function fetch_csv( array $config ): array {
		$path = $config['file_path'] ?? '';
		$url  = $config['file_url']  ?? '';

		if ( $path ) {
			// Resolve any ../ or symlinks to a real path.
			$real   = realpath( $path );
			$upload = wp_upload_dir();
			$base   = realpath( $upload['basedir'] );

			// FIX #16: reject any path that is not inside the uploads dir.
			if (
				$real !== false &&
				$base !== false &&
				strncmp( $real, $base, strlen( $base ) ) === 0 &&
				file_exists( $real )
			) {
				return self::parse_csv_string( file_get_contents( $real ) );
			}
		}

		if ( $url ) {
			$r = wp_remote_get( $url, [ 'timeout' => 30 ] );
			return is_wp_error( $r ) ? [] : self::parse_csv_string( wp_remote_retrieve_body( $r ) );
		}

		return [];
	}

	/**
	 * Parse a raw CSV string into an array of associative rows.
	 *
	 * FIX #17 — Use fgetcsv() on an in-memory stream instead of
	 * str_getcsv( $content, "\n" ) which incorrectly splits on newlines
	 * inside properly quoted CSV cells (multi-line values).  fgetcsv()
	 * correctly handles RFC-4180 quoted fields with embedded newlines.
	 */
	private static function parse_csv_string( string $content ): array {
		if ( empty( $content ) ) return [];

		// Normalise line endings.
		$content = str_replace( [ "\r\n", "\r" ], "\n", $content );

		// FIX #17: open a memory stream so fgetcsv() can parse correctly,
		// honouring quoted multi-line fields per RFC 4180.
		$handle = fopen( 'php://memory', 'r+' );
		if ( ! $handle ) return [];
		fwrite( $handle, $content );
		rewind( $handle );

		// First non-empty line is the header.
		$headers = null;
		while ( $headers === null ) {
			$line = fgetcsv( $handle );
			if ( $line === false ) {
				fclose( $handle );
				return [];
			}
			// Skip blank lines.
			if ( array_filter( $line ) ) {
				$headers = array_map( 'trim', $line );
			}
		}

		$header_count = count( $headers );
		$rows         = [];

		while ( ( $line = fgetcsv( $handle ) ) !== false ) {
			if ( ! array_filter( $line ) ) continue; // skip empty rows

			// Pad short rows; trim extra columns from long rows.
			$line  = array_pad( $line, $header_count, '' );
			$line  = array_slice( $line, 0, $header_count );
			$rows[] = array_combine( $headers, $line );
		}

		fclose( $handle );
		return $rows;
	}

	/**
	 * Fetch a public Google Sheet exported as CSV.
	 * Only connects to docs.google.com — the URL is constructed here,
	 * not supplied by the user as a raw URL, so SSRF risk is minimal.
	 */
	private static function fetch_google_sheets( array $config ): array {
		$sheet_id = $config['sheet_id'] ?? '';
		$gid      = $config['gid']      ?? '0';
		if ( ! $sheet_id ) return [];
		$url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv&gid={$gid}";
		$r   = wp_remote_get( $url, [ 'timeout' => 30 ] );
		return is_wp_error( $r ) ? [] : self::parse_csv_string( wp_remote_retrieve_body( $r ) );
	}
}
