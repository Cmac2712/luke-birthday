<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( ! class_exists( 'FusionSC_Container' ) ) {
	/**
	 * Shortcode class.
	 *
	 * @since 1.0
	 */
	class FusionSC_Container extends Fusion_Element {

		/**
		 * The internal container counter.
		 *
		 * @access private
		 * @since 1.3
		 * @var int
		 */
		private $container_counter = 0;

		/**
		 * Counter counter for a specific scope, reset for different layout sections.
		 *
		 * @access private
		 * @since 2.2
		 * @var int
		 */
		private $scope_container_counter = 0;

		/**
		 * The internal container counter for nested.
		 *
		 * @access private
		 * @since 2.2
		 * @var int
		 */
		private $nested_counter = 0;


		/**
		 * Whether a container is rendering.
		 *
		 * @access private
		 * @since 2.2
		 * @var bool
		 */
		private $rendering = false;

		/**
		 * The counter for 100% height scroll sections.
		 *
		 * @access private
		 * @since 1.3
		 * @var int
		 */
		private $scroll_section_counter = 0;

		/**
		 * The counter for elements in a 100% height scroll section.
		 *
		 * @access private
		 * @since 1.3
		 * @var int
		 */
		private $scroll_section_element_counter = 1;

		/**
		 * Stores the navigation for a scroll section.
		 *
		 * @access private
		 * @since 1.3
		 * @var int
		 */
		private $scroll_section_navigation = '';

		/**
		 * Scope that the scroll section exists on.
		 *
		 * @access private
		 * @since 2.2
		 * @var mixed
		 */
		private $scroll_section_scope = false;

		/**
		 * Constructor.
		 *
		 * @access public
		 * @since 1.0
		 */
		public function __construct() {
			parent::__construct();
			add_shortcode( 'fusion_builder_container', [ $this, 'render' ] );
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

			return [
				'admin_label'                           => '',
				'is_nested'                             => '0', // Variable that simply checks if the current container is a nested one (e.g. from FAQ or blog element).
				'hide_on_mobile'                        => fusion_builder_default_visibility( 'string' ),
				'id'                                    => '',
				'class'                                 => '',
				'status'                                => 'published',
				'publish_date'                          => '',
				// Background.
				'background_color'                      => $fusion_settings->get( 'full_width_bg_color' ),
				'gradient_start_color'                  => $fusion_settings->get( 'full_width_gradient_start_color' ),
				'gradient_end_color'                    => $fusion_settings->get( 'full_width_gradient_end_color' ),
				'gradient_start_position'               => '0',
				'gradient_end_position'                 => '100',
				'gradient_type'                         => 'linear',
				'radial_direction'                      => 'center',
				'linear_angle'                          => '180',
				'background_image'                      => '',
				'background_position'                   => 'center center',
				'background_repeat'                     => 'no-repeat',
				'background_parallax'                   => 'none',
				'parallax_speed'                        => '0.3',
				'background_blend_mode'                 => 'none',
				'opacity'                               => '100',
				'break_parents'                         => '0',
				'fade'                                  => 'no',
				// Style.
				'hundred_percent'                       => 'no',
				'hundred_percent_height'                => 'no',
				'hundred_percent_height_scroll'         => 'no',
				'hundred_percent_height_center_content' => 'no',
				'padding_bottom'                        => '',
				'padding_left'                          => '',
				'padding_right'                         => '',
				'padding_top'                           => '',
				'border_color'                          => $fusion_settings->get( 'full_width_border_color' ),
				'border_size'                           => $fusion_settings->get( 'full_width_border_size' ),
				'border_style'                          => 'solid',
				'equal_height_columns'                  => 'no',
				'data_bg_height'                        => '',
				'data_bg_width'                         => '',
				'enable_mobile'                         => 'no',
				'menu_anchor'                           => '',
				'margin_top'                            => '',
				'margin_bottom'                         => '',
				'link_color'                            => $fusion_settings->get( 'link_color' ),
				'link_hover_color'                      => $fusion_settings->get( 'primary_color' ),
				// Video Background.
				'video_mp4'                             => '',
				'video_webm'                            => '',
				'video_ogv'                             => '',
				'video_loop'                            => 'yes',
				'video_mute'                            => 'yes',
				'video_preview_image'                   => '',
				'overlay_color'                         => '',
				'overlay_opacity'                       => '0.5',
				'video_url'                             => '',
				'video_loop_refinement'                 => '',
				'video_aspect_ratio'                    => '16:9',
				// Filters.
				'filter_hue'                            => '0',
				'filter_saturation'                     => '100',
				'filter_brightness'                     => '100',
				'filter_contrast'                       => '100',
				'filter_invert'                         => '0',
				'filter_sepia'                          => '0',
				'filter_opacity'                        => '100',
				'filter_blur'                           => '0',
				'filter_hue_hover'                      => '0',
				'filter_saturation_hover'               => '100',
				'filter_brightness_hover'               => '100',
				'filter_contrast_hover'                 => '100',
				'filter_invert_hover'                   => '0',
				'filter_sepia_hover'                    => '0',
				'filter_opacity_hover'                  => '100',
				'filter_blur_hover'                     => '0',
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
				'full_width_border_color'         => 'border_color',
				'full_width_border_size'          => 'border_size',
				'full_width_bg_color'             => 'background_color',
				'full_width_gradient_start_color' => 'gradient_start_color',
				'full_width_gradient_end_color'   => 'gradient_end_color',
				'link_color'                      => 'link_color',
				'link_hover_color'                => 'primary_color',
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
				'container_padding_100'     => $fusion_settings->get( 'container_padding_100' ),
				'container_padding_default' => $fusion_settings->get( 'container_padding_default' ),
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
				'container_padding_100'     => 'container_padding_100',
				'container_padding_default' => 'container_padding_default',
			];
		}

		/**
		 * Container shortcode.
		 *
		 * @access public
		 * @since 1.0
		 * @param array  $atts    The attributes array.
		 * @param string $content The content.
		 * @return string
		 */
		public function render( $atts, $content = '' ) {

			$fusion_settings = fusion_get_fusion_settings();

			$atts = fusion_section_deprecated_args( $atts );

			$args = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $atts, 'fusion_builder_container' );

			// Correct radial direction params.
			$new_radial_direction_names = [
				'bottom'        => 'center bottom',
				'bottom center' => 'center bottom',
				'left'          => 'left center',
				'right'         => 'right center',
				'top'           => 'center top',
				'center'        => 'center center',
				'center left'   => 'left center',
			];
			if ( array_key_exists( $args['radial_direction'], $new_radial_direction_names ) ) {
				$args['radial_direction'] = $new_radial_direction_names [ $args['radial_direction'] ];
			}

			extract( $args );

			global $parallax_id, $fusion_fwc_type, $is_IE, $is_edge, $columns, $global_container_count;

			// If we are inside another container render, then we count nested.
			if ( ! $this->rendering ) {
				$this->scope_container_counter++;
				$this->container_counter++;
				$this->rendering      = true;
				$this->nested_counter = 0;
				$is_nested            = false;
				$container_counter    = $this->container_counter;
				$last_container       = ( $global_container_count === $this->scope_container_counter );
				$scroll_scope_matches = 'nested' !== $this->scroll_section_scope;

				// Last top level, reset the scoped counters.
				if ( $last_container ) {
					$global_container_count        = false;
					$this->scope_container_counter = 0;
				}
			} else {
				$this->nested_counter++;
				$is_nested            = true;
				$container_counter    = $this->container_counter . '-' . $this->nested_counter;
				$last_container       = ( $global_container_count === $this->nested_counter );
				$scroll_scope_matches = 'parent' !== $this->scroll_section_scope;
			}

			// If container is no published, return early.
			if ( ! apply_filters( 'fusion_is_container_viewable', $this->is_container_viewable( $args ), $args ) ) {
				return;
			}

			$fusion_fwc_type = [];

			$style           = '';
			$overlay_style   = '';
			$lazy_load_style = '';
			$classes         = 'fusion-fullwidth fullwidth-box fusion-builder-row-' . $container_counter;
			$outer_html      = '';
			$lazy_load       = $fusion_settings->get( 'lazy_load' );

			if ( ! $background_image || '' === $background_image ) {
				$lazy_load = false;
			}

			// Video background.
			$video_bg  = false;
			$video_src = '';

			// TODO: refactor this whole section.
			$c_page_id = fusion_library()->get_page_id();

			$width_100     = false;
			$page_template = '';

			// Placeholder background color.
			if ( false !== strpos( $background_image, 'https://placehold.it/' ) ) {
				$dimensions = str_replace( 'x', '', str_replace( 'https://placehold.it/', '', $background_image ) );
				if ( is_numeric( $dimensions ) ) {
					$background_image = $background_image . '/333333/ffffff/';
				}
			}
			if ( function_exists( 'is_woocommerce' ) ) {
				if ( is_woocommerce() ) {
					$custom_fields = get_post_custom_values( '_wp_page_template', $c_page_id );
					$page_template = ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) ? $custom_fields[0] : '';
				}
			}

			$background_color = ( '' !== $overlay_color ) ? fusion_library()->sanitize->get_rgba( $overlay_color, $overlay_opacity ) : $background_color;

			$alpha_background_color     = 1;
			$alpha_gradient_start_color = 1;
			$alpha_gradient_end_color   = 1;

			if ( class_exists( 'Fusion_Color' ) ) {
				$alpha_background_color     = Fusion_Color::new_color( $background_color )->alpha;
				$alpha_gradient_start_color = Fusion_Color::new_color( $gradient_start_color )->alpha;
				$alpha_gradient_end_color   = Fusion_Color::new_color( $gradient_end_color )->alpha;
			}

			$is_gradient_color = ( ! empty( $gradient_start_color ) && 0 !== $alpha_gradient_start_color ) || ( ! empty( $gradient_end_color ) && 0 !== $alpha_gradient_end_color ) ? true : false;

			// If no blend mode is defined, check if we should set to overlay.
			if ( ! isset( $atts['background_blend_mode'] ) && 1 > $alpha_background_color && 0 !== $alpha_background_color && ! $is_gradient_color && ( ! empty( $background_image ) || $video_bg ) ) {
				$background_blend_mode = 'overlay';
			}

			if ( $is_gradient_color ) {
				$lazy_load_style .= 'data-bg-gradient="' . Fusion_Builder_Gradient_Helper::get_gradient_string( $args ) . '"';
			}

			if ( ! empty( $video_mp4 ) ) {
				$video_src .= '<source src="' . $video_mp4 . '" type="video/mp4">';
				$video_bg   = true;
			}

			if ( ! empty( $video_webm ) ) {
				$video_src .= '<source src="' . $video_webm . '" type="video/webm">';
				$video_bg   = true;
			}

			if ( ! empty( $video_ogv ) ) {
				$video_src .= '<source src="' . $video_ogv . '" type="video/ogg">';
				$video_bg   = true;
			}

			if ( ! empty( $video_url ) ) {
				$video_bg = true;
			}

			if ( $video_bg ) {

				$classes .= ' video-background';

				if ( ! empty( $video_url ) ) {
					$video_url = fusion_builder_get_video_provider( $video_url );

					if ( 'youtube' === $video_url['type'] ) {
						$outer_html .= "<div style='opacity:0;' class='fusion-background-video-wrapper' id='video-" . ( $parallax_id++ ) . "' data-youtube-video-id='" . $video_url['id'] . "' data-mute='" . $video_mute . "' data-loop='" . ( 'yes' === $video_loop ? 1 : 0 ) . "' data-loop-adjustment='" . $video_loop_refinement . "' data-video-aspect-ratio='" . $video_aspect_ratio . "'><div class='fusion-container-video-bg' id='video-" . ( $parallax_id++ ) . "-inner'></div></div>";
					} elseif ( 'vimeo' === $video_url['type'] ) {
						$outer_html .= '<div id="video-' . $parallax_id . '" class="fusion-background-video-wrapper" data-vimeo-video-id="' . $video_url['id'] . '" data-mute="' . $video_mute . '" data-video-aspect-ratio="' . $video_aspect_ratio . '" style="opacity:0;"><iframe id="video-iframe-' . $parallax_id . '" class="fusion-container-video-bg" src="//player.vimeo.com/video/' . $video_url['id'] . '?html5=1&autopause=0&autoplay=1&badge=0&byline=0&autopause=0&loop=' . ( 'yes' === $video_loop ? '1' : '0' ) . '&title=0' . ( 'yes' === $video_mute ? '&muted=1' : '' ) . '" frameborder="0"></iframe></div>';
					}
				} else {
					$video_attributes = 'preload="auto" autoplay playsinline';

					if ( 'yes' === $video_loop ) {
						$video_attributes .= ' loop';
					}

					if ( 'yes' === $video_mute ) {
						$video_attributes .= ' muted';
					}

					// Video Preview Image.
					if ( ! empty( $video_preview_image ) ) {
						$video_preview_image_style = 'background-image:url(' . $video_preview_image . ');';
						$outer_html               .= '<div class="fullwidth-video-image" style="' . $video_preview_image_style . '"></div>';
					}

					$outer_html .= '<div class="fullwidth-video"><video ' . $video_attributes . '>' . $video_src . '</video></div>';
				}

				// Video Overlay.
				if ( $is_gradient_color ) {
					$overlay_style .= 'background-image: ' . Fusion_Builder_Gradient_Helper::get_gradient_string( $args ) . ';';
				}

				if ( ! empty( $background_color ) && 1 > $alpha_background_color ) {
					$overlay_style .= 'background-color:' . $background_color . ';';
				}

				if ( '' !== $overlay_style ) {
					$outer_html .= '<div class="fullwidth-overlay" style="' . $overlay_style . '"></div>';
				}
			}

			if ( $is_IE || $is_edge ) {
				if ( 1 > $alpha_background_color ) {
					$classes .= ' fusion-ie-mode';
				}
			}

			// Background.
			if ( ! empty( $background_color ) && ! ( 'yes' === $fade && ! empty( $background_image ) && false === $video_bg ) ) {
				$style .= 'background-color: ' . esc_attr( $background_color ) . ';';
			}

			if ( ! empty( $background_image ) && 'yes' !== $fade && ! $lazy_load ) {
				$style .= 'background-image: url("' . esc_url_raw( $background_image ) . '");';
			}

			if ( $is_gradient_color ) {
				$style .= 'background-image:' . Fusion_Builder_Gradient_Helper::get_gradient_string( $args, 'main_bg' );
			}

			if ( ! empty( $background_position ) ) {
				$style .= 'background-position: ' . esc_attr( $background_position ) . ';';
			}

			if ( ! empty( $background_repeat ) ) {
				$style .= 'background-repeat: ' . esc_attr( $background_repeat ) . ';';
			}

			if ( 'none' !== $background_blend_mode ) {
				$style .= 'background-blend-mode: ' . esc_attr( $background_blend_mode ) . ';';
			}

			// Get correct container padding.
			$paddings = [ 'top', 'right', 'bottom', 'left' ];

			foreach ( $paddings as $padding ) {
				$padding_name = 'padding_' . $padding;

				if ( '' === ${$padding_name} ) {

					// TO padding.
					${$padding_name}             = $fusion_settings->get( 'container_padding_default', $padding );
					$is_hundred_percent_template = apply_filters( 'fusion_is_hundred_percent_template', false, $c_page_id );
					if ( $is_hundred_percent_template ) {
						${$padding_name} = $fusion_settings->get( 'container_padding_100', $padding );
					}
				}

				// Add padding to style.
				if ( ! empty( ${$padding_name} ) ) {
					$style .= 'padding-' . $padding . ':' . fusion_library()->sanitize->get_value_with_unit( ${$padding_name} ) . ';';
				}
			}

			// Margin; for separator conversion only.
			if ( ! empty( $margin_bottom ) ) {
				$style .= 'margin-bottom: ' . fusion_library()->sanitize->get_value_with_unit( $margin_bottom ) . ';';
			}

			if ( ! empty( $margin_top ) ) {
				$style .= 'margin-top: ' . fusion_library()->sanitize->get_value_with_unit( $margin_top ) . ';';
			}

			// Border.
			if ( ! empty( $border_size ) ) {
				$style .= 'border-top-width:' . esc_attr( FusionBuilder::validate_shortcode_attr_value( $border_size, 'px' ) ) . ';';
				$style .= 'border-bottom-width:' . esc_attr( FusionBuilder::validate_shortcode_attr_value( $border_size, 'px' ) ) . ';';
				$style .= 'border-color:' . esc_attr( $border_color ) . ';';
				$style .= 'border-top-style:' . esc_attr( $border_style ) . ';';
				$style .= 'border-bottom-style:' . esc_attr( $border_style ) . ';';
			}

			// Fading Background.
			if ( 'yes' === $fade && ! empty( $background_image ) && false === $video_bg ) {
				$bg_type    = 'faded';
				$fade_style = '';
				$classes   .= ' faded-background';

				if ( 'fixed' === $background_parallax ) {
					$fade_style .= 'background-attachment:' . $background_parallax . ';';
				}

				if ( $background_color ) {
					$fade_style .= 'background-color:' . $background_color . ';';
				}

				if ( $background_image && ! $lazy_load ) {
					$fade_style .= 'background-image: url(' . $background_image . ');';
				}

				if ( $is_gradient_color ) {
					$fade_style .= 'background-image: ' . Fusion_Builder_Gradient_Helper::get_gradient_string( $args, 'fade' );
				}

				if ( $background_position ) {
					$fade_style .= 'background-position:' . $background_position . ';';
				}

				if ( $background_repeat ) {
					$fade_style .= 'background-repeat:' . $background_repeat . ';';
				}

				if ( 'none' !== $background_blend_mode ) {
					$fade_style .= 'background-blend-mode: ' . esc_attr( $background_blend_mode ) . ';';
				}

				if ( 'no-repeat' === $background_repeat ) {
					$fade_style .= '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
				}

				if ( ! $lazy_load ) {
					$outer_html .= '<div class="fullwidth-faded" style="' . $fade_style . '"></div>';
				} else {
					$outer_html .= '<div class="fullwidth-faded lazyload" style="' . $fade_style . '" data-bg="' . $background_image . '" ' . $lazy_load_style . '></div>';
				}
			}

			if ( ! empty( $background_image ) && ! $video_bg ) {
				if ( 'no-repeat' === $background_repeat ) {
					$style .= '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
				}
			}

			// Parallax.
			$parallax_helper = '';
			if ( false === $video_bg && ! empty( $background_image ) ) {
				$parallax_data  = '';
				$parallax_data .= ' data-bg-align="' . esc_attr( $background_position ) . '"';
				$parallax_data .= ' data-direction="' . $background_parallax . '"';
				$parallax_data .= ' data-mute="' . ( 'mute' === $video_mute ? 'true' : 'false' ) . '"';
				$parallax_data .= ' data-opacity="' . esc_attr( $opacity ) . '"';
				$parallax_data .= ' data-velocity="' . esc_attr( (float) $parallax_speed * -1 ) . '"';
				$parallax_data .= ' data-mobile-enabled="' . ( ( 'yes' === $enable_mobile ) ? 'true' : 'false' ) . '"';
				$parallax_data .= ' data-break_parents="' . esc_attr( $break_parents ) . '"';
				$parallax_data .= ' data-bg-image="' . esc_attr( $background_image ) . '"';
				$parallax_data .= ' data-bg-repeat="' . esc_attr( isset( $background_repeat ) && 'no-repeat' !== $background_repeat ? 'true' : 'false' ) . '"';

				$bg_color_alpha = Fusion_Color::new_color( $background_color )->alpha;
				if ( 0 !== $bg_color_alpha ) {
					$parallax_data .= ' data-bg-color="' . esc_attr( $background_color ) . '"';
				}

				if ( 'none' !== $background_blend_mode ) {
					$parallax_data .= ' data-blend-mode="' . esc_attr( $background_blend_mode ) . '"';
				}

				if ( $is_gradient_color ) {
					$parallax_data .= ' data-bg-gradient-type="' . esc_attr( $gradient_type ) . '"';
					$parallax_data .= ' data-bg-gradient-angle="' . esc_attr( $linear_angle ) . '"';
					$parallax_data .= ' data-bg-gradient-start-color="' . esc_attr( $gradient_start_color ) . '"';
					$parallax_data .= ' data-bg-gradient-start-position="' . esc_attr( $gradient_start_position ) . '"';
					$parallax_data .= ' data-bg-gradient-end-color="' . esc_attr( $gradient_end_color ) . '"';
					$parallax_data .= ' data-bg-gradient-end-position="' . esc_attr( $gradient_end_position ) . '"';
					$parallax_data .= ' data-bg-radial-direction="' . esc_attr( $radial_direction ) . '"';
				}

				$parallax_data .= ' data-bg-height="' . esc_attr( $data_bg_height ) . '"';
				$parallax_data .= ' data-bg-width="' . esc_attr( $data_bg_width ) . '"';

				if ( 'none' !== $background_parallax && 'fixed' !== $background_parallax ) {
					$parallax_helper = '<div class="fusion-bg-parallax" ' . $parallax_data . '></div>';
				}

				// Parallax css class.
				if ( ! empty( $background_parallax ) ) {
					$classes .= " fusion-parallax-{$background_parallax}";
				}

				if ( 'fixed' === $background_parallax ) {
					$style .= 'background-attachment:' . $background_parallax . ';';
				}
			}

			// Custom CSS class.
			if ( ! empty( $class ) ) {
				$classes .= " {$class}";
			}

			$width_100_page_option = 'blog_width_100';
			if ( 'avada_portfolio' === get_post_type( $c_page_id ) ) {
				$width_100_page_option = 'portfolio_width_100';
			} elseif ( 'product' === get_post_type( $c_page_id ) ) {
				$width_100_page_option = 'product_width_100';
			}

			if ( '100%' === $fusion_settings->get( 'site_width' ) ||
				is_page_template( '100-width.php' ) ||
				is_page_template( 'blank.php' ) ||
				fusion_get_option( $width_100_page_option ) || // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				'100-width.php' === $page_template ) {
				$width_100 = true;
			}

			// Hundred percent.
			$classes                             .= ( 'yes' === $hundred_percent ) ? ' hundred-percent-fullwidth' : ' nonhundred-percent-fullwidth';
			$fusion_fwc_type['content']           = ( 'yes' === $hundred_percent ) ? 'fullwidth' : 'contained';
			$fusion_fwc_type['width_100_percent'] = $width_100;
			$fusion_fwc_type['padding']           = [
				'left'  => $padding_left,
				'right' => $padding_right,
			];

			// Hundred percent height.
			$scroll_section_container = $data_attr = $active_class = $css_id = '';

			if ( 'yes' === $hundred_percent_height ) {
				$classes .= ' hundred-percent-height';

				if ( 'yes' === $hundred_percent_height_center_content ) {
					$classes .= ' hundred-percent-height-center-content';
				}

				if ( 'yes' === $hundred_percent_height_scroll && $scroll_scope_matches ) {

					if ( 1 === $this->scroll_section_element_counter ) {
						$this->scroll_section_counter++;
						$this->scroll_section_scope = $is_nested ? 'nested' : 'parent';
						$scroll_section_container   = '<div id="fusion-scroll-section-' . $this->scroll_section_counter . '" class="fusion-scroll-section" data-section="' . $this->scroll_section_counter . '">';

						$active_class .= ' active';
					}

					$classes .= ' hundred-percent-height-scrolling';

					if ( '' !== $id ) {
						$css_id = $id;
					}
					$id        = 'fusion-scroll-section-element-' . $this->scroll_section_counter . '-' . $this->scroll_section_element_counter;
					$data_attr = ' data-section="' . $this->scroll_section_counter . '" data-element="' . $this->scroll_section_element_counter . '"';

					$this->scroll_section_navigation .= '<li><a href="#' . $id . '" class="fusion-scroll-section-link" data-name="' . $admin_label . '" data-element="' . $this->scroll_section_element_counter . '"><span class="fusion-scroll-section-link-bullet"></span></a></li>';

					$this->scroll_section_element_counter++;
				} else {
					$classes .= ' non-hundred-percent-height-scrolling';
				}
			} else {
				$classes .= ' non-hundred-percent-height-scrolling';
			}

			if ( ( $last_container || 'no' === $hundred_percent_height_scroll || 'no' === $hundred_percent_height ) && $scroll_scope_matches ) {

				if ( 1 < $this->scroll_section_element_counter ) {
					$scroll_navigation_position = ( 'right' === fusion_get_option( 'header_position' ) || is_rtl() ) ? 'scroll-navigation-left' : 'scroll-navigation-right';
					$scroll_section_container   = '<nav id="fusion-scroll-section-nav-' . $this->scroll_section_counter . '" class="fusion-scroll-section-nav ' . $scroll_navigation_position . '" data-section="' . $this->scroll_section_counter . '"><ul>' . $this->scroll_section_navigation . '</ul></nav>';
					$scroll_section_container  .= '</div>';
				}
				$this->scroll_section_scope           = false;
				$this->scroll_section_element_counter = 1;
				$this->scroll_section_navigation      = '';
			}

			// Equal column height.
			if ( 'yes' === $equal_height_columns ) {
				$classes .= ' fusion-equal-height-columns';
			}

			// Visibility classes.
			if ( 'no' === $hundred_percent_height || 'no' === $hundred_percent_height_scroll ) {
				$classes = fusion_builder_visibility_atts( $hide_on_mobile, $classes );
			}

			$main_content = do_shortcode( fusion_builder_fix_shortcodes( $content ) );

			// Additional wrapper for content centering.
			if ( 'yes' === $hundred_percent_height && 'yes' === $hundred_percent_height_center_content ) {
				$main_content = '<div class="fusion-fullwidth-center-content">' . $main_content . '</div>';
			}

			// CSS inline style.
			$style = ! empty( $style ) ? " style='{$style}'" : '';

			// Custom CSS ID.
			$id = ( '' !== $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

			if ( $lazy_load ) {
				$classes .= ' lazyload';
				$style   .= ' data-bg="' . $background_image . '"' . $lazy_load_style;
			}
			$output = $parallax_helper . '<div' . $id . ' class="' . $classes . '" ' . $style . '>' . $outer_html . $main_content . '</div>';

			// Menu anchor.
			if ( ! empty( $menu_anchor ) ) {
				$output = '<div id="' . $menu_anchor . '">' . $output . '</div>';
			}

			// Add custom link colors and filter CSS.
			$style = '';

			if ( isset( $atts['link_color'] ) || isset( $atts['link_color'] ) ) {
				$style_prefix             = '.fusion-fullwidth.fusion-builder-row-' . $container_counter;
				$link_exclusion_selectors = ' a:not(.fusion-button):not(.fusion-builder-module-control):not(.fusion-social-network-icon):not(.fb-icon-element):not(.fusion-countdown-link):not(.fusion-rollover-link):not(.fusion-rollover-gallery):not(.fusion-button-bar):not(.add_to_cart_button):not(.show_details_button):not(.product_type_external):not(.fusion-quick-view):not(.fusion-rollover-title-link):not(.fusion-breadcrumb-link)';

				// Add link styles.
				if ( '' !== $args['link_color'] && isset( $atts['link_color'] ) ) {
					$style .= $style_prefix . $link_exclusion_selectors . ' , ' . $style_prefix . $link_exclusion_selectors . ':before, ' . $style_prefix . $link_exclusion_selectors . ':after {color: ' . $args['link_color'] . ';}';
				}

				// Add link hover styles.
				if ( '' !== $args['link_hover_color'] && isset( $atts['link_hover_color'] ) ) {
					$style .= $style_prefix . $link_exclusion_selectors . ':hover, ' . $style_prefix . $link_exclusion_selectors . ':hover:before, ' . $style_prefix . $link_exclusion_selectors . ':hover:after {color: ' . $args['link_hover_color'] . ';}';
					$style .= $style_prefix . ' .pagination a.inactive:hover, ' . $style_prefix . ' .fusion-filters .fusion-filter.fusion-active a {border-color: ' . $args['link_hover_color'] . ';}';
					$style .= $style_prefix . ' .pagination .current {border-color: ' . $args['link_hover_color'] . '; background-color: ' . $args['link_hover_color'] . ';}';
					$style .= $style_prefix . ' .fusion-filters .fusion-filter.fusion-active a, ' . $style_prefix . ' .fusion-date-and-formats .fusion-format-box, ' . $style_prefix . ' .fusion-popover, ' . $style_prefix . ' .tooltip-shortcode {color: ' . $args['link_hover_color'] . ';}';
					$style .= '#main ' . $style_prefix . ' .post .blog-shortcode-post-title a:hover {color: ' . $args['link_hover_color'] . ';}';
				}
			}

			// Add filter styles.
			$filter_style = Fusion_Builder_Filter_Helper::get_filter_style_element( $args, '.fusion-builder-row-' . $container_counter, false );
			if ( '' !== $filter_style ) {
				$style .= $filter_style;
			}

			if ( '' !== $style ) {
				$output .= '<style type="text/css">' . $style . '</style>';
			}

			if ( 'yes' === $hundred_percent_height_scroll && 'yes' === $hundred_percent_height && $scroll_scope_matches ) {

				// Custom CSS ID.
				$css_id = ( '' !== $css_id ) ? ' id="' . esc_attr( $css_id ) . '"' : '';

				$scroll_element_style = ' style="transition-duration:' . $fusion_settings->get( 'container_hundred_percent_scroll_sensitivity' ) . 'ms;"';

				$output = '<div' . $css_id . ' class="fusion-scroll-section-element' . $active_class . '"' . $scroll_element_style . $data_attr . '>' . $output . '</div>';
			}

			if ( $last_container && 'yes' === $hundred_percent_height_scroll && 'yes' === $hundred_percent_height && $scroll_scope_matches ) {
				$output = $output . $scroll_section_container;
			} else {
				$output = $scroll_section_container . $output;
			}

			$fusion_fwc_type = [];
			$columns         = 0;

			// If we are rendering a top level container, then set render to false.
			if ( ! $is_nested ) {
				$this->rendering = false;
			}
			return apply_filters( 'fusion_element_container_content', $output, $atts );
		}

		/**
		 * Check if container should render.
		 *
		 * @access public
		 * @since 1.7
		 * @param array $args An array of arguments containing status & publish_date.
		 * @return array
		 */
		public function is_container_viewable( $args ) {

			// Published, all can see.
			if ( 'published' === $args['status'] || '' === $args['status'] ) {
				return true;
			}

			// If is author, can also see.
			if ( is_user_logged_in() && current_user_can( 'publish_posts' ) ) {
				return true;
			}

			// Set to hide.
			if ( 'draft' === $args['status'] ) {
				return false;
			}

			// Set to show until or after.
			$time_check    = strtotime( $args['publish_date'] );
			$wp_local_time = current_time( 'timestamp' );
			if ( '' !== $args['publish_date'] && $time_check ) {
				if ( 'published_until' === $args['status'] ) {
					return $wp_local_time < $time_check;
				}
				if ( 'publish_after' === $args['status'] ) {
					return $wp_local_time > $time_check;
				}
			}

			// Any incorrect set-up default to show.
			return true;
		}

		/**
		 * Builds the dynamic styling.
		 *
		 * @access public
		 * @since 1.1
		 * @return array
		 */
		public function add_styling() {
			global $fusion_settings;

			$css['global']['.fusion-builder-row.fusion-row']['max-width'] = 'var(--site_width)';

			return $css;
		}

		/**
		 * Adds settings to element options panel.
		 *
		 * @access public
		 * @since 1.1
		 * @return array $sections Column settings.
		 */
		public function add_options() {

			return [
				'container_shortcode_section' => [
					'label'       => esc_html__( 'Container', 'fusion-builder' ),
					'description' => '',
					'id'          => 'container_shortcode_section',
					'type'        => 'accordion',
					'icon'        => 'fusiona-container',
					'fields'      => [
						'container_padding_default'       => [
							'label'       => esc_html__( 'Container Padding for Default Template', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the top/right/bottom/left padding of the container element when using the Default page template. ', 'fusion-builder' ),
							'id'          => 'container_padding_default',
							'choices'     => [
								'top'    => true,
								'bottom' => true,
								'left'   => true,
								'right'  => true,
								'units'  => [ 'px', '%' ],
							],
							'default'     => [
								'top'    => '0px',
								'bottom' => '0px',
								'left'   => '0px',
								'right'  => '0px',
							],
							'type'        => 'spacing',
							'transport'   => 'postMessage',
						],
						'container_padding_100'           => [
							'label'       => esc_html__( 'Container Padding for 100% Width Template', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the top/right/bottom/left padding of the container element when using the 100% width page template.', 'fusion-builder' ),
							'id'          => 'container_padding_100',
							'choices'     => [
								'top'    => true,
								'bottom' => true,
								'left'   => true,
								'right'  => true,
								'units'  => [ 'px', '%' ],
							],
							'default'     => [
								'top'    => '0px',
								'bottom' => '0px',
								'left'   => '30px',
								'right'  => '30px',
							],
							'type'        => 'spacing',
							'transport'   => 'postMessage',
						],
						'full_width_bg_color'             => [
							'label'       => esc_html__( 'Container Background Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the background color of the container element.', 'fusion-builder' ),
							'id'          => 'full_width_bg_color',
							'default'     => 'rgba(255,255,255,0)',
							'type'        => 'color-alpha',
							'transport'   => 'postMessage',
						],
						'full_width_gradient_start_color' => [
							'label'       => esc_html__( 'Container Gradient Start Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the start color for gradient of the container element.', 'fusion-builder' ),
							'id'          => 'full_width_gradient_start_color',
							'default'     => 'rgba(255,255,255,0)',
							'type'        => 'color-alpha',
							'transport'   => 'postMessage',
						],
						'full_width_gradient_end_color'   => [
							'label'       => esc_html__( 'Container Gradient End Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the end color for gradient of the container element.', 'fusion-builder' ),
							'id'          => 'full_width_gradient_end_color',
							'default'     => 'rgba(255,255,255,0)',
							'type'        => 'color-alpha',
							'transport'   => 'postMessage',
						],
						'full_width_border_size'          => [
							'label'       => esc_html__( 'Container Border Size', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the top and bottom border size of the container element.', 'fusion-builder' ),
							'id'          => 'full_width_border_size',
							'default'     => '0',
							'type'        => 'slider',
							'transport'   => 'postMessage',
							'choices'     => [
								'min'  => '0',
								'max'  => '50',
								'step' => '1',
							],
						],
						'full_width_border_color'         => [
							'label'       => esc_html__( 'Container Border Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the border color of the container element.', 'fusion-builder' ),
							'id'          => 'full_width_border_color',
							'default'     => '#e2e2e2',
							'type'        => 'color-alpha',
							'transport'   => 'postMessage',
						],
						'container_scroll_nav_bg_color'   => [
							'label'       => esc_html__( 'Container 100% Height Navigation Background Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the background colors of the navigation area and name box when using 100% height containers.', 'fusion-builder' ),
							'id'          => 'container_scroll_nav_bg_color',
							'default'     => 'rgba(0,0,0,0.2)',
							'type'        => 'color-alpha',
							'css_vars'    => [
								[
									'name'     => '--container_scroll_nav_bg_color',
									'element'  => '.fusion-scroll-section-nav',
									'callback' => [ 'sanitize_color' ],
								],
							],
						],
						'container_scroll_nav_bullet_color' => [
							'label'       => esc_html__( 'Container 100% Height Navigation Element Color', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the color of the navigation circles and text name when using 100% height containers.', 'fusion-builder' ),
							'id'          => 'container_scroll_nav_bullet_color',
							'default'     => '#e2e2e2',
							'type'        => 'color-alpha',
							'css_vars'    => [
								[
									'name'     => '--container_scroll_nav_bullet_color',
									'element'  => '.fusion-scroll-section-link-bullet',
									'callback' => [ 'sanitize_color' ],
								],
							],
						],
						'container_hundred_percent_scroll_sensitivity' => [
							'label'       => esc_html__( 'Container 100% Height Scroll Sensitivity', 'fusion-builder' ),
							'description' => esc_html__( 'Controls the sensitivity of the scrolling transition on 100% height scrolling secitions. In milliseconds.', 'fusion-builder' ),
							'id'          => 'container_hundred_percent_scroll_sensitivity',
							'default'     => '450',
							'type'        => 'slider',
							'transport'   => 'postMessage',
							'choices'     => [
								'min'  => '200',
								'max'  => '1500',
								'step' => '10',
							],
						],
						'container_hundred_percent_height_mobile' => [
							'label'       => esc_html__( 'Container 100% Height On Mobile', 'fusion-builder' ),
							'description' => esc_html__( 'Turn on to enable the 100% height containers on mobile. Please note, this feature only works when your containers have minimal content. If the container has a lot of content it will overflow the screen height. In many cases, 100% height containers work well on desktop, but will need disabled on mobile.', 'fusion-builder' ),
							'id'          => 'container_hundred_percent_height_mobile',
							'default'     => '0',
							'type'        => 'switch',
							'output'      => [
								[
									'element'           => 'helperElement',
									'property'          => 'dummy',
									'js_callback'       => [
										'fusionGlobalScriptSet',
										[
											'globalVar' => 'fusionContainerVars',
											'id'        => 'container_hundred_percent_height_mobile',
											'trigger'   => [ 'resize' ],
										],
									],
									'sanitize_callback' => '__return_empty_string',
								],
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
			$fusion_settings = fusion_get_fusion_settings();

			$is_sticky_header_transparent = 0;
			if ( 1 > Fusion_Color::new_color( $fusion_settings->get( 'header_sticky_bg_color' ) )->alpha ) {
				$is_sticky_header_transparent = 1;
			}

			Fusion_Dynamic_JS::enqueue_script(
				'fusion-container',
				FusionBuilder::$js_folder_url . '/general/fusion-container.js',
				FusionBuilder::$js_folder_path . '/general/fusion-container.js',
				[ 'jquery', 'modernizr', 'fusion-animations', 'jquery-fade', 'fusion-parallax', 'fusion-video-general', 'fusion-video-bg' ],
				'1',
				true
			);
			Fusion_Dynamic_JS::localize_script(
				'fusion-container',
				'fusionContainerVars',
				[
					'content_break_point'                => intval( $fusion_settings->get( 'content_break_point' ) ),
					'container_hundred_percent_height_mobile' => intval( $fusion_settings->get( 'container_hundred_percent_height_mobile' ) ),
					'is_sticky_header_transparent'       => $is_sticky_header_transparent,
					'hundred_percent_scroll_sensitivity' => intval( $fusion_settings->get( 'container_hundred_percent_scroll_sensitivity' ) ),
				]
			);
		}
	}
}

new FusionSC_Container();

/**
 * Map Column shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_builder_add_section() {

	$fusion_settings = fusion_get_fusion_settings();
	$is_builder      = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );
	$to_link         = '';

	if ( $is_builder ) {
		$to_link = '<span class="fusion-panel-shortcut" data-fusion-option="container_hundred_percent_height_mobile">' . __( 'theme options', 'fusion-builder' ) . '</span>';
	} else {
		$to_link = '<a href="' . esc_url( $fusion_settings->get_setting_link( 'container_hundred_percent_height_mobile' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'theme options', 'fusion-builder' ) . '</a>';
	}

	$subset   = [ 'top', 'right', 'bottom', 'left' ];
	$setting  = 'container_padding';
	$default  = rtrim( $fusion_settings->get_default_description( $setting . '_default', $subset, '' ), '.' );
	$default .= __( ' on default template. ', 'fusion-builder' );
	$default .= rtrim( $fusion_settings->get_default_description( $setting . '_100', $subset, '' ), '.' );
	$default .= __( ' on 100% width template.', 'fusion-builder' );
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Container',
			[
				'name'              => esc_attr__( 'Container', 'fusion-builder' ),
				'shortcode'         => 'fusion_builder_container',
				'hide_from_builder' => true,
				'help_url'          => 'https://theme-fusion.com/documentation/fusion-builder/elements/container-element/',
				'params'            => [
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Interior Content Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Select if the interior content is contained to site width or 100% width.', 'fusion-builder' ),
						'param_name'  => 'hundred_percent',
						'value'       => [
							'yes' => esc_attr__( '100% Width', 'fusion-builder' ),
							'no'  => esc_attr__( 'Site Width', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( '100% Height', 'fusion-builder' ),
						/* translators: URL. */
						'description' => sprintf( __( 'Select if the container should be fixed to 100%% height of the viewport. Larger content that is taller than the screen height will be cut off, this option works best with minimal content. <strong>Important:</strong> Mobile devices are even shorter in height so this option can be disabled on mobile in %s while still being active on desktop.', 'fusion-builder' ), $to_link ),
						'param_name'  => 'hundred_percent_height',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Enable 100% Height Scroll', 'fusion-builder' ),
						'description' => __( 'Select to add this container to a collection of 100% height containers that share scrolling navigation. <strong>Important:</strong> When this option is used, the mobile visibility settings are disabled.', 'fusion-builder' ),
						'param_name'  => 'hundred_percent_height_scroll',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'hundred_percent_height',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Center Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to center the content vertically on 100% height containers.', 'fusion-builder' ),
						'param_name'  => 'hundred_percent_height_center_content',
						'default'     => 'yes',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'hundred_percent_height',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Set Columns to Equal Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Select to set all columns that are used inside the container to have equal height.', 'fusion-builder' ),
						'param_name'  => 'equal_height_columns',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_toggle_class',
							'args'     => [
								'selector' => '.fusion-fullwidth',
								'classes'  => [
									'yes' => 'fusion-equal-height-columns',
									'no'  => '',
								],
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Name Of Menu Anchor', 'fusion-builder' ),
						'description' => esc_attr__( 'This name will be the id you will have to use in your one page menu.', 'fusion-builder' ),
						'param_name'  => 'menu_anchor',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Container Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the section on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'hundred_percent_height',
								'value'    => 'yes',
								'operator' => '!=',
							],
							[
								'element'  => 'hundred_percent_height_scroll',
								'value'    => 'yes',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Container Publishing Status', 'fusion-builder' ),
						'description' => __( 'Controls the publishing status of the container.  If draft is selected the container will only be visible to logged in users with the capability to publish posts.  If publish until or publish after are selected the container will be in draft mode when not published.', 'fusion-builder' ),
						'param_name'  => 'status',
						'default'     => 'published',
						'value'       => [
							'published'       => esc_attr__( 'Published', 'fusion-builder' ),
							'published_until' => esc_attr__( 'Published Until', 'fusion-builder' ),
							'publish_after'   => esc_attr__( 'Publish After', 'fusion-builder' ),
							'draft'           => esc_attr__( 'Draft', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'date_time_picker',
						'heading'     => esc_attr__( 'Container Publishing Date', 'fusion-builder' ),
						'description' => __( 'Controls when the container should be published.  Can be before a date or after a date.  Use SQL time format: YYYY-MM-DD HH:MM:SS. E.g: 2016-05-10 12:30:00.  Timezone of site is used.', 'fusion-builder' ),
						'param_name'  => 'publish_date',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'status',
								'value'    => 'published',
								'operator' => '!=',
							],
							[
								'element'  => 'status',
								'value'    => 'draft',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_add_class',
							'args'     => [
								'selector' => '.fusion-fullwidth',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_add_id',
							'args'     => [
								'selector' => '.fusion-fullwidth',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Container Link Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of container links.', 'fusion-builder' ),
						'param_name'  => 'link_color',
						'value'       => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'link_color' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Container Link Hover Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of container links in hover state.', 'fusion-builder' ),
						'param_name'  => 'link_hover_color',
						'value'       => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'primary_color' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Container Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the container element. In pixels.', 'fusion-builder' ),
						'param_name'  => 'border_size',
						'value'       => '',
						'min'         => '0',
						'max'         => '50',
						'default'     => $fusion_settings->get( 'full_width_border_size' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector' => '.fusion-fullwidth',
								'property' => [ 'border-bottom-width', 'border-top-width' ],
								'unit'     => 'px',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Container Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the container element.', 'fusion-builder' ),
						'param_name'  => 'border_color',
						'value'       => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'full_width_border_color' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector' => '.fusion-fullwidth',
								'property' => 'border-color',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Border Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border style.', 'fusion-builder' ),
						'param_name'  => 'border_style',
						'value'       => [
							'solid'  => esc_attr__( 'Solid', 'fusion-builder' ),
							'dashed' => esc_attr__( 'Dashed', 'fusion-builder' ),
							'dotted' => esc_attr__( 'Dotted', 'fusion-builder' ),
						],
						'default'     => 'solid',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'border_size',
								'value'    => '0',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector' => '.fusion-fullwidth',
								'property' => [ 'border-top-style', 'border-bottom-style' ],
							],
						],
					],
					'fusion_margin_placeholder'   => [
						'param_name'  => 'spacing',
						'description' => esc_attr__( 'Spacing above and below the section. Enter values including any valid CSS unit, ex: 4%.', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector'  => '.fusion-fullwidth',
								'property'  => [
									'margin_top'    => 'margin-top',
									'margin_bottom' => 'margin-bottom',
								],
								'dimension' => true,
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'fusion-builder' ) . $default,
						'param_name'       => 'dimensions',
						'value'            => [
							'padding_top'    => '',
							'padding_right'  => '',
							'padding_bottom' => '',
							'padding_left'   => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'callback'         => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector'  => '.fusion-fullwidth',
								'property'  => [
									'padding_top'    => 'padding-top',
									'padding_right'  => 'padding-right',
									'padding_bottom' => 'padding-bottom',
									'padding_left'   => 'padding-left',
								],
								'dimension' => true,
							],
						],
					],
					[
						'type'             => 'subgroup',
						'heading'          => esc_attr__( 'Background Options', 'fusion-builder' ),
						'description'      => esc_attr__( 'Use filters to see specific type of content.', 'fusion-builder' ),
						'param_name'       => 'background_type',
						'default'          => 'single',
						'group'            => esc_attr__( 'BG', 'fusion-builder' ),
						'remove_from_atts' => true,
						'value'            => [
							'single'   => esc_attr__( 'Color', 'fusion-builder' ),
							'gradient' => esc_attr__( 'Gradient', 'fusion-builder' ),
							'image'    => esc_attr__( 'Image', 'fusion-builder' ),
							'video'    => esc_attr__( 'Video', 'fusion-builder' ),
						],
						'icons'            => [
							'single'   => '<span class="fusiona-fill-drip-solid" style="font-size:18px;"></span>',
							'gradient' => '<span class="fusiona-gradient-fill" style="font-size:18px;"></span>',
							'image'    => '<span class="fusiona-image" style="font-size:18px;"></span>',
							'video'    => '<span class="fusiona-video" style="font-size:18px;"></span>',
						],
					],
					'fusion_gradient_placeholder' => [
						'selector' => '.fusion-fullwidth',
						'defaults' => 'TO',
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Container Background Color', 'fusion-builder' ),
						'param_name'  => 'background_color',
						'value'       => '',
						'description' => esc_attr__( 'Controls the background color of the container element.', 'fusion-builder' ),
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'single',
						],
						'default'     => $fusion_settings->get( 'full_width_bg_color' ),
						'callback'    => [
							'function' => 'fusion_preview',
							'args'     => [
								'selector' => '.fusion-fullwidth, .fullwidth-overlay',
								'property' => 'background-color',
							],
						],
					],
					[
						'type'         => 'upload',
						'heading'      => esc_attr__( 'Background Image', 'fusion-builder' ),
						'description'  => esc_attr__( 'Upload an image to display in the background.', 'fusion-builder' ),
						'param_name'   => 'background_image',
						'value'        => '',
						'group'        => esc_attr__( 'BG', 'fusion-builder' ),
						'dynamic_data' => true,
						'subgroup'     => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the position of the background image.', 'fusion-builder' ),
						'param_name'  => 'background_position',
						'value'       => [
							'left top'      => esc_attr__( 'Left Top', 'fusion-builder' ),
							'left center'   => esc_attr__( 'Left Center', 'fusion-builder' ),
							'left bottom'   => esc_attr__( 'Left Bottom', 'fusion-builder' ),
							'right top'     => esc_attr__( 'Right Top', 'fusion-builder' ),
							'right center'  => esc_attr__( 'Right Center', 'fusion-builder' ),
							'right bottom'  => esc_attr__( 'Right Bottom', 'fusion-builder' ),
							'center top'    => esc_attr__( 'Center Top', 'fusion-builder' ),
							'center center' => esc_attr__( 'Center Center', 'fusion-builder' ),
							'center bottom' => esc_attr__( 'Center Bottom', 'fusion-builder' ),
						],
						'default'     => 'center center',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Repeat', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how the background image repeats.', 'fusion-builder' ),
						'param_name'  => 'background_repeat',
						'value'       => [
							'no-repeat' => esc_attr__( 'No Repeat', 'fusion-builder' ),
							'repeat'    => esc_attr__( 'Repeat Vertically and Horizontally', 'fusion-builder' ),
							'repeat-x'  => esc_attr__( 'Repeat Horizontally', 'fusion-builder' ),
							'repeat-y'  => esc_attr__( 'Repeat Vertically', 'fusion-builder' ),
						],
						'default'     => 'no-repeat',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Fading Animation', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to have the background image fade and blur on scroll. WARNING: Only works for images.', 'fusion-builder' ),
						'param_name'  => 'fade',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Parallax', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how the background image scrolls and responds. This does not work for videos and must be set to "No Parallax" for the video to show.', 'fusion-builder' ),
						'param_name'  => 'background_parallax',
						'value'       => [
							'none'  => esc_attr__( 'No Parallax (no effects)', 'fusion-builder' ),
							'fixed' => esc_attr__( 'Fixed (fixed on desktop, non-fixed on mobile)', 'fusion-builder' ),
							'up'    => esc_attr__( 'Up (moves up on desktop and mobile)', 'fusion-builder' ),
							'down'  => esc_attr__( 'Down (moves down on desktop and mobile)', 'fusion-builder' ),
							'left'  => esc_attr__( 'Left (moves left on desktop and mobile)', 'fusion-builder' ),
							'right' => esc_attr__( 'Right (moves right on desktop and mobile)', 'fusion-builder' ),
						],
						'default'     => 'none',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Enable Parallax on Mobile', 'fusion-builder' ),
						'description' => esc_attr__( 'Works for up/down/left/right only. Parallax effects would most probably cause slowdowns when your site is viewed in mobile devices. If the device width is less than 980 pixels, then it is assumed that the site is being viewed in a mobile device.', 'fusion-builder' ),
						'param_name'  => 'enable_mobile',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Parallax Speed', 'fusion-builder' ),
						'description' => esc_attr__( 'The movement speed, value should be between 0.1 and 1.0. A lower number means slower scrolling speed. Higher scrolling speeds will enlarge the image more.', 'fusion-builder' ),
						'param_name'  => 'parallax_speed',
						'value'       => '0.3',
						'min'         => '0',
						'max'         => '1',
						'step'        => '0.1',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Background Blend Mode', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how blending should work for each background layer.', 'fusion-builder' ),
						'param_name'  => 'background_blend_mode',
						'value'       => [
							'none'        => esc_attr__( 'Disabled', 'fusion-builder' ),
							'multiply'    => esc_attr__( 'Multiply', 'fusion-builder' ),
							'screen'      => esc_attr__( 'Screen', 'fusion-builder' ),
							'overlay'     => esc_attr__( 'Overlay', 'fusion-builder' ),
							'darken'      => esc_attr__( 'Darken', 'fusion-builder' ),
							'lighten'     => esc_attr__( 'Lighten', 'fusion-builder' ),
							'color-dodge' => esc_attr__( 'Color Dodge', 'fusion-builder' ),
							'color-burn'  => esc_attr__( 'Color Burn', 'fusion-builder' ),
							'hard-light'  => esc_attr__( 'Hard Light', 'fusion-builder' ),
							'soft-light'  => esc_attr__( 'Soft Light', 'fusion-builder' ),
							'difference'  => esc_attr__( 'Difference', 'fusion-builder' ),
							'exclusion'   => esc_attr__( 'Exclusion', 'fusion-builder' ),
							'hue'         => esc_attr__( 'Hue', 'fusion-builder' ),
							'saturation'  => esc_attr__( 'Saturation', 'fusion-builder' ),
							'color'       => esc_attr__( 'Color', 'fusion-builder' ),
							'luminosity'  => esc_attr__( 'Luminosity', 'fusion-builder' ),
						],
						'default'     => 'none',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'image',
						],
						'dependency'  => [
							[
								'element'  => 'background_image',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'uploadfile',
						'heading'     => esc_attr__( 'Video MP4 Upload', 'fusion-builder' ),
						'description' => esc_attr__( 'Add your MP4 video file. This format must be included to render your video with cross-browser compatibility. WebM and OGV are optional. Using videos in a 16:9 aspect ratio is recommended.', 'fusion-builder' ),
						'param_name'  => 'video_mp4',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
					],
					[
						'type'        => 'uploadfile',
						'heading'     => esc_attr__( 'Video WebM Upload', 'fusion-builder' ),
						'description' => esc_attr__( 'Add your WebM video file. This is optional, only MP4 is required to render your video with cross-browser compatibility. Using videos in a 16:9 aspect ratio is recommended.', 'fusion-builder' ),
						'param_name'  => 'video_webm',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
					],
					[
						'type'        => 'uploadfile',
						'heading'     => esc_attr__( 'Video OGV Upload', 'fusion-builder' ),
						'description' => esc_attr__( 'Add your OGV video file. This is optional, only MP4 is required to render your video with cross-browser compatibility. Using videos in a 16:9 aspect ratio is recommended.', 'fusion-builder' ),
						'param_name'  => 'video_ogv',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'YouTube/Vimeo Video URL or ID', 'fusion-builder' ),
						'description' => esc_attr__( "Enter the URL to the video or the video ID of your YouTube or Vimeo video you want to use as your background. If your URL isn't showing a video, try inputting the video ID instead. Ads will show up in the video if it has them.", 'fusion-builder' ),
						'param_name'  => 'video_url',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Video Aspect Ratio', 'fusion-builder' ),
						'description' => esc_attr__( 'The video will be resized to maintain this aspect ratio, this is to prevent the video from showing any black bars. Enter an aspect ratio here such as: "16:9", "4:3" or "16:10". The default is "16:9".', 'fusion-builder' ),
						'param_name'  => 'video_aspect_ratio',
						'value'       => '16:9',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'video_mp4',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_ogv',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_webm',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_url',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'       => 'radio_button_set',
						'heading'    => esc_attr__( 'Loop Video', 'fusion-builder' ),
						'param_name' => 'video_loop',
						'value'      => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'    => 'yes',
						'group'      => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'   => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
						'or'         => true,
						'dependency' => [
							[
								'element'  => 'video_mp4',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_ogv',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_webm',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_url',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Mute Video', 'fusion-builder' ),
						'description' => esc_attr__( 'IMPORTANT: In some modern browsers, videos with sound won\'t be auto played, and thus won\'t show as container background when not muted.', 'fusion-builder' ),
						'param_name'  => 'video_mute',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'video_mp4',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_ogv',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_webm',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_url',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Video Preview Image', 'fusion-builder' ),
						'description' => esc_attr__( 'IMPORTANT: This field is a fallback for self-hosted videos in older browsers that are not able to play the video. If your site is optimized for modern browsers, this field does not need to be filled in.', 'fusion-builder' ),
						'param_name'  => 'video_preview_image',
						'value'       => '',
						'group'       => esc_attr__( 'BG', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'background_type',
							'tab'  => 'video',
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'video_mp4',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_ogv',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_webm',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'video_url',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					'fusion_filter_placeholder'   => [
						'selector_base' => 'fusion-builder-row-live-',
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_builder_add_section' );
