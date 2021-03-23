<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

/**
 * Row shortcode.
 *
 * @param array  $atts    The attributes array.
 * @param string $content The content.
 * @return string
 */
function fusion_builder_row_inner( $atts, $content = '' ) {
	extract(
		shortcode_atts(
			[
				'id'    => '',
				'class' => '',
			],
			$atts,
			'fusion_builder_row_inner'
		)
	);

	$id      = ( '' !== $id ) ? ' id="' . esc_attr( $id ) . '"' : '';
	$class_2 = ( '' !== $class ) ? ' ' . esc_attr( $class ) : '';

	$html = '<div' . $id . ' class="fusion-builder-row fusion-builder-row-inner fusion-row ' . esc_attr( $class ) . $class_2 . '">' . do_shortcode( fusion_builder_fix_shortcodes( $content ) ) . '</div>';

	return apply_filters( 'fusion_element_row_inner_content', $html, $atts );
}
add_shortcode( 'fusion_builder_row_inner', 'fusion_builder_row_inner' );


/**
 * Map Row shortcode to Fusion Builder
 */
function fusion_element_row_inner() {
	fusion_builder_map(
		[
			'name'              => esc_attr__( 'Nested Columns', 'fusion-builder' ),
			'shortcode'         => 'fusion_builder_row_inner',
			'hide_from_builder' => true,
		]
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_row_inner' );
