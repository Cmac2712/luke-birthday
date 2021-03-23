<?php
/**
 * A collections of functions.
 *
 * @package Fusion-Library
 * @since 1.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Returns an instance of the Fusion class.
 *
 * @since 1.0.0
 */
function fusion_library() {
	return Fusion::get_instance();
}

/**
 * Returns an instance of the Fusion_Data_Framework class.
 *
 * @since 2.2.0
 * @return Fusion_Data_Framework
 */
function fusion_data() {
	return Fusion_Data_Framework::init();
}

if ( ! function_exists( 'fusion_get_option' ) ) {
	/**
	 * Get theme option or page option.
	 *
	 * @param  string  $option_name Theme option ID.
	 * @param  string  $page_option Page option ID.
	 * @param  integer $post_id     Post/Page ID.
	 * @return string               Theme option or page option value.
	 */
	function fusion_get_option( $option_name, $page_option = false, $post_id = false ) {

		$value       = '';
		$value_found = false;
		$id          = Fusion::get_instance()->get_page_id();
		$is_archive  = ( false !== strpos( $id, 'archive' ) || false === $id );
		$map         = Fusion_Options_Map::get_option_map();

		/**
		 * Tweak for the "mobile_header_bg_color" option.
		 */
		if ( 'mobile_archive_header_bg_color' === $option_name && ! is_archive() ) {
			$option_name = 'mobile_header_bg_color';
		}

		/**
		 * Tweak for blog "page_title_bar".
		 */
		if ( 'page_title_bar' === $option_name && 'post' === get_post_type( $id ) ) {
			$option_name = 'blog_page_title_bar';
		}

		if ( false === strpos( $option_name, '[' ) ) {
			$option_name_located = Fusion_Options_Map::get_option_name_from_theme_option( $option_name );
			if ( is_array( $option_name_located ) ) {
				$value = [];
				foreach ( $option_name_located as $key => $option_id ) {
					$value[ $key ] = fusion_get_option( $option_id );
				}
			}
		}

		/**
		 * Get term options.
		 * Overrides page-option & theme-option.
		 */
		if ( $is_archive ) {
			$tax_value = fusion_data()->term_meta( intval( $id ) )->get( $option_name );
			if ( null !== $tax_value && '' !== $tax_value ) {
				$value_found = true;
				$value       = $tax_value;
			}
		}

		$post_id = ( $post_id ) ? $post_id : $id;

		// If $post_id is not set that means there is a call for a TO and it is still to early for post ID to be set.
		if ( false === $post_id ) {
			$skip = true;
		} else {
			$post_meta = fusion_data()->post_meta( $post_id );

			// Make sure this is not an override that should not be happening.
			// See https://github.com/Theme-Fusion/Avada/issues/8122 for details.
			$skip = (
				( '' === $post_meta->get( 'header_bg_image[url]' ) && in_array( $option_name, [ 'header_bg_repeat', 'header_bg_full' ], true ) ) ||
				( '' === $post_meta->get( 'bg_image[url]' ) && ( 'bg_repeat' === $option_name || 'bg_full' === $option_name ) ) ||
				( '' === $post_meta->get( 'content_bg_image[url]', $post_id ) && in_array( $option_name, [ 'content_bg_repeat', 'content_bg_full' ], true ) )
			);
		}

		/**
		 * Get page options.
		 * Overrides theme-option.
		 */
		$get_page_option = apply_filters( 'fusion_should_get_page_option', ( is_singular() || fusion_is_shop( $post_id ) || ( is_home() && ! is_front_page() ) ) );

		if ( ! $value_found && ! $skip && $get_page_option ) {

			// Get the page-option.
			$page_option = $post_meta->get( $option_name );

			if ( 'default' !== $page_option && false !== $page_option && '' !== $page_option && null !== $page_option ) {
				$value_found = true;
				$value       = $page_option;
			}

			// Tweak for sidebars options.
			$sidebars_options = [
				'pages_sidebar',
				'pages_sidebar_2',
				'posts_sidebar',
				'posts_sidebar_2',
				'portfolio_sidebar',
				'portfolio_sidebar_2',
				'woo_sidebar',
				'woo_sidebar_2',
				'ec_sidebar',
				'ec_sidebar_2',
				'ppbress_sidebar',
				'ppbress_sidebar_2',
			];

			if ( '' === $page_option && in_array( $option_name, $sidebars_options, true ) ) {
				$value_found = true;
				$value       = $page_option;
			}

			// Tweak for show_first_featured_image.
			if ( 'show_first_featured_image' === $option_name && '' === $page_option && 'avada_portfolio' !== get_post_type( $post_id ) ) {
				$value_found = true;
				$value       = true;
			}
		}

		// Get the theme-option value if we couldn't find a value in page-options or taxonomy-options.
		if ( ! $value_found ) {

			/**
			 * Get the theme options.
			 */
			$option_name = Fusion_Options_Map::get_option_name( $option_name, 'theme' );
			$value       = fusion_get_theme_option( $option_name );
		}

		// Tweak values for the "page_title_bar" option - TOs and POs have different formats.
		if ( 'page_title_bar' === $option_name || 'blog_page_title_bar' === $option_name ) {
			$value = strtolower( $value );
			$value = 'yes' === $value ? 'bar_and_content' : $value;
			$value = 'yes_without_bar' === $value ? 'content_only' : $value;
			$value = 'no' === $value ? 'hide' : $value;
		}

		// Tweak values for the "page_title_bar_bs" option - TOs and POs have different formats.
		if ( 'page_title_bar_bs' === $option_name ) {
			$value = strtolower( $value );
			$value = 'searchbar' === $value ? 'search_box' : $value;
		}

		/**
		 * Apply mods for options.
		 */
		if ( is_string( $option_name ) && isset( $map[ $option_name ] ) && isset( $map[ $option_name ]['is_bool'] ) && true === $map[ $option_name ]['is_bool'] ) {
			return ( '1' === $value || 1 === $value || true === $value || 'yes' === $value );
		}
		return apply_filters( 'fusion_get_option', $value, $option_name, $page_option, $post_id );
	}
}

if ( ! function_exists( 'fusion_get_theme_option' ) ) {
	/**
	 * Gets a theme-option value.
	 *
	 * @since 2.0
	 * @param string|array $option The option we want to get. If we use an array, then the 2nd arg is the subset.
	 * @param string       $subset A subset of the option.
	 * @return mixed
	 */
	function fusion_get_theme_option( $option = '', $subset = '' ) {
		if ( is_string( $option ) && false !== strpos( $option, '[' ) ) {
			$option = explode( '[', str_replace( ']', '', $option ) );
		}
		if ( is_array( $option ) ) {
			$subset = ( isset( $option[1] ) && '' === $subset ) ? $option[1] : $subset;
			$option = $option[0];
		}

		if ( '' !== $subset ) {
			return ( class_exists( 'Avada' ) ) ? Avada()->settings->get( $option, $subset ) : fusion_library()->get_option( $option, $subset );
		}
		return ( class_exists( 'Avada' ) ) ? Avada()->settings->get( $option ) : fusion_library()->get_option( $option );
	}
}

