<?php
/**
 * Single social-icon handler.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 */

/**
 * Single social-icon handler.
 *
 * @since 4.0.0
 */
class Fusion_Social_Icon {

	/**
	 * Array of our arguments for this icon.
	 *
	 * @static
	 * @access public
	 * @var array
	 */
	public static $args = [];

	/**
	 * The prefix that we'll be using for all our icon classes.
	 *
	 * @static
	 * @access public
	 * @var string
	 */
	public static $iconfont_prefix = 'fusion-icon-';

	/**
	 * Creates the markup for a single icon.
	 *
	 * @static
	 * @access public
	 * @param array $args The arguments array.
	 * @return string
	 */
	public static function get_markup( $args ) {
		$icon_options = [
			'class' => '',
			'style' => '',
		];
		if ( isset( $args['social_network'] ) ) {
			$icon_options['social_network'] = $args['social_network'];
		} elseif ( isset( $args['icon'] ) ) {
			$icon_options['social_network'] = $args['icon'];
		}
		$icon_options['social_link'] = '';
		if ( isset( $args['social_link'] ) ) {
			$icon_options['social_link'] = $args['social_link'];
		} elseif ( isset( $args['url'] ) ) {
			$icon_options['social_link'] = $args['url'];
		}
		if ( isset( $args['icon_color'] ) ) {
			$icon_options['icon_color'] = $args['icon_color'];
		}
		if ( isset( $args['box_color'] ) ) {
			$icon_options['box_color'] = $args['box_color'];
		}
		$icon_options['last'] = ( isset( $args['last'] ) ) ? $args['last'] : false;

		$custom         = '';
		$is_custom_icon = ( isset( $args['custom_source'] ) && isset( $args['custom_title'] ) ) ? true : false;
		// This is a custom icon.
		if ( $is_custom_icon ) {
			$custom = '<img src="' . $args['custom_source'] . '" style="width:auto;" alt="' . $args['custom_title'] . '" />';
		}

		if ( 'custom' === substr( $icon_options['social_network'], 0, 7 ) ) {
			$icon_options['class'] .= 'custom ';
			$tooltip                = esc_attr__( 'Custom', 'fusion-builder' );

			if ( isset( $args['custom_title'] ) ) {
				$tooltip = str_replace( 'custom', '', $args['custom_title'] );
			}
		} else {
			$tooltip = ucfirst( $icon_options['social_network'] );
			$tooltip = 'email' === $icon_options['social_network'] ? esc_attr__( 'Email', 'fusion-builder' ) : $icon_options['social_network'];
		}

		$icon_options['social_network'] = ( 'email' === $icon_options['social_network'] ) ? 'mail' : $icon_options['social_network'];

		$icon_options['class'] .= 'fusion-social-network-icon fusion-tooltip fusion-' . $icon_options['social_network'] . ' ' . self::$iconfont_prefix . $icon_options['social_network'];
		$icon_options['class'] .= ( $args['last'] ) ? ' fusion-last-social-icon' : '';

		$icon_options['href'] = $icon_options['social_link'];

		if ( self::$args['linktarget'] ) {
			$icon_options['target'] = '_blank';

			if ( 'facebook' !== $icon_options['social_network'] || isset( $args['icon'] ) ) {
				$icon_options['rel'] = 'noopener noreferrer';
			}
		}

		if ( 'mail' === $icon_options['social_network'] ) {

			if ( 'http' === substr( $icon_options['social_link'], 0, 4 ) ) {
				$icon_options['href'] = $icon_options['social_link'];
			} else {
				if ( false !== strpos( $icon_options['social_link'], 'body=' ) ) {
					$icon_options['href'] = 'mailto:' . str_replace( 'mailto:', '', $icon_options['social_link'] );
				} else {
					if ( apply_filters( 'fusion_disable_antispambot', false ) ) {
						$icon_options['href'] = 'mailto:' . str_replace( 'mailto:', '', $icon_options['social_link'] );
					} else {
						$icon_options['href'] = 'mailto:' . antispambot( str_replace( 'mailto:', '', $icon_options['social_link'] ) );
					}
				}
			}

			$icon_options['target'] = '_self';
		}

		if ( fusion_library()->get_option( 'nofollow_social_links' ) ) {
			$icon_options['rel'] = 'nofollow';
		}

		if ( isset( $args['icon_color'] ) && $args['icon_color'] ) {
			$icon_options['style'] .= 'color:' . $args['icon_color'] . ';';
		}

		if ( $is_custom_icon ) {
			$icon_options['style'] .= 'position:relative;';
		}

		if ( ! $is_custom_icon && self::$args['icon_boxed'] && isset( $args['box_color'] ) && $args['box_color'] && ! is_array( $args['box_color'] ) ) {
			$icon_options['style'] .= 'background-color:' . $args['box_color'] . ';border-color:' . $args['box_color'] . ';';
		}

		if ( ! $is_custom_icon && self::$args['icon_boxed'] && ( isset( self::$args['icon_boxed_radius'] ) && ( self::$args['icon_boxed_radius'] || '0' === self::$args['icon_boxed_radius'] ) ) ) {
			self::$args['icon_boxed_radius'] = ( 'round' === self::$args['icon_boxed_radius'] ) ? '50%' : self::$args['icon_boxed_radius'];
			$icon_options['style']          .= 'border-radius:' . self::$args['icon_boxed_radius'] . ';';
		}

		if ( 'none' !== strtolower( self::$args['tooltip_placement'] ) ) {
			$icon_options['data-placement'] = strtolower( self::$args['tooltip_placement'] );
			if ( 'Youtube' === $tooltip ) {
				$tooltip = 'YouTube';
			} elseif ( 'Linkedin' === $tooltip ) {
				$tooltip = 'LinkedIn';
			}

			$icon_options['data-title']  = $tooltip;
			$icon_options['data-toggle'] = 'tooltip';
		}

		$icon_options['title'] = $tooltip;

		$icon_options = apply_filters( 'fusion_attr_social-icons-class-icon', $icon_options ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		$properties = '';

		$not_allowed_attributes = [ 'last', 'box_color', 'icon_color', 'social_link', 'social_network' ];
		foreach ( $icon_options as $name => $value ) {
			if ( ! in_array( $name, $not_allowed_attributes, true ) ) {
				$properties .= ! empty( $value ) ? ' ' . esc_html( $name ) . '="' . esc_attr( $value ) . '"' : esc_html( " {$name}" );
			}
		}

		return '<a ' . $properties . '><span class="screen-reader-text">' . $tooltip . '</span>' . $custom . '</a>';

	}
}
