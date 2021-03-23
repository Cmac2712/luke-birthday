<?php
/**
 * Sermon manager mods.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Various helper methods for Sermon Manager plugin in Avada
 */
class Avada_Sermon_Manager {

	/**
	 * Custom Excerpt function for Sermon Manager.
	 *
	 * @access public
	 * @since 5.1.0
	 * @param bool $archive True if an archive, else false.
	 * @return string
	 */
	public function get_sermon_content( $archive = false ) {
		global $post;

		$sermon_content          = '';
		$sermon_manager_template = '';

		if ( class_exists( 'Sermon_Manager_Template_Tags' ) ) {
			$sermon_manager_template = new Sermon_Manager_Template_Tags();
		}

		// Get the date.
		ob_start();
		if ( is_object( $sermon_manager_template ) ) {
			$sermon_manager_template->wpfc_sermon_date( get_option( 'date_format' ), '<span class="sermon_date">', '</span> ' );
		} else {
			wpfc_sermon_date( get_option( 'date_format' ), '<span class="sermon_date">', '</span> ' );
		}
		$date = ob_get_clean();

		// Print the date.
		ob_start(); ?>
		<p>
			<?php /* translators: the Date. */ ?>
			<?php printf( esc_attr__( 'Date: %s', 'Avada' ), $date ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo the_terms( $post->ID, 'wpfc_service_type', ' <span class="service_type">(', ' ', ')</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>
		<?php $sermon_content .= ob_get_clean(); ?>

		<?php ob_start(); ?>
		<p>
			<?php if ( is_object( $sermon_manager_template ) ) : ?>
				<?php $sermon_manager_template->wpfc_sermon_meta( 'bible_passage', '<span class="bible_passage">' . esc_attr__( 'Bible Text: ', 'Avada' ), '</span> | ' ); ?>
			<?php else : ?>
				<?php wpfc_sermon_meta( 'bible_passage', '<span class="bible_passage">' . esc_attr__( 'Bible Text: ', 'Avada' ), '</span> | ' ); ?>
			<?php endif; ?>
			<?php echo the_terms( $post->ID, 'wpfc_preacher', '<span class="preacher_name">', ', ', '</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo the_terms( $post->ID, 'wpfc_sermon_series', '<p><span class="sermon_series">' . esc_attr__( 'Series: ', 'Avada' ), ' ', '</span></p>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>

		<?php if ( $archive ) : ?>
			<?php $sermonoptions = get_option( 'wpfc_options' ); ?>
			<?php if ( isset( $sermonoptions['archive_player'] ) ) : ?>
				<div class="wpfc_sermon cf">
					<?php
					if ( is_object( $sermon_manager_template ) ) {
						echo $sermon_manager_template->wpfc_sermon_media(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						wpfc_sermon_files();
					}
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! $archive ) : ?>
			<?php
			if ( is_object( $sermon_manager_template ) ) {
				echo $sermon_manager_template->wpfc_sermon_media(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$sermon_manager_template->wpfc_sermon_description();
				echo $sermon_manager_template->wpfc_sermon_attachments(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo wpfc_sermon_media(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wpfc_sermon_description();
				echo wpfc_sermon_attachments(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<?php echo the_terms( $post->ID, 'wpfc_sermon_topics', '<p class="sermon_topics">' . esc_attr__( 'Topics: ', 'sermon-manager' ), ',', '', '</p>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<?php $sermon_content .= ob_get_clean(); ?>

		<?php if ( $archive ) : ?>
			<?php ob_start(); ?>
			<?php if ( is_object( $sermon_manager_template ) ) : ?>
				<?php $sermon_manager_template->wpfc_sermon_description(); ?>
			<?php else : ?>
				<?php wpfc_sermon_description(); ?>
			<?php endif; ?>
			<?php $description = ob_get_clean(); ?>
			<?php $excerpt_length = fusion_library()->get_option( 'excerpt_length_blog' ); ?>

			<?php $sermon_content .= Avada()->blog->get_content_stripped_and_excerpted( $excerpt_length, $description ); ?>
		<?php endif; ?>
		<?php

		return $sermon_content;
	}

	/**
	 * Render sermon manager archives content.
	 *
	 * @access  public
	 * @since 5.1.0
	 */
	public function render_wpfc_sorting() {
		if ( class_exists( 'Sermon_Manager_Template_Tags' ) ) {
			$sermon_manager_template = new Sermon_Manager_Template_Tags();
			echo $sermon_manager_template->render_wpfc_sorting(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			render_wpfc_sorting();
		}
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
