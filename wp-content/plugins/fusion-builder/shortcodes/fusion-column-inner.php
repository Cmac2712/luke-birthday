<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( ! class_exists( 'FusionSC_ColumnInner' ) ) {
	/**
	 * Shortcode class.
	 *
	 * @since 1.0
	 */
	class FusionSC_ColumnInner extends Fusion_Element {

		/**
		 * Inner column counter.
		 *
		 * @access private
		 * @since 2.1
		 * @var int
		 */
		private $column_counter = 1;

		/**
		 * An array of the shortcode arguments.
		 *
		 * @access protected
		 * @since 2.1
		 * @var array
		 */
		protected $args;

		/**
		 * Constructor.
		 *
		 * @access public
		 * @since 1.0
		 */
		public function __construct() {
			parent::__construct();
			add_shortcode( 'fusion_builder_column_inner', [ $this, 'render' ] );

		}

		/**
		 * Gets the default values.
		 *
		 * @static
		 * @access public
		 * @since 2.0.0
		 * @return array
		 */
		public static function get_element_defaults() {
			$fusion_settings = fusion_get_fusion_settings();

			return [
				'hide_on_mobile'             => fusion_builder_default_visibility( 'string' ),
				'class'                      => '',
				'id'                         => '',
				'background_color'           => '',
				'gradient_start_color'       => '',
				'gradient_end_color'         => '',
				'gradient_start_position'    => '0',
				'gradient_end_position'      => '100',
				'gradient_type'              => 'linear',
				'radial_direction'           => 'center',
				'linear_angle'               => '180',
				'background_image'           => '',
				'background_position'        => 'left top',
				'background_repeat'          => 'no-repeat',
				'background_blend_mode'      => 'none',
				'border_color'               => '',
				'border_position'            => 'all',
				'border_radius_bottom_left'  => '',
				'border_radius_bottom_right' => '',
				'border_radius_top_left'     => '',
				'border_radius_top_right'    => '',
				'border_size'                => '',
				'border_style'               => '',
				'box_shadow'                 => '',
				'box_shadow_blur'            => '',
				'box_shadow_color'           => '',
				'box_shadow_horizontal'      => '',
				'box_shadow_spread'          => '',
				'box_shadow_style'           => '',
				'box_shadow_vertical'        => '',
				'margin_top'                 => $fusion_settings->get( 'col_margin', 'top' ),
				'margin_bottom'              => $fusion_settings->get( 'col_margin', 'bottom' ),
				'row_column_index'           => '',
				'spacing'                    => '4%',
				'padding'                    => '',
				'animation_type'             => '',
				'animation_direction'        => 'left',
				'animation_speed'            => '0.3',
				'animation_offset'           => $fusion_settings->get( 'animation_offset' ),
				'center_content'             => 'no',
				'type'                       => '1_3',
				'link'                       => '',
				'target'                     => '',
				'hover_type'                 => 'none',
				'min_height'                 => '',
				// Filters.
				'filter_hue'                 => '0',
				'filter_saturation'          => '100',
				'filter_brightness'          => '100',
				'filter_contrast'            => '100',
				'filter_invert'              => '0',
				'filter_sepia'               => '0',
				'filter_opacity'             => '100',
				'filter_blur'                => '0',
				'filter_hue_hover'           => '0',
				'filter_saturation_hover'    => '100',
				'filter_brightness_hover'    => '100',
				'filter_contrast_hover'      => '100',
				'filter_invert_hover'        => '0',
				'filter_sepia_hover'         => '0',
				'filter_opacity_hover'       => '100',
				'filter_blur_hover'          => '0',
			];
		}

		/**
		 * Maps settings to param variables.
		 *
		 * @static
		 * @access public
		 * @since 2.0.0
		 * @return array
		 */
		public static function settings_to_params() {
			return [
				'animation_offset'   => 'animation_offset',
				'col_margin[top]'    => 'margin_top',
				'col_margin[bottom]' => 'margin_bottom',
			];
		}

		/**
		 * Column shortcode.
		 *
		 * @since 1.0
		 * @param array  $atts    The attributes array.
		 * @param string $content The content.
		 */
		public function render( $atts, $content = '' ) {
			global $inner_column, $global_column_inner_array, $inner_columns, $is_IE, $is_edge;
			$fusion_settings = fusion_get_fusion_settings();

			$content_id = get_the_ID();

			$defaults = self::get_element_defaults();
			if ( ! isset( $atts['padding'] ) ) {
				$padding_values           = [];
				$padding_values['top']    = ( isset( $atts['padding_top'] ) && '' !== $atts['padding_top'] ) ? $atts['padding_top'] : '0px';
				$padding_values['right']  = ( isset( $atts['padding_right'] ) && '' !== $atts['padding_right'] ) ? $atts['padding_right'] : '0px';
				$padding_values['bottom'] = ( isset( $atts['padding_bottom'] ) && '' !== $atts['padding_bottom'] ) ? $atts['padding_bottom'] : '0px';
				$padding_values['left']   = ( isset( $atts['padding_left'] ) && '' !== $atts['padding_left'] ) ? $atts['padding_left'] : '0px';

				$defaults['padding'] = implode( ' ', $padding_values );
			}

			extract( $this->args = shortcode_atts( $defaults, $atts, 'fusion_builder_column_inner' ) );

			$style               = '';
			$classes             = 'fusion-builder-nested-column-' . $this->column_counter;
			$wrapper_classes     = 'fusion-column-wrapper fusion-column-wrapper-' . $this->column_counter;
			$wrapper_style       = '';
			$wrapper_style_bg    = '';
			$href_link           = '';
			$last                = 'no';
			$current_row         = '';
			$current_column_type = '';

			$alpha_background_color     = 1;
			$alpha_gradient_start_color = 1;
			$alpha_gradient_end_color   = 1;

			if ( class_exists( 'Fusion_Color' ) ) {
				$alpha_background_color     = Fusion_Color::new_color( $background_color )->alpha;
				$alpha_gradient_start_color = Fusion_Color::new_color( $gradient_start_color )->alpha;
				$alpha_gradient_end_color   = Fusion_Color::new_color( $gradient_end_color )->alpha;
			}

			$is_gradient_color = ( ! empty( $gradient_start_color ) && 0 !== $alpha_gradient_start_color ) || ( ! empty( $gradient_end_color ) && 0 !== $alpha_gradient_end_color ) ? true : false;

			// If no blend mode is defined, check if we should set to overlay.
			if ( ! isset( $atts['background_blend_mode'] ) && 1 > $alpha_background_color && 0 !== $alpha_background_color && ! $is_gradient_color && ! empty( $background_image ) ) {
				$background_blend_mode = 'overlay';
			}

			if ( '' === $margin_bottom ) {
				$margin_bottom = $fusion_settings->get( 'col_margin', 'bottom' );
			} else {
				$margin_bottom = fusion_library()->sanitize->get_value_with_unit( $margin_bottom );
			}
			if ( '' === $margin_top ) {
				$margin_top = $fusion_settings->get( 'col_margin', 'top' );
			} else {
				$margin_top = fusion_library()->sanitize->get_value_with_unit( $margin_top );
			}

			if ( $border_size ) {
				$border_size = FusionBuilder::validate_shortcode_attr_value( $border_size, 'px' );
			}

			if ( $padding ) {
				$padding = fusion_library()->sanitize->get_value_with_unit( $padding );
			}

			// If there is no map of columns, we must use fallback method like 4.0.3.
			if ( ( ! isset( $global_column_inner_array[ $content_id ] ) || ! [ $global_column_inner_array[ $content_id ] ] || 0 === count( $global_column_inner_array[ $content_id ] ) ) && 'no' !== $spacing ) {
				$spacing = 'yes';
			}

			$border_radius_top_left     = $border_radius_top_left ? fusion_library()->sanitize->get_value_with_unit( $border_radius_top_left ) : '0px';
			$border_radius_top_right    = $border_radius_top_right ? fusion_library()->sanitize->get_value_with_unit( $border_radius_top_right ) : '0px';
			$border_radius_bottom_right = $border_radius_bottom_right ? fusion_library()->sanitize->get_value_with_unit( $border_radius_bottom_right ) : '0px';
			$border_radius_bottom_left  = $border_radius_bottom_left ? fusion_library()->sanitize->get_value_with_unit( $border_radius_bottom_left ) : '0px';
			$border_radius              = $border_radius_top_left . ' ' . $border_radius_top_right . ' ' . $border_radius_bottom_right . ' ' . $border_radius_bottom_left;
			$border_radius              = ( '0px 0px 0px 0px' === $border_radius ) ? '' : $border_radius;

			// Set the row and column index as well as the column type for the current column.
			if ( '' !== $row_column_index ) {
				$row_column_index     = explode( '_', $row_column_index );
				$current_row_index    = $row_column_index[0];
				$current_column_index = $row_column_index[1];
				if ( isset( $global_column_inner_array[ $content_id ] ) && isset( $global_column_inner_array[ $content_id ][ $current_row_index ] ) ) {
					$current_row = $global_column_inner_array[ $content_id ][ $current_row_index ];
				}
				if ( isset( $current_row ) && is_array( $current_row ) ) {
					$current_row_number_of_columns = count( $current_row );
					$current_column_type           = $current_row[ $current_column_index ][1];
				}
			}

			// Column size value.
			switch ( $type ) {
				case '1_1':
					$column_size = 1;
					$classes    .= ' fusion-one-full';
					break;
				case '1_4':
					$column_size = 0.25;
					$classes    .= ' fusion-one-fourth';
					break;
				case '3_4':
					$column_size = 0.75;
					$classes    .= ' fusion-three-fourth';
					break;
				case '1_2':
					$column_size = 0.50;
					$classes    .= ' fusion-one-half';
					break;
				case '1_3':
					$column_size = 0.3333;
					$classes    .= ' fusion-one-third';
					break;
				case '2_3':
					$column_size = 0.6666;
					$classes    .= ' fusion-two-third';
					break;
				case '1_5':
					$column_size = 0.20;
					$classes    .= ' fusion-one-fifth';
					break;
				case '2_5':
					$column_size = 0.40;
					$classes    .= ' fusion-two-fifth';
					break;
				case '3_5':
					$column_size = 0.60;
					$classes    .= ' fusion-three-fifth';
					break;
				case '4_5':
					$column_size = 0.80;
					$classes    .= ' fusion-four-fifth';
					break;
				case '5_6':
					$column_size = 0.8333;
					$classes    .= ' fusion-five-sixth';
					break;
				case '1_6':
					$column_size = 0.1666;
					$classes    .= ' fusion-one-sixth';
					break;
			}

			// Map old column width to old width with spacing.
			$map_old_spacing = [
				'0.1666' => '13.3333%',
				'0.8333' => '82.6666%',
				'0.2'    => '16.8%',
				'0.4'    => '37.6%',
				'0.6'    => '58.4%',
				'0.8'    => '79.2%',
				'0.25'   => '22%',
				'0.75'   => '74%',
				'0.3333' => '30.6666%',
				'0.6666' => '65.3333%',
				'0.5'    => '48%',
			];

			$old_spacing_values = [
				'yes',
				'Yes',
				'No',
				'no',
			];

			// Check if all columns are yes, no, or empty.
			$fallback = true;
			if ( is_array( $current_row ) && 0 !== count( $global_column_inner_array[ $content_id ] ) ) {
				foreach ( $current_row as $column_space ) {
					if ( isset( $column_space[0] ) && ! in_array( $column_space[0], $old_spacing_values, true ) ) {
						$fallback = false;
					}
				}
			}

			// If not using a fallback, work out first and last from the generated array.
			if ( ! $fallback ) {
				if ( false !== strpos( $current_column_type, 'first' ) ) {
					$classes .= ' fusion-column-first';
				}

				if ( false !== strpos( $current_column_type, 'last' ) ) {
					$classes .= ' fusion-column-last';
					$last     = 'yes';
				} else {
					$last = 'no';
				}
			} else {
				// If we are using the fallback, then work out first and last using global var.
				$last = '';

				if ( ! $inner_columns ) {
					$inner_columns = 0;
				}

				if ( 0 === $inner_columns ) {
					$classes .= ' fusion-column-first';
				}
				$inner_columns += $column_size;
				if ( 0.990 < $inner_columns ) {
					$last          = 'yes';
					$inner_columns = 0;
				}
				if ( 1 < $inner_columns ) {
					$last          = 'no';
					$inner_columns = $column_size;
					$classes      .= ' fusion-column-first';
				}

				if ( 'yes' === $last ) {
					$classes .= ' fusion-column-last';
				}
			}

			// Background.
			$background_color_style = '';
			if ( ! empty( $background_color ) && ( empty( $background_image ) || 0 !== $alpha_background_color ) ) {
				$background_color_style = 'background-color:' . esc_attr( $background_color ) . ';';
				if ( ( 'none' === $hover_type || empty( $hover_type ) ) && empty( $link ) ) {
					$wrapper_style .= $background_color_style;
				} else {
					$wrapper_style_bg .= $background_color_style;
				}
			}

			if ( 1 > $alpha_background_color && 0 !== $alpha_background_color && 'none' === $background_blend_mode && ! $is_gradient_color && empty( $background_image ) ) {
				$classes .= ' fusion-blend-mode';
			}

			$background_image_style = '';
			if ( ! empty( $background_image ) ) {
				$background_image_style .= "background-image: url('" . esc_attr( $background_image ) . "');";
			}

			if ( $is_gradient_color ) {
				$background_image_style .= 'background-image: ' . Fusion_Builder_Gradient_Helper::get_gradient_string( $this->args, 'column' );
			}

			if ( ! empty( $background_position ) ) {
				$background_image_style .= 'background-position:' . esc_attr( $background_position ) . ';';
			}

			if ( 'none' !== $background_blend_mode ) {
				$background_image_style .= 'background-blend-mode: ' . esc_attr( $background_blend_mode ) . ';';
			}

			if ( ! empty( $background_repeat ) ) {
				$background_image_style .= 'background-repeat:' . esc_attr( $background_repeat ) . ';';
				if ( 'no-repeat' === $background_repeat ) {
					$background_image_style .= '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
				}
			}

			if ( ( ! $is_IE && ! $is_edge ) || ( 'none' !== $hover_type || ( ! empty( $hover_type ) && 'none' !== $hover_type ) || ! empty( $link ) ) ) {
				$wrapper_style_bg .= $background_image_style;
			}

			// Border.
			if ( $border_color && $border_size && $border_style ) {
				$border_position = ( 'all' !== $border_position ) ? '-' . $border_position : '';
				$wrapper_style  .= esc_attr( 'border' . $border_position . ':' . $border_size . ' ' . $border_style . ' ' . $border_color . ';' );
			}

			// Border radius.
			if ( $border_radius ) {
				$wrapper_style    .= 'overflow:hidden;border-radius:' . esc_attr( $border_radius ) . ';';
				$wrapper_style_bg .= 'border-radius:' . esc_attr( $border_radius ) . ';';
			}

			// Box shadow.
			if ( 'yes' === $box_shadow ) {
				$box_shadow_styles = Fusion_Builder_Box_Shadow_Helper::get_box_shadow_styles(
					[
						'box_shadow_horizontal' => $box_shadow_horizontal,
						'box_shadow_vertical'   => $box_shadow_vertical,
						'box_shadow_blur'       => $box_shadow_blur,
						'box_shadow_spread'     => $box_shadow_spread,
						'box_shadow_color'      => $box_shadow_color,
						'box_shadow_style'      => $box_shadow_style,
					]
				);
				$box_shadow_styles = 'box-shadow:' . esc_attr( trim( $box_shadow_styles ) ) . ';';

				if ( 'liftup' === $hover_type || ! empty( $link ) ) {
					$wrapper_style_bg .= $box_shadow_styles;
				} else {
					$wrapper_style   .= $box_shadow_styles;
					$wrapper_classes .= ' fusion-column-has-shadow';
				}
			}

			// Padding.
			if ( ! empty( $padding ) ) {
				$wrapper_style .= 'padding: ' . esc_attr( $padding ) . ';';
			}

			// Margins.
			if ( '' !== $margin_top && 'undefined' !== $margin_top ) {
				$style .= 'margin-top: ' . esc_attr( $margin_top ) . ';';
			}

			if ( '' !== $margin_bottom && 'undefined' !== $margin_bottom ) {
				$style .= 'margin-bottom: ' . esc_attr( $margin_bottom ) . ';';
			}

			// Fix the spacing values.
			if ( is_array( $current_row ) ) {
				foreach ( $current_row as $key => $value ) {
					if ( '' === $value[0] || 'yes' === $value[0] ) {
						$current_row[ $key ] = '4%';
					} elseif ( 'no' === $value[0] ) {
						unset( $current_row[ $key ] );
					} else {
						$current_row[ $key ] = $value[0];
					}
				}
			}

			// Spacing.  If using fallback and spacing is no then ignore and just use full % width.
			if ( isset( $spacing ) && ! ( in_array( $spacing, [ '0px', 'no', '0', 0, false ], true ) && $fallback ) ) {
				$width = $column_size * 100 . '%';
				if ( 'yes' === $spacing || '' === $spacing ) {
					$spacing = '4%';
				} elseif ( 'no' === $spacing ) {
					$spacing = '0px';
				}
				$spacing = fusion_library()->sanitize->get_value_with_unit( esc_attr( $spacing ) );

				if ( 0 === filter_var( $spacing, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) ) {
					$classes .= ' fusion-spacing-no';
				}

				$width_offset = '';
				if ( is_array( $current_row ) ) {
					$width_offset = '( ( ' . implode( ' + ', $current_row ) . ' ) * ' . $column_size . ' ) ';
				}

				if ( 'yes' !== $last && ! ( $fallback && '0px' === $spacing ) ) {
					$spacing_direction = 'right';
					if ( is_rtl() ) {
						$spacing_direction = 'left';
					}
					if ( ! $fallback ) {
						$style .= 'width:' . $width . ';width:calc(' . $width . ' - ' . $width_offset . ');margin-' . $spacing_direction . ':' . $spacing . ';';
					} else {
						$style .= 'width:' . $map_old_spacing[ strval( $column_size ) ] . '; margin-' . $spacing_direction . ':' . $spacing . ';';
					}
				} elseif ( isset( $current_row_number_of_columns ) && 1 < $current_row_number_of_columns ) {
					if ( ! $fallback ) {
						$style .= 'width:' . $width . ';width:calc(' . $width . ' - ' . $width_offset . ');';
					} elseif ( '0px' !== $spacing && isset( $map_old_spacing[ strval( $column_size ) ] ) ) {
						$style .= 'width:' . $map_old_spacing[ strval( $column_size ) ];
					} else {
						$style .= 'width:' . $width;
					}
				} elseif ( ! isset( $current_row_number_of_columns ) && isset( $map_old_spacing[ strval( $column_size ) ] ) ) {
					$style .= 'width:' . $map_old_spacing[ strval( $column_size ) ];
				}
			}

			// Custom CSS class.
			if ( ! empty( $class ) ) {
				$classes .= " {$class}";
			}

			// Visibility classes.
			$classes = fusion_builder_visibility_atts( $hide_on_mobile, $classes );

			// Hover type or link.
			if ( ! empty( $link ) || ( 'none' !== $hover_type && ! empty( $hover_type ) ) ) {
				$classes .= ' fusion-column-inner-bg-wrapper';
			}

			// Hover type or link.
			if ( ! empty( $link ) ) {
				$href_link .= 'href="' . $link . '"';
			}

			if ( '_blank' === $target ) {
				$href_link .= ' rel="noopener noreferrer" target="_blank"';
			}

			// Min height for newly created columns by the converter.
			if ( 'none' === $min_height ) {
				$classes .= ' fusion-column-no-min-height';
			}

			if ( empty( $animation_offset ) ) {
				$animation_offset = $fusion_settings->get( 'animation_offset' );
			}

			// Animation.
			$animation = fusion_builder_animation_data( $animation_type, $animation_direction, $animation_speed, $animation_offset );
			$classes  .= $animation['class'];

			// Style.
			$style = ! empty( $style ) ? " style='{$style}'" : '';

			// Wrapper Style.
			$wrapper_style = ! empty( $wrapper_style ) ? $wrapper_style : '';

			// Shortcode content.
			$inner_content = do_shortcode( fusion_builder_fix_shortcodes( $content ) );

			// If content should be centered, add needed markup.
			if ( 'yes' === $center_content ) {
				$inner_content = '<div class="fusion-column-content-centered"><div class="fusion-column-content">' . $inner_content . '</div></div>';
			}

			if ( ( 'none' === $hover_type && empty( $link ) ) || ( empty( $hover_type ) && empty( $link ) ) ) {

				// Background color fallback for IE and Edge.
				$additional_bg_image_div = '';

				$bg_color_fix = '';

				if ( $is_IE || $is_edge ) {
					$ms_border_radius = $border_radius ? 'border-radius:' . esc_attr( $border_radius ) . ';' : '';

					if ( ! empty( $background_color ) && 1 === $alpha_background_color && ! empty( $background_image ) ) {
						$bg_color_fix            = 'background-color:transparent;';
						$background_image_style .= 'background-color:' . esc_attr( $background_color ) . ';';
					}
					$additional_bg_image_div = '<div class="' . $wrapper_classes . '" style="content:\'\';z-index:-1;position:absolute;top:0;right:0;bottom:0;left:0;' . $ms_border_radius . $background_image_style . '" data-bg-url="' . $background_image . '"></div>';
					$wrapper_classes         = str_replace( ' lazyload', '', $wrapper_classes );
				}

				$output =
				'<div ' . ( ! empty( $id ) ? 'id="' . esc_attr( $id ) . '"' : '' ) . esc_attr( $animation['data'] ) . ' class="fusion-layout-column fusion_builder_column fusion_builder_column_' . $type . ' ' . esc_attr( $classes ) . ' ' . ( ! empty( $type ) ? esc_attr( $type ) : '' ) . '" ' . $style . '>
					<div class="' . $wrapper_classes . '" style="' . $wrapper_style . $wrapper_style_bg . $bg_color_fix . '" data-bg-url="' . $background_image . '">
						' . $inner_content
						. $additional_bg_image_div . '

					</div>
				</div>';
			} else {

				if ( $animation['class'] && 'liftup' === $hover_type ) {
					$classes .= ' fusion-column-hover-type-liftup';
				}

				// Background color fallback for IE and Edge.
				$additional_bg_color_span = '';
				if ( $background_color_style && ( $is_IE || $is_edge ) ) {
					$additional_bg_color_span = '<span class="fusion-column-inner-bg-image" style="' . $background_color_style . '"></span>';
				}

				$output =
				'<div ' . ( ! empty( $id ) ? 'id="' . esc_attr( $id ) . '"' : '' ) . esc_attr( $animation['data'] ) . ' class="fusion-layout-column fusion_builder_column fusion_builder_column_' . $type . ' ' . esc_attr( $classes ) . ' ' . ( ! empty( $type ) ? esc_attr( $type ) : '' ) . '" ' . $style . '>
					<div class="' . $wrapper_classes . '" style="' . $wrapper_style . '" data-bg-url="' . $background_image . '">
						' . $inner_content . '
					</div>
					<span class="fusion-column-inner-bg hover-type-' . $hover_type . '">
						<a ' . $href_link . '>
							<span class="fusion-column-inner-bg-image" style="' . $wrapper_style_bg . '"></span>'
							. $additional_bg_color_span .
						'</a>
					</span>
				</div>';
			}

			// Add filter styles.
			$filter_style = Fusion_Builder_Filter_Helper::get_filter_style_element( $this->args, '.fusion-builder-nested-column-' . $this->column_counter );
			if ( '' !== $filter_style ) {
				$output .= $filter_style;
			}

			$this->column_counter++;

			return apply_filters( 'fusion_element_column_inner_content', $output, $atts );

		}
	}
}
new FusionSC_ColumnInner();

