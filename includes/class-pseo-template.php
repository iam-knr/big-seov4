<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_Template {

    public static function render( string $tpl, array $row ): string {
        // 1. Replace {{column}} and {{raw:column}} placeholders
        foreach ( $row as $key => $value ) {
            $tpl = str_replace( '{{' . $key . '}}',      esc_html( $value ), $tpl );
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
        return preg_replace_callback(
            '/\[if:([a-zA-Z0-9_]+)([=!<>]+)([^\]]*)\](.*?)\[\/if\]/s',
            function ( $m ) use ( $row ) {
                [ , $col, $op, $val, $content ] = $m;
                $actual = $row[ $col ] ?? '';
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

    public static function build_slug( string $pattern, array $row ): string {
        foreach ( $row as $key => $value ) {
            $pattern = str_replace( '{{' . $key . '}}', sanitize_title( $value ), $pattern );
        }
        return trim( $pattern, '/' );
    }
}