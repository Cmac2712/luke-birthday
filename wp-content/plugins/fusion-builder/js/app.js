/* global fusionBuilderGetContent, FusionPageBuilderApp, tinymce, fusionBuilderConfig, fusionHistoryManager, tinyMCE, unescape, fusionAllElements, FusionPageBuilderElements, confirm, fusionBuilderText, alert, FusionPageBuilderViewManager, console, fusionMultiElements, fusionBuilderStickyHeader, openShortcodeGenerator, Fuse, fusionIconSearch */
/* eslint no-bitwise: 0 */
/* eslint no-redeclare: 0 */
/* eslint no-alert: 0 */
/* eslint no-undef: 0 */
/* eslint no-mixed-operators: 0 */
/* eslint no-useless-escape: 0 */
/* eslint no-unused-vars: 0 */
/* eslint no-shadow: 0 */
/* eslint array-callback-return: 0 */
/* eslint no-throw-literal: 0 */
/* eslint max-depth: 0 */
/* eslint no-multi-assign: 0 */
/* eslint guard-for-in: 0 */
/* eslint no-native-reassign: 0 */
/* eslint no-continue: 0 */
/* eslint no-global-assign: 0 */

var FusionPageBuilder = FusionPageBuilder || {};

// Events
var FusionPageBuilderEvents = _.extend( {}, Backbone.Events );

