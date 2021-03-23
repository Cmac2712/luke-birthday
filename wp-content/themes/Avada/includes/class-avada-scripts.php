<?php
/**
 * Enqueues scripts and styles.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handle enqueueing scrips.
 */
class Avada_Scripts {

	/**
	 * The theme version.
	 *
	 * @static
	 * @access private
	 * @since 5.0.0
	 * @var string
	 */
	private static $version;

	/**
	 * The CSS-compiling mode.
	 *
	 * @access private
	 * @since 5.1.5
	 * @var string
	 */
	private $compiler_mode;

	/**
	 * The media-queries.
	 *
	 * @static
	 * @access public
	 * @since 6.0
	 * @var array
	 */
	public static $media_queries = [];

	/**
	 * The class construction
	 *
	 * @access public
	 */
	public function __construct() {
		self::$version = Avada::get_theme_version();

		$dynamic_css_obj     = Fusion_Dynamic_CSS::get_instance();
		$this->compiler_mode = ( method_exists( $dynamic_css_obj, 'get_mode' ) ) ? $dynamic_css_obj->get_mode() : $dynamic_css_obj->mode;

		if ( ! is_admin() && ! in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) {
			add_action( 'wp', [ $this, 'wp_action' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_styles' ] );
			add_action( 'script_loader_tag', [ $this, 'add_async' ], 10, 2 );

			// This is added with a priority of 999 because it has to run after all other scripts have been added.
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_scripts' ], 999 );
		}

		if ( class_exists( 'WooCommerce' ) ) {
			add_filter( 'woocommerce_enqueue_styles', [ $this, 'remove_woo_scripts' ] );
		}

		add_filter( 'fusion_dynamic_css_final', [ $this, 'combine_stylesheets' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_css' ], 999 );
		add_action( 'admin_head', [ $this, 'admin_styles' ] );

		// Handle media-query styles.
		add_action( 'wp', [ $this, 'add_media_query_styles' ] );

		add_action( 'customize_preview_init', [ $this, 'customize_preview_init' ] );

		// Disable emojis script.
		add_action( 'init', [ $this, 'disable_emojis' ] );

		// Delay fusion_get_options in order for default value to be available.
		add_action( 'init', [ $this, 'add_filter_replace_css_vars' ], 11 );
	}

	/**
	 * Add filter for replacing CSS vars.
	 */
	public function add_filter_replace_css_vars() {

		// Replace CSS-Variables in compiled CSS.
		$callback = fusion_get_option( 'css_vars' ) ? '__return_false' : '__return_true';
		add_filter( 'fusion_replace_css_var_values', $callback );
	}

	/**
	 * A method that runs on 'wp'.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return void
	 */
	public function wp_action() {

		$this->enqueue_scripts();
		$this->localize_scripts();

	}

	/**
	 * Adds our scripts using Fusion_Dynamic_JS.
	 *
	 * @access protected
	 * @since 5.1.0
	 * @return void
	 */
	protected function enqueue_scripts() {
		$multilingual = fusion_library()->multilingual;

		$page_id = Avada()->fusion_library->get_page_id();

		$js_folder_suffix = AVADA_DEV_MODE ? '/assets/js' : '/assets/min/js';
		$js_folder_url    = Avada::$template_dir_url . $js_folder_suffix;
		$js_folder_path   = Avada::$template_dir_path . $js_folder_suffix;

		$privacy_options = Avada()->privacy_embeds->get_options();

		$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

		$scripts = [
			[
				'avada-skip-link-focus-fix',
				$js_folder_url . '/general/avada-skip-link-focus-fix.js',
				$js_folder_path . '/general/avada-skip-link-focus-fix.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'bootstrap-scrollspy',
				$js_folder_url . '/library/bootstrap.scrollspy.js',
				$js_folder_path . '/library/bootstrap.scrollspy.js',
				[ 'jquery' ],
				'3.3.2',
				true,
			],
			[
				'avada-comments',
				$js_folder_url . '/general/avada-comments.js',
				$js_folder_path . '/general/avada-comments.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'avada-general-footer',
				$js_folder_url . '/general/avada-general-footer.js',
				$js_folder_path . '/general/avada-general-footer.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'avada-quantity',
				$js_folder_url . '/general/avada-quantity.js',
				$js_folder_path . '/general/avada-quantity.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'avada-scrollspy',
				$js_folder_url . '/general/avada-scrollspy.js',
				$js_folder_path . '/general/avada-scrollspy.js',
				( ! is_page_template( 'blank.php' ) && 'no' !== fusion_get_page_option( 'display_header', $page_id ) ) ? [ 'avada-header', 'fusion-waypoints', 'bootstrap-scrollspy' ] : [ 'fusion-waypoints', 'bootstrap-scrollspy' ],
				self::$version,
				true,
			],
			[
				'avada-select',
				$js_folder_url . '/general/avada-select.js',
				$js_folder_path . '/general/avada-select.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'avada-sidebars',
				$js_folder_url . '/general/avada-sidebars.js',
				$js_folder_path . '/general/avada-sidebars.js',
				[ 'jquery', 'modernizr' ],
				self::$version,
				true,
			],
			[
				'jquery-sticky-kit',
				$js_folder_url . '/library/jquery.sticky-kit.js',
				$js_folder_path . '/library/jquery.sticky-kit.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
			[
				'avada-tabs-widget',
				$js_folder_url . '/general/avada-tabs-widget.js',
				$js_folder_path . '/general/avada-tabs-widget.js',
				[ 'jquery' ],
				self::$version,
				true,
			],
		];

		// Conditional scripts.
		$available_languages = $multilingual->get_available_languages();
		if ( ! empty( $available_languages ) ) {
			$scripts[] = [
				'avada-wpml',
				$js_folder_url . '/general/avada-wpml.js',
				$js_folder_path . '/general/avada-wpml.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}
		if ( $privacy_options['privacy_embeds'] || $privacy_options['privacy_bar'] || Avada()->settings->get( 'slidingbar_widgets' ) || $is_builder ) {
			$scripts[] = [
				'avada-container-scroll',
				$js_folder_url . '/general/avada-container-scroll.js',
				$js_folder_path . '/general/avada-container-scroll.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}
		if ( is_page_template( 'side-navigation.php' ) ) {
			$scripts[] = [
				'avada-side-nav',
				$js_folder_url . '/general/avada-side-nav.js',
				$js_folder_path . '/general/avada-side-nav.js',
				[ 'jquery', 'jquery-hover-intent' ],
				self::$version,
				true,
			];
		}
		if ( ! is_page_template( 'blank.php' ) && 'no' !== fusion_get_page_option( 'display_header', $page_id ) ) {
			$scripts[] = [
				'avada-header',
				$js_folder_url . '/general/avada-header.js',
				$js_folder_path . '/general/avada-header.js',
				[ 'modernizr', 'jquery', 'jquery-easing' ],
				self::$version,
				true,
			];
			$scripts[] = [
				'avada-menu',
				$js_folder_url . '/general/avada-menu.js',
				$js_folder_path . '/general/avada-menu.js',
				[ 'modernizr', 'jquery', 'avada-header' ],
				self::$version,
				true,
			];
		}
		if ( 'off' !== Avada()->settings->get( 'status_totop' ) || $is_builder ) {
			$scripts[] = [
				'jquery-to-top',
				$js_folder_url . '/library/jquery.toTop.js',
				$js_folder_path . '/library/jquery.toTop.js',
				[ 'jquery' ],
				'1.2',
				true,
			];
			$scripts[] = [
				'avada-to-top',
				$js_folder_url . '/general/avada-to-top.js',
				$js_folder_path . '/general/avada-to-top.js',
				[ 'jquery', 'cssua', 'jquery-to-top' ],
				self::$version,
				true,
			];
		}
		if ( Avada()->settings->get( 'slidingbar_widgets' ) || $is_builder ) {
			$scripts[] = [
				'avada-sliding-bar',
				$js_folder_url . '/general/avada-sliding-bar.js',
				$js_folder_path . '/general/avada-sliding-bar.js',
				[ 'modernizr', 'jquery', 'jquery-easing', 'avada-container-scroll' ],
				self::$version,
				true,
			];
		}
		if ( Avada()->settings->get( 'avada_styles_dropdowns' ) || $is_builder ) {
			$scripts[] = [
				'avada-drop-down',
				$js_folder_url . '/general/avada-drop-down.js',
				$js_folder_path . '/general/avada-drop-down.js',
				[ 'jquery', 'avada-select' ],
				self::$version,
				true,
			];
		}
		if ( 'top' !== fusion_get_option( 'header_position' ) ) {
			$scripts[] = [
				'avada-side-header-scroll',
				$js_folder_url . '/general/avada-side-header-scroll.js',
				$js_folder_path . '/general/avada-side-header-scroll.js',
				[ 'modernizr', 'jquery' ],
				self::$version,
				true,
			];
		}

		if ( class_exists( 'RevSliderFront' ) && ( fusion_get_option( 'avada_rev_styles' ) || $is_builder ) ) {

			// If slider revolution is active. Can't check for rev styles option as it can be enabled in page options.
			$scripts[] = [
				'avada-rev-styles',
				$js_folder_url . '/general/avada-rev-styles.js',
				$js_folder_path . '/general/avada-rev-styles.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}
		if ( 'footer_parallax_effect' === Avada()->settings->get( 'footer_special_effects' ) || $is_builder ) {
			$scripts[] = [
				'avada-parallax-footer',
				$js_folder_url . '/general/avada-parallax-footer.js',
				$js_folder_path . '/general/avada-parallax-footer.js',
				[ 'jquery', 'modernizr' ],
				self::$version,
				true,
			];
		}
		if ( ! Avada()->settings->get( 'disable_mobile_image_hovers' ) || $is_builder ) {
			$scripts[] = [
				'avada-mobile-image-hover',
				$js_folder_url . '/general/avada-mobile-image-hover.js',
				$js_folder_path . '/general/avada-mobile-image-hover.js',
				[ 'jquery', 'modernizr' ],
				self::$version,
				true,
			];
		}
		if ( Avada()->settings->get( 'page_title_fading' ) || $is_builder ) {

			// If we add a page option for this, it will need to be changed here too.
			$scripts[] = [
				'avada-fade',
				$js_folder_url . '/general/avada-fade.js',
				$js_folder_path . '/general/avada-fade.js',
				[ 'jquery', 'cssua', 'jquery-fade' ],
				self::$version,
				true,
			];
		}
		if ( defined( 'WPCF7_PLUGIN' ) ) {
			$scripts[] = [
				'avada-contact-form-7',
				$js_folder_url . '/general/avada-contact-form-7.js',
				$js_folder_path . '/general/avada-contact-form-7.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}
		if ( class_exists( 'GFForms' ) && Avada()->settings->get( 'avada_styles_dropdowns' ) ) {
			$scripts[] = [
				'avada-gravity-forms',
				$js_folder_url . '/general/avada-gravity-forms.js',
				$js_folder_path . '/general/avada-gravity-forms.js',
				[ 'jquery', 'avada-select' ],
				self::$version,
				true,
			];
		}
		if ( Avada()->settings->get( 'status_eslider' ) || $is_builder ) {
			$scripts[] = [
				'jquery-elastic-slider',
				$js_folder_url . '/library/jquery.elasticslider.js',
				$js_folder_path . '/library/jquery.elasticslider.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
			$scripts[] = [
				'avada-elastic-slider',
				$js_folder_url . '/general/avada-elastic-slider.js',
				$js_folder_path . '/general/avada-elastic-slider.js',
				[ 'jquery', 'jquery-elastic-slider' ],
				self::$version,
				true,
			];
		}
		if ( class_exists( 'WooCommerce' ) ) {
			$scripts[] = [
				'avada-woocommerce',
				$js_folder_url . '/general/avada-woocommerce.js',
				$js_folder_path . '/general/avada-woocommerce.js',
				[ 'jquery', 'modernizr', 'fusion-equal-heights' ],
				self::$version,
				true,
			];
		}
		if ( function_exists( 'is_bbpress' ) ) {
			$scripts[] = [
				'avada-bbpress',
				$js_folder_url . '/general/avada-bbpress.js',
				$js_folder_path . '/general/avada-bbpress.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$scripts[] = [
				'avada-events',
				$js_folder_url . '/general/avada-events.js',
				$js_folder_path . '/general/avada-events.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}

		if ( fusion_get_option( 'smooth_scrolling' ) || $is_builder ) {
			$scripts[] = [
				'jquery-nicescroll',
				$js_folder_url . '/library/jquery.nicescroll.js',
				$js_folder_path . '/library/jquery.nicescroll.js',
				[ 'jquery' ],
				'3.7.4',
				true,
			];
		}

		if ( fusion_get_option( 'smooth_scrolling' ) && ! $is_builder ) {
			$scripts[] = [
				'avada-nicescroll',
				$js_folder_url . '/general/avada-nicescroll.js',
				$js_folder_path . '/general/avada-nicescroll.js',
				[ 'jquery', 'modernizr', 'jquery-nicescroll' ],
				self::$version,
				true,
			];
		}

		if ( $privacy_options['privacy_embeds'] || $privacy_options['privacy_bar'] ) {
			$scripts[] = [
				'avada-privacy',
				$js_folder_url . '/general/avada-privacy.js',
				$js_folder_path . '/general/avada-privacy.js',
				[ 'jquery', 'avada-container-scroll' ],
				self::$version,
				true,
			];
		}

		if ( fusion_get_option( 'live_search' ) || $is_builder || function_exists( 'fusion_is_element_enabled' ) && fusion_is_element_enabled( 'fusion_search' ) ) {
			$scripts[] = [
				'avada-live-search',
				$js_folder_url . '/general/avada-live-search.js',
				$js_folder_path . '/general/avada-live-search.js',
				[ 'jquery' ],
				self::$version,
				true,
			];
		}

		if ( ! class_exists( 'FusionBuilder' ) ) {
			$scripts[] = [
				'fusion-carousel',
				str_replace( Avada::$template_dir_url, FUSION_LIBRARY_URL, $js_folder_url ) . '/general/fusion-carousel.js',
				str_replace( Avada::$template_dir_path, FUSION_LIBRARY_PATH, $js_folder_path ) . '/general/fusion-carousel.js',
				[ 'jquery-caroufredsel', 'jquery-touch-swipe' ],
				'1',
				true,
			];
			$scripts[] = [
				'fusion-blog',
				str_replace( Avada::$template_dir_url, FUSION_LIBRARY_URL, $js_folder_url ) . '/general/fusion-blog.js',
				str_replace( Avada::$template_dir_path, FUSION_LIBRARY_PATH, $js_folder_path ) . '/general/fusion-blog.js',
				[ 'jquery', 'isotope', 'fusion-lightbox', 'fusion-flexslider', 'jquery-infinite-scroll', 'images-loaded' ],
				'1',
				true,
			];
		}

		foreach ( $scripts as $script ) {
			Fusion_Dynamic_JS::enqueue_script(
				$script[0],
				$script[1],
				$script[2],
				$script[3],
				$script[4],
				$script[5]
			);
		}

		Fusion_Dynamic_JS::enqueue_script( 'fusion-alert' );

		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/background.min.css', Avada::$template_dir_url . '/assets/css/dynamic/background.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/header.min.css', Avada::$template_dir_url . '/assets/css/dynamic/header.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/blog.min.css', Avada::$template_dir_url . '/assets/css/dynamic/blog.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/colors.min.css', Avada::$template_dir_url . '/assets/css/dynamic/colors.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/contact.min.css', Avada::$template_dir_url . '/assets/css/dynamic/contact.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/elastic-slider.min.css', Avada::$template_dir_url . '/assets/css/dynamic/elastic-slider.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/extra.min.css', Avada::$template_dir_url . '/assets/css/dynamic/extra.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/footer.min.css', Avada::$template_dir_url . '/assets/css/dynamic/footer.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/layout.min.css', Avada::$template_dir_url . '/assets/css/dynamic/layout.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/logo.min.css', Avada::$template_dir_url . '/assets/css/dynamic/logo.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/menu.min.css', Avada::$template_dir_url . '/assets/css/dynamic/menu.min.css' );

		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/portfolio.min.css', Avada::$template_dir_url . '/assets/css/dynamic/portfolio.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/search.min.css', Avada::$template_dir_url . '/assets/css/dynamic/search.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/sidebars.min.css', Avada::$template_dir_url . '/assets/css/dynamic/sidebars.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/slideshows.min.css', Avada::$template_dir_url . '/assets/css/dynamic/slideshows.min.css' );
		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/social-media.min.css', Avada::$template_dir_url . '/assets/css/dynamic/social-media.min.css' );

		$override_footer = class_exists( 'Fusion_Template_Builder' ) ? Fusion_Template_Builder::get_instance()->get_override( 'footer' ) : false;
		if ( ! $override_footer ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/fusion-footer.min.css', Avada::$template_dir_url . '/assets/css/fusion-footer.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/fusion-footer-widget-area.min.css', Avada::$template_dir_url . '/assets/css/fusion-footer-widget-area.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/footer-copyright.min.css', Avada::$template_dir_url . '/assets/css/footer-copyright.min.css' );
		}

		$override_ptb = class_exists( 'Fusion_Template_Builder' ) ? Fusion_Template_Builder::get_instance()->get_override( 'page_title_bar' ) : false;
		if ( ( ! $override_ptb && ( ! get_the_ID() || avada_is_page_title_bar_active( get_the_ID() ) ) ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/page-title-bar.min.css', Avada::$template_dir_url . '/assets/css/page-title-bar.min.css' );
		}

		Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/typography.min.css', Avada::$template_dir_url . '/assets/css/dynamic/typography.min.css' );

		if ( class_exists( 'RevSliderFront' ) && ( fusion_get_option( 'avada_rev_styles' ) || $is_builder ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/revslider.min.css', Avada::$template_dir_url . '/assets/css/dynamic/revslider.min.css' );
		}

		if ( is_rtl() ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/rtl.min.css', Avada::$template_dir_url . '/assets/css/dynamic/rtl.min.css' );
		} else {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/ltr.min.css', Avada::$template_dir_url . '/assets/css/dynamic/ltr.min.css' );
		}

		if ( ! class_exists( 'FusionBuilder' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/shared.min.css', Avada::$template_dir_url . '/assets/css/shared.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/no-fb.min.css', Avada::$template_dir_url . '/assets/css/dynamic/no-fb.min.css' );
		} else {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/fb.min.css', Avada::$template_dir_url . '/assets/css/dynamic/fb.min.css' );
		}

		if ( Avada()->settings->get( 'status_lightbox' ) && ! class_exists( 'FusionBuilder' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/ilightbox.min.css', Avada::$template_dir_url . '/assets/css/ilightbox.min.css' );
		}

		if ( ( 'off' !== Avada()->settings->get( 'status_css_animations' ) && ! class_exists( 'FusionBuilder' ) ) || $is_builder ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/animations.min.css', Avada::$template_dir_url . '/assets/css/animations.min.css' );
		}

		if ( class_exists( 'WooCommerce' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/woocommerce.min.css', Avada::$template_dir_url . '/assets/css/woocommerce.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/woocommerce.min.css', Avada::$template_dir_url . '/assets/css/dynamic/woocommerce.min.css' );
		}

		if ( class_exists( 'bbPress' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/bbpress.min.css', Avada::$template_dir_url . '/assets/css/bbpress.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/bbpress.min.css', Avada::$template_dir_url . '/assets/css/dynamic/bbpress.min.css' );
		}

		if ( class_exists( 'GFForms' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/gravityforms.min.css', Avada::$template_dir_url . '/assets/css/gravityforms.min.css' );
		}

		if ( class_exists( 'WPCF7' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/contactform7.min.css', Avada::$template_dir_url . '/assets/css/contactform7.min.css' );
		}

		if ( class_exists( 'Tribe__Events__Main' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/events-calendar.min.css', Avada::$template_dir_url . '/assets/css/events-calendar.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/ec.min.css', Avada::$template_dir_url . '/assets/css/dynamic/ec.min.css' );

			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/events-calendar-templates-v2.min.css', Avada::$template_dir_url . '/assets/css/events-calendar-templates-v2.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/ec-v2.min.css', Avada::$template_dir_url . '/assets/css/dynamic/ec-v2.min.css' );
		}

		if ( Avada()->settings->get( 'slidingbar_widgets' ) || $is_builder ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/slidingbar.min.css', Avada::$template_dir_url . '/assets/css/slidingbar.min.css' );
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/sliding-bar.min.css', Avada::$template_dir_url . '/assets/css/dynamic/sliding-bar.min.css' );
		}

		if ( defined( 'WPML_PLUGIN_FILE' ) || defined( 'ICL_PLUGIN_FILE' ) || class_exists( 'SitePress' ) ) {
			Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/wpml.min.css', Avada::$template_dir_url . '/assets/css/dynamic/wpml.min.css' );
		}
	}

	/**
	 * Localize the dynamic JS files.
	 *
	 * @access protected
	 * @since 5.1.0
	 * @return void
	 */
	protected function localize_scripts() {

		global $wp_styles, $woocommerce;
		$multilingual     = fusion_library()->multilingual;
		$layout           = fusion_get_option( 'layout' );
		$avada_rev_styles = fusion_get_option( 'avada_rev_styles' ) ? 1 : 0;

		$privacy_options = Avada()->privacy_embeds->get_options();

		$side_header_breakpoint = Avada()->settings->get( 'side_header_break_point' );
		if ( ! $side_header_breakpoint ) {
			$side_header_breakpoint = 800;
		}

		$cookie_args      = class_exists( 'Avada_Privacy_Embeds' ) && $privacy_options['privacy_embeds'] ? Avada()->privacy_embeds->get_cookie_args() : false;
		$consents         = class_exists( 'Avada_Privacy_Embeds' ) && $privacy_options['privacy_embeds'] ? array_keys( Avada()->privacy_embeds->get_embed_types() ) : [];
		$default_consents = class_exists( 'Avada_Privacy_Embeds' ) && $privacy_options['privacy_embeds'] ? Avada()->privacy_embeds->get_default_consents() : [];
		$header_sticky    = fusion_get_option( 'header_sticky' );

		if ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) {
			$preferences = Fusion_Preferences::get_instance()->get_preferences();

			if ( isset( $preferences['sticky_header'] ) && 'off' === $preferences['sticky_header'] ) {
				$header_sticky = '';
			}
		}

		$shop_page_bg_color = fusion_get_option( 'content_bg_color', 'content_bg_color', get_option( 'woocommerce_shop_page_id' ) );

		$scripts = [
			[
				'avada-header',
				'avadaHeaderVars',
				[
					'header_position'            => fusion_get_option( 'header_position' ),
					'header_sticky'              => $header_sticky,
					'header_sticky_type2_layout' => Avada()->settings->get( 'header_sticky_type2_layout' ),
					'header_sticky_shadow'       => fusion_get_option( 'header_sticky_shadow' ),
					'side_header_break_point'    => (int) $side_header_breakpoint,
					'header_sticky_mobile'       => fusion_get_option( 'header_sticky_mobile' ),
					'header_sticky_tablet'       => fusion_get_option( 'header_sticky_tablet' ),
					'mobile_menu_design'         => Avada()->settings->get( 'mobile_menu_design' ),
					'sticky_header_shrinkage'    => fusion_get_option( 'header_sticky_shrinkage' ),
					'nav_height'                 => (int) Avada()->settings->get( 'nav_height' ),
					'nav_highlight_border'       => ( 'bar' === Avada()->settings->get( 'menu_highlight_style' ) ) ? (int) Avada()->settings->get( 'nav_highlight_border' ) : '0',
					'nav_highlight_style'        => Avada()->settings->get( 'menu_highlight_style' ),
					'logo_margin_top'            => ( '' !== Avada()->settings->get( 'logo', 'url' ) || '' !== Avada()->settings->get( 'logo_retina', 'url' ) ) ? Avada()->settings->get( 'logo_margin', 'top' ) : '0px',
					'logo_margin_bottom'         => ( '' !== Avada()->settings->get( 'logo', 'url' ) || '' !== Avada()->settings->get( 'logo_retina', 'url' ) ) ? Avada()->settings->get( 'logo_margin', 'bottom' ) : '0px',
					'layout_mode'                => strtolower( $layout ),
					'header_padding_top'         => Avada()->settings->get( 'header_padding', 'top' ),
					'header_padding_bottom'      => Avada()->settings->get( 'header_padding', 'bottom' ),
					'scroll_offset'              => Avada()->settings->get( 'scroll_offset' ),
				],
			],
			[
				'avada-menu',
				'avadaMenuVars',
				[
					'site_layout'             => Avada()->settings->get( 'layout' ),
					'header_position'         => fusion_get_option( 'header_position' ),
					'logo_alignment'          => Avada()->settings->get( 'logo_alignment' ),
					'header_sticky'           => $header_sticky,
					'header_sticky_mobile'    => fusion_get_option( 'header_sticky_mobile' ),
					'header_sticky_tablet'    => fusion_get_option( 'header_sticky_tablet' ),
					'side_header_break_point' => (int) $side_header_breakpoint,
					'megamenu_base_width'     => Avada()->settings->get( 'megamenu_width' ),
					'mobile_menu_design'      => Avada()->settings->get( 'mobile_menu_design' ),
					'dropdown_goto'           => __( 'Go to...', 'Avada' ),
					'mobile_nav_cart'         => __( 'Shopping Cart', 'Avada' ),
					/* Translators: The submenu title. */
					'mobile_submenu_open'     => esc_attr__( 'Open submenu of %s', 'Avada' ),
					/* Translators: The submenu title. */
					'mobile_submenu_close'    => esc_attr__( 'Close submenu of %s', 'Avada' ),
					'submenu_slideout'        => fusion_get_option( 'mobile_nav_submenu_slideout' ),
				],
			],
			[
				'avada-comments',
				'avadaCommentVars',
				[
					'title_style_type'    => Avada()->settings->get( 'title_style_type' ),
					'title_margin_top'    => Avada()->settings->get( 'title_margin', 'top' ),
					'title_margin_bottom' => Avada()->settings->get( 'title_margin', 'bottom' ),
				],
			],
			[
				'jquery-to-top',
				'toTopscreenReaderText',
				[
					'label' => esc_attr__( 'Go to Top', 'Avada' ),
				],
			],
			[
				'avada-to-top',
				'avadaToTopVars',
				[
					'status_totop'           => Avada()->settings->get( 'status_totop' ),
					'totop_position'         => Avada()->settings->get( 'totop_position' ),
					'totop_scroll_down_only' => Avada()->settings->get( 'totop_scroll_down_only' ),
				],
			],
			[
				'avada-wpml',
				'avadaLanguageVars',
				[
					'language_flag' => $multilingual->get_active_language(),
				],
			],
			[
				'avada-sidebars',
				'avadaSidebarsVars',
				[
					'header_position'            => fusion_get_option( 'header_position' ),
					'header_layout'              => Avada()->settings->get( 'header_layout' ),
					'header_sticky'              => fusion_get_option( 'header_sticky' ),
					'header_sticky_type2_layout' => Avada()->settings->get( 'header_sticky_type2_layout' ),
					'side_header_break_point'    => (int) $side_header_breakpoint,
					'header_sticky_tablet'       => fusion_get_option( 'header_sticky_tablet' ),
					'sticky_header_shrinkage'    => fusion_get_option( 'header_sticky_shrinkage' ),
					'nav_height'                 => (int) Avada()->settings->get( 'nav_height' ),
					'sidebar_break_point'        => Avada()->settings->get( 'sidebar_break_point' ),
				],
			],
			[
				'avada-side-nav',
				'avadaSideNavVars',
				[
					'sidenav_behavior' => fusion_get_option( 'sidenav_behavior' ),
				],
			],
			[
				'avada-side-header-scroll',
				'avadaSideHeaderVars',
				[
					'side_header_break_point' => (int) $side_header_breakpoint,
					'footer_special_effects'  => Avada()->settings->get( 'footer_special_effects' ),
				],
			],
			[
				'avada-rev-styles',
				'avadaRevVars',
				[
					'avada_rev_styles' => $avada_rev_styles,
				],
			],
			[
				'avada-drop-down',
				'avadaSelectVars',
				[
					'avada_drop_down' => Avada()->settings->get( 'avada_styles_dropdowns' ),
				],
			],
			[
				'avada-parallax-footer',
				'avadaParallaxFooterVars',
				[
					'side_header_break_point' => (int) $side_header_breakpoint,
					'header_position'         => fusion_get_option( 'header_position' ),
				],
			],
			[
				'avada-mobile-image-hover',
				'avadaMobileImageVars',
				[
					'side_header_break_point'     => (int) $side_header_breakpoint,
					'disable_mobile_image_hovers' => Avada()->settings->get( 'disable_mobile_image_hovers' ),
				],
			],
			[
				'jquery-nicescroll',
				'avadaNiceScrollVars',
				[
					'side_header_width' => ( 'top' !== fusion_get_option( 'header_position' ) ) ? (int) Avada()->settings->get( 'side_header_width' ) : '0',
					'smooth_scrolling'  => fusion_get_option( 'smooth_scrolling' ),
				],
			],
			[
				'avada-woocommerce',
				'avadaWooCommerceVars',
				[
					'order_actions'                   => __( 'Details', 'Avada' ),
					'title_style_type'                => Avada()->settings->get( 'title_style_type' ),
					'woocommerce_shop_page_columns'   => Avada()->settings->get( 'woocommerce_shop_page_columns' ),
					'woocommerce_checkout_error'      => esc_attr__( 'Not all fields have been filled in correctly.', 'Avada' ),
					'woocommerce_single_gallery_size' => Fusion_Sanitize::number( Avada()->settings->get( 'woocommerce_single_gallery_size' ) ),
					'related_products_heading_size'   => ( false === avada_is_page_title_bar_enabled( get_the_ID() ) ? '2' : '3' ),
					'ajaxurl'                         => admin_url( 'admin-ajax.php' ),
					'shop_page_bg_color'              => $shop_page_bg_color,
					'shop_page_bg_color_lightness'    => Fusion_Color::new_color( $shop_page_bg_color )->lightness,
					'post_title_font_size'            => Fusion_Sanitize::convert_font_size_to_px( Avada()->settings->get( 'post_title_typography', 'font-size' ), Avada()->settings->get( 'post_title_typography', 'font-size' ) ),
				],
			],
			[
				'avada-bbpress',
				'avadaBbpressVars',
				[
					'alert_box_text_align'     => Avada()->settings->get( 'alert_box_text_align' ),
					'alert_box_text_transform' => Avada()->settings->get( 'alert_box_text_transform' ),
					'alert_box_dismissable'    => Avada()->settings->get( 'alert_box_dismissable' ),
					'alert_box_shadow'         => Avada()->settings->get( 'alert_box_shadow' ),
					'alert_border_size'        => Avada()->settings->get( 'alert_border_size' ),
				],
			],
			[
				'avada-elastic-slider',
				'avadaElasticSliderVars',
				[
					'tfes_autoplay'  => Avada()->settings->get( 'tfes_autoplay' ),
					'tfes_animation' => Avada()->settings->get( 'tfes_animation' ),
					'tfes_interval'  => (int) Avada()->settings->get( 'tfes_interval' ),
					'tfes_speed'     => (int) Avada()->settings->get( 'tfes_speed' ),
					'tfes_width'     => (int) Avada()->settings->get( 'tfes_width' ),
				],
			],
			[
				'avada-fade',
				'avadaFadeVars',
				[
					'page_title_fading' => Avada()->settings->get( 'page_title_fading' ),
					'header_position'   => fusion_get_option( 'header_position' ),
				],
			],
			[
				'avada-privacy',
				'avadaPrivacyVars',
				[
					'name'     => $cookie_args ? $cookie_args['name'] : 'privacy_embeds',
					'days'     => $cookie_args ? $cookie_args['days'] : '30',
					'path'     => $cookie_args ? $cookie_args['path'] : '/',
					'types'    => $consents ? $consents : [],
					'defaults' => $default_consents ? $default_consents : [],
					'button'   => $privacy_options['privacy_bar_button_save'],
				],
			],
			[
				'avada-live-search',
				'avadaLiveSearchVars',
				[
					'live_search'       => fusion_get_option( 'live_search' ) || function_exists( 'fusion_is_element_enabled' ) && fusion_is_element_enabled( 'fusion_search' ),
					'ajaxurl'           => admin_url( 'admin-ajax.php' ),
					'no_search_results' => esc_html__( 'No search results match your query. Please try again', 'Avada' ),
					'min_char_count'    => Avada()->settings->get( 'live_search_min_char_count' ),
					'per_page'          => Avada()->settings->get( 'live_search_results_per_page' ),
					'show_feat_img'     => fusion_get_option( 'live_search_display_featured_image' ),
					'display_post_type' => Avada()->settings->get( 'live_search_display_post_type' ),
				],
			],
		];

		foreach ( $scripts as $script ) {
			Fusion_Dynamic_JS::localize_script(
				$script[0],
				$script[1],
				$script[2]
			);

		}

	}

	/**
	 * Takes care of enqueueing all our scripts.
	 *
	 * @access public
	 */
	public function wp_enqueue_scripts() {

		wp_enqueue_script( 'jquery' );

		// The comment-reply script.
		if ( is_singular() && get_option( 'thread_comments' ) && comments_open() ) {
			wp_enqueue_script( 'comment-reply', '', [], self::$version, true );
		}

		if ( function_exists( 'novagallery_shortcode' ) ) {
			wp_enqueue_script( 'novagallery_modernizr' );
		}

		if ( function_exists( 'ccgallery_shortcode' ) ) {
			wp_enqueue_script( 'ccgallery_modernizr' );
		}

		if ( Avada()->settings->get( 'status_lightbox' ) && class_exists( 'WooCommerce' ) ) {
			wp_dequeue_script( 'prettyPhoto' );
			wp_dequeue_script( 'prettyPhoto-init' );
		}
	}

	/**
	 * Takes care of enqueueing all our styles.
	 *
	 * @access public
	 */
	public function wp_enqueue_styles() {

		if ( fusion_should_defer_styles_loading() && doing_action( 'wp_enqueue_scripts' ) ) {
			add_action( 'wp_body_open', [ $this, 'wp_enqueue_styles' ] );
			return;
		}

		wp_enqueue_style( 'avada-stylesheet', Avada::$template_dir_url . '/assets/css/style.min.css', [], self::$version );

		wp_enqueue_style( 'avada-IE', Avada::$template_dir_url . '/assets/css/ie.min.css', [], self::$version );
		wp_style_add_data( 'avada-IE', 'conditional', 'IE' );

		$form_bg_color = Avada()->settings->get( 'form_bg_color' ) ? Avada()->settings->get( 'form_bg_color' ) : '#ffffff';
		wp_add_inline_style( 'avada-IE', '.avada-select-parent .select-arrow{background-color:' . $form_bg_color . '}' );
		wp_add_inline_style( 'avada-IE', '.select-arrow{background-color:' . $form_bg_color . '}' );

		if ( Avada()->settings->get( 'status_lightbox' ) && class_exists( 'WooCommerce' ) ) {
			wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
		}

		if ( is_rtl() && 'file' !== $this->compiler_mode ) {
			wp_enqueue_style( 'avada-rtl', Avada::$template_dir_url . '/assets/css/rtl.min.css', [], self::$version );
		}
	}

	/**
	 * Adds assets to the compiled CSS.
	 *
	 * @access public
	 * @since 5.1.5
	 * @param string $original_styles The compiled styles.
	 * @return string The compiled styles with any additional CSS appended.
	 */
	public function combine_stylesheets( $original_styles ) {
		$styles = '';

		if ( 'off' !== Avada()->settings->get( 'css_cache_method' ) ) {
			if ( is_rtl() ) {
				// Stylesheet ID: avada-rtl.
				$styles .= file_get_contents( Avada::$template_dir_path . '/assets/css/rtl.min.css' );
			}
		}

		if ( function_exists( 'wpcf7_plugin_path' ) ) {
			$cf7_styles = file_get_contents( wpcf7_plugin_path( 'includes/css/styles.css' ) );
			if ( function_exists( 'wpcf7_is_rtl' ) && wpcf7_is_rtl() ) {
				$cf7_styles .= file_get_contents( wpcf7_plugin_path( 'includes/css/styles-rtl.css' ) );
			}
			$cf7_styles = str_replace( '../../images/ajax-loader.gif', wpcf7_plugin_url( 'images/ajax-loader.gif' ), $cf7_styles );

			$styles .= $cf7_styles;
		}

		return $styles . $original_styles;
	}

	/**
	 * Removes WooCommerce scripts.
	 *
	 * @access public
	 * @since 5.0.0
	 * @param array $scripts The WooCommerce scripts.
	 * @return array
	 */
	public function remove_woo_scripts( $scripts ) {

		if ( isset( $scripts['woocommerce-layout'] ) ) {
			unset( $scripts['woocommerce-layout'] );
		}
		if ( isset( $scripts['woocommerce-smallscreen'] ) ) {
			unset( $scripts['woocommerce-smallscreen'] );
		}
		if ( isset( $scripts['woocommerce-general'] ) ) {
			unset( $scripts['woocommerce-general'] );
		}
		return $scripts;

	}

	/**
	 * Adds media-query styles.
	 *
	 * @access public
	 * @since 6.0.0
	 */
	public function add_media_query_styles() {

		$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

		if ( ! fusion_get_option( 'responsive' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-shbp-not-responsive',
				get_template_directory_uri() . '/assets/css/media/max-shbp-not-responsive.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-shbp' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-shbp-18-not-responsive',
				get_template_directory_uri() . '/assets/css/media/max-shbp-18-not-responsive.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-shbp-18' ),
			];
			return;
		}

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-1c',
			get_template_directory_uri() . '/assets/css/media/max-1c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-1c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-2c',
			get_template_directory_uri() . '/assets/css/media/max-2c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-2c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-2c-max-3c',
			get_template_directory_uri() . '/assets/css/media/min-2c-max-3c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-2c-max-3c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-3c-max-4c',
			get_template_directory_uri() . '/assets/css/media/min-3c-max-4c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-3c-max-4c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-4c-max-5c',
			get_template_directory_uri() . '/assets/css/media/min-4c-max-5c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-4c-max-5c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-5c-max-6c',
			get_template_directory_uri() . '/assets/css/media/min-5c-max-6c.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-5c-max-6c' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-shbp',
			get_template_directory_uri() . '/assets/css/media/min-shbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-shbp' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-shbp',
			get_template_directory_uri() . '/assets/css/media/max-shbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-shbp' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-sh-shbp',
			get_template_directory_uri() . '/assets/css/media/max-sh-shbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-shbp' ),
		];

		// IPAD.
		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-768-max-1024-p',
			get_template_directory_uri() . '/assets/css/media/min-768-max-1024-p.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-768-max-1024-p' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-768-max-1024-l',
			get_template_directory_uri() . '/assets/css/media/min-768-max-1024-l.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-768-max-1024-l' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-sh-cbp',
			get_template_directory_uri() . '/assets/css/media/max-sh-cbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-sh-sbp',
			get_template_directory_uri() . '/assets/css/media/max-sh-sbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-sbp' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-sh-640',
			get_template_directory_uri() . '/assets/css/media/max-sh-640.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-640' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-shbp-18',
			get_template_directory_uri() . '/assets/css/media/max-shbp-18.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-shbp-18' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-shbp-32',
			get_template_directory_uri() . '/assets/css/media/max-shbp-32.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-shbp-32' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-min-sh-cbp',
			get_template_directory_uri() . '/assets/css/media/min-sh-cbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-sh-cbp' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-640',
			get_template_directory_uri() . '/assets/css/media/max-640.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-640' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-main',
			get_template_directory_uri() . '/assets/css/media/max-main.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-main' ),
		];

		Fusion_Media_Query_Scripts::$media_query_assets[] = [
			'avada-max-cbp',
			get_template_directory_uri() . '/assets/css/media/max-cbp.min.css',
			[],
			self::$version,
			Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-cbp' ),
		];

		// bbPress.
		if ( function_exists( 'is_bbpress' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-640-bbpress',
				get_template_directory_uri() . '/assets/css/media/max-640-bbpress.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-640-bbpress',
				get_template_directory_uri() . '/assets/css/media/max-sh-640-bbpress.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-bbpress',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-bbpress.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-min-sh-cbp-bbpress',
				get_template_directory_uri() . '/assets/css/media/min-sh-cbp-bbpress.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-sh-cbp' ),
			];
		}

		// Gravity Forms.
		if ( class_exists( 'GFForms' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-640-gravity',
				get_template_directory_uri() . '/assets/css/media/max-640-gravity.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-gravity',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-gravity.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];
		}

		// WPCF7.
		if ( defined( 'WPCF7_PLUGIN' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-cf7',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-cf7.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];
		}

		// LayerSlider & RevSlider.
		if ( defined( 'LS_PLUGIN_SLUG' ) || defined( 'RS_PLUGIN_PATH' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-640-sliders',
				get_template_directory_uri() . '/assets/css/media/max-640-sliders.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-sliders',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-sliders.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];
		}

		// Elastic Slider.
		if ( Avada()->settings->get( 'status_eslider' ) || $is_builder ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-eslider',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-eslider.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];
		}

		// CSS only added for the admin-bar.
		if ( is_admin_bar_showing() ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-782-adminbar',
				get_template_directory_uri() . '/assets/css/media/max-782-adminbar.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-782' ),
			];
		}

		// WooCommerce.
		if ( class_exists( 'WooCommerce' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-min-768-max-1024-woo',
				get_template_directory_uri() . '/assets/css/media/min-768-max-1024-woo.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-768-max-1024' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-640-woo',
				get_template_directory_uri() . '/assets/css/media/max-sh-640-woo.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-640' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-woo',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-woo.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];

			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-min-sh-cbp-woo',
				get_template_directory_uri() . '/assets/css/media/min-sh-cbp-woo.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-sh-cbp' ),
			];
		}

		// Events Calendar.
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-768-ec',
				get_template_directory_uri() . '/assets/css/media/max-768-ec.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-768' ),
			];
			Fusion_Media_Query_Scripts::$media_query_assets[] = [
				'avada-max-sh-cbp-ec',
				get_template_directory_uri() . '/assets/css/media/max-sh-cbp-ec.min.css',
				[],
				self::$version,
				Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
			];
		}
	}

	/**
	 * Add admin CSS
	 *
	 * @access public
	 */
	public function admin_css() {
		wp_enqueue_style( 'avada_wp_admin_css', get_template_directory_uri() . '/assets/admin/css/admin.css', false, self::$version );
	}

	/**
	 * Add async to avada javascript file for performance
	 *
	 * @access public
	 * @param  string $tag    The script tag.
	 * @param  string $handle The script handle.
	 */
	public function add_async( $tag, $handle ) {
		return ( 'avada' === $handle ) ? preg_replace( '/(><\/[a-zA-Z][^0-9](.*)>)$/', ' async $1 ', $tag ) : $tag;
	}

	/**
	 * Add extra admin styles.
	 *
	 * @access public
	 * @since 5.1.2
	 */
	public function admin_styles() {

		$font_url = FUSION_LIBRARY_URL . '/assets/fonts/icomoon-admin';
		$font_url = str_replace( [ 'http://', 'https://' ], '//', $font_url );
		?>
		<style type="text/css">
			@font-face {
				font-family: 'icomoon';
				src:url('<?php echo esc_url_raw( $font_url ); ?>/icomoon.eot');
				src:url('<?php echo esc_url_raw( $font_url ); ?>/icomoon.eot?#iefix') format('embedded-opentype'),
					url('<?php echo esc_url_raw( $font_url ); ?>/icomoon.woff') format('woff'),
					url('<?php echo esc_url_raw( $font_url ); ?>/icomoon.ttf') format('truetype'),
					url('<?php echo esc_url_raw( $font_url ); ?>/icomoon.svg#icomoon') format('svg');
				font-weight: normal;
				font-style: normal;
			}
		</style>
		<?php

	}

	/**
	 * Add scripts on customize_preview_init.
	 *
	 * @access public
	 * @since 6.0.0
	 */
	public function customize_preview_init() {
		wp_enqueue_script( 'avada-customizer', get_template_directory_uri() . '/assets/js/general/avada-customizer.js', [ 'jquery', 'customize-preview' ], self::$version, true );
		wp_localize_script( 'avada-customizer', 'fusionSetting', Fusion_Settings::get_option_name() );
		wp_add_inline_script( 'avada-customizer', 'parent.fusionIFrameWin = window;', 'before' );
	}

	/**
	 * Remove styles.
	 *
	 * @access public
	 * @since 5.8
	 */
	public function dequeue_scripts() {

		// Dequeue CF7 styles.
		// These get added in our dynamic-css (see combine_stylesheets() method).
		wp_dequeue_style( 'contact-form-7' );
		wp_dequeue_style( 'contact-form-7-rtl' );
	}

	/**
	 * Removes all emoji related scripts and styles.
	 *
	 * @since 5.8.1
	 */
	public function disable_emojis() {

		if ( 'disabled' !== Avada()->settings->get( 'emojis_disabled' ) ) {
			return;
		}

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', [ $this, 'disable_emojis_tinymce' ] );
		add_filter( 'wp_resource_hints', [ $this, 'disable_emojis_remove_dns_prefetch' ], 10, 2 );

		if ( '1' === get_option( 'use_smilies' ) ) {
			update_option( 'use_smilies', '0' );
		}
	}

	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 *
	 * @since 5.8.1
	 * @param array $plugins Array of TinyMCE plugins.
	 * @return array Difference betwen the two arrays
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, [ 'wpemoji' ] );
		}

		return [];
	}

	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @since 5.8.1
	 * @param  array  $urls URLs to print for resource hints.
	 * @param  string $relation_type The relation type the URLs are printed for.
	 * @return array  Difference betwen the two arrays.
	 */
	public function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {

		if ( 'dns-prefetch' === $relation_type ) {
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/11/svg/' );
			$urls          = array_diff( $urls, [ $emoji_svg_url ] );
		}

		return $urls;
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
