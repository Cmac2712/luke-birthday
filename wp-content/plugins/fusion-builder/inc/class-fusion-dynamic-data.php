<?php
/**
 * Fusion Dynamic Data class.
 *
 * @package Fusion-Builder
 * @since 2.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Fusion Dynamic Data class.
 *
 * @since 2.1
 */
class Fusion_Dynamic_Data {

	/**
	 * Array of dynamic param definitions.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $params = [];

	/**
	 * Array of dynamic param values and arguments.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $values = [];

	/**
	 * Array of text fields.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $text_fields = [ 'textfield', 'textarea', 'tinymce', 'raw_textarea' ];

	/**
	 * Array of image fields.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $image_fields = [ 'upload' ];

	/**
	 * Array of link fields.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $link_fields = [ 'link_selector' ];

	/**
	 * Options which show on both text and link.
	 *
	 * @access private
	 * @since 2.1
	 * @var array
	 */
	private $link_and_text_fields = [ 'link_selector', 'textfield', 'textarea', 'tinymce', 'raw_textarea' ];

	/**
	 * Class constructor.
	 *
	 * @since 2.1
	 * @access public
	 */
	public function __construct() {
		if ( ! apply_filters( 'fusion_load_dynamic_data', true ) ) {
			return;
		}
		add_filter( 'fusion_pre_shortcode_atts', [ $this, 'filter_dynamic_args' ], 10, 4 );
		add_filter( 'fusion_shortcode_content', [ $this, 'filter_dynamic_content' ], 10, 4 );
		add_filter( 'fusion_app_preview_data', [ $this, 'filter_preview_data' ], 10, 3 );
		add_filter( 'fusion_dynamic_override', [ $this, 'extra_output_filter' ], 10, 5 );
		add_action( 'fusion_builder_admin_scripts_hook', [ $this, 'backend_builder_data' ], 10 );
		$this->include_and_init_callbacks();
	}

