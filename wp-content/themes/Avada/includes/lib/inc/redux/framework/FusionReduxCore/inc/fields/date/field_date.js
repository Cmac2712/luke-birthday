/*global jQuery, document, fusionredux*/

(function( $ ) {
    "use strict";

    fusionredux.field_objects = fusionredux.field_objects || {};
    fusionredux.field_objects.date = fusionredux.field_objects.date || {};

    $( document ).ready(
        function() {
            //fusionredux.field_objects.date.init();
        }
    );

    fusionredux.field_objects.date.init = function( selector ) {
        if ( !selector ) {
            selector = $( document ).find( '.fusionredux-container-date:visible' );
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
//                        var someArr = []
//                        someArr = i;
//                        console.log(someArr);

//                var str = JSON.parse('{"fusionredux_demo[opt-multi-check]":{"fusionredux_demo[opt-multi-check][1]":"1","fusionredux_demo[opt-multi-check][2]":"","fusionredux_demo[opt-multi-check][3]":""}}');
//                console.log (str);
//
//                $.each(str, function(idx, val){
//                    var tmpArr = new Object();
//                    var count = 1;
//
//                    $.each(val, function (i, v){
//
//                        tmpArr[count] = v;
//                        count++;
//                    });
//
//                    var newArr = {};
//                    newArr[idx] = tmpArr;
//                    var newJSON = JSON.stringify(newArr)
//                    //console.log(newJSON);
//                });

                el.find( '.fusionredux-datepicker' ).each( function() {

                    $( this ).datepicker({
                        beforeShow: function(input, instance){
                            var el = $('#ui-datepicker-div');
                            //$.datepicker._pos = $.datepicker._findPos(input); //this is the default position
                            var popover = instance.dpDiv;
                            $('.fusionredux-container:first').append(el);
                            $('#ui-datepicker-div').hide();
                            setTimeout(function() {
                                popover.position({
                                    my: 'left top',
                                    at: 'left bottom',
                                    collision: 'none',
                                    of: input
                                });
                            }, 1);
                        }
                    });
                });
            }
        );


    };
})( jQuery );
