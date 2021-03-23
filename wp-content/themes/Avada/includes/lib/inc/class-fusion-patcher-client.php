<?php
/**
 * Gets tha patches.
 *
 * @package Fusion-Library
 * @subpackage Fusion-Patcher
 */

/**
 * Handles getting patches remotely and preparing them for Avada.
 *
 * @since 1.0.0
 */
class Fusion_Patcher_Client {

	/**
	 * Patches array.
	 *
	 * @var bool|array
	 */
	public static $patches;

	/**
	 * The URL of the patches remote server.
	 *
	 * @static
	 * @var string
	 */
	public static $remote_patches_uri = 'https://updates.theme-fusion.com/avada_patch/';

	/**
	 * Gets an array of all our patches.
	 * If we have these cached then use caches,
	 * otherwise query the server.
	 *
	 * @param array $args An array of arguments inherited from Fusion_Patcher.
	 * @return array
	 */
	public static function get_patches( $args = [] ) {
		// Get a new instance of this object.
		$client = new self();
		// Set the $args property.
		$client->args = $args;
		// Get the patches.
		if ( $client->get_cached() ) {
			self::$patches = $client->get_cached();
		} else {
			self::$patches = $client->query_patch_server();
			// Cache the patches.
			$client->cache_response();
		}
		// Returns a formatted array of patches.
		return $client->prepare_patches( self::$patches );
	}

	/**
	 * Queries the patches server for a list of patches.
	 *
	 * @return bool|array
	 */
	private function query_patch_server() {
		global $is_apache, $is_IIS, $wp_version;
		$args = [];
		if ( ! empty( $this->args ) ) {
			$id                       = str_replace( '-', '_', $this->args['context'] );
			$args[ $id . '_version' ] = $this->args['version'];
			$args['limit']            = true;
		}
		$site_url = site_url();
		// EVERYTHING is anonymous.
		$args['site_url'] = md5( $site_url );
		if ( isset( $_SERVER ) ) {
			if ( $is_apache ) {
				$args['server_software'] = 'apache';
			} elseif ( $is_IIS ) {
				$args['server_software'] = 'iis';
			} elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$server_software         = sanitize_key( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
				$args['server_software'] = ( false !== strpos( $server_software, 'nginx' ) ) ? 'nginx' : urlencode( $server_software ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			}
		}
		if ( defined( 'PHP_VERSION_ID' ) ) {
			$args['php_version_id'] = PHP_VERSION_ID;
		}
		$args['wp_version'] = $wp_version;

		// Build the remote server URL using the provided version.
		$url = add_query_arg( $args, self::$remote_patches_uri );

		// Get the server response.
		$response = wp_remote_get(
			$url,
			[
				'user-agent' => 'fusion-patcher-client',
			]
		);

		// Return false if we couldn't get to the server.
		if ( is_wp_error( $response ) ) {
			// Add a message so that the user knows what happened.
			new Fusion_Patcher_Admin_Notices( 'server-unreachable', esc_html__( 'The ThemeFusion patches server could not be reached. Please contact your host to unblock the "https://updates.theme-fusion.com/" domain.', 'Avada' ) );
			return false;
		}

		// Return false if the response does not have a body.
		if ( ! isset( $response['body'] ) ) {
			return false;
		}
		$json = $response['body'];

		// Response may have comments from caching plugins making it invalid.
		if ( false !== strpos( $response['body'], '<!--' ) ) {
			$json = explode( '<!--', $json );
			return json_decode( $json[0] );
		}
		return json_decode( $json );
	}

	/**
	 * Decodes patches if needed.
	 *
	 * @return array
	 */
	private function prepare_patches() {
		self::$patches = (array) self::$patches;
		$patches       = [];

		if ( ! empty( self::$patches ) ) {
			foreach ( self::$patches as $patch_id => $patch_args ) {
				$patches[ $patch_id ] = (array) $patch_args;
				if ( empty( $patch_args ) ) {
					continue;
				}
				foreach ( $patch_args as $key => $patch ) {
					$patches[ $patch_id ][ $key ] = (array) $patch;
					foreach ( $patches[ $patch_id ]['patch'] as $patch_key => $args ) {
						$args                                        = (array) $args;
						$args['reference']                           = fusion_decode_input( $args['reference'] );
						$patches[ $patch_id ]['patch'][ $patch_key ] = $args;
					}
				}
			}
		}
		return $patches;
	}

	/**
	 * Gets the cached patches.
	 */
	private function get_cached() {
		$cache = new Fusion_Patcher_Cache();
		// Force getting new options from the server if needed.
		if ( $_GET && isset( $_GET['fusion-reset-cached-patches'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$cache->reset_caches();
			return false;
		}
		return $cache->get_cache( $this->args );
	}

	/**
	 * Caches the patches.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function cache_response() {

		if ( false !== self::$patches && ! empty( self::$patches ) ) {
			$cache = new Fusion_Patcher_Cache();
			$cache->set_cache( $this->args, self::$patches );
		}

	}
}
