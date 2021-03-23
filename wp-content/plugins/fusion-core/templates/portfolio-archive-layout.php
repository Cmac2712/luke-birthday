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
<?php
global $wp_query, $fusion_library;

// Get main settings and mofify as needed.
$portfolio_layout_setting      = Avada()->settings->get( 'portfolio_archive_layout' );
$portfolio_one_column_text_pos = Avada()->settings->get( 'portfolio_archive_one_column_text_position' );
$portfolio_text_layout         = Avada()->settings->get( 'portfolio_archive_text_layout' );
$portfolio_content_length      = Avada()->settings->get( 'portfolio_archive_content_length' );
$portfolio_text_alignment      = Avada()->settings->get( 'portfolio_archive_text_alignment' );
$portfolio_columns_int         = Avada()->settings->get( 'portfolio_archive_columns' );
$portfolio_pagination_type     = Avada()->settings->get( 'portfolio_archive_pagination_type' );
$portfolio_image_size          = Avada()->settings->get( 'portfolio_archive_featured_image_size' );
$portfolio_image_size_set      = $portfolio_image_size;
$lazy_load                     = Avada()->settings->get( 'lazy_load' );

if ( ! $portfolio_text_layout ) {
	$portfolio_text_layout = 'unboxed';
}

switch ( $portfolio_columns_int ) {
	case 1:
		$portfolio_columns = 'one';
		break;
	case 2:
		$portfolio_columns = 'two';
		break;
	case 3:
		$portfolio_columns = 'three';
		break;
	case 4:
		$portfolio_columns = 'four';
		break;
	case 5:
		$portfolio_columns = 'five';
		break;
	case 6:
		$portfolio_columns = 'six';
		break;
}

$portfolio_layout = 'fusion-portfolio-' . $portfolio_columns;

// Set the portfolio main classes.
$portfolio_classes[] = 'fusion-portfolio fusion-portfolio-archive';
$portfolio_classes[] = 'fusion-portfolio-layout-' . $portfolio_layout_setting;
$portfolio_classes[] = $portfolio_layout;

// If one column layout is used, add special class for text/notext and floated.
if ( 'one' === $portfolio_columns ) {
	if ( 'no_text' === $portfolio_text_layout ) {
		$portfolio_classes[] = 'fusion-portfolio-one-nontext';
	} elseif ( 'floated' === $portfolio_one_column_text_pos && 'grid' === $portfolio_layout_setting ) {
		$portfolio_classes[] = 'fusion-portfolio-text-floated';
	}
}

// For text layouts add the class for boxed/unboxed.
if ( 'no_text' !== $portfolio_text_layout ) {
	$portfolio_classes[] = 'fusion-portfolio-' . $portfolio_text_layout;
	$portfolio_classes[] = 'fusion-portfolio-text';
	$portfolio_classes[] = 'fusion-portfolio-text-' . $portfolio_text_alignment;
}

// Add class if rollover is enabled.
if ( $fusion_settings->get( 'image_rollover' ) ) {
	$portfolio_classes[] = 'fusion-portfolio-rollover';
}

// Set the correct image size.
$portfolio_image_size = 'portfolio-' . $portfolio_columns;

// Portfolio-four no longer exists.
if ( 'four' === $portfolio_columns ) {
	$portfolio_image_size = 'portfolio-three';
}

// Portfolio-six no longer exists.
if ( 'six' === $portfolio_columns ) {
	$portfolio_image_size = 'portfolio-five';
}

if ( 'full' === $portfolio_image_size_set || 'masonry' === $portfolio_layout_setting ) {
	$portfolio_image_size = 'full';
}

$post_featured_image_size_dimensions = avada_get_image_size_dimensions( $portfolio_image_size );

// Get the column spacing.
$column_spacing_class = ' fusion-col-spacing';
$column_spacing       = 'padding:calc(var(--portfolio_archive_column_spacing) / 2);';
$wrapper_margin       = 'margin:calc(var(--portfolio_archive_column_spacing) / -2);';

if ( 'one' === $portfolio_columns && 'grid' === $portfolio_layout_setting ) {
	$column_spacing_class = '';
	$column_spacing       = '';
	$wrapper_margin       = '';
}

