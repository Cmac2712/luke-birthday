/* global FusionPageBuilderApp, fusionAllElements */
( function( $ ) {

	$( document ).ready( function() {

		FusionPageBuilderApp.wpWidgetShortcodeFilter = function( attributes, view ) {
			var newAttributes,
				defaults,
				widgetParams,
				formatName,
				widget; // eslint-disable-line no-unused-vars

			widget = view.settingsView.getWidget();

			if ( ! widget ) {
				return attributes;
			}

			widgetParams = Object.keys( widget.fields );
			defaults = Object.keys( fusionAllElements.fusion_widget.defaults );
			newAttributes = {
				params: {}
			};

			formatName = function ( className, name ) {
				var prefix = className.toLowerCase() + '__';

				try {
					prefix += name.match( /\[(.*?)\]/g ).slice( -1 )[ 0 ].replace( /\[|(\])/g, '' );
				} catch ( e ) {
					return prefix;
				}

				return prefix;
			};

			if ( widgetParams ) {
				_.each( attributes.params, function( param, key ) {
					if ( widgetParams.includes( key ) || defaults.includes( key ) ) {
						newAttributes.params[ key ] = param;
					}
				} );
			}

			if ( widget.isInvalid ) {
				view.$el
				.find( '.fusion-widget-settings-form' )
				.find( 'fieldset, input, select, textarea' )
				.not( '[type="button"]' )
				.each( function() {
					var key = formatName( attributes.params.type, this.name );
					if ( widgetParams.includes( key ) ) {
						newAttributes.params[ key ] = attributes.params[ this.id ];
						if ( 'checkbox' === this.type ) {
							newAttributes.params[ key ] =  this.checked ? this.value : '';
						}
					}
				} );
			}

			return newAttributes;
		};

	} );

}( jQuery ) );
