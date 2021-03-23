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
			<h3><?php esc_html_e( 'Fusion Builder Library', 'fusion-builder' ); ?></h3>
			<p>
			<?php
			printf(
				/* translators: "Fusion Builder" link. */
				esc_html__( 'The Fusion Builder Library contains your saved Page Templates, Containers, Columns and Elements. Here, you can create and manage your library content. For more information please see the %s.', 'fusion-builder' ),
				'<a href="https://theme-fusion.com/documentation/fusion-builder/fusion-builder-library/" target="_blank">' . esc_attr__( 'Fusion Builder Library Documentation', 'fusion-builder' ) . '</a>'
			);
			?>
			</p>
		</div>
		<form>
			<input type="hidden" name="action" value="fusion_library_new">
			<div>
				<label for="fusion-library-type"><?php esc_html_e( 'Select the library element type', 'fusion-builder' ); ?></label>
				<select id="fusion-library-type" name="fusion_library_type" >
				<?php
					$types = [
						'templates' => esc_html__( 'Template', 'fusion-builder' ),
						'sections'  => esc_html__( 'Container', 'fusion-builder' ),
						'columns'   => esc_html__( 'Column', 'fusion-builder' ),
						'elements'  => esc_html__( 'Element', 'fusion-builder' ),
					];
					?>
				<?php foreach ( $types as $type_name => $type_label ) : ?>
					<option value="<?php echo esc_attr( $type_name ); ?>"><?php echo esc_html( $type_label ); ?></option>
				<?php endforeach; ?>

				</select>
				<?php wp_nonce_field( 'fusion_library_new_element' ); ?>
			</div>

			<div>
				<label for="fusion-library-name"><?php esc_html_e( 'Element name', 'fusion-builder' ); ?></label>
				<input type="text" placeholder="<?php esc_attr_e( 'Enter Element Name', 'fusion-builder' ); ?>" required id="fusion-library-name" name="name" />
			</div>

			<div id="fusion-global-field">
				<label for="fusion-library-global"><?php esc_html_e( 'Global element', 'fusion-builder' ); ?></label>
				<input type="checkbox" id="fusion-library-global" name="global" />
			</div>

			<div>
				<input type="submit" value="<?php esc_attr_e( 'Create Library Element', 'fusion-builder' ); ?>" class="button button-large button-primary avada-large-button" />
			</div>
		</form>
	</div>

	<div class="fusion-library-data-items">
		<?php
			$fusion_library_table = new Fusion_Builder_Library_Table();
			$fusion_library_table->get_status_links();
		?>
		<form id="fusion-library-data" method="get">
			<?php
			$fusion_library_table->prepare_items();
			$fusion_library_table->display();
			?>
		</form>
	</div>

	<?php Fusion_Builder_Admin::footer(); ?>
</div>
