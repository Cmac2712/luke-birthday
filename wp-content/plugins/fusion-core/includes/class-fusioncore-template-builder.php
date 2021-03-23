<?php
/**
 * FusionCore Template Builder.
 *
 * @package Fusion-Core
 * @since 2.2
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * FusionCore Template Builder class.
 *
 * @since 2.2
 */
class FusionCore_Template_Builder {

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
	 * Class constructor.
	 *
	 * @since 2.12
	 * @access public
	 */
	public function __construct() {
		if ( ! apply_filters( 'fusion_load_template_builder', true ) ) {
			return;
		}

		add_action( 'fusion_builder_shortcodes_init', [ $this, 'init_shortcodes' ] );

		// Requirements for live editor.
		add_action( 'fusion_builder_load_templates', [ $this, 'load_component_templates' ] );
	}

	/**
	 * Init shortcode files specific to templates.
	 *
	 * @since 2.2
	 * @access public
	 */
	public function init_shortcodes() {
		if ( ! class_exists( 'Fusion_Component' ) ) {
			return;
		}
		include FUSION_CORE_PATH . '/shortcodes/components/project-details.php';
	}

	/**
	 * Load the templates for live editor..
	 *
	 * @since 2.2
	 * @access public
	 */
	public function load_component_templates() {
		include FUSION_CORE_PATH . '/shortcodes/previews/front-end/components/fusion-tb-project-details.php';
	}
}
new FusionCore_Template_Builder();