if ( ! function_exists( 'fusion_get_page_option' ) ) {
	/**
	 * Get page option value.
	 *
	 * @param  string  $page_option ID of page option.
	 * @param  integer $post_id     Post/Page ID.
	 * @return string               Value of page option.
	 */
	function fusion_get_page_option( $page_option, $post_id = null ) {

		if ( ! $post_id ) {
			$post_id = Fusion::get_instance()->get_page_id();
		}

		// Allow post ID to be filtered depending on page_option.
		$post_id = apply_filters( 'fusion_get_page_option_id', $post_id, $page_option );

		// Allow override which returns early.
		$override = apply_filters( 'fusion_get_page_option_override', null, $post_id, $page_option );
		if ( ! is_null( $override ) ) {
			return $override;
		}

		if ( $page_option && $post_id ) {
			return fusion_data()->post_meta( $post_id )->get( $page_option );
		}
		return false;

	}
}

if ( ! function_exists( 'fusion_render_rich_snippets_for_pages' ) ) {
	/**
	 * Render the full meta data for blog archive and single layouts.
	 *
	 * @param  boolean $title_tag   Set to true to render title rich snippet.
	 * @param  bool    $author_tag  Set to true to render author rich snippet.
	 * @param  bool    $updated_tag Set to true to render updated rich snippet.
	 * @return string               HTML markup to display rich snippets.
	 */
	function fusion_render_rich_snippets_for_pages( $title_tag = true, $author_tag = true, $updated_tag = true ) {
		ob_start();
		include wp_normalize_path( locate_template( 'templates/pages-rich-snippets.php' ) );
		$rich_snippets = ob_get_clean();
		return str_replace( [ "\t", "\n", "\r", "\0", "\x0B" ], '', $rich_snippets );
	}
}

if ( ! function_exists( 'fusion_render_post_metadata' ) ) {
	/**
	 * Render the full meta data for blog archive and single layouts.
	 *
	 * @param string $layout    The blog layout (either single, standard, alternate or grid_timeline).
	 * @param string $settings HTML markup to display the date and post format box.
	 * @return  string
	 */
	function fusion_render_post_metadata( $layout, $settings = [] ) {

		$html     = '';
		$author   = '';
		$date     = '';
		$metadata = '';

		$settings = ( is_array( $settings ) ) ? $settings : [];

		if ( is_search() ) {
			$search_meta = array_flip( fusion_library()->get_option( 'search_meta' ) );

			$default_settings = [
				'post_meta'          => empty( $search_meta ) ? false : true,
				'post_meta_author'   => isset( $search_meta['author'] ),
				'post_meta_date'     => isset( $search_meta['date'] ),
				'post_meta_cats'     => isset( $search_meta['categories'] ),
				'post_meta_tags'     => isset( $search_meta['tags'] ),
				'post_meta_comments' => isset( $search_meta['comments'] ),
				'post_meta_type'     => isset( $search_meta['post_type'] ),
			];
		} else {
			$default_settings = [
				'post_meta'          => fusion_library()->get_option( 'post_meta' ),
				'post_meta_author'   => fusion_library()->get_option( 'post_meta_author' ),
				'post_meta_date'     => fusion_library()->get_option( 'post_meta_date' ),
				'post_meta_cats'     => fusion_library()->get_option( 'post_meta_cats' ),
				'post_meta_tags'     => fusion_library()->get_option( 'post_meta_tags' ),
				'post_meta_comments' => fusion_library()->get_option( 'post_meta_comments' ),
				'post_meta_type'     => false,
			];
		}

		$settings  = wp_parse_args( $settings, $default_settings );
		$post_meta = fusion_data()->post_meta( get_queried_object_id() )->get( 'post_meta' );

		// Check if meta data is enabled.
		if ( ( $settings['post_meta'] && 'no' !== $post_meta ) || ( ! $settings['post_meta'] && 'yes' === $post_meta ) ) {

			// For alternate, grid and timeline layouts return empty single-line-meta if all meta data for that position is disabled.
			if ( in_array( $layout, [ 'alternate', 'grid_timeline' ], true ) && ! $settings['post_meta_author'] && ! $settings['post_meta_date'] && ! $settings['post_meta_cats'] && ! $settings['post_meta_tags'] && ! $settings['post_meta_comments'] && ! $settings['post_meta_type'] ) {
				return '';
			}

			// Render post type meta data.
			if ( $settings['post_meta_type'] ) {
				$metadata .= '<span class="fusion-meta-post-type">' . esc_html( ucwords( get_post_type() ) ) . '</span>';
				$metadata .= '<span class="fusion-inline-sep">|</span>';
			}

			// Render author meta data.
			if ( $settings['post_meta_author'] ) {
				ob_start();
				the_author_posts_link();
				$author_post_link = ob_get_clean();

				// Check if rich snippets are enabled.
				if ( fusion_library()->get_option( 'disable_date_rich_snippet_pages' ) && fusion_library()->get_option( 'disable_rich_snippet_author' ) ) {
					/* translators: The author. */
					$metadata .= sprintf( esc_html__( 'By %s', 'Avada' ), '<span class="vcard"><span class="fn">' . $author_post_link . '</span></span>' );
				} else {
					/* translators: The author. */
					$metadata .= sprintf( esc_html__( 'By %s', 'Avada' ), '<span>' . $author_post_link . '</span>' );
				}
				$metadata .= '<span class="fusion-inline-sep">|</span>';
			} else { // If author meta data won't be visible, render just the invisible author rich snippet.
				$author .= fusion_render_rich_snippets_for_pages( false, true, false );
			}

			// Render the updated meta data or at least the rich snippet if enabled.
			if ( $settings['post_meta_date'] ) {
				$metadata .= fusion_render_rich_snippets_for_pages( false, false, true );

				$formatted_date = get_the_time( fusion_library()->get_option( 'date_format' ) );
				$date_markup    = '<span>' . $formatted_date . '</span><span class="fusion-inline-sep">|</span>';
				$metadata      .= apply_filters( 'fusion_post_metadata_date', $date_markup, $formatted_date );
			} else {
				$date .= fusion_render_rich_snippets_for_pages( false, false, true );
			}

			// Render rest of meta data.
			// Render categories.
			if ( $settings['post_meta_cats'] ) {
				$post_type  = get_post_type();
				$taxonomies = [
					'avada_portfolio' => 'portfolio_category',
					'avada_faq'       => 'faq_category',
					'product'         => 'product_cat',
					'tribe_events'    => 'tribe_events_cat',
				];
				ob_start();
				if ( 'post' === $post_type ) {
					the_category( ', ' );
				} elseif ( 'page' !== $post_type && isset( $taxonomies[ $post_type ] ) ) {
					the_terms( get_the_ID(), $taxonomies[ $post_type ], '', ', ' );
				}
				$categories = ob_get_clean();

				if ( $categories ) {
					/* translators: The categories list. */
					$metadata .= ( $settings['post_meta_tags'] ) ? sprintf( esc_html__( 'Categories: %s', 'Avada' ), $categories ) : $categories;
					$metadata .= '<span class="fusion-inline-sep">|</span>';
				}
			}

			// Render tags.
			if ( $settings['post_meta_tags'] ) {
				ob_start();
				the_tags( '' );
				$tags = ob_get_clean();

				if ( $tags ) {
					/* translators: The tags list. */
					$metadata .= '<span class="meta-tags">' . sprintf( esc_html__( 'Tags: %s', 'Avada' ), $tags ) . '</span><span class="fusion-inline-sep">|</span>';
				}
			}

			// Render comments.
			if ( $settings['post_meta_comments'] && 'grid_timeline' !== $layout ) {
				ob_start();
				comments_popup_link( esc_html__( '0 Comments', 'Avada' ), esc_html__( '1 Comment', 'Avada' ), esc_html__( '% Comments', 'Avada' ) );
				$comments  = ob_get_clean();
				$metadata .= '<span class="fusion-comments">' . $comments . '</span>';
			}

			// Render the HTML wrappers for the different layouts.
			if ( $metadata ) {
				$metadata = $author . $date . $metadata;

				if ( 'single' === $layout ) {
					$html .= '<div class="fusion-meta-info"><div class="fusion-meta-info-wrapper">' . $metadata . '</div></div>';
				} elseif ( in_array( $layout, [ 'alternate', 'grid_timeline' ], true ) ) {
					$html .= '<p class="fusion-single-line-meta">' . $metadata . '</p>';
				} else {
					$html .= '<div class="fusion-alignleft">' . $metadata . '</div>';
				}
			} else {
				$html .= $author . $date;
			}
		} else {
			// Render author and updated rich snippets for grid and timeline layouts.
			if ( fusion_library()->get_option( 'disable_date_rich_snippet_pages' ) ) {
				$html .= fusion_render_rich_snippets_for_pages( false );
			}
		}

		return apply_filters( 'fusion_post_metadata_markup', $html );
	}
}

