<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_text' ) ) {

	if ( ! class_exists( 'FusionSC_FusionText' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_FusionText extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 1.0
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
				add_filter( 'fusion_attr_text-element-wrapper', [ $this, 'wrapper_attr' ] );

				add_shortcode( 'fusion_text', [ $this, 'render' ] );

				add_filter( 'fusion_text_content', 'shortcode_unautop' );
				add_filter( 'fusion_text_content', 'do_shortcode' );
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
					'animation_direction' => 'left',
					'animation_offset'    => $fusion_settings->get( 'animation_offset' ),
					'animation_speed'     => '',
					'animation_type'      => '',
					'class'               => '',
					'columns'             => $fusion_settings->get( 'text_columns' ),
					'column_min_width'    => $fusion_settings->get( 'text_column_min_width' ),
					'column_spacing'      => $fusion_settings->get( 'text_column_spacing' ),
					'hide_on_mobile'      => fusion_builder_default_visibility( 'string' ),
					'id'                  => '',
					'rule_color'          => $fusion_settings->get( 'text_rule_color' ),
					'rule_size'           => $fusion_settings->get( 'text_rule_size' ),
					'rule_style'          => $fusion_settings->get( 'text_rule_style' ),
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
					'text_rule_style'       => 'rule_style',
					'text_rule_size'        => 'rule_size',
					'text_rule_color'       => 'rule_color',
					'text_column_spacing'   => 'column_spacing',
					'text_column_min_width' => 'column_min_width',
					'text_columns'          => 'columns',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$fusion_settings = fusion_get_fusion_settings();

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_text' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_text', $args );

				$this->args = $defaults;

				if ( 'default' === $this->args['rule_style'] ) {
					$this->args['rule_style'] = $fusion_settings->get( 'text_rule_style' );
				}

				$html = '<div ' . FusionBuilder::attributes( 'text-element-wrapper' ) . '>' . wpautop( $content, false ) . '</div>';

				$html = apply_filters( 'fusion_text_content', $html, $content );

				return apply_filters( 'fusion_element_text_content', $html, $args );
			}


			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.5
			 * @return array
			 */
			public function wrapper_attr() {

				$attr = [
					'class' => 'fusion-text',
					'style' => '',
				];

				// Only add styling if more than one column is used.
				if ( 1 < $this->args['columns'] ) {
					$attr['class'] .= ' fusion-text-split-columns fusion-text-columns-' . $this->args['columns'];

					$browser_prefixes = [ '-webkit-', '-moz-', '' ];

					foreach ( $browser_prefixes as $prefix ) {

						$attr['style'] .= ' ' . $prefix . 'column-count:' . $this->args['columns'] . ';';

						if ( $this->args['column_spacing'] ) {
							$attr['style'] .= ' ' . $prefix . 'column-gap:' . FusionBuilder::validate_shortcode_attr_value( $this->args['column_spacing'], 'px' ) . ';';
						}

						if ( $this->args['column_min_width'] ) {
							$attr['style'] .= ' ' . $prefix . 'column-width:' . FusionBuilder::validate_shortcode_attr_value( $this->args['column_min_width'], 'px' ) . ';';
						}

						if ( 'none' !== $this->args['rule_style'] ) {
							$attr['style'] .= ' ' . $prefix . 'column-rule:' . $this->args['rule_size'] . 'px ' . $this->args['rule_style'] . ' ' . $this->args['rule_color'] . ';';
						}
					}
				}

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}


			/**
			 * Adds settings to element options panel.
			 *
			 * @access public
			 * @since 1.5
			 * @return array $sections Title settings.
			 */
			public function add_options() {
				global $fusion_settings;

				return [
					'text_shortcode_section' => [
						'label'       => esc_html__( 'Text Block', 'fusion-builder' ),
						'description' => '',
						'id'          => 'text_shortcode_section',
						'type'        => 'accordion',
						'icon'        => 'fusiona-font',
						'fields'      => [
							'text_columns'          => [
								'label'       => esc_html__( 'Number Of Inline Columns', 'fusion-builder' ),
								'description' => __( 'Set the number of columns the text should be broken into.<br />IMPORTANT: This feature is designed to be used for running text, images, dropcaps and other inline content. While some block elements will work, their usage is not recommended and others can easily break the layout.', 'fusion-builder' ),
								'id'          => 'text_columns',
								'default'     => '1',
								'type'        => 'slider',
								'transport'   => 'postMessage',
								'choices'     => [
									'min'  => '1',
									'max'  => '6',
									'step' => '1',
								],
							],
							'text_column_min_width' => [
								'label'           => esc_html__( 'Column Min Width', 'fusion-builder' ),
								'description'     => esc_html__( 'Set the minimum width for each column, this allows your columns to gracefully break into the selected size as the screen width narrows. Leave this option empty if you wish to keep the same amount of columns from desktop to mobile.', 'fusion-builder' ),
								'id'              => 'text_column_min_width',
								'default'         => '100px',
								'type'            => 'dimension',
								'transport'       => 'postMessage',
								'soft_dependency' => true,
							],
							'text_column_spacing'   => [
								'label'           => esc_html__( 'Column Spacing', 'fusion-builder' ),
								'description'     => esc_html__( 'Controls the column spacing between one column to the next.', 'fusion-builder' ),
								'id'              => 'text_column_spacing',
								'default'         => '2em',
								'type'            => 'dimension',
								'transport'       => 'postMessage',
								'soft_dependency' => true,
							],
							'text_rule_style'       => [
								'label'           => esc_html__( 'Rule Style', 'fusion-builder' ),
								'description'     => esc_html__( 'Select the style of the vertical line between columns. Some of the styles depend on the rule size and color.', 'fusion-builder' ),
								'id'              => 'text_rule_style',
								'default'         => 'none',
								'transport'       => 'postMessage',
								'type'            => 'select',
								'choices'         => [
									'none'   => esc_html__( 'None', 'fusion-builder' ),
									'solid'  => esc_html__( 'Solid', 'fusion-builder' ),
									'dashed' => esc_html__( 'Dashed', 'fusion-builder' ),
									'dotted' => esc_html__( 'Dotted', 'fusion-builder' ),
									'double' => esc_html__( 'Double', 'fusion-builder' ),
									'groove' => esc_html__( 'Groove', 'fusion-builder' ),
									'ridge'  => esc_html__( 'Ridge', 'fusion-builder' ),
								],
								'soft_dependency' => true,
							],
							'text_rule_size'        => [
								'label'           => esc_html__( 'Rule Size', 'fusion-builder' ),
								'description'     => esc_attr__( 'Sets the size of the vertical line between columns. The rule is rendered as "below" spacing and columns, so it can span over the gap between columns if it is larger than the column spacing amount.', 'fusion-builder' ),
								'id'              => 'text_rule_size',
								'default'         => '1',
								'type'            => 'slider',
								'transport'       => 'postMessage',
								'choices'         => [
									'min'  => '1',
									'max'  => '50',
									'step' => '1',
								],
								'soft_dependency' => true,
							],
							'text_rule_color'       => [
								'label'           => esc_html__( 'Rule Color', 'fusion-builder' ),
								'description'     => esc_html__( 'Controls the color of the vertical line between columns.', 'fusion-builder' ),
								'id'              => 'text_rule_color',
								'default'         => $fusion_settings->get( 'sep_color' ),
								'type'            => 'color-alpha',
								'transport'       => 'postMessage',
								'soft_dependency' => true,
							],
						],
					],
				];
			}
		}
	}

	new FusionSC_FusionText();

}

