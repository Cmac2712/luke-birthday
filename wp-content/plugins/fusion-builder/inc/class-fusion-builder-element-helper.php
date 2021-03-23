<?php
/**
 * Fusion Builder Element Helper class.
 *
 * @package Fusion-Builder
 * @since 2.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Fusion Builder Element Helper class.
 *
 * @since 2.1
 */
class Fusion_Builder_Element_Helper {

	/**
	 * Class constructor.
	 *
	 * @since 2.1
	 * @access public
	 */
	public function __construct() {

	}

	/**
	 * Replace placeholders with params.
	 *
	 * @since 2.1
	 * @access public
	 * @param array  $params Element params.
	 * @param string $shortcode Shortcode handle.
	 * @return array
	 */
	public static function placeholders_to_params( $params, $shortcode ) {

		// placeholder => callback.
		$placeholders_to_params = [
			'fusion_animation_placeholder'           => 'Fusion_Builder_Animation_Helper::get_params',
			'fusion_filter_placeholder'              => 'Fusion_Builder_Filter_Helper::get_params',
			'fusion_border_radius_placeholder'       => 'Fusion_Builder_Border_Radius_Helper::get_params',
			'fusion_gradient_placeholder'            => 'Fusion_Builder_Gradient_Helper::get_params',
			'fusion_margin_placeholder'              => 'Fusion_Builder_Margin_Helper::get_params',
			'fusion_margin_mobile_placeholder'       => 'Fusion_Builder_Margin_Helper::get_params',
			'fusion_box_shadow_placeholder'          => 'Fusion_Builder_Box_Shadow_Helper::get_params',
			'fusion_box_shadow_no_inner_placeholder' => 'Fusion_Builder_Box_Shadow_Helper::get_no_inner_params',
		];

		foreach ( $placeholders_to_params as $placeholder => $param_callback ) {

			if ( isset( $params[ $placeholder ] ) ) {

				$placeholder_args              = is_array( $params[ $placeholder ] ) ? $params[ $placeholder ] : [ $params[ $placeholder ] ];
				$placeholder_args['shortcode'] = $shortcode;

				// Get placeholder element position.
				$params_keys = array_keys( $params );
				$position    = array_search( $placeholder, $params_keys, true );

				// Unset placeholder element as we don't need it anymore.
				unset( $params[ $placeholder ] );

				// Insert params.
				$param_callback = false !== strpos( $param_callback, '::' ) ? $param_callback : 'Fusion_Builder_Element_Helper::' . $param_callback;
				if ( is_callable( $param_callback ) ) {
					array_splice( $params, $position, 0, call_user_func_array( $param_callback, [ $placeholder_args ] ) );
				}
			}
		}

		return $params;
	}

	/**
	 * Get font family attributes.
	 *
	 * @since 2.2
	 * @access public
	 * @param array  $params Element params.
	 * @param string $param Font family param name.
	 * @return array
	 */
	public static function get_font_styling( $params, $param = 'font_family' ) {
		$style = '';
		if ( '' !== $params[ 'fusion_font_family_' . $param ] ) {
			$style .= 'font-family:"' . $params[ 'fusion_font_family_' . $param ] . '";';
		}

		if ( '' !== $params[ 'fusion_font_variant_' . $param ] ) {
			$weight = str_replace( 'italic', '', $params[ 'fusion_font_variant_' . $param ] );
			if ( $weight !== $params[ 'fusion_font_variant_' . $param ] ) {
				$style .= 'font-style: italic;';
			}
			if ( '' !== $weight ) {
				$style .= 'font-weight:' . $weight . ';';
			}
		}
		return $style;
	}

}

// Add replacement filter.
add_filter( 'fusion_builder_element_params', 'Fusion_Builder_Element_Helper::placeholders_to_params', 10, 2 );
