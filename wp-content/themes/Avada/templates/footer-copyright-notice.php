<?php
/**
 * Footer copyright-text template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1.0
 */

?>
<div class="fusion-copyright-notice">
	<?php
	/**
	 * The 'footer_text' setting is not sanitized.
	 * In order to be able to take advantage of this,
	 * a user would have to gain access to the database
	 * in which case the footer text is the least of your worries.
	 */
	?>
	<div>
		<?php echo html_entity_decode( do_shortcode( Avada()->settings->get( 'footer_text' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
