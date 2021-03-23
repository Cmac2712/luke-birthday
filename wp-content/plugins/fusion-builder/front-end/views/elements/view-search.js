/* global */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {
	jQuery( document ).ready( function() {
		// Button Element View.
		FusionPageBuilder.fusion_search = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.2
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};
				attributes.wrapperAttr = this.buildAttr( atts.values );
				attributes.formAttr = this.buildFormAttr( atts.values );

				// Any extras that need passed on.
				attributes.cid = this.model.get( 'cid' );

				// Any extras that need passed on.
				attributes.values = atts.values;

				return attributes;
			},

			buildFormAttr: function( values ) {
				var attr = {
					class: 'searchform fusion-search-form fusion-live-search'
				};

				if ( values.design ) {
					attr[ 'class' ] += ' fusion-search-form-' + values.design;
				}

				return attr;
			},

			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-search-element',
						style: ''
					} );

				if ( values.margin_top ) {
					attr.style += 'margin-top:' + values.margin_top + ';';
				}

				if ( values.margin_bottom ) {
					attr.style += 'margin-bottom:' + values.margin_bottom + ';';
				}

				if ( values.margin_right ) {
					attr.style += 'margin-right:' + values.margin_right + ';';
				}

				if ( values.margin_left ) {
					attr.style += 'margin-left:' + values.margin_left + ';';
				}

				if ( values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				attr.id = values.id;

				attr = _.fusionAnimations( values, attr );
				return attr;
			}

		} );
	} );
}( jQuery ) );
