<?php
/**
 * Handles migration from SMOF to Redux.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 *
 * phpcs:ignoreFile WordPress.Security.NonceVerification
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handle migration from SMOF to Redux.
 */
class Avada_AvadaRedux_Migration extends Avada_Migrate {

	/**
	 * Instance.
	 *
	 * @static
	 * @access public
	 * @var null|object
	 */
	public static $instance = null;


	/**
	 * The Theme Options db name.
	 *
	 * @since 5.1.0
	 * @access public
	 * @var string
	 */
	private $avada_option_name = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $avada_option_name The option-name.
	 */
	public function __construct( $avada_option_name ) {

		// Only run on the dashboard.
		if ( ! is_admin() ) {
			return;
		}
		// Set the version.
		$this->version           = '400';
		$this->avada_option_name = $avada_option_name;

		// Set the language.
		if ( $_GET && isset( $_GET['lang'] ) ) {
			$this->lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
		}
		if ( ! empty( $this->lang ) && ! Avada::$lang_applied ) {
			Fusion_Multilingual::set_active_language( $this->lang );
		}
		$default_old_options = get_option( 'Avada_options', [] );
		// For multilingual sites, set the language for the options.
		if ( ! in_array( $this->lang, [ '', 'en', 'all', null ] ) ) {
			$old_options = get_option( 'Avada_options_' . Fusion_Multilingual::get_active_language(), [] );
		} else {
			$old_options = $default_old_options;
		}
		$new_options = get_option( $this->avada_option_name, [] );

		// If clean install, set builder and encoding active.
		if ( empty( $old_options ) && empty( $new_options ) ) {
			update_option( 'avada_disable_builder', 1 );
			update_option( 'avada_disable_encoding', 1 );
		}

		// No need to proceed if there's no data at all to migrate.
		if ( empty( $default_old_options ) ) {
			return;
		}

		// Add migration steps for previous versions of Avada.
		parent::update_installation( true );

		// No need to proceed if the old options are empty.
		if ( empty( $old_options ) && ! Fusion_Multilingual::get_available_languages() ) {
			return;
		}
		// Redirect to the migration script if needed.
		$trigger_migration = false;
		if ( ! $_GET || ! isset( $_GET['avada_update'] ) || '1' != $_GET['avada_update'] ) {
			if ( ! empty( $old_options ) ) {
				if ( empty( $new_options ) ) {
					$trigger_migration = true;
				} else {
					// Get a record of already run migrations and determine if we should continue or not.
					$migration_run = get_option( 'avada_migrations', [] );
					if ( isset( $migration_run[ $this->version ] ) ) {
						if ( is_array( $migration_run[ $this->version ] ) ) {
							if ( isset( $migration_run[ $this->version ]['finished'] ) && true !== $migration_run[ $this->version ]['finished'] ) {
								$trigger_migration = true;
							}
							if ( isset( $migration_run[ $this->version ]['started'] ) && true !== $migration_run[ $this->version ]['started'] ) {
								$trigger_migration = true;
							}
						} else {
							$trigger_migration = true;
						}
					} else {
						$trigger_migration = true;
					}
				}
			}
		}

		if ( $trigger_migration ) {
			Avada_Upgrade::clear_twitter_widget_transients();
			wp_safe_redirect( trailingslashit( admin_url() ) . 'index.php?avada_update=1&ver=400&step=0&new=1' );
			exit;
		}

		// Define the migration steps.
		$this->steps = [
			[
				'callback'    => '__return_true',
				'description' => esc_html__( 'Preparing to run Upgrade', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'migrate_sliders' ],
				'description' => esc_html__( 'Slider controls', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'migrate_checkboxes' ],
				'description' => esc_html__( 'Checkbox & Switch controls', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'dimension' ],
				'description' => esc_html__( 'Dimension controls', 'Avada' ),
			],
			// Caution: social-icons migration is placed before the colors migrations.
			// We're doing this to avoid mis-sanitizations of multi-color entries.
			[
				'callback'    => [ $this, 'social' ],
				'description' => esc_html__( 'Social Networks', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'color_alpha' ],
				'description' => esc_html__( 'HEX to RGBA colors conversion', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'color_hex' ],
				'description' => esc_html__( 'HEX colors', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'media_files' ],
				'description' => esc_html__( 'Media Files', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'spacing_1' ],
				'description' => esc_html__( 'Spacing Options', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'custom_fonts' ],
				'description' => esc_html__( 'Custom Fonts', 'Avada' ),
			],
			[
				'callback'    => [ $this, 'typography_1' ],
				/* translators: %1$s, %2$s: Numbers. */
				'description' => sprintf( esc_html__( 'Typography Options (step %1$s of %2$s)', 'Avada' ), '1', '2' ),
			],
			[
				'callback'    => [ $this, 'typography_2' ],
				/* translators: %1$s, %2$s: Numbers. */
				'description' => sprintf( esc_html__( 'Typography Options (step %1$s of %2$s)', 'Avada' ), '2', '2' ),
			],
			[
				'callback'    => [ $this, 'other_options' ],
				'description' => esc_html__( 'Other Options', 'Avada' ),
			],
		];

		// Run the parent class constructor.
		parent::__construct();

		// Copy the old options to the new options for new migrations.
		if ( $_GET && isset( $_GET['new'] ) && 1 == $_GET['new'] ) {
			$migration_run                               = get_option( 'avada_migrations', [] );
			$migration_run[ $this->version ]['started']  = true;
			$migration_run[ $this->version ]['finished'] = false;
			if ( ! isset( $migration_run['copied'] ) ) {
				$migration_run['copied'] = false;
			}
			// Copy default options.
			$old_options = get_option( 'Avada_options', [] );
			if ( false == $migration_run['copied'] ) {
				update_option( $this->avada_option_name, $old_options );
			}
			// Check multilingual installations.
			if ( Fusion_Multilingual::get_available_languages() && false == $migration_run['copied'] ) {
				// Loop languages.
				foreach ( Fusion_Multilingual::get_available_languages() as $language ) {
					// Process secondary languages.
					// 'en' & 'all' have already been handled before we check for multilingual.
					if ( ! in_array( $language, [ '', 'en', 'all', null ] ) ) {
						// Get the old language options.
						$old_language_options = get_option( 'Avada_options_' . $language, [] );
						// If the old language options are empty, use the standard options instead.
						if ( empty( $old_language_options ) ) {
							$old_language_options = $old_options;
						}
						// Update the new language options with the old ones.
						update_option( $this->avada_option_name . '_' . $language, $old_language_options );
					}
				}
				$migration_run['copied'] = true;
			}
			update_option( 'avada_migrations', $migration_run );
		}

	}

