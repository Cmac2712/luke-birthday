<?php // phpcs:disable WordPress.Files.FileName
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
 * Social Media
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_social_media( $sections ) {
	$sections['social_media'] = [
		'label'    => esc_html__( 'Social Media', 'fusion-builder' ),
		'id'       => 'heading_social_media',
		'priority' => 18,
		'icon'     => 'el-icon-share-alt',
		'alt_icon' => 'fusiona-link',
		'fields'   => [
			'social_media_icons_section' => [
				'label'  => esc_html__( 'Social Media Icons', 'fusion-builder' ),
				'id'     => 'social_media_icons_section',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'social_media_icons_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> This tab controls the social networks that display in the header and footer and which can also be used in the social links widget.. Add the network of your choice along with your unique URL. Each network you wish to display must be added here to show up in the header and footer. These settings do not control the avada social widget, social link element or person element.', 'Avada' ) . '</div>',
						'id'          => 'social_media_icons_important_note_info',
						'type'        => 'custom',
					],
					'social_media_icons' => [
						'label'           => esc_html__( 'Social Media Icons / Links', 'fusion-builder' ),
						'description'     => esc_html__( 'Social media links use a repeater field and allow one network per field. Click the "Add" button to add additional fields.', 'fusion-builder' ),
						'id'              => 'social_media_icons',
						'default'         => [],
						'type'            => 'repeater',
						'bind_title'      => 'icon',
						'limit'           => 50,
						'fields'          => [
							'icon'          => [
								'id'          => 'icon',
								'type'        => 'select',
								'label'       => esc_html__( 'Social Network', 'fusion-builder' ),
								'description' => esc_html__( 'Select a social network to automatically add its icon', 'fusion-builder' ),
								'default'     => 'none',
								'choices'     => Fusion_Data::fusion_social_icons( true, false ),
							],
							'url'           => [
								'id'          => 'url',
								'type'        => 'text',
								'label'       => esc_html__( 'Custom Link', 'fusion-builder' ),
								'description' => esc_html__( 'Insert your custom link here', 'fusion-builder' ),
								'default'     => '',
							],
							'custom_title'  => [
								'id'          => 'custom_title',
								'type'        => 'text',
								'label'       => esc_html__( 'Custom Icon Title', 'fusion-builder' ),
								'description' => esc_html__( 'Insert your custom link here', 'fusion-builder' ),
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
								'label'       => esc_html__( 'Choose the image you want to use as icon', 'fusion-builder' ),
								'description' => esc_html__( 'Upload your custom icon', 'fusion-builder' ),
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
						'edit_shortcut'   => [
							'header_social_links_boxed' => [
								'selector'   => '.fusion-header-wrapper',
								'aria_label' => esc_html__( 'Edit Header Layout', 'fusion-builder' ),
							],
						],
					],
				],
			],
			'heading_social_sharing_box' => [
				'label'  => esc_html__( 'Social Sharing Box', 'fusion-builder' ),
				'id'     => 'heading_social_sharing_box',
				'icon'   => true,
				'type'   => 'sub-section',
				'fields' => [
					'sharing_social_tagline'             => [
						'label'           => esc_html__( 'Sharing Box Tagline', 'fusion-builder' ),
						'description'     => esc_html__( 'Insert a tagline for the social sharing boxes.', 'fusion-builder' ),
						'id'              => 'sharing_social_tagline',
						'default'         => esc_html__( 'Share This Story, Choose Your Platform!', 'fusion-builder' ),
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
						'label'       => esc_html__( 'Sharing Box Tagline Text Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the tagline text in the social sharing boxes.', 'fusion-builder' ),
						'id'          => 'sharing_box_tagline_text_color',
						'default'     => '#333333',
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
						'label'       => esc_html__( 'Sharing Box Background Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the background color of the social sharing boxes.', 'fusion-builder' ),
						'id'          => 'social_bg_color',
						'default'     => '#f6f6f6',
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
					],
					'social_share_box_icon_info'         => [
						'label'       => esc_html__( 'Social Sharing Box Icons', 'fusion-builder' ),
						'description' => '',
						'id'          => 'social_share_box_icon_info',
						'icon'        => true,
						'type'        => 'info',
					],
					'sharing_social_links_font_size'     => [
						'label'       => esc_html__( 'Sharing Box Icon Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the social icons in the social sharing boxes.', 'fusion-builder' ),
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
						'label'           => esc_html__( 'Sharing Box Icons Tooltip Position', 'fusion-builder' ),
						'description'     => esc_html__( 'Controls the tooltip position of the social icons in the social sharing boxes.', 'fusion-builder' ),
						'id'              => 'sharing_social_links_tooltip_placement',
						'default'         => 'Top',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'top'    => esc_html__( 'Top', 'fusion-builder' ),
							'right'  => esc_html__( 'Right', 'fusion-builder' ),
							'bottom' => esc_html__( 'Bottom', 'fusion-builder' ),
							'left'   => esc_html__( 'Left', 'fusion-builder' ),
							'none'   => esc_html__( 'None', 'fusion-builder' ),
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
						'label'           => esc_html__( 'Sharing Box Icon Color Type', 'fusion-builder' ),
						'description'     => esc_html__( 'Custom colors allow you to choose a color for icons and boxes. Brand colors will use the exact brand color of each network for the icons or boxes.', 'fusion-builder' ),
						'id'              => 'sharing_social_links_color_type',
						'default'         => 'custom',
						'type'            => 'radio-buttonset',
						'choices'         => [
							'custom' => esc_html__( 'Custom Colors', 'fusion-builder' ),
							'brand'  => esc_html__( 'Brand Colors', 'fusion-builder' ),
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
						'label'       => esc_html__( 'Sharing Box Icon Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the social icons in the social sharing boxes. This color will be used for all social icons.', 'fusion-builder' ),
						'id'          => 'sharing_social_links_icon_color',
						'default'     => '#bebdbd',
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
						'label'           => esc_html__( 'Sharing Box Icons Boxed', 'fusion-builder' ),
						'description'     => esc_html__( 'Controls if each social icon is displayed in a small box.', 'fusion-builder' ),
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
						'label'       => esc_html__( 'Sharing Box Icon Box Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the social icon box.', 'fusion-builder' ),
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
						'label'       => esc_html__( 'Sharing Box Icon Boxed Radius', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the box radius of the social icon box.', 'fusion-builder' ),
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
						'label'       => esc_html__( 'Sharing Box Icons Boxed Padding', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the interior padding of the social icon box.', 'fusion-builder' ),
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
								'name' => '--sharing_social_links_boxed_padding',
							],
						],
					],
					'social_share_box_links_title'       => [
						'label'       => esc_html__( 'Sharing Box Links', 'fusion-builder' ),
						'description' => '',
						'id'          => 'social_share_box_links_title',
						'icon'        => true,
						'type'        => 'info',
					],
					'sharing_facebook'                   => [
						'label'           => esc_html__( 'Facebook', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Facebook', 'Avada' ) ),
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
						'label'           => esc_html__( 'Twitter', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Twitter', 'Avada' ) ),
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
						'label'           => esc_html__( 'Reddit', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Reddit', 'Avada' ) ),
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
						'label'           => esc_html__( 'LinkedIn', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'LinkedIn', 'Avada' ) ),
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
						'label'           => esc_html__( 'WhatsApp', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'WhatsApp', 'Avada' ) ),
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
						'label'           => esc_html__( 'Tumblr', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Tumblr', 'Avada' ) ),
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
						'label'           => esc_html__( 'Pinterest', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Pinterest', 'Avada' ) ),
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
						'label'           => esc_html__( 'VK', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'VK', 'Avada' ) ),
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
						'label'           => esc_html__( 'Email', 'fusion-builder' ),
						/* translators: Social Network name. */
						'description'     => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Email', 'Avada' ) ),
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
