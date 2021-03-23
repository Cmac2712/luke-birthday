<?php
/**
 * Registration handler.
 *
 * @package Fusion-Library
 * @since 1.0.0
 */

/**
 * A class to handle everything related to product registration
 *
 * @since 1.0.0
 */
class Fusion_Product_Registration {

	/**
	 * The option name.
	 *
	 * @access private
	 * @since 1.0.0
	 * @var string
	 */
	private $option_name = 'fusion_registration_data';

	/**
	 * Holding the available registration data.
	 *
	 * @access private
	 * @since 1.9.2
	 * @var array
	 */
	private $registration_data = [];

	/**
	 * The arguments that are used in the constructor.
	 *
	 * @access private
	 * @since 1.0.0
	 * @var array
	 */
	private $args = [];

	/**
	 * The product-name converted to ID.
	 *
	 * @access private
	 * @since 1.0.0
	 * @var string
	 */
	private $product_id = '';

	/**
	 * An array of bundled products.
	 *
	 * @static
	 * @access private
	 * @since 1.0.0
	 * @var array
	 */
	private static $bundled = [];

	/**
	 * Updater
	 *
	 * @access private
	 * @since 1.0.0
	 * @var null|object Fusion_Updater.
	 */
	private $updater = null;

	/**
	 * An instance of the Fusion_Envato_API class.
	 *
	 * @access private
	 * @since 1.0.0
	 * @var null|object Fusion_Envato_API.
	 */
	private $envato_api = null;

	/**
	 * Envato API response as WP_Error object.
	 *
	 * @access private
	 * @since 1.7
	 * @var null|object WP_Error.
	 */
	private $envato_api_error = null;

	/**
	 * The class constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $args An array of our arguments [string "type", string "name", array "bundled"].
	 */
	public function __construct( $args = [] ) {

		$this->args       = $args;
		$this->product_id = sanitize_key( $args['name'] );

		if ( isset( $args['bundled'] ) ) {
			$this->add_bundled_product( $args['bundled'] );
		}

		$this->set_registration_data();

		// Instantiate the updater.
		if ( null === $this->updater ) {
			$this->updater = new Fusion_Updater( $this );
		}
	}

	/**
	 * Adds a product to the array of bundled products.
	 *
	 * @access private
	 * @since 1.0.0
	 * @param array $bundled An array o bundled products.
	 */
	private function add_bundled_product( $bundled ) {

		$bundled = (array) $bundled;
		foreach ( $bundled as $product_slug => $product_name ) {
			$product = sanitize_key( $product_name );

			if ( ! isset( self::$bundled[ $product ] ) ) {
				self::$bundled[ $product ] = $this->args['name'];
			}
		}
	}

	/**
	 * Gets bundled products array.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array
	 */
	public function get_bundled() {

		return self::$bundled;
	}

	/**
	 * Gets the arguments.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array
	 */
	public function get_args() {

		return $this->args;
	}

	/**
	 * Envato API class.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return Fusion_Envato_API
	 */
	public function envato_api() {

		if ( null === $this->envato_api ) {
			$this->envato_api = new Fusion_Envato_API( $this );
		}
		return $this->envato_api;
	}

