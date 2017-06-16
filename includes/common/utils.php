<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Utils {

	public static function is_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}

	public static function is_script_debug() {
		return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
	}

	public static function get_edit_link( $post_id = 0 ) {
		return apply_filters( 'builder/utils/get_edit_link', add_query_arg( 'builder', '', get_permalink( $post_id ) ), $post_id );
	}

	public static function is_post_type_support( $post_id = 0 ) {
		$post_type = get_post_type( $post_id );
		$is_supported = post_type_supports( $post_type, 'builder' );

		return apply_filters( 'builder/utils/is_post_type_support', $is_supported, $post_id, $post_type );
	}

	public static function get_placeholder_image_src() {
		return apply_filters( 'builder/utils/get_placeholder_image_src', builder()->core_assets_url . 'images/placeholder.png' );
	}

	public static function generate_random_string( $length = 7 ) {
		$salt = 'abcdefghijklmnopqrstuvwxyz';
		return substr( str_shuffle( str_repeat( $salt, $length ) ), 0, $length );
	}

    /**
     * Get video id
     * Supports youtube and vimeo
     *
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
	public static function get_video_id_from_url( $url ) {

        $video_id = false;
        $video_data = array();

        // get video id
        if ( strpos( $url, 'youtu' ) ) {
            $video_data['host'] = 'youtube';
        } elseif ( strpos( $url, 'vimeo' ) ) {
            $video_data['host'] = 'vimeo';
        }

        $parts = parse_url( $url );

        if ( isset( $parts['query'] ) ) {

            parse_str( $parts['query'], $args );

            if ( isset( $args['v'] ) ) {
                $video_data['id'] = $args['v'];
                return $video_data;
            } else if ( isset( $args['vi'] ) ) {
                $video_data['id'] = $args['vi'];
                return $video_data;
            }
        }

        if ( isset( $parts['path'] ) ) {
            $path = explode( '/', trim( $parts['path'], '/' ) );
            $video_data['id'] = $path[ count( $path ) -1 ];
            return $video_data;
        }

        return $video_id;

	}

	/**
	 * Tell to WP Cache plugins do not cache this request.
	 *
	 * @return void
	 */
	public static function do_not_cache() {

		if ( ! defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', true );

		if ( ! defined( 'DONOTCACHEDB' ) )
			define( 'DONOTCACHEDB', true );

		if ( ! defined( 'DONOTMINIFY' ) )
			define( 'DONOTMINIFY', true );

		if ( ! defined( 'DONOTCDN' ) )
			define( 'DONOTCDN', true );

		if ( ! defined( 'DONOTCACHCEOBJECT' ) )
			define( 'DONOTCACHCEOBJECT', true );

		// Set the headers to prevent caching for the different browsers
		nocache_headers();
	}

	public static function get_timezone_string() {
		$current_offset = (float) get_option( 'gmt_offset' );
		$timezone_string = get_option( 'timezone_string' );

		// Create a UTC+- zone if no timezone string exists
		if ( empty( $timezone_string ) ) {
			if ( 0 === $current_offset ) {
				$timezone_string = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$timezone_string = 'UTC' . $current_offset;
			} else {
				$timezone_string = 'UTC+' . $current_offset;
			}
		}

		return $timezone_string;
	}

	public static function get_client_ip() {
		$server_ip_keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $server_ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
				return $_SERVER[ $key ];
			}
		}

		// Fallback local ip.
		return '127.0.0.1';
	}

	public static function get_site_domain() {
		return str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
	}

	public static function do_action_deprecated( $tag, $args, $version, $replacement = false, $message = null ) {
		if ( function_exists( 'do_action_deprecated' ) ) { /* WP >= 4.6 */
			do_action_deprecated( $tag, $args, $version, $replacement, $message );
		} else {
			do_action_ref_array( $tag, $args );
		}
	}

	public static function apply_filters_deprecated( $tag, $args, $version, $replacement = false, $message = null ) {
		if ( function_exists( 'apply_filters_deprecated' ) ) { /* WP >= 4.6 */
			return apply_filters_deprecated( $tag, $args, $version, $replacement, $message );
		} else {
			return apply_filters_ref_array( $tag, $args );
		}
	}
}