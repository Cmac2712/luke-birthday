/* global FusionEvents, fusionBuilderText */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		FusionPageBuilder.fusion_widget = FusionPageBuilder.ElementView.extend( {

			onInit: function() {
				this.contentView = false;
				this.listenTo( FusionEvents, 'fusion-widget-rendered', this.removeLoadingOverlay );

			},

			onRender: function() {
				this.renderWidgetContent();
			},

			/**
			 * Removes loading overlay
			 *
			 * @since 2.0.0
			 * @return {void}
			 */
			removeLoadingOverlay: function() {
				var contentType = 'element',
					$elementContent;

				if ( _.isObject( this.model.attributes ) ) {
					if ( 'fusion_builder_container' === this.model.attributes.element_type ) {
						contentType = 'container';
					} else if ( 'fusion_builder_column' === this.model.attributes.element_type ) {
						contentType = 'columns';
					}
				}

				$elementContent = this.$el.find( '.fusion-builder-' + contentType + '-content' );

				$elementContent.removeClass( 'fusion-loader' );
				$elementContent.find( '.fusion-builder-loader' ).remove();
			},

			beforeRemove: function() {
				if ( this.contentView ) {
					this.contentView.removeElement();
				}
			},

			renderWidgetContent: function() {
				var view,
					viewSettings = {
						model: this.model
					},
					widgetType = this.model.attributes.params.type;

				if ( ! this.model.get( 'params' ).type ) {
					return;
				}
				if ( this.contentView ) {
					this.$el.find( '.fusion-widget-content' ).html( this.contentView.render().el );

				} else {
					if ( 'undefined' !== typeof FusionPageBuilder[ widgetType ] ) {
						view = new FusionPageBuilder[ widgetType ]( viewSettings );
					} else {
						view = new FusionPageBuilder.fusion_widget_content( viewSettings );
					}

					this.contentView = view;

					this.$el.find( '.fusion-widget-content' ).html( view.render().el );
				}
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Create attribute objects
				attributes.attr   	= this.buildAttr( atts.values );
				attributes.styles 	= this.buildStyles( atts.values );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.values = atts.values;
				attributes.placeholder = this.getWidgetPlaceholder();

				return attributes;
			},

			/**
			 * Get widget placeholder.
			 *
			 * @since 2.0.0
			 * @return {string}
			 */
			getWidgetPlaceholder: function() {
				var placeholder = jQuery( this.getPlaceholder() ).append( '<span class="fusion-tb-source-separator"> - </span><br/><span>' + fusionBuilderText.select_widget + '</span>' );
				return placeholder[ 0 ].outerHTML;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-widget fusion-widget-element fusion-widget-area fusion-content-widget-area widget fusion-live-widget fusion-widget-cid' + this.model.get( 'cid' ),
						style: ''
					} );

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				if ( values.fusion_align ) {
					attr[ 'class' ] += ' fusion-widget-align-' + values.fusion_align;
				}

				if ( values.fusion_align_mobile ) {
					attr[ 'class' ] += ' fusion-widget-mobile-align-' + values.fusion_align_mobile;
				}
				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildStyles: function( values ) {
				var styles       = '',
					cid          = this.model.get( 'cid' );

				styles = '<style type="text/css">';
				styles += '.fusion-widget.fusion-widget-cid' + cid + '{';
				styles += 'background-color:' + values.fusion_bg_color + ';';

				if ( 'undefined' !== typeof values.fusion_padding_color ) {
					styles += 'padding:' + _.fusionCheckValue( values.fusion_padding_color ) + ';';
				}
				if ( 'undefined' !== typeof values.fusion_bg_radius_size ) {
					styles += 'border-radius:' + _.fusionCheckValue( values.fusion_bg_radius_size ) + ';';
				}
				if ( 'undefined' !== typeof values.fusion_margin ) {
					styles += 'margin:' + _.fusionCheckValue( values.fusion_margin ) + ';';
				}
				styles += 'border-color:' + values.fusion_border_color + ';';
				styles += 'border-width:' + _.fusionValidateAttrValue( values.fusion_border_size, 'px' ) + ';';

				if ( '' !== values.fusion_border_size ) {
					styles += 'border-style:' + values.fusion_border_style + ';';
				}

				styles += '}';

				if ( 'no' === values.fusion_display_title ) {
					styles += '.fusion-widget.fusion-widget-cid' + cid + ' .widget-title{display:none;}';
				}
				styles += '</style>';

				return styles;
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
					if ( 'replaceElement' === info.action ) {

						if ( 'undefined' !== typeof info.oldValue.attributes && -1 !== info.oldValue.attributes[ 'class' ].indexOf( 'fusion-widget-content-view' ) ) {

							// Ignore.
						} else {
							filteredDiff.push( info );
						}
					} else if ( 'addElement' === info.action ) {
						if ( -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-widget-content-view' ) || -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-widget' ) ) {

							// Ignore.
						} else {
							filteredDiff.push( info );
						}
					} else if ( 'removeElement' === info.action ) {
						if ( -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-widget-content' ) || -1 !== info.element.attributes[ 'class' ].indexOf( 'fusion-widget' ) ) {

							// Ignore.
						} else {
							filteredDiff.push( info );
						}
					} else {
						filteredDiff.push( info );
					}
				} );

				return filteredDiff;
			}

		} );

		_.extend( FusionPageBuilder.Callback.prototype, {
			fusion_get_widget_markup: function( name, value, modelData, args, cid, action, model, view ) {
				view.changeParam( name, value );
				view.contentView.getHTML( view );
			},

			fusion_widget_changed: function( name, value, args, view ) {
				view.changeParam( name, value );
				view.model.attributes.markup = '';
				FusionEvents.trigger( 'fusion-widget-changed' );
				view.render();
				view.addLoadingOverlay();
				// prevent another re-render
				return {
					render: false
				};
			}
		} );

	} );

}() );