( function( $ ) {

	var FusionDelay;

	$.fn.outerHTML = function() {
		return ( ! this.length ) ? this : ( this[ 0 ].outerHTML || ( function( el ) {
			var div = document.createElement( 'div' ),
				contents;

			div.appendChild( el.cloneNode( true ) );
			contents = div.innerHTML;
			div = null;
			return contents;
		}( this[ 0 ] ) ) );
	};

	window.fusionBuilderGetContent = function( textareaID, removeAutoP, initialLoad ) { // jshint ignore:line

		var content;

		if ( 'undefined' === typeof removeAutoP ) {
			removeAutoP = false;
		}

		if ( 'undefined' === typeof initialLoad ) {
			initialLoad = false;
		}

		if ( ! initialLoad && 'undefined' !== typeof window.tinyMCE && window.tinyMCE.get( textareaID ) && ! window.tinyMCE.get( textareaID ).isHidden() ) {
			content = window.tinyMCE.get( textareaID ).getContent();
		} else if ( $( '#' + textareaID ).length ) {
			content = $( '#' + textareaID ).val().replace( /\r?\n/g, '\r\n' );
		}

		// Remove auto p tags from content.
		if ( removeAutoP && 'undefined' !== typeof window.tinyMCE && 'undefined' !== typeof content ) {
			content = content.replace( /<p>\[/g, '[' );
			content = content.replace( /\]<\/p>/g, ']' );
		}

		if ( 'undefined' !== typeof content ) {
			return content.trim();
		}
	};

	// Delay
	FusionDelay = ( function() {
		var timer = 0;

		return function( callback, ms ) {
			clearTimeout( timer );
			timer = setTimeout( callback, ms );
		};
	}() );

	$( window ).load( function() {
		if ( $( '#fusion_toggle_builder' ).data( 'enabled' ) ) {
			$( '#fusion_toggle_builder' ).trigger( 'click' );
		}
	} );

	$( '#publishing-action #publish' ).on( 'click', function() {
		FusionPageBuilderApp.saveGlobal = false;
	} );

	$( window ).bind( 'beforeunload', function() {
		var editor = 'undefined' !== typeof tinymce && tinymce.get( 'content' );
		if ( ( ( editor && ! editor.isHidden() && editor.isDirty() ) || ( wp.autosave && wp.autosave.server.postChanged() ) ) && ( true === FusionPageBuilderApp.saveGlobal && ! $( '#publish' ).hasClass( 'disable' ) ) ) {
			FusionPageBuilderApp.saveGlobal = false;
			return '';
		}
	} );

	$( document ).ready( function() {

		var $selectedDemo,
			$useBuilderMetaField,
			$toggleBuilderButton,
			$builder,
			$mainEditorWrapper,
			$container;

		// Column sizes dialog. Close on outside click.
		$( document ).click( function( e ) {
			if ( $( e.target ).parents( '.column-sizes' ).length || $( e.target ).hasClass( 'fusion-builder-resize-column' ) ) {

				// Column sizes dialog clicked
			} else {
				$( '.column-sizes' ).hide();
			}
		} );

		// Fusion Builder App View
		FusionPageBuilder.AppView = window.wp.Backbone.View.extend( {

			el: $( '#fusion_builder_main_container' ),

			template: FusionPageBuilder.template( $( '#fusion-builder-app-template' ).html() ),

			events: {
				'click .fusion-builder-layout-button-save': 'saveLayout',
				'click .fusion-builder-layout-button-load': 'loadLayout',
				'click .fusion-builder-layout-button-delete': 'deleteLayout',
				'click .fusion-builder-layout-buttons-clear': 'clearLayout',
				'click .fusion-builder-demo-button-load': 'loadDemoPage',
				'click .fusion-builder-layout-custom-css': 'customCSS',
				'click .fusion-builder-template-buttons-save': 'saveTemplateDialog',
				'click #fusion-builder-layouts .fusion-builder-modal-close': 'hideLibrary',
				'click .fusion-builder-library-dialog': 'openLibrary',
				'mouseenter .fusion-builder-layout-buttons-history': 'showHistoryDialog',
				'mouseleave .fusion-builder-layout-buttons-history': 'hideHistoryDialog',
				'click .fusion-builder-element-button-save': 'saveElement',
				'click #fusion-load-template-dialog': 'loadPreBuiltPage',
				'click .fusion-builder-layout-buttons-toggle-containers': 'toggleAllContainers',
				'click .fusion-builder-global-tooltip': 'unglobalize',
				'click .fusion-builder-publish-tooltip': 'publish',
				contextmenu: 'contextMenu'
			},

			initialize: function() {

				this.builderActive             = false;
				this.pauseBuilder              = false;
				this.ajaxurl                   = fusionBuilderConfig.ajaxurl;
				this.fusion_load_nonce         = fusionBuilderConfig.fusion_load_nonce;
				this.fusion_builder_plugin_dir = fusionBuilderConfig.fusion_builder_plugin_dir;
				this.layoutIsLoading           = false;
				this.layoutIsSaving            = false;
				this.saveGlobal                = false;
				this.layoutIsDeleting          = false;
				this.parentRowId               = '';
				this.parentColumnId            = '';
				this.targetContainerCID        = '';
				this.activeModal               = '';
				this.innerColumn               = '';
				this.blankPage                 = '';
				this.newLayoutLoaded           = false;
				this.newContainerAdded         = false;
				this.fullWidth                 = fusionBuilderConfig.full_width;
				this.allContent                = '';

				// Shortcode Generator
				this.shortcodeGenerator                  = '';
				this.shortcodeGeneratorMultiElement      = '';
				this.shortcodeGeneratorMultiElementChild = '';
				this.allowShortcodeGenerator             = '';
				this.shortcodeGeneratorActiveEditor      = '';
				this.shortcodeGeneratorEditorID          = '';
				this.manuallyAdded                       = false;
				this.manualGenerator                     = false;
				this.manualEditor                        = '';
				this.fromExcerpt                         = false;

				// Code Block encoding
				this.disable_encoding = fusionBuilderConfig.disable_encoding;
				this._keyStr          = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
				this.codeEditor       = '';

				this.MultiElementChildSettings = false;

				// Listen for new elements
				this.listenTo( this.collection, 'add', this.addBuilderElement );

				// Convert builder layout to shortcodes
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-added', this.builderToShortcodes );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-removed', this.builderToShortcodes );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-cloned', this.builderToShortcodes );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-edited', this.builderToShortcodes );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-sorted', this.builderToShortcodes );

				// Sync global layouts.
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-added', this.syncGlobalLayouts );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-cloned', this.syncGlobalLayouts );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-edited', this.syncGlobalLayouts );
				this.listenTo( FusionPageBuilderEvents, 'fusion-element-sorted', this.syncGlobalLayouts );

				// Loader animation
				this.listenTo( FusionPageBuilderEvents, 'fusion-show-loader', this.showLoader );
				this.listenTo( FusionPageBuilderEvents, 'fusion-hide-loader', this.hideLoader );

				// Hide library
				this.listenTo( FusionPageBuilderEvents, 'fusion-hide-library', this.hideLibrary );

				// Save layout template on return key
				this.listenTo( FusionPageBuilderEvents, 'fusion-save-layout', this.saveLayout );

				// Save history state
				this.listenTo( FusionPageBuilderEvents, 'fusion-save-history-state', this.saveHistoryState );

				// Toggled Containers
				this.toggledContainers = true;

				this.render();

				// Typograph uses assets model.
				if ( 'function' === typeof FusionPageBuilder.Assets ) {
					this.assets = new FusionPageBuilder.Assets();
				}

				if ( ! jQuery( 'body' ).hasClass( 'gutenberg-editor-page' ) ) {
					if ( $( '#fusion_toggle_builder' ).hasClass( 'fusion_builder_is_active' ) ) {

						// Create builder layout on initial load.
						this.initialBuilderLayout( true );
					}

					// Turn on history tracking. Capture editor. Save initial history state.
					fusionHistoryManager.turnOnTracking();
					fusionHistoryManager.captureEditor();
					fusionHistoryManager.turnOffTracking();
				}

				// Context menu.
				this.contextMenuView = false;
				this.clipboard = {};

				// Dynamic Values Model.
				this.dynamicValues = new FusionPageBuilder.DynamicValues();
				if ( 'object' === typeof fusionDynamicData ) {
					this.dynamicValues.addData( null, fusionDynamicData.dynamicOptions );
				}
			},

			render: function() {
				this.$el.html( this.template() );
				this.sortableContainers();

				return this;
			},

			unglobalize: function( event ) {
				var cid    = jQuery( event.currentTarget ).data( 'cid' ),
					view   = FusionPageBuilderViewManager.getView( cid ),
					params = view.model.get( 'params' ),
					type   = view.model.get( 'element_type' ),
					r;

				r = confirm( fusionBuilderText.are_you_sure_you_want_to_remove_global );

				if ( false === r ) {
					return false;
				}

				// Remove global attributes
				delete params.fusion_global;
				view.model.set( 'params', params );
				view.$el.removeClass( 'fusion-global-element fusion-global-container fusion-global-column' );
				jQuery( event.currentTarget ).remove();
				view.$el.removeAttr( 'fusion-global-layout' );

				if ( 'fusion_builder_container' === type ) {
					view.$el.find( '.fusion-builder-container-content > .fusion-builder-section-content' ).removeAttr( 'fusion-global-layout' );
				}

				fusionHistoryManager.turnOnTracking();
				fusionHistoryState = fusionBuilderText.removed_global;
				FusionPageBuilderEvents.trigger( 'fusion-element-edited' );
			},

			publish: function( event ) {
				var cid    = jQuery( event.currentTarget ).data( 'cid' ),
					view   = FusionPageBuilderViewManager.getView( cid ),
					params = view.model.get( 'params' ),
					r;

				r = confirm( fusionBuilderText.are_you_sure_you_want_to_publish );

				if ( false === r ) {
					return false;
				}

				params.status = 'published';
				view.model.set( 'params', params );

				view.updateStatusIcons();

				fusionHistoryManager.turnOnTracking();
				fusionHistoryState = fusionBuilderText.container_published; // jshint ignore:line
				FusionPageBuilderEvents.trigger( 'fusion-element-edited' );
			},

			isTinyMceActive: function() {
				var isActive = ( 'undefined' !== typeof tinyMCE ) && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();

				return isActive;
			},

			base64Encode: function( data ) {
				var b64 = this._keyStr,
					o1,
					o2,
					o3,
					h1,
					h2,
					h3,
					h4,
					bits,
					i      = 0,
					ac     = 0,
					enc    = '',
					tmpArr = [],
					r;

				if ( ! data ) {
					return data;
				}

				data = unescape( encodeURIComponent( data ) );

				do {

					// Pack three octets into four hexets
					o1 = data.charCodeAt( i++ );
					o2 = data.charCodeAt( i++ );
					o3 = data.charCodeAt( i++ );

					bits = o1 << 16 | o2 << 8 | o3;

					h1 = bits >> 18 & 0x3f;
					h2 = bits >> 12 & 0x3f;
					h3 = bits >> 6 & 0x3f;
					h4 = bits & 0x3f;

					// Use hexets to index into b64, and append result to encoded string.
					tmpArr[ ac++ ] = b64.charAt( h1 ) + b64.charAt( h2 ) + b64.charAt( h3 ) + b64.charAt( h4 );
				} while ( i < data.length );

				enc = tmpArr.join( '' );
				r   = data.length % 3;

				return ( r ? enc.slice( 0, r - 3 ) : enc ) + '==='.slice( r || 3 );
			},

			base64Decode: function( input ) {
				var output = '',
					chr1,
					chr2,
					chr3,
					enc1,
					enc2,
					enc3,
					enc4,
					i = 0;

				input = input.replace( /[^A-Za-z0-9\+\/\=]/g, '' );

				while ( i < input.length ) {

					enc1 = this._keyStr.indexOf( input.charAt( i++ ) );
					enc2 = this._keyStr.indexOf( input.charAt( i++ ) );
					enc3 = this._keyStr.indexOf( input.charAt( i++ ) );
					enc4 = this._keyStr.indexOf( input.charAt( i++ ) );

					chr1 = ( enc1 << 2 ) | ( enc2 >> 4 );
					chr2 = ( ( enc2 & 15 ) << 4 ) | ( enc3 >> 2 );
					chr3 = ( ( enc3 & 3 ) << 6 ) | enc4;

					output = output + String.fromCharCode( chr1 );

					if ( 64 !== enc3 ) {
						output = output + String.fromCharCode( chr2 );
					}
					if ( 64 !== enc4 ) {
						output = output + String.fromCharCode( chr3 );
					}

				}

				output = this.utf8Decode( output );

				return output;
			},

			utf8Decode: function( utftext ) {
				var string = '',
					i  = 0,
					c  = 0,
					c1 = 0,
					c2 = 0,
					c3;

				while ( i < utftext.length ) {

					c = utftext.charCodeAt( i );

					if ( 128 > c ) {
						string += String.fromCharCode( c );
						i++;
					} else if ( ( 191 < c ) && ( 224 > c ) ) {
						c2 = utftext.charCodeAt( i + 1 );
						string += String.fromCharCode( ( ( c & 31 ) << 6 ) | ( c2 & 63 ) );
						i += 2;
					} else {
						c2 = utftext.charCodeAt( i + 1 );
						c3 = utftext.charCodeAt( i + 2 );
						string += String.fromCharCode( ( ( c & 15 ) << 12 ) | ( ( c2 & 63 ) << 6 ) | ( c3 & 63 ) );
						i += 3;
					}
				}
				return string;
			},

			fusionBuilderMCEremoveEditor: function( id ) {
				if ( 'undefined' !== typeof window.tinyMCE ) {
					window.tinyMCE.execCommand( 'mceRemoveEditor', false, id );
					if ( 'undefined' !== typeof window.tinyMCE.get( id ) ) {
						window.tinyMCE.remove( '#' + id );
					}
				}
			},

			fusion_builder_sortable_text: function( $element ) {
				var $sortable;
				$sortable = $element.find( '.fusion-sortable-text-options' );

				$sortable.each( function() {
					var $sort = jQuery( this );

					$sort.sortable( {
						handle: '.fusion-sortable-move'
					} );
					$sort.on( 'sortupdate', function( event ) {
						var sortContainer = jQuery( event.target ),
							sortOrder = '';

						sortContainer.children( '.fusion-sortable-option' ).each( function() {
							sortOrder += jQuery( this ).find( 'input' ).val() + '|';
						} );

						sortOrder = sortOrder.slice( 0, -1 );

						sortContainer.siblings( '.sort-order' ).val( sortOrder ).trigger( 'change' );
					} );

					$sort.on( 'click', '.fusion-sortable-remove', function( event ) {
						event.preventDefault();

						jQuery( event.target ).closest( '.fusion-sortable-option' ).remove();
						$sort.trigger( 'sortupdate' );
					} );

					$sort.on( 'change keyup', 'input', function() {
						$sort.trigger( 'sortupdate' );
					} );

					$sort.prev( '.fusion-builder-add-sortable-child' ).on( 'click', function( event ) {
						var $newItem = $sort.next( '.fusion-placeholder-example' ).clone( true );

						event.preventDefault();

						$newItem.removeClass( 'fusion-placeholder-example' ).removeAttr( 'style' ).appendTo( $sort );

						setTimeout( function() {
							$sort.find( '.fusion-sortable-option:last-child input' ).focus();
						}, 100 );

						$sort.trigger( 'sortupdate' );
					} );
				} );
			},

			fusion_builder_iconpicker: function( value, id, container, search ) {

				var output           = jQuery( '.fusion-icons-rendered' ).html(),
					outputNav        = jQuery( '.fusion-icon-picker-nav-rendered' ).html(),
					oldIconName      = '',
					$container       = jQuery( container ),
					$containerParent = $container.parent(),
					valueSelector    = '',
					selectedSetId   = '';

				if ( '' !== value ) {

					if ( 'fusion-prefix-' === value.substr( 0, 14 ) ) {

						// Custom icon, we need to remove prefix.
						value = value.replace( 'fusion-prefix-', '' );
					} else {
						value = value.split( ' ' );

						// Legacy FontAwesome 4.x icon, so we need check if it needs to be updated.
						if ( 'undefined' === typeof value[ 1 ] ) {
							value[ 1 ] = 'fas';

							if ( 'undefined' !== typeof window[ 'fusion-fontawesome-free-shims' ] ) {
								oldIconName = value[ 0 ].substr( 3 );

								jQuery.each( window[ 'fusion-fontawesome-free-shims' ], function( i, shim ) {

									if ( shim[ 0 ] === oldIconName ) {

										// Update icon name.
										if ( null !== shim[ 2 ] ) {
											value[ 0 ] = 'fa-' + shim[ 2 ];
										}

										// Update icon subset.
										if ( null !== shim[ 1 ] ) {
											value[ 1 ] = shim[ 1 ];
										}

										return false;
									}
								} );
							}

							// Update form field with new values.
							$containerParent.find( '.fusion-iconpicker-input' ).attr( 'value', value[ 0 ] + ' ' + value[ 1 ] );
						}
					}
				}

				// Add icon container and icon navigation.
				$container.html( output ).before( '<div class="fusion-icon-picker-nav">' + outputNav + '</div>' );

				// Icon navigation link is clicked.
				$containerParent.find( '.fusion-icon-picker-nav > a' ).on( 'click', function( e ) {
					e.preventDefault();

					jQuery( '.fusion-icon-picker-nav-active' ).removeClass( 'fusion-icon-picker-nav-active' );
					jQuery( this ).addClass( 'fusion-icon-picker-nav-active' );
					$container.find( '.fusion-icon-set' ).css( 'display', 'none' );
					$container.find( jQuery( this ).attr( 'href' ) ).css( 'display', 'grid' );
				} );

				if ( '' !== value ) {

					// FA or custom icon.
					valueSelector = '.' + ( Array.isArray( value ) ? value.join( '.' ) : value );
					$container.find( valueSelector ).parent().addClass( 'selected-element' ).css( 'display', 'flex' );

					// Trigger click on parent nav tab item.
					selectedSetId = $container.find( '.selected-element' ).closest( '.fusion-icon-set' ).prepend( $container.find( '.selected-element' ) ).attr( 'id' );
					$containerParent.find( '.fusion-icon-picker-nav a[href="#' + selectedSetId + '"]' ).trigger( 'click' );
				}

				// Icon Search bar.
				jQuery( search ).on( 'change paste keyup', function() {
					var thisEl = jQuery( this );

					FusionDelay( function() {
						var options,
							fuse,
							result,
							value;

						if ( thisEl.val() ) {
							value = thisEl.val().toLowerCase();

							if ( 3 > value.length ) {
								return;
							}

							$container.find( '.fusion-icon-set .icon_preview' ).css( 'display', 'none' );
							options = {
								threshold: 0.2,
								location: 0,
								distance: 100,
								maxPatternLength: 32,
								minMatchCharLength: 3,
								keys: [
									'name',
									'keywords',
									'categories'
								]
							};
							fuse = new Fuse( fusionIconSearch, options );
							result = fuse.search( value );

							// Show icons.
							_.each( result, function( resultIcon ) {
								$container.find( '.icon_preview.' + resultIcon.name ).css( 'display', 'flex' );
							} );

							// Add attributes to iconset containers.
							_.each( $container.find( '.fusion-icon-set' ), function( subContainer ) {
								var hasSearchResults = false;
								subContainer.classList.add( 'no-search-results' );
								jQuery( '.icon_preview' ).each( function( index, icon ) {
									if ( 'none' !== icon.style.display && subContainer.classList.contains( 'no-search-results' ) ) {
										hasSearchResults = true;
									}
								} );

								if ( ! hasSearchResults && ! subContainer.querySelector( '.no-search-results-notice' ) ) {
									jQuery( subContainer ).append( '<div class="no-search-results-notice">' + fusionBuilderText.no_results_in.replace( '%s', jQuery( 'a[href="#' + subContainer.id + '"]' ).html() ) + '</div>' );
								} else if ( hasSearchResults ) {
									subContainer.classList.remove( 'no-search-results' );
								}
							} );

						} else {
							$container.find( '.fusion-icon-set .icon_preview' ).css( 'display', 'flex' );
							_.each( $container.find( '.fusion-icon-set' ), function( subContainer ) {
								subContainer.classList.remove( 'no-search-results' );
							} );
						}
					}, 100 );
				} );
			},

			/**
			 * Trigger context menu.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The jQuery event.
			 * @return {void}
			 */
			contextMenu: function( event ) {
				var viewSettings,
					view,
					self         = this,
					$clickTarget = jQuery( event.target ),
					$target      = $clickTarget.closest( '[data-cid]:not(.fusion-builder-row-content)' ),
					pageType     = 'default',
					elementType;

				// Disable on blank template element.
				if ( $clickTarget.hasClass( 'fusion_builder_blank_page' ) || $clickTarget.closest( '.fusion_builder_blank_page' ).length ) {
					return;
				}

				if ( $clickTarget.data( 'cid' ) ) {
					$target = $clickTarget;
				}

				// If targeting the container heading area.
				if ( $clickTarget.hasClass( 'fusion-builder-section-header' ) || $clickTarget.closest( '.fusion-builder-section-header' ).length ) {
					if ( $clickTarget.hasClass( 'fusion-builder-section-name' ) ) {
						return;
					}
					$target = $clickTarget.closest( '.fusion_builder_container' ).find( '.fusion-builder-section-content' ).first();
				}

				// Remove any existing.
				this.removeContextMenu();

				event.preventDefault();

				view = FusionPageBuilderViewManager.getView( $target.data( 'cid' ) );

				if ( ! view ) {
					return;
				}

				elementType = this.getElementType( view.model.attributes.element_type );

				// Make sure library view has limited abilities.
				if ( jQuery( 'body' ).hasClass( 'fusion-builder-library-edit' ) && ! $clickTarget.closest( '.fusion-builder-row-container-inner' ).length ) {
					if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-sections' ) ) {
						pageType = 'container';
					}
					if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-columns' ) ) {
						pageType = 'column';
						if ( 'fusion_builder_container' === elementType ) {
							return;
						}
					}
					if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-elements' ) ) {
						pageType = 'element';
						if ( 'fusion_builder_container' === elementType || 'fusion_builder_column' === elementType || 'fusion_builder_column_inner' === elementType ) {
							return;
						}
					}
				}

				if ( ! view ) {
					return;
				}

				viewSettings = {
					model: {
						parent: view.model,
						event: event,
						parentView: view,
						pageType: pageType
					}
				};

				// Create new context view.
				this.contextMenuView = new FusionPageBuilder.ContextMenuView( viewSettings );

				// Add context menu to builder.
				this.$el.append( this.contextMenuView.render().el );

				// Add listener to remove.
				this.$el.one( 'click', function() {
					self.removeContextMenu();
				} );
			},

			/**
			 * Remove any contextMenu.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			removeContextMenu: function() {
				if ( this.contextMenuView && 'function' === typeof this.contextMenuView.removeMenu ) {
					this.contextMenuView.removeMenu();
				}
			},

			/**
			 * Get element type, split up element.
			 *
			 * @since 2.0.0
			 * @param {string} elementType - The element type/name.
			 * @return {void}
			 */
			getElementType: function( elementType ) {
				var childElements;

				if ( 'fusion_builder_container' === elementType || 'fusion_builder_column' === elementType || 'fusion_builder_column_inner' === elementType ) {
					return elementType;
				}

				// First check if its a parent.
				if ( elementType in fusionMultiElements ) {
					return 'parent_element';
				}

				// Check if its a child.
				childElements = _.values( fusionMultiElements );
				if ( -1 !== childElements.indexOf( elementType ) ) {
					return 'child_element';
				}

				if ( 'fusion_builder_row_inner' === elementType && FusionPageBuilderApp.pauseBuilder ) {
					return 'fusion_builder_row_inner';
				}

				// Made it this far it must be regular.
				return 'element';
			},

			fusionBuilderImagePreview: function( $uploadButton ) {
				var $uploadField = $uploadButton.siblings( '.fusion-builder-upload-field' ),
					$preview     = $uploadField.siblings( '.fusion-builder-upload-preview' ),
					$removeBtn   = $uploadButton.siblings( '.upload-image-remove' ),
					imageURL     = $uploadField.val().trim(),
					imagePreview,
					imageIDField;

				if ( 0 <= imageURL.indexOf( '<img' ) ) {
					imagePreview = imageURL;
				} else {
					imagePreview = '<img src="' + imageURL + '" />';
				}

				if ( 'image' !== $uploadButton.data( 'type' ) ) {
					return;
				}

				if ( $uploadButton.hasClass( 'hide-edit-buttons' ) ) {
					return;
				}

				if ( '' === imageURL ) {
					if ( $preview.length ) {
						$preview.remove();
						$removeBtn.remove();
						$uploadButton.val( 'Upload Image' );
					}

					// Remove image ID if image preview is empty.
					imageIDField = $uploadButton.parents( '.fusion-builder-option' ).next().find( '#' + $uploadButton.data( 'param' ) + '_id' );

					if ( 'element_content' === $uploadButton.data( 'param' ) ) {
						imageIDField = $uploadButton.parents( '.fusion-builder-option' ).next().find( '#image_id' );
					}

					if ( imageIDField.length ) {
						imageIDField.val( '' );
					}

					return;
				}

				if ( ! $preview.length ) {
					$uploadButton.siblings( '.preview' ).before( '<div class="fusion-builder-upload-preview"><strong class="fusion-builder-upload-preview-title">Preview</strong><div class="fusion-builder-preview-image"><img src="" width="300" height="300" /></div></div>' );
					$uploadButton.after( '<input type="button" class="button upload-image-remove" value="Remove" />' );
					$uploadButton.val( 'Edit' );
					$preview = $uploadField.siblings( '.fusion-builder-upload-preview' );

				}

				$preview.find( 'img' ).replaceWith( imagePreview );
			},

			FusionBuilderActivateUpload: function( $uploadButton ) {
				$uploadButton.click( function( event ) {

					var $thisEl,
						fileFrame,
						multiImageContainer,
						multiImageInput,
						multiVal,
						multiUpload    = false,
						multiImages    = false,
						multiImageHtml = '',
						ids            = '',
						optionID       = '',
						attachment     = '',
						attachments    = [],
						elementType    = $( this ).closest( '.fusion_builder_module_settings' ).data( 'element_type' ),
						param          = $( this ).closest( '.fusion-builder-option' ).data( 'option-id' );

					if ( event ) {
						event.preventDefault();
					}

					$thisEl = $( this );

					// If its a multi upload element, clone default params.
					if ( 'fusion-multiple-upload' === $thisEl.data( 'id' ) ) {
						multiUpload = true;
					}

					if ( 'fusion-multiple-images' === $thisEl.data( 'id' ) ) {
						multiImages = true;
						multiImageContainer = jQuery( $thisEl.next( '.fusion-multiple-image-container' ) )[ 0 ];
						multiImageInput = jQuery( $thisEl ).prev( '.fusion-multi-image-input' );
					}

					fileFrame = wp.media( {
						library: {
							type: $thisEl.data( 'type' )
						},
						title: $thisEl.data( 'title' ),
						multiple: ( multiUpload || multiImages ) ? 'between' : false,
						frame: 'post',
						className: 'media-frame mode-select fusion-builder-media-dialog ' + $thisEl.data( 'id' ),
						displayUserSettings: false,
						displaySettings: true,
						allowLocalEdits: true
					} );
					wp.media.frames.file_frame = fileFrame;

					// Set the media dialog box state as 'gallery' if the element is gallery.
					if ( multiImages && 'fusion_gallery' === elementType ) {
						multiVal    = multiImageInput.val();
						ids         = 'string' === typeof multiVal ? multiVal.split( ',' ) : '';
						attachments = [];
						attachment  = '';

						wp.media._galleryDefaults.link  = 'none';
						wp.media._galleryDefaults.size  = 'thumbnail';
						fileFrame.options.syncSelection = true;

						if ( 'undefined' !== typeof multiVal && '' !== multiVal ) {
							fileFrame.options.state = 'gallery-edit';
						} else {
							fileFrame.options.state = 'gallery';
						}
					}

					// Select currently active image automatically.
					fileFrame.on( 'open', function() {
						var selection = fileFrame.state().get( 'selection' ),
							library   = fileFrame.state().get( 'library' ),
							attachment,
							id,
							fetchIds = [];

						if ( multiImages ) {
							if ( 'fusion_gallery' !== elementType || 'gallery-edit' !== fileFrame.options.state ) {
								$( '.fusion-builder-media-dialog' ).addClass( 'hide-menu' );
							}

							jQuery.each( ids, function( index, id ) {
								if ( '' !== id && 'NaN' !== id ) {

									// Check if attachment exists.
									if ( 'undefined' !== typeof wp.media.attachment( id ).get( 'url' ) ) {

										// Exists, add it to selection.
										selection.add( wp.media.attachment( id ) );
										library.add( wp.media.attachment( id ) );

									} else {

										// Doesn't exist we need to fetch.
										fetchIds.push( id );
									}
								}
							} );

							// If still some attachments needing fetched, fetch them in a single query.
							if ( 0 < fetchIds.length ) {
								wp.media.query( { post__in: fetchIds, posts_per_page: fetchIds.length } ).more().then( function() {
									jQuery.each( ids, function( index, id ) {
										if ( '' !== id && 'NaN' !== id ) {

											// Add fetched attachment to selection.
											selection.add( wp.media.attachment( id ) );
											library.add( wp.media.attachment( id ) );
										}
									} );
								} );
							}
						} else {
							optionID = $thisEl.parents( '.fusion-builder-option.upload' ).data( 'option-id' );

							id = $thisEl.parents( '.fusion-builder-module-settings' ).find( '#' + optionID + '_id' ).val();
							id = ( 'undefined' !== typeof id ? id : $thisEl.parents( '.fusion-builder-module-settings' ).find( '#image_id' ).val() );

							if ( 'undefined' !== typeof id && '' !== id ) {
								id = id.split( '|' )[ 0 ];
							}

							attachment = wp.media.attachment( id );

							$( '.fusion-builder-media-dialog' ).addClass( 'hide-menu' );
							if ( id ) {
								attachment.fetch( {
									success: function( att ) {
										library.add( att ? [ att ] : [] );
										selection.add( att ? [ att ] : [] );
									}
								} );
							}
						}
					} );

					// Set the attachment ids from gallery selection if the element is gallery.
					if ( multiImages && 'fusion_gallery' === elementType ) {
						fileFrame.on( 'update', function( selection ) {
							var imageIDs = '',
								imageURL = '';

							imageIDs = selection.map( function( attachment ) {
								var imageID = attachment.id;

								if ( attachment.attributes.sizes && 'undefined' !== typeof attachment.attributes.sizes.thumbnail ) {
									imageURL = attachment.attributes.sizes.thumbnail.url;
								} else if ( attachment.attributes.url ) {
									imageURL = attachment.attributes.url;
								}

								if ( multiImages ) {
									multiImageHtml += '<div class="fusion-multi-image" data-image-id="' + imageID + '">';
									multiImageHtml += '<img src="' + imageURL + '"/>';
									multiImageHtml += '<span class="fusion-multi-image-remove dashicons dashicons-no-alt"></span>';
									multiImageHtml += '</div>';
								}
								return attachment.id;
							} );

							multiImageInput.val( imageIDs );
							jQuery( multiImageContainer ).html( multiImageHtml );
						} );
					}

					fileFrame.on( 'select insert', function() {

						var imageURL,
							imageID,
							imageSize,
							imageIDs,
							state = fileFrame.state(),
							firstElementNode,
							firstElement,
							imageIDField;

						if ( 'undefined' === typeof state.get( 'selection' ) ) {
							imageURL = jQuery( fileFrame.$el ).find( '#embed-url-field' ).val();
						} else {

							imageIDs = state.get( 'selection' ).map( function( attachment ) {
								return attachment.id;
							} );

							// If its a multi image element, add the images container and IDs to input field.
							if ( multiImages ) {
								multiImageInput.val( imageIDs );
							}

							// Remove default item.
							if ( multiUpload ) {
								firstElementNode = jQuery( $thisEl ).parents( '.fusion-builder-main-settings' ).find( '.fusion-builder-sortable-options li:first-child' );
								if ( firstElementNode.length ) {
									firstElement = FusionPageBuilderElements.find( function( model ) {
										return model.get( 'cid' ) === firstElementNode.data( 'cid' );
									} );
									if ( firstElement && ( 'undefined' === typeof firstElement.attributes.params.image || '' === firstElement.attributes.params.image ) ) {
										jQuery( $thisEl ).parents( '.fusion-builder-main-settings' ).find( '.fusion-builder-sortable-options li:first-child .fusion-builder-multi-setting-remove' ).trigger( 'click' );
									}
								}
							}

							state.get( 'selection' ).map( function( attachment ) {
								var element = attachment.toJSON(),
									display = state.display( attachment ).toJSON(),
									defaultParams  = {},
									child,
									params,
									createChildren;

								imageID = element.id;
								imageSize = display.size;
								if ( element.sizes && element.sizes[ display.size ] && element.sizes[ display.size ].url ) {
									imageURL = element.sizes[ display.size ].url;
								} else if ( element.url ) {
									imageURL = element.url;
								}

								if ( multiImages ) {
									multiImageHtml += '<div class="fusion-multi-image" data-image-id="' + imageID + '">';
									multiImageHtml += '<img src="' + imageURL + '"/>';
									multiImageHtml += '<span class="fusion-multi-image-remove dashicons dashicons-no-alt"></span>';
									multiImageHtml += '</div>';
								}

								// If its a multi upload element, add the image to defaults and trigger a new item to be added.
								if ( multiUpload ) {
									child          = fusionAllElements[ elementType ].element_child;
									params         = fusionAllElements[ elementType ].params[ param ].child_params;
									createChildren = 'undefined' !== typeof fusionAllElements[ elementType ].params[ param ].create_children ? fusionAllElements[ elementType ].params[ param ].create_children : true;

									// Save default values
									_.each( params, function( name, param ) {
										defaultParams[ param ] = fusionAllElements[ child ].params[ param ].value;
									} );

									// Set new default values
									_.each( params, function( name, param ) {
										fusionAllElements[ child ].params[ param ].value = attachment.attributes[ name ];
									} );

									if ( 'image' === param ) {
										fusionAllElements[ elementType ].params[ param + '_id' ].value = imageID + '|' + imageSize;
									}

									if ( createChildren ) {

										jQuery( $thisEl ).parents( '.fusion-builder-main-settings' ).find( '.fusion-builder-add-multi-child' ).trigger( 'click' );
										FusionPageBuilderEvents.trigger( 'fusion-multi-child-update-preview' );
									}

									// Restore default values
									_.each( defaultParams, function( defaultValue, param ) {
										fusionAllElements[ child ].params[ param ].value = defaultValue;
									} );
								}
							} );
						}

						jQuery( multiImageContainer ).html( multiImageHtml );
						if ( ! multiUpload && ! multiImages ) {
							$thisEl.siblings( '.fusion-builder-upload-field' ).val( imageURL ).trigger( 'change' );

							// Set image id.
							imageIDField = $thisEl.parents( '.fusion-builder-option' ).next().find( '#' + param + '_id' );

							if ( 'element_content' === param ) {
								imageIDField = $thisEl.parents( '.fusion-builder-option' ).next().find( '#image_id' );
							}

							if ( imageIDField.length ) {
								imageIDField.val( imageID + '|' + imageSize );
							}

							FusionPageBuilderApp.fusionBuilderImagePreview( $thisEl );
						}
					} );

					fileFrame.open();

					return false;
				} );

				$uploadButton.siblings( '.fusion-builder-upload-field' ).on( 'input', function() {
					FusionPageBuilderApp.fusionBuilderImagePreview( $( this ).siblings( '.fusion-builder-upload-button' ) );
				} );

				$uploadButton.siblings( '.fusion-builder-upload-field' ).each( function() {
					FusionPageBuilderApp.fusionBuilderImagePreview( $( this ).siblings( '.fusion-builder-upload-button' ) );
				} );

				jQuery( 'body' ).on( 'click', '.fusion-multi-image-remove', function() {
					var input = jQuery( this ).parents( '.fusion-multiple-upload-images' ).find( '.fusion-multi-image-input' ),
						imageIDs,
						imageID,
						imageIndex;

					imageID = jQuery( this ).parent( '.fusion-multi-image' ).data( 'image-id' );
					imageIDs = input.val().split( ',' ).map( function( v ) {
						return parseInt( v, 10 );
					} );
					imageIndex = imageIDs.indexOf( imageID );
					if ( -1 !== imageIndex ) {
						imageIDs.splice( imageIndex, 1 );
					}
					imageIDs = imageIDs.join( ',' );
					input.val( imageIDs );
					jQuery( this ).parent( '.fusion-multi-image' ).remove();
				} );
			},

			fusionBuilderActivateLinkSelector: function( $linkButton ) {
				var $linkSubmit       = jQuery( '#wp-link-submit' ),
					$linkTitle        = jQuery( '.wp-link-text-field' ),
					$linkTarget       = jQuery( '.link-target' ),
					$fusionLinkSubmit = jQuery( '<input type="button" name="fusion-link-submit" id="fusion-link-submit" class="button-primary" value="Set Link">' ),
					$linkDialog       = window.wpLink,
					wpLinkL10n        = window.wpLinkL10n,
					$input,
					$url;

				jQuery( $linkButton ).click( function( e ) {
					$fusionLinkSubmit.insertBefore( $linkSubmit );
					$input = jQuery( e.target ).prev( '.fusion-builder-link-field' );
					$url   = $input.val();
					$linkSubmit.hide();
					$linkTitle.hide();
					$linkTarget.hide();
					$fusionLinkSubmit.show();
					$linkDialog = ! window.wpLink && $.fn.wpdialog && jQuery( '#wp-link' ).length ? {
						$link: ! 1,
						open: function() {
							this.$link = jQuery( '#wp-link' ).wpdialog( {
								title: wpLinkL10n.title,
								width: 480,
								height: 'auto',
								modal: ! 0,
								dialogClass: 'wp-dialog',
								zIndex: 3e5
							} );
						},
						close: function() {
							this.$link.wpdialog( 'close' );
						}
					} : window.wpLink;
					$linkDialog.fusionUpdateLink = function( $fusionLinkSubmit ) {
						e.preventDefault();
						e.stopImmediatePropagation();
						e.stopPropagation();
						$url = jQuery( '#wp-link-url' ).length ? jQuery( '#wp-link-url' ).val() : jQuery( '#url-field' ).val();
						$input.val( $url ).trigger( 'change' );
						$linkSubmit.show();
						$linkTitle.show();
						$linkTarget.show();
						$fusionLinkSubmit.remove();
						jQuery( '#wp-link-cancel' ).unbind( 'click' );
						$linkDialog.close();
					};

					// Using custom CSS field here as dummy text area, as it is always available.
					$linkDialog.open( 'fusion-custom-css-field' );
					jQuery( '#wp-link-url' ).val( $url );
				} );

				jQuery( 'body' ).on( 'click', '#fusion-link-submit', function() {
					$linkDialog.fusionUpdateLink( jQuery( this ) );
				} );

				jQuery( 'body' ).on( 'click', '#wp-link-cancel, #wp-link-close, #wp-link-backdrop', function() {
					$linkSubmit.show();
					$linkTitle.show();
					$linkTarget.show();
					$fusionLinkSubmit.remove();
				} );
			},

			fusionBuilderSetContent: function( textareaID, content ) {
				if ( 'undefined' !== typeof window.tinyMCE && window.tinyMCE.get( textareaID ) && ! window.tinyMCE.get( textareaID ).isHidden() ) {

					if ( window.tinyMCE.get( textareaID ).getParam( 'wpautop', true ) && 'undefined' !== typeof window.switchEditors ) {
						content = window.switchEditors.wpautop( content );
					}

					window.tinyMCE.get( textareaID ).setContent( content, { format: 'html' } );
				} else {
					$( '#' + textareaID ).val( content );
				}
			},

			layoutLoaded: function() {
				this.newLayoutLoaded = true;
			},

			clearLayout: function( event ) {

				var r;

				if ( event ) {
					event.preventDefault();
				}

				r = confirm( fusionBuilderText.are_you_sure_you_want_to_delete_this_layout );

				if ( false === r ) {
					return false;
				}

				this.blankPage = true;
				this.clearBuilderLayout( true );

				// Clear history
				fusionHistoryManager.clearEditor( 'blank' );

			},

			showHistoryDialog: function( event ) {
				if ( event ) {
					event.preventDefault();
				}
				this.$el.find( '.fusion-builder-history-list' ).show();
			},

			hideHistoryDialog: function( event ) {
				if ( event ) {
					event.preventDefault();
				}
				this.$el.find( '.fusion-builder-history-list' ).hide();
			},

			saveTemplateDialog: function( event ) {
				if ( event ) {
					event.preventDefault();
				}
				this.showLibrary();
				$( '#fusion-builder-layouts-templates-trigger' ).click();
			},

			loadPreBuiltPage: function( event ) {
				if ( event ) {
					event.preventDefault();
				}
				this.showLibrary();
				$( '#fusion-builder-layouts-demos-trigger' ).click();
			},

			saveLayout: function( event ) {

				var templateContent,
					templateName,
					layoutsContainer,
					currentPostID,
					emptyMessage,
					customCSS,
					pageTemplate,
					$customFields = [],
					$name,
					$value;

				if ( event ) {
					event.preventDefault();
				}

				// Get custom field values for saving.
				jQuery( 'input[id^="pyre_"], select[id^="pyre_"]' ).each( function( n ) {
					$name = jQuery( this ).attr( 'id' );
					$value = jQuery( this ).val();
					if ( 'undefined' !== typeof $name && 'undefined' !== typeof $value ) {
						$customFields[ n ] = [ $name, $value ];
					}
				} );

				templateContent  = fusionBuilderGetContent( 'content', true ); // jshint ignore:line
				templateName     = $( '#new_template_name' ).val();
				layoutsContainer = $( '#fusion-builder-layouts-templates .fusion-page-layouts' );
				currentPostID    = $( '#fusion_builder_main_container' ).data( 'post-id' );
				emptyMessage     = $( '#fusion-builder-layouts-templates .fusion-page-layouts .fusion-empty-library-message' );
				customCSS        = $( '#fusion-custom-css-field' ).val();
				pageTemplate     = $( '#page_template' ).val();

				if ( '' !== templateName ) {

					$.ajax( {
						type: 'POST',
						url: fusionBuilderConfig.ajaxurl,
						dataType: 'json',
						data: {
							action: 'fusion_builder_save_layout',
							fusion_load_nonce: fusionBuilderConfig.fusion_load_nonce,
							fusion_layout_name: templateName,
							fusion_layout_content: templateContent,
							fusion_layout_post_type: 'fusion_template',
							fusion_current_post_id: currentPostID,
							fusion_custom_css: customCSS,
							fusion_page_template: pageTemplate,
							fusion_options: $customFields
						},
						complete: function( data ) {
							layoutsContainer.prepend( data.responseText );
							emptyMessage.hide();
						}
					} );

					$( '#new_template_name' ).val( '' );

				} else {
					alert( fusionBuilderText.please_enter_template_name );
				}
			},

			saveElement: function( event ) {
				var fusionElementType,
					elementCID,
					elementView;

				if ( event ) {
					event.preventDefault();
				}

				fusionElementType = $( event.currentTarget ).data( 'element-type' );
				elementCID        = $( event.currentTarget ).data( 'element-cid' );
				elementView       = FusionPageBuilderViewManager.getView( elementCID );

				elementView.saveElement();
			},

			loadLayout: function( event ) {
				var $layout,
					contentPlacement,
					content,
					$customCSS;

				if ( event ) {
					event.preventDefault();
				}

				if ( true === this.layoutIsLoading ) {
					return;
				}

				this.layoutIsLoading = true;

				$layout          = $( event.currentTarget ).closest( 'li' );
				contentPlacement = $( event.currentTarget ).data( 'load-type' );
				content          = fusionBuilderGetContent( 'content' );
				$customCSS       = jQuery( '#fusion-custom-css-field' ).val();

				$.ajax( {
					type: 'POST',
					url: fusionBuilderConfig.ajaxurl,
					data: {
						action: 'fusion_builder_load_layout',
						fusion_load_nonce: fusionBuilderConfig.fusion_load_nonce,
						fusion_layout_id: $layout.data( 'layout_id' )
					},
					beforeSend: function() {
						FusionPageBuilderEvents.trigger( 'fusion-show-loader' );

						$( 'body' ).removeClass( 'fusion_builder_inner_row_no_scroll' );
						$( '.fusion_builder_modal_inner_row_overlay' ).remove();
						$( '#fusion-builder-layouts' ).hide();

					},
					success: function( data ) {
						var dataObj;

						// New layout loaded
						FusionPageBuilderApp.layoutLoaded();

						dataObj = JSON.parse( data );

						if ( 'above' === contentPlacement ) {
							content = dataObj.post_content + content;

							// Set custom css above
							if ( 'undefined' !== typeof dataObj.custom_css ) {
								$( '#fusion-custom-css-field' ).val( dataObj.custom_css + '\n' + $customCSS );
							}

						} else if ( 'below' === contentPlacement ) {
							content = content + dataObj.post_content;

							// Set custom css below
							if ( 'undefined' !== typeof dataObj.custom_css ) {
								if ( $customCSS.length ) {
									$( '#fusion-custom-css-field' ).val( $customCSS + '\n' + dataObj.custom_css );
								} else {
									$( '#fusion-custom-css-field' ).val( dataObj.custom_css );
								}
							}

						} else {
							content = dataObj.post_content;

							// Set custom css.
							if ( 'undefined' !== typeof dataObj.custom_css ) {
								$( '#fusion-custom-css-field' ).val( dataObj.custom_css );
							}

							// Set Fusion Option selection.
							jQuery.each( dataObj.post_meta, function( $name, $value ) {
								jQuery( '#' + $name ).val( $value ).trigger( 'change' );
							} );
						}

						FusionPageBuilderApp.clearBuilderLayout();

						FusionPageBuilderApp.createBuilderLayout( content );

						// Set page template
						if ( 'undefined' !== typeof dataObj.page_template ) {
							$( '#page_template' ).val( dataObj.page_template );
						}

						FusionPageBuilderApp.layoutIsLoading = false;
					},
					complete: function() {
						FusionPageBuilderEvents.trigger( 'fusion-hide-loader' );
					}
				} );
			},

			loadDemoPage: function( event ) {
				var pageName,
					demoName,
					postId,
					content,
					r;

				if ( event ) {
					event.preventDefault();
				}

				r = confirm( fusionBuilderText.importing_single_page );

				if ( false === r ) {
					return false;
				}

				if ( true === this.layoutIsLoading ) {
					return;
				}

				this.layoutIsLoading = true;

				pageName = $( event.currentTarget ).data( 'page-name' );
				demoName = $( event.currentTarget ).data( 'demo-name' );
				postId   = $( event.currentTarget ).data( 'post-id' );

				$.ajax( {
					type: 'POST',
					url: fusionBuilderConfig.ajaxurl,
					data: {
						action: 'fusion_builder_load_demo',
						fusion_load_nonce: fusionBuilderConfig.fusion_load_nonce,
						page_name: pageName,
						demo_name: demoName,
						post_id: postId
					},
					beforeSend: function() {
						FusionPageBuilderEvents.trigger( 'fusion-show-loader' );

						$( 'body' ).removeClass( 'fusion_builder_inner_row_no_scroll' );
						$( '.fusion_builder_modal_inner_row_overlay' ).remove();
						$( '#fusion-builder-layouts' ).hide();

					},
					success: function( data ) {
						var dataObj,
							meta;

						// New layout loaded
						FusionPageBuilderApp.layoutLoaded();

						dataObj = JSON.parse( data );

						content = dataObj.post_content;

						FusionPageBuilderApp.clearBuilderLayout( false );

						FusionPageBuilderApp.createBuilderLayout( content );

						// Set page template
						if ( 'undefined' !== typeof dataObj.page_template ) {
							$( '#page_template' ).val( dataObj.page_template );
						}

						meta = dataObj.meta;

						// Set page options
						_.each( meta, function( value, name ) {
							$( '#' + name ).val( value ).trigger( 'change' );
						} );

						FusionPageBuilderApp.layoutIsLoading = false;
					},
					complete: function() {
						FusionPageBuilderEvents.trigger( 'fusion-hide-loader' );
					}
				} );
			},

			deleteLayout: function( event ) {

				var $layout,
					r,
					isGlobal = false;

				if ( event ) {
					event.preventDefault();

					if ( $( event.currentTarget ).closest( 'li' ).hasClass( 'fusion-global' ) ) {
						r        = confirm( fusionBuilderText.are_you_sure_you_want_to_delete_global );
						isGlobal = true;
					} else {
						r = confirm( fusionBuilderText.are_you_sure_you_want_to_delete_this );
					}

					if ( false === r ) {
						return false;
					}
				}

				if ( true === this.layoutIsDeleting ) {
					return;
				}

				this.layoutIsDeleting = true;

				$layout = $( event.currentTarget ).closest( 'li' );

				$.ajax( {
					type: 'POST',
					url: fusionBuilderConfig.ajaxurl,
					data: {
						action: 'fusion_builder_delete_layout',
						fusion_load_nonce: fusionBuilderConfig.fusion_load_nonce,
						fusion_layout_id: $layout.data( 'layout_id' )
					},
					success: function() {
						var $containerSuffix = 'elements';
						if ( $layout.parents( '#fusion-builder-layouts-templates' ).length ) {
							$containerSuffix = 'templates';
						}

						$layout.remove();

						FusionPageBuilderApp.layoutIsDeleting = false;
						if ( ! $( '#fusion-builder-layouts-' + $containerSuffix + ' .fusion-page-layouts' ).find( 'li' ).length ) {
							$( '#fusion-builder-layouts-' + $containerSuffix + ' .fusion-page-layouts .fusion-empty-library-message' ).show();
						}

						if ( true === isGlobal ) {
							$.each( $( 'div[fusion-global-layout="' + $layout.data( 'layout_id' ) + '"]' ), function( i, val ) { // jshint ignore:line
								if ( $( this ).hasClass( 'fusion-builder-section-content' ) ) {
									$( this ).parent().parent().find( 'a.fusion-builder-remove' ).first().trigger( 'click' );
								} else {
									$( this ).find( 'a.fusion-builder-remove' ).first().trigger( 'click' );
									$( this ).find( 'a.fusion-builder-remove-inner-row' ).first().trigger( 'click' );
								}
							} );
						}
					}
				} );
			},

			openLibrary: function( event ) {
				if ( event ) {
					event.preventDefault();
				}
				this.showLibrary();
				$( '#fusion-builder-layouts-templates-trigger' ).click();
			},

			showLibrary: function( event ) {
				if ( event ) {
					event.preventDefault();
				}

				$( '#fusion-builder-layouts' ).show();
				$( 'body' ).addClass( 'fusion_builder_inner_row_no_scroll' ).append( '<div class="fusion_builder_modal_inner_row_overlay"></div>' );

				setTimeout( function() {
					$( '.fusion-builder-save-element-input, #new_template_name' ).focus();
				}, 20 );
			},

			hideLibrary: function( event ) {
				if ( event ) {
					event.preventDefault();
				}

				$( '#fusion-builder-layouts' ).hide();
				$( 'body' ).removeClass( 'fusion_builder_inner_row_no_scroll' );
				$( '.fusion_builder_modal_inner_row_overlay' ).remove();
				$( '.fusion-save-element-fields' ).remove();
			},

			showLoader: function() {
				$( '#fusion_builder_main_container' ).css( 'height', '148px' );
				$( '#fusion_builder_container' ).hide();
				$( '#fusion-loader' ).fadeIn( 'fast' );
			},

			hideLoader: function() {
				$( '#fusion_builder_container' ).fadeIn( 'fast' );
				$( '#fusion_builder_main_container' ).removeAttr( 'style' );
				$( '#fusion-loader' ).fadeOut( 'fast' );
			},

			sortableContainers: function() {
				this.$el.sortable( {
					handle: '.fusion-builder-section-header',
					items: '.fusion_builder_container, .fusion-builder-next-page',
					cancel: '.fusion-builder-section-name, .fusion-builder-settings, .fusion-builder-clone, .fusion-builder-remove, .fusion-builder-section-add, .fusion-builder-add-element, .fusion-builder-insert-column, #fusion_builder_controls, .fusion-builder-save-element',
					cursor: 'move',
					update: function() {
						fusionHistoryManager.turnOnTracking();
						fusionHistoryState = fusionBuilderText.moved_container; // jshint ignore:line
						FusionPageBuilderEvents.trigger( 'fusion-element-sorted' );
					}
				} );
			},

			initialBuilderLayout: function( initialLoad ) {

				// Clear all views
				FusionPageBuilderViewManager.removeViews();

				FusionPageBuilderEvents.trigger( 'fusion-show-loader' );

				setTimeout( function() {

					var content                   = fusionBuilderGetContent( 'content', true, initialLoad ),
						contentErrorMarkup        = '',
						contentErrorMarkupWrapper = '',
						contentErrorMarkupClone   = '';

					try {

						if ( ! jQuery( 'body' ).hasClass( 'fusion-builder-library-edit' ) ) {
							content = FusionPageBuilderApp.validateContent( content );
						}

						FusionPageBuilderApp.createBuilderLayout( content );

						FusionPageBuilderEvents.trigger( 'fusion-hide-loader' );

					} catch ( error ) {
						console.log( error );
						FusionPageBuilderApp.fusionBuilderSetContent( 'content', content );
						jQuery( '#fusion_toggle_builder' ).trigger( 'click' );

						contentErrorMarkup = FusionPageBuilderApp.$el.find( '#content-error' );
						contentErrorMarkupWrapper = FusionPageBuilderApp.$el;
						contentErrorMarkupClone = contentErrorMarkup.clone();

						contentErrorMarkup.dialog( {
							dialogClass: 'fusion-builder-dialog',
							autoOpen: false,
							modal: true,
							buttons: {
								OK: function() {
									jQuery( this ).dialog( 'close' );
								}
							},
							close: function() {
								contentErrorMarkupWrapper.append( contentErrorMarkupClone );
							}
						} );

						contentErrorMarkup.dialog( 'open' );
					}

				}, 50 );
			},

			validateContent: function( content ) {
				var contentIsEmpty = '' === content,
					textNodes      = '',
					columns        = [],
					containers     = [],
					shortcodeTags,
					columnwrapped,
					insertionFlag;

				// Throw exception with the fullwidth shortcode.
				if ( -1 !== content.indexOf( '[fullwidth' ) ) {
					throw 'Avada 4.0.3 or earlier fullwidth container used!';
				}

				if ( ! contentIsEmpty ) {

					// Fixes [fusion_text /] instances, which were created in 5.0.1 for empty text blocks.
					content = content.replace( /\[fusion\_text \/\]/g, '[fusion_text][/fusion_text]' ).replace( /\[\/fusion\_text\]\[\/fusion\_text\]/g, '[/fusion_text]' );

					content = content.replace( /\$\$/g, '&#36;&#36;' );
					textNodes = content;

					// Add container if missing.
					textNodes = wp.shortcode.replace( 'fusion_builder_container', textNodes, function() {
						return '@|@';
					} );
					textNodes = wp.shortcode.replace( 'fusion_builder_next_page', textNodes, function() {
						return '@|@';
					} );
					textNodes = textNodes.trim().split( '@|@' );

					_.each( textNodes, function( textNodes ) {
						if ( '' !== textNodes.trim() ) {
							content = content.replace( textNodes, '[fusion_builder_container hundred_percent="no" equal_height_columns="no" menu_anchor="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id="" background_color="" background_image="" background_position="center center" background_repeat="no-repeat" fade="no" background_parallax="none" parallax_speed="0.3" video_mp4="" video_webm="" video_ogv="" video_url="" video_aspect_ratio="16:9" video_loop="yes" video_mute="yes" overlay_color="" overlay_opacity="0.5" video_preview_image="" border_size="" border_color="" border_style="solid" padding_top="" padding_bottom="" padding_left="" padding_right=""][fusion_builder_row]' + textNodes + '[/fusion_builder_row][/fusion_builder_container]' );
						}
					} );

					textNodes = wp.shortcode.replace( 'fusion_builder_container', content, function( tag ) {
						containers.push( tag.content );
					} );

					_.each( containers, function( textNodes ) {

						// Add column if missing.
						textNodes = wp.shortcode.replace( 'fusion_builder_row', textNodes, function( tag ) {
							return tag.content;
						} );

						textNodes = wp.shortcode.replace( 'fusion_builder_column', textNodes, function() {
							return '@|@';
						} );

						textNodes = textNodes.trim().split( '@|@' );
						_.each( textNodes, function( textNodes ) {
							if ( '' !== textNodes.trim() && '[fusion_builder_row][/fusion_builder_row]' !== textNodes.trim() ) {
								columnwrapped = '[fusion_builder_column type="1_1" background_position="left top" background_color="" border_size="" border_color="" border_style="solid" border_position="all" spacing="yes" background_image="" background_repeat="no-repeat" padding="" margin_top="0px" margin_bottom="0px" class="" id="" animation_type="" animation_speed="0.3" animation_direction="left" hide_on_mobile="small-visibility,medium-visibility,large-visibility" center_content="no" last="no" min_height="" hover_type="none" link=""]' + textNodes + '[/fusion_builder_column]';
								content = content.replace( textNodes, columnwrapped );

							}
						} );
					} );

					textNodes = wp.shortcode.replace( 'fusion_builder_column_inner', content, function( tag ) {
						columns.push( tag.content );
					} );
					textNodes = wp.shortcode.replace( 'fusion_builder_column', content, function( tag ) {
						columns.push( tag.content );
					} );

					_.each( columns, function( textNodes ) {

						// Wrap non fusion elements.
						shortcodeTags = fusionAllElements;
						_.each( shortcodeTags, function( shortcode ) {
							if ( 'undefined' === typeof shortcode.generator_only ) {
								textNodes = wp.shortcode.replace( shortcode.shortcode, textNodes, function() {
									return '@|@';
								} );
							}
						} );

						textNodes = textNodes.trim().split( '@|@' );
						_.each( textNodes, function( textNodes ) {
							if ( '' !== textNodes.trim() && '<br />' !== textNodes.trim() ) {
								insertionFlag = '@=%~@';
								if ( '@' === textNodes.slice( -1 ) ) {
									insertionFlag = '#=%~#';
								}
								content = content.replace( textNodes, '[fusion_text]' + textNodes.slice( 0, -1 ) + insertionFlag + textNodes.slice( -1 ) + '[/fusion_text]' );
							}
						} );
					} );
					content = content.replace( /@=%~@/g, '' ).replace( /#=%~#/g, '' );

					// Check for once deactivated elements in text blocks that are active again.
					content = wp.shortcode.replace( 'fusion_text', content, function( tag ) {
						shortcodeTags = fusionAllElements;
						textNodes = tag.content;
						_.each( shortcodeTags, function( shortcode ) {
							if ( 'undefined' === typeof shortcode.generator_only ) {
								textNodes = wp.shortcode.replace( shortcode.shortcode, textNodes, function() {
									return '|';
								} );
							}
						} );
						if ( ! textNodes.replace( /\|/g, '' ).length ) {
							return tag.content;
						}
					} );
				}

				function replaceDollars() {
					return '$$';
				}

				content = content.replace( /&#36;&#36;/g, replaceDollars );

				return content;
			},

			validateLibraryContent: function( content ) {
				var contentIsEmpty = '' === content,
					openContainer  = '[fusion_builder_container hundred_percent="no" equal_height_columns="no" menu_anchor="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id="" background_color="" background_image="" background_position="center center" background_repeat="no-repeat" fade="no" background_parallax="none" parallax_speed="0.3" video_mp4="" video_webm="" video_ogv="" video_url="" video_aspect_ratio="16:9" video_loop="yes" video_mute="yes" overlay_color="" overlay_opacity="0.5" video_preview_image="" border_size="" border_color="" border_style="solid" padding_top="" padding_bottom="" padding_left="" padding_right=""][fusion_builder_row]',
					closeContainer = '[/fusion_builder_row][/fusion_builder_container]',
					openColumn     = '[fusion_builder_column type="1_1" background_position="left top" background_color="" border_size="" border_color="" border_style="solid" border_position="all" spacing="yes" background_image="" background_repeat="no-repeat" padding="" margin_top="0px" margin_bottom="0px" class="" id="" animation_type="" animation_speed="0.3" animation_direction="left" hide_on_mobile="small-visibility,medium-visibility,large-visibility" center_content="no" last="no" min_height="" hover_type="none" link=""]',
					closeColumn    = '[/fusion_builder_column]';

				if ( ! contentIsEmpty ) {

					// Editing element
					if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-elements' ) ) {

						content = openContainer + openColumn + content + closeColumn + closeContainer;

					} else if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-columns' ) ) {

						content = openContainer + content + closeContainer;
					}
				}

				function replaceDollars() {
					return '$$';
				}

				content = content.replace( /&#36;&#36;/g, replaceDollars );

				return content;
			},

			clearBuilderLayout: function( blankPageLayout ) {

				// Remove blank page layout
				this.$el.find( '.fusion-builder-blank-page-content' ).each( function() {
					var $that = $( this ),
						thisView = FusionPageBuilderViewManager.getView( $that.data( 'cid' ) );

					if ( 'undefined' !== typeof thisView ) {
						thisView.removeBlankPageHelper();
					}
				} );

				// Remove all containers
				this.$el.find( '.fusion-builder-section-content' ).each( function() {
					var $that = $( this ),
						thisView = FusionPageBuilderViewManager.getView( $that.data( 'cid' ) );

					if ( 'undefined' !== typeof thisView ) {
						thisView.removeContainer();
					}
				} );

				// Create blank page layout
				if ( blankPageLayout ) {

					if ( true === this.blankPage ) {
						if ( ! this.$el.find( '.fusion-builder-blank-page-content' ).length ) {
							this.createBuilderLayout( '[fusion_builder_blank_page][/fusion_builder_blank_page]' );
						}

						this.blankPage = false;
					}

				}

			},

			convertGalleryElement: function( content ) {
				var regExp      = window.wp.shortcode.regexp( 'fusion_gallery' ),
					innerRegExp = this.regExpShortcode( 'fusion_gallery' ),
					matches     = content.match( regExp ),
					newContent  = content,
					fetchIds    = [];

				_.each( matches, function( shortcode ) {
					var shortcodeElement    = shortcode.match( innerRegExp ),
						shortcodeAttributes = '' !== shortcodeElement[ 3 ] ? window.wp.shortcode.attrs( shortcodeElement[ 3 ] ) : '',
						children     = '',
						newShortcode = '',
						ids;

					// Check for the old format shortcode
					if ( 'undefined' !== typeof shortcodeAttributes.named.image_ids ) {
						ids = shortcodeAttributes.named.image_ids.split( ',' );

						// Add new children shortcodes
						_.each( ids, function( id ) {
							children += '[fusion_gallery_image image="" image_id="' + id + '" /]';
							fetchIds.push( id );
						} );

						// Add children shortcodes, remove image_ids attribute.
						newShortcode = shortcode.replace( '][/fusion_gallery]', ']' + children + '[/fusion_gallery]' ).replace( '/]', ']' + children + '[/fusion_gallery]' ).replace( 'image_ids="' + shortcodeAttributes.named.image_ids + '" ', '' );

						// Replace the old shortcode with the new one
						newContent = newContent.replace( shortcode, newShortcode );
					}
				} );

				// Fetch attachment data
				wp.media.query( { post__in: fetchIds, posts_per_page: fetchIds.length } ).more();

				return newContent;
			},

			createBuilderLayout: function( content ) {
				if ( jQuery( 'body' ).hasClass( 'fusion-builder-library-edit' ) ) {
					content = FusionPageBuilderApp.validateLibraryContent( content );
				}

				content = this.convertGalleryElement( content );

				this.shortcodesToBuilder( content );

				if ( jQuery( 'body' ).hasClass( 'fusion-builder-library-edit' ) ) {
					this.libraryBuilderToShortcodes();
				} else {
					this.builderToShortcodes();
				}
			},

			/**
			 * Convert shortcodes for the builder.
			 *
			 * @since 2.0.0
			 * @param {string} content - The content.
			 * @param {number} parentCID - The parent CID.
			 * @param {string} targetEl - If we want to add in relation to a specific element.
			 * @param {string} targetPosition - Whether we want to be before or after specific element.
			 * @return {string|null}
			 */
			shortcodesToBuilder: function( content, parentCID, targetEl, targetPosition ) {
				var thisEl,
					regExp,
					innerRegExp,
					matches,
					shortcodeTags;

				// Show blank page layout
				if ( '' === content && ! this.$el.find( '.fusion-builder-blank-page-content' ).length ) {
					this.createBuilderLayout( '[fusion_builder_blank_page][/fusion_builder_blank_page]' );

					return;
				}

				thisEl        = this;
				shortcodeTags = _.keys( fusionAllElements ).join( '|' );
				regExp        = window.wp.shortcode.regexp( shortcodeTags );
				innerRegExp   = this.regExpShortcode( shortcodeTags );
				matches       = content.match( regExp );

				_.each( matches, function( shortcode ) {

					var shortcodeElement    = shortcode.match( innerRegExp ),
						shortcodeName       = shortcodeElement[ 2 ],
						shortcodeAttributes = '' !== shortcodeElement[ 3 ] ? window.wp.shortcode.attrs( shortcodeElement[ 3 ] ) : '',
						shortcodeContent    = 'undefined' !== typeof shortcodeElement[ 5 ] ? shortcodeElement[ 5 ] : '',
						elementCID          = FusionPageBuilderViewManager.generateCid(),
						prefixedAttributes  = { params: ( {} ) },
						elementSettings,
						key,
						prefixedKey,
						dependencyOption,
						dependencyOptionValue,
						elementContent,
						alpha,
						paging,
						values,
						buttonPrefix,
						radiaDirectionsNew,

						// Check for shortcodes inside shortcode content
						shortcodesInContent = 'undefined' !== typeof shortcodeContent && '' !== shortcodeContent && shortcodeContent.match( regExp ),

						// Check if shortcode allows generator
						allowGenerator = 'undefined' !== typeof fusionAllElements[ shortcodeName ].allow_generator ? fusionAllElements[ shortcodeName ].allow_generator : '';

					elementSettings = {
						type: shortcodeName,
						element_type: shortcodeName,
						cid: elementCID,
						created: 'manually',
						multi: '',
						params: {},
						allow_generator: allowGenerator
					};

					if ( 'fusion_builder_container' !== shortcodeName || 'fusion_builder_next_page' !== shortcodeName ) {
						elementSettings.parent = parentCID;
					}

					if ( 'fusion_builder_container' !== shortcodeName && 'fusion_builder_row' !== shortcodeName && 'fusion_builder_column' !== shortcodeName && 'fusion_builder_column_inner' !== shortcodeName && 'fusion_builder_row_inner' !== shortcodeName && 'fusion_builder_blank_page' !== shortcodeName && 'fusion_builder_next_page' !== shortcodeName ) {

						if ( -1 !== shortcodeName.indexOf( 'fusion_' ) ||
							-1 !== shortcodeName.indexOf( 'layerslider' ) ||
							-1 !== shortcodeName.indexOf( 'rev_slider' ) ||
							'undefined' !== typeof fusionAllElements[ shortcodeName ] ) {
							elementSettings.type = 'element';
						}
					}

					if ( _.isObject( shortcodeAttributes.named ) ) {

						// If no blend mode is defined, check if we should set to overlay.
						if ( ( 'fusion_builder_container' === shortcodeName || 'fusion_builder_column' === shortcodeName || 'fusion_builder_column_inner' === shortcodeName ) && 'undefined' === typeof shortcodeAttributes.named.background_blend_mode ) {
							backgroundColor = shortcodeAttributes.named.background_color;
							videoBg         = 'fusion_builder_container' === shortcodeName && 'undefined' !== typeof shortcodeAttributes.named.video_bg ? shortcodeAttributes.named.video_bg : '';

							if ( 'fusion_builder_container' === shortcodeName && ( 'undefined' === typeof backgroundColor || '' === backgroundColor ) ) {
								backgroundColor = fusionAllElements[ shortcodeName ].defaults.background_color;
							}
							if ( '' !== backgroundColor  ) {
								alphaBackgroundColor = jQuery.Color( backgroundColor ).alpha();
								if ( 1 > alphaBackgroundColor && 0 !== alphaBackgroundColor && ( '' !== shortcodeAttributes.named.background_image || '' !== videoBg ) ) {
									shortcodeAttributes.named.background_blend_mode = 'overlay';
								}
							}
						}

						// Correct radial direction params.
						if ( ( 'fusion_builder_container' === shortcodeName || 'fusion_builder_column' === shortcodeName || 'fusion_builder_column_inner' === shortcodeName ) && 'undefined' !== typeof shortcodeAttributes.named.radial_direction ) {
							radiaDirectionsNew   = { 'bottom': 'center bottom', 'bottom center': 'center bottom', 'left': 'left center', 'right': 'right center', 'top': 'center top', 'center': 'center center', 'center left': 'left center' };

							if ( shortcodeAttributes.named.radial_direction in radiaDirectionsNew ) {
								shortcodeAttributes.named.radial_direction = radiaDirectionsNew[ shortcodeAttributes.named.radial_direction ];
							}
						}

						if ( 'fusion_fontawesome' === shortcodeName ) {
							if ( 'undefined' === typeof shortcodeAttributes.named.iconcolor_hover && 'string' === typeof shortcodeAttributes.named.iconcolor ) {
								shortcodeAttributes.named.iconcolor_hover = shortcodeAttributes.named.iconcolor;
							}
							if ( 'undefined' === typeof shortcodeAttributes.named.circlecolor_hover && 'string' === typeof shortcodeAttributes.named.circlecolor ) {
								shortcodeAttributes.named.circlecolor_hover = shortcodeAttributes.named.circlecolor;
							}
							if ( 'undefined' === typeof shortcodeAttributes.named.circlebordercolor_hover && 'string' === typeof shortcodeAttributes.named.circlebordercolor ) {
								shortcodeAttributes.named.circlebordercolor_hover = shortcodeAttributes.named.circlebordercolor;
							}
						}

						if ( 'fusion_countdown' === shortcodeName ) {

							// Correct old combined border radius setting.
							if ( 'undefined' === typeof shortcodeAttributes.named.counter_border_radius && 'string' === typeof shortcodeAttributes.named.border_radius ) {
								shortcodeAttributes.named.counter_border_radius = shortcodeAttributes.named.border_radius;
							}

							// Correct the label text color.
							if ( 'undefined' === typeof shortcodeAttributes.named.label_color && 'string' === typeof shortcodeAttributes.named.counter_text_color ) {
								shortcodeAttributes.named.label_color = shortcodeAttributes.named.counter_text_color;
							}
						}

						for ( key in shortcodeAttributes.named ) {

							prefixedKey = key;
							if ( ( 'fusion_builder_column' === shortcodeName || 'fusion_builder_column_inner' === shortcodeName ) && 'type' === prefixedKey ) {
								prefixedKey = 'layout';

								prefixedAttributes[ prefixedKey ] = shortcodeAttributes.named[ key ];
							}

							prefixedAttributes.params[ prefixedKey ] = shortcodeAttributes.named[ key ];
							if ( 'fusion_products_slider' === shortcodeName && 'cat_slug' === key ) {
								prefixedAttributes.params.cat_slug = shortcodeAttributes.named[ key ].replace( /\|/g, ',' );
							}
							if ( 'gradient_colors' === key ) {
								delete prefixedAttributes.params[ prefixedKey ];
								if ( -1 !== shortcodeAttributes.named[ key ].indexOf( '|' ) ) {
									prefixedAttributes.params.button_gradient_top_color = shortcodeAttributes.named[ key ].split( '|' )[ 0 ].replace( 'transparent', 'rgba(255,255,255,0)' );
									prefixedAttributes.params.button_gradient_bottom_color = shortcodeAttributes.named[ key ].split( '|' )[ 1 ] ? shortcodeAttributes.named[ key ].split( '|' )[ 1 ].replace( 'transparent', 'rgba(255,255,255,0)' ) : shortcodeAttributes.named[ key ].split( '|' )[ 0 ].replace( 'transparent', 'rgba(255,255,255,0)' );
								} else {
									prefixedAttributes.params.button_gradient_bottom_color = prefixedAttributes.params.button_gradient_top_color = shortcodeAttributes.named[ key ].replace( 'transparent', 'rgba(255,255,255,0)' );
								}
							}
							if ( 'gradient_hover_colors' === key ) {
								delete prefixedAttributes.params[ prefixedKey ];
								if ( -1 !== shortcodeAttributes.named[ key ].indexOf( '|' ) ) {
									prefixedAttributes.params.button_gradient_top_color_hover = shortcodeAttributes.named[ key ].split( '|' )[ 0 ].replace( 'transparent', 'rgba(255,255,255,0)' );
									prefixedAttributes.params.button_gradient_bottom_color_hover = shortcodeAttributes.named[ key ].split( '|' )[ 1 ] ? shortcodeAttributes.named[ key ].split( '|' )[ 1 ].replace( 'transparent', 'rgba(255,255,255,0)' ) : shortcodeAttributes.named[ key ].split( '|' )[ 0 ].replace( 'transparent', 'rgba(255,255,255,0)' );
								} else {
									prefixedAttributes.params.button_gradient_bottom_color_hover = prefixedAttributes.params.button_gradient_top_color_hover = shortcodeAttributes.named[ key ].replace( 'transparent', 'rgba(255,255,255,0)' );
								}
							}
							if ( 'overlay_color' === key && '' !== shortcodeAttributes.named[ key ] && 'fusion_builder_container' === shortcodeName ) {
								delete prefixedAttributes.params[ prefixedKey ];
								alpha = ( 'undefined' !== typeof shortcodeAttributes.named.overlay_opacity ) ? shortcodeAttributes.named.overlay_opacity : 1;
								prefixedAttributes.params.background_color = jQuery.Color( shortcodeAttributes.named[ key ] ).alpha( alpha ).toRgbaString();
							}
							if ( 'overlay_opacity' === key ) {
								delete prefixedAttributes.params[ prefixedKey ];
							}
							if ( 'scrolling' === key && 'fusion_blog' === shortcodeName ) {
								delete prefixedAttributes.params.paging;
								paging = ( 'undefined' !== typeof shortcodeAttributes.named.paging ) ? shortcodeAttributes.named.paging : '';
								if ( 'no' === paging && 'pagination' === shortcodeAttributes.named.scrolling ) {
									prefixedAttributes.params.scrolling = 'no';
								}
							}

							// The grid-with-text layout was removed in Avada 5.2, so layout has to
							// be converted to grid. And boxed_layout was replaced by new text_layout.
							if ( 'fusion_portfolio' === shortcodeName ) {
								if ( 'layout' === key ) {
									if ( 'grid' === shortcodeAttributes.named[ key ] && shortcodeAttributes.named.hasOwnProperty( 'boxed_text' ) ) {
										shortcodeAttributes.named.boxed_text = 'no_text';
									} else if ( 'grid-with-text' === shortcodeAttributes.named[ key ] ) {
										prefixedAttributes.params[ key ] = 'grid';
									}
								}

								if ( 'boxed_text' === key ) {
									prefixedAttributes.params.text_layout = shortcodeAttributes.named[ key ];
									delete prefixedAttributes.params[ key ];
								}

								if ( 'content_length' === key && 'full-content' === shortcodeAttributes.named[ key ] ) {
									prefixedAttributes.params[ key ] = 'full_content';
								}

							}

							// Make sure the background hover color is set to border color, if it does not exist already.
							if ( 'fusion_pricing_table' === shortcodeName ) {
								if ( 'backgroundcolor' === key && ! shortcodeAttributes.named.hasOwnProperty( 'background_color_hover' ) ) {
									prefixedAttributes.params.background_color_hover = shortcodeAttributes.named.bordercolor;
								}
							}

							if ( 'padding' === key && ( 'fusion_widget_area' === shortcodeName || 'fusion_builder_column' === shortcodeName || 'fusion_builder_column_inner' === shortcodeName ) ) {
								values = shortcodeAttributes.named[ key ].split( ' ' );

								if ( 1 === values.length ) {
									prefixedAttributes.params.padding_top = values[ 0 ];
									prefixedAttributes.params.padding_right = values[ 0 ];
									prefixedAttributes.params.padding_bottom = values[ 0 ];
									prefixedAttributes.params.padding_left = values[ 0 ];
								}

								if ( 2 === values.length ) {
									prefixedAttributes.params.padding_top = values[ 0 ];
									prefixedAttributes.params.padding_right = values[ 1 ];
									prefixedAttributes.params.padding_bottom = values[ 0 ];
									prefixedAttributes.params.padding_left = values[ 1 ];
								}

								if ( 3 === values.length ) {
									prefixedAttributes.params.padding_top = values[ 0 ];
									prefixedAttributes.params.padding_right = values[ 1 ];
									prefixedAttributes.params.padding_bottom = values[ 2 ];
									prefixedAttributes.params.padding_left = values[ 1 ];
								}

								if ( 4 === values.length ) {
									prefixedAttributes.params.padding_top = values[ 0 ];
									prefixedAttributes.params.padding_right = values[ 1 ];
									prefixedAttributes.params.padding_bottom = values[ 2 ];
									prefixedAttributes.params.padding_left = values[ 3 ];
								}

								delete prefixedAttributes.params[ key ];
							}
						}

						// Ensures backwards compatibility for the table style in table element.
						if ( 'fusion_table' === shortcodeName && 'undefined' === typeof shortcodeAttributes.named.fusion_table_type ) {
							if ( '1' === shortcodeContent.charAt( 18 ) || '2' === shortcodeContent.charAt( 18 ) ) {
								prefixedAttributes.params.fusion_table_type = shortcodeContent.charAt( 18 );
							}
						}

						// Fix old values of image_width in content boxes and flip boxes and children.
						if ( 'fusion_content_boxes' === shortcodeName || 'fusion_flip_boxes' === shortcodeName ) {
							if ( 'undefined' !== typeof shortcodeAttributes.named.image_width ) {
								prefixedAttributes.params.image_max_width = shortcodeAttributes.named.image_width;
							}

							shortcodeContent = shortcodeContent.replace( /image_width/g, 'image_max_width' );
						}

						if ( 'fusion_button' === shortcodeName || 'fusion_tagline_box' === shortcodeName ) {
							buttonPrefix = 'fusion_tagline_box' === shortcodeName ? 'button_' : '';

							// Ensures backwards compatibility for button shape.
							if ( 'undefined' !== typeof shortcodeAttributes.named[ buttonPrefix + 'shape' ] ) {
								if ( 'square' === shortcodeAttributes.named[ buttonPrefix + 'shape' ] ) {
									prefixedAttributes.params[ buttonPrefix + 'border_radius' ] = '0';
								} else if ( 'round' === shortcodeAttributes.named[ buttonPrefix + 'shape' ] ) {
									prefixedAttributes.params[ buttonPrefix + 'border_radius' ] = '2';

									if ( '3d' === shortcodeAttributes.named.type ) {
										prefixedAttributes.params[ buttonPrefix + 'border_radius' ] = '4';
									}
								} else if ( 'pill' === shortcodeAttributes.named[ buttonPrefix + 'shape' ] ) {
									prefixedAttributes.params[ buttonPrefix + 'border_radius' ] = '25';
								} else if ( '' === shortcodeAttributes.named[ buttonPrefix + 'shape' ] ) {
									prefixedAttributes.params[ buttonPrefix + 'border_radius' ] = '';
								}

								delete prefixedAttributes.params[ buttonPrefix + 'shape' ];
							}
						}

						if ( 'fusion_button' === shortcodeName ) {
							// Ensures backwards compatibility for button border color.
							if ( 'undefined' === typeof shortcodeAttributes.named.border_color && 'undefined' !== typeof shortcodeAttributes.named.accent_color && '' !== shortcodeAttributes.named.accent_color ) {
								prefixedAttributes.params.border_color = shortcodeAttributes.named.accent_color;
							}

							if ( 'undefined' === typeof shortcodeAttributes.named.border_hover_color && 'undefined' !== typeof shortcodeAttributes.named.accent_hover_color && '' !== shortcodeAttributes.named.accent_hover_color ) {
								prefixedAttributes.params.border_hover_color = shortcodeAttributes.named.accent_hover_color;
							}
						}

						// Ensures backwards compatibility for register note in user registration element.
						if ( 'fusion_register' === shortcodeName && 'undefined' === typeof shortcodeAttributes.named.register_note ) {
							prefixedAttributes.params.register_note = fusionBuilderText.user_login_register_note;
						}

						elementSettings = _.extend( elementSettings, prefixedAttributes );
					}

					if ( ! shortcodesInContent && 'fusion_builder_column' !== shortcodeName ) {
						elementSettings.params.element_content = shortcodeContent;
					}

					// Compare shortcode name to multi elements object / array
					if ( shortcodeName in fusionMultiElements ) {
						elementSettings.multi = 'multi_element_parent';
					}

					// Set content for elements with dependency options
					if ( 'undefined' !== typeof fusionAllElements[ shortcodeName ].option_dependency ) {

						dependencyOption      = fusionAllElements[ shortcodeName ].option_dependency;
						dependencyOptionValue = prefixedAttributes.params[ dependencyOption ];
						elementContent        = prefixedAttributes.params.element_content;
						prefixedAttributes.params[ dependencyOptionValue ] = elementContent;
					}

					if ( shortcodesInContent ) {
						if ( 'fusion_builder_container' !== shortcodeName && 'fusion_builder_row' !== shortcodeName && 'fusion_builder_row_inner' !== shortcodeName && 'fusion_builder_column' !== shortcodeName && 'fusion_builder_column_inner' !== shortcodeName && 'fusion_builder_next_page' !== shortcodeName ) {
							elementSettings.params.element_content = shortcodeContent;
						}
					}

					if ( 'undefined' !== typeof targetEl && targetEl ) {
						elementSettings.targetElement = targetEl;
					}
					if ( 'undefined' !== typeof targetPosition && targetPosition ) {
						elementSettings.targetElementPosition = targetPosition;
					}

					thisEl.collection.add( [ elementSettings ] );

					if ( shortcodesInContent ) {

						if ( 'fusion_builder_container' === shortcodeName || 'fusion_builder_row' === shortcodeName || 'fusion_builder_row_inner' === shortcodeName || 'fusion_builder_column' === shortcodeName || 'fusion_builder_column_inner' === shortcodeName ) {
							thisEl.shortcodesToBuilder( shortcodeContent, elementCID );
						}
					}
				} );
			},

			addBuilderElement: function( element ) {

				var view,
					viewSettings = {
						model: element,
						collection: FusionPageBuilderElements
					},
					parentModel,
					elementType,
					previewView;

				switch ( element.get( 'type' ) ) {

				case 'fusion_builder_blank_page':

					view = new FusionPageBuilder.BlankPageView( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					if ( ! _.isUndefined( element.get( 'view' ) ) ) {
						element.get( 'view' ).$el.after( view.render().el );

					} else {
						this.$el.find( '#fusion_builder_container' ).append( view.render().el );
					}

					break;

				case 'fusion_builder_container':

					// Check custom container position
					if ( '' !== FusionPageBuilderApp.targetContainerCID ) {
						element.attributes.view = FusionPageBuilderViewManager.getView( FusionPageBuilderApp.targetContainerCID );

						FusionPageBuilderApp.targetContainerCID = '';
					}

					view = new FusionPageBuilder.ContainerView( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					if ( ! _.isUndefined( element.get( 'view' ) ) ) {
						if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
							element.get( 'view' ).$el.after( view.render().el );
						} else {
							element.get( 'view' ).$el.before( view.render().el );
						}

					} else {
						this.$el.find( '#fusion_builder_container' ).append( view.render().el );
						this.$el.find( '.fusion_builder_blank_page' ).remove();
					}

					// Add row if needed
					if ( 'manually' !== element.get( 'created' ) ) {
						view.addRow();
					}

					// Check if container is toggled
					if ( ! _.isUndefined( element.attributes.params.admin_toggled ) && 'no' === element.attributes.params.admin_toggled || _.isUndefined( element.attributes.params.admin_toggled ) ) {
						FusionPageBuilderApp.toggledContainers = false;
						$( '.fusion-builder-layout-buttons-toggle-containers' ).find( 'span' ).addClass( 'dashicons-arrow-up' ).removeClass( 'dashicons-arrow-down' );
					}

					break;

				case 'fusion_builder_row':

					view = new FusionPageBuilder.RowView( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					if ( FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-section-content' ).length ) {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-section-content' ).append( view.render().el );

					} else {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '> .fusion-builder-add-element' ).hide().end().append( view.render().el );
					}

					// Add parent view to inner rows that have been converted from shortcodes
					if ( 'manually' === element.get( 'created' ) && 'row_inner' === element.get( 'element_type' ) ) {
						element.set( 'view', FusionPageBuilderViewManager.getView( element.get( 'parent' ) ), { silent: true } );
					}

					break;

				case 'fusion_builder_row_inner':

					FusionPageBuilderEvents.trigger( 'fusion-remove-modal-view' );

					view = new FusionPageBuilder.InnerRowView( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					// TODO - Check appendAfter.
					if ( ! _.isUndefined( element.get( 'appendAfter' ) ) ) {
						element.get( 'appendAfter' ).after( view.render().el );
						element.unset( 'appendAfter' );

					} else if ( FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-section-content' ).length ) {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-section-content' ).append( view.render().el );

					} else if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
						if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
							element.get( 'targetElement' ).after( view.render().el );
						} else {
							element.get( 'targetElement' ).before( view.render().el );
						}
					} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '> .fusion-builder-add-element' ).before( view.render().el );
					} else {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '> .fusion-builder-column-controls' ).after( view.render().el );
					}

					// Add parent view to inner rows that have been converted from shortcodes
					if ( 'manually' === element.get( 'created' ) && 'row_inner' === element.get( 'element_type' ) ) {
						element.set( 'view', FusionPageBuilderViewManager.getView( element.get( 'parent' ) ), { silent: true } );
					}

					break;

				case 'fusion_builder_column':

					if ( element.get( 'layout' ) ) {
						viewSettings.className = 'fusion-builder-column fusion-builder-column-outer fusion-builder-column-' + element.get( 'layout' );
						view = new FusionPageBuilder.ColumnView( viewSettings );

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
								FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-row-container' ).append( view.render().el );
							} else {
								FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-row-container .fusion-builder-empty-section' ).after( view.render().el );
							}
							element.unset( 'from' );
						}
					}
					break;

				case 'fusion_builder_column_inner':

					viewSettings.className = 'fusion-builder-column fusion-builder-column-inner fusion-builder-column-' + element.get( 'layout' );

					view = new FusionPageBuilder.NestedColumnView( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
						if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
							element.get( 'targetElement' ).after( view.render().el );
						} else {
							element.get( 'targetElement' ).before( view.render().el );
						}
					} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-row-container-inner' ).append( view.render().el );
					} else {
						FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-row-container-inner' ).prepend( view.render().el );
					}
					break;

				case 'element':

					viewSettings.attributes = {
						'data-cid': element.get( 'cid' )
					};

					// Multi element child
					if ( 'undefined' !== typeof element.get( 'multi' ) && 'multi_element_child' === element.get( 'multi' ) ) {

						view = new FusionPageBuilder.MultiElementSortableChild( viewSettings );

						element.targetElement = view.$el;

						element.attributes.view.child_views.push( view );

						FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

						if ( ! _.isUndefined( element.get( 'targetElement' ) ) ) {
							if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
								element.get( 'targetElement' ).after( view.render().el );
							} else {
								element.get( 'targetElement' ).before( view.render().el );
							}

						} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
							FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-sortable-options' ).append( view.render().el );
						} else {
							FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-sortable-options' ).prepend( view.render().el );
						}

						// This child was cloned
						if ( ! _.isUndefined( element.get( 'titleLabel' ) ) ) {
							if ( ! _.isUndefined( element.get( 'cloned' ) ) ) {
								view.$el.find( '.multi-element-child-name' ).html( element.get( 'titleLabel' ) );
							}
							element.unset( 'cloned' );
						}

						// Standard element
					} else {

						FusionPageBuilderEvents.trigger( 'fusion-remove-modal-view' );

						view = new FusionPageBuilder.ElementView( viewSettings );

						// Get element parent
						parentModel = this.collection.find( function( model ) {
							return model.get( 'cid' ) === element.get( 'parent' );
						} );

						// Add element builder view to proper column
						if ( 'undefined' !== typeof parentModel && 'fusion_builder_column_inner' === parentModel.get( 'type' ) ) {

							if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
								if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
									element.get( 'targetElement' ).after( view.render().el );
								} else {
									element.get( 'targetElement' ).before( view.render().el );
								}
							} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) {
								FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-add-element' ).before( view.render().el );
							} else {
								FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.prepend( view.render().el );
							}

						} else if ( ! _.isUndefined( element.get( 'targetElement' ) ) && 'undefined' === typeof element.get( 'from' ) ) {
							if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'after' === element.get( 'targetElementPosition' ) ) {
								element.get( 'targetElement' ).after( view.render().el );
							} else {
								element.get( 'targetElement' ).before( view.render().el );
							}
						} else if ( 'undefined' === typeof element.get( 'targetElementPosition' ) || 'end' === element.get( 'targetElementPosition' ) ) { // TO-DO: Check why this doesn't work. Will be wrong parent no doubt.
							FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.find( '.fusion-builder-add-element:not(.fusion-builder-column-inner .fusion-builder-add-element)' ).before( view.render().el );
						} else {
							FusionPageBuilderViewManager.getView( element.get( 'parent' ) ).$el.prepend( view.render().el );
						}

						FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

						// Check if element was added manually
						if ( 'manually' === element.get( 'added' ) ) {

							viewSettings.attributes = {
								'data-modal_view': 'element_settings'
							};

							view = new FusionPageBuilder.ModalView( viewSettings );

							$( 'body' ).append( view.render().el );

							// Generate element preview
						} else {

							elementType = element.get( 'element_type' );

							if ( 'undefined' !== typeof fusionAllElements[ elementType ].preview ) {

								previewView = new FusionPageBuilder.ElementPreviewView( viewSettings );
								view.$el.find( '.fusion-builder-module-preview' ).append( previewView.render().el );
							}
						}
					}

					break;

				case 'generated_element':

					FusionPageBuilderEvents.trigger( 'fusion-remove-modal-view' );

					// Ignore modals for columns inserted with generator
					if ( 'fusion_builder_column_inner' !== element.get( 'element_type' ) && 'fusion_builder_column' !== element.get( 'element_type' ) ) {

						viewSettings.attributes = {
							'data-modal_view': 'element_settings'
						};
						view = new FusionPageBuilder.ModalView( viewSettings );
						$( 'body' ).append( view.render().el );

					}

					break;

				case 'fusion_builder_next_page':
					view = new FusionPageBuilder.NextPage( viewSettings );

					FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

					if ( ! _.isUndefined( element.get( 'appendAfter' ) ) ) {

						// TODO - Check appendAfter.
						if ( ! element.get( 'appendAfter' ).next().next().hasClass( 'fusion-builder-next-page' ) ) {
							element.get( 'appendAfter' ).after( view.render().el );
						}
					} else {
						$( '.fusion_builder_container:last-child' ).after( view.render().el );
					}

					break;

				}
			},

			regExpShortcode: _.memoize( function( tag ) {
				return new RegExp( '\\[(\\[?)(' + tag + ')(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*(?:\\[(?!\\/\\2\\])[^\\[]*)*)(\\[\\/\\2\\]))?)(\\]?)' );
			} ),

			findShortcodeMatches: function( content, match ) {

				var shortcodeMatches,
					shortcodeRegExp,
					shortcodeInnerRegExp;

				if ( _.isObject( content ) ) {
					content = content.value;
				}

				shortcodeMatches     = '';
				content              = 'undefined' !== typeof content ? content : '';
				shortcodeRegExp      = window.wp.shortcode.regexp( match );
				shortcodeInnerRegExp = new RegExp( '\\[(\\[?)(' + match + ')(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*(?:\\[(?!\\/\\2\\])[^\\[]*)*)(\\[\\/\\2\\]))?)(\\]?)' );

				if ( 'undefined' !== typeof content && '' !== content ) {
					shortcodeMatches = content.match( shortcodeRegExp );
				}

				return shortcodeMatches;
			},

			libraryBuilderToShortcodes: function() {
				var shortcode = '',
					cid,
					view;

				// Editing element
				if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-elements' ) ) {
					if ( jQuery( '.fusion-builder-column-outer .fusion_builder_row_inner' ).length ) {
						cid = jQuery( '.fusion-builder-column-outer .fusion_builder_row_inner' ).data( 'cid' );
						view  = FusionPageBuilderViewManager.getView( cid );
						shortcode = view.getInnerRowContent();

					} else if ( jQuery( '.fusion_module_block' ).length ) {
						shortcode = FusionPageBuilderApp.generateElementShortcode( jQuery( '.fusion_module_block' ), false );
					}

				// Editing column.
				} else if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-columns' ) ) {
					if ( jQuery( '.fusion-builder-column-outer' ).length ) {
						cid = jQuery( '.fusion-builder-column-outer' ).data( 'cid' );
						view  = FusionPageBuilderViewManager.getView( cid );
						shortcode = view.getColumnContent( jQuery( '.fusion-builder-column-outer' ) );
					}

				// Editing container
				} else if ( jQuery( 'body' ).hasClass( 'fusion-element-post-type-sections' ) ) {
					if ( jQuery( '.fusion-builder-section-content' ).length ) {
						cid = jQuery( '.fusion-builder-section-content.fusion-builder-data-cid' ).data( 'cid' );
						view  = FusionPageBuilderViewManager.getView( cid );
						shortcode = view.getContainerContent();
					}
				}

				setTimeout( function() {
					FusionPageBuilderApp.fusionBuilderSetContent( 'content', shortcode );
					FusionPageBuilderEvents.trigger( 'fusion-save-history-state' );
				}, 500 );
			},

			builderToShortcodes: function() {

				var shortcode = '',
					thisEl    = this;

				if ( jQuery( 'body' ).hasClass( 'fusion-builder-library-edit' ) ) {
					this.libraryBuilderToShortcodes();

				} else if ( 'undefined' !== this.pauseBuilder && ! this.pauseBuilder ) {

					this.$el.find( '.fusion_builder_container' ).each( function() {

						var $thisContainer = $( this ).find( '.fusion-builder-section-content' );

						shortcode += thisEl.generateElementShortcode( $( this ), true );

						$thisContainer.find( '.fusion_builder_row' ).each( function() {

							var $thisRow = $( this );

							shortcode += '[fusion_builder_row]';

							$thisRow.find( '.fusion-builder-column-outer' ).each( function() {
								var $thisColumn = $( this ),
									columnCID   = $thisColumn.data( 'cid' ),
									columnView  = FusionPageBuilderViewManager.getView( columnCID );

								shortcode += columnView.getColumnContent( $thisColumn );
							} );
							shortcode += '[/fusion_builder_row]';
						} );

						shortcode += '[/fusion_builder_container]';

						// Check for next page shortcode
						if ( $( this ).next().hasClass( 'fusion-builder-next-page' ) ) {
							shortcode += '[fusion_builder_next_page]';
						}

					} );

					setTimeout( function() {

						FusionPageBuilderApp.fusionBuilderSetContent( 'content', shortcode );
						FusionPageBuilderEvents.trigger( 'fusion-save-history-state' );
						FusionPageBuilderApp.setGoogleFonts( shortcode );
					}, 500 );
				}
			},

			/**
			 * Checks page content for font dependencies.
			 *
			 * @since 2.0.0
			 * @return {Object}
			 */
			setGoogleFonts: function( content ) {
				var self        = this,
					googleFonts = {},
					$input      = jQuery( '#fusion-google-fonts-field' ),
					savedData   = $input.val();

				if ( savedData && '' !== savedData ) {
					try {
						savedData = JSON.parse( savedData );
					} catch ( error ) {
						console.log( error );
					}
				}

				googleFonts = this.setElementFonts( content, googleFonts );
				googleFonts = this.setInlineFonts( content, googleFonts );

				if ( 'object' === typeof savedData ) {
					_.each( savedData, function( fontData, fontFamily ) {
						_.each( fontData, function( values, key ) {
							savedData[ fontFamily ][ key ] = _.values( values );
						} );
					} );

					// We have existing values and existing value is not the same as new.
					if ( ! _.isEqual( savedData, googleFonts ) ) {

						if ( _.isEmpty( googleFonts ) ) {
							googleFonts = '';
						}
						savedData = googleFonts; // eslint-disable-line camelcase
					}
				} else if ( ! _.isEmpty( googleFonts ) ) {

					// We do not have existing values and we do have fonts now.
					savedData = googleFonts; // eslint-disable-line camelcase
				}

				// Set the json encoded value to text area.
				$input.val( JSON.stringify( savedData ) );
			},

			/**
			 * Checks page content for element font families.
			 *
			 * @since 2.0.0
			 * @param object googleFonts
			 * @return {Object}
			 */
			setElementFonts: function( postContent, googleFonts ) {
				var regexp,
					elementFonts,
					tempFonts = {},
					saveFonts = [];

				if ( '' !== postContent && -1 !== postContent.indexOf( 'fusion_font_' ) ) {
					regexp       = new RegExp( '(fusion_font_[^=]*=")([^"]*)"', 'g' );
					elementFonts = postContent.match( regexp );
					if ( 'object' === typeof elementFonts ) {
						_.each( elementFonts, function( match, key ) {
							var matches = match.slice( 0, -1 ).split( '="' ),
								unique  = matches[ 0 ].replace( 'fusion_font_family_', '' ).replace( 'fusion_font_subset_', '' ).replace( 'fusion_font_variant_', '' ),
								type    = 'family';

							if ( -1 !== matches[ 0 ].indexOf( 'fusion_font_subset_' ) ) {
								type = 'subset';
							} else if (  -1 !== matches[ 0 ].indexOf( 'fusion_font_variant_' ) ) {
								type = 'variant';
							}

							if ( '' === matches[ 1 ] && 'family' === type ) {
								return;
							}

							if ( 'object' !== typeof tempFonts[ unique ] ) {
								tempFonts[ unique ] = {};
							} else if ( 'family' === type ) {

								// If we are setting family again for something already in process, then save out incomplete and start fresh
								saveFonts.push( tempFonts[ unique ] );
								tempFonts[ unique ] = {};
							}

							tempFonts[ unique ][ type ] = matches[ 1 ];

							// If all three are set, add to save fonts and delete from temporary holder so others can be collected with same ID.
							if ( 'undefined' !== typeof tempFonts[ unique ].family && 'undefined' !== typeof tempFonts[ unique ].subset && 'undefined' !== typeof tempFonts[ unique ].variant ) {
								saveFonts.push( tempFonts[ unique ] );
								delete tempFonts[ unique ];
							}
						} );
					}

					// Check for incomplete ones with family and add them too.
					_.each( tempFonts, function( font, option ) {
						if ( 'undefined' !== typeof font.family && '' !== font.family ) {
							saveFonts.push( tempFonts[ option ] );
						}
					} );

					// Look all fonts for saving and save.
					_.each( saveFonts, function( font, option ) {
						if ( 'undefined' === typeof font.family || '' === font.family ) {
							return;
						}
						if ( 'undefined' === typeof googleFonts[ font.family ] ) {
							googleFonts[ font.family ] = {
								variants: [],
								subsets: []
							};
						}

						// Add the variant if it does not exist already.
						if ( 'string' === typeof font.variant && ! googleFonts[ font.family ].variants.includes( font.variant ) ) {
							googleFonts[ font.family ].variants.push( font.variant );
						}

						// Add the subset if not already included.
						if ( 'string' === typeof font.subset && ! googleFonts[ font.family ].subsets.includes( font.subset ) ) {
							googleFonts[ font.family ].subsets.push( font.subset );
						}
					} );
				}

				return googleFonts;
			},

			/**
			 * Checks page content for inline font families.
			 *
			 * @since 2.0.0
			 * @param object googleFonts
			 * @return {Object}
			 */
			setInlineFonts: function( postContent, googleFonts ) {
				var regexp,
					inlineFonts,
					current   = {},
					tempFonts = [],
					saveFonts = [];

				if ( '' !== postContent && -1 !== postContent.indexOf( 'data-fusion-google-' ) ) {
					regexp       = new RegExp( 'data-fusion-google-[^=]*="([^"]*)"', 'g' );
					inlineFonts = postContent.match( regexp );
					if ( 'object' === typeof inlineFonts ) {
						_.each( inlineFonts, function( match, key ) {
							var matches = match.slice( 0, -1 ).split( '="' ),
								type    = 'family';

							if ( -1 !== matches[ 0 ].indexOf( 'data-fusion-google-subset' ) ) {
								type = 'subset';
							} else if (  -1 !== matches[ 0 ].indexOf( 'data-fusion-google-variant' ) ) {
								type = 'variant';
							}

							// Unfilled font family and reached another, bump to temporary and reset current.
							if ( 'string' === typeof current.family && 'family' === type ) {
								tempFonts.push( current );
								current = {};
							}

							current[ type ] = matches[ 1 ];

							// If all three are set, add to save fonts and delete from temporary holder so others can be collected with same ID.
							if ( 'undefined' !== typeof current.family && 'undefined' !== typeof current.subset && 'undefined' !== typeof current.variant ) {
								saveFonts.push( current );
								current = {};
							}
						} );
					}

					// Check for incomplete ones with family and add them too.
					_.each( tempFonts, function( font, option ) {
						if ( 'undefined' !== typeof font.family ) {
							saveFonts.push( tempFonts[ option ] );
						}
					} );

					// Look all fonts for saving and save.
					_.each( saveFonts, function( font, option ) {
						if ( 'undefined' === typeof googleFonts[ font.family ] ) {
							googleFonts[ font.family ] = {
								variants: [],
								subsets: []
							};
						}

						// Add the variant.
						if ( 'string' === typeof font.variant ) {
							googleFonts[ font.family ].variants.push( font.variant );
						}

						// Add the variant.
						if ( 'string' === typeof font.subset ) {
							googleFonts[ font.family ].subsets.push( font.subset );
						}
					} );
				}
				return googleFonts;
			},

			syncGlobalLayouts: function() {
				var $mainContainer = $( '#fusion_builder_main_container' ),
					childChanged   = false,
					updated        = [],
					elementCID,
					element;

				// Return if no globals.
				if ( 0 === $mainContainer.find( 'div[class^="fusion-global-"],div[class*=" fusion-global-"]' ).length ) {
					return;
				}

				// Loop through all global elements.
				$( 'div[class^="fusion-global-"],div[class*=" fusion-global-"]' ).each( function() {
					var globalLayoutID = $( this ).attr( 'fusion-global-layout' );

					// Check if multiple instances exist.
					if ( 1 < $mainContainer.find( '[fusion-global-layout="' + globalLayoutID + '"]' ).length ) {

						// Loop through all multiple instances.
						$( '[fusion-global-layout="' + globalLayoutID + '"]' ).each( function() {
							childChanged = false;

							// Check for child element changes.
							if ( $( this ).hasClass( 'fusion-global-container' ) ) {
								childChanged = FusionPageBuilderApp.isChildElementChanged( $( this ), 'container' );
							} else if ( $( this ).hasClass( 'fusion-global-column' ) ) {
								childChanged = FusionPageBuilderApp.isChildElementChanged( $( this ), 'column' );
							}

							// Get cid from html element.
							elementCID = 'undefined' === typeof $( this ).data( 'cid' ) ? $( this ).find( '.fusion-builder-data-cid' ).data( 'cid' ) : $( this ).data( 'cid' );

							// Get model by cid.
							element = FusionPageBuilderElements.find( function( model ) {
								return model.get( 'cid' ) === elementCID;
							} );

							if ( ( 0 < _.keys( element.changed ).length || true === childChanged ) && -1 === $.inArray( globalLayoutID, updated ) ) {

								// Sync models / Update layout template.
								FusionPageBuilderApp.updateGlobalLayouts( this, element, globalLayoutID );
								updated.push( globalLayoutID );
							}
						} );
					}
				} );
			},

			isChildElementChanged: function( currentElement, section ) {

				// TO DO :: Check for clone and delete too.
				var isChanged = false,
					$thisColumn,
					columnCID,
					column;

				if ( 'container' === section ) {

					// Parse rows.
					currentElement.find( '.fusion-builder-row-content:not(.fusion_builder_row_inner .fusion-builder-row-content)' ).each( function() {

						var thisRow = $( this ),
							rowCID  = thisRow.data( 'cid' ),
							row;

						// Get model from collection by cid.
						row = FusionPageBuilderElements.find( function( model ) {
							return model.get( 'cid' ) === rowCID;
						} );

						if ( 0 < _.keys( row.changed ).length ) {
							isChanged = true;
							return false;
						}

						// Parse columns.
						thisRow.find( '.fusion-builder-column-outer' ).each( function() {

							// Parse column elements.
							var thisColumn = $( this ),
								columnCID  = thisColumn.data( 'cid' ),

								// Get model from collection by cid.
								column = FusionPageBuilderElements.find( function( model ) {
									return model.get( 'cid' ) === columnCID;
								} );

							if ( 0 < _.keys( column.changed ).length ) {
								isChanged = true;
								return false;
							}

							// Find column elements.
							thisColumn.children( '.fusion_module_block, .fusion_builder_row_inner' ).each( function() {
								var thisElement,
									elementCID,
									element,
									thisInnerRow,
									InnerRowCID,
									innerRowView;

								// Regular element.
								if ( $( this ).hasClass( 'fusion_module_block' ) ) {

									thisElement = $( this );
									elementCID  = thisElement.data( 'cid' );

									// Get model from collection by cid.
									element = FusionPageBuilderElements.find( function( model ) {
										return model.get( 'cid' ) === elementCID;
									} );

									if ( 0 < _.keys( element.changed ).length ) {
										isChanged = true;
										return false;
									}
								} else if ( $( this ).hasClass( 'fusion_builder_row_inner' ) ) { // Inner row element

									thisInnerRow = $( this );
									InnerRowCID = thisInnerRow.data( 'cid' );

									innerRowView = FusionPageBuilderViewManager.getView( InnerRowCID );

									// Check inner row.
									if ( 'undefined' !== typeof innerRowView ) {
										isChanged = FusionPageBuilderApp.isNestedRowChanged( '', columnCID );
									}
								}

							} );

						} );

					} );
				} else if ( 'column' === section ) {
					$thisColumn = '';
					columnCID   = currentElement.data( 'cid' );

					// Get model from collection by cid.
					column = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) === columnCID;
					} );

					if ( 0 < _.keys( column.changed ).length ) {
						isChanged = true;
						return false;
					}

					// Parse column elements.
					$thisColumn = currentElement;
					$thisColumn.find( '.fusion_builder_column_element:not(.fusion-builder-column-inner .fusion_builder_column_element)' ).each( function() {
						var $thisModule,
							moduleCID,
							module,
							$thisInnerRow,
							innerRowCID,
							innerRowView;

						// Standard element.
						if ( $( this ).hasClass( 'fusion_module_block' ) ) {
							$thisModule = $( this );
							moduleCID   = 'undefined' === typeof $thisModule.data( 'cid' ) ? $thisModule.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisModule.data( 'cid' );

							// Get model from collection by cid.
							module = FusionPageBuilderElements.find( function( model ) {
								return model.get( 'cid' ) === moduleCID;
							} );

							if ( 0 < _.keys( module.changed ).length ) {
								isChanged = true;
								return false;
							}

						// Inner row/nested element.
						} else if ( $( this ).hasClass( 'fusion_builder_row_inner' ) ) {
							$thisInnerRow = $( this );
							innerRowCID   = 'undefined' === typeof $thisInnerRow.data( 'cid' ) ? $thisInnerRow.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisInnerRow.data( 'cid' );
							innerRowView  = FusionPageBuilderViewManager.getView( innerRowCID );

							// Clone inner row.
							if ( 'undefined' !== typeof innerRowView ) {
								isChanged = FusionPageBuilderApp.isNestedRowChanged( '', columnCID );
							}
						}

					} );
				}

				return isChanged;
			},

			isNestedRowChanged: function( event ) {
				var thisInnerRow,
					isChanged;

				if ( event ) {
					event.preventDefault();
				}

				if ( 0 < _.keys( this.model.changed ).length ) {
					isChanged = true;
					return false;
				}

				// Parse inner columns.
				thisInnerRow = this.$el;
				thisInnerRow.find( '.fusion-builder-column-inner' ).each( function() {
					var $thisColumnInner  = $( this ),
						columnInnerCID    = $thisColumnInner.data( 'cid' ),
						innerColumnModule = FusionPageBuilderElements.findWhere( { cid: columnInnerCID } );

					if ( 0 < _.keys( innerColumnModule.changed ).length ) {
						isChanged = true;
						return false;
					}

					// Parse elements inside inner col.
					$thisColumnInner.find( '.fusion_module_block' ).each( function() {
						var thisModule = $( this ),
							moduleCID  = 'undefined' === typeof thisModule.data( 'cid' ) ? thisModule.find( '.fusion-builder-data-cid' ).data( 'cid' ) : thisModule.data( 'cid' ),

							// Get model from collection by cid.
							module = FusionPageBuilderElements.find( function( model ) {
								return model.get( 'cid' ) === moduleCID;
							} );

						if ( 0 < _.keys( module.changed ).length ) {
							isChanged = true;
							return false;
						}
					} );

				} );
				return isChanged;
			},

			checkGlobalParents: function( parentCID ) {
				var $mainContainer = $( '#fusion_builder_main_container' ),
					thisView;

				module = FusionPageBuilderElements.find( function( model ) { // jshint ignore:line
					return model.get( 'cid' ) === parentCID;
				} );

				if ( 'undefined' === typeof module ) {
					return;
				}

				if ( 'undefined' !== typeof module.attributes.params && 'undefined' !== typeof module.attributes.params.fusion_global && 1 < $mainContainer.find( '[fusion-global-layout="' + module.attributes.params.fusion_global + '"]' ).length ) {

					// Get element view.
					thisView = FusionPageBuilderViewManager.getView( module.get( 'cid' ) );
					if ( 'undefined' !== typeof thisView ) {

						// Update global layout.
						FusionPageBuilderApp.updateGlobalLayouts( thisView.$el, module, module.attributes.params.fusion_global );
					}
				}

				if ( 'undefined' !== typeof module.attributes.params && 'undefined' !== typeof module.get( 'parent' ) ) {
					FusionPageBuilderApp.checkGlobalParents( module.get( 'parent' ) );
				}
			},

			updateGlobalLayouts: function( html, element, layoutID ) {
				var $thisContainer = $( html ),
					shortcode      = '',
					columnCID,
					columnView,
					innerRowCID,
					innerRowView;

				if ( $( html ).hasClass( 'fusion_builder_column_element' ) && ! $( html ).hasClass( 'fusion_builder_row_inner' ) ) {
					shortcode += FusionPageBuilderApp.generateElementShortcode( $( html ), false );
				}  else if ( $( html ).hasClass( 'fusion_builder_row_inner' ) ) {
					innerRowCID   = $thisContainer.data( 'cid' );
					innerRowView  = FusionPageBuilderViewManager.getView( innerRowCID );
					shortcode    += innerRowView.getInnerRowContent( $thisContainer );
				} else if ( $( html ).hasClass( 'fusion-builder-column' ) ) {
					columnCID   = $( html ).data( 'cid' );
					columnView  = FusionPageBuilderViewManager.getView( columnCID );
					shortcode  += columnView.getColumnContent( $( html ) );
				} else if ( $( html ).hasClass( 'fusion_builder_container' ) ) {
					shortcode += FusionPageBuilderApp.generateElementShortcode( $( html ), true );
					$thisContainer.find( '.fusion_builder_row' ).each( function() {
						var $thisRow = $( this );
						shortcode += '[fusion_builder_row]';
						$thisRow.find( '.fusion-builder-column-outer' ).each( function() {
							var $thisColumn = $( this ),
								columnCID   = $thisColumn.data( 'cid' ),
								columnView  = FusionPageBuilderViewManager.getView( columnCID );

							shortcode += columnView.getColumnContent( $thisColumn );

						} );
						shortcode += '[/fusion_builder_row]';
					} );
					shortcode += '[/fusion_builder_container]';
				}

				// Update layout in DB.
				$.ajax( {
					type: 'POST',
					url: fusionBuilderConfig.ajaxurl,
					dataType: 'json',
					data: {
						action: 'fusion_builder_update_layout',
						fusion_load_nonce: fusionBuilderConfig.fusion_load_nonce,
						fusion_layout_id: layoutID,
						fusion_layout_content: shortcode
					},
					complete: function() {

						// Do Stuff.
					}
				} );
			},

			saveHistoryState: function() {

				if ( true === this.newLayoutLoaded ) {
					fusionHistoryManager.clearEditor();
					this.newLayoutLoaded = false;
				}

				fusionHistoryManager.captureEditor();
				fusionHistoryManager.turnOffTracking();
			},

			generateElementShortcode: function( $element, openTagOnly, generator ) {
				var attributes = '',
					content    = '',
					element,
					$thisElement,
					elementCID,
					elementType,
					elementSettings = '',
					shortcode,
					ignoredAtts,
					optionDependency,
					optionDependencyValue,
					key,
					setting,
					settingName,
					settingValue,
					param,
					keyName,
					optionValue,
					ignored,
					paramDependency,
					paramDependencyElement,
					paramDependencyValue;

				// Check if added from Shortcode Generator
				if ( true === generator ) {
					element = $element;
				} else {
					$thisElement = $element;

					// Get cid from html element
					elementCID = 'undefined' === typeof $thisElement.data( 'cid' ) ? $thisElement.find( '.fusion-builder-data-cid' ).data( 'cid' ) : $thisElement.data( 'cid' );

					// Get model by cid
					element = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) === elementCID;
					} );
				}

				elementType     = 'undefined' !== typeof element ? element.get( 'element_type' ) : 'undefined';
				elementSettings = '';
				shortcode       = '';
				elementSettings = element.attributes;

				// Ignored shortcode attributes
				ignoredAtts = 'undefined' !== typeof fusionAllElements[ elementType ].remove_from_atts ? fusionAllElements[ elementType ].remove_from_atts : [];
				ignoredAtts.push( 'undefined' );

				// Option dependency
				optionDependency = ( 'undefined' !== typeof fusionAllElements[ elementType ].option_dependency ) ? fusionAllElements[ elementType ].option_dependency : '';

				for ( key in elementSettings ) {

					settingName = key;

					if ( 'params' !== settingName ) {
						continue;
					}

					settingValue = 'undefined' !== typeof element.get( settingName ) ? element.get( settingName ) : '';

					if ( 'params' === settingName ) {

						// Loop over params
						for ( param in settingValue ) {

							keyName = param;

							if ( 'element_content' === keyName ) {

								optionValue = ( 'undefined' !== typeof settingValue[ param ] ) ? settingValue[ param ] : '';

								content = optionValue;

								if ( 'undefined' !== typeof settingValue[ optionDependency ] && '' !== optionDependency ) {
									optionDependency = fusionAllElements[ elementType ].option_dependency;
									optionDependencyValue = ( 'undefined' !== typeof settingValue[ optionDependency ] ) ? settingValue[ optionDependency ] : '';

									// Set content
									content = 'undefined' !== typeof settingValue[ optionDependencyValue ] ? settingValue[ optionDependencyValue ] : '';
								}

							} else {

								ignored = '';

								if ( '' !== optionDependency ) {

									setting = keyName;

									// Get option dependency value ( value for type )
									optionDependencyValue = ( 'undefined' !== typeof settingValue[ optionDependency ] ) ? settingValue[ optionDependency ] : '';

									// Check for old fusion_map array structure
									if ( 'undefined' !== typeof fusionAllElements[ elementType ].params[ setting ] ) {

										// Dependency exists
										if ( 'undefined' !== typeof fusionAllElements[ elementType ].params[ setting ].dependency ) {

											paramDependency = fusionAllElements[ elementType ].params[ setting ].dependency;

											paramDependencyElement = ( 'undefined' !== typeof paramDependency.element ) ? paramDependency.element : '';

											paramDependencyValue = ( 'undefined' !== typeof paramDependency.value ) ? paramDependency.value : '';

											if ( paramDependencyElement === optionDependency ) {

												if ( paramDependencyValue !== optionDependencyValue ) {

													ignored = '';
													ignored = setting;

												}
											}
										}
									}
								}

								// Ignore shortcode attributes tagged with "remove_from_atts"
								if ( -1 < $.inArray( param, ignoredAtts ) || ignored === param ) {

									// This attribute should be ignored from the shortcode
								} else {

									optionValue = 'undefined' !== typeof settingValue[ param ] ? settingValue[ param ] : '';

									// Check if attribute value is null
									if ( null === optionValue ) {
										optionValue = '';
									}

									attributes += ' ' + param + '="' + optionValue + '"';
								}
							}
						}

					} else if ( '' !== settingValue ) {
						attributes += ' ' + settingName + '="' + settingValue + '"';
					}
				}

				shortcode = '[' + elementType + attributes;

				if ( '' === content && 'fusion_tab' !== elementType && 'fusion_text' !== elementType && 'fusion_code' !== elementType && ( 'undefined' !== typeof elementSettings.type && 'element' === elementSettings.type ) ) {
					openTagOnly = true;
					shortcode += ' /]';
				} else {
					shortcode += ']';
				}

				if ( ! openTagOnly ) {
					shortcode += content + '[/' + elementType + ']';
				}

				return shortcode;
			},

			customCSS: function( event ) {
				if ( event ) {
					event.preventDefault();
				}

				$( '.fusion-custom-css' ).slideToggle();
			},

			toggleAllContainers: function( event ) {

				var toggleButton,
					containerCID,
					that = this;

				if ( event ) {
					event.preventDefault();
				}

				toggleButton = $( '.fusion-builder-layout-buttons-toggle-containers' ).find( 'span' );

				if ( toggleButton.hasClass( 'dashicons-arrow-up' ) ) {
					toggleButton.removeClass( 'dashicons-arrow-up' ).addClass( 'dashicons-arrow-down' );

					jQuery( '.fusion_builder_container' ).each( function() {
						var containerModel;

						containerCID   = jQuery( this ).find( '.fusion-builder-data-cid' ).data( 'cid' );
						containerModel = that.collection.find( function( model ) {
							return model.get( 'cid' ) === containerCID;
						} );
						containerModel.attributes.params.admin_toggled = 'yes';
						jQuery( this ).addClass( 'fusion-builder-section-folded' );
						jQuery( this ).find( '.fusion-builder-toggle > span' ).removeClass( 'dashicons-arrow-up' ).addClass( 'dashicons-arrow-down' );
					} );

				} else {
					toggleButton.addClass( 'dashicons-arrow-up' ).removeClass( 'dashicons-arrow-down' );
					jQuery( '.fusion_builder_container' ).each( function() {
						var containerModel;

						containerCID   = jQuery( this ).find( '.fusion-builder-data-cid' ).data( 'cid' );
						containerModel = that.collection.find( function( model ) {
							return model.get( 'cid' ) === containerCID;
						} );
						containerModel.attributes.params.admin_toggled = 'no';
						jQuery( this ).removeClass( 'fusion-builder-section-folded' );
						jQuery( this ).find( '.fusion-builder-toggle > span' ).addClass( 'dashicons-arrow-up' ).removeClass( 'dashicons-arrow-down' );
					} );
				}

				FusionPageBuilderEvents.trigger( 'fusion-element-edited' );
			},

			showSavedElements: function( elementType, container ) {

				var data = jQuery( '#fusion-builder-layouts-' + elementType ).find( '.fusion-page-layouts' ).clone(),
					postId;

				data.find( 'li' ).each( function() {
					postId = jQuery( this ).find( '.fusion-builder-demo-button-load' ).attr( 'data-post-id' );
					jQuery( this ).find( '.fusion-layout-buttons' ).remove();
					jQuery( this ).find( 'h4' ).attr( 'class', 'fusion_module_title' );
					jQuery( this ).attr( 'data-layout_id', postId );
					jQuery( this ).addClass( 'fusion_builder_custom_' + elementType + '_load' );
					if ( '' !== jQuery( this ).attr( 'data-layout_type' ) ) {
						jQuery( this ).addClass( 'fusion-element-type-' + jQuery( this ).attr( 'data-layout_type' ) );
					}
				} );
				container.append( '<div id="fusion-loader"><span class="fusion-builder-loader"></span></div>' );
				container.append( '<ul class="fusion-builder-all-modules">' + data.html() + '</div>' );
			},

			rangeOptionPreview: function( view ) {
				view.find( '.fusion-range-option' ).each( function() {
					$( this ).next().html( $( this ).val() );
					$( this ).on( 'change mousemove', function() {
						$( this ).next().html( $( this ).val() );
					} );
				} );
			},

			addClassToElement: function( builderElement, className, layoutID, cid ) {
				var tooltip = fusionBuilderText.global_element;

				builderElement.addClass( className );
				builderElement.attr( 'fusion-global-layout', layoutID );

				if ( 'fusion-global-column' === className ) {
					tooltip = fusionBuilderText.global_column;
				} else if ( 'fusion-global-container' === className ) {
					tooltip = fusionBuilderText.global_container;
				}

				// If container add to utility toolbar area.
				if ( builderElement.find( '.fusion-builder-container-utility-toolbar' ).length ) {
					builderElement.find( '.fusion-builder-container-utility-toolbar' ).append( '<div class="fusion-builder-global-tooltip" data-cid="' + cid + '"><span>' + tooltip + '</span></div>' );
				} else {
					builderElement.append( '<div class="fusion-builder-global-tooltip" data-cid="' + cid + '"><span>' + tooltip + '</span></div>' );
				}
			},

			calculateTableData: function( params, view ) {
				var tableDOM,
					tr,
					rowsOld,
					thTdOld,
					tdOld,
					columnsOld;

				if ( 'undefined' === typeof params.element_content || '' === params.element_content ) {
					return params;
				}

				tableDOM   = jQuery.parseHTML( params.element_content.trim() );
				tr         = jQuery( tableDOM ).find( 'tbody > tr' );
				rowsOld    = tr.length + 1;
				thTdOld    = jQuery( tableDOM ).find( 'th' ).length;
				tdOld      = tr.first().children( 'td' ).length;
				columnsOld = Math.max( thTdOld, tdOld );

				params.fusion_table_columns = columnsOld;
				params.fusion_table_rows = rowsOld;

				return params;
			},

			checkOptionDependency: function( view, thisEl, parentValues, repeaterFields, parentEl ) {
				var $dependencies        = {},
					$dependencyIds       = '',
					$parentDependencyIds = '',
					params               = view.params,
					$currentVal,
					$currentId,
					$optionId,
					$passedArray,
					dividerType,
					upAndDown,
					centerOption,
					$targetElement;

				if ( 'undefined' !== typeof repeaterFields ) {
					params = repeaterFields;
				}

				function doesTestPass( current, comparison, operator ) {
					if ( '==' === operator && current == comparison ) { // jshint ignore:line
						return true;
					}
					if ( '!=' === operator && current != comparison ) { // jshint ignore:line
						return true;
					}
					if ( '>' === operator && current > comparison ) {
						return true;
					}
					if ( '<' === operator && current < comparison ) {
						return true;
					}
					return false;
				}

				// Special check for section separator.
				if ( 'undefined' !== typeof view.shortcode && 'fusion_section_separator' === view.shortcode ) {
					dividerType  = thisEl.find( '#divider_type' );
					upAndDown    = dividerType.parents( 'ul' ).find( 'li[data-option-id="divider_candy"]' ).find( '.fusion-option-divider_candy' ).find( '.ui-button[data-value="bottom,top"]' );
					centerOption = dividerType.parents( 'ul' ).find( 'li[data-option-id="divider_position"]' ).find( '.fusion-option-divider_position' ).find( '.ui-button[data-value="center"]' );

					if ( 'triangle' !== dividerType.val() ) {
						upAndDown.hide();
					} else {
						upAndDown.show();
					}

					if ( 'bigtriangle' !== dividerType.val() ) {
						centerOption.hide();
					} else {
						centerOption.show();
					}

					dividerType.on( 'change paste keyup', function() {

						if ( 'triangle' !== jQuery( this ).val() ) {
							upAndDown.hide();
						} else {
							upAndDown.show();
						}

						if ( 'bigtriangle' !== jQuery( this ).val() ) {
							centerOption.hide();
							if ( centerOption.hasClass( 'ui-state-active' ) ) {
								centerOption.prev().click();
							}
						} else {
							centerOption.show();
						}

					} );
				}

				// Initial checks and create helper objects.
				jQuery.each( params, function( index, value ) {
					if ( 'undefined' !== typeof value.dependency ) {
						$optionId    = index;
						$passedArray = [];

						// Check each dependency for this option
						jQuery.each( value.dependency, function( index, dependency ) {

							// Create IDs of fields to check for.
							if ( 'undefined' !== typeof repeaterFields && 'parent_' === dependency.element.substring( 0, 7 ) && 0 > $parentDependencyIds.indexOf( '#' + dependency.element.replace( 'parent_', '' ) ) ) {
								$parentDependencyIds += ', #' + dependency.element.replace( 'parent_', '' );
							} else if ( 0 > $dependencyIds.indexOf( '#' + dependency.element ) ) {
								$dependencyIds += ', #' + dependency.element;
							}

							// If option has dependency add to check array.
							if ( 'undefined' === typeof $dependencies[ dependency.element ] ) {
								$dependencies[ dependency.element ] = [ { option: $optionId, or: value.or } ];
							} else {
								$dependencies[ dependency.element ].push( { option: $optionId, or: value.or } );
							}

							// If parentValues is an object and this is a parent dependency, then we should take value from there.
							if ( 'parent_' === dependency.element.substring( 0, 7 ) ) {
								if ( 'undefined' !== typeof repeaterFields ) {
									$currentVal = thisEl.parents( '.fusion-builder-main-settings' ).find( '#' + dependency.element.replace( 'parent_', '' ) ).val();
								} else if ( 'object' === typeof parentValues && parentValues[ dependency.element.replace( dependency.element.substring( 0, 7 ), '' ) ] ) {
									$currentVal = parentValues[ dependency.element.replace( dependency.element.substring( 0, 7 ), '' ) ];
								} else {
									$currentVal = '';
								}
							} else {
								$currentVal = thisEl.find( '#' + dependency.element ).val();

								// Use fake value if dynamic data is set.
								if ( '' === $currentVal && 'true' === thisEl.find( '#' + dependency.element ).closest( '.fusion-builder-option' ).attr( 'data-dynamic' ) ) {
									$currentVal = 'using-dynamic-value';
								}
							}
							$passedArray.push( doesTestPass( $currentVal, dependency.value, dependency.operator ) );
						} );

						$targetElement = thisEl.find( '#' + index ).parents( '.fusion-builder-option' ).first();

						// Check if it passes for regular "and" test.
						if ( -1 === $.inArray( false, $passedArray ) && 'undefined' === typeof value.or ) {
							$targetElement.fadeIn( 300 );

						// Check if it passes "or" test.
						} else if ( -1 !== $.inArray( true, $passedArray ) && 'undefined' !== typeof value.or ) {
							$targetElement.fadeIn( 300 );

						// If it fails.
						} else {
							$targetElement.hide();
						}
					}
				} );

				// Listen for changes to options which other are dependent on.
				if ( $dependencyIds.length ) {
					thisEl.on( 'change paste keyup', $dependencyIds.substring( 2 ), function() {
						$currentId = jQuery( this ).attr( 'id' );

						// Loop through each option id that is dependent on this option.
						jQuery.each( $dependencies[ $currentId ], function( index, value ) {
							$passedArray = [];

							// Check each dependency for that id.
							jQuery.each( params[ value.option ].dependency, function( index, dependency ) {

								// If parentValues is an object and this is a parent dependency, then we should take value from there.
								if ( 'parent_' === dependency.element.substring( 0, 7 ) ) {
									if ( 'object' === typeof parentValues && parentValues[ dependency.element.replace( dependency.element.substring( 0, 7 ), '' ) ] ) {
										$currentVal = parentValues[ dependency.element.replace( dependency.element.substring( 0, 7 ), '' ) ];
									} else {
										$currentVal = '';
									}
								} else {
									$currentVal = thisEl.find( '#' + dependency.element ).val();
								}

								// Use fake value if dynamic data is set.
								if ( '' === $currentVal && 'true' === jQuery( '#' + $currentId ).closest( '.fusion-builder-option' ).attr( 'data-dynamic' ) ) {
									$currentVal = 'using-dynamic-value';
								}

								$passedArray.push( doesTestPass( $currentVal, dependency.value, dependency.operator ) );
							} );

							$targetElement = thisEl.find( '#' + value.option ).parents( '.fusion-builder-option' ).first();

							// Check if it passes for regular "and" test.
							if ( -1 === $.inArray( false, $passedArray ) && 'undefined' === typeof value.or ) {
								$targetElement.fadeIn( 300 );

							// Check if it passes "or" test.
							} else if ( -1 !== $.inArray( true, $passedArray ) && 'undefined' !== typeof value.or ) {
								$targetElement.fadeIn( 300 );

							// If it fails.
							} else {
								$targetElement.hide();
							}
						} );

					} );
				}

				// Repeater element row, listen for changes to parent options.
				if ( 'undefined' !== typeof repeaterFields && 'undefined' !== typeof parentEl && $parentDependencyIds.length ) {
					parentEl.on( 'change paste keyup', $parentDependencyIds.substring( 2 ), function() {
						$currentId = jQuery( this ).attr( 'id' );

						// Loop through each option id that is dependent on this option.
						jQuery.each( $dependencies[ 'parent_' + $currentId ], function( index, value ) {
							$passedArray = [];

							// Check each dependency for that id.
							jQuery.each( params[ value.option ].dependency, function( index, dependency ) {
								if ( 'parent_' === dependency.element.substring( 0, 7 ) ) {
									$currentVal = parentEl.find( '#' + dependency.element.replace( 'parent_', '' ) ).val();
								} else {
									$currentVal = parentEl.find( '#' + dependency.element ).val();
								}
								$passedArray.push( doesTestPass( $currentVal, dependency.value, dependency.operator ) );
							} );

							$targetElement = thisEl.find( '#' + value.option ).parents( '.fusion-builder-option' ).first();

							// Check if it passes for regular "and" test.
							if ( -1 === $.inArray( false, $passedArray ) && 'undefined' === typeof value.or ) {
								$targetElement.fadeIn( 300 );

							// Check if it passes "or" test.
							} else if ( -1 !== $.inArray( true, $passedArray ) && 'undefined' !== typeof value.or ) {
								$targetElement.fadeIn( 300 );

							// If it fails.
							} else {
								$targetElement.hide();
							}
						} );

					} );
				}
			}

		} );

		// Instantiate Builder App
		FusionPageBuilderApp = new FusionPageBuilder.AppView( { // jshint ignore:line
			model: FusionPageBuilder.Element,
			collection: FusionPageBuilderElements
		} );

		// Stores 'active' value in fusion_builder_status meta key if builder is activa
		$useBuilderMetaField = $( '#fusion_use_builder' );

		// Fusion Builder Toggle Button
		$toggleBuilderButton = $( '#fusion_toggle_builder' );

		// Fusion Builder div
		$builder = $( '#fusion_builder_layout' );

		// Main wrap for the main editor
		$mainEditorWrapper = $( '#fusion_main_editor_wrap' );

		// Show builder div if it's activated
		if ( $toggleBuilderButton.hasClass( 'fusion_builder_is_active' ) ) {
			$builder.show();
			FusionPageBuilderApp.builderActive = true;

			// Sticky header
			fusionBuilderEnableStickyHeader();

			jQuery( 'body' ).addClass( 'fusion-builder-enabled' );
		}

		// Builder toggle button event
		$toggleBuilderButton.click( function( event ) {

			var isBuilderUsed;

			if ( event ) {
				event.preventDefault();
			}

			isBuilderUsed = $( this ).hasClass( 'fusion_builder_is_active' );

			if ( isBuilderUsed ) {
				fusionBuilderDeactivate( $( this ) );
				FusionPageBuilderApp.builderActive = false;
				jQuery( 'body' ).removeClass( 'fusion-builder-enabled' );
				jQuery( 'body' ).trigger( 'scroll' );
			} else {
				fusionBuilderActivate( $( this ) );
				FusionPageBuilderApp.builderActive = true;
				jQuery( 'body' ).addClass( 'fusion-builder-enabled' );
			}
		} );

		// Front End Editor button.
		jQuery( '#fusion_toggle_front_end' ).on( 'click', function( event ) {
			var $wpTitle = jQuery( '#title' ),
				$link = jQuery( this );

			event.preventDefault();

			if ( window.confirm( fusionBuilderText.front_end_redirect_confirm ) ) {
				if ( ! $wpTitle.val() ) {
					$wpTitle.val( 'FB #' + jQuery( '#post_ID' ).val() );
				}

				if ( wp.autosave ) {
					wp.autosave.server.triggerSave();
				}

				// Autosave callback.
				jQuery( document ).on( 'heartbeat-tick.autosave', function() {

					// Changes saved, so need for "are you sure you want to navigate away" alert.
					jQuery( window ).off( 'beforeunload.edit-post' );

					// Redirect user.
					window.location = $link.attr( 'href' );
				} );
			}
		} );

		// Sticky builder header
		function fusionBuilderEnableStickyHeader() {
			var builderHeader = document.getElementById( 'fusion_builder_controls' );
			fusionBuilderStickyHeader( builderHeader, jQuery( '#wpadminbar' ).height() );
		}

		function fusionBuilderActivate( toggle ) {

			fusionBuilderReset();

			FusionPageBuilderApp.initialBuilderLayout();

			$useBuilderMetaField.val( 'active' );

			$builder.show();

			toggle.children( 'span' ).text( toggle.data( 'editor' ) );
			toggle.toggleClass( 'fusion_builder_is_active' ).toggleClass( 'button-primary' ).toggleClass( 'fusiona-FB_logo_black' );

			$mainEditorWrapper.toggleClass( 'fusion_builder_hidden' );

			// Sticky header
			fusionBuilderEnableStickyHeader();

		}

		function fusionBuilderReset() {

			// Clear all models and views
			FusionPageBuilderElements.reset();
			FusionPageBuilderViewManager.set( 'elementCount', 0 );
			FusionPageBuilderViewManager.set( 'views', {} );

			// Clear layout
			$( '#fusion_builder_container' ).html( '' );

			FusionPageBuilderApp.shortcodeGenerator = false;
		}

		function fusionBuilderDeactivate() {
			var $body,
				pagePosition;

			fusionBuilderReset();

			$body        = $( 'body' );
			pagePosition = 0;

			window.wpActiveEditor = 'content';

			$useBuilderMetaField.val( 'off' );

			$builder.hide();

			$toggleBuilderButton.children( 'span' ).text( $toggleBuilderButton.data( 'builder' ) );
			$toggleBuilderButton.toggleClass( 'fusion_builder_is_active' ).toggleClass( 'button-primary' ).toggleClass( 'fusiona-FB_logo_black' );

			$mainEditorWrapper.toggleClass( 'fusion_builder_hidden' );

			FusionPageBuilderApp.$el.find( '.fusion_builder_container' ).remove();

			pagePosition = $body.scrollTop();
			jQuery( 'html, body' ).scrollTop( pagePosition + 1 );

		}

		// Remove preview image.
		$container = $( 'body' );
		$container.on( 'click', '.upload-image-remove', function( event ) {
			var $field,
				$preview,
				$upload,
				imageIDField;

			if ( event ) {
				event.preventDefault();
			}

			$field   = $( this ).parents( '.fusion-builder-option-container' ).find( '.fusion-builder-upload-field' );
			$preview = $( this ).parents( '.fusion-builder-option-container' ).find( '.fusion-builder-upload-preview' );
			$upload  = $( this ).parents( '.fusion-builder-option-container' ).find( '.fusion-builder-upload-button' );

			$field.val( '' ).trigger( 'change' );
			$upload.val( 'Upload Image' );
			$preview.remove();

			// Remove image ID if image is removed.
			imageIDField = $upload.parents( '.fusion-builder-option' ).next().find( '#' + $upload.data( 'param' ) + '_id' );

			if ( 'element_content' === $upload.data( 'param' ) ) {
				imageIDField = $upload.parents( '.fusion-builder-option' ).next().find( '#image_id' );
			}

			if ( imageIDField.length ) {
				imageIDField.val( '' );
			}

			jQuery( this ).remove();
		} );

		// History steps.
		$( 'body' ).on( 'click', '.fusion-builder-history-list li', function( event ) {
			var step;

			if ( event ) {
				event.preventDefault();
			}

			step = $( event.currentTarget ).data( 'state-id' );
			fusionHistoryManager.historyStep( step );
		} );

		// Element option tabs.
		$( 'body' ).on( 'click', '.fusion-tabs-menu a', function( event ) {

			var tab;

			if ( event ) {
				event.preventDefault();
			}

			FusionPageBuilderEvents.trigger( 'fusion-switch-element-option-tabs' );
			FusionPageBuilderEvents.trigger( 'fusion-switch-element-option-tabs' );

			$( this ).parent().addClass( 'current' ).removeClass( 'inactive' );
			$( this ).parent().siblings().removeClass( 'current' ).addClass( 'inactive' );
			tab = $( this ).attr( 'href' );
			$( this ).parents( '.fusion-builder-modal-container' ).find( '.fusion-tab-content' ).not( tab ).css( 'display', 'none' );
			$( '.fusion-builder-layouts-tab' ).hide();

			if ( $( this ).parents( '.fusion-builder-modal-container' ).length ) {
				$( this ).parents( '.fusion-builder-modal-container' ).find( tab ).fadeIn( 'fast' );
			} else {
				$( tab ).fadeIn( 'fast' );
			}

			if ( jQuery( '.fusion-builder-modal-top-container' ).find( '.fusion-elements-filter' ).length ) {
				setTimeout( function() {
					jQuery( '.fusion-builder-modal-top-container' ).find( '.fusion-elements-filter' ).focus();
				}, 50 );
			}
		} );

		// Close modal on overlick click.
		jQuery( '.fusion_builder_modal_overlay' ).on( 'click', function() {
			FusionPageBuilderEvents.trigger( 'fusion-remove-modal-view' );
			FusionPageBuilderEvents.trigger( 'fusion-close-modal' );
		} );

		// Close nested modal on overlick click.
		jQuery( '.fusion_builder_modal_inner_row_overlay' ).on( 'click', function() {
			FusionPageBuilderEvents.trigger( 'fusion-close-inner-modal' );
			FusionPageBuilderEvents.trigger( 'fusion-hide-library' );
		} );

		// Demo select.
		$selectedDemo = jQuery( '.fusion-builder-demo-select' ).val();
		jQuery( '#fusion-builder-layouts-demos .demo-' + $selectedDemo ).show();

		jQuery( '.fusion-builder-demo-select' ).on( 'change', function() {
			$selectedDemo = jQuery( '.fusion-builder-demo-select' ).val();
			jQuery( '#fusion-builder-layouts-demos .fusion-page-layouts' ).hide();
			jQuery( '#fusion-builder-demo-url-invalid' ).hide();
			jQuery( '.fusion-builder-demo-page-link' ).val( '' );
			jQuery( '#fusion-builder-layouts-demos .demo-' + $selectedDemo ).show();
		} );

		jQuery( '.fusion-builder-demo-page-link' ).on( 'input', function() {
			var demoPageLink = jQuery( this ).val(),
				demoPage,
				parentDemo,
				demoSelectorVal;

			demoPageLink = demoPageLink.replace( 'https://', '' ).replace( 'http://', '' );
			if ( '/' !== demoPageLink[ demoPageLink.length - 1 ] && ! _.isEmpty( demoPageLink ) ) {
				demoPageLink += '/';
			}

			demoPage   = jQuery( '#fusion-builder-layouts-demos' ).find( '.fusion-page-layout[data-page-link="' + demoPageLink + '"]' );
			parentDemo = demoPage.closest( '.fusion-page-layouts' );

			jQuery( '#fusion-builder-layouts-demos .fusion-page-layouts' ).hide();
			jQuery( '#fusion-builder-demo-url-invalid' ).hide();

			if ( _.isEmpty( demoPageLink ) ) {
				demoSelectorVal = jQuery( '.fusion-builder-demo-select' ).val();
				jQuery( '#fusion-builder-layouts-demos .demo-' + demoSelectorVal ).show();
			} else if ( ! demoPage.length ) {
				jQuery( '#fusion-builder-demo-url-invalid' ).show();
			} else {
				parentDemo.show();
				parentDemo.find( '.fusion-page-layout' ).hide();
				demoPage.show();
			}
		} );

		// Iconpicker select/deselect handler.
		jQuery( 'body' ).on( 'click', '.icon_select_container .icon_preview', function( e ) {

			var fontName,
				subset = 'fas',
				$i     = jQuery( this ).find( 'i' ),
				value  = '',
				$containerParent = jQuery( this ).closest( '.fusion-iconpicker' );

			e.preventDefault();

			fontName = 'fa-' + jQuery( this ).find( 'i' ).attr( 'data-name' );

			if ( ! $i.hasClass( 'fas' ) && ! $i.hasClass( 'fab' ) && ! $i.hasClass( 'far' ) && ! $i.hasClass( 'fal' ) ) {

				// Custom icon set, so we need to add prefix.
				value = 'fusion-prefix-' + jQuery( this ).find( 'i' ).attr( 'class' );
			} else if ( $i.hasClass( 'fab' ) ) {
				subset = 'fab';
			} else if ( $i.hasClass( 'far' ) ) {
				subset = 'far';
			} else if ( $i.hasClass( 'fal' ) ) {
				subset = 'fal';
			}

			// FA icon.
			if ( '' === value ) {
				value = fontName + ' ' + subset;
			}

			if ( $( this ).hasClass( 'selected-element' ) ) {
				$containerParent.find( '.selected-element' ).removeClass( 'selected-element' );
				$containerParent.find( '.fusion-iconpicker-input' ).attr( 'value', '' ).trigger( 'change' );

			} else {
				$containerParent.find( '.selected-element' ).removeClass( 'selected-element' );
				$( this ).find( 'i' ).parent().addClass( 'selected-element' );
				$containerParent.find( '.fusion-iconpicker-input' ).attr( 'value', value ).trigger( 'change' );
			}
		} );

		// Open shortcode generator.
		$( document ).on( 'click', '#qt_content_fusion_shortcodes_text_mode, #qt_excerpt_fusion_shortcodes_text_mode, #qt_element_content_fusion_shortcodes_text_mode', function() {
			openShortcodeGenerator( $( this ) );
		} );

		$( 'input[type="radio"][name="screen_columns"]' ).on( 'click', function() {
			$( window ).trigger( 'resize' );
		} );

		$( '.notice-dismiss, #show-settings-link' ).on( 'click', function() {
			setTimeout( function() {
				$( window ).trigger( 'resize' );
			}, 750 );
		} );

		// Save layout template on return key.
		$( '#new_template_name' ).keydown( function( e ) {
			if ( 13 === e.keyCode || '13' === e.keyCode ) {
				e.preventDefault();
				e.stopPropagation();
				FusionPageBuilderEvents.trigger( 'fusion-save-layout' );
				return false;
			}
			return true;
		} );

		// Save elements on return key.
		$( 'body' ).on( 'keydown', '#fusion-builder-save-element-input', function( e ) {
			if ( 13 === e.keyCode || '13' === e.keyCode ) {
				e.preventDefault();
				e.stopPropagation();
				$( '.fusion-builder-element-button-save' ).trigger( 'click' );
				return false;
			}
			return true;
		} );

		// Handle the sticky publish buttons.
		jQuery( '.fusion-preview' ).click( function( e ) {
			e.preventDefault();
			jQuery( '#post-preview' ).trigger( 'click' );
		} );
		jQuery( '.fusion-save-draft' ).click( function( e ) {
			e.preventDefault();
			jQuery( '#save-post' ).trigger( 'click' );
		} );
		jQuery( '.fusion-update' ).click( function( e ) {
			e.preventDefault();
			jQuery( '#publish' ).trigger( 'click' );
		} );

		( function initIconPicker() {
			var icons       = fusionBuilderConfig.fontawesomeicons,
				output      = '<div class="fusion-icons-rendered" style="height:0px; overflow:hidden;">',
				outputSets  = {
					fas: '',
					fab: '',
					far: '',
					fal: ''
				},
				iconSubsets = {
					fas: 'Solid',
					far: 'Regular',
					fal: 'Light',
					fab: 'Brands'
				},
				outputNav = '<div class="fusion-icon-picker-nav-rendered" style="height:0px; overflow:hidden;">',
				isSearchDefined = 'undefined' !== typeof fusionIconSearch && Array.isArray( fusionIconSearch );

			// Iterate through all FA icons and divide them into sets (one icon can belong to multiple sets).
			_.each( icons, function( icon, key ) {

				_.each( icon[ 1 ], function( iconSubset ) {
					if ( -1 !== fusionBuilderConfig.fontawesomesubsets.indexOf( iconSubset ) ) {
						outputSets[ iconSubset ] += '<span class="icon_preview ' + key + '" title="' + key + ' - ' + iconSubsets[ iconSubset ] + '"><i class="' + icon[ 0 ] + ' ' + iconSubset + '" data-name="' + icon[ 0 ].substr( 3 ) + '"></i></span>';
					}
				} );
			} );

			// Add FA sets to output.
			_.each( iconSubsets, function( label, key ) {
				if ( -1 !== fusionBuilderConfig.fontawesomesubsets.indexOf( key ) ) {
					outputNav += '<a href="#fusion-' + key + '">' + label + '</a>';
					output    += '<div id="fusion-' + key + '" class="fusion-icon-set">' + outputSets[ key ] + '</div>';
				}
			} );

			// WIP: Add custom icons.
			icons = fusionBuilderConfig.customIcons;
			_.each( icons, function( iconSet, IconSetKey ) {
				outputNav += '<a href="#' + IconSetKey + '">' + iconSet.name + '</a>';
				output    += '<div id="' + IconSetKey + '" class="fusion-icon-set fusion-custom-icon-set">';
				_.each( iconSet.icons, function( icon ) {

					if ( isSearchDefined ) {
						fusionIconSearch.push( { name: icon } );
					}

					output += '<span class="icon_preview ' + icon + '" title="' + iconSet.css_prefix + icon + '"><i class="' + iconSet.css_prefix + icon + '" data-name="' + icon + '"></i></span>';
				} );
				output += '</div>';
			} );

			outputNav += '</div>';
			output    += '</div>';

			$( 'body' ).append( output + outputNav );

		}() );
	} );
}( jQuery ) );
