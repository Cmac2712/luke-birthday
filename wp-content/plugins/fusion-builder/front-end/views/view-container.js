/* global FusionApp, cssua, FusionPageBuilderApp, FusionPageBuilderViewManager, fusionAllElements, fusionBuilderText, FusionEvents, FusionPageBuilderElements */
/* jshint -W020 */
/* eslint no-shadow: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Builder Container View
		FusionPageBuilder.ContainerView = FusionPageBuilder.BaseView.extend( {

			template: FusionPageBuilder.template( jQuery( '#fusion-builder-container-template' ).html() ),
			className: function() {
				var classes = 'fusion-builder-container fusion-builder-data-cid',
					values  = _.fusionCleanParameters( jQuery.extend( true, {}, this.model.get( 'params' ) ) );

				if ( 'yes' === values.hundred_percent_height_scroll && 'yes' === values.hundred_percent_height ) {
					classes += ' scrolling-helper';
				}
				return classes;
			},
			events: {
				'click .fusion-builder-container-settings': 'settings',
				'click .fusion-builder-container-remove': 'removeContainer',
				'click .fusion-builder-container-clone': 'cloneContainer',
				'click .fusion-builder-container-add': 'addContainer',
				'click .fusion-builder-container-save': 'openLibrary',
				'paste .fusion-builder-section-name': 'renameContainer',
				'keydown .fusion-builder-section-name': 'renameContainer',
				'click .fusion-builder-toggle': 'toggleContainer',
				'click .fusion-builder-publish-tooltip': 'publish',
				'click .fusion-builder-unglobal-tooltip': 'unglobalize',
				'click .fusion-builder-container-drag': 'preventDefault'
			},

			/**
			 * Init.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			initialize: function() {
				var cid = this.model.get( 'cid' ),
					el  = this.$el;

				el.attr( 'data-cid', cid );
				el.attr( 'id', 'fusion-container-' + cid );

				if ( 'undefined' !== typeof this.model.attributes.params && 'undefined' !== typeof this.model.attributes.params.fusion_global ) {
					el.attr( 'fusion-global-layout', this.model.attributes.params.fusion_global );
					this.$el.removeClass( 'fusion-global-container' ).addClass( 'fusion-global-container' );
				}

				this.listenTo( FusionEvents, 'fusion-view-update-fusion_builder_container', this.reRender );
				this.listenTo( FusionEvents, 'fusion-param-changed-' + this.model.get( 'cid' ), this.onOptionChange );

				this._triggerCallback = _.debounce( _.bind( this.triggerCallback, this ), 200 );

				this.model.children = new FusionPageBuilder.Collection();
				this.listenTo( this.model.children, 'add', this.addChildView );

				this.listenTo( FusionEvents, 'fusion-wireframe-toggle', this.wireFrameToggled );

				this.renderedYet          = FusionPageBuilderApp.loaded;
				this._refreshJs           = _.debounce( _.bind( this.refreshJs, this ), 300 );
				this._triggerScrollUpdate = _.debounce( _.bind( this.triggerScrollUpdate, this ), 300 );

				this.typingTimer; // jshint ignore:line
				this.doneTypingInterval = 800;

				this.scrollingSections = false;

				this.settingsControlsOffset = 0;
				this.width = el.width();
				el.on( 'hover', _.bind( this.setSettingsControlsOffset, this ) );
				this.correctStackingContextForFilters();

				this.deprecatedParams();

				this.baseInit();
			},

			/**
			 * Set correct top offset for the container setting controls.
			 *
			 * @since 2.0
			 * @param {boolean} forced - Whether to force an update and bypass checks.
			 * @return {void}
			 */
			setSettingsControlsOffset: function( forced ) {
				if ( ( 'undefined' !== typeof forced || 0 === this.settingsControlsOffset || this.width !== this.$el.width() ) && 'undefined' !== typeof window.frames[ 0 ].getStickyHeaderHeight ) {
					this.settingsControlsOffset = 'off' !== FusionApp.preferencesData.sticky_header ? ( window.frames[ 0 ].getStickyHeaderHeight( true ) + 15 ) + 'px' : '15px';
					this.width = this.$el.width();

					this.$el.find( '.fusion-builder-module-controls-container-wrapper .fusion-builder-module-controls-type-container' ).css( 'top', this.settingsControlsOffset );
				}

				if ( this.$el.find( '.fusion-builder-empty-container' ).is( ':visible' ) ) {
					this.$el.find( '.fusion-builder-module-controls-container-wrapper .fusion-builder-module-controls-type-container' ).css( 'margin-top', '8.5px' );
				} else {
					this.$el.find( '.fusion-builder-module-controls-container-wrapper .fusion-builder-module-controls-type-container' ).css( 'margin-top', '' );
				}
			},

			/**
			 * Corrects the stacking context if filters are used, to make all elements accessible.
			 *
			 * @since 2.2
			 * @return {void}
			 */
			correctStackingContextForFilters: function() {
				var parent = this.$el;


				this.$el.on( 'mouseenter', '.fusion-fullwidth', function() {
					if ( 'none' !== jQuery( this ).css( 'filter' ) ) {
						parent.addClass( 'fusion-has-filters' );
					}
				} );

				this.$el.on( 'mouseleave', '.fusion-fullwidth', function() {
					if ( ! parent.hasClass( 'fusion-container-editing-child' ) ) {
						parent.removeClass( 'fusion-has-filters' );
					}
				} );
			},

			/**
			 * Renders the view.
			 *
			 * @since 2.0.0
			 * @return {Object} this
			 */
			render: function() {
				var self = this,
					data = this.getTemplateAtts();

				this.$el.html( this.template( data ) );
				this.appendChildren();

				if ( this.renderedYet ) {
					this._refreshJs();

					// Trigger equal height columns js
					jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-option-change-equal_height_columns', this.model.attributes.cid );
				}

				if ( 'undefined' !== typeof this.model.attributes.params.admin_toggled && 'yes' === this.model.attributes.params.admin_toggled ) {
					this.$el.addClass( 'fusion-builder-section-folded' );
					this.$el.find( '.fusion-builder-toggle > span' ).toggleClass( 'fusiona-caret-up' ).toggleClass( 'fusiona-caret-down' );
				}

				this.onRender();

				this.renderedYet = true;

				setTimeout( function() {
					self.droppableContainer();
				}, 100 );

				this._triggerScrollUpdate();

				return this;
			},

			/**
			 * Adds drop zones for continers and makes container draggable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			droppableContainer: function() {

				var $el   = this.$el,
					cid   = this.model.get( 'cid' ),
					$body = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' );

				if ( ! $el ) {
					return;
				}

				$el.draggable( {
					appendTo: FusionPageBuilderApp.$el,
					zIndex: 999999,
					delay: 100,
					cursorAt: { top: 15, left: 15 },
					iframeScroll: true,
					containment: $body,
					cancel: '.fusion-builder-column',
					helper: function() {
						var $classes = FusionPageBuilderApp.DraggableHelpers.draggableClasses( cid );
						return jQuery( '<div class="fusion-container-helper ' + $classes + '" data-cid="' + cid + '"><span class="fusiona-container"></span></div>' );
					},
					start: function() {
						$body.addClass( 'fusion-container-dragging fusion-active-dragging' );
						$el.addClass( 'fusion-being-dragged' );

						//  Add a class to hide the unnecessary target after.
						if ( $el.prev( '.fusion-builder-container' ).length ) {
							$el.prev( '.fusion-builder-container' ).addClass( 'hide-target-after' );
						}

						if ( $el.prev( '.fusion-fusion-builder-next-pager' ).length ) {
							$el.prev( '.fusion-fusion-builder-next-page' ).addClass( 'hide-target-after' );
						}
					},
					stop: function() {
						setTimeout( function() {
							$body.removeClass( 'fusion-container-dragging fusion-active-dragging' );
						}, 10 );
						$el.removeClass( 'fusion-being-dragged' );
						FusionPageBuilderApp.$el.find( '.hide-target-after' ).removeClass( 'hide-target-after' );
					}
				} );

				$el.find( '.fusion-container-target' ).droppable( {
					tolerance: 'touch',
					hoverClass: 'ui-droppable-active',
					accept: '.fusion-builder-container, .fusion-builder-next-page',
					drop: function( event, ui ) {

						// Move the actual html.
						if ( jQuery( event.target ).hasClass( 'target-after' ) ) {
							$el.after( ui.draggable );
						} else {
							$el.before( ui.draggable );
						}

						FusionEvents.trigger( 'fusion-content-changed' );

						FusionPageBuilderApp.scrollingContainers();

						FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.full_width_section + ' order changed' );
					}
				} );

				// If we are in wireframe mode, then disable.
				if ( FusionPageBuilderApp.wireframeActive ) {
					this.disableDroppableContainer();
				}
			},

			/**
			 * Enable the droppable and draggable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			enableDroppableContainer: function() {
				var $el = this.$el;

				if ( 'undefined' !== typeof $el.draggable( 'instance' ) && 'undefined' !== typeof $el.find( '.fusion-container-target' ).droppable( 'instance' ) ) {
					$el.draggable( 'enable' );
					$el.find( '.fusion-container-target' ).droppable( 'enable' );
				} else {

					// No sign of init, then need to call it.
					this.droppableContainer();
				}
			},

			/**
			 * Destroy or disable the droppable and draggable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			disableDroppableContainer: function() {
				var $el = this.$el;

				// If its been init, just disable.
				if ( 'undefined' !== typeof $el.draggable( 'instance' ) ) {
					$el.draggable( 'disable' );
				}

				// If its been init, just disable.
				if ( 'undefined' !== typeof $el.find( '.fusion-container-target' ).droppable( 'instance' ) ) {
					$el.find( '.fusion-container-target' ).droppable( 'disable' );
				}
			},

			/**
			 * Fired when wireframe mode is toggled.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			wireFrameToggled: function() {
				if ( FusionPageBuilderApp.wireframeActive ) {
					this.disableDroppableContainer();
				} else {
					this.enableDroppableContainer();
				}
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

			/**
			 * Remove deprecated params.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			deprecatedParams: function() {
				var params               = this.model.get( 'params' ),
					defaults             = fusionAllElements.fusion_builder_container.defaults,
					values               = jQuery.extend( true, {}, defaults, params ),
					alphaBackgroundColor = 1,
					radiaDirectionsNew   = { 'bottom': 'center bottom', 'bottom center': 'center bottom', 'left': 'left center', 'right': 'right center', 'top': 'center top', 'center': 'center center', 'center left': 'left center' };

				params = _.fusionContainerMapDeprecatedArgs( params );

				// If no blend mode is defined, check if we should set to overlay.
				if ( 'undefined' === typeof params.background_blend_mode && '' !== values.background_color  ) {
					alphaBackgroundColor = jQuery.Color( values.background_color ).alpha();
					if ( 1 > alphaBackgroundColor && 0 !== alphaBackgroundColor && ( '' !== params.background_image || '' !== params.video_bg ) ) {
						params.background_blend_mode = 'overlay';
					}
				}

				// Correct radial direction params.
				if ( 'undefined' !== typeof params.radial_direction && ( params.radial_direction in radiaDirectionsNew ) ) {
					params.radial_direction = radiaDirectionsNew[ values.radial_direction ];
				}

				this.model.set( 'params', params );
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
			 * Get template attributes.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			getTemplateAtts: function() {
				var element            = fusionAllElements[ this.model.get( 'element_type' ) ],
					templateAttributes = jQuery.extend( true, {}, this.model.attributes ),
					params             = jQuery.extend( true, {}, this.model.get( 'params' ) ),
					values             = {},
					extras             = {},
					style              = '',
					defaults           = fusionAllElements.fusion_builder_container.defaults,
					classes            = 'fusion-fullwidth fullwidth-box fusion-builder-row-live-' + this.model.get( 'cid' ),
					outerHTML          = '',
					videoBg            = false,
					videoSrc           = '',
					parallaxHelper     = '',
					parallaxData       = '',
					paddings           = [ 'top', 'right', 'bottom', 'left' ],
					alphaBackgroundColor,
					topOverlap,
					bottomOverlap,
					videoUrl,
					videoAttributes,
					videoPreviewImageStyle,
					overlayStyle = '',
					fadeStyle,
					paddingName,
					contentStyle = '',
					id = '',
					loop,
					centerContentClass = '',
					cid = this.model.get( 'cid' ),
					bgColor,
					stylePrefix,
					linkExclusionSelectors,
					styleBlock = '';

				extras = jQuery.extend( true, {}, fusionAllElements.fusion_builder_container.extras );

				// If 100 page template.
				if ( FusionPageBuilderApp.$el.find( '#main' ).hasClass( 'width-100' ) && 'undefined' !== typeof extras.container_padding_100 ) {
					defaults.padding_top    = extras.container_padding_100.top;
					defaults.padding_right  = extras.container_padding_100.right;
					defaults.padding_bottom = extras.container_padding_100.bottom;
					defaults.padding_left   = extras.container_padding_100.left;
				} else if ( ! FusionPageBuilderApp.$el.find( '#main' ).hasClass( 'width-100' ) && 'undefined' !== typeof extras.container_padding_default ) {
					defaults.padding_top    = extras.container_padding_default.top;
					defaults.padding_right  = extras.container_padding_default.right;
					defaults.padding_bottom = extras.container_padding_default.bottom;
					defaults.padding_left   = extras.container_padding_default.left;
				}

				params = _.fusionCleanParameters( params );

				// Set values & extras
				if ( element && 'undefined' !== typeof element.defaults ) {
					values = jQuery.extend( true, {}, defaults, params );
				}

				values = this.getDynamicAtts( values );

				// Videos.
				if ( 'undefined' !== typeof values.video_mp4 && '' !== values.video_mp4 ) {
					videoSrc += '<source src="' + values.video_mp4 + '" type="video/mp4">';
					videoBg   = true;
				}

				if ( 'undefined' !== typeof values.video_webm && '' !== values.video_webm ) {
					videoSrc += '<source src="' + values.video_webm + '" type="video/webm">';
					videoBg   = true;
				}

				if ( 'undefined' !== typeof values.video_ogv && '' !== values.video_ogv ) {
					videoSrc += '<source src="' + values.video_ogv + '" type="video/ogg">';
					videoBg   = true;
				}

				if ( 'undefined' !== typeof values.video_url && '' !== values.video_url ) {
					videoBg = true;
				}

				alphaBackgroundColor = jQuery.Color( values.background_color ).alpha();

				if ( true === videoBg ) {

					classes += ' video-background';

					if ( '' !== values.video_url ) {
						videoUrl = _.fusionGetVideoProvider( values.video_url );
						loop     = ( 'yes' === values.video_loop ? 1 : 0 );
						if ( 'youtube' === videoUrl.type ) {
							outerHTML += '<div style=\'opacity:0;\' class=\'fusion-background-video-wrapper\' id=\'video-' + cid + '\' data-youtube-video-id=\'' + videoUrl.id + '\' data-mute=\'' + values.video_mute + '\' data-loop=\'' + loop + '\' data-loop-adjustment=\'' + values.video_loop_refinement + '\' data-video-aspect-ratio=\'' + values.video_aspect_ratio + '\'><div class=\'fusion-container-video-bg\' id=\'video-' + cid + '-inner\'></div></div>';
						} else if ( 'vimeo' === videoUrl.type ) {
							outerHTML += '<div id="video-' + cid + '" data-vimeo-video-id="' + videoUrl.id + '" data-mute="' + values.video_mute + '" data-video-aspect-ratio="' + values.video_aspect_ratio + ' }}" style="visibility:hidden;"><iframe id="video-iframe-' + cid + '" src="//player.vimeo.com/video/' + videoUrl.id + '?api=1&player_id=video-iframe-' + cid + '&html5=1&autopause=0&autoplay=1&badge=0&byline=0&loop=' + loop + '&title=0" frameborder="0"></iframe></div>';
						}
					} else {
						videoAttributes = 'preload="auto" autoplay playsinline';

						if ( 'yes' === values.video_loop ) {
							videoAttributes += ' loop';
						}

						if ( 'yes' === values.video_mute ) {
							videoAttributes += ' muted';
						}

						// Video Preview Image.
						if ( '' !== values.video_preview_image ) {
							videoPreviewImageStyle = 'background-image: url(\'' + values.video_preview_image + '\');';
							outerHTML += '<div class="fullwidth-video-image" style="' + videoPreviewImageStyle + '"></div>';
						}

						outerHTML += '<div class="fullwidth-video"><video ' + videoAttributes + '>' + videoSrc + '</video></div>';
					}

					// Video Overlay.
					if ( '' !== _.getGradientString( values ) ) {
						overlayStyle += 'background-image:' + _.getGradientString( values ) + ';';
					}

					if ( '' !== values.background_color && 1 > alphaBackgroundColor ) {
						overlayStyle += 'background-color:' + values.background_color + ';';
					}

					if ( '' !== overlayStyle ) {
						outerHTML   += '<div class="fullwidth-overlay" style="' + overlayStyle + '"></div>';
					}
				}

				if ( cssua.ua.ie || cssua.ua.edge ) {
					if ( 1 > alphaBackgroundColor ) {
						classes += ' fusion-ie-mode';
					}
				}

				// Background.
				if ( '' !== values.background_color && ! ( 'yes' === values.fade && '' !== values.background_image && false === videoBg ) ) {
					style += 'background-color: ' + values.background_color + ';';
				}

				if ( '' !== values.background_image && 'yes' !== values.fade ) {
					style += 'background-image: url(\'' + values.background_image + '\');';
				}

				if ( '' !== _.getGradientString( values, 'main_bg' ) ) {
					style += 'background-image: ' + _.getGradientString( values, 'main_bg' ) + ';';
				}

				if ( '' !== values.background_position ) {
					style += 'background-position: ' + values.background_position + ';';
				}

				if ( '' !== values.background_repeat ) {
					style += 'background-repeat: ' + values.background_repeat + ';';
				}

				if ( 'none' !== values.background_blend_mode ) {
					style += 'background-blend-mode: ' + values.background_blend_mode + ';';
				}

				// Get correct container padding.
				jQuery.each( paddings, function( index, padding ) {
					paddingName = 'padding_' + padding;

					// Fall back to px if no unit is set.
					if ( 'undefined' !== typeof values[ paddingName ] && false === values[ paddingName ].indexOf( '%' ) && false === values[ paddingName ].indexOf( 'px' ) ) {
						values[ paddingName ] += 'px';
					}

					// Add padding to style.
					if ( '' !== values[ paddingName ] ) {
						if ( 'yes' === values.hundred_percent_height && 'yes' === values.hundred_percent_height_center_content ) {
							contentStyle += 'padding-' + padding + ':' + _.fusionCheckValue( values[ paddingName ] ) + ';';
						}
						style += 'padding-' + padding + ':' + _.fusionCheckValue( values[ paddingName ] ) + ';';
					}
				} );

				// Margin; for separator conversion only.
				if ( '' !== values.margin_bottom ) {
					style += 'margin-bottom: ' + _.fusionCheckValue( values.margin_bottom ) + ';';
				}

				if ( '' !== values.margin_top ) {
					style += 'margin-top: ' + _.fusionCheckValue( values.margin_top ) + ';';
				}

				// Border.
				if ( '' === values.border_size ) {
					values.border_size = 0;
				}
				style += 'border-top-width:' + _.fusionValidateAttrValue( values.border_size, 'px' ) + ';';
				style += 'border-bottom-width:' + _.fusionValidateAttrValue( values.border_size, 'px' ) + ';';
				style += 'border-color:' + values.border_color + ';';
				style += 'border-top-style:' + values.border_style + ';';
				style += 'border-bottom-style:' + values.border_style + ';';

				// Fading Background.
				if ( 'yes' === values.fade && '' !== values.background_image && false === videoBg ) {
					fadeStyle = '';
					classes  += ' faded-background';

					if ( values.background_parallax ) {
						fadeStyle += 'background-attachment:' + values.background_parallax + ';';
					}

					if ( values.background_color ) {
						fadeStyle += 'background-color:' + values.background_color + ';';
					}

					if ( values.background_image ) {
						fadeStyle += 'background-image: url(' + values.background_image + ');';
					}

					if ( '' !== _.getGradientString( values, 'fade' ) ) {
						fadeStyle += 'background-image: ' + _.getGradientString( values, 'fade' ) + ';';
					}

					if ( values.background_position ) {
						fadeStyle += 'background-position:' + values.background_position + ';';
					}

					if ( values.background_repeat ) {
						fadeStyle += 'background-repeat:' + values.background_repeat + ';';
					}

					if ( 'none' !== values.background_blend_mode ) {
						fadeStyle += 'background-blend-mode: ' + values.background_blend_mode + ';';
					}

					if ( 'no-repeat' === values.background_repeat ) {
						fadeStyle += '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
					}

					outerHTML += '<div class="fullwidth-faded" style="' + fadeStyle + '"></div>';
				}

				if ( '' !== values.background_image && false === videoBg ) {
					if ( 'no-repeat' === values.background_repeat ) {
						style += '-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;';
					}
				}

				// Remove old parallax bg.
				if ( this.$el.find( '.fusion-bg-parallax' ).length ) {
					if ( 'undefined' !== typeof this.$el.find( '.fusion-bg-parallax' ).data( 'parallax-index' ) ) {
						jQuery( '#fb-preview' )[ 0 ].contentWindow._fusionImageParallaxImages.splice( this.$el.find( '.fusion-bg-parallax' ).data( 'parallax-index' ), 1 );
					}

					this.$el.find( '.fusion-bg-parallax' ).remove();
					this.$el.find( '.parallax-inner' ).remove();
				}

				// Parallax.
				if ( false === videoBg && '' !== values.background_image ) {
					parallaxData += ' data-bg-align="' + values.background_position + '"';
					parallaxData += ' data-direction="' + values.background_parallax + '"';
					parallaxData += ' data-mute="' + ( 'mute' === values.video_mute ? 'true' : 'false' ) + '"';
					parallaxData += ' data-opacity="' + values.opacity + '"';
					parallaxData += ' data-velocity="' + ( values.parallax_speed * -1 ) + '"';
					parallaxData += ' data-mobile-enabled="' + ( 'yes' === values.enable_mobile ? 'true' : 'false' ) + '"';
					parallaxData += ' data-break_parents="' + values.break_parents + '"';
					parallaxData += ' data-bg-image="' + values.background_image + '"';
					parallaxData += ' data-bg-repeat="' + ( '' !== values.background_repeat && 'no-repeat' !== values.background_repeat ? 'true' : 'false' ) + '"';
					parallaxData += ' data-bg-height="' + values.data_bg_height + '"';
					parallaxData += ' data-bg-width="' + values.data_bg_width + '"';

					bgColor = jQuery.Color( values.background_color ).alpha();
					if ( 0 !== bgColor ) {
						parallaxData += ' data-bg-color="' + values.background_color + '"';
					}

					if ( 'none' !== values.background_blend_mode ) {
						parallaxData += ' data-blend-mode="' + values.background_blend_mode + '"';
					}
					parallaxData += ' data-bg-alpha="' + bgColor + '"';

					if ( '' !== values.gradient_start_color || '' !== values.gradient_end_color ) {
						parallaxData += ' data-bg-gradient-type="' + values.gradient_type + '"';
						parallaxData += ' data-bg-gradient-angle="' + values.linear_angle + '"';
						parallaxData += ' data-bg-gradient-start-color="' + values.gradient_start_color + '"';
						parallaxData += ' data-bg-gradient-start-position="' + values.gradient_start_position + '"';
						parallaxData += ' data-bg-gradient-end-color="' + values.gradient_end_color + '"';
						parallaxData += ' data-bg-gradient-end-position="' + values.gradient_end_position + '"';
						parallaxData += ' data-bg-radial-direction="' + values.radial_direction + '"';
					}

					if ( 'none' !== values.background_parallax && 'fixed' !== values.background_parallax ) {
						parallaxHelper = '<div class="fusion-bg-parallax" ' + parallaxData + '></div>';
					}

					// Parallax css class+
					if ( '' !== values.background_parallax ) {
						classes += ' fusion-parallax-' + values.background_parallax;
					}

					if ( values.background_parallax ) {
						style += 'background-attachment:' + values.background_parallax + ';';
					}
				}

				// Custom CSS class+
				if ( '' !== values[ 'class' ] ) {
					classes += ' ' + values[ 'class' ];
				}

				classes += ( 'yes' === values.hundred_percent ) ? ' hundred-percent-fullwidth' : ' nonhundred-percent-fullwidth';

				classes += ( 'yes' === values.hundred_percent_height_scroll && 'yes' === values.hundred_percent_height ) ? ' fusion-scrolling-section-edit' : '';
				classes += ( 'yes' === values.hundred_percent_height ) ? ' non-hundred-percent-height-scrolling' : '';
				classes += ( 'yes' === values.hundred_percent_height && 'yes' !== values.hundred_percent_height_center_content ) ? ' hundred-percent-height' : '';
				classes += ( 'yes' === values.hundred_percent_height && 'yes' === values.hundred_percent_height_center_content ) ? ' hundred-percent-height-center-content' : '';

				// Equal column height.
				if ( 'yes' === values.equal_height_columns ) {
					classes += ' fusion-equal-height-columns';
				}

				// Hundred percent height and centered content, if added to centerContentClass then the padding makes the container too large.
				if ( 'yes' === values.hundred_percent_height && 'yes' === values.hundred_percent_height_center_content ) {
					classes += ' hundred-percent-height non-hundred-percent-height-scrolling';
				}

				values.margin_bottom = '' === values.margin_bottom ? '0px' : values.margin_bottom;
				values.padding_top   = '' === values.padding_top ? '0px' : values.padding_top;

				topOverlap           = ( 20 > parseInt( values.padding_top, 10 ) && ( '0%' === values.padding_top || -1 === values.padding_top.indexOf( '%' ) ) ) ? 'fusion-overlap' : '';
				bottomOverlap        = ( 20 > parseInt( values.margin_bottom, 10 ) && ( '0%' === values.margin_bottom || -1 === values.margin_bottom.indexOf( '%' ) ) ) ? 'fusion-overlap' : '';

				// Visibility classes.
				classes = _.fusionVisibilityAtts( values.hide_on_mobile, classes );

				// CSS inline style.
				style = ( '' !== style ) ? ' style="' + style + '"' : '';

				// Custom CSS ID.
				if ( '' !== values.id ) {
					id = values.id;
				}

				stylePrefix = '.fusion-fullwidth.fusion-builder-row-live-' + cid + ' .fusion-builder-element-content';
				linkExclusionSelectors = ' a:not(.fusion-button):not(.fusion-builder-module-control):not(.fusion-social-network-icon):not(.fb-icon-element):not(.fusion-countdown-link):not(.fusion-rollover-link):not(.fusion-rollover-gallery):not(.add_to_cart_button):not(.show_details_button):not(.product_type_external):not(.fusion-quick-view):not(.fusion-rollover-title-link):not(.fusion-breadcrumb-link)';


				if ( 'undefined' !== typeof params.link_color && '' !== params.link_color ) {
					styleBlock += stylePrefix + linkExclusionSelectors + ', ';
					styleBlock += stylePrefix + linkExclusionSelectors + ':before, ';
					styleBlock += stylePrefix + linkExclusionSelectors + ':after ';
					styleBlock += '{color: ' + params.link_color + ';}';
				}

				if ( 'undefined' !== typeof params.link_hover_color && '' !== params.link_hover_color ) {
					styleBlock += stylePrefix + linkExclusionSelectors + ':hover, ' + stylePrefix + linkExclusionSelectors + ':hover:before, ' + stylePrefix + linkExclusionSelectors + ':hover:after {color: ' + params.link_hover_color + ';}';
					styleBlock += stylePrefix + ' .pagination a.inactive:hover, ' + stylePrefix + ' .fusion-filters .fusion-filter.fusion-active a {border-color: ' + params.link_hover_color + ';}';
					styleBlock += stylePrefix + ' .pagination .current {border-color: ' + params.link_hover_color + '; background-color: ' + params.link_hover_color + ';}';
					styleBlock += stylePrefix + ' .fusion-filters .fusion-filter.fusion-active a, ' + stylePrefix + ' .fusion-date-and-formats .fusion-format-box, ' + stylePrefix + ' .fusion-popover, ' + stylePrefix + ' .tooltip-shortcode {color: ' + params.link_hover_color + ';}';
					styleBlock += '#main ' + stylePrefix + ' .post .blog-shortcode-post-title a:hover {color: ' + params.link_hover_color + ';}';
				}

				if ( '' !== styleBlock ) {
					styleBlock = '<style type="text/css">' + styleBlock + '</style>';
				}

				templateAttributes.extras = extras;
				templateAttributes.id = id;
				templateAttributes.classes = classes;
				templateAttributes.style = style;
				templateAttributes.outer_html = outerHTML;
				templateAttributes.parallax_helper = parallaxHelper;
				templateAttributes.menu_anchor = values.menu_anchor;
				templateAttributes.status = values.status;
				templateAttributes.cid = this.model.get( 'cid' );
				templateAttributes.content_styles = values.content_styles;
				templateAttributes.hundred_percent_height = values.hundred_percent_height;
				templateAttributes.hundred_percent_height_center_content = values.hundred_percent_height_center_content;
				templateAttributes.topOverlap = topOverlap;
				templateAttributes.bottomOverlap = bottomOverlap;
				templateAttributes.admin_label = ( '' !== values.admin_label ) ? _.unescape( values.admin_label ) : fusionBuilderText.full_width_section;
				templateAttributes.centerContentClass = centerContentClass;
				templateAttributes.contentStyle = contentStyle;
				templateAttributes.hundred_percent_height_scroll = values.hundred_percent_height_scroll;
				templateAttributes.isGlobal = ( 'undefined' !== typeof values.fusion_global ) ? 'yes' : 'no';
				templateAttributes.scrollPosition = ( 'right' === FusionApp.settings.header_position || jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).hasClass( 'rtl' ) ) ? 'scroll-navigation-left' : 'scroll-navigation-right';
				templateAttributes.styleBlock = styleBlock + _.fusionGetFilterStyleElem( values, '.fusion-builder-row-live-' + cid, cid  );

				return templateAttributes;
			},

			triggerScrollUpdate: function() {
				setTimeout( function() {
					FusionPageBuilderApp.scrollingContainers();
				}, 100 );
			},

			beforePatch: function() {
				if ( this.$el.find( '.fusion-bg-parallax' ).length ) {
					if ( 'object' === typeof jQuery( '#fb-preview' )[ 0 ].contentWindow._fusionImageParallaxImages && 'undefined' !== typeof this.$el.find( '.fusion-bg-parallax' ).attr( 'data-parallax-index' ) ) {
						jQuery( '#fb-preview' )[ 0 ].contentWindow._fusionImageParallaxImages.splice( this.$el.find( '.fusion-bg-parallax' ).attr( 'data-parallax-index' ), 1 );
					}
				}
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0.0
			 * @return null
			 */
			afterPatch: function() {
				var self = this;

				this.appendChildren();

				// Using non debounced version for smoothness.
				this.refreshJs();

				this._triggerScrollUpdate();

				setTimeout( function() {
					self.droppableContainer();
				}, 100 );

				if ( 'yes' === this.model.attributes.params.hundred_percent_height && 'yes' === this.model.attributes.params.hundred_percent_height_scroll ) {
					this.$el.addClass( 'scrolling-helper' );
				} else {
					this.$el.removeClass( 'scrolling-helper' );
				}

				this.setSettingsControlsOffset( true );
			},

			/**
			 * Triggers a refresh.
			 *
			 * @since 2.0.0
			 * @return void
			 */
			refreshJs: function( cid ) {
				cid = 'undefined' === typeof cid ? this.model.attributes.cid : cid;
				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-element-render-fusion_builder_container', cid );
				jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( 'body' ).trigger( 'fusion-reinit-carousels', cid );
			},

			/**
			 * Adds a container.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The event.
			 * @return {void}
			 */
			addContainer: function( event ) {
				var elementID,
					defaultParams,
					params,
					value,
					newContainer;

				if ( event ) {
					event.preventDefault();
					FusionPageBuilderApp.newContainerAdded = true;
				}

				elementID     = FusionPageBuilderViewManager.generateCid();
				defaultParams = fusionAllElements.fusion_builder_container.params;
				params        = {};

				// Process default options for shortcode.
				_.each( defaultParams, function( param )  {
					value = ( _.isObject( param.value ) ) ? param[ 'default' ] : param.value;
					params[ param.param_name ] = value;

					if ( 'dimension' === param.type && _.isObject( param.value ) ) {
						_.each( param.value, function( val, name )  {
							params[ name ] = val;
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

				// Make sure to add row to new container not current one.
				newContainer = FusionPageBuilderViewManager.getView( elementID );
				newContainer.addRow();

				FusionPageBuilderApp.scrollingContainers();
			},

			/**
			 * Adds a row.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			addRow: function() {

				this.collection.add( [
					{
						type: 'fusion_builder_row',
						element_type: 'fusion_builder_row',
						added: 'manually',
						cid: FusionPageBuilderViewManager.generateCid(),
						parent: this.model.get( 'cid' ),
						view: this,
						element_content: ''
					}
				] );
			},

			/**
			 * Removes the container.
			 *
			 * @since 2.0.0
			 * @param {Object}         event - The event.
			 * @param {boolean|undefined} skip - Should we skip this?
			 * @return {void}
			 */
			removeContainer: function( event, skip ) {

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

				FusionPageBuilderViewManager.removeView( this.model.get( 'cid' ) );

				this.model.destroy();

				FusionEvents.trigger( 'fusion-element-removed', this.model.get( 'cid' ) );

				this.remove();

				// If its the last container add empty page view.
				if ( 1 > FusionPageBuilderViewManager.countElementsByType( 'fusion_builder_container' ) && 'undefined' === typeof skip ) {
					FusionPageBuilderApp.blankPage = true;
					FusionPageBuilderApp.clearBuilderLayout( true );
				}

				if ( event ) {

					FusionPageBuilderApp.scrollingContainers();

					FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.deleted_section );
					FusionEvents.trigger( 'fusion-content-changed' );
				}
			},

			/**
			 * Clones a container.
			 *
			 * @since 2.0.0
			 * @param {Object} event - The evemt.
			 * @return {void}
			 */
			cloneContainer: function( event ) {

				var containerAttributes,
					$thisContainer;

				if ( event ) {
					event.preventDefault();
				}

				containerAttributes = jQuery.extend( true, {}, this.model.attributes );

				containerAttributes.cid = FusionPageBuilderViewManager.generateCid();
				containerAttributes.created = 'manually';
				containerAttributes.view = this;
				FusionPageBuilderApp.collection.add( containerAttributes );

				$thisContainer = this.$el;

				// Parse rows
				$thisContainer.find( '.fusion-builder-row-container:not(.fusion_builder_row_inner .fusion-builder-row-container)' ).each( function() {

					var thisRow = jQuery( this ),
						rowCID  = thisRow.data( 'cid' ),
						rowView,

						// Get model from collection by cid.
						row = FusionPageBuilderElements.find( function( model ) {
							return model.get( 'cid' ) == rowCID; // jshint ignore: line
						} ),

						// Clone row.
						rowAttributes = jQuery.extend( true, {}, row.attributes );

					rowAttributes.created = 'manually';
					rowAttributes.cid     = FusionPageBuilderViewManager.generateCid();
					rowAttributes.parent  = containerAttributes.cid;
					FusionPageBuilderApp.collection.add( rowAttributes );

					// Make sure spacing is calculated.
					rowView = FusionPageBuilderViewManager.getView( rowAttributes.cid );

					// Parse columns
					thisRow.find( '.fusion-builder-column-outer' ).each( function() {

						// Parse column elements
						var thisColumn = jQuery( this ),
							$columnCID = thisColumn.data( 'cid' ),

							// Get model from collection by cid
							column = FusionPageBuilderElements.find( function( model ) {
								return model.get( 'cid' ) == $columnCID; // jshint ignore: line
							} ),

							// Clone column
							columnAttributes = jQuery.extend( true, {}, column.attributes );

						columnAttributes.created = 'manually';
						columnAttributes.cid     = FusionPageBuilderViewManager.generateCid();
						columnAttributes.parent  = rowAttributes.cid;
						columnAttributes.from    = 'fusion_builder_container';
						columnAttributes.cloned  = true;

						// Don't need target element, position is defined from order.
						delete columnAttributes.targetElementPosition;

						FusionPageBuilderApp.collection.add( columnAttributes );

						// Find column elements
						thisColumn.find( '.fusion-builder-column-content:not( .fusion-nested-column-content )' ).children( '.fusion-builder-live-element, .fusion_builder_row_inner' ).each( function() {

							var thisElement,
								elementCID,
								element,
								elementAttributes,
								thisInnerRow,
								InnerRowCID,
								innerRowView;

							// Regular element
							if ( jQuery( this ).hasClass( 'fusion-builder-live-element' ) ) {

								thisElement = jQuery( this );
								elementCID = thisElement.data( 'cid' );

								// Get model from collection by cid
								element = FusionPageBuilderElements.find( function( model ) {
									return model.get( 'cid' ) == elementCID; // jshint ignore: line
								} );

								// Clone model attritubes
								elementAttributes         = jQuery.extend( true, {}, element.attributes );
								elementAttributes.created = 'manually';
								elementAttributes.cid     = FusionPageBuilderViewManager.generateCid();
								elementAttributes.parent  = columnAttributes.cid;
								elementAttributes.from    = 'fusion_builder_container';

								// Don't need target element, position is defined from order.
								delete elementAttributes.targetElementPosition;

								FusionPageBuilderApp.collection.add( elementAttributes );

							// Inner row element
							} else if ( jQuery( this ).hasClass( 'fusion_builder_row_inner' ) ) {

								thisInnerRow = jQuery( this );
								InnerRowCID = thisInnerRow.data( 'cid' );

								innerRowView = FusionPageBuilderViewManager.getView( InnerRowCID );

								// Clone inner row
								if ( 'undefined' !== typeof innerRowView ) {
									innerRowView.cloneNestedRow( '', columnAttributes.cid );
								}
							}
						} );
					} );

					// Update spacing for columns.
					rowView.setRowData();
				} );

				FusionPageBuilderApp.scrollingContainers();

				FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.cloned_section );
				FusionEvents.trigger( 'fusion-content-changed' );
				this._refreshJs( containerAttributes.cid );
			},

			/**
			 * Adds a child view.
			 *
			 * @param {Object} element - The element model.
			 * @return {void}
			 */
			addChildView: function( element ) {

				var view,
					viewSettings = {
						model: element,
						collection: FusionPageBuilderElements
					};

				view = new FusionPageBuilder.RowView( viewSettings );

				FusionPageBuilderViewManager.addView( element.get( 'cid' ), view );

				if ( this.$el.find( '.fusion-builder-container-content' ).length ) {
					this.$el.find( '.fusion-builder-container-content' ).append( view.render().el );
				} else {
					this.$el.find( '> .fusion-builder-add-element' ).hide().end().append( view.render().el );
				}

				// Add parent view to inner rows that have been converted from shortcodes
				if ( 'manually' === element.get( 'created' ) && 'row_inner' === element.get( 'element_type' ) ) {
					element.set( 'view', FusionPageBuilderViewManager.getView( element.get( 'parent' ) ), { silent: true } );
				}
			},

			/**
			 * Appends model children.
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

					self.$el.find( '.fusion-builder-container-content' ).append( view.$el );

					view.delegateEvents();
					view.delegateChildEvents();
					view.droppableColumn();
				} );
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
				var reInitDraggables = false;

				// Reverted to history step or user entered value manually.
				if ( 'undefined' === typeof event || ( 'undefined' !== typeof event && ( 'change' !== event.type || ( 'change' === event.type && 'undefined' !== typeof event.srcElement ) ) ) ) {
					reInitDraggables = true;
				}

				switch ( paramName ) {

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

				case 'admin_label':
						this.model.attributes.params[ paramName ] = paramValue.replace( /[[\]]+/g, '' );
					break;
				}
			},

			/**
			 * Gets the contents of the container.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getContent: function() {
				var shortcode = '';

				shortcode += FusionPageBuilderApp.generateElementShortcode( this.$el, true );

				this.$el.find( '.fusion_builder_row' ).each( function() {
					var $thisRow = jQuery( this );

					shortcode += '[fusion_builder_row]';

					$thisRow.find( '.fusion-builder-column-outer' ).each( function() {
						var $thisColumn = jQuery( this ),
							columnCID   = $thisColumn.data( 'cid' ),
							columnView  = FusionPageBuilderViewManager.getView( columnCID );

						shortcode += columnView.getColumnContent();

					} );

					shortcode += '[/fusion_builder_row]';

				} );

				shortcode += '[/fusion_builder_container]';

				return shortcode;
			},

			/**
			 * Get the save label.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getSaveLabel: function() {
				return fusionBuilderText.save_section;
			},

			/**
			 * Returns the 'sections' string.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getCategory: function() {
				return 'sections';
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
					parentWidth = $el.closest( '.fusion-row, .fusion-builder-live-editor' ).width(),
					actualDimension;

				if ( this.$el.hasClass( 'active' ) ) {
					return;
				}

				_.each( directions, function( handle, direction )  {

					// Check if using a percentage.
					if ( 'undefined' !== typeof self.model.attributes.params[ 'margin_' + direction ] ) {
						actualDimension = self.model.attributes.params[ 'margin_' + direction ];
						percentSpacing  = -1 !== self.model.attributes.params[ 'margin_' + direction ].indexOf( '%' );

						if ( percentSpacing ) {

							// Get actual dimension and set.
							actualDimension = ( parentWidth / 100 ) * parseFloat( self.model.attributes.params[ 'margin_' + direction ] );
							$el.find( '.fusion-container-margin-' + direction ).css( 'height', actualDimension );
							if ( 'bottom' === direction && 20 > actualDimension ) {
								$el.find( '.fusion-container-margin-bottom, .fusion-container-padding-bottom' ).addClass( 'fusion-overlap' );
							}
						}
					}

					$el.find( '.fusion-container-margin-' + direction ).css( 'display', 'block' );
					$el.find( '.fusion-container-margin-' + direction ).height( actualDimension );

					$el.find( '.fusion-container-margin-' + direction ).resizable( {
						handles: handle,
						minHeight: 0,
						minWidth: 0,
						grid: ( percentSpacing ) ? [ parentWidth / 100, 10 ] : '',
						resize: function( event, ui ) {
							jQuery( ui.element ).addClass( 'active' );

							// Recheck in case unit is changed in the modal.
							percentSpacing = 'undefined' !== typeof self.model.attributes.params[ 'margin_' + direction ] ? -1 !== self.model.attributes.params[ 'margin_' + direction ].indexOf( '%' ) : false;

							jQuery( ui.element ).closest( '.fusion-builder-container' ).addClass( 'active' );
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
									$el.find( '.fusion-container-padding-bottom' ).addClass( 'fusion-overlap' );
								} else {
									jQuery( ui.element ).removeClass( 'fusion-overlap' );
									$el.find( '.fusion-container-padding-bottom' ).removeClass( 'fusion-overlap' );
								}
							}

							$el.find( '.fusion-fullwidth' ).css( 'margin-' + direction, value );

							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).addClass( 'active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip' ).text( value );

							// Update open modal.
							self.updateDragSettings( '#margin_' + direction, value );
						},
						stop: function( event, ui ) {
							jQuery( ui.element ).removeClass( 'active' );
							jQuery( ui.element ).closest( '.fusion-builder-container' ).removeClass( 'active' );

							// Delete all spacing resizable within because parent width has changed.
							if ( jQuery( ui.element ).closest( '.fusion-builder-container' ).find( '.fusion-column-spacing .ui-resizable' ).length ) {
								jQuery( ui.element ).closest( '.fusion-builder-container' ).find( '.fusion-column-spacing .ui-resizable' ).resizable( 'destroy' );
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
					parentWidth = $el.closest( '.fusion-row, .fusion-builder-live-editor' ).width(),
					actualDimension,
					valueAllowed = ( parentWidth / 100 ),
					defaults,
					extras;

				if ( this.$el.hasClass( 'active' ) ) {
					return;
				}

				defaults = fusionAllElements.fusion_builder_container.defaults;
				extras   = jQuery.extend( true, {}, fusionAllElements.fusion_builder_container.extras );

				// If 100 page template.
				if ( FusionPageBuilderApp.$el.find( '#main' ).hasClass( 'width-100' ) && 'undefined' !== typeof extras.container_padding_100 ) {
					defaults.padding_right = extras.container_padding_100.right;
					defaults.padding_left  = extras.container_padding_100.left;
				}

				_.each( directions, function( handle, direction )  {

					// Check if using a percentage.
					actualDimension = 'undefined' !== typeof self.model.attributes.params[ 'padding_' + direction ] && '' !== self.model.attributes.params[ 'padding_' + direction ] ? self.model.attributes.params[ 'padding_' + direction ] : defaults[ 'padding_' + direction ];
					percentSpacing  = 'undefined' !== typeof actualDimension ? -1 !== actualDimension.indexOf( '%' ) : false;

					if ( percentSpacing ) {

						// Get actual dimension and set.
						actualDimension = ( parentWidth / 100 ) * parseFloat( actualDimension );
						if ( 'top' === direction || 'bottom' === direction ) {
							$el.find( '.fusion-container-padding-' + direction ).css( 'height', actualDimension );
						} else {
							$el.find( '.fusion-container-padding-' + direction ).css( 'width', actualDimension );
						}
						if ( 'top' === direction && 20 > actualDimension ) {
							$el.find( '.fusion-container-margin-top, .fusion-container-padding-top' ).addClass( 'fusion-overlap' );
						}
					}

					$el.find( '.fusion-container-padding-' + direction ).css( 'display', 'block' );
					if ( 'top' === direction || 'bottom' === direction ) {
						$el.find( '.fusion-container-padding-' + direction ).height( actualDimension );
					} else {
						$el.find( '.fusion-container-padding-' + direction ).width( actualDimension );
					}

					$el.find( '.fusion-container-padding-' + direction ).resizable( {
						handles: handle,
						minHeight: 0,
						minWidth: 0,

						create: function() {
							if ( 'top' === direction ) {
								if ( 20 > parseInt( actualDimension, 10 ) && ! percentSpacing ) {
									$el.find( '.fusion-container-margin-top, .fusion-container-padding-top' ).addClass( 'fusion-overlap' );
								} else {
									$el.find( '.fusion-container-margin-top, .fusion-container-padding-top' ).removeClass( 'fusion-overlap' );
								}
							}
						},

						resize: function( event, ui ) {
							var dimension = 'top' === direction || 'bottom' === direction ? 'height' : 'width';

							// Force to grid amount.
							if ( percentSpacing ) {
								ui.size[ dimension ] = Math.round( ui.size[ dimension ] / valueAllowed ) * valueAllowed;
							}

							jQuery( ui.element ).addClass( 'active' );
							jQuery( ui.element ).closest( '.fusion-builder-container' ).addClass( 'active' );

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
									$el.find( '.fusion-container-margin-top' ).addClass( 'fusion-overlap' );
								} else {
									jQuery( ui.element ).removeClass( 'fusion-overlap' );
									$el.find( '.fusion-container-margin-top' ).removeClass( 'fusion-overlap' );
								}
							}

							// Set values and width.
							$el.find( '.fusion-fullwidth' ).css( 'padding-' + direction, value );

							jQuery( ui.element ).find( '.fusion-spacing-tooltip, .fusion-column-spacing' ).addClass( 'active' );
							jQuery( ui.element ).find( '.fusion-spacing-tooltip' ).text( value );

							// Update open modal.
							self.updateDragSettings( '#padding_' + direction, value );
						},
						stop: function( event, ui ) {
							jQuery( ui.element ).removeClass( 'active' );
							jQuery( ui.element ).closest( '.fusion-builder-container' ).removeClass( 'active' );

							// Delete all spacing resizable within because parent width has changed.
							if ( jQuery( ui.element ).closest( '.fusion-builder-container' ).find( '.fusion-column-spacing .ui-resizable' ).length ) {
								jQuery( ui.element ).closest( '.fusion-builder-container' ).find( '.fusion-column-spacing .ui-resizable' ).resizable( 'destroy' );
							}
						}
					} );
				} );
			},

			/**
			 * Destroy container resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyResizable: function() {
				this.destroyMarginResizable();
				this.destroyPaddingResizable();
			},

			/**
			 * Destroy container margin resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyMarginResizable: function() {
				var $containerSpacer = this.$el.find( '.fusion-container-margin-top, .fusion-container-margin-bottom' );

				jQuery.each( $containerSpacer, function( index, spacer ) {
					if ( jQuery( spacer ).hasClass( 'ui-resizable' ) ) {
						jQuery( spacer ).resizable( 'destroy' );
						jQuery( spacer ).hide();
					}
				} );
			},

			/**
			 * Destroy container padding resizable.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			destroyPaddingResizable: function() {
				var $containerSpacer = this.$el.find( '.fusion-container-padding-top, .fusion-container-padding-right, .fusion-container-padding-bottom, .fusion-container-padding-left' );

				jQuery.each( $containerSpacer, function( index, spacer ) {
					if ( jQuery( spacer ).hasClass( 'ui-resizable' ) ) {
						jQuery( spacer ).resizable( 'destroy' );
						jQuery( spacer ).hide();
					}
				} );
			},

			/**
			 * Filter out DOM before patching.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			patcherFilter: function( diff ) {
				var filteredDiff = [];

				_.each( diff, function( info ) {
					if ( 'removeElement' === info.action ) {
						if ( 'undefined' !== typeof info.element.attributes && 'undefined' !== typeof info.element.attributes[ 'class' ] && -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-container-spacing' ) ) {

							// Ignore.
						} else {
							filteredDiff.push( info );
						}
					} else if ( 'addElement' === info.action ) {
						if ( 'undefined' !== typeof info.element.attributes && 'undefined' !== typeof info.element.attributes[ 'class' ] && -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-container-spacing' ) ) {

							// Ignore.
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
			 * Handle container name edit in wireframe mode.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			renameContainer: function( event ) {

				// Detect "enter" key
				var code,
					model,
					input,
					fusionHistoryState;

				code = event.keyCode || event.which;

				if ( 13 == code ) { // jshint ignore:line
					event.preventDefault();
					this.$el.find( '.fusion-builder-section-name' ).blur();

					return false;
				}

				fusionHistoryState = fusionBuilderText.edited + ' ' + fusionAllElements[ this.model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element;

				model = this.model;
				input = this.$el.find( '.fusion-builder-section-name' );
				clearTimeout( this.typingTimer );

				this.typingTimer = setTimeout( function() {

					model.attributes.params.admin_label = input.val().replace( /[[\]]+/g, '' );
					FusionEvents.trigger( 'fusion-content-changed' );
					FusionEvents.trigger( 'fusion-history-save-step', fusionHistoryState );

				}, this.doneTypingInterval );
			},

			/**
			 * Handle container toggle in wireframe mode.
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			toggleContainer: function( event ) {

				var thisEl = jQuery( event.currentTarget ),
					fusionHistoryState;

				if ( event ) {
					event.preventDefault();
				}

				this.$el.toggleClass( 'fusion-builder-section-folded' );
				thisEl.find( 'span' ).toggleClass( 'fusiona-caret-up' ).toggleClass( 'fusiona-caret-down' );

				if ( this.$el.hasClass( 'fusion-builder-section-folded' ) ) {
					this.model.attributes.params.admin_toggled = 'yes';
				} else {
					this.model.attributes.params.admin_toggled = 'no';
				}

				fusionHistoryState = fusionBuilderText.edited + ' ' + fusionAllElements[ this.model.get( 'element_type' ) ].name + ' ' + fusionBuilderText.element;

				FusionEvents.trigger( 'fusion-content-changed' );
				FusionEvents.trigger( 'fusion-history-save-step', fusionHistoryState );
			},

			scrollHighlight: function() {
				var $trigger = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '.fusion-one-page-text-link' ),
					$el      = this.$el;

				setTimeout( function() {
					if ( $trigger.length && 'function' === typeof $trigger.fusion_scroll_to_anchor_target ) {
						$trigger.attr( 'href', '#fusion-container-' + this.model.get( 'cid' ) ).fusion_scroll_to_anchor_target( 15 );
					}

					$el.addClass( 'fusion-active-highlight' );
					setTimeout( function() {
						$el.removeClass( 'fusion-active-highlight' );
					}, 6000 );
				}, 10 );
			},

			publish: function( event ) {
				var cid    = jQuery( event.currentTarget ).data( 'cid' ),
					view   = FusionPageBuilderViewManager.getView( cid ),
					params = view.model.get( 'params' );

				FusionApp.confirmationPopup( {
					title: fusionBuilderText.container_publish,
					content: fusionBuilderText.are_you_sure_you_want_to_publish,
					actions: [
						{
							label: fusionBuilderText.no,
							classes: 'no',
							callback: function() {
								FusionApp.confirmationPopup( {
									action: 'hide'
								} );
							}
						},
						{
							label: fusionBuilderText.yes,
							classes: 'yes',
							callback: function() {
								params.status = 'published';
								view.model.set( 'params', params );
								view.$el.find( 'a[data-cid="' + cid + '"].fusion-builder-publish-tooltip' ).remove();

								FusionEvents.trigger( 'fusion-history-turn-on-tracking' );
								FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.container_published );

								FusionEvents.trigger( 'fusion-content-changed' );
								FusionApp.confirmationPopup( {
									action: 'hide'
								} );
							}
						}
					]
				} );
			},

			unglobalize: function( event ) {
				var cid    = jQuery( event.currentTarget ).data( 'cid' ),
					view   = FusionPageBuilderViewManager.getView( cid ),
					params = view.model.get( 'params' );

				event.preventDefault();

				FusionApp.confirmationPopup( {

					title: fusionBuilderText.remove_global,
					content: fusionBuilderText.are_you_sure_you_want_to_remove_global,
					actions: [
						{
							label: fusionBuilderText.no,
							classes: 'no',
							callback: function() {
								FusionApp.confirmationPopup( {
									action: 'hide'
								} );
							}
						},
						{
							label: fusionBuilderText.yes,
							classes: 'yes',
							callback: function() {

								// Remove global attributes.
								delete params.fusion_global;
								view.model.set( 'params', params );
								view.$el.removeClass( 'fusion-global-container fusion-global-column fusion-global-nested-row fusion-global-element fusion-global-parent-element' );
								view.$el.find( 'a[data-cid="' + cid + '"].fusion-builder-unglobal-tooltip' ).remove();
								view.$el.removeAttr( 'fusion-global-layout' );

								FusionEvents.trigger( 'fusion-history-turn-on-tracking' );
								FusionEvents.trigger( 'fusion-history-save-step', fusionBuilderText.removed_global );

								FusionEvents.trigger( 'fusion-content-changed' );
								FusionApp.confirmationPopup( {
									action: 'hide'
								} );
							}
						}
					]
				} );
			}
		} );
	} );
}( jQuery ) );
