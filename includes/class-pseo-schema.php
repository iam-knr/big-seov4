<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Schema {
    public function __construct() {
        add_action( 'wp_head', [ $this, 'output_schema' ], 5 );
    }

    public function output_schema(): void {
        if ( ! is_singular() ) return;
        $post_id     = get_the_ID();
        $project_id  = get_post_meta( $post_id, '_pseo_project_id', true );
        if ( ! $project_id ) return;
        $schema_type = get_post_meta( $post_id, '_pseo_schema_type', true );
        $row         = json_decode( get_post_meta( $post_id, '_pseo_row_data', true ), true ) ?: [];
        $post        = get_post( $post_id );
        $schema      = apply_filters( 'pseo_schema', self::build( $schema_type, $row, $post ), $schema_type, $row, $post );
        if ( empty( $schema ) ) return;
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }

    public static function build( string $type, array $row, \WP_Post $post ): array {
        return match( $type ) {
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
            'headline' => $p->post_title, 'description' => $r['description'] ?? wp_trim_words( wp_strip_all_tags( $p->post_content ), 25 ),
            'datePublished' => get_the_date( 'c', $p ), 'dateModified' => get_the_modified_date( 'c', $p ),
            'author' => [ '@type' => 'Person', 'name' => get_bloginfo( 'name' ) ],
            'publisher' => [ '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url() ],
        ];
    }

    private static function local_business( array $r, \WP_Post $p ): array {
        return [ '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
            'name' => $r['business_name'] ?? $p->post_title, 'description' => $r['description'] ?? '',
            'telephone' => $r['phone'] ?? '', 'url' => get_permalink( $p ),
            'address' => [ '@type' => 'PostalAddress', 'streetAddress' => $r['address'] ?? '',
                'addressLocality' => $r['city'] ?? '', 'addressRegion' => $r['state'] ?? '',
                'postalCode' => $r['zip'] ?? '', 'addressCountry' => $r['country'] ?? 'IN' ],
            'priceRange' => $r['price_range'] ?? '',
        ];
    }

    private static function product( array $r, \WP_Post $p ): array {
        $s = [ '@context' => 'https://schema.org', '@type' => 'Product',
            'name' => $r['product_name'] ?? $p->post_title, 'description' => $r['description'] ?? '', 'url' => get_permalink( $p ) ];
        if ( ! empty( $r['price'] ) ) $s['offers'] = [ '@type' => 'Offer', 'price' => $r['price'], 'priceCurrency' => $r['currency'] ?? 'INR', 'availability' => 'https://schema.org/InStock' ];
        return $s;
    }

    private static function faq_page( array $r, \WP_Post $p ): array {
        $faqs = []; $i = 1;
        while ( isset( $r["faq_q{$i}"], $r["faq_a{$i}"] ) ) {
            $faqs[] = [ '@type' => 'Question', 'name' => $r["faq_q{$i}"], 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $r["faq_a{$i}"] ] ];
            $i++;
        }
        return empty( $faqs ) ? [] : [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs ];
    }

    private static function breadcrumb( array $r, \WP_Post $p ): array {
        return [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url() ],
            [ '@type' => 'ListItem', 'position' => 2, 'name' => $p->post_title, 'item' => get_permalink( $p ) ],
        ]];
    }

    private static function job_posting( array $r, \WP_Post $p ): array {
        return [ '@context' => 'https://schema.org', '@type' => 'JobPosting',
            'title' => $r['job_title'] ?? $p->post_title, 'description' => $r['description'] ?? '',
            'datePosted' => get_the_date( 'c', $p ),
            'hiringOrganization' => [ '@type' => 'Organization', 'name' => $r['company'] ?? get_bloginfo( 'name' ) ],
            'jobLocation' => [ '@type' => 'Place', 'address' => [ '@type' => 'PostalAddress',
                'addressLocality' => $r['city'] ?? '', 'addressRegion' => $r['state'] ?? '', 'addressCountry' => $r['country'] ?? 'IN' ] ],
        ];
    }
}