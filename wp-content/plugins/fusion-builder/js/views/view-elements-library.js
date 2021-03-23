/* global FusionPageBuilderEvents, FusionPageBuilderViewManager, FusionPageBuilderApp, fusionHistoryManager, fusionBuilderText, fusionAllElements */
/* eslint no-unused-vars: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		// Builder Elements View
		FusionPageBuilder.ElementLibraryView = window.wp.Backbone.View.extend( {

			className: 'fusion_builder_modal_settings',

			template: FusionPageBuilder.template( $( '#fusion-builder-modules-template' ).html() ),

			events: {
				'click .fusion-builder-all-modules .fusion-builder-element:not(.fusion-builder-element-generator,.fusion-builder-disabled-element)': 'addModule',
				'click .fusion_builder_custom_elements_load': 'addCustomModule',
				'click .fusion-builder-column-layouts li': 'addNestedColumns'
			},

			initialize: function( attributes ) {
				this.options = attributes;
				this.listenTo( FusionPageBuilderEvents, 'fusion-modal-view-removed', this.remove );
			},

			render: function() {
				var self = this;

				this.$el.html( this.template( FusionPageBuilderViewManager.toJSON() ) );

				// Load saved elements
				FusionPageBuilderApp.showSavedElements( 'elements', this.$el.find( '#custom-elements' ) );

				// If adding element to nested column
				if ( 'true' === FusionPageBuilderApp.innerColumn ) {
					this.$el.addClass( 'fusion-add-to-nested' );
				}

				setTimeout( function() {
					self.$el.find( '.fusion-elements-filter' ).focus();
				}, 50 );

				return this;
			},

			addCustomModule: function( event ) {
				var layoutID,
					title,
					isGlobal;

				if ( event ) {
					event.preventDefault();
				}

				if ( true === FusionPageBuilderApp.layoutIsLoading ) {
					return;
				}

				FusionPageBuilderApp.layoutIsLoading = true;

				layoutID = $( event.currentTarget ).closest( 'li' ).data( 'layout_id' );
				title    = $( event.currentTarget ).find( '.fusion_module_title' ).text();
				isGlobal = $( event.currentTarget ).closest( 'li' ).hasClass( 'fusion-global' );

				$( event.currentTarget ).parent( '.fusion-builder-all-modules' ).css( 'opacity', '0' );
				$( event.currentTarget ).parent( '.fusion-builder-all-modules' ).prev( '#fusion-loader' ).show();

				$.ajax( {
					type: 'POST',
					url: FusionPageBuilderApp.ajaxurl,
					data: {
						action: 'fusion_builder_load_layout',
						fusion_load_nonce: FusionPageBuilderApp.fusion_load_nonce,
						fusion_is_global: isGlobal,
						fusion_layout_id: layoutID
					},

					success: function( data ) {
						var dataObj = JSON.parse( data );

						FusionPageBuilderApp.shortcodesToBuilder( dataObj.post_content, FusionPageBuilderApp.parentColumnId );
						FusionPageBuilderApp.layoutIsLoading = false;

						$( event.currentTarget ).parent( '.fusion-builder-all-modules' ).css( 'opacity', '1' );
						$( event.currentTarget ).parent( '.fusion-builder-all-modules' ).prev( '#fusion-loader' ).hide();

						// Check for globals.
						setTimeout( FusionPageBuilderApp.checkGlobalParents, 500, FusionPageBuilderApp.parentColumnId );
					},

					complete: function() {

						// Save history state
						fusionHistoryManager.turnOnTracking();
						window.fusionHistoryState = fusionBuilderText.added_custom_element + title;

						FusionPageBuilderEvents.trigger( 'fusion-element-added' );
					}
				} );
			},

			addModule: function( event ) {
				var $thisEl,
					label,
					params,
					defaultParams,
					multi,
					type,
					name,
					allowGenerator;

				if ( event ) {
					event.preventDefault();
				}

				$thisEl = $( event.currentTarget );
				label   = $thisEl.find( '.fusion_module_label' ).text();

				if ( label in fusionAllElements ) {

					params = fusionAllElements[ label ].params;
					multi  = fusionAllElements[ label ].multi;
					type   = fusionAllElements[ label ].shortcode;
					name   = fusionAllElements[ label ].name;
					allowGenerator = fusionAllElements[ label ].allow_generator;

					// Get default options
					defaultParams = fusionAllElements[ label ].params;
					params = {};

					// Process default parameters from shortcode
					_.each( defaultParams, function( param )  {
						params[ param.param_name ] = ( _.isObject( param.value ) ) ? param[ 'default' ] : param.value;
					} );

				} else {
					params = '';
					multi  = '';
					type   = '';
					allowGenerator = '';
				}

				if ( event ) {
					window.fusionHistoryState = fusionBuilderText.added + ' ' + name + ' ' + fusionBuilderText.element;
				}

				this.collection.add( [
					{
						type: 'element',
						added: 'manually',
						cid: FusionPageBuilderViewManager.generateCid(),
						element_type: type,
						params: params,
						parent: this.attributes[ 'data-parent_cid' ],
						view: this.options.view,
						allow_generator: allowGenerator,
						multi: multi
					}
				] );

				// Add component to used array.
				if ( 'undefined' !== typeof fusionAllElements[ label ].component && true === fusionAllElements[ label ].component ) {
					FusionPageBuilderViewManager.defaults.usedComponents[ type ]++;
				}

				this.remove();

				FusionPageBuilderEvents.trigger( 'fusion-element-added' );

			},

			addNestedColumns: function( event, appendAfter ) {
				var moduleID,
					that,
					$layoutEl,
					layout,
					layoutElementsNum,
					thisView,
					defaultParams,
					params,
					value;

				if ( event ) {
					event.preventDefault();
				}

				moduleID = FusionPageBuilderViewManager.generateCid();

				this.collection.add( [
					{
						type: 'fusion_builder_row_inner',
						element_type: 'fusion_builder_row_inner',
						cid: moduleID,
						parent: this.model.get( 'cid' ),
						view: this,
						appendAfter: appendAfter
					}
				] );

				FusionPageBuilderApp.activeModal = 'column';

				that              = this;
				$layoutEl         = $( event.target ).is( 'li' ) ? $( event.target ) : $( event.target ).closest( 'li' );
				layout            = $layoutEl.data( 'layout' ).split( ',' );
				layoutElementsNum = _.size( layout );
				thisView          = this.options.view;

				// Get default options
				defaultParams = fusionAllElements.fusion_builder_column_inner.params;
				params = {};

				// Process default parameters from shortcode
				_.each( defaultParams, function( param )  {
					if ( _.isObject( param.value ) ) {
						value = param[ 'default' ];
					} else {
						value = param.value;
					}
					params[ param.param_name ] = value;
				} );

				_.each( layout, function( element, index ) { // jshint ignore:line
					var columnAttributes = {
						type: 'fusion_builder_column_inner',
						element_type: 'fusion_builder_column_inner',
						cid: FusionPageBuilderViewManager.generateCid(),
						parent: moduleID,
						layout: element,
						view: thisView,
						params: params
					};

					that.collection.add( [ columnAttributes ] );

				} );

				this.remove();

				FusionPageBuilderEvents.trigger( 'fusion-columns-added' );

				if ( event ) {

					// Save history state
					fusionHistoryManager.turnOnTracking();
					window.fusionHistoryState = fusionBuilderText.added_nested_columns;

					FusionPageBuilderEvents.trigger( 'fusion-element-cloned' );
				}
			}
		} );
	} );
}( jQuery ) );
