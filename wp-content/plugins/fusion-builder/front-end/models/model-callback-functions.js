/* global fusionOptionName, FusionApp, FusionPageBuilderElements, fusionAppConfig, FusionPageBuilderApp, FusionPageBuilderViewManager */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	_.extend( FusionPageBuilder.Callback.prototype, {
		fusion_preview: function( name, value, args, view ) {
			var property = args.property,
				element  = window.fusionAllElements[ view.model.get( 'element_type' ) ],
				$theEl;

			if ( ! value && '' !== value ) {
				return;
			}

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			if ( '' === value && 'undefined' !== typeof element && 'undefined' !== typeof element.defaults && 'undefined' !== typeof element.defaults[ name ] ) {
				value = element.defaults[ name ];
			}
			if ( 'undefined' !== typeof args.dimension ) {
				property = ( 'undefined' !== typeof args.property[ name ] ) ? args.property[ name ] : name.replace( /_/g, '-' );
			}
			if ( 'undefined' !== typeof args.unit ) {
				value = _.fusionGetValueWithUnit( value, args.unit );
			}
			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );
			if ( 'string' === typeof property ) {
				$theEl.css( property, value );
			}
			if ( 'object' === typeof property ) {
				_.each( args.property, function( singleProperty ) {
					$theEl.css( singleProperty, value );
				} );
			}

			return {
				render: false
			};
		},

		fusion_add_id: function( name, value, args, view ) {
			var $theEl;

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );

			$theEl.attr( 'id', value );

			return {
				render: false
			};
		},

		fusion_add_class: function( name, value, args, view ) {
			var $theEl,
				existingValue = view.model.attributes.params[ name ];

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );

			$theEl.removeClass( existingValue );
			$theEl.addClass( value );

			return {
				render: false
			};
		},

		fusion_toggle_class: function( name, value, args, view ) {
			var $theEl;

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );

			if ( 'object' === typeof args.classes ) {
				_.each( args.classes, function( optionClass, optionValue ) {
					$theEl.removeClass( optionClass );
					if ( value === optionValue ) {
						$theEl.addClass( optionClass );
					}
				} );
			}

			return {
				render: false
			};
		},

		fusion_ajax: function( name, value, modelData, args, cid, action, model, elementView ) {

			var params   = jQuery.extend( true, {}, modelData.params ),
				ajaxData = {};

			if ( 'undefined' !== typeof name && ! args.skip ) {
				params[ name ] = value;
			}
			ajaxData.params = jQuery.extend( true, {}, window.fusionAllElements[ modelData.element_type ].defaults, _.fusionCleanParameters( params ) );

			jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: {
					action: action,
					model: ajaxData,
					option_name: 'string' === typeof fusionOptionName ? fusionOptionName : false,
					fusion_options: 'undefined' !== typeof FusionApp && 'object' === typeof FusionApp.settings ? jQuery.param( FusionApp.settings ) : false,
					fusion_meta: 'undefined' !== typeof FusionApp && 'object' === typeof FusionApp.data.postMeta ? jQuery.param( FusionApp.data.postMeta ) : false,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					if ( 'undefined' === typeof model ) {
						model = FusionPageBuilderElements.find( function( scopedModel ) {
							return scopedModel.get( 'cid' ) == cid; // jshint ignore: line
						} );
					}

					// This changes actual model.
					if ( 'undefined' !== typeof name && ! args.skip ) {
						elementView.changeParam( name, value );
					}

					if ( 'image_id' === name && 'undefined' !== typeof response.image_data && 'undefined' !== typeof response.image_data.url && ! args.skip ) {
						elementView.changeParam( 'image', response.image_data.url );
					}

					model.set( 'query_data', response );

					if ( 'generated_element' !== model.get( 'type' ) ) {
						if ( 'undefined' == typeof elementView ) {
							elementView = FusionPageBuilderViewManager.getView( cid );
						}

						if ( 'undefined' !== typeof elementView && ! args.skip ) {
							elementView.reRender();
						}
					}
				}
			} );
		},

		fusion_do_shortcode: function( cid, content, parent, ajaxShortcodes ) {

			jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'get_shortcode_render',
					content: content,
					shortcodes: 'undefined' !== typeof ajaxShortcodes ? ajaxShortcodes : '',
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					cid: cid,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					var markup = {},
						modelcid = cid,
						model,
						view;

					if ( 'undefined' !== typeof parent && parent ) {
						modelcid = parent;
					}

					model = FusionPageBuilderElements.find( function( scopedModel ) {
						return scopedModel.get( 'cid' ) == modelcid; // jshint ignore: line
					} );

					view = FusionPageBuilderViewManager.getView( modelcid );

					markup.output = FusionPageBuilderApp.addPlaceholder( content, response.content );

					if ( view && 'function' === typeof view.filterOutput ) {
						markup.output = view.filterOutput( markup.output );
					}

					markup.shortcode = content;

					if ( model ) {
						model.set( 'markup', markup );
					}

					// If multi shortcodes, add each.
					if ( 'object' === typeof response.shortcodes ) {
						_.each( response.shortcodes, function( output, shortcode ) {
							FusionPageBuilderApp.extraShortcodes.addShortcode( shortcode, FusionPageBuilderApp.addPlaceholder( shortcode, output ) );
						} );
					}

					if ( 'undefined' !== typeof view ) {
						view.reRender( 'ajax' );
					}

					if ( FusionPageBuilderApp.viewsToRerender ) {
						_.each( FusionPageBuilderApp.viewsToRerender, function( scopedCID ) {
							FusionPageBuilderViewManager.getView( scopedCID ).reRender( 'ajax' );
						} );

						FusionPageBuilderApp.viewsToRerender = [];
					}
				}
			} );
		},

		fusion_code_mirror: function( name, value, args, view ) {

			// Save encoded value.
			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			if ( FusionPageBuilderApp.base64Encode( FusionPageBuilderApp.base64Decode( value ) ) === value ) {
				value = FusionPageBuilderApp.base64Decode( value );
			}

			// Update with decoded value.
			view.syntaxHighlighter.getDoc().setValue( value );

			return {
				render: false
			};
		},

		dynamic_shortcode: function( args ) {
			if ( 'undefined' === typeof args.shortcode || '' === args.shortcode ) {
				return '';
			}

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'get_shortcode_render',
					content: args.shortcode,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					cid: false,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		post_featured_image: function( args ) {
			var ID = FusionApp.data.postMeta._thumbnail_id;
			if ( 'undefined' === typeof ID || '' === ID ) {
				return ID;
			}
			return wp.media.attachment( ID ).fetch().then( function() {
				FusionPageBuilderApp.dynamicValues.setValue( args, wp.media.attachment( ID ).get( 'url' ) );
			} );
		},

		fusion_get_object_title: function() {
			return 'undefined' !== typeof FusionApp.data ? FusionApp.getDynamicPost( 'post_title' ) : '';
		},

		fusion_get_post_id: function() {
			return 'undefined' !== typeof FusionApp.data ? FusionApp.getDynamicPost( 'post_id' ) : '';
		},

		fusion_get_object_excerpt: function() {
			return 'undefined' !== typeof FusionApp.data ? FusionApp.getDynamicPost( 'post_excerpt' ) : '';
		},

		fusion_get_post_date: function( args ) {

			if ( 'undefined' === typeof FusionApp.data ) {
				return '';
			}

			if ( 'undefined' === args.format || '' === args.format ) {
				return 'undefined' !== typeof args.type && 'modified' === args.type ? FusionApp.getDynamicPost( 'post_modified' ) : FusionApp.getDynamicPost( 'post_date' );
			}

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		fusion_get_post_time: function( args ) {

			if ( 'undefined' === typeof FusionApp.data ) {
				return '';
			}

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		fusion_get_post_terms: function( args ) {

			if ( 'undefined' === typeof FusionApp.data ) {
				return '';
			}

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		fusion_get_post_custom_field: function( args ) {
			var postMeta = FusionApp.getDynamicPost( 'post_meta' );
			postMeta._fusion = postMeta._fusion || {};
			return 'undefined' !== typeof postMeta[ args.key ] ? postMeta[ args.key ] : '';
		},

		fusion_get_page_option: function( args ) {
			var postMeta = FusionApp.getDynamicPost( 'post_meta' );
			postMeta._fusion = postMeta._fusion || {};
			return 'undefined' !== typeof postMeta._fusion[ args.data ] ? postMeta._fusion[ args.data ] : '';
		},

		fusion_get_site_title: function() {
			return 'undefined' !== typeof FusionApp.data ? FusionApp.data.site_title : '';
		},

		fusion_get_site_tagline: function() {
			return 'undefined' !== typeof FusionApp.data ? FusionApp.data.site_tagline : '';
		},

		woo_get_price: function( args ) {

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		woo_get_sku: function( args ) {

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		defaultDynamicCallback: function( args ) {
			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		woo_get_stock: function( args ) {

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		woo_get_rating: function( args ) {

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'ajax_dynamic_data_default_callback',
					callback: FusionApp.data.dynamicOptions[ args.data ].callback[ 'function' ],
					args: args,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.getDynamicPost( 'post_id' )
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		/**
		 * Updates selector element with CSS filter style.
		 *
		 * @param {*} name Param name.
		 * @param {*} value Param value.
		 * @param {*} args Args defined.
		 * @param {*} view Element view.
		 */
		fusion_update_filter_style: function( name, value, args, view ) {
			var newStyle = '',
				cid      = view.model.get( 'cid' ),
				$styleEl = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( '#fusion-filter-' + cid + '-style' );

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			newStyle = _.fusionGetFilterStyleElem( view.getValues(), '.' + args.selector_base + cid, cid );

			// Update filter style block.
			if ( 0 < $styleEl.length ) {
				$styleEl.replaceWith( newStyle );
			}

			return {
				render: false
			};
		},

		/**
		 * Updates gradient styles for selector element.
		 *
		 * @param  {String} name  Param name.
		 * @param  {String} value Param value.
		 * @param  {Object} args  Args defined.
		 * @param  {Object} view  Element view.
		 * @return {Object}
		 */
		fusion_update_gradient_style: function( name, value, args, view ) {
			var $theEl,
				mainBGStyles         = '',
				parallaxStyles       = '',
				overlayStyles        = '',
				fadedStyles          = '',
				alphaBackgroundColor = '',
				videoBg              = false,
				elementType          = view.model.get( 'element_type' ),
				values               = {};

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			values = view.getValues();

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector ).first();

			switch ( elementType ) {
				case 'fusion_builder_container':
					mainBGStyles         = _.getGradientString( values, 'main_bg' );
					parallaxStyles       = _.getGradientString( values, 'parallax' );
					fadedStyles          = _.getGradientString( values, 'fade' );
					overlayStyles        = _.getGradientString( values );
					alphaBackgroundColor = jQuery.Color( values.background_color ).alpha();

					if ( '' === mainBGStyles && '' !== values.background_image && 'yes' !== values.fade ) {
						mainBGStyles = 'url(\'' + values.background_image + '\')';
					}

					$theEl.css( 'background-image', mainBGStyles );
					$theEl.find( '.parallax-inner' ).css( 'background-image', parallaxStyles );
					$theEl.find( '.fullwidth-overlay' ).css( 'background-image', overlayStyles );
					$theEl.find( '.fullwidth-faded' ).css( 'background-image', fadedStyles );

					if ( ( 'undefined' !== typeof values.video_mp4 && '' !== values.video_mp4 ) ||
					( 'undefined' !== typeof values.video_webm && '' !== values.video_webm ) ||
					( 'undefined' !== typeof values.video_ogv && '' !== values.video_ogv ) ||
					( 'undefined' !== typeof values.video_url && '' !== values.video_url ) ) {
						videoBg   = true;
					}

					if ( 1 > alphaBackgroundColor && 0 !== alphaBackgroundColor && 'none' === values.background_blend_mode && '' === overlayStyles && ( ! _.isEmpty( values.background_image ) || ! videoBg ) ) {
						$theEl.addClass( 'fusion-blend-mode' );
					} else {
						$theEl.removeClass( 'fusion-blend-mode' );
					}

					break;

				case 'fusion_builder_column':
				case 'fusion_builder_column_inner':
					mainBGStyles         = _.getGradientString( values, 'column' );
					alphaBackgroundColor = jQuery.Color( values.background_color ).alpha();

					if ( '' === mainBGStyles && '' !== values.background_image ) {
						mainBGStyles = 'url(\'' + values.background_image + '\')';
					}
					$theEl.css( 'background-image', mainBGStyles );

					if ( 1 > alphaBackgroundColor && 0 !== alphaBackgroundColor && 'none' === values.background_blend_mode && '' === _.getGradientString( values ) && _.isEmpty( values.background_image ) && ! _.isEmpty( values.background_color ) ) {
						$theEl.closest( '.fusion-layout-column' ).addClass( 'fusion-blend-mode' );
					} else {
						$theEl.closest( '.fusion-layout-column' ).removeClass( 'fusion-blend-mode' );
					}

					break;
			}

			return {
				render: false
			};
		},

		/**
		 * Updates separator for selector element.
		 *
		 * @param  {String} name  Param name.
		 * @param  {String} value Param value.
		 * @param  {Object} args  Args defined.
		 * @param  {Object} view  Element view.
		 * @return {Object}
		 */
		fusion_update_breadcrumbs_separator: function( name, value, args, view ) {
			var $theEl,
				markup     = {},
				query_data = {};

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			if ( FusionApp.data.is_home || FusionApp.data.is_front_page ) {
				return;
			}

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );

			if ( '' !== value ) {
				$theEl.find( '.fusion-breadcrumb-sep' ).html( value );
			} else if ( 'undefined' !== typeof FusionApp && 'object' === typeof FusionApp.settings ) {
				$theEl.find( '.fusion-breadcrumb-sep' ).html( FusionApp.settings.breadcrumb_separator );
			}

			markup.output          = $theEl.html();
			query_data.breadcrumbs = $theEl.html();

			view.model.set( 'markup', markup );
			view.model.set( 'query_data', query_data );

			return {
				render: false
			};
		},

		/**
		 * Updates prefix for selector element.
		 *
		 * @param  {String} name  Param name.
		 * @param  {String} value Param value.
		 * @param  {Object} args  Args defined.
		 * @param  {Object} view  Element view.
		 * @return {Object}
		 */
		fusion_update_breadcrumbs_prefix: function( name, value, args, view ) {
			var $theEl,
				markup     = {},
				query_data = {};

			if ( ! args.skip ) {
				view.changeParam( name, value );
			}

			if ( FusionApp.data.is_home || FusionApp.data.is_front_page ) {
				return;
			}

			$theEl = ( 'undefined' === typeof args.selector ) ? view.$el : view.$el.find( args.selector );

			$theEl.find( '.fusion-breadcrumb-prefix' ).remove();

			if ( '' !== value ) {
				$theEl.prepend( '<span class="fusion-breadcrumb-prefix"><span class="fusion-breadcrumb-item"><span>' + value + '</span></span>:</span>' );
			} else if ( 'undefined' !== typeof FusionApp && 'object' === typeof FusionApp.settings && '' !== FusionApp.settings.breacrumb_prefix ) {
				$theEl.prepend( '<span class="fusion-breadcrumb-prefix"><span class="fusion-breadcrumb-item"><span>' + FusionApp.settings.breacrumb_prefix + '</span></span>:</span>' );
			}

			markup.output          = $theEl.html();
			query_data.breadcrumbs = $theEl.html();

			view.model.set( 'markup', markup );
			view.model.set( 'query_data', query_data );

			return {
				render: false
			};
		},

		acf_get_field: function( args, image ) {
			if ( 'undefined' === typeof args.field || '' === args.field ) {
				return '';
			}

			return jQuery.ajax( {
				url: fusionAppConfig.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'ajax_acf_get_field',
					field: args.field,
					fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
					post_id: FusionApp.data.postDetails.post_id,
					image: 'undefined' !== typeof image ? image : false,
					cid: false
				},
				success: function( response ) {
					FusionPageBuilderApp.dynamicValues.setValue( args, response.content );
				}
			} );
		},

		acf_get_image_field: function( args ) {
			return this.acf_get_field( args, true );
		}
	} );
}( jQuery ) );
