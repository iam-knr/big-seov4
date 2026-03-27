<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_SeoMeta {

	/**
	 * Allowed robots directive tokens — FIX #9.
	 * Any value stored in _pseo_robots that is not in this list (or a
	 * comma-joined combination of these) is replaced with 'index,follow'
	 * to prevent arbitrary strings from being output in the robots meta.
	 */
	private const ALLOWED_ROBOTS_TOKENS = [
		'index', 'noindex', 'follow', 'nofollow',
		'noarchive', 'nosnippet', 'noodp', 'notranslate',
		'noimageindex', 'none', 'all',
	];

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_meta' ], 1 );
		add_filter( 'pre_get_document_title', [ $this, 'pre_title' ], 99 );
	}

	private function get_pseo_post_id(): ?int {
		if ( ! is_singular() ) return null;
		$id = get_the_ID();
		return get_post_meta( $id, '_pseo_project_id', true ) ? $id : null;
	}

	public function pre_title( string $title ): string {
		$post_id = $this->get_pseo_post_id();
		if ( ! $post_id ) return $title;
		return get_post_meta( $post_id, '_pseo_seo_title', true ) ?: $title;
	}

	public function output_meta(): void {
		$post_id = $this->get_pseo_post_id();
		if ( ! $post_id ) return;

		$desc   = get_post_meta( $post_id, '_pseo_seo_desc',  true );
		$robots = get_post_meta( $post_id, '_pseo_robots',    true ) ?: 'index,follow';

		// FIX #9: validate the robots value against the allowed token list.
		// Split on comma, strip spaces, check each token is in the allowed
		// set, then re-join.  Fall back to 'index,follow' if any token is
		// unrecognised to prevent arbitrary strings reaching the output.
		$robots = $this->sanitize_robots( $robots );

		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";
	}

	/**
	 * Validate a robots directive string.
	 *
	 * Splits on commas, trims whitespace, lower-cases each token, and
	 * checks it against ALLOWED_ROBOTS_TOKENS.  Numeric max- directives
	 * like 'max-snippet:-1' are also accepted.  If any token fails
	 * validation, the whole value is replaced with 'index,follow'.
	 */
	private function sanitize_robots( string $robots ): string {
		$tokens = array_map( 'trim', explode( ',', strtolower( $robots ) ) );
		foreach ( $tokens as $token ) {
			if (
				in_array( $token, self::ALLOWED_ROBOTS_TOKENS, true ) ||
				preg_match( '/^max-(snippet|image-preview|video-preview):-?\d+$/', $token )
			) {
				continue;
			}
			// Unknown token found — fall back to safe default.
			return 'index,follow';
		}
		return implode( ',', $tokens );
	}
}
