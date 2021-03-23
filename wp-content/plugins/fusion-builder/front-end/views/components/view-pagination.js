var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Pagination view.
		FusionPageBuilder.fusion_tb_pagination = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.2
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				attributes.wrapperAttr = this.buildAttr( atts.values );
				attributes.styles      = this.buildStyleBlock( atts.values );

				// Any extras that need passed on.
				attributes.cid = this.model.get( 'cid' );

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since  2.2
			 * @param  {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.border_size = _.fusionValidateAttrValue( values.border_size, 'px' );
				values.height      = _.fusionValidateAttrValue( values.height, 'px' );
			},

			/**
			 * Builds attributes.
			 *
			 * @since  2.2
			 * @param  {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr         = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'single-navigation clearfix fusion-live-pagination-tb fusion-pagination-tb fusion-pagination-tb-' + this.model.get( 'cid' ),
						style: ''
					} );

				if ( '' !== values.margin_top ) {
					attr.style += 'margin-top:' + values.margin_top + ';';
				}

				if ( '' !== values.margin_right ) {
					attr.style += 'margin-right:' + values.margin_right + ';';
				}

				if ( '' !== values.margin_bottom ) {
					attr.style += 'margin-bottom:' + values.margin_bottom + ';';
				}

				if ( '' !== values.margin_left ) {
					attr.style += 'margin-left:' + values.margin_left + ';';
				}

				if ( '' !== values.height ) {
					attr.style += 'min-height:' + values.height + ';';
				}

				if ( '' !== values.font_size ) {
					attr.style += 'font-size:' + values.font_size + ';';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.alignment ) {
					attr[ 'class' ] += ' align-' + values.alignment;
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				attr = _.fusionAnimations( values, attr );

				return attr;
			},

			/**
			 * Builds styles.
			 *
			 * @since  2.2
			 * @param  {Object} values - The values object.
			 * @return {String}
			 */
			buildStyleBlock: function( values ) {
				var styles = '<style type="text/css">';

				if ( '' !== values.border_size ) {
					styles += '.fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation{border-width:' + values.border_size + ';}';
				}

				if ( '' !== values.border_color ) {
					styles += '.fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation{border-color:' + values.border_color + ';}';
				}

				if ( '' !== values.text_color ) {
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a,';
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a::before,';
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a::after {';
					styles += 'color:' + values.text_color + ';';
					styles += '}';
				}

				if ( '' !== values.text_hover_color ) {
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a:hover,';
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a:hover::before,';
					styles += '.fusion-fullwidth .fusion-builder-row.fusion-row .fusion-pagination-tb-' + this.model.get( 'cid' ) + '.single-navigation a:hover::after {';
					styles += 'color:' + values.text_hover_color + ';';
					styles += '}';
				}

				styles += '</style>';

				return styles;
			}

		} );
	} );
}( jQuery ) );
