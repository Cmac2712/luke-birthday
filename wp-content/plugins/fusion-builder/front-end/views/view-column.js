/* global FusionPageBuilderViewManager, fusionGlobalManager, fusionBuilderText, FusionPageBuilderApp, FusionPageBuilderElements, FusionEvents, fusionAllElements */
/* eslint no-unused-vars: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Column View
		FusionPageBuilder.ColumnView = FusionPageBuilder.BaseColumnView.extend( {

			template: FusionPageBuilder.template( jQuery( '#fusion-builder-column-template' ).html() ),

			events: {
				'click .fusion-builder-column-settings:not(.fusion-builder-column-inner .fusion-builder-column-setting)': 'settings',
				'click .fusion-builder-column-size:not(.fusion-builder-column-inner .fusion-builder-column-size)': 'sizesShow',
				'hover .fusion-builder-column-content': 'offsetClass',
				'click .column-size:not(.fusion-builder-column-inner .column-size)': 'sizeSelect',
				'click .fusion-builder-add-element:not(.fusion-builder-column-inner .fusion-builder-add-element)': 'addModule',
				'click .fusion-builder-column-remove:not(.fusion-builder-column-inner .fusion-builder-column-remove)': 'removeColumn',
				'click .fusion-builder-column-clone:not(.fusion-builder-column-inner .fusion-builder-column-clone)': 'cloneColumn',
				'click .fusion-builder-column-save:not(.fusion-builder-column-inner .fusion-builder-column-save)': 'openLibrary',
				'click .fusion-builder-column-drag:not(.fusion-builder-column-inner .fusion-builder-column-drag)': 'preventDefault'
			},

			/**
			 * Init.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			initialize: function() {
				var params  = this.model.get( 'params' ),
					spacing = '' !== params.spacing ? spacing : '4%';

				this.renderedYet         = false;
				this.columnSpacer        = false;
				this.forceAppendChildren = false;

				this.listenTo( FusionEvents, 'fusion-view-update-fusion_builder_column', this.reRender );
				this.listenTo( FusionEvents, 'fusion-param-changed-' + this.model.get( 'cid' ), this.onOptionChange );

				this._triggerCallback = _.debounce( _.bind( this.triggerCallback, this ), 200 );

				this._toolTipHide = _.debounce( _.bind( this.toolTipHide, this ), 500 );
				this.$el.attr( 'data-cid', this.model.get( 'cid' ) );
				this.$el.attr( 'id', 'fusion-column-' + this.model.get( 'cid' ) );
				this.$el.attr( 'data-column-size', this.model.attributes.params.type );
				this.$el.attr( 'data-column-spacing', spacing );

				if ( 'undefined' !== typeof this.model.attributes.params && 'undefined' !== typeof this.model.attributes.params.fusion_global ) {
					this.$el.attr( 'fusion-global-layout', this.model.attributes.params.fusion_global );
					this.$el.removeClass( 'fusion-global-column' ).addClass( 'fusion-global-column' );
				}

				this.model.children = new FusionPageBuilder.Collection();

				this.listenTo( this.model.children, 'add', this.addChildView );

				this.currentClasses = '';

				// JQuery trigger.
				this._refreshJs = _.debounce( _.bind( this.refreshJs, this ), 300 );

				// Equal height trigger.
				this._equalHeights = _.debounce( _.bind( this.equalHeights, this ), 300 );

				this.listenTo( FusionEvents, 'fusion-wireframe-toggle', this.wireFrameToggled );

				this.deprecatedParams();

				this.baseInit();
			},

			/**
			 * Renders the view.
			 *
			 * @since 2.0.0
			 * @return {Object} this
			 */
			render: function() {
				var self = this,
					data = this.getTemplateAtts(),
					columnSize = '';

				this.$el.html( this.template( data ) );

				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}

				// Add active column size CSS class
				columnSize = this.model.attributes.params.type;
				this.$el.find( '.column-size-' + columnSize ).addClass( 'active-size' );

				this.appendChildren();

				setTimeout( function() {
					self.droppableColumn();
					self.sortableElements();
					self.disableSortableElements();
				}, 100 );

				// Don't refresh on first render.
				if ( this.renderedYet ) {
					this._refreshJs();
				}

				this.renderedYet = true;

				return this;
			},

			droppableColumn: function() {
				var self = this,
					$el  = this.$el,
					cid,
					$droppables,
					$body;

				if ( ! $el ) {
					return;
				}

				cid         = this.model.get( 'cid' );
				$droppables = $el.find( '.fusion-column-target' );
				$body       = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' );

				$el.draggable( {
					appendTo: FusionPageBuilderApp.$el,
					zIndex: 999999,
					delay: 100,
					cursorAt: { top: 15, left: 15 },
					iframeScroll: true,
					containment: $body,
					cancel: '.fusion-builder-live-element, .fusion_builder_row_inner',
					helper: function() {
						var $classes = FusionPageBuilderApp.DraggableHelpers.draggableClasses( cid ),
							style = '';

						if ( $el.css( 'margin-top' ) ) {
							style = 'style="transform: translateY(' + $el.css( 'margin-top' ) + ');"';
						}

						return jQuery( '<div><div class="fusion-column-helper ' + $classes + '" data-cid="' + cid + '"' + style + '><span class="fusiona-column"></span></div></div>' );
					},
					start: function() {
						$body.addClass( 'fusion-column-dragging fusion-active-dragging' );
						$el.addClass( 'fusion-being-dragged' );
					},
					stop: function() {
						setTimeout( function() {
							$body.removeClass( 'fusion-column-dragging fusion-active-dragging' );
						}, 10 );
						$el.removeClass( 'fusion-being-dragged' );
					}
				} );

				$droppables.droppable( {
					tolerance: 'touch',
					hoverClass: 'ui-droppable-active',
					accept: '.fusion-builder-column',
					drop: function( event, ui ) {
						var parentCid,
							destinationRow,
							columnCid      = ui.draggable.data( 'cid' ),
							columnView     = FusionPageBuilderViewManager.getView( columnCid ),
							originalCid    = columnView.model.get( 'parent' ),
							$target        = $el,
							originalView,
							newIndex;

						// Move the actual html.
						if ( jQuery( event.target ).hasClass( 'target-after' ) ) {
							$target.after( ui.draggable );
						} else {
							$el.before( ui.draggable );
						}

						parentCid      = ui.draggable.closest( '.fusion-builder-row' ).data( 'cid' );
						destinationRow = FusionPageBuilderViewManager.getView( parentCid );

						newIndex = ui.draggable.parent().children( '.fusion-builder-column' ).index( ui.draggable );

						FusionPageBuilderApp.onDropCollectionUpdate( columnView.model, newIndex, parentCid );

						// Update destination row which is this current one.
						destinationRow.setRowData();

						// If destination row and original row are different, update original as well.
						if ( parentCid !== originalCid ) {
							originalView = FusionPageBuilderViewManager.getView( originalCid );
							originalView.setRowData();
						}

						FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.column + ' order changed' );

						setTimeout( function() {
							columnView.droppableColumn();
						}, 300 );
					}
				} );

				$el.find( '.fusion-element-target-column' ).droppable( {
					tolerance: 'touch',
					hoverClass: 'ui-droppable-active',
					accept: '.fusion-builder-live-element, .fusion_builder_row_inner',
					drop: function( event, ui ) {
						var elementView  = FusionPageBuilderViewManager.getView( ui.draggable.data( 'cid' ) ),
							newIndex,
							MultiGlobalArgs;

						// Move the actual html.
						$el.find( '.fusion-builder-column-content:not(.fusion_builder_row_inner .fusion-builder-column-content ):not( .fusion-nested-column-content )' ).append( ui.draggable );

						newIndex = ui.draggable.parent().children( '.fusion-builder-live-element, .fusion_builder_row_inner' ).index( ui.draggable );

						FusionPageBuilderApp.onDropCollectionUpdate( elementView.model, newIndex, self.model.get( 'cid' ) );

						FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.moved + ' ' + fusionAllElements[ elementView.model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element );

						// Handle multiple global elements.
						MultiGlobalArgs = {
							currentModel: elementView.model,
							handleType: 'save',
							attributes: elementView.model.attributes
						};
						fusionGlobalManager.handleMultiGlobal( MultiGlobalArgs );

						FusionEvents.trigger( 'fusion-content-changed' );

						self._equalHeights();
					}
				} );

				// If we are in wireframe mode, then disable.
				if ( FusionPageBuilderApp.wireframeActive ) {
					this.disableDroppableColumn();
				}
			},

			/**
			 * Things to do, places to go when options change.
			 *
			 * @since 2.0.0
			 * @param {string} paramName - The name of the parameter that changed.
			 * @param {mixed}  paramValue - The value of the option that changed.
			 * @param {Object} event - The event triggering the option change.
			 * @return {void}
			 */
			onOptionChange: function( paramName, paramValue, event ) {
				var rowView,
					parentCID            = this.model.get( 'parent' ),
					cid                  = this.model.get( 'cid' ),
					reInitDraggables     = false,
					view                 = {},
					values               = {},
					alphaBackgroundColor = 1;

				// Reverted to history step or user entered value manually.
				if ( 'undefined' === typeof event || ( 'undefined' !== typeof event && ( 'change' !== event.type || ( 'change' === event.type && 'undefined' !== typeof event.srcElement ) ) ) ) {
					reInitDraggables = true;
				}

				switch ( paramName ) {

				case 'spacing':
					this.model.attributes.params[ paramName ] = paramValue;

					// Only update preview if it a valid unit.
					if ( this.validColumnSpacing( paramValue ) ) {
						rowView = FusionPageBuilderViewManager.getView( parentCID );
						rowView.setSingleRowData( cid );
					}

					if ( true === reInitDraggables ) {
						if ( 'yes' === paramValue || 'no' === paramValue ) {
							this.destroySpacingResizable();
						} else {
							this.columnSpacer = false;
							this.columnSpacing();
						}
					}

					break;

				case 'margin_top':
				case 'margin_bottom':
					this.model.attributes.params[ paramName ] = paramValue;

					if ( true === reInitDraggables ) {
						this.destroyMarginResizable();
						this.marginDrag();
					}
					break;

				case 'padding_top':
				case 'padding_right':
				case 'padding_bottom':
				case 'padding_left':
					this.model.attributes.params[ paramName ] = paramValue;

					if ( true === reInitDraggables ) {
						this.destroyPaddingResizable();
						this.paddingDrag();
					}
					break;

				case 'border_size':
				case 'border_color':
				case 'border_style':
				case 'border_position':
					this.model.attributes.params[ paramName ] = paramValue;

					// this.borderStyle( event );
					break;

				case 'padding':
					if ( -1 === jQuery( event.target ).attr( 'name' ).indexOf( '_' ) ) {
						this.model.attributes.params[ paramName ] = paramValue;
						this.renderSectionSeps( event );
						this._refreshJs();
					}
					break;
				}
			},

			/**
			 * Render the section separators.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			renderSectionSeps: function() {
				var elements = FusionPageBuilderViewManager.getChildViews( this.model.get( 'cid' ) );
				_.each( elements, function( element ) {
					if ( 'fusion_section_separator' === element.model.get( 'element_type' ) ) {
						element.reRender();
					}
				} );
			},

			/**
			 * Triggers a refresh.
			 *
			 * @since 2.0.0
			 * @return void
			 */
			refreshJs: function() {
				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-element-render-fusion_builder_column', this.model.attributes.cid );
			},

			/**
			 * Changes the border styles for the element.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			borderStyle: function( event ) {
				var borderSize     = this.model.attributes.params.border_size + 'px',
					borderColor    = this.model.attributes.params.border_color,
					borderStyle    = this.model.attributes.params.border_style,
					borderPosition = this.model.attributes.params.border_position,
					positions      = [ 'top', 'right', 'bottom', 'left' ],
					self           = this,
					$target        = ( 'lift_up' === this.model.attributes.params.hover_type ) ? self.$el.find( '.fusion-column-wrapper, .fusion-column-inner-bg-image' ) : self.$el.find( '.fusion-column-wrapper' );

				if ( event ) {
					event.preventDefault();
				}
				self.$el.find( '.fusion-column-wrapper, .fusion-column-inner-bg-image' ).css( 'border', '' );
				if ( 'all' === borderPosition ) {
					_.each( positions, function( position ) {
						$target.css( 'border-' + position, borderSize + ' ' + borderStyle + ' ' + borderColor );
					} );
				} else {
					_.each( positions, function( position ) {
						if ( position === borderPosition ) {
							$target.css( 'border-' + position, borderSize + ' ' + borderStyle + ' ' + borderColor );
						} else {
							$target.css( 'border-' + position, 'none' );
						}
					} );
				}
			},

			/**
			 * Sets the attributes of an element.
			 *
			 * @since 2.0.0
			 * @param {Object} element - The element we're updating.
			 * @param {Object} attributes - The attributes we're setting/updating.
			 * @return {void}
			 */
			setElementAttributes: function( element, attributes ) {
				var self = this;

				element.removeClass( this.currentClasses );

				if ( 'object' === typeof attributes && element.length ) {
					_.each( attributes, function( values, attribute ) {
						if ( 'class' === attribute ) {
							self.currentClasses = values;
							element.addClass( values );
						} else if ( 'id' === attribute ) {
							element.attr( 'id', values );
						} else if ( 'style' === attribute ) {
							element.attr( 'style', values );
						} else if ( -1 !== attribute.indexOf( 'data' ) ) {
							attribute = attribute.replace( /_/g, '-' );
							element.attr( attribute, values );
						}
					} );
				}
			},

			/**
			 * Clones a column.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			cloneColumn: function( event ) {
				var columnAttributes = jQuery.extend( true, {}, this.model.attributes ),
					$thisColumn,
					container;

				if ( event ) {
					event.preventDefault();
				}

				columnAttributes.created       = 'manually';
				columnAttributes.cid           = FusionPageBuilderViewManager.generateCid();
				columnAttributes.targetElement = this.$el;
				columnAttributes.cloned        = true;
				columnAttributes.at_index      = FusionPageBuilderApp.getCollectionIndex( this.$el );

				FusionPageBuilderApp.collection.add( columnAttributes );

				// Parse column elements
				$thisColumn = this.$el;
				$thisColumn.find( '.fusion-builder-live-element:not(.fusion-builder-column-inner .fusion-builder-live-element), .fusion-builder-nested-element' ).each( function() {
					var $thisModule,
						moduleCID,
						module,
						elementAttributes,
						$thisInnerRow,
						innerRowCID,
						innerRowView;

					// Standard element
					if ( jQuery( this ).hasClass( 'fusion-builder-live-element' ) ) {
						$thisModule = jQuery( this );
						moduleCID = 'undefined' === typeof $thisModule.data( 'cid' ) ? $thisModule.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisModule.data( 'cid' );

						// Get model from collection by cid
						module = FusionPageBuilderElements.find( function( model ) {
							return model.get( 'cid' ) == moduleCID; // jshint ignore: line
						} );

						// Clone model attritubes
						elementAttributes          = jQuery.extend( true, {}, module.attributes );

						elementAttributes.created  = 'manually';
						elementAttributes.cid      = FusionPageBuilderViewManager.generateCid();
						elementAttributes.parent   = columnAttributes.cid;
						elementAttributes.from     = 'fusion_builder_column';

						// Don't need target element, position is defined from order.
						delete elementAttributes.targetElementPosition;

						FusionPageBuilderApp.collection.add( elementAttributes );

					// Inner row/nested element
					} else if ( jQuery( this ).hasClass( 'fusion_builder_row_inner' ) ) {
						$thisInnerRow = jQuery( this );
						innerRowCID = 'undefined' === typeof $thisInnerRow.data( 'cid' ) ? $thisInnerRow.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisInnerRow.data( 'cid' );

						innerRowView = FusionPageBuilderViewManager.getView( innerRowCID );

						// Clone inner row
						if ( 'undefined' !== typeof innerRowView ) {
							innerRowView.cloneNestedRow( '', columnAttributes.cid );
						}
					}

				} );

				// If column is cloned manually
				if ( event ) {

					FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.cloned + ' ' + fusionBuilderText.column );

					container = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );

					container.createVirtualRows();
					container.updateColumnsPreview();

					FusionEvents.trigger( 'fusion-content-changed' );
				}
				this._refreshJs();
			},

			/**
			 * Append the column's children to its content.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			appendChildren: function() {
				var self = this,
					cid,
					view;

				this.model.children.each( function( child ) {

					cid  = child.attributes.cid;
					view = FusionPageBuilderViewManager.getView( cid );

					self.$el.find( '.fusion-builder-column-content:not(.fusion_builder_row_inner .fusion-builder-column-content ):not( .fusion-nested-column-content )' ).append( view.$el );

				} );

				this.delegateChildEvents();
			},

			/**
			 * Gets the column contents.
			 *
			 * @since 2.0.0
			 * @param {Object} $thisColumn - The jQuery object of the element.
			 * @return {string}
			 */
			getColumnContent: function() {
				var shortcode    = '',
					columnParams = {},
					ColumnAttributesCheck;

				_.each( this.model.get( 'params' ), function( value, name ) {
					columnParams[ name ] = ( 'undefined' === value ) ? '' : value;
				} );

				// Legacy support for new column options
				ColumnAttributesCheck = {
					min_height: '',
					last: 'no',
					hover_type: 'none',
					link: '',
					border_position: 'all'
				};

				_.each( ColumnAttributesCheck, function( value, name ) {
					if ( 'undefined' === typeof columnParams[ name ] ) {
						columnParams[ name ] = value;
					}
				} );

				// Build column shortcode
				shortcode += '[fusion_builder_column type="' + this.model.attributes.params.type + '"';

				_.each( columnParams, function( value, name ) {
					shortcode += ' ' + name + '="' + value + '"';
				} );

				shortcode += ']';

				// Find elements inside this column
				this.$el.find( '.fusion-builder-live-element:not(.fusion-builder-column-inner .fusion-builder-live-element), .fusion-builder-nested-element' ).each( function() {
					var $thisRowInner;

					// Find standard elements
					if ( jQuery( this ).hasClass( 'fusion-builder-live-element' ) ) {
						shortcode += FusionPageBuilderApp.generateElementShortcode( jQuery( this ), false );

					// Find inner rows
					} else {
						$thisRowInner = FusionPageBuilderViewManager.getView( jQuery( this ).data( 'cid' ) );
						if ( 'undefined' !== typeof $thisRowInner ) {
							shortcode += $thisRowInner.getInnerRowContent();
						}

					}
				} );

				shortcode += '[/fusion_builder_column]';

				return shortcode;
			},

			/**
			 * Removes a column.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event triggering the column removal.
			 * @return {void}
			 */
			removeColumn: function( event ) {
				var elements,
					rowView,
					parentCID = this.model.get( 'parent' );

				if ( event ) {
					event.preventDefault();
				}

				elements = FusionPageBuilderViewManager.getChildViews( this.model.get( 'cid' ) );

				_.each( elements, function( element ) {
					if ( 'fusion_builder_row' === element.model.get( 'type' ) || 'fusion_builder_row_inner' === element.model.get( 'type' ) ) {
						element.removeRow();
					} else {
						element.removeElement();
					}
				} );

				FusionPageBuilderViewManager.removeView( this.model.get( 'cid' ) );

				this._equalHeights( parentCID );

				FusionEvents.trigger( 'fusion-element-removed', this.model.get( 'cid' ) );

				this.model.destroy();

				this.remove();

				// If the column is deleted manually
				if ( event ) {

					// Update preview for spacing.
					rowView = FusionPageBuilderViewManager.getView( parentCID );
					rowView.setRowData();

					FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.deleted + ' ' + fusionBuilderText.column );

					FusionEvents.trigger( 'fusion-content-changed' );
				}
			},

			/**
			 * Adds a child view.
			 *
			 * @since 2.0.0
			 * @param {Object} element - The element.
			 * @return {void}
			 */
			addChildView: function( element ) {

				var view,
					viewSettings = {
						model: element,
						collection: FusionPageBuilderElements,
						attributes: {
							'data-cid': element.get( 'cid' )
						}
					},
					containerSuffix = ':not(.fusion_builder_row_inner .fusion-builder-column-content)';

				if ( 'undefined' !== typeof element.get( 'multi' ) && 'multi_element_parent' === element.get( 'multi' ) ) {

					if ( 'undefined' !== typeof FusionPageBuilder[ element.get( 'element_type' ) ] ) {
						view = new FusionPageBuilder[ element.get( 'element_type' ) ]( viewSettings );
					} else {
						view = new FusionPageBuilder.ParentElementView( viewSettings );
					}

				} else if ( 'undefined' !== typeof FusionPageBuilder[ element.get( 'element_type' ) ] ) {
					view = new FusionPageBuilder[ element.get( 'element_type' ) ]( viewSettings );
				} else if ( 'fusion_builder_row_inner' === element.get( 'element_type' ) ) {
					view = new FusionPageBuilder.InnerRowView( viewSettings );
				} else {
					view = new FusionPageBuilder.ElementView( viewSettings );
				}

				// Add new view to manager.
				FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

				if (  'undefined' !== typeof this.model && 'fusion_builder_column_inner' === this.model.get( 'type' ) ) {
					containerSuffix = '';
				}

				if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
					if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
						element.get( 'targetElement' ).after( view.render().el );
					} else {
						element.get( 'targetElement' ).before( view.render().el );
					}
				} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
					if ( 'fusion_widget' === view.model.get( 'element_type' ) ) {
						// eslint-disable-next-line vars-on-top
						var renderedView = view.render();
						renderedView.$el.find( 'script' ).remove();
						this.$el.find( '.fusion-builder-column-content' + containerSuffix ).append( renderedView.el );
					} else {
						this.$el.find( '.fusion-builder-column-content' + containerSuffix ).append( view.render().el );
					}
				} else {
					this.$el.find( '.fusion-builder-column-content' + containerSuffix ).find( '.fusion-builder-empty-column' ).first().after( view.render().el );
				}

				// Check if we should open the settings or not.
				if ( 'off' !== window.FusionApp.preferencesData.open_settings && 'undefined' !== typeof element.get( 'added' ) ) {
					if ( 'fusion_builder_row_inner' === element.get( 'type' ) ) {
						if ( ! jQuery( 'body' ).hasClass( 'fusion-builder-ui-wireframe' ) ) {
							view.editRow();
						}
					} else {
						view.settings();
					}
				}
			},

			/**
			 * Delegates multiple child elements.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			delegateChildEvents: function() {
				var cid,
					view;

				this.model.children.each( function( child ) {
					cid  = child.attributes.cid;
					view = FusionPageBuilderViewManager.getView( cid );

					view.delegateEvents();

					// Re init for elements.
					if ( 'function' === typeof view.droppableElement ) {
						view.droppableElement();
					}

					// Re init for nested row.
					if ( 'function' === typeof view.droppableColumn ) {
						view.droppableColumn();
					}

					// Multi elements
					if ( 'undefined' !== typeof view.model.get( 'multi' ) && 'multi_element_parent' === view.model.get( 'multi' ) ) {
						view.delegateChildEvents();
						view.sortableChildren();
					}
				} );
			},

			/**
			 * Get the save label.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getSaveLabel: function() {
				return fusionBuilderText.save_column;
			},

			/**
			 * Returns the 'columns' string.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getCategory: function() {
				return 'columns';
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
					columnWidth     = this.model.attributes.params.type;

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
				$placeholder.css( 'margin-right', existingSpacing );
				this.$el.css( 'margin-right', existingSpacing );
			},

			scrollHighlight: function( scroll ) {
				var self     = this,
					$trigger = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-one-page-text-link' ),
					$el      = this.$el;

				scroll   = 'undefined' === typeof scroll ? true : scroll;

				setTimeout( function() {
					if ( scroll && $trigger.length && 'function' === typeof $trigger.fusion_scroll_to_anchor_target ) {
						$trigger.attr( 'href', '#fusion-column-' + self.model.get( 'cid' ) ).fusion_scroll_to_anchor_target( 15 );
					}

					$el.addClass( 'fusion-active-highlight' );
					setTimeout( function() {
						$el.removeClass( 'fusion-active-highlight' );
					}, 6000 );
				}, 10 );
			},

			/**
			 * Destroy the droppable and draggable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			disableDroppableColumn: function() {
				var $el         = this.$el,
					$droppables = $el.find( '.fusion-column-target' );

				if ( 'undefined' !== typeof $el.draggable( 'instance' ) ) {
					$el.draggable( 'destroy' );
				}

				if ( 'undefined' !== typeof $droppables.droppable( 'instance' ) ) {
					$droppables.droppable( 'destroy' );
				}

				if ( 'undefined' !== typeof $el.find( '.fusion-element-target-column' ).droppable( 'instance' ) ) {
					$el.find( '.fusion-element-target-column' ).droppable( 'destroy' );
				}
			},

			/**
			 * Enable the droppable and draggable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			enableDroppableColumn: function() {
				this.droppableColumn();
			},

			/**
			 * Initialize element sortable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			sortableElements: function() {
				var self = this;

				this.$el.find( '.fusion-builder-column-content' ).sortable( {
					items: '.fusion-builder-live-element:not(.fusion_builder_row_inner .fusion-builder-live-element), .fusion_builder_row_inner',
					connectWith: '.fusion-builder-column-content',
					cancel: '.fusion-builder-settings, .fusion-builder-clone, .fusion-builder-row-clone, .fusion-builder-remove, .fusion-builder-add-element, .fusion-builder-insert-column, .fusion-builder-save-module-dialog, .fusion-builder-row-remove, .fusion-builder-save-inner-row-dialog-button, .fusion_builder_row_inner .fusion-builder-row-content',
					tolerance: 'pointer',
					appendTo: FusionPageBuilderApp.$el,
					helper: 'clone',
					disabled: ! FusionPageBuilderApp.wireframeActive,
					over: function( event ) {
						self.onSortOver( event );
					},

					update: function( event, ui ) {
						self.onSortUpdate( event, ui );
					},

					stop: function( event, ui ) {
						self.onSortStop( event, ui );
					}
				} );
			}
		} );
	} );
}( jQuery ) );
