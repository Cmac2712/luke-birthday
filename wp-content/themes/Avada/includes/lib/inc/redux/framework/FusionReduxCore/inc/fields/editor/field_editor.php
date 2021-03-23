<?php

/**
 * FusionRedux Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * FusionRedux Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with FusionRedux Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     FusionReduxFramework
 * @subpackage  Field_Editor
 * @author      Daniel J Griffiths (Ghost1227)
 * @author      Dovy Paukstys
 * @author      Kevin Provance (kprovance)
 * @version     3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't duplicate me!
if ( ! class_exists( 'FusionReduxFramework_editor' ) ) {

	/**
	 * Main FusionReduxFramework_editor class
	 *
	 * @since       1.0.0
	 */
	class FusionReduxFramework_editor {

		/**
		 * Field Constructor.
		 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
		 *
		 * @since       1.0.0
		 * @access      public
		 * @return      void
		 */
		function __construct( $field = array(), $value = '', $parent ) {
			$this->parent = $parent;
			$this->field  = $field;
			$this->value  = $value;
		}

		/**
		 * Field Render Function.
		 * Takes the vars and outputs the HTML for the field in the settings
		 *
		 * @since       1.0.0
		 * @access      public
		 * @return      void
		 */
		public function render() {

			if ( ! isset( $this->field['args'] ) ) {
				$this->field['args'] = array();
			}

			$this->field['args']['onchange_callback'] = "alert('here')";

			// Setup up default args
			$defaults = array(
				'textarea_name' => $this->field['name'] . $this->field['name_suffix'],
				'editor_class'  => $this->field['class'],
				'textarea_rows' => 10, //Wordpress default
				'teeny'         => true,
			);

			if ( isset( $this->field['editor_options'] ) && empty( $this->field['args'] ) ) {
				$this->field['args'] = $this->field['editor_options'];
				unset( $this->field['editor_options'] );
			}

			$this->field['args'] = wp_parse_args( $this->field['args'], $defaults );

			wp_editor( $this->value, $this->field['id'], $this->field['args'] );
		}


		/**
		 * Enqueue Function.
		 * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
		 *
		 * @since       1.0.0
		 * @access      public
		 * @return      void
		 */
		public function enqueue() {
			if ($this->parent->args['dev_mode']) {
				wp_enqueue_style(
					'fusionredux-field-editor-css',
					FusionReduxFramework::$_url . 'inc/fields/editor/field_editor.css',
					array(),
					time(),
					'all'
				);
			}

			wp_enqueue_script(
				'fusionredux-field-editor-js',
				FusionReduxFramework::$_url . 'inc/fields/editor/field_editor' . FusionRedux_Functions::isMin() . '.js',
				array( 'jquery', 'fusionredux-js' ),
				time(),
				true
			);
		}
	}
}
