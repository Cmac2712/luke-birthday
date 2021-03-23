<?php // phpcs:disable WordPress.Files.FileName
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
 * Custom CSS settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_custom_css( $sections ) {

	$sections['custom_css'] = [
		'label'    => esc_html__( 'Custom CSS', 'fusion-builder' ),
		'id'       => 'custom_css_section',
		'priority' => 27,
		'icon'     => 'el-icon-css',
		'alt_icon' => 'fusiona-code',
		'fields'   => [
			'custom_css' => [
				'label'       => esc_html__( 'CSS Code', 'fusion-builder' ),
				/* translators: <code>!important</code> */
				'description' => sprintf( esc_html__( 'Enter your CSS code in the field below. Do not include any tags or HTML in the field. Custom CSS entered here will override the theme CSS. In some cases, the %s tag may be needed. Don\'t URL encode image or svg paths. Contents of this field will be auto encoded.', 'fusion-builder' ), '<code>!important</code>' ),
				'id'          => 'custom_css',
				'default'     => '',
				'type'        => 'code',
				'choices'     => [
					'language' => 'css',
					'height'   => 450,
					'theme'    => 'chrome',
					'minLines' => 40,
					'maxLines' => 50,
				],
			],
		],
	];

	return $sections;

}
