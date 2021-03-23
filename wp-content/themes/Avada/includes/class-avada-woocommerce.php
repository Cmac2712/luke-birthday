<?php
/**
 * Modifications for WooCommerce.
 *
 * @author     ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class to apply woocommerce templates.
 *
 * @since 4.0.0
 */
class Avada_Woocommerce {

	/**
	 * Holds info if currently product elements are looped..
	 *
	 * @access private
	 * @var bool
	 */
	private $in_product_elements = false;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		add_filter( 'woocommerce_show_page_title', [ $this, 'shop_title' ], 10 );

		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', [ $this, 'before_container' ], 10 );
		add_action( 'woocommerce_after_main_content', [ $this, 'after_container' ], 10 );

		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		add_action( 'woocommerce_sidebar', [ $this, 'add_sidebar' ], 10 );

		add_filter( 'fusion_responsive_sidebar_order', [ $this, 'responsive_sidebar_order' ], 10 );

		// Products Loop.
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'add_product_wrappers_open' ], 30 );
		add_action( 'woocommerce_shop_loop_item_title', [ $this, 'product_title' ], 10 );
		add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'add_product_wrappers_close' ], 20 );

		add_action( 'avada_woocommerce_buttons_on_rollover', [ $this, 'template_loop_add_to_cart' ], 10 );
		add_action( 'avada_woocommerce_buttons_on_rollover', [ $this, 'rollover_buttons_linebreak' ], 15 );
		add_action( 'avada_woocommerce_buttons_on_rollover', [ $this, 'show_details_button' ], 20 );

		if ( 'clean' === Avada()->settings->get( 'woocommerce_product_box_design' ) ) {

			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
			add_action( 'woocommerce_after_shop_loop_item', [ $this, 'before_shop_item_buttons' ], 9 );

		} else {

			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'show_product_loop_outofstock_flash' ], 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'before_shop_loop_item_title_open' ], 5 );
			add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'before_shop_loop_item_title_close' ], 20 );
			add_action( 'woocommerce_after_shop_loop_item', [ $this, 'before_shop_item_buttons' ], 5 );
			add_action( 'woocommerce_after_shop_loop_item', [ $this, 'template_loop_add_to_cart' ], 10 );
			add_action( 'woocommerce_after_shop_loop_item', [ $this, 'show_details_button' ], 15 );

		}

		add_action( 'woocommerce_after_shop_loop_item', [ $this, 'after_shop_item_buttons' ], 20 );

		// Single Product Page.
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'add_product_border' ], 19 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'template_single_title' ], 5 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'stock_html' ], 10 );
		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 11 );

		// Add product-title class to the cart item name link.
		add_filter( 'woocommerce_cart_item_name', [ $this, 'cart_item_name' ], 10 );

		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'proceed_to_checkout' ], 10 );

		add_action( 'woocommerce_before_account_navigation', [ $this, 'avada_top_user_container' ], 10 );

		// Add welcome user bar to checkout page.
		add_action( 'woocommerce_before_checkout_form', [ $this, 'avada_top_user_container' ], 1 );

		// Filter the pagination.
		add_filter( 'woocommerce_pagination_args', [ $this, 'change_pagination' ] );
		add_filter( 'woocommerce_comment_pagination_args', [ $this, 'change_pagination' ] );

		add_action( 'woocommerce_before_single_product_summary', [ $this, 'before_single_product_summary_open' ], 5 );
		add_action( 'woocommerce_before_single_product_summary', [ $this, 'before_single_product_summary_close' ], 30 );

		add_filter( 'woocommerce_single_product_carousel_options', [ $this, 'single_product_carousel_options' ], 10 );
		add_filter( 'woocommerce_product_thumbnails_columns', [ $this, 'product_thumbnails_columns' ], 10 );

		if ( fusion_get_option( 'disable_woo_gallery' ) ) {
			add_filter( 'woocommerce_single_product_image_gallery_classes', [ $this, 'single_product_image_gallery_classes' ], 10 );
			add_filter( 'woocommerce_single_product_image_thumbnail_html', [ $this, 'single_product_image_thumbnail_html' ], 10, 2 );
		}

		// Checkout page.
		add_filter( 'woocommerce_order_button_html', [ $this, 'order_button_html' ] );

		add_action( 'woocommerce_checkout_terms_and_conditions', [ $this, 'change_allowed_post_tags_before_terms' ], 15 );
		add_action( 'woocommerce_checkout_terms_and_conditions', [ $this, 'change_allowed_post_tags_after_terms' ], 35 );

		// Account Page.
		add_action( 'woocommerce_account_dashboard', [ $this, 'account_dashboard' ], 5 );
		add_action( 'woocommerce_before_account_orders', [ $this, 'before_account_content_heading' ] );
		add_action( 'woocommerce_before_account_downloads', [ $this, 'before_account_content_heading' ] );
		add_action( 'woocommerce_before_account_payment_methods', [ $this, 'before_account_content_heading' ] );
		add_action( 'woocommerce_edit_account_form_start', [ $this, 'before_account_content_heading' ] );

		remove_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
		add_action( 'woocommerce_view_order', [ $this, 'view_order' ], 10 );
		add_action( 'woocommerce_thankyou', [ $this, 'view_order' ] );

		add_filter( 'woocommerce_account_menu_item_classes', [ $this, 'account_menu_item_classes' ], 10, 2 );

		add_action( 'wp_loaded', [ $this, 'wpml_fix' ], 30 );

		add_action( 'woocommerce_checkout_after_order_review', [ $this, 'checkout_after_order_review' ], 20 );
		add_filter( 'post_class', [ $this, 'change_product_class' ] );
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
		add_action( 'woocommerce_after_customer_login_form', [ $this, 'after_customer_login_form' ] );
		add_action( 'woocommerce_before_customer_login_form', [ $this, 'before_customer_login_form' ] );
		add_filter( 'get_product_search_form', [ $this, 'product_search_form' ] );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		add_action( 'pre_get_posts', [ $this, 'product_ordering' ], 5 );
		add_filter( 'loop_shop_per_page', [ $this, 'loop_shop_per_page' ] );

		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'thumbnail' ], 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );

		add_filter( 'wp_nav_menu_items', [ $this, 'add_woo_cart_to_widget' ], 20, 2 );
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'header_add_to_cart_fragment' ] );

		add_action( 'woocommerce_single_product_summary', [ $this, 'single_product_summary_open' ], 1 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'single_product_summary_close' ], 100 );

		add_action( 'woocommerce_after_single_product_summary', [ $this, 'after_single_product_summary' ], 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'output_related_products' ], 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'upsell_display' ], 10 );

		add_action( 'woocommerce_before_cart_table', [ $this, 'before_cart_table' ], 20 );
		add_action( 'woocommerce_after_cart_table', [ $this, 'after_cart_table' ], 20 );

		add_action( 'woocommerce_cart_collaterals', [ $this, 'cart_collaterals' ], 5 );
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		add_action( 'woocommerce_cart_collaterals', [ $this, 'cross_sell_display' ], 5 );

		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'checkout_coupon_form' ], 10 );

		if ( ! Avada()->settings->get( 'woocommerce_one_page_checkout' ) ) {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'before_checkout_form' ] );
			add_action( 'woocommerce_after_checkout_form', [ $this, 'after_checkout_form' ] );
		} else {
			add_action( 'woocommerce_checkout_before_customer_details', [ $this, 'checkout_before_customer_details' ] );
			add_action( 'woocommerce_checkout_after_customer_details', [ $this, 'checkout_after_customer_details' ] );
		}
		add_action( 'woocommerce_checkout_billing', [ $this, 'checkout_billing' ], 20 );
		add_action( 'woocommerce_checkout_shipping', [ $this, 'checkout_shipping' ], 20 );
		add_filter( 'woocommerce_enable_order_notes_field', [ $this, 'enable_order_notes_field' ] );

		// Make sure that the single product shortcode does not use default column amount.
		add_filter( 'shortcode_atts_product', [ $this, 'change_product_shortcode_atts' ], 20, 4 );

		if ( Avada()->settings->get( 'woocommerce_enable_quick_view' ) ) {
			add_action( 'avada_after_main_content', [ $this, 'quick_view_load_container' ] );
			add_action( 'wp_ajax_fusion_quick_view_load', [ $this, 'quick_view_load_product' ] );
			add_action( 'wp_ajax_nopriv_fusion_quick_view_load', [ $this, 'quick_view_load_product' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'quick_view_enqueue_scripts' ] );
			add_action( 'fusion_quick_view_summary_content', [ $this, 'template_single_title' ], 5 );
			add_action( 'fusion_quick_view_summary_content', [ $this, 'stock_html' ], 10 );
			add_action( 'fusion_quick_view_summary_content', 'woocommerce_template_single_price', 10 );
			add_action( 'fusion_quick_view_summary_content', 'woocommerce_template_single_rating', 11 );
			add_action( 'fusion_quick_view_summary_content', [ $this, 'add_product_border' ], 19 );
			add_action( 'fusion_quick_view_summary_content', 'woocommerce_template_single_excerpt', 20 );
			add_action( 'fusion_quick_view_summary_content', 'woocommerce_template_single_add_to_cart', 30 );
			/**
			 * WIP
			add_action( 'fusion_quick_view_summary_content', 'woocommerce_template_single_meta', 40 );
			add_action( 'fusion_quick_view_summary_content', [ $this, 'after_single_product_summary' ], 50 );
			 */
		}

		// Remove WC customizer options.
		add_filter( 'loop_shop_columns', [ $this, 'remove_woo_customizer_columns' ] );

		// Add notice to WC customizer panel.
		add_action( 'customize_register', [ $this, 'add_woocommerce_customizer_notice' ] );

		add_action( 'fusion_woocommerce_after_shop_loop_item', [ $this, 'woocommerce_after_shop_loop_item' ] );

		add_filter( 'woocommerce_default_catalog_orderby', [ $this, 'woocommerce_default_catalog_orderby' ], 99999 );
	}

	/**
	 * Runs the `woocommerce_after_shop_loop_item` hook.
	 *
	 * @access public
	 * @since 6.2.0
	 * @return void
	 */
	public function woocommerce_after_shop_loop_item() {
		$this->in_product_elements = true;
		do_action( 'woocommerce_after_shop_loop_item' );
		$this->in_product_elements = false;
	}

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @static
	 * @access private
	 * @since 3.7.2
	 * @return string woocommerce version number or null.
	 */
	private static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Add content before the container.
	 *
	 * @access public
	 */
	public function before_container() {
		ob_start();
		Avada()->layout->add_class( 'content_class' );
		$content_class = ob_get_clean();

		ob_start();
		Avada()->layout->add_style( 'content_style' );
		$content_css = ob_get_clean();
		?>
		<div class="woocommerce-container">
			<section id="content"<?php echo $content_class . ' ' . $content_css; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php
	}

	/**
	 * Returns false.
	 *
	 * @access public
	 * @return false
	 */
	public function shop_title() {
		return false;
	}

	/**
	 * Closes 2 divs that were previously opened.
	 *
	 * @access public
	 */
	public function after_container() {
		get_template_part( 'templates/wc-after-container' );
	}

	/**
	 * Adds the sidebar.
	 *
	 * @access public
	 */
	public function add_sidebar() {
		do_action( 'avada_after_content' );
	}

	/**
	 * Adds necessary selector to sidebar order array.
	 *
	 * @access public
	 * @param array $sidebar_order Array of selectors.
	 */
	public function responsive_sidebar_order( $sidebar_order ) {
		$key = array_search( 'content', $sidebar_order );
		if ( false !== $key ) {
			$sidebar_order[ $key ] .= ', .woocommerce-container';
		}

		return $sidebar_order;
	}

	/**
	 * Prints the out of stock warning.
	 *
	 * @access public
	 */
	public function show_product_loop_outofstock_flash() {
		get_template_part( 'templates/wc-product-loop-outofstock-flash' );
	}

	/**
	 * Adds the link to permalink.
	 *
	 * @access public
	 */
	public function before_shop_loop_item_title_open() {
		get_template_part( 'templates/wc-before-shop-loop-item-title-open' );
	}

	/**
	 * Closes the link.
	 *
	 * @access public
	 */
	public function before_shop_loop_item_title_close() {
		get_template_part( 'templates/wc-before-shop-loop-item-title-close' );
	}

	/**
	 * Content before the item buttons.
	 *
	 * @access public
	 */
	public function before_shop_item_buttons() {
		if ( ! $this->in_product_elements ) {
			get_template_part( 'templates/wc-before-shop-item-buttons' );
		}
	}

	/**
	 * Add to cart loop.
	 *
	 * @access public
	 * @param array $args The arguments.
	 */
	public function template_loop_add_to_cart( $args = [] ) {
		global $product;

		if ( ! $this->in_product_elements ) {

			if ( $product && ( ( $product->is_purchasable() && $product->is_in_stock() ) || $product->is_type( 'external' ) ) ) {

				$filtered_classes = array_filter(
					[
						'button',
						'product_type_' . $product->get_type(),
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
					]
				);

				$defaults = [
					'quantity' => 1,
					'class'    => implode( ' ', $filtered_classes ),
				];

				if ( version_compare( self::get_wc_version(), '3.3', '>=' ) ) {
					$defaults['attributes'] = [
						'data-product_id'  => $product->get_id(),
						'data-product_sku' => $product->get_sku(),
						'aria-label'       => $product->add_to_cart_description(),
						'rel'              => 'nofollow',
					];
				}

				$args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );

				wc_get_template( 'loop/add-to-cart.php', $args );
			}
		}
	}

	/**
	 * Adds the linebreak where needed.
	 *
	 * @access public
	 */
	public function rollover_buttons_linebreak() {
		global $product;
		if ( $product && ( ( $product->is_purchasable() && $product->is_in_stock() ) || $product->is_type( 'external' ) ) ) {
			get_template_part( 'templates/wc-rollover-buttons-linebreak' );
		}
	}

	/**
	 * Renders the "Details" button.
	 *
	 * @access public
	 */
	public function show_details_button() {
		if ( ! $this->in_product_elements ) {
			get_template_part( 'templates/wc-show-details-button' );
		}
	}

	/**
	 * Closes 2 divs that were previously opened.
	 *
	 * @access public
	 */
	public function after_shop_item_buttons() {
		if ( ! $this->in_product_elements ) {
			get_template_part( 'templates/wc-after-shop-item-buttons' );
		}
	}

	/**
	 * Adds a div that is used for borders.
	 *
	 * @access public
	 */
	public function add_product_border() {
		get_template_part( 'templates/wc-add-product-border' );
	}

	/**
	 * Modifies the pagination.
	 *
	 * @access public
	 * @param array $options An array of our options.
	 * @return array         The options, modified.
	 */
	public function change_pagination( $options ) {
		$options['prev_text'] = '<span class="page-prev"></span><span class="page-text">' . esc_attr__( 'Previous', 'Avada' ) . '</span>';
		$options['next_text'] = '<span class="page-text">' . esc_attr__( 'Next', 'Avada' ) . '</span><span class="page-next"></span>';
		$options['type']      = 'plain';
		$options['mid_size']  = Avada()->settings->get( 'pagination_range' );
		$options['end_size']  = Avada()->settings->get( 'pagination_start_end_range' );

		return $options;
	}

	/**
	 * Add wrapping container opening for single product image gallery.
	 *
	 * @since 5.1
	 * @access public
	 * @return void
	 */
	public function before_single_product_summary_open() {
		get_template_part( 'templates/wc-before-single-product-summary-open' );
	}

	/**
	 * Add wrapping container closing for single product image gallery.
	 *
	 * @since 5.1
	 * @access public
	 * @return void
	 */
	public function before_single_product_summary_close() {
		get_template_part( 'templates/wc-before-single-product-summary-close' );
	}

	/**
	 * Filters single product page image flexslider options.
	 *
	 * @since 5.1
	 * @access public
	 * @param array $flexslider_options Holds the default options for setting up the flexslider object.
	 * @return array The altered flexslider options.
	 */
	public function single_product_carousel_options( $flexslider_options ) {
		global $post;

		$flexslider_options['directionNav'] = true;

		$product = wc_get_product( $post );

		if ( is_object( $product ) ) {

			$attachment_ids = $product->get_gallery_image_ids();

			if ( fusion_get_option( 'disable_woo_gallery' ) && 0 < count( $attachment_ids ) ) {
				$flexslider_options['animationLoop'] = true;
				$flexslider_options['smoothHeight']  = true;
			}
		}

		return $flexslider_options;
	}

	/**
	 * Filters single product gallery thumbnail columns.
	 *
	 * @since 5.1
	 * @access public
	 * @param string $columns Holds the number of gallery thumbnail columns.
	 * @return string The altered gallery thumbnail columns.
	 */
	public function product_thumbnails_columns( $columns ) {
		return Avada()->settings->get( 'woocommerce_gallery_thumbnail_columns' );
	}

	/**
	 * Filters single product page image gallery classes.
	 *
	 * @since 5.1
	 * @access public
	 * @param string $classes Holds the single product image gallery classes.
	 * @return array The altered classes.
	 */
	public function single_product_image_gallery_classes( $classes ) {

		$classes[] = 'avada-product-gallery';
		return $classes;

	}

	/**
	 * Filters single product image thumbnail html.
	 *
	 * @since 5.1
	 * @access public
	 * @param string $html Holds the single product image thumbnail html.
	 * @param number $attachment_id The attachment id for single product image.
	 * @return array The altered html markup.
	 */
	public function single_product_image_thumbnail_html( $html, $attachment_id ) {
		global $post, $product;

		$attachment_count = count( $product->get_gallery_image_ids() );
		$full_size_image  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$attachment_data  = fusion_library()->images->get_attachment_data( $attachment_id, 'none' );

		$gallery = '[]';
		if ( $attachment_count > 0 ) {
			$gallery = '[product-gallery]';
		}

		$html = str_replace( '</div>', '<a class="avada-product-gallery-lightbox-trigger" href="' . esc_url( $full_size_image[0] ) . '" data-rel="iLightbox' . $gallery . '" alt="' . $attachment_data['alt'] . '" data-title="' . $attachment_data['title_attribute'] . '" data-caption="' . $attachment_data['caption_attribute'] . '"></a></div>', $html );

		return $html;
	}

	/**
	 * Open wrapper divs.
	 *
	 * @access public
	 */
	public function add_product_wrappers_open() {
		get_template_part( 'templates/wc-add-product-wrappers-open' );
	}

	/**
	 * Renders the product title.
	 *
	 * @access public
	 */
	public function product_title() {
		get_template_part( 'templates/wc-product-title' );
	}

	/**
	 * Closes previously opened wrappers.
	 *
	 * @access public
	 */
	public function add_product_wrappers_close() {
		get_template_part( 'templates/wc-add-product-wrappers-close' );
	}

	/**
	 * Single Product Page functions.
	 *
	 * @access public
	 */
	public function template_single_title() {
		get_template_part( 'templates/wc-single-title' );
	}

	/**
	 * Add the availability HTML.
	 *
	 * @access public
	 */
	public function stock_html() {
		get_template_part( 'templates/wc-stock' );
	}

	/**
	 * Adds the product-title class to the cart item name link.
	 *
	 * @since 5.1
	 * @access public
	 * @param string $name The cart item name, can be wrapped by an a tag or not.
	 * @return string The cart item name.
	 */
	public function cart_item_name( $name ) {
		if ( false !== strpos( $name, 'href=' ) ) {
			return str_replace( '<a', '<a class="product-title"', $name );
		}
		return $name;
	}

	/**
	 * Added in the 'woocommerce_proceed_to_checkout' action.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function proceed_to_checkout() {
		get_template_part( 'templates/wc-proceed-to-checkout' );
	}

	/**
	 * Add the view-order markup.
	 *
	 * @param int $order_id The ID of the order we're querying.
	 */
	public function view_order( $order_id ) {
		include wp_normalize_path( locate_template( 'templates/wc-view-order.php' ) );
	}

	/**
	 * Add 'is-active' CSS class if on 'my-account/view-order' page
	 *
	 * @param array  $classes  Array of menu item classes.
	 * @param string $endpoint Current menu item endpoint.
	 */
	public function account_menu_item_classes( $classes, $endpoint ) {

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'view-order' ) && 'orders' === $endpoint ) {
				$classes[] = 'is-active';
		}

		return $classes;
	}

	/**
	 * Account Page functions.
	 *
	 * @access public
	 */
	public function avada_top_user_container() {
		get_template_part( 'templates/wc-top-user-container' );
	}

	/**
	 * Change the HTML of the checkout button.
	 *
	 * @since 5.1
	 * @access public
	 * @param string $html The checkout button HTML.
	 * @return string The changed HTML.
	 */
	public function order_button_html( $html ) {
		return str_replace( 'class="button', 'class="button fusion-button button-default fusion-button-default-size', $html );
	}

	/**
	 * Calls filter to change allowed post tags.
	 *
	 * @since 6.2
	 * @access public
	 * @return void
	 */
	public function change_allowed_post_tags_before_terms() {
		add_filter( 'wp_kses_allowed_html', [ $this, 'change_wp_kses_allowed_html_before_terms' ] );
	}

	/**
	 * Calls filter to change allowed post tags.
	 *
	 * @since 6.2
	 * @access public
	 * @return void
	 */
	public function change_allowed_post_tags_after_terms() {
		add_filter( 'wp_kses_allowed_html', [ $this, 'change_wp_kses_allowed_html_after_terms' ] );
	}

	/**
	 * Change allowed post tags.
	 *
	 * @since 6.2
	 * @access public
	 * @param array $tags The allowed HTML tags.
	 * @return array $tags The allowed HTML tags.
	 */
	public function change_wp_kses_allowed_html_before_terms( $tags ) {
		$tags['style'] = [ 'type' => true ];

		return $tags;
	}

	/**
	 * Change allowed post tags.
	 *
	 * @since 6.2
	 * @param array $tags The allowed HTML tags.
	 * @return array $tags The allowed HTML tags.
	 */
	public function change_wp_kses_allowed_html_after_terms( $tags ) {
		unset( $tags['style'] );

		return $tags;
	}

	/**
	 * The account dashboard.
	 *
	 * @access public
	 */
	public function account_dashboard() {
		?>
		<style>
		.woocommerce-MyAccount-content{ display: -webkit-flex;display: -ms-flexbox;display:flex;-webkit-flex-flow: column wrap;flex-flow: column nowrap; }
		.avada-woocommerce-myaccount-heading{ -ms-flex-order: 0;-webkit-order: 0;order: 0; }
		.woocommerce-MyAccount-content > p, .woocommerce-MyAccount-content > div, .woocommerce-MyAccount-content > span{ -ms-flex-order: 1;-webkit-order: 1;order: 1; }
		.woocommerce-MyAccount-content > p:first-child { display: none; }
		</style>
		<?php
		self::before_account_content_heading();
	}

	/**
	 * Content injected before the content heading.
	 *
	 * @access public
	 */
	public function before_account_content_heading() {
		if ( is_account_page() ) {
			$account_items   = wc_get_account_menu_items();
			$heading_content = esc_attr__( 'Dashboard', 'Avada' );

			if ( is_wc_endpoint_url( 'orders' ) ) {
				$heading_content = $account_items['orders'];
			} elseif ( is_wc_endpoint_url( 'downloads' ) ) {
				$heading_content = $account_items['downloads'];
			} elseif ( is_wc_endpoint_url( 'payment-methods' ) ) {
				$heading_content = $account_items['payment-methods'];
			} elseif ( is_wc_endpoint_url( 'edit-account' ) ) {
				$heading_content = $account_items['edit-account'];
			}
			?>
			<h2 class="avada-woocommerce-myaccount-heading">
				<?php echo $heading_content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</h2>
			<?php
		}
	}

	/**
	 * Dealing with mini-cart cache in internal browser storage.
	 * Response to action 'woocommerce_add_to_cart_hash', which overwrites the default WC cart hash and cookies.
	 *
	 * @access public
	 * @since 5.0.2
	 * @param string $hash Default WC hash.
	 * @param array  $cart WC variable holding contents of the cart without language information.
	 */
	public function add_to_cart_hash( $hash, $cart ) {

		$hash = $this->get_cart_hash( $cart );
		if ( ! headers_sent() ) {
			wc_setcookie( 'woocommerce_cart_hash', $hash );
		}
		return $hash;
	}

	/**
	 * Dealing with mini-cart cache in internal browser storage.
	 *
	 * @access private
	 * @since 5.0.2
	 * @param  array $cart WC variable holding contents of the cart without language information.
	 * @return string Cart hash with language information
	 */
	private function get_cart_hash( $cart ) {

		$lang = Fusion_Multilingual::get_active_language();
		return md5( wp_json_encode( $cart ) . $lang );

	}

	/**
	 * Dealing with mini-cart cache in internal browser storage.
	 * Sets 'woocommerce_cart_hash' cookie.
	 *
	 * @access private
	 * @since 5.0.2
	 * @param array $cart wc variable holding contents of the cart without language information.
	 */
	private function set_cookies_cart_hash( $cart ) {

		if ( ! $cart ) {
			return;
		}
		$hash = $this->get_cart_hash( $cart );
		wc_setcookie( 'woocommerce_cart_hash', $hash );

	}

	/**
	 * Dealing with mini-cart cache in internal browser storage.
	 * Response to action 'woocommerce_cart_loaded_from_session'.
	 *
	 * @access public
	 * @since 5.0.2
	 * @param WC_Cart $wc_cart wc object without language information.
	 */
	public function cart_loaded_from_session( $wc_cart ) {

		if ( headers_sent() || ! $wc_cart ) {
			return;
		}
		$cart = $wc_cart->get_cart_for_session();
		$this->set_cookies_cart_hash( $cart );

	}

	/**
	 * Dealing with mini-cart cache in internal browser storage.
	 * Response to action 'woocommerce_set_cart_cookies', which overwrites the default WC cart hash and cookies.
	 *
	 * @access public
	 * @since 5.0.2
	 * @param bool $set is true if cookies need to be set, otherwse they are unset in calling function.
	 */
	public function set_cart_cookies( $set ) {

		if ( $set ) {
			$wc      = WC();
			$wc_cart = $wc->cart;
			$cart    = $wc_cart->get_cart_for_session();
			$this->set_cookies_cart_hash( $cart );
		}
	}

	/**
	 * Fix for WPML.
	 *
	 * @access public
	 * @since 5.1 (Moved from the constructor - Props @andreagrillo)
	 */
	public function wpml_fix() {
		if ( class_exists( 'SitePress' ) ) {
			$cart_hash_filter = 'woocommerce_cart_hash';
			if ( version_compare( self::get_wc_version(), '3.6', '<' ) ) {
				$cart_hash_filter = 'woocommerce_add_to_cart_hash';
			}

			add_filter( $cart_hash_filter, [ $this, 'add_to_cart_hash' ], 5, 2 );
			add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'cart_loaded_from_session' ], 5 );
			add_action( 'woocommerce_set_cart_cookies', [ $this, 'set_cart_cookies' ] );
		}
	}

	/**
	 * Changes the markup for the product search form.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param string $form The HTML of the form.
	 * @return string      Modified HTML of the form.
	 */
	public function product_search_form( $form ) {
		ob_start();
		get_template_part( 'templates/wc-product-search-form' );
		return ob_get_clean();
	}

	/**
	 * Closes the div.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function checkout_after_order_review() {
		echo ( Avada()->settings->get( 'woocommerce_one_page_checkout' ) ) ? '</div>' : '';
	}

	/**
	 * Open a div if needed.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function before_customer_login_form() {
		echo ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) ? '<div id="customer_login" class="woocommerce-content-box full-width">' : '';
	}

	/**
	 * Markup to add after the customer-login form.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function after_customer_login_form() {
		echo ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) ? '</div>' : '';
	}

	/**
	 * The avada_change_product_class hook - Function to add 'product-list-view' class if the list view is being displayed.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $classes An array containing class names for the particular post / product.
	 * @return array $classes An array containing additional class 'product-list-view' if the product view is set to list.
	 */
	public function change_product_class( $classes ) {

		if ( 'product' !== get_post_type() || is_product() ) {
			return $classes;
		}

		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $params );
			$product_view = ( isset( $params['product_view'] ) ) ? $params['product_view'] : Avada()->settings->get( 'woocommerce_product_view' );
			$classes[]    = 'product-' . $product_view . '-view';
		}
		return $classes;
	}

	/**
	 * Controls the actions adding the ordering boxes.
	 *
	 * @access public
	 * @since 5.0.4
	 * @param object $query The main query.
	 * @return void
	 */
	public function product_ordering( $query ) {

		// We only want to affect the main query and no ordering on search page.
		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( fusion_is_shop( $query->get( 'page_id' ) ) || $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) ) {

			if ( Avada()->settings->get( 'woocommerce_avada_ordering' ) || Avada()->settings->get( 'woocommerce_toggle_grid_list' ) ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
				add_action( 'woocommerce_before_shop_loop', [ $this, 'catalog_ordering' ], 30 );

				add_filter( 'woocommerce_get_catalog_ordering_args', [ $this, 'get_catalog_ordering_args' ], 20 );
			}
		}
	}

	/**
	 * Modified the ordering of products.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function catalog_ordering() {
		get_template_part( 'templates/wc-catalog-ordering' );
	}

	/**
	 * Gets the catalogue ordering arguments.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The arguments.
	 * @return array
	 */
	public function get_catalog_ordering_args( $args ) {
		global $woocommerce;
		$woo_default_catalog_orderby = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', '' ) );

		// On search pages use "Relevance" as default.
		if ( is_search() ) {
			$woo_default_catalog_orderby = 'relevance';
		}

		// Get the query args.
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $params );
		}

		// Get order by.
		$pob = ( ! empty( $params['product_orderby'] ) && 'default' !== $params['product_orderby'] ) ? $params['product_orderby'] : $woo_default_catalog_orderby;

		// Get order.
		$po = 'asc';
		if ( isset( $params['product_order'] ) ) {
			// Dedicated ordering.
			$po = $params['product_order'];
		} else {
			// Get the correct default order.
			$po = 'asc';
			if ( 'date' === $pob || 'popularity' === $pob || 'rating' === $pob || 'price-desc' === $pob ) {
				$po = 'desc';
			}
		}

		// Remove posts_clause filter, if default ordering is set to rating or popularity to make custom ordering work correctly.
		if ( 'default' !== $pob ) {
			if ( false !== strpos( $woo_default_catalog_orderby, 'price' ) || 'popularity' === $woo_default_catalog_orderby || 'rating' === $woo_default_catalog_orderby ) {
				WC()->query->remove_ordering_args();
			}
		}

		$orderby  = 'date';
		$order    = strtoupper( $po );
		$meta_key = '';

		switch ( $pob ) {
			case 'menu_order':
			case 'default':
				$orderby = $args['orderby'];
				break;
			case 'date':
				$orderby = 'date';
				break;
			case 'price':
			case 'price-desc':
				$meta_key = '_price';
				$orderby  = 'meta_value_num ID';
				break;
			case 'relevance':
				$orderby = 'relevance';
				$order   = 'DESC';
				break;
			case 'popularity':
				$meta_key = 'total_sales';
				add_filter( 'posts_clauses', [ $this, 'order_by_popularity_post_clauses' ] );
				add_action( 'wp', [ $this, 'remove_ordering_args_filters' ] );
				break;
			case 'rating':
				$meta_key = '_wc_average_rating';
				$orderby  = [
					'meta_value_num' => strtoupper( $po ),
					'ID'             => 'ASC',
				];
				break;
			case 'name':
				$orderby = 'title';
				break;
		}

		$args['orderby']  = $orderby;
		$args['order']    = $order;
		$args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery

		return $args;
	}

	/**
	 * The order_by_price_post_clauses method.
	 *
	 * @access public
	 * @since 5.2.2
	 * @param array $args The arguments array.
	 * @return array The altered arguments array.
	 */
	public function order_by_price_post_clauses( $args ) {
		global $wpdb;
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $params );
		}

		$order           = empty( $params['product_order'] ) ? 'ASC' : strtoupper( $params['product_order'] );
		$min_max         = ( 'DESC' === $order ) ? 'max' : 'min';
		$args['join']   .= " INNER JOIN ( SELECT post_id, {$min_max}( meta_value+0 ) price FROM $wpdb->postmeta WHERE meta_key='_price' GROUP BY post_id ) as fusion_price_query ON $wpdb->posts.ID = fusion_price_query.post_id ";
		$args['orderby'] = " fusion_price_query.price {$order} ";
		return $args;
	}

	/**
	 * The order_by_popularity_post_clauses method.
	 *
	 * @access public
	 * @since 5.0.0
	 * @param array $args The arguments array.
	 * @return array The altered arguments array.
	 */
	public function order_by_popularity_post_clauses( $args ) {
		global $wpdb;
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $params );
		}

		$order = empty( $params['product_order'] ) ? 'DESC' : strtoupper( $params['product_order'] );

		$join_sql = $args['join'];
		if ( ! strstr( $join_sql, 'wc_product_meta_lookup' ) ) {
			$join_sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}

		$args['join']    = $join_sql;
		$args['orderby'] = ' wc_product_meta_lookup.total_sales ' . $order . ', wc_product_meta_lookup.product_id ' . $order;
		return $args;
	}

	/**
	 * Removes the order_by_price_post_clauses and order_by_popularity_post_clauses filters.
	 *
	 * @access public
	 * @since 5.0.4
	 */
	public function remove_ordering_args_filters() {
		remove_filter( 'posts_clauses', [ $this, 'order_by_price_post_clauses' ] );
		remove_filter( 'posts_clauses', [ $this, 'order_by_popularity_post_clauses' ] );
	}

	/**
	 * Determine how many products we want to show per page.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return int
	 */
	public function loop_shop_per_page() {

		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $params );
		}

		$per_page = 12;
		if ( Avada()->settings->get( 'woo_items' ) ) {
			$per_page = Avada()->settings->get( 'woo_items' );
		}

		return ( ! empty( $params['product_count'] ) ) ? $params['product_count'] : $per_page;
	}

	/**
	 * Shows the product image.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function thumbnail() {

		$mode = Avada()->settings->get( 'woocommerce_product_box_design' );
		$mode = ( ! $mode ) ? 'classic' : $mode;
		get_template_part( 'templates/wc-thumbnail', $mode );
	}

	/**
	 * Adds cart menu item.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param string $items The menu items.
	 * @param array  $args  The menu arguments.
	 * @return string
	 */
	public function add_woo_cart_to_widget( $items, $args ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $items;
		}
		$ubermenu = false;
		if ( function_exists( 'ubermenu_get_menu_instance_by_theme_location' ) && ubermenu_get_menu_instance_by_theme_location( $args->theme_location ) ) {
			// Disable woo cart on ubermenu navigations.
			$ubermenu = true;
		}
		if ( false === $ubermenu && 'fusion-widget-menu' === $args->container_class ) {
			$items .= fusion_add_woo_cart_to_widget_html();
		}

		return $items;
	}

	/**
	 * Modify the cart ajax.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $fragments Ajax fragments handled by WooCommerce.
	 * @return array
	 */
	public function header_add_to_cart_fragment( $fragments ) {
		global $woocommerce;

		$header_top_cart                          = avada_nav_woo_cart( 'secondary' );
		$fragments['.fusion-secondary-menu-cart'] = $header_top_cart;

		$header_cart                         = avada_nav_woo_cart( 'main' );
		$fragments['.fusion-main-menu-cart'] = $header_cart;

		$flyout_menu_cart                         = avada_flyout_menu_woo_cart();
		$fragments['.fusion-flyout-cart-wrapper'] = $flyout_menu_cart;

		$widget_cart                      = fusion_add_woo_cart_to_widget_html();
		$fragments['.fusion-widget-cart'] = $widget_cart;

		return $fragments;
	}

	/**
	 * Opens a div.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function single_product_summary_open() {
		echo '<div class="summary-container">';
	}

	/**
	 * Closes the div.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function single_product_summary_close() {
		echo '</div>';
	}

	/**
	 * Markup to add after the summary on single products.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function after_single_product_summary() {
		get_template_part( 'templates/wc-after-single-product-summary' );
	}

	/**
	 * Add related products.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function output_related_products() {
		global $post;

		$number_of_columns = fusion_get_page_option( 'number_of_related_products', $post->ID );
		if ( in_array( $number_of_columns, [ 'default', '' ] ) || ! $number_of_columns ) {
			$number_of_columns = Avada()->settings->get( 'woocommerce_related_columns' );
		}

		$args = [
			'posts_per_page' => $number_of_columns,
			'columns'        => $number_of_columns,
			'orderby'        => 'rand',
		];

		echo '<div class="fusion-clearfix"></div>';
		woocommerce_related_products( apply_filters( 'woocommerce_output_related_products_args', $args ) );
	}

	/**
	 * Displays upsells.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function upsell_display() {

		global $product, $post;

		$upsells = $product->get_upsell_ids();

		if ( 0 === count( $upsells ) ) {
			return;
		}

		echo '<div class="fusion-clearfix"></div>';

		$number_of_columns = fusion_get_page_option( 'number_of_related_products', $post->ID );
		if ( in_array( $number_of_columns, [ 'default', '' ] ) || ! $number_of_columns ) {
			$number_of_columns = Avada()->settings->get( 'woocommerce_related_columns' );
		}
		woocommerce_upsell_display( - 1, $number_of_columns );
	}

	/**
	 * Add markup before the cart table.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args Not really used here.
	 */
	public function before_cart_table( $args ) {
		global $woocommerce;
		?>
		<div class="woocommerce-content-box full-width clearfix">
				<?php /* translators: Number. */ ?>
				<h2><?php printf( esc_attr( _n( 'You Have %s Item In Your Cart', 'You Have %s Items In Your Cart', $woocommerce->cart->get_cart_contents_count(), 'Avada' ) ), esc_html( number_format_i18n( $woocommerce->cart->get_cart_contents_count() ) ) ); ?></h2>
			<?php
	}

	/**
	 * Adds markup after the cart table.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args Not used here.
	 */
	public function after_cart_table( $args ) {
		echo '</div>';
	}

	/**
	 * Adds coupon code form.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The formarguments.
	 */
	public function cart_collaterals( $args ) {
		get_template_part( 'templates/wc-cart-collaterals' );
	}

	/**
	 * Displays cross-sell.
	 *
	 * @access public
	 * @since 5.1.0
	 */
	public function cross_sell_display() {
		global $product, $post;

		$crosssells = WC()->cart->get_cross_sells();

		if ( 0 === count( $crosssells ) ) {
			return;
		}

		$number_of_columns = Avada()->settings->get( 'woocommerce_related_columns' );

		woocommerce_cross_sell_display( apply_filters( 'woocommerce_cross_sells_total', - 1 ), $number_of_columns );
	}

	/**
	 * Adds coupon form in the checkout page.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The form arguments.
	 */
	public function checkout_coupon_form( $args ) {
		include wp_normalize_path( locate_template( 'templates/wc-checkout-coupon-form.php' ) );
	}

	/**
	 * Markup to add before the checkout form.
	 *
	 * @param array $args Not used in this context.
	 */
	public function before_checkout_form( $args ) {
		include wp_normalize_path( locate_template( 'templates/wc-before-checkout-form.php' ) );
	}

	/**
	 * Closes the div after the checkout form.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The arguments (not used here).
	 */
	public function after_checkout_form( $args ) {
		echo '</div>';
	}

	/**
	 * Markup to add before the customer details form.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The form arguments. Not used in the context of this function.
	 */
	public function checkout_before_customer_details( $args ) {
		global $woocommerce;

		if ( WC()->cart->needs_shipping() && ! wc_ship_to_billing_address_only() || apply_filters( 'woocommerce_enable_order_notes_field', get_option( 'woocommerce_enable_order_comments', 'yes' ) === 'yes' ) && ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) ) {
			return;
		}
		echo '<div class="avada-checkout-no-shipping">';
	}

	/**
	 * Adds markup after the customer details form.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The form arguments. Not used in the context of this function.
	 */
	public function checkout_after_customer_details( $args ) {
		global $woocommerce;

		if ( WC()->cart->needs_shipping() && ! wc_ship_to_billing_address_only() || apply_filters( 'woocommerce_enable_order_notes_field', get_option( 'woocommerce_enable_order_comments', 'yes' ) === 'yes' ) && ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) ) {
			echo '<div class="clearboth"></div>';
		} else {
			echo '<div class="clearboth"></div></div>';
		}
		echo '<div class="woocommerce-content-box full-width">';
	}

	/**
	 * Add checkout billing markup.
	 *
	 * @param array $args The form arguments. Not used in the context of this function.
	 */
	public function checkout_billing( $args ) {
		global $woocommerce;

		$data_name = 'order_review';
		if ( WC()->cart->needs_shipping() && ! wc_ship_to_billing_address_only() || apply_filters( 'woocommerce_enable_order_notes_field', get_option( 'woocommerce_enable_order_comments', 'yes' ) === 'yes' ) && ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) ) {
			$data_name = 'col-2';
		}
		?>
		<?php if ( ! Avada()->settings->get( 'woocommerce_one_page_checkout' ) ) : ?>
			<a data-name="<?php echo esc_attr( $data_name ); ?>" href="#" class="fusion-button button-default fusion-button-default-size button continue-checkout">
				<?php esc_attr_e( 'Continue', 'Avada' ); ?>
			</a>
			<div class="clearboth"></div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Add checkout shipping markup.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param array $args The form arguments. Not used in the context of this function.
	 */
	public function checkout_shipping( $args ) {
		?>
		<?php if ( ! Avada()->settings->get( 'woocommerce_one_page_checkout' ) ) : ?>
			<a data-name="order_review" href="#" class="fusion-button button-default fusion-button-default-size continue-checkout button">
				<?php esc_attr_e( 'Continue', 'Avada' ); ?>
			</a>
			<div class="clearboth"></div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Determines if we should enable order notes or not.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return bool
	 */
	public function enable_order_notes_field() {
		return ( ! Avada()->settings->get( 'woocommerce_enable_order_notes' ) ) ? 0 : 1;
	}

	/**
	 * Remove columns and rows option from WooCommerce customizer panel.
	 *
	 * @access public
	 * @since 5.4.2
	 * @param int $cols Number of columns.
	 * @return int
	 */
	public function remove_woo_customizer_columns( $cols ) {
		return $cols;
	}

	/**
	 * Add notice to WooCommerce customizer panel.
	 *
	 * @access public
	 * @since 5.4.2
	 * @param array $wp_customize Customizer object.
	 * @return void
	 */
	public function add_woocommerce_customizer_notice( $wp_customize ) {
		$wp_customize->add_control(
			'avada_woocommerce_column_notice',
			[
				'label'       => __( 'NOTE', 'Avada' ),
				/* translators: WC Customizer notice. */
				'description' => sprintf( __( 'You can control the <a href="%1$s" target="_blank">number of products per page</a> and the <a href="%2$s" target="_blank">number of columns for the main shop page</a> from Avada theme options panel.', 'Avada' ), Avada()->settings->get_setting_link( 'woo_items' ), Avada()->settings->get_setting_link( 'woocommerce_shop_page_columns' ) ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_default_catalog_orderby',
				'type'        => 'hidden',

			]
		);
	}

	/**
	 * Filters the single product shortcode and sets amount of columns to 1.
	 *
	 * @access public
	 * @since 5.5
	 * @param array  $out       The output array of shortcode attributes.
	 * @param array  $pairs     The supported attributes and their defaults.
	 * @param array  $atts      The user defined shortcode attributes.
	 * @param string $shortcode The shortcode name.
	 *
	 * @return array $out The attribute output array.
	 */
	public function change_product_shortcode_atts( $out, $pairs, $atts, $shortcode ) {
		if ( ! isset( $atts['columns'] ) ) {
			$out['columns'] = '1';
		}
		return $out;
	}

	/**
	 * Creates the quick view container.
	 *
	 * @access public
	 * @since 6.1
	 *
	 * @return void
	 */
	public function quick_view_load_container() {
		get_template_part( 'templates/wc-quick-view-container' );
	}

	/**
	 * Creates the product quick view.
	 *
	 * @access public
	 * @since 6.1
	 *
	 * @return void
	 */
	public function quick_view_load_product() {
		global $post, $product, $woocommerce;

		check_ajax_referer( 'fusion_quick_view_nonce', 'nonce' );

		if ( isset( $_POST['product'] ) ) {
			$product_id = sanitize_text_field( wp_unslash( $_POST['product'] ) );
			$post       = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$product    = wc_get_product( $product_id );

			ob_start();

			get_template_part( 'templates/wc-quick-view-product' );

			$output = ob_get_contents();
			ob_end_clean();
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		wp_die();
	}

	/**
	 * Enqueue scripts for quick view.
	 *
	 * @access public
	 * @since 6.1
	 *
	 * @return void
	 */
	public function quick_view_enqueue_scripts() {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}

	/**
	 * Filter the default catalog ordering value.
	 *
	 * @access public
	 * @since 6.2.2
	 * 
	 * @param string $default_order The default order.
	 * @return string The filtered default oder.
	 */
	public function woocommerce_default_catalog_orderby( $default_order ) {
		if ( ! $default_order ) {
			$default_order = 'menu_order';
		}

		return $default_order;
	}

}
