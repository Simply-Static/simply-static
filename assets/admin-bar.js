jQuery(document).ready(function ($) {
    function updateStatus() {
        $.ajax({
            url: ss_admin_status_object.ajax_url,
            type: 'POST',
            data: {
                action: 'ss_admin_get_status',
                security: ss_admin_status_object.nonce,
            },
            success: function (response) {
                let message = '<span style="color:#ddc551;font-weight:bold">' + ss_admin_status_object.translations.idle + '</span>';

                if (response.data.status === 'running') {
                    message = '<span style="color:#4ee536;font-weight:bold">' + ss_admin_status_object.translations.running + '</span>';
                }

                if (response.data.status === 'error') {
                    message = '<span style="color:#d63f41;font-weight:bold">' + ss_admin_status_object.translations.error + '</span>';
                }

                // render html
                $('#wp-admin-bar-ss-admin-bar .ab-item').html(ss_admin_status_object.translations.label + ' ' + message);
            }
        });
    }

    // Initial call
    updateStatus();

    // Update every 5 seconds
    setInterval(updateStatus, 5000);
});
