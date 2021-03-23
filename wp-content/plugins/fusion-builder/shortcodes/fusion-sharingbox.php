<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_sharing' ) ) {

	if ( ! class_exists( 'FusionSC_SharingBox' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_SharingBox extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $args;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_sharingbox-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_sharingbox-shortcode-tagline', [ $this, 'tagline_attr' ] );
				add_filter( 'fusion_attr_sharingbox-shortcode-social-networks', [ $this, 'social_networks_attr' ] );
				add_filter( 'fusion_attr_sharingbox-shortcode-icon', [ $this, 'icon_attr' ] );

				add_shortcode( 'fusion_sharing', [ $this, 'render' ] );

			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_defaults() {
				global $post;

				$fusion_settings = fusion_get_fusion_settings();

				return [
					'hide_on_mobile'     => fusion_builder_default_visibility( 'string' ),
					'class'              => '',
					'id'                 => '',
					'backgroundcolor'    => strtolower( $fusion_settings->get( 'social_bg_color' ) ),
					'description'        => isset( $post->post_content ) ? fusion_get_content_stripped_and_excerpted( 55, $post->post_content ) : '',
					'color_type'         => $fusion_settings->get( 'sharing_social_links_color_type' ),
					'icon_colors'        => strtolower( $fusion_settings->get( 'sharing_social_links_icon_color' ) ),
					'box_colors'         => strtolower( $fusion_settings->get( 'sharing_social_links_box_color' ) ),
					'icons_boxed'        => ( 1 == $fusion_settings->get( 'sharing_social_links_boxed' ) ) ? 'yes' : 'no', // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					'icons_boxed_radius' => fusion_library()->sanitize->size( $fusion_settings->get( 'sharing_social_links_boxed_radius' ) ),
					'link'               => get_permalink(),
					'pinterest_image'    => '',
					'pinterest_image_id' => '',
					'social_networks'    => self::get_options_settings(),
					'tagline'            => '',
					'tagline_color'      => strtolower( $fusion_settings->get( 'sharing_box_tagline_text_color' ) ),
					'title'              => get_the_title(),
					'tooltip_placement'  => strtolower( $fusion_settings->get( 'sharing_social_links_tooltip_placement' ) ),
				];
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'social_bg_color'                   => 'backgroundcolor',
					'sharing_social_links_color_type'   => 'color_type',
					'sharing_social_links_boxed'        => [
						'param'    => 'icons_boxed',
						'callback' => 'toYes',
					],
					'sharing_social_links_boxed_radius' => 'icons_boxed_radius',
					'sharing_box_tagline_text_color'    => 'tagline_color',
					'sharing_social_links_tooltip_placement' => 'tooltip_placement',
					'sharing_social_links_box_color'    => 'box_colors',
					'sharing_social_links_icon_color'   => 'icon_colors',

					// These are used to update social networks array.
					'sharing_email'                     => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_vk'                        => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_pinterest'                 => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_tumblr'                    => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_whatsapp'                  => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_reddit'                    => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_linkedin'                  => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_twitter'                   => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
					'sharing_facebook'                  => [
						'param'    => 'social_networks',
						'callback' => 'createSocialNetworks',
					],
				];
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = fusion_get_fusion_settings();
				return [
					'linktarget' => $fusion_settings->get( 'social_icons_new' ),
				];
			}

			/**
			 * Maps settings to extra variables.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function settings_to_extras() {

				return [
					'social_icons_new' => 'linktarget',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				global $fusion_settings;

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_sharing' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_sharing', $args );

				$defaults['icons_boxed_radius'] = FusionBuilder::validate_shortcode_attr_value( $defaults['icons_boxed_radius'], 'px' );

				$defaults['description'] = fusion_decode_if_needed( $defaults['description'] );

				extract( $defaults );

				$this->args = $defaults;

				$use_brand_colors = false;
				if ( 'brand' === $color_type ) {
					$use_brand_colors = true;
					// Get a list of all the available social networks.
					$social_icon_boxed_colors         = Fusion_Data::fusion_social_icons( false, true );
					$social_icon_boxed_colors['mail'] = [
						'label' => esc_attr__( 'Email Address', 'fusion-builder' ),
						'color' => '#000000',
					];
				}

				$icons           = '';
				$icon_colors     = explode( '|', $icon_colors );
				$box_colors      = explode( '|', $box_colors );
				$social_networks = explode( '|', $social_networks );

				$num_of_icon_colors    = count( $icon_colors );
				$num_of_box_colors     = count( $box_colors );
				$social_networks_count = count( $social_networks );

				for ( $i = 0; $i < $social_networks_count; $i++ ) {
					if ( 1 === $num_of_icon_colors ) {
						$icon_colors[ $i ] = $icon_colors[0];
					}

					if ( 1 === $num_of_box_colors ) {
						$box_colors[ $i ] = $box_colors[0];
					}

					$network = $social_networks[ $i ];

					if ( $use_brand_colors ) {
						$icon_options = [
							'social_network' => $network,
							'icon_color'     => ( 'yes' === $icons_boxed ) ? '#ffffff' : $social_icon_boxed_colors[ $network ]['color'],
							'box_color'      => ( 'yes' === $icons_boxed ) ? $social_icon_boxed_colors[ $network ]['color'] : '',
						];

					} else {
						$icon_options = [
							'social_network' => $network,
							'icon_color'     => $i < count( $icon_colors ) ? $icon_colors[ $i ] : '',
							'box_color'      => $i < count( $box_colors ) ? $box_colors[ $i ] : '',
						];
					}

					$icons .= '<a ' . FusionBuilder::attributes( 'sharingbox-shortcode-icon', $icon_options ) . '></a>';
				}

				$html  = '<div ' . FusionBuilder::attributes( 'sharingbox-shortcode' ) . '>';
				$html .= '<h4 ' . FusionBuilder::attributes( 'sharingbox-shortcode-tagline' ) . '>' . $tagline . '</h4>';
				$html .= '<div ' . FusionBuilder::attributes( 'sharingbox-shortcode-social-networks' ) . '>';
				$html .= $icons;
				$html .= '</div>';
				$html .= '</div>';

				return apply_filters( 'fusion_element_sharingbox_content', $html, $args );

			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				$attr = fusion_builder_visibility_atts(
					$this->args['hide_on_mobile'],
					[
						'class' => 'share-box fusion-sharing-box',
					]
				);

				if ( 'yes' === $this->args['icons_boxed'] ) {
					$attr['class'] .= ' boxed-icons';
				}

				if ( $this->args['backgroundcolor'] ) {
					$attr['style'] = 'background-color:' . $this->args['backgroundcolor'] . ';';

					if ( fusion_is_color_transparent( $this->args['backgroundcolor'] ) ) {
						$attr['style'] .= 'padding:0;';
					}
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				$attr['data-title']       = $this->args['title'];
				$attr['data-description'] = $this->args['description'];
				$attr['data-link']        = $this->args['link'];
				$attr['data-image']       = $this->args['pinterest_image'];

				return $attr;

			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function tagline_attr() {

				$attr = [
					'class' => 'tagline',
				];

				if ( $this->args['tagline_color'] ) {
					$attr['style'] = 'color:' . $this->args['tagline_color'] . ';';
				}

				return $attr;

			}

			/**
			 * Builds the social networks attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function social_networks_attr() {

				$attr = [
					'class' => 'fusion-social-networks',
				];

				if ( 'yes' === $this->args['icons_boxed'] ) {
					$attr['class'] .= ' boxed-icons';
				}

				if ( ! $this->args['tagline'] ) {
					$attr['style'] = 'text-align: inherit;';
				}

				return $attr;

			}

			/**
			 * Builds the icon attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @param array $args The arguments array.
			 * @return array
			 */
			public function icon_attr( $args ) {
				global $fusion_settings;

				if ( ! empty( $this->args['pinterest_image'] ) ) {

					$image_data = fusion_library()->images->get_attachment_data_by_helper( $this->args['pinterest_image_id'], $this->args['pinterest_image'] );

					if ( $image_data['url'] ) {
						$this->args['pinterest_image'] = $image_data['url'];
					}
				}

				$description = $this->args['description'];
				$link        = $this->args['link'];
				$title       = $this->args['title'];
				$image       = rawurlencode( $this->args['pinterest_image'] );

				$attr = [
					'class' => 'fusion-social-network-icon fusion-tooltip fusion-' . $args['social_network'] . ' fusion-icon-' . $args['social_network'],
				];

				$social_link = '';
				switch ( $args['social_network'] ) {
					case 'facebook':
						$social_link = 'https://m.facebook.com/sharer.php?u=' . $link;
						// TODO: Use Jetpack's implementation instead.
						if ( ! wp_is_mobile() ) {
							$social_link = 'https://www.facebook.com/sharer.php?u=' . rawurlencode( $link ) . '&t=' . rawurlencode( $title );
						}
						break;

					case 'twitter':
						$social_link = 'https://twitter.com/share?text=' . rawurlencode( html_entity_decode( $title, ENT_COMPAT, 'UTF-8' ) ) . '&url=' . rawurlencode( $link );
						break;
					case 'linkedin':
						$social_link = 'https://www.linkedin.com/shareArticle?mini=true&url=' . rawurlencode( $link ) . '&title=' . rawurlencode( $title ) . '&summary=' . rawurlencode( $description );
						break;
					case 'reddit':
						$social_link = 'http://reddit.com/submit?url=' . $link . '&amp;title=' . $title;
						break;
					case 'whatsapp':
						$social_link = 'https://api.whatsapp.com/send?text=' . rawurlencode( $link );
						break;
					case 'tumblr':
						$social_link = 'http://www.tumblr.com/share/link?url=' . rawurlencode( $link ) . '&amp;name=' . rawurlencode( $title ) . '&amp;description=' . rawurlencode( $description );
						break;

					case 'pinterest':
						$social_link = 'http://pinterest.com/pin/create/button/?url=' . rawurlencode( $link ) . '&amp;description=' . rawurlencode( $description ) . '&amp;media=' . $image;
						break;
					case 'vk':
						$social_link = 'http://vkontakte.ru/share.php?url=' . rawurlencode( $link ) . '&amp;title=' . rawurlencode( $title ) . '&amp;description=' . rawurlencode( $description );
						break;
					case 'mail':
						$social_link = 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $link );
						break;
				}

				$attr['href']   = $social_link;
				$attr['target'] = ( $fusion_settings->get( 'social_icons_new' ) && 'mail' !== $args['social_network'] ) ? '_blank' : '_self';

				if ( '_blank' === $attr['target'] && 'facebook' !== $args['social_network'] ) {
					$attr['rel'] = 'noopener noreferrer';
				}

				if ( $fusion_settings->get( 'nofollow_social_links' ) ) {
					$attr['rel'] = ( isset( $attr['rel'] ) ) ? $attr['rel'] . ' nofollow' : 'nofollow';
				}

				$attr['style'] = ( $args['icon_color'] ) ? 'color:' . $args['icon_color'] . ';' : '';

				if ( isset( $this->args['icons_boxed'] ) && 'yes' === $this->args['icons_boxed'] && $args['box_color'] ) {
					$attr['style'] .= 'background-color:' . $args['box_color'] . ';border-color:' . $args['box_color'] . ';';
				}

				if ( 'yes' === $this->args['icons_boxed'] && $this->args['icons_boxed_radius'] || '0' === $this->args['icons_boxed_radius'] ) {
					if ( 'round' === $this->args['icons_boxed_radius'] ) {
						$this->args['icons_boxed_radius'] = '50%';
					}
					$attr['style'] .= 'border-radius:' . $this->args['icons_boxed_radius'] . ';';
				}

				$attr['data-placement'] = $this->args['tooltip_placement'];
				$tooltip                = $args['social_network'];
				if ( 'youtube' === $tooltip ) {
					$tooltip = 'YouTube';
				} elseif ( 'linkedin' === $tooltip ) {
					$tooltip = 'LinkedIn';
				}

				$attr['data-title'] = ucfirst( $tooltip );
				$attr['title']      = ucfirst( $tooltip );
				$attr['aria-label'] = ucfirst( $tooltip );

				if ( 'none' !== $this->args['tooltip_placement'] ) {
					$attr['data-toggle'] = 'tooltip';
				}

				return $attr;

			}

			/**
			 * Gets the options from the theme.
			 *
			 * @access public
			 * @since 1.0
			 * @return string
			 */
			public static function get_options_settings() {

				global $fusion_settings;

				$social_media = [];

				if ( $fusion_settings->get( 'sharing_facebook' ) ) {
					$social_media[] = [
						'network' => 'facebook',
					];
				}

				if ( $fusion_settings->get( 'sharing_twitter' ) ) {
					$social_media[] = [
						'network' => 'twitter',
					];
				}

				if ( $fusion_settings->get( 'sharing_linkedin' ) ) {
					$social_media[] = [
						'network' => 'linkedin',
					];
				}

				if ( $fusion_settings->get( 'sharing_reddit' ) ) {
					$social_media[] = [
						'network' => 'reddit',
					];
				}

				if ( $fusion_settings->get( 'sharing_whatsapp' ) ) {
					$social_media[] = [
						'network' => 'whatsapp',
					];
				}

				if ( $fusion_settings->get( 'sharing_tumblr' ) ) {
					$social_media[] = [
						'network' => 'tumblr',
					];
				}

				if ( $fusion_settings->get( 'sharing_pinterest' ) ) {
					$social_media[] = [
						'network' => 'pinterest',
					];
				}

				if ( $fusion_settings->get( 'sharing_vk' ) ) {
					$social_media[] = [
						'network' => 'vk',
					];
				}

				if ( $fusion_settings->get( 'sharing_email' ) ) {
					$social_media[] = [
						'network' => 'mail',
					];
				}

				$networks = [];

				foreach ( $social_media as $network ) {
					$networks[] = $network['network'];
				}
				return implode( '|', $networks );

			}

			/**
			 * Adds settings to element options panel.
			 *
			 * @access public
			 * @since 1.1
			 * @return array $sections Sharing Box settings.
			 */
			public function add_options() {

				return [
					'sharing_box_shortcode_section' => [
						'label'       => esc_html__( 'Sharing Box', 'fusion-builder' ),
						'id'          => 'sharing_box_shortcode_section',
						'description' => '',
						'type'        => 'accordion',
						'icon'        => 'fusiona-share2',
						'fields'      => [
							'sharing_social_tagline'       => [
								'label'       => esc_html__( 'Sharing Box Tagline', 'fusion-builder' ),
								'description' => esc_html__( 'Insert a tagline for the social sharing boxes.', 'fusion-builder' ),
								'id'          => 'sharing_social_tagline',
								'default'     => esc_html__( 'Share This Story, Choose Your Platform!', 'fusion-builder' ),
								'type'        => 'text',
							],
							'sharing_box_tagline_text_color' => [
								'label'       => esc_html__( 'Sharing Box Tagline Text Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the tagline text in the social sharing boxes.', 'fusion-builder' ),
								'id'          => 'sharing_box_tagline_text_color',
								'default'     => '#333333',
								'type'        => 'color-alpha',
							],
							'social_bg_color'              => [
								'label'       => esc_html__( 'Sharing Box Background Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the background color of the social sharing boxes.', 'fusion-builder' ),
								'id'          => 'social_bg_color',
								'default'     => '#f6f6f6',
								'type'        => 'color-alpha',
							],
							'social_share_box_icon_info'   => [
								'label'       => esc_html__( 'Social Sharing Box Icons', 'fusion-builder' ),
								'description' => '',
								'id'          => 'social_share_box_icon_info',
								'icon'        => true,
								'type'        => 'info',
							],
							'sharing_social_links_font_size' => [
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
								'label'       => esc_html__( 'Sharing Box Icons Tooltip Position', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the tooltip position of the social icons in the social sharing boxes.', 'fusion-builder' ),
								'id'          => 'sharing_social_links_tooltip_placement',
								'default'     => 'Top',
								'type'        => 'radio-buttonset',
								'choices'     => [
									'top'    => esc_html__( 'Top', 'fusion-builder' ),
									'right'  => esc_html__( 'Right', 'fusion-builder' ),
									'bottom' => esc_html__( 'Bottom', 'fusion-builder' ),
									'left'   => esc_html__( 'Left', 'fusion-builder' ),
									'none'   => esc_html__( 'None', 'fusion-builder' ),
								],
							],
							'sharing_social_links_color_type' => [
								'label'       => esc_html__( 'Sharing Box Icon Color Type', 'fusion-builder' ),
								'description' => esc_html__( 'Custom colors allow you to choose a color for icons and boxes. Brand colors will use the exact brand color of each network for the icons or boxes.', 'fusion-builder' ),
								'id'          => 'sharing_social_links_color_type',
								'default'     => 'custom',
								'type'        => 'radio-buttonset',
								'choices'     => [
									'custom' => esc_html__( 'Custom Colors', 'fusion-builder' ),
									'brand'  => esc_html__( 'Brand Colors', 'fusion-builder' ),
								],
							],
							'sharing_social_links_icon_color' => [
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
							],
							'sharing_social_links_boxed'   => [
								'label'       => esc_html__( 'Sharing Box Icons Boxed', 'fusion-builder' ),
								'description' => esc_html__( 'Controls if each social icon is displayed in a small box.', 'fusion-builder' ),
								'id'          => 'sharing_social_links_boxed',
								'default'     => '0',
								'type'        => 'switch',
							],
							'sharing_social_links_box_color' => [
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
							],
							'sharing_social_links_boxed_radius' => [
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
							'social_share_box_links_title' => [
								'label'       => esc_html__( 'Sharing Box Links', 'fusion-builder' ),
								'description' => '',
								'id'          => 'social_share_box_links_title',
								'icon'        => true,
								'type'        => 'info',
							],
							'sharing_facebook'             => [
								'label'       => esc_html__( 'Facebook', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Facebook', 'fusion-builder' ) ),
								'id'          => 'sharing_facebook',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_twitter'              => [
								'label'       => esc_html__( 'Twitter', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Twitter', 'fusion-builder' ) ),
								'id'          => 'sharing_twitter',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_reddit'               => [
								'label'       => esc_html__( 'Reddit', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Reddit', 'fusion-builder' ) ),
								'id'          => 'sharing_reddit',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_linkedin'             => [
								'label'       => esc_html__( 'LinkedIn', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'LinkedIn', 'fusion-builder' ) ),
								'id'          => 'sharing_linkedin',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_tumblr'               => [
								'label'       => esc_html__( 'Tumblr', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Tumblr', 'fusion-builder' ) ),
								'id'          => 'sharing_tumblr',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_pinterest'            => [
								'label'       => esc_html__( 'Pinterest', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Pinterest', 'fusion-builder' ) ),
								'id'          => 'sharing_pinterest',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_vk'                   => [
								'label'       => esc_html__( 'VK', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'VK', 'fusion-builder' ) ),
								'id'          => 'sharing_vk',
								'default'     => '1',
								'type'        => 'switch',
							],
							'sharing_email'                => [
								'label'       => esc_html__( 'Email', 'fusion-builder' ),
								/* translators: The network-name. */
								'description' => sprintf( esc_html__( 'Turn on to display %s in the social share box.', 'fusion-builder' ), esc_html__( 'Email', 'fusion-builder' ) ),
								'id'          => 'sharing_email',
								'default'     => '1',
								'type'        => 'switch',
							],
						],
					],
				];
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 1.1
			 * @return void
			 */
			public function add_scripts() {
				Fusion_Dynamic_JS::enqueue_script( 'fusion-tooltip' );
				Fusion_Dynamic_JS::enqueue_script( 'fusion-sharing-box' );
			}
		}
	}

	new FusionSC_SharingBox();

}

/**
 * Map shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_element_sharing_box() {

	global $fusion_settings;

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_SharingBox',
			[
				'name'          => esc_attr__( 'Sharing Box', 'fusion-builder' ),
				'shortcode'     => 'fusion_sharing',
				'icon'          => 'fusiona-share2',
				'preview'       => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-sharingbox-preview.php',
				'preview_id'    => 'fusion-builder-block-module-sharingbox-preview-template',
				'help_url'      => 'https://theme-fusion.com/documentation/fusion-builder/elements/sharing-box-element/',
				'inline_editor' => true,
				'params'        => [
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tagline', 'fusion-builder' ),
						'description' => esc_attr__( 'The title tagline that will display.', 'fusion-builder' ),
						'param_name'  => 'tagline',
						'value'       => esc_attr__( 'Share This Story, Choose Your Platform!', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Tagline Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color.', 'fusion-builder' ),
						'param_name'  => 'tagline_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'sharing_box_tagline_text_color' ),
						'dependency'  => [
							[
								'element'  => 'tagline',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color.', 'fusion-builder' ),
						'param_name'  => 'backgroundcolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'social_bg_color' ),
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Title', 'fusion-builder' ),
						'description'  => __( 'The post title that will be shared. Leave empty to use title of current post. <strong>Note:</strong> Some of the social networks will ignore this option and will instead auto pull the post title based on the shared link.', 'fusion-builder' ),
						'param_name'   => 'title',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'         => 'link_selector',
						'heading'      => esc_attr__( 'Link to Share', 'fusion-builder' ),
						'description'  => esc_attr__( 'The link that will be shared. Leave empty to use URL of current post.', 'fusion-builder' ),
						'param_name'   => 'link',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'         => 'raw_textarea',
						'heading'      => esc_attr__( 'Description', 'fusion-builder' ),
						'description'  => __( 'The description that will be shared. Leave empty to use excerpt of current post. <strong>Note:</strong> Some of the social networks do not offer description in their sharing options and others might ignore it and will instead auto pull the post excerpt based on the shared link.', 'fusion-builder' ),
						'param_name'   => 'description',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Boxed Social Icons', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the color type of social icons. Brand colors will use the exact brand color of each network for the icons or boxes.', 'fusion-builder' ),
						'param_name'  => 'icons_boxed',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Social Icon Box Radius', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the radius of the boxed icons. In pixels (px), ex: 1px, or "round". ', 'fusion-builder' ),
						'param_name'  => 'icons_boxed_radius',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'icons_boxed',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Social Icons Color Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to get a boxed icons. Choose default for theme option selection.', 'fusion-builder' ),
						'param_name'  => 'color_type',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom Colors', 'fusion-builder' ),
							'brand'  => esc_attr__( 'Brand Colors', 'fusion-builder' ),
						],
						'default'     => '',
					],
					[
						'type'        => 'textarea',
						'heading'     => esc_attr__( 'Social Icon Custom Colors', 'fusion-builder' ),
						'description' => esc_attr__( 'Specify the color of social icons. ', 'fusion-builder' ),
						'param_name'  => 'icon_colors',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'color_type',
								'value'    => 'brand',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textarea',
						'heading'     => esc_attr__( 'Social Icon Custom Box Colors', 'fusion-builder' ),
						'description' => esc_attr__( 'Specify the box color of social icons. ', 'fusion-builder' ),
						'param_name'  => 'box_colors',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'icons_boxed',
								'value'    => 'no',
								'operator' => '!=',
							],
							[
								'element'  => 'color_type',
								'value'    => 'brand',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Social Icon Tooltip Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the display position for tooltips. Choose default for theme option selection.', 'fusion-builder' ),
						'param_name'  => 'tooltip_placement',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'top'    => esc_attr__( 'Top', 'fusion-builder' ),
							'bottom' => esc_attr__( 'Bottom', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'Right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => '',
					],
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Pinterest Sharing Image', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose an image to share on pinterest.', 'fusion-builder' ),
						'param_name'  => 'pinterest_image',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Pinterest Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Pinterest Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'pinterest_image_id',
						'value'       => '',
						'hidden'      => true,
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_sharing_box' );
