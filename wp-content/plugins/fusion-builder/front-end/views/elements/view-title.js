/* global fusionAllElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Title View
		FusionPageBuilder.fusion_title = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				// Create attribute objects
				attributes.attr          = this.buildAttr( atts.values );
				attributes.headingAttr   = this.buildHeadingAttr( atts.values );
				attributes.animatedAttr  = this.buildAnimatedAttr( atts.values );
				attributes.rotatedAttr   = this.buildRotatedAttr( atts.values );
				attributes.separatorAttr = this.builderSeparatorAttr( atts.values );
				attributes.style         = this.buildStyleBlock( atts.values, atts.extras );

				// Any extras that need passed on.
				attributes.cid            = this.model.get( 'cid' );
				attributes.output         = atts.values.element_content;
				attributes.style_type     = atts.values.style_type;
				attributes.size           = atts.values.size;
				attributes.content_align  = atts.values.content_align;
				attributes.title_type     = atts.values.title_type;
				attributes.before_text    = atts.values.before_text;
				attributes.highlight_text = atts.values.highlight_text;
				attributes.after_text     = atts.values.after_text;
				attributes.rotation_text  = atts.values.rotation_text;

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
				values.margin_top           = _.fusionValidateAttrValue( values.margin_top, 'px' );
				values.margin_bottom        = _.fusionValidateAttrValue( values.margin_bottom, 'px' );
				values.margin_top_mobile    = _.fusionValidateAttrValue( values.margin_top_mobile, 'px' );
				values.margin_bottom_mobile = _.fusionValidateAttrValue( values.margin_bottom_mobile, 'px' );

				if ( 'rotating' === values.title_type && '' !== values.rotation_text ) {
					values.rotation_text = values.rotation_text.split( '|' );
				} else {
					values.rotation_text = [];
				}

				if ( 'text' !== values.title_type ) {
					values.style_type = 'none';
				}

				if ( 'default' === values.style_type ) {
					values.style_type = fusionAllElements.fusion_title.defaults.style_type;
				}

				if ( 1 === values.style_type.split( ' ' ).length ) {
					values.style_type += ' solid';
				}

				// Make sure the title text is not wrapped with an unattributed p tag.
				if ( 'undefined' !== typeof values.element_content ) {
					values.element_content = values.element_content.trim();
					values.element_content = values.element_content.replace( /(<p[^>]+?>|<p>|<\/p>)/img, '' );
				}

				if ( 'undefined' !== typeof values.font_size && '' !== values.font_size ) {
					values.font_size = _.fusionGetValueWithUnit( values.font_size );
				}

				if ( 'undefined' !== typeof values.letter_spacing && '' !== values.letter_spacing ) {
					values.letter_spacing = _.fusionGetValueWithUnit( values.letter_spacing );
				}
			},

			buildStyleBlock: function( values, extras ) {
				var style = '<style type="text/css">',
					bottomHighlights = [ 'underline', 'double_underline', 'underline_zigzag', 'underline_zigzag', 'curly' ];

				if ( 'highlight' === values.title_type && '' !== values.highlight_color ) {
					style += '.fusion-title.fusion-title-cid' + this.model.get( 'cid' ) + ' svg path{stroke:' + values.highlight_color + '!important}';
				}

				if ( 'highlight' === values.title_type && '' !== values.highlight_top_margin && bottomHighlights.includes( values.highlight_effect ) ) {
					style += '.fusion-title.fusion-title-cid' + this.model.get( 'cid' ) + ' svg{margin-top:' + values.highlight_top_margin + 'px!important}';
				}

				if ( 'highlight' === values.title_type && '' !== values.highlight_width ) {
					style += '.fusion-title.fusion-title-cid' + this.model.get( 'cid' ) + ' svg path{stroke-width:' + values.highlight_width + '!important}';
				}

				if ( 'rotating' === values.title_type && '' !== values.text_color && ( 'clipIn' === values.rotation_effect || 'typeIn' === values.rotation_effect ) ) {
					style += '.fusion-title.fusion-title-cid' + this.model.get( 'cid' ) + ' .fusion-animated-texts-wrapper::before{background-color:' + values.text_color + '!important}';
				}

				if ( ! ( '' === values.margin_top_mobile && '' === values.margin_bottom_mobile ) && ! ( '0px' === values.margin_top_mobile && '20px' === values.margin_bottom_mobile ) ) {
					style += '@media only screen and (max-width:' + extras.content_break_point + 'px) {';
					style += '.fusion-body .fusion-title.fusion-title-cid' + this.model.get( 'cid' ) + '{margin-top:' + values.margin_top_mobile + '!important;margin-bottom:' + values.margin_bottom_mobile + '!important;}';
					style += '}';
				}

				style += '</style>';

				return style;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var styles,
					titleSize = 'two',
					attr      = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-title title fusion-title-cid' + this.model.get( 'cid' ),
						style: ''
					} );

				if ( -1 !== values.style_type.indexOf( 'underline' ) ) {
					styles = values.style_type.split( ' ' );

					_.each( styles, function( style ) {
						attr[ 'class' ] += ' sep-' + style;
					} );

					if ( values.sep_color ) {
						attr.style = 'border-bottom-color:' + values.sep_color + ';';
					}
				} else if ( -1 !== values.style_type.indexOf( 'none' ) || 'text' !== values.title_type ) {
					attr[ 'class' ] += ' fusion-sep-none';
				}

				if ( 'center' === values.content_align ) {
					attr[ 'class' ] += ' fusion-title-center';
				}

				if ( '' !== values.title_type ) {
					attr[ 'class' ] += ' fusion-title-' + values.title_type;
				}

				if ( 'text' !== values.title_type && '' !== values.loop_animation ) {
					attr[ 'class' ] += ' fusion-loop-' + values.loop_animation;
				}

				if ( '' !== values.rotation_effect ) {
					attr[ 'class' ] += ' fusion-title-' + values.rotation_effect;
				}

				if ( 'highlight' === values.title_type && '' !== values.highlight_effect ) {
					attr[ 'data-highlight' ] = values.highlight_effect;
					attr[ 'class' ]         += ' fusion-highlight-' + values.highlight_effect;
				}

				if ( '1' == values.size ) {
					titleSize = 'one';
				} else if ( '2' == values.size ) {
					titleSize = 'two';
				} else if ( '3' == values.size ) {
					titleSize = 'three';
				} else if ( '4' == values.size ) {
					titleSize = 'four';
				} else if ( '5' == values.size ) {
					titleSize = 'five';
				} else if ( '6' == values.size ) {
					titleSize = 'six';
				}

				attr[ 'class' ] += ' fusion-title-size-' + titleSize;

				if ( 'undefined' !== typeof values.font_size && '' !== values.font_size ) {
					attr.style += 'font-size:' + values.font_size + ';';
				}

				if ( '' !== values.margin_top ) {
					attr.style += 'margin-top:' + values.margin_top + ';';
				}

				if ( '' !== values.margin_bottom ) {
					attr.style += 'margin-bottom:' + values.margin_bottom + ';';
				}

				if ( '' === values.margin_top && '' === values.margin_bottom ) {
					attr.style += ' margin-top:0px; margin-bottom:0px';
					attr[ 'class' ] += ' fusion-title-default-margin';
				}

				attr = _.fusionAnimations( values, attr );

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildHeadingAttr: function( values ) {
				var self        = this,
					headingAttr = {
						class: 'title-heading-' + values.content_align,
						style: ''
					};

				headingAttr.style += _.fusionGetFontStyle( 'title_font', values );

				if ( '' !== values.margin_top || '' !== values.margin_bottom ) {
					headingAttr.style += 'margin:0;';
				}

				if ( '' !== values.font_size ) {
					headingAttr.style += 'font-size:1em;';
				}

				if ( 'undefined' !== typeof values.line_height && '' !== values.line_height ) {
					headingAttr.style += 'line-height:' + values.line_height + ';';
				}

				if ( 'undefined' !== typeof values.letter_spacing && '' !== values.letter_spacing ) {
					headingAttr.style += 'letter-spacing:' + values.letter_spacing + ';';
				}

				if ( 'undefined' !== typeof values.text_color && '' !== values.text_color ) {
					headingAttr.style += 'color:' + values.text_color + ';';
				}

				if ( '' !== values.style_tag ) {
					headingAttr.style += values.style_tag;
				}

				if ( 'text' === values.title_type ) {
					headingAttr = _.fusionInlineEditor( {
						cid: self.model.get( 'cid' ),
						overrides: {
							color: 'text_color',
							'font-size': 'font_size',
							'line-height': 'line_height',
							'letter-spacing': 'letter_spacing',
							tag: 'size'
						}
					}, headingAttr );
				}

				return headingAttr;
			},

			/**
			 * Builds animation attributes.
			 *
			 * @since 2.1
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAnimatedAttr: function( values ) {
				var animationAttr = {
						class: 'fusion-animated-texts-wrapper',
						style: ''
					};

				if ( '' !== values.animated_text_color ) {
					animationAttr.style += 'color:' + values.animated_text_color + ';';
				}

				if ( values.animated_font_size ) {
					animationAttr.style += 'font-size:' + values.animated_font_size + ';';
				}

				if ( 'highlight' === values.title_type ) {
					animationAttr[ 'class' ] = 'fusion-highlighted-text';
				}

				if ( 'rotating' === values.title_type ) {
					animationAttr[ 'data-length' ] = this.getAnimationLength( values.rotation_effect );

					if ( '' !== values.display_time ) {
						animationAttr[ 'data-minDisplayTime' ] = values.display_time;
					}

					if ( '' !== values.after_text ) {
						animationAttr.style += 'text-align: center;';
					}
				}

				return animationAttr;

			},

			/**
			 * Get Animation length.
			 *
			 * @since 2.1
			 * @param {String} effect - The animation effect.
			 * @return {String}
			 */
			getAnimationLength: function ( effect ) {
				var animationLength = '';

				switch ( effect ) {

					case 'flipInX':
					case 'bounceIn':
					case 'zoomIn':
					case 'slideInDown':
					case 'clipIn':
						animationLength = 'line';
						break;

					case 'lightSpeedIn':
						animationLength = 'word';
						break;

					case 'rollIn':
					case 'typeIn':
					case 'fadeIn':
						animationLength = 'char';
						break;
				}

				return animationLength;
			},

			/**
			 * Builds rotated text attributes.
			 *
			 * @since 2.1
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildRotatedAttr: function( values ) {
				var effect    = values.rotation_effect,
					rotatedAttr = {
						class: 'fusion-animated-text',
						style: ''
					};

				rotatedAttr[ 'data-in-effect' ]   = effect;
				rotatedAttr[ 'data-in-sequence' ] = 'true';
				rotatedAttr[ 'data-out-reverse' ] = 'true';

				effect = effect.replace( 'In', 'Out' );
				effect = effect.replace( 'Down', 'Up' );

				rotatedAttr[ 'data-out-effect' ] = effect;

				return rotatedAttr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			builderSeparatorAttr: function( values ) {
				var separatorAttr = {
						class: 'title-sep'
					},
					styles        = values.style_type.split( ' ' );

				_.each( styles, function( style ) {
					separatorAttr[ 'class' ] += ' sep-' + style;
				} );

				if ( values.sep_color ) {
					separatorAttr.style = 'border-color:' + values.sep_color + ';';
				}

				return separatorAttr;
			},

			onCancel: function() {
				this.resetTypography();
			},

			afterPatch: function() {
				this.resetTypography();
				this.refreshJs();
			},

			refreshJs: function() {
				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-element-render-fusion_title', this.model.attributes.cid );
			},

			resetTypography: function() {
				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-typography-reset', this.model.get( 'cid' ) );

				if ( 800 > jQuery( '#fb-preview' ).width() ) {
					setTimeout( function() {
						jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'resize' );
					}, 50 );
				}
			}
		} );
	} );
}( jQuery ) );