if ( ! function_exists( 'fusion_calc_color_brightness' ) ) {
	/**
	 * Convert Calculate the brightness of a color.
	 *
	 * @param  string $color Color (Hex) Code.
	 * @return integer brightness level.
	 */
	function fusion_calc_color_brightness( $color ) {

		$brightness_level = 150;
		if ( ! is_string( $color ) ) {
			return $brightness_level;
		}

		if ( in_array( strtolower( $color ), [ 'black', 'navy', 'purple', 'maroon', 'indigo', 'darkslategray', 'darkslateblue', 'darkolivegreen', 'darkgreen', 'darkblue' ], true ) ) {

			$brightness_level = 0;

		} elseif ( 0 === strpos( $color, '#' ) || 0 === strpos( $color, 'rgb' ) || ctype_xdigit( $color ) ) {

			$color            = fusion_hex2rgb( $color );
			$brightness_level = sqrt( pow( $color[0], 2 ) * 0.299 + pow( $color[1], 2 ) * 0.587 + pow( $color[2], 2 ) * 0.114 );

		}

		return (int) round( $brightness_level );
	}
}

if ( ! function_exists( 'fusion_hex2rgb' ) ) {
	/**
	 * Convert Hex Code to RGB.
	 *
	 * @param  string $hex Color Hex Code.
	 * @return array       RGB values.
	 */
	function fusion_hex2rgb( $hex ) {
		if ( false !== strpos( $hex, 'rgb' ) ) {

			$rgb_part = strstr( $hex, '(' );
			$rgb_part = trim( $rgb_part, '(' );
			$rgb_part = rtrim( $rgb_part, ')' );
			$rgb_part = explode( ',', $rgb_part );

			$rgb = [ $rgb_part[0], $rgb_part[1], $rgb_part[2], $rgb_part[3] ];

		} elseif ( 'transparent' === $hex ) {
			$rgb = [ '255', '255', '255', '0' ];
		} else {

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
			$rgb = [ $r, $g, $b ];
		}

		return $rgb; // Returns an array with the rgb values.
	}
}

if ( ! function_exists( 'fusion_render_first_featured_image_markup' ) ) {
	/**
	 * Render the full markup of the first featured image, incl. image wrapper and rollover.
	 *
	 * @param  string  $post_id                   ID of the current post.
	 * @param  string  $post_featured_image_size  Size of the featured image.
	 * @param  string  $post_permalink            Permalink of current post.
	 * @param  boolean $display_placeholder_image Set to true to show an image placeholder.
	 * @param  boolean $display_woo_price         Set to true to show WooCommerce prices.
	 * @param  boolean $display_woo_buttons       Set to true to show WooCommerce buttons.
	 * @param  boolean $display_post_categories   Set to yes to show post categories on rollover.
	 * @param  string  $display_post_title        Controls if the post title will be shown; "default": theme option setting; enable/disable otheriwse.
	 * @param  string  $type                      Type of element the featured image is for. "Related" for related posts is the only type in use so far.
	 * @param  string  $gallery_id                ID of a special gallery the rollover "zoom" link should be connected to for lightbox.
	 * @param  string  $display_rollover          yes|no|force_yes: no disables rollover; force_yes will force rollover even if the Theme Option is set to no.
	 * @param  bool    $display_woo_rating        Whether we want to display ratings or not.
	 * @param  aray    $attributes                Arry with attributes that will be added to the wrapper.
	 * @return string Full HTML markup of the first featured image.
	 */
	function fusion_render_first_featured_image_markup( $post_id, $post_featured_image_size = '', $post_permalink = '', $display_placeholder_image = false, $display_woo_price = false, $display_woo_buttons = false, $display_post_categories = 'default', $display_post_title = 'default', $type = '', $gallery_id = '', $display_rollover = 'yes', $display_woo_rating = false, $attributes = [] ) {

		// Add a class for fixed image size, to restrict the image rollovers to the image width.
		$image_size_class = ( 'full' !== $post_featured_image_size ) ? ' fusion-image-size-fixed' : '';
		$image_size_class = ( ( ! has_post_thumbnail( $post_id ) && fusion_data()->post_meta( $post_id )->get( 'video' ) ) || ( is_home() && 'blog-large' === $post_featured_image_size ) ) ? '' : $image_size_class;

		ob_start();
		/**
		 * WIP
		include wp_normalize_path( locate_template( 'templates/featured-image-first.php' ) );
		*/
		include FUSION_LIBRARY_PATH . '/inc/templates/featured-image-first.php';
		return ob_get_clean();
	}
}

