<?php
/**
 * Options handler.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Gets the options from separate files and unites them.
 */
class Avada_Options {

	/**
	 * An array of section names.
	 * We'll be using those to load all other files containing the options.
	 *
	 * @access public
	 * @var array
	 */
	public $section_names = [];

	/**
	 * An array of our sections.
	 *
	 * @access public
	 * @var array
	 */
	public $sections = [];

	/**
	 * An array of our fields.
	 *
	 * @access private
	 * @var array
	 */
	private static $fields;

	/**
	 * The class instance.
	 *
	 * @static
	 * @access private
	 * @var null|object
	 */
	private static $instance = null;

	/**
	 * The class constructor.
	 *
	 * @access public
	 */
	private function __construct() {

		Avada::$is_updating = ( $_GET && isset( $_GET['avada_update'] ) && '1' == $_GET['avada_update'] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.PHP.StrictComparisons.LooseComparison

		/**
		 * The array of sections by ID.
		 * These are used in the filenames AND the function-names.
		 */
		$this->section_names = [
			'layout',
			'responsive',
			'colors',
			'header',
			'menu',
			'logo',
			'page_title_bar',
			'breadcrumbs',
			'sliding_bar',
			'footer',
			'sidebars',
			'background',
			'typography',
			'blog',
			'portfolio',
			'social_media',
			'slideshows',
			'elastic_slider',
			'lightbox',
			'contact',
			'search',
			'privacy',
			'extra',
			'advanced',
			'performance',
			'bbpress',
			'woocommerce',
			'events_calendar',
			'custom_css',
		];

		// Include the section files.
		$this->include_files();

		// Set the $sections.
		$this->set_sections();

		// Set the $fields.
		$this->set_fields();

		add_filter( 'fusion_settings_all_fields', [ __CLASS__, 'get_option_fields' ] );

	}

	/**
	 * Returns a single instance of the object (singleton).
	 *
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Avada_Options();
		}
		return self::$instance;
	}

	/**
	 * Include required files.
	 *
	 * @access public
	 */
	public function include_files() {

		foreach ( $this->section_names as $section ) {
			include_once Avada::$template_dir_path . '/includes/options/' . $section . '.php';
		}

	}

	/**
	 * Set the sections.
	 *
	 * @access public
	 */
	public function set_sections() {

		$sections = [];
		foreach ( $this->section_names as $section ) {
			// Make sure the function exists before call_user_func().
			if ( ! function_exists( 'avada_options_section_' . $section ) ) {
				continue;
			}
			$sections = call_user_func( 'avada_options_section_' . $section, $sections );
		}

		$this->sections = apply_filters( 'avada_options_sections', $sections );

	}

	/**
	 * Get a flat array of our fields.
	 * This will contain simply the field IDs and nothing more than that.
	 * We'll be using this to check if a setting belongs to Avada or not.
	 *
	 * @access public
	 * @return array
	 */
	public function fields_array() {

		// Get the options object.
		$avada_new_options = Avada::get_options();
		$fields            = [];

		// Start parsing sections.
		foreach ( $avada_new_options->sections as $section ) {

			// Make sure we have defined fields for this section.
			// No need to proceed otherwise.
			if ( ! isset( $section['fields'] ) ) {
				continue;
			}

			// Start parsing the fields inside the section.
			foreach ( $section['fields'] as $field ) {

				// Make sure a field-type has been defined.
				if ( ! isset( $field['type'] ) ) {
					continue;
				}

				// For normal fields, we'll just add the field ID to our array.
				if ( ! in_array( $field['type'], [ 'sub-section', 'accordion' ] ) ) {
					if ( isset( $field['id'] ) ) {
						$fields[] = $field['id'];
					}
				} else {

					// For sub-sections & accordions we'll have to parse the sub-fields and add them to our array.
					if ( ! isset( $field['fields'] ) ) {
						continue;
					}
					foreach ( $field['fields'] as $sub_field ) {
						if ( isset( $sub_field['id'] ) ) {
							$fields[] = $sub_field['id'];
						}
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Sets the fields.
	 *
	 * @access public
	 */
	public function set_fields() {

		// Start parsing the sections.
		foreach ( $this->sections as $section ) {
			if ( ! isset( $section['fields'] ) ) {
				continue;
			}

			// Start parsing the fields.
			foreach ( $section['fields'] as $field ) {
				if ( ! isset( $field['id'] ) ) {
					continue;
				}

				// This is a sub-section or an accordion.
				if ( isset( $field['type'] ) && in_array( $field['type'], [ 'sub-section', 'accordion' ] ) ) {

					// Start parsing the fields inside the sub-section/accordion.
					foreach ( $field['fields'] as $sub_field ) {
						if ( ! isset( $sub_field['id'] ) ) {
							continue;
						}
						self::$fields[ $sub_field['id'] ] = $sub_field;
					}
				} else {

					// This is not a section, continue processing.
					self::$fields[ $field['id'] ] = $field;
				}
			}
		}
	}

	/**
	 * Returns the static $fields property.
	 *
	 * @static
	 * @access public
	 * @param array $fields The existing fields.
	 * @return array
	 */
	public static function get_option_fields( $fields = [] ) {

		if ( ! is_array( self::$fields ) || ! self::$fields || empty( self::$fields ) ) {
			$instance = self::get_instance();
			$instance->set_fields();
		}

		return array_replace_recursive( $fields, self::$fields );

	}
}
