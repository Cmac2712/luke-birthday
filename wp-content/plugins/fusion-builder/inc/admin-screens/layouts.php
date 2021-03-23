<?php
/**
 * Admin Screen markup (Layout Sections builder page).
 *
 * @package fusion-builder
 */

?>
<div class="wrap about-wrap fusion-builder-wrap">

	<?php Fusion_Builder_Admin::header(); ?>

	<?php
		$display_notification = '' === get_user_meta( get_current_user_id(), 'fusion-template-builder-layouts', true ) ? true : false;
		$wrapper_class        = true === $display_notification ? 'fusion-has-notification' : '';
	?>

	<div class="fusion-builder-important-notice-wrapper <?php echo esc_attr( $wrapper_class ); ?>">

		<div class="fusion-builder-important-notice fusion-builder-template-notification" data-dismissible="true" data-dismiss-type="user_meta" data-dismiss-option="fusion-template-builder-layouts" data-nonce="<?php echo esc_attr( wp_create_nonce( 'fusion_admin_notice' ) ); ?>">
			<button class="fusion-notice-dismiss"><i class="fusiona-times-solid"></i></button>
			<div class="intro-text">
				<p>
					<span class="fusion-notification-number">1</span>
					<?php
					printf(
						/* translators: %1$s: "layout sections". %2$s: "layout" */
						esc_html__( 'Use the %1$s to replace %2$s on every page of your site or you can create a new %3$s to replace them on specific pages based on the conditions you choose.', 'fusion-builder' ),
						'<strong>' . esc_html__( 'Global Layout', 'fusion-builder' ) . '</strong>',
						'<strong>' . esc_html__( 'Layout Sections', 'fusion-builder' ) . '</strong>',
						'<strong>' . esc_html__( 'Layout', 'fusion-builder' ) . '</strong>'
					);
					?>
				</p>

				<p>
					<span class="fusion-notification-number">2</span>
					<?php
					printf(
						/* translators: %1$s: "layout sections". */
						esc_html__( 'Create and assign custom %1$s to any layout by clicking on the part you wish to replace.', 'fusion-builder' ),
						'<strong>' . esc_html__( 'Layout Sections', 'fusion-builder' ) . '</strong>'
					);
					?>
				</p>

				<p>
					<span class="fusion-notification-number">3</span>
					<?php
					printf(
						/* translators: %1$s: "layout". */
						esc_html__( 'Choose which pages of your site will be affected by a %1$s by clicking on the cog icon to specify the conditions.', 'fusion-builder' ),
						'<strong>' . esc_html__( 'Layout', 'fusion-builder' ) . '</strong>'
					);
					?>
				</p>
			</div>
		</div>

		<div class="fusion-builder-important-notice fusion-template-builder">
			<div class="intro-text">
				<h3><?php esc_html_e( 'Theme Builder', 'fusion-builder' ); ?></h3>
				<p><?php esc_html_e( 'Create a new layout which you can then assign layout sections to and set layout conditions.', 'fusion-builder' ); ?> <?php
					printf(
						/* translators: %1$s: "layout". */
						esc_html__( 'For more information please see the %s.', 'fusion-builder' ),
						'<a href="https://theme-fusion.com/documentation/fusion-builder/theme-builder/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Theme Builder Documentation', 'fusion-builder' ) . '</a>'
					);
					?>
					</p>
			</div>
			<form id="fusion-create-layout-form">
				<input type="hidden" name="action" value="fusion_tb_new_layout">

				<div>
					<input type="text" placeholder="<?php esc_attr_e( 'Enter your layout name...', 'fusion-builder' ); ?>" required id="fusion-tb-layout-name" name="name" />
				</div>

				<?php wp_nonce_field( 'fusion_tb_new_layout' ); ?>

				<div>
					<input type="submit" value="<?php esc_attr_e( 'Create New Layout', 'fusion-builder' ); ?>" class="button button-large button-primary avada-large-button" />
				</div>
			</form>

			<button class="fusion-tutorial-link fusiona-question-circle-solid fusion-builder-tooltip" target="_blank">
				<span class="fusion-tooltip-text"><?php esc_html_e( 'Show Tutorial', 'fusion-builder' ); ?></span>
			</button>
		</div>
	</div>

	<div class="fusion-layout-nav-wrapper">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=fusion-layouts' ) ); ?>" class="active">
		<?php esc_html_e( 'Layouts', 'fusion-builder' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=fusion-layout-sections' ) ); ?>">
		<?php esc_html_e( 'Layout Sections', 'fusion-builder' ); ?>
	</a>
	</div>

	<div class="fusion-layouts">
		<script>
			fusionLayouts = <?php echo wp_json_encode( Fusion_Template_Builder()::get_registered_layouts(), JSON_FORCE_OBJECT ); ?>;
			fusionTemplates = <?php echo wp_json_encode( Fusion_Template_Builder()->get_templates_by_term(), JSON_FORCE_OBJECT ); ?>;
		</script>
	</div>
	<?php Fusion_Builder_Admin::footer(); ?>
</div>
