<?php
/**
 * Main Fusion_App Class.
 *
 * @since 2.0
 * @package fusion-library
 */

/**
 * Main Fusion_App Class.
 *
 * @since 2.0
 */
class Fusion_App {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 2.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Has data to filter in.
	 *
	 * @access protected
	 * @since 2.0
	 * @var bool
	 */
	protected $has_data = false;

	/**
	 * Data we want to emulate.
	 *
	 * @access protected
	 * @since 2.0
	 * @var bool
	 */
	protected $data = [];

	/**
	 * Is this the preview?
	 *
	 * @access protected
	 * @since 2.0
	 * @var bool
	 */
	protected $is_preview = false;

	/**
	 * Is this preview only?
	 *
	 * @access protected
	 * @since 2.0
	 * @var bool
	 */
	protected $is_preview_only = false;

	/**
	 * Is builder active.
	 *
	 * @access public
	 * @var boolean $is_builder.
	 */
	public $is_builder = false;

	/**
	 * Preferences object
	 *
	 * @access public
	 * @var Fusion_Preferences
	 */
	public $preferences = null;

	/**
	 * Save data
	 *
	 * @access public
	 * @var save_data
	 */
	public $save_data = [];

	/**
	 * Is this ajax from app?
	 *
	 * @access protected
	 * @since 2.0
	 * @var bool
	 */
	protected $is_ajax = false;

	/**
	 * An array of our google fonts.
	 *
	 * @static
	 * @access public
	 * @var null|object
	 */
	public static $google_fonts = null;

