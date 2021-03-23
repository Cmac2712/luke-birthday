/* global FusionPageBuilderViewManager, FusionPageBuilderApp */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Builder Row View
		FusionPageBuilder.BaseRowView = window.wp.Backbone.View.extend( {

			/**
			 * Calculate virtual rows.
			 *
			 * @since 2.0.0
			 * @return {null}
			 */
			createVirtualRows: function() {
				this.updateVirtualRows();
				this.assignColumn();
			},

			/**
			 * Set the initial column data to the model.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			updateVirtualRows: function() {
				var rows        = {},
					column      = {},
					columns     = [],
					count       = 0,
					index       = 0,
					oldRows     = this.model.get( 'rows' ),
					columnWidth;

				this.model.children.each( function( child ) {
					column      = {};
					columnWidth = child.attributes.params.type;

					if ( ! columnWidth ) {
						columnWidth = '1_1';
					}
					columnWidth = columnWidth.split( '_' );
					columnWidth = columnWidth[ 0 ] / columnWidth[ 1 ];
					count += columnWidth;

					if ( 1 < count ) {
						index += 1;
						count = columnWidth;
					}

					column = {
						cid: child.attributes.cid
					};

					if ( 'undefined' === typeof rows[ index ] ) {
						rows[ index ] = [ column ];
					} else {
						rows[ index ].push( column );
					}

					columns[ child.attributes.cid ] = index;
				} );

				this.model.set( 'columns', columns );
				this.model.set( 'rows', rows );

				if ( 'object' === typeof oldRows ) {
					this.model.set( 'oldRows', oldRows );
				}
			},

			/**
			 * Change the column in the model.
			 *
			 * @since 2.0.0
			 * @param {Object} column - The column view.
			 * @return {void}
			 */
			assignColumn: function() {
				var that = this,
					columnParams,
					oldRows = this.model.get( 'oldRows' ),
					updatedCols = false;

				// Reset first, last positions
				this.model.children.each( function( column ) {
					columnParams = jQuery.extend( true, {}, column.get( 'params' ) );
					columnParams.first = false;
					columnParams.last  = false;
					column.set( 'params', columnParams );
				} );

				// Loop over virtual rows
				_.each( this.model.get( 'rows' ), function( row, rowIndex ) {
					var total     = row.length,
						lastIndex = total - 1,
						rowSame   = true;

					// Loop over columns inside virtual row
					_.each( row, function( col, colIndex ) {
						var columnFirst = false,
							columnLast = false,
							model = that.model.children.find( function( model ) {
								return model.get( 'cid' ) == col.cid; // jshint ignore: line
							} ),
							params = jQuery.extend( true, {}, model.get( 'params' ) );

						// First index
						if ( 0 === colIndex ) {
							columnFirst = true;
						}

						if ( lastIndex === colIndex ) {
							columnLast = true;
						}

						params.first = columnFirst;
						params.last  = columnLast;

						model.set( 'params', params );

						// Check if col is same as before.
						if ( rowSame ) {
							if ( 'object' !== typeof oldRows || 'undefined' === typeof oldRows[ rowIndex ] || 'undefined' === typeof oldRows[ rowIndex ][ colIndex ] || oldRows[ rowIndex ][ colIndex ].cid !== col.cid ) {
								rowSame = false;
							}
						}
					} );

					if ( ! rowSame && FusionPageBuilderApp.loaded ) {
						if ( false === updatedCols ) {
							updatedCols = [];
						}
						_.each( row, function( col ) {
							updatedCols.push( col.cid );
						} );
					}
				} );

				this.model.set( 'updatedCols', updatedCols );
			},

			getVirtualRowByCID: function( cid ) {
				var rows    = this.model.get( 'rows' ),
					columns = this.model.get( 'columns' ),
					index   = columns[ cid ],
					row     = rows[ index ];

				return row;
			},

			updateColumnsPreview: function() {
				var updatedCols = this.model.get( 'updatedCols' ),
					self        = this;

				if ( true === FusionPageBuilderApp.loaded ) {
					this.model.children.each( function( child ) {
						var view,
							singleRow,
							columnRow;

						if ( false === updatedCols || _.contains( updatedCols, child.attributes.cid ) ) {
							view      = FusionPageBuilderViewManager.getView( child.attributes.cid );
							singleRow = self.getVirtualRowByCID( view.model.get( 'cid' ) );
							columnRow = [];

							// Update first/last classes
							view.$el.removeClass( 'fusion-column-last' );
							view.$el.removeClass( 'fusion-column-first' );

							if ( true === view.model.attributes.params.last ) {
								view.$el.addClass( 'fusion-column-last' );
							}

							if ( true === view.model.attributes.params.first ) {
								view.$el.addClass( 'fusion-column-first' );
							}

							// Update column spacing.
							_.each( singleRow, function( cid ) {
								var model,
									value;

								cid   = cid.cid;
								model = self.collection.find( function( model ) {
									return model.get( 'cid' ) == cid; // jshint ignore: line
								} );
								value = model.attributes.params.spacing;

								columnRow.push( value );
							} );

							view.columnSpacingPreview( columnRow );
						}
					} );
				}
			},

			/**
			 * Sets the row data.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			setRowData: function() {
				this.createVirtualRows();
				this.updateColumnsPreview();
			},

			setSingleRowData: function( cid ) {
				var row = this.getVirtualRowByCID( cid ),
					view;

				_.each( row, function( column ) {
					view = FusionPageBuilderViewManager.getView( column.cid );
					view.reRender();
				} );
			}

		} );
	} );
}( jQuery ) );