if ( ! function_exists( 'avada_featured_images_lightbox' ) ) {
	/**
	 * The featured images lightbox.
	 *
	 * @param  int $post_id The post ID.
	 * @return string
	 */
	function avada_featured_images_lightbox( $post_id ) {

		global $fusion_settings;
		if ( ! $fusion_settings ) {
			$fusion_settings = Fusion_Settings::get_instance();
		}

		$html            = '';
		$video           = '';
		$featured_images = '';

		$video_url = fusion_data()->post_meta( $post_id )->get( 'video_url', true );

		if ( $video_url ) {
			$video = '<a href="' . $video_url . '" class="iLightbox[gallery' . $post_id . ']"></a>';
		}

		$i = 2;

		$posts_slideshow_number = $fusion_settings->get( 'posts_slideshow_number' );
		if ( ! is_numeric( $posts_slideshow_number ) ) {
			$posts_slideshow_number = 1;
		}

		while ( $i <= $posts_slideshow_number ) :

			$attachment_new_id = fusion_get_featured_image_id( 'featured-image-' . $i, get_post_type( $post_id ) );
			if ( $attachment_new_id ) {
				$attachment_image = wp_get_attachment_image_src( $attachment_new_id, 'full' );
				$full_image       = wp_get_attachment_image_src( $attachment_new_id, 'full' );
				$featured_images .= '<a href="' . $full_image[0] . '" data-rel="iLightbox[gallery' . $post_id . ']" title="' . get_post_field( 'post_title', $attachment_new_id ) . '" data-title="' . get_post_field( 'post_title', $attachment_new_id ) . '" data-caption="' . get_post_field( 'post_excerpt', $attachment_new_id ) . '"></a>';
			}
			$i++;

		endwhile;

		return $html . '<div class="fusion-portfolio-gallery-hidden">' . $video . $featured_images . '</div>';
	}
}

if ( ! function_exists( 'avada_render_rollover' ) ) {
	/**
	 * Output the image rollover
	 *
	 * @param  string  $post_id                    ID of the current post.
	 * @param  string  $post_permalink             Permalink of current post.
	 * @param  boolean $display_woo_price          Set to yes to show´woocommerce price tag for woo sliders.
	 * @param  boolean $display_woo_buttons        Set to yes to show the woocommerce "add to cart" and "show details" buttons.
	 * @param  string  $display_post_categories    Controls if the post categories will be shown; "deafult": theme option setting; enable/disable otheriwse.
	 * @param  string  $display_post_title         Controls if the post title will be shown; "deafult": theme option setting; enable/disable otheriwse.
	 * @param  string  $gallery_id                 ID of a special gallery the rollover "zoom" link should be connected to for lightbox.
	 * @param  bool    $display_woo_rating         Whether we want to display ratings or not.
	 * @return void
	 */
	function avada_render_rollover( $post_id, $post_permalink = '', $display_woo_price = false, $display_woo_buttons = false, $display_post_categories = 'default', $display_post_title = 'default', $gallery_id = '', $display_woo_rating = false ) {
		include FUSION_LIBRARY_PATH . '/inc/templates/rollover.php';
	}
}

add_action( 'avada_rollover', 'avada_render_rollover', 10, 8 );

if ( ! function_exists( 'fusion_get_post_content' ) ) {
	/**
	 * Return the post content, either excerpted or in full length.
	 *
	 * @param  string  $page_id        The id of the current page or post.
	 * @param  string  $excerpt        Can be either 'blog' (for main blog page), 'portfolio' (for portfolio page template) or 'yes' (for shortcodes).
	 * @param  integer $excerpt_length Length of the excerpts.
	 * @param  boolean $strip_html     Can be used by shortcodes for a custom strip html setting.
	 * @return string Post content.
	 **/
	function fusion_get_post_content( $page_id = '', $excerpt = 'blog', $excerpt_length = 55, $strip_html = false ) {

		$content_excerpted = false;

		// Main blog page.
		if ( 'blog' === $excerpt ) {

			// Check if the content should be excerpted.
			if ( 'excerpt' === fusion_get_option( 'content_length' ) ) {
				$content_excerpted = true;

				// Get the excerpt length.
				$excerpt_length = fusion_library()->get_option( 'excerpt_length_blog' );
			}

			// Check if HTML should be stripped from contant.
			if ( fusion_library()->get_option( 'strip_html_excerpt' ) ) {
				$strip_html = true;
			}
		} elseif ( 'search' === $excerpt ) {

			// Check if the content should be excerpted.
			if ( 'excerpt' === fusion_get_option( 'search_content_length' ) ) {
				$content_excerpted = true;

				// Get the excerpt length.
				$excerpt_length = fusion_library()->get_option( 'search_excerpt_length' );
			}

			// Check if HTML should be stripped from contant.
			if ( fusion_library()->get_option( 'search_strip_html_excerpt' ) ) {
				$strip_html = true;
			}
		} elseif ( 'portfolio' === $excerpt ) {
			// Check if the content should be excerpted.
			$portfolio_excerpt_length = fusion_get_portfolio_excerpt_length( $page_id );
			if ( false !== $portfolio_excerpt_length ) {
				$excerpt_length    = $portfolio_excerpt_length;
				$content_excerpted = true;
			}

			// Check if HTML should be stripped from contant.
			if ( fusion_library()->get_option( 'portfolio_strip_html_excerpt' ) ) {
				$strip_html = true;
			}
		} elseif ( 'yes' === $excerpt ) {
			$content_excerpted = true;
		}

		$content_excerpted = apply_filters( 'fusion_post_content_is_excerpted', $content_excerpted );

		// Sermon specific additional content.
		if ( 'wpfc_sermon' === get_post_type( get_the_ID() ) && class_exists( 'Avada' ) ) {
			return Avada()->sermon_manager->get_sermon_content( true );
		}

		// Return excerpted content.
		if ( $content_excerpted ) {
			return fusion_get_post_content_excerpt( $excerpt_length, $strip_html, $page_id );
		}

		// Return full content.
		ob_start();
		the_content();
		return ob_get_clean();

	}
}

