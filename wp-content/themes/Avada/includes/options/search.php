<?php
/**
 * Avada Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Search.
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_options_section_search( $sections ) {

	// Check if we have a global content override.
	$has_global_content = false;
	if ( class_exists( 'Fusion_Template_Builder' ) ) {
		$default_layout     = Fusion_Template_Builder::get_default_layout();
		$has_global_content = isset( $default_layout['data']['template_terms'] ) && isset( $default_layout['data']['template_terms']['content'] ) && $default_layout['data']['template_terms']['content'];
	}

	$sections['search'] = [
		'label'    => esc_html__( 'Search', 'Avada' ),
		'id'       => 'heading_search',
		'priority' => 23,
		'icon'     => 'el-icon-search',
		'alt_icon' => 'fusiona-search',
		'fields'   => [
			'search_form_options_section' => [
				'label'       => esc_html__( 'Search Form', 'Avada' ),
				'description' => '',
				'id'          => 'search_form_options_section',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'search_filter_results'              => [
						'label'           => esc_html__( 'Limit Search Results Post Types', 'Avada' ),
						'description'     => esc_html__( 'Turn on to limit the search results to specific post types you can choose.', 'Avada' ),
						'id'              => 'search_filter_results',
						'default'         => '0',
						'type'            => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_search',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'search_content'                     => [
						'label'           => esc_html__( 'Search Results Content', 'Avada' ),
						'description'     => esc_html__( 'Controls the type of content that displays in search results.', 'Avada' ),
						'id'              => 'search_content',
						'default'         => [ 'post', 'page', 'avada_portfolio', 'avada_faq' ],
						'type'            => 'select',
						'multi'           => true,
						'choices'         => [
							'post'            => esc_html__( 'Posts', 'Avada' ),
							'page'            => esc_html__( 'Pages', 'Avada' ),
							'avada_portfolio' => esc_html__( 'Portfolio Items', 'Avada' ),
							'avada_faq'       => esc_html__( 'FAQ Items', 'Avada' ),
							'product'         => esc_html__( 'WooCommerce Products', 'Avada' ),
							'tribe_events'    => esc_html__( 'Events Calendar Posts', 'Avada' ),
						],
						'required'        => [
							[
								'setting'  => 'search_filter_results',
								'operator' => '=',
								'value'    => '1',
							],
						],
						'update_callback' => [
							[
								'condition' => 'is_search',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'search_limit_to_post_titles'        => [
						'label'           => esc_html__( 'Limit Search to Post Titles', 'Avada' ),
						'description'     => esc_html__( 'Turn on to limit the search to post titles only.', 'Avada' ),
						'id'              => 'search_limit_to_post_titles',
						'default'         => '0',
						'type'            => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_search',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'search_form_design'                 => [
						'label'       => esc_html__( 'Search Form Design', 'Avada' ),
						'description' => esc_html__( 'Controls the design of the search forms.', 'Avada' ),
						'id'          => 'search_form_design',
						'default'     => 'clean',
						'type'        => 'radio-buttonset',
						'choices'     => [
							'classic' => esc_html__( 'Classic', 'Avada' ),
							'clean'   => esc_html__( 'Clean', 'Avada' ),
						],
						'output'      => [
							// Change classes in <body>.
							[
								'element'       => 'body',
								'function'      => 'attr',
								'attr'          => 'class',
								'value_pattern' => 'fusion-search-form-$',
								'remove_attrs'  => [ 'fusion-search-form-classic', 'fusion-search-form-clean' ],
							],
						],
					],
					'live_search'                        => [
						'label'           => esc_html__( 'Enable Live Search', 'Avada' ),
						'description'     => esc_html__( 'Turn on to enable live search results on menu search field and other fitting search forms.', 'Avada' ),
						'id'              => 'live_search',
						'default'         => '0',
						'type'            => 'switch',
						// Partial refresh for the searchform.
						'partial_refresh' => [
							'searchform_live_search' => [
								'selector'              => '.searchform.fusion-search-form',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'searchform' ],
								'success_trigger_event' => 'avadaLiveSearch',
							],
						],
						// This is for the avadaLiveSearchVars.live_search var.
						[
							'element'           => 'helperElement',
							'property'          => 'bottom',
							'js_callback'       => [
								'fusionGlobalScriptSet',
								[
									'globalVar' => 'avadaLiveSearchVars',
									'id'        => 'live_search',
									'trigger'   => [ 'avadaLiveSearch' ],
								],
							],
							'sanitize_callback' => '__return_empty_string',
						],
					],
					'live_search_min_char_count'         => [
						'label'           => esc_html__( 'Live Search Minimal Character Count', 'Avada' ),
						'description'     => esc_html__( 'Set the minimal character count to trigger the live search.', 'Avada' ),
						'id'              => 'live_search_min_char_count',
						'default'         => '4',
						'type'            => 'slider',
						'choices'         => [
							'min'  => '1',
							'max'  => '20',
							'step' => '1',
						],
						'required'        => [
							[
								'setting'  => 'live_search',
								'operator' => '=',
								'value'    => '1',
							],
						],
						// Partial refresh for the searchform.
						'partial_refresh' => [
							'searchform_live_search_min_char_count' => [
								'selector'              => '.searchform.fusion-search-form',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'searchform' ],
								'success_trigger_event' => 'avadaLiveSearch',
							],
						],
						// JS var: avadaLiveSearchVars.min_char_count.
						[
							'element'           => 'helperElement',
							'property'          => 'bottom',
							'js_callback'       => [
								'fusionGlobalScriptSet',
								[
									'globalVar' => 'avadaLiveSearchVars',
									'id'        => 'min_char_count',
									'trigger'   => [ 'avadaLiveSearch' ],
								],
							],
							'sanitize_callback' => '__return_empty_string',
						],
					],
					'live_search_results_per_page'       => [
						'label'       => esc_html__( 'Live Search Number of Posts', 'Avada' ),
						'description' => esc_html__( 'Controls the number of posts that should be displayed as search result suggestions.', 'Avada' ),
						'id'          => 'live_search_results_per_page',
						'default'     => '100',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '10',
							'max'  => '500',
							'step' => '10',
						],
						'required'    => [
							[
								'setting'  => 'live_search',
								'operator' => '=',
								'value'    => '1',
							],
						],
						'output'      => [
							// JS var: avadaLiveSearchVars.per_page.
							[
								'element'           => 'helperElement',
								'property'          => 'bottom',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'avadaLiveSearchVars',
										'id'        => 'per_page',
										'trigger'   => [ 'avadaLiveSearch' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'live_search_results_height'         => [
						'label'       => esc_html__( 'Live Search Results Container Height', 'Avada' ),
						'description' => esc_html__( 'Controls the height of the container in which the search results will be listed.', 'Avada' ),
						'id'          => 'live_search_results_height',
						'default'     => '250',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '100',
							'max'  => '800',
							'step' => '5',
						],
						'required'    => [
							[
								'setting'  => 'live_search',
								'operator' => '=',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'          => '--live_search_results_height',
								'element'       => '.fusion-search-results',
								'value_pattern' => '$px',
							],
						],
					],
					'live_search_display_featured_image' => [
						'label'       => esc_html__( 'Live Search Display Featured Image', 'Avada' ),
						'description' => esc_html__( 'Turn on to display the featured image of each live search result.', 'Avada' ),
						'id'          => 'live_search_display_featured_image',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'live_search',
								'operator' => '=',
								'value'    => '1',
							],
						],
						'output'      => [
							// JS var: avadaLiveSearchVars.show_feat_img.
							[
								'element'           => 'helperElement',
								'property'          => 'bottom',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'avadaLiveSearchVars',
										'id'        => 'show_feat_img',
										'trigger'   => [ 'avadaLiveSearch' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'live_search_display_post_type'      => [
						'label'       => esc_html__( 'Live Search Display Post Type', 'Avada' ),
						'description' => esc_html__( 'Turn on to display the post type of each live search result.', 'Avada' ),
						'id'          => 'live_search_display_post_type',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'live_search',
								'operator' => '=',
								'value'    => '1',
							],
						],
						'output'      => [
							// JS var: avadaLiveSearchVars.display_post_type.
							[
								'element'           => 'helperElement',
								'property'          => 'bottom',
								'js_callback'       => [
									'fusionGlobalScriptSet',
									[
										'globalVar' => 'avadaLiveSearchVars',
										'id'        => 'display_post_type',
										'trigger'   => [ 'avadaLiveSearch' ],
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
				],
			],
		],
	];

	$sections['search']['fields']['search_page_options_section'] = ( $has_global_content ) ? [
		'label'       => esc_html__( 'Search Page', 'Avada' ),
		'description' => '',
		'id'          => 'search_page_options_section',
		'icon'        => true,
		'type'        => 'sub-section',
		'fields'      => [
			'search_page_options_template_notice' => [
				'id'          => 'search_page_options_template_notice',
				'label'       => '',
				'description' => sprintf(
					/* translators: 1: Content|Footer|Page Title Bar. 2: URL. */
					'<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> The options on this tab are not available because a global %1$s override is currently used. To edit your global layout please visit <a href="%2$s" target="_blank">this page</a>.', 'Avada' ) . '</div>',
					Fusion_Template_Builder::get_instance()->get_template_terms()['content']['label'],
					admin_url( 'admin.php?page=fusion-layouts' )
				),
				'type'        => 'custom',
			],
		],
	] : [
		'label'       => esc_html__( 'Search Page', 'Avada' ),
		'description' => '',
		'id'          => 'search_page_options_section',
		'icon'        => true,
		'type'        => 'sub-section',
		'fields'      => [
			'search_layout'              => [
				'label'           => esc_html__( 'Search Results Layout', 'Avada' ),
				'description'     => esc_html__( 'Controls the layout for the search results page.', 'Avada' ),
				'id'              => 'search_layout',
				'default'         => 'grid',
				'type'            => 'select',
				'choices'         => [
					'large'            => esc_html__( 'Large', 'Avada' ),
					'medium'           => esc_html__( 'Medium', 'Avada' ),
					'large alternate'  => esc_html__( 'Large Alternate', 'Avada' ),
					'medium alternate' => esc_html__( 'Medium Alternate', 'Avada' ),
					'grid'             => esc_html__( 'Grid', 'Avada' ),
					'timeline'         => esc_html__( 'Timeline', 'Avada' ),
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_results_per_page'    => [
				'label'           => esc_html__( 'Number of Search Results Per Page', 'Avada' ),
				'description'     => esc_html__( 'Controls the number of search results per page.', 'Avada' ),
				'id'              => 'search_results_per_page',
				'default'         => '10',
				'type'            => 'slider',
				'choices'         => [
					'min'  => '1',
					'max'  => '100',
					'step' => '1',
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_pagination_type'     => [
				'label'           => esc_html__( 'Search Pagination Type', 'Avada' ),
				'description'     => esc_html__( 'Controls the pagination type for the search results page.', 'Avada' ),
				'id'              => 'search_pagination_type',
				'default'         => 'pagination',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'pagination'       => esc_html__( 'Pagination', 'Avada' ),
					'infinite_scroll'  => esc_html__( 'Infinite Scroll', 'Avada' ),
					'load_more_button' => esc_html__( 'Load More Button', 'Avada' ),
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_grid_columns'        => [
				'label'           => esc_html__( 'Number of Columns', 'Avada' ),
				'description'     => __( 'Controls the number of columns for grid layouts.', 'Avada' ),
				'id'              => 'search_grid_columns',
				'default'         => 3,
				'type'            => 'slider',
				'class'           => 'fusion-or-gutter',
				'choices'         => [
					'min'  => 1,
					'max'  => 6,
					'step' => 1,
				],
				'required'        => [
					[
						'setting'  => 'search_layout',
						'operator' => '=',
						'value'    => 'grid',
					],
					[
						'setting'  => 'search_layout',
						'operator' => '=',
						'value'    => 'masonry',
					],
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_grid_column_spacing' => [
				'label'       => esc_html__( 'Column Spacing', 'Avada' ),
				'description' => esc_html__( 'Controls the column spacing for search results.', 'Avada' ),
				'id'          => 'search_grid_column_spacing',
				'default'     => '40',
				'type'        => 'slider',
				'class'       => 'fusion-or-gutter',
				'choices'     => [
					'min'  => '0',
					'step' => '1',
					'max'  => '300',
					'edit' => 'yes',
				],
				'required'    => [
					[
						'setting'  => 'search_layout',
						'operator' => '=',
						'value'    => 'grid',
					],
					[
						'setting'  => 'search_layout',
						'operator' => '=',
						'value'    => 'masonry',
					],
				],
				'css_vars'    => [
					[
						'name'          => '--search_grid_column_spacing',
						'element'       => '.fusion-blog-layout-grid',
						'value_pattern' => '$px',
					],
				],
			],
			'search_content_length'      => [
				'label'           => esc_html__( 'Search Content Display', 'Avada' ),
				'description'     => esc_html__( 'Controls if the search results content displays as an excerpt or full content or is completely disabled.', 'Avada' ),
				'id'              => 'search_content_length',
				'default'         => 'excerpt',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'excerpt'      => esc_html__( 'Excerpt', 'Avada' ),
					'full_content' => esc_html__( 'Full Content', 'Avada' ),
					'no_text'      => esc_html__( 'No Text', 'Avada' ),
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_excerpt_length'      => [
				'label'           => esc_html__( 'Search Excerpt Length', 'Avada' ),
				'description'     => esc_html__( 'Controls the number of words (or characters) in the search results excerpts.', 'Avada' ),
				'id'              => 'search_excerpt_length',
				'default'         => '10',
				'type'            => 'slider',
				'choices'         => [
					'min'  => '0',
					'max'  => '500',
					'step' => '1',
				],
				'required'        => [
					[
						'setting'  => 'search_content_length',
						'operator' => '==',
						'value'    => 'excerpt',
					],
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_strip_html_excerpt'  => [
				'label'           => esc_html__( 'Search Strip HTML from Excerpt', 'Avada' ),
				'description'     => esc_html__( 'Turn on to strip HTML content from the excerpt for the search results page.', 'Avada' ),
				'id'              => 'search_strip_html_excerpt',
				'default'         => '1',
				'type'            => 'switch',
				'required'        => [
					[
						'setting'  => 'search_content_length',
						'operator' => '==',
						'value'    => 'excerpt',
					],
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_featured_images'     => [
				'label'           => esc_html__( 'Featured Images for Search Results', 'Avada' ),
				'description'     => esc_html__( 'Turn on to display featured images for search results.', 'Avada' ),
				'id'              => 'search_featured_images',
				'default'         => '1',
				'type'            => 'switch',
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_meta'                => [
				'label'           => esc_html__( 'Search Results Meta', 'Avada' ),
				'description'     => esc_html__( 'Select the post meta data you want to be displayed in the individual search results.', 'Avada' ),
				'id'              => 'search_meta',
				'default'         => [ 'author', 'date', 'categories', 'comments', 'read_more' ],
				'type'            => 'select',
				'multi'           => true,
				'choices'         => [
					'author'     => esc_html__( 'Author', 'Avada' ),
					'date'       => esc_html__( 'Date', 'Avada' ),
					'categories' => esc_html__( 'Categories', 'Avada' ),
					'tags'       => esc_html__( 'Tags', 'Avada' ),
					'comments'   => esc_html__( 'Comments', 'Avada' ),
					'read_more'  => esc_html__( 'Read More Link', 'Avada' ),
					'post_type'  => esc_html__( 'Post Type', 'Avada' ),
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
			'search_new_search_position' => [
				'label'           => esc_html__( 'Search Field Position', 'Avada' ),
				'description'     => esc_html__( 'Controls the position of the search bar on the search results page.', 'Avada' ),
				'id'              => 'search_new_search_position',
				'default'         => 'top',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'top'    => esc_html__( 'Above Results', 'Avada' ),
					'bottom' => esc_html__( 'Below Results', 'Avada' ),
					'hidden' => esc_html__( 'Hide', 'Avada' ),
				],
				'update_callback' => [
					[
						'condition' => 'is_search',
						'operator'  => '===',
						'value'     => true,
					],
				],
			],
		],
	];

	return $sections;

}
