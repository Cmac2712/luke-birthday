/* global fusionredux_change, wp */

/*global fusionredux_change, fusionredux*/

(function( $ ) {
    "use strict";

    fusionredux.field_objects = fusionredux.field_objects || {};
    fusionredux.field_objects.gallery = fusionredux.field_objects.gallery || {};

    $( document ).ready(
        function() {
            //fusionredux.field_objects.gallery.init();
        }
    );

    fusionredux.field_objects.gallery.init = function( selector ) {


        if ( !selector ) {
            selector = $( document ).find( '.fusionredux-container-gallery:visible' );
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
                // When the user clicks on the Add/Edit gallery button, we need to display the gallery editing
                el.on(
                    {
                        click: function( event ) {
                            // hide gallery settings used for posts/pages
                            wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                                template: function(view){
                                  return;
                                }
                            });

                            var current_gallery = $( this ).closest( 'fieldset' );

                            if ( event.currentTarget.id === 'clear-gallery' ) {
                                //remove value from input

                                var rmVal = current_gallery.find( '.gallery_values' ).val( '' );

                                //remove preview images
                                current_gallery.find( ".screenshot" ).html( "" );

                                return;

                            }

                            // Make sure the media gallery API exists
                            if ( typeof wp === 'undefined' || !wp.media || !wp.media.gallery ) {
                                return;
                            }
                            event.preventDefault();

                            // Activate the media editor
                            var $$ = $( this );

                            var val = current_gallery.find( '.gallery_values' ).val();
                            var final;

                            if ( !val ) {
                                final = '[gallery ids="0"]';
                            } else {
                                final = '[gallery ids="' + val + '"]';
                            }

                            var frame = wp.media.gallery.edit( final );

                            // When the gallery-edit state is updated, copy the attachment ids across
                            frame.state( 'gallery-edit' ).on(
                                'update', function( selection ) {

                                    //clear screenshot div so we can append new selected images
                                    current_gallery.find( ".screenshot" ).html( "" );

                                    var element, preview_html = "", preview_img;
                                    var ids = selection.models.map(
                                        function( e ) {
                                            element = e.toJSON();
                                            preview_img = typeof element.sizes.thumbnail !== 'undefined' ? element.sizes.thumbnail.url : element.url;
                                            preview_html = "<a class='of-uploaded-image' href='" + preview_img + "'><img class='fusionredux-option-image' src='" + preview_img + "' alt='' /></a>";
                                            current_gallery.find( ".screenshot" ).append( preview_html );

                                            return e.id;
                                        }
                                    );

                                    current_gallery.find( '.gallery_values' ).val( ids.join( ',' ) );
                                    fusionredux_change( current_gallery.find( '.gallery_values' ) );

                                }
                            );

                            return false;
                        }
                    }, '.gallery-attachments'
                );
            }
        );

    };
})( jQuery );
