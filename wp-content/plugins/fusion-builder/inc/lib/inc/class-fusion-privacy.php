<?php
/**
 * Fusion-Privacy handler.
 *
 * @package Fusion-Library
 * @since 1.5.2
 */

/**
 * Handle Privacy related stuff.
 *
 * @since 1.5.2
 */
class Fusion_Privacy {

	/**
	 * The screens where notices will be displayed.
	 *
	 * @access private
	 * @since 1.5.2
	 * @var string
	 */
	private $screens;

	/**
	 * The contents of message.
	 *
	 * @access private
	 * @since 1.5.2
	 * @var string
	 */
	private $message;

	/**
	 * Array of data which is sent to the server.
	 *
	 * @access private
	 * @since 1.5.2
	 * @var string
	 */
	private $server_data;

	/**
	 * Current screen.
	 *
	 * @access private
	 * @since 1.5.2
	 * @var string
	 */
	private $current_screen;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add the notices.
		add_action( 'current_screen', [ $this, 'display_notice' ] );

		// Handle saving the data via ajax.
		add_action( 'wp_ajax_fusion_dismiss_admin_notice', [ $this, 'dismiss_notice' ] );
	}

	/**
	 * Check if we're on the right screen and display notice.
	 *
	 * @access public
	 * @since 1.5.2
	 * @return void
	 */
	public function display_notice() {
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->current_screen = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$this->screens        = $this->get_allowed_screens();
			$this->server_data    = $this->get_server_data();
			$this->message        = $this->get_message_contents( $this->current_screen );

			if ( class_exists( 'Fusion_Admin_Notice' ) && $this->is_show() && ( isset( $this->current_screen ) && in_array( $this->current_screen, $this->screens, true ) ) ) {
				new Fusion_Admin_Notice(
					'fusion-privacy-notice',
					$this->message,
					is_super_admin(),
					'info',
					true,
					'user_meta',
					'the-meta',
					[ 'avada_page_' . $this->current_screen ]
				);
			}
		}
	}

	/**
	 * Dmismiss notice.
	 *
	 * @access public
	 * @since 1.5.2
	 * @return void
	 */
	public function dismiss_notice() {
		check_ajax_referer( 'fusion_admin_notice', 'nonce' );

		if ( ! empty( $_POST ) && isset( $_POST['data'] ) ) {
			$option = '';
			if ( isset( $_POST['data']['dismissOption'] ) ) {
				$option = sanitize_text_field( wp_unslash( $_POST['data']['dismissOption'] ) );
			} elseif ( isset( $_POST['data']['dismiss-option'] ) ) {
				$option = sanitize_text_field( wp_unslash( $_POST['data']['dismiss-option'] ) );
			}

			$type = '';
			if ( isset( $_POST['data']['dismissType'] ) ) {
				$type = sanitize_text_field( wp_unslash( $_POST['data']['dismissType'] ) );
			} elseif ( isset( $_POST['data']['dismiss-type'] ) ) {
				$type = sanitize_text_field( wp_unslash( $_POST['data']['dismiss-type'] ) );
			}

			switch ( $type ) {
				case 'user_meta':
					update_user_meta( get_current_user_id(), $option, true );
					break;
			}
		}

		wp_die();
	}

	/**
	 * Get list of screens where notice should be displayed.
	 *
	 * @access private
	 * @since 1.5.2
	 * @return array
	 */
	private function get_allowed_screens() {
		$screens = [
			'avada-fusion-patcher',
			'avada-registration',
			'avada-plugins',
			'avada-demos',
		];

		return $screens;
	}

	/**
	 * Array of data which is sent to server.
	 *
	 * @access private
	 * @since 1.5.2
	 * @return array
	 */
	private function get_server_data() {
		global $wp_version;
		$data = [
			'server'    => [
				'name'  => esc_html__( 'PHP Version', 'fusion-builder' ),
				'value' => phpversion(),
			],
			'php'       => [
				'name'  => esc_html__( 'Server Software', 'fusion-builder' ),
				'value' => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			],
			'wp'        => [
				'name'  => esc_html__( 'WordPress Version', 'fusion-builder' ),
				'value' => $wp_version,
			],
			'avada_ver' => [
				'name'  => esc_html__( 'Avada Version', 'fusion-builder' ),
				'value' => ( defined( 'AVADA_VERSION' ) ) ? AVADA_VERSION : '',
			],
			'url'       => [
				'name'  => esc_html__( 'Encrypted Site URL', 'fusion-builder' ),
				'value' => md5( site_url() ),
			],
			'token'     => [
				'name'  => esc_html__( 'Token', 'fusion-builder' ),
				'value' => class_exists( 'Avada' ) ? Avada()->registration->get_token() : '',
			],
		];
		return $data;
	}

	/**
	 * Prepare message contents.
	 *
	 * @access private
	 * @since 1.5.2
	 * @param string $page current page slug.
	 * @return string
	 */
	private function get_message_contents( $page ) {
		switch ( $page ) {
			case 'avada-demos':
				$message = sprintf( '<p>%s</p>', esc_html__( 'Following data is sent to a ThemeFusion server located in the US to verify purchase and to ensure that demos are compatible with your install.', 'fusion-builder' ) );
				break;
			case 'avada-registration':
				$message = sprintf( '<p>%s</p>', esc_html__( 'Following data is sent to a ThemeFusion server located in the US to verify purchase.', 'fusion-builder' ) );
				break;
			case 'avada-plugins':
				$message = sprintf( '<p>%s</p>', esc_html__( 'Following data will be sent to a ThemeFusion server located in the US to verify purchase and to ensure that plugins are compatible with your install.', 'fusion-builder' ) );
				break;
			default:
				$message = sprintf( '<p>%s</p>', esc_html__( 'Following data is sent to a ThemeFusion server located in the US to ensure that patches are compatible with your install.', 'fusion-builder' ) );
		}
		$message .= '<table>';
		if ( 'avada-fusion-patcher' !== $page ) {
			$message .= sprintf( '<tr><td>%s:</td><td>%s</td></tr>', $this->server_data['token']['name'], $this->server_data['token']['value'] );
		} else {
			foreach ( $this->server_data as $i => $index ) {
				if ( 'Token' !== $index['name'] ) {
					$message .= sprintf( '<tr><td>%s:</td><td>%s</td></tr>', $index['name'], $index['value'] );
				}
			}
		}

		$message .= '</table>';

		$message .= sprintf( '<p>%s</p>', esc_html__( 'We will never collect any confidential data such as IP, email addresses or usernames.', 'fusion-builder' ) );

		return $message;
	}

	/**
	 * Check if message should be displayed or not?
	 *
	 * @access private
	 * @since 1.5.2
	 * @return bool
	 */
	private function is_show() {

		if ( 'avada-fusion-patcher' === $this->current_screen || ( ( 'avada-registration' === $this->current_screen || 'avada-plugins' === $this->current_screen || 'avada-demos' === $this->current_screen ) && class_exists( 'Avada' ) && Avada()->registration->is_registered() ) ) {
			return true;
		}

		return false;
	}
}