	/**
	 * Require callbacks class.
	 *
	 * @since 2.1
	 * @access public
	 */
	public function include_and_init_callbacks() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-dynamic-data-callbacks.php';
		new Fusion_Dynamic_Data_Callbacks();
	}

	/**
	 * Filter the shortcode content.
	 *
	 * @since 2.1
	 * @access public
	 * @param string $content Shortcode element content.
	 * @param string $shortcode Shortcode name.
	 * @param array  $args Shortcode parameters.
	 * @return array
	 */
	public function filter_dynamic_content( $content, $shortcode, $args ) {
		if ( ! isset( $args['dynamic_params'] ) ) {
			return $content;
		}

		$dynamic_args = $this->convert( $args['dynamic_params'] );
		$dynamic_arg  = $dynamic_args && isset( $dynamic_args['element_content'] ) ? $dynamic_args['element_content'] : false;

		if ( ! $dynamic_arg ) {
			return $content;
		}

		$value = $this->get_value( $dynamic_arg );

		if ( false === $value ) {
			return $content;
		}

		return $value;
	}

	/**
	 * Filter full output array.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $out Array to filter.
	 * @param array  $dynamic_arg Args for dynamic param.
	 * @param string $param_id ID for param in element.
	 * @param string $shortcode Name of shortcode.
	 * @param mixed  $value Value being set to that param.
	 * @return array
	 */
	public function extra_output_filter( $out, $dynamic_arg, $param_id, $shortcode, $value ) {
		$dynamic_id = $dynamic_arg['data'];

		switch ( $dynamic_id ) {
			case 'post_featured_image':
				if ( 'fusion_imageframe' === $shortcode && 'element_content' === $param_id ) {
					$out['image_id'] = get_post_thumbnail_id();
				} else {
					$out[ $param_id . '_id' ] = get_post_thumbnail_id();
				}
				break;
			case 'acf_image':
				$image_id   = false;
				$image_data = isset( $dynamic_arg['field'] ) ? get_field( $dynamic_arg['field'] ) : false;

				if ( is_array( $image_data ) && isset( $image_data['url'] ) ) {
					$image_id = $image_data['ID'];
				} elseif ( is_integer( $image_data ) ) {
					$image_id = $image_data;
				}

				if ( 'fusion_imageframe' === $shortcode && 'element_content' === $param_id ) {
					$out['image_id'] = $image_id;
				} else {
					$out[ $param_id . '_id' ] = $image_id;
				}
				break;
		}
		return $out;
	}

	/**
	 * Filter the arguments.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $out Array to filter.
	 * @param array  $defaults Defaults for shortcode.
	 * @param array  $args Arguments for shortcode.
	 * @param stirng $shortcode Shortcode name.
	 * @return array
	 */
	public function filter_dynamic_args( $out, $defaults, $args, $shortcode ) {
		if ( ! isset( $out['dynamic_params'] ) ) {
			return $out;
		}

		$dynamic_args = $this->convert( $out['dynamic_params'] );

		foreach ( $dynamic_args as $id => $dynamic_arg ) {

			$value = $this->get_value( $dynamic_arg );

			if ( false === $value ) {
				continue;
			}

			$out[ $id ] = $value;

			$out = apply_filters( 'fusion_dynamic_override', $out, $dynamic_arg, $id, $shortcode, $value );
		}
		return $out;
	}

	/**
	 * Get the dynamic value.
	 *
	 * @since 2.1
	 * @access public
	 * @param array $dynamic_arg Array of arguments.
	 * @return mixed
	 */
	public function get_value( $dynamic_arg ) {
		$param             = isset( $dynamic_arg['data'] ) ? $this->get_param( $dynamic_arg['data'] ) : false;
		$fallback          = isset( $dynamic_arg['fallback'] ) && '' !== $dynamic_arg['fallback'] ? $dynamic_arg['fallback'] : false;
		$callback          = $param && isset( $param['callback'] ) ? $param['callback'] : false;
		$default           = $param && isset( $param['default'] ) && function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() && is_singular( 'fusion_tb_section' ) ? $param['default'] : false;
		$callback_function = $callback && isset( $callback['function'] ) ? $callback['function'] : false;
		$callback_exists   = $callback_function && ( is_callable( 'Fusion_Dynamic_Data_Callbacks::' . $callback_function ) || is_callable( $callback_function ) ) ? true : false;
		if ( ! $param || ( ! $default && ! $fallback && ! $callback_exists ) ) {
			return false;
		}

		if ( ! $callback_exists ) {
			return false !== $fallback ? $fallback : $default;
		}

		$value = is_callable( 'Fusion_Dynamic_Data_Callbacks::' . $callback_function ) ? call_user_func_array( 'Fusion_Dynamic_Data_Callbacks::' . $callback_function, [ $dynamic_arg ] ) : call_user_func_array( $callback_function, [ $dynamic_arg ] );
		if ( ( ! $value || '' === $value ) && ( $default || $fallback ) ) {
			return false !== $fallback ? $fallback : $default;
		}

		(string) $before_string = isset( $dynamic_arg['before'] ) ? $dynamic_arg['before'] : '';
		(string) $after_string  = isset( $dynamic_arg['after'] ) ? $dynamic_arg['after'] : '';

		$this->maybe_store_value( $value, $dynamic_arg );

		return $before_string . $value . $after_string;
	}

	/**
	 * If a live editor load then we store.
	 *
	 * @since 2.1
	 * @access public
	 * @param mixed $value Dynamic value.
	 * @param array $dynamic_arg The arguments for specific dynamic value.
	 * @return void
	 */
	public function maybe_store_value( $value, $dynamic_arg ) {
		if ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) {
			$this->values[ $dynamic_arg['data'] ][] = [
				'value' => $value,
				'args'  => $dynamic_arg,
			];
		}
	}

	/**
	 * Add in dynamic data values to live editor data.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $data Existing data.
	 * @param string $page_id The ID of the page.
	 * @param string $post_type The post type of the page.
	 * @return array
	 */
	public function filter_preview_data( $data, $page_id, $post_type ) {
		$page_id = apply_filters( 'fusion_dynamic_post_id', $page_id );

		$data['dynamicValues'][ $page_id ] = $this->values;
		$data['dynamicOptions']            = $this->get_params();
		$data['dynamicCommon']             = $this->get_common();
		$data['dynamicPostID']             = $page_id;
		$data['site_title']                = get_bloginfo( 'name' );
		$data['site_tagline']              = get_bloginfo( 'description' );
		return $data;
	}

	/**
	 * Add in dynamic data values to live editor data.
	 *
	 * @since 2.1
	 * @access public
	 */
	public function backend_builder_data() {
		$script = FUSION_BUILDER_DEV_MODE ? 'fusion_builder_app_js' : 'fusion_builder';
		wp_localize_script(
			$script,
			'fusionDynamicData',
			[
				'dynamicOptions'      => $this->get_params(),
				'commonDynamicFields' => $this->get_common(),
			]
		);
	}

	/**
	 * Convert from encoded string to array.
	 *
	 * @since 2.1
	 * @access public
	 * @param string $param_string Encoded param string.
	 * @return array
	 */
	public function convert( $param_string ) {
		(array) $params = json_decode( fusion_decode_if_needed( $param_string ), true );
		return $params;
	}

	/**
	 * Get param map.
	 *
	 * @since 2.1
	 * @access public
	 * @return array
	 */
	public function get_params() {
		if ( empty( $this->params ) ) {
			$this->set_params();
		}
		return $this->params;
	}

	/**
	 * Get single param.
	 *
	 * @since 2.1
	 * @access public
	 * @param string $id Param ID.
	 * @return mixed
	 */
	public function get_param( $id ) {
		if ( empty( $this->params ) ) {
			$this->set_params();
		}
		return is_array( $this->params ) && isset( $this->params[ $id ] ) ? $this->params[ $id ] : false;
	}

	/**
	 * Common shared fields.
	 *
	 * @since 2.1
	 * @access public
	 * @return array
	 */
	public function get_common() {
		return [
			'before'   => [
				'label'       => esc_html__( 'Before', 'fusion-builder' ),
				'description' => esc_html__( 'Text before value.' ),
				'id'          => 'before',
				'default'     => '',
				'type'        => 'text',
				'value'       => '',
			],
			'after'    => [
				'label'       => esc_html__( 'After', 'fusion-builder' ),
				'description' => esc_html__( 'Text after value.' ),
				'id'          => 'after',
				'default'     => '',
				'type'        => 'text',
				'value'       => '',
			],
			'fallback' => [
				'label'       => esc_html__( 'Fallback', 'fusion-builder' ),
				'description' => esc_html__( 'Fallback if no value found.' ),
				'id'          => 'fallback',
				'default'     => '',
				'type'        => 'text',
				'value'       => '',
			],
		];
	}

	/**
	 * Get builder status.
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function get_builder_status() {
		global $pagenow;

		$allowed_post_types = class_exists( 'FusionBuilder' ) ? FusionBuilder()->allowed_post_types() : [];
		$post_type          = get_post_type();

		return ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() || ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) ) && $post_type && in_array( $post_type, $allowed_post_types, true );
	}

	/**
	 * Get builder status.
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function is_template_edited() {
		global $pagenow;

		$allowed_post_types = class_exists( 'FusionBuilder' ) ? FusionBuilder()->allowed_post_types() : [];
		$post_type          = get_post_type();

		return ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() || ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) ) && $post_type && 'fusion_tb_section' === $post_type;
	}

	/**
	 * Set param map.
	 *
	 * @since 2.1
	 * @access public
	 * @return void
	 */
	public function set_params() {
		$post_taxonomies = [];
		$post_meta       = [];
		$params          = [];
		$single_label    = esc_html__( 'Post', 'fusion-builder' );

		$post_data = [
			'id'        => get_the_ID(),
			'post_type' => get_post_type(),
			'archive'   => false,
		];

		$post_data = apply_filters( 'fusion_dynamic_post_data', $post_data );

		if ( $this->get_builder_status() ) {
			// Get all registered taxonomies.
			$object_tax_slugs = get_object_taxonomies( $post_data['post_type'] );

			// Create key value pairs.
			foreach ( $object_tax_slugs as $tax_slug ) {
				$tax = get_taxonomy( $tax_slug );
				if ( false !== $tax && $tax->public ) {
					$post_taxonomies[ $tax_slug ] = $tax->labels->name;
				}
			}

			// Create an array of our post-meta keys.
			$meta_fields      = maybe_unserialize( fusion_data()->post_meta( $post_data['id'] )->get_all_meta() );
			$meta_fields_keys = array_keys( $meta_fields );
			$post_meta        = array_combine( $meta_fields_keys, $meta_fields_keys );
		}

		$post_type_object = get_post_type_object( $post_data['post_type'] );
		if ( is_object( $post_type_object ) ) {
			$single_label = $post_type_object->labels->singular_name;
		}
		$params = [
			'post_title' => [
				/* translators: Single post type title. */
				'label'            => esc_html__( 'Title', 'fusion-builder' ),
				$single_label,
				'id'               => 'post_title',
				'group'            => $single_label,
				'options'          => $this->text_fields,
				'ajax_on_template' => true,
				'default'          => __( 'Your Title Goes Here', 'fusion-builder' ),
				'callback'         => [
					'function' => 'fusion_get_object_title',
					'ajax'     => true,
				],
				'listeners'        => [
					'post_title' => [
						'location' => 'postDetails',
					],
				],
				'fields'           => [
					'include_context' => [
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Include Context', 'fusion-builder' ),
						'description' => esc_html__( 'Whether to include title context, ie. Category: Avada.' ),
						'param_name'  => 'include_context',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
				],
			],
		];

		$params['post_excerpt'] = [
			/* translators: Single post type excerpt. */
			'label'            => esc_html__( 'Excerpt / Archive Description', 'fusion-builder' ),
			'id'               => 'post_excerpt',
			'group'            => $single_label,
			'options'          => $this->text_fields,
			'default'          => __( 'Your Description Goes Here', 'fusion-builder' ),
			'ajax_on_template' => true,
			'callback'         => [
				'function' => 'fusion_get_object_excerpt',
				'ajax'     => true,
			],
		];

		// Only add single post related for single posts.
		$params['post_comments'] = $this->is_template_edited() || ( $post_data['id'] && 0 < $post_data['id'] && comments_open( $post_data['id'] ) ) ? [
			/* translators: Single post type terms. */
			'label'    => esc_html__( 'Comments Number', 'fusion-builder' ),
			'id'       => 'post_comments',
			'group'    => $single_label,
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_post_comments',
				'ajax'     => true,
			],
			'fields'   => [
				'link' => [
					'type'        => 'radio_button_set',
					'heading'     => esc_html__( 'Link', 'fusion-builder' ),
					'description' => esc_html__( 'Whether the comment number should link to the comments form.' ),
					'param_name'  => 'link',
					'default'     => 'no',
					'value'       => [
						'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
						'no'  => esc_attr__( 'No', 'fusion-builder' ),
					],
				],
			],
		] : false;

		$params['post_terms'] = $this->is_template_edited() || ! empty( $post_taxonomies ) || ! $this->get_builder_status() ? [
			/* translators: Single post type terms. */
			'label'    => esc_html__( 'Terms', 'fusion-builder' ),
			'id'       => 'post_terms',
			'group'    => $single_label,
			'options'  => $this->text_fields,
			'default'  => 'Lorem, Ipsum, Dolor',
			'callback' => [
				'function' => 'fusion_get_post_terms',
				'ajax'     => true,
			],
			'fields'   => [
				'type'      => [
					'heading'     => esc_html__( 'Taxonomy', 'fusion-builder' ),
					'description' => esc_html__( 'Taxonomy to use.' ),
					'param_name'  => 'type',
					'default'     => '',
					'type'        => $this->is_template_edited() ? 'text' : 'select',
					'value'       => $post_taxonomies,
				],
				'separator' => [
					'heading'     => esc_html__( 'Separator', 'fusion-builder' ),
					'description' => esc_html__( 'Separator between post terms.' ),
					'param_name'  => 'separator',
					'value'       => ',',
					'type'        => 'textfield',
				],
				'link'      => [
					'type'        => 'radio_button_set',
					'heading'     => esc_html__( 'Link', 'fusion-builder' ),
					'description' => esc_html__( 'Whether each term should link to term page.' ),
					'param_name'  => 'link',
					'default'     => 'yes',
					'value'       => [
						'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
						'no'  => esc_attr__( 'No', 'fusion-builder' ),
					],
				],
			],
		] : false;

		$params['post_id'] = [
			/* translators: Single post type ID. */
			'label'    => esc_html__( 'ID', 'fusion-builder' ),
			'id'       => 'post_id',
			'group'    => $single_label,
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_post_id',
				'ajax'     => false,
			],
		];

		$params['post_time'] = [
			/* translators: Single post type time. */
			'label'    => esc_html__( 'Time', 'fusion-builder' ),
			'id'       => 'post_time',
			'group'    => $single_label,
			'options'  => $this->text_fields,
			'default'  => current_time( get_option( 'time_format' ) ),
			'callback' => [
				'function' => 'fusion_get_post_time',
				'ajax'     => true,
			],
			'fields'   => [
				'format' => [
					'heading'     => esc_html__( 'Format', 'fusion-builder' ),
					'description' => __( 'Time format to use.  <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>' ),
					'param_name'  => 'format',
					'value'       => get_option( 'time_format' ),
					'type'        => 'text',
				],
			],
		];

		$params['post_date'] = [
			/* translators: Single post type date. */
			'label'    => esc_html__( 'Date', 'fusion-builder' ),
			'id'       => 'post_date',
			'group'    => $single_label,
			'options'  => $this->text_fields,
			'default'  => current_time( get_option( 'date_format' ) ),
			'callback' => [
				'function' => 'fusion_get_post_date',
				'ajax'     => true,
			],
			'fields'   => [
				'type'   => [
					'heading'     => esc_html__( 'Format', 'fusion-builder' ),
					'description' => esc_html__( 'Date format to use.' ),
					'param_name'  => 'type',
					'default'     => '',
					'type'        => 'select',
					'value'       => [
						''         => esc_html__( 'Post Published', 'fusion-builder' ),
						'modified' => esc_html__( 'Post Modified', 'fusion-builder' ),
					],
				],
				'format' => [
					'heading'     => esc_html__( 'Format', 'fusion-builder' ),
					'description' => __( 'Date format to use.  <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>' ),
					'param_name'  => 'format',
					'value'       => get_option( 'date_format' ),
					'type'        => 'text',
				],
			],
		];

		$params['post_custom_field'] = [
			/* translators: Single post type custom field. */
			'label'    => esc_html__( 'Custom Field', 'fusion-builder' ),
			'id'       => 'post_custom_field',
			'group'    => $single_label,
			'options'  => $this->link_and_text_fields,
			'default'  => __( 'Custom Field Value Here', 'fusion-builder' ),
			'callback' => [
				'function' => 'fusion_get_post_custom_field',
				'ajax'     => false,
			],
			'fields'   => [
				'key' => [
					'heading'     => esc_html__( 'Key', 'fusion-builder' ),
					'description' => esc_html__( 'Custom field ID key.' ),
					'param_name'  => 'key',
					'default'     => '',
					'type'        => $this->is_template_edited() ? 'text' : 'select',
					'value'       => $post_meta,
				],
			],
		];

		$params['post_featured_image'] = post_type_supports( $post_data['post_type'], 'thumbnail' ) || ! $this->get_builder_status() || $this->is_template_edited() ? [
			'label'     => esc_html__( 'Featured Image', 'fusion-builder' ),
			'id'        => 'post_featured_image',
			'group'     => $single_label,
			'options'   => $this->image_fields,
			'callback'  => [
				'function' => 'post_featured_image',
				'ajax'     => true,
			],
			'exclude'   => [ 'before', 'after' ],
			'options'   => [ 'upload' ],
			'listeners' => [
				'_thumbnail_id' => [
					'location' => 'postMeta',
				],
			],
		] : false;

		$params['site_title']        = [
			'label'    => esc_html__( 'Site Title', 'fusion-builder' ),
			'id'       => 'site_title',
			'group'    => esc_attr__( 'Site', 'fusion-builder' ),
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_site_title',
				'ajax'     => true,
			],
		];
		$params['site_tagline']      = [
			'label'    => esc_html__( 'Site Tagline', 'fusion-builder' ),
			'id'       => 'site_tagline',
			'group'    => esc_attr__( 'Site', 'fusion-builder' ),
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_site_tagline',
				'ajax'     => true,
			],
		];
		$params['request_parameter'] = [
			'label'    => esc_html__( 'Request Parameter', 'fusion-builder' ),
			'id'       => 'site_request_param',
			'group'    => esc_attr__( 'Other', 'fusion-builder' ),
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_site_request_param',
				'ajax'     => true,
			],
			'fields'   => [
				'type' => [
					'heading'    => esc_html__( 'Param Type', 'fusion-builder' ),
					'param_name' => 'type',
					'default'    => 'get',
					'type'       => 'select',
					'value'      => [
						'get'       => esc_html__( 'GET', 'fusion-builder' ),
						'post'      => esc_html__( 'POST', 'fusion-builder' ),
						'query_var' => esc_html__( 'Query Var', 'fusion-builder' ),
					],
				],
				'name' => [
					'heading'    => esc_html__( 'Query Var', 'fusion-builder' ),
					'param_name' => 'name',
					'type'       => 'textfield',
					'value'      => '',
				],
			],
		];
		$params['shortcode']         = [
			'label'    => esc_html__( 'Shortcode', 'fusion-builder' ),
			'id'       => 'shortcode',
			'group'    => esc_attr__( 'Other', 'fusion-builder' ),
			'options'  => $this->link_and_text_fields,
			'callback' => [
				'function' => 'dynamic_shortcode',
				'ajax'     => true,
			],
			'fields'   => [
				'shortcode' => [
					'heading'    => esc_html__( 'Shortcode', 'fusion-builder' ),
					'param_name' => 'shortcode',
					'type'       => 'textarea',
					'value'      => '',
				],
			],
		];

		$params['date'] = [
			'label'    => esc_html__( 'Date', 'fusion-builder' ),
			'id'       => 'date',
			'group'    => esc_attr__( 'Other', 'fusion-builder' ),
			'options'  => $this->text_fields,
			'callback' => [
				'function' => 'fusion_get_date',
				'ajax'     => true,
			],
			'fields'   => [
				'format' => [
					'heading'     => esc_html__( 'Format', 'fusion-builder' ),
					'description' => __( 'Date format to use.  <a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" rel="noopener noreferrer">Formatting Date and Time</a>' ),
					'param_name'  => 'format',
					'value'       => get_option( 'date_format' ),
					'type'        => 'text',
				],
			],
		];

		$params = $this->maybe_add_acf_fields( $params, $post_data['id'], $post_data['post_type'] );
		$params = $this->maybe_add_woo_fields( $params, $post_data['id'], $post_data['post_type'] );

		// Skip target post data.
		$params = $this->maybe_add_page_title_bar_fields( $params, get_the_ID(), get_post_type() );

		// Skip author if we are editing archive template.
		if ( ! $post_data['archive'] && ! is_404() && ! is_search() || $this->is_template_edited() ) {
			$params = $this->maybe_add_author_fields( $params, $post_data['id'], $post_data['post_type'] );
		}
		$this->params = apply_filters( 'fusion_set_dynamic_params', $params );

	}

	/**
	 * Add Author fields if they exist.
	 *
	 * @since 2.2
	 * @access public
	 * @param array  $params Params being used.
	 * @param int    $post_id The target post id.
	 * @param string $post_type The target post type.
	 * @return array
	 */
	public function maybe_add_author_fields( $params, $post_id, $post_type ) {
		if ( post_type_supports( $post_type, 'author' ) || $this->is_template_edited() ) {
			$params['author_name']        = [
				'label'    => esc_html__( 'Author Name', 'fusion-builder' ),
				'id'       => 'author_name',
				'group'    => esc_attr__( 'Author', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => 'Emery Burns',
				'callback' => [
					'function' => 'get_author_name',
					'ajax'     => true,
				],
			];
			$params['author_description'] = [
				'label'    => esc_html__( 'Author Description', 'fusion-builder' ),
				'id'       => 'author_description',
				'group'    => esc_attr__( 'Author', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => 'Lorem ipsum dolor sit amet.',
				'callback' => [
					'function' => 'get_author_description',
					'ajax'     => true,
				],
			];
			$params['author_avatar']      = [
				'label'    => esc_html__( 'Author Avatar', 'fusion-builder' ),
				'id'       => 'author_avatar',
				'group'    => esc_attr__( 'Author', 'fusion-builder' ),
				'options'  => $this->image_fields,
				'callback' => [
					'function' => 'get_author_avatar',
					'ajax'     => true,
				],
			];
			$params['author_url']         = [
				'label'    => esc_html__( 'Author Page URL', 'fusion-builder' ),
				'id'       => 'author_url',
				'group'    => esc_attr__( 'Author', 'fusion-builder' ),
				'options'  => $this->link_fields,
				'exclude'  => [ 'before', 'after' ],
				'default'  => 'https://theme-fusion.com',
				'callback' => [
					'function' => 'get_author_url',
					'ajax'     => true,
				],
			];
			$params['author_social']      = [
				'label'    => esc_html__( 'Author Social URL', 'fusion-builder' ),
				'id'       => 'author_social',
				'group'    => esc_attr__( 'Author', 'fusion-builder' ),
				'options'  => $this->link_fields,
				'exclude'  => [ 'before', 'after' ],
				'callback' => [
					'function' => 'get_author_social',
					'ajax'     => true,
				],
				'fields'   => [
					'type' => [
						'heading'     => esc_html__( 'Social Link', 'fusion-builder' ),
						'description' => esc_html__( 'Select which social platform link to use.' ),
						'param_name'  => 'type',
						'default'     => 'author_email',
						'type'        => 'select',
						'value'       => [
							'author_email'    => esc_html__( 'Email', 'fusion-builder' ),
							'author_facebook' => esc_html__( 'Facebook', 'fusion-builder' ),
							'author_twitter'  => esc_html__( 'Twitter', 'fusion-builder' ),
							'author_linkedin' => esc_html__( 'LinkedIn', 'fusion-builder' ),
							'author_dribble'  => esc_html__( 'Dribble', 'fusion-builder' ),
							'author_whatsapp' => esc_html__( 'WhatsApp', 'fusion-builder' ),
						],
					],
				],
			];
		}
		return $params;
	}

	/**
	 * Add ACF fields if they exist.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $params    Params being used.
	 * @param int    $post_id   The target post id.
	 * @param string $post_type The target post type.
	 * @return array
	 */
	public function maybe_add_acf_fields( $params, $post_id, $post_type ) {
		if ( class_exists( 'ACF' ) ) {
			$fields              = [];
			$text_options        = false;
			$image_options       = false;
			$link_options        = false;
			$string_option_types = [ 'text', 'textarea', 'number', 'range', 'wysiwyg', 'raw_textarea' ];

			// In builder get fields active for post type for each group.
			if ( $this->get_builder_status() ) {
				$groups = acf_get_field_groups( [ 'post_type' => $post_type ] );
				foreach ( $groups as $group ) {

					// Get fields for group and check for text or image types.
					$fields = acf_get_fields( $group['key'] );
					if ( $fields && is_array( $fields ) ) {
						foreach ( $fields as $field ) {
							if ( in_array( $field['type'], $string_option_types, true ) ) {
								$text_options[ $field['name'] ] = $field['label'];
							} elseif ( 'image' === $field['type'] ) {
								$image_options[ $field['name'] ] = $field['label'];
							} elseif ( 'url' === $field['type'] ) {
								$link_options[ $field['name'] ] = $field['label'];
							}
						}
					}
				}
			}

			// In builder and have text options add option, on front-end add for callback availability.
			if ( ! $this->get_builder_status() || $text_options || $this->is_template_edited() ) {
				$params['acf_text'] = [
					'label'    => esc_html__( 'ACF Text', 'fusion-builder' ),
					'id'       => 'acf_text',
					'group'    => esc_attr__( 'Advanced Custom Fields', 'fusion-builder' ),
					'options'  => $this->text_fields,
					'default'  => __( 'Custom Field Value Here', 'fusion-builder' ),
					'callback' => [
						'function' => 'acf_get_field',
						'ajax'     => true,
					],
					'fields'   => [
						'field' => [
							'heading'     => esc_html__( 'Field', 'fusion-builder' ),
							'description' => esc_html__( 'Which field you want to use.', 'fusion-builder' ),
							'param_name'  => 'field',
							'default'     => '',
							'type'        => $this->is_template_edited() ? 'text' : 'select',
							'value'       => $text_options,
						],
					],
				];
			}

			// In builder and have image options add option, on front-end add for callback availability.
			if ( ! $this->get_builder_status() || $image_options || $this->is_template_edited() ) {
				$params['acf_image'] = [
					'label'    => esc_html__( 'ACF Image', 'fusion-builder' ),
					'id'       => 'acf_image',
					'group'    => esc_attr__( 'Advanced Custom Fields', 'fusion-builder' ),
					'callback' => [
						'function' => 'acf_get_image_field',
						'ajax'     => true,
					],
					'exclude'  => [ 'before', 'after', 'fallback' ],
					'options'  => $this->image_fields,
					'fields'   => [
						'field' => [
							'heading'     => esc_html__( 'Field', 'fusion-builder' ),
							'description' => esc_html__( 'Which field you want to use.', 'fusion-builder' ),
							'param_name'  => 'field',
							'default'     => '',
							'type'        => $this->is_template_edited() ? 'text' : 'select',
							'value'       => $image_options,
						],
					],
				];
			}

			// In builder and have image options add option, on front-end add for callback availability.
			if ( ! $this->get_builder_status() || $link_options || $this->is_template_edited() ) {
				$params['acf_link'] = [
					'label'    => esc_html__( 'ACF Link', 'fusion-builder' ),
					'id'       => 'acf_link',
					'group'    => esc_attr__( 'Advanced Custom Fields', 'fusion-builder' ),
					'callback' => [
						'function' => 'acf_get_field',
						'ajax'     => true,
					],
					'exclude'  => [ 'before', 'after', 'fallback' ],
					'options'  => $this->link_fields,
					'fields'   => [
						'field' => [
							'heading'     => esc_html__( 'Field', 'fusion-builder' ),
							'description' => esc_html__( 'Which field you want to use.', 'fusion-builder' ),
							'param_name'  => 'field',
							'default'     => '',
							'type'        => $this->is_template_edited() ? 'text' : 'select',
							'value'       => $link_options,
						],
					],
				];
			}
		}

		return $params;
	}

	/**
	 * Add WooCommerce single product fields if they exist.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $params    Params being used.
	 * @param string $post_type The current post type.
	 * @return array
	 */
	public function maybe_add_woo_fields( $params, $post_type ) {
		if ( ( function_exists( 'is_product' ) && is_product() ) || ( 'product' === $post_type ) || ( function_exists( 'is_product' ) && $this->is_template_edited() ) ) {
			$params['woo_price'] = [
				'label'    => esc_html__( 'Product Price', 'fusion-builder' ),
				'id'       => 'woo_price',
				'group'    => esc_attr__( 'WooCommerce', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => wc_price( 10 ),
				'callback' => [
					'function' => 'woo_get_price',
					'ajax'     => true,
				],
				'fields'   => [
					'format' => [
						'heading'     => esc_html__( 'Format', 'fusion-builder' ),
						'description' => esc_html__( 'Format of price to display.', 'fusion-builder' ),
						'param_name'  => 'format',
						'default'     => '',
						'type'        => 'select',
						'value'       => [
							''         => esc_html__( 'Both', 'fusion-builder' ),
							'original' => esc_html__( 'Original Only', 'fusion-builder' ),
							'sale'     => esc_html__( 'Sale Only', 'fusion-builder' ),
						],
					],
				],
			];

			$params['woo_rating'] = [
				'label'    => esc_html__( 'Product Rating', 'fusion-builder' ),
				'id'       => 'woo_rating',
				'group'    => esc_attr__( 'WooCommerce', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => '5',
				'callback' => [
					'function' => 'woo_get_rating',
					'ajax'     => true,
				],
				'fields'   => [
					'format' => [
						'heading'     => esc_html__( 'Format', 'fusion-builder' ),
						'description' => esc_html__( 'Format of rating to display.', 'fusion-builder' ),
						'param_name'  => 'format',
						'default'     => '',
						'type'        => 'select',
						'value'       => [
							''       => esc_html__( 'Average Rating', 'fusion-builder' ),
							'rating' => esc_html__( 'Rating Count', 'fusion-builder' ),
							'review' => esc_html__( 'Review Count', 'fusion-builder' ),
						],
					],
				],
			];

			$params['woo_sku'] = [
				'label'    => esc_html__( 'Product SKU', 'fusion-builder' ),
				'id'       => 'woo_sku',
				'group'    => esc_attr__( 'WooCommerce', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => '123',
				'callback' => [
					'function' => 'woo_get_sku',
					'ajax'     => true,
				],
			];

			$params['woo_stock'] = [
				'label'    => esc_html__( 'Product Stock', 'fusion-builder' ),
				'id'       => 'woo_stock',
				'group'    => esc_attr__( 'WooCommerce', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => '10',
				'callback' => [
					'function' => 'woo_get_stock',
					'ajax'     => true,
				],
			];
		}

		return $params;
	}

	/**
	 * Add page title bar fields.
	 *
	 * @since 2.2
	 * @access public
	 * @param array  $params    Params being used.
	 * @param int    $post_id   The target post id.
	 * @param string $post_type The current post type.
	 * @return array
	 */
	public function maybe_add_page_title_bar_fields( $params, $post_id, $post_type ) {
		$fb_template_type = false;
		$override         = Fusion_Template_Builder()->get_override( 'page_title_bar' );
		$is_builder       = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

		if ( 'fusion_tb_section' === $post_type ) {

			// Template category is used to filter components.
			$terms = get_the_terms( $post_id, 'fusion_tb_category' );

			if ( is_array( $terms ) ) {
				$fb_template_type = $terms[0]->name;
			}
		}

		if ( ( 'fusion_tb_section' === $post_type && 'page_title_bar' === $fb_template_type ) || ( ! is_admin() && $override ) || ( fusion_doing_ajax() && isset( $_POST['fusion_load_nonce'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$params['page_title_custom_text']      = [
				'label'    => esc_html__( 'Heading', 'fusion-builder' ),
				'id'       => 'page_title_custom_text',
				'group'    => esc_attr__( 'Page Title Bar', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => __( 'Your Heading Goes Here', 'fusion-builder' ),
				'callback' => [
					'function' => 'fusion_get_dynamic_heading',
					'ajax'     => false,
				],
				'fields'   => [
					'include_context' => [
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Include Context', 'fusion-builder' ),
						'description' => esc_html__( 'Whether to include title context, ie. Category: Avada.' ),
						'param_name'  => 'include_context',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
				],
			];
			$params['page_title_custom_subheader'] = [
				'label'    => esc_html__( 'Subheading', 'fusion-builder' ),
				'id'       => 'page_title_custom_subheader',
				'group'    => esc_attr__( 'Page Title Bar', 'fusion-builder' ),
				'options'  => $this->text_fields,
				'default'  => __( 'Your Subheading Goes Here', 'fusion-builder' ),
				'callback' => [
					'function' => 'fusion_get_dynamic_option',
					'ajax'     => false,
				],
			];
			$params['page_title_bg']               = [
				'label'    => esc_html__( 'Background Image', 'fusion-builder' ),
				'id'       => 'page_title_bg',
				'group'    => esc_attr__( 'Page Title Bar', 'fusion-builder' ),
				'options'  => $this->image_fields,
				'exclude'  => [ 'before', 'after' ],
				'callback' => [
					'function' => 'fusion_get_dynamic_option',
					'ajax'     => false,
				],
			];
			$params['page_title_bg_retina']        = [
				'label'    => esc_html__( 'Retina Background Image', 'fusion-builder' ),
				'id'       => 'page_title_bg_retina',
				'group'    => esc_attr__( 'Page Title Bar', 'fusion-builder' ),
				'options'  => $this->image_fields,
				'exclude'  => [ 'before', 'after' ],
				'callback' => [
					'function' => 'fusion_get_dynamic_option',
					'ajax'     => false,
				],
			];
		}

		return $params;
	}
}
