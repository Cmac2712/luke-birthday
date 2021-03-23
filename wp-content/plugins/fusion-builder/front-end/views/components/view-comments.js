var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Comments view.
		FusionPageBuilder.fusion_tb_comments = FusionPageBuilder.ElementView.extend( {

			onInit: function() {
				if ( this.model.attributes.markup && '' === this.model.attributes.markup.output ) {
					this.model.attributes.markup.output = this.getComponentPlaceholder();
				}
			},

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

				attributes.output      = this.buildOutput( atts );
				attributes.placeholder = this.getComponentPlaceholder();

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
				values.padding     = _.fusionValidateAttrValue( values.padding, 'px' );
			},

			/**
			 * Builds output.
			 *
			 * @since  2.2
			 * @param  {Object} values - The values object.
			 * @return {String}
			 */
			buildOutput: function( atts ) {
				var output = '',
					title  = '';

				if ( 'undefined' !== typeof atts.markup && 'undefined' !== typeof atts.markup.output && 'undefined' === typeof atts.query_data ) {
					output = jQuery( jQuery.parseHTML( atts.markup.output ) ).filter( '.fusion-comments-tb' ).html();
					output = ( 'undefined' === typeof output ) ? atts.markup.output : output;
				} else if ( 'undefined' !== typeof atts.query_data && 'undefined' !== typeof atts.query_data.comments ) {
					output = atts.query_data.comments;
				}

				_.each( jQuery( jQuery.parseHTML( output ) ).find( 'h1, h2, h3, h4, h5, h6' ), function( item ) {
					title  = _.buildTitleElement( atts.values, atts.extras, jQuery( item ).html() );
					output = output.replace( jQuery( item ).parent().prop( 'outerHTML' ), title );
				} );

				return output;
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
						class: 'fusion-comments-tb fusion-live-comments-tb fusion-comments-tb-' + this.model.get( 'cid' ),
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

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( 'hide' !== values.avatar ) {
					attr[ 'class' ] += ' ' + values.avatar;
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
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .commentlist .the-comment{border-bottom-width:' + values.border_size + ';}';
				}

				if ( '' !== values.border_color ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .commentlist .the-comment{border-color:' + values.border_color + ';}';
				}

				if ( 'hide' === values.avatar ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .commentlist .the-comment .comment-text{margin-left:0px;}';
				}

				if ( 'circle' === values.avatar ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + '.circle .the-comment .avatar{border-radius: 50%;}';
				}

				if ( 'square' === values.avatar ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + '.square .the-comment .avatar{border-radius: 0;}';
				}

				if ( '' !== values.padding ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .commentlist .children{padding-left:' + values.padding + ';}';
				}

				if ( 'hide' === values.avatar ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .avatar{display:none;}';
				}

				if ( 'hide' === values.headings ) {
					styles += '.fusion-comments-tb-' + this.model.get( 'cid' ) + ' .fusion-title{display:none;}';
				}

				styles += '</style>';

				return styles;
			}

		} );
	} );
}( jQuery ) );
