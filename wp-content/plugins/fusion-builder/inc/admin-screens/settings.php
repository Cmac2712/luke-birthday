<?php
/**
 * Admin Screen markup (Settings page).
 *
 * @package fusion-builder
 */

global $all_fusion_builder_elements;
// If Fusion Builder is not bundled in another plugin/theme, it has its own options panel.
if ( null === FusionBuilder()->registration ) {
	$options_name = __( 'Fusion Element Theme Options', 'fusion-builder' );
} else {
	$options_name = __( 'Fusion Element Options', 'fusion-builder' );
}
?>

<div class="wrap about-wrap fusion-builder-wrap fusion-builder-settings">

	<?php Fusion_Builder_Admin::header(); ?>
	<?php $existing_settings = get_option( 'fusion_builder_settings' ); ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

		<div class="fusion-builder-important-notice">
			<p class="about-description">
				<?php
				printf(
					/* translators: Fusion Element Theme Options | Fusion Element Options. */
					esc_html__( 'Fusion Builder elements are fully customizable through a global set of options in the %1$s Panel. Click the button below to go to %1$s to see all the style settings available for of each element. Additionally, there are options below for general Fusion Builder settings.', 'fusion-builder' ),
					esc_html( $options_name )
				);
				?>
				<br/>
				<a href="<?php echo esc_url_raw( apply_filters( 'fusion_builder_options_url', admin_url( 'admin.php?page=fusion-element-options' ) ) ); ?>#alert_shortcode_section_start_accordion" class="button button-primary button-large avada-large-button">
					<?php
					printf(
						/* translators: Fusion Element Theme Options | Fusion Element Options. */
						esc_html__( 'Go To %s', 'fusion-builder' ),
						esc_html( $options_name )
					);
					?>
				</a>
			</p>
		</div>

		<div class="fusion-builder-settings">

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Fusion Builder Auto Activation', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'Turn on to enable the Fusion Builder user interface by default when opening a page or post. Turn off to enable the default WP editor view.', 'fusion-builder' ); ?></p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<div class="fusion-form-radio-button-set ui-buttonset enable-builder-ui">
						<?php
						$enable_builder_ui_by_default = '0';
						if ( isset( $existing_settings['enable_builder_ui_by_default'] ) ) {
							$enable_builder_ui_by_default = $existing_settings['enable_builder_ui_by_default'];
						}
						?>
						<input type="hidden" class="button-set-value" value="<?php echo esc_attr( $enable_builder_ui_by_default ); ?>" name="enable_builder_ui_by_default" id="enable_builder_ui_by_default">
						<a data-value="1" class="ui-button buttonset-item<?php echo ( $enable_builder_ui_by_default ) ? ' ui-state-active' : ''; ?>" href="#"><?php esc_html_e( 'On', 'fusion-builder' ); ?></a>
						<a data-value="0" class="ui-button buttonset-item<?php echo ( ! $enable_builder_ui_by_default ) ? ' ui-state-active' : ''; ?>" href="#"><?php esc_html_e( 'Off', 'fusion-builder' ); ?></a>
					</div>
				</div>
			</div>

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Fusion Builder Elements', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'Each Fusion Builder element can be enabled or disabled. This can increase performance if you are not using a specific element. Check the box to enable, uncheck to disable.', 'fusion-builder' ); ?></p>
						<p><?php _e( '<strong>NOTE:</strong> Elements for plugins like WooCommere or The Events Calendar will only be available in the builder, if the corresponding options are activated here and if those plugins are active.', 'fusion-builder' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
						<p><?php _e( '<strong>WARNING:</strong> Use with caution. Disabling an element will remove it from all pages/posts, old and new. If it was on a previous page/post, it will render as regular element markup on the frontend.', 'fusion-builder' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
						<p>
							<a href="#" class="button fusion-check-all" title="<?php esc_attr_e( 'Check All Elements', 'fusion-builder' ); ?>"><?php esc_attr_e( 'Check All Elements', 'fusion-builder' ); ?></a>

							<a href="#" class="button fusion-uncheck-all" title="<?php esc_attr_e( 'Uncheck All Elements', 'fusion-builder' ); ?>"><?php esc_attr_e( 'Uncheck All Elements', 'fusion-builder' ); ?></a>
						</p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<ul>
						<?php
						$i               = 0;
						$plugin_elements = [
							'fusion_featured_products_slider' => [
								'name'      => esc_attr__( 'Woo Featured', 'fusion-builder' ),
								'shortcode' => 'fusion_featured_products_slider',
								'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
							],
							'fusion_products_slider' => [
								'name'      => esc_attr__( 'Woo Carousel', 'fusion-builder' ),
								'shortcode' => 'fusion_products_slider',
								'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
							],
							'fusion_woo_shortcodes'  => [
								'name'      => esc_attr__( 'Woo Shortcodes', 'fusion-builder' ),
								'shortcode' => 'fusion_woo_shortcodes',
								'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
							],
							'layerslider'            => [
								'name'      => esc_attr__( 'Layer Slider', 'fusion-builder' ),
								'shortcode' => 'layerslider',
								'class'     => ( defined( 'LS_PLUGIN_BASE' ) ) ? '' : 'hidden',
							],
							'rev_slider'             => [
								'name'      => esc_attr__( 'Slider Revolution', 'fusion-builder' ),
								'shortcode' => 'rev_slider',
								'class'     => ( defined( 'RS_PLUGIN_PATH' ) ) ? '' : 'hidden',
							],
							'fusion_events'          => [
								'name'      => esc_attr__( 'Events', 'fusion-builder' ),
								'shortcode' => 'fusion_events',
								'class'     => ( class_exists( 'Tribe__Events__Main' ) ) ? '' : 'hidden',
							],
							'fusion_fontawesome'     => [
								'name'      => esc_attr__( 'Icon', 'fusion-builder' ),
								'shortcode' => 'fusion_fontawesome',
							],
							'fusion_fusionslider'    => [
								'name'      => esc_attr__( 'Fusion Slider', 'fusion-builder' ),
								'shortcode' => 'fusion_fusionslider',
							],
						];

						$all_fusion_builder_elements = array_merge( $all_fusion_builder_elements, apply_filters( 'fusion_builder_plugin_elements', $plugin_elements ) );

						usort( $all_fusion_builder_elements, 'fusion_element_sort' );
						foreach ( $all_fusion_builder_elements as $module ) :
							if ( empty( $module['hide_from_builder'] ) ) {
								$i++;
								$checked = '';
								$class   = ( isset( $module['class'] ) && '' !== $module['class'] ) ? $module['class'] : '';

								if ( ( isset( $existing_settings['fusion_elements'] ) && is_array( $existing_settings['fusion_elements'] ) && in_array( $module['shortcode'], $existing_settings['fusion_elements'] ) ) || ( ! isset( $existing_settings['fusion_elements'] ) || ! is_array( $existing_settings['fusion_elements'] ) ) || ( isset( $module['component'] ) && $module['component'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
									$checked = 'checked';
								}
								echo '<li class="' . esc_attr( $class ) . '">';
								echo '<label for="hide_from_builder_' . esc_attr( $i ) . '">';
								echo '<input name="fusion_elements[]" type="checkbox" value="' . esc_attr( $module['shortcode'] ) . '" ' . $checked . ' id="hide_from_builder_' . esc_attr( $i ) . '"/>'; // phpcs:ignore WordPress.Security.EscapeOutput
								echo $module['name'] . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
								echo '</li>';
							}
						endforeach;
						?>
					</ul>
				</div>
			</div>

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Post Types', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'Fusion Builder can be enabled or disabled on registered post types. Check the box to enable, uncheck to disable. Please note the Fusion element generator will still be active on any post type that is disabled.', 'fusion-builder' ); ?></p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<ul>
						<input type="hidden" name="post_types[]" value=" " />
						<?php
						$args       = [
							'public' => true,
						];
						$post_types = get_post_types( $args, 'names', 'and' );
						// Filter out not relevant post types (can add filter later).
						$disabled_post_types = [ 'attachment', 'slide', 'themefusion_elastic', 'fusion_template', 'fusion_tb_section', 'fusion_tb_layout' ];
						foreach ( $disabled_post_types as $disabled ) {
							unset( $post_types[ $disabled ] );
						}
						$defaults = FusionBuilder::default_post_types();
						$i        = 0;
						foreach ( $post_types as $post_type ) :
							$i++;
							$post_type_obj = get_post_type_object( $post_type );
							// Either selected in options saved, or in array of default post types.
							$checked = (
								( isset( $existing_settings['post_types'] ) && is_array( $existing_settings['post_types'] ) && in_array( $post_type, $existing_settings['post_types'] ) ) || // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								( ! isset( $existing_settings['post_types'] ) && in_array( $post_type, $defaults ) ) ) // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								? 'checked' : '';
							echo '<li>';
							echo '<label for="fusion_post_type_' . esc_attr( $i ) . '">';
							echo '<input type="checkbox" name="post_types[]" value="' . esc_attr( $post_type ) . '" ' . $checked . ' id="fusion_post_type_' . esc_attr( $i ) . '"/>'; // phpcs:ignore WordPress.Security.EscapeOutput
							echo $post_type_obj->labels->singular_name . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
							echo '</li>';
						endforeach;
						?>
						<input type="hidden" name="post_types[]" value="fusion_template" checked="checked" />
						<input type="hidden" name="post_types[]" value="fusion_tb_section" checked="checked" />
					</ul>
				</div>
			</div>

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Import Fusion Builder Content', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'Choose to import Fusion Builder content; custom saved containers / columns / elements or full page templates. Click "Choose File" and select your Fusion Builder XML file.', 'fusion-builder' ); ?></p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<form id="fusion-importer-form" method="post" enctype="multipart/form-data" name="fusion-importer-form">
						<input type="file" id="fusion-builder-import-file" name="fusion-builder-import-file" size="25" value="" accept=".xml" />
						<input type="submit" name="submit" id="submit" class="button fusion-builder-import-data" value="Import" disabled />
					</form>
					<div class="fusion-builder-import-success"><?php esc_html_e( 'Content Successfully  Imported', 'fusion-builder' ); ?></div>
				</div>
			</div>

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Export Fusion Builder Content', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'Choose to export Fusion Builder content; custom saved containers / columns / elements or full page templates. An XML file will be downloaded to your computer.' ); ?></p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=fusion-builder-options&fusion_action=export&fusion_export_type=fusion_element' ) ); ?>" class="button" title="<?php esc_attr_e( 'Export Fusion Elements from your Library', 'fusion-builder' ); ?>"><?php esc_html_e( 'Export Content', 'fusion-builder' ); ?></a>

					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=fusion-builder-options&fusion_action=export&fusion_export_type=fusion_template' ) ); ?>" class="button" title="<?php esc_attr_e( 'Export Fusion Templates from your Library', 'fusion-builder' ); ?>"><?php esc_html_e( 'Export Templates', 'fusion-builder' ); ?></a>
				</div>
			</div>

			<div class="fusion-builder-option">
				<div class="fusion-builder-option-title">
					<h3><?php esc_html_e( 'Sticky Preview / Publish Buttons', 'fusion-builder' ); ?></h3>
					<span class="fusion-builder-option-label">
						<p><?php esc_html_e( 'This option allows the preview and publish button to stick to the bottom of the page so you can quickly access them.', 'fusion-builder' ); ?></p>
					</span>
				</div>

				<div class="fusion-builder-option-field">
					<div class="fusion-form-radio-button-set ui-buttonset enable-builder-ui">
						<?php
						$enable_builder_sticky_publish_buttons = '1';
						if ( isset( $existing_settings['enable_builder_sticky_publish_buttons'] ) ) {
							$enable_builder_sticky_publish_buttons = $existing_settings['enable_builder_sticky_publish_buttons'];
						}
						?>
						<input type="hidden" class="button-set-value" value="<?php echo esc_attr( $enable_builder_sticky_publish_buttons ); ?>" name="enable_builder_sticky_publish_buttons" id="enable_builder_sticky_publish_buttons">
						<a data-value="1" class="ui-button buttonset-item<?php echo ( $enable_builder_sticky_publish_buttons ) ? ' ui-state-active' : ''; ?>" href="#"><?php esc_html_e( 'On', 'fusion-builder' ); ?></a>
						<a data-value="0" class="ui-button buttonset-item<?php echo ( ! $enable_builder_sticky_publish_buttons ) ? ' ui-state-active' : ''; ?>" href="#"><?php esc_html_e( 'Off', 'fusion-builder' ); ?></a>
					</div>
				</div>
			</div>

			<input type="hidden" name="action" value="save_fb_settings">
			<?php wp_nonce_field( 'fusion_builder_save_fb_settings', 'fusion_builder_save_fb_settings' ); ?>
			<input type="submit" class="button button-primary fusion-builder-save-settings" value="<?php esc_attr_e( 'Save Settings', 'fusion-builder' ); ?>" />

		</div>

	</form>

	<?php Fusion_Builder_Admin::footer(); ?>
</div>
