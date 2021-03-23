<?php
/**
 * The main FusionBuilder class.
 *
 * @package fusion-builder
 * @since 2.0
 */

/**
 * Main FusionBuilder Class.
 *
 * @since 1.0
 */
class FusionBuilder {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 1.0
	 * @var object
	 */
	private static $instance;

	/**
	 * An array of allowed post types.
	 *
	 * @static
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private static $allowed_post_types = [];

	/**
	 * An array of the element option descriptions.
	 *
	 * @static
	 * @access public
	 * @since 2.0
	 * @var array
	 */
	public static $element_descriptions_map = [];

	/**
	 * An array of the element option dependencies.
	 *
	 * @static
	 * @access public
	 * @since 2.0
	 * @var array
	 */
	public static $element_dependency_map = [];

	/**
	 * Fusion_Product_Registration
	 *
	 * @access public
	 * @var object Fusion_Product_Registration.
	 */
	public $registration;

	/**
	 * Fusion_Images.
	 *
	 * @access public
	 * @var object
	 */
	public $images;

	/**
	 * An array of body classes to be added.
	 *
	 * @access private
	 * @since 1.1
	 * @var array
	 */
	private $body_classes = [];

	/**
	 * Determine if we're currently upgrading/migration options.
	 *
	 * @static
	 * @access public
	 * @var bool
	 */
	public static $is_updating = false;

	/**
	 * The Fusion_Builder_Options_Panel object.
	 *
	 * @access private
	 * @since 1.1.0
	 * @var object
	 */
	private $fusion_builder_options_panel;

	/**
	 * The Fusion_Builder_Dynamic_CSS object.
	 *
	 * @access private
	 * @since 1.1.3
	 * @var object
	 */
	private $fusion_builder_dynamic_css;

	/**
	 * URL to the js files.
	 *
	 * @static
	 * @access public
	 * @since 1.1.3
	 * @var string
	 */
	public static $js_folder_url;

	/**
	 * Path to the js files.
	 *
	 * @static
	 * @access public
	 * @since 1.1.3
	 * @var string
	 */
	public static $js_folder_path;

	/**
	 * Shortcode array for live builder.
	 *
	 * @access public
	 * @var array $shortcode_array.
	 */
	public $shortcode_array;

	/**
	 * Parent id scope for shortcode render.
	 *
	 * @access public
	 * @var mixed $shortcode_parent.
	 */
	public $shortcode_parent;

