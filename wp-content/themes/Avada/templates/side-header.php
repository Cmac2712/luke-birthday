<?php
/**
 * Side-header template.
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
<?php

// The avada_before_header_wrapper hook.
do_action( 'avada_before_header_wrapper' );

$sticky_header_logo = ( Avada()->settings->get( 'sticky_header_logo' ) ) ? true : false;
$mobile_logo        = ( Avada()->settings->get( 'mobile_logo' ) ) ? true : false;

$side_header_css = 'clearfix fusion-mobile-menu-design-' . strtolower( Avada()->settings->get( 'mobile_menu_design' ) ) . ' fusion-sticky-logo-' . $sticky_header_logo . ' fusion-mobile-logo-' . $mobile_logo . ' fusion-sticky-menu-' . has_nav_menu( 'sticky_navigation' ) . ( Avada()->settings->get( 'header_shadow' ) ? ' header-shadow' : '' );

if ( 'flyout' === Avada()->settings->get( 'mobile_menu_design' ) ) {
	$side_header_css .= ' fusion-header-has-flyout-menu';
}
?>

<div id="side-header-sticky"></div>
<div id="side-header" class="<?php echo esc_attr( $side_header_css ); ?>">
	<div class="side-header-wrapper">
		<?php
		// The avada_header_inner_before hook.
		do_action( 'avada_header_inner_before' );
		?>
		<?php if ( 'flyout' === Avada()->settings->get( 'mobile_menu_design' ) ) : ?>
			<div class="fusion-header-has-flyout-menu-content">
		<?php endif; ?>
		<?php $mobile_logo = ( Avada()->settings->get( 'mobile_logo' ) ) ? true : false; ?>
		<div class="side-header-content fusion-logo-<?php echo esc_attr( strtolower( Avada()->settings->get( 'logo_alignment' ) ) ); ?> fusion-mobile-logo-<?php echo esc_attr( $mobile_logo ); ?>">
			<?php avada_logo(); ?>
		</div>
		<div class="fusion-main-menu-container fusion-logo-menu-<?php echo esc_attr( strtolower( Avada()->settings->get( 'logo_alignment' ) ) ); ?>">
			<?php avada_main_menu(); ?>
		</div>

		<?php avada_mobile_menu_search(); ?>

		<?php if ( 'leave_empty' !== Avada()->settings->get( 'header_left_content' ) || 'leave_empty' !== fusion_get_option( 'header_right_content' ) ) : ?>
			<?php $content_1 = avada_secondary_header_content( 'header_left_content' ); ?>
			<?php $content_2 = avada_secondary_header_content( 'header_right_content' ); ?>

			<div class="side-header-content side-header-content-1-2">
				<?php if ( $content_1 ) : ?>
					<div class="side-header-content-1 fusion-clearfix">
					<?php echo $content_1; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
				<?php if ( $content_2 ) : ?>
					<div class="side-header-content-2 fusion-clearfix">
					<?php echo $content_2; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( 'none' !== fusion_get_option( 'header_v4_content' ) ) : ?>
			<div class="side-header-content side-header-content-3">
				<?php avada_header_content_3(); ?>
			</div>
		<?php endif; ?>

		<?php
		// The avada_header_inner_after hook.
		do_action( 'avada_header_inner_after' );
		?>
		<?php if ( 'flyout' === Avada()->settings->get( 'mobile_menu_design' ) ) : ?>
			</div>
		<?php endif; ?>
	</div>
	<style>
	.side-header-styling-wrapper > div {
		display: none !important;
	}

	.side-header-styling-wrapper .side-header-background-image,
	.side-header-styling-wrapper .side-header-background-color,
	.side-header-styling-wrapper .side-header-border {
		display: block !important;
	}
	</style>
	<div class="side-header-styling-wrapper" style="overflow:hidden;">
		<div class="side-header-background-image"></div>
		<div class="side-header-background-color"></div>
		<div class="side-header-border"></div>
	</div>
</div>

<?php
// The avada_after_header_wrapper hook.
do_action( 'avada_after_header_wrapper' );
