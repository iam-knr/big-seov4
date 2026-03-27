<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Template {

	/**
	 * Render a template string by replacing {{column}} placeholders with
	 * values from $row.
	 *
	 * FIX #3 (partial) — placeholder matching is now case-insensitive.
	 * If the CSV header is 'City' but the template uses '{{city}}' (or vice
	 * versa) it will still resolve correctly. We build a lower-cased lookup
	 * map so lookups always succeed regardless of header capitalisation.
	 */
	public static function render( string $tpl, array $row ): string {
		// Build a case-insensitive lookup map once.
		$lower_row = [];
		foreach ( $row as $key => $value ) {
			$lower_row[ strtolower( $key ) ] = $value;
		}

		// 1. Replace {{column}} and {{raw:column}} placeholders.
		foreach ( $lower_row as $key => $value ) {
			$tpl = str_replace( '{{' . $key . '}}',       esc_html( $value ), $tpl );
			$tpl = str_replace( '{{raw:' . $key . '}}',  $value,             $tpl );
		}

		// 2. Spintax  {option A|option B}
		$tpl = self::process_spintax( $tpl );

		// 3. Conditional blocks  [if:col=value]...[/if]
		$tpl = self::process_conditionals( $tpl, $row );

		return $tpl;
	}

	public static function process_spintax( string $text ): string {
		return preg_replace_callback(
			'/\{([^{}]+)\}/',
			fn( $m ) => ( $opts = explode( '|', $m[1] ) )[ array_rand( $opts ) ],
			$text
		);
	}

	public static function process_conditionals( string $text, array $row ): string {
		// Build case-insensitive lookup for conditionals too.
		$lower_row = [];
		foreach ( $row as $key => $value ) {
			$lower_row[ strtolower( $key ) ] = $value;
		}

		return preg_replace_callback(
			'/\[if:([a-zA-Z0-9_]+)([=!<>]+)([^\]]*)\](.*?)\[\/if\]/s',
			function ( $m ) use ( $lower_row ) {
				[ , $col, $op, $val, $content ] = $m;
				$actual = $lower_row[ strtolower( $col ) ] ?? '';
				return match ( $op ) {
					'='  => $actual == $val  ? $content : '',
					'!=' => $actual != $val  ? $content : '',
					'>'  => $actual >  $val  ? $content : '',
					'<'  => $actual <  $val  ? $content : '',
					'>=' => $actual >= $val  ? $content : '',
					'<=' => $actual <= $val  ? $content : '',
					default => '',
				};
			},
			$text
		);
	}

	/**
	 * Build a URL slug from a pattern like '/locations/{{city}}/{{service}}'.
	 *
	 * FIX #3 — matching is now case-insensitive. The pattern placeholder
	 * '{{City}}' and the CSV header 'city' (or any other capitalisation
	 * variant) will now resolve to the same value. Without this fix, unmatched
	 * placeholders stayed as literal text in the slug, causing all affected
	 * rows to produce identical slugs — so only the first page was ever
	 * created and the rest silently overwrote it.
	 */
	public static function build_slug( string $pattern, array $row ): string {
		// Lower-case all row keys for case-insensitive placeholder matching.
		$lower_row = [];
		foreach ( $row as $key => $value ) {
			$lower_row[ strtolower( $key ) ] = $value;
		}

		// Replace {{placeholder}} tokens (case-insensitive).
		$pattern = preg_replace_callback(
			'/\{\{([^}]+)\}\}/',
			function ( $m ) use ( $lower_row ) {
				$key = strtolower( trim( $m[1] ) );
				// If key not found, strip the placeholder (empty string avoids identical slugs).
				return isset( $lower_row[ $key ] )
					? sanitize_title( $lower_row[ $key ] )
					: '';
			},
			$pattern
		);

		return trim( $pattern, '/' );
	}
}
