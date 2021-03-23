/* global FusionPageBuilderElements, fusionAllElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Toggle child View.
		FusionPageBuilder.fusion_toggle = FusionPageBuilder.ChildElementView.extend( {

			/**
			 * Runs during render() call.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			onRender: function() {
				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					this.model.attributes.selectors[ 'class' ] += ' ' + this.className;
					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			afterPatch: function() {

				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					this.model.attributes.selectors[ 'class' ] += ' ' + this.className;
					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}

				// Using non debounced version for smoothness.
				this.refreshJs();
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {},
					parent          = this.model.get( 'parent' ),
					parentModel     = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) == parent;
					} ),
					parentValues    = jQuery.extend( true, {}, fusionAllElements.fusion_accordion.defaults, _.fusionCleanParameters( parentModel.get( 'params' ) ) );

				// Validate values.
				this.validateValues( atts.values );

				// Create attribute objects.
				attributes.toggleShortcodeCollapse   = this.buildCollapseAttr( atts.values );
				attributes.toggleShortcodeDataToggle = this.buildDataToggleAttr( atts.values, parentValues, parentModel );
				attributes.headingAttr               = this.buildHeadingAttr( atts.values );
				attributes.contentAttr               = this.buildContentAttr( atts.values );
				attributes.title                     = atts.values.title;
				attributes.elementContent            = atts.values.element_content;

				// Set selectors.
				this.buildPanelAttr( atts.values, parentValues );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.parent = this.model.get( 'parent' );

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.toggle_class = ( 'yes' === values.open ) ? 'in' : '';
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildCollapseAttr: function( values ) {
				var collapseID              = '#accordion-' + this.model.get( 'cid' ),
					toggleShortcodeCollapse = {
						id: collapseID.replace( '#', '' ),
						class: 'panel-collapse collapse ' + values.toggle_class
					};

				return toggleShortcodeCollapse;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @param {Object} parentValues - The parent object values.
			 * @return {Object}
			 */
			buildPanelAttr: function( values, parentValues ) {
				var toggleShortcodePanel = {
					class: 'fusion-panel panel-default'
				};

				if ( ' ' !== values[ 'class' ] ) {
					toggleShortcodePanel[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					toggleShortcodePanel.id = values.id;
				}

				if ( '1' == parentValues.boxed_mode || 'yes' === parentValues.boxed_mode ) {
					toggleShortcodePanel[ 'class' ] += ' fusion-toggle-no-divider fusion-toggle-boxed-mode';
				} else if ( '0' == parentValues.divider_line || 'no' === parentValues.divider_line ) {
					toggleShortcodePanel[ 'class' ] += ' fusion-toggle-no-divider';
				}

				this.model.set( 'selectors', toggleShortcodePanel );

				return toggleShortcodePanel;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @param {Object} parentValues - The parent values object.
			 * @param {Object} parentModel - The parent element model.
			 * @return {Object}
			 */
			buildDataToggleAttr: function( values, parentValues, parentModel ) {
				var toggleShortcodeDataToggle = {},
					collapseID                = '#accordion-' + this.model.get( 'cid' );

				if ( 'yes' === values.open ) {
					toggleShortcodeDataToggle[ 'class' ] = 'active';
				}

				toggleShortcodeDataToggle[ 'data-toggle' ] = 'collapse';
				if ( 'toggles' !== parentValues.type ) {
					toggleShortcodeDataToggle[ 'data-parent' ] = '#accordion-cid' + parentModel.attributes.cid;
				}
				toggleShortcodeDataToggle[ 'data-target' ] =  collapseID;
				toggleShortcodeDataToggle.href           =  collapseID;

				return toggleShortcodeDataToggle;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildHeadingAttr: function() {
				var that = this,
					headingAttr = {
						class: 'fusion-toggle-heading'
					};

				headingAttr = _.fusionInlineEditor( {
					cid: that.model.get( 'cid' ),
					param: 'title',
					'disable-return': true,
					'disable-extra-spaces': true,
					toolbar: false
				}, headingAttr );

				return headingAttr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildContentAttr: function() {
				var that = this,
					contentAttr = {
						class: 'panel-body toggle-content fusion-clearfix'
					};

				contentAttr = _.fusionInlineEditor( {
					cid: that.model.get( 'cid' )
				}, contentAttr );

				return contentAttr;
			}
		} );
	} );
}( jQuery ) );