/**
 * Map shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_element_text() {
	$fusion_settings = fusion_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_FusionText',
			[
				'name'            => esc_attr__( 'Text Block', 'fusion-builder' ),
				'shortcode'       => 'fusion_text',
				'icon'            => 'fusiona-font',
				'preview'         => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-text-preview.php',
				'preview_id'      => 'fusion-builder-block-module-text-preview-template',
				'allow_generator' => true,
				'inline_editor'   => true,
				'help_url'        => 'https://theme-fusion.com/documentation/fusion-builder/elements/text-block-element/',
				'params'          => [
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Number Of Inline Columns', 'fusion-builder' ),
						'description' => __( 'Set the number of columns the text should be broken into.<br />IMPORTANT: This feature is designed to be used for running text, images, dropcaps and other inline content. While some block elements will work, their usage is not recommended and others can easily break the layout.', 'fusion-builder' ),
						'param_name'  => 'columns',
						'default'     => $fusion_settings->get( 'text_columns' ),
						'min'         => '1',
						'max'         => '6',
						'step'        => '1',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Column Min Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the minimum width for each column, this allows your columns to gracefully break into the selected size as the screen width narrows. Leave this option empty if you wish to keep the same amount of columns from desktop to mobile. Enter value including any valid CSS unit, ex: 200px.', 'fusion-builder' ),
						'param_name'  => 'column_min_width',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'columns',
								'value'    => '1',
								'operator' => '>',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Column Spacing', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the column spacing between one column to the next. Enter value including any valid CSS unit besides % which does not work for inline columns, ex: 2em.', 'fusion-builder' ),
						'param_name'  => 'column_spacing',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'columns',
								'value'    => '1',
								'operator' => '>',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Rule Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the style of the vertical line between columns. Some of the styles depend on the rule size and color.', 'fusion-builder' ),
						'param_name'  => 'rule_style',
						'value'       => [
							'default' => esc_html__( 'Default', 'fusion-builder' ),
							'none'    => esc_attr__( 'None', 'fusion-builder' ),
							'solid'   => esc_attr__( 'Solid', 'fusion-builder' ),
							'dashed'  => esc_attr__( 'Dashed', 'fusion-builder' ),
							'dotted'  => esc_attr__( 'Dotted', 'fusion-builder' ),
							'double'  => esc_attr__( 'Double', 'fusion-builder' ),
							'groove'  => esc_attr__( 'Groove', 'fusion-builder' ),
							'ridge'   => esc_attr__( 'Ridge', 'fusion-builder' ),
						],
						'default'     => 'default',
						'dependency'  => [
							[
								'element'  => 'columns',
								'value'    => '1',
								'operator' => '>',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Rule Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Sets the size of the vertical line between columns. The rule is rendered as "below" spacing and columns, so it can span over the gap between columns if it is larger than the column spacing amount. In pixels.', 'fusion-builder' ),
						'param_name'  => 'rule_size',
						'default'     => $fusion_settings->get( 'text_rule_size' ),
						'min'         => '1',
						'max'         => '50',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'columns',
								'value'    => '1',
								'operator' => '>',
							],
							[
								'element'  => 'rule_style',
								'value'    => 'none',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Rule Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the vertical line between columns.', 'fusion-builder' ),
						'param_name'  => 'rule_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'text_rule_color' ),
						'dependency'  => [
							[
								'element'  => 'columns',
								'value'    => '1',
								'operator' => '>',
							],
							[
								'element'  => 'rule_style',
								'value'    => 'none',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'tinymce',
						'heading'      => esc_attr__( 'Content', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter some content for this text block.', 'fusion-builder' ),
						'param_name'   => 'element_content',
						'value'        => esc_attr__( 'Your Content Goes Here', 'fusion-builder' ),
						'placeholder'  => true,
						'dynamic_data' => true,
					],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-text',
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
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_text' );
