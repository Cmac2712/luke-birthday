<?php
/**
 * Registration Admin page.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>
<div class="wrap about-wrap avada-wrap">
	<?php $this->get_admin_screens_header( 'registration' ); ?>

	<!-- <p class="about-description"><span class="dashicons dashicons-admin-network avada-icon-key"></span><?php esc_attr_e( 'Your Purchase Must Be Registered To Receive Theme Support & Auto Updates', 'Avada' ); ?></p> -->
		<div class="feature-section avada-registration">
			<div class="avada-important-notice">
				<p class="about-description">
					<?php
					if ( Avada()->registration->is_registered() ) {
						esc_html_e( 'Thank you for choosing Avada! Your product is already registered, so you have access to the Avada demos, auto theme updates and included premium plugins.', 'Avada' );
					} else {
						esc_html_e( 'Thank you for choosing Avada! Your product must be registered to receive the Avada demos, auto theme updates and included premium plugins. Please follow the instructions below to create a valid token.', 'Avada' );
					}
					?>
				</p>
			</div>
		<?php
		/*
		 * Print the registration form.
		 */
		Avada()->registration->the_form();
		?>
	</div>
	<div class="avada-thanks">
		<p class="description"><?php esc_attr_e( 'Thank you for choosing Avada. We are honored and are fully dedicated to making your experience perfect.', 'Avada' ); ?></p>
	</div>
</div>
