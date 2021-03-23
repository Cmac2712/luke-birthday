<?php
/**
 * System-Status Admin page.
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
?>
<div class="wrap about-wrap avada-wrap">
	<?php $this->get_admin_screens_header( 'system-status' ); ?>
	<div class="avada-system-status">
		<table class="widefat fusion-system-status-debug" cellspacing="0">
			<tbody>
				<tr>
					<td colspan="3" data-export-label="Avada Versions">
						<span class="get-system-status"><a href="#" class="button-primary debug-report"><?php esc_html_e( 'Get System Report', 'Avada' ); ?></a><span class="system-report-msg"><?php esc_html_e( 'Click the button to produce a report, then copy and paste into your support ticket.', 'Avada' ); ?></span></span>

						<div id="debug-report">
							<textarea id="debug-report-textarea" readonly="readonly"></textarea>
							<p class="submit"><button id="copy-for-support" class="button-primary" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'Avada' ); ?>"><?php esc_html_e( 'Copy for Support', 'Avada' ); ?></button></p>
						</div>
					</td>
				</tr>
			</tbody>
		</div>
		<h3 class="screen-reader-text"><?php esc_html_e( 'Avada Version History', 'Avada' ); ?></h3>
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Avada Versions"><?php esc_html_e( 'Avada Version History', 'Avada' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-export-label="Current Version"><?php esc_html_e( 'Current Version:', 'Avada' ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo esc_html( $this->theme_version ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Previous Version"><?php esc_html_e( 'Previous Versions:', 'Avada' ); ?></td>
					<td class="help">&nbsp;</td>
					<?php
					$previous_version        = get_option( 'avada_previous_version', false );
					$previous_versions_array = [];
					$previous_version_string = __( 'No previous versions could be detected', 'Avada' );

					if ( $previous_version && is_array( $previous_version ) ) {
						foreach ( $previous_version as $key => $value ) {
							if ( ! $value ) {
								unset( $previous_version[ $key ] );
							}
						}

						$previous_versions_array = $previous_version;
						$previous_version_string = array_slice( $previous_version, -3, 3, true );
						$previous_version_string = implode( ' <span style="font-size:1em;line-height:inherit;" class="dashicons dashicons-arrow-right-alt"></span> ', array_map( 'esc_attr', $previous_version_string ) );
					}
					?>
					<td>
						<?php echo $previous_version_string; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		$show_400_migration       = false;
		$force_hide_400_migration = false;
		$show_500_migration       = false;
		$versions_count           = count( $previous_versions_array );
		if ( isset( $previous_versions_array[ $versions_count - 1 ] ) && isset( $previous_versions_array[ $versions_count - 2 ] ) ) {
			if ( version_compare( $previous_versions_array[ $versions_count - 1 ], '4.0.0', '>=' ) && version_compare( $previous_versions_array[ $versions_count - 2 ], '4.0.0', '<=' ) ) {
				$force_hide_400_migration = true;
			}
		}

		if ( ! empty( $previous_version ) ) {
			if ( is_array( $previous_version ) ) {
				foreach ( $previous_version as $ver ) {
					$ver = Avada_Helper::normalize_version( $ver );
					if ( version_compare( $ver, '4.0.0', '<' ) ) {
						$show_400_migration = true;
						$last_pre_4_version = $ver;
					}

					if ( version_compare( $ver, '5.0.0', '<' ) ) {
						$show_500_migration = true;
						$last_pre_5_version = $ver;
					}
					$last_version = $ver;
				}
			} else {
				$previous_version = Avada_Helper::normalize_version( $previous_version );
				if ( version_compare( $previous_version, '4.0.0', '<' ) ) {
					$show_400_migration = true;
					$last_pre_4_version = $previous_version;
				}

				if ( version_compare( $previous_version, '5.0.0', '<' ) ) {
					$show_500_migration = true;
					$last_pre_5_version = $previous_version;
				}
				$last_version = $previous_version;
			}
		}
		?>

		<h3 class="screen-reader-text"><?php esc_html_e( 'Avada Conversion Controls', 'Avada' ); ?></h3>
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Avada Versions"><?php esc_html_e( 'Avada Conversion Controls', 'Avada' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="padding-top: 25px; padding-bottom: 25px;">
						<?php /* translators: Version Number. */ ?>
						<?php printf( esc_html__( 'Avada %s Conversion:', 'Avada' ), esc_html( $this->theme_version ) ); ?>
						<div><a href="https://theme-fusion.com/documentation/avada/install-update/avada-changelog/" target="_blank"><?php esc_html_e( 'Changelog', 'Avada' ); ?></a></div>
					</td>
					<td class="help" style="padding-top: 25px; padding-bottom: 30px;">&nbsp;</td>
					<td style="padding-top: 25px; padding-bottom: 30px;">
						<table class="widefat fusion-conversion-button" style="margin-bottom: 0;">
							<tr>
								<?php /* translators: Version Number. */ ?>
								<td style="width:auto;"><?php printf( esc_html__( 'Rerun Theme Options Conversion for version %s manually.', 'Avada' ), esc_html( $this->theme_version ) ); ?></td>
								<td style="width:140px;"><a class="button button-small button-primary" style="display:block;width:100%;text-align:center;" id="avada-manual-current-version-migration-trigger" href="#"><?php esc_attr_e( 'Run Conversion', 'Avada' ); ?></a></td>
							</tr>
						</table>
					</td>
				</tr>

				<?php // Display Avada 4.0 and/or 5.0 conversions if available. ?>
				<?php if ( ( $show_400_migration && ! $force_hide_400_migration ) || $show_500_migration ) : ?>
					<tr>
						<td colspan="3" style="border-top: 1px solid #ccd0d4;">
							<p style="margin: 0;padding: 17px 0;">
								<?php /* translators: URL. */ ?>
								<?php printf( __( '<strong style="color:red;">IMPORTANT:</strong> Updating to Avada 4.0 and 5.0 requires a conversion process to ensure your content is compatible with the new version. This is an automatic process that happens upon update. In rare situations, you may need to rerun conversion if there was an issue through the automatic process. The controls below allow you to do this if needed. Please <a href="%s" target="_blank">contact our support team</a> through a ticket if you have any questions or need assistance.', 'Avada' ), 'https://theme-fusion.com/documentation/avada/getting-started/avada-theme-support/' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( $show_400_migration && false === $force_hide_400_migration ) : ?>
					<?php /* translators: Version Number. */ ?>
					<?php $latest_version = ( empty( $last_version ) || ! $last_version ) ? esc_html__( 'Previous Version', 'Avada' ) : sprintf( esc_html__( 'Version %s', 'Avada' ), esc_html( $last_version ) ); ?>
					<?php $last_pre_4_version = ( isset( $last_pre_4_version ) ) ? $last_pre_4_version : $latest_version; ?>
					<tr>
						<td>
							<?php esc_html_e( 'Avada 4.0 Conversion:', 'Avada' ); ?>
							<div><a href="https://theme-fusion.com/documentation/avada/knowledgebase/avada-v4-migration/" target="_blank"><?php esc_html_e( 'Learn More', 'Avada' ); ?></a></div>
						</td>
						<td class="help">&nbsp;</td>
						<td>
							<table class="widefat fusion-conversion-button">
								<tr>
									<?php /* translators: Version Number. */ ?>
									<td style="width:auto;"><?php printf( esc_html__( 'Rerun Theme Options Conversion from version %s to version 4.0 manually.', 'Avada' ), esc_html( $last_pre_4_version ) ); ?></td>
									<td style="width:140px;"><a class="button button-small button-primary" style="display:block;width:100%;text-align:center;" id="avada-manual-400-migration-trigger" href="#"><?php esc_attr_e( 'Run Conversion', 'Avada' ); ?></a></td>
								</tr>
							</table>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( $show_500_migration ) : ?>
					<?php /* translators: Version Number. */ ?>
					<?php $latest_version = ( empty( $last_version ) || ! $last_version ) ? esc_html__( 'Previous Version', 'Avada' ) : sprintf( esc_html__( 'Version %s', 'Avada' ), $last_version ); ?>
					<?php $last_pre_5_version = ( isset( $last_pre_5_version ) ) ? $last_pre_5_version : $latest_version; ?>
					<tr>
						<td>
							<?php esc_html_e( 'Avada 5.0 Conversion:', 'Avada' ); ?>
							<div><a href="https://theme-fusion.com/documentation/fusion-builder/technical/converting-fusion-builder-pages/" target="_blank"><?php esc_html_e( 'Learn More', 'Avada' ); ?></a></div>
						</td>
						<td class="help">&nbsp;</td>
						<td>
							<table class="widefat fusion-conversion-button">
								<tr>
									<?php /* translators: Version Number. */ ?>
									<td style="width:auto;"><?php printf( esc_html__( 'Rerun Shortcode Conversion from version %s to version 5.0 manually.', 'Avada' ), esc_html( $last_pre_5_version ) ); ?></td>
									<td style="width:140px;"><a class="button button-small button-primary" style="display:block;width:100%;text-align:center;" id="avada-manual-500-migration-trigger" href="#"><?php esc_html_e( 'Run Conversion', 'Avada' ); ?></a></td>
								</tr>
								<?php
								$option_name = Avada::get_option_name();
								$backup      = get_option( $option_name . '_500_backup', false );
								if ( ! $backup && 'fusion_options' === $option_name ) {
									$backup = get_option( 'avada_theme_options_500_backup', false );
								}
								?>
								<?php if ( false !== get_option( 'fusion_core_unconverted_posts_converted', true ) ) : ?>
									<?php if ( false !== $backup || false !== get_option( 'scheduled_avada_fusionbuilder_migration_cleanups', true ) ) : ?>
										<tr>
											<td style="width:auto;"><?php esc_html_e( 'Revert Fusion Builder Conversion.', 'Avada' ); ?></td>
											<td style="width:140px;"><a class="button button-small button-primary" style="display:block;width:100%;text-align:center;" id="avada-manual-500-migration-revert-trigger" href="#"><?php esc_attr_e( 'Revert Conversion', 'Avada' ); ?></a></td>
										</tr>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ( false !== $backup || false !== get_option( 'scheduled_avada_fusionbuilder_migration_cleanups', false ) ) : ?>
									<tr>
										<td style="width:auto;">
											<?php $show_remove_backups_button = false; ?>
											<?php if ( isset( $_GET['cleanup-500-backups'] ) && '1' == $_GET['cleanup-500-backups'] ) : // phpcs:ignore WordPress.Security.NonceVerification, WordPress.PHP.StrictComparisons.LooseComparison ?>
												<?php update_option( 'scheduled_avada_fusionbuilder_migration_cleanups', true ); ?>
												<?php esc_html_e( 'The backups cleanup process has been scheduled and your the version 5.0 conversion backups will be purged from your database.', 'Avada' ); ?>
											<?php else : ?>
												<?php if ( false !== get_option( 'avada_migration_cleanup_id', false ) ) : ?>
													<?php
													// The post types we'll need to check.
													$post_types = apply_filters(
														'fusion_builder_shortcode_migration_post_types',
														[
															'page',
															'post',
															'avada_faq',
															'avada_portfolio',
															'product',
															'tribe_events',
														]
													);
													foreach ( $post_types as $key => $post_type ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
														if ( ! post_type_exists( $post_type ) ) {
															unset( $post_types[ $key ] );
														}
													}

													// Build the query array.
													$args = [
														'posts_per_page' => 1,
														'orderby'        => 'ID',
														'order'          => 'DESC',
														'post_type'      => $post_types,
														'post_status'    => 'any',
													];

													// The query to get posts that meet our criteria.
													$posts = fusion_cached_get_posts( $args ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride

													$current_step = get_option( 'avada_migration_cleanup_id', false );
													$total_steps  = $posts[0]->ID;
													?>
													<?php /* translators: Numbers. */ ?>
													<?php printf( esc_html__( 'Currently removing backups from your database (step %1$s of %2$s)', 'Avada' ), (int) $current_step, (int) $total_steps ); ?>
												<?php else : ?>
													<?php $show_remove_backups_button = true; ?>
													<?php esc_html_e( 'Remove Shortcode Conversion Backups created during the version 5.0 conversion.', 'Avada' ); ?>
												<?php endif; ?>
											<?php endif; ?>
										</td>
										<?php if ( isset( $show_remove_backups_button ) && true === $show_remove_backups_button ) : ?>
											<td style="width:140px;">
												<a class="button button-small button-primary" style="display:block;width:100%;text-align:center;" id="avada-remove-500-migration-backups" href="#"><?php esc_html_e( 'Remove Backups', 'Avada' ); ?></a>
											</td>
										<?php endif; ?>
									</tr>
								<?php endif; ?>
							</table>
						</td>
					</tr>

				<?php endif; ?>
			</tbody>
		</table>

		<h3 class="screen-reader-text"><?php esc_html_e( 'WordPress Environment', 'Avada' ); ?></h3>
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="WordPress Environment"><?php esc_html_e( 'WordPress Environment', 'Avada' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-export-label="Home URL"><?php esc_html_e( 'Home URL:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The URL of your site\'s homepage.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo esc_url_raw( home_url() ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Site URL"><?php esc_html_e( 'Site URL:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The root URL of your site.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo esc_url_raw( site_url() ); ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Content Path"><?php esc_html_e( 'WP Content Path:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'System path of your wp-content directory.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo defined( 'WP_CONTENT_DIR' ) ? esc_html( WP_CONTENT_DIR ) : esc_html__( 'N/A', 'Avada' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Path"><?php esc_html_e( 'WP Path:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'System path of your WP root directory.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo defined( 'ABSPATH' ) ? esc_html( ABSPATH ) : esc_html__( 'N/A', 'Avada' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Version"><?php esc_html_e( 'WP Version:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of WordPress installed on your site.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php bloginfo( 'version' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Multisite"><?php esc_html_e( 'WP Multisite:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Whether or not you have WordPress Multisite enabled.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo ( is_multisite() ) ? '&#10004;' : '&ndash;'; ?></td>
				</tr>
				<tr>
					<td data-export-label="PHP Memory Limit"><?php esc_html_e( 'PHP Memory Limit:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum amount of memory (RAM) that your site can use at one time.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td>
						<?php
						// Get the memory from PHP's configuration.
						$memory = ini_get( 'memory_limit' );
						// If we can't get it, fallback to WP_MEMORY_LIMIT.
						if ( ! $memory || -1 === $memory ) {
							$memory = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
						}
						// Make sure the value is properly formatted in bytes.
						if ( ! is_numeric( $memory ) ) {
							$memory = wp_convert_hr_to_bytes( $memory );
						}
						?>
						<?php if ( $memory < 128000000 ) : ?>
							<mark class="error">
								<?php /* translators: %1$s: Current value. %2$s: URL. */ ?>
								<?php printf( __( '%1$s - We recommend setting memory to at least <strong>128MB</strong>. Please define memory limit in <strong>wp-config.php</strong> file. To learn how, see: <a href="%2$s" target="_blank" rel="noopener noreferrer">Increasing memory allocated to PHP.</a>', 'Avada' ), esc_attr( size_format( $memory ) ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</mark>
						<?php else : ?>
							<mark class="yes">
								<?php echo esc_html( size_format( $memory ) ); ?>
							</mark>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td data-export-label="WP Debug Mode"><?php esc_html_e( 'WP Debug Mode:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Displays whether or not WordPress is in Debug Mode.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td>
						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
							<mark class="yes">&#10004;</mark>
						<?php else : ?>
							<mark class="no">&ndash;</mark>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td data-export-label="Language"><?php esc_html_e( 'Language:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The current language used by WordPress. Default = English', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo esc_html( get_locale() ); ?></td>
				</tr>
			</tbody>
		</table>

		<h3 class="screen-reader-text"><?php esc_html_e( ' Environment', 'Avada' ); ?></h3>
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Server Environment"><?php esc_html_e( 'Server Environment', 'Avada' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-export-label="Server Info"><?php esc_html_e( 'Server Info:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Information about the web server that is currently hosting your site.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo isset( $_SERVER['SERVER_SOFTWARE'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : esc_html__( 'Unknown', 'Avada' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="PHP Version"><?php esc_html_e( 'PHP Version:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of PHP installed on your hosting server.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td>
						<?php
						$php_version = null;
						if ( defined( 'PHP_VERSION' ) ) {
							$php_version = PHP_VERSION;
						} elseif ( function_exists( 'phpversion' ) ) {
							$php_version = phpversion();
						}
						if ( null === $php_version ) {
							$message = esc_html__( 'PHP Version could not be detected.', 'Avada' );
						} else {
							if ( version_compare( $php_version, '7.3' ) >= 0 ) {
								$message = $php_version;
							} else {
								$message = sprintf(
									/* translators: %1$s: Current PHP version. %2$s: Recommended PHP version. %3$s: "WordPress Requirements" link. */
									esc_html__( '%1$s. WordPress recommendation: %2$s or above. See %3$s for details.', 'Avada' ),
									$php_version,
									'7.3',
									'<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress Requirements', 'Avada' ) . '</a>'
								);
							}
						}
						echo $message; // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</td>
				</tr>
				<?php if ( function_exists( 'ini_get' ) ) : ?>
					<tr>
						<td data-export-label="PHP Post Max Size"><?php esc_html_e( 'PHP Post Max Size:', 'Avada' ); ?></td>
						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The largest file size that can be contained in one post.', 'Avada' ) . '">[?]</a>'; ?></td>
						<td><?php echo esc_html( size_format( wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ) ) ); ?></td>
					</tr>
					<tr>
						<td data-export-label="PHP Time Limit"><?php esc_html_e( 'PHP Time Limit:', 'Avada' ); ?></td>
						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'Avada' ) . '">[?]</a>'; ?></td>
						<td>
							<?php
							$time_limit = ini_get( 'max_execution_time' );

							if ( 180 > $time_limit && 0 != $time_limit ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
								/* translators: %1$s: Current value. %2$s: URL. */
								echo '<mark class="error">' . sprintf( __( '%1$s - We recommend setting max execution time to at least 180.<br />See: <a href="%2$s" target="_blank" rel="noopener noreferrer">Increasing max execution to PHP</a>', 'Avada' ), $time_limit, 'http://codex.wordpress.org/Common_WordPress_Errors#Maximum_execution_time_exceeded' ) . '</mark>'; // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo '<mark class="yes">' . esc_attr( $time_limit ) . '</mark>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td data-export-label="PHP Max Input Vars"><?php esc_html_e( 'PHP Max Input Vars:', 'Avada' ); ?></td>
						<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'Avada' ) . '">[?]</a>'; ?></td>
						<?php
						$registered_navs  = get_nav_menu_locations();
						$menu_items_count = [
							'0' => '0',
						];
						foreach ( $registered_navs as $handle => $registered_nav ) {
							$menu = wp_get_nav_menu_object( $registered_nav ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
							if ( $menu ) {
								$menu_items_count[] = $menu->count;
							}
						}

						$max_items = max( $menu_items_count );
						if ( Avada()->settings->get( 'disable_megamenu' ) ) {
							$required_input_vars = $max_items * 20;
						} else {
							$required_input_vars = $max_items * 12;
						}
						?>
						<td>
							<?php
							$max_input_vars      = ini_get( 'max_input_vars' );
							$required_input_vars = $required_input_vars + ( 500 + 1000 );
							// 1000 = theme options
							if ( $max_input_vars < $required_input_vars ) {
								/* translators: %1$s: Current value. $2%s: Recommended value. %3$s: URL. */
								echo '<mark class="error">' . sprintf( __( '%1$s - Recommended Value: %2$s.<br />Max input vars limitation will truncate POST data such as menus. See: <a href="%3$s" target="_blank" rel="noopener noreferrer">Increasing max input vars limit.</a>', 'Avada' ), $max_input_vars, '<strong>' . $required_input_vars . '</strong>', 'http://sevenspark.com/docs/ubermenu-3/faqs/menu-item-limit' ) . '</mark>'; // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo '<mark class="yes">' . esc_html( $max_input_vars ) . '</mark>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td data-export-label="SUHOSIN Installed"><?php esc_html_e( 'SUHOSIN Installed:', 'Avada' ); ?></td>
						<td class="help">
							<a href="#" class="help_tip" data-tip="<?php esc_attr_e( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'Avada' ); ?>">[?]</a>
						</td>
						<td><?php echo extension_loaded( 'suhosin' ) ? '&#10004;' : '&ndash;'; ?></td>
					</tr>
					<?php if ( extension_loaded( 'suhosin' ) ) : ?>
						<tr>
							<td data-export-label="Suhosin Post Max Vars"><?php esc_html_e( 'Suhosin Post Max Vars:', 'Avada' ); ?></td>
							<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'Avada' ) . '">[?]</a>'; ?></td>
							<?php
							$registered_navs  = get_nav_menu_locations();
							$menu_items_count = [
								'0' => '0',
							];
							foreach ( $registered_navs as $handle => $registered_nav ) {
								$menu = wp_get_nav_menu_object( $registered_nav ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
								if ( $menu ) {
									$menu_items_count[] = $menu->count;
								}
							}

							$max_items = max( $menu_items_count );
							if ( Avada()->settings->get( 'disable_megamenu' ) ) {
								$required_input_vars = $max_items * 20;
							} else {
								$required_input_vars = $max_items * 12;
							}
							?>
							<td>
								<?php
								$max_input_vars      = ini_get( 'suhosin.post.max_vars' );
								$required_input_vars = $required_input_vars + ( 500 + 1000 );

								if ( $max_input_vars < $required_input_vars ) {
									/* translators: %1$s: Current value. $2%s: Recommended value. %3$s: URL. */
									echo '<mark class="error">' . sprintf( __( '%1$s - Recommended Value: %2$s.<br />Max input vars limitation will truncate POST data such as menus. See: <a href="%3$s" target="_blank" rel="noopener noreferrer">Increasing max input vars limit.</a>', 'Avada' ), $max_input_vars, '<strong>' . ( $required_input_vars ) . '</strong>', 'http://sevenspark.com/docs/ubermenu-3/faqs/menu-item-limit' ) . '</mark>'; // phpcs:ignore WordPress.Security.EscapeOutput
								} else {
									echo '<mark class="yes">' . esc_html( $max_input_vars ) . '</mark>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td data-export-label="Suhosin Request Max Vars"><?php esc_html_e( 'Suhosin Request Max Vars:', 'Avada' ); ?></td>
							<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'Avada' ) . '">[?]</a>'; ?></td>
							<?php
							$registered_navs  = get_nav_menu_locations();
							$menu_items_count = [
								'0' => '0',
							];
							foreach ( $registered_navs as $handle => $registered_nav ) {
								$menu = wp_get_nav_menu_object( $registered_nav ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
								if ( $menu ) {
									$menu_items_count[] = $menu->count;
								}
							}

							$max_items = max( $menu_items_count );
							if ( Avada()->settings->get( 'disable_megamenu' ) ) {
								$required_input_vars = $max_items * 20;
							} else {
								$required_input_vars = ini_get( 'suhosin.request.max_vars' );
							}
							?>
							<td>
								<?php
								$max_input_vars      = ini_get( 'suhosin.request.max_vars' );
								$required_input_vars = $required_input_vars + ( 500 + 1000 );

								if ( $max_input_vars < $required_input_vars ) {
									/* translators: %1$s: Current value. $2%s: Recommended value. %3$s: URL. */
									echo '<mark class="error">' . sprintf( __( '%1$s - Recommended Value: %2$s.<br />Max input vars limitation will truncate POST data such as menus. See: <a href="%3$s" target="_blank" rel="noopener noreferrer">Increasing max input vars limit.</a>', 'Avada' ), $max_input_vars, '<strong>' . ( $required_input_vars + ( 500 + 1000 ) ) . '</strong>', 'http://sevenspark.com/docs/ubermenu-3/faqs/menu-item-limit' ) . '</mark>'; // phpcs:ignore WordPress.Security.EscapeOutput
								} else {
									echo '<mark class="yes">' . esc_html( $max_input_vars ) . '</mark>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td data-export-label="Suhosin Post Max Value Length"><?php esc_html_e( 'Suhosin Post Max Value Length:', 'Avada' ); ?></td>
							<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Defines the maximum length of a variable that is registered through a POST request.', 'Avada' ) . '">[?]</a>'; ?></td>
							<td>
							<?php
								$suhosin_max_value_length     = ini_get( 'suhosin.post.max_value_length' );
								$recommended_max_value_length = 2000000;

							if ( $suhosin_max_value_length < $recommended_max_value_length ) {
								/* translators: %1$s: Current value. $2%s: Recommended value. %3$s: URL. */
								echo '<mark class="error">' . sprintf( __( '%1$s - Recommended Value: %2$s.<br />Post Max Value Length limitation may prohibit the Theme Options data from being saved to your database. See: <a href="%3$s" target="_blank" rel="noopener noreferrer">Suhosin Configuration Info</a>.', 'Avada' ), $suhosin_max_value_length, '<strong>' . $recommended_max_value_length . '</strong>', 'http://suhosin.org/stories/configuration.html' ) . '</mark>'; // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo '<mark class="yes">' . esc_attr( $suhosin_max_value_length ) . '</mark>';
							}
							?>
							</td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>
				<tr>
					<td data-export-label="ZipArchive"><?php esc_html_e( 'ZipArchive:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'ZipArchive is required for importing demos. They are used to import and export zip files specifically for sliders.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo class_exists( 'ZipArchive' ) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">ZipArchive is not installed on your server, but is required if you need to import demo content.</mark>'; ?></td>
				</tr>
				<tr>
					<td data-export-label="MySQL Version"><?php esc_html_e( 'MySQL Version:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of MySQL installed on your hosting server.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td>
						<?php global $wpdb; ?>
						<?php echo esc_html( $wpdb->db_version() ); ?>
					</td>
				</tr>
				<tr>
					<td data-export-label="Max Upload Size"><?php esc_html_e( 'Max Upload Size:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The largest file size that can be uploaded to your WordPress installation.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo esc_attr( size_format( wp_max_upload_size() ) ); ?></td>
				</tr>
				<tr>
					<td data-export-label="DOMDocument"><?php esc_html_e( 'DOMDocument:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'DOMDocument is required for the Fusion Builder plugin to properly function.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td><?php echo class_exists( 'DOMDocument' ) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">DOMDocument is not installed on your server, but is required if you need to use the Fusion Page Builder.</mark>'; ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Remote Get"><?php esc_html_e( 'WP Remote Get:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Avada uses this method to communicate with different APIs, e.g. Google, Twitter, Facebook.', 'Avada' ) . '">[?]</a>'; ?></td>
					<?php
					$response = wp_safe_remote_get(
						'https://build.envato.com/api/',
						[
							'decompress' => false,
							'user-agent' => 'avada-remote-get-test',
						]
					);
					?>
					<td><?php echo ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">wp_remote_get() failed. Some theme features may not work. Please contact your hosting provider and make sure that https://build.envato.com/api/ is not blocked.</mark>'; ?></td>
				</tr>
				<tr>
					<td data-export-label="WP Remote Post"><?php esc_attr_e( 'WP Remote Post:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Avada uses this method to communicate with different APIs, e.g. Google, Twitter, Facebook.', 'Avada' ) . '">[?]</a>'; ?></td>
					<?php
					$response = wp_safe_remote_post(
						'https://www.google.com/recaptcha/api/siteverify',
						[
							'decompress' => false,
							'user-agent' => 'avada-remote-get-test',
						]
					);
					?>
					<td><?php echo ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) ? '<mark class="yes">&#10004;</mark>' : '<mark class="error">wp_remote_post() failed. Some theme features may not work. Please contact your hosting provider and make sure that https://www.google.com/recaptcha/api/siteverify is not blocked.</mark>'; ?></td>
				</tr>
				<tr>
					<td data-export-label="GD Library"><?php esc_html_e( 'GD Library:', 'Avada' ); ?></td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Avada uses this library to resize images and speed up your site\'s loading time', 'Avada' ) . '">[?]</a>'; ?></td>
					<td>
						<?php
						$info = esc_html__( 'Not Installed', 'Avada' );
						if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
							$info    = esc_html__( 'Installed', 'Avada' );
							$gd_info = gd_info();
							if ( isset( $gd_info['GD Version'] ) ) {
								$info = $gd_info['GD Version'];
							}
						}
						echo esc_attr( $info );
						?>
					</td>
				</tr>
			</tbody>
		</table>

		<h3 class="screen-reader-text"><?php esc_html_e( 'Updates Server Status', 'Avada' ); ?></h3>
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3"><?php esc_html_e( 'Updates Server Status', 'Avada' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<a href="#" data-api_type="envato" class="button button-primary fusion-check-api-status"><?php esc_html_e( 'Check Envato Server Status', 'Avada' ); ?></a>
						<span class="fusion-system-status-spinner" style="display: none;">
							<img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" />
						</span>
					</td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Server from which Avada is updated.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td></td>
				</tr>
				<tr>
					<td>
						<a href="#" data-api_type="tf_updates" class="button button-primary fusion-check-api-status"><?php esc_html_e( 'Check ThemeFusion Server Status', 'Avada' ); ?></a>
						<span class="fusion-system-status-spinner" style="display: none;">
							<img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" />
						</span>
					</td>
					<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Server from which plugins and patches are downloaded.', 'Avada' ) . '">[?]</a>'; ?></td>
					<td></td>
				</tr>
				<tr>
					<td colspan="3"><textarea id="fusion-check-api-textarea" readonly style="display:none;width:100%;"></textarea></td>
				</tr>
			</tbody>
		</table>

		<h3 class="screen-reader-text"><?php esc_html_e( 'Active Plugins', 'Avada' ); ?></h3>
		<?php
		$active_plugins = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', [] ) ) );
		}
		?>
		<table class="widefat" cellspacing="0" id="status">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Active Plugins (<?php echo count( $active_plugins ); ?>)"><?php esc_html_e( 'Active Plugins', 'Avada' ); ?> (<?php echo count( $active_plugins ); ?>)</th>
				</tr>
			</thead>
			<tbody>
				<?php

				foreach ( $active_plugins as $plugin_file ) {

					$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
					$dirname        = dirname( $plugin_file );
					$version_string = '';
					$network_string = '';

					if ( ! empty( $plugin_data['Name'] ) ) {

						// Link the plugin name to the plugin url if available.
						if ( ! empty( $plugin_data['PluginURI'] ) ) {
							$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . __( 'Visit plugin homepage', 'Avada' ) . '">' . esc_html( $plugin_data['Name'] ) . '</a>';
						} else {
							$plugin_name = esc_html( $plugin_data['Name'] );
						}
						?>
						<tr>
							<td>
								<?php echo $plugin_name; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td>
							<td class="help">&nbsp;</td>
							<td>
								<?php /* translators: plugin author. */ ?>
								<?php printf( esc_html__( 'by %s', 'Avada' ), '<a href="' . esc_url( $plugin_data['AuthorURI'] ) . '" target="_blank">' . esc_html( $plugin_data['AuthorName'] ) . '</a>' ) . ' &ndash; ' . esc_html( $plugin_data['Version'] ) . $version_string . $network_string; ?>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="avada-thanks">
		<p class="description"><?php esc_html_e( 'Thank you for choosing Avada. We are honored and are fully dedicated to making your experience perfect.', 'Avada' ); ?></p>
	</div>
</div>




<script type="text/javascript">
	jQuery( '#avada-manual-current-version-migration-trigger' ).on( 'click', function( e ) {
		e.preventDefault();
		<?php /* translators: Version Number. */ ?>
		var migration_response = confirm( "<?php printf( esc_html__( 'Note: By clicking OK, the Theme Options conversion for Avada %s will be rerun. This page will be newly loaded, which already completes the conversion.', 'Avada' ), esc_html( $this->theme_version ) ); ?>" );
		if ( true == migration_response ) {
			window.location= "<?php echo esc_url_raw( admin_url( 'admin.php?page=avada-system-status&migrate=' . esc_html( $this->theme_version ) ) ); ?>";
		}
	} );

<?php if ( $show_400_migration && false === $force_hide_400_migration ) : ?>
	jQuery( '#avada-manual-400-migration-trigger' ).on( 'click', function( e ) {
		e.preventDefault();
		<?php /* translators: last version. */ ?>
		var migration_response = confirm( "<?php printf( esc_html__( 'Warning: By clicking OK, all changes made to your theme options after installing Avada 4.0 will be lost. Your Theme Options will be reset to the values from %s and then converted again to 4.0.', 'Avada' ), esc_html( $latest_version ) ); ?>" );
		if ( true == migration_response ) {
			window.location= "<?php echo esc_url_raw( admin_url( 'index.php?avada_update=1&ver=400&new=1' ) ); ?>";
		}
	} );
<?php endif; ?>

<?php if ( $show_500_migration ) : ?>
	jQuery( '#avada-manual-500-migration-trigger' ).on( 'click', function( e ) {
		e.preventDefault();
		var migration_response = confirm( "<?php esc_html_e( 'Warning: By clicking OK, you will be redirected to the conversion splash screen, where you can restart the conversion of your page contents to the new Fusion Builder format.', 'Avada' ); ?>" );
		if ( migration_response == true ) {
			window.location= "<?php echo esc_url_raw( admin_url( 'index.php?fusion_builder_migrate=1&ver=500' ) ); ?>";
		}
	} );

	jQuery( '#avada-manual-500-migration-revert-trigger' ).on( 'click', function( e ) {
		e.preventDefault();
		var migration_response = confirm( "<?php esc_html_e( 'Warning: By clicking OK, you will be redirected to the conversion splash screen, where you can start the conversion reversion of your page contents to the old Fusion Builder format.', 'Avada' ); ?>" );
		if ( migration_response == true ) {
			window.location= "<?php echo esc_url_raw( admin_url( 'index.php?fusion_builder_migrate=1&ver=500&revert=1' ) ); ?>";
		}
	} );

	jQuery( '#avada-remove-500-migration-backups' ).on( 'click', function( e ) {
		e.preventDefault();
		var migration_response = confirm( "<?php esc_html_e( 'Warning: This is a non-reversable process. By clicking OK, all backups created during the 5.0 shortcode-conversion process will be removed from your database.', 'Avada' ); ?>" );
		if ( migration_response == true ) {
			window.location= "<?php echo esc_url_raw( admin_url( 'admin.php?page=avada-system-status&cleanup-500-backups=1' ) ); ?>";
		}
	});
<?php endif; ?>
</script>



<script type="text/javascript">
	jQuery( document ).ready( function() {

		jQuery( '.fusion-check-api-status' ).on( 'click', function( event ) {
			var $this = jQuery( this ),
				$statusCell = $this.closest( 'tr' ).find( 'td:nth-child(3)' );
				data = {
				action: 'fusion_check_api_status',
				nonce: '<?php echo esc_js( wp_create_nonce( 'fusion_check_api_status_nonce' ) ); ?>'
			};

			event.preventDefault();

			if ( 'undefined' === typeof jQuery( this ).data( 'api_type' ) ) {
				return;
			}

			$statusCell.html( '' );
			$this.closest( 'tr' ).find( '.fusion-system-status-spinner' ).css( 'display', 'inline-block' );

			data.api_type = jQuery( this ).data( 'api_type' );

			jQuery.get( ajaxurl, data, function( response ) {

				if ( 200 === response.code ) {
					$statusCell.removeClass( 'fusion-api-status-error' );
					$statusCell.addClass( 'fusion-api-status-ok' );
					jQuery( '#fusion-check-api-textarea' ).css( 'display', 'none' );
				} else {
					$statusCell.removeClass( 'fusion-api-status-ok' );
					$statusCell.addClass( 'fusion-api-status-error' );
					jQuery( '#fusion-check-api-textarea' ).css( 'display', 'block' );
				}

				$this.closest( 'tr' ).find( '.fusion-system-status-spinner' ).css( 'display', 'none' );
				$statusCell.html( response.message );

				jQuery( '#fusion-check-api-textarea' ).html( response.api_response );
			}, 'json' );

		} );
	});
</script>
