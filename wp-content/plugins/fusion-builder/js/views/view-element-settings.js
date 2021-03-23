/* global openShortcodeGenerator, FusionPageBuilderEvents, fusionAllElements, FusionPageBuilderApp, fusionBuilderText, noUiSlider, wNumb, FusionPageBuilderViewManager */
/* eslint no-unused-vars: 0 */
/* eslint no-shadow: 0 */
/* eslint no-extend-native: 0 */
/* eslint no-alert: 0 */
/* eslint no-empty-function: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		FusionPageBuilder.ElementSettingsView = window.wp.Backbone.View.extend( {

			className: 'fusion_builder_module_settings',
			template: FusionPageBuilder.template( $( '#fusion-builder-block-module-settings-template' ).html() ),

			events: {
				'click #qt_element_content_fusion_shortcodes_text_mode': 'activateSCgenerator',
				'click .option-dynamic-content': 'addDynamicContent'
			},

			activateSCgenerator: function( event ) {
				openShortcodeGenerator( $( event.target ) );
			},

			initialize: function() {
				var functionName,
					params,
					processedParams;

				this.listenTo( FusionPageBuilderEvents, 'fusion-modal-view-removed', this.removeElement );

				// Manupulate model attributes via custom function if provided by the element
				if ( 'undefined' !== typeof fusionAllElements[ this.model.get( 'element_type' ) ].on_settings ) {

					functionName = fusionAllElements[ this.model.get( 'element_type' ) ].on_settings;

					if ( 'function' === typeof FusionPageBuilderApp[ functionName ] ) {
						params          = this.model.get( 'params' );
						processedParams = FusionPageBuilderApp[ functionName ]( params, this );

						this.model.set( 'params', processedParams );
					}
				}

				this.listenTo( FusionPageBuilderEvents, 'fusion-dynamic-data-removed', this.removeDynamicStatus );
				this.listenTo( FusionPageBuilderEvents, 'fusion-dynamic-data-added', this.addDynamicStatus );
				this.dynamicSelection = false;
				this.dynamicParams    = 'object' === typeof this.options && 'object' === typeof this.options.dynamicParams ? this.options.dynamicParams : false;

				this.onInit();
			},

			onInit: function() {
			},

			addDynamicContent: function( event ) {
				var self         = this,
					$option      = jQuery( event.target ).closest( '.fusion-builder-option' ),
					param        = $option.attr( 'data-option-id' ),
					sameParam    = false,
					viewSettings;

				if ( this.dynamicSelection ) {
					if ( param === this.dynamicSelection.model.get( 'param' ) ) {
						sameParam = true;
					}
					this.dynamicSelection.removeView();
				}

				if ( sameParam ) {
					return;
				}

				viewSettings = {
					model: new FusionPageBuilder.Element( {
						param: param,
						option: $option,
						parent: this
					} )
				};

				// On select or cancel or event we destroy.
				this.dynamicSelection = new FusionPageBuilder.DynamicSelection( viewSettings );
				$option.find( '.fusion-dynamic-selection' ).html( this.dynamicSelection.render().el );
			},

			removeDynamicStatus: function( param ) {
				this.$el.find( '.fusion-builder-option[data-option-id="' + param + '"]' ).attr( 'data-dynamic', false );

				// Needed for dependencies.
				this.$el.find( '#' + param ).trigger( 'change' );
			},

			addDynamicStatus: function( param ) {
				this.$el.find( '.fusion-builder-option[data-option-id="' + param + '"]' ).attr( 'data-dynamic', true );

				// Needed for dependencies.
				this.$el.find( '#' + param ).trigger( 'change' );
			},

			render: function() {
				var $thisEl = this.$el,
					atts    = this.model.attributes;

				this.beforeRender();

				if ( 'object' === typeof this.dynamicParams ) {
					this.dynamicParams.createBackup();
					atts.dynamic_params = this.dynamicParams.getAll();
				}

				$thisEl.html( this.template( { atts: atts } ) );

				this.optionInit( $thisEl );

				setTimeout( function() {
					$thisEl.find( 'select, input, textarea, radio' ).filter( ':eq(0)' ).not( '[data-placeholder]' ).focus();
				}, 1 );

				// Check option dependencies
				if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get ) {
					FusionPageBuilderApp.checkOptionDependency( fusionAllElements[ this.model.get( 'element_type' ) ], $thisEl );
				}

				this.onRender();

				FusionPageBuilderEvents.trigger( 'fusion-settings-modal-open' );

				return this;
			},

			beforeRender: function() {
			},

			onRender: function() {
			},

			optionInit: function( $el ) {
				var $thisEl = $el,
					self    = this,
					content = '',
					view,
					$contentTextarea,
					$contentTextareaOption,
					$colorPicker,
					$uploadButton,
					$iconPicker,
					$multiselect,
					$checkboxbuttonset,
					$radiobuttonset,
					$value,
					$id,
					$container,
					$search,
					viewCID,
					$checkboxsetcontainer,
					$radiosetcontainer,
					$subgroupWrapper,
					$visibility,
					$choice,
					$rangeSlider,
					$i,
					thisModel,
					$selectField,
					textareaID,
					allowGenerator = false,
					$dimensionField,
					codeBlockId,
					$codeBlock,
					codeElement,
					that = this,
					$textField,
					$placeholderText,
					$theContent,
					fixSettingsLvl = false,
					parentAtts,
					$linkButton,
					$dateTimePicker,
					$multipleImages,
					fetchIds = [],
					parentValues,
					$repeater,
					$sortableText,
					codeMirrorJSON,
					$fontFamily;

				thisModel = this.model;

				// Fix for deprecated 'settings_lvl' attribute
				if ( 'undefined' !== thisModel.attributes.params.settings_lvl && 'parent' === thisModel.attributes.params.settings_lvl ) {
					fixSettingsLvl = true;
					parentAtts = thisModel.attributes.params;
				}

				if ( 'undefined' !== typeof thisModel.get && 'undefined' !== typeof thisModel.get( 'allow_generator' ) && true === thisModel.get( 'allow_generator' ) ) {
					FusionPageBuilderApp.allowShortcodeGenerator = true;
				}

				// Set parentValues for dependencies on child.
				parentValues = ( 'undefined' !== typeof this.model.get && 'undefined' !== typeof this.model.get( 'parent_values' ) ) ? this.model.get( 'parent_values' ) : false;
				$textField         = $thisEl.find( '[data-placeholder]' );
				$contentTextarea   = $thisEl.find( '.fusion-editor-field' );
				$colorPicker       = $thisEl.find( '.fusion-builder-color-picker-hex' );
				$uploadButton      = $thisEl.find( '.fusion-builder-upload-button' );
				$iconPicker        = $thisEl.find( '.fusion-iconpicker' );
				$multiselect       = $thisEl.find( '.fusion-form-multiple-select' );
				$checkboxbuttonset = $thisEl.find( '.fusion-form-checkbox-button-set' );
				$radiobuttonset    = $thisEl.find( '.fusion-form-radio-button-set' );
				$rangeSlider       = $thisEl.find( '.fusion-slider-container' );
				$selectField       = $thisEl.find( '.fusion-select-field:not( .fusion-skip-init )' );
				$dimensionField    = $thisEl.find( '.single-builder-dimension' );
				$codeBlock         = $thisEl.find( '.fusion-builder-code-block' );
				$linkButton        = $thisEl.find( '.fusion-builder-link-button' );
				$dateTimePicker    = $thisEl.find( '.fusion-datetime' );
				$multipleImages    = $thisEl.find( '.fusion-multiple-image-container' );
				$repeater          = $thisEl.find( '.fusion-builder-option.repeater' );
				$sortableText      = $thisEl.find( '.fusion-builder-option.sortable_text' );
				$fontFamily        = $thisEl.find( '.fusion-builder-font-family' );

				if ( $textField.length ) {
					$textField.on( 'focus', function( event ) {
						if ( jQuery( event.target ).data( 'placeholder' ) === jQuery( event.target ).val() ) {
							jQuery( event.target ).val( '' );
						}
					} );
				}

				if ( $linkButton.length ) {
					FusionPageBuilderApp.fusionBuilderActivateLinkSelector( $linkButton );
				}

				if ( $dateTimePicker.length ) {
					jQuery( $dateTimePicker ).fusiondatetimepicker( {
						format: 'yyyy-MM-dd hh:mm:ss'
					} );
				}

				// Dynamic data init.
				this.optionDynamicData( $thisEl );

				if ( $colorPicker.length ) {
					$colorPicker.each( function() {
						var self          = $( this ),
							$defaultReset = self.parents( '.fusion-builder-option' ).find( '.fusion-builder-default-reset' ),
							parentValue   = 'undefined' !== typeof parentValues && 'undefined' !== typeof parentValues[ self.attr( 'id' ) ] ? parentValues[ self.attr( 'id' ) ] : false;

						// Picker with default.
						if ( $( this ).data( 'default' ) && $( this ).data( 'default' ).length ) {
							$( this ).wpColorPicker( {
								change: function( event, ui ) {
									that.colorChange( ui.color.toString(), self, $defaultReset, parentValue );
								},
								clear: function( event ) {
									that.colorClear( event, self, parentValue );
								}
							} );

							// Make it so the reset link also clears color.
							$defaultReset.on( 'click', 'a', function( event ) {
								event.preventDefault();
								that.colorClear( event, self, parentValue );
							} );

						// Picker without default.
						} else {
							$( this ).wpColorPicker( {

							} );
						}

						// For some reason non alpha are not triggered straight away.
						if ( true !== $( this ).data( 'alpha' ) ) {
							$( this ).wpColorPicker().change();
						}
					} );
				}

				if ( $multipleImages.length ) {
					$multipleImages.each( function() {
						var $multipleImageContainer = jQuery( this ),
							ids;

						$multipleImageContainer.html( '' );

						if ( 'string' !== typeof $multipleImageContainer.parent().find( '#image_ids' ).val() ) {
							return;
						}

						// Set the media dialog box state as 'gallery' if the element is gallery.
						ids = $multipleImageContainer.parent().find( '#image_ids' ).val().split( ',' );

						// Check which attachments exist.
						jQuery.each( ids, function( index, id ) {
							if ( '' !== id && 'NaN' !== id ) {

								// Doesn't exist need to fetch.
								if ( 'undefined' === typeof wp.media.attachment( id ).get( 'url' ) ) {
									fetchIds.push( id );
								}
							}
						} );

						// Fetch attachments if neccessary.
						if ( 0 < fetchIds.length ) {
							wp.media.query( { post__in: fetchIds, posts_per_page: fetchIds.length } ).more().then( function( response ) { // jshint ignore:line
								that.renderAttachments( ids, $multipleImageContainer );
							} );
						} else {
							that.renderAttachments( ids, $multipleImageContainer );
						}
					} );
				}
				if ( $codeBlock.length ) {
					$codeBlock.each( function() {
						var codeBlockLang;
						if ( 'undefined' === typeof wp.CodeMirror ) {
							return;
						}
						codeBlockId   = $( this ).attr( 'id' );
						codeElement   = $thisEl.find( '#' + codeBlockId );
						codeBlockLang = jQuery( this ).data( 'language' );

						// Get wp.CodeMirror object json.
						codeMirrorJSON = $thisEl.find( '.' + codeBlockId ).val();
						if ( 'undefined' !== typeof codeMirrorJSON ) {
							codeMirrorJSON = jQuery.parseJSON( codeMirrorJSON );
							codeMirrorJSON.lineNumbers = true;
						}
						if ( 'undefined' !== typeof codeBlockLang && 'default' !== codeBlockLang ) {
							codeMirrorJSON.mode = 'text/' + codeBlockLang;
						}

						FusionPageBuilderApp.codeEditor = wp.CodeMirror.fromTextArea( codeElement[ 0 ], codeMirrorJSON );

						// Refresh editor after initialization
						setTimeout( function() {
							FusionPageBuilderApp.codeEditor.refresh();
							FusionPageBuilderApp.codeEditor.focus();
						}, 100 );

					} );
				}

				if ( $dimensionField.length ) {
					$dimensionField.each( function() {
						jQuery( this ).find( '.fusion-builder-dimension input' ).on( 'change paste keyup', function() {
							jQuery( this ).parents( '.single-builder-dimension' ).find( 'input[type="hidden"]' ).val(
								( ( jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(1) input' ).val().length ) ? jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(1) input' ).val() : '0px' ) + ' ' +
								( ( jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(2) input' ).val().length ) ? jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(2) input' ).val() : '0px' ) + ' ' +
								( ( jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(3) input' ).val().length ) ? jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(3) input' ).val() : '0px' ) + ' ' +
								( ( jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(4) input' ).val().length ) ? jQuery( this ).parents( '.single-builder-dimension' ).find( 'div:nth-child(4) input' ).val() : '0px' )
							);
						} );
					} );
				}

				if ( $selectField.length ) {
					$selectField.select2();
				}


				if ( $fontFamily.length ) {
					if ( _.isUndefined( FusionPageBuilderApp.assets ) || _.isUndefined( FusionPageBuilderApp.assets.webfonts ) ) {
						jQuery.when( FusionPageBuilderApp.assets.getWebFonts() ).done( function() {
							self.initAfterWebfontsLoaded( $fontFamily );
						} );
					} else {
						this.initAfterWebfontsLoaded( $fontFamily );
					}
				}

				if ( $uploadButton.length ) {
					FusionPageBuilderApp.FusionBuilderActivateUpload( $uploadButton );
				}

				if ( $iconPicker.length ) {
					$iconPicker.each( function() {
						var $picker = jQuery( this );

						$value     = $picker.find( '.fusion-iconpicker-input' ).val();
						$id        = $picker.find( '.fusion-iconpicker-input' ).attr( 'id' );
						$container = $picker.find( '.icon_select_container' );
						$search    = $picker.find( '.fusion-icon-search' );

						FusionPageBuilderApp.fusion_builder_iconpicker( $value, $id, $container, $search );
					} );
				}

				if ( $multiselect.length ) {

					$multiselect.each( function() {

						$placeholderText = fusionBuilderText.select_options_or_leave_blank_for_all;
						if ( -1 !== jQuery( this ).attr( 'id' ).indexOf( 'cat_slug' ) || -1 !== jQuery( this ).attr( 'id' ).indexOf( 'category' ) ) {
							$placeholderText = fusionBuilderText.select_categories_or_leave_blank_for_all;
						} else if ( -1 !== jQuery( this ).attr( 'id' ).indexOf( 'exclude_cats' ) ) {
							$placeholderText = fusionBuilderText.select_categories_or_leave_blank_for_none;
						}

						jQuery( this ).select2( {
							placeholder: $placeholderText
						} );
					} );
				}

				if ( $checkboxbuttonset.length ) {

					// For the visibility option check if choice is no or yes then convert to new style
					$visibility = $thisEl.find( '.fusion-form-checkbox-button-set.hide_on_mobile' );
					if ( $visibility.length ) {
						$choice = $visibility.find( '.button-set-value' ).val();
						if ( 'no' === $choice || '' === $choice ) {
							$visibility.find( 'a' ).addClass( 'ui-state-active' );
						}
						if ( 'yes' === $choice ) {
							$visibility.find( 'a:not([data-value="small-visibility"])' ).addClass( 'ui-state-active' );
						}
					}

					$checkboxbuttonset.find( 'a' ).on( 'click', function( e ) {
						e.preventDefault();
						$checkboxsetcontainer = jQuery( this ).parents( '.fusion-form-checkbox-button-set' );
						jQuery( this ).toggleClass( 'ui-state-active' );
						$checkboxsetcontainer.find( '.button-set-value' ).val( $checkboxsetcontainer.find( '.ui-state-active' ).map( function( _, el ) {
							return jQuery( el ).data( 'value' );
						} ).get() );
					} );
				}

				if ( $radiobuttonset.length ) {
					$radiobuttonset.find( 'a' ).on( 'click', function( e ) {
						e.preventDefault();
						$radiosetcontainer = jQuery( this ).parents( '.fusion-form-radio-button-set' );
						$subgroupWrapper   = $radiosetcontainer.closest( '.fusion-builder-option.subgroup' ).parent();
						$radiosetcontainer.find( '.ui-state-active' ).removeClass( 'ui-state-active' );
						jQuery( this ).addClass( 'ui-state-active' );
						$radiosetcontainer.find( '.button-set-value' ).val( $radiosetcontainer.find( '.ui-state-active' ).data( 'value' ) ).trigger( 'change' );

						if ( $radiosetcontainer.closest( '.fusion-builder-option.subgroup' ).length ) {
							$subgroupWrapper.find( '.fusion-subgroup-content' ).removeClass( 'active' );
							$subgroupWrapper.find( '.fusion-subgroup-' + $radiosetcontainer.find( '.ui-state-active' ).data( 'value' ) ).addClass( 'active' );
						}
					} );
				}

				// Init sort-able text.
				if ( $sortableText.length ) {
					FusionPageBuilderApp.fusion_builder_sortable_text( $sortableText );
				}

				function createSlider( $slide, $targetId, $rangeInput, $min, $max, $step, $value, $decimals, $rangeDefault, $hiddenValue, $defaultValue, $direction ) {

					// Create slider with values passed on in data attributes.
					var $slider = noUiSlider.create( $rangeSlider[ $slide ], {
							start: [ $value ],
							step: $step,
							direction: $direction,
							range: {
								min: $min,
								max: $max
							},
							format: wNumb( {
								decimals: $decimals
							} )
						} ),
						$notFirst = false;

					// Check if default is currently set.
					if ( $rangeDefault && '' === $hiddenValue.val() ) {
						$rangeDefault.parent().addClass( 'checked' );
					}

					// If this range has a default option then if checked set slider value to data-value.
					if ( $rangeDefault ) {
						$rangeDefault.on( 'click', function( e ) {
							e.preventDefault();
							$rangeSlider[ $slide ].noUiSlider.set( $defaultValue );
							$hiddenValue.val( '' );
							jQuery( this ).parent().addClass( 'checked' );
						} );
					}

					// On slider move, update input
					$slider.on( 'update', function( values, handle ) {
						if ( $rangeDefault && $notFirst ) {
							$rangeDefault.parent().removeClass( 'checked' );
							$hiddenValue.val( values[ handle ] );
						}
						$notFirst = true;
						jQuery( this.target ).closest( '.fusion-slider-container' ).prev().val( values[ handle ] ).trigger( 'change' );
						$thisEl.find( '#' + $targetId ).trigger( 'change' );
					} );

					// On manual input change, update slider position.
					$rangeInput.on( 'blur', function( event ) {

						// If slider already has value, do nothing.
						if ( this.value === $rangeSlider[ $slide ].noUiSlider.get() ) {
							return;
						}

						// This triggers 'update' event.
						if ( $min <= this.value && $max >= this.value ) {
							$rangeSlider[ $slide ].noUiSlider.set( this.value );
						} else if ( $min > this.value ) {
							$rangeSlider[ $slide ].noUiSlider.set( $min );
						} else if ( $max < this.value ) {
							$rangeSlider[ $slide ].noUiSlider.set( $max );
						}

					} );
				}

				if ( $rangeSlider.length ) {

					// Counter variable for sliders
					$i = 0;

					// Method for retreiving decimal places from step
					Number.prototype.countDecimals = function() {
						if ( Math.floor( this.valueOf() ) === this.valueOf() ) {
							return 0;
						}
						return this.toString().split( '.' )[ 1 ].length || 0;
					};

					// Each slider on page, determine settings and create slider
					$rangeSlider.each( function() {

						var $targetId     = jQuery( this ).data( 'id' ),
							$rangeInput   = jQuery( this ).prev( '.fusion-slider-input' ),
							$min          = jQuery( this ).data( 'min' ),
							$max          = jQuery( this ).data( 'max' ),
							$step         = jQuery( this ).data( 'step' ),
							$direction    = jQuery( this ).data( 'direction' ),
							$value        = $rangeInput.val(),
							$decimals     = $step.countDecimals(),
							$rangeDefault = ( jQuery( this ).parents( '.fusion-builder-option' ).find( '.fusion-range-default' ).length ) ? jQuery( this ).parents( '.fusion-builder-option' ).find( '.fusion-range-default' ) : false,
							$hiddenValue  = ( $rangeDefault ) ? jQuery( this ).parent().find( '.fusion-hidden-value' ) : false,
							$defaultValue = ( $rangeDefault ) ? jQuery( this ).parents( '.fusion-builder-option' ).find( '.fusion-range-default' ).data( 'default' ) : false;

						// Check if parent has another value set to override TO default.
						if ( 'undefined' !== typeof parentValues && 'undefined' !== typeof parentValues[ $targetId ] && $rangeDefault ) {

							//  Set default values to new value.
							jQuery( this ).parents( '.fusion-builder-option' ).find( '.fusion-range-default' ).data( 'default', parentValues[ $targetId ] );
							$defaultValue = parentValues[ $targetId ];

							// If no current value is set, also update $value as representation on load.
							if ( ! $hiddenValue || '' === $hiddenValue.val() ) {
								$value = $defaultValue;
							}
						}

						createSlider( $i, $targetId, $rangeInput, $min, $max, $step, $value, $decimals, $rangeDefault, $hiddenValue, $defaultValue, $direction );

						$i++;
					} );

				}

				// TODO: fix for WooCommerce element.
				if ( 'undefined' !== typeof this.model.get && 'fusion_woo_shortcodes' === this.model.get( 'element_type' ) ) {
					if ( true === FusionPageBuilderApp.shortcodeGenerator ) {
						$thisEl.find( '#element_content' ).attr( 'id', 'generator_element_content' );
					}
				}

				// If there is tiny mce editor ( tinymce element option )
				if ( $contentTextarea.length ) {
					$contentTextareaOption = $contentTextarea.closest( '.fusion-builder-option' );

					// Multi element ( parent )
					if ( 'undefined' !== typeof this.model.get( 'multi' ) && 'multi_element_parent' === this.model.get( 'multi' ) ) {

						viewCID = FusionPageBuilderViewManager.generateCid();

						this.view_cid = viewCID;

						$contentTextareaOption.hide();

						$contentTextarea.attr( 'id', 'fusion_builder_content_main' );

						view = new FusionPageBuilder.MultiElementSortablesView( {
							model: this,
							el: this.$el.find( '.fusion-builder-option-advanced-module-settings' ),
							attributes: {
								cid: viewCID,
								parentCid: this.model.get( 'cid' )
							}
						} );

						FusionPageBuilderViewManager.addView( viewCID, view );

						$contentTextareaOption.before( view.render() );

						if ( '' !== $contentTextarea.html() ) {
							view.generateMultiElementChildSortables( $contentTextarea.html(), $thisEl.find( '.fusion-builder-option-advanced-module-settings' ).data( 'element_type' ), fixSettingsLvl, parentAtts );
						}

					// Standard element
					} else {

						content = $contentTextarea.html();

						// Called from shortcode generator
						if ( true === FusionPageBuilderApp.shortcodeGenerator ) {

							// TODO: unique id ( multiple mce )
							if ( true === FusionPageBuilderApp.shortcodeGeneratorMultiElementChild ) {
								$contentTextarea.attr( 'id', 'generator_multi_child_content' );
							} else {
								$contentTextarea.attr( 'id', 'generator_element_content' );
							}

							textareaID = $contentTextarea.attr( 'id' );

							setTimeout( function() {

								$contentTextarea.wp_editor( content, textareaID );

								// If it is a placeholder, add an on focus listener.
								if ( jQuery( '#' + textareaID ).data( 'placeholder' ) ) {
									window.tinyMCE.get( textareaID ).on( 'focus', function() {
										$theContent = window.tinyMCE.get( textareaID ).getContent();
										$theContent = jQuery( '<div/>' ).html( $theContent ).text();
										if ( $theContent === jQuery( '#' + textareaID ).data( 'placeholder' ) ) {
											window.tinyMCE.get( textareaID ).setContent( '' );
										}
									} );
								}

							}, 100 );

						} else {

							textareaID = $contentTextarea.attr( 'id' );

							setTimeout( function() {

								if ( 'undefined' !== typeof thisModel.get( 'allow_generator' ) && true === thisModel.get( 'allow_generator' ) ) {
									allowGenerator = true;
								}

								$contentTextarea.wp_editor( content, textareaID, allowGenerator );

								// If it is a placeholder, add an on focus listener.
								if ( jQuery( '#' + textareaID ).data( 'placeholder' ) ) {
									window.tinyMCE.get( textareaID ).on( 'focus', function() {
										$theContent = window.tinyMCE.get( textareaID ).getContent();
										$theContent = jQuery( '<div/>' ).html( $theContent ).text();
										if ( $theContent === jQuery( '#' + textareaID ).data( 'placeholder' ) ) {
											window.tinyMCE.get( textareaID ).setContent( '' );
										}
									} );
								}

							}, 100 );

						}

					}

				}

				// Init repeaters last.
				if ( $repeater.length ) {
					$repeater.each( function() {
						that.initRepeater( jQuery( this ) );
					} );
				}

				// Attachment upload alert.
				$thisEl.find( '.uploadattachment .fusion-builder-upload-button' ).on( 'click', function() {
					alert( fusionBuilderText.to_add_images );
				} );

				// Range option preview
				FusionPageBuilderApp.rangeOptionPreview( $thisEl );

			},

			beforeRemove: function() {
			},

			removeElement: function() {
				this.beforeRemove();
				// Remove settings modal on save or close/cancel
				this.remove();
			},

			initRepeater: function( $element ) {
				var self       = this,
					param      = $element.data( 'option-id' ),
					option     = fusionAllElements[ this.model.get( 'element_type' ) ].params[ param ],
					fields     = 'undefined' !== typeof option ? option.fields : {},
					params     = this.model.get( 'params' ),
					values     = 'undefined' !== typeof params[ param ] ? params[ param ] : '',
					$target    = $element.find( '.repeater-rows' ),
					rowTitle   = 'undefined' !== typeof option ? option.row_title : false,
					rows       = false;

				if ( 'string' === typeof values && '' !== values ) {
					try {
						if ( FusionPageBuilderApp.base64Encode( FusionPageBuilderApp.base64Decode( values ) ) === values ) {
							values = FusionPageBuilderApp.base64Decode( values );
							values = _.unescape( values );
							values = JSON.parse( values );
							rows   = true;
						}
					} catch ( e ) {
						console.warn( 'Something went wrong! Error triggered - ' + e );
					}
				} else {
					self.createRepeaterRow( fields, {}, $target, rowTitle );
				}

				// Create the rows for existing values.
				if ( 'object' === typeof values && rows ) {
					_.each( values, function( field, index ) {
						self.createRepeaterRow( fields, values[ index ], $target, rowTitle );
					} );
				}

				// Repeater row add click event.
				$element.on( 'click', '.repeater-row-add', function( event ) {
					event.preventDefault();
					self.createRepeaterRow( fields, {}, $target, rowTitle );
				} );

				// Repeater row remove click event.
				$element.on( 'click', '.repeater-row-remove', function( event ) {
					event.preventDefault();
					jQuery( this ).parents( '.repeater-row' ).first().remove();
				} );

				$element.on( 'click', '.repeater-title', function() {
					jQuery( this ).parent().find( '.repeater-fields' ).slideToggle( 300 );
					if ( jQuery( this ).find( '.repeater-toggle-icon' ).hasClass( 'fusiona-plus2' ) ) {
						jQuery( this ).find( '.repeater-toggle-icon' ).removeClass( 'fusiona-plus2' ).addClass( 'fusiona-minus' );
					} else {
						jQuery( this ).find( '.repeater-toggle-icon' ).removeClass( 'fusiona-minus' ).addClass( 'fusiona-plus2' );
					}
				} );

				$element.sortable( {
					handle: '.repeater-title',
					items: '.repeater-row',
					cursor: 'move',
					cancel: '.repeater-row-remove',
					update: function() {
					}
				} );

			},

			createRepeaterRow: function( fields, values, $target, rowTitle ) {
				var $html      = '',
					attributes = {},
					repeater   = FusionPageBuilder.template( jQuery( '#fusion-app-repeater-fields' ).html() ),
					depFields  = {},
					value;

				rowTitle = 'undefined' !== typeof rowTitle && rowTitle ? rowTitle : 'Repeater Row';

				$html += '<div class="repeater-row">';
				$html += '<div class="repeater-title">';
				$html += '<span class="repeater-toggle-icon fusiona-plus2"></span>';
				$html += '<h3>' + rowTitle + '</h3>';
				$html += '<span class="repeater-row-remove fusiona-plus2"></span>';
				$html += '</div>';
				$html += '<ul class="repeater-fields">';

				_.each( fields, function( field ) {
					value = values[ field.param_name ];
					depFields[ field.param_name ] = field;
					attributes = {
						field: field,
						value: value
					};
					$html += jQuery( repeater( attributes ) ).html();
				} );

				$html += '</ul>';
				$html += '</div>';

				this.optionInit( $target.append( $html ).children( 'div:last-child' ) );

				// Check option dependencies
				if ( 'undefined' !== typeof this.model && 'undefined' !== typeof this.model.get ) {
					FusionPageBuilderApp.checkOptionDependency( fusionAllElements[ this.model.get( 'element_type' ) ], $target.children( 'div:last-child' ), false, depFields, this.$el );
				}
			},

			colorChange: function( value, self, defaultReset ) {
				var defaultColor = self.data( 'default' );

				if ( value === defaultColor ) {
					defaultReset.addClass( 'checked' );
				} else {
					defaultReset.removeClass( 'checked' );
				}

				if ( '' === value && null !== defaultColor ) {
					self.val( defaultColor );
					self.change();
					self.val( '' );
				}
			},

			colorClear: function( event, self ) {
				var defaultColor = self.data( 'default' );

				if ( null !== defaultColor ) {
					self.val( defaultColor );
					self.change();
					self.val( '' );
					self.parent().parent().find( '.wp-color-result' ).css( 'background-color', defaultColor );
				}
			},

			renderAttachments: function( ids, $multipleImageContainer ) {
				var $imageHTML,
					attachment,
					imageSizes,
					thumbnail,
					image;

				if ( 0 < ids.length ) {
					jQuery.each( ids, function( index, id ) {
						if ( '' !== id && 'NaN' !== id ) {
							attachment = wp.media.attachment( id );
							imageSizes = attachment.get( 'sizes' );

							if ( 'undefined' !== typeof imageSizes[ '200' ] ) {
								image = imageSizes[ '200' ].url;
							} else if ( 'undefined' !== typeof imageSizes.thumbnail ) {
								image = imageSizes.thumbnail.url;
							} else {
								image = attachment.get( 'url' );
							}

							$imageHTML  = '<div class="fusion-multi-image" data-image-id="' + attachment.get( 'id' ) + '">';
							$imageHTML += '<img src="' + image + '"/>';
							$imageHTML += '<span class="fusion-multi-image-remove dashicons dashicons-no-alt"></span>';
							$imageHTML += '</div>';
							$multipleImageContainer.append( $imageHTML );
						}
					} );
				}
			},

			/**
			 * Create the data for font family and render select field.
			 *
			 * @since 2.2
			 * @param {object} $fontFamily - The option jQuery elements.
			 * @return {Void}
			 */
			initAfterWebfontsLoaded: function( $fontFamily ) {
				var self          = this,
					fonts         = FusionPageBuilderApp.assets.webfonts,
					standardFonts = [],
					googleFonts   = [],
					customFonts   = [],
					data          = [],
					$fusionSelect;

				data.push( {
					id: '',
					text: fusionBuilderText.typography_default
				} );

				// Format standard fonts as an array.
				if ( ! _.isUndefined( fonts.standard ) ) {
					_.each( fonts.standard, function( font ) {
						standardFonts.push( {
							id: font.family.replace( /&quot;/g, '&#39' ),
							text: font.label
						} );
					} );
				}

				// Format google fonts as an array.
				if ( ! _.isUndefined( fonts.google ) ) {
					_.each( fonts.google, function( font ) {
						googleFonts.push( {
							id: font.family,
							text: font.label
						} );
					} );
				}

				// Format custom fonts as an array.
				if ( ! _.isUndefined( fonts.custom ) ) {
					_.each( fonts.custom, function( font ) {
						if ( font.family && '' !== font.family ) {
							customFonts.push( {
								id: font.family.replace( /&quot;/g, '&#39' ),
								text: font.label
							} );
						}
					} );
				}

				// Combine forces and build the final data.
				if ( customFonts[ 0 ] ) {
					data.push( { text: 'Custom Fonts', children: customFonts } );
				}
				data.push( { text: 'Standard Fonts', children: standardFonts } );
				data.push( { text: 'Google Fonts',   children: googleFonts } );

				$fontFamily.each( function() {
					var $familyInput  = jQuery( this ).find( '.input-font_family' );

					$familyInput.select2( {
						data: data
					} );

					jQuery( this ).find( '.font-family' ).addClass( 'loaded' );

					$familyInput.val( $familyInput.data( 'value' ) ).trigger( 'change' );

					self.renderVariant( jQuery( this ) );
					self.renderSubset( jQuery( this ) );

					$familyInput.on( 'change', function() {
						var $wrapper = jQuery( this ).closest( '.fusion-builder-font-family' );

						self.renderVariant( $wrapper );
						self.renderSubset( $wrapper );
					} );
				} );
			},

			/**
			 * Render variant select with relevant choices and hide if should not be shwon.
			 *
			 * @since 2.2
			 * @param {object} $fontOption - The option jQuery element.
			 * @return {Void}
			 */
			renderVariant: function( $fontOption ) {
				var data          = [],
					fontFamily    = $fontOption.find( '.input-font_family' ).val(),
					variants      = this.getVariants( fontFamily ),
					$input        = $fontOption.find( '.input-variant' ),
					value         = 'undefined' !== typeof $input.data( 'value' ) ? $input.data( 'value' ).toString() : false,
					valueExists   = false,
					defaultVal    = $input.data( 'default' ),
					defaultExists = false;

				if ( $input.hasClass( 'select2-hidden-accessible' ) ) {
					value = $input.val();
					$input.select2( 'destroy' ).empty();
				}

				if ( fontFamily && '' !== fontFamily ) {
					$fontOption.find( '.fusion-variant-wrapper' ).show();
				} else {
					$fontOption.find( '.fusion-variant-wrapper' ).hide();
					return;
				}

				_.each( variants, function( scopedVariant ) {

					if ( scopedVariant.id && 'italic' === scopedVariant.id ) {
						scopedVariant.id = '400italic';
					}
					if ( 'function' === typeof scopedVariant.id.toString && scopedVariant.id.toString() === value ) {
						valueExists = true;
					}

					if ( 'function' === typeof scopedVariant.id.toString && 'function' === typeof defaultVal.toString && scopedVariant.id.toString() === defaultVal.toString() ) {
						defaultExists = true;
					}

					data.push( {
						id: scopedVariant.id,
						text: scopedVariant.label
					} );
				} );

				$input.select2( {
					data: data
				} );

				// if no value exists, set to default, otherwise use first.
				if ( ! valueExists && defaultExists ) {
					value = defaultVal;
				} else if ( ! valueExists && 'object' === typeof variants[ 0 ] ) {
					value = variants[ 0 ].id;
				}
				$input.val( value ).trigger( 'change' );
			},

			/**
			 * Render subset select with relevant choices and hide if should not be shwon.
			 *
			 * @since 2.2
			 * @param {object} $fontOption - The option jQuery element.
			 * @return {Void}
			 */
			renderSubset: function( $fontOption ) {
				var data          = [],
					fontFamily    = $fontOption.find( '.input-font_family' ).val(),
					subsets       = this.getSubsets( fontFamily ),
					$input        = $fontOption.find( '.input-subsets' ),
					value         = $input.data( 'value' ),
					valueExists   = false,
					defaultVal    = $input.data( 'default' ),
					defaultExists = false;

				if ( $input.hasClass( 'select2-hidden-accessible' ) ) {
					value = $input.val();
					$input.select2( 'destroy' ).empty();
				}

				if ( fontFamily && '' !== fontFamily && 'object' === typeof subsets ) {
					$fontOption.find( '.fusion-subsets-wrapper' ).show();
				} else {
					$fontOption.find( '.fusion-subsets-wrapper' ).hide();
					return;
				}

				_.each( subsets, function( subset ) {
					if ( subset.id === value ) {
						valueExists = true;
					}
					if ( subset.id === defaultVal ) {
						defaultExists = true;
					}
					data.push( {
						id: subset.id,
						text: subset.label
					} );
				} );

				$input.select2( {
					data: data
				} );

				// if no value exists, set to default, otherwise use first.
				if ( ! valueExists && defaultExists ) {
					value = defaultVal;
				} else if ( ! valueExists && 'object' === typeof subsets[ 0 ] ) {
					value = subsets[ 0 ].id;
				}
				$input.val( value ).trigger( 'change' );
			},

			/**
			 * Get variants for a font-family.
			 *
			 * @since 2.2
			 * @param {string} fontFamily - The font-family name.
			 * @return {Object} - Returns the variants for the selected font-family.
			 */
			getVariants: function( fontFamily ) {
				var variants = false;

				if ( this.isCustomFont( fontFamily ) ) {
					return [
						{
							id: '400',
							label: 'Normal 400'
						}
					];
				}

				_.each( FusionPageBuilderApp.assets.webfonts.standard, function( font ) {
					if ( fontFamily && font.family === fontFamily ) {
						variants = font.variants;
						return font.variants;
					}
				} );

				_.each( FusionPageBuilderApp.assets.webfonts.google, function( font ) {
					if ( font.family === fontFamily ) {
						variants = font.variants;
						return font.variants;
					}
				} );
				return variants;
			},

			/**
			 * Get subsets for a font-family.
			 *
			 * @since 2.2
			 * @param {string} fontFamily - The font-family.
			 * @return {Object} - Returns the subsets for the current font-family.
			 */
			getSubsets: function( fontFamily ) {

				var subsets = false,
					fonts   = FusionPageBuilderApp.assets.webfonts;

				_.each( fonts.google, function( font ) {
					if ( font.family === fontFamily ) {
						subsets = font.subsets;
					}
				} );
				return subsets;
			},

			/**
			 * Check if a font-family is a custom font or not.
			 *
			 * @since 2.2
			 * @param {string} family - The font-family to check.
			 * @return {boolean} - Whether the font-family is a custom font or not.
			 */
			isCustomFont: function( family ) {
				var isCustom = false;

				// Figure out if this is a google-font.
				_.each( FusionPageBuilderApp.assets.webfonts.custom, function( font ) {
					if ( font.family === family ) {
						isCustom = true;
					}
				} );

				return isCustom;
			}
		} );

		_.extend( FusionPageBuilder.ElementSettingsView.prototype, FusionPageBuilder.options.fusionDynamicData );
	} );
}( jQuery ) );
