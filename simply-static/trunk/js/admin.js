'use strict';
jQuery( document ).ready( function() {

    // show / hide tabs:
    jQuery( '#sistTabs' ).find( 'a' ).click( function() {
        jQuery( '#sistTabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
        jQuery( '.tab-pane' ).removeClass( 'active' );

        var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
        jQuery( '#' + id ).addClass( 'active' );
        jQuery( this ).addClass( 'nav-tab-active' );
    });

    // set active tab on page load:
    var activeTab = window.location.hash.replace( '#tab-', '' );

    // if no tab hash, default to the first tab
    if ( activeTab === '' ) {
        activeTab = jQuery( '.tab-pane' ).attr( 'id' );
    }

    jQuery( '#' + activeTab ).addClass( 'active' );
    jQuery( '#' + activeTab + '-tab' ).addClass( 'nav-tab-active' );

    // pretend the user clicked on the active tab
    jQuery( '.nav-tab-active' ).click();

});