/**
 * Map Column shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_element_column_inner() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_ColumnInner',
			[
				'name'              => esc_attr__( 'Nested Column', 'fusion-builder' ),
				'shortcode'         => 'fusion_builder_column_inner',
				'hide_from_builder' => true,
				'params'            => [
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Column Spacing', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the margin added to the column. Enter value including any valid CSS unit, ex: 4%.', 'fusion-builder' ),
						'param_name'  => 'spacing',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Center Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to center the content vertically. Equal heights on the parent container must be turned on.', 'fusion-builder' ),
						'param_name'  => 'center_content',
						'default'     => 'no',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Hover Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the hover effect type. This will disable links and hover effects on elements inside the column.', 'fusion-builder' ),
						'param_name'  => 'hover_type',
						'default'     => 'none',
						'value'       => [
							'none'    => esc_attr__( 'None', 'fusion-builder' ),
							'zoomin'  => esc_attr__( 'Zoom In', 'fusion-builder' ),
							'zoomout' => esc_attr__( 'Zoom Out', 'fusion-builder' ),
							'liftup'  => esc_attr__( 'Lift Up', 'fusion-builder' ),
						],
						'preview'     => [
							'selector' => '.fusion-column-inner-bg',
							'type'     => 'class',
							'toggle'   => 'hover',
						],
					],
					[
						'type'         => 'link_selector',
						'heading'      => esc_attr__( 'Link URL', 'fusion-builder' ),
						'description'  => esc_attr__( 'Add the URL the column will link to, ex: http://example.com.', 'fusion-builder' ),
						'param_name'   => 'link',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => esc_attr__( '_self = open in same browser tab, _blank = open in new browser tab.', 'fusion-builder' ),
						'param_name'  => 'target',
						'default'     => '_self',
						'value'       => [
							'_self'  => esc_attr__( '_self', 'fusion-builder' ),
							'_blank' => esc_attr__( '_blank', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Ignore Equal Heights', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to ignore equal heights on this column if you are using equal heights on the surrounding container.', 'fusion-builder' ),
						'param_name'  => 'min_height',
						'default'     => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'value'       => [
							'none' => esc_attr__( 'Yes', 'fusion-builder' ),
							''     => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Column Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the column on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'In pixels.', 'fusion-builder' ),
						'param_name'  => 'border_size',
						'value'       => '0',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color.', 'fusion-builder' ),
						'param_name'  => 'border_color',
						'value'       => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Border Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border style.', 'fusion-builder' ),
						'param_name'  => 'border_style',
						'default'     => 'solid',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
						'value'       => [
							'solid'  => esc_attr__( 'Solid', 'fusion-builder' ),
							'dashed' => esc_attr__( 'Dashed', 'fusion-builder' ),
							'dotted' => esc_attr__( 'Dotted', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Border Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the postion of the border.', 'fusion-builder' ),
						'param_name'  => 'border_position',
						'default'     => 'all',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
						'value'       => [
							'all'    => esc_attr__( 'All', 'fusion-builder' ),
							'top'    => esc_attr__( 'Top', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
							'bottom' => esc_attr__( 'Bottom', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Border Radius', 'fusion-builder' ),
						'description'      => __( 'Enter values including any valid CSS unit, ex: 10px. <strong>IMPORTANT:</strong> In order to make border radius work in browsers, the overflow CSS rule of the column needs set to hidden. Thus, depending on the setup, some contents might get clipped.', 'fusion-builder' ),
						'param_name'       => 'border_radius',
						'value'            => [
							'border_radius_top_left'     => '',
							'border_radius_top_right'    => '',
							'border_radius_bottom_right' => '',
							'border_radius_bottom_left'  => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Box Shadow', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to enable box shadows.', 'fusion-builder' ),
						'param_name'  => 'box_shadow',
						'default'     => 'no',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Box Shadow Position', 'fusion-builder' ),
						'description'      => esc_attr__( 'Set the vertical and horizontal position of the box shadow. Positive values put the shadow below and right of the box, negative values put it above and left of the box. In pixels, ex. 5px.', 'fusion-builder' ),
						'param_name'       => 'dimension_box_shadow',
						'value'            => [
							'box_shadow_vertical'   => '',
							'box_shadow_horizontal' => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'box_shadow',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Box Shadow Blur Radius', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the blur radius of the box shadow. In pixels.', 'fusion-builder' ),
						'param_name'  => 'box_shadow_blur',
						'value'       => '0',
						'min'         => '0',
						'max'         => '100',
						'step'        => '1',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'box_shadow',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Box Shadow Spread Radius', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the spread radius of the box shadow. A positive value increases the size of the shadow, a negative value decreases the size of the shadow. In pixels.', 'fusion-builder' ),
						'param_name'  => 'box_shadow_spread',
						'value'       => '0',
						'min'         => '-100',
						'max'         => '100',
						'step'        => '1',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'box_shadow',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Box Shadow Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the box shadow.', 'fusion-builder' ),
						'param_name'  => 'box_shadow_color',
						'value'       => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'box_shadow',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Box Shadow Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the style of the box shadow to either be an outer or inner shadow.', 'fusion-builder' ),
						'param_name'  => 'box_shadow_style',
						'default'     => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'value'       => [
							''      => esc_attr__( 'Outer', 'fusion-builder' ),
							'inset' => esc_attr__( 'Inner', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'box_shadow',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels (px), ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'padding',
						'value'            => [
							'padding_top'    => '',
							'padding_right'  => '',
							'padding_bottom' => '',
							'padding_left'   => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],
					'fusion_margin_placeholder'    => [],
					[
						'type'             => 'subgroup',
						'heading'          => esc_attr__( 'Background Type', 'fusion-builder' ),
						'description'      => esc_attr__( 'Use filters to see specific type of content.', 'fusion-builder' ),
						'param_name'       => 'background_type',
						'default'          => 'single',
						'group'            => esc_attr__( 'BG', 'fusion-builder' ),
						'remove_from_atts' => true,
						'value'            => [
							'single'   => esc_attr__( 'Color', 'fusion-builder' ),
							'gradient' => esc_attr__( 'Gradient', 'fusion-builder' ),
							'image'    => esc_attr__( 'Image', 'fusion-builder' ),
						],
						'icons'            => [
							'single'   => '<span class="fusiona-fill-drip-solid" style="font-size:18px;"></span>',
							'gradient' => '<span class="fusiona-gradient-fill" style="font-size:18px;"></span>',
							'image'    => '<span class="fusiona-image" style="font-size:18px;"></span>',
							'video'    => '<span class="fusiona-video" style="font-size:18px;"></span>',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color.', 'fusion-builder' ),
						'param_name'  => 'background_color',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'single',
						],
					],
					'fusion_gradient_placeholder'  => [
						'selector' => '.fusion-column-wrapper',
					],
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Background Image', 'fusion-builder' ),
						'description' => esc_attr__( 'Upload an image to display in the background.', 'fusion-builder' ),
						'param_name'  => 'background_image',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the postion of the background image.', 'fusion-builder' ),
						'param_name'  => 'background_position',
						'default'     => 'left top',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'value'       => [
							'left top'      => esc_attr__( 'Left Top', 'fusion-builder' ),
							'left center'   => esc_attr__( 'Left Center', 'fusion-builder' ),
							'left bottom'   => esc_attr__( 'Left Bottom', 'fusion-builder' ),
							'right top'     => esc_attr__( 'Right Top', 'fusion-builder' ),
							'right center'  => esc_attr__( 'Right Center', 'fusion-builder' ),
							'right bottom'  => esc_attr__( 'Right Bottom', 'fusion-builder' ),
							'center top'    => esc_attr__( 'Center Top', 'fusion-builder' ),
							'center center' => esc_attr__( 'Center Center', 'fusion-builder' ),
							'center bottom' => esc_attr__( 'Center Bottom', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Repeat', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how the background image repeats.', 'fusion-builder' ),
						'param_name'  => 'background_repeat',
						'value'       => [
							'no-repeat' => esc_attr__( 'No Repeat', 'fusion-builder' ),
							'repeat'    => esc_attr__( 'Repeat Vertically and Horizontally', 'fusion-builder' ),
							'repeat-x'  => esc_attr__( 'Repeat Horizontally', 'fusion-builder' ),
							'repeat-y'  => esc_attr__( 'Repeat Vertically', 'fusion-builder' ),
						],
						'default'     => 'no-repeat',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Blend Mode', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how blending should work for each background layer.', 'fusion-builder' ),
						'param_name'  => 'background_blend_mode',
						'value'       => [
							'none'        => esc_attr__( 'Disabled', 'fusion-builder' ),
							'multiply'    => esc_attr__( 'Multiply', 'fusion-builder' ),
							'screen'      => esc_attr__( 'Screen', 'fusion-builder' ),
							'overlay'     => esc_attr__( 'Overlay', 'fusion-builder' ),
							'darken'      => esc_attr__( 'Darken', 'fusion-builder' ),
							'lighten'     => esc_attr__( 'Lighten', 'fusion-builder' ),
							'color-dodge' => esc_attr__( 'Color Dodge', 'fusion-builder' ),
							'color-burn'  => esc_attr__( 'Color Burn', 'fusion-builder' ),
							'hard-light'  => esc_attr__( 'Hard Light', 'fusion-builder' ),
							'soft-light'  => esc_attr__( 'Soft Light', 'fusion-builder' ),
							'difference'  => esc_attr__( 'Difference', 'fusion-builder' ),
							'exclusion'   => esc_attr__( 'Exclusion', 'fusion-builder' ),
							'hue'         => esc_attr__( 'Hue', 'fusion-builder' ),
							'saturation'  => esc_attr__( 'Saturation', 'fusion-builder' ),
							'color'       => esc_attr__( 'Color', 'fusion-builder' ),
							'luminosity'  => esc_attr__( 'Luminosity', 'fusion-builder' ),
						],
						'default'     => 'none',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					'fusion_animation_placeholder' => [
						'preview_selector' => '$el',
					],
					'fusion_filter_placeholder'    => [
						'selector_base' => 'fusion-column-wrapper-',
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_column_inner' );
