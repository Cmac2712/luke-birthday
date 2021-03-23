<?php
/**
 * Functions for retrieving dynamic data values.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion Builder
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * A wrapper for static methods.
 */
class Fusion_Dynamic_Data_Callbacks {

	/**
	 * Post ID for callbacks to use.
	 *
	 * @access public
	 * @var array
	 */
	public $post_data = [];

	/**
	 * Class constructor.
	 *
	 * @since 2.1
	 * @access public
	 */
	public function __construct() {
		add_action( 'wp_ajax_ajax_acf_get_field', [ $this, 'ajax_acf_get_field' ] );
		add_action( 'wp_ajax_ajax_get_post_date', [ $this, 'ajax_get_post_date' ] );

		add_action( 'wp_ajax_ajax_dynamic_data_default_callback', [ $this, 'ajax_dynamic_data_default_callback' ] );
	}

	/**
	 * Returns the post-ID.
	 *
	 * @since 6.2.0
	 * @return int
	 */
	public static function get_post_id() {

		if ( fusion_doing_ajax() && isset( $_GET['fusion_load_nonce'] ) && isset( $_GET['post_id'] ) ) {
			check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
			$post_id = sanitize_text_field( wp_unslash( $_GET['post_id'] ) );
		} else {
			$post_id = fusion_library()->get_page_id();
		}

		return apply_filters( 'fusion_dynamic_post_id', $post_id );
	}

