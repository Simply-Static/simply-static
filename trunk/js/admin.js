'use strict';
jQuery( document ).ready( function( $ ) {

    // show / hide tabs:
    $( '#sistContainer #sistTabs' ).find( 'a' ).click( function() {
        $( '#sistContainer #sistTabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
        $( '#sistContainer .tab-pane' ).removeClass( 'active' );

        var id = $( this ).attr( 'id' ).replace( '-tab', '' );
        $( '#sistContainer #' + id ).addClass( 'active' );
        $( this ).addClass( 'nav-tab-active' );
    } );

    // set active tab on page load:
    var activeTab = window.location.hash.replace( '#tab-', '' );

    // if no tab hash, default to the first tab
    if ( activeTab === '' ) {
        activeTab = $( '#sistContainer .tab-pane' ).attr( 'id' );
    }

    $( '#sistContainer #' + activeTab ).addClass( 'active' );
    $( '#sistContainer #' + activeTab + '-tab' ).addClass( 'nav-tab-active' );

    // pretend the user clicked on the active tab
    $( '#sistContainer .nav-tab-active' ).click();

    // -----------------------------------------------------------------------//

    // delivery method selection:
    $( '#sistContainer #deliveryMethod' ).change( function() {
        var selected = $( this ).val();
        $( '#sistContainer .delivery-method' ).removeClass( 'active' );
        $( '#sistContainer .' + selected + '.delivery-method' ).addClass( 'active ');
    } );

    // pretend the user selected a value
    $( '#sistContainer #deliveryMethod' ).change();

    // -----------------------------------------------------------------------//

    // Upon form submission, disable the Generate button and show a spinner
    $( '#sistContainer #generateForm' ).submit( function( e ) {
        $( '#sistContainer #generateForm .spinner' ).addClass( 'is-active' );
        $( '#sistContainer #generate' ).attr( 'disabled', 'disabled' );
    } );


} );
