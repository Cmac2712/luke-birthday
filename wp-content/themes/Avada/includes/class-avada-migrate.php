<?php
/**
 * Handles migrations.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 *
 * phpcs:ignoreFile WordPress.Security.NonceVerification
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handles migrations.
 */
class Avada_Migrate extends Avada_Upgrade {

	/**
	 * The current step number.
	 *
	 * @access  public
	 * @var  int
	 */
	public $step;

	/**
	 * The number of steps available.
	 *
	 * @access  public
	 * @var  array
	 */
	public $steps = [];

	/**
	 * Should we proceed to the next step?
	 *
	 * @access  public
	 * @var  bool
	 */
	public $proceed = true;

	/**
	 * The one, true instance of this object.
	 *
	 * @access  public
	 * @var  null|object
	 */
	public static $instance = null;

	/**
	 * An array of all available languages.
	 *
	 * @access  public
	 * @var  array
	 */
	public $available_languages = [];

	/**
	 * The active language.
	 *
	 * @access  public
	 * @var  string
	 */
	public $active_language = '';

	/**
	 * The default language/
	 *
	 * @access  public
	 * @var  string
	 */
	public $default_language = '';

	/**
	 * An array of our options.
	 *
	 * @access  public
	 * @var  array
	 */
	public $options;

	/**
	 * An array of all our fields.
	 *
	 * @access  public
	 * @var  array
	 */
	public $fields;

	/**
	 * The version.
	 *
	 * @access  public
	 * @var  string
	 */
	public $version;

	/**
	 * The language.
	 *
	 * @access  public
	 * @var  string
	 */
	public $lang = '';

	/**
	 * The language used when we start.
	 *
	 * @access  public
	 * @var  string
	 */
	public $starting_language;

