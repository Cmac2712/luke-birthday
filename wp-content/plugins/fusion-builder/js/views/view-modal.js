/* global FusionPageBuilderEvents, FusionPageBuilderViewManager, fusionAllElements, FusionPageBuilderApp, fusionHistoryManager, fusionBuilderGetContent, fusionBuilderInsertIntoEditor, fusionBuilderText, FusionPageBuilderElements */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		// Modal view
		FusionPageBuilder.ModalView = window.wp.Backbone.View.extend( {

			className: 'fusion-builder-modal-settings-container',

			template: FusionPageBuilder.template( $( '#fusion-builder-modal-template' ).html() ),

			events: {
				'click .fusion-builder-modal-save': 'saveSettings',
				'click .fusion-builder-modal-close': 'closeModal'
			},

			initialize: function( attributes ) {

				// New columns added. Remove modal view.
				this.listenTo( FusionPageBuilderEvents, 'fusion-columns-added', this.removeView );

				// Remove modal view
				this.listenTo( FusionPageBuilderEvents, 'fusion-remove-modal-view', this.removeView );

				// Close modal view
				this.listenTo( FusionPageBuilderEvents, 'fusion-close-modal', this.closeModal );

				this.options = attributes;

				this.elementType = '';
			},

			initDynamicParams: function() {
				var self        = this,
					params      = 'undefined' !== typeof this.model && 'object' === typeof this.model.get( 'params' ) ? this.model.get( 'params' ) : {},
					dynamicData = params.dynamic_params;

				this.dynamicParams = new FusionPageBuilder.DynamicParams( { elementView: this } );

				if ( 'string' === typeof params.dynamic_params && '' !== params.dynamic_params ) {
					try {
						if ( FusionPageBuilderApp.base64Encode( FusionPageBuilderApp.base64Decode( dynamicData ) ) === dynamicData ) {
							dynamicData = FusionPageBuilderApp.base64Decode( dynamicData );
							dynamicData = _.unescape( dynamicData );
							dynamicData = JSON.parse( dynamicData );
						}
						self.dynamicParams.setData( dynamicData );
					} catch ( error ) {
						console.log( error ); // jshint ignore:line
					}
				}
			},

			render: function() {
				var view,
					viewSettings = {
						model: this.model,
						collection: this.collection,
						view: this.options.view
					},
					customSettingsViewName,
					$container;

				this.initDynamicParams();
				viewSettings.dynamicParams = this.dynamicParams;

				// TODO: checked column
				if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get( 'view' ) && ( 'row_inner' === this.model.get( 'element_type' ) || 'fusion_builder_row' === this.model.get( 'element_type' ) ) && this.model.get( 'parent' ) !== this.model.get( 'view' ).$el.data( 'cid' ) ) {
					this.model.set( 'view', FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) ), { silent: true } );
				}

				if ( 'undefined' !== typeof this.model ) {
					this.$el.html( this.template( this.model.toJSON() ) );

				} else {
					this.$el.html( this.template() );
				}

				$container = this.$el.find( '.fusion-builder-modal-container' );

				// Show columns library view
				if ( 'column_library' === this.attributes[ 'data-modal_view' ] ) {
					view = new FusionPageBuilder.ColumnLibraryView( viewSettings );

				// Show elements library view
				} else if ( 'element_library' === this.attributes[ 'data-modal_view' ] ) {
					viewSettings.attributes = {
						'data-parent_cid': this.model.get( 'cid' )
					};
					view = new FusionPageBuilder.ElementLibraryView( viewSettings );

				// Show all shortcodes for generator
				} else if ( 'all_elements_generator' === this.attributes[ 'data-modal_view' ] ) {
					viewSettings.attributes = {};
					view = new FusionPageBuilder.GeneratorElementsView( viewSettings );

				// Show multi element element child settings
				} else if ( 'multi_element_child_settings' === this.attributes[ 'data-modal_view' ] ) {
					viewSettings.attributes = {};
					view = new FusionPageBuilder.MultiElementSettingsView( viewSettings );

				// Show element settings
				} else if ( 'element_settings' === this.attributes[ 'data-modal_view' ] ) {
					viewSettings.attributes = {
						'data-element_type': this.model.get( 'element_type' )
					};

					if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get( 'multi' ) && 'multi_element_parent' === this.model.get( 'multi' ) ) {
						this.elementType = 'multi';
					}

					viewSettings.view = this;

					customSettingsViewName = fusionAllElements[ this.model.get( 'element_type' ) ].custom_settings_view_name;

					if ( 'undefined' !== typeof customSettingsViewName && '' !== customSettingsViewName ) {
						view = new FusionPageBuilder[ customSettingsViewName ]( viewSettings );

					} else {
						view = new FusionPageBuilder.ElementSettingsView( viewSettings );
					}
				}
				this.settingsView = view;
				$container.append( view.render().el );

				if ( 1 > $( '.fusion_builder_modal_overlay' ).length && 1 > $( '.fusion_builder_modal_inner_row_overlay' ).length ) {
					$( 'body' ).addClass( 'fusion_builder_no_scroll' ).append( '<div class="fusion_builder_modal_overlay"></div>' );
				}

				// Element search field
				if ( 'column_library' === this.attributes[ 'data-modal_view' ] || 'element_library' === this.attributes[ 'data-modal_view' ] || 'all_elements_generator' === this.attributes[ 'data-modal_view' ] ) {
					this.elementSearchFilter();
				}

				// Add additional container class for multi elements
				if ( 'multi' === this.elementType ) {
					this.$el.addClass( 'fusion_builder_modal_multi_element_settings_container' );
				}

				return this;
			},

			closeModal: function( event ) {

				var parentID,
					parentView,
					params,
					defaultParams,
					value,
					attributes,
					editorID,
					sortableCID,
					sortableUIView,
					elementView;

				if ( event ) {
					event.preventDefault();
				}

				FusionPageBuilderApp.activeModal = '';

				// Close colorpickers before saving
				this.$el.find( '.wp-color-picker' ).each( function() {
					$( this ).wpColorPicker( 'close' );
				} );

				// Destroy CodeMirror editor instance
				if ( FusionPageBuilderApp.codeEditor ) {
					FusionPageBuilderApp.codeEditor.toTextArea();
				}

				// If new section creation was cancelled
				if ( true === FusionPageBuilderApp.newContainerAdded ) {
					FusionPageBuilderApp.newContainerAdded = false;
				}

				// Restore updated dynamic params.
				if ( elementView && 'object' === typeof this.dynamicParams ) {
					this.dynamicParams.restoreBackup();
				}

				// Remove each instance of tinyMCE editor from this view
				this.$el.find( '.tinymce' ).each( function() {
					editorID = $( this ).find( 'textarea.fusion-editor-field' ).attr( 'id' );
					FusionPageBuilderApp.fusionBuilderMCEremoveEditor( editorID );
				} );

				// Save history state
				if ( 'undefined' !== typeof this.model && true !== FusionPageBuilderApp.MultiElementChildSettings && 'undefined' !== this.model.get( 'added' ) && 'manually' === this.model.get( 'added' ) ) {
					fusionHistoryManager.turnOnTracking();
				} else {
					FusionPageBuilderApp.MultiElementChildSettings = false;
				}

				// Generator active
				if ( true === FusionPageBuilderApp.shortcodeGenerator ) {

					// Multi element parent
					if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get( 'multi' ) && 'multi_element_parent' === this.model.get( 'multi' ) ) {

						FusionPageBuilderApp.shortcodeGeneratorMultiElement      = '';
						FusionPageBuilderApp.shortcodeGeneratorMultiElementChild = '';
						FusionPageBuilderApp.shortcodeGenerator                  = '';

						// Remove sortable UI view
						sortableCID    = this.$el.find( '.fusion-builder-option-advanced-module-settings' ).data( 'cid' );
						sortableUIView = FusionPageBuilderViewManager.getView( sortableCID );
						sortableUIView.removeView();

						sortableCID    = '';
						sortableUIView = '';

					// Multi element child
					} else if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get( 'multi' ) && 'multi_element_child' === this.model.get( 'multi' ) ) {

						FusionPageBuilderApp.shortcodeGeneratorMultiElementChild = '';

					// Regular element
					} else {

						FusionPageBuilderApp.shortcodeGenerator         = '';
						FusionPageBuilderApp.shortcodeGeneratorEditorID = '';
					}

				} else if ( 'undefined' !== this.model.get( 'added' ) && 'manually' === this.model.get( 'added' ) ) { // If element was added manually ( by clicking + add element )

					if ( 'fusion_builder_row' === this.model.get( 'element_type' ) ) {
						parentID   = this.model.get( 'parent' );
						parentView = FusionPageBuilderViewManager.getView( parentID );

						if ( 'undefined' !== typeof parentView ) {
							parentView.removeContainer();
						}

					} else {

						// On Element creation set default options if Cancel button is clicked
						defaultParams = fusionAllElements[ this.model.get( 'element_type' ) ].params;
						params        = {};

						// Process default parameters from shortcode
						_.each( defaultParams, function( param ) {
							value = ( _.isObject( param.value ) ) ? param[ 'default' ] : param.value;
							params[ param.param_name ] = value;
						} );

						attributes = {
							params: params
						};

						this.model.set( attributes );

						if ( event ) {
							FusionPageBuilderEvents.trigger( 'fusion-element-added' );
						}
					}

					if ( 'element' === this.model.get( 'type' ) ) {
						this.deleteModel();
					}

					if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get( 'multi' ) && 'multi_element_parent' === this.model.get( 'multi' ) ) {

						// Remove sortable UI view
						FusionPageBuilderEvents.trigger( 'fusion-multi-remove-sortables-view' );
					}
				}

				this.removeOverlay();

				FusionPageBuilderEvents.trigger( 'fusion-settings-modal-cancel' );

				this.remove();
			},

			removeView: function() {

				this.removeOverlay();

				if ( 'undefined' === typeof this.model || ( 'fusion_builder_row' === this.model.get( 'type' ) || 'fusion_builder_column' === this.model.get( 'type' ) || 'fusion_builder_row_inner' === this.model.get( 'type' ) || 'fusion_builder_column_inner' === this.model.get( 'type' ) ) ) {
					this.remove();
				}
			},

			saveSettings: function( event ) {

				var attributes,
					generatedShortcode,
					editorID,
					functionName,
					sortableUIView,
					sortableCID,
					self = this,
					dynamicParams;

				if ( event ) {
					event.preventDefault();
				}

				// Close colorpickers before saving
				this.$el.find( '.wp-color-picker' ).each( function() {
					$( this ).wpColorPicker( 'close' );
				} );

				// Destroy CodeMirror editor instance
				if ( FusionPageBuilderApp.codeEditor ) {
					FusionPageBuilderApp.codeEditor.toTextArea();
				}

				// Save history state
				if ( true !== FusionPageBuilderApp.MultiElementChildSettings ) {
					fusionHistoryManager.turnOnTracking();
				} else {
					FusionPageBuilderApp.MultiElementChildSettings = false;
				}

				attributes = { params: ( {} ) };

				// Preserve container admin label
				if ( 'fusion_builder_container' === this.model.get( 'element_type' ) ) {
					if ( 'undefined' !== typeof this.model.attributes.params.admin_label ) {
						attributes.params.admin_label = this.model.attributes.params.admin_label;
					}
				}

				// Preserve global elements.
				if ( 'undefined' !== typeof this.model.attributes.params.fusion_global ) {
					attributes.params.fusion_global = this.model.attributes.params.fusion_global;
				}

				this.$el.find( 'input, select, textarea, #fusion_builder_content_main, #fusion_builder_content_main_child, #generator_element_content, #generator_multi_child_content, #element_content' ).not( ':input[type=button], .fusion-icon-search, .category-search-field, .fusion-builder-table input, .fusion-builder-table textarea, .single-builder-dimension .fusion-builder-dimension input, .fusion-hide-from-atts' ).each( function() {
					var $thisEl = $( this ),
						settingValue,
						name;

					name = self.getSettingName( $thisEl, false );

					if ( 'undefined' !== typeof name ) {
						settingValue              = self.getSettingValue( $thisEl, false );
						attributes.params[ name ] = settingValue;
					}

				} );

				// Get dynamic values and store.
				dynamicParams = this.getDynamicValues();
				if ( dynamicParams ) {
					attributes.params.dynamic_params = dynamicParams.string;
					attributes.dynamic_params        = dynamicParams.object;
				} else {
					delete attributes.params.dynamic_params;
					attributes.dynamic_params = {};
				}

				// Escapes &, <, >, ", `, and ' characters
				if ( 'undefined' !== typeof fusionAllElements[ this.model.get( 'element_type' ) ].escape_html && true === fusionAllElements[ this.model.get( 'element_type' ) ].escape_html ) {
					attributes.params.element_content = _.escape( attributes.params.element_content );
				}

				// Manipulate model attributes via custom function if provided by element.
				if ( 'undefined' !== typeof fusionAllElements[ this.model.get( 'element_type' ) ].on_save ) {
					functionName = fusionAllElements[ this.model.get( 'element_type' ) ].on_save;

					if ( 'function' === typeof FusionPageBuilderApp[ functionName ] ) {
						attributes = FusionPageBuilderApp[ functionName ]( attributes, this );
					}
				}

				// Generator active
				if ( true === FusionPageBuilderApp.shortcodeGenerator ) {

					// Multi element parent
					if ( 'multi_element_parent' === this.model.get( 'multi' ) ) {

						this.model.set( attributes, { silent: true } );

						generatedShortcode = FusionPageBuilderApp.generateElementShortcode( this.model, false, true );
						fusionBuilderInsertIntoEditor( generatedShortcode );

						FusionPageBuilderApp.shortcodeGeneratorMultiElement = '';
						FusionPageBuilderApp.shortcodeGeneratorMultiElementChild = '';
						FusionPageBuilderApp.shortcodeGenerator = '';

						// Remove sortable UI view
						sortableCID = this.$el.find( '.fusion-builder-option-advanced-module-settings' ).data( 'cid' );
						if ( 'undefined' !== typeof sortableCID ) {
							sortableUIView = FusionPageBuilderViewManager.getView( sortableCID );
							sortableUIView.removeView();
						}

						sortableCID = '';
						sortableUIView = '';

						this.remove();
						this.removeOverlay();

					// Multi element child
					} else if ( 'multi_element_child' === this.model.get( 'multi' ) ) {

						this.model.set( attributes );

						FusionPageBuilderEvents.trigger( 'fusion-multi-element-edited' );
						FusionPageBuilderEvents.trigger( 'fusion-multi-child-update-preview' );

						FusionPageBuilderApp.shortcodeGeneratorMultiElementChild = '';

						this.remove();

					// Regular element
					} else {

						if ( 'fusion_builder_column' === this.model.get( 'element_type' ) ) {
							attributes.params.type = this.model.get( 'layout' );
						}

						if ( 'fusion_builder_container' === this.model.get( 'element_type' ) ) {
							attributes.params.element_content = '[fusion_builder_row][/fusion_builder_row]';
						}

						this.model.set( attributes, { silent: true } );

						generatedShortcode = FusionPageBuilderApp.generateElementShortcode( this.model, false, true );

						fusionBuilderInsertIntoEditor( generatedShortcode, FusionPageBuilderApp.shortcodeGeneratorEditorID );

						// Slide element "add video" button check
						if ( 'video' !== FusionPageBuilderApp.shortcodeGeneratorEditorID ) {
							FusionPageBuilderApp.shortcodeGenerator = '';
							FusionPageBuilderApp.shortcodeGeneratorEditorID = '';
						}

						this.remove();

						// Remove overlay if generator was triggered outside of builder
						if ( false === FusionPageBuilderApp.builderActive || true === FusionPageBuilderApp.fromExcerpt ) {
							this.removeOverlay();
							FusionPageBuilderApp.fromExcerpt = false;
						}
					}

				// Not from Shortcode Generator
				} else {

					if ( 'multi_element_child' === this.model.get( 'multi' ) ) {

						// Set element/model attributes
						this.model.set( attributes, { silent: true } );

						FusionPageBuilderEvents.trigger( 'fusion-multi-element-edited' );
						FusionPageBuilderEvents.trigger( 'fusion-multi-child-update-preview' );

						this.remove();

					} else if ( 'multi_element_parent' === this.model.get( 'multi' ) ) {

						// Save history state
						if ( 'undefined' === typeof this.model.get( 'added' ) ) {
							window.fusionHistoryState = fusionBuilderText.edited + ' ' + fusionAllElements[ this.model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element;
						}

						// Remove 'added' attribute from newly created elements
						this.model.unset( 'added' );

						this.model.set( attributes );

						// Remove each instance of tinyMCE editor from this view
						this.$el.find( '.tinymce' ).each( function() {
							editorID = $( this ).find( 'textarea.fusion-editor-field' ).attr( 'id' );
							FusionPageBuilderApp.fusionBuilderMCEremoveEditor( editorID );
						} );

						// Remove sortable UI view
						FusionPageBuilderEvents.trigger( 'fusion-multi-remove-sortables-view' );

						this.remove();

						FusionPageBuilderEvents.trigger( 'fusion-modal-view-removed' );

						this.generatePreview();

						this.removeOverlay();

					} else {

						// Save history state
						if ( 'undefined' === typeof this.model.get( 'added' ) ) {
							window.fusionHistoryState = fusionBuilderText.edited + ' ' + fusionAllElements[ this.model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element;
						}

						// Remove 'added' attribute from newly created elements
						this.model.unset( 'added' );

						this.model.set( attributes );

						// Remove each instance of tinyMCE editor from this view
						this.$el.find( '.tinymce' ).each( function() {
							editorID = $( this ).find( 'textarea.fusion-editor-field' ).attr( 'id' );
							FusionPageBuilderApp.fusionBuilderMCEremoveEditor( editorID );
						} );

						this.remove();

						FusionPageBuilderEvents.trigger( 'fusion-modal-view-removed' );

						if ( true === FusionPageBuilderApp.builderActive ) {
							this.generatePreview();
						}

						this.removeOverlay();

					}

					if ( event ) {
						FusionPageBuilderEvents.trigger( 'fusion-element-added' );
					}

				}

				if ( FusionPageBuilderApp.manuallyAdded ) {
					FusionPageBuilderApp.shortcodeGenerator         = FusionPageBuilderApp.manualGenerator;
					FusionPageBuilderApp.shortcodeGeneratorEditorID = FusionPageBuilderApp.manualEditor;
					FusionPageBuilderApp.manuallyAdded              = false;
				}

				// Remove each instance of tinyMCE editor from this view
				this.$el.find( '.tinymce' ).each( function() {
					editorID = $( this ).find( 'textarea.fusion-editor-field' ).attr( 'id' );
					FusionPageBuilderApp.fusionBuilderMCEremoveEditor( editorID );
				} );

				FusionPageBuilderApp.activeModal = '';

				FusionPageBuilderEvents.trigger( 'fusion-settings-modal-save' );

			},

			getSettingName: function( $thisEl, fromRepeater ) {
				var name;

				// Skip options within repeater.
				if ( $thisEl.parents( '.repeater-fields, .dynamic-param-fields' ).length && ! fromRepeater ) {
					return;
				}

				// Multi element
				if ( $thisEl.is( '#generator_element_content' ) ||
					$thisEl.is( '#fusion_builder_content_main' ) ||
					$thisEl.is( '#element_content' ) ||
					$thisEl.is( '#generator_multi_child_content' ) ) {
					name = 'element_content';
				} else {
					name = $thisEl.attr( 'id' );
				}

				return name;
			},

			getSettingValue: function( $thisEl, fromRepeater  ) {
				var settingValue,
					self = this;

				// Skip options within repeater.
				if ( $thisEl.parents( '.repeater-fields, .dynamic-param-fields' ).length && ! fromRepeater ) {
					return;
				}

				if ( $thisEl.is( '#fusion_builder_content_main' ) ) {
					settingValue = $thisEl.val();

				} else if ( ! $thisEl.is( ':checkbox' ) ) {

					if ( $thisEl.is( '#generator_element_content' ) ) {
						settingValue = fusionBuilderGetContent( 'generator_element_content' );

					} else if ( $thisEl.is( '#generator_multi_child_content' ) ) {
						settingValue = fusionBuilderGetContent( 'generator_multi_child_content' );

					} else if ( $thisEl.is( 'textarea#element_content' ) && $thisEl.parents( '.fusion-builder-option' ).hasClass( 'tinymce' ) ) {
						settingValue = fusionBuilderGetContent( 'element_content' );

					} else {
						settingValue = $thisEl.val();
					}
				}

				// Escape input fields
				if ( $thisEl.is( 'input' ) && '' !== settingValue ) {
					if ( ! $thisEl.hasClass( 'fusion-builder-upload-field' ) && ! $thisEl.is( '#generator_element_content' ) && ! $thisEl.is( '#generator_multi_child_content' ) ) {
						settingValue = _.escape( settingValue );
					}
				}

				// Encode raw-textarea.
				if ( $thisEl.hasClass( 'fusion-builder-raw-textarea' ) ) {
					settingValue = FusionPageBuilderApp.base64Encode( settingValue );
				}

				// Encode code field type.
				if ( $thisEl.hasClass( 'fusion-builder-code-block' ) && 1 === Number( FusionPageBuilderApp.disable_encoding ) ) {
					settingValue = FusionPageBuilderApp.base64Encode( settingValue );
				}

				if ( $thisEl.hasClass( 'fusion-repeater-value' ) ) {
					settingValue = self.getRepeaterValues( $thisEl );
				}

				if ( 'infobox_content' === name ) {
					settingValue = _.escape( settingValue );
				}
				return settingValue;
			},

			getRepeaterValues: function( $el ) {
				var value = [],
					self  = this,
					rowValues;

				$el.parent().find( '.repeater-row' ).each( function() {
					rowValues = {};
					jQuery( this ).find( 'input, select, textarea, #fusion_builder_content_main, #fusion_builder_content_main_child, #generator_element_content, #generator_multi_child_content, #element_content' ).not( ':input[type=button], .fusion-icon-search, .category-search-field, .fusion-builder-table input, .fusion-builder-table textarea, .single-builder-dimension .fusion-builder-dimension input, .fusion-hide-from-atts' ).each( function() {
						var $input = jQuery( this ),
							name   = self.getSettingName( $input, true ),
							value  = self.getSettingValue( $input, true );
						rowValues[ name ] = value;
					} );
					value.push( rowValues );
				} );

				value    = JSON.stringify( value );
				value    = FusionPageBuilderApp.base64Encode( value );
				return value;
			},

			getDynamicValues: function() {
				var self          = this,
					dynamicParams = false;

				if ( this.$el.find( '[data-dynamic="true"]:not([data-parent-content="true"])' ).length ) {
					dynamicParams = {};
					this.$el.find( '[data-dynamic="true"]:not([data-parent-content="true"])' ).each( function() {
						var param        = jQuery( this ).attr( 'data-option-id' ),
							dynamicParam = jQuery( this ).find( '.dynamic-wrapper' ).attr( 'data-id' );

						dynamicParams[ param ] = {
							data: dynamicParam
						};
						jQuery( this ).find( 'input, select, textarea, #fusion_builder_content_main, #fusion_builder_content_main_child, #generator_element_content, #generator_multi_child_content, #element_content' ).not( ':input[type=button], .fusion-icon-search, .category-search-field, .fusion-builder-table input, .fusion-builder-table textarea, .single-builder-dimension .fusion-builder-dimension input, .fusion-hide-from-atts' ).each( function() {
							var $input = jQuery( this ),
								name   = self.getSettingName( $input, true ),
								value  = self.getSettingValue( $input, true );
								dynamicParams[ param ][ name ] = value;
						} );
					} );
				}

				if ( dynamicParams ) {
					return {
						string: FusionPageBuilderApp.base64Encode( JSON.stringify( dynamicParams ) ),
						object: dynamicParams
					};
				}
				return false;
			},

			removeOverlay: function() {
				if ( $( '.fusion_builder_modal_overlay' ).length && 2 > $( '.fusion-builder-modal-settings-container' ).length ) {
					$( '.fusion_builder_modal_overlay' ).remove();
					$( 'body' ).removeClass( 'fusion_builder_no_scroll' );
				}
			},

			generatePreview: function() {
				var elementType = this.model.get( 'element_type' ),
					viewSettings,
					view,
					previewView,
					params,
					emptySectionText;

				// Change empty section desc depending on bg image param.
				if ( 'fusion_builder_container' === elementType ) {
					params = this.model.get( 'params' );
					view = FusionPageBuilderViewManager.getView( this.model.get( 'cid' ) ).$el;
					emptySectionText = fusionBuilderText.empty_section;

					if ( '' !== params.background_image ) {
						emptySectionText = fusionBuilderText.empty_section_with_bg;
					}

					view.find( '.fusion-builder-empty-section' ).html( emptySectionText );

					// Update status icons UI
					FusionPageBuilderViewManager.getView( this.model.get( 'cid' ) ).updateStatusIcons();
				}

				if ( 'undefined' !== typeof fusionAllElements[ elementType ].preview ) {

					viewSettings = {
						model: this.model,
						collection: FusionPageBuilderElements
					};

					view = FusionPageBuilderViewManager.getView( this.model.get( 'cid' ) ).$el.find( '.fusion-builder-module-preview' );
					previewView = new FusionPageBuilder.ElementPreviewView( viewSettings );
					view.html( '' ).append( previewView.render().el );
				}
			},

			deleteModel: function() {
				FusionPageBuilderViewManager.getView( this.model.get( 'cid' ) ).$el.find( '.fusion-builder-remove' ).click();
			},

			elementSearchFilter: function() {
				var thisEl = this.$el,
					name,
					value;

				thisEl.find( '.fusion-elements-filter' ).on( 'change paste keyup', function() {

					if ( $( this ).val() ) {
						value = $( this ).val().toLowerCase();

						thisEl.find( '.fusion-builder-all-modules li' ).each( function() {

							name = $( this ).find( '.fusion_module_title' ).text().trim().toLowerCase();
							name = name.replace( 'this is a global column.', '' );

							// Also show portfolio on recent works search
							if ( 'portfolio' === name ) {
								name += ' recent works';
							}

							if ( -1 !== name.search( value ) ) {
								$( this ).show();
							} else {
								$( this ).hide();
							}

						} );

					} else {

						thisEl.find( '.fusion-builder-all-modules li' ).show();
					}
				} );
			}
		} );
	} );
}( jQuery ) );
