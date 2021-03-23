<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 2.2.0
 */

if ( fusion_is_element_enabled( 'fusion_search' ) ) {

	if ( ! class_exists( 'FusionSC_Search' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 2.2.0
		 */
		class FusionSC_Search extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 2.2.0
			 * @var array
			 */
			protected $args;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 2.2.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_search-element', [ $this, 'attr' ] );

				add_shortcode( 'fusion_search', [ $this, 'render' ] );

				if ( ! is_admin() ) {
					add_filter( 'pre_get_posts', [ $this, 'modify_search_filter' ] );
				}
			}

			/**
			 * Modifies the search filter.
			 *
			 * @access public
			 * @since 2.2.0
			 * @param object $query The search query.
			 * @return object $query The modified search query.
			 */
			public function modify_search_filter( $query ) {
				if ( is_search() && $query->is_search ) {

					if ( isset( $_GET ) && isset( $_GET['fs'] ) && isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$query->set( 'post_type', wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
					}
				}

				return $query;
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'search_form_design' => 'design',
				];
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = fusion_get_fusion_settings();
				return [
					'animation_type'              => '',
					'animation_direction'         => 'down',
					'animation_speed'             => '',
					'animation_offset'            => $fusion_settings->get( 'animation_offset' ),
					'class'                       => '',
					'search_content'              => '',
					'placeholder'                 => 'Search...',
					'design'                      => $fusion_settings->get( 'search_form_design' ),
					'live_search'                 => $fusion_settings->get( 'live_search' ) ? 'yes' : 'no',
					'search_limit_to_post_titles' => $fusion_settings->get( 'search_limit_to_post_titles' ) ? 'yes' : 'no',
					'hide_on_mobile'              => fusion_builder_default_visibility( 'string' ),
					'id'                          => '',
					'margin_bottom'               => '',
					'margin_left'                 => '',
					'margin_right'                => '',
					'margin_top'                  => '',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 2.2.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$defaults   = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_search' );
				$this->args = $defaults;

				$this->args['margin_top']    = fusion_library()->sanitize->get_value_with_unit( $this->args['margin_top'] );
				$this->args['margin_right']  = fusion_library()->sanitize->get_value_with_unit( $this->args['margin_right'] );
				$this->args['margin_bottom'] = fusion_library()->sanitize->get_value_with_unit( $this->args['margin_bottom'] );
				$this->args['margin_left']   = fusion_library()->sanitize->get_value_with_unit( $this->args['margin_left'] );

				$html  = '';
				$html .= '<div ' . FusionBuilder::attributes( 'search-element' ) . '>';
				$html .= $this->get_search_form();
				$html .= '</div>';

				return apply_filters( 'fusion_element_search_content', $html, $args );
			}

			/**
			 * Get the searchform
			 *
			 * @access public
			 * @since 2.1
			 * @return array
			 */
			public function get_search_form() {
				$extra_fields   = '';
				$search_content = explode( ',', $this->args['search_content'] );

				if ( $search_content ) {
					foreach ( $search_content as $value ) {
						$extra_fields .= '<input type="hidden" name="post_type[]" value="' . $value . '" />';
					}
				}

				if ( 'yes' === $this->args['search_limit_to_post_titles'] ) {
					$extra_fields .= '<input type="hidden" name="search_limit_to_post_titles" value="1" />';
				}

				// Activate the search filter.
				$extra_fields .= '<input type="hidden" name="fs" value="1" />';

				$args = [
					'live_search'  => 'yes' === $this->args['live_search'] ? 1 : 0,
					'design'       => $this->args['design'],
					'after_fields' => $extra_fields,
				];

				if ( $this->args['placeholder'] ) {
					$args['placeholder'] = $this->args['placeholder'];
				}

				ob_start();
				Fusion_Searchform::get_form( $args );
				return ob_get_clean();
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public function attr() {
				global $fusion_settings;

				$attr = [
					'class' => 'fusion-search-element',
					'style' => '',
				];

				// Top margin.
				if ( ! empty( $this->args['margin_top'] ) ) {
					$attr['style'] .= 'margin-top:' . esc_attr( $this->args['margin_top'] ) . ';';
				}

				// Right margin.
				if ( ! empty( $this->args['margin_right'] ) ) {
					$attr['style'] .= 'margin-right:' . esc_attr( $this->args['margin_right'] ) . ';';
				}

				// Bottom margin.
				if ( ! empty( $this->args['margin_bottom'] ) ) {
					$attr['style'] .= 'margin-bottom:' . esc_attr( $this->args['margin_bottom'] ) . ';';
				}

				// Left margin.
				if ( ! empty( $this->args['margin_left'] ) ) {
					$attr['style'] .= 'margin-left:' . esc_attr( $this->args['margin_left'] ) . ';';
				}

				// Animation class.
				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['design'] ) {
					$attr['class'] .= ' fusion-search-form-' . $this->args['design'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

		}
	}

	new FusionSC_Search();

}

/**
 * Map shortcode to Fusion Builder.
 *
 * @since 2.2.0
 */
function fusion_element_search() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Search',
			[
				'name'       => esc_attr__( 'Search', 'fusion-builder' ),
				'shortcode'  => 'fusion_search',
				'icon'       => 'fusiona-search',
				'preview'    => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-search-preview.php',
				'preview_id' => 'fusion-builder-block-module-search-preview-template',
				'help_url'   => '',
				'params'     => [
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Enable Live Search', 'fusion-builder' ),
						'description' => esc_attr__( 'Turn on to enable live search results on menu search field and other fitting search forms.', 'fusion-builder' ),
						'param_name'  => 'live_search',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Search Form Design', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the design of the search form.', 'fusion-builder' ),
						'param_name'  => 'design',
						'default'     => '',
						'value'       => [
							''        => esc_attr__( 'Default', 'fusion-builder' ),
							'classic' => esc_attr__( 'Classic', 'fusion-builder' ),
							'clean'   => esc_attr__( 'Clean', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'multiple_select',
						'heading'     => esc_attr__( 'Search Results Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the type of content that displays in search results.', 'fusion-builder' ),
						'param_name'  => 'search_content',
						'default'     => [ 'post', 'page', 'avada_portfolio', 'avada_faq' ],
						'choices'     => [
							'post'            => esc_attr__( 'Posts', 'fusion-builder' ),
							'page'            => esc_attr__( 'Pages', 'fusion-builder' ),
							'avada_portfolio' => esc_attr__( 'Portfolio Items', 'fusion-builder' ),
							'avada_faq'       => esc_attr__( 'FAQ Items', 'fusion-builder' ),
							'product'         => esc_attr__( 'WooCommerce Products', 'fusion-builder' ),
							'tribe_events'    => esc_attr__( 'Events Calendar Posts', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Limit Search to Post Titles', 'fusion-builder' ),
						'description' => esc_attr__( 'Turn on to limit the search to post titles only.', 'fusion-builder' ),
						'param_name'  => 'search_limit_to_post_titles',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Placeholder', 'fusion-builder' ),
						'description' => esc_attr__( 'Search placeholder', 'fusion-builder' ),
						'param_name'  => 'placeholder',
						'value'       => '',
					],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-search-element',
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'Enter values including any valid CSS unit, ex: 4%.', 'fusion-builder' ),
						'param_name'       => 'margin',
						'value'            => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
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
add_action( 'fusion_builder_before_init', 'fusion_element_search' );
