<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Schema {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_schema' ], 5 );
	}

	public function output_schema(): void {
		if ( ! is_singular() ) return;
		$post_id    = get_the_ID();
		$project_id = get_post_meta( $post_id, '_pseo_project_id', true );
		if ( ! $project_id ) return;
		$schema_type = get_post_meta( $post_id, '_pseo_schema_type', true );
		$row         = json_decode( get_post_meta( $post_id, '_pseo_row_data', true ), true ) ?: [];
		$post        = get_post( $post_id );
		$schema      = apply_filters( 'pseo_schema', self::build( $schema_type, $row, $post ), $schema_type, $row, $post );
		if ( empty( $schema ) ) return;
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
	}

	public static function build( string $type, array $row, \WP_Post $post ): array {
		return match ( $type ) {
			'Article'       => self::article( $row, $post ),
			'LocalBusiness' => self::local_business( $row, $post ),
			'Product'       => self::product( $row, $post ),
			'FAQPage'       => self::faq_page( $row, $post ),
			'BreadcrumbList'=> self::breadcrumb( $row, $post ),
			'JobPosting'    => self::job_posting( $row, $post ),
			default         => [],
		};
	}

	private static function article( array $r, \WP_Post $p ): array {
		return [ '@context' => 'https://schema.org', '@type' => 'Article',
			'headline'      => $p->post_title,
			'description'   => $r['description'] ?? wp_trim_words( wp_strip_all_tags( $p->post_content ), 25 ),
			'datePublished' => get_the_date( 'c', $p ),
			'dateModified'  => get_the_modified_date( 'c', $p ),
			'author'        => [ '@type' => 'Person',       'name' => get_bloginfo( 'name' ) ],
			'publisher'     => [ '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url() ],
		];
	}

	private static function local_business( array $r, \WP_Post $p ): array {
		return [ '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
			'name'        => $r['business_name'] ?? $p->post_title,
			'description' => $r['description']   ?? '',
			'telephone'   => $r['phone']          ?? '',
			'url'         => get_permalink( $p ),
			'address'     => [ '@type' => 'PostalAddress',
				'streetAddress'   => $r['address'] ?? '',
				'addressLocality' => $r['city']    ?? '',
				'addressRegion'   => $r['state']   ?? '',
				'postalCode'      => $r['zip']     ?? '',
				'addressCountry'  => $r['country'] ?? '',
			],
			'priceRange'  => $r['price_range']    ?? '',
		];
	}

	private static function product( array $r, \WP_Post $p ): array {
		$s = [ '@context' => 'https://schema.org', '@type' => 'Product',
			'name'        => $r['product_name'] ?? $p->post_title,
			'description' => $r['description']  ?? '',
			'url'         => get_permalink( $p ),
		];
		if ( ! empty( $r['price'] ) ) {
			$s['offers'] = [ '@type' => 'Offer', 'price' => $r['price'], 'priceCurrency' => $r['currency'] ?? '', 'availability' => 'https://schema.org/InStock' ];
		}
		return $s;
	}

	/**
	 * FIX #13 — FAQ question and answer values are now sanitized with
	 * wp_kses_post() before being embedded in JSON-LD output to prevent
	 * raw HTML tags (including <script>) from reaching the schema block.
	 */
	private static function faq_page( array $r, \WP_Post $p ): array {
		$faqs = []; $i = 1;
		while ( isset( $r["faq_q_{$i}"], $r["faq_a_{$i}"] ) ) {
			$faqs[] = [
				'@type' => 'Question',
				'name'  => wp_strip_all_tags( $r["faq_q_{$i}"] ),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_kses_post( $r["faq_a_{$i}"] ),
				],
			];
			$i++;
		}
		return empty( $faqs ) ? [] : [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs ];
	}

	/**
	 * FIX #12 — BreadcrumbList now includes intermediate URL segments so
	 * the breadcrumb path matches the actual page URL hierarchy.
	 * e.g. /services/seo/london/ produces: Home > Services > SEO > London
	 */
	private static function breadcrumb( array $r, \WP_Post $p ): array {
		$items = [];
		// Build ancestor chain from WordPress post hierarchy.
		$ancestors = array_reverse( get_post_ancestors( $p ) );
		$position  = 1;
		// Always start with Home.
		$items[] = [ '@type' => 'ListItem', 'position' => $position++, 'name' => __( 'Home', 'knr-pseo-generator' ), 'item' => home_url() ];
		// Add each ancestor page.
		foreach ( $ancestors as $ancestor_id ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			];
		}
		// Add the current page.
		$items[] = [ '@type' => 'ListItem', 'position' => $position, 'name' => $p->post_title, 'item' => get_permalink( $p ) ];
		return [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items ];
	}

	/**
	 * FIX #11 — JobPosting datePosted is now read from the CSV row
	 * ($r['date_posted']) so it reflects the actual job posting date
	 * instead of the WordPress page creation/last-generation date.
	 * Falls back to the post publish date only when the row has no
	 * date_posted column.
	 */
	private static function job_posting( array $r, \WP_Post $p ): array {
		// Prefer CSV-supplied date; fall back to post date.
		$date_posted = ! empty( $r['date_posted'] )
			? date( 'c', strtotime( $r['date_posted'] ) )
			: get_the_date( 'c', $p );

		return [ '@context' => 'https://schema.org', '@type' => 'JobPosting',
			'title'             => $r['job_title']   ?? $p->post_title,
			'description'       => $r['description'] ?? '',
			'datePosted'        => $date_posted,
			'hiringOrganization'=> [ '@type' => 'Organization', 'name' => $r['company'] ?? get_bloginfo( 'name' ) ],
			'jobLocation'       => [ '@type' => 'Place', 'address' => [
				'@type'           => 'PostalAddress',
				'addressLocality' => $r['city']    ?? '',
				'addressRegion'   => $r['state']   ?? '',
				'addressCountry'  => $r['country'] ?? '',
			] ],
		];
	}
}
