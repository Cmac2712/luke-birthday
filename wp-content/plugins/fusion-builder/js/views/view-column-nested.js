/* global fusionHistoryManager, fusionBuilderText, fusionAllElements, FusionPageBuilderEvents, FusionPageBuilderViewManager, FusionPageBuilderApp, FusionPageBuilderElements */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		// Nested Column View
		FusionPageBuilder.NestedColumnView = window.wp.Backbone.View.extend( {

			template: FusionPageBuilder.template( $( '#fusion-builder-inner-column-template' ).html() ),

			events: {
				'click .fusion-builder-add-element': 'addModule',
				'click .fusion-builder-settings-column': 'showSettings',
				'click .fusion-builder-resize-inner-column': 'columnSizeDialog',
				'click .column-size': 'columnSize',
				'click .fusion-builder-remove-inner-column': 'removeColumn',
				'click .fusion-builder-clone-inner-column': 'cloneColumn'
			},

			initialize: function() {
				this.$el.attr( 'data-cid', this.model.get( 'cid' ) );
				this.$el.attr( 'data-column-size', this.model.get( 'layout' ) );
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				this.sortableElements();

				return this;
			},

			sortableElements: function() {
				var thisEl = this;
				this.$el.sortable( {
					items: '.fusion_module_block',
					connectWith: '.fusion-builder-column-inner',
					cancel: '.fusion-builder-settings, .fusion-builder-clone, .fusion-builder-remove, .fusion-builder-add-element, .fusion-builder-insert-column, .fusion-builder-save-module-dialog',
					tolerance: 'pointer',

					update: function( event, ui ) {
						var $moduleBlock = $( ui.item ),
							moduleCID    = ui.item.data( 'cid' ),
							model        = thisEl.collection.find( function( model ) {
								return model.get( 'cid' ) === moduleCID;
							} );

						// If column is empty add before "Add Element" button
						if ( $( ui.item ).closest( event.target ).length && 1 === $( event.target ).find( '.fusion_module_block' ).length ) {
							$moduleBlock.insertBefore( $( event.target ).find( '.fusion-builder-add-element' ) );
						}

						// Moved the element within the same column
						if ( model.get( 'parent' ) === thisEl.model.attributes.cid && $( ui.item ).closest( event.target ).length ) {

						// Moved the element to a different column
						} else {
							model.set( 'parent', thisEl.model.attributes.cid );
						}

						// Save history state
						fusionHistoryManager.turnOnTracking();
						window.fusionHistoryState = fusionBuilderText.moved + ' ' + fusionAllElements[ model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element;
						FusionPageBuilderEvents.trigger( 'fusion-element-sorted' );
					}

				} );
			},

			showSettings: function( event ) {
				var modalView,
					viewSettings = {
						model: this.model,
						collection: this.collection,
						attributes: {
							'data-modal_view': 'element_settings'
						}
					};

				if ( event ) {
					event.preventDefault();
				}

				modalView = new FusionPageBuilder.ModalView( viewSettings );

				$( 'body' ).append( modalView.render().el );
			},

			removeColumn: function( event ) {
				var modules,
					parentCID;

				if ( event ) {
					event.preventDefault();
				}

				parentCID = this.model.get( 'parent' );

				modules = FusionPageBuilderViewManager.getChildViews( this.model.get( 'cid' ) );

				_.each( modules, function( module ) {
					module.removeElement();
				} );

				FusionPageBuilderViewManager.removeView( this.model.get( 'cid' ) );

				this.model.destroy();

				this.remove();

				// If the column is deleted manually
				if ( event ) {
					FusionPageBuilderEvents.trigger( 'fusion-element-removed' );
				}

				// Check for globals.
				setTimeout( FusionPageBuilderApp.checkGlobalParents, 500, parentCID );
			},

			cloneColumn: function( event ) {
				var columnAttributes = $.extend( true, {}, this.model.attributes ),
					$thisColumn;

				if ( event ) {
					event.preventDefault();
				}

				columnAttributes.created       = 'manually';
				columnAttributes.cid           = FusionPageBuilderViewManager.generateCid();
				columnAttributes.targetElement = this.$el;
				columnAttributes.cloned        = true;

				FusionPageBuilderApp.collection.add( columnAttributes );

				// Parse column elements
				$thisColumn = this.$el;
				$thisColumn.find( '.fusion_builder_column_element' ).each( function() {
					var $thisModule,
						moduleCID,
						module,
						elementAttributes;

					// Standard element
					if ( $( this ).hasClass( 'fusion_module_block' ) ) {
						$thisModule = $( this );
						moduleCID = 'undefined' === typeof $thisModule.data( 'cid' ) ? $thisModule.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisModule.data( 'cid' );

						// Get model from collection by cid
						module = FusionPageBuilderElements.find( function( model ) {
							return model.get( 'cid' ) === moduleCID;
						} );

						// Clone model attritubes
						elementAttributes         = $.extend( true, {}, module.attributes );
						elementAttributes.created = 'manually';
						elementAttributes.cid     = FusionPageBuilderViewManager.generateCid();
						elementAttributes.parent  = columnAttributes.cid;
						elementAttributes.from    = 'fusion_builder_column';

						FusionPageBuilderApp.collection.add( elementAttributes );
					}
				} );

				// If column is cloned manually
				if ( event ) {

					// Save history state
					fusionHistoryManager.turnOnTracking();
					window.fusionHistoryState = fusionBuilderText.cloned_nested_columns;

					FusionPageBuilderEvents.trigger( 'fusion-element-cloned' );
				}
			},

			addModule: function( event ) {
				var view,
					$eventTarget,
					$addModuleButton;

				if ( event ) {
					event.preventDefault();
					event.stopPropagation();
				}

				FusionPageBuilderApp.innerColumn = 'true';
				FusionPageBuilderApp.parentColumnId = this.model.get( 'cid' );

				$eventTarget     = $( event.target );
				$addModuleButton = $eventTarget.is( 'span' ) ? $eventTarget.parent( '.fusion-builder-add-element' ) : $eventTarget;

				if ( ! $addModuleButton.parent().is( event.delegateTarget ) ) {
					return;
				}

				view = new FusionPageBuilder.ModalView( {
					model: this.model,
					collection: this.collection,
					attributes: {
						'data-modal_view': 'element_library'
					},
					view: this
				} );

				$( 'body' ).append( view.render().el );
			},

			columnSizeDialog: function( event ) {
				var leftPosition = this.$el.position().left,
					modelWidth = this.$el.parents( '.fusion-builder-row-container-inner' ).width(),
					columnSizeWidth = 240;

				if ( event ) {
					event.preventDefault();
				}

				if ( modelWidth - leftPosition < columnSizeWidth ) {
					this.$el.find( '.column-sizes' ).css( { left: 'auto', right: '0' } );
				}
				this.$el.find( '.column-sizes' ).toggle();
			},

			columnSize: function( event ) {
				var $thisEl = $( event.currentTarget ),

					// Get current column size
					size = this.model.get( 'layout' ),

					// New column size
					newSize = $thisEl.attr( 'data-column-size' ),

					// Fraction size
					fractionSize = '';

				if ( event ) {
					event.preventDefault();
				}

				if ( 'undefined' !== typeof newSize ) {

					// Set new size
					this.model.set( 'layout', newSize );
					this.$el.attr( 'data-column-size', newSize );

					// Change css size class
					this.$el.removeClass( 'fusion-builder-column-' + size );
					this.$el.addClass( 'fusion-builder-column-' + newSize );

					fractionSize = newSize.replace( '_', '/' );

					this.$el.find( '.fusion-builder-resize-inner-column' ).text( fractionSize );
					this.$el.find( '.column-sizes' ).hide();
					this.$el.find( '.column-sizes .column-size' ).removeClass( 'active-size' );
					this.$el.find( '.column-size-' + newSize ).addClass( 'active-size' );

					// Save history state
					fusionHistoryManager.turnOnTracking();
					window.fusionHistoryState = fusionBuilderText.resized_column + ' ' + fractionSize;

					FusionPageBuilderEvents.trigger( 'fusion-element-edited' );

				}
			},

			getColumnContent: function( $thisColumn ) {
				var shortcode        = '',
					$thisColumnInner = 'undefined' !== typeof $thisColumn ? $thisColumn : this.$el,
					columnInnerCID   = $thisColumnInner.data( 'cid' ),
					module           = FusionPageBuilderElements.findWhere( { cid: columnInnerCID } ),
					columnParams     = {},
					columnAttributesCheck;

				_.each( module.get( 'params' ), function( value, name ) {

					if ( 'undefined' === value ) {
						columnParams[ name ] = '';
					} else {
						columnParams[ name ] = value;
					}

				} );

				// Legacy support for new column options
				columnAttributesCheck = {
					min_height: '',
					last: 'no',
					hover_type: 'none',
					link: '',
					border_position: 'all'
				};

				_.each( columnAttributesCheck, function( value, name ) {

					if ( 'undefined' === typeof columnParams[ name ] ) {
						columnParams[ name ] = value;
					}

				} );

				// Build column shortcode.
				shortcode += '[fusion_builder_column_inner type="' + module.get( 'layout' ) + '" background_position="' + columnParams.background_position + '" background_color="' + columnParams.background_color + '" border_size="' + columnParams.border_size + '" border_color="' + columnParams.border_color + '" border_style="' + columnParams.border_style + '" spacing="' + columnParams.spacing + '" background_image="' + columnParams.background_image + '" background_repeat="' + columnParams.background_repeat + '" padding_top="' + columnParams.padding_top + '" padding_bottom="' + columnParams.padding_bottom + '" padding_left="' + columnParams.padding_left + '" padding_right="' + columnParams.padding_right + '" margin_top="' + columnParams.margin_top + '" margin_bottom="' + columnParams.margin_bottom + '" class="' + columnParams[ 'class' ] + '" id="' + columnParams.id + '" animation_type="' + columnParams.animation_type + '" animation_speed="' + columnParams.animation_speed + '" animation_direction="' + columnParams.animation_direction + '" hide_on_mobile="' + columnParams.hide_on_mobile + '" center_content="' + columnParams.center_content + '" last="' + columnParams.last + '" min_height="' + columnParams.min_height + '" hover_type="' + columnParams.hover_type + '" link="' + columnParams.link + '"]';

				// Find elements in this column
				$thisColumnInner.find( '.fusion_module_block' ).each( function() {
					shortcode += FusionPageBuilderApp.generateElementShortcode( $( this ), false );
				} );

				shortcode += '[/fusion_builder_column_inner]';

				return shortcode;
			},

			getElementContent: function() {
				var $thisColumnInner = this.$el,
					module           = this.model,
					columnParams     = {},
					shortcode        = '',
					columnAttributesCheck;

				_.each( module.get( 'params' ), function( value, name ) {

					if ( 'undefined' === value ) {
						columnParams[ name ] = '';
					} else {
						columnParams[ name ] = value;
					}

				} );

				// Legacy support for new column options
				columnAttributesCheck = {
					min_height: '',
					last: 'no',
					hover_type: 'none',
					link: '',
					border_position: 'all'
				};

				_.each( columnAttributesCheck, function( value, name ) {

					if ( 'undefined' === typeof columnParams[ name ] ) {
						columnParams[ name ] = value;
					}

				} );

				// Build column shortcdoe
				shortcode += '[fusion_builder_column_inner type="' + module.get( 'layout' ) + '" background_position="' + columnParams.background_position + '" background_color="' + columnParams.background_color + '" border_size="' + columnParams.border_size + '" border_color="' + columnParams.border_color + '" border_style="' + columnParams.border_style + '" spacing="' + columnParams.spacing + '" background_image="' + columnParams.background_image + '" background_repeat="' + columnParams.background_repeat + '" padding="' + columnParams.padding + '" margin_top="' + columnParams.margin_top + '" margin_bottom="' + columnParams.margin_bottom + '" class="' + columnParams[ 'class' ] + '" id="' + columnParams.id + '" animation_type="' + columnParams.animation_type + '" animation_speed="' + columnParams.animation_speed + '" animation_direction="' + columnParams.animation_direction + '" hide_on_mobile="' + columnParams.hide_on_mobile + '" center_content="' + columnParams.center_content + '" last="' + columnParams.last + '" min_height="' + columnParams.min_height + '" hover_type="' + columnParams.hover_type + '" link="' + columnParams.link + '"]';

				// Find elements in this column
				$thisColumnInner.find( '.fusion_module_block' ).each( function() {
					shortcode += FusionPageBuilderApp.generateElementShortcode( $( this ), false );
				} );

				shortcode += '[/fusion_builder_column_inner]';

				return shortcode;
			}
		} );
	} );
}( jQuery ) );
