<?php
/**
 * Avada Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * WooCommerce settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_options_section_woocommerce( $sections ) {

	$sections['woocommerce'] = ( Avada::$is_updating || class_exists( 'WooCommerce' ) ) ? [
		'label'    => esc_html__( 'WooCommerce', 'Avada' ),
		'id'       => 'heading_woocommerce',
		'priority' => 26,
		'icon'     => 'el-icon-shopping-cart',
		'alt_icon' => 'fusiona-cart',
		'fields'   => [
			'general_woocommerce_options_subsection' => [
				'label'       => esc_html__( 'General WooCommerce', 'Avada' ),
				'description' => '',
				'id'          => 'general_woocommerce_options_subsection',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'woo_items' => [
						'label'       => esc_html__( 'WooCommerce Number of Products per Page', 'Avada' ),
						'description' => esc_html__( 'Controls the number of products that display per page. ', 'Avada' ),
						'id'          => 'woo_items',
						'default'     => '12',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '1',
							'max'  => '50',
							'step' => '1',
						],
					],
					'woocommerce_shop_page_columns' => [
						'label'           => esc_html__( 'WooCommerce Number of Product Columns', 'Avada' ),
						'description'     => esc_html__( 'Controls the number of columns for the main shop page.', 'Avada' ),
						'id'              => 'woocommerce_shop_page_columns',
						'default'         => 4,
						'type'            => 'slider',
						'choices'     => [
							'min'  => 1,
							'max'  => 6,
							'step' => 1,
						],
						'update_callback' => [
							[
								'condition' => 'is_shop',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'woocommerce_related_columns' => [
						'label'           => esc_html__( 'WooCommerce Related/Up-Sell/Cross-Sell Product Number of Columns', 'Avada' ),
						'description'     => esc_html__( 'Controls the number of columns for the related and up-sell products on single posts and cross-sells on cart page.', 'Avada' ),
						'id'              => 'woocommerce_related_columns',
						'default'         => 4,
						'type'            => 'slider',
						'choices'     => [
							'min'  => 1,
							'max'  => 6,
							'step' => 1,
						],
						'transport'   => 'refresh',
						'output'      => [
							[
								'element'       => 'body',
								'function'      => 'attr',
								'attr'          => 'class',
								'value_pattern' => 'fusion-woo-related-columns-$',
								'remove_attrs'  => [ 'fusion-woo-related-columns-1', 'fusion-woo-related-columns-2', 'fusion-woo-related-columns-3', 'fusion-woo-related-columns-4', 'fusion-woo-related-columns-5', 'fusion-woo-related-columns-6' ],
							],
						],
					],
					'woocommerce_archive_page_columns' => [
						'label'           => esc_html__( 'WooCommerce Archive Number of Product Columns', 'Avada' ),
						'description'     => esc_html__( 'Controls the number of columns for the archive pages.', 'Avada' ),
						'id'              => 'woocommerce_archive_page_columns',
						'default'         => 3,
						'type'            => 'slider',
						'choices'     => [
							'min'  => 1,
							'max'  => 6,
							'step' => 1,
						],
					],
					'disable_woo_gallery' => [
						'label'           => esc_html__( 'Avada\'s WooCommerce Product Gallery Slider', 'Avada' ),
						'description'     => esc_html__( 'Turn on to enable Avada\'s product gallery slider.', 'Avada' ),
						'id'              => 'disable_woo_gallery',
						'default'         => '1',
						'type'            => 'switch',
					],
					'woocommerce_single_gallery_size' => [
						'label'       => esc_html__( 'WooCommerce Product Gallery Size', 'Avada' ),
						'description' => __( 'Controls the size of the single product page image gallery. For the image gallery zoom feature to work, the images you upload must be larger than the gallery size you select for this option. <strong>Important:</strong> When this option is changed, you may need to adjust the Single Product Image size setting in WooCommerce Settings to make sure that one is larger and also regenerate thumbnails. <a href="https://theme-fusion.com/documentation/avada/woocommerce-single-product-gallery/" target="_blank">See this post for more information.</a><br/>', 'Avada' ),
						'id'          => 'woocommerce_single_gallery_size',
						'default'     => '500px',
						'type'        => 'dimension',
						'choices'     => [ 'px' ],
						'css_vars'    => [
							[
								'name'    => '--woocommerce_single_gallery_size',
								'element' => '.images',
							],
						],
						'output'      => [
							[
								'element'       => '.product .summary.entry-summary',
								'property'      => 'margin-left',
								'value_pattern' => 'calc($ + 30px)',
							],
							[
								'element'       => '.rtl .product .summary.entry-summary',
								'property'      => 'margin-right',
								'value_pattern' => 'calc($ + 30px)',
							],
						],
					],
					'woocommerce_gallery_thumbnail_columns' => [
						'label'           => esc_html__( 'WooCommerce Product Gallery Thumbnails Columns', 'Avada' ),
						'description'     => esc_html__( 'Controls the number of columns of the single product page image gallery thumbnails. In order to avoid blurry thumbnails, make sure the Product Thumbnails size setting in WooCommerce Settings is large enough. It has to be at least WooCommerce Product Gallery Size setting divided by this number of columns.', 'Avada' ),
						'id'              => 'woocommerce_gallery_thumbnail_columns',
						'default'         => 4,
						'type'            => 'slider',
						'choices'     => [
							'min'  => 1,
							'max'  => 6,
							'step' => 1,
						],
						'update_callback' => [
							[
								'condition' => 'is_product',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'enable_woo_gallery_zoom' => [
						'label'           => esc_html__( 'WooCommerce Product Gallery Zoom', 'Avada' ),
						'description'     => __( 'Turn on to enable the WooCommerce gallery zoom feature. Important: Every product image you use must be larger than the gallery container for zoom to work correctly.<br/><a href="https://theme-fusion.com/documentation/avada/woocommerce-single-product-gallery/" target="_blank">See this post for more information.</a>', 'Avada' ),
						'id'              => 'enable_woo_gallery_zoom',
						'default'         => '1',
						'type'            => 'switch',
					],
					'woocommerce_enable_quick_view' => [
						'label'           => esc_html__( 'WooCommerce Product Quick View', 'Avada' ),
						'description'     => esc_html__( 'Turn on to enable product quick view for products.', 'Avada' ),
						'id'              => 'woocommerce_enable_quick_view',
						'default'         => '0',
						'type'            => 'switch',
					],
					'woocommerce_avada_ordering' => [
						'label'           => esc_html__( 'WooCommerce Shop Page Ordering Boxes', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the ordering boxes on the shop page.', 'Avada' ),
						'id'              => 'woocommerce_avada_ordering',
						'default'         => '1',
						'type'            => 'switch',
					],
					'woocommerce_disable_crossfade_effect' => [
						'label'           => esc_html__( 'WooCommerce Shop Page Crossfade Image Effect', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the product crossfade image effect on the shop page.', 'Avada' ),
						'id'              => 'woocommerce_disable_crossfade_effect',
						'default'         => '1',
						'type'            => 'switch',
					],
					'woocommerce_one_page_checkout' => [
						'label'           => esc_html__( 'WooCommerce One Page Checkout', 'Avada' ),
						'description'     => esc_html__( 'Turn on to use the one page checkout template.', 'Avada' ),
						'id'              => 'woocommerce_one_page_checkout',
						'default'         => '0',
						'type'            => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_checkout',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'woocommerce_enable_order_notes' => [
						'label'           => esc_html__( 'WooCommerce Order Notes on Checkout', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the order notes on the checkout page.', 'Avada' ),
						'id'              => 'woocommerce_enable_order_notes',
						'default'         => '1',
						'type'            => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_checkout',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'woocommerce_acc_link_main_nav' => [
						'label'           => esc_html__( 'WooCommerce My Account Link in Main Menu', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the "My Account" link in the main menu. Not compatible with Ubermenu.', 'Avada' ),
						'id'              => 'woocommerce_acc_link_main_nav',
						'default'         => '0',
						'type'            => 'switch',
						'class'       => 'fusion-or-gutter',
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_woocommerce_acc_link_main_nav_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_acc_link_main_nav_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_acc_link_main_nav' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],
						],
					],
					'woocommerce_cart_link_main_nav' => [
						'label'           => esc_html__( 'WooCommerce Cart Icon in Main Menu', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the cart icon in the main menu. Not compatible with Ubermenu.', 'Avada' ),
						'id'              => 'woocommerce_cart_link_main_nav',
						'default'         => '1',
						'type'            => 'switch',
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_woocommerce_cart_link_main_nav_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_link_main_nav_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_link_main_nav' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],
						],
					],
					'woocommerce_cart_counter' => [
						'label'       => esc_html__( 'WooCommerce Menu Cart Icon Counter', 'Avada' ),
						'description' => esc_html__( 'Turn on to display the WooCommerce cart counter circle.', 'Avada' ),
						'id'          => 'woocommerce_cart_counter',
						'default'     => '0',
						'type'        => 'switch',
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_woocommerce_cart_counter_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_counter_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_counter' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],
						],
					],
					'woocommerce_acc_link_top_nav' => [
						'label'           => esc_html__( 'WooCommerce My Account Link in Secondary Menu', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the "My Account" link in the secondary menu. Not compatible with Ubermenu.', 'Avada' ),
						'id'              => 'woocommerce_acc_link_top_nav',
						'default'         => '1',
						'type'            => 'switch',
						'class'           => 'fusion-or-gutter',
						'required'        => [
							[
								'setting'  => 'header_position',
								'operator' => '!=',
								'value'    => 'top',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v2',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v3',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v4',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v5',
							],
						],
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_woocommerce_acc_link_top_nav_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_acc_link_top_nav_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_acc_link_top_nav' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],
						],
					],
					'woocommerce_cart_link_top_nav' => [
						'label'           => esc_html__( 'WooCommerce Cart Icon in Secondary Menu', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the cart icon in the secondary menu. Not compatible with Ubermenu.', 'Avada' ),
						'id'              => 'woocommerce_cart_link_top_nav',
						'default'         => '1',
						'type'            => 'switch',
						'class'           => 'fusion-or-gutter',
						'required'        => [
							[
								'setting'  => 'header_position',
								'operator' => '!=',
								'value'    => 'top',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v2',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v3',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v4',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '=',
								'value'    => 'v5',
							],
						],
						// Partial refresh for the header.
						'partial_refresh' => [
							'header_content_woocommerce_cart_link_top_nav_remove_before_hook' => [
								'selector'            => '.avada-hook-before-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_link_top_nav_remove_after_hook' => [
								'selector'            => '.avada-hook-after-header-wrapper',
								'container_inclusive' => true,
								'render_callback'     => '__return_null',
							],
							'header_content_woocommerce_cart_link_top_nav' => [
								'selector'              => '.fusion-header-wrapper',
								'container_inclusive'   => true,
								'render_callback'       => [ 'Avada_Partial_Refresh_Callbacks', 'header' ],
								'success_trigger_event' => 'header-rendered',
							],
						],
					],
					'woocommerce_social_links' => [
						'label'           => esc_html__( 'WooCommerce Social Icons', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the social icons on single product posts.', 'Avada' ),
						'id'              => 'woocommerce_social_links',
						'default'         => '1',
						'type'            => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_product',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'woocommerce_toggle_grid_list' => [
						'label'           => esc_html__( 'WooCommerce Product Grid / List View', 'Avada' ),
						'description'     => esc_html__( 'Turn on to display the grid/list toggle on the main shop page and archive shop pages.', 'Avada' ),
						'id'              => 'woocommerce_toggle_grid_list',
						'default'         => '1',
						'type'            => 'switch',
					],
					'woocommerce_product_view' => [
						'type'        => 'radio-buttonset',
						'label'       => esc_html__( 'WooCommerce Product Default View', 'Avada' ),
						'description' => esc_html__( 'Sets the default product view for shop page and product archive pages.', 'Avada' ),
						'id'          => 'woocommerce_product_view',
						'default'     => 'grid',
						'choices'     => [
							'grid' => esc_html__( 'Grid', 'Avada' ),
							'list' => esc_html__( 'List', 'Avada' ),
						],
					],
					'woo_acc_msg_1' => [
						'label'           => esc_html__( 'WooCommerce Account Area Message 1', 'Avada' ),
						'description'     => esc_html__( 'Controls the text that displays in the first message box on the account page.', 'Avada' ),
						'id'              => 'woo_acc_msg_1',
						'default'         => 'Need Assistance? Call customer service at 888-555-5555.',
						'type'            => 'textarea',
						'partial_refresh' => [
							'partial_woo_acc_msg_1' => [
								'selector'            => '.avada-myaccount-user',
								'container_inclusive' => true,
								'render_callback'     => [ 'Avada_Partial_Refresh_Callbacks', 'wc_top_user_container' ],
							],
						],
					],
					'woo_acc_msg_2' => [
						'label'           => esc_html__( 'WooCommerce Account Area Message 2', 'Avada' ),
						'description'     => esc_html__( 'Controls the text that displays in the second message box on the account page.', 'Avada' ),
						'id'              => 'woo_acc_msg_2',
						'default'         => 'E-mail them at info@yourshop.com',
						'type'            => 'textarea',
						'partial_refresh' => [
							'partial_woo_acc_msg_2' => [
								'selector'            => '.avada-myaccount-user',
								'container_inclusive' => true,
								'render_callback'     => [ 'Avada_Partial_Refresh_Callbacks', 'wc_top_user_container' ],
							],
						],
					],
				],
			],
			'woocommerce_styling_options_subsection' => [
				'label'       => esc_html__( 'WooCommerce Styling', 'Avada' ),
				'description' => '',
				'id'          => 'woocommerce_styling_options_subsection',
				'icon'        => true,
				'type'        => 'sub-section',
				'fields'      => [
					'woocommerce_product_box_design' => [
						'type'        => 'radio-buttonset',
						'label'       => esc_html__( 'WooCommerce Product Box Design', 'Avada' ),
						'description' => esc_html__( 'Controls the design of the product boxes.', 'Avada' ),
						'id'          => 'woocommerce_product_box_design',
						'default'     => 'classic',
						'choices'     => [
							'classic' => esc_html__( 'Classic', 'Avada' ),
							'clean'   => esc_html__( 'Clean', 'Avada' ),
						],
					],
					'woocommerce_product_box_content_padding' => [
						'label'       => esc_html__( 'WooCommerce Product Box Content Padding', 'Avada' ),
						'description' => esc_html__( 'Controls the top/right/bottom/left padding of the products contents.', 'Avada' ),
						'id'          => 'woocommerce_product_box_content_padding',
						'choices'     => [
							'top'    => true,
							'bottom' => true,
							'left'   => true,
							'right'  => true,
							'units'  => [ 'px', '%' ],
						],
						'default'     => [
							'top'    => '20px',
							'bottom' => '15px',
							'left'   => '15px',
							'right'  => '15px',
						],
						'type'        => 'spacing',
						'css_vars'    => [
							[
								'name'   => '--woocommerce_product_box_content_padding-top',
								'choice' => 'top',
							],
							[
								'name'   => '--woocommerce_product_box_content_padding-bottom',
								'choice' => 'bottom',
							],
							[
								'name'   => '--woocommerce_product_box_content_padding-left',
								'choice' => 'left',
							],
							[
								'name'   => '--woocommerce_product_box_content_padding-right',
								'choice' => 'right',
							],
						],
					],
					'product_width_100' => [
						'label'       => esc_html__( '100% Width Page', 'Avada' ),
						'description' => esc_html__( 'Turn on to display product posts at 100% browser width according to the window size. Turn off to follow site width.', 'Avada' ),
						'id'          => 'product_width_100',
						'default'     => '0',
						'type'        => 'switch',
						'update_callback' => [
							[
								'condition' => 'is_product',
								'operator'  => '===',
								'value'     => true,
							],
						],
					],
					'woocommerce_equal_heights' => [
						'label'       => esc_html__( 'Equal Heights', 'Avada' ),
						'description' => esc_html__( 'Turn on to display grid boxes with equal heights per row.', 'Avada' ),
						'id'          => 'woocommerce_equal_heights',
						'default'     => 0,
						'type'        => 'switch',
						'output'      => [
							[
								'element'           => 'helperElement',
								'property'          => 'dummy',
								'callback'          => [
									'toggle_class',
									[
										'condition' => [ '', 'true' ],
										'element'   => 'body',
										'className' => 'fusion-woocommerce-equal-heights',
									],
								],
								'sanitize_callback' => '__return_empty_string',
							],
						],
					],
					'woocommerce_product_tab_design' => [
						'label'           => esc_html__( 'WooCommerce Tab Design', 'Avada' ),
						'description'     => esc_html__( 'Controls the design of all WooCommerce tabs.', 'Avada' ),
						'id'              => 'woocommerce_product_tab_design',
						'default'         => 'vertical',
						'type'            => 'radio-buttonset',
						'choices'     => [
							'horizontal' => esc_html__( 'Horizontal Tabs', 'Avada' ),
							'vertical'   => esc_html__( 'Vertical Tabs', 'Avada' ),
						],
						'output'      => [
							[
								'element'       => 'body',
								'function'      => 'attr',
								'attr'          => 'class',
								'value_pattern' => 'woo-tabs-$',
								'remove_attrs'  => [ 'woo-tabs-vertical', 'woo-tabs-horizontal' ],
							],
						],
					],
					'qty_bg_color' => [
						'label'           => esc_html__( 'WooCommerce Quantity Box Background Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the background color of the WooCommerce quantity box.', 'Avada' ),
						'id'              => 'qty_bg_color',
						'default'         => '#fbfaf9',
						'type'            => 'color-alpha',
						'css_vars'        => [
							[
								'name' => '--qty_bg_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'qty_bg_hover_color' => [
						'label'           => esc_html__( 'WooCommerce Quantity Box Hover Background Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the hover color of the WooCommerce quantity box.', 'Avada' ),
						'id'              => 'qty_bg_hover_color',
						'default'         => '#ffffff',
						'type'            => 'color-alpha',
						'css_vars'        => [
							[
								'name' => '--qty_bg_hover_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'woo_dropdown_bg_color' => [
						'label'           => esc_html__( 'WooCommerce Order Dropdown Background Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the background color of the WooCommerce order dropdowns.', 'Avada' ),
						'id'              => 'woo_dropdown_bg_color',
						'default'         => '#fbfaf9',
						'type'            => 'color-alpha',
						'css_vars'        => [
							[
								'name' => '--woo_dropdown_bg_color',
								'callback' => [ 'sanitize_color' ],
							],
							[
								'name'     => '--woo_dropdown_bg_color-1l',
								'callback' => [ 'lightness_adjust', 0.15 ],
							],
						],
					],
					'woo_dropdown_text_color' => [
						'label'           => esc_html__( 'WooCommerce Order Dropdown Text Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the color of the text and icons in the WooCommerce order dropdowns.', 'Avada' ),
						'id'              => 'woo_dropdown_text_color',
						'default'         => '#333333',
						'type'            => 'color-alpha',
						'css_vars'        => [
							[
								'name' => '--woo_dropdown_text_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'woo_dropdown_border_color' => [
						'label'           => esc_html__( 'WooCommerce Order Dropdown Border Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the border color in the WooCommerce order dropdowns.', 'Avada' ),
						'id'              => 'woo_dropdown_border_color',
						'default'         => '#dbdbdb',
						'type'            => 'color-alpha',
						'css_vars'        => [
							[
								'name' => '--woo_dropdown_border_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'woo_cart_bg_color' => [
						'label'           => esc_html__( 'WooCommerce Cart Menu Background Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the bottom section background color of the WooCommerce cart dropdown.', 'Avada' ),
						'id'              => 'woo_cart_bg_color',
						'default'         => '#fafafa',
						'type'            => 'color-alpha',
						'class'           => 'fusion-or-gutter',
						'required'        => [
							[
								'setting'  => 'header_position',
								'operator' => '!=',
								'value'    => 'top',
							],
							[
								'setting'  => 'header_layout',
								'operator' => '!=',
								'value'    => 'v6',
							],
						],
						'css_vars'        => [
							[
								'name' => '--woo_cart_bg_color',
								'callback' => [ 'sanitize_color' ],
							],
						],
					],
					'woo_icon_font_size' => [
						'label'           => esc_html__( 'WooCommerce Icon Font Size', 'Avada' ),
						'description'     => esc_html__( 'Controls the font size of the WooCommerce icons.', 'Avada' ),
						'id'              => 'woo_icon_font_size',
						'type'            => 'dimension',
						'default'         => '12px',
						'css_vars'        => [
							[
								'name' => '--woo_icon_font_size',
							],
						],
					],
				],
			],
		],
	 ] : [];

	return $sections;

}
