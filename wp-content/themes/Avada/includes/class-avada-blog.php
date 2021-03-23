<?php
/**
 * Blog mods.
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
 * The Avada_Blog class.
 */
class Avada_Blog {

	/**
	 * Constructor.
	 *
	 * @access  public
	 */
	public function __construct() {

		add_filter( 'excerpt_length', [ $this, 'excerpt_length' ], 999 );
		add_action( 'pre_get_posts', [ $this, 'alter_search_loop' ], 1 );
	}

	/**
	 * Modify the default excerpt length.
	 *
	 * @param  int $length The excerpt length.
	 * @return  int
	 */
	public function excerpt_length( $length ) {

		// Normal blog posts excerpt length.
		if ( ! is_null( Avada()->settings->get( 'excerpt_length_blog' ) ) ) {
			$length = Avada()->settings->get( 'excerpt_length_blog' );
		}

		// Search results excerpt length.
		if ( is_search() ) {
			$length = Avada()->settings->get( 'excerpt_length_blog' );
		}

		return $length;

	}

	/**
	 * Get the post (excerpt).
	 *
	 * @return void Content is directly echoed.
	 */
	public function render_post_content() {

		if ( is_search() ) {
			echo fusion_get_post_content( '', 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo fusion_get_post_content(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Apply post per page on search pages.
	 *
	 * @param  object $query The WP_Query object.
	 * @return  void
	 */
	public function alter_search_loop( $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_search() && Avada()->settings->get( 'search_results_per_page' ) ) {
			$query->set( 'posts_per_page', Avada()->settings->get( 'search_results_per_page' ) );
		}
	}

	/**
	 * Get the content of the post
	 * strip it and apply any changes required to the excerpt first.
	 *
	 * @param  int    $excerpt_length The length of our excerpt.
	 * @param  string $content        The content.
	 */
	public function get_content_stripped_and_excerpted( $excerpt_length, $content ) {
		$pattern = get_shortcode_regex();
		$content = preg_replace_callback( "/$pattern/s", 'fusion_extract_shortcode_contents', $content );
		$content = explode( ' ', $content, $excerpt_length + 1 );

		if ( $excerpt_length < count( $content ) ) {
			array_pop( $content );
		}

		$content = implode( ' ', $content );
		$content = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', $content ); // Strip shortcodes and keep the content.
		$content = str_replace( ']]>', ']]&gt;', $content );
		$content = strip_tags( $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		$content = str_replace( [ '"', "'" ], [ '&quot;', '&#39;' ], $content );
		$content = trim( $content );

		return $content;

	}

	/**
	 * Retrieve the content and apply and read-more modifications needed.
	 *
	 * @param  int  $limit      The limit we've set for our content.
	 * @param  bool $strip_html If we want to strip HTML from our content.
	 */
	public function content( $limit, $strip_html ) {

		global $more;

		$content = '';

		// Sanitizing the limit value.
		$limit = ( ! $limit && 0 !== $limit && '0' !== $limit ) ? 285 : intval( $limit );

		$test_strip_html = ( 'true' === $strip_html || true === $strip_html );

		$custom_excerpt = false;

		$post = get_post( get_the_ID() );

		$pos = strpos( $post->post_content, '<!--more-->' );

		$readmore = ( Avada()->settings->get( 'link_read_more' ) ) ? ' <a href="' . get_permalink( get_the_ID() ) . '">&#91;...&#93;</a>' : ' &#91;...&#93;';
		$readmore = ( ! Avada()->settings->get( 'disable_excerpts' ) ) ? '' : $readmore;

		if ( $test_strip_html ) {

			$more        = 0; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$raw_content = wp_strip_all_tags( get_the_content( '{{read_more_placeholder}}' ), '<p>' );

			// Strip out all attributes.
			$raw_content = preg_replace( '/<(\w+)[^>]*>/', '<$1>', $raw_content );

			$raw_content = str_replace( '{{read_more_placeholder}}', $readmore, $raw_content );

			if ( $post->post_excerpt || false !== $pos ) {
				$raw_content    = ( ! $pos ) ? wp_strip_all_tags( rtrim( get_the_excerpt(), '[&hellip;]' ), '<p>' ) . $readmore : $raw_content;
				$custom_excerpt = true;
			}
		} else {

			$more        = 0; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$raw_content = get_the_content( $readmore );
			if ( $post->post_excerpt || false !== $pos ) {
				$raw_content    = ( ! $pos ) ? rtrim( get_the_excerpt(), '[&hellip;]' ) . $readmore : $raw_content;
				$custom_excerpt = true;
			}
		}

		if ( $raw_content && ! $custom_excerpt ) {

			$pattern = get_shortcode_regex();
			$content = preg_replace_callback( "/$pattern/s", 'fusion_extract_shortcode_contents', $raw_content );

			if ( 'characters' === fusion_get_option( 'excerpt_base' ) ) {

				$content  = mb_substr( $content, 0, $limit );
				$content .= ( 0 !== $limit && Avada()->settings->get( 'disable_excerpts' ) ) ? $readmore : '';

			} else {

				$content = explode( ' ', $content, $limit + 1 );

				if ( $limit < count( $content ) ) {

					array_pop( $content );
					$content = implode( ' ', $content );
					if ( Avada()->settings->get( 'disable_excerpts' ) ) {
						$content .= ( 0 !== $limit ) ? $readmore : '';
					}
				} else {

					$content = implode( ' ', $content );

				}
			}

			if ( 0 !== $limit && ! $test_strip_html ) {

				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );

			} else {
				$content = '<p>' . $content . '</p>';
			}

			$strip_html_class = ( $test_strip_html ) ? 'strip-html' : '';
			$content          = '<div class="excerpt-container ' . $strip_html_class . '">' . do_shortcode( $content ) . '</div>';

			return $content;

		}

		if ( $custom_excerpt ) {

			$pattern = get_shortcode_regex();
			$content = preg_replace_callback( "/$pattern/s", 'fusion_extract_shortcode_contents', $raw_content );

			if ( $test_strip_html ) {

				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				$content = '<div class="excerpt-container strip-html">' . do_shortcode( $content ) . '</div>';

			} else {

				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );

			}
		}

		if ( has_excerpt() ) {

			$content = do_shortcode( get_the_excerpt() );
			$content = '<p>' . $content . '</p>';

		}

		return $content;

	}

	/**
	 * Get the blog layout for the current page template.
	 *
	 * @return string The correct layout name for the blog post class.
	 */
	public function get_blog_layout() {
		$theme_options_blog_var = '';

		if ( is_home() ) {
			$theme_options_blog_var = 'blog_layout';
		} elseif ( is_search() ) {
			$theme_options_blog_var = 'search_layout';
		} elseif ( is_archive() || is_author() ) {
			$theme_options_blog_var = 'blog_archive_layout';
		}

		return str_replace( ' ', '-', Avada()->settings->get( $theme_options_blog_var ) );
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
