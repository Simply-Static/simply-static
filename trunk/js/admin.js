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

	// // Upon form submission, disable the Generate button and show a spinner
	// $( '#sistContainer #generateForm' ).submit( function( e ) {
	//	 $( '#sistContainer #generateForm .spinner' ).addClass( 'is-active' );
	//	 $( '#sistContainer #generate' ).attr( 'disabled', 'disabled' );
	// } );
	var pause = false;

	$( '#sistContainer #generate' ).click( function( e ) {
		initiate_action( 'start' );
	} );

	$( '#sistContainer #resume' ).click( function( e ) {
		initiate_action( 'continue' );
	} );

	$( '#sistContainer #cancel' ).click( function( e ) {
		initiate_action( 'cancel' );
	} );

	$( '#sistContainer #pause' ).click( function( e ) {
		$( this ).attr( 'disabled', 'disabled' );
		pause = true;
	} );

	function initiate_action( action ) {
		pause = false;

		$( '#sistContainer .actions input' ).attr( 'disabled', 'disabled' );
		$( '#sistContainer .actions .spinner' ).addClass( 'is-active' );

		send_action_to_archive_manager( action );
	}

	// where action is one of 'start', 'continue', 'cancel'
	function send_action_to_archive_manager( action ) {
		var data = {
			'action': 'generate_static_archive',
			'perform': action
		};

		$.post( window.ajaxurl, data, function(response) {
			handle_response_from_archive_manager( response );
		} );
	}

	function handle_response_from_archive_manager( response ) {
		console.log( JSON.stringify( response ) );

		$( '#sistContainer .actions input' )
			.removeAttr( 'disabled' )
			.addClass( 'hide' );

		if ( response.done == true ) {
			$( '#sistContainer .actions .spinner' ).removeClass( 'is-active' );
			$( '#sistContainer #generate' ).removeClass( 'hide' );
		} else {
			if ( pause == true ) {
				$( '#sistContainer .actions .spinner' ).removeClass( 'is-active' );
				$( '#sistContainer #resume, #sistContainer #cancel' ).removeClass( 'hide' );
			} else {
				$( '#sistContainer #pause' ).removeClass( 'hide' );
				send_action_to_archive_manager( 'continue' );
			}
		}
	}

} );
