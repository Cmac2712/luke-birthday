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
 * Element settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_misc( $sections ) {

	global $content_min_media_query, $dynamic_css_helpers;

	$option_name = Fusion_Settings::get_option_name();
	$settings    = get_option( $option_name, [] );

	$primary_color_elements = [
		'.fusion-date-and-formats .fusion-format-box',
		'.fusion-blog-pagination .pagination .pagination-prev:hover:before',
		'.fusion-blog-pagination .pagination .pagination-next:hover:after',

		'.fusion-filters .fusion-filter.fusion-active a',
	];

	$extras                 = apply_filters( 'fusion_builder_element_classes', [ '.fusion-dropcap' ], '.fusion-dropcap' );
	$primary_color_elements = array_merge( $primary_color_elements, $extras );

	$extras                 = apply_filters( 'fusion_builder_element_classes', [ '.fusion-popover' ], '.fusion-popover' );
	$primary_color_elements = array_merge( $primary_color_elements, $extras );

	$extras                 = apply_filters( 'fusion_builder_element_classes', [ '.tooltip-shortcode' ], '.tooltip-shortcode' );
	$primary_color_elements = array_merge( $primary_color_elements, $extras );

	$extras = apply_filters( 'fusion_builder_element_classes', [ '.fusion-login-box' ], '.fusion-login-box' );
	foreach ( $extras as $key => $val ) {
		$extras[ $key ] .= ' a:hover';
	}
	$primary_color_elements = array_merge( $primary_color_elements, $extras );

	$primary_border_color_elements = [
		'.fusion-blog-pagination .pagination .current',
		'.fusion-blog-pagination .fusion-hide-pagination-text .pagination-prev:hover',
		'.fusion-blog-pagination .fusion-hide-pagination-text .pagination-next:hover',
		'.fusion-date-and-formats .fusion-date-box',
		'.fusion-blog-pagination .pagination a.inactive:hover',
		'.fusion-hide-pagination-text .fusion-blog-pagination .pagination .pagination-next:hover',
		'.fusion-hide-pagination-text .fusion-blog-pagination .pagination .pagination-prev:hover',

		'.fusion-filters .fusion-filter.fusion-active a',

		'.table-2 table thead',

		'.fusion-tabs.classic .nav-tabs > li.active .tab-link:hover',
		'.fusion-tabs.classic .nav-tabs > li.active .tab-link:focus',
		'.fusion-tabs.classic .nav-tabs > li.active .tab-link',
		'.fusion-tabs.vertical-tabs.classic .nav-tabs > li.active .tab-link',
	];

	$main_elements = apply_filters( 'fusion_builder_element_classes', [ '.fusion-reading-box-container' ], '.fusion-reading-box-container' );
	foreach ( $extras as $key => $val ) {
		$extras[ $key ] .= ' .reading-box';
	}
	$primary_border_color_elements = array_merge( $primary_border_color_elements, $extras );

	$primary_background_color_elements = [
		'.fusion-blog-pagination .pagination .current',
		'.fusion-blog-pagination .fusion-hide-pagination-text .pagination-prev:hover',
		'.fusion-blog-pagination .fusion-hide-pagination-text .pagination-next:hover',
		'.fusion-date-and-formats .fusion-date-box',

		'.table-2 table thead',
	];

	Fusion_Dynamic_CSS::add_replace_pattern( '.fusion-builder-elements-primary_color_elements', Fusion_Dynamic_CSS_Helpers::get_elements_string( $primary_color_elements ) );
	Fusion_Dynamic_CSS::add_replace_pattern( '.fusion-builder-elements-primary_border_color_elements', Fusion_Dynamic_CSS_Helpers::get_elements_string( $primary_border_color_elements ) );
	Fusion_Dynamic_CSS::add_replace_pattern( '.fusion-builder-elements-primary_background_color_elements', Fusion_Dynamic_CSS_Helpers::get_elements_string( $primary_background_color_elements ) );

	$sections['globals'] = [
		'label'    => esc_attr__( 'Global Options', 'fusion-builder' ),
		'id'       => 'globals',
		'is_panel' => true,
		'priority' => 1,
		'icon'     => 'el-icon-cog',
		'fields'   => [
			'primary_color'                  => [
				'label'       => esc_attr__( 'Primary Color', 'fusion-builder' ),
				'description' => esc_attr__( 'Controls the main highlight color throughout Fusion Builder elements.', 'fusion-builder' ),
				'id'          => 'primary_color',
				'default'     => '#65bc7b',
				'type'        => 'color',
				'css_vars'    => [
					[
						'name'     => '--primary_color',
						'callback' => [ 'sanitize_color' ],
					],
					[
						'name'     => '--primary_color-7a',
						'callback' => [ 'color_alpha_set', '0.7' ],
					],
					[
						'name'     => '--primary_color-85a',
						'callback' => [ 'color_alpha_set', '0.85' ],
					],
					[
						'name'     => '--primary_color-2a',
						'callback' => [ 'color_alpha_set', '0.2' ],
					],
				],
			],
			'gmap_api'                       => [
				'label'           => esc_attr__( 'Google Maps API Key', 'fusion-builder' ),
				/* translators: Link with the "the Google docs". */
				'description'     => sprintf( esc_attr__( 'Follow the steps in %s to get the API key. This key applies to both the contact page map and Fusion Builder google map element.', 'fusion-builder' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key#key" target="_blank" rel="noopener noreferrer">' . esc_attr__( 'the Google docs', 'fusion-builder' ) . '</a>' ),
				'id'              => 'gmap_api',
				'default'         => '',
				'type'            => 'text',
				'active_callback' => [ 'Avada_Options_Conditionals', 'is_contact' ],
				'required'        => [
					[
						'setting'  => 'status_gmap',
						'operator' => '=',
						'value'    => '1',
					],
				],
			],
			'woocommerce_product_box_design' => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'WooCommerce Product Box Design', 'fusion-builder' ),
				'description' => esc_attr__( 'Controls the design of the product boxes.', 'fusion-builder' ),
				'id'          => 'woocommerce_product_box_design',
				'default'     => 'classic',
				'choices'     => [
					'classic' => esc_attr__( 'Classic', 'fusion-builder' ),
					'clean'   => esc_attr__( 'Clean', 'fusion-builder' ),
				],
			],
			'ec_hover_type'                  => [
				'label'       => esc_attr__( 'Events Featured Image Hover Type', 'fusion-builder' ),
				'description' => esc_attr__( 'Controls the hover type for event featured images.', 'fusion-builder' ),
				'id'          => 'ec_hover_type',
				'default'     => 'none',
				'type'        => 'select',
				'choices'     => [
					'none'    => 'none',
					'zoomin'  => esc_attr__( 'Zoom In', 'fusion-builder' ),
					'zoomout' => esc_attr__( 'Zoom Out', 'fusion-builder' ),
					'liftup'  => esc_attr__( 'Lift Up', 'fusion-builder' ),
				],
			],
		],
	];

	return $sections;

}
