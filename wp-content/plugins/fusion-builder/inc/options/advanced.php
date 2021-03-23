<?php
/**
 * Fusion Builder Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion Builder
 * @subpackage Core
 * @since      6.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Advanced settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_advanced( $sections ) {
	$sections['advanced'] = [
		'label'    => esc_html__( 'Advanced', 'fusion-builder' ),
		'id'       => 'heading_advanced',
		'is_panel' => true,
		'priority' => 25,
		'icon'     => 'el-icon-puzzle',
		'alt_icon' => 'fusiona-dashboard',
		'fields'   => [
			'tracking_head_body_section' => [
				'label'  => esc_html__( 'Code Fields (Tracking etc.)', 'fusion-builder' ),
				'id'     => 'tracking_head_body_section',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'google_analytics' => [
						'label'       => esc_html__( 'Tracking Code', 'fusion-builder' ),
						'description' => esc_html__( 'Paste your tracking code here. This will be added into the header template of your theme. Place code inside &lt;script&gt; tags.', 'fusion-builder' ),
						'id'          => 'google_analytics',
						'default'     => '',
						'type'        => 'code',
						'choices'     => [
							'language' => 'html',
							'height'   => 300,
							'theme'    => 'chrome',
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'space_head'       => [
						'label'       => esc_html__( 'Space before &lt;/head&gt;', 'fusion-builder' ),
						'description' => esc_html__( 'Only accepts javascript code wrapped with &lt;script&gt; tags and HTML markup that is valid inside the &lt;/head&gt; tag.', 'fusion-builder' ),
						'id'          => 'space_head',
						'default'     => '',
						'type'        => 'code',
						'choices'     => [
							'language' => 'html',
							'height'   => 350,
							'theme'    => 'chrome',
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'space_body'       => [
						'label'       => esc_html__( 'Space before &lt;/body&gt;', 'fusion-builder' ),
						'description' => esc_html__( 'Only accepts javascript code, wrapped with &lt;script&gt; tags and valid HTML markup inside the &lt;/body&gt; tag.', 'fusion-builder' ),
						'id'          => 'space_body',
						'default'     => '',
						'type'        => 'code',
						'choices'     => [
							'language' => 'html',
							'height'   => 350,
							'theme'    => 'chrome',
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
				],
			],
			'global_features_section'    => [
				'label'  => esc_html__( 'Global Features', 'fusion-builder' ),
				'id'     => 'global_features_section',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'disable_code_block_encoding'     => [
						'label'       => esc_html__( 'Code Block Encoding', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to enable encoding in the Fusion Builder code block and syntax highlighting elements.', 'fusion-builder' ),
						'id'          => 'disable_code_block_encoding',
						'default'     => '1',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'status_yt'                       => [
						'label'       => esc_html__( 'Youtube API Scripts', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to enable Youtube API scripts.', 'fusion-builder' ),
						'id'          => 'status_yt',
						'default'     => '1',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
						'output'      => [
							// This is for the fusionVideoBgVars.status_yt var.
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionVideoBgVars',
										'id'        => 'status_yt',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionVideoGeneralVars',
										'id'        => 'status_yt',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionFlexSliderVars',
										'id'        => 'status_yt',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionBlogVars',
										'id'        => 'status_yt',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'status_vimeo'                    => [
						'label'       => esc_html__( 'Vimeo API Scripts', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to enable Vimeo API scripts.', 'fusion-builder' ),
						'id'          => 'status_vimeo',
						'default'     => '1',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
						'output'      => [
							// This is for the fusionVideoBgVars.status_vimeo var.
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionVideoBgVars',
										'id'        => 'status_vimeo',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionVideoGeneralVars',
										'id'        => 'status_vimeo',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'fusionFlexSliderVars',
										'id'        => 'status_vimeo',
										'trigger'   => [ 'load' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'status_gmap'                     => [
						'label'       => esc_html__( 'Google Map Scripts', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to enable google map.', 'fusion-builder' ),
						'id'          => 'status_gmap',
						'default'     => '1',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'status_fontawesome'              => [
						'label'       => esc_html__( 'Font Awesome', 'fusion-builder' ),
						'description' => esc_html__( 'Choose which Font Awesome icon subsets you want to load. Note that Light subset can only be used if Font Awesome Pro is enabled.', 'fusion-builder' ),
						'id'          => 'status_fontawesome',
						'default'     => [ 'fab', 'far', 'fas' ],
						'type'        => 'select',
						'multi'       => true,
						'choices'     => [
							'fab' => esc_html__( 'Brands', 'fusion-builder' ),
							'far' => esc_html__( 'Regular', 'fusion-builder' ),
							'fas' => esc_html__( 'Solid', 'fusion-builder' ),
							'fal' => esc_html__( 'Light', 'fusion-builder' ),
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'fontawesome_v4_compatibility'    => [
						'label'       => esc_html__( 'Font Awesome v4 Compatibility', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to enable support for Font Awesome 4 icon code format.', 'fusion-builder' ),
						'id'          => 'fontawesome_v4_compatibility',
						'default'     => '0',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'status_fontawesome_pro'          => [
						'label'       => esc_html__( 'Font Awesome Pro', 'fusion-builder' ),
						/* translators: %1$s = license text & link. %2$s: whitelist text & link. */
						'description' => sprintf( esc_html__( 'Font Awesome Pro %1$s is required and you need to %2$s your domain.', 'fusion-builder' ), '<a href="https://fontawesome.com/buy/standard" target="_blank" rel="noopener noreferrer">license</a>', '<a href="https://fontawesome.com/account/domains" target="_blank" rel="noopener noreferrer">whitelist</a>' ),
						'id'          => 'status_fontawesome_pro',
						'default'     => '0',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'disable_date_rich_snippet_pages' => [
						'label'       => esc_html__( 'Rich Snippets', 'Avada' ),
						'description' => esc_html__( 'Turn on to enable rich snippets data site wide. If set to "On", you can also control individual items below. If set to "Off" all items will be disabled.', 'Avada' ),
						'id'          => 'disable_date_rich_snippet_pages',
						'default'     => '1',
						'type'        => 'switch',
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'disable_rich_snippet_title'      => [
						'label'       => esc_html__( 'Rich Snippets Title', 'Avada' ),
						'description' => esc_html__( 'Turn on to enable title rich snippet data site wide.', 'Avada' ),
						'id'          => 'disable_rich_snippet_title',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'disable_date_rich_snippet_pages',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'disable_rich_snippet_author'     => [
						'label'       => esc_html__( 'Rich Snippets Author Info', 'Avada' ),
						'description' => esc_html__( 'Turn on to enable author rich snippet data site wide.', 'Avada' ),
						'id'          => 'disable_rich_snippet_author',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'disable_date_rich_snippet_pages',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
					'disable_rich_snippet_date'       => [
						'label'       => esc_html__( 'Rich Snippets Last Update Date', 'Avada' ),
						'description' => esc_html__( 'Turn on to enable udate date rich snippet data site wide.', 'Avada' ),
						'id'          => 'disable_rich_snippet_date',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'disable_date_rich_snippet_pages',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// No need to refresh the page.
						'transport'   => 'postMessage',
					],
				],
			],
		],
	];

	return $sections;

}
