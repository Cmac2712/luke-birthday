<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_imageframe' ) ) {

	if ( ! class_exists( 'FusionSC_Imageframe' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @package fusion-builder
		 * @since 1.0
		 */
		class FusionSC_Imageframe extends Fusion_Element {

			/**
			 * The image-frame counter.
			 *
			 * @access private
			 * @since 1.0
			 * @var int
			 */
			private $imageframe_counter = 1;

			/**
			 * The image data.
			 *
			 * @access private
			 * @since 1.0
			 * @var false|array
			 */
			private $image_data = false;

			/**
			 * The lightbox image data.
			 *
			 * @access private
			 * @since 1.7
			 * @var false|array
			 */
			private $lightbox_image_data = false;

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
				add_filter( 'fusion_attr_image-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_image-shortcode-link', [ $this, 'link_attr' ] );

				add_shortcode( 'fusion_imageframe', [ $this, 'render' ] );

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
					'align'                   => '',
					'alt'                     => '',
					'animation_direction'     => 'left',
					'animation_offset'        => $fusion_settings->get( 'animation_offset' ),
					'animation_speed'         => '',
					'animation_type'          => '',
					'blur'                    => $fusion_settings->get( 'imageframe_blur' ),
					'bordercolor'             => $fusion_settings->get( 'imgframe_border_color' ),
					'borderradius'            => intval( $fusion_settings->get( 'imageframe_border_radius' ) ) . 'px',
					'bordersize'              => $fusion_settings->get( 'imageframe_border_size' ),
					'class'                   => '',
					'gallery_id'              => '',
					'hide_on_mobile'          => fusion_builder_default_visibility( 'string' ),
					'hover_type'              => 'none',
					'image_id'                => '',
					'id'                      => '',
					'lightbox'                => 'no',
					'lightbox_image'          => '',
					'lightbox_image_id'       => '',
					'link'                    => '',
					'linktarget'              => '_self',
					'max_width'               => '',
					'stylecolor'              => $fusion_settings->get( 'imgframe_style_color' ),
					'style_type'              => $fusion_settings->get( 'imageframe_style_type' ),

					// Filters.
					'filter_hue'              => '0',
					'filter_saturation'       => '100',
					'filter_brightness'       => '100',
					'filter_contrast'         => '100',
					'filter_invert'           => '0',
					'filter_sepia'            => '0',
					'filter_opacity'          => '100',
					'filter_blur'             => '0',
					'filter_hue_hover'        => '0',
					'filter_saturation_hover' => '100',
					'filter_brightness_hover' => '100',
					'filter_contrast_hover'   => '100',
					'filter_invert_hover'     => '0',
					'filter_sepia_hover'      => '0',
					'filter_opacity_hover'    => '100',
					'filter_blur_hover'       => '0',

					// Deprecated params.
					'style'                   => '',
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
					'animation_offset'         => 'animation_offset',
					'imageframe_blur'          => 'blur',
					'imgframe_border_color'    => 'bordercolor',
					'imageframe_border_radius' => 'borderradius',
					'imageframe_border_size'   => 'bordersize',
					'imgframe_style_color'     => 'stylecolor',
					'imageframe_style_type'    => 'style_type',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode paramters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				global $fusion_settings;

				$this->set_element_id( $this->imageframe_counter );

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_imageframe' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_imageframe', $args );

				$defaults['blur']         = FusionBuilder::validate_shortcode_attr_value( $defaults['blur'], 'px' );
				$defaults['borderradius'] = FusionBuilder::validate_shortcode_attr_value( $defaults['borderradius'], 'px' );
				$defaults['bordersize']   = FusionBuilder::validate_shortcode_attr_value( $defaults['bordersize'], 'px' );

				if ( ! $defaults['style'] ) {
					$defaults['style'] = $defaults['style_type'];
				}

				if ( $defaults['borderradius'] && 'bottomshadow' === $defaults['style'] ) {
					$defaults['borderradius'] = '0';
				}

				if ( 'round' === $defaults['borderradius'] ) {
					$defaults['borderradius'] = '50%';
				}

				extract( $defaults );

				$this->args = $defaults;

				$border_radius = '';

				if ( '0' !== $borderradius && 0 !== $borderradius && '0px' !== $borderradius ) {
					$border_radius .= "-webkit-border-radius:{$borderradius};-moz-border-radius:{$borderradius};border-radius:{$borderradius};";
				}

				// Alt tag.
				$title = $alt_tag = $image_url = $image_id = $image_width = $image_height = '';

				// The URL is added as element content, but where image ID was not available.
				if ( false === strpos( $content, '<img' ) && $content ) {
					$src = $content;
				} else {
					// Old version, where the img tag was added in element contant.
					preg_match( '/(src=["\'](.*?)["\'])/', $content, $src );
					if ( array_key_exists( '2', $src ) ) {
						$src = $src[2];
					}
				}

				if ( $src ) {

					$src = str_replace( '&#215;', 'x', $src );

					$image_url = $this->args['pic_link'] = $src;

					$lightbox_image = $this->args['pic_link'];
					if ( $this->args['lightbox_image'] ) {
						$lightbox_image = $this->args['lightbox_image'];

						$this->lightbox_image_data = fusion_library()->images->get_attachment_data_by_helper( $this->args['lightbox_image_id'], $this->args['lightbox_image'] );
					}

					$this->image_data = fusion_library()->images->get_attachment_data_by_helper( $this->args['image_id'], $this->args['pic_link'] );

					if ( $this->image_data['url'] ) {
						$image_url = $this->image_data['url'];
					}

					$image_width  = $this->image_data['width'];
					$image_height = $this->image_data['height'];
					$image_id     = $this->image_data['id'];
					$alt_tag      = $this->image_data['alt'];
					$title        = $this->image_data['title_attribute'];

					// For pre 5.0 shortcodes extract the alt tag.
					preg_match( '/(alt=["\'](.*?)["\'])/', $content, $legacy_alt );
					if ( array_key_exists( '2', $legacy_alt ) && '' !== $legacy_alt[2] ) {
						$alt_tag = $legacy_alt[2];
					} elseif ( $alt ) {
						$alt_tag = $alt;
					}

					if ( false !== strpos( $content, 'alt=""' ) && $alt_tag ) {
						$content = str_replace( 'alt=""', $alt_tag, $content );
					} elseif ( false === strpos( $content, 'alt' ) && $alt_tag ) {
						$content = str_replace( '/> ', $alt_tag . ' />', $content );
					}

					if ( 'no' === $lightbox && ! $link ) {
						$title = ' title="' . $title . '"';
					} else {
						$title = '';
					}
					$image_width  = $image_width ? 'width="' . $image_width . '"' : '';
					$image_height = $image_height ? 'height="' . $image_height . '"' : '';
					$content      = '<img src="' . $image_url . '" ' . $image_width . ' ' . $image_height . ' alt="' . $alt_tag . '"' . $title . ' />';
				}

				$img_classes = 'img-responsive';

				if ( ! empty( $image_id ) ) {
					$img_classes .= ' wp-image-' . $image_id;
				}

				// Get custom classes from the img tag.
				preg_match( '/(class=["\'](.*?)["\'])/', $content, $classes );

				if ( ! empty( $classes ) ) {
					$img_classes .= ' ' . $classes[2];
				}

				$img_classes = 'class="' . $img_classes . '"';

				// Add custom and responsive class to the img tag.
				if ( ! empty( $classes ) ) {
					$content = str_replace( $classes[0], $img_classes, $content );
				} else {
					$content = str_replace( '/>', $img_classes . '/>', $content );
				}

				fusion_library()->images->set_grid_image_meta(
					[
						'layout'  => 'large',
						'columns' => '1',
					]
				);

				if ( function_exists( 'wp_make_content_images_responsive' ) ) {
					$content = wp_make_content_images_responsive( $content );
				}

				$content = fusion_library()->images->apply_lazy_loading( $content, null, $image_id, 'full' );

				fusion_library()->images->set_grid_image_meta( [] );

				// Set the lightbox image to the dedicated link if it is set.
				if ( $lightbox_image ) {
					$this->args['pic_link'] = $lightbox_image;
				}

				$output = do_shortcode( $content );

				if ( 'yes' === $lightbox || $link ) {
					$output = '<a ' . FusionBuilder::attributes( 'image-shortcode-link' ) . '>' . do_shortcode( $content ) . '</a>';
				}

				$html = '<span ' . FusionBuilder::attributes( 'image-shortcode' ) . '>' . $output . '</span>';

				if ( 'liftup' === $hover_type || ( 'bottomshadow' === $style && ( 'none' === $hover_type || 'zoomin' === $hover_type || 'zoomout' === $hover_type ) ) ) {
					$stylecolor = ( '#' === $this->args['stylecolor'][0] ) ? Fusion_Color::new_color( $this->args['stylecolor'] )->get_new( 'alpha', '0.4' )->to_css( 'rgba' ) : Fusion_Color::new_color( $this->args['stylecolor'] )->to_css( 'rgba' );

					if ( 'liftup' === $hover_type ) {
						$wrapper_classes = 'imageframe-liftup';
						$element_styles  = '';

						if ( 'left' === $align ) {
							$wrapper_classes .= ' fusion-imageframe-liftup-left';
						} elseif ( 'right' === $align ) {
							$wrapper_classes .= ' fusion-imageframe-liftup-right';
						}

						if ( $border_radius ) {
							$element_styles   = '.imageframe-liftup.imageframe-' . $this->element_id . ':before{' . $border_radius . '}';
							$wrapper_classes .= ' imageframe-' . $this->element_id;
						}

						if ( 'bottomshadow' === $style ) {
							$element_styles  .= '.element-bottomshadow.imageframe-' . $this->element_id . ':before, .element-bottomshadow.imageframe-' . $this->element_id . ':after{';
							$element_styles  .= '-webkit-box-shadow: 0 17px 10px ' . $stylecolor . ';box-shadow: 0 17px 10px ' . $stylecolor . ';}';
							$wrapper_classes .= ' fusion-image-frame-bottomshadow image-frame-shadow-' . $this->element_id;
						}

						if ( $element_styles ) {
							$element_styles = '<style>' . $element_styles . '</style>';
						}
					} else {
						if ( 'zoomin' === $hover_type || 'zoomout' === $hover_type ) {
							$wrapper_classes = 'fusion-image-frame-bottomshadow element-bottomshadow image-frame-shadow-' . $this->element_id;
						} else {
							$wrapper_classes = 'fusion-image-frame-bottomshadow image-frame-shadow-' . $this->element_id;
						}
						$element_styles = '';
						$element_styles = '<style>.fusion-image-frame-bottomshadow.image-frame-shadow-' . $this->element_id . '{';
						if ( 'left' === $align ) {
							$element_styles .= 'margin-right:25px;float:left;';
						} elseif ( 'right' === $align ) {
							$element_styles .= 'margin-left:25px;float:right;';
						}
						$element_styles .= 'display:inline-block}';

						$element_styles .= '.element-bottomshadow.imageframe-' . $this->element_id . ':before, .element-bottomshadow.imageframe-' . $this->element_id . ':after{';
						$element_styles .= '-webkit-box-shadow: 0 17px 10px ' . $stylecolor . ';box-shadow: 0 17px 10px ' . $stylecolor . ';}';

						$element_styles .= '</style>';
					}

					$wrapper_classes = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $wrapper_classes );

					$wrapper_styles = '';
					if ( $max_width ) {
						$wrapper_styles = 'style="width:100%;max-width:' . $max_width . ';"';
					}

					$html = '<div ' . FusionBuilder::attributes( $wrapper_classes ) . $wrapper_styles . '>' . $element_styles . $html . '</div>';
				}

				if ( 'center' === $align ) {
					$html = '<div ' . FusionBuilder::attributes( 'imageframe-align-center' ) . '>' . $html . '</div>';
				}

				// Add filter styles.
				$filter_style = Fusion_Builder_Filter_Helper::get_filter_style_element( $this->args, '.imageframe-' . $this->element_id );
				if ( '' !== $filter_style ) {
					$html .= $filter_style;
				}

				$this->imageframe_counter++;
				$this->lightbox_image_data = false;

				return apply_filters( 'fusion_element_image_content', $html, $args );

			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				global $fusion_settings;

				$attr = [
					'style' => '',
				];

				$visibility_classes_need_set = false;

				$bordercolor  = $this->args['bordercolor'];
				$stylecolor   = ( '#' === $this->args['stylecolor'][0] ) ? Fusion_Color::new_color( $this->args['stylecolor'] )->get_new( 'alpha', '0.3' )->to_css( 'rgba' ) : Fusion_Color::new_color( $this->args['stylecolor'] )->to_css( 'rgba' );
				$blur         = $this->args['blur'];
				$blur_radius  = ( (int) $blur + 4 ) . 'px';
				$bordersize   = $this->args['bordersize'];
				$borderradius = $this->args['borderradius'];
				$style        = $this->args['style'];
				$img_styles   = '';

				if ( '0' !== $bordersize && 0 !== $bordersize && '0px' !== $bordersize ) {
					$img_styles .= "border:{$bordersize} solid {$bordercolor};";
				}

				if ( '0' !== $borderradius && 0 !== $borderradius && '0px' !== $borderradius ) {
					$img_styles .= "-webkit-border-radius:{$borderradius};-moz-border-radius:{$borderradius};border-radius:{$borderradius};";

					if ( '50%' === $borderradius || 100 < intval( $borderradius ) ) {
							$img_styles .= '-webkit-mask-image: -webkit-radial-gradient(circle, white, black);';
					}
				}

				if ( 'glow' === $style ) {
					$img_styles .= "-webkit-box-shadow: 0 0 {$blur} {$stylecolor};box-shadow: 0 0 {$blur} {$stylecolor};";
				} elseif ( 'dropshadow' === $style ) {
					$img_styles .= "-webkit-box-shadow: {$blur} {$blur} {$blur_radius} {$stylecolor};box-shadow: {$blur} {$blur} {$blur_radius} {$stylecolor};";
				}

				if ( $img_styles ) {
					$attr['style'] .= $img_styles;
				}

				$attr['class'] = 'fusion-imageframe imageframe-' . $this->args['style'] . ' imageframe-' . $this->element_id;

				if ( 'bottomshadow' === $this->args['style'] ) {
					$attr['class'] .= ' element-bottomshadow';
				}

				if ( 'liftup' !== $this->args['hover_type'] && ( 'bottomshadow' !== $this->args['style'] && ( 'zoomin' !== $this->args['hover_type'] || 'zoomout' !== $this->args['hover_type'] ) ) ) {
					$visibility_classes_need_set = true;

					if ( 'left' === $this->args['align'] ) {
						$attr['style'] .= 'margin-right:25px;float:left;';
					} elseif ( 'right' === $this->args['align'] ) {
						$attr['style'] .= 'margin-left:25px;float:right;';
					}
				}

				if ( $this->args['max_width'] && 'liftup' !== $this->args['hover_type'] && 'bottomshadow' !== $this->args['style'] ) {
					$attr['style'] .= 'width:100%;max-width:' . $this->args['max_width'] . ';';
				}

				if ( 'liftup' !== $this->args['hover_type'] ) {
					$attr['class'] .= ' hover-type-' . $this->args['hover_type'];
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				if ( $visibility_classes_need_set ) {
					return fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );
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
			public function link_attr() {

				$attr = [];

				if ( 'yes' === $this->args['lightbox'] ) {
					$attr['href']  = $this->args['pic_link'];
					$attr['class'] = 'fusion-lightbox';

					if ( $this->args['gallery_id'] || '0' === $this->args['gallery_id'] ) {
						$attr['data-rel'] = 'iLightbox[' . $this->args['gallery_id'] . ']';
					} else {
						$attr['data-rel'] = 'iLightbox[' . substr( md5( $this->args['pic_link'] ), 13 ) . ']';
					}

					if ( $this->lightbox_image_data ) {
						$attr['data-caption'] = $this->lightbox_image_data['caption'];
						$attr['data-title']   = $this->lightbox_image_data['title'];
					} elseif ( $this->image_data ) {
						$attr['data-caption'] = $this->image_data['caption'];
						$attr['data-title']   = $this->image_data['title'];
					}

					if ( $this->image_data ) {
						$attr['title'] = $this->image_data['title'];
					}
				} elseif ( $this->args['link'] ) {
					$attr['class']      = 'fusion-no-lightbox';
					$attr['href']       = $this->args['link'];
					$attr['target']     = $this->args['linktarget'];
					$attr['aria-label'] = ( $this->image_data ) ? $this->image_data['title'] : '';
					if ( '_blank' === $this->args['linktarget'] ) {
						$attr['rel'] = 'noopener noreferrer';
					}
				}

				return $attr;

			}

			/**
			 * Adds settings to element options panel.
			 *
			 * @access public
			 * @since 1.1
			 * @return array $sections Image Frame settings.
			 */
			public function add_options() {

				return [
					'imageframe_shortcode_section' => [
						'label'       => esc_html__( 'Image', 'fusion-builder' ),
						'description' => '',
						'id'          => 'imageframe_shortcode_section',
						'type'        => 'accordion',
						'icon'        => 'fusiona-image',
						'fields'      => [
							'imageframe_style_type'    => [
								'label'       => esc_html__( 'Image Style Type', 'fusion-builder' ),
								'description' => esc_html__( 'Select the style type.', 'fusion-builder' ),
								'id'          => 'imageframe_style_type',
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
							'imageframe_blur'          => [
								'label'           => esc_html__( 'Image Glow / Drop Shadow Blur', 'fusion-builder' ),
								'description'     => esc_html__( 'Choose the amount of blur added to glow or drop shadow effect.', 'fusion-builder' ),
								'id'              => 'imageframe_blur',
								'default'         => '3',
								'type'            => 'slider',
								'transport'       => 'postMessage',
								'choices'         => [
									'min'  => '0',
									'max'  => '50',
									'step' => '1',
								],
								'soft_dependency' => true,
							],
							'imgframe_style_color'     => [
								'label'           => esc_html__( 'Image Style Color', 'fusion-builder' ),
								'description'     => esc_html__( 'Controls the style color for all style types except border. Hex colors will use a subtle auto added alpha level to produce a nice effect.', 'fusion-builder' ),
								'id'              => 'imgframe_style_color',
								'default'         => '#000000',
								'type'            => 'color-alpha',
								'transport'       => 'postMessage',
								'soft_dependency' => true,
							],
							'imageframe_border_size'   => [
								'label'       => esc_html__( 'Image Border Size', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border size of the image.', 'fusion-builder' ),
								'id'          => 'imageframe_border_size',
								'default'     => '0',
								'type'        => 'slider',
								'transport'   => 'postMessage',
								'choices'     => [
									'min'  => '0',
									'max'  => '50',
									'step' => '1',
								],
							],
							'imgframe_border_color'    => [
								'label'           => esc_html__( 'Image Border Color', 'fusion-builder' ),
								'description'     => esc_html__( 'Controls the border color of the image.', 'fusion-builder' ),
								'id'              => 'imgframe_border_color',
								'default'         => '#e2e2e2',
								'type'            => 'color-alpha',
								'transport'       => 'postMessage',
								'soft_dependency' => true,
							],
							'imageframe_border_radius' => [
								'label'       => esc_html__( 'Image Border Radius', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border radius of the image.', 'fusion-builder' ),
								'id'          => 'imageframe_border_radius',
								'default'     => '0px',
								'type'        => 'dimension',
								'choices'     => [ 'px', '%' ],
								'transport'   => 'postMessage',
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
				Fusion_Dynamic_JS::enqueue_script( 'fusion-animations' );
				Fusion_Dynamic_JS::enqueue_script( 'fusion-lightbox' );
			}
		}
	}

	new FusionSC_Imageframe();

}

/**
 * Map shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_element_image() {

	global $fusion_settings;

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Imageframe',
			[
				'name'       => esc_attr__( 'Image', 'fusion-builder' ),
				'shortcode'  => 'fusion_imageframe',
				'icon'       => 'fusiona-image',
				'preview'    => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-image-frame-preview.php',
				'preview_id' => 'fusion-builder-block-module-image-frame-preview-template',
				'help_url'   => 'https://theme-fusion.com/documentation/fusion-builder/elements/image-element/',
				'params'     => [
					[
						'type'         => 'upload',
						'heading'      => esc_attr__( 'Image', 'fusion-builder' ),
						'description'  => esc_attr__( 'Upload an image to display.', 'fusion-builder' ),
						'param_name'   => 'element_content',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'image_id',
						'value'       => '',
						'hidden'      => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image Max Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the maximum width the image should take up. Enter value including any valid CSS unit, ex: 200px. Leave empty to use full image width.', 'fusion-builder' ),
						'param_name'  => 'max_width',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Style Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the style type.', 'fusion-builder' ),
						'param_name'  => 'style_type',
						'value'       => [
							''             => esc_attr__( 'Default', 'fusion-builder' ),
							'none'         => esc_attr__( 'None', 'fusion-builder' ),
							'glow'         => esc_attr__( 'Glow', 'fusion-builder' ),
							'dropshadow'   => esc_attr__( 'Drop Shadow', 'fusion-builder' ),
							'bottomshadow' => esc_attr__( 'Bottom Shadow', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Glow / Drop Shadow Blur', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the amount of blur added to glow or drop shadow effect. In pixels.', 'fusion-builder' ),
						'param_name'  => 'blur',
						'value'       => '',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'default'     => $fusion_settings->get( 'imageframe_blur' ),
						'dependency'  => [
							[
								'element'  => 'style_type',
								'value'    => 'none',
								'operator' => '!=',
							],
							[
								'element'  => 'style_type',
								'value'    => 'bottomshadow',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Style Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the style color for all style types except border. Hex colors will use a subtle auto added alpha level to produce a nice effect.', 'fusion-builder' ),
						'param_name'  => 'stylecolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'imgframe_style_color' ),
						'dependency'  => [
							[
								'element'  => 'style_type',
								'value'    => 'none',
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
						'preview'     => [
							'selector' => '.fusion-imageframe',
							'type'     => 'class',
							'toggle'   => 'hover',
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'In pixels.', 'fusion-builder' ),
						'param_name'  => 'bordersize',
						'value'       => '',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'default'     => $fusion_settings->get( 'imageframe_border_size' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color. ', 'fusion-builder' ),
						'param_name'  => 'bordercolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'imgframe_border_color' ),
						'dependency'  => [
							[
								'element'  => 'bordersize',
								'value'    => '0',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Border Radius', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the image border radius. In pixels (px), ex: 1px, or "round". ', 'fusion-builder' ),
						'param_name'  => 'borderradius',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'style_type',
								'value'    => 'bottomshadow',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Align', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how to align the image.', 'fusion-builder' ),
						'param_name'  => 'align',
						'value'       => [
							'none'   => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
						],
						'default'     => 'none',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image lightbox', 'fusion-builder' ),
						'description' => esc_attr__( 'Show image in lightbox. Lightbox must be enabled in Theme Options or the image will open up in the same tab by itself.', 'fusion-builder' ),
						'param_name'  => 'lightbox',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Gallery ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Set a name for the lightbox gallery this image should belong to.', 'fusion-builder' ),
						'param_name'  => 'gallery_id',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'lightbox',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Lightbox Image', 'fusion-builder' ),
						'description' => esc_attr__( 'Upload an image that will show up in the lightbox.', 'fusion-builder' ),
						'param_name'  => 'lightbox_image',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'lightbox',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Lightbox Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Lightbox Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'lightbox_image_id',
						'value'       => '',
						'hidden'      => true,
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Alt Text', 'fusion-builder' ),
						'description'  => esc_attr__( 'The alt attribute provides alternative information if an image cannot be viewed.', 'fusion-builder' ),
						'param_name'   => 'alt',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'         => 'link_selector',
						'heading'      => esc_attr__( 'Picture Link URL', 'fusion-builder' ),
						'description'  => esc_attr__( 'Add the URL the picture will link to, ex: http://example.com.', 'fusion-builder' ),
						'param_name'   => 'link',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'lightbox',
								'value'    => 'yes',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => __( '_self = open in same window<br />_blank = open in new window.', 'fusion-builder' ),
						'param_name'  => 'linktarget',
						'value'       => [
							'_self'  => esc_attr__( '_self', 'fusion-builder' ),
							'_blank' => esc_attr__( '_blank', 'fusion-builder' ),
						],
						'default'     => '_self',
						'dependency'  => [
							[
								'element'  => 'lightbox',
								'value'    => 'yes',
								'operator' => '!=',
							],
							[
								'element'  => 'link',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-imageframe',
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
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					'fusion_filter_placeholder'    => [
						'selector_base' => 'imageframe-cid',
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_image' );
