<?php
/**
 * Admin Screen markup (Addons page).
 *
 * @package fusion-builder
 */

?>
<div class="wrap about-wrap fusion-builder-wrap">

	<?php Fusion_Builder_Admin::header(); ?>

	<div class="fusion-builder-important-notice">
		<p class="about-description">
			<?php /* translators: %1$s: link attributes. %2$s: email-to link. */ ?>
			<?php printf( __( 'The Fusion Builder plugin has been created with extensibility as a key factor. Creating Add-ons for the Builder will extend the plugin\'s functionality and provide users with the tools to create even more dynamic and complex content and value added services. Generating an ecosystem to extend and evolve Fusion Builder will be easier than ever before and beneficial to everyone who uses it. To learn more about how to get involved by creating Add-ons for the Fusion Builder, please check out the <a %1$s>developer documentation</a> and email us at %2$s to potentially be promoted here.', 'fusion-builder' ), 'href="https://theme-fusion.com/documentation/fusion-builder/api/" target="_blank"', '<a href="malto:support@theme-fusion.com" target="_blank">support@theme-fusion.com</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<br/><br/><?php _e( '<strong>IMPORTANT:</strong> Add-ons are only supported by the author who created them.', 'fusion-builder' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</p>
	</div>

	<div class="avada-registration-steps">

		<div class="feature-section theme-browser rendered fusion-builder-addons">
			<?php
			$addons_json = ( isset( $_GET['reset_transient'] ) ) ? false : get_site_transient( 'fusion_builder_addons_json' ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! $addons_json ) {
				$response    = wp_remote_get(
					'https://updates.theme-fusion.com/fusion_builder_addon/',
					[
						'timeout'    => 30,
						'user-agent' => 'fusion-builder',
					]
				);
				$addons_json = wp_remote_retrieve_body( $response );
				set_site_transient( 'fusion_builder_addons_json', $addons_json, 300 );
			}
			$addons = json_decode( $addons_json, true );
			// Move coming_soon to the end.
			if ( isset( $addons['415041'] ) ) {
				$coming_soon = $addons['415041'];
				unset( $addons['415041'] );
				$addons['coming-soon'] = $coming_soon;
			}
			$n                 = 0;
			$installed_plugins = get_plugins();
			?>
			<?php foreach ( $addons as $id => $addon ) : ?>
				<?php
				$addon_info   = fusion_get_plugin_info( $addon['plugin_name'], $installed_plugins );
				$active_class = '';
				if ( is_array( $addon_info ) ) {
					$active_class = ( $addon_info['is_active'] ) ? ' active' : ' installed';
				}
				?>
				<div class="fusion-admin-box">
					<div class="theme<?php echo esc_html( $active_class ); ?>">
						<div class="theme-wrapper">
							<div class="theme-screenshot">
								<img class="addon-image" src="" data-src="<?php echo esc_url_raw( $addon['thumbnail'] ); ?>" <?php echo ( ! empty( $addon['retinaThumbnail'] ) ) ? 'data-src-retina="' . esc_url_raw( $addon['retinaThumbnail'] ) . '"' : ''; ?> />
								<noscript>
									<img src="<?php echo esc_url_raw( $addon['thumbnail'] ); ?>" />
								</noscript>
							</div>
							<h3 class="theme-name" id="<?php esc_attr( $addon['post_title'] ); ?>">
								<?php echo ( is_array( $addon_info ) && $addon_info['is_active'] ) ? esc_html__( 'Active:', 'fusion-builder' ) : ''; ?>
								<?php echo esc_html( ucwords( str_replace( 'Fusion Builder ', '', $addon['post_title'] ) ) ); ?>
								<?php if ( is_array( $addon_info ) ) : ?>
								<div class="plugin-info">
										<?php
										$version = ( isset( $addon_info['Version'] ) ) ? $addon_info['Version'] : false;
										$author  = ( $addon_info['Author'] && $addon_info['AuthorURI'] ) ? "<a href='{$addon_info['AuthorURI']}' target='_blank'>{$addon_info['Author']}</a>" : false;

										if ( $version && $author ) :
											/* translators: %1$s: Version. %2$s: Author. */
											printf( __( 'v%1$s | %2$s', 'fusion-builder' ), $version, $author ); // phpcs:ignore WordPress.Security.EscapeOutput
										endif;
										?>
								</div>
							<?php endif; ?>
							</h3>
							<div class="theme-actions">
								<?php if ( 'coming-soon' !== $id ) : ?>
									<?php if ( is_array( $addon_info ) ) : ?>
										<?php if ( $addon_info['is_active'] ) : ?>
											<a class="button button-primary" href="<?php echo esc_url_raw( wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $addon_info['plugin_file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'deactivate-plugin_' . $addon_info['plugin_file'] ) ); ?>" target="_blank"><?php esc_attr_e( 'Deactivate', 'fusion-builder' ); ?></a>
										<?php else : ?>
											<a class="button button-primary" href="<?php echo esc_url_raw( wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon_info['plugin_file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $addon_info['plugin_file'] ) ); ?>" target="_blank"><?php esc_attr_e( 'Activate', 'fusion-builder' ); ?></a>
										<?php endif; ?>
									<?php else : ?>
										<a class="button button-primary button-get-addon" href="<?php echo esc_url_raw( add_query_arg( 'ref', 'ThemeFusion', $addon['url'] ) ); ?>" target="_blank"><?php esc_attr_e( 'Get Add-on', 'fusion-builder' ); ?></a>
									<?php endif; ?>
								<?php endif; ?>

							</div>
							<?php if ( isset( $addon['new'] ) && true === $addon['new'] ) : ?>
								<?php
								// Show the new badge for first 30 days after release.
								$now             = time();
								$date_difference = (int) floor( ( $now - $addon['date'] ) / ( 60 * 60 * 24 ) );

								if ( 30 >= $date_difference ) :
									?>
									<div class="plugin-required"><?php esc_attr_e( 'New', 'fusion-builder' ); ?></div>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php $n++; ?>
			<?php endforeach; ?>
		</div>
		<script>
			jQuery( document ).ready( function() {
				var images = jQuery( '.addon-image' ),
					isRetina = window.devicePixelRatio > 1 ? true : false;
				jQuery.each( images, function( i, v ) {
					var imageSrc = ( 'undefined' !== typeof jQuery( this ).data( 'src-retina' ) && isRetina ) ? jQuery( this ).data( 'src-retina' ) : jQuery( this ).data( 'src' );
					jQuery( this ).attr( 'src', imageSrc );
				} );
			});
		</script>
	</div>
	<?php Fusion_Builder_Admin::footer(); ?>
</div>
