<?php
/**
 * Fusion Builder Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion Builder
 * @subpackage Core
 * @since      2.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Mobile settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_responsive( $sections ) {

	$settings = get_option( Fusion_settings::get_option_name(), [] );

	$sections['mobile'] = [
		'label'    => esc_html__( 'Responsive', 'fusion-builder' ),
		'id'       => 'responsive',
		'priority' => 2,
		'icon'     => 'el-icon-resize-horizontal',
		'alt_icon' => 'fusiona-mobile',
		'fields'   => [
			'responsive'              => [
				'label'       => esc_html__( 'Responsive Design', 'fusion-builder' ),
				'description' => esc_html__( 'Turn on to use the responsive design features. If set to off, the fixed layout is used.', 'fusion-builder' ),
				'id'          => 'responsive',
				'default'     => '1',
				'type'        => 'switch',
				'choices'     => [
					'on'  => esc_html__( 'On', 'fusion-builder' ),
					'off' => esc_html__( 'Off', 'fusion-builder' ),
				],
			],
			'grid_main_break_point'   => [
				'label'       => esc_html__( 'Grid Responsive Breakpoint', 'fusion-builder' ),
				'description' => esc_html__( 'Controls when grid layouts (blog/portfolio) start to break into smaller columns. Further breakpoints are auto calculated.', 'fusion-builder' ),
				'id'          => 'grid_main_break_point',
				'default'     => '1000',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '360',
					'max'  => '2000',
					'step' => '1',
					'edit' => 'yes',
				],
				'required'    => [
					[
						'setting'  => 'responsive',
						'operator' => '==',
						'value'    => '1',
					],
				],
				'output'      => [
					// runs fusionRecalcAllMediaQueries().
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'dummy',
								'id'        => 'dummy',
								'trigger'   => [ 'fusionRecalcAllMediaQueries' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
			],
			'side_header_break_point' => [
				'label'       => esc_html__( 'Header Responsive Breakpoint', 'fusion-builder' ),
				'description' => esc_html__( 'Controls when the desktop header changes to the mobile header.', 'fusion-builder' ),
				'id'          => 'side_header_break_point',
				'default'     => '800',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '0',
					'max'  => '2000',
					'step' => '1',
					'edit' => 'yes',
				],
				'required'    => [
					[
						'setting'  => 'responsive',
						'operator' => '==',
						'value'    => '1',
					],
				],
				'output'      => [
					// runs fusionRecalcAllMediaQueries().
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'dummy',
								'id'        => 'dummy',
								'trigger'   => [ 'fusionRecalcAllMediaQueries' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
			],
			'content_break_point'     => [
				'label'       => esc_html__( 'Site Content Responsive Breakpoint', 'fusion-builder' ),
				'description' => esc_html__( 'Controls when the site content area changes to the mobile layout. This includes all content below the header including the footer.', 'fusion-builder' ),
				'id'          => 'content_break_point',
				'default'     => '800',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '0',
					'max'  => '2000',
					'step' => '1',
					'edit' => 'yes',
				],
				'required'    => [
					[
						'setting'  => 'responsive',
						'operator' => '==',
						'value'    => '1',
					],
				],
				'output'      => [
					// runs fusionRecalcAllMediaQueries().
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'dummy',
								'id'        => 'dummy',
								'trigger'   => [ 'fusionRecalcAllMediaQueries' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
			],
			'typography_sensitivity'  => [
				'label'       => esc_html__( 'Responsive Typography Sensitivity', 'fusion-builder' ),
				'description' => esc_html__( 'Set to 0 to disable responsive typography. Increase the value for a greater effect.', 'fusion-builder' ),
				'id'          => 'typography_sensitivity',
				'default'     => '0.6',
				'type'        => 'slider',
				'required'    => [
					[
						'setting'  => 'responsive',
						'operator' => '==',
						'value'    => '1',
					],
				],
				'choices'     => [
					'min'  => '0',
					'max'  => '1',
					'step' => '.01',
				],
				'output'      => [
					// This is for the fusionTypographyVars.typography_sensitivity var.
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'fusionTypographyVars',
								'id'        => 'typography_sensitivity',
								'trigger'   => [ 'fusionInitTypography' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
				'css_vars'    => [
					[
						'name' => '--typography_sensitivity',
					],
				],
			],
			'typography_factor'       => [
				'label'       => esc_html__( 'Minimum Font Size Factor', 'fusion-builder' ),
				'description' => esc_html__( 'Minimum font factor is used to determine the minimum distance between headings and body font by a multiplying value.', 'fusion-builder' ),
				'id'          => 'typography_factor',
				'default'     => '1.5',
				'type'        => 'slider',
				'required'    => [
					[
						'setting'  => 'responsive',
						'operator' => '==',
						'value'    => '1',
					],
				],
				'choices'     => [
					'min'  => '0',
					'max'  => '4',
					'step' => '.01',
				],
				'output'      => [
					// This is for the fusionTypographyVars.typography_factor var.
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'fusionTypographyVars',
								'id'        => 'typography_factor',
								'trigger'   => [ 'fusionInitTypography' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
				'css_vars'    => [
					[
						'name' => '--typography_factor',
					],
				],
			],
		],
	];

	return $sections;

}
