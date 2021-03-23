<?php
/**
 * Portfolio Template.
 *
 * @package Fusion-Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
if ( ! class_exists( 'Avada' ) ) {
	exit( 'This feature requires the Avada theme.' );
}
?>
<?php get_header(); ?>
	<div id="content" <?php Avada()->layout->add_class( 'content_class' ); ?> <?php Avada()->layout->add_style( 'content_style' ); ?>>
		<?php require 'portfolio-archive-layout.php'; ?>
	</div>
	<?php do_action( 'avada_after_content' ); ?>
<?php
get_footer();

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
