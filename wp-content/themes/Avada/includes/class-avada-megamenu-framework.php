<?php
/**
 * Fusion MegaMenu Functions
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Don't duplicate me!
if ( ! class_exists( 'Avada_Megamenu_Framework' ) ) {

	/**
	 * Main Avada_Megamenu_Framework Class
	 */
	class Avada_Megamenu_Framework {

		/**
		 * The theme info object.
		 *
		 * @static
		 * @access public
		 * @var object
		 */
		public static $theme_info;

		/**
		 * Array of objects.
		 *
		 * @static
		 * @access public
		 * @var mixed
		 */
		public static $classes;

		/**
		 * Constructor.
		 *
		 * @access public
		 */
		public function __construct() {

			self::$theme_info = wp_get_theme();

			add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'register_stylesheets' ] );

			do_action( 'fusion_init' );

			self::$classes['menus'] = new Avada_Megamenu();

			// Add the first level menu style dropdown to the menu fields.
			add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'add_menu_button_fields' ], 10, 4 );

			// Add the mega menu custom fields to the menu fields.
			if ( Avada()->settings->get( 'disable_megamenu' ) ) {
				add_filter( 'avada_menu_options', [ $this, 'add_megamenu_fields' ], 20, 4 );
			}

			// Add the menu arrow highlights.
			add_filter( 'avada_menu_arrow_hightlight', [ $this, 'add_menu_arrow_highlight' ], 10, 2 );
		}

		/**
		 * Register megamenu javascript assets.
		 *
		 * @since  3.4
		 * @access public
		 * @param string $hook The hook we're currently on.
		 * @return void
		 */
		public function register_scripts( $hook ) {
			if ( 'nav-menus.php' === $hook ) {

				// Scripts.
				wp_enqueue_media();
				wp_register_script( 'avada-megamenu', Avada::$template_dir_url . '/assets/admin/js/mega-menu.js', [], self::$theme_info->get( 'Version' ), false );
				wp_enqueue_script( 'avada-megamenu' );
			}
		}

		/**
		 * Enqueue megamenu stylesheets
		 *
		 * @since  3.4
		 * @access public
		 * @param string $hook The hook we're currently on.
		 * @return void
		 */
		public function register_stylesheets( $hook ) {
			if ( 'nav-menus.php' === $hook ) {
				wp_enqueue_style( 'avada-megamenu', Avada::$template_dir_url . '/assets/css/mega-menu.css', false, self::$theme_info->get( 'Version' ) );
			}
		}

		/**
		 * Adds the menu button fields.
		 *
		 * @access public
		 * @since 6.0.0
		 * @return array.
		 */
		public static function menu_options_map() {
			return apply_filters(
				'avada_menu_options',
				[
					'megamenu-style'                      => [
						'id'          => 'megamenu-style',
						'label'       => esc_attr__( 'Menu First Level Style', 'Avada' ),
						'choices'     => [
							''                     => esc_attr__( 'Default Style', 'Avada' ),
							'fusion-button-small'  => esc_attr__( 'Button Small', 'Avada' ),
							'fusion-button-medium' => esc_attr__( 'Button Medium', 'Avada' ),
							'fusion-button-large'  => esc_attr__( 'Button Large', 'Avada' ),
							'fusion-button-xlarge' => esc_attr__( 'Button xLarge', 'Avada' ),
						],
						'description' => esc_attr__( 'Select to use normal text (default) for the parent level menu item, or a button. Button styles are controlled in Theme Options > Fusion Builder Elements.', 'Avada' ),
						'type'        => 'select',
						'default'     => '',
						'save_id'     => 'fusion_menu_style',
					],
					'megamenu-icon'                       => [
						'id'          => 'megamenu-icon',
						'label'       => esc_attr__( 'Icon Select', 'Avada' ),
						'description' => esc_attr__( 'Select an icon for your menu item. For top-level menu items, icon styles can be controlled in Theme Options > Menu > Main Menu Icons.', 'Avada' ),
						'type'        => 'iconpicker',
						'default'     => '',
					],
					'megamenu-icononly'                   => [
						'id'          => 'megamenu-icononly',
						'label'       => esc_attr__( 'Icon/Thumbnail Only', 'Avada' ),
						'description' => esc_attr__( 'Turn on to only show the icon/image thumbnail while hiding the menu text. Important: this does not apply to the mobile menu.', 'Avada' ),
						'type'        => 'radio-buttonset',
						'default'     => 'off',
						'choices'     => [
							'icononly' => esc_attr__( 'On', 'Avada' ),
							'off'      => esc_attr__( 'Off', 'Avada' ),
						],
						'save_id'     => 'fusion_menu_icononly',
					],
					'megamenu-highlight-label'            => [
						'id'          => 'megamenu-highlight-label',
						'label'       => esc_attr__( 'Menu Highlight Label', 'Avada' ),
						'description' => esc_attr__( 'Set the highlight label for menu item.', 'Avada' ),
						'type'        => 'text',
						'save_id'     => 'fusion_highlight_label',
					],
					'megamenu-highlight-label-background' => [
						'id'          => 'megamenu-highlight-label-background',
						'label'       => esc_attr__( 'Menu Highlight Label Background Color', 'Avada' ),
						/* translators: "Theme Options" link. */
						'description' => sprintf( esc_attr__( 'Set the highlight label background color. To set a border radius, visit %s and modify the Menu Highlight Label Radius option.', 'Avada' ), '<a href="' . esc_url_raw( admin_url( 'themes.php?page=avada_options#main_nav_highlight_radius' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_attr__( 'Theme Options', 'Avada' ) . '</a>' ),
						'type'        => 'color-alpha',
						'save_id'     => 'fusion_highlight_label_background',
						'dependency'  => [
							[
								'field'      => 'megamenu-highlight-label',
								'value'      => '',
								'comparison' => '!=',
							],
						],
					],
					'megamenu-highlight-label-color'      => [
						'id'          => 'megamenu-highlight-label-color',
						'label'       => esc_attr__( 'Menu Highlight Label Text Color', 'Avada' ),
						'description' => esc_attr__( 'Set the highlight label text color.', 'Avada' ),
						'type'        => 'color',
						'save_id'     => 'fusion_highlight_label_color',
						'dependency'  => [
							[
								'field'      => 'megamenu-highlight-label',
								'value'      => '',
								'comparison' => '!=',
							],
						],
					],
					'megamenu-highlight-label-border-color' => [
						'id'          => 'megamenu-highlight-label-border-color',
						'label'       => esc_attr__( 'Menu Highlight Label Border Color', 'Avada' ),
						'description' => esc_attr__( 'Set the highlight label border color.', 'Avada' ),
						'type'        => 'color',
						'save_id'     => 'fusion_highlight_label_border_color',
						'dependency'  => [
							[
								'field'      => 'megamenu-highlight-label',
								'value'      => '',
								'comparison' => '!=',
							],
						],
					],
					'megamenu-modal'                      => [
						'id'          => 'megamenu-modal',
						'label'       => esc_attr__( 'Modal Window Anchor', 'Avada' ),
						'description' => esc_attr__( 'Add the class name of the modal window you want to open on menu item click.', 'Avada' ),
						'type'        => 'text',
					],
				]
			);
		}

		/**
		 * Adds the megamenu fields.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param array $fields Current fields map.
		 * @return array.
		 */
		public function add_megamenu_fields( $fields ) {
			global $wp_registered_sidebars;
			$sidebars = [
				'0' => esc_attr__( 'Select Widget Area', 'Avada' ),
			];
			if ( ! empty( $wp_registered_sidebars ) && is_array( $wp_registered_sidebars ) ) {
				foreach ( $wp_registered_sidebars as $sidebar ) {
								$sidebars[ esc_attr( $sidebar['id'] ) ] = esc_attr( $sidebar['name'] );
				}
			}

			$mega_fields = [
				'megamenu-status'           => [
					'id'          => 'megamenu-status',
					'label'       => esc_html__( 'Fusion Mega Menu', 'Avada' ),
					'choices'     => [
						'enabled' => esc_attr__( 'On', 'Avada' ),
						'off'     => esc_attr__( 'Off', 'Avada' ),
					],
					'description' => esc_html__( 'Turn on to enable the mega menu.  Note this will only work for the main menu.', 'Avada' ),
					'type'        => 'radio-buttonset',
					'default'     => 'off',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '1',
							'comparison' => '==',
						],
					],
				],
				'megamenu-background-image' => [
					'id'          => 'megamenu-background-image',
					'label'       => esc_html__( 'Mega Menu / Flyout Menu Background Image', 'Avada' ),
					'description' => __( 'Select an image for the mega menu or flyout menu background.<br /><strong>Mega Menu:</strong> In case of mega menu, if left empty, the Main Menu Dropdown Background Color will be used. Each mega menu column can have its own background image, or you can have one image that spreads across the entire mega menu width.<br /><strong>Flyout Menu:</strong> When used in the flyout menu, the image will be shown full screen when hovering the corresponding menu item.', 'Avada' ),
					'type'        => 'media',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '3',
							'comparison' => '<',
						],
						[
							'field'      => 'parent_megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
				'megamenu-thumbnail'        => [
					'id'          => 'megamenu-thumbnail',
					'label'       => esc_html__( 'Mega Menu Thumbnail', 'Avada' ),
					'description' => esc_html__( 'Select an image to use as a thumbnail for the menu item. For top-level items, the size of the thumbnail can be controlled in Theme Options > Menu > Main Menu Icons.', 'Avada' ),
					'type'        => 'media',
					'dependency'  => [
						[
							'field'      => 'parent_megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
				'megamenu-width'            => [
					'id'          => 'megamenu-width',
					'label'       => esc_attr__( 'Full Width Mega Menu', 'Avada' ),
					'choices'     => [
						'fullwidth' => esc_attr__( 'On', 'Avada' ),
						'off'       => esc_attr__( 'Off', 'Avada' ),
					],
					'description' => esc_attr__( 'Turn on to have the mega menu full width, which is taken from the site width option in Theme Options. Note this overrides the column width option.', 'Avada' ),
					'type'        => 'radio-buttonset',
					'default'     => 'off',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '1',
							'comparison' => '==',
						],
						[
							'field'      => 'megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
				'megamenu-columns'          => [
					'id'          => 'megamenu-columns',
					'label'       => esc_attr__( 'Mega Menu Number of Columns', 'Avada' ),
					'choices'     => [
						'auto' => esc_attr__( 'Auto', 'Avada' ),
						'1'    => '1',
						'2'    => '2',
						'3'    => '3',
						'4'    => '4',
						'5'    => '5',
						'6'    => '6',
					],
					'description' => esc_attr__( 'Select the number of columns you want to use.', 'Avada' ),
					'type'        => 'select',
					'default'     => 'auto',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '1',
							'comparison' => '==',
						],
						[
							'field'      => 'megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
				'megamenu-columnwidth'      => [
					'id'          => 'megamenu-columnwidth',
					'label'       => esc_attr__( 'Mega Menu Column Width', 'Avada' ),
					'description' => esc_attr__( 'Set the width of the column. In percentage, ex 60%.', 'Avada' ),
					'type'        => 'text',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '2',
							'comparison' => '==',
						],
						[
							'field'      => 'parent_megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
						[
							'field'      => 'parent_megamenu-width',
							'value'      => 'fullwidth',
							'comparison' => '!=',
						],
					],
				],
				'megamenu-title'            => [
					'id'          => 'megamenu-title',
					'label'       => esc_attr__( 'Mega Menu Column Title', 'Avada' ),
					'choices'     => [
						'enabled'  => esc_attr__( 'On', 'Avada' ),
						'disabled' => esc_attr__( 'Off', 'Avada' ),
					],
					'description' => esc_attr__( 'Turn on to display item as linked column title. Turn off to display item as normal mega menu entry.', 'Avada' ),
					'type'        => 'radio-buttonset',
					'default'     => 'enabled',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '2',
							'comparison' => '==',
						],
						[
							'field'      => 'parent_megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
				'megamenu-widgetarea'       => [
					'id'          => 'megamenu-widgetarea',
					'label'       => esc_attr__( 'Mega Menu Widget Area', 'Avada' ),
					'choices'     => $sidebars,
					'description' => esc_attr__( 'Select a widget area to be used as the content for the column.', 'Avada' ),
					'type'        => 'select',
					'default'     => '0',
					'dependency'  => [
						[
							'field'      => 'depth',
							'value'      => '1',
							'comparison' => '>',
						],
						[
							'field'      => 'depth',
							'value'      => '4',
							'comparison' => '<',
						],
						[
							'field'      => 'parent_megamenu-status',
							'value'      => 'enabled',
							'comparison' => '==',
						],
					],
				],
			];
			return array_merge( $fields, $mega_fields );
		}

		/**
		 * Adds the markup for the options.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string $item_id The ID of the menu item.
		 * @param object $item    The menu item object.
		 * @return void.
		 */
		public function parse_options( $item_id, $item ) {
			$fields = $this::menu_options_map();
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {

					// Defaults.
					$field['id']          = isset( $field['id'] ) ? $field['id'] : '';
					$field['label']       = isset( $field['label'] ) ? $field['label'] : '';
					$field['choices']     = isset( $field['choices'] ) ? $field['choices'] : [];
					$field['description'] = isset( $field['description'] ) ? $field['description'] : '';
					$field['default']     = isset( $field['default'] ) ? $field['default'] : '';
					$field['dependency']  = isset( $field['dependency'] ) ? $field['dependency'] : [];
					$field['save_id']     = isset( $field['save_id'] ) ? $field['save_id'] : 'fusion_' . str_replace( '-', '_', $field['id'] );

					if ( isset( $field['type'] ) ) {
						switch ( $field['type'] ) {

							case 'text':
								$this->text( $field['id'], $field['label'], $field['description'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'radio-buttonset':
								$this->radio_buttonset( $field['id'], $field['label'], $field['choices'], $field['description'], $field['default'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'select':
								$this->select( $field['id'], $field['label'], $field['choices'], $field['description'], $field['default'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'color-alpha':
								$this->color_alpha( $field['id'], $field['label'], $field['description'], $field['default'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'color':
								$this->color( $field['id'], $field['label'], $field['description'], $field['default'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'media':
								$this->media( $field['id'], $field['label'], $field['description'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
							case 'iconpicker':
								$this->iconpicker( $field['id'], $field['label'], $field['description'], $field['dependency'], $item_id, $item, $field['save_id'] );
								break;
						}
					}
				}
			}
		}

		/**
		 * Text controls.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string $id         The ID.
		 * @param string $label      The label.
		 * @param string $desc       The description.
		 * @param array  $dependency The dependencies array.
		 * @param string $item_id    The ID of the menu item.
		 * @param object $item       The menu item object.
		 * @param string $save_id    The save ID if it is different from ID.
		 */
		public function text( $id, $label, $desc = '', $dependency = [], $item_id, $item, $save_id ) {
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container">
					<input type="text" id="edit-menu-item-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-<?php echo esc_attr( $id ); ?>" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->{ $save_id } ); ?>" />
				</div>
			</div>
			<?php
		}

		/**
		 * Radio button set field.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string           $id         ID of input field.
		 * @param string           $label      Label of field.
		 * @param array            $options    Options to select from.
		 * @param string           $desc       Description of field.
		 * @param string|int|float $default    The default value.
		 * @param array            $dependency The dependencies array.
		 * @param string           $item_id    The ID of the menu item.
		 * @param object           $item       The menu item object.
		 * @param string           $save_id    The save ID if it is different from ID.
		 */
		public function radio_buttonset( $id, $label, $options, $desc = '', $default = '', $dependency = [], $item_id, $item, $save_id ) {
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container">
					<div class="fusion-form-radio-button-set ui-buttonset edit-menu-item-<?php echo esc_attr( $id ); ?>">
						<input type="hidden" id="edit-menu-item-fusion-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->{ $save_id } ); ?>" class="button-set-value" />
						<?php foreach ( $options as $value => $label ) : ?>
							<?php $value_check = ( '' !== $item->{ $save_id } ) ? $item->{ $save_id } : $default; ?>
							<a href="#" class="ui-button buttonset-item<?php echo ( $value === $value_check ) ? ' ui-state-active' : ''; ?>" data-value="<?php echo esc_attr( $value ); ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Select field.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string           $id         ID of input field.
		 * @param string           $label      Label of field.
		 * @param array            $options    Options to select from.
		 * @param string           $desc       Description of field.
		 * @param string|int|float $default    The default value.
		 * @param array            $dependency The dependencies array.
		 * @param string           $item_id    The ID of the menu item.
		 * @param object           $item       The menu item object.
		 * @param string           $save_id    The save ID if it is different from ID.
		 */
		public function select( $id, $label, $options, $desc = '', $default = '', $dependency = [], $item_id, $item, $save_id ) {
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container">
					<select id="edit-menu-item-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-<?php echo esc_attr( $id ); ?>" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]">
						<?php foreach ( $options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $item->{ $save_id }, $value ); ?>><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php
		}

		/**
		 * Icon field.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string $id         ID of input field.
		 * @param string $label      Label of field.
		 * @param string $desc       Description of field.
		 * @param array  $dependency The dependencies array.
		 * @param string $item_id    The ID of the menu item.
		 * @param object $item       The menu item object.
		 * @param string $save_id    The save ID if it is different from ID.
		 */
		public function iconpicker( $id, $label, $desc = '', $dependency = [], $item_id, $item, $save_id ) {
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container fusion-iconpicker">
					<span class="add-custom-icons">
						<a href="<?php echo esc_url( admin_url( '/post-new.php?post_type=fusion_icons' ) ); ?>" target="_blank" class="fusiona-plus" title="<?php echo esc_attr_e( 'Add Custom Icon Set', 'fusion-builder' ); ?>"></a>
					</span>
					<input type="text" class="fusion-icon-search" placeholder="<?php esc_attr_e( 'Search Icons', 'Avada' ); ?>" />
					<span class="input-icon fusiona-search"></span>
					<div class="icon_select_container"></div>
					<input type="hidden" id="edit-menu-item-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="fusion-iconpicker-input" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->{ $save_id } ); ?>" />
				</div>
			</div>
			<?php
		}

		/**
		 * Color alpha field.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string           $id         ID of input field.
		 * @param string           $label      Label of field.
		 * @param string           $desc       Description of field.
		 * @param string|int|float $default    The default value.
		 * @param array            $dependency The dependencies array.
		 * @param string           $item_id    The ID of the menu item.
		 * @param object           $item       The menu item object.
		 * @param string           $save_id    The save ID if it is different from ID.
		 */
		public function color_alpha( $id, $label, $desc = '', $default = '', $dependency = [], $item_id, $item, $save_id ) {
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container pyre_field avada-color colorpickeralpha">
					<input type="text" id="edit-menu-item-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-<?php echo esc_attr( $id ); ?> fusion-builder-color-picker-hex color-picker" data-alpha="true" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->{ $save_id } ); ?>" />
				</div>
			</div>
			<?php
		}

		/**
		 * Color field.
		 * Alias of color-alpha.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string           $id         ID of input field.
		 * @param string           $label      Label of field.
		 * @param string           $desc       Description of field.
		 * @param string|int|float $default    The default value.
		 * @param array            $dependency The dependencies array.
		 * @param string           $item_id    The ID of the menu item.
		 * @param object           $item       The menu item object.
		 * @param string           $save_id    The save ID if it is different from ID.
		 */
		public function color( $id, $label, $desc = '', $default = '', $dependency = [], $item_id, $item, $save_id ) {
			$this->color_alpha( $id, $label, $desc, $default, $dependency, $item_id, $item, $save_id );
		}

		/**
		 * Media field.
		 *
		 * @access public
		 * @since 6.0.0
		 * @param string $id         ID of input field.
		 * @param string $label      Label of field.
		 * @param string $desc       Description of field.
		 * @param array  $dependency The dependencies array.
		 * @param string $item_id    The ID of the menu item.
		 * @param object $item       The menu item object.
		 * @param string $save_id    The save ID if it is different from ID.
		 */
		public function media( $id, $label, $desc = '', $dependency = [], $item_id, $item, $save_id ) {
			$media_id = str_replace( 'megamenu-', '', $id );
			?>
			<div class="fusion-builder-option field-<?php echo esc_attr( $id ); ?>">
				<div class="option-details">
					<h3><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<p class="description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>
				<div class="option-field fusion-builder-option-container">
					<div class="fusion-upload-image<?php echo ( isset( $item->{ $save_id } ) && '' !== $item->{ $save_id } ) ? ' fusion-image-set' : ''; ?>">
						<input type="hidden" id="edit-menu-item-<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="regular-text fusion-builder-upload-field" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->{ $save_id } ); ?>" />
						<?php
						$thumbnail_id = isset( $item->fusion_megamenu_thumbnail ) ? $item->fusion_megamenu_thumbnail_id : 0;
						if ( ! $thumbnail_id && isset( $item->fusion_megamenu_thumbnail ) && '' !== $item->fusion_megamenu_thumbnail ) {
							$thumbnail_id = Fusion_Images::get_attachment_id_from_url( $item->fusion_megamenu_thumbnail );
						}
						?>
						<input type="hidden" id="edit-menu-item-<?php echo esc_attr( $id ); ?>-id-<?php echo esc_attr( $item_id ); ?>" class="regular-text fusion-builder-upload-field" name="menu-item-fusion-<?php echo esc_attr( $id ); ?>-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
						<div class="fusion-builder-upload-preview">
							<img src="<?php echo esc_attr( $item->{ $save_id } ); ?>" id="fusion-media-img-<?php echo esc_attr( $media_id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="fusion-<?php echo esc_attr( $id ); ?>-image" style="<?php echo ( trim( $item->fusion_megamenu_thumbnail ) ) ? 'display:inline;' : ''; ?>" />
						</div>
						<input type='button' data-id="<?php echo esc_attr( $media_id ); ?>-<?php echo esc_attr( $item_id ); ?>" class='button-upload fusion-builder-upload-button avada-edit-button' data-type="image" value="<?php esc_attr_e( 'Edit', 'Avada' ); ?>" />
						<input type="button" data-id="<?php echo esc_attr( $media_id ); ?>-<?php echo esc_attr( $item_id ); ?>" class="upload-image-remove avada-remove-button" value="<?php esc_attr_e( 'Remove', 'Avada' ); ?>"  />
						<input type='button' data-id="<?php echo esc_attr( $media_id ); ?>-<?php echo esc_attr( $item_id ); ?>" class='button-upload fusion-builder-upload-button avada-upload-button' data-type="image" value="<?php esc_attr_e( 'Upload Image', 'Avada' ); ?>" />
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Adds the menu button fields.
		 *
		 * @access public
		 * @param string $item_id The ID of the menu item.
		 * @param object $item    The menu item object.
		 * @param int    $depth   The depth of the current item in the menu.
		 * @param array  $args    Menu arguments.
		 * @return void.
		 */
		public function add_menu_button_fields( $item_id, $item, $depth, $args ) {
			$name = 'menu-item-fusion-megamenu-style';
			?>
			<div class="fusion-menu-options-container">
				<a class="button button-primary button-large fusion-menu-option-trigger" href="#"><?php esc_attr_e( 'Avada Menu Options', 'Avada' ); ?></a>
				<div class="fusion_builder_modal_overlay" style="display:none"></div>
				<div id="fusion-menu-options-<?php echo esc_attr( $item_id ); ?>" class="fusion-options-holder fusion-builder-modal-settings-container" style="display:none">
					<div class="fusion-builder-modal-container fusion_builder_module_settings">
						<div class="fusion-builder-modal-top-container">
							<h2><?php esc_attr_e( 'Avada Menu Options', 'Avada' ); ?></h2>
							<div class="fusion-builder-modal-close fusiona-plus2"></div>
						</div>
						<div class="fusion-builder-modal-bottom-container">
							<a href="#" class="fusion-builder-modal-save" ><span><?php esc_attr_e( 'Save', 'Avada' ); ?></span></a>
							<a href="#" class="fusion-builder-modal-close" ><span><?php esc_attr_e( 'Cancel', 'Avada' ); ?></span></a>
						</div>
						<div class="fusion-builder-main-settings fusion-builder-main-settings-full">
							<div class="fusion-builder-module-settings">
								<?php $this->parse_options( $item_id, $item ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Adds the menu arrow light to main menu top level items.
		 *
		 * @since 5.3
		 * @access public
		 * @param string $title The menu item title markup.
		 * @param bool   $has_children Whether the menu item has children or not.
		 * @return string The extended title markup, including the menu arrow highlight.
		 */
		public function add_menu_arrow_highlight( $title, $has_children = false ) {
			$menu_highlight_style = Avada()->settings->get( 'menu_highlight_style' );
			$header_layout        = Avada()->settings->get( 'header_layout' );
			$svg                  = '';

			if ( 'arrow' === $menu_highlight_style && 'v6' !== $header_layout ) {
				$header_position = fusion_get_option( 'header_position' );
				$svg_height      = Fusion_Sanitize::size( Avada()->settings->get( 'menu_arrow_size', 'height' ) );
				$svg_height_int  = intval( $svg_height );
				$svg_width       = Fusion_Sanitize::size( Avada()->settings->get( 'menu_arrow_size', 'width' ) );
				$svg_width_int   = intval( $svg_width );
				$svg_bg          = 'fill="' . Fusion_Sanitize::color( fusion_get_option( 'header_bg_color' ) ) . '"';
				$svg_border      = '';

				$header_2_3_border = ( 'v2' === $header_layout || 'v3' === $header_layout );
				$header_4_5_border = ( ( 'v4' === $header_layout || 'v5' === $header_layout ) && 1 === Fusion_Color::new_color( Fusion_Sanitize::color( fusion_get_option( 'header_bg_color' ) ) )->alpha );

				if ( 'top' !== $header_position || $header_2_3_border || $header_4_5_border ) {
					$svg_border = 'class="header_border_color_stroke" stroke-width="1"';
				}

				if ( 'left' === $header_position ) {
					$svg = '<span class="fusion-arrow-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
						<path d="M0 0 L' . $svg_width_int . ' ' . ( $svg_height_int / 2 ) . ' L0 ' . $svg_height_int . ' Z" ' . $svg_bg . ' ' . $svg_border . '/>
						</svg></span>';
				} elseif ( 'right' === $header_position ) {
					$svg = '<span class="fusion-arrow-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
					<path d="M' . $svg_width_int . ' 0 L0 ' . ( $svg_height_int / 2 ) . ' L' . $svg_width_int . ' ' . $svg_height_int . ' Z" ' . $svg_bg . ' ' . $svg_border . '/>
					</svg></span>';
				} elseif ( 'top' === $header_position ) {
					$svg = '<span class="fusion-arrow-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
					<path d="M0 0 L' . ( $svg_width_int / 2 ) . ' ' . $svg_height_int . ' L' . $svg_width_int . ' 0 Z" ' . $svg_bg . ' ' . $svg_border . '/>
					</svg></span>';
				}

				// Add svg markup for dropdown.
				if ( $has_children ) {
					$svg_bg = 'fill="' . Fusion_Sanitize::color( Avada()->settings->get( 'menu_sub_bg_color' ) ) . '"';
					if ( 'top' === $header_position ) {
						$dropdownsvg = '<span class="fusion-dropdown-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
						<path d="M0 ' . $svg_height_int . ' L' . ( $svg_width_int / 2 ) . ' 0 L' . $svg_width_int . ' ' . $svg_height_int . ' Z" ' . $svg_bg . '/>
						</svg></span>';
					} elseif ( 'left' === $header_position ) {
						$dropdownsvg = '<span class="fusion-dropdown-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
						<path d="M' . $svg_width_int . ' 0 L0 ' . ( $svg_height_int / 2 ) . ' L' . $svg_width_int . ' ' . $svg_height_int . ' Z" ' . $svg_bg . '/>
						</svg></span>';
					} elseif ( 'right' === $header_position ) {
						$dropdownsvg = '<span class="fusion-dropdown-svg"><svg height="' . $svg_height . '" width="' . $svg_width . '">
						<path d="M0 0 L' . $svg_width_int . ' ' . ( $svg_height_int / 2 ) . ' L0 ' . $svg_height_int . ' Z" ' . $svg_bg . '/>
						</svg></span>';
					}
					$svg = $svg . $dropdownsvg;
				}
			}

			return $title . $svg;
		}
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
