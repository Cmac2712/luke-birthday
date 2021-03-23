<?php
/**
 * Plugin Name: Sidebar Generator
 * Plugin URI: http://www.getson.info
 * Description: This plugin generates as many sidebars as you need. Then allows you to place them on any page you wish. Version 1.1 now supports themes with multiple sidebars.
 * Version: 1.1.0
 * Author: Kyle Getson
 * Author URI: http://www.kylegetson.com
 * Copyright (C) 2009 Kyle Robert Getson
 *
 * @package Avada
 */

/*
Copyright (C) 2009 Kyle Robert Getson, kylegetson.com and getson.info

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * The Sidebar Generator.
 */
class Sidebar_Generator {

	/**
	 * The class constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'widgets_admin_page', [ $this, 'admin_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_print_scripts', [ $this, 'admin_print_scripts' ] );
	}

	/**
	 * Initializes the sidebar registration.
	 *
	 * @access public
	 */
	public function init() {

		if ( current_user_can( 'edit_theme_options' ) ) {
			add_action( 'wp_ajax_add_sidebar', [ $this, 'add_sidebar' ] );
			add_action( 'wp_ajax_remove_sidebar', [ $this, 'remove_sidebar' ] );
		}

		// Go through each sidebar and register it.
		$sidebars = self::get_sidebars();

		if ( is_array( $sidebars ) ) {
			foreach ( $sidebars as $sidebar ) {
				$sidebar_class = self::name_to_class( $sidebar );
				register_sidebar(
					[
						'name'          => $sidebar,
						'id'            => 'avada-custom-sidebar-' . strtolower( $sidebar_class ),
						'before_widget' => '<div id="%1$s" class="widget %2$s">',
						'after_widget'  => '</div>',
						'before_title'  => '<div class="heading"><h4 class="widget-title">',
						'after_title'   => '</h4></div>',
					]
				);
			}
		}

	}

	/**
	 * Enqueues the necessary scripts.
	 *
	 * @access public
	 */
	public function admin_enqueue_scripts() {

		wp_enqueue_script( [ 'sack' ] );

	}

	/**
	 * Prints some additional scripts.
	 *
	 * @access public
	 */
	public function admin_print_scripts() {

		$ajax_add_sidebar_nonce    = wp_create_nonce( 'add-sidebar' );
		$ajax_remove_sidebar_nonce = wp_create_nonce( 'remove-sidebar' );

		?>
		<script>
			function add_sidebar( sidebar_name ) {
				var mysack = new sack( "<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>" );

				mysack.execute = 1;
				mysack.method  = 'POST';
				mysack.setVar( 'action', 'add_sidebar' );
				mysack.setVar( 'security', '<?php echo $ajax_add_sidebar_nonce; // phpcs:ignore WordPress.Security.EscapeOutput ?>' );
				mysack.setVar( 'sidebar_name', sidebar_name );
				// mysack.encVar( 'cookie', document.cookie, false );
				mysack.onError = function() { alert( 'Ajax error. Cannot add sidebar' ) };
				mysack.runAJAX();
				return true;
			}

			function remove_sidebar( sidebar_name, num ) {
				var mysack = new sack("<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>" );

				mysack.execute = 1;
				mysack.method  = 'POST';
				mysack.setVar( 'action', 'remove_sidebar' );
				mysack.setVar( 'security', '<?php echo $ajax_remove_sidebar_nonce; // phpcs:ignore WordPress.Security.EscapeOutput ?>' );
				mysack.setVar( 'sidebar_name', sidebar_name );
				mysack.setVar( 'row_number', num );
				//mysack.encVar( 'cookie', document.cookie, false );
				mysack.onError = function() { alert( 'Ajax error. Cannot remove sidebar' ) };
				mysack.runAJAX();
				// alert( 'hi!:::'+sidebar_name );
				return true;
			}
		</script>
		<?php

	}

