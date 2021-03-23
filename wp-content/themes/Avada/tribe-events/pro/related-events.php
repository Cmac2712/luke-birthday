<?php
/**
 * Related Events Template
 * The template for displaying related events on the single event page.
 *
 * You can recreate an ENTIRELY new related events view by doing a template override, and placing
 * a related-events.php file in a tribe-events/pro/ directory within your theme directory, which
 * will override the /views/related-events.php.
 *
 * You can use any or all filters included in this file or create your own filters in
 * your functions.php. In order to modify or extend a single filter, please see our
 * readme on templates hooks and filters
 *
 * @package TribeEventsCalendarPro
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$posts = tribe_get_related_posts();

if ( is_array( $posts ) && ! empty( $posts ) ) : ?>

	<div class="related-posts single-related-posts">
		<?php Avada()->template->title_template( sprintf( __( 'Related %s', 'tribe-events-calendar-pro' ), tribe_get_event_label_plural() ), '3' ); ?>

		<ul class="tribe-related-events tribe-clearfix hfeed vcalendar">
			<?php foreach ( $posts as $post ) : ?>
			<li>
				<?php $thumb = ( has_post_thumbnail( $post->ID ) ) ? get_the_post_thumbnail( $post->ID, 'large' ) : '<img src="' . esc_url( trailingslashit( Tribe__Events__Pro__Main::instance()->pluginUrl ) . 'src/resources/images/tribe-related-events-placeholder.png' ) . '" alt="' . the_title_attribute( array( 'echo' => false, 'post' => $post->ID ) ) . '" />'; ?>
				<div class="fusion-ec-hover-type tribe-related-events-thumbnail hover-type-<?php echo Avada()->settings->get( 'ec_hover_type' ); ?>">
					<a href="<?php echo esc_url( tribe_get_event_link( $post ) ); ?>" class="url" rel="bookmark"><?php echo $thumb ?></a>
				</div>
				<div class="tribe-related-event-info">
					<h3 class="tribe-related-events-title summary"><a class="fusion-related-posts-title-link" href="<?php echo tribe_get_event_link( $post ); ?>" class="url" rel="bookmark"><?php echo get_the_title( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></h3>
					<?php
						if ( $post->post_type == Tribe__Events__Main::POSTTYPE ) {
							echo tribe_events_event_schedule_details( $post );
						}
					?>
				</div>
			</li>
			<?php endforeach; ?>
		</ul>

	</div>
<?php endif;

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
