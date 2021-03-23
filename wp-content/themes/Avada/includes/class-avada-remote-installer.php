<?php
/**
 * Handles remotely installing premium plugins.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Remote installer for premium plugins.
 * This only works with our custom server plugin.
 *
 * @since 5.0.0
 */
class Avada_Remote_Installer {

	/**
	 * The remote API URL.
	 *
	 * @access private
	 * @var string
	 */
	private $api_url = 'https://updates.theme-fusion.com/';

	/**
	 * The constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->api_url = trailingslashit( $this->api_url );
	}

	/**
	 * Checks token against API.
	 *
	 * @access private
	 * @param string $download The name of the download.
	 * @param string $token    The token.
	 * @return false|string    Returns false if invalid.
	 *                         If valid, then returns a nonce from the remote server.
	 */
	private function _get_nonce( $download, $token = false ) {

		// Tweak for Envato Hosted.
		$is_envato_hosted = (bool) ( defined( 'ENVATO_HOSTED_SITE' ) && ENVATO_HOSTED_SITE && defined( 'SUBSCRIPTION_CODE' ) && SUBSCRIPTION_CODE );
		$token            = ( $is_envato_hosted ) ? SUBSCRIPTION_CODE : $token;

		// Get any existing copy of our transient data.
		$saved_nonce = get_transient( 'avada_ri_' . $download . $token );
		if ( false === $saved_nonce ) {
			// It wasn't there, so regenerate the data and save the transient.
			$avada_version = Avada::get_theme_version();
			$url           = $this->api_url . '?avada_action=request_download&item_name=' . rawurlencode( $download ) . '&token=' . $token . '&ver=' . $avada_version;
			$url          .= ( $is_envato_hosted ) ? '&envato-hosted=true' : '';

			$response = wp_remote_get(
				$url,
				[
					'user-agent' => 'avada-user-agent',
				]
			);
			$body     = wp_remote_retrieve_body( $response );

			// Check for errors.
			$error_responses = [
				'Product not defined' => 'download-undefined',
				'Invalid Token'       => 'invalid-token',
			];
			foreach ( $error_responses as $key => $value ) {
				if ( false !== strpos( $body, $key ) ) {
					return false;
				}
			}
			$trimmed = trim( $body );
			$parts   = explode( '|', $trimmed );

			$saved_nonce = [];

			if ( 2 === count( $parts ) ) {

				$saved_nonce = [
					esc_attr( $parts[0] ),
					esc_attr( $parts[1] ),
				];
			} else {
				return false;
			}

			set_transient( 'avada_ri_' . $download . $token, $saved_nonce, 600 );
		}

		return $saved_nonce;

	}

	/**
	 * Gets the download URL for a plugin.
	 *
	 * @access public
	 * @since 5.0.0
	 * @param string       $download The plugin to download.
	 * @param string|false $token    Force-use a token, or use default if false.
	 * @return string|false
	 */
	public function get_package( $download, $token = false ) {

		// Try to get a cached response.
		$download_src = get_transient( 'avada_remote_installer_package_' . $download );

		// If we've got it cached, then return it.
		if ( false !== $download_src ) {
			return $download_src;
		}

		$token = ( ! $token ) ? Avada()->registration->get_token() : $token;

		// Source is not cached, retrieve it and cache it.
		// Check for token and then install if it's valid.
		$nonces = $this->_get_nonce( $download, $token );

		$registered = ( ! in_array( $download, [ 'Fusion Builder', 'Fusion Core' ], true ) ) ? Avada()->registration->is_registered() : true;

		if ( false !== $nonces && $registered ) {
			$api_args = [
				'avada_action' => 'get_download',
				'item_name'    => rawurlencode( $download ),
				'nonce'        => isset( $nonces[0] ) ? $nonces[0] : '',
				't'            => isset( $nonces[1] ) ? $nonces[1] : '',
				'ver'          => Avada::get_theme_version(),
			];

			$download_src = add_query_arg( $api_args, $this->api_url );
			set_transient( 'avada_remote_installer_package_' . $download, $download_src, 300 );

			return $download_src;
		}

		// Something went wrong, return false.
		return false;
	}

	/**
	 * Gets the download URL for a plugin.
	 *
	 * @since 5.3
	 * @access public
	 * @return bool True if subscription code is valid, false otherwise.
	 */
	public function validate_envato_hosted_subscription_code() {
		$nonce = $this->_get_nonce( 'Avada' );

		if ( is_array( $nonce ) ) {
			return true;
		}

		return false;
	}
}
