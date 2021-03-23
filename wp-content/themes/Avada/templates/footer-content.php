<?php
/**
 * Footer content template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.3.0
 */

$c_page_id = Avada()->fusion_library->get_page_id();
/**
 * Check if the footer widget area should be displayed.
 */
?>
<?php if ( fusion_get_option( 'footer_widgets' ) ) : ?>
	<?php
	$footer_widget_columns           = (int) Avada()->settings->get( 'footer_widgets_columns' );
	$footer_widget_area_center_class = ( Avada()->settings->get( 'footer_widgets_center_content' ) ) ? ' fusion-footer-widget-area-center' : '';
	?>

	<footer class="fusion-footer-widget-area fusion-widget-area<?php echo esc_attr( $footer_widget_area_center_class ); ?>">
		<div class="fusion-row">
			<div class="fusion-columns fusion-columns-<?php echo esc_attr( $footer_widget_columns ); ?> fusion-widget-area">
				<?php
				/**
				 * Check the column width based on the amount of columns chosen in Theme Options.
				 */
				$footer_widget_columns = ( ! $footer_widget_columns ) ? 1 : $footer_widget_columns;
				$column_width          = ( 5 === $footer_widget_columns ) ? 2 : 12 / $footer_widget_columns;
				?>

				<?php
				/**
				 * Render as many widget columns as have been chosen in Theme Options.
				 */
				?>
				<?php for ( $i = 1; $i < 7; $i++ ) : ?>
					<?php if ( $i <= $footer_widget_columns ) : ?>
						<?php
						$css_class = 'fusion-column' . ( $footer_widget_columns === $i ? ' fusion-column-last' : '' ) . ' col-lg-' . $column_width . ' col-md-' . $column_width . ' col-sm-' . $column_width;
						if ( Avada()->settings->get( 'footer_divider_line' ) ) {
							$css_class .= ( 0 < fusion_count_widgets( 'avada-footer-widget-' . $i ) ? ' fusion-has-widgets' : ' fusion-empty-area' );
						}
						?>
						<div class="<?php echo esc_attr( $css_class ); ?>">
							<?php if ( function_exists( 'dynamic_sidebar' ) && dynamic_sidebar( 'avada-footer-widget-' . $i ) ) : ?>
								<?php
								/**
								 * All is good, dynamic_sidebar() already called the rendering.
								 */
								?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endfor; ?>

				<div class="fusion-clearfix"></div>
			</div> <!-- fusion-columns -->
		</div> <!-- fusion-row -->
	</footer> <!-- fusion-footer-widget-area -->
<?php endif; // End footer wigets check. ?>

<?php
/**
 * Check if the footer copyright area should be displayed.
 */
?>
<?php if ( fusion_get_option( 'footer_copyright' ) ) : ?>
	<?php $footer_copyright_center_class = ( Avada()->settings->get( 'footer_copyright_center_content' ) ) ? ' fusion-footer-copyright-center' : ''; ?>

	<footer id="footer" class="fusion-footer-copyright-area<?php echo esc_attr( $footer_copyright_center_class ); ?>">
		<div class="fusion-row">
			<div class="fusion-copyright-content">

				<?php
				/**
				 * Footer Content (Copyright area) avada_footer_copyright_content hook.
				 *
				 * @hooked avada_render_footer_copyright_notice - 10 (outputs the HTML for the Theme Options footer copyright text)
				 * @hooked avada_render_footer_social_icons - 15 (outputs the HTML for the footer social icons)..
				 */
				do_action( 'avada_footer_copyright_content' );
				?>

			</div> <!-- fusion-fusion-copyright-content -->
		</div> <!-- fusion-row -->
	</footer> <!-- #footer -->
<?php endif; // End footer copyright area check. ?>
<?php
// Displays WPML language switcher inside footer if parallax effect is used.
if ( ( defined( 'WPML_PLUGIN_FILE' ) || defined( 'ICL_PLUGIN_FILE' ) ) && 'footer_parallax_effect' === Avada()->settings->get( 'footer_special_effects' ) ) {
	global $wpml_language_switcher;
	$slot = $wpml_language_switcher->get_slot( 'statics', 'footer' );
	if ( $slot->is_enabled() ) {
		echo $wpml_language_switcher->render( $slot ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
