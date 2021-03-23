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
 * Slideshows settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_slideshows( $sections ) {

	$sections['slideshows'] = [
		'label'    => esc_html__( 'Slideshows', 'fusion-builder' ),
		'id'       => 'heading_slideshows',
		'priority' => 19,
		'icon'     => 'el-icon-picture',
		'alt_icon' => 'fusiona-uniF61C',
		'fields'   => [
			'posts_slideshow_number'    => [
				'label'       => esc_html__( 'Posts Slideshow Images', 'fusion-builder' ),
				'description' => esc_html__( 'Controls the number of featured image boxes for blog/portfolio posts.', 'fusion-builder' ),
				'id'          => 'posts_slideshow_number',
				'default'     => '5',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '1',
					'max'  => '30',
					'step' => '1',
				],
			],
			'slideshow_autoplay'        => [
				'label'       => esc_html__( 'Autoplay', 'fusion-builder' ),
				'description' => esc_html__( 'Turn on to autoplay the slideshows.', 'fusion-builder' ),
				'id'          => 'slideshow_autoplay',
				'default'     => '1',
				'type'        => 'switch',
				// No need to refresh the page.
				'transport'   => 'postMessage',
			],
			'slideshow_smooth_height'   => [
				'label'       => esc_html__( 'Smooth Height', 'fusion-builder' ),
				'description' => esc_html__( 'Turn on to enable smooth height on slideshows when using images with different heights. Please note, smooth height is disabled on blog grid layout.', 'fusion-builder' ),
				'id'          => 'slideshow_smooth_height',
				'default'     => '0',
				'type'        => 'switch',
			],
			'slideshow_speed'           => [
				'label'       => esc_html__( 'Slideshow Speed', 'fusion-builder' ),
				'description' => esc_html__( 'Controls the speed of slideshows for the slider element and sliders within posts. ex: 1000 = 1 second.', 'fusion-builder' ),
				'id'          => 'slideshow_speed',
				'default'     => '7000',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '100',
					'max'  => '20000',
					'step' => '50',
				],
				'output'      => [

					// Change the fusionFlexSliderVars.slideshow_speed var.
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'fusionFlexSliderVars',
								'id'        => 'slideshow_speed',
								'trigger'   => [ 'fusionDestroyPostFlexSlider', 'fusionInitPostFlexSlider' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
			],
			'pagination_video_slide'    => [
				'label'       => esc_html__( 'Pagination Circles Below Video Slides', 'fusion-builder' ),
				'description' => esc_html__( 'Turn on to show pagination circles below a video slide for the slider element. Turn off to hide them on video slides.', 'fusion-builder' ),
				'id'          => 'pagination_video_slide',
				'default'     => '0',
				'type'        => 'switch',
				'output'      => [

					// Change the fusionFlexSliderVars.pagination_video_slide var.
					[
						'element'           => 'helperElement',
						'property'          => 'bottom',
						'js_callback'       => [
							'fusionGlobalScriptSet',
							[
								'globalVar' => 'fusionFlexSliderVars',
								'id'        => 'pagination_video_slide',
								'trigger'   => [ 'fusionDestroyPostFlexSlider', 'fusionInitPostFlexSlider' ],
							],
						],
						'sanitize_callback' => '__return_empty_string',
					],
				],
			],

			'slider_nav_box_dimensions' => [
				'label'       => esc_html__( 'Navigation Box Dimensions', 'fusion-builder' ),
				'description' => esc_html__( 'Controls the width and height of the navigation box.', 'fusion-builder' ),
				'id'          => 'slider_nav_box_dimensions',
				'units'       => false,
				'default'     => [
					'width'  => '30px',
					'height' => '30px',
				],
				'type'        => 'dimensions',
				'css_vars'    => [
					[
						'name'   => '--slider_nav_box_dimensions-width',
						'choice' => 'width',
					],
					[
						'name'   => '--slider_nav_box_dimensions-height',
						'choice' => 'height',
					],
				],
			],
			'slider_arrow_size'         => [
				'label'       => esc_html__( 'Navigation Arrow Size', 'fusion-builder' ),
				'description' => esc_html__( 'Controls the font size of the navigation arrow.', 'fusion-builder' ),
				'id'          => 'slider_arrow_size',
				'default'     => '14px',
				'type'        => 'dimension',
				'css_vars'    => [
					[
						'name' => '--slider_arrow_size',
					],
				],
			],
		],
	];

	return $sections;

}
