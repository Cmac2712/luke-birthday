<?php
/**
 * Fusion Layout Sections Builder.
 *
 * @package Fusion-Builder
 * @since 2.2
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Fusion Layouts Builder class.
 *
 * @since 2.2
 */
class Fusion_Template_Builder {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 2.2
	 * @var object
	 */
	private static $instance;

	/**
	 * The layout overide.
	 *
	 * @access public
	 * @var mixed
	 */
	public $layout = null;

	/**
	 * The template overrides in template.
	 *
	 * @access public
	 * @var mixed
	 */
	public $overrides = [];

	/**
	 * Pause content override.
	 *
	 * @access private
	 * @since 2.2
	 * @var bool
	 */
	private $override_paused = false;

	/**
	 * The template types.
	 *
	 * @access public
	 * @var array
	 */
	public $types = [];

	/**
	 * The template meta.
	 *
	 * @access public
	 * @var array
	 */
	public $template_meta = [];

	/**
	 * The default layout data.
	 *
	 * @access public
	 * @var array
	 */
	public static $default_layout_data = [
		'conditions'     => [],
		'template_terms' => [],
	];

	/**
	 * Class constructor.
	 *
	 * @since 2.2
	 * @access private
	 */
	private function __construct() {
		if ( ! apply_filters( 'fusion_load_template_builder', true ) ) {
			return;
		}
		$this->setup_post_type();
		$this->set_global_overrides();

		add_action( 'fusion_builder_shortcodes_init', [ $this, 'init_shortcodes' ] );
		add_action( 'fusion_template_content', [ $this, 'render_content' ] );

		add_filter( 'template_include', [ $this, 'template_include' ], 12 );
		add_filter( 'fusion_is_hundred_percent_template', [ $this, 'is_hundred_percent_template' ], 25, 2 );

		// Requirements for live editor.
		add_action( 'fusion_builder_load_templates', [ $this, 'load_component_templates' ] );
		add_action( 'fusion_builder_enqueue_separate_live_scripts', [ $this, 'load_component_views' ] );

		// Filter in some template options along with post.
		add_filter( 'fusion_pagetype_data', [ $this, 'template_tabs' ], 10, 2 );

		// Special sidebar overrides.
		add_filter( 'avada_setting_get_posts_global_sidebar', [ $this, 'filter_posts_global_sidebar' ] );
		add_filter( 'avada_setting_get_portfolio_global_sidebar', [ $this, 'filter_portfolio_global_sidebar' ] );
		add_filter( 'avada_setting_get_search_sidebar', [ $this, 'filter_search_sidebar_1' ] );
		add_filter( 'avada_setting_get_search_sidebar_2', [ $this, 'filter_search_sidebar_2' ] );
		add_filter( 'avada_sidebar_post_meta_option_names', [ $this, 'load_template_sidebars' ], 10, 2 );

		// New layout hook.
		add_action( 'admin_action_fusion_tb_new_layout', [ $this, 'add_new_layout' ] );

		// New template hook.
		add_action( 'admin_action_fusion_tb_new_post', [ $this, 'add_new_template' ] );

		// Override template post type and ID with target example.
		add_filter( 'fusion_dynamic_post_data', [ $this, 'dynamic_data' ] );
		add_filter( 'fusion_dynamic_post_id', [ $this, 'dynamic_id' ] );
		add_filter( 'fusion_breadcrumb_post_id', [ $this, 'dynamic_id' ] );

		// If saving a 404 or search, update post meta conditions of others of same type.
		add_action( 'fusion_save_post', [ $this, 'panel_save' ] );

		// Reset caches when a template or layout gets deleted, undeleted etc.
		add_action( 'clean_post_cache', [ $this, 'clean_post_cache' ], 10, 2 );

		// Filters to pause.
		add_action( 'fusion_pause_template_builder_override', [ $this, 'pause_content_filter' ], 999 );
		add_action( 'fusion_resume_template_builder_override', [ $this, 'resume_content_filter' ], 999 );

		// Add FusionApp data.
		add_filter( 'fusion_app_preview_data', [ $this, 'add_builder_data' ], 10 );

		// Front end page edit trigger.
		add_action( 'admin_bar_menu', [ $this, 'builder_trigger' ], 999 );

		// Render footer override if it exists.
		add_action( 'get_footer', [ $this, 'maybe_render_footer' ] );
		add_filter( 'avada_setting_get_footer_special_effects', [ $this, 'filter_special_effects' ] );
		add_filter( 'generate_css_get_footer_special_effects', [ $this, 'filter_special_effects' ] );

		// Render Page Title Bar override if it exists.
		add_action( 'wp_head', [ $this, 'maybe_render_page_title_bar' ] );

		// Add custom CSS.
		// This has a priority of 1000 because we need it to be
		// just before the `fusion_builder_custom_css` hook - which runs on 1001.
		add_action( 'wp_head', [ $this, 'render_custom_css' ], 1000 );

		// Admin head hook. Add styles & scripts if needed.
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 2.2
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new Fusion_Template_Builder();
		}
		return self::$instance;
	}

	/**
	 * Setup the post type and taxonomies.
	 *
	 * @since 2.2
	 * @access public
	 */
	public function setup_post_type() {

		// Layout post type, where you select templates.
		$labels = [
			'name'                     => _x( 'Fusion Builder Layouts', 'Layout general name', 'fusion-builder' ),
			'singular_name'            => _x( 'Layout', 'Layout singular name', 'fusion-builder' ),
			'add_new'                  => _x( 'Add New', 'Layout item', 'fusion-builder' ),
			'add_new_item'             => esc_html__( 'Add New Layout', 'fusion-builder' ),
			'edit_item'                => esc_html__( 'Edit Layout', 'fusion-builder' ),
			'new_item'                 => esc_html__( 'New Layout', 'fusion-builder' ),
			'all_items'                => esc_html__( 'All Layouts', 'fusion-builder' ),
			'view_item'                => esc_html__( 'View Layouts', 'fusion-builder' ),
			'search_items'             => esc_html__( 'Search Layouts', 'fusion-builder' ),
			'not_found'                => esc_html__( 'Nothing found', 'fusion-builder' ),
			'not_found_in_trash'       => esc_html__( 'Nothing found in Trash', 'fusion-builder' ),
			'item_published'           => esc_html__( 'Layout published.', 'fusion-builder' ),
			'item_published_privately' => esc_html__( 'Layout published privately.', 'fusion-builder' ),
			'item_reverted_to_draft'   => esc_html__( 'Layout reverted to draft.', 'fusion-builder' ),
			'item_scheduled'           => esc_html__( 'Layout scheduled.', 'fusion-builder' ),
			'item_updated'             => esc_html__( 'Layout updated.', 'fusion-builder' ),
			'parent_item_colon'        => '',
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => true,
			'can_export'          => true,
			'query_var'           => true,
			'has_archive'         => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'supports'            => [ 'title', 'editor', 'revisions' ],
		];

		register_post_type( 'fusion_tb_layout', apply_filters( 'fusion_tb_layout_args', $args ) );

		// Individual Templates.
		$labels = [
			'name'                     => _x( 'Fusion Builder Sections', 'Section type general name', 'fusion-builder' ),
			'singular_name'            => _x( 'Section', 'Section type singular name', 'fusion-builder' ),
			'add_new'                  => _x( 'Add New', 'Section item', 'fusion-builder' ),
			'add_new_item'             => esc_html__( 'Add New Section', 'fusion-builder' ),
			'edit_item'                => esc_html__( 'Edit Section', 'fusion-builder' ),
			'new_item'                 => esc_html__( 'New Section', 'fusion-builder' ),
			'all_items'                => esc_html__( 'All Sections', 'fusion-builder' ),
			'view_item'                => esc_html__( 'View Sections', 'fusion-builder' ),
			'search_items'             => esc_html__( 'Search Sections', 'fusion-builder' ),
			'not_found'                => esc_html__( 'Nothing found', 'fusion-builder' ),
			'not_found_in_trash'       => esc_html__( 'Nothing found in Trash', 'fusion-builder' ),
			'item_published'           => esc_html__( 'Layout published.', 'fusion-builder' ),
			'item_published_privately' => esc_html__( 'Layout published privately.', 'fusion-builder' ),
			'item_reverted_to_draft'   => esc_html__( 'Layout reverted to draft.', 'fusion-builder' ),
			'item_scheduled'           => esc_html__( 'Layout scheduled.', 'fusion-builder' ),
			'item_updated'             => esc_html__( 'Layout updated.', 'fusion-builder' ),
			'parent_item_colon'        => '',
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => true,
			'can_export'          => true,
			'query_var'           => true,
			'has_archive'         => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'supports'            => [ 'title', 'editor', 'revisions' ],
		];

		register_post_type( 'fusion_tb_section', apply_filters( 'fusion_tb_section_args', $args ) );

		// Different template categories.
		$labels = [
			'name' => esc_attr__( 'Section Category', 'fusion-builder' ),
		];

		register_taxonomy(
			'fusion_tb_category',
			[ 'fusion_tb_section' ],
			[
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => false,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_nav_menus' => false,
			]
		);

		$this->set_template_terms();
	}

	/**
	 * Set the template terms that builder supports.
	 *
	 * @since 2.2
	 * @access public
	 * @return void
	 */
	public function set_template_terms() {
		$this->types = apply_filters(
			'fusion_tb_types',
			[
				'page_title_bar' => [
					'label' => esc_html__( 'Page Title Bar', 'fusion-builder' ),
					'icon'  => 'fusiona-page_title',
				],
				'content'        => [
					'label' => esc_html__( 'Content', 'fusion-builder' ),
					'icon'  => 'fusiona-content',
				],
				'footer'         => [
					'label' => esc_html__( 'Footer', 'fusion-builder' ),
					'icon'  => 'fusiona-footer',
				],
			]
		);
	}

	/**
	 * Get the template terms that builder supports.
	 *
	 * @since 2.2
	 * @access public
	 * @return array
	 */
	public function get_template_terms() {
		return $this->types;
	}

	/**
	 * Get the templates by term.
	 *
	 * @since 2.2
	 * @access public
	 * @return array
	 */
	public function get_templates_by_term() {
		$templates = [];
		$args      = [
			'post_type' => 'fusion_tb_section',
			'nopaging'  => true,
		];
		foreach ( $this->get_template_terms() as $term => $value ) {
			$args['tax_query']  = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'fusion_tb_category',
					'field'    => 'name',
					'terms'    => $term,
				],
			];
			$templates[ $term ] = get_posts( $args );
		}
		return $templates;
	}

	/**
	 * Handles the update of a layout content.
	 *
	 * @access public
	 * @param string $id The layout ID.
	 * @param string $value The new post content.
	 * @return string
	 * @since 2.2
	 */
	public static function update_layout_content( $id, $value ) {
		// TODO make a function that sanitizes value
		// i.e. remove all keys that aren't terms and conditions
		// i.e Check that terms are valids
		// i.e Check that conditions only have valid keys and sanitize those.

		// Check if it's global template.
		// Else update post_content.
		if ( 0 === $id || '0' === $id || 'global' === $id ) {
			$updated_layout = self::update_default_layout( $value );
		} else {
			$post = get_post( $id );

			$updated_layout = wp_parse_args(
				$value,
				self::$default_layout_data
			);

			$post->{'post_content'} = wp_slash( wp_json_encode( $updated_layout ) );
			wp_update_post( $post );
		}

		// Reset caches.
		fusion_reset_all_caches();
		return $updated_layout;
	}

	/**
	 * Handles the update of a layout title.
	 *
	 * @access public
	 * @param string $id The layout ID.
	 * @param string $value The value of new title.
	 * @return void
	 * @since 2.2
	 */
	public static function update_layout_title( $id, $value ) {

		$post = get_post( $id );

		$post->{'post_title'} = esc_html( sanitize_text_field( $value ) );
		wp_update_post( $post );

		// Reset caches.
		fusion_reset_all_caches();
	}

	/**
	 * Returns default layout
	 *
	 * @return array
	 * @since 2.2
	 */
	public static function get_default_layout() {
		$data = wp_parse_args( json_decode( wp_unslash( get_option( 'fusion_tb_layout_default' ) ), true ), self::$default_layout_data );

		// Cleanup: Remove empty items.
		if ( isset( $data['template_terms'] ) ) {
			foreach ( $data['template_terms'] as $key => $val ) {
				if ( ! $val || 'publish' !== get_post_status( absint( $val ) ) ) {
					unset( $data['template_terms'][ $key ] );
				}
			}
		}

		return [
			'id'    => 'global',
			'title' => esc_html__( 'Global Layout', 'fusion-builder' ),
			'data'  => $data,
		];
	}

	/**
	 * Returns registered layouts
	 *
	 * @return array
	 * @since 2.2
	 */
	public static function get_registered_layouts() {
		$args               = [
			'post_type'      => [ 'fusion_tb_layout' ],
			'post_status'    => [ 'any' ],
			'posts_per_page' => -1,
		];
		$layouts            = fusion_cached_query( $args );
		$registered_layouts = [];
		// Add default layout.
		$registered_layouts[0] = self::get_default_layout();

		if ( $layouts->have_posts() ) {
			foreach ( $layouts->posts as $layout ) {
				$data                              = json_decode( wp_unslash( $layout->post_content ), true );
				$registered_layouts[ $layout->ID ] = [
					'id'    => $layout->ID,
					'title' => $layout->post_title,
					'data'  => wp_parse_args( $data, self::$default_layout_data ),
				];
			}
		}
		return $registered_layouts;
	}

	/**
	 * Handles the update of the default layout content.
	 *
	 * @access public
	 * @param string $value The value to update.
	 * @return string
	 * @since 2.2
	 */
	public static function update_default_layout( $value ) {
		$updated_content = wp_parse_args(
			$value,
			[
				'conditions'     => [],
				'template_terms' => [],
			]
		);
		update_option( 'fusion_tb_layout_default', wp_slash( wp_json_encode( $updated_content ) ) );
		return $updated_content;
	}

	/**
	 * Check if search should have a template override.
	 *
	 * @since 2.2
	 * @access public
	 * @param WP_Query $query an instance of the WP_Query object.
	 * @return object
	 */
	public function get_search_override( $query ) {
		global $wp_query;

		if ( ! is_admin() && $query->is_main_query() && ( $query->is_search() || $query->is_archive() ) ) {
			if ( null === $this->layout ) {
				$this->override_paused = true;
				$args                  = [
					'post_type'      => [ 'fusion_tb_layout' ],
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				];
				$layouts               = fusion_cached_query( $args );
				$layouts               = $layouts->posts;
				/**
				 * Check if whatever is being loaded should have a template override.
				 *
				 * @since 2.2
				 * @access public
				 * @param string $type Type of override you are checking for.
				 * @return object
				 */
				if ( is_array( $layouts ) ) {
					$wp_query->is_search = $query->is_search();
					foreach ( $layouts as $layout ) {
						if ( $this->check_full_conditions( $layout, null ) ) {
							$layout->permalink = get_permalink( $layout->ID );
							$this->layout      = $layout;
						}
					}
				}

				// We're on purpose using wp_reset_query() instead of wp_reset_postdata() here
				// because we've altered the main query above.
				wp_reset_query(); // phpcs:ignore WordPress.WP.DiscouragedFunctions

				// Add global layout if no custom layout was detected.
				if ( ! $this->layout ) {
					$default_layout = self::get_default_layout();

					// Check if our global layout has overrides before adding anything.
					if ( ! empty( $default_layout['data']['template_terms'] ) ) {
						$this->layout               = new stdClass();
						$this->layout->ID           = 'global';
						$this->layout->post_content = wp_json_encode( $default_layout['data'] );
					}
				}

				/**
				 * Filter the layout override.
				 *
				 * @since 2.2.0
				 * @param Post|false $this->layout The layout override.
				 * @param int|string $c_page_id    The page-ID as returned from fusion_library()->get_page_id().
				 * @return Post|false
				 */
				$this->layout = apply_filters( 'fusion_tb_override', $this->layout, false );

				$this->set_overrides();

				$this->override_paused = false;
			}

			if ( ! $this->layout ) {
				$this->layout    = null;
				$this->overrides = apply_filters( 'fusion_set_overrides', [] );
			}

			$override = isset( $this->overrides['content'] ) ? $this->overrides['content'] : false;

			/**
			 * Filter overrides.
			 *
			 * @since 2.2.0
			 * @param Post|false $override  The override.
			 * @param string     $type      The type of override we're querying.
			 * @param int|string $c_page_id The page-ID as returned from fusion_library()->get_page_id().
			 * @return Post|false
			 */
			return apply_filters( 'fusion_get_override', $override, 'content', false );
		}
		return false;
	}

	/**
	 * Check if whatever is being loaded should have a template override.
	 *
	 * @since 2.2
	 * @access public
	 * @param string $type Type of override you are checking for.
	 * @return object
	 */
	public function get_override( $type = 'content' ) {
		global $post, $wp_query, $pagenow;

		$backend_pages = [ 'post.php', 'term.php' ];
		// Early exit if called too early.
		if ( ( ! is_admin() && ! did_action( 'wp' ) ) || doing_filter( 'fusion_set_overrides' ) || fusion_is_builder_frame() || ( ! isset( $post ) && $pagenow !== $backend_pages[1] && ! is_archive() && ! is_404() && ! is_search() ) || $this->override_paused ) {
			return false;
		}

		$target_post = $post;
		$c_page_id   = fusion_library()->get_page_id();

		// If $this->layout is null it has not been calculated yet.
		if ( null === $this->layout ) {
			$this->override_paused = true;

			$args    = [
				'post_type'      => [ 'fusion_tb_layout' ],
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			];
			$layouts = fusion_cached_query( $args );
			$layouts = $layouts->posts;

			if ( fusion_is_preview_frame() || ( is_admin() && in_array( $pagenow, $backend_pages ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
				if ( 'fusion_tb_section' === get_post_type() ) {
					add_filter( 'fusion_app_preview_data', [ $this, 'add_post_data' ], 10, 3 );
					$target_post = $this->get_target_example( $post->ID );
					$option      = fusion_get_page_option( 'dynamic_content_preview_type', $post->ID );
				}

				// Check if front page.
				if ( isset( $target_post ) && 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) === $target_post->ID ) {
					$target_post->is_front_page = true;
				}
				// Check if singular.
				if ( isset( $target_post ) && $target_post->post_type ) {
					$target_post->is_singular = true;
				}

				$query_altered = false;
				if ( isset( $option ) && 'search' === $option ) {
					$wp_query->is_search = true;
					$query_altered       = true;
				} elseif ( isset( $option ) && '404' === $option ) {
					$wp_query->is_404 = true;
					$query_altered    = true;
				} elseif ( isset( $option ) && 'archives' === $option ) {
					$wp_query->is_archive = true;
					$query_altered        = true;
				}

				if ( $query_altered ) {
					// We're on purpose using wp_reset_query() instead of wp_reset_postdata() here
					// because we've altered the main query above.
					wp_reset_query(); // phpcs:ignore WordPress.WP.DiscouragedFunctions				
				}
			}
			if ( is_array( $layouts ) ) {
				foreach ( $layouts as $layout ) {
					if ( $this->check_full_conditions( $layout, $target_post ) ) {
						$layout->permalink = get_permalink( $layout->ID );
						$this->layout      = $layout;
					}
				}
			}

			// Add global layout if no custom layout was detected.
			if ( ! $this->layout ) {
				$default_layout = self::get_default_layout();

				// Check if our global layout has overrides before adding anything.
				if ( ! empty( $default_layout['data']['template_terms'] ) ) {
					$this->layout               = new stdClass();
					$this->layout->ID           = 'global';
					$this->layout->post_content = wp_json_encode( $default_layout['data'] );
				}
			}

			/**
			 * Filter the layout override.
			 *
			 * @since 2.2.0
			 * @param Post|false $this->layout The layout override.
			 * @param int|string $c_page_id    The page-ID as returned from fusion_library()->get_page_id().
			 * @return Post|false
			 */
			$this->layout = apply_filters( 'fusion_tb_override', $this->layout, $c_page_id );

			$this->set_overrides();

			$this->override_paused = false;
		}

		if ( ! $this->layout ) {
			$this->layout    = false;
			$this->overrides = apply_filters( 'fusion_set_overrides', [] );
		}

		$override = $this->layout;
		if ( 'layout' !== $type ) {
			$override = isset( $this->overrides[ $type ] ) ? $this->overrides[ $type ] : false;
		}

		/**
		 * Filter overrides.
		 *
		 * @since 2.2.0
		 * @param Post|false $override  The override.
		 * @param string     $type      The type of override we're querying.
		 * @param int|string $c_page_id The page-ID as returned from fusion_library()->get_page_id().
		 * @return Post|false
		 */
		return apply_filters( 'fusion_get_override', $override, $type, $c_page_id );
	}

	/**
	 * Sets individual template overrides based on layout override
	 *
	 * @since 2.2.2
	 * @return void
	 */
	public function set_overrides() {
		if ( $this->layout && 'global' !== $this->layout->ID ) {
			$data  = json_decode( wp_unslash( $this->layout->post_content ), true );
			$types = isset( $data['template_terms'] ) ? $data['template_terms'] : false;
			if ( is_array( $types ) ) {
				foreach ( $types as $type_name => $template_id ) {

					// If template found and is not what we are viewing/editing.
					if ( $template_id && '' !== $template_id && (string) fusion_library()->get_page_id() !== (string) $template_id ) {

						$template_post = get_post( $template_id );

						// If the template doesn't exist (for example it has been deleted), unset it.
						if ( ! $template_post || 'publish' !== $template_post->post_status ) {
							continue;
						}

						$this->overrides[ $type_name ]            = $template_post;
						$this->overrides[ $type_name ]->permalink = get_permalink( $template_id );
					}
				}
			}
		}

		$this->overrides = apply_filters( 'fusion_set_overrides', $this->overrides );

		// If not on single, but we have content override, ensure PO is read like it was a page.
		if ( ! is_singular() && isset( $this->overrides['content'] ) ) {
			add_filter( 'fusion_should_get_page_option', [ $this, 'should_get_option' ], 10 );
		}
	}

	/**
	 * Sets overrides for each global layout section.
	 *
	 * @since 2.2.2
	 * @return void
	 */
	public function set_global_overrides() {
		$globals = self::get_default_layout();
		if ( isset( $globals['data'] ) && isset( $globals['data']['template_terms'] ) ) {
			foreach ( $globals['data']['template_terms'] as $type_name => $template_id ) {
				$template_post = get_post( $template_id );

				// If the template doesn't exist (for example it has been deleted), unset it.
				if ( ! $template_post || 'publish' !== $template_post->post_status ) {
					continue;
				}

				$this->overrides[ $type_name ]            = $template_post;
				$this->overrides[ $type_name ]->permalink = get_permalink( $template_id );
			}
		}
	}

	/**
	 * Make sure to ignore TO global option.
	 *
	 * @since 2.2.2
	 * @param string $value The global option for post sidebars.
	 * @return string
	 */
	public function filter_posts_global_sidebar( $value ) {
		$override = $this->get_override( 'content' );
		if ( ( is_singular( 'post' ) && $override ) || is_singular( 'fusion_tb_section' ) ) {
			return 0;
		}
		return $value;
	}

	/**
	 * Use template option if set rather than global on search page.
	 *
	 * @since 2.2.2
	 * @param string $value The global option for search sidebar 1.
	 * @return string
	 */
	public function filter_search_sidebar_1( $value ) {
		$override = $this->get_override( 'content' );
		if ( $override ) {
			return fusion_get_page_option( 'template_sidebar', $override->ID );
		}
		return $value;
	}

	/**
	 * Use template option if set rather than global for sidebar 2 on search page.
	 *
	 * @since 2.2.2
	 * @param string $value The global option for search sidebar 2.
	 * @return string
	 */
	public function filter_search_sidebar_2( $value ) {
		$override = $this->get_override( 'content' );
		if ( $override ) {
			return fusion_get_page_option( 'template_sidebar_2', $override->ID );
		}
		return $value;
	}

	/**
	 * Make sure to ignore TO global option.
	 *
	 * @since 2.2.2
	 * @param string $value The global option for portfolio sidebars.
	 * @return string
	 */
	public function filter_portfolio_global_sidebar( $value ) {
		$override = $this->get_override( 'content' );
		if ( is_singular( 'avada_portfolio' ) && $override ) {
			return 0;
		}
		return $value;
	}

	/**
	 * Add any special case classes we need.
	 *
	 * @since 2.2.2
	 * @param string $value The footer special effects value in TO.
	 * @return string
	 */
	public function filter_special_effects( $value ) {
		$footer_override = $this->get_override( 'footer' );

		if ( $footer_override ) {
			$value = fusion_get_page_option( 'special_effect', $footer_override->ID );
			if ( '' === $value ) {
				return 'none';
			}
		}
		return $value;
	}

	/**
	 * Check if we have a footer and if so render it.
	 *
	 * @since 2.2
	 * @return void
	 * @access public
	 */
	public function maybe_render_footer() {
		$footer_override = $this->get_override( 'footer' );

		if ( $footer_override ) {
			add_action(
				'avada_render_footer',
				function() use ( $footer_override ) {
					echo '<div class="fusion-tb-footer fusion-footer' . ( class_exists( 'Avada' ) && 'footer_parallax_effect' === Avada()->settings->get( 'footer_special_effects' ) ? ' fusion-footer-parallax' : '' ) . '">';
					echo '<div class="fusion-footer-widget-area fusion-widget-area">';
					$this->render_content( $footer_override );
					echo '</div></div>';
				}
			);
		}
	}

	/**
	 * Check if we have a page title bar and if so render it.
	 *
	 * @since 2.2
	 * @return void
	 * @access public
	 */
	public function maybe_render_page_title_bar() {
		$page_title_bar_override = $this->get_override( 'page_title_bar' );

		if ( $page_title_bar_override ) {
			add_action(
				'avada_override_current_page_title_bar',
				function() use ( $page_title_bar_override ) {
					echo '<div class="fusion-page-title-bar fusion-tb-page-title-bar">';
					$this->render_content( $page_title_bar_override );
					echo '</div>';
				}
			);
		}
	}

	/**
	 * Check if current post matched conditions of template.
	 *
	 * @static
	 * @since 2.2
	 * @param WP_Post $template Section post object.
	 * @return array  $return Whether it passed or not.
	 * @access public
	 */
	public static function get_conditions( $template ) {
		if ( $template ) {
			$data = json_decode( wp_unslash( $template->post_content ), true );
			if ( isset( $data['conditions'] ) ) {
				$conditions = [];
				// Group child conditions into same id.
				foreach ( $data['conditions'] as $id => $condition ) {
					if ( ! isset( $condition['parent'] ) ) {
						$conditions[ $id ] = $condition;
						continue;
					}
					// Create unique id for the parent condition to avoid collitions between same conditions with different modes.
					$parent_id = $condition['parent'] . '-' . $condition['mode'] . '-' . $condition['type'];
					if ( ! isset( $conditions[ $parent_id ] ) ) {
						$conditions[ $parent_id ] = [
							'mode'             => $condition['mode'],
							'type'             => $condition['type'],
							$condition['type'] => $condition['parent'],
						];
					}
					$conditions[ $parent_id ][ $condition['parent'] ][ $id ] = $condition;
				}
				// Sort exclude conditions first and remove unique id.
				usort(
					$conditions,
					function( $a, $b ) {
						return strcmp( $a['mode'], $b['mode'] );
					}
				);

				return $conditions;
			}
		}
		return false;
	}

	/**
	 * Check if current post matched conditions of template.
	 *
	 * @since 2.2
	 * @param WP_Post $template    Section post object.
	 * @param WP_Post $target_post The target post object.
	 * @return bool Whether it passed or not.
	 * @access public
	 */
	public function check_full_conditions( $template, $target_post ) {
		global $pagenow;

		$conditions    = self::get_conditions( $template );
		$backend_pages = [ 'post.php', 'term.php' ];

		if ( is_array( $conditions ) ) {
			foreach ( $conditions as $condition ) {
				if ( isset( $condition['type'] ) && '' !== $condition['type'] && isset( $condition[ $condition['type'] ] ) ) {
					$type    = $condition['type'];
					$exclude = 'exclude' === $condition['mode'];

					if ( fusion_is_preview_frame() || ( is_admin() && in_array( $pagenow, $backend_pages ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
						$pass = 'archives' === $type ? $this->builder_check_archive_condition( $condition ) : $this->builder_check_singular_condition( $condition, $target_post );
					} else {
						$pass = 'archives' === $type ? $this->check_archive_condition( $condition ) : $this->check_singular_condition( $condition );
					}

					// If it doesn't pass all exclude conditions check is false.
					// If all exclude conditions are valid and we find one valid condition check is true.
					if ( $exclude && ! $pass ) {
						return false;
					} elseif ( ! $exclude && $pass ) {
						return true;
					}
				}
			}
		}
		// The default behaviour.
		return false;
	}

	/**
	 * Check if archive condition is true.
	 *
	 * @since 2.2
	 * @param array $condition Condition array to check.
	 * @return bool  $return Whether it passed or not.
	 * @access public
	 */
	public function builder_check_archive_condition( $condition ) {
		global $pagenow;

		$archive_type   = isset( $condition['archives'] ) ? $condition['archives'] : '';
		$exclude        = isset( $condition['mode'] ) && 'exclude' === $condition['mode'];
		$condition_type = isset( $condition['type'] ) ? $condition['type'] : '';
		$sub_condition  = isset( $condition[ $archive_type ] ) ? $condition[ $archive_type ] : '';

		if ( '' === $sub_condition ) {
			if ( 'all_archives' === $archive_type ) {
				if ( is_admin() ) {
					return $exclude ? 'term.php' !== $pagenow : 'term.php' === $pagenow;
				}
				return $exclude ? ! is_archive() : is_archive();
			}

			if ( 'author_archive' === $archive_type ) {
				if ( is_admin() ) {
					return $exclude ? 'profile.php' !== $pagenow : 'profile.php' === $pagenow;
				}
				return $exclude ? ! is_author() : is_author();
			}
			// Check if it's a archive page.
			if ( 'term.php' === $pagenow ) {
				if ( is_admin() ) {
					return $exclude ? $archive_type !== $_GET['taxonomy'] : $archive_type === $_GET['taxonomy']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
				}
				return $exclude ? ! get_queried_object()->taxonomy === $archive_type : get_queried_object()->taxonomy === $archive_type;
			}

			// Only check live editor, cannot edit search or taxonomy archive on back-end.
			if ( ! is_admin() ) {
				if ( 'search_results' === $archive_type ) {
					return $exclude ? ! is_search() : is_search();
				}

				if ( 'archives' === $condition_type && taxonomy_exists( $archive_type ) ) {
					return $exclude ? ! ( get_queried_object()->taxonomy === $archive_type ) : get_queried_object()->taxonomy === $archive_type;
				}
			}

			return false;
		}

		// Check for specific author pages.
		if ( false !== strpos( $archive_type, 'author_archive_' ) ) {
			$author_ids = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$author_ids[] = explode( '|', $id )[1];
			}
			$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );

			if ( ! $curauth ) {
				return $exclude ? true : false;
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( $curauth->ID, $author_ids ) : in_array( $curauth->ID, $author_ids ); // phpcs:ignore WordPress.PHP.StrictInArray
		}
		// Check for especific terms.
		if ( false !== strpos( $archive_type, 'taxonomy_of_' ) && ! is_archive() ) {
			$taxonomy = str_replace( 'taxonomy_of_', '', $archive_type );
			$terms    = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$terms[] = explode( '|', $id )[1];
			}
			switch ( $taxonomy ) {
				case 'category':
					return $exclude ? ! in_category( $terms ) : in_category( $terms );
				case 'post_tag':
					return $exclude ? ! has_tag( $terms ) : has_tag( $terms );
				default:
					return $exclude ? ! has_term( $terms, $taxonomy ) : has_term( $terms, $taxonomy );
			}
		}

		// Check for specific author pages.
		if ( false !== strpos( $archive_type, 'author_archive_' ) ) {
			$author_ids = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$author_ids[] = explode( '|', $id )[1];
			}
			$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );

			if ( ! $curauth ) {
				return $exclude ? true : false;
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( $curauth->ID, $author_ids ) : in_array( $curauth->ID, $author_ids ); // phpcs:ignore WordPress.PHP.StrictInArray
		}

		// Check for general archive pages.
		if ( is_archive() || 'term.php' === $pagenow ) {
			$terms = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$terms[] = explode( '|', $id )[1];
			}
			if ( is_admin() ) {
				return $exclude ? ! in_array( $_GET['tag_ID'], $terms ) : in_array( $_GET['tag_ID'], $terms ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification, WordPress.PHP.StrictInArray
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( get_queried_object()->term_id, $terms ) : in_array( get_queried_object()->term_id, $terms ); // phpcs:ignore WordPress.PHP.StrictInArray
		}
	}

	/**
	 * Check if archive condition is true.
	 *
	 * @since 2.2
	 * @param array $condition Condition array to check.
	 * @return bool  $return Whether it passed or not.
	 * @access public
	 */
	public function check_archive_condition( $condition ) {
		$archive_type   = isset( $condition['archives'] ) ? $condition['archives'] : '';
		$exclude        = isset( $condition['mode'] ) && 'exclude' === $condition['mode'];
		$condition_type = isset( $condition['type'] ) ? $condition['type'] : '';
		$sub_condition  = isset( $condition[ $archive_type ] ) ? $condition[ $archive_type ] : '';

		if ( '' === $sub_condition ) {
			if ( 'all_archives' === $archive_type ) {
				return $exclude ? ! is_archive() : is_archive();
			}

			if ( 'author_archive' === $archive_type ) {
				return $exclude ? ! is_author() : is_author();
			}

			if ( 'date_archive' === $archive_type ) {
				return $exclude ? ! is_date() : is_date();
			}

			if ( 'search_results' === $archive_type ) {
				return $exclude ? ! is_search() : is_search();
			}

			if ( 'archives' === $condition_type && taxonomy_exists( $archive_type ) ) {
				if ( 'category' === $archive_type ) {
					return $exclude ? ! is_category() : is_category();
				}
				if ( 'post_tag' === $archive_type ) {
					return $exclude ? ! is_tag() : is_tag();
				}

				return $exclude ? ! is_tax( $archive_type ) : is_tax( $archive_type );
			}

			// Check for general archive pages.
			if ( false !== strpos( $archive_type, 'archive_of_' ) && is_archive() && null !== get_queried_object() ) {
				$taxonomy = str_replace( 'archive_of_', '', $archive_type );
				return $exclude ? ! is_post_type_archive( $taxonomy ) : is_post_type_archive( $taxonomy );
			}

			return false;
		}

		// Check for specific author pages.
		if ( false !== strpos( $archive_type, 'author_archive_' ) ) {
			$author_ids = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$author_ids[] = explode( '|', $id )[1];
			}
			$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );

			if ( ! $curauth ) {
				return $exclude ? true : false;
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( $curauth->ID, $author_ids ) : in_array( $curauth->ID, $author_ids ); // phpcs:ignore WordPress.PHP.StrictInArray
		}

		// Check for general archive pages.
		if ( false === strpos( $archive_type, 'taxonomy_of_' ) && is_archive() && null !== get_queried_object() ) {
			$terms = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$terms[] = explode( '|', $id )[1];
			}

			if ( ! isset( get_queried_object()->term_id ) ) {
				return false;
			}

			// Intentionally not strict comparison.
			return $exclude ? ! in_array( get_queried_object()->term_id, $terms ) : in_array( get_queried_object()->term_id, $terms ); // phpcs:ignore WordPress.PHP.StrictInArray
		}

		// Check if we're checking for especific terms.
		if ( false !== strpos( $archive_type, 'taxonomy_of_' ) && ! is_archive() ) {
			$taxonomy = str_replace( 'taxonomy_of_', '', $archive_type );
			$terms    = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$terms[] = explode( '|', $id )[1];
			}
			switch ( $taxonomy ) {
				case 'category':
					return $exclude ? ! in_category( $terms ) : in_category( $terms );
				case 'post_tag':
					return $exclude ? ! has_tag( $terms ) : has_tag( $terms );
				default:
					return $exclude ? ! has_term( $terms, $taxonomy ) : has_term( $terms, $taxonomy );
			}
		}
	}

	/**
	 * Check if singular condition is true.
	 *
	 * @since 2.2
	 * @param array $condition Condition array to check.
	 * @return bool  $return Whether it passed or not.
	 * @access public
	 */
	public function check_singular_condition( $condition ) {
		global $post;

		$singular_type = isset( $condition['singular'] ) ? $condition['singular'] : '';
		$exclude       = isset( $condition['mode'] ) && 'exclude' === $condition['mode'];
		$sub_condition = isset( $condition[ $singular_type ] ) ? $condition[ $singular_type ] : '';
		$post_type     = str_replace( 'singular_', '', $singular_type );

		if ( '' === $sub_condition ) {
			if ( 'front_page' === $singular_type ) {
				return $exclude ? ! is_front_page() : is_front_page();
			}
			if ( 'not_found' === $singular_type ) {
				return $exclude ? ! is_404() : is_404();
			}
			$is_single = is_singular( $post_type ) || ( get_post_type() === $post_type && is_admin() && isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			return $exclude ? ! $is_single : $is_single;
		}
		// Specific post check.
		if ( false !== strpos( $singular_type, 'specific_' ) ) {
			$specific_posts = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$specific_posts[] = explode( '|', $id )[1];
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( get_the_id(), $specific_posts, false ) : in_array( get_the_id(), $specific_posts, false ); // phpcs:ignore WordPress.PHP.StrictInArray
		}
		// Hierarchy check.
		if ( false !== strpos( $singular_type, 'children_of' ) ) {
			$ancestors   = get_post_ancestors( $post );
			$is_children = false;
			foreach ( array_keys( $sub_condition ) as $id ) {
				$parent = explode( '|', $id )[1];
				if ( in_array( $parent, $ancestors ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
					$is_children = true;
					break;
				}
			}
			return $exclude ? ! $is_children : $is_children;
		}
	}

	/**
	 * Check if singular condition is true.
	 *
	 * @since 2.2
	 * @param array   $condition Condition array to check.
	 * @param WP_Post $target_post The target post object.
	 * @return bool Whether it passed or not.
	 * @access public
	 */
	public function builder_check_singular_condition( $condition, $target_post ) {
		global $post;

		$singular_type = isset( $condition['singular'] ) ? $condition['singular'] : '';
		$exclude       = isset( $condition['mode'] ) && 'exclude' === $condition['mode'];
		$sub_condition = isset( $condition[ $singular_type ] ) ? $condition[ $singular_type ] : '';
		$post_type     = str_replace( 'singular_', '', $singular_type );

		// Check for specific post type of page.
		if ( '' === $sub_condition ) {
			if ( 'front_page' === $singular_type ) {
				if ( ! $target_post ) {
					return $exclude ? true : false;
				}

				if ( fusion_is_preview_frame() ) {
					return $exclude ? ! is_front_page() : is_front_page();
				} else {
					return $exclude ? ! $target_post->is_front_page : $target_post->is_front_page;
				}
			}
			if ( 'not_found' === $singular_type ) {
				return $exclude ? ! is_404() : is_404();
			}
			// Is post type.
			if ( ! $target_post ) {
				return $exclude ? true : false;
			}
			$is_single = $post_type === $target_post->post_type ? true : false; // phpcs:ignore WordPress.Security.NonceVerification
			return $exclude ? ! $is_single : $is_single;
		}
		// Check if page matches condition id.
		if ( $sub_condition && false !== strpos( $singular_type, 'specific_' ) ) {
			$specific_posts = [];
			foreach ( array_keys( $sub_condition ) as $id ) {
				$specific_posts[] = explode( '|', $id )[1];
			}
			if ( ! $target_post ) {
				return $exclude ? true : false;
			}
			// Intentionally not strict comparison.
			return $exclude ? ! in_array( $target_post->ID, $specific_posts, false ) : in_array( $target_post->ID, $specific_posts, false ); // phpcs:ignore WordPress.PHP.StrictInArray
		}
		// Hierarchy check.
		if ( false !== strpos( $singular_type, 'children_of' ) ) {
			$ancestors   = get_post_ancestors( $target_post );
			$is_children = false;
			foreach ( array_keys( $sub_condition ) as $id ) {
				$parent = explode( '|', $id )[1];
				if ( in_array( $parent, $ancestors ) ) { // phpcs:ignore WordPress.PHP.StrictInArray
					$is_children = true;
					break;
				}
			}
			return $exclude ? ! $is_children : $is_children;
		}
	}

	/**
	 * Decide which template to include.
	 *
	 * @since 2.2
	 * @param string $template template path.
	 * @access public
	 */
	public function template_include( $template ) {
		if ( $this->override_paused ) {
			return $template;
		}

		if ( $this->get_override( 'content' ) || is_singular( 'fusion_tb_section' ) ) {
			$new_template = locate_template( [ 'template-page.php' ] );
			if ( ! empty( $new_template ) ) {
				return $new_template;
			} else {
				return FUSION_BUILDER_PLUGIN_DIR . 'templates/template-page.php';
			}
		}

		return $template;
	}

	/**
	 * Filter the wrapping content in.
	 *
	 * @since 2.2
	 * @param mixed $override Pass post object to to be used.
	 * @access public
	 */
	public function render_content( $override = false ) {
		global $post;

		$post_object = $override ? $override : $this->get_override( 'content' );

		if ( $post_object ) {

			// Override means target post load.  Means lets make actual post content non editable in live editor.
			do_action( 'fusion_pause_live_editor_filter' );

			$content = apply_filters( 'the_content', $post_object->post_content );
			$content = str_replace( ']]>', ']]&gt;', $content );

			do_action( 'fusion_resume_live_editor_filter' );
		} else {

			// No override means editing template in live editor, in which case we do not pause filter.
			$content = apply_filters( 'the_content', $post->post_content );
			$content = str_replace( ']]>', ']]&gt;', $content );
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Init shortcode files specific to templates.
	 *
	 * @since 2.2
	 * @access public
	 */
	public function init_shortcodes() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/author.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/comments.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/content.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/pagination.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/related.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/featured-slider.php';
		require_once FUSION_BUILDER_PLUGIN_DIR . 'shortcodes/components/archives.php';
	}

	/**
	 * Make sure page is 100% width if using an override.
	 *
	 * @since 2.2
	 * @access public
	 * @param bool $fullwidth Whether it is fullwidth or not.
	 */
	public function is_hundred_percent_template( $fullwidth ) {
		$override = $this->get_override( 'content' );
		if ( $override || is_singular( 'fusion_tb_section' ) ) {
			$post_id = $override ? $override->ID : get_the_id();
			return ( 'no' !== fusion_get_page_option( 'fusion_tb_section_width_100', $post_id ) );
		}
		return $fullwidth;
	}

	/**
	 * If we are on front-end and have override, use template sidebar names.
	 *
	 * @since 2.2
	 * @access public
	 * @param array $options Full sidebar options array.
	 * @param int   $post_type Post type for post being viewed.
	 * @return array.
	 */
	public function load_template_sidebars( $options, $post_type ) {
		if ( ! is_admin() ) {
			$override = $this->get_override( 'content' );
			if ( is_singular( 'fusion_tb_section' ) || $override ) {
				return [ 'template_sidebar', 'template_sidebar_2', 'template_sidebar_position', false ];
			}
		}
		return $options;
	}

	/**
	 * Ensures that even search and 404 pages get the template option.
	 *
	 * @since 2.2
	 * @access public
	 * @param bool $return Whether to get page option or not.
	 * @return bool
	 */
	public function should_get_option( $return ) {
		return true;
	}

	/**
	 * Replaces ID for dynamic css retrieval.
	 *
	 * @since 2.2
	 * @access public
	 * @param int $post_id Post id for what we want.
	 * @return int.
	 */
	public function replace_post_id( $post_id ) {
		$override = $this->get_override( 'content' );
		return ( $override ) ? $override->ID : $post_id;
	}

	/**
	 * Load the templates for live editor.
	 *
	 * @since 2.2
	 * @access public
	 */
	public function load_component_templates() {
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-author.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-archives.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-comments.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-content.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-pagination.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-related.php';
		include FUSION_BUILDER_PLUGIN_DIR . '/front-end/templates/components/fusion-tb-featured-slider.php';
	}

	/**
	 * Load the views for the components.
	 *
	 * @since 2.2
	 * @access public
	 */
	public function load_component_views() {

		// TODO: needs added to compiled JS file.
		wp_enqueue_script( 'fusion_builder_tb_author', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-author.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_comments', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-comments.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_pagination', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-pagination.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_content', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-content.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_related', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-related.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_featured_images_slider', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-featured-slider.js', [], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_builder_tb_archives', FUSION_BUILDER_PLUGIN_URL . 'front-end/views/components/view-archives.js', [], FUSION_BUILDER_VERSION, true );
	}

	/**
	 * Get example target post if exists.
	 *
	 * @since 2.2
	 * @access public
	 * @param int    $page_id page id.
	 * @param string $type Post type.
	 * @return mixed
	 */
	public function get_target_example( $page_id = false, $type = false ) {
		$page_id = ! $page_id ? get_the_id() : $page_id;
		$post    = false;

		if ( ! $type ) {
			$terms = get_the_terms( $page_id, 'fusion_tb_category' );
			$type  = is_array( $terms ) ? $terms[0]->name : false;
		}

		$post = $this->get_dynamic_content_selection( $page_id );

		if ( ! $post ) {
			$post = Fusion_Dummy_Post::get_dummy_post();
		}
		return apply_filters( 'fusion_tb_target_example', $post, $page_id, $type );
	}

	/**
	 * Get the page option from the template if not set in post.
	 *
	 * @since 2.2
	 * @access public
	 * @param array  $data Full data array.
	 * @param string $page_id Id for post.
	 * @param string $post_type Post type for post being edited.
	 * @return mixed
	 */
	public function add_post_data( $data, $page_id, $post_type ) {

		// Section category is used to filter components.
		$terms                     = get_the_terms( $page_id, 'fusion_tb_category' );
		$type                      = is_array( $terms ) ? $terms[0]->name : false;
		$data['template_category'] = $type;

		$post = $this->get_target_example( $page_id, $type );
		if ( $post ) {
			$post_type_obj = get_post_type_object( $post->post_type );

			// We need to pause filtering to get real content.
			do_action( 'fusion_pause_live_editor_filter' );
			$content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			do_action( 'fusion_resume_live_editor_filter' );

			// Get flattened page values.
			$page_values = fusion_data()->post_meta( $post->ID )->get_all_meta();

			$data['examplePostDetails'] = [
				'post_id'        => $post->ID,
				'post_permalink' => get_permalink( $post ),
				'post_title'     => get_the_title( $post->ID ),
				'post_content'   => $content,
				'post_name'      => $post->post_name,
				'post_type'      => $post->post_type,
				'post_type_name' => is_object( $post_type_obj ) ? $post_type_obj->labels->singular_name : esc_html__( 'Page', 'fusion-builder' ),
				'post_status'    => get_post_status( $post->ID ),
				'post_password'  => $post->post_password,
				'post_date'      => $post->post_date,
				'post_meta'      => $page_values,
			];
		}

		return $data;
	}

	/**
	 * Add new template.
	 *
	 * @since 2.2
	 * @access public
	 * @return void
	 */
	public function add_new_template() {

		check_admin_referer( 'fusion_tb_new_post' );

		$post_type_object = get_post_type_object( 'fusion_tb_section' );
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return;
		}

		if ( ! isset( $_GET['fusion_tb_category'] ) || '' === $_GET['fusion_tb_category'] ) {

			// Redirect back to form page.
			wp_safe_redirect( esc_url( admin_url( 'admin.php?page=fusion-templates' ) ) );
			die();
		}

		$category = sanitize_text_field( wp_unslash( $_GET['fusion_tb_category'] ) );

		$template = [
			'post_title'  => isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : '',
			'post_status' => 'publish',
			'post_type'   => 'fusion_tb_section',
		];

		$template_id = wp_insert_post( $template );
		if ( is_wp_error( $template_id ) ) {
			$error_string = $template_id->get_error_message();
			wp_die( esc_html( $error_string ) );
		}

		$template_type = wp_set_object_terms( $template_id, $category, 'fusion_tb_category' );
		if ( is_wp_error( $template_type ) ) {
			$error_string = $template_type->get_error_message();
			wp_die( esc_html( $error_string ) );
		}

		// Just redirect to back-end editor.  In future tie it to default editor option.
		wp_safe_redirect( get_edit_post_link( $template_id, false ) );
		die();
	}

	/**
	 * Add new layout.
	 *
	 * @since 2.2
	 * @access public
	 * @return void
	 */
	public function add_new_layout() {

		check_admin_referer( 'fusion_tb_new_layout' );

		$post_type_object = get_post_type_object( 'fusion_tb_layout' );
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return;
		}

		$layout = [
			'post_title'  => isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : '',
			'post_status' => 'publish',
			'post_type'   => 'fusion_tb_layout',
		];

		$layout_id = wp_insert_post( $layout );
		if ( is_wp_error( $layout_id ) ) {
			$error_string = $layout_id->get_error_message();
			wp_die( esc_html( $error_string ) );
		}

		// Reset caches.
		fusion_reset_all_caches();

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_safe_redirect( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		}
		die();
	}

	/**
	 * Override target post data.
	 *
	 * @since 2.2
	 * @access public
	 * @param array $post_data Post data to target.
	 * @return array
	 */
	public function dynamic_data( $post_data ) {
		if ( 'fusion_tb_section' === $post_data['post_type'] ) {
			$post = $this->get_target_example();
			if ( $post ) {
				$post_data['id']        = $post->ID;
				$post_data['post_type'] = get_post_type( $post );
			} else {
				$post_data['archive'] = true;
			}
		}
		return $post_data;
	}

	/**
	 * Override target post data.
	 *
	 * @since 2.2
	 * @access public
	 * @param int $id Post ID to target.
	 * @return int
	 */
	public function dynamic_id( $id ) {
		if ( 'fusion_tb_section' === get_post_type( $id ) ) {
			$post = $this->get_target_example( $id );

			if ( $post ) {
				return $post->ID;
			}
		}

		return $id;
	}

	/**
	 * Checks and returns dynamic content selection data.
	 *
	 * @since 2.2
	 * @access public
	 * @param int $id   Post ID to get values from.
	 * @return array|string $post Post data.
	 */
	public function get_dynamic_content_selection( $id ) {
		$post = $option = $value = false;

		// Filter data.
		if ( class_exists( 'Fusion_App' ) ) {
			do_action( 'fusion_filter_data' );
		}

		$option = fusion_get_page_option( 'dynamic_content_preview_type', $id );
		$value  = fusion_get_page_option( 'preview_' . $option, $id );

		if ( ! empty( $option ) && ( ( ! empty( $value ) && '0' !== $value ) || ( is_array( $value ) && isset( $value[0] ) ) ) ) {
			$post = get_post( is_array( $value ) && isset( $value[0] ) ? $value[0] : $value );
		} elseif ( 'default' !== $option && '' !== $option ) {
			$args = [
				'numberposts' => 1,
				'post_type'   => $option,
			];

			$post = get_posts( $args );

			if ( is_array( $post ) && isset( $post[0] ) ) {
				return $post[0];
			}
		}

		return $post;
	}

	/**
	 * Checks and returns post type for archives component.
	 *
	 * @since 2.2
	 * @access public
	 * @param  array $defaults current params array.
	 * @return array $defaults Updated params array.
	 */
	public function archives_type( $defaults ) {

		// No DB changes, we can skip the nonce checks in this function.
		// phpcs:disable WordPress.Security.NonceVerification
		global $post;

		$type = $post_id = $option = false;

		if ( fusion_is_preview_frame() ) {
			$type    = fusion_get_page_option( 'dynamic_content_preview_type', $post->ID );
			$option  = fusion_get_page_option( 'preview_archives', $post->ID );
			$post_id = $post->ID;
		}

		if ( isset( $_POST['fusion_meta'] ) && isset( $_POST['post_id'] ) && false === $option ) {
			$meta    = fusion_string_to_array( wp_unslash( $_POST['fusion_meta'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$option  = isset( $meta['_fusion']['preview_archives'] ) ? $meta['_fusion']['preview_archives'] : false;
			$type    = isset( $meta['_fusion']['dynamic_content_preview_type'] ) && in_array( $meta['_fusion']['dynamic_content_preview_type'], [ 'search', 'archives' ], true ) ? $meta['_fusion']['dynamic_content_preview_type'] : false;
			$post_id = sanitize_text_field( wp_unslash( $_POST['post_id'] ) );
		}
		$defaults['post_type'] = 'search' !== $type && false !== $option ? $option : 'any';

		// phpcs:enable WordPress.Security.NonceVerification
		return $defaults;
	}

	/**
	 * Flag to pause override of content.
	 *
	 * @since 2.2
	 * @return void
	 */
	public function pause_content_filter() {
		$this->override_paused = true;
	}

	/**
	 * Flag to resume override of content.
	 *
	 * @since 2.2
	 * @return void
	 */
	public function resume_content_filter() {
		$this->override_paused = false;
	}

	/**
	 * Fetch templates of a type.
	 *
	 * @since 2.2
	 * @param string $type The template type.
	 * @return object
	 */
	public function get_templates( $type = 'content' ) {
		$args = [
			'post_type'      => [ 'fusion_tb_section' ],
			'posts_per_page' => -1,
			'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'fusion_tb_category',
					'field'    => 'name',
					'terms'    => $type,
				],
			],
		];
		return fusion_cached_query( $args )->posts;
	}

	/**
	 * Live editor saved, need to trigger save post hook.
	 *
	 * @access public
	 * @since 2.2
	 * @return void
	 */
	public function panel_save() {
		$app     = Fusion_App();
		$post_id = $app->get_data( 'post_id' );

		// Reset caches.
		fusion_reset_all_caches();
	}

	/**
	 * Add necessary data for builder.
	 *
	 * @access public
	 * @since 2.2
	 * @param  array $data The data already added.
	 * @return array $data The data with panel data added.
	 */
	public function add_builder_data( $data ) {
		$data['template_override'] = [
			'content'        => $this->get_override( 'content' ),
			'footer'         => $this->get_override( 'footer' ),
			'page_title_bar' => $this->get_override( 'page_title_bar' ),
		];
		return $data;
	}

	/**
	 * Link to admin bar for builder.
	 *
	 * @access public
	 * @since 2.2
	 * @param array $admin_bar admin bar.
	 * @return void
	 */
	public function builder_trigger( $admin_bar ) {
		$override = $this->get_override( 'content' );
		if ( ! $override || ! ( is_404() || is_search() ) ) {
			return;
		}

		$customize_url = get_the_guid( $override );
		$customize_url = add_query_arg( 'fb-edit', true, $customize_url );

		$admin_bar->add_node(
			[
				'id'    => 'fb-edit',
				'title' => esc_html__( 'Fusion Builder Live', 'fusion-builder' ),
				'href'  => $customize_url,
			]
		);

	}

	/**
	 * Get override notice text.
	 *
	 * @access public
	 * @since 2.2
	 * @param object $override Post object for template.
	 * @param string $type Type of template override.
	 * @return string
	 */
	public function get_override_text( $override, $type = 'content' ) {

		if ( ! is_admin() && ! ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) ) {
			return;
		}

		$post_type = get_post_type();
		if ( ! $post_type ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );
		$labels           = get_post_type_labels( $post_type_object );
		/* Translators: The layout-section type. */
		$type_label = ( 'layout' === $type ) ? esc_html__( 'Layout', 'fusion-builder' ) : sprintf( esc_html__( '%s Layout Section', 'fusion-builder' ), esc_html( $this->types[ $type ]['label'] ) );
		$edit_link  = get_edit_post_link( $override );
		$title      = get_the_title( $override );

		if ( ! $override->ID ) {
			$edit_link = admin_url( 'admin.php?page=fusion-layouts' );
			$title     = __( 'Global', 'fusion-builder' );
		}

		return sprintf(
			/* translators: 1: The current post type and the edit link. 2: "footer" or "page title bar". 3: template title & link. */
			esc_html__( 'This %1$s is currently using a custom %2$s - %3$s.', 'fusion-builder' ),
			$labels->singular_name,
			$type_label,
			'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( admin_url( 'admin.php?page=fusion-layouts' ) ) . '">' . esc_html( $title ) . '</a>'
		);
	}

	/**
	 * Change which tabs should show depending on template type.
	 *
	 * @access public
	 * @since 2.2
	 * @param array  $pagetype_data Array of tabs for each post type.
	 * @param string $posttype Current post type.
	 * @return array
	 */
	public function template_tabs( $pagetype_data, $posttype ) {
		if ( 'fusion_tb_section' === $posttype ) {
			$post_id  = is_admin() ? get_the_id() : fusion_library()->get_page_id();
			$terms    = get_the_terms( $post_id, 'fusion_tb_category' );
			$category = is_array( $terms ) ? $terms[0]->name : false;

			// Check type of template.
			if ( 'footer' === $category || 'page_title_bar' === $category ) {
				$pagetype_data['fusion_tb_section'] = [ 'template' ];
			}
		}

		return $pagetype_data;
	}

	/**
	 * Renders the template custom-CSS.
	 *
	 * @access public
	 * @since 2.2.0
	 * @return void
	 */
	public function render_custom_css() {

		$types = $this->get_template_terms();

		foreach ( $types as $type => $args ) {

			// Get the override.
			$override = $this->get_override( $type );

			// No need to do anything if we don't have an override for this type.
			if ( ! $override ) {
				continue;
			}

			// Get the custom-CSS.
			$css = get_post_meta( $override->ID, '_fusion_builder_custom_css', true );

			// Skip if there's no CSS.
			if ( ! $css ) {
				continue;
			}

			// Output the styles.
			echo '<style type="text/css" id="fusion-builder-template-' . esc_attr( $type ) . '-css">';
			echo wp_strip_all_tags( $css ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</style>';
		}
	}

	/**
	 * Returns layout template conditions
	 *
	 * @access public
	 * @since 2.2.0
	 * @return array
	 */
	public function get_layout_conditions() {
		$sections = [
			'page'    => $this->get_layout_section_conditions( 'page' ),
			'post'    => $this->get_layout_section_conditions( 'post' ),
			'archive' => $this->get_layout_section_conditions_for_archives(),
		];

		$post_types = get_post_types(
			[
				'public'             => true,
				'show_in_nav_menus'  => true,
				'publicly_queryable' => true,
			]
		);
		sort( $post_types );
		// Remove post type because is already in sections.
		unset( $post_types['post'] );
		// Create a section for each post type.
		foreach ( $post_types as $post_type ) {
			$sections[ $post_type ] = $this->get_layout_section_conditions( $post_type );
		}

		$sections['other'] = [
			'label'      => esc_html__( 'Other', 'fusion-builder' ),
			'conditions' => [
				[
					'id'    => 'search_results',
					'label' => 'Search Results',
					'type'  => 'archives',
				],
				[
					'id'    => 'not_found',
					'label' => esc_html__( '404 Page', 'fusion-builder' ),
					'type'  => 'singular',
				],
			],
		];

		return $sections;
	}

	/**
	 * Returns layout single section conditions
	 *
	 * @access public
	 * @since 2.2.0
	 * @param string $post_type - The post type name.
	 * @return array
	 */
	public function get_layout_section_conditions( $post_type ) {
		$section          = [];
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type ) {
			return $section;
		}
		$section = [
			'label'     => $post_type_object->label,
			'post_type' => $post_type,
		];
		// All condition.
		$section['conditions'][] = [
			'id'    => 'singular_' . $post_type,
			/* Translators: The post-type label. */
			'label' => sprintf( esc_html__( 'All %s', 'fusion-builder' ), $post_type_object->label ),
			'type'  => 'singular',
		];
		// Specific page conditions.
		if ( 'page' === $post_type ) {
			$section['conditions'][] = [
				'id'    => 'front_page',
				'label' => __( 'Front Page', 'fusion-builder' ),
				'type'  => 'singular',
			];
		}
		// Specific archives conditions.
		if ( $post_type_object->has_archive || 'post' === $post_type ) {
			$section['conditions'][] = [
				'id'    => 'archive_of_' . $post_type,
				'type'  => 'archives',
				/* Translators: The post-type label. */
				'label' => sprintf( esc_html__( '%s Archive Type', 'fusion-builder' ), $post_type_object->label ),
			];
		}

		$section['conditions'][] = [
			'id'       => 'specific_' . $post_type,
			/* Translators: The post-type label. */
			'label'    => sprintf( esc_html__( 'Specific %s', 'Avada' ), $post_type_object->label ),
			'type'     => 'singular',
			'multiple' => true,
		];

		if ( is_post_type_hierarchical( $post_type ) ) {
			$section['conditions'][] = [
				'id'       => 'children_of_' . $post_type,
				/* Translators: The post-type label. */
				'label'    => sprintf( esc_html__( 'Children of Specific %s', 'fusion-builder' ), $post_type_object->label ),
				'type'     => 'singular',
				'multiple' => true,
			];
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		foreach ( $taxonomies as $taxonomy_id => $taxonomy ) {
			if ( ! $taxonomy->public || ! $taxonomy->show_ui ) {
				continue;
			}
			$section['conditions'][] = [
				'id'       => 'taxonomy_of_' . $taxonomy_id,
				/* translators: 1: The post-type label. 2: The laxonomy label. */
				'label'    => sprintf( esc_html__( '%1$s with Specific %2$s', 'fusion-builder' ), $post_type_object->label, $taxonomy->label ),
				'type'     => 'archives',
				'multiple' => true,
			];
		}

		return $section;
	}

	/**
	 * Returns layout archives section conditions
	 *
	 * @access public
	 * @since 2.2.0
	 * @return array
	 */
	public function get_layout_section_conditions_for_archives() {
		$section = [
			'label'      => esc_html__( 'Archives', 'fusion-builder' ),
			'conditions' => [
				[
					'id'    => 'all_archives',
					'label' => esc_html__( 'All Archives Pages', 'fusion-builder' ),
					'type'  => 'archives',
				],
				[
					'id'    => 'date_archive',
					'label' => esc_html__( 'All Date Pages', 'fusion-builder' ),
					'type'  => 'archives',
				],
				// Author archives conditions.
				$section['conditions'][] = [
					'id'    => 'author_archive',
					'label' => esc_html__( 'All Author Pages', 'fusion-builder' ),
					'type'  => 'archives',
				],
				$section['conditions'][] = [
					'id'       => 'author_archive_',
					'label'    => esc_html__( 'Specific Author Page', 'fusion-builder' ),
					'type'     => 'archives',
					'multiple' => true,
				],
			],
		];

		$taxonomies = get_taxonomies(
			[
				'public'   => true,
				'show_ui'  => true,
				'_builtin' => false,
			],
			'objects'
		);
		ksort( $taxonomies );

		$taxonomies = array_merge(
			[
				'category' => get_taxonomy( 'category' ),
				'post_tag' => get_taxonomy( 'post_tag' ),
			],
			$taxonomies
		);

		foreach ( $taxonomies as $taxonomy ) {

			$section['conditions'][] = [
				'id'    => $taxonomy->name,
				/* Translators: The post-type label. */
				'label' => sprintf( esc_html__( 'All %s', 'fusion-builder' ), $taxonomy->label ),
				'type'  => 'archives',
			];

			$section['conditions'][] = [
				'id'       => 'archive_of_' . $taxonomy->name,
				/* Translators: The post-type label. */
				'label'    => sprintf( esc_html__( 'Specific %s', 'fusion-builder' ), $taxonomy->label ),
				'type'     => 'archives',
				'multiple' => true,
			];
		}
		return $section;
	}

	/**
	 * Returns layout single section child conditions
	 *
	 * @access public
	 * @since 2.2.0
	 * @param string $parent The parent condition.
	 * @param int    $page   The current page.
	 * @param string $search The serach string.
	 * @return array
	 */
	public function get_layout_child_conditions( $parent, $page = 1, $search = '' ) {
		$is_post_type   = strpos( $parent, 'specific_' ) || strpos( $parent, 'children_of_' );
		$is_author      = strpos( $parent, 'author_archive_' );
		$posts_per_page = 10;
		$conditions     = [];

		if ( false !== strpos( $parent, 'children_of_' ) || false !== strpos( $parent, 'specific_' ) ) {
			$post_type = preg_replace( '/specific_|children_of_/', '', $parent );
			$args      = [
				'post_type'      => $post_type,
				'posts_per_page' => $posts_per_page,
				'paged'          => $page,
				's'              => $search,
			];
			$posts     = get_posts( $args );
			foreach ( $posts as $post ) {
				$conditions [] = [
					'id'     => $parent . '|' . $post->ID,
					'parent' => $parent,
					'label'  => $post->post_title,
					'type'   => 'singular',
				];
			}
		} elseif ( false !== $is_author ) {
			$args  = [
				'number' => $posts_per_page,
				'paged'  => $page,
				'search' => $search,
			];
			$users = get_users( $args );

			foreach ( $users as $user ) {
				$conditions[] = [
					'id'     => $parent . '|' . $user->ID,
					'parent' => $parent,
					'label'  => $user->display_name,
					'type'   => 'archives',
				];
			}
		} else {
			$taxonomy = preg_replace( '/taxonomy_of_|archive_of_/', '', $parent );
			$terms    = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'number'     => $posts_per_page,
					'offset'     => ( $page - 1 ) * $posts_per_page,
					'search'     => $search,
				]
			);

			foreach ( $terms as $term ) {
				$conditions[] = [
					'id'     => $parent . '|' . $term->term_id,
					'parent' => $parent,
					'label'  => $term->name,
					'type'   => 'archives',
				];
			}
		}

		return $conditions;
	}

	/**
	 * Reset caches when a template or layout gets deleted.
	 *
	 * @access public
	 * @since 2.2.0
	 * @param int     $pid  The post-ID.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function clean_post_cache( $pid, $post ) {
		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return;
		}

		if ( 'fusion_tb_section' === $post->post_type || 'fusion_tb_layout' === $post->post_type ) {
			fusion_reset_all_caches();
		}
	}

	/**
	 * Print extra scripts and styles in the admin footer.
	 *
	 * @access public
	 * @since 2.2.0
	 * @return void
	 */
	public function admin_footer() {
		if ( ! $this->get_override( 'content' ) ) {
			return;
		}
		?>
		<script>
		let pageTemplateDropdown = document.getElementById( 'page_template' );
		if ( pageTemplateDropdown ) {
			pageTemplateDropdown.setAttribute( 'disabled', true );
		}
		</script>
		<?php
	}
}

/**
 * Instantiates the Fusion_Template_Builder class.
 * Make sure the class is properly set-up.
 *
 * @since object 2.2
 * @return object Fusion_App
 */
function Fusion_Template_Builder() { // phpcs:ignore WordPress.NamingConventions
	return Fusion_Template_Builder::get_instance();
}
Fusion_Template_Builder();
