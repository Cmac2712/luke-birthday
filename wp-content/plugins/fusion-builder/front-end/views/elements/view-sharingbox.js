/* global cssua */
/* jshint -W107 */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Sharing Box View.
		FusionPageBuilder.fusion_sharing = FusionPageBuilder.ElementView.extend( {

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			beforePatch: function() {
				var tooltips = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( this.$el ).find( '.fusion-social-networks [data-toggle="tooltip"]' );

				tooltips.tooltip( 'destroy' );
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			afterPatch: function() {
				var tooltips = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( this.$el ).find( '.fusion-social-networks [data-toggle="tooltip"]' );

				setTimeout( function() {
					tooltips.tooltip( {
						container: 'body'
					} );
				}, 150 );

			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values and extras.
				this.validateValuesExtras( atts.values, atts.extras );

				// Create attribute objects.
				attributes.shortcodeAttr      = this.buildShortcodeAttr( atts.values );
				attributes.socialNetworksAttr = this.buildSocialNetworksAttr( atts.values );
				attributes.taglineAttr        = this.buildTaglineAttr( atts.values );
				attributes.icons              = this.buildIcons( atts.values );
				attributes.tagline            = atts.values.tagline;

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @param {Object} extras - Extra args.
			 * @return {void}
			 */
			validateValuesExtras: function( values, extras ) {
				extras.linktarget         = extras.linktarget ? '_blank' : '_self';
				values.icons_boxed_radius = _.fusionValidateAttrValue( values.icons_boxed_radius, 'px' );
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildShortcodeAttr: function( values ) {
				var sharingboxShortcode = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'share-box fusion-sharing-box'
				} );

				if ( 'yes' === values.icons_boxed ) {
					sharingboxShortcode[ 'class' ] += ' boxed-icons';
				}

				if ( '' !== values.backgroundcolor ) {
					sharingboxShortcode.style = 'background-color:' + values.backgroundcolor + ';';

					if ( 'transparent' === values.backgroundcolor || 0 === jQuery.Color( values.backgroundcolor ).alpha() ) {
						sharingboxShortcode.style += 'padding:0;';
					}
				}

				if ( '' !== values[ 'class' ] ) {
					sharingboxShortcode[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					sharingboxShortcode[ 'class' ] += ' ' + values.id;
				}

				sharingboxShortcode[ 'data-title' ]       = values.title;
				sharingboxShortcode[ 'data-description' ] = values.description;
				sharingboxShortcode[ 'data-link' ]        = values.link;
				sharingboxShortcode[ 'data-image' ]       = values.pinterest_image;

				return sharingboxShortcode;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildSocialNetworksAttr: function( values ) {
				var sharingboxShortcodeSocialNetworks = {
					class: 'fusion-social-networks'
				};

				if ( 'yes' === values.icons_boxed ) {
					sharingboxShortcodeSocialNetworks[ 'class' ] += ' boxed-icons';
				}

				if ( '' === values.tagline ) {
					sharingboxShortcodeSocialNetworks.style = 'text-align: inherit;';
				}

				return sharingboxShortcodeSocialNetworks;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildTaglineAttr: function( values ) {
				var sharingboxShortcodeTagline = {
						class: 'tagline'
					},
					that = this;

				if ( '' !== values.tagline_color ) {
					sharingboxShortcodeTagline.style = 'color:' + values.tagline_color + ';';
				}

				sharingboxShortcodeTagline = _.fusionInlineEditor( {
					param: 'tagline',
					cid: that.model.get( 'cid' ),
					toolbar: false
				}, sharingboxShortcodeTagline );

				return sharingboxShortcodeTagline;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildIconAttr: function( values ) {
				var sharingboxShortcodeTagline = {
					class: 'tagline'
				};

				if ( '' !== values.tagline_color ) {
					sharingboxShortcodeTagline.style = 'color:' + values.tagline_color + ';';
				}

				return sharingboxShortcodeTagline;
			},

			/**
			 * Builds HTML for the icons.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @param {Object} extras - Extra args.
			 * @return {string}
			 */
			buildIcons: function( values ) {
				var icons            = '',
					iconColors       = values.icon_colors,
					boxColors        = values.box_colors,
					useBrandColors   = false,
					numOfIconColors,
					numOfBoxColors,
					socialNetworks,
					socialNetworksCount,
					i,
					description,
					link,
					title,
					image,
					socialLink,
					sharingboxShortcodeIcon,
					iconOptions,
					socialIconBoxedColors,
					network,
					tooltip;

				if ( 'brand' === values.color_type ) {
					useBrandColors = true;

					// Get a list of all the available social networks.
					socialIconBoxedColors = _.fusionSocialIcons( false, true );
					socialIconBoxedColors.mail = {
						label: 'Email Address',
						color: '#000000'
					};

				}

				iconColors = iconColors.split( '|' );
				boxColors  = boxColors.split( '|' );

				numOfIconColors     = iconColors.length;
				numOfBoxColors      = boxColors.length;
				socialNetworks      = values.social_networks.split( '|' );
				socialNetworksCount = socialNetworks.length;

				for ( i = 0; i < socialNetworksCount; i++ ) {
					network = socialNetworks[ i ];

					if ( true === useBrandColors ) {
						iconOptions = {
							social_network: network,
							icon_color: ( 'yes' === values.icons_boxed ) ? '#ffffff' : socialIconBoxedColors[ network ].color,
							box_color: ( 'yes' === values.icons_boxed ) ? socialIconBoxedColors[ network ].color : ''
						};

					} else {
						iconOptions = {
							social_network: network,
							icon_color: i < iconColors.length ? iconColors[ i ] : '',
							box_color: i < boxColors.length ? boxColors[ i ] : ''
						};

						if ( 1 === numOfIconColors ) {
							iconOptions.icon_color = iconColors[ 0 ];
						}
						if ( 1 === numOfBoxColors ) {
							iconOptions.box_color = boxColors[ 0 ];
						}
					}

					// sharingboxShortcodeIcon attributes
					description = values.description;
					link        = values.link;
					title       = values.title;
					image       = _.fusionRawUrlEncode( values.pinterest_image );

					sharingboxShortcodeIcon = {
						class: 'fusion-social-network-icon fusion-tooltip fusion-' + iconOptions.social_network + ' fusion-icon-' + iconOptions.social_network
					};

					socialLink = '';
					switch ( iconOptions.social_network ) {
					case 'facebook':
						socialLink = 'https://m.facebook.com/sharer.php?u=' + link;
						if ( cssua.ua.mobile ) {
							socialLink = 'http://www.facebook.com/sharer.php?m2w&s=100&p&#91;url&#93;=' + link + '&p&#91;images&#93;&#91;title&#93;=' + _.fusionRawUrlEncode( title );
						}
						break;
					case 'twitter':
						socialLink = 'https://twitter.com/share?text=' + _.fusionRawUrlEncode( title ) + '&url=' + _.fusionRawUrlEncode( link );
						break;
					case 'linkedin':
						socialLink = 'https://www.linkedin.com/shareArticle?mini=true&url=' + _.fusionRawUrlEncode( link ) + '&amp;title=' + _.fusionRawUrlEncode( title ) + '&amp;summary=' + _.fusionRawUrlEncode( description );
						break;
					case 'reddit':
						socialLink = 'http://reddit.com/submit?url=' + link + '&amp;title=' + title;
						break;
					case 'tumblr':
						socialLink = 'http://www.tumblr.com/share/link?url=' + _.fusionRawUrlEncode( link ) + '&amp;name=' + _.fusionRawUrlEncode( title ) + '&amp;description=' + _.fusionRawUrlEncode( description );
						break;
					case 'pinterest':
						socialLink = 'http://pinterest.com/pin/create/button/?url=' + _.fusionRawUrlEncode( link ) + '&amp;description=' + _.fusionRawUrlEncode( description ) + '&amp;media=' + image;
						break;
					case 'vk':
						socialLink = 'http://vkontakte.ru/share.php?url=' + _.fusionRawUrlEncode( link ) + '&amp;title=' + _.fusionRawUrlEncode( title ) + '&amp;description=' + _.fusionRawUrlEncode( description );
						break;
					case 'mail':
						socialLink = 'mailto:?subject=' + _.fusionRawUrlEncode( title ) + '&body=' + _.fusionRawUrlEncode( link );
						break;
					}

					sharingboxShortcodeIcon.href   = socialLink;
					sharingboxShortcodeIcon.target = ( values.linktarget && 'mail' !== iconOptions.social_network ) ? '_blank' : '_self';

					if ( '_blank' === sharingboxShortcodeIcon.target ) {
						sharingboxShortcodeIcon.rel = 'noopener noreferrer';
					}

					sharingboxShortcodeIcon.style = ( iconOptions.icon_color ) ? 'color:' + iconOptions.icon_color + ';' : '';

					if ( values.icons_boxed && 'yes' === values.icons_boxed && iconOptions.box_color ) {
						sharingboxShortcodeIcon.style += 'background-color:' + iconOptions.box_color + ';border-color:' + iconOptions.box_color + ';';
					}

					if ( ( 'yes' === values.icons_boxed && values.icons_boxed_radius ) || '0' === values.icons_boxed_radius ) {
						if ( 'round' === values.icons_boxed_radius ) {
							values.icons_boxed_radius = '50%';
						}
						sharingboxShortcodeIcon.style += 'border-radius:' + values.icons_boxed_radius + ';';
					}

					sharingboxShortcodeIcon[ 'data-placement' ] = values.tooltip_placement;
					tooltip = iconOptions.social_network;

					sharingboxShortcodeIcon[ 'data-title' ] = _.fusionUcFirst( tooltip );
					sharingboxShortcodeIcon.title         = _.fusionUcFirst( tooltip );
					sharingboxShortcodeIcon[ 'aria-label' ] = _.fusionUcFirst( tooltip );

					if ( 'none' !== values.tooltip_placement ) {
						sharingboxShortcodeIcon[ 'data-toggle' ] = 'tooltip';
					}
					icons += '<a ' + _.fusionGetAttributes( sharingboxShortcodeIcon ) + '></a>';
				}

				return icons;
			}

		} );
	} );
}( jQuery ) );
