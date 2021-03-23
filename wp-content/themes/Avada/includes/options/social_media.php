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
 * Social Media
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_options_section_social_media( $sections ) {

	$sections['social_media'] = [
		'label'    => esc_html__( 'Social Media', 'Avada' ),
		'id'       => 'heading_social_media',
		'priority' => 18,
		'icon'     => 'el-icon-share-alt',
		'alt_icon' => 'fusiona-link',
		'fields'   => [
			'social_media_icons_section'  => [
				'label'  => esc_html__( 'Social Media Icons', 'Avada' ),
				'id'     => 'social_media_icons_section',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'social_media_icons_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> This tab controls the social networks that display in the header and footer and which can also be used in the social links widget. Add the network of your choice along with your unique URL. Each network you wish to display must be added here to show up in the header and footer. These settings do not control the avada social widget, social link element or person element.', 'Avada' ) . '</div>',
						'id'          => 'social_media_icons_important_note_info',
						'type'        => 'custom',
					],
					'social_media_icons' => [
						'label'           => esc_html__( 'Social Media Icons / Links', 'Avada' ),
						'description'     => esc_html__( 'Social media links use a repeater field and allow one network per field. Click the "Add" button to add additional fields.', 'Avada' ),
						'id'              => 'social_media_icons',
						'default'         => [
							'fusionredux_repeater_data' => [
								[
									'title' => '',
								],
								[
									'title' => '',
								],
								[
									'title' => '',
								],
								[
									'title' => '',
								],
							],
							'icon'                      => [ 'facebook', 'twitter', 'instagram', 'pinterest' ],
							'url'                       => [ '#', '#', '#', '#' ],
							'custom_title'              => [ '', '', '', '' ],
							'custom_source'             => [
								[
									'url'       => '',
									'id'        => '',
									'height'    => '',
									'width'     => '',
									'thumbnail' => '',
								],
								[
									'url'       => '',
									'id'        => '',
									'height'    => '',
									'width'     => '',
									'thumbnail' => '',
								],
								[
									'url'       => '',
									'id'        => '',
									'height'    => '',
									'width'     => '',
									'thumbnail' => '',
								],
								[
									'url'       => '',
									'id'        => '',
									'height'    => '',
									'width'     => '',
									'thumbnail' => '',
								],
							],
						],
						'type'            => 'repeater',
						'bind_title'      => 'icon',
						'limit'           => 50,
						'fields'          => [
							'icon'          => [
								'id'          => 'icon',
								'type'        => 'select',
								'label'       => esc_html__( 'Social Network', 'Avada' ),
								'description' => esc_html__( 'Select a social network to automatically add its icon', 'Avada' ),
								'default'     => 'none',
								'choices'     => Fusion_Data::fusion_social_icons( true, false ),
							],
							'url'           => [
								'id'          => 'url',
								'type'        => 'text',
								'label'       => esc_html__( 'Custom Link', 'Avada' ),
								'description' => esc_html__( 'Insert your custom link here', 'Avada' ),
								'default'     => '',
							],
							'custom_title'  => [
								'id'          => 'custom_title',
								'type'        => 'text',
								'label'       => esc_html__( 'Custom Icon Title', 'Avada' ),
								'description' => esc_html__( 'Insert your custom link here', 'Avada' ),
								'default'     => '',
								'required'    => [
									[
										'setting'  => 'icon',
										'operator' => '==',
										'value'    => 'custom',
									],
								],
							],
							'custom_source' => [
								'id'          => 'custom_source',
								'type'        => 'media',
								'label'       => esc_html__( 'Choose the image you want to use as icon', 'Avada' ),
								'description' => esc_html__( 'Upload your custom icon', 'Avada' ),
								'default'     => '',
								'mode'        => false,
								'required'    => [
									[
										'setting'  => 'icon',
										'operator' => '==',
										'value'    => 'custom',
									],
								],
							],
						],
						'partial_refresh' => [

							// Partial refresh for the header.
							'header_content_social_media_icons_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_social_media_icons_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_social_media_icons' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],

							// Partial refresh for the footer.
							'footer_content_social_media_icons' => [
								'selector'            => '.fusion-footer',
								'container_inclusive' => false,
								'render_callback'     => [ 'Avada_Partial_Refresh_Callbacks', 'footer' ],
							],

							// Partial refresh for the sharingbox.
							'sharingbox_social_media_icons' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
				],
			],
			'header_social_icons_options' => [
				'label'       => esc_html__( 'Header Social Icons Styling', 'Avada' ),
				'description' => '',
				'id'          => 'header_social_icons_options',
				'default'     => '',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'header_social_links_font_size'     => [
						'label'       => esc_html__( 'Header Social Icon Font Size', 'Avada' ),
						'description' => esc_html__( 'Controls the font size of the header social icons.', 'Avada' ),
						'id'          => 'header_social_links_font_size',
						'default'     => '16px',
						'type'        => 'dimension',
						'css_vars'    => [
							[
								'name'    => '--header_social_links_font_size',
								'element' => '.fusion-social-networks',
							],
						],
					],
					'header_social_links_tooltip_placement' => [
						'label'           => esc_html__( 'Header Social Icon Tooltip Position', 'Avada' ),
						'description'     => esc_html__( 'Controls the tooltip position of the header social icons.', 'Avada' ),
						'id'              => 'header_social_links_tooltip_placement',
						'default'         => 'Bottom',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'top'    => esc_html__( 'Top', 'Avada' ),
							'right'  => esc_html__( 'Right', 'Avada' ),
							'bottom' => esc_html__( 'Bottom', 'Avada' ),
							'left'   => esc_html__( 'Left', 'Avada' ),
							'none'   => esc_html__( 'None', 'Avada' ),
						],
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_header_social_links_tooltip_placement_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_tooltip_placement_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_tooltip_placement' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => [ 'header-rendered', 'fusionInitTooltips' ],
							],
						],
					],
					'header_social_links_color_type'    => [
						'label'           => esc_html__( 'Header Social Icon Color Type', 'Avada' ),
						'description'     => esc_html__( 'Custom colors allow you to choose a color for icons and boxes. Brand colors will use the exact brand color of each network for the icons or boxes.', 'Avada' ),
						'id'              => 'header_social_links_color_type',
						'default'         => 'custom',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'custom' => esc_html__( 'Custom Colors', 'Avada' ),
							'brand'  => esc_html__( 'Brand Colors', 'Avada' ),
						],
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_header_social_links_color_type_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_color_type_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_color_type' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => [ 'header-rendered', 'fusionInitTooltips' ],
							],
						],
					],
					'header_social_links_icon_color'    => [
						'label'       => esc_html__( 'Header Social Icon Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the header social icons. This color will be used for all social icons in the header.', 'Avada' ),
						'id'          => 'header_social_links_icon_color',
						'default'     => '#ffffff',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'header_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--header_social_links_icon_color',
								'element'  => '.fusion-social-network-icon',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'header_social_links_boxed'         => [
						'label'           => esc_html__( 'Header Social Icons Boxed', 'Avada' ),
						'description'     => esc_html__( 'Controls if each icon is displayed in a small box.', 'Avada' ),
						'id'              => 'header_social_links_boxed',
						'default'         => '0',
						'type'            => 'switch',
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_header_social_links_boxed_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_boxed_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_header_social_links_boxed' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => [ 'header-rendered', 'fusionInitTooltips' ],
							],
						],
					],
					'header_social_links_box_color'     => [
						'label'       => esc_html__( 'Header Social Icon Box Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the social icon box.', 'Avada' ),
						'id'          => 'header_social_links_box_color',
						'default'     => '#ffffff',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'header_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'header_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--header_social_links_box_color',
								'element'  => '.fusion-social-network-icon',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'header_social_links_boxed_radius'  => [
						'label'       => esc_html__( 'Header Social Icon Boxed Radius', 'Avada' ),
						'description' => esc_html__( 'Controls the box radius.', 'Avada' ),
						'id'          => 'header_social_links_boxed_radius',
						'default'     => '4px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'header_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--header_social_links_boxed_radius',
								'element' => '.fusion-social-network-icon',
							],
						],
					],
					'header_social_links_boxed_padding' => [
						'label'       => esc_html__( 'Header Social Icon Boxed Padding', 'Avada' ),
						'description' => esc_html__( 'Controls the interior padding of the box.', 'Avada' ),
						'id'          => 'header_social_links_boxed_padding',
						'default'     => '8px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'header_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--header_social_links_boxed_padding',
								'element' => '.fusion-social-networks',
							],
						],
					],
				],
			],
			'footer_social_icons_options' => [
				'label'       => esc_html__( 'Footer Social Icons Styling', 'Avada' ),
				'description' => '',
				'id'          => 'footer_social_icons_options',
				'default'     => '',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'icons_footer'                      => [
						'label'           => esc_html__( 'Display Social Icons In The Footer', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display social icons in the footer copyright bar.', 'Avada' ),
						'id'              => 'icons_footer',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [
							'footer_content_icons_footer' => [
								'selector'              => '.fusion-footer',
								'container_inclusive'   => false,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'footer' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'footer_social_links_font_size'     => [
						'label'       => esc_html__( 'Footer Social Icon Font Size', 'Avada' ),
						'description' => esc_html__( 'Controls the font size of the footer social icons.', 'Avada' ),
						'id'          => 'footer_social_links_font_size',
						'default'     => '16px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--footer_social_links_font_size',
								'element' => '.fusion-social-networks',
							],
						],
					],
					'footer_social_links_tooltip_placement' => [
						'label'           => esc_html__( 'Footer Social Icon Tooltip Position', 'Avada' ),
						'description'     => esc_html__( 'Controls the tooltip position of the footer social icons.', 'Avada' ),
						'id'              => 'footer_social_links_tooltip_placement',
						'default'         => 'Top',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'top'    => esc_html__( 'Top', 'Avada' ),
							'right'  => esc_html__( 'Right', 'Avada' ),
							'bottom' => esc_html__( 'Bottom', 'Avada' ),
							'left'   => esc_html__( 'Left', 'Avada' ),
							'none'   => esc_html__( 'None', 'Avada' ),
						],
						'required'        => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'partial_refresh' => [
							'footer_content_footer_social_links_tooltip_placement' => [
								'selector'              => '.fusion-footer',
								'container_inclusive'   => false,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'footer' ],
								'success_trigger_event' => [ 'fusionInitTooltips' ],
							],
						],
					],
					'footer_social_links_color_type'    => [
						'label'           => esc_html__( 'Footer Social Icon Color Type', 'Avada' ),
						'description'     => esc_html__( 'Custom colors allow you to choose a color for icons and boxes. Brand colors will use the exact brand color of each network for the icons or boxes.', 'Avada' ),
						'id'              => 'footer_social_links_color_type',
						'default'         => 'custom',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'custom' => esc_html__( 'Custom Colors', 'Avada' ),
							'brand'  => esc_html__( 'Brand Colors', 'Avada' ),
						],
						'required'        => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'partial_refresh' => [
							'footer_content_footer_social_links_color_type' => [
								'selector'              => '.fusion-footer',
								'container_inclusive'   => false,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'footer' ],
								'success_trigger_event' => [ 'fusionInitTooltips' ],
							],
						],
					],
					'footer_social_links_icon_color'    => [
						'label'       => esc_html__( 'Footer Social Icon Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the footer social icons. This color will be used for all social icons in the footer.', 'Avada' ),
						'id'          => 'footer_social_links_icon_color',
						'type'        => 'color-alpha',
						'default'     => 'rgba(255,255,255,0.8)',
						'required'    => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'footer_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--footer_social_links_icon_color',
								'element'  => '.fusion-social-network-icon',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'footer_social_links_boxed'         => [
						'label'           => esc_html__( 'Footer Social Icons Boxed', 'Avada' ),
						'description'     => esc_html__( 'Controls if each icon is displayed in a small box.', 'Avada' ),
						'id'              => 'footer_social_links_boxed',
						'default'         => '0',
						'type'            => 'switch',
						'required'        => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'partial_refresh' => [
							'footer_content_footer_social_links_boxed' => [
								'selector'              => '.fusion-footer',
								'container_inclusive'   => false,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'footer' ],
								'success_trigger_event' => [ 'fusionInitTooltips' ],
							],
						],
					],
					'footer_social_links_box_color'     => [
						'label'       => esc_html__( 'Footer Social Icon Box Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the social icon box.', 'Avada' ),
						'id'          => 'footer_social_links_box_color',
						'default'     => '#222222',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'footer_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'footer_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--footer_social_links_box_color',
								'element'  => '.fusion-social-network-icon',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'footer_social_links_boxed_radius'  => [
						'label'       => esc_html__( 'Footer Social Icon Boxed Radius', 'Avada' ),
						'description' => esc_html__( 'Controls the box radius.', 'Avada' ),
						'id'          => 'footer_social_links_boxed_radius',
						'default'     => '4px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'footer_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--footer_social_links_boxed_radius',
								'element' => '.fusion-social-network-icon',
							],
						],
					],
					'footer_social_links_boxed_padding' => [
						'label'       => esc_html__( 'Footer Social Icon Boxed Padding', 'Avada' ),
						'description' => esc_html__( 'Controls the interior padding of the box.', 'Avada' ),
						'id'          => 'footer_social_links_boxed_padding',
						'default'     => '8px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'icons_footer',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'footer_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--footer_social_links_boxed_padding',
								'element' => '.fusion-social-networks',
							],
						],
					],
				],
			],
			'heading_social_sharing_box'  => [
				'label'  => esc_html__( 'Social Sharing Box', 'Avada' ),
				'id'     => 'heading_social_sharing_box',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'sharing_social_tagline'             => [
						'label'           => esc_html__( 'Sharing Box Tagline', 'Avada' ),
						'description'     => esc_html__( 'Insert a tagline for the social sharing boxes.', 'Avada' ),
						'id'              => 'sharing_social_tagline',
						'default'         => esc_html__( 'Share This Story, Choose Your Platform!', 'Avada' ),
						'type'            => 'text',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_social_tagline' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_box_tagline_text_color'     => [
						'label'       => esc_html__( 'Sharing Box Tagline Text Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the tagline text in the social sharing boxes.', 'Avada' ),
						'id'          => 'sharing_box_tagline_text_color',
						'default'     => '#212934',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--sharing_box_tagline_text_color',
								'element'  => '.share-box',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'social_bg_color'                    => [
						'label'       => esc_html__( 'Sharing Box Background Color', 'Avada' ),
						'description' => esc_html__( 'Controls the background color of the social sharing boxes.', 'Avada' ),
						'id'          => 'social_bg_color',
						'default'     => '#f9f9fb',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--social_bg_color',
								'callback' => [ 'sanitize_color' ],
							],
							[
								'name'     => '--social_bg_color-0-transparent',
								'callback' => [
									'return_string_if_transparent',
									[
										'transparent' => '0px',
										'opaque'      => '',
									],
								],
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
										'className' => 'avada-social-full-transparent',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'social_share_box_icon_info'         => [
						'label'       => esc_html__( 'Social Sharing Box Icons', 'Avada' ),
						'description' => '',
						'id'          => 'social_share_box_icon_info',
						'icon'        => true,
						'type'        => 'info',
					],
					'sharing_social_links_font_size'     => [
						'label'       => esc_html__( 'Sharing Box Icon Font Size', 'Avada' ),
						'description' => esc_html__( 'Controls the font size of the social icons in the social sharing boxes.', 'Avada' ),
						'id'          => 'sharing_social_links_font_size',
						'default'     => '16px',
						'type'        => 'dimension',
						'css_vars'    => [
							[
								'name'    => '--sharing_social_links_font_size',
								'element' => '.fusion-sharing-box',
							],
						],
					],
					'sharing_social_links_tooltip_placement' => [
						'label'           => esc_html__( 'Sharing Box Icons Tooltip Position', 'Avada' ),
						'description'     => esc_html__( 'Controls the tooltip position of the social icons in the social sharing boxes.', 'Avada' ),
						'id'              => 'sharing_social_links_tooltip_placement',
						'default'         => 'Top',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'top'    => esc_html__( 'Top', 'Avada' ),
							'right'  => esc_html__( 'Right', 'Avada' ),
							'bottom' => esc_html__( 'Bottom', 'Avada' ),
							'left'   => esc_html__( 'Left', 'Avada' ),
							'none'   => esc_html__( 'None', 'Avada' ),
						],
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_social_links_tooltip_placement' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_social_links_color_type'    => [
						'label'           => esc_html__( 'Sharing Box Icon Color Type', 'Avada' ),
						'description'     => esc_html__( 'Custom colors allow you to choose a color for icons and boxes. Brand colors will use the exact brand color of each network for the icons or boxes.', 'Avada' ),
						'id'              => 'sharing_social_links_color_type',
						'default'         => 'custom',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'custom' => esc_html__( 'Custom Colors', 'Avada' ),
							'brand'  => esc_html__( 'Brand Colors', 'Avada' ),
						],
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_sharing_social_links_color_type' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_social_links_icon_color'    => [
						'label'       => esc_html__( 'Sharing Box Icon Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the social icons in the social sharing boxes. This color will be used for all social icons.', 'Avada' ),
						'id'          => 'sharing_social_links_icon_color',
						'default'     => '#9ea0a4',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'sharing_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--sharing_social_links_icon_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'sharing_social_links_boxed'         => [
						'label'           => esc_html__( 'Sharing Box Icons Boxed', 'Avada' ),
						'description'     => esc_html__( 'Controls if each social icon is displayed in a small box.', 'Avada' ),
						'id'              => 'sharing_social_links_boxed',
						'default'         => '0',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_social_links_boxed' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_social_links_box_color'     => [
						'label'       => esc_html__( 'Sharing Box Icon Box Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the social icon box.', 'Avada' ),
						'id'          => 'sharing_social_links_box_color',
						'default'     => '#e8e8e8',
						'type'        => 'color-alpha',
						'required'    => [
							[
								'setting'  => 'sharing_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
							[
								'setting'  => 'sharing_social_links_color_type',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						'css_vars'    => [
							[
								'name'     => '--sharing_social_links_box_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'sharing_social_links_boxed_radius'  => [
						'label'       => esc_html__( 'Sharing Box Icon Boxed Radius', 'Avada' ),
						'description' => esc_html__( 'Controls the box radius of the social icon box.', 'Avada' ),
						'id'          => 'sharing_social_links_boxed_radius',
						'default'     => '4px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'sharing_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--sharing_social_links_boxed_radius',
								'element' => '.fusion-social-network-icon',
							],
						],
					],
					'sharing_social_links_boxed_padding' => [
						'label'       => esc_html__( 'Sharing Box Icons Boxed Padding', 'Avada' ),
						'description' => esc_html__( 'Controls the interior padding of the social icon box.', 'Avada' ),
						'id'          => 'sharing_social_links_boxed_padding',
						'default'     => '8px',
						'type'        => 'dimension',
						'required'    => [
							[
								'setting'  => 'sharing_social_links_boxed',
								'operator' => '==',
								'value'    => '1',
							],
						],
						'css_vars'    => [
							[
								'name'    => '--sharing_social_links_boxed_padding',
								'element' => '.fusion-sharing-box',
							],
						],
					],
					'social_share_box_links_title'       => [
						'label'       => esc_html__( 'Sharing Box Links', 'Avada' ),
						'description' => '',
						'id'          => 'social_share_box_links_title',
						'icon'        => true,
						'type'        => 'info',
					],
					'sharing_facebook'                   => [
						'label'           => esc_html__( 'Facebook', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Facebook', 'Avada' ) ),
						'id'              => 'sharing_facebook',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_facebook' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_twitter'                    => [
						'label'           => esc_html__( 'Twitter', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Twitter', 'Avada' ) ),
						'id'              => 'sharing_twitter',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_twitter' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_reddit'                     => [
						'label'           => esc_html__( 'Reddit', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Reddit', 'Avada' ) ),
						'id'              => 'sharing_reddit',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_reddit' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_linkedin'                   => [
						'label'           => esc_html__( 'LinkedIn', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'LinkedIn', 'Avada' ) ),
						'id'              => 'sharing_linkedin',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_linkedin' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_whatsapp'                   => [
						'label'           => esc_html__( 'WhatsApp', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'WhatsApp', 'Avada' ) ),
						'id'              => 'sharing_whatsapp',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_whatsapp' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_tumblr'                     => [
						'label'           => esc_html__( 'Tumblr', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Tumblr', 'Avada' ) ),
						'id'              => 'sharing_tumblr',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_tumblr' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_pinterest'                  => [
						'label'           => esc_html__( 'Pinterest', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Pinterest', 'Avada' ) ),
						'id'              => 'sharing_pinterest',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_pinterest' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_vk'                         => [
						'label'           => esc_html__( 'VK', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'VK', 'Avada' ) ),
						'id'              => 'sharing_vk',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_vk' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
					'sharing_email'                      => [
						'label'           => esc_html__( 'Email', 'Avada' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'Avada' ), esc_html__( 'Email', 'Avada' ) ),
						'id'              => 'sharing_email',
						'default'         => '1',
						'type'            => 'switch',
						'partial_refresh' => [

							// Partial refresh for the sharingbox.
							'sharingbox_sharing_email' => [
								'selector'              => '.fusion-sharing-box.fusion-single-sharing-box',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'sharingbox' ],
								'success_trigger_event' => 'fusionInitTooltips',
							],
						],
					],
				],
			],
		],
	];

	return $sections;

}
