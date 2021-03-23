<?php
/**
 * Fusion Builder Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion Builder
 * @subpackage Core
 * @since      6.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Advanced settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function fusion_builder_options_section_performance( $sections ) {
	$settings = get_option( Fusion_Settings::get_option_name() );

	// Is the JS compiler enabled?
	$is_http2 = Fusion_Dynamic_JS::is_http2();
	if ( $is_http2 ) {
		$js_compiler_enabled = ( isset( $settings['js_compiler'] ) && ( '1' === $settings['js_compiler'] || 1 === $settings['js_compiler'] || true === $settings['js_compiler'] ) );
	} else {
		$js_compiler_enabled = ( ! isset( $settings['js_compiler'] ) || ( '1' === $settings['js_compiler'] || 1 === $settings['js_compiler'] || true === $settings['js_compiler'] ) );
	}

	$sections['performance'] = [
		'label'    => esc_html__( 'Performance', 'fusion-builder' ),
		'id'       => 'heading_performance',
		'is_panel' => true,
		'priority' => 25,
		'icon'     => 'el-icon-time-alt',
		'alt_icon' => 'fusiona-check',
		'fields'   => [
			'dynamic_compiler_section' => [
				'label' => esc_html__( 'Dynamic CSS & JS', 'fusion-builder' ),
				'id'    => 'dynamic_compiler_section',
				'icon'  => true,
				'type'  => 'info',
			],
			'css_cache_method'         => [
				'label'       => esc_html__( 'CSS Compiling method', 'fusion-builder' ),
				'description' => esc_html__( 'Select "File" mode to compile the dynamic CSS to files (a separate file will be created for each of your pages & posts inside of the uploads/fusion-styles folder), "Database" mode to cache the CSS in your database, or select "Disabled" to disable.', 'fusion-builder' ),
				'id'          => 'css_cache_method',
				'default'     => 'file',
				'type'        => 'radio-buttonset',
				'choices'     => [
					'file' => esc_html__( 'File', 'fusion-builder' ),
					'db'   => esc_html__( 'Database', 'fusion-builder' ),
					'off'  => esc_html__( 'Disabled', 'fusion-builder' ),
				],
				'transport'   => 'postMessage', // No need to refresh the page.
			],
			'media_queries_async'      => [
				'label'       => esc_html__( 'Load Media-Queries Files Asynchronously', 'fusion-builder' ),
				'description' => esc_html__( 'When enabled, the CSS media-queries will be enqueued separately and then loaded asynchronously, improving performance on mobile and desktop.', 'fusion-builder' ),
				'id'          => 'media_queries_async',
				'default'     => '0',
				'type'        => 'switch',
				'transport'   => 'postMessage', // No need to refresh the page.
			],
			'css_vars'                 => [
				'label'       => esc_html__( 'Enable CSS Variables', 'Avada' ),
				'description' => sprintf(
					/* translators: Link properties (URL, target, ref). */
					__( 'Enable this option to use CSS Variables (Custom Properties). Makes compilations faster and lighter, but is <a %s>not compatible with older IE browsers</a>.', 'Avada' ),
					'href="https://caniuse.com/#feat=css-variables" target="_blank" rel="external nofollow noopener noreferrer"'
				),
				'id'          => 'css_vars',
				'default'     => '0',
				'type'        => 'switch',
				'transport'   => 'postMessage', // No need to refresh the page.
			],
			'cache_server_ip'          => [
				'label'       => esc_html__( 'Cache Server IP', 'fusion-builder' ),
				'description' => esc_html__( 'For unique cases where you are using cloud flare and a cache server, ex: varnish cache. Enter your cache server IP to clear the theme options dynamic CSS cache. Consult with your server admin for help.', 'fusion-builder' ),
				'id'          => 'cache_server_ip',
				'default'     => '',
				'type'        => 'text',
				'transport'   => 'postMessage', // No need to refresh the page.
			],
			'js_compiler_note'         => ( apply_filters( 'fusion_compiler_js_file_is_readable', ( get_transient( 'fusion_dynamic_js_readable' ) || ! $js_compiler_enabled ) ) ) ? [] : [
				'label'       => '',
				'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> JS Compiler is disabled. File does not exist or access is restricted.', 'Avada' ) . '</div>',
				'id'          => 'js_compiler_note',
				'type'        => 'custom',
			],
			'js_compiler'              => [
				'label'       => esc_html__( 'Enable JS Compiler', 'fusion-builder' ),
				'description' => ( $is_http2 ) ? esc_html__( 'We have detected that your server supports HTTP/2. We recommend you leave the compiler disabled as that will improve performance of your site by allowing multiple JS files to be downloaded simultaneously.', 'Avada' ) : esc_html__( 'By default all the javascript files are combined. Disabling the JS compiler will load non-combined javascript files. This will have an impact on the performance of your site.', 'fusion-builder' ),
				'id'          => 'js_compiler',
				'default'     => ( $is_http2 ) ? '0' : '1',
				'type'        => 'switch',
				'transport'   => 'postMessage', // No need to refresh the page.
			],
			'reset_caches_button'      => [
				'label'         => esc_html__( 'Reset Fusion Caches', 'fusion-builder' ),
				/* translators: %1$s: <code>uploads/fusion-styles</code>. %2$s: <code>uploads/fusion-scripts</code>. */
				'description'   => ( is_multisite() && is_main_site() ) ? sprintf( esc_html__( 'Resets all Dynamic CSS & Dynamic JS, cleans-up the database and deletes the %1$s and %2$s folders. When resetting the caches on the main site of a multisite installation, caches for all sub-sites will be reset. IMPORTANT NOTE: On large multisite installations with a low PHP timeout setting, bulk-resetting the caches may timeout.', 'fusion-builder' ), '<code>uploads/fusion-styles</code>', '<code>uploads/fusion-scripts</code>' ) : sprintf( esc_html__( 'Resets all Dynamic CSS & Dynamic JS, cleans-up the database and deletes the %1$s and %2$s folders.', 'fusion-builder' ), '<code>uploads/fusion-styles</code>', '<code>uploads/fusion-scripts</code>' ),
				'id'            => 'reset_caches_button',
				'default'       => '',
				'type'          => 'raw',
				'content'       => '<a class="button button-secondary" href="#" onclick="fusionResetCaches(event);" target="_self" >' . esc_html__( 'Reset Fusion Caches', 'Avada' ) . '</a><span class="spinner fusion-spinner"></span>',
				'full_width'    => false,
				'transport'     => 'postMessage', // No need to refresh the page.
				'hide_on_front' => true,
			],
		],
	];

	return $sections;

}
