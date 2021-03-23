<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_person' ) ) {

	if ( ! class_exists( 'FusionSC_Person' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_Person extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $args;

			/**
			 * The person image data.
			 *
			 * @access private
			 * @since 1.0
			 * @var false|array
			 */
			private $person_image_data = false;

			/**
			 * The person element counter.
			 *
			 * @access private
			 * @since 1.7.1
			 * @var int
			 */
			private $person_counter = 1;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_person-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_person-shortcode-image-wrapper', [ $this, 'image_wrapper_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-image-container', [ $this, 'image_container_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-href', [ $this, 'href_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-img', [ $this, 'img_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-author', [ $this, 'author_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-social-networks', [ $this, 'social_networks_attr' ] );
				add_filter( 'fusion_attr_person-shortcode-icon', [ $this, 'icon_attr' ] );
				add_filter( 'fusion_attr_person-desc', [ $this, 'desc_attr' ] );

				add_shortcode( 'fusion_person', [ $this, 'render' ] );

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

				$fusion_settings = fusion_get_fusion_settings();

				$social_icon_order  = '';
				$social_media_icons = $fusion_settings->get( 'social_media_icons' );
				if ( is_array( $social_media_icons ) && isset( $social_media_icons['icon'] ) && is_array( $social_media_icons['icon'] ) ) {
					$social_icon_order = implode( '|', $social_media_icons['icon'] );
				}
				return [
					'hide_on_mobile'           => fusion_builder_default_visibility( 'string' ),
					'class'                    => '',
					'id'                       => '',
					'lightbox'                 => 'no',
					'linktarget'               => '_self',
					'name'                     => '',
					'social_icon_boxed'        => ( 1 == $fusion_settings->get( 'social_links_boxed' ) ) ? 'yes' : $fusion_settings->get( 'social_links_boxed' ), // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					'social_icon_boxed_colors' => strtolower( $fusion_settings->get( 'social_links_box_color' ) ),
					'social_icon_boxed_radius' => fusion_library()->sanitize->size( $fusion_settings->get( 'social_links_boxed_radius' ) ),
					'social_icon_color_type'   => $fusion_settings->get( 'social_links_color_type' ),
					'social_icon_colors'       => strtolower( $fusion_settings->get( 'social_links_icon_color' ) ),
					'social_icon_font_size'    => fusion_library()->sanitize->size( $fusion_settings->get( 'social_links_font_size' ) ),
					'social_icon_order'        => $social_icon_order,
					'social_icon_padding'      => fusion_library()->sanitize->size( $fusion_settings->get( 'social_links_boxed_padding' ) ),
					'social_icon_tooltip'      => strtolower( $fusion_settings->get( 'social_links_tooltip_placement' ) ),
					'pic_bordercolor'          => strtolower( $fusion_settings->get( 'person_border_color' ) ),
					'pic_borderradius'         => intval( $fusion_settings->get( 'person_border_radius' ) ) . 'px',
					'pic_bordersize'           => $fusion_settings->get( 'person_border_size' ),
					'pic_link'                 => '',
					'pic_style'                => $fusion_settings->get( 'person_pic_style' ),
					'pic_style_blur'           => $fusion_settings->get( 'person_pic_style_blur' ),
					'pic_style_color'          => strtolower( $fusion_settings->get( 'person_style_color' ) ),
					'show_custom'              => 'no',
					'picture'                  => '',
					'picture_id'               => '',
					'title'                    => '',
					'hover_type'               => 'none',
					'background_color'         => strtolower( $fusion_settings->get( 'person_background_color' ) ),
					'content_alignment'        => strtolower( $fusion_settings->get( 'person_alignment' ) ),
					'icon_position'            => strtolower( $fusion_settings->get( 'person_icon_position' ) ),
					'facebook'                 => '',
					'twitch'                   => '',
					'twitter'                  => '',
					'instagram'                => '',
					'linkedin'                 => '',
					'dribbble'                 => '',
					'rss'                      => '',
					'youtube'                  => '',
					'pinterest'                => '',
					'flickr'                   => '',
					'vimeo'                    => '',
					'tumblr'                   => '',
					'discord'                  => '',
					'digg'                     => '',
					'blogger'                  => '',
					'skype'                    => '',
					'mixer'                    => '',
					'myspace'                  => '',
					'deviantart'               => '',
					'yahoo'                    => '',
					'reddit'                   => '',
					'forrst'                   => '',
					'paypal'                   => '',
					'dropbox'                  => '',
					'soundcloud'               => '',
					'vk'                       => '',
					'whatsapp'                 => '',
					'xing'                     => '',
					'yelp'                     => '',
					'spotify'                  => '',
					'email'                    => '',
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
					'social_links_boxed'             => 'social_icon_boxed',
					'social_links_box_color'         => 'social_icon_boxed_colors',
					'social_links_boxed_radius'      => 'social_icon_boxed_radius',
					'social_links_color_type'        => 'social_icon_color_type',
					'social_links_icon_color'        => 'social_icon_colors',
					'social_links_font_size'         => 'social_icon_font_size',
					'social_links_boxed_padding'     => 'social_icon_padding',
					'social_links_tooltip_placement' => 'social_icon_tooltip',
					'person_border_color'            => 'pic_bordercolor',
					'person_border_radius'           => 'pic_borderradius',
					'person_border_size'             => 'pic_bordersize',
					'person_style_color'             => 'pic_style_color',
					'person_pic_style'               => 'pic_style',
					'person_pic_style_blur'          => 'pic_style_blur',
					'person_background_color'        => 'background_color',
					'person_alignment'               => [
						'param'    => 'content_alignment',
						'callback' => 'toLowerCase',
					],
					'person_icon_position'           => [
						'param'    => 'icon_position',
						'callback' => 'toLowerCase',
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
					'linktarget'              => ( $fusion_settings->get( 'social_icons_new' ) ) ? '_blank' : '_self',
					'social_links_box_color'  => $fusion_settings->get( 'social_links_box_color' ),
					'social_links_icon_color' => $fusion_settings->get( 'social_links_icon_color' ),
					'social_media_icons'      => $fusion_settings->get( 'social_media_icons' ),
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
					'social_icons_new'        => 'linktarget',
					'social_links_box_color'  => 'social_links_box_color',
					'social_links_icon_color' => 'social_links_icon_color',
					'social_media_icons'      => 'social_media_icons',
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

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_person' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_person', $args );
				foreach ( $args as $key => $arg ) {
					if ( false !== strpos( $key, 'custom_' ) ) {
						$defaults[ $key ] = $arg;
					}
				}
				$defaults['pic_style_blur']           = FusionBuilder::validate_shortcode_attr_value( $defaults['pic_style_blur'], 'px' );
				$defaults['pic_bordersize']           = FusionBuilder::validate_shortcode_attr_value( $defaults['pic_bordersize'], 'px' );
				$defaults['pic_borderradius']         = FusionBuilder::validate_shortcode_attr_value( $defaults['pic_borderradius'], 'px' );
				$defaults['social_icon_boxed_radius'] = FusionBuilder::validate_shortcode_attr_value( $defaults['social_icon_boxed_radius'], 'px' );
				$defaults['social_icon_font_size']    = FusionBuilder::validate_shortcode_attr_value( $defaults['social_icon_font_size'], 'px' );
				$defaults['social_icon_padding']      = FusionBuilder::validate_shortcode_attr_value( $defaults['social_icon_padding'], 'px' );

				if ( '0px' !== $defaults['pic_borderradius'] && ! empty( $defaults['pic_borderradius'] ) && 'bottomshadow' === $defaults['pic_style'] ) {
					$defaults['pic_style'] = 'none';
				}

				if ( 'round' === $defaults['pic_borderradius'] ) {
					$defaults['pic_borderradius'] = '50%';
				}

				extract( $defaults );

				$this->args = $defaults;

				$this->args['styles'] = '';

				$stylecolor  = ( '#' === $this->args['pic_style_color'][0] ) ? Fusion_Color::new_color( $this->args['pic_style_color'] )->get_new( 'alpha', '0.3' )->to_css( 'rgba' ) : Fusion_Color::new_color( $this->args['pic_style_color'] )->to_css( 'rgba' );
				$blur        = $this->args['pic_style_blur'];
				$blur_radius = ( (int) $blur + 4 ) . 'px';

				if ( 'glow' === $pic_style ) {
					$this->args['styles'] .= "-webkit-box-shadow: 0 0 {$blur} {$stylecolor};box-shadow: 0 0 {$blur} {$stylecolor};";
				} elseif ( 'dropshadow' === $pic_style ) {
					$this->args['styles'] .= "-webkit-box-shadow: {$blur} {$blur} {$blur_radius} {$stylecolor};box-shadow: {$blur} {$blur} {$blur_radius} {$stylecolor};";
				}

				if ( $pic_borderradius ) {
					$this->args['styles'] .= '-webkit-border-radius:' . $this->args['pic_borderradius'] . ';-moz-border-radius:' . $this->args['pic_borderradius'] . ';border-radius:' . $this->args['pic_borderradius'] . ';';
				}

				$styles = '';
				if ( 'bottomshadow' === $pic_style ) {
					$styles .= '.fusion-person-' . $this->person_counter . ' .element-bottomshadow:before, .fusion-person-' . $this->person_counter . ' .element-bottomshadow:after{';
					$styles .= '-webkit-box-shadow: 0 17px 10px ' . $stylecolor . ';box-shadow: 0 17px 10px ' . $stylecolor . ';}';
				}
				if ( 'liftup' === $this->args['hover_type'] && $pic_borderradius ) {
					$styles .= '.fusion-person-' . $this->person_counter . ' .imageframe-liftup:before{';
					$styles .= '-webkit-border-radius:' . $this->args['pic_borderradius'] . ';-moz-border-radius:' . $this->args['pic_borderradius'] . ';border-radius:' . $this->args['pic_borderradius'] . ';';
				}

				if ( '' !== $styles ) {
					$styles = '<style>' . $styles . '</style>';
				}

				$inner_content = $social_icons_content = $social_icons_content_top = $social_icons_content_bottom = '';

				if ( $picture ) {
					$this->person_image_data = fusion_library()->images->get_attachment_data_by_helper( $this->args['picture_id'], $picture );

					$picture = '<img ' . FusionBuilder::attributes( 'person-shortcode-img' ) . ' />';

					fusion_library()->images->set_grid_image_meta(
						[
							'layout'  => 'large',
							'columns' => '1',
						]
					);

					if ( function_exists( 'wp_make_content_images_responsive' ) ) {
						$picture = wp_make_content_images_responsive( $picture );
					}

					$picture = fusion_library()->images->apply_lazy_loading( $picture, null, $this->args['picture_id'], 'full' );

					fusion_library()->images->set_grid_image_meta( [] );

					if ( $pic_link ) {
						$picture = '<a ' . FusionBuilder::attributes( 'person-shortcode-href' ) . '>' . $picture . '</a>';
					}

					$picture = '<div ' . FusionBuilder::attributes( 'person-shortcode-image-wrapper' ) . '><div ' . FusionBuilder::attributes( 'person-shortcode-image-container' ) . '>' . $picture . '</div></div>';
				}

				if ( $name || $title || $content ) {

					$social_networks = fusion_builder_get_social_networks( $defaults );
					$social_networks = fusion_builder_sort_social_networks( $social_networks );
					$icons           = fusion_builder_build_social_links( $social_networks, 'person-shortcode-icon', $defaults );
					if ( 0 < count( $social_networks ) ) {
						$social_icons_content_top  = '<div ' . FusionBuilder::attributes( 'person-shortcode-social-networks' ) . '>';
						$social_icons_content_top .= '<div ' . FusionBuilder::attributes( 'fusion-social-networks-wrapper' ) . '>' . $icons . '</div>';
						$social_icons_content_top .= '</div>';

						$social_icons_content_bottom  = '<div ' . FusionBuilder::attributes( 'person-shortcode-social-networks' ) . '>';
						$social_icons_content_bottom .= '<div ' . FusionBuilder::attributes( 'fusion-social-networks-wrapper' ) . '>' . $icons . '</div>';
						$social_icons_content_bottom .= '</div>';
					}

					if ( 'top' === $this->args['icon_position'] ) {
						$social_icons_content_bottom = '';
					}
					if ( 'bottom' === $this->args['icon_position'] ) {
						$social_icons_content_top = '';
					}

					$person_author_wrapper = '<div ' . FusionBuilder::attributes( 'person-author-wrapper' ) . '><span ' . FusionBuilder::attributes( 'person-name' ) . '>' . $name . '</span><span ' . FusionBuilder::attributes( 'person-title' ) . '>' . $title . '</span></div>';

					$person_author_content = $person_author_wrapper . $social_icons_content_top;
					if ( 'right' === $content_alignment ) {
						$person_author_content = $social_icons_content_top . $person_author_wrapper;
					}

					$inner_content .= '<div ' . FusionBuilder::attributes( 'person-desc' ) . '>';
					$inner_content .= '<div ' . FusionBuilder::attributes( 'person-shortcode-author' ) . '>' . $person_author_content . '</div>';
					$inner_content .= '<div ' . FusionBuilder::attributes( 'person-content fusion-clearfix' ) . '>' . do_shortcode( $content ) . '</div>';
					$inner_content .= $social_icons_content_bottom;
					$inner_content .= '</div>';

				}

				$html = '<div ' . FusionBuilder::attributes( 'person-shortcode' ) . '>' . $styles . $picture . $inner_content . '</div>';

				$this->person_counter++;

				return apply_filters( 'fusion_element_person_content', $html, $args );
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
						'class' => 'fusion-person person fusion-person-' . $this->args['content_alignment'] . ' fusion-person-' . $this->person_counter . ' fusion-person-icon-' . $this->args['icon_position'],
					]
				);

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;

			}

			/**
			 * Builds the image-container attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function image_container_attr() {

				$attr = [
					'class' => 'person-image-container',
					'style' => '',
				];

				if ( 'liftup' !== $this->args['hover_type'] ) {
					$attr['class'] .= ' hover-type-' . $this->args['hover_type'];
				}

				if ( 'glow' === $this->args['pic_style'] ) {
					$attr['class'] .= ' glow';
				} elseif ( 'dropshadow' === $this->args['pic_style'] ) {
					$attr['class'] .= ' dropshadow';
				} elseif ( 'bottomshadow' === $this->args['pic_style'] && ( 'zoomin' !== $this->args['hover_type'] || 'zoomout' !== $this->args['hover_type'] ) ) {
					$attr['class'] .= ' element-bottomshadow';
				}

				if ( $this->args['pic_borderradius'] && '0px' !== $this->args['pic_borderradius'] ) {
					$attr['class'] .= ' person-rounded-overflow';
					$attr['style'] .= '-webkit-border-radius:' . $this->args['pic_borderradius'] . ';-moz-border-radius:' . $this->args['pic_borderradius'] . ';border-radius:' . $this->args['pic_borderradius'] . ';';
				}

				if ( $this->args['pic_bordersize'] ) {
					$attr['style'] .= 'border:' . $this->args['pic_bordersize'] . ' solid ' . $this->args['pic_bordercolor'] . ';';
				}

				$attr['style'] .= $this->args['styles'];

				return $attr;

			}

			/**
			 * Builds the image-wrapper attributes array.
			 *
			 * @access public
			 * @since 1.3.1
			 * @return array
			 */
			public function image_wrapper_attr() {

				$attr = [
					'class' => 'person-shortcode-image-wrapper',
					'style' => '',
				];

				if ( 'liftup' === $this->args['hover_type'] ) {
					$attr['class'] .= ' imageframe-liftup';
				}

				if ( 'bottomshadow' === $this->args['pic_style'] && ( 'zoomin' === $this->args['hover_type'] || 'zoomout' === $this->args['hover_type'] ) ) {
					$attr['class'] .= ' element-bottomshadow';
					$attr['style'] .= ' display:inline-block; z-index:1';
				}

				return $attr;

			}

			/**
			 * Builds the link attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function href_attr() {

				$attr = [
					'href' => $this->args['pic_link'],
				];

				if ( 'yes' === $this->args['lightbox'] ) {
					$attr['class'] = 'lightbox-shortcode';
					$attr['href']  = $this->args['picture'];
				} else {
					$attr['target'] = $this->args['linktarget'];
				}

				return $attr;

			}

			/**
			 * Builds the image attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function img_attr() {

				$attr = [
					'class' => 'person-img img-responsive',
					'style' => '',
				];

				$image_width  = $this->person_image_data['width'];
				$image_height = $this->person_image_data['height'];
				$image_id     = $this->person_image_data['id'];

				if ( $image_id ) {
					$attr['class'] .= esc_attr( ' wp-image-' . $image_id );
				}

				if ( $image_width ) {
					$attr['width'] = esc_attr( $image_width );
				}

				if ( $image_height ) {
					$attr['height'] = esc_attr( $image_height );
				}

				$attr['src'] = ( $this->person_image_data['url'] ) ? $this->person_image_data['url'] : $this->args['picture'];
				$attr['alt'] = $this->args['name'];

				return $attr;

			}

			/**
			 * Builds the author attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function author_attr() {
				return [
					'class' => 'person-author',
				];
			}

			/**
			 * Builds the description attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function desc_attr() {

				$attr = [
					'class' => 'person-desc',
				];

				if ( $this->args['background_color'] && ! fusion_is_color_transparent( $this->args['background_color'] ) ) {
					$attr['style'] = 'background-color:' . $this->args['background_color'] . ';padding:40px;margin-top:0;';
				}

				return $attr;
			}

			/**
			 * Builds the social-networks attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function social_networks_attr() {

				$attr = [
					'class' => 'fusion-social-networks',
				];

				if ( 'yes' === $this->args['social_icon_boxed'] ) {
					$attr['class'] .= ' boxed-icons';
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

				$attr = [
					'class' => 'fusion-social-network-icon fusion-tooltip fusion-' . $args['social_network'] . ' fusion-icon-' . $args['social_network'],
				];

				$attr['aria-label'] = 'fusion-' . $args['social_network'];

				$link   = $args['social_link'];
				$target = ( $fusion_settings->get( 'social_icons_new' ) ) ? '_blank' : '_self';

				if ( 'mail' === $args['social_network'] ) {
					if ( apply_filters( 'fusion_disable_antispambot', false ) ) {
						$link = 'mailto:' . str_replace( 'mailto:', '', $args['social_link'] );
					} else {
						$link = 'mailto:' . str_replace( 'mailto:', '', antispambot( $args['social_link'] ) );
					}
					$target = '_self';
				}

				$attr['href']   = $link;
				$attr['target'] = $target;

				if ( '_blank' === $attr['target'] ) {
					$attr['rel'] = 'noopener noreferrer';
				}

				if ( $fusion_settings->get( 'nofollow_social_links' ) ) {
					$attr['rel'] = 'nofollow';
				}

				$attr['style'] = '';

				if ( $args['icon_color'] ) {
					$attr['style'] = 'color:' . $args['icon_color'] . ';';
				}

				if ( 'yes' === $this->args['social_icon_boxed'] && $args['box_color'] ) {
					$attr['style'] .= 'background-color:' . $args['box_color'] . ';border-color:' . $args['box_color'] . ';';
				}

				if ( 'yes' === $this->args['social_icon_boxed'] && $this->args['social_icon_boxed_radius'] || '0' === $this->args['social_icon_boxed_radius'] ) {
					if ( 'round' === $this->args['social_icon_boxed_radius'] ) {
						$this->args['social_icon_boxed_radius'] = '50%';
					}
					$attr['style'] .= 'border-radius:' . $this->args['social_icon_boxed_radius'] . ';';
				}

				if ( $this->args['social_icon_font_size'] ) {
					$attr['style'] .= 'font-size:' . $this->args['social_icon_font_size'] . ';';
				}

				if ( 'yes' === $this->args['social_icon_boxed'] && $this->args['social_icon_padding'] ) {
					$attr['style'] .= 'padding:' . $this->args['social_icon_padding'] . ';';
				}

				$attr['data-placement'] = $this->args['social_icon_tooltip'];
				$tooltip                = $args['social_network'];
				$tooltip                = ( 'youtube' === strtolower( $tooltip ) ) ? 'YouTube' : $tooltip;
				$tooltip                = ( 'linkedin' === strtolower( $tooltip ) ) ? 'LinkedIn' : $tooltip;

				$attr['data-title'] = ucfirst( $tooltip );
				$attr['title']      = ucfirst( $tooltip );

				if ( 'none' !== $this->args['social_icon_tooltip'] ) {
					$attr['data-toggle'] = 'tooltip';
				}

				return $attr;

			}

			/**
			 * Adds settings to element options panel.
			 *
			 * @access public
			 * @since 1.1
			 * @return array $sections Person settings.
			 */
			public function add_options() {

				return [
					'person_shortcode_section' => [
						'label'       => esc_html__( 'Person', 'fusion-builder' ),
						'description' => '',
						'id'          => 'person_shortcode_section',
						'type'        => 'accordion',
						'icon'        => 'fusiona-user',
						'fields'      => [
							'person_shortcode_important_note_info' => [
								'label'       => '',
								'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> The styling options for the social icons used in the person element are controlled through the options under the "Social Icon Elements" section on this tab.', 'fusion-builder' ) . '</div>',
								'id'          => 'person_shortcode_important_note_info',
								'type'        => 'custom',
							],
							'person_background_color' => [
								'label'       => esc_html__( 'Person Background Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the background color of the person area.', 'fusion-builder' ),
								'id'          => 'person_background_color',
								'default'     => 'rgba(0,0,0,0)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
							],
							'person_pic_style'        => [
								'label'       => esc_html__( 'Person Picture Style Type', 'fusion-builder' ),
								'description' => esc_html__( 'Select the style type.', 'fusion-builder' ),
								'id'          => 'person_pic_style',
								'default'     => 'none',
								'type'        => 'radio-buttonset',
								'transport'   => 'postMessage',
								'choices'     => [
									'none'         => esc_attr__( 'None', 'fusion-builder' ),
									'glow'         => esc_attr__( 'Glow', 'fusion-builder' ),
									'dropshadow'   => esc_attr__( 'Drop Shadow', 'fusion-builder' ),
									'bottomshadow' => esc_attr__( 'Bottom Shadow', 'fusion-builder' ),
								],
							],
							'person_pic_style_blur'   => [
								'label'       => esc_html__( 'Person Picture Glow / Drop Shadow Blur', 'fusion-builder' ),
								'description' => esc_html__( 'Choose the amount of blur added to glow or drop shadow effect.', 'fusion-builder' ),
								'id'          => 'person_pic_style_blur',
								'default'     => '3',
								'type'        => 'slider',
								'transport'   => 'postMessage',
								'choices'     => [
									'min'  => '0',
									'max'  => '50',
									'step' => '1',
								],
								'required'    => [
									[
										'setting'  => 'person_pic_style',
										'operator' => '!=',
										'value'    => 'none',
									],
									[
										'setting'  => 'person_pic_style',
										'operator' => '!=',
										'value'    => 'bottomshadow',
									],
								],
							],
							'person_style_color'      => [
								'label'       => esc_html__( 'Person Style Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the style color for all style types except border.', 'fusion-builder' ),
								'id'          => 'person_style_color',
								'default'     => '#000000',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
							],
							'person_border_color'     => [
								'label'       => esc_html__( 'Person Border Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border color of the person image.', 'fusion-builder' ),
								'id'          => 'person_border_color',
								'default'     => '#e2e2e2',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
							],
							'person_border_size'      => [
								'label'       => esc_html__( 'Person Border Size', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border size of the person image.', 'fusion-builder' ),
								'id'          => 'person_border_size',
								'default'     => '0',
								'type'        => 'slider',
								'transport'   => 'postMessage',
								'choices'     => [
									'min'  => '0',
									'max'  => '50',
									'step' => '1',
								],
							],
							'person_border_radius'    => [
								'label'       => esc_html__( 'Person Border Radius', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border radius of the person image.', 'fusion-builder' ),
								'id'          => 'person_border_radius',
								'default'     => '0px',
								'type'        => 'dimension',
								'choices'     => [ 'px', '%' ],
								'transport'   => 'postMessage',
							],
							'person_alignment'        => [
								'label'       => esc_html__( 'Person Content Alignment', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the alignment of the person content.', 'fusion-builder' ),
								'id'          => 'person_alignment',
								'default'     => 'Left',
								'type'        => 'radio-buttonset',
								'transport'   => 'postMessage',
								'choices'     => [
									'Left'   => esc_html__( 'Left', 'fusion-builder' ),
									'Center' => esc_html__( 'Center', 'fusion-builder' ),
									'Right'  => esc_html__( 'Right', 'fusion-builder' ),
								],
							],
							'person_icon_position'    => [
								'label'       => esc_html__( 'Person Social Icon Position', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the position of the social icons.', 'fusion-builder' ),
								'id'          => 'person_icon_position',
								'default'     => 'Top',
								'type'        => 'radio-buttonset',
								'transport'   => 'postMessage',
								'choices'     => [
									'Top'    => esc_html__( 'Top', 'fusion-builder' ),
									'Bottom' => esc_html__( 'Bottom', 'fusion-builder' ),
								],
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
			}
		}
	}

	new FusionSC_Person();

}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 1.0
 */
function fusion_element_person() {

	global $fusion_settings;

	$person_options         = [
		'name'       => esc_attr__( 'Person', 'fusion-builder' ),
		'shortcode'  => 'fusion_person',
		'icon'       => 'fusiona-user',
		'preview'    => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-person-preview.php',
		'preview_id' => 'fusion-builder-block-module-person-preview-template',
		'help_url'   => 'https://theme-fusion.com/documentation/fusion-builder/elements/person-element/',
		'params'     => [
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Name', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert the name of the person.', 'fusion-builder' ),
				'param_name'   => 'name',
				'value'        => esc_attr__( 'Name', 'fusion-builder' ),
				'placeholder'  => true,
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Title', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert the title of the person.', 'fusion-builder' ),
				'param_name'   => 'title',
				'value'        => esc_attr__( 'Title', 'fusion-builder' ),
				'placeholder'  => true,
				'dynamic_data' => true,
			],
			[
				'type'         => 'textarea',
				'heading'      => esc_attr__( 'Profile Description', 'fusion-builder' ),
				'description'  => esc_attr__( 'Enter the content to be displayed.', 'fusion-builder' ),
				'param_name'   => 'element_content',
				'value'        => esc_attr__( 'Your Content Goes Here', 'fusion-builder' ),
				'placeholder'  => true,
				'dynamic_data' => true,
			],
			[
				'type'         => 'upload',
				'heading'      => esc_attr__( 'Picture', 'fusion-builder' ),
				'description'  => esc_attr__( 'Upload an image to display.', 'fusion-builder' ),
				'param_name'   => 'picture',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'        => 'textfield',
				'heading'     => esc_attr__( 'Picture ID', 'fusion-builder' ),
				'description' => esc_attr__( 'Picture ID from Media Library.', 'fusion-builder' ),
				'param_name'  => 'picture_id',
				'value'       => '',
				'hidden'      => true,
			],
			[
				'type'         => 'link_selector',
				'heading'      => esc_attr__( 'Picture Link URL', 'fusion-builder' ),
				'description'  => esc_attr__( 'Add the URL the picture will link to, ex: http://example.com.', 'fusion-builder' ),
				'param_name'   => 'pic_link',
				'dynamic_data' => true,
				'value'        => '',
				'dependency'   => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
				'description' => __( '_self = open in same window.<br />_blank = open in new window.', 'fusion-builder' ),
				'param_name'  => 'linktarget',
				'value'       => [
					'_self'  => esc_attr__( '_self', 'fusion-builder' ),
					'_blank' => esc_attr__( '_blank', 'fusion-builder' ),
				],
				'default'     => '_self',
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Picture Style Type', 'fusion-builder' ),
				'description' => esc_attr__( 'Select the style type for the picture.', 'fusion-builder' ),
				'param_name'  => 'pic_style',
				'value'       => [
					''             => esc_attr__( 'Default', 'fusion-builder' ),
					'none'         => esc_attr__( 'None', 'fusion-builder' ),
					'glow'         => esc_attr__( 'Glow', 'fusion-builder' ),
					'dropshadow'   => esc_attr__( 'Drop Shadow', 'fusion-builder' ),
					'bottomshadow' => esc_attr__( 'Bottom Shadow', 'fusion-builder' ),
				],
				'default'     => '',
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'range',
				'heading'     => esc_attr__( 'Picture Glow / Drop Shadow Blur', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose the amount of blur added to glow or drop shadow effect. In pixels.', 'fusion-builder' ),
				'param_name'  => 'pic_style_blur',
				'value'       => '',
				'min'         => '0',
				'max'         => '50',
				'step'        => '1',
				'default'     => $fusion_settings->get( 'person_pic_style_blur' ),
				'dependency'  => [
					[
						'element'  => 'picture',
						'operator' => '!=',
						'value'    => '',
					],
					[
						'element'  => 'pic_style',
						'operator' => '!=',
						'value'    => 'none',
					],
					[
						'element'  => 'pic_style',
						'operator' => '!=',
						'value'    => 'bottomshadow',
					],
				],
				'preview'     => [
					'selector' => '.person-image-container',
					'type'     => 'class',
					'toggle'   => 'hover',
				],
			],
			[
				'type'        => 'colorpickeralpha',
				'heading'     => esc_attr__( 'Picture Style Color', 'fusion-builder' ),
				'description' => esc_attr__( 'For all style types except border. Controls the style color.', 'fusion-builder' ),
				'param_name'  => 'pic_style_color',
				'value'       => '',
				'default'     => $fusion_settings->get( 'person_style_color' ),
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'range',
				'heading'     => esc_attr__( 'Picture Border Size', 'fusion-builder' ),
				'description' => esc_attr__( 'In pixels.', 'fusion-builder' ),
				'param_name'  => 'pic_bordersize',
				'value'       => '',
				'min'         => '0',
				'max'         => '50',
				'step'        => '1',
				'default'     => $fusion_settings->get( 'person_border_size' ),
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'colorpickeralpha',
				'heading'     => esc_attr__( 'Picture Border Color', 'fusion-builder' ),
				'description' => esc_attr__( "Controls the picture's border color.", 'fusion-builder' ),
				'param_name'  => 'pic_bordercolor',
				'value'       => '',
				'default'     => $fusion_settings->get( 'person_border_color' ),
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
					[
						'element'  => 'pic_bordersize',
						'value'    => '0',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'textfield',
				'heading'     => esc_attr__( 'Picture Border Radius', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose the border radius of the person image. In pixels (px), ex: 1px, or "round".', 'fusion-builder' ),
				'param_name'  => 'pic_borderradius',
				'value'       => '',
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Hover Type', 'fusion-builder' ),
				'description' => esc_attr__( 'Select the hover effect type.', 'fusion-builder' ),
				'param_name'  => 'hover_type',
				'value'       => [
					'none'    => esc_attr__( 'None', 'fusion-builder' ),
					'zoomin'  => esc_attr__( 'Zoom In', 'fusion-builder' ),
					'zoomout' => esc_attr__( 'Zoom Out', 'fusion-builder' ),
					'liftup'  => esc_attr__( 'Lift Up', 'fusion-builder' ),
				],
				'default'     => 'none',
				'dependency'  => [
					[
						'element'  => 'picture',
						'value'    => '',
						'operator' => '!=',
					],
				],
				'preview'     => [
					'selector' => '.person-image-container',
					'type'     => 'class',
					'toggle'   => 'hover',
				],
			],
			[
				'type'        => 'colorpickeralpha',
				'heading'     => esc_attr__( 'Background Color', 'fusion-builder' ),
				'description' => esc_attr__( 'Controls the background color.', 'fusion-builder' ),
				'param_name'  => 'background_color',
				'value'       => '',
				'default'     => $fusion_settings->get( 'person_background_color' ),
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Content Alignment', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose the alignment of content.', 'fusion-builder' ),
				'param_name'  => 'content_alignment',
				'value'       => [
					''       => esc_attr__( 'Default', 'fusion-builder' ),
					'left'   => esc_attr__( 'Left', 'fusion-builder' ),
					'center' => esc_attr__( 'Center', 'fusion-builder' ),
					'right'  => esc_attr__( 'Right', 'fusion-builder' ),
				],
				'default'     => '',
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Social Icons Position', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose the social icon position.', 'fusion-builder' ),
				'param_name'  => 'icon_position',
				'value'       => [
					''       => esc_attr__( 'Default', 'fusion-builder' ),
					'top'    => esc_attr__( 'Top', 'fusion-builder' ),
					'bottom' => esc_attr__( 'Bottom', 'fusion-builder' ),
				],
				'default'     => '',
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Boxed Social Icons', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose to get boxed icons.', 'fusion-builder' ),
				'param_name'  => 'social_icon_boxed',
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
				'description' => esc_attr__( 'Choose the border radius of the boxed icons. In pixels (px), ex: 1px, or "round".', 'fusion-builder' ),
				'param_name'  => 'social_icon_boxed_radius',
				'value'       => '',
				'dependency'  => [
					[
						'element'  => 'social_icon_boxed',
						'value'    => 'no',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Social Icon Color Type', 'fusion-builder' ),
				'description' => esc_attr__( 'Controls the color type of the social icons.', 'fusion-builder' ),
				'param_name'  => 'social_icon_color_type',
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
				'description' => esc_attr__( 'Specify the color of social icons. Use one for all or separate by | symbol. ex: #AA0000|#00AA00|#0000AA.', 'fusion-builder' ),
				'param_name'  => 'social_icon_colors',
				'value'       => '',
				'dependency'  => [
					[
						'element'  => 'social_icon_color_type',
						'value'    => 'brand',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'textarea',
				'heading'     => esc_attr__( 'Social Icon Custom Box Colors', 'fusion-builder' ),
				'description' => esc_attr__( 'Specify the box color of social icons. Use one for all or separate by | symbol. ex: #AA0000|#00AA00|#0000AA.', 'fusion-builder' ),
				'param_name'  => 'social_icon_boxed_colors',
				'value'       => '',
				'dependency'  => [
					[
						'element'  => 'social_icon_boxed',
						'value'    => 'no',
						'operator' => '!=',
					],
					[
						'element'  => 'social_icon_color_type',
						'value'    => 'brand',
						'operator' => '!=',
					],
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Social Icon Tooltip Position', 'fusion-builder' ),
				'description' => esc_attr__( 'Choose the display position for tooltips.', 'fusion-builder' ),
				'param_name'  => 'social_icon_tooltip',
				'value'       => [
					''       => esc_attr__( 'Default', 'fusion-builder' ),
					'top'    => esc_attr__( 'Top', 'fusion-builder' ),
					'bottom' => esc_attr__( 'Bottom', 'fusion-builder' ),
					'left'   => esc_attr__( 'Left', 'fusion-builder' ),
					'Right'  => esc_attr__( 'Right', 'fusion-builder' ),
					'none'   => esc_attr__( 'None', 'fusion-builder' ),
				],
				'default'     => '',
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Blogger Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Blogger link.', 'fusion-builder' ),
				'param_name'   => 'blogger',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Deviantart Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Deviantart link.', 'fusion-builder' ),
				'param_name'   => 'deviantart',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Discord Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Discord link.', 'fusion-builder' ),
				'param_name'   => 'discord',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Digg Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Digg link.', 'fusion-builder' ),
				'param_name'   => 'digg',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Dribbble Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Dribbble link.', 'fusion-builder' ),
				'param_name'   => 'dribbble',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Dropbox Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Dropbox link.', 'fusion-builder' ),
				'param_name'   => 'dropbox',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Facebook Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Facebook link.', 'fusion-builder' ),
				'param_name'   => 'facebook',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Flickr Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Flickr link.', 'fusion-builder' ),
				'param_name'   => 'flickr',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Forrst Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Forrst link.', 'fusion-builder' ),
				'param_name'   => 'forrst',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Instagram Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Instagram link.', 'fusion-builder' ),
				'param_name'   => 'instagram',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'LinkedIn Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom LinkedIn link.', 'fusion-builder' ),
				'param_name'   => 'linkedin',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Mixer Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Mixer link.', 'fusion-builder' ),
				'param_name'   => 'mixer',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Myspace Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Myspace link.', 'fusion-builder' ),
				'param_name'   => 'myspace',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'PayPal Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom PayPal link.', 'fusion-builder' ),
				'param_name'   => 'paypal',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Pinterest Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Pinterest link.', 'fusion-builder' ),
				'param_name'   => 'pinterest',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Reddit Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Reddit link.', 'fusion-builder' ),
				'param_name'   => 'reddit',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'RSS Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom RSS link.', 'fusion-builder' ),
				'param_name'   => 'rss',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Skype Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Skype link.', 'fusion-builder' ),
				'param_name'   => 'skype',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'SoundCloud Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom SoundCloud link.', 'fusion-builder' ),
				'param_name'   => 'soundcloud',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Spotify Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Spotify link.', 'fusion-builder' ),
				'param_name'   => 'spotify',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Tumblr Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Tumblr link.', 'fusion-builder' ),
				'param_name'   => 'tumblr',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Twitch Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Twitch link.', 'fusion-builder' ),
				'param_name'   => 'twitch',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Twitter Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Twitter link.', 'fusion-builder' ),
				'param_name'   => 'twitter',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Vimeo Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Vimeo link.', 'fusion-builder' ),
				'param_name'   => 'vimeo',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'VK Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom VK link.', 'fusion-builder' ),
				'param_name'   => 'vk',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'WhatsApp Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom WhatsApp link.', 'fusion-builder' ),
				'param_name'   => 'whatsapp',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Xing Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Xing link.', 'fusion-builder' ),
				'param_name'   => 'xing',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Yahoo Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Yahoo link.', 'fusion-builder' ),
				'param_name'   => 'yahoo',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Yelp Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Yelp link.', 'fusion-builder' ),
				'param_name'   => 'yelp',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Youtube Link', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert your custom Youtube link.', 'fusion-builder' ),
				'param_name'   => 'youtube',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'         => 'textfield',
				'heading'      => esc_attr__( 'Email Address', 'fusion-builder' ),
				'description'  => esc_attr__( 'Insert an email address to display the email icon.', 'fusion-builder' ),
				'param_name'   => 'email',
				'value'        => '',
				'dynamic_data' => true,
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Show Custom Social Icons', 'fusion-builder' ),
				'description' => esc_attr__( 'Show the custom social icons specified in Theme Options.', 'fusion-builder' ),
				'param_name'  => 'show_custom',
				'value'       => [
					'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
					'no'  => esc_attr__( 'No', 'fusion-builder' ),
				],
				'default'     => 'no',
			],
		],
	];
	$custom_social_networks = fusion_builder_get_custom_social_networks();
	if ( is_array( $custom_social_networks ) ) {
		$custom_networks = [];
		foreach ( $custom_social_networks as $key => $custom_network ) {
			$person_options['params'][] = [
				'type'        => 'textfield',
				/* translators: The network-name. */
				'heading'     => sprintf( esc_attr__( '%s Link', 'fusion-builder' ), $custom_network['title'] ),
				'description' => esc_attr__( 'Insert your custom social link.', 'fusion-builder' ),
				'param_name'  => 'custom_' . $key,
				'value'       => '',
				'dependency'  => [
					[
						'element'  => 'show_custom',
						'value'    => 'yes',
						'operator' => '==',
					],
				],
			];
		}
	}
	$person_options['params'][] = [
		'type'        => 'checkbox_button_set',
		'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
		'param_name'  => 'hide_on_mobile',
		'value'       => fusion_builder_visibility_options( 'full' ),
		'default'     => fusion_builder_default_visibility( 'array' ),
		'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
	];
	$person_options['params'][] = [
		'type'        => 'textfield',
		'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
		'param_name'  => 'class',
		'value'       => '',
		'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
	];
	$person_options['params'][] = [
		'type'        => 'textfield',
		'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
		'param_name'  => 'id',
		'value'       => '',
		'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
	];
	fusion_builder_map( fusion_builder_frontend_data( 'FusionSC_Person', $person_options ) );
}
add_action( 'fusion_builder_before_init', 'fusion_element_person' );
