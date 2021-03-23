/* global FusionPageBuilderApp, fusionBuilderText, FusionPageBuilderEvents, FusionPageBuilderViewManager, fusionAllElements, FusionPageBuilderElements, fusionHistoryManager */
/* eslint no-unused-vars: 0 */
/* eslint no-alert: 0 */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		// Builder Container View
		FusionPageBuilder.ContainerView = window.wp.Backbone.View.extend( {

			className: 'fusion_builder_container',
			template: FusionPageBuilder.template( $( '#fusion-builder-container-template' ).html() ),
			events: {
				'click .fusion-builder-clone-container': 'cloneContainer',
				'click .fusion-builder-remove': 'removeContainer',
				'click .fusion-builder-section-add': 'addContainer',
				'click .fusion-builder-toggle': 'toggleContainer',
				'click .fusion-builder-settings-container': 'showSettings',
				'paste .fusion-builder-section-name': 'renameContainer',
				'keydown .fusion-builder-section-name': 'renameContainer',
				'click .fusion-builder-save-element': 'saveElementDialog'
			},

			initialize: function() {
				this.typingTimer; // jshint ignore:line
				this.doneTypingInterval = 800;
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );

				if ( 'undefined' !== typeof this.model.attributes.params.admin_toggled && 'yes' === this.model.attributes.params.admin_toggled ) {
					this.$el.addClass( 'fusion-builder-section-folded' );
					this.$el.find( 'span' ).toggleClass( 'dashicons-arrow-up' ).toggleClass( 'dashicons-arrow-down' );
				}

				// If global, make it.
				if ( 'undefined' !== typeof this.model.attributes.params.fusion_global ) {
					FusionPageBuilderApp.addClassToElement( this.$el, 'fusion-global-container', this.model.attributes.params.fusion_global, this.model.get( 'cid' ) );
				}

				// Show status icons UI
				this.updateStatusIcons();

				return this;
			},

			updateStatusIcons: function() {
				var toolbar = this.$el.find( '.fusion-builder-container-utility-toolbar' ),
					statusIcon = '';

				toolbar.find( '.fusion-builder-publish-tooltip' ).remove();

				// Append status icons
				if ( 'draft' === this.model.attributes.params.status ) {
					statusIcon = '<div class="fusion-builder-publish-tooltip fusion-container-draft" data-cid="' + this.model.get( 'cid' ) + '"><span>' + fusionBuilderText.container_draft + '<br>' + fusionBuilderText.container_publish + '</span></div>';

					toolbar.prepend( statusIcon );

				} else if ( 'published_until' === this.model.attributes.params.status || 'publish_after' === this.model.attributes.params.status ) {
					statusIcon = '<div class="fusion-builder-publish-tooltip fusion-container-scheduled" data-cid="' + this.model.get( 'cid' ) + '"><span>' + fusionBuilderText.container_scheduled + '<br>' + fusionBuilderText.container_publish + '</span></div>';

					toolbar.prepend( statusIcon );
				}
			},

			saveElement: function( event ) {
				var elementContent   = this.getContainerContent(),
					$mainContainer   = $( '#fusion_builder_main_container' ),
					elementName      = $( '#fusion-builder-save-element-input' ).val(),
					saveGlobal       = $( '#fusion_save_global' ).is( ':checked' ),
					layoutsContainer = $( '#fusion-builder-layouts-sections .fusion-page-layouts' ),
					emptyMessage     = $( '#fusion-builder-layouts-sections .fusion-empty-library-message' ),
					thisModel        = this.model,
					isDuplicate      = false,
					oldGLobalID      = null,
					params           = {};

				if ( event ) {
					event.preventDefault();
				}

				if ( 'undefined' !== typeof this.model.attributes.params && 'undefined' !== typeof this.model.attributes.params.fusion_global && 0 < $mainContainer.find( '[fusion-global-layout="' + this.model.attributes.params.fusion_global + '"]' ).length ) {

					// Make a copy.
					oldGLobalID = this.model.attributes.params.fusion_global;
					params      = this.model.get( 'params' );

					// Remove temporarily and update model
					delete params.fusion_global;
					this.model.set( 'params', params );

					// Get content.
					elementContent = this.getContainerContent();

					// Add it back.
					params.fusion_global = oldGLobalID;
					this.model.set( 'params', params );
				}

				$.each( jQuery( 'ul.fusion-page-layouts.fusion-layout-sections li' ), function( index, value ) { // jshint ignore:line
					var $title = jQuery( this ).find( 'h4.fusion-page-layout-title' ),
						templateName;

					if ( $title.length ) {
						templateName = $title.html().split( '<div ' )[ 0 ];
						templateName = templateName.replace( /\u2013|\u2014/g, '-' );
						if ( elementName.toLowerCase().trim() === templateName.toLowerCase().trim() ) {
							alert( fusionBuilderText.duplicate_element_name_error );
							isDuplicate = true;
							return false;
						}
					}
				} );

				if ( true === FusionPageBuilderApp.layoutIsSaving || true === isDuplicate ) {
					return;
				}
				FusionPageBuilderApp.layoutIsSaving = true;

				if ( '' !== elementName ) {
					$.ajax( {
						type: 'POST',
						url: FusionPageBuilderApp.ajaxurl,
						dataType: 'json',
						data: {
							action: 'fusion_builder_save_layout',
							fusion_load_nonce: FusionPageBuilderApp.fusion_load_nonce,
							fusion_layout_name: elementName,
							fusion_save_global: saveGlobal,
							fusion_layout_content: elementContent,
							fusion_layout_post_type: 'fusion_element',
							fusion_layout_new_cat: 'sections'
						},
						complete: function( data ) {
							FusionPageBuilderApp.layoutIsSaving = false;
							layoutsContainer.prepend( data.responseText );
							$( '.fusion-save-element-fields' ).remove();
							emptyMessage.hide();

							// If global, make it.
							if ( saveGlobal ) {
								thisModel.attributes.params.fusion_global = $( data.responseText ).attr( 'data-layout_id' );
								$( 'div[data-cid="' + thisModel.get( 'cid' ) + '"]' ).closest( '.fusion_builder_container' ).addClass( 'fusion-global-container' );
								$( 'div[data-cid="' + thisModel.get( 'cid' ) + '"]' ).attr( 'fusion-global-layout', $( data.responseText ).attr( 'data-layout_id' ) );

								if ( $( 'div[data-cid="' + thisModel.get( 'cid' ) + '"]' ).closest( '.fusion_builder_container' ).find( '.fusion-builder-container-utility-toolbar' ).length ) {
									$( 'div[data-cid="' + thisModel.get( 'cid' ) + '"]' ).closest( '.fusion_builder_container' ).find( '.fusion-builder-container-utility-toolbar' ).append( '<div class="fusion-builder-global-tooltip" data-cid="' + thisModel.get( 'cid' ) + '"><span>' + fusionBuilderText.global_container + '</span></div>' );
								} else {
									$( 'div[data-cid="' + thisModel.get( 'cid' ) + '"]' ).append( '<div class="fusion-builder-global-tooltip" data-cid="' + thisModel.get( 'cid' ) + '"><span>' + fusionBuilderText.global_container + '</span></div>' );
								}
								FusionPageBuilderEvents.trigger( 'fusion-element-added' );
								FusionPageBuilderApp.saveGlobal = true;
							}
						}
					} );

				} else {
					FusionPageBuilderApp.layoutIsSaving = false;
					alert( fusionBuilderText.please_enter_element_name );
				}
			},

			getContainerContent: function( model, collection, options ) { // jshint ignore:line
				var shortcode      = '',
					$thisContainer = this.$el.find( '.fusion-builder-section-content' );

				shortcode += FusionPageBuilderApp.generateElementShortcode( this.$el, true );

				$thisContainer.find( '.fusion_builder_row' ).each( function() {
					var $thisRow = $( this );

					shortcode += '[fusion_builder_row]';

					$thisRow.find( '.fusion-builder-column-outer' ).each( function() {
						var $thisColumn = $( this ),
							$columnCID  = $thisColumn.data( 'cid' ),
							$columnView = FusionPageBuilderViewManager.getView( $columnCID );

						// Get column contents shortcode
						shortcode += $columnView.getColumnContent( $thisColumn );

					} );

					shortcode += '[/fusion_builder_row]';

				} );

				shortcode += '[/fusion_builder_container]';

				return shortcode;
			},

			saveElementDialog: function( event ) {
				var containerName;

				containerName = 'undefined' !== typeof this.model.get( 'admin_label' ) && '' !== this.model.get( 'admin_label' ) ? this.model.get( 'admin_label' ) : '';

				if ( event ) {
					event.preventDefault();
				}
				FusionPageBuilderApp.showLibrary();

				$( '#fusion-builder-layouts-sections-trigger' ).click();

				$( '#fusion-builder-layouts-sections .fusion-builder-layouts-header-element-fields' ).append( '<div class="fusion-save-element-fields"><input type="text" value="' + containerName + '" id="fusion-builder-save-element-input" class="fusion-builder-save-element-input" placeholder="' + fusionBuilderText.enter_name + '" /><div class="save-as-global"><label><input type="checkbox" id="fusion_save_global" name="fusion_save_global">' + fusionBuilderText.save_global + '</label><a href="#" class="fusion-builder-save-column fusion-builder-element-button-save" data-element-cid="' + this.model.get( 'cid' ) + '">' + fusionBuilderText.save_section + '</a></div></div>' );
			},

			showSettings: function( event ) {

				var $modalView,
					$viewSettings = {
						model: this.model,
						collection: this.collection,
						attributes: {
							'data-modal_view': 'element_settings'
						}
					};

				if ( event ) {
					event.preventDefault();
				}

				// Get settings view
				$modalView = new FusionPageBuilder.ModalView( $viewSettings );

				// Render settings view
				$( 'body' ).append( $modalView.render().el );

				this.hideHundredPercentOption();
			},

			hideHundredPercentOption: function() {
				var currentTemplate       = jQuery( '#page_template' ),
					currentPostWidth      = jQuery( '#pyre_blog_width_100' ).val(),
					currentPortfolioWidth = jQuery( '#pyre_portfolio_width_100' ).val(),
					option                = jQuery( '.fusion_builder_container li[data-option-id="hundred_percent"]' );

				// Normal post.
				if ( ! jQuery( 'body' ).hasClass( 'fusion-tb-section-edit' ) ) {

					// Check the post type.
					if ( 'undefined' !== typeof currentPostWidth ) {

						// Blog post.
						if ( 'no' === currentPostWidth || ( 'default' === currentPostWidth && '' === FusionPageBuilderApp.fullWidth ) ) {
							option.hide();
						}

					} else if ( 'undefined' !== typeof currentPortfolioWidth ) {

						// Portfolio post.
						if ( 'no' === currentPortfolioWidth || ( 'default' === currentPortfolioWidth && '' === FusionPageBuilderApp.fullWidth ) ) {
							option.hide();
						}

					} else if ( '100-width.php' !== currentTemplate.val() ) {

						// Page with default template.
						option.hide();
					}
				} else if ( 'no' === jQuery( '#pyre_fusion_tb_section_width_100' ).val() ) { // Template Builder.
					option.hide();
				}
			},

			addContainer: function( event ) {

				var elementID,
					defaultParams,
					params,
					value;

				if ( event ) {
					event.preventDefault();
					FusionPageBuilderApp.newContainerAdded = true;
				}

				FusionPageBuilderApp.activeModal = 'container';

				elementID     = FusionPageBuilderViewManager.generateCid();
				defaultParams = fusionAllElements.fusion_builder_container.params;
				params        = {};

				// Process default options for shortcode.
				_.each( defaultParams, function( param ) {
					if ( _.isObject( param.value ) ) {
						value = param[ 'default' ];
					} else {
						value = param.value;
					}
					params[ param.param_name ] = value;

					if ( 'dimension' === param.type && _.isObject( param.value ) ) {
						_.each( param.value, function( value, name ) {
							params[ name ] = value;
						} );
					}
				} );

				this.collection.add( [
					{
						type: 'fusion_builder_container',
						added: 'manually',
						element_type: 'fusion_builder_container',
						cid: elementID,
						params: params,
						view: this,
						created: 'auto'
					}
				] );

				FusionPageBuilderApp.activeModal = '';
			},

			addRow: function() {
				var elementID = FusionPageBuilderViewManager.generateCid();

				this.collection.add( [
					{
						type: 'fusion_builder_row',
						element_type: 'fusion_builder_row',
						added: 'manually',
						cid: elementID,
						parent: this.model.get( 'cid' ),
						view: this
					}
				] );
			},

			cloneContainer: function( event ) {

				var containerAttributes,
					$thisContainer;

				if ( event ) {
					event.preventDefault();
				}

				containerAttributes = $.extend( true, {}, this.model.attributes );

				containerAttributes.cid = FusionPageBuilderViewManager.generateCid();
				containerAttributes.created = 'manually';
				containerAttributes.view = this;
				FusionPageBuilderApp.collection.add( containerAttributes );

				$thisContainer = this.$el;

				// Parse rows
				$thisContainer.find( '.fusion-builder-row-content:not(.fusion_builder_row_inner .fusion-builder-row-content)' ).each( function() {

					var thisRow = $( this ),
						rowCID  = thisRow.data( 'cid' ),

						// Get model from collection by cid.
						row = FusionPageBuilderElements.find( function( model ) {
							return model.get( 'cid' ) === rowCID;
						} ),

						// Clone row.
						rowAttributes = $.extend( true, {}, row.attributes );

					rowAttributes.created = 'manually';
					rowAttributes.cid = FusionPageBuilderViewManager.generateCid();
					rowAttributes.parent = containerAttributes.cid;
					FusionPageBuilderApp.collection.add( rowAttributes );

					// Parse columns
					thisRow.find( '.fusion-builder-column-outer' ).each( function() {

						// Parse column elements
						var thisColumn = $( this ),
							$columnCID = thisColumn.data( 'cid' ),

							// Get model from collection by cid
							column = FusionPageBuilderElements.find( function( model ) {
								return model.get( 'cid' ) === $columnCID;
							} ),

							// Clone column
							columnAttributes = $.extend( true, {}, column.attributes );

						columnAttributes.created = 'manually';
						columnAttributes.cid     = FusionPageBuilderViewManager.generateCid();
						columnAttributes.parent  = rowAttributes.cid;
						columnAttributes.from    = 'fusion_builder_container';
						columnAttributes.cloned  = true;

						FusionPageBuilderApp.collection.add( columnAttributes );

						// Find column elements
						thisColumn.children( '.fusion_module_block, .fusion_builder_row_inner' ).each( function() {

							var thisElement,
								elementCID,
								element,
								elementAttributes,
								thisInnerRow,
								InnerRowCID,
								innerRowView;

							// Regular element
							if ( $( this ).hasClass( 'fusion_module_block' ) ) {

								thisElement = $( this );
								elementCID = thisElement.data( 'cid' );

								// Get model from collection by cid
								element = FusionPageBuilderElements.find( function( model ) {
									return model.get( 'cid' ) === elementCID;
								} );

								// Clone model attritubes
								elementAttributes         = $.extend( true, {}, element.attributes );
								elementAttributes.created = 'manually';
								elementAttributes.cid     = FusionPageBuilderViewManager.generateCid();
								elementAttributes.parent  = columnAttributes.cid;
								elementAttributes.from    = 'fusion_builder_container';

								FusionPageBuilderApp.collection.add( elementAttributes );

							// Inner row element
							} else if ( $( this ).hasClass( 'fusion_builder_row_inner' ) ) {

								thisInnerRow = $( this );
								InnerRowCID = thisInnerRow.data( 'cid' );

								innerRowView = FusionPageBuilderViewManager.getView( InnerRowCID );

								// Clone inner row
								if ( 'undefined' !== typeof innerRowView ) {
									innerRowView.cloneNestedRow( '', columnAttributes.cid );
								}
							}

						} );

					} );

				} );

				// Save history state
				fusionHistoryManager.turnOnTracking();
				window.fusionHistoryState = fusionBuilderText.cloned_section;

				FusionPageBuilderEvents.trigger( 'fusion-element-cloned' );
			},

			removeContainer: function( event ) {

				var rows;

				if ( event ) {
					event.preventDefault();
				}

				rows = FusionPageBuilderViewManager.getChildViews( this.model.get( 'cid' ) );

				_.each( rows, function( row ) {
					if ( 'fusion_builder_row' === row.model.get( 'type' ) ) {
						row.removeRow();
					}
				} );

				if ( 1 < FusionPageBuilderViewManager.countElementsByType( 'fusion_builder_container' ) ) {

				// If the only container is deleted show blank page layout
				} else {
					FusionPageBuilderApp.blankPage = true;
				}

				FusionPageBuilderViewManager.removeView( this.model.get( 'cid' ) );

				this.model.destroy();

				this.remove();

				if ( true === FusionPageBuilderApp.blankPage ) {
					FusionPageBuilderApp.clearBuilderLayout( true );

					return;
				}

				if ( event ) {

					// Save history state
					fusionHistoryManager.turnOnTracking();
					window.fusionHistoryState = fusionBuilderText.deleted_section;

					FusionPageBuilderEvents.trigger( 'fusion-element-removed' );
				}
			},

			toggleContainer: function( event ) {

				var thisEl = $( event.currentTarget );

				if ( event ) {
					event.preventDefault();
				}

				this.$el.toggleClass( 'fusion-builder-section-folded' );
				thisEl.find( 'span' ).toggleClass( 'dashicons-arrow-up' ).toggleClass( 'dashicons-arrow-down' );

				if ( this.$el.hasClass( 'fusion-builder-section-folded' ) ) {
					this.model.attributes.params.admin_toggled = 'yes';
				} else {
					this.model.attributes.params.admin_toggled = 'no';
				}

				FusionPageBuilderEvents.trigger( 'fusion-element-edited' );
			},

			renameContainer: function( event ) {

				// Detect "enter" key
				var code,
					model,
					input;

				code = event.keyCode || event.which;

				if ( 13 == code ) { // jshint ignore:line
					event.preventDefault();
					this.$el.find( '.fusion-builder-section-name' ).blur();

					return false;
				}

				model = this.model;
				input = this.$el.find( '.fusion-builder-section-name' );
				clearTimeout( this.typingTimer );

				this.typingTimer = setTimeout( function() {

					// Strip square brackets.
					model.attributes.params.admin_label = input.val().replace( /[[\]]+/g, '' );

					FusionPageBuilderEvents.trigger( 'fusion-element-edited' );

				}, this.doneTypingInterval );
			}
		} );
	} );
}( jQuery ) );
