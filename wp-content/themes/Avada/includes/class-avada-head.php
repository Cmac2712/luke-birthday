<?php
/**
 * Tweaks for the <head> of the document.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      3.8
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Tweaks for the <head> of the document.
 */
class Avada_Head {

	/**
	 * Constructor.
	 *
	 * @access  public
	 */
	public function __construct() {
		/**
		 * WIP
		add_action( 'wp_head', array( $this, 'x_ua_meta' ), 1 );
		add_action( 'wp_head', array( $this, 'the_meta' ) );
		 */

		add_filter( 'language_attributes', [ $this, 'add_opengraph_doctype' ] );

		add_filter( 'document_title_separator', [ $this, 'document_title_separator' ] );

		add_filter( 'theme_color_meta', [ $this, 'theme_color' ] );

		add_action( 'wp_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'wp_head', [ $this, 'insert_og_meta' ], 5 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

		if ( ! function_exists( '_wp_render_title_tag' ) ) {
			add_action( 'wp_head', [ $this, 'render_title' ] );
		}

		add_action( 'wp_head', [ $this, 'set_user_agent' ], 1000 );

		// wp_body_open function introduced in WP 5.2.
		if ( function_exists( 'wp_body_open' ) ) {
			add_action( 'avada_before_body_content', 'wp_body_open' );
		}

		/**
		 * WIP
		add_filter( 'wpseo_metadesc', array( $this, 'yoast_metadesc_helper' ) );
		*/

	}

	/**
	 * Adding the Open Graph in the Language Attributes
	 *
	 * @access public
	 * @param  string $output The output we want to process/filter.
	 * @return string The altered doctype
	 */
	public function add_opengraph_doctype( $output ) {
		if ( Avada()->settings->get( 'status_opengraph' ) ) {
			return $output . ' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"';
		}
		return $output;
	}

	/**
	 * Renders the title.
	 *
	 * @access public
	 * @since 5.0.0
	 * @return void
	 */
	public function render_title() {
		wp_title( '' );
	}

	/**
	 * Set the user agent data attribute on the HTML tag.
	 *
	 * @access public
	 * @since 6.0
	 * @return void
	 */
	public function set_user_agent() {
		?>
		<script type="text/javascript">
			var doc = document.documentElement;
			doc.setAttribute( 'data-useragent', navigator.userAgent );
		</script>
		<?php
	}

	/**
	 * Avada extra OpenGraph tags
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access  public
	 * @return void
	 */
	public function insert_og_meta() {

		// Early exit if we don't need to continue any further.
		if ( ! Avada()->settings->get( 'status_opengraph' ) ) {
			return;
		}

		// Early exit if this is not a singular post/page/cpt.
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$settings = Avada::settings();

		$image = '';
		if ( ! has_post_thumbnail( $post->ID ) ) {
			if ( isset( $settings['logo'] ) && $settings['logo'] ) {
				$image = $settings['logo'];
			}
		} else {
			$thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$image         = esc_attr( $thumbnail_src[0] );
		}

		if ( is_array( $image ) ) {
			$image = ( isset( $image['url'] ) && ! empty( $image['url'] ) ) ? $image['url'] : '';
		}
		?>

		<meta property="og:title" content="<?php echo esc_attr( strip_tags( str_replace( [ '"', "'" ], [ '&quot;', '&#39;' ], $post->post_title ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags ?>"/>
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="<?php echo esc_url_raw( get_permalink() ); ?>"/>
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content ) ); ?>"/>

		<?php if ( '' != $image ) : // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison ?>
			<?php if ( is_array( $image ) ) : ?>
				<?php if ( isset( $image['url'] ) ) : ?>
					<meta property="og:image" content="<?php echo esc_url_raw( $image['url'] ); ?>"/>
				<?php endif; ?>
			<?php else : ?>
				<meta property="og:image" content="<?php echo esc_url_raw( $image ); ?>"/>
			<?php endif; ?>
		<?php endif; ?>
		<?php

	}

	/**
	 * Add X-UA-Compatible meta when needed.
	 *
	 * @access  public
	 */
	public function x_ua_meta() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( false !== strpos( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 'MSIE' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			echo '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />';
		}
	}

	/**
	 * Set the document title separator.
	 *
	 * @access  public
	 */
	public function document_title_separator() {
		return '-';
	}

	/**
	 * Avada favicon as set in theme options
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access  public
	 * @since   4.0
	 * @return  void
	 */
	public function insert_favicons() {
		?>
		<?php if ( '' !== Avada()->settings->get( 'favicon', 'url' ) ) : ?>
			<link rel="shortcut icon" href="<?php echo esc_url_raw( Avada()->settings->get( 'favicon', 'url' ) ); ?>" type="image/x-icon" />
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'iphone_icon', 'url' ) ) : ?>
			<!-- For iPhone -->
			<link rel="apple-touch-icon" href="<?php echo esc_url_raw( Avada()->settings->get( 'iphone_icon', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'iphone_icon_retina', 'url' ) ) : ?>
			<!-- For iPhone Retina display -->
			<link rel="apple-touch-icon" sizes="114x114" href="<?php echo esc_url_raw( Avada()->settings->get( 'iphone_icon_retina', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'ipad_icon', 'url' ) ) : ?>
			<!-- For iPad -->
			<link rel="apple-touch-icon" sizes="72x72" href="<?php echo esc_url_raw( Avada()->settings->get( 'ipad_icon', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'ipad_icon_retina', 'url' ) ) : ?>
			<!-- For iPad Retina display -->
			<link rel="apple-touch-icon" sizes="144x144" href="<?php echo esc_url_raw( Avada()->settings->get( 'ipad_icon_retina', 'url' ) ); ?>">
		<?php endif; ?>
		<?php

	}

	/**
	 * Fixes YOAST SEO plugin issues.
	 *
	 * @access public
	 * @since 5.0.3
	 * @param string $metadesc The description.
	 * @return string
	 */
	public function yoast_metadesc_helper( $metadesc ) {
		if ( '' === $metadesc ) {
			global $post;

			$metadesc = Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content );
		}

		return $metadesc;
	}

	/**
	 * Echoes the viewport.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return void
	 */
	public function the_viewport() {

		$is_ipad = (bool) ( isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'iPad' ) ); // phpcs:ignore WordPress.Security

		$viewport = '';
		if ( fusion_get_option( 'responsive' ) && $is_ipad ) {
			$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
		} elseif ( fusion_get_option( 'responsive' ) ) {
			if ( Avada()->settings->get( 'mobile_zoom' ) ) {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1" />';
			} else {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
			}
		}

		$viewport = apply_filters( 'avada_viewport_meta', $viewport );

		echo $viewport; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Prints the theme-color meta.
	 *
	 * @access public
	 * @since 5.8
	 * @param string $theme_color The theme-color we want to use.
	 * @return string
	 */
	public function theme_color( $theme_color ) {

		// Exit early if PWA is not enabled.
		$pwa_enabled = Fusion_Settings::get_instance()->get( 'pwa_enable' );
		if ( true === $pwa_enabled || '1' !== $pwa_enabled ) {
			$settings    = Fusion_Settings::get_instance();
			$theme_color = $settings->get( 'pwa_theme_color' );
			return Fusion_Color::new_color( $theme_color )->get_new( 'alpha', 1 )->to_css( 'hex' );
		}
		return $theme_color;
	}
}
