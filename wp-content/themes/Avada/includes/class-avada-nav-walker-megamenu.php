<?php
/**
 * Megamenu walker.
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

// Don't duplicate me!
if ( ! class_exists( 'Avada_Nav_Walker_Megamenu' ) ) {

	/**
	 * The megamenu navwalker class.
	 */
	class Avada_Nav_Walker_Megamenu extends Walker_Nav_Menu {

		/**
		 * Starts the list before the elements are added.
		 *
		 * @see Walker_Nav_Menu::start_lvl()
		 *
		 * @since 3.0.0
		 *
		 * @param string $output Passed by reference.
		 * @param int    $depth  Depth of menu item. Used for padding.
		 * @param array  $args   Not used.
		 */
		public function start_lvl( &$output, $depth = 0, $args = [] ) {}

		/**
		 * Ends the list of after the elements are added.
		 *
		 * @see Walker_Nav_Menu::end_lvl()
		 *
		 * @since 3.0.0
		 *
		 * @param string $output Passed by reference.
		 * @param int    $depth  Depth of menu item. Used for padding.
		 * @param array  $args   Not used.
		 */
		public function end_lvl( &$output, $depth = 0, $args = [] ) {}

		/**
		 * Start the element output.
		 *
		 * @see Walker_Nav_Menu::start_el()
		 * @since 3.0.0
		 *
		 * @param string $output Passed by reference. Used to append additional content.
		 * @param object $item   Menu item data object.
		 * @param int    $depth  Depth of menu item. Used for padding.
		 * @param array  $args   Not used.
		 * @param int    $id     Not used.
		 */
		public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 ) {
			global $_wp_nav_menu_max_depth, $wp_registered_sidebars;

			$_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

			$depth = (int) $depth;

			ob_start();
			$item_id      = esc_attr( $item->ID );
			$removed_args = [
				'action',
				'customlink-tab',
				'edit-menu-item',
				'menu-item',
				'page-tab',
				'_wpnonce',
			];

			$original_title = '';
			if ( 'taxonomy' === $item->type ) {
				$original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
				if ( is_wp_error( $original_title ) ) {
					$original_title = false;
				}
			} elseif ( 'post_type' === $item->type ) {
				$original_object = get_post( $item->object_id );
				$original_title  = get_the_title( $original_object->ID );
			}

			$classes = [
				'menu-item menu-item-depth-' . $depth,
				'menu-item-' . esc_attr( $item->object ),
				'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && sanitize_text_field( wp_unslash( $_GET['edit-menu-item'] ) ) === $item_id ) ? 'active' : 'inactive' ), // phpcs:ignore WordPress.Security.NonceVerification
			];

			$title = $item->title;

			if ( ! empty( $item->_invalid ) ) {
				$classes[] = 'menu-item-invalid';
				/* translators: %s: title of menu item which is invalid */
				$title = sprintf( esc_html__( '%s (Invalid)', 'Avada' ), $item->title );
			} elseif ( isset( $item->post_status ) && 'draft' === $item->post_status ) {
				$classes[] = 'pending';
				/* translators: %s: title of menu item in draft status */
				$title = sprintf( esc_html__( '%s (Pending)', 'Avada' ), $item->title );
			}

			$title = ( ! isset( $item->label ) || '' === $item->label ) ? $title : $item->label;

			$submenu_text = '';
			if ( 0 === $depth ) {
				$submenu_text = 'style="display:none;"';
			}

			?>
			<li id="menu-item-<?php echo esc_attr( $item_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<dl class="menu-item-bar">
					<dt class="menu-item-handle">
						<span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <span class="is-submenu" <?php echo $submenu_text; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php esc_attr_e( 'sub item', 'Avada' ); ?></span></span>
						<span class="item-controls">
							<span class="item-type"><?php echo esc_html( $item->type_label ); ?></span>
							<span class="item-order hide-if-js">
								<?php
								$url1 = wp_nonce_url(
									add_query_arg(
										[
											'action'    => 'move-up-menu-item',
											'menu-item' => $item_id,
										],
										remove_query_arg( $removed_args, admin_url( 'nav-menus.php' ) )
									),
									'move-menu_item'
								);
								$url2 = wp_nonce_url(
									add_query_arg(
										[
											'action'    => 'move-down-menu-item',
											'menu-item' => $item_id,
										],
										remove_query_arg( $removed_args, admin_url( 'nav-menus.php' ) )
									),
									'move-menu_item'
								);
								if ( isset( $_GET['edit-menu-item'] ) && sanitize_text_field( wp_unslash( $_GET['edit-menu-item'] ) ) === $item_id ) { // phpcs:ignore WordPress.Security.NonceVerification
									$url3 = admin_url( 'nav-menus.php' );
								} else {
									$url3 = add_query_arg(
										'edit-menu-item',
										$item_id,
										remove_query_arg(
											$removed_args,
											admin_url( 'nav-menus.php#menu-item-settings-' . $item_id )
										)
									);
								}
								?>
								<a href="<?php echo esc_url( $url1 ); ?>" class="item-move-up"><abbr title="<?php esc_attr_e( 'Move up', 'Avada' ); ?>">&#8593;</abbr></a>
								|
								<a href="<?php echo esc_url( $url2 ); ?>" class="item-move-down"><abbr title="<?php esc_attr_e( 'Move down', 'Avada' ); ?>">&#8595;</abbr></a>
							</span>
							<a class="item-edit" id="edit-<?php echo esc_attr( $item_id ); ?>" title="<?php esc_attr_e( 'Edit Menu Item', 'Avada' ); ?>" href="<?php echo esc_url( $url3 ); ?>"><?php esc_attr_e( 'Edit Menu Item', 'Avada' ); ?></a>
						</span>
					</dt>
				</dl>

				<div class="menu-item-settings" id="menu-item-settings-<?php echo esc_attr( $item_id ); ?>">
					<?php if ( 'custom' === $item->type ) : ?>
						<p class="field-url description description-wide">
							<label for="edit-menu-item-url-<?php echo esc_attr( $item_id ); ?>">
								<?php esc_attr_e( 'URL', 'Avada' ); ?><br />
								<input type="text" id="edit-menu-item-url-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
							</label>
						</p>
					<?php endif; ?>
					<p class="description description-thin">
						<label for="edit-menu-item-title-<?php echo esc_attr( $item_id ); ?>">
							<?php esc_attr_e( 'Navigation Label', 'Avada' ); ?><br />
							<input type="text" id="edit-menu-item-title-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
						</label>
					</p>
					<p class="description description-thin">
						<label for="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>">
							<?php esc_attr_e( 'Title Attribute', 'Avada' ); ?><br />
							<input type="text" id="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
						</label>
					</p>
					<p class="field-link-target description">
						<label for="edit-menu-item-target-<?php echo esc_attr( $item_id ); ?>">
							<input type="checkbox" id="edit-menu-item-target-<?php echo esc_attr( $item_id ); ?>" value="_blank" name="menu-item-target[<?php echo esc_attr( $item_id ); ?>]"<?php checked( $item->target, '_blank' ); ?> />
							<?php esc_attr_e( 'Open link in a new window/tab', 'Avada' ); ?>
						</label>
					</p>
					<p class="field-css-classes description description-thin">
						<label for="edit-menu-item-classes-<?php echo esc_attr( $item_id ); ?>">
							<?php esc_attr_e( 'CSS Classes (optional)', 'Avada' ); ?><br />
							<input type="text" id="edit-menu-item-classes-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( implode( ' ', $item->classes ) ); ?>" />
						</label>
					</p>
					<p class="field-xfn description description-thin">
						<label for="edit-menu-item-xfn-<?php echo esc_attr( $item_id ); ?>">
							<?php esc_attr_e( 'Link Relationship (XFN)', 'Avada' ); ?><br />
							<input type="text" id="edit-menu-item-xfn-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
						</label>
					</p>
					<p class="field-description description description-wide">
						<label for="edit-menu-item-description-<?php echo esc_attr( $item_id ); ?>">
							<?php esc_attr_e( 'Description', 'Avada' ); ?><br />
							<textarea id="edit-menu-item-description-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo esc_attr( $item_id ); ?>]"><?php echo esc_html( $item->description ); ?></textarea>
							<span class="description"><?php esc_attr_e( 'The description will be displayed in the menu if the current theme supports it.', 'Avada' ); ?></span>
						</label>
					</p>

					<?php do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args ); ?>

					<fieldset class="field-move hide-if-no-js description description-wide">
						<span class="field-move-visual-label" aria-hidden="true"><?php esc_attr_e( 'Move', 'Avada' ); ?></span>
						<button type="button" class="button-link menus-move menus-move-up" data-dir="up"><?php esc_attr_e( 'Up one', 'Avada' ); ?></button>
						<button type="button" class="button-link menus-move menus-move-down" data-dir="down"><?php esc_attr_e( 'Down one', 'Avada' ); ?></button>
						<button type="button" class="button-link menus-move menus-move-left" data-dir="left"></button>
						<button type="button" class="button-link menus-move menus-move-right" data-dir="right"></button>
						<button type="button" class="button-link menus-move menus-move-top" data-dir="top"><?php esc_attr_e( 'To the top', 'Avada' ); ?></button>
					</fieldset>

					<div class="menu-item-actions description-wide submitbox">
						<?php if ( 'custom' !== $item->type && false !== $original_title ) : ?>
							<p class="link-to-original">
								<?php /* translators: Title (with link). */ ?>
								<?php printf( esc_html__( 'Original: %s', 'Avada' ), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?>
							</p>
						<?php endif; ?>
						<?php
						$url = wp_nonce_url(
							add_query_arg(
								[
									'action'    => 'delete-menu-item',
									'menu-item' => $item_id,
								],
								admin_url( 'nav-menus.php' )
							),
							'delete-menu_item_' . $item_id
						);
						?>
						<a class="item-delete submitdelete deletion" id="delete-<?php echo esc_attr( $item_id ); ?>" href="<?php echo esc_url( $url ); ?>"><?php esc_attr_e( 'Remove', 'Avada' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span>
						<?php
						$url_with_args = add_query_arg(
							[
								'edit-menu-item' => $item_id,
								'cancel'         => time(),
							],
							admin_url( 'nav-menus.php' )
						);
						?>
						<a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo esc_attr( $item_id ); ?>" href="<?php echo esc_url( $url_with_args ); ?>#menu-item-settings-<?php echo esc_attr( $item_id ); ?>"><?php esc_attr_e( 'Cancel', 'Avada' ); ?></a>
					</div>

					<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item_id ); ?>" />
					<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
					<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
					<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
					<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
					<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->type ); ?>" />
				</div><!-- .menu-item-settings-->
				<ul class="menu-item-transport"></ul>
			<?php
			$output .= ob_get_clean();
		}
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
