<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_SeoMeta {
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
        if ( $desc )   echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
        echo '<link rel="canonical" href="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";
    }
}