	/**
	 * Checks if the product is part of the themes or plugins
	 * purchased by the user belonging to the token.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function check_registration() {

		// Sanity check. No need to do anything if we're not saving the form.
		if ( isset( $_POST[ $this->option_name ] ) && isset( $_POST[ $this->option_name ][ $this->product_id ] ) && isset( $_POST['_wpnonce'] ) ) {

			// Security check.
			check_admin_referer( $this->option_name . '_' . $this->product_id );

			// The new token.
			$token = '';
			if ( isset( $_POST[ $this->option_name ][ $this->product_id ]['token'] ) ) {
				$token = sanitize_text_field( wp_unslash( $_POST[ $this->option_name ][ $this->product_id ]['token'] ) );
			}
			$token = wp_strip_all_tags( trim( $token ) );

			// Update saved product data.
			$this->registration_data[ $this->product_id ]['token']    = $token;
			$this->registration_data[ $this->product_id ]['is_valid'] = $this->product_exists( $token );
			$this->registration_data[ $this->product_id ]['scopes']   = $this->envato_api()->get_token_scopes( $token );

			update_option( $this->option_name, $this->registration_data );
		}
	}

	/**
	 * Checks if the product is part of the themes or plugins
	 * purchased by the user belonging to the token.
	 *
	 * @access private
	 * @since 1.0.0
	 * @param string $token A token to check.
	 * @param int    $page  The page number if one is necessary.
	 * @return bool
	 */
	private function product_exists( $token = '', $page = '' ) {

		if ( empty( $token ) && 32 !== strlen( $token ) ) {
			return false;
		}

		// Set the new token for the API call.
		if ( '' !== $token ) {
			$this->envato_api()->set_token( $token );
		}
		if ( 'theme' === $this->args['type'] ) {
			$products = $this->envato_api()->themes( [], $page );
		} elseif ( 'plugin' === $this->args['type'] ) {
			$products = $this->envato_api()->plugins( [], $page );
		}

		if ( is_wp_error( $products ) ) {
			$this->envato_api_error = $products;
			return false;
		}

		// Check iv product is part of the purchased themes/plugins.
		foreach ( $products as $product ) {
			if ( isset( $product['name'] ) ) {
				if ( $this->args['name'] === $product['name'] ) {
					return true;
				}
			}
		}

		if ( 100 === count( $products ) ) {
			$page = ( ! $page ) ? 2 : $page + 1;
			return $this->product_exists( '', $page );
		}
		return false;
	}

	/**
	 * Set available registration data.
	 *
	 * @access public
	 * @since 1.9.2
	 * @return void
	 */
	public function set_registration_data() {
		$registration_data        = [];
		$registration_data_stored = get_option( $this->option_name, [] );

		$registration_data_dummy = [
			'token'         => '',
			'purchase_code' => '',
			'is_valid'      => 'false',
			'scopes'        => [],
		];

		foreach ( $registration_data_stored as $product => $data ) {
			$registration_data[ $product ] = wp_parse_args( $data, $registration_data_dummy );
		}

		$this->registration_data = $registration_data;
	}



	/**
	 * Get all available registration data.
	 *
	 * @access public
	 * @since 1.9.2
	 * @return array The registration data.
	 */
	public function get_registration_data() {

		return $this->registration_data;
	}