	/**
	 * Constructor.
	 */
	protected function __construct() {

		Avada::$is_updating = true;

		// Raise the memory limit and max_execution_time time.
		if ( function_exists( 'ini_get' ) ) {
			$memory            = ini_get( 'memory_limit' );
			$memory_shorthands = [
				'k' => 1024,
				'm' => 1048576,
				'g' => 1073741824,
			];
			$memory_last_char  = substr( $memory, -1 );
			if ( array_key_exists( strtolower( $memory_last_char ), $memory_shorthands ) ) {
				$memory = (int) str_replace( $memory_last_char, '', $memory ) * $memory_shorthands[ strtolower( $memory_last_char ) ];
			}

			if ( 268435456 > $memory ) {
				wp_raise_memory_limit();
			}

			$time_limit = ini_get( 'max_execution_time' );
			if ( 300 > $time_limit && 0 != $time_limit ) {
				@set_time_limit( 0 );
			}
		}

		$this->available_languages = Fusion_Multilingual::get_available_languages();
		$this->active_language     = Fusion_Multilingual::get_active_language();
		$this->default_language    = Fusion_Multilingual::get_default_language();

		// If English is used then make this first in array order.  Also set starting language so that it is migrated first.
		if ( in_array( 'en', $this->available_languages ) ) {
			$en_array                  = [ 'en' ];
			$en_key                    = array_search( 'en', $this->available_languages );
			$available_languages_no_en = $this->available_languages;
			unset( $available_languages_no_en[ $en_key ] );
			$this->available_languages = array_merge( $en_array, $available_languages_no_en );
			$this->starting_language   = 'en';
		} else {
			// If not English then make default language first in array order.  Also set it to be starting language for migration.
			$default_array                  = [ $this->default_language ];
			$default_key                    = array_search( $this->default_language, $this->available_languages );
			$available_languages_no_default = $this->available_languages;
			unset( $available_languages_no_default[ $default_key ] );
			$this->available_languages = array_merge( $default_array, $available_languages_no_default );
			$this->starting_language   = $this->default_language;
		}

		if ( $_GET && isset( $_GET['avada_update'] ) ) {
			// Only continue if the URL is ?avada_update=1.
			if ( '1' != $_GET['avada_update'] ) {
				return;
			}
			// Only continue if we're updating to version 4.0.0.
			if ( ! isset( $_GET['ver'] ) || ( $this->version != $_GET['ver'] ) ) {
				return;
			}
			// Get the current step.
			if ( ! isset( $_GET['step'] ) ) {
				$this->step = 0;
			} else {
				$this->step = intval( $_GET['step'] );
			}
			if ( isset( $_GET['proceed'] ) && '0' == $_GET['proceed'] ) {
				$this->proceed = false;
			}

			$_get_lang = ( isset( $_GET['lang'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
			if ( isset( $_GET['lang'] ) && ! in_array( $_get_lang, [ '', 'en', 'all', null ] ) ) {
				Fusion_Multilingual::set_active_language( $_get_lang );
			}

			$this->options = get_option( Avada::get_option_name(), [] );
			$this->fields  = Avada_Options::get_option_fields();

			add_action( 'admin_init', [ $this, 'migrate_page' ] );

		}

	}

	/**
	 * The migration page.
	 *
	 * @access  public
	 */
	public function migrate_page() {
		ob_start();
		$this->setup_wizard_template();

		if ( isset( $this->steps[ $this->step ] ) && isset( $this->steps[ $this->step ]['callback'] ) ) {
			call_user_func( $this->steps[ $this->step ]['callback'] );
		}

		// Make sure we have not finished.
		if ( $this->step >= count( $this->steps ) - 1 ) {
			if ( empty( $this->available_languages ) || count( $this->available_languages ) == array_search( $this->active_language, $this->available_languages ) + 1 ) {
				$this->finished();
			}
		}

		exit;
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_template() {
		$current_step = intval( $this->step );
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title ><?php esc_html_e( 'Avada Theme Option Migration', 'Avada' ); ?></title >
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_head' ); ?>
				<style>
				.avada-setup {
					padding: 3% 20%;
					background-color: #f2f2f2;
					font-family: "Noto Sans", Roboto, "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;
					font-weight:300;
					font-size: 1.1em;
				}
				.update-content {
					max-width: 1150px;
					margin: auto;
				}
				.avada-logo {
					margin-bottom: 25px;
					text-align: center;
				}
				.avada-logo img {
					max-width: 226.5px;
					height: auto;
					vertical-align: bottom;
				}
				.avada-version {
					vertical-align: bottom;
				}
				.avada-version-inner {
					display: inline-block;
					margin-left: 20px;
					padding: 5px 10px;
					background-color: #65bc7b;
					-webkit-border-radius: 3px;
					border-radius: 3px;
					color: #fff;
				}
				.avada-content-wrapper {
					-webkit-border-radius: 3px;
					border-radius: 3px;
					-webkit-box-shadow: 1px 1px 3px 1px rgba(0,0,0,.2);
					box-shadow: 1px 1px 3px 1px rgba(0,0,0,.2);
				}
				.avada-welcome-msg {
					padding: 25px 35px;
					line-height: 1.6em;
					background-color: #65bc7b;
					color: #fff;
					font-style: italic;
					text-align: center;
				}
				.avada-migration-link {
					color: #fff;
				}
				.avada-setup-content {
					padding: 30px 10%;
					background: #fff;
				}
				.avada-update-progress-bar {
					height: 20px;
					position: relative;
					background: #F0F4C3;
					margin: 35px 0;
					padding: 1px;
				}
				.avada-update-progress-bar > span {
					display: block;
					height: 100%;
					background-color: #8bc34a;
					position: relative;
					overflow: hidden;
				}
				.tasks-list {
					padding: 0;
					list-style: none;
					<?php if ( 3 < $this->steps ) : ?>
						-webkit-column-count: 2;
						-moz-column-count: 2;
						column-count: 2;
					<?php endif; ?>
				}
				.tasks-list li .content {
					color: #333;
				}
				.tasks-list li {
					color: #f2f2f2;
				}
				.tasks-list li:before {
					vertical-align: middle;
					font-family: dashicons;
					content: "\f147";
					font-size: 1.4em
				}
				.tasks-list li.done {
					color: #65bc7b;
				}
				.tasks-list li.doing {
					color: #000;
				}
				.avada-save-options {
					display: inline-block;
					margin: 2em 0 0.67em 0;
					padding: 1em 2em;
					background-color: #65bc7b;
					color: #fff;
					text-decoration: none;
					-webkit-border-radius: 3px;
					border-radius: 3px;
					-webkit-transition: all 0.3s;
					-moz-transition: all 0.3s;
					-ms-transition: all 0.3s;
					transition: all 0.3s;
				}
				.avada-save-options.needs-update {
					background-color: #ef5350;
				}
				.avada-save-options:hover {
					background-color: #5aa86c;
				}
				.avada-save-options.needs-update:hover {
					background-color: #f44336;
				}
				.avada-footer {
					padding: 25px 35px;
					background: #f2f2f2;
					font-size: 0.8em;
					text-align: right;
				}
				.avada-themefusion-link {
					color: #000;
					text-decoration: none;
				}
				.avada-separator {
					padding: 0 10px;
				}
				.avada-heart {
					padding-left: 5px;
					vertical-align: middle;
				}
				.avada-heart:after {
					font-family: dashicons;
					content: "\f487";
				}
				</style>
			</head>
			<?php $version = Avada::get_theme_version(); ?>
			<body class="avada-setup wp-core-ui">
				<div class="update-content">
					<div class="avada-logo">
						<img src="<?php echo esc_url_raw( Avada::$template_dir_url ); ?>/assets/images/logo_migration.png" alt="<?php esc_html_e( 'Avada Logo', 'Avada' ); ?>" width="453" height="95">
						<span class="avada-version">
							<span class="avada-version-inner"><?php echo esc_attr( $version ); ?></span>
						</span>
					</div>
					<div class="avada-content-wrapper">
						<div class="avada-welcome-msg">
							<?php

							if ( ! empty( $this->available_languages ) ) {
								/* translators: Version number. */
								printf( esc_html__( 'We have an amazing new update in store for you! Avada %s includes our completely new Theme Options Panel and the brand new Fusion Builder. To enjoy the full experience, two primary conversion steps need to be performed. First your Theme Options database entries need to be converted (sequentially for each language, if you have a multi-lingual site). In a second step your shortcodes will be converted for the new builder. Thank you for choosing Avada!', 'Avada' ), esc_attr( $version ) );
							} else {
								/* translators: Version number. */
								printf( esc_html__( 'We have an amazing new update in store for you! Avada %s includes our completely new Theme Options Panel and the brand new Fusion Builder. To enjoy the full experience, two primary conversion steps need to be performed. First your Theme Options database entries need to be converted. In a second step your shortcodes will be converted for the new builder. Thank you for choosing Avada!', 'Avada' ), esc_attr( $version ) );
							}
							?>
						</div>
						<div class="avada-setup-content">
							<h1 style="font-size:1.3em;">
								<?php esc_html_e( 'Updating Avada Database Entries', 'Avada' ); ?>
							</h1>
							<?php if ( ! empty( $this->available_languages ) ) : ?>
								<?php /* translators: Language label. */ ?>
								<?php printf( esc_html__( 'Currently converting language: %s', 'Avada' ), '<strong>' . esc_attr( $this->active_language ) . '</strong>' ); ?>
							<?php endif; ?>
							<?php if ( $current_step >= count( $this->steps ) ) : ?>
								<p><?php esc_html_e( 'Done!', 'Avada' ); ?></p>
							<?php elseif ( $current_step >= ( count( $this->steps ) * 0.75 ) ) : ?>
								<p><?php esc_html_e( 'Almost there...', 'Avada' ); ?></p>
							<?php elseif ( $current_step >= ( count( $this->steps ) * 0.4 ) ) : ?>
								<p><?php esc_html_e( 'Halfway there... Patience Padawan, Patience.', 'Avada' ); ?></p>
							<?php else : ?>
								<p><?php esc_html_e( 'This may take a few minutes, please wait.', 'Avada' ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $this->available_languages ) && 1 < count( $this->available_languages ) ) : ?>
								<?php $current_lang_step = 0; ?>
								<?php $current_lang_step = array_search( $this->active_language, $this->available_languages ) + 1; ?>
								<div class="avada-update-progress-bar"><span style="width: <?php echo intval( 100 * $current_lang_step / count( $this->available_languages ) ); ?>%"></span></div>
								<?php /* translators: %1$s, %2$s: Numbers. */ ?>
								<p><?php printf( esc_html__( 'Converting language: %1$s of %2$s.', 'Avada' ), absint( $current_lang_step ), count( $this->available_languages ) ); ?></p>
							<?php endif; ?>

							<?php if ( $current_step <= count( $this->steps ) && isset( $this->steps[ $this->step ] ) ) : ?>
								<div class="avada-update-progress-bar"><span style="width: <?php echo intval( 100 * ( $current_step + 1 ) / count( $this->steps ) ); ?>%"></span></div>
								<?php /* translators: %1$s, %2$s: Numbers. */ ?>
								<p><?php printf( esc_html__( 'Updating Avada Database options: step %1$s of %2$s.', 'Avada' ), intval( $this->step + 1 ), count( $this->steps ) ); ?></p>
								<ul class="tasks-list">
									<?php foreach ( $this->steps as $key => $step ) : ?>
										<?php
										$li_class = '';
										if ( $key <= $current_step - 1 ) {
											$li_class = 'done';
										} elseif ( $key == $current_step ) {
											$li_class = 'doing';
										}
										?>
										<li class="<?php echo esc_attr( $li_class ); ?>">
											<span class="content"><?php echo esc_html( $step['description'] ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<?php if ( empty( $this->available_languages ) || count( $this->available_languages ) == array_search( $this->active_language, $this->available_languages ) + 1 ) : ?>
									<p><?php esc_html_e( 'Congratulations, Theme Options database enrties were successfully converted.', 'Avada' ); ?></p>
									<p><?php esc_html_e( 'For best experience, please clear your browser cache once.', 'Avada' ); ?></p>
									<p><?php esc_html_e( 'Dynamic-CSS caches have been auto reset.', 'Avada' ); ?></p>
								<?php endif; ?>
							<?php endif; ?>

							<?php if ( intval( $this->step ) >= count( $this->steps ) ) : ?>
								<?php if ( empty( $this->available_languages ) || count( $this->available_languages ) == array_search( $this->active_language, $this->available_languages ) + 1 ) : ?>
									<a class="avada-save-options" href="<?php echo esc_url_raw( admin_url( 'index.php?fusion_builder_migrate=1&ver=500' ) ); ?>">
										<?php esc_attr_e( 'Take Me To Shortcode Conversion', 'Avada' ); ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
						<?php /* translators: (heart) icon. */ ?>
						<div class="avada-footer"><a class="avada-themefusion-link" href="https://theme-fusion.com" target="_blank" rel="noopener noreferrer" title="ThemeFusion">ThemeFusion</a><span class="avada-separator">|</span><?php printf( esc_html__( 'Created with %s', 'Avada' ), '<span class="avada-heart"></span>' ); ?></div>
					</div>
					<?php echo $this->redirect_script(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</body>
		</html>
		<?php
	}

	/**
	 * Run when all steps have been completed.
	 */
	public function finished() {
		// Reset the CSS.
		update_option( 'fusion_dynamic_css_posts', [] );
	}

	/**
	 * Take care of redirecting to the next step.
	 */
	public function redirect_script() {
		$languages    = $this->available_languages;
		$current_step = intval( $this->step );
		$next_step    = $current_step + 1;
		// Add 500ms delay for refreshes.
		$delay = 500;
		if ( ( $current_step + 1 ) <= count( $this->steps ) && $this->proceed ) {
			// Redirect to next step.
			$lang = ( isset( $_GET['lang'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
			return '<script type="text/javascript">setTimeout(function () {window.location.href = "' . admin_url( 'index.php?avada_update=1&ver=400&lang=' . $lang . '&step=' . $next_step ) . '";}, ' . $delay . ');</script>';
		} else {
			// Check if this is a multilingual site.
			if ( ! empty( $languages ) ) {
				// Get the next language code.
				$next_lang = $this->get_next_language();
				if ( 'finished' === $next_lang ) {
					return;
				}
				return '<script type="text/javascript">setTimeout(function () {window.location.href = "' . admin_url( 'index.php?avada_update=1&ver=400&new=1&lang=' . $next_lang ) . '";}, ' . $delay . ');</script>';
			}
		}
	}

	/**
	 * Gets the next language in multilingual sites.
	 *
	 * @access  public
	 * @return  string
	 */
	public function get_next_language() {
		// Get all languages.
		$languages = $this->available_languages;
		// Get the current language key.
		if ( $_GET && isset( $_GET['lang'] ) ) {
			$current_lang_code = ( isset( $_GET['lang'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
			$current_lang_key  = (int) array_search( $current_lang_code, $languages );
			if ( false === $current_lang_key ) {
				$current_lang_key = null;
			}
		} else {
			$current_lang_key = null;
		}

		// If no language is currently defined, then proceed to 0.
		if ( null === $current_lang_key ) {
			$next_lang_key = 0;
		} else { // Proceed to next language.
			$next_lang_key = $current_lang_key + 1;
		}

		// Check if this key exists in the array.
		// If not, then proceed to the next one.
		if ( ! isset( $languages[ $next_lang_key ] ) ) {
			$next_lang_key++;
		}

		// If the next language is "en" skip it.
		if ( isset( $languages[ $next_lang_key ] ) && 'en' == $languages[ $next_lang_key ] ) {
			$next_lang_key++;
		}

		// Check if we're finished.
		// If yes, then return "finished".
		if ( $next_lang_key >= count( $languages ) ) {
			return 'finished';
		}

		// Return the code of the next language.
		return esc_attr( $languages[ $next_lang_key ] );

	}

	/**
	 * Debug helper.
	 * Creates an `avada-migration-debug.log` file in wp-content.
	 *
	 * @static
	 * @access  public
	 * @param  array $data The setting data.
	 */
	public static function generate_debug_log( $data = [] ) {
		$debug_log       = '';
		$debug_content   = '';
		$debug_file_path = WP_CONTENT_DIR . '/avada-migration-debug.log';
		if ( defined( 'AVADA_MIGRATION_DEBUG_LOG' ) && AVADA_MIGRATION_DEBUG_LOG ) {
			if ( ! empty( $data ) ) {
				$final_data = [];
				foreach ( $data as $item ) {
					$final_data[] = ( is_array( $item ) ) ? wp_json_encode( $item ) : $item;
				}
				$debug_log .= 'Old Setting: ' . $final_data[0] . "\r\n";
				$debug_log .= 'New Setting: ' . $final_data[1] . "\r\n";
				$debug_log .= 'Old Value: ' . $final_data[2] . "\r\n";
				$debug_log .= 'New Value: ' . $final_data[3] . "\r\n";
				$debug_log .= "\r\n";
			}
			// Write debug file contents.
			if ( file_exists( $debug_file_path ) ) {
				$debug_content = file_get_contents( $debug_file_path );
			}
			$debug_content .= $debug_log;
			file_put_contents( $debug_file_path, $debug_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
}
