<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Sitemap {

	public function __construct() {
		add_action( 'init',              [ $this, 'register_rewrite' ] );
		add_action( 'template_redirect', [ $this, 'serve_sitemap' ] );
	}

	public function register_rewrite(): void {
		add_rewrite_rule( '^pseo-sitemap\.xml$', 'index.php?pseo_sitemap=1', 'top' );
		add_filter( 'query_vars', fn( $v ) => array_merge( $v, [ 'pseo_sitemap' ] ) );
	}

	public function serve_sitemap(): void {
		// FIX #14: check for the specific value '1', not just any truthy
		// query var, to match the rewrite rule exactly.
		if ( get_query_var( 'pseo_sitemap' ) !== '1' ) return;

		$projects = PSEO_Database::get_projects();
		$urls     = [];
		foreach ( $projects as $p ) {
			foreach ( PSEO_Database::get_generated_page_ids( (int) $p->id ) as $pid ) {
				$urls[] = [
					'loc'     => get_permalink( $pid ),
					'lastmod' => get_the_modified_date( 'Y-m-d', $pid ),
				];
			}
		}

		// FIX #15: send Content-Type header BEFORE any output so it is
		// never blocked by previously buffered PHP notices or warnings.
		// ob_clean() discards any output that may have been buffered so
		// far (e.g. from WordPress hooks that echo debug notices).
		if ( ob_get_level() ) {
			ob_end_clean();
		}
		header( 'Content-Type: application/xml; charset=UTF-8' );
		status_header( 200 );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $u ) {
			echo "  <url>\n";
			echo '    <loc>'     . esc_url( $u['loc'] )         . "</loc>\n";
			echo '    <lastmod>' . esc_html( $u['lastmod'] )    . "</lastmod>\n";
			echo "  </url>\n";
		}
		echo '</urlset>';
		exit;
	}
}