if ( ! function_exists( 'fusion_get_post_content_excerpt' ) ) {
	/**
	 * Do the actual custom excerpting for of post/page content.
	 *
	 * @param  string  $limit      Maximum number of words or chars to be displayed in excerpt.
	 * @param  boolean $strip_html Set to TRUE to strip HTML tags from excerpt.
	 * @param  string  $page_id    The id of the current page or post.
	 * @return string               The custom excerpt.
	 **/
	function fusion_get_post_content_excerpt( $limit = 285, $strip_html, $page_id = '' ) {
		global $more;

		// Init variables, cast to correct types.
		$content        = '';
		$read_more      = '';
		$custom_excerpt = false;
		$limit          = intval( $limit );
		$strip_html     = filter_var( $strip_html, FILTER_VALIDATE_BOOLEAN );

		// If excerpt length is set to 0, return empty.
		if ( 0 === $limit ) {
			return $content;
		}

		if ( ! $page_id ) {
			$page_id = get_the_ID();
		}

		$post = get_post( $page_id );

		// If read more for excerpts is not disabled.
		if ( fusion_library()->get_option( 'disable_excerpts' ) ) {

			$read_more_text = fusion_library()->get_option( 'excerpt_read_more_symbol' );
			if ( '' === $read_more_text ) {
				$read_more_text = '&#91;...&#93;';
			}

			// Filter to set the default [...] read more to something arbritary.
			$read_more_text = apply_filters( 'fusion_blog_read_more_excerpt', $read_more_text );

			// Check if the read more [...] should link to single post.
			if ( fusion_library()->get_option( 'link_read_more' ) ) {
				$read_more = ' <a href="' . get_permalink( get_the_ID() ) . '">' . $read_more_text . '</a>';
			} else {
				$read_more = ' ' . $read_more_text;
			}
		}

		// Construct the content.
		// Posts having a custom excerpt.
		if ( has_excerpt( $post->ID ) ) {
			// WooCommerce products should use short description field, which is a custom excerpt.
			if ( 'product' === $post->post_type ) {
				$content = do_shortcode( $post->post_excerpt );

				// Strip tags, if needed.
				if ( $strip_html ) {
					$content = wp_strip_all_tags( $content, '<p>' );
				}
			} else { // All other posts with custom excerpt.
				$content = '<p>' . do_shortcode( get_the_excerpt( $post->ID ) ) . '</p>';
			}
		} else { // All other posts (with and without <!--more--> tag in the contents).
			// HTML tags should be stripped.
			if ( $strip_html ) {
				$content = wp_strip_all_tags( get_the_content( '{{read_more_placeholder}}' ), '<p>' );

				// Strip out all attributes.
				$content = preg_replace( '/<(\w+)[^>]*>/', '<$1>', $content );
				$content = str_replace( '{{read_more_placeholder}}', $read_more, $content );

			} else { // HTML tags remain in excerpt.
				$content = get_the_content( $read_more );
			}

			$pattern = get_shortcode_regex();
			$content = preg_replace_callback( "/$pattern/s", 'fusion_extract_shortcode_contents', $content );

			// <!--more--> tag is used in the post.
			if ( false !== strpos( $post->post_content, '<!--more-->' ) ) {
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );

				if ( $strip_html ) {
					$content = do_shortcode( $content );
				}
			}
		}

		// Limit the contents to the $limit length.
		if ( ! has_excerpt( $post->ID ) || 'product' === $post->post_type ) {
			// Check if the excerpting should be char or word based.
			if ( 'characters' === fusion_get_option( 'excerpt_base' ) ) {
				$content  = mb_substr( $content, 0, $limit );
				$content .= $read_more;
			} else { // Excerpting is word based.
				$content = explode( ' ', $content, $limit + 1 );
				if ( count( $content ) > $limit ) {
					array_pop( $content );
					$content  = implode( ' ', $content );
					$content .= $read_more;

				} else {
					$content = implode( ' ', $content );
				}
			}

			if ( $strip_html ) {
				$content = '<p>' . $content . '</p>';
			} else {
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
			}

			$content = do_shortcode( $content );
		}

		return fusion_force_balance_tags( $content );
	}
}

