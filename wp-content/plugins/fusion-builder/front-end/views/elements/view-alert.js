var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Alert Element View.
		FusionPageBuilder.fusion_alert = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				// Create attribute objects
				attributes.attr           = this.buildAttr( atts.values );
				attributes.buttonStyles   = this.buildButtonStyles( atts.values );
				attributes.contentAttr    = this.buildContentAttr( atts.values );
				attributes.contentStyles  = this.buildContentStyles( atts.values );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.values = atts.values;

				return attributes;
			},

			/**
			 * Modify the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.alert_class = 'info';

				switch ( values.type ) {
				case 'general':
					values.alert_class = 'info';
					if ( ! values.icon || 'none' !== values.icon ) {
						values.icon = 'fa-info-circle';
					}
					break;
				case 'error':
					values.alert_class = 'danger';
					if ( ! values.icon || 'none' !== values.icon ) {
						values.icon = 'fa-exclamation-triangle';
					}
					break;
				case 'success':
					values.alert_class = 'success';
					if ( ! values.icon || 'none' !== values.icon ) {
						values.icon = 'fa-check-circle';
					}
					break;
				case 'notice':
					values.alert_class = 'warning';
					if ( ! values.icon || 'none' !== values.icon ) {
						values.icon = 'fa-lg fa-cog';
					}
					break;
				case 'blank':
					values.alert_class = 'blank';
					break;
				case 'custom':
					values.alert_class = 'custom';
					break;
				}

				// Make sure the title text is not wrapped with an unattributed p tag.
				if ( 'undefined' !== typeof values.element_content ) {
					values.element_content = values.element_content.trim();
					values.element_content = values.element_content.replace( /(<p[^>]+?>|<p>|<\/p>)/img, '' );
				}
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr         = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-alert alert fusion-live-alert fusion-alert-cid' + this.model.get( 'cid' ),
						style: ''
					} ),
					alertClass   = values.alert_class;

				if ( 'capitalize' === values.text_transform ) {
					alertClass += ' fusion-alert-capitalize';
				}

				if ( 'yes' === values.dismissable ) {
					alertClass += ' alert-dismissable';
				}

				attr[ 'class' ] += ' alert-' + alertClass;
				attr[ 'class' ] += ' fusion-alert-' + values.text_align;
				attr[ 'class' ] += ' ' + values.type;

				if ( 'yes' === values.box_shadow ) {
					attr[ 'class' ] += ' alert-shadow';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				attr = _.fusionAnimations( values, attr );

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildContentStyles: function( values ) {
				var alertClass   = values.alert_class,
					args         = {},
					styles       = '',
					cid          = this.model.get( 'cid' ),
					backgroundColor,
					accentColor;

				if ( 'custom' === alertClass ) {
					values.border_size    = parseFloat( values.border_size ) + 'px';
					args.background_color = values.background_color;
					args.accent_color     = values.accent_color;
					args.border_size      = values.border_size;
				} else {
					backgroundColor       = 'var(--' + alertClass + '_bg_color)';
					accentColor           = 'var(--' + alertClass + '_accent_color)';
					args.background_color = backgroundColor;
					args.accent_color     = accentColor;
					args.border_size      = parseFloat( window.fusionAllElements.fusion_alert.defaults.border_size ) + 'px';
				}

				styles = '<style type="text/css">';
				styles += '.fusion-alert.alert.fusion-alert-cid' + cid + '{';
				styles += 'background-color:' + args.background_color + ';';
				styles += 'color:' + args.accent_color + ';';
				styles += 'border-color:' + args.accent_color + ';';
				styles += 'border-width:' + args.border_size + ';';
				styles += '}';
				styles += '</style>';

				return styles;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildContentAttr: function() {
				var contentAttr = _.fusionInlineEditor( {
					cid: this.model.get( 'cid' ),
					'disable-return': true,
					'disable-extra-spaces': true,
					toolbar: 'simple'
				}, {
					class: 'fusion-alert-content'
				} );
				return contentAttr;
			},

			/**
			 * Builds the styles.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {string}
			 */
			buildButtonStyles: function( values ) {
				if ( 'custom' === values.alert_class ) {
					return 'color:' + values.accent_color + ';border-color:' + values.accent_color + ';';
				}
				return '';
			}
		} );
	} );
}( jQuery ) );
