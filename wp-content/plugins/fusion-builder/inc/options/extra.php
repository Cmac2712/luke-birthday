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
 * Extra settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_extra( $sections ) {
	$sections['extras'] = [
		'label'    => esc_html__( 'Extra', 'fusion-builder' ),
		'id'       => 'extra_section',
		'priority' => 24,
		'icon'     => 'el-icon-cogs',
		'alt_icon' => 'fusiona-cog',
		'fields'   => [
			'misc_options_section'   => [
				'label'       => esc_html__( 'Miscellaneous', 'fusion-builder' ),
				'description' => '',
				'id'          => 'misc_options_section',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'featured_image_placeholder' => [
						'label'           => esc_html__( 'Image Placeholders', 'fusion-builder' ),
						'description'     => esc_html__( 'Turn on to display a placeholder image for posts that do not have a featured image. This allows the post to display on portfolio archives and related posts/projects carousels.', 'fusion-builder' ),
						'id'              => 'featured_image_placeholder',
						'default'         => '1',
						'type'            => 'switch',
						// Partial refresh for related-posts. Full refresh if js_callback returns false.
						'partial_refresh' => [
							'related_posts_layout_partial' => [
								'selector'              => 'section.related-posts',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'related_posts_template' ],
								'success_trigger_event' => 'fusion-reinit-related-posts-carousel',
								'js_callback'           => [ 'noPortfolioOnPage' ],
							],
						],
					],
					'excerpt_base'               => [
						'label'       => esc_html__( 'Basis for Excerpt Length', 'fusion-builder' ),
						'description' => esc_html__( 'Controls if the excerpt length is based on words or characters.', 'fusion-builder' ),
						'id'          => 'excerpt_base',
						'default'     => 'words',
						'type'        => 'radio-buttonset',
						'choices'     => [
							'words'      => esc_html__( 'Words', 'fusion-builder' ),
							'characters' => esc_html__( 'Characters', 'fusion-builder' ),
						],
					],
					'disable_excerpts'           => [
						'label'       => esc_html__( 'Excerpt [...] Display', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to display the read more sign [...] on excerpts throughout the site.', 'fusion-builder' ),
						'id'          => 'disable_excerpts',
						'default'     => '1',
						'type'        => 'switch',
					],
					'link_read_more'             => [
						'label'       => esc_html__( 'Make [...] Link to Single Post Page', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to have the read more sign [...] on excerpts link to the single post page.', 'fusion-builder' ),
						'id'          => 'link_read_more',
						'default'     => '0',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'disable_excerpts',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
					'nofollow_social_links'      => [
						'label'       => esc_html__( 'Add "nofollow" to social links', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to add "nofollow" attribute to all social links.', 'fusion-builder' ),
						'id'          => 'nofollow_social_links',
						'default'     => '0',
						'type'        => 'switch',
						// No need to update the preview.
						'transport'   => 'postMessage',
					],
					'social_icons_new'           => [
						'label'       => esc_html__( 'Open Social Icons in a New Window', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to allow social icons to open in a new window.', 'fusion-builder' ),
						'id'          => 'social_icons_new',
						'default'     => '1',
						'type'        => 'switch',
						// No need to update the preview.
						'transport'   => 'postMessage',
					],
				],
			],
			'rollover_sub_section'   => [
				'label'       => esc_html__( 'Featured Image Rollover', 'fusion-builder' ),
				'description' => '',
				'id'          => 'rollover_sub_section',
				'type'        => 'sub-section',
				'fields'      => [
					'image_rollover'              => [
						'label'        => esc_html__( 'Image Rollover', 'fusion-builder' ),
						'description'  => esc_html__( 'Turn on to display the rollover graphic on blog and portfolio featured images.', 'fusion-builder' ),
						'id'           => 'image_rollover',
						'default'      => '1',
						'type'         => 'switch',
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'image_rollover_layout_partial' => [
								'js_callback' => [ 'isRolloverOnPage' ],
							],
						],
					],
					'image_rollover_direction'    => [
						'label'       => esc_html__( 'Image Rollover Direction', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the direction the rollover starts from.', 'fusion-builder' ),
						'id'          => 'image_rollover_direction',
						'default'     => 'left',
						'type'        => 'select',
						'choices'     => [
							'fade'            => esc_html__( 'Fade', 'fusion-builder' ),
							'left'            => esc_html__( 'Left', 'fusion-builder' ),
							'right'           => esc_html__( 'Right', 'fusion-builder' ),
							'bottom'          => esc_html__( 'Bottom', 'fusion-builder' ),
							'top'             => esc_html__( 'Top', 'fusion-builder' ),
							'center_horiz'    => esc_html__( 'Center Horizontal', 'fusion-builder' ),
							'center_vertical' => esc_html__( 'Center Vertical', 'fusion-builder' ),
						],
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'output'      => [
							// Change classes in <body>.
							[
								'element'       => 'body',
								'function'      => 'attr',
								'attr'          => 'class',
								'value_pattern' => 'avada-image-rollover-direction-$',
								'remove_attrs'  => [ 'avada-image-rollover-direction-fade', 'avada-image-rollover-direction-left', 'avada-image-rollover-direction-right', 'avada-image-rollover-direction-bottom', 'avada-image-rollover-direction-top', 'avada-image-rollover-direction-center_horiz', 'avada-image-rollover-direction-center_vertical' ],
							],
						],
					],
					'image_rollover_icon_size'    => [
						'label'       => esc_html__( 'Image Rollover Icon Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the size of the rollover icons.', 'fusion-builder' ),
						'id'          => 'image_rollover_icon_size',
						'default'     => '15px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name' => '--image_rollover_icon_size',
							],
						],
					],
					'image_rollover_icons'        => [
						'label'        => esc_html__( 'Image Rollover Icons', 'fusion-builder' ),
						'description'  => esc_html__( 'Choose which icons display.', 'fusion-builder' ),
						'id'           => 'image_rollover_icons',
						'default'      => 'linkzoom',
						'type'         => 'radio-buttonset',
						'choices'      => [
							'linkzoom' => esc_html__( 'Link + Zoom', 'Avada' ),
							'link'     => esc_attr__( 'Link', 'Avada' ),
							'zoom'     => esc_attr__( 'Zoom', 'Avada' ),
							'no'       => esc_attr__( 'No Icons', 'Avada' ),
						],
						'required'     => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'image_rollover_icons_partial' => [
								'js_callback' => [ 'isRolloverOnPage' ],
							],
						],
					],
					'title_image_rollover'        => [
						'label'        => esc_html__( 'Image Rollover Title', 'fusion-builder' ),
						'description'  => esc_html__( 'Turn on to display the post title in the image rollover.', 'fusion-builder' ),
						'id'           => 'title_image_rollover',
						'default'      => '1',
						'type'         => 'switch',
						'required'     => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'title_image_rollover_partial' => [
								'js_callback' => [ 'isRolloverOnPage' ],
							],
						],
					],
					'cats_image_rollover'         => [
						'label'        => esc_html__( 'Image Rollover Categories', 'fusion-builder' ),
						'description'  => esc_html__( 'Turn on to display the post categories in the image rollover.', 'fusion-builder' ),
						'id'           => 'cats_image_rollover',
						'default'      => '1',
						'type'         => 'switch',
						'required'     => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'cats_image_rollover_partial' => [
								'js_callback' => [ 'isRolloverOnPage' ],
							],
						],
					],
					'icon_circle_image_rollover'  => [
						'label'       => esc_html__( 'Image Rollover Icon Circle', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to display the icon background circle in the image rollover.', 'fusion-builder' ),
						'id'          => 'icon_circle_image_rollover',
						'default'     => '1',
						'type'        => 'switch',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'true' ],
										'element'   => 'body',
										'className' => 'avada-image-rollover-circle-yes',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'false' ],
										'element'   => 'body',
										'className' => 'avada-image-rollover-circle-no',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'image_gradient_top_color'    => [
						'label'       => esc_html__( 'Image Rollover Gradient Top Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the top color of the image rollover background.', 'fusion-builder' ),
						'id'          => 'image_gradient_top_color',
						'type'        => 'color-alpha',
						'default'     => 'rgba(101,188,123,0.8)',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--image_gradient_top_color',
								'element'  => '.fusion-image-wrapper',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'image_gradient_bottom_color' => [
						'label'       => esc_html__( 'Image Rollover Gradient Bottom Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the bottom color of the image rollover background.', 'fusion-builder' ),
						'id'          => 'image_gradient_bottom_color',
						'default'     => '#65bc7b',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--image_gradient_bottom_color',
								'element'  => '.fusion-image-wrapper',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'image_rollover_text_color'   => [
						'label'       => esc_html__( 'Image Rollover Element Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of image rollover text and icon circular backgrounds.', 'fusion-builder' ),
						'id'          => 'image_rollover_text_color',
						'default'     => '#333333',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--image_rollover_text_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'image_rollover_icon_color'   => [
						'label'       => esc_html__( 'Image Rollover Icon Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the icons in the image rollover.', 'fusion-builder' ),
						'id'          => 'image_rollover_icon_color',
						'default'     => '#ffffff',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'image_rollover',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--image_rollover_icon_color',
								'element'  => '.fusion-rollover',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
				],
			],
			'pagination_box_section' => [
				'label'       => esc_html__( 'Pagination', 'fusion-builder' ),
				'description' => '',
				'id'          => 'pagination_box_section',
				'type'        => 'sub-section',
				'fields'      => [
					'pagination_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> The options on this tab apply to all pagination throughout the site, including the 3rd party plugins that Avada has design integration with.', 'Avada' ) . '</div>',
						'id'          => 'pagination_important_note_info',
						'type'        => 'custom',
					],
					'pagination_sizing'              => [
						'type'        => 'radio-buttonset',
						'label'       => esc_html__( 'Pagination Sizing', 'fusion-builder' ),
						'description' => esc_html__( 'Set on which dimension the pagination box size should be based.', 'fusion-builder' ),
						'id'          => 'pagination_sizing',
						'default'     => 'width_height',
						'choices'     => [
							'width_height' => esc_html__( 'Width/Height Based', 'fusion-builder' ),
							'padding'      => esc_html__( 'Padding Based', 'fusion-builder' ),
						],
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '===', 'padding' ],
										'element'   => 'body',
										'className' => 'avada-has-pagination-padding',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'pagination_width_height'        => [
						'label'       => esc_html__( 'Pagination Box Width/Height', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the width and height of the displayed page links.', 'fusion-builder' ),
						'id'          => 'pagination_width_height',
						'default'     => '30',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '5',
							'max'  => '100',
							'step' => '1',
						],
						'required'    => [
							[
								'setting'  => 'pagination_sizing',
								'operator' => '!=',
								'value'    => 'padding',
							],
						],
						'css_vars'    => [
							[
								'name'          => '--pagination_width_height',
								'value_pattern' => '$px',
							],
						],
					],
					'pagination_box_padding'         => [
						'label'       => esc_html__( 'Pagination Box Padding', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the padding inside the pagination boxes.', 'fusion-builder' ),
						'id'          => 'pagination_box_padding',
						'units'       => false,
						'default'     => [
							'width'  => '6px',
							'height' => '2px',
						],
						'type'        => 'dimensions',
						'required'    => [
							[
								'setting'  => 'pagination_sizing',
								'operator' => '=',
								'value'    => 'padding',
							],
						],
						'css_vars'    => [
							[
								'name'   => '--pagination_box_padding-width',
								'choice' => 'width',
							],
							[
								'name'   => '--pagination_box_padding-height',
								'choice' => 'height',
							],
						],
					],
					'pagination_border_width'        => [
						'label'       => esc_html__( 'Pagination Border Width', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the border width of the displayed page links. In Pixels.', 'fusion-builder' ),
						'id'          => 'pagination_border_width',
						'default'     => '1',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '0',
							'max'  => '25',
							'step' => '1',
						],
						'css_vars'    => [
							[
								'name'          => '--pagination_border_width',
								'value_pattern' => '$px',
							],
						],
					],
					'pagination_border_radius'       => [
						'label'       => esc_html__( 'Pagination Border Radius', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the border radius of the displayed page links. Values of half the overall width or higher will yield circular links.', 'fusion-builder' ),
						'id'          => 'pagination_border_radius',
						'default'     => '0',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '0',
							'max'  => '50',
							'step' => '1',
						],
						'css_vars'    => [
							[
								'name'          => '--pagination_border_radius',
								'value_pattern' => '$px',
							],
						],
					],
					'pagination_text_display'        => [
						'label'       => esc_html__( 'Pagination Text Display', 'fusion-builder' ),
						'description' => esc_html__( 'Turn on to display the "Previous/Next" text.', 'fusion-builder' ),
						'id'          => 'pagination_text_display',
						'default'     => '1',
						'type'        => 'switch',
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'true' ],
										'element'   => 'body',
										'className' => 'fusion-show-pagination-text',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'pagination_font_size'           => [
						'label'       => esc_html__( 'Pagination Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the size of the pagination text.', 'fusion-builder' ),
						'id'          => 'pagination_font_size',
						'default'     => '12px',
						'type'        => 'dimension',
						'css_vars'    => [
							[
								'name' => '--pagination_font_size',
							],
						],
					],
					'pagination_range'               => [
						'label'        => esc_html__( 'Pagination Range', 'fusion-builder' ),
						'description'  => esc_html__( 'Controls the number of page links displayed left and right of current page.', 'fusion-builder' ),
						'id'           => 'pagination_range',
						'default'      => '1',
						'type'         => 'slider',
						'choices'      => [
							'min'  => '0',
							'max'  => '10',
							'step' => '1',
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'related_posts_layout_partial' => [
								'js_callback' => [ 'isPaginationOnPage' ],
							],
						],
					],
					'pagination_start_end_range'     => [
						'label'        => esc_html__( 'Pagination Start / End Range', 'fusion-builder' ),
						'description'  => esc_html__( 'Controls the number of page links displayed at the start and at the end of pagination.', 'fusion-builder' ),
						'id'           => 'pagination_start_end_range',
						'default'      => '0',
						'type'         => 'slider',
						'choices'      => [
							'min'  => '0',
							'max'  => '10',
							'step' => '1',
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'pagination_start_end_range_partial' => [
								'js_callback' => [ 'isPaginationOnPage' ],
							],
						],
					],
				],
			],
			'gridbox_section'        => [
				'label'       => esc_html__( 'Grid / Masonry', 'fusion-builder' ),
				'description' => '',
				'id'          => 'gridbox_section',
				'type'        => 'sub-section',
				'fields'      => [
					'gridbox_styling_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> These are Grid Box Styling global options that apply to grid boxes throughout the site; blog grid and timeline, portfolio boxed layout and WooCommerce boxes. Blog / Portfolio elements also have options to override these.', 'Avada' ) . '</div>',
						'id'          => 'gridbox_styling_important_note_info',
						'type'        => 'custom',
					],
					'timeline_bg_color'                   => [
						'label'       => esc_html__( 'Grid Box Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the background color for the grid boxes.', 'fusion-builder' ),
						'id'          => 'timeline_bg_color',
						'default'     => 'rgba(255,255,255,0)',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--timeline_bg_color',
								'callback' => [ 'sanitize_color' ],
							],
							[
								'name'     => '--timeline_bg_color-20px-transparent',
								'callback' => [
									'return_string_if_transparent',
									[
										'transparent' => '',
										'opaque'      => '20px',
									],
								],
							],
							[
								'name'     => '--timeline_bg_color-not-transparent',
								'callback' => [ 'get_non_transparent_color', '' ],
							],
						],
					],
					'timeline_color'                      => [
						'label'       => esc_html__( 'Grid Element Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of borders/date box/timeline dots and arrows for the grid boxes.', 'fusion-builder' ),
						'id'          => 'timeline_color',
						'default'     => '#ebeaea',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'        => '--timeline_color',
								'js_callback' => [ 'timeLineColorCallback' ],
							],
						],
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'full-transparent' ],
										'element'   => 'html',
										'className' => 'avada-has-transparent-timeline_color',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'grid_separator_style_type'           => [
						'label'       => esc_html__( 'Grid Separator Style', 'fusion-builder' ),
						'description' => __( 'Controls the line style of grid separators. <strong>Note:</strong> For blog and portfolio grids at least one meta data field must be enabled and excerpt or full content must be shown in order that the separator will be displayed.', 'fusion-builder' ),
						'id'          => 'grid_separator_style_type',
						'default'     => 'double|solid',
						'type'        => 'select',
						'choices'     => [
							'none'          => esc_html__( 'No Style', 'fusion-builder' ),
							'single|solid'  => esc_html__( 'Single Border Solid', 'fusion-builder' ),
							'double|solid'  => esc_html__( 'Double Border Solid', 'fusion-builder' ),
							'single|dashed' => esc_html__( 'Single Border Dashed', 'fusion-builder' ),
							'double|dashed' => esc_html__( 'Double Border Dashed', 'fusion-builder' ),
							'single|dotted' => esc_html__( 'Single Border Dotted', 'fusion-builder' ),
							'double|dotted' => esc_html__( 'Double Border Dotted', 'fusion-builder' ),
							'shadow'        => esc_html__( 'Shadow', 'fusion-builder' ),
						],
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'updateGridSeps',
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'grid_separator_color'                => [
						'label'       => esc_html__( 'Grid Separator Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the line style color of grid separators.', 'fusion-builder' ),
						'id'          => 'grid_separator_color',
						'default'     => '#ebeaea',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--grid_separator_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'full-transparent' ],
										'element'   => 'body',
										'className' => 'avada-has-transparent-grid_separator_color',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'grid_masonry_heading'                => [
						'label'       => esc_html__( 'Masonry Options', 'fusion-builder' ),
						'description' => '',
						'id'          => 'grid_masonry_heading',
						'type'        => 'info',
					],
					'gridbox_masonry_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> These are Masonry global options that apply to the Blog / Portfolio / Gallery elements in addition to Blog and Portfolio archives. Blog / Portfolio / Gallery elements also have options to override these.', 'Avada' ) . '</div>',
						'id'          => 'gridbox_masonry_important_note_info',
						'type'        => 'custom',
					],
					'masonry_grid_ratio'                  => [
						'label'        => esc_html__( 'Masonry Image Aspect Ratio', 'fusion-builder' ),
						'description'  => __( 'Set the ratio to decide when an image should become landscape (ratio being width : height) and portrait (ratio being height : width). <strong>IMPORTANT:</strong> The value of "1.0" represents a special case, which will use the auto calculated ratios like in versions prior to Avada 5.5.', 'fusion-builder' ),
						'id'           => 'masonry_grid_ratio',
						'default'      => '1.5',
						'type'         => 'slider',
						'choices'      => [
							'min'  => 1.0,
							'max'  => 4.0,
							'step' => 0.1,
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'masonry_grid_ratio_partial' => [
								'js_callback' => [ 'isMasonryOnPage' ],
							],
						],
					],
					'masonry_width_double'                => [
						'label'        => esc_html__( 'Masonry 2x2 Width', 'fusion-builder' ),
						'description'  => __( 'This option decides when a square 1x1 image should become 2x2. This will not apply to images that highly favor landscape or portrait layouts. <strong>IMPORTANT:</strong> There is a “Masonry Image Layout” setting for every image in the WP media library that allows you to manually set how an image will appear (1x1, landscape, portrait or 2x2), regardless of the original ratio.', 'fusion-builder' ),
						'id'           => 'masonry_width_double',
						'default'      => '2000',
						'type'         => 'slider',
						'choices'      => [
							'min'  => 200,
							'max'  => 5120,
							'step' => 1,
						],
						// Prevents full refresh if js_callback returns false.
						'full_refresh' => [
							'masonry_width_double_partial' => [
								'js_callback' => [ 'isMasonryOnPage' ],
							],
						],
					],
				],
			],
		],
	];

	return $sections;

}
