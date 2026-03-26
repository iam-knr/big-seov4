<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PSEO_DataSource {

    public static function fetch( object $project ): array {
        $config = json_decode( $project->source_config, true ) ?: [];
        switch ( $project->source_type ) {
            case 'csv_upload':
            case 'csv_url':        return self::fetch_csv( $config );
            case 'google_sheets':  return self::fetch_google_sheets( $config );
            case 'json_url':       return self::fetch_json( $config );
            case 'rest_api':       return self::fetch_rest_api( $config );
            default:               return [];
        }
    }

    public static function fetch_csv( array $config ): array {
        $path = $config['file_path'] ?? '';
        $url  = $config['file_url']  ?? '';
        if ( $path && file_exists( $path ) ) return self::parse_csv_string( file_get_contents( $path ) );
        if ( $url ) {
            $r = wp_remote_get( $url, [ 'timeout' => 30 ] );
            return is_wp_error( $r ) ? [] : self::parse_csv_string( wp_remote_retrieve_body( $r ) );
        }
        return [];
    }

    private static function parse_csv_string( string $content ): array {
        $content = str_replace( [ "\r\n", "\r" ], "\n", $content );
        $lines   = str_getcsv( $content, "\n" );
        if ( empty( $lines ) ) return [];
        $headers = array_map( 'trim', str_getcsv( array_shift( $lines ) ) );
        $rows    = [];
        foreach ( $lines as $line ) {
            if ( trim( $line ) === '' ) continue;
            $values = str_getcsv( $line );
            if ( count( $values ) === count( $headers ) ) {
                $rows[] = array_combine( $headers, $values );
            }
        }
        return $rows;
    }

    public static function fetch_google_sheets( array $config ): array {
        $sheet_id = $config['sheet_id'] ?? '';
        $gid      = $config['gid']      ?? '0';
        if ( ! $sheet_id ) return [];
        $url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv&gid={$gid}";
        $r   = wp_remote_get( $url, [ 'timeout' => 30 ] );
        return is_wp_error( $r ) ? [] : self::parse_csv_string( wp_remote_retrieve_body( $r ) );
    }

    public static function fetch_json( array $config ): array {
        $url  = $config['url']  ?? '';
        $path = $config['path'] ?? '';
        if ( ! $url ) return [];
        $r    = wp_remote_get( $url, [ 'timeout' => 30 ] );
        if ( is_wp_error( $r ) ) return [];
        $data = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( $path ) {
            foreach ( explode( '.', $path ) as $key ) $data = $data[ $key ] ?? [];
        }
        if ( ! is_array( $data ) ) return [];
        return isset( $data[0] ) && is_array( $data[0] ) ? $data : [ $data ];
    }

    public static function fetch_rest_api( array $config ): array {
        $url      = $config['url']       ?? '';
        $path     = $config['data_path'] ?? '';
        $headers  = $config['headers']   ?? [];
        $per_page = (int) ( $config['per_page']  ?? 100 );
        $max_pages = (int) ( $config['max_pages'] ?? 10 );
        $param    = $config['page_param'] ?? 'page';
        if ( ! $url ) return [];
        $all = []; $page = 1;
        do {
            $req_url  = add_query_arg( [ $param => $page, 'per_page' => $per_page ], $url );
            $response = wp_remote_get( $req_url, [ 'timeout' => 30, 'headers' => $headers ] );
            if ( is_wp_error( $response ) ) break;
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $path ) {
                foreach ( explode( '.', $path ) as $key ) $data = $data[ $key ] ?? [];
            }
            if ( ! is_array( $data ) || empty( $data ) ) break;
            $all = array_merge( $all, $data );
            $page++;
        } while ( $page <= $max_pages );
        return $all;
    }
}