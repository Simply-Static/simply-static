'use strict';
jQuery( document ).ready( function() {

    // show / hide tabs:
    jQuery( '#sistContainer #sistTabs' ).find( 'a' ).click( function() {
        jQuery( '#sistContainer #sistTabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
        jQuery( '#sistContainer .tab-pane' ).removeClass( 'active' );

        var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
        jQuery( '#sistContainer #' + id ).addClass( 'active' );
        jQuery( this ).addClass( 'nav-tab-active' );
    } );

    // set active tab on page load:
    var activeTab = window.location.hash.replace( '#tab-', '' );

    // if no tab hash, default to the first tab
    if ( activeTab === '' ) {
        activeTab = jQuery( '#sistContainer .tab-pane' ).attr( 'id' );
    }

    jQuery( '#sistContainer #' + activeTab ).addClass( 'active' );
    jQuery( '#sistContainer #' + activeTab + '-tab' ).addClass( 'nav-tab-active' );

    // pretend the user clicked on the active tab
    jQuery( '#sistContainer .nav-tab-active' ).click();

    // -----------------------------------------------------------------------//

    // delivery method selection:
    jQuery( '#sistContainer #deliveryMethod' ).change( function() {
        var selected = jQuery( this ).val();
        jQuery( '#sistContainer .delivery-method' ).removeClass( 'active' );
        jQuery( '#sistContainer .' + selected + '.delivery-method' ).addClass( 'active ');
    } );

    // pretend the user selected a value
    jQuery( '#sistContainer #deliveryMethod' ).change();

    // -----------------------------------------------------------------------//

    jQuery( '#sistContainer #generateForm' ).submit( function( e ) {
        jQuery( '#sistContainer #generateForm .spinner' ).addClass( 'is-active' );
        jQuery( '#sistContainer #generate' ).attr( 'disabled', 'disabled' );
    } );


} );
