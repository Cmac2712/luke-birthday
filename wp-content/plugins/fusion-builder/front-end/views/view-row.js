/* global FusionApp, FusionPageBuilderApp, fusionBuilderText, FusionPageBuilderViewManager, FusionEvents, FusionPageBuilderElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Builder Row View
		FusionPageBuilder.RowView = FusionPageBuilder.BaseRowView.extend( {

			template: FusionPageBuilder.template( jQuery( '#fusion-builder-row-template' ).html() ),
			className: 'fusion_builder_row',
			events: {
				'click .fusion-builder-insert-column': 'displayColumnsOptions'
			},

			/**
			 * Init.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			initialize: function() {
				this.model.set( 'rows', {} );
				this.model.children = new FusionPageBuilder.Collection();
				this.listenTo( this.model.children, 'add', this.addChildView );
				this.listenTo( FusionEvents, 'fusion-builder-loaded', this.updateColumnsPreview );
				this.listenTo( FusionEvents, 'fusion-wireframe-toggle', this.wireFrameToggled );
			},

			/**
			 * Renders the view.
			 *
			 * @since 2.0.0
			 * @return {Object} this
			 */
			render: function() {
				var self = this;

				this.$el.html( this.template( this.model.attributes ) );

				this.appendChildren();

				setTimeout( function() {
					self.droppableColumn();
				}, 100 );

				// Show column dialog when adding a new row
				if ( 'manually' !== this.model.get( 'created' ) ) {
					this.displayContainerLibrary();
				}

				return this;
			},

			/**
			 * Display the column options.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			displayColumnsOptions: function( event ) {

				var viewSettings,
					view;

				if ( event ) {
					event.preventDefault();
				}

				FusionPageBuilderApp.parentRowId = this.model.get( 'cid' );

				viewSettings = {
					model: this.model,
					collection: this.collection
				};

				if ( jQuery( event.currentTarget ).closest( '.fusion-builder-column' ).length && ! FusionPageBuilderApp.wireframeActive ) {
					viewSettings.targetElement = jQuery( event.currentTarget ).closest( '.fusion-builder-column' );
				}

				view = new FusionPageBuilder.ColumnLibraryView( viewSettings );

				jQuery( view.render().el ).dialog( {
					title: 'Select Column',
					width: FusionApp.dialog.dialogWidth,
					height: FusionApp.dialog.dialogHeight,
					draggable: false,
					modal: true,
					resizable: false,
					dialogClass: 'fusion-builder-dialog fusion-builder-large-library-dialog fusion-builder-columns-library-dialog',

					open: function() {
						FusionApp.dialog.resizeDialog();
					},

					close: function() {
						view.remove();
					}
				} );
			},

			/**
			 * Display the container library.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			displayContainerLibrary: function( event ) {

				var viewSettings,
					view,
					parentView;

				if ( event ) {
					event.preventDefault();
				}

				FusionPageBuilderApp.parentRowId = this.model.get( 'cid' );
				parentView = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );

				viewSettings = {
					model: this.model,
					collection: this.collection
				};

				view = new FusionPageBuilder.ContainerLibraryView( viewSettings );

				jQuery( view.render().el ).dialog( {
					title: 'Select Container',
					width: FusionApp.dialog.dialogWidth,
					height: FusionApp.dialog.dialogHeight,
					draggable: false,
					modal: true,
					resizable: false,
					dialogClass: 'fusion-builder-dialog fusion-builder-large-library-dialog fusion-builder-container-library-dialog',
					open: function() {
						FusionApp.dialog.resizeDialog();
					},
					close: function() {
						parentView.removeContainer();
						view.remove();
					}
				} );
			},

			/**
			 * Removes a row.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			removeRow: function( event ) { // jshint ignore: line

				var columns;

				if ( event ) {
					event.preventDefault();
				}

				columns = FusionPageBuilderViewManager.getChildViews( this.model.get( 'cid' ) );

				// Remove all columns
				_.each( columns, function( column ) {
					column.removeColumn();
				} );

				FusionPageBuilderViewManager.removeView( this.model.get( 'cid' ) );

				this.model.destroy();

				this.remove();

				this.setRowData();

				if ( event ) {
					FusionEvents.trigger( 'fusion-content-changed' );
				}
			},

			/**
			 * Creates drop zone for empty row.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			droppableColumn: function() {
				var $el = this.$el,
					self = this;

				if ( ! $el ) {
					return;
				}
				$el.find( '.fusion-column-target' ).droppable( {
					tolerance: 'touch',
					hoverClass: 'ui-droppable-active',
					accept: '.fusion-builder-column',
					drop: function( event, ui ) {
						var columnCid      = ui.draggable.data( 'cid' ),
							columnView     = FusionPageBuilderViewManager.getView( columnCid ),
							originalCid    = columnView.model.get( 'parent' ),
							originalView,
							newIndex;

						// Move the actual html.
						$el.find( '.fusion-builder-empty-container' ).after( ui.draggable );

						newIndex = ui.draggable.parent().children( '.fusion-builder-column' ).index( ui.draggable );

						FusionPageBuilderApp.onDropCollectionUpdate( columnView.model, newIndex, self.model.get( 'cid' ) );

						// Update destination row which is this current one.
						self.setRowData();

						// If destination row and original row are different, update original as well.
						if ( self.model.get( 'cid' ) !== originalCid ) {
							originalView = FusionPageBuilderViewManager.getView( originalCid );
							originalView.setRowData();
						}

						FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.column + ' order changed' );

						setTimeout( function() {
							columnView.droppableColumn();
						}, 300 );
					}
				} );
			},

			/**
			 * Appends children. Calls the delegateEvents function in the view.
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

					self.$el.find( '.fusion-builder-row-container' ).append( view.$el );

					view.delegateEvents();
				} );
			},

			/**
			 * Adds a child view.
			 *
			 * @since 2.0.0
			 * @param {Object} element - The element model.
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
					};

				viewSettings.className = 'fusion-builder-column fusion-builder-column-outer fusion-builder-column-' + element.attributes.params.type;

				view = new FusionPageBuilder.ColumnView( viewSettings );

				// Calculate virtual rows
				this.createVirtualRows();

				// This column was cloned
				if ( ! _.isUndefined( element.get( 'cloned' ) ) && true === element.get( 'cloned' ) ) {
					element.targetElement = view.$el;
					element.unset( 'cloned' );
				}

				FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

				if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
					if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
						element.get( 'targetElement' ).after( view.render().el );
					} else {
						element.get( 'targetElement' ).before( view.render().el );
					}
				} else {
					if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
						this.$el.find( '.fusion-builder-row-container' ).append( view.render().el );
					} else {
						this.$el.find( '.fusion-builder-row-container' ).prepend( view.render().el );
					}
					element.unset( 'from' );
				}

				this.updateColumnsPreview();
			},

			/**
			 * Delegates child events.
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
					view.delegateChildEvents();
					view.droppableColumn();
				} );
			},

			/**
			 * Fired when wireframe mode is toggled.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			wireFrameToggled: function() {
				if ( FusionPageBuilderApp.wireframeActive ) {
					this.enableSortableColumns();
				} else {
					this.disableSortableColumns();
				}
			},

			/**
			 * Destroy or disable column sortable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			disableSortableColumns: function() {
				var rowContainer = this.$el.find( '.fusion-builder-row-container' );

				if ( 'undefined' !== typeof rowContainer.sortable( 'instance' ) ) {
					rowContainer.enableSelection();
					rowContainer.sortable( 'disable' );
				}
			},

			/**
			 * Initialize column sortable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			sortableColumns: function() {
				var rowContainer = this.$el.find( '.fusion-builder-row-container' ),
					items = '.fusion-builder-column-outer',
					self = this;

				rowContainer.sortable( {
					cancel: '.fusion-builder-column-settings, .fusion-builder-column-size, .fusion-builder-column-clone, .fusion-builder-column-save, .fusion-builder-column-remove, .fusion-builder-add-element, .column-sizes',
					items: items,
					connectWith: '.fusion-builder-row-container',
					tolerance: 'pointer',
					appendTo: rowContainer.parent(),
					helper: 'clone',

					update: function( event, ui ) {
						self.onSortUpdate( event, ui );
					},

					stop: function( event, ui ) {
						self.onSortStop( event, ui, items );
					}

				} ).disableSelection();
			}
		} );
	} );
}( jQuery ) );
