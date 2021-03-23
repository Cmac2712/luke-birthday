/* global fusionAllElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Separator View.
		FusionPageBuilder.fusion_separator = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				attributes.values = atts.values;

				// Create attribute objects
				attributes.attr            = this.buildAttr( atts.values );
				attributes.iconWrapperAttr = this.buildIconWrapperAttr( atts.values );
				attributes.iconAttr        = this.buildIconAttr( atts.values );

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.border_size   = _.fusionValidateAttrValue( values.border_size, 'px' );
				values.width         = _.fusionValidateAttrValue( values.width, 'px' );
				values.top_margin    = _.fusionValidateAttrValue( values.top_margin, 'px' );
				values.bottom_margin = _.fusionValidateAttrValue( values.bottom_margin, 'px' );

				if ( '0' === values.icon_circle ) {
					values.icon_circle = 'no';
				}

				if ( '' !== values.style ) {
					values.style_type = values.style;
				} else if ( 'default' === values.style_type ) {
					values.style_type = fusionAllElements.fusion_separator.defaults.style_type;
				}

				values.style_type = values.style_type.replace( / /g, '|' );

				if ( '' !== values.bottom ) {
					values.bottom_margin = _.fusionValidateAttrValue( values.bottom, 'px' );
				}

				if ( '' !== values.color ) {
					values.sep_color = values.color;
				}

				if ( '' !== values.top ) {
					values.top_margin = _.fusionValidateAttrValue( values.top, 'px' );

					if ( '' === values.bottom && 'none' !== values.style ) {
						values.bottom_margin = _.fusionValidateAttrValue( values.top, 'px' );
					}
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
				var attr,
					styles,
					shadow;

				attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'fusion-separator ' + values[ 'class' ],
					style: ''
				} );

				if ( '' === values.width || '100%' === values.width ) {
					attr[ 'class' ] += ' fusion-full-width-sep';
				}

				styles = values.style_type.split( '|' );

				if ( -1 === jQuery.inArray( 'none', styles ) && -1 === jQuery.inArray( 'single', styles ) && -1 === jQuery.inArray( 'double', styles ) && -1 === jQuery.inArray( 'shadow', styles ) ) {
					styles.push( 'single' );
				}
				jQuery.each( styles, function( key, style ) {
					attr[ 'class' ] += ' sep-' + style;
				} );

				if ( values.sep_color ) {
					if ( 'shadow' === values.style_type ) {
						shadow = 'background:radial-gradient(ellipse at 50% -50% , ' + values.sep_color + ' 0px, rgba(255, 255, 255, 0) 80%) repeat scroll 0 0 rgba(0, 0, 0, 0);';

						attr.style  = shadow;
						attr.style += shadow.replace( 'radial-gradient', '-webkit-radial-gradient' );
						attr.style += shadow.replace( 'radial-gradient', '-moz-radial-gradient' );
						attr.style += shadow.replace( 'radial-gradient', '-o-radial-gradient' );
					} else if ( 'none' !== values.style_type ) {
						attr.style = 'border-color:' + values.sep_color + ';';
					}
				}

				if ( -1 !== jQuery.inArray( 'single', styles ) ) {
					attr.style += 'border-top-width:' + values.border_size + ';';
				}

				if ( -1 !== jQuery.inArray( 'double', styles )  ) {
					attr.style += 'border-top-width:' + values.border_size + ';border-bottom-width:' + values.border_size + ';';
				}

				if ( 'center' === values.alignment ) {
					attr.style += 'margin-left: auto;margin-right: auto;';
				} else if ( 'right' === values.alignment ) {
					attr.style += 'float:right;';
					attr[ 'class' ] += ' fusion-clearfix';
				}

				attr.style += 'margin-top:' + values.top_margin + ';';

				if ( '' !== values.bottom_margin ) {
					attr.style += 'margin-bottom:' + values.bottom_margin + ';';
				}

				if ( '' !== values.width ) {
					attr.style += 'width:100%;max-width:' + values.width + ';';
				}

				attr.id = values.id;

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildIconWrapperAttr: function( values ) {
				var circleColor,
					marginTop,
					styles = values.style_type.split( '|' ),
					iconWrapperAttr = {
						class: 'icon-wrapper'
					};

				circleColor = ( 'no' === values.icon_circle ) ? 'transparent' : values.sep_color;

				iconWrapperAttr.style = 'border-color:' + circleColor + ';';

				if ( values.icon_circle_color && 'no' !== values.icon_circle ) {
					iconWrapperAttr.style += 'background-color:' + values.icon_circle_color + ';';
				}

				if ( values.icon_size ) {
					iconWrapperAttr.style += 'width: calc(' + values.icon_size + 'px * 1.63 );';
					iconWrapperAttr.style += 'height: calc(' + values.icon_size + 'px * 1.63 );';
				}

				if ( values.border_size ) {
					iconWrapperAttr.style += 'border-width:' + values.border_size + ';';
					iconWrapperAttr.style += 'padding:' + values.border_size + ';';
				}

				if ( -1 !== jQuery.inArray( 'single', styles ) ) {
					marginTop = parseInt( values.border_size, 10 ) / 2;
					iconWrapperAttr.style += 'margin-top:-' + marginTop + 'px;';
				}

				return iconWrapperAttr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildIconAttr: function( values ) {
				var iconAttr = {
					class: _.fusionFontAwesome( values.icon ),
					style: 'color:' + values.sep_color + ';'
				};

				if ( '' !== values.icon_size ) {
					iconAttr.style += 'font-size:' + values.icon_size + 'px;';
				}

				return iconAttr;
			}
		} );
	} );
}( jQuery ) );
