<?php
/**
 * Elements settings functions.
 *
 * @package fusion-builder
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Element settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_elements( $sections ) {

	$option_name = Fusion_Settings::get_option_name();
	$settings    = get_option( $option_name, [] );

	$sections['shortcode_styling'] = [
		'label'    => esc_html__( 'Fusion Builder Elements', 'fusion-builder' ),
		'id'       => 'fusion_builder_elements',
		'is_panel' => true,
		'priority' => 14,
		'icon'     => 'el-icon-check',
		'fields'   => [
			'animations_shortcode_section' => [
				'label'       => esc_html__( 'Animations', 'fusion-builder' ),
				'description' => '',
				'id'          => 'animations_shortcode_section',
				'default'     => '',
				'type'        => 'accordion',
				'icon'        => 'fusiona-play-circle',
				'fields'      => [
					'animation_offset'      => [
						'label'       => esc_html__( 'Animation Offset', 'fusion-builder' ),
						'description' => esc_html__( 'Controls when the animation should start.', 'fusion-builder' ),
						'id'          => 'animation_offset',
						'default'     => 'top-into-view',
						'type'        => 'select',
						'option_name' => $option_name,
						'choices'     => [
							'top-into-view'   => esc_html__( 'Top of element hits bottom of viewport', 'fusion-builder' ),
							'top-mid-of-view' => esc_html__( 'Top of element hits middle of viewport', 'fusion-builder' ),
							'bottom-in-view'  => esc_html__( 'Bottom of element enters viewport', 'fusion-builder' ),
						],
						'transport'   => 'postMessage',
					],
					'status_css_animations' => [
						'label'       => esc_html__( 'Element Appearance Animations', 'fusion-builder' ),
						'description' => esc_html__( 'Select to enable animations for elements appearance.', 'fusion-builder' ),
						'id'          => 'status_css_animations',
						'default'     => 'desktop',
						'type'        => 'radio-buttonset',
						'choices'     => [
							'desktop_and_mobile' => esc_html__( 'Desktop & Mobile', 'fusion-builder' ),
							'desktop'            => esc_html__( 'Desktop Only', 'fusion-builder' ),
							'off'                => esc_html__( 'Off', 'fusion-builder' ),
						],
						'output'      => [

							// Change the fusionAnimationsVars.status_css_animations var.
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionAnimationsVars',
										'id'        => 'status_css_animations',
										'trigger'   => [ 'CSSAnimations' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
				],
			],
			'carousel_shortcode_section'   => [
				'label'       => esc_html__( 'Carousel', 'fusion-builder' ),
				'description' => '',
				'id'          => 'carousel_shortcode_section',
				'type'        => 'accordion',
				'icon'        => 'fusiona-images',
				'fields'      => [
					'carousel_nav_color'   => [
						'label'       => esc_html__( 'Carousel Navigation Box Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the navigation box for carousel sliders.', 'fusion-builder' ),
						'id'          => 'carousel_nav_color',
						'default'     => 'rgba(29,36,45,0.7)',
						'type'        => 'color-alpha',
						'option_name' => $option_name,
						'css_vars'    => [
							[
								'name'     => '--carousel_nav_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'carousel_hover_color' => [
						'label'       => esc_html__( 'Carousel Hover Navigation Box Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the hover navigation box for carousel sliders.', 'fusion-builder' ),
						'id'          => 'carousel_hover_color',
						'default'     => 'rgba(29,36,45,0.8)',
						'type'        => 'color-alpha',
						'option_name' => $option_name,
						'css_vars'    => [
							[
								'name'     => '--carousel_hover_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'carousel_speed'       => [
						'label'       => esc_html__( 'Carousel Speed', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the speed of all carousel elements. ex: 1000 = 1 second.', 'fusion-builder' ),
						'id'          => 'carousel_speed',
						'default'     => '2500',
						'type'        => 'slider',
						'option_name' => $option_name,
						'choices'     => [
							'min'  => '1000',
							'max'  => '20000',
							'step' => '250',
						],
						'output'      => [
							// This is for the fusionCarouselVars.carousel_speed var.
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionCarouselVars',
										'id'        => 'carousel_speed',
										'trigger'   => [ 'fusion-reinit-carousels' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
				],
			],
			'visibility_shortcode_section' => [
				'label'       => esc_html__( 'Visibility Size Options', 'fusion-builder' ),
				'id'          => 'visibility_shortcode_section',
				'description' => '',
				'type'        => 'accordion',
				'icon'        => 'fusiona-mobile',
				'fields'      => [
					'visibility_small'  => [
						'label'       => esc_html__( 'Small Screen', 'fusion-builder' ),
						'description' => esc_html__( 'Controls when the small screen visibility should take effect.', 'fusion-builder' ),
						'id'          => 'visibility_small',
						'default'     => '640',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '0',
							'step' => '1',
							'max'  => '2000',
						],
						'option_name' => $option_name,
						'output'      => [
							// Runs fusionRecalcVisibilityMediaQueries().
							[
								'element'           => 'helperElement',
								'property'          => 'bottom',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'dummy',
										'id'        => 'dummy',
										'trigger'   => [ 'fusionRecalcVisibilityMediaQueries' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'visibility_medium' => [
						'label'       => esc_html__( 'Medium Screen', 'fusion-builder' ),
						'description' => esc_html__( 'Controls when the medium screen visibility should take effect.', 'fusion-builder' ),
						'id'          => 'visibility_medium',
						'default'     => '1024',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '0',
							'step' => '1',
							'max'  => '2000',
						],
						'option_name' => $option_name,
						'output'      => [
							// Runs fusionRecalcVisibilityMediaQueries().
							[
								'element'           => 'helperElement',
								'property'          => 'bottom',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'dummy',
										'id'        => 'dummy',
										'trigger'   => [ 'fusionRecalcVisibilityMediaQueries' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'visibility_large'  => [
						'label'       => esc_html__( 'Large Screen', 'fusion-builder' ),
						'description' => esc_html__( 'Any screen larger than that which is defined as the medium screen will be counted as a large screen.', 'fusion-builder' ),
						'id'          => 'visibility_large',
						'full_width'  => false,
						'type'        => 'raw',
						'content'     => '<div id="fusion-visibility-large">' . ( ( isset( $settings['visibility_medium'] ) && ! empty( $settings['visibility_medium'] ) ) ? '> <span>' . $settings['visibility_medium'] . '</span>' : '> <span>1200</span>' ) . '</div>',
						'option_name' => $option_name,
					],
				],
			],
		],
	];

	return $sections;

}
