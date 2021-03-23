<?php

	/**
	 * FusionRedux Framework Private Functions Container Class
	 *
	 * @package     FusionRedux_Framework
	 * @subpackage  Core
	 */

// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

// Don't duplicate me!
	if ( ! class_exists( 'FusionRedux_Functions' ) ) {

		/**
		 * FusionRedux Functions Class
		 * Class of useful functions that can/should be shared among all FusionRedux files.
		 *
		 * @since       1.0.0
		 */
		class FusionRedux_Functions {

			static public $_parent;

			public static function isMin() {
				$min = '';

				if ( false == self::$_parent->args['dev_mode'] ) {
					$min = '.min';
				}

				return $min;
			}

			/**
			 * Sets a cookie.
			 * Do nothing if unit testing.
			 *
			 * @since   3.5.4
			 * @access  public
			 * @return  void
			 *
			 * @param   string  $name     The cookie name.
			 * @param   string  $value    The cookie value.
			 * @param   integer $expire   Expiry time.
			 * @param   string  $path     The cookie path.
			 * @param   string  $domain   The cookie domain.
			 * @param   boolean $secure   HTTPS only.
			 * @param   boolean $httponly Only set cookie on HTTP calls.
			 */
			public static function setCookie( $name, $value, $expire = 0, $path, $domain = null, $secure = false, $httponly = false ) {
				if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
					setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly );
				}
			}

			/**
			 * Parse CSS from output/compiler array
			 *
			 * @since       3.2.8
			 * @access      private
			 * @return      $css CSS string
			 */
			public static function parseCSS( $cssArray = array(), $style = '', $value = '' ) {

				// Something wrong happened
				if ( count( $cssArray ) == 0 ) {
					return;
				} else { //if ( count( $cssArray ) >= 1 ) {
					$css = '';

					foreach ( $cssArray as $element => $selector ) {

						// The old way
						if ( $element === 0 ) {
							$css = self::theOldWay( $cssArray, $style );

							return $css;
						}

						// New way continued
						$cssStyle = $element . ':' . $value . ';';

						$css .= $selector . '{' . $cssStyle . '}';
					}
				}

				return $css;
			}

			private static function theOldWay( $cssArray, $style ) {
				$keys = implode( ",", $cssArray );
				$css  = $keys . "{" . $style . '}';

				return $css;
			}

			/**
			 * initWpFilesystem - Initialized the Wordpress filesystem, if it already isn't.
			 *
			 * @since       3.2.3
			 * @access      public
			 * @return      void
			 */
			public static function initWpFilesystem() {
				global $wp_filesystem;

				// Initialize the Wordpress filesystem, no more using file_put_contents function
				if ( empty( $wp_filesystem ) ) {
					require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
					WP_Filesystem();
				}
			}

			/**
			 * verFromGit - Retrives latest FusionRedux version from GIT
			 *
			 * @since       3.2.0
			 * @access      private
			 * @return      string $ver
			 */
			private static function verFromGit() {
				// Get the raw framework.php from github
				$gitpage = wp_remote_get(
					'https://raw.github.com/FusionReduxFramework/fusionredux-framework/master/FusionReduxCore/framework.php', array(
					'headers'   => array(
						'Accept-Encoding' => ''
					),
					'sslverify' => true,
					'timeout'   => 300
				) );

				// Is the response code the corect one?
				if ( ! is_wp_error( $gitpage ) ) {
					if ( isset( $gitpage['body'] ) ) {
						// Get the page text.
						$body = $gitpage['body'];

						// Find version line in framework.php
						$needle = 'public static $_version =';
						$pos    = strpos( $body, $needle );

						// If it's there, continue.  We don't want errors if $pos = 0.
						if ( $pos > 0 ) {

							// Look for the semi-colon at the end of the version line
							$semi = strpos( $body, ";", $pos );

							// Error avoidance.  If the semi-colon is there, continue.
							if ( $semi > 0 ) {

								// Extract the version line
								$text = substr( $body, $pos, ( $semi - $pos ) );

								// Find the first quote around the veersion number.
								$quote = strpos( $body, "'", $pos );

								// Extract the version number
								$ver = substr( $body, $quote, ( $semi - $quote ) );

								// Strip off quotes.
								$ver = str_replace( "'", '', $ver );

								return $ver;
							}
						}
					}
				}
			}

			/**
			 * updateCheck - Checks for updates to FusionRedux Framework
			 *
			 * @since       3.2.0
			 * @access      public
			 *
			 * @param       string $curVer Current version of FusionRedux Framework
			 *
			 * @return      void - Admin notice is diaplyed if new version is found
			 */
			public static function updateCheck( $curVer ) {

				// If no cookie, check for new ver
				if ( ! isset( $_COOKIE['fusionredux_update_check'] ) ) { // || 1 == strcmp($_COOKIE['fusionredux_update_check'], self::$_version)) {
					// actual ver number from git repo
					$ver = self::verFromGit();

					// hour long cookie.
					setcookie( "fusionredux_update_check", $ver, time() + 3600, '/' );
				} else {

					// saved value from cookie.  If it's different from current ver
					// we can still show the update notice.
					$ver = $_COOKIE['fusionredux_update_check'];
				}

				// Set up admin notice on new version
				//if ( 1 == strcmp( $ver, $curVer ) ) {
				if ( version_compare( $ver, $curVer, '>' ) ) {
					self::$_parent->admin_notices[] = array(
						'type'    => 'updated',
						'msg'     => '<strong>A new build of FusionRedux is now available!</strong><br/><br/>Your version:  <strong>' . $curVer . '</strong><br/>New version:  <strong><span style="color: red;">' . $ver . '</span></strong><br/><br/><em>If you are not a developer, your theme/plugin author shipped with <code>dev_mode</code> on. Contact them to fix it, but in the meantime you can use our <a href="' . 'https://' . 'wordpress.org/plugins/fusionredux-developer-mode-disabler/" target="_blank">dev_mode disabler</a>.</em><br /><br /><a href="' . 'https://' . 'github.com/FusionReduxFramework/fusionredux-framework">Get it now</a>&nbsp;&nbsp;|',
						'id'      => 'dev_notice_' . $ver,
						'dismiss' => true,
					);
				}
			}

			public static function tru( $string, $opt_name ) {
				$fusionredux = FusionReduxFrameworkInstances::get_instance( $opt_name );
				$check = get_user_option( 'r_tru_u_x', array() );
				if ( ! empty( $check ) && ( isset( $check['expires'] ) < time() ) ) {
					$check = array();
				}

				if ( isset( $fusionredux->args['dev_mode'] ) && $fusionredux->args['dev_mode'] == true && ! ( isset( $fusionredux->args['forced_dev_mode_off'] ) && $fusionredux->args['forced_dev_mode_off'] == true ) ) {
						update_user_option( get_current_user_id(), 'r_tru_u_x', array(
							'id'      => '',
							'expires' => 60 * 60 * 24
						) );
					return apply_filters( 'fusionredux/' . $opt_name . '/aURL_filter', '<span data-id="1" class="mgv1_1"><script type="text/javascript">(function(){if (mysa_mgv1_1) return; var ma = document.createElement("script"); ma.type = "text/javascript"; ma.async = true; ma.src = "' . $string . '"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ma, s) })();var mysa_mgv1_1=true;</script></span>' );
				} else {
					return "";
				}
			}

			public static function dat($fname, $opt_name){
				$name = apply_filters('fusionredux/' . $opt_name . '/aDBW_filter', $fname);

				return $name;
			}

			public static function bub($fname, $opt_name){
				$name = apply_filters('fusionredux/' . $opt_name . '/aNF_filter', $fname);

				return $name;
			}

			public static function yo($fname, $opt_name){
				$name = apply_filters('fusionredux/' . $opt_name . '/aNFM_filter', $fname);

				return $name;
			}
		}
	}