	/**
	 * Get ACF field value.
	 *
	 * @since 2.1
	 */
	public function ajax_acf_get_field() {
		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
		$return_data = [];

		if ( isset( $_POST['field'] ) && isset( $_POST['post_id'] ) && function_exists( 'get_field' ) ) {
			$field_value = get_field( wp_unslash( $_POST['field'] ), wp_unslash( $_POST['post_id'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( ! isset( $_POST['image'] ) || ! $_POST['image'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$return_data['content'] = $field_value;
			} elseif ( is_array( $field_value ) && isset( $field_value['url'] ) ) {
				$return_data['content'] = $field_value['url'];
			} elseif ( is_integer( $field_value ) ) {
				$return_data['content'] = wp_get_attachment_url( $field_value );
			} elseif ( is_string( $field_value ) ) {
				$return_data['content'] = $field_value;
			}
		}

		echo wp_json_encode( $return_data );
		wp_die();
	}

	/**
	 * Runs the defined callback.
	 *
	 * @access public
	 * @since 2.1
	 * @return void
	 */
	public function ajax_dynamic_data_default_callback() {
		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
		$return_data = [];

		$callback_function = ( isset( $_GET['callback'] ) ) ? sanitize_text_field( wp_unslash( $_GET['callback'] ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( $callback_function && is_callable( 'Fusion_Dynamic_Data_Callbacks::' . $callback_function ) && isset( $_GET['post_id'] ) && isset( $_GET['args'] ) ) {
			$return_data['content'] = call_user_func_array( 'Fusion_Dynamic_Data_Callbacks::' . $callback_function, [ wp_unslash( $_GET['args'] ), apply_filters( 'fusion_dynamic_post_id', wp_unslash( $_GET['post_id'] ) ) ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		echo wp_json_encode( $return_data );
		wp_die();

	}

	/**
	 * Shortcode.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param arrsy $args Arguments.
	 * @return string
	 */
	public static function dynamic_shortcode( $args ) {
		(string) $shortcode_string = isset( $args['shortcode'] ) ? $args['shortcode'] : '';
		return do_shortcode( $shortcode_string );
	}

	/**
	 * Featured image.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param arrsy $args Arguments.
	 * @return string
	 */
	public static function post_featured_image( $args ) {
		return get_the_post_thumbnail_url( self::get_post_id() );
	}

	/**
	 * Get post or archive title.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_object_title( $args ) {
		$include_context = ( isset( $args['include_context'] ) && 'yes' === $args['include_context'] ) ? true : false;

		if ( is_search() ) {
			/* translators: The search keyword(s). */
			$title = sprintf( __( 'Search: %s', 'fusion-builder' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				/* translators: %s is the page number. */
				$title .= sprintf( __( '&nbsp;&ndash; Page %s', 'fusion-builder' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_category() ) {
			$title = single_cat_title( '', false );

			if ( $include_context ) {
				/* translators: Category archive title. */
				$title = sprintf( __( 'Category: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_tag() ) {
			$title = single_tag_title( '', false );
			if ( $include_context ) {
				/* translators: Tag archive title. */
				$title = sprintf( __( 'Tag: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_author() ) {
			$title = get_the_author();

			if ( $include_context ) {
				/* translators: Author archive title. */
				$title = sprintf( __( 'Author: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_year() ) {
			$title = get_the_date( _x( 'Y', 'yearly archives date format', 'fusion-builder' ) );

			if ( $include_context ) {
				/* translators: Yearly archive title. */
				$title = sprintf( __( 'Year: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_month() ) {
			$title = get_the_date( _x( 'F Y', 'monthly archives date format', 'fusion-builder' ) );

			if ( $include_context ) {
				/* translators: Monthly archive title. */
				$title = sprintf( __( 'Month: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_day() ) {
			$title = get_the_date( _x( 'F j, Y', 'daily archives date format', 'fusion-builder' ) );

			if ( $include_context ) {
				/* translators: Daily archive title. */
				$title = sprintf( __( 'Day: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_tax( 'post_format' ) ) {
			if ( is_tax( 'post_format', 'post-format-aside' ) ) {
				$title = _x( 'Asides', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
				$title = _x( 'Galleries', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
				$title = _x( 'Images', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
				$title = _x( 'Videos', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
				$title = _x( 'Quotes', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
				$title = _x( 'Links', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
				$title = _x( 'Statuses', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
				$title = _x( 'Audio', 'post format archive title', 'fusion-builder' );
			} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
				$title = _x( 'Chats', 'post format archive title', 'fusion-builder' );
			}
		} elseif ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );

			if ( $include_context ) {
				/* translators: Post type archive title. */
				$title = sprintf( __( 'Archives: %s', 'fusion-builder' ), $title );
			}
		} elseif ( is_tax() ) {
			$title = single_term_title( '', false );

			if ( $include_context ) {
				$tax = get_taxonomy( get_queried_object()->taxonomy );

				if ( $tax ) {
					/* translators: Taxonomy term archive title. %1$s: Taxonomy singular name, %2$s: Current taxonomy term. */
					$title = sprintf( __( '%1$s: %2$s', 'fusion-builder' ), $tax->labels->singular_name, $title );
				}
			}
		} elseif ( is_archive() ) {
			$title = __( 'Archives', 'fusion-builder' );
		} elseif ( is_404() ) {
			$title = __( '404', 'fusion-builder' );
		} else {
			/* translators: %s: Search term. */
			$title = get_the_title( self::get_post_id() );

			if ( $include_context ) {
				$post_type_obj = get_post_type_object( get_post_type( self::get_post_id() ) );

				if ( $post_type_obj ) {
					/* translators: %1$s: Post Object Label. %2$s: Post Title. */
					$title = sprintf( '%s: %s', $post_type_obj->labels->singular_name, $title );
				}
			}
		}

		return $title;
	}

	/**
	 * Post ID.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return int
	 */
	public static function fusion_get_post_id( $args ) {
		return self::get_post_id();
	}

	/**
	 * Get post excerpt or archive description.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return int
	 */
	public static function fusion_get_object_excerpt( $args ) {
		return is_archive() ? get_the_archive_description() : get_the_excerpt( self::get_post_id() );
	}

	/**
	 * Post date.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_post_date( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$format = isset( $args['format'] ) ? $args['format'] : '';
		return 'modified' === $args['type'] ? get_the_modified_date( $format, $post_id ) : get_the_date( $format, $post_id );
	}

	/**
	 * Current date.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_date( $args ) {
		$format = isset( $args['format'] ) ? $args['format'] : '';
		return date( $format );
	}

	/**
	 * Get dynamic heading.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_dynamic_heading( $args, $post_id = 0 ) {
		$title = self::fusion_get_dynamic_option( $args, $post_id );
		if ( ! $title ) {
			$title = self::fusion_get_object_title( $args );
		}
		return $title;
	}

	/**
	 * Get Dynamic Content Page Option.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_dynamic_option( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$post_type   = get_post_type( $post_id );
		$pause_metas = ( 'fusion_tb_section' === $post_type || ( isset( $_POST['meta_values'] ) && strpos( $_POST['meta_values'], 'dynamic_content_preview_type' ) ) ); // phpcs:ignore WordPress.Security

		if ( $pause_metas ) {
			do_action( 'fusion_pause_meta_filter' );
		}

		$data = fusion_get_page_option( $args['data'], $post_id );

		if ( $pause_metas ) {
			do_action( 'fusion_resume_meta_filter' );
		}

		// For image data.
		if ( is_array( $data ) && isset( $data['url'] ) ) {
			$data = $data['url'];
		}

		return $data;
	}

	/**
	 * Post time.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_post_time( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$format = isset( $args['format'] ) && '' !== $args['format'] ? $args['format'] : 'U';
		return get_post_time( $format, false, $post_id );
	}

	/**
	 * Post terms.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_post_terms( $args, $post_id = 0 ) {
		$output = '';
		if ( ! isset( $args['type'] ) ) {
			return $output;
		}

		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$terms = wp_get_object_terms( $post_id, $args['type'] );
		if ( ! is_wp_error( $terms ) ) {
			$separator   = isset( $args['separator'] ) ? $args['separator'] : '';
			$should_link = isset( $args['link'] ) && 'no' === $args['link'] ? false : true;

			foreach ( $terms as $term ) {
				if ( $should_link ) {
					$output .= '<a href="' . get_term_link( $term->slug, $args['type'] ) . '" title="' . esc_attr( $term->name ) . '">';
				}

				$output .= esc_html( $term->name );

				if ( $should_link ) {
					$output .= '</a>';
				}

				$output .= $separator;
			}

			return '' !== $separator ? rtrim( $output, $separator ) : $output;
		}

		return $output;
	}

	/**
	 * Post meta.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_post_custom_field( $args ) {
		do_action( 'fusion_pause_meta_filter' );
		$post_meta = fusion_get_page_option( $args['key'], self::get_post_id() );
		do_action( 'fusion_resume_meta_filter' );

		if ( ! is_array( $post_meta ) ) {
			return $post_meta;
		}
	}

	/**
	 * Site title.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_site_title( $args ) {
		return get_bloginfo( 'name' );
	}

	/**
	 * Site tagline.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_site_tagline( $args ) {
		return get_bloginfo( 'description' );
	}


	/**
	 * Site request parameter.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function fusion_get_site_request_param( $args ) {
		$type  = isset( $args['type'] ) ? strtoupper( $args['type'] ) : false;
		$name  = isset( $args['name'] ) ? $args['name'] : false;
		$value = '';

		if ( ! $name || ! $type ) {
			return '';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		switch ( $type ) {
			case 'POST':
				if ( ! isset( $_POST[ $name ] ) ) {
					return '';
				}
				$value = wp_unslash( $_POST[ $name ] );
				break;
			case 'GET':
				if ( ! isset( $_GET[ $name ] ) ) {
					return '';
				}
				$value = wp_unslash( $_GET[ $name ] );
				break;
			case 'QUERY_VAR':
				$value = get_query_var( $name );
				break;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		return htmlentities( wp_kses_post( $value ) );
	}

	/**
	 * ACF text field.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function acf_get_field( $args ) {
		if ( ! isset( $args['field'] ) ) {
			return '';
		}
		return get_field( $args['field'], self::get_post_id() );
	}

	/**
	 * ACF get image field.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args Arguments.
	 * @return string
	 */
	public static function acf_get_image_field( $args ) {
		if ( ! isset( $args['field'] ) ) {
			return '';
		}
		$image_data = get_field( $args['field'], self::get_post_id() );

		if ( is_array( $image_data ) && isset( $image_data['url'] ) ) {
			return $image_data['url'];
		} elseif ( is_integer( $image_data ) ) {
			return wp_get_attachment_url( $image_data );
		} elseif ( is_string( $image_data ) ) {
			return $image_data;
		}

		return '';
	}

	/**
	 * Get product price.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function woo_get_price( $args, $post_id = 0 ) {

		if ( ! isset( $args['format'] ) ) {
			$args['format'] = '';
		}

		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$_product = wc_get_product( $post_id );
		$price    = '';

		if ( ! $_product ) {
			return;
		}

		if ( '' === $args['format'] ) {
			$price = $_product->get_price_html();
		}

		if ( 'original' === $args['format'] ) {
			$price = wc_price( wc_get_price_to_display( $_product, [ 'price' => $_product->get_regular_price() ] ) );
		}

		if ( 'sale' === $args['format'] ) {
			$price = wc_price( wc_get_price_to_display( $_product, [ 'price' => $_product->get_sale_price() ] ) );
		}

		return $price;
	}

	/**
	 * Get product SKU.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function woo_get_sku( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$_product = wc_get_product( $post_id );

		if ( ! $_product ) {
			return;
		}

		return $_product->get_sku();
	}

	/**
	 * Get product stock.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function woo_get_stock( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$_product = wc_get_product( $post_id );

		if ( ! $_product ) {
			return;
		}

		$stock = $_product->get_stock_quantity();

		return null !== $stock ? $stock : '';
	}

	/**
	 * Get product rating.
	 *
	 * @static
	 * @access public
	 * @since 2.1
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function woo_get_rating( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$_product = wc_get_product( $post_id );

		if ( ! $_product ) {
			return;
		}

		if ( '' === $args['format'] ) {
			$output = $_product->get_average_rating();
		}

		if ( 'rating' === $args['format'] ) {
			$output = $_product->get_rating_count();
		}

		if ( 'review' === $args['format'] ) {
			$output = $_product->get_review_count();
		}

		return $output;
	}

	/**
	 * Author Name.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function get_author_name( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}
		$user_id = get_post_field( 'post_author', $post_id );
		return get_the_author_meta( 'display_name', $user_id );
	}

	/**
	 * Author Description.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function get_author_description( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}
		$user_id = get_post_field( 'post_author', $post_id );
		return get_the_author_meta( 'description', $user_id );
	}

	/**
	 * Author Avatar.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function get_author_avatar( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}
		$user_id = get_post_field( 'post_author', $post_id );
		return get_avatar_url( get_the_author_meta( 'email', $user_id ) );
	}

	/**
	 * Author URL.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function get_author_url( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}
		$user_id = get_post_field( 'post_author', $post_id );
		return esc_url( get_author_posts_url( $user_id ) );
	}

	/**
	 * Author Social Link.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function get_author_social( $args, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}

		$type    = isset( $args['type'] ) ? $args['type'] : 'author_email';
		$user_id = get_post_field( 'post_author', $post_id );
		$url     = get_the_author_meta( $type, $user_id );

		if ( 'author_email' === $type ) {
			$url = 'mailto:' . $url;
		}
		return esc_url( $url );
	}

	/**
	 * Post comments number.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 * @param array $args    Arguments.
	 * @param int   $post_id The post-ID.
	 * @return string
	 */
	public static function fusion_get_post_comments( $args, $post_id = 0 ) {
		$output      = '';
		$should_link = isset( $args['link'] ) && 'no' === $args['link'] ? false : true;

		if ( ! $post_id ) {
			$post_id = self::get_post_id();
		}
		$number = get_comments_number( $post_id );

		if ( 0 === $number ) {
			$output = esc_html__( 'No Comments', 'fusion-builder' );
		} elseif ( 1 === $number ) {
			$output = esc_html__( 'One Comment', 'fusion-builder' );
		} else {
			/* Translators: Number of comments */
			$output = sprintf( _n( '%s Comment', '%s Comments', $number, 'fusion-builder' ), number_format_i18n( $number ) );

		}

		if ( $should_link ) {
			$output = '<a class="fusion-one-page-text-link" href="' . get_comments_link( $post_id ) . '">' . $output . '</a>';
		}
		return $output;
	}
}
