<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 2.2
 */

if ( fusion_is_element_enabled( 'fusion_tb_pagination' ) ) {

	if ( ! class_exists( 'FusionTB_Pagination' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 2.2
		 */
		class FusionTB_Pagination extends Fusion_Component {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 2.2
			 * @var array
			 */
			protected $args;

			/**
			 * The internal container counter.
			 *
			 * @access private
			 * @since 2.2
			 * @var int
			 */
			private $counter = 1;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 2.2
			 */
			public function __construct() {
				parent::__construct( 'fusion_tb_pagination' );
				add_filter( 'fusion_attr_fusion_tb_pagination-shortcode', [ $this, 'attr' ] );
			}


			/**
			 * Check if component should render
			 *
			 * @access public
			 * @since 2.2
			 * @return boolean
			 */
			public function should_render() {
				return is_singular();
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.2
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = fusion_get_fusion_settings();
				return [
					'alignment'           => '',
					'font_size'           => $fusion_settings->get( 'body_typography', 'font-size' ),
					'text_color'          => $fusion_settings->get( 'link_color' ),
					'text_hover_color'    => $fusion_settings->get( 'primary_color' ),
					'border_size'         => 1,
					'border_color'        => $fusion_settings->get( 'sep_color' ),
					'height'              => '36',
					'margin_bottom'       => '',
					'margin_left'         => '',
					'margin_right'        => '',
					'margin_top'          => '',
					'hide_on_mobile'      => fusion_builder_default_visibility( 'string' ),
					'class'               => '',
					'id'                  => '',
					'animation_type'      => '',
					'animation_direction' => 'down',
					'animation_speed'     => '0.1',
					'animation_offset'    => $fusion_settings->get( 'animation_offset' ),
				];
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.2
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'sep_color'                  => 'border_color',
					'link_color'                 => 'text_color',
					'primary_color'              => 'text_hover_color',
					'body_typography[font-size]' => 'font_size',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 2.2
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );
				$defaults   = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_tb_pagination' );

				$defaults['border_size'] = FusionBuilder::validate_shortcode_attr_value( $defaults['border_size'], 'px' );
				$defaults['height']      = FusionBuilder::validate_shortcode_attr_value( $defaults['height'], 'px' );

				$this->args = $defaults;

				$this->emulate_post();

				$content  = '<div ' . FusionBuilder::attributes( 'fusion_tb_pagination-shortcode' ) . '>';
				$content .= get_previous_post_link( '%link', esc_attr__( 'Previous', 'fusion-builder' ) );
				$content .= get_next_post_link( '%link', esc_attr__( 'Next', 'fusion-builder' ) );
				$content .= '</div>';

				$styles = '<style type="text/css">';

				if ( $this->args['border_size'] ) {
					$styles .= '.fusion-pagination-tb-' . $this->counter . '.single-navigation{border-width:' . $this->args['border_size'] . ';}';
				}

				if ( $this->args['border_color'] ) {
					$styles .= '.fusion-pagination-tb-' . $this->counter . '.single-navigation{border-color:' . $this->args['border_color'] . ';}';
				}

				if ( $this->args['text_color'] ) {
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a,';
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a::before,';
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a::after{';
					$styles .= 'color:' . $this->args['text_color'];
					$styles .= ';}';
				}

				if ( $this->args['text_hover_color'] ) {
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a:hover,';
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a:hover::before,';
					$styles .= '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' . $this->counter . '.single-navigation a:hover::after{';
					$styles .= 'color:' . $this->args['text_hover_color'];
					$styles .= ';}';
				}

				$styles .= '</style>';

				$html = $styles . $content;

				$this->restore_post();

				$this->counter++;

				return apply_filters( 'fusion_component_' . $this->shortcode_handle . '_content', $html, $args );
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 2.2
			 * @return array
			 */
			public function attr() {
				$attr = [
					'class' => 'single-navigation clearfix fusion-pagination-tb fusion-pagination-tb-' . $this->counter,
					'style' => '',
				];

				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				$attr['style'] .= Fusion_Builder_Margin_Helper::get_margins_style( $this->args );

				if ( $this->args['alignment'] ) {
					$attr['class'] .= ' align-' . $this->args['alignment'];
				}

				if ( $this->args['height'] ) {
					$attr['style'] .= 'min-height:' . $this->args['height'] . ';';
				}

				if ( $this->args['font_size'] ) {
					$attr['style'] .= 'font-size:' . $this->args['font_size'] . ';';
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}
		}
	}

	new FusionTB_Pagination();
}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 2.2
 */
function fusion_component_pagination() {

	global $fusion_settings;

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionTB_Pagination',
			[
				'name'                    => esc_attr__( 'Pagination', 'fusion-builder' ),
				'shortcode'               => 'fusion_tb_pagination',
				'icon'                    => 'fusiona-pagination',
				'class'                   => 'hidden',
				'component'               => true,
				'templates'               => [ 'content' ],
				'components_per_template' => 2,
				'params'                  => [
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the pagination section height. In pixels.', 'fusion-builder' ),
						'param_name'  => 'height',
						'value'       => '36',
						'min'         => '0',
						'max'         => '200',
						'step'        => '1',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Text Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Make a selection for pagination text alignment.', 'fusion-builder' ),
						'param_name'  => 'alignment',
						'default'     => '',
						'value'       => [
							''      => esc_html__( 'Distributed', 'fusion-builder' ),
							'left'  => esc_html__( 'Left', 'fusion-builder' ),
							'right' => esc_html__( 'Right', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Text Font Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the font size for the pagination text. Enter value including CSS unit (px, em, rem), ex: 10px', 'fusion-builder' ),
						'param_name'  => 'font_size',
						'value'       => '',
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the pagination section text.', 'fusion-builder' ),
						'param_name'  => 'text_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'link_color' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Text Hover Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text hover color of the pagination section text.', 'fusion-builder' ),
						'param_name'  => 'text_hover_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'primary_color' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Separator Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the separators. In pixels.', 'fusion-builder' ),
						'param_name'  => 'border_size',
						'value'       => '1',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Separator Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the separators.', 'fusion-builder' ),
						'param_name'  => 'border_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'sep_color' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'margin',
						'value'            => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
					],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-pagination-tb',
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_component_pagination' );
