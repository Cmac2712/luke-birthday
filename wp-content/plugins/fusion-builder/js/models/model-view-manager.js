/* global fusionAllElements, fusionBuilderConfig */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {

	$( document ).ready( function() {

		var fusionElements          = [],
			sortedElements          = [],
			fusionGeneratorElements = [],
			fusionComponents        = [],
			componentsCounter       = 0,
			fusionUsedComponents    = [],
			postContent             = jQuery( '#content' ).text();


		// Loop over all available elements and add them to Fusion Builder.
		sortedElements = _.sortBy( fusionAllElements, function( element ) {
			return element.name.toLowerCase();
		} );

		_.each( sortedElements, function( element ) {
			var newElement,
				targetObject = fusionGeneratorElements;

			if ( 'undefined' === typeof element.hide_from_builder ) {

				newElement = {
					title: element.name,
					label: element.shortcode
				};

				if ( 'undefined' !== typeof element.component && element.component ) {
					targetObject = fusionComponents;
				}
				if ( 'undefined' === typeof element.generator_only ) {
					fusionElements.push( newElement );
				}

				targetObject.push(
					Object.assign(
						{},
						newElement,
						{
							generator_only: 'undefined' !== typeof element.generator_only ? true : element.generator_only,
							templates: 'undefined' !== typeof element.templates ? element.templates : false,
							components_per_template: 'undefined' !== typeof element.components_per_template ? element.components_per_template : false
						}
					)
				);
			}
		} );

		// Filter compoments.
		fusionComponents.forEach( function( component ) {
			var re = new RegExp( '\\[' + component.label, 'g' );

			// Update usedComponents array.
			fusionUsedComponents[ component.label ] = ( postContent.match( re ) || [] ).length;

			if ( 'string' === typeof fusionBuilderConfig.template_category && ( 'object' !== typeof component.templates || component.templates.includes( fusionBuilderConfig.template_category ) ) ) {
				componentsCounter++;
			}
		} );

		FusionPageBuilder.ViewManager = Backbone.Model.extend( {
			defaults: {
				modules: fusionElements,
				generator_elements: fusionGeneratorElements,
				components: fusionComponents,
				componentsCounter: componentsCounter,
				usedComponents: fusionUsedComponents,
				elementCount: 0,
				views: {}
			},

			getView: function( cid ) {
				return this.get( 'views' )[ cid ];
			},

			getChildViews: function( parentID ) {
				var views      = this.get( 'views' ),
					childViews = {};

				_.each( views, function( view, key ) {
					if ( parentID === view.model.attributes.parent ) {
						childViews[ key ] = view;
					}
				} );

				return childViews;
			},

			generateCid: function() {
				var elementCount = this.get( 'elementCount' ) + 1;

				this.set( { elementCount: elementCount } );

				return elementCount;
			},

			addView: function( cid, view ) {
				var views = this.get( 'views' );

				views[ cid ] = view;
				this.set( { views: views } );
			},

			removeView: function( cid ) {
				var views    = this.get( 'views' ),
					updatedViews = {};

				_.each( views, function( value, key ) {
					if ( key != cid ) { // jshint ignore:line
						updatedViews[ key ] = value;
					}
				} );

				this.set( { views: updatedViews } );
			},

			removeViews: function() {
				var updatedViews = {};
				this.set( { views: updatedViews } );
			},

			countElementsByType: function( elementType ) {
				var views = this.get( 'views' ),
					num   = 0;

				_.each( views, function( view ) {
					if ( view.model.attributes.type === elementType ) {
						num++;
					}
				} );

				return num;
			}

		} );

		window.FusionPageBuilderViewManager = new FusionPageBuilder.ViewManager(); // jshint ignore:line

	} );

}( jQuery ) );
