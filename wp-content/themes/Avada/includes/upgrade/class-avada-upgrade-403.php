<?php
/**
 * Upgrades Handler.
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
 * Handle migrations for Avada 4.0.3.
 *
 * @since 5.0.0
 */
class Avada_Upgrade_403 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 5.0.0
	 * @var string
	 */
	protected $version = '4.0.3';

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 5.0.0
	 */
	protected function migration_process() {

		$options = get_option( $this->option_name, [] );

		// Update the post title option.
		$post_title = $options['blog_post_title'];

		if ( '1' === $post_title ) {
			$new_post_title = 'below';
		} else {
			$new_post_title = 'disabled';
		}

		$options['blog_post_title'] = $new_post_title;

		update_option( $this->option_name, $options );

	}
}
