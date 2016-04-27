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

	// display the export and activity log on page load
	display_export_log();
	display_activity_log();

	// for pausing the continuing requests to WP ajax archive generation
	var pause = false;

	$( '#sistContainer #generate' ).click( function( e ) {
		$( '#sistContainer #activityLog' ).html('');
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

	// disable all actions and show spinner
	function initiate_action( action ) {
		pause = false;

		$( '#sistContainer .actions input' ).attr( 'disabled', 'disabled' );
		$( '#sistContainer .actions .spinner' ).addClass( 'is-active' );

		send_action_to_archive_manager( action );
	}

	// where action is one of 'start', 'continue', 'cancel'
	function send_action_to_archive_manager( action, handle_failure ) {
		// always handle failures unless told otherwise
		handle_failure = handle_failure || true;

		var data = {
			'action': 'generate_static_archive',
			'perform': action
		};

		$.post( window.ajaxurl, data, function( response ) {
			handle_response_from_archive_manager( response );
		} ).fail( function( response ) {
			// Try the request again to pick up the error. If we get yet another
			// error, don't make another request (infinite loop prevention).
			if ( handle_failure ) {
				send_action_to_archive_manager( action, false );
			}
		} );
	}

	function handle_response_from_archive_manager( response ) {
		// loop through the responses and create an .activity div for each one
		// in #activityLog
		$('#activityLog').html( response.activity_log_html );

		// re-enable and hide all actions
		$( '#sistContainer .actions input' )
			.removeAttr( 'disabled' )
			.addClass( 'hide' );

		if ( response.done == true ) {
			// remove spinner and show #generate
			$( '#sistContainer .actions .spinner' ).removeClass( 'is-active' );
			$( '#sistContainer #generate' ).removeClass( 'hide' );

			display_export_log();
		} else {
			if ( pause == true ) {
				// remove spinner and show #resume/#cancel
				$( '#sistContainer .actions .spinner' ).removeClass( 'is-active' );
				$( '#sistContainer #resume, #sistContainer #cancel' ).removeClass( 'hide' );
			} else {
				// show #hide and send ajax request to continue generating
				$( '#sistContainer #pause' ).removeClass( 'hide' );
				send_action_to_archive_manager( 'continue' );
			}
		}
	}

	function display_export_log() {
		var data = {
			'action': 'render_export_log'
		};

		$('#exportLog').html( "<span class='spinner is-active'></span>" );

		$.post( window.ajaxurl, data, function( response ) {
			$('#exportLog').html( response.html );
		} );
	}

	function display_activity_log() {
		var data = {
			'action': 'render_activity_log'
		};

		$('#activityLog').html( "<span class='spinner is-active'></span>" );

		$.post( window.ajaxurl, data, function( response ) {
			$('#activityLog').html( response.html );
		} );
	}

} );