	/**
	 * Backup object for global $wp_query.
	 *
	 * @access protected
	 * @var null|object
	 */
	protected $backup_wp_query = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 2.0
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new Fusion_App();
		}
		return self::$instance;
	}

	/**
	 * Initializes the plugin by setting localization, hooks, filters,
	 * and administrative functions.
	 *
	 * @access private
	 * @since 2.0
	 */
	private function __construct() {

		$can_edit = ( current_user_can( 'edit_theme_options' ) || current_user_can( 'publish_pages' ) || current_user_can( 'publish_posts' ) );
		if ( ! apply_filters( 'fusion_load_live_editor', $can_edit ) ) {
			return;
		}

		// Save post content.
		add_action( 'wp_ajax_fusion_app_save_post_content', [ $this, 'fusion_app_save_post_content' ] );

		if ( ! $this->has_capability() ) {
			return;
		}

		$this->set_builder_status();
		$this->set_preview_status();
		$this->set_ajax_status();

		$this->init();
	}

	/**
	 * Initializes the plugin by setting localization, hooks, filters,
	 * and administrative functions.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function init() {

		add_filter( 'wp_refresh_nonces', [ $this, 'fusion_refresh_nonces' ], 10, 3 );

		$this->set_data();

		if ( $this->has_data ) {
			add_action( 'wp', [ $this, 'filter_data' ] );
		}

		// If preview frame.
		if ( $this->is_preview ) {
			add_action( 'wp_footer', [ $this, 'preview_data' ] );
			show_admin_bar( false ); // phpcs:ignore WPThemeReview.PluginTerritory
			add_action( 'wp_enqueue_scripts', [ $this, 'preview_live_scripts' ], 999 );

			add_filter( 'body_class', [ $this, 'preview_body_class' ], 999 );
			add_filter( 'avada_the_html_class', [ $this, 'preview_html_class' ], 999 );
			add_action( 'wp_head', [ $this, 'access_control_allow_origin' ] );
		}

		if ( $this->is_builder ) {
			show_admin_bar( false ); // phpcs:ignore WPThemeReview.PluginTerritory
			add_action(
				'wp_enqueue_scripts',
				function() {
					global $wp_scripts, $wp_styles;
					$wp_scripts->queue = [];
					$wp_styles->queue  = [];
				},
				100
			);

			add_action( 'wp_enqueue_scripts', [ $this, 'live_scripts' ], 997 );
			add_action( 'wp_footer', [ $this, 'load_templates' ] );

			add_action( 'wp_print_footer_scripts', [ $this, 'fusion_authorization' ], 10 );

			add_filter( 'body_class', [ $this, 'body_class' ], 997 );

			add_filter( 'template_include', [ $this, 'template_include' ], 999 );
		}

		if ( $this->is_preview || $this->is_builder ) {
			add_filter( 'wp_headers', [ $this, 'cache_headers' ], 999 );
		}

		$this->set_preference();

		// Action for replacing partial contents.
		add_action( 'wp_ajax_fusion_app_partial_refresh', [ $this, 'fusion_app_partial_refresh' ] );

		add_action( 'wp_ajax_fusion_get_webfonts_ajax', [ $this, 'get_googlefonts_ajax' ] );

		// Action to add new term from live editor.
		add_action( 'wp_ajax_fusion_multiselect_addnew', [ $this, 'fusion_multiselect_addnew' ] );

		// Front end page edit trigger.
		add_action( 'admin_bar_menu', [ $this, 'builder_trigger' ], 999 );
	}

	/**
	 * Check for POST data of refresh.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function set_data() {

		$data = [];

		$this->has_data = isset( $_POST ) && isset( $_POST['action'] ) && isset( $_POST['fusion_load_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fusion_load_nonce'] ) ), 'fusion_load_nonce' );

		if ( $this->has_data ) {
			$this->data['action'] = sanitize_text_field( wp_unslash( $_POST['action'] ) );

			if ( isset( $_POST['post_id'] ) ) {
				$this->data['post_id'] = sanitize_text_field( wp_unslash( $_POST['post_id'] ) );
			}

			if ( isset( $_POST['partials'] ) ) {
				$this->data['partials'] = wp_unslash( $_POST['partials'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			if ( isset( $_POST['post_details'] ) ) {
				$this->data['post_details'] = fusion_string_to_array( wp_unslash( $_POST['post_details'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			if ( isset( $_POST['post_content'] ) ) {
				$post_content = $_POST['post_content']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				if ( 'fusion_app_preview_only' === $this->data['action'] ) {
					$post_content = urldecode( utf8_encode( $post_content ) );
				}
				$this->data['post_content'] = wp_unslash( apply_filters( 'content_save_pre', $post_content ) );
			}

			if ( isset( $_POST['query'] ) ) {
				$this->data['query'] = wp_unslash( $_POST['query'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			if ( isset( $_POST['option_name'] ) ) {
				$this->data['option_name'] = sanitize_text_field( wp_unslash( $_POST['option_name'] ) );
			}

			if ( isset( $_POST['fusion_options'] ) ) {
				$this->data['fusion_options'] = fusion_string_to_array( wp_unslash( $_POST['fusion_options'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			if ( isset( $_POST['meta_values'] ) ) {
				$this->data['meta_values'] = fusion_string_to_array( wp_unslash( $_POST['meta_values'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}
		}
	}

	/**
	 * Get any data set to app.
	 *
	 * @access public
	 * @since 2.0
	 * @param string $id The data we want to get (post_id, partials, post_content etc).
	 * @return mixed
	 */
	public function get_data( $id = '' ) {
		if ( '' === $id ) {
			return $this->has_data ? $this->data : false;
		}
		if ( isset( $this->data[ $id ] ) ) {
			return $this->data[ $id ];
		}
		return false;
	}

	/**
	 * Checks if request is for a full refresh of same page.
	 *
	 * @access public
	 * @since 2.0
	 * @return boolean
	 */
	public function is_full_refresh() {
		return $this->has_data && 'fusion_app_full_refresh' === $this->data['action'];
	}

	/**
	 * Filter page data with POST data.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function filter_data() {
		if ( ! $this->has_data ) {
			return;
		}

		delete_transient( 'avada_googlefonts_contents' );
		delete_site_transient( 'avada_googlefonts_contents' );

		$this->filter_core_settings();

		do_action( 'fusion_filter_data' );
	}

	/**
	 * More no cache headers to pass on.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $headers Existing headers.
	 * @return array
	 */
	public function cache_headers( $headers ) {
		if ( isset( $_SERVER['REQUEST_URI'] ) && ( false !== strpos( $_SERVER['REQUEST_URI'], 'fb-edit' ) || false !== strpos( $_SERVER['REQUEST_URI'], 'builder=true' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$headers['Pragma']        = 'no-cache';
			$headers['Cache-Control'] = 'no-cache, must-revalidate';
		}
		return $headers;
	}

	/**
	 * Echoes the data for Front-End builder preview.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function preview_data() {
		global $post, $fusion_library, $template, $wp_query;

		$_post               = $post;
		$permalink           = fusion_app_get_permalink();
		$permalink           = remove_query_arg( [ 'builder', 'builder_id', 'option' ], $permalink );
		$permalink           = add_query_arg( 'fb-edit', true, $permalink );
		$page_id             = Fusion::get_instance()->get_page_id();
		$post_content        = '';
		$post_type           = get_post_type( $_post );
		$post_type_obj       = get_post_type_object( $post_type );
		$is_fusion_element   = 'fusion_element' === $post_type ? true : false;
		$fusion_element_type = false;
		$backend_link        = '';

		if ( $page_id && false === strpos( $page_id, '-archive' ) ) {
			$_post        = get_post( $page_id );
			$post_content = is_object( $_post ) ? wpautop( trim( apply_filters( 'content_edit_pre', $_post->post_content, $page_id ) ) ) : '';

			if ( function_exists( 'fusion_builder_fix_shortcodes' ) ) {
				$post_content = fusion_builder_fix_shortcodes( $post_content );
			}

			// Add additional next page element, for the last page live preview.
			if ( 0 < substr_count( $post_content, '[fusion_builder_next_page]' ) ) {
				$post_content .= '[fusion_builder_next_page last="true"]';
			}
		}


		if ( is_category() || is_tax() ) {
			$category     = get_queried_object();
			$backend_link = get_edit_term_link( $category->term_id, $category->taxonomy );
		}

		if ( $is_fusion_element ) {
			$terms = get_the_terms( $post->ID, 'element_category' );

			if ( $terms ) {
				$fusion_element_type = $terms[0]->name;
			}
		}

		$data = [
			'query'                => $wp_query->query,
			'currentPage'          => fusion_app_get_current_page(),
			'is_home'              => is_home(),
			'is_front_page'        => is_front_page(),
			'is_single'            => is_single( $page_id ),
			'is_sticky'            => is_sticky( $page_id ),
			'is_post_type_archive' => is_post_type_archive(),
			'is_posts_archive'     => is_post_type_archive( 'post' ),
			'comments_open'        => comments_open(),
			'is_page'              => is_page( $page_id ),
			'template'             => str_replace( [ get_template_directory() . '/', get_stylesheet_directory() . '/' ], '', $template ),
			'is_archive'           => is_archive() && ( ! function_exists( 'is_shop' ) || function_exists( 'is_shop' ) && ! is_shop() ),
			'is_category'          => is_category(),
			'is_tag'               => is_tag(),
			'is_tax'               => is_tax(),
			'is_author'            => is_author(),
			'is_date'              => is_date(),
			'is_year'              => is_year(),
			'is_month'             => is_month(),
			'is_day'               => is_day(),
			'is_time'              => is_time(),
			'is_new_day'           => is_new_day(),
			'is_search'            => is_search(),
			'is_404'               => is_404(),
			'is_paged'             => is_paged(),
			'is_attachment'        => is_attachment(),
			'is_singular'          => is_singular(),
			'has_excerpt'          => has_excerpt(),
			'is_child_theme'       => is_child_theme(),
			'is_singular_post'     => is_singular( 'post' ),
			'is_woocommerce'       => function_exists( 'is_woocommerce' ) ? is_woocommerce() : false,
			'is_shop'              => function_exists( 'is_shop' ) ? is_shop() : false,
			'is_product_category'  => function_exists( 'is_product_category' ) ? is_product_category() : false,
			'is_product_tag'       => function_exists( 'is_product_tag' ) ? is_product_tag() : false,
			'is_product'           => function_exists( 'is_product' ) ? is_product() : false,
			'is_cart'              => function_exists( 'is_cart' ) ? is_cart() : false,
			'is_checkout'          => function_exists( 'is_checkout' ) ? is_checkout() : false,
			'is_account_page'      => function_exists( 'is_account_page' ) ? is_account_page() : false,
			'is_woo_archive'       => ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() && is_tax() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ),
			'is_portfolio_single'  => is_singular( 'avada_portfolio' ),
			'is_portfolio_archive' => is_post_type_archive( 'avada_portfolio' ) || is_tax( [ 'portfolio_category', 'portfolio_skills', 'portfolio_tags' ] ),
			'is_singular_ec'       => ( is_singular( 'tribe_events' ) || is_singular( 'tribe_organizer' ) || is_singular( 'tribe_venue' ) ),
			'is_bbpress'           => function_exists( 'is_bbpress' ) && is_bbpress(),
			'is_buddypress'        => function_exists( 'is_buddypress' ) && is_buddypress(),
			'backendLink'          => $backend_link,
			'is_fusion_element'    => $is_fusion_element,
			'fusion_element_type'  => $fusion_element_type,
			'postDetails'          => [
				'post_id'        => $page_id,
				'post_permalink' => $permalink,
			],
		];

		if ( $page_id && false === strpos( $page_id, '-archive' ) ) {

			$data['postDetails'] = [
				'post_id'        => $page_id,
				'post_permalink' => $permalink,
				'post_title'     => get_the_title( $page_id ),
				'post_content'   => $post_content,
				'post_name'      => $_post->post_name,
				'post_type'      => $post_type,
				'post_type_name' => is_object( $post_type_obj ) ? $post_type_obj->labels->singular_name : esc_html__( 'Page', 'fusion-builder' ),
				'post_status'    => get_post_status( $page_id ),
				'post_password'  => is_object( $_post ) ? $_post->post_password : '',
				'post_date'      => is_object( $_post ) ? $_post->post_date : '',
				'menu_order'     => ( isset( $_post->menu_order ) ) ? $_post->menu_order : '0',
			];

			if ( post_type_supports( $post_type, 'post-formats' ) && current_theme_supports( 'post-formats' ) ) {
				$data['postDetails']['post_format'] = get_post_format( $page_id ) ? $data['postDetails']['post_format'] : 'standard';
			}

			// Taxonomies.
			if ( in_array( $post_type, [ 'post', 'avada_portfolio' ], true ) ) {
				$post_taxonomies = get_object_taxonomies( $post_type, 'objects' );
				if ( 0 < count( $post_taxonomies ) ) {
					foreach ( $post_taxonomies as $taxonomy ) {
						if ( 'post_format' !== $taxonomy->name ) {

							// current terms.
							$post_terms    = get_the_terms( $page_id, $taxonomy->name );
							$current_value = [];

							if ( is_object( $post_terms ) && ! is_wp_error( $post_terms ) ) {
								$post_terms = [ $post_terms ];
							}
							$post_terms = ( is_array( $post_terms ) ) ? $post_terms : [];

							if ( 0 < count( $post_terms ) && ! empty( $post_terms ) ) {
								foreach ( $post_terms as $post_term ) {
									array_push( $current_value, $post_term->term_id );
								}
							}

							$data['postDetails'][ $taxonomy->name ] = implode( ',', $current_value );
						}
					}
				}
			}
		} elseif ( $page_id && false !== strpos( $page_id, '-archive' ) ) {

			$_term = get_term( (int) str_replace( 'archive-', '', $page_id ) );
			if ( ! is_wp_error( $_term ) ) {
				$data['postDetails'] = [
					'post_id'        => $page_id,
					'post_permalink' => $permalink,
					'name'           => $_term->name,
					'slug'           => $_term->slug,
					'parent'         => (int) $_term->parent,
					'description'    => $_term->description,
				];
			}
		}

		$data = $this->add_multilingual_data( $data );

		$data = apply_filters( 'fusion_app_preview_data', $data, $page_id, $post_type );

		// Load font for inline editor.
		fusion_the_admin_font_async();
		?>

		<script type="text/javascript">
			function initFusionAppInitialData() {
				if ( 'undefined' === typeof parent.window.FusionApp ) {
					setTimeout( function() {
						initFusionAppInitialData();
					}, 60 );
					return;
				}
				parent.window.FusionApp.initialData     = <?php echo wp_json_encode( $data, JSON_FORCE_OBJECT ); ?>;
				parent.window.FusionApp.preferences     = <?php echo wp_json_encode( $this->preferences->params(), JSON_FORCE_OBJECT ); ?>;
				parent.window.FusionApp.preferencesData = <?php echo wp_json_encode( $this->preferences->get_preferences(), JSON_FORCE_OBJECT ); ?>;
				parent.window.FusionApp.setup();
				window.addEventListener( 'load', function() {
					parent.window.FusionApp.iframeLoaded();
				} );
			}
			initFusionAppInitialData();
		</script>
		<?php
	}

	/**
	 * Add multilingual data if necessary.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $data The data for the fusion app.
	 * @return array
	 */
	public function add_multilingual_data( $data ) {
		$multilingual        = Fusion_Library()->multilingual;
		$available_languages = $multilingual->get_available_languages();
		$language            = false;

		if ( ! empty( $available_languages ) ) {
			$language                 = $multilingual->get_active_language();
			$option_name              = Avada::get_option_name();
			$data['optionName']       = $option_name;
			$data['languageTo']       = get_option( $option_name );
			$data['languageSwitcher'] = $multilingual->get_language_switcher_data();

			// Retrieve defaults for this language.
			do_action( 'fusion_builder_before_init' );
			$fusion_builder_elements = fusion_builder_filter_available_elements();
			if ( ! empty( $fusion_builder_elements ) ) {
				$fusion_builder_elements  = apply_filters( 'fusion_builder_all_elements', $fusion_builder_elements );
				$data['languageDefaults'] = $fusion_builder_elements;
			}
		}
		$data['language'] = $language;

		return $data;
	}

	/**
	 * Checks if user should see builder.
	 *
	 * @access public
	 * @since 2.0
	 * @return bool
	 */
	public function has_capability() {
		return is_user_logged_in();
	}

	/**
	 * Include a template file.
	 *
	 * @access public
	 * @since 2.0
	 * @param string $template The template file we want to include.
	 * @return string
	 */
	public function template_include( $template ) {
		global $wp_query;

		if ( $wp_query->is_main_query() && $this->get_builder_status() ) {
			return FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/front-customize.php';
		}
		return $template;
	}

	/**
	 * Link to admin bar for builder.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $admin_bar admin bar.
	 * @return void
	 */
	public function builder_trigger( $admin_bar ) {
		$customize_url = fusion_app_get_permalink( $admin_bar );

		if ( ! $customize_url || '' === $customize_url ) {
			return;
		}

		$customize_url = add_query_arg( 'fb-edit', true, $customize_url );

		$admin_bar->add_node(
			[
				'id'    => 'fb-edit',
				'title' => esc_html__( 'Fusion Builder Live', 'fusion-builder' ),
				'href'  => $customize_url,
			]
		);

		if ( class_exists( 'Fusion_Template_Builder' ) && function_exists( 'get_post_type' ) && 'fusion_tb_section' !== get_post_type() ) {
			$templates     = Fusion_Template_Builder()->get_template_terms();
			$submenu_items = [];

			foreach ( $templates as $key => $template_arr ) {
				$template = Fusion_Template_Builder::get_instance()->get_override( $key );
				if ( $template ) {
					$submenu_items[] = [
						'key'         => $key,
						'label'       => $template_arr['label'],
						'template_id' => $template->ID,
					];

				}
			}

			if ( $submenu_items ) {

				$admin_bar->add_node(
					[
						'parent' => 'fb-edit',
						'id'     => 'fb-edit-page',
						'title'  => esc_html__( 'Edit Page', 'fusion-builder' ),
						'href'   => $customize_url,
					]
				);

				foreach ( $submenu_items as $item ) {

					$admin_bar->add_node(
						[
							'parent' => 'fb-edit',
							'id'     => 'fb-edit-' . $item['key'],
							/* translators: Template name, for example Content */
							'title'  => sprintf( __( 'Edit %s', 'fusion-builder' ), $item['label'] ),
							'href'   => add_query_arg( 'fb-edit', true, get_permalink( $item['template_id'] ) ),
						]
					);

				}
			}
		}
	}

	/**
	 * Add preview frame body class.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $classes classes being used.
	 * @return string
	 */
	public function preview_body_class( $classes ) {

		$classes[] = 'fusion-builder-live dont-animate';
		if ( is_preview_only() ) {
			$classes[] = 'fusion-builder-live-preview-only';
		} else {
			$classes[] = 'fusion-builder-live-preview';
		}

		$preferences = $this->preferences;

		if ( ! isset( $preferences::$preferences['droppables_visible'] ) || 'off' === $preferences::$preferences['droppables_visible'] ) {
			$classes[] = 'fusion-hide-droppables';
		}

		if ( isset( $preferences::$preferences['tooltips'] ) && 'off' === $preferences::$preferences['tooltips'] ) {
			$classes[] = 'fusion-hide-all-tooltips';
		}

		if ( isset( $preferences::$preferences['element_filters'] ) && 'off' === $preferences::$preferences['element_filters'] ) {
			$classes[] = 'fusion-disable-element-filters';
		}

		return $classes;
	}

	/**
	 * Add preview frame html class.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $classes classes being used.
	 * @return string
	 */
	public function preview_html_class( $classes ) {
		$preferences = $this->preferences;

		if ( isset( $preferences::$preferences['transparent_header'] ) && 'off' === $preferences::$preferences['transparent_header'] && in_array( 'avada-header-color-not-opaque', $classes, true ) ) {
			unset( $classes[ array_search( 'avada-header-color-not-opaque', $classes, true ) ] );
		}

		return $classes;
	}

	/**
	 * Add editor body class.
	 *
	 * @access public
	 * @since 2.0
	 * @param array $classes classes being used.
	 * @return string
	 */
	public function body_class( $classes ) {

		$preferences = $this->preferences;
		$classes     = [];
		$classes[]   = 'fusion-builder-live wp-core-ui fb-customizer js';
		if ( wp_is_mobile() ) {
			$classes[] = 'mobile';
		}

		if ( isset( $preferences::$preferences['sidebar_position'] ) && 'right' === $preferences::$preferences['sidebar_position'] ) {
			$classes[] = 'sidebar-right';
		}

		if ( isset( $preferences::$preferences['tooltips'] ) && 'off' === $preferences::$preferences['tooltips'] ) {
			$classes[] = 'fusion-hide-all-tooltips';
		}

		if ( isset( $preferences::$preferences['element_filters'] ) && 'off' === $preferences::$preferences['element_filters'] ) {
			$classes[] = 'fusion-disable-element-filters';
		}

		if ( is_rtl() ) {
			$classes[] = 'rtl';
		}

		$classes[] = 'locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

		return $classes;
	}

	/**
	 * Partial refresh.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function fusion_app_partial_refresh() {
		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

		$this->set_data();

		$this->emulate_wp_query();

		$return_data = [];

		// Emulate the page id.
		$post_id = $this->get_data( 'post_id' );
		if ( $post_id ) {
			add_filter(
				'fusion-page-id',
				function() use ( $post_id ) {
					return absint( $post_id );
				}
			);
		}

		$this->filter_core_settings();

		do_action( 'fusion_filter_data' );

		$partials = $this->get_data( 'partials' );
		if ( is_array( $partials ) ) {
			foreach ( $partials as $key => $partial ) {

				if ( is_callable( $partial['render_callback'] ) ) {
					ob_start();
					call_user_func( $partial['render_callback'] );
					$data                = ob_get_clean();
					$return_data[ $key ] = $data;
				}
			}
		}
		echo wp_json_encode( $return_data );
		wp_die();
	}

	/**
	 * Emulate changed core page or archive settings.
	 *
	 * @since 6.0.0
	 * @return void
	 */
	public function filter_core_settings() {
		global $post;

		$post_details = $this->get_data( 'post_details' );

		if ( is_array( $post_details ) ) {

			if ( false === strpos( $post_details['post_id'], '-archive' ) ) {

				// Update global post.
				foreach ( $post_details as $key => $value ) {
					if ( property_exists( $post, $key ) ) {
						$post->{ $key } = $value;
					}
				}

				// Filter post title.
				if ( isset( $post_details['post_title'] ) ) {
					add_filter(
						'the_title',
						function( $title, $id ) use ( $post_details ) {
							if ( $id === (int) $post_details['post_id'] ) {
								return $post_details['post_title'];
							}
							return $title;
						},
						10,
						2
					);
				}

				// Filter post format.
				if ( in_array( $post_details['post_type'], [ 'post', 'avada_portfolio' ], true ) ) {
					add_filter(
						'get_the_terms',
						function( $terms, $id, $taxonomy ) use ( $post_details ) {
							if ( $id === (int) $post_details['post_id'] && isset( $post_details[ $taxonomy ] ) ) {
								if ( 'post_format' === $taxonomy && isset( $terms[0] ) ) {
									$terms[0]->slug = 'post-format-' . $post_details['post_format'];
								} elseif ( ( is_array( $post_details[ $taxonomy ] ) && 0 < count( $post_details[ $taxonomy ] ) ) || ( is_string( $post_details[ $taxonomy ] ) && 0 < strlen( $post_details[ $taxonomy ] ) ) ) {
									$term_data = is_array( $post_details[ $taxonomy ] ) ? $post_details[ $taxonomy ] : explode( ',', $post_details[ $taxonomy ] );
									$term_data = array_map( 'intval', $term_data );
									$terms     = get_terms(
										[
											'taxonomy'   => $taxonomy,
											'hide_empty' => false,
											'include'    => $term_data,
										]
									);
								}

								return $terms;
							}

							return $terms;
						},
						10,
						3
					);
				}
			} else {

				// Filter the term values.
				add_filter(
					'get_term',
					function( $_term, $taxonomy ) use ( $post_details ) {
						if ( (int) str_replace( '-archive', '', $post_details['post_id'] ) === $_term->term_id ) {
							foreach ( $post_details as $key => $value ) {
								if ( property_exists( $_term, $key ) ) {
									$_term->{ $key } = $value;
								}
							}
						}
						return $_term;
					},
					10,
					2
				);

				// Filter for single term title.
				$type = false;
				if ( is_category() ) {
					$type = 'cat';
				} elseif ( is_tag() ) {
					$type = 'tag';
				} elseif ( is_tax() ) {
					$type = 'term';
				}

				if ( $type ) {
					add_filter(
						'single_' . $type . '_title',
						function( $title ) use ( $post_details ) {
							return $post_details['name'];
						}
					);
				}
			}
		}
	}

	/**
	 * Sets builder status.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function set_builder_status() {
		if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'fb-edit' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$this->is_builder = true;
		}
	}

	/**
	 * Gets builder status.
	 *
	 * @access public
	 * @since 2.0
	 * @return bool
	 */
	public function get_builder_status() {
		return $this->is_builder;
	}

	/**
	 * Sets preview status.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function set_preview_status() {
		$request_uri = ( isset( $_SERVER['REQUEST_URI'] ) ) ? $_SERVER['REQUEST_URI'] : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( $request_uri && false !== strpos( $request_uri, 'builder=true' ) || ( false !== strpos( $request_uri, 'builder_id' ) && false !== strpos( $request_uri, 'fbpreview=true' ) ) ) {
			$this->is_preview = true;
		}
	}

	/**
	 * Gets preview only status.
	 *
	 * @access public
	 * @since 2.0
	 * @return bool
	 */
	public function get_preview_only_status() {
		return $this->has_data && 'fusion_app_preview_only' === $this->data['action'];
	}

	/**
	 * Sets ajax status.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function set_ajax_status() {
		if ( function_exists( 'wp_doing_ajax' ) ) {
			$this->is_ajax = wp_doing_ajax();
			return;
		}
		$this->is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Gets preview status.
	 *
	 * @access public
	 * @since 2.0
	 * @return bool
	 */
	public function get_preview_status() {
		return $this->is_preview;
	}

	/**
	 * Gets ajax status.
	 *
	 * @access public
	 * @since 2.0
	 * @return bool
	 */
	public function get_ajax_status() {
		return $this->is_ajax;
	}

	/**
	 * Load the template files.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function load_templates() {
		include FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/front-end-toolbar.php';
		include FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/repeater-fields.php';
		include FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/modal-dialog-more.php';
	}

	/**
	 * Enqueue preview frame scripts.
	 *
	 * @access public
	 * @since 2.0
	 * @param mixed $hook The hook.
	 * @return void
	 */
	public function preview_live_scripts( $hook ) {
		global $fusion_library_latest_version;

		wp_enqueue_style( 'fusion-app-preview-frame-css', FUSION_LIBRARY_URL . '/inc/fusion-app/css/fusion-preview-frame.css', [], $fusion_library_latest_version );

		$min = '';
		if ( ( ! defined( 'FUSION_LIBRARY_DEV_MODE' ) || ! FUSION_LIBRARY_DEV_MODE ) ) {
			$min = '.min';
		}
		wp_enqueue_style( 'fusion-font-icomoon', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/fonts/icomoon' . $min . '.css', false, $fusion_library_latest_version, 'all' );

		// For inline editor.
		wp_enqueue_script( 'jquery-touch-punch' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), [], $fusion_library_latest_version, true );
		wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), [], $fusion_library_latest_version, true );

		$colorpicker_l10n = [
			'clear'         => __( 'Clear', 'fusion-builder' ),
			'defaultString' => __( 'Default', 'fusion-builder' ),
			'pick'          => __( 'Select Color', 'fusion-builder' ),
			'current'       => __( 'Current Color', 'fusion-builder' ),
		];

		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

		// ColorPicker Alpha Channel.
		wp_enqueue_script( 'wp-color-picker-alpha', FUSION_LIBRARY_URL . '/inc/redux/custom-fields/color_alpha/wp-color-picker-alpha.js', [], $fusion_library_latest_version, true );

		// Media.
		wp_enqueue_media();
		wp_enqueue_style( 'forms' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @access public
	 * @since 2.0
	 * @param mixed $hook The hook.
	 * @return void
	 */
	public function live_scripts( $hook ) {
		global $fusion_library_latest_version, $fusion_settings;

		// Compatibility with WP 5.2: These don't get loaded by default so if missing we need to include the post.php file.
		if ( ! function_exists( 'get_available_post_mime_types' ) || ! function_exists( 'get_available_post_mime_types' ) ) {
			require_once ABSPATH . '/wp-admin/includes/post.php';
		}

		$min = '';
		if ( ( ! defined( 'FUSION_LIBRARY_DEV_MODE' ) || ! FUSION_LIBRARY_DEV_MODE ) ) {
			$min = '.min';
		}

		// Authorization styling for logging back in.
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_style( 'wp-auth-check' );
		wp_enqueue_script( 'wp-auth-check' );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'underscore' );
		wp_enqueue_script( 'backbone' );

		wp_enqueue_style( 'editor-buttons' );

		// Main styling.
		wp_enqueue_style( 'fusion-app-builder-frame-css', FUSION_LIBRARY_URL . '/inc/fusion-app/css/fusion-builder-frame.min.css', [], $fusion_library_latest_version );

		// Underscore util.
		wp_enqueue_script( 'wp-util' );

		// jQuery UI.
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_style( 'jquery-ui-css', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/css/jquery-ui/jquery-ui.min.css', false, '1.12.1' );

		// Font Awesome Search.
		wp_enqueue_script( 'fuse-script', FUSION_LIBRARY_URL . '/assets/min/js/library/fuse.js', [], $fusion_library_latest_version, false );
		wp_enqueue_script( 'fontawesome-search-script', FUSION_LIBRARY_URL . '/assets/fonts/fontawesome/js/icons-search-free.js', [], $fusion_library_latest_version, false );

		// FontAwesome.
		wp_enqueue_style( 'fontawesome', Fusion_Font_Awesome::get_backend_css_url(), [], $fusion_library_latest_version );

		if ( '1' === $fusion_settings->get( 'fontawesome_v4_compatibility' ) ) {
			wp_enqueue_script( 'fontawesome-shim-script', FUSION_LIBRARY_URL . '/assets/fonts/fontawesome/js/fa-v4-shims.js', [], $fusion_library_latest_version, false );

			wp_enqueue_style( 'fontawesome-shims', Fusion_Font_Awesome::get_backend_shims_css_url(), [], $fusion_library_latest_version );
		}

		// Fonts.
		wp_enqueue_style( 'fusion-font-icomoon', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/fonts/icomoon' . $min . '.css', false, $fusion_library_latest_version, 'all' );

		// Media.
		wp_enqueue_media();
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'forms' );

		wp_enqueue_script( 'jquery-touch-punch' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), [], $fusion_library_latest_version, true );
		wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), [], $fusion_library_latest_version, true );

		$colorpicker_l10n = [
			'clear'         => __( 'Clear', 'fusion-builder' ),
			'defaultString' => __( 'Default', 'fusion-builder' ),
			'pick'          => __( 'Select Color', 'fusion-builder' ),
			'current'       => __( 'Current Color', 'fusion-builder' ),
		];

		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

		// ColorPicker Alpha Channel.
		wp_enqueue_script( 'wp-color-picker-alpha', FUSION_LIBRARY_URL . '/inc/redux/custom-fields/color_alpha/wp-color-picker-alpha.js', [], $fusion_library_latest_version, true );

		// Code Mirror.
		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			foreach ( [ 'text/html', 'text/css', 'application/javascript' ] as $mime_type ) {
				wp_enqueue_code_editor(
					[
						'type' => $mime_type,
					]
				);
			}
		} else {
			wp_enqueue_script( 'fusion-builder-codemirror-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/codemirror.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion-builder-codemirror-js-mode', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/modes/javascript/javascript.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion-builder-codemirror-css-mode', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/modes/css/css.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion-builder-codemirror-xml-mode', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/modes/xml/xml.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion-builder-codemirror-html-mode', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/modes/htmlmixed/htmlmixed.js', [], $fusion_library_latest_version, true );
		}
		wp_enqueue_style( 'fusion-builder-codemirror-css', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/codemirror.css', [], $fusion_library_latest_version, 'all' );

		// Bootstrap date and time picker.
		wp_enqueue_script( 'bootstrap-datetimepicker', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/datetimepicker/bootstrap-datetimepicker.min.js', [], $fusion_library_latest_version, false );
		wp_enqueue_style( 'bootstrap-datetimepicker', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/css/bootstrap-datetimepicker.css', [], '5.0.0', 'all' );

		// WP Editor.
		wp_enqueue_script( 'fusion-builder-wp-editor-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/wpeditor/wp-editor.js', [], $fusion_library_latest_version, true );

		// The noUi Slider.
		wp_enqueue_style( 'avadaredux-nouislider-css', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/css/nouislider.css', [], '5.0.0', 'all' );

		wp_enqueue_script( 'avadaredux-nouislider-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/nouislider/nouislider.min.js', [], '8.5.1', true );
		wp_enqueue_script( 'wnumb-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/wNumb.js', [ 'jquery' ], '1.0.2', true );

		// Live editor.
		wp_enqueue_script( 'medium-editor', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/medium-editor.min.js', [], $fusion_library_latest_version, false );
		wp_enqueue_script( 'rangy-core', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/rangy-core.js', [], $fusion_library_latest_version, false );
		wp_enqueue_script( 'rangy-classapplier', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/rangy-classapplier.js', [], $fusion_library_latest_version, false );
		wp_enqueue_script( 'fuse', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/fuse.min.js', [], $fusion_library_latest_version, false );
		wp_enqueue_script( 'webfont-loader', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/webfontloader.js', [], '1.6.28', false );

		// If we're not debugging, load the combined script.
		if ( ( ! defined( 'FUSION_LIBRARY_DEV_MODE' ) || ! FUSION_LIBRARY_DEV_MODE ) && ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ) {
			wp_enqueue_script( 'fusion_library_frontend_combined', FUSION_LIBRARY_URL . '/inc/fusion-app/fusion-frontend-combined.min.js', [ 'jquery', 'underscore', 'backbone' ], $fusion_library_latest_version, true );
			$localize_handle = 'fusion_library_frontend_combined';
		} else {
			wp_enqueue_script( 'cssua', FUSION_LIBRARY_URL . '/assets/min/js/library/cssua.js', [], $fusion_library_latest_version, false );
			wp_enqueue_script( 'fusion_app_toolbar', FUSION_LIBRARY_URL . '/inc/fusion-app/views/view-toolbar.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_dialog', FUSION_LIBRARY_URL . '/inc/fusion-app/model-dialog.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_validation', FUSION_LIBRARY_URL . '/inc/fusion-app/model-validation.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_callback_functions', FUSION_LIBRARY_URL . '/inc/fusion-app/model-callback-functions.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_dependencies', FUSION_LIBRARY_URL . '/inc/fusion-app/model-dependencies.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_assets', FUSION_LIBRARY_URL . '/inc/fusion-app/model-assets.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_model_view_manager', FUSION_LIBRARY_URL . '/inc/fusion-app/model-view-manager.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_active_states', FUSION_LIBRARY_URL . '/inc/fusion-app/model-active-states.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_hotkeys', FUSION_LIBRARY_URL . '/inc/fusion-app/model-hotkeys.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_util_js', FUSION_LIBRARY_URL . '/inc/fusion-app/util.js', [], $fusion_library_latest_version, true );

			wp_enqueue_script( 'fusion_app_inline_editor', FUSION_LIBRARY_URL . '/inc/fusion-app/model-inline-editor.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_inline_editor_manager', FUSION_LIBRARY_URL . '/inc/fusion-app/model-inline-editor-manager.js', [], $fusion_library_latest_version, true );

			// Options.
			wp_enqueue_script( 'fusion_app_option_linkselector', FUSION_LIBRARY_URL . '/inc/fusion-app/options/link-selector.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_linkselector_object', FUSION_LIBRARY_URL . '/inc/fusion-app/options/link-selector-object.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_media_upload', FUSION_LIBRARY_URL . '/inc/fusion-app/options/media-upload.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_placeholder', FUSION_LIBRARY_URL . '/inc/fusion-app/options/textfield-placeholder.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_date_picker', FUSION_LIBRARY_URL . '/inc/fusion-app/options/date-picker.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_color_picker', FUSION_LIBRARY_URL . '/inc/fusion-app/options/color-picker.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_icon_picker', FUSION_LIBRARY_URL . '/inc/fusion-app/options/icon-picker.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_code_block', FUSION_LIBRARY_URL . '/inc/fusion-app/options/code-block.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_dimension', FUSION_LIBRARY_URL . '/inc/fusion-app/options/dimension.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_select', FUSION_LIBRARY_URL . '/inc/fusion-app/options/select.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_ajax-select', FUSION_LIBRARY_URL . '/inc/fusion-app/options/ajax-select.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_multi_select', FUSION_LIBRARY_URL . '/inc/fusion-app/options/multi-select.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_editor', FUSION_LIBRARY_URL . '/inc/fusion-app/options/editor.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_checkbox_set', FUSION_LIBRARY_URL . '/inc/fusion-app/options/checkbox-set.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_radio_set', FUSION_LIBRARY_URL . '/inc/fusion-app/options/radio-set.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_raw', FUSION_LIBRARY_URL . '/inc/fusion-app/options/raw.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_range', FUSION_LIBRARY_URL . '/inc/fusion-app/options/range.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_repeater', FUSION_LIBRARY_URL . '/inc/fusion-app/options/repeater.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_switch', FUSION_LIBRARY_URL . '/inc/fusion-app/options/switch.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_typography', FUSION_LIBRARY_URL . '/inc/fusion-app/options/typography.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_font_family', FUSION_LIBRARY_URL . '/inc/fusion-app/options/font-family.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_import', FUSION_LIBRARY_URL . '/inc/fusion-app/options/import.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_export', FUSION_LIBRARY_URL . '/inc/fusion-app/options/export.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_sortable', FUSION_LIBRARY_URL . '/inc/fusion-app/options/sortable.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_sortable_text', FUSION_LIBRARY_URL . '/inc/fusion-app/options/sortable-text.js', [], $fusion_library_latest_version, true );
			wp_enqueue_script( 'fusion_app_option_color_palette', FUSION_LIBRARY_URL . '/inc/fusion-app/options/color-palette.js', [], $fusion_library_latest_version, true );

			wp_enqueue_script( 'fusion-extra-panel-functions', FUSION_LIBRARY_URL . '/inc/fusion-app/callbacks.js', [], $fusion_library_latest_version, true );

			wp_enqueue_script( 'fusion_app_modal_dialog_more', FUSION_LIBRARY_URL . '/inc/fusion-app/views/view-dialog-more-options.js', [], $fusion_library_latest_version, true );

			// Fusion App.
			wp_enqueue_script( 'fusion_app', FUSION_LIBRARY_URL . '/inc/fusion-app/fusion-app.js', [], $fusion_library_latest_version, true );
			$localize_handle = 'fusion_app';
		}

		$fusion_load_nonce = false;
		if ( current_user_can( 'edit_theme_options' ) || current_user_can( 'edit_published_pages' ) || current_user_can( 'edit_published_posts' ) ) {
			$fusion_load_nonce = wp_create_nonce( 'fusion_load_nonce' );
		}
		// Localize Scripts.
		wp_localize_script(
			$localize_handle,
			'fusionAppConfig',
			[
				'ajaxurl'                => admin_url( 'admin-ajax.php' ),
				'admin_url'              => admin_url(),
				'fusion_load_nonce'      => $fusion_load_nonce,
				'fontawesomeicons'       => fusion_get_icons_array(),
				'customIcons'            => fusion_get_custom_icons_array(),
				'includes_url'           => includes_url(),
				'fusion_library_url'     => esc_url_raw( FUSION_LIBRARY_URL ),
				'fusion_web_fonts'       => apply_filters( 'fusion_live_initial_google_fonts', true ) ? $this->get_googlefonts_ajax() : false,
				'widget_element_enabled' => function_exists( 'fusion_is_element_enabled' ) && fusion_is_element_enabled( 'fusion_widget' ),
			]
		);

		wp_localize_script(
			$localize_handle,
			'builderConfig',
			[
				'fusion_builder_plugin_dir' => defined( 'FUSION_BUILDER_PLUGIN_URL' ) ? FUSION_BUILDER_PLUGIN_URL : '',
				'allowed_post_types'        => class_exists( 'FusionBuilder' ) ? FusionBuilder()->allowed_post_types() : [],
				'disable_encoding'          => get_option( 'avada_disable_encoding' ),
			]
		);

		// Localize scripts. Text strings.
		wp_localize_script( $localize_handle, 'fusionBuilderText', fusion_app_textdomain_strings() );

		// Allow other components to add scripts and styles to builder window.
		do_action( 'fusion_enqueue_live_scripts' );
	}

	/**
	 * Save post content.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function fusion_app_save_post_content() {

		if ( ! $this->has_capability() ) {
			wp_send_json_error( [ 'failure' => 'logged_in' ] );
			wp_die();
		}

		if ( ! isset( $_POST['fusion_load_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fusion_load_nonce'] ) ), 'fusion_load_nonce' ) ) {
			wp_send_json_error( [ 'failure' => 'nonce_check' ] );
			wp_die();
		}

		$this->set_data();

		// Save the shared post details.
		$post_id      = $this->get_data( 'post_id' );
		$post_content = $this->get_data( 'post_content' );
		$post_details = $this->get_data( 'post_details' );

		if ( ( false !== $post_content || false !== $post_details ) && $post_id && '' !== $post_id && false === strpos( $post_id, '-archive' ) ) {

			if ( ! $this->can_edit_post( $post_id ) ) {
				$this->add_save_data( 'content', false, esc_html__( 'You do not have permission to edit this post.', 'fusion-builder' ) );
			} else {
				$post = [
					'ID' => $post_id,
				];

				if ( $post_details ) {

					// Post-Title.
					if ( isset( $post_details['post_title'] ) ) {
						$post['post_title'] = $post_details['post_title'];
					}

					// Post-Permalink.
					if ( isset( $post_details['post_name'] ) ) {
						$post['post_name'] = sanitize_title( $post_details['post_name'] );
					}

					// Post Status.
					if ( isset( $post_details['post_status'] ) ) {
						$post['post_status'] = sanitize_key( $post_details['post_status'] );
					}

					// Parent post.
					if ( is_post_type_hierarchical( get_post_type( $post_id ) ) ) {
						if ( isset( $post_details['post_parent'] ) ) {
							$post['post_parent'] = sanitize_key( $post_details['post_parent'] );
						}
					}

					// Post Order.
					if ( isset( $post_details['menu_order'] ) && ! empty( $post_details['menu_order'] ) ) {
						$post['menu_order'] = absint( $post_details['menu_order'] );
					}

					// Post Format.
					if ( isset( $post_details['post_format'] ) && ! empty( $post_details['post_format'] ) ) {
						set_post_format( $post_id, sanitize_key( $post_details['post_format'] ) );
					}

					// Post taxonomies.
					if ( in_array( $post_details['post_type'], [ 'post', 'avada_portfolio' ], true ) ) {
						$post_taxonomies = get_object_taxonomies( $post_details['post_type'], 'objects' );
						foreach ( $post_taxonomies as $taxonomy ) {
							if ( 'post_format' !== $taxonomy->name ) {
								if ( is_null( $post_details[ $taxonomy->name ] ) ) {

									// If term data is null, we want to remove the whole terms from the post.
									$term_data = $post_details[ $taxonomy->name ];
								} else {
									$term_data = is_array( $post_details[ $taxonomy->name ] ) ? $post_details[ $taxonomy->name ] : explode( ',', $post_details[ $taxonomy->name ] );
									$term_data = array_map( 'intval', $term_data );
								}
								wp_set_object_terms( $post_id, $term_data, $taxonomy->name );
							}
						}
					}
				}

				$update_post = wp_update_post( apply_filters( 'fusion_save_post_object', $post ), true );
				if ( is_wp_error( $update_post ) ) {
					$this->add_save_data( 'content', false, $update_post->get_error_messages() );
				} else {
					$this->add_save_data( 'content', true, esc_html__( 'The page contents updated.', 'fusion-builder' ) );
				}
			}
		} elseif ( $post_id && '' !== $post_id && false !== strpos( $post_id, '-archive' ) ) {

			$term_id = (int) str_replace( 'archive-', '', $post_id );
			if ( ! current_user_can( 'manage_categories', $term_id ) ) {
				$this->add_save_data( 'content', false, esc_html__( 'You do not have permission to edit this archive.', 'fusion-builder' ) );
			} else {
				$term_data    = get_term( $term_id );
				$term_details = [];
				$term         = [];

				if ( $post_details ) {
					$term_details = $post_details;

					// Term Name.
					if ( isset( $term_details['name'] ) ) {
						$term['name'] = $term_details['name'];
					}

					// Term Slug.
					if ( isset( $term_details['slug'] ) ) {
						$term['slug'] = sanitize_title( $term_details['slug'] );
					}

					// Term Description.
					if ( isset( $term_details['description'] ) ) {
						$term['description'] = $term_details['description'];
					}

					// Term Parent.
					if ( is_taxonomy_hierarchical( $term_data->taxonomy ) ) {
						if ( isset( $term_details['parent'] ) ) {
							$term['parent'] = sanitize_key( $term_details['parent'] );
						}
					}

					$update_term = wp_update_term( $term_id, $term_data->taxonomy, apply_filters( 'fusion_save_term_object', $term ) );
					if ( is_wp_error( $update_post ) ) {
						$this->add_save_data( 'content', false, $update_post->get_error_messages() );
					} else {
						$this->add_save_data( 'content', true, esc_html__( 'The archive details updated.', 'fusion-builder' ) );
					}
				}
			}
		}

		do_action( 'fusion_save_post' );

		if ( class_exists( 'Fusion_Cache' ) ) {
			$fusion_cache = new Fusion_Cache();
			$fusion_cache->reset_all_caches();
		}

		do_action( 'fusion_builder_custom_save' );

		$save_data = apply_filters( 'fusion_save_data', $this->save_data );
		wp_send_json_success( $save_data );

		wp_die();
	}

	/**
	 * Check if user can edit this post.
	 *
	 * @access public
	 * @since 3.0.10
	 * @param string $post_id  The id of the post to edit.
	 * @return boolean
	 */
	public function can_edit_post( $post_id ) {
		$post_id          = ! isset( $post_id ) || '' === $post_id ? fusion_library()->get_page_id() : $post_id;
		$post_type        = get_post_type( $post_id );
		$post_type_object = get_post_type_object( $post_type );

		return current_user_can( $post_type_object->cap->edit_post, $post_id );
	}

	/**
	 * Used to set success, failure and message for each module being saved.
	 *
	 * @access public
	 * @since 3.0.10
	 * @param string  $context  The context/module name being saved.
	 * @param boolean $success  Whether the save was successful.
	 * @param mixed   $message  The message or messages to describe save.
	 * @return void
	 */
	public function add_save_data( $context, $success, $message ) {
		$existing_data = $this->save_data;
		$type          = $success ? 'success' : 'failure';

		// Create new entry.
		$new_data[ $type ][ $context ] = $message;

		// Merge in the new data.
		$this->save_data = array_merge_recursive( $new_data, $existing_data );
	}

	/**
	 * This is fired via AJAX to return an array of added terms.
	 *
	 * @access public
	 * @since 3.0.10
	 * @return void|array
	 */
	public function fusion_multiselect_addnew() {
		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

		$values = '';
		if ( isset( $_POST['values'] ) ) {
			$values = wp_unslash( $_POST['values'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		$taxonomy = '';
		if ( isset( $_POST['taxonomy'] ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
		}
		$selection = [];

		if ( ! empty( $values ) && is_array( $values ) ) {
			foreach ( $values as $value ) {
				$term_exists         = term_exists( trim( $value ), $taxonomy );
				$term_data           = null === $term_exists || 0 === $term_exists ? wp_insert_term( trim( $value ), $taxonomy ) : $term_exists;
				$selection[ $value ] = $term_data['term_id'];
			}
		}

		echo wp_json_encode( $selection );
		wp_die();
	}

	/**
	 * This is fired via AJAX to return an array of googlefonts.
	 *
	 * @access public
	 * @since 3.0.10
	 * @return void|array
	 */
	public function get_googlefonts_ajax() {

		global $fusion_settings;

		// Get google-fonts.
		if ( null === self::$google_fonts || empty( self::$google_fonts ) ) {

			$fonts              = include FUSION_LIBRARY_PATH . '/inc/googlefonts-array.php';
			self::$google_fonts = [];
			if ( is_array( $fonts ) ) {
				foreach ( $fonts['items'] as $font ) {
					self::$google_fonts[ $font['family'] ] = [
						'label'    => $font['family'],
						'variants' => $font['variants'],
						'subsets'  => $font['subsets'],
					];
				}
			}
		}
		$google_fonts = self::$google_fonts;

		// An array of all available variants.
		$all_variants = [
			'100'       => esc_html__( 'Ultra-Light 100', 'fusion-builder' ),
			'100light'  => esc_html__( 'Ultra-Light 100', 'fusion-builder' ),
			'100italic' => esc_html__( 'Ultra-Light 100 Italic', 'fusion-builder' ),
			'200'       => esc_html__( 'Light 200', 'fusion-builder' ),
			'200italic' => esc_html__( 'Light 200 Italic', 'fusion-builder' ),
			'300'       => esc_html__( 'Book 300', 'fusion-builder' ),
			'300italic' => esc_html__( 'Book 300 Italic', 'fusion-builder' ),
			'400'       => esc_html__( 'Normal 400', 'fusion-builder' ),
			'regular'   => esc_html__( 'Normal 400', 'fusion-builder' ),
			'italic'    => esc_html__( 'Normal 400 Italic', 'fusion-builder' ),
			'500'       => esc_html__( 'Medium 500', 'fusion-builder' ),
			'500italic' => esc_html__( 'Medium 500 Italic', 'fusion-builder' ),
			'600'       => esc_html__( 'Semi-Bold 600', 'fusion-builder' ),
			'600bold'   => esc_html__( 'Semi-Bold 600', 'fusion-builder' ),
			'600italic' => esc_html__( 'Semi-Bold 600 Italic', 'fusion-builder' ),
			'700'       => esc_html__( 'Bold 700', 'fusion-builder' ),
			'700italic' => esc_html__( 'Bold 700 Italic', 'fusion-builder' ),
			'800'       => esc_html__( 'Extra-Bold 800', 'fusion-builder' ),
			'800bold'   => esc_html__( 'Extra-Bold 800', 'fusion-builder' ),
			'800italic' => esc_html__( 'Extra-Bold 800 Italic', 'fusion-builder' ),
			'900'       => esc_html__( 'Ultra-Bold 900', 'fusion-builder' ),
			'900bold'   => esc_html__( 'Ultra-Bold 900', 'fusion-builder' ),
			'900italic' => esc_html__( 'Ultra-Bold 900 Italic', 'fusion-builder' ),
		];

		// An array of all available subsets.
		$all_subsets = [
			'cyrillic'     => 'Cyrillic',
			'cyrillic-ext' => 'Cyrillic Extended',
			'devanagari'   => 'Devanagari',
			'greek'        => 'Greek',
			'greek-ext'    => 'Greek Extended',
			'khmer'        => 'Khmer',
			'latin'        => 'Latin',
			'latin-ext'    => 'Latin Extended',
			'vietnamese'   => 'Vietnamese',
			'hebrew'       => 'Hebrew',
			'arabic'       => 'Arabic',
			'bengali'      => 'Bengali',
			'gujarati'     => 'Gujarati',
			'tamil'        => 'Tamil',
			'telugu'       => 'Telugu',
			'thai'         => 'Thai',
		];

		// Format the array for use by the typography controls.
		$google_fonts_final = [];
		foreach ( $google_fonts as $family => $args ) {
			$variants = ( isset( $args['variants'] ) ) ? $args['variants'] : [ 'regular', '700' ];
			$subsets  = ( isset( $args['subsets'] ) ) ? $args['subsets'] : [];

			$available_variants = [];
			if ( is_array( $variants ) ) {
				foreach ( $variants as $variant ) {
					if ( array_key_exists( $variant, $all_variants ) ) {
						$available_variants[] = [
							'id'    => 'regular' === $variant ? '400' : $variant,
							'label' => $all_variants[ $variant ],
						];
					}
				}
			}

			$available_subsets = [];
			if ( is_array( $subsets ) ) {
				foreach ( $subsets as $subset ) {
					if ( array_key_exists( $subset, $all_subsets ) ) {
						$available_subsets[] = [
							'id'    => $subset,
							'label' => $all_subsets[ $subset ],
						];
					}
				}
			}

			$google_fonts_final[] = [
				'family'   => $family,
				'label'    => ( isset( $args['label'] ) ) ? $args['label'] : $family,
				'variants' => $available_variants,
				'subsets'  => $available_subsets,
			];
		}

		// Build the standard fonts.
		$standard_fonts       = [
			'Arial, Helvetica, sans-serif',
			"'Arial Black', Gadget, sans-serif",
			"'Bookman Old Style', serif",
			"'Comic Sans MS', cursive",
			'Courier, monospace',
			'Garamond, serif',
			'Georgia, serif',
			'Impact, Charcoal, sans-serif',
			"'Lucida Console', Monaco, monospace",
			"'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
			"'MS Sans Serif', Geneva, sans-serif",
			"'MS Serif', 'New York', sans-serif",
			"'Palatino Linotype', 'Book Antiqua', Palatino, serif",
			'Tahoma,Geneva, sans-serif',
			"'Times New Roman', Times,serif",
			"'Trebuchet MS', Helvetica, sans-serif",
			'Verdana, Geneva, sans-serif',
		];
		$standard_fonts_final = [];
		$default_variants     = [
			[
				'id'    => '400',
				'label' => $all_variants['400'],
			],
			[
				'id'    => 'italic',
				'label' => $all_variants['italic'],
			],
			[
				'id'    => '700',
				'label' => $all_variants['700'],
			],
			[
				'id'    => '700italic',
				'label' => $all_variants['700italic'],
			],
		];
		foreach ( $standard_fonts as $font ) {
			$standard_fonts_final[] = [
				'family'      => $font,
				'label'       => $font,
				'subsets'     => [],
				'is_standard' => true,
				'variants'    => $default_variants,
			];
		}

		$custom_fonts       = [];
		$saved_custom_fonts = $fusion_settings->get( 'custom_fonts' );
		if ( ! empty( $saved_custom_fonts ) && is_array( $saved_custom_fonts ) && isset( $saved_custom_fonts['name'] ) && ! empty( $saved_custom_fonts['name'] ) ) {
			foreach ( $saved_custom_fonts['name'] as $font ) {
				$custom_fonts[] = [
					'family'   => $font,
					'label'    => $font,
					'subsets'  => [],
					'variants' => [
						[
							'id'    => '400',
							'label' => $all_variants['400'],
						],
					],
				];
			}
		}

		$fonts_array = [
			'standard' => $standard_fonts_final,
			'google'   => $google_fonts_final,
			'custom'   => $custom_fonts,
		];

		if ( $this->is_ajax ) {
			echo wp_json_encode( $fonts_array );
			wp_die();
		}

		return $fonts_array;
	}

	/**
	 * Include preferences.
	 *
	 * @access public
	 * @since 3.0.10
	 * @return void
	 */
	public function set_preference() {
		require_once FUSION_LIBRARY_PATH . '/inc/fusion-app/class-fusion-preferences.php';
		$this->preferences = new Fusion_Preferences();
	}

	/**
	 * Adds an "Access-Control-Allow-Origin" meta in the <head> to avoid cross-site-scripting errors in the browser.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function access_control_allow_origin() {
		echo '<meta http-equiv="Access-Control-Allow-Origin" content="' . esc_url_raw( site_url() ) . '">';
	}

	/**
	 * Adds authorization check html..
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function fusion_authorization() {
		wp_auth_check_html();
	}


	/**
	 * Adds authorization check html.
	 *
	 * @access public
	 * @since 2.0
	 * @param array  $response  The Heartbeat response.
	 * @param array  $data      The $_POST data sent.
	 * @param string $screen_id The screen id.
	 * @return array $response  Reponse data.
	 */
	public function fusion_refresh_nonces( $response, $data, $screen_id ) {
		if ( ! current_user_can( 'edit_theme_options' ) && ! current_user_can( 'edit_published_pages' ) && ! current_user_can( 'edit_published_posts' ) ) {
			return $response;
		}

		// TODO: add more checks here.
		$response['fusion_builder'] = [
			'fusion_load_nonce' => wp_create_nonce( 'fusion_load_nonce' ),
		];

		return $response;
	}

	/**
	 * Changes the global $wp_query to emulate the current page.
	 *
	 * @access public
	 * @since 6.0
	 * @return WP_Query
	 */
	public function emulate_wp_query() {
		global $wp_query, $wp_the_query, $post;

		$query = $this->get_data( 'query' );
		if ( $query ) {

			// Encode to json and then decode it so that we're sure everything (including all nested levels)
			// are formatted as an array and there are no onjects.
			$args = json_decode( wp_json_encode( $query ), true );

			$this->backup_wp_query = $wp_query;
			$wp_query              = new WP_Query( $args ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride

			if ( fusion_doing_ajax() ) {
				$wp_the_query = $wp_query;
				$post_id      = $this->get_data( 'post_id' );
				$post_id      = $post_id ? $post_id : 0;
				$post         = get_post( $post_id );
			}
			return $wp_query;
		}

		/**
		 * Fallback implementation in case there is no query arg.
		 */
		$post_id   = $this->get_data( 'post_id' );
		$post_id   = $post_id ? $post_id : 0;
		$post_type = get_post_type( $post_id );

		$args = [
			'p'         => $post_id,
			'post_type' => $post_type ? $post_type : 'any',
		];

		if ( false !== strpos( $args['p'], '-archive' ) ) {
			$term_id = absint( $args['p'] );
			$term    = get_term( $term_id );
			if ( is_object( $term ) && isset( $term->taxonomy ) ) {
				global $wp_taxonomies;
				$post_type = 'post';
				if ( isset( $wp_taxonomies[ $term->taxonomy ] ) ) {
					$post_types = $wp_taxonomies[ $term->taxonomy ]->object_type;
					$post_type  = $post_types[0];
				}

				$args = [
					'post_type' => $post_type,
					'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery
						'taxonomy' => $term->taxonomy,
						'filed'    => 'slug',
						'terms'    => $term->slug,
					],
				];

				// Category query.
				if ( 'category' === $term->taxonomy ) {
					$args = [
						'category_name' => $term->slug,
					];
				}

				// Tag query.
				if ( 'tag' === $term->taxonomy ) {
					$args = [
						'tag' => $term->slug,
					];
				}
			}
		}

		$this->backup_wp_query = $wp_query;
		$wp_query              = new WP_Query( $args ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		return $wp_query;
	}

	/**
	 * Restores the global $wp_query.
	 *
	 * @access public
	 * @since 6.2
	 * @return void
	 */
	public function restore_wp_query() {
		global $wp_query;

		if ( null !== $this->backup_wp_query ) {
			$wp_query = $this->backup_wp_query;
		}

	}
}

/**
 * Instantiates the Fusion_App class.
 * Make sure the class is properly set-up.
 * The Fusion_App class is a singleton
 * so we can directly access the one true FusionBuilder object using this function.
 *
 * @since object 2.0
 * @return object Fusion_App
 */
function Fusion_App() { // phpcs:ignore WordPress.NamingConventions
	return Fusion_App::get_instance();
}
