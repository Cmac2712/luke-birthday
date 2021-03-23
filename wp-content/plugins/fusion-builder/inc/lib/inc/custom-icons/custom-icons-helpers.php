<?php
/**
 * Custom Icons helper functions.3
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion-Library
 * @since      2.2
 */

/**
 * Get Icon Set CSS URL.
 *
 * @since 6.2
 * @param int $post_id Post ID.
 * @return string URL.
 */
function fusion_get_custom_icons_css_url( $post_id = 0 ) {

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$icon_set = fusion_data()->post_meta( $post_id )->get( 'custom_icon_set' );

	return ! empty( $icon_set['icon_set_dir_name'] ) ? FUSION_ICONS_BASE_URL . $icon_set['icon_set_dir_name'] . '/style.css' : '';
}

/**
 * WIP.
 *
 * @since 6.2
 * @param array $args Optional WP_Query arguments.
 * @return array Icon array.
 */
function fusion_get_custom_icons_array( $args = [] ) {

	$upload_dir         = wp_upload_dir();
	$icons_base_dir_url = trailingslashit( $upload_dir['baseurl'] ) . 'fusion-icons/';

	$custom_icons = [];
	$default_args = [
		'post_type'      => 'fusion_icons',
		'post_status'    => 'publish',
		'posts_per_page' => -1, // phpcs:ignore WPThemeReview.CoreFunctionality.PostsPerPage.posts_per_page_posts_per_page
	];

	$args = wp_parse_args(
		$args,
		$default_args
	);

	$posts = get_posts( $args );
	foreach ( $posts as $post ) {
		$meta = fusion_data()->post_meta( $post->ID )->get( 'custom_icon_set' );

		if ( '' !== $meta ) {
			$custom_icons[ $post->post_name ]            = $meta;
			$custom_icons[ $post->post_name ]['name']    = get_the_title( $post->ID );
			$custom_icons[ $post->post_name ]['css_url'] = fusion_get_custom_icons_css_url( $post->ID );
		}   
	}

	return apply_filters( 'fusion_custom_icons', $custom_icons );
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