	/**
	 * Adds the sidebar.
	 *
	 * @access public
	 */
	public function add_sidebar() {

		check_ajax_referer( 'add-sidebar', 'security' );

		$sidebars = self::get_sidebars();
		$name     = isset( $_POST['sidebar_name'] ) ? str_replace( [ "\n", "\r", "\t" ], '', sanitize_text_field( wp_unslash( $_POST['sidebar_name'] ) ) ) : '';
		$counter  = ( is_array( $sidebars ) && ! empty( $sidebars ) ) ? count( $sidebars ) + 1 : 1;
		$id       = self::name_to_class( $name );

		if ( isset( $sidebars[ $id ] ) ) {
			die( "alert('" . esc_html__( 'Widget Area already exists, please use a different name.', 'Avada' ) . "')" );
		}

		$sidebars[ $id ] = $name;
		self::update_sidebars( $sidebars );

		$id = 'fusion-' . strtolower( self::name_to_class( $name ) );
		$js = "
		var tbl = document.getElementById('sbg_table');
		var lastRow = tbl.rows.length;
		// if there's no header row in the table, then iteration = lastRow + 1
		var iteration = lastRow;
		var row = tbl.insertRow(lastRow);

		// left cell
		var cellLeft = row.insertCell(0);
		var textNode = document.createTextNode('$name');
		cellLeft.appendChild(textNode);

		//middle cell
		var cellLeft = row.insertCell(1);
		var textNode = document.createTextNode('$id');
		cellLeft.appendChild(textNode);

		//var cellLeft = row.insertCell(2);
		//var textNode = document.createTextNode('[<a href=\'javascript:void(0);\' onclick=\'return remove_sidebar_link($name);\'>Remove</a>]');
		//cellLeft.appendChild(textNode)

		var cellLeft = row.insertCell(2);
		removeLink = document.createElement('a');
		linkText = document.createTextNode('remove');
		removeLink.setAttribute('onclick', 'remove_sidebar_link(\'$name\', $counter)');
		removeLink.setAttribute('href', 'javascript:void(0)');

		removeLink.appendChild(linkText);
		cellLeft.appendChild(removeLink);

		var tbl = document.getElementById( 'no-widget-sections' );
		if ( tbl !== null ) {
			tbl.remove();
		}
		location.reload();
		";

		die( "$js" ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Removes a sidebar.
	 *
	 * @access public
	 */
	public function remove_sidebar() {

		check_ajax_referer( 'remove-sidebar', 'security' );

		$sidebars = self::get_sidebars();
		$id       = isset( $_POST['sidebar_name'] ) ? strtolower( str_replace( [ "\n", "\r", "\t" ], '', sanitize_text_field( wp_unslash( $_POST['sidebar_name'] ) ) ) ) : false;
		$counter  = '1';

		if ( ! $id ) {
			return;
		}

		if ( is_array( $sidebars ) && ! empty( $sidebars ) ) {
			$sidebars = array_change_key_case( $sidebars, CASE_LOWER );
			$counter  = count( $sidebars );
		}
		$no_widget_text = esc_html__( 'No Widget area defined.', 'Avada' );

		if ( ! isset( $sidebars[ $id ] ) ) {
			die( 'alert("' . esc_html__( 'Widget area does not exist.', 'Avada' ) . '")' );
		}
		$row_number = ( isset( $_POST['row_number'] ) ) ? sanitize_text_field( wp_unslash( $_POST['row_number'] ) ) : '0';
		unset( $sidebars[ $id ] );
		self::update_sidebars( $sidebars );
		$js = "
			var tbl = document.getElementById('sbg_table');

			if ( $counter - 1  == '0' ) {
				var last_row = tbl.rows.length;
				var row = tbl.insertRow( last_row );
				var cell = row.insertCell( 0 );
				var text_node = document.createTextNode( '$no_widget_text' );
				row.setAttribute( 'id', 'no-widget-sections' );
				cell.appendChild( text_node );
				cell.colSpan = 3;
			}
			tbl.deleteRow( $row_number );
			location.reload();
		";
		die( $js ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Adds the admin page.
	 *
	 * @access public
	 */
	public function admin_page() {
		?>

		<script>
		function remove_sidebar_link( name, num ) {
			answer = confirm( '<?php esc_attr_e( 'Are you sure you want to remove', 'Avada' ); ?> ' + name + '?\n<?php esc_attr_e( 'This will remove any widgets you have assigned to this widget area.', 'Avada' ); ?>' );
			if ( answer ) {
				remove_sidebar( name, num );
			} else {
				return false;
			}
		}
		function add_sidebar_link() {
			var sidebar_name = prompt( '<?php esc_html_e( 'Widget Area Name:', 'Avada' ); ?>', '' );
			if ( sidebar_name === null || sidebar_name == '' ) {
				return;
			}

			add_sidebar( sidebar_name );
		}
		</script>

		<div class="postbox" style="max-width:calc(42% + 900px + 1.16%);">
			<h2 class="hndle ui-sortable-handle" style="padding: 15px 12px; margin: 0;">
				<span><?php esc_attr_e( 'Widget Areas', 'Avada' ); ?></span>
			</h2>
			<div class="inside" style="margin-bottom: 0;">
				<table class="widefat page" id="sbg_table">
					<tr>
						<th><?php esc_attr_e( 'Widget Area Name', 'Avada' ); ?></th>
						<th><?php esc_attr_e( 'CSS Class', 'Avada' ); ?></th>
						<th><?php esc_attr_e( 'Remove', 'Avada' ); ?></th>
					</tr>
					<?php $sidebars = self::get_sidebars(); ?>
					<?php if ( is_array( $sidebars ) && ! empty( $sidebars ) ) : ?>
						<?php $cnt = 0; ?>
						<?php foreach ( $sidebars as $sidebar ) : ?>
							<?php $alt = ( 0 === $cnt % 2 ) ? 'alternate' : ''; ?>
							<tr class="<?php echo esc_attr( $alt ); ?>">
								<td><?php echo esc_html( $sidebar ); ?></td>
								<td><?php echo 'fusion-' . strtolower( self::name_to_class( $sidebar ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td><a href="javascript:void(0);" onclick="return remove_sidebar_link('<?php echo self::name_to_class( $sidebar ); // phpcs:ignore WordPress.Security.EscapeOutput ?>',<?php echo intval( $cnt + 1 ); ?>);" title="<?php esc_attr_e( 'Remove This Widget Area', 'Avada' ); ?>"><?php esc_html_e( 'remove', 'Avada' ); ?></a></td>
							</tr>
							<?php $cnt++; ?>
						<?php endforeach; ?>
					<?php else : ?>
						<tr id="no-widget-sections">
							<td colspan="3"><?php esc_html_e( 'No Widget Areas defined.', 'Avada' ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
				<p class="add_sidebar"><a href="javascript:void(0);" onclick="return add_sidebar_link()" title="<?php esc_attr_e( 'Add New Widget Area', 'Avada' ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Widget Area', 'Avada' ); ?></a></p>
			</div>
		</div>
		<?php

	}

	/**
	 * Called by the action get_sidebar. this is what places this into the theme.
	 *
	 * @static
	 * @access public
	 * @param string $name The sidebat name.
	 */
	public static function get_sidebar( $name = '0' ) {

		if ( ! is_singular() && '0' == $name ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$name = 'avada-blog-sidebar';
		}

		if ( 'none' === strtolower( $name ) || __( 'None', 'Avada' ) === $name || empty( $name ) ) {
			return;
		}

		dynamic_sidebar( $name );
	}

	/**
	 * Called by the action get_sidebar. this is what places this into the theme.
	 *
	 * @static
	 * @access public
	 * @param string $name The sidebar name.
	 */
	public static function get_sidebar_2( $name = '0' ) {
		self::get_sidebar( $name );
	}

	/**
	 * Replaces array of sidebar names.
	 *
	 * @static
	 * @access public
	 * @param array $sidebar_array The sidebar array.
	 */
	public static function update_sidebars( $sidebar_array ) {

		update_option( 'sbg_sidebars', $sidebar_array );

	}

	/**
	 * Gets the generated sidebars.
	 *
	 * @static
	 * @access public
	 */
	public static function get_sidebars() {

		$sidebars = get_option( 'sbg_sidebars', [] );

		// Check needed in case empty string (as wrongly converted false) is stored in var.
		if ( empty( $sidebars ) || ! is_array( $sidebars ) ) {
			$sidebars = [];
		}

		return $sidebars;

	}

	/**
	 * Converts a sidebar name to a class.
	 *
	 * @static
	 * @access public
	 * @param string $name The sidebar name.
	 * @return string
	 */
	public static function name_to_class( $name ) {

		$class = str_replace( [ ' ', ',', '.', '"', "'", '/', '\\', '+', '=', ')', '(', '*', '&', '^', '%', '$', '#', '@', '!', '~', '`', '<', '>', '?', '[', ']', '{', '}', '|', ':' ], '', $name );
		return strtolower( sanitize_html_class( $class ) );

	}
}
$sbg = new Sidebar_Generator();

/**
 * Gets a generated sidebar.
 *
 * @param string $name The sidebar name.
 * @return true
 */
function generated_dynamic_sidebar( $name = '0' ) {

	Sidebar_Generator::get_sidebar( $name );
	return true;

}

/**
 * Gets a generated sidebar.
 *
 * @param string $name The sidebar name.
 * @return true
 */
function generated_dynamic_sidebar_2( $name = '0' ) {

	Sidebar_Generator::get_sidebar_2( $name );
	return true;

}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
