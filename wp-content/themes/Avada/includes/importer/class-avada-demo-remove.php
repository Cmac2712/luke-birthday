<?php
/**
 * A collection of functions used by the importer.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Importer
 * @since      5.2
 */

/**
 * Removes a demo.
 */
class Avada_Demo_Remove {

	/**
	 * The demo-type.
	 *
	 * @access private
	 * @since 5.2
	 * @var string
	 */
	private $demo_type;

	/**
	 * Avada_Demo_Content_Tracker instance.
	 *
	 * @access private
	 * @since 5.2
	 * @var object
	 */
	private $content_tracker;

	/**
	 * The class constructor.
	 *
	 * @access public
	 * @since 5.2
	 */
	public function __construct() {

		// Hook importer into admin init.
		add_action( 'wp_ajax_fusion_remove_demo_data', [ $this, 'remove_demo_stage' ] );
	}

	/**
	 * Main controller method.
	 *
	 * @access public
	 * @since 5.2
	 */
	public function remove_demo_stage() {

		check_ajax_referer( 'avada_demo_ajax', 'security' );

		if ( current_user_can( 'manage_options' ) ) {

			$this->demo_type = 'classic';
			if ( isset( $_POST['demoType'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['demoType'] ) ) ) {
				$this->demo_type = sanitize_text_field( wp_unslash( $_POST['demoType'] ) );
			}
			$remove_stages = [ '' ];
			if ( isset( $_POST['removeStages'] ) ) {
				$remove_stages = wp_unslash( $_POST['removeStages'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			if ( ! class_exists( 'Avada_Demo_Content_Tracker' ) ) {
				include_once wp_normalize_path( Avada::$template_dir_path . '/includes/importer/class-avada-demo-content-tracker.php' );
			}

			$this->content_tracker = new Avada_Demo_Content_Tracker( $this->demo_type );

			if ( ! empty( $remove_stages[0] ) && method_exists( $this, 'remove_' . $remove_stages[0] ) ) {
				call_user_func( [ $this, 'remove_' . $remove_stages[0] ] );
			}

			// We've just processed last import stage.
			if ( 1 === count( $remove_stages ) ) {

				if ( $this->content_tracker->get( 'general_data' ) ) {
					$this->remove_general_data();
				}

				// Removes demo from 'all' entry if needed.
				$this->content_tracker->reset_stage( 'all' );

				// Remove all demo history (backup).
				$this->content_tracker->remove_demo();

				// Reset all caches. Deletes downloaded demo data as well.
				fusion_reset_all_caches();

				echo 'demo removed';
			} else {
				echo 'Demo partially removed: ' . $remove_stages[0]; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			exit;
		}
	}

	/**
	 * Removes content completely for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_content() {

		$this->remove_post_types();
		$this->remove_terms();

		$content_types = [
			'post',
			'page',
			'attachment',
			'avada_portfolio',
			'avada_faq',
			'avada_layout',
			'fusion_icons',
			'product',
			'event',
			'forum',
		];

		foreach ( $content_types as $content_type ) {
			$this->content_tracker->reset_stage( $content_type );
		}

		// Restore global layout backup, since they are part of 'content' stage need to be handled separately.
		$this->remove_avada_layout();
	}

	/**
	 * Removes posts for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 * @param array  $post_types The post-types.
	 * @param string $meta_key   The meta-key.
	 */
	private function remove_post_types( $post_types = [], $meta_key = 'fusion_demo_import' ) {

		if ( empty( $post_types ) ) {
			$post_types = [
				'post',
				'page',
				'fusion_element',
				'fusion_template',
				'fusion_tb_layout',
				'fusion_tb_section',
				'fusion_icons',
				'avada_faq',
				'avada_portfolio',
				'attachment',
				'nav_menu_item',
				'product',
				'shop_order',
				'shop_coupon',
				'tribe_events',
				'tribe_venue',
				'tribe_organizer',
				'forum',
				'topic',
				'reply',
				'wpcf7_contact_form',
			];
		}

		$args = [
			'posts_per_page' => -1, // phpcs:ignore WPThemeReview.CoreFunctionality.PostsPerPage
			'post_type'      => $post_types,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => $meta_key,
					'value' => $this->demo_type,
				],
			],
		];

		$query = new WP_Query( $args );

		if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Removes terms for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_terms() {

		$history_terms = (array) $this->content_tracker->get( 'terms' );

		if ( ! empty( $history_terms ) ) {
			foreach ( $history_terms as $k => $t ) {

				if ( 'element_category' === $t['taxonomy'] ) {
					$term = get_term( $t['term_id'], $t['taxonomy'] );

					if ( ! is_wp_error( $term ) && ! empty( $term->count ) ) {
						continue;
					}
				}

				wp_delete_term( $t['term_id'], $t['taxonomy'] );
				unset( $history_terms[ $k ] );

			}

			$this->content_tracker->set( 'terms', $history_terms );
		}
	}

	/**
	 * Removes sliders for selected demos.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_sliders() {

		$this->remove_fusion_sliders();
		$this->remove_rev_sliders();
		$this->remove_layer_sliders();
		$this->content_tracker->reset_stage( 'sliders' );
	}

	/**
	 * Removes Fusion Sliders for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_fusion_sliders() {

		$this->remove_post_types( [ 'slide' ] );

		// This one is needed in case sliders were imported separately.
		$this->remove_post_types( [ 'attachment' ], 'fusion_slider_demo_import' );

		$history_sliders = $this->content_tracker->get( 'fusion_sliders' );

		if ( ! empty( $history_sliders ) ) {
			foreach ( $history_sliders as $k => $slider ) {

				wp_delete_term( $slider['term_id'], $slider['taxonomy'] );
				unset( $history_sliders[ $k ] );

			}
			$this->content_tracker->set( 'fusion_sliders', $history_sliders );
		}
	}

	/**
	 * Removes Slider Revolution sliders for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_rev_sliders() {

		if ( class_exists( 'RevSliderSlider' ) ) { // If revslider is activated.

			$slider = new RevSliderSlider();

			$history_sliders = $this->content_tracker->get( 'rev_sliders' );

			if ( ! empty( $history_sliders ) ) {

				foreach ( $history_sliders as $k => $slider_id ) {
					$slider->initByID( $slider_id );
					$slider->deleteSlider();

					unset( $history_sliders[ $k ] );
				}
				$this->content_tracker->set( 'rev_sliders', $history_sliders );
			}
		}
	}

	/**
	 * Removes Layer sliders and it's images from Media Library for selected demo.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_layer_sliders() {

		if ( class_exists( 'LS_Sliders' ) ) { // If layer slider is activated.

			include WP_PLUGIN_DIR . '/LayerSlider/classes/class.ls.exportutil.php';
			$slider_export = new LS_ExportUtil();

			$history_sliders = $this->content_tracker->get( 'layer_sliders' );

			if ( ! empty( $history_sliders ) ) {

				foreach ( $history_sliders as $k => $slider_id ) {

					// Delete slider images.
					$slider        = LS_Sliders::find( (int) $slider_id );
					$slider_images = $slider_export->getImagesForSlider( $slider['data'] );

					if ( ! empty( $slider_images ) && is_array( $slider_images ) ) {
						foreach ( $slider_images as $slider_image ) {

							$attachment_id = Fusion_Images::get_attachment_id_from_url( $slider_image );
							wp_delete_attachment( $attachment_id );

						}
					}

					// Delete slider.
					LS_Sliders::delete( (int) $slider_id );

					unset( $history_sliders[ $k ] );
				}
				$this->content_tracker->set( 'layer_sliders', $history_sliders );
			}
		}
	}

	/**
	 * Removes widgets for selected demo and restores backup.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_widgets() {

		update_option( 'sidebars_widgets', $this->content_tracker->get( 'sidebars_widgets' ) );
		update_option( 'sbg_sidebars', $this->content_tracker->get( 'sbg_sidebars' ) );

		$widgets = $this->content_tracker->get( 'widgets' );
		foreach ( $widgets as $widget ) {
			update_option( $widget->option_name, maybe_unserialize( $widget->option_value ) );
		}

		$this->content_tracker->reset_stage( 'widgets' );
	}

	/**
	 * Removes Theme Options for selected demo and restores backup.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_theme_options() {

		$theme_options = $this->content_tracker->get( 'theme_options' );

		if ( $theme_options ) {
			update_option( Avada::get_original_option_name(), $theme_options );
		} else {
			delete_option( Avada::get_original_option_name() );
		}

		$this->content_tracker->reset_stage( 'theme_options' );
	}

	/**
	 * Removes global layout for selected demo and restores backup.
	 *
	 * @access private
	 * @since 6.2
	 */
	private function remove_avada_layout() {

		$avada_layout = $this->content_tracker->get( 'fusion_tb_layout_default' );
		if ( $avada_layout ) {
			update_option( 'fusion_tb_layout_default', $avada_layout );
		}

		$this->content_tracker->reset_stage( 'avada_layout' );
	}

	/**
	 * Removes Convert Plugin for selected demo and restores backup.
	 *
	 * @access private
	 * @since 6.2
	 */
	private function remove_convertplug() {

		if ( defined( 'CP_VERSION' ) ) {

			$history_convertplug = $this->content_tracker->get( 'convertplug_modules' );

			if ( ! empty( $history_convertplug ) ) {

				foreach ( $history_convertplug as $k => $module ) {

					$data = [
						'style_id'       => $module[0],
						'option'         => 'smile_' . $module[1] . '_styles',
						'variant_option' => $module[1] . '_variant_tests',
						'deleteMethod'   => 'hard',

					];

					// Remove module.
					fusion_cp_delete_all_modal_action( $data );

					unset( $history_convertplug[ $k ] );
				}

				// Remove modules' attachments. Needed if only Convert Plugin was imported (and not Content).
				$this->remove_post_types( 'attachment' );

				$this->content_tracker->set( 'convertplug_modules', $history_convertplug );
			}

			$this->content_tracker->reset_stage( 'convertplug' );
		}
	}

	/**
	 * Removes 'general data' for selected demo and restores backup.
	 *
	 * @access private
	 * @since 5.2
	 */
	private function remove_general_data() {

		if ( $this->content_tracker->get( 'blogname' ) ) {
			update_option( 'blogname', $this->content_tracker->get( 'blogname' ) );
		}

		if ( $this->content_tracker->get( 'page_on_front' ) ) {
			update_option( 'page_on_front', $this->content_tracker->get( 'page_on_front' ) );
		}

		if ( $this->content_tracker->get( 'show_on_front' ) ) {
			update_option( 'show_on_front', $this->content_tracker->get( 'show_on_front' ) );
		}

		if ( $this->content_tracker->get( 'nav_menu_locations' ) ) {

			$menu_locations = maybe_unserialize( $this->content_tracker->get( 'nav_menu_locations' ) );

			foreach ( $menu_locations as $location => $menu_id ) {

				if ( 0 === $menu_id ) {
					continue;
				}

				if ( ! term_exists( (int) $menu_id, 'nav_menu' ) ) {
					unset( $menu_locations[ $location ] );
				}
			}

			// Menu items are removed with the rest of the content.
			set_theme_mod( 'nav_menu_locations', $menu_locations );
		}

		$this->content_tracker->reset_stage( 'general_data' );
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