// Check pagination type.
if ( 'load_more_button' === $portfolio_pagination_type ) {
	$portfolio_classes[] = 'fusion-portfolio-paging-load-more-button';
} elseif ( 'infinite_scroll' === $portfolio_pagination_type ) {
	$portfolio_classes[] = 'fusion-portfolio-paging-infinite';
}

if ( Avada()->settings->get( 'portfolio_equal_heights' ) && 'grid' === $portfolio_layout_setting ) {
	$portfolio_classes[] = 'fusion-portfolio-equal-heights';
}

// Get the correct ID of the archive.
$archive_id = get_queried_object_id();

$title      = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
$categories = true;

// Get title and category status.
if ( 'no_text' !== $portfolio_text_layout ) {
	$title_display = Avada()->settings->get( 'portfolio_archive_title_display' );
	$title         = ( 'all' === $title_display || 'title' === $title_display ) ? true : false; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	$categories    = ( 'all' === $title_display || 'cats' === $title_display ) ? true : false;
}
?>

<div class="<?php echo esc_attr( implode( ' ', $portfolio_classes ) ); ?>">

	<?php
	/**
	 * Render category description if it is set.
	 */
	?>
	<?php if ( category_description() ) : ?>
		<div id="post-<?php echo intval( get_the_ID() ); ?>" <?php post_class( 'post' ); ?>>
			<div class="post-content">
				<?php echo category_description(); ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="fusion-portfolio-wrapper" data-picturesize="<?php echo ( 'full' !== $portfolio_image_size ) ? 'fixed' : 'auto'; ?>" data-pages="<?php echo esc_attr( $wp_query->max_num_pages ); ?>" style="<?php echo esc_attr( $wrapper_margin ); ?>">

		<?php if ( 'masonry' === $portfolio_layout_setting ) : ?>
		<article class="fusion-portfolio-post fusion-grid-sizer"></article>
		<?php endif; ?>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php if ( Avada()->settings->get( 'featured_image_placeholder' ) || has_post_thumbnail() ) : ?>
				<?php
				$element_orientation_class = '';
				$element_base_padding      = 0.8;
				$responsive_images_columns = $portfolio_columns_int;
				$masonry_attributes        = [];

				// Masonry layout, get the element orientation class.
				if ( 'masonry' === $portfolio_layout_setting ) {
					// Set image or placeholder and correct corresponding styling.
					if ( has_post_thumbnail() ) {
						$post_thumbnail_attachment = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
						$masonry_attribute_style   = $lazy_load ? '' : 'background-image:url(' . $post_thumbnail_attachment[0] . ');';

					} else {
						$masonry_attribute_style = 'background-color:#f6f6f6;';
					}

					// Get the correct image orientation class.
					$element_orientation_class = $fusion_library->images->get_element_orientation_class( get_post_thumbnail_id() );
					$element_base_padding      = $fusion_library->images->get_element_base_padding( $element_orientation_class );

					$masonry_column_offset = ' - var(--portfolio_archive_column_spacing) / 2';
					if ( false !== strpos( $element_orientation_class, 'fusion-element-portrait' ) ) {
						$masonry_column_offset = '';
					}

					$masonry_column_spacing = 'var(--portfolio_archive_column_spacing)';

					if ( 'no_text' !== $portfolio_text_layout && 'boxed' === $portfolio_text_layout &&
						class_exists( 'Fusion_Sanitize' ) && class_exists( 'Fusion_Color' ) &&
						'transparent' !== Fusion_Sanitize::color( $fusion_settings->get( 'timeline_color' ) ) &&
						0 !== Fusion_Color::new_color( $fusion_settings->get( 'timeline_color' ) )->alpha
					) {

						$masonry_column_offset = ' - var(--portfolio_archive_column_spacing) / 2';
						if ( false !== strpos( $element_orientation_class, 'fusion-element-portrait' ) ) {
							$masonry_column_offset = ' + 4px';
						}

						$masonry_column_spacing = 'var(--portfolio_archive_column_spacing) - 4px';
						if ( false !== strpos( $element_orientation_class, 'fusion-element-landscape' ) ) {
							$masonry_column_spacing = 'var(--portfolio_archive_column_spacing) - 10px';
						}
					}

					// Calculate the correct size of the image wrapper container, based on orientation and column spacing.
					$masonry_attribute_style .= 'padding-top:calc((100% + ' . $masonry_column_spacing . ') * ' . $element_base_padding . $masonry_column_offset . ');';

					// Check if we have a landscape image, then it has to stretch over 2 cols.
					if ( '1' !== $portfolio_columns_int && 1 !== $portfolio_columns_int && false !== strpos( $element_orientation_class, 'fusion-element-landscape' ) ) {
						$responsive_images_columns = (int) $portfolio_columns_int / 2;
					}

					// Set the masonry attributes to use them in the first featured image function.
					$element_orientation_class = ' ' . $element_orientation_class;

					$masonry_attributes = [
						'class' => 'fusion-masonry-element-container',
						'style' => $masonry_attribute_style,
					];

					if ( $lazy_load && isset( $post_thumbnail_attachment[0] ) ) {
						$masonry_attributes['data-bg'] = $post_thumbnail_attachment[0];
						$masonry_attributes['class']  .= ' lazyload';
					}
				}
				?>

				<article class="fusion-portfolio-post post-<?php echo esc_attr( $post->ID ); ?> <?php echo esc_attr( $column_spacing_class . $element_orientation_class ); ?>" style="<?php echo esc_attr( $column_spacing ); ?>">

					<?php
					/**
					 * Open portfolio-item-wrapper for text layouts.
					 */
					?>
					<?php if ( 'no_text' !== $portfolio_text_layout || 'one' === $portfolio_columns ) : ?>
						<div class="fusion-portfolio-content-wrapper">
					<?php endif; ?>

						<?php
						/**
						 * If no featured image is present,
						 * on one column layouts render the video set in page options.
						 */
						?>
						<?php if ( ! has_post_thumbnail() && fusion_get_page_option( 'video', $post->ID ) ) : ?>
							<?php
							/**
							 * For the portfolio one column layout we need a fixed max-width.
							 * For all other layouts get the calculated max-width from the image size.
							 */
							?>
							<?php $video_max_width = ( 'one' === $portfolio_columns && 'floated' === $portfolio_one_column_text_pos ) ? '540px' : $post_featured_image_size_dimensions['width']; ?>
							<div class="fusion-image-wrapper fusion-video" style="max-width:<?php echo esc_attr( $video_max_width ); ?>;">
								<?php echo fusion_get_page_option( 'video', $post->ID ); // phpcs:ignore WordPress.Security ?>
							</div>

							<?php
							/**
							 * On every other other layout render the featured image.
							 */
							?>
						<?php else : ?>
							<?php
							if ( 'full' === $portfolio_image_size && class_exists( 'Avada' ) ) {
								Avada()->images->set_grid_image_meta(
									[
										'layout'       => 'portfolio_full',
										'columns'      => $responsive_images_columns,
										'gutter_width' => Avada()->settings->get( 'portfolio_archive_column_spacing' ),
									]
								);
							}
							echo fusion_render_first_featured_image_markup( $post->ID, $portfolio_image_size, get_permalink( $post->ID ), true, false, false, 'default', 'default', '', '', 'yes', false, $masonry_attributes ); // phpcs:ignore WordPress.Security
							Avada()->images->set_grid_image_meta( [] );
							?>

						<?php endif; ?>

						<?php
						/**
						 * If we don't have a text layout then only render rich snippets.
						 */
						?>
						<?php if ( 'no_text' === $portfolio_text_layout ) : ?>
							<?php echo fusion_render_rich_snippets_for_pages(); // phpcs:ignore WordPress.Security ?>
							<?php
							/**
							 * If we have a text layout render its contents.
							 */
							?>
						<?php else : ?>
							<div class="fusion-portfolio-content">
								<?php
								/**
								 * Render the post title.
								 */
								?>
								<?php
								if ( $title ) {
									echo avada_render_post_title( $post->ID ); // phpcs:ignore WordPress.Security
								}
								?>
								<?php
								/**
								 * Render the post categories.
								 */
								?>
								<?php
								if ( $categories ) {
									echo '<div class="fusion-portfolio-meta">' . get_the_term_list( $post->ID, 'portfolio_category', '', ', ', '' ) . '</div>';
								}
								?>
								<?php echo fusion_render_rich_snippets_for_pages( false ); // phpcs:ignore WordPress.Security ?>
								<?php
								/**
								 * For boxed layouts add a content separator if there is a post content and either categories or title is used.
								 */
								?>
								<?php if ( 'masonry' !== $portfolio_layout_setting && 'boxed' === $portfolio_text_layout && '0' !== fusion_get_portfolio_excerpt_length( $post->ID ) && 'no_text' !== $portfolio_content_length && ( $title || $categories ) ) : ?>
									<?php
									$separator_styles_array = explode( '|', $fusion_settings->get( 'grid_separator_style_type' ) );
									$separator_styles       = '';

									foreach ( $separator_styles_array as $separator_style ) {
										$separator_styles .= ' sep-' . $separator_style;
									}

									$border_color = Fusion_Color::new_color( $fusion_settings->get( 'grid_separator_color' ) );
									if ( 0 === $border_color->alpha || 'transparent' === $fusion_settings->get( 'grid_separator_color' ) ) {
										$separator_styles .= ' sep-transparent';
									}
									?>
									<div class="fusion-content-sep<?php echo esc_attr( $separator_styles ); ?>"></div>
								<?php endif; ?>

								<div class="fusion-post-content">
									<?php
									/**
									 * The avada_portfolio_post_content hook.
									 *
									 * @hooked avada_get_portfolio_content - 10 (outputs the post content).
									 */
									do_action( 'avada_portfolio_post_content', $post->ID );
									?>

									<?php
									/**
									 * On one column layouts render the "Learn More" and "View Project" buttons.
									 */
									?>
									<?php if ( 'one' === $portfolio_columns && 'grid' === $portfolio_layout_setting ) : ?>
										<?php $button_span_class = ( 'yes' === $fusion_settings->get( 'button_span' ) ) ? ' fusion-portfolio-buttons-full' : ''; ?>
										<div class="fusion-portfolio-buttons<?php echo esc_attr( $button_span_class ); ?>">
											<?php
											/**
											 * Render "Learn More" button.
											 */
											?>
											<a href="<?php echo esc_url_raw( get_permalink( $post->ID ) ); ?>" class="fusion-button fusion-button-small fusion-button-default fusion-button-<?php echo esc_attr( strtolower( Avada()->settings->get( 'button_type' ) ) ); ?>">
												<?php esc_html_e( 'Learn More', 'fusion-core' ); ?>
											</a>
											<?php
											/**
											 * Render the "View Project" button only if a project url was set.
											 */
											?>
											<?php if ( fusion_get_page_option( 'project_url', $post->ID ) ) : ?>
												<a href="<?php echo esc_url_raw( fusion_get_page_option( 'project_url', $post->ID ) ); ?>" class="fusion-button fusion-button-small fusion-button-default fusion-button-<?php echo esc_attr( strtolower( Avada()->settings->get( 'button_type' ) ) ); ?>">
													<?php esc_html_e( ' View Project', 'fusion-core' ); ?>
												</a>
											<?php endif; ?>
										</div>
									<?php endif; ?>

								</div><!-- end post-content -->

							</div><!-- end portfolio-content -->

						<?php endif; // End template check. ?>

					<?php
					/**
					 * Close portfolio-item-wrapper for text layouts.
					 */
					?>
					<?php if ( 'no_text' !== $portfolio_text_layout || 'one' === $portfolio_columns ) : ?>
						</div>

						<?php
						/**
						 * On unboxed one column layouts render a separator at the bottom of the post.
						 */
						?>
						<?php if ( 'one' === $portfolio_columns && 'boxed' !== $portfolio_text_layout && 'grid' === $portfolio_layout_setting ) : ?>
							<div class="fusion-clearfix"></div>
							<div class="fusion-separator sep-double"></div>
						<?php endif; ?>
					<?php endif; ?>

				</article><!-- end portfolio-post -->

			<?php endif; // Placeholders or featured image. ?>
		<?php endwhile; ?>

	</div><!-- end portfolio-wrapper -->

	<?php
	/**
	 * Render the pagination.
	 */
	?>
	<?php echo fusion_pagination( '', Avada()->settings->get( 'pagination_range' ) ); // phpcs:ignore WordPress.Security ?>
	<?php
	/**
	 * If infinite scroll with "load more" button is used.
	 */
	?>
	<?php if ( 'load_more_button' === $portfolio_pagination_type && 1 < esc_attr( $wp_query->max_num_pages ) ) : ?>
		<div class="fusion-load-more-button fusion-portfolio-button fusion-clearfix">
			<?php echo esc_attr( apply_filters( 'avada_load_more_posts_name', esc_html__( 'Load More Posts', 'fusion-core' ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div><!-- end fusion-portfolio -->