if ( ! function_exists( 'fusion_get_content_stripped_and_excerpted' ) ) {
	/**
	 * Get the content of the post, strip it and apply any changes required to the excerpt first.
	 *
	 * @since 2.1.1
	 * @param  int    $excerpt_length The length of our excerpt.
	 * @param  string $content        The content.
	 * @return string The  stripped and excerpted content.
	 */
	function fusion_get_content_stripped_and_excerpted( $excerpt_length, $content ) {
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
}

if ( ! function_exists( 'fusion_extract_shortcode_contents' ) ) {
	/**
	 * Extract text contents from all shortcodes for usage in excerpts.
	 *
	 * @param array $m The text.
	 * @return string The shortcode contents
	 */
	function fusion_extract_shortcode_contents( $m ) {

		global $shortcode_tags;

		// Setup the array of all registered shortcodes.
		$shortcodes          = array_keys( $shortcode_tags );
		$no_space_shortcodes = [ 'fusion_dropcap' ];
		$omitted_shortcodes  = [ 'fusion_code', 'fusion_imageframe', 'fusion_slide', 'fusion_syntax_highlighter' ];

		// Extract contents from all shortcodes recursively.
		if ( in_array( $m[2], $shortcodes, true ) && ! in_array( $m[2], $omitted_shortcodes, true ) ) {
			$pattern = get_shortcode_regex();
			// Add space to the excerpt by shortcode, except for those who should stick together, like dropcap.
			$space = ' ';
			if ( in_array( $m[2], $no_space_shortcodes, true ) ) {
				$space = '';
			}
			$content = preg_replace_callback( "/$pattern/s", 'fusion_extract_shortcode_contents', rtrim( $m[5] ) . $space );

			return $content;
		}

		// Allow [[foo]] syntax for escaping a tag.
		if ( '[' === $m[1] && ']' === $m[6] ) {
			return substr( $m[0], 1, -1 );
		}

		return $m[1] . $m[6];
	}
}

/**
 * Returns the excerpt length for portfolio posts.
 *
 * @since 4.0.0
 * @param  string $page_id        The id of the current page or post.
 * @return string/boolean The excerpt length for the post; false if full content should be shown.
 **/
function fusion_get_portfolio_excerpt_length( $page_id = '' ) {
	$excerpt_length = false;

	if ( 'excerpt' === fusion_get_option( 'portfolio_archive_content_length', 'portfolio_content_length', $page_id ) ) {
		// Determine the correct excerpt length.
		if ( fusion_get_page_option( 'portfolio_excerpt', $page_id ) ) {
			$excerpt_length = fusion_get_page_option( 'portfolio_excerpt', $page_id );
		} else {
			$excerpt_length = fusion_library()->get_option( 'portfolio_archive_excerpt_length' );
		}
	} elseif ( ! $page_id && 'excerpt' === fusion_get_option( 'portfolio_archive_content_length' ) ) {
		$excerpt_length = fusion_library()->get_option( 'portfolio_archive_excerpt_length' );
	}

	return $excerpt_length;

}

if ( ! function_exists( 'fusion_link_pages' ) ) {
	/**
	 * Pages links.
	 *
	 * @since 2.2.0
	 * @param array $args An array of arguments we want to pass to the wp_parse_args function.
	 * @return void
	 */
	function fusion_link_pages( $args = '' ) {
		wp_link_pages(
			wp_parse_args(
				$args,
				[
					'before'      => '<div class="page-links pagination"><span class="page-links-title">' . esc_html__( 'Pages:', 'Avada' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span class="page-number">',
					'link_after'  => '</span>',
					'pagelink'    => '%',
				]
			)
		);
	}
}

if ( ! function_exists( 'fusion_link_pages_link' ) ) {
	/**
	 * Returns page link html.
	 *
	 * @since 5.5.0
	 * @param string  $link WP page link html.
	 * @param integer $i    WP page number.
	 * @return string
	 */
	function fusion_link_pages_link( $link, $i ) {
		global $page;

		if ( $i == $page ) { // phpcs:ignore WordPress.PHP.StrictComparisons
			$link = '<span class="current">' . $i . '</span>';
		}

		return $link;
	}
}
add_filter( 'wp_link_pages_link', 'fusion_link_pages_link', 99, 2 );

if ( ! function_exists( 'fusion_cached_query' ) ) {
	/**
	 * Returns a cached query.
	 * If the query is not cached then it caches it and returns the result.
	 *
	 * @param string|array $args Same as in WP_Query.
	 * @return object
	 */
	function fusion_cached_query( $args ) {

		// Make sure cached queries are not language agnostic.
		if ( is_array( $args ) ) {
			$args['fusion_lang'] = Fusion_Multilingual::get_active_language();
		} else {
			$args .= '&fusion_lang=' . Fusion_Multilingual::get_active_language();
		}

		$query_id = md5( maybe_serialize( $args ) );
		$query    = wp_cache_get( $query_id, 'fusion_library' );
		if ( false === $query ) {
			$query = new WP_Query( $args );
			wp_cache_set( $query_id, $query, 'fusion_library' );
		}
		return $query;
	}
}

if ( ! function_exists( 'fusion_flush_object_cache' ) ) {
	/**
	 * Deletes WP object cache.
	 *
	 * @since 1.2
	 * @return void
	 */
	function fusion_flush_object_cache() {
		wp_cache_flush();
	}
}
add_action( 'save_post', 'fusion_flush_object_cache' );
add_action( 'delete_post', 'fusion_flush_object_cache' );

if ( ! function_exists( 'fusion_cached_get_posts' ) ) {
	/**
	 * Returns a cached query.
	 * If the query is not cached then it caches it and returns the result.
	 *
	 * @param string|array $args Same as in WP_Query.
	 * @return array
	 */
	function fusion_cached_get_posts( $args ) {
		$query = fusion_cached_query( $args );
		return $query->posts;
	}
}

if ( ! function_exists( 'fusion_get_user_locale' ) ) {
	/**
	 * Retrieves the locale of a user.
	 * If using WordPress 4.7+ uses get_user_locale.
	 * If using WordPress 4.7- uses get_locale.
	 * If the user has a locale set to a non-empty string then it will be
	 * returned. Otherwise it returns the locale of get_locale().
	 *
	 * @since 5.1
	 * @deprecated 6.1 Avada now required WP 4.7+ so this is now just an alias.
	 * @uses get_user_locale
	 * @uses get_locale
	 * @param int|WP_User $user_id User's ID or a WP_User object. Defaults to current user.
	 * @return string The locale of the user.
	 */
	function fusion_get_user_locale( $user_id = 0 ) {
		_deprecated_function( 'fusion_get_user_locale', 'Avada 6.1', 'get_user_locale' );
		return get_user_locale( $user_id );
	}
}

if ( ! function_exists( 'fusion_get_featured_image_id' ) ) {
	/**
	 * Gets the ID of the featured image.
	 *
	 * @since 1.1.0
	 * @param int|string $image_id  The image ID.
	 * @param string     $post_type The post-type.
	 * @param int|string $post_id   The post-ID.
	 * @return int
	 */
	function fusion_get_featured_image_id( $image_id, $post_type, $post_id = null ) {
		return Fusion_Featured_Image::get_featured_image_id( $image_id, $post_type, $post_id );
	}
}

if ( ! function_exists( 'fusion_pagination' ) ) {
	/**
	 * Number based pagination.
	 *
	 * @since 1.3
	 * @param string|int $max_pages       Maximum number of pages.
	 * @param integer    $range           How many page numbers to display to either side of the current page.
	 * @param string     $current_query   The current query.
	 * @param bool       $infinite_scroll Whether we want infinite scroll or not.
	 * @param bool       $is_element      Whether pagination is definitely only set for a specific element.
	 * @return string                     The pagination markup.
	 */
	function fusion_pagination( $max_pages = '', $range = 1, $current_query = '', $infinite_scroll = false, $is_element = false ) {
		global $paged, $wp_query, $fusion_settings;

		$range       = apply_filters( 'fusion_pagination_size', $range );
		$start_range = apply_filters( 'fusion_pagination_start_end_size', $fusion_settings->get( 'pagination_start_end_range' ) );
		$end_range   = apply_filters( 'fusion_pagination_start_end_size', $fusion_settings->get( 'pagination_start_end_range' ) );

		if ( '' === $current_query ) {
			$current_page = ( empty( $paged ) ) ? 1 : $paged;
		} else {
			$current_page = $current_query->query_vars['paged'];
		}

		if ( '' === $max_pages ) {
			if ( '' === $current_query ) {
				$max_pages = $wp_query->max_num_pages;
				$max_pages = ( ! $max_pages ) ? 1 : $max_pages;
			} else {
				$max_pages = $current_query->max_num_pages;
			}
		}
		$max_pages    = intval( $max_pages );
		$current_page = intval( $current_page );
		$output       = '';

		if ( 1 !== $max_pages ) {
			if ( $infinite_scroll || ( ! $is_element && ( ( 'pagination' !== $fusion_settings->get( 'blog_pagination_type' ) && ( is_home() || ( 'post' === get_post_type() && ( is_author() || is_archive() ) ) ) ) || ( 'pagination' !== fusion_get_option( 'search_pagination_type' ) && is_search() ) || ( 'pagination' !== $fusion_settings->get( 'portfolio_archive_pagination_type' ) && ( is_post_type_archive( 'avada_portfolio' ) || is_tax( 'portfolio_category' ) || is_tax( 'portfolio_skills' ) || is_tax( 'portfolio_tags' ) ) ) ) ) ) {
				$output .= '<div class="fusion-infinite-scroll-trigger"></div>';
				$output .= '<div class="pagination infinite-scroll clearfix" style="display:none;">';
			} else {
				$output .= '<div class="pagination clearfix">';
			}

			$start = $current_page - $range;
			$end   = $current_page + $range;
			if ( 0 >= $start ) {
				$start = ( 0 < $current_page - 1 ) ? $current_page - 1 : 1;
			}

			if ( $max_pages < $end ) {
				$end = $max_pages;
			}

			if ( 1 < $current_page ) {
				$output .= '<a class="pagination-prev" href="' . esc_url( get_pagenum_link( $current_page - 1 ) ) . '">';
				$output .= '<span class="page-prev"></span>';
				$output .= '<span class="page-text">' . esc_html__( 'Previous', 'Avada' ) . '</span>';
				$output .= '</a>';

				if ( 0 < $start_range ) {
					if ( $start_range >= $start ) {
						$start_range = $start - 1;
					}

					for ( $i = 1; $i <= $start_range; $i++ ) {
						$output .= '<a href="' . esc_url( get_pagenum_link( $i ) ) . '" class="inactive">' . absint( $i ) . '</a>';
					}

					if ( 0 < $start_range && $start_range < $start - 1 ) {
						$output .= '<span class="pagination-dots paginations-dots-start">&middot;&middot;&middot;</span>';
					}
				}
			}

			for ( $i = $start; $i <= $end; $i++ ) {
				if ( $current_page === $i ) {
					$output .= '<span class="current">' . absint( $i ) . '</span>';
				} else {
					$output .= '<a href="' . esc_url( get_pagenum_link( $i ) ) . '" class="inactive">' . absint( $i ) . '</a>';
				}
			}

			if ( $current_page < $max_pages ) {

				if ( 0 < $end_range ) {

					if ( $max_pages - $end_range <= $end ) {
						$end_range = $max_pages - $end;
					}

					$end_range--;

					if ( $end + 1 < $max_pages - $end_range ) {
						$output .= '<span class="pagination-dots paginations-dots-end">&middot;&middot;&middot;</span>';
					}

					for ( $i = $max_pages - $end_range; $i <= $max_pages; $i++ ) {
						$output .= '<a href="' . esc_url( get_pagenum_link( $i ) ) . '" class="inactive">' . absint( $i ) . '</a>';
					}
				}

				$output .= '<a class="pagination-next" href="' . esc_url( get_pagenum_link( $current_page + 1 ) ) . '">';
				$output .= '<span class="page-text">' . esc_html__( 'Next', 'Avada' ) . '</span>';
				$output .= '<span class="page-next"></span>';
				$output .= '</a>';
			}

			$output .= '</div>';
			$output .= '<div class="fusion-clearfix"></div>';
		}

		return $output;

		// Needed for Theme check.
		ob_start(); // phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
		posts_nav_link(); // phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
		ob_get_clean(); // phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
	}
}

if ( ! function_exists( 'fusion_get_referer' ) ) {
	/**
	 * Gets the HTTP referer.
	 *
	 * @since 1.7
	 * @return string|false
	 */
	function fusion_get_referer() {
		$referer = wp_get_referer();
		if ( ! $referer ) {
			$referer = wp_get_raw_referer();
		}
		return $referer;
	}
}

if ( ! function_exists( 'fusion_is_color_transparent' ) ) {
	/**
	 * Figure out if a color is transparent or not.
	 *
	 * @since 2.0
	 * @param string $color The color we want to check.
	 * @return bool
	 */
	function fusion_is_color_transparent( $color ) {
		$color = trim( $color );
		if ( 'transparent' === $color ) {
			return true;
		}
		return ( 0 === Fusion_Color::new_color( $color )->alpha );
	}
}

if ( ! function_exists( 'fusion_the_admin_font_async' ) ) {
	/**
	 * Adds the font used for the admin UI asyncronously.
	 *
	 * @since 2.0
	 * @return void
	 */
	function fusion_the_admin_font_async() {
		echo '<style>';
		include FUSION_LIBRARY_PATH . '/inc/fusion-app/css/noto-sans.css';
		echo '</style>';
	}
}


if ( ! function_exists( 'fusion_doing_ajax' ) ) {
	/**
	 * Wrapper function for wp_doing_ajax, which was introduced in WP 4.7.
	 *
	 * @since 5.1.5
	 */
	function fusion_doing_ajax() {
		if ( function_exists( 'wp_doing_ajax' ) ) {
			return wp_doing_ajax();
		}

		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( ! function_exists( 'fusion_is_shop' ) ) {
	/**
	 * Returns true when viewing the product type archive (shop).
	 *
	 * @since 1.8
	 * @param integer/string $current_page_id Post/Page ID.
	 * @return bool Theme option or page option value.
	 */
	function fusion_is_shop( $current_page_id ) {
		$current_page_id      = (int) $current_page_id;
		$front_page_id        = (int) get_option( 'page_on_front' );
		$shop_page_id         = (int) apply_filters( 'woocommerce_get_shop_page_id', get_option( 'woocommerce_shop_page_id' ) );
		$is_static_front_page = 'page' === get_option( 'show_on_front' );

		if ( ( $is_static_front_page && $front_page_id === $current_page_id ) || is_null( get_queried_object() ) || ( class_exists( 'BuddyPress' ) && bp_is_user() ) ) {
			$is_shop_page = ( $current_page_id === $shop_page_id ) ? true : false;
		} else {
			$is_shop_page = function_exists( 'is_shop' ) && is_shop();
		}

		return $is_shop_page;
	}
}

if ( ! function_exists( 'fusion_get_google_maps_language_code' ) ) {
	/**
	 * Returns the correct Google maps language code.
	 *
	 * @since 1.9
	 * @return string The correct Google maps language code.
	 */
	function fusion_get_google_maps_language_code() {
		$lang_codes  = [ 'en_Au', 'en_GB', 'pt_BR', 'pt_PT', 'zh_CN', 'zh_TW' ];
		$lang_locale = get_locale();
		$lang_code   = in_array( $lang_locale, $lang_codes, true ) ? str_replace( '_', '-', $lang_locale ) : substr( get_locale(), 0, 2 );

		return $lang_code;
	}
}

if ( ! function_exists( 'wp_body_open' ) ) {
	/**
	 * Polyfill for the WP wp_body_open function added in WP 5.2.
	 *
	 * @since 2.0
	 * @return void
	 */
	function wp_body_open() {
		do_action( 'wp_body_open' );
	}
}

if ( ! function_exists( 'fusion_get_social_icons_class' ) ) {
	/**
	 * Return the social icons object.
	 *
	 * @since 1.9.2
	 * @return Fusion_Social_Icons
	 */
	function fusion_get_social_icons_class() {
		global $social_icons;

		if ( ! $social_icons ) {
			$social_icons = new Fusion_Social_Icons();
		}

		return $social_icons;
	}
}

if ( ! function_exists( 'fusion_reset_all_caches' ) ) {
	/**
	 * Reset all Fusion Caches.
	 *
	 * @since 1.9.2
	 * @param array $delete_cache An array of caches to delete.
	 * @return void
	 */
	function fusion_reset_all_caches( $delete_cache = [] ) {
		// Reset fusion-caches.
		if ( ! class_exists( 'Fusion_Cache' ) ) {
			require_once FUSION_LIBRARY_PATH . '/inc/class-fusion-cache.php';
		}

		$fusion_cache = new Fusion_Cache();
		$fusion_cache->reset_all_caches( $delete_cache );

		wp_cache_flush();
	}
}

if ( ! function_exists( 'fusion_is_plugin_activated' ) ) {
	/**
	 * Reset all Fusion Caches.
	 *
	 * @since 1.9.2
	 * @param string $plugin Name of the plugin that should be checked.
	 * @return bool If plugin is active or not.
	 */
	function fusion_is_plugin_activated( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', [] ), true ) || is_plugin_active_for_network( $plugin );
	}
}

if ( ! function_exists( 'fusion_encode_input' ) ) {
	/**
	 * Encode function wrapper.
	 *
	 * @since 1.9.2
	 * @param string $input Input that should be encoded.
	 * @param string $method The encoding mathod.
	 * @return string Encoded input.
	 */
	function fusion_encode_input( $input, $method = 'base64' ) {
		$method = in_array( $method, [ 'base64', 'utf8' ], true ) ? $method . '_encode' : $method;
		return $method( $input );
	}
}

if ( ! function_exists( 'fusion_decode_input' ) ) {
	/**
	 * Decode function wrapper.
	 *
	 * @since 1.9.2
	 * @param string $input Input that should be decoded.
	 * @param string $method The decoding mathod.
	 * @return string Decoded input.
	 */
	function fusion_decode_input( $input, $method = 'base64' ) {
		$method = in_array( $method, [ 'base64', 'utf8' ], true ) ? $method . '_decode' : $method;
		return $method( $input );
	}
}

if ( ! function_exists( 'fusion_decode_if_needed' ) ) {
	/**
	 * Check if input needs decoded and do so.
	 *
	 * @since 1.9.2
	 * @param string $input Input that should be decoded.
	 * @param string $method The decoding mathod.
	 * @return string Decoded input.
	 */
	function fusion_decode_if_needed( $input, $method = 'base64' ) {
		$encode = $method;
		$decode = $method;
		if ( in_array( $method, [ 'base64', 'utf8' ], true ) ) {
			$encode .= '_encode';
			$decode .= '_decode';
		}

		if ( fusion_encode_input( fusion_decode_input( $input, $decode ), $encode ) === $input ) {
			$input = fusion_decode_input( $input, $decode );
		}

		return $input;
	}
}

if ( ! function_exists( 'fusion_should_defer_styles_loading' ) ) {
	/**
	 * Figure out if we want to defer loading styles to the footer or not.
	 *
	 * @since 2.0
	 * @return bool
	 */
	function fusion_should_defer_styles_loading() {
		$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );
		return $is_builder ? false : (bool) fusion_get_option( 'defer_styles' );
	}
}

if ( ! function_exists( 'fusion_force_balance_tags' ) ) {
	/**
	 * Use DOMDocument to do a more robust job at force_balance_tags.
	 *
	 * "force_balance_tags() is not a really safe function. It doesn’t use an HTML parser
	 * but a bunch of potentially expensive regular expressions. You should use it only if
	 * you control the length of the excerpt too. Otherwise you could run into memory issues
	 * or some obscure bugs." <http://wordpress.stackexchange.com/a/89169/8521>
	 *
	 * For more reasons why to not use regular expressions on markup, see http://stackoverflow.com/a/1732454/93579
	 *
	 * @since 2.0.3
	 * @link http://wordpress.stackexchange.com/questions/89121/why-doesnt-default-wordpress-page-view-use-force-balance-tags
	 * @see force_balance_tags()
	 *
	 * @param string $markup Markup.
	 * @return string Balanced markup.
	 */
	function fusion_force_balance_tags( $markup ) {

		// Sanity check with fallback to default force_balance_tags function.
		if ( ! class_exists( 'DOMDocument' ) || ! function_exists( 'libxml_use_internal_errors' ) ) {
			return force_balance_tags( $markup );
		}

		$libxml_previous_state = libxml_use_internal_errors( true );

		$dom  = new DOMDocument();
		$html = sprintf(
			'<html><head><meta http-equiv="content-type" content="text/html; charset=%1$s"></head><body>%2$s</body></html>',
			esc_attr( get_bloginfo( 'charset' ) ),
			$markup
		);

		$dom->loadHTML( $html );
		$body   = $dom->getElementsByTagName( 'body' )->item( 0 );
		$markup = str_replace( [ '<body>', '</body>' ], '', $dom->saveHTML( $body ) );

		libxml_clear_errors();
		libxml_use_internal_errors( $libxml_previous_state );
		return $markup;
	}
}

if ( ! function_exists( 'fusion_element_attributes' ) ) {
	/**
	 * Prints element attributes.
	 *
	 * @since 2.2.0
	 * @param string $el     The element.
	 * @param array  $args   Extra arguments.
	 * @param bool   $return Whether the result should be returned or echoed.
	 * @return string        Returns the attributes. If $return is false then it echoes the result.
	 */
	function fusion_element_attributes( $el = '', $args = [], $return = false ) {
		$attrs = [];
		$args  = apply_filters( 'fusion_element_attributes_args', $args, $el );
		foreach ( $args as $prop => $val ) {
			$attrs[] = esc_attr( $prop ) . '"' . esc_attr( $val ) . '"';
		}

		if ( ! $return ) {
			echo esc_html( implode( ' ', $attrs ) );
		}
		return implode( ' ', $attrs );
	}
}