	/**
	 * Check if product is part of registration data and is also valid.
	 *
	 * @access public
	 * @since 1.9.2
	 * @param string $product_id The plugin/theme ID.
	 * @return bool
	 */
	public function is_registered( $product_id = '' ) {
		if ( ! $product_id ) {
			$product_id = $this->product_id;
		}

		// Is the product registered?
		if ( isset( $this->registration_data[ $product_id ] ) && true === $this->registration_data[ $product_id ]['is_valid'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the stored token for the product.
	 *
	 * @access public
	 * @since 1.0.0
	 * @param string $product_id The product-ID.
	 * @return string The current token.
	 */
	public function get_token( $product_id = '' ) {
		if ( '' === $product_id ) {
			$product_id = $this->product_id;
		}

		if ( isset( $this->registration_data[ $product_id ] ) ) {
			return $this->registration_data[ $product_id ]['token'];
		}

		return '';
	}

	/**
	 * Returns the stored purchase key for the product.
	 *
	 * @access public
	 * @since 1.9.2
	 * @param string $product_id The product-ID.
	 * @return string The current token.
	 */
	public function get_purchase_key( $product_id = '' ) {
		if ( '' === $product_id ) {
			$product_id = $this->product_id;
		}

		if ( isset( $this->registration_data[ $product_id ] ) ) {
			return $this->registration_data[ $product_id ]['purchase_key'];
		}

		return '';
	}

	/**
	 * Prints the registration form.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function the_form() {

		// Print styles.
		$this->form_styles();

		// No need to display anything if this is a bundled product.
		// TODO: Please note another implementation a few lines below.
		if ( isset( self::$bundled[ $this->product_id ] ) ) {
			return;
		}

		// Check registration.
		$this->check_registration();

		// Get the stored token.
		$token = $this->get_token();

		// Is the product registered?
		$is_registered = $this->is_registered();
		?>
		<div class="fusion-library-important-notice registration-form-container">
			<?php if ( $is_registered ) : ?>
				<p class="about-description"><?php esc_html_e( 'Congratulations! Thank you for registering your product.', 'Avada' ); ?></p>
			<?php else : ?>
				<p class="about-description"><?php esc_html_e( 'Please enter your Envato token to complete registration.', 'Avada' ); ?></p>
			<?php endif; ?>
			<div class="fusion-library-registration-form">
				<form id="fusion-library_product_registration" method="post">
					<?php $show_form = true; ?>
					<?php if ( isset( self::$bundled[ $this->product_id ] ) ) : ?>
						<?php if ( ! $token ) : ?>
							<?php $show_form = false; ?>
							<p style="width:100%;max-width:100%;">
								<?php
								printf(
									/* translators: The product name and whether it's a theme or plugin. */
									esc_html__( 'The %1$s %2$s is bundled in %3$s and no registration is required for it. Updates will be provided from %3$s. If however you have purchased %1$s separately and wish to enter a registration token for it in order to receive %2$s updates regardless of %3$s, please check this checkbox.', 'Avada' ),
									esc_attr( $this->args['name'] ),
									esc_attr( $this->args['type'] ),
									esc_attr( self::$bundled[ $this->product_id ] )
								);
								?>
								<input type="checkbox" id="reveal-registration-form" />
								<script>
								jQuery( document ).ready( function() {
									jQuery( '#reveal-registration-form' ).on( 'click', function() {
										jQuery( '.toggle-hidden' ).toggleClass( 'hidden' );
									} );
								});
								</script>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $token ) : ?>
						<?php if ( $is_registered ) : ?>
							<span class="dashicons dashicons-yes fusion-library-icon-key<?php echo ( ! $show_form ) ? ' toggle-hidden hidden' : ''; ?>"></span>
						<?php else : ?>
							<span class="dashicons dashicons-no fusion-library-icon-key<?php echo ( ! $show_form ) ? ' toggle-hidden hidden' : ''; ?>"></span>
						<?php endif; ?>
					<?php else : ?>
						<span class="dashicons dashicons-admin-network fusion-library-icon-key<?php echo ( ! $show_form ) ? ' toggle-hidden hidden' : ''; ?>"></span>
					<?php endif; ?>
					<input <?php echo ( ! $show_form ) ? 'class="toggle-hidden hidden" ' : ''; ?>type="text" name="<?php echo esc_attr( "{$this->option_name}[{$this->product_id}][token]" ); ?>" value="<?php echo esc_attr( $token ); ?>" />
					<?php wp_nonce_field( $this->option_name . '_' . $this->product_id ); ?>
					<?php
					$button_classes = [ 'primary', 'large', 'fusion-library-large-button', 'fusion-library-register' ];
					if ( ! $show_form ) {
						$button_classes[] = 'toggle-hidden';
						$button_classes[] = 'hidden';
					}
					?>
					<?php submit_button( esc_html__( 'Submit', 'Avada' ), $button_classes ); ?>
				</form>

				<?php if ( $token && ! $is_registered ) : ?>
					<p class="error-invalid-token">
						<?php if ( 36 === strlen( $token ) && 4 === substr_count( $token, '-' ) ) : ?>
							<?php esc_html_e( 'Registration could not be completed because the value entered above is a purchase code. A token key is needed to register. Please read the directions below to find out how to create a token key to complete registration.', 'Avada' ); ?>
						<?php elseif ( $this->envato_api_error ) : ?>
							<?php $error_code = $this->envato_api_error->get_error_code(); ?>
							<?php $error_message = str_replace( [ 'Unauthorized', 'Forbidden' ], '', $this->envato_api_error->get_error_message() ); ?>
							<?php /* translators: The server error code and the error message. */ ?>
							<?php printf( esc_html__( 'Invalid token, the server responded with code %1$s.%2$s', 'Avada' ), esc_html( $error_code ), esc_html( $error_message ) ); ?>
						<?php else : ?>
							<?php /* translators: The product name for the license. */ ?>
							<?php printf( esc_html__( 'Invalid token, or corresponding Envato account does not have %s purchased.', 'Avada' ), esc_html( $this->args['name'] ) ); ?>
						<?php endif; ?>
					</p>
				<?php elseif ( $token ) : ?>
					<?php $scopes_ok = $this->envato_api()->check_token_scopes( $this->registration_data[ $this->product_id ]['scopes'] ); ?>
					<?php if ( ! $scopes_ok ) : ?>
						<p class="error-invalid-token">
							<?php _e( 'Token does not have the necessary permissions. Please create a new token and make sure the following permissions are enabled for it: <strong>View Your Envato Account Username</strong>, <strong>Download Your Purchased Items</strong>, <strong>List Purchases You\'ve Made</strong>, <strong>Verify Purchases You\'ve Made</strong>.', 'Avada' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( ! $is_registered ) : ?>

					<div <?php echo ( ! $show_form ) ? 'class="toggle-hidden hidden" ' : ''; ?>style="font-size:17px;line-height:27px;margin-top:1em;padding-top:1em">
						<hr>

						<h3><?php esc_html_e( 'Instructions For Generating A Token', 'Avada' ); ?></h3>
						<ol>
							<li>
								<?php
								printf(
									/* translators: "Generate A Personal Token" link. */
									__( 'Click on this %1$s link. <strong>IMPORTANT:</strong> You must be logged into the same Themeforest account that purchased %2$s. If you are logged in already, look in the top menu bar to ensure it is the right account. If you are not logged in, you will be directed to login then directed back to the Create A Token Page.', 'Avada' ), // phpcs:ignore WordPress.Security.EscapeOutput
									'<a href="https://build.envato.com/create-token/?user:username=t&purchase:download=t&purchase:verify=t&purchase:list=t" target="_blank">' . esc_html__( 'Generate A Personal Token', 'Avada' ) . '</a>',
									esc_html( $this->args['name'] )
								);
								?>
							</li>
							<li>
								<?php
								_e( 'Enter a name for your token, then check the boxes for <strong>View Your Envato Account Username, Download Your Purchased Items, List Purchases You\'ve Made</strong> and <strong>Verify Purchases You\'ve Made</strong> from the permissions needed section. Check the box to agree to the terms and conditions, then click the <strong>Create Token button</strong>', 'Avada' ); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
							</li>
							<li>
								<?php
								_e( 'A new page will load with a token number in a box. Copy the token number then come back to this registration page and paste it into the field below and click the <strong>Submit</strong> button.', 'Avada' ); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
							</li>
							<li>
								<?php
								printf(
									/* translators: "documentation post" link. */
									esc_html__( 'You will see a green check mark for success, or a failure message if something went wrong. If it failed, please make sure you followed the steps above correctly. You can also view our %s for various fallback methods.', 'Avada' ),
									'<a href="https://theme-fusion.com/documentation/avada/getting-started/how-to-register-your-purchase/" target="_blank">' . esc_html__( 'documentation post', 'Avada' ) . '</a>'
								);
								?>
							</li>
						</ol>

					</div>

				<?php endif; ?>
			</div>
		</div>
		<?php

	}

	/**
	 * Print styles for the form.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function form_styles() {
		?>
		<style>
		.registration-form-container {
			float: left;
			width: 95%;
			margin-bottom: 0;
		}
		.fusion-library-important-notice {
			padding: 30px;
			background: #fff;
			margin: 0 0 30px;
		}
		.dashicons.dashicons-admin-network.fusion-library-icon-key {
			line-height: 30px;
			height: 30px;
			margin-right: 10px;
			width: 30px;
		}

		#fusion-library_product_registration {
			display: -webkit-flex;
			display: -ms-flexbox;
			display: flex;
			flex-wrap: wrap;

			-webkit-align-items: center;
			-ms-align-items: center;
			align-items: center;
		}

		.fusion-library-registration-form input[type="text"],
		.fusion-library-registration-form input#submit {
			margin: 0 1em;
			padding: 10px 15px;
			width: calc(100% - 2em - 180px);
			height: 40px;
		}

		.fusion-library-registration-form input#submit {
			margin: 0;
			width: 150px;
			line-height: 1;
		}

		#fusion-library_product_registration p.submit {
			margin: 0;
			padding: 0;
		}

		#fusion-library_product_registration .dashicons {
			margin: 0;
			color: #333333;
			width: 30px;
		}

		#fusion-library_product_registration .dashicons-yes {
			color: #43A047;
		}

		#fusion-library_product_registration .dashicons-no {
			color:#c00;
		}

		.fusion-library-important-notice p.error-invalid-token {
			margin: 1em 0 0 0;
			padding:1em;
			color:#fff;
			background-color:#c00;
			text-align:center;
		}
		</style>
		<?php
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
