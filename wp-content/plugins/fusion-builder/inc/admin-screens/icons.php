<?php
/**
 * Admin Screen markup (Ligrary page).
 *
 * @package fusion-builder
 */

?>
<div class="wrap about-wrap fusion-builder-wrap">

	<?php Fusion_Builder_Admin::header(); ?>

	<div class="fusion-builder-important-notice fusion-template-builder">
		<div class="intro-text">
			<h3><?php esc_html_e( 'Custom Icons', 'fusion-builder' ); ?></h3>
			<p>
			<?php
			printf(
				esc_html__( 'Add a name for your Custom Icon Set. You will be redirected to the Edit Icon Set Page, where you can upload your custom Icomoon icon set.', 'fusion-builder' )
			);
			?>
			<?php
				printf(
					/* translators: %1$s: "layout". */
					esc_html__( 'For more information please see the %s.', 'fusion-builder' ),
					'<a href="https://theme-fusion.com/documentation/fusion-builder/settings-tools/how-to-upload-and-use-custom-icons-in-avada/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Custom Icons Documentation', 'fusion-builder' ) . '</a>'
				);
				?>
			</p>
		</div>
		<form>
			<input type="hidden" name="action" value="fusion_custom_icons_new">
			<?php wp_nonce_field( 'fusion_new_custom_icon_set' ); ?>

			<div>
				<input type="text" placeholder="<?php esc_attr_e( 'Enter Icon Set Name', 'fusion-builder' ); ?>" required id="fusion-icon-set-name" name="name" />
			</div>

			<div>
				<input type="submit" value="<?php esc_attr_e( 'Create New Icon Set', 'fusion-builder' ); ?>" class="button button-large button-primary avada-large-button" />
			</div>
		</form>
	</div>

	<div class="fusion-library-data-items">
		<?php
			$fusion_icons_table = new Fusion_Custom_Icons_Table();
			$fusion_icons_table->get_status_links();
		?>
		<form id="fusion-library-data" method="get">
			<?php
			$fusion_icons_table->prepare_items();
			$fusion_icons_table->display();
			?>
		</form>
	</div>

	<?php Fusion_Builder_Admin::footer(); ?>
</div>
