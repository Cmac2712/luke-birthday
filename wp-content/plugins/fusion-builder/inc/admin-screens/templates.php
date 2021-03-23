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
				<h3><?php esc_html_e( 'Theme Builder Layout Sections', 'fusion-builder' ); ?></h3>
				<p><?php esc_html_e( 'Create layout sections to customize the different sections of your site.', 'fusion-builder' ); ?> <?php
					printf(
						/* translators: %1$s: "layout". */
						esc_html__( 'For more information please see the %s.', 'fusion-builder' ),
						'<a href="https://theme-fusion.com/documentation/fusion-builder/theme-builder/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Theme Builder Documentation', 'fusion-builder' ) . '</a>'
					);
					?>
					</p>
			</div>
			<form id="fusion-create-template-form">
				<input type="hidden" name="action" value="fusion_tb_new_post">
				<div class="fusion-form-fields-wrap">
					<div>
						<select id="fusion-tb-category" name="fusion_tb_category" required>
							<option value="" disabled selected><?php esc_html_e( 'Layout Section Type', 'fusion-builder' ); ?></option>
						<?php
							$types = Fusion_Template_Builder()->get_template_terms();
						?>
						<?php foreach ( $types as $type_name => $type ) : ?>
							<option value="<?php echo esc_attr( $type_name ); ?>"><?php echo esc_html( $type['label'] ); ?></option>
						<?php endforeach; ?>

						<?php wp_nonce_field( 'fusion_tb_new_post' ); ?>
						</select>
					</div>

					<div>
						<input type="text" placeholder="<?php echo esc_attr_e( 'Enter Section Name', 'fusion-builder' ); ?>" required id="fusion-tb-name" name="name" />
					</div>
				</div>

				<div>
					<input type="submit" value="<?php esc_attr_e( 'Create New Layout Section', 'fusion-builder' ); ?>" class="button button-large button-primary avada-large-button" />
				</div>
			</form>

			<button class="fusion-tutorial-link fusiona-question-circle-solid fusion-builder-tooltip" target="_blank">
				<span class="fusion-tooltip-text"><?php esc_html_e( 'Show Tutorial', 'fusion-builder' ); ?></span>
			</button>
		</div>

	</div>

	<div class="fusion-layout-nav-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=fusion-layouts' ) ); ?>">
			<?php esc_html_e( 'Layouts', 'fusion-builder' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=fusion-templates' ) ); ?>" class="active">
			<?php esc_html_e( 'Layout Sections', 'fusion-builder' ); ?>
		</a>
	</div>

	<div class="fusion-template-builder-data-items">
		<?php
			$fusion_template_builder_table = new Fusion_Template_Builder_Table();
			$fusion_template_builder_table->get_status_links();
		?>
		<form id="fusion-template-builder-data" method="get">
			<?php
			$fusion_template_builder_table->prepare_items();
			$fusion_template_builder_table->display();
			?>
		</form>
	</div>

	<?php Fusion_Builder_Admin::footer(); ?>
</div>