	/**
	 * Extra fonts for page to load.
	 *
	 * @access private
	 * @since 2.2
	 * @var mixed
	 */
	public $extra_fonts = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 */
	public static function get_instance() {

		global $wp_rich_edit, $is_gecko, $is_opera, $is_safari, $is_chrome, $is_IE, $is_edge;

		if ( ! isset( $wp_rich_edit ) ) {
			$wp_rich_edit = false;

			// Defaults to 'true' for logged out users.
			if ( 'true' == @get_user_option( 'rich_editing' ) || ! @is_user_logged_in() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.StrictComparisons.LooseComparison
				if ( $is_safari && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
					$wp_rich_edit = ! wp_is_mobile() || ( preg_match( '!AppleWebKit/(\d+)!', wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), $match ) && intval( $match[1] ) >= 534 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				} elseif ( $is_gecko || $is_chrome || $is_IE || $is_edge || ( $is_opera && ! wp_is_mobile() ) ) {
					$wp_rich_edit = true;
				}
			}
		}

		if ( $wp_rich_edit ) {

			// If the single instance hasn't been set, set it now.
			if ( ! self::$instance ) {
				self::$instance = new self();
			}
		} else {
			add_action( 'edit_form_after_title', 'fusion_builder_add_notice_of_disabled_rich_editor' );
		}

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new FusionBuilder();
		}
		return self::$instance;
	}

	/**
	 * Initializes the plugin by setting localization, hooks, filters,
	 * and administrative functions.
	 *
	 * @access private
	 * @since 1.0
	 */
	private function __construct() {
		$path                  = ( true === FUSION_BUILDER_DEV_MODE ) ? '' : '/min';
		$this->shortcode_array = [];

		self::$js_folder_url  = FUSION_BUILDER_PLUGIN_URL . 'assets/js' . $path;
		self::$js_folder_path = FUSION_BUILDER_PLUGIN_DIR . 'assets/js' . $path;

		self::set_element_description_map();
		self::set_element_dependency_map();

		$this->set_is_updating();
		$this->includes();
		$this->register_scripts();
		$this->init();

		if ( is_admin() && ! class_exists( 'Avada' ) ) {
			$this->registration = new Fusion_Product_Registration(
				[
					'type' => 'plugin',
					'name' => 'Fusion Builder',
				]
			);
		}
		add_action( 'fusion_settings_construct', [ $this, 'add_options_to_fusion_settings' ] );

		$this->versions_compare();

		add_action( 'wp', [ $this, 'add_media_query_styles' ] );
	}

	/**
	 * Initializes the plugin by setting localization, hooks, filters,
	 * and administrative functions.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function init() {

		if ( is_admin() ) {
			do_action( 'fusion_builder_before_init' );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

		// Display Fusion Builder wrapper.
		$options           = get_option( 'fusion_builder_settings' );
		$enable_builder_ui = '1';
		if ( isset( $options['enable_builder_ui'] ) ) {
			$enable_builder_ui = $options['enable_builder_ui'];
		}

		if ( $enable_builder_ui ) {
			add_action( 'edit_form_after_title', [ $this, 'before_main_editor' ], 999 );
			add_action( 'edit_form_after_editor', [ $this, 'after_main_editor' ] );
		}

		// WP editor scripts.
		add_action( 'admin_print_footer_scripts', [ $this, 'enqueue_wp_editor_scripts' ] );

		// Add Page Builder meta box.
		add_action( 'add_meta_boxes', [ $this, 'add_builder_meta_box' ] );
		add_filter( 'wpseo_metabox_prio', [ $this, 'set_yoast_meta_box_priority' ] );

		// Page Builder Helper metaboxes.
		add_action( 'add_meta_boxes', [ $this, 'add_builder_helper_meta_box' ] );

		// Content filter.
		add_filter( 'the_content', [ $this, 'fix_builder_shortcodes' ] );
		add_filter( 'the_content', [ $this, 'fusion_calculate_columns' ], 0 );
		add_filter( 'the_content', [ $this, 'fusion_calculate_containers' ], 1 );
		add_filter( 'widget_text', [ $this, 'fusion_calculate_columns' ], 1, 3 );
		add_filter( 'widget_display_callback', [ $this, 'fusion_disable_wpautop_in_widgets' ], 10, 3 );
		add_filter( 'no_texturize_shortcodes', [ $this, 'exempt_from_wptexturize' ] );

		// Save Helper metaboxes.
		add_action( 'save_post', [ $this, 'metabox_settings_save_details' ], 10, 2 );

		// Builder mce button.
		add_filter( 'mce_external_plugins', [ $this, 'add_rich_plugins' ] );
		add_filter( 'mce_buttons', [ $this, 'register_rich_buttons' ] );

		// Fusion Builder menu icon.
		add_action( 'admin_head', [ $this, 'admin_styles' ] );

		// Enable shortcodes in text widgets.
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'body_class', [ $this, 'body_class_filter' ] );

		// Replace next page shortcode.
		add_filter( 'the_posts', [ $this, 'next_page' ] );

		// Dynamic-css additions.
		add_filter( 'fusion_dynamic_css_final', [ $this, 'shortcode_styles_dynamic_css' ], 100 );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_action_settings_link' ] );

		// Exclude post types from Events Calendar.
		add_filter( 'tribe_tickets_settings_post_types', [ $this, 'fusion_builder_exclude_post_type' ] );

		// Add admin body classes.
		add_action( 'admin_body_class', [ $this, 'admin_body_class' ] );

		// Activate ConvertPlug element on plugin activation after Fusion Builder.
		add_action( 'after_cp_activate', [ $this, 'activate_convertplug_element' ] );

		// Add Google fonts used within content.
		add_filter( 'fusion_google_fonts', [ $this, 'set_extra_google_fonts' ] );
		add_filter( 'fusion_google_font_subsets', [ $this, 'set_extra_google_font_subsets' ] );
		add_filter( 'fusion_google_fonts_extra', [ $this, 'has_extra_google_fonts' ] );

		add_action( 'wp_head', [ $this, 'add_element_visibility_styles' ] );
	}

	/**
	 * Returns whether or not page has extra google fonts.
	 *
	 * @access public
	 * @since 2.0
	 * @param mixed $has_extra Has extra google fonts.
	 */
	public function has_extra_google_fonts( $has_extra ) {
		$extra_fonts = $this->get_extra_google_fonts();

		if ( $extra_fonts ) {
			return true;
		}
		return $has_extra;
	}

	/**
	 * Get extra fonts.
	 *
	 * @access public
	 * @since 2.2
	 * @return mixed.
	 */
	public function get_extra_google_fonts() {
		if ( null === $this->extra_fonts ) {
			$id          = get_query_var( 'fb-edit' ) ? get_query_var( 'fb-edit' ) : get_the_id();
			$extra_fonts = maybe_unserialize( get_post_meta( $id, '_fusion_google_fonts', true ) );

			if ( class_exists( 'Fusion_Template_Builder' ) && function_exists( 'get_post_type' ) && 'fusion_tb_section' !== get_post_type() ) {
				$templates     = Fusion_Template_Builder()->get_template_terms();
				$submenu_items = [];

				foreach ( $templates as $key => $template_arr ) {
					$template = Fusion_Template_Builder::get_instance()->get_override( $key );
					if ( $template ) {
						$template_fonts = get_post_meta( $template->ID, '_fusion_google_fonts', true );
						if ( is_string( $template_fonts ) ) {
							$template_fonts = maybe_unserialize( $template_fonts );
						}

						if ( empty( $template_fonts ) ) {
							continue;
						}

						if ( ! $extra_fonts ) {
							$extra_fonts = $template_fonts;
							continue;
						}

						$extra_fonts = array_merge( $template_fonts, $extra_fonts );
					}
				}
			}

			$this->extra_fonts = $extra_fonts;
		}
		return $this->extra_fonts;
	}

	/**
	 * Sets inline google fonts to be enqueued.
	 *
	 * @access public
	 * @since 2.0
	 * @param mixed $fonts Fonts.
	 */
	public function set_extra_google_fonts( $fonts ) {
		$extra_fonts = $this->get_extra_google_fonts();

		if ( $extra_fonts && is_array( $extra_fonts ) ) {
			foreach ( $extra_fonts as $family => $extra_font ) {
				if ( ! isset( $fonts[ $family ] ) ) {
					$fonts[ $family ] = [];
				}
				if ( isset( $extra_font['variants'] ) && is_array( $extra_font['variants'] ) ) {
					foreach ( $extra_font['variants'] as $variant ) {
						$fonts[ $family ][] = $variant;
					}
					$fonts[ $family ] = array_unique( $fonts[ $family ] );
				} else {
					$fonts[ $family ] = [ '400', 'regular' ];
				}
			}
		}
		return $fonts;
	}

	/**
	 * Sets inline google fonts subsets.
	 *
	 * @access public
	 * @since 2.0
	 * @param mixed $subsets Subsets.
	 */
	public function set_extra_google_font_subsets( $subsets ) {
		$id          = get_query_var( 'fb-edit' ) ? get_query_var( 'fb-edit' ) : get_the_id();
		$extra_fonts = get_post_meta( $id, '_fusion_google_fonts', true );

		if ( is_string( $extra_fonts ) ) {
			$extra_fonts = maybe_unserialize( $extra_fonts );
		}

		if ( $extra_fonts && is_array( $extra_fonts ) ) {
			foreach ( $extra_fonts as $extra_font ) {
				if ( isset( $extra_font['subsets'] ) && is_array( $extra_font['subsets'] ) ) {
					foreach ( $extra_font['subsets'] as $subset ) {
						$subsets[] = $subset;
					}
				}
			}
			return array_unique( array_filter( $subsets ) );
		}
		return $subsets;
	}

	/**
	 * Helper function for PHP 5.2 compatibility in the next_page method.
	 *
	 * @access private
	 * @since 1.1.0
	 * @param mixed $p Posts.
	 */
	private function next_page_helper( $p ) {

		if ( false !== strpos( $p->post_content, '[fusion_builder_next_page]' ) ) {
			$p->post_content = str_replace( '[fusion_builder_next_page]', '<!--nextpage-->', $p->post_content );
		}
		return $p;

	}

	/**
	 * Replace fusion_builder_next_page shortcode with <!--nextpage-->
	 *
	 * @access public
	 * @since 1.1
	 * @param array $posts The array of posts.
	 */
	public function next_page( $posts ) {
		if ( null !== $posts ) {
			$posts = array_map( [ $this, 'next_page_helper' ], $posts );
		}
		return $posts;

	}

	/**
	 * Set WP editor settings.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function enqueue_wp_editor_scripts() {
		global $typenow;

		if ( isset( $typenow ) && in_array( $typenow, self::allowed_post_types(), true ) ) {

			if ( ! class_exists( '_WP_Editors' ) ) {
				require wp_normalize_path( ABSPATH . WPINC . '/class-wp-editor.php' );
			}

			$set = _WP_Editors::parse_settings( 'fusion_builder_editor', [] );

			if ( ! current_user_can( 'upload_files' ) ) {
				$set['media_buttons'] = false;
			}

			_WP_Editors::editor_settings( 'fusion_builder_editor', $set );
		}
	}

	/**
	 * Processes that must run when the plugin is activated.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 */
	public static function activation() {

		if ( ! class_exists( 'Fusion' ) ) {
			// Include Fusion-Library.
			include_once FUSION_BUILDER_PLUGIN_DIR . 'inc/lib/fusion-library.php';
		}

		$installed_plugins   = get_plugins();
		$keys                = array_keys( get_plugins() );
		$fusion_core_key     = '';
		$fusion_core_slug    = 'fusion-core';
		$fusion_core_version = '';

		foreach ( $keys as $key ) {
			if ( preg_match( '|^' . $fusion_core_slug . '/|', $key ) ) {
				$fusion_core_key = $key;
			}
		}

		if ( $fusion_core_key ) {
			$fusion_core         = $installed_plugins[ $fusion_core_key ];
			$fusion_core_version = $fusion_core['Version'];

			if ( version_compare( $fusion_core_version, '3.0', '<' ) ) {
				$message  = '<style>#error-page > p{display:-webkit-flex;display:flex;}#error-page img {height: 120px;margin-right:25px;}.fb-heading{font-size: 1.17em; font-weight: bold; display: block; margin-bottom: 15px;}.fb-link{display: inline-block;margin-top:15px;}.fb-link:focus{outline:none;box-shadow:none;}</style>';
				$message .= '<img src="' . esc_url_raw( plugins_url( 'images/icons/fb_logo.svg', __FILE__ ) ) . '" />';
				$message .= '<span><span class="fb-heading">Fusion Builder could not be activated</span>';
				$message .= '<span>Fusion Builder can only be activated on installs that use Fusion Core 3.0 or higher. Click the link below to install/activate Fusion Core 3.0, then you can activate Fusion Builder.</span>';
				$message .= '<a class="fb-link" href="' . esc_url_raw( admin_url( 'admin.php?page=avada-plugins' ) ) . '">' . esc_attr__( 'Go to the Avada plugin installation page', 'fusion-builder' ) . '</a></span>';
				wp_die( $message ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		// Delete the patcher caches.
		delete_site_transient( 'fusion_patcher_check_num' );

		if ( ! class_exists( 'Fusion_Cache' ) ) {
			include_once FUSION_BUILDER_PLUGIN_DIR . 'inc/lib/inc/class-fusion-cache.php';
		}

		// Auto activate elements.
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/helpers.php';
		if ( function_exists( 'fusion_builder_auto_activate_element' ) ) {
			$db_version = get_option( 'fusion_builder_version', false );

			// Only activate if a user is updating from a version which is older than the version the element was added to.
			if ( version_compare( $db_version, '1.0', '<' ) ) {
				fusion_builder_auto_activate_element( 'fusion_gallery' );
				if ( class_exists( 'Convert_Plug' ) ) {
					fusion_builder_auto_activate_element( 'fusion_convert_plus' );
				}
			}
			if ( version_compare( $db_version, '1.5', '<' ) ) {
				fusion_builder_auto_activate_element( 'fusion_syntax_highlighter' );
				fusion_builder_auto_activate_element( 'fusion_chart' );
				fusion_builder_auto_activate_element( 'fusion_image_before_after' );
			}
			if ( version_compare( $db_version, '2.1', '<' ) ) {
				fusion_builder_auto_activate_element( 'fusion_audio' ); // Added in v2.1.
			}

			if ( version_compare( $db_version, '2.2', '<' ) ) {
				fusion_builder_auto_activate_element( 'fusion_search' );
				fusion_builder_auto_activate_element( 'fusion_tb_archives' );
				fusion_builder_auto_activate_element( 'fusion_tb_author' );
				fusion_builder_auto_activate_element( 'fusion_tb_comments' );
				fusion_builder_auto_activate_element( 'fusion_tb_content' );
				fusion_builder_auto_activate_element( 'fusion_tb_featured_slider' );
				fusion_builder_auto_activate_element( 'fusion_tb_pagination' );
				fusion_builder_auto_activate_element( 'fusion_tb_related' );
				fusion_builder_auto_activate_element( 'fusion_tb_results' );
			}
		}

		$fusion_cache = new Fusion_Cache();
		$fusion_cache->reset_all_caches();

		// FLush rewrite rules.
		add_action(
			'init',
			function() {
				// Ensure the $wp_rewrite global is loaded.
				global $wp_rewrite;
				// Call flush_rules() as a method of the $wp_rewrite object.
				$wp_rewrite->flush_rules( false );
			},
			99
		);
	}

	/**
	 * Activate Convertplug element on plugin activation.
	 *
	 * @static
	 * @access public
	 * @since 1.7
	 */
	public function activate_convertplug_element() {
		if ( function_exists( 'fusion_builder_auto_activate_element' ) ) {
			fusion_builder_auto_activate_element( 'fusion_convert_plus' );
		}
	}

	/**
	 * Processes that must run when the plugin is deactivated.
	 *
	 * @static
	 * @access public
	 * @since 1.1
	 */
	public static function deactivation() {
		// Delete the patcher caches.
		delete_site_transient( 'fusion_patcher_check_num' );

		if ( ! class_exists( 'Fusion_Cache' ) ) {
			include_once FUSION_BUILDER_PLUGIN_DIR . 'inc/lib/inc/class-fusion-cache.php';
		}

		$fusion_cache = new Fusion_Cache();
		$fusion_cache->reset_all_caches();
	}

	/**
	 * Add TinyMCE rich editor button.
	 *
	 * @access public
	 * @since 1.0
	 * @param array $buttons The array of available buttons.
	 * @return array
	 */
	public function register_rich_buttons( $buttons ) {
		if ( is_array( $buttons ) ) {
			array_push( $buttons, 'fusion_button' );
		}

		return $buttons;
	}

	/**
	 * Add Fusion Builder menu icon.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function admin_styles() {

		if ( class_exists( 'Avada' ) ) {
			return;
		}

		$font_url = FUSION_LIBRARY_URL . '/assets/fonts/icomoon-admin';
		$font_url = str_replace( [ 'http://', 'https://' ], '//', $font_url );

		echo '<style type="text/css">';
		echo '@font-face {';
		echo 'font-family: "icomoon";';
		echo 'src:url("' . esc_url_raw( $font_url ) . '/icomoon.eot");';
		echo 'src:url("' . esc_url_raw( $font_url ) . '/icomoon.eot?#iefix") format("embedded-opentype"),';
		echo 'url("' . esc_url_raw( $font_url ) . '/icomoon.woff") format("woff"),';
		echo 'url("' . esc_url_raw( $font_url ) . '/icomoon.ttf") format("truetype"),';
		echo 'url("' . esc_url_raw( $font_url ) . '/icomoon.svg#icomoon") format("svg");';
		echo 'font-weight: normal;font-style: normal;';
		echo '}';

		if ( current_user_can( 'switch_themes' ) ) {
			echo '.dashicons-fusiona-logo:before{content: "\e62d"; font-family: "icomoon"; speak: none; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; line-height: 1; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;}';
			echo '#wp-admin-bar-fb-edit > .ab-item::before { content: "\e901"; font-family: "icomoon"; font-size: 22px; font-weight: 400; margin-top: 1px; }';
		}

		echo '</style>';
	}

	/**
	 * Define TinyMCE rich editor js plugin.
	 *
	 * @access public
	 * @since 1.0
	 * @param array $plugin_array The plugins array.
	 * @return array.
	 */
	public function add_rich_plugins( $plugin_array ) {
		if ( is_admin() ) {
			$plugin_array['fusion_button'] = FUSION_BUILDER_PLUGIN_URL . 'js/fusion-plugin.js';
		}

		return $plugin_array;
	}

	/**
	 * Set global variables.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function init_global_vars() {
		global $wp_version, $content_media_query, $six_fourty_media_query, $three_twenty_six_fourty_media_query, $ipad_portrait_media_query, $content_min_media_query, $small_media_query, $medium_media_query, $large_media_query, $six_columns_media_query, $five_columns_media_query, $four_columns_media_query, $three_columns_media_query, $two_columns_media_query, $one_column_media_query, $dynamic_css, $dynamic_css_helpers;

		$fusion_settings = fusion_get_fusion_settings();

		$c_page_id           = fusion_library()->get_page_id();
		$dynamic_css         = $this->fusion_builder_dynamic_css;
		$dynamic_css_helpers = $dynamic_css->get_helpers();

		$side_header_width       = ( 'top' === fusion_get_option( 'header_position' ) ) ? 0 : intval( $fusion_settings->get( 'side_header_width' ) );
		$content_media_query     = '@media only screen and (max-width: ' . ( intval( $side_header_width ) + intval( $fusion_settings->get( 'content_break_point' ) ) ) . 'px)';
		$six_fourty_media_query  = '@media only screen and (max-width: ' . ( intval( $side_header_width ) + 640 ) . 'px)';
		$content_min_media_query = '@media only screen and (min-width: ' . ( intval( $side_header_width ) + intval( $fusion_settings->get( 'content_break_point' ) ) ) . 'px)';

		$three_twenty_six_fourty_media_query = '@media only screen and (min-device-width: 320px) and (max-device-width: 640px)';
		$ipad_portrait_media_query           = '@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait)';

		// Visible options for shortcodes.
		$small_media_query  = '@media screen and (max-width: ' . intval( $fusion_settings->get( 'visibility_small' ) ) . 'px)';
		$medium_media_query = '@media screen and (min-width: ' . ( intval( $fusion_settings->get( 'visibility_small' ) ) + 1 ) . 'px) and (max-width: ' . intval( $fusion_settings->get( 'visibility_medium' ) ) . 'px)';
		$large_media_query  = '@media screen and (min-width: ' . ( intval( $fusion_settings->get( 'visibility_medium' ) ) + 1 ) . 'px)';

		// # Grid System.
		$main_break_point = (int) $fusion_settings->get( 'grid_main_break_point' );
		if ( 640 < $main_break_point ) {
			$breakpoint_range = $main_break_point - 640;
		} else {
			$breakpoint_range = 360;
		}

		$breakpoint_interval = $breakpoint_range / 5;

		$six_columns_breakpoint   = $main_break_point + $side_header_width;
		$five_columns_breakpoint  = $six_columns_breakpoint - $breakpoint_interval;
		$four_columns_breakpoint  = $five_columns_breakpoint - $breakpoint_interval;
		$three_columns_breakpoint = $four_columns_breakpoint - $breakpoint_interval;
		$two_columns_breakpoint   = $three_columns_breakpoint - $breakpoint_interval;
		$one_column_breakpoint    = $two_columns_breakpoint - $breakpoint_interval;

		$six_columns_media_query   = '@media only screen and (min-width: ' . $five_columns_breakpoint . 'px) and (max-width: ' . $six_columns_breakpoint . 'px)';
		$five_columns_media_query  = '@media only screen and (min-width: ' . $four_columns_breakpoint . 'px) and (max-width: ' . $five_columns_breakpoint . 'px)';
		$four_columns_media_query  = '@media only screen and (min-width: ' . $three_columns_breakpoint . 'px) and (max-width: ' . $four_columns_breakpoint . 'px)';
		$three_columns_media_query = '@media only screen and (min-width: ' . $two_columns_breakpoint . 'px) and (max-width: ' . $three_columns_breakpoint . 'px)';
		$two_columns_media_query   = '@media only screen and (max-width: ' . $two_columns_breakpoint . 'px)';
		$one_column_media_query    = '@media only screen and (max-width: ' . $one_column_breakpoint . 'px)';

	}

	/**
	 * Find and include all shortcodes within shortcodes folder.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function init_shortcodes() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-alert.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-audio.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-blank-page.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-breadcrumbs.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-blog.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-button.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-chart.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-checklist.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-code-block.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-column-inner.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-column.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-contact-form7.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-container.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-content-boxes.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-convertplus.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-countdown.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-counters-box.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-counters-circle.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-dropcap.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-events.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-flip-boxes.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-fontawesome.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-gallery.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-global.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-google-map.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-gravity-form.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-highlight.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-image-before-after.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-image-carousel.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-image.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-layer-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-lightbox.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-menu-anchor.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-modal.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-nextpage.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-one-page-link.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-person.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-popover.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-post-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-pricing-table.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-progress.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-recent-posts.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-revolution-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-row-inner.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-row.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-section-separator.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-separator.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-sharingbox.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-social-links.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-soundcloud.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-syntax-highlighter.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-search.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-table.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-tabs.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-tagline.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-testimonials.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-text.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-title.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-toggle.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-tooltip.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-user-login.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-vimeo.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-video.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-widget-area.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-widget.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-woo-featured-products-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-woo-product-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-woo-shortcodes.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/fusion-youtube.php';
	}

	/**
	 * Add helper meta box on allowed post types.
	 *
	 * @access public
	 * @since 1.0
	 * @param mixed $post The post (not used in this context).
	 */
	public function single_settings_meta_box( $post ) {
		global $typenow;

		wp_nonce_field( basename( __FILE__ ), 'fusion_settings_nonce' );
		?>
		<?php if ( isset( $typenow ) && in_array( $typenow, self::allowed_post_types(), true ) ) : ?>
			<p class="fusion_page_settings">
				<input type="text" id="fusion_use_builder" name="fusion_use_builder" value="<?php echo esc_attr( get_post_meta( $post->ID, 'fusion_builder_status', true ) ); ?>" />
			</p>
		<?php endif; ?>
		<?php

	}

	/**
	 * Add Fusion library message meta box.
	 *
	 * @access public
	 * @since 1.0
	 * @param mixed $post The post (not used in this context).
	 */
	public function library_single_message_box( $post ) {
		$terms   = get_the_terms( $post->ID, 'element_category' );
		$message = '';

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$term_name = $term->name;

				if ( 'sections' === $term_name ) {
					$message = esc_html__( 'You are editing a saved container from the Fusion Builder Library which will update with your changes when you click the update button. This is not a real page, only a saved container.', 'fusion-builder' );
				} elseif ( 'columns' === $term_name ) {
					$message = esc_html__( 'You are editing a saved column from the Fusion Builder Library which will update with your changes when you click the update button. This is not a real page, only a saved column.', 'fusion-builder' );
				} elseif ( 'elements' === $term_name ) {
					$message = esc_html__( 'You are editing a saved element from the Fusion Builder Library which will update with your changes when you click the update button. This is not a real page, only a saved element.', 'fusion-builder' );
				}
			}
		}
		?>

		<p class="fusion-library-single-message">
			<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</p>

		<?php
	}

	/**
	 * Add Helper MetaBox.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function add_builder_helper_meta_box() {
		$screens = self::allowed_post_types();

		add_meta_box( 'fusion_settings_meta_box', esc_attr__( 'Fusion Builder Settings', 'fusion-builder' ), [ $this, 'single_settings_meta_box' ], $screens, 'side', 'high' );

		add_meta_box( 'fusion_library_message_box', esc_attr__( 'Important', 'fusion-builder' ), [ $this, 'library_single_message_box' ], 'fusion_element', 'side', 'low' );
	}

	/**
	 * Save Helper MetaBox Settings.
	 *
	 * @access public
	 * @since 1.0
	 * @param int|string $post_id The post ID.
	 * @param object     $post    The post.
	 * @return int|void
	 */
	public function metabox_settings_save_details( $post_id, $post ) {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		if ( ! isset( $_POST['fusion_settings_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['fusion_settings_nonce'] ), basename( __FILE__ ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return $post_id;
		}

		// Make sure we delete, necessary for slashses.
		if ( isset( $_POST['_fusion_builder_custom_css'] ) && '' === $_POST['_fusion_builder_custom_css'] ) {
			delete_post_meta( $post_id, '_fusion_builder_custom_css' );
		}

		if ( isset( $_POST['_fusion_google_fonts'] ) && '' !== $_POST['_fusion_google_fonts'] ) {
			update_post_meta( $post_id, '_fusion_google_fonts', json_decode( wp_unslash( $_POST['_fusion_google_fonts'] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( isset( $_POST['fusion_use_builder'] ) ) {
			update_post_meta( $post_id, 'fusion_builder_status', sanitize_text_field( wp_unslash( $_POST['fusion_use_builder'] ) ) );
		} else {
			delete_post_meta( $post_id, 'fusion_builder_status' );
		}

	}

	/**
	 * Fix shortcode content on front end by getting rid of random p tags.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $content The content.
	 * return string          The content, modified.
	 */
	public function fix_builder_shortcodes( $content ) {
		$is_builder_page = is_singular() && ( 'active' === get_post_meta( get_the_ID(), 'fusion_builder_status', true ) || 'yes' === get_post_meta( get_the_ID(), 'fusion_builder_converted', true ) );
		$has_override    = Fusion_Template_Builder::get_instance()->get_override( 'content' );
		if ( $is_builder_page || $has_override ) {
			$content = fusion_builder_fix_shortcodes( $content );
		}
		return $content;
	}

	/**
	 * Count the containers of a page.
	 *
	 * @access public
	 * @since 1.3
	 * @param string $content The content.
	 * @return string $content
	 */
	public function fusion_calculate_containers( $content ) {
		global $global_container_count;

		if ( ! $global_container_count ) {
			$global_container_count = substr_count( $content, '[fusion_builder_container' );
		}

		return $content;
	}


	/**
	 * Count the columns and break up to rows.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $content         The content.
	 * @param string $widget_instance The widget Instance.
	 * @param string $widget          The widget.
	 * @return $content
	 */
	public function fusion_calculate_columns( $content, $widget_instance = '', $widget = '' ) {

		global $global_column_array, $global_column_inner_array;
		$is_in_widget = false;
		$content_id   = get_the_ID();
		if ( is_object( $widget ) && isset( $widget->id ) ) {
			$content_id   = $widget->id;
			$is_in_widget = true;
		}

		$content = apply_filters( 'content_edit_pre', $content, $content, $content_id );

		$needles = [
			[
				'row_opening'    => '[fusion_builder_row]',
				'row_closing'    => '[/fusion_builder_row]',
				'column_opening' => '[fusion_builder_column ',
			],
			[
				'row_opening'    => '[fusion_builder_row_inner]',
				'row_closing'    => '[/fusion_builder_row_inner]',
				'column_opening' => '[fusion_builder_column_inner ',
			],
		];

		$column_opening_positions_index = [];
		$php_version                    = phpversion();

		foreach ( $needles as $needle ) {
			$column_array                 = [];
			$last_pos                     = -1;
			$positions                    = [];
			$row_index                    = -1;
			$row_shortcode_name_length    = strlen( $needle['row_opening'] );
			$column_shortcode_name_length = strlen( $needle['column_opening'] );

			// Get all positions of [fusion_builder_row shortcode.
			while ( ( $last_pos = strpos( $content, $needle['row_opening'], $last_pos + 1 ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				$positions[] = $last_pos;
			}

			// For each row.
			foreach ( $positions as $position ) {

				$row_closing_position = strpos( $content, $needle['row_closing'], $position );

				// Search within this range/row.
				$range = $row_closing_position - $position + 1;
				// Row content.
				$row_content          = substr( $content, $position + strlen( $needle['row_opening'] ), $range );
				$original_row_content = $row_content;

				$row_last_pos             = -1;
				$row_position_change      = 0;
				$element_positions        = [];
				$container_column_counter = 0;
				$column_index             = 0;
				$row_index++;
				$element_position_change = 0;
				$last_column_was_full    = false;

				while ( ( $row_last_pos = strpos( $row_content, $needle['column_opening'], $row_last_pos + 1 ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
					$element_positions[] = $row_last_pos;
				}

				$number_of_elements = count( $element_positions );

				// Loop through each column.
				foreach ( $element_positions as $key => $element_position ) {
					$column_index++;

					// Get all parameters from column.
					$end_position = strlen( $row_content ) - 1;
					if ( isset( $element_position[ $key + 1 ] ) ) {
						$end_position = $element_position[ $key + 1 ];
					}

					$column_values = shortcode_parse_atts( strstr( substr( $row_content, $element_position + $column_shortcode_name_length, $end_position ), ']', true ) );

					// Check that type parameter is found, if so calculate row and set spacing to array.
					if ( isset( $column_values['type'] ) ) {
						$column_type               = explode( '_', $column_values['type'] );
						$column_width              = intval( $column_type[0] ) / intval( $column_type[1] );
						$container_column_counter += $column_width;
						$column_spacing            = ( isset( $column_values['spacing'] ) ) ? $column_values['spacing'] : '4%';

						// First column.
						if ( 0 === $key ) {
							if ( 0 < $row_index && ! empty( $column_array[ $row_index - 1 ] ) ) {
								// Get column index of last column of last row.
								end( $column_array[ $row_index - 1 ] );
								$previous_row_last_column = key( $column_array[ $row_index - 1 ] );

								// Add "last" to the last column of previous row.
								if ( false !== strpos( $column_array[ $row_index - 1 ][ $previous_row_last_column ][1], 'first' ) ) {
									$column_array[ $row_index - 1 ][ $previous_row_last_column ] = [ 'no', 'first_last' ];
								} else {
									$column_array[ $row_index - 1 ][ $previous_row_last_column ] = [ 'no', 'last' ];
								}
							}

							// If column is full width it is automatically first and last of row.
							if ( 1 === $column_width ) {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index ][ $column_index ] = [ $column_spacing, 'first' ];
							}
						} elseif ( 0 === $container_column_counter - $column_width ) { // First column of a row.
							if ( 1 === $column_width ) {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index ][ $column_index ] = [ $column_spacing, 'first' ];
							}
						} elseif ( 1 === $container_column_counter ) { // Column fills remaining space in the row exactly.
							// If column is full width it is automatically first and last of row.
							if ( 1 === $column_width ) {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'last' ];
							}
						} elseif ( 1 < $container_column_counter ) { // Column overflows the current row.
							$container_column_counter = $column_width;
							$row_index++;

							// Get column index of last column of last row.
							end( $column_array[ $row_index - 1 ] );
							$previous_row_last_column = key( $column_array[ $row_index - 1 ] );

							// Add "last" to the last column of previous row.
							if ( false !== strpos( $column_array[ $row_index - 1 ][ $previous_row_last_column ][1], 'first' ) ) {
								$column_array[ $row_index - 1 ][ $previous_row_last_column ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index - 1 ][ $previous_row_last_column ] = [ 'no', 'last' ];
							}

							// If column is full width it is automatically first and last of row.
							if ( 1 === $column_width ) {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index ][ $column_index ] = [ $column_spacing, 'first' ];
							}
						} elseif ( $number_of_elements - 1 === $key ) { // Last column.
							// If column is full width it is automatically first and last of row.
							if ( 1 === $column_width ) {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'first_last' ];
							} else {
								$column_array[ $row_index ][ $column_index ] = [ 'no', 'last' ];
							}
						} else {
							$column_array[ $row_index ][ $column_index ] = [ $column_spacing, 'default' ];
						}
					}

					if ( '[fusion_builder_column ' === $needle['column_opening'] ) {
						$global_column_array[ $content_id ] = $column_array;
					}
					if ( '[fusion_builder_column_inner ' === $needle['column_opening'] ) {
						$global_column_inner_array[ $content_id ] = $column_array;
					}

					$column_opening_positions_index[] = [ $position + $element_position + $row_shortcode_name_length + $column_shortcode_name_length, $row_index . '_' . $column_index ];

				}
			}
		}

		/*
		 * Make sure columns and inner columns are sorted correctly for index insertion.
		 * Use the start index on shortcode in the content string as order value.
		 */
		usort( $column_opening_positions_index, [ $this, 'column_opening_positions_index_substract' ] );

		// Add column index and if in widget also the widget ID to the column shortcodes.
		foreach ( array_reverse( $column_opening_positions_index ) as $position ) {
			if ( $is_in_widget ) {
				$content = substr_replace( $content, 'row_column_index="' . $position[1] . '" widget_id="' . $widget->id . '" ', $position[0], 0 );
			} else {
				$content = substr_replace( $content, 'row_column_index="' . $position[1] . '" ', $position[0], 0 );
			}
		}

		return $content;
	}

	/**
	 * Fixes line break issue for shortcodes in widgets.
	 *
	 * @access public
	 * @since  1.2
	 * @param  string $widget_instance The widget Instance.
	 * @param  string $widget          The widget.
	 * @param  Array  $args            The Args.
	 * @return $instance
	 */
	public function fusion_disable_wpautop_in_widgets( $widget_instance, $widget, $args ) {
		if ( isset( $widget_instance['text'] ) && false !== strpos( $widget_instance['text'], '[fusion_' ) ) {
			remove_filter( 'widget_text_content', 'wpautop' );
		}
		return $widget_instance;
	}

	/**
	 * Fixes image src issue for URLs with dashes.
	 *
	 * @access public
	 * @since  1.4
	 * @param  Array $shortcodes    Array of shortcodes to exempt.
	 * @return $shortcodes
	 */
	public function exempt_from_wptexturize( $shortcodes ) {
		$shortcodes[] = 'fusion_imageframe';
		return $shortcodes;
	}

	/**
	 * Helper function that substracts values.
	 * Added for compatibility with older PHP versions.
	 *
	 * @access public
	 * @since 1.0.3
	 * @param array $a 1st value.
	 * @param array $b 2nd value.
	 * @return int
	 */
	public function column_opening_positions_index_substract( $a, $b ) {
		return $a[0] - $b[0];
	}

	/**
	 * Add shortcode styles in dynamic-css.
	 *
	 * @access public
	 * @since 1.1.5
	 * @param string $original_styles The compiled styles.
	 * @return string The compiled styles with the new ones appended.
	 */
	public function shortcode_styles_dynamic_css( $original_styles ) {

		$fusion_settings = fusion_get_fusion_settings();
		$dynamic_css_obj = Fusion_Dynamic_CSS::get_instance();
		$styles          = '';
		$is_builder      = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

		// Stylesheet ID: fusion-builder-shortcodes.
		$styles .= file_get_contents( FUSION_BUILDER_PLUGIN_DIR . 'css/fusion-shortcodes.min.css' );

		// Stylesheet ID: fusion-builder-animations.
		if ( 'off' !== fusion_library()->get_option( 'status_css_animations' ) || $is_builder ) {
			$styles .= file_get_contents( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/animations.min.css' );
		}

		// Stylesheet ID: fusion-builder-ilightbox.
		if ( fusion_library()->get_option( 'status_lightbox' ) ) {
			$ilightbox_styles = file_get_contents( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/ilightbox.min.css' );
			$ilightbox_url    = set_url_scheme( FUSION_BUILDER_PLUGIN_URL . 'assets/images/' );
			$styles          .= str_replace( 'url(../../assets/images/', 'url(' . $ilightbox_url, $ilightbox_styles );
		}

		$replacement_patterns = Fusion_Dynamic_CSS::get_replacement_patterns();
		if ( ! empty( $replacement_patterns ) ) {
			$styles = str_replace( array_keys( $replacement_patterns ), array_values( $replacement_patterns ), $styles );
		}

		return $original_styles . $styles;
	}

	/**
	 * Shortcode Scripts & Styles.
	 * Registers the FB library scripts used as dependency.
	 *
	 * @access public
	 * @since 1.1
	 * @return void
	 */
	public function register_scripts() {

		$fusion_settings = fusion_get_fusion_settings();
		$is_builder      = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

		if ( 'off' !== fusion_library()->get_option( 'status_css_animations' ) || $is_builder ) {
			Fusion_Dynamic_JS::register_script(
				'fusion-animations',
				self::$js_folder_url . '/general/fusion-animations.js',
				self::$js_folder_path . '/general/fusion-animations.js',
				[ 'jquery', 'cssua', 'fusion-waypoints' ],
				'1',
				true
			);
		}
		Fusion_Dynamic_JS::localize_script(
			'fusion-animations',
			'fusionAnimationsVars',
			[
				'status_css_animations' => $fusion_settings->get( 'status_css_animations' ),
			]
		);
		Fusion_Dynamic_JS::register_script(
			'jquery-count-to',
			self::$js_folder_url . '/library/jquery.countTo.js',
			self::$js_folder_path . '/library/jquery.countTo.js',
			[ 'jquery' ],
			'1',
			true
		);
		Fusion_Dynamic_JS::register_script(
			'jquery-count-down',
			self::$js_folder_url . '/library/jquery.countdown.js',
			self::$js_folder_path . '/library/jquery.countdown.js',
			[ 'jquery' ],
			'1.0',
			true
		);
		Fusion_Dynamic_JS::localize_script(
			'fusion-video',
			'fusionVideoVars',
			[
				'status_vimeo' => $fusion_settings->get( 'status_vimeo' ),
			]
		);
		$fusion_video_dependencies = [ 'jquery', 'fusion-video-general' ];
		if ( $fusion_settings->get( 'status_vimeo' ) ) {
			$fusion_video_dependencies = [ 'jquery', 'vimeo-player', 'fusion-video-general' ];
		}
		Fusion_Dynamic_JS::register_script(
			'fusion-video',
			self::$js_folder_url . '/general/fusion-video.js',
			self::$js_folder_path . '/general/fusion-video.js',
			$fusion_video_dependencies,
			'1',
			true
		);
		Fusion_Dynamic_JS::register_script(
			'fusion-chartjs',
			self::$js_folder_url . '/library/Chart.js',
			self::$js_folder_path . '/library/Chart.js',
			[],
			'2.7.1',
			true
		);
	}

	/**
	 * Admin Scripts.
	 * Enqueues all necessary scripts in the WP Admin to run Fusion Builder.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $hook Not used in the context of this function.
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		global $typenow, $fusion_builder_elements, $fusion_builder_multi_elements, $pagenow, $fusion_settings;

		// Load Fusion builder importer js.
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'fusion-builder-settings' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_script( 'fusion_builder_importer_js', FUSION_BUILDER_PLUGIN_URL . 'inc/importer/js/fusion-builder-importer.js', '', FUSION_BUILDER_VERSION, true );

			// Localize Scripts.
			wp_localize_script(
				'fusion_builder_importer_js',
				'fusionBuilderConfig',
				[
					'ajaxurl'             => admin_url( 'admin-ajax.php' ),
					'fusion_import_nonce' => wp_create_nonce( 'fusion_import_nonce' ),
				]
			);
		}

		// Load icons if Avada is not installed / active.
		if ( ! class_exists( 'Avada' ) ) {
			wp_enqueue_style( 'fusion-font-icomoon', FUSION_LIBRARY_URL . '/assets/fonts/icomoon-admin/icomoon.css', false, FUSION_BUILDER_VERSION, 'all' );
		}

		if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && post_type_supports( $typenow, 'editor' ) ) {

			// TODO: has to be loaded for shortcode generator to work. Even if FB is disabled for this post type.
			/* if ( is_admin() && isset( $typenow ) && in_array( $typenow, self::allowed_post_types(), true ) ) { */

			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'jquery-ui-button' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'underscore' );
			wp_enqueue_script( 'backbone' );
			wp_enqueue_script( 'jquery-color' );

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
				wp_enqueue_script( 'fusion-builder-codemirror-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/codemirror.js', [ 'jquery' ], FUSION_BUILDER_VERSION, true );
			}
			wp_enqueue_style( 'fusion-builder-codemirror-css', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/codemirror/codemirror.css', [], FUSION_BUILDER_VERSION, 'all' );

			// WP Editor.
			wp_enqueue_script( 'fusion-builder-wp-editor-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/wpeditor/wp-editor.js', [ 'jquery' ], FUSION_BUILDER_VERSION, true );

			// ColorPicker Alpha Channel.
			wp_enqueue_script( 'wp-color-picker-alpha', FUSION_LIBRARY_URL . '/inc/redux/custom-fields/color_alpha/wp-color-picker-alpha.js', [ 'wp-color-picker', 'jquery-color' ], FUSION_BUILDER_VERSION, false );

			// Bootstrap date and time picker.
			wp_enqueue_script( 'bootstrap-datetimepicker', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/datetimepicker/bootstrap-datetimepicker-back.min.js', [ 'jquery' ], FUSION_BUILDER_VERSION, false );
			wp_enqueue_style( 'bootstrap-datetimepicker', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/css/bootstrap-datetimepicker-back.css', [], '5.0.0', 'all' );

			// The noUi Slider.
			wp_enqueue_style( 'avadaredux-nouislider-css', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/css/nouislider.css', [], '5.0.0', 'all' );

			wp_enqueue_script( 'avadaredux-nouislider-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/nouislider/nouislider.min.js', [ 'jquery' ], '8.5.1', true );

			wp_enqueue_script( 'wnumb-js', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/wNumb.js', [ 'jquery' ], '1.0.2', true );

			// FontAwesome.
			wp_enqueue_style( 'fontawesome', Fusion_Font_Awesome::get_backend_css_url(), [], FUSION_BUILDER_VERSION );

			if ( '1' === $fusion_settings->get( 'fontawesome_v4_compatibility' ) ) {
				wp_enqueue_script( 'fontawesome-shim-script', FUSION_BUILDER_PLUGIN_URL . 'inc/lib/assets/fonts/fontawesome/js/fa-v4-shims.js', [], FUSION_BUILDER_VERSION, false );

				wp_enqueue_style( 'fontawesome-shims', Fusion_Font_Awesome::get_backend_shims_css_url(), [], FUSION_BUILDER_VERSION );
			}

			if ( '1' === $fusion_settings->get( 'status_fontawesome_pro' ) ) {
				wp_enqueue_script( 'fontawesome-search-script', FUSION_LIBRARY_URL . '/assets/fonts/fontawesome/js/icons-search-pro.js', [], FUSION_BUILDER_VERSION, false );
			} else {
				wp_enqueue_script( 'fontawesome-search-script', FUSION_LIBRARY_URL . '/assets/fonts/fontawesome/js/icons-search-free.js', [], FUSION_BUILDER_VERSION, false );
			}
			wp_enqueue_script( 'fuse-script', FUSION_LIBRARY_URL . '/assets/min/js/library/fuse.js', [], FUSION_BUILDER_VERSION, false );

			// Icomoon font.
			wp_enqueue_style( 'fusion-font-icomoon', FUSION_LIBRARY_URL . '/assets/fonts/icomoon-admin/icomoon.css', false, FUSION_BUILDER_VERSION, 'all' );

			// Select2 js & css.
			wp_enqueue_script( 'select2', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/select2/js/select2.full.min.js', [ 'jquery' ], FUSION_BUILDER_VERSION, true );
			wp_enqueue_style( 'select2', FUSION_LIBRARY_URL . '/inc/fusion-app/assets/js/select2/css/select2.min.css', [], FUSION_BUILDER_VERSION );

			$fb_template_type = false;
			if ( 'fusion_tb_section' === get_post_type() ) {

				// Layout Section category is used to filter components.
				$terms = get_the_terms( get_the_ID(), 'fusion_tb_category' );

				if ( is_array( $terms ) ) {
					$fb_template_type = $terms[0]->name;
				}
			}

			// Assets model for webfonts.
			wp_enqueue_script( 'fusion_app_assets', FUSION_LIBRARY_URL . '/inc/fusion-app/model-assets.js', [], FUSION_BUILDER_VERSION, true );

			// Developer mode is enabled.
			if ( true === FUSION_BUILDER_DEV_MODE ) {

				// Utility for underscore.js templates.
				wp_enqueue_script( 'fusion_builder_app_util_js', FUSION_LIBRARY_URL . '/inc/fusion-app/util.js', [ 'jquery', 'jquery-ui-core', 'underscore', 'backbone' ], FUSION_BUILDER_VERSION, true );

				// Sticky builder header.
				wp_enqueue_script( 'fusion-sticky-header', FUSION_BUILDER_PLUGIN_URL . 'js/sticky-menu.js', [ 'jquery', 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, false );

				// Backbone Models.
				wp_enqueue_script( 'fusion_builder_model_element', FUSION_BUILDER_PLUGIN_URL . 'js/models/model-element.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_model_view_manager', FUSION_BUILDER_PLUGIN_URL . 'js/models/model-view-manager.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_dynamic_values', FUSION_BUILDER_PLUGIN_URL . 'js/models/model-dynamic-values.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_dynamic_params', FUSION_BUILDER_PLUGIN_URL . 'js/models/model-dynamic-params.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// Backbone Element Collection.
				wp_enqueue_script( 'fusion_builder_collection_element', FUSION_BUILDER_PLUGIN_URL . 'js/collections/collection-element.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// Backbone Views.
				wp_enqueue_script( 'fusion_builder_view_element', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-element.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_model_view_element_preview', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-element-preview.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_elements_library', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-elements-library.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_generator_elements', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-generator-elements.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_container', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-container.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_blank_page', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-blank-page.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_row', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-row.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_row_nested', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-row-nested.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_column_nested_library', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-nested-column-library.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_column_nested', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-column-nested.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_column', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-column.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_modal', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-modal.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_next_page', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-next-page.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_context_menu', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-context-menu.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_element_settings', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-element-settings.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_multi_element_child_settings', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-multi-element-child-settings.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_widget_settings', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-base-widget-settings.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_multi_element_ui', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-multi-element-sortable-ui.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_multi_element_child_ui', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-multi-element-sortable-child.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_view_column_library', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-column-library.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// Backbone App.
				wp_enqueue_script( 'fusion_builder_app_js', FUSION_BUILDER_PLUGIN_URL . 'js/app.js', [ 'jquery', 'jquery-ui-core', 'underscore', 'backbone', 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// Shortcode Generator.
				wp_enqueue_script( 'fusion_builder_sc_generator', FUSION_BUILDER_PLUGIN_URL . 'js/fusion-shortcode-generator.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// History.
				wp_enqueue_script( 'fusion_builder_history', FUSION_BUILDER_PLUGIN_URL . 'js/fusion-history.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_dynamic_selection', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-dynamic-selection.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				wp_enqueue_script( 'fusion_builder_dynamic_data', FUSION_BUILDER_PLUGIN_URL . 'js/views/view-dynamic-data.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, true );

				// Localize Scripts.
				wp_localize_script(
					'fusion_builder_app_js',
					'fusionBuilderConfig',
					[
						'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
						'admin_url'                 => admin_url(),
						'fusion_load_nonce'         => wp_create_nonce( 'fusion_load_nonce' ),
						'fontawesomeicons'          => fusion_get_icons_array(),
						'fontawesomesubsets'        => fusion_library()->get_option( 'status_fontawesome' ),
						'customIcons'               => fusion_get_custom_icons_array(),
						'fusion_builder_plugin_dir' => FUSION_BUILDER_PLUGIN_URL,
						'includes_url'              => includes_url(),
						'disable_encoding'          => get_option( 'avada_disable_encoding' ),
						'full_width'                => apply_filters( 'fusion_builder_width_hundred_percent', '' ),
						'widget_element_enabled'    => fusion_is_element_enabled( 'fusion_widget' ),
						'template_category'         => $fb_template_type,
					]
				);

				// Localize scripts. Text strings.
				wp_localize_script( 'fusion_builder_app_js', 'fusionBuilderText', fusion_app_textdomain_strings() );

				wp_localize_script(
					'fusion_builder',
					'fusionAppConfig',
					[
						'includes_url' => includes_url(),
					]
				);

				// Developer mode is disabled.
			} else {

				// Fusion Builder js.
				wp_enqueue_script(
					'fusion_builder',
					FUSION_BUILDER_PLUGIN_URL . 'js/fusion-builder.js',
					[ 'jquery', 'jquery-ui-core', 'underscore', 'backbone' ],
					FUSION_BUILDER_VERSION,
					true
				);

				// Localize Script.
				wp_localize_script(
					'fusion_builder',
					'fusionBuilderConfig',
					[
						'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
						'fusion_load_nonce'         => wp_create_nonce( 'fusion_load_nonce' ),
						'fontawesomeicons'          => fusion_get_icons_array(),
						'fontawesomesubsets'        => fusion_library()->get_option( 'status_fontawesome' ),
						'customIcons'               => fusion_get_custom_icons_array(),
						'fusion_builder_plugin_dir' => FUSION_BUILDER_PLUGIN_URL,
						'includes_url'              => includes_url(),
						'disable_encoding'          => get_option( 'avada_disable_encoding' ),
						'full_width'                => apply_filters( 'fusion_builder_width_hundred_percent', '' ),
						'widget_element_enabled'    => fusion_is_element_enabled( 'fusion_widget' ),
						'template_category'         => $fb_template_type,
					]
				);

				// Localize script. Text strings.
				wp_localize_script( 'fusion_builder', 'fusionBuilderText', fusion_app_textdomain_strings() );

				wp_localize_script(
					'fusion_builder',
					'fusionAppConfig',
					[
						'includes_url' => includes_url(),
					]
				);

			}

			// Builder Styling.
			wp_enqueue_style( 'fusion_builder_css', FUSION_BUILDER_PLUGIN_URL . 'css/fusion-builder.css', [], FUSION_BUILDER_VERSION );

			// Elements Preview.
			wp_enqueue_style( 'fusion_element_preview_css', FUSION_BUILDER_PLUGIN_URL . 'css/elements-preview.css', [], FUSION_BUILDER_VERSION );

			// Filter disabled elements.
			$fusion_builder_elements = fusion_builder_filter_available_elements();

			// Create elements js object. Load element's js and css.
			if ( ! empty( $fusion_builder_elements ) ) {

				$fusion_builder_elements = apply_filters( 'fusion_builder_all_elements', $fusion_builder_elements );

				echo '<script>var fusionAllElements = ' . wp_json_encode( $fusion_builder_elements ) . ';</script>';

				// Load modules backend js and css.
				foreach ( $fusion_builder_elements as $module ) {
					// JS file.
					if ( ! empty( $module['admin_enqueue_js'] ) ) {
						wp_enqueue_script( $module['shortcode'], $module['admin_enqueue_js'], '', FUSION_BUILDER_VERSION, true );
					}

					// CSS file.
					if ( ! empty( $module['admin_enqueue_css'] ) ) {
						wp_enqueue_style( $module['shortcode'], $module['admin_enqueue_css'], [], FUSION_BUILDER_VERSION );
					}

					// Preview template.
					if ( ! empty( $module['preview'] ) ) {
						require_once wp_normalize_path( $module['preview'] );
					}

					// Custom settings template.
					if ( ! empty( $module['custom_settings_template_file'] ) ) {
						require_once wp_normalize_path( $module['custom_settings_template_file'] );
					}
					// Custom settings view.
					if ( ! empty( $module['custom_settings_view_js'] ) ) {
						wp_enqueue_script( $module['shortcode'] . '_custom_settings_view', $module['custom_settings_view_js'], '', FUSION_BUILDER_VERSION, true );
					}
				}
			}

			// Multi Element object.
			if ( ! empty( $fusion_builder_multi_elements ) ) {
				echo '<script>var fusionMultiElements = ' . wp_json_encode( $fusion_builder_multi_elements ) . ';</script>';
			}

			// Builder admin scripts hook.
			do_action( 'fusion_builder_admin_scripts_hook' );

			/* } */
		}
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function includes() {

		// Helper functions.
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/helpers.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-options.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-dynamic-css.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-template-builder.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-gutenberg.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-dynamic-data.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-elements-dynamic-css.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-element.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-component.php';

		Fusion_Builder_Options::get_instance();
		new Fusion_Elements_Dynamic_CSS();

		$this->fusion_builder_dynamic_css = new Fusion_Builder_Dynamic_CSS();

		$this->fusion_builder_gutenberg = new Fusion_Builder_Gutenberg();

		$this->dynamic_data = new Fusion_Dynamic_Data();

		// Load globals media vars.
		$this->init_global_vars();

		do_action( 'fusion_builder_shortcodes_init' );

		// Load all shortcode elements.
		$this->init_shortcodes();
		// Shortcode related functions.
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/shortcodes.php';

		// Page layouts.
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-library.php';

		if ( is_admin() ) {
			// Importer/Exporter.
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/importer/importer.php';
			// Builder underscores templates.
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/templates.php';
			// Settings.
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-admin.php';
		}
		if ( is_admin() || is_customize_preview() ) {
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-options-panel.php';
			// Fusion Library.
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-builder-library-table.php';
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-template-builder-table.php';
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-custom-icons-table.php';
			$this->fusion_builder_options_panel = new Fusion_Builder_Options_Panel();
		}

		// WooCommerce.
		if ( class_exists( 'WooCommerce' ) ) {
			require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/woocommerce/class-fusionbuilder-woocommerce.php';
		}
	}

	/**
	 * Fusion Builder wrapper.
	 *
	 * @access public
	 * @since 1.0
	 * @param object $post The post.
	 */
	public function before_main_editor( $post ) {
		global $typenow;

		if ( isset( $typenow ) && in_array( $typenow, self::allowed_post_types(), true ) ) {

			$builder_active = 'active' === get_post_meta( $post->ID, 'fusion_builder_status', true ) ? true : false;
			$live_editor    = apply_filters( 'fusion_load_live_editor', true );

			$builder_enabled_data = '';
			$builder_settings     = get_option( 'fusion_builder_settings' );
			if ( ( isset( $builder_settings['enable_builder_ui_by_default'] ) && $builder_settings['enable_builder_ui_by_default'] && 'active' !== get_post_meta( $post->ID, 'fusion_builder_status', true ) ) || ( 'fusion_element' === $typenow && 'active' !== get_post_meta( $post->ID, 'fusion_builder_status', true ) ) ) {
				$builder_enabled_data = ' data-enabled="1"';
			}

			$editor_label   = ( $builder_active ) ? esc_attr__( 'Default Editor', 'fusion-builder' ) : esc_attr__( 'Fusion Builder', 'fusion-builder' );
			$builder_hidden = ( $builder_active ) ? 'fusion_builder_hidden' : '';
			$builder_active = ( $builder_active ) ? ' fusion_builder_is_active' : ' fusiona-FB_logo_black button-primary';

			echo '<div class="fusion-builder-toggle-buttons">';
			echo '<a href="#" id="fusion_toggle_builder" data-builder="' . esc_attr__( 'Fusion Builder', 'fusion-builder' ) . '" data-editor="' . esc_attr__( 'Default Editor', 'fusion-builder' ) . '"' . $builder_enabled_data . ' class="fusiona-FB_logo_black button button-large' . $builder_active . '"><span class="fusion-builder-button-text">' . $editor_label . '</span></a>';  // phpcs:ignore WordPress.Security.EscapeOutput

			if ( $live_editor ) {
				$builder_link = add_query_arg( 'fb-edit', '1', get_the_permalink( $post->ID ) );
				echo '<a id="fusion_toggle_front_end" href="' . esc_url( $builder_link ) . '" class="fusiona-FB_logo_black button button-primary button-large" target=""><span class="fusion-builder-button-text">' . esc_attr__( 'Fusion Builder Live', 'fusion-builder' ) . '</span></a>';
			}

			echo '</div>';
			echo '<div id="fusion_main_editor_wrap" class="' . esc_attr( $builder_hidden ) . '">';
		}
	}

	/**
	 * Fusion Builder wrapper.
	 *
	 * @package Fusion Builder
	 * @author Theme Fusion
	 */
	public function after_main_editor() {
		global $typenow;

		if ( isset( $typenow ) && in_array( $typenow, self::allowed_post_types(), true ) ) {
			echo '</div>';
		}
	}

	/**
	 * Default post types.
	 *
	 * @package Fusion Builder
	 * @author Theme Fusion
	 * @since 1.0
	 */
	public static function default_post_types() {

		// Allow theme developers to change default selection via filter.  Can also do so for Avada.
		return apply_filters(
			'fusion_builder_default_post_types',
			[
				'page',
				'post',
				'avada_faq',
				'avada_portfolio',
				'fusion_template',
				'fusion_element',
				'fusion_tb_section',
				'fusion_tb_layout',
			]
		);
	}

	/**
	 * Builder is displayed on the following post types.
	 *
	 * @package Fusion Builder
	 * @author Theme Fusion
	 */
	public static function allowed_post_types() {

		if ( ! empty( self::$allowed_post_types ) ) {
			return self::$allowed_post_types;
		}

		$options = get_option( 'fusion_builder_settings', [] );

		self::$allowed_post_types = self::default_post_types();
		if ( ! empty( $options ) && isset( $options['post_types'] ) ) {
			// If there are options saved, use them.
			$post_types = ( ' ' === $options['post_types'] ) ? [] : $options['post_types'];
			// Add defaults to allowed post types ( bc ).
			$post_types[]             = 'fusion_element';
			$post_types[]             = 'fusion_tb_section';
			self::$allowed_post_types = apply_filters( 'fusion_builder_allowed_post_types', $post_types );
		}
		return self::$allowed_post_types;
	}

	/**
	 * Add Page Builder MetaBox.
	 *
	 * @since 1.0
	 * @param  string $post_type  Post type slug.
	 * @return void
	 */
	public function add_builder_meta_box( $post_type ) {
		if ( post_type_supports( $post_type, 'editor' ) ) {
			add_meta_box( 'fusion_builder_layout', '<span class="fusion-builder-logo"></span><span class="fusion-builder-title">' . esc_attr__( 'Fusion Builder', 'fusion-builder' ) . '</span><a href="https://theme-fusion.com/documentation/fusion-builder/" target="_blank" rel="noopener noreferrer"><span class="fusion-builder-help dashicons dashicons-editor-help"></span></a>', 'fusion_pagebuilder_meta_box', null, 'normal', 'high' );
		}
	}

	/**
	 * Resets the meta box priority for Yoast SEO.
	 * Devs can override by using fusion_builder_yoast_meta_box_priority filter.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The meta box priority.
	 */
	public function set_yoast_meta_box_priority() {
		return apply_filters( 'fusion_builder_yoast_meta_box_priority', 'default' );
	}

	/**
	 * Function to apply attributes to HTML tags.
	 * Devs can override attributes in a child theme by using the correct slug.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param  string $slug    Slug to refer to the HTML tag.
	 * @param  array  $attributes Attributes for HTML tag.
	 * @return string The string of all attributes.
	 */
	public static function attributes( $slug, $attributes = [] ) {

		$out  = '';
		$attr = apply_filters( "fusion_attr_{$slug}", $attributes );

		if ( empty( $attr ) ) {
			$attr['class'] = $slug;
		}

		foreach ( $attr as $name => $value ) {
			if ( 'valueless_attribute' === $value ) {
				$out .= ' ' . esc_html( $name );
			} elseif ( ! empty( $value ) || strlen( $value ) > 0 || is_bool( $value ) ) {
				$value = str_replace( '  ', ' ', $value );
				$out  .= ' ' . esc_html( $name ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return trim( $out );
	}

	/**
	 * Function to get the default shortcode param values applied.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param  array  $defaults  Array of defaults.
	 * @param  array  $args      Array with user set param values.
	 * @param  string $shortcode Shortcode name.
	 * @return array
	 */
	public static function set_shortcode_defaults( $defaults, $args, $shortcode = false ) {

		if ( ! $args ) {
			$args = [];
		}

		$args = apply_filters( 'fusion_pre_shortcode_atts', $args, $defaults, $args, $shortcode );
		$args = shortcode_atts( $defaults, $args, $shortcode );

		foreach ( $args as $key => $value ) {
			if ( ( '' === $value || '|' === $value ) && isset( $defaults[ $key ] ) ) {
				$args[ $key ] = $defaults[ $key ];
			}
		}

		return $args;
	}

	/**
	 * Returns an array with the rgb values.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param string $hex The HEX color.
	 * @return array
	 */
	public static function hex2rgb( $hex ) {
		$hex = str_replace( '#', '', $hex );

		if ( 3 === strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}
		return [ $r, $g, $b ];
	}

	/**
	 * Function to return animation classes for shortcodes mainly.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param  array $args Animation type, direction and speed.
	 * @return array       Array with data attributes.
	 */
	public static function animations( $args = [] ) {
		$defaults = [
			'type'      => '',
			'direction' => 'left',
			'speed'     => '0.1',
			'offset'    => 'bottom-in-view',
		];

		$args = wp_parse_args( $args, $defaults );

		$animation_attribues = [];

		if ( $args['type'] ) {

			$animation_attribues['animation_class'] = 'fusion-animated';

			if ( 'static' === $args['direction'] ) {
				$args['direction'] = '';
			}

			if ( ! in_array( $args['type'], [ 'bounce', 'flash', 'shake', 'rubberBand' ], true ) ) {
				$direction_suffix = 'In' . ucfirst( $args['direction'] );
				$args['type']    .= $direction_suffix;
			}

			$animation_attribues['data-animationType'] = $args['type'];

			if ( $args['speed'] ) {
				$animation_attribues['data-animationDuration'] = $args['speed'];
			}
		}

		if ( $args['offset'] ) {
			$offset = $args['offset'];
			if ( 'top-into-view' === $args['offset'] ) {
				$offset = '100%';
			} elseif ( 'top-mid-of-view' === $args['offset'] ) {
				$offset = '50%';
			}
			$animation_attribues['data-animationOffset'] = $offset;
		}

		return $animation_attribues;
	}

	/**
	 * Strips the unit from a given value.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param  string $value The value with or without unit.
	 * @param  string $unit_to_strip The unit to be stripped.
	 * @return string   the value without a unit.
	 */
	public static function strip_unit( $value, $unit_to_strip = 'px' ) {
		$value_length = strlen( $value );
		$unit_length  = strlen( $unit_to_strip );

		if ( $value_length > $unit_length && 0 === substr_compare( $value, $unit_to_strip, $unit_length * ( -1 ), $unit_length ) ) {
			return substr( $value, 0, $value_length - $unit_length );
		}
		return $value;
	}

	/**
	 * Get the regular expression to parse a single shortcode.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param string $tagname Not used in the context of this function.
	 * @return string
	 */
	public static function get_shortcode_regex( $tagname ) {
		return '/\\['                              // Opening bracket.
			. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
			. "($tagname)"                     // 2: Shortcode name.
			. '(?![\\w-])'                       // Not followed by word character or hyphen.
			. '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
			. '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
			. '(?:'
			. '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
			. '[^\\]\\/]*'               // Not a closing bracket or forward slash.
			. ')*?'
			. ')'
			. '(?:'
			. '(\\/)'                        // 4: Self closing tag...
			. '\\]'                          // ...and closing bracket.
			. '|'
			. '\\]'                          // Closing bracket.
			. '(?:'
			. '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
			. '[^\\[]*+'             // Not an opening bracket.
			. '(?:'
			. '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag.
			. '[^\\[]*+'         // Not an opening bracket.
			. ')*+'
			. ')'
			. '\\[\\/\\2\\]'             // Closing shortcode tag.
			. ')?'
			. ')'
			. '(\\]?)/';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]].
	}

	/**
	 * Get Registered Sidebars.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public static function fusion_get_sidebars() {
		global $wp_registered_sidebars;

		$sidebars = [];

		foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
			$name                    = $sidebar['name'];
			$sidebars[ $sidebar_id ] = $name;
		}

		return $sidebars;
	}

	/**
	 * Validate shortcode attribute value.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 * @param string $value         The value.
	 * @param string $accepted_unit The accepted unit.
	 * @param string $bc_support    Return value even if invalid.
	 * @return value
	 */
	public static function validate_shortcode_attr_value( $value, $accepted_unit, $bc_support = true ) {

		if ( '' !== $value ) {
			$value           = trim( $value );
			$unit            = preg_replace( '/[\d-]+/', '', $value );
			$numerical_value = preg_replace( '/[a-z,%]/', '', $value );

			if ( empty( $accepted_unit ) ) {
				return $numerical_value;
			}

			// Add unit if it's required.
			if ( empty( $unit ) ) {
				return $numerical_value . $accepted_unit;
			}

			// If unit was found use original value. BC support.
			if ( $bc_support || $unit === $accepted_unit ) {
				return $value;
			}

			return false;
		}

		return '';
	}

	/**
	 * Adds the options in the Fusion_Settings class.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function add_options_to_fusion_settings() {

		if ( ! function_exists( 'fusion_builder_settings' ) ) {
			require_once wp_normalize_path( 'inc/class-fusion-builder-options.php' );
		}

	}

	/**
	 * Gets the value of a page option.
	 *
	 * @static
	 * @access public
	 * @param  string  $theme_option Theme option ID.
	 * @param  string  $page_option  Page option ID.
	 * @param  integer $post_id      Post/Page ID.
	 * @since  1.0.1
	 * @return string                Theme option or page option value.
	 */
	public static function get_page_option( $theme_option, $page_option, $post_id ) {

		$value = '';

		// If Avada is installed, use it to get the theme-option.
		if ( class_exists( 'Avada' ) ) {
			$value = fusion_get_option( $theme_option, $page_option, $post_id );
		}

		return apply_filters( 'fusion_builder_get_page_option', $value );

	}

	/**
	 * Checks if we're in the migration page.
	 * It does that by checking _GET, and then sets the $is_updating property.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function set_is_updating() {
		if ( ! self::$is_updating && $_GET && isset( $_GET['avada_update'] ) && '1' == $_GET['avada_update'] ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.PHP.StrictComparisons.LooseComparison
			self::$is_updating = true;
		}
	}

	/**
	 * Checks if we're editing Fusion Library element.
	 *
	 * @access public
	 * @since 1.5.2
	 * @param array $classes An array of body classes.
	 * @return array
	 */
	public function admin_body_class( $classes ) {
		global $post, $typenow;

		if ( 'fusion_element' === $typenow && $post ) {
			$terms    = get_the_terms( $post->ID, 'element_category' );
			$classes .= ' fusion-builder-library-edit';

			if ( $terms ) {
				$classes .= ' fusion-element-post-type-' . $terms[0]->name . ' ';
			}
		}

		if ( 'fusion_tb_section' === $typenow && $post ) {
			$terms    = get_the_terms( $post->ID, 'fusion_tb_category' );
			$classes .= ' fusion-tb-section-edit';

			if ( $terms ) {
				$classes .= ' fusion-tb-category-' . $terms[0]->name . ' ';
			}
		}

		return $classes;
	}

	/**
	 * Adds extra classes for the <body> element, using the 'body_class' filter.
	 * Documentation: https://codex.wordpress.org/Plugin_API/Filter_Reference/body_class
	 *
	 * @since 1.1
	 * @param  array $classes CSS classes.
	 * @return array The merged and extended body classes.
	 */
	public function body_class_filter( $classes ) {
		$this->set_body_classes();
		return array_merge( $classes, $this->body_classes );
	}

	/**
	 * Calculate any extra classes for the <body> element.
	 *
	 * @return array The needed body classes.
	 */
	public function set_body_classes() {
		$classes   = [];
		$classes[] = 'fusion-image-hovers';

		if ( fusion_get_option( 'pagination_sizing' ) ) {
			$classes[] = 'fusion-pagination-sizing';
		}

		$classes[] = 'fusion-button_size-' . strtolower( fusion_get_option( 'button_size' ) );
		$classes[] = 'fusion-button_type-' . strtolower( fusion_get_option( 'button_type' ) );
		$classes[] = 'fusion-button_span-' . strtolower( fusion_get_option( 'button_span' ) );

		if ( fusion_get_option( 'icon_circle_image_rollover' ) ) {
			$classes[] = 'avada-image-rollover-circle-yes';
		} else {
			$classes[] = 'avada-image-rollover-circle-no';
		}
		if ( fusion_get_option( 'image_rollover' ) ) {
			$classes[] = 'avada-image-rollover-yes';
			$classes[] = 'avada-image-rollover-direction-' . fusion_get_option( 'image_rollover_direction' );
		} else {
			$classes[] = 'avada-image-rollover-no';
		}

		if ( fusion_get_option( 'button_gradient_top_color' ) !== fusion_get_option( 'button_gradient_bottom_color' ) ) {
			$classes[] = 'fusion-has-button-gradient';
		}

		return $this->body_classes = $classes;
	}

	/**
	 * Gets the fusion_builder_options_panel private property.
	 *
	 * @access public
	 * @since 1.1.0
	 * @return object
	 */
	public function get_fusion_builder_options_panel() {
		return $this->fusion_builder_options_panel;
	}

	/**
	 * Compares db and plugin versions and does stuff if needed.
	 *
	 * @access private
	 * @since 1.1.2
	 */
	private function versions_compare() {

		$db_version = get_option( 'fusion_builder_version', false );
		if ( ! $db_version || FUSION_BUILDER_VERSION !== $db_version ) {

			// Reset caches.
			$fusion_cache = new Fusion_Cache();
			$fusion_cache->reset_all_caches();

			// Update version in the database.
			update_option( 'fusion_builder_version', FUSION_BUILDER_VERSION );
		}
	}

	/**
	 * Compares db and plugin versions and does stuff if needed.
	 *
	 * @since 1.2.1
	 * @access private
	 * @param array $links The array of action links.
	 * @return Array The $links array plus the added settings link.
	 */
	public function add_action_settings_link( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=fusion-builder-settings' ) . '">' . esc_html__( 'Settings', 'fusion-builder' ) . '</a>';

		return $links;
	}

	/**
	 * Return post types to exclude from events calendar.
	 *
	 * @since 1.3.0
	 * @access public
	 * @param array $all_post_types All allowed post types in events calendar.
	 * @return array
	 */
	public function fusion_builder_exclude_post_type( $all_post_types ) {

		unset( $all_post_types['fusion_template'] );
		unset( $all_post_types['fusion_element'] );

		return $all_post_types;
	}

	/**
	 * Adds media-query styles.
	 *
	 * @access public
	 * @since 6.0.0
	 */
	public function add_media_query_styles() {

		if ( fusion_get_fusion_settings()->get( 'responsive' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-max-sh-cbp',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/max-sh-cbp.min.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-min-768-max-1024-p',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/min-768-max-1024-p.min.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-768-max-1024-p' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-max-640',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/max-640.min.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-max-1c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/max-1c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-1c' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-max-2c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/max-2c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-2c' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-min-2c-max-3c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/min-2c-max-3c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-2c-max-3c' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-min-3c-max-4c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/min-3c-max-4c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-3c-max-4c' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-min-4c-max-5c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/min-4c-max-5c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-4c-max-5c' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'fb-min-5c-max-6c',
				FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/min-5c-max-6c.css',
				[],
				FUSION_BUILDER_VERSION,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-5c-max-6c' ),
			];
		}
	}

	/**
	 * Add styles for element visibility.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function add_element_visibility_styles() {
		global $wp_version, $content_media_query, $six_fourty_media_query, $three_twenty_six_fourty_media_query, $ipad_portrait_media_query, $content_min_media_query, $small_media_query, $medium_media_query, $large_media_query, $six_columns_media_query, $five_columns_media_query, $four_columns_media_query, $three_columns_media_query, $two_columns_media_query, $one_column_media_query;

		echo '<style type="text/css" id="css-fb-visibility">';
		echo wp_strip_all_tags( $small_media_query ) . '{body:not(.fusion-builder-ui-wireframe) .fusion-no-small-visibility{display:none !important;}}'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo wp_strip_all_tags( $medium_media_query ) . '{body:not(.fusion-builder-ui-wireframe) .fusion-no-medium-visibility{display:none !important;}}'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo wp_strip_all_tags( $large_media_query ) . '{body:not(.fusion-builder-ui-wireframe) .fusion-no-large-visibility{display:none !important;}}'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</style>';
	}

	/**
	 * Setup the element option decsription map.
	 *
	 * @static
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public static function set_element_description_map() {
		$element_option_map = apply_filters( 'fusion_builder_map_descriptions', [] );

		// Audio.
		$element_option_map['controls_color_scheme']['fusion_audio'] = [
			'theme-option' => 'audio_controls_color_scheme',
			'type'         => 'select',
		];
		$element_option_map['progress_color']['fusion_audio']        = [
			'theme-option' => 'audio_progressbar_color',
			'reset'        => true,
		];
		$element_option_map['background_color']['fusion_audio']      = [
			'theme-option' => 'audio_background_color',
			'reset'        => true,
		];
		$element_option_map['border_color']['fusion_audio']          = [
			'theme-option' => 'audio_border_color',
			'reset'        => true,
		];
		$element_option_map['max_width']['fusion_audio']             = [
			'theme-option' => 'audio_max_width',
			'type'         => 'select',
		];
		$element_option_map['border_size']['fusion_audio']           = [
			'theme-option' => 'audio_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_radius']['fusion_audio']         = [
			'theme-option' => 'audio_border_radius',
			'subset'       => [ 'top_left', 'top_right', 'bottom_right', 'bottom_left' ],
		];

		// Alert.
		$element_option_map['text_align']['fusion_alert']     = [
			'theme-option' => 'alert_box_text_align',
			'type'         => 'select',
		];
		$element_option_map['text_transform']['fusion_alert'] = [
			'theme-option' => 'alert_box_text_transform',
			'type'         => 'select',
		];
		$element_option_map['dismissable']['fusion_alert']    = [
			'theme-option' => 'alert_box_dismissable',
			'type'         => 'select',
		];
		$element_option_map['box_shadow']['fusion_alert']     = [
			'theme-option' => 'alert_box_shadow',
			'type'         => 'select',
		];
		$element_option_map['border_size']['fusion_alert']    = [
			'theme-option' => 'alert_border_size',
			'type'         => 'range',
		];

		// Blog.
		$element_option_map['blog_grid_columns']['fusion_blog']         = [
			'theme-option' => 'blog_grid_columns',
			'type'         => 'range',
		];
		$element_option_map['blog_grid_column_spacing']['fusion_blog']  = [
			'theme-option' => 'blog_grid_column_spacing',
			'type'         => 'range',
		];
		$element_option_map['grid_box_color']['fusion_blog']            = [
			'theme-option' => 'timeline_bg_color',
			'reset'        => true,
		];
		$element_option_map['grid_element_color']['fusion_blog']        = [
			'theme-option' => 'timeline_color',
			'reset'        => true,
		];
		$element_option_map['grid_separator_style_type']['fusion_blog'] = [
			'theme-option' => 'grid_separator_style_type',
			'type'         => 'select',
		];
		$element_option_map['grid_separator_color']['fusion_blog']      = [
			'theme-option' => 'grid_separator_color',
			'reset'        => true,
		];
		$element_option_map['blog_grid_padding']['fusion_blog']         = [
			'theme-option' => 'blog_grid_padding',
			'subset'       => [ 'top', 'left', 'bottom', 'right' ],
		];
		$element_option_map['excerpt']['fusion_blog']                   = [
			'theme-option' => 'blog_excerpt',
			'type'         => 'select',
		];
		$element_option_map['excerpt_length']['fusion_blog']            = [
			'theme-option' => 'blog_excerpt_length',
			'type'         => 'range',
			'reset'        => true,
		];
		$element_option_map['blog_masonry_grid_ratio']['fusion_blog']   = [
			'theme-option' => 'masonry_grid_ratio',
			'type'         => 'range',
		];
		$element_option_map['blog_masonry_width_double']['fusion_blog'] = [
			'theme-option' => 'masonry_width_double',
			'type'         => 'range',
		];

		// Breadcrumbs.
		$element_option_map['prefix']['fusion_breadcrumbs']            = [ 'theme-option' => 'breacrumb_prefix' ];
		$element_option_map['separator']['fusion_breadcrumbs']         = [ 'theme-option' => 'breadcrumb_separator' ];
		$element_option_map['font_size']['fusion_breadcrumbs']         = [ 'theme-option' => 'breadcrumbs_font_size' ];
		$element_option_map['text_color']['fusion_breadcrumbs']        = [
			'theme-option' => 'breadcrumbs_text_color',
			'reset'        => true,
		];
		$element_option_map['text_hover_color']['fusion_breadcrumbs']  = [
			'theme-option' => 'breadcrumbs_text_hover_color',
			'reset'        => true,
		];
		$element_option_map['show_categories']['fusion_breadcrumbs']   = [
			'theme-option' => 'breadcrumb_show_categories',
			'type'         => 'yesno',
		];
		$element_option_map['post_type_archive']['fusion_breadcrumbs'] = [
			'theme-option' => 'breadcrumb_show_post_type_archive',
			'type'         => 'yesno',
		];
		$element_option_map['bold_last']['fusion_breadcrumbs']         = [
			'theme-option' => 'breadcrumb_bold_last_item',
			'type'         => 'yesno',
		];

		// Button.
		$element_option_map['size']['fusion_button']                               = [
			'theme-option' => 'button_size',
			'type'         => 'select',
		];
		$element_option_map['stretch']['fusion_button']                            = [
			'theme-option' => 'button_span',
			'type'         => 'select',
		];
		$element_option_map['type']['fusion_button']                               = [
			'theme-option' => 'button_type',
			'type'         => 'select',
		];
		$element_option_map['button_gradient_top_color']['fusion_button']          = [
			'theme-option' => 'button_gradient_top_color',
			'reset'        => true,
		];
		$element_option_map['button_gradient_bottom_color']['fusion_button']       = [
			'theme-option' => 'button_gradient_bottom_color',
			'reset'        => true,
		];
		$element_option_map['button_gradient_top_color_hover']['fusion_button']    = [
			'theme-option' => 'button_gradient_top_color_hover',
			'reset'        => true,
		];
		$element_option_map['button_gradient_bottom_color_hover']['fusion_button'] = [
			'theme-option' => 'button_gradient_bottom_color_hover',
			'reset'        => true,
		];
		$element_option_map['accent_color']['fusion_button']                       = [
			'theme-option' => 'button_accent_color',
			'reset'        => true,
		];
		$element_option_map['accent_hover_color']['fusion_button']                 = [
			'theme-option' => 'button_accent_hover_color',
			'reset'        => true,
		];
		$element_option_map['bevel_color']['fusion_button']                        = [
			'theme-option' => 'button_bevel_color',
			'reset'        => true,
		];
		$element_option_map['border_color']['fusion_button']                       = [
			'theme-option' => 'button_border_color',
			'reset'        => true,
		];
		$element_option_map['border_hover_color']['fusion_button']                 = [
			'theme-option' => 'button_border_hover_color',
			'reset'        => true,
		];
		$element_option_map['border_width']['fusion_button']                       = [
			'theme-option' => 'button_border_width',
			'type'         => 'range',
		];
		$element_option_map['border_radius']['fusion_button']                      = [
			'theme-option' => 'button_border_radius',
			'type'         => 'range',
		];
		$element_option_map['text_transform']['fusion_button']                     = [
			'theme-option' => 'button_text_transform',
			'type'         => 'select',
		];

		$element_option_map['button_fullwidth']['fusion_login']           = [
			'theme-option' => 'button_span',
			'type'         => 'yesno',
		];
		$element_option_map['button_fullwidth']['fusion_register']        = [
			'theme-option' => 'button_span',
			'type'         => 'yesno',
		];
		$element_option_map['button_fullwidth']['fusion_lost_password']   = [
			'theme-option' => 'button_span',
			'type'         => 'yesno',
		];
		$element_option_map['button_size']['fusion_tagline_box']          = [
			'theme-option' => 'button_size',
			'type'         => 'select',
		];
		$element_option_map['button_type']['fusion_tagline_box']          = [
			'theme-option' => 'button_type',
			'type'         => 'select',
		];
		$element_option_map['button_border_radius']['fusion_tagline_box'] = [
			'theme-option' => 'button_border_radius',
			'reset'        => true,
		];

		// Checklist.
		$element_option_map['iconcolor']['fusion_checklist']     = [
			'theme-option' => 'checklist_icons_color',
			'reset'        => true,
		];
		$element_option_map['circle']['fusion_checklist']        = [
			'theme-option' => 'checklist_circle',
			'type'         => 'yesno',
		];
		$element_option_map['circlecolor']['fusion_checklist']   = [
			'theme-option' => 'checklist_circle_color',
			'reset'        => true,
		];
		$element_option_map['divider']['fusion_checklist']       = [
			'theme-option' => 'checklist_divider',
			'type'         => 'select',
		];
		$element_option_map['divider_color']['fusion_checklist'] = [
			'theme-option' => 'checklist_divider_color',
			'reset'        => true,
		];
		$element_option_map['size']['fusion_checklist']          = [
			'theme-option' => 'checklist_item_size',
		];

		// Columns.
		$element_option_map['dimension_margin']['fusion_builder_column']       = [
			'theme-option' => 'col_margin',
			'subset'       => [ 'top', 'bottom' ],
		];
		$element_option_map['dimension_margin']['fusion_builder_column_inner'] = [
			'theme-option' => 'col_margin',
			'subset'       => [ 'top', 'bottom' ],
		];

		// Container.
		$element_option_map['background_color']['fusion_builder_container']     = [
			'theme-option' => 'full_width_bg_color',
			'reset'        => true,
		];
		$element_option_map['gradient_start_color']['fusion_builder_container'] = [
			'theme-option' => 'full_width_gradient_start_color',
			'reset'        => true,
		];
		$element_option_map['gradient_end_color']['fusion_builder_container']   = [
			'theme-option' => 'full_width_gradient_end_color',
			'reset'        => true,
		];
		$element_option_map['border_size']['fusion_builder_container']          = [
			'theme-option' => 'full_width_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_builder_container']         = [
			'theme-option' => 'full_width_border_color',
			'reset'        => true,
		];
		$element_option_map['link_color']['fusion_builder_container']           = [
			'theme-option' => 'link_color',
			'reset'        => true,
		];
		$element_option_map['link_hover_color']['fusion_builder_container']     = [
			'theme-option' => 'primary_color',
			'reset'        => true,
		];

		// Content Box.
		$element_option_map['backgroundcolor']['fusion_content_boxes']        = [
			'theme-option' => 'content_box_bg_color',
			'reset'        => true,
		];
		$element_option_map['title_size']['fusion_content_boxes']             = [ 'theme-option' => 'content_box_title_size' ];
		$element_option_map['title_color']['fusion_content_boxes']            = [
			'theme-option' => 'content_box_title_color',
			'reset'        => true,
		];
		$element_option_map['body_color']['fusion_content_boxes']             = [
			'theme-option' => 'content_box_body_color',
			'reset'        => true,
		];
		$element_option_map['icon_size']['fusion_content_boxes']              = [
			'theme-option' => 'content_box_icon_size',
			'reset'        => true,
		];
		$element_option_map['iconcolor']['fusion_content_boxes']              = [
			'theme-option' => 'content_box_icon_color',
			'reset'        => true,
		];
		$element_option_map['icon_circle']['fusion_content_boxes']            = [
			'theme-option' => 'content_box_icon_circle',
			'type'         => 'select',
		];
		$element_option_map['icon_circle_radius']['fusion_content_boxes']     = [ 'theme-option' => 'content_box_icon_circle_radius' ];
		$element_option_map['circlecolor']['fusion_content_boxes']            = [
			'theme-option' => 'content_box_icon_bg_color',
			'reset'        => true,
		];
		$element_option_map['circlebordercolor']['fusion_content_boxes']      = [
			'theme-option' => 'content_box_icon_bg_inner_border_color',
			'reset'        => true,
		];
		$element_option_map['outercirclebordercolor']['fusion_content_boxes'] = [
			'theme-option' => 'content_box_icon_bg_outer_border_color',
			'reset'        => true,
		];
		$element_option_map['circlebordersize']['fusion_content_boxes']       = [
			'theme-option' => 'content_box_icon_bg_inner_border_size',
			'type'         => 'range',
		];
		$element_option_map['outercirclebordersize']['fusion_content_boxes']  = [
			'theme-option' => 'content_box_icon_bg_outer_border_size',
			'type'         => 'range',
		];
		$element_option_map['icon_hover_type']['fusion_content_boxes']        = [
			'theme-option' => 'content_box_icon_hover_type',
			'type'         => 'select',
		];
		$element_option_map['button_span']['fusion_content_boxes']            = [
			'theme-option' => 'content_box_button_span',
			'reset'        => true,
			'type'         => 'select',
		];
		$element_option_map['hover_accent_color']['fusion_content_boxes']     = [
			'theme-option' => 'content_box_hover_animation_accent_color',
			'reset'        => true,
		];
		$element_option_map['link_type']['fusion_content_boxes']              = [
			'theme-option' => 'content_box_link_type',
			'type'         => 'select',
		];
		$element_option_map['link_area']['fusion_content_boxes']              = [
			'theme-option' => 'content_box_link_area',
			'type'         => 'select',
		];
		$element_option_map['link_target']['fusion_content_boxes']            = [
			'theme-option' => 'content_box_link_target',
			'type'         => 'select',
		];
		$element_option_map['margin_top']['fusion_content_boxes']             = [
			'theme-option' => 'content_box_margin',
			'subset'       => 'top',
		];
		$element_option_map['margin_bottom']['fusion_content_boxes']          = [
			'theme-option' => 'content_box_margin',
			'subset'       => 'bottom',
		];
		$element_option_map['backgroundcolor']['fusion_content_box']          = [
			'theme-option' => 'content_box_bg_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['iconcolor']['fusion_content_box']                = [
			'theme-option' => 'content_box_icon_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['icon_circle_radius']['fusion_content_box']       = [
			'theme-option' => 'content_box_icon_circle_radius',
			'type'         => 'child',
		];
		$element_option_map['circlecolor']['fusion_content_box']              = [
			'theme-option' => 'content_box_icon_bg_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['circlebordercolor']['fusion_content_box']        = [
			'theme-option' => 'content_box_icon_bg_inner_border_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['outercirclebordercolor']['fusion_content_box']   = [
			'theme-option' => 'content_box_icon_bg_outer_border_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['circlebordersize']['fusion_content_box']         = [
			'theme-option' => 'content_box_icon_bg_inner_border_size',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['outercirclebordersize']['fusion_content_box']    = [
			'theme-option' => 'content_box_icon_bg_outer_border_size',
			'type'         => 'child',
			'reset'        => true,
		];

		// Countdown.
		$element_option_map['timezone']['fusion_countdown']              = [
			'theme-option' => 'countdown_timezone',
			'type'         => 'select',
		];
		$element_option_map['layout']['fusion_countdown']                = [
			'theme-option' => 'countdown_layout',
			'type'         => 'select',
		];
		$element_option_map['show_weeks']['fusion_countdown']            = [
			'theme-option' => 'countdown_show_weeks',
			'type'         => 'select',
		];
		$element_option_map['label_position']['fusion_countdown']        = [
			'theme-option' => 'countdown_label_position',
			'type'         => 'select',
		];
		$element_option_map['background_color']['fusion_countdown']      = [
			'theme-option' => 'countdown_background_color',
			'reset'        => true,
		];
		$element_option_map['background_image']['fusion_countdown']      = [
			'theme-option' => 'countdown_background_image',
			'subset'       => 'thumbnail',
		];
		$element_option_map['background_repeat']['fusion_countdown']     = [
			'theme-option' => 'countdown_background_repeat',
		];
		$element_option_map['background_position']['fusion_countdown']   = [
			'theme-option' => 'countdown_background_position',
		];
		$element_option_map['counter_box_spacing']['fusion_countdown']   = [
			'theme-option' => 'countdown_counter_box_spacing',
		];
		$element_option_map['counter_box_color']['fusion_countdown']     = [
			'theme-option' => 'countdown_counter_box_color',
			'reset'        => true,
		];
		$element_option_map['counter_padding']['fusion_countdown']       = [
			'theme-option' => 'countdown_counter_padding',
			'subset'       => [ 'top', 'right', 'bottom', 'left' ],
		];
		$element_option_map['counter_border_size']['fusion_countdown']   = [
			'theme-option' => 'countdown_counter_border_size',
			'type'         => 'range',
		];
		$element_option_map['counter_border_color']['fusion_countdown']  = [
			'theme-option' => 'countdown_counter_border_color',
			'reset'        => true,
		];
		$element_option_map['counter_border_radius']['fusion_countdown'] = [
			'theme-option' => 'countdown_counter_border_radius',
		];
		$element_option_map['counter_font_size']['fusion_countdown']     = [
			'theme-option' => 'countdown_counter_font_size',
		];
		$element_option_map['counter_text_color']['fusion_countdown']    = [
			'theme-option' => 'countdown_counter_text_color',
			'reset'        => true,
		];
		$element_option_map['label_font_size']['fusion_countdown']       = [
			'theme-option' => 'countdown_label_font_size',
		];
		$element_option_map['label_color']['fusion_countdown']           = [
			'theme-option' => 'countdown_label_color',
			'reset'        => true,
		];
		$element_option_map['heading_font_size']['fusion_countdown']     = [
			'theme-option' => 'countdown_heading_font_size',
		];
		$element_option_map['heading_text_color']['fusion_countdown']    = [
			'theme-option' => 'countdown_heading_text_color',
			'reset'        => true,
		];
		$element_option_map['subheading_font_size']['fusion_countdown']  = [
			'theme-option' => 'countdown_subheading_font_size',
		];
		$element_option_map['subheading_text_color']['fusion_countdown'] = [
			'theme-option' => 'countdown_subheading_text_color',
			'reset'        => true,
		];
		$element_option_map['link_text_color']['fusion_countdown']       = [
			'theme-option' => 'countdown_link_text_color',
			'reset'        => true,
		];
		$element_option_map['link_target']['fusion_countdown']           = [
			'theme-option' => 'countdown_link_target',
			'type'         => 'select',
		];

		// Counter box.
		$element_option_map['color']['fusion_counters_box']        = [
			'theme-option' => 'counter_box_color',
			'reset'        => true,
		];
		$element_option_map['title_size']['fusion_counters_box']   = [
			'theme-option' => 'counter_box_title_size',
			'reset'        => true,
			'type'         => 'range',
		];
		$element_option_map['icon_size']['fusion_counters_box']    = [
			'theme-option' => 'counter_box_icon_size',
			'reset'        => true,
			'type'         => 'range',
		];
		$element_option_map['body_color']['fusion_counters_box']   = [
			'theme-option' => 'counter_box_body_color',
			'reset'        => true,
		];
		$element_option_map['body_size']['fusion_counters_box']    = [
			'theme-option' => 'counter_box_body_size',
			'reset'        => true,
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_counters_box'] = [
			'theme-option' => 'counter_box_border_color',
			'reset'        => true,
		];
		$element_option_map['icon_top']['fusion_counters_box']     = [
			'theme-option' => 'counter_box_icon_top',
			'type'         => 'yesno',
		];

		// TB Comments.
		$element_option_map['border_size']['fusion_tb_comments']  = [
			'theme-option' => 'separator_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_tb_comments'] = [
			'theme-option' => 'sep_color',
			'reset'        => true,
		];

		// Counter Circle.
		$element_option_map['filledcolor']['fusion_counter_circle']   = [
			'theme-option' => 'counter_filled_color',
			'reset'        => true,
		];
		$element_option_map['unfilledcolor']['fusion_counter_circle'] = [
			'theme-option' => 'counter_unfilled_color',
			'reset'        => true,
		];

		// Dropcap.
		$element_option_map['color']['fusion_dropcap'] = [
			'theme-option' => 'dropcap_color',
			'shortcode'    => 'fusion_dropcap',
			'reset'        => true,
		];

		// Events.
		$element_option_map['number_posts']['fusion_events'] = [
			'theme-option' => 'events_per_page',
			'type'         => 'range',
		];

		$element_option_map['column_spacing']['fusion_events'] = [
			'theme-option' => 'events_column_spacing',
			'type'         => 'range',
		];

		$element_option_map['content_padding']['fusion_events'] = [
			'theme-option' => 'events_content_padding',
			'subset'       => [ 'top', 'left', 'bottom', 'right' ],
		];

		$element_option_map['content_length']['fusion_events'] = [
			'theme-option' => 'events_content_length',
			'type'         => 'select',
		];

		$element_option_map['excerpt_length']['fusion_events'] = [
			'theme-option' => 'excerpt_length_events',
			'type'         => 'range',
		];

		$element_option_map['strip_html']['fusion_events'] = [
			'theme-option' => 'events_strip_html_excerpt',
			'type'         => 'yesno',
		];

		// Flipboxes.
		$element_option_map['flip_direction']['fusion_flip_boxes'] = [
			'theme-option' => 'flip_boxes_flip_direction',
			'type'         => 'select',
		];
		$element_option_map['flip_direction']['fusion_flip_box']   = [
			'theme-option' => 'flip_boxes_flip_direction',
			'type'         => 'child',
		];
		$element_option_map['flip_effect']['fusion_flip_boxes']    = [
			'theme-option' => 'flip_boxes_flip_effect',
			'type'         => 'select',
		];
		$element_option_map['flip_duration']['fusion_flip_boxes']  = [
			'theme-option' => 'flip_boxes_flip_duration',
			'type'         => 'range',
		];
		$element_option_map['equal_heights']['fusion_flip_boxes']  = [
			'theme-option' => 'flip_boxes_equal_heights',
			'type'         => 'select',
		];

		$element_option_map['icon_color']['fusion_flip_boxes']           = [
			'theme-option' => 'icon_color',
			'reset'        => true,
		];
		$element_option_map['circle_color']['fusion_flip_boxes']         = [
			'theme-option' => 'icon_circle_color',
			'reset'        => true,
		];
		$element_option_map['circle_border_color']['fusion_flip_boxes']  = [
			'theme-option' => 'icon_border_color',
			'reset'        => true,
		];
		$element_option_map['background_color_front']['fusion_flip_box'] = [
			'theme-option' => 'flip_boxes_front_bg',
			'reset'        => true,
		];
		$element_option_map['title_front_color']['fusion_flip_box']      = [
			'theme-option' => 'flip_boxes_front_heading',
			'reset'        => true,
		];
		$element_option_map['text_front_color']['fusion_flip_box']       = [
			'theme-option' => 'flip_boxes_front_text',
			'reset'        => true,
		];
		$element_option_map['background_color_back']['fusion_flip_box']  = [
			'theme-option' => 'flip_boxes_back_bg',
			'reset'        => true,
		];
		$element_option_map['title_back_color']['fusion_flip_box']       = [
			'theme-option' => 'flip_boxes_back_heading',
			'reset'        => true,
		];
		$element_option_map['text_back_color']['fusion_flip_box']        = [
			'theme-option' => 'flip_boxes_back_text',
			'reset'        => true,
		];
		$element_option_map['border_size']['fusion_flip_box']            = [
			'theme-option' => 'flip_boxes_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_flip_box']           = [ 'theme-option' => 'flip_boxes_border_color' ];
		$element_option_map['border_radius']['fusion_flip_box']          = [ 'theme-option' => 'flip_boxes_border_radius' ];
		$element_option_map['circle_color']['fusion_flip_box']           = [
			'theme-option' => 'icon_circle_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['circle_border_color']['fusion_flip_box']    = [
			'theme-option' => 'icon_border_color',
			'type'         => 'child',
			'reset'        => true,
		];
		$element_option_map['icon_color']['fusion_flip_box']             = [
			'theme-option' => 'icon_color',
			'type'         => 'child',
			'reset'        => true,
		];

		// Google Map element.
		$element_option_map['api_type']['fusion_map'] = [
			'theme-option' => 'google_map_api_type',
			'type'         => 'select',
		];

		// Icon Element.
		$element_option_map['size']['fusion_fontawesome']                    = [
			'theme-option' => 'icon_size',
			'reset'        => true,
		];
		$element_option_map['circle']['fusion_fontawesome']                  = [
			'theme-option' => 'icon_circle',
			'reset'        => 'yesno',
		];
		$element_option_map['circlecolor']['fusion_fontawesome']             = [
			'theme-option' => 'icon_circle_color',
			'reset'        => true,
		];
		$element_option_map['circlecolor_hover']['fusion_fontawesome']       = [
			'theme-option' => 'icon_circle_color_hover',
			'reset'        => true,
		];
		$element_option_map['circlebordersize']['fusion_fontawesome']        = [
			'theme-option' => 'icon_border_size',
			'reset'        => true,
		];
		$element_option_map['circlebordercolor']['fusion_fontawesome']       = [
			'theme-option' => 'icon_border_color',
			'reset'        => true,
		];
		$element_option_map['circlebordercolor_hover']['fusion_fontawesome'] = [
			'theme-option' => 'icon_border_color_hover',
			'reset'        => true,
		];
		$element_option_map['iconcolor']['fusion_fontawesome']               = [
			'theme-option' => 'icon_color',
			'reset'        => true,
		];
		$element_option_map['iconcolor_hover']['fusion_fontawesome']         = [
			'theme-option' => 'icon_color_hover',
			'reset'        => true,
		];
		$element_option_map['icon_hover_type']['fusion_fontawesome']         = [
			'theme-option' => 'icon_hover_type',
			'type'         => 'select',
		];

		// Image.
		$element_option_map['style_type']['fusion_imageframe'] = [ 'theme-option' => 'imageframe_style_type' ];

		$element_option_map['blur']['fusion_imageframe']         = [
			'theme-option' => 'imageframe_blur',
			'type'         => 'range',
		];
		$element_option_map['stylecolor']['fusion_imageframe']   = [
			'theme-option' => 'imgframe_style_color',
			'reset'        => true,
		];
		$element_option_map['bordersize']['fusion_imageframe']   = [
			'theme-option' => 'imageframe_border_size',
			'type'         => 'range',
		];
		$element_option_map['bordercolor']['fusion_imageframe']  = [
			'theme-option' => 'imgframe_border_color',
			'reset'        => true,
		];
		$element_option_map['borderradius']['fusion_imageframe'] = [ 'theme-option' => 'imageframe_border_radius' ];

		$element_option_map['lightbox']['fusion_imageframe'] = [
			'theme-option' => 'status_lightbox',
			'type'         => 'yesno',
		];

		// Image Compare.
		$element_option_map['type']['fusion_image_before_after']            = [
			'theme-option' => 'before_after_type',
			'type'         => 'select',
		];
		$element_option_map['font_size']['fusion_image_before_after']       = [
			'theme-option' => 'before_after_font_size',
			'type'         => 'range',
		];
		$element_option_map['accent_color']['fusion_image_before_after']    = [
			'theme-option' => 'before_after_accent_color',
			'reset'        => true,
		];
		$element_option_map['label_placement']['fusion_image_before_after'] = [
			'theme-option' => 'before_after_label_placement',
			'type'         => 'select',
		];
		$element_option_map['handle_type']['fusion_image_before_after']     = [
			'theme-option' => 'before_after_handle_type',
			'type'         => 'select',
		];
		$element_option_map['handle_color']['fusion_image_before_after']    = [
			'theme-option' => 'before_after_handle_color',
			'reset'        => true,
		];
		$element_option_map['handle_bg']['fusion_image_before_after']       = [
			'theme-option' => 'before_after_handle_bg',
			'reset'        => true,
		];
		$element_option_map['transition_time']['fusion_image_before_after'] = [
			'theme-option' => 'before_after_transition_time',
			'type'         => 'range',
		];
		$element_option_map['offset']['fusion_image_before_after']          = [
			'theme-option' => 'before_after_offset',
			'type'         => 'range',
		];
		$element_option_map['orientation']['fusion_image_before_after']     = [
			'theme-option' => 'before_after_orientation',
			'type'         => 'select',
		];
		$element_option_map['handle_movement']['fusion_image_before_after'] = [
			'theme-option' => 'before_after_handle_movement',
			'type'         => 'select',
		];

		// Modal.
		$element_option_map['background']['fusion_modal']   = [
			'theme-option' => 'modal_bg_color',
			'reset'        => true,
		];
		$element_option_map['border_color']['fusion_modal'] = [
			'theme-option' => 'modal_border_color',
			'reset'        => true,
		];

		// TB Pagination.
		$element_option_map['border_color']['fusion_tb_pagination']     = [
			'theme-option' => 'sep_color',
			'reset'        => true,
		];
		$element_option_map['font_size']['fusion_tb_pagination']        = [
			'theme-option' => 'body_typography',
			'subset'       => 'font-size',
		];
		$element_option_map['text_color']['fusion_tb_pagination']       = [
			'theme-option' => 'link_color',
			'reset'        => true,
		];
		$element_option_map['text_hover_color']['fusion_tb_pagination'] = [
			'theme-option' => 'primary_color',
			'reset'        => true,
		];

		// Person.
		$element_option_map['background_color']['fusion_person']  = [
			'theme-option' => 'person_background_color',
			'reset'        => true,
		];
		$element_option_map['pic_style']['fusion_person']         = [ 'theme-option' => 'person_pic_style' ];
		$element_option_map['pic_style_blur']['fusion_person']    = [
			'theme-option' => 'person_pic_style_blur',
			'type'         => 'range',
		];
		$element_option_map['pic_style_color']['fusion_person']   = [
			'theme-option' => 'person_style_color',
			'reset'        => true,
		];
		$element_option_map['pic_bordercolor']['fusion_person']   = [
			'theme-option' => 'person_border_color',
			'reset'        => true,
		];
		$element_option_map['pic_bordersize']['fusion_person']    = [
			'theme-option' => 'person_border_size',
			'type'         => 'range',
		];
		$element_option_map['pic_borderradius']['fusion_person']  = [ 'theme-option' => 'person_border_radius' ];
		$element_option_map['pic_style_color']['fusion_person']   = [
			'theme-option' => 'person_style_color',
			'reset'        => true,
		];
		$element_option_map['content_alignment']['fusion_person'] = [
			'theme-option' => 'person_alignment',
			'type'         => 'select',
		];
		$element_option_map['icon_position']['fusion_person']     = [
			'theme-option' => 'person_icon_position',
			'type'         => 'select',
		];

		// Popover.
		$element_option_map['title_bg_color']['fusion_popover']   = [
			'theme-option' => 'popover_heading_bg_color',
			'reset'        => true,
		];
		$element_option_map['content_bg_color']['fusion_popover'] = [
			'theme-option' => 'popover_content_bg_color',
			'reset'        => true,
		];
		$element_option_map['bordercolor']['fusion_popover']      = [
			'theme-option' => 'popover_border_color',
			'reset'        => true,
		];
		$element_option_map['textcolor']['fusion_popover']        = [
			'theme-option' => 'popover_text_color',
			'reset'        => true,
		];
		$element_option_map['placement']['fusion_popover']        = [
			'theme-option' => 'popover_placement',
			'type'         => 'select',
		];

		// Pricing table.
		$element_option_map['backgroundcolor']['fusion_pricing_table']        = [
			'theme-option' => 'pricing_bg_color',
			'reset'        => true,
		];
		$element_option_map['background_color_hover']['fusion_pricing_table'] = [
			'theme-option' => 'pricing_background_color_hover',
			'reset'        => true,
		];
		$element_option_map['bordercolor']['fusion_pricing_table']            = [
			'theme-option' => 'pricing_border_color',
			'reset'        => true,
		];
		$element_option_map['dividercolor']['fusion_pricing_table']           = [
			'theme-option' => 'pricing_divider_color',
			'reset'        => true,
		];
		$element_option_map['heading_color_style_1']['fusion_pricing_table']  = [
			'theme-option' => 'full_boxed_pricing_box_heading_color',
			'reset'        => true,
		];
		$element_option_map['heading_color_style_2']['fusion_pricing_table']  = [
			'theme-option' => 'sep_pricing_box_heading_color',
			'reset'        => true,
		];
		$element_option_map['pricing_color']['fusion_pricing_table']          = [
			'theme-option' => 'pricing_box_color',
			'reset'        => true,
		];
		$element_option_map['body_text_color']['fusion_pricing_table']        = [
			'theme-option' => 'body_typography',
			'reset'        => true,
			'subset'       => 'color',
		];

		// Progress bar.
		$element_option_map['height']['fusion_progress']            = [ 'theme-option' => 'progressbar_height' ];
		$element_option_map['text_position']['fusion_progress']     = [
			'theme-option' => 'progressbar_text_position',
			'type'         => 'select',
		];
		$element_option_map['filledcolor']['fusion_progress']       = [
			'theme-option' => 'progressbar_filled_color',
			'reset'        => true,
		];
		$element_option_map['filledbordercolor']['fusion_progress'] = [
			'theme-option' => 'progressbar_filled_border_color',
			'reset'        => true,
		];
		$element_option_map['filledbordersize']['fusion_progress']  = [
			'theme-option' => 'progressbar_filled_border_size',
			'type'         => 'range',
		];
		$element_option_map['unfilledcolor']['fusion_progress']     = [
			'theme-option' => 'progressbar_unfilled_color',
			'reset'        => true,
		];
		$element_option_map['textcolor']['fusion_progress']         = [
			'theme-option' => 'progressbar_text_color',
			'reset'        => true,
		];

		// Section Separator.
		$element_option_map['backgroundcolor']['fusion_section_separator'] = [
			'theme-option' => 'section_sep_bg',
			'reset'        => true,
		];
		$element_option_map['bordersize']['fusion_section_separator']      = [
			'theme-option' => 'section_sep_border_size',
			'type'         => 'range',
		];
		$element_option_map['bordercolor']['fusion_section_separator']     = [
			'theme-option' => 'section_sep_border_color',
			'reset'        => true,
		];
		$element_option_map['icon_color']['fusion_section_separator']      = [
			'theme-option' => 'icon_color',
			'reset'        => true,
		];

		// Separator.
		$element_option_map['border_size']['fusion_separator']       = [
			'theme-option' => 'separator_border_size',
			'type'         => 'range',
		];
		$element_option_map['icon_size']['fusion_separator']         = [
			'theme-option' => 'separator_icon_size',
			'type'         => 'range',
		];
		$element_option_map['icon_circle']['fusion_separator']       = [
			'theme-option' => 'separator_circle',
			'type'         => 'yesno',
		];
		$element_option_map['icon_circle_color']['fusion_separator'] = [
			'theme-option' => 'separator_circle_bg_color',
			'reset'        => true,
		];
		$element_option_map['sep_color']['fusion_separator']         = [
			'theme-option' => 'sep_color',
			'reset'        => true,
		];
		$element_option_map['style_type']['fusion_separator']        = [
			'theme-option' => 'separator_style_type',
			'type'         => 'select',
		];

		// Search.
		$element_option_map['design']['fusion_search']                      = [
			'theme-option' => 'search_form_design',
			'reset'        => true,
		];
		$element_option_map['live_search']['fusion_search']                 = [
			'theme-option' => 'live_search',
			'type'         => 'yesno',
			'reset'        => true,
		];
		$element_option_map['search_limit_to_post_titles']['fusion_search'] = [
			'theme-option' => 'search_limit_to_post_titles',
			'type'         => 'yesno',
			'reset'        => true,
		];

		// Sharing Box.
		$element_option_map['backgroundcolor']['fusion_sharing']    = [
			'theme-option' => 'social_bg_color',
			'reset'        => true,
		];
		$element_option_map['icons_boxed']['fusion_sharing']        = [
			'theme-option' => 'sharing_social_links_boxed',
			'type'         => 'yesno',
		];
		$element_option_map['icons_boxed_radius']['fusion_sharing'] = [ 'theme-option' => 'sharing_social_links_boxed_radius' ];
		$element_option_map['tagline_color']['fusion_sharing']      = [
			'theme-option' => 'sharing_box_tagline_text_color',
			'reset'        => true,
		];
		$element_option_map['tooltip_placement']['fusion_sharing']  = [
			'theme-option' => 'sharing_social_links_tooltip_placement',
			'type'         => 'select',
		];
		$element_option_map['color_type']['fusion_sharing']         = [
			'theme-option' => 'sharing_social_links_color_type',
			'type'         => 'select',
		];
		$element_option_map['icon_colors']['fusion_sharing']        = [ 'theme-option' => 'sharing_social_links_icon_color' ];
		$element_option_map['box_colors']['fusion_sharing']         = [ 'theme-option' => 'sharing_social_links_box_color' ];

		// Social Icons.
		$element_option_map['color_type']['fusion_social_links']         = [
			'theme-option' => 'social_links_color_type',
			'type'         => 'select',
		];
		$element_option_map['icon_colors']['fusion_social_links']        = [ 'theme-option' => 'social_links_icon_color' ];
		$element_option_map['icons_boxed']['fusion_social_links']        = [
			'theme-option' => 'social_links_boxed',
			'type'         => 'yesno',
		];
		$element_option_map['box_colors']['fusion_social_links']         = [ 'theme-option' => 'social_links_box_color' ];
		$element_option_map['icons_boxed_radius']['fusion_social_links'] = [ 'theme-option' => 'social_links_boxed_radius' ];
		$element_option_map['tooltip_placement']['fusion_social_links']  = [
			'theme-option' => 'social_links_tooltip_placement',
			'type'         => 'select',
		];

		// Social Icons for Person.
		$element_option_map['social_icon_font_size']['fusion_person']    = [ 'theme-option' => 'social_links_font_size' ];
		$element_option_map['social_icon_padding']['fusion_person']      = [ 'theme-option' => 'social_links_boxed_padding' ];
		$element_option_map['social_icon_color_type']['fusion_person']   = [
			'theme-option' => 'social_links_color_type',
			'type'         => 'select',
		];
		$element_option_map['social_icon_colors']['fusion_person']       = [ 'theme-option' => 'social_links_icon_color' ];
		$element_option_map['social_icon_boxed']['fusion_person']        = [
			'theme-option' => 'social_links_boxed',
			'type'         => 'yesno',
		];
		$element_option_map['social_icon_boxed_colors']['fusion_person'] = [ 'theme-option' => 'social_links_box_color' ];
		$element_option_map['social_icon_boxed_radius']['fusion_person'] = [ 'theme-option' => 'social_links_boxed_radius' ];
		$element_option_map['social_icon_tooltip']['fusion_person']      = [
			'theme-option' => 'social_links_tooltip_placement',
			'type'         => 'select',
		];

		// Tabs.
		$element_option_map['backgroundcolor']['fusion_tabs'] = [
			'theme-option' => 'tabs_bg_color',
			'shortcode'    => 'fusion_tabs',
			'reset'        => true,
		];
		$element_option_map['inactivecolor']['fusion_tabs']   = [
			'theme-option' => 'tabs_inactive_color',
			'shortcode'    => 'fusion_tabs',
			'reset'        => true,
		];
		$element_option_map['bordercolor']['fusion_tabs']     = [
			'theme-option' => 'tabs_border_color',
			'shortcode'    => 'fusion_tabs',
			'reset'        => true,
		];
		$element_option_map['icon_position']['fusion_tabs']   = [
			'theme-option' => 'tabs_icon_position',
			'shortcode'    => 'fusion_tabs',
			'type'         => 'select',
		];
		$element_option_map['icon_size']['fusion_tabs']       = [
			'theme-option' => 'tabs_icon_size',
			'shortcode'    => 'fusion_tabs',
			'type'         => 'range',
		];

		// Tagline.
		$element_option_map['backgroundcolor']['fusion_tagline_box'] = [
			'theme-option' => 'tagline_bg',
			'reset'        => true,
		];
		$element_option_map['bordercolor']['fusion_tagline_box']     = [
			'theme-option' => 'tagline_border_color',
			'reset'        => true,
		];
		$element_option_map['margin_top']['fusion_tagline_box']      = [
			'theme-option' => 'tagline_margin',
			'subset'       => 'top',
		];
		$element_option_map['margin_bottom']['fusion_tagline_box']   = [
			'theme-option' => 'tagline_margin',
			'subset'       => 'bottom',
		];

		// Testimonials.
		$element_option_map['speed']['fusion_testimonials']           = [
			'theme-option' => 'testimonials_speed',
			'type'         => 'range',
			'reset'        => true,
		];
		$element_option_map['backgroundcolor']['fusion_testimonials'] = [
			'theme-option' => 'testimonial_bg_color',
			'reset'        => true,
		];
		$element_option_map['textcolor']['fusion_testimonials']       = [
			'theme-option' => 'testimonial_text_color',
			'reset'        => true,
		];
		$element_option_map['random']['fusion_testimonials']          = [
			'theme-option' => 'testimonials_random',
			'type'         => 'yesno',
		];

		// Text.
		$element_option_map['columns']['fusion_text']          = [
			'theme-option' => 'text_columns',
			'type'         => 'range',
		];
		$element_option_map['column_min_width']['fusion_text'] = [
			'theme-option' => 'text_column_min_width',
		];
		$element_option_map['column_spacing']['fusion_text']   = [
			'theme-option' => 'text_column_spacing',
		];
		$element_option_map['rule_style']['fusion_text']       = [
			'theme-option' => 'text_rule_style',
			'type'         => 'select',
		];
		$element_option_map['rule_size']['fusion_text']        = [
			'theme-option' => 'text_rule_size',
			'type'         => 'range',
		];
		$element_option_map['rule_color']['fusion_text']       = [
			'theme-option' => 'text_rule_color',
			'reset'        => true,
		];

		// Title.
		$element_option_map['style_type']['fusion_title']    = [
			'theme-option' => 'title_style_type',
			'type'         => 'select',
		];
		$element_option_map['sep_color']['fusion_title']     = [
			'theme-option' => 'title_border_color',
			'reset'        => true,
		];
		$element_option_map['dimensions']['fusion_title']    = [
			'theme-option' => 'title_margin',
			'subset'       => [ 'top', 'bottom' ],
		];
		$element_option_map['margin_mobile']['fusion_title'] = [
			'theme-option' => 'title_margin_mobile',
			'subset'       => [ 'top', 'bottom' ],
		];

		// Toggles.
		$element_option_map['type']['fusion_accordion']                      = [
			'theme-option' => 'accordion_type',
			'type'         => 'select',
		];
		$element_option_map['divider_line']['fusion_accordion']              = [
			'theme-option' => 'accordion_divider_line',
			'type'         => 'yesno',
		];
		$element_option_map['boxed_mode']['fusion_accordion']                = [
			'theme-option' => 'accordion_boxed_mode',
			'type'         => 'yesno',
		];
		$element_option_map['border_size']['fusion_accordion']               = [
			'theme-option' => 'accordion_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_accordion']              = [
			'theme-option' => 'accordian_border_color',
			'reset'        => true,
		];
		$element_option_map['background_color']['fusion_accordion']          = [
			'theme-option' => 'accordian_background_color',
			'reset'        => true,
		];
		$element_option_map['hover_color']['fusion_accordion']               = [
			'theme-option' => 'accordian_hover_color',
			'reset'        => true,
		];
		$element_option_map['title_font_size']['fusion_accordion']           = [
			'theme-option' => 'accordion_title_font_size',
		];
		$element_option_map['icon_size']['fusion_accordion']                 = [
			'theme-option' => 'accordion_icon_size',
			'type'         => 'range',
		];
		$element_option_map['icon_color']['fusion_accordion']                = [
			'theme-option' => 'accordian_icon_color',
			'reset'        => true,
		];
		$element_option_map['icon_boxed_mode']['fusion_accordion']           = [
			'theme-option' => 'accordion_icon_boxed',
			'type'         => 'yesno',
		];
		$element_option_map['icon_box_color']['fusion_accordion']            = [
			'theme-option' => 'accordian_inactive_color',
			'reset'        => true,
		];
		$element_option_map['icon_alignment']['fusion_accordion']            = [
			'theme-option' => 'accordion_icon_align',
			'type'         => 'select',
		];
		$element_option_map['toggle_hover_accent_color']['fusion_accordion'] = [
			'theme-option' => 'accordian_active_color',
			'reset'        => true,
		];

		// User Login Element.
		$element_option_map['text_align']['fusion_login']            = [
			'theme-option' => 'user_login_text_align',
			'type'         => 'select',
		];
		$element_option_map['form_field_layout']['fusion_login']     = [
			'theme-option' => 'user_login_form_field_layout',
			'type'         => 'select',
		];
		$element_option_map['form_background_color']['fusion_login'] = [
			'theme-option' => 'user_login_form_background_color',
			'reset'        => true,
		];
		$element_option_map['show_labels']['fusion_login']           = [
			'theme-option' => 'user_login_form_show_labels',
			'type'         => 'select',
		];
		$element_option_map['show_placeholders']['fusion_login']     = [
			'theme-option' => 'user_login_form_show_placeholders',
			'type'         => 'select',
		];
		$element_option_map['show_remember_me']['fusion_login']      = [
			'theme-option' => 'user_login_form_show_remember_me',
			'type'         => 'select',
		];

		$element_option_map['text_align']['fusion_register']            = [
			'theme-option' => 'user_login_text_align',
			'type'         => 'select',
		];
		$element_option_map['form_field_layout']['fusion_register']     = [
			'theme-option' => 'user_login_form_field_layout',
			'type'         => 'select',
		];
		$element_option_map['form_background_color']['fusion_register'] = [
			'theme-option' => 'user_login_form_background_color',
			'reset'        => true,
		];
		$element_option_map['show_labels']['fusion_register']           = [
			'theme-option' => 'user_login_form_show_labels',
			'type'         => 'select',
		];
		$element_option_map['show_placeholders']['fusion_register']     = [
			'theme-option' => 'user_login_form_show_placeholders',
			'type'         => 'select',
		];

		$element_option_map['text_align']['fusion_lost_password']            = [
			'theme-option' => 'user_login_text_align',
			'type'         => 'select',
		];
		$element_option_map['form_background_color']['fusion_lost_password'] = [
			'theme-option' => 'user_login_form_background_color',
			'reset'        => true,
		];
		$element_option_map['show_labels']['fusion_lost_password']           = [
			'theme-option' => 'user_login_form_show_labels',
			'type'         => 'select',
		];
		$element_option_map['show_placeholders']['fusion_lost_password']     = [
			'theme-option' => 'user_login_form_show_placeholders',
			'type'         => 'select',
		];
		$element_option_map['link_color']['fusion_login']                    = [ 'theme-option' => 'link_color' ];
		$element_option_map['link_color']['fusion_register']                 = [ 'theme-option' => 'link_color' ];
		$element_option_map['link_color']['fusion_lost_password']            = [ 'theme-option' => 'link_color' ];

		// Widget Area Element.
		$element_option_map['title_color']['fusion_widget_area'] = [
			'theme-option' => 'widget_area_title_color',
			'reset'        => true,
		];
		$element_option_map['title_size']['fusion_widget_area']  = [ 'theme-option' => 'widget_area_title_size' ];

		// Gallery.
		$element_option_map['picture_size']['fusion_gallery']                 = [
			'theme-option' => 'gallery_picture_size',
			'reset'        => true,
			'type'         => 'select',
		];
		$element_option_map['layout']['fusion_gallery']                       = [
			'theme-option' => 'gallery_layout',
			'reset'        => true,
			'type'         => 'select',
		];
		$element_option_map['columns']['fusion_gallery']                      = [
			'theme-option' => 'gallery_columns',
			'reset'        => true,
		];
		$element_option_map['column_spacing']['fusion_gallery']               = [
			'theme-option' => 'gallery_column_spacing',
			'reset'        => true,
		];
		$element_option_map['lightbox_content']['fusion_gallery']             = [
			'theme-option' => 'gallery_lightbox_content',
			'reset'        => true,
			'type'         => 'select',
		];
		$element_option_map['lightbox']['fusion_gallery']                     = [
			'theme-option' => 'status_lightbox',
			'type'         => 'yesno',
		];
		$element_option_map['hover_type']['fusion_gallery']                   = [
			'theme-option' => 'gallery_hover_type',
			'reset'        => true,
			'type'         => 'select',
		];
		$element_option_map['gallery_masonry_grid_ratio']['fusion_gallery']   = [
			'theme-option' => 'masonry_grid_ratio',
			'type'         => 'range',
		];
		$element_option_map['gallery_masonry_width_double']['fusion_gallery'] = [
			'theme-option' => 'masonry_width_double',
			'type'         => 'range',
		];
		$element_option_map['bordersize']['fusion_gallery']                   = [
			'theme-option' => 'gallery_border_size',
			'type'         => 'range',
		];
		$element_option_map['bordercolor']['fusion_gallery']                  = [
			'theme-option' => 'gallery_border_color',
			'reset'        => true,
		];
		$element_option_map['border_radius']['fusion_gallery']                = [
			'theme-option' => 'gallery_border_radius',
		];

		// Image Carousel.
		$element_option_map['lightbox']['fusion_images'] = [
			'theme-option' => 'status_lightbox',
			'type'         => 'yesno',
		];

		// Slide.
		$element_option_map['lightbox']['fusion_slide'] = [
			'theme-option' => 'status_lightbox',
			'type'         => 'yesno',
		];

		// Post Slider.
		$element_option_map['lightbox']['fusion_postslider'] = [
			'theme-option' => 'status_lightbox',
			'type'         => 'yesno',
		];

		// Syntax Highlighter.
		$element_option_map['theme']['fusion_syntax_highlighter']                        = [
			'theme-option' => 'syntax_highlighter_theme',
			'type'         => 'select',
		];
		$element_option_map['line_numbers']['fusion_syntax_highlighter']                 = [
			'theme-option' => 'syntax_highlighter_line_numbers',
			'type'         => 'select',
		];
		$element_option_map['background_color']['fusion_syntax_highlighter']             = [
			'theme-option' => 'syntax_highlighter_background_color',
			'reset'        => true,
		];
		$element_option_map['line_number_background_color']['fusion_syntax_highlighter'] = [
			'theme-option' => 'syntax_highlighter_line_number_background_color',
			'reset'        => true,
		];
		$element_option_map['line_number_text_color']['fusion_syntax_highlighter']       = [
			'theme-option' => 'syntax_highlighter_line_number_text_color',
			'reset'        => true,
		];
		$element_option_map['line_wrapping']['fusion_syntax_highlighter']                = [
			'theme-option' => 'syntax_highlighter_line_wrapping',
			'type'         => 'select',
		];
		$element_option_map['copy_to_clipboard']['fusion_syntax_highlighter']            = [
			'theme-option' => 'syntax_highlighter_copy_to_clipboard',
			'type'         => 'select',
		];
		$element_option_map['copy_to_clipboard_text']['fusion_syntax_highlighter']       = [
			'theme-option' => 'syntax_highlighter_copy_to_clipboard_text',
			'type'         => 'reset',
		];
		$element_option_map['font_size']['fusion_syntax_highlighter']                    = [
			'theme-option' => 'syntax_highlighter_font_size',
			'type'         => 'range',
		];
		$element_option_map['border_size']['fusion_syntax_highlighter']                  = [
			'theme-option' => 'syntax_highlighter_border_size',
			'type'         => 'range',
		];
		$element_option_map['border_color']['fusion_syntax_highlighter']                 = [
			'theme-option' => 'syntax_highlighter_border_color',
			'reset'        => true,
		];
		$element_option_map['border_style']['fusion_syntax_highlighter']                 = [
			'theme-option' => 'syntax_highlighter_border_style',
			'type'         => 'select',
		];
		$element_option_map['margin']['fusion_syntax_highlighter']                       = [
			'theme-option' => 'syntax_highlighter_margin',
			'subset'       => [ 'top', 'left', 'bottom', 'right' ],
		];

		// Chart.
		$element_option_map['show_tooltips']['fusion_chart'] = [
			'theme-option' => 'chart_show_tooltips',
			'type'         => 'select',
		];

		$element_option_map['chart_legend_position']['fusion_chart'] = [
			'theme-option' => 'chart_legend_position',
			'type'         => 'select',
		];

		// Video.
		$element_option_map['width']['fusion_video'] = [
			'theme-option' => 'video_max_width',
			'type'         => 'select',
		];

		$element_option_map['controls']['fusion_video'] = [
			'theme-option' => 'video_controls',
			'type'         => 'select',
		];

		$element_option_map['preload']['fusion_video'] = [
			'theme-option' => 'video_preload',
			'type'         => 'select',
		];

		// Related posts component.
		$element_option_map['number_related_posts']['fusion_tb_related'] = [
			'theme-option' => 'number_related_posts',
			'type'         => 'range',
		];

		$element_option_map['related_posts_columns']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_columns',
			'type'         => 'range',
		];

		$element_option_map['related_posts_column_spacing']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_column_spacing',
			'type'         => 'range',
		];

		$element_option_map['related_posts_swipe_items']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_swipe_items',
			'type'         => 'range',
		];

		$element_option_map['related_posts_image_size']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_image_size',
			'type'         => 'select',
		];

		$element_option_map['related_posts_autoplay']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_autoplay',
			'type'         => 'yesno',
		];

		$element_option_map['related_posts_navigation']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_navigation',
			'type'         => 'yesno',
		];

		$element_option_map['related_posts_swipe']['fusion_tb_related'] = [
			'theme-option' => 'related_posts_swipe',
			'type'         => 'yesno',
		];

		// Slider element.
		$element_option_map['slideshow_autoplay']['fusion_slider'] = [
			'theme-option' => 'slideshow_autoplay',
			'type'         => 'yesno',
		];

		$element_option_map['slideshow_smooth_height']['fusion_slider'] = [
			'theme-option' => 'slideshow_smooth_height',
			'type'         => 'yesno',
		];

		$element_option_map['slideshow_speed']['fusion_slider'] = [
			'theme-option' => 'slideshow_speed',
			'type'         => 'range',
		];

		self::$element_descriptions_map = $element_option_map;
	}

	/**
	 * Setup the element option dependency map.
	 *
	 * @static
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public static function set_element_dependency_map() {
		$element_option_map = [];

		// Audio.
		$element_option_map['border_color']['fusion_audio'][] = [
			'check'  => [
				'element-option' => 'audio_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'border_size',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Blog.
		$blog_is_excerpt = [
			'check'  => [
				'element-option' => 'blog_excerpt',
				'value'          => 'yes',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'excerpt',
				'value'    => '',
				'operator' => '!=',
			],
		];

		$element_option_map['excerpt_length']['fusion_blog'][] = $blog_is_excerpt;
		$element_option_map['strip_html']['fusion_blog'][]     = $blog_is_excerpt;

		$blog_is_single_column = [
			'check'  => [
				'element-option' => 'blog_grid_columns',
				'value'          => '1',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'blog_grid_columns',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['blog_grid_column_spacing']['fusion_blog'][] = $blog_is_single_column;
		$element_option_map['equal_heights']['fusion_blog'][]            = $blog_is_single_column;

		// Google Map.
		$is_embed_map = [
			'check'  => [
				'element-option' => 'google_map_api_type',
				'value'          => 'embed',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'api_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		$element_option_map['embed_address']['fusion_map'][]  = $is_embed_map;
		$element_option_map['embed_map_type']['fusion_map'][] = $is_embed_map;

		$is_not_embed_map = [
			'check'  => [
				'element-option' => 'google_map_api_type',
				'value'          => 'embed',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'api_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		$element_option_map['address']['fusion_map'][] = $is_not_embed_map;
		$element_option_map['type']['fusion_map'][]    = $is_not_embed_map;

		$is_static_map = [
			'check'  => [
				'element-option' => 'google_map_api_type',
				'value'          => 'static',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'api_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		$element_option_map['icon_static']['fusion_map'][]      = $is_static_map;
		$element_option_map['static_map_color']['fusion_map'][] = $is_static_map;

		$is_js_map = [
			'check'  => [
				'element-option' => 'google_map_api_type',
				'value'          => 'js',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'api_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['scrollwheel']['fusion_map'][]              = $is_js_map;
		$element_option_map['scale']['fusion_map'][]                    = $is_js_map;
		$element_option_map['zoom_pancontrol']['fusion_map'][]          = $is_js_map;
		$element_option_map['animation']['fusion_map'][]                = $is_js_map;
		$element_option_map['popup']['fusion_map'][]                    = $is_js_map;
		$element_option_map['map_style']['fusion_map'][]                = $is_js_map;
		$element_option_map['overlay_color']['fusion_map'][]            = $is_js_map;
		$element_option_map['infobox_content']['fusion_map'][]          = $is_js_map;
		$element_option_map['infobox']['fusion_map'][]                  = $is_js_map;
		$element_option_map['icon']['fusion_map'][]                     = $is_js_map;
		$element_option_map['infobox_text_color']['fusion_map'][]       = $is_js_map;
		$element_option_map['infobox_background_color']['fusion_map'][] = $is_js_map;

		// Fontawesome Icon.
		$has_icon_background                                       = [
			'check'  => [
				'element-option' => 'icon_circle',
				'value'          => 'yes',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'circle',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['circlecolor']['fusion_fontawesome'][] = $has_icon_background;
		$element_option_map['circlecolor_hover']['fusion_fontawesome'][]       = $has_icon_background;
		$element_option_map['circlebordercolor']['fusion_fontawesome'][]       = $has_icon_background;
		$element_option_map['circlebordercolor_hover']['fusion_fontawesome'][] = $has_icon_background;
		$element_option_map['circlebordersize']['fusion_fontawesome'][]        = $has_icon_background;

		$has_border_size = [
			'check'  => [
				'element-option' => 'icon_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'circlebordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['circlebordercolor']['fusion_fontawesome'][]       = $has_icon_background;
		$element_option_map['circlebordercolor_hover']['fusion_fontawesome'][] = $has_icon_background;
		// Progress.
		$element_option_map['filledbordercolor']['fusion_progress'][] = [
			'check'  => [
				'element-option' => 'progressbar_filled_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'filledbordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Social links.
		$element_option_map['icons_boxed_radius']['fusion_social_links'][] = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icons_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['box_colors']['fusion_social_links'][]         = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icons_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['icon_colors']['fusion_social_links'][]        = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['box_colors']['fusion_social_links'][]         = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Sharing box.
		$element_option_map['icons_boxed_radius']['fusion_sharing'][] = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icons_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['box_colors']['fusion_sharing'][]         = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['box_colors']['fusion_sharing'][]         = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icons_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['icon_colors']['fusion_sharing'][]        = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Toggles.
		$element_option_map['divider_line']['fusion_accordion'][]     = [
			'check'  => [
				'element-option' => 'accordion_boxed_mode',
				'value'          => '1',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['border_size']['fusion_accordion'][]      = [
			'check'  => [
				'element-option' => 'accordion_boxed_mode',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['border_color']['fusion_accordion'][]     = [
			'check'  => [
				'element-option' => 'accordion_boxed_mode',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['background_color']['fusion_accordion'][] = [
			'check'  => [
				'element-option' => 'accordion_boxed_mode',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['icon_box_color']['fusion_accordion'][]   = [
			'check'  => [
				'element-option' => 'accordion_icon_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icon_boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['hover_color']['fusion_accordion'][]      = [
			'check'  => [
				'element-option' => 'accordion_boxed_mode',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'boxed_mode',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Checklist.
		$element_option_map['circlecolor']['fusion_checklist'][]   = [
			'check'  => [
				'element-option' => 'checklist_circle',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'circle',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['divider_color']['fusion_checklist'][] = [
			'check'  => [
				'element-option' => 'checklist_divider',
				'value'          => 'no',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'divider',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Image.
		$element_option_map['blur']['fusion_imageframe'][]        = [
			'check'  => [
				'element-option' => 'imageframe_style_type',
				'value'          => 'none',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'style_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['stylecolor']['fusion_imageframe'][]  = [
			'check'  => [
				'element-option' => 'imageframe_style_type',
				'value'          => 'none',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'style_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['bordercolor']['fusion_imageframe'][] = [
			'check'  => [
				'element-option' => 'imageframe_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'bordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Image Before & After.
		$element_option_map['before_label']['fusion_image_before_after'][]    = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['after_label']['fusion_image_before_after'][]     = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['font_size']['fusion_image_before_after'][]       = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['accent_color']['fusion_image_before_after'][]    = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['label_placement']['fusion_image_before_after'][] = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_type']['fusion_image_before_after'][]     = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_color']['fusion_image_before_after'][]    = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_bg']['fusion_image_before_after'][]       = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_bg']['fusion_image_before_after'][]       = [
			'check'  => [
				'element-option' => 'before_after_handle_type',
				'value'          => 'arrows',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'handle_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_bg']['fusion_image_before_after'][]       = [
			'check'  => [
				'element-option' => 'before_after_handle_type',
				'value'          => 'circle',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'handle_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['transition_time']['fusion_image_before_after'][] = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'before_after',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['offset']['fusion_image_before_after'][]          = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['orientation']['fusion_image_before_after'][]     = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['handle_movement']['fusion_image_before_after'][] = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'switch',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['link']['fusion_image_before_after'][]            = [
			'check'  => [
				'element-option' => 'before_after_type',
				'value'          => 'before_after',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Button.
		$element_option_map['bevel_color']['fusion_button'][] = [
			'check'  => [
				'element-option' => 'button_type',
				'value'          => 'Flat',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Gallery.
		$element_option_map['bordercolor']['fusion_gallery'][] = [
			'check'  => [
				'element-option' => 'gallery_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'bordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Person.
		$element_option_map['pic_style_blur']['fusion_person'][]           = [
			'check'  => [
				'element-option' => 'person_pic_style',
				'value'          => 'none',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'pic_style',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['pic_style_color']['fusion_person'][]          = [
			'check'  => [
				'element-option' => 'person_pic_style',
				'value'          => 'none',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'pic_style',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['social_icon_boxed_radius']['fusion_person'][] = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'social_icon_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['social_icon_boxed_colors']['fusion_person'][] = [
			'check'  => [
				'element-option' => 'social_links_boxed',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'social_icon_boxed',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['social_icon_boxed_colors']['fusion_person'][] = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'social_icon_color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['social_icon_colors']['fusion_person'][]       = [
			'check'  => [
				'element-option' => 'social_links_color_type',
				'value'          => 'brand',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'social_icon_color_type',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Content boxes.
		$element_option_map['circlebordercolor']['fusion_content_boxes'][]      = [
			'check'  => [
				'element-option' => 'content_box_icon_bg_inner_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'circlebordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['outercirclebordercolor']['fusion_content_boxes'][] = [
			'check'  => [
				'element-option' => 'content_box_icon_bg_outer_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'outercirclebordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['button_span']['fusion_content_boxes'][]            = [
			'check'  => [
				'element-option' => 'content_box_link_type',
				'value'          => 'button',
				'operator'       => '!=',
			],
			'output' => [
				'element'  => 'link_type',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$boxed_content_boxes = [
			'check'  => [
				'element-option' => 'content_box_icon_circle',
				'value'          => 'no',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icon_circle',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['icon_circle_radius']['fusion_content_boxes'][]     = $boxed_content_boxes;
		$element_option_map['circlecolor']['fusion_content_boxes'][]            = $boxed_content_boxes;
		$element_option_map['circlebordercolor']['fusion_content_boxes'][]      = $boxed_content_boxes;
		$element_option_map['circlebordersize']['fusion_content_boxes'][]       = $boxed_content_boxes;
		$element_option_map['outercirclebordercolor']['fusion_content_boxes'][] = $boxed_content_boxes;
		$element_option_map['outercirclebordersize']['fusion_content_boxes'][]  = $boxed_content_boxes;

		$parent_boxed_content_boxes = [
			'check'  => [
				'element-option' => 'content_box_icon_circle',
				'value'          => 'no',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'parent_icon_circle',
				'value'    => '',
				'operator' => '!=',
			],
		];

		$element_option_map['circlecolor']['fusion_content_box'][]            = $parent_boxed_content_boxes;
		$element_option_map['circlebordercolor']['fusion_content_box'][]      = $parent_boxed_content_boxes;
		$element_option_map['circlebordersize']['fusion_content_box'][]       = $parent_boxed_content_boxes;
		$element_option_map['outercirclebordercolor']['fusion_content_box'][] = $parent_boxed_content_boxes;
		$element_option_map['outercirclebordersize']['fusion_content_box'][]  = $parent_boxed_content_boxes;

		// Flip boxes.
		$element_option_map['border_color']['fusion_flip_box'][] = [
			'check'  => [
				'element-option' => 'flip_boxes_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'border_size',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Container.
		$element_option_map['border_color']['fusion_builder_container'][] = [
			'check'  => [
				'element-option' => 'full_width_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'border_size',
				'value'    => '',
				'operator' => '!=',
			],
		];
		$element_option_map['border_style']['fusion_builder_container'][] = [
			'check'  => [
				'element-option' => 'full_width_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'border_size',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Section separator.
		$element_option_map['bordercolor']['fusion_section_separator'][] = [
			'check'  => [
				'element-option' => 'section_sep_border_size',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'bordersize',
				'value'    => '',
				'operator' => '!=',
			],
		];

		// Separator.
		$element_option_map['icon_circle_color']['fusion_separator'][] = [
			'check'  => [
				'element-option' => 'separator_circle',
				'value'          => '0',
				'operator'       => '==',
			],
			'output' => [
				'element'  => 'icon_circle',
				'value'    => '',
				'operator' => '!=',
			],
		];

		self::$element_dependency_map = $element_option_map;
	}

	/**
	 * Set scope for shortcode IDs.
	 *
	 * @access public
	 * @since 2.0
	 * @param int $parent_id Id of parent element.
	 * @return void
	 */
	public function set_global_shortcode_parent( $parent_id ) {
		$this->shortcode_parent = (int) $parent_id;
	}

	/**
	 * Get scope for shortcode IDs.
	 *
	 * @access public
	 * @since 2.0
	 * @return mixed
	 */
	public function get_global_shortcode_parent() {
		if ( $this->shortcode_parent ) {
			return $this->shortcode_parent;
		}
		return false;
	}
}
