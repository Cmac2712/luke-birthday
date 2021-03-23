/* global FusionPageBuilderApp, fusionAllElements, FusionPageBuilderElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Section separator view.
		FusionPageBuilder.fusion_section_separator = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				this.extras = atts.extras;

				// Create attribute objects
				attributes.attr             = this.buildAtts( atts.values );
				attributes.attrCandyArrow   = this.buildCandyArrowAtts( atts.values );
				attributes.attrCandy        = this.buildCandyAtts( atts.values );
				attributes.attrSVG          = this.buildSVGAtts( atts.values );
				attributes.attrButton       = this.buildButtonAtts( atts.values );
				attributes.attrRoundedSplit = this.buildRoundedSplitAtts( atts.values );
				attributes.values           = atts.values;

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
				if ( ! isNaN( values.bordersize ) ) {
					values.bordersize = _.fusionGetValueWithUnit( values.bordersize );
				}

				values.borderSizeWithoutUnits = parseInt( values.bordersize.match( /\d+/ ), 10 );

				if ( 'horizon' === values.divider_type ) {
					values.yMin = 'top' === values.divider_candy ? '-0.5' : '0';
				} else if ( 'hills_opacity' === values.divider_type ) {
					values.yMin = 'top' === values.divider_candy ? '-0.5' : '0';
				} else if ( 'waves' === values.divider_type ) {
					values.yMin = 'top' === values.divider_candy ? '54' : '1';
				} else if ( 'waves_opacity' === values.divider_type ) {
					values.yMin = 'top' === values.divider_candy ? '0' : '1';
				}
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildAtts: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-section-separator section-separator'
					} ),
					parent = this.model.get( 'parent' ),
					parentContainerValues,
					containerDefaults,
					parentContainerModel,
					parentRowModel,
					parentColumnModel,
					parentColumnWidth,
					parentColumnValues,
					marginLeft,
					marginRight,
					marginRightUnitless,
					marginLeftUnitless,
					marginLeftUnitlessScaled,
					marginRightUnitlessScaled,
					marginLeftNegative,
					marginRightNegative,
					viewportWidth,
					marginLeftFinal,
					marginRightFinal,
					marginSum,
					marginUnit,
					mainPaddingUnit,
					mainPadding,
					mainPaddingUnitless,
					marginDifferenceHalf,
					containerPercentage;

				attr.style = '';

				if ( 'triangle' === values.divider_type ) {
					if ( '' !== values.bordercolor ) {
						if ( 'bottom' === values.divider_candy ) {
							attr.style = 'border-bottom:' + values.bordersize + ' solid ' + values.bordercolor + ';';
						} else if ( 'top' === values.divider_candy ) {
							attr.style = 'border-top:' + values.bordersize + ' solid ' + values.bordercolor + ';';
						} else if ( -1 !== values.divider_candy.indexOf( 'top' ) && -1 !== values.divider_candy.indexOf( 'bottom' ) ) {
							attr.style = 'border:' + values.bordersize + ' solid ' + values.bordercolor + ';';
						}
					}
				} else if ( 'bigtriangle' === values.divider_type || 'slant' === values.divider_type || 'big-half-circle' === values.divider_type || 'clouds' === values.divider_type || 'curved' === values.divider_type ) {
					attr.style = 'padding:0;';
				} else if ( 'horizon' === values.divider_type || 'waves' === values.divider_type || 'waves_opacity' === values.divider_type || 'hills' === values.divider_type || 'hills_opacity' === values.divider_type ) {
					attr.style = 'font-size:0;line-height:0;';
				}

				if ( 'rounded-split' === values.divider_type ) {
					attr[ 'class' ] += ' rounded-split-separator';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				if ( ! _.isUndefined( parent ) ) {

					// Get the column.
					parentColumnModel = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) == parent;
					} );
					parentColumnWidth  = parentColumnModel.attributes.params.type;
					parentColumnValues = jQuery.extend( true, {}, fusionAllElements.fusion_builder_column.defaults, _.fusionCleanParameters( parentColumnModel.get( 'params' ) ) );

					// Get the row.
					parentRowModel = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) == parentColumnModel.attributes.parent;
					} );

					// Get the container.
					parentContainerModel = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) == parentRowModel.attributes.parent;
					} );

					// If 100 page template.
					containerDefaults = fusionAllElements.fusion_builder_container.defaults;
					if ( FusionPageBuilderApp.$el.find( '#main' ).hasClass( 'width-100' ) ) {
						containerDefaults.padding_top    = this.extras.container_padding_100.top;
						containerDefaults.padding_right  = this.extras.container_padding_100.right;
						containerDefaults.padding_bottom = this.extras.container_padding_100.bottom;
						containerDefaults.padding_left   = this.extras.container_padding_100.left;
					}
					parentContainerValues = jQuery.extend( true, {}, containerDefaults, _.fusionCleanParameters( parentContainerModel.get( 'params' ) ) );

					if ( ! _.isUndefined( parentContainerModel.attributes ) && 'fusion_builder_container' === parentContainerModel.attributes.type ) {
						marginLeft  = ( 'undefined' !== typeof parentContainerValues.padding_left ) ? parentContainerValues.padding_left : '';
						marginRight = ( 'undefined' !== typeof parentContainerValues.padding_right ) ? parentContainerValues.padding_right : '';

						if ( '1_1' !== parentColumnWidth ) {
							marginLeft  = _.fusionSingleDimension( parentColumnValues.padding_left, 'left' );
							marginRight = _.fusionSingleDimension( parentColumnValues.padding_right, 'right' );
						}

						marginLeft  = ( '0' === marginLeft || 0 === marginLeft ) ? '0px' : marginLeft;
						marginRight = ( '0' === marginRight || 0 === marginRight ) ? '0px' : marginRight;

						marginLeftUnitless  = parseFloat( marginLeft );
						marginRightUnitless = parseFloat( marginRight );

						containerPercentage  = 100 - marginLeftUnitless - marginRightUnitless;
						if ( -1 !== marginLeft.indexOf( '%' ) ) {
							marginLeftUnitlessScaled = marginLeftUnitless / containerPercentage * 100;
						}

						if ( -1 !== marginRight.indexOf( '%' ) ) {
							marginRightUnitlessScaled = marginRightUnitless / containerPercentage * 100;
						}

						viewportWidth = '100vw';

						if ( 'boxed' === this.extras.layout.toLowerCase() ) {
							viewportWidth = this.extras.site_width;
						}

						if ( 'top' !== this.extras.header_position ) {
							viewportWidth = viewportWidth + ' - ' + parseFloat( this.extras.side_header_width ) + 'px';
						}

						// 100% width template && non 100% interior width container.
						if ( FusionPageBuilderApp.$el.find( '#main' ).hasClass( 'width-100' ) && 'no' === parentContainerValues.hundred_percent && '1_1' === parentColumnWidth ) {

							// Both container paddings use px.
							if ( -1 !== marginLeft.indexOf( 'px' ) && -1 !== marginRight.indexOf( 'px' ) ) {
								marginUnit = 'px';
								marginDifferenceHalf = Math.abs( ( marginLeftUnitless - marginRightUnitless ) / 2 ) + marginUnit;

								if ( 'boxed' === this.extras.layout.toLowerCase() ) {
									marginLeftNegative  = '-' + marginLeft;
									marginRightNegative = '-' + marginRight;
								} else {
									if ( marginLeftUnitless > marginRightUnitless ) {
										marginLeft  = '- ' + marginDifferenceHalf;
										marginRight = '+ ' + marginDifferenceHalf;
									} else if ( marginLeftUnitless < marginRightUnitless ) {
										marginLeft  = '+ ' + marginDifferenceHalf;
										marginRight = '- ' + marginDifferenceHalf;
									} else if ( marginLeftUnitless === marginRightUnitless ) {
										marginLeft  = '';
										marginRight = '';
									}

									marginLeftNegative  = 'calc( (' + viewportWidth + ' - 100% ) / -2 ' + marginLeft + ' )';
									marginRightNegative = 'calc( (' + viewportWidth + ' - 100% ) / -2  ' + marginRight + ' )';
								}

								attr[ 'class' ] += ' fusion-section-separator-with-offset';

								// Both container paddings use %.
							} else if ( -1 !== marginLeft.indexOf( '%' ) && -1 !== marginRight.indexOf( '%' ) ) {

								if ( 'boxed' === this.extras.layout.toLowerCase() ) {
									marginUnit = '%';

									mainPadding         = this.extras.hundredp_padding;
									mainPaddingUnitless = parseFloat( mainPadding );
									mainPaddingUnit     = mainPadding.replace( mainPaddingUnitless, '' );

									marginLeftNegative  = 'calc( ( 100% - ' + ( 2 * mainPaddingUnitless ) + mainPaddingUnit + ' ) * ' + ( ( -1 / 100 ) * marginLeftUnitlessScaled ) + ' )';
									marginRightNegative = 'calc( ( 100% - ' + ( 2 * mainPaddingUnitless ) + mainPaddingUnit + ' ) * ' + ( ( -1 / 100 ) * marginRightUnitlessScaled ) + ' )';
								} else {
									marginUnit = 'vw';
									marginSum  = ' - ' + ( marginLeftUnitless + marginRightUnitless ) + marginUnit;

									marginLeftNegative  = 'calc( (' + viewportWidth + ' - 100% ' + marginSum + ') / -2 - ' + marginLeftUnitless + marginUnit + ' )';
									marginRightNegative = 'calc( (' + viewportWidth + ' - 100% ' + marginSum + ') / -2  - ' + marginRightUnitless + marginUnit + ' )';

									attr[ 'class' ] += ' fusion-section-separator-with-offset';

								}
							} else {

								// Mixed container padding units.
								marginLeftFinal = marginLeft;
								if ( -1 !== marginLeft.indexOf( '%' ) && 'boxed' !== this.extras.layout.toLowerCase() ) {
									marginLeftFinal = marginLeftUnitless + 'vw';
								}

								marginRightFinal = marginRight;
								if ( -1 !== marginRight.indexOf( '%' ) && 'boxed' !== this.extras.layout.toLowerCase() ) {
									marginRightFinal = marginRightUnitless + 'vw';
								}

								marginLeftNegative  = 'calc( (' + viewportWidth + ' - 100% - ' + marginLeft + ' - ' + marginRight + ') / -2 - ' + marginLeftFinal + ' )';
								marginRightNegative = 'calc( (' + viewportWidth + ' - 100% - ' + marginLeft + ' - ' + marginRight + ') / -2 - ' + marginRightFinal + ' )';
							}
						} else {

							// Non 100% width template.
							if ( -1 !== marginLeft.indexOf( '%' ) ) {
								marginLeft = marginLeftUnitlessScaled + '%';
								if ( -1 !== marginRight.indexOf( '%' ) ) {
									marginRight = marginRightUnitlessScaled + '%';
								}

								marginLeftNegative = 'calc( (100% + ' + marginLeft + ' + ' + marginRight + ') * ' + marginLeftUnitless + ' / -100 )';
							} else {
								marginLeftNegative = '-' + marginLeft;
							}

							if ( -1 !== marginRight.indexOf( '%' ) ) {
								marginRight = marginRightUnitlessScaled + '%';
								if ( -1 !== marginLeft.indexOf( '%' ) ) {
									marginLeft = marginLeftUnitlessScaled + '%';
								}

								marginRightNegative = 'calc( (100% + ' + marginLeft + ' + ' + marginRight + ') * ' + marginRightUnitless + ' / -100 )';
							} else {
								marginRightNegative = '-' + marginRight;
							}
						}

						attr.style += 'margin-left:' + marginLeftNegative + ';';
						attr.style += 'margin-right:' + marginRightNegative + ';';

					}
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildCandyAtts: function( values ) {
				var attrCandy = {
					class: 'divider-candy'
				};

				if ( 'bottom' === values.divider_candy ) {
					attrCandy[ 'class' ] += ' bottom';
					attrCandy.style = 'bottom:-' + ( values.borderSizeWithoutUnits + 20 ) + 'px;border-bottom:1px solid ' + values.bordercolor + ';border-left:1px solid ' + values.bordercolor + ';';
				} else if ( 'top' === values.divider_candy ) {
					attrCandy[ 'class' ] += ' top';
					attrCandy.style = 'top:-' + ( values.borderSizeWithoutUnits + 20 ) + 'px;border-bottom:1px solid ' + values.bordercolor + ';border-left:1px solid ' + values.bordercolor + ';';

					// Modern setup, that won't work in IE8.
				} else if ( -1 !== values.divider_candy.indexOf( 'top' ) && -1 !== values.divider_candy.indexOf( 'bottom' ) ) {
					attrCandy[ 'class' ] += ' both';
					attrCandy.style = 'background-color:' + values.backgroundcolor + ';border:1px solid ' + values.bordercolor + ';';
				}

				if ( -1 !== values.divider_candy.indexOf( 'top' ) && -1 !== values.divider_candy.indexOf( 'bottom' ) ) {
					attrCandy[ 'class' ] += ' triangle';
				}
				return attrCandy;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildCandyArrowAtts: function( values ) {
				var attrCandyArrow = {
					class: 'divider-candy-arrow'
				};

				// For borders of size 1, we need to hide the border line on the arrow, thus we set it to 0.
				var arrowPosition = values.borderSizeWithoutUnits;
				if ( 1 === arrowPosition ) {
					arrowPosition = 0;
				}

				if ( 'bottom' === values.divider_candy ) {
					attrCandyArrow[ 'class' ] += ' bottom';
					attrCandyArrow.style  = 'top:' + arrowPosition + 'px;border-top-color: ' + values.backgroundcolor + ';';
				} else if ( 'top' === values.divider_candy ) {
					attrCandyArrow[ 'class' ] += ' top';
					attrCandyArrow.style  = 'bottom:' + arrowPosition + 'px;border-bottom-color: ' + values.backgroundcolor + ';';
				}

				return attrCandyArrow;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildSVGAtts: function( values ) {
				var attrSVG = {
					display: 'block'
				};

				if ( 'bigtriangle' === values.divider_type || 'slant' === values.divider_type || 'big-half-circle' === values.divider_type || 'clouds' === values.divider_type || 'curved' === values.divider_type ) {
					attrSVG.style = 'fill:' + values.backgroundcolor + ';padding:0;';
				}
				if ( 'slant' === values.divider_type && 'bottom' === values.divider_candy ) {
					attrSVG.style = 'fill:' + values.backgroundcolor + ';padding:0;margin-bottom:-3px;display:block';
				}

				if ( 'horizon' === values.divider_type || 'hills' === values.divider_type || 'hills_opacity' === values.divider_type || 'waves' === values.divider_type || 'waves_opacity' === values.divider_type ) {
					attrSVG.style = 'fill:' + values.backgroundcolor;
				}

				return attrSVG;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildButtonAtts: function( values ) {
				var attrButton = {};

				if ( '' !== values.icon ) {
					attrButton = {
						class: 'section-separator-icon icon ' + _.fusionFontAwesome( values.icon ),
						style: 'color:' + values.icon_color + ';'
					};

					if ( ! values.icon_color ) {
						values.icon_color = values.bordercolor;
					}

					if ( 1 < values.borderSizeWithoutUnits ) {
						if ( 'bottom' === values.divider_candy ) {
							attrButton.style += 'bottom:-' + ( values.borderSizeWithoutUnits + 10 ) + 'px;top:auto;';
						} else if ( 'top' === values.divider_candy ) {
							attrButton.style += 'top:-' + ( values.borderSizeWithoutUnits + 10 ) + 'px;';
						}
					}
				}

				return attrButton;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildRoundedSplitAtts: function( values ) {
				var attrRoundedSplit = {};

				if ( 'rounded-split' === values.divider_type ) {
					attrRoundedSplit = {
						class: 'rounded-split ' + values.divider_candy,
						style: 'background-color:' + values.backgroundcolor + ';'
					};
				}

				return attrRoundedSplit;
			}

		} );
	} );
}( jQuery ) );
