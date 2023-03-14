'use strict';
jQuery(document).ready(function ($) {
	var STATIC_PAGES_PER_PAGE = 50; // max number of pages to show at once
	var done = true;
	var refreshTimer = null;

	// display the export and activity log on page load
	display_export_log();
	display_activity_log();
	initiate_action();

	$('#sistContainer #generate').click(function (e) {
		$('#sistContainer #activityLog').html('');
		initiate_action('start');
	});

	$('#sistContainer #cancel').click(function (e) {
		initiate_action('cancel');
	});

	$(document.body).on( 'change', '#simply_static_blog_id', function (){
		display_export_log();
		display_activity_log();
	});

	// disable all actions and show spinner
	function initiate_action(action) {
		if (action == null) {
			action = 'ping';
		}

		// cancel existing timer
		if (refreshTimer != null) {
			clearInterval(refreshTimer);
		}
		// send action now
		send_action_to_archive_manager(action);
		// set loop for pinging server
		refreshTimer = setInterval(function () {
			send_action_to_archive_manager('ping');
		}, 2000);
	}

	function send_action_site_setting() {
		if ( ! $('#simply_static_use_settings_network').length ) {
			return 'site';
		}

		if ( $('#simply_static_use_settings_network').prop('checked') ) {
			return 'network';
		}

		return 'site';
	}

	// where action is one of 'start', 'continue', 'cancel'
	function send_action_to_archive_manager(action) {
		var data = {
			'_ajax_nonce': $('#simply_static_nonce').val(),
			'action': 'static_archive_action',
			'perform': action,
			'blog_id': $('#simply_static_blog_id').val(),
			'is_network_admin': ss_generate.is_network_admin,
			'settings_type': send_action_site_setting()
		};

		$.post(window.ajaxurl, data, function (response) {
			if (response.action == 'start') {
				$('#generate').attr('disabled', 'disabled');
				$('#sistContainer .actions .spinner').addClass('is-active');
			}
			handle_response_from_archive_manager(response);
		});
	}

	function handle_response_from_archive_manager(response) {
		// loop through the responses and create an .activity div for each one
		// in #activityLog
		var $activityLog = $('#activityLog');
		$activityLog.html(response.activity_log_html)
			.scrollTop($activityLog.prop('scrollHeight'));

		if (response.done == true && done == false) {
			display_export_log();
		}

		done = response.done;

		// only adjust the button/spinner state on a 'ping'
		// (ensures that the job has had time to process the action)
		if (response.action == 'ping') {
			if (done == true) {
				// remove spinner and show #generate
				$('#sistContainer .actions .spinner').removeClass('is-active');
				$('#sistContainer #generate').removeClass('hide');
				$('#sistContainer #cancel').addClass('hide');
				$('#sistContainer #generate').removeAttr('disabled');
				clearInterval(refreshTimer);
			} else {
				// Enable cancel button.
				$('#sistContainer #cancel').removeClass('hide');

				// Disable generate button.
				$('#generate').removeAttr('disabled');
				$('#generate').addClass('hide');
			}
		}
	}

	function display_export_log() {
		var data = {
			'_ajax_nonce': $('#simply_static_nonce').val(),
			'action': 'render_export_log',
			'page': 1,
			'per_page': STATIC_PAGES_PER_PAGE,
			'blog_id': $('#simply_static_blog_id').val(),
			'is_network_admin': ss_generate.is_network_admin
		};

		var $exportLog = $('#exportLog');
		$exportLog.html("<span class='spinner is-active'></span>");

		$.post(window.ajaxurl, data, function (response) {
			$exportLog.html(response.html);
		});
	}

	function display_activity_log() {
		var data = {
			'_ajax_nonce': $('#simply_static_nonce').val(),
			'action': 'render_activity_log',
			'blog_id': $('#simply_static_blog_id').val(),
			'is_network_admin': ss_generate.is_network_admin
		};

		var $activityLog = $('#activityLog');
		$activityLog.html("<span class='spinner is-active'></span>");

		$.post(window.ajaxurl, data, function (response) {
			$activityLog.html(response.html)
				.scrollTop($activityLog.prop('scrollHeight'));
		});
	}

	// -- AJAX pagination ----------------------------------------------------//
	$('#sistContainer #exportLog').on('click', 'a.page-numbers', function (e) {
		e.preventDefault();

		var url = $(this).attr('href');
		var re = /page=(\d+)/;
		var matches = re.exec(url);

		var page = 1;
		if (matches) {
			page = matches[1];
		}

		var data = {
			'_ajax_nonce': $('#simply_static_nonce').val(),
			'action': 'render_export_log',
			'page': page,
			'per_page': STATIC_PAGES_PER_PAGE,
			'blog_id': $('#simply_static_blog_id').val(),
			'is_network_admin': ss_generate.is_network_admin
		};

		$.post(window.ajaxurl, data, function (response) {
			$('#exportLog').html(response.html);
		});
	});

});