	/**
	 * Convert checkbox values.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function migrate_checkboxes() {
		$options = get_option( $this->avada_option_name, [] );

		// Logic switch options, from disable to enable.
		$zero_options = [
			'blog_pn_nav',
			'post_meta_author',
			'post_meta_date',
			'post_meta_cats',
			'post_meta_comments',
			'post_meta_read',
			'post_meta_tags',
			'portfolio_pn_nav',
			'portfolio_disable_first_featured_image',
			'status_lightbox',
			'status_lightbox_single',
			'mobile_slidingbar_widgets',
			'map_pin',
			'map_scrollwheel',
			'map_scale',
			'map_zoomcontrol',
			'search_excerpt',
			'search_featured_images',
			'disable_excerpts',
			'featured_images_pages',
			'link_image_rollover',
			'zoom_image_rollover',
			'title_image_rollover',
			'cats_image_rollover',
			'icon_circle_image_rollover',
			'smooth_scrolling',
			'disable_code_block_encoding',
			'disable_megamenu',
			'avada_rev_styles',
			'avada_styles_dropdowns',
			'disable_mobile_image_hovers',
			'status_yt',
			'status_vimeo',
			'status_gmap',
			'status_totop',
			'status_totop_mobile',
			'status_fusion_slider',
			'status_eslider',
			'status_fontawesome',
			'status_opengraph',
			'disable_date_rich_snippet_pages',
			'woocommerce_avada_ordering',
			'woocommerce_disable_crossfade_effect',
			'disable_woo_gallery',
		];

		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) && isset( $field['id'] ) && in_array( $field['type'], [ 'checkbox', 'switch', 'toggle' ] ) ) {
				$initial_value = $options[ $field['id'] ];
				if ( isset( $options[ $field['id'] ] ) ) {
					$value = $options[ $field['id'] ];
				} else {
					$value = $field['default'];
				}

				$value = ( 'yes' == strtolower( $value ) ) ? '1' : $value;
				$value = ( 'no' == strtolower( $value ) ) ? '0' : $value;

				// Take care of options in the $zero_options array.
				if ( in_array( $field['id'], $zero_options ) ) {
					$value = ( $value || '1' == $value ) ? '0' : '1';
				}
				$value                   = (int) $value;
				$options[ $field['id'] ] = ( $value ) ? '1' : '0';
				Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
			}
		}

		// Do disable_builder separately since it no longer exists in get_option_fields.
		$options['disable_builder'] = ( $options['disable_builder'] || '1' == $options['disable_builder'] ) ? '0' : '1';

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Returns an array of spacing elements to convert.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function spacing_1() {
		$spacing_options = [
			'logo_margin'         => [
				'top'    => 'margin_logo_top',
				'bottom' => 'margin_logo_bottom',
				'left'   => 'margin_logo_left',
				'right'  => 'margin_logo_right',
				'units'  => 'px',
			],
			'header_padding'      => [
				'top'    => 'margin_header_top',
				'bottom' => 'margin_header_bottom',
				'left'   => 'padding_header_left',
				'right'  => 'padding_header_right',
				'units'  => 'px',
			],
			'main_padding'        => [
				'top'    => 'main_top_padding',
				'bottom' => 'main_bottom_padding',
				'units'  => 'px',
			],
			'footer_area_padding' => [
				'top'    => 'footer_area_top_padding',
				'bottom' => 'footer_area_bottom_padding',
				'left'   => 'footer_area_left_padding',
				'right'  => 'footer_area_right_padding',
				'units'  => 'px',
			],
			'copyright_padding'   => [
				'top'    => 'copyright_top_padding',
				'bottom' => 'copyright_bottom_padding',
				'units'  => 'px',
			],
			'col_margin'          => [
				'top'    => 'col_top_margin',
				'bottom' => 'col_bottom_margin',
				'units'  => 'px',
			],
			'tagline_margin'      => [
				'top'    => 'tagline_margin_top',
				'bottom' => 'tagline_margin_bottom',
				'units'  => 'px',
			],
			'title_margin'        => [
				'top'    => 'title_top_margin',
				'bottom' => 'title_bottom_margin',
				'units'  => 'px',
			],
			'content_box_margin'  => [
				'top'    => 'content_box_margin_top',
				'bottom' => 'content_box_margin_bottom',
				'units'  => 'px',
			],
		];
		$this->spacing_options( $spacing_options );
	}

	/**
	 * Returns an array of typography elements to convert.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function typography_1() {
		$typography_options = [
			'body_typography' => [
				'font-family' => [
					'google'   => 'google_body',
					'standard' => 'standard_body',
					'custom'   => false,
				],
				'font-size'   => 'body_font_size',
				'line-height' => 'body_font_lh',
				'font-weight' => 'font_weight_body',
				'color'       => 'body_text_color',
			],
			'nav_typography'  => [
				'font-family'    => [
					'google'   => 'google_nav',
					'standard' => 'standard_nav',
					'custom'   => true,
				],
				'font-weight'    => 'font_weight_menu',
				'letter-spacing' => 'menu_font_ls',
			],
			'h1_typography'   => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h1_font_size',
				'line-height'    => 'h1_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h1_font_ls',
				'color'          => 'h1_color',
				'margin-top'     => 'h1_top_margin',
				'margin-bottom'  => 'h1_bottom_margin',
				'margin-units'   => 'em',

			],
			'h2_typography'   => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h2_font_size',
				'line-height'    => 'h2_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h2_font_ls',
				'color'          => 'h2_color',
				'margin-top'     => 'h2_top_margin',
				'margin-bottom'  => 'h2_bottom_margin',
				'margin-units'   => 'em',
			],
			'h3_typography'   => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h3_font_size',
				'line-height'    => 'h3_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h3_font_ls',
				'color'          => 'h3_color',
				'margin-top'     => 'h3_top_margin',
				'margin-bottom'  => 'h3_bottom_margin',
				'margin-units'   => 'em',
			],
		];
		$this->migrate_typography_fields( $typography_options );
	}

	/**
	 * Returns an array of typography elements to convert.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function typography_2() {
		$typography_options = [
			'h4_typography'              => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h4_font_size',
				'line-height'    => 'h4_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h4_font_ls',
				'color'          => 'h4_color',
				'margin-top'     => 'h4_top_margin',
				'margin-bottom'  => 'h4_bottom_margin',
				'margin-units'   => 'em',
			],
			'h5_typography'              => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h5_font_size',
				'line-height'    => 'h5_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h5_font_ls',
				'color'          => 'h5_color',
				'margin-top'     => 'h5_top_margin',
				'margin-bottom'  => 'h5_bottom_margin',
				'margin-units'   => 'em',
			],
			'h6_typography'              => [
				'font-family'    => [
					'google'   => 'google_headings',
					'standard' => 'standard_headings',
					'custom'   => true,
				],
				'font-size'      => 'h6_font_size',
				'line-height'    => 'h6_font_lh',
				'font-weight'    => 'font_weight_headings',
				'letter-spacing' => 'h6_font_ls',
				'color'          => 'h6_color',
				'margin-top'     => 'h6_top_margin',
				'margin-bottom'  => 'h6_bottom_margin',
				'margin-units'   => 'em',
			],
			'button_typography'          => [
				'font-family'    => [
					'google'   => 'google_button',
					'standard' => 'standard_button',
					'custom'   => false,
				],
				'font-weight'    => 'font_weight_button',
				'letter-spacing' => 'button_font_ls',
			],
			'footer_headings_typography' => [
				'font-family' => [
					'google'   => 'google_footer_headings',
					'standard' => 'standard_footer_headings',
					'custom'   => false,
				],
				'font-size'   => 'footw_font_size',
				'font-weight' => 'font_weight_footer_headings',
				'color'       => 'footer_headings_color',
			],
		];
		$this->migrate_typography_fields( $typography_options );
	}

	/**
	 * Migrate sliders.
	 */
	public function migrate_sliders() {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) && isset( $field['id'] ) && 'slider' == $field['type'] ) {
				$initial_value = ( isset( $options[ $field['id'] ] ) ) ? $options[ $field['id'] ] : 'UNDEFINED';

				$min  = ( isset( $field['choices'] ) && isset( $field['choices']['min'] ) ) ? intval( $field['choices']['min'] ) : 0;
				$max  = ( isset( $field['choices'] ) && isset( $field['choices']['max'] ) ) ? intval( $field['choices']['max'] ) : 100;
				$step = ( isset( $field['choices'] ) && isset( $field['choices']['step'] ) ) ? $field['choices']['step'] : 1;

				if ( isset( $options[ $field['id'] ] ) && '' != $options[ $field['id'] ] ) {
					$value = $options[ $field['id'] ];
					/**
					 * If the value does not exist, SMOF saves it as 0.
					 * We have to check if the value is empty or zero.
					 * If it is, then compare to the min value.
					 * If 0 is smaller than the min value, then set to default.
					 *
					 * The exception to the above rule is the search_results_per_page setting.
					 * If that setting is < 1, then we need to set it to the max value.
					 */
					if ( 'search_results_per_page' == $field['id'] && 1 > $value ) {
						$value = $max;
					}
					if ( ( '' == $value || 0 == $value ) && 0 < $min ) {
						if ( isset( $field['default'] ) ) {
							$value = $field['default'];
						}
					} else {
						// Limit lower & max values.
						$value = max( $min, min( $max, $options[ $field['id'] ] ) );
					}
					// Round using the step.
					if ( 1 == $step ) {
						$value = intval( $value );
					} elseif ( 1 < $step ) {
						$value = $min + ( round( ( $value - $min ) / $step ) * $step );
					} else {
						$value = $step * round( ( $value / $step ), 2 );
					}
					$options[ $field['id'] ] = $value;
				} else {
					if ( isset( $field['default'] ) ) {
						$options[ $field['id'] ] = Fusion_Sanitize::number( $field['default'] );
					}
				}
				Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Add the 'px' suffix to integers if needed.
	 */
	public function dimension() {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) && 'dimension' == $field['type'] ) {
				$initial_value = ( isset( $options[ $field['id'] ] ) ) ? $options[ $field['id'] ] : 'UNDEFINED';
				/**
				 * Convert previously integer options to strings
				 * by appending 'px' and sanitizing them.
				 * Please note that some settings use % and not px.
				 */
				if ( ! isset( $options[ $field['id'] ] ) ) {
					if ( isset( $field['default'] ) ) {
						$options[ $field['id'] ] = Fusion_Sanitize::size( $field['default'] );
					}
				}
				/**
				 * Check if the value is set to 'round'.
				 * If yes, then convert to 50%.
				 */
				$value = trim( $options[ $field['id'] ] );
				if ( 'round' == $value ) {
					$value = '50%';
				}
				$options[ $field['id'] ] = Fusion_Sanitize::size( $options[ $field['id'] ] );
				if ( empty( $options[ $field['id'] ] ) || is_null( $options[ $field['id'] ] ) ) {
					if ( isset( $field['default'] ) ) {
						$options[ $field['id'] ] = Fusion_Sanitize::size( $field['default'] );
					}
				}

				$numeric_value = Fusion_Sanitize::number( $options[ $field['id'] ] );
				if ( $numeric_value == $options[ $field['id'] ] ) {
					$percent_settings = [
						'sidebar_width',
						'sidebar_2_1_width',
						'sidebar_2_2_width',
						'ec_sidebar_width',
						'ec_sidebar_2_1_width',
						'ec_sidebar_2_2_width',
					];
					if ( in_array( $field['id'], $percent_settings ) ) {
						$options[ $field['id'] ] = $numeric_value . '%';
					} else {
						$options[ $field['id'] ] = intval( $options[ $field['id'] ] ) . 'px';
					}
				}
				Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Color-alpha options.
	 */
	public function color_alpha() {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			/**
			 * Make sure color-alpha fields are properly formatted.
			 */
			if ( isset( $options[ $field['id'] ] ) && isset( $field['type'] ) && 'color-alpha' == $field['type'] ) {
				$initial_value = ( isset( $options[ $field['id'] ] ) ) ? $options[ $field['id'] ] : 'UNDEFINED';
				if ( ! isset( $options[ $field['id'] ] ) ) {
					$value = $field['default'];
				} elseif ( empty( $options[ $field['id'] ] ) ) {
					$to_transparent = [
						'button_gradient_top_color',
						'button_gradient_bottom_color',
						'button_gradient_top_color_hover',
						'button_gradient_bottom_color_hover',
					];
					$value          = $field['default'];
					if ( in_array( $field['id'], $to_transparent ) ) {
						$value = 'rgba(255,255,255,0)';
					}
				} else {
					$value = $options[ $field['id'] ];
				}
				// Hack for fields that used to inherit their value from the primary color.
				if ( in_array(
					$field['id'],
					[
						'content_box_hover_animation_accent_color',
						'map_overlay_color',
					]
				) && '' == $value ) {
					$value = $options['primary_color'];
				}
				if ( is_array( $value ) ) {
					if ( isset( $value['rgba'] ) ) {
						$value = $value['rgba'];
					} elseif ( isset( $value['color'] ) ) {
						if ( '' == $value['color'] || 'transparent' == $value['color'] ) {
							$value = 'rgba(255,255,255,0)';
						} else {
							$opacity = 1;
							if ( isset( $value['opacity'] ) ) {
								$opacity = Fusion_Sanitize::number( $value['opacity'] );
							} elseif ( isset( $value['alpha'] ) ) {
								$opacity = Fusion_Sanitize::number( $value['alpha'] );
							}
							$color_obj        = Fusion_Color::new_color( $value['color'] );
							$color_obj->alpha = $opacity;
							$value            = ( 1 > $opacity ) ? $color_obj->to_css( 'rgba' ) : $value['color'];
						}
					}
				} elseif ( 'transparent' == $value || '' == $value ) {
					$value = 'rgba(255,255,255,0)';
				}
				// Make sure value is properly sanitized and then set it.
				$options[ $field['id'] ] = Fusion_Sanitize::color( $value );
				Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * HEX sanitization.
	 */
	public function color_hex() {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) ) {
				/**
				 * Make sure color fields are properly formatted.
				 */
				if ( 'color' == $field['type'] ) {
					$initial_value = ( isset( $options[ $field['id'] ] ) ) ? $options[ $field['id'] ] : 'UNDEFINED';
					if ( isset( $options[ $field['id'] ] ) ) {
						if ( ! empty( $options[ $field['id'] ] ) ) {
							$value = $options[ $field['id'] ];
							if ( is_string( $options[ $field['id'] ] ) ) {
								$value = trim( $options[ $field['id'] ] );
							}
						} else {
							$value = $field['default'];
						}
						if ( is_array( $value ) ) {
							if ( isset( $value['color'] ) ) {
								$value['color']          = ( 'transparent' == $value['color'] ) ? '#ffffff' : $value['color'];
								$options[ $field['id'] ] = Fusion_Color::new_color( $value['color'] )->to_css( 'hex' );
							}
						} else {
							$value = ( 'transparent' == $value ) ? '#ffffff' : $value;
							if ( false !== strpos( 'rgba', $value ) ) {
								$value = Fusion_Color::new_color( $value )->to_css( 'hex' );
							}
							$options[ $field['id'] ] = Fusion_Color::new_color( $value )->to_css( 'hex' );
						}
					} elseif ( isset( $field['default'] ) ) {
						$options[ $field['id'] ] = Fusion_Color::new_color( $field['default'] )->to_css( 'hex' );
					}
					Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
				}
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Media files migration.
	 */
	public function media_files() {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) ) {
				/**
				 * Make sure media fields are properly formatted.
				 */
				if ( 'media' == $field['type'] ) {
					$initial_value = ( isset( $options[ $field['id'] ] ) ) ? $options[ $field['id'] ] : 'UNDEFINED';
					if ( isset( $options[ $field['id'] ] ) ) {
						if ( ! empty( $options[ $field['id'] ] ) ) {
							$options[ $field['id'] ] = $this->single_media_file( $field );
						}
					}
					Avada_Migrate::generate_debug_log( [ $field['id'], $field['id'], $initial_value, $options[ $field['id'] ] ] );
				}
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );

	}

	/**
	 * A single media file.
	 *
	 * @since 4.0.0
	 * @param array $field The field array.
	 * @return array
	 */
	public function single_media_file( $field ) {
		global $wpdb;
		$options = get_option( $this->avada_option_name, [] );

		// Try to get the image from the media library.
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $options[ $field['id'] ] ) );
		// Attachment was not found in the media library.
		// We'll have to create it ourselves.
		if ( empty( $attachment ) ) {
			// Include required files to help us import the image to the media library.
			require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/media.php' );
			require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/file.php' );
			require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/image.php' );
			// Upload image to the media library.
			if ( is_string( $options[ $field['id'] ] ) ) {
				$new_image = media_sideload_image( $options[ $field['id'] ], 0, '', 'src' );
				// Check that media_sideload_image did not return a WP_Error object.
				if ( ! is_wp_error( $new_image ) ) {
					$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $new_image ) );
				}
			}
		}

		$new_media = [
			'url' => $options[ $field['id'] ],
		];

		if ( is_array( $attachment ) && isset( $attachment[0] ) ) {
			$attachment_id   = $attachment[0];
			$attachment_url  = wp_get_attachment_url( $attachment_id );
			$attachment_meta = wp_get_attachment_metadata( $attachment[0] );

			if ( ! isset( $attachment_meta['width'] ) || isset( $attachment['height'] ) ) {
				$file_path = get_attached_file( $attachment_id );
				wp_generate_attachment_metadata( $attachment_id, $file_path );
			}

			$new_media = [
				'url' => $attachment_url,
			];
			if ( isset( $attachment_meta['width'] ) ) {
				$new_media['width'] = $attachment_meta['width'];
			}
			if ( isset( $attachment_meta['height'] ) ) {
				$new_media['height'] = $attachment_meta['height'];
			}
		}

		// Return the modified option.
		return $new_media;

	}

	/**
	 * Spacing options.
	 *
	 * @since 4.0.0
	 * @access public
	 * @param array $spacing_options The spacing options to convert.
	 * @return void
	 */
	public function spacing_options( $spacing_options = [] ) {
		$options      = get_option( $this->avada_option_name, [] );
		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) ) {
				/**
				 * Convert spacing options.
				 */
				if ( isset( $field['id'] ) && array_key_exists( $field['id'], $spacing_options ) ) {
					if ( ! isset( $options[ $field['id'] ] ) ) {
						if ( isset( $field['default'] ) ) {
							$options[ $field['id'] ] = $field['default'];
						} else {
							$options[ $field['id'] ] = [];
						}
					}
					foreach ( $spacing_options[ $field['id'] ] as $direction => $old_setting ) {
						$initial_value = 'UNDEFINED';
						if ( isset( $options[ $spacing_options[ $field['id'] ][ $direction ] ] ) ) {
							// Get the previous value.
							$options[ $field['id'] ][ $direction ] = $options[ $spacing_options[ $field['id'] ][ $direction ] ];
							$initial_value                         = $options[ $field['id'] ][ $direction ];
							// Figure out the units we'll be using.
							$units = ( isset( $spacing_options[ $field['id'] ]['units'] ) ) ? $spacing_options[ $field['id'] ]['units'] : 'px';
							// If numeric value is the same as the whole value, then we don't have units and we'll need to add them.
							if ( Fusion_Sanitize::number( $options[ $field['id'] ][ $direction ] ) == $options[ $field['id'] ][ $direction ] ) {
								// Make sure value is not empty.
								if ( '' == trim( $options[ $field['id'] ][ $direction ] ) ) {
									$options[ $field['id'] ][ $direction ] = $field['default'][ $direction ];
								}
								// Format the value: numeric + units.
								$options[ $field['id'] ][ $direction ] = Fusion_Sanitize::number( $options[ $field['id'] ][ $direction ] ) . $units;
							}
						}
						if ( 'units' != $direction ) {
							Avada_Migrate::generate_debug_log( [ $old_setting, $field['id'] . '[' . $direction . ']', $initial_value, $options[ $field['id'] ][ $direction ] ] );
						}
					}
				}
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Migrate typography fields.
	 *
	 * @since 4.0.0
	 * @access public
	 * @param array $typography_options The typography options array.
	 * @return void
	 */
	public function migrate_typography_fields( $typography_options ) {
		global $wpdb;
		$options = get_option( $this->avada_option_name, [] );

		$is_custom_font = false;
		if ( isset( $options['custom_fonts'] ) ) {
			if ( isset( $options['custom_fonts']['woff'] ) && ! empty( $options['custom_fonts']['woff'] ) ) {
				if ( isset( $options['custom_fonts']['woff'][0] ) && isset( $options['custom_fonts']['woff'][0]['id'] ) ) {
					$is_custom_font   = true;
					$woff_path        = get_attached_file( $options['custom_fonts']['woff'][0]['id'] );
					$custom_font_name = str_replace( '.woff', '', basename( $woff_path ) );
					$custom_font_name = str_replace( ' ', '-', $custom_font_name );
				}
			}
		}

		// Get subsets from gfont_settings.
		$subset = false;
		if ( isset( $options['gfont_settings'] ) && ! empty( $options['gfont_settings'] ) ) {
			if ( false !== strpos( $options['gfont_settings'], 'subset' ) ) {
				$gfont_settings = wp_parse_args( $options['gfont_settings'], [] );
			} else {
				$all_subsets    = Fusion_Data::font_subsets();
				$gfont_settings = [];
				foreach ( $all_subsets as $available_subset ) {
					if ( false !== strpos( $options['gfont_settings'], $available_subset ) ) {
						$gfont_settings['subset'][] = $available_subset;
					}
				}
			}
			if ( isset( $gfont_settings['subset'] ) && ! empty( $gfont_settings['subset'] ) ) {
				if ( is_array( $gfont_settings['subset'] ) ) {
					$subsets = $gfont_settings['subset'];
				} else {
					if ( false !== strpos( $gfont_settings['subset'], ',' ) ) {
						$subsets = explode( ',', $gfont_settings['subset'] );
					} else {
						$all_subsets = Fusion_Data::font_subsets();
						$subsets     = [];
						foreach ( $all_subsets as $available_subset ) {
							if ( false !== strpos( $options['gfont_settings'], $available_subset ) ) {
								$subsets[] = $available_subset;
							}
						}
					}
				}

				if ( 1 < count( $subsets ) ) {
					$subset = ( 'latin' == $subsets[0] ) ? $subsets[1] : $subsets[0];
				} else {
					$subset = $subsets[0];
				}
			} else {
				$subset = ( isset( $gfont_settings['subset'] ) ) ? $gfont_settings['subset'] : false;
			}
		}
		$subset = ( 'latin' == $subset ) ? false : $subset;

		$this->fields = Avada_Options::get_option_fields();

		foreach ( $this->fields as $field ) {
			if ( isset( $field['type'] ) ) {
				/**
				 * Convert typography fields.
				 */
				if ( isset( $field['id'] ) && array_key_exists( $field['id'], $typography_options ) ) {
					$options[ $field['id'] ] = [];

					/**
					 * Font-family.
					 */
					if ( isset( $typography_options[ $field['id'] ]['font-family'] ) ) {
						if ( is_array( $typography_options[ $field['id'] ]['font-family'] ) ) {
							// Standard fonts.
							if ( isset( $typography_options[ $field['id'] ]['font-family']['standard'] ) && isset( $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ] ) ) {
								if ( ! in_array( $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ], [ '', 'None', 'none', 'Select Font' ] ) ) {
									$options[ $field['id'] ]['font-family'] = $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ];
								} elseif ( 'body_typography' != $field['id'] && isset( $options['body_typography']['font-backup'] ) ) {
									$options[ $field['id'] ]['font-backup'] = $options['body_typography']['font-backup'];
								}
							}
							// Google fonts.
							if ( isset( $typography_options[ $field['id'] ]['font-family']['google'] ) && isset( $options[ $typography_options[ $field['id'] ]['font-family']['google'] ] ) && ! in_array( $options[ $typography_options[ $field['id'] ]['font-family']['google'] ], [ '', 'None', 'none' ] ) ) {
								$options[ $field['id'] ]['font-family'] = $options[ $typography_options[ $field['id'] ]['font-family']['google'] ];
								$options[ $field['id'] ]['google']      = 'true';
								// Use standard font as backup font.
								if ( isset( $typography_options[ $field['id'] ]['font-family']['standard'] ) && isset( $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ] ) ) {
									if ( ! in_array( $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ], [ '', 'None', 'none', 'Select Font' ] ) ) {
										$options[ $field['id'] ]['font-backup'] = $options[ $typography_options[ $field['id'] ]['font-family']['standard'] ];
									} else {
										$options[ $field['id'] ]['font-backup'] = 'Arial, Helvetica, sans-serif';
									}
								}
							}
							// Handle custom-fonts.
							if ( isset( $custom_font_name ) && isset( $typography_options[ $field['id'] ]['font-family']['custom'] ) && true === $typography_options[ $field['id'] ]['font-family']['custom'] && $is_custom_font ) {
								$options[ $field['id'] ]['font-family'] = $custom_font_name;
							}
						} else {
							$options[ $field['id'] ]['font-family'] = $options[ $typography_options[ $field['id'] ]['font-family'] ];
						}
					}
					/**
					 * Font-size.
					 */
					if ( isset( $typography_options[ $field['id'] ]['font-size'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['font-size'] ] ) && ! isset( $options[ $field['id'] ]['font-size'] ) ) {
							$options[ $field['id'] ]['font-size'] = $options[ $typography_options[ $field['id'] ]['font-size'] ] . 'px';
						}
					}
					/**
					 * Line-height.
					 */
					if ( isset( $typography_options[ $field['id'] ]['line-height'] ) && isset( $options[ $typography_options[ $field['id'] ]['line-height'] ] ) ) {
						$font_size   = intval( $options[ $field['id'] ]['font-size'] );
						$line_height = intval( $options[ $typography_options[ $field['id'] ]['line-height'] ] );
						if ( 4 < $line_height ) {
							$options[ $field['id'] ]['line-height'] = round( ( $line_height / $font_size ), 2 );
						}
					}
					/**
					 * Font-weight.
					 */
					if ( isset( $typography_options[ $field['id'] ]['font-weight'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['font-weight'] ] ) ) {
							$options[ $field['id'] ]['font-weight'] = (int) $options[ $typography_options[ $field['id'] ]['font-weight'] ];
						}
					}
					/**
					 * Letter-spacing.
					 */
					if ( isset( $typography_options[ $field['id'] ]['letter-spacing'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['letter-spacing'] ] ) ) {
							$options[ $field['id'] ]['letter-spacing'] = intval( $options[ $typography_options[ $field['id'] ]['letter-spacing'] ] ) . 'px';
						}
					}
					/**
					 * Color.
					 */
					if ( isset( $typography_options[ $field['id'] ]['color'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['color'] ] ) ) {
							$options[ $field['id'] ]['color'] = $options[ $typography_options[ $field['id'] ]['color'] ];
						}
					}
					/**
					 * Margin-top.
					 */
					if ( isset( $typography_options[ $field['id'] ]['margin-top'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['margin-top'] ] ) ) {
							// Get the previous value.
							$options[ $field['id'] ]['margin-top'] = $options[ $typography_options[ $field['id'] ]['margin-top'] ];
							// Figure out the units we'll be using.
							$units = ( isset( $typography_options[ $field['id'] ]['margin-units'] ) ) ? $typography_options[ $field['id'] ]['margin-units'] : 'em';
							// If numeric value is the same as the whole value, then we don't have units and we'll need to add them.
							if ( Fusion_Sanitize::number( $options[ $field['id'] ]['margin-top'] ) == $options[ $field['id'] ]['margin-top'] ) {
								// Format the value: numeric + units.
								$options[ $field['id'] ]['margin-top'] = $options[ $field['id'] ]['margin-top'] . $units;
							}
						}
					}

					/**
					 * Margin-bottom.
					 */
					if ( isset( $typography_options[ $field['id'] ]['margin-bottom'] ) ) {
						if ( isset( $options[ $typography_options[ $field['id'] ]['margin-bottom'] ] ) ) {
							// Get the previous value.
							$options[ $field['id'] ]['margin-bottom'] = $options[ $typography_options[ $field['id'] ]['margin-bottom'] ];
							// Figure out the units we'll be using.
							$units = ( isset( $typography_options[ $field['id'] ]['margin-units'] ) ) ? $typography_options[ $field['id'] ]['margin-units'] : 'em';
							// If numeric value is the same as the whole value, then we don't have units and we'll need to add them.
							if ( Fusion_Sanitize::number( $options[ $field['id'] ]['margin-bottom'] ) == $options[ $field['id'] ]['margin-bottom'] ) {
								// Format the value: numeric + units.
								$options[ $field['id'] ]['margin-bottom'] = $options[ $field['id'] ]['margin-bottom'] . $units;
							}
						}
					}

					/**
					 * Add fallbacks to default values.
					 */
					if ( isset( $all_new_fields[ $field['id'] ]['default']['font-family'] ) && ! isset( $options[ $field['id'] ]['font-family'] ) ) {
						$options[ $field['id'] ]['font-family'] = $all_new_fields[ $field['id'] ]['default']['font-family'];
					}
					if ( isset( $all_new_fields[ $field['id'] ]['default']['font-size'] ) && ! isset( $options[ $field['id'] ]['font-size'] ) ) {
						$options[ $field['id'] ]['font-size'] = $all_new_fields[ $field['id'] ]['default']['font-size'];
					}
					if ( isset( $all_new_fields[ $field['id'] ]['default']['line-height'] ) && ! isset( $options[ $field['id'] ]['line-height'] ) ) {
						$options[ $field['id'] ]['line-height'] = $all_new_fields[ $field['id'] ]['default']['line-height'];
					}
					if ( isset( $all_new_fields[ $field['id'] ]['default']['font-weight'] ) && ! isset( $options[ $field['id'] ]['font-weight'] ) ) {
						$options[ $field['id'] ]['font-weight'] = $all_new_fields[ $field['id'] ]['default']['font-weight'];
					}
					if ( isset( $all_new_fields[ $field['id'] ]['default']['letter-spacing'] ) && ! isset( $options[ $field['id'] ]['letter-spacing'] ) ) {
						$options[ $field['id'] ]['letter-spacing'] = $all_new_fields[ $field['id'] ]['default']['letter-spacing'];
					}
					if ( isset( $all_new_fields[ $field['id'] ]['default']['color'] ) && ! isset( $options[ $field['id'] ]['color'] ) ) {
						$options[ $field['id'] ]['color'] = $all_new_fields[ $field['id'] ]['default']['color'];
					}

					// Add subsets.
					if ( $subset ) {
						$options[ $field['id'] ]['subsets'] = $subset;
					}
				}
			}
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Custom fonts.
	 */
	public function custom_fonts() {
		global $wpdb;
		$options        = get_option( $this->avada_option_name, [] );
		$is_custom_font = (
			(
				( isset( $options['custom_font_woff']['url'] ) && $options['custom_font_woff']['url'] ) &&
				( isset( $options['custom_font_ttf']['url'] ) && $options['custom_font_ttf']['url'] ) &&
				( isset( $options['custom_font_svg']['url'] ) && $options['custom_font_svg']['url'] ) &&
				( isset( $options['custom_font_eot']['url'] ) && $options['custom_font_eot'] )
			) || (
				( isset( $options['custom_font_woff'] ) && $options['custom_font_woff'] ) &&
				( isset( $options['custom_font_ttf'] ) && $options['custom_font_ttf'] ) &&
				( isset( $options['custom_font_svg'] ) && $options['custom_font_svg'] ) &&
				( isset( $options['custom_font_eot'] ) && $options['custom_font_eot'] )
			)
		);
		// Convert custom fonts.
		if ( ! $is_custom_font ) {
			return;
		}
		// Get the files.
		$custom_font_woff = ( is_array( $options['custom_font_woff'] ) && isset( $options['custom_font_woff']['url'] ) ) ? $options['custom_font_woff']['url'] : $options['custom_font_woff'];
		$custom_font_ttf  = ( is_array( $options['custom_font_ttf'] ) && isset( $options['custom_font_ttf']['url'] ) ) ? $options['custom_font_ttf']['url'] : $options['custom_font_ttf'];
		$custom_font_svg  = ( is_array( $options['custom_font_svg'] ) && isset( $options['custom_font_svg']['url'] ) ) ? $options['custom_font_svg']['url'] : $options['custom_font_svg'];
		$custom_font_eot  = ( is_array( $options['custom_font_eot'] ) && isset( $options['custom_font_eot']['url'] ) ) ? $options['custom_font_eot']['url'] : $options['custom_font_eot'];
		// Get the attachment IDs.
		$attachment_woff = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $custom_font_woff ) );
		$attachment_ttf  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $custom_font_ttf ) );
		$attachment_svg  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $custom_font_svg ) );
		$attachment_eot  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s;", $custom_font_eot ) );
		// Get the font name from the filename.
		$custom_font_name = 'custom-font';
		if ( is_array( $attachment_woff ) && isset( $attachment_woff[0] ) ) {
			$woff_path        = get_attached_file( $attachment_woff[0] );
			$custom_font_name = str_replace( '.woff', '', basename( $woff_path ) );
			$custom_font_name = str_replace( ' ', '-', $custom_font_name );
		}

		if (
			( ! is_array( $attachment_woff ) || ! isset( $attachment_woff[0] ) ) ||
			( ! is_array( $attachment_ttf ) || ! isset( $attachment_ttf[0] ) ) ||
			( ! is_array( $attachment_svg ) || ! isset( $attachment_svg[0] ) ) ||
			( ! is_array( $attachment_eot ) || ! isset( $attachment_eot[0] ) )
		) {
			return;
		}

		$options['custom_fonts'] = [
			'avadaredux_repeater_data' => [
				[
					'title' => '',
				],
			],
			'name'                     => [
				$custom_font_name,
			],
			'woff'                     => [
				[
					'url'       => $custom_font_woff,
					'id'        => $attachment_woff[0],
					'height'    => '',
					'width'     => '',
					'thumbnail' => trailingslashit( includes_url() ) . 'images/media/default.png',
				],
			],
			'ttf'                      => [
				[
					'url'       => $custom_font_ttf,
					'id'        => $attachment_ttf[0],
					'height'    => '',
					'width'     => '',
					'thumbnail' => trailingslashit( includes_url() ) . 'images/media/default.png',
				],
			],
			'svg'                      => [
				[
					'url'       => $custom_font_svg,
					'id'        => $attachment_svg[0],
					'height'    => '',
					'width'     => '',
					'thumbnail' => trailingslashit( includes_url() ) . 'images/media/default.png',
				],
			],
			'eot'                      => [
				[
					'url'       => $custom_font_eot,
					'id'        => $attachment_eot[0],
					'height'    => '',
					'width'     => '',
					'thumbnail' => trailingslashit( includes_url() ) . 'images/media/default.png',
				],
			],
		];

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Social networks.
	 */
	public function social() {
		$options = get_option( $this->avada_option_name, [] );
		/**
		 * Convert social-sorter control to the new format.
		 * We used to have multiple separate settings, all saved as strings.
		 * We'll be converting this to a single setting, but an array.
		 */
		$new_social = [];
		if ( isset( $options['social_sorter'] ) ) {
			/**
			 * Setting is saved as a comma-separated string with all values included.
			 */
			$values = explode( ',', $options['social_sorter'] );
			/**
			 * Get colors and determine if we want to use a different color for each social network.
			 * We'll be doing this for the following settings:
			 * footer_social_links_icon_color
			 * footer_social_links_box_color
			 * header_social_links_icon_color
			 * header_social_links_box_color
			 *
			 * The goal is to determin if we should turn on the brand-colors options.
			 */
			$footer_social_links_icon_color = isset( $options['footer_social_links_icon_color'] ) ? $options['footer_social_links_icon_color'] : '';
			$footer_social_links_box_color  = isset( $options['footer_social_links_box_color'] ) ? $options['footer_social_links_box_color'] : '';
			$header_social_links_icon_color = isset( $options['header_social_links_icon_color'] ) ? $options['header_social_links_icon_color'] : '';
			$header_social_links_box_color  = isset( $options['header_social_links_box_color'] ) ? $options['header_social_links_box_color'] : '';

			$use_brand_colors_on_footer = false;
			$use_brand_colors_on_footer = ( false !== strpos( $footer_social_links_icon_color, '|' ) ) ? true : $use_brand_colors_on_footer;
			$use_brand_colors_on_footer = ( false !== strpos( $footer_social_links_box_color, '|' ) ) ? true : $use_brand_colors_on_footer;

			$use_brand_colors_on_header = false;
			$use_brand_colors_on_header = ( false !== strpos( $header_social_links_icon_color, '|' ) ) ? true : $use_brand_colors_on_header;
			$use_brand_colors_on_header = ( false !== strpos( $header_social_links_box_color, '|' ) ) ? true : $use_brand_colors_on_header;

			$options['footer_social_links_color_type'] = ( $use_brand_colors_on_footer ) ? 'brand' : 'custom';
			$options['header_social_links_color_type'] = ( $use_brand_colors_on_header ) ? 'brand' : 'custom';

			$active_icons = [];
			// Find active acons and their colors.
			foreach ( $values as $key => $row ) {
				// Check if there's a setting with that name.
				if ( isset( $options[ $row ] ) ) {
					if ( isset( $options[ $options[ $row ] ] ) && ! empty( $options[ $options[ $row ] ] ) ) {
						$active_icons[] = $row;
					}
				}
			}

			$new_social['avadaredux_repeater_data'] = [];
			foreach ( $active_icons as $active_icon ) {
				$new_social['avadaredux_repeater_data'][] = [
					'title' => '',
				];
			}
			// Process each row in the array separately.
			foreach ( $values as $key => $row ) {
				// Check if there's a setting with that name.
				if ( isset( $options[ $row ] ) ) {
					if ( false !== array_search( $row, $active_icons ) ) {
						$active_icon_key = array_search( $row, $active_icons );
					}
					// Check if a URL is defined for this social network.
					if ( isset( $active_icon_key ) && isset( $options[ $options[ $row ] ] ) && ! empty( $options[ $options[ $row ] ] ) ) {
						$new_social['icon'][ $active_icon_key ] = str_replace( [ 'google', 'googleplus' ], 'gplus', str_replace( '_link', '', $options[ $row ] ) );
						$new_social['url'][ $active_icon_key ]  = $options[ $options[ $row ] ];
					}
				}
			}
			/**
			 * Take care of custom Icon.
			 */
			if ( isset( $options['custom_icon_image'] ) && ! empty( $options['custom_icon_image'] ) ) {
				// Get the key for the custom icon by counting other icons.
				$custom_icon_key = count( $new_social['icon'] );
				// Set the 'avadaredux_repeater_data' for this icon.
				$new_social['avadaredux_repeater_data'][ $custom_icon_key ] = [
					'title' => '',
				];
				// Set the icon type to custom.
				$new_social['icon'][ $custom_icon_key ] = 'custom';
				// Set the icon name.
				$new_social['custom_title'][ $custom_icon_key ] = isset( $options['custom_icon_name'] ) ? $options['custom_icon_name'] : '';
				// Set the URL.
				$new_social['url'][ $custom_icon_key ] = ( isset( $options['custom_icon_link'] ) ) ? $options['custom_icon_link'] : '';
				// Make sure color values are not unset.
				$new_social['header_box_color'][ $custom_icon_key ] = '';
				$new_social['footer_box_color'][ $custom_icon_key ] = '';
				// Get the image.
				// If we're using a retina image then use that instead,
				// and set the retina checkbox to 1.
				if ( isset( $options['custom_icon_image_retina'] ) && ! empty( $options['custom_icon_image_retina'] ) ) {
					$new_social['custom_source'][ $custom_icon_key ] = $this->single_media_file(
						[
							'id' => 'custom_icon_image_retina',
						]
					);
				} else {
					$new_social['custom_source'][ $custom_icon_key ] = $this->single_media_file(
						[
							'id' => 'custom_icon_image',
						]
					);
				}
			}
		}
		$options['social_media_icons'] = $new_social;

		/**
		 * Decide if we want to use brand colors or custom colors.
		 */
		$options['sharing_social_links_color_type'] = 'custom';
		if ( isset( $options['sharing_social_links_icon_color'] ) && false !== strpos( $options['sharing_social_links_icon_color'], '|' ) ) {
			$options['sharing_social_links_color_type'] = 'brand';
		}
		if ( isset( $options['sharing_social_links_box_color'] ) && false !== strpos( $options['sharing_social_links_box_color'], '|' ) ) {
			$options['sharing_social_links_color_type'] = 'brand';
		}

		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Various other options that don't fit anywhere else.
	 */
	public function other_options() {
		$options = get_option( $this->avada_option_name, [] );

		// Convert the "round" option to "50%".
		$round_options = [
			'content_box_icon_circle_radius',
			'content_box_icon_circle_radius',
			'social_links_boxed_radius',
			'header_social_links_boxed_radius',
			'footer_social_links_boxed_radius',
			'sharing_social_links_boxed_radius',
			'imageframe_border_radius',
			'person_border_radius',
			'flip_boxes_border_radius',
		];
		foreach ( $round_options as $round_option ) {
			if ( isset( $options[ $round_option ] ) ) {
				$initial_value = ( isset( $options[ $round_option ] ) ) ? $options[ $round_option ] : 'UNDEFINED';
				if ( 'round' == strtolower( trim( $options[ $round_option ] ) ) ) {
					$options[ $round_option ] = '50%';
				}
				Avada_Migrate::generate_debug_log( [ $round_option, $round_option, $initial_value, $options[ $round_option ] ] );
			}
		}

		$dimensions_fields = [
			'slider_nav_box_dimensions' => [
				'width'  => 'slider_nav_box_width',
				'height' => 'slider_nav_box_height',
			],
			'tfes_dimensions'           => [
				'width'  => 'tfes_slider_width',
				'height' => 'tfes_slider_height',
			],
			'gmap_dimensions'           => [
				'width'  => 'gmap_width',
				'height' => 'gmap_height',
			],
			'lightbox_video_dimensions' => [
				'width'  => 'lightbox_video_width',
				'height' => 'lightbox_video_height',
			],
		];

		$this->fields = Avada_Options::get_option_fields();

		foreach ( $dimensions_fields as $dimensions_field => $args ) {
			$initial_value_width  = 'UNDEFINED';
			$initial_value_height = 'UNDEFINED';
			$width                = ( isset( $this->fields[ $dimensions_field ]['default']['width'] ) ) ? $this->fields[ $dimensions_field ]['default']['width'] : '';
			if ( isset( $options[ $args['width'] ] ) ) {
				$width               = $options[ $args['width'] ];
				$initial_value_width = $width;
			}
			$height = ( isset( $this->fields[ $dimensions_field ]['default']['height'] ) ) ? $this->fields[ $dimensions_field ]['default']['height'] : '';
			if ( isset( $options[ $args['height'] ] ) ) {
				$height               = $options[ $args['height'] ];
				$initial_value_height = $height;
			}
			$options[ $dimensions_field ] = [
				'width'  => Fusion_Sanitize::size( $width ),
				'height' => Fusion_Sanitize::size( $height ),
			];
			Avada_Migrate::generate_debug_log( [ $args['width'], $dimensions_field . '[width]', $initial_value_width, $options[ $dimensions_field ]['width'] ] );
			Avada_Migrate::generate_debug_log( [ $args['height'], $dimensions_field . '[height]', $initial_value_height, $options[ $dimensions_field ]['height'] ] );
		}

		$capitalization_check_fields = [
			'excerpt_base',
			'sidenav_behavior',
		];
		foreach ( $capitalization_check_fields as $capitalization_check_field ) {
			if ( isset( $options[ $capitalization_check_field ] ) ) {
				$initial_value                          = $options[ $capitalization_check_field ];
				$options[ $capitalization_check_field ] = ucfirst( $options[ $capitalization_check_field ] );
				Avada_Migrate::generate_debug_log( [ $capitalization_check_field, $capitalization_check_field, $initial_value, $options[ $capitalization_check_field ] ] );
			}
		}

		// SMOF default value is "show".
		if ( isset( $options['faq_filters'] ) && 'show' == $options['faq_filters'] ) {
			$options['faq_filters'] = 'yes';
		}

		if ( isset( $options['load_more_posts_button_bg_color'] ) ) {
			$options['blog_load_more_posts_button_bg_color']      = $options['load_more_posts_button_bg_color'];
			$options['portfolio_load_more_posts_button_bg_color'] = $options['load_more_posts_button_bg_color'];
		}

		if ( isset( $options['pagination_box_padding'] ) && ! is_array( $options['pagination_box_padding'] ) ) {
			$options['pagination_box_padding'] = trim( $options['pagination_box_padding'] );
			if ( strpos( $options['pagination_box_padding'], ' ' ) ) {
				$values                            = explode( ' ', $options['pagination_box_padding'] );
				$options['pagination_box_padding'] = [
					'height' => $values[0],
					'width'  => $values[1],
				];
			} else {
				$value                             = $options['pagination_box_padding'];
				$options['pagination_box_padding'] = [
					'width'  => $value,
					'height' => $value,
				];
			}
		}
		// Update the options with our modifications.
		update_option( $this->avada_option_name, $options );
	}

	/**
	 * Runs when all steps have been completed.
	 */
	public function finished() {

		// Make sure initial values are set without need to save.
		$options = get_option( $this->avada_option_name, [] );

		// Disable builder needs switched here because logic switch wont trigger earlier due option being removed.
		update_option( 'avada_disable_builder', $options['disable_builder'] );
		update_option( 'avada_disable_encoding', $options['disable_code_block_encoding'] );

		// Reset the css.
		update_option( 'fusion_dynamic_css_posts', [] );

		// Update the 'avada_migrations' option.
		$migration_run                               = get_option( 'avada_migrations', [] );
		$migration_run[ $this->version ]['finished'] = true;
		unset( $migration_run['copied'] );
		update_option( 'avada_migrations', $migration_run );

	}
}
