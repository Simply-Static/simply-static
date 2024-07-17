jQuery(document).ready(function ($) {
    let failed_tests_html = '<span style="color:#FFFFFF;background:#d63f41;padding: 1px 5px;border-radius:50%;margin-left:5px;">' + ss_admin_status_object.failed_tests + '</span>';
    let failed_tests = ss_admin_status_object.failed_tests > 0 ? failed_tests_html : '';

    function updateStatus() {
        $.ajax({
            url: ss_admin_status_object.ajax_url,
            type: 'POST',
            data: {
                action: 'ss_admin_get_status',
                security: ss_admin_status_object.nonce,
            },
            success: function (response) {
                let message = '<span style="color:#ddc551;font-weight:bold">' + ss_admin_status_object.translations.idle + '</span>' + failed_tests;;

                if (response.data.status === 'running') {
                    message = '<span style="color:#4ee536;font-weight:bold">' + ss_admin_status_object.translations.running + '</span>' + failed_tests;;
                }

                if (response.data.status === 'error') {
                    message = '<span style="color:#d63f41;font-weight:bold">' + ss_admin_status_object.translations.error + '</span>' + failed_tests;;
                }

                // render html
                $('#wp-admin-bar-ss-admin-bar .ab-item').html(ss_admin_status_object.translations.label + ' ' + message);
            }
        });
    }

    // Initial call
    updateStatus();

    // Update every 10 seconds
    setInterval(updateStatus, 10000);
});
