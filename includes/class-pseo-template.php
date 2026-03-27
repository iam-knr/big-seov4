<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Template {

	/**
	 * Render a template string by replacing {{column}} placeholders with
	 * values from $row.
	 *
	 * FIX #6 — Correct processing order:
	 *   1. Replace {{placeholders}} (data substitution)
	 *   2. Strip any remaining unresolved {{placeholders}} (FIX #7)
	 *   3. Process [if:...][/if] conditionals
	 *   4. Apply spintax {A|B} LAST so it cannot corrupt conditional
	 *      syntax or operate on unresolved placeholder tokens.
	 *
	 * FIX #7 — After step 1, any {{placeholder}} whose key was not
	 *   found in $row is stripped (replaced with empty string) so that
	 *   spintax cannot accidentally treat the inner text as spin options.
	 */
	public static function render( string $tpl, array $row ): string {
		// Build a case-insensitive lookup map once.
		$lower_row = [];
		foreach ( $row as $key => $value ) {
			$lower_row[ strtolower( $key ) ] = $value;
		}

		// 1. Replace {{column}} and {{raw:column}} placeholders.
		foreach ( $lower_row as $key => $value ) {
			$tpl = str_replace( '{{' . $key . '}}',      esc_html( $value ), $tpl );
			$tpl = str_replace( '{{raw:' . $key . '}}', $value,             $tpl );
		}

		// FIX #7: strip any {{unresolved}} placeholders that had no
		// matching CSV column so they don't corrupt spintax output.
		$tpl = preg_replace( '/\{\{raw:[^}]+\}\}/', '', $tpl );
		$tpl = preg_replace( '/\{\{[^}]+\}\}/',     '', $tpl );

		// 2. FIX #6: process conditionals BEFORE spintax so that
		// {A|B} tokens inside [if:...] blocks aren't spun prematurely.
		$tpl = self::process_conditionals( $tpl, $row );

		// 3. Spintax {option A|option B} — applied last.
		$tpl = self::process_spintax( $tpl );

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
	 * FIX #7 complement: unresolved placeholders are stripped (empty string)
	 * rather than left as literal text in the slug.
	 */
	public static function build_slug( string $pattern, array $row ): string {
		$lower_row = [];
		foreach ( $row as $key => $value ) {
			$lower_row[ strtolower( $key ) ] = $value;
		}
		$pattern = preg_replace_callback(
			'/\{\{([^}]+)\}\}/',
			function ( $m ) use ( $lower_row ) {
				$key = strtolower( trim( $m[1] ) );
				return isset( $lower_row[ $key ] )
					? sanitize_title( $lower_row[ $key ] )
					: '';
			},
			$pattern
		);
		return trim( $pattern, '/' );
	}
}
