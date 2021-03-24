<?php
/**
 * The footer template.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>
						<?php do_action( 'avada_after_main_content' ); ?>

					</div>  <!-- fusion-row -->
				</main>  <!-- #main -->
				<?php do_action( 'avada_after_main_container' ); ?>

				<?php
				/**
				 * Get the correct page ID.
				 */
				$c_page_id = Avada()->fusion_library->get_page_id();
				?>

				<?php
				/**
				 * Only include the footer.
				 */
				?>
				<?php if ( ! is_page_template( 'blank.php' ) ) : ?>

					<?php 
					if ( has_action( 'avada_render_footer' ) ) {
						do_action( 'avada_render_footer' );
					} else {
						Avada()->template->render_footer();
					} 
					?>

					<div class="fusion-sliding-bar-wrapper">
						<?php
						/**
						 * Add sliding bar.
						 */
						if ( Avada()->settings->get( 'slidingbar_widgets' ) ) {
							get_template_part( 'sliding_bar' );
						}
						?>
					</div>

					<?php do_action( 'avada_before_wrapper_container_close' ); ?>
				<?php endif; // End is not blank page check. ?>
			</div> <!-- wrapper -->
		</div> <!-- #boxed-wrapper -->
		<div class="fusion-top-frame"></div>
		<div class="fusion-bottom-frame"></div>
		<div class="fusion-boxed-shadow"></div>
		<a class="fusion-one-page-text-link fusion-page-load-link"></a>

		<div id="video-modal"></div>

		<div class="avada-footer-scripts">
			<?php wp_footer(); ?>
		</div>

		<script>
jQuery(function ($) {
	const links = $('.fusion-layout-column a');
	
	const openInModal = e => {
		const target = $(e.target);
		const parent = target.parents('.fusion-layout-column'); 
		const vid = parent.find('video');
		const modal = $('#video-modal');

		modal.html(vid.clone());

		 modal.dialog({
			modal: true,
			width: 1200 
		});	

		// Close when clicke outside
		$('.ui-widget-overlay').on('click', () => {
			$('#video-modal').dialog('close');	
		});
		
		e.preventDefault();
	}
	

	links.on('click', e => openInModal(e));
});
		</script>


	</body>
</html>
