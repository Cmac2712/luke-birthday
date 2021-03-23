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
 * Blog settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_blog( $sections ) {
	$sections['blog'] = [
		'label'    => esc_html__( 'Blog', 'fusion-builder' ),
		'id'       => 'blog_section',
		'priority' => 15,
		'icon'     => 'el-icon-file-edit',
		'alt_icon' => 'fusiona-blog',
		'class'    => 'hidden-section-heading',
		'fields'   => [
			'blog_general_options' => [
				'label'          => esc_html__( 'General Blog', 'fusion-builder' ),
				'description'    => '',
				'id'             => 'blog_general_options',
				'icon'           => true,
				'type'           => 'sub-section',
				'fields'         => [
					'general_blog_important_note_info'     => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> The options on this tab only control the assigned blog page in "Settings > Reading", blog archives or the blog single post page, not the blog element. The only options on this tab that work with the blog element are the Date Format options and Load More Post Button Color.', 'Avada' ) . '</div>',
						'id'          => 'general_blog_important_note_info',
						'type'        => 'custom',
					],
					'blog_load_more_posts_button_bg_color' => [
						'label'       => esc_html__( 'Load More Posts Button Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the background color of the load more button for ajax post loading. Also works with the blog element.', 'fusion-builder' ),
						'id'          => 'blog_load_more_posts_button_bg_color',
						'default'     => '#ebeaea',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--blog_load_more_posts_button_bg_color',
								'element'  => '.fusion-load-more-button',
								'callback' => [ 'sanitize_color' ],
							],
							[
								'name'     => '--blog_load_more_posts_button_bg_color-hover-bg',
								'element'  => '.fusion-load-more-button',
								'callback' => [ 'color_alpha_set', '0.8' ],
							],
							[
								'name'     => '--blog_load_more_posts_button_bg_color-text-color',
								'element'  => '.fusion-load-more-button',
								'callback' => [ 'get_readable_color' ],
							],
						],
					],
					'dates_box_color'                      => [
						'label'       => esc_attr__( 'Blog Alternate Layout Date Box Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the date box in blog alternate and recent posts layouts.', 'fusion-builder' ),
						'id'          => 'dates_box_color',
						'default'     => '#eef0f2',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--dates_box_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'alternate_date_format_month_year'     => [
						'label'       => esc_html__( 'Blog Alternate Layout Month and Year Format', 'fusion-builder' ),
						'description' => __( 'Controls the month and year format for blog alternate layouts. <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>', 'fusion-builder' ),
						'id'          => 'alternate_date_format_month_year',
						'default'     => 'm, Y',
						'type'        => 'text',
					],
					'alternate_date_format_day'            => [
						'label'       => esc_html__( 'Blog Alternate Layout Day Format', 'fusion-builder' ),
						'description' => __( 'Controls the day format for blog alternate layouts. <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>', 'fusion-builder' ),
						'id'          => 'alternate_date_format_day',
						'default'     => 'j',
						'type'        => 'text',
					],
					'timeline_date_format'                 => [
						'label'       => esc_html__( 'Blog Timeline Layout Date Format', 'fusion-builder' ),
						'description' => __( 'Controls the timeline label format for blog timeline layouts. <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date</a>', 'fusion-builder' ),
						'id'          => 'timeline_date_format',
						'default'     => 'F Y',
						'type'        => 'text',
					],
				],
				'meta_font_size' => [
					'label'       => esc_html__( 'Meta Data Font Size', 'fusion-builder' ),
					'description' => esc_html__( 'Controls the font size for meta data text.', 'fusion-builder' ),
					'id'          => 'meta_font_size',
					'default'     => '12px',
					'type'        => 'dimension',
					'css_vars'    => [
						[
							'name' => '--meta_font_size',
						],
					],
				],
				'date_format'    => [
					'label'       => esc_html__( 'Date Format', 'fusion-builder' ),
					'description' => __( 'Controls the date format for date meta data.  <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>', 'fusion-builder' ),
					'id'          => 'date_format',
					'default'     => 'F jS, Y',
					'type'        => 'text',
				],
			],
		],
	];

	return $sections;

}
