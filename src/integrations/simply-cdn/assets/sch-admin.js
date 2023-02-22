jQuery(document).ready(function ($) {
    // Ajax for clear cache
    $('#sch-clear-cache').on('click', function (e) {
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: sch_ajax.ajax_url,
            data: {'action': 'clear_cache', 'nonce': sch_ajax.cache_nonce},
            success: function (response) {
                if (response.success) {
                    $('<p class="success">' + sch_ajax.cache_cleared + '</p>').insertAfter('#sch-clear-cache');
                }
            }
        });

    });

    // Ajax for saving the token
    $('#simply-cdn-connect').on('click', function (e) {
        let token = $('#sch_token').val();

        $.ajax({
            type: 'post',
            dataType: 'json',
            url: sch_ajax.ajax_url,
            data: {'action': 'update_token', 'security-token': token, 'nonce': sch_ajax.token_nonce},
            success: function (response) {
                if (response.success) {
                    $('<p style="color:#2aa42a;">' + sch_ajax.token_connected + '</p>').insertAfter('#simply-cdn-connect').delay(3000).fadeOut(300);

                    setTimeout(function () {
                        location.reload(true);
                    }, 3500);
                } else {
                    $('<p style="color:#e61a3d;">' + response.error_message + '</p>').insertAfter('#simply-cdn-connect').delay(3000).fadeOut(300);
                }
            }
        });
    });
});
