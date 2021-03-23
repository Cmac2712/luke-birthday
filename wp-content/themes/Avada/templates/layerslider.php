<?php
/**
 * LayerSlider template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1
 */

global $wpdb;

// Get slider.
$ls_table_name = $wpdb->prefix . 'layerslider';

if ( strtolower( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ls_table_name ) ) ) === strtolower( $ls_table_name ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ls_slider = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}layerslider WHERE id = %d ORDER BY date_c DESC LIMIT 1", (int) $id ), ARRAY_A );
	$ls_slider = json_decode( $ls_slider['data'], true );
	?>
	<style type="text/css">
		#layerslider-container{max-width:<?php echo esc_attr( $ls_slider['properties']['width'] ); ?>;}
	</style>
	<div id="layerslider-container">
		<div id="layerslider-wrapper">
			<?php if ( 'avada' === $ls_slider['properties']['skin'] ) : ?>
				<div class="ls-shadow-top"></div>
			<?php endif; ?>
			<?php echo do_shortcode( '[layerslider id="' . $id . '"]' ); ?>
			<?php if ( 'avada' === $ls_slider['properties']['skin'] ) : ?>
				<div class="ls-shadow-bottom"></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
