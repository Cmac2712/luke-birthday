/* global FusionApp, fusionBuilderText, fusionAllElements, cssua, FusionPageBuilderViewManager, FusionPageBuilderApp, FusionEvents */
/* eslint no-unused-vars: 0 */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Nested Column View
		FusionPageBuilder.BaseColumnView = FusionPageBuilder.BaseView.extend( {

			/**
			 * Runs before view DOM is patched.
			 *
			 * @since 2.0.0
			 * @return null
			 */
			beforePatch: function() {
				if ( FusionPageBuilderApp.wireframeActive ) {
					this.$el.find( '.fusion-builder-column-content' ).removeClass( 'ui-sortable' );
				}
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0.0
			 * @return null
			 */
			afterPatch: function() {
				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					if ( this.model.get( 'dragging' ) ) {
						this.model.attributes.selectors.style += ';display: none;';
						this.model.attributes.selectors[ 'class' ] += ' ignore-me-column';
					}

					this.$el.removeAttr( 'data-animationType' );
					this.$el.removeAttr( 'data-animationDuration' );
					this.$el.removeAttr( 'data-animationOffset' );

					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}

				if ( this.forceAppendChildren ) {
					this.appendChildren();
					this.forceAppendChildren = false;
				}

				this.droppableColumn();
				this._refreshJs();

				if ( FusionPageBuilderApp.wireframeActive ) {
					this.$el.find( '.fusion-builder-column-content' ).addClass( 'ui-sortable' );
				}
			},

			/**
			 * Updates now deprecated params and adds BC checks.
			 *
			 * @since 2.1
			 * @return {void}
			 */
			deprecatedParams: function() {
				var params               = this.model.get( 'params' ),
					alphaBackgroundColor = 1,
					radiaDirectionsNew   = { 'bottom': 'center bottom', 'bottom center': 'center bottom', 'left': 'left center', 'right': 'right center', 'top': 'center top', 'center': 'center center', 'center left': 'left center' };

				// If no blend mode is defined, check if we should set to overlay.
				if ( 'undefined' === typeof params.background_blend_mode && '' !== params.background_color ) {
					alphaBackgroundColor = jQuery.Color( params.background_color ).alpha();
					if ( 1 > alphaBackgroundColor && 0 !== alphaBackgroundColor && '' !== params.background_image ) {
						params.background_blend_mode = 'overlay';
					}
				}

				// Correct radial direction params.
				if ( 'undefined' !== typeof params.radial_direction && ( params.radial_direction in radiaDirectionsNew ) ) {
					params.radial_direction = radiaDirectionsNew[ params.radial_direction ];
				}

				this.model.set( 'params', params );
			},

			/**
			 * Handle margin adjustments on drag.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			marginDrag: function() {
				var $el = this.$el,
					self = this,
					directions = { top: 's', bottom: 's' },
					value,
					percentSpacing = false,
					parentWidth = 'fusion_builder_column_inner' === this.model.get( 'type' ) ? $el.closest( '.fusion-builder-row-container-inner' ).width() : $el.closest( '.fusion-row' ).width(),
					actualDimension,
					defaults,
					extras,
					element = 'column';

				if ( this.$el.hasClass( 'resizable-active' ) ) {
					return;
				}

				if ( 'fusion_builder_column' === this.model.get( 'type' ) ) {
					defaults = fusionAllElements.fusion_builder_column.defaults,
					extras   = jQuery.extend( true, {}, fusionAllElements.fusion_builder_column.extras );

					defaults.margin_top     = extras.col_margin.top;
					defaults.margin_bottom  = extras.col_margin.bottom;
				} else {
					defaults = fusionAllElements.fusion_builder_column_inner.defaults;
				}

				_.each( directions, function( handle, direction )  {

					actualDimension = 'undefined' !== typeof self.model.attributes.params[ 'margin_' + direction ] && '' !== self.model.attributes.params[ 'margin_' + direction ] ? self.model.attributes.params[ 'margin_' + direction ] : defaults[ 'margin_' + direction ];

					// Check if using a percentage.
					percentSpacing  = -1 !== actualDimension.indexOf( '%' );

					if ( percentSpacing ) {

						// Get actual dimension and set.
						actualDimension = ( parentWidth / 100 ) * parseFloat( actualDimension );
					}

					if ( 'bottom' === direction ) {
						if ( 20 > parseInt( actualDimension, 10 ) ) {
							$el.find( '> .fusion-' + element + '-margin-bottom, > .fusion-' + element + '-padding-bottom' ).addClass( 'fusion-overlap' );
						} else {
							$el.find( '> .fusion-' + element + '-margin-bottom, > .fusion-' + element + '-padding-bottom' ).removeClass( 'fusion-overlap' );
						}
					}

					$el.find( '> .fusion-' + element + '-margin-' + direction ).css( 'display', 'block' );
					$el.find( '> .fusion-' + element + '-margin-' + direction ).height( actualDimension );

					$el.find( '> .fusion-' + element + '-margin-' + direction ).resizable( {
						handles: handle,
						minHeight: 0,
						minWidth: 0,
						grid: ( percentSpacing ) ? [ parentWidth / 100, 10 ] : '',
						resize: function( event, ui ) {

							// Recheck in case unit is changed in the modal.
							percentSpacing = 'undefined' !== typeof self.model.attributes.params[ 'margin_' + direction ] ? -1 !== self.model.attributes.params[ 'margin_' + direction ].indexOf( '%' ) : false;

							jQuery( ui.element ).closest( '.fusion-builder-' + element ).addClass( 'resizable-active' );
							value = 'top' === direction || 'bottom' === direction ? ui.size.height : ui.size.width;
							value = 0 > value ? 0 : value;
							value = value + 'px';
							if ( percentSpacing ) {
								value = 0 === parseFloat( value ) ? '0%' : Math.round( parseFloat( parseFloat( value ) / ( parentWidth / 100 ) ) ) + '%';
							}

							// Bottom margin overlap
							if ( 'bottom' === direction ) {
								if ( 20 > ui.size.height ) {
									jQuery( ui.element ).addClass( 'fusion-overlap' );
									$el.find( '> .fusion-' + element + '-padding-bottom' ).addClass( 'fusion-overlap' );
								} else {
									jQuery( ui.element ).removeClass( 'fusion-overlap' );
									$el.find( '> .fusion-' + element + '-padding-bottom' ).removeClass( 'fusion-overlap' );
								}
							}

							$el.css( 'margin-' + direction, value );

							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).addClass( 'active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip' ).text( value );

							// Update open modal.
							self.updateDragSettings( '#margin_' + direction, value );
						},
						stop: function( event, ui ) {
							jQuery( ui.element ).closest( '.fusion-builder-' + element ).removeClass( 'resizable-active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).removeClass( 'active' );

							// Delete all spacing resizable within because parent width has changed.
							if ( jQuery( ui.element ).find( '.fusion-builder-column-inner'  ).find( '.fusion-element-spacing .ui-resizable' ).length ) {
								jQuery( ui.element ).closest( '.fusion-builder-column-inner'  ).find( '.fusion-element-spacing .ui-resizable' ).resizable( 'destroy' );
							}
						}
					} );
				} );
			},

			/**
			 * Handle padding adjustments on drag.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			paddingDrag: function() {
				var $el = this.$el,
					self = this,
					directions = { top: 's', right: 'w', bottom: 's', left: 'e' },
					value,
					percentSpacing = false,
					parentWidth = $el.find( '.fusion-column-wrapper' ).first().width(),
					actualDimension,
					valueAllowed = ( parentWidth / 100 ),
					element = 'column';

				if ( this.$el.hasClass( 'resizable-active' ) ) {
					return;
				}

				_.each( directions, function( handle, direction )  {

					actualDimension = 'undefined' !== typeof self.model.attributes.params[ 'padding_' + direction ] && '' !== self.model.attributes.params[ 'padding_' + direction ] ? self.model.attributes.params[ 'padding_' + direction ] : '0px';

					// Check if using a percentage.
					percentSpacing = 'undefined' !== typeof actualDimension ? -1 !== actualDimension.indexOf( '%' ) : false;

					if ( percentSpacing ) {

						// Get actual dimension and set.
						actualDimension = ( parentWidth / 100 ) * parseFloat( actualDimension );
					}

					if ( 'top' === direction ) {
						if ( 20 > parseInt( actualDimension, 10 ) ) {
							$el.find( '> .fusion-' + element + '-margin-top, > .fusion-' + element + '-padding-top' ).addClass( 'fusion-overlap' );
						} else {
							$el.find( '> .fusion-' + element + '-margin-top, > .fusion-' + element + '-padding-top' ).removeClass( 'fusion-overlap' );
						}
					}

					$el.find( '> .fusion-' + element + '-padding-' + direction ).css( 'display', 'block' );
					if ( 'top' === direction || 'bottom' === direction ) {
						$el.find( '> .fusion-' + element + '-padding-' + direction ).height( actualDimension );
					} else {
						$el.find( '> .fusion-' + element + '-padding-' + direction ).width( actualDimension );
					}

					$el.find( '> .fusion-' + element + '-padding-' + direction ).resizable( {
						handles: handle,
						minHeight: 0,
						minWidth: 0,

						resize: function( event, ui ) {
							var actualDimension,
								dimension = 'top' === direction || 'bottom' === direction ? 'height' : 'width';

							actualDimension = self.model.attributes.params[ 'padding_' + direction ];

							// Recheck in case unit is changed in the modal.
							percentSpacing = 'undefined' !== typeof actualDimension ? -1 !== actualDimension.indexOf( '%' ) : false;

							// Force to grid amount.
							if ( percentSpacing ) {
								ui.size[ dimension ] = Math.round( ui.size[ dimension ] / valueAllowed ) * valueAllowed;
							}

							jQuery( ui.element ).closest( '.fusion-builder-' + element ).addClass( 'resizable-active' );

							// Change format of value.
							value = ui.size[ dimension ];
							value = 0 > value ? 0 : value;
							value = value + 'px';
							if ( percentSpacing ) {
								value = 0 === parseFloat( value ) ? '0%' : Math.round( parseFloat( parseFloat( value ) / ( parentWidth / 100 ) ) ) + '%';
							}

							// Top padding overlap
							if ( 'top' === direction ) {
								if ( 20 > ui.size.height ) {
									jQuery( ui.element ).addClass( 'fusion-overlap' );
									$el.find( '> .fusion-' + element + '-margin-top' ).addClass( 'fusion-overlap' );
								} else {
									jQuery( ui.element ).removeClass( 'fusion-overlap' );
									$el.find( '> .fusion-' + element + '-margin-top' ).removeClass( 'fusion-overlap' );
								}
							}

							// Right padding overlap.
							if ( 'right' === direction ) {
								if ( 20 > ui.size.width && 20 > $el.find( '> .fusion-column-spacing .fusion-spacing-value' ).width() ) {
									jQuery( ui.element ).addClass( 'fusion-overlap' );
									$el.find( '> .fusion-column-spacing' ).addClass( 'fusion-overlap' );
								} else {
									jQuery( ui.element ).removeClass( 'fusion-overlap' );
									$el.find( '> .fusion-column-spacing' ).removeClass( 'fusion-overlap' );
								}
							}

							// Set values.
							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).addClass( 'active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip' ).text( value );

							// Update open modal.
							self.updateDragSettings( '#padding_' + direction, value );
						},
						stop: function( event, ui ) {
							jQuery( ui.element ).closest( '.fusion-builder-' + element ).removeClass( 'resizable-active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).removeClass( 'active' );

							// Delete all spacing resizable within because parent width has changed.
							if ( jQuery( ui.element ).find( '.fusion-builder-column-inner'  ).find( '.fusion-element-spacing .ui-resizable' ).length ) {
								jQuery( ui.element ).closest( '.fusion-builder-column-inner'  ).find( '.fusion-element-spacing .ui-resizable' ).resizable( 'destroy' );
							}
						}
					} );
				} );
			},

			/**
			 * Destroy column's resizables.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyResizable: function() {
				this.destroySpacingResizable();
				this.destroyMarginResizable();
				this.destroyPaddingResizable();
			},

			/**
			 * Destroy column's spacing resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroySpacingResizable: function() {
				var $columnSpacer;

				$columnSpacer = this.$el.find( '> .fusion-column-spacing .fusion-spacing-value' );

				if ( $columnSpacer.hasClass( 'ui-resizable' ) ) {
					$columnSpacer.resizable( 'destroy' );
					$columnSpacer.hide();
					this.columnSpacer = false;
				}
			},

			/**
			 * Destroy column's margin resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyMarginResizable: function() {

				this.$el.find( '> .fusion-element-spacing' ).each( function( index, elem ) {
					if ( jQuery( elem ).hasClass( 'ui-resizable' ) &&  -1 !== jQuery( elem ).attr( 'class' ).indexOf( 'fusion-column-margin-' ) ) {
						jQuery( elem ).resizable( 'destroy' );
						jQuery( elem ).hide();
					}
				} );

			},

			/**
			 * Destroy column's padding resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyPaddingResizable: function() {

				this.$el.find( '> .fusion-element-spacing' ).each( function( index, elem ) {
					if ( jQuery( elem ).hasClass( 'ui-resizable' ) &&  -1 !== jQuery( elem ).attr( 'class' ).indexOf( 'fusion-column-padding-' ) ) {
						jQuery( elem ).resizable( 'destroy' );
						jQuery( elem ).hide();
					}
				} );

			},

			/**
			 * Changes the column spacing.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			columnSpacing: function( event ) {
				var percentSpacing = false,
					$el            = this.$el,
					self           = this,
					parentWidth,
					marginRight,
					container,
					columnSpacing,
					existingSpacing,
					modelSpacing,
					$columnSpacer,
					maxWidth,
					rightPadding,
					rightOverlap;

				$columnSpacer = this.$el.find( '> .fusion-column-spacing .fusion-spacing-value' );

				if ( event && 'event' !== event ) {
					event.preventDefault();
				}

				// If responsive mode and columns are 1/1 hide and return.
				if ( jQuery( '#fb-preview' ).width() < FusionApp.settings.content_break_point && FusionApp.settings.responsive ) {
					$columnSpacer.hide();
					return;
				}

				$columnSpacer.show();

				// If this is the last column in a virtual row, then no handles.
				if ( this.$el.hasClass( 'fusion-column-last' ) ) {
					return;
				}

				// No resizer for fallback method.
				if ( 'yes' === this.model.attributes.params.spacing || 'no' === this.model.attributes.params.spacing ) {
					return;
				}

				existingSpacing = this.model.attributes.params.spacing;
				if ( 'undefined' === typeof existingSpacing || '' === existingSpacing ) {
					existingSpacing = '4%';
				}
				if ( 'no' === existingSpacing ) {
					existingSpacing = '0';
				}

				// Already created spacer and not %, no need to continue.
				if ( this.columnSpacer && -1 === existingSpacing.indexOf( '%' ) ) {
					return;
				}

				// Get the container width.
				container = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );

				if ( 'fusion_builder_column_inner' === this.model.get( 'type' ) ) {
					parentWidth = container.$el.find( '.fusion-builder-row-container-inner' ).width();
				} else {
					parentWidth = container.$el.find( '.fusion-row' ).width();
				}

				// Already created spacer, % is being used and width is the same, no need to continue.
				if ( this.columnSpacer && parentWidth === this.parentWidth ) {
					return;
				}

				// Store parent width to compare.
				this.parentWidth = parentWidth;

				// Get the column right margin.  In real usage use the model attribute.
				columnSpacing = existingSpacing;
				marginRight   = existingSpacing;

				// Set column spacing width.
				if ( -1 !== existingSpacing.indexOf( '%' ) ) {
					percentSpacing = true;
					marginRight    = parseFloat( marginRight ) / 100.0;
					columnSpacing  = marginRight * parentWidth;
				}

				// Set max width spacing.
				maxWidth = parentWidth - 100;

				// Destroy in case it's already active
				if ( $columnSpacer.hasClass( 'ui-resizable' ) ) {
					$columnSpacer.resizable( 'destroy' );
				}

				$columnSpacer.width( columnSpacing );

				$columnSpacer.resizable( {
					handles: FusionPageBuilderApp.$el.hasClass( 'rtl' ) ? 'w' : 'e',
					minWidth: 0,
					maxWidth: maxWidth,
					grid: ( percentSpacing ) ? [ parentWidth / 100, 10 ] : '',
					create: function() {
						if ( 0 === $el.find( '> .fusion-column-spacing .fusion-spacing-value' ).width() ) {
							$el.find( '> .fusion-column-spacing' ).addClass( 'empty' );
						} else if ( $el.find( '> .fusion-column-spacing.empty' ).length ) {
							$el.find( '> .fusion-column-spacing' ).removeClass( 'empty' );
						}
					},
					resize: function( event, ui ) {
						var marginDirection = FusionPageBuilderApp.$el.hasClass( 'rtl' ) ? 'left' : 'right';

						ui.size.width = 0 > ui.size.width ? 0 : ui.size.width;

						if ( 0 === modelSpacing ) {
							$el.find( '> .fusion-column-spacing' ).addClass( 'empty' );
						} else if ( $el.find( '> .fusion-column-spacing.empty' ).length ) {
							$el.find( '> .fusion-column-spacing' ).removeClass( 'empty' );
						}
						modelSpacing = ui.size.width + 'px';
						if ( percentSpacing ) {
							modelSpacing = Math.round( parseFloat( ui.size.width / ( parentWidth / 100 ) ) ) + '%';
						}
						$el.css( 'margin-' + marginDirection, modelSpacing );

						// Update open modal.
						if ( jQuery( '[data-element-cid="' + self.model.get( 'cid' ) + '"]' ).length ) {
							jQuery( '[data-element-cid="' + self.model.get( 'cid' ) + '"] [data-option-id="spacing"] #spacing' ).val( modelSpacing ).trigger( 'change' );
						}

						$el.find( '> .fusion-column-spacing .fusion-spacing-tooltip, > .fusion-column-spacing' ).addClass( 'active' );
						$el.find( '> .fusion-column-spacing .fusion-spacing-tooltip' ).text( modelSpacing );
						$el.addClass( 'active-drag' );
						self._toolTipHide();

						// Right padding overlap.
						if ( 20 > ui.size.width && 20 > $el.find( '> .fusion-column-padding-' + marginDirection ).width() ) {
							jQuery( ui.element ).parent().addClass( 'fusion-overlap' );
							$el.find( '> .fusion-column-padding-' + marginDirection ).addClass( 'fusion-overlap' );
						} else {
							jQuery( ui.element ).parent().removeClass( 'fusion-overlap' );
							$el.find( '> .fusion-column-padding-' + marginDirection ).removeClass( 'fusion-overlap' );
						}
					},
					stop: function( event, ui ) { // jshint ignore: line
						$el.removeClass( 'active-drag' );
					}
				} );

				rightPadding = 'undefined' === typeof this.model.attributes.params.padding_right || '' === this.model.attributes.params.padding_right ? '0px' : this.model.attributes.params.padding_right;
				rightOverlap = ( 20 > parseInt( rightPadding, 10 ) && ( '0%' === rightPadding || -1 === rightPadding.indexOf( '%' ) ) && ( 20 > parseInt( columnSpacing, 10 ) ) ) ? 'fusion-overlap' : '';

				if ( '' !== rightOverlap ) {
					$el.find( '> .fusion-column-padding-right, > .fusion-column-spacing' ).addClass( 'fusion-overlap' );
				} else {
					$el.find( '> .fusion-column-padding-right, > .fusion-column-spacing' ).removeClass( 'fusion-overlap' );
				}

				// Column spacer created
				this.columnSpacer = true;
			},

			/**
			 * Changes the size of a column.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event triggering the change in size.
			 * @return {void}
			 */
			sizeSelect: function( event ) {
				var columnSize,
					fractionSize,
					container = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );

				if ( event ) {
					event.preventDefault();
				}

				columnSize = jQuery( event.target ).data( 'column-size' );

				this.model.attributes.params.type = columnSize;

				this.$el.find( '.column-sizes' ).hide();
				this.$el.removeClass( 'active' );
				this.$el.attr( 'data-column-size', columnSize );

				fractionSize = columnSize.replace( '_', '/' );

				// Necessary for re-sizing then cloning.
				this.reRender();

				container.setRowData();

				if ( 'fusion_builder_column_inner' !== this.model.get( 'type' ) ) {
					this.renderSectionSeps();
				}

				this.$el.find( '.column-sizes .column-size' ).removeClass( 'active-size' );
				this.$el.find( '.column-size-' + columnSize ).addClass( 'active-size' );

				this.$el.closest( '.fusion-builder-container' ).removeClass( 'fusion-column-sizer-active' );

				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-column-resized', this.model.get( 'cid' ) );
				FusionEvents.trigger( 'fusion-column-resized' );

				// Save history state
				FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.resized_column + ' ' + fractionSize );
			},

			/**
			 * Checks if the value is in pixels.
			 *
			 * @since 2.0.0
			 * @param {string} value - The value we want to check.
			 * @return {boolean}
			 */
			pxCheck: function( value ) {
				if ( 'undefined' === typeof value ) {
					return false;
				}

				// If 0, then consider valid.
				if ( '0' === value || 0 === value ) {
					return true;
				}

				return ( -1 !== value.indexOf( 'px' ) ) ? true : false;
			},

			/**
			 * Checks if the value is using %.
			 *
			 * @since 2.0.0
			 * @param {string} value - The value we want to check.
			 * @return {boolean}
			 */
			percentageCheck: function( value ) {
				if ( 'undefined' === typeof value ) {
					return false;
				}

				// If 0, then consider valid.
				if ( '0' === value || 0 === value ) {
					return true;
				}

				return ( -1 !== value.indexOf( '%' ) ) ? true : false;
			},

			/**
			 * Adds 2 values.
			 *
			 * @since 2.0.0
			 * @param {string|number|double} a - The 1st value.
			 * @param {string|number|double} b - The 2nd value.
			 * @return {number}
			 */
			addValues: function( a, b ) {
				return parseFloat( a ) + parseFloat( b );
			},

			/**
			 * Add a module.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event triggering the module addition.
			 * @return {void}
			 */
			addModule: function( event ) {
				var view,
					viewSettings,
					closestParent;

				if ( event ) {
					event.preventDefault();
					event.stopPropagation();
					FusionPageBuilderApp.sizesHide( event );
				}

				FusionPageBuilderApp.parentColumnId = this.model.get( 'cid' );

				viewSettings = {
					model: this.model,
					collection: this.collection,
					view: this,
					attributes: {
						'data-parent_cid': this.model.get( 'cid' )
					}
				};

				if ( ! jQuery( event.currentTarget ).closest( '.fusion-builder-empty-column' ).length && ! FusionPageBuilderApp.wireframeActive ) {
					closestParent = jQuery( event.currentTarget ).closest( '.fusion-builder-live-element' );
					if ( closestParent.length ) {
						viewSettings.targetElement = closestParent;
					} else {
						viewSettings.targetElement = jQuery( event.currentTarget ).closest( '.fusion-builder-nested-element' );
					}
				}

				view = new FusionPageBuilder.ElementLibraryView( viewSettings );

				jQuery( view.render().el ).dialog( {
					title: 'Select Element',
					draggable: false,
					modal: true,
					resizable: false,
					dialogClass: 'fusion-builder-dialog fusion-builder-large-library-dialog fusion-builder-element-library-dialog',

					resizeStart: function( event, ui ) {
						FusionApp.dialog.addResizingClasses();
					},

					resizeStop: function( event, ui ) {
						FusionApp.dialog.removeResizingClasses();
					},

					open: function( event, ui ) { // jshint ignore: line
						FusionApp.dialog.resizeDialog();

						// On start can sometimes be laggy/late.
						FusionApp.dialog.addResizingHoverEvent();
					},
					close: function( event, ui ) { // jshint ignore: line
						view.remove();
					}
				} );
			},

			/**
			 * Get dynamic values.
			 *
			 * @since 2.0.0
			 * @return {Object}
			 */
			getDynamicAtts: function( values ) {
				var self = this;

				if ( 'undefined' !== typeof this.dynamicParams && this.dynamicParams && ! _.isEmpty( this.dynamicParams.getAll() ) ) {
					_.each( this.dynamicParams.getAll(), function( data, id ) {
						var value = self.dynamicParams.getParamValue( data );

						if ( 'undefined' !== typeof value && false !== value ) {
							values[ id ] = value;
						}
					} );
				}
				return values;
			},

			/**
			 * Get the template.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			getTemplate: function() {
				var atts = this.getTemplateAtts();
				return this.template( atts );
			},

			getTemplateAtts: function() {
				var params                 = jQuery.extend( true, {}, this.model.get( 'params' ) ),
					data                   = {},
					columnSize             = '',
					style                  = '',
					classes                = 'fusion-builder-column-live-' + this.model.get( 'cid' ),
					wrapperClasses         = 'fusion-column-wrapper',
					wrapperStyle           = '',
					wrapperStyleBG         = '',
					hrefLink               = '',
					currentRow             = '',
					fusionColumnAttributes = {},
					widthOffset            = '',
					innerBgStyle           = '',
					liftUpStyleTag         = '',
					spacings               = '',
					nestedClass            = '',
					fallback               = false,
					values,
					layout,
					animations,
					width,
					innerContent,

					// Map old column width to old width with spacing.
					mapOldSpacing = {
						0.1666: '13.3333%',
						0.8333: '82.6666%',
						0.2: '16.8%',
						0.4: '37.6%',
						0.6: '58.4%',
						0.8: '79.2%',
						0.25: '22%',
						0.75: '74%',
						0.3333: '30.6666%',
						0.6666: '65.3333%',
						0.5: '48%',
						1: '100%'
					},
					container,
					containerRows,
					currentRowNumberOfColumns,
					backgroundImageStyle,
					backgroundColorStyle,
					spacingDirection,
					columnSpacing,
					alphaBackgroundColor = 1,
					total,
					lastIndex,
					model,
					borderRadius,
					styleSelector = '';

				fusionColumnAttributes[ 'class' ] = '';

				// Make sure initial width is correctly inherited.
				if ( 'undefined' === typeof params.type ) {
					params.type = this.model.attributes.params.type;
				}

				if ( fusionAllElements[ this.model.get( 'type' ) ] ) {
					values = jQuery.extend( true, {}, fusionAllElements[ this.model.get( 'type' ) ].defaults, _.fusionCleanParameters( params ) );
				}

				values = this.getDynamicAtts( values );

				// Nested column
				if ( 'fusion_builder_column_inner' === this.model.get( 'type' ) ) {
					nestedClass = ' fusion-nested-column-content';
				}

				// Animation
				animations = _.fusionAnimations( values );

				if ( animations ) {
					fusionColumnAttributes = jQuery.extend( fusionColumnAttributes, animations );
					fusionColumnAttributes[ 'class' ] += ' ' + fusionColumnAttributes.animation_class;
					delete fusionColumnAttributes.animation_class;
				}

				values      = values || {};
				values.type = values.type || '1_1';

				// Column size value
				switch ( values.type ) {
				case '1_1':
					columnSize = 1;
					classes += ' fusion-one-full';
					break;
				case '1_4':
					columnSize = 0.25;
					classes += ' fusion-one-fourth';
					break;
				case '3_4':
					columnSize = 0.75;
					classes += ' fusion-three-fourth';
					break;
				case '1_2':
					columnSize = 0.50;
					classes += ' fusion-one-half';
					break;
				case '1_3':
					columnSize = 0.3333;
					classes += ' fusion-one-third';
					break;
				case '2_3':
					columnSize = 0.6666;
					classes += ' fusion-two-third';
					break;
				case '1_5':
					columnSize = 0.20;
					classes += ' fusion-one-fifth';
					break;
				case '2_5':
					columnSize = 0.40;
					classes += ' fusion-two-fifth';
					break;
				case '3_5':
					columnSize = 0.60;
					classes += ' fusion-three-fifth';
					break;
				case '4_5':
					columnSize = 0.80;
					classes += ' fusion-four-fifth';
					break;
				case '5_6':
					columnSize = 0.8333;
					classes += ' fusion-five-sixth';
					break;
				case '1_6':
					columnSize = 0.1666;
					classes += ' fusion-one-sixth';
					break;
				}

				if ( '' !== values.margin_bottom ) {
					values.margin_bottom = _.fusionGetValueWithUnit( values.margin_bottom );
				}
				if ( '' !== values.margin_top ) {
					values.margin_top = _.fusionGetValueWithUnit( values.margin_top );
				}
				if ( values.border_size ) {
					values.border_size = _.fusionValidateAttrValue( values.border_size, 'px' );
				}
				if ( '' !== values.padding ) {
					values.padding = _.fusionGetValueWithUnit( values.padding );
				}

				// Border radius validation.
				values.border_radius_top_left     = values.border_radius_top_left ? _.fusionGetValueWithUnit( values.border_radius_top_left ) : '0px';
				values.border_radius_top_right    = values.border_radius_top_right ? _.fusionGetValueWithUnit( values.border_radius_top_right ) : '0px';
				values.border_radius_bottom_left  = values.border_radius_bottom_left ? _.fusionGetValueWithUnit( values.border_radius_bottom_left ) : '0px';
				values.border_radius_bottom_right = values.border_radius_bottom_right ? _.fusionGetValueWithUnit( values.border_radius_bottom_right ) : '0px';
				borderRadius                      = values.border_radius_top_left + ' ' + values.border_radius_top_right + ' ' + values.border_radius_bottom_right + ' ' + values.border_radius_bottom_left;
				borderRadius                      = '0px 0px 0px 0px' === borderRadius ? '' : borderRadius;

				// If padding (combined all 4) is not set in params, then use individual variables.
				if ( 'undefined' === typeof params.padding ) {
					values = _.fusionGetPadding( values );
				}

				function fallbackCheck( value ) {
					return ( 'yes' === value || 'no' === value );
				}

				width = ( columnSize * 100 ) + '%';

				if ( 'yes' === values.spacing || '' === values.spacing ) {
					values.spacing = '4%';
				} else if ( 'no' === values.spacing ) {
					values.spacing = '0px';
				}
				values.spacing = _.fusionGetValueWithUnit( values.spacing );

				if ( 0 === parseFloat( values.spacing ) ) {
					classes += ' fusion-spacing-no';
				}

				container     = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );
				containerRows = container.model.get( 'rows' );
				currentRow    = container.getVirtualRowByCID( this.model.get( 'cid' ) );

				// Pop off the last because it can't have spacing.
				if ( 'undefined' !== typeof currentRow ) {

					// currentRow = currentRow.slice( 0, -1 );
					currentRowNumberOfColumns = currentRow.length + 1;
				}
				if ( 'object' === typeof currentRow ) {
					fallback = currentRow.every( fallbackCheck );
				}

				// Nested column check
				if ( 'object' === typeof currentRow ) {
					spacings  = [];
					total     = currentRow.length;
					lastIndex = total - 1;

					_.each( currentRow, function( column, index ) {

						if ( lastIndex !== index ) {
							model = container.model.children.find( function( model ) {
								return model.get( 'cid' ) == column.cid; // jshint ignore: line
							} );

							columnSpacing = model.attributes.params.spacing;
							columnSpacing = ( 'undefined' === typeof columnSpacing || '' === columnSpacing ) ? '4%' : columnSpacing;

							spacings.push( columnSpacing );
						}

						if ( 1 === total ) {
							spacings.push( '' );
						}

					} );

					spacings = spacings.join( ' + ' );

					// If no fallback make sure to replace mixed values.
					if ( ! fallback ) {
						spacings = spacings.replace( /yes/g, '4%' ).replace( /no/g, '0%' );
					}
					widthOffset = '( ( ' + spacings + ' ) * ' + columnSize + ' ) ';
				}

				if ( ! values.last && ! ( fallback && '0px' === values.spacing ) ) {
					spacingDirection = 'right';

					if ( FusionPageBuilderApp.$el.hasClass( 'rtl' ) ) {
						spacingDirection = 'left';
					}
					if ( ! fallback ) {
						style += 'width:' + width + ';width:calc(' + width + ' - ' + widthOffset + ');margin-' + spacingDirection + ': ' + values.spacing + ';';
					} else {
						style += 'width:' + mapOldSpacing[ columnSize ] + '; margin-' + spacingDirection + ': ' + values.spacing + ';';
					}
				} else if ( 'undefined' !== typeof currentRowNumberOfColumns && 1 < currentRowNumberOfColumns ) {
					if ( ! fallback ) {
						style += 'width:' + width + ';width:calc(' + width + ' - ' + widthOffset + ');';
					} else if ( '0px' !== values.spacing && 'undefined' !== typeof mapOldSpacing[ columnSize ] ) {
						style += 'width:' + mapOldSpacing[ columnSize ] + ';';
					} else {
						style += 'width:' + width + ';';
					}
				} else if ( 'undefined' === typeof currentRowNumberOfColumns && 'undefined' !== mapOldSpacing[ columnSize ] ) {
					style += 'width:' + mapOldSpacing[ columnSize ] + ';';
				}

				// Background
				backgroundColorStyle = '';
				if ( '' !== values.background_color ) {
					alphaBackgroundColor = jQuery.Color( values.background_color ).alpha();
					if ( '' === values.background_image || ( 0 !== alphaBackgroundColor ) ) {
						backgroundColorStyle = 'background-color:' + values.background_color + ';';
						if ( 'none' === values.hover_type || ( '' === values.hover_type && '' === values.link ) ) {
							wrapperStyle += backgroundColorStyle;
						} else {
							wrapperStyleBG += backgroundColorStyle;
						}
					}
				}

				backgroundImageStyle = '';
				if ( '' !== values.background_image ) {
					backgroundImageStyle += 'background-image: url(\'' + values.background_image + '\');';
				}

				if ( '' !== _.getGradientString( values, 'column' ) ) {
					backgroundImageStyle += 'background-image:' + _.getGradientString( values, 'column' ) + ';';
				}

				if ( '' !== values.background_position ) {
					backgroundImageStyle += 'background-position:' + values.background_position + ';';
				}

				if ( 'none' !== values.background_blend_mode ) {
					backgroundImageStyle += 'background-blend-mode: ' + values.background_blend_mode + ';';
				}

				if ( '' !== values.background_repeat ) {
					backgroundImageStyle += 'background-repeat:' + values.background_repeat + ';';
					if ( 'no-repeat' === values.background_repeat ) {
						backgroundImageStyle += '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
					}
				}

				if ( ( ! cssua.ua.ie && ! cssua.ua.edge ) || ( 'none' !== values.hover_type || ( '' !== values.hover_type && 'none' !== values.hover_type ) || '' !== values.link ) ) {
					wrapperStyleBG += backgroundImageStyle;
				}

				// Border.
				if ( '' !== values.border_color && '' !== values.border_size && '' !== values.border_style ) {
					values.border_position = 'all' !== values.border_position ? '-' + values.border_position : '';
					wrapperStyle          += 'border' + values.border_position + ':' + values.border_size + ' ' + values.border_style + ' ' + values.border_color + ';';

					if ( 'liftup' === values.hover_type ) {
						wrapperStyleBG += 'border' + values.border_position + ':' + values.border_size + ' ' + values.border_style + ' ' + values.border_color + ';';
						classes        += ' fusion-column-liftup-border';
					}
				}

				// Border radius.
				if ( borderRadius ) {
					wrapperStyle   += 'overflow:hidden;border-radius:' + borderRadius + ';';
					wrapperStyleBG += 'border-radius:' + borderRadius + ';';
					wrapperClasses += ' fusion-column-has-overflow-hidden';
					if ( 'liftup' === values.hover_type ) {
						liftUpStyleTag = '<style type="text/css">.fusion-builder-column-live-' + this.model.get( 'cid' ) + ' .hover-type-liftup:before{border-radius:' + borderRadius + ';}</style>';
					} else if ( 'zoomin' === values.hover_type || 'zoomout' === values.hover_type || '' !== values.link ) {
						innerBgStyle += 'style="overflow:hidden;border-radius:' + borderRadius + ';"';
					}
				}

				// Box shadow.
				if ( 'yes' === values.box_shadow ) {
					values.box_shadow = _.fusionGetBoxShadowStyle( values );

					if ( 'liftup' === values.hover_type ) {
						wrapperStyleBG += 'box-shadow:' + values.box_shadow.trim() + ';';
					} else {
						wrapperStyle   += 'box-shadow:' + values.box_shadow.trim() + ';';
						wrapperClasses += ' fusion-column-has-shadow';
					}
				}

				if ( '' !== values.hover_type ) {
					classes += ' fusion-image-hover-effect';
				}

				// Padding.
				if ( '' !== values.padding ) {
					wrapperStyle += 'padding: ' + values.padding + ';';
				}

				// Top margin.
				if ( '' !== values.margin_top ) {
					style += 'margin-top:' + values.margin_top + ';';
				}

				// Bottom margin.
				if ( '' !== values.margin_bottom ) {
					style += 'margin-bottom:' + values.margin_bottom + ';';
				}

				// Custom CSS class.
				if ( 'undefined' !== typeof values[ 'class' ] && '' !== values[ 'class' ] ) {
					classes += ' ' + values[ 'class' ];
				}

				// Visibility classes.
				classes = _.fusionVisibilityAtts( values.hide_on_mobile, classes );

				// Hover type or link.
				if ( ( values.link && '' !== values.link ) || ( 'none' !== values.hover_type && '' !== values.hover_type ) ) {
					classes += ' fusion-column-inner-bg-wrapper';
				}

				if ( values.first ) {
					classes += ' fusion-column-first';
				}

				if ( values.last ) {
					classes += ' fusion-column-last';
				}

				// Hover type or link+
				if ( '' !== values.link ) {
					hrefLink += 'href="' + values.link + '"';
				}

				if ( '_blank' === values.target ) {
					hrefLink += ' rel="noopener noreferrer" target="_blank"';
				} else if ( 'lightbox' === values.target   ) {
					hrefLink += ' data-rel="prettyPhoto"';
				}

				// Min height for newly created columns by the converter+
				if ( 'none' === values.min_height ) {
					classes += ' fusion-column-no-min-height';
				}

				// Wrapper Style
				wrapperStyle = '' !== wrapperStyle ? wrapperStyle : '';

				// Contents of column.
				innerContent = '<span class="fusion-builder-module-controls-container"><span class="fusion-builder-controls fusion-builder-module-controls"><a href="#" class="fusion-builder-add-element fusion-builder-module-control"><span class="fusiona-plus"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text">' + fusionBuilderText.add_element + '</span></span></a></span></span><div class="fusion-droppable fusion-droppable-horizontal fusion-element-target target-replace fusion-element-target-column"></div>';
				innerContent = '<div class="fusion-builder-column-content' + nestedClass + '" data-cid="' + this.model.get( 'cid' ) + '"><span class="fusion-builder-empty-column">' + innerContent + '</span></div>';

				// If content should be centered, add needed markup+
				if ( 'yes' === values.center_content ) {
					innerContent = '<div class="fusion-column-content-centered"><div class="fusion-column-content">' + innerContent + '</div></div>';
				}

				// Clearing div at end of inner content, as we did in old builder.
				innerContent += '<div class="fusion-clearfix"></div>';

				if ( '' !== values.id ) {
					fusionColumnAttributes.id = values.id;
				}
				if ( '' !== values.type ) {
					fusionColumnAttributes[ 'class' ] += ' ' + values.type;
				}
				if ( '' !== style ) {
					fusionColumnAttributes.style = style;
				}

				fusionColumnAttributes[ 'class' ] += ' fusion-layout-column fusion_builder_column fusion_builder_column_' + values.type + ' ' + classes;

				style = '<style type="text/css">';
				style += '.fusion-column-wrapper-live-' + this.model.get( 'cid' ) + ' {' + wrapperStyle + '}';
				style += '</style>';

				// Nested columns dont have filters.
				if ( 'fusion_builder_column' === this.model.attributes.element_type ) {
					styleSelector = '.fusion-builder-column-live-' + this.model.get( 'cid' );
				} else {
					styleSelector = { regular: '.fusion-builder-column .fusion-column-wrapper-live-' + this.model.get( 'cid' ), hover: '.fusion-builder-column:hover .fusion-column-wrapper-live-' + this.model.get( 'cid' ) };
				}
				style        += _.fusionGetFilterStyleElem( values, styleSelector, this.model.get( 'cid' ) );

				layout = this.model.attributes.params.type;
				layout = layout ? layout.replace( '_', '/' ) : '';

				this.model.set( 'selectors', fusionColumnAttributes );

				values.margin_bottom = 'undefined' === typeof values.margin_bottom || '' === values.margin_bottom ? '0px' : values.margin_bottom;
				values.padding_top   = 'undefined' === typeof values.padding_top || '' === values.padding_top ? '0px' : values.padding_top;

				// The data to pass on to the template.
				data.cid                    = this.model.get( 'cid' );
				data.layout                 = layout;
				data.wrapper_classes        = wrapperClasses;
				data.inner_content          = innerContent;
				data.wrapper_style_bg       = wrapperStyleBG;
				data.hover_type             = values.hover_type;
				data.link                   = values.link;
				data.background_image       = values.background_image;
				data.background_color_style = backgroundColorStyle;
				data.href_link              = hrefLink;
				data.isGlobal               = 'undefined' !== typeof values.fusion_global ? 'yes' : 'no';
				data.style                  = style;
				data.background_image_style = backgroundImageStyle;
				data.innerBgStyle           = innerBgStyle;
				data.liftUpStyleTag         = liftUpStyleTag;

				return data;
			},

			/**
			 * Toggles the 'active' class.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event triggering the class toggling.
			 * @return {void}
			 */
			sizesShow: function( event ) {
				var parentContainer = this.$el.closest( '.fusion-builder-container' ),
					sizesPopover = this.$el.find( '.column-sizes' ),
					columnOffsetTop = 0,
					html, header, headerBottom, conditional;

				if ( event ) {
					event.preventDefault();
					event.stopPropagation();
				}

				sizesPopover.removeClass( 'fusion-expand-to-bottom' );

				// This needs to be the way it is setup, as nested cols could trigger sizing on several cols at once.
				if ( ! this.$el.hasClass( 'active' ) ) {
					this.$el.addClass( 'active' );
					parentContainer.addClass( 'fusion-column-sizer-active' );

					columnOffsetTop = this.$el.offset().top;
					html = this.$el.closest( 'html' );
					conditional = false;

					if ( html.children( 'body' ).hasClass( 'fusion-top-header' ) ) {
						if ( jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-footer-parallax' ).length ) {
							sizesPopover.on( 'mouseenter', function() {
								jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#main' ).css( 'z-index', 'auto' );

								if ( 'fixed' === jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-footer-parallax' ).css( 'position' ) ) {
									jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-footer-parallax' ).css( 'z-index', '-1' );

									if ( jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#sliders-container' ).find( '.tfs-slider[data-parallax="1"]' ).length ) {
										jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#sliders-container' ).css( 'z-index', 'auto' );
									}
								}
							} );

							sizesPopover.on( 'mouseleave', function() {
								jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#main' ).css( 'z-index', '' );
								jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-footer-parallax' ).css( 'z-index', '' );
								jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#sliders-container' ).css( 'z-index', '' );
							} );
						}

						header = html.find( '.fusion-header-wrapper' );
						headerBottom = header.offset().top + header.outerHeight();
						conditional = 106 > columnOffsetTop - headerBottom;
					}

					if ( 54 > columnOffsetTop - 121 || conditional || sizesPopover.parents( '.fusion-fullwidth' ).hasClass( 'bg-parallax-parent' ) ) {
						sizesPopover.addClass( 'fusion-expand-to-bottom' );
					}
				} else {
					this.$el.removeClass( 'active' );
					parentContainer.removeClass( 'fusion-column-sizer-active' );

					sizesPopover.off( 'mouseover' ).off( 'mouseleave' );
				}

			},

			/**
			 * Toggle class to show content in bottom
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			offsetClass: function() {
				if ( 100 > this.$el.offset().top ) {
					this.$el.addClass( 'fusion-content-bottom' );
				} else if ( 100 < this.$el.offset().top && this.$el.hasClass( 'fusion-content-bottom' )  ) {
					this.$el.removeClass( 'fusion-content-bottom' );
				}
			},

			/**
			 * Column spacing dimensions version.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			fallbackColumnSpacing: function( $placeholder, allNo ) {
				var columnSize      = '100%',
					fullcolumnSize  = columnSize,
					existingSpacing = '0%',
					columnWidth     = this.model.attributes.params.type,
					spacingDirection;

				if ( 'yes' === this.model.attributes.params.spacing ) {
					existingSpacing = '4%';
				}

				columnWidth = this.model.attributes.params.type;

				switch ( columnWidth ) {
				case '1_1':
					columnSize     = '100%';
					fullcolumnSize = '100%';
					break;
				case '1_4':
					columnSize     = '22%';
					fullcolumnSize = '25%';
					break;
				case '3_4':
					columnSize     = '74%';
					fullcolumnSize = '75%';
					break;
				case '1_2':
					columnSize     = '48%';
					fullcolumnSize = '50%';
					break;
				case '1_3':
					columnSize     = '30.6666%';
					fullcolumnSize = '33.3333%';
					break;
				case '2_3':
					columnSize     = '65.3333%';
					fullcolumnSize = '66.6666%';
					break;
				case '1_5':
					columnSize     = '16.8%';
					fullcolumnSize = '20%';
					break;
				case '2_5':
					columnSize     = '37.6%';
					fullcolumnSize = '40%';
					break;
				case '3_5':
					columnSize     = '58.4%';
					fullcolumnSize = '60%';
					break;
				case '4_5':
					columnSize     = '79.2%';
					fullcolumnSize = '80%';
					break;
				case '5_6':
					columnSize     = '82.6666%';
					fullcolumnSize = '83.3333%';
					break;
				case '1_6':
					columnSize     = '13.3333%';
					fullcolumnSize = '16.6666%';
					break;
				}

				if ( '4%' !== existingSpacing && ( ! this.model.attributes.params.last || allNo ) ) {
					columnSize = fullcolumnSize;
				}

				this.$el.css( 'width', columnSize );
				$placeholder.css( 'width', columnSize );

				spacingDirection = 'right';
				if ( FusionPageBuilderApp.$el.hasClass( 'rtl' ) ) {
					spacingDirection = 'left';
				}
				$placeholder.css( 'margin-' + spacingDirection, existingSpacing );
				this.$el.css( 'margin-' + spacingDirection, existingSpacing );
			},

			/**
			 * Column spacing dimensions version.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			dimensionColumnSpacing: function( columnRow, columnWidth, $placeholder ) {
				var decimalWidth,
					check,
					spacingWidth,
					existingSpacing,
					spacings = [],
					spacingDirection;

				// Remove last from calcs.
				columnRow.pop();

				columnWidth  = columnWidth[ 0 ] / columnWidth[ 1 ];
				decimalWidth = columnWidth;

				if ( 'object' === typeof columnRow ) {
					check = columnRow.every( this.pxCheck );
					if ( check ) {
						spacingWidth = ( columnRow.reduce( this.addValues, 0 ) * decimalWidth ) + 'px';
						this.$el.css( 'width', 'calc( ' + ( columnWidth * 100 ) + '% - ' + spacingWidth + ' )' );
						$placeholder.css( 'width', 'calc( ' + ( columnWidth * 100 ) + '% - ' + spacingWidth + ' )' );
					} else if ( columnRow.every( this.percentageCheck ) ) {
						columnWidth = ( columnWidth * 100 ) - ( columnRow.reduce( this.addValues, 0 ) * decimalWidth );
						this.$el.css( 'width', columnWidth + '%' );
						$placeholder.css( 'width', columnWidth + '%' );
					} else {

						_.each( columnRow, function( space ) {
							space = ( 'undefined' === typeof space || '' === space ) ? '4%' : space;
							spacings.push( space );
						} );

						spacingWidth = spacings.join( ' + ' );
						this.$el.css( 'width', 'calc( ' + ( columnWidth * 100 ) + '% - ( ( ' + spacingWidth + ' ) * ' + decimalWidth + ' )' );
						$placeholder.css( 'width', 'calc( ' + ( columnWidth * 100 ) + '% - ( ( ' + spacingWidth + ' ) * ' + decimalWidth + ' )' );
					}
				}

				existingSpacing = this.model.attributes.params.spacing;
				if ( 'undefined' === typeof this.model.attributes.params.spacing || 'yes' === this.model.attributes.params.spacing || '' === this.model.attributes.params.spacing ) {
					existingSpacing = '4%';
				}
				if ( 'no' === this.model.attributes.params.spacing ) {
					existingSpacing = '0';
				}

				spacingDirection = 'right';
				if ( FusionPageBuilderApp.$el.hasClass( 'rtl' ) ) {
					spacingDirection = 'left';
				}
				$placeholder.css( 'margin-' + spacingDirection, existingSpacing );
				this.$el.css( 'margin-' + spacingDirection, existingSpacing );
			},

			/**
			 * Check if value is valid for column spacing.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			validColumnSpacing: function( value ) {
				if ( 'yes' !== value && 'no' !== value && ! ( /\d/ ).test( value ) && '' !== value ) {
					return false;
				}
				return true;
			},

			/**
			 * Filter out DOM before patching.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			patcherFilter: function( diff ) {
				var filteredDiff = [],
					self = this;

				_.each( diff, function( info ) {
					if ( 'removeElement' === info.action ) {
						if ( 'undefined' !== typeof info.element.attributes[ 'class' ] && ( -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-column-content-centered' ) || -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-column-content-centered' ) ) ) {
							self.forceAppendChildren = true;
						}

						if (
							'undefined' !== typeof info.element.attributes[ 'class' ] &&
							(
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-spacing-value' ) ||
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-element-spacing' ) ||
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-builder-live-element' ) ||
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-builder-live-element' ) ||
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion_builder_row_inner' ) ||
								-1 !== info.element.attributes[ 'class' ].indexOf( 'fusion_builder_row_inner' )
							)
						) {

							// ignore
						} else {
							filteredDiff.push( info );
						}
					} else if ( 'addElement' === info.action ) {
						if ( 'undefined' !== typeof info.element.attributes[ 'class' ] && ( -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-column-content-centered' ) || -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-column-content-centered' ) ) ) {
							self.forceAppendChildren = true;
						}

						if ( 'undefined' !== typeof info.element.attributes[ 'class' ] && ( -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-spacing-value' ) || -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-element-spacing' ) ) ) {

							// ignore
						} else {
							filteredDiff.push( info );
						}
					} else {
						filteredDiff.push( info );
					}
				} );

				return filteredDiff;
			},

			/**
			 * Adds a delay to the change trigger to accomodate equal-heights implementation.
			 *
			 * @since 2.0.0
			 * @param {number|string} cid - The CID of the element.
			 * @return {void}
			 */
			equalHeights: function( cid ) {
				cid = 'undefined' === typeof cid ? this.model.attributes.cid : cid;
				setTimeout( function() {
					jQuery( document ).trigger( 'fusion-content-changed', cid );
					jQuery( window ).trigger( 'fusion-content-changed', cid );
					jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-content-changed', cid );
				}, 300 );
			},

			/**
			 * Removes the 'active' class.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			toolTipHide: function() {
				this.$el.find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).removeClass( 'active' );
			},

			/**
			 * Resize spacer on window resize event.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			resizeSpacer: function() {
				if ( this.columnSpacer ) {
					this.columnSpacing();
				}
			},

			/**
			 * Preview column-spacing changes.
			 *
			 * @since 2.0.0
			 * @param {Object} columnRow - The row.
			 * @return {void}
			 */
			columnSpacingPreview: function( columnRow ) {
				var columnWidth = 'undefined' !== typeof this.model.attributes.params.type ? this.model.attributes.params.type.split( '_' ) : [ '1', '1' ],
					fallback = true,
					origValue,
					$placeholder = jQuery( '.fusion-builder-column-placeholder[data-cid="' + this.model.get( 'cid' ) + '"]' ),
					allNo = true;

				_.each( columnRow, function( value, index ) {
					origValue          = value;
					value              = ( 'yes' === value ) ? '4%' : value;
					value              = ( 'no' === value ) ? '0' : value;
					fallback           = fallback && origValue !== value;
					allNo              = allNo && 0 === parseInt( value, 10 );
					columnRow[ index ]   = value;
				} );

				if ( ! fallback ) {
					this.dimensionColumnSpacing( columnRow, columnWidth, $placeholder );
				} else {
					this.fallbackColumnSpacing( $placeholder, allNo );
				}
			},

			/**
			 * Gets the column content.
			 * Alias of getColumnContent method.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getContent: function() {
				return this.getColumnContent();
			}

		} );
	} );
}( jQuery ) );
