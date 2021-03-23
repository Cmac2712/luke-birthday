<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

/**
 * Row shortcode
 *
 * @param array  $atts    The attributes array.
 * @param string $content The content.
 * @return string
 */
function fusion_builder_row( $atts, $content = '' ) {
	extract(
		shortcode_atts(
			[
				'id'    => '',
				'class' => '',
			],
			$atts,
			'fusion_builder_row'
		)
	);

	$html = '<div' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' class="fusion-builder-row fusion-row ' . esc_attr( $class ) . ( '' !== $class ? esc_attr( $class ) : '' ) . '">' . do_shortcode( fusion_builder_fix_shortcodes( $content ) ) . '</div>';

	return apply_filters( 'fusion_element_row_content', $html, $atts );
}
add_shortcode( 'fusion_builder_row', 'fusion_builder_row' );


/**
 * Map Row shortcode to Fusion Builder
 */
function fusion_element_row() {
	fusion_builder_map(
		[
			'name'              => esc_attr__( 'Row', 'fusion-builder' ),
			'shortcode'         => 'fusion_builder_row',
			'hide_from_builder' => true,
		]
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_row' );
