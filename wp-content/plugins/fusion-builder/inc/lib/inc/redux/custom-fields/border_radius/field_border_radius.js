/*global fusionredux*/

(function( $ ) {
    "use strict";

    fusionredux.field_objects = fusionredux.field_objects || {};
    fusionredux.field_objects.border_radius = fusionredux.field_objects.border_radius || {};

    $( document ).ready(
        function() {
            //fusionredux.field_objects.border_radius.init();
        }
    );

    fusionredux.field_objects.border_radius.init = function( selector ) {

        if ( !selector ) {
            selector = $( document ).find( ".fusionredux-group-tab:visible" ).find( '.fusionredux-container-border_radius:visible' );
        }

        $( selector ).each(
            function() {
                var el = $( this );
                var parent = el;
                if ( !el.hasClass( 'fusionredux-field-container' ) ) {
                    parent = el.parents( '.fusionredux-field-container:first' );
                }
                if ( parent.is( ":hidden" ) ) { // Skip hidden fields
                    return;
                }
                if ( parent.hasClass( 'fusionredux-field-init' ) ) {
                    parent.removeClass( 'fusionredux-field-init' );
                } else {
                    return;
                }

                el.find( '.fusionredux-border_radius-input' ).on(
                    'change', function() {

                        var value = $( this ).val();

                        if ( $( this ).hasClass( 'fusionredux-border_radius-all' ) ) {
                            $( this ).parents( '.fusionredux-field:first' ).find( '.fusionredux-border_radius-value' ).each(
                                function() {
                                    $( this ).val( value );
                                }
                            );
                        } else {
                            $( '#' + $( this ).attr( 'rel' ) ).val( value );
                        }
                    }
                );
            }
        );
    };
})( jQuery );
