<?php
/**
 * Plugin Name: Fusion Core
 * Plugin URI: https://theme-fusion.com
 * Description: ThemeFusion Core Plugin for ThemeFusion Themes
 * Version: 4.2.3
 * Author: ThemeFusion
 * Author URI: https://theme-fusion.com
 * Requires PHP: 5.6
 *
 * @package Fusion-Core
 * @subpackage Core
 */

// Plugin version.
if ( ! defined( 'FUSION_CORE_VERSION' ) ) {
	define( 'FUSION_CORE_VERSION', '4.2.3' );
}

// Minimum PHP version required.
if ( ! defined( 'FUSION_CORE_MIN_PHP_VER_REQUIRED' ) ) {
	define( 'FUSION_CORE_MIN_PHP_VER_REQUIRED', '5.6' );
}

// Minimum WP version required.
if ( ! defined( 'FUSION_CORE_MIN_WP_VER_REQUIRED' ) ) {
	define( 'FUSION_CORE_MIN_WP_VER_REQUIRED', '4.5' );
}

// Plugin Folder Path.
if ( ! defined( 'FUSION_CORE_PATH' ) ) {
	define( 'FUSION_CORE_PATH', wp_normalize_path( dirname( __FILE__ ) ) );
}

// Plugin Folder URL.
if ( ! defined( 'FUSION_CORE_URL' ) ) {
	define( 'FUSION_CORE_URL', plugin_dir_url( __FILE__ ) );
}

// The main plugin file path.
if ( ! defined( 'FUSION_CORE_MAIN_PLUGIN_FILE' ) ) {
	define( 'FUSION_CORE_MAIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Compatibility check.
 *
 * Check that the site meets the minimum requirements for the plugin before proceeding.
 *
 * @since 4.0
 */
if ( version_compare( $GLOBALS['wp_version'], FUSION_CORE_MIN_WP_VER_REQUIRED, '<' ) || version_compare( PHP_VERSION, FUSION_CORE_MIN_PHP_VER_REQUIRED, '<' ) ) {
	require_once FUSION_CORE_PATH . '/includes/bootstrap-compat.php';
	return;
}

/**
 * Bootstrap the plugin.
 *
 * @since 4.0
 */
require_once FUSION_CORE_PATH . '/includes/bootstrap.php';

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
