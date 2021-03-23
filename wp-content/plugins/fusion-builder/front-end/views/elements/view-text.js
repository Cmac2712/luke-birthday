/* global fusionAllElements, fusionBuilderText */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Text Element View.
		FusionPageBuilder.fusion_text = FusionPageBuilder.ElementView.extend( {

			/**
			 * Runs before view DOM is patched.
			 *
			 * @since 2.0.0
			 * @return null
			 */
			beforePatch: function() {

				if ( 'undefined' === typeof this.model.attributes.params.element_content || '' === this.model.attributes.params.element_content ) {
					this.model.attributes.params.element_content = fusionBuilderText.text_placeholder;
				}

			},

			afterPatch: function() {
				this._refreshJs();
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Create attribute objects
				attributes.attr = this.buildAttr( atts.values );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.output = _.autop( atts.values.element_content );

				return attributes;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var self           = this,
					textAttributes = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-text',
						style: ''
					} ),
					browserPrefixes = [ '-webkit-', '-moz-', '' ];

				if ( 'default' === values.rule_style ) {
					values.rule_style = fusionAllElements.fusion_text.defaults.rule_style;
				}

				// Only add styling if more than one column is used.
				if ( 1 < values.columns ) {
					textAttributes[ 'class' ] += ' fusion-text-split-columns fusion-text-columns-' + values.columns;

					_.each( browserPrefixes, function( prefix ) {

						textAttributes.style += ' ' + prefix + 'column-count:' + values.columns + ';';

						if ( 'none' !== values.column_spacing && values.column_spacing ) {
							textAttributes.style += ' ' + prefix + 'column-gap:' + _.fusionValidateAttrValue( values.column_spacing, 'px' ) + ';';
						}

						if ( 'none' !== values.column_min_width && values.column_min_width ) {
							textAttributes.style += ' ' + prefix + 'column-width:' + _.fusionValidateAttrValue( values.column_min_width, 'px' ) + ';';
						}

						if ( 'none' !== values.rule_style ) {
							textAttributes.style += ' ' + prefix + 'column-rule:' + values.rule_size + 'px ' + values.rule_style + ' ' + values.rule_color + ';';
						}

					} );
				}

				if ( 'undefined' !== typeof values[ 'class' ] && '' !== values[ 'class' ] ) {
					textAttributes[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( 'undefined' !== typeof values.id && '' !== values.id ) {
					textAttributes.id = values.id;
				}

				textAttributes = _.fusionInlineEditor( {
					cid: self.model.get( 'cid' )
				}, textAttributes );

				textAttributes = _.fusionAnimations( values, textAttributes );

				return textAttributes;
			}
		} );
	} );
}( jQuery ) );